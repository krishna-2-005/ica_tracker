<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/settings_helpers.php';
require_once __DIR__ . '/includes/academic_context.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'program_chair') {
    header('Location: login.php');
    exit;
}

function columnExists(mysqli $conn, string $table, string $column): bool
{
    $table = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    $column = preg_replace('/[^A-Za-z0-9_]/', '', $column);
    if ($table === '' || $column === '') {
        return false;
    }
    $check = mysqli_query($conn, "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    $exists = $check && mysqli_num_rows($check) > 0;
    if ($check) {
        mysqli_free_result($check);
    }
    return $exists;
}

if (!function_exists('normalize_elective_short_label')) {
    function normalize_elective_short_label(?string $value): string
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return '';
        }

        $upper = function_exists('mb_strtoupper') ? mb_strtoupper($raw, 'UTF-8') : strtoupper($raw);
        if (preg_match('/\bOE\s*\d+\s*-\s*[A-Z0-9]+\b/u', $upper, $match)) {
            return preg_replace('/\s+/', '', str_replace(' - ', '-', trim($match[0])));
        }

        if (preg_match('/\b(open|department(?:al)?)\s*elective\b/iu', $raw) !== 1) {
            return $raw;
        }

        $number = '';
        if (preg_match('/\belective\s*([0-9]+)\b/iu', $raw, $numMatch) === 1) {
            $number = (string)$numMatch[1];
        } elseif (preg_match('/\b([0-9]+)\b/u', $raw, $numMatch) === 1) {
            $number = (string)$numMatch[1];
        }

        $code = '';
        if (preg_match('/[-:\/]\s*([A-Za-z0-9]+)\s*$/u', $raw, $codeMatch) === 1) {
            $code = strtoupper(trim((string)$codeMatch[1]));
        }

        if ($number !== '' && $code !== '') {
            return 'OE' . $number . '-' . $code;
        }

        return $upper;
    }
}

if (!function_exists('teacher_status_meta')) {
    function teacher_status_meta(float $avgCompletion, float $evaluationCoverage, ?string $lastUpdate): array
    {
        $lastUpdateText = trim((string)$lastUpdate);
        if ($lastUpdateText === '') {
            return [
                'code' => 'no_updates',
                'label' => 'No Updates',
                'class' => 'badge-neutral',
            ];
        }

        if ($avgCompletion >= 85 && $evaluationCoverage >= 70) {
            return [
                'code' => 'on_track',
                'label' => 'On Track',
                'class' => 'badge-success',
            ];
        }

        if ($avgCompletion >= 70) {
            return [
                'code' => 'slight_delay',
                'label' => 'Slight Delay',
                'class' => 'badge-warning',
            ];
        }

        if ($avgCompletion >= 50) {
            return [
                'code' => 'behind',
                'label' => 'Behind',
                'class' => 'badge-orange',
            ];
        }

        return [
            'code' => 'critical_delay',
            'label' => 'Critical Delay',
            'class' => 'badge-danger',
        ];
    }
}

if (!function_exists('teacher_compliance_score')) {
    function teacher_compliance_score(float $avgCompletion, float $evaluationCoverage, int $pendingAlerts, int $resolvedAlerts): float
    {
        $totalAlerts = $pendingAlerts + $resolvedAlerts;
        $alertResponsiveness = $totalAlerts > 0 ? (($resolvedAlerts / $totalAlerts) * 100) : 100;
        $score = ($avgCompletion * 0.45) + ($evaluationCoverage * 0.35) + ($alertResponsiveness * 0.20);
        $score = max(0, min(100, $score));
        return round($score, 1);
    }
}

$class_has_school = columnExists($conn, 'classes', 'school');
$class_has_department = columnExists($conn, 'classes', 'department');

$current_program_chair_id = (int)($_SESSION['user_id'] ?? 0);
$syllabus_threshold = get_syllabus_threshold($conn, $current_program_chair_id);
$performance_threshold = get_performance_threshold($conn, $current_program_chair_id);
$needs_support_label = sprintf('Needs Support (<%d%%)', (int)round($performance_threshold));

$pc_school = '';
$pc_school_id = null;
$pc_school_stmt = mysqli_prepare($conn, "SELECT u.school, u.department, s.id AS school_id FROM users u LEFT JOIN schools s ON s.school_name = u.school WHERE u.id = ? LIMIT 1");
if ($pc_school_stmt) {
    $pc_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    mysqli_stmt_bind_param($pc_school_stmt, "i", $pc_user_id);
    mysqli_stmt_execute($pc_school_stmt);
    $pc_result = mysqli_stmt_get_result($pc_school_stmt);
    if ($pc_row = mysqli_fetch_assoc($pc_result)) {
        if (!empty($pc_row['school'])) {
            $pc_school = $pc_row['school'];
        } elseif (!empty($pc_row['department'])) {
            // Legacy data fallback until every record is migrated to use 'school'
            $pc_school = $pc_row['department'];
        }
        if (isset($pc_row['school_id'])) {
            $pc_school_id = $pc_row['school_id'];
        }
    }
    mysqli_stmt_close($pc_school_stmt);
}

$school_param_provided = array_key_exists('school', $_GET);
$school_filter = '';
if ($school_param_provided) {
    $school_filter = trim($_GET['school']);
} elseif ($pc_school !== '') {
    $school_filter = $pc_school;
}

$available_schools = [];
$schools_res = mysqli_query($conn, "SELECT school_name FROM schools ORDER BY school_name");
if ($schools_res) {
    while ($row = mysqli_fetch_assoc($schools_res)) {
        if (!empty($row['school_name'])) {
            $available_schools[] = $row['school_name'];
        }
    }
    mysqli_free_result($schools_res);
}
if (empty($available_schools)) {
    $fallback_res = mysqli_query($conn, "SELECT DISTINCT school FROM classes WHERE school IS NOT NULL AND school <> '' ORDER BY school");
    if ($fallback_res) {
        while ($row = mysqli_fetch_assoc($fallback_res)) {
            $available_schools[] = $row['school'];
        }
        mysqli_free_result($fallback_res);
    }
}
if ($pc_school !== '' && !in_array($pc_school, $available_schools, true)) {
    $available_schools[] = $pc_school;
    sort($available_schools);
}

$contextSchool = $school_filter !== '' ? $school_filter : $pc_school;
$academicContext = resolveAcademicContext($conn, [
    'school_name' => $contextSchool
]);
$activeTerm = $academicContext['active'] ?? null;
$activeTermId = ($activeTerm && isset($activeTerm['id'])) ? (int)$activeTerm['id'] : 0;
$termDateFilter = $academicContext['date_filter'] ?? null;
$termStartDate = $termDateFilter['start'] ?? null;
$termEndDate = $termDateFilter['end'] ?? null;
$termStartBound = $termStartDate ? $termStartDate . ' 00:00:00' : null;
$termEndBound = $termEndDate ? $termEndDate . ' 23:59:59' : null;
$defaultSemesterNumber = ($activeTerm && isset($activeTerm['semester_number']) && $activeTerm['semester_number'] !== null)
    ? (int)$activeTerm['semester_number']
    : '';

$buildTermWhere = static function (string $alias) use ($activeTermId): string {
    if ($activeTermId <= 0) {
        return '';
    }
    $termId = (int)$activeTermId;
    return " WHERE ({$alias}.academic_term_id = {$termId} OR {$alias}.academic_term_id IS NULL)";
};

$buildTermAnd = static function (string $alias) use ($activeTermId): string {
    if ($activeTermId <= 0) {
        return '';
    }
    $termId = (int)$activeTermId;
    return " AND ({$alias}.academic_term_id = {$termId} OR {$alias}.academic_term_id IS NULL)";
};

$buildDateWhere = static function (string $column) use ($conn, $termStartBound, $termEndBound): string {
    if (!$termStartBound || !$termEndBound) {
        return '';
    }
    $start = mysqli_real_escape_string($conn, $termStartBound);
    $end = mysqli_real_escape_string($conn, $termEndBound);
    return " WHERE {$column} BETWEEN '{$start}' AND '{$end}'";
};

$buildDateAnd = static function (string $column) use ($conn, $termStartBound, $termEndBound): string {
    if (!$termStartBound || !$termEndBound) {
        return '';
    }
    $start = mysqli_real_escape_string($conn, $termStartBound);
    $end = mysqli_real_escape_string($conn, $termEndBound);
    return " AND {$column} BETWEEN '{$start}' AND '{$end}'";
};

