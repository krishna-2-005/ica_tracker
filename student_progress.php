<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';

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
    $marks_sql = "SELECT s.subject_name,
                         ic.id AS component_id,
                         ic.component_name,
                         ic.instances,
                         ic.marks_per_instance,
                         ic.total_marks,
                         ic.scaled_total_marks,
                         ism.marks,
                         ism.instance_number
                  FROM ica_student_marks ism
                  JOIN ica_components ic ON ism.component_id = ic.id
                  JOIN subjects s ON ic.subject_id = s.id
                  WHERE ism.student_id = ?
                  ORDER BY s.subject_name, ic.component_name, ism.instance_number";

    $stmt = mysqli_prepare($conn, $marks_sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Unable to load marks for the selected student.']);
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'i', $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $subject_components = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $subject = $row['subject_name'] ?? 'Unknown Subject';
        $component_id = isset($row['component_id']) ? (int)$row['component_id'] : 0;

        if (!isset($subject_components[$subject])) {
            $subject_components[$subject] = [];
        }

        if (!isset($subject_components[$subject][$component_id])) {
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

            $subject_components[$subject][$component_id] = [
                'component_name' => $row['component_name'] ?? 'Component',
                'instances' => $instances,
                'max_total' => $scaled_total,
                'scale_ratio' => $scale_ratio,
                'raw_total' => 0.0,
                'has_any' => false,
                'has_numeric' => false,
            ];
        }

        $subject_components[$subject][$component_id]['has_any'] = true;
        if ($row['marks'] !== null) {
            $subject_components[$subject][$component_id]['raw_total'] += (float)$row['marks'];
            $subject_components[$subject][$component_id]['has_numeric'] = true;
        }
    }

    mysqli_stmt_close($stmt);

    $subjects_payload = [];
    foreach ($subject_components as $subject => $components) {
        $subjects_payload[$subject] = [];
        foreach ($components as $component) {
            $label = build_component_sum_label($component['component_name'], $component['instances'], $component['max_total']);
            $is_absent = !$component['has_numeric'] && $component['has_any'];
            $scaled_mark = null;
            if ($component['has_numeric']) {
                $ratio = isset($component['scale_ratio']) ? (float)$component['scale_ratio'] : 1.0;
                $raw_total = isset($component['raw_total']) ? (float)$component['raw_total'] : 0.0;
                $scaled_mark = $raw_total * $ratio;
            }

            $subjects_payload[$subject][] = [
                'component_name' => $label,
                'marks' => $component['has_numeric'] ? $scaled_mark : null,
                'max_marks' => $component['max_total'],
                'is_absent' => $is_absent,
            ];
        }
    }

    $all_subjects = [];
    foreach ($subjects_payload as $components) {
        foreach ($components as $component) {
            $label = $component['component_name'];
            if (!isset($all_subjects[$label])) {
                $all_subjects[$label] = [
                    'total' => 0.0,
                    'max_total' => 0.0,
                    'entries' => 0,
                    'numeric_entries' => 0,
                    'absent_entries' => 0,
                ];
            }

            $all_subjects[$label]['max_total'] += (float)$component['max_marks'];
            $all_subjects[$label]['entries']++;

            if (!empty($component['is_absent'])) {
                $all_subjects[$label]['absent_entries']++;
            }

            if ($component['marks'] !== null) {
                $all_subjects[$label]['total'] += (float)$component['marks'];
                $all_subjects[$label]['numeric_entries']++;
            }
        }
    }

    $subjects_payload['All Subjects'] = [];
    foreach ($all_subjects as $label => $aggregate) {
        $has_numeric = $aggregate['numeric_entries'] > 0;
        $avg_marks = $has_numeric ? $aggregate['total'] / $aggregate['numeric_entries'] : null;
        $avg_max = $aggregate['entries'] > 0 ? $aggregate['max_total'] / $aggregate['entries'] : 0.0;

        $subjects_payload['All Subjects'][] = [
            'component_name' => $label,
            'marks' => $has_numeric ? $avg_marks : null,
            'max_marks' => $avg_max,
            'is_absent' => !$has_numeric && $aggregate['absent_entries'] > 0,
        ];
    }

    echo json_encode(['subjects' => $subjects_payload]);
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
$semester_filter = isset($_GET['semester']) && $_GET['semester'] !== '' ? trim($_GET['semester']) : '';
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
    $sem_sql = "SELECT DISTINCT semester FROM classes WHERE school = ? ORDER BY CAST(semester AS UNSIGNED)";
    $sem_stmt = mysqli_prepare($conn, $sem_sql);
    if ($sem_stmt) {
        mysqli_stmt_bind_param($sem_stmt, "s", $school_filter);
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
                 JOIN (
                        SELECT s2.sap_id, MAX(CAST(c2.semester AS UNSIGNED)) AS max_semester
                        FROM students s2
                        JOIN classes c2 ON s2.class_id = c2.id
                        WHERE c2.school = ?
                        GROUP BY s2.sap_id
                 ) latest_class ON s.sap_id = latest_class.sap_id AND CAST(c.semester AS UNSIGNED) = latest_class.max_semester
                 WHERE c.school = ?";

    $types = 'ss';
    $params = [$school_filter, $school_filter];

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
        #studentDetailChartContainer { height: 250px; }
        .modal-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
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
                                    <select name="class_id" id="class_filter">
                                        <option value="">All classes</option>
                                        <?php foreach ($classes_list as $class_option): ?>
                                            <option value="<?php echo (int)$class_option['id']; ?>" <?php echo $class_filter === (int)$class_option['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($class_option['class_name']); ?></option>
                                        <?php endforeach; ?>
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
                    <h5>Performance Overview</h5>
                    <div class="form-group"><select id="subjectFilter" class="form-control" style="width: auto;"></select></div>
                </div>
                <div id="studentDetailChartContainer"><canvas id="studentDetailChart"></canvas></div>
                <h5 style="margin-top: 20px;">Component-wise Marks</h5>
                <table id="modalMarksTable">
                    <thead><tr><th>Component</th><th>Marks Obtained</th><th>Max Marks</th></tr></thead>
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
                classFilter.innerHTML = '<option value="">All classes</option>';
                sectionFilter.innerHTML = '<option value="">All divisions</option>';
                sectionContainer.style.display = 'none';

                if (!school) {
                    return;
                }

                fetch(`get_semesters.php?school=${encodeURIComponent(school)}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(item => {
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
                        }
                    })
                    .catch(() => {});
            }

            function populateClasses(school, semester, selectedClass, selectedSection) {
                classFilter.innerHTML = '<option value="">All classes</option>';
                sectionFilter.innerHTML = '<option value="">All divisions</option>';
                sectionContainer.style.display = 'none';

                if (!school || !semester) {
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
                    classFilter.innerHTML = '<option value="">All classes</option>';
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
                const labels = components.map(item => item.component_name);
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
                    const numeric = Number(item && item.max_marks);
                    return Number.isFinite(numeric) ? numeric : 0;
                });

                if (!detailChart) {
                    const ctx = document.getElementById('studentDetailChart').getContext('2d');
                    detailChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [
                                { label: 'Marks Obtained', data: marks, backgroundColor: 'rgba(166, 25, 46, 0.7)' },
                                { label: 'Max Marks', data: maxMarks, backgroundColor: 'rgba(54, 162, 235, 0.5)' }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: { beginAtZero: true, ticks: { precision: 0 } }
                            }
                        }
                    });
                } else {
                    detailChart.data.labels = labels;
                    detailChart.data.datasets[0].data = marks;
                    detailChart.data.datasets[1].data = maxMarks;
                    detailChart.update();
                }

                const tbody = document.querySelector('#modalMarksTable tbody');
                tbody.innerHTML = '';

                if (!components.length) {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');
                    cell.colSpan = 3;
                    cell.textContent = 'No marks recorded for this selection.';
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
                    marksCell.textContent = componentAbsent ? 'AB' : formatNumericValue(component.marks);
                    const maxCell = document.createElement('td');
                    maxCell.textContent = formatNumericValue(component.max_marks);
                    row.appendChild(nameCell);
                    row.appendChild(marksCell);
                    row.appendChild(maxCell);
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
