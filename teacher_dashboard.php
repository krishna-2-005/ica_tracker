<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/academic_context.php';
require_once __DIR__ . '/includes/term_switcher_ui.php';

$teacher_id = (int)$_SESSION['user_id'];
$teacherNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$teacherNameDisplay = $teacherNameRaw !== '' ? format_person_display($teacherNameRaw) : '';

function ensure_column_exists(mysqli $conn, string $table, string $column, string $definition): void {
    $table_safe = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    $column_safe = preg_replace('/[^A-Za-z0-9_]/', '', $column);
    if ($table_safe === '' || $column_safe === '') {
        return;
    }
    $exists_sql = sprintf(
        "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '%s' AND COLUMN_NAME = '%s'",
        mysqli_real_escape_string($conn, $table_safe),
        mysqli_real_escape_string($conn, $column_safe)
    );
    $exists_result = mysqli_query($conn, $exists_sql);
    if ($exists_result) {
        $row = mysqli_fetch_assoc($exists_result);
        mysqli_free_result($exists_result);
        if ((int)($row['cnt'] ?? 0) > 0) {
            return;
        }
    }
    $alter_sql = sprintf("ALTER TABLE `%s` ADD COLUMN `%s` %s", $table_safe, $column_safe, $definition);
    @mysqli_query($conn, $alter_sql);
}

ensure_column_exists($conn, 'syllabus_progress', 'extra_classes', 'DECIMAL(6,2) NOT NULL DEFAULT 0');
ensure_column_exists($conn, 'syllabus_progress', 'actual_theory_hours', 'DECIMAL(6,2) NOT NULL DEFAULT 0');
ensure_column_exists($conn, 'syllabus_progress', 'actual_practical_hours', 'DECIMAL(6,2) NOT NULL DEFAULT 0');
ensure_column_exists($conn, 'syllabus_progress', 'extra_theory_hours', 'DECIMAL(6,2) NOT NULL DEFAULT 0');
ensure_column_exists($conn, 'syllabus_progress', 'extra_practical_hours', 'DECIMAL(6,2) NOT NULL DEFAULT 0');
ensure_column_exists($conn, 'syllabus_progress', 'class_id', 'INT NOT NULL DEFAULT 0');
ensure_column_exists($conn, 'syllabus_progress', 'section_id', 'INT NOT NULL DEFAULT 0');
ensure_column_exists($conn, 'syllabus_progress', 'class_label', "VARCHAR(255) NOT NULL DEFAULT ''");

function ensure_progress_unique_index(mysqli $conn): void {
    $indexName = 'uniq_progress';
    $expectedOrder = ['teacher_id', 'subject', 'class_id', 'section_id', 'timeline'];
    $hasIndex = false;
    $currentOrder = [];
    $indexSql = "SHOW INDEX FROM syllabus_progress WHERE Key_name = '" . $indexName . "'";
    if ($indexResult = mysqli_query($conn, $indexSql)) {
        while ($row = mysqli_fetch_assoc($indexResult)) {
            $hasIndex = true;
            $seq = isset($row['Seq_in_index']) ? (int)$row['Seq_in_index'] : 0;
            $currentOrder[$seq] = $row['Column_name'] ?? '';
        }
        mysqli_free_result($indexResult);
    }

    ksort($currentOrder);
    $orderedColumns = array_values(array_filter($currentOrder, static fn($value) => $value !== ''));
    if ($orderedColumns === $expectedOrder) {
        return;
    }

    if ($hasIndex) {
        @mysqli_query($conn, "ALTER TABLE syllabus_progress DROP INDEX `{$indexName}`");
    }
    @mysqli_query($conn, "ALTER TABLE syllabus_progress ADD UNIQUE KEY `{$indexName}` (`teacher_id`,`subject`,`class_id`,`section_id`,`timeline`)");
}

ensure_progress_unique_index($conn);

if (!function_exists('build_assignment_key')) {
    function build_assignment_key(string $subjectName, int $classId, ?int $sectionId = null): string {
        $payload = json_encode([
            'subject' => $subjectName,
            'class_id' => $classId,
            'section_id' => $sectionId !== null ? (int)$sectionId : 0
        ], JSON_UNESCAPED_UNICODE);
        if ($payload === false) {
            return '';
        }
        $encoded = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        return $encoded;
    }
}

$teacherSchool = '';
$user_school_query = "SELECT school FROM users WHERE id = ?";
$stmt_school = mysqli_prepare($conn, $user_school_query);
if ($stmt_school) {
    mysqli_stmt_bind_param($stmt_school, "i", $teacher_id);
    mysqli_stmt_execute($stmt_school);
    $school_result = mysqli_stmt_get_result($stmt_school);
    if ($school_row = mysqli_fetch_assoc($school_result)) {
        $teacherSchool = trim((string)($school_row['school'] ?? ''));
    }
    mysqli_stmt_close($stmt_school);
}

$academicContext = resolveAcademicContext($conn, [
    'school_name' => $teacherSchool
]);
$activeTerm = $academicContext['active'] ?? null;
$activeTermId = $activeTerm && isset($activeTerm['id']) ? (int)$activeTerm['id'] : 0;
$termDateFilter = $academicContext['date_filter'] ?? null;
$termStartDate = $termDateFilter['start'] ?? null;
$termEndDate = $termDateFilter['end'] ?? null;
$termStartBound = $termStartDate ? $termStartDate . ' 00:00:00' : null;
$termEndBound = $termEndDate ? $termEndDate . ' 23:59:59' : null;

$week_number_display = $termStartDate ? 'Not started' : 'Timeline not linked';
if ($termStartDate && $termEndDate) {
    $start_date = new DateTime($termStartDate);
    $end_date = new DateTime($termEndDate);
    $today = new DateTime('today');
    if ($today < $start_date) {
        $week_number_display = 'Not started';
    } elseif ($today > $end_date) {
        $week_number_display = 'Term completed';
    } else {
        $interval = $start_date->diff($today);
        $days_passed = max(0, $interval->days);
        $current_week = (int)floor($days_passed / 7) + 1;
        $week_number_display = "Week " . $current_week;
    }
}

// --- START: FILTER HANDLING ---
$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Fetch up to 5 pending alerts for quick display
$alerts_query = "SELECT id, message, created_at FROM alerts WHERE teacher_id = ? AND status = 'pending'";
if ($termStartBound && $termEndBound) {
    $alerts_query .= " AND created_at BETWEEN ? AND ?";
}
$alerts_query .= " ORDER BY created_at DESC LIMIT 5";
$stmt_alerts = mysqli_prepare($conn, $alerts_query);
if ($termStartBound && $termEndBound) {
    mysqli_stmt_bind_param($stmt_alerts, "iss", $teacher_id, $termStartBound, $termEndBound);
} else {
    mysqli_stmt_bind_param($stmt_alerts, "i", $teacher_id);
}
mysqli_stmt_execute($stmt_alerts);
$alerts_result = mysqli_stmt_get_result($stmt_alerts);
$pending_alerts = [];
while ($alert_row = mysqli_fetch_assoc($alerts_result)) {
    $pending_alerts[] = $alert_row;
}
mysqli_stmt_close($stmt_alerts);

