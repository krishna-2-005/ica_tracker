<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'program_chair') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/academic_context.php';

if (!function_exists('abbreviate_subject_name')) {
    function abbreviate_subject_name(string $name): string {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return '';
        }

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
}

$pc_user_id = (int)$_SESSION['user_id'];
$pc_school = '';
$pc_school_stmt = mysqli_prepare($conn, "SELECT school, department FROM users WHERE id = ? LIMIT 1");
if ($pc_school_stmt) {
    mysqli_stmt_bind_param($pc_school_stmt, 'i', $pc_user_id);
    if (mysqli_stmt_execute($pc_school_stmt)) {
        $pc_res = mysqli_stmt_get_result($pc_school_stmt);
        if ($pc_res && ($pc_row = mysqli_fetch_assoc($pc_res))) {
            if (!empty($pc_row['school'])) {
                $pc_school = $pc_row['school'];
            } elseif (!empty($pc_row['department'])) {
                $pc_school = $pc_row['department'];
            }
        }
        if ($pc_res) {
            mysqli_free_result($pc_res);
        }
    }
    mysqli_stmt_close($pc_school_stmt);
}

$academicContext = resolveAcademicContext($conn, [
    'school_name' => $pc_school
]);
$activeTerm = $academicContext['active'] ?? null;
$activeTermId = $activeTerm && isset($activeTerm['id']) ? (int)$activeTerm['id'] : 0;
$termDateFilter = $academicContext['date_filter'] ?? null;
$termStartDate = $termDateFilter['start'] ?? null;
$termEndDate = $termDateFilter['end'] ?? null;
$termStartBound = $termStartDate ? $termStartDate . ' 00:00:00' : null;
$termEndBound = $termEndDate ? $termEndDate . ' 23:59:59' : null;
$termStartEsc = $termStartBound ? mysqli_real_escape_string($conn, $termStartBound) : null;
$termEndEsc = $termEndBound ? mysqli_real_escape_string($conn, $termEndBound) : null;

$allowed_timelines = ['week5', 'week10', 'final', 'latest'];
$timeline_labels = [
    'week5' => 'Week 5',
    'week10' => 'Week 10',
    'final' => 'Final Progress',
    'latest' => 'Latest'
];
$school_filter = '';
$school_param_provided = array_key_exists('school', $_GET);
if ($school_param_provided) {
    $school_filter = trim($_GET['school']);
}
if (!$school_param_provided && $pc_school !== '') {
    $school_filter = $pc_school;
}

$semester_filter = isset($_GET['semester']) ? trim($_GET['semester']) : '';
$class_filter = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$subject_filter = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$teacher_filter = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;
$timeline_filter = isset($_GET['timeline']) ? strtolower(trim($_GET['timeline'])) : 'latest';
if (!in_array($timeline_filter, $allowed_timelines, true)) {
    $timeline_filter = 'latest';
}
$timeline_filter_label = $timeline_labels[$timeline_filter] ?? ucfirst($timeline_filter);
$export_csv = isset($_GET['export']) && $_GET['export'] === 'csv';

$available_schools = [];
$school_sql = "SELECT school_name FROM schools ORDER BY school_name";
$school_res = mysqli_query($conn, $school_sql);
if ($school_res) {
    while ($row = mysqli_fetch_assoc($school_res)) {
        if (!empty($row['school_name'])) {
            $available_schools[] = $row['school_name'];
        }
    }
    mysqli_free_result($school_res);
}
if (empty($available_schools)) {
    $subject_school_res = mysqli_query($conn, "SELECT DISTINCT school FROM subjects WHERE school IS NOT NULL AND school <> '' ORDER BY school");
    if ($subject_school_res) {
        while ($row = mysqli_fetch_assoc($subject_school_res)) {
            $available_schools[] = $row['school'];
        }
        mysqli_free_result($subject_school_res);
    }
}
if ($pc_school !== '' && !in_array($pc_school, $available_schools, true)) {
    $available_schools[] = $pc_school;
    sort($available_schools);
}

$semester_options = [];
if ($school_filter !== '') {
    $sem_stmt = mysqli_prepare($conn, "SELECT DISTINCT semester FROM subjects WHERE school = ? ORDER BY CAST(semester AS UNSIGNED)");
    if ($sem_stmt) {
        mysqli_stmt_bind_param($sem_stmt, 's', $school_filter);
        mysqli_stmt_execute($sem_stmt);
        $sem_res = mysqli_stmt_get_result($sem_stmt);
        if ($sem_res) {
            while ($row = mysqli_fetch_assoc($sem_res)) {
                if ($row['semester'] !== null && $row['semester'] !== '') {
                    $semester_options[] = $row['semester'];
                }
            }
            mysqli_free_result($sem_res);
        }
        mysqli_stmt_close($sem_stmt);
    }
} else {
    $sem_res = mysqli_query($conn, "SELECT DISTINCT semester FROM subjects ORDER BY CAST(semester AS UNSIGNED)");
    if ($sem_res) {
        while ($row = mysqli_fetch_assoc($sem_res)) {
            if ($row['semester'] !== null && $row['semester'] !== '') {
                $semester_options[] = $row['semester'];
            }
        }
        mysqli_free_result($sem_res);
    }
}

$class_options = [];
$class_sql = "SELECT id, class_name, semester FROM classes";
$class_conditions = [];
if ($school_filter !== '') {
    $class_conditions[] = "school = '" . mysqli_real_escape_string($conn, $school_filter) . "'";
}
if ($semester_filter !== '') {
    $class_conditions[] = "semester = '" . mysqli_real_escape_string($conn, $semester_filter) . "'";
}
if ($activeTermId > 0) {
    $termId = (int)$activeTermId;
    $class_conditions[] = "(academic_term_id = {$termId} OR academic_term_id IS NULL)";
}
if (!empty($class_conditions)) {
    $class_sql .= ' WHERE ' . implode(' AND ', $class_conditions);
}
$class_sql .= ' ORDER BY CAST(semester AS UNSIGNED), class_name';
$class_res = mysqli_query($conn, $class_sql);
if ($class_res) {
    while ($row = mysqli_fetch_assoc($class_res)) {
        $class_options[] = [
            'id' => (int)$row['id'],
            'name' => $row['class_name'],
            'semester' => $row['semester']
        ];
    }
    mysqli_free_result($class_res);
}

$subject_options = [];
$subject_list_sql = "SELECT id, subject_name FROM subjects";
$subject_filters = [];
if ($school_filter !== '') {
    $subject_filters[] = "school = '" . mysqli_real_escape_string($conn, $school_filter) . "'";
}
if ($semester_filter !== '') {
    $subject_filters[] = "semester = '" . mysqli_real_escape_string($conn, $semester_filter) . "'";
}
if ($activeTermId > 0) {
    $termId = (int)$activeTermId;
    $subject_filters[] = "EXISTS (SELECT 1 FROM teacher_subject_assignments term_ts JOIN classes term_c ON term_c.id = term_ts.class_id WHERE term_ts.subject_id = subjects.id AND (term_c.academic_term_id = {$termId} OR term_c.academic_term_id IS NULL))";
}
if (!empty($subject_filters)) {
    $subject_list_sql .= ' WHERE ' . implode(' AND ', $subject_filters);
}
$subject_list_sql .= ' ORDER BY subject_name';
$subject_list_res = mysqli_query($conn, $subject_list_sql);
if ($subject_list_res) {
    while ($row = mysqli_fetch_assoc($subject_list_res)) {
        $raw_subject_name = $row['subject_name'] ?? '';
        $subject_options[] = [
            'id' => (int)$row['id'],
            'name' => format_subject_display($raw_subject_name),
            'name_raw' => $raw_subject_name
        ];
    }
    mysqli_free_result($subject_list_res);
}

$teacher_options = [];
$teacher_sql = "SELECT DISTINCT u.id, u.name FROM users u JOIN teacher_subject_assignments tsa ON tsa.teacher_id = u.id JOIN classes cterm ON cterm.id = tsa.class_id";
$teacher_conditions = ["u.role = 'teacher'"];
if ($school_filter !== '') {
    $escapedSchool = mysqli_real_escape_string($conn, $school_filter);
    $teacher_conditions[] = "(EXISTS (SELECT 1 FROM subjects s WHERE s.id = tsa.subject_id AND s.school = '" . $escapedSchool . "') OR cterm.school = '" . $escapedSchool . "')";
}
if ($semester_filter !== '') {
    $escapedSemester = mysqli_real_escape_string($conn, $semester_filter);
    $teacher_conditions[] = "(EXISTS (SELECT 1 FROM subjects s2 WHERE s2.id = tsa.subject_id AND s2.semester = '" . $escapedSemester . "') OR cterm.semester = '" . $escapedSemester . "')";
}
if ($activeTermId > 0) {
    $termId = (int)$activeTermId;
    $teacher_conditions[] = "(cterm.academic_term_id = {$termId} OR cterm.academic_term_id IS NULL)";
}
if (!empty($teacher_conditions)) {
    $teacher_sql .= ' WHERE ' . implode(' AND ', $teacher_conditions);
}
$teacher_sql .= ' ORDER BY u.name';
$teacher_res = mysqli_query($conn, $teacher_sql);
if ($teacher_res) {
    while ($row = mysqli_fetch_assoc($teacher_res)) {
        $teacherNameRaw = isset($row['name']) ? trim((string)$row['name']) : '';
        $teacher_options[] = [
            'id' => (int)$row['id'],
            'name' => $teacherNameRaw,
            'name_display' => $teacherNameRaw !== '' ? format_person_display($teacherNameRaw) : ''
        ];
    }
    mysqli_free_result($teacher_res);
}

