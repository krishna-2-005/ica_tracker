<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/academic_context.php';

if (!function_exists('ensureColumnExists')) {
    function ensureColumnExists(mysqli $conn, string $table, string $column, string $definition): void
    {
        $tableSafe = preg_replace('/[^A-Za-z0-9_]/', '', $table);
        $columnSafe = preg_replace('/[^A-Za-z0-9_]/', '', $column);
        if ($tableSafe === '' || $columnSafe === '') {
            return;
        }
        $checkSql = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . mysqli_real_escape_string($conn, $tableSafe) . "' AND COLUMN_NAME = '" . mysqli_real_escape_string($conn, $columnSafe) . "'";
        $checkResult = mysqli_query($conn, $checkSql);
        if ($checkResult) {
            $row = mysqli_fetch_assoc($checkResult);
            mysqli_free_result($checkResult);
            if ((int)($row['cnt'] ?? 0) > 0) {
                return;
            }
        }
        $alterSql = "ALTER TABLE `{$tableSafe}` ADD COLUMN `{$columnSafe}` {$definition}";
        @mysqli_query($conn, $alterSql);
    }
}

if (!function_exists('ensureClassesTermColumn')) {
    function ensureClassesTermColumn(mysqli $conn): void
    {
        ensureColumnExists($conn, 'classes', 'academic_term_id', 'INT NULL DEFAULT NULL');
    }
}

ensureClassesTermColumn($conn);
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';
$adminNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$adminNameDisplay = $adminNameRaw !== '' ? format_person_display($adminNameRaw) : 'ADMIN';
$stme_class_options = array_map('strtoupper', [
    '1st Year CSEDS',
    '1st Year CE',
    '2nd Year CSEDS',
    '2nd Year CE',
    '3rd Year CSEDS',
    '3rd Year CE',
    '4th Year CSEDS',
    '4th Year CE'
]);
$stme_semester_map = [
    '1ST YEAR CSEDS' => [1, 2],
    '1ST YEAR CE' => [1, 2],
    '2ND YEAR CSEDS' => [3, 4],
    '2ND YEAR CE' => [3, 4],
    '3RD YEAR CSEDS' => [5, 6],
    '3RD YEAR CE' => [5, 6],
    '4TH YEAR CSEDS' => [7, 8],
    '4TH YEAR CE' => [7, 8]
];

$class_name_input = '';
$selected_school = isset($_POST['school']) ? trim((string)$_POST['school']) : '';
$selected_semester = isset($_POST['semester']) ? (int)$_POST['semester'] : '';
$sections_input = isset($_POST['sections']) ? strtoupper(trim((string)$_POST['sections'])) : '';
$standard_sections = ['A', 'B', 'C', 'D', 'E'];
$section_select_value = '';
$section_other_value = '';
if ($sections_input !== '') {
    if (in_array($sections_input, $standard_sections, true)) {
        $section_select_value = $sections_input;
    } else {
        $section_select_value = 'OTHER';
        $section_other_value = $sections_input;
    }
}
$description_input = isset($_POST['description']) ? strtoupper(trim((string)$_POST['description'])) : '';
$selected_term_id = isset($_POST['academic_term_id']) ? (int)$_POST['academic_term_id'] : 0;

$terms_by_school = [];
$terms_for_js = [];
$allTerms = fetchAcademicTerms($conn);
foreach ($allTerms as $termRow) {
    $schoolKey = $termRow['school_name'] ?? '';
    if ($schoolKey === '') {
        continue;
    }
    if (!isset($terms_by_school[$schoolKey])) {
        $terms_by_school[$schoolKey] = [];
    }
    $terms_by_school[$schoolKey][] = $termRow;
}
foreach ($terms_by_school as $schoolKey => $termList) {
    $terms_for_js[$schoolKey] = array_map(static function ($termRow) {
        return [
            'id' => (int)$termRow['id'],
            'label' => $termRow['label'],
        ];
    }, $termList);
}

