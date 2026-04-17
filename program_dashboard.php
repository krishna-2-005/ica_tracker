<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/settings_helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'program_chair') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/academic_context.php';
require_once __DIR__ . '/includes/term_switcher_ui.php';

$program_chair_id = (int)$_SESSION['user_id'];
$syllabus_threshold = get_syllabus_threshold($conn, $program_chair_id);
$performance_threshold = get_performance_threshold($conn, $program_chair_id);
$programChairNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$programChairNameDisplay = $programChairNameRaw !== '' ? format_person_display($programChairNameRaw) : '';

function columnExists($conn, $table, $column) {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM {$table} LIKE '{$column}'");
    $exists = $check && mysqli_num_rows($check) > 0;
    if ($check) { mysqli_free_result($check); }
    return $exists;
}

function ensureColumn(mysqli $conn, string $table, string $column, string $definition): void {
    if (columnExists($conn, $table, $column)) {
        return;
    }
    $table_safe = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    $column_safe = preg_replace('/[^A-Za-z0-9_]/', '', $column);
    if ($table_safe === '' || $column_safe === '') {
        return;
    }
    $alter_sql = sprintf("ALTER TABLE `%s` ADD COLUMN `%s` %s", $table_safe, $column_safe, $definition);
    @mysqli_query($conn, $alter_sql);
}
$syllabus_columns = [
    ['extra_classes', 'DECIMAL(6,2) NOT NULL DEFAULT 0'],
    ['actual_theory_hours', 'DECIMAL(6,2) NOT NULL DEFAULT 0'],
    ['actual_practical_hours', 'DECIMAL(6,2) NOT NULL DEFAULT 0'],
    ['extra_theory_hours', 'DECIMAL(6,2) NOT NULL DEFAULT 0'],
    ['extra_practical_hours', 'DECIMAL(6,2) NOT NULL DEFAULT 0'],
    ['planned_hours', 'DECIMAL(6,2) NOT NULL DEFAULT 0'],
    ['actual_hours', 'DECIMAL(6,2) NOT NULL DEFAULT 0']
];
foreach ($syllabus_columns as [$column_name, $definition]) {
    ensureColumn($conn, 'syllabus_progress', $column_name, $definition);
}

function format_hours_display($value): string {
    if (!is_numeric($value)) {
        return '0';
    }
    $value = (float)$value;
    if (abs($value - round($value)) < 0.01) {
        return (string)round($value);
    }
    return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
}

function metric_severity_from_count(int $count, int $mediumThreshold, int $highThreshold): string {
    if ($count >= $highThreshold) {
        return 'high';
    }
    if ($count >= $mediumThreshold) {
        return 'medium';
    }
    return 'low';
}

function abbreviate_subject_name(string $name): string {
    $trimmed = trim($name);
    if ($trimmed === '') {
        return '';
    }

    // Prefer explicit code entered before the first hyphen (e.g., "BEEE-...").
    if (preg_match('/[-–—]/u', $trimmed)) {
        $parts = preg_split('/[-–—]/u', $trimmed, 2);
        if ($parts && count($parts) > 1) {
            $candidate = trim((string)$parts[0]);
            if ($candidate !== '' && !preg_match('/\s/u', $candidate) && strtoupper($candidate) === $candidate) {
                return $candidate;
            }
        }
    }

    $tokens = preg_split('/[\s\-_]+/u', $trimmed, -1, PREG_SPLIT_NO_EMPTY);
    if (!$tokens) {
        $cleaned = preg_replace('/[^A-Za-z0-9]/', '', $trimmed);
        $cleaned = $cleaned !== '' ? $cleaned : $trimmed;
        return strtoupper(substr($cleaned, 0, 4));
    }

    $stop_words = ['and', 'the', 'of', 'for', 'in', 'to', 'with', 'a', 'an', 'on', 'at', 'by', 'from', 'into', 'per', 'as', 'is', '&'];
    $abbr = '';
    foreach ($tokens as $token) {
        $clean = preg_replace('/[^A-Za-z0-9]/', '', $token);
        if ($clean === '') {
            continue;
        }
        if (in_array(strtolower($clean), $stop_words, true)) {
            continue;
        }
        $abbr .= strtoupper($clean[0]);
    }

    if ($abbr === '') {
        foreach ($tokens as $token) {
            $clean = preg_replace('/[^A-Za-z0-9]/', '', $token);
            if ($clean === '') {
                continue;
            }
            $abbr .= strtoupper($clean[0]);
        }
    }

    if ($abbr === '') {
        $fallback = preg_replace('/[^A-Za-z0-9]/', '', $trimmed);
        $fallback = $fallback !== '' ? $fallback : $trimmed;
        return strtoupper(substr($fallback, 0, 4));
    }

    return substr($abbr, 0, 6);
}

$class_has_school = columnExists($conn, 'classes', 'school');
$class_has_department = columnExists($conn, 'classes', 'department');
$subject_has_school = columnExists($conn, 'subjects', 'school');
$subject_has_department = columnExists($conn, 'subjects', 'department');
$user_has_school = columnExists($conn, 'users', 'school');
$user_has_department = columnExists($conn, 'users', 'department');
$calendar_has_school = columnExists($conn, 'academic_calendar', 'school_name');
$calendar_has_department = columnExists($conn, 'academic_calendar', 'department_name');

$class_school_field = $class_has_school ? 'school' : ($class_has_department ? 'department' : null);
$subject_school_field = $subject_has_school ? 'school' : ($subject_has_department ? 'department' : null);
$user_school_field = $user_has_school ? 'school' : ($user_has_department ? 'department' : null);
$calendar_field = $calendar_has_school ? 'school_name' : ($calendar_has_department ? 'department_name' : null);

$class_alt_field = ($class_has_school && $class_has_department) ? ($class_school_field === 'school' ? 'department' : 'school') : null;
$subject_alt_field = ($subject_has_school && $subject_has_department) ? ($subject_school_field === 'school' ? 'department' : 'school') : null;
$user_alt_field = ($user_has_school && $user_has_department) ? ($user_school_field === 'school' ? 'department' : 'school') : null;
$syllabus_has_class_id = columnExists($conn, 'syllabus_progress', 'class_id');
$syllabus_has_section_id = columnExists($conn, 'syllabus_progress', 'section_id');
$syllabus_has_class_label = columnExists($conn, 'syllabus_progress', 'class_label');

// --- DYNAMIC ACADEMIC WEEK CALCULATION ---
$week_number_display = "Not Set";
// Fetch program chair's school/department value
$pc_school = '';
if ($user_school_field) {
    $user_dept_query = "SELECT {$user_school_field} AS pc_school FROM users WHERE id = ?";
    $stmt_dept = mysqli_prepare($conn, $user_dept_query);
    if ($stmt_dept) {
        mysqli_stmt_bind_param($stmt_dept, "i", $program_chair_id);
        mysqli_stmt_execute($stmt_dept);
        $dept_result = mysqli_stmt_get_result($stmt_dept);
        if ($dept_result && ($user_dept = mysqli_fetch_assoc($dept_result))) {
            $pc_school = (string)($user_dept['pc_school'] ?? '');
        }
        if ($dept_result) {
            mysqli_free_result($dept_result);
        }
        mysqli_stmt_close($stmt_dept);
    }
}

$academicContext = resolveAcademicContext($conn, [
    'school_name' => $pc_school
]);
$activeTerm = $academicContext['active'] ?? null;
$termDateFilter = $academicContext['date_filter'] ?? null;
$termStartDate = $termDateFilter['start'] ?? null;
$termEndDate = $termDateFilter['end'] ?? null;
$activeTermId = isset($activeTerm['id']) ? (int)$activeTerm['id'] : 0;
$activeTermTypeRaw = strtolower(trim((string)($activeTerm['semester_term'] ?? '')));
$activeTermParity = '';
if (strpos($activeTermTypeRaw, 'even') !== false) {
    $activeTermParity = 'even';
} elseif (strpos($activeTermTypeRaw, 'odd') !== false) {
    $activeTermParity = 'odd';
}
$termStartBound = $termStartDate ? $termStartDate . ' 00:00:00' : null;
$termEndBound = $termEndDate ? $termEndDate . ' 23:59:59' : null;
$termStartEsc = $termStartBound ? mysqli_real_escape_string($conn, $termStartBound) : null;
$termEndEsc = $termEndBound ? mysqli_real_escape_string($conn, $termEndBound) : null;

if ($termStartDate && $termEndDate) {
    $start_date = new DateTime($termStartDate);
    $end_date = new DateTime($termEndDate);
    $today = new DateTime('today');

    if ($today < $start_date) {
        $week_number_display = 'Term not started';
    } elseif ($today > $end_date) {
        $week_number_display = 'Term completed';
    } else {
        $days_passed = $start_date->diff($today)->days;
        $current_week = (int)floor($days_passed / 7) + 1;
        $week_number_display = 'Week ' . $current_week;
    }
}
$marksDateClause = ($termStartEsc && $termEndEsc) ? " AND ism.updated_at BETWEEN '$termStartEsc' AND '$termEndEsc'" : '';
$marksDateCondition = ($termStartEsc && $termEndEsc) ? "ism.updated_at BETWEEN '$termStartEsc' AND '$termEndEsc'" : null;
$syllabusDateClause = ($termStartEsc && $termEndEsc) ? " AND sp.updated_at BETWEEN '$termStartEsc' AND '$termEndEsc'" : '';
$syllabusDateCondition = ($termStartEsc && $termEndEsc) ? "sp.updated_at BETWEEN '$termStartEsc' AND '$termEndEsc'" : null;
$alertsDateClause = ($termStartEsc && $termEndEsc) ? " AND a.created_at BETWEEN '$termStartEsc' AND '$termEndEsc'" : '';
$alertsDateCondition = ($termStartEsc && $termEndEsc) ? "a.created_at BETWEEN '$termStartEsc' AND '$termEndEsc'" : null;

// --- ALL DATA FETCHING LOGIC ---

// 1. FILTERS
$school_param_provided = array_key_exists('school', $_GET);
$school_filter_display = '';
if ($school_param_provided) {
    $school_filter_display = trim($_GET['school']);
} elseif (!empty($pc_school)) {
    $school_filter_display = $pc_school;
}
$school_filter = $school_filter_display !== '' ? mysqli_real_escape_string($conn, $school_filter_display) : '';
$semester_filter = isset($_GET['semester'])
    ? mysqli_real_escape_string($conn, trim((string)$_GET['semester']))
    : '';
$class_filter = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$status_filter_raw = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
$allowed_status_filters = ['good', 'average', 'at_risk'];
$status_filter = in_array($status_filter_raw, $allowed_status_filters, true) ? $status_filter_raw : '';
// departments_result now aliases whichever field is present to 'school' so the front-end can use the same name
$school_options = [];
$schools_res = mysqli_query($conn, "SELECT school_name FROM schools ORDER BY school_name");
if ($schools_res) {
    while ($row = mysqli_fetch_assoc($schools_res)) {
        if (!empty($row['school_name'])) {
            $school_options[] = $row['school_name'];
        }
    }
    mysqli_free_result($schools_res);
}
if (empty($school_options) && $class_school_field) {
    $dept_sql = "SELECT DISTINCT {$class_school_field} AS school FROM classes WHERE {$class_school_field} IS NOT NULL AND {$class_school_field} <> '' ORDER BY {$class_school_field}";
    $dept_res = mysqli_query($conn, $dept_sql);
    if ($dept_res) {
        while ($row = mysqli_fetch_assoc($dept_res)) {
            if (!empty($row['school'])) {
                $school_options[] = $row['school'];
            }
        }
        mysqli_free_result($dept_res);
    }
}
if (!empty($pc_school) && !in_array($pc_school, $school_options, true)) {
    $school_options[] = $pc_school;
    sort($school_options);
}
if ($school_filter === '' && !$school_param_provided && !empty($pc_school)) {
    $school_filter_display = $pc_school;
    $school_filter = mysqli_real_escape_string($conn, $pc_school);
}

// 2. OVERVIEW CARDS DATA
$assignment_where_parts = [];
if ($activeTermId > 0) {
    $assignment_where_parts[] = 'c.academic_term_id = ' . $activeTermId;
} elseif ($activeTermParity === 'even') {
    $assignment_where_parts[] = 'CAST(c.semester AS UNSIGNED) % 2 = 0';
} elseif ($activeTermParity === 'odd') {
    $assignment_where_parts[] = 'CAST(c.semester AS UNSIGNED) % 2 = 1';
}
if ($school_filter !== '') {
    $school_clauses = [];
    if ($subject_school_field) {
        $school_clauses[] = "s_assign.{$subject_school_field} = '$school_filter'";
    }
    if ($subject_alt_field) {
        $school_clauses[] = "s_assign.{$subject_alt_field} = '$school_filter'";
    }
    if ($class_school_field) {
        $school_clauses[] = "c.{$class_school_field} = '$school_filter'";
    }
    if ($class_alt_field) {
        $school_clauses[] = "c.{$class_alt_field} = '$school_filter'";
    }
    if (!empty($school_clauses)) {
        $assignment_where_parts[] = '(' . implode(' OR ', array_unique($school_clauses)) . ')';
    }
}
if ($semester_filter !== '') {
    $assignment_where_parts[] = "c.semester = '$semester_filter'";
}
if ($class_filter > 0) {
    $assignment_where_parts[] = 'c.id = ' . $class_filter;
}
$assignmentWhereSql = !empty($assignment_where_parts) ? implode(' AND ', $assignment_where_parts) : '';
$assignmentWhereClause = $assignmentWhereSql !== '' ? ' WHERE ' . $assignmentWhereSql : '';
$assignmentWhereAnd = $assignmentWhereSql !== '' ? ' AND ' . $assignmentWhereSql : '';
$assignmentSubjectSql = $assignmentWhereSql !== '' ? str_replace('s_assign.', 's.', $assignmentWhereSql) : '';
$assignmentSubjectAnd = $assignmentSubjectSql !== '' ? ' AND ' . $assignmentSubjectSql : '';

$assignment_scope_sql = "
    SELECT DISTINCT
        tsa.teacher_id,
        tsa.subject_id,
        s_assign.subject_name,
        tsa.class_id,
        COALESCE(tsa.section_id, 0) AS section_id
    FROM teacher_subject_assignments tsa
    INNER JOIN classes c ON c.id = tsa.class_id
    INNER JOIN subjects s_assign ON s_assign.id = tsa.subject_id
" . $assignmentWhereClause;

$assignment_detail_sql = "
    SELECT
        tsa.teacher_id,
        tsa.subject_id,
        s_assign.subject_name,
        c.id AS class_id,
        COALESCE(tsa.section_id, 0) AS section_id
    FROM teacher_subject_assignments tsa
    INNER JOIN classes c ON c.id = tsa.class_id
    INNER JOIN subjects s_assign ON s_assign.id = tsa.subject_id
" . $assignmentWhereClause;

$card_link_params = [];
if ($school_filter_display !== '') {
    $card_link_params['school'] = $school_filter_display;
}
if ($semester_filter !== '') {
    $card_link_params['semester'] = $semester_filter;
}
if ($class_filter > 0) {
    $card_link_params['class_id'] = $class_filter;
}
if ($status_filter !== '') {
    $card_link_params['status'] = $status_filter;
}
$card_link_query = http_build_query($card_link_params);
$course_progress_link = 'course_progress.php' . ($card_link_query !== '' ? ('?' . $card_link_query) : '');
$students_page_link = 'student_progress.php' . ($card_link_query !== '' ? ('?' . $card_link_query) : '');
$student_at_risk_link = 'student_progress.php?' . http_build_query(array_merge($card_link_params, ['status' => 'at_risk']));
$alerts_link = 'send_alerts.php' . ($card_link_query !== '' ? ('?' . $card_link_query) : '');
$week_link = 'manage_academic_calendar.php';

$teacher_link_params = [];
if ($school_filter_display !== '') {
    $teacher_link_params['school'] = $school_filter_display;
}
if ($semester_filter !== '') {
    $teacher_link_params['semester_filter'] = $semester_filter;
}
if ($class_filter > 0) {
    $teacher_link_params['class_filter'] = $class_filter;
}
$teacher_progress_link = 'teacher_progress.php' . (!empty($teacher_link_params) ? ('?' . http_build_query($teacher_link_params)) : '');
$teacher_at_risk_link = 'teacher_progress.php?' . http_build_query(array_merge($teacher_link_params, ['status_filter' => 'at_risk']));

