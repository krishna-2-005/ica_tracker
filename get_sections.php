<?php
require_once __DIR__ . '/includes/init.php';

header('Content-Type: application/json');

require_once __DIR__ . '/db_connect.php';

if (!isset($_GET['class_id'])) {
    echo json_encode([]);
    exit;
}

$class_id = (int)$_GET['class_id'];

$query = "SELECT id, section_name FROM sections WHERE class_id = ? ORDER BY section_name ASC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $class_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$sections = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sections[] = $row;
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode($sections);
?>