// --- AJAX ENDPOINT ---
if (isset($_GET['action']) && $_GET['action'] == 'get_teacher_details') {
    $teacher_id = (int)$_GET['id'];
    $detail_school_filter = (isset($_GET['school']) && $_GET['school'] !== '') ? trim($_GET['school']) : '';
    $detail_semester_filter = (isset($_GET['semester_filter']) && $_GET['semester_filter'] !== '') ? (int)$_GET['semester_filter'] : null;
    $detail_class_filter = (isset($_GET['class_filter']) && $_GET['class_filter'] !== '') ? (int)$_GET['class_filter'] : null;
    $detail_course_filter = (isset($_GET['course_filter']) && $_GET['course_filter'] !== '') ? (int)$_GET['course_filter'] : null;
    $detailMarksDateClause = $buildDateAnd('ism.updated_at');

    // Course-specific data sourced from teacher_subject_assignments
    $courses_sql = "
        SELECT
            s.subject_name,
            COALESCE(NULLIF(s.short_name, ''), s.subject_name) AS subject_display,
            c.class_name,
            c.semester,
            c.school,
            COALESCE(sec.section_name, '') AS section_name,
            MAX(sp.completion_percentage) AS syllabus_pct
        FROM teacher_subject_assignments tsa
        JOIN subjects s ON s.id = tsa.subject_id
        JOIN classes c ON c.id = tsa.class_id
        LEFT JOIN sections sec ON sec.id = tsa.section_id
        LEFT JOIN syllabus_progress sp ON sp.teacher_id = tsa.teacher_id AND sp.subject = s.subject_name
        WHERE tsa.teacher_id = ?";

    $course_types = 'i';
    $course_params = [$teacher_id];
    if ($detail_school_filter !== '') {
        $course_school_clauses = [];
        if ($class_has_school) {
            $course_school_clauses[] = 'c.school = ?';
            $course_types .= 's';
            $course_params[] = $detail_school_filter;
        }
        if ($class_has_department) {
            if ($class_has_school) {
                $course_school_clauses[] = "((c.school IS NULL OR c.school = '') AND c.department = ?)";
            } else {
                $course_school_clauses[] = 'c.department = ?';
            }
            $course_types .= 's';
            $course_params[] = $detail_school_filter;
        }
        if (!empty($course_school_clauses)) {
            $courses_sql .= ' AND (' . implode(' OR ', $course_school_clauses) . ')';
        }
    }
    if ($detail_semester_filter !== null) {
        $courses_sql .= " AND c.semester = ?";
        $course_types .= 's';
        $course_params[] = (string)$detail_semester_filter;
    }
    if ($detail_class_filter !== null) {
        $courses_sql .= " AND c.id = ?";
        $course_types .= 'i';
        $course_params[] = $detail_class_filter;
    }
    if ($detail_course_filter !== null) {
        $courses_sql .= " AND s.id = ?";
        $course_types .= 'i';
        $course_params[] = $detail_course_filter;
    }

    if ($activeTermId > 0) {
        $termId = (int)$activeTermId;
        $courses_sql .= " AND (c.academic_term_id = {$termId} OR c.academic_term_id IS NULL)";
    }
    $courses_sql .= $buildDateAnd('sp.updated_at');

    $courses_sql .= "
        GROUP BY s.id, s.subject_name, c.id, c.class_name, c.semester, c.school, sec.section_name
        ORDER BY s.subject_name, c.class_name, section_name";

    $stmt_c = mysqli_prepare($conn, $courses_sql);
    if ($stmt_c) {
        $courseBindParams = [];
        foreach ($course_params as $idx => $value) {
            $courseBindParams[$idx] = &$course_params[$idx];
        }
        array_unshift($courseBindParams, $stmt_c, $course_types);
        call_user_func_array('mysqli_stmt_bind_param', $courseBindParams);
        mysqli_stmt_execute($stmt_c);
        $courses_result = mysqli_stmt_get_result($stmt_c);
    } else {
        $courses_result = false;
    }
    $courses_data = [];
    if ($courses_result) {
        while ($row = mysqli_fetch_assoc($courses_result)) {
            $classLabel = format_class_label(
                normalize_elective_short_label($row['class_name'] ?? ''),
                $row['section_name'] ?? '',
                $row['semester'] ?? '',
                $row['school'] ?? ''
            );
            if ($classLabel === '') {
                $classLabel = 'UNASSIGNED';
            }

            $courses_data[] = [
                'subject_name' => normalize_elective_short_label($row['subject_display'] ?? $row['subject_name'] ?? ''),
                'class_name' => $row['class_name'],
                'section_name' => $row['section_name'],
                'semester' => $row['semester'],
                'school' => $row['school'] ?? '',
                'class_label' => $classLabel,
                'syllabus_pct' => isset($row['syllabus_pct']) ? round((float)$row['syllabus_pct'], 2) : 0.0,
            ];
        }
        mysqli_free_result($courses_result);
    }
    if ($stmt_c) {
        mysqli_stmt_close($stmt_c);
    }

    $filtered_taught_subquery = "
        SELECT DISTINCT tsa.teacher_id, tsa.class_id, COALESCE(tsa.section_id, 0) AS section_key
        FROM teacher_subject_assignments tsa
        INNER JOIN classes cls ON cls.id = tsa.class_id
        WHERE tsa.teacher_id = ?";
    $filtered_taught_types = 'i';
    $filtered_taught_params = [$teacher_id];
    if ($detail_school_filter !== '') {
        $taught_school_clauses = [];
        if ($class_has_school) {
            $taught_school_clauses[] = 'cls.school = ?';
            $filtered_taught_types .= 's';
            $filtered_taught_params[] = $detail_school_filter;
        }
        if ($class_has_department) {
            if ($class_has_school) {
                $taught_school_clauses[] = "((cls.school IS NULL OR cls.school = '') AND cls.department = ?)";
            } else {
                $taught_school_clauses[] = 'cls.department = ?';
            }
            $filtered_taught_types .= 's';
            $filtered_taught_params[] = $detail_school_filter;
        }
        if (!empty($taught_school_clauses)) {
            $filtered_taught_subquery .= ' AND (' . implode(' OR ', $taught_school_clauses) . ')';
        }
    }
    if ($detail_semester_filter !== null) {
        $filtered_taught_subquery .= " AND cls.semester = ?";
        $filtered_taught_types .= 's';
        $filtered_taught_params[] = (string)$detail_semester_filter;
    }
    if ($detail_class_filter !== null) {
        $filtered_taught_subquery .= " AND cls.id = ?";
        $filtered_taught_types .= 'i';
        $filtered_taught_params[] = $detail_class_filter;
    }
    if ($detail_course_filter !== null) {
        $filtered_taught_subquery .= " AND tsa.subject_id = ?";
        $filtered_taught_types .= 'i';
        $filtered_taught_params[] = $detail_course_filter;
    }

    if ($activeTermId > 0) {
        $termId = (int)$activeTermId;
        $filtered_taught_subquery .= " AND (cls.academic_term_id = {$termId} OR cls.academic_term_id IS NULL)";
    }

    // Alerts data
    $alerts_sql = "SELECT message, response, status, created_at FROM alerts WHERE teacher_id = ?";
    if ($termStartBound && $termEndBound) {
        $startEsc = mysqli_real_escape_string($conn, $termStartBound);
        $endEsc = mysqli_real_escape_string($conn, $termEndBound);
        $alerts_sql .= " AND created_at BETWEEN '{$startEsc}' AND '{$endEsc}'";
    }
    $alerts_sql .= " ORDER BY created_at DESC LIMIT 5";
    $stmt_a = mysqli_prepare($conn, $alerts_sql);
    mysqli_stmt_bind_param($stmt_a, "i", $teacher_id);
    mysqli_stmt_execute($stmt_a);
    $alerts_result = mysqli_stmt_get_result($stmt_a);
    $alerts_data = [];
    while ($row = mysqli_fetch_assoc($alerts_result)) {
        $alerts_data[] = $row;
    }
    mysqli_stmt_close($stmt_a);

    // Student performance snapshot
    $student_summary = [
        'total_students' => 0,
        'evaluated_students' => 0,
        'avg_marks_obtained' => null,
        'avg_marks_total' => null,
        'avg_percentage' => null,
        'high_achievers' => 0,
        'needs_support' => 0
    ];

    $summary_sql = "
        SELECT
            COUNT(*) AS total_students,
            SUM(CASE WHEN student_possible > 0 THEN 1 ELSE 0 END) AS evaluated_students,
            AVG(CASE WHEN student_possible > 0 THEN student_percentage END) AS avg_pct,
            AVG(CASE WHEN student_possible > 0 THEN student_total END) AS avg_obtained,
            AVG(CASE WHEN student_possible > 0 THEN student_possible END) AS avg_possible,
            SUM(CASE WHEN student_percentage >= 80 THEN 1 ELSE 0 END) AS high_achievers,
            SUM(CASE WHEN student_percentage < ? THEN 1 ELSE 0 END) AS needs_support
        FROM (
            SELECT
                st.id AS student_id,
                SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 AND ism.marks IS NOT NULL THEN ism.marks ELSE 0 END) AS student_total,
                SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 AND ism.marks IS NOT NULL THEN ic.marks_per_instance ELSE 0 END) AS student_possible,
                CASE
                    WHEN SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 AND ism.marks IS NOT NULL THEN ic.marks_per_instance ELSE 0 END) > 0
                        THEN (
                            SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 AND ism.marks IS NOT NULL THEN ism.marks ELSE 0 END)
                            / SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 AND ism.marks IS NOT NULL THEN ic.marks_per_instance ELSE 0 END)
                        ) * 100
                    ELSE NULL
                END AS student_percentage
            FROM (
                $filtered_taught_subquery
            ) taught
            JOIN students st ON st.class_id = taught.class_id
                AND (taught.section_key = 0 OR COALESCE(st.section_id, 0) = taught.section_key)
            LEFT JOIN ica_student_marks ism ON ism.teacher_id = taught.teacher_id
                AND ism.student_id = st.id
                AND ism.marks IS NOT NULL{$detailMarksDateClause}
            LEFT JOIN ica_components ic ON ic.id = ism.component_id
            GROUP BY st.id
        ) student_stats";
    $summary_bind_types = 'd' . $filtered_taught_types;
    $summary_bind_params = $filtered_taught_params;
    array_unshift($summary_bind_params, (float)$performance_threshold);

    $stmt_summary = mysqli_prepare($conn, $summary_sql);
    if ($stmt_summary) {
        $summaryBindArgs = [];
        foreach ($summary_bind_params as $idx => $value) {
            $summaryBindArgs[$idx] = &$summary_bind_params[$idx];
        }
        array_unshift($summaryBindArgs, $stmt_summary, $summary_bind_types);
        call_user_func_array('mysqli_stmt_bind_param', $summaryBindArgs);
        mysqli_stmt_execute($stmt_summary);
        $summary_result = mysqli_stmt_get_result($stmt_summary);
        if ($summary_result && ($summary_row = mysqli_fetch_assoc($summary_result))) {
            $student_summary = [
                'total_students' => (int)($summary_row['total_students'] ?? 0),
                'evaluated_students' => (int)($summary_row['evaluated_students'] ?? 0),
                'avg_marks_obtained' => isset($summary_row['avg_obtained']) ? round((float)$summary_row['avg_obtained'], 2) : null,
                'avg_marks_total' => isset($summary_row['avg_possible']) ? round((float)$summary_row['avg_possible'], 2) : null,
                'avg_percentage' => isset($summary_row['avg_pct']) ? round((float)$summary_row['avg_pct'], 2) : null,
                'high_achievers' => (int)($summary_row['high_achievers'] ?? 0),
                'needs_support' => (int)($summary_row['needs_support'] ?? 0)
            ];
        }
        mysqli_stmt_close($stmt_summary);
    }

    // Top performing students
    $top_students_sql = "
        SELECT
            st.sap_id,
            st.name AS student_name,
            SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 AND ism.marks IS NOT NULL THEN ism.marks ELSE 0 END) AS marks_obtained,
            SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 AND ism.marks IS NOT NULL THEN ic.marks_per_instance ELSE 0 END) AS marks_total,
            CASE
                WHEN SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 AND ism.marks IS NOT NULL THEN ic.marks_per_instance ELSE 0 END) > 0
                    THEN (
                        SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 AND ism.marks IS NOT NULL THEN ism.marks ELSE 0 END)
                        / SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 AND ism.marks IS NOT NULL THEN ic.marks_per_instance ELSE 0 END)
                    ) * 100
                ELSE NULL
            END AS performance_pct
        FROM (
            $filtered_taught_subquery
        ) taught
        JOIN students st ON st.class_id = taught.class_id
            AND (taught.section_key = 0 OR COALESCE(st.section_id, 0) = taught.section_key)
        LEFT JOIN ica_student_marks ism ON ism.teacher_id = taught.teacher_id
            AND ism.student_id = st.id
            AND ism.marks IS NOT NULL{$detailMarksDateClause}
        LEFT JOIN ica_components ic ON ic.id = ism.component_id
        GROUP BY st.id, st.sap_id, st.name
        HAVING marks_total > 0
        ORDER BY performance_pct DESC
        LIMIT 5";
    $stmt_top = mysqli_prepare($conn, $top_students_sql);
    $top_students = [];
    if ($stmt_top) {
        $top_bind_types = $filtered_taught_types;
        $top_bind_params = $filtered_taught_params;
        $topBindArgs = [];
        foreach ($top_bind_params as $idx => $value) {
            $topBindArgs[$idx] = &$top_bind_params[$idx];
        }
        array_unshift($topBindArgs, $stmt_top, $top_bind_types);
        call_user_func_array('mysqli_stmt_bind_param', $topBindArgs);
        mysqli_stmt_execute($stmt_top);
        $top_result = mysqli_stmt_get_result($stmt_top);
        if ($top_result) {
            while ($row = mysqli_fetch_assoc($top_result)) {
                $top_students[] = [
                    'sap_id' => $row['sap_id'],
                    'student_name' => $row['student_name'],
                    'marks_obtained' => round((float)($row['marks_obtained'] ?? 0), 2),
                    'marks_total' => round((float)($row['marks_total'] ?? 0), 2),
                    'performance_pct' => isset($row['performance_pct']) ? round((float)$row['performance_pct'], 2) : null
                ];
            }
            mysqli_free_result($top_result);
        }
        mysqli_stmt_close($stmt_top);
    }

    // Students below performance threshold
    $weak_students_sql = "
        SELECT
            st.sap_id,
            st.name AS student_name,
            SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 AND ism.marks IS NOT NULL THEN ism.marks ELSE 0 END) AS marks_obtained,
            SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 AND ism.marks IS NOT NULL THEN ic.marks_per_instance ELSE 0 END) AS marks_total,
            CASE
                WHEN SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 AND ism.marks IS NOT NULL THEN ic.marks_per_instance ELSE 0 END) > 0
                    THEN (
                        SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 AND ism.marks IS NOT NULL THEN ism.marks ELSE 0 END)
                        / SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 AND ism.marks IS NOT NULL THEN ic.marks_per_instance ELSE 0 END)
                    ) * 100
                ELSE NULL
            END AS performance_pct
        FROM (
            $filtered_taught_subquery
        ) taught
        JOIN students st ON st.class_id = taught.class_id
            AND (taught.section_key = 0 OR COALESCE(st.section_id, 0) = taught.section_key)
        LEFT JOIN ica_student_marks ism ON ism.teacher_id = taught.teacher_id
            AND ism.student_id = st.id
            AND ism.marks IS NOT NULL{$detailMarksDateClause}
        LEFT JOIN ica_components ic ON ic.id = ism.component_id
        GROUP BY st.id, st.sap_id, st.name
        HAVING marks_total > 0 AND performance_pct < ?
        ORDER BY performance_pct ASC
        LIMIT 10";
    $stmt_weak = mysqli_prepare($conn, $weak_students_sql);
    $weak_students = [];
    if ($stmt_weak) {
        $weak_bind_types = $filtered_taught_types . 'd';
        $weak_bind_params = $filtered_taught_params;
        $weak_bind_params[] = (float)$performance_threshold;
        $weakBindArgs = [];
        foreach ($weak_bind_params as $idx => $value) {
            $weakBindArgs[$idx] = &$weak_bind_params[$idx];
        }
        array_unshift($weakBindArgs, $stmt_weak, $weak_bind_types);
        call_user_func_array('mysqli_stmt_bind_param', $weakBindArgs);
        mysqli_stmt_execute($stmt_weak);
        $weak_result = mysqli_stmt_get_result($stmt_weak);
        if ($weak_result) {
            while ($row = mysqli_fetch_assoc($weak_result)) {
                $weak_students[] = [
                    'sap_id' => $row['sap_id'] ?? '',
                    'student_name' => $row['student_name'] ?? '',
                    'marks_obtained' => round((float)($row['marks_obtained'] ?? 0), 2),
                    'marks_total' => round((float)($row['marks_total'] ?? 0), 2),
                    'performance_pct' => isset($row['performance_pct']) ? round((float)$row['performance_pct'], 2) : null
                ];
            }
            mysqli_free_result($weak_result);
        }
        mysqli_stmt_close($stmt_weak);
    }

    // Course-wise evaluation coverage and average performance
    $course_coverage = [];
    $course_performance_map = [];
    $low_performing_courses = [];

    $coverage_sql = "
        SELECT
            s.subject_name,
            COALESCE(NULLIF(s.short_name, ''), s.subject_name) AS subject_display,
            c.class_name,
            c.semester,
            c.school,
            COALESCE(sec.section_name, '') AS section_name,
            COUNT(DISTINCT st.id) AS total_students,
            COUNT(DISTINCT CASE WHEN score.student_possible > 0 THEN st.id END) AS evaluated_students,
            AVG(CASE WHEN score.student_possible > 0 THEN score.student_percentage END) AS avg_performance_pct
        FROM teacher_subject_assignments tsa
        JOIN subjects s ON s.id = tsa.subject_id
        JOIN classes c ON c.id = tsa.class_id
        LEFT JOIN sections sec ON sec.id = tsa.section_id
        LEFT JOIN students st ON st.class_id = tsa.class_id
            AND (COALESCE(tsa.section_id, 0) = 0 OR COALESCE(st.section_id, 0) = COALESCE(tsa.section_id, 0))
        LEFT JOIN (
            SELECT
                ism.teacher_id,
                ism.student_id,
                SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 AND ism.marks IS NOT NULL THEN ism.marks ELSE 0 END) AS student_total,
                SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 AND ism.marks IS NOT NULL THEN ic.marks_per_instance ELSE 0 END) AS student_possible,
                CASE
                    WHEN SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 AND ism.marks IS NOT NULL THEN ic.marks_per_instance ELSE 0 END) > 0
                        THEN (
                            SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 AND ism.marks IS NOT NULL THEN ism.marks ELSE 0 END)
                            / SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 AND ism.marks IS NOT NULL THEN ic.marks_per_instance ELSE 0 END)
                        ) * 100
                    ELSE NULL
                END AS student_percentage
            FROM ica_student_marks ism
            LEFT JOIN ica_components ic ON ic.id = ism.component_id
            WHERE ism.teacher_id = ? AND ism.marks IS NOT NULL{$detailMarksDateClause}
            GROUP BY ism.teacher_id, ism.student_id
        ) score ON score.teacher_id = tsa.teacher_id AND score.student_id = st.id
        WHERE tsa.teacher_id = ?";

    $coverage_types = 'ii';
    $coverage_params = [$teacher_id, $teacher_id];
    if ($detail_school_filter !== '') {
        $coverage_school_clauses = [];
        if ($class_has_school) {
            $coverage_school_clauses[] = 'c.school = ?';
            $coverage_types .= 's';
            $coverage_params[] = $detail_school_filter;
        }
        if ($class_has_department) {
            if ($class_has_school) {
                $coverage_school_clauses[] = "((c.school IS NULL OR c.school = '') AND c.department = ?)";
            } else {
                $coverage_school_clauses[] = 'c.department = ?';
            }
            $coverage_types .= 's';
            $coverage_params[] = $detail_school_filter;
        }
        if (!empty($coverage_school_clauses)) {
            $coverage_sql .= ' AND (' . implode(' OR ', $coverage_school_clauses) . ')';
        }
    }
    if ($detail_semester_filter !== null) {
        $coverage_sql .= " AND c.semester = ?";
        $coverage_types .= 's';
        $coverage_params[] = (string)$detail_semester_filter;
    }
    if ($detail_class_filter !== null) {
        $coverage_sql .= " AND c.id = ?";
        $coverage_types .= 'i';
        $coverage_params[] = $detail_class_filter;
    }
    if ($detail_course_filter !== null) {
        $coverage_sql .= " AND s.id = ?";
        $coverage_types .= 'i';
        $coverage_params[] = $detail_course_filter;
    }
    if ($activeTermId > 0) {
        $termId = (int)$activeTermId;
        $coverage_sql .= " AND (c.academic_term_id = {$termId} OR c.academic_term_id IS NULL)";
    }

    $coverage_sql .= "
        GROUP BY s.id, c.id, sec.section_name
        ORDER BY subject_display, c.class_name, sec.section_name";

    $stmt_coverage = mysqli_prepare($conn, $coverage_sql);
    if ($stmt_coverage) {
        $coverageBindArgs = [];
        foreach ($coverage_params as $idx => $value) {
            $coverageBindArgs[$idx] = &$coverage_params[$idx];
        }
        array_unshift($coverageBindArgs, $stmt_coverage, $coverage_types);
        call_user_func_array('mysqli_stmt_bind_param', $coverageBindArgs);
        mysqli_stmt_execute($stmt_coverage);
        $coverage_result = mysqli_stmt_get_result($stmt_coverage);
        if ($coverage_result) {
            while ($coverage_row = mysqli_fetch_assoc($coverage_result)) {
                $classLabel = format_class_label(
                    normalize_elective_short_label($coverage_row['class_name'] ?? ''),
                    $coverage_row['section_name'] ?? '',
                    $coverage_row['semester'] ?? '',
                    $coverage_row['school'] ?? ''
                );
                if ($classLabel === '') {
                    $classLabel = trim((string)($coverage_row['class_name'] ?? ''));
                }
                if ($classLabel === '') {
                    $classLabel = 'UNASSIGNED';
                }

                $subjectDisplay = normalize_elective_short_label($coverage_row['subject_display'] ?? $coverage_row['subject_name'] ?? '');
                $totalStudents = (int)($coverage_row['total_students'] ?? 0);
                $evaluatedStudents = (int)($coverage_row['evaluated_students'] ?? 0);
                $coveragePct = $totalStudents > 0 ? round(($evaluatedStudents / $totalStudents) * 100, 1) : 0.0;
                $avgPerf = isset($coverage_row['avg_performance_pct']) ? (float)$coverage_row['avg_performance_pct'] : null;

                $course_coverage[] = [
                    'subject_name' => $subjectDisplay,
                    'class_label' => $classLabel,
                    'total_students' => $totalStudents,
                    'evaluated_students' => $evaluatedStudents,
                    'coverage_pct' => $coveragePct,
                    'avg_performance_pct' => $avgPerf !== null ? round($avgPerf, 2) : null
                ];

                if (!isset($course_performance_map[$subjectDisplay])) {
                    $course_performance_map[$subjectDisplay] = [];
                }
                if ($avgPerf !== null) {
                    $course_performance_map[$subjectDisplay][] = $avgPerf;
                }

                if ($avgPerf !== null && $avgPerf + 0.0001 < (float)$performance_threshold) {
                    $low_performing_courses[] = [
                        'subject_name' => $subjectDisplay,
                        'class_label' => $classLabel,
                        'avg_performance_pct' => round($avgPerf, 2),
                        'coverage_pct' => $coveragePct
                    ];
                }
            }
            mysqli_free_result($coverage_result);
        }
        mysqli_stmt_close($stmt_coverage);
    }

    $course_performance = [];
    foreach ($course_performance_map as $subjectName => $coursePerfList) {
        if (empty($coursePerfList)) {
            continue;
        }
        $course_performance[] = [
            'subject_name' => $subjectName,
            'avg_performance_pct' => round(array_sum($coursePerfList) / count($coursePerfList), 2)
        ];
    }
    usort($course_performance, static function (array $a, array $b): int {
        return ($b['avg_performance_pct'] <=> $a['avg_performance_pct']);
    });
    usort($low_performing_courses, static function (array $a, array $b): int {
        return ($a['avg_performance_pct'] <=> $b['avg_performance_pct']);
    });

    // Syllabus planned vs actual and progress timeline
    $progress_sql = "
        SELECT
            sp.subject,
            sp.timeline,
            sp.completion_percentage,
            sp.planned_hours,
            sp.actual_hours,
            sp.updated_at,
            c.class_name,
            c.semester,
            c.school,
            COALESCE(sec.section_name, '') AS section_name,
            sp.class_label
        FROM syllabus_progress sp
        LEFT JOIN classes c ON c.id = sp.class_id
        LEFT JOIN sections sec ON sec.id = sp.section_id
        WHERE sp.teacher_id = ?";
    $progress_types = 'i';
    $progress_params = [$teacher_id];

    if ($termStartBound && $termEndBound) {
        $startEsc = mysqli_real_escape_string($conn, $termStartBound);
        $endEsc = mysqli_real_escape_string($conn, $termEndBound);
        $progress_sql .= " AND sp.updated_at BETWEEN '{$startEsc}' AND '{$endEsc}'";
    }
    if ($detail_school_filter !== '') {
        $progress_school_clauses = [];
        if ($class_has_school) {
            $progress_school_clauses[] = 'c.school = ?';
            $progress_types .= 's';
            $progress_params[] = $detail_school_filter;
        }
        if ($class_has_department) {
            if ($class_has_school) {
                $progress_school_clauses[] = "((c.school IS NULL OR c.school = '') AND c.department = ?)";
            } else {
                $progress_school_clauses[] = 'c.department = ?';
            }
            $progress_types .= 's';
            $progress_params[] = $detail_school_filter;
        }
        if (!empty($progress_school_clauses)) {
            $progress_sql .= ' AND (' . implode(' OR ', $progress_school_clauses) . ')';
        }
    }
    if ($detail_semester_filter !== null) {
        $progress_sql .= " AND c.semester = ?";
        $progress_types .= 's';
        $progress_params[] = (string)$detail_semester_filter;
    }
    if ($detail_class_filter !== null) {
        $progress_sql .= " AND sp.class_id = ?";
        $progress_types .= 'i';
        $progress_params[] = $detail_class_filter;
    }
    if ($detail_course_filter !== null) {
        $progress_sql .= " AND EXISTS (SELECT 1 FROM subjects s_prog WHERE s_prog.id = ? AND s_prog.subject_name = sp.subject)";
        $progress_types .= 'i';
        $progress_params[] = $detail_course_filter;
    }
    if ($activeTermId > 0) {
        $termId = (int)$activeTermId;
        $progress_sql .= " AND (c.id IS NULL OR c.academic_term_id = {$termId} OR c.academic_term_id IS NULL)";
    }
    $progress_sql .= " ORDER BY sp.updated_at DESC LIMIT 40";

    $syllabus_plan_actual_map = [];
    $recent_progress_updates = [];
    $stmt_progress = mysqli_prepare($conn, $progress_sql);
    if ($stmt_progress) {
        $progressBindArgs = [];
        foreach ($progress_params as $idx => $value) {
            $progressBindArgs[$idx] = &$progress_params[$idx];
        }
        array_unshift($progressBindArgs, $stmt_progress, $progress_types);
        call_user_func_array('mysqli_stmt_bind_param', $progressBindArgs);
        mysqli_stmt_execute($stmt_progress);
        $progress_result = mysqli_stmt_get_result($stmt_progress);
        if ($progress_result) {
            while ($progress_row = mysqli_fetch_assoc($progress_result)) {
                $subjectName = normalize_elective_short_label($progress_row['subject'] ?? '');
                $classLabel = format_class_label(
                    normalize_elective_short_label($progress_row['class_name'] ?? ''),
                    $progress_row['section_name'] ?? '',
                    $progress_row['semester'] ?? '',
                    $progress_row['school'] ?? ''
                );
                if ($classLabel === '') {
                    $classLabel = trim((string)($progress_row['class_label'] ?? ''));
                }
                if ($classLabel === '') {
                    $classLabel = 'UNASSIGNED';
                }

                $plannedHours = isset($progress_row['planned_hours']) ? (float)$progress_row['planned_hours'] : 0.0;
                $actualHours = isset($progress_row['actual_hours']) ? (float)$progress_row['actual_hours'] : 0.0;
                $completionPct = isset($progress_row['completion_percentage']) ? (float)$progress_row['completion_percentage'] : 0.0;
                $updatedAt = isset($progress_row['updated_at']) ? trim((string)$progress_row['updated_at']) : '';

                $recent_progress_updates[] = [
                    'subject_name' => $subjectName,
                    'class_label' => $classLabel,
                    'timeline' => $progress_row['timeline'] ?? '',
                    'completion_pct' => round($completionPct, 2),
                    'planned_hours' => round($plannedHours, 2),
                    'actual_hours' => round($actualHours, 2),
                    'updated_at' => $updatedAt !== '' ? date('d M Y, h:i A', strtotime($updatedAt)) : '—'
                ];

                $planKey = $subjectName . '|' . $classLabel;
                if (!isset($syllabus_plan_actual_map[$planKey])) {
                    $syllabus_plan_actual_map[$planKey] = [
                        'subject_name' => $subjectName,
                        'class_label' => $classLabel,
                        'planned_hours' => round($plannedHours, 2),
                        'actual_hours' => round($actualHours, 2),
                        'completion_pct' => round($completionPct, 2),
                        'variance_hours' => round($actualHours - $plannedHours, 2),
                        'updated_at' => $updatedAt !== '' ? date('d M Y, h:i A', strtotime($updatedAt)) : '—'
                    ];
                }
            }
            mysqli_free_result($progress_result);
        }
        mysqli_stmt_close($stmt_progress);
    }
    $recent_progress_updates = array_slice($recent_progress_updates, 0, 10);
    $syllabus_plan_actual = array_values($syllabus_plan_actual_map);

    // Alert intervention detail
    $unresolved_alerts = [];
    $unresolved_sql = "SELECT message, status, created_at
        FROM alerts
        WHERE teacher_id = ?
          AND LOWER(status) IN ('pending', 'open', 'active', 'escalated')";
    if ($termStartBound && $termEndBound) {
        $startEsc = mysqli_real_escape_string($conn, $termStartBound);
        $endEsc = mysqli_real_escape_string($conn, $termEndBound);
        $unresolved_sql .= " AND created_at BETWEEN '{$startEsc}' AND '{$endEsc}'";
    }
    $unresolved_sql .= " ORDER BY created_at DESC LIMIT 8";
    $stmt_unresolved = mysqli_prepare($conn, $unresolved_sql);
    if ($stmt_unresolved) {
        mysqli_stmt_bind_param($stmt_unresolved, 'i', $teacher_id);
        mysqli_stmt_execute($stmt_unresolved);
        $unresolved_result = mysqli_stmt_get_result($stmt_unresolved);
        if ($unresolved_result) {
            while ($row = mysqli_fetch_assoc($unresolved_result)) {
                $unresolved_alerts[] = [
                    'message' => $row['message'] ?? '',
                    'status' => $row['status'] ?? '',
                    'created_at' => !empty($row['created_at']) ? date('d M Y, h:i A', strtotime((string)$row['created_at'])) : '—'
                ];
            }
            mysqli_free_result($unresolved_result);
        }
        mysqli_stmt_close($stmt_unresolved);
    }

    $response_history = [];
    $response_sql = "SELECT message, response, status, created_at, responded_at
        FROM alerts
        WHERE teacher_id = ?
          AND (
            (response IS NOT NULL AND TRIM(response) <> '')
            OR LOWER(status) IN ('responded', 'resolved', 'closed', 'acknowledged', 'completed')
          )";
    if ($termStartBound && $termEndBound) {
        $startEsc = mysqli_real_escape_string($conn, $termStartBound);
        $endEsc = mysqli_real_escape_string($conn, $termEndBound);
        $response_sql .= " AND created_at BETWEEN '{$startEsc}' AND '{$endEsc}'";
    }
    $response_sql .= " ORDER BY COALESCE(responded_at, created_at) DESC LIMIT 10";
    $stmt_response = mysqli_prepare($conn, $response_sql);
    if ($stmt_response) {
        mysqli_stmt_bind_param($stmt_response, 'i', $teacher_id);
        mysqli_stmt_execute($stmt_response);
        $response_result = mysqli_stmt_get_result($stmt_response);
        if ($response_result) {
            while ($row = mysqli_fetch_assoc($response_result)) {
                $response_history[] = [
                    'message' => $row['message'] ?? '',
                    'response' => $row['response'] ?? '',
                    'status' => $row['status'] ?? '',
                    'created_at' => !empty($row['created_at']) ? date('d M Y, h:i A', strtotime((string)$row['created_at'])) : '—',
                    'responded_at' => !empty($row['responded_at']) ? date('d M Y, h:i A', strtotime((string)$row['responded_at'])) : '—'
                ];
            }
            mysqli_free_result($response_result);
        }
        mysqli_stmt_close($stmt_response);
    }

    echo json_encode([
        'courses' => $courses_data,
        'alerts' => $alerts_data,
        'student_summary' => $student_summary,
        'top_students' => $top_students,
        'weak_students' => $weak_students,
        'course_coverage' => $course_coverage,
        'course_performance' => $course_performance,
        'low_performing_courses' => $low_performing_courses,
        'syllabus_plan_actual' => $syllabus_plan_actual,
        'recent_progress_updates' => $recent_progress_updates,
        'unresolved_alerts' => $unresolved_alerts,
        'response_history' => $response_history,
        'thresholds' => [
            'performance' => $performance_threshold
        ]
    ]);
    exit;
}

