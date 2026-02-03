<?php
header('Content-Type: application/json');
include 'db_connect.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'Student id required']);
    exit;
}
$id = (int)$_GET['id'];

$stmt = mysqli_prepare($conn, "SELECT id, sap_id, name, roll_number, class_id, section_id FROM students WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (!$res) {
    echo json_encode(['error' => 'Query failed']);
    exit;
}
$row = mysqli_fetch_assoc($res);
if (!$row) {
    echo json_encode(['error' => 'Student not found']);
    exit;
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode(['student' => $row]);
exit;
?>
