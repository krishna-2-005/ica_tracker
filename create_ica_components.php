<?php
session_start();
include 'db_connect.php';
require_once 'alert_helpers.php';
require_once __DIR__ . '/includes/academic_context.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header('Location: login.php');
    exit;
}

define('ICA_SCALED_TOTAL_LIMIT', 50.0);

function calculate_scaled_total(array $components, string $scaledKey): float {
    $total = 0.0;
    foreach ($components as $component) {
        if (isset($component[$scaledKey])) {
            $total += (float)$component[$scaledKey];
        }
    }
    return $total;
}

function scaled_total_exceeds_limit(float $total): bool {
    return $total > ICA_SCALED_TOTAL_LIMIT + 0.0001;
}

function scaled_total_below_limit(float $total): bool {
    return $total + 0.0001 < ICA_SCALED_TOTAL_LIMIT;
}

$teacher_id = (int)$_SESSION['user_id'];
$teacherNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$teacherNameDisplay = $teacherNameRaw !== '' ? format_person_display($teacherNameRaw) : 'FACULTY';
$error = '';
$success = '';
$existing_components = [];
$existing_course_type = '';
$edit_mode = false;
$edit_subject_id = 0;
$selected_subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : (isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0);
$selected_class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : (isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0);
$available_classes = [];

// Ensure ICA components table has class linkage for multi-class subjects.
$classColumnCheck = mysqli_query($conn, "SHOW COLUMNS FROM ica_components LIKE 'class_id'");
if ($classColumnCheck && mysqli_num_rows($classColumnCheck) === 0) {
    $alterSql = "ALTER TABLE ica_components ADD COLUMN class_id INT NULL AFTER subject_id, ADD KEY idx_class_id (class_id)";
    if (!mysqli_query($conn, $alterSql)) {
        error_log('Failed to add class_id to ica_components: ' . mysqli_error($conn), 3, 'C:\\xampp\\php\\logs\\php_error_log');
    }
}
if ($classColumnCheck) {
    mysqli_free_result($classColumnCheck);
}

function load_components_for_context(mysqli $conn, int $teacher_id, int $subject_id, int $class_id, array &$existing_components, string &$existing_course_type, int &$selected_class_id): void {
    $existing_components = [];
    if ($subject_id <= 0) {
        return;
    }

    $base_sql = "SELECT * FROM ica_components WHERE teacher_id = ? AND subject_id = ?";
    $base_types = "ii";
    $base_params = [$teacher_id, $subject_id];

    $result_sets = [];

    if ($class_id > 0) {
        $class_sql = $base_sql . " AND class_id = ?";
        $class_types = $base_types . 'i';
        $class_params = array_merge($base_params, [$class_id]);
        $result_sets[] = [$class_sql, $class_types, $class_params, true];
    }

    $result_sets[] = [$base_sql . " AND (class_id IS NULL OR class_id = 0)", $base_types, $base_params, false];

    foreach ($result_sets as [$sql, $types, $params, $is_class_specific]) {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            continue;
        }

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
            $rows = [];
            while ($row = mysqli_fetch_assoc($result)) {
                if ($existing_course_type === '' && !empty($row['course_type'])) {
                    $existing_course_type = $row['course_type'];
                }
                if ($is_class_specific && isset($row['class_id']) && (int)$row['class_id'] > 0) {
                    $selected_class_id = (int)$row['class_id'];
                }
                $rows[] = $row;
            }
            mysqli_free_result($result);

            if (!empty($rows)) {
                if ($is_class_specific) {
                    $existing_components = $rows;
                    mysqli_stmt_close($stmt);
                    return;
                }
                if (empty($existing_components)) {
                    $existing_components = $rows;
                }
            }
        }
        mysqli_stmt_close($stmt);
    }
}

$teacherSchool = '';
$stmtTeacherSchool = mysqli_prepare($conn, "SELECT school FROM users WHERE id = ?");
if ($stmtTeacherSchool) {
    mysqli_stmt_bind_param($stmtTeacherSchool, "i", $teacher_id);
    mysqli_stmt_execute($stmtTeacherSchool);
    $resTeacherSchool = mysqli_stmt_get_result($stmtTeacherSchool);
    if ($resTeacherSchool && ($rowSchool = mysqli_fetch_assoc($resTeacherSchool))) {
        $teacherSchool = trim((string)($rowSchool['school'] ?? ''));
        mysqli_free_result($resTeacherSchool);
    }
    mysqli_stmt_close($stmtTeacherSchool);
}

$academicContext = resolveAcademicContext($conn, [
    'school_name' => $teacherSchool
]);
$activeTerm = $academicContext['active'] ?? null;
$activeTermId = $activeTerm && isset($activeTerm['id']) ? (int)$activeTerm['id'] : 0;
$termDateFilter = $academicContext['date_filter'] ?? null;
$termStartBound = isset($termDateFilter['start']) ? $termDateFilter['start'] . ' 00:00:00' : null;
$termEndBound = isset($termDateFilter['end']) ? $termDateFilter['end'] . ' 23:59:59' : null;

// Check if in edit mode
if (isset($_GET['edit']) && isset($_GET['subject_id'])) {
    $edit_mode = true;
    $edit_subject_id = (int)$_GET['subject_id'];
    $selected_subject_id = $edit_subject_id;

    if ($selected_class_id <= 0 && !empty($available_classes)) {
        $selected_class_id = (int)$available_classes[0]['id'];
    }

    load_components_for_context($conn, $teacher_id, $edit_subject_id, $selected_class_id, $existing_components, $existing_course_type, $selected_class_id);
}