// --- AJAX ENDPOINT FOR TIMETABLES ---
if (isset($_GET['action']) && $_GET['action'] == 'get_teacher_timetables') {
    $teacher_id = (int)$_GET['id'];
    $timetables_sql = "SELECT id, file_name, file_path, uploaded_at FROM timetables WHERE teacher_id = ? ORDER BY uploaded_at DESC";
    $stmt_t = mysqli_prepare($conn, $timetables_sql);
    mysqli_stmt_bind_param($stmt_t, "i", $teacher_id);
    mysqli_stmt_execute($stmt_t);
    $timetables_result = mysqli_stmt_get_result($stmt_t);
    $timetables_data = [];
    while ($row = mysqli_fetch_assoc($timetables_result)) {
        $timetables_data[] = [
            'id' => $row['id'],
            'file_name' => $row['file_name'],
            'file_path' => $row['file_path'],
            'uploaded_at' => date("d-M-Y H:i", strtotime($row['uploaded_at']))
        ];
    }
    
    echo json_encode(['timetables' => $timetables_data]);
    exit;
}

// --- PAGE DISPLAY LOGIC ---
$semester_filter = '';
$semester_param_provided = array_key_exists('semester_filter', $_GET);
if ($semester_param_provided) {
    $semester_value = trim((string)$_GET['semester_filter']);
    if ($semester_value !== '') {
        $semester_filter = (int)$semester_value;
    }
}
$class_filter = (isset($_GET['class_filter']) && $_GET['class_filter'] !== '') ? (int)$_GET['class_filter'] : '';
$course_filter = (isset($_GET['course_filter']) && $_GET['course_filter'] !== '') ? (int)$_GET['course_filter'] : '';
$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
$workload_filter = isset($_GET['workload_filter']) ? trim($_GET['workload_filter']) : '';
$completion_filter = isset($_GET['completion_filter']) ? trim($_GET['completion_filter']) : '';
$alert_filter = isset($_GET['alert_filter']) ? trim($_GET['alert_filter']) : '';

