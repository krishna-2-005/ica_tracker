<?php
session_start();
include 'db_connect.php';
require_once __DIR__ . '/includes/activity_logger.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

$userNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$userNameDisplay = $userNameRaw !== '' ? format_person_display($userNameRaw) : '';
$subject_name_value = '';
$subject_type_value = 'regular';
$theory_hours_value = '';
$practical_hours_value = '';
$tutorial_hours_value = '';
$include_practical_value = false;
$include_tutorial_value = false;
$elective_category_value = '';
$elective_number_value = '';
$elective_number_other_value = '';
$error = '';
$success = '';

$createSubjectDetailsTable = "CREATE TABLE IF NOT EXISTS subject_details (
    subject_id INT(11) NOT NULL,
    subject_type VARCHAR(20) NOT NULL DEFAULT 'regular',
    elective_category VARCHAR(20) DEFAULT NULL,
    elective_number VARCHAR(50) DEFAULT NULL,
    theory_hours INT(11) NOT NULL DEFAULT 0,
    practical_hours INT(11) NOT NULL DEFAULT 0,
    tutorial_hours INT(11) NOT NULL DEFAULT 0,
    tutorial_label VARCHAR(50) NOT NULL DEFAULT 'Practical',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
mysqli_query($conn, $createSubjectDetailsTable);

$hasElectiveCategory = mysqli_query($conn, "SHOW COLUMNS FROM subject_details LIKE 'elective_category'");
if ($hasElectiveCategory && mysqli_num_rows($hasElectiveCategory) === 0) {
    mysqli_query($conn, "ALTER TABLE subject_details ADD COLUMN elective_category VARCHAR(20) DEFAULT NULL AFTER subject_type");
}
if ($hasElectiveCategory) {
    mysqli_free_result($hasElectiveCategory);
}

$hasElectiveNumber = mysqli_query($conn, "SHOW COLUMNS FROM subject_details LIKE 'elective_number'");
if ($hasElectiveNumber && mysqli_num_rows($hasElectiveNumber) === 0) {
    mysqli_query($conn, "ALTER TABLE subject_details ADD COLUMN elective_number VARCHAR(50) DEFAULT NULL AFTER elective_category");
}
if ($hasElectiveNumber) {
    mysqli_free_result($hasElectiveNumber);
}

$hasTutorialHours = mysqli_query($conn, "SHOW COLUMNS FROM subject_details LIKE 'tutorial_hours'");
if ($hasTutorialHours && mysqli_num_rows($hasTutorialHours) === 0) {
    mysqli_query($conn, "ALTER TABLE subject_details ADD COLUMN tutorial_hours INT(11) NOT NULL DEFAULT 0 AFTER practical_hours");
}
if ($hasTutorialHours) {
    mysqli_free_result($hasTutorialHours);
}

$hasTutorialLabel = mysqli_query($conn, "SHOW COLUMNS FROM subject_details LIKE 'tutorial_label'");
$tutorialLabelExists = $hasTutorialLabel && mysqli_num_rows($hasTutorialLabel) > 0;
if ($hasTutorialLabel) {
    mysqli_free_result($hasTutorialLabel);
}
if (!$tutorialLabelExists) {
    $hasLegacyPracticalLabel = mysqli_query($conn, "SHOW COLUMNS FROM subject_details LIKE 'practical_label'");
    $legacyExists = $hasLegacyPracticalLabel && mysqli_num_rows($hasLegacyPracticalLabel) > 0;
    if ($hasLegacyPracticalLabel) {
        mysqli_free_result($hasLegacyPracticalLabel);
    }
    if ($legacyExists) {
        mysqli_query($conn, "ALTER TABLE subject_details CHANGE COLUMN practical_label tutorial_label VARCHAR(50) NOT NULL DEFAULT 'Practical'");
    } else {
        mysqli_query($conn, "ALTER TABLE subject_details ADD COLUMN tutorial_label VARCHAR(50) NOT NULL DEFAULT 'Practical' AFTER tutorial_hours");
    }
}