$subject_query = "SELECT s.id, s.subject_name, s.semester, s.school FROM subjects s WHERE 1 = 1";
$subject_params = [];
$subject_types = '';
if ($school_filter !== '') {
    $subject_query .= ' AND s.school = ?';
    $subject_params[] = $school_filter;
    $subject_types .= 's';
}
if ($semester_filter !== '') {
    $subject_query .= ' AND s.semester = ?';
    $subject_params[] = $semester_filter;
    $subject_types .= 's';
}
if ($subject_filter > 0) {
    $subject_query .= ' AND s.id = ?';
    $subject_params[] = $subject_filter;
    $subject_types .= 'i';
}
if ($class_filter > 0) {
    $subject_query .= ' AND EXISTS (SELECT 1 FROM teacher_subject_assignments tsc WHERE tsc.subject_id = s.id AND tsc.class_id = ?)';
    $subject_params[] = $class_filter;
    $subject_types .= 'i';
}
if ($teacher_filter > 0) {
    $subject_query .= ' AND EXISTS (SELECT 1 FROM teacher_subject_assignments tst WHERE tst.subject_id = s.id AND tst.teacher_id = ?)';
    $subject_params[] = $teacher_filter;
    $subject_types .= 'i';
}
if ($activeTermId > 0) {
    $termId = (int)$activeTermId;
    $subject_query .= ' AND EXISTS (SELECT 1 FROM teacher_subject_assignments ts_term JOIN classes c_term ON c_term.id = ts_term.class_id WHERE ts_term.subject_id = s.id AND (c_term.academic_term_id = ' . $termId . ' OR c_term.academic_term_id IS NULL))';
}
$subject_query .= ' ORDER BY CAST(s.semester AS UNSIGNED), s.subject_name';

$subjects_stmt = mysqli_prepare($conn, $subject_query);
if ($subjects_stmt) {
    if ($subject_types !== '') {
        $bind_params = [$subjects_stmt, $subject_types];
        foreach ($subject_params as $idx => $value) {
            $bind_params[] = &$subject_params[$idx];
        }
        call_user_func_array('mysqli_stmt_bind_param', $bind_params);
    }
    mysqli_stmt_execute($subjects_stmt);
    $subjects_res = mysqli_stmt_get_result($subjects_stmt);
} else {
    $subjects_res = false;
}

$courses = [];
if ($subjects_res) {
    while ($row = mysqli_fetch_assoc($subjects_res)) {
        $raw_subject_name = $row['subject_name'] ?? '';
        $courses[(int)$row['id']] = [
            'id' => (int)$row['id'],
            'name' => format_subject_display($raw_subject_name),
            'name_raw' => $raw_subject_name,
            'abbr' => abbreviate_subject_name($raw_subject_name),
            'semester' => $row['semester'],
            'school' => $row['school'],
            'classes' => '',
            'teachers' => '',
            'total_students' => 0,
            'evaluated_students' => 0,
            'avg_completion_selected' => null,
            'overall_completion' => null,
            'week3_progress' => null,
            'week5_progress' => null,
            'week10_progress' => null,
            'final_progress' => null,
            'avg_ica' => null,
            'at_risk_students' => 0,
            'evaluation_coverage' => null,
            'last_updated' => null,
            'status' => 'No Updates'
        ];
    }
    mysqli_free_result($subjects_res);
}
if ($subjects_stmt) {
    mysqli_stmt_close($subjects_stmt);
}

$course_ids = array_keys($courses);
if (!empty($course_ids)) {
    $id_list = implode(',', array_map('intval', $course_ids));

    $class_teacher_sql = "SELECT tsa.subject_id,
            GROUP_CONCAT(DISTINCT c.class_name ORDER BY c.class_name SEPARATOR ', ') AS classes,
            GROUP_CONCAT(DISTINCT u.name ORDER BY u.name SEPARATOR ', ') AS teachers
        FROM teacher_subject_assignments tsa
        JOIN classes c ON c.id = tsa.class_id
        LEFT JOIN users u ON u.id = tsa.teacher_id
        WHERE tsa.subject_id IN ($id_list)" . ($activeTermId > 0 ? " AND (c.academic_term_id = " . (int)$activeTermId . " OR c.academic_term_id IS NULL)" : '') . "
        GROUP BY tsa.subject_id";
    $class_teacher_res = mysqli_query($conn, $class_teacher_sql);
    if ($class_teacher_res) {
        while ($row = mysqli_fetch_assoc($class_teacher_res)) {
            $sid = (int)$row['subject_id'];
            if (isset($courses[$sid])) {
                $courses[$sid]['classes'] = $row['classes'] ?? '';
                $teachersConcat = $row['teachers'] ?? '';
                if ($teachersConcat !== '') {
                    $teacherList = [];
                    foreach (explode(',', $teachersConcat) as $teacherName) {
                        $trimmedTeacher = trim((string)$teacherName);
                        if ($trimmedTeacher === '') {
                            continue;
                        }
                        $teacherList[] = format_person_display($trimmedTeacher);
                    }
                    $teacherList = array_values(array_unique($teacherList));
                    $courses[$sid]['teachers'] = !empty($teacherList) ? implode(', ', $teacherList) : '';
                } else {
                    $courses[$sid]['teachers'] = '';
                }
            }
        }
        mysqli_free_result($class_teacher_res);
    }

    $student_count_sql = "SELECT tsa.subject_id,
            COUNT(DISTINCT stu.id) AS total_students
        FROM teacher_subject_assignments tsa
        JOIN classes c ON c.id = tsa.class_id
        JOIN students stu ON stu.class_id = tsa.class_id
            AND (tsa.section_id IS NULL OR tsa.section_id = 0 OR COALESCE(stu.section_id, 0) = tsa.section_id)
        WHERE tsa.subject_id IN ($id_list)" . ($activeTermId > 0 ? " AND (c.academic_term_id = " . (int)$activeTermId . " OR c.academic_term_id IS NULL)" : '') . "
        GROUP BY tsa.subject_id";
    $student_count_res = mysqli_query($conn, $student_count_sql);
    if ($student_count_res) {
        while ($row = mysqli_fetch_assoc($student_count_res)) {
            $sid = (int)$row['subject_id'];
            if (isset($courses[$sid])) {
                $courses[$sid]['total_students'] = (int)$row['total_students'];
            }
        }
        mysqli_free_result($student_count_res);
    }

    $evaluated_sql = "SELECT ic.subject_id,
            COUNT(DISTINCT CONCAT_WS('-', ism.student_id, c.id)) AS evaluated_students
        FROM ica_student_marks ism
        JOIN ica_components ic ON ic.id = ism.component_id
        JOIN teacher_subject_assignments tsa ON tsa.subject_id = ic.subject_id AND tsa.teacher_id = ism.teacher_id
        JOIN classes c ON c.id = tsa.class_id" . ($activeTermId > 0 ? " AND (c.academic_term_id = " . (int)$activeTermId . " OR c.academic_term_id IS NULL)" : '') . "
        WHERE ic.subject_id IN ($id_list)
        GROUP BY ic.subject_id";
    $evaluated_res = mysqli_query($conn, $evaluated_sql);
    if ($evaluated_res) {
        while ($row = mysqli_fetch_assoc($evaluated_res)) {
            $sid = (int)$row['subject_id'];
            if (isset($courses[$sid])) {
                $courses[$sid]['evaluated_students'] = (int)$row['evaluated_students'];
            }
        }
        mysqli_free_result($evaluated_res);
    }

    $ica_sql = "SELECT subject_id,
            AVG(avg_pct) AS avg_pct,
            SUM(CASE WHEN avg_pct < 50 THEN 1 ELSE 0 END) AS at_risk
        FROM (
            SELECT ic.subject_id,
                   ism.student_id,
                   AVG((ism.marks / NULLIF(ic.marks_per_instance, 0)) * 100) AS avg_pct
            FROM ica_student_marks ism
            JOIN ica_components ic ON ic.id = ism.component_id
            JOIN teacher_subject_assignments tsa ON tsa.subject_id = ic.subject_id AND tsa.teacher_id = ism.teacher_id
            JOIN classes c ON c.id = tsa.class_id" . ($activeTermId > 0 ? " AND (c.academic_term_id = " . (int)$activeTermId . " OR c.academic_term_id IS NULL)" : '') . "
            WHERE ic.subject_id IN ($id_list)
              AND ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance <> 0
            GROUP BY ic.subject_id, ism.student_id
        ) subject_scores
        GROUP BY subject_id";
    $ica_res = mysqli_query($conn, $ica_sql);
    if ($ica_res) {
        while ($row = mysqli_fetch_assoc($ica_res)) {
            $sid = (int)$row['subject_id'];
            if (isset($courses[$sid])) {
                $avg_pct = isset($row['avg_pct']) ? (float)$row['avg_pct'] : null;
                $courses[$sid]['avg_ica'] = $avg_pct !== null ? round($avg_pct, 2) : null;
                $courses[$sid]['at_risk_students'] = (int)($row['at_risk'] ?? 0);
            }
        }
        mysqli_free_result($ica_res);
    }

    $subject_names = [];
    foreach ($courses as $cid => $course) {
        $subject_names[$cid] = $course['name_raw'] ?? $course['name'];
    }
    $escaped_names = array_map(static function ($name) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, $name) . "'";
    }, array_values($subject_names));
    if (!empty($escaped_names)) {
        $name_list = implode(',', $escaped_names);
                $syllabus_sql = "SELECT sp.subject,
                                MAX(CASE WHEN sp.timeline LIKE 'week_%' AND CAST(SUBSTRING_INDEX(sp.timeline, '_', -1) AS UNSIGNED) BETWEEN 1 AND 3 THEN sp.completion_percentage ELSE NULL END) AS week3_completion,
                                MAX(CASE WHEN sp.timeline LIKE 'week_%' AND CAST(SUBSTRING_INDEX(sp.timeline, '_', -1) AS UNSIGNED) BETWEEN 1 AND 5 THEN sp.completion_percentage ELSE NULL END) AS week5_completion,
                                MAX(CASE WHEN sp.timeline LIKE 'week_%' AND CAST(SUBSTRING_INDEX(sp.timeline, '_', -1) AS UNSIGNED) BETWEEN 6 AND 10 THEN sp.completion_percentage ELSE NULL END) AS week10_completion,
                                MAX(CASE WHEN sp.timeline = 'final' THEN sp.completion_percentage ELSE NULL END) AS final_completion,
                                MAX(sp.completion_percentage) AS latest_completion,
                                MAX(sp.updated_at) AS last_updated
                        FROM syllabus_progress sp
                        JOIN teacher_subject_assignments tsa ON tsa.teacher_id = sp.teacher_id
                        JOIN subjects subj ON subj.id = tsa.subject_id AND subj.subject_name = sp.subject
                        JOIN classes c ON c.id = tsa.class_id" . ($activeTermId > 0 ? " AND (c.academic_term_id = " . (int)$activeTermId . " OR c.academic_term_id IS NULL)" : '') . ($termStartEsc && $termEndEsc ? "
                        WHERE sp.updated_at BETWEEN '" . $termStartEsc . "' AND '" . $termEndEsc . "'" : "
                        WHERE 1 = 1") . "
                              AND subj.id IN ($id_list)
                              AND sp.subject IN ($name_list)
                        GROUP BY sp.subject";
        $syllabus_res = mysqli_query($conn, $syllabus_sql);
        if ($syllabus_res) {
            $subject_lookup = array_flip($subject_names);
            while ($row = mysqli_fetch_assoc($syllabus_res)) {
                $subject_name = $row['subject'];
                if (isset($subject_lookup[$subject_name])) {
                    $sid = $subject_lookup[$subject_name];
                    $week3 = $row['week3_completion'] !== null ? (int)$row['week3_completion'] : null;
                    $week5 = $row['week5_completion'] !== null ? (int)$row['week5_completion'] : null;
                    $week10 = $row['week10_completion'] !== null ? (int)$row['week10_completion'] : null;
                    $final = $row['final_completion'] !== null ? (int)$row['final_completion'] : null;
                    $latest = $row['latest_completion'] !== null ? (int)$row['latest_completion'] : null;
                    if (isset($courses[$sid])) {
                        $selected = null;
                        if ($timeline_filter === 'week5') {
                            $selected = $week5;
                        } elseif ($timeline_filter === 'week10') {
                            $selected = $week10;
                        } elseif ($timeline_filter === 'final') {
                            $selected = $final;
                        } else {
                            $selected = $latest;
                        }
                        $courses[$sid]['avg_completion_selected'] = $selected;
                        $courses[$sid]['overall_completion'] = $latest;
                        $courses[$sid]['last_updated'] = $row['last_updated'];
                        $courses[$sid]['week3_progress'] = $week3;
                        $courses[$sid]['week5_progress'] = $week5;
                        $courses[$sid]['week10_progress'] = $week10;
                        $courses[$sid]['final_progress'] = $final;
                    }
                }
            }
            mysqli_free_result($syllabus_res);
        }
    }
}