// Fetch classes for the filter dropdown
$filter_classes = [];
$filter_classes_query = "SELECT c.id,
        c.class_name,
        c.semester,
        c.school,
        COALESCE(GROUP_CONCAT(DISTINCT sec.section_name ORDER BY sec.section_name SEPARATOR '/'), '') AS divisions
    FROM classes c
    JOIN teacher_subject_assignments tsa ON c.id = tsa.class_id
    LEFT JOIN sections sec ON sec.id = tsa.section_id
    WHERE tsa.teacher_id = ?";
if ($activeTermId > 0) {
    $filter_classes_query .= " AND c.academic_term_id = ?";
}
$filter_classes_query .= " GROUP BY c.id, c.class_name, c.semester, c.school ORDER BY c.class_name";
$stmt_filter_classes = mysqli_prepare($conn, $filter_classes_query);
if ($stmt_filter_classes) {
    if ($activeTermId > 0) {
        mysqli_stmt_bind_param($stmt_filter_classes, "ii", $teacher_id, $activeTermId);
    } else {
        mysqli_stmt_bind_param($stmt_filter_classes, "i", $teacher_id);
    }
    mysqli_stmt_execute($stmt_filter_classes);
    $filter_classes_result = mysqli_stmt_get_result($stmt_filter_classes);
    if ($filter_classes_result) {
        while ($row = mysqli_fetch_assoc($filter_classes_result)) {
            $classLabel = format_class_label(
                $row['class_name'] ?? '',
                $row['divisions'] ?? '',
                $row['semester'] ?? '',
                $row['school'] ?? ''
            );
            $filter_classes[] = [
                'id' => (int)$row['id'],
                'class_name' => $classLabel !== '' ? $classLabel : format_subject_display($row['class_name'] ?? '')
            ];
        }
        mysqli_free_result($filter_classes_result);
    }
    mysqli_stmt_close($stmt_filter_classes);
}

if (empty($filter_classes)) {
    $fallback_classes_query = "SELECT c.id,
            c.class_name,
            c.semester,
            c.school,
            COALESCE(GROUP_CONCAT(DISTINCT sec.section_name ORDER BY sec.section_name SEPARATOR '/'), '') AS divisions
        FROM classes c
        JOIN teacher_classes tc ON c.id = tc.class_id
        LEFT JOIN sections sec ON sec.class_id = c.id
        WHERE tc.teacher_id = ?";
    if ($activeTermId > 0) {
        $fallback_classes_query .= " AND c.academic_term_id = ?";
    }
    $fallback_classes_query .= " GROUP BY c.id, c.class_name, c.semester, c.school ORDER BY c.class_name";
    $stmt_fallback_classes = mysqli_prepare($conn, $fallback_classes_query);
    if ($stmt_fallback_classes) {
        if ($activeTermId > 0) {
            mysqli_stmt_bind_param($stmt_fallback_classes, "ii", $teacher_id, $activeTermId);
        } else {
            mysqli_stmt_bind_param($stmt_fallback_classes, "i", $teacher_id);
        }
        mysqli_stmt_execute($stmt_fallback_classes);
        $fallback_classes_result = mysqli_stmt_get_result($stmt_fallback_classes);
        if ($fallback_classes_result) {
            while ($row = mysqli_fetch_assoc($fallback_classes_result)) {
                $fallbackLabel = format_class_label(
                    $row['class_name'] ?? '',
                    $row['divisions'] ?? '',
                    $row['semester'] ?? '',
                    $row['school'] ?? ''
                );
                $filter_classes[] = [
                    'id' => (int)$row['id'],
                    'class_name' => $fallbackLabel !== '' ? $fallbackLabel : format_subject_display($row['class_name'] ?? '')
                ];
            }
            mysqli_free_result($fallback_classes_result);
        }
        mysqli_stmt_close($stmt_fallback_classes);
    }
}
// --- END: FILTER HANDLING ---


// --- DATA FOR OVERVIEW CARDS ---
$assignmentRows = [];
$assignmentsSql = "
    SELECT
        tsa.id AS assignment_id,
        tsa.section_id,
        s.id AS subject_id,
        s.subject_name,
        s.total_planned_hours,
        COALESCE(sd.subject_type, 'regular') AS subject_type,
        COALESCE(sd.theory_hours, 0) AS theory_hours,
        COALESCE(sd.practical_hours, 0) AS practical_hours,
        COALESCE(sd.tutorial_label, 'Practical') AS practical_label,
        c.id AS class_id,
        c.class_name,
        c.semester,
        c.school,
        COALESCE(sec.section_name, '') AS section_name
    FROM teacher_subject_assignments tsa
    JOIN classes c ON c.id = tsa.class_id
    LEFT JOIN sections sec ON sec.id = tsa.section_id
    LEFT JOIN subjects s ON s.id = tsa.subject_id
    LEFT JOIN subject_details sd ON sd.subject_id = s.id
    WHERE tsa.teacher_id = ?
";
if ($activeTermId > 0) {
    $assignmentsSql .= " AND c.academic_term_id = ?";
}
$assignmentsSql .= " ORDER BY s.subject_name, c.class_name, sec.section_name";

$stmtAssignments = mysqli_prepare($conn, $assignmentsSql);
if ($stmtAssignments) {
    if ($activeTermId > 0) {
        mysqli_stmt_bind_param($stmtAssignments, "ii", $teacher_id, $activeTermId);
    } else {
        mysqli_stmt_bind_param($stmtAssignments, "i", $teacher_id);
    }
    mysqli_stmt_execute($stmtAssignments);
    $assignmentsResult = mysqli_stmt_get_result($stmtAssignments);
    if ($assignmentsResult) {
        while ($row = mysqli_fetch_assoc($assignmentsResult)) {
            $assignmentRows[] = $row;
        }
        mysqli_free_result($assignmentsResult);
    }
    mysqli_stmt_close($stmtAssignments);
}

$subjects = [];
$assignmentMeta = [];
$uniqueSubjectKeys = [];
$assignmentKeyBySubject = [];
$assignmentKeyByName = [];

