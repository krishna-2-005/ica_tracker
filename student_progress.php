<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/academic_context.php';
require_once __DIR__ . '/includes/term_switcher_ui.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'program_chair') {
    header('Location: login.php');
    exit;
}

if (!function_exists('format_component_mark_label')) {
    function format_component_mark_label(?float $value): string
    {
        if ($value === null) {
            return '';
        }

        $rounded = round((float)$value, 2);
        if (!is_finite($rounded) || $rounded <= 0) {
            return '';
        }

        if (abs($rounded - round($rounded)) < 0.01) {
            return (string)(int)round($rounded);
        }

        return number_format($rounded, 2, '.', '');
    }
}

if (!function_exists('build_component_sum_label')) {
    function build_component_sum_label(string $component_name, int $instances, ?float $maxTotal = null): string
    {
        $name = trim($component_name);
        if ($name === '') {
            return '';
        }

        $formattedMax = format_component_mark_label($maxTotal);
        if ($formattedMax !== '') {
            return sprintf('%s (/ %s)', $name, $formattedMax);
        }

        if ($instances > 1) {
            $suffix = implode(' ', range(1, $instances));
            return sprintf('%s (Sum of %s)', $name, $suffix);
        }

        return $name;
    }
}

if (!function_exists('build_subject_short_label')) {
    function build_subject_short_label(string $subject_name): string
    {
        $subject_name = trim($subject_name);
        if ($subject_name === '') {
            return '';
        }

        if (preg_match('/^([A-Za-z]{2,8})\s*[-:]/', $subject_name, $matches)) {
            return strtoupper($matches[1]);
        }

        $tokens = preg_split('/[\s\-_:()]+/', $subject_name, -1, PREG_SPLIT_NO_EMPTY);
        if (!$tokens) {
            return strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $subject_name), 0, 8));
        }

        $skip = ['and', 'of', 'the', 'to', 'for', 'in', 'on', 'with'];
        $abbr = '';
        foreach ($tokens as $token) {
            if ($token === '' || in_array(strtolower($token), $skip, true)) {
                continue;
            }
            $abbr .= strtoupper($token[0]);
        }

        if ($abbr === '') {
            foreach ($tokens as $token) {
                if ($token !== '') {
                    $abbr .= strtoupper($token[0]);
                }
            }
        }

        return substr($abbr, 0, 8);
    }
}

$pc_school = '';
$pc_school_stmt = mysqli_prepare($conn, "SELECT u.school, u.department FROM users u WHERE u.id = ? LIMIT 1");
if ($pc_school_stmt) {
    $pc_user_id = (int)$_SESSION['user_id'];
    mysqli_stmt_bind_param($pc_school_stmt, "i", $pc_user_id);
    mysqli_stmt_execute($pc_school_stmt);
    $pc_result = mysqli_stmt_get_result($pc_school_stmt);
    if ($pc_row = mysqli_fetch_assoc($pc_result)) {
        if (!empty($pc_row['school'])) {
            $pc_school = $pc_row['school'];
        } elseif (!empty($pc_row['department'])) {
            // Fallback for legacy data until all users have a school assigned
            $pc_school = $pc_row['department'];
        }
    }
    mysqli_stmt_close($pc_school_stmt);
}