$total_courses = count($courses);
$sum_completion = 0.0;
$sum_ica = 0.0;
$count_completion = 0;
$count_ica = 0;
$total_at_risk_students = 0;
$courses_behind = 0;
$coverage_percentages = [];
$status_counts = [
    'On Track' => 0,
    'Slightly Behind' => 0,
    'At Risk' => 0,
    'No Updates' => 0
];

foreach ($courses as $cid => &$course) {
    if ($course['total_students'] > 0) {
        $coverage = ($course['evaluated_students'] / $course['total_students']) * 100;
        $course['evaluation_coverage'] = round($coverage, 1);
        $coverage_percentages[] = $coverage;
    } else {
        $course['evaluation_coverage'] = null;
    }
    if ($course['avg_completion_selected'] !== null) {
        $sum_completion += $course['avg_completion_selected'];
        $count_completion++;
    }
    if ($course['overall_completion'] !== null) {
        if ($course['overall_completion'] >= 75) {
            $course['status'] = 'On Track';
        } elseif ($course['overall_completion'] >= 50) {
            $course['status'] = 'Slightly Behind';
            $courses_behind++;
        } else {
            $course['status'] = 'At Risk';
            $courses_behind++;
        }
    } else {
        $course['status'] = 'No Updates';
    }
    if (isset($status_counts[$course['status']])) {
        $status_counts[$course['status']]++;
    }
    if ($course['avg_ica'] !== null) {
        $sum_ica += $course['avg_ica'];
        $count_ica++;
    }
    $total_at_risk_students += (int)$course['at_risk_students'];
}
unset($course);

$avg_completion_overall = $count_completion > 0 ? round($sum_completion / $count_completion, 1) : 0;
$avg_ica_overall = $count_ica > 0 ? round($sum_ica / $count_ica, 1) : 0;
$avg_coverage_overall = !empty($coverage_percentages) ? round(array_sum($coverage_percentages) / count($coverage_percentages), 1) : 0;

$chart_data = [
    'labels' => [],
    'fullLabels' => [],
    'subjectIds' => [],
    'selectedCompletion' => [],
    'overallCompletion' => [],
    'avgIca' => [],
    'coverage' => [],
    'atRisk' => []
];

foreach ($courses as $course) {
    $chart_data['labels'][] = $course['abbr'];
    $chart_data['fullLabels'][] = $course['name'];
    $chart_data['subjectIds'][] = $course['id'];
    $chart_data['selectedCompletion'][] = $course['avg_completion_selected'] !== null ? $course['avg_completion_selected'] : 0;
    $chart_data['overallCompletion'][] = $course['overall_completion'] !== null ? $course['overall_completion'] : 0;
    $chart_data['avgIca'][] = $course['avg_ica'] !== null ? $course['avg_ica'] : 0;
    $chart_data['coverage'][] = $course['evaluation_coverage'] !== null ? $course['evaluation_coverage'] : 0;
    $chart_data['atRisk'][] = (int)$course['at_risk_students'];
}