// Fetch subjects assigned to the teacher using teacher_subject_assignments
$subjects_query = "
    SELECT DISTINCT s.id, s.subject_name, COALESCE(sd.subject_type, 'regular') AS subject_type
    FROM teacher_subject_assignments tsa
    JOIN subjects s ON s.id = tsa.subject_id
    LEFT JOIN subject_details sd ON sd.subject_id = s.id
    JOIN classes c ON c.id = tsa.class_id
    WHERE tsa.teacher_id = ?
";
if ($activeTermId > 0) {
    $subjects_query .= " AND c.academic_term_id = ?";
}
$subjects_query .= "
    ORDER BY s.subject_name
";
$stmt_subjects = mysqli_prepare($conn, $subjects_query);
if (!$stmt_subjects) {
    error_log("Prepare failed for subjects_query: " . mysqli_error($conn), 3, 'C:\xampp\php\logs\php_error_log');
    $error = "Failed to load subjects. Please try again.";
} else {
    if ($activeTermId > 0) {
        mysqli_stmt_bind_param($stmt_subjects, "ii", $teacher_id, $activeTermId);
    } else {
        mysqli_stmt_bind_param($stmt_subjects, "i", $teacher_id);
    }
    mysqli_stmt_execute($stmt_subjects);
    $subjects_result = mysqli_stmt_get_result($stmt_subjects);
}