$assignment_detail_res = mysqli_query($conn, $assignment_detail_sql);
$course_keys = [];
$subject_ids = [];
$teacher_subject_pairs = [];
if ($assignment_detail_res) {
    while ($row = mysqli_fetch_assoc($assignment_detail_res)) {
        $subject_id = isset($row['subject_id']) ? (int)$row['subject_id'] : 0;
        $class_id = isset($row['class_id']) ? (int)$row['class_id'] : 0;
        $section_id = isset($row['section_id']) ? (int)$row['section_id'] : 0;
        $teacher_id = isset($row['teacher_id']) ? (int)$row['teacher_id'] : 0;
        $course_keys[$subject_id . ':' . $class_id . ':' . $section_id] = true;
        if ($subject_id > 0) {
            $subject_ids[$subject_id] = true;
        }
        if ($teacher_id > 0 && $subject_id > 0) {
            $pair_key = $teacher_id . ':' . $subject_id;
            if (!isset($teacher_subject_pairs[$pair_key])) {
                $teacher_subject_pairs[$pair_key] = [
                    'teacher_id' => $teacher_id,
                    'subject_id' => $subject_id,
                    'subject_name' => $row['subject_name']
                ];
            }
        }
    }
    mysqli_free_result($assignment_detail_res);
}
$total_courses = count($subject_ids);

$avg_syllabus = 0;
if (!empty($teacher_subject_pairs)) {
    $avg_syllabus_q = "
        SELECT AVG(completion) AS avg_syllabus
        FROM (
            SELECT
                tsa.teacher_id,
                tsa.subject_id,
                MAX(sp.completion_percentage) AS completion
            FROM teacher_subject_assignments tsa
            INNER JOIN classes c ON c.id = tsa.class_id
            INNER JOIN subjects s_assign ON s_assign.id = tsa.subject_id
            LEFT JOIN syllabus_progress sp
                ON sp.teacher_id = tsa.teacher_id
                AND sp.subject = s_assign.subject_name" . ($syllabusDateCondition ? " AND {$syllabusDateCondition}" : '') . "
            " . $assignmentWhereClause . "
            GROUP BY tsa.teacher_id, tsa.subject_id
        ) latest
        WHERE latest.completion IS NOT NULL
    ";
    $avg_syllabus_res = mysqli_query($conn, $avg_syllabus_q);
    if ($avg_syllabus_res) {
        $avg_syllabus = (int)round(mysqli_fetch_assoc($avg_syllabus_res)['avg_syllabus'] ?? 0);
        mysqli_free_result($avg_syllabus_res);
    }
}

$low_performing_students = 0;
if (!empty($subject_ids)) {
    $marksDateJoin = $marksDateCondition ? " AND {$marksDateCondition}" : '';
    $low_performing_q = "
        SELECT COUNT(*) AS low_count FROM (
            SELECT
                st.id AS student_id,
                SUM(COALESCE(ism.marks, 0)) AS obtained,
                SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 THEN ic.marks_per_instance ELSE 0 END) AS possible
            FROM teacher_subject_assignments tsa
            INNER JOIN classes c ON c.id = tsa.class_id
            INNER JOIN subjects s_assign ON s_assign.id = tsa.subject_id
            INNER JOIN students st ON st.class_id = c.id
                AND (tsa.section_id IS NULL OR tsa.section_id = 0 OR COALESCE(st.section_id, 0) = tsa.section_id)
            LEFT JOIN ica_components ic ON ic.subject_id = s_assign.id
            LEFT JOIN ica_student_marks ism ON ism.component_id = ic.id
                AND ism.teacher_id = tsa.teacher_id
                AND ism.student_id = st.id" . $marksDateJoin . "
            " . ($assignmentWhereClause !== '' ? $assignmentWhereClause : ' WHERE 1=1') . "
            GROUP BY st.id
        ) student_rollup
        WHERE student_rollup.possible > 0
          AND (student_rollup.obtained / student_rollup.possible) * 100 < 50
    ";
    $low_performing_res = mysqli_query($conn, $low_performing_q);
    if ($low_performing_res) {
        $low_performing_students = (int) (mysqli_fetch_assoc($low_performing_res)['low_count'] ?? 0);
        mysqli_free_result($low_performing_res);
    }
}

$base_progress_where = $syllabusDateCondition ? " WHERE {$syllabusDateCondition}" : '';
$ica_marks_where_parts = ["ic.total_marks > 0"];
if ($marksDateCondition) {
    $ica_marks_where_parts[] = $marksDateCondition;
}
$ica_marks_where = ' WHERE ' . implode(' AND ', $ica_marks_where_parts);

// 3. TEACHER PERFORMANCE TABLE DATA (Query fixed to show only the latest update)
$teacher_conditions = ["latest_progress.rn = 1", "u.role = 'teacher'"];
$teacher_school_condition = '';
if ($school_filter && $user_school_field) {
    $teacher_school_condition = "u.{$user_school_field} = '$school_filter'";
    $teacher_conditions[] = $teacher_school_condition;
}
if ($semester_filter !== '') {
    $teacher_conditions[] = "EXISTS (
        SELECT 1 FROM teacher_subject_assignments tsem
        JOIN classes csem ON csem.id = tsem.class_id
        JOIN subjects ssem ON ssem.id = tsem.subject_id
        WHERE tsem.teacher_id = u.id
          AND ssem.subject_name = latest_progress.subject
          AND csem.semester = '$semester_filter'
    )";
}
if ($class_filter > 0) {
    $teacher_conditions[] = "EXISTS (
        SELECT 1 FROM teacher_subject_assignments tcls
        WHERE tcls.teacher_id = u.id
          AND tcls.class_id = $class_filter
          AND tcls.subject_id = (SELECT id FROM subjects WHERE subject_name = latest_progress.subject LIMIT 1)
    )";
}

$base_progress_class_expr = $syllabus_has_class_id
    ? 'COALESCE(sp.class_id, tsa.class_id, 0)'
    : 'COALESCE(tsa.class_id, 0)';
$base_progress_section_expr = $syllabus_has_section_id
    ? 'COALESCE(sp.section_id, tsa.section_id, 0)'
    : 'COALESCE(tsa.section_id, 0)';
$base_progress_label_expr = $syllabus_has_class_label
    ? "COALESCE(NULLIF(sp.class_label, ''), TRIM(CONCAT_WS(' ', cls_base.class_name, NULLIF(sec_base.section_name, ''))))"
    : "TRIM(CONCAT_WS(' ', cls_base.class_name, NULLIF(sec_base.section_name, '')))";

$base_assignment_match_clauses = [
    'tsa.teacher_id = sp.teacher_id',
    'tsa.subject_id = s_base.id'
];
if ($syllabus_has_class_id) {
    $base_assignment_match_clauses[] = '(sp.class_id IS NULL OR sp.class_id = 0 OR tsa.class_id = sp.class_id)';
}
if ($syllabus_has_section_id) {
    $base_assignment_match_clauses[] = '(COALESCE(sp.section_id, 0) = 0 OR COALESCE(tsa.section_id, 0) = COALESCE(sp.section_id, 0))';
}
$base_assignment_match_sql = implode(' AND ', $base_assignment_match_clauses);

$teacher_performance_query = "
    WITH base_progress AS (
        SELECT
            sp.teacher_id,
            sp.subject,
            {$base_progress_class_expr} AS class_id,
            {$base_progress_section_expr} AS section_id,
            {$base_progress_label_expr} AS class_label,
            sp.timeline,
            sp.planned_hours,
            sp.actual_hours,
            sp.actual_theory_hours,
            sp.actual_practical_hours,
            sp.extra_classes,
            sp.extra_theory_hours,
            sp.extra_practical_hours,
            sp.completion_percentage,
            sp.updated_at
        FROM syllabus_progress sp
        LEFT JOIN subjects s_base ON s_base.subject_name = sp.subject
        LEFT JOIN teacher_subject_assignments tsa ON {$base_assignment_match_sql}
        LEFT JOIN classes cls_base ON cls_base.id = tsa.class_id
        LEFT JOIN sections sec_base ON sec_base.id = tsa.section_id
        {$base_progress_where}
    ),
    ranked_progress AS (
        SELECT
            bp.*,
            ROW_NUMBER() OVER (
                PARTITION BY bp.teacher_id, bp.subject, bp.class_id, bp.section_id
                ORDER BY
                    CASE
                        WHEN bp.timeline REGEXP '^week_[0-9]+' THEN CAST(SUBSTRING_INDEX(bp.timeline, '_', -1) AS UNSIGNED)
                        WHEN bp.timeline = 'final' THEN 1000
                        ELSE 2000
                    END DESC,
                    bp.updated_at DESC
            ) AS rn
        FROM base_progress bp
    ),
    aggregated_progress AS (
        SELECT
            bp.teacher_id,
            bp.subject,
            bp.class_id,
            bp.section_id,
            MAX(bp.class_label) AS class_label,
            SUM(bp.planned_hours) AS total_planned_hours,
            SUM(bp.actual_hours) AS total_actual_hours,
            SUM(bp.actual_theory_hours) AS total_actual_theory_hours,
            SUM(bp.actual_practical_hours) AS total_actual_practical_hours,
            SUM(bp.extra_classes) AS total_extra_classes,
            SUM(bp.extra_theory_hours) AS total_extra_theory_hours,
            SUM(bp.extra_practical_hours) AS total_extra_practical_hours,
            MAX(bp.completion_percentage) AS max_completion
        FROM base_progress bp
        GROUP BY bp.teacher_id, bp.subject, bp.class_id, bp.section_id
    )
    SELECT
        u.id AS teacher_id,
        u.name AS teacher_name,
        totals.subject AS course_name,
        subj.id AS subject_id,
        subj.semester AS subject_semester,
        totals.class_id,
        totals.section_id,
        COALESCE(NULLIF(totals.class_label, ''), TRIM(CONCAT_WS(' ', cls.class_name, NULLIF(sec.section_name, '')))) AS class_label,
        latest_progress.timeline,
        totals.total_planned_hours AS planned_hours,
        totals.total_actual_hours AS actual_hours,
        totals.total_actual_theory_hours AS actual_theory_hours,
        totals.total_actual_practical_hours AS actual_practical_hours,
        totals.total_extra_classes AS extra_classes,
        totals.total_extra_theory_hours AS extra_theory_hours,
        totals.total_extra_practical_hours AS extra_practical_hours,
        totals.max_completion AS avg_completion,
        latest_progress.updated_at AS last_updated,
        COALESCE(NULLIF(sd.tutorial_label, ''), '') AS practical_label_raw,
        COALESCE(sd.practical_hours, 0) AS planned_practical_hours
    FROM users u
    JOIN aggregated_progress totals ON u.id = totals.teacher_id
    JOIN ranked_progress latest_progress ON latest_progress.teacher_id = totals.teacher_id
        AND latest_progress.subject = totals.subject
        AND latest_progress.class_id = totals.class_id
        AND latest_progress.section_id = totals.section_id
        AND latest_progress.rn = 1
    JOIN subjects subj ON subj.subject_name = totals.subject
    LEFT JOIN subject_details sd ON sd.subject_id = subj.id
    LEFT JOIN classes cls ON cls.id = totals.class_id
    LEFT JOIN sections sec ON sec.id = totals.section_id
    JOIN (
        $assignment_scope_sql
    ) scope ON scope.teacher_id = totals.teacher_id
            AND scope.subject_id = subj.id
            AND scope.class_id = totals.class_id
            AND scope.section_id = totals.section_id
    WHERE " . implode(' AND ', $teacher_conditions) . "
    ORDER BY u.name, totals.subject, class_label";
$teacher_performance_result = mysqli_query($conn, $teacher_performance_query);
// If no rows returned and a school_filter was applied, try the alternate field (department) in case subjects/users use that column
if ($school_filter && $teacher_school_condition && $user_alt_field && (!$teacher_performance_result || mysqli_num_rows($teacher_performance_result) == 0)) {
    $teacher_performance_query_alt = str_replace($teacher_school_condition, "u.{$user_alt_field} = '$school_filter'", $teacher_performance_query);
    $alt_res = mysqli_query($conn, $teacher_performance_query_alt);
    if ($alt_res && mysqli_num_rows($alt_res) > 0) {
        $teacher_performance_result = $alt_res;
    }
}

// --- AUTOMATIC ALERT FOR LOW PROGRESS ---
if ($teacher_performance_result) {
    $alert_check_stmt = mysqli_prepare($conn, "SELECT id FROM alerts WHERE teacher_id = ? AND message = ?");
    $alert_insert_stmt = mysqli_prepare($conn, "INSERT INTO alerts (teacher_id, message, status, created_at) VALUES (?, ?, 'pending', NOW())");
    
    mysqli_data_seek($teacher_performance_result, 0); // Rewind result set
    while($row = mysqli_fetch_assoc($teacher_performance_result)) {
        $avg_completion = isset($row['avg_completion']) ? (float)$row['avg_completion'] : 0.0;
        if ($avg_completion + 0.0001 < $syllabus_threshold) {
            $teacher_id_alert = $row['teacher_id'];
            $subject_alert = $row['course_name'];
            $timeline_alert = $row['timeline'];
            $completion_alert = round($avg_completion);
            $threshold_label = (int)round($syllabus_threshold);

            $teacher_message = "Your syllabus progress for {$subject_alert} ({$timeline_alert}) is currently at {$completion_alert}%, which is below the {$threshold_label}% threshold.";
            
            // Check if this exact alert already exists to avoid duplicates
            mysqli_stmt_bind_param($alert_check_stmt, "is", $teacher_id_alert, $teacher_message);
            mysqli_stmt_execute($alert_check_stmt);
            if (mysqli_stmt_get_result($alert_check_stmt)->num_rows == 0) {
                mysqli_stmt_bind_param($alert_insert_stmt, "is", $teacher_id_alert, $teacher_message);
                mysqli_stmt_execute($alert_insert_stmt);
            }
        }
    }
    mysqli_stmt_close($alert_check_stmt);
    mysqli_stmt_close($alert_insert_stmt);
    mysqli_data_seek($teacher_performance_result, 0); // Rewind again for display
}


// Helper queries for teacher performance table
$teacher_progress_meta = [];

$ica_conditions = [];
$ica_school_condition = '';
if ($school_filter && $subject_school_field) {
    $ica_school_condition = "s.{$subject_school_field} = '$school_filter'";
    $ica_conditions[] = $ica_school_condition;
}
if ($semester_filter !== '') {
    $ica_conditions[] = "s.semester = '$semester_filter'";
}
if ($class_filter > 0) {
    $ica_conditions[] = "tsa.class_id = $class_filter";
}
$ica_avg_query = "SELECT tsa.teacher_id, s.subject_name, AVG(student_total) as final_ica_avg FROM (SELECT ism.student_id, ic.subject_id, SUM((ism.marks/ic.total_marks)*ic.scaled_total_marks) as student_total FROM ica_student_marks ism JOIN ica_components ic ON ism.component_id=ic.id" . $ica_marks_where . " GROUP BY ism.student_id, ic.subject_id) as student_totals JOIN subjects s ON student_totals.subject_id=s.id JOIN teacher_subject_assignments tsa ON s.id=tsa.subject_id";
if (!empty($ica_conditions)) {
    $ica_avg_query .= " WHERE " . implode(' AND ', $ica_conditions);
}
$ica_avg_query .= " GROUP BY tsa.teacher_id, s.id";
$ica_avg_result = mysqli_query($conn, $ica_avg_query);
if ((!$ica_avg_result || mysqli_num_rows($ica_avg_result) === 0) && $school_filter && $ica_school_condition && $subject_alt_field) {
    $ica_conditions_alt = $ica_conditions;
    foreach ($ica_conditions_alt as $index => $condition) {
        if ($condition === $ica_school_condition) {
            $ica_conditions_alt[$index] = "s.{$subject_alt_field} = '$school_filter'";
        }
    }
    $ica_avg_query_alt = "SELECT tsa.teacher_id, s.subject_name, AVG(student_total) as final_ica_avg FROM (SELECT ism.student_id, ic.subject_id, SUM((ism.marks/ic.total_marks)*ic.scaled_total_marks) as student_total FROM ica_student_marks ism JOIN ica_components ic ON ism.component_id=ic.id" . $ica_marks_where . " GROUP BY ism.student_id, ic.subject_id) as student_totals JOIN subjects s ON student_totals.subject_id=s.id JOIN teacher_subject_assignments tsa ON s.id=tsa.subject_id";
    if (!empty($ica_conditions_alt)) {
        $ica_avg_query_alt .= " WHERE " . implode(' AND ', $ica_conditions_alt);
    }
    $ica_avg_query_alt .= " GROUP BY tsa.teacher_id, s.id";
    $ica_avg_result_alt = mysqli_query($conn, $ica_avg_query_alt);
    if ($ica_avg_result_alt && mysqli_num_rows($ica_avg_result_alt) > 0) {
        $ica_avg_result = $ica_avg_result_alt;
    }
}
$teacher_ica_data = [];
if ($ica_avg_result) {
    while($row = mysqli_fetch_assoc($ica_avg_result)){
        $teacher_ica_data[$row['teacher_id']][$row['subject_name']] = $row['final_ica_avg'];
    }
}

