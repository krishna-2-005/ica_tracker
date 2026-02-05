<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/academic_context.php';
require_once __DIR__ . '/includes/email_notifications.php';
require_once __DIR__ . '/includes/term_switcher_ui.php';
require_once __DIR__ . '/includes/activity_logger.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}
$error = '';
$success = '';
$userNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$userNameDisplay = $userNameRaw !== '' ? format_person_display($userNameRaw) : '';

$adminSchool = isset($_SESSION['school']) ? (string)$_SESSION['school'] : '';
$academicContext = resolveAcademicContext($conn, [
    'school_name' => $adminSchool
]);
$activeTerm = $academicContext['active'] ?? null;
$activeTermId = isset($activeTerm['id']) ? (int)$activeTerm['id'] : null;

$class_sections = [];
$section_lookup = [];
$sections_query_global = "SELECT s.id, s.class_id, s.section_name FROM sections s INNER JOIN classes c ON s.class_id = c.id";
$sections_filters = [];
if ($activeTermId) {
    $sections_filters[] = '(c.academic_term_id = ' . (int)$activeTermId . ' OR c.academic_term_id IS NULL)';
}
if (!empty($sections_filters)) {
    $sections_query_global .= ' WHERE ' . implode(' AND ', $sections_filters);
}
$sections_query_global .= ' ORDER BY s.section_name';
$sections_result_global = mysqli_query($conn, $sections_query_global);
if ($sections_result_global) {
    while ($section_row = mysqli_fetch_assoc($sections_result_global)) {
        $class_key = (string)$section_row['class_id'];
        if (!isset($class_sections[$class_key])) {
            $class_sections[$class_key] = [];
        }
        $class_sections[$class_key][] = [
            'id' => (int)$section_row['id'],
            'name' => $section_row['section_name'],
        ];
        $section_lookup[(int)$section_row['id']] = $section_row['section_name'];
    }
    mysqli_free_result($sections_result_global);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $teacher_id = (int)$_POST['teacher_id'];
    $subject_id = (int)$_POST['subject_id'];
    $selected_option = isset($_POST['class_id']) ? $_POST['class_id'] : '';
    $selected_subject_id = $subject_id;
    $selected_class_option = $selected_option;

    $class_id = 0;
    $section_id = 0;
    if ($selected_option !== '') {
        $parts = explode(':', $selected_option);
        if (!empty($parts)) {
            $class_id = isset($parts[0]) ? (int)$parts[0] : 0;
            $section_id = isset($parts[1]) ? (int)$parts[1] : 0;
        }
    }

    $class_key = (string)$class_id;
    $class_has_sections = isset($class_sections[$class_key]) && !empty($class_sections[$class_key]);

    if ($class_id <= 0) {
        $error = "Please select a valid class.";
    }

    if ($error === '' && $teacher_id > 0 && $subject_id > 0 && $class_id > 0) {
        $conflict_stmt = null;
        if ($section_id > 0) {
            $conflict_sql = "SELECT tsa.teacher_id, u.name AS teacher_name FROM teacher_subject_assignments tsa LEFT JOIN users u ON u.id = tsa.teacher_id WHERE tsa.subject_id = ? AND tsa.class_id = ? AND tsa.section_id = ? LIMIT 1";
            $conflict_stmt = mysqli_prepare($conn, $conflict_sql);
            if ($conflict_stmt) {
                mysqli_stmt_bind_param($conflict_stmt, "iii", $subject_id, $class_id, $section_id);
            }
        } else {
            $conflict_sql = "SELECT tsa.teacher_id, u.name AS teacher_name FROM teacher_subject_assignments tsa LEFT JOIN users u ON u.id = tsa.teacher_id WHERE tsa.subject_id = ? AND tsa.class_id = ? AND tsa.section_id IS NULL LIMIT 1";
            $conflict_stmt = mysqli_prepare($conn, $conflict_sql);
            if ($conflict_stmt) {
                mysqli_stmt_bind_param($conflict_stmt, "ii", $subject_id, $class_id);
            }
        }

        if ($conflict_stmt) {
            mysqli_stmt_execute($conflict_stmt);
            mysqli_stmt_bind_result($conflict_stmt, $existing_teacher_id, $existing_teacher_name);
            if (mysqli_stmt_fetch($conflict_stmt)) {
                if ((int)$existing_teacher_id === $teacher_id) {
                    $error = "This teacher already has the selected subject and class.";
                } else {
                    $displayName = $existing_teacher_name !== null && $existing_teacher_name !== '' ? $existing_teacher_name : 'another teacher';
                    $error = "This subject and class are already assigned to " . $displayName . ".";
                }
            }
            mysqli_stmt_close($conflict_stmt);
        }

        if ($error !== '') {
            $conflict_stmt = null;
        }

        if ($error === '') {
            // --- Assign Subject to Teacher ---
            $subject_query = "SELECT subject_name, total_planned_hours FROM subjects WHERE id = ?";
            $stmt_subject = mysqli_prepare($conn, $subject_query);
            mysqli_stmt_bind_param($stmt_subject, "i", $subject_id);
            mysqli_stmt_execute($stmt_subject);
            $subject_result = mysqli_stmt_get_result($stmt_subject);
            $subject_details = mysqli_fetch_assoc($subject_result);

            if ($subject_details) {
                $check_sub_q = "SELECT id FROM teacher_subjects WHERE teacher_id = ? AND subject_name = ?";
                $stmt_check_sub = mysqli_prepare($conn, $check_sub_q);
                mysqli_stmt_bind_param($stmt_check_sub, "is", $teacher_id, $subject_details['subject_name']);
                mysqli_stmt_execute($stmt_check_sub);
                mysqli_stmt_store_result($stmt_check_sub);

                if (mysqli_stmt_num_rows($stmt_check_sub) == 0) {
                    $assign_sub_q = "INSERT INTO teacher_subjects (teacher_id, subject_name, total_planned_hours) VALUES (?, ?, ?)";
                    $stmt_assign_sub = mysqli_prepare($conn, $assign_sub_q);
                    mysqli_stmt_bind_param($stmt_assign_sub, "isi", $teacher_id, $subject_details['subject_name'], $subject_details['total_planned_hours']);
                    mysqli_stmt_execute($stmt_assign_sub);
                }
            }

            // --- Assign Class to Teacher ---
            $check_class_q = "SELECT teacher_id FROM teacher_classes WHERE teacher_id = ? AND class_id = ?";
            $stmt_check_class = mysqli_prepare($conn, $check_class_q);
            mysqli_stmt_bind_param($stmt_check_class, "ii", $teacher_id, $class_id);
            mysqli_stmt_execute($stmt_check_class);
            mysqli_stmt_store_result($stmt_check_class);

            if (mysqli_stmt_num_rows($stmt_check_class) == 0) {
                $assign_class_q = "INSERT INTO teacher_classes (teacher_id, class_id) VALUES (?, ?)";
                $stmt_assign_class = mysqli_prepare($conn, $assign_class_q);
                mysqli_stmt_bind_param($stmt_assign_class, "ii", $teacher_id, $class_id);
                mysqli_stmt_execute($stmt_assign_class);
            }

            // --- Create Teacher-Subject-Class-Section Assignment ---
            if ($error === '') {
                if ($section_id > 0) {
                    $check_assignment_q = "SELECT id FROM teacher_subject_assignments WHERE teacher_id = ? AND subject_id = ? AND class_id = ? AND section_id = ?";
                    $stmt_check_assignment = mysqli_prepare($conn, $check_assignment_q);
                    mysqli_stmt_bind_param($stmt_check_assignment, "iiii", $teacher_id, $subject_id, $class_id, $section_id);
                } else {
                    $check_assignment_q = "SELECT id FROM teacher_subject_assignments WHERE teacher_id = ? AND subject_id = ? AND class_id = ? AND section_id IS NULL";
                    $stmt_check_assignment = mysqli_prepare($conn, $check_assignment_q);
                    mysqli_stmt_bind_param($stmt_check_assignment, "iii", $teacher_id, $subject_id, $class_id);
                }

                mysqli_stmt_execute($stmt_check_assignment);
                mysqli_stmt_store_result($stmt_check_assignment);

                if (mysqli_stmt_num_rows($stmt_check_assignment) == 0) {
                    if ($section_id > 0) {
                        $assign_assignment_q = "INSERT INTO teacher_subject_assignments (teacher_id, subject_id, class_id, section_id) VALUES (?, ?, ?, ?)";
                        $stmt_assign_assignment = mysqli_prepare($conn, $assign_assignment_q);
                        mysqli_stmt_bind_param($stmt_assign_assignment, "iiii", $teacher_id, $subject_id, $class_id, $section_id);
                    } else {
                        $assign_assignment_q = "INSERT INTO teacher_subject_assignments (teacher_id, subject_id, class_id, section_id) VALUES (?, ?, ?, NULL)";
                        $stmt_assign_assignment = mysqli_prepare($conn, $assign_assignment_q);
                        mysqli_stmt_bind_param($stmt_assign_assignment, "iii", $teacher_id, $subject_id, $class_id);
                    }

                    mysqli_stmt_execute($stmt_assign_assignment);
                    mysqli_stmt_close($stmt_assign_assignment);
                }

                mysqli_stmt_close($stmt_check_assignment);
            }
            
            if ($error === '') {
                $success = "Assignment successful! The teacher is now linked to the subject and class.";
                if ($section_id > 0 && isset($section_lookup[$section_id])) {
                    $success .= " Division: " . $section_lookup[$section_id];
                }

                $class_option_key = $class_id . ':' . ($section_id > 0 ? $section_id : 0);
                $assignment_class_meta = $class_option_map[$class_option_key] ?? null;
                $assignment_label = $subject_details['subject_name'] ?? '';
                if ($assignment_class_meta && isset($assignment_class_meta['label'])) {
                    $assignment_label .= ' | ' . $assignment_class_meta['label'];
                }

                if (isset($_SESSION['user_id'])) {
                    $teacherSnapshot = resolve_user_snapshot_for_logging($conn, $teacher_id);
                    log_activity($conn, [
                        'actor_id' => (int)$_SESSION['user_id'],
                        'event_type' => 'assignment_created',
                        'event_label' => 'Teacher assignment created',
                        'description' => 'Teacher assigned to subject and class.',
                        'target_user_id' => $teacher_id > 0 ? $teacher_id : null,
                        'object_type' => 'teacher_subject_assignment',
                        'object_id' => $subject_id . ':' . $class_id . ':' . $section_id,
                        'object_label' => $assignment_label,
                        'metadata' => [
                            'teacher_id' => $teacher_id,
                            'teacher_unique_id' => $teacherSnapshot['unique_id'] ?? null,
                            'subject_id' => $subject_id,
                            'subject_name' => $subject_details['subject_name'] ?? null,
                            'class_id' => $class_id,
                            'section_id' => $section_id,
                            'class_label' => $assignment_class_meta['label'] ?? null,
                            'school' => $assignment_class_meta['school'] ?? null,
                            'semester' => $assignment_class_meta['semester'] ?? null,
                            'total_planned_hours' => $subject_details['total_planned_hours'] ?? null,
                            'active_term_id' => $activeTermId,
                        ],
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    ]);
                }

                $teacherContacts = email_notification_fetch_users($conn, [$teacher_id]);
                $teacherInfo = $teacherContacts[$teacher_id] ?? null;
                if ($teacherInfo && isset($teacherInfo['email']) && $teacherInfo['email'] !== '') {
                    $classLabel = email_notification_format_class_section($conn, $class_id, $section_id);
                    $studentCount = 0;
                    $countSql = 'SELECT COUNT(*) AS total FROM students WHERE class_id = ?';
                    if ($section_id > 0) {
                        $countSql .= ' AND COALESCE(section_id, 0) = ?';
                    }
                    $countStmt = mysqli_prepare($conn, $countSql);
                    if ($countStmt) {
                        if ($section_id > 0) {
                            mysqli_stmt_bind_param($countStmt, 'ii', $class_id, $section_id);
                        } else {
                            mysqli_stmt_bind_param($countStmt, 'i', $class_id);
                        }
                        mysqli_stmt_execute($countStmt);
                        $countRes = mysqli_stmt_get_result($countStmt);
                        if ($countRes && ($rowCount = mysqli_fetch_assoc($countRes))) {
                            $studentCount = (int)($rowCount['total'] ?? 0);
                            mysqli_free_result($countRes);
                        }
                        mysqli_stmt_close($countStmt);
                    }

                    $recipientName = $teacherInfo['name'] !== '' ? format_person_display($teacherInfo['name']) : 'Faculty Member';
                    $roleLabel = $teacherInfo['role'] === 'program_chair' ? 'Program Chair' : 'Faculty';
                    $academicYear = is_array($activeTerm) ? ($activeTerm['academic_year'] ?? '') : '';
                    $semesterValue = $assignment_class_meta['semester'] ?? '';

                    send_notification_email($teacherInfo['email'], EMAIL_SCENARIO_SUBJECT_ASSIGNMENT, [
                        'recipient_name' => $recipientName,
                        'subject_name' => $subject_details['subject_name'] ?? '',
                        'academic_year' => $academicYear,
                        'semester' => $semesterValue,
                        'class_section' => $classLabel,
                        'student_count' => $studentCount > 0 ? (string)$studentCount : '',
                        'assigned_role' => $roleLabel,
                        'subjects_url' => email_notification_app_url() . '/teacher_dashboard.php',
                    ]);
                }
            }
        }

    } else {
        if ($error === '') {
            $error = "Please make a selection for all fields.";
        }
    }
}