if ($selected_subject_id > 0) {
    $classes_sql = "SELECT c.id,
                           c.class_name,
                           c.semester,
                           c.school,
                           COALESCE(GROUP_CONCAT(DISTINCT sec.section_name ORDER BY sec.section_name SEPARATOR '/'), '') AS divisions
                    FROM classes c
                    JOIN teacher_subject_assignments tsa ON tsa.class_id = c.id
                    LEFT JOIN sections sec ON sec.id = tsa.section_id
                    WHERE tsa.teacher_id = ? AND tsa.subject_id = ?";
    if ($activeTermId > 0) {
        $classes_sql .= " AND c.academic_term_id = ?";
    }
    $classes_sql .= "
                    GROUP BY c.id, c.class_name, c.semester, c.school
                    ORDER BY c.class_name";
    $stmt_classes = mysqli_prepare($conn, $classes_sql);
    if ($stmt_classes) {
        if ($activeTermId > 0) {
            mysqli_stmt_bind_param($stmt_classes, "iii", $teacher_id, $selected_subject_id, $activeTermId);
        } else {
            mysqli_stmt_bind_param($stmt_classes, "ii", $teacher_id, $selected_subject_id);
        }
        mysqli_stmt_execute($stmt_classes);
        $class_res = mysqli_stmt_get_result($stmt_classes);
        if ($class_res) {
            while ($class_row = mysqli_fetch_assoc($class_res)) {
                $class_row['class_name'] = format_class_label($class_row['class_name'] ?? '', $class_row['divisions'] ?? '', $class_row['semester'] ?? '', $class_row['school'] ?? '');
                $available_classes[] = $class_row;
            }
            mysqli_free_result($class_res);
        }
        mysqli_stmt_close($stmt_classes);
    }

    if (!empty($available_classes) && $selected_class_id > 0) {
        $validClassIds = array_map('intval', array_column($available_classes, 'id'));
        if (!in_array($selected_class_id, $validClassIds, true)) {
            $selected_class_id = $validClassIds[0] ?? 0;
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['copy_components'])) {
        $copy_subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
        $source_class_id = isset($_POST['source_class_id']) ? (int)$_POST['source_class_id'] : 0;
        $target_class_id = isset($_POST['target_class_id']) ? (int)$_POST['target_class_id'] : 0;

        $selected_subject_id = $copy_subject_id;
        $selected_class_id = $target_class_id;
        $edit_mode = true;
        $edit_subject_id = $copy_subject_id;

        if ($copy_subject_id <= 0 || $target_class_id <= 0) {
            $error = 'Please select both source and target classes before copying ICA components.';
        } else {
            $copy_sql = "SELECT course_type, component_name, instances, marks_per_instance, total_marks, scaled_total_marks FROM ica_components WHERE teacher_id = ? AND subject_id = ?";
            $copy_types = "ii";
            $copy_params = [$teacher_id, $copy_subject_id];
            if ($source_class_id > 0) {
                $copy_sql .= " AND class_id = ?";
                $copy_types .= "i";
                $copy_params[] = $source_class_id;
            } else {
                $copy_sql .= " AND (class_id IS NULL OR class_id = 0)";
            }

            $stmt_copy = mysqli_prepare($conn, $copy_sql);
            if (!$stmt_copy) {
                $error = 'Failed to prepare copy request. Please try again.';
            } else {
                mysqli_stmt_bind_param($stmt_copy, $copy_types, ...$copy_params);
                mysqli_stmt_execute($stmt_copy);
                $copy_res = mysqli_stmt_get_result($stmt_copy);
                $components_to_copy = [];
                if ($copy_res) {
                    while ($row = mysqli_fetch_assoc($copy_res)) {
                        $components_to_copy[] = $row;
                    }
                    mysqli_free_result($copy_res);
                }
                mysqli_stmt_close($stmt_copy);

                if (empty($components_to_copy)) {
                    $error = 'No ICA components found to copy from the selected class.';
                } else {
                    $copy_total_scaled = calculate_scaled_total($components_to_copy, 'scaled_total_marks');
                    if (scaled_total_exceeds_limit($copy_total_scaled)) {
                        $error = 'The source class has more than 50 scaled ICA marks allocated (' . number_format($copy_total_scaled, 2) . '). Please fix the source class before copying.';
                    } elseif (scaled_total_below_limit($copy_total_scaled)) {
                        $error = 'The source class has less than 50 scaled ICA marks allocated (' . number_format($copy_total_scaled, 2) . '). Please complete its components before copying.';
                    } else {
                        mysqli_begin_transaction($conn);
                        try {
                            $delete_sql = "DELETE FROM ica_components WHERE teacher_id = ? AND subject_id = ? AND (class_id = ? OR (class_id IS NULL AND ? = 0))";
                            $stmt_delete = mysqli_prepare($conn, $delete_sql);
                            if (!$stmt_delete) {
                                throw new Exception('Unable to clean up existing components for the target class.');
                            }
                            mysqli_stmt_bind_param($stmt_delete, "iiii", $teacher_id, $copy_subject_id, $target_class_id, $target_class_id);
                            mysqli_stmt_execute($stmt_delete);
                            mysqli_stmt_close($stmt_delete);

                            $insert_sql = "INSERT INTO ica_components (teacher_id, subject_id, class_id, course_type, component_name, instances, marks_per_instance, total_marks, scaled_total_marks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            $stmt_insert = mysqli_prepare($conn, $insert_sql);
                            if (!$stmt_insert) {
                                throw new Exception('Unable to copy components to the selected class.');
                            }

                            foreach ($components_to_copy as $component_row) {
                                $course_type = $component_row['course_type'] ?? '';
                                $component_name = $component_row['component_name'] ?? '';
                                $instances = isset($component_row['instances']) ? (int)$component_row['instances'] : 0;
                                $marks_per_instance = isset($component_row['marks_per_instance']) ? (float)$component_row['marks_per_instance'] : 0.0;
                                $total_marks = isset($component_row['total_marks']) ? (float)$component_row['total_marks'] : ($instances * $marks_per_instance);
                                $scaled_total = isset($component_row['scaled_total_marks']) ? (float)$component_row['scaled_total_marks'] : 0.0;

                                mysqli_stmt_bind_param($stmt_insert, "iiissiddd", $teacher_id, $copy_subject_id, $target_class_id, $course_type, $component_name, $instances, $marks_per_instance, $total_marks, $scaled_total);
                                mysqli_stmt_execute($stmt_insert);
                            }
                            mysqli_stmt_close($stmt_insert);

                            mysqli_commit($conn);
                            $success = 'Components copied to the selected class successfully.';
                            if (!empty($components_to_copy[0]['course_type'])) {
                                $existing_course_type = $components_to_copy[0]['course_type'];
                            }
                        } catch (Exception $ex) {
                            mysqli_rollback($conn);
                            $error = $ex->getMessage();
                        }
                    }
                }
            }
        }

        if (empty($error)) {
            load_components_for_context($conn, $teacher_id, $copy_subject_id, $selected_class_id, $existing_components, $existing_course_type, $selected_class_id);
        }
    } else {
        $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
        $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;

        $selected_subject_id = $subject_id;
        $selected_class_id = $class_id;

        if ($subject_id <= 0) {
            $error = 'Please select a subject before saving ICA components.';
        } elseif ($class_id <= 0) {
            $error = 'Please select a class for this subject before saving ICA components.';
        } else {
            $course_type_input = $_POST['course_type'] ?? '';
            if ($course_type_input === '') {
                $course_type_input = $existing_course_type !== '' ? $existing_course_type : 'Regular';
            }
            $course_type = mysqli_real_escape_string($conn, $course_type_input);
            $total_scaled_marks = isset($_POST['components']) ? calculate_scaled_total($_POST['components'], 'scaled_marks') : 0.0;

            if (scaled_total_exceeds_limit($total_scaled_marks)) {
                if ($subject_id > 0) {
                    send_scaled_marks_alert($conn, $teacher_id, $subject_id, $total_scaled_marks);
                }
                $error = 'Total scaled marks cannot exceed 50. Your Program Chair has been notified.';
            } elseif (scaled_total_below_limit($total_scaled_marks)) {
                $error = 'Total scaled marks must add up to 50 before you can save.';
            } else {
                mysqli_begin_transaction($conn);
                try {
                    if ($edit_mode) {
                        $existing_ids = array_column($existing_components, 'id');
                        $submitted_ids = [];

                        if (isset($_POST['components'])) {
                            $insert_query = "INSERT INTO ica_components (teacher_id, subject_id, class_id, course_type, component_name, instances, marks_per_instance, total_marks, scaled_total_marks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            $update_query = "UPDATE ica_components SET class_id = ?, course_type = ?, component_name = ?, instances = ?, marks_per_instance = ?, total_marks = ?, scaled_total_marks = ? WHERE id = ? AND teacher_id = ? AND subject_id = ?";
                            $stmt_insert = mysqli_prepare($conn, $insert_query);
                            $stmt_update = mysqli_prepare($conn, $update_query);

                            foreach ($_POST['components'] as $component) {
                                $name = ($component['name_select'] ?? '') === 'Other' ? ($component['name_other'] ?? '') : ($component['name_select'] ?? '');
                                $instances = isset($component['instances']) ? (int)$component['instances'] : 0;
                                $marks_per_instance = isset($component['marks']) ? (float)$component['marks'] : 0.0;
                                $total_marks = $instances * $marks_per_instance;
                                $scaled_total_marks = isset($component['scaled_marks']) ? (float)$component['scaled_marks'] : 0.0;
                                $component_id = isset($component['id']) ? (int)$component['id'] : 0;

                                if ($component_id && in_array($component_id, $existing_ids, true)) {
                                    if ($stmt_update) {
                                        mysqli_stmt_bind_param($stmt_update, "issidddiii", $class_id, $course_type, $name, $instances, $marks_per_instance, $total_marks, $scaled_total_marks, $component_id, $teacher_id, $subject_id);
                                        mysqli_stmt_execute($stmt_update);
                                    }
                                    $submitted_ids[] = $component_id;
                                } else {
                                    if ($stmt_insert) {
                                        mysqli_stmt_bind_param($stmt_insert, "iiissiddd", $teacher_id, $subject_id, $class_id, $course_type, $name, $instances, $marks_per_instance, $total_marks, $scaled_total_marks);
                                        mysqli_stmt_execute($stmt_insert);
                                    }
                                }
                            }

                            if ($stmt_insert) {
                                mysqli_stmt_close($stmt_insert);
                            }
                            if ($stmt_update) {
                                mysqli_stmt_close($stmt_update);
                            }

                            $delete_ids = array_diff($existing_ids, $submitted_ids);
                            if (!empty($delete_ids)) {
                                $placeholders = implode(',', array_fill(0, count($delete_ids), '?'));
                                $delete_query = "DELETE FROM ica_components WHERE id IN ($placeholders) AND teacher_id = ? AND subject_id = ? AND (class_id = ? OR (class_id IS NULL AND ? = 0))";
                                $stmt_delete = mysqli_prepare($conn, $delete_query);
                                if ($stmt_delete) {
                                    $types = str_repeat('i', count($delete_ids)) . 'iiii';
                                    $params = array_merge($delete_ids, [$teacher_id, $subject_id, $class_id, $class_id]);
                                    mysqli_stmt_bind_param($stmt_delete, $types, ...$params);
                                    mysqli_stmt_execute($stmt_delete);
                                    mysqli_stmt_close($stmt_delete);
                                }
                            }
                        }
                    } else {
                        $delete_query = "DELETE FROM ica_components WHERE teacher_id = ? AND subject_id = ? AND (class_id = ? OR (class_id IS NULL AND ? = 0))";
                        $stmt_delete = mysqli_prepare($conn, $delete_query);
                        if ($stmt_delete) {
                            mysqli_stmt_bind_param($stmt_delete, "iiii", $teacher_id, $subject_id, $class_id, $class_id);
                            mysqli_stmt_execute($stmt_delete);
                            mysqli_stmt_close($stmt_delete);
                        }

                        if (isset($_POST['components'])) {
                            $insert_query = "INSERT INTO ica_components (teacher_id, subject_id, class_id, course_type, component_name, instances, marks_per_instance, total_marks, scaled_total_marks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            $stmt_insert = mysqli_prepare($conn, $insert_query);
                            if ($stmt_insert) {
                                foreach ($_POST['components'] as $component) {
                                    $name = ($component['name_select'] ?? '') === 'Other' ? ($component['name_other'] ?? '') : ($component['name_select'] ?? '');
                                    $instances = isset($component['instances']) ? (int)$component['instances'] : 0;
                                    $marks_per_instance = isset($component['marks']) ? (float)$component['marks'] : 0.0;
                                    $total_marks = $instances * $marks_per_instance;
                                    $scaled_total_marks = isset($component['scaled_marks']) ? (float)$component['scaled_marks'] : 0.0;

                                    mysqli_stmt_bind_param($stmt_insert, "iiissiddd", $teacher_id, $subject_id, $class_id, $course_type, $name, $instances, $marks_per_instance, $total_marks, $scaled_total_marks);
                                    mysqli_stmt_execute($stmt_insert);
                                }
                                mysqli_stmt_close($stmt_insert);
                            }
                        }
                    }

                    mysqli_commit($conn);
                    $success = "ICA components have been " . ($edit_mode ? "updated" : "saved") . " successfully!";
                    $existing_course_type = $course_type;
                    $edit_mode = true;
                    $edit_subject_id = $subject_id;

                    load_components_for_context($conn, $teacher_id, $subject_id, $class_id, $existing_components, $existing_course_type, $selected_class_id);
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    error_log("Error saving components: " . $e->getMessage(), 3, 'C:\\xampp\\php\\logs\\php_error_log');
                    $error = 'Failed to save components. Please try again.';
                }
            }
        }
    }
}

if ($stmt_subjects) {
    mysqli_stmt_close($stmt_subjects);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create ICA Components - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg"><link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 400px;
            text-align: center;
        }
        .modal-content button {
            background-color: #A6192E;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        .modal-content button:hover {
            background-color: #8b1627;
        }
        .success-modal .modal-content {
            background-color: #e6ffe6;
            border: 2px solid #28a745;
        }
        .success-modal .modal-content h3 {
            color: #28a745;
        }
        .error-modal .modal-content {
            background-color: #ffcccc;
            border: 2px solid #dc3545;
            font-weight: bold;
        }
        .error-modal .modal-content h3 {
            color: #dc3545;
        }
        body.dark-mode .modal-content {
            background-color: #555;
            color: #e0e0e0;
        }
        body.dark-mode .success-modal .modal-content {
            background-color: #2a4b2a;
            border-color: #28a745;
        }
        body.dark-mode .error-modal .modal-content {
            background-color: #4b2a2a;
            border-color: #dc3545;
        }

        /* START: Styles to optimize form spacing */
        .component-row .form-group {
            margin-bottom: 12px; /* Reduced from default */
        }
        .component-row .form-group label {
            margin-bottom: 5px;
            font-size: 0.9em;
            font-weight: 600;
            color: #333333;
        }
        .component-row .form-group input[type="text"],
        .component-row .form-group input[type="number"],
        .component-row .form-group select {
            padding: 10px 14px;
            font-size: 0.95em;
            margin-bottom: 0;
            background-color: #ffffff;
            border: 1px solid #b5b7bd;
            border-radius: 6px;
            color: #212529;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }
        .component-row .form-group select {
            min-height: 42px;
        }
        .component-row .form-group select:focus,
        .component-row .form-group input[type="text"]:focus,
        .component-row .form-group input[type="number"]:focus {
            border-color: #A6192E;
            box-shadow: 0 0 0 2px rgba(166, 25, 46, 0.15);
            outline: none;
        }
        .component-row .other-name-input {
            margin-top: 8px !important; /* Reduced top margin for the 'Other' input */
        }
        /* END: Styles to optimize form spacing */
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
            <h2>ICA Tracker</h2>
            <a href="teacher_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="update_progress.php"><i class="fas fa-chart-line"></i> <span>Update Progress</span></a>
            <a href="create_ica_components.php" class="active"><i class="fas fa-cogs"></i> <span>ICA Components</span></a>
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
            <div class="container">
                <div class="card">
                    <div class="card-header"><h5><?php echo $edit_mode ? 'Edit ICA Components' : 'Define ICA Components'; ?> for a Subject</h5></div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <p style="color: #d32f2f; font-weight: bold;"><?php echo htmlspecialchars($error); ?></p>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <p style="color: #388e3c; font-weight: bold;"><?php echo htmlspecialchars($success); ?></p>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    document.getElementById('success-modal').style.display = 'flex';
                                });
                            </script>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>Select Subject</label>
                            <select id="subject_select" name="subject_id_selector" required>
                                <option value="">-- Select a Subject --</option>
                                <?php while ($subject = mysqli_fetch_assoc($subjects_result)) : ?>
                                    <?php
                                        $subjectType = isset($subject['subject_type']) ? strtolower(trim((string)$subject['subject_type'])) : 'regular';
                                        $isElectiveSubject = ($subjectType === 'elective');
                                        $label = $isElectiveSubject ? $subject['subject_name'] . ' (Elective)' : $subject['subject_name'];
                                    ?>
                                    <option value="<?php echo $subject['id']; ?>" data-subject-type="<?php echo htmlspecialchars($subjectType); ?>" <?php echo ($selected_subject_id == $subject['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group" id="class-select-group" style="<?php echo $selected_subject_id ? '' : 'display:none;'; ?>">
                            <label>Select Class</label>
                            <select id="class_select" name="class_id_selector" required>
                                <option value="">-- Select a Class --</option>
                                <?php foreach ($available_classes as $class_option): ?>
                                    <option value="<?php echo (int)$class_option['id']; ?>" <?php echo ($selected_class_id === (int)$class_option['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class_option['class_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="edit-components-link" style="display: none; text-align: center; padding: 20px;">
                            <p style="margin-bottom: 12px;">
                                Components for this subject are already defined.
                            </p>
                            <a href="#" id="edit-btn" class="btn">Edit Components</a>
                        </div>

                        <form method="POST" id="components-form" style="<?php echo $edit_mode ? '' : 'display: none;'; ?>" autocomplete="off">
                            <input type="hidden" name="subject_id" id="form_subject_id" value="<?php echo $selected_subject_id ?: ''; ?>">
                            <input type="hidden" name="class_id" id="form_class_id" value="<?php echo $selected_class_id ?: ''; ?>">
                            <input type="hidden" name="course_type" value="<?php echo htmlspecialchars($existing_course_type !== '' ? $existing_course_type : 'Regular'); ?>">

                            <hr style="margin: 20px 0;">
                            
                            <div id="components-container"></div>

                            <div class="card-footer" style="text-align: right; padding: 15px; background-color: #f0f0f0; border-radius: 8px; margin-top: 15px;">
                                <h4 id="remaining-marks-display">Total ICA Marks Allocated: 0 / 50</h4>
                            </div>

                            <div style="margin-top: 20px;">
                                <button type="button" id="add-component-btn" class="btn" style="background-color: #28a745;">Add Component</button>
                                <button type="submit" class="btn">Save All Components</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="copy-components-wrapper" class="card" style="display: none; margin-top: 20px;">
                    <div class="card-header"><h5>Reuse Components for Another Class</h5></div>
                    <div class="card-body">
                        <p style="margin-bottom: 12px;">Copy this subject's ICA components to another assigned class without re-entering details.</p>
                        <form method="POST" id="copy-components-form" autocomplete="off" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
                            <input type="hidden" name="copy_components" value="1">
                            <input type="hidden" name="subject_id" id="copy_subject_id" value="">
                            <input type="hidden" name="source_class_id" id="copy_source_class_id" value="">
                            <div class="form-group" style="min-width:220px;margin:0;">
                                <label>Select Target Class</label>
                                <select name="target_class_id" id="copy_target_class_id" required>
                                    <option value="">-- Select a Class --</option>
                                </select>
                            </div>
                            <button type="submit" class="btn" style="white-space:nowrap;">Copy Components</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="success-modal" class="modal success-modal">
        <div class="modal-content">
            <h3>ICA Marks Completed</h3>
            <p>ICA marks allocation of 50 marks has been completed successfully.</p>
            <button onclick="closeSuccessModal()">OK</button>
        </div>
    </div>

    <div id="error-modal" class="modal error-modal">
        <div class="modal-content">
            <h3>Marks Limit Exceeded</h3>
            <p>You have reached the maximum limit of ICA components. No more components can be created.</p>
            <button onclick="closeErrorModal()">OK</button>
        </div>
    </div>

    <script>
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        }
        if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');

        function showSuccessModal() {
            document.getElementById('success-modal').style.display = 'flex';
        }
        function closeSuccessModal() {
            document.getElementById('success-modal').style.display = 'none';
        }
        function showErrorModal() {
            document.getElementById('error-modal').style.display = 'flex';
        }
        function closeErrorModal() {
            document.getElementById('error-modal').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const subjectSelect = document.getElementById('subject_select');
            const componentsForm = document.getElementById('components-form');
            const editLinkDiv = document.getElementById('edit-components-link');
            const editBtn = document.getElementById('edit-btn');
            const container = document.getElementById('components-container');
            const addBtn = document.getElementById('add-component-btn');
            const remainingMarksDisplay = document.getElementById('remaining-marks-display');
            const saveButton = document.querySelector('button[type="submit"]');
            const formSubjectIdInput = document.getElementById('form_subject_id');
            const classSelectGroup = document.getElementById('class-select-group');
            const classSelect = document.getElementById('class_select');
            const formClassIdInput = document.getElementById('form_class_id');
            const copyWrapper = document.getElementById('copy-components-wrapper');
            const copyForm = document.getElementById('copy-components-form');
            const copySubjectInput = document.getElementById('copy_subject_id');
            const copySourceInput = document.getElementById('copy_source_class_id');
            const copyTargetSelect = document.getElementById('copy_target_class_id');
            const totalIcaMarks = 50;
            const scaledAlertState = Object.create(null);
            let componentIndex = 0;

            const isEditMode = <?php echo json_encode($edit_mode); ?>;
            const existingComponentsData = <?php echo json_encode($existing_components); ?>;
            const initialSubjectId = <?php echo (int)$selected_subject_id; ?>;
            const initialClassId = <?php echo (int)$selected_class_id; ?>;
            const initialClasses = <?php echo json_encode($available_classes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
            let cachedClasses = Array.isArray(initialClasses) ? initialClasses : [];

            const allComponentOptions = [
                'Mid Exam', 'Assignment', 'Quiz', 'Class participation', 
                'Research Paper', 'Case Study', 'Mini Project', 'Lab Performance', 'Lab Test', 
                'Review 1/project proposal', 'Review 2', 'Progress review/report', 
                'Final Presentation/viva/report', 'Viva', 'Other'
            ];

            function populateClassOptions(classes, selectedValue) {
                if (!classSelect) {
                    return;
                }
                cachedClasses = Array.isArray(classes) ? classes : [];
                classSelect.innerHTML = '<option value="">-- Select a Class --</option>';
                cachedClasses.forEach(cls => {
                    if (!cls || typeof cls !== 'object') {
                        return;
                    }
                    const option = document.createElement('option');
                    option.value = cls.id;
                    option.textContent = cls.class_name;
                    classSelect.appendChild(option);
                });
                if (selectedValue) {
                    classSelect.value = String(selectedValue);
                }
                if (classSelectGroup) {
                    classSelectGroup.style.display = cachedClasses.length > 0 ? '' : 'none';
                }
            }

            function updateCopySection(classes, subjectId, classId, componentsDefined) {
                if (!copyWrapper || !copyForm || !copyTargetSelect) {
                    return;
                }

                if (!componentsDefined || !subjectId || !classId || !Array.isArray(classes) || classes.length <= 1) {
                    copyWrapper.style.display = 'none';
                    copySubjectInput.value = '';
                    copySourceInput.value = '';
                    copyTargetSelect.innerHTML = '<option value="">-- Select a Class --</option>';
                    return;
                }

                copyWrapper.style.display = '';
                copySubjectInput.value = subjectId;
                copySourceInput.value = classId;
                copyTargetSelect.innerHTML = '<option value="">-- Select a Class --</option>';
                classes.forEach(cls => {
                    if (!cls || typeof cls !== 'object') {
                        return;
                    }
                    if (String(cls.id) === String(classId)) {
                        return;
                    }
                    const option = document.createElement('option');
                    option.value = cls.id;
                    option.textContent = cls.class_name;
                    copyTargetSelect.appendChild(option);
                });
                copyTargetSelect.disabled = copyTargetSelect.options.length <= 1;
            }

            function handleClassSelection(triggerLookup = true) {
                const subjectId = subjectSelect ? subjectSelect.value : '';
                const classId = classSelect ? classSelect.value : '';
                if (formClassIdInput) {
                    formClassIdInput.value = classId;
                }

                if (!subjectId) {
                    if (componentsForm) componentsForm.style.display = 'none';
                    if (editLinkDiv) editLinkDiv.style.display = 'none';
                    updateCopySection([], null, null, false);
                    return;
                }

                if (!classId) {
                    if (componentsForm) componentsForm.style.display = 'none';
                    if (editLinkDiv) editLinkDiv.style.display = 'none';
                    updateCopySection(cachedClasses, subjectId, null, false);
                    return;
                }

                const isCurrentEditContext = isEditMode && String(initialSubjectId) === String(subjectId) && String(initialClassId) === String(classId);

                if (isCurrentEditContext) {
                    if (editLinkDiv) editLinkDiv.style.display = 'none';
                    if (componentsForm) componentsForm.style.display = '';
                    updateCopySection(cachedClasses, subjectId, classId, existingComponentsData.length > 0);
                    return;
                }

                if (!triggerLookup) {
                    return;
                }

                fetch(`check_components.php?subject_id=${encodeURIComponent(subjectId)}&class_id=${encodeURIComponent(classId)}`)
                    .then(res => res.json())
                    .then(data => {
                        const exists = data && data.exists;
                        if (exists) {
                            if (editLinkDiv) {
                                editLinkDiv.style.display = 'block';
                            }
                            if (componentsForm) {
                                componentsForm.style.display = 'none';
                            }
                            if (editBtn) {
                                editBtn.href = `create_ica_components.php?subject_id=${encodeURIComponent(subjectId)}&class_id=${encodeURIComponent(classId)}&edit=true`;
                            }
                            updateCopySection(cachedClasses, subjectId, classId, true);
                        } else {
                            if (editLinkDiv) {
                                editLinkDiv.style.display = 'none';
                            }
                            if (componentsForm) {
                                componentsForm.style.display = '';
                            }
                            updateCopySection(cachedClasses, subjectId, classId, false);
                        }
                    })
                    .catch(() => {
                        if (editLinkDiv) {
                            editLinkDiv.style.display = 'none';
                        }
                        if (componentsForm) {
                            componentsForm.style.display = '';
                        }
                        updateCopySection(cachedClasses, subjectId, classId, false);
                    });
            }

            function loadClassesForSubject(subjectId, preselectValue) {
                if (!classSelect) {
                    return;
                }

                if (!subjectId) {
                    populateClassOptions([], '');
                    if (formClassIdInput) {
                        formClassIdInput.value = '';
                    }
                    updateCopySection([], null, null, false);
                    return;
                }

                classSelect.innerHTML = '<option value="">Loading...</option>';
                fetch(`get_classes_for_subject.php?subject_id=${encodeURIComponent(subjectId)}`)
                    .then(res => res.json())
                    .then(classes => {
                        const classList = Array.isArray(classes) ? classes : [];
                        populateClassOptions(classList, preselectValue);
                        if (!classSelect.value && classList.length === 1) {
                            classSelect.value = String(classList[0].id);
                        }
                        if (formClassIdInput) {
                            formClassIdInput.value = classSelect.value;
                        }
                        if (classSelect.value) {
                            handleClassSelection(true);
                        } else {
                            if (componentsForm) componentsForm.style.display = 'none';
                            if (editLinkDiv) editLinkDiv.style.display = 'none';
                            updateCopySection(classList, subjectId, null, false);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading classes for subject:', error);
                        populateClassOptions([], '');
                        if (componentsForm) componentsForm.style.display = 'none';
                        if (editLinkDiv) editLinkDiv.style.display = 'none';
                        updateCopySection([], subjectId, null, false);
                    });
            }
            
            function updateAllDropdowns() {
                const selects = Array.from(container.querySelectorAll('.component-name-select'));

                selects.forEach(select => {
                    const currentSelection = select.value;
                    const takenValues = new Set();
                    selects.forEach(other => {
                        if (other !== select && other.value && other.value !== 'Other') {
                            takenValues.add(other.value);
                        }
                    });

                    let optionsHtml = '<option value="">-- Select Component --</option>';
                    allComponentOptions.forEach(optionLabel => {
                        if (optionLabel === 'Other') {
                            optionsHtml += '<option value="Other">Other</option>';
                            return;
                        }

                        if (!takenValues.has(optionLabel) || optionLabel === currentSelection) {
                            optionsHtml += `<option value="${optionLabel}">${optionLabel}</option>`;
                        }
                    });

                    select.innerHTML = optionsHtml;
                    if (currentSelection && Array.from(select.options).some(opt => opt.value === currentSelection)) {
                        select.value = currentSelection;
                    }
                });
            }

            function reindexComponents() {
                const rows = container.querySelectorAll('.component-row');
                componentIndex = rows.length;
                rows.forEach((row, index) => {
                    const header = row.querySelector('h5');
                    header.textContent = `Component ${index + 1}`;
                    const inputs = row.querySelectorAll('input, select');
                    inputs.forEach(input => {
                        const name = input.name;
                        if (name && name.includes('components[')) {
                            input.name = name.replace(/components\[\d+\]/, `components[${index}]`);
                        }
                    });
                });
            }

            function updateRemainingMarks() {
                const allScaledTotals = container.querySelectorAll('.scaled-marks-input');
                let currentTotal = 0;
                allScaledTotals.forEach(input => {
                    currentTotal += parseFloat(input.value) || 0;
                });
                const subjectId = formSubjectIdInput.value;
                const formattedTotal = currentTotal.toFixed(2);
                let statusNote = '';

                if (currentTotal > totalIcaMarks) {
                    statusNote = ' (Limit exceeded - Program Chair notified)';
                    remainingMarksDisplay.style.color = '#dc3545';
                    if (subjectId) {
                        if (!scaledAlertState[subjectId]) {
                            scaledAlertState[subjectId] = true;
                            sendScaledAlert(subjectId, currentTotal);
                        }
                    }
                } else if (currentTotal < totalIcaMarks) {
                    statusNote = ' (Add more components to reach 50)';
                    remainingMarksDisplay.style.color = '#ffc107';
                    if (subjectId) {
                        scaledAlertState[subjectId] = false;
                    }
                } else {
                    statusNote = ' (Ready to submit)';
                    remainingMarksDisplay.style.color = '#28a745';
                    if (subjectId) {
                        scaledAlertState[subjectId] = false;
                    }
                }

                remainingMarksDisplay.textContent = `Total ICA Marks Allocated: ${formattedTotal} / ${totalIcaMarks}${statusNote}`;
                saveButton.disabled = currentTotal !== totalIcaMarks;
                addBtn.disabled = currentTotal >= totalIcaMarks;
            }

            function addComponentRow(data = {}) {
                const componentDiv = document.createElement('div');
                componentDiv.className = 'component-row card';
                componentDiv.style.padding = '15px';
                componentDiv.style.marginBottom = '15px';
                
                componentDiv.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h5>Component ${componentIndex + 1}</h5>
                        <button type="button" class="remove-component-btn btn" style="background-color: #dc3545;">Remove</button>
                    </div>
                    <input type="hidden" name="components[${componentIndex}][id]" value="${data.id || ''}">
                    <div class="form-group"><label>Component Name</label><select name="components[${componentIndex}][name_select]" class="component-name-select" required></select><input type="text" name="components[${componentIndex}][name_other]" class="other-name-input" style="display: none;" placeholder="Enter Custom Component Name"></div>
                    <div class="form-group"><label>Number of Instances</label><input type="number" name="components[${componentIndex}][instances]" class="instances-input" value="${data.instances || 1}" min="1" required></div>
                    <div class="form-group"><label>Marks per Instance</label><input type="number" name="components[${componentIndex}][marks]" class="marks-input" value="${data.marks_per_instance || 10}" min="0" step="0.5" required></div>
                    <div class="form-group"><label>Raw Total Marks</label><input type="text" class="raw-total-display" readonly style="background-color: #e9ecef;" value="${data.total_marks || (data.instances || 1) * (data.marks_per_instance || 10)}"></div>
                    <div class="form-group"><label>Total Component Marks (Scaled)</label><input type="number" name="components[${componentIndex}][scaled_marks]" class="scaled-marks-input" value="${data.scaled_total_marks || 10}" min="0" step="0.5" required></div>
                `;
                
                container.appendChild(componentDiv);
                updateAllDropdowns();

                const select = componentDiv.querySelector('.component-name-select');
                const otherInput = componentDiv.querySelector('.other-name-input');
                if (data.component_name && !allComponentOptions.includes(data.component_name)) {
                    select.value = 'Other';
                    otherInput.value = data.component_name;
                    otherInput.style.display = 'block';
                    otherInput.required = true;
                } else if (data.component_name) {
                    select.value = data.component_name;
                }
                
                componentIndex++;
                updateAllDropdowns();
                updateRemainingMarks();
            }

            addBtn.addEventListener('click', () => {
                const currentTotal = Array.from(container.querySelectorAll('.scaled-marks-input'))
                    .reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
                if (currentTotal >= totalIcaMarks) {
                    showErrorModal();
                    return;
                }
                addComponentRow();
            });

            container.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-component-btn')) {
                    e.target.closest('.component-row').remove();
                    reindexComponents();
                    updateAllDropdowns();
                    updateRemainingMarks();
                }
            });

            container.addEventListener('change', function(e) {
                if (e.target.classList.contains('component-name-select')) {
                    const otherInput = e.target.closest('.form-group').querySelector('.other-name-input');
                    otherInput.style.display = e.target.value === 'Other' ? 'block' : 'none';
                    otherInput.required = e.target.value === 'Other';
                    if (e.target.value !== 'Other') {
                        otherInput.value = '';
                    }
                    updateAllDropdowns();
                }
            });

            container.addEventListener('input', function(e) {
                if (e.target.classList.contains('instances-input') || e.target.classList.contains('marks-input')) {
                    const row = e.target.closest('.component-row');
                    const instances = row.querySelector('.instances-input').value || 0;
                    const marks = row.querySelector('.marks-input').value || 0;
                    row.querySelector('.raw-total-display').value = (parseFloat(instances) * parseFloat(marks)).toFixed(2);
                }
                if (e.target.classList.contains('scaled-marks-input')) {
                    updateRemainingMarks();
                }
            });

            if (subjectSelect) {
                subjectSelect.addEventListener('change', function() {
                    const subjectId = this.value;
                    if (formSubjectIdInput) {
                        formSubjectIdInput.value = subjectId;
                    }
                    if (subjectId) {
                        scaledAlertState[subjectId] = false;
                    }
                    if (componentsForm) {
                        componentsForm.style.display = 'none';
                    }
                    if (editLinkDiv) {
                        editLinkDiv.style.display = 'none';
                    }
                    container.innerHTML = '';
                    componentIndex = 0;
                    updateRemainingMarks();

                    if (!subjectId) {
                        populateClassOptions([], '');
                        updateCopySection([], null, null, false);
                        return;
                    }

                    loadClassesForSubject(subjectId, '');
                });
            }

            if (classSelect) {
                classSelect.addEventListener('change', function() {
                    container.innerHTML = '';
                    componentIndex = 0;
                    updateRemainingMarks();
                    handleClassSelection(true);
                });
            }

            if (isEditMode && existingComponentsData.length) {
                existingComponentsData.forEach(compData => addComponentRow(compData));
                updateRemainingMarks();
            }

            if (initialSubjectId) {
                if (subjectSelect) {
                    subjectSelect.value = String(initialSubjectId);
                }
                if (formSubjectIdInput) {
                    formSubjectIdInput.value = initialSubjectId;
                }
                if (cachedClasses.length) {
                    populateClassOptions(cachedClasses, initialClassId ? String(initialClassId) : '');
                    if (initialClassId && classSelect) {
                        classSelect.value = String(initialClassId);
                        if (formClassIdInput) {
                            formClassIdInput.value = initialClassId;
                        }
                        handleClassSelection(isEditMode ? false : true);
                    } else if (!isEditMode) {
                        handleClassSelection(false);
                    }
                } else {
                    loadClassesForSubject(initialSubjectId, initialClassId ? String(initialClassId) : '');
                }
            } else {
                populateClassOptions([], '');
                updateCopySection([], null, null, false);
            }

            function sendScaledAlert(subjectId, totalScaled) {
                fetch('trigger_scaled_alert.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ subject_id: subjectId, total_scaled: totalScaled })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data || data.status !== 'ok') {
                        console.warn('Unable to notify program chair about scaled marks limit.', data && data.message ? data.message : '');
                    }
                })
                .catch(error => {
                    console.error('Failed to send scaled marks alert:', error);
                });
            }
        });
    </script>
</body>
</html>