$teacher_totals_where = ' WHERE 1=1';
if ($school_filter && $subject_school_field) {
    $teacher_totals_where .= " AND s.{$subject_school_field} = '$school_filter'";
}
if ($semester_filter !== '') {
    $teacher_totals_where .= " AND s.semester = '$semester_filter'";
}
if ($class_filter > 0) {
    $teacher_totals_where .= " AND tsa.class_id = $class_filter";
}

$teacher_totals_sql = "SELECT tsa.teacher_id, s.subject_name, COUNT(DISTINCT stu.id) AS total_students
    FROM teacher_subject_assignments tsa
    JOIN subjects s ON s.id = tsa.subject_id
    LEFT JOIN students stu ON stu.class_id = tsa.class_id
        AND (tsa.section_id IS NULL OR tsa.section_id = 0 OR COALESCE(stu.section_id, 0) = tsa.section_id)
    $teacher_totals_where
    GROUP BY tsa.teacher_id, s.id";
$teacher_totals_res = mysqli_query($conn, $teacher_totals_sql);
if ((!$teacher_totals_res || mysqli_num_rows($teacher_totals_res) === 0) && $school_filter && $subject_school_field && $subject_alt_field) {
    $teacher_totals_sql_alt = str_replace("s.{$subject_school_field} = '$school_filter'", "s.{$subject_alt_field} = '$school_filter'", $teacher_totals_sql);
    $teacher_totals_res_alt = mysqli_query($conn, $teacher_totals_sql_alt);
    if ($teacher_totals_res_alt && mysqli_num_rows($teacher_totals_res_alt) > 0) {
        $teacher_totals_res = $teacher_totals_res_alt;
    }
}
if ($teacher_totals_res) {
    while ($row = mysqli_fetch_assoc($teacher_totals_res)) {
        $teacher_id = (int)$row['teacher_id'];
        $subject_name = $row['subject_name'];
        if (!isset($teacher_progress_meta[$teacher_id])) {
            $teacher_progress_meta[$teacher_id] = [];
        }
        if (!isset($teacher_progress_meta[$teacher_id][$subject_name])) {
            $teacher_progress_meta[$teacher_id][$subject_name] = [
                'total_students' => 0,
                'evaluated_students' => 0,
                'at_risk_students' => 0
            ];
        }
        $teacher_progress_meta[$teacher_id][$subject_name]['total_students'] = (int)$row['total_students'];
    }
}

$teacher_eval_subquery = "SELECT tsa.teacher_id, s.subject_name, ism.student_id,
        AVG((ism.marks / NULLIF(ic.marks_per_instance, 0)) * 100) AS avg_pct
    FROM teacher_subject_assignments tsa
    JOIN subjects s ON s.id = tsa.subject_id
    JOIN classes c_eval ON c_eval.id = tsa.class_id
    JOIN ica_components ic ON ic.subject_id = s.id
    JOIN ica_student_marks ism ON ism.component_id = ic.id
    JOIN students stu ON stu.id = ism.student_id
    WHERE stu.class_id = tsa.class_id
      AND (tsa.section_id IS NULL OR tsa.section_id = 0 OR COALESCE(stu.section_id, 0) = COALESCE(tsa.section_id, 0))
      AND ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance <> 0";
    if ($school_filter && $subject_school_field) {
        $teacher_eval_subquery .= " AND s.{$subject_school_field} = '$school_filter'";
    }
    if ($semester_filter !== '') {
        $teacher_eval_subquery .= " AND s.semester = '$semester_filter'";
    }
    if ($class_filter > 0) {
        $teacher_eval_subquery .= " AND tsa.class_id = $class_filter";
    }
    if ($activeTermId > 0) {
        $teacher_eval_subquery .= " AND c_eval.academic_term_id = " . $activeTermId;
    }
if ($marksDateCondition) {
    $teacher_eval_subquery .= " AND {$marksDateCondition}";
}
$teacher_eval_subquery .= " GROUP BY tsa.teacher_id, s.id, ism.student_id";

$teacher_eval_sql = "SELECT stats.teacher_id, stats.subject_name,
        COUNT(DISTINCT stats.student_id) AS evaluated_students,
        SUM(CASE WHEN stats.avg_pct < 50 THEN 1 ELSE 0 END) AS at_risk_students
    FROM (" . $teacher_eval_subquery . ") stats
    GROUP BY stats.teacher_id, stats.subject_name";
$teacher_eval_res = mysqli_query($conn, $teacher_eval_sql);
if ((!$teacher_eval_res || mysqli_num_rows($teacher_eval_res) === 0) && $school_filter && $subject_school_field && $subject_alt_field) {
    $teacher_eval_sql_alt = str_replace("s.{$subject_school_field} = '$school_filter'", "s.{$subject_alt_field} = '$school_filter'", $teacher_eval_sql);
    $teacher_eval_res_alt = mysqli_query($conn, $teacher_eval_sql_alt);
    if ($teacher_eval_res_alt && mysqli_num_rows($teacher_eval_res_alt) > 0) {
        $teacher_eval_res = $teacher_eval_res_alt;
    }
}
if ($teacher_eval_res) {
    while ($row = mysqli_fetch_assoc($teacher_eval_res)) {
        $teacher_id = (int)$row['teacher_id'];
        $subject_name = $row['subject_name'];
        if (!isset($teacher_progress_meta[$teacher_id])) {
            $teacher_progress_meta[$teacher_id] = [];
        }
        if (!isset($teacher_progress_meta[$teacher_id][$subject_name])) {
            $teacher_progress_meta[$teacher_id][$subject_name] = [
                'total_students' => 0,
                'evaluated_students' => 0,
                'at_risk_students' => 0
            ];
        }
        $teacher_progress_meta[$teacher_id][$subject_name]['evaluated_students'] = (int)$row['evaluated_students'];
        $teacher_progress_meta[$teacher_id][$subject_name]['at_risk_students'] = (int)($row['at_risk_students'] ?? 0);
    }
}


// 4. CHART DATA (WITH FILTERS)
$chartAssignmentsJoin = "
    FROM teacher_subject_assignments tsa
    INNER JOIN classes c ON c.id = tsa.class_id
    INNER JOIN subjects s ON s.id = tsa.subject_id
";
$chartWhereClauseSql = $assignmentSubjectSql !== '' ? ' WHERE ' . $assignmentSubjectSql : '';
$chartGroupBySql = " GROUP BY s.id, s.subject_name, s.semester, s.school ORDER BY s.subject_name";

$syllabus_chart_q = "SELECT s.id,
        s.subject_name,
        s.semester,
        s.school,
        MAX(CASE WHEN sp.timeline LIKE 'week_%' AND CAST(SUBSTRING_INDEX(sp.timeline, '_', -1) AS UNSIGNED) BETWEEN 1 AND 3 THEN sp.completion_percentage END) AS week3_completion,
        MAX(CASE WHEN sp.timeline LIKE 'week_%' AND CAST(SUBSTRING_INDEX(sp.timeline, '_', -1) AS UNSIGNED) BETWEEN 1 AND 5 THEN sp.completion_percentage END) AS week5_completion,
        MAX(CASE WHEN sp.timeline LIKE 'week_%' AND CAST(SUBSTRING_INDEX(sp.timeline, '_', -1) AS UNSIGNED) BETWEEN 6 AND 10 THEN sp.completion_percentage END) AS week10_completion,
        MAX(CASE WHEN sp.timeline = 'final' THEN sp.completion_percentage END) AS final_checkpoint,
        MAX(sp.completion_percentage) AS latest_completion
    $chartAssignmentsJoin
    LEFT JOIN syllabus_progress sp ON sp.teacher_id = tsa.teacher_id
        AND sp.subject = s.subject_name" . ($syllabusDateCondition ? " AND {$syllabusDateCondition}" : '') . "
" . $chartWhereClauseSql . $chartGroupBySql;
$syllabus_chart_result = mysqli_query($conn, $syllabus_chart_q);
$syllabus_chart_data = [];
if ($syllabus_chart_result) {
    while ($row = mysqli_fetch_assoc($syllabus_chart_result)) {
        $raw_subject_name = $row['subject_name'] ?? '';
        $display_subject_name = format_subject_display($raw_subject_name);
        $syllabus_chart_data[] = [
            'subject_id' => isset($row['id']) ? (int)$row['id'] : null,
            'subject_name' => $display_subject_name,
            'subject_name_raw' => $raw_subject_name,
            'semester' => $row['semester'],
            'school' => $row['school'],
            'week3' => isset($row['week3_completion']) ? (int)$row['week3_completion'] : null,
            'week5' => isset($row['week5_completion']) ? (int)$row['week5_completion'] : null,
            'week10' => isset($row['week10_completion']) ? (int)$row['week10_completion'] : null,
            'final' => isset($row['final_checkpoint']) ? (int)$row['final_checkpoint'] : null,
            'latest' => isset($row['latest_completion']) ? (int)$row['latest_completion'] : null,
            'abbr' => abbreviate_subject_name($raw_subject_name)
        ];
    }
    mysqli_free_result($syllabus_chart_result);
}

$mid_perf_q = "SELECT s.id AS subject_id,
        s.subject_name,
        s.semester,
        s.school,
        AVG(CASE WHEN ic.component_name LIKE '%Mid Exam%' THEN (ism.marks / NULLIF(ic.marks_per_instance, 0)) * 100 ELSE NULL END) AS mid_avg
    $chartAssignmentsJoin
    LEFT JOIN ica_components ic ON ic.subject_id = s.id
    LEFT JOIN ica_student_marks ism ON ism.component_id = ic.id
        AND ism.teacher_id = tsa.teacher_id" . ($marksDateCondition ? " AND {$marksDateCondition}" : '') . "
    LEFT JOIN students stu_mid ON stu_mid.id = ism.student_id
        AND stu_mid.class_id = c.id
        AND (tsa.section_id IS NULL OR tsa.section_id = 0 OR COALESCE(stu_mid.section_id, 0) = COALESCE(tsa.section_id, 0))
" . $chartWhereClauseSql . $chartGroupBySql;
$mid_perf_result = mysqli_query($conn, $mid_perf_q);
$mid_perf_data = [];
if ($mid_perf_result) {
    while ($row = mysqli_fetch_assoc($mid_perf_result)) {
        $raw_subject_name = $row['subject_name'] ?? '';
        $row['subject_name_raw'] = $raw_subject_name;
        $row['subject_name'] = format_subject_display($raw_subject_name);
        $row['subject_id'] = isset($row['subject_id']) ? (int)$row['subject_id'] : null;
        $row['abbr'] = abbreviate_subject_name($raw_subject_name);
        $row['mid_avg'] = isset($row['mid_avg']) ? (float)$row['mid_avg'] : null;
        $mid_perf_data[] = $row;
    }
    mysqli_free_result($mid_perf_result);
}

$class_chart_summary_payload = [];
$class_subject_chart_payload = [];
$class_subject_chart_q = "SELECT
        c.id AS class_id,
        c.class_name,
    COALESCE(tsa.section_id, 0) AS section_id,
    COALESCE(sec_chart.section_name, '') AS section_name,
        c.semester,
        c.school,
        s.id AS subject_id,
        s.subject_name,
        AVG(latest_sp.completion_percentage) AS syllabus_avg,
        AVG(CASE
            WHEN ic.component_name LIKE '%Mid Exam%'
            THEN (ism.marks / NULLIF(ic.marks_per_instance, 0)) * 100
            ELSE NULL
        END) AS mid_avg
    FROM teacher_subject_assignments tsa
    INNER JOIN classes c ON c.id = tsa.class_id
    INNER JOIN subjects s ON s.id = tsa.subject_id
    LEFT JOIN (
        SELECT ranked.teacher_id, ranked.subject, ranked.class_id, ranked.section_id, ranked.completion_percentage
        FROM (
            SELECT
                sp.teacher_id,
                sp.subject,
                COALESCE(sp.class_id, 0) AS class_id,
                COALESCE(sp.section_id, 0) AS section_id,
                sp.completion_percentage,
                ROW_NUMBER() OVER (
                    PARTITION BY sp.teacher_id, sp.subject, COALESCE(sp.class_id, 0), COALESCE(sp.section_id, 0)
                    ORDER BY
                        CASE
                            WHEN sp.timeline REGEXP '^week_[0-9]+' THEN CAST(SUBSTRING_INDEX(sp.timeline, '_', -1) AS UNSIGNED)
                            WHEN sp.timeline = 'final' THEN 1000
                            ELSE 2000
                        END DESC,
                        sp.updated_at DESC
                ) AS rn
            FROM syllabus_progress sp" . ($syllabusDateCondition ? " WHERE {$syllabusDateCondition}" : '') . "
        ) ranked
        WHERE ranked.rn = 1
    ) latest_sp ON latest_sp.teacher_id = tsa.teacher_id
        AND latest_sp.subject = s.subject_name
        AND (latest_sp.class_id = 0 OR latest_sp.class_id = tsa.class_id)
        AND (latest_sp.section_id = 0 OR latest_sp.section_id = COALESCE(tsa.section_id, 0))
    LEFT JOIN ica_components ic ON ic.subject_id = s.id
    LEFT JOIN ica_student_marks ism ON ism.component_id = ic.id
        AND ism.teacher_id = tsa.teacher_id" . ($marksDateCondition ? " AND {$marksDateCondition}" : '') . "
    LEFT JOIN students stu_chart ON stu_chart.id = ism.student_id
        AND stu_chart.class_id = c.id
        AND (tsa.section_id IS NULL OR tsa.section_id = 0 OR COALESCE(stu_chart.section_id, 0) = COALESCE(tsa.section_id, 0))
    LEFT JOIN sections sec_chart ON sec_chart.id = tsa.section_id
    " . $chartWhereClauseSql . "
    GROUP BY c.id, c.class_name, COALESCE(tsa.section_id, 0), COALESCE(sec_chart.section_name, ''), c.semester, c.school, s.id, s.subject_name
    ORDER BY c.class_name, section_name, s.subject_name";