$post_action = isset($_POST['action']) ? trim((string)$_POST['action']) : 'create_class';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $post_action === 'update_timeline') {
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $new_term_id_value = isset($_POST['academic_term_id']) ? trim((string)$_POST['academic_term_id']) : '';
    $new_term_id = $new_term_id_value === '' ? null : (int)$new_term_id_value;

    if ($class_id <= 0) {
        $error = 'Invalid class selected for timeline update.';
    } else {
        $class_stmt = mysqli_prepare($conn, "SELECT id, class_name, school FROM classes WHERE id = ?");
        if ($class_stmt) {
            mysqli_stmt_bind_param($class_stmt, "i", $class_id);
            mysqli_stmt_execute($class_stmt);
            $result = mysqli_stmt_get_result($class_stmt);
            $class_row = $result ? mysqli_fetch_assoc($result) : null;
            if ($result) {
                mysqli_free_result($result);
            }
            mysqli_stmt_close($class_stmt);
        } else {
            $class_row = null;
        }

        if (!$class_row) {
            $error = 'Unable to locate the chosen class.';
        } else {
            $class_school = $class_row['school'] ?? '';
            $allowed_terms = $terms_by_school[$class_school] ?? [];
            $allowed_term_ids = array_map(static function ($row) {
                return (int)$row['id'];
            }, $allowed_terms);

            if ($new_term_id !== null && !in_array($new_term_id, $allowed_term_ids, true)) {
                $error = 'The selected timeline does not belong to this class school.';
            } else {
                if ($new_term_id !== null && $new_term_id > 0) {
                    $update_stmt = mysqli_prepare($conn, "UPDATE classes SET academic_term_id = ? WHERE id = ?");
                    if ($update_stmt) {
                        mysqli_stmt_bind_param($update_stmt, "ii", $new_term_id, $class_id);
                        if (mysqli_stmt_execute($update_stmt)) {
                            $success = 'Timeline linked to ' . htmlspecialchars($class_row['class_name']) . ' successfully.';
                        } else {
                            $error = 'Failed to link the selected timeline to the class.';
                        }
                        mysqli_stmt_close($update_stmt);
                    } else {
                        $error = 'Failed to link the selected timeline to the class.';
                    }
                } else {
                    $update_stmt = mysqli_prepare($conn, "UPDATE classes SET academic_term_id = NULL WHERE id = ?");
                    if ($update_stmt) {
                        mysqli_stmt_bind_param($update_stmt, "i", $class_id);
                        if (mysqli_stmt_execute($update_stmt)) {
                            $success = 'Timeline link removed for ' . htmlspecialchars($class_row['class_name']) . '.';
                        } else {
                            $error = 'Failed to remove the timeline link from the class.';
                        }
                        mysqli_stmt_close($update_stmt);
                    } else {
                        $error = 'Failed to remove the timeline link from the class.';
                    }
                }
            }
        }
    }

    if ($error !== '') {
        $success = '';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $post_action === 'edit_class') {
    $edit_class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $edited_name = isset($_POST['class_name']) ? strtoupper(trim((string)$_POST['class_name'])) : '';
    $edited_semester = isset($_POST['semester']) ? (int)$_POST['semester'] : 0;
    $edited_description = isset($_POST['description']) ? strtoupper(trim((string)$_POST['description'])) : '';
    $edited_sections_input = isset($_POST['sections']) ? strtoupper(trim((string)$_POST['sections'])) : '';
    $edited_term_value = isset($_POST['academic_term_id']) ? trim((string)$_POST['academic_term_id']) : '';
    $edited_term_id = $edited_term_value === '' ? null : (int)$edited_term_value;

    if ($edit_class_id <= 0) {
        $error = 'Invalid class selected for editing.';
    }

    $target_class = null;
    if ($error === '') {
        $class_stmt = mysqli_prepare($conn, "SELECT id, class_name, school, academic_term_id FROM classes WHERE id = ? LIMIT 1");
        if ($class_stmt) {
            mysqli_stmt_bind_param($class_stmt, 'i', $edit_class_id);
            mysqli_stmt_execute($class_stmt);
            $result = mysqli_stmt_get_result($class_stmt);
            if ($result && mysqli_num_rows($result) === 1) {
                $target_class = mysqli_fetch_assoc($result);
            } else {
                $error = 'Unable to find the requested class.';
            }
            if ($result) {
                mysqli_free_result($result);
            }
            mysqli_stmt_close($class_stmt);
        } else {
            $error = 'Unable to load class details.';
        }
    }

    if ($error === '' && $target_class === null) {
        $error = 'Unable to find the requested class.';
    }

    $class_school = $target_class['school'] ?? '';

    if ($error === '' && $edited_name === '') {
        $error = 'Class name cannot be empty.';
    }

    if ($error === '' && ($edited_semester < 1 || $edited_semester > 10)) {
        $error = 'Semester must be between 1 and 10.';
    }

    if ($error === '' && $class_school === '') {
        $error = 'Class is missing its associated school.';
    }

    if ($error === '') {
        $allowed_terms = $terms_by_school[$class_school] ?? [];
        $allowed_term_ids = array_map(static function ($row) {
            return (int)$row['id'];
        }, $allowed_terms);
        if ($edited_term_id !== null && !in_array($edited_term_id, $allowed_term_ids, true)) {
            $error = 'Selected timeline is not valid for this class.';
        }
    }

    if ($error === '') {
        $duplicate_sql = "SELECT id FROM classes WHERE class_name = ? AND school = ? AND semester = ? AND ";
        if ($edited_term_id === null) {
            $duplicate_sql .= "academic_term_id IS NULL ";
        } else {
            $duplicate_sql .= "academic_term_id = ? ";
        }
        $duplicate_sql .= "AND id <> ? LIMIT 1";
        $duplicate_stmt = mysqli_prepare($conn, $duplicate_sql);
        if ($duplicate_stmt) {
            if ($edited_term_id === null) {
                mysqli_stmt_bind_param($duplicate_stmt, 'ssii', $edited_name, $class_school, $edited_semester, $edit_class_id);
            } else {
                mysqli_stmt_bind_param($duplicate_stmt, 'ssiii', $edited_name, $class_school, $edited_semester, $edited_term_id, $edit_class_id);
            }
            mysqli_stmt_execute($duplicate_stmt);
            $dup_result = mysqli_stmt_get_result($duplicate_stmt);
            if ($dup_result && mysqli_num_rows($dup_result) > 0) {
                $error = 'Another class with the same name, semester, and timeline already exists.';
            }
            if ($dup_result) {
                mysqli_free_result($dup_result);
            }
            mysqli_stmt_close($duplicate_stmt);
        } else {
            $error = 'Failed to verify class uniqueness.';
        }
    }

    $parsed_sections = [];
    if ($error === '' && $edited_sections_input !== '') {
        $parsed_sections = array_filter(array_map(static function ($value) {
            return strtoupper(trim((string)$value));
        }, explode(',', $edited_sections_input)), static function ($value) {
            return $value !== '';
        });
        $parsed_sections = array_values(array_unique($parsed_sections));
    }

    if ($error === '') {
        if (!mysqli_begin_transaction($conn)) {
            $error = 'Unable to start class update.';
        }
    }

    if ($error === '') {
        $update_stmt = null;
        if ($edited_term_id === null) {
            $update_stmt = mysqli_prepare($conn, "UPDATE classes SET class_name = ?, semester = ?, description = ?, academic_term_id = NULL WHERE id = ?");
            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, 'sisi', $edited_name, $edited_semester, $edited_description, $edit_class_id);
            }
        } else {
            $update_stmt = mysqli_prepare($conn, "UPDATE classes SET class_name = ?, semester = ?, description = ?, academic_term_id = ? WHERE id = ?");
            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, 'sisii', $edited_name, $edited_semester, $edited_description, $edited_term_id, $edit_class_id);
            }
        }

        $class_updated = false;
        if ($update_stmt && mysqli_stmt_execute($update_stmt)) {
            $class_updated = true;
        } else {
            $error = 'Failed to update class details.';
        }
        if ($update_stmt) {
            mysqli_stmt_close($update_stmt);
        }

        if ($error === '' && $class_updated) {
            $delete_stmt = mysqli_prepare($conn, "DELETE FROM sections WHERE class_id = ?");
            if ($delete_stmt) {
                mysqli_stmt_bind_param($delete_stmt, 'i', $edit_class_id);
                if (!mysqli_stmt_execute($delete_stmt)) {
                    $error = 'Failed to refresh class divisions.';
                }
                mysqli_stmt_close($delete_stmt);
            } else {
                $error = 'Failed to refresh class divisions.';
            }
        }

        if ($error === '' && $class_updated && !empty($parsed_sections)) {
            $insert_section_stmt = mysqli_prepare($conn, "INSERT INTO sections (class_id, section_name) VALUES (?, ?)");
            if ($insert_section_stmt) {
                foreach ($parsed_sections as $section_name) {
                    mysqli_stmt_bind_param($insert_section_stmt, 'is', $edit_class_id, $section_name);
                    if (!mysqli_stmt_execute($insert_section_stmt)) {
                        $error = 'Failed to add one or more divisions.';
                        break;
                    }
                }
                mysqli_stmt_close($insert_section_stmt);
            } else {
                $error = 'Failed to add class divisions.';
            }
        }

        if ($error === '') {
            mysqli_commit($conn);
            $success = 'Class updated successfully.';
        } else {
            mysqli_rollback($conn);
            if ($success !== '') {
                $success = '';
            }
        }
    }

    if ($error !== '') {
        $success = '';
    }
}