foreach ($assignmentRows as $row) {
    $subjectId = isset($row['subject_id']) ? (int)$row['subject_id'] : 0;
    $subjectNameRaw = isset($row['subject_name']) ? trim((string)$row['subject_name']) : '';
    if ($subjectNameRaw === '') {
        continue;
    }

    $subjectDisplay = format_subject_display($subjectNameRaw);
    $theoryHours = (int)($row['theory_hours'] ?? 0);
    $practicalHours = (int)($row['practical_hours'] ?? 0);
    $totalPlanned = (int)($row['total_planned_hours'] ?? 0);
    if ($totalPlanned <= 0 && ($theoryHours > 0 || $practicalHours > 0)) {
        $totalPlanned = $theoryHours + $practicalHours;
    }

    $practicalLabel = isset($row['practical_label']) && $row['practical_label'] !== ''
        ? (string)$row['practical_label']
        : 'Practical';

    $classId = isset($row['class_id']) ? (int)$row['class_id'] : 0;
    $sectionId = isset($row['section_id']) ? (int)$row['section_id'] : 0;
    $classNameRaw = isset($row['class_name']) ? trim((string)$row['class_name']) : '';
    $sectionName = isset($row['section_name']) ? trim((string)$row['section_name']) : '';
    $semesterLabel = isset($row['semester']) ? trim((string)$row['semester']) : '';
    $schoolLabel = isset($row['school']) ? trim((string)$row['school']) : '';
    $classLabel = format_class_label($classNameRaw, $sectionName, $semesterLabel, $schoolLabel);
    if ($classLabel === '') {
        $classLabel = $classNameRaw !== '' ? format_subject_display($classNameRaw) : 'Class assignment pending';
    }

    $assignmentKey = build_assignment_key($subjectNameRaw, $classId, $sectionId);

    $subjects[] = [
        'assignment_key' => $assignmentKey,
        'assignment_id' => isset($row['assignment_id']) ? (int)$row['assignment_id'] : 0,
        'subject_id' => $subjectId,
        'subject_name' => $subjectNameRaw,
        'subject_name_display' => $subjectDisplay,
        'total_planned_hours' => $totalPlanned,
        'subject_type' => strtolower($row['subject_type'] ?? 'regular'),
        'theory_hours' => $theoryHours,
        'practical_hours' => $practicalHours,
        'practical_label' => $practicalLabel,
        'class_names' => $classLabel,
        'class_label' => $classLabel,
        'class_id' => $classId,
        'section_id' => $sectionId,
        'school' => $schoolLabel,
        'semester' => $semesterLabel,
        'has_assignment' => true
    ];

    $assignmentMeta[$assignmentKey] = [
        'subject_id' => $subjectId,
        'subject_name' => $subjectNameRaw,
        'class_id' => $classId,
        'section_id' => $sectionId,
        'class_label' => $classLabel,
        'school' => $schoolLabel,
        'semester' => $semesterLabel,
        'subject_type' => strtolower($row['subject_type'] ?? 'regular'),
        'theory_hours' => $theoryHours,
        'practical_hours' => $practicalHours,
        'total_planned_hours' => $totalPlanned,
        'practical_label' => $practicalLabel
    ];

    $classSectionKey = $classId . ':' . $sectionId;
    if ($subjectId > 0) {
        if (!isset($assignmentKeyBySubject[$subjectId])) {
            $assignmentKeyBySubject[$subjectId] = [];
        }
        $assignmentKeyBySubject[$subjectId][$classSectionKey] = $assignmentKey;
    }

    $nameKey = strtolower($subjectNameRaw);
    if ($nameKey !== '') {
        if (!isset($assignmentKeyByName[$nameKey])) {
            $assignmentKeyByName[$nameKey] = [];
        }
        $assignmentKeyByName[$nameKey][$classSectionKey] = $assignmentKey;
    }

    $uniqueKey = $subjectId > 0 ? 'id:' . $subjectId : 'name:' . strtolower($subjectNameRaw);
    $uniqueSubjectKeys[$uniqueKey] = true;
}

if (!empty($subjects)) {
    usort($subjects, static function (array $a, array $b): int {
        $subjectCompare = strcasecmp($a['subject_name_display'] ?? $a['subject_name'], $b['subject_name_display'] ?? $b['subject_name']);
        if ($subjectCompare !== 0) {
            return $subjectCompare;
        }
        return strcasecmp($a['class_label'] ?? '', $b['class_label'] ?? '');
    });
}

$assigned_subjects_count = !empty($uniqueSubjectKeys) ? count($uniqueSubjectKeys) : count($subjects);
$subjectAssignmentMeta = $assignmentMeta;
$assignmentKeyBySubjectId = $assignmentKeyBySubject;
$assignmentKeyBySubjectName = $assignmentKeyByName;

// 2. Fetch count of assigned class/division combinations
$classes_count_query = "
    SELECT COUNT(DISTINCT CONCAT(tsa.class_id, ':', COALESCE(tsa.section_id, 0))) AS class_count
    FROM teacher_subject_assignments tsa
    JOIN classes c ON c.id = tsa.class_id
    WHERE tsa.teacher_id = ?";
if ($activeTermId > 0) {
    $classes_count_query .= " AND c.academic_term_id = ?";
}
$stmt_classes = mysqli_prepare($conn, $classes_count_query);
if ($activeTermId > 0) {
    mysqli_stmt_bind_param($stmt_classes, "ii", $teacher_id, $activeTermId);
} else {
    mysqli_stmt_bind_param($stmt_classes, "i", $teacher_id);
}
mysqli_stmt_execute($stmt_classes);
$classes_count_result = mysqli_stmt_get_result($stmt_classes);
$assigned_classes_row = mysqli_fetch_assoc($classes_count_result);
$assigned_classes_count = isset($assigned_classes_row['class_count']) ? (int)$assigned_classes_row['class_count'] : 0;
mysqli_free_result($classes_count_result);
mysqli_stmt_close($stmt_classes);

if ($assigned_classes_count === 0) {
    $fallback_class_count_query = "SELECT COUNT(DISTINCT tc.class_id) AS class_count FROM teacher_classes tc JOIN classes c ON c.id = tc.class_id WHERE tc.teacher_id = ?";
    if ($activeTermId > 0) {
        $fallback_class_count_query .= " AND c.academic_term_id = ?";
    }
    $stmt_fallback_class_count = mysqli_prepare($conn, $fallback_class_count_query);
    if ($stmt_fallback_class_count) {
        if ($activeTermId > 0) {
            mysqli_stmt_bind_param($stmt_fallback_class_count, "ii", $teacher_id, $activeTermId);
        } else {
            mysqli_stmt_bind_param($stmt_fallback_class_count, "i", $teacher_id);
        }
        mysqli_stmt_execute($stmt_fallback_class_count);
        $fallback_class_count_result = mysqli_stmt_get_result($stmt_fallback_class_count);
        if ($fallback_class_count_result) {
            $fallback_class_row = mysqli_fetch_assoc($fallback_class_count_result);
            if ($fallback_class_row && isset($fallback_class_row['class_count'])) {
                $assigned_classes_count = (int)$fallback_class_row['class_count'];
            }
            mysqli_free_result($fallback_class_count_result);
        }
        mysqli_stmt_close($stmt_fallback_class_count);
    }
}

// --- DATA FOR DETAILS PANEL ---
$progress_query = "SELECT subject,
                          class_id,
                          section_id,
                          class_label,
                          timeline,
                          topic,
                          planned_hours,
                          actual_hours,
                          actual_theory_hours,
                          actual_practical_hours,
                          extra_theory_hours,
                          extra_practical_hours,
                          extra_classes,
                          completion_percentage,
                          updated_at 
                   FROM syllabus_progress WHERE teacher_id = ?";
