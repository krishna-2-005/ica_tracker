<?php
session_start();
include 'db_connect.php';
require_once __DIR__ . '/includes/academic_context.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header('Location: login.php');
    exit;
}

$teacher_id = (int)$_SESSION['user_id'];
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
$error = '';
$success = '';

$classColumnCheck = mysqli_query($conn, "SHOW COLUMNS FROM ica_components LIKE 'class_id'");
if ($classColumnCheck && mysqli_num_rows($classColumnCheck) === 0) {
    $alterSql = "ALTER TABLE ica_components ADD COLUMN class_id INT NULL AFTER subject_id, ADD KEY idx_class_id (class_id)";
    if (!mysqli_query($conn, $alterSql)) {
        error_log('Failed to add class_id column in manage_ica_marks: ' . mysqli_error($conn), 3, 'C:\\xampp\\php\\logs\\php_error_log');
    }
}
if ($classColumnCheck) {
    mysqli_free_result($classColumnCheck);
}

// Check for a success/error message from the session after a redirect
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Fetch subjects assigned to the teacher via teacher_subject_assignments
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
if ($activeTermId > 0) {
    mysqli_stmt_bind_param($stmt_subjects, "ii", $teacher_id, $activeTermId);
} else {
    mysqli_stmt_bind_param($stmt_subjects, "i", $teacher_id);
}
mysqli_stmt_execute($stmt_subjects);
$subjects_result = mysqli_stmt_get_result($stmt_subjects);

function get_component_context(mysqli $conn, int $teacher_id, int $component_id, ?int $requested_class_id = null, ?int $requested_section_id = null): array {
    global $activeTermId;
    $context = [
        'error' => '',
        'component' => null,
        'students' => [],
    ];

    if ($component_id <= 0) {
        $context['error'] = "Please select a component before saving marks.";
        return $context;
    }

    $component_stmt = mysqli_prepare($conn, "SELECT id, component_name, subject_id, instances, marks_per_instance, class_id FROM ica_components WHERE id = ?");
    if (!$component_stmt) {
        $context['error'] = "Unable to load component details.";
        return $context;
    }

    mysqli_stmt_bind_param($component_stmt, "i", $component_id);
    mysqli_stmt_execute($component_stmt);
    $component_res = mysqli_stmt_get_result($component_stmt);
    $component_row = $component_res ? mysqli_fetch_assoc($component_res) : null;
    if ($component_res) {
        mysqli_free_result($component_res);
    }
    mysqli_stmt_close($component_stmt);

    if (!$component_row) {
        $context['error'] = "The selected component could not be found.";
        return $context;
    }

    $component_row['component_name'] = trim((string)$component_row['component_name']);
    $component_row['instances'] = isset($component_row['instances']) ? (int)$component_row['instances'] : 0;
    $component_row['subject_id'] = isset($component_row['subject_id']) ? (int)$component_row['subject_id'] : 0;
    $component_row['marks_per_instance'] = isset($component_row['marks_per_instance']) ? (float)$component_row['marks_per_instance'] : 0.0;
    $component_row['class_id'] = isset($component_row['class_id']) ? (int)$component_row['class_id'] : 0;

    if ($component_row['subject_id'] <= 0) {
        $context['error'] = "Unable to identify the subject for the selected component.";
        return $context;
    }

    $subject_id = $component_row['subject_id'];
    $class_id = $component_row['class_id'];
    $requested_section_id = $requested_section_id !== null ? (int)$requested_section_id : null;
    if ($requested_class_id !== null && $requested_class_id > 0) {
        if ($class_id > 0 && $class_id !== $requested_class_id) {
            $context['error'] = "The selected class does not match this component.";
            return $context;
        }
        if ($class_id <= 0) {
            $class_id = $requested_class_id;
            $component_row['class_id'] = $class_id;
        }
    }
    $subject_type = determine_subject_type($conn, $subject_id);
    $is_elective_subject = ($subject_type === 'elective');

    $students_query = "
        SELECT DISTINCT s.id, s.sap_id, s.roll_number, s.name
                FROM teacher_subject_assignments tsa
                JOIN classes c ON c.id = tsa.class_id
                JOIN students s ON s.class_id = tsa.class_id";
    if ($is_elective_subject) {
        $students_query .= "
                JOIN student_elective_choices sec ON sec.student_id = s.id
                    AND sec.class_id = tsa.class_id
                    AND sec.subject_id = tsa.subject_id";
    }
    $students_query .= "
                WHERE tsa.teacher_id = ?
                    AND tsa.subject_id = ?";
    $types = "ii";
    $params = [$teacher_id, $subject_id];
    if ($class_id > 0) {
        $students_query .= " AND tsa.class_id = ?";
        $types .= "i";
        $params[] = $class_id;
    }
    if ($requested_section_id !== null && $requested_section_id > 0) {
        $students_query .= " AND COALESCE(tsa.section_id, 0) = ?";
        $types .= "i";
        $params[] = $requested_section_id;
    }
    $students_query .= "
                    AND (tsa.section_id IS NULL OR COALESCE(tsa.section_id, 0) = COALESCE(s.section_id, 0))";
    if ($activeTermId > 0) {
        $students_query .= " AND c.academic_term_id = ?";
        $types .= "i";
        $params[] = $activeTermId;
    }
    $students_query .= "
        ORDER BY s.roll_number ASC";
    if ($requested_section_id !== null && $requested_section_id > 0) {
        $students_query = str_replace("ORDER BY", " AND COALESCE(s.section_id, 0) = ?\n        ORDER BY", $students_query);
        $types .= "i";
        $params[] = $requested_section_id;
    }

    $students_stmt = mysqli_prepare($conn, $students_query);
    if ($students_stmt) {
        mysqli_stmt_bind_param($students_stmt, $types, ...$params);
        mysqli_stmt_execute($students_stmt);
        $students_res = mysqli_stmt_get_result($students_stmt);
        if ($students_res) {
            while ($student = mysqli_fetch_assoc($students_res)) {
                $context['students'][] = [
                    'id' => (int)$student['id'],
                    'sap_id' => trim((string)$student['sap_id']),
                    'roll_number' => trim((string)$student['roll_number']),
                    'name' => $student['name'],
                ];
            }
            mysqli_free_result($students_res);
        }
        mysqli_stmt_close($students_stmt);
    }

    $context['component'] = $component_row;
    return $context;
}

function summarize_list(array $entries): string {
    if (empty($entries)) {
        return '';
    }
    $preview = array_slice($entries, 0, 5);
    $text = implode(', ', $preview);
    if (count($entries) > 5) {
        $text .= ', ...';
    }
    return $text;
}

if (!function_exists('determine_subject_type')) {
    function determine_subject_type(mysqli $conn, int $subject_id): string {
        if ($subject_id <= 0) {
            return 'regular';
        }
        $subject_type = 'regular';
        $stmt = mysqli_prepare($conn, "SELECT COALESCE(sd.subject_type, 'regular') AS subject_type FROM subjects s LEFT JOIN subject_details sd ON sd.subject_id = s.id WHERE s.id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $subject_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result && ($row = mysqli_fetch_assoc($result))) {
                $subject_type = strtolower(trim((string)$row['subject_type']));
            }
            if ($result) {
                mysqli_free_result($result);
            }
            mysqli_stmt_close($stmt);
        }
        return $subject_type === '' ? 'regular' : $subject_type;
    }
}

function build_component_instance_header_label(string $component_name, int $instance_number, int $total_instances, ?float $marks_per_instance = null): string {
    $component_name = trim($component_name);
    if ($component_name === '') {
        return '';
    }

    $suffix = '';
    if ($marks_per_instance !== null && $marks_per_instance > 0) {
        $formatted = format_component_mark_value($marks_per_instance);
        if ($formatted !== '') {
            $suffix = ' (/ ' . $formatted . ')';
        }
    }

    if ($total_instances > 1 && $instance_number > 0) {
        return $component_name . ' ' . $instance_number . $suffix;
    }

    return $component_name . $suffix;
}

function format_component_mark_value(?float $value): string {
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

function format_excel_numeric_literal(float $value, int $precision = 10): string {
    $formatted = number_format($value, $precision, '.', '');
    $formatted = rtrim(rtrim($formatted, '0'), '.');
    if ($formatted === '') {
        return '0';
    }
    return $formatted;
}

function excel_column_from_index(int $index): string {
    $index += 1; // convert to 1-based
    $column = '';
    while ($index > 0) {
        $remainder = ($index - 1) % 26;
        $column = chr(65 + $remainder) . $column;
        $index = intdiv($index - 1, 26);
    }
    return $column;
}

function build_component_total_header_label(string $component_name, int $total_instances, ?float $scaled_total = null): string {
    $component_name = trim($component_name);
    if ($component_name === '') {
        return '';
    }

    $formatted_max = format_component_mark_value($scaled_total);
    if ($formatted_max !== '') {
        return $component_name . ' (/ ' . $formatted_max . ')';
    }

    if ($total_instances > 1) {
        $suffix = implode(' ', range(1, $total_instances));
        return $component_name . ' (Sum of ' . $suffix . ')';
    }

    return $component_name;
}

function build_component_total_formula(array $instance_column_indexes, int $row_number, float $raw_total, float $scaled_total): string {
    if (empty($instance_column_indexes)) {
        return '';
    }

    if ($scaled_total <= 0) {
        return '=0';
    }

    $first_column = excel_column_from_index(min($instance_column_indexes));
    $last_column = excel_column_from_index(max($instance_column_indexes));
    $cell_range = $first_column . $row_number;
    if ($last_column !== $first_column) {
        $cell_range .= ':' . $last_column . $row_number;
    }

    $ratio = ($raw_total > 0) ? ($scaled_total / $raw_total) : 1.0;
    $ratio = max($ratio, 0.0);

    $sum_expression = (count($instance_column_indexes) === 1)
        ? $cell_range
        : 'SUM(' . $cell_range . ')';

    if (abs($ratio - 1.0) < 0.00001) {
        return '=ROUND(' . $sum_expression . ', 2)';
    }

    $ratio_literal = format_excel_numeric_literal($ratio, 12);
    $scaled_literal = format_excel_numeric_literal($scaled_total, 4);
    return '=MIN(' . $scaled_literal . ', ROUND(' . $sum_expression . ' * ' . $ratio_literal . ', 2))';
}

function upsert_student_mark(mysqli $conn, int $teacher_id, int $student_id, int $component_id, int $instance_number, ?float $marks_value): bool {
    $teacher_id = max(0, $teacher_id);
    $student_id = max(0, $student_id);
    $component_id = max(0, $component_id);
    $instance_number = max(0, $instance_number);

    $value_sql = $marks_value === null
        ? 'NULL'
        : sprintf('%.5f', $marks_value);

    $query = sprintf(
        "INSERT INTO ica_student_marks (teacher_id, student_id, component_id, instance_number, marks, updated_at) VALUES (%d, %d, %d, %d, %s, NOW()) " .
        "ON DUPLICATE KEY UPDATE marks = VALUES(marks), updated_at = NOW()",
        $teacher_id,
        $student_id,
        $component_id,
        $instance_number,
        $value_sql
    );

    return mysqli_query($conn, $query) === true;
}

function fetch_components_for_template(mysqli $conn, int $teacher_id, int $subject_id, ?int $class_id, array $filter_component_ids = []): array {
    $components = [];
    $seen_ids = [];
    $targets = [];

    if ($class_id !== null && $class_id > 0) {
        $targets[] = $class_id;
    }
    $targets[] = null;

    $id_filter = [];
    foreach ($filter_component_ids as $component_id) {
        $component_id = (int)$component_id;
        if ($component_id > 0) {
            $id_filter[$component_id] = true;
        }
    }

    foreach ($targets as $target_class_id) {
        $sql = "SELECT id, component_name, instances, marks_per_instance, total_marks, scaled_total_marks, class_id
                FROM ica_components
                WHERE teacher_id = ? AND subject_id = ?";
        $types = "ii";
        $params = [$teacher_id, $subject_id];

        if (!empty($id_filter)) {
            $placeholders = implode(',', array_fill(0, count($id_filter), '?'));
            $sql .= " AND id IN ($placeholders)";
            $types .= str_repeat('i', count($id_filter));
            $params = array_merge($params, array_keys($id_filter));
        }

        if ($target_class_id === null) {
            $sql .= " AND (class_id IS NULL OR class_id = 0)";
        } else {
            $sql .= " AND class_id = ?";
            $types .= "i";
            $params[] = $target_class_id;
        }

        $sql .= " ORDER BY component_name, id";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            continue;
        }

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $component_id = isset($row['id']) ? (int)$row['id'] : 0;
                if ($component_id <= 0 || isset($seen_ids[$component_id])) {
                    continue;
                }
                $seen_ids[$component_id] = true;
                $components[] = [
                    'id' => $component_id,
                    'component_name' => trim((string)$row['component_name']),
                    'instances' => isset($row['instances']) ? (int)$row['instances'] : 0,
                    'marks_per_instance' => isset($row['marks_per_instance']) ? (float)$row['marks_per_instance'] : 0.0,
                    'total_marks' => isset($row['total_marks']) ? (float)$row['total_marks'] : 0.0,
                    'scaled_total_marks' => isset($row['scaled_total_marks']) ? (float)$row['scaled_total_marks'] : 0.0,
                    'class_id' => isset($row['class_id']) ? (int)$row['class_id'] : null,
                ];
            }
            mysqli_free_result($result);
        }
        mysqli_stmt_close($stmt);
    }

    if (!empty($id_filter)) {
        $components = array_values(array_filter($components, static function(array $component) use ($id_filter) {
            return isset($id_filter[$component['id']]);
        }));
    }

    usort($components, static function(array $a, array $b): int {
        return strcasecmp($a['component_name'], $b['component_name']) ?: ($a['id'] <=> $b['id']);
    });

    return $components;
}

