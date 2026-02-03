<?php
if (!isset($GLOBALS['ACADEMIC_CONTEXT_INCLUDED'])) {
    $GLOBALS['ACADEMIC_CONTEXT_INCLUDED'] = true;

    if (!function_exists('ensureAcademicCalendarColumn')) {
        function ensureAcademicCalendarColumn(mysqli $conn, string $column, string $definition): void
        {
            $columnName = preg_replace('/[^A-Za-z0-9_]/', '', $column);
            if ($columnName === '') {
                return;
            }
            $columnCheckSql = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'academic_calendar' AND COLUMN_NAME = '" . mysqli_real_escape_string($conn, $columnName) . "'";
            $columnCheckResult = mysqli_query($conn, $columnCheckSql);
            if ($columnCheckResult) {
                $row = mysqli_fetch_assoc($columnCheckResult);
                mysqli_free_result($columnCheckResult);
                if ((int)($row['cnt'] ?? 0) > 0) {
                    return;
                }
            }
            $alterSql = "ALTER TABLE academic_calendar ADD COLUMN `{$columnName}` {$definition}";
            @mysqli_query($conn, $alterSql);
        }
    }

    if (!function_exists('ensureAcademicCalendarSchema')) {
        function ensureAcademicCalendarSchema(mysqli $conn): void
        {
            ensureAcademicCalendarColumn($conn, 'academic_year', "VARCHAR(20) NOT NULL DEFAULT ''");
            ensureAcademicCalendarColumn($conn, 'semester_number', 'INT NULL DEFAULT NULL');
            ensureAcademicCalendarColumn($conn, 'label_override', "VARCHAR(120) NOT NULL DEFAULT ''");
            $fillYearSql = "UPDATE academic_calendar SET academic_year = CASE WHEN academic_year = '' THEN CONCAT(YEAR(start_date), '-', YEAR(end_date)) ELSE academic_year END";
            @mysqli_query($conn, $fillYearSql);
        }
    }

    if (!function_exists('fetchAcademicTerms')) {
        function fetchAcademicTerms(mysqli $conn, array $options = []): array
        {
            ensureAcademicCalendarSchema($conn);
            $schoolName = isset($options['school_name']) ? trim((string)$options['school_name']) : '';
            $sql = "SELECT id, school_name, semester_term, academic_year, semester_number, label_override, start_date, end_date FROM academic_calendar";
            $params = [];
            $types = '';
            if ($schoolName !== '') {
                $sql .= " WHERE school_name = ?";
                $types .= 's';
                $params[] = $schoolName;
            }
            $sql .= " ORDER BY start_date DESC, id DESC";
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                return [];
            }
            if ($schoolName !== '') {
                mysqli_stmt_bind_param($stmt, 's', $schoolName);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if (!$result) {
                mysqli_stmt_close($stmt);
                return [];
            }
            $today = new DateTimeImmutable('today');
            $terms = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $startDate = $row['start_date'] ?? null;
                $endDate = $row['end_date'] ?? null;
                $startObj = parseAcademicDate($startDate);
                $endObj = parseAcademicDate($endDate);
                $isCurrent = false;
                if ($startObj && $endObj) {
                    $isCurrent = $today >= $startObj && $today <= $endObj;
                }
                $semesterNumber = $row['semester_number'] !== null ? (int)$row['semester_number'] : null;
                $labelPieces = [];

                $displayAcademicYear = '';
                $rawAcademicYear = isset($row['academic_year']) ? trim((string)$row['academic_year']) : '';
                if ($startObj && $endObj) {
                    $startYear = (int)$startObj->format('Y');
                    $endYear = (int)$endObj->format('Y');
                    $endYearForDisplay = ($endYear === $startYear) ? $startYear + 1 : $endYear;
                    $defaultAcademicYear = $startYear . '-' . $endYearForDisplay;

                    if ($rawAcademicYear === '') {
                        $displayAcademicYear = $defaultAcademicYear;
                    } elseif (preg_match('/^(\d{4})\s*-\s*(\d{4})$/', $rawAcademicYear, $matches)) {
                        $startCandidate = (int)$matches[1];
                        $endCandidate = (int)$matches[2];
                        if ($startCandidate === $endCandidate) {
                            $displayAcademicYear = $startCandidate . '-' . ($startCandidate + 1);
                        } else {
                            $displayAcademicYear = $startCandidate . '-' . $endCandidate;
                        }
                    } else {
                        $displayAcademicYear = $rawAcademicYear;
                    }
                } else {
                    $displayAcademicYear = $rawAcademicYear;
                }

                if (!empty($row['label_override'])) {
                    $labelPieces[] = $row['label_override'];
                } else {
                    if ($semesterNumber !== null) {
                        $labelPieces[] = 'Semester ' . $semesterNumber;
                    }
                    $termText = $row['semester_term'] !== '' ? ucfirst((string)$row['semester_term']) . ' Term' : '';
                    if ($termText !== '') {
                        $labelPieces[] = $termText;
                    }
                    if ($displayAcademicYear !== '') {
                        $labelPieces[] = 'AY ' . $displayAcademicYear;
                    }
                }
                if ($startObj && $endObj) {
                    $labelPieces[] = $startObj->format('d M Y') . ' - ' . $endObj->format('d M Y');
                }
                $terms[] = [
                    'id' => (int)$row['id'],
                    'school_name' => $row['school_name'],
                    'semester_term' => $row['semester_term'],
                    'semester_number' => $semesterNumber,
                    'academic_year' => $displayAcademicYear,
                    'label_override' => $row['label_override'] ?? '',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'label' => implode(' • ', array_filter($labelPieces)),
                    'is_current' => $isCurrent,
                ];
            }
            mysqli_free_result($result);
            mysqli_stmt_close($stmt);
            return $terms;
        }
    }

    if (!function_exists('parseAcademicDate')) {
        function parseAcademicDate(?string $date): ?DateTimeImmutable
        {
            if ($date === null || $date === '' || $date === '0000-00-00') {
                return null;
            }
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            return $parsed ?: null;
        }
    }

    if (!function_exists('termContainsDate')) {
        function termContainsDate(array $term, DateTimeImmutable $date): bool
        {
            $start = parseAcademicDate($term['start_date'] ?? null);
            $end = parseAcademicDate($term['end_date'] ?? null);
            if (!$start || !$end) {
                return false;
            }
            return $date >= $start && $date <= $end;
        }
    }

    if (!function_exists('termHasEndedBefore')) {
        function termHasEndedBefore(array $term, DateTimeImmutable $date): bool
        {
            $end = parseAcademicDate($term['end_date'] ?? null);
            if (!$end) {
                return false;
            }
            return $end < $date;
        }
    }

    if (!function_exists('pickPreferredAcademicTerm')) {
        function pickPreferredAcademicTerm(array $terms, DateTimeImmutable $today): ?array
        {
            $upcoming = null;
            $upcomingStart = null;
            $recent = null;
            $recentStart = null;

            foreach ($terms as $termRow) {
                if (termContainsDate($termRow, $today)) {
                    return $termRow;
                }

                $startObj = parseAcademicDate($termRow['start_date'] ?? null);
                if (!$startObj) {
                    continue;
                }

                if ($startObj > $today) {
                    if ($upcoming === null || $startObj < $upcomingStart) {
                        $upcoming = $termRow;
                        $upcomingStart = $startObj;
                    }
                } else {
                    if ($recent === null || $startObj > $recentStart) {
                        $recent = $termRow;
                        $recentStart = $startObj;
                    }
                }
            }

            if ($upcoming !== null) {
                return $upcoming;
            }

            if ($recent !== null) {
                return $recent;
            }

            return $terms ? $terms[0] : null;
        }
    }

    if (!function_exists('resolveAcademicContext')) {
        function resolveAcademicContext(mysqli $conn, array $options = []): array
        {
            $schoolName = isset($options['school_name']) ? (string)$options['school_name'] : '';
            $defaultSemester = $options['default_semester'] ?? null;
            $terms = fetchAcademicTerms($conn, ['school_name' => $schoolName]);
            $overrideId = isset($_SESSION['academic_term_override']) ? (int)$_SESSION['academic_term_override'] : 0;
            $today = new DateTimeImmutable('today');

            $active = null;
            $overrideTerm = null;
            if ($overrideId > 0) {
                foreach ($terms as $termRow) {
                    if ($termRow['id'] === $overrideId) {
                        $overrideTerm = $termRow;
                        break;
                    }
                }
            }

            $preferred = pickPreferredAcademicTerm($terms, $today);

            if ($overrideTerm !== null) {
                $active = $overrideTerm;
            }

            if ($active === null) {
                foreach ($terms as $termRow) {
                    if (!empty($termRow['is_current'])) {
                        $active = $termRow;
                        break;
                    }
                }
            }

            if ($active === null && $defaultSemester !== null) {
                foreach ($terms as $termRow) {
                    if (isset($termRow['semester_number']) && (string)$termRow['semester_number'] === (string)$defaultSemester) {
                        $active = $termRow;
                        break;
                    }
                }
            }

            if ($active === null && $preferred !== null) {
                $active = $preferred;
            }

            if ($active === null && $terms) {
                $active = $terms[0];
            }

            $context = [
                'terms' => $terms,
                'active' => $active,
                'override_id' => $overrideId > 0 ? $overrideId : null,
                'default_semester' => $defaultSemester,
            ];

            $context['date_filter'] = $active ? buildAcademicDateFilter($active) : null;
            if ($active && isset($active['id'])) {
                $_SESSION['active_academic_term_id'] = (int)$active['id'];
            } else {
                unset($_SESSION['active_academic_term_id']);
            }
            return $context;
        }
    }

    if (!function_exists('buildAcademicDateFilter')) {
        function buildAcademicDateFilter(array $term): ?array
        {
            if (empty($term['start_date']) || empty($term['end_date'])) {
                return null;
            }
            return [
                'start' => $term['start_date'],
                'end' => $term['end_date'],
            ];
        }
    }

    if (!function_exists('setAcademicTermOverride')) {
        function setAcademicTermOverride(?int $termId): void
        {
            if ($termId !== null && $termId > 0) {
                $_SESSION['academic_term_override'] = $termId;
            } else {
                unset($_SESSION['academic_term_override']);
            }
        }
    }

    if (!function_exists('getActiveAcademicTermId')) {
        function getActiveAcademicTermId(): ?int
        {
            return isset($_SESSION['active_academic_term_id']) ? (int)$_SESSION['active_academic_term_id'] : null;
        }
    }
}
