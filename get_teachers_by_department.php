<?php
require_once __DIR__ . '/includes/init.php';

// Set header to JSON early to ensure JSON is always returned, even on error.
header('Content-Type: application/json');

require_once __DIR__ . '/db_connect.php';

if (!isset($_GET['department'])) {
    echo json_encode(['error' => 'No department specified']);
    exit;
}

$department = mysqli_real_escape_string($conn, $_GET['department']);

// This query specifically fetches users with the 'teacher' role from the selected department.
$query = "SELECT id, name FROM users WHERE role = 'teacher' AND department = ? ORDER BY name";
$stmt = mysqli_prepare($conn, $query);

if ($stmt === false) {
    // If prepare fails, it's a syntax error in the SQL or DB connection issue.
    echo json_encode(['error' => 'Database query failed to prepare.']);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $department);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$teachers = [];
while ($row = mysqli_fetch_assoc($result)) {
    $nameRaw = isset($row['name']) ? trim((string)$row['name']) : '';
    $row['name_display'] = $nameRaw !== '' ? format_person_display($nameRaw) : '';
    $teachers[] = $row;
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode($teachers);
?>