if ($termStartBound && $termEndBound) {
    $progress_query .= " AND updated_at BETWEEN ? AND ?";
}
$progress_query .= " ORDER BY subject, class_id, section_id, updated_at DESC";
$stmt_progress = mysqli_prepare($conn, $progress_query);
if ($termStartBound && $termEndBound) {
    mysqli_stmt_bind_param($stmt_progress, "iss", $teacher_id, $termStartBound, $termEndBound);
} else {
    mysqli_stmt_bind_param($stmt_progress, "i", $teacher_id);
}
mysqli_stmt_execute($stmt_progress);
$progress_result = mysqli_stmt_get_result($stmt_progress);
$progress_by_subject = [];
while ($row = mysqli_fetch_assoc($progress_result)) {
    $subjectName = isset($row['subject']) ? trim((string)$row['subject']) : '';
    if ($subjectName === '') {
        continue;
    }
    $classId = isset($row['class_id']) ? (int)$row['class_id'] : 0;
    $sectionId = isset($row['section_id']) ? (int)$row['section_id'] : 0;
    $classSectionKey = $classId . ':' . $sectionId;
    $normalizedName = strtolower($subjectName);

    $assignmentKey = null;
    if (isset($assignmentKeyBySubjectName[$normalizedName])) {
        $lookup = $assignmentKeyBySubjectName[$normalizedName];
        if ($classId > 0 && isset($lookup[$classSectionKey])) {
            $assignmentKey = $lookup[$classSectionKey];
        } elseif ($classId > 0 && isset($lookup['0:0'])) {
            $assignmentKey = $lookup['0:0'];
        } else {
            $firstKey = reset($lookup);
            if (is_string($firstKey)) {
                $assignmentKey = $firstKey;
            }
        }
    }

    $progressRow = [
        'subject_name' => $subjectName,
        'class_id' => $classId,
        'section_id' => $sectionId,
        'class_label' => isset($row['class_label']) && $row['class_label'] !== ''
            ? (string)$row['class_label']
            : ($assignmentMeta[$assignmentKey]['class_label'] ?? ''),
        'timeline' => $row['timeline'],
        'topic' => $row['topic'] ?? '',
        'planned_hours' => isset($row['planned_hours']) ? (float)$row['planned_hours'] : 0.0,
        'actual_hours' => isset($row['actual_hours']) ? (float)$row['actual_hours'] : 0.0,
        'actual_theory_hours' => isset($row['actual_theory_hours']) ? (float)$row['actual_theory_hours'] : 0.0,
        'actual_practical_hours' => isset($row['actual_practical_hours']) ? (float)$row['actual_practical_hours'] : 0.0,
        'extra_theory_hours' => isset($row['extra_theory_hours']) ? (float)$row['extra_theory_hours'] : 0.0,
        'extra_practical_hours' => isset($row['extra_practical_hours']) ? (float)$row['extra_practical_hours'] : 0.0,
        'extra_classes' => isset($row['extra_classes']) ? (float)$row['extra_classes'] : 0.0,
        'completion_percentage' => isset($row['completion_percentage']) ? (float)$row['completion_percentage'] : 0.0,
        'updated_at' => $row['updated_at']
    ];

    if ($assignmentKey !== null && !isset($progress_by_subject[$assignmentKey])) {
        $progress_by_subject[$assignmentKey] = $progressRow;
    }

    $legacyKey = 'legacy|' . $normalizedName;
    if (!isset($progress_by_subject[$legacyKey])) {
        $progress_by_subject[$legacyKey] = $progressRow;
    }
}
mysqli_stmt_close($stmt_progress);


// --- START: DATA FOR GRAPHS (MODIFIED WITH FILTERS) ---

// 1. Syllabus Progress Chart Data
$syllabus_chart_query = "SELECT 
                            s.subject_name AS subject,
                            c.class_name,
                            c.semester,
                            c.school,
                            COALESCE(sec.section_name, '') AS section_name,
                            MAX(CASE WHEN sp.timeline LIKE 'week_%' AND CAST(SUBSTRING_INDEX(sp.timeline, '_', -1) AS UNSIGNED) BETWEEN 1 AND 5 THEN sp.completion_percentage END) AS week5_completion,
                            MAX(CASE WHEN sp.timeline LIKE 'week_%' AND CAST(SUBSTRING_INDEX(sp.timeline, '_', -1) AS UNSIGNED) BETWEEN 6 AND 10 THEN sp.completion_percentage END) AS week10_completion,
                            MAX(CASE WHEN sp.timeline = 'final' THEN sp.completion_percentage END) AS final_checkpoint
                        FROM teacher_subject_assignments tsa
                        JOIN subjects s ON s.id = tsa.subject_id
                        LEFT JOIN classes c ON c.id = tsa.class_id
                        LEFT JOIN sections sec ON sec.id = tsa.section_id
                        LEFT JOIN syllabus_progress sp ON sp.subject = s.subject_name AND sp.teacher_id = tsa.teacher_id AND sp.class_id = c.id AND sp.section_id = COALESCE(tsa.section_id, 0)
                        WHERE tsa.teacher_id = ?";
if ($selected_class > 0) {
    $syllabus_chart_query .= " AND c.id = ?";
    }
    if ($termStartBound && $termEndBound) {
        $syllabus_chart_query .= " AND sp.updated_at BETWEEN ? AND ?";
}
$syllabus_chart_query .= " GROUP BY s.id, s.subject_name, c.id, c.class_name, c.semester, c.school, section_name ORDER BY s.subject_name, c.class_name";

$stmt_syllabus_chart = mysqli_prepare($conn, $syllabus_chart_query);
if ($selected_class > 0) {
    if ($termStartBound && $termEndBound) {
        mysqli_stmt_bind_param($stmt_syllabus_chart, "iiss", $teacher_id, $selected_class, $termStartBound, $termEndBound);
    } else {
        mysqli_stmt_bind_param($stmt_syllabus_chart, "ii", $teacher_id, $selected_class);
    }
} else {
    if ($termStartBound && $termEndBound) {
        mysqli_stmt_bind_param($stmt_syllabus_chart, "iss", $teacher_id, $termStartBound, $termEndBound);
    } else {
        mysqli_stmt_bind_param($stmt_syllabus_chart, "i", $teacher_id);
    }
}
mysqli_stmt_execute($stmt_syllabus_chart);
$syllabus_chart_result = mysqli_stmt_get_result($stmt_syllabus_chart);
$syllabus_chart_data = [];
while ($row = mysqli_fetch_assoc($syllabus_chart_result)) {
    $subjectLabel = isset($row['subject']) ? trim((string)$row['subject']) : '';
    $classLabel = format_class_label(
        $row['class_name'] ?? '',
        $row['section_name'] ?? '',
        $row['semester'] ?? '',
        $row['school'] ?? ''
    );
    $chartLabel = format_subject_display($subjectLabel);
    if ($classLabel !== '') {
        $chartLabel .= ' — ' . $classLabel;
    }
    $syllabus_chart_data[] = [
        'subject' => $subjectLabel,
        'subject_display' => $chartLabel,
        'week5' => isset($row['week5_completion']) ? (int)$row['week5_completion'] : null,
        'week10' => isset($row['week10_completion']) ? (int)$row['week10_completion'] : null,
        'final' => isset($row['final_checkpoint']) ? (int)$row['final_checkpoint'] : null
    ];
}
mysqli_stmt_close($stmt_syllabus_chart);


// 2. Mid Marks Chart Data
$mid_marks_query = "SELECT s.subject_name,
                        AVG(CASE WHEN ic.component_name LIKE '%Mid Exam%' THEN (ism.marks / ic.marks_per_instance) * 100 ELSE NULL END) as mid_avg
                    FROM ica_student_marks ism
                    JOIN ica_components ic ON ism.component_id = ic.id
                    JOIN subjects s ON ic.subject_id = s.id";
if ($selected_class > 0) {
    $mid_marks_query .= " JOIN classes c ON s.semester = c.semester AND s.school = c.school";
}
$mid_marks_query .= " WHERE ism.teacher_id = ? AND ic.marks_per_instance > 0 AND ic.component_name LIKE '%Mid Exam%'";
if ($termStartBound && $termEndBound) {
    $mid_marks_query .= " AND ism.updated_at BETWEEN ? AND ?";
}
if ($selected_class > 0) {
    $mid_marks_query .= " AND c.id = ?";
}
$mid_marks_query .= " GROUP BY s.id, s.subject_name";

$stmt_mid_marks = mysqli_prepare($conn, $mid_marks_query);
if ($selected_class > 0) {
    if ($termStartBound && $termEndBound) {
        mysqli_stmt_bind_param($stmt_mid_marks, "iiss", $teacher_id, $termStartBound, $termEndBound, $selected_class);
    } else {
        mysqli_stmt_bind_param($stmt_mid_marks, "ii", $teacher_id, $selected_class);
    }
} else {
    if ($termStartBound && $termEndBound) {
        mysqli_stmt_bind_param($stmt_mid_marks, "iss", $teacher_id, $termStartBound, $termEndBound);
    } else {
        mysqli_stmt_bind_param($stmt_mid_marks, "i", $teacher_id);
    }
}
mysqli_stmt_execute($stmt_mid_marks);
$mid_marks_result = mysqli_stmt_get_result($stmt_mid_marks);
$mid_marks_data = [];
while ($row = mysqli_fetch_assoc($mid_marks_result)) {
    $subjectLabel = isset($row['subject_name']) ? trim((string)$row['subject_name']) : '';
    $mid_marks_data[] = [
        'subject_name' => $subjectLabel,
        'subject_name_display' => format_subject_display($subjectLabel),
        'mid_avg' => isset($row['mid_avg']) ? (float)$row['mid_avg'] : null
    ];
}
mysqli_stmt_close($stmt_mid_marks);
// --- END: DATA FOR GRAPHS ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .overview-card {
            background-color: #fff;
            border-radius: 12px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .overview-card i {
            font-size: 2.5rem;
            padding: 15px;
            border-radius: 50%;
            color: #fff;
        }
        .overview-card .card-value {
            font-size: 2rem;
            font-weight: bold;
        }
        .overview-card .card-label {
            font-size: 1rem;
            color: #666;
        }

        .subject-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }
        .subject-card {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            border: 1px solid #e0e0e0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .subject-card:hover, .subject-card.active {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(166, 25, 46, 0.1);
            border-color: #A6192E;
        }
        .subject-card i { font-size: 2rem; color: #A6192E; margin-bottom: 10px; }
        .subject-card .subject-name { font-size: 1.2rem; font-weight: 600; margin-bottom: 5px; }
        .subject-card .subject-hours { font-size: 0.9rem; color: #666; display: block; }
        .subject-card .subject-classes { font-size: 0.85rem; color: #444; margin-top: 6px; display: block; }
        .subject-card .subject-classes--empty { color: #b35c5c; }
        .subject-card .subject-class-meta { font-size: 0.75rem; color: #777; margin-top: 2px; display: block; }
    .subject-card .subject-type-badge { display:inline-block; padding:4px 10px; border-radius:12px; background:#fff0f4; color:#b10024; font-size:0.75rem; font-weight:600; margin-bottom:6px; }
        .progress-summary { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin:18px 0; }
        .progress-summary .summary-card { background-color:#ffffff; border:1px solid #dedede; border-radius:10px; padding:16px; text-align:left; }
        .summary-card .summary-label { font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; color:#777; }
        .summary-card .summary-value { font-size:1.35rem; font-weight:600; color:#A6192E; }
        .summary-card .summary-subtext { font-size:0.85rem; color:#4a4a4a; margin-top:4px; }
        .progress-status { margin:12px 0; padding:10px 12px; border-radius:8px; font-size:0.95rem; }
        .progress-status.status-positive { background:#e9f7ef; color:#1e7c3a; border:1px solid #c8ecd6; }
        .progress-status.status-negative { background:#fff5f5; color:#b10024; border:1px solid #f3d4da; }
        .progress-status.status-neutral { background:#f0f4f7; color:#2c3e50; border:1px solid #d6e0ea; }
        
        #subject-details-panel { display: none; margin-top: 20px; padding: 20px; border-radius: 12px; background-color: #f8f9fa; }
        .details-header { font-size: 1.5rem; font-weight: 600; color: #A6192E; margin-bottom: 15px; }
    .subject-meta { margin-bottom: 8px; font-size: 0.95rem; color: #444; }
        .details-actions { margin-top: 20px; display: flex; gap: 10px; }

        .alerts-card {
            background-color: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .alerts-card h5 {
            margin-bottom: 12px;
            font-weight: 600;
            color: #A6192E;
        }
        .alerts-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .alerts-list li {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 12px 14px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 12px;
            background-color: #fafafa;
        }
        .alerts-list li:last-child { margin-bottom: 0; }
        .alert-message { font-weight: 500; color: #333; }
        .alert-meta { font-size: 0.85rem; color: #777; }
        body.dark-mode .alerts-card { background-color: #5a5a5a; }
        body.dark-mode .alerts-list li { background-color: #4a4a4a; border-color: #777; color: #f2f2f2; }

        body.dark-mode .overview-card { background-color: #5a5a5a; }
        body.dark-mode .overview-card .card-label { color: #ccc; }
        body.dark-mode .subject-card { background-color: #5a5a5a; border-color: #777; color: #FFFFFF; }
        body.dark-mode .subject-card .subject-hours { color: #ccc; }
    body.dark-mode .subject-card .subject-classes { color: #e0e0e0; }
    body.dark-mode .subject-card .subject-classes--empty { color: #f4c2c2; }
    body.dark-mode .subject-card .subject-class-meta { color:#c8c8c8; }
    body.dark-mode .subject-card .subject-type-badge { background:#4a1a25; color:#ffd6e0; }
    body.dark-mode #subject-details-panel { background-color: #4a4a4a; }
    body.dark-mode .subject-meta { color: #f0f0f0; }
    body.dark-mode .progress-summary .summary-card { background-color:#5a5a5a; border-color:#777; color:#f0f0f0; }
    body.dark-mode .summary-card .summary-label { color:#ddd; }
    body.dark-mode .summary-card .summary-subtext { color:#e0e0e0; }
    body.dark-mode .progress-status.status-neutral { background:#3f4a55; color:#f0f0f0; border-color:#5a6673; }
    body.dark-mode .progress-status.status-positive { background:#2f5141; color:#d8f5e3; border-color:#3f7157; }
    body.dark-mode .progress-status.status-negative { background:#5a2a31; color:#ffd7de; border-color:#7a3a45; }

        /* START: Modified styles for charts and filters */
        .chart-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }
        .card-header form {
            margin: 0; padding: 0; box-shadow: none; background: none;
        }
        .card-header .form-group {
            display: flex; align-items: center; gap: 8px; margin-bottom: 0;
        }
        .card-header label {
            margin-bottom: 0; font-size: 0.9em; font-weight: normal; white-space: nowrap;
        }
        .card-header select {
            padding: 6px 10px; font-size: 0.9em; border-radius: 6px; margin-bottom: 0;
        }
        @media (max-width: 992px) {
            .chart-grid {
                grid-template-columns: 1fr; /* Stack charts on smaller screens */
            }
        }
        /* END: Modified styles */
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h2>ICA Tracker</h2>
            <a href="teacher_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="update_progress.php"><i class="fas fa-chart-line"></i> <span>Update Progress</span></a>
            <a href="create_ica_components.php"><i class="fas fa-cogs"></i> <span>ICA Components</span></a>
            <a href="manage_ica_marks.php"><i class="fas fa-book"></i> <span>Manage ICA Marks</span></a>
           <a href="assignments.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a>
            <a href="view_alerts.php"><i class="fas fa-bell"></i> <span>View Alerts</span></a>
            <a href="view_reports.php"><i class="fas fa-file-alt"></i> <span>View Reports</span></a>
            <a href="timetable.php"><i class="fas fa-calendar-alt"></i> <span>Timetable</span></a>
            <a href="edit_profile.php"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
        <div class="main-content">
            <div class="header">
                <h2>Welcome, <?php echo htmlspecialchars($teacherNameDisplay !== '' ? $teacherNameDisplay : $teacherNameRaw); ?>!</h2>
            </div>
            <?php renderTermSwitcher($academicContext, ['school_name' => $teacherSchool]); ?>
            <div class="container">
                <div class="overview-grid">
                    <div class="overview-card">
                        <i class="fas fa-book" style="background-color: #A6192E;"></i>
                        <div>
                            <div class="card-value"><?php echo $assigned_subjects_count; ?></div>
                            <div class="card-label">Assigned Subjects</div>
                        </div>
                    </div>
                    <div class="overview-card">
                        <i class="fas fa-users" style="background-color: #A6192E;"></i>
                        <div>
                            <div class="card-value"><?php echo $assigned_classes_count; ?></div>
                            <div class="card-label">Assigned Classes</div>
                        </div>
                    </div>
                    <div class="overview-card">
                        <i class="fas fa-calendar-week" style="background-color: #A6192E;"></i>
                        <div>
                            <div class="card-value"><?php echo $week_number_display; ?></div>
                            <div class="card-label">Current Academic Week</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5>Assigned Subjects</h5></div>
                    <div class="card-body">
                        <?php if (empty($subjects)): ?>
                            <p>No subjects have been assigned to you yet.</p>
                        <?php else: ?>
                            <p style="text-align: center; color: #666; font-size: 0.9em; margin-bottom: 20px;">
                                Click on a subject card to view recent updates and manage details.
                            </p>
                            <div class="subject-grid">
                                <?php foreach ($subjects as $subject): ?>
                                     <div class="subject-card"
                                         data-assignment-key="<?php echo htmlspecialchars($subject['assignment_key']); ?>"
                                         data-subject-name="<?php echo htmlspecialchars($subject['subject_name']); ?>"
                                         data-subject-name-display="<?php echo htmlspecialchars($subject['subject_name_display'] ?? $subject['subject_name']); ?>"
                                         data-class-label="<?php echo htmlspecialchars($subject['class_label'] ?? ''); ?>"
                                         data-class-id="<?php echo (int)($subject['class_id'] ?? 0); ?>"
                                         data-section-id="<?php echo (int)($subject['section_id'] ?? 0); ?>"
                                         data-school="<?php echo htmlspecialchars($subject['school'] ?? ''); ?>"
                                         data-semester="<?php echo htmlspecialchars($subject['semester'] ?? ''); ?>"
                                         data-total-hours="<?php echo (int)$subject['total_planned_hours']; ?>"
                                         data-theory-hours="<?php echo (int)$subject['theory_hours']; ?>"
                                         data-practical-hours="<?php echo (int)$subject['practical_hours']; ?>"
                                         data-practical-label="<?php echo htmlspecialchars($subject['practical_label'] ?? 'Practical'); ?>"
                                         data-subject-type="<?php echo htmlspecialchars($subject['subject_type']); ?>"
                                         data-assignment-note="<?php echo htmlspecialchars(!empty($subject['has_assignment']) ? '' : 'Class link missing - ask admin to reassign this subject.'); ?>">
                                        <i class="fas fa-book-open"></i>
                                        <?php if ($subject['subject_type'] === 'elective'): ?>
                                            <span class="subject-type-badge">Elective</span>
                                        <?php endif; ?>
                                        <span class="subject-name"><?php echo htmlspecialchars($subject['subject_name_display'] ?? $subject['subject_name']); ?></span>
                                        <span class="subject-hours"><?php echo (int)$subject['theory_hours']; ?> theory / <?php echo (int)$subject['practical_hours']; ?> <?php echo htmlspecialchars($subject['practical_label'] ?? 'Practical'); ?> (<?php echo (int)$subject['total_planned_hours']; ?> total)</span>
                                        <?php if (!empty($subject['class_label'])): ?>
                                            <span class="subject-classes"><?php echo htmlspecialchars($subject['class_label']); ?></span>
                                            <?php if (!empty($subject['school']) || !empty($subject['semester'])): ?>
                                                <span class="subject-class-meta"><?php echo htmlspecialchars(trim(($subject['school'] ?? '') . (isset($subject['school'], $subject['semester']) && $subject['school'] && $subject['semester'] ? ' • ' : '') . ($subject['semester'] ?? ''))); ?></span>
                                            <?php endif; ?>
                                        <?php elseif (isset($subject['has_assignment']) && !$subject['has_assignment']): ?>
                                            <span class="subject-classes subject-classes--empty">Class link missing - ask admin to reassign this subject.</span>
                                        <?php else: ?>
                                            <span class="subject-classes subject-classes--empty">Class assignment pending</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="subject-details-panel" class="card">
                </div>

                <div class="chart-grid">
                    <div class="card">
                        <div class="card-header">
                            <h5>Syllabus Progress (%)</h5>
                            <form method="GET" id="filterForm">
                                <div class="form-group">
                                    <label>Filter by Class:</label>
                                    <select name="class_id" onchange="this.form.submit()">
                                        <option value="0">All My Classes</option>
                                        <?php foreach ($filter_classes as $class): ?>
                                            <option value="<?php echo (int)$class['id']; ?>" <?php if ($selected_class == (int)$class['id']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($class['class_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="syllabusProgressChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header"><h5>Mid-Term Average Marks (%)</h5></div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="midMarksChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($pending_alerts)) : ?>
                    <div class="alerts-card" style="margin-top: 24px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <h5>Pending Alerts</h5>
                            <a href="view_alerts.php" style="font-size:0.9rem; color:#A6192E;">View all</a>
                        </div>
                        <ul class="alerts-list">
                            <?php foreach ($pending_alerts as $alert): ?>
                                <li>
                                    <a href="view_alerts.php?id=<?php echo (int)$alert['id']; ?>" style="display:block; text-decoration:none; color:inherit;">
                                        <span class="alert-message"><?php echo htmlspecialchars($alert['message']); ?></span>
                                        <span class="alert-meta">Received on <?php echo date('d M Y, h:i A', strtotime($alert['created_at'])); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                </div>
                <div class="footer-bottom">
                    &copy; <?php echo date("Y"); ?> Kuchuru Sai Krishna Reddy – STME. All rights reserved.
                </div>
        </div>
    </div>
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const subjectCards = document.querySelectorAll('.subject-card');
            const detailsPanel = document.getElementById('subject-details-panel');
            const progressData = <?php echo json_encode($progress_by_subject); ?>;
            let activeCard = null;

            const escapeHtml = (value = '') => value.toString().replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[char]));

            const formatHours = (value) => {
                const num = Number(value);
                if (!Number.isFinite(num)) {
                    return '0';
                }
                const rounded = Math.round(num * 10) / 10;
                return Math.abs(rounded - Math.round(rounded)) < 0.05 ? Math.round(rounded).toString() : rounded.toFixed(1);
            };

            const formatTimeline = (timeline = '') => {
                if (!timeline) {
                    return 'Not reported';
                }
                const weekMatch = timeline.match(/week_(\d+)/i);
                if (weekMatch) {
                    return `Week ${weekMatch[1]}`;
                }
                switch (timeline.toLowerCase()) {
                    case 'mid1':
                        return 'Mid 1 checkpoint';
                    case 'mid2':
                        return 'Mid 2 checkpoint';
                    case 'final':
                        return 'Final checkpoint';
                    default:
                        return timeline.charAt(0).toUpperCase() + timeline.slice(1);
                }
            };

            const formatDateTime = (value = '') => {
                if (!value) {
                    return 'Not recorded';
                }
                const normalized = value.replace(' ', 'T');
                const parsed = new Date(normalized);
                if (Number.isNaN(parsed.getTime())) {
                    return value;
                }
                return parsed.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
            };

            subjectCards.forEach(card => {
                card.addEventListener('click', function() {
                    const subjectName = this.dataset.subjectName || '';
                    const subjectNameDisplay = this.dataset.subjectNameDisplay || subjectName;
                    const assignmentKey = this.dataset.assignmentKey || '';
                    const classLabel = this.dataset.classLabel || '';
                    const classId = Number.parseInt(this.dataset.classId || '0', 10) || 0;
                    const sectionId = Number.parseInt(this.dataset.sectionId || '0', 10) || 0;
                    const school = this.dataset.school || '';
                    const semester = this.dataset.semester || '';
                    const totalHours = parseFloat(this.dataset.totalHours || '0') || 0;
                    const theoryHours = parseFloat(this.dataset.theoryHours || '0') || 0;
                    const practicalHours = parseFloat(this.dataset.practicalHours || '0') || 0;
                    const practicalLabel = (this.dataset.practicalLabel || 'Practical');
                    const subjectType = (this.dataset.subjectType || 'regular').toLowerCase();
                    const assignmentNote = this.dataset.assignmentNote || '';

                    if (this.classList.contains('active')) {
                        detailsPanel.style.display = 'none';
                        this.classList.remove('active');
                        activeCard = null;
                        return;
                    }

                    if (activeCard) {
                        activeCard.classList.remove('active');
                    }

                    this.classList.add('active');
                    activeCard = this;

                    populateDetailsPanel(subjectName, subjectNameDisplay, classLabel, classId, sectionId, school, semester, totalHours, theoryHours, practicalHours, practicalLabel, subjectType, assignmentNote, assignmentKey);
                    detailsPanel.style.display = 'block';
                });
            });

            function populateDetailsPanel(subjectNameRaw, subjectNameDisplay, classLabel, classId, sectionId, school, semester, totalHours, theoryHours, practicalHours, practicalLabel, subjectType, assignmentNote, assignmentKey) {
                const normalizedName = subjectNameRaw || '';
                const displayName = subjectNameDisplay || normalizedName;
                let data = null;
                if (progressData && typeof progressData === 'object') {
                    if (assignmentKey && Object.prototype.hasOwnProperty.call(progressData, assignmentKey)) {
                        data = progressData[assignmentKey];
                    }
                    if (!data && normalizedName) {
                        const legacyKey = 'legacy|' + normalizedName.toLowerCase();
                        if (Object.prototype.hasOwnProperty.call(progressData, legacyKey)) {
                            data = progressData[legacyKey];
                        }
                    }
                }

                const safeSubject = escapeHtml(displayName || '');
                const readableClass = classLabel !== '' ? escapeHtml(classLabel) : 'Class assignment pending';
                const labelForDisplay = (practicalLabel && practicalLabel.trim() !== '') ? practicalLabel.trim() : 'Practical';
                const weeklyTotal = (theoryHours > 0 || practicalHours > 0) ? (theoryHours + practicalHours) : totalHours;
                let hoursLabel = '';
                if (theoryHours > 0 || practicalHours > 0) {
                    hoursLabel = `${formatHours(theoryHours)} theory / ${formatHours(practicalHours)} ${labelForDisplay} (total ${formatHours(theoryHours + practicalHours)})`;
                } else if (totalHours > 0) {
                    hoursLabel = `${formatHours(totalHours)} total weekly hours`;
                }
                const typeLabel = subjectType === 'elective' ? 'Elective' : 'Regular';
                let contentHTML = `<div class="details-header">Details for ${safeSubject}</div>`;

                contentHTML += `<p class="subject-meta"><strong>Class:</strong> ${readableClass}</p>`;
                const metaBits = [];
                if (school) {
                    metaBits.push(`School: ${escapeHtml(school)}`);
                }
                if (semester) {
                    metaBits.push(`Semester: ${escapeHtml(semester)}`);
                }
                if (metaBits.length) {
                    contentHTML += `<p class="subject-meta">${metaBits.join(' • ')}</p>`;
                }
                contentHTML += `<p class="subject-meta"><strong>Subject Type:</strong> ${escapeHtml(typeLabel)}</p>`;
                if (hoursLabel !== '') {
                    contentHTML += `<p class="subject-meta"><strong>Weekly Plan:</strong> ${escapeHtml(hoursLabel)}</p>`;
                }
                if (assignmentNote) {
                    contentHTML += `<p class="subject-meta"><strong>Note:</strong> ${escapeHtml(assignmentNote)}</p>`;
                }

                if (data) {
                    const plannedHours = parseFloat(data.planned_hours ?? 0) || 0;
                    const actualHours = parseFloat(data.actual_hours ?? 0) || 0;
                    const extraClasses = parseFloat(data.extra_classes ?? 0) || 0;
                    const scheduledActual = Math.max(actualHours - extraClasses, 0);
                    const completionPct = parseFloat(data.completion_percentage ?? 0) || 0;
                    const timelineLabel = formatTimeline(data.timeline || '');
                    const topicNote = (data.topic || '').toString();
                    const weeksCovered = (weeklyTotal > 0) ? (plannedHours / weeklyTotal) : 0;
                    const weeksDescriptor = weeksCovered > 0 ? `≈ ${formatHours(weeksCovered)} week${Math.abs(weeksCovered - 1) > 0.01 ? 's' : ''}` : '';
                    const plannedDisplay = formatHours(plannedHours);
                    const scheduledDisplay = formatHours(scheduledActual);
                    const extraDisplay = formatHours(extraClasses);
                    let plannedSubtext = `Expected cumulative by ${timelineLabel}`;
                    if (weeksDescriptor) {
                        plannedSubtext += ` • ${weeksDescriptor}`;
                    }
                    if (weeksCovered > 0 && (theoryHours > 0 || practicalHours > 0)) {
                        const theoryExpected = theoryHours > 0 ? theoryHours * weeksCovered : 0;
                        const practicalExpected = practicalHours > 0 ? practicalHours * weeksCovered : 0;
                        const parts = [];
                        if (theoryHours > 0) {
                            parts.push(`${formatHours(theoryExpected)} theory`);
                        }
                        if (practicalHours > 0) {
                            parts.push(`${formatHours(practicalExpected)} practical`);
                        }
                        if (parts.length > 0) {
                            plannedSubtext += ` • ${parts.join(' / ')}`;
                        }
                    }
                    const scheduledSubtext = 'Recorded via scheduled sessions (excludes extras)';
                    const extraSubtext = extraClasses > 0 ? 'Logged beyond weekly plan' : 'No extra sessions logged';
                    const delta = actualHours - plannedHours;
                    const deltaAbs = Math.abs(delta);
                    let statusClass = 'status-neutral';
                    let statusText;
                    const completionLabel = `${Math.round(completionPct)}% of expected hours`;
                    if (deltaAbs < 0.5) {
                        statusText = `On track. ${completionLabel}.`;
                    } else if (delta > 0) {
                        statusClass = 'status-positive';
                        statusText = `Ahead by ${formatHours(delta)} hrs (including extra sessions). ${completionLabel}.`;
                    } else {
                        statusClass = 'status-negative';
                        statusText = `Behind by ${formatHours(deltaAbs)} hrs. ${completionLabel}.`;
                    }

                    contentHTML += `
                        <h5>Latest Update</h5>
                        <p class="subject-meta"><strong>Timeline:</strong> ${escapeHtml(timelineLabel)}</p>
                        ${topicNote && topicNote.toLowerCase() !== timelineLabel.toLowerCase() ? `<p class="subject-meta"><strong>Entry Note:</strong> ${escapeHtml(topicNote)}</p>` : ''}
                        <div class="progress-summary">
                            <div class="summary-card">
                                <div class="summary-label">Planned To Date</div>
                                <div class="summary-value">${escapeHtml(plannedDisplay)} hrs</div>
                                <div class="summary-subtext">${escapeHtml(plannedSubtext)}</div>
                            </div>
                            <div class="summary-card">
                                <div class="summary-label">Delivered (Scheduled)</div>
                                <div class="summary-value">${escapeHtml(scheduledDisplay)} hrs</div>
                                <div class="summary-subtext">${escapeHtml(scheduledSubtext)}</div>
                            </div>
                            <div class="summary-card">
                                <div class="summary-label">Extra Classes</div>
                                <div class="summary-value">${escapeHtml(extraDisplay)} hrs</div>
                                <div class="summary-subtext">${escapeHtml(extraSubtext)}</div>
                            </div>
                        </div>
                        <div class="progress-status ${statusClass}">${escapeHtml(statusText)}</div>
                        <p class="subject-meta"><strong>Last Updated:</strong> ${escapeHtml(formatDateTime(data.updated_at || ''))}</p>
                    `;
                } else {
                    contentHTML += '<p>No weekly progress has been submitted yet. Use the Update Progress page to log your first entry.</p>';
                }

                contentHTML += `
                    <div class="details-actions">
                        <a href="update_progress.php?subject=${encodeURIComponent(subjectNameRaw)}&class_id=${classId}&section_id=${sectionId}&assignment_key=${encodeURIComponent(assignmentKey)}" class="btn">Update Progress</a>
                        <a href="manage_ica_marks.php" class="btn">Manage ICA Marks</a>
                        <a href="view_reports.php" class="btn">View Reports</a>
                    </div>
                `;

                detailsPanel.innerHTML = contentHTML;
            }

            // --- START: Chart Rendering ---
            
            // 1. Syllabus Progress Chart
            const syllabusData = <?php echo json_encode($syllabus_chart_data); ?>;
            const syllabusLabels = syllabusData.map(item => item.subject_display || item.subject || '');
            const week5Series = syllabusData.map(item => item.week5);
            const week10Series = syllabusData.map(item => item.week10);
            const finalSeries = syllabusData.map(item => item.final);
            new Chart(document.getElementById('syllabusProgressChart'), {
                type: 'bar',
                data: {
                    labels: syllabusLabels,
                    datasets: [
                        {
                            label: 'Week 5 Progress',
                            data: week5Series,
                            backgroundColor: 'rgba(255, 99, 132, 0.7)',
                        },
                        {
                            label: 'Week 10 Progress',
                            data: week10Series,
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        },
                        {
                            label: 'Final Progress',
                            data: finalSeries,
                            backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, max: 100, ticks: { callback: value => value + '%' } },
                        x: { stacked: false }
                    },
                    plugins: { legend: { position: 'top' } }
                }
            });

            // 2. Mid Marks Chart
            const midMarksData = <?php echo json_encode($mid_marks_data); ?>;
            const midMarksLabels = midMarksData.map(item => item.subject_name_display || item.subject_name || '');
            new Chart(document.getElementById('midMarksChart'), {
                type: 'bar',
                data: {
                    labels: midMarksLabels,
                    datasets: [
                        {
                            label: 'Mid Exam Average',
                            data: midMarksData.map(item => item.mid_avg),
                            backgroundColor: 'rgba(255, 206, 86, 0.7)',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, max: 100, ticks: { callback: value => value + '%' } }
                    },
                    plugins: { legend: { position: 'top' } }
                }
            });
            // --- END: Chart Rendering ---
        });
    </script>
</body>
</html>