function fetch_students_for_template(mysqli $conn, int $teacher_id, int $subject_id, int $class_id, int $section_id): array {
    global $activeTermId;
    $students = [];
    if ($subject_id <= 0 || $class_id <= 0) {
        return $students;
    }

    $subject_type = determine_subject_type($conn, $subject_id);
    $is_elective = ($subject_type === 'elective');

        $query = "
            SELECT DISTINCT s.id, s.name, s.roll_number, s.sap_id
            FROM teacher_subject_assignments tsa
            JOIN classes c ON c.id = tsa.class_id
            JOIN students s ON s.class_id = tsa.class_id";

    if ($is_elective) {
        $query .= "
            JOIN student_elective_choices sec ON sec.student_id = s.id
                AND sec.class_id = tsa.class_id
                AND sec.subject_id = tsa.subject_id";
    }

    $query .= "
            WHERE tsa.teacher_id = ?
                AND tsa.subject_id = ?
                AND tsa.class_id = ?";

    $types = "iii";
    $params = [$teacher_id, $subject_id, $class_id];

    if ($section_id > 0) {
        $query .= " AND COALESCE(tsa.section_id, 0) = ?";
        $types .= "i";
        $params[] = $section_id;
    }

    $query .= "
                AND (tsa.section_id IS NULL OR COALESCE(tsa.section_id, 0) = COALESCE(s.section_id, 0))";
    if ($activeTermId > 0) {
        $query .= " AND c.academic_term_id = ?";
        $types .= "i";
        $params[] = $activeTermId;
    }

    if ($section_id > 0) {
        $query .= " AND COALESCE(s.section_id, 0) = ?";
        $types .= "i";
        $params[] = $section_id;
    }

    $query .= "
            ORDER BY s.roll_number ASC";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        return $students;
    }

    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $students[] = [
                'id' => isset($row['id']) ? (int)$row['id'] : 0,
                'name' => $row['name'] ?? '',
                'roll_number' => $row['roll_number'] ?? '',
                'sap_id' => $row['sap_id'] ?? '',
            ];
        }
        mysqli_free_result($result);
    }

    mysqli_stmt_close($stmt);
    return $students;
}

function resolve_template_subject_and_class_labels(mysqli $conn, int $subject_id, int $class_id): array {
    $subject_name = '';
    $class_name = '';

    if ($subject_id > 0) {
        $stmt = mysqli_prepare($conn, "SELECT subject_name FROM subjects WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $subject_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result && ($row = mysqli_fetch_assoc($result))) {
                $subject_name = trim((string)$row['subject_name']);
            }
            if ($result) {
                mysqli_free_result($result);
            }
            mysqli_stmt_close($stmt);
        }
    }

    if ($class_id > 0) {
        $stmt = mysqli_prepare($conn, "SELECT class_name FROM classes WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $class_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result && ($row = mysqli_fetch_assoc($result))) {
                $class_name = trim((string)$row['class_name']);
            }
            if ($result) {
                mysqli_free_result($result);
            }
            mysqli_stmt_close($stmt);
        }
    }

    return [$subject_name, $class_name];
}

