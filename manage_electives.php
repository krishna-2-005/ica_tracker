<?php
session_start();
include 'db_connect.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'program_chair'], true)) {
    header('Location: login.php');
    exit;
}

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

$indexResult = mysqli_query($conn, "SHOW INDEX FROM student_elective_choices");
$hasOldUnique = false;
$hasNewUnique = false;
if ($indexResult) {
    while ($idxRow = mysqli_fetch_assoc($indexResult)) {
        $keyName = isset($idxRow['Key_name']) ? (string)$idxRow['Key_name'] : '';
        $nonUnique = isset($idxRow['Non_unique']) ? (int)$idxRow['Non_unique'] : 0;
        if ($keyName === 'uniq_student_class' && $nonUnique === 0) {
            $hasOldUnique = true;
        }
        if ($keyName === 'uniq_student_class_subject' && $nonUnique === 0) {
            $hasNewUnique = true;
        }
    }
    mysqli_free_result($indexResult);
}

if ($hasOldUnique) {
    mysqli_query($conn, "ALTER TABLE student_elective_choices DROP INDEX uniq_student_class");
}

if (!$hasNewUnique) {
    mysqli_query($conn, "ALTER TABLE student_elective_choices ADD UNIQUE KEY uniq_student_class_subject (student_id, class_id, subject_id)");
}

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

$error = '';
$success = '';
$userNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$userNameDisplay = $userNameRaw !== '' ? format_person_display($userNameRaw) : '';

if (!empty($_SESSION['manage_electives_success'])) {
    $success = (string)$_SESSION['manage_electives_success'];
    unset($_SESSION['manage_electives_success']);
}
if (!empty($_SESSION['manage_electives_error'])) {
    $error = (string)$_SESSION['manage_electives_error'];
    unset($_SESSION['manage_electives_error']);
}

if (!function_exists('format_class_display_name')) {
    function format_class_display_name(string $name): string {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return '';
        }
        $normalized = preg_replace_callback('/\b([0-9]+(?:st|nd|rd|th))\b/i', static function (array $matches) {
            return strtoupper($matches[1]);
        }, $trimmed);
        $normalized = preg_replace('/\byear\b/i', 'YEAR', $normalized);
        return $normalized;
    }
}

$classes = [];
$class_query = "SELECT c.id AS class_id,
                       c.class_name,
                       c.semester,
                       c.school,
                       sec.id AS section_id,
                       sec.section_name
                FROM classes c
                LEFT JOIN sections sec ON sec.class_id = c.id
                ORDER BY c.class_name,
                         CASE WHEN sec.section_name IS NULL OR sec.section_name = '' THEN 1 ELSE 0 END,
                         sec.section_name";
$class_result = mysqli_query($conn, $class_query);
if ($class_result) {
    $class_map = [];
    while ($row = mysqli_fetch_assoc($class_result)) {
        $class_id = isset($row['class_id']) ? (int)$row['class_id'] : 0;
        $section_id = isset($row['section_id']) ? (int)$row['section_id'] : 0;
        $class_name = isset($row['class_name']) ? trim((string)$row['class_name']) : '';
        $section_name = isset($row['section_name']) ? trim((string)$row['section_name']) : '';
        $semester_raw = isset($row['semester']) ? trim((string)$row['semester']) : '';
        $school_name = isset($row['school']) ? trim((string)$row['school']) : '';

        $label = format_class_label($class_name, $section_name, $semester_raw, $school_name);

        $key = $class_id . '|' . $section_id;
        if (!isset($class_map[$key])) {
            $class_map[$key] = [
                'class_id' => $class_id,
                'class_name' => $class_name,
                'section_id' => $section_id,
                'section_name' => $section_name,
                'semester' => $semester_raw,
                'school' => $school_name,
                'label' => $label !== '' ? $label : format_class_display_name($class_name)
            ];
        }
    }
    mysqli_free_result($class_result);
    $classes = array_values($class_map);
}

$parseClassKey = static function ($value): array {
    $value = trim((string)$value);
    if ($value === '') {
        return [0, 0];
    }
    if (strpos($value, '|') !== false) {
        [$class_part, $section_part] = explode('|', $value, 2);
        return [max(0, (int)$class_part), max(0, (int)$section_part)];
    }
    return [max(0, (int)$value), 0];
};