$createElectiveChoicesTable = "CREATE TABLE IF NOT EXISTS student_elective_choices (
    id INT(11) NOT NULL AUTO_INCREMENT,
    subject_id INT(11) NOT NULL,
    student_id INT(11) NOT NULL,
    class_id INT(11) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_student_class (student_id, class_id),
    KEY idx_subject (subject_id),
    KEY idx_class (class_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
mysqli_query($conn, $createElectiveChoicesTable);

$createSubjectClassMapTable = "CREATE TABLE IF NOT EXISTS subject_class_map (
    id INT(11) NOT NULL AUTO_INCREMENT,
    subject_id INT(11) NOT NULL,
    class_id INT(11) NOT NULL,
    section_id INT(11) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_subject (subject_id),
    KEY idx_class (class_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
mysqli_query($conn, $createSubjectClassMapTable);

$hasSubjectShortColumn = mysqli_query($conn, "SHOW COLUMNS FROM subjects LIKE 'short_name'");
$subjectsHasShortName = $hasSubjectShortColumn && mysqli_num_rows($hasSubjectShortColumn) > 0;
if ($hasSubjectShortColumn) {
    mysqli_free_result($hasSubjectShortColumn);
}
if (!$subjectsHasShortName) {
    mysqli_query($conn, "ALTER TABLE subjects ADD COLUMN short_name VARCHAR(20) DEFAULT NULL AFTER subject_name");
    $subjectsHasShortName = true;
}

if ($subjectsHasShortName) {
    $shortPopulateResult = mysqli_query($conn, "SELECT id, subject_name FROM subjects WHERE short_name IS NULL OR short_name = ''");
    if ($shortPopulateResult) {
        $updateShortStmt = mysqli_prepare($conn, "UPDATE subjects SET short_name = ? WHERE id = ?");
        if ($updateShortStmt) {
            $shortValue = '';
            $idValue = 0;
            mysqli_stmt_bind_param($updateShortStmt, "si", $shortValue, $idValue);
            while ($shortRow = mysqli_fetch_assoc($shortPopulateResult)) {
                $shortValue = derive_subject_short_name($shortRow['subject_name'] ?? '');
                $idValue = isset($shortRow['id']) ? (int)$shortRow['id'] : 0;
                mysqli_stmt_execute($updateShortStmt);
            }
            mysqli_stmt_close($updateShortStmt);
        }
        mysqli_free_result($shortPopulateResult);
    }
}

// Fetch classes to populate the dropdown
$classes_query = "SELECT id, class_name, semester, school FROM classes ORDER BY school, class_name, semester";
$classes_result = mysqli_query($conn, $classes_query);
$classes = [];
if ($classes_result) {
    while ($row = mysqli_fetch_assoc($classes_result)) {
        $classes[] = $row;
    }
    mysqli_free_result($classes_result);
}

$class_sections = [];
$section_lookup = [];
$sections_query = "SELECT id, class_id, section_name FROM sections ORDER BY section_name";
$sections_result = mysqli_query($conn, $sections_query);
if ($sections_result) {
    while ($section_row = mysqli_fetch_assoc($sections_result)) {
        $class_id_key = (string)$section_row['class_id'];
        if (!isset($class_sections[$class_id_key])) {
            $class_sections[$class_id_key] = [];
        }
        $class_sections[$class_id_key][] = [
            'id' => (int)$section_row['id'],
            'name' => $section_row['section_name'],
        ];
        $section_lookup[(int)$section_row['id']] = $section_row['section_name'];
    }
    mysqli_free_result($sections_result);
}

$class_option_labels = [];
$class_option_map = [];
$selected_class_option = '';
foreach ($classes as $class_row) {
    $class_id_key = (string)$class_row['id'];
    $sections_for_class = $class_sections[$class_id_key] ?? [];
    if (!empty($sections_for_class)) {
        foreach ($sections_for_class as $section_item) {
            $option_value = $class_row['id'] . ':' . $section_item['id'];
            $label = format_class_label($class_row['class_name'] ?? '', $section_item['name'] ?? '', $class_row['semester'] ?? '', $class_row['school'] ?? '');
            $class_option_map[$option_value] = [
                'class_id' => (int)$class_row['id'],
                'section_id' => (int)$section_item['id'],
                'label' => $label,
                'class_name' => $class_row['class_name'],
                'school' => $class_row['school'],
                'semester' => $class_row['semester'],
            ];
            $class_option_labels[] = $label;
        }
    } else {
        $option_value = $class_row['id'] . ':0';
        $label = format_class_label($class_row['class_name'] ?? '', '', $class_row['semester'] ?? '', $class_row['school'] ?? '');
        $class_option_map[$option_value] = [
            'class_id' => (int)$class_row['id'],
            'section_id' => 0,
            'label' => $label,
            'class_name' => $class_row['class_name'],
            'school' => $class_row['school'],
            'semester' => $class_row['semester'],
        ];
        $class_option_labels[] = $label;
    }
}
sort($class_option_labels, SORT_NATURAL);

$subject_class_labels = [];
$subject_class_labels_map = [];
$subject_primary_option_map = [];
$subject_class_map_query = "SELECT subject_id, class_id, COALESCE(section_id, 0) AS section_id FROM subject_class_map";
$subject_class_map_result = mysqli_query($conn, $subject_class_map_query);
if ($subject_class_map_result) {
    while ($link_row = mysqli_fetch_assoc($subject_class_map_result)) {
        $subject_id = isset($link_row['subject_id']) ? (int)$link_row['subject_id'] : 0;
        $class_id = isset($link_row['class_id']) ? (int)$link_row['class_id'] : 0;
        $section_id = isset($link_row['section_id']) ? (int)$link_row['section_id'] : 0;
        if ($subject_id <= 0 || $class_id <= 0) {
            continue;
        }
        $option_key = $class_id . ':' . $section_id;
        if (!isset($class_option_map[$option_key])) {
            continue;
        }
        $meta = $class_option_map[$option_key];
        if (!isset($subject_class_labels_map[$subject_id])) {
            $subject_class_labels_map[$subject_id] = [];
        }
        if (!in_array($meta['label'], $subject_class_labels_map[$subject_id], true)) {
            $subject_class_labels_map[$subject_id][] = $meta['label'];
        }
        if (!isset($subject_primary_option_map[$subject_id])) {
            $subject_primary_option_map[$subject_id] = $option_key;
        }
        if (!in_array($meta['label'], $subject_class_labels, true)) {
            $subject_class_labels[] = $meta['label'];
        }
    }
    mysqli_free_result($subject_class_map_result);
}

        function resolve_class_option_meta(array $class_option_map, string $selected_option): ?array {
            if ($selected_option === '' || !isset($class_option_map[$selected_option])) {
                return null;
            }
            $meta = $class_option_map[$selected_option];
            $meta['option_value'] = $selected_option;
            $meta['section_id'] = isset($meta['section_id']) ? (int)$meta['section_id'] : 0;
            $meta['class_id'] = isset($meta['class_id']) ? (int)$meta['class_id'] : 0;
            return $meta;
        }

        function fetch_subject_overview(mysqli $conn, int $subject_id): ?array {
            global $class_option_map;

            $sql = "
                SELECT s.id,
                       s.subject_name,
                      s.short_name,
                       s.semester,
                       s.school,
                       s.total_planned_hours,
                       COALESCE(sd.subject_type, 'regular') AS subject_type,
                       COALESCE(sd.elective_category, '') AS elective_category,
                       COALESCE(sd.elective_number, '') AS elective_number,
                       COALESCE(sd.theory_hours, 0) AS theory_hours,
                       COALESCE(sd.practical_hours, 0) AS practical_hours,
                       COALESCE(sd.tutorial_hours, 0) AS tutorial_hours
                FROM subjects s
                LEFT JOIN subject_details sd ON sd.subject_id = s.id
                WHERE s.id = ?
                LIMIT 1
            ";

            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                return null;
            }
            mysqli_stmt_bind_param($stmt, "i", $subject_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($result) ?: null;
            mysqli_free_result($result);
            mysqli_stmt_close($stmt);

            if ($data) {
                $data['theory_hours'] = isset($data['theory_hours']) ? (int)$data['theory_hours'] : 0;
                $data['practical_hours'] = isset($data['practical_hours']) ? (int)$data['practical_hours'] : 0;
                $data['tutorial_hours'] = isset($data['tutorial_hours']) ? (int)$data['tutorial_hours'] : 0;
                $data['total_planned_hours'] = isset($data['total_planned_hours']) ? (int)$data['total_planned_hours'] : ($data['theory_hours'] + $data['practical_hours'] + $data['tutorial_hours']);
                $data['practical_label'] = derive_contact_hours_label($data['practical_hours'], $data['tutorial_hours']);
                $data['contact_summary'] = format_contact_hours_summary($data['theory_hours'], $data['practical_hours'], $data['tutorial_hours']);
                $data['short_name'] = isset($data['short_name']) ? format_subject_display($data['short_name']) : '';
                $data['elective_category'] = isset($data['elective_category']) ? strtolower(trim((string)$data['elective_category'])) : '';
                $data['elective_number'] = isset($data['elective_number']) ? trim((string)$data['elective_number']) : '';
                $data['practical_label'] = $data['practical_label'] !== '' ? (string)$data['practical_label'] : 'Practical';

                $class_labels = [];
                $map_stmt = mysqli_prepare($conn, "SELECT class_id, COALESCE(section_id, 0) AS section_id FROM subject_class_map WHERE subject_id = ?");
                if ($map_stmt) {
                    mysqli_stmt_bind_param($map_stmt, "i", $subject_id);
                    mysqli_stmt_execute($map_stmt);
                    $map_result = mysqli_stmt_get_result($map_stmt);
                    if ($map_result) {
                        while ($map_row = mysqli_fetch_assoc($map_result)) {
                            $class_id = isset($map_row['class_id']) ? (int)$map_row['class_id'] : 0;
                            $section_id = isset($map_row['section_id']) ? (int)$map_row['section_id'] : 0;
                            if ($class_id <= 0) {
                                continue;
                            }
                            $option_key = $class_id . ':' . $section_id;
                            if (isset($class_option_map[$option_key])) {
                                $label = $class_option_map[$option_key]['label'];
                                if (!in_array($label, $class_labels, true)) {
                                    $class_labels[] = $label;
                                }
                            }
                        }
                        mysqli_free_result($map_result);
                    }
                    mysqli_stmt_close($map_stmt);
                }

                if (empty($class_labels)) {
                    $assign_stmt = mysqli_prepare($conn, "
                        SELECT DISTINCT
                            c.class_name,
                            c.semester,
                            c.school,
                            sec.section_name
                        FROM teacher_subject_assignments tsa
                        LEFT JOIN classes c ON c.id = tsa.class_id
                        LEFT JOIN sections sec ON sec.id = tsa.section_id
                        WHERE tsa.subject_id = ?
                    ");
                    if ($assign_stmt) {
                        mysqli_stmt_bind_param($assign_stmt, "i", $subject_id);
                        mysqli_stmt_execute($assign_stmt);
                        $assign_result = mysqli_stmt_get_result($assign_stmt);
                        if ($assign_result) {
                            while ($assign_row = mysqli_fetch_assoc($assign_result)) {
                                $label = format_class_label(
                                    $assign_row['class_name'] ?? '',
                                    $assign_row['section_name'] ?? '',
                                    $assign_row['semester'] ?? '',
                                    $assign_row['school'] ?? ''
                                );
                                if ($label !== '' && !in_array($label, $class_labels, true)) {
                                    $class_labels[] = $label;
                                }
                            }
                            mysqli_free_result($assign_result);
                        }
                        mysqli_stmt_close($assign_stmt);
                    }
                }

                $data['class_division_list'] = !empty($class_labels) ? implode('||', $class_labels) : '';
            }
            return $data;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_subject') {
            header('Content-Type: application/json');

            $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
            if ($subject_id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid subject selected.']);
                exit;
            }

            $subject_snapshot = fetch_subject_overview($conn, $subject_id);
            if (!$subject_snapshot) {
                echo json_encode(['status' => 'error', 'message' => 'Subject not found.']);
                exit;
            }

            mysqli_begin_transaction($conn);
            try {
                $delete_assignments_stmt = mysqli_prepare($conn, "DELETE FROM assignments WHERE subject_id = ?");
                if ($delete_assignments_stmt) {
                    mysqli_stmt_bind_param($delete_assignments_stmt, "i", $subject_id);
                    mysqli_stmt_execute($delete_assignments_stmt);
                    mysqli_stmt_close($delete_assignments_stmt);
                }

                $delete_electives_stmt = mysqli_prepare($conn, "DELETE FROM student_elective_choices WHERE subject_id = ?");
                if ($delete_electives_stmt) {
                    mysqli_stmt_bind_param($delete_electives_stmt, "i", $subject_id);
                    mysqli_stmt_execute($delete_electives_stmt);
                    mysqli_stmt_close($delete_electives_stmt);
                }

                $delete_class_map_stmt = mysqli_prepare($conn, "DELETE FROM subject_class_map WHERE subject_id = ?");
                if ($delete_class_map_stmt) {
                    mysqli_stmt_bind_param($delete_class_map_stmt, "i", $subject_id);
                    mysqli_stmt_execute($delete_class_map_stmt);
                    mysqli_stmt_close($delete_class_map_stmt);
                }

                $delete_details_stmt = mysqli_prepare($conn, "DELETE FROM subject_details WHERE subject_id = ?");
                if ($delete_details_stmt) {
                    mysqli_stmt_bind_param($delete_details_stmt, "i", $subject_id);
                    mysqli_stmt_execute($delete_details_stmt);
                    mysqli_stmt_close($delete_details_stmt);
                }

                $delete_subject_stmt = mysqli_prepare($conn, "DELETE FROM subjects WHERE id = ?");
                if (!$delete_subject_stmt) {
                    throw new Exception('Failed to prepare subject deletion statement.');
                }
                mysqli_stmt_bind_param($delete_subject_stmt, "i", $subject_id);
                mysqli_stmt_execute($delete_subject_stmt);
                if (mysqli_stmt_affected_rows($delete_subject_stmt) <= 0) {
                    mysqli_stmt_close($delete_subject_stmt);
                    throw new Exception('Subject could not be deleted.');
                }
                mysqli_stmt_close($delete_subject_stmt);

                mysqli_commit($conn);
            } catch (Throwable $delete_error) {
                mysqli_rollback($conn);
                echo json_encode(['status' => 'error', 'message' => 'Unable to delete subject right now.']);
                exit;
            }

            if (isset($_SESSION['user_id'])) {
                $classLabels = [];
                if (!empty($subject_snapshot['class_division_list'])) {
                    $classLabels = array_filter(array_map('trim', explode('||', $subject_snapshot['class_division_list'])));
                }
                $objectLabel = $subject_snapshot['subject_name'] ?? '';
                $primaryClassLabel = $classLabels[0] ?? null;
                if ($objectLabel !== '' && $primaryClassLabel) {
                    $objectLabel .= ' | ' . $primaryClassLabel;
                }

                log_activity($conn, [
                    'actor_id' => (int)$_SESSION['user_id'],
                    'event_type' => 'subject_deleted',
                    'event_label' => 'Subject deleted',
                    'description' => 'Subject removed via admin interface.',
                    'object_type' => 'subject',
                    'object_id' => (string)$subject_id,
                    'object_label' => $objectLabel,
                    'metadata' => [
                        'subject_id' => $subject_id,
                        'subject_name' => $subject_snapshot['subject_name'] ?? null,
                        'school' => $subject_snapshot['school'] ?? null,
                        'semester' => $subject_snapshot['semester'] ?? null,
                        'class_labels' => $classLabels,
                        'total_planned_hours' => $subject_snapshot['total_planned_hours'] ?? null,
                        'subject_type' => $subject_snapshot['subject_type'] ?? null,
                    ],
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ]);
            }

            echo json_encode([
                'status' => 'ok',
                'data' => [
                    'subject_id' => $subject_id,
                    'subject_name' => $subject_snapshot['subject_name'] ?? '',
                ]
            ]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_subject_inline') {
            header('Content-Type: application/json');

            $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
            $subject_name_input = isset($_POST['subject_name']) ? trim((string)$_POST['subject_name']) : '';
            $subject_type_input = isset($_POST['subject_type']) ? strtolower(trim((string)$_POST['subject_type'])) : 'regular';
            $theory_hours = isset($_POST['theory_hours']) ? max(0, (int)$_POST['theory_hours']) : 0;
            $practical_hours = isset($_POST['practical_hours']) ? max(0, (int)$_POST['practical_hours']) : 0;
            $tutorial_hours = isset($_POST['tutorial_hours']) ? max(0, (int)$_POST['tutorial_hours']) : 0;
            $class_option = isset($_POST['class_option']) ? trim((string)$_POST['class_option']) : '';
            $elective_category_input = isset($_POST['elective_category']) ? strtolower(trim((string)$_POST['elective_category'])) : '';
            $elective_number_input = isset($_POST['elective_number']) ? trim((string)$_POST['elective_number']) : '';

            if ($subject_id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid subject selected.']);
                exit;
            }
            if ($subject_name_input === '') {
                echo json_encode(['status' => 'error', 'message' => 'Subject name cannot be empty.']);
                exit;
            }

            $subject_short_name = derive_subject_short_name($subject_name_input);
            $subject_short_name_raw = $subject_short_name;

            $class_meta = resolve_class_option_meta($class_option_map, $class_option);
            if (!$class_meta || $class_meta['class_id'] <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Please choose a valid class/division.']);
                exit;
            }

            $subject_type = in_array($subject_type_input, ['regular', 'elective'], true) ? $subject_type_input : 'regular';
            $total_hours = $theory_hours + $practical_hours + $tutorial_hours;
            if ($total_hours <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Planned hours must include at least theory, practical, or tutorial hours.']);
                exit;
            }

            $existing_details = ['elective_category' => null, 'elective_number' => null];
            $details_lookup_stmt = mysqli_prepare($conn, "SELECT elective_category, elective_number FROM subject_details WHERE subject_id = ? LIMIT 1");
            if ($details_lookup_stmt) {
                mysqli_stmt_bind_param($details_lookup_stmt, "i", $subject_id);
                mysqli_stmt_execute($details_lookup_stmt);
                $details_res = mysqli_stmt_get_result($details_lookup_stmt);
                if ($details_res) {
                    $existing_details_row = mysqli_fetch_assoc($details_res);
                    if ($existing_details_row) {
                        $existing_details['elective_category'] = $existing_details_row['elective_category'];
                        $existing_details['elective_number'] = $existing_details_row['elective_number'];
                        // legacy contact-hour labels no longer required
                    }
                    mysqli_free_result($details_res);
                }
                mysqli_stmt_close($details_lookup_stmt);
            }

            if ($subject_type === 'elective' && strlen($elective_number_input) > 50) {
                echo json_encode(['status' => 'error', 'message' => 'Elective number description should be 50 characters or fewer.']);
                exit;
            }

            $elective_category = null;
            $elective_number = null;
            if ($subject_type === 'elective') {
                if (in_array($elective_category_input, ['open', 'departmental'], true)) {
                    $elective_category = $elective_category_input;
                } elseif (!empty($existing_details['elective_category'])) {
                    $elective_category = $existing_details['elective_category'];
                }

                if ($elective_number_input !== '') {
                    $elective_number = $elective_number_input;
                } elseif (!empty($existing_details['elective_number'])) {
                    $elective_number = $existing_details['elective_number'];
                }
            }

            $resolved_practical_label = derive_contact_hours_label($practical_hours, $tutorial_hours);

            $subject_stmt = mysqli_prepare($conn, "SELECT subject_name, short_name FROM subjects WHERE id = ?");
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
                echo json_encode(['status' => 'error', 'message' => 'Subject not found.']);
                exit;
            }

            $old_subject_name = $subject_row['subject_name'];
            $class_id = (int)$class_meta['class_id'];
            $section_id = $class_meta['section_id'] > 0 ? (int)$class_meta['section_id'] : null;
            $semester = $class_meta['semester'];
            $school = $class_meta['school'];

            $dup_stmt = mysqli_prepare($conn, "SELECT id FROM subjects WHERE subject_name = ? AND semester = ? AND school = ? AND id <> ? LIMIT 1");
            if ($dup_stmt) {
                mysqli_stmt_bind_param($dup_stmt, "sssi", $subject_name_input, $semester, $school, $subject_id);
                mysqli_stmt_execute($dup_stmt);
                mysqli_stmt_store_result($dup_stmt);
                if (mysqli_stmt_num_rows($dup_stmt) > 0) {
                    mysqli_stmt_close($dup_stmt);
                    echo json_encode(['status' => 'error', 'message' => 'Another subject with the same name already exists for this semester and school.']);
                    exit;
                }
                mysqli_stmt_close($dup_stmt);
            }

            mysqli_begin_transaction($conn);
            try {
                $update_subject_stmt = mysqli_prepare($conn, "UPDATE subjects SET subject_name = ?, short_name = ?, semester = ?, school = ?, total_planned_hours = ? WHERE id = ?");
                if (!$update_subject_stmt) {
                    throw new Exception('Failed to prepare subject update statement.');
                }
                mysqli_stmt_bind_param($update_subject_stmt, "ssssii", $subject_name_input, $subject_short_name, $semester, $school, $total_hours, $subject_id);
                mysqli_stmt_execute($update_subject_stmt);
                mysqli_stmt_close($update_subject_stmt);

                $details_stmt = mysqli_prepare($conn, "REPLACE INTO subject_details (subject_id, subject_type, elective_category, elective_number, theory_hours, practical_hours, tutorial_hours) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if (!$details_stmt) {
                    throw new Exception('Failed to prepare details update statement.');
                }
                mysqli_stmt_bind_param($details_stmt, "isssiii", $subject_id, $subject_type, $elective_category, $elective_number, $theory_hours, $practical_hours, $tutorial_hours);
                mysqli_stmt_execute($details_stmt);
                mysqli_stmt_close($details_stmt);

                $map_stmt = mysqli_prepare($conn, "SELECT id FROM subject_class_map WHERE subject_id = ? ORDER BY id ASC LIMIT 1");
                if (!$map_stmt) {
                    throw new Exception('Failed to prepare class map lookup.');
                }
                mysqli_stmt_bind_param($map_stmt, "i", $subject_id);
                mysqli_stmt_execute($map_stmt);
                $map_res = mysqli_stmt_get_result($map_stmt);
                $map_row = mysqli_fetch_assoc($map_res);
                mysqli_free_result($map_res);
                mysqli_stmt_close($map_stmt);

                if ($map_row) {
                    $update_map_stmt = mysqli_prepare($conn, "UPDATE subject_class_map SET class_id = ?, section_id = ? WHERE id = ?");
                    if (!$update_map_stmt) {
                        throw new Exception('Failed to prepare class map update.');
                    }
                    $section_param = $section_id ?? null;
                    mysqli_stmt_bind_param($update_map_stmt, "iii", $class_id, $section_param, $map_row['id']);
                    mysqli_stmt_execute($update_map_stmt);
                    mysqli_stmt_close($update_map_stmt);
                } else {
                    $insert_map_stmt = mysqli_prepare($conn, "INSERT INTO subject_class_map (subject_id, class_id, section_id) VALUES (?, ?, ?)");
                    if (!$insert_map_stmt) {
                        throw new Exception('Failed to prepare class map insert.');
                    }
                    $section_param = $section_id ?? null;
                    mysqli_stmt_bind_param($insert_map_stmt, "iii", $subject_id, $class_id, $section_param);
                    mysqli_stmt_execute($insert_map_stmt);
                    mysqli_stmt_close($insert_map_stmt);
                }

                if ($old_subject_name !== $subject_name_input) {
                    $update_teacher_subjects_stmt = mysqli_prepare($conn, "UPDATE teacher_subjects ts JOIN teacher_subject_assignments tsa ON tsa.teacher_id = ts.teacher_id SET ts.subject_name = ?, ts.total_planned_hours = ? WHERE tsa.subject_id = ? AND ts.subject_name = ?");
                    if ($update_teacher_subjects_stmt) {
                        mysqli_stmt_bind_param($update_teacher_subjects_stmt, "siis", $subject_name_input, $total_hours, $subject_id, $old_subject_name);
                        mysqli_stmt_execute($update_teacher_subjects_stmt);
                        mysqli_stmt_close($update_teacher_subjects_stmt);
                    }
                } else {
                    $update_teacher_hours_stmt = mysqli_prepare($conn, "UPDATE teacher_subjects ts JOIN teacher_subject_assignments tsa ON tsa.teacher_id = ts.teacher_id SET ts.total_planned_hours = ? WHERE tsa.subject_id = ? AND ts.subject_name = ?");
                    if ($update_teacher_hours_stmt) {
                        mysqli_stmt_bind_param($update_teacher_hours_stmt, "iis", $total_hours, $subject_id, $subject_name_input);
                        mysqli_stmt_execute($update_teacher_hours_stmt);
                        mysqli_stmt_close($update_teacher_hours_stmt);
                    }
                }

                mysqli_commit($conn);
            } catch (Exception $ex) {
                mysqli_rollback($conn);
                echo json_encode(['status' => 'error', 'message' => $ex->getMessage()]);
                exit;
            }

            $refreshed = fetch_subject_overview($conn, $subject_id);
            $class_label = $class_meta['label'];
            $class_labels = [];
            if ($refreshed && !empty($refreshed['class_division_list'])) {
                $class_labels = array_filter(array_map('trim', explode('||', $refreshed['class_division_list'])));
            } elseif ($class_label !== '') {
                $class_labels = [$class_label];
            }

            echo json_encode([
                'status' => 'ok',
                'data' => [
                    'subject_id' => $subject_id,
                    'subject_name' => format_subject_display($subject_name_input),
                    'subject_name_raw' => $subject_name_input,
                    'subject_short_name' => format_subject_display($subject_short_name_raw),
                    'subject_type' => $subject_type,
                    'elective_category' => $elective_category ?? '',
                    'elective_number' => $elective_number ?? '',
                    'theory_hours' => $theory_hours,
                    'practical_hours' => $practical_hours,
                    'tutorial_hours' => $tutorial_hours,
                    'practical_label' => $resolved_practical_label,
                    'contact_summary' => format_contact_hours_summary($theory_hours, $practical_hours, $tutorial_hours),
                    'total_hours' => $total_hours,
                    'semester' => $semester,
                    'school' => $school,
                    'class_option' => $class_meta['option_value'],
                    'class_label' => $class_label,
                    'class_labels' => $class_labels,
                    'class_labels_attr' => strtolower(implode('||', $class_labels)),
                ],
            ]);
            exit;
        }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject_name_input = isset($_POST['subject_name']) ? trim((string)$_POST['subject_name']) : '';
    $subject_name = mysqli_real_escape_string($conn, $subject_name_input);
    $subject_short_name_raw = derive_subject_short_name($subject_name_input);
    $selected_option = isset($_POST['class_id']) ? $_POST['class_id'] : '';
    $subject_type_input = isset($_POST['subject_type']) ? strtolower(trim((string)$_POST['subject_type'])) : 'regular';
    $subject_type = in_array($subject_type_input, ['regular', 'elective'], true) ? $subject_type_input : 'regular';
    $elective_category_input = isset($_POST['elective_category']) ? strtolower(trim((string)$_POST['elective_category'])) : '';
    $elective_number_input = isset($_POST['elective_number']) ? trim((string)$_POST['elective_number']) : '';
    $elective_number_other_input = isset($_POST['elective_number_other']) ? trim((string)$_POST['elective_number_other']) : '';
    $theory_hours = isset($_POST['theory_hours']) ? max(0, (int)$_POST['theory_hours']) : 0;
    $practical_hours_raw = isset($_POST['practical_hours']) ? max(0, (int)$_POST['practical_hours']) : 0;
    $tutorial_hours_raw = isset($_POST['tutorial_hours']) ? max(0, (int)$_POST['tutorial_hours']) : 0;
    $include_practical_value = isset($_POST['include_practical']) || $practical_hours_raw > 0;
    $include_tutorial_value = isset($_POST['include_tutorial']) || $tutorial_hours_raw > 0;
    $practical_hours = $include_practical_value ? $practical_hours_raw : 0;
    $tutorial_hours = $include_tutorial_value ? $tutorial_hours_raw : 0;
    $total_planned_hours = $theory_hours + $practical_hours + $tutorial_hours;
    $selected_class_option = $selected_option;
    $subject_name_value = $subject_name_input;
    $subject_type_value = $subject_type;
    $theory_hours_value = $theory_hours;
    $practical_hours_value = $include_practical_value ? $practical_hours : '';
    $tutorial_hours_value = $include_tutorial_value ? $tutorial_hours : '';
    $elective_category_value = $elective_category_input;
    $elective_number_value = $elective_number_input;
    $elective_number_other_value = $elective_number_other_input;

    $elective_category = null;
    $elective_number = null;

    if ($subject_name_input === '') {
        $error = "Please enter a subject name.";
    }

    if ($error === '' && $total_planned_hours <= 0) {
        $error = "Please enter theory, practical, or tutorial hours for the subject.";
    }

    if ($subject_type === 'elective') {
        if ($error === '') {
            $validCategories = ['open', 'departmental'];
            if (!in_array($elective_category_input, $validCategories, true)) {
                $error = "Please specify if this elective is open or departmental.";
            } else {
                $elective_category = $elective_category_input;
            }
        }

        if ($error === '') {
            $allowedNumbers = ['1', '2', '3', '4', 'other'];
            $elective_number_key = strtolower($elective_number_input);
            if ($elective_number_key === '') {
                $error = "Please select an elective number.";
            } elseif (!in_array($elective_number_key, $allowedNumbers, true)) {
                $error = "Please choose a valid elective number option.";
            } elseif ($elective_number_key === 'other') {
                if ($elective_number_other_input === '') {
                    $error = "Enter the elective number when selecting Other.";
                } elseif (strlen($elective_number_other_input) > 50) {
                    $error = "Elective number description should be 50 characters or fewer.";
                } else {
                    $elective_number = $elective_number_other_input;
                }
            } else {
                $elective_number = $elective_number_key;
            }
            $elective_number_value = $elective_number_key;
        }
    } else {
        $elective_category_value = '';
        $elective_number_value = '';
        $elective_number_other_value = '';
    }

    $class_id = 0;
    $section_id = 0;
    if ($selected_option !== '' && isset($class_option_map[$selected_option])) {
        $class_id = $class_option_map[$selected_option]['class_id'];
        $section_id = $class_option_map[$selected_option]['section_id'];
    }

    $semester = null;
    $school = null;

    if ($class_id <= 0) {
        $error = "Please select a valid class.";
    }

    // Get semester and department from the selected class ID
    if ($error === '' && $class_id > 0) {
        $class_details_query = "SELECT semester, school FROM classes WHERE id = ?";
        $stmt_class = mysqli_prepare($conn, $class_details_query);
        mysqli_stmt_bind_param($stmt_class, "i", $class_id);
        mysqli_stmt_execute($stmt_class);
        mysqli_stmt_bind_result($stmt_class, $semester, $school);
        mysqli_stmt_fetch($stmt_class);
        mysqli_stmt_close($stmt_class);
    }
    if ($error === '' && $semester && $school) {
        // Check if this subject already exists for the derived semester and department
        $check_query = "SELECT id FROM subjects WHERE subject_name = ? AND semester = ? AND school = ?";
        $stmt_check = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt_check, "sss", $subject_name, $semester, $school);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);

        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $error = "This subject already exists for the selected class's semester and department.";
        } else {
            // Insert the new subject
            $query = "INSERT INTO subjects (subject_name, short_name, semester, school, total_planned_hours) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssssi", $subject_name, $subject_short_name_raw, $semester, $school, $total_planned_hours);
            if (mysqli_stmt_execute($stmt)) {
                $new_subject_id = mysqli_insert_id($conn);

                if ($class_id > 0) {
                    $section_value_sql = ($section_id > 0) ? (string)(int)$section_id : 'NULL';
                    $map_sql = "INSERT INTO subject_class_map (subject_id, class_id, section_id) VALUES (" . (int)$new_subject_id . ", " . (int)$class_id . ", " . $section_value_sql . ")";
                    mysqli_query($conn, $map_sql);
                }

                $details_stmt = mysqli_prepare($conn, "REPLACE INTO subject_details (subject_id, subject_type, elective_category, elective_number, theory_hours, practical_hours, tutorial_hours) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($details_stmt) {
                    mysqli_stmt_bind_param($details_stmt, "isssiii", $new_subject_id, $subject_type, $elective_category, $elective_number, $theory_hours, $practical_hours, $tutorial_hours);
                    mysqli_stmt_execute($details_stmt);
                    mysqli_stmt_close($details_stmt);
                }

                $success = "Subject added successfully!";
                if ($subject_type === 'elective') {
                    $assign_link = 'manage_electives.php?class_id=' . (int)$class_id . '&subject_id=' . (int)$new_subject_id;
                    $success .= " <a href='" . htmlspecialchars($assign_link, ENT_QUOTES, 'UTF-8') . "'>Assign students to this elective</a>.";
                }
                if ($section_id > 0 && isset($section_lookup[$section_id])) {
                    $success .= " Division selected: " . htmlspecialchars($section_lookup[$section_id]);
                }

                $new_option_key = $class_id . ':' . ($section_id > 0 ? $section_id : 0);
                if (isset($class_option_map[$new_option_key])) {
                    $new_label = $class_option_map[$new_option_key]['label'];
                    $subject_class_labels_map[$new_subject_id] = [$new_label];
                    $subject_primary_option_map[$new_subject_id] = $new_option_key;
                }

                $logClassLabel = isset($class_option_map[$new_option_key]) ? $class_option_map[$new_option_key]['label'] : null;
                $objectLabel = $subject_name;
                if ($logClassLabel) {
                    $objectLabel .= ' | ' . $logClassLabel;
                }

                if (isset($_SESSION['user_id'])) {
                    log_activity($conn, [
                        'actor_id' => (int)$_SESSION['user_id'],
                        'event_type' => 'subject_created',
                        'event_label' => 'Subject created',
                        'description' => 'Subject created via admin interface.',
                        'object_type' => 'subject',
                        'object_id' => (string)$new_subject_id,
                        'object_label' => $objectLabel,
                        'metadata' => [
                            'subject_id' => $new_subject_id,
                            'subject_name' => $subject_name,
                            'short_name' => $subject_short_name_raw,
                            'subject_type' => $subject_type,
                            'school' => $school,
                            'semester' => $semester,
                            'class_id' => $class_id,
                            'section_id' => $section_id,
                            'class_label' => $logClassLabel,
                            'theory_hours' => $theory_hours,
                            'practical_hours' => $practical_hours,
                            'tutorial_hours' => $tutorial_hours,
                            'total_planned_hours' => $total_planned_hours,
                            'elective_category' => $elective_category,
                            'elective_number' => $elective_number,
                        ],
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    ]);
                }

                $subject_name_value = '';
                $selected_class_option = '';
                $subject_type_value = 'regular';
                $theory_hours_value = '';
                $practical_hours_value = '';
                $tutorial_hours_value = '';
                $include_practical_value = false;
                $include_tutorial_value = false;
                $elective_category_value = '';
                $elective_number_value = '';
                $elective_number_other_value = '';
            } else {
                $error = "Error creating subject: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_stmt_close($stmt_check);
    } elseif ($error === '') {
        $error = "Invalid class selected. Could not determine semester and department.";
    }
}