$semesters = [];
$classes_list = [];
$courses_list = [];

$semester_sql = "SELECT DISTINCT semester FROM classes";
$semester_has_where = false;
if ($school_filter !== '') {
    $semester_school_clauses = [];
    if ($class_has_school) {
        $semester_school_clauses[] = 'school = ?';
    }
    if ($class_has_department) {
        if ($class_has_school) {
            $semester_school_clauses[] = "((school IS NULL OR school = '') AND department = ?)";
        } else {
            $semester_school_clauses[] = 'department = ?';
        }
    }
    if (!empty($semester_school_clauses)) {
        $semester_sql .= ' WHERE (' . implode(' OR ', $semester_school_clauses) . ')';
        $semester_has_where = true;
    }
}
if ($activeTermId > 0) {
    $termCond = "(academic_term_id = " . (int)$activeTermId . " OR academic_term_id IS NULL)";
    $semester_sql .= $semester_has_where
        ? " AND {$termCond}"
        : " WHERE {$termCond}";
    $semester_has_where = true;
}
$semester_sql .= " ORDER BY CAST(semester AS UNSIGNED) ASC";
$semester_stmt = mysqli_prepare($conn, $semester_sql);
if ($semester_stmt) {
    if ($semester_has_where) {
        if ($class_has_school && $class_has_department) {
            mysqli_stmt_bind_param($semester_stmt, "ss", $school_filter, $school_filter);
        } else {
            mysqli_stmt_bind_param($semester_stmt, "s", $school_filter);
        }
    }
    mysqli_stmt_execute($semester_stmt);
    $semester_result = mysqli_stmt_get_result($semester_stmt);
    if ($semester_result) {
        while ($row = mysqli_fetch_assoc($semester_result)) {
            $semesters[] = (int)$row['semester'];
        }
        mysqli_free_result($semester_result);
    }
    mysqli_stmt_close($semester_stmt);
}

$class_sql = "SELECT id, class_name, semester FROM classes";
$class_conditions = [];
$class_types = '';
$class_params = [];

if ($school_filter !== '') {
    $class_school_clauses = [];
    if ($class_has_school) {
        $class_school_clauses[] = 'school = ?';
        $class_types .= 's';
        $class_params[] = $school_filter;
    }
    if ($class_has_department) {
        if ($class_has_school) {
            $class_school_clauses[] = "((school IS NULL OR school = '') AND department = ?)";
        } else {
            $class_school_clauses[] = 'department = ?';
        }
        $class_types .= 's';
        $class_params[] = $school_filter;
    }
    if (!empty($class_school_clauses)) {
        $class_conditions[] = '(' . implode(' OR ', $class_school_clauses) . ')';
    }
}
if ($semester_filter !== '') {
    $class_conditions[] = "semester = ?";
    $class_types .= 's';
    $class_params[] = (string)$semester_filter;
}
if ($activeTermId > 0) {
    $termId = (int)$activeTermId;
    $class_conditions[] = "(academic_term_id = {$termId} OR academic_term_id IS NULL)";
}
if (!empty($class_conditions)) {
    $class_sql .= ' WHERE ' . implode(' AND ', $class_conditions);
}
$class_sql .= " ORDER BY CAST(semester AS UNSIGNED), class_name";
$class_stmt = mysqli_prepare($conn, $class_sql);
if ($class_stmt) {
    if ($class_types === 's') {
        mysqli_stmt_bind_param($class_stmt, 's', $class_params[0]);
    } elseif ($class_types === 'ss') {
        mysqli_stmt_bind_param($class_stmt, 'ss', $class_params[0], $class_params[1]);
    }
    mysqli_stmt_execute($class_stmt);
    $class_result = mysqli_stmt_get_result($class_stmt);
    if ($class_result) {
        while ($row = mysqli_fetch_assoc($class_result)) {
            $classes_list[] = [
                'id' => (int)$row['id'],
                'name' => normalize_elective_short_label($row['class_name']),
                'semester' => (int)$row['semester']
            ];
        }
        mysqli_free_result($class_result);
    }
    mysqli_stmt_close($class_stmt);
}

if ($class_filter !== '' && !empty($classes_list)) {
    $availableClassIds = array_column($classes_list, 'id');
    if (!in_array((int)$class_filter, $availableClassIds, true)) {
        $class_filter = '';
    }
}

$course_sql = "
    SELECT DISTINCT
        s.id,
        COALESCE(NULLIF(s.short_name, ''), s.subject_name) AS subject_display,
        s.subject_name
    FROM teacher_subject_assignments tsa
    INNER JOIN subjects s ON s.id = tsa.subject_id
    INNER JOIN classes c_course ON c_course.id = tsa.class_id
    WHERE 1=1";
$course_types = '';
$course_params = [];
if ($school_filter !== '') {
    $course_school_clauses = [];
    if ($class_has_school) {
        $course_school_clauses[] = 'c_course.school = ?';
        $course_types .= 's';
        $course_params[] = $school_filter;
    }
    if ($class_has_department) {
        if ($class_has_school) {
            $course_school_clauses[] = "((c_course.school IS NULL OR c_course.school = '') AND c_course.department = ?)";
        } else {
            $course_school_clauses[] = 'c_course.department = ?';
        }
        $course_types .= 's';
        $course_params[] = $school_filter;
    }
    if (!empty($course_school_clauses)) {
        $course_sql .= ' AND (' . implode(' OR ', $course_school_clauses) . ')';
    }
}
if ($semester_filter !== '') {
    $course_sql .= ' AND c_course.semester = ?';
    $course_types .= 's';
    $course_params[] = (string)$semester_filter;
}
if ($class_filter !== '') {
    $course_sql .= ' AND c_course.id = ?';
    $course_types .= 'i';
    $course_params[] = (int)$class_filter;
}
if ($activeTermId > 0) {
    $course_sql .= ' AND (c_course.academic_term_id = ' . (int)$activeTermId . ' OR c_course.academic_term_id IS NULL)';
}
$course_sql .= ' ORDER BY subject_display';

$course_stmt = mysqli_prepare($conn, $course_sql);
if ($course_stmt) {
    if ($course_types !== '') {
        $courseBindValues = [$course_stmt, $course_types];
        foreach ($course_params as $courseParamIndex => $courseParamValue) {
            $courseBindValues[] = &$course_params[$courseParamIndex];
        }
        call_user_func_array('mysqli_stmt_bind_param', $courseBindValues);
    }
    mysqli_stmt_execute($course_stmt);
    $course_result = mysqli_stmt_get_result($course_stmt);
    if ($course_result) {
        while ($course_row = mysqli_fetch_assoc($course_result)) {
            $courses_list[] = [
                'id' => (int)$course_row['id'],
                'name' => normalize_elective_short_label($course_row['subject_display'] ?? $course_row['subject_name'] ?? '')
            ];
        }
        mysqli_free_result($course_result);
    }
    mysqli_stmt_close($course_stmt);
}

if ($course_filter !== '' && !empty($courses_list)) {
    $availableCourseIds = array_column($courses_list, 'id');
    if (!in_array((int)$course_filter, $availableCourseIds, true)) {
        $course_filter = '';
    }
}