$selected_class_id = 0;
$selected_section_id = 0;

if (isset($_REQUEST['class_key'])) {
    [$selected_class_id, $selected_section_id] = $parseClassKey($_REQUEST['class_key']);
} else {
    if (isset($_REQUEST['class_id'])) {
        [$selected_class_id, $maybe_section] = $parseClassKey($_REQUEST['class_id']);
        if ($maybe_section > 0) {
            $selected_section_id = $maybe_section;
        }
    }
    if (isset($_REQUEST['section_id'])) {
        $section_candidate = (int)$_REQUEST['section_id'];
        if ($section_candidate > 0) {
            $selected_section_id = $section_candidate;
        }
    }
}

if ($selected_class_id <= 0) {
    $selected_section_id = 0;
}

$selected_class_key = $selected_class_id > 0 ? $selected_class_id . '|' . $selected_section_id : '';

function fetchElectiveSubjects(mysqli $conn, int $class_id, int $section_id = 0): array {
    if ($class_id <= 0) {
        return [];
    }
    $section_filter_sql = '';
    if ($section_id > 0) {
        $section_filter_sql = "
              AND (scm.section_id IS NULL OR scm.section_id = ? OR scm.section_id = 0)
              AND (tsa.section_id IS NULL OR tsa.section_id = ? OR tsa.section_id = 0)";
    }
    $sql = "SELECT DISTINCT s.id, s.subject_name
            FROM subjects s
            LEFT JOIN subject_details sd ON sd.subject_id = s.id
            LEFT JOIN subject_class_map scm ON scm.subject_id = s.id
            LEFT JOIN teacher_subject_assignments tsa ON tsa.subject_id = s.id
            WHERE COALESCE(sd.subject_type, 'regular') = 'elective'
              AND (scm.class_id = ? OR tsa.class_id = ?)" . $section_filter_sql . "
            ORDER BY s.subject_name";
    $stmt = mysqli_prepare($conn, $sql);
    if ($section_id > 0) {
        mysqli_stmt_bind_param($stmt, 'iiii', $class_id, $class_id, $section_id, $section_id);
    } else {
        mysqli_stmt_bind_param($stmt, 'ii', $class_id, $class_id);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $subjects = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $subjects[(int)$row['id']] = $row['subject_name'];
    }
    mysqli_stmt_close($stmt);
    return $subjects;
}

