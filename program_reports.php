<?php
session_start();
include 'db_connect.php';

require_once __DIR__ . '/includes/academic_context.php';
require_once __DIR__ . '/includes/term_switcher_ui.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'program_chair') {
    header('Location: login.php');
    exit;
}

$pc_school = '';
$pc_school_stmt = mysqli_prepare($conn, "SELECT u.school, u.department FROM users u WHERE u.id = ? LIMIT 1");
if ($pc_school_stmt) {
    $pc_user_id = (int)$_SESSION['user_id'];
    mysqli_stmt_bind_param($pc_school_stmt, "i", $pc_user_id);
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


$status_label_map = [
    'at_risk' => 'At-Risk Students',
    'average' => 'Average Students',
    'good' => 'Good Standing Students',
    'all' => 'Students (All Statuses)'
];

$normalize_status_filter = static function (?string $value): string {
    $allowed = ['at_risk', 'average', 'good', 'all'];
    $value = strtolower(trim((string)$value));
    return in_array($value, $allowed, true) ? $value : 'at_risk';
};

$fetch_teacher_student_rows = static function (
    mysqli $conn,
    int $teacher_id,
    int $class_filter,
    int $subject_filter,
    string $status_filter,
    string $school_filter,
    ?int $active_term_id,
    ?string $term_start_bound,
    ?string $term_end_bound,
    bool $collect_rows = true
): array {
    $normalized_status = $status_filter === 'all' ? '' : $status_filter;

    $marksDateClause = '';
    if ($term_start_bound !== null && $term_end_bound !== null) {
        $marksDateClause = " AND ((ism.updated_at BETWEEN ? AND ?) OR ism.updated_at IS NULL)";
    }

    $query = "SELECT
                stu.id AS student_id,
                stu.name AS student_name,
                stu.roll_number,
                c.id AS class_id,
                c.class_name,
                sub.id AS subject_id,
                sub.subject_name,
                totals.total_marks,
                totals.max_total
              FROM (
                    SELECT
                        ism.teacher_id,
                        ism.student_id,
                        ic.subject_id,
                        SUM(ism.marks) AS total_marks,
                        SUM(CASE WHEN ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance > 0 THEN ic.marks_per_instance ELSE 0 END) AS max_total
                    FROM ica_student_marks ism
                    JOIN ica_components ic ON ic.id = ism.component_id
                    WHERE ism.teacher_id = ?" . $marksDateClause . "
                    GROUP BY ism.teacher_id, ism.student_id, ic.subject_id
              ) totals
              JOIN students stu ON stu.id = totals.student_id
              JOIN classes c ON c.id = stu.class_id
              JOIN subjects sub ON sub.id = totals.subject_id
              JOIN teacher_subject_assignments tsa ON tsa.teacher_id = totals.teacher_id AND tsa.subject_id = totals.subject_id AND tsa.class_id = c.id
              WHERE (tsa.section_id IS NULL OR COALESCE(tsa.section_id, 0) = COALESCE(stu.section_id, 0))";

    $types = 'i';
    $params = [$teacher_id];

    if ($term_start_bound !== null && $term_end_bound !== null) {
        $types .= 'ss';
        $params[] = $term_start_bound;
        $params[] = $term_end_bound;
    }
    if ($school_filter !== '') {
        $query .= " AND sub.school = ?";
        $types .= 's';
        $params[] = $school_filter;
    }
    if ($class_filter > 0) {
        $query .= " AND stu.class_id = ?";
        $types .= 'i';
        $params[] = $class_filter;
    }
    if ($subject_filter > 0) {
        $query .= " AND sub.id = ?";
        $types .= 'i';
        $params[] = $subject_filter;
    }
    if ($active_term_id !== null && $active_term_id > 0) {
        $query .= " AND c.academic_term_id = ?";
        $types .= 'i';
        $params[] = $active_term_id;
    }

    $query .= " GROUP BY totals.teacher_id, totals.student_id, totals.subject_id
                ORDER BY c.class_name, stu.roll_number, sub.subject_name";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log('Failed to prepare student aggregation query: ' . mysqli_error($conn));
        return [
            'rows' => [],
            'status_counts' => ['at_risk' => 0, 'average' => 0, 'good' => 0],
            'matching_student_count' => 0,
            'total_student_count' => 0
        ];
    }

    $bind_params = [$stmt, $types];
    foreach ($params as $key => $value) {
        $bind_params[] = &$params[$key];
    }
    call_user_func_array('mysqli_stmt_bind_param', $bind_params);

    if (!mysqli_stmt_execute($stmt)) {
        error_log('Failed to execute student aggregation query: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [
            'rows' => [],
            'status_counts' => ['at_risk' => 0, 'average' => 0, 'good' => 0],
            'matching_student_count' => 0,
            'total_student_count' => 0
        ];
    }

    $result = mysqli_stmt_get_result($stmt);
    $raw_rows = [];
    $student_status_map = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $total_marks = isset($row['total_marks']) ? (float)$row['total_marks'] : 0.0;
            $max_total = isset($row['max_total']) ? (float)$row['max_total'] : 0.0;

            $percentage = 0.0;
            if ($max_total > 0) {
                $percentage = ($total_marks / $max_total) * 100.0;
            }
            if ($percentage < 0) {
                $percentage = 0.0;
            }
            if ($percentage > 100) {
                $percentage = 100.0;
            }

            $status = 'at_risk';
            if ($percentage >= 70) {
                $status = 'good';
            } elseif ($percentage >= 50) {
                $status = 'average';
            }

            $student_id = (int)$row['student_id'];
            if (!isset($student_status_map[$student_id])) {
                $student_status_map[$student_id] = [];
            }
            $student_status_map[$student_id][] = $status;

            $raw_rows[] = [
                'student_id' => $student_id,
                'student_name' => $row['student_name'],
                'roll_number' => $row['roll_number'],
                'class_name' => $row['class_name'],
                'subject_name' => $row['subject_name'],
                'avg_marks' => round($percentage, 2),
                'total_marks' => round($total_marks, 2),
                'max_marks' => round($max_total, 2),
                'status' => $status
            ];
        }
        mysqli_free_result($result);
    }

    mysqli_stmt_close($stmt);

    $status_counts = ['at_risk' => 0, 'average' => 0, 'good' => 0];
    foreach ($student_status_map as $statuses) {
        if (in_array('at_risk', $statuses, true)) {
            $status_counts['at_risk']++;
        } elseif (in_array('average', $statuses, true)) {
            $status_counts['average']++;
        } else {
            $status_counts['good']++;
        }
    }

    $filtered_rows = [];
    $matching_students = [];
    foreach ($raw_rows as $row) {
        $include_row = ($normalized_status === '' || $row['status'] === $normalized_status);
        if ($include_row) {
            $matching_students[$row['student_id']] = true;
            if ($collect_rows) {
                $filtered_rows[] = [
                    'student_name' => $row['student_name'],
                    'roll_number' => $row['roll_number'],
                    'class_name' => $row['class_name'],
                    'subject_name' => $row['subject_name'],
                    'total_marks' => $row['total_marks'],
                    'max_marks' => $row['max_marks'],
                    'avg_marks' => $row['avg_marks'],
                    'status' => $row['status'],
                    'status_label' => $row['status'] === 'good' ? 'Good Standing' : ($row['status'] === 'average' ? 'Average' : 'At-Risk')
                ];
            }
        }
    }

    return [
        'rows' => $collect_rows ? $filtered_rows : [],
        'status_counts' => $status_counts,
        'matching_student_count' => count($matching_students),
        'total_student_count' => count($student_status_map)
    ];
};

