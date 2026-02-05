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
$semester_filter = isset($_GET['semester']) ? mysqli_real_escape_string($conn, $_GET['semester']) : '';
$class_filter = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
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
    $assignment_where_parts[] = '(c.academic_term_id = ' . $activeTermId . ' OR c.academic_term_id IS NULL)';
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

$teacher_performance_query = "
    WITH base_progress AS (
        SELECT
            sp.teacher_id,
            sp.subject,
            sp.class_id,
            COALESCE(sp.section_id, 0) AS section_id,
            sp.class_label,
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
        $teacher_eval_subquery .= " AND (c_eval.academic_term_id = " . $activeTermId . " OR c_eval.academic_term_id IS NULL)";
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

// 5. PENDING ALERTS
// Pending alerts: if we have a pc_school value, filter by it; otherwise show all pending alerts
if (!empty($pc_school) && $user_school_field) {
    $alerts_q = "SELECT u.name as teacher_name, a.message, a.created_at, a.responded_at, a.status FROM alerts a JOIN users u ON a.teacher_id = u.id WHERE u.{$user_school_field} = ? AND a.status = 'pending' AND u.role = 'teacher' ORDER BY a.created_at DESC";
    $stmt_alerts = mysqli_prepare($conn, $alerts_q);
    mysqli_stmt_bind_param($stmt_alerts, "s", $pc_school);
    mysqli_stmt_execute($stmt_alerts);
    $alerts_result = mysqli_stmt_get_result($stmt_alerts);
    if ($alerts_result && mysqli_num_rows($alerts_result) === 0 && $user_alt_field) {
        mysqli_stmt_close($stmt_alerts);
        $alerts_q_alt = "SELECT u.name as teacher_name, a.message, a.created_at, a.responded_at, a.status FROM alerts a JOIN users u ON a.teacher_id = u.id WHERE u.{$user_alt_field} = ? AND a.status = 'pending' AND u.role = 'teacher' ORDER BY a.created_at DESC";
        $stmt_alerts_alt = mysqli_prepare($conn, $alerts_q_alt);
        mysqli_stmt_bind_param($stmt_alerts_alt, "s", $pc_school);
        mysqli_stmt_execute($stmt_alerts_alt);
        $alerts_result_alt = mysqli_stmt_get_result($stmt_alerts_alt);
        if ($alerts_result_alt && mysqli_num_rows($alerts_result_alt) > 0) {
            $alerts_result = $alerts_result_alt;
        }
        mysqli_stmt_close($stmt_alerts_alt);
    } else {
        mysqli_stmt_close($stmt_alerts);
    }
} else {
    $alerts_q = "SELECT u.name as teacher_name, a.message, a.created_at, a.responded_at, a.status FROM alerts a JOIN users u ON a.teacher_id = u.id WHERE a.status = 'pending' AND u.role = 'teacher' ORDER BY a.created_at DESC";
    $alerts_result = mysqli_query($conn, $alerts_q);
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
    <link rel="stylesheet" href="program_dashboard.css">
    <style>
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
                <div class="overview-grid">
                    <div class="overview-card clickable-card" data-link="course_progress.php"><i class="fas fa-book"></i><div><div class="card-value"><?php echo $total_courses; ?></div><div class="card-label">Total Courses</div></div></div>
                    <div class="overview-card clickable-card" data-link="course_progress.php"><i class="fas fa-check-double"></i><div><div class="card-value"><?php echo $avg_syllabus; ?>%</div><div class="card-label">Syllabus Covered</div></div></div>
                    <div class="overview-card" onclick="window.location.href='student_progress.php?status=at_risk'"><i class="fas fa-exclamation-triangle"></i><div><div class="card-value"><?php echo $low_performing_students; ?></div><div class="card-label">Low Performing Students</div></div></div>
                    <div class="overview-card"><i class="fas fa-calendar-week"></i><div><div class="card-value"><?php echo htmlspecialchars($week_number_display); ?></div><div class="card-label">Current Academic Week</div></div></div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="filter-header">
                            <h5>Filters &amp; Tools</h5>
                            <a href="program_dashboard.php" class="link-reset">Reset filters</a>
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
                                        <option value="">-- Select School --</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="class_filter">Class</label>
                                    <select name="class_id" id="class_filter">
                                        <option value="">-- Select Sem --</option>
                                    </select>
                                </div>
                                <div class="form-group filter-actions">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn">Apply Filters</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5>Teacher Performance</h5></div>
                    <div class="card-body">
                        <table>
                            <thead><tr><th>Teacher</th><th>Course</th><th>Class</th><th>Timeline</th><th>Planned To Date</th><th>Completed To Date</th><th>Extra Classes</th><th>Syllabus Status</th><th>ICA Avg</th><th>Evaluated Students</th><th>At-Risk Students</th><th>Last Update</th></tr></thead>
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
                                            $timeline_label = $row['timeline'] ?? '';
                        if (preg_match('/week_(\d+)/i', $timeline_label, $matches)) {
                            $timeline_label = 'Week ' . $matches[1];
                        } else {
                            $timeline_label = ucwords(str_replace('_', ' ', (string)$timeline_label));
                        }
                        echo htmlspecialchars($timeline_label);
                                        ?>
                                    </td>
                                    <?php
                        $planned_hours = isset($row['planned_hours']) ? (float)$row['planned_hours'] : 0;
                        $actual_hours = isset($row['actual_hours']) ? (float)$row['actual_hours'] : 0;
                        $actual_theory = isset($row['actual_theory_hours']) ? (float)$row['actual_theory_hours'] : 0;
                        $actual_practical = isset($row['actual_practical_hours']) ? (float)$row['actual_practical_hours'] : 0;
                        $extra_total = isset($row['extra_classes']) ? (float)$row['extra_classes'] : 0;
                        $extra_theory = isset($row['extra_theory_hours']) ? (float)$row['extra_theory_hours'] : 0;
                        $extra_practical = isset($row['extra_practical_hours']) ? (float)$row['extra_practical_hours'] : 0;
                        $planned_practical = isset($row['planned_practical_hours']) ? (float)$row['planned_practical_hours'] : 0;
                        $practical_label_raw = isset($row['practical_label_raw']) ? trim((string)$row['practical_label_raw']) : '';
                        if ($practical_label_raw === '' && stripos((string)($row['course_name'] ?? ''), 'tutorial') !== false) {
                            $practical_label_raw = 'Tutorial';
                        }
                        $practical_label_display = $practical_label_raw !== '' ? $practical_label_raw : 'Practical';
                        $show_practical = $planned_practical > 0 || abs($actual_practical) > 0.0001 || abs($extra_practical) > 0.0001;
                                    ?>
                                    <td>
                                        <?php echo htmlspecialchars(format_hours_display($planned_hours) . ' hrs'); ?>
                                    </td>
                                    <td>
                                        <?php
                        echo htmlspecialchars(format_hours_display($actual_hours) . ' hrs');
                        $actual_parts = ['T: ' . format_hours_display($actual_theory)];
                        if ($show_practical) {
                            $actual_parts[] = $practical_label_display . ': ' . format_hours_display($actual_practical);
                        }
                        echo '<br><small>' . htmlspecialchars(implode(' | ', $actual_parts)) . '</small>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                        echo htmlspecialchars(format_hours_display($extra_total) . ' hrs');
                        $extra_parts = ['T: ' . format_hours_display($extra_theory)];
                        if ($show_practical) {
                            $extra_parts[] = $practical_label_display . ': ' . format_hours_display($extra_practical);
                        }
                        echo '<br><small>' . htmlspecialchars(implode(' | ', $extra_parts)) . '</small>';
                                        ?>
                                    </td>
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
                                <tr><td colspan="12">No teacher performance data available for the selected filters.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="chart-grid">
                    <div class="card">
                        <div class="card-header"><h5>MID Performance Overview</h5></div>
                        <div class="card-body"><div class="chart-container"><canvas id="midPerformanceChart"></canvas></div></div>
                    </div>
                    <div class="card">
                        <div class="card-header"><h5>Syllabus Coverage by Timeline</h5></div>
                        <div class="card-body"><div class="chart-container"><canvas id="syllabusCoverageChart"></canvas></div></div>
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
            
            const currentSchool = <?php echo json_encode($school_filter_display); ?>;
            const currentSem = '<?php echo $semester_filter; ?>';
            const currentClass = '<?php echo $class_filter; ?>';

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

            schoolFilter.addEventListener('change', () => { 
                semFilter.innerHTML = '<option value="">-- Select School --</option>';
                classFilter.innerHTML = '<option value="">-- Select Sem --</option>';
                if (schoolFilter.value) {
                    fetchAndPopulate(`get_semesters.php?school=${schoolFilter.value}`, semFilter, null, 'All Semesters');
                }
            });
            semFilter.addEventListener('change', () => { 
                classFilter.innerHTML = '<option value="">-- Select Sem --</option>';
                if (schoolFilter.value && semFilter.value) {
                    fetchAndPopulate(`get_classes.php?school=${schoolFilter.value}&semester=${semFilter.value}`, classFilter, null, 'All Classes');
                }
            });
            
            // Initial population on page load
            if (currentSchool) {
                fetchAndPopulate(`get_semesters.php?school=${currentSchool}`, semFilter, currentSem, 'All Semesters');
            }
            if (currentSchool && currentSem) {
                 fetchAndPopulate(`get_classes.php?school=${currentSchool}&semester=${currentSem}`, classFilter, currentClass, 'All Classes');
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
            const syllabusData = <?php echo json_encode($syllabus_chart_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const midData = <?php echo json_encode($mid_perf_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

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

            const syllabusLabels = syllabusData.map(item => item.abbr || item.subject_name);
            const syllabusFullLabels = syllabusData.map(item => item.subject_name);
            const syllabusSubjectIds = syllabusData.map(item => item.subject_id);
            const week3Series = syllabusData.map(item => item.week3 !== null ? Number(item.week3) : null);
            const week5Series = syllabusData.map(item => item.week5 !== null ? Number(item.week5) : null);
            const week10Series = syllabusData.map(item => item.week10 !== null ? Number(item.week10) : null);
            const finalSeries = syllabusData.map(item => item.final !== null ? Number(item.final) : null);

            const syllabusCtx = document.getElementById('syllabusCoverageChart').getContext('2d');
            const syllabusChart = new Chart(syllabusCtx, {
                type: 'bar',
                data: {
                    labels: syllabusLabels,
                    datasets: [
                        { label: 'Week 5 Progress', data: week5Series, backgroundColor: 'rgba(255, 99, 132, 0.7)' },
                        { label: 'Week 10 Progress', data: week10Series, backgroundColor: 'rgba(54, 162, 235, 0.7)' },
                        { label: 'Final Progress', data: finalSeries, backgroundColor: 'rgba(75, 192, 192, 0.7)' }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, max: 100 } },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                title: (tooltipItems) => {
                                    if (!tooltipItems.length) { return ''; }
                                    return syllabusFullLabels[tooltipItems[0].dataIndex] || '';
                                },
                                label: (context) => {
                                    const idx = context.dataIndex;
                                    const datasetLabel = context.dataset.label || '';
                                    const rawValue = context.raw;
                                    let line = `${datasetLabel}: ${formatPercentLabel(rawValue)}`;
                                    if (datasetLabel === 'Week 5 Progress') {
                                        const week3Value = week3Series[idx];
                                        if (week3Value !== null && week3Value !== undefined) {
                                            line += ` (Week 3: ${formatPercentLabel(week3Value)})`;
                                        }
                                    }
                                    return line;
                                }
                            }
                        }
                    },
                    onClick: (event) => {
                        const points = syllabusChart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, true);
                        if (points.length > 0) {
                            const index = points[0].index;
                            openSubjectDetail(syllabusSubjectIds[index]);
                        }
                    }
                }
            });

            const midLabels = midData.map(item => item.abbr || item.subject_name);
            const midFullLabels = midData.map(item => item.subject_name);
            const midSubjectIds = midData.map(item => item.subject_id);
            const midAvgSeries = midData.map(item => item.mid_avg !== null ? Number(item.mid_avg) : null);

            const midCtx = document.getElementById('midPerformanceChart').getContext('2d');
            const midPerformanceChart = new Chart(midCtx, {
                type: 'bar',
                data: {
                    labels: midLabels,
                    datasets: [
                        { label: 'Mid Exam Avg %', data: midAvgSeries, backgroundColor: 'rgba(74, 144, 226, 0.7)' }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, max: 100 } },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                title: (tooltipItems) => {
                                    if (!tooltipItems.length) { return ''; }
                                    return midFullLabels[tooltipItems[0].dataIndex] || '';
                                },
                                label: (context) => {
                                    const rawValue = context.raw;
                                    return `Mid Exam Avg: ${formatPercentLabel(rawValue)}`;
                                }
                            }
                        }
                    },
                    onClick: (event) => {
                        const points = midPerformanceChart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, true);
                        if (points.length > 0) {
                            const index = points[0].index;
                            openSubjectDetail(midSubjectIds[index]);
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>

