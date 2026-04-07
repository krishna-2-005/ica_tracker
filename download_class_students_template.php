<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo 'Unauthorized.';
    exit;
}

$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$sectionId = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;

if ($classId <= 0) {
    http_response_code(400);
    echo 'Invalid class selection.';
    exit;
}

$classLabel = 'class_' . $classId;
$classStmt = mysqli_prepare($conn, 'SELECT class_name, semester, school FROM classes WHERE id = ? LIMIT 1');
if ($classStmt) {
    mysqli_stmt_bind_param($classStmt, 'i', $classId);
    mysqli_stmt_execute($classStmt);
    $classRes = mysqli_stmt_get_result($classStmt);
    if ($classRes) {
        $row = mysqli_fetch_assoc($classRes);
        if ($row) {
            $classLabel = trim((string)($row['class_name'] ?? ''));
            $semester = trim((string)($row['semester'] ?? ''));
            $school = trim((string)($row['school'] ?? ''));
            if ($semester !== '') {
                $classLabel .= '_sem_' . preg_replace('/[^A-Za-z0-9]/', '', $semester);
            }
            if ($school !== '') {
                $classLabel .= '_' . preg_replace('/[^A-Za-z0-9]/', '', $school);
            }
        }
        mysqli_free_result($classRes);
    }
    mysqli_stmt_close($classStmt);
}

$sql = 'SELECT sap_id, roll_number, name, college_email FROM students WHERE class_id = ?';
$types = 'i';
$params = [$classId];
if ($sectionId > 0) {
    $sql .= ' AND section_id = ?';
    $types .= 'i';
    $params[] = $sectionId;
}
$sql .= ' ORDER BY roll_number, sap_id, name';

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    http_response_code(500);
    echo 'Unable to prepare export.';
    exit;
}

mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', strtolower($classLabel));
if ($filename === '') {
    $filename = 'class_students';
}
$filename .= '_template.csv';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
if ($output === false) {
    mysqli_stmt_close($stmt);
    exit;
}

fputcsv($output, ['S/N', 'ROLL NO', 'SAP ID', 'NAME OF STUDENT', 'COLLEGE EMAIL']);

$serial = 1;
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $serial,
            (string)($row['roll_number'] ?? ''),
            (string)($row['sap_id'] ?? ''),
            (string)($row['name'] ?? ''),
            (string)($row['college_email'] ?? ''),
        ]);
        $serial++;
    }
    mysqli_free_result($result);
}

mysqli_stmt_close($stmt);
fclose($output);
exit;