$class_subject_chart_result = mysqli_query($conn, $class_subject_chart_q);
if ($class_subject_chart_result) {
    $class_rollup = [];
    while ($chart_row = mysqli_fetch_assoc($class_subject_chart_result)) {
        $class_id = isset($chart_row['class_id']) ? (int)$chart_row['class_id'] : 0;
        $section_id = isset($chart_row['section_id']) ? (int)$chart_row['section_id'] : 0;
        $subject_id = isset($chart_row['subject_id']) ? (int)$chart_row['subject_id'] : 0;
        if ($class_id <= 0 || $subject_id <= 0) {
            continue;
        }
        $class_entity_key = $class_id . ':' . $section_id;
        $section_name = isset($chart_row['section_name']) ? trim((string)$chart_row['section_name']) : '';
        $class_name_base = trim((string)($chart_row['class_name'] ?? ''));
        $semester_value = isset($chart_row['semester']) ? trim((string)$chart_row['semester']) : '';
        $class_label_parts = [];
        if ($class_name_base !== '') {
            $class_label_parts[] = $class_name_base;
        }
        if ($semester_value !== '') {
            $class_label_parts[] = '(Sem ' . $semester_value . ')';
        }
        if ($section_name !== '') {
            $class_label_parts[] = '- ' . $section_name;
        }
        $class_label = trim(implode(' ', $class_label_parts));
        if ($class_label === '') {
            $class_label = (string)($chart_row['class_name'] ?? ('Class ' . $class_id));
        }

        if (!isset($class_subject_chart_payload[$class_entity_key])) {
            $class_label_short = $class_label;
            $class_subject_chart_payload[$class_entity_key] = [
                'class_key' => $class_entity_key,
                'class_id' => $class_id,
                'section_id' => $section_id,
                'class_label' => $class_label,
                'class_label_short' => $class_label_short !== '' ? $class_label_short : ('Class ' . $class_id),
                'subjects' => []
            ];
        }

        $subject_name_raw = (string)($chart_row['subject_name'] ?? '');
        $subject_name_display = format_subject_display($subject_name_raw);
        $subject_entry = [
            'subject_id' => $subject_id,
            'subject_name' => $subject_name_display,
            'subject_name_raw' => $subject_name_raw,
            'abbr' => abbreviate_subject_name($subject_name_raw),
            'syllabus_avg' => isset($chart_row['syllabus_avg']) ? (float)$chart_row['syllabus_avg'] : null,
            'mid_avg' => isset($chart_row['mid_avg']) ? (float)$chart_row['mid_avg'] : null
        ];
        $class_subject_chart_payload[$class_entity_key]['subjects'][] = $subject_entry;

        if (!isset($class_rollup[$class_entity_key])) {
            $class_rollup[$class_entity_key] = [
                'class_key' => $class_entity_key,
                'class_id' => $class_id,
                'section_id' => $section_id,
                'class_label' => $class_label,
                'class_label_short' => $class_subject_chart_payload[$class_entity_key]['class_label_short'],
                'syllabus_sum' => 0.0,
                'syllabus_count' => 0,
                'mid_sum' => 0.0,
                'mid_count' => 0
            ];
        }
        if ($subject_entry['syllabus_avg'] !== null) {
            $class_rollup[$class_entity_key]['syllabus_sum'] += (float)$subject_entry['syllabus_avg'];
            $class_rollup[$class_entity_key]['syllabus_count']++;
        }
        if ($subject_entry['mid_avg'] !== null) {
            $class_rollup[$class_entity_key]['mid_sum'] += (float)$subject_entry['mid_avg'];
            $class_rollup[$class_entity_key]['mid_count']++;
        }
    }
    mysqli_free_result($class_subject_chart_result);

    foreach ($class_subject_chart_payload as &$class_entry) {
        usort($class_entry['subjects'], static function ($a, $b) {
            return strcmp((string)($a['subject_name'] ?? ''), (string)($b['subject_name'] ?? ''));
        });
    }
    unset($class_entry);

    foreach ($class_rollup as $rollup) {
        $class_chart_summary_payload[] = [
            'class_key' => $rollup['class_key'],
            'class_id' => $rollup['class_id'],
            'section_id' => $rollup['section_id'],
            'class_label' => $rollup['class_label'],
            'class_label_short' => $rollup['class_label_short'],
            'syllabus_avg' => $rollup['syllabus_count'] > 0 ? round($rollup['syllabus_sum'] / $rollup['syllabus_count'], 2) : null,
            'mid_avg' => $rollup['mid_count'] > 0 ? round($rollup['mid_sum'] / $rollup['mid_count'], 2) : null
        ];
    }
    usort($class_chart_summary_payload, static function ($a, $b) {
        return strcmp((string)($a['class_label'] ?? ''), (string)($b['class_label'] ?? ''));
    });
}

$subject_chart_meta = [];
$chart_subject_ids = [];
foreach ($syllabus_chart_data as $entry) {
    $subject_id = $entry['subject_id'] ?? null;
    if ($subject_id === null) {
        continue;
    }
    $chart_subject_ids[$subject_id] = true;
    $subject_chart_meta[$subject_id] = [
        'subjectId' => $subject_id,
        'name' => $entry['subject_name'],
        'abbreviation' => $entry['abbr'],
        'semester' => $entry['semester'],
        'school' => $entry['school'],
        'syllabus' => [
            'week3' => $entry['week3'],
            'week5' => $entry['week5'],
            'week10' => $entry['week10'],
            'final' => $entry['final'],
            'latest' => $entry['latest']
        ],
        'midAverage' => null,
        'teachers' => [],
        'classes' => []
    ];
}
foreach ($mid_perf_data as &$mid_entry) {
    $subject_id = $mid_entry['subject_id'] ?? null;
    if ($subject_id === null) {
        continue;
    }
    $chart_subject_ids[$subject_id] = true;
    $semester = $mid_entry['semester'] ?? null;
    $school = $mid_entry['school'] ?? null;
    if (!isset($subject_chart_meta[$subject_id])) {
        $subject_chart_meta[$subject_id] = [
            'subjectId' => $subject_id,
            'name' => $mid_entry['subject_name'],
            'abbreviation' => $mid_entry['abbr'],
            'semester' => $semester,
            'school' => $school,
            'syllabus' => [
                'week3' => null,
                'week5' => null,
                'week10' => null,
                'final' => null,
                'latest' => null
            ],
            'midAverage' => null,
            'teachers' => [],
            'classes' => []
        ];
    } else {
        if (($subject_chart_meta[$subject_id]['semester'] ?? null) === null && $semester !== null) {
            $subject_chart_meta[$subject_id]['semester'] = $semester;
        }
        if (($subject_chart_meta[$subject_id]['school'] ?? null) === null && $school !== null) {
            $subject_chart_meta[$subject_id]['school'] = $school;
        }
    }
    if ($mid_entry['mid_avg'] !== null) {
        $subject_chart_meta[$subject_id]['midAverage'] = round($mid_entry['mid_avg'], 2);
    }
}
unset($mid_entry);

$subject_ids_for_meta = array_keys($chart_subject_ids);
if (!empty($subject_ids_for_meta)) {
    $subject_id_list = implode(',', array_map('intval', $subject_ids_for_meta));
    $teacher_class_sql = "SELECT DISTINCT
            tsa.subject_id,
            u.name AS teacher_name,
            c.class_name,
            c.semester,
            c.school,
            COALESCE(sec.section_name, '') AS section_name
        FROM teacher_subject_assignments tsa
        LEFT JOIN users u ON u.id = tsa.teacher_id
        INNER JOIN classes c ON c.id = tsa.class_id
        LEFT JOIN sections sec ON sec.id = tsa.section_id
        INNER JOIN subjects s ON s.id = tsa.subject_id
        WHERE tsa.subject_id IN ($subject_id_list)" . ($assignmentSubjectAnd !== '' ? $assignmentSubjectAnd : '') . "
        ORDER BY c.class_name";
    $teacher_class_res = mysqli_query($conn, $teacher_class_sql);
    if ($teacher_class_res) {
        while ($row = mysqli_fetch_assoc($teacher_class_res)) {
            $sid = (int)$row['subject_id'];
            if (!isset($subject_chart_meta[$sid])) {
                continue;
            }
            $teacherNameRaw = isset($row['teacher_name']) ? trim((string)$row['teacher_name']) : '';
            if ($teacherNameRaw !== '') {
                $teacherNameDisplay = format_person_display($teacherNameRaw);
                if (!in_array($teacherNameDisplay, $subject_chart_meta[$sid]['teachers'], true)) {
                    $subject_chart_meta[$sid]['teachers'][] = $teacherNameDisplay;
                }
            }
            $classLabel = format_class_label(
                $row['class_name'] ?? '',
                $row['section_name'] ?? '',
                $row['semester'] ?? '',
                $row['school'] ?? ''
            );
            if ($classLabel !== '' && !in_array($classLabel, $subject_chart_meta[$sid]['classes'], true)) {
                $subject_chart_meta[$sid]['classes'][] = $classLabel;
            }
        }
        mysqli_free_result($teacher_class_res);
    }
    foreach ($subject_chart_meta as &$meta) {
        sort($meta['teachers']);
        sort($meta['classes']);
    }
    unset($meta);
}

$subject_chart_meta_payload = [];
foreach ($subject_chart_meta as $subject_id => $meta) {
    $subject_chart_meta_payload[(string)$subject_id] = $meta;
}

// 5. ALERTS + ACTION METRICS
$pending_alerts_count = 0;
$resolved_alerts_count = 0;
$alerts_result = false;

if (!empty($pc_school) && $user_school_field) {
    $alerts_count_q = "SELECT COUNT(*) AS total
        FROM alerts a
        JOIN users u ON a.teacher_id = u.id
        WHERE u.{$user_school_field} = ?
          AND a.status = 'pending'
          AND u.role = 'teacher'" . $alertsDateClause;
    $stmt_alert_count = mysqli_prepare($conn, $alerts_count_q);
    if ($stmt_alert_count) {
        mysqli_stmt_bind_param($stmt_alert_count, "s", $pc_school);
        mysqli_stmt_execute($stmt_alert_count);
        $alert_count_res = mysqli_stmt_get_result($stmt_alert_count);
        if ($alert_count_res && ($count_row = mysqli_fetch_assoc($alert_count_res))) {
            $pending_alerts_count = (int)($count_row['total'] ?? 0);
        }
        if ($alert_count_res) {
            mysqli_free_result($alert_count_res);
        }
        mysqli_stmt_close($stmt_alert_count);
    }

    if ($pending_alerts_count === 0 && $user_alt_field) {
        $alerts_count_q_alt = "SELECT COUNT(*) AS total
            FROM alerts a
            JOIN users u ON a.teacher_id = u.id
            WHERE u.{$user_alt_field} = ?
              AND a.status = 'pending'
              AND u.role = 'teacher'" . $alertsDateClause;
        $stmt_alert_count_alt = mysqli_prepare($conn, $alerts_count_q_alt);
        if ($stmt_alert_count_alt) {
            mysqli_stmt_bind_param($stmt_alert_count_alt, "s", $pc_school);
            mysqli_stmt_execute($stmt_alert_count_alt);
            $alert_count_res_alt = mysqli_stmt_get_result($stmt_alert_count_alt);
            if ($alert_count_res_alt && ($count_row_alt = mysqli_fetch_assoc($alert_count_res_alt))) {
                $pending_alerts_count = (int)($count_row_alt['total'] ?? 0);
            }
            if ($alert_count_res_alt) {
                mysqli_free_result($alert_count_res_alt);
            }
            mysqli_stmt_close($stmt_alert_count_alt);
        }
    }
} else {
    $pending_count_res = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM alerts a JOIN users u ON a.teacher_id = u.id WHERE a.status = 'pending' AND u.role = 'teacher'" . $alertsDateClause
    );
    if ($pending_count_res && ($pending_count_row = mysqli_fetch_assoc($pending_count_res))) {
        $pending_alerts_count = (int)($pending_count_row['total'] ?? 0);
        mysqli_free_result($pending_count_res);
    }
}

if (!empty($pc_school) && $user_school_field) {
    $resolved_alerts_q = "SELECT COUNT(*) AS total
        FROM alerts a
        JOIN users u ON a.teacher_id = u.id
        WHERE u.{$user_school_field} = ?
          AND a.status IN ('responded', 'resolved')
          AND u.role = 'teacher'" . $alertsDateClause;
    $stmt_resolved_alerts = mysqli_prepare($conn, $resolved_alerts_q);
    if ($stmt_resolved_alerts) {
        mysqli_stmt_bind_param($stmt_resolved_alerts, "s", $pc_school);
        mysqli_stmt_execute($stmt_resolved_alerts);
        $resolved_alerts_res = mysqli_stmt_get_result($stmt_resolved_alerts);
        if ($resolved_alerts_res && ($resolved_row = mysqli_fetch_assoc($resolved_alerts_res))) {
            $resolved_alerts_count = (int)($resolved_row['total'] ?? 0);
        }
        if ($resolved_alerts_res) {
            mysqli_free_result($resolved_alerts_res);
        }
        mysqli_stmt_close($stmt_resolved_alerts);
    }

    if ($resolved_alerts_count === 0 && $user_alt_field) {
        $resolved_alerts_q_alt = "SELECT COUNT(*) AS total
            FROM alerts a
            JOIN users u ON a.teacher_id = u.id
            WHERE u.{$user_alt_field} = ?
              AND a.status IN ('responded', 'resolved')
              AND u.role = 'teacher'" . $alertsDateClause;
        $stmt_resolved_alerts_alt = mysqli_prepare($conn, $resolved_alerts_q_alt);
        if ($stmt_resolved_alerts_alt) {
            mysqli_stmt_bind_param($stmt_resolved_alerts_alt, "s", $pc_school);
            mysqli_stmt_execute($stmt_resolved_alerts_alt);
            $resolved_alerts_res_alt = mysqli_stmt_get_result($stmt_resolved_alerts_alt);
            if ($resolved_alerts_res_alt && ($resolved_row_alt = mysqli_fetch_assoc($resolved_alerts_res_alt))) {
                $resolved_alerts_count = (int)($resolved_row_alt['total'] ?? 0);
            }
            if ($resolved_alerts_res_alt) {
                mysqli_free_result($resolved_alerts_res_alt);
            }
            mysqli_stmt_close($stmt_resolved_alerts_alt);
        }
    }
} else {
    $resolved_alerts_res = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM alerts a JOIN users u ON a.teacher_id = u.id WHERE a.status IN ('responded', 'resolved') AND u.role = 'teacher'" . $alertsDateClause
    );
    if ($resolved_alerts_res && ($resolved_row = mysqli_fetch_assoc($resolved_alerts_res))) {
        $resolved_alerts_count = (int)($resolved_row['total'] ?? 0);
        mysqli_free_result($resolved_alerts_res);
    }
}

// Pending alerts table: if we have a pc_school value, filter by it; otherwise show all pending alerts.
if (!empty($pc_school) && $user_school_field) {
    $alerts_q = "SELECT u.name as teacher_name, a.message, a.created_at, a.responded_at, a.status FROM alerts a JOIN users u ON a.teacher_id = u.id WHERE u.{$user_school_field} = ? AND a.status = 'pending' AND u.role = 'teacher'" . $alertsDateClause . " ORDER BY a.created_at DESC";
    $stmt_alerts = mysqli_prepare($conn, $alerts_q);
    if ($stmt_alerts) {
        mysqli_stmt_bind_param($stmt_alerts, "s", $pc_school);
        mysqli_stmt_execute($stmt_alerts);
        $alerts_result = mysqli_stmt_get_result($stmt_alerts);
        if ($alerts_result && mysqli_num_rows($alerts_result) === 0 && $user_alt_field) {
            mysqli_stmt_close($stmt_alerts);
            $alerts_q_alt = "SELECT u.name as teacher_name, a.message, a.created_at, a.responded_at, a.status FROM alerts a JOIN users u ON a.teacher_id = u.id WHERE u.{$user_alt_field} = ? AND a.status = 'pending' AND u.role = 'teacher'" . $alertsDateClause . " ORDER BY a.created_at DESC";
            $stmt_alerts_alt = mysqli_prepare($conn, $alerts_q_alt);
            if ($stmt_alerts_alt) {
                mysqli_stmt_bind_param($stmt_alerts_alt, "s", $pc_school);
                mysqli_stmt_execute($stmt_alerts_alt);
                $alerts_result_alt = mysqli_stmt_get_result($stmt_alerts_alt);
                if ($alerts_result_alt && mysqli_num_rows($alerts_result_alt) > 0) {
                    $alerts_result = $alerts_result_alt;
                    $pending_alerts_count = mysqli_num_rows($alerts_result_alt);
                }
                mysqli_stmt_close($stmt_alerts_alt);
            }
        } elseif ($alerts_result) {
            $pending_alerts_count = mysqli_num_rows($alerts_result);
        } else {
            mysqli_stmt_close($stmt_alerts);
        }
    }
} else {
    $alerts_q = "SELECT u.name as teacher_name, a.message, a.created_at, a.responded_at, a.status FROM alerts a JOIN users u ON a.teacher_id = u.id WHERE a.status = 'pending' AND u.role = 'teacher'" . $alertsDateClause . " ORDER BY a.created_at DESC";
    $alerts_result = mysqli_query($conn, $alerts_q);
    if ($alerts_result) {
        $pending_alerts_count = mysqli_num_rows($alerts_result);
    }
}