$school_param_provided = array_key_exists('school_name', $_GET);
$selected_school = '';
if ($school_param_provided) {
    $selected_school = trim((string)$_GET['school_name']);
} elseif ($pc_school !== '') {
    $selected_school = $pc_school;
}

$school_options = [];
$schools_query = "SELECT school_name FROM schools ORDER BY school_name";
$schools_result = mysqli_query($conn, $schools_query);
if ($schools_result) {
    while ($row = mysqli_fetch_assoc($schools_result)) {
        $name = isset($row['school_name']) ? trim((string)$row['school_name']) : '';
        if ($name === '') {
            continue;
        }
        $school_options[$name] = $name;
    }
    mysqli_free_result($schools_result);
}
if (empty($school_options)) {
    $fallback_sql = "SELECT DISTINCT COALESCE(NULLIF(u.school, ''), NULLIF(u.department, '')) AS school_name
                     FROM users u
                     WHERE u.role = 'teacher' AND (u.school IS NOT NULL OR u.department IS NOT NULL)
                     ORDER BY school_name";
    $fallback_result = mysqli_query($conn, $fallback_sql);
    if ($fallback_result) {
        while ($row = mysqli_fetch_assoc($fallback_result)) {
            $name = isset($row['school_name']) ? trim((string)$row['school_name']) : '';
            if ($name === '') {
                continue;
            }
            $school_options[$name] = $name;
        }
        mysqli_free_result($fallback_result);
    }
}
if ($pc_school !== '' && !isset($school_options[$pc_school])) {
    $school_options[$pc_school] = $pc_school;
}
if ($selected_school !== '' && !isset($school_options[$selected_school])) {
    $school_options[$selected_school] = $selected_school;
}
ksort($school_options);

