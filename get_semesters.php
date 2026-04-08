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

$active_term_id = isset($_GET['active_term_id']) ? (int)$_GET['active_term_id'] : 0;
$term_type = isset($_GET['term_type']) ? strtolower(trim((string)$_GET['term_type'])) : '';
$parity_mod = null;
if ($term_type === 'even') {
    $parity_mod = 0;
} elseif ($term_type === 'odd') {
    $parity_mod = 1;
}

$query = "SELECT DISTINCT semester FROM classes WHERE school = ?";
if ($active_term_id > 0) {
    $query .= " AND academic_term_id = ?";
} elseif ($parity_mod !== null) {
    $query .= " AND CAST(semester AS UNSIGNED) % 2 = ?";
}
$query .= " ORDER BY CAST(semester AS UNSIGNED) ASC";

$stmt = mysqli_prepare($conn, $query);
if ($active_term_id > 0) {
    mysqli_stmt_bind_param($stmt, "si", $school, $active_term_id);
} elseif ($parity_mod !== null) {
    mysqli_stmt_bind_param($stmt, "si", $school, $parity_mod);
} else {
    mysqli_stmt_bind_param($stmt, "s", $school);
}
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
