<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';

// Ensure a school is provided in the request
if (!isset($_GET['school'])) {
    echo json_encode([]);
    exit;
}

$school = mysqli_real_escape_string($conn, $_GET['school']);

// **MODIFIED QUERY**: Added a condition to the WHERE clause to fetch only
// users with the role of 'teacher' or 'program_chair'.
$query = "SELECT id, name, role FROM users 
          WHERE school = ? 
          AND (role = 'teacher' OR role = 'program_chair') 
          ORDER BY name";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $school);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $nameRaw = isset($row['name']) ? trim((string)$row['name']) : '';
    $row['name_display'] = $nameRaw !== '' ? format_person_display($nameRaw) : '';
    if ($row['role'] === 'program_chair') {
        $row['name_display'] = ($row['name_display'] !== '' ? $row['name_display'] : 'PROGRAM CHAIR') . ' (PROGRAM CHAIR)';
    }
    $users[] = $row;
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

// Set the content type to JSON and output the data
header('Content-Type: application/json');
echo json_encode($users);
?>