if (isset($_GET['action']) && $_GET['action'] === 'course_details') {
    header('Content-Type: application/json');
    $requested_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
    if ($requested_id <= 0) {
        echo json_encode(['error' => 'Invalid course']);
        exit;
    }

    $course_stmt = mysqli_prepare($conn, "SELECT s.id, s.subject_name, s.semester, s.school FROM subjects s WHERE s.id = ? LIMIT 1");
    if (!$course_stmt) {
        echo json_encode(['error' => 'Course not found']);
        exit;
    }
    mysqli_stmt_bind_param($course_stmt, 'i', $requested_id);
    mysqli_stmt_execute($course_stmt);
    $course_res = mysqli_stmt_get_result($course_stmt);
    $course_info = $course_res ? mysqli_fetch_assoc($course_res) : null;
    if ($course_res) {
        mysqli_free_result($course_res);
    }
    mysqli_stmt_close($course_stmt);

    if (!$course_info) {
        echo json_encode(['error' => 'Course not found']);
        exit;
    }
    if ($pc_school !== '' && $course_info['school'] !== $pc_school) {
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    if ($activeTermId > 0) {
        $belongs_stmt = mysqli_prepare($conn, "SELECT 1 FROM teacher_subject_assignments tsa JOIN classes c ON c.id = tsa.class_id WHERE tsa.subject_id = ? AND (c.academic_term_id = ? OR c.academic_term_id IS NULL) LIMIT 1");
        if ($belongs_stmt) {
            mysqli_stmt_bind_param($belongs_stmt, 'ii', $requested_id, $activeTermId);
            mysqli_stmt_execute($belongs_stmt);
            mysqli_stmt_store_result($belongs_stmt);
            if (mysqli_stmt_num_rows($belongs_stmt) === 0) {
                mysqli_stmt_close($belongs_stmt);
                echo json_encode(['error' => 'Course not linked to selected semester']);
                exit;
            }
            mysqli_stmt_close($belongs_stmt);
        }
    }

    $subject_totals = [
        'theory' => 0.0,
        'practical' => 0.0,
        'total' => 0.0
    ];
    $plan_weeks = 15;
    $current_week = null;
    $today = new DateTime();

    $detail_meta_stmt = mysqli_prepare($conn, "SELECT COALESCE(sd.theory_hours, 0) AS theory_hours, COALESCE(sd.practical_hours, 0) AS practical_hours, COALESCE(s.total_planned_hours, 0) AS total_planned_hours FROM subjects s LEFT JOIN subject_details sd ON sd.subject_id = s.id WHERE s.id = ? LIMIT 1");
    if ($detail_meta_stmt) {
        mysqli_stmt_bind_param($detail_meta_stmt, 'i', $requested_id);
        mysqli_stmt_execute($detail_meta_stmt);
        $meta_res = mysqli_stmt_get_result($detail_meta_stmt);
        if ($meta_res && ($meta_row = mysqli_fetch_assoc($meta_res))) {
            $subject_totals['theory'] = isset($meta_row['theory_hours']) ? (float)$meta_row['theory_hours'] : 0.0;
            $subject_totals['practical'] = isset($meta_row['practical_hours']) ? (float)$meta_row['practical_hours'] : 0.0;
            $subject_totals['total'] = isset($meta_row['total_planned_hours']) ? (float)$meta_row['total_planned_hours'] : 0.0;
            if ($subject_totals['total'] <= 0) {
                $subject_totals['total'] = $subject_totals['theory'] + $subject_totals['practical'];
            }
        }
        if ($meta_res) {
            mysqli_free_result($meta_res);
        }
        mysqli_stmt_close($detail_meta_stmt);
    }

    if ($activeTerm && !empty($activeTerm['start_date']) && !empty($activeTerm['end_date'])) {
        try {
            $start_date = new DateTime($activeTerm['start_date']);
            $end_date = new DateTime($activeTerm['end_date']);
            $total_days = $start_date->diff($end_date)->days + 1;
            $total_weeks = max(1, (int)ceil($total_days / 7));
            $instructional_weeks = min(15, $total_weeks);
            $plan_weeks = $instructional_weeks > 0 ? $instructional_weeks : $total_weeks;
            if ($plan_weeks <= 0) {
                $plan_weeks = 15;
            }
            if ($today < $start_date) {
                $current_week = 1;
            } elseif ($today > $end_date) {
                $current_week = $plan_weeks;
            } else {
                $days_passed = $start_date->diff($today)->days;
                $current_week = min($plan_weeks, (int)floor($days_passed / 7) + 1);
            }
        } catch (Exception $e) {
            // Ignore calendar parsing issues and rely on defaults
        }
    } elseif (!empty($course_info['school'])) {
        $calendar_stmt = mysqli_prepare($conn, "SELECT start_date, end_date FROM academic_calendar WHERE school_name = ? AND CURDATE() BETWEEN start_date AND end_date LIMIT 1");
        if ($calendar_stmt) {
            mysqli_stmt_bind_param($calendar_stmt, 's', $course_info['school']);
            mysqli_stmt_execute($calendar_stmt);
            $calendar_res = mysqli_stmt_get_result($calendar_stmt);
            if ($calendar_res && ($calendar_row = mysqli_fetch_assoc($calendar_res))) {
                try {
                    $start_date = new DateTime($calendar_row['start_date']);
                    $end_date = new DateTime($calendar_row['end_date']);
                    $total_days = $start_date->diff($end_date)->days + 1;
                    $total_weeks = max(1, (int)ceil($total_days / 7));
                    $instructional_weeks = min(15, $total_weeks);
                    $plan_weeks = $instructional_weeks > 0 ? $instructional_weeks : $total_weeks;
                    if ($plan_weeks <= 0) {
                        $plan_weeks = 15;
                    }
                    if ($today < $start_date) {
                        $current_week = 1;
                    } elseif ($today > $end_date) {
                        $current_week = $plan_weeks;
                    } else {
                        $days_passed = $start_date->diff($today)->days;
                        $current_week = min($plan_weeks, (int)floor($days_passed / 7) + 1);
                    }
                } catch (Exception $e) {
                    // Ignore calendar parsing issues and rely on defaults
                }
                mysqli_free_result($calendar_res);
            }
            mysqli_stmt_close($calendar_stmt);
        }
    }

    if ($plan_weeks <= 0) {
        $plan_weeks = 15;
    }

    $detail = [
        'subject_name' => format_subject_display($course_info['subject_name'] ?? ''),
        'subject_name_raw' => $course_info['subject_name'] ?? '',
        'semester' => $course_info['semester'],
        'teachers' => [],
        'classes' => [],
        'students' => ['total' => 0, 'evaluated' => 0],
        'timeline' => [],
        'ica' => [
            'average_percentage' => null,
            'average_marks' => ['obtained' => null, 'total' => null],
            'at_risk' => 0,
            'distribution' => ['good' => 0, 'average' => 0, 'at_risk' => 0]
        ],
        'recent_topics' => []
    ];

    $teachers_sql = "SELECT DISTINCT u.name AS teacher_name, c.class_name
        FROM teacher_subject_assignments tsa
        LEFT JOIN users u ON u.id = tsa.teacher_id
        JOIN classes c ON c.id = tsa.class_id" . ($activeTermId > 0 ? " AND (c.academic_term_id = " . (int)$activeTermId . " OR c.academic_term_id IS NULL)" : '') . "
        WHERE tsa.subject_id = " . (int)$requested_id . "
        ORDER BY u.name";
    $teachers_res = mysqli_query($conn, $teachers_sql);
    if ($teachers_res) {
        while ($row = mysqli_fetch_assoc($teachers_res)) {
            if (!empty($row['teacher_name'])) {
                $detail['teachers'][] = format_person_display(trim((string)$row['teacher_name']));
            }
            if ($row['class_name']) {
                $detail['classes'][] = $row['class_name'];
            }
        }
        $detail['teachers'] = array_values(array_unique($detail['teachers']));
        $detail['classes'] = array_values(array_unique($detail['classes']));
        mysqli_free_result($teachers_res);
    }

    $detail_students_sql = "SELECT COUNT(DISTINCT stu.id) AS total_students
        FROM teacher_subject_assignments tsa
        JOIN classes c ON c.id = tsa.class_id" . ($activeTermId > 0 ? " AND (c.academic_term_id = " . (int)$activeTermId . " OR c.academic_term_id IS NULL)" : '') . "
        JOIN students stu ON stu.class_id = tsa.class_id
            AND (tsa.section_id IS NULL OR tsa.section_id = 0 OR COALESCE(stu.section_id, 0) = tsa.section_id)
        WHERE tsa.subject_id = " . (int)$requested_id;
    $detail_students_res = mysqli_query($conn, $detail_students_sql);
    if ($detail_students_res && ($row = mysqli_fetch_assoc($detail_students_res))) {
        $detail['students']['total'] = (int)$row['total_students'];
        mysqli_free_result($detail_students_res);
    }

    $detail_evaluated_sql = "SELECT COUNT(DISTINCT CONCAT_WS('-', ism.student_id, c.id)) AS evaluated_students
        FROM ica_student_marks ism
        JOIN ica_components ic ON ic.id = ism.component_id
        JOIN teacher_subject_assignments tsa ON tsa.subject_id = ic.subject_id AND tsa.teacher_id = ism.teacher_id
        JOIN classes c ON c.id = tsa.class_id" . ($activeTermId > 0 ? " AND (c.academic_term_id = " . (int)$activeTermId . " OR c.academic_term_id IS NULL)" : '') . "
        WHERE ic.subject_id = " . (int)$requested_id;
    $detail_evaluated_res = mysqli_query($conn, $detail_evaluated_sql);
    if ($detail_evaluated_res && ($row = mysqli_fetch_assoc($detail_evaluated_res))) {
        $detail['students']['evaluated'] = (int)$row['evaluated_students'];
        mysqli_free_result($detail_evaluated_res);
    }

    $detail_ica_sql = "SELECT
            AVG(student_total) AS avg_obtained,
            AVG(student_possible) AS avg_possible,
            AVG(CASE WHEN student_possible > 0 THEN (student_total / student_possible) * 100 ELSE NULL END) AS avg_pct,
            SUM(CASE WHEN student_possible > 0 AND (student_total / student_possible) * 100 < 50 THEN 1 ELSE 0 END) AS at_risk,
            SUM(CASE WHEN student_possible > 0 AND (student_total / student_possible) * 100 >= 70 THEN 1 ELSE 0 END) AS good_count,
            SUM(CASE WHEN student_possible > 0 AND (student_total / student_possible) * 100 >= 50 AND (student_total / student_possible) * 100 < 70 THEN 1 ELSE 0 END) AS average_count
        FROM (
            SELECT
                ism.student_id,
                SUM(ism.marks) AS student_total,
                SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 THEN ic.marks_per_instance ELSE 0 END) AS student_possible
            FROM ica_student_marks ism
            JOIN ica_components ic ON ic.id = ism.component_id
            JOIN teacher_subject_assignments tsa ON tsa.subject_id = ic.subject_id AND tsa.teacher_id = ism.teacher_id
            JOIN classes c ON c.id = tsa.class_id" . ($activeTermId > 0 ? " AND (c.academic_term_id = " . (int)$activeTermId . " OR c.academic_term_id IS NULL)" : '') . "
            WHERE ic.subject_id = " . (int)$requested_id . "
            GROUP BY ism.student_id
            HAVING student_total IS NOT NULL
        ) student_scores";
    $detail_ica_res = mysqli_query($conn, $detail_ica_sql);
    if ($detail_ica_res && ($row = mysqli_fetch_assoc($detail_ica_res))) {
        $avg_obtained = isset($row['avg_obtained']) ? (float)$row['avg_obtained'] : null;
        $avg_possible = isset($row['avg_possible']) ? (float)$row['avg_possible'] : null;
        if ($avg_obtained !== null) {
            $detail['ica']['average_marks']['obtained'] = round($avg_obtained, 2);
        }
        if ($avg_possible !== null) {
            $detail['ica']['average_marks']['total'] = round($avg_possible, 2);
        }
        $detail['ica']['average_percentage'] = isset($row['avg_pct']) ? round((float)$row['avg_pct'], 2) : null;
        $detail['ica']['at_risk'] = (int)($row['at_risk'] ?? 0);
        $detail['ica']['distribution']['good'] = (int)($row['good_count'] ?? 0);
        $detail['ica']['distribution']['average'] = (int)($row['average_count'] ?? 0);
        $detail['ica']['distribution']['at_risk'] = (int)($row['at_risk'] ?? 0);
        mysqli_free_result($detail_ica_res);
    }

    $detail_topics_sql = "SELECT DISTINCT sp.topic, sp.completion_percentage, sp.timeline, sp.updated_at
        FROM syllabus_progress sp
        JOIN teacher_subject_assignments tsa ON tsa.teacher_id = sp.teacher_id
        JOIN classes c ON c.id = tsa.class_id" . ($activeTermId > 0 ? " AND (c.academic_term_id = " . (int)$activeTermId . " OR c.academic_term_id IS NULL)" : '') . "
        JOIN subjects subj ON subj.id = tsa.subject_id AND subj.subject_name = sp.subject
        WHERE subj.id = " . (int)$requested_id . ($termStartEsc && $termEndEsc ? " AND sp.updated_at BETWEEN '" . $termStartEsc . "' AND '" . $termEndEsc . "'" : '') . "
        ORDER BY sp.updated_at DESC
        LIMIT 6";
    $detail_topics_res = mysqli_query($conn, $detail_topics_sql);
    if ($detail_topics_res) {
        while ($row = mysqli_fetch_assoc($detail_topics_res)) {
            $detail['recent_topics'][] = [
                'topic' => $row['topic'],
                'completion' => (int)$row['completion_percentage'],
                'timeline' => $row['timeline'],
                'updated_at' => $row['updated_at']
            ];
        }
        mysqli_free_result($detail_topics_res);
    }

    $timeline_rows = [];
    $timeline_sql = "SELECT timeline, planned_hours, actual_hours, actual_theory_hours, actual_practical_hours, extra_theory_hours, extra_practical_hours, completion_percentage, updated_at FROM syllabus_progress WHERE subject = '" . mysqli_real_escape_string($conn, $course_info['subject_name']) . "' ORDER BY updated_at ASC";
    $timeline_res = mysqli_query($conn, $timeline_sql);
    $max_week_recorded = 0;
    if ($timeline_res) {
        while ($row = mysqli_fetch_assoc($timeline_res)) {
            $timeline_label = $row['timeline'] ?? '';
            $week_number = null;
            if (preg_match('/week[\s_-]*(\d+)/i', $timeline_label, $matches)) {
                $week_number = (int)$matches[1];
                if ($week_number > $max_week_recorded) {
                    $max_week_recorded = $week_number;
                }
            }
            $timeline_rows[] = [
                'timeline_label' => $timeline_label,
                'week' => $week_number,
                'planned_hours' => isset($row['planned_hours']) ? (float)$row['planned_hours'] : null,
                'actual_hours' => isset($row['actual_hours']) ? (float)$row['actual_hours'] : null,
                'actual_theory' => isset($row['actual_theory_hours']) ? (float)$row['actual_theory_hours'] : 0.0,
                'actual_practical' => isset($row['actual_practical_hours']) ? (float)$row['actual_practical_hours'] : 0.0,
                'extra_theory' => isset($row['extra_theory_hours']) ? (float)$row['extra_theory_hours'] : 0.0,
                'extra_practical' => isset($row['extra_practical_hours']) ? (float)$row['extra_practical_hours'] : 0.0,
                'completion' => isset($row['completion_percentage']) ? (float)$row['completion_percentage'] : null,
                'updated_at' => $row['updated_at'] ?? null
            ];
            if (isset($row['planned_hours'])) {
                $subject_totals['total'] = max($subject_totals['total'], (float)$row['planned_hours']);
            }
        }
        mysqli_free_result($timeline_res);
    }

    if ($plan_weeks < $max_week_recorded) {
        $plan_weeks = $max_week_recorded;
    }
    if ($current_week === null && $max_week_recorded > 0) {
        $current_week = $max_week_recorded;
    }

    if ($subject_totals['total'] <= 0 && !empty($timeline_rows)) {
        foreach ($timeline_rows as $row) {
            if ($row['planned_hours'] !== null) {
                $subject_totals['total'] = max($subject_totals['total'], (float)$row['planned_hours']);
            }
            if ($row['actual_hours'] !== null) {
                $subject_totals['total'] = max($subject_totals['total'], (float)$row['actual_hours']);
            }
        }
    }

    $subject_total_reference = $subject_totals['total'];
    if ($subject_total_reference <= 0) {
        $subject_total_reference = $subject_totals['theory'] + $subject_totals['practical'];
        $subject_totals['total'] = $subject_total_reference;
    }

    $weekly_theory_hours = ($plan_weeks > 0 && $subject_totals['theory'] > 0) ? $subject_totals['theory'] / $plan_weeks : 0.0;
    $weekly_practical_hours = ($plan_weeks > 0 && $subject_totals['practical'] > 0) ? $subject_totals['practical'] / $plan_weeks : 0.0;
    $weekly_total_hours = $weekly_theory_hours + $weekly_practical_hours;
    if ($weekly_total_hours <= 0 && $plan_weeks > 0 && $subject_totals['total'] > 0) {
        $weekly_total_hours = $subject_totals['total'] / $plan_weeks;
    }

    $round_hours = static function ($value) {
        if ($value === null) {
            return null;
        }
        $numeric = (float)$value;
        if (abs($numeric - round($numeric)) < 0.05) {
            return (float)round($numeric);
        }
        return round($numeric, 1);
    };

    $milestone_rows = [
        'week5' => null,
        'week10' => null,
        'final' => null
    ];
    foreach ($timeline_rows as $row) {
        $week_val = $row['week'];
        if ($week_val !== null) {
            if ($week_val <= 5 && ($milestone_rows['week5'] === null || $week_val >= ($milestone_rows['week5']['week'] ?? 0))) {
                $milestone_rows['week5'] = $row;
            }
            if ($week_val <= 10 && ($milestone_rows['week10'] === null || $week_val >= ($milestone_rows['week10']['week'] ?? 0))) {
                $milestone_rows['week10'] = $row;
            }
        }
        if (isset($row['timeline_label']) && strcasecmp($row['timeline_label'], 'final') === 0) {
            $milestone_rows['final'] = $row;
        }
    }

    $latest_row = !empty($timeline_rows) ? end($timeline_rows) : null;
    if (!empty($timeline_rows)) {
        reset($timeline_rows);
    }

    $week_for_latest = $current_week;
    if ($week_for_latest === null && $latest_row && $latest_row['week'] !== null) {
        $week_for_latest = $latest_row['week'];
    }

    $milestone_definitions = [
        'week5' => ['label' => 'Week 5', 'week_limit' => 5, 'row' => $milestone_rows['week5']],
        'week10' => ['label' => 'Week 10', 'week_limit' => 10, 'row' => $milestone_rows['week10']],
        'final' => ['label' => 'Final Progress', 'week_limit' => $plan_weeks, 'row' => $milestone_rows['final'], 'is_final' => true],
        'latest' => ['label' => 'Latest Update', 'week_limit' => $week_for_latest, 'row' => $latest_row]
    ];

    foreach ($milestone_definitions as $key => $config) {
        $milestone_row = $config['row'] ?? null;
        $week_limit = isset($config['is_final']) && $config['is_final'] ? $plan_weeks : ($config['week_limit'] ?? null);

        $planned_total = null;
        $planned_theory = null;
        $planned_practical = null;

        if (!empty($config['is_final'])) {
            $planned_total = $subject_totals['total'] > 0 ? $subject_totals['total'] : null;
            $planned_theory = $subject_totals['theory'] > 0 ? $subject_totals['theory'] : null;
            $planned_practical = $subject_totals['practical'] > 0 ? $subject_totals['practical'] : null;
        } elseif ($week_limit !== null) {
            $effective_week = $plan_weeks > 0 ? min($plan_weeks, max(0, (int)$week_limit)) : max(0, (int)$week_limit);
            if ($effective_week > 0) {
                if ($subject_totals['theory'] > 0 && $weekly_theory_hours > 0) {
                    $planned_theory = min($subject_totals['theory'], $weekly_theory_hours * $effective_week);
                } elseif ($subject_totals['theory'] > 0) {
                    $planned_theory = min($subject_totals['theory'], $subject_totals['theory']);
                }
                if ($subject_totals['practical'] > 0 && $weekly_practical_hours > 0) {
                    $planned_practical = min($subject_totals['practical'], $weekly_practical_hours * $effective_week);
                } elseif ($subject_totals['practical'] > 0) {
                    $planned_practical = min($subject_totals['practical'], $subject_totals['practical']);
                }
                if ($subject_totals['theory'] <= 0 && $subject_totals['practical'] <= 0 && $subject_totals['total'] > 0) {
                    $planned_total = min($subject_totals['total'], $weekly_total_hours > 0 ? $weekly_total_hours * $effective_week : $subject_totals['total']);
                }
            } else {
                $planned_total = 0.0;
                $planned_theory = $subject_totals['theory'] > 0 ? 0.0 : null;
                $planned_practical = $subject_totals['practical'] > 0 ? 0.0 : null;
            }
        }

        if ($planned_total === null && $planned_theory === null && $planned_practical === null && $milestone_row && $milestone_row['planned_hours'] !== null) {
            $planned_total = (float)$milestone_row['planned_hours'];
        }

        if (($planned_theory !== null || $planned_practical !== null) && $planned_total === null) {
            $planned_total = ($planned_theory ?? 0) + ($planned_practical ?? 0);
        }

        if (($planned_theory === null && $planned_practical === null) && $planned_total !== null && $subject_total_reference > 0 && ($subject_totals['theory'] > 0 || $subject_totals['practical'] > 0)) {
            $ratio_theory = $subject_totals['theory'] > 0 ? $subject_totals['theory'] / $subject_total_reference : 0;
            $ratio_practical = $subject_totals['practical'] > 0 ? $subject_totals['practical'] / $subject_total_reference : 0;
            $ratio_sum = $ratio_theory + $ratio_practical;
            if ($ratio_sum > 0) {
                if ($subject_totals['theory'] > 0) {
                    $planned_theory = $planned_total * ($ratio_theory / $ratio_sum);
                }
                if ($subject_totals['practical'] > 0) {
                    $planned_practical = $planned_total * ($ratio_practical / $ratio_sum);
                }
            }
        }

        $planned_block = [
            'total' => $round_hours($planned_total),
            'theory' => $subject_totals['theory'] > 0 ? $round_hours($planned_theory) : null,
            'practical' => $subject_totals['practical'] > 0 ? $round_hours($planned_practical) : null
        ];

        $actual_total = null;
        $actual_theory = null;
        $actual_practical = null;
        if ($milestone_row) {
            $extra_theory_cumulative = 0.0;
            $extra_practical_cumulative = 0.0;
            $week_for_extras = null;
            if ($milestone_row['week'] !== null) {
                $week_for_extras = (int)$milestone_row['week'];
            } elseif ($week_limit !== null && is_numeric($week_limit)) {
                $week_for_extras = (int)$week_limit;
            }
            if ($week_for_extras !== null) {
                foreach ($timeline_rows as $row_extra) {
                    if ($row_extra['week'] !== null && $row_extra['week'] <= $week_for_extras) {
                        $extra_theory_cumulative += $row_extra['extra_theory'];
                        $extra_practical_cumulative += $row_extra['extra_practical'];
                    }
                }
            } else {
                foreach ($timeline_rows as $row_extra) {
                    $extra_theory_cumulative += $row_extra['extra_theory'];
                    $extra_practical_cumulative += $row_extra['extra_practical'];
                }
            }

            $actual_theory = $milestone_row['actual_theory'] + $extra_theory_cumulative;
            $actual_practical = $milestone_row['actual_practical'] + $extra_practical_cumulative;
            if ($milestone_row['actual_hours'] !== null) {
                $actual_total = (float)$milestone_row['actual_hours'];
            } else {
                $actual_total = $actual_theory + $actual_practical;
            }
        }

        $actual_block = [
            'total' => $round_hours($actual_total),
            'theory' => ($subject_totals['theory'] > 0 || ($actual_theory !== null && $actual_theory > 0)) ? $round_hours($actual_theory) : null,
            'practical' => ($subject_totals['practical'] > 0 || ($actual_practical !== null && $actual_practical > 0)) ? $round_hours($actual_practical) : null
        ];

        $completion_value = $milestone_row && $milestone_row['completion'] !== null ? round((float)$milestone_row['completion'], 1) : null;

        $detail['timeline'][$key] = [
            'label' => $config['label'],
            'planned' => $planned_block,
            'actual' => $actual_block,
            'completion' => $completion_value,
            'week_number' => isset($config['is_final']) && $config['is_final'] ? null : ($week_limit ?? ($milestone_row['week'] ?? null)),
            'timeline_label' => $milestone_row['timeline_label'] ?? null,
            'updated_at' => $milestone_row['updated_at'] ?? null
        ];
    }

    if (!isset($detail['timeline']['week5'])) {
        $detail['timeline']['week5'] = null;
    }
    if (!isset($detail['timeline']['week10'])) {
        $detail['timeline']['week10'] = null;
    }
    if (!isset($detail['timeline']['final'])) {
        $detail['timeline']['final'] = null;
    }
    if (!isset($detail['timeline']['latest'])) {
        $detail['timeline']['latest'] = null;
    }

    echo json_encode($detail);
    exit;
}