function fetchStudentsForClass(mysqli $conn, int $class_id, int $section_id = 0): array {
    if ($class_id <= 0) {
        return [];
    }
    $sql = "SELECT id, name, roll_number, COALESCE(section_id, 0) AS section_id
            FROM students
            WHERE class_id = ?";
    if ($section_id > 0) {
        $sql .= " AND COALESCE(section_id, 0) = ?";
    }
    $sql .= "
            ORDER BY roll_number";
    $stmt = mysqli_prepare($conn, $sql);
    if ($section_id > 0) {
        mysqli_stmt_bind_param($stmt, 'ii', $class_id, $section_id);
    } else {
        mysqli_stmt_bind_param($stmt, 'i', $class_id);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $students = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $nameRaw = isset($row['name']) ? trim((string)$row['name']) : '';
        $row['name'] = $nameRaw;
        $row['name_display'] = format_person_display($nameRaw);
        $students[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $students;
}

function fetchExistingChoices(mysqli $conn, int $class_id): array {
    if ($class_id <= 0) {
        return [];
    }
    $sql = "SELECT student_id, subject_id FROM student_elective_choices WHERE class_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $class_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $choices = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $studentId = isset($row['student_id']) ? (int)$row['student_id'] : 0;
        $subjectId = isset($row['subject_id']) ? (int)$row['subject_id'] : 0;
        if ($studentId <= 0 || $subjectId <= 0) {
            continue;
        }
        if (!isset($choices[$studentId])) {
            $choices[$studentId] = [];
        }
        $choices[$studentId][$subjectId] = true;
    }
    mysqli_stmt_close($stmt);
    return $choices;
}

function calculateSubjectCounts(array $existing_choices): array {
    $counts = [];
    foreach ($existing_choices as $studentAssignments) {
        if (!is_array($studentAssignments)) {
            continue;
        }
        foreach ($studentAssignments as $subjectId => $flag) {
            $subjectKey = (int)$subjectId;
            if ($subjectKey <= 0) {
                continue;
            }
            if (!isset($counts[$subjectKey])) {
                $counts[$subjectKey] = 0;
            }
            $counts[$subjectKey]++;
        }
    }
    return $counts;
}

$elective_subjects = $selected_class_id > 0 ? fetchElectiveSubjects($conn, $selected_class_id, $selected_section_id) : [];
$students = $selected_class_id > 0 ? fetchStudentsForClass($conn, $selected_class_id, $selected_section_id) : [];
$existing_choices = $selected_class_id > 0 ? fetchExistingChoices($conn, $selected_class_id) : [];
$selected_subject_id = 0;
$selected_subject_name = '';
if (!empty($elective_subjects)) {
    $requested_subject_id = isset($_REQUEST['subject_id']) ? (int)$_REQUEST['subject_id'] : 0;
    if ($requested_subject_id > 0 && isset($elective_subjects[$requested_subject_id])) {
        $selected_subject_id = $requested_subject_id;
        $selected_subject_name = $elective_subjects[$requested_subject_id];
    }
}
if ($selected_subject_id === 0 && !empty($elective_subjects)) {
    $firstSubjectId = array_key_first($elective_subjects);
    if ($firstSubjectId !== null) {
        $selected_subject_id = (int)$firstSubjectId;
        $selected_subject_name = $elective_subjects[$firstSubjectId];
    }
}
$subject_counts = calculateSubjectCounts($existing_choices);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($selected_class_id <= 0) {
        $error = 'Please select a class to manage electives.';
    } elseif (empty($elective_subjects)) {
        $error = 'No elective subjects found for the selected class. Create or assign electives first.';
    } else {
        $post_subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
        if ($post_subject_id <= 0 || !isset($elective_subjects[$post_subject_id])) {
            $error = 'Select a valid elective before saving assignments.';
        } else {
            $selected_subject_id = $post_subject_id;
            $selected_subject_name = $elective_subjects[$post_subject_id];
            $selected_students = isset($_POST['selected_students']) && is_array($_POST['selected_students']) ? $_POST['selected_students'] : [];
            $valid_student_ids = array_map(static fn($row) => (int)$row['id'], $students);
            $valid_student_lookup = array_flip($valid_student_ids);

            $normalized_student_ids = [];
            foreach ($selected_students as $candidate) {
                $student_id = (int)$candidate;
                if ($student_id > 0 && isset($valid_student_lookup[$student_id])) {
                    $normalized_student_ids[$student_id] = true;
                }
            }

            $insert_stmt = mysqli_prepare($conn, "INSERT INTO student_elective_choices (student_id, class_id, subject_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE subject_id = VALUES(subject_id), updated_at = CURRENT_TIMESTAMP");
            $delete_stmt = mysqli_prepare($conn, "DELETE FROM student_elective_choices WHERE student_id = ? AND class_id = ? AND subject_id = ?");

            $updated_count = 0;
            foreach ($students as $student_row) {
                $student_id = isset($student_row['id']) ? (int)$student_row['id'] : 0;
                if ($student_id <= 0) {
                    continue;
                }
                $current_assignments = $existing_choices[$student_id] ?? [];
                $has_current_subject = isset($current_assignments[$post_subject_id]);
                $should_assign = isset($normalized_student_ids[$student_id]);

                if ($should_assign) {
                    mysqli_stmt_bind_param($insert_stmt, 'iii', $student_id, $selected_class_id, $post_subject_id);
                    mysqli_stmt_execute($insert_stmt);
                    if (mysqli_stmt_affected_rows($insert_stmt) >= 1) {
                        $updated_count++;
                    }
                    if (!isset($existing_choices[$student_id])) {
                        $existing_choices[$student_id] = [];
                    }
                    $existing_choices[$student_id][$post_subject_id] = true;
                } elseif ($has_current_subject) {
                    mysqli_stmt_bind_param($delete_stmt, 'iii', $student_id, $selected_class_id, $post_subject_id);
                    mysqli_stmt_execute($delete_stmt);
                    if (mysqli_stmt_affected_rows($delete_stmt) >= 1) {
                        $updated_count++;
                    }
                    unset($existing_choices[$student_id][$post_subject_id]);
                    if (empty($existing_choices[$student_id])) {
                        unset($existing_choices[$student_id]);
                    }
                }
            }

            mysqli_stmt_close($insert_stmt);
            mysqli_stmt_close($delete_stmt);

            $subject_counts = calculateSubjectCounts($existing_choices);
            $success = $updated_count > 0 ? "Saved elective assignments for {$updated_count} students." : 'No changes were necessary.';
            $_SESSION['manage_electives_success'] = $success;
            $redirectUrl = $_SERVER['PHP_SELF'] . '?class_key=' . rawurlencode($selected_class_key) . '&subject_id=' . (int)$selected_subject_id;
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
}

$selected_class_name = '';
foreach ($classes as $class_row) {
    if ((int)$class_row['class_id'] === $selected_class_id && (int)$class_row['section_id'] === $selected_section_id) {
        $selected_class_name = $class_row['label'] !== '' ? $class_row['label'] : $class_row['class_name'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Electives - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .elective-table { width:100%; border-collapse:collapse; table-layout:fixed; }
        .elective-table th, .elective-table td { border:1px solid #d0d4db; padding:10px 12px; text-align:left; color:#1e2432; }
        .elective-table th { background:#A6192E; color:#ffffff; font-weight:600; letter-spacing:0.02em; }
        .elective-table tbody tr:nth-child(even) { background:#f8f9fb; }
        .elective-table tbody tr:nth-child(odd) { background:#ffffff; }
        .elective-selector { display:flex; flex-wrap:wrap; gap:12px; margin-top:16px; }
        .elective-button { display:flex; flex-direction:column; align-items:flex-start; gap:4px; padding:10px 16px; border-radius:8px; border:1px solid #d0d4db; background:#ffffff; color:#1e2432; text-decoration:none; font-weight:600; min-width:200px; transition:background 0.2s ease, color 0.2s ease, border-color 0.2s ease; }
        .elective-button span:last-child { font-weight:500; font-size:0.9rem; color:#4c5264; }
        .elective-button:hover { background:#f8f9fb; border-color:#A6192E; }
        .elective-button.active { background:#A6192E; border-color:#A6192E; color:#ffffff; }
        .elective-button.active span:last-child { color:#ffffff; opacity:0.9; }
        .assigned-here { background:#f3faf6 !important; }
        .current-elective { font-weight:600; }
        .current-elective.other { color:#8a5200; }
        .checkbox-label { display:flex; align-items:center; gap:8px; cursor:pointer; user-select:none; }
        .checkbox-label input { width:18px; height:18px; }
        .checkbox-label span { font-weight:600; color:#1e2432; }
        .info-banner { padding:10px 14px; border-radius:8px; margin-bottom:16px; }
        .info-banner.info { background:#f0f4ff; color:#1a3c7c; border:1px solid #d1dcff; }
        .info-banner.warning { background:#fff7e6; color:#8a5200; border:1px solid #ffe1b3; }
        .info-banner.success { background:#edf7ed; color:#0f5132; border:1px solid #badbcc; }
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
            <a href="assign_teachers.php"><i class="fas fa-user-tag"></i> <span>Assign Teachers</span></a>
            <a href="manage_electives.php" class="active"><i class="fas fa-user-friends"></i> <span>Manage Electives</span></a>
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
                    <div class="card-header"><h5>Manage Elective Enrollments</h5></div>
                    <div class="card-body">
                        <form method="GET" style="display:flex; gap:16px; flex-wrap:wrap; align-items:flex-end;">
                            <div class="form-group" style="min-width:240px;">
                                <label>Select Class</label>
                                <select name="class_key" id="class-selector" required>
                                    <option value="">Choose a class</option>
                                    <?php foreach ($classes as $class_row): ?>
                                        <?php
                                            $optionValue = $class_row['class_id'] . '|' . $class_row['section_id'];
                                            $isSelected = ($selected_class_id === (int)$class_row['class_id']) && ($selected_section_id === (int)$class_row['section_id']);
                                            $label = $class_row['label'] !== '' ? $class_row['label'] : $class_row['class_name'];
                                        ?>
                                        <option value="<?php echo htmlspecialchars($optionValue); ?>" <?php echo $isSelected ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn">Load Students</button>
                        </form>
                        <?php if ($error): ?>
                            <div class="info-banner warning" style="margin-top:16px; font-weight:600;"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                               <div class="info-banner success" style="margin-top:16px; font-weight:600;"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        <?php if ($selected_class_id > 0): ?>
                            <?php if (empty($elective_subjects)): ?>
                                <div class="info-banner warning" style="margin-top:20px;">
                                    No elective subjects are mapped to <?php echo htmlspecialchars($selected_class_name); ?> yet. Create an elective via the subject form and assign it to this class first.
                                </div>
                            <?php elseif (empty($students)): ?>
                                <div class="info-banner warning" style="margin-top:20px;">No students found for this class.</div>
                            <?php else: ?>
                                <div class="info-banner info" style="margin-top:20px;">Choose an elective below to review or update its enrolled students.</div>
                                <div class="elective-selector">
                                    <?php foreach ($elective_subjects as $sub_id => $sub_name): ?>
                                        <?php
                                            $isActiveElective = $selected_subject_id === (int)$sub_id;
                                            $electiveLabel = $sub_name . ' (Elective)';
                                            $countForElective = $subject_counts[$sub_id] ?? 0;
                                            $electiveLink = $_SERVER['PHP_SELF'] . '?class_key=' . rawurlencode($selected_class_key) . '&subject_id=' . (int)$sub_id;
                                        ?>
                                        <a href="<?php echo htmlspecialchars($electiveLink); ?>" class="elective-button<?php echo $isActiveElective ? ' active' : ''; ?>">
                                            <span><?php echo htmlspecialchars($electiveLabel); ?></span>
                                            <span><?php echo (int)$countForElective; ?> students</span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($selected_subject_id > 0): ?>
                                    <?php
                                        $totalStudents = count($students);
                                        $studentsWithAssignments = 0;
                                        foreach ($students as $studentRow) {
                                            $sid = isset($studentRow['id']) ? (int)$studentRow['id'] : 0;
                                            if ($sid > 0 && !empty($existing_choices[$sid])) {
                                                $studentsWithAssignments++;
                                            }
                                        }
                                        $unassignedCount = max(0, $totalStudents - $studentsWithAssignments);
                                    ?>
                                    <form method="POST" style="margin-top:20px;">
                                        <input type="hidden" name="class_id" value="<?php echo (int)$selected_class_id; ?>">
                                        <input type="hidden" name="section_id" value="<?php echo (int)$selected_section_id; ?>">
                                        <input type="hidden" name="class_key" value="<?php echo htmlspecialchars($selected_class_key); ?>">
                                        <input type="hidden" name="subject_id" value="<?php echo (int)$selected_subject_id; ?>">
                                        <div class="info-banner info" style="margin-bottom:16px;">
                                            You are assigning students to <strong><?php echo htmlspecialchars($selected_subject_name); ?></strong>. Check the box for each student who should be enrolled. Currently, <?php echo (int)$unassignedCount; ?> students are unassigned.
                                        </div>
                                        <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:12px;">
                                            <label class="checkbox-label" style="margin:0;">
                                                <input type="checkbox" id="select-all-students">
                                                <span>Select all students</span>
                                            </label>
                                            <button type="button" class="btn" id="clear-all-students" style="background:#63666A;">Clear selection</button>
                                        </div>
                                        <div style="max-height:520px; overflow:auto;">
                                            <table class="elective-table" data-elective-table>
                                                <thead>
                                                    <tr>
                                                        <th style="width:140px;">Roll Number</th>
                                                        <th>Student Name</th>
                                                        <th style="width:240px;">Current Elective</th>
                                                        <th style="width:220px;">Assign to <?php echo htmlspecialchars($selected_subject_name); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($students as $student): ?>
                                                        <?php
                                                            $student_id = isset($student['id']) ? (int)$student['id'] : 0;
                                                            $current_assignments = $existing_choices[$student_id] ?? [];
                                                            $assigned_subject_ids = array_keys($current_assignments);
                                                            $label_parts = [];
                                                            foreach ($assigned_subject_ids as $assigned_subject_id) {
                                                                if (isset($elective_subjects[$assigned_subject_id])) {
                                                                    $label_parts[] = $elective_subjects[$assigned_subject_id] . ' (Elective)';
                                                                }
                                                            }
                                                            $current_label = $label_parts ? implode(', ', $label_parts) : 'Not Assigned';
                                                            $isAssignedHere = isset($current_assignments[$selected_subject_id]);
                                                            $hasOtherAssignments = !$isAssignedHere && !empty($current_assignments);
                                                            $checkboxId = 'elective_' . $student_id;
                                                            $isChecked = $isAssignedHere;
                                                        ?>
                                                        <tr class="<?php echo $isChecked ? 'assigned-here' : ''; ?>">
                                                            <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                                            <td><?php echo htmlspecialchars($student['name_display'] ?? $student['name']); ?></td>
                                                            <td><span class="current-elective<?php echo $hasOtherAssignments ? ' other' : ''; ?>"><?php echo htmlspecialchars($current_label); ?></span></td>
                                                            <td>
                                                                <label class="checkbox-label" for="<?php echo htmlspecialchars($checkboxId); ?>">
                                                                    <input type="checkbox" id="<?php echo htmlspecialchars($checkboxId); ?>" name="selected_students[]" value="<?php echo (int)$student_id; ?>" <?php echo $isChecked ? 'checked' : ''; ?> data-student-checkbox>
                                                                    <span>Enrolled</span>
                                                                </label>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div style="margin-top:18px; display:flex; gap:12px;">
                                            <button type="submit" class="btn">Save Assignments</button>
                                            <?php $resetLink = $_SERVER['PHP_SELF'] . '?class_key=' . rawurlencode($selected_class_key) . '&subject_id=' . (int)$selected_subject_id; ?>
                                            <a href="<?php echo htmlspecialchars($resetLink); ?>" class="btn" style="background:#63666A;">Reset</a>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="info-banner info" style="margin-top:20px;">Select an elective above to load the student list.</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="info-banner info" style="margin-top:20px;">Select a class to begin assigning electives to students.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function () {
            var classSelect = document.getElementById('class-selector');
            if (classSelect) {
                classSelect.addEventListener('change', function () {
                    var targetValue = this.value;
                    var basePath = window.location.pathname;
                    if (!targetValue) {
                        window.location.href = basePath;
                        return;
                    }
                    var params = new URLSearchParams(window.location.search);
                    params.set('class_key', targetValue);
                    params.delete('subject_id');
                    window.location.href = basePath + '?' + params.toString();
                });
            }
        })();

        (function () {
            var table = document.querySelector('[data-elective-table]');
            if (!table) {
                return;
            }

            var checkboxes = Array.prototype.slice.call(table.querySelectorAll('input[data-student-checkbox]'));
            if (checkboxes.length === 0) {
                return;
            }

            var selectAllBox = document.getElementById('select-all-students');
            var clearButton = document.getElementById('clear-all-students');

            var updateRowState = function (checkbox) {
                var row = checkbox.closest('tr');
                if (!row) {
                    return;
                }
                if (checkbox.checked) {
                    row.classList.add('assigned-here');
                } else {
                    row.classList.remove('assigned-here');
                }
            };

            var syncSelectAll = function () {
                if (!selectAllBox) {
                    return;
                }
                var checkedCount = 0;
                checkboxes.forEach(function (checkbox) {
                    if (checkbox.checked) {
                        checkedCount++;
                    }
                });
                if (checkedCount === checkboxes.length) {
                    selectAllBox.checked = true;
                    selectAllBox.indeterminate = false;
                } else if (checkedCount === 0) {
                    selectAllBox.checked = false;
                    selectAllBox.indeterminate = false;
                } else {
                    selectAllBox.checked = false;
                    selectAllBox.indeterminate = true;
                }
            };

            var setAll = function (state) {
                checkboxes.forEach(function (checkbox) {
                    if (checkbox.checked !== state) {
                        checkbox.checked = state;
                    }
                    updateRowState(checkbox);
                });
                if (selectAllBox) {
                    selectAllBox.indeterminate = false;
                    selectAllBox.checked = !!state;
                }
                syncSelectAll();
            };

            checkboxes.forEach(function (checkbox) {
                updateRowState(checkbox);
                checkbox.addEventListener('change', function () {
                    updateRowState(checkbox);
                    syncSelectAll();
                });
            });

            if (selectAllBox) {
                selectAllBox.addEventListener('change', function () {
                    setAll(selectAllBox.checked);
                });
                syncSelectAll();
            }

            if (clearButton) {
                clearButton.addEventListener('click', function () {
                    setAll(false);
                });
            }
        })();
    </script>
</body>
</html>