$context_school = $selected_school !== '' ? $selected_school : $pc_school;
$academicContext = resolveAcademicContext($conn, [
    'school_name' => $context_school
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

$calendar_start_for_file = $termStartDate ?? date('Y-m-d');
$calendar_end_for_file = $termEndDate ?? $calendar_start_for_file;

$class_filter = isset($_GET['class_id']) && $_GET['class_id'] !== '' ? (int)$_GET['class_id'] : 0;
$subject_filter = isset($_GET['subject_id']) && $_GET['subject_id'] !== '' ? (int)$_GET['subject_id'] : 0;
$student_status_filter = $normalize_status_filter($_GET['student_status'] ?? 'at_risk');

$status_column_label = $status_label_map[$student_status_filter] ?? $status_label_map['at_risk'];

$class_options = [];
$class_conditions = [];
$class_types = '';
$class_params = [];
if ($selected_school !== '') {
    $class_conditions[] = 'school = ?';
    $class_types .= 's';
    $class_params[] = $selected_school;
}
if ($activeTermId > 0) {
    $class_conditions[] = 'academic_term_id = ?';
    $class_types .= 'i';
    $class_params[] = $activeTermId;
}
$class_sql = "SELECT id, class_name FROM classes";
if (!empty($class_conditions)) {
    $class_sql .= ' WHERE ' . implode(' AND ', $class_conditions);
}
$class_sql .= ' ORDER BY class_name';
$class_stmt = mysqli_prepare($conn, $class_sql);
if ($class_stmt) {
    if ($class_types !== '') {
        $bind_params = [$class_stmt, $class_types];
        foreach ($class_params as $key => $value) {
            $bind_params[] = &$class_params[$key];
        }
        call_user_func_array('mysqli_stmt_bind_param', $bind_params);
    }
    if (mysqli_stmt_execute($class_stmt)) {
        $class_res = mysqli_stmt_get_result($class_stmt);
        if ($class_res) {
            while ($row = mysqli_fetch_assoc($class_res)) {
                $class_options[] = $row;
            }
            mysqli_free_result($class_res);
        }
    }
    mysqli_stmt_close($class_stmt);
}
$valid_class_ids = array_column($class_options, 'id');
if ($class_filter > 0 && !in_array($class_filter, $valid_class_ids, true)) {
    $class_filter = 0;
}

$subject_options = [];
$subject_sql = "SELECT id, subject_name FROM subjects";
if ($selected_school !== '') {
    $subject_sql .= " WHERE school = ?";
}
$subject_sql .= " ORDER BY subject_name";
$subject_stmt = mysqli_prepare($conn, $subject_sql);
if ($subject_stmt) {
    if ($selected_school !== '') {
        mysqli_stmt_bind_param($subject_stmt, 's', $selected_school);
    }
    if (mysqli_stmt_execute($subject_stmt)) {
        $subject_res = mysqli_stmt_get_result($subject_stmt);
        if ($subject_res) {
            while ($row = mysqli_fetch_assoc($subject_res)) {
                $subject_options[] = $row;
            }
            mysqli_free_result($subject_res);
        }
    }
    mysqli_stmt_close($subject_stmt);
}

$valid_subject_ids = array_column($subject_options, 'id');
if ($subject_filter > 0 && !in_array($subject_filter, $valid_subject_ids, true)) {
    $subject_filter = 0;
}

// Main query with additional metrics
$avg_completion_subquery = "COALESCE((SELECT AVG(sp.completion_percentage) FROM syllabus_progress sp WHERE sp.teacher_id = u.id";
if ($termStartEsc && $termEndEsc) {
    $avg_completion_subquery .= " AND ((sp.updated_at BETWEEN '$termStartEsc' AND '$termEndEsc') OR sp.updated_at IS NULL)";
}
if ($activeTermId > 0) {
    $avg_completion_subquery .= " AND EXISTS (SELECT 1 FROM teacher_subject_assignments tsa2 JOIN classes c2 ON c2.id = tsa2.class_id WHERE tsa2.teacher_id = u.id AND tsa2.subject_id = (SELECT id FROM subjects WHERE subject_name = sp.subject LIMIT 1) AND c2.academic_term_id = " . (int)$activeTermId . ")";
}
$avg_completion_subquery .= "), 0) AS avg_completion";

$assigned_courses_subquery = "COALESCE((SELECT COUNT(DISTINCT tsa.subject_id) FROM teacher_subject_assignments tsa JOIN classes c2 ON c2.id = tsa.class_id WHERE tsa.teacher_id = u.id";
if ($activeTermId > 0) {
    $assigned_courses_subquery .= " AND c2.academic_term_id = " . (int)$activeTermId;
}
$assigned_courses_subquery .= "), 0) AS assigned_courses";

$assigned_classes_subquery = "COALESCE((SELECT COUNT(DISTINCT tsa.class_id) FROM teacher_subject_assignments tsa JOIN classes c3 ON c3.id = tsa.class_id WHERE tsa.teacher_id = u.id";
if ($activeTermId > 0) {
    $assigned_classes_subquery .= " AND c3.academic_term_id = " . (int)$activeTermId;
}
$assigned_classes_subquery .= "), 0) AS assigned_classes";

$progress_query = "SELECT 
    u.id,
    u.name,
    COALESCE(NULLIF(u.school, ''), u.department, '') AS school_name,
    " . $avg_completion_subquery . ",
    " . $assigned_courses_subquery . ",
    " . $assigned_classes_subquery . "
FROM users u
WHERE u.role = 'teacher'";

if ($selected_school !== '') {
    $escaped_school = mysqli_real_escape_string($conn, $selected_school);
    $progress_query .= " AND (u.school = '" . $escaped_school . "' OR (u.school IS NULL OR u.school = '') AND u.department = '" . $escaped_school . "')";
}
if ($activeTermId > 0) {
    $progress_query .= " AND EXISTS (SELECT 1 FROM teacher_subject_assignments tsa_scope JOIN classes c_scope ON c_scope.id = tsa_scope.class_id WHERE tsa_scope.teacher_id = u.id AND c_scope.academic_term_id = " . (int)$activeTermId . ")";
}

$progress_query .= " ORDER BY u.name ASC";

$progress_result = mysqli_query($conn, $progress_query);
$teachers = [];
if ($progress_result) {
    while ($row = mysqli_fetch_assoc($progress_result)) {
        $row['school_name'] = isset($row['school_name']) ? trim((string)$row['school_name']) : '';
        $teacherNameRaw = isset($row['name']) ? trim((string)$row['name']) : '';
        $row['name_display'] = $teacherNameRaw !== '' ? format_person_display($teacherNameRaw) : '';
        $row['assigned_courses'] = isset($row['assigned_courses']) ? (int)$row['assigned_courses'] : 0;
        $row['assigned_classes'] = isset($row['assigned_classes']) ? (int)$row['assigned_classes'] : 0;
        $teachers[] = $row;
    }
} else {
    error_log("Main progress query failed: " . mysqli_error($conn));
}

foreach ($teachers as &$teacher_row) {
    $teacher_stats = $fetch_teacher_student_rows(
        $conn,
        (int)$teacher_row['id'],
        $class_filter,
        $subject_filter,
        $student_status_filter,
        $selected_school,
        $activeTermId,
        $termStartBound,
        $termEndBound,
        false
    );
    $teacher_row['matching_student_count'] = $teacher_stats['matching_student_count'];
    $teacher_row['status_counts'] = $teacher_stats['status_counts'];
    $teacher_row['total_student_count'] = $teacher_stats['total_student_count'];
}
unset($teacher_row);

$export_params = $_GET;
unset($export_params['export'], $export_params['action'], $export_params['id']);

$pdf_params = $export_params;
$pdf_params['export'] = 'pdf';
$pdf_query = http_build_query($pdf_params);
$pdf_export_url = 'program_reports.php' . ($pdf_query ? '?' . $pdf_query : '');

$csv_params = $export_params;
$csv_params['export'] = 'csv';
$csv_query = http_build_query($csv_params);
$csv_export_url = 'program_reports.php' . ($csv_query ? '?' . $csv_query : '');

// AJAX endpoint for teacher details
if (isset($_GET['action']) && $_GET['action'] == 'get_teacher_details') {
    header('Content-Type: application/json');

    $teacher_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($teacher_id <= 0) {
        echo json_encode([
            'courses' => [],
            'alerts' => [],
            'error' => 'Invalid teacher selected.'
        ]);
        exit;
    }

    $response = [
        'courses' => [],
        'alerts' => [],
        'error' => ''
    ];

    // Course-specific syllabus progress
    $syllabusDateJoinClause = '';
    if ($termStartBound && $termEndBound) {
        $syllabusDateJoinClause = " AND ((sp.updated_at BETWEEN ? AND ?) OR sp.updated_at IS NULL)";
    }
    $riskDateClause = '';
    if ($termStartBound && $termEndBound) {
        $riskDateClause = " WHERE ((ism.updated_at BETWEEN ? AND ?) OR ism.updated_at IS NULL)";
    }

    $courses_sql = "
        SELECT 
            s.subject_name,
            c.class_name,
            c.semester,
            c.school,
            sec.section_name,
            COALESCE(MAX(sp.completion_percentage), 0) AS syllabus_completion,
            COALESCE(SUM(CASE WHEN risk.avg_mark IS NOT NULL AND risk.avg_mark < 50 THEN 1 ELSE 0 END), 0) AS at_risk_count
        FROM teacher_subject_assignments tsa
        JOIN subjects s ON s.id = tsa.subject_id
        JOIN classes c ON c.id = tsa.class_id
        LEFT JOIN sections sec ON sec.id = tsa.section_id
        LEFT JOIN syllabus_progress sp ON sp.teacher_id = tsa.teacher_id AND sp.subject = s.subject_name" . $syllabusDateJoinClause . "
        LEFT JOIN (
            SELECT
                ic.subject_id,
                ism.teacher_id,
                st.class_id AS class_id,
                COALESCE(st.section_id, 0) AS section_key,
                ism.student_id,
                AVG(ism.marks) AS avg_mark
            FROM ica_student_marks ism
            JOIN ica_components ic ON ic.id = ism.component_id
            JOIN students st ON st.id = ism.student_id" . ($riskDateClause !== '' ? "\n" . $riskDateClause : '') . "
            GROUP BY ic.subject_id, ism.teacher_id, st.class_id, section_key, ism.student_id
        ) AS risk ON risk.teacher_id = tsa.teacher_id
            AND risk.subject_id = s.id
            AND risk.class_id = c.id
            AND risk.section_key = COALESCE(tsa.section_id, 0)
        WHERE tsa.teacher_id = ?";

    if ($activeTermId > 0) {
        $courses_sql .= " AND c.academic_term_id = ?";
    }
    if ($selected_school !== '') {
        $courses_sql .= " AND s.school = ?";
    }

    $courses_sql .= "
        GROUP BY s.id, s.subject_name, c.id, c.class_name, c.semester, c.school, sec.section_name
        ORDER BY s.subject_name, c.class_name, sec.section_name";

    $stmt_c = mysqli_prepare($conn, $courses_sql);
    if (!$stmt_c) {
        $response['error'] = 'Unable to prepare course data query: ' . mysqli_error($conn);
    } else {
        $bindTypes = '';
        $bindValues = [];
        if ($termStartBound && $termEndBound) {
            $bindTypes .= 'ss';
            $bindValues[] = $termStartBound;
            $bindValues[] = $termEndBound;
            $bindTypes .= 'ss';
            $bindValues[] = $termStartBound;
            $bindValues[] = $termEndBound;
        }
        $bindTypes .= 'i';
        $bindValues[] = $teacher_id;
        if ($activeTermId > 0) {
            $bindTypes .= 'i';
            $bindValues[] = $activeTermId;
        }
        if ($selected_school !== '') {
            $bindTypes .= 's';
            $bindValues[] = $selected_school;
        }
        if ($bindTypes !== '') {
            $bindParams = [$stmt_c, $bindTypes];
            foreach ($bindValues as $key => $value) {
                $bindParams[] = &$bindValues[$key];
            }
            call_user_func_array('mysqli_stmt_bind_param', $bindParams);
        }
        if (!mysqli_stmt_execute($stmt_c)) {
            $response['error'] = 'Unable to execute course data query: ' . mysqli_stmt_error($stmt_c);
        } else {
            $courses_result = mysqli_stmt_get_result($stmt_c);
            if ($courses_result) {
                while ($row = mysqli_fetch_assoc($courses_result)) {
                    $row['class_label'] = format_class_label(
                        $row['class_name'] ?? '',
                        $row['section_name'] ?? '',
                        $row['semester'] ?? '',
                        $row['school'] ?? ''
                    );
                    $response['courses'][] = $row;
                }
                mysqli_free_result($courses_result);
            } else {
                $response['error'] = 'Unable to fetch course data: ' . mysqli_error($conn);
            }
        }
        mysqli_stmt_close($stmt_c);
    }

    // Alerts data
    $alerts_sql = "SELECT message, status, response, created_at 
                   FROM alerts 
                   WHERE teacher_id = ?";
    if ($termStartBound && $termEndBound) {
        $alerts_sql .= " AND created_at BETWEEN ? AND ?";
    }
    $alerts_sql .= " 
                   ORDER BY created_at DESC LIMIT 5";
    $stmt_a = mysqli_prepare($conn, $alerts_sql);
    if ($stmt_a) {
        if ($termStartBound && $termEndBound) {
            mysqli_stmt_bind_param($stmt_a, "iss", $teacher_id, $termStartBound, $termEndBound);
        } else {
            mysqli_stmt_bind_param($stmt_a, "i", $teacher_id);
        }
        if (mysqli_stmt_execute($stmt_a)) {
            $alerts_result = mysqli_stmt_get_result($stmt_a);
            if ($alerts_result) {
                while ($row = mysqli_fetch_assoc($alerts_result)) {
                    $response['alerts'][] = $row;
                }
                mysqli_free_result($alerts_result);
            }
        } else {
            $response['error'] = $response['error'] ?: 'Unable to execute alerts query: ' . mysqli_stmt_error($stmt_a);
        }
        mysqli_stmt_close($stmt_a);
    } else {
        $response['error'] = $response['error'] ?: 'Unable to prepare alerts query: ' . mysqli_error($conn);
    }

    echo json_encode($response);
    exit;
}

// AJAX endpoint for at-risk students
if (isset($_GET['action']) && $_GET['action'] == 'get_at_risk_students') {
    header('Content-Type: application/json');
    $teacher_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($teacher_id <= 0) {
        echo json_encode(['error' => 'Invalid teacher selected.']);
        exit;
    }

    $class_param = isset($_GET['class_id']) && $_GET['class_id'] !== '' ? (int)$_GET['class_id'] : 0;
    $subject_param = isset($_GET['subject_id']) && $_GET['subject_id'] !== '' ? (int)$_GET['subject_id'] : 0;
    $status_param = $normalize_status_filter($_GET['student_status'] ?? 'at_risk');

    $student_data = $fetch_teacher_student_rows(
        $conn,
        $teacher_id,
        $class_param,
        $subject_param,
        $status_param,
        $selected_school,
        $activeTermId,
        $termStartBound,
        $termEndBound,
        true
    );
    $status_label = $status_label_map[$status_param] ?? $status_label_map['at_risk'];

    echo json_encode([
        'students' => $student_data['rows'],
        'status_counts' => $student_data['status_counts'],
        'matching_student_count' => $student_data['matching_student_count'],
        'total_student_count' => $student_data['total_student_count'],
        'status_label' => $status_label
    ]);
    exit;
}

// Filename helper
$format_segment = static function (string $value): string {
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    $value = str_replace(['/', '\\'], '-', $value);
    $value = preg_replace('/[^A-Za-z0-9-]+/', '_', $value);
    $value = preg_replace('/_+/', '_', $value);
    return trim($value, '_');
};

$date_range_for_file = $calendar_start_for_file && $calendar_end_for_file ? $calendar_start_for_file . '_to_' . $calendar_end_for_file : date('Y-m-d');
$school_label_for_file = $selected_school !== '' ? $selected_school : $pc_school;

// Generate PDF (raw PDF syntax)
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
    $filename_parts = [];
    if ($school_label_for_file !== '') {
        $filename_parts[] = $format_segment($school_label_for_file);
    }
    $filename_parts[] = 'Teachers_Report';

    $filename = trim(implode('_', array_filter($filename_parts)), '_');
    if ($filename !== '') {
        $filename .= '_' . $date_range_for_file . '.pdf';
    } else {
        $filename = 'Teachers_Report_' . $date_range_for_file . '.pdf';
    }
    $filename = preg_replace('/_+/', '_', $filename);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = "%PDF-1.4\n";
    $output .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $output .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $output .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 6 0 R >> >>\nendobj\n";
    $output .= "4 0 obj\n<< /Length 5 0 R >>\nstream\n";
    $output .= "BT /F1 12 Tf 50 800 Td (Teachers Progress Report) Tj ET\n";
    $pdf_status_heading = str_replace(['(', ')'], '', $status_column_label);
    $output .= "BT /F1 12 Tf 50 780 Td (Teacher) Tj 150 780 Td (School) Tj 250 780 Td (Assigned Courses) Tj 340 780 Td (Assigned Classes) Tj 430 780 Td (Avg Completion %) Tj 520 780 Td (" . str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $pdf_status_heading) . ") Tj ET\n";

    $y = 760;
    foreach ($teachers as $teacher) {
            $matching_count = (int)$teacher['matching_student_count'];
            $statusValue = (string)$matching_count;
            if ($student_status_filter === 'all') {
                $statusValue .= ' AR:' . (int)$teacher['status_counts']['at_risk'] . ' AV:' . (int)$teacher['status_counts']['average'] . ' GD:' . (int)$teacher['status_counts']['good'];
            }
            $statusValue = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $statusValue);
            $nameForPdf = $teacher['name_display'] ?? '';
            if ($nameForPdf === '' && isset($teacher['name'])) {
                $nameForPdf = trim((string)$teacher['name']);
            }
            $teacherNamePdf = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], htmlspecialchars($nameForPdf));
            $schoolLabel = $teacher['school_name'] !== '' ? $teacher['school_name'] : 'N/A';
            $schoolPdf = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], htmlspecialchars($schoolLabel));
            $classesPdf = (string)((int)($teacher['assigned_classes'] ?? 0));
            $coursesPdf = (string)((int)($teacher['assigned_courses'] ?? 0));
            $output .= "BT /F1 12 Tf 50 $y Td (" . $teacherNamePdf . ") Tj 150 $y Td (" . $schoolPdf . ") Tj 250 $y Td (" . $coursesPdf . ") Tj 340 $y Td (" . $classesPdf . ") Tj 430 $y Td (" . round($teacher['avg_completion'], 2) . "%) Tj 520 $y Td (" . $statusValue . ") Tj ET\n";
        $y -= 20;
    }
    $output .= "endstream\nendobj\n";
    $output .= "5 0 obj\n" . strlen($output) . "\nendobj\n";
    $output .= "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
    $output .= "xref\n0 7\n0000000000 65535 f \n0000000010 00000 n \n0000000075 00000 n \n0000000120 00000 n \n0000000175 00000 n \n0000000290 00000 n \n0000000350 00000 n \ntrailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n400\n%%EOF";

    echo $output;
    exit;
}

