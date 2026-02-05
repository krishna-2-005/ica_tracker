<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['subject_id'])) {
    echo json_encode([]);
    exit;
}

$allowed_roles = ['admin', 'program_chair', 'teacher'];
if (!in_array($_SESSION['role'], $allowed_roles, true)) {
    echo json_encode([]);
    exit;
}

$subject_id = (int)$_GET['subject_id'];
if ($subject_id <= 0) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT ic.id,
               ic.component_name,
               ic.instances,
               ic.marks_per_instance,
               ic.total_marks,
               ic.scaled_total_marks,
               ic.course_type,
               u.name AS teacher_name
        FROM ica_components ic
        LEFT JOIN users u ON u.id = ic.teacher_id
        WHERE ic.subject_id = ?
        ORDER BY COALESCE(u.name, ''), ic.component_name";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode([]);
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $subject_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$components = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $teacherNameRaw = isset($row['teacher_name']) ? trim((string)$row['teacher_name']) : '';
        $components[] = [
            'id' => (int)$row['id'],
            'component_name' => $row['component_name'],
            'instances' => isset($row['instances']) ? (int)$row['instances'] : null,
            'marks_per_instance' => isset($row['marks_per_instance']) ? (float)$row['marks_per_instance'] : null,
            'total_marks' => isset($row['total_marks']) ? (float)$row['total_marks'] : null,
            'scaled_total_marks' => isset($row['scaled_total_marks']) ? (float)$row['scaled_total_marks'] : null,
            'course_type' => $row['course_type'],
            'teacher_name' => $teacherNameRaw,
            'teacher_name_display' => $teacherNameRaw !== '' ? format_person_display($teacherNameRaw) : ''
        ];
    }
    mysqli_free_result($result);
}

mysqli_stmt_close($stmt);

echo json_encode($components);