$teacher_scope_ids = [];
foreach ($teacher_subject_pairs as $pair) {
    $tid = isset($pair['teacher_id']) ? (int)$pair['teacher_id'] : 0;
    if ($tid > 0) {
        $teacher_scope_ids[$tid] = true;
    }
}
$teachers_monitored = count($teacher_scope_ids);

$students_in_scope = 0;
$students_status_where = '';
if ($status_filter === 'at_risk') {
    $students_status_where = ' WHERE scoped_students.evaluated_components > 0 AND scoped_students.avg_marks < 50';
} elseif ($status_filter === 'average') {
    $students_status_where = ' WHERE scoped_students.evaluated_components > 0 AND scoped_students.avg_marks >= 50 AND scoped_students.avg_marks < 70';
} elseif ($status_filter === 'good') {
    $students_status_where = ' WHERE scoped_students.evaluated_components > 0 AND scoped_students.avg_marks >= 70';
}
$students_in_scope_q = "SELECT COUNT(*) AS total_students
    FROM (
        SELECT
            st.id,
            AVG(
                CASE
                    WHEN ic.marks_per_instance IS NOT NULL
                         AND ic.marks_per_instance <> 0
                         AND ism.marks IS NOT NULL
                    THEN (ism.marks / ic.marks_per_instance) * 100
                    ELSE NULL
                END
            ) AS avg_marks,
            SUM(
                CASE
                    WHEN ic.marks_per_instance IS NOT NULL
                         AND ic.marks_per_instance <> 0
                         AND ism.marks IS NOT NULL
                    THEN 1
                    ELSE 0
                END
            ) AS evaluated_components
        FROM students st
        JOIN (
            SELECT DISTINCT class_id, section_id
            FROM (
                $assignment_scope_sql
            ) scope_rows
        ) scope ON scope.class_id = st.class_id
               AND (scope.section_id = 0 OR COALESCE(st.section_id, 0) = scope.section_id)
        LEFT JOIN ica_student_marks ism ON ism.student_id = st.id" . ($marksDateCondition ? " AND {$marksDateCondition}" : '') . "
        LEFT JOIN ica_components ic ON ic.id = ism.component_id
            AND EXISTS (
                SELECT 1
                FROM teacher_subject_assignments tsa_scope
                WHERE tsa_scope.subject_id = ic.subject_id
                  AND tsa_scope.class_id = st.class_id
                  AND (COALESCE(tsa_scope.section_id, 0) = 0 OR COALESCE(st.section_id, 0) = COALESCE(tsa_scope.section_id, 0))
            )
        GROUP BY st.id
    ) scoped_students" . $students_status_where;
$students_in_scope_res = mysqli_query($conn, $students_in_scope_q);
if ($students_in_scope_res && ($students_scope_row = mysqli_fetch_assoc($students_in_scope_res))) {
    $students_in_scope = (int)($students_scope_row['total_students'] ?? 0);
    mysqli_free_result($students_in_scope_res);
}

$unevaluated_students = 0;
$unevaluated_students_q = "SELECT COUNT(*) AS total_unevaluated
    FROM (
        SELECT
            st.id,
            SUM(
                CASE
                    WHEN ic.marks_per_instance IS NOT NULL
                         AND ic.marks_per_instance <> 0
                         AND ism.marks IS NOT NULL
                    THEN 1
                    ELSE 0
                END
            ) AS evaluated_components
        FROM students st
        JOIN (
            SELECT DISTINCT class_id, section_id
            FROM (
                $assignment_scope_sql
            ) scope_rows
        ) scope ON scope.class_id = st.class_id
               AND (scope.section_id = 0 OR COALESCE(st.section_id, 0) = scope.section_id)
        LEFT JOIN ica_student_marks ism ON ism.student_id = st.id" . ($marksDateCondition ? " AND {$marksDateCondition}" : '') . "
        LEFT JOIN ica_components ic ON ic.id = ism.component_id
            AND EXISTS (
                SELECT 1
                FROM teacher_subject_assignments tsa_scope
                WHERE tsa_scope.subject_id = ic.subject_id
                  AND tsa_scope.class_id = st.class_id
                  AND (COALESCE(tsa_scope.section_id, 0) = 0 OR COALESCE(st.section_id, 0) = COALESCE(tsa_scope.section_id, 0))
            )
        GROUP BY st.id
    ) scoped_students
    WHERE scoped_students.evaluated_components = 0";
$unevaluated_students_res = mysqli_query($conn, $unevaluated_students_q);
if ($unevaluated_students_res && ($unevaluated_row = mysqli_fetch_assoc($unevaluated_students_res))) {
    $unevaluated_students = (int)($unevaluated_row['total_unevaluated'] ?? 0);
    mysqli_free_result($unevaluated_students_res);
}

$coverage_total_students = 0;
$coverage_evaluated_students = 0;
$teacher_coverage_rollup = [];
foreach ($teacher_progress_meta as $teacher_id => $subjects_meta) {
    $teacher_total = 0;
    $teacher_evaluated = 0;
    foreach ($subjects_meta as $meta) {
        $subject_total = (int)($meta['total_students'] ?? 0);
        $subject_evaluated = (int)($meta['evaluated_students'] ?? 0);
        $teacher_total += $subject_total;
        $teacher_evaluated += min($subject_total, $subject_evaluated);
        $coverage_total_students += $subject_total;
        $coverage_evaluated_students += min($subject_total, $subject_evaluated);
    }
    if ($teacher_total > 0) {
        $teacher_coverage_rollup[(int)$teacher_id] = [
            'total' => $teacher_total,
            'evaluated' => $teacher_evaluated,
            'coverage_pct' => round(($teacher_evaluated / $teacher_total) * 100, 1)
        ];
    }
}

$evaluation_coverage_pct = $coverage_total_students > 0
    ? round(($coverage_evaluated_students / $coverage_total_students) * 100)
    : null;

$teacher_low_coverage_count = 0;
foreach ($teacher_coverage_rollup as $rollup) {
    if (($rollup['coverage_pct'] ?? 0) < 50) {
        $teacher_low_coverage_count++;
    }
}

$avg_ica_performance = null;
$ica_samples = [];
foreach ($teacher_ica_data as $teacher_course_scores) {
    foreach ($teacher_course_scores as $course_score) {
        if (is_numeric($course_score)) {
            $ica_samples[] = (float)$course_score;
        }
    }
}
if (!empty($ica_samples)) {
    $avg_ica_performance = round(array_sum($ica_samples) / count($ica_samples), 1);
}

$below_threshold_courses = 0;
$no_recent_updates = 0;
$todayTs = time();
$teacher_latest_updates = [];
$weak_course_risks = [];

if ($teacher_performance_result) {
    mysqli_data_seek($teacher_performance_result, 0);
    while ($metrics_row = mysqli_fetch_assoc($teacher_performance_result)) {
        $completion_value = isset($metrics_row['avg_completion']) ? (float)$metrics_row['avg_completion'] : 0.0;
        $teacher_id_metrics = isset($metrics_row['teacher_id']) ? (int)$metrics_row['teacher_id'] : 0;
        $course_name_raw = isset($metrics_row['course_name']) ? (string)$metrics_row['course_name'] : '';
        $course_name_display = format_subject_display($course_name_raw);
        $class_label_raw = isset($metrics_row['class_label']) ? trim((string)$metrics_row['class_label']) : '';
        $class_label_display = $class_label_raw !== '' ? $class_label_raw : '—';
        $teacher_name_raw = isset($metrics_row['teacher_name']) ? trim((string)$metrics_row['teacher_name']) : '';
        $teacher_name_display = $teacher_name_raw !== '' ? format_person_display($teacher_name_raw) : ('Teacher ' . $teacher_id_metrics);

        if ($completion_value + 0.0001 < $syllabus_threshold) {
            $below_threshold_courses++;
        }

        $mid_avg_value = null;
        if ($teacher_id_metrics > 0 && isset($teacher_ica_data[$teacher_id_metrics][$course_name_raw]) && is_numeric($teacher_ica_data[$teacher_id_metrics][$course_name_raw])) {
            $mid_avg_value = (float)$teacher_ica_data[$teacher_id_metrics][$course_name_raw];
        }

        $has_syllabus_risk = $completion_value + 0.0001 < $syllabus_threshold;
        $has_mid_risk = $mid_avg_value !== null && ($mid_avg_value + 0.0001 < $performance_threshold);
        if ($has_syllabus_risk || $has_mid_risk) {
            $risk_score = max(0, $syllabus_threshold - $completion_value);
            if ($mid_avg_value !== null) {
                $risk_score += max(0, $performance_threshold - $mid_avg_value);
            }
            $weak_course_risks[] = [
                'course_name' => $course_name_display,
                'class_label' => $class_label_display,
                'teacher_name' => $teacher_name_display,
                'syllabus_pct' => round($completion_value),
                'mid_avg_pct' => $mid_avg_value !== null ? round($mid_avg_value, 1) : null,
                'risk_score' => $risk_score
            ];
        }

        if ($teacher_id_metrics > 0) {
            if (!isset($teacher_latest_updates[$teacher_id_metrics])) {
                $teacher_latest_updates[$teacher_id_metrics] = [
                    'teacher_name' => $teacher_name_display,
                    'last_ts' => null,
                    'last_update_text' => 'No update logged'
                ];
            }
            $last_updated_raw = isset($metrics_row['last_updated']) ? trim((string)$metrics_row['last_updated']) : '';
            if ($last_updated_raw !== '') {
                $last_ts = strtotime($last_updated_raw);
                if ($last_ts !== false && ($teacher_latest_updates[$teacher_id_metrics]['last_ts'] === null || $last_ts > $teacher_latest_updates[$teacher_id_metrics]['last_ts'])) {
                    $teacher_latest_updates[$teacher_id_metrics]['last_ts'] = $last_ts;
                    $teacher_latest_updates[$teacher_id_metrics]['last_update_text'] = date('d M Y, h:i A', $last_ts);
                }
            }
        }
    }

    mysqli_data_seek($teacher_performance_result, 0);
}

$stale_teacher_details = [];
foreach ($teacher_latest_updates as $teacher_update) {
    $days_since_update = null;
    $is_stale = true;
    if ($teacher_update['last_ts'] !== null) {
        $days_since_update = (int)floor(($todayTs - (int)$teacher_update['last_ts']) / 86400);
        $is_stale = $days_since_update > 7;
    }
    if ($is_stale) {
        $stale_teacher_details[] = [
            'teacher_name' => $teacher_update['teacher_name'],
            'days_since' => $days_since_update,
            'last_update_text' => $teacher_update['last_update_text']
        ];
    }
}

usort($stale_teacher_details, static function (array $a, array $b): int {
    $left = $a['days_since'] ?? PHP_INT_MAX;
    $right = $b['days_since'] ?? PHP_INT_MAX;
    return $right <=> $left;
});
$no_recent_updates = count($stale_teacher_details);
$top_pending_faculty = array_slice($stale_teacher_details, 0, 5);

usort($weak_course_risks, static function (array $a, array $b): int {
    if ($a['risk_score'] === $b['risk_score']) {
        return $a['syllabus_pct'] <=> $b['syllabus_pct'];
    }
    return $b['risk_score'] <=> $a['risk_score'];
});
$top_risk_courses = array_slice($weak_course_risks, 0, 5);

$low_mid_subjects = 0;
foreach ($mid_perf_data as $mid_row) {
    if (!isset($mid_row['mid_avg']) || $mid_row['mid_avg'] === null) {
        continue;
    }
    if ((float)$mid_row['mid_avg'] + 0.0001 < $performance_threshold) {
        $low_mid_subjects++;
    }
}

$student_risk_threshold = 50;
$top_risk_students = [];
$at_risk_students_q = "SELECT
        st.id AS student_id,
        st.name AS student_name,
        st.roll_number,
        st.sap_id,
        cls.class_name,
        COALESCE(sec.section_name, '') AS section_name,
        cls.semester,
        cls.school,
        AVG(
            CASE
                WHEN ic.marks_per_instance IS NOT NULL
                     AND ic.marks_per_instance <> 0
                     AND ism.marks IS NOT NULL
                THEN (ism.marks / ic.marks_per_instance) * 100
                ELSE NULL
            END
        ) AS avg_pct,
        SUM(
            CASE
                WHEN ic.marks_per_instance IS NOT NULL
                     AND ic.marks_per_instance <> 0
                     AND ism.marks IS NOT NULL
                THEN 1
                ELSE 0
            END
        ) AS evaluated_components
    FROM students st
    JOIN (
        SELECT DISTINCT class_id, section_id
        FROM (
            $assignment_scope_sql
        ) scope_rows
    ) scope ON scope.class_id = st.class_id
           AND (scope.section_id = 0 OR COALESCE(st.section_id, 0) = scope.section_id)
    LEFT JOIN classes cls ON cls.id = st.class_id
    LEFT JOIN sections sec ON sec.id = st.section_id
    LEFT JOIN ica_student_marks ism ON ism.student_id = st.id" . ($marksDateCondition ? " AND {$marksDateCondition}" : '') . "
    LEFT JOIN ica_components ic ON ic.id = ism.component_id
        AND EXISTS (
            SELECT 1
            FROM teacher_subject_assignments tsa_scope
            WHERE tsa_scope.subject_id = ic.subject_id
              AND tsa_scope.class_id = st.class_id
              AND (COALESCE(tsa_scope.section_id, 0) = 0 OR COALESCE(st.section_id, 0) = COALESCE(tsa_scope.section_id, 0))
        )
    GROUP BY st.id, st.name, st.roll_number, st.sap_id, cls.class_name, sec.section_name, cls.semester, cls.school
    HAVING evaluated_components > 0 AND avg_pct < " . (int)$student_risk_threshold . "
    ORDER BY avg_pct ASC, evaluated_components DESC
    LIMIT 5";
$at_risk_students_res = mysqli_query($conn, $at_risk_students_q);
if ($at_risk_students_res) {
    while ($risk_student_row = mysqli_fetch_assoc($at_risk_students_res)) {
        $student_name_raw = isset($risk_student_row['student_name']) ? trim((string)$risk_student_row['student_name']) : '';
        $student_name_display = $student_name_raw !== '' ? format_person_display($student_name_raw) : 'Student';
        $student_identifier = isset($risk_student_row['roll_number']) ? trim((string)$risk_student_row['roll_number']) : '';
        if ($student_identifier === '' && isset($risk_student_row['sap_id'])) {
            $student_identifier = trim((string)$risk_student_row['sap_id']);
        }
        if ($student_identifier === '') {
            $student_identifier = 'ID ' . (int)($risk_student_row['student_id'] ?? 0);
        }

        $student_class_label = format_class_label(
            (string)($risk_student_row['class_name'] ?? ''),
            (string)($risk_student_row['section_name'] ?? ''),
            (string)($risk_student_row['semester'] ?? ''),
            (string)($risk_student_row['school'] ?? '')
        );
        if ($student_class_label === '') {
            $student_class_label = '—';
        }

        $top_risk_students[] = [
            'student_name' => $student_name_display,
            'student_identifier' => $student_identifier,
            'class_label' => $student_class_label,
            'avg_pct' => round((float)($risk_student_row['avg_pct'] ?? 0), 1)
        ];
    }
    mysqli_free_result($at_risk_students_res);
}

