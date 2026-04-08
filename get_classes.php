<?php
require_once __DIR__ . '/includes/init.php';

header('Content-Type: application/json');

require_once __DIR__ . '/db_connect.php';

$school = $_GET['school'] ?? null;
$semester = $_GET['semester'] ?? null;
if (!$school || !$semester) {
    echo json_encode([]);
    exit;
}

$school = mysqli_real_escape_string($conn, $school);
$semester = mysqli_real_escape_string($conn, $semester);

$active_term_id = isset($_GET['active_term_id']) ? (int)$_GET['active_term_id'] : 0;
$term_type = isset($_GET['term_type']) ? strtolower(trim((string)$_GET['term_type'])) : '';
$parity_mod = null;
if ($term_type === 'even') {
    $parity_mod = 0;
} elseif ($term_type === 'odd') {
    $parity_mod = 1;
}

$query = "SELECT c.id,
                 c.class_name,
                 c.semester,
                 c.school,
                 COALESCE(GROUP_CONCAT(DISTINCT sec.section_name ORDER BY sec.section_name SEPARATOR '/'), '') AS divisions
          FROM classes c
          LEFT JOIN sections sec ON sec.class_id = c.id
          WHERE c.school = ? AND c.semester = ?";
if ($active_term_id > 0) {
    $query .= " AND c.academic_term_id = ?";
} elseif ($parity_mod !== null) {
    $query .= " AND CAST(c.semester AS UNSIGNED) % 2 = ?";
}

$query .= "
          GROUP BY c.id, c.class_name, c.semester, c.school
          ORDER BY c.class_name ASC";
$stmt = mysqli_prepare($conn, $query);
if ($active_term_id > 0) {
    mysqli_stmt_bind_param($stmt, "ssi", $school, $semester, $active_term_id);
} elseif ($parity_mod !== null) {
    mysqli_stmt_bind_param($stmt, "ssi", $school, $semester, $parity_mod);
} else {
    mysqli_stmt_bind_param($stmt, "ss", $school, $semester);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$classes = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['class_name'] = format_class_label($row['class_name'] ?? '', $row['divisions'] ?? '', $row['semester'] ?? '', $row['school'] ?? '');
    $classes[] = $row;
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode($classes);
?>
