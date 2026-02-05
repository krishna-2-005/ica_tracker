<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
$subject_id = (int)$_GET['subject_id'];
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$teacher_id = (int)$_SESSION['user_id'];

$query = "SELECT COUNT(*) as count FROM ica_components WHERE subject_id = ? AND teacher_id = ?";
$types = 'ii';
$params = [$subject_id, $teacher_id];
if ($class_id > 0) {
    $query .= " AND (class_id = ? OR class_id IS NULL)";
    $types .= 'i';
    $params[] = $class_id;
}

$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    error_log("Prepare failed for check_components: " . mysqli_error($conn), 3, 'C:\xampp\php\logs\php_error_log');
    http_response_code(500);
    echo json_encode(['exists' => false]);
    exit;
}
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
echo json_encode(['exists' => $row['count'] > 0]);
mysqli_stmt_close($stmt);
?>