// --- PHP LOGIC FOR AJAX/FETCH CALLS ---
if (isset($_GET['action']) && $_GET['action'] === 'get_student_details') {
    header('Content-Type: application/json');

    if (!isset($_GET['id'])) {
        echo json_encode(['error' => 'Student ID not provided']);
        exit;
    }

    $student_id = (int)$_GET['id'];

    $student_scope_stmt = mysqli_prepare($conn, "SELECT s.class_id,
                                                        COALESCE(s.section_id, 0) AS section_id,
                                                        c.school,
                                                        c.semester,
                                                        COALESCE(c.academic_term_id, 0) AS academic_term_id
                                                 FROM students s
                                                 JOIN classes c ON c.id = s.class_id
                                                 WHERE s.id = ?
                                                 LIMIT 1");
    if (!$student_scope_stmt) {
        echo json_encode(['error' => 'Unable to resolve student scope.']);
        exit;
    }

    mysqli_stmt_bind_param($student_scope_stmt, 'i', $student_id);
    mysqli_stmt_execute($student_scope_stmt);
    $student_scope_res = mysqli_stmt_get_result($student_scope_stmt);
    $student_scope = $student_scope_res ? mysqli_fetch_assoc($student_scope_res) : null;
    mysqli_stmt_close($student_scope_stmt);

    if (!$student_scope) {
        echo json_encode(['error' => 'Student not found.']);
        exit;
    }

    $student_class_id = (int)($student_scope['class_id'] ?? 0);
    $student_section_id = (int)($student_scope['section_id'] ?? 0);
    $student_school = trim((string)($student_scope['school'] ?? ''));
    $student_semester = trim((string)($student_scope['semester'] ?? ''));

    $scope_conditions = [
        'tsa.class_id = ' . $student_class_id,
        '(tsa.section_id IS NULL OR tsa.section_id = 0 OR tsa.section_id = ' . $student_section_id . ')'
    ];
    if ($student_semester !== '') {
        $scope_conditions[] = "c_scope.semester = '" . mysqli_real_escape_string($conn, $student_semester) . "'";
    }

    $assigned_subjects_sql = "SELECT DISTINCT subj.id AS subject_id, subj.subject_name
                             FROM teacher_subject_assignments tsa
                             JOIN subjects subj ON subj.id = tsa.subject_id
                             JOIN classes c_scope ON c_scope.id = tsa.class_id
                             WHERE " . implode(' AND ', $scope_conditions) . "
                             ORDER BY subj.subject_name";
    $assigned_subjects_res = mysqli_query($conn, $assigned_subjects_sql);

    $assigned_subjects = [];
    if ($assigned_subjects_res) {
        while ($subject_row = mysqli_fetch_assoc($assigned_subjects_res)) {
            $sid = (int)($subject_row['subject_id'] ?? 0);
            if ($sid <= 0) {
                continue;
            }
            $assigned_subjects[$sid] = (string)($subject_row['subject_name'] ?? ('Subject ' . $sid));
        }
        mysqli_free_result($assigned_subjects_res);
    }

    // Ensure historical marks are visible even when assignment rows are missing for that semester snapshot.
    $marked_subjects_sql = "SELECT DISTINCT subj.id AS subject_id, subj.subject_name
                            FROM ica_student_marks ism
                            JOIN ica_components ic ON ic.id = ism.component_id
                            JOIN subjects subj ON subj.id = ic.subject_id
                            WHERE ism.student_id = " . $student_id . "
                            ORDER BY subj.subject_name";
    $marked_subjects_res = mysqli_query($conn, $marked_subjects_sql);
    if ($marked_subjects_res) {
        while ($marked_row = mysqli_fetch_assoc($marked_subjects_res)) {
            $sid = (int)($marked_row['subject_id'] ?? 0);
            if ($sid <= 0) {
                continue;
            }
            if (!isset($assigned_subjects[$sid])) {
                $assigned_subjects[$sid] = (string)($marked_row['subject_name'] ?? ('Subject ' . $sid));
            }
        }
        mysqli_free_result($marked_subjects_res);
    }

    $subject_components = [];
    $subject_faculty_map = [];
    $subject_topper_map = [];
    foreach ($assigned_subjects as $sid => $subject_name) {
        $subject_components[$sid] = [];
        $subject_faculty_map[$sid] = [];
    }

    if (!empty($assigned_subjects)) {
        $subject_id_list = implode(',', array_map('intval', array_keys($assigned_subjects)));

        $faculty_sql = "SELECT tsa.subject_id, u.name AS faculty_name
                        FROM teacher_subject_assignments tsa
                        JOIN users u ON u.id = tsa.teacher_id
                        WHERE tsa.class_id = " . $student_class_id . "
                          AND (tsa.section_id IS NULL OR tsa.section_id = 0 OR tsa.section_id = " . $student_section_id . ")
                          AND tsa.subject_id IN (" . $subject_id_list . ")";
        $faculty_res = mysqli_query($conn, $faculty_sql);
        if ($faculty_res) {
            while ($faculty_row = mysqli_fetch_assoc($faculty_res)) {
                $sid = (int)($faculty_row['subject_id'] ?? 0);
                if ($sid <= 0 || !isset($subject_faculty_map[$sid])) {
                    continue;
                }
                $faculty_name_raw = trim((string)($faculty_row['faculty_name'] ?? ''));
                if ($faculty_name_raw === '') {
                    continue;
                }
                $faculty_name_display = format_person_display($faculty_name_raw);
                if (!in_array($faculty_name_display, $subject_faculty_map[$sid], true)) {
                    $subject_faculty_map[$sid][] = $faculty_name_display;
                }
            }
            mysqli_free_result($faculty_res);
        }

        $subject_marks_sql = "SELECT subj.id AS subject_id,
                                     subj.subject_name,
                                     ic.id AS component_id,
                                     ic.component_name,
                                     ic.instances,
                                     ic.marks_per_instance,
                                     ic.total_marks,
                                     ic.scaled_total_marks,
                                     ism.marks,
                                     ism.instance_number
                              FROM subjects subj
                              LEFT JOIN ica_components ic
                                ON ic.subject_id = subj.id
                               AND (ic.class_id IS NULL OR ic.class_id = 0 OR ic.class_id = " . $student_class_id . ")
                              LEFT JOIN ica_student_marks ism
                                ON ism.component_id = ic.id
                               AND ism.student_id = " . $student_id . "
                              WHERE subj.id IN (" . $subject_id_list . ")
                              ORDER BY subj.subject_name, ic.component_name, ism.instance_number";
        $subject_marks_res = mysqli_query($conn, $subject_marks_sql);

        if ($subject_marks_res) {
            while ($row = mysqli_fetch_assoc($subject_marks_res)) {
                $sid = isset($row['subject_id']) ? (int)$row['subject_id'] : 0;
                if ($sid <= 0 || !isset($subject_components[$sid])) {
                    continue;
                }

                $component_id = isset($row['component_id']) ? (int)$row['component_id'] : 0;
                if ($component_id <= 0) {
                    continue;
                }

                if (!isset($subject_components[$sid][$component_id])) {
                    $instances = isset($row['instances']) ? (int)$row['instances'] : 1;
                    if ($instances <= 0) {
                        $instances = 1;
                    }

                    $marks_per_instance = isset($row['marks_per_instance']) ? (float)$row['marks_per_instance'] : 0.0;
                    $raw_capacity = isset($row['total_marks']) ? (float)$row['total_marks'] : 0.0;
                    if ($raw_capacity <= 0 && $marks_per_instance > 0) {
                        $raw_capacity = $marks_per_instance * $instances;
                    }

                    $scaled_total = isset($row['scaled_total_marks']) ? (float)$row['scaled_total_marks'] : 0.0;
                    if ($scaled_total <= 0 && $raw_capacity > 0) {
                        $scaled_total = $raw_capacity;
                    }

                    $scale_ratio = ($raw_capacity > 0 && $scaled_total > 0)
                        ? ($scaled_total / $raw_capacity)
                        : 1.0;

                    $subject_components[$sid][$component_id] = [
                        'component_name' => $row['component_name'] ?? 'Component',
                        'instances' => $instances,
                        'max_total' => $scaled_total,
                        'scale_ratio' => $scale_ratio,
                        'raw_total' => 0.0,
                        'has_any' => false,
                        'has_numeric' => false,
                    ];
                }

                $subject_components[$sid][$component_id]['has_any'] = true;
                if ($row['marks'] !== null) {
                    $subject_components[$sid][$component_id]['raw_total'] += (float)$row['marks'];
                    $subject_components[$sid][$component_id]['has_numeric'] = true;
                }
            }
            mysqli_free_result($subject_marks_res);
        }

        $section_scope_condition = $student_section_id > 0
            ? ('COALESCE(st_scope.section_id, 0) = ' . $student_section_id)
            : '1=1';
        $topper_sql = "SELECT st_scope.id AS student_id,
                              st_scope.name AS student_name,
                              ic.subject_id,
                              ism.marks,
                              ic.instances,
                              ic.marks_per_instance,
                              ic.total_marks,
                              ic.scaled_total_marks
                       FROM students st_scope
                       JOIN ica_student_marks ism ON ism.student_id = st_scope.id
                       JOIN ica_components ic ON ic.id = ism.component_id
                       WHERE st_scope.class_id = " . $student_class_id . "
                         AND " . $section_scope_condition . "
                         AND ic.subject_id IN (" . $subject_id_list . ")
                         AND ism.marks IS NOT NULL";
        $topper_res = mysqli_query($conn, $topper_sql);
        if ($topper_res) {
            $subject_student_scores = [];
            $subject_student_names = [];
            while ($topper_row = mysqli_fetch_assoc($topper_res)) {
                $sid = (int)($topper_row['subject_id'] ?? 0);
                $scope_student_id = (int)($topper_row['student_id'] ?? 0);
                if ($sid <= 0 || $scope_student_id <= 0) {
                    continue;
                }

                $instances = isset($topper_row['instances']) ? (int)$topper_row['instances'] : 1;
                if ($instances <= 0) {
                    $instances = 1;
                }
                $marks_per_instance = isset($topper_row['marks_per_instance']) ? (float)$topper_row['marks_per_instance'] : 0.0;
                $raw_capacity = isset($topper_row['total_marks']) ? (float)$topper_row['total_marks'] : 0.0;
                if ($raw_capacity <= 0 && $marks_per_instance > 0) {
                    $raw_capacity = $marks_per_instance * $instances;
                }
                $scaled_total = isset($topper_row['scaled_total_marks']) ? (float)$topper_row['scaled_total_marks'] : 0.0;
                if ($scaled_total <= 0 && $raw_capacity > 0) {
                    $scaled_total = $raw_capacity;
                }
                $scale_ratio = ($raw_capacity > 0 && $scaled_total > 0)
                    ? ($scaled_total / $raw_capacity)
                    : 1.0;
                $scaled_mark = ((float)$topper_row['marks']) * $scale_ratio;

                $score_key = $sid . ':' . $scope_student_id;
                if (!isset($subject_student_scores[$score_key])) {
                    $subject_student_scores[$score_key] = 0.0;
                }
                $subject_student_scores[$score_key] += $scaled_mark;
                $subject_student_names[$score_key] = format_person_display(trim((string)($topper_row['student_name'] ?? '')));
            }
            mysqli_free_result($topper_res);

            foreach ($subject_student_scores as $score_key => $total_score) {
                [$sid_text] = explode(':', $score_key, 2);
                $sid = (int)$sid_text;
                if (!isset($subject_topper_map[$sid]) || $total_score > (float)$subject_topper_map[$sid]['marks']) {
                    $subject_topper_map[$sid] = [
                        'marks' => round($total_score, 2),
                        'student_name' => $subject_student_names[$score_key] ?? ''
                    ];
                }
            }
        }
    }

    $subjects_payload = [];
    $subject_faculty_payload = [];
    $all_subjects_rows = [];
    foreach ($assigned_subjects as $sid => $subject_name) {
        $subject_label = trim($subject_name) !== '' ? trim($subject_name) : ('Subject ' . $sid);
        if (isset($subjects_payload[$subject_label])) {
            $subject_label .= ' (#' . $sid . ')';
        }

        $assigned_faculty = !empty($subject_faculty_map[$sid])
            ? implode(', ', $subject_faculty_map[$sid])
            : 'Not Assigned';
        $subject_faculty_payload[$subject_label] = $assigned_faculty;
        $subject_short = build_subject_short_label($subject_label);

        $subjects_payload[$subject_label] = [];
        $subject_total = 0.0;
        $subject_has_numeric = false;

        foreach (($subject_components[$sid] ?? []) as $component) {
            $label = build_component_sum_label($component['component_name'], $component['instances'], $component['max_total']);
            $is_absent = !$component['has_numeric'] && $component['has_any'];
            $scaled_mark = null;
            if ($component['has_numeric']) {
                $ratio = isset($component['scale_ratio']) ? (float)$component['scale_ratio'] : 1.0;
                $raw_total = isset($component['raw_total']) ? (float)$component['raw_total'] : 0.0;
                $scaled_mark = $raw_total * $ratio;
                $subject_total += $scaled_mark;
                $subject_has_numeric = true;
            }

            $subjects_payload[$subject_label][] = [
                'component_name' => $label,
                'marks' => $component['has_numeric'] ? round($scaled_mark, 2) : null,
                'max_marks' => $component['max_total'],
                'is_absent' => $is_absent,
                'assigned_faculty' => $assigned_faculty,
                'short_label' => $subject_short,
                'top_student_name' => $subject_topper_map[$sid]['student_name'] ?? '',
            ];
        }

        $all_subjects_rows[] = [
            'component_name' => $subject_label,
            'marks' => $subject_has_numeric ? round($subject_total, 2) : null,
            'max_marks' => isset($subject_topper_map[$sid]) ? (float)$subject_topper_map[$sid]['marks'] : null,
            'is_absent' => !$subject_has_numeric,
            'assigned_faculty' => $assigned_faculty,
            'short_label' => $subject_short,
            'top_student_name' => $subject_topper_map[$sid]['student_name'] ?? '',
        ];
    }

    $subjects_payload['All Subjects'] = $all_subjects_rows;

    echo json_encode([
        'subjects' => $subjects_payload,
        'subject_faculty' => $subject_faculty_payload,
    ]);
    exit;
}

// --- PHP LOGIC FOR PAGE DISPLAY ---
$school_param_provided = array_key_exists('school', $_GET);
$school_filter = '';
if ($school_param_provided) {
    $school_filter = trim($_GET['school']);
} elseif ($pc_school !== '') {
    $school_filter = $pc_school;
}
$contextSchool = $school_filter !== '' ? $school_filter : $pc_school;
$academicContext = resolveAcademicContext($conn, [
    'school_name' => $contextSchool
]);
$timeline_semester = '';
if (isset($academicContext['active']['semester_number']) && $academicContext['active']['semester_number'] !== null) {
    $timeline_semester = trim((string)$academicContext['active']['semester_number']);
}

$semester_param_provided = array_key_exists('semester', $_GET);
$semester_filter = ($semester_param_provided && isset($_GET['semester']) && $_GET['semester'] !== '')
    ? trim($_GET['semester'])
    : '';
if (!$semester_param_provided && $timeline_semester !== '') {
    $semester_filter = $timeline_semester;
}
$class_filter = isset($_GET['class_id']) && $_GET['class_id'] !== '' ? (int)$_GET['class_id'] : 0;
$section_filter = isset($_GET['section_id']) && $_GET['section_id'] !== '' ? (int)$_GET['section_id'] : 0;
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

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

$semesters = [];
if ($school_filter !== '') {
    $sem_sql = "SELECT DISTINCT semester FROM classes WHERE school = ?";
    $sem_types = 's';
    $sem_params = [$school_filter];
    if ($timeline_semester !== '') {
        $sem_sql .= " AND semester = ?";
        $sem_types .= 's';
        $sem_params[] = $timeline_semester;
    }
    $sem_sql .= " ORDER BY CAST(semester AS UNSIGNED)";
    $sem_stmt = mysqli_prepare($conn, $sem_sql);
    if ($sem_stmt) {
        if ($sem_types === 's') {
            mysqli_stmt_bind_param($sem_stmt, 's', $sem_params[0]);
        } else {
            mysqli_stmt_bind_param($sem_stmt, 'ss', $sem_params[0], $sem_params[1]);
        }
        mysqli_stmt_execute($sem_stmt);
        $sem_res = mysqli_stmt_get_result($sem_stmt);
        if ($sem_res) {
            while ($row = mysqli_fetch_assoc($sem_res)) {
                $semesters[] = $row['semester'];
            }
            mysqli_free_result($sem_res);
        }
        mysqli_stmt_close($sem_stmt);
    }
}

$classes_list = [];
if ($school_filter !== '') {
    $class_sql = "SELECT id, class_name, semester FROM classes WHERE school = ?";
    $class_types = 's';
    $class_params = [$school_filter];
    if ($semester_filter !== '') {
        $class_sql .= " AND semester = ?";
        $class_types .= 's';
        $class_params[] = $semester_filter;
    }
    $class_sql .= " ORDER BY CAST(semester AS UNSIGNED), class_name";
    $class_stmt = mysqli_prepare($conn, $class_sql);
    if ($class_stmt) {
        if ($class_types === 's') {
            mysqli_stmt_bind_param($class_stmt, 's', $class_params[0]);
        } else {
            mysqli_stmt_bind_param($class_stmt, 'ss', $class_params[0], $class_params[1]);
        }
        mysqli_stmt_execute($class_stmt);
        $class_res = mysqli_stmt_get_result($class_stmt);
        if ($class_res) {
            while ($row = mysqli_fetch_assoc($class_res)) {
                $row['class_name'] = format_class_label(
                    $row['class_name'] ?? '',
                    '',
                    $row['semester'] ?? '',
                    $school_filter
                );
                $classes_list[] = $row;
            }
            mysqli_free_result($class_res);
        }
        mysqli_stmt_close($class_stmt);
    }
}

$sections_list = [];
if ($class_filter > 0) {
    $sec_stmt = mysqli_prepare($conn, "SELECT id, section_name FROM sections WHERE class_id = ? ORDER BY section_name");
    if ($sec_stmt) {
        mysqli_stmt_bind_param($sec_stmt, "i", $class_filter);
        mysqli_stmt_execute($sec_stmt);
        $sec_res = mysqli_stmt_get_result($sec_stmt);
        if ($sec_res) {
            while ($row = mysqli_fetch_assoc($sec_res)) {
                $sections_list[] = $row;
            }
            mysqli_free_result($sec_res);
        }
        mysqli_stmt_close($sec_stmt);
    }
}

$filters_applied = $school_filter !== '';

$students = [];
if ($filters_applied) {
    $base_sql = "SELECT s.id, s.sap_id, s.name, s.roll_number, c.class_name, c.semester, c.school AS school_name, sec.section_name,
                                                    (SELECT AVG(ism.marks / NULLIF(ic.marks_per_instance, 0) * 100)
                                                     FROM ica_student_marks ism
                                                     JOIN ica_components ic ON ism.component_id = ic.id
                                                     WHERE ism.student_id = s.id AND ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance <> 0) AS avg_marks,
                                                    (SELECT COUNT(*)
                                                     FROM ica_student_marks ism
                                                     JOIN ica_components ic ON ism.component_id = ic.id
                                                     WHERE ism.student_id = s.id
                                                         AND ic.marks_per_instance IS NOT NULL AND ic.marks_per_instance <> 0
                                                         AND ism.marks IS NOT NULL) AS evaluated_components
                 FROM students s
                 JOIN classes c ON s.class_id = c.id
                 LEFT JOIN sections sec ON s.section_id = sec.id
                 WHERE c.school = ?";

    $types = 's';
    $params = [$school_filter];

    if ($semester_filter !== '') {
        $base_sql .= " AND c.semester = ?";
        $types .= 's';
        $params[] = $semester_filter;
    }
    if ($class_filter > 0) {
        $base_sql .= " AND s.class_id = ?";
        $types .= 'i';
        $params[] = $class_filter;
    }
    if ($section_filter > 0) {
        $base_sql .= " AND s.section_id = ?";
        $types .= 'i';
        $params[] = $section_filter;
    }

    $base_sql .= " GROUP BY s.id";

    if ($status_filter === 'at_risk') {
        $base_sql .= " HAVING avg_marks < 50";
    } elseif ($status_filter === 'average') {
        $base_sql .= " HAVING avg_marks >= 50 AND avg_marks < 70";
    } elseif ($status_filter === 'good') {
        $base_sql .= " HAVING avg_marks >= 70";
    }

    $base_sql .= " ORDER BY c.class_name, s.roll_number ASC";

    $stmt = mysqli_prepare($conn, $base_sql);
    if ($stmt) {
        $bind_values = [$stmt, $types];
        foreach ($params as $key => $value) {
            $bind_values[] = &$params[$key];
        }
        call_user_func_array('mysqli_stmt_bind_param', $bind_values);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $nameRaw = isset($row['name']) ? trim((string)$row['name']) : '';
                $studentRow = $row;
                $studentRow['name'] = $nameRaw;
                $studentRow['name_display'] = format_person_display($nameRaw);
                $formattedClass = format_class_label(
                    $row['class_name'] ?? '',
                    $row['section_name'] ?? '',
                    $row['semester'] ?? '',
                    $row['school_name'] ?? ''
                );
                if ($formattedClass !== '') {
                    $studentRow['class_name'] = $formattedClass;
                }
                $students[] = $studentRow;
            }
            mysqli_free_result($result);
        }
        mysqli_stmt_close($stmt);
    }
}