$subjects_list = [];
$subject_schools = [];
$subject_semesters = [];
$subject_class_labels = [];
$subjects_query = "
    SELECT s.id,
        s.subject_name,
        s.short_name,
        s.semester,
        s.school,
        s.total_planned_hours,
        COALESCE(sd.subject_type, 'regular') AS subject_type,
        COALESCE(sd.elective_category, '') AS elective_category,
        COALESCE(sd.elective_number, '') AS elective_number,
        COALESCE(sd.theory_hours, 0) AS theory_hours,
        COALESCE(sd.practical_hours, 0) AS practical_hours,
        COALESCE(sd.tutorial_hours, 0) AS tutorial_hours,
        COALESCE(MAX(COALESCE(scm.class_id, 0)), 0) AS primary_class_id,
        COALESCE(MAX(COALESCE(scm.section_id, 0)), 0) AS primary_section_id,
           COALESCE(GROUP_CONCAT(DISTINCT c.class_name ORDER BY c.class_name SEPARATOR ', '), '') AS class_list,
           COALESCE(GROUP_CONCAT(DISTINCT CONCAT_WS('::', c.class_name, COALESCE(sec.section_name, ''), COALESCE(c.semester, ''), COALESCE(c.school, '')) ORDER BY c.class_name, sec.section_name SEPARATOR '||'), '') AS class_division_meta,
           COALESCE(GROUP_CONCAT(DISTINCT sec.section_name ORDER BY sec.section_name SEPARATOR ', '), '') AS division_list
    FROM subjects s
    LEFT JOIN subject_details sd ON sd.subject_id = s.id
    LEFT JOIN subject_class_map scm ON scm.subject_id = s.id
    LEFT JOIN teacher_subject_assignments tsa ON s.id = tsa.subject_id
    LEFT JOIN classes c ON c.id = tsa.class_id
    LEFT JOIN sections sec ON sec.id = tsa.section_id
    GROUP BY s.id, s.subject_name, s.short_name, s.semester, s.school, s.total_planned_hours, sd.subject_type, sd.elective_category, sd.elective_number, sd.theory_hours, sd.practical_hours, sd.tutorial_hours
    ORDER BY s.school, s.subject_name";
