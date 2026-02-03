<?php
// Set header to JSON early to ensure JSON is always returned, even on error.
header('Content-Type: application/json');
include 'db_connect.php';

if (!isset($_GET['school'])) {
    echo json_encode(['error' => 'No school specified']);
    exit;
}

$school = mysqli_real_escape_string($conn, $_GET['school']);

// Fetch teachers and program chairs from the selected school.
$query = "SELECT id, name, role FROM users WHERE role IN ('teacher', 'program_chair') AND school = ? ORDER BY name";
$stmt = mysqli_prepare($conn, $query);

if ($stmt === false) {
    echo json_encode(['error' => 'Database query failed to prepare.']);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $school);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$teachers = [];
while ($row = mysqli_fetch_assoc($result)) {
    $nameRaw = isset($row['name']) ? trim((string)$row['name']) : '';
    $nameDisplay = $nameRaw !== '' ? format_person_display($nameRaw) : '';
    if ($row['role'] === 'program_chair') {
        $nameDisplay = ($nameDisplay !== '' ? $nameDisplay : 'PROGRAM CHAIR') . ' (PROGRAM CHAIR)';
    } elseif ($nameDisplay === '' && $nameRaw !== '') {
        $nameDisplay = $nameRaw;
    }

    $teachers[] = [
        'id' => (int)$row['id'],
        'name' => $nameDisplay !== '' ? $nameDisplay : 'FACULTY'
    ];
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode($teachers);
?>