$tsJoin = $activeTermId > 0 ? "\n        INNER JOIN classes term_c_ts ON term_c_ts.id = tsa.class_id" : '';
$tsWhere = $activeTermId > 0
    ? "\n        WHERE (term_c_ts.academic_term_id = " . (int)$activeTermId . " OR term_c_ts.academic_term_id IS NULL)"
    : '';
$spWhere = $buildDateWhere('sp.updated_at');
$alertsWhere = $buildDateWhere('a.created_at');
$timetableWhere = $buildDateWhere('t.uploaded_at');
$ismDateClause = $buildDateAnd('ism.updated_at');
$taughtJoin = $activeTermId > 0 ? "\n            INNER JOIN classes term_cls ON term_cls.id = tsa.class_id" : '';
$taughtWhere = $activeTermId > 0
    ? "\n            WHERE (term_cls.academic_term_id = " . (int)$activeTermId . " OR term_cls.academic_term_id IS NULL)"
    : '';

$teacher_sql = "
SELECT 
    u.id,
    u.name,
    COALESCE(sc.school_name, u.school, u.department) AS school_name,
    IFNULL(ts.assigned_courses, 0) AS assigned_courses,
    IFNULL(sp.avg_completion, 0) AS avg_completion,
    IFNULL(al.pending_alerts, 0) AS pending_alerts,
    IFNULL(al.resolved_alerts, 0) AS resolved_alerts,
    IFNULL(st.student_count, 0) AS student_count,
    st.avg_mark AS avg_student_mark,
    IFNULL(tt.timetable_count, 0) AS timetable_count
FROM users u
LEFT JOIN schools sc ON sc.school_name = COALESCE(u.school, u.department)
LEFT JOIN (
    SELECT tsa.teacher_id, COUNT(DISTINCT tsa.subject_id) AS assigned_courses
    FROM teacher_subject_assignments tsa{$tsJoin}{$tsWhere}
    GROUP BY tsa.teacher_id
) ts ON u.id = ts.teacher_id
LEFT JOIN (
    SELECT sp.teacher_id, AVG(sp.completion_percentage) AS avg_completion
    FROM syllabus_progress sp{$spWhere}
    GROUP BY sp.teacher_id
) sp ON u.id = sp.teacher_id
LEFT JOIN (
    SELECT a.teacher_id,
           SUM(CASE WHEN a.status IN ('Pending','Open','Escalated','Active') THEN 1 ELSE 0 END) AS pending_alerts,
           SUM(CASE WHEN a.status IN ('Resolved','Closed','Acknowledged','Completed') THEN 1 ELSE 0 END) AS resolved_alerts
    FROM alerts a{$alertsWhere}
    GROUP BY a.teacher_id
) al ON u.id = al.teacher_id
LEFT JOIN (
    SELECT t.teacher_id, COUNT(*) AS timetable_count
    FROM timetables t{$timetableWhere}
    GROUP BY t.teacher_id
) tt ON u.id = tt.teacher_id
LEFT JOIN (
    SELECT
        data.teacher_id,
        COUNT(*) AS student_count,
        AVG(data.avg_mark) AS avg_mark
    FROM (
        SELECT
            taught.teacher_id,
            st.id AS student_id,
            AVG(ism.marks) AS avg_mark
        FROM (
            SELECT DISTINCT tsa.teacher_id, tsa.class_id, COALESCE(tsa.section_id, 0) AS section_key
            FROM teacher_subject_assignments tsa{$taughtJoin}{$taughtWhere}
        ) taught
        JOIN students st ON st.class_id = taught.class_id
            AND (taught.section_key = 0 OR COALESCE(st.section_id, 0) = taught.section_key)
        LEFT JOIN ica_student_marks ism ON ism.teacher_id = taught.teacher_id
            AND ism.student_id = st.id
            AND ism.marks IS NOT NULL{$ismDateClause}
        GROUP BY taught.teacher_id, st.id
    ) data
    GROUP BY data.teacher_id
) st ON u.id = st.teacher_id
WHERE u.role = 'teacher'";

$teacher_types = '';
$teacher_params = [];
if ($school_filter !== '') {
    $teacher_sql .= " AND COALESCE(u.school, u.department) = ?";
    $teacher_types .= 's';
    $teacher_params[] = $school_filter;
}

if ($semester_filter !== '') {
    $teacher_sql .= " AND EXISTS (
        SELECT 1
        FROM teacher_subject_assignments t_sem
        INNER JOIN classes c_sem ON c_sem.id = t_sem.class_id
        WHERE t_sem.teacher_id = u.id
          AND c_sem.semester = " . (int)$semester_filter . "
    )";
}

if ($class_filter !== '') {
    $teacher_sql .= " AND EXISTS (
        SELECT 1
        FROM teacher_subject_assignments t_class
        WHERE t_class.teacher_id = u.id
          AND t_class.class_id = " . (int)$class_filter . "
    )";
}

if ($status_filter === 'good') {
    $teacher_sql .= " AND IFNULL(sp.avg_completion, 0) >= 85";
} elseif ($status_filter === 'average') {
    $teacher_sql .= " AND IFNULL(sp.avg_completion, 0) >= 60 AND IFNULL(sp.avg_completion, 0) < 85";
} elseif ($status_filter === 'at_risk') {
    $teacher_sql .= " AND IFNULL(sp.avg_completion, 0) < 60";
}

// Apply question-style filters, keeping thresholds in code to avoid arbitrary input
if ($workload_filter === 'light') {
    $teacher_sql .= " AND IFNULL(st.student_count, 0) <= 40";
} elseif ($workload_filter === 'balanced') {
    $teacher_sql .= " AND IFNULL(st.student_count, 0) > 40 AND IFNULL(st.student_count, 0) <= 80";
} elseif ($workload_filter === 'heavy') {
    $teacher_sql .= " AND IFNULL(st.student_count, 0) > 80";
}

if ($completion_filter === 'ahead') {
    $teacher_sql .= " AND IFNULL(sp.avg_completion, 0) >= 85";
} elseif ($completion_filter === 'on_track') {
    $teacher_sql .= " AND IFNULL(sp.avg_completion, 0) >= 60 AND IFNULL(sp.avg_completion, 0) < 85";
} elseif ($completion_filter === 'behind') {
    $teacher_sql .= " AND IFNULL(sp.avg_completion, 0) < 60";
}

if ($alert_filter === 'urgent') {
    $teacher_sql .= " AND IFNULL(al.pending_alerts, 0) >= 3";
} elseif ($alert_filter === 'open') {
    $teacher_sql .= " AND IFNULL(al.pending_alerts, 0) BETWEEN 1 AND 2";
} elseif ($alert_filter === 'clear') {
    $teacher_sql .= " AND IFNULL(al.pending_alerts, 0) = 0";
}

$teacher_sql .= " ORDER BY u.name ASC";

$stmt = mysqli_prepare($conn, $teacher_sql);
if ($teacher_types === 's') {
    mysqli_stmt_bind_param($stmt, 's', $teacher_params[0]);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$teachers = [];
$chartData = [
    'ids' => [],
    'names' => [],
    'avgCompletion' => [],
    'studentCounts' => [],
    'avgMarks' => [],
    'pendingAlerts' => [],
    'resolvedAlerts' => []
];

$totalTeachers = 0;
$totalStudents = 0;
$sumCompletion = 0;
$sumAvgMarks = 0;
$teachersWithMarks = 0;
$totalPendingAlerts = 0;
$totalResolvedAlerts = 0;
$topTeacherName = '';
$topTeacherCompletion = -1;
$topTeacherId = null;

while ($row = mysqli_fetch_assoc($result)) {
    $teacherNameRaw = isset($row['name']) ? trim((string)$row['name']) : '';
    $row['name_display'] = $teacherNameRaw !== '' ? format_person_display($teacherNameRaw) : '';
    $row['avg_completion'] = round((float)$row['avg_completion'], 2);
    if ($row['avg_student_mark'] !== null) {
        $row['avg_student_mark'] = round((float)$row['avg_student_mark'], 2);
    }
    $row['pending_alerts'] = (int)$row['pending_alerts'];
    $row['resolved_alerts'] = (int)$row['resolved_alerts'];
    $row['student_count'] = (int)$row['student_count'];
    $row['assigned_courses'] = (int)$row['assigned_courses'];
    $row['timetable_count'] = (int)$row['timetable_count'];

    $teachers[] = $row;

    $chartData['ids'][] = (int)$row['id'];
    $chartData['names'][] = $row['name_display'] !== '' ? $row['name_display'] : $teacherNameRaw;
    $chartData['avgCompletion'][] = $row['avg_completion'];
    $chartData['studentCounts'][] = $row['student_count'];
    $chartData['avgMarks'][] = $row['avg_student_mark'];
    $chartData['pendingAlerts'][] = $row['pending_alerts'];
    $chartData['resolvedAlerts'][] = $row['resolved_alerts'];

    $totalTeachers++;
    $sumCompletion += $row['avg_completion'];
    if ($row['avg_student_mark'] !== null) {
        $sumAvgMarks += $row['avg_student_mark'];
        $teachersWithMarks++;
    }
    $totalPendingAlerts += $row['pending_alerts'];
    $totalResolvedAlerts += $row['resolved_alerts'];

    if ($row['avg_completion'] > $topTeacherCompletion) {
        $topTeacherCompletion = $row['avg_completion'];
        $topTeacherName = $row['name_display'] !== '' ? $row['name_display'] : $teacherNameRaw;
        $topTeacherId = $row['id'];
    }
}

mysqli_stmt_close($stmt);

$student_scope_conditions = [];
if ($school_filter !== '') {
    $escaped_school_scope = mysqli_real_escape_string($conn, $school_filter);
    $scope_school_clauses = [];
    if ($class_has_school) {
        $scope_school_clauses[] = "cls_scope.school = '{$escaped_school_scope}'";
    }
    if ($class_has_department) {
        if ($class_has_school) {
            $scope_school_clauses[] = "((cls_scope.school IS NULL OR cls_scope.school = '') AND cls_scope.department = '{$escaped_school_scope}')";
        } else {
            $scope_school_clauses[] = "cls_scope.department = '{$escaped_school_scope}'";
        }
    }
    if (!empty($scope_school_clauses)) {
        $student_scope_conditions[] = '(' . implode(' OR ', $scope_school_clauses) . ')';
    }
}
if ($semester_filter !== '') {
    $student_scope_conditions[] = 'cls_scope.semester = ' . (int)$semester_filter;
}
if ($class_filter !== '') {
    $student_scope_conditions[] = 'tsa.class_id = ' . (int)$class_filter;
}
if ($activeTermId > 0) {
    $term_id = (int)$activeTermId;
    $student_scope_conditions[] = "(cls_scope.academic_term_id = {$term_id} OR cls_scope.academic_term_id IS NULL)";
}

$student_scope_sql = "
    SELECT COUNT(DISTINCT CASE
        WHEN TRIM(COALESCE(st.sap_id, '')) <> '' THEN UPPER(TRIM(st.sap_id))
        ELSE CONCAT('ID#', CAST(st.id AS CHAR))
    END) AS total_students
    FROM students st
    JOIN (
        SELECT DISTINCT tsa.class_id, COALESCE(tsa.section_id, 0) AS section_id
        FROM teacher_subject_assignments tsa
        INNER JOIN classes cls_scope ON cls_scope.id = tsa.class_id";
if (!empty($student_scope_conditions)) {
    $student_scope_sql .= ' WHERE ' . implode(' AND ', $student_scope_conditions);
}
$student_scope_sql .= "
    ) scoped ON scoped.class_id = st.class_id
           AND (scoped.section_id = 0 OR COALESCE(st.section_id, 0) = scoped.section_id)";

$student_scope_result = mysqli_query($conn, $student_scope_sql);
$totalStudents = 0;
if ($student_scope_result && ($student_scope_row = mysqli_fetch_assoc($student_scope_result))) {
    $totalStudents = (int)($student_scope_row['total_students'] ?? 0);
    mysqli_free_result($student_scope_result);
}

$overallAvgCompletion = $totalTeachers ? round($sumCompletion / $totalTeachers, 1) : 0;
$overallAvgStudentMark = $teachersWithMarks ? round($sumAvgMarks / $teachersWithMarks, 1) : 0;
$chartDataJson = json_encode($chartData, JSON_NUMERIC_CHECK);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Progress - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            color: white;
            font-size: 0.8em;
            font-weight: bold;
        }
        .badge-info {
            background-color: #17a2b8;
        }
        .badge-success {
            background-color: #28a745;
        }
        .badge-warning {
            background-color: #ffc107;
        }
        .clickable-row {
            cursor: pointer;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 900px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .modal-header h4 {
            margin: 0;
            color: #A6192E;
            font-size: 1.5rem;
            font-weight: 600;
        }
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover, .close:focus {
            color: #A6192E;
            text-decoration: none;
        }
        .view-btn {
            padding: 8px 16px;
            background-color: #A6192E;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
        }
        .view-btn:hover {
            background-color: #8b1624;
        }
        body.dark-mode .modal-content {
            background-color: #5a5a5a;
            color: #e0e0e0;
        }
        body.dark-mode .modal-header {
            border-bottom: 1px solid #777;
        }
        body.dark-mode .view-btn {
            background-color: #8b1624;
        }
        body.dark-mode .view-btn:hover {
            background-color: #A6192E;
        }
        #comparisonChartContainer {
            height: 400px;
        }
        .insight-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .insight-grid.insight-grid-static {
            grid-template-columns: repeat(7, minmax(120px, 1fr));
            gap: 10px;
            align-items: stretch;
        }
        .insight-card {
            background: #f9f9f9;
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: box-shadow 0.15s ease;
        }
        .insight-grid.insight-grid-static .insight-card {
            padding: 12px;
            border-radius: 10px;
        }
        .insight-card[data-insight] {
            cursor: pointer;
        }
        .insight-card[data-insight]:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        .insight-card .label {
            display: block;
            font-size: 0.85rem;
            color: #555;
            margin-bottom: 6px;
        }
        .insight-grid.insight-grid-static .insight-card .label {
            font-size: 0.78rem;
            margin-bottom: 4px;
            line-height: 1.2;
        }
        .insight-card .value {
            font-size: 1.4rem;
            font-weight: 700;
            color: #A6192E;
        }
        .insight-grid.insight-grid-static .insight-card .value {
            font-size: 1.12rem;
            line-height: 1.25;
        }
        .insight-card .value-subtext {
            display: block;
            margin-top: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #555;
        }
        body.dark-mode .insight-card {
            background-color: #4a4a4a;
            color: #ffffff;
        }
        body.dark-mode .insight-card[data-insight]:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        body.dark-mode .insight-card .label {
            color: #dddddd;
        }
        body.dark-mode .insight-card .value-subtext {
            color: #dddddd;
        }
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .chart-item {
            background: #ffffff;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .chart-item h6 {
            margin-bottom: 12px;
            font-size: 1rem;
            color: #A6192E;
        }
        .chart-item.full {
            grid-column: 1 / -1;
        }
        body.dark-mode .chart-item {
            background-color: #4a4a4a;
            color: #ffffff;
        }
        body.dark-mode .chart-item h6 {
            color: #ffccd5;
        }
        .chart-item canvas {
            width: 100% !important;
            height: 260px !important;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(205px, 1fr));
            gap: 6px;
            margin-bottom: 2px;
            grid-auto-rows: 1fr;
        }
        .filter-grid .form-group {
            display: flex;
            flex-direction: column;
            gap: 3px;
            height: 100%;
        }
        .filter-grid label {
            display: block;
            font-weight: 600;
            color: #A6192E;
            margin-bottom: 0;
            font-size: 0.83rem;
            line-height: 1.2;
        }
        .filter-grid select {
            width: 100%;
            padding: 3px 8px;
            border-radius: 7px;
            border: 1px solid #ccc;
            background-color: #fff;
            flex-grow: 1;
            font-size: 0.84rem;
            min-height: 30px;
            line-height: 1.25;
        }
        .filter-grid select option {
            font-size: 0.83rem;
        }
        .filter-actions {
            display: flex;
            align-items: flex-end;
            gap: 6px;
            flex-wrap: wrap;
        }
        .filter-actions > div {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .filter-actions .btn {
            background-color: #f0f0f0;
            color: #333;
        }
        .filter-actions .btn.primary {
            background-color: #A6192E;
            color: #ffffff;
        }
        .filter-actions .btn.primary:hover {
            background-color: #8c1426;
        }
        .filter-actions .btn:hover {
            background-color: #e0e0e0;
        }
        body.dark-mode .filter-grid select {
            background-color: #5a5a5a;
            border-color: #777;
            color: #ffffff;
        }
        body.dark-mode .filter-grid label {
            color: #ffccd5;
        }
        body.dark-mode .filter-actions .btn {
            background-color: #6a6a6a;
            color: #ffffff;
        }
        body.dark-mode .filter-actions .btn.primary {
            background-color: #c4354c;
        }
        body.dark-mode .filter-actions .btn.primary:hover {
            background-color: #b02d42;
        }
        body.dark-mode .filter-actions .btn:hover {
            background-color: #7a7a7a;
        }
        .filter-section {
            margin-bottom: 6px;
        }
        .filter-section:last-of-type {
            margin-bottom: 0;
        }
        .filter-section h6 {
            margin: 0 0 2px 0;
            font-size: 0.84rem;
            color: #444;
            font-weight: 600;
        }
        .card-body > form[method="get"] {
            margin: 0;
        }
        .filter-tools-card .card-header {
            margin-bottom: 6px;
        }
        .filter-tools-card .card-body {
            padding-top: 0;
        }
        .filter-tools-card #compareBtn {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        body.dark-mode .filter-section h6 {
            color: #ffccd5;
        }
        .insight-modal-body table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .insight-modal-body th,
        .insight-modal-body td {
            padding: 8px 10px;
            border-bottom: 1px solid #e0e0e0;
            text-align: left;
            font-size: 0.95rem;
        }
        .insight-modal-body tr:nth-child(even) {
            background-color: #fafafa;
        }
        body.dark-mode .insight-modal-body th,
        body.dark-mode .insight-modal-body td {
            border-color: #666;
        }
        body.dark-mode .insight-modal-body tr:nth-child(even) {
            background-color: #555;
        }
    </style>
</head>
<body class="program-chair">
    <div class="dashboard">
        <div class="sidebar">
            <h2>ICA Tracker</h2>
            <a href="program_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="teacher_progress.php" class="active"><i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span></a>
            <a href="student_progress.php"><i class="fas fa-user-graduate"></i> <span>Students</span></a>
            <a href="course_progress.php"><i class="fas fa-book"></i> <span>Courses</span></a>
            <a href="program_reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
            <a href="send_alerts.php"><i class="fas fa-bell"></i> <span>Alerts</span></a>
            <a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
            <a href="edit_profile.php"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
            <a href="login_as.php?role=teacher"><i class="fas fa-exchange-alt"></i> <span>Switch to Teacher</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
        <div class="main-content">
            <div class="header"><h2>Teacher Progress Analysis</h2></div>
            <div class="container">
                <div class="card filter-tools-card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h5>Filters & Tools</h5>
                            <button id="compareBtn" class="btn"><i class="fas fa-balance-scale"></i> Compare Selected</button>
                        </div>
                    </div>
                    <div class="card-body">
<form method="get">
    <div class="filter-section">
        <h6>Classroom focus</h6>
        <div class="filter-grid">
            <div class="form-group">
                <label>School</label>
                <select name="school">
                    <option value="" <?php echo $school_filter === '' ? 'selected' : ''; ?>>All schools</option>
                    <?php foreach ($available_schools as $school_option): ?>
                        <option value="<?php echo htmlspecialchars($school_option); ?>" <?php echo $school_filter === $school_option ? 'selected' : ''; ?>><?php echo htmlspecialchars($school_option); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Semester</label>
                <select name="semester_filter">
                    <option value="" <?php echo $semester_filter === '' ? 'selected' : ''; ?>>All semesters</option>
                    <?php foreach ($semesters as $semesterOption): ?>
                        <option value="<?php echo $semesterOption; ?>" <?php echo ($semester_filter !== '' && (int)$semester_filter === (int)$semesterOption) ? 'selected' : ''; ?>>Semester <?php echo $semesterOption; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Class</label>
                <select name="class_filter">
                    <option value="" <?php echo $class_filter === '' ? 'selected' : ''; ?>>All classes</option>
                    <?php if (!empty($classes_list)): ?>
                        <?php foreach ($classes_list as $classOption): ?>
                            <option value="<?php echo $classOption['id']; ?>" <?php echo ($class_filter !== '' && (int)$class_filter === (int)$classOption['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($classOption['name']); ?> (Sem <?php echo $classOption['semester']; ?>)</option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>No classes found</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Teacher Status</label>
                <select name="status_filter">
                    <option value="" <?php echo $status_filter === '' ? 'selected' : ''; ?>>All statuses</option>
                    <option value="good" <?php echo $status_filter === 'good' ? 'selected' : ''; ?>>Good standing</option>
                    <option value="average" <?php echo $status_filter === 'average' ? 'selected' : ''; ?>>Average</option>
                    <option value="at_risk" <?php echo $status_filter === 'at_risk' ? 'selected' : ''; ?>>At-risk</option>
                </select>
            </div>
        </div>
    </div>
    <div class="filter-section">
        <h6>Performance insights</h6>
        <div class="filter-grid">
            <div class="form-group">
                <label>Teacher workload</label>
                <select name="workload_filter">
                    <option value="" <?php echo $workload_filter === '' ? 'selected' : ''; ?>>Show every workload</option>
                    <option value="light" <?php echo $workload_filter === 'light' ? 'selected' : ''; ?>>Up to 40 students</option>
                    <option value="balanced" <?php echo $workload_filter === 'balanced' ? 'selected' : ''; ?>>41-80 students</option>
                    <option value="heavy" <?php echo $workload_filter === 'heavy' ? 'selected' : ''; ?>>81+ students</option>
                </select>
            </div>
            <div class="form-group">
                <label>Syllabus pace</label>
                <select name="completion_filter">
                    <option value="" <?php echo $completion_filter === '' ? 'selected' : ''; ?>>All completion levels</option>
                    <option value="ahead" <?php echo $completion_filter === 'ahead' ? 'selected' : ''; ?>>Ahead (G��85%)</option>
                    <option value="on_track" <?php echo $completion_filter === 'on_track' ? 'selected' : ''; ?>>On track (60-84%)</option>
                    <option value="behind" <?php echo $completion_filter === 'behind' ? 'selected' : ''; ?>>Behind (&lt;60%)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Alert urgency</label>
                <select name="alert_filter">
                    <option value="" <?php echo $alert_filter === '' ? 'selected' : ''; ?>>All alert states</option>
                    <option value="urgent" <?php echo $alert_filter === 'urgent' ? 'selected' : ''; ?>>Urgent (3+ open)</option>
                    <option value="open" <?php echo $alert_filter === 'open' ? 'selected' : ''; ?>>Follow-up (1-2 open)</option>
                    <option value="clear" <?php echo $alert_filter === 'clear' ? 'selected' : ''; ?>>No pending alerts</option>
                </select>
            </div>
            <div class="form-group filter-actions">
                <label>&nbsp;</label>
                <div>
                    <button type="submit" class="btn primary" style="padding: 5px 10px;">Apply filters</button>
                    <a href="teacher_progress.php" class="btn" style="padding: 5px 10px;">Reset filters</a>
                </div>
            </div>
        </div>
    </div>
</form>

                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h5>Program Chair Insights</h5></div>
                    <div class="card-body">
                        <div class="insight-grid insight-grid-static">
                            <div class="insight-card" data-insight="total_teachers">
                                <span class="label">Total Teachers</span>
                                <span class="value"><?php echo $totalTeachers; ?></span>
                            </div>
                            <div class="insight-card" data-insight="total_students">
                                <span class="label">Total Students Mapped</span>
                                <span class="value"><?php echo $totalStudents; ?></span>
                            </div>
                            <div class="insight-card" data-insight="avg_completion">
                                <span class="label">Avg. Syllabus Completion</span>
                                <span class="value"><?php echo $overallAvgCompletion; ?>%</span>
                            </div>
                            <div class="insight-card" data-insight="avg_ica">
                                <span class="label">Avg. ICA Performance</span>
                                <span class="value"><?php echo $overallAvgStudentMark; ?>%</span>
                            </div>
                            <div class="insight-card" data-insight="open_alerts">
                                <span class="label">Open Alerts</span>
                                <span class="value"><?php echo $totalPendingAlerts; ?></span>
                            </div>
                            <div class="insight-card" data-insight="resolved_alerts">
                                <span class="label">Resolved Alerts</span>
                                <span class="value"><?php echo $totalResolvedAlerts; ?></span>
                            </div>
                            <div class="insight-card" data-insight="top_teacher" data-teacher-id="<?php echo $topTeacherId !== null ? (int)$topTeacherId : ''; ?>" data-teacher-name="<?php echo htmlspecialchars($topTeacherName); ?>">
                                <span class="label">Top Performing Teacher</span>
                                <span class="value">
                                    <?php
                                        if ($topTeacherCompletion >= 0 && $topTeacherName) {
                                            echo htmlspecialchars($topTeacherName) . ' (' . number_format($topTeacherCompletion, 1) . '%)';
                                        } else {
                                            echo 'N/A';
                                        }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h5>Teacher Analytics</h5></div>
                    <div class="card-body">
                        <?php if (empty($chartData['ids'])): ?>
                            <p>No teacher analytics available yet. Assign teachers to classes and update progress to see insights.</p>
                        <?php else: ?>
                            <div class="chart-grid">
                                <div class="chart-item">
                                    <h6>Average Syllabus Completion</h6>
                                    <canvas id="teacherCompletionChart" height="260"></canvas>
                                </div>
                                <div class="chart-item">
                                    <h6>Student Load & ICA Performance</h6>
                                    <canvas id="teacherStudentChart" height="260"></canvas>
                                </div>
                                <div class="chart-item full">
                                    <h6>Alert Snapshot</h6>
                                    <canvas id="teacherAlertChart" height="260"></canvas>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h5>Teacher Data Overview</h5></div>
                    <div class="card-body">
                        <table>
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>Name</th>
                                    <th>Assigned Courses</th>
                                    <th>Syllabus Status</th>
                                    <th>Student Load</th>
                                    <th>Avg ICA %</th>
                                    <th>Pending Alerts</th>
                                    <th>Teacher Schedule</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teachers as $teacher): ?>
                                    <?php
                                        $teacherNameDisplay = $teacher['name_display'] ?? '';
                                        if ($teacherNameDisplay === '' && isset($teacher['name'])) {
                                            $teacherNameDisplay = format_person_display(trim((string)$teacher['name']));
                                        }
                                    ?>
                                    <tr class="clickable-row" data-teacher-id="<?php echo $teacher['id']; ?>" data-teacher-name="<?php echo htmlspecialchars($teacherNameDisplay); ?>">
                                        <td><input type="checkbox" class="teacher-checkbox" value="<?php echo $teacher['id']; ?>" data-name="<?php echo htmlspecialchars($teacherNameDisplay); ?>" data-completion="<?php echo $teacher['avg_completion'] ?? 0; ?>"></td>
                                        <td><?php echo htmlspecialchars($teacherNameDisplay); ?></td>
                                        <td><?php echo $teacher['assigned_courses']; ?></td>
                                        <td>
                                            <?php
                                            $comp = $teacher['avg_completion'] ?? 0;
                                            if ($comp > 85) {
                                                echo '<span class="badge badge-info">Ahead</span>';
                                            } elseif ($comp >= 60) {
                                                echo '<span class="badge badge-success">On Track</span>';
                                            } else {
                                                echo '<span class="badge badge-warning">Behind</span>';
                                            }
                                            echo ' (' . number_format($comp, 1) . '%)';
                                            ?>
                                        </td>
                                        <td><?php echo $teacher['student_count']; ?></td>
                                        <td><?php echo $teacher['avg_student_mark'] > 0 ? number_format($teacher['avg_student_mark'], 1) . '%' : 'N/A'; ?></td>
                                        <td>
                                            <?php if ($teacher['pending_alerts'] > 0): ?>
                                                <span class="badge badge-warning"><?php echo $teacher['pending_alerts']; ?> pending</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($teacher['timetable_count'] > 0): ?>
                                                <a href="#" class="view-btn view-timetable" data-teacher-id="<?php echo $teacher['id']; ?>" data-teacher-name="<?php echo htmlspecialchars($teacherNameDisplay); ?>">View</a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="teacherDetailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="modalTeacherName"></h4>
                <span class="close">&times;</span>
            </div>
            <div id="modalBody">
                <h5>Assigned Courses Overview</h5>
                <table id="modalCoursesTable">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Class</th>
                            <th>Syllabus %</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <h5 style="margin-top: 20px;">Recent Alerts</h5>
                <table id="modalAlertsTable">
                    <thead>
                        <tr>
                            <th>Message</th>
                            <th>Response</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <h5 style="margin-top: 20px;">Student Performance Snapshot</h5>
                <div class="insight-grid" id="studentPerformanceSummary">
                    <div class="insight-card">
                        <span class="label">Total Students</span>
                        <span class="value" id="summaryTotalStudents">--</span>
                    </div>
                    <div class="insight-card">
                        <span class="label">Avg ICA Marks</span>
                        <span class="value" id="summaryAvgMark">--</span>
                    </div>
                    <div class="insight-card">
                        <span class="label">High Achievers (&ge;80%)</span>
                        <span class="value" id="summaryHighAchievers">--</span>
                    </div>
                    <div class="insight-card">
                        <span class="label"><?php echo htmlspecialchars($needs_support_label, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="value" id="summaryNeedsSupport">--</span>
                    </div>
                </div>
                <h5 style="margin-top: 20px;">Top Performing Students</h5>
                <table id="topStudentsTable">
                    <thead>
                        <tr>
                            <th>SAP ID</th>
                            <th>Student Name</th>
                            <th>ICA Marks</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div id="timetableModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="timetableModalTeacherName"></h4>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="card">
                    <div class="card-header">
                        <h5>Uploaded Timetables</h5>
                    </div>
                    <div class="card-body">
                        <table id="timetableTable">
                            <thead>
                                <tr>
                                    <th>File Name</th>
                                    <th>Uploaded At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="insightModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="insightModalTitle"></h4>
                <span class="close">&times;</span>
            </div>
            <div class="insight-modal-body" id="insightModalBody"></div>
        </div>
    </div>
    
    <div id="comparisonModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Teacher Comparison</h4>
                <span class="close">&times;</span>
            </div>
            <div id="comparisonBody">
                <div id="comparisonChartContainer">
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const teacherChartData = <?php echo $chartDataJson; ?>;
            const teacherTableData = <?php echo json_encode($teachers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_NUMERIC_CHECK); ?>;
            const detailModal = document.getElementById('teacherDetailModal');
            const timetableModal = document.getElementById('timetableModal');
            const comparisonModal = document.getElementById('comparisonModal');
            const insightModal = document.getElementById('insightModal');
            const insightModalTitle = document.getElementById('insightModalTitle');
            const insightModalBody = document.getElementById('insightModalBody');
            const closeBtns = document.querySelectorAll('.close');
            const modals = [detailModal, timetableModal, comparisonModal, insightModal];
            const baseSearchParams = new URLSearchParams(window.location.search);

            function hideAllModals() {
                modals.forEach(modal => {
                    if (modal) {
                        modal.style.display = 'none';
                    }
                });
            }
            let comparisonChart = null;
            let completionChart = null;
            let studentChart = null;
            let alertChart = null;

            const summaryTotalStudents = document.getElementById('summaryTotalStudents');
            const summaryAvgMark = document.getElementById('summaryAvgMark');
            const summaryHighAchievers = document.getElementById('summaryHighAchievers');
            const summaryNeedsSupport = document.getElementById('summaryNeedsSupport');

            function resetStudentSummary() {
                if (summaryTotalStudents) summaryTotalStudents.innerHTML = '--';
                if (summaryAvgMark) summaryAvgMark.innerHTML = '--';
                if (summaryHighAchievers) summaryHighAchievers.innerHTML = '--';
                if (summaryNeedsSupport) summaryNeedsSupport.innerHTML = '--';
                const topTbody = document.querySelector('#topStudentsTable tbody');
                if (topTbody) {
                    topTbody.innerHTML = '<tr><td colspan="3">Loading...</td></tr>';
                }
            }

            function showTeacherDetail(teacherId, teacherName) {
                if (!teacherId) {
                    return;
                }
                hideAllModals();
                document.getElementById('modalTeacherName').innerText = teacherName || 'Teacher Details';
                document.querySelector('#modalCoursesTable tbody').innerHTML = '<tr><td colspan="3">Loading...</td></tr>';
                document.querySelector('#modalAlertsTable tbody').innerHTML = '<tr><td colspan="3">Loading...</td></tr>';
                resetStudentSummary();
                detailModal.style.display = 'block';

                const detailParams = new URLSearchParams(baseSearchParams.toString());
                detailParams.set('action', 'get_teacher_details');
                detailParams.set('id', teacherId);

                fetch(`?${detailParams.toString()}`)
                    .then(res => res.json())
                    .then(data => {
                        const coursesTbody = document.querySelector('#modalCoursesTable tbody');
                        coursesTbody.innerHTML = '';
                        if (data.courses && data.courses.length) {
                            data.courses.forEach(c => {
                                const classDisplay = c.class_label || 'Unassigned';
                                coursesTbody.innerHTML += `<tr><td>${c.subject_name}</td><td>${classDisplay}</td><td>${parseFloat(c.syllabus_pct || 0).toFixed(2)}%</td></tr>`;
                            });
                        } else {
                            coursesTbody.innerHTML = '<tr><td colspan="3">No course data yet.</td></tr>';
                        }

                        const alertsTbody = document.querySelector('#modalAlertsTable tbody');
                        alertsTbody.innerHTML = '';
                        if (data.alerts && data.alerts.length) {
                            data.alerts.forEach(a => {
                                alertsTbody.innerHTML += `<tr><td>${a.message}</td><td>${a.response || 'N/A'}</td><td>${a.status}</td></tr>`;
                            });
                        } else {
                            alertsTbody.innerHTML = '<tr><td colspan="3">No recent alerts.</td></tr>';
                        }

                        if (data.student_summary) {
                            const totalStudents = data.student_summary.total_students ?? 0;
                            const evaluatedStudents = data.student_summary.evaluated_students ?? 0;
                            summaryTotalStudents.innerHTML = `${totalStudents}<span class="value-subtext">Evaluated: ${evaluatedStudents}</span>`;
                            const avgObtained = data.student_summary.avg_marks_obtained;
                            const avgTotal = data.student_summary.avg_marks_total;
                            if (avgObtained !== null && avgObtained !== undefined && avgTotal !== null && avgTotal !== undefined && Number(avgTotal) > 0) {
                                const obtainedLabel = formatScore(avgObtained);
                                const totalLabel = formatScore(avgTotal);
                                summaryAvgMark.innerHTML = obtainedLabel && totalLabel
                                    ? `${obtainedLabel} / ${totalLabel}<span class="value-subtext">Avg: ${formatPercentSafe(data.student_summary.avg_percentage)}</span>`
                                    : 'N/A';
                            } else {
                                summaryAvgMark.innerHTML = 'N/A';
                            }
                            summaryHighAchievers.innerHTML = `${data.student_summary.high_achievers ?? 0}`;
                            summaryNeedsSupport.innerHTML = `${data.student_summary.needs_support ?? 0}`;
                            const performanceThresholdValue = data.thresholds && typeof data.thresholds.performance !== 'undefined'
                                ? Number(data.thresholds.performance)
                                : null;
                            if (Number.isFinite(performanceThresholdValue)) {
                                summaryNeedsSupport.setAttribute('title', `Students scoring below ${Math.round(performanceThresholdValue)}%`);
                            } else {
                                summaryNeedsSupport.removeAttribute('title');
                            }
                        } else {
                            summaryTotalStudents.innerHTML = '0';
                            summaryAvgMark.innerHTML = 'N/A';
                            summaryHighAchievers.innerHTML = '0';
                            summaryNeedsSupport.innerHTML = '0';
                            summaryNeedsSupport.removeAttribute('title');
                        }

                        const topTbody = document.querySelector('#topStudentsTable tbody');
                        topTbody.innerHTML = '';
                        if (data.top_students && data.top_students.length) {
                            data.top_students.forEach(student => {
                                const obtainedLabel = formatScore(student.marks_obtained);
                                const totalLabel = formatScore(student.marks_total);
                                const markDisplay = (obtainedLabel && totalLabel && Number(student.marks_total) > 0)
                                    ? `${obtainedLabel} / ${totalLabel}`
                                    : 'N/A';
                                const pctTooltip = student.performance_pct !== null && student.performance_pct !== undefined
                                    ? ` title="${Number(student.performance_pct).toFixed(1)}%"`
                                    : '';
                                topTbody.innerHTML += `<tr><td>${student.sap_id || '-'}</td><td>${student.student_name || '-'}</td><td${pctTooltip}>${markDisplay}</td></tr>`;
                            });
                        } else {
                            topTbody.innerHTML = '<tr><td colspan="3">No student performance data yet.</td></tr>';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching teacher details:', error);
                        document.querySelector('#modalCoursesTable tbody').innerHTML = '<tr><td colspan="3">Error loading course data.</td></tr>';
                        document.querySelector('#modalAlertsTable tbody').innerHTML = '<tr><td colspan="3">Error loading alerts.</td></tr>';
                        const topTbody = document.querySelector('#topStudentsTable tbody');
                        if (topTbody) {
                            topTbody.innerHTML = '<tr><td colspan="3">Error loading student data.</td></tr>';
                        }
                        summaryTotalStudents.innerText = '--';
                        summaryAvgMark.innerHTML = 'N/A';
                        summaryHighAchievers.innerHTML = '--';
                        summaryNeedsSupport.innerHTML = '--';
                    });
            }

            function handleChartClick(elements) {
                if (!elements || !elements.length) return;
                const index = elements[0].index;
                const teacherId = teacherChartData.ids[index];
                const teacherName = teacherChartData.names[index];
                showTeacherDetail(teacherId, teacherName);
            }

            document.querySelectorAll('.insight-card[data-insight]').forEach(card => {
                const insightType = card.dataset.insight;
                card.addEventListener('click', () => {
                    if (insightType === 'top_teacher') {
                        const teacherId = parseInt(card.dataset.teacherId, 10);
                        if (!Number.isNaN(teacherId)) {
                            const teacherName = card.dataset.teacherName || '';
                            showTeacherDetail(teacherId, teacherName);
                        }
                        return;
                    }
                    openInsightDetail(insightType);
                });
            });

            function formatScore(value, decimals = 1) {
                const num = Number(value);
                if (!Number.isFinite(num)) {
                    return null;
                }
                if (Math.abs(num - Math.round(num)) < 0.05) {
                    return String(Math.round(num));
                }
                return num.toFixed(decimals);
            }

            function formatPercent(value, decimals = 1) {
                const num = Number(value);
                if (!Number.isFinite(num)) {
                    return 'N/A';
                }
                return `${num.toFixed(decimals)}%`;
            }

            function formatPercentSafe(value, decimals = 1) {
                const num = Number(value);
                if (!Number.isFinite(num)) {
                    return 'N/A';
                }
                return `${num.toFixed(decimals)}%`;
            }

            function formatIcaPercent(value, decimals = 1) {
                const num = Number(value);
                if (!Number.isFinite(num) || num <= 0) {
                    return 'N/A';
                }
                return `${num.toFixed(decimals)}%`;
            }

            function mapStatusLabel(value) {
                const num = Number(value);
                if (!Number.isFinite(num)) {
                    return 'Not Allocated';
                }
                if (num >= 85) {
                    return 'Good Standing';
                }
                if (num >= 60) {
                    return 'Average';
                }
                return 'At-Risk';
            }

            function renderInsightModal(config) {
                const { title, columns, rows, emptyMessage } = config;
                insightModalTitle.textContent = title;
                insightModalBody.innerHTML = '';

                if (!rows.length) {
                    const emptyState = document.createElement('p');
                    emptyState.textContent = emptyMessage;
                    insightModalBody.appendChild(emptyState);
                } else {
                    const table = document.createElement('table');
                    const thead = document.createElement('thead');
                    const headRow = document.createElement('tr');

                    columns.forEach(col => {
                        const th = document.createElement('th');
                        th.textContent = col;
                        headRow.appendChild(th);
                    });

                    thead.appendChild(headRow);
                    table.appendChild(thead);

                    const tbody = document.createElement('tbody');
                    rows.forEach(row => {
                        const tr = document.createElement('tr');
                        row.forEach(cell => {
                            const td = document.createElement('td');
                            td.textContent = cell;
                            tr.appendChild(td);
                        });
                        tbody.appendChild(tr);
                    });

                    table.appendChild(tbody);
                    insightModalBody.appendChild(table);
                }

                hideAllModals();
                insightModal.style.display = 'block';
            }

            function openInsightDetail(type) {
                const data = Array.isArray(teacherTableData) ? teacherTableData : [];
                const config = {
                    title: '',
                    columns: [],
                    rows: [],
                    emptyMessage: 'No data available yet.'
                };

                switch (type) {
                    case 'total_teachers': {
                        config.title = 'Teacher Roster';
                        config.columns = ['Teacher', 'Status', 'Assigned Courses', 'Completion %', 'Avg ICA %', 'Student Load', 'Pending Alerts', 'Resolved Alerts'];
                        config.rows = data.map(entry => [
                            entry.name || '-',
                            mapStatusLabel(entry.avg_completion),
                            entry.assigned_courses ?? 0,
                            formatPercent(entry.avg_completion),
                            formatIcaPercent(entry.avg_student_mark),
                            entry.student_count ?? 0,
                            entry.pending_alerts ?? 0,
                            entry.resolved_alerts ?? 0
                        ]);
                        config.emptyMessage = 'No teachers are assigned yet.';
                        break;
                    }
                    case 'total_students': {
                        config.title = 'Student Distribution by Teacher';
                        config.columns = ['Teacher', 'Status', 'Student Load', 'Avg ICA %', 'Assigned Courses', 'Pending Alerts'];
                        const sorted = [...data].sort((a, b) => (Number(b.student_count) || 0) - (Number(a.student_count) || 0));
                        config.rows = sorted.map(entry => [
                            entry.name || '-',
                            mapStatusLabel(entry.avg_completion),
                            entry.student_count ?? 0,
                            formatIcaPercent(entry.avg_student_mark),
                            entry.assigned_courses ?? 0,
                            entry.pending_alerts ?? 0
                        ]);
                        config.emptyMessage = 'No students have been mapped yet.';
                        break;
                    }
                    case 'avg_completion': {
                        config.title = 'Syllabus Completion Status';
                        config.columns = ['Teacher', 'Status', 'Avg Completion %', 'Students'];
                        const sorted = [...data].sort((a, b) => (Number(b.avg_completion) || 0) - (Number(a.avg_completion) || 0));
                        config.rows = sorted.map(entry => [
                            entry.name || '-',
                            mapStatusLabel(entry.avg_completion),
                            formatPercent(entry.avg_completion),
                            entry.student_count ?? 0
                        ]);
                        config.emptyMessage = 'Syllabus progress is not available yet.';
                        break;
                    }
                    case 'avg_ica': {
                        config.title = 'Average ICA Performance';
                        config.columns = ['Teacher', 'Status', 'Avg ICA %', 'Students', 'Completion %'];
                        const sorted = [...data].sort((a, b) => (Number(b.avg_student_mark) || 0) - (Number(a.avg_student_mark) || 0));
                        config.rows = sorted.map(entry => [
                            entry.name || '-',
                            mapStatusLabel(entry.avg_completion),
                            formatIcaPercent(entry.avg_student_mark),
                            entry.student_count ?? 0,
                            formatPercent(entry.avg_completion)
                        ]);
                        config.emptyMessage = 'ICA performance data is not available yet.';
                        break;
                    }
                    case 'open_alerts': {
                        config.title = 'Teachers with Open Alerts';
                        config.columns = ['Teacher', 'Status', 'Pending Alerts', 'Completion %', 'Students'];
                        const filtered = data.filter(entry => (Number(entry.pending_alerts) || 0) > 0)
                            .sort((a, b) => (Number(b.pending_alerts) || 0) - (Number(a.pending_alerts) || 0));
                        config.rows = filtered.map(entry => [
                            entry.name || '-',
                            mapStatusLabel(entry.avg_completion),
                            entry.pending_alerts ?? 0,
                            formatPercent(entry.avg_completion),
                            entry.student_count ?? 0
                        ]);
                        config.emptyMessage = 'There are no pending alerts right now.';
                        break;
                    }
                    case 'resolved_alerts': {
                        config.title = 'Alert Resolution Summary';
                        config.columns = ['Teacher', 'Status', 'Resolved Alerts', 'Pending Alerts', 'Completion %'];
                        const filtered = data.filter(entry => (Number(entry.resolved_alerts) || 0) > 0)
                            .sort((a, b) => (Number(b.resolved_alerts) || 0) - (Number(a.resolved_alerts) || 0));
                        config.rows = filtered.map(entry => [
                            entry.name || '-',
                            mapStatusLabel(entry.avg_completion),
                            entry.resolved_alerts ?? 0,
                            entry.pending_alerts ?? 0,
                            formatPercent(entry.avg_completion)
                        ]);
                        config.emptyMessage = 'No alerts have been resolved yet.';
                        break;
                    }
                    default:
                        return;
                }

                renderInsightModal(config);
            }

            // Handle detail view from table rows
            document.querySelectorAll('.clickable-row').forEach(row => {
                row.addEventListener('click', function(e) {
                    if (e.target.type === 'checkbox' || e.target.classList.contains('view-btn')) return;
                    const teacherId = parseInt(this.dataset.teacherId, 10);
                    const teacherName = this.dataset.teacherName;
                    showTeacherDetail(teacherId, teacherName);
                });
            });

            // Handle timetable view
            document.querySelectorAll('.view-timetable').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const teacherId = this.dataset.teacherId;
                    const teacherName = this.dataset.teacherName;
                    document.getElementById('timetableModalTeacherName').innerText = `${teacherName} - Timetables`;

                    document.querySelector('#timetableTable tbody').innerHTML = '<tr><td colspan="3">Loading...</td></tr>';
                    hideAllModals();
                    timetableModal.style.display = 'block';

                    fetch(`?action=get_teacher_timetables&id=${teacherId}`)
                        .then(res => res.json())
                        .then(data => {
                            const timetableTbody = document.querySelector('#timetableTable tbody');
                            timetableTbody.innerHTML = '';
                            if (data.timetables && data.timetables.length) {
                                data.timetables.forEach(t => {
                                    timetableTbody.innerHTML += `
                                        <tr>
                                            <td>${t.file_name}</td>
                                            <td>${t.uploaded_at}</td>
                                            <td><a href="${t.file_path}" target="_blank" class="view-btn">View</a></td>
                                        </tr>`;
                                });
                            } else {
                                timetableTbody.innerHTML = '<tr><td colspan="3">No timetables uploaded.</td></tr>';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching timetables:', error);
                            document.querySelector('#timetableTable tbody').innerHTML = '<tr><td colspan="3">Error loading timetables.</td></tr>';
                        });
                });
            });

            // Handle comparison view
            document.getElementById('compareBtn').addEventListener('click', function() {
                const selected = document.querySelectorAll('.teacher-checkbox:checked');
                if (selected.length < 2) {
                    alert('Please select at least two teachers to compare.');
                    return;
                }

                const labels = Array.from(selected).map(cb => cb.dataset.name);
                const data = Array.from(selected).map(cb => parseFloat(cb.dataset.completion));

                hideAllModals();
                comparisonModal.style.display = 'block';
                const ctx = document.getElementById('comparisonChart').getContext('2d');
                const chartData = {
                    labels: labels,
                    datasets: [{
                        label: 'Average Syllabus Completion %',
                        data: data,
                        backgroundColor: '#A6192E'
                    }]
                };
                if (comparisonChart) {
                    comparisonChart.data = chartData;
                    comparisonChart.update();
                } else {
                    comparisonChart = new Chart(ctx, {
                        type: 'bar',
                        data: chartData,
                        options: {
                            scales: { y: { beginAtZero: true, max: 100 } }
                        }
                    });
                }
            });

            document.getElementById('selectAll').addEventListener('change', function() {
                document.querySelectorAll('.teacher-checkbox').forEach(cb => cb.checked = this.checked);
            });

            // Initialise analytics charts
            if (teacherChartData.ids && teacherChartData.ids.length) {
                const completionCanvas = document.getElementById('teacherCompletionChart');
                if (completionCanvas) {
                    const ctxCompletion = completionCanvas.getContext('2d');
                    completionChart = new Chart(ctxCompletion, {
                        type: 'bar',
                        data: {
                            labels: teacherChartData.names,
                            datasets: [{
                                label: 'Avg Syllabus Completion %',
                                data: teacherChartData.avgCompletion,
                                backgroundColor: '#A6192E'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: { beginAtZero: true, max: 100 }
                            },
                            onClick: (evt, elements) => handleChartClick(elements),
                            plugins: {
                                tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y.toFixed(1)}%` } }
                            }
                        }
                    });
                }

                const studentCanvas = document.getElementById('teacherStudentChart');
                if (studentCanvas) {
                    const ctxStudent = studentCanvas.getContext('2d');
                    studentChart = new Chart(ctxStudent, {
                        data: {
                            labels: teacherChartData.names,
                            datasets: [
                                {
                                    type: 'bar',
                                    label: 'Students',
                                    data: teacherChartData.studentCounts,
                                    backgroundColor: '#1976d2',
                                    order: 2
                                },
                                {
                                    type: 'line',
                                    label: 'Avg ICA %',
                                    data: teacherChartData.avgMarks,
                                    borderColor: '#A6192E',
                                    backgroundColor: '#A6192E',
                                    yAxisID: 'y1',
                                    tension: 0.3,
                                    order: 1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: { beginAtZero: true, title: { display: true, text: 'Students' } },
                                y1: { beginAtZero: true, position: 'right', max: 100, title: { display: true, text: 'Avg ICA %' } }
                            },
                            onClick: (evt, elements) => handleChartClick(elements)
                        }
                    });
                }

                const alertCanvas = document.getElementById('teacherAlertChart');
                if (alertCanvas) {
                    const ctxAlert = alertCanvas.getContext('2d');
                    alertChart = new Chart(ctxAlert, {
                        type: 'bar',
                        data: {
                            labels: teacherChartData.names,
                            datasets: [
                                {
                                    label: 'Pending Alerts',
                                    data: teacherChartData.pendingAlerts,
                                    backgroundColor: '#ffc107'
                                },
                                {
                                    label: 'Resolved Alerts',
                                    data: teacherChartData.resolvedAlerts,
                                    backgroundColor: '#28a745'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: { stacked: true },
                                y: { stacked: true, beginAtZero: true }
                            },
                            onClick: (evt, elements) => handleChartClick(elements)
                        }
                    });
                }
            }

            closeBtns.forEach(btn => {
                btn.onclick = function() {
                    hideAllModals();
                };
            });

            window.onclick = function(event) {
                if (modals.includes(event.target)) {
                    hideAllModals();
                }
            };
        });
    </script>
</body>
</html>