// Generate CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $filename_parts = [];
    if ($school_label_for_file !== '') {
        $filename_parts[] = $format_segment($school_label_for_file);
    }
    $filename_parts[] = 'Teachers_Report';

    $filename = trim(implode('_', array_filter($filename_parts)), '_');
    if ($filename !== '') {
        $filename .= '_' . $date_range_for_file . '.csv';
    } else {
        $filename = 'Teachers_Report_' . $date_range_for_file . '.csv';
    }
    $filename = preg_replace('/_+/', '_', $filename);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Teacher', 'School', 'Assigned Courses', 'Assigned Classes', 'Average Completion %', $status_column_label]);
    foreach ($teachers as $teacher) {
        $statusValue = (int)$teacher['matching_student_count'];
        if ($student_status_filter === 'all') {
            $statusValue .= sprintf(' (At-Risk: %d, Average: %d, Good: %d)',
                (int)$teacher['status_counts']['at_risk'],
                (int)$teacher['status_counts']['average'],
                (int)$teacher['status_counts']['good']
            );
        }
        $schoolLabel = $teacher['school_name'] !== '' ? $teacher['school_name'] : 'N/A';
        $nameForCsv = $teacher['name_display'] ?? '';
        if ($nameForCsv === '' && isset($teacher['name'])) {
            $nameForCsv = trim((string)$teacher['name']);
        }
        fputcsv($output, [
            $nameForCsv,
            $schoolLabel,
            (int)($teacher['assigned_courses'] ?? 0),
            (int)($teacher['assigned_classes'] ?? 0),
            round($teacher['avg_completion'], 2),
            $statusValue
        ]);
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
    <title>Program Chair Reports - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .clickable-row { cursor: pointer; }
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
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); 
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
        .export-btn, .view-btn {
            margin-left: 10px;
            padding: 8px 16px;
            background-color: #A6192E;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
        }
        .export-btn:hover, .view-btn:hover {
            background-color: #8b1624;
        }
        body.dark-mode .modal-content { 
            background-color: #5a5a5a; 
            color: #e0e0e0; 
        }
        body.dark-mode .modal-header { 
            border-bottom: 1px solid #777; 
        }
        body.dark-mode .export-btn, body.dark-mode .view-btn {
            background-color: #8b1624;
        }
        body.dark-mode .export-btn:hover, body.dark-mode .view-btn:hover {
            background-color: #A6192E;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            align-items: end;
            margin-bottom: 20px;
        }
        .filter-apply-btn {
            margin: 0;
        }
        .status-breakdown {
            margin-top: 6px;
            line-height: 1.2;
        }
        .status-summary {
            font-size: 0.85rem;
            color: #555;
        }
        body.dark-mode .status-summary {
            color: #ccc;
        }
    </style>