// Fetch initial data for dropdowns
$subjects_query = "SELECT id, subject_name, semester, school FROM subjects ORDER BY subject_name";
$subjects_result = mysqli_query($conn, $subjects_query);
$subjects = [];
$subjects_map = [];
if ($subjects_result) {
    while ($row = mysqli_fetch_assoc($subjects_result)) {
        $subjects[] = $row;
        $subjects_map[(int)$row['id']] = $row;
    }
    mysqli_free_result($subjects_result);
}

$classes_query = "SELECT id, class_name, semester, school, academic_term_id FROM classes";
$class_filters = [];
if ($activeTermId) {
    $class_filters[] = '(academic_term_id = ' . (int)$activeTermId . ' OR academic_term_id IS NULL)';
}
if ($adminSchool !== '') {
    $class_filters[] = "school = '" . mysqli_real_escape_string($conn, $adminSchool) . "'";
}
if (!empty($class_filters)) {
    $classes_query .= ' WHERE ' . implode(' AND ', $class_filters);
}
$classes_query .= ' ORDER BY class_name, semester';
$classes_result = mysqli_query($conn, $classes_query);
$classes = [];
if ($classes_result) {
    while ($row = mysqli_fetch_assoc($classes_result)) {
        $classes[] = $row;
    }
    mysqli_free_result($classes_result);
}

$class_option_map = [];
foreach ($classes as $class_row) {
    $class_key = (string)$class_row['id'];
    $sections_for_class = $class_sections[$class_key] ?? [];
    $className = $class_row['class_name'] ?? '';
    $semesterValue = $class_row['semester'] ?? '';
    $schoolName = $class_row['school'] ?? '';

    if (!empty($sections_for_class)) {
        foreach ($sections_for_class as $section_item) {
            $option_value = $class_row['id'] . ':' . $section_item['id'];
            $label = format_class_label($className, $section_item['name'] ?? '', $semesterValue, $schoolName);
            $class_option_map[$option_value] = [
                'class_id' => (int)$class_row['id'],
                'section_id' => (int)$section_item['id'],
                'label' => $label,
                'class_name' => $className,
                'semester' => $semesterValue,
                'school' => $schoolName,
            ];
        }
    } else {
        $option_value = $class_row['id'] . ':0';
        $label = format_class_label($className, '', $semesterValue, $schoolName);
        $class_option_map[$option_value] = [
            'class_id' => (int)$class_row['id'],
            'section_id' => 0,
            'label' => $label,
            'class_name' => $className,
            'semester' => $semesterValue,
            'school' => $schoolName,
        ];
    }
}

$selected_class_option = '';
$selected_subject_id = 0;

$teachers_for_edit = [];
$teachers_query = "SELECT id, name, school, role FROM users WHERE role IN ('teacher', 'program_chair') ORDER BY name";
$teachers_result = mysqli_query($conn, $teachers_query);
if ($teachers_result) {
    while ($teacher_row = mysqli_fetch_assoc($teachers_result)) {
        $display_name = format_teacher_display_name($teacher_row['name'], $teacher_row['role'] ?? null);
        $teachers_for_edit[] = [
            'id' => (int)$teacher_row['id'],
            'name' => $display_name,
            'name_display' => $display_name,
            'name_raw' => $teacher_row['name'],
            'school' => $teacher_row['school'],
            'role' => $teacher_row['role']
        ];
    }
    mysqli_free_result($teachers_result);
}

function resolve_assignment_class_option(array $class_option_map, string $value): ?array {
    if ($value === '' || !isset($class_option_map[$value])) {
        return null;
    }
    $meta = $class_option_map[$value];
    $meta['option_value'] = $value;
    $meta['class_id'] = isset($meta['class_id']) ? (int)$meta['class_id'] : 0;
    $meta['section_id'] = isset($meta['section_id']) ? (int)$meta['section_id'] : 0;
    return $meta;
}

function format_teacher_display_name(string $name, ?string $role): string {
    $trimmed = trim($name);
    $formatted = $trimmed !== '' ? format_person_display($trimmed) : '';
    if ($role === 'program_chair') {
        return ($formatted !== '' ? $formatted : 'PROGRAM CHAIR') . ' (PROGRAM CHAIR)';
    }
    return $formatted !== '' ? $formatted : $trimmed;
}