// --- HANDLE CSV EXPORT with DYNAMIC FILENAME ---
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
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

    $class_name_for_file = '';
    $section_name_for_file = '';
    $school_for_file = $school_filter;
    $start_date_for_file = '';
    $end_date_for_file = '';

    if ($class_filter > 0) {
        $info_q_sql = "SELECT c.class_name, c.school AS school_name, sec.section_name FROM classes c LEFT JOIN sections sec ON c.id = sec.class_id AND sec.id = ? WHERE c.id = ?";
        $info_q = mysqli_prepare($conn, $info_q_sql);
        if ($info_q) {
            mysqli_stmt_bind_param($info_q, "ii", $section_filter, $class_filter);
            if (mysqli_stmt_execute($info_q)) {
                $info_res = mysqli_stmt_get_result($info_q);
                if ($info_res && ($info_row = mysqli_fetch_assoc($info_res))) {
                    $class_name_for_file = $info_row['class_name'] ?? '';
                    $section_name_for_file = $info_row['section_name'] ?? '';
                    $school_for_file = $info_row['school_name'] ?? $school_for_file;

                    $sd_q = mysqli_prepare($conn, "SELECT start_date, end_date FROM academic_calendar WHERE school_name = ? AND CURDATE() BETWEEN start_date AND end_date LIMIT 1");
                    if ($sd_q) {
                        mysqli_stmt_bind_param($sd_q, "s", $school_for_file);
                        if (mysqli_stmt_execute($sd_q)) {
                            $sd_res = mysqli_stmt_get_result($sd_q);
                            if ($sd_res && ($date_row = mysqli_fetch_assoc($sd_res))) {
                                $start_date_for_file = $date_row['start_date'] ?? '';
                                $end_date_for_file = $date_row['end_date'] ?? '';
                            }
                            if ($sd_res) {
                                mysqli_free_result($sd_res);
                            }
                        }
                        mysqli_stmt_close($sd_q);
                    }
                }
                if ($info_res) {
                    mysqli_free_result($info_res);
                }
            }
            mysqli_stmt_close($info_q);
        }
    } else {
        if ($semester_filter !== '') {
            $class_name_for_file = 'Semester ' . $semester_filter;
        }
    }

    if ($school_for_file === '') {
        $school_for_file = $school_filter;
    }

    if ($start_date_for_file === '') {
        $start_date_for_file = date('Y-m-d');
    }
    if ($end_date_for_file === '') {
        $end_date_for_file = $start_date_for_file;
    }

    $filename_parts = [];
    if ($school_for_file !== '') {
        $filename_parts[] = $format_segment($school_for_file);
    }
    if ($class_name_for_file !== '') {
        $filename_parts[] = $format_segment($class_name_for_file);
    }
    if ($section_name_for_file !== '' && strtoupper($section_name_for_file) !== 'N/A') {
        $filename_parts[] = $format_segment('Section ' . $section_name_for_file);
    }
    $filename_parts[] = 'Student_Progress';

    $date_segment = $start_date_for_file && $end_date_for_file ? $start_date_for_file . '_to_' . $end_date_for_file : date('Y-m-d');

    $filename = trim(implode('_', array_filter($filename_parts)), '_');
    if ($filename !== '') {
        $filename .= '_' . $date_segment . '.csv';
    } else {
        $filename = 'Student_Progress_' . $date_segment . '.csv';
    }
    $filename = preg_replace('/_+/', '_', $filename);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['SAP ID', 'Name', 'Roll Number', 'Class', 'Division', 'School', 'Status']);
    foreach ($students as $student) {
        $avg = isset($student['avg_marks']) ? (float)$student['avg_marks'] : null;
        $evaluated = isset($student['evaluated_components']) ? (int)$student['evaluated_components'] : 0;
        if ($evaluated <= 0 || $avg === null) {
            $status = 'Not Allocated';
        } else {
            $status = $avg >= 70 ? 'Good' : ($avg >= 50 ? 'Average' : 'At-Risk');
        }
        fputcsv($output, [ $student['sap_id'], $student['name'], $student['roll_number'], $student['class_name'], $student['section_name'] ?? 'N/A', $student['school_name'], $status ]);
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
    <title>Student Progress - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; align-items: end; }
        .badge { padding: 4px 8px; border-radius: 12px; color: white; font-size: 0.8em; font-weight: bold; }
        .badge-success { background-color: #28a745; } .badge-warning { background-color: #ffc107; } .badge-danger { background-color: #dc3545; }
        .badge-neutral { background-color: #6c757d; }
        .clickable-row { cursor: pointer; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 900px; border-radius: 12px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; }
        .modal-header h4 { margin: 0; color: #A6192E; font-size: 1.5rem; }
        .close { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        body.dark-mode .modal-content { background-color: #5a5a5a; color: #e0e0e0; }
        body.dark-mode .modal-header { border-bottom: 1px solid #777; }
        #studentDetailChartContainer { height: 340px; width: 100%; }
        .modal-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-content { width: 88%; max-width: 1080px; }
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
            <a href="student_progress.php" class="active"><i class="fas fa-user-graduate"></i> <span>Students</span></a>
            <a href="course_progress.php"><i class="fas fa-book"></i> <span>Courses</span></a>
            <a href="program_reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
            <a href="send_alerts.php"><i class="fas fa-bell"></i> <span>Alerts</span></a>
            <a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
            <a href="edit_profile.php"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
        <div class="main-content">
            <div class="header"><h2>Student Progress Analysis</h2></div>
            <?php renderTermSwitcher($academicContext, [
                'school_name' => $contextSchool,
                'fallback_semester' => $semester_filter !== '' ? $semester_filter : null,
            ]); ?>
            <div class="container">
                <div class="card">
                    <div class="card-header"><div style="display: flex; justify-content: space-between; align-items: center;"><h5>Filters</h5></div></div>
                    <div class="card-body">
                        <form method="get" id="filterForm">
                            <div class="filter-grid">
                                <div class="form-group">
                                    <label>School</label>
                                    <select name="school" id="school_filter">
                                        <option value="" <?php echo $school_filter === '' ? 'selected' : ''; ?>>All Schools</option>
                                        <?php foreach ($available_schools as $school_option): ?>
                                            <option value="<?php echo htmlspecialchars($school_option); ?>" <?php echo $school_filter === $school_option ? 'selected' : ''; ?>><?php echo htmlspecialchars($school_option); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Semester</label>
                                    <select name="semester" id="semester_filter">
                                        <option value="">All semesters</option>
                                        <?php foreach ($semesters as $semester_option): ?>
                                            <option value="<?php echo htmlspecialchars($semester_option); ?>" <?php echo (string)$semester_option === (string)$semester_filter ? 'selected' : ''; ?>>Semester <?php echo htmlspecialchars($semester_option); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Class</label>
                                    <select name="class_id" id="class_filter" <?php echo $semester_filter === '' ? 'disabled' : ''; ?>>
                                        <?php if ($semester_filter === ''): ?>
                                            <option value="" selected>Select semester</option>
                                        <?php else: ?>
                                            <option value="">All classes</option>
                                            <?php foreach ($classes_list as $class_option): ?>
                                                <option value="<?php echo (int)$class_option['id']; ?>" <?php echo $class_filter === (int)$class_option['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($class_option['class_name']); ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="form-group" id="section_filter_container" <?php echo empty($sections_list) ? 'style="display:none;"' : ''; ?>>
                                    <label>Division</label>
                                    <select name="section_id" id="section_filter">
                                        <option value="">All divisions</option>
                                        <?php foreach ($sections_list as $section_option): ?>
                                            <option value="<?php echo (int)$section_option['id']; ?>" <?php echo $section_filter === (int)$section_option['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($section_option['section_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Status</label>
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
                                <button type="button" id="exportCsvBtn" class="btn" <?php echo empty($students) ? 'disabled' : ''; ?>><i class="fas fa-file-csv"></i> Download CSV</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($filters_applied): ?>
                <div class="card">
                    <div class="card-header"><h5>Student Data Overview</h5></div>
                    <div class="card-body">
                        <table>
                            <thead><tr><th>SAP ID</th><th>Name</th><th>Roll Number</th><th>Class</th><th>Division</th><th>School</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                    <tr><td colspan="7" style="text-align: center;">No students match the selected criteria.</td></tr>
                                <?php else: ?>
                                                            <?php foreach ($students as $student): ?>
                                                            <tr class="clickable-row student-edit-row"
                                                                data-student-id="<?php echo $student['id']; ?>"
                                                                data-sap-id="<?php echo htmlspecialchars($student['sap_id'] ?? '', ENT_QUOTES); ?>"
                                                                data-name="<?php echo htmlspecialchars($student['name'] ?? '', ENT_QUOTES); ?>"
                                                                data-name-display="<?php echo htmlspecialchars($student['name_display'] ?? ($student['name'] ?? ''), ENT_QUOTES); ?>"
                                                                data-roll-number="<?php echo htmlspecialchars($student['roll_number'] ?? '', ENT_QUOTES); ?>"
                                                                data-class-id="<?php echo (int)($student['class_id'] ?? 0); ?>"
                                                                data-section-id="<?php echo (int)($student['section_id'] ?? 0); ?>"
                                                                >
                                            <td><?php echo htmlspecialchars($student['sap_id']); ?></td>
                                            <td><?php echo htmlspecialchars($student['name_display'] ?? $student['name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                            <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['section_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($student['school_name']); ?></td>
                                            <td>
                                                <?php
                                                    $avg = isset($student['avg_marks']) ? (float)$student['avg_marks'] : null;
                                                    $evaluated = isset($student['evaluated_components']) ? (int)$student['evaluated_components'] : 0;
                                                    if ($evaluated <= 0 || $avg === null) {
                                                        echo '<span class="badge badge-neutral">Not Allocated</span>';
                                                    } elseif ($avg >= 70) {
                                                        echo '<span class="badge badge-success">Good</span>';
                                                    } elseif ($avg >= 50) {
                                                        echo '<span class="badge badge-warning">Average</span>';
                                                    } else {
                                                        echo '<span class="badge badge-danger">At-Risk</span>';
                                                    }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
            <!-- Edit Student Modal -->
            <div id="editStudentModalOverlay" class="modal-overlay" style="display:none;">
                <div class="modal" style="max-width:520px;">
                    <div class="modal-header">
                        <h5>Edit Student</h5>
                        <button class="modal-close" id="closeEditStudentModal">&times;</button>
                    </div>
                    <form id="editStudentForm" method="POST" action="edit_student.php">
                        <input type="hidden" name="student_id" id="es_student_id">
                        <div class="form-group">
                            <label>SAP ID</label>
                            <input type="text" name="sap_id" id="es_sap_id" required>
                        </div>
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" id="es_name" required>
                        </div>
                        <div class="form-group">
                            <label>Roll Number</label>
                            <input type="text" name="roll_number" id="es_roll" required>
                        </div>
                        <div class="form-group">
                            <label>Class ID (paste class id or leave as is)</label>
                            <input type="text" name="class_id" id="es_class_id">
                        </div>
                        <div class="form-group">
                            <label>Section ID (paste section id or leave blank)</label>
                            <input type="text" name="section_id" id="es_section_id">
                        </div>
                        <div style="text-align:right; margin-top:12px;">
                            <button type="button" class="btn btn-secondary" id="cancelEditStudent">Cancel</button>
                            <button type="submit" class="btn">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

    <div id="studentDetailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h4 id="modalStudentName"></h4><span class="close">&times;</span></div>
            <div id="modalBody">
                <div class="modal-controls">
                    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                        <h5 style="margin:0;">Performance Overview</h5>
                        <span id="assignedFacultyInfo" style="font-size:0.92rem; color:#475569; font-weight:600;"></span>
                    </div>
                    <div class="form-group"><select id="subjectFilter" class="form-control" style="width: auto;"></select></div>
                </div>
                <div id="studentDetailChartContainer"><canvas id="studentDetailChart"></canvas></div>
                <h5 id="modalMarksTitle" style="margin-top: 20px;">Component-wise Marks</h5>
                <table id="modalMarksTable">
                    <thead><tr><th id="modalPrimaryColumn">Component</th><th>Marks Obtained</th><th>Max Marks</th><th id="modalFacultyColumn">Assigned Faculty</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const schoolFilter = document.getElementById('school_filter');
            const semFilter = document.getElementById('semester_filter');
            const classFilter = document.getElementById('class_filter');
            const sectionContainer = document.getElementById('section_filter_container');
            const sectionFilter = document.getElementById('section_filter');
            const statusFilter = document.getElementById('status_filter');
            const exportBtn = document.getElementById('exportCsvBtn');

            const currentSchool = <?php echo json_encode($school_filter); ?>;
            const currentSem = <?php echo json_encode($semester_filter); ?>;
            const currentClass = <?php echo json_encode($class_filter > 0 ? $class_filter : ''); ?>;
            const currentSection = <?php echo json_encode($section_filter > 0 ? $section_filter : ''); ?>;
            const currentStatus = <?php echo json_encode($status_filter); ?>;
            const timelineSemester = <?php echo json_encode($timeline_semester); ?>;

            if (statusFilter && currentStatus !== null) {
                statusFilter.value = currentStatus;
            }

            function formatNumericValue(rawValue) {
                const value = Number(rawValue);
                if (!Number.isFinite(value)) {
                    return '--';
                }
                return Number.isInteger(value) ? value.toString() : value.toFixed(2);
            }

            function populateSemesters(school, selectedSemester, selectedClass, selectedSection) {
                semFilter.innerHTML = '<option value="">All semesters</option>';
                classFilter.innerHTML = '<option value="">Select semester</option>';
                classFilter.disabled = true;
                sectionFilter.innerHTML = '<option value="">All divisions</option>';
                sectionContainer.style.display = 'none';

                if (!school) {
                    return;
                }

                fetch(`get_semesters.php?school=${encodeURIComponent(school)}`)
                    .then(response => response.json())
                    .then(data => {
                        const filteredSemesters = Array.isArray(data)
                            ? data.filter(item => !timelineSemester || String(item.semester) === String(timelineSemester))
                            : [];

                        if (!selectedSemester && timelineSemester) {
                            selectedSemester = timelineSemester;
                        }

                        filteredSemesters.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.semester;
                            option.textContent = `Semester ${item.semester}`;
                            if (String(item.semester) === String(selectedSemester)) {
                                option.selected = true;
                            }
                            semFilter.appendChild(option);
                        });

                        if (selectedSemester) {
                            populateClasses(school, selectedSemester, selectedClass, selectedSection);
                        } else if (timelineSemester && filteredSemesters.length) {
                            populateClasses(school, timelineSemester, selectedClass, selectedSection);
                        }
                    })
                    .catch(() => {});
            }

            function populateClasses(school, semester, selectedClass, selectedSection) {
                classFilter.innerHTML = '<option value="">All classes</option>';
                classFilter.disabled = false;
                sectionFilter.innerHTML = '<option value="">All divisions</option>';
                sectionContainer.style.display = 'none';

                if (!school || !semester) {
                    classFilter.innerHTML = '<option value="">Select semester</option>';
                    classFilter.disabled = true;
                    return;
                }

                fetch(`get_classes.php?school=${encodeURIComponent(school)}&semester=${encodeURIComponent(semester)}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.id;
                            option.textContent = item.class_name;
                            if (Number(item.id) === Number(selectedClass)) {
                                option.selected = true;
                            }
                            classFilter.appendChild(option);
                        });

                        if (selectedClass) {
                            populateSections(selectedClass, selectedSection);
                        }
                    })
                    .catch(() => {});
            }

            function populateSections(classId, selectedSection) {
                sectionFilter.innerHTML = '<option value="">All divisions</option>';
                sectionContainer.style.display = 'none';

                if (!classId) {
                    return;
                }

                fetch(`get_sections.php?class_id=${encodeURIComponent(classId)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (Array.isArray(data) && data.length) {
                            sectionContainer.style.display = 'block';
                            data.forEach(sec => {
                                const option = document.createElement('option');
                                option.value = sec.id;
                                option.textContent = sec.section_name;
                                if (Number(sec.id) === Number(selectedSection)) {
                                    option.selected = true;
                                }
                                sectionFilter.appendChild(option);
                            });
                        }
                    })
                    .catch(() => {});
            }

            if (schoolFilter) {
                schoolFilter.addEventListener('change', function() {
                    const chosenSchool = this.value;
                    populateSemesters(chosenSchool, '', '', '');
                });
            }

            semFilter.addEventListener('change', function() {
                const chosenSchool = schoolFilter ? schoolFilter.value : currentSchool;
                const chosenSemester = this.value;
                populateClasses(chosenSchool, chosenSemester, '', '');
            });

            classFilter.addEventListener('change', function() {
                const chosenClass = this.value;
                populateSections(chosenClass, '');
            });

            if (currentSchool) {
                populateSemesters(currentSchool, currentSem, currentClass, currentSection);
                if (!currentSem) {
                    classFilter.innerHTML = '<option value="">Select semester</option>';
                    classFilter.disabled = true;
                }
            }
            if (currentClass) {
                populateSections(currentClass, currentSection);
            }

            if (exportBtn) {
                exportBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const params = new URLSearchParams(new FormData(document.getElementById('filterForm')));
                    params.append('export', 'csv');
                    window.location.href = `student_progress.php?${params.toString()}`;

                    // Edit student modal logic
                    const editModalOverlay = document.getElementById('editStudentModalOverlay');
                    const closeEditStudentModal = document.getElementById('closeEditStudentModal');
                    const cancelEditStudent = document.getElementById('cancelEditStudent');
                    const editForm = document.getElementById('editStudentForm');
                    const es_student_id = document.getElementById('es_student_id');
                    const es_sap_id = document.getElementById('es_sap_id');
                    const es_name = document.getElementById('es_name');
                    const es_roll = document.getElementById('es_roll');
                    const es_class_id = document.getElementById('es_class_id');
                    const es_section_id = document.getElementById('es_section_id');

                    function hideEditModal() {
                        editModalOverlay.style.display = 'none';
                    }

                    document.querySelectorAll('.student-edit-row').forEach(function(row) {
                        row.addEventListener('click', function() {
                            const id = this.dataset.studentId;
                            // Try to fill from data attrs first
                            es_student_id.value = id;
                            es_sap_id.value = this.dataset.sapId || '';
                            es_name.value = this.dataset.name || '';
                            es_roll.value = this.dataset.rollNumber || '';
                            es_class_id.value = this.dataset.classId || '';
                            es_section_id.value = this.dataset.sectionId || '';
                            editModalOverlay.style.display = 'flex';
                        });
                    });

                    closeEditStudentModal.addEventListener('click', hideEditModal);
                    cancelEditStudent.addEventListener('click', hideEditModal);
                });
            }

            const modal = document.getElementById('studentDetailModal');
            const closeBtn = modal.querySelector('.close');
            const subjectFilter = document.getElementById('subjectFilter');
            const modalMarksTitle = document.getElementById('modalMarksTitle');
            const modalPrimaryColumn = document.getElementById('modalPrimaryColumn');
            const modalFacultyInfo = document.getElementById('assignedFacultyInfo');
            const modalFacultyColumn = document.getElementById('modalFacultyColumn');
            let detailChart = null;
            let studentData = null;

            function updateModalView() {
                if (!studentData || !studentData.subjects) {
                    return;
                }

                let selectedSubject = subjectFilter.value;
                const availableSubjects = Object.keys(studentData.subjects);
                if (!selectedSubject || !studentData.subjects[selectedSubject]) {
                    selectedSubject = availableSubjects[0] || null;
                    if (selectedSubject) {
                        subjectFilter.value = selectedSubject;
                    }
                }

                const components = selectedSubject ? (studentData.subjects[selectedSubject] || []) : [];
                const isAllSubjects = selectedSubject === 'All Subjects';
                if (modalMarksTitle) {
                    modalMarksTitle.textContent = isAllSubjects ? 'Subject-wise Marks' : 'Component-wise Marks';
                }
                if (modalPrimaryColumn) {
                    modalPrimaryColumn.textContent = isAllSubjects ? 'Subject' : 'Component';
                }
                const selectedFaculty = !isAllSubjects && studentData.subject_faculty
                    ? (studentData.subject_faculty[selectedSubject] || 'Not Assigned')
                    : '';
                if (modalFacultyInfo) {
                    modalFacultyInfo.textContent = !isAllSubjects ? `Assigned Faculty: ${selectedFaculty}` : '';
                }
                if (modalFacultyColumn) {
                    modalFacultyColumn.style.display = isAllSubjects ? '' : 'none';
                }

                const labels = components.map(item => isAllSubjects ? (item.short_label || item.component_name) : item.component_name);
                const fullLabels = components.map(item => item.component_name || '');
                const topperNames = components.map(item => item.top_student_name || '');
                const marks = components.map(item => {
                    if (!item) {
                        return null;
                    }
                    const isAbsent = item.is_absent === true || item.marks === null || item.marks === undefined;
                    if (isAbsent) {
                        return null;
                    }
                    const numeric = Number(item.marks);
                    return Number.isFinite(numeric) ? numeric : null;
                });
                const maxMarks = components.map(item => {
                    if (!item || item.max_marks === null || item.max_marks === undefined || item.max_marks === '') {
                        return null;
                    }
                    const numeric = Number(item.max_marks);
                    return Number.isFinite(numeric) ? numeric : null;
                });

                if (detailChart) {
                    detailChart.destroy();
                }

                const ctx = document.getElementById('studentDetailChart').getContext('2d');
                detailChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [
                            { label: 'Marks Obtained', data: marks, backgroundColor: 'rgba(166, 25, 46, 0.78)' },
                            { label: 'Max Marks', data: maxMarks, backgroundColor: 'rgba(54, 162, 235, 0.55)' }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    title: (tooltipItems) => {
                                        if (!tooltipItems.length) {
                                            return '';
                                        }
                                        const idx = tooltipItems[0].dataIndex;
                                        return fullLabels[idx] || '';
                                    },
                                    label: (ctxInfo) => {
                                        const idx = ctxInfo.dataIndex;
                                        const rawValue = ctxInfo.raw;
                                        const baseLabel = ctxInfo.dataset.label || '';
                                        if (rawValue === null || rawValue === undefined) {
                                            return `${baseLabel}: Not Assigned`;
                                        }
                                        let line = `${baseLabel}: ${formatNumericValue(rawValue)}`;
                                        if (isAllSubjects && ctxInfo.datasetIndex === 1 && topperNames[idx]) {
                                            line += ` (Topper: ${topperNames[idx]})`;
                                        }
                                        return line;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 },
                                suggestedMax: 50
                            },
                            x: {
                                ticks: {
                                    maxRotation: 30,
                                    minRotation: 0
                                }
                            }
                        }
                    }
                });

                const tbody = document.querySelector('#modalMarksTable tbody');
                tbody.innerHTML = '';

                if (!components.length) {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');
                    cell.colSpan = isAllSubjects ? 4 : 3;
                    cell.textContent = 'Not Assigned for this selection.';
                    row.appendChild(cell);
                    tbody.appendChild(row);
                    return;
                }

                components.forEach(component => {
                    const row = document.createElement('tr');
                    const nameCell = document.createElement('td');
                    nameCell.textContent = component.component_name;
                    const marksCell = document.createElement('td');
                    const componentAbsent = component.is_absent === true || component.marks === null || component.marks === undefined;
                    marksCell.textContent = componentAbsent ? 'Not Assigned' : formatNumericValue(component.marks);
                    const maxCell = document.createElement('td');
                    maxCell.textContent = (component.max_marks === null || component.max_marks === undefined || component.max_marks === '')
                        ? ''
                        : formatNumericValue(component.max_marks);
                    row.appendChild(nameCell);
                    row.appendChild(marksCell);
                    row.appendChild(maxCell);
                    if (isAllSubjects) {
                        const facultyCell = document.createElement('td');
                        facultyCell.textContent = component.assigned_faculty || 'Not Assigned';
                        row.appendChild(facultyCell);
                    }
                    tbody.appendChild(row);
                });
            }

            subjectFilter.addEventListener('change', updateModalView);

            document.querySelectorAll('.clickable-row').forEach(row => {
                row.addEventListener('click', function() {
                    const studentId = this.dataset.studentId;
                    const nameCell = this.querySelector('td:nth-child(2)');
                    const idCell = this.querySelector('td:nth-child(1)');
                    const fallbackName = nameCell ? nameCell.textContent.trim() : '';
                    const fallbackSap = idCell ? idCell.textContent.trim() : '';
                    const studentName = this.dataset.nameDisplay || this.dataset.name || this.dataset.studentName || fallbackName;
                    const sapId = this.dataset.sapId || fallbackSap;

                    const headerText = studentName && sapId
                        ? `${studentName} • ${sapId}`
                        : studentName || sapId || 'Student';
                    document.getElementById('modalStudentName').innerText = headerText;
                    modal.style.display = 'block';

                    fetch(`student_progress.php?action=get_student_details&id=${encodeURIComponent(studentId)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                studentData = null;
                                subjectFilter.innerHTML = '';
                                updateModalView();
                                return;
                            }

                            studentData = data;
                            subjectFilter.innerHTML = '';

                            const subjectNames = Object.keys(data.subjects || {});
                            subjectNames.sort((a, b) => a.localeCompare(b));
                            const allIndex = subjectNames.indexOf('All Subjects');
                            if (allIndex > -1) {
                                subjectNames.splice(allIndex, 1);
                                subjectNames.unshift('All Subjects');
                            }

                            subjectNames.forEach(subjectName => {
                                const option = document.createElement('option');
                                option.value = subjectName;
                                option.textContent = subjectName;
                                subjectFilter.appendChild(option);
                            });

                            if (subjectNames.length) {
                                subjectFilter.value = subjectNames[0];
                            }

                            updateModalView();
                        })
                        .catch(() => {
                            studentData = null;
                        });
                });
            });

            closeBtn.onclick = function() {
                modal.style.display = 'none';
            };

            window.onclick = function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            };
        });
    </script>
</body>
</html>