$action_center_items = [];
if ($below_threshold_courses > 0) {
    $action_center_items[] = [
        'title' => 'Courses behind syllabus target',
        'count' => $below_threshold_courses,
        'severity' => metric_severity_from_count($below_threshold_courses, 3, 8),
        'detail' => 'Coverage is below ' . (int)round($syllabus_threshold) . '% for mapped course allocations.',
        'primary_label' => 'View Courses',
        'primary_link' => $course_progress_link,
        'secondary_label' => 'Send Reminder',
        'secondary_link' => $alerts_link
    ];
}
if ($low_mid_subjects > 0) {
    $action_center_items[] = [
        'title' => 'Weak mid-exam outcomes',
        'count' => $low_mid_subjects,
        'severity' => metric_severity_from_count($low_mid_subjects, 3, 6),
        'detail' => 'Mid average is below ' . (int)round($performance_threshold) . '% in these subjects.',
        'primary_label' => 'Open Student View',
        'primary_link' => $student_at_risk_link,
        'secondary_label' => 'Review Teachers',
        'secondary_link' => $teacher_at_risk_link
    ];
}
if ($no_recent_updates > 0) {
    $action_center_items[] = [
        'title' => 'Faculty progress updates overdue',
        'count' => $no_recent_updates,
        'severity' => metric_severity_from_count($no_recent_updates, 2, 5),
        'detail' => 'No syllabus update posted in the last 7 days.',
        'primary_label' => 'View Faculty',
        'primary_link' => $teacher_at_risk_link,
        'secondary_label' => 'Send Reminder',
        'secondary_link' => $alerts_link
    ];
}
if ($pending_alerts_count > 0) {
    $action_center_items[] = [
        'title' => 'Open alerts waiting for response',
        'count' => $pending_alerts_count,
        'severity' => metric_severity_from_count($pending_alerts_count, 3, 8),
        'detail' => 'Alerts are pending acknowledgment from faculty.',
        'primary_label' => 'Open Alerts',
        'primary_link' => $alerts_link,
        'secondary_label' => 'Track Teachers',
        'secondary_link' => $teacher_progress_link
    ];
}
if (empty($action_center_items)) {
    $action_center_items[] = [
        'title' => 'No high-priority action pending',
        'count' => 0,
        'severity' => 'low',
        'detail' => 'Current filters show healthy progress and no urgent intervention required.',
        'primary_label' => 'View Reports',
        'primary_link' => 'program_reports.php',
        'secondary_label' => '',
        'secondary_link' => ''
    ];
}

$pending_academic_actions = [];
if ($below_threshold_courses > 0) {
    $pending_academic_actions[] = [
        'item' => 'Courses below syllabus threshold',
        'count' => $below_threshold_courses,
        'owner' => 'Course Coordinators',
        'severity' => metric_severity_from_count($below_threshold_courses, 3, 8),
        'link' => $course_progress_link,
        'link_label' => 'Open Courses'
    ];
}
if ($teacher_low_coverage_count > 0) {
    $pending_academic_actions[] = [
        'item' => 'Faculty below 50% evaluation coverage',
        'count' => $teacher_low_coverage_count,
        'owner' => 'Faculty',
        'severity' => metric_severity_from_count($teacher_low_coverage_count, 2, 5),
        'link' => $teacher_at_risk_link,
        'link_label' => 'Open Faculty'
    ];
}
if ($low_performing_students > 0) {
    $pending_academic_actions[] = [
        'item' => 'Students below 50% aggregate',
        'count' => $low_performing_students,
        'owner' => 'Mentors',
        'severity' => metric_severity_from_count($low_performing_students, 10, 30),
        'link' => $student_at_risk_link,
        'link_label' => 'Open Students'
    ];
}
if ($pending_alerts_count > 0) {
    $pending_academic_actions[] = [
        'item' => 'Pending faculty alerts',
        'count' => $pending_alerts_count,
        'owner' => 'Program Office',
        'severity' => metric_severity_from_count($pending_alerts_count, 3, 8),
        'link' => $alerts_link,
        'link_label' => 'Open Alerts'
    ];
}
if ($unevaluated_students > 0) {
    $pending_academic_actions[] = [
        'item' => 'Students with zero evaluation entries',
        'count' => $unevaluated_students,
        'owner' => 'Subject Faculty',
        'severity' => metric_severity_from_count($unevaluated_students, 10, 25),
        'link' => $students_page_link,
        'link_label' => 'Open Student List'
    ];
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename_parts = ['Program_Dashboard'];
    if ($school_filter_display !== '') {
        $filename_parts[] = preg_replace('/[^A-Za-z0-9]+/', '_', $school_filter_display);
    }
    if ($semester_filter !== '') {
        $filename_parts[] = 'Sem_' . preg_replace('/[^A-Za-z0-9]+/', '_', (string)$semester_filter);
    }
    if ($class_filter > 0) {
        $filename_parts[] = 'Class_' . $class_filter;
    }
    if ($status_filter !== '') {
        $filename_parts[] = 'Status_' . $status_filter;
    }
    $filename_parts[] = date('Y-m-d');
    $filename = implode('_', array_filter($filename_parts)) . '.csv';
    $filename = preg_replace('/_+/', '_', $filename);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Teacher', 'Course', 'Class', 'Syllabus (%)', 'Mid Avg (%)', 'Evaluated', 'Total', 'At-Risk', 'Last Update']);

    if ($teacher_performance_result && mysqli_num_rows($teacher_performance_result) > 0) {
        mysqli_data_seek($teacher_performance_result, 0);
        while ($row = mysqli_fetch_assoc($teacher_performance_result)) {
            $teacher_name = isset($row['teacher_name']) ? trim((string)$row['teacher_name']) : '';
            $teacher_name = $teacher_name !== '' ? format_person_display($teacher_name) : '';
            $course_name = format_subject_display((string)($row['course_name'] ?? ''));
            $class_label = isset($row['class_label']) && trim((string)$row['class_label']) !== '' ? trim((string)$row['class_label']) : '—';
            $completion = isset($row['avg_completion']) ? (float)$row['avg_completion'] : 0.0;
            $mid_avg = isset($teacher_ica_data[$row['teacher_id']][$row['course_name']]) ? (float)$teacher_ica_data[$row['teacher_id']][$row['course_name']] : null;
            $meta = $teacher_progress_meta[$row['teacher_id']][$row['course_name']] ?? ['total_students' => 0, 'evaluated_students' => 0, 'at_risk_students' => 0];
            $last_updated = !empty($row['last_updated']) ? date('d M Y, h:i A', strtotime((string)$row['last_updated'])) : '—';

            fputcsv($output, [
                $teacher_name,
                $course_name,
                $class_label,
                round($completion),
                $mid_avg !== null ? round($mid_avg, 2) : 'N/A',
                (int)($meta['evaluated_students'] ?? 0),
                (int)($meta['total_students'] ?? 0),
                (int)($meta['at_risk_students'] ?? 0),
                $last_updated
            ]);
        }
    }

    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Chair Dashboard - ICA Tracker</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="ica_tracker.css">
    <style>
        .sa-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:9px; margin-bottom:14px; }
        .sa-stat  { min-width:0; background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%); border-radius:10px; border:1px solid #e5e7eb; padding:8px 9px; display:flex; align-items:center; gap:8px; box-shadow:0 1px 4px rgba(0,0,0,.05); text-decoration:none; transition:transform .15s,box-shadow .15s,border-color .15s; }
        .sa-stat:hover { transform:translateY(-1px); box-shadow:0 4px 10px rgba(166,25,46,.10); border-color:#d9dee7; }
        .sa-stat-icon { width:30px;height:30px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0; }
        .si-red{ background:rgba(166,25,46,.1); color:#A6192E; }
        .si-blue{ background:rgba(37,99,235,.1); color:#2563eb; }
        .si-amber{ background:rgba(217,119,6,.1); color:#d97706; }
        .si-purple{ background:rgba(124,58,237,.1); color:#7c3aed; }
        .si-green{ background:rgba(22,163,74,.1); color:#16a34a; }
        .si-teal{ background:rgba(13,148,136,.1); color:#0d9488; }
        .sa-stat-info { min-width:0; }
        .sa-stat-info h4 { margin:0 0 2px; font-size:.6rem; color:#6b7280; text-transform:uppercase; letter-spacing:.04em; line-height:1.15; }
        .sa-stat-val { font-size:1.08rem; font-weight:700; color:#111827; line-height:1; }
        .sa-stat-sub { font-size:.62rem; color:#94a3b8; margin-top:2px; line-height:1.2; }
        .section-label { font-size:.72rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.06em; margin:0 0 8px; }
        .charts-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px; }
        .charts-row2 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:12px; }
        @media(max-width:900px){
            .charts-row,.charts-row2{ grid-template-columns:1fr; }
            .sa-stats { gap:7px; }
            .sa-stat { padding:7px 7px; gap:6px; border-radius:9px; }
            .sa-stat-icon { width:26px; height:26px; font-size:.8rem; }
            .sa-stat-info h4 { font-size:.54rem; }
            .sa-stat-val { font-size:.92rem; }
            .sa-stat-sub { font-size:.56rem; }
        }
        .chart-card { background:#fff; border-radius:7px; border:1px solid #e5e7eb; padding:10px 13px; box-shadow:0 1px 4px rgba(0,0,0,.06); }
        .chart-card-title { font-size:1rem; font-weight:700; color:#111827; margin-bottom:8px; display:flex; align-items:center; gap:6px; }
        .chart-card-title i { color:#A6192E; font-size:.92rem; }
        .chart-card canvas { max-height:260px; min-height:240px; }
        .syllabus-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 60px;
            padding: 4px 10px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.85rem;
            color: #fff;
            letter-spacing: 0.02em;
        }
        .chip-success { background: #2e7d32; }
        .chip-warning { background: #ed6c02; }
        .chip-danger { background: #c62828; }
        .link-button {
            background: none;
            border: none;
            padding: 0;
            font-weight: 600;
            color: #A6192E;
            cursor: pointer;
            text-decoration: underline;
        }
        .link-button:hover {
            text-decoration: none;
        }
        .detail-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            z-index: 3000;
            align-items: center;
            justify-content: center;
            padding: 30px;
        }
        .detail-modal.show { display: flex; }
        .detail-modal .modal-content {
            background: #fff;
            border-radius: 14px;
            max-width: 720px;
            width: 100%;
            padding: 24px;
            position: relative;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.18);
        }
        .detail-modal .modal-close {
            position: absolute;
            top: 16px;
            right: 18px;
            font-size: 1.5rem;
            border: none;
            background: none;
            cursor: pointer;
            color: #6c757d;
        }
        .detail-section { margin-bottom: 18px; }
        .detail-section:last-child { margin-bottom: 0; }
        .detail-section h5 { margin-bottom: 8px; color: #A6192E; }
        .chip-row { display: flex; flex-wrap: wrap; gap: 8px; }
        .chip-item { background: rgba(166, 25, 46, 0.1); color: #A6192E; padding: 4px 10px; border-radius: 999px; font-size: 0.85rem; }
        .timeline-list { list-style: none; padding: 0; margin: 0; display: grid; gap: 6px; }
        .timeline-list li { display: flex; justify-content: space-between; background: #f8f9fa; padding: 10px; border-radius: 8px; font-weight: 600; }
        .timeline-list li span:last-child { font-weight: 700; color: #2c3e50; }
        .clickable-card { cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .clickable-card:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(166, 25, 46, 0.15); }
        .table-compact th, .table-compact td { padding: 8px 10px; font-size: 0.86rem; }
        .table-compact th { white-space: nowrap; }
        .table-compact td { vertical-align: middle; }
        .table-wrap { overflow-x: auto; }
        .insight-list { margin: 0; padding-left: 18px; line-height: 1.45; }
        .insight-list li + li { margin-top: 4px; }
        .action-center-list { display: grid; gap: 10px; }
        .action-center-item {
            border: 1px solid #e5e7eb;
            border-left: 4px solid #16a34a;
            border-radius: 10px;
            padding: 10px 12px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            background: #ffffff;
        }
        .action-center-item.severity-medium { border-left-color: #d97706; background: #fffaf2; }
        .action-center-item.severity-high { border-left-color: #b91c1c; background: #fff5f5; }
        .action-center-main { min-width: 0; }
        .action-center-title { margin: 0 0 3px; font-size: 0.94rem; font-weight: 700; color: #111827; }
        .action-center-detail { margin: 0; font-size: 0.8rem; color: #6b7280; }
        .action-center-meta { display: flex; align-items: center; gap: 8px; margin-top: 6px; flex-wrap: wrap; }
        .severity-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 3px 9px;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        .severity-pill.low { background: rgba(22, 163, 74, 0.15); color: #166534; }
        .severity-pill.medium { background: rgba(217, 119, 6, 0.16); color: #92400e; }
        .severity-pill.high { background: rgba(185, 28, 28, 0.15); color: #991b1b; }
        .count-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            color: #111827;
            background: #f3f4f6;
        }
        .action-center-links { display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .action-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.78rem;
            font-weight: 600;
            color: #A6192E;
            text-decoration: none;
        }
        .action-link:hover { text-decoration: underline; }
        .pending-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 700;
            background: #f3f4f6;
            color: #111827;
        }
        .risk-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }
        .risk-panel {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px;
            background: #fff;
            min-height: 180px;
        }
        .risk-panel h6 {
            margin: 0 0 8px;
            font-size: 0.84rem;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .risk-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 8px;
        }
        .risk-item {
            border: 1px solid #eceff3;
            border-radius: 8px;
            padding: 8px;
            background: #f8fafc;
        }
        .risk-item-main { font-size: 0.82rem; font-weight: 700; color: #111827; margin: 0 0 3px; }
        .risk-item-sub { font-size: 0.75rem; color: #6b7280; margin: 0; }
        .risk-empty {
            margin: 0;
            font-size: 0.79rem;
            color: #6b7280;
            padding: 10px;
            border: 1px dashed #d1d5db;
            border-radius: 8px;
            background: #f9fafb;
        }
        .analytics-header { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 8px; }
        .analytics-filter { display: inline-flex; align-items: center; gap: 8px; }
        .analytics-filter label { margin: 0; font-size: 0.82rem; color: #6b7280; font-weight: 600; }
        .analytics-filter select { min-width: 210px; max-width: 260px; margin: 0; padding: 6px 10px; font-size: 0.86rem; }
        .analytics-header-right { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:8px; }
        .chart-note { margin: 0 0 8px; font-size: 0.78rem; color: #6b7280; }
        body.program-chair .main-content > .container {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 0;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            align-items: end;
        }
        .filter-grid .form-group {
            margin-bottom: 0;
        }
        @media (max-width: 1100px) {
            .filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 700px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
            .risk-grid {
                grid-template-columns: 1fr;
            }
            .action-center-item {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body class="program-chair">
    <div class="dashboard">
        <div class="sidebar">
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
            <h2>ICA Tracker</h2>
            <a href="program_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="teacher_progress.php"><i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span></a>
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
            <div class="header">
                <h2>Welcome, <?php echo htmlspecialchars($programChairNameDisplay !== '' ? $programChairNameDisplay : $programChairNameRaw); ?>!</h2>
            </div>
            <?php renderTermSwitcher($academicContext, [
                'school_name' => $pc_school,
                'fallback_semester' => $activeTerm['semester_number'] ?? null,
            ]); ?>
            <div class="container">
                <p class="section-label">Overview</p>
                <div class="sa-stats">
                    <a class="sa-stat" href="<?php echo htmlspecialchars($course_progress_link); ?>">
                        <div class="sa-stat-icon si-red"><i class="fas fa-book"></i></div>
                        <div class="sa-stat-info"><h4>Courses Mapped</h4><div class="sa-stat-val"><?php echo $total_courses; ?></div></div>
                    </a>
                    <a class="sa-stat" href="<?php echo htmlspecialchars($teacher_progress_link); ?>">
                        <div class="sa-stat-icon si-blue"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div class="sa-stat-info"><h4>Active Teachers</h4><div class="sa-stat-val"><?php echo $teachers_monitored; ?></div></div>
                    </a>
                    <a class="sa-stat" href="<?php echo htmlspecialchars($students_page_link); ?>">
                        <div class="sa-stat-icon si-teal"><i class="fas fa-user-graduate"></i></div>
                        <div class="sa-stat-info"><h4>Students In Scope</h4><div class="sa-stat-val"><?php echo $students_in_scope; ?></div></div>
                    </a>
                    <a class="sa-stat" href="<?php echo htmlspecialchars($course_progress_link); ?>">
                        <div class="sa-stat-icon si-green"><i class="fas fa-check-double"></i></div>
                        <div class="sa-stat-info"><h4>Avg Syllabus</h4><div class="sa-stat-val"><?php echo $avg_syllabus; ?>%</div></div>
                    </a>
                    <a class="sa-stat" href="<?php echo htmlspecialchars($students_page_link); ?>">
                        <div class="sa-stat-icon si-purple"><i class="fas fa-chart-line"></i></div>
                        <div class="sa-stat-info"><h4>Avg ICA</h4><div class="sa-stat-val"><?php echo $avg_ica_performance !== null ? htmlspecialchars(rtrim(rtrim(number_format((float)$avg_ica_performance, 1, '.', ''), '0'), '.') . '%') : 'N/A'; ?></div></div>
                    </a>
                    <a class="sa-stat" href="<?php echo htmlspecialchars($teacher_progress_link); ?>">
                        <div class="sa-stat-icon si-teal"><i class="fas fa-clipboard-check"></i></div>
                        <div class="sa-stat-info"><h4>Eval Coverage</h4><div class="sa-stat-val"><?php echo $evaluation_coverage_pct !== null ? (int)$evaluation_coverage_pct . '%' : 'N/A'; ?></div></div>
                    </a>
                    <a class="sa-stat" href="<?php echo htmlspecialchars($student_at_risk_link); ?>">
                        <div class="sa-stat-icon si-amber"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="sa-stat-info"><h4>Low Performing</h4><div class="sa-stat-val"><?php echo $low_performing_students; ?></div></div>
                    </a>
                    <a class="sa-stat" href="<?php echo htmlspecialchars($course_progress_link); ?>">
                        <div class="sa-stat-icon si-red"><i class="fas fa-hourglass-half"></i></div>
                        <div class="sa-stat-info"><h4>Courses Behind</h4><div class="sa-stat-val"><?php echo $below_threshold_courses; ?></div></div>
                    </a>
                    <a class="sa-stat" href="<?php echo htmlspecialchars($teacher_at_risk_link); ?>">
                        <div class="sa-stat-icon si-amber"><i class="fas fa-user-clock"></i></div>
                        <div class="sa-stat-info"><h4>Faculty Pending</h4><div class="sa-stat-val"><?php echo $no_recent_updates; ?></div></div>
                    </a>
                    <a class="sa-stat" href="<?php echo htmlspecialchars($alerts_link); ?>">
                        <div class="sa-stat-icon si-purple"><i class="fas fa-bell"></i></div>
                        <div class="sa-stat-info"><h4>Open Alerts</h4><div class="sa-stat-val"><?php echo $pending_alerts_count; ?></div></div>
                    </a>
                    <a class="sa-stat" href="<?php echo htmlspecialchars($alerts_link); ?>">
                        <div class="sa-stat-icon si-green"><i class="fas fa-check-circle"></i></div>
                        <div class="sa-stat-info"><h4>Resolved Alerts</h4><div class="sa-stat-val"><?php echo $resolved_alerts_count; ?></div></div>
                    </a>
                </div>

                <div class="card">
                    <div class="card-header"><h5>Program Chair Action Center</h5></div>
                    <div class="card-body">
                        <div class="action-center-list">
                            <?php foreach ($action_center_items as $action_item): ?>
                                <?php
                                    $action_severity = isset($action_item['severity']) ? (string)$action_item['severity'] : 'low';
                                    if (!in_array($action_severity, ['low', 'medium', 'high'], true)) {
                                        $action_severity = 'low';
                                    }
                                ?>
                                <div class="action-center-item severity-<?php echo htmlspecialchars($action_severity); ?>">
                                    <div class="action-center-main">
                                        <p class="action-center-title"><?php echo htmlspecialchars((string)($action_item['title'] ?? 'Action item')); ?></p>
                                        <p class="action-center-detail"><?php echo htmlspecialchars((string)($action_item['detail'] ?? '')); ?></p>
                                        <div class="action-center-meta">
                                            <span class="severity-pill <?php echo htmlspecialchars($action_severity); ?>"><?php echo htmlspecialchars(ucfirst($action_severity)); ?></span>
                                            <span class="count-pill"><?php echo (int)($action_item['count'] ?? 0); ?></span>
                                        </div>
                                    </div>
                                    <div class="action-center-links">
                                        <?php if (!empty($action_item['primary_link']) && !empty($action_item['primary_label'])): ?>
                                            <a class="action-link" href="<?php echo htmlspecialchars((string)$action_item['primary_link']); ?>"><i class="fas fa-arrow-right"></i><?php echo htmlspecialchars((string)$action_item['primary_label']); ?></a>
                                        <?php endif; ?>
                                        <?php if (!empty($action_item['secondary_link']) && !empty($action_item['secondary_label'])): ?>
                                            <a class="action-link" href="<?php echo htmlspecialchars((string)$action_item['secondary_link']); ?>"><i class="fas fa-bell"></i><?php echo htmlspecialchars((string)$action_item['secondary_label']); ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5>Pending Academic Actions</h5></div>
                    <div class="card-body">
                        <div class="table-wrap">
                            <table class="table-compact">
                                <thead>
                                    <tr>
                                        <th>Action Item</th>
                                        <th>Count</th>
                                        <th>Owner</th>
                                        <th>Priority</th>
                                        <th>Quick Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($pending_academic_actions)): ?>
                                        <?php foreach ($pending_academic_actions as $pending_action): ?>
                                            <?php
                                                $pending_severity = isset($pending_action['severity']) ? (string)$pending_action['severity'] : 'low';
                                                if (!in_array($pending_severity, ['low', 'medium', 'high'], true)) {
                                                    $pending_severity = 'low';
                                                }
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars((string)($pending_action['item'] ?? '')); ?></td>
                                                <td><span class="pending-count"><?php echo (int)($pending_action['count'] ?? 0); ?></span></td>
                                                <td><?php echo htmlspecialchars((string)($pending_action['owner'] ?? '')); ?></td>
                                                <td><span class="severity-pill <?php echo htmlspecialchars($pending_severity); ?>"><?php echo htmlspecialchars(ucfirst($pending_severity)); ?></span></td>
                                                <td>
                                                    <?php if (!empty($pending_action['link']) && !empty($pending_action['link_label'])): ?>
                                                        <a class="action-link" href="<?php echo htmlspecialchars((string)$pending_action['link']); ?>"><?php echo htmlspecialchars((string)$pending_action['link_label']); ?></a>
                                                    <?php else: ?>
                                                        —
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5">No pending academic action for the selected filters.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5>Top Risks This Week</h5></div>
                    <div class="card-body">
                        <div class="risk-grid">
                            <div class="risk-panel">
                                <h6>At-Risk Students</h6>
                                <?php if (!empty($top_risk_students)): ?>
                                    <ul class="risk-list">
                                        <?php foreach ($top_risk_students as $risk_student): ?>
                                            <li class="risk-item">
                                                <p class="risk-item-main"><?php echo htmlspecialchars((string)$risk_student['student_name']); ?> (<?php echo htmlspecialchars((string)$risk_student['student_identifier']); ?>)</p>
                                                <p class="risk-item-sub"><?php echo htmlspecialchars((string)$risk_student['class_label']); ?> | Avg <?php echo htmlspecialchars((string)$risk_student['avg_pct']); ?>%</p>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="risk-empty">No high-risk students found for the selected scope.</p>
                                <?php endif; ?>
                            </div>
                            <div class="risk-panel">
                                <h6>Weak Courses</h6>
                                <?php if (!empty($top_risk_courses)): ?>
                                    <ul class="risk-list">
                                        <?php foreach ($top_risk_courses as $risk_course): ?>
                                            <li class="risk-item">
                                                <p class="risk-item-main"><?php echo htmlspecialchars((string)$risk_course['course_name']); ?> (<?php echo htmlspecialchars((string)$risk_course['class_label']); ?>)</p>
                                                <p class="risk-item-sub">Syllabus <?php echo htmlspecialchars((string)$risk_course['syllabus_pct']); ?>% | Mid <?php echo $risk_course['mid_avg_pct'] !== null ? htmlspecialchars((string)$risk_course['mid_avg_pct']) . '%' : 'N/A'; ?> | <?php echo htmlspecialchars((string)$risk_course['teacher_name']); ?></p>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="risk-empty">No weak course signal detected for the selected scope.</p>
                                <?php endif; ?>
                            </div>
                            <div class="risk-panel">
                                <h6>Faculty Pending Updates</h6>
                                <?php if (!empty($top_pending_faculty)): ?>
                                    <ul class="risk-list">
                                        <?php foreach ($top_pending_faculty as $pending_faculty): ?>
                                            <li class="risk-item">
                                                <p class="risk-item-main"><?php echo htmlspecialchars((string)$pending_faculty['teacher_name']); ?></p>
                                                <p class="risk-item-sub">
                                                    <?php
                                                        if ($pending_faculty['days_since'] !== null) {
                                                            echo htmlspecialchars((string)$pending_faculty['days_since']) . ' days since update';
                                                        } else {
                                                            echo 'No update logged yet';
                                                        }
                                                    ?>
                                                    | Last: <?php echo htmlspecialchars((string)$pending_faculty['last_update_text']); ?>
                                                </p>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="risk-empty">No delayed faculty update signal in this scope.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="filter-header">
                            <h5>Filters &amp; Tools</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" id="filterForm">
                            <div class="filter-grid">
                                <div class="form-group">
                                    <label for="school_filter">School</label>
                                    <select name="school" id="school_filter"></select>
                                </div>
                                <div class="form-group">
                                    <label for="semester_filter">Semester</label>
                                    <select name="semester" id="semester_filter">
                                        <option value="">All semesters</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="class_filter">Class</label>
                                    <select name="class_id" id="class_filter" <?php echo $semester_filter === '' ? 'disabled' : ''; ?>>
                                        <option value=""><?php echo $semester_filter === '' ? 'Select semester' : 'All classes'; ?></option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="status_filter">Status</label>
                                    <select name="status" id="status_filter">
                                        <option value="" <?php echo $status_filter === '' ? 'selected' : ''; ?>>All</option>
                                        <option value="good" <?php echo $status_filter === 'good' ? 'selected' : ''; ?>>Good Standing</option>
                                        <option value="average" <?php echo $status_filter === 'average' ? 'selected' : ''; ?>>Average</option>
                                        <option value="at_risk" <?php echo $status_filter === 'at_risk' ? 'selected' : ''; ?>>At-Risk</option>
                                    </select>
                                </div>
                            </div>
                            <div style="margin-top: 20px; display: flex; gap: 10px;">
                                <button type="submit" class="btn">Apply Filters</button>
                                <button type="button" id="resetFiltersBtn" class="btn" style="background:#63666A;">Reset Filters</button>
                                <button type="button" id="exportCsvBtn" class="btn" <?php echo (!$teacher_performance_result || mysqli_num_rows($teacher_performance_result) === 0) ? 'disabled' : ''; ?>><i class="fas fa-file-csv"></i> Download CSV</button>
                            </div>
                        </form>
                    </div>
                </div>

                <p class="section-label">Analytics</p>
                <div class="charts-row">
                    <div class="chart-card">
                        <div class="chart-card-title"><i class="fas fa-chart-line"></i> Syllabus Coverage (%)</div>
                        <p class="chart-note" id="analytics_mode_note">Showing class/division-wise average across all mapped subjects.</p>
                        <canvas id="pcLineChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <div class="analytics-header-right">
                            <div class="chart-card-title"><i class="fas fa-chart-bar"></i> Mid Exam Performance (%)</div>
                            <div class="analytics-filter">
                                <label for="analytics_class_filter">View By</label>
                                <select id="analytics_class_filter">
                                    <option value="">Class/Division-wise Average</option>
                                </select>
                            </div>
                        </div>
                        <canvas id="pcMidBarChart"></canvas>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5>Teacher Performance</h5></div>
                    <div class="card-body">
                        <div class="table-wrap">
                        <table class="table-compact">
                            <thead><tr><th>Teacher</th><th>Course</th><th>Class</th><th>Syllabus</th><th>Mid Avg</th><th>Evaluated / Total</th><th>At-Risk</th><th>Last Update</th></tr></thead>
                            <tbody>
                                <?php if($teacher_performance_result && mysqli_num_rows($teacher_performance_result) > 0): while ($row = mysqli_fetch_assoc($teacher_performance_result)): ?>
                                <?php
                                    $teacherNameRaw = isset($row['teacher_name']) ? trim((string)$row['teacher_name']) : '';
                                    $teacherNameDisplay = $teacherNameRaw !== '' ? format_person_display($teacherNameRaw) : '';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($teacherNameDisplay !== '' ? $teacherNameDisplay : $teacherNameRaw); ?></td>
                                    <td>
                                        <?php $subject_identifier = isset($row['subject_id']) ? (int)$row['subject_id'] : 0; ?>
                                        <?php if ($subject_identifier > 0): ?>
                                            <button type="button" class="link-button subject-detail-trigger" data-subject-id="<?php echo $subject_identifier; ?>">
                                                <?php echo htmlspecialchars(format_subject_display($row['course_name'] ?? '')); ?>
                                            </button>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars(format_subject_display($row['course_name'] ?? '')); ?>
                                        <?php endif; ?>
                                    </td>
                                    <?php
                                        $classLabelRaw = isset($row['class_label']) ? trim((string)$row['class_label']) : '';
                                        $classLabelDisplay = $classLabelRaw !== '' ? $classLabelRaw : '—';
                                    ?>
                                    <td><?php echo htmlspecialchars($classLabelDisplay); ?></td>
                                    <td>
                                        <?php
                        $completion = round($row['avg_completion'] ?? 0);
                        $rounded_threshold = (int)round($syllabus_threshold);
                        $warning_threshold = max(0, $rounded_threshold - 10);
                        $color_class = 'syllabus-chip chip-danger';
                        if ($completion >= $rounded_threshold) {
                            $color_class = 'syllabus-chip chip-success';
                        } elseif ($completion >= $warning_threshold) {
                            $color_class = 'syllabus-chip chip-warning';
                        }
                        echo '<span class="' . $color_class . '">' . $completion . '%</span>';
                                        ?>
                                    </td>
                                    <td><?php echo isset($teacher_ica_data[$row['teacher_id']][$row['course_name']]) ? round($teacher_ica_data[$row['teacher_id']][$row['course_name']]) . '%' : 'N/A'; ?></td>
                                    <td>
                                        <?php
                        $meta = $teacher_progress_meta[$row['teacher_id']][$row['course_name']] ?? ['total_students' => 0, 'evaluated_students' => 0, 'at_risk_students' => 0];
                        $total_students_display = $meta['total_students'] ?? 0;
                        $evaluated_display = $meta['evaluated_students'] ?? 0;
                        echo htmlspecialchars($evaluated_display . ' / ' . $total_students_display);
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars(($meta['at_risk_students'] ?? 0)); ?>
                                    </td>
                                    <td>
                                        <?php
                        $last_updated = $row['last_updated'] ?? null;
                        echo $last_updated ? htmlspecialchars(date('d M Y, h:i A', strtotime($last_updated))) : '—';
                                        ?>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="8">No teacher performance data available for the selected filters.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5>Pending Alerts</h5></div>
                    <div class="card-body">
                        <table>
                            <thead><tr><th>Teacher</th><th>Message</th><th>Sent At</th><th>Received At</th></tr></thead>
                            <tbody>
                                <?php if($alerts_result && mysqli_num_rows($alerts_result) > 0): while ($alert = mysqli_fetch_assoc($alerts_result)): ?>
                                <?php
                                    $alertTeacherRaw = isset($alert['teacher_name']) ? trim((string)$alert['teacher_name']) : '';
                                    $alertTeacherDisplay = $alertTeacherRaw !== '' ? format_person_display($alertTeacherRaw) : '';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($alertTeacherDisplay !== '' ? $alertTeacherDisplay : $alertTeacherRaw); ?></td>
                                    <td><?php echo htmlspecialchars($alert['message']); ?></td>
                                    <td><?php echo date("d-M-Y H:i:s", strtotime($alert['created_at'])); ?></td>
                                    <td><?php echo !empty($alert['responded_at']) ? date("d-M-Y H:i:s", strtotime($alert['responded_at'])) : '—'; ?></td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="4">No pending alerts.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; <?php echo date("Y"); ?> Kuchuru Sai Krishna Reddy – STME. All rights reserved.
            </div>
        </div>
    </div>
    <div class="detail-modal" id="subjectDetailModal">
        <div class="modal-content">
            <button type="button" class="modal-close" id="subjectDetailClose">&times;</button>
            <div id="subjectDetailBody"></div>
        </div>
    </div>

    <script>
        function toggleTheme() { document.body.classList.toggle('dark-mode'); localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light'); }
        if (localStorage.getItem('theme') === 'dark') { document.body.classList.add('dark-mode'); }

        document.addEventListener('DOMContentLoaded', function() {
            const schoolFilter = document.getElementById('school_filter');
            const semFilter = document.getElementById('semester_filter');
            const classFilter = document.getElementById('class_filter');
            const statusFilter = document.getElementById('status_filter');
            const resetBtn = document.getElementById('resetFiltersBtn');
            const exportBtn = document.getElementById('exportCsvBtn');
            const filterForm = document.getElementById('filterForm');
            
            const currentSchool = <?php echo json_encode($school_filter_display); ?>;
            const currentSem = <?php echo json_encode((string)$semester_filter); ?>;
            const currentClass = <?php echo json_encode($class_filter > 0 ? (string)$class_filter : ''); ?>;
            const currentStatus = <?php echo json_encode($status_filter); ?>;
            const currentActiveTermId = <?php echo json_encode($activeTermId > 0 ? $activeTermId : 0); ?>;
            const currentTermType = <?php echo json_encode($activeTermParity); ?>;

            if (statusFilter) {
                statusFilter.value = currentStatus || '';
            }

            // Populate Department Filter
            schoolFilter.innerHTML = '<option value="">All Schools</option>';
            const schoolOptions = <?php echo json_encode($school_options, JSON_UNESCAPED_UNICODE); ?>;
            schoolOptions.forEach((name) => {
                const option = document.createElement('option');
                option.value = name;
                option.textContent = name;
                if (currentSchool === name) {
                    option.selected = true;
                }
                schoolFilter.appendChild(option);
            });

            function fetchAndPopulate(url, selectElement, selectedValue, defaultText) {
                const targetValue = selectedValue === undefined || selectedValue === null ? '' : String(selectedValue);
                fetch(url).then((r) => r.json()).then((data) => {
                    let optionsHtml = `<option value="" ${targetValue === '' ? 'selected' : ''}>${defaultText}</option>`;
                    data.forEach((item) => {
                        const optionValueRaw = item.id ?? item.semester ?? '';
                        const optionValue = optionValueRaw === undefined || optionValueRaw === null ? '' : String(optionValueRaw);
                        const displayText = item.class_name ?? item.semester ?? optionValue;
                        const isSelected = optionValue === targetValue;
                        optionsHtml += `<option value="${optionValue}" ${isSelected ? 'selected' : ''}>${displayText}</option>`;
                    });
                    selectElement.innerHTML = optionsHtml;
                });
            }

            function buildTermScopedUrl(basePath, extraParams = {}) {
                const params = new URLSearchParams(extraParams);
                if (currentActiveTermId && Number(currentActiveTermId) > 0) {
                    params.set('active_term_id', String(currentActiveTermId));
                } else if (currentTermType) {
                    params.set('term_type', currentTermType);
                }
                return `${basePath}?${params.toString()}`;
            }

            schoolFilter.addEventListener('change', () => { 
                semFilter.innerHTML = '<option value="">All semesters</option>';
                classFilter.innerHTML = '<option value="">Select semester</option>';
                classFilter.disabled = true;
                if (schoolFilter.value) {
                    fetchAndPopulate(buildTermScopedUrl('get_semesters.php', { school: schoolFilter.value }), semFilter, null, 'All Semesters');
                }
            });
            semFilter.addEventListener('change', () => { 
                classFilter.innerHTML = '<option value="">Select semester</option>';
                classFilter.disabled = true;
                if (schoolFilter.value && semFilter.value) {
                    fetchAndPopulate(buildTermScopedUrl('get_classes.php', { school: schoolFilter.value, semester: semFilter.value }), classFilter, null, 'All Classes');
                    classFilter.disabled = false;
                }
            });
            
            // Initial population on page load
            if (currentSchool) {
                fetchAndPopulate(buildTermScopedUrl('get_semesters.php', { school: currentSchool }), semFilter, currentSem, 'All Semesters');
            }
            if (currentSchool && currentSem) {
                 fetchAndPopulate(buildTermScopedUrl('get_classes.php', { school: currentSchool, semester: currentSem }), classFilter, currentClass, 'All Classes');
                 classFilter.disabled = false;
            } else {
                classFilter.innerHTML = '<option value="">Select semester</option>';
                classFilter.disabled = true;
            }

            if (resetBtn) {
                resetBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = 'program_dashboard.php';
                });
            }

            if (exportBtn && filterForm) {
                exportBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const params = new URLSearchParams(new FormData(filterForm));
                    params.append('export', 'csv');
                    window.location.href = `program_dashboard.php?${params.toString()}`;
                });
            }

            document.querySelectorAll('.clickable-card[data-link]').forEach(card => {
                card.addEventListener('click', () => {
                    const target = card.getAttribute('data-link');
                    if (target) {
                        window.location.href = target;
                    }
                });
            });

            const subjectMeta = <?php echo json_encode($subject_chart_meta_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const classSummaryData = <?php echo json_encode($class_chart_summary_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const classSubjectData = <?php echo json_encode($class_subject_chart_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const currentClassFilter = <?php echo json_encode((string)$class_filter); ?>;

            const detailModal = document.getElementById('subjectDetailModal');
            const detailBody = document.getElementById('subjectDetailBody');
            const detailClose = document.getElementById('subjectDetailClose');

            function formatPercent(value) {
                if (value === null || value === undefined) {
                    return 'N/A';
                }
                const numericValue = Number(value);
                if (Number.isNaN(numericValue)) {
                    return 'N/A';
                }
                return Number.isInteger(numericValue) ? `${numericValue}%` : `${numericValue.toFixed(1)}%`;
            }

            function formatPercentLabel(value) {
                if (value === null || value === undefined) {
                    return 'N/A';
                }
                const numericValue = Number(value);
                if (Number.isNaN(numericValue)) {
                    return 'N/A';
                }
                return Number.isInteger(numericValue) ? `${numericValue}%` : `${numericValue.toFixed(1)}%`;
            }

            function openSubjectDetail(subjectId) {
                if (!subjectId) {
                    return;
                }
                const meta = subjectMeta[String(subjectId)];
                if (!meta) {
                    detailBody.innerHTML = '<p style="margin:0; font-weight:600; color:#6c757d;">Subject details are not available for this selection.</p>';
                    detailModal.classList.add('show');
                    return;
                }
                const teacherMarkup = (meta.teachers && meta.teachers.length)
                    ? meta.teachers.map(name => `<span class="chip-item">${name}</span>`).join('')
                    : '<span class="chip-item">Unassigned</span>';
                const classMarkup = (meta.classes && meta.classes.length)
                    ? meta.classes.map(name => `<span class="chip-item">${name}</span>`).join('')
                    : '<span class="chip-item">Unassigned</span>';
                const semesterBadge = meta.semester ? ` <small style="color:#6c757d;">(Semester ${meta.semester})</small>` : '';
                const midOverview = meta.midAverage !== null ? `${Number(meta.midAverage).toFixed(1)}%` : 'N/A';
                const schoolLine = meta.school ? `<p style="margin:0 0 12px 0; color:#6c757d;">School: <strong>${meta.school}</strong></p>` : '';
                const week3Helper = meta.syllabus.week3 !== null ? `<br><small style="color:#6c757d; font-weight:500;">Week 3: ${formatPercent(meta.syllabus.week3)}</small>` : '';
                detailBody.innerHTML = `
                    <h3 style="margin-bottom:12px; color:#A6192E;">${meta.name}${semesterBadge}</h3>
                    ${schoolLine}
                    <div class="detail-section">
                        <h5>Faculty</h5>
                        <div class="chip-row">${teacherMarkup}</div>
                    </div>
                    <div class="detail-section">
                        <h5>Mapped Classes</h5>
                        <div class="chip-row">${classMarkup}</div>
                    </div>
                    <div class="detail-section">
                        <h5>Syllabus Checkpoints</h5>
                        <ul class="timeline-list">
                            <li><span>Week 5</span><span>${formatPercent(meta.syllabus.week5)}${week3Helper}</span></li>
                            <li><span>Week 10</span><span>${formatPercent(meta.syllabus.week10)}</span></li>
                            <li><span>Final Progress</span><span>${formatPercent(meta.syllabus.final)}</span></li>
                            <li><span>Latest Update</span><span>${formatPercent(meta.syllabus.latest)}</span></li>
                        </ul>
                    </div>
                    <div class="detail-section">
                        <h5>Mid Exam Overview</h5>
                        <p style="margin:0; font-weight:600;">Average Mid Exam Score: <span style="color:#2c3e50;">${midOverview}</span></p>
                    </div>
                `;
                detailModal.classList.add('show');
            }

            detailClose.addEventListener('click', () => detailModal.classList.remove('show'));
            detailModal.addEventListener('click', (event) => {
                if (event.target === detailModal) {
                    detailModal.classList.remove('show');
                }
            });

            document.querySelectorAll('.subject-detail-trigger').forEach(btn => {
                btn.addEventListener('click', () => openSubjectDetail(btn.getAttribute('data-subject-id')));
            });

            const RED = '#A6192E';
            const barPalette = ['#A6192E', '#2563eb', '#16a34a', '#d97706', '#7c3aed', '#0d9488'];
            const lineCanvas = document.getElementById('pcLineChart');
            const midBarCanvas = document.getElementById('pcMidBarChart');
            const analyticsClassFilter = document.getElementById('analytics_class_filter');
            const analyticsModeNote = document.getElementById('analytics_mode_note');

            let syllabusChart = null;
            let midChart = null;
            let currentSubjectIds = [];

            classSummaryData.forEach((row) => {
                if (!analyticsClassFilter) {
                    return;
                }
                const opt = document.createElement('option');
                opt.value = String(row.class_key || row.class_id || '');
                opt.textContent = row.class_label_short || row.class_label || `Class ${row.class_id}`;
                analyticsClassFilter.appendChild(opt);
            });

            if (analyticsClassFilter && currentClassFilter) {
                analyticsClassFilter.value = String(currentClassFilter);
            }

            const getSelectedAnalyticsData = (selectedClassId) => {
                const classId = String(selectedClassId || '');
                if (classId !== '' && classSubjectData[classId]) {
                    const classEntry = classSubjectData[classId];
                    const labels = classEntry.subjects.map(item => item.abbr || item.subject_name);
                    const fullLabels = classEntry.subjects.map(item => item.subject_name || 'Subject');
                    const subjectIds = classEntry.subjects.map(item => item.subject_id || null);
                    const syllabusValues = classEntry.subjects.map(item => item.syllabus_avg !== null ? Number(item.syllabus_avg) : null);
                    const midValues = classEntry.subjects.map(item => item.mid_avg !== null ? Number(item.mid_avg) : null);
                    return {
                        labels,
                        fullLabels,
                        subjectIds,
                        syllabusValues,
                        midValues,
                        viewMode: 'subject',
                        classContextLabel: classEntry.class_label || '',
                        note: `Showing subject-level performance for ${classEntry.class_label}.`
                    };
                }

                return {
                    labels: classSummaryData.map(item => item.class_label_short || item.class_label || `Class ${item.class_id}`),
                    fullLabels: classSummaryData.map(item => item.class_label || item.class_label_short || `Class ${item.class_id}`),
                    subjectIds: classSummaryData.map(() => null),
                    syllabusValues: classSummaryData.map(item => item.syllabus_avg !== null ? Number(item.syllabus_avg) : null),
                    midValues: classSummaryData.map(item => item.mid_avg !== null ? Number(item.mid_avg) : null),
                    viewMode: 'class',
                    classContextLabel: '',
                    note: 'Showing class/division-wise average across all mapped subjects.'
                };
            };

            const renderAnalyticsCharts = (selectedClassId) => {
                if (!lineCanvas || !midBarCanvas) {
                    return;
                }

                const hasNumericData = (values) => Array.isArray(values) && values.some((value) => value !== null && value !== undefined && Number.isFinite(Number(value)));

                const compactAxisLabel = (value) => {
                    const label = String(value || '').trim();
                    if (label.length <= 16) {
                        return label;
                    }
                    return `${label.slice(0, 16)}...`;
                };

                const source = getSelectedAnalyticsData(selectedClassId);
                currentSubjectIds = source.subjectIds;
                const hasSourceData = source.labels.length > 0 && (hasNumericData(source.syllabusValues) || hasNumericData(source.midValues));
                const labelsToRender = source.labels.length > 0 ? source.labels : ['No data'];
                const fullLabelsToRender = source.fullLabels.length > 0 ? source.fullLabels : ['No data'];
                const syllabusValuesToRender = hasNumericData(source.syllabusValues) ? source.syllabusValues : labelsToRender.map(() => 0);
                const midValuesToRender = hasNumericData(source.midValues) ? source.midValues : labelsToRender.map(() => 0);
                const chartColors = hasSourceData
                    ? labelsToRender.map((_, idx) => barPalette[idx % barPalette.length])
                    : labelsToRender.map(() => '#cbd5e1');
                if (analyticsModeNote) {
                    analyticsModeNote.textContent = hasSourceData
                        ? source.note
                        : `${source.note} No measurable syllabus or mid-evaluation data found for this scope.`;
                }

                if (syllabusChart) {
                    syllabusChart.destroy();
                }
                syllabusChart = new Chart(lineCanvas, {
                    type: 'bar',
                    data: {
                        labels: labelsToRender,
                        datasets: [{
                            label: 'Syllabus Coverage %',
                            data: syllabusValuesToRender,
                            backgroundColor: chartColors,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    title: (tooltipItems) => tooltipItems.length ? (fullLabelsToRender[tooltipItems[0].dataIndex] || '') : '',
                                    label: (ctx) => hasSourceData ? `Coverage: ${formatPercentLabel(ctx.raw)}` : 'No data available',
                                    afterLabel: (ctx) => {
                                        if (!hasSourceData) {
                                            return '';
                                        }
                                        if (source.viewMode === 'subject') {
                                            return source.classContextLabel ? `Class/Div/Sem: ${source.classContextLabel}` : '';
                                        }
                                        return `Class/Div/Sem: ${fullLabelsToRender[ctx.dataIndex] || ''}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: { beginAtZero: true, max: 100 },
                            x: {
                                ticks: {
                                    autoSkip: false,
                                    maxRotation: 50,
                                    minRotation: 35,
                                    padding: 2,
                                    callback: function(value) {
                                        const raw = this.getLabelForValue(value);
                                        return compactAxisLabel(raw);
                                    },
                                    font: {
                                        size: 8
                                    }
                                }
                            }
                        },
                        onClick: (event) => {
                            if (!hasSourceData) {
                                return;
                            }
                            const points = syllabusChart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, true);
                            if (!points.length) {
                                return;
                            }
                            const subjectId = currentSubjectIds[points[0].index];
                            if (subjectId) {
                                openSubjectDetail(subjectId);
                            }
                        }
                    }
                });

                if (midChart) {
                    midChart.destroy();
                }
                midChart = new Chart(midBarCanvas, {
                    type: 'bar',
                    data: {
                        labels: labelsToRender,
                        datasets: [{
                            label: 'Mid Exam Avg %',
                            data: midValuesToRender,
                            backgroundColor: chartColors,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    title: (tooltipItems) => tooltipItems.length ? (fullLabelsToRender[tooltipItems[0].dataIndex] || '') : '',
                                    label: (ctx) => hasSourceData ? `Mid Avg: ${formatPercentLabel(ctx.raw)}` : 'No data available',
                                    afterLabel: (ctx) => {
                                        if (!hasSourceData) {
                                            return '';
                                        }
                                        if (source.viewMode === 'subject') {
                                            return source.classContextLabel ? `Class/Div/Sem: ${source.classContextLabel}` : '';
                                        }
                                        return `Class/Div/Sem: ${fullLabelsToRender[ctx.dataIndex] || ''}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: { beginAtZero: true, max: 100 },
                            x: {
                                ticks: {
                                    autoSkip: false,
                                    maxRotation: 50,
                                    minRotation: 35,
                                    padding: 2,
                                    callback: function(value) {
                                        const raw = this.getLabelForValue(value);
                                        return compactAxisLabel(raw);
                                    },
                                    font: {
                                        size: 8
                                    }
                                }
                            }
                        }
                    }
                });
            };

            if (analyticsClassFilter) {
                analyticsClassFilter.addEventListener('change', () => {
                    renderAnalyticsCharts(analyticsClassFilter.value || '');
                });
            }

            renderAnalyticsCharts(analyticsClassFilter ? analyticsClassFilter.value : '');
        });
    </script>
</body>
</html>