function format_class_display_name(string $name): string {
    $trimmed = trim($name);
    if ($trimmed === '') {
        return '';
    }
    $normalized = preg_replace_callback('/\b([0-9]+(?:st|nd|rd|th))\b/i', static function (array $matches) {
        return strtoupper($matches[1]);
    }, $trimmed);
    return preg_replace('/\byear\b/i', 'YEAR', $normalized);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_assignment') {
    header('Content-Type: application/json');

    $assignment_id = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;
    if ($assignment_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid assignment specified.']);
        exit;
    }

    $assignment_stmt = mysqli_prepare($conn, "SELECT tsa.teacher_id, tsa.subject_id, tsa.class_id, tsa.section_id, u.name AS teacher_name, u.role AS teacher_role, s.subject_name, s.school AS subject_school, s.semester AS subject_semester FROM teacher_subject_assignments tsa LEFT JOIN users u ON u.id = tsa.teacher_id LEFT JOIN subjects s ON s.id = tsa.subject_id WHERE tsa.id = ?");
    if (!$assignment_stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Unable to locate the assignment.']);
        exit;
    }
    mysqli_stmt_bind_param($assignment_stmt, "i", $assignment_id);
    mysqli_stmt_execute($assignment_stmt);
    $assignment_res = mysqli_stmt_get_result($assignment_stmt);
    $assignment_row = mysqli_fetch_assoc($assignment_res);
    mysqli_free_result($assignment_res);
    mysqli_stmt_close($assignment_stmt);

    if (!$assignment_row) {
        echo json_encode(['status' => 'error', 'message' => 'Assignment not found.']);
        exit;
    }

    $teacher_id = (int)$assignment_row['teacher_id'];
    $subject_id = (int)$assignment_row['subject_id'];
    $class_id = (int)$assignment_row['class_id'];
    $section_id = isset($assignment_row['section_id']) ? (int)$assignment_row['section_id'] : 0;
    $teacher_display = format_teacher_display_name($assignment_row['teacher_name'] ?? '', $assignment_row['teacher_role'] ?? null);
    $subject_name = $assignment_row['subject_name'] ?? '';
    $class_meta_key = $class_id . ':' . ($section_id > 0 ? $section_id : 0);
    $class_meta = isset($class_option_map[$class_meta_key]) && is_array($class_option_map[$class_meta_key])
        ? $class_option_map[$class_meta_key]
        : null;
    $class_label = is_array($class_meta) && isset($class_meta['label']) ? $class_meta['label'] : '';
    $class_school = is_array($class_meta) && isset($class_meta['school']) ? $class_meta['school'] : ($assignment_row['subject_school'] ?? '');
    $class_semester = is_array($class_meta) && isset($class_meta['semester']) ? $class_meta['semester'] : ($assignment_row['subject_semester'] ?? '');
    $class_label_display = $class_label !== ''
        ? $class_label
        : (is_array($class_meta) && isset($class_meta['class_name']) ? $class_meta['class_name'] : '');

    mysqli_begin_transaction($conn);

    try {
        $delete_assignment_stmt = mysqli_prepare($conn, "DELETE FROM teacher_subject_assignments WHERE id = ?");
        if (!$delete_assignment_stmt) {
            throw new Exception('Failed to delete assignment.');
        }
        mysqli_stmt_bind_param($delete_assignment_stmt, "i", $assignment_id);
        mysqli_stmt_execute($delete_assignment_stmt);
        mysqli_stmt_close($delete_assignment_stmt);

        if ($teacher_id > 0 && $subject_id > 0) {
            $check_subject_stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM teacher_subject_assignments WHERE teacher_id = ? AND subject_id = ?");
            if ($check_subject_stmt) {
                mysqli_stmt_bind_param($check_subject_stmt, "ii", $teacher_id, $subject_id);
                mysqli_stmt_execute($check_subject_stmt);
                mysqli_stmt_bind_result($check_subject_stmt, $remaining_subject_assignments);
                mysqli_stmt_fetch($check_subject_stmt);
                mysqli_stmt_close($check_subject_stmt);

                if ((int)$remaining_subject_assignments === 0) {
                    $remove_subject_stmt = mysqli_prepare($conn, "DELETE FROM teacher_subjects WHERE teacher_id = ? AND subject_name = (SELECT subject_name FROM subjects WHERE id = ? LIMIT 1)");
                    if ($remove_subject_stmt) {
                        mysqli_stmt_bind_param($remove_subject_stmt, "ii", $teacher_id, $subject_id);
                        mysqli_stmt_execute($remove_subject_stmt);
                        mysqli_stmt_close($remove_subject_stmt);
                    }
                }
            }
        }

        if ($teacher_id > 0 && $class_id > 0) {
            $check_class_stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM teacher_subject_assignments WHERE teacher_id = ? AND class_id = ?");
            if ($check_class_stmt) {
                mysqli_stmt_bind_param($check_class_stmt, "ii", $teacher_id, $class_id);
                mysqli_stmt_execute($check_class_stmt);
                mysqli_stmt_bind_result($check_class_stmt, $remaining_class_assignments);
                mysqli_stmt_fetch($check_class_stmt);
                mysqli_stmt_close($check_class_stmt);

                if ((int)$remaining_class_assignments === 0) {
                    $remove_class_stmt = mysqli_prepare($conn, "DELETE FROM teacher_classes WHERE teacher_id = ? AND class_id = ?");
                    if ($remove_class_stmt) {
                        mysqli_stmt_bind_param($remove_class_stmt, "ii", $teacher_id, $class_id);
                        mysqli_stmt_execute($remove_class_stmt);
                        mysqli_stmt_close($remove_class_stmt);
                    }
                }
            }
        }

        mysqli_commit($conn);
    } catch (Exception $deleteException) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete assignment. Please try again.']);
        exit;
    }

    if (isset($_SESSION['user_id'])) {
        $assignmentLabel = $subject_name !== '' ? $subject_name : 'Assignment';
        if ($class_label_display !== '' && strcasecmp($class_label_display, 'Not linked yet') !== 0) {
            $assignmentLabel .= ' | ' . $class_label_display;
        }
        $teacherSnapshot = resolve_user_snapshot_for_logging($conn, $teacher_id);
        log_activity($conn, [
            'actor_id' => (int)$_SESSION['user_id'],
            'event_type' => 'assignment_deleted',
            'event_label' => 'Teacher assignment deleted',
            'description' => 'Teacher assignment removed via admin interface.',
            'target_user_id' => $teacher_id > 0 ? $teacher_id : null,
            'object_type' => 'teacher_subject_assignment',
            'object_id' => (string)$assignment_id,
            'object_label' => $assignmentLabel,
            'metadata' => [
                'assignment_id' => $assignment_id,
                'teacher_id' => $teacher_id,
                'teacher_unique_id' => $teacherSnapshot['unique_id'] ?? null,
                'teacher_name' => $teacher_display,
                'subject_id' => $subject_id,
                'subject_name' => $subject_name,
                'class_id' => $class_id,
                'section_id' => $section_id,
                'class_label' => $class_label_display,
                'class_school' => $class_school,
                'class_semester' => $class_semester,
            ],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    echo json_encode(['status' => 'ok', 'data' => ['assignment_id' => $assignment_id]]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_assignment_inline') {
    header('Content-Type: application/json');

    $assignment_id = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;
    $teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;
    $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
    $class_option_value = isset($_POST['class_option']) ? trim((string)$_POST['class_option']) : '';

    if ($assignment_id <= 0 || $teacher_id <= 0 || $subject_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid assignment details provided.']);
        exit;
    }

    $class_meta = resolve_assignment_class_option($class_option_map, $class_option_value);
    if (!$class_meta || $class_meta['class_id'] <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Choose a valid class / division to update.']);
        exit;
    }

    $assignment_stmt = mysqli_prepare($conn, "SELECT teacher_id, subject_id, class_id, section_id FROM teacher_subject_assignments WHERE id = ?");
    if (!$assignment_stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Unable to load assignment.']);
        exit;
    }
    mysqli_stmt_bind_param($assignment_stmt, "i", $assignment_id);
    mysqli_stmt_execute($assignment_stmt);
    $assignment_res = mysqli_stmt_get_result($assignment_stmt);
    $assignment_row = mysqli_fetch_assoc($assignment_res);
    mysqli_free_result($assignment_res);
    mysqli_stmt_close($assignment_stmt);

    if (!$assignment_row) {
        echo json_encode(['status' => 'error', 'message' => 'Assignment not found.']);
        exit;
    }

    $teacher_stmt = mysqli_prepare($conn, "SELECT name, role FROM users WHERE id = ? AND role IN ('teacher', 'program_chair')");
    if (!$teacher_stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Unable to load teacher information.']);
        exit;
    }
    mysqli_stmt_bind_param($teacher_stmt, "i", $teacher_id);
    mysqli_stmt_execute($teacher_stmt);
    $teacher_res = mysqli_stmt_get_result($teacher_stmt);
    $teacher_row = mysqli_fetch_assoc($teacher_res);
    $teacher_display_name = null;
    if ($teacher_row) {
        $teacher_display_name = format_teacher_display_name($teacher_row['name'], $teacher_row['role'] ?? null);
    }
    mysqli_free_result($teacher_res);
    mysqli_stmt_close($teacher_stmt);

    if (!$teacher_row) {
        echo json_encode(['status' => 'error', 'message' => 'Selected teacher is not available.']);
        exit;
    }

    $subject_stmt = mysqli_prepare($conn, "SELECT subject_name, total_planned_hours, school, semester FROM subjects WHERE id = ?");
    if (!$subject_stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Unable to load subject details.']);
        exit;
    }
    mysqli_stmt_bind_param($subject_stmt, "i", $subject_id);
    mysqli_stmt_execute($subject_stmt);
    $subject_res = mysqli_stmt_get_result($subject_stmt);
    $subject_row = mysqli_fetch_assoc($subject_res);
    mysqli_free_result($subject_res);
    mysqli_stmt_close($subject_stmt);

    if (!$subject_row) {
        echo json_encode(['status' => 'error', 'message' => 'Selected subject is not available.']);
        exit;
    }

    $section_id = $class_meta['section_id'];
    $duplicate_stmt_sql = "SELECT tsa.id, tsa.teacher_id, u.name FROM teacher_subject_assignments tsa LEFT JOIN users u ON u.id = tsa.teacher_id WHERE tsa.subject_id = ? AND tsa.class_id = ? AND ((tsa.section_id IS NULL AND ? = 0) OR tsa.section_id = ?) AND tsa.id <> ? LIMIT 1";
    $duplicate_stmt = mysqli_prepare($conn, $duplicate_stmt_sql);
    if ($duplicate_stmt) {
        $section_compare = $section_id;
        mysqli_stmt_bind_param($duplicate_stmt, "iiiii", $subject_id, $class_meta['class_id'], $section_compare, $section_compare, $assignment_id);
        mysqli_stmt_execute($duplicate_stmt);
        mysqli_stmt_bind_result($duplicate_stmt, $existing_assignment_id, $existing_teacher_id, $existing_teacher_name);
        if (mysqli_stmt_fetch($duplicate_stmt)) {
            mysqli_stmt_close($duplicate_stmt);
            if ((int)$existing_teacher_id === $teacher_id) {
                echo json_encode(['status' => 'error', 'message' => 'This teacher already has the same subject and class combination.']);
            } else {
                $conflict_teacher = $existing_teacher_name !== null && $existing_teacher_name !== '' ? $existing_teacher_name : 'another teacher';
                echo json_encode(['status' => 'error', 'message' => 'This subject and class are already assigned to ' . $conflict_teacher . '.']);
            }
            exit;
        }
        mysqli_stmt_close($duplicate_stmt);
    }

    mysqli_begin_transaction($conn);
    try {
        $update_stmt = mysqli_prepare($conn, "UPDATE teacher_subject_assignments SET teacher_id = ?, subject_id = ?, class_id = ?, section_id = ? WHERE id = ?");
        if (!$update_stmt) {
            throw new Exception('Failed to prepare assignment update.');
        }
        $section_param = $section_id > 0 ? $section_id : null;
        mysqli_stmt_bind_param($update_stmt, "iiiii", $teacher_id, $subject_id, $class_meta['class_id'], $section_param, $assignment_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);

        $check_tc_stmt = mysqli_prepare($conn, "SELECT 1 FROM teacher_classes WHERE teacher_id = ? AND class_id = ? LIMIT 1");
        if ($check_tc_stmt) {
            mysqli_stmt_bind_param($check_tc_stmt, "ii", $teacher_id, $class_meta['class_id']);
            mysqli_stmt_execute($check_tc_stmt);
            mysqli_stmt_store_result($check_tc_stmt);
            $has_class = mysqli_stmt_num_rows($check_tc_stmt) > 0;
            mysqli_stmt_close($check_tc_stmt);
            if (!$has_class) {
                $insert_tc_stmt = mysqli_prepare($conn, "INSERT INTO teacher_classes (teacher_id, class_id) VALUES (?, ?)");
                if ($insert_tc_stmt) {
                    mysqli_stmt_bind_param($insert_tc_stmt, "ii", $teacher_id, $class_meta['class_id']);
                    mysqli_stmt_execute($insert_tc_stmt);
                    mysqli_stmt_close($insert_tc_stmt);
                }
            }
        }

        $check_ts_stmt = mysqli_prepare($conn, "SELECT id FROM teacher_subjects WHERE teacher_id = ? AND subject_name = ? LIMIT 1");
        if ($check_ts_stmt) {
            mysqli_stmt_bind_param($check_ts_stmt, "is", $teacher_id, $subject_row['subject_name']);
            mysqli_stmt_execute($check_ts_stmt);
            mysqli_stmt_store_result($check_ts_stmt);
            $has_subject = mysqli_stmt_num_rows($check_ts_stmt) > 0;
            mysqli_stmt_close($check_ts_stmt);
            if ($has_subject) {
                $update_ts_stmt = mysqli_prepare($conn, "UPDATE teacher_subjects SET total_planned_hours = ? WHERE teacher_id = ? AND subject_name = ?");
                if ($update_ts_stmt) {
                    mysqli_stmt_bind_param($update_ts_stmt, "iis", $subject_row['total_planned_hours'], $teacher_id, $subject_row['subject_name']);
                    mysqli_stmt_execute($update_ts_stmt);
                    mysqli_stmt_close($update_ts_stmt);
                }
            } else {
                $insert_ts_stmt = mysqli_prepare($conn, "INSERT INTO teacher_subjects (teacher_id, subject_name, total_planned_hours) VALUES (?, ?, ?)");
                if ($insert_ts_stmt) {
                    mysqli_stmt_bind_param($insert_ts_stmt, "isi", $teacher_id, $subject_row['subject_name'], $subject_row['total_planned_hours']);
                    mysqli_stmt_execute($insert_ts_stmt);
                    mysqli_stmt_close($insert_ts_stmt);
                }
            }
        }

        mysqli_commit($conn);
    } catch (Exception $assignmentEx) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 'error', 'message' => $assignmentEx->getMessage()]);
        exit;
    }

    if (isset($_SESSION['user_id'])) {
        $teacherSnapshot = resolve_user_snapshot_for_logging($conn, $teacher_id);
        $previousAssignment = [
            'teacher_id' => isset($assignment_row['teacher_id']) ? (int)$assignment_row['teacher_id'] : null,
            'subject_id' => isset($assignment_row['subject_id']) ? (int)$assignment_row['subject_id'] : null,
            'class_id' => isset($assignment_row['class_id']) ? (int)$assignment_row['class_id'] : null,
            'section_id' => isset($assignment_row['section_id']) ? ($assignment_row['section_id'] !== null ? (int)$assignment_row['section_id'] : null) : null,
        ];
        $assignmentLabel = $subject_row['subject_name'] ?? '';
        if (!empty($class_meta['label'])) {
            $assignmentLabel .= ' | ' . $class_meta['label'];
        }
        log_activity($conn, [
            'actor_id' => (int)$_SESSION['user_id'],
            'event_type' => 'assignment_updated',
            'event_label' => 'Teacher assignment updated',
            'description' => 'Teacher assignment updated via inline edit.',
            'target_user_id' => $teacher_id > 0 ? $teacher_id : null,
            'object_type' => 'teacher_subject_assignment',
            'object_id' => (string)$assignment_id,
            'object_label' => $assignmentLabel,
            'metadata' => [
                'assignment_id' => $assignment_id,
                'teacher_id' => $teacher_id,
                'teacher_unique_id' => $teacherSnapshot['unique_id'] ?? null,
                'subject_id' => $subject_id,
                'subject_name' => $subject_row['subject_name'] ?? null,
                'class_id' => $class_meta['class_id'],
                'section_id' => $class_meta['section_id'],
                'class_label' => $class_meta['label'] ?? null,
                'school' => $subject_row['school'] ?? ($class_meta['school'] ?? null),
                'semester' => $subject_row['semester'] ?? ($class_meta['semester'] ?? null),
                'previous_assignment' => $previousAssignment,
                'request_source' => 'inline_edit',
            ],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    echo json_encode([
        'status' => 'ok',
        'data' => [
            'assignment_id' => $assignment_id,
            'teacher_id' => $teacher_id,
            'teacher_name' => $teacher_display_name ?? '',
            'subject_id' => $subject_id,
            'subject_name' => $subject_row['subject_name'],
            'class_option' => $class_meta['option_value'],
            'class_label' => $class_meta['label'],
            'school' => $subject_row['school'],
            'semester' => $subject_row['semester']
        ]
    ]);
    exit;
}
$class_options_for_js = array_map(static function ($value_key, $meta) {
    return [
        'value' => $value_key,
        'label' => $meta['label'],
        'classId' => $meta['class_id'],
        'sectionId' => $meta['section_id'],
        'school' => $meta['school'],
        'semester' => $meta['semester'],
    ];
}, array_keys($class_option_map), $class_option_map);

$subjects_meta_for_js = array_map(static function ($meta) {
    return [
        'id' => (int)$meta['id'],
        'school' => $meta['school'],
        'semester' => $meta['semester'],
        'name' => $meta['subject_name'],
    ];
}, $subjects);

$teacher_card_data_map = [];
$teacher_assignments_query = "
    SELECT tsa.id AS assignment_id,
        u.id AS teacher_id,
        u.name AS teacher_name,
        u.role AS teacher_role,
        s.id AS subject_id,
        s.subject_name,
        c.id AS class_id,
        c.class_name,
        c.semester,
        c.school,
        sec.id AS section_id,
        sec.section_name
    FROM users u
    LEFT JOIN teacher_subject_assignments tsa ON tsa.teacher_id = u.id
    LEFT JOIN subjects s ON s.id = tsa.subject_id
    LEFT JOIN classes c ON c.id = tsa.class_id
    LEFT JOIN sections sec ON sec.id = tsa.section_id
        WHERE u.role IN ('teacher', 'program_chair')
            AND COALESCE(LOWER(u.status), 'active') = 'active'
";
if ($activeTermId) {
    $teacher_assignments_query .= '    AND (c.academic_term_id = ' . (int)$activeTermId . ' OR c.academic_term_id IS NULL OR c.id IS NULL)';
}
$teacher_assignments_query .= "
    ORDER BY u.name, s.subject_name, c.class_name, sec.section_name
";
$teacher_assignments_result = mysqli_query($conn, $teacher_assignments_query);
$assignments_flat = [];
if ($teacher_assignments_result) {
    while ($row = mysqli_fetch_assoc($teacher_assignments_result)) {
        $teacher_id = (int)$row['teacher_id'];
        $teacher_display_name = format_teacher_display_name($row['teacher_name'] ?? '', $row['teacher_role'] ?? null);
        if (!isset($teacher_card_data_map[$teacher_id])) {
            $teacher_card_data_map[$teacher_id] = [
                'teacher_id' => $teacher_id,
                'teacher_name' => $teacher_display_name,
                'subjects' => [],
                'classes' => [],
                'assignments' => [],
                '_subject_seen' => [],
                '_class_seen' => [],
                '_assignment_seen' => [],
            ];
        }

        $cardEntry = &$teacher_card_data_map[$teacher_id];

        $subjectName = $row['subject_name'] ?? '';
        $classLabel = '';
        if (!empty($row['class_name'])) {
            $classLabel = format_class_label(
                $row['class_name'],
                $row['section_name'] ?? '',
                $row['semester'] ?? '',
                $row['school'] ?? ''
            );
        }

        if ($subjectName !== '') {
            $subjectKey = strtolower($subjectName);
            if (!isset($cardEntry['_subject_seen'][$subjectKey])) {
                $cardEntry['_subject_seen'][$subjectKey] = true;
                $cardEntry['subjects'][] = $subjectName;
            }
        }

        if ($classLabel !== '') {
            $classKey = strtolower($classLabel);
            if (!isset($cardEntry['_class_seen'][$classKey])) {
                $cardEntry['_class_seen'][$classKey] = true;
                $cardEntry['classes'][] = $classLabel;
            }
        }

        if ($subjectName !== '' || $classLabel !== '') {
            $assignmentKey = strtolower($subjectName . '||' . $classLabel);
            if (!isset($cardEntry['_assignment_seen'][$assignmentKey])) {
                $cardEntry['_assignment_seen'][$assignmentKey] = true;
                $cardEntry['assignments'][] = [
                    'subject' => $subjectName !== '' ? $subjectName : 'Not linked yet',
                    'class' => $classLabel !== '' ? $classLabel : 'Not linked yet',
                ];
            }
        }

        if (!empty($row['assignment_id'])) {
            $class_option_value = '';
            if (!empty($row['class_id'])) {
                $section_component = !empty($row['section_id']) ? (int)$row['section_id'] : 0;
                $candidate_value = (int)$row['class_id'] . ':' . $section_component;
                if (isset($class_option_map[$candidate_value])) {
                    $class_option_value = $candidate_value;
                }
            }
            $assignments_flat[] = [
                'assignment_id' => (int)$row['assignment_id'],
                'teacher_id' => $teacher_id,
                'teacher_name' => $teacher_display_name,
                'subject_id' => isset($row['subject_id']) ? (int)$row['subject_id'] : 0,
                'subject_name' => $row['subject_name'] ?? 'Not linked yet',
                'class_label' => $classLabel !== '' ? $classLabel : 'Not linked yet',
                'class_option' => $class_option_value,
                'school' => $row['school'] ?? '',
                'semester' => $row['semester'] ?? ''
            ];
        }

        unset($cardEntry);
    }
    mysqli_free_result($teacher_assignments_result);
}

$assignment_schools = [];
$assignment_semesters = [];
$assignment_class_labels = [];
$assignment_class_labels_map = [];

foreach ($assignments_flat as $flat_entry) {
    $school_key = isset($flat_entry['school']) ? trim((string)$flat_entry['school']) : '';
    if ($school_key !== '' && !in_array($school_key, $assignment_schools, true)) {
        $assignment_schools[] = $school_key;
    }

    $semester_value = isset($flat_entry['semester']) ? trim((string)$flat_entry['semester']) : '';
    if ($semester_value !== '' && !in_array($semester_value, $assignment_semesters, true)) {
        $assignment_semesters[] = $semester_value;
    }

    $class_label_value = isset($flat_entry['class_label']) ? trim((string)$flat_entry['class_label']) : '';
    if ($class_label_value !== '' && strcasecmp($class_label_value, 'Not linked yet') !== 0) {
        $normalized_label = strtolower($class_label_value);
        if (!isset($assignment_class_labels_map[$normalized_label])) {
            $assignment_class_labels_map[$normalized_label] = $class_label_value;
            $assignment_class_labels[] = $class_label_value;
        }
    }
}

sort($assignment_schools, SORT_NATURAL | SORT_FLAG_CASE);
sort($assignment_semesters, SORT_NATURAL | SORT_FLAG_CASE);
sort($assignment_class_labels, SORT_NATURAL | SORT_FLAG_CASE);

$teacher_card_data = [];
foreach ($teacher_card_data_map as $entry) {
    $entry['subject_count'] = count($entry['subjects']);
    $entry['class_count'] = count($entry['classes']);
    unset($entry['_subject_seen'], $entry['_class_seen'], $entry['_assignment_seen']);
    $teacher_card_data[] = $entry;
}
usort($teacher_card_data, static function ($a, $b) {
    return strcasecmp($a['teacher_name'], $b['teacher_name']);
});

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Teachers - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .teacher-card {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        .teacher-card.teacher-card-active {
            border-color: #a6192e;
            background: #fff5f7;
            box-shadow: 0 2px 8px rgba(166, 25, 46, 0.15);
        }
        .teacher-card-meta {
            font-size: 0.9rem;
            color: #555;
        }
        .teacher-card-details {
            display: none;
            width: 100%;
            border-top: 1px solid #e0e0e0;
            padding-top: 10px;
            margin-top: 4px;
            background: #ffffff;
            border-radius: 6px;
            padding: 12px;
        }
        .teacher-card.teacher-card-active .teacher-card-details {
            display: block;
        }
        .teacher-card-details-header,
        .teacher-card-details-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.85rem;
            line-height: 1.35;
        }
        .teacher-card-details-header span,
        .teacher-card-details-row span {
            flex: 1;
        }
        .teacher-card-details-row span {
            color: #333;
        }
        .teacher-card-details-header span:last-child,
        .teacher-card-details-row span:last-child {
            text-align: right;
        }
        .teacher-card-details-header {
            font-weight: 600;
            color: #a6192e;
            padding-bottom: 6px;
            border-bottom: 1px solid #d9d9d9;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .teacher-card-details-row {
            padding: 6px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .teacher-card-details-row:last-child {
            border-bottom: none;
        }
        .teacher-card-details-empty {
            font-size: 0.85rem;
            color: #777;
        }
        .ica-components-wrapper {
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: #fafafa;
            margin-top: 8px;
        }
        .ica-components-title {
            font-weight: 600;
            margin-bottom: 6px;
            color: #a6192e;
        }
        .ica-components-placeholder {
            font-size: 0.9rem;
            color: #555;
        }
        .ica-components-placeholder.error {
            color: #c62828;
        }
        .ica-components-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        .ica-components-table th,
        .ica-components-table td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }
        .ica-components-table thead {
            background: #f2f2f2;
        }
        .ica-components-table tbody tr:nth-child(even) {
            background: #fbfbfb;
        }
        .table-action-btn {
            display: inline-block;
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            background-color: #A6192E;
            color: #fff;
            font-size: 0.85rem;
            cursor: pointer;
            margin-right: 6px;
            transition: background-color 0.2s ease;
        }
        .table-action-btn:last-child {
            margin-right: 0;
        }
        .table-action-btn:hover {
            background-color: #8b1425;
        }
        .assignment-select {
            width: 100%;
            box-sizing: border-box;
            padding: 6px 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
            <h2>ICA Tracker</h2>
            <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> <span>Manage Teachers</span></a>
            <a href="create_classes.php"><i class="fas fa-layer-group"></i> <span>Create Classes</span></a>
            <a href="create_subjects.php"><i class="fas fa-book"></i> <span>Create Subjects</span></a>
            <a href="assign_teachers.php" class="active"><i class="fas fa-user-tag"></i> <span>Assign Teachers</span></a>
            <a href="manage_electives.php"><i class="fas fa-user-friends"></i> <span>Manage Electives</span></a>
            <a href="change_roles.php"><i class="fas fa-user-cog"></i> <span>Change Roles</span></a>
            <a href="bulk_add_students.php"><i class="fas fa-file-upload"></i> <span>Add Students</span></a>
                       <a href="manage_academic_calendar.php"><i class="fas fa-calendar-alt"></i> <span>Academic Calendar</span></a>

            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
        <div class="main-content">
            <div class="header">
                <h2>Welcome, <?php echo htmlspecialchars($userNameDisplay !== '' ? $userNameDisplay : $userNameRaw); ?>!</h2>
            </div>
            <?php renderTermSwitcher($academicContext, ['school_name' => $adminSchool]); ?>
            <div class="container">
                <div class="card">
                    <div class="card-header"><h5>Assign Subject and Class to Teacher</h5></div>
                    <div class="card-body">
                        <?php if ($error) { echo '<p style="color: #d32f2f; font-weight: bold;">' . htmlspecialchars($error) . '</p>'; } ?>
                        <?php if ($success) { echo '<p style="color: #388e3c; font-weight: bold;">' . htmlspecialchars($success) . '</p>'; } ?>
                        <form method="POST">
                            
<div class="form-group">
    <label>Which School/Department is this assignment for? (e.g., STME)</label>
    <select id="school" name="school" required>
        <option value="">-- Select School --</option>
        <option value="STME">STME</option>
        <option value="SOL">SOL</option>
        <option value="SPTM">SPTM</option>
        <option value="SBM">SBM</option>
        <option value="SOC">SOC</option>
    </select>
</div>
<div class="form-group">
    <label>Which Teacher should be assigned? (select a teacher after choosing School)</label>
    <select id="teacher_id" name="teacher_id" required>
        <option value="">-- Select School First --</option>
    </select>
</div>
                            <div class="form-group">
                                <label>Which Subject should this teacher take? (e.g., Calculus)</label>
                                <select name="subject_id" required>
                                    <option value="">-- Select Subject --</option>
                                    <?php foreach ($subjects as $subject) { ?>
                                        <option value="<?php echo $subject['id']; ?>" <?php echo ($selected_subject_id === (int)$subject['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subject['subject_name'] . ' (Sem ' . $subject['semester'] . ' - ' . $subject['school'] . ')'); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group" id="subject-components-wrapper" style="display:none;">
                                <label>ICA components already configured for this subject</label>
                                <div class="ica-components-wrapper">
                                    <div id="subject-components" class="ica-components-placeholder">Select a subject to view its ICA components.</div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Which Class should be assigned? (Class sets Semester & Department)</label>
                                <select name="class_id" id="class_id" required>
                                    <option value="">-- Select Class / Division --</option>
                                    <?php foreach ($class_option_map as $value => $meta): ?>
                                        <option value="<?php echo htmlspecialchars($value); ?>" <?php echo ($selected_class_option === $value) ? 'selected' : ''; ?>><?php echo htmlspecialchars($meta['label']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn">Assign</button>
                        </form>
                    </div>
                </div>
                <?php if (!empty($teacher_card_data)): ?>
                    <div class="card" id="teacher-overview-card">
                        <div class="card-header"><h5 style="margin:0;">Teacher Overview</h5></div>
                        <div class="card-body">
                            <div class="teacher-card-grid" style="display:flex; flex-wrap:wrap; gap:12px;">
                                <?php foreach ($teacher_card_data as $card): ?>
                                    <button type="button" class="teacher-card" data-teacher-name="<?php echo htmlspecialchars($card['teacher_name']); ?>" style="flex:1 1 240px; min-width:240px; border:1px solid #ddd; border-radius:8px; padding:16px; background:#f9f9f9; text-align:left; cursor:pointer;">
                                        <div style="font-weight:600; font-size:1rem; color:#a6192e;">
                                            <?php echo htmlspecialchars($card['teacher_name']); ?>
                                        </div>
                                        <div class="teacher-card-meta">
                                            <?php echo (int)$card['subject_count']; ?> Subjects • <?php echo (int)$card['class_count']; ?> Classes
                                        </div>
                                        <div class="teacher-card-details">
                                            <?php if (!empty($card['assignments'])): ?>
                                                <div class="teacher-card-details-header">
                                                    <span>Subject</span>
                                                    <span>Class / Division</span>
                                                </div>
                                                <?php foreach ($card['assignments'] as $assignment): ?>
                                                    <div class="teacher-card-details-row">
                                                        <span><?php echo htmlspecialchars($assignment['subject']); ?></span>
                                                        <span><?php echo htmlspecialchars($assignment['class']); ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="teacher-card-details-empty">No assignments yet</div>
                                            <?php endif; ?>
                                        </div>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($assignments_flat)): ?>
                    <div class="card" id="assignment-management-card" style="margin-top:20px;">
                        <div class="card-header"><h5 style="margin:0;">Manage Existing Assignments</h5></div>
                        <div class="card-body" style="overflow-x:auto;">
                            <div id="assignment-filters" style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px;">
                                <select id="assignment-school-filter" class="filter-control" style="flex:1 1 220px; min-width:200px;">
                                    <option value="">All Schools</option>
                                    <?php foreach ($assignment_schools as $school_option): ?>
                                        <option value="<?php echo htmlspecialchars($school_option); ?>"><?php echo htmlspecialchars($school_option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select id="assignment-semester-filter" class="filter-control" style="flex:1 1 220px; min-width:200px;">
                                    <option value="">All Semesters / Years</option>
                                    <?php foreach ($assignment_semesters as $semester_option): ?>
                                        <option value="<?php echo htmlspecialchars($semester_option); ?>">Semester <?php echo htmlspecialchars($semester_option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php
                                    $hasClassOptions = !empty($assignment_class_labels);
                                    $classFilterLabel = $hasClassOptions ? 'All Classes / Divisions' : 'No Classes Available';
                                ?>
                                <select id="assignment-class-filter" class="filter-control" style="flex:1 1 240px; min-width:220px;" <?php echo $hasClassOptions ? '' : 'disabled'; ?>>
                                    <option value="" data-default-text="All Classes / Divisions" data-empty-text="No Classes Available"><?php echo htmlspecialchars($classFilterLabel); ?></option>
                                    <?php foreach ($assignment_class_labels as $class_option): ?>
                                        <option value="<?php echo htmlspecialchars($class_option); ?>"><?php echo htmlspecialchars($class_option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" id="assignment-search" class="filter-control" placeholder="Search by teacher, subject, or class" style="flex:1 1 260px; min-width:240px;">
                            </div>
                            <table id="assignments-table" style="width:100%; border-collapse:collapse;">
                                <thead>
                                    <tr>
                                        <th style="text-align:left; padding:8px; border-bottom:1px solid #ddd;">Teacher</th>
                                        <th style="text-align:left; padding:8px; border-bottom:1px solid #ddd;">Subject</th>
                                        <th style="text-align:left; padding:8px; border-bottom:1px solid #ddd;">Class / Division</th>
                                        <th style="text-align:left; padding:8px; border-bottom:1px solid #ddd;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignments_flat as $assignment_row): ?>
                                        <?php
                                            $row_class_label = isset($assignment_row['class_label']) ? (string)$assignment_row['class_label'] : '';
                                            $normalized_class_label = strtolower($row_class_label);
                                            $normalized_teacher = strtolower((string)$assignment_row['teacher_name']);
                                            $normalized_subject = strtolower((string)$assignment_row['subject_name']);
                                        ?>
                                        <tr class="assignment-row" data-assignment-id="<?php echo $assignment_row['assignment_id']; ?>" data-teacher-id="<?php echo $assignment_row['teacher_id']; ?>" data-subject-id="<?php echo $assignment_row['subject_id']; ?>" data-class-option="<?php echo htmlspecialchars($assignment_row['class_option']); ?>" data-school="<?php echo htmlspecialchars(strtolower($assignment_row['school'])); ?>" data-semester="<?php echo htmlspecialchars(strtolower((string)$assignment_row['semester'])); ?>" data-class-label="<?php echo htmlspecialchars($normalized_class_label); ?>" data-class-label-display="<?php echo htmlspecialchars($assignment_row['class_label']); ?>" data-teacher-name="<?php echo htmlspecialchars($normalized_teacher); ?>" data-subject-name="<?php echo htmlspecialchars($normalized_subject); ?>">
                                            <td class="assignment-teacher-cell" style="padding:8px; border-bottom:1px solid #f0f0f0;">
                                                <?php echo htmlspecialchars($assignment_row['teacher_name']); ?>
                                            </td>
                                            <td class="assignment-subject-cell" style="padding:8px; border-bottom:1px solid #f0f0f0;">
                                                <?php echo htmlspecialchars($assignment_row['subject_name']); ?>
                                            </td>
                                            <td class="assignment-class-cell" style="padding:8px; border-bottom:1px solid #f0f0f0;">
                                                <?php echo htmlspecialchars($assignment_row['class_label']); ?>
                                            </td>
                                            <td class="assignment-actions-cell" style="padding:8px; border-bottom:1px solid #f0f0f0; white-space:nowrap;">
                                                <button type="button" class="table-action-btn assignment-edit-btn">Edit</button>
                                                <button type="button" class="table-action-btn assignment-save-btn" style="display:none; background-color:#28a745;">Save</button>
                                                <button type="button" class="table-action-btn assignment-cancel-btn" style="display:none; background-color:#6c757d;">Cancel</button>
                                                <button type="button" class="table-action-btn assignment-delete-btn" style="background-color:#dc3545;">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <p id="assignments-empty-message" style="display:none; margin-top:12px; color:#555;">No assignments match the selected filters.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        }
        if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');

        const teacherSelectSearch = (function() {
            let buffer = '';
            let resetTimer = null;

            function normalizeLabel(label) {
                return String(label || '')
                    .toLowerCase()
                    .replace(/^(dr|prof|mr|mrs|ms)\.?\s*/, '')
                    .replace(/\s+/g, ' ')
                    .trim();
            }

            function updateOptionMetadata(select) {
                if (!select) {
                    return;
                }
                Array.from(select.options).forEach(function(option) {
                    option.dataset.search = normalizeLabel(option.textContent);
                });
            }

            function clearBuffer() {
                buffer = '';
                if (resetTimer) {
                    clearTimeout(resetTimer);
                    resetTimer = null;
                }
            }

            function focusMatch(select) {
                const term = buffer.replace(/\s+/g, ' ').trim();
                if (!term) {
                    return;
                }
                const match = Array.from(select.options).find(function(option) {
                    if (!option.value) {
                        return false;
                    }
                    const target = option.dataset.search || normalizeLabel(option.textContent);
                    return target.startsWith(term);
                });
                if (match) {
                    const valueChanged = select.value !== match.value;
                    select.value = match.value;
                    if (valueChanged) {
                        const changeEvent = new Event('change', { bubbles: true });
                        select.dispatchEvent(changeEvent);
                    }
                }
            }

            function handleKeydown(event) {
                const select = event.currentTarget;
                if (!select) {
                    return;
                }

                if (event.ctrlKey || event.altKey || event.metaKey) {
                    clearBuffer();
                    return;
                }

                if (event.key === 'Backspace') {
                    if (buffer.length > 0) {
                        buffer = buffer.slice(0, -1);
                        event.preventDefault();
                        focusMatch(select);
                    }
                    return;
                }

                if (event.key === 'Escape') {
                    clearBuffer();
                    return;
                }

                if (event.key.length === 1 && !event.repeat) {
                    buffer += event.key.toLowerCase();
                    event.preventDefault();
                    focusMatch(select);
                    if (resetTimer) {
                        clearTimeout(resetTimer);
                    }
                    resetTimer = setTimeout(clearBuffer, 700);
                }
            }

            function attach(select) {
                if (!select || select.dataset.searchEnhanced === '1') {
                    return;
                }
                updateOptionMetadata(select);
                select.addEventListener('keydown', handleKeydown);
                select.addEventListener('blur', clearBuffer);
                select.dataset.searchEnhanced = '1';
            }

            function refresh(select) {
                updateOptionMetadata(select);
                clearBuffer();
            }

            return {
                attach: attach,
                refresh: refresh
            };
        })();

        const assignmentTeacherOptions = <?php echo json_encode($teachers_for_edit, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        const assignmentSubjectOptions = <?php echo json_encode($subjects_meta_for_js, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        const assignmentClassOptions = <?php echo json_encode($class_options_for_js, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

        const teacherSelectElement = document.getElementById('teacher_id');
        teacherSelectSearch.attach(teacherSelectElement);

        document.getElementById('school').addEventListener('change', function() {
            const school = this.value;
            const teacherSelect = document.getElementById('teacher_id');
            
            teacherSelect.innerHTML = '<option value="">Loading...</option>';

            if (school) {
                fetch('get_teachers_by_school.php?school=' + school)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok. Status: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        teacherSelect.innerHTML = '<option value="">-- Select Teacher --</option>';
                        if (data.length > 0) {
                            data.forEach(teacher => {
                                const option = document.createElement('option');
                                option.value = teacher.id;
                                option.textContent = teacher.name;
                                teacherSelect.appendChild(option);
                            });
                            teacherSelectSearch.refresh(teacherSelect);
                        } else {
                             teacherSelect.innerHTML = '<option value="">No teachers found in this school</option>';
                            teacherSelectSearch.refresh(teacherSelect);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching teachers:', error);
                        teacherSelect.innerHTML = '<option value="">Error loading teachers</option>';
                        teacherSelectSearch.refresh(teacherSelect);
                        alert('An error occurred while fetching the teacher list. Make sure the get_teachers_by_school.php file exists and has no errors.');
                    });
            } else {
                teacherSelect.innerHTML = '<option value="">-- Select School First --</option>';
                teacherSelectSearch.refresh(teacherSelect);
            }
        });

        (function setupClassOptionsBySubject() {
            const subjectSelect = document.querySelector('select[name="subject_id"]');
            const classSelect = document.getElementById('class_id');
            if (!subjectSelect || !classSelect) {
                return;
            }

            const classOptions = <?php echo json_encode($class_options_for_js, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
            const subjectsMeta = <?php echo json_encode($subjects_meta_for_js, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

            function findSubjectMeta(subjectId) {
                return subjectsMeta.find(function(subject) {
                    return subject.id === subjectId;
                }) || null;
            }

            function rebuildOptions(options, preselectValue) {
                classSelect.innerHTML = '<option value="">-- Select Class / Division --</option>';
                let matchedSelection = false;
                options.forEach(function(option) {
                    const optElement = document.createElement('option');
                    optElement.value = option.value;
                    optElement.textContent = option.label;
                    if (preselectValue && preselectValue === option.value) {
                        optElement.selected = true;
                        matchedSelection = true;
                    }
                    classSelect.appendChild(optElement);
                });
                if (preselectValue && !matchedSelection) {
                    classSelect.value = '';
                }
            }

            function refreshClassOptions() {
                const selectedSubjectId = parseInt(subjectSelect.value, 10);
                const currentSelection = classSelect.value;
                let filteredOptions = classOptions;

                if (!Number.isNaN(selectedSubjectId)) {
                    const subjectMeta = findSubjectMeta(selectedSubjectId);
                    if (subjectMeta) {
                        filteredOptions = classOptions.filter(function(option) {
                            return option.school === subjectMeta.school && String(option.semester) === String(subjectMeta.semester);
                        });
                    }
                }

                if (filteredOptions.length === 0) {
                    filteredOptions = classOptions;
                }

                const shouldKeepSelection = currentSelection && filteredOptions.some(function(option) {
                    return option.value === currentSelection;
                });

                rebuildOptions(filteredOptions, shouldKeepSelection ? currentSelection : '');
            }

            refreshClassOptions();
            subjectSelect.addEventListener('change', refreshClassOptions);
        })();

        (function setupSubjectComponentsPreview() {
            const subjectSelect = document.querySelector('select[name="subject_id"]');
            const wrapper = document.getElementById('subject-components-wrapper');
            const container = document.getElementById('subject-components');
            if (!subjectSelect || !wrapper || !container) {
                return;
            }

            let latestRequestId = 0;

            function renderPlaceholder(message, isError) {
                container.className = 'ica-components-placeholder' + (isError ? ' error' : '');
                container.textContent = message;
            }

            function renderTable(components) {
                if (!Array.isArray(components) || components.length === 0) {
                    renderPlaceholder('No ICA components have been configured yet for this subject.', false);
                    return;
                }

                const table = document.createElement('table');
                table.className = 'ica-components-table';
                const header = document.createElement('thead');
                header.innerHTML = '<tr><th>Component</th><th>Instances</th><th>Marks / Instance</th><th>Total Marks</th><th>Scaled Total</th><th>Configured By</th></tr>';
                const body = document.createElement('tbody');

                components.forEach(function(component) {
                    const row = document.createElement('tr');
                    const teacherLabel = component.teacher_name_display || component.teacher_name || '—';
                    row.innerHTML = '<td>' + (component.component_name || '-') + '</td>' +
                        '<td>' + (component.instances ?? '-') + '</td>' +
                        '<td>' + (component.marks_per_instance ?? '-') + '</td>' +
                        '<td>' + (component.total_marks ?? '-') + '</td>' +
                        '<td>' + (component.scaled_total_marks ?? '-') + '</td>' +
                        '<td>' + teacherLabel + '</td>';
                    body.appendChild(row);
                });

                table.appendChild(header);
                table.appendChild(body);
                container.className = '';
                container.innerHTML = '';
                container.appendChild(table);
            }

            function loadComponents(subjectId) {
                const requestId = ++latestRequestId;
                if (!subjectId) {
                    wrapper.style.display = 'none';
                    renderPlaceholder('Select a subject to view its ICA components.', false);
                    return;
                }

                wrapper.style.display = '';
                renderPlaceholder('Loading ICA components...', false);

                fetch('get_subject_ica_components.php?subject_id=' + encodeURIComponent(subjectId), { cache: 'no-store' })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('Request failed with status ' + response.status);
                        }
                        return response.json();
                    })
                    .then(function(data) {
                        if (requestId !== latestRequestId) {
                            return;
                        }
                        renderTable(data);
                    })
                    .catch(function(error) {
                        console.error('Error loading ICA components:', error);
                        if (requestId === latestRequestId) {
                            renderPlaceholder('Unable to load ICA components at the moment. Please try again later.', true);
                        }
                    });
            }

            subjectSelect.addEventListener('change', function(event) {
                loadComponents(event.target.value);
            });

            if (subjectSelect.value) {
                loadComponents(subjectSelect.value);
            }
        })();

        (function setupAssignmentFilters() {
            const table = document.getElementById('assignments-table');
            const schoolFilter = document.getElementById('assignment-school-filter');
            const semesterFilter = document.getElementById('assignment-semester-filter');
            const classFilter = document.getElementById('assignment-class-filter');
            const searchInput = document.getElementById('assignment-search');
            const emptyMessage = document.getElementById('assignments-empty-message');

            if (!table || !schoolFilter || !semesterFilter || !searchInput) {
                return;
            }

            const placeholderOption = classFilter ? classFilter.options[0] : null;
            const classFilterDefaultText = placeholderOption ? (placeholderOption.dataset.defaultText || placeholderOption.text) : 'All Classes / Divisions';
            const classFilterEmptyText = placeholderOption ? (placeholderOption.dataset.emptyText || 'No Classes Available') : 'No Classes Available';

            const rowSearchCache = new WeakMap();
            let rows = [];

            function refreshRows() {
                rows = Array.from(table.querySelectorAll('tbody tr'));
            }

            function resolveRowSearch(row) {
                if (rowSearchCache.has(row)) {
                    return rowSearchCache.get(row);
                }
                const base = [
                    row.dataset.teacherName || '',
                    row.dataset.subjectName || '',
                    row.dataset.classLabel || ''
                ].join(' ');
                const value = (base + ' ' + (row.textContent || '')).toLowerCase();
                rowSearchCache.set(row, value);
                return value;
            }

            function refreshClassFilterState() {
                if (!classFilter) {
                    return;
                }
                const optionCount = classFilter.options.length - 1;
                if (optionCount > 0) {
                    classFilter.disabled = false;
                    classFilter.options[0].text = classFilterDefaultText;
                } else {
                    classFilter.disabled = true;
                    classFilter.value = '';
                    classFilter.options[0].text = classFilterEmptyText;
                }
            }

            function updateClassFilterOptions(optionMap, selectedNormalizedValue) {
                if (!classFilter) {
                    return;
                }
                const normalizedSelection = selectedNormalizedValue || '';
                while (classFilter.options.length > 1) {
                    classFilter.remove(1);
                }

                let matchedDisplayValue = '';
                const sorted = Array.from(optionMap.entries()).sort(function(a, b) {
                    return a[1].localeCompare(b[1], undefined, { sensitivity: 'base' });
                });

                sorted.forEach(function(entry) {
                    const normalized = entry[0];
                    const label = entry[1];
                    const option = document.createElement('option');
                    option.value = label;
                    option.textContent = label;
                    classFilter.appendChild(option);
                    if (normalizedSelection && normalized === normalizedSelection) {
                        matchedDisplayValue = label;
                    }
                });

                if (normalizedSelection) {
                    classFilter.value = matchedDisplayValue || '';
                } else {
                    classFilter.value = '';
                }

                refreshClassFilterState();
            }

            function applyFilters(triggeredByClassFilter) {
                const schoolValue = (schoolFilter.value || '').toLowerCase();
                const semesterValue = (semesterFilter.value || '').toLowerCase();
                const classValue = (classFilter && classFilter.value ? classFilter.value.toLowerCase() : '');
                const searchValue = (searchInput.value || '').toLowerCase().trim();

                const optionMap = new Map();
                let visibleCount = 0;

                rows.forEach(function(row) {
                    const rowSchool = (row.dataset.school || '').toLowerCase();
                    const rowSemester = (row.dataset.semester || '').toLowerCase();
                    const rowClass = (row.dataset.classLabel || '').toLowerCase();

                    let matchesCore = true;

                    if (schoolValue && rowSchool !== schoolValue) {
                        matchesCore = false;
                    }

                    if (matchesCore && semesterValue && rowSemester !== semesterValue) {
                        matchesCore = false;
                    }

                    if (matchesCore && searchValue) {
                        const haystack = resolveRowSearch(row);
                        if (!haystack.includes(searchValue)) {
                            matchesCore = false;
                        }
                    }

                    if (matchesCore) {
                        const normalized = rowClass.trim();
                        const display = (row.dataset.classLabelDisplay || '').trim();
                        if (normalized && normalized !== 'not linked yet' && !optionMap.has(normalized)) {
                            optionMap.set(normalized, display || normalized);
                        }
                    }

                    let matchesFinal = matchesCore;

                    if (matchesFinal && classValue && rowClass !== classValue) {
                        matchesFinal = false;
                    }

                    row.style.display = matchesFinal ? '' : 'none';
                    if (matchesFinal) {
                        visibleCount += 1;
                    }
                });

                if (!triggeredByClassFilter) {
                    if (!searchValue && Array.isArray(assignmentClassOptions)) {
                        assignmentClassOptions.forEach(function(option) {
                            if (!option || !option.label) {
                                return;
                            }
                            const normalizedLabel = option.label.toLowerCase();
                            if (!normalizedLabel || normalizedLabel === 'not linked yet') {
                                return;
                            }
                            if (schoolValue) {
                                const optionSchool = (option.school || '').toString().toLowerCase();
                                if (optionSchool !== schoolValue) {
                                    return;
                                }
                            }
                            if (semesterValue) {
                                const optionSemester = (option.semester || '').toString().toLowerCase();
                                if (optionSemester !== semesterValue) {
                                    return;
                                }
                            }
                            if (!optionMap.has(normalizedLabel)) {
                                optionMap.set(normalizedLabel, option.label);
                            }
                        });
                    }

                    updateClassFilterOptions(optionMap, classValue);
                } else {
                    refreshClassFilterState();
                }

                if (emptyMessage) {
                    emptyMessage.style.display = visibleCount === 0 ? '' : 'none';
                }
            }

            function applyFiltersFromNonClass() {
                applyFilters(false);
            }

            function applyFiltersFromClass() {
                applyFilters(true);
            }

            schoolFilter.addEventListener('change', applyFiltersFromNonClass);
            semesterFilter.addEventListener('change', applyFiltersFromNonClass);
            if (classFilter) {
                classFilter.addEventListener('change', applyFiltersFromClass);
            }
            searchInput.addEventListener('input', applyFiltersFromNonClass);

            table.addEventListener('assignmentRowUpdated', function(event) {
                const updatedRow = event && event.detail ? event.detail.row : null;
                if (updatedRow) {
                    rowSearchCache.delete(updatedRow);
                }
                refreshRows();
                applyFilters(false);
            });

            table.addEventListener('assignmentRowRemoved', function(event) {
                const removedRow = event && event.detail ? event.detail.row : null;
                if (removedRow) {
                    rowSearchCache.delete(removedRow);
                }
                refreshRows();
                applyFilters(false);
            });

            refreshRows();
            applyFilters(false);
        })();

            (function enableAssignmentInlineEditing() {
                const table = document.getElementById('assignments-table');
                if (!table) {
                    return;
                }

                const subjectMetaMap = Object.create(null);
                (assignmentSubjectOptions || []).forEach(function(meta) {
                    if (!meta) {
                        return;
                    }
                    subjectMetaMap[String(meta.id)] = meta;
                });

                function escapeHtml(value) {
                    return String(value ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function buildTeacherOptions(selectedId) {
                    let html = '<option value="">-- Select Teacher --</option>';
                    (assignmentTeacherOptions || []).forEach(function(teacher) {
                        if (!teacher) {
                            return;
                        }
                        const selected = Number(teacher.id) === Number(selectedId) ? ' selected' : '';
                        html += '<option value="' + teacher.id + '"' + selected + '>' + escapeHtml(teacher.name) + '</option>';
                    });
                    return html;
                }

                function buildSubjectOptions(selectedId) {
                    let html = '<option value="">-- Select Subject --</option>';
                    (assignmentSubjectOptions || []).forEach(function(subject) {
                        if (!subject) {
                            return;
                        }
                        const selected = Number(subject.id) === Number(selectedId) ? ' selected' : '';
                        html += '<option value="' + subject.id + '"' + selected + '>' + escapeHtml(subject.name) + '</option>';
                    });
                    return html;
                }

                function buildClassOptionsForSubject(subjectId, selectedValue) {
                    let filtered = assignmentClassOptions || [];
                    const subjectMeta = subjectMetaMap[String(subjectId)];
                    if (subjectMeta && subjectMeta.school && subjectMeta.semester) {
                        filtered = filtered.filter(function(option) {
                            return option && option.school === subjectMeta.school && String(option.semester) === String(subjectMeta.semester);
                        });
                    }
                    if (!filtered.length) {
                        filtered = assignmentClassOptions || [];
                    }
                    let html = '<option value="">-- Select Class / Division --</option>';
                    filtered.forEach(function(option) {
                        if (!option) {
                            return;
                        }
                        const selected = option.value === selectedValue ? ' selected' : '';
                        html += '<option value="' + option.value + '"' + selected + '>' + escapeHtml(option.label) + '</option>';
                    });
                    return html;
                }

                function showError(row, message) {
                    let error = row.querySelector('.assignment-inline-error');
                    if (!error) {
                        error = document.createElement('div');
                        error.className = 'assignment-inline-error';
                        error.style.color = '#b10024';
                        error.style.fontSize = '0.85rem';
                        error.style.marginTop = '6px';
                        row.querySelector('.assignment-actions-cell').appendChild(error);
                    }
                    error.textContent = message;
                    error.style.display = message ? '' : 'none';
                }

                function clearError(row) {
                    showError(row, '');
                }

                function exitEditMode(row, restore) {
                    if (!row.classList.contains('editing')) {
                        return;
                    }
                    const editBtn = row.querySelector('.assignment-edit-btn');
                    const saveBtn = row.querySelector('.assignment-save-btn');
                    const cancelBtn = row.querySelector('.assignment-cancel-btn');
                    const deleteBtn = row.querySelector('.assignment-delete-btn');
                    if (restore && row._originalCells) {
                        row.querySelector('.assignment-teacher-cell').innerHTML = row._originalCells.teacher;
                        row.querySelector('.assignment-subject-cell').innerHTML = row._originalCells.subject;
                        row.querySelector('.assignment-class-cell').innerHTML = row._originalCells.classValue;
                    }
                    if (editBtn) editBtn.style.display = '';
                    if (saveBtn) {
                        saveBtn.disabled = false;
                        saveBtn.style.display = 'none';
                    }
                    if (cancelBtn) {
                        cancelBtn.disabled = false;
                        cancelBtn.style.display = 'none';
                    }
                    if (deleteBtn) {
                        deleteBtn.disabled = false;
                        deleteBtn.style.display = '';
                    }
                    row.classList.remove('editing');
                    clearError(row);
                    delete row._originalCells;
                }

                function enterEditMode(row) {
                    if (row.classList.contains('editing')) {
                        return;
                    }

                    const teacherCell = row.querySelector('.assignment-teacher-cell');
                    const subjectCell = row.querySelector('.assignment-subject-cell');
                    const classCell = row.querySelector('.assignment-class-cell');
                    const editBtn = row.querySelector('.assignment-edit-btn');
                    const saveBtn = row.querySelector('.assignment-save-btn');
                    const cancelBtn = row.querySelector('.assignment-cancel-btn');
                    const deleteBtn = row.querySelector('.assignment-delete-btn');

                    row._originalCells = {
                        teacher: teacherCell.innerHTML,
                        subject: subjectCell.innerHTML,
                        classValue: classCell.innerHTML
                    };

                    const teacherId = row.dataset.teacherId || '';
                    const subjectId = row.dataset.subjectId || '';
                    const classOption = row.dataset.classOption || '';

                    teacherCell.innerHTML = '<select class="assignment-select assignment-teacher-select">' + buildTeacherOptions(teacherId) + '</select>';
                    subjectCell.innerHTML = '<select class="assignment-select assignment-subject-select">' + buildSubjectOptions(subjectId) + '</select>';
                    classCell.innerHTML = '<select class="assignment-select assignment-class-select">' + buildClassOptionsForSubject(subjectId, classOption) + '</select>';

                    const subjectSelect = subjectCell.querySelector('.assignment-subject-select');
                    const classSelect = classCell.querySelector('.assignment-class-select');
                    if (subjectSelect && classSelect) {
                        subjectSelect.addEventListener('change', function() {
                            classSelect.innerHTML = buildClassOptionsForSubject(subjectSelect.value, classSelect.value);
                        });
                    }

                    if (editBtn) editBtn.style.display = 'none';
                    if (saveBtn) saveBtn.style.display = '';
                    if (cancelBtn) cancelBtn.style.display = '';
                    if (deleteBtn) {
                        deleteBtn.disabled = true;
                        deleteBtn.style.display = 'none';
                    }
                    row.classList.add('editing');
                    clearError(row);
                }

                function applyRow(row, payload) {
                    const teacherCell = row.querySelector('.assignment-teacher-cell');
                    const subjectCell = row.querySelector('.assignment-subject-cell');
                    const classCell = row.querySelector('.assignment-class-cell');

                    teacherCell.textContent = payload.teacher_name;
                    subjectCell.textContent = payload.subject_name;
                    classCell.textContent = payload.class_label;

                    row.dataset.teacherId = payload.teacher_id;
                    row.dataset.subjectId = payload.subject_id;
                    row.dataset.classOption = payload.class_option;
                    row.dataset.school = (payload.school || '').toString().toLowerCase();
                    row.dataset.semester = (payload.semester || '').toString().toLowerCase();
                    row.dataset.classLabel = ((payload.class_label || '').toString().toLowerCase()).trim();
                    row.dataset.classLabelDisplay = payload.class_label || '';
                    row.dataset.teacherName = ((payload.teacher_name || '').toString().toLowerCase()).trim();
                    row.dataset.subjectName = ((payload.subject_name || '').toString().toLowerCase()).trim();

                    exitEditMode(row, false);

                    const assignmentTable = document.getElementById('assignments-table');
                    if (assignmentTable && typeof window.CustomEvent === 'function') {
                        assignmentTable.dispatchEvent(new CustomEvent('assignmentRowUpdated', {
                            detail: { row: row }
                        }));
                    }
                }

                function saveRow(row) {
                    const assignmentId = parseInt(row.dataset.assignmentId || '0', 10);
                    if (!assignmentId) {
                        return;
                    }

                    const teacherSelect = row.querySelector('.assignment-teacher-select');
                    const subjectSelect = row.querySelector('.assignment-subject-select');
                    const classSelect = row.querySelector('.assignment-class-select');
                    const saveBtn = row.querySelector('.assignment-save-btn');

                    if (!teacherSelect || !subjectSelect || !classSelect) {
                        showError(row, 'Unable to save changes at the moment.');
                        return;
                    }

                    const teacherId = parseInt(teacherSelect.value || '0', 10);
                    const subjectId = parseInt(subjectSelect.value || '0', 10);
                    const classOption = classSelect.value;

                    if (!teacherId) {
                        showError(row, 'Please pick a teacher.');
                        return;
                    }
                    if (!subjectId) {
                        showError(row, 'Please choose a subject.');
                        return;
                    }
                    if (!classOption) {
                        showError(row, 'Please pick a class / division.');
                        return;
                    }

                    clearError(row);
                    if (saveBtn) {
                        saveBtn.disabled = true;
                    }

                    const formData = new FormData();
                    formData.append('action', 'update_assignment_inline');
                    formData.append('assignment_id', assignmentId);
                    formData.append('teacher_id', teacherId);
                    formData.append('subject_id', subjectId);
                    formData.append('class_option', classOption);

                    fetch('assign_teachers.php', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('Request failed with status ' + response.status);
                        }
                        return response.json();
                    })
                    .then(function(payload) {
                        if (!payload || payload.status !== 'ok') {
                            throw new Error(payload && payload.message ? payload.message : 'Unknown error.');
                        }
                        applyRow(row, payload.data);
                    })
                    .catch(function(error) {
                        console.error('Failed to update assignment:', error);
                        if (saveBtn) {
                            saveBtn.disabled = false;
                        }
                        showError(row, error.message || 'Unable to save assignment right now.');
                    });
                }

                function deleteRow(row) {
                    const assignmentId = parseInt(row.dataset.assignmentId || '0', 10);
                    if (!assignmentId) {
                        return;
                    }

                    const deleteBtn = row.querySelector('.assignment-delete-btn');
                    const teacherCell = row.querySelector('.assignment-teacher-cell');
                    const subjectCell = row.querySelector('.assignment-subject-cell');
                    const classCell = row.querySelector('.assignment-class-cell');
                    const teacherLabel = teacherCell ? teacherCell.textContent.trim() : '';
                    const subjectLabel = subjectCell ? subjectCell.textContent.trim() : '';
                    const classLabel = classCell ? classCell.textContent.trim() : '';

                    const confirmSegments = [];
                    if (teacherLabel) {
                        confirmSegments.push('"' + teacherLabel + '"');
                    }
                    if (subjectLabel) {
                        confirmSegments.push('"' + subjectLabel + '"');
                    }
                    const hasSpecificClass = classLabel && classLabel.toLowerCase() !== 'not linked yet';
                    const confirmMessage = confirmSegments.length
                        ? 'Remove the assignment linking ' + confirmSegments.join(' to ') + (hasSpecificClass ? ' for ' + classLabel + '?' : '?')
                        : 'Remove this teacher assignment?';

                    if (!window.confirm(confirmMessage)) {
                        return;
                    }

                    clearError(row);
                    if (deleteBtn) {
                        deleteBtn.disabled = true;
                    }

                    const formData = new FormData();
                    formData.append('action', 'delete_assignment');
                    formData.append('assignment_id', assignmentId);

                    fetch('assign_teachers.php', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('Request failed with status ' + response.status);
                        }
                        return response.json();
                    })
                    .then(function(payload) {
                        if (!payload || payload.status !== 'ok') {
                            throw new Error(payload && payload.message ? payload.message : 'Unknown error.');
                        }
                        row.dataset.deleted = '1';
                        row.remove();
                        const assignmentTable = document.getElementById('assignments-table');
                        if (assignmentTable && typeof window.CustomEvent === 'function') {
                            assignmentTable.dispatchEvent(new CustomEvent('assignmentRowRemoved', {
                                detail: { row: row }
                            }));
                        }
                        window.alert('Assignment deleted successfully.');
                    })
                    .catch(function(error) {
                        console.error('Failed to delete assignment:', error);
                        showError(row, error.message || 'Unable to delete this assignment right now.');
                        if (deleteBtn) {
                            deleteBtn.disabled = false;
                        }
                    });
                }

                table.addEventListener('click', function(event) {
                    const target = event.target;
                    if (!(target instanceof HTMLElement)) {
                        return;
                    }
                    const row = target.closest('.assignment-row');
                    if (!row) {
                        return;
                    }

                    if (target.classList.contains('assignment-edit-btn')) {
                        enterEditMode(row);
                    } else if (target.classList.contains('assignment-save-btn')) {
                        saveRow(row);
                    } else if (target.classList.contains('assignment-cancel-btn')) {
                        exitEditMode(row, true);
                    } else if (target.classList.contains('assignment-delete-btn')) {
                        deleteRow(row);
                    }
                });
            })();

        (function setupClassSections() {
            const classSelect = document.getElementById('class_id');
            const sectionWrapper = document.getElementById('section-wrapper');
            const sectionSelect = document.getElementById('section_id');
            if (!classSelect || !sectionWrapper || !sectionSelect) {
                return;
            }

            const sectionsMap = <?php echo json_encode($class_sections, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

            function updateSections(classId) {
                const sections = sectionsMap[classId] || [];
                sectionSelect.innerHTML = '<option value="">-- Select Division / Section --</option>';

                if (sections.length > 0) {
                    sections.forEach(function(section) {
                        const option = document.createElement('option');
                        option.value = section.id;
                        option.textContent = section.name;
                        sectionSelect.appendChild(option);
                    });
                    sectionWrapper.style.display = '';
                    sectionSelect.required = true;
                } else {
                    sectionWrapper.style.display = 'none';
                    sectionSelect.required = false;
                    sectionSelect.value = '';
                }
            }

            classSelect.addEventListener('change', function() {
                updateSections(this.value);
            });

            if (classSelect.value) {
                updateSections(classSelect.value);
            }
        })();

        (function setupTeacherCards() {
            const cards = document.querySelectorAll('.teacher-card');
            if (!cards.length) {
                return;
            }

            cards.forEach(function(card) {
                card.addEventListener('click', function() {
                    const wasActive = card.classList.contains('teacher-card-active');
                    cards.forEach(function(otherCard) {
                        otherCard.classList.remove('teacher-card-active');
                    });
                    if (!wasActive) {
                        card.classList.add('teacher-card-active');
                        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
            });
        })();
    </script>
</body>
</html>