if ($export_csv) {
    $filename_parts = [];
    if ($school_filter !== '') {
        $filename_parts[] = preg_replace('/\s+/', '_', $school_filter);
    }
    if ($semester_filter !== '') {
        $filename_parts[] = 'Semester_' . preg_replace('/\s+/', '_', $semester_filter);
    }
    if ($timeline_filter !== '') {
        $filename_parts[] = ucfirst($timeline_filter);
    }
    $filename_parts[] = 'Course_Progress';
    $file_stub = implode('_', $filename_parts);
    if ($file_stub === '') {
        $file_stub = 'Course_Progress';
    }
    $filename = $file_stub . '_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Subject', 'Semester', 'Teachers', 'Classes', 'Syllabus (' . ucfirst($timeline_filter) . ') %', 'Overall Completion %', 'Avg ICA %', 'Evaluated Students', 'Total Students', 'Evaluation Coverage %', 'At-Risk Students', 'Status']);
    foreach ($courses as $course) {
        fputcsv($out, [
            $course['name'],
            $course['semester'],
            $course['teachers'],
            $course['classes'],
            $course['avg_completion_selected'] !== null ? $course['avg_completion_selected'] : 'N/A',
            $course['overall_completion'] !== null ? $course['overall_completion'] : 'N/A',
            $course['avg_ica'] !== null ? $course['avg_ica'] : 'N/A',
            $course['evaluated_students'],
            $course['total_students'],
            $course['evaluation_coverage'] !== null ? $course['evaluation_coverage'] : 'N/A',
            $course['at_risk_students'],
            $course['status']
        ]);
    }
    fclose($out);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Progress - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; align-items: end; }
        .overview-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .overview-card { display: flex; align-items: center; gap: 14px; background: #fff; padding: 18px; border-radius: 10px; box-shadow: 0 8px 20px rgba(166, 25, 46, 0.12); }
        .overview-card i { font-size: 28px; color: #A6192E; }
        .overview-card .card-value { font-size: 1.6rem; font-weight: 700; color: #2c3e50; }
        .overview-card .card-label { font-size: 0.95rem; color: #6c757d; }
        .status-pill { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-weight: 600; font-size: 0.85rem; }
        .status-ontrack { background: rgba(40,167,69,0.15); color: #198754; }
        .status-behind { background: rgba(255,193,7,0.18); color: #b8860b; }
        .status-risk { background: rgba(220,53,69,0.18); color: #dc3545; }
        .status-none { background: rgba(108,117,125,0.18); color: #6c757d; }
        .clickable-row { cursor: pointer; transition: background 0.2s; }
        .clickable-row:hover { background: rgba(166,25,46,0.08); }
        .chart-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-top: 24px; }
        .chart-card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
        .chart-card h4 { margin-bottom: 12px; color: #A6192E; font-size: 1.05rem; }
        .table-wrapper { overflow-x: auto; }
        .actions-bar { display: flex; flex-wrap: wrap; gap: 12px; justify-content: flex-end; margin-bottom: 12px; }
        .actions-bar a, .actions-bar button { text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .empty-state { background: #fff; padding: 30px; border-radius: 10px; text-align: center; color: #6c757d; box-shadow: 0 6px 16px rgba(0,0,0,0.08); }
        .modal { display: none; position: fixed; z-index: 2000; inset: 0; background: rgba(0,0,0,0.55); padding: 30px; overflow-y: auto; align-items: flex-start; justify-content: center; min-height: 100vh; }
        .modal-content { max-width: 900px; width: 100%; margin: 40px auto; background: #fff; border-radius: 14px; padding: 24px; position: relative; max-height: calc(100vh - 80px); overflow-y: auto; }
        .modal-close { position: absolute; top: 16px; right: 18px; border: none; background: none; font-size: 1.4rem; cursor: pointer; color: #6c757d; }
        .modal-section { margin-bottom: 18px; }
        .modal-section h5 { margin-bottom: 8px; color: #A6192E; }
        .tag-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .tag { background: rgba(166,25,46,0.12); color: #A6192E; padding: 4px 10px; border-radius: 999px; font-size: 0.85rem; }
        .metric-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; }
        .metric-card { background: #f8f9fa; padding: 12px; border-radius: 10px; text-align: center; }
        .metric-card h6 { font-size: 0.9rem; color: #6c757d; margin-bottom: 6px; }
        .metric-card span { font-size: 1.2rem; font-weight: 700; color: #2c3e50; }
        .timeline-list { list-style: none; padding: 0; margin: 0; display: grid; gap: 6px; }
        .timeline-list li { background: #f8f9fa; padding: 12px; border-radius: 8px; display: flex; flex-direction: column; gap: 6px; }
        .timeline-header { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; }
        .timeline-label { font-weight: 600; color: #2c3e50; }
        .timeline-meta { color: #6c757d; font-size: 0.85rem; }
        .timeline-body { display: grid; gap: 4px; font-size: 0.95rem; color: #2c3e50; }
        .timeline-body strong { color: #A6192E; }
        .course-name-abbr { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: #2c3e50; letter-spacing: 0.35px; }
        .course-name-abbr:hover { color: #A6192E; }
        .chart-clickable { cursor: pointer; }
        body.modal-open { overflow: hidden; }
        @media (max-width: 768px) {
            .overview-grid { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
            .chart-grid { grid-template-columns: 1fr; }
            .modal { padding: 16px; }
            .modal-content { margin: 24px auto; max-height: calc(100vh - 48px); }
        }
    </style>
</head>
<body class="program-chair">
    <div class="dashboard">
        <div class="sidebar">
            <h2>ICA Tracker</h2>
            <a href="program_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="teacher_progress.php"><i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span></a>
            <a href="student_progress.php"><i class="fas fa-user-graduate"></i> <span>Students</span></a>
            <a href="course_progress.php" class="active"><i class="fas fa-book"></i> <span>Courses</span></a>
            <a href="program_reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
            <a href="send_alerts.php"><i class="fas fa-bell"></i> <span>Alerts</span></a>
            <a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
            <a href="edit_profile.php"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>

        <div class="main-content">
            <div class="header">
                <h2>Course Progress Overview</h2>
                <div>
                    <span style="font-weight:600; color:#6c757d;">Timeline:</span>
                    <span style="margin-left:6px; font-weight:700; color:#A6192E; text-transform:none;">
                        <?php echo htmlspecialchars($timeline_filter_label); ?>
                    </span>
                </div>
            </div>

            <div class="container">
                <div class="overview-grid">
                    <div class="overview-card">
                        <i class="fas fa-layer-group"></i>
                        <div>
                            <div class="card-value"><?php echo $total_courses; ?></div>
                            <div class="card-label">Active Courses</div>
                        </div>
                    </div>
                    <div class="overview-card">
                        <i class="fas fa-clipboard-check"></i>
                        <div>
                            <div class="card-value"><?php echo $avg_completion_overall; ?>%</div>
                            <div class="card-label">Avg Syllabus (<?php echo htmlspecialchars($timeline_filter_label); ?>)</div>
                        </div>
                    </div>
                    <div class="overview-card">
                        <i class="fas fa-chart-line"></i>
                        <div>
                            <div class="card-value"><?php echo $avg_ica_overall; ?>%</div>
                            <div class="card-label">Avg ICA Performance</div>
                        </div>
                    </div>
                    <div class="overview-card">
                        <i class="fas fa-user-shield"></i>
                        <div>
                            <div class="card-value"><?php echo $total_at_risk_students; ?></div>
                            <div class="card-label">Students At Risk</div>
                        </div>
                    </div>
                </div>

                <form method="get">
                    <div class="filter-grid">
                        <div>
                            <label for="school">School</label>
                            <select name="school" id="school">
                                <option value="">All Schools</option>
                                <?php foreach ($available_schools as $school) : ?>
                                    <option value="<?php echo htmlspecialchars($school); ?>" <?php echo $school_filter === $school ? 'selected' : ''; ?>><?php echo htmlspecialchars($school); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="semester">Semester</label>
                            <select name="semester" id="semester">
                                <option value="">All Semesters</option>
                                <?php foreach ($semester_options as $semester) : ?>
                                    <option value="<?php echo htmlspecialchars($semester); ?>" <?php echo $semester_filter === $semester ? 'selected' : ''; ?>>Semester <?php echo htmlspecialchars($semester); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="class_id">Class</label>
                            <select name="class_id" id="class_id">
                                <option value="">All Classes</option>
                                <?php foreach ($class_options as $class) : ?>
                                    <option value="<?php echo $class['id']; ?>" <?php echo $class_filter === $class['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($class['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="teacher_id">Teacher</label>
                            <select name="teacher_id" id="teacher_id">
                                <option value="">All Teachers</option>
                                <?php foreach ($teacher_options as $teacher) : ?>
                                    <?php
                                        $teacherNameDisplay = $teacher['name_display'] ?? '';
                                        if ($teacherNameDisplay === '' && isset($teacher['name'])) {
                                            $teacherNameDisplay = format_person_display(trim((string)$teacher['name']));
                                        }
                                    ?>
                                    <option value="<?php echo $teacher['id']; ?>" <?php echo $teacher_filter === $teacher['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($teacherNameDisplay !== '' ? $teacherNameDisplay : ($teacher['name'] ?? '')); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="subject_id">Course</label>
                            <select name="subject_id" id="subject_id">
                                <option value="">All Courses</option>
                                <?php foreach ($subject_options as $subject) : ?>
                                    <option value="<?php echo $subject['id']; ?>" <?php echo $subject_filter === $subject['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($subject['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="timeline">Timeline Focus</label>
                            <select name="timeline" id="timeline">
                                <?php foreach ($allowed_timelines as $timeline) : ?>
                                    <?php $option_label = $timeline_labels[$timeline] ?? ucfirst($timeline); ?>
                                    <option value="<?php echo $timeline; ?>" <?php echo $timeline_filter === $timeline ? 'selected' : ''; ?>><?php echo htmlspecialchars($option_label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>&nbsp;</label>
                            <button type="submit"><i class="fas fa-filter"></i> Apply Filters</button>
                        </div>
                    </div>
                </form>

                <div class="actions-bar">
                    <a href="course_progress.php" class="theme-toggle" style="text-decoration:none; display:inline-flex; align-items:center;">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, ['export' => 'csv']))); ?>" class="theme-toggle" style="text-decoration:none; display:inline-flex; align-items:center;">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </a>
                </div>

                <?php if (empty($courses)) : ?>
                    <div class="empty-state">
                        <i class="fas fa-info-circle" style="font-size:2rem; margin-bottom:10px; color:#A6192E;"></i>
                        <p>No courses match the selected filters. Adjust the filters to view progress data.</p>
                    </div>
                <?php else : ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Semester</th>
                                    <th>Teachers</th>
                                    <th>Classes</th>
                                    <th>Syllabus (<?php echo htmlspecialchars($timeline_filter_label); ?>)</th>
                                    <th>Overall Completion</th>
                                    <th>Avg ICA</th>
                                    <th>Evaluated / Total</th>
                                    <th>Coverage %</th>
                                    <th>At-Risk</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course) : ?>
                                    <tr class="clickable-row" data-course-id="<?php echo $course['id']; ?>">
                                        <td>
                                            <span class="course-name-abbr" title="<?php echo htmlspecialchars($course['name']); ?>">
                                                <?php echo htmlspecialchars($course['abbr']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($course['semester']); ?></td>
                                        <td><?php echo htmlspecialchars($course['teachers'] ?: 'Unassigned'); ?></td>
                                        <td><?php echo htmlspecialchars($course['classes'] ?: 'Unassigned'); ?></td>
                                        <td><?php echo $course['avg_completion_selected'] !== null ? $course['avg_completion_selected'] . '%' : 'N/A'; ?></td>
                                        <td><?php echo $course['overall_completion'] !== null ? $course['overall_completion'] . '%' : 'N/A'; ?></td>
                                        <td><?php echo $course['avg_ica'] !== null ? $course['avg_ica'] . '%' : 'N/A'; ?></td>
                                        <td><?php echo $course['evaluated_students'] . ' / ' . $course['total_students']; ?></td>
                                        <td><?php echo $course['evaluation_coverage'] !== null ? $course['evaluation_coverage'] . '%' : 'N/A'; ?></td>
                                        <td><?php echo (int)$course['at_risk_students']; ?></td>
                                        <td>
                                            <?php
                                            $status = $course['status'];
                                            $status_class = 'status-none';
                                            if ($status === 'On Track') {
                                                $status_class = 'status-ontrack';
                                            } elseif ($status === 'Slightly Behind') {
                                                $status_class = 'status-behind';
                                            } elseif ($status === 'At Risk') {
                                                $status_class = 'status-risk';
                                            }
                                            ?>
                                            <span class="status-pill <?php echo $status_class; ?>"><?php echo htmlspecialchars($status); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="chart-grid">
                        <div class="chart-card">
                            <h4>Syllabus Completion Trend</h4>
                            <canvas id="syllabusChart" class="chart-clickable" height="280"></canvas>
                        </div>
                        <div class="chart-card">
                            <h4>ICA Performance & Coverage</h4>
                            <canvas id="icaChart" class="chart-clickable" height="280"></canvas>
                        </div>
                        <div class="chart-card">
                            <h4>Course Status Distribution</h4>
                            <canvas id="statusChart" height="280"></canvas>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal" id="courseModal" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="modal-content" role="document" tabindex="-1">
            <button class="modal-close" id="modalClose" aria-label="Close">&times;</button>
            <div id="modalBody"></div>
        </div>
    </div>

    <script>
        const chartPayload = <?php echo json_encode($chart_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        const statusCounts = <?php echo json_encode($status_counts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

        function openCourseFromIndex(chartIndex) {
            if (chartIndex === undefined || chartIndex === null) {
                return;
            }
            const ids = chartPayload.subjectIds || [];
            const subjectId = ids[chartIndex];
            if (subjectId) {
                openModal(subjectId);
            }
        }

        function formatCourseTooltipTitle(contextItems) {
            if (!contextItems.length) {
                return '';
            }
            const dataIndex = contextItems[0].dataIndex;
            if (typeof dataIndex === 'number' && Array.isArray(chartPayload.fullLabels) && chartPayload.fullLabels[dataIndex]) {
                return chartPayload.fullLabels[dataIndex];
            }
            return contextItems[0].label || '';
        }

        function formatNumberValue(value) {
            if (value === null || value === undefined) {
                return null;
            }
            const numeric = Number(value);
            if (!Number.isFinite(numeric)) {
                return null;
            }
            if (Math.abs(numeric - Math.round(numeric)) < 0.05) {
                return String(Math.round(numeric));
            }
            return numeric.toFixed(1);
        }

        function formatHoursBlock(block) {
            if (!block || block.total === null || block.total === undefined) {
                return 'N/A';
            }
            const totalLabel = formatNumberValue(block.total);
            const parts = [];
            if (block.theory !== null && block.theory !== undefined) {
                const theoryLabel = formatNumberValue(block.theory);
                if (theoryLabel !== null) {
                    parts.push(`${theoryLabel}T`);
                }
            }
            if (block.practical !== null && block.practical !== undefined) {
                const practicalLabel = formatNumberValue(block.practical);
                if (practicalLabel !== null) {
                    parts.push(`${practicalLabel}P`);
                }
            }
            if (totalLabel === null) {
                return 'N/A';
            }
            return parts.length ? `${totalLabel} (${parts.join(' + ')})` : totalLabel;
        }

        function formatDateLabel(value) {
            if (!value) {
                return '';
            }
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return '';
            }
            return date.toLocaleDateString(undefined, { day: '2-digit', month: 'short' });
        }

        const syllabusCtx = document.getElementById('syllabusChart');
        if (syllabusCtx && chartPayload.labels.length) {
            new Chart(syllabusCtx, {
                type: 'bar',
                data: {
                    labels: chartPayload.labels,
                    datasets: [
                        {
                            label: '<?php echo htmlspecialchars($timeline_filter_label); ?> Completion %',
                            data: chartPayload.selectedCompletion,
                            backgroundColor: 'rgba(166, 25, 46, 0.6)',
                            borderRadius: 6
                        },
                        {
                            label: 'Overall Completion %',
                            data: chartPayload.overallCompletion,
                            backgroundColor: 'rgba(52, 152, 219, 0.5)',
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true, max: 100 }
                    },
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                title: formatCourseTooltipTitle
                            }
                        }
                    },
                    onClick: (_, activeElements) => {
                        if (activeElements.length) {
                            openCourseFromIndex(activeElements[0].index);
                        }
                    },
                    onHover: (_, activeElements, chart) => {
                        chart.canvas.style.cursor = activeElements.length ? 'pointer' : 'default';
                    }
                }
            });
        }

        const icaCtx = document.getElementById('icaChart');
        if (icaCtx && chartPayload.labels.length) {
            new Chart(icaCtx, {
                type: 'line',
                data: {
                    labels: chartPayload.labels,
                    datasets: [
                        {
                            label: 'Avg ICA %',
                            data: chartPayload.avgIca,
                            borderColor: 'rgba(166, 25, 46, 0.8)',
                            backgroundColor: 'rgba(166, 25, 46, 0.12)',
                            tension: 0.3,
                            fill: true,
                            pointRadius: 4
                        },
                        {
                            label: 'Evaluation Coverage %',
                            data: chartPayload.coverage,
                            borderColor: 'rgba(40, 167, 69, 0.8)',
                            backgroundColor: 'rgba(40, 167, 69, 0.12)',
                            tension: 0.3,
                            fill: true,
                            pointRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true, max: 100 }
                    },
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                title: formatCourseTooltipTitle
                            }
                        }
                    },
                    onClick: (_, activeElements) => {
                        if (activeElements.length) {
                            openCourseFromIndex(activeElements[0].index);
                        }
                    },
                    onHover: (_, activeElements, chart) => {
                        chart.canvas.style.cursor = activeElements.length ? 'pointer' : 'default';
                    }
                }
            });
        }

        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            const statusLabels = Object.keys(statusCounts);
            const statusValues = Object.values(statusCounts);
            const statusTotal = statusValues.reduce((sum, value) => sum + value, 0);
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusValues,
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.7)',
                            'rgba(255, 193, 7, 0.7)',
                            'rgba(220, 53, 69, 0.7)',
                            'rgba(108,117,125,0.6)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const value = context.parsed || 0;
                                    const percentage = statusTotal > 0 ? ((value / statusTotal) * 100).toFixed(1) : '0.0';
                                    const courseLabel = value === 1 ? 'course' : 'courses';
                                    return `${context.label}: ${value} ${courseLabel} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        const modal = document.getElementById('courseModal');
        const modalBody = document.getElementById('modalBody');
        const modalClose = document.getElementById('modalClose');
        const modalContent = modal.querySelector('.modal-content');
        let lastFocusedElement = null;
        let modalRequestToken = 0;

        function closeModal() {
            if (!document.body.classList.contains('modal-open')) {
                return;
            }
            modalRequestToken++;
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            modal.removeAttribute('aria-labelledby');
            if (modalContent) {
                modalContent.removeAttribute('aria-labelledby');
                modalContent.scrollTop = 0;
            }
            document.body.classList.remove('modal-open');
            modalBody.innerHTML = '';
            if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
                lastFocusedElement.focus();
            }
            lastFocusedElement = null;
        }

        function renderCourseDetails(data) {
            const teachers = data.teachers.length ? data.teachers.join(', ') : 'Unassigned';
            const classes = data.classes.length ? data.classes.join(', ') : 'Unassigned';
            const coverage = data.students.total > 0 ? `${((data.students.evaluated / data.students.total) * 100).toFixed(1)}%` : 'N/A';
            const avgMarks = data.ica && data.ica.average_marks ? data.ica.average_marks : null;
            let avgMarksLabel = 'N/A';
            if (avgMarks && avgMarks.obtained !== null && avgMarks.total !== null && Number(avgMarks.total) > 0) {
                const obtainedLabel = formatNumberValue(avgMarks.obtained);
                const totalLabel = formatNumberValue(avgMarks.total);
                if (obtainedLabel !== null && totalLabel !== null) {
                    avgMarksLabel = `${obtainedLabel} / ${totalLabel}`;
                }
            }

            const timelineOrder = ['week5', 'week10', 'final', 'latest'];
            const timelineMarkup = timelineOrder.map(key => {
                const entry = data.timeline ? data.timeline[key] : null;
                if (!entry) {
                    return '';
                }
                const plannedText = formatHoursBlock(entry.planned);
                const actualText = formatHoursBlock(entry.actual);
                let completionText = 'N/A';
                if (entry.completion !== null && entry.completion !== undefined) {
                    const completionLabel = formatNumberValue(entry.completion);
                    completionText = completionLabel !== null ? `${completionLabel}%` : 'N/A';
                }
                const metaSegments = [];
                if (entry.timeline_label) {
                    metaSegments.push(entry.timeline_label);
                }
                if (entry.updated_at) {
                    const dateLabel = formatDateLabel(entry.updated_at);
                    if (dateLabel) {
                        metaSegments.push(`Updated ${dateLabel}`);
                    }
                }
                const metaLine = metaSegments.length ? `<span class="timeline-meta">${metaSegments.join(' • ')}</span>` : '';
                const displayLabel = entry.label || (entry.week_number ? `Week ${entry.week_number}` : 'Update');
                return `
                    <li>
                        <div class="timeline-header">
                            <span class="timeline-label">${displayLabel}</span>
                            ${metaLine}
                        </div>
                        <div class="timeline-body">
                            <span><strong>Planned:</strong> ${plannedText}</span>
                            <span><strong>Conducted:</strong> ${actualText}</span>
                            <span><strong>Completion:</strong> ${completionText}</span>
                        </div>
                    </li>
                `;
            }).filter(Boolean).join('');

            const recentTopics = data.recent_topics.map(item => `
                <li>
                    <span><strong>${item.topic || 'Topic Not Set'}</strong> (${item.timeline || 'N/A'})</span>
                    <span>${item.completion}%</span>
                </li>
            `).join('');

            modalBody.innerHTML = `
                <h3 id="courseModalTitle" style="margin-bottom:12px; color:#A6192E;">${data.subject_name} <small style="color:#6c757d;">(Semester ${data.semester})</small></h3>
                <div class="modal-section">
                    <h5>Teaching Team</h5>
                    <div class="tag-list">
                        <span class="tag"><i class="fas fa-chalkboard-teacher"></i> ${teachers}</span>
                    </div>
                </div>
                <div class="modal-section">
                    <h5>Mapped Classes</h5>
                    <div class="tag-list">
                        ${classes.split(',').map(name => `<span class="tag">${name.trim()}</span>`).join('') || '<span class="tag">Unassigned</span>'}
                    </div>
                </div>
                <div class="modal-section">
                    <h5>Key Metrics</h5>
                    <div class="metric-grid">
                        <div class="metric-card"><h6>Total Students</h6><span>${data.students.total}</span></div>
                        <div class="metric-card"><h6>Evaluated</h6><span>${data.students.evaluated}</span></div>
                        <div class="metric-card"><h6>Coverage %</h6><span>${coverage}</span></div>
                        <div class="metric-card"><h6>Avg ICA Marks</h6><span>${avgMarksLabel}</span></div>
                        <div class="metric-card"><h6>Students At Risk</h6><span>${data.ica.at_risk}</span></div>
                    </div>
                </div>
                <div class="modal-section">
                    <h5>Syllabus Timeline</h5>
                    <ul class="timeline-list">
                        ${timelineMarkup || '<li>No timeline data available.</li>'}
                    </ul>
                </div>
                <div class="modal-section">
                    <h5>Recent Updates</h5>
                    <ul class="timeline-list">${recentTopics || '<li>No recent updates</li>'}</ul>
                </div>
            `;

            const modalTitle = modalBody.querySelector('#courseModalTitle');
            if (modalTitle) {
                modal.setAttribute('aria-labelledby', 'courseModalTitle');
                if (modalContent) {
                    modalContent.setAttribute('aria-labelledby', 'courseModalTitle');
                }
            }
        }

        function openModal(courseId) {
            lastFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            modalBody.innerHTML = '<p>Loading course details...</p>';
            const requestToken = ++modalRequestToken;
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            if (modalContent) {
                modalContent.scrollTop = 0;
                try {
                    modalContent.focus({ preventScroll: true });
                } catch (err) {
                    modalContent.focus();
                }
            }
            fetch(`<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?action=course_details&subject_id=${courseId}`)
                .then(res => res.json())
                .then(data => {
                    if (requestToken !== modalRequestToken) {
                        return;
                    }
                    if (data.error) {
                        modalBody.innerHTML = `<p style="color:#dc3545;">${data.error}</p>`;
                    } else {
                        renderCourseDetails(data);
                    }
                })
                .catch(() => {
                    if (requestToken !== modalRequestToken) {
                        return;
                    }
                    modalBody.innerHTML = '<p style="color:#dc3545;">Unable to load course details.</p>';
                });
        }

        document.querySelectorAll('.clickable-row').forEach(row => {
            row.addEventListener('click', () => {
                const courseId = row.getAttribute('data-course-id');
                if (courseId) {
                    openModal(courseId);
                }
            });
        });

        modalClose.addEventListener('click', closeModal);

        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && document.body.classList.contains('modal-open')) {
                closeModal();
            }
        });
    </script>
</body>
</html>

