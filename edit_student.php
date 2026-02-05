<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: student_progress.php');
    exit;
}

$student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
$sap_id = isset($_POST['sap_id']) ? trim($_POST['sap_id']) : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$roll = isset($_POST['roll_number']) ? trim($_POST['roll_number']) : '';
$class_id = isset($_POST['class_id']) && is_numeric($_POST['class_id']) ? (int)$_POST['class_id'] : null;
$section_id = isset($_POST['section_id']) && is_numeric($_POST['section_id']) ? (int)$_POST['section_id'] : null;

if ($student_id <= 0) {
    $_SESSION['error'] = 'Invalid student selected.';
    header('Location: student_progress.php');
    exit;
}

$stmt = mysqli_prepare($conn, "UPDATE students SET sap_id = ?, name = ?, roll_number = ?, class_id = ?, section_id = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, "sssiii", $sap_id, $name, $roll, $class_id, $section_id, $student_id);
if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success'] = 'Student details updated successfully.';
} else {
    $_SESSION['error'] = 'Failed to update student: ' . mysqli_error($conn);
}
mysqli_stmt_close($stmt);
mysqli_close($conn);

header('Location: student_progress.php');
exit;
?>