$subjects_result = mysqli_query($conn, $subjects_query);
if ($subjects_result) {
    while ($row = mysqli_fetch_assoc($subjects_result)) {
        $subject_id_val = isset($row['id']) ? (int)$row['id'] : 0;
        $original_subject_name = isset($row['subject_name']) ? (string)$row['subject_name'] : '';
        $original_short_name = isset($row['short_name']) ? (string)$row['short_name'] : '';
        $row['subject_name_original'] = $original_subject_name;
        $row['subject_name'] = format_subject_display($original_subject_name);
        $row['short_name_original'] = $original_short_name;
        $row['short_name'] = $original_short_name !== '' ? format_subject_display($original_short_name) : '';

        if (!in_array($row['school'], $subject_schools, true)) {
            $subject_schools[] = $row['school'];
        }
        $semester_key = (string)$row['semester'];
        if ($semester_key !== '' && !in_array($semester_key, $subject_semesters, true)) {
            $subject_semesters[] = $semester_key;
        }
        $row['primary_class_id'] = isset($row['primary_class_id']) ? (int)$row['primary_class_id'] : 0;
        $row['primary_section_id'] = isset($row['primary_section_id']) ? (int)$row['primary_section_id'] : 0;
        $row['elective_category'] = isset($row['elective_category']) ? strtolower((string)$row['elective_category']) : '';
        $row['elective_number'] = isset($row['elective_number']) ? trim((string)$row['elective_number']) : '';
        $row['theory_hours'] = isset($row['theory_hours']) ? (int)$row['theory_hours'] : 0;
        $row['practical_hours'] = isset($row['practical_hours']) ? (int)$row['practical_hours'] : 0;
        $row['tutorial_hours'] = isset($row['tutorial_hours']) ? (int)$row['tutorial_hours'] : 0;
        $row['total_planned_hours'] = isset($row['total_planned_hours']) ? (int)$row['total_planned_hours'] : ($row['theory_hours'] + $row['practical_hours'] + $row['tutorial_hours']);
        $row['practical_label'] = derive_contact_hours_label($row['practical_hours'], $row['tutorial_hours']);
        $row['contact_summary'] = format_contact_hours_summary($row['theory_hours'], $row['practical_hours'], $row['tutorial_hours']);
        $class_labels_for_subject = [];
        if ($subject_id_val > 0 && isset($subject_class_labels_map[$subject_id_val])) {
            $class_labels_for_subject = $subject_class_labels_map[$subject_id_val];
        } elseif (!empty($row['class_division_meta'])) {
            $metaEntries = array_filter(array_map('trim', explode('||', $row['class_division_meta'])));
            foreach ($metaEntries as $metaEntry) {
                [$classNameMeta, $sectionMeta, $semesterMeta, $schoolMeta] = array_pad(explode('::', $metaEntry), 4, '');
                $labelCandidate = format_class_label($classNameMeta, $sectionMeta, $semesterMeta, $schoolMeta);
                if ($labelCandidate !== '' && !in_array($labelCandidate, $class_labels_for_subject, true)) {
                    $class_labels_for_subject[] = $labelCandidate;
                }
            }
        } elseif (!empty($row['class_list'])) {
            $class_labels_for_subject = array_filter(array_map('trim', explode(',', $row['class_list'])));
        }
        if (!empty($class_labels_for_subject)) {
            $class_labels_for_subject = array_values(array_unique(array_map('trim', $class_labels_for_subject)));
        }
        if (!empty($class_labels_for_subject)) {
            foreach ($class_labels_for_subject as $label_item) {
                if (!in_array($label_item, $subject_class_labels, true)) {
                    $subject_class_labels[] = $label_item;
                }
            }
        }
        $row['class_labels'] = $class_labels_for_subject;
        if ($subject_id_val > 0 && isset($subject_primary_option_map[$subject_id_val])) {
            $row['primary_class_option'] = $subject_primary_option_map[$subject_id_val];
        }
        $subjects_list[] = $row;
    }
    sort($subject_schools);
    sort($subject_semesters, SORT_NATURAL);
    sort($subject_class_labels, SORT_NATURAL);
    mysqli_free_result($subjects_result);
}

$filter_class_labels = array_values(array_unique(array_merge($class_option_labels, $subject_class_labels)));
sort($filter_class_labels, SORT_NATURAL);
$filter_class_labels = array_values(array_filter($filter_class_labels, static function ($label) {
    return preg_match('/sem[^0-9]*[0-9]/i', $label) === 1;
}));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Subjects - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
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
        .subject-row.editing {
            background-color: #fff5f7;
        }
        .contact-hours-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }
        .contact-hours-block {
            flex: 1 1 240px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            background-color: #fafafa;
        }
        .contact-hours-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .contact-hours-fields input[type="number"] {
            width: 100%;
            margin-bottom: 6px;
        }
        .contact-hours-hint {
            display: block;
            font-size: 0.78rem;
            color: #555;
        }
        .subject-inline-hours {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .subject-contact-inline {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .subject-contact-option {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .subject-contact-toggle {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
        }
        .subject-contact-inline-hint {
            font-size: 0.72rem;
            color: #666;
        }
        .subject-inline-input,
        .subject-inline-select {
            width: 100%;
            box-sizing: border-box;
            padding: 6px 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .subject-inline-hours {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .subject-inline-hours > label {
            display: flex;
            flex-direction: column;
            font-size: 0.8rem;
            color: #444;
            min-width: 120px;
        }
        .subject-elective-inline {
            margin-top: 10px;
        }
        .subject-elective-wrapper {
            display: flex;
            flex-direction: column;
            gap: 8px;
            background-color: #f7f7f7;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 10px;
        }
        .subject-elective-category-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .subject-elective-option {
            font-size: 0.85rem;
            color: #333;
        }
        .subject-elective-number-label {
            font-weight: 600;
            font-size: 0.85rem;
        }
        .subject-elective-number-other {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .subject-inline-error {
            color: #b10024;
            font-size: 0.85rem;
            margin-top: 6px;
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
            <a href="create_subjects.php" class="active"><i class="fas fa-book"></i> <span>Create Subjects</span></a>
            <a href="assign_teachers.php"><i class="fas fa-user-tag"></i> <span>Assign Teachers</span></a>
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
            <div class="container">
                <div class="card">
                    <div class="card-header"><h5>Create New Subject</h5></div>
                    <div class="card-body">
                        <?php if ($error) echo "<p style='color: #d32f2f; font-weight: bold;'>$error</p>"; ?>
                        <?php if ($success) echo "<p style='color: #388e3c; font-weight: bold;'>$success</p>"; ?>
                        <form method="POST">
                            <div class="form-group">
                                <label>What is the Subject Name? (e.g., Calculus)</label>
                                <input type="text" name="subject_name" value="<?php echo htmlspecialchars($subject_name_value); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Select Class (This will set the Semester and Department)</label>
                                <select name="class_id" required>
                                    <option value="">Select a Class</option>
                                    <?php foreach ($class_option_map as $option_value => $option_meta) { ?>
                                        <option value="<?php echo htmlspecialchars($option_value); ?>" <?php echo ($selected_class_option === $option_value) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($option_meta['label']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Is this a regular subject or elective?</label>
                                <div style="display:flex; gap:16px; flex-wrap:wrap;">
                                    <label style="display:flex; align-items:center; gap:6px;">
                                        <input type="radio" name="subject_type" value="regular" <?php echo $subject_type_value === 'elective' ? '' : 'checked'; ?>> Regular
                                    </label>
                                    <label style="display:flex; align-items:center; gap:6px;">
                                        <input type="radio" name="subject_type" value="elective" <?php echo $subject_type_value === 'elective' ? 'checked' : ''; ?>> Elective
                                    </label>
                                </div>
                            </div>
                            <?php
                                $isElectiveSelected = ($subject_type_value === 'elective');
                                $isOpenElective = $isElectiveSelected && $elective_category_value === 'open';
                                $isDepartmentalElective = $isElectiveSelected && $elective_category_value === 'departmental';
                                $normalizedElectiveNumberValue = strtolower((string)$elective_number_value);
                                $isOtherElectiveNumber = $isElectiveSelected && $normalizedElectiveNumberValue === 'other';
                            ?>
                            <div class="form-group elective-extra" id="elective-extra-fields" style="<?php echo $isElectiveSelected ? '' : 'display:none;'; ?>">
                                <label>Is this an open elective or departmental elective?</label>
                                <div style="display:flex; gap:16px; flex-wrap:wrap;">
                                    <label style="display:flex; align-items:center; gap:6px;">
                                        <input type="radio" name="elective_category" value="open" <?php echo $isElectiveSelected ? '' : 'disabled'; ?> <?php echo $isOpenElective ? 'checked' : ''; ?>> Open elective
                                    </label>
                                    <label style="display:flex; align-items:center; gap:6px;">
                                        <input type="radio" name="elective_category" value="departmental" <?php echo $isElectiveSelected ? '' : 'disabled'; ?> <?php echo $isDepartmentalElective ? 'checked' : ''; ?>> Departmental elective
                                    </label>
                                </div>
                                <label style="margin-top:12px;">Which elective number is this?</label>
                                <select name="elective_number" id="elective-number-select" <?php echo $isElectiveSelected ? '' : 'disabled'; ?>>
                                    <option value="">Select elective number</option>
                                    <option value="1" <?php echo $normalizedElectiveNumberValue === '1' ? 'selected' : ''; ?>>Elective 1</option>
                                    <option value="2" <?php echo $normalizedElectiveNumberValue === '2' ? 'selected' : ''; ?>>Elective 2</option>
                                    <option value="3" <?php echo $normalizedElectiveNumberValue === '3' ? 'selected' : ''; ?>>Elective 3</option>
                                    <option value="4" <?php echo $normalizedElectiveNumberValue === '4' ? 'selected' : ''; ?>>Elective 4</option>
                                    <option value="other" <?php echo $isOtherElectiveNumber ? 'selected' : ''; ?>>Other (specify)</option>
                                </select>
                                <div id="elective-number-other" style="margin-top:12px; <?php echo $isOtherElectiveNumber ? '' : 'display:none;'; ?>">
                                    <label>Enter elective number</label>
                                    <input type="text" name="elective_number_other" id="elective-number-other-input" value="<?php echo htmlspecialchars($elective_number_other_value); ?>" maxlength="50" <?php echo $isOtherElectiveNumber ? '' : 'disabled'; ?> placeholder="e.g., Honors Elective A">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>How many theory hours are planned for this subject each semester?</label>
                                <input type="number" name="theory_hours" min="0" value="<?php echo $theory_hours_value !== '' ? (int)$theory_hours_value : ''; ?>" placeholder="e.g., 30">
                            </div>
                            <?php
                                $practical_hours_checked = $include_practical_value || ($practical_hours_value !== '' && $practical_hours_value !== false && $practical_hours_value !== null);
                                $tutorial_hours_checked = $include_tutorial_value || ($tutorial_hours_value !== '' && $tutorial_hours_value !== false && $tutorial_hours_value !== null);
                            ?>
                            <div class="form-group">
                                <label>Contact Hours (Practical and Tutorial)</label>
                                <div class="contact-hours-wrapper">
                                    <div class="contact-hours-block">
                                        <label class="contact-hours-toggle">
                                            <input type="checkbox" name="include_practical" id="include-practical" value="1" <?php echo $practical_hours_checked ? 'checked' : ''; ?>>
                                            Add Practical hours
                                        </label>
                                        <div class="contact-hours-fields" id="practical-hours-fields" style="<?php echo $practical_hours_checked ? '' : 'display:none;'; ?>">
                                            <input type="number" name="practical_hours" min="0" value="<?php echo htmlspecialchars($practical_hours_checked ? (string)$practical_hours_value : ''); ?>" placeholder="e.g., 30" <?php echo $practical_hours_checked ? '' : 'disabled'; ?>>
                                            <small class="contact-hours-hint">Remember: 1 lab equals 2 hours of practical.</small>
                                        </div>
                                    </div>
                                    <div class="contact-hours-block">
                                        <label class="contact-hours-toggle">
                                            <input type="checkbox" name="include_tutorial" id="include-tutorial" value="1" <?php echo $tutorial_hours_checked ? 'checked' : ''; ?>>
                                            Add Tutorial hours
                                        </label>
                                        <div class="contact-hours-fields" id="tutorial-hours-fields" style="<?php echo $tutorial_hours_checked ? '' : 'display:none;'; ?>">
                                            <input type="number" name="tutorial_hours" min="0" value="<?php echo htmlspecialchars($tutorial_hours_checked ? (string)$tutorial_hours_value : ''); ?>" placeholder="e.g., 15" <?php echo $tutorial_hours_checked ? '' : 'disabled'; ?>>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn">Create Subject</button>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h5 style="margin:0;">Existing Subjects</h5></div>
                    <div class="card-body" style="overflow-x:auto;">
                        <?php
                            $subjectHasClassOptions = !empty($filter_class_labels);
                            $subjectClassFilterLabel = $subjectHasClassOptions ? 'All Classes / Divisions' : 'No Classes Available';
                        ?>
                        <div id="subject-filters" style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px;">
                            <select id="subject-school-filter" class="filter-control" style="flex:1 1 220px; min-width:200px;">
                                <option value="">All Schools</option>
                                <?php foreach ($subject_schools as $school_option): ?>
                                    <option value="<?php echo htmlspecialchars($school_option); ?>"><?php echo htmlspecialchars($school_option); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="subject-semester-filter" class="filter-control" style="flex:1 1 220px; min-width:200px;">
                                <option value="">All Semesters / Years</option>
                                <?php foreach ($subject_semesters as $semester_option): ?>
                                    <option value="<?php echo htmlspecialchars($semester_option); ?>">Semester <?php echo htmlspecialchars($semester_option); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="subject-class-filter" class="filter-control" style="flex:1 1 240px; min-width:220px;" <?php echo $subjectHasClassOptions ? '' : 'disabled'; ?>>
                                <option value="" data-default-text="All Classes / Divisions" data-empty-text="No Classes Available"><?php echo htmlspecialchars($subjectClassFilterLabel); ?></option>
                                <?php foreach ($filter_class_labels as $class_option): ?>
                                    <option value="<?php echo htmlspecialchars($class_option); ?>"><?php echo htmlspecialchars($class_option); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" id="subject-search" class="filter-control" placeholder="Search by subject or class" style="flex:1 1 260px; min-width:240px;">
                        </div>
                        <?php if (empty($subjects_list)): ?>
                            <p id="subjects-empty-message">No subjects found yet. Create the first subject above.</p>
                        <?php else: ?>
                            <table id="subjects-table">
                                <thead>
                                    <tr>
                                        <th>Subject Name</th>
                                        <th>School</th>
                                        <th>Semester</th>
                                        <th>Type</th>
                                        <th>Classes / Divisions</th>
                                        <th>Planned Hours (Theory / Practical / Tutorial)</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subjects_list as $subject_row): ?>
                                        <?php
                                            $class_labels = isset($subject_row['class_labels']) ? $subject_row['class_labels'] : [];
                                            $class_labels_attr = strtolower(implode('||', $class_labels));
                                            $primary_class_id = isset($subject_row['primary_class_id']) ? (int)$subject_row['primary_class_id'] : 0;
                                            $primary_section_id = isset($subject_row['primary_section_id']) ? (int)$subject_row['primary_section_id'] : 0;
                                            $primary_class_option = isset($subject_row['primary_class_option']) ? $subject_row['primary_class_option'] : '';
                                            if ($primary_class_option !== '' && !isset($class_option_map[$primary_class_option])) {
                                                $primary_class_option = '';
                                            }
                                            if ($primary_class_option === '' && $primary_class_id > 0) {
                                                $class_option_candidate = $primary_class_id . ':' . ($primary_section_id > 0 ? $primary_section_id : 0);
                                                if (isset($class_option_map[$class_option_candidate])) {
                                                    $primary_class_option = $class_option_candidate;
                                                }
                                            }
                                            $class_option_value = $primary_class_option;
                                            $theory_hours_val = isset($subject_row['theory_hours']) ? (int)$subject_row['theory_hours'] : 0;
                                            $practical_hours_val = isset($subject_row['practical_hours']) ? (int)$subject_row['practical_hours'] : 0;
                                            $tutorial_hours_val = isset($subject_row['tutorial_hours']) ? (int)$subject_row['tutorial_hours'] : 0;
                                            $total_hours_val = isset($subject_row['total_planned_hours']) ? (int)$subject_row['total_planned_hours'] : ($theory_hours_val + $practical_hours_val + $tutorial_hours_val);
                                            $subject_type_val = strtolower($subject_row['subject_type'] ?? 'regular');
                                            $contact_summary_val = isset($subject_row['contact_summary']) && $subject_row['contact_summary'] !== ''
                                                ? $subject_row['contact_summary']
                                                : format_contact_hours_summary($theory_hours_val, $practical_hours_val, $tutorial_hours_val);
                                            $contact_label_val = derive_contact_hours_label($practical_hours_val, $tutorial_hours_val);
                                        ?>
                                        <tr class="subject-row" data-subject-id="<?php echo (int)$subject_row['id']; ?>" data-school="<?php echo htmlspecialchars(strtolower($subject_row['school'])); ?>" data-subject="<?php echo htmlspecialchars(strtolower($subject_row['subject_name'])); ?>" data-shortname="<?php echo htmlspecialchars(strtolower($subject_row['short_name'])); ?>" data-semester="<?php echo htmlspecialchars(strtolower((string)$subject_row['semester'])); ?>" data-classlabels="<?php echo htmlspecialchars($class_labels_attr); ?>" data-class-option="<?php echo htmlspecialchars($class_option_value); ?>" data-subject-type="<?php echo htmlspecialchars($subject_type_val); ?>" data-theory-hours="<?php echo $theory_hours_val; ?>" data-practical-hours="<?php echo $practical_hours_val; ?>" data-tutorial-hours="<?php echo $tutorial_hours_val; ?>" data-contact-label="<?php echo htmlspecialchars($contact_label_val); ?>" data-contact-summary="<?php echo htmlspecialchars($contact_summary_val); ?>" data-total-hours="<?php echo $total_hours_val; ?>" data-elective-category="<?php echo htmlspecialchars($subject_row['elective_category']); ?>" data-elective-number="<?php echo htmlspecialchars($subject_row['elective_number']); ?>">
                                            <td class="subject-name-cell"><?php echo htmlspecialchars(format_subject_display($subject_row['subject_name'] ?? '')); ?></td>
                                            <td class="subject-school-cell"><?php echo htmlspecialchars($subject_row['school']); ?></td>
                                            <td class="subject-semester-cell"><?php echo htmlspecialchars($subject_row['semester']); ?></td>
                                            <td class="subject-type-cell"><?php echo htmlspecialchars(ucfirst($subject_type_val)); ?></td>
                                            <td class="subject-classes-cell"><?php echo !empty($class_labels) ? nl2br(htmlspecialchars(implode("\n", $class_labels))) : 'Not assigned yet'; ?></td>
                                            <td class="subject-hours-cell"><?php echo htmlspecialchars($total_hours_val) . ' hrs (' . htmlspecialchars($contact_summary_val) . ')'; ?></td>
                                            <td class="subject-actions-cell">
                                                <?php if (!empty($subject_row['id'])): ?>
                                                    <button type="button" class="table-action-btn subject-edit-btn">Edit</button>
                                                    <button type="button" class="table-action-btn subject-save-btn" style="display:none; background-color:#28a745;">Save</button>
                                                    <button type="button" class="table-action-btn subject-cancel-btn" style="display:none; background-color:#6c757d;">Cancel</button>
                                                    <button type="button" class="table-action-btn subject-delete-btn" style="background-color:#d32f2f;">Delete</button>
                                                <?php else: ?>
                                                    <span style="color:#999;">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <p id="subjects-empty-message" style="display:none;">No subjects match the selected filters.</p>
                        <?php endif; ?>
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
        if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');

        (function setupElectiveFormControls() {
            const form = document.querySelector('form');
            if (!form) {
                return;
            }
            const subjectTypeRadios = Array.prototype.slice.call(form.querySelectorAll('input[name="subject_type"]'));
            const electiveFields = document.getElementById('elective-extra-fields');
            const electiveCategoryInputs = Array.prototype.slice.call(form.querySelectorAll('input[name="elective_category"]'));
            const electiveNumberSelect = document.getElementById('elective-number-select');
            const electiveOtherGroup = document.getElementById('elective-number-other');
            const electiveOtherInput = document.getElementById('elective-number-other-input');

            function updateElectiveOther(isElective) {
                if (!electiveNumberSelect) {
                    return;
                }
                const needsOther = isElective && electiveNumberSelect.value === 'other';
                if (electiveOtherGroup) {
                    electiveOtherGroup.style.display = needsOther ? '' : 'none';
                }
                if (electiveOtherInput) {
                    electiveOtherInput.disabled = !needsOther;
                    electiveOtherInput.required = needsOther;
                }
            }

            function updateElectiveFields() {
                const selectedType = subjectTypeRadios.find(function(radio) { return radio.checked; });
                const isElective = selectedType && selectedType.value === 'elective';

                if (electiveFields) {
                    electiveFields.style.display = isElective ? '' : 'none';
                }

                electiveCategoryInputs.forEach(function(input) {
                    input.disabled = !isElective;
                    input.required = isElective;
                });

                if (electiveNumberSelect) {
                    electiveNumberSelect.disabled = !isElective;
                    electiveNumberSelect.required = isElective;
                }

                updateElectiveOther(isElective);
            }

            subjectTypeRadios.forEach(function(radio) {
                radio.addEventListener('change', updateElectiveFields);
            });

            if (electiveNumberSelect) {
                electiveNumberSelect.addEventListener('change', function() {
                    const selectedType = subjectTypeRadios.find(function(radio) { return radio.checked; });
                    const isElective = selectedType && selectedType.value === 'elective';
                    updateElectiveOther(isElective);
                });
            }

            updateElectiveFields();
        })();

        (function setupContactHoursControls() {
            const practicalToggle = document.getElementById('include-practical');
            const tutorialToggle = document.getElementById('include-tutorial');
            const practicalFields = document.getElementById('practical-hours-fields');
            const tutorialFields = document.getElementById('tutorial-hours-fields');
            const practicalInput = practicalFields ? practicalFields.querySelector('input[name="practical_hours"]') : null;
            const tutorialInput = tutorialFields ? tutorialFields.querySelector('input[name="tutorial_hours"]') : null;

            function syncState(toggle, container, input) {
                if (!toggle) {
                    return;
                }
                const checked = toggle.checked;
                if (container) {
                    container.style.display = checked ? '' : 'none';
                }
                if (input) {
                    input.disabled = !checked;
                    if (!checked) {
                        input.value = '';
                    }
                }
            }

            if (practicalToggle) {
                practicalToggle.addEventListener('change', function() {
                    syncState(practicalToggle, practicalFields, practicalInput);
                });
                syncState(practicalToggle, practicalFields, practicalInput);
            }

            if (tutorialToggle) {
                tutorialToggle.addEventListener('change', function() {
                    syncState(tutorialToggle, tutorialFields, tutorialInput);
                });
                syncState(tutorialToggle, tutorialFields, tutorialInput);
            }
        })();

        const subjectClassOptions = <?php echo json_encode($class_option_map, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

        (function enableSubjectInlineEditing() {
            const table = document.getElementById('subjects-table');
            if (!table) {
                return;
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function buildClassOptions(selectedValue) {
                let optionsHtml = '<option value="">-- Select Class / Division --</option>';
                Object.keys(subjectClassOptions || {}).forEach(function(valueKey) {
                    const meta = subjectClassOptions[valueKey];
                    if (!meta) {
                        return;
                    }
                    const selectedAttr = valueKey === selectedValue ? ' selected' : '';
                    optionsHtml += '<option value="' + valueKey + '"' + selectedAttr + '>' + escapeHtml(meta.label) + '</option>';
                });
                return optionsHtml;
            }

            function showError(row, message) {
                let errorEl = row.querySelector('.subject-inline-error');
                if (!errorEl) {
                    errorEl = document.createElement('div');
                    errorEl.className = 'subject-inline-error';
                    row.querySelector('.subject-actions-cell').appendChild(errorEl);
                }
                errorEl.textContent = message;
                errorEl.style.display = message ? '' : 'none';
            }

            function clearError(row) {
                const errorEl = row.querySelector('.subject-inline-error');
                if (errorEl) {
                    errorEl.textContent = '';
                    errorEl.style.display = 'none';
                }
            }

            function exitEditMode(row, restoreOriginal) {
                if (!row.classList.contains('editing')) {
                    return;
                }
                const editBtn = row.querySelector('.subject-edit-btn');
                const saveBtn = row.querySelector('.subject-save-btn');
                const cancelBtn = row.querySelector('.subject-cancel-btn');
                const deleteBtn = row.querySelector('.subject-delete-btn');
                if (restoreOriginal && row._originalCells) {
                    row.querySelector('.subject-name-cell').innerHTML = row._originalCells.name;
                    row.querySelector('.subject-type-cell').innerHTML = row._originalCells.type;
                    row.querySelector('.subject-classes-cell').innerHTML = row._originalCells.classes;
                    row.querySelector('.subject-hours-cell').innerHTML = row._originalCells.hours;
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
                const subjectId = parseInt(row.dataset.subjectId || '0', 10);
                if (!subjectId) {
                    return;
                }

                const editBtn = row.querySelector('.subject-edit-btn');
                const saveBtn = row.querySelector('.subject-save-btn');
                const cancelBtn = row.querySelector('.subject-cancel-btn');
                const deleteBtn = row.querySelector('.subject-delete-btn');
                const nameCell = row.querySelector('.subject-name-cell');
                const typeCell = row.querySelector('.subject-type-cell');
                const classesCell = row.querySelector('.subject-classes-cell');
                const hoursCell = row.querySelector('.subject-hours-cell');

                row._originalCells = {
                    name: nameCell.innerHTML,
                    type: typeCell.innerHTML,
                    classes: classesCell.innerHTML,
                    hours: hoursCell.innerHTML
                };

                const displayName = nameCell.textContent.trim();
                const subjectType = row.dataset.subjectType || 'regular';
                const classOption = row.dataset.classOption || '';
                const theoryHours = parseInt(row.dataset.theoryHours || '0', 10) || 0;
                const practicalHours = parseInt(row.dataset.practicalHours || '0', 10) || 0;
                const tutorialHours = parseInt(row.dataset.tutorialHours || '0', 10) || 0;
                const practicalChecked = practicalHours > 0;
                const tutorialChecked = tutorialHours > 0;

                nameCell.innerHTML = '<input type="text" class="subject-inline-input subject-name-input" value="' + escapeHtml(displayName) + '">';

                const typeSelect = document.createElement('select');
                typeSelect.className = 'subject-inline-select subject-type-select';
                typeSelect.innerHTML = '<option value="regular">Regular</option><option value="elective">Elective</option>';
                typeSelect.value = subjectType === 'elective' ? 'elective' : 'regular';
                typeCell.innerHTML = '';
                typeCell.appendChild(typeSelect);

                const categoryGroupName = 'subject-elective-category-' + subjectId;
                const numberSelectId = 'subject-elective-number-' + subjectId;
                const numberOtherId = 'subject-elective-number-other-' + subjectId;
                const electiveContainer = document.createElement('div');
                electiveContainer.className = 'subject-elective-inline';
                electiveContainer.innerHTML = `
                    <div class="subject-elective-wrapper">
                        <div class="subject-elective-category-group">
                            <label class="subject-elective-option">
                                <input type="radio" name="${categoryGroupName}" value="open" class="subject-elective-category-input"> Open elective
                            </label>
                            <label class="subject-elective-option">
                                <input type="radio" name="${categoryGroupName}" value="departmental" class="subject-elective-category-input"> Departmental elective
                            </label>
                        </div>
                        <label class="subject-elective-number-label">Which elective number is this?</label>
                        <select id="${numberSelectId}" class="subject-inline-select subject-elective-number-select">
                            <option value="">Select elective number</option>
                            <option value="1">Elective 1</option>
                            <option value="2">Elective 2</option>
                            <option value="3">Elective 3</option>
                            <option value="4">Elective 4</option>
                            <option value="other">Other (specify)</option>
                        </select>
                        <div class="subject-elective-number-other" style="display:none;">
                            <label>Enter elective number</label>
                            <input type="text" id="${numberOtherId}" class="subject-inline-input subject-elective-number-other-input" maxlength="50" placeholder="e.g., Honors Elective A" disabled>
                        </div>
                    </div>
                `;
                typeCell.appendChild(electiveContainer);

                const electiveCategoryInputs = electiveContainer.querySelectorAll('.subject-elective-category-input');
                const electiveNumberSelect = electiveContainer.querySelector('.subject-elective-number-select');
                const electiveNumberOtherGroup = electiveContainer.querySelector('.subject-elective-number-other');
                const electiveNumberOtherInput = electiveNumberOtherGroup ? electiveNumberOtherGroup.querySelector('input') : null;

                const initialElectiveCategory = (row.dataset.electiveCategory || '').toLowerCase();
                electiveCategoryInputs.forEach(function(input) {
                    input.checked = input.value === initialElectiveCategory;
                });

                const allowedElectiveNumbers = ['1', '2', '3', '4'];
                const initialElectiveNumberRaw = (row.dataset.electiveNumber || '').toString().trim();
                let initialElectiveSelectValue = '';
                let initialElectiveCustomValue = '';
                if (initialElectiveNumberRaw !== '') {
                    const normalizedNumber = initialElectiveNumberRaw.toLowerCase();
                    if (allowedElectiveNumbers.indexOf(normalizedNumber) !== -1) {
                        initialElectiveSelectValue = normalizedNumber;
                    } else {
                        initialElectiveSelectValue = 'other';
                        initialElectiveCustomValue = initialElectiveNumberRaw;
                    }
                }
                if (electiveNumberSelect) {
                    electiveNumberSelect.value = initialElectiveSelectValue;
                }
                if (electiveNumberOtherInput) {
                    electiveNumberOtherInput.value = initialElectiveCustomValue;
                }

                function syncElectiveOther() {
                    if (!electiveNumberSelect || !electiveNumberOtherGroup || !electiveNumberOtherInput) {
                        return;
                    }
                    const needsOther = electiveNumberSelect.value === 'other';
                    electiveNumberOtherGroup.style.display = needsOther ? '' : 'none';
                    electiveNumberOtherInput.disabled = !needsOther;
                    if (!needsOther) {
                        electiveNumberOtherInput.value = '';
                    }
                }

                function syncElectiveVisibility() {
                    const isElective = typeSelect.value === 'elective';
                    if (electiveContainer) {
                        electiveContainer.style.display = isElective ? '' : 'none';
                    }
                    electiveCategoryInputs.forEach(function(input) {
                        input.disabled = !isElective;
                        if (!isElective) {
                            input.checked = false;
                        }
                    });
                    if (electiveNumberSelect) {
                        electiveNumberSelect.disabled = !isElective;
                        if (!isElective) {
                            electiveNumberSelect.value = '';
                        }
                    }
                    if (electiveNumberOtherInput) {
                        if (!isElective) {
                            electiveNumberOtherInput.value = '';
                        }
                        electiveNumberOtherInput.disabled = !isElective || (electiveNumberSelect && electiveNumberSelect.value !== 'other');
                    }
                    if (isElective) {
                        syncElectiveOther();
                    } else if (electiveNumberOtherGroup) {
                        electiveNumberOtherGroup.style.display = 'none';
                    }
                }

                if (electiveNumberSelect) {
                    electiveNumberSelect.addEventListener('change', syncElectiveOther);
                }
                typeSelect.addEventListener('change', function() {
                    syncElectiveVisibility();
                });

                syncElectiveVisibility();

                const classSelect = document.createElement('select');
                classSelect.className = 'subject-inline-select subject-class-select';
                classSelect.innerHTML = buildClassOptions(classOption);
                classesCell.innerHTML = '';
                classesCell.appendChild(classSelect);

                const hoursWrapper = document.createElement('div');
                hoursWrapper.className = 'subject-inline-hours';
                hoursWrapper.innerHTML = `
                    <label>Theory Hours
                        <input type="number" min="0" class="subject-inline-input subject-theory-input" value="${theoryHours}">
                    </label>
                    <div class="subject-contact-inline">
                        <div class="subject-contact-option">
                            <label class="subject-contact-toggle">
                                <input type="checkbox" class="subject-practical-toggle" ${practicalChecked ? 'checked' : ''}> Practical hours
                            </label>
                            <input type="number" min="0" class="subject-inline-input subject-practical-input" value="${practicalChecked ? practicalHours : ''}" ${practicalChecked ? '' : 'disabled'} placeholder="0">
                            <span class="subject-contact-inline-hint">1 lab = 2 practical hrs</span>
                        </div>
                        <div class="subject-contact-option">
                            <label class="subject-contact-toggle">
                                <input type="checkbox" class="subject-tutorial-toggle" ${tutorialChecked ? 'checked' : ''}> Tutorial hours
                            </label>
                            <input type="number" min="0" class="subject-inline-input subject-tutorial-input" value="${tutorialChecked ? tutorialHours : ''}" ${tutorialChecked ? '' : 'disabled'} placeholder="0">
                        </div>
                    </div>
                `;
                hoursCell.innerHTML = '';
                hoursCell.appendChild(hoursWrapper);

                const practicalToggle = hoursWrapper.querySelector('.subject-practical-toggle');
                const tutorialToggle = hoursWrapper.querySelector('.subject-tutorial-toggle');
                const practicalInput = hoursWrapper.querySelector('.subject-practical-input');
                const tutorialInput = hoursWrapper.querySelector('.subject-tutorial-input');

                function syncContactToggle(toggle, input) {
                    if (!toggle || !input) {
                        return;
                    }
                    const checked = toggle.checked;
                    input.disabled = !checked;
                    if (!checked) {
                        input.value = '';
                    }
                }

                if (practicalToggle && practicalInput) {
                    practicalToggle.addEventListener('change', function() {
                        syncContactToggle(practicalToggle, practicalInput);
                    });
                    syncContactToggle(practicalToggle, practicalInput);
                }

                if (tutorialToggle && tutorialInput) {
                    tutorialToggle.addEventListener('change', function() {
                        syncContactToggle(tutorialToggle, tutorialInput);
                    });
                    syncContactToggle(tutorialToggle, tutorialInput);
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

            function applyRowData(row, payload) {
                const nameCell = row.querySelector('.subject-name-cell');
                const typeCell = row.querySelector('.subject-type-cell');
                const schoolCell = row.querySelector('.subject-school-cell');
                const semesterCell = row.querySelector('.subject-semester-cell');
                const classesCell = row.querySelector('.subject-classes-cell');
                const hoursCell = row.querySelector('.subject-hours-cell');

                const classDisplay = (payload.class_labels && payload.class_labels.length)
                    ? payload.class_labels.map(function(label) { return escapeHtml(label); }).join('<br>')
                    : (payload.class_label ? escapeHtml(payload.class_label) : 'Not assigned yet');

                nameCell.textContent = payload.subject_name;
                typeCell.textContent = payload.subject_type.charAt(0).toUpperCase() + payload.subject_type.slice(1);
                schoolCell.textContent = payload.school;
                semesterCell.textContent = payload.semester;
                classesCell.innerHTML = classDisplay;
                const contactSummary = payload.contact_summary || '';
                hoursCell.textContent = payload.total_hours + ' hrs (' + contactSummary + ')';

                row.dataset.subject = payload.subject_name.toLowerCase();
                row.dataset.school = (payload.school || '').toLowerCase();
                row.dataset.semester = (payload.semester || '').toString().toLowerCase();
                row.dataset.classlabels = payload.class_labels_attr || '';
                row.dataset.classOption = payload.class_option || '';
                row.dataset.subjectType = payload.subject_type;
                row.dataset.theoryHours = payload.theory_hours;
                row.dataset.practicalHours = payload.practical_hours;
                row.dataset.tutorialHours = payload.tutorial_hours;
                row.dataset.contactLabel = payload.practical_label || '';
                row.dataset.contactSummary = contactSummary;
                row.dataset.totalHours = payload.total_hours;
                row.dataset.electiveCategory = payload.elective_category || '';
                row.dataset.electiveNumber = payload.elective_number || '';

                exitEditMode(row, false);
            }

            function saveRow(row) {
                const subjectId = parseInt(row.dataset.subjectId || '0', 10);
                if (!subjectId) {
                    return;
                }

                const nameInput = row.querySelector('.subject-name-input');
                const typeSelect = row.querySelector('.subject-type-select');
                const classSelect = row.querySelector('.subject-class-select');
                const theoryInput = row.querySelector('.subject-theory-input');
                const practicalInput = row.querySelector('.subject-practical-input');
                const tutorialInput = row.querySelector('.subject-tutorial-input');
                const practicalToggle = row.querySelector('.subject-practical-toggle');
                const tutorialToggle = row.querySelector('.subject-tutorial-toggle');
                const electiveCategoryInputs = row.querySelectorAll('.subject-elective-category-input');
                const electiveNumberSelect = row.querySelector('.subject-elective-number-select');
                const electiveNumberOtherInput = row.querySelector('.subject-elective-number-other-input');
                const saveBtn = row.querySelector('.subject-save-btn');

                if (!nameInput || !typeSelect || !classSelect || !theoryInput || !practicalInput || !tutorialInput || !practicalToggle || !tutorialToggle) {
                    showError(row, 'Unable to save changes. Please reload and try again.');
                    return;
                }

                const subjectName = nameInput.value.trim();
                const subjectType = typeSelect.value;
                const classOption = classSelect.value;
                const theoryHours = parseInt(theoryInput.value || '0', 10) || 0;
                const practicalHoursRaw = parseInt(practicalInput.value || '0', 10) || 0;
                const tutorialHoursRaw = parseInt(tutorialInput.value || '0', 10) || 0;
                const includePractical = practicalToggle.checked || practicalHoursRaw > 0;
                const includeTutorial = tutorialToggle.checked || tutorialHoursRaw > 0;
                const practicalHours = includePractical ? practicalHoursRaw : 0;
                const tutorialHours = includeTutorial ? tutorialHoursRaw : 0;
                let electiveCategory = '';
                let electiveNumber = '';
                let electiveNumberSelection = '';

                if (subjectName === '') {
                    showError(row, 'Subject name cannot be empty.');
                    return;
                }
                if (!classOption) {
                    showError(row, 'Please select a class / division.');
                    return;
                }
                if (theoryHours + practicalHours + tutorialHours <= 0) {
                    showError(row, 'Add theory, practical, or tutorial hours before saving.');
                    return;
                }
                if (subjectType === 'elective') {
                    electiveCategory = Array.from(electiveCategoryInputs || []).find(function(input) {
                        return input.checked;
                    })?.value || '';
                    electiveNumberSelection = electiveNumberSelect ? electiveNumberSelect.value : '';
                    if (!electiveCategory) {
                        showError(row, 'Choose whether this is an open or departmental elective.');
                        return;
                    }
                    if (!electiveNumberSelection) {
                        showError(row, 'Select which elective number this is.');
                        return;
                    }
                    if (electiveNumberSelection === 'other') {
                        electiveNumber = electiveNumberOtherInput ? electiveNumberOtherInput.value.trim() : '';
                        if (electiveNumber === '') {
                            showError(row, 'Enter the elective number label.');
                            return;
                        }
                    } else {
                        electiveNumber = electiveNumberSelection;
                    }
                }

                clearError(row);
                if (saveBtn) {
                    saveBtn.disabled = true;
                }

                const formData = new FormData();
                formData.append('action', 'update_subject_inline');
                formData.append('subject_id', subjectId);
                formData.append('subject_name', subjectName);
                formData.append('subject_type', subjectType);
                formData.append('class_option', classOption);
                formData.append('theory_hours', theoryHours);
                formData.append('practical_hours', practicalHours);
                formData.append('tutorial_hours', tutorialHours);
                formData.append('elective_category', subjectType === 'elective' ? electiveCategory : '');
                formData.append('elective_number', subjectType === 'elective' ? electiveNumber : '');
                formData.append('elective_number_selection', subjectType === 'elective' ? electiveNumberSelection : '');

                fetch('create_subjects.php', {
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
                    applyRowData(row, payload.data);
                })
                .catch(function(error) {
                    console.error('Failed to update subject:', error);
                    if (saveBtn) {
                        saveBtn.disabled = false;
                    }
                    showError(row, error.message || 'Unable to save changes right now.');
                });
            }

            function deleteRow(row) {
                const subjectId = parseInt(row.dataset.subjectId || '0', 10);
                if (!subjectId) {
                    return;
                }

                const deleteBtn = row.querySelector('.subject-delete-btn');
                const subjectName = (row.querySelector('.subject-name-cell')?.textContent || '').trim();
                const confirmMessage = subjectName !== ''
                    ? 'Delete "' + subjectName + '" and its related data?'
                    : 'Delete this subject and its related data?';
                if (!window.confirm(confirmMessage)) {
                    return;
                }

                clearError(row);
                if (deleteBtn) {
                    deleteBtn.disabled = true;
                }

                const formData = new FormData();
                formData.append('action', 'delete_subject');
                formData.append('subject_id', subjectId);

                fetch('create_subjects.php', {
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
                    document.dispatchEvent(new CustomEvent('subjectRowsChanged'));
                    window.alert('Subject deleted successfully.');
                })
                .catch(function(error) {
                    console.error('Failed to delete subject:', error);
                    showError(row, error.message || 'Unable to delete this subject right now.');
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
                const row = target.closest('.subject-row');
                if (!row) {
                    return;
                }

                if (target.classList.contains('subject-edit-btn')) {
                    enterEditMode(row);
                } else if (target.classList.contains('subject-save-btn')) {
                    saveRow(row);
                } else if (target.classList.contains('subject-cancel-btn')) {
                    exitEditMode(row, true);
                } else if (target.classList.contains('subject-delete-btn')) {
                    deleteRow(row);
                }
            });
        })();

        (function setupSubjectFilters() {
            const schoolFilter = document.getElementById('subject-school-filter');
            const semesterFilter = document.getElementById('subject-semester-filter');
            const classFilter = document.getElementById('subject-class-filter');
            const searchInput = document.getElementById('subject-search');
            const table = document.getElementById('subjects-table');
            if (!schoolFilter || !searchInput || !table) {
                return;
            }

            let rows = Array.from(table.querySelectorAll('tbody tr'));
            const emptyMessage = document.getElementById('subjects-empty-message');

            function refreshRows() {
                rows = Array.from(table.querySelectorAll('tbody tr'));
            }

            function datasetContains(datasetValue, target) {
                if (!datasetValue || !target) {
                    return false;
                }
                return datasetValue.split('||').some(function (value) {
                    return value.trim() === target;
                });
            }

            function extractSemesterKey(labelText) {
                if (!labelText) {
                    return '';
                }
                const match = labelText.toLowerCase().match(/sem[^0-9]*([0-9]+)/);
                return match ? match[1] : '';
            }

            const classFilterDefaultText = classFilter && classFilter.options.length
                ? (classFilter.options[0].dataset.defaultText || classFilter.options[0].text)
                : 'All Classes / Divisions';
            const classFilterEmptyText = classFilter && classFilter.options.length
                ? (classFilter.options[0].dataset.emptyText || 'No Classes Available')
                : 'No Classes Available';
            const classFilterSourceOptions = classFilter
                ? Array.from(classFilter.options).slice(1).map(function (opt) {
                    const label = opt.text || opt.value;
                    return {
                        value: opt.value,
                        text: label,
                        semesterKey: extractSemesterKey(label),
                    };
                })
                : [];

            function refreshClassFilterOptions() {
                if (!classFilter) {
                    return;
                }
                const selectedSemester = semesterFilter ? semesterFilter.value.trim().toLowerCase() : '';
                const previousValue = classFilter.value;
                const filteredOptions = selectedSemester
                    ? classFilterSourceOptions.filter(function (opt) {
                        return opt.semesterKey === selectedSemester;
                    })
                    : classFilterSourceOptions.slice();

                classFilter.innerHTML = '';

                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = filteredOptions.length ? classFilterDefaultText : classFilterEmptyText;
                defaultOption.dataset.defaultText = classFilterDefaultText;
                defaultOption.dataset.emptyText = classFilterEmptyText;
                classFilter.appendChild(defaultOption);

                let retained = false;
                filteredOptions.forEach(function (opt) {
                    const optionEl = document.createElement('option');
                    optionEl.value = opt.value;
                    optionEl.textContent = opt.text;
                    optionEl.dataset.semesterKey = opt.semesterKey;
                    if (!retained && previousValue && previousValue === opt.value) {
                        optionEl.selected = true;
                        retained = true;
                    }
                    classFilter.appendChild(optionEl);
                });

                if (!retained) {
                    classFilter.value = '';
                }
                classFilter.disabled = filteredOptions.length === 0;
            }

            function applyFilters() {
                refreshRows();
                const schoolValue = schoolFilter.value.trim().toLowerCase();
                const semesterValue = semesterFilter ? semesterFilter.value.trim().toLowerCase() : '';
                const classValue = classFilter ? classFilter.value.trim().toLowerCase() : '';
                const searchValue = searchInput.value.trim().toLowerCase();
                let visibleCount = 0;

                rows.forEach(row => {
                    const matchesSchool = !schoolValue || row.dataset.school === schoolValue;
                    const matchesSemester = !semesterValue || row.dataset.semester === semesterValue;
                    const classLabelsDataset = row.dataset.classlabels || '';
                    const matchesClass = !classValue || datasetContains(classLabelsDataset, classValue);
                    const matchesSearch = !searchValue
                        || row.dataset.subject.includes(searchValue)
                        || row.dataset.semester.includes(searchValue)
                        || (classLabelsDataset && classLabelsDataset.includes(searchValue));
                    if (matchesSchool && matchesSemester && matchesClass && matchesSearch) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (emptyMessage) {
                    emptyMessage.style.display = visibleCount ? 'none' : 'block';
                }
            }

            schoolFilter.addEventListener('change', applyFilters);
            if (semesterFilter) {
                semesterFilter.addEventListener('change', function () {
                    refreshClassFilterOptions();
                    applyFilters();
                });
            }
            if (classFilter) {
                classFilter.addEventListener('change', applyFilters);
            }
            searchInput.addEventListener('input', applyFilters);

            refreshClassFilterOptions();
            applyFilters();

            document.addEventListener('subjectRowsChanged', function () {
                refreshClassFilterOptions();
                applyFilters();
            });
        })();
    </script>
</body>
</html>
