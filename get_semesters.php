<?php
require_once __DIR__ . '/includes/init.php';

header('Content-Type: application/json');

require_once __DIR__ . '/db_connect.php';

$param = isset($_GET['school']) ? 'school' : null;
if (!$param) {
    echo json_encode([]);
    exit;
}

$school = mysqli_real_escape_string($conn, $_GET['school']);

$query = "SELECT DISTINCT semester FROM classes WHERE school = ? ORDER BY semester ASC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $school);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$semesters = [];
while ($row = mysqli_fetch_assoc($result)) {
    $semesters[] = $row;
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode($semesters);
?>