if (isset($_POST['download_template'])) {
    $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $section_id = isset($_POST['section_id']) ? (int)$_POST['section_id'] : 0;
    $selected_payload_raw = $_POST['selected_components_payload'] ?? '';
    $requested_component_ids = [];

    if ($selected_payload_raw !== '') {
        $decoded = json_decode($selected_payload_raw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                $component_id = null;
                if (is_array($item) && isset($item['component_id'])) {
                    $component_id = (int)$item['component_id'];
                } elseif (is_numeric($item)) {
                    $component_id = (int)$item;
                }
                if ($component_id !== null && $component_id > 0) {
                    $requested_component_ids[$component_id] = true;
                }
            }
        }
    }

    if ($subject_id <= 0) {
        $_SESSION['error_message'] = "Please select a subject before downloading the template.";
        header('Location: manage_ica_marks.php');
        exit;
    }

    if ($class_id <= 0) {
        $_SESSION['error_message'] = "Please select a class before downloading the template.";
        header('Location: manage_ica_marks.php');
        exit;
    }

    $component_ids = array_keys($requested_component_ids);
    $components = fetch_components_for_template($conn, $teacher_id, $subject_id, $class_id, $component_ids);

    if (empty($components)) {
        $_SESSION['error_message'] = "No ICA components were found for the selected class.";
        header('Location: manage_ica_marks.php');
        exit;
    }

    $students = fetch_students_for_template($conn, $teacher_id, $subject_id, $class_id, $section_id);
    if (empty($students)) {
        $_SESSION['error_message'] = "No students were found for the selected class.";
        header('Location: manage_ica_marks.php');
        exit;
    }

    $headers = ['S/N', 'ROLL NO', 'SAP ID', 'NAME OF STUDENT'];
    $component_blueprints = [];

    foreach ($components as $component) {
        $component_name = $component['component_name'];
        $instances = isset($component['instances']) ? (int)$component['instances'] : 0;
        if ($instances <= 0) {
            $instances = 1;
        }

        $marks_per_instance = isset($component['marks_per_instance'])
            ? (float)$component['marks_per_instance']
            : null;

        $raw_total = isset($component['total_marks']) ? (float)$component['total_marks'] : 0.0;
        if ($raw_total <= 0 && isset($component['marks_per_instance'])) {
            $raw_total = $instances * (float)$component['marks_per_instance'];
        }

        $scaled_total = isset($component['scaled_total_marks']) ? (float)$component['scaled_total_marks'] : 0.0;
        if ($scaled_total <= 0 && $raw_total > 0) {
            $scaled_total = $raw_total;
        }

        $instance_labels = [];
        for ($i = 1; $i <= $instances; $i++) {
            $label = build_component_instance_header_label($component_name, $i, $instances, $marks_per_instance);
            $headers[] = $label;
            $instance_labels[] = $label;
        }

        $total_label = '';
        if ($instances > 1) {
            $total_label = build_component_total_header_label($component_name, $instances, $scaled_total);
            $headers[] = $total_label;
        }

        $component_blueprints[] = [
            'component_id' => $component['id'],
            'instance_labels' => $instance_labels,
            'total_label' => $total_label,
            'instances' => $instances,
            'scaled_total' => $scaled_total,
            'raw_total' => $raw_total,
            'marks_per_instance' => $marks_per_instance,
        ];
    }

    [$subject_name, $class_name] = resolve_template_subject_and_class_labels($conn, $subject_id, $class_id);

    $filename_parts = ['ica_marks_template'];
    if ($subject_name !== '') {
        $filename_parts[] = preg_replace('/[^A-Za-z0-9]+/', '_', strtolower($subject_name));
    }
    if ($class_name !== '') {
        $filename_parts[] = preg_replace('/[^A-Za-z0-9]+/', '_', strtolower($class_name));
    }
    if ($section_id > 0) {
        $filename_parts[] = 'section_' . $section_id;
    }
    $filename = implode('_', array_filter($filename_parts));
    $filename = preg_replace('/_+/', '_', $filename);
    $filename = trim($filename, '_');
    if ($filename === '') {
        $filename = 'ica_marks_template';
    }
    $filename .= '.csv';

    $output = fopen('php://output', 'w');
    if ($output === false) {
        $_SESSION['error_message'] = "Unable to prepare the template for download.";
        header('Location: manage_ica_marks.php');
        exit;
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache');

    fputcsv($output, $headers);

    $serial = 1;
    foreach ($students as $student) {
        $row_number = $serial + 1;
        $row = [
            $serial++,
            $student['roll_number'] ?? '',
            $student['sap_id'] ?? '',
            $student['name'] ?? '',
        ];

        foreach ($component_blueprints as $blueprint) {
            $instance_column_indexes = [];
            foreach ($blueprint['instance_labels'] as $_) {
                $instance_column_indexes[] = count($row);
                $row[] = '';
            }
            if ($blueprint['total_label'] !== '') {
                $formula = build_component_total_formula(
                    $instance_column_indexes,
                    $row_number,
                    isset($blueprint['raw_total']) ? (float)$blueprint['raw_total'] : 0.0,
                    isset($blueprint['scaled_total']) ? (float)$blueprint['scaled_total'] : 0.0
                );
                $row[] = $formula;
            }
        }

        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

if (isset($_POST['upload_csv'])) {
    $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $section_id = isset($_POST['section_id']) ? (int)$_POST['section_id'] : 0;
    $selected_payload_raw = $_POST['selected_components_payload'] ?? '';

    $requested_component_ids = [];
    if ($selected_payload_raw !== '') {
        $decoded = json_decode($selected_payload_raw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $entry) {
                if (is_array($entry) && isset($entry['component_id'])) {
                    $component_id = (int)$entry['component_id'];
                } elseif (is_numeric($entry)) {
                    $component_id = (int)$entry;
                } else {
                    $component_id = 0;
                }
                if ($component_id > 0) {
                    $requested_component_ids[$component_id] = true;
                }
            }
        }
    }

    if (empty($requested_component_ids)) {
        if ($subject_id <= 0) {
            $_SESSION['error_message'] = "Please select a subject before uploading marks.";
            header('Location: manage_ica_marks.php');
            exit;
        }
        if ($class_id <= 0) {
            $_SESSION['error_message'] = "Please select a class before uploading marks.";
            header('Location: manage_ica_marks.php');
            exit;
        }
        $all_components = fetch_components_for_template($conn, $teacher_id, $subject_id, $class_id, []);
        foreach ($all_components as $component_row) {
            $component_id = isset($component_row['id']) ? (int)$component_row['id'] : 0;
            if ($component_id > 0) {
                $requested_component_ids[$component_id] = true;
            }
        }
    }

    if (empty($requested_component_ids)) {
        $_SESSION['error_message'] = "No ICA components were found for the selected class.";
        header('Location: manage_ica_marks.php');
        exit;
    }

    $component_contexts = [];
    $component_order = [];
    $students_reference = null;
    $expected_by_sap = [];
    $normalize_sap = static function ($value) {
        return strtoupper(trim((string)$value));
    };

    foreach (array_keys($requested_component_ids) as $component_id) {
        $context = get_component_context($conn, $teacher_id, $component_id, $class_id, $section_id);
        $component = $context['component'];
        $students = $context['students'];
        $validation_error = $context['error'];

        if ($validation_error !== '') {
            $_SESSION['error_message'] = $validation_error;
            header('Location: manage_ica_marks.php');
            exit;
        }

        if ($class_id > 0 && $component && isset($component['class_id']) && $component['class_id'] > 0 && $component['class_id'] !== $class_id) {
            $_SESSION['error_message'] = "The selected class does not match one of the chosen components.";
            header('Location: manage_ica_marks.php');
            exit;
        }

        if ($section_id > 0 && empty($students)) {
            $_SESSION['error_message'] = "No students are assigned to the chosen class division. Please verify the selection.";
            header('Location: manage_ica_marks.php');
            exit;
        }

        if (empty($students)) {
            $_SESSION['error_message'] = "No students are currently assigned to component '" . $component['component_name'] . "'.";
            header('Location: manage_ica_marks.php');
            exit;
        }

        if ($students_reference === null) {
            $students_reference = $students;
            foreach ($students_reference as $student) {
                $sap = $normalize_sap($student['sap_id'] ?? '');
                if ($sap !== '') {
                    $expected_by_sap[$sap] = $student;
                }
            }
        } else {
            if (count($students_reference) !== count($students)) {
                $_SESSION['error_message'] = "Selected components must belong to the same class roster. Please upload them separately.";
                header('Location: manage_ica_marks.php');
                exit;
            }
            $current_map = [];
            foreach ($students as $student) {
                $sap = $normalize_sap($student['sap_id'] ?? '');
                if ($sap !== '') {
                    $current_map[$sap] = true;
                }
            }
            $missing_from_current = array_diff(array_keys($expected_by_sap), array_keys($current_map));
            if (!empty($missing_from_current)) {
                $_SESSION['error_message'] = "Selected components must share the same student list (SAP IDs mismatch).";
                header('Location: manage_ica_marks.php');
                exit;
            }
        }

        $total_instances = isset($component['instances']) ? (int)$component['instances'] : 0;
        if ($total_instances <= 0) {
            $total_instances = 1;
        }

        $component_contexts[$component_id] = [
            'component' => $component,
            'instances' => $total_instances,
            'marks_per_instance' => isset($component['marks_per_instance']) ? (float)$component['marks_per_instance'] : 0.0
        ];
        $component_order[] = $component_id;
    }

    if (empty($component_contexts) || $students_reference === null) {
        $_SESSION['error_message'] = "Could not load the selected component details. Please try again.";
        header('Location: manage_ica_marks.php');
        exit;
    }

    if (!isset($_FILES['marks_csv']) || $_FILES['marks_csv']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error_message'] = "Please upload a CSV file containing the marks.";
        header('Location: manage_ica_marks.php');
        exit;
    }

    $tmp_path = $_FILES['marks_csv']['tmp_name'];
    if (!is_uploaded_file($tmp_path)) {
        $_SESSION['error_message'] = "Upload failed. Please try again.";
        header('Location: manage_ica_marks.php');
        exit;
    }

    $handle = fopen($tmp_path, 'r');
    if (!$handle) {
        $_SESSION['error_message'] = "Unable to read the uploaded CSV file.";
        header('Location: manage_ica_marks.php');
        exit;
    }

    $header_row = fgetcsv($handle);
    if ($header_row === false) {
        fclose($handle);
        $_SESSION['error_message'] = "The uploaded CSV file is empty.";
        header('Location: manage_ica_marks.php');
        exit;
    }

    if (!empty($header_row)) {
        $header_row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header_row[0]);
    }

    $normalize_header = static function ($value) {
        $value = preg_replace('/\s+/', ' ', trim((string)$value));
        return strtoupper($value);
    };

    $header_map = [];
    foreach ($header_row as $index => $header_value) {
        $normalized = $normalize_header($header_value);
        if ($normalized !== '') {
            $header_map[$normalized] = $index;
        }
    }

    $required_headers = ['S/N', 'ROLL NO', 'SAP ID', 'NAME OF STUDENT'];
    foreach ($required_headers as $required_header) {
        if (!array_key_exists($required_header, $header_map)) {
            fclose($handle);
            $_SESSION['error_message'] = "Uploaded CSV must contain the column '{$required_header}'.";
            header('Location: manage_ica_marks.php');
            exit;
        }
    }

    $component_column_map = [];
    foreach ($component_contexts as $component_id => $meta) {
        $component = $meta['component'];
        $component_name = $component['component_name'];
        $instances = $meta['instances'];
        $component_column_map[$component_id] = [];

        for ($instance_number = 1; $instance_number <= $instances; $instance_number++) {
            $expected_label = build_component_instance_header_label($component_name, $instance_number, $instances, $marks_per_instance);
            $header_key = $normalize_header($expected_label);
            $column_index = $header_map[$header_key] ?? null;

            if ($column_index === null && $instances === 1) {
                $fallback_key = $normalize_header($component_name);
                if (array_key_exists($fallback_key, $header_map)) {
                    $column_index = $header_map[$fallback_key];
                }
            }

            if ($column_index === null) {
                fclose($handle);
                $_SESSION['error_message'] = "Uploaded CSV must contain the column '" . $expected_label . "'.";
                header('Location: manage_ica_marks.php');
                exit;
            }

            $component_column_map[$component_id][$instance_number] = $column_index;
        }
    }

    $sap_index = $header_map['SAP ID'];

    $csv_marks = [];
    foreach ($component_contexts as $component_id => $meta) {
        $csv_marks[$component_id] = [];
        for ($instance_number = 1; $instance_number <= $meta['instances']; $instance_number++) {
            $csv_marks[$component_id][$instance_number] = [];
        }
    }

    while (($row = fgetcsv($handle)) !== false) {
        $has_value = false;
        foreach ($row as $cell) {
            if (trim((string)$cell) !== '') {
                $has_value = true;
                break;
            }
        }
        if (!$has_value) {
            continue;
        }

        $sap_value = isset($row[$sap_index]) ? trim((string)$row[$sap_index]) : '';
        if ($sap_value === '') {
            continue;
        }

        $sap_key = $normalize_sap($sap_value);
        if (!isset($expected_by_sap[$sap_key])) {
            continue;
        }

        $student = $expected_by_sap[$sap_key];
        $student_id = $student['id'];

        foreach ($component_column_map as $component_id => $instance_map) {
            foreach ($instance_map as $instance_number => $column_index) {
                $raw_mark = isset($row[$column_index]) ? trim((string)$row[$column_index]) : '';
                $csv_marks[$component_id][$instance_number][$student_id] = $raw_mark;
            }
        }
    }

    fclose($handle);

    $missing_by_component = [];
    $invalid_by_component = [];

    foreach ($component_contexts as $component_id => $meta) {
        $component = $meta['component'];
        $component_name = $component['component_name'];
        $instances = $meta['instances'];
        $max_per_instance = $meta['marks_per_instance'];

        for ($instance_number = 1; $instance_number <= $instances; $instance_number++) {
            $instance_label = build_component_instance_header_label($component_name, $instance_number, $instances, $marks_per_instance);
            $missing_students = [];
            $invalid_students = [];

            foreach ($students_reference as $student) {
                $student_id = $student['id'];
                $raw_value = $csv_marks[$component_id][$instance_number][$student_id] ?? null;
                $normalized = is_string($raw_value) ? trim($raw_value) : $raw_value;

                if ($normalized === '' || is_null($normalized)) {
                    $missing_students[] = $student['name'] . ' (' . $student['sap_id'] . ')';
                    continue;
                }

                if (is_string($normalized)) {
                    $upper = strtoupper($normalized);
                    if (in_array($upper, ['AB', 'A', 'ABSENT'], true)) {
                        continue;
                    }
                    if (!is_numeric($normalized)) {
                        $invalid_students[] = $student['name'] . ' (' . $student['sap_id'] . ')';
                        continue;
                    }
                    $numeric = (float)$normalized;
                } elseif (is_numeric($normalized)) {
                    $numeric = (float)$normalized;
                } else {
                    $invalid_students[] = $student['name'] . ' (' . $student['sap_id'] . ')';
                    continue;
                }

                if ($numeric < 0) {
                    $invalid_students[] = $student['name'] . ' (' . $student['sap_id'] . ')';
                    continue;
                }
                if ($max_per_instance > 0 && $numeric > $max_per_instance) {
                    $invalid_students[] = $student['name'] . ' (' . $student['sap_id'] . ')';
                }
            }

            if (!empty($missing_students)) {
                $missing_by_component[$instance_label] = $missing_students;
            }
            if (!empty($invalid_students)) {
                $invalid_by_component[$instance_label] = $invalid_students;
            }
        }
    }

    if (!empty($missing_by_component)) {
        $messages = [];
        foreach ($missing_by_component as $component_name => $names) {
            $messages[] = "{$component_name}: " . summarize_list($names);
        }
        $_SESSION['error_message'] = "Marks are missing for the following entries - " . implode('; ', $messages) . '.';
        header('Location: manage_ica_marks.php');
        exit;
    }

    if (!empty($invalid_by_component)) {
        $messages = [];
        foreach ($invalid_by_component as $component_name => $names) {
            $messages[] = "{$component_name}: " . summarize_list($names);
        }
        $_SESSION['error_message'] = "Invalid marks detected - " . implode('; ', $messages) . '.';
        header('Location: manage_ica_marks.php');
        exit;
    }

    $rows_attempted = 0;
    $rows_saved = 0;

    foreach ($component_order as $component_id) {
        $meta = $component_contexts[$component_id];
        $instances = $meta['instances'];

        for ($instance_number = 1; $instance_number <= $instances; $instance_number++) {
            foreach ($students_reference as $student) {
                $rows_attempted++;
                $student_id = $student['id'];
                $raw = $csv_marks[$component_id][$instance_number][$student_id] ?? null;
                $raw = is_string($raw) ? trim($raw) : $raw;
                $marks_value = null;

                if (is_string($raw)) {
                    $upper = strtoupper($raw);
                    if (!in_array($upper, ['AB', 'A', 'ABSENT'], true)) {
                        $marks_value = (float)$raw;
                    }
                } elseif (!is_null($raw)) {
                    $marks_value = (float)$raw;
                }

                if (upsert_student_mark($conn, $teacher_id, $student_id, $component_id, $instance_number, $marks_value)) {
                    $rows_saved++;
                }
            }
        }
    }

    if ($rows_saved === $rows_attempted) {
        $labels = [];
        foreach ($component_order as $component_id) {
            $meta = $component_contexts[$component_id];
            $component = $meta['component'];
            $instances = $meta['instances'];
            $labels[] = $component['component_name'] . ($instances > 1 ? ' (all instances)' : '');
        }
        $_SESSION['success_message'] = "Marks uploaded successfully for: " . implode(', ', $labels) . '.';
    } else {
        $_SESSION['error_message'] = "Some marks could not be saved. Please try again.";
    }

    header('Location: manage_ica_marks.php');
    exit;
}

if (isset($_POST['submit_marks'])) {
    $component_id = isset($_POST['component_id']) ? (int)$_POST['component_id'] : 0;
    $instance_number = isset($_POST['instance_number']) ? (int)$_POST['instance_number'] : 0;
    $marks_data = $_POST['marks'] ?? [];
    $requested_class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $requested_section_id = isset($_POST['section_id']) ? (int)$_POST['section_id'] : 0;

    $context = get_component_context($conn, $teacher_id, $component_id, $requested_class_id, $requested_section_id);
    $component = $context['component'];
    $students = $context['students'];
    $validation_error = $context['error'];

    if ($validation_error === '' && $requested_class_id <= 0) {
        $validation_error = "Please select a class before saving marks.";
    } elseif ($validation_error === '' && $component && isset($component['class_id']) && $component['class_id'] > 0 && $component['class_id'] !== $requested_class_id) {
        $validation_error = "The selected class does not match this component.";
    }

    if ($validation_error === '' && $instance_number <= 0) {
        $validation_error = "Please select the assessment instance before saving marks.";
    } elseif ($validation_error === '' && $component && $component['instances'] > 0 && $instance_number > $component['instances']) {
        $validation_error = "Invalid assessment instance selected for this component.";
    } elseif ($validation_error === '' && empty($students)) {
        $validation_error = $requested_section_id > 0
            ? "No students found for the selected class division."
            : "No students are currently assigned to this component.";
    }

    if ($validation_error === '') {
        $missing_students = [];
        $invalid_students = [];
        $max_per_instance = isset($component['marks_per_instance']) ? (float)$component['marks_per_instance'] : 0.0;

        foreach ($students as $student) {
            $student_id = $student['id'];
            $raw_value = $marks_data[$student_id][$instance_number] ?? null;
            $normalized = is_string($raw_value) ? trim($raw_value) : $raw_value;

            if ($normalized === '' || is_null($normalized)) {
                $missing_students[] = $student['name'] . ' (' . $student['sap_id'] . ')';
                continue;
            }

            if (is_string($normalized)) {
                $upper = strtoupper($normalized);
                if (in_array($upper, ['AB', 'A', 'ABSENT'], true)) {
                    continue;
                }
                if (!is_numeric($normalized)) {
                    $invalid_students[] = $student['name'] . ' (' . $student['sap_id'] . ')';
                    continue;
                }
                $numeric = (float)$normalized;
            } elseif (is_numeric($normalized)) {
                $numeric = (float)$normalized;
            } else {
                $invalid_students[] = $student['name'] . ' (' . $student['sap_id'] . ')';
                continue;
            }

            if ($numeric < 0) {
                $invalid_students[] = $student['name'] . ' (' . $student['sap_id'] . ')';
                continue;
            }
            if ($max_per_instance > 0 && $numeric > $max_per_instance) {
                $invalid_students[] = $student['name'] . ' (' . $student['sap_id'] . ')';
            }
        }

        if (!empty($missing_students)) {
            $validation_error = "Please enter marks for: " . summarize_list($missing_students) . '.';
        } elseif (!empty($invalid_students)) {
            $validation_error = "Invalid marks detected for: " . summarize_list($invalid_students) . '.';
        }
    }

    if ($validation_error !== '') {
        $_SESSION['error_message'] = $validation_error;
        header('Location: manage_ica_marks.php');
        exit;
    }

    $rows_attempted = count($students);
    $rows_saved = 0;

    foreach ($students as $student) {
        $student_id = $student['id'];
        $raw = $marks_data[$student_id][$instance_number] ?? null;
        $raw = is_string($raw) ? trim($raw) : $raw;
        $marks_value = null;

        if (is_string($raw)) {
            $upper = strtoupper($raw);
            if (!in_array($upper, ['AB', 'A', 'ABSENT'], true)) {
                $marks_value = (float)$raw;
            }
        } elseif (!is_null($raw)) {
            $marks_value = (float)$raw;
        }

        if (upsert_student_mark($conn, $teacher_id, $student_id, $component_id, $instance_number, $marks_value)) {
            $rows_saved++;
        }
    }

    if ($rows_saved === $rows_attempted) {
        $instance_label = ($component['instances'] > 1) ? $component['component_name'] . " (Instance " . $instance_number . ")" : $component['component_name'];
        $_SESSION['success_message'] = "Marks for '" . $instance_label . "' have been submitted successfully!";
    } else {
        $_SESSION['error_message'] = "Some marks could not be saved. Please try again.";
    }

    header('Location: manage_ica_marks.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage ICA Marks - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #A6192E; color: white; font-weight: bold; }
        body.dark-mode th { background-color: #cc4b4b; color: #e0e0e0; }
        .clickable-row { cursor: pointer; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 600px; border-radius: 12px; }
        body.dark-mode .modal-content { background-color: #333; color: #e0e0e0; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; }
        .modal-header h4 { margin: 0; color: #A6192E; font-size: 1.5rem; }
        body.dark-mode .modal-header h4 { color: #cc4b4b; }
        .close { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: #000; }
        .summary-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .summary-table th, .summary-table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        .summary-table th { background-color: #A6192E; color: white; }
        body.dark-mode .summary-table th { background-color: #cc4b4b; }
        .summary-table .highlight { font-weight: bold; background-color: #f0f0f0; }
        body.dark-mode .summary-table .highlight { background-color: #444; }
        .mode-toggle { display: flex; gap: 10px; margin: 20px 0; }
        .mode-toggle .mode-toggle-btn { background-color: #f7f7f7; border: 1px solid #A6192E; color: #A6192E; padding: 8px 18px; border-radius: 24px; cursor: pointer; transition: background-color 0.2s ease; }
        .mode-toggle .mode-toggle-btn.active { background-color: #A6192E; color: #fff; }
        .mode-toggle .mode-toggle-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        #csv-upload-form { border: 1px solid #ddd; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        #csv-upload-form .help-text { font-size: 0.9rem; color: #555; margin-top: 10px; }
        .component-checklist { display: flex; flex-wrap: wrap; gap: 10px; padding: 12px; border: 1px solid #ddd; border-radius: 10px; background: #fafafa; }
        .component-option { display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 8px; background: #ffffff; border: 1px solid #c8c8c8; cursor: pointer; transition: border-color 0.2s ease, background-color 0.2s ease, color 0.2s ease; }
        .component-option input { margin: 0; }
        .component-option.selected { border-color: #A6192E; color: #A6192E; background: #fff4f6; }
        .selected-components-config { margin-top: 12px; border-left: 3px solid #A6192E; padding-left: 12px; display: grid; gap: 8px; }
        .selected-component-row { display: flex; align-items: center; gap: 12px; }
        .selected-component-row .component-name { font-weight: 600; }
        .selected-component-row select { padding: 4px 8px; border-radius: 6px; }
        .instance-badge { font-size: 0.85rem; color: #555; background: #f0f0f0; padding: 4px 8px; border-radius: 6px; }
        .csv-columns { margin-top: 12px; font-size: 0.9rem; color: #333; }
        .csv-columns strong { display: block; margin-bottom: 6px; color: #A6192E; letter-spacing: 0.03em; text-transform: uppercase; font-size: 0.82rem; }
        .csv-columns .column-chip { display: inline-block; background: #f3f3f3; border: 1px solid #dadada; color: #333; border-radius: 14px; padding: 4px 12px; margin: 2px 6px 2px 0; font-size: 0.82rem; font-weight: 500; letter-spacing: 0.01em; }
        .csv-columns .column-chip.mandatory { background: #ffe4ea; border-color: #f1a9b5; color: #a6192e; }
        .csv-columns .column-chip[data-instance]::after { content: attr(data-instance); display: inline-block; margin-left: 6px; font-size: 0.7rem; color: #666; font-weight: 400; }
        .csv-columns-note { margin-top: 6px; color: #666; font-size: 0.78rem; }
        body.dark-mode .component-checklist { background: #303030; border-color: #555; }
        body.dark-mode .component-option { background: #3c3c3c; border-color: #666; color: #f0f0f0; }
        body.dark-mode .component-option.selected { background: #5a2a36; border-color: #d36; color: #ffdce5; }
        body.dark-mode .selected-components-config { border-color: #d36; }
        body.dark-mode .instance-badge { background: #555; color: #f0f0f0; }
        body.dark-mode .csv-columns { color: #f0f0f0; }
        body.dark-mode .csv-columns strong { color: #ffd5dc; }
        body.dark-mode .csv-columns .column-chip { background: #3d3d3d; border-color: #555; color: #fdfdfd; }
        body.dark-mode .csv-columns .column-chip.mandatory { background: #5a2d36; border-color: #d36; color: #ffd8e2; }
        body.dark-mode .csv-columns .column-chip[data-instance]::after { color: #bbb; }
        body.dark-mode .csv-columns-note { color: #d6d6d6; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h2>ICA Tracker</h2>
            <a href="teacher_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="update_progress.php"><i class="fas fa-chart-line"></i> <span>Update Progress</span></a>
            <a href="create_ica_components.php"><i class="fas fa-cogs"></i> <span>ICA Components</span></a>
            <a href="manage_ica_marks.php" class="active"><i class="fas fa-book"></i> <span>Manage ICA Marks</span></a>
            <a href="assignments.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a>
            <a href="view_alerts.php"><i class="fas fa-bell"></i> <span>View Alerts</span></a>
            <a href="view_reports.php"><i class="fas fa-file-alt"></i> <span>View Reports</span></a>
            <a href="timetable.php"><i class="fas fa-calendar-alt"></i> <span>Timetable</span></a>
            <a href="edit_profile.php"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>

        <div class="main-content">
            <div class="header">
                <h2>Manage ICA Marks</h2>
            </div>
            <div class="container">
                <div class="card">
                    <div class="card-header"><h5>Manage Student Marks</h5></div>
                    <div class="card-body">
                        <?php if ($error): ?> <p style="color: #A6192E; font-weight: bold;"><?php echo htmlspecialchars($error); ?></p> <?php endif; ?>
                        <?php if ($success): ?> <p style="color: #388e3c; font-weight: bold;"><?php echo htmlspecialchars($success); ?></p> <?php endif; ?>

                        <div class="form-group">
                            <label>1. Select Subject</label>
                            <select id="subject_id">
                                <option value="">-- Select a Subject --</option>
                                <?php while ($subject = mysqli_fetch_assoc($subjects_result)): ?>
                                    <?php
                                        $subjectType = isset($subject['subject_type']) ? strtolower(trim((string)$subject['subject_type'])) : 'regular';
                                        $isElectiveSubject = ($subjectType === 'elective');
                                        $label = $isElectiveSubject ? $subject['subject_name'] . ' (Elective)' : $subject['subject_name'];
                                    ?>
                                    <option value="<?php echo $subject['id']; ?>" data-subject-type="<?php echo htmlspecialchars($subjectType); ?>"><?php echo htmlspecialchars($label); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group" id="class-selector-group" style="display:none;">
                            <label>2. Select Class</label>
                            <select id="class_id_select">
                                <option value="">-- Select Subject First --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>3. Choose Components</label>
                            <div style="margin-bottom: 10px;">
                                <button type="button" id="selectAllComponentsBtn" class="btn" style="background-color: #28a745; margin-right: 8px; font-size: 0.9rem;">
                                    <i class="fas fa-check-square"></i> Select All
                                </button>
                                <button type="button" id="clearAllComponentsBtn" class="btn" style="background-color: #6c757d; font-size: 0.9rem;">
                                    <i class="fas fa-times-circle"></i> Clear All
                                </button>
                            </div>
                            <div id="component_checklist" class="component-checklist">
                                <span style="color:#666;">Select a subject and class to see available components.</span>
                            </div>
                            <small class="help-text" style="display:block; margin-top:6px; color:#555;">Tick one component for manual entry or multiple components to prepare a combined CSV upload.</small>
                        </div>

                        <div id="selected-components-config" class="selected-components-config" style="display:none;"></div>

                        <div class="form-group" id="instance-selector-container" style="display: none;">
                            <label>4. Select Assessment Number</label>
                            <select id="instance_number">
                                <option value="">-- Select an Instance --</option>
                            </select>
                        </div>

                        <div id="entry-mode-container" class="mode-toggle" style="display: none;">
                            <button type="button" id="manualEntryBtn" class="btn mode-toggle-btn active">Manual Entry</button>
                            <button type="button" id="csvUploadBtn" class="btn mode-toggle-btn">Upload CSV</button>
                        </div>

                        <form method="POST" id="csv-upload-form" enctype="multipart/form-data" style="display: none;">
                            <input type="hidden" name="selected_components_payload" id="selected_components_payload">
                            <input type="hidden" name="subject_id" id="csv_subject_id">
                            <input type="hidden" name="class_id" id="csv_class_id">
                            <input type="hidden" name="section_id" id="csv_section_id">
                            <div class="form-group">
                                <label>Upload CSV for <span id="csvComponentLabel">selected components</span></label>
                                <input type="file" name="marks_csv" id="marksCsvInput" accept=".csv,text/csv">
                            </div>
                            <p class="help-text">CSV must include the mandatory student columns (S/N, ROLL NO, SAP ID, NAME OF STUDENT) and every component column shown below. Enter marks in each instance column (for example, Quiz 2); the total column in the template is optional and ignored during upload.</p>
                            <div id="csv-required-columns" class="csv-columns"></div>
                            <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                                <button type="button" id="downloadCsvTemplateBtn" class="btn" style="background-color: #0056b3; color: #fff;">Download Template</button>
                                <button type="submit" name="upload_csv" class="btn">Upload CSV</button>
                                <button type="button" id="cancelCsvBtn" class="btn" style="background-color: #6c757d;">Cancel</button>
                            </div>
                        </form>

                        <form method="POST" id="csv-template-form" style="display: none;">
                            <input type="hidden" name="download_template" value="1">
                            <input type="hidden" name="subject_id" id="template_subject_id">
                            <input type="hidden" name="class_id" id="template_class_id">
                            <input type="hidden" name="section_id" id="template_section_id">
                            <input type="hidden" name="selected_components_payload" id="template_components_payload">
                        </form>
                        
                        <form method="POST" id="marks-form" style="display: none;">
                            <input type="hidden" name="component_id" id="form_component_id">
                            <input type="hidden" name="instance_number" id="form_instance_number">
                            <input type="hidden" name="class_id" id="form_class_id">
                            <input type="hidden" name="section_id" id="form_section_id">
                            <div id="marks-table-container"></div>
                            <div id="form-actions" style="margin-top: 20px;">
                                <button type="submit" name="submit_marks" id="save-marks-btn" class="btn">Save Marks</button>
                                <button type="button" id="edit-marks-btn" class="btn" style="display: none; background-color: #ffc107; color: black;">Edit Marks</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="studentDetailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="modalStudentName"></h4>
                <span class="close">&times;</span>
            </div>
            <div id="modalBody">
                <h5>Marks Summary</h5>
                <table class="summary-table">
                    <thead><tr><th>Instance</th><th>Marks</th></tr></thead>
                    <tbody id="modalMarksTable"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        }
        if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');

        document.addEventListener('DOMContentLoaded', function() {
            const subjectSelect = document.getElementById('subject_id');
            const componentChecklist = document.getElementById('component_checklist');
            const selectedConfigContainer = document.getElementById('selected-components-config');
            const selectedComponentsPayload = document.getElementById('selected_components_payload');
            const instanceContainer = document.getElementById('instance-selector-container');
            const instanceSelect = document.getElementById('instance_number');
            const marksForm = document.getElementById('marks-form');
            const marksTableContainer = document.getElementById('marks-table-container');
            const saveBtn = document.getElementById('save-marks-btn');
            const editBtn = document.getElementById('edit-marks-btn');
            const instanceHiddenInput = document.getElementById('form_instance_number');
            const componentHiddenInput = document.getElementById('form_component_id');
            const modal = document.getElementById('studentDetailModal');
            const closeBtn = modal ? modal.querySelector('.close') : null;
            const modalStudentName = document.getElementById('modalStudentName');
            const modalMarksTable = document.getElementById('modalMarksTable');
            const entryModeContainer = document.getElementById('entry-mode-container');
            const manualEntryBtn = document.getElementById('manualEntryBtn');
            const csvUploadBtn = document.getElementById('csvUploadBtn');
            const csvUploadForm = document.getElementById('csv-upload-form');
            const csvComponentLabel = document.getElementById('csvComponentLabel');
            const marksCsvInput = document.getElementById('marksCsvInput');
            const cancelCsvBtn = document.getElementById('cancelCsvBtn');
            const classSelectGroup = document.getElementById('class-selector-group');
            const classSelect = document.getElementById('class_id_select');
            const downloadTemplateBtn = document.getElementById('downloadCsvTemplateBtn');
            const templateForm = document.getElementById('csv-template-form');
            const csvSubjectHidden = document.getElementById('csv_subject_id');
            const csvClassHidden = document.getElementById('csv_class_id');
            const formClassHidden = document.getElementById('form_class_id');
            const csvSectionHidden = document.getElementById('csv_section_id');
            const formSectionHidden = document.getElementById('form_section_id');
            const csvColumnsContainer = document.getElementById('csv-required-columns');
            const templateSubjectHidden = document.getElementById('template_subject_id');
            const templateClassHidden = document.getElementById('template_class_id');
            const templateSectionHidden = document.getElementById('template_section_id');
            const templateComponentsHidden = document.getElementById('template_components_payload');

            const mandatoryColumnNames = ['S/N', 'ROLL NO', 'SAP ID', 'NAME OF STUDENT'];

            const formatDisplayMark = (value) => {
                if (value === null || typeof value === 'undefined') {
                    return '';
                }
                const numeric = Number(value);
                if (!Number.isFinite(numeric) || numeric <= 0) {
                    return '';
                }
                if (Math.abs(numeric - Math.round(numeric)) < 0.01) {
                    return String(Math.round(numeric));
                }
                const fixed = numeric.toFixed(2);
                return fixed.replace(/\.00$/, '').replace(/(\.\d)0$/, '$1');
            };

            const buildInstanceLabel = (componentName, instanceNumber, totalInstances) => {
                const trimmed = String(componentName || '').trim();
                if (!trimmed) {
                    return '';
                }
                if (totalInstances > 1) {
                    return `${trimmed} ${instanceNumber}`;
                }
                return trimmed;
            };

            const buildTotalLabel = (componentName, totalInstances, scaledTotal) => {
                const trimmed = String(componentName || '').trim();
                if (!trimmed) {
                    return '';
                }
                const formattedMax = formatDisplayMark(scaledTotal);
                if (formattedMax) {
                    return `${trimmed} (/ ${formattedMax})`;
                }
                if (totalInstances > 1) {
                    const suffix = Array.from({ length: totalInstances }, (_, idx) => idx + 1).join(' ');
                    return `${trimmed} (Sum of ${suffix})`;
                }
                return trimmed;
            };

            let componentsData = [];
            let studentsData = [];
            let marksData = {};
            let currentSubjectId = '';
            let currentClassId = '';
            let currentSectionId = '';
            let selectedComponentIds = new Set();
            let selectedComponentInstances = {};
            let currentManualComponentId = null;
            let currentMode = 'manual';
            let lastSelectionCount = 0;

            if (saveBtn) {
                saveBtn.disabled = true;
            }

            const ABSENT_CODES = ['AB', 'A', 'ABSENT'];

            const createClassOptionValue = (classId, sectionId) => {
                const classPart = classId ? String(classId) : '';
                const sectionPart = sectionId && Number(sectionId) > 0 ? String(sectionId) : '';
                return sectionPart ? `${classPart}|${sectionPart}` : classPart;
            };

            const parseClassOptionValue = (value) => {
                if (!value) {
                    return { classId: '', sectionId: '' };
                }
                const [classPart, sectionPart] = String(value).split('|');
                return {
                    classId: classPart || '',
                    sectionId: sectionPart || ''
                };
            };

            const normalizeMarkValue = (value) => {
                if (value === null || typeof value === 'undefined') {
                    return '';
                }
                if (typeof value === 'string') {
                    const trimmed = value.trim().toUpperCase();
                    if (trimmed === 'A' || trimmed === 'ABSENT') {
                        return 'AB';
                    }
                    return trimmed;
                }
                return value;
            };

            const isAbsentValue = (value) => {
                if (value === null || typeof value === 'undefined') {
                    return false;
                }
                const normalized = normalizeMarkValue(value);
                return typeof normalized === 'string' && ABSENT_CODES.includes(normalized);
            };

            const isValidMarkValue = (value, maxMarks) => {
                if (value === '') return true;
                if (isAbsentValue(value)) return true;
                const numeric = parseFloat(value);
                if (Number.isNaN(numeric)) return false;
                if (numeric < 0) return false;
                if (typeof maxMarks === 'number' && maxMarks >= 0 && numeric > maxMarks) return false;
                return true;
            };

            function setComponentChecklistMessage(message) {
                if (componentChecklist) {
                    componentChecklist.innerHTML = '<span style="color:#666;">' + message + '</span>';
                }
            }

            function clearManualForm() {
                if (marksForm) {
                    marksForm.style.display = 'none';
                }
                if (instanceContainer) {
                    instanceContainer.style.display = 'none';
                }
                if (instanceSelect) {
                    instanceSelect.innerHTML = '<option value="">-- Select an Instance --</option>';
                    instanceSelect.value = '';
                }
                if (marksTableContainer) {
                    marksTableContainer.innerHTML = '';
                }
                if (instanceHiddenInput) {
                    instanceHiddenInput.value = '';
                }
                if (componentHiddenInput) {
                    componentHiddenInput.value = '';
                }
                if (saveBtn) {
                    saveBtn.disabled = true;
                    saveBtn.style.display = 'inline-block';
                }
                if (templateComponentsHidden) {
                    templateComponentsHidden.value = selectedComponentsPayload.value;
                }
                if (editBtn) {
                    editBtn.style.display = 'none';
                    editBtn.disabled = false;
                }
                marksData = {};
                currentManualComponentId = null;
            }

            function resetInterface(showPlaceholder = true) {
                componentsData = [];
                studentsData = [];
                selectedComponentIds = new Set();
                selectedComponentInstances = {};
                currentManualComponentId = null;
                currentMode = 'manual';
                lastSelectionCount = 0;
                clearManualForm();
                if (selectedComponentsPayload) {
                    selectedComponentsPayload.value = '';
                }
                if (selectedConfigContainer) {
                    selectedConfigContainer.innerHTML = '';
                    selectedConfigContainer.style.display = 'none';
                }
                if (entryModeContainer) {
                    entryModeContainer.style.display = 'none';
                }
                if (csvUploadForm) {
                    csvUploadForm.style.display = 'none';
                }
                if (downloadTemplateBtn) {
                    downloadTemplateBtn.disabled = true;
                }
                if (manualEntryBtn) {
                    manualEntryBtn.disabled = true;
                    manualEntryBtn.classList.remove('active');
                }
                if (csvUploadBtn) {
                    csvUploadBtn.disabled = true;
                    csvUploadBtn.classList.remove('active');
                }
                if (marksCsvInput) {
                    marksCsvInput.required = false;
                    marksCsvInput.value = '';
                }
                if (showPlaceholder) {
                    setComponentChecklistMessage('Select a subject and class to see available components.');
                } else if (componentChecklist) {
                    componentChecklist.innerHTML = '';
                }
                if (csvComponentLabel) {
                    csvComponentLabel.textContent = 'selected components';
                }
                if (templateComponentsHidden) {
                    templateComponentsHidden.value = '';
                }
            }

            function updateCsvPayload() {
                if (!selectedComponentsPayload) {
                    return;
                }
                const payload = [];
                const summaryLabels = [];
                const columnChips = [];

                selectedComponentIds.forEach(componentKey => {
                    const component = componentsData.find(c => String(c.id) === componentKey);
                    if (!component) {
                        return;
                    }
                    payload.push({ component_id: parseInt(component.id, 10) });

                    const instances = component.instances && component.instances > 0 ? component.instances : 1;
                    const totalLabel = buildTotalLabel(component.component_name, instances, component.scaled_total_marks);
                    summaryLabels.push(totalLabel || component.component_name);

                    for (let instanceNumber = 1; instanceNumber <= instances; instanceNumber++) {
                        const instanceLabel = buildInstanceLabel(component.component_name, instanceNumber, instances);
                        if (instanceLabel) {
                            columnChips.push(`<span class="column-chip" data-instance="Instance ${instanceNumber}">${instanceLabel}</span>`);
                        }
                    }

                    if (instances > 1) {
                        if (totalLabel) {
                            columnChips.push(`<span class="column-chip">${totalLabel}</span>`);
                        }
                    } else if (instances === 1) {
                        const singleInstanceLabel = buildInstanceLabel(component.component_name, 1, instances);
                        if (totalLabel && totalLabel !== singleInstanceLabel) {
                            columnChips.push(`<span class="column-chip">${totalLabel}</span>`);
                        }
                    }

                    if (!selectedComponentInstances[componentKey]) {
                        selectedComponentInstances[componentKey] = 1;
                    }
                });

                selectedComponentsPayload.value = payload.length ? JSON.stringify(payload) : '';
                if (templateComponentsHidden) {
                    templateComponentsHidden.value = selectedComponentsPayload.value;
                }
                if (csvComponentLabel) {
                    csvComponentLabel.textContent = summaryLabels.length ? summaryLabels.join(', ') : 'selected components';
                }
                if (csvColumnsContainer) {
                    let html = '<strong>Required CSV Columns</strong>';
                    const mandatoryChips = mandatoryColumnNames.map(name => `<span class="column-chip mandatory">${name}</span>`).join('');
                    html += mandatoryChips;
                    if (columnChips.length) {
                        html += columnChips.join('');
                    }
                    const noteText = columnChips.length
                        ? 'Enter marks in each instance column shown above (for example, Quiz 2). The total column provided in the template is optional and ignored during upload.'
                        : 'Tick components to add their mark columns. Enter marks in each instance column; the total column is optional and ignored during upload.';
                    html += `<div class="csv-columns-note">${noteText}</div>`;
                    csvColumnsContainer.innerHTML = html;
                }
            }

            function renderSelectedComponentsConfig() {
                if (!selectedConfigContainer) {
                    return;
                }
                if (!selectedComponentIds.size) {
                    selectedConfigContainer.innerHTML = '';
                    selectedConfigContainer.style.display = 'none';
                    return;
                }
                selectedConfigContainer.innerHTML = '';
                selectedConfigContainer.style.display = 'grid';
                selectedComponentIds.forEach(componentKey => {
                    const component = componentsData.find(c => String(c.id) === componentKey);
                    if (!component) {
                        return;
                    }
                    const row = document.createElement('div');
                    row.className = 'selected-component-row';

                    const nameSpan = document.createElement('span');
                    nameSpan.className = 'component-name';
                    const totalLabel = buildTotalLabel(component.component_name, component.instances || 1, component.scaled_total_marks);
                    nameSpan.textContent = totalLabel || component.component_name;
                    row.appendChild(nameSpan);

                    const badge = document.createElement('span');
                    badge.className = 'instance-badge';
                    const instanceCount = component.instances && component.instances > 1 ? component.instances : 1;
                    badge.textContent = instanceCount > 1 ? `${instanceCount} instances` : 'Single instance';
                    row.appendChild(badge);

                    if (!selectedComponentInstances[componentKey]) {
                        selectedComponentInstances[componentKey] = 1;
                    }

                    selectedConfigContainer.appendChild(row);
                });
            }

            function renderComponentChecklist() {
                if (!componentChecklist) {
                    return;
                }
                if (!componentsData.length) {
                    setComponentChecklistMessage('No components defined for this selection.');
                    return;
                }
                componentChecklist.innerHTML = '';
                componentsData.forEach(component => {
                    const componentKey = String(component.id);
                    const label = document.createElement('label');
                    label.className = 'component-option';
                    if (selectedComponentIds.has(componentKey)) {
                        label.classList.add('selected');
                    }

                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.value = component.id;
                    checkbox.checked = selectedComponentIds.has(componentKey);
                    checkbox.addEventListener('change', () => {
                        if (checkbox.checked) {
                            selectedComponentIds.add(componentKey);
                            if (!selectedComponentInstances[componentKey]) {
                                selectedComponentInstances[componentKey] = 1;
                            }
                            label.classList.add('selected');
                        } else {
                            selectedComponentIds.delete(componentKey);
                            delete selectedComponentInstances[componentKey];
                            label.classList.remove('selected');
                        }
                        renderSelectedComponentsConfig();
                        updateCsvPayload();
                        updateEntryModes();
                        if (currentMode === 'manual' && selectedComponentIds.size === 1) {
                            const onlyComponent = [...selectedComponentIds][0];
                            if (onlyComponent) {
                                prepareManualEntry(onlyComponent);
                            }
                        } else if (!selectedComponentIds.size) {
                            clearManualForm();
                        }
                    });

                    const nameSpan = document.createElement('span');
                    nameSpan.textContent = component.component_name;
                    label.appendChild(checkbox);
                    label.appendChild(nameSpan);

                    if (component.instances > 1) {
                        const badge = document.createElement('span');
                        badge.className = 'instance-badge';
                        badge.textContent = `${component.instances} instances`;
                        label.appendChild(badge);
                    }

                    componentChecklist.appendChild(label);
                });
            }

            function showManualEntry() {
                if (csvUploadForm) {
                    csvUploadForm.style.display = 'none';
                    if (marksCsvInput) {
                        marksCsvInput.required = false;
                        marksCsvInput.value = '';
                    }
                }
                if (manualEntryBtn) {
                    manualEntryBtn.classList.add('active');
                }
                if (csvUploadBtn) {
                    csvUploadBtn.classList.remove('active');
                }
                if (marksForm && currentManualComponentId) {
                    marksForm.style.display = 'block';
                }
            }

            function showCsvUpload() {
                if (marksForm) {
                    marksForm.style.display = 'none';
                }
                if (csvUploadForm) {
                    csvUploadForm.style.display = 'block';
                }
                if (marksCsvInput) {
                    marksCsvInput.required = true;
                    marksCsvInput.value = '';
                }
                if (manualEntryBtn) {
                    manualEntryBtn.classList.remove('active');
                }
                if (csvUploadBtn) {
                    csvUploadBtn.classList.add('active');
                }
                clearManualForm();
            }

            function updateEntryModes() {
                const selectionCount = selectedComponentIds.size;
                const hasStudents = Array.isArray(studentsData) && studentsData.length > 0;

                if (!selectionCount) {
                    if (entryModeContainer) entryModeContainer.style.display = 'none';
                    if (csvUploadForm) csvUploadForm.style.display = 'none';
                    if (manualEntryBtn) {
                        manualEntryBtn.disabled = true;
                        manualEntryBtn.classList.remove('active');
                    }
                    if (csvUploadBtn) {
                        csvUploadBtn.disabled = true;
                        csvUploadBtn.classList.remove('active');
                    }
                    clearManualForm();
                    lastSelectionCount = 0;
                    return;
                }

                if (entryModeContainer) {
                    entryModeContainer.style.display = 'flex';
                }

                if (manualEntryBtn) {
                    manualEntryBtn.disabled = selectionCount !== 1 || !hasStudents;
                }
                if (csvUploadBtn) {
                    csvUploadBtn.disabled = !hasStudents;
                }

                if (selectionCount === 1 && lastSelectionCount !== 1 && manualEntryBtn && !manualEntryBtn.disabled) {
                    currentMode = 'manual';
                }

                if (selectionCount > 1 && currentMode === 'manual') {
                    currentMode = 'csv';
                }

                if (currentMode === 'manual' && manualEntryBtn && manualEntryBtn.disabled) {
                    currentMode = 'csv';
                }

                if (currentMode === 'manual') {
                    showManualEntry();
                } else {
                    showCsvUpload();
                }

                lastSelectionCount = selectionCount;
            }

            function prepareManualEntry(componentKey) {
                const component = componentsData.find(c => String(c.id) === componentKey);
                if (!component || !Array.isArray(studentsData) || !studentsData.length) {
                    clearManualForm();
                    return;
                }

                currentManualComponentId = component.id;
                if (componentHiddenInput) {
                    componentHiddenInput.value = component.id;
                }
                if (formClassHidden) {
                    formClassHidden.value = currentClassId || '';
                }
                if (csvClassHidden) {
                    csvClassHidden.value = currentClassId || '';
                }
                if (formSectionHidden) {
                    formSectionHidden.value = currentSectionId || '';
                }
                if (csvSectionHidden) {
                    csvSectionHidden.value = currentSectionId || '';
                }

                if (marksForm) {
                    marksForm.style.display = 'block';
                }
                if (instanceContainer) {
                    instanceContainer.style.display = 'none';
                }
                if (marksTableContainer) {
                    marksTableContainer.innerHTML = '<p>Loading marks...</p>';
                }

                fetch(`get_student_marks.php?component_id=${component.id}`)
                    .then(res => res.json())
                    .then(marks => {
                        marksData = {};
                        Object.entries(marks || {}).forEach(([studentId, entries]) => {
                            marksData[studentId] = {};
                            Object.entries(entries || {}).forEach(([instance, markValue]) => {
                                const cleaned = isAbsentValue(markValue) ? 'AB' : markValue;
                                marksData[studentId][parseInt(instance, 10)] = cleaned;
                            });
                        });

                        const defaultInstance = parseInt(selectedComponentInstances[String(component.id)] || 1, 10) || 1;
                        if (component.instances <= 1) {
                            if (instanceHiddenInput) {
                                instanceHiddenInput.value = '1';
                            }
                            generateMarksTable(studentsData, component, marksData, 1);
                        } else {
                            if (instanceSelect) {
                                instanceSelect.innerHTML = '<option value="">-- Select Assessment Number --</option>';
                                for (let i = 1; i <= component.instances; i++) {
                                    const option = document.createElement('option');
                                    option.value = String(i);
                                    option.textContent = `${component.component_name} ${i}`;
                                    instanceSelect.appendChild(option);
                                }
                                instanceSelect.value = defaultInstance ? String(defaultInstance) : '';
                            }
                            if (instanceContainer) {
                                instanceContainer.style.display = 'block';
                            }
                            if (defaultInstance) {
                                instanceSelect.dispatchEvent(new Event('change'));
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error loading marks:', error);
                        marksData = {};
                        const defaultInstance = parseInt(selectedComponentInstances[String(component.id)] || 1, 10) || 1;
                        if (component.instances <= 1) {
                            if (instanceHiddenInput) {
                                instanceHiddenInput.value = '1';
                            }
                            generateMarksTable(studentsData, component, marksData, 1);
                        } else {
                            if (instanceSelect) {
                                instanceSelect.innerHTML = '<option value="">-- Select Assessment Number --</option>';
                                for (let i = 1; i <= component.instances; i++) {
                                    const option = document.createElement('option');
                                    option.value = String(i);
                                    option.textContent = `${component.component_name} ${i}`;
                                    instanceSelect.appendChild(option);
                                }
                                instanceSelect.value = defaultInstance ? String(defaultInstance) : '';
                            }
                            if (instanceContainer) {
                                instanceContainer.style.display = 'block';
                            }
                        }
                    });
            }

            function populateClassOptions(classes) {
                if (!classSelect) {
                    return;
                }
                const hasClasses = Array.isArray(classes) && classes.length > 0;
                if (!hasClasses) {
                    classSelect.innerHTML = '<option value="">-- No Classes Found --</option>';
                    if (classSelectGroup) classSelectGroup.style.display = 'none';
                    currentClassId = '';
                    currentSectionId = '';
                    if (formClassHidden) formClassHidden.value = '';
                    if (csvClassHidden) csvClassHidden.value = '';
                    if (formSectionHidden) formSectionHidden.value = '';
                    if (csvSectionHidden) csvSectionHidden.value = '';
                    resetInterface();
                    return;
                }

                classSelect.innerHTML = '<option value="">-- Select a Class --</option>';
                classes.forEach(cls => {
                    if (!cls || typeof cls !== 'object') {
                        return;
                    }
                    const option = document.createElement('option');
                    const optionClassId = typeof cls.id !== 'undefined' && cls.id !== null ? String(cls.id) : '';
                    const optionSectionId = typeof cls.section_id !== 'undefined' && cls.section_id !== null ? String(cls.section_id) : '';
                    option.value = createClassOptionValue(optionClassId, optionSectionId);
                    option.dataset.classId = optionClassId;
                    option.dataset.sectionId = optionSectionId;
                    option.textContent = cls.class_name;
                    classSelect.appendChild(option);
                });
                if (classSelectGroup) {
                    classSelectGroup.style.display = '';
                }
            }

            function loadComponentsForClass(subjectId, classId, sectionId) {
                resetInterface(false);
                if (!subjectId) {
                    setComponentChecklistMessage('Select a subject to see available components.');
                    return;
                }
                if (!classId) {
                    setComponentChecklistMessage('Select a class to see available components.');
                    return;
                }

                if (componentChecklist) {
                    setComponentChecklistMessage('Loading components...');
                }

                const studentParams = new URLSearchParams({ subject_id: subjectId });
                if (classId) {
                    studentParams.append('class_id', classId);
                }
                if (sectionId) {
                    studentParams.append('section_id', sectionId);
                }

                Promise.all([
                    fetch(`get_ica_components.php?subject_id=${encodeURIComponent(subjectId)}&class_id=${encodeURIComponent(classId)}`).then(res => res.json()),
                    fetch(`get_students_for_subject.php?${studentParams.toString()}`).then(res => res.json())
                ]).then(([components, students]) => {
                    componentsData = Array.isArray(components) ? components : [];
                    studentsData = Array.isArray(students) ? students : [];
                    if (downloadTemplateBtn) {
                        downloadTemplateBtn.disabled = componentsData.length === 0 || studentsData.length === 0;
                    }
                    if (!studentsData.length) {
                        console.warn('No students found for selected class.');
                    }
                    selectedComponentIds = new Set();
                    selectedComponentInstances = {};
                    componentsData.forEach(component => {
                        if (!component || typeof component.id === 'undefined') {
                            return;
                        }
                        const key = String(component.id);
                        selectedComponentIds.add(key);
                        selectedComponentInstances[key] = 1;
                    });
                    renderComponentChecklist();
                    renderSelectedComponentsConfig();
                    updateCsvPayload();
                    updateEntryModes();
                }).catch(error => {
                    console.error('Error fetching data for class:', error);
                    resetInterface();
                    setComponentChecklistMessage('Failed to load components. Please try again.');
                    if (downloadTemplateBtn) {
                        downloadTemplateBtn.disabled = true;
                    }
                });
            }

            function loadClassesForSubject(subjectId) {
                currentClassId = '';
                currentSectionId = '';
                if (formClassHidden) formClassHidden.value = '';
                if (csvClassHidden) csvClassHidden.value = '';
                if (templateClassHidden) templateClassHidden.value = '';
                if (formSectionHidden) formSectionHidden.value = '';
                if (csvSectionHidden) csvSectionHidden.value = '';
                if (templateSectionHidden) templateSectionHidden.value = '';
                resetInterface();
                if (!subjectId) {
                    if (classSelect) {
                        classSelect.innerHTML = '<option value="">-- Select Subject First --</option>';
                    }
                    return;
                }

                if (classSelect) {
                    classSelect.innerHTML = '<option value="">Loading...</option>';
                    if (classSelectGroup) {
                        classSelectGroup.style.display = '';
                    }
                }

                fetch(`get_classes_for_subject.php?subject_id=${encodeURIComponent(subjectId)}&separate_divisions=1`)
                    .then(res => res.json())
                    .then(data => {
                        const classList = Array.isArray(data) ? data : [];
                        populateClassOptions(classList);
                        if (classList.length === 1 && classSelect) {
                            const firstEntry = classList[0] || {};
                            const autoValue = createClassOptionValue(firstEntry.id, firstEntry.section_id);
                            classSelect.value = autoValue;
                            const selection = parseClassOptionValue(autoValue);
                            currentClassId = selection.classId;
                            currentSectionId = selection.sectionId;
                            if (formClassHidden) formClassHidden.value = currentClassId;
                            if (csvClassHidden) csvClassHidden.value = currentClassId;
                            if (templateClassHidden) templateClassHidden.value = currentClassId;
                            if (formSectionHidden) formSectionHidden.value = currentSectionId;
                            if (csvSectionHidden) csvSectionHidden.value = currentSectionId;
                            if (templateSectionHidden) templateSectionHidden.value = currentSectionId;
                            loadComponentsForClass(subjectId, currentClassId, currentSectionId);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading classes for subject:', error);
                        populateClassOptions([]);
                    });
            }

            if (subjectSelect) {
                subjectSelect.addEventListener('change', function() {
                    currentSubjectId = this.value;
                    if (csvSubjectHidden) {
                        csvSubjectHidden.value = currentSubjectId || '';
                    }
                    if (templateSubjectHidden) {
                        templateSubjectHidden.value = currentSubjectId || '';
                    }
                    loadClassesForSubject(currentSubjectId);
                });
            }

            if (classSelect) {
                classSelect.addEventListener('change', function() {
                    const selection = parseClassOptionValue(this.value);
                    currentClassId = selection.classId;
                    currentSectionId = selection.sectionId;
                    if (formClassHidden) formClassHidden.value = currentClassId;
                    if (csvClassHidden) csvClassHidden.value = currentClassId;
                    if (templateClassHidden) templateClassHidden.value = currentClassId;
                    if (formSectionHidden) formSectionHidden.value = currentSectionId;
                    if (csvSectionHidden) csvSectionHidden.value = currentSectionId;
                    if (templateSectionHidden) templateSectionHidden.value = currentSectionId;
                    loadComponentsForClass(currentSubjectId, currentClassId, currentSectionId);
                });
            }

            if (manualEntryBtn) {
                manualEntryBtn.addEventListener('click', () => {
                    if (manualEntryBtn.disabled) {
                        return;
                    }
                    currentMode = 'manual';
                    updateEntryModes();
                    const componentKey = [...selectedComponentIds][0];
                    if (componentKey) {
                        prepareManualEntry(componentKey);
                    }
                });
            }

            if (csvUploadBtn) {
                csvUploadBtn.addEventListener('click', () => {
                    if (csvUploadBtn.disabled) {
                        return;
                    }
                    currentMode = 'csv';
                    updateEntryModes();
                });
            }

            if (cancelCsvBtn) {
                cancelCsvBtn.addEventListener('click', () => {
                    if (manualEntryBtn && !manualEntryBtn.disabled) {
                        currentMode = 'manual';
                        updateEntryModes();
                        const componentKey = [...selectedComponentIds][0];
                        if (componentKey) {
                            prepareManualEntry(componentKey);
                        }
                    } else {
                        showCsvUpload();
                    }
                });
            }

            if (downloadTemplateBtn && templateForm) {
                downloadTemplateBtn.addEventListener('click', () => {
                    if (downloadTemplateBtn.disabled) {
                        return;
                    }
                    if (!currentSubjectId) {
                        alert('Please select a subject before downloading the template.');
                        return;
                    }
                    if (!currentClassId) {
                        alert('Please select a class before downloading the template.');
                        return;
                    }
                    if (csvSubjectHidden) {
                        csvSubjectHidden.value = currentSubjectId || '';
                    }
                    if (templateSubjectHidden) {
                        templateSubjectHidden.value = currentSubjectId || '';
                    }
                    if (csvClassHidden) {
                        csvClassHidden.value = currentClassId || '';
                    }
                    if (templateClassHidden) {
                        templateClassHidden.value = currentClassId || '';
                    }
                    if (csvSectionHidden) {
                        csvSectionHidden.value = currentSectionId || '';
                    }
                    if (templateSectionHidden) {
                        templateSectionHidden.value = currentSectionId || '';
                    }
                    if (templateComponentsHidden) {
                        templateComponentsHidden.value = selectedComponentsPayload ? selectedComponentsPayload.value : '';
                    }
                    templateForm.submit();
                });
            }

            if (csvUploadBtn) {
                csvUploadBtn.disabled = true;
            }
            if (manualEntryBtn) {
                manualEntryBtn.disabled = true;
            }
            if (downloadTemplateBtn) {
                downloadTemplateBtn.disabled = true;
            }

            const selectAllBtn = document.getElementById('selectAllComponentsBtn');
            const clearAllBtn = document.getElementById('clearAllComponentsBtn');

            if (selectAllBtn) {
                selectAllBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const checkboxes = document.querySelectorAll('#component_checklist input[type="checkbox"]');
                    checkboxes.forEach(cb => {
                        cb.checked = true;
                        cb.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                });
            }

            if (clearAllBtn) {
                clearAllBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const checkboxes = document.querySelectorAll('#component_checklist input[type="checkbox"]');
                    checkboxes.forEach(cb => {
                        cb.checked = false;
                        cb.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                });
            }

            if (instanceSelect) {
                instanceSelect.addEventListener('change', function() {
                    const selectedInstance = parseInt(this.value, 10);
                    const componentId = currentManualComponentId;

                    if (!componentId || !selectedInstance) {
                        if (marksForm) {
                            marksForm.style.display = 'none';
                        }
                        if (instanceHiddenInput) {
                            instanceHiddenInput.value = '';
                        }
                        if (saveBtn) {
                            saveBtn.disabled = true;
                        }
                        return;
                    }

                    const component = componentsData.find(c => c.id == componentId);
                    if (!component) {
                        return;
                    }

                    if (selectedInstance > 1) {
                        const previousInstance = selectedInstance - 1;
                        let previousInstanceHasMarks = false;
                        for (const studentId in marksData) {
                            if (Object.prototype.hasOwnProperty.call(marksData, studentId)) {
                                const entry = marksData[studentId] && marksData[studentId][previousInstance];
                                if (entry != null && entry !== '') {
                                    previousInstanceHasMarks = true;
                                    break;
                                }
                            }
                        }
                        if (!previousInstanceHasMarks) {
                            alert(`Error: You must enter and save marks for '${component.component_name} ${previousInstance}' before proceeding to instance ${selectedInstance}.`);
                            this.value = '';
                            if (marksForm) {
                                marksForm.style.display = 'none';
                            }
                            if (instanceHiddenInput) {
                                instanceHiddenInput.value = '';
                            }
                            if (saveBtn) {
                                saveBtn.disabled = true;
                            }
                            return;
                        }
                    }

                    selectedComponentInstances[String(componentId)] = selectedInstance;
                    const configSelect = selectedConfigContainer ? selectedConfigContainer.querySelector(`select[data-component-id="${componentId}"]`) : null;
                    if (configSelect) {
                        configSelect.value = String(selectedInstance);
                    }
                    updateCsvPayload();

                    if (instanceHiddenInput) {
                        instanceHiddenInput.value = String(selectedInstance);
                    }
                    generateMarksTable(studentsData, component, marksData, selectedInstance);
                });
            }

            function generateMarksTable(students, component, marks, instanceNumber) {
                if (!marksTableContainer || !component) {
                    return;
                }
                const maxMarks = parseFloat(component.marks_per_instance) || 0;
                let hasAnySavedMark = false;
                const datalistId = 'markChoices';
                let tableHTML = `<datalist id="${datalistId}">
                                    <option value="AB"></option>
                                    <option value="A"></option>
                                 </datalist>
                                 <h4>Entering Marks for: ${component.component_name}${component.instances > 1 ? ' ' + instanceNumber : ''}</h4>
                                 <p style="font-weight: bold; color: #A6192E;">Max Marks: ${maxMarks.toFixed(2)} &nbsp;|&nbsp; Enter numeric marks or type 'AB' to mark a student absent.</p>
                                 <table><thead><tr><th>Roll No</th><th>Student Name</th><th>Marks (Out of ${maxMarks.toFixed(2)})</th></tr></thead><tbody>`;
                if (!students.length) {
                    tableHTML += '<tr><td colspan="3" style="text-align: center;">No students assigned.</td></tr>';
                } else {
                    students.forEach(student => {
                        let savedMark = '';
                        if (marks[student.id] && typeof marks[student.id][instanceNumber] !== 'undefined') {
                            const markEntry = marks[student.id][instanceNumber];
                            if (isAbsentValue(markEntry)) {
                                savedMark = 'AB';
                            } else if (markEntry !== null && markEntry !== '') {
                                const parsed = parseFloat(markEntry);
                                if (!Number.isNaN(parsed)) {
                                    savedMark = parsed.toString();
                                }
                            }
                        }
                        if (savedMark !== '') {
                            hasAnySavedMark = true;
                        }
                        tableHTML += `<tr class="clickable-row" data-student-id="${student.id}" data-student-name="${student.name}">
                                        <td>${student.roll_number}</td>
                                        <td>${student.name}</td>
                                        <td><input type="text" name="marks[${student.id}][${instanceNumber}]" value="${savedMark}" list="${datalistId}" data-max="${maxMarks}" class="mark-input" placeholder="0 - ${maxMarks.toFixed(2)} or AB" style="width: 90px;" required data-student-name="${student.name}"></td>
                                      </tr>`;
                    });
                }
                tableHTML += '</tbody></table>';
                marksTableContainer.innerHTML = tableHTML;

                if (marksForm) {
                    marksForm.style.display = students.length ? 'block' : 'none';
                }
                if (instanceHiddenInput) {
                    instanceHiddenInput.value = students.length ? instanceNumber : '';
                }
                if (componentHiddenInput) {
                    componentHiddenInput.value = component.id;
                }

                toggleEditState(hasAnySavedMark);
                updateSaveButtonState();
                updateCsvPayload();
            }

            function toggleEditState(isSaved) {
                if (!marksTableContainer) {
                    return;
                }
                const inputs = marksTableContainer.querySelectorAll('.mark-input');
                if (!inputs.length) {
                    if (saveBtn) {
                        saveBtn.disabled = true;
                        saveBtn.style.display = 'inline-block';
                    }
                    if (editBtn) {
                        editBtn.style.display = 'none';
                    }
                    return;
                }
                if (isSaved) {
                    inputs.forEach(input => { input.disabled = true; });
                    if (saveBtn) {
                        saveBtn.style.display = 'none';
                        saveBtn.disabled = true;
                    }
                    if (editBtn) {
                        editBtn.style.display = 'inline-block';
                    }
                } else {
                    inputs.forEach(input => { input.disabled = false; });
                    if (saveBtn) {
                        saveBtn.style.display = 'inline-block';
                    }
                    if (editBtn) {
                        editBtn.style.display = 'none';
                    }
                    updateSaveButtonState();
                }
            }

            function updateSaveButtonState() {
                if (!marksTableContainer || !saveBtn) {
                    return;
                }
                const inputs = Array.from(marksTableContainer.querySelectorAll('.mark-input'));
                if (!inputs.length) {
                    saveBtn.disabled = true;
                    return;
                }

                const activeInputs = inputs.filter(input => !input.disabled);
                if (!activeInputs.length) {
                    saveBtn.disabled = true;
                    return;
                }

                let allFilled = true;
                let allValid = true;

                activeInputs.forEach(input => {
                    const maxAttr = parseFloat(input.dataset.max);
                    const value = input.value;
                    const normalized = normalizeMarkValue(value);

                    if (normalized === '') {
                        allFilled = false;
                    }

                    if (!isValidMarkValue(value, maxAttr)) {
                        allValid = false;
                    }
                });

                saveBtn.disabled = !(allFilled && allValid);
            }

            if (editBtn) {
                editBtn.addEventListener('click', function() {
                    toggleEditState(false);
                });
            }

            if (marksTableContainer) {
                marksTableContainer.addEventListener('input', function(e) {
                    if (e.target.classList.contains('mark-input')) {
                        const inputEl = e.target;
                        const maxAttr = parseFloat(inputEl.dataset.max);
                        if (typeof inputEl.value === 'string') {
                            inputEl.value = inputEl.value.replace(/\s+/g, '').toUpperCase();
                            if (inputEl.value === 'A' || inputEl.value === 'ABSENT') {
                                inputEl.value = 'AB';
                            }
                        }
                        if (isValidMarkValue(inputEl.value, maxAttr)) {
                            inputEl.setCustomValidity('');
                        } else {
                            const studentName = inputEl.dataset.studentName || 'this student';
                            inputEl.setCustomValidity(`Enter a number between 0 and ${maxAttr} or AB to mark ${studentName} absent.`);
                        }
                        updateSaveButtonState();
                    }
                });

                marksTableContainer.addEventListener('blur', function(e) {
                    if (!e.target.classList.contains('mark-input')) {
                        return;
                    }

                    const inputEl = e.target;
                    const maxAttr = parseFloat(inputEl.dataset.max);
                    const studentName = inputEl.dataset.studentName || 'this student';
                    const normalized = normalizeMarkValue(inputEl.value);

                    if (normalized === '') {
                        const message = `Marks are required for ${studentName}. Enter a value or use AB for absent.`;
                        inputEl.setCustomValidity(message);
                        alert(message);
                        inputEl.reportValidity();
                        return;
                    }

                    if (!isValidMarkValue(inputEl.value, maxAttr)) {
                        const message = `Enter a number between 0 and ${maxAttr} or AB to mark ${studentName} absent.`;
                        inputEl.setCustomValidity(message);
                        alert(message);
                        inputEl.reportValidity();
                    } else {
                        inputEl.setCustomValidity('');
                    }
                }, true);

                marksTableContainer.addEventListener('click', function(e) {
                    if (e.target.tagName.toLowerCase() === 'input') {
                        return;
                    }
                    const row = e.target.closest('.clickable-row');
                    if (!row) {
                        return;
                    }

                    const studentId = row.dataset.studentId;
                    const studentName = row.dataset.studentName;
                    const componentId = currentManualComponentId;
                    if (!studentId || !componentId) {
                        return;
                    }

                    const selectedComponent = componentsData.find(c => c.id == componentId);
                    if (selectedComponent && modal) {
                        modalStudentName.innerText = `${studentName} - ${selectedComponent.component_name}`;
                        generateStudentSummary(studentId, selectedComponent, marksData);
                        modal.style.display = 'block';
                    }
                });
            }

            function generateStudentSummary(studentId, component, marks) {
                if (!modalMarksTable || !component) {
                    return;
                }
                const totalPossibleRawMarks = parseFloat(component.total_marks) || 0;
                const totalPossibleScaledMarks = parseFloat(component.scaled_total_marks) || 0;
                let totalRawMarksObtained = 0;
                let allInstancesEntered = true;

                let tableHTML = '';
                for (let i = 1; i <= component.instances; i++) {
                    const markEntry = marks[studentId] && typeof marks[studentId][i] !== 'undefined' ? marks[studentId][i] : null;
                    let displayMark = '-';
                    if (isAbsentValue(markEntry)) {
                        displayMark = 'AB';
                    } else if (markEntry === null || markEntry === '') {
                        allInstancesEntered = false;
                    } else {
                        const numeric = parseFloat(markEntry);
                        if (Number.isNaN(numeric)) {
                            allInstancesEntered = false;
                        } else {
                            displayMark = numeric.toFixed(2);
                            totalRawMarksObtained += numeric;
                        }
                    }
                    if (displayMark === '-') {
                        allInstancesEntered = false;
                    }
                    const instanceName = component.instances > 1 ? `${component.component_name} ${i}` : component.component_name;
                    tableHTML += `<tr><td>${instanceName}</td><td>${displayMark}</td></tr>`;
                }

                let finalScaledMark = '-';
                if (allInstancesEntered && totalPossibleRawMarks > 0) {
                    finalScaledMark = ((totalRawMarksObtained / totalPossibleRawMarks) * totalPossibleScaledMarks).toFixed(2);
                }

                tableHTML += `
                    <tr class="highlight">
                        <td><strong>Total Raw Marks</strong></td>
                        <td><strong>${totalRawMarksObtained.toFixed(2)} / ${totalPossibleRawMarks.toFixed(2)}</strong></td>
                    </tr>
                    <tr class="highlight">
                        <td><strong>Final Scaled Marks</strong></td>
                        <td><strong>${finalScaledMark} / ${totalPossibleScaledMarks.toFixed(2)}</strong></td>
                    </tr>`;

                modalMarksTable.innerHTML = tableHTML;
            }

            if (closeBtn && modal) {
                closeBtn.onclick = function() {
                    modal.style.display = 'none';
                };
                window.onclick = function(event) {
                    if (event.target === modal) {
                        modal.style.display = 'none';
                    }
                };
            }
        });
    </script>
</body>
</html>