if ($post_action === 'edit_class' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_name_input = '';
    $selected_school = '';
    $selected_semester = '';
    $sections_input = '';
    $description_input = '';
    $selected_term_id = 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $post_action === 'create_class') {
    $class_name_input = isset($_POST['class_name']) ? strtoupper(trim((string)$_POST['class_name'])) : '';
    $class_name = mysqli_real_escape_string($conn, $class_name_input);
    $school = mysqli_real_escape_string($conn, $selected_school);
    $semester = (int)$selected_semester;
    $description = mysqli_real_escape_string($conn, $description_input);

    if ($class_name_input === '') {
        $error = 'Please select a class name.';
    }

    if ($error === '') {
        $school_terms = $terms_by_school[$selected_school] ?? [];
        if (empty($school_terms)) {
            $error = 'No academic calendar timelines exist for the selected school. Add a calendar entry first.';
        } else {
            $valid_ids = array_map(static function ($item) {
                return (int)$item['id'];
            }, $school_terms);
            if ($selected_term_id <= 0 || !in_array($selected_term_id, $valid_ids, true)) {
                $error = 'Please choose a valid timeline for this class.';
            }
        }
    }

    if ($error === '') {
        // Check if the class already exists for the given department, semester, and timeline
        $check_query = "SELECT id, academic_term_id FROM classes WHERE class_name = ? AND school = ? AND semester = ?";
        $stmt_check = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt_check, "ssi", $class_name, $school, $semester);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        $matching_classes = [];
        if ($result_check) {
            while ($row = mysqli_fetch_assoc($result_check)) {
                $matching_classes[] = $row;
            }
            mysqli_free_result($result_check);
        }
        mysqli_stmt_close($stmt_check);

        $existing_class_id = null;
        foreach ($matching_classes as $class_row) {
            $row_term_id = isset($class_row['academic_term_id']) ? (int)$class_row['academic_term_id'] : 0;
            if ($row_term_id === $selected_term_id) {
                $existing_class_id = (int)$class_row['id'];
                break;
            }
        }

        if ($existing_class_id) {
        if ($sections_input === '') {
            $error = "This class already exists for the selected school, semester, and timeline. To add new sections, list them in the Divisions/Sections field.";
        } else {
            $section_names = array_filter(array_map(static function ($value) {
                return strtoupper(trim((string)$value));
            }, explode(',', $sections_input)), static function ($value) {
                return $value !== '';
            });
            $section_names = array_values(array_unique($section_names));

            if (empty($section_names)) {
                $error = "No new section names were provided.";
            } else {
                $existing_sections = [];
                $sections_lookup_stmt = mysqli_prepare($conn, "SELECT section_name FROM sections WHERE class_id = ?");
                if ($sections_lookup_stmt) {
                    mysqli_stmt_bind_param($sections_lookup_stmt, "i", $existing_class_id);
                    mysqli_stmt_execute($sections_lookup_stmt);
                    mysqli_stmt_bind_result($sections_lookup_stmt, $existing_section_name);
                    while (mysqli_stmt_fetch($sections_lookup_stmt)) {
                        $existing_sections[] = strtolower(trim((string)$existing_section_name));
                    }
                    mysqli_stmt_close($sections_lookup_stmt);
                }

                $new_sections = [];
                foreach ($section_names as $section_name) {
                    $normalized_section = strtoupper($section_name);
                    if (!in_array(strtolower($normalized_section), $existing_sections, true)) {
                        $new_sections[] = $normalized_section;
                    }
                }

                if (empty($new_sections)) {
                    $error = "All listed sections already exist for this class.";
                } else {
                    $section_insert_stmt = mysqli_prepare($conn, "INSERT INTO sections (class_id, section_name) VALUES (?, ?)");
                    if ($section_insert_stmt) {
                        foreach ($new_sections as $section_name) {
                            mysqli_stmt_bind_param($section_insert_stmt, "is", $existing_class_id, $section_name);
                            mysqli_stmt_execute($section_insert_stmt);
                        }
                        mysqli_stmt_close($section_insert_stmt);
                    }
                    $success = "Added new sections to '{$class_name}': " . implode(', ', $new_sections) . ".";
                }
            }
        }
        } else {
            // If no duplicate, proceed with insertion
            $insert_query = "INSERT INTO classes (class_name, school, semester, description, academic_term_id) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt_insert, "ssisi", $class_name, $school, $semester, $description, $selected_term_id);

            if (mysqli_stmt_execute($stmt_insert)) {
                $new_class_id = mysqli_insert_id($conn);
                $success = "Class '{$class_name}' created successfully!";

                if (!empty($sections_input)) {
                    $section_names = array_map(static function ($value) {
                        return strtoupper(trim((string)$value));
                    }, explode(',', $sections_input));
                    $added_sections = [];

                    $section_query = "INSERT INTO sections (class_id, section_name) VALUES (?, ?)";
                    $stmt_section = mysqli_prepare($conn, $section_query);

                    foreach ($section_names as $section_name) {
                        if (!empty($section_name)) {
                            mysqli_stmt_bind_param($stmt_section, "is", $new_class_id, $section_name);
                            mysqli_stmt_execute($stmt_section);
                            $added_sections[] = $section_name;
                        }
                    }
                    mysqli_stmt_close($stmt_section);
                    if (!empty($added_sections)) {
                        $success .= " Divisions added: " . implode(', ', $added_sections) . ".";
                    }
                }
            } else {
                $error = "Error creating class: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt_insert);
        }
    }
}

// --- MODIFIED QUERY: Fetch all classes AND their associated divisions ---
$classes_query = "SELECT c.*, 
                  MAX(ac.label_override) AS calendar_label_override,
                  MAX(ac.academic_year) AS calendar_academic_year,
                  MAX(ac.semester_term) AS calendar_semester_term,
                  MAX(ac.semester_number) AS calendar_semester_number,
                  MAX(ac.start_date) AS calendar_start_date,
                  MAX(ac.end_date) AS calendar_end_date,
                  GROUP_CONCAT(s.section_name ORDER BY s.section_name SEPARATOR ', ') AS divisions
                  FROM classes c
                  LEFT JOIN academic_calendar ac ON c.academic_term_id = ac.id
                  LEFT JOIN sections s ON c.id = s.class_id
                  GROUP BY c.id
                  ORDER BY c.school, c.semester, c.class_name";
$classes_result = mysqli_query($conn, $classes_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Classes - ICA Tracker</title>
     <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .term-filter-group {
            display: flex;
            gap: 10px;
            margin: 8px 0 12px;
            flex-wrap: wrap;
        }
        .term-filter-button {
            background-color: #f3f4f6;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            color: #374151;
            cursor: pointer;
            font-size: 0.9em;
            padding: 6px 14px;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .term-filter-button.active {
            background-color: #A6192E;
            border-color: #A6192E;
            color: #fff;
        }
        input[data-force-uppercase="true"] {
            text-transform: uppercase;
        }
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: stretch;
        }
        .action-buttons .btn {
            margin-top: 0;
        }
        .action-buttons small {
            color: #a6192e;
            font-size: 0.82rem;
        }
        .btn.secondary {
            background: #fff;
            color: #A6192E;
            border: 1px solid #A6192E;
        }
        .btn.secondary:hover {
            background: #A6192E;
            color: #fff;
        }
        .btn.ghost {
            background: transparent;
            color: #4b5563;
            border: 1px solid #d1d5db;
        }
        .btn.ghost:hover {
            background: #f3f4f6;
            color: #111827;
        }
        #edit-class-modal {
            position: fixed;
            inset: 0;
            background: rgba(44, 42, 41, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            z-index: 1050;
        }
        #edit-class-modal.active {
            display: flex;
        }
        #edit-class-modal .modal-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.18);
            width: 100%;
            max-width: 520px;
            padding: 24px;
            position: relative;
        }
        #edit-class-modal .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        #edit-class-modal .modal-header h3 {
            font-size: 1.2rem;
            color: #2c2a29;
            font-weight: 600;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.6rem;
            color: #6b7280;
            cursor: pointer;
            line-height: 1;
        }
        .modal-close:hover {
            color: #A6192E;
        }
        .modal-body {
            max-height: 64vh;
            overflow-y: auto;
            padding-right: 4px;
        }
        .modal-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 14px;
        }
        .modal-field label {
            font-size: 0.92rem;
            color: #4c5264;
            font-weight: 500;
        }
        .modal-field input,
        .modal-field select,
        .modal-field textarea {
            width: 100%;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid #dcdde7;
            font-size: 0.95rem;
            transition: border-color 0.2s ease;
            resize: vertical;
        }
        .modal-field small {
            color: #4c5264;
            font-size: 0.82rem;
        }
        .modal-field input:focus,
        .modal-field select:focus,
        .modal-field textarea:focus {
            border-color: #A6192E;
            outline: none;
            box-shadow: 0 0 0 3px rgba(166, 25, 46, 0.18);
        }
        .modal-field input[readonly] {
            background: #f3f4f6;
            color: #63666A;
            cursor: not-allowed;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 18px;
        }
        .modal-footer .btn {
            margin-top: 0;
            min-width: 120px;
        }
        body.modal-open {
            overflow: hidden;
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
            <a href="create_classes.php" class="active"><i class="fas fa-layer-group"></i> <span>Create Classes</span></a>
            <a href="create_subjects.php"><i class="fas fa-book"></i> <span>Create Subjects</span></a>
            <a href="assign_teachers.php"><i class="fas fa-user-tag"></i> <span>Assign Teachers</span></a>
            <a href="manage_electives.php"><i class="fas fa-user-friends"></i> <span>Manage Electives</span></a>
            <a href="change_roles.php"><i class="fas fa-user-cog"></i> <span>Change Roles</span></a>
            <a href="bulk_add_students.php"><i class="fas fa-user-plus"></i> <span>Add Students</span></a>
                        <a href="manage_academic_calendar.php"><i class="fas fa-calendar-alt"></i> <span>Academic Calendar</span></a>

            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
        <div class="main-content">
            <div class="header">
                <h2>Welcome, <?php echo htmlspecialchars($adminNameDisplay !== '' ? $adminNameDisplay : $adminNameRaw); ?>!</h2>
                
            </div>
            <div class="container">
                <div class="card">
                    <div class="card-header"><h5>Create New Class</h5></div>
                    <div class="card-body">
                        <?php if ($error) echo "<p style='color: #d32f2f; font-weight: bold;'>$error</p>"; ?>
                        <?php if ($success) echo "<p style='color: #388e3c; font-weight: bold;'>$success</p>"; ?>
                        <form method="POST" id="create-class-form">
                            <input type="hidden" name="action" value="create_class">
                            <div class="form-group">
                                <label>Which School/Department should this class belong to?</label>
                                <select name="school" id="school-select" required>
                                    <option value="">Select School</option>
                                    <option value="STME" <?php echo $selected_school === 'STME' ? 'selected' : ''; ?>>STME</option>
                                    <option value="SOL" <?php echo $selected_school === 'SOL' ? 'selected' : ''; ?>>SOL</option>
                                    <option value="SPTM" <?php echo $selected_school === 'SPTM' ? 'selected' : ''; ?>>SPTM</option>
                                    <option value="SBM" <?php echo $selected_school === 'SBM' ? 'selected' : ''; ?>>SBM</option>
                                    <option value="SOC" <?php echo $selected_school === 'SOC' ? 'selected' : ''; ?>>SOC</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Select the Class Name</label>
                                <input type="hidden" name="class_name" id="class_name_value" value="<?php echo htmlspecialchars($class_name_input ?? ''); ?>">
                                <div id="class-select-wrapper" style="display:none;">
                                    <select id="class-name-select">
                                        <option value="">Choose Class</option>
                                        <?php foreach ($stme_class_options as $option): ?>
                                            <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($selected_school === 'STME' && $class_name_input === $option) ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="class-input-wrapper" style="display:none;">
                                    <input type="text" id="class-name-input" placeholder="Enter class name" value="<?php echo ($selected_school !== 'STME' || !in_array($class_name_input, $stme_class_options, true)) ? htmlspecialchars($class_name_input ?? '') : ''; ?>" data-force-uppercase="true">
                                </div>
                                <small id="class-helper" style="display:block; margin-top:6px; color:#4c5264;">Select a school to continue.</small>
                            </div>
                            <div class="form-group">
                                <label>Which Semester will this class be associated with?</label>
                                <select name="semester" id="semester-select" required>
                                    <option value="">Select semester</option>
                                    <?php for ($semesterOption = 1; $semesterOption <= 10; $semesterOption++): ?>
                                        <option value="<?php echo $semesterOption; ?>" <?php echo ((int)$selected_semester === $semesterOption) ? 'selected' : ''; ?>><?php echo $semesterOption; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Assign the academic calendar timeline</label>
                                <select name="academic_term_id" id="academic-term-select" required>
                                    <option value="">Select timeline</option>
                                    <?php
                                    $initial_terms = $terms_by_school[$selected_school] ?? [];
                                    foreach ($initial_terms as $term_option) {
                                        $term_id = (int)$term_option['id'];
                                        $selected_attr = ($term_id === $selected_term_id) ? 'selected' : '';
                                        echo '<option value="' . $term_id . '" ' . $selected_attr . '>' . htmlspecialchars($term_option['label'], ENT_QUOTES, 'UTF-8') . '</option>';
                                    }
                                    ?>
                                </select>
                                <small id="timeline-helper" style="display:block; margin-top:6px; color:#4c5264;">Pick the calendar entry that matches this class.</small>
                            </div>
                            <div class="form-group">
                                <label>Divisions/Sections (optional)</label>
                                <input type="hidden" name="sections" id="sections-hidden" value="<?php echo htmlspecialchars($sections_input); ?>">
                                <select id="sections-select">
                                    <option value="">Select division (optional)</option>
                                    <?php foreach ($standard_sections as $sectionOption): ?>
                                        <option value="<?php echo $sectionOption; ?>" <?php echo $section_select_value === $sectionOption ? 'selected' : ''; ?>><?php echo $sectionOption; ?></option>
                                    <?php endforeach; ?>
                                    <option value="OTHER" <?php echo $section_select_value === 'OTHER' ? 'selected' : ''; ?>>Other</option>
                                </select>
                                <div id="sections-other-wrapper" style="margin-top:8px; <?php echo $section_select_value === 'OTHER' ? '' : 'display:none;'; ?>">
                                    <input type="text" id="sections-other-input" placeholder="Enter division or sections" value="<?php echo htmlspecialchars($section_other_value); ?>" data-force-uppercase="true">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Short description (optional)</label>
                                <input type="text" name="description" placeholder="Description (optional)" value="<?php echo htmlspecialchars($description_input); ?>" data-force-uppercase="true">
                            </div>
                            <button type="submit" class="btn">Create Class</button>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h5>Existing Classes</h5></div>
                    <div class="card-body">
                        <div class="term-filter-group" id="existing_classes_filters">
                            <button type="button" class="term-filter-button" data-filter="odd">Odd Term</button>
                            <button type="button" class="term-filter-button" data-filter="even">Even Term</button>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Class Name</th>
                                    <th>Department</th>
                                    <th>Semester</th>
                                    <th>Timeline</th>
                                    <th>Divisions</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($class = mysqli_fetch_assoc($classes_result)) {
                                    $timeline_label = 'Not linked';
                                    if (!empty($class['academic_term_id'])) {
                                        $label_parts = [];
                                        if (!empty($class['calendar_label_override'])) {
                                            $label_parts[] = $class['calendar_label_override'];
                                        } else {
                                            if (!empty($class['calendar_semester_number'])) {
                                                $label_parts[] = 'Semester ' . (int)$class['calendar_semester_number'];
                                            }
                                            if (!empty($class['calendar_semester_term'])) {
                                                $label_parts[] = ucfirst((string)$class['calendar_semester_term']) . ' Term';
                                            }
                                            if (!empty($class['calendar_academic_year'])) {
                                                $label_parts[] = 'AY ' . $class['calendar_academic_year'];
                                            }
                                        }
                                        if (!empty($class['calendar_start_date']) && !empty($class['calendar_end_date'])) {
                                            $label_parts[] = date('d M Y', strtotime($class['calendar_start_date'])) . ' - ' . date('d M Y', strtotime($class['calendar_end_date']));
                                        }
                                        $compiled_label = implode(' • ', array_filter($label_parts));
                                        if ($compiled_label !== '') {
                                            $timeline_label = $compiled_label;
                                        }
                                    }
                                    $class_terms = $terms_by_school[$class['school']] ?? [];
                                    $current_term_id = !empty($class['academic_term_id']) ? (int)$class['academic_term_id'] : 0;
                                    $semesterValue = $class['semester'] ?? '';
                                    $semesterDigits = preg_replace('/[^0-9]/', '', (string)$semesterValue);
                                    $semesterNumber = $semesterDigits !== '' ? (int)$semesterDigits : 0;
                                    $semesterParity = $semesterNumber > 0 ? (($semesterNumber % 2 === 0) ? 'even' : 'odd') : 'other';
                                    $divisions_raw = strtoupper(trim((string)($class['divisions'] ?? '')));
                                    $divisions_display = $divisions_raw !== '' ? $divisions_raw : 'N/A';
                                    $description_raw = strtoupper(trim((string)($class['description'] ?? '')));
                                    $description_display = $description_raw !== '' ? $description_raw : 'N/A';
                                ?>
                                    <tr data-semester-row data-parity="<?php echo htmlspecialchars($semesterParity); ?>">
                                        <td><?php echo htmlspecialchars(strtoupper($class['class_name'])); ?></td>
                                        <td><?php echo htmlspecialchars($class['school']); ?></td>
                                        <td><?php echo htmlspecialchars($class['semester']); ?></td>
                                        <td><?php echo htmlspecialchars($timeline_label); ?></td>
                                        <td><?php echo htmlspecialchars($divisions_display); ?></td>
                                        <td><?php echo htmlspecialchars($description_display); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <form method="POST" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                                    <input type="hidden" name="action" value="update_timeline">
                                                    <input type="hidden" name="class_id" value="<?php echo (int)$class['id']; ?>">
                                                    <select name="academic_term_id" style="min-width:180px; padding:6px 8px; border-radius:6px; border:1px solid #ccc;"
                                                        <?php echo empty($class_terms) ? 'disabled' : ''; ?>>
                                                        <option value="" <?php echo $current_term_id === 0 ? 'selected' : ''; ?>>Not linked</option>
                                                        <?php foreach ($class_terms as $term_option) {
                                                            $term_id = (int)$term_option['id'];
                                                            $selected_attr = ($term_id === $current_term_id) ? 'selected' : '';
                                                            echo '<option value="' . $term_id . '" ' . $selected_attr . '>' . htmlspecialchars($term_option['label'], ENT_QUOTES, 'UTF-8') . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                    <button type="submit" class="btn" style="padding:6px 12px; font-size:0.85rem;">Update</button>
                                                </form>
                                                <button type="button" class="btn secondary edit-class-btn" style="padding:6px 12px; font-size:0.85rem;"
                                                    data-class-id="<?php echo (int)$class['id']; ?>"
                                                    data-class-name="<?php echo htmlspecialchars(strtoupper($class['class_name']), ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-school="<?php echo htmlspecialchars($class['school'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-semester="<?php echo htmlspecialchars($class['semester'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-divisions="<?php echo htmlspecialchars($divisions_raw, ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-description="<?php echo htmlspecialchars($description_raw, ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-term-id="<?php echo $current_term_id; ?>"
                                                >Edit</button>
                                                <?php if (empty($class_terms)) { ?>
                                                    <small>Add a calendar for <?php echo htmlspecialchars($class['school']); ?> to enable linking.</small>
                                                <?php } ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="edit-class-modal" aria-hidden="true">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Edit Class</h3>
                <button type="button" class="modal-close" id="edit-class-close" aria-label="Close">&times;</button>
            </div>
            <form method="POST" id="edit-class-form">
                <input type="hidden" name="action" value="edit_class">
                <input type="hidden" name="class_id" id="edit-class-id">
                <div class="modal-body">
                    <div class="modal-field">
                        <label for="edit-class-name">Class Name</label>
                        <input type="text" id="edit-class-name" name="class_name" required data-force-uppercase="true">
                    </div>
                    <div class="modal-field">
                        <label for="edit-class-school">School</label>
                        <input type="text" id="edit-class-school" readonly>
                    </div>
                    <div class="modal-field">
                        <label for="edit-class-semester">Semester</label>
                        <select id="edit-class-semester" name="semester" required>
                            <option value="">Select semester</option>
                            <?php for ($semesterOption = 1; $semesterOption <= 10; $semesterOption++): ?>
                                <option value="<?php echo $semesterOption; ?>"><?php echo $semesterOption; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="modal-field">
                        <label for="edit-class-term">Timeline</label>
                        <select id="edit-class-term" name="academic_term_id">
                            <option value="">Not linked</option>
                        </select>
                        <small id="edit-timeline-helper"></small>
                    </div>
                    <div class="modal-field">
                        <label for="edit-class-sections-select">Divisions/Sections</label>
                        <input type="hidden" id="edit-class-sections-hidden" name="sections">
                        <select id="edit-class-sections-select">
                            <option value="">Select division (optional)</option>
                            <?php foreach ($standard_sections as $sectionOption): ?>
                                <option value="<?php echo $sectionOption; ?>"><?php echo $sectionOption; ?></option>
                            <?php endforeach; ?>
                            <option value="OTHER">Other</option>
                        </select>
                        <div id="edit-class-sections-other-wrapper" style="margin-top:8px; display:none;">
                            <input type="text" id="edit-class-sections-other" data-force-uppercase="true" placeholder="Enter division or sections">
                        </div>
                    </div>
                    <div class="modal-field">
                        <label for="edit-class-description">Description</label>
                        <input type="text" id="edit-class-description" name="description" data-force-uppercase="true" placeholder="Optional description">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn ghost" id="edit-class-cancel">Cancel</button>
                    <button type="submit" class="btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        }
        if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');

        (function () {
            const stmeClasses = <?php echo json_encode($stme_class_options); ?>;
            const classSemesterMap = <?php echo json_encode($stme_semester_map); ?>;
            const termsBySchool = <?php echo json_encode($terms_for_js); ?>;
            const schoolSelect = document.getElementById('school-select');
            const classSelectWrapper = document.getElementById('class-select-wrapper');
            const classInputWrapper = document.getElementById('class-input-wrapper');
            const classSelect = document.getElementById('class-name-select');
            const classInput = document.getElementById('class-name-input');
            const helper = document.getElementById('class-helper');
            const hiddenField = document.getElementById('class_name_value');
            const createForm = document.getElementById('create-class-form');
            const termSelect = document.getElementById('academic-term-select');
            const timelineHelper = document.getElementById('timeline-helper');
            const semesterSelect = document.getElementById('semester-select');
            const defaultSemesterOptions = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
            let rememberedSemester = <?php echo $selected_semester !== '' ? (int)$selected_semester : 'null'; ?>;
            const sectionsHidden = document.getElementById('sections-hidden');
            const sectionsSelect = document.getElementById('sections-select');
            const sectionsOtherWrapper = document.getElementById('sections-other-wrapper');
            const sectionsOtherInput = document.getElementById('sections-other-input');
            const standardSections = <?php echo json_encode($standard_sections); ?>;
            const editModal = document.getElementById('edit-class-modal');
            const editForm = document.getElementById('edit-class-form');
            const editButtons = Array.from(document.querySelectorAll('.edit-class-btn'));
            const editClose = document.getElementById('edit-class-close');
            const editCancel = document.getElementById('edit-class-cancel');
            const editClassId = document.getElementById('edit-class-id');
            const editClassName = document.getElementById('edit-class-name');
            const editClassSchool = document.getElementById('edit-class-school');
            const editClassSemester = document.getElementById('edit-class-semester');
            const editClassTerm = document.getElementById('edit-class-term');
            const editSectionsHidden = document.getElementById('edit-class-sections-hidden');
            const editSectionsSelect = document.getElementById('edit-class-sections-select');
            const editSectionsOtherWrapper = document.getElementById('edit-class-sections-other-wrapper');
            const editSectionsOtherInput = document.getElementById('edit-class-sections-other');
            const editClassDescription = document.getElementById('edit-class-description');
            const editTimelineHelper = document.getElementById('edit-timeline-helper');
            let rememberedTermId = <?php echo $selected_term_id > 0 ? (int)$selected_term_id : 'null'; ?>;

            if (!schoolSelect || !hiddenField || !createForm) {
                return;
            }

            function syncHiddenValue() {
                if (classSelectWrapper.style.display !== 'none') {
                    hiddenField.value = classSelect.value.trim().toUpperCase();
                } else if (classInputWrapper.style.display !== 'none') {
                    hiddenField.value = classInput.value.trim().toUpperCase();
                } else {
                    hiddenField.value = '';
                }
            }

            const uppercaseFields = document.querySelectorAll('[data-force-uppercase="true"]');
            uppercaseFields.forEach(function (field) {
                field.value = field.value.toUpperCase();
                if (field === classInput) {
                    syncHiddenValue();
                }
                if (field === sectionsOtherInput) {
                    syncSectionsHidden();
                }
                if (field === editSectionsOtherInput) {
                    syncEditSectionsHidden();
                }
            });
            uppercaseFields.forEach(function (field) {
                field.addEventListener('input', function () {
                    let start = null;
                    let end = null;
                    if (typeof field.selectionStart === 'number' && typeof field.selectionEnd === 'number') {
                        start = field.selectionStart;
                        end = field.selectionEnd;
                    }
                    field.value = field.value.toUpperCase();
                    if (start !== null && end !== null && typeof field.setSelectionRange === 'function' && field === document.activeElement) {
                        field.setSelectionRange(start, end);
                    }
                    if (field === classInput) {
                        syncHiddenValue();
                        updateCreateSemesterOptions(true);
                    }
                    if (field === sectionsOtherInput) {
                        syncSectionsHidden();
                    }
                    if (field === editSectionsOtherInput) {
                        syncEditSectionsHidden();
                    }
                });
            });

            function showHelper(message) {
                if (!helper) {
                    return;
                }
                helper.textContent = message;
            }

            function toIntOrNull(value) {
                const parsed = parseInt(value, 10);
                return Number.isNaN(parsed) ? null : parsed;
            }

            function setTimelineHelper(message) {
                if (!timelineHelper) {
                    return;
                }
                timelineHelper.textContent = message;
            }

            function getAllowedSemesters(school, className) {
                const normalizedSchool = (school || '').toString().trim().toUpperCase();
                if (normalizedSchool === 'STME') {
                    const normalizedClass = (className || '').toString().trim().toUpperCase();
                    if (normalizedClass && Object.prototype.hasOwnProperty.call(classSemesterMap, normalizedClass)) {
                        return classSemesterMap[normalizedClass];
                    }
                }
                return defaultSemesterOptions;
            }

            function rebuildSemesterOptions(selectElement, allowedOptions, preferredValue) {
                if (!selectElement) {
                    return null;
                }
                const optionsToRender = Array.isArray(allowedOptions) && allowedOptions.length ? allowedOptions : defaultSemesterOptions;
                const desiredValue = preferredValue !== undefined && preferredValue !== null ? preferredValue : toIntOrNull(selectElement.value);
                selectElement.innerHTML = '<option value="">Select semester</option>';
                let matched = false;
                optionsToRender.forEach(function(optionValue) {
                    const option = document.createElement('option');
                    option.value = String(optionValue);
                    option.textContent = String(optionValue);
                    if (!matched && desiredValue !== null && optionValue === desiredValue) {
                        option.selected = true;
                        matched = true;
                    }
                    selectElement.appendChild(option);
                });
                if (!matched) {
                    selectElement.selectedIndex = 0;
                    return null;
                }
                return desiredValue;
            }

            function updateCreateSemesterOptions(preserveSelection) {
                if (!semesterSelect) {
                    return;
                }
                const allowedSemesters = getAllowedSemesters(schoolSelect.value, hiddenField.value);
                const preferredValue = preserveSelection ? rememberedSemester : null;
                rememberedSemester = rebuildSemesterOptions(semesterSelect, allowedSemesters, preferredValue);
            }

            function syncSectionsHidden() {
                if (!sectionsHidden || !sectionsSelect) {
                    return;
                }
                const selection = sectionsSelect.value;
                if (!selection) {
                    sectionsHidden.value = '';
                    if (sectionsOtherWrapper) {
                        sectionsOtherWrapper.style.display = 'none';
                    }
                    if (sectionsOtherInput) {
                        sectionsOtherInput.value = '';
                    }
                    return;
                }
                if (selection === 'OTHER') {
                    if (sectionsOtherWrapper) {
                        sectionsOtherWrapper.style.display = '';
                    }
                    const otherValue = sectionsOtherInput ? sectionsOtherInput.value.trim().toUpperCase() : '';
                    sectionsHidden.value = otherValue;
                    return;
                }
                sectionsHidden.value = selection;
                if (sectionsOtherWrapper) {
                    sectionsOtherWrapper.style.display = 'none';
                }
                if (sectionsOtherInput) {
                    sectionsOtherInput.value = '';
                }
            }

            function syncEditSectionsHidden() {
                if (!editSectionsHidden || !editSectionsSelect) {
                    return;
                }
                const selection = editSectionsSelect.value;
                if (!selection) {
                    editSectionsHidden.value = '';
                    if (editSectionsOtherWrapper) {
                        editSectionsOtherWrapper.style.display = 'none';
                    }
                    if (editSectionsOtherInput) {
                        editSectionsOtherInput.value = '';
                    }
                    return;
                }
                if (selection === 'OTHER') {
                    if (editSectionsOtherWrapper) {
                        editSectionsOtherWrapper.style.display = '';
                    }
                    const otherValue = editSectionsOtherInput ? editSectionsOtherInput.value.trim().toUpperCase() : '';
                    editSectionsHidden.value = otherValue;
                    return;
                }
                editSectionsHidden.value = selection;
                if (editSectionsOtherWrapper) {
                    editSectionsOtherWrapper.style.display = 'none';
                }
                if (editSectionsOtherInput) {
                    editSectionsOtherInput.value = '';
                }
                syncEditSectionsHidden();
            }

            function populateTimelineOptions(currentSchool, preserveSelection) {
                if (!termSelect) {
                    return;
                }
                termSelect.innerHTML = '<option value="">Select timeline</option>';
                termSelect.disabled = true;

                if (!currentSchool) {
                    setTimelineHelper('Select a school to see available timelines.');
                    return;
                }

                const options = termsBySchool[currentSchool] || [];
                if (!options.length) {
                    setTimelineHelper('No timelines found. Please add an academic calendar for this school first.');
                    return;
                }

                options.forEach(function(option) {
                    const opt = document.createElement('option');
                    opt.value = String(option.id);
                    opt.textContent = option.label;
                    if (preserveSelection && rememberedTermId !== null && option.id === rememberedTermId) {
                        opt.selected = true;
                    }
                    termSelect.appendChild(opt);
                });

                if (preserveSelection && rememberedTermId !== null) {
                    const selectedExists = options.some(function(option) { return option.id === rememberedTermId; });
                    if (!selectedExists) {
                        termSelect.selectedIndex = 0;
                    }
                }

                termSelect.disabled = false;
                setTimelineHelper('Pick the calendar entry that matches this class.');
            }

            function handleSchoolChange() {
                const school = schoolSelect.value;
                if (school === '') {
                    classSelectWrapper.style.display = 'none';
                    classInputWrapper.style.display = 'none';
                    classSelect.required = false;
                    classInput.required = false;
                    classSelect.value = '';
                    classInput.value = '';
                    hiddenField.value = '';
                    showHelper('Select a school to continue.');
                    populateTimelineOptions('', false);
                    updateCreateSemesterOptions(true);
                    return;
                }

                if (school === 'STME') {
                    classSelectWrapper.style.display = 'block';
                    classInputWrapper.style.display = 'none';
                    classSelect.required = true;
                    classSelect.disabled = false;
                    classInput.required = false;
                    classInput.disabled = true;
                    if (!classSelect.value) {
                        classSelect.value = '';
                    } else if (!stmeClasses.includes(classSelect.value)) {
                        classSelect.value = '';
                    }
                    showHelper('Choose from the STME class list.');
                } else {
                    classSelectWrapper.style.display = 'none';
                    classInputWrapper.style.display = 'block';
                    classSelect.required = false;
                    classSelect.disabled = true;
                    classSelect.value = '';
                    classInput.required = true;
                    classInput.disabled = false;
                    showHelper('Enter a class name for ' + school + '.');
                }

                syncHiddenValue();
                updateCreateSemesterOptions(true);
                populateTimelineOptions(school, true);
                rememberedTermId = termSelect && termSelect.value ? toIntOrNull(termSelect.value) : null;
            }

            schoolSelect.addEventListener('change', handleSchoolChange);
            if (classSelect) {
                classSelect.addEventListener('change', function () {
                    syncHiddenValue();
                    updateCreateSemesterOptions(true);
                });
            }
            if (classInput) {
                classInput.addEventListener('input', function () {
                    syncHiddenValue();
                    updateCreateSemesterOptions(true);
                });
            }
            if (termSelect) {
                termSelect.addEventListener('change', function() {
                    rememberedTermId = termSelect.value ? toIntOrNull(termSelect.value) : null;
                });
            }
            if (semesterSelect) {
                semesterSelect.addEventListener('change', function () {
                    rememberedSemester = semesterSelect.value ? toIntOrNull(semesterSelect.value) : null;
                });
            }
            if (sectionsSelect) {
                sectionsSelect.addEventListener('change', function () {
                    syncSectionsHidden();
                    if (sectionsSelect.value === 'OTHER' && sectionsOtherInput) {
                        sectionsOtherInput.focus();
                    }
                });
            }
            if (sectionsOtherInput) {
                sectionsOtherInput.addEventListener('input', syncSectionsHidden);
            }

            createForm.addEventListener('submit', function (event) {
                syncHiddenValue();
                syncSectionsHidden();
                if (!hiddenField.value) {
                    event.preventDefault();
                    showHelper('Please provide a class name before creating the class.');
                    if (classSelectWrapper.style.display !== 'none') {
                        classSelect.focus();
                    } else if (classInputWrapper.style.display !== 'none') {
                        classInput.focus();
                    } else {
                        schoolSelect.focus();
                    }
                }
                if (termSelect && (!termSelect.value || termSelect.disabled)) {
                    event.preventDefault();
                    setTimelineHelper('Please choose a timeline before creating the class.');
                    termSelect.focus();
                }
            });

            // Initialize state based on previously submitted values
            const initialClassValue = hiddenField.value;
            handleSchoolChange();
            updateCreateSemesterOptions(true);

            if (initialClassValue) {
                if (schoolSelect.value === 'STME' && stmeClasses.includes(initialClassValue)) {
                    classSelect.value = initialClassValue;
                } else if (schoolSelect.value !== 'STME' && classInput) {
                    classInput.value = initialClassValue;
                }
            }
            syncHiddenValue();
            updateCreateSemesterOptions(true);
            if (termSelect && termSelect.value) {
                rememberedTermId = toIntOrNull(termSelect.value);
            }
            syncSectionsHidden();

            if (editSectionsSelect) {
                editSectionsSelect.addEventListener('change', function () {
                    syncEditSectionsHidden();
                    if (editSectionsSelect.value === 'OTHER' && editSectionsOtherInput) {
                        editSectionsOtherInput.focus();
                    }
                });
            }
            if (editSectionsOtherInput) {
                editSectionsOtherInput.addEventListener('input', syncEditSectionsHidden);
            }
            if (editClassName) {
                editClassName.addEventListener('input', function () {
                    if (!editClassSchool || !editClassSemester) {
                        return;
                    }
                    const currentSemester = toIntOrNull(editClassSemester.value);
                    setEditSemesterOptions(editClassSchool.value, editClassName.value, currentSemester);
                });
            }
            if (editForm) {
                editForm.addEventListener('submit', function () {
                    syncEditSectionsHidden();
                });
            }

            function setEditTimelineOptions(school, selectedId) {
                if (!editClassTerm || !editTimelineHelper) {
                    return;
                }
                editClassTerm.innerHTML = '<option value="">Not linked</option>';
                editClassTerm.disabled = true;
                if (!school) {
                    editTimelineHelper.textContent = 'School information is missing for this class.';
                    return;
                }
                const options = termsBySchool[school] || [];
                if (!options.length) {
                    editTimelineHelper.textContent = 'No timelines available for this school yet.';
                    return;
                }
                let matched = false;
                options.forEach(function(option) {
                    const opt = document.createElement('option');
                    opt.value = String(option.id);
                    opt.textContent = option.label;
                    if (selectedId !== null && option.id === selectedId) {
                        opt.selected = true;
                        matched = true;
                    }
                    editClassTerm.appendChild(opt);
                });
                editClassTerm.disabled = false;
                editTimelineHelper.textContent = matched || selectedId === null
                    ? 'Choose the academic calendar timeline for this class.'
                    : 'The previous timeline is unavailable. Please pick a new one.';
            }

            function setEditSemesterOptions(school, className, preferredValue) {
                if (!editClassSemester) {
                    return;
                }
                const preservedValue = preferredValue !== undefined && preferredValue !== null
                    ? preferredValue
                    : toIntOrNull(editClassSemester.value);
                const allowed = getAllowedSemesters(school, className);
                rebuildSemesterOptions(editClassSemester, allowed, preservedValue);
            }

            function closeEditModal() {
                if (!editModal) {
                    return;
                }
                editModal.classList.remove('active');
                editModal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
                if (editForm) {
                    editForm.reset();
                }
                if (editClassId) {
                    editClassId.value = '';
                }
                if (editClassSchool) {
                    editClassSchool.value = '';
                }
                if (editClassSemester) {
                    rebuildSemesterOptions(editClassSemester, defaultSemesterOptions, null);
                }
                if (editClassTerm) {
                    editClassTerm.innerHTML = '<option value="">Not linked</option>';
                    editClassTerm.disabled = true;
                }
                if (editTimelineHelper) {
                    editTimelineHelper.textContent = '';
                }
                if (editSectionsHidden) {
                    editSectionsHidden.value = '';
                }
                if (editSectionsSelect) {
                    editSectionsSelect.value = '';
                }
                if (editSectionsOtherWrapper) {
                    editSectionsOtherWrapper.style.display = 'none';
                }
                if (editSectionsOtherInput) {
                    editSectionsOtherInput.value = '';
                }
            }

            function openEditModal(button) {
                if (!editModal || !button) {
                    return;
                }
                const dataset = button.dataset || {};
                const school = dataset.school || '';
                const termIdRaw = dataset.termId || '';
                const parsedTermId = termIdRaw && termIdRaw !== '0' ? parseInt(termIdRaw, 10) : null;
                let parsedSemester = null;
                if (dataset.semester) {
                    const semesterNumeric = parseInt(dataset.semester, 10);
                    if (!Number.isNaN(semesterNumeric)) {
                        parsedSemester = semesterNumeric;
                    }
                }
                if (editClassId) {
                    editClassId.value = dataset.classId || '';
                }
                if (editClassName) {
                    const nameValue = (dataset.className || '').toUpperCase();
                    editClassName.value = nameValue;
                }
                if (editClassSchool) {
                    editClassSchool.value = school;
                }
                if (editClassSemester) {
                    const classNameForSemesters = editClassName ? editClassName.value : (dataset.className || '');
                    setEditSemesterOptions(school, classNameForSemesters, parsedSemester);
                }
                if (editSectionsHidden && editSectionsSelect && editSectionsOtherWrapper && editSectionsOtherInput) {
                    const divisionsValue = (dataset.divisions || '').trim().toUpperCase();
                    if (!divisionsValue || divisionsValue === 'N/A') {
                        editSectionsHidden.value = '';
                        editSectionsSelect.value = '';
                        editSectionsOtherWrapper.style.display = 'none';
                        editSectionsOtherInput.value = '';
                    } else if (standardSections.includes(divisionsValue)) {
                        editSectionsHidden.value = divisionsValue;
                        editSectionsSelect.value = divisionsValue;
                        editSectionsOtherWrapper.style.display = 'none';
                        editSectionsOtherInput.value = '';
                    } else {
                        editSectionsHidden.value = divisionsValue;
                        editSectionsSelect.value = 'OTHER';
                        editSectionsOtherWrapper.style.display = '';
                        editSectionsOtherInput.value = divisionsValue;
                    }
                    syncEditSectionsHidden();
                }
                if (editClassDescription) {
                    const descValue = (dataset.description || '').trim().toUpperCase();
                    editClassDescription.value = descValue === 'N/A' ? '' : descValue;
                }
                if (editClassTerm) {
                    setEditTimelineOptions(school, parsedTermId);
                    if (parsedTermId === null) {
                        editClassTerm.value = '';
                    }
                }
                if (parsedTermId === null && editClassTerm && editClassTerm.disabled && editTimelineHelper) {
                    editTimelineHelper.textContent = editTimelineHelper.textContent || 'No timelines available for this class yet.';
                }
                editModal.classList.add('active');
                editModal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
                if (editClassName) {
                    editClassName.focus();
                }
            }

            if (editButtons.length) {
                editButtons.forEach(function(button) {
                    button.addEventListener('click', function () {
                        openEditModal(button);
                    });
                });
            }
            if (editCancel) {
                editCancel.addEventListener('click', function () {
                    closeEditModal();
                });
            }
            if (editClose) {
                editClose.addEventListener('click', function () {
                    closeEditModal();
                });
            }
            if (editModal) {
                editModal.addEventListener('click', function (event) {
                    if (event.target === editModal) {
                        closeEditModal();
                    }
                });
            }
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && editModal && editModal.classList.contains('active')) {
                    closeEditModal();
                }
            });

            const classFilterButtons = Array.from(document.querySelectorAll('#existing_classes_filters .term-filter-button'));
            const classRows = Array.from(document.querySelectorAll('[data-semester-row]'));
            if (classFilterButtons.length && classRows.length) {
                let activeFilter = '';
                classFilterButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        const target = this.dataset.filter || '';
                        if (activeFilter === target) {
                            activeFilter = '';
                            classFilterButtons.forEach(function (btn) {
                                btn.classList.remove('active');
                            });
                        } else {
                            activeFilter = target;
                            classFilterButtons.forEach(function (btn) {
                                btn.classList.toggle('active', btn === button);
                            });
                        }
                        classRows.forEach(function (row) {
                            const parity = row.getAttribute('data-parity');
                            row.style.display = !activeFilter || parity === activeFilter ? '' : 'none';
                        });
                    });
                });
            }
        })();
    </script>
</body>
</html>