</head>
<body class="program-chair">
    <div class="dashboard">
        <div class="sidebar">
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
            <h2>ICA Tracker</h2>
            <a href="program_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="teacher_progress.php"><i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span></a>
            <a href="student_progress.php"><i class="fas fa-user-graduate"></i> <span>Students</span></a>
            <a href="course_progress.php"><i class="fas fa-book"></i> <span>Courses</span></a>
            <a href="program_reports.php" class="active"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
            <a href="send_alerts.php"><i class="fas fa-bell"></i> <span>Alerts</span></a>
            <a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
            <a href="edit_profile.php"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>

        <div class="main-content">
            <div class="header">
                <h2>Teachers' Progress Reports</h2>
            </div>
            <?php if (isset($academicContext)) { renderTermSwitcher($academicContext, ['school_name' => $context_school]); } ?>
            <div class="container">
                <div class="card">
                    <div class="card-header">
                        <h5>Filters</h5>
                    </div>
                    <div class="card-body">
                        <form method="get" class="filter-grid" id="teacherFiltersForm">
                            <div class="form-group">
                                <label>School</label>
                                <select name="school_name" id="schoolFilter">
                                    <option value="" <?php echo $selected_school === '' ? 'selected' : ''; ?>>All Schools</option>
                                    <?php foreach ($school_options as $school_name): ?>
                                        <option value="<?php echo htmlspecialchars($school_name); ?>" <?php echo ($selected_school !== '' && $selected_school === $school_name) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($school_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Class</label>
                                <select name="class_id" id="classFilter">
                                    <option value="">All Classes</option>
                                    <?php foreach ($class_options as $class_option): ?>
                                        <option value="<?php echo (int)$class_option['id']; ?>" <?php echo ($class_filter === (int)$class_option['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class_option['class_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Subject</label>
                                <select name="subject_id" id="subjectFilter">
                                    <option value="">All Subjects</option>
                                    <?php foreach ($subject_options as $subject_option): ?>
                                        <option value="<?php echo (int)$subject_option['id']; ?>" <?php echo ($subject_filter === (int)$subject_option['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subject_option['subject_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Student Status</label>
                                <select name="student_status" id="studentStatusFilter">
                                    <option value="at_risk" <?php echo $student_status_filter === 'at_risk' ? 'selected' : ''; ?>>At-Risk</option>
                                    <option value="average" <?php echo $student_status_filter === 'average' ? 'selected' : ''; ?>>Average</option>
                                    <option value="good" <?php echo $student_status_filter === 'good' ? 'selected' : ''; ?>>Good Standing</option>
                                    <option value="all" <?php echo $student_status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                </select>
                            </div>
                            <div class="form-group" style="align-self: flex-start;">
                                <button type="submit" class="export-btn filter-apply-btn">Apply Filters</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>Teachers' Progress Overview</h5>
                        <div>
                            <a href="<?php echo htmlspecialchars($pdf_export_url); ?>" class="export-btn">Download PDF</a>
                            <a href="<?php echo htmlspecialchars($csv_export_url); ?>" class="export-btn">Download CSV</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>Teacher</th>
                                    <th>School</th>
                                    <th>Assigned Courses</th>
                                    <th>Assigned Classes</th>
                                    <th>Average Completion (%)</th>
                                    <th><?php echo htmlspecialchars($status_column_label); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($teachers)): ?>
                                    <tr><td colspan="5">No teachers found. Check database data in 'users' and 'syllabus_progress' tables.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <?php
                                            $teacherNameDisplay = $teacher['name_display'] ?? '';
                                            if ($teacherNameDisplay === '' && isset($teacher['name'])) {
                                                $teacherNameDisplay = format_person_display(trim((string)$teacher['name']));
                                            }
                                        ?>
                                        <tr class="clickable-row" data-teacher-id="<?php echo $teacher['id']; ?>" data-teacher-name="<?php echo htmlspecialchars($teacherNameDisplay); ?>">
                                            <td><?php echo htmlspecialchars($teacherNameDisplay); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['school_name'] !== '' ? $teacher['school_name'] : '—'); ?></td>
                                            <td><?php echo $teacher['assigned_courses']; ?></td>
                                            <td><?php echo $teacher['assigned_classes']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1">
                                                        <div class="progress-bar <?php echo ($teacher['avg_completion'] >= 80 ? 'bg-success' : ($teacher['avg_completion'] >= 40 ? 'bg-warning' : 'bg-danger')); ?>" role="progressbar" style="width: <?php echo $teacher['avg_completion']; ?>%" aria-valuenow="<?php echo $teacher['avg_completion']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <span class="ms-2"><?php echo round($teacher['avg_completion'], 2); ?>%</span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo (int)$teacher['matching_student_count']; ?>
                                                <?php if ($student_status_filter === 'all'): ?>
                                                    <div class="status-breakdown">
                                                        <small>
                                                            At-Risk: <?php echo (int)$teacher['status_counts']['at_risk']; ?> |
                                                            Average: <?php echo (int)$teacher['status_counts']['average']; ?> |
                                                            Good: <?php echo (int)$teacher['status_counts']['good']; ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ((int)$teacher['matching_student_count'] > 0): ?>
                                                    <a href="#" class="view-btn view-at-risk" data-teacher-id="<?php echo $teacher['id']; ?>" data-teacher-name="<?php echo htmlspecialchars($teacherNameDisplay); ?>">View</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
            <div class="modal-body">
                <div class="card">
                    <div class="card-header">
                        <h5>Course-wise Progress</h5>
                    </div>
                    <div class="card-body">
                        <table id="modalCoursesTable">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Class</th>
                                    <th>Syllabus Completion (%)</th>
                                    <th>At-Risk Students</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Alerts</h5>
                    </div>
                    <div class="card-body">
                        <table id="modalAlertsTable">
                            <thead>
                                <tr>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Response</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="atRiskStudentsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="atRiskModalTeacherName"></h4>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="card">
                    <div class="card-header">
                        <h5 id="atRiskStatusHeader">Students</h5>
                        <span id="atRiskStatusSummary" class="status-summary"></span>
                    </div>
                    <div class="card-body">
                        <table id="atRiskStudentsTable">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Roll Number</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Marks Obtained</th>
                                    <th>Marks Evaluated</th>
                                    <th>Average Marks (%)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        }

        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }

        const teacherDetailModal = document.getElementById('teacherDetailModal');
        const atRiskStudentsModal = document.getElementById('atRiskStudentsModal');
        const modalTeacherName = document.getElementById('modalTeacherName');
        const atRiskModalTeacherName = document.getElementById('atRiskModalTeacherName');
        const coursesTableBody = document.querySelector('#modalCoursesTable tbody');
        const alertsTableBody = document.querySelector('#modalAlertsTable tbody');
        const atRiskTableBody = document.querySelector('#atRiskStudentsTable tbody');
    const schoolFilterSelect = document.getElementById('schoolFilter');
    const classFilterSelect = document.getElementById('classFilter');
        const subjectFilterSelect = document.getElementById('subjectFilter');
        const studentStatusFilterSelect = document.getElementById('studentStatusFilter');
        const atRiskStatusHeader = document.getElementById('atRiskStatusHeader');
        const atRiskStatusSummary = document.getElementById('atRiskStatusSummary');
        const closeBtns = document.querySelectorAll('.close');
        const rows = document.querySelectorAll('.clickable-row');
        const viewAtRiskLinks = document.querySelectorAll('.view-at-risk');
        const statusLabelMap = {
            at_risk: 'At-Risk Students',
            average: 'Average Students',
            good: 'Good Standing Students',
            all: 'Students (All Statuses)'
        };

        // Handle teacher details modal
        rows.forEach(row => {
            row.addEventListener('click', function(e) {
                if (e.target.classList.contains('view-btn')) return; // Prevent modal if clicking View button
                const teacherId = this.dataset.teacherId;
                const teacherName = this.dataset.teacherName;
                modalTeacherName.textContent = `${teacherName} - Progress Details`;
                
                coursesTableBody.innerHTML = '<tr><td colspan="4">Loading...</td></tr>';
                alertsTableBody.innerHTML = '<tr><td colspan="3">Loading...</td></tr>';
                teacherDetailModal.style.display = 'block';

                const detailParams = new URLSearchParams({ action: 'get_teacher_details', id: teacherId });
                if (schoolFilterSelect) {
                    detailParams.set('school_name', schoolFilterSelect.value);
                }

                fetch(`?${detailParams.toString()}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            const errorText = data.error;
                            coursesTableBody.innerHTML = `<tr><td colspan="4">${errorText}</td></tr>`;
                            if (!data.alerts || data.alerts.length === 0) {
                                alertsTableBody.innerHTML = '<tr><td colspan="3">No alerts available.</td></tr>';
                            } else {
                                alertsTableBody.innerHTML = '';
                                data.alerts.forEach(alert => {
                                    const row = document.createElement('tr');
                                    row.innerHTML = `
                                        <td>${alert.message}</td>
                                        <td>${alert.status}</td>
                                        <td>${alert.response || 'N/A'}</td>
                                    `;
                                    alertsTableBody.appendChild(row);
                                });
                            }
                            return;
                        }

                        // Update courses table
                        coursesTableBody.innerHTML = '';
                        if (data.courses.length === 0) {
                            coursesTableBody.innerHTML = '<tr><td colspan="4">No course data available.</td></tr>';
                        } else {
                            data.courses.forEach(course => {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td>${course.subject_name}</td>
                                    <td>${course.class_label || '-'}</td>
                                    <td>${parseFloat(course.syllabus_completion || 0).toFixed(2)}%</td>
                                    <td>${course.at_risk_count || 0}</td>
                                `;
                                coursesTableBody.appendChild(row);
                            });
                        }

                        // Update alerts table
                        alertsTableBody.innerHTML = '';
                        if (data.alerts.length === 0) {
                            alertsTableBody.innerHTML = '<tr><td colspan="3">No alerts available.</td></tr>';
                        } else {
                            data.alerts.forEach(alert => {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td>${alert.message}</td>
                                    <td>${alert.status}</td>
                                    <td>${alert.response || 'N/A'}</td>
                                `;
                                alertsTableBody.appendChild(row);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching teacher details:', error);
                        coursesTableBody.innerHTML = '<tr><td colspan="4">Error loading course data.</td></tr>';
                        alertsTableBody.innerHTML = '<tr><td colspan="3">Error loading alerts.</td></tr>';
                    });
            });
        });

        // Handle at-risk students modal
        viewAtRiskLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const teacherId = this.dataset.teacherId;
                const teacherName = this.dataset.teacherName;

                const params = new URLSearchParams({ action: 'get_at_risk_students', id: teacherId });
                if (schoolFilterSelect) {
                    params.set('school_name', schoolFilterSelect.value);
                }
                if (classFilterSelect && classFilterSelect.value) {
                    params.set('class_id', classFilterSelect.value);
                }
                if (subjectFilterSelect && subjectFilterSelect.value) {
                    params.set('subject_id', subjectFilterSelect.value);
                }
                if (studentStatusFilterSelect && studentStatusFilterSelect.value) {
                    params.set('student_status', studentStatusFilterSelect.value);
                }

                const currentStatusKey = studentStatusFilterSelect && studentStatusFilterSelect.value ? studentStatusFilterSelect.value : 'at_risk';
                const statusLabel = statusLabelMap[currentStatusKey] || statusLabelMap.at_risk;

                atRiskModalTeacherName.textContent = `${teacherName} - ${statusLabel}`;
                if (atRiskStatusHeader) atRiskStatusHeader.textContent = statusLabel;
                if (atRiskStatusSummary) atRiskStatusSummary.textContent = '';

                atRiskTableBody.innerHTML = '<tr><td colspan="8">Loading...</td></tr>';
                atRiskStudentsModal.style.display = 'block';

                fetch(`program_reports.php?${params.toString()}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            throw new Error(data.error);
                        }

                        atRiskTableBody.innerHTML = '';

                        if (atRiskStatusHeader && data.status_label) {
                            atRiskStatusHeader.textContent = data.status_label;
                        }
                        if (atRiskModalTeacherName && data.status_label) {
                            atRiskModalTeacherName.textContent = `${teacherName} - ${data.status_label}`;
                        }

                        if (atRiskStatusSummary) {
                            const counts = data.status_counts || {};
                            const matching = data.matching_student_count ?? 0;
                            const total = data.total_student_count ?? 0;
                            atRiskStatusSummary.textContent = `Showing ${matching} of ${total} students | At-Risk: ${counts.at_risk ?? 0} | Average: ${counts.average ?? 0} | Good: ${counts.good ?? 0}`;
                        }

                        const students = data.students || [];
                        if (students.length === 0) {
                            atRiskTableBody.innerHTML = '<tr><td colspan="8">No students found for the selected filters.</td></tr>';
                        } else {
                            students.forEach(student => {
                                const row = document.createElement('tr');
                                const marksObtained = typeof student.total_marks !== 'undefined' && student.total_marks !== null
                                    ? parseFloat(student.total_marks).toFixed(2)
                                    : '0.00';
                                const maxMarks = typeof student.max_marks !== 'undefined' && student.max_marks !== null
                                    ? parseFloat(student.max_marks).toFixed(2)
                                    : '0.00';
                                const averagePercent = typeof student.avg_marks !== 'undefined' && student.avg_marks !== null
                                    ? parseFloat(student.avg_marks).toFixed(2)
                                    : '0.00';
                                const cells = [
                                    student.student_name || student.name || 'N/A',
                                    student.roll_number || 'N/A',
                                    student.class_name || 'N/A',
                                    student.subject_name || 'N/A',
                                    marksObtained,
                                    maxMarks,
                                    `${averagePercent}%`,
                                    student.status_label || student.status || ''
                                ];
                                cells.forEach(value => {
                                    const td = document.createElement('td');
                                    td.textContent = value;
                                    row.appendChild(td);
                                });
                                atRiskTableBody.appendChild(row);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching at-risk students:', error);
                        if (atRiskStatusSummary) {
                            atRiskStatusSummary.textContent = 'Error loading students. Please try again.';
                        }
                        atRiskTableBody.innerHTML = '<tr><td colspan="8">Error loading students.</td></tr>';
                    });
            });
        });

        // Close modals
        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                teacherDetailModal.style.display = 'none';
                atRiskStudentsModal.style.display = 'none';
                if (atRiskStatusHeader) atRiskStatusHeader.textContent = 'Students';
                if (atRiskStatusSummary) atRiskStatusSummary.textContent = '';
                if (atRiskTableBody) atRiskTableBody.innerHTML = '';
            });
        });

        window.addEventListener('click', event => {
            if (event.target == teacherDetailModal) {
                teacherDetailModal.style.display = 'none';
            }
            if (event.target == atRiskStudentsModal) {
                atRiskStudentsModal.style.display = 'none';
                if (atRiskStatusHeader) atRiskStatusHeader.textContent = 'Students';
                if (atRiskStatusSummary) atRiskStatusSummary.textContent = '';
                if (atRiskTableBody) atRiskTableBody.innerHTML = '';
            }
        });
    </script>
</body>
</html>
