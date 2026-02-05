<?php
require_once __DIR__ . '/includes/init.php';

header('Content-Type: application/json');

require_once __DIR__ . '/db_connect.php';

$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$history_table_exists = false;
$history_check = mysqli_query($conn, "SHOW TABLES LIKE 'student_class_history'");
if ($history_check) {
    $history_table_exists = mysqli_num_rows($history_check) > 0;
    mysqli_free_result($history_check);
}

if ($class_id <= 0) {
    echo json_encode([]);
    exit;
}

if ($section_id > 0) {
    if ($history_table_exists) {
        $query = "SELECT * FROM (
                    SELECT st.id, st.sap_id, st.roll_number, st.name, st.class_id, st.section_id, c.class_name, c.school, c.semester, sec.section_name, 0 AS is_historical
                    FROM students st
                    JOIN classes c ON st.class_id = c.id
                    LEFT JOIN sections sec ON st.section_id = sec.id
                    WHERE st.class_id = ? AND st.section_id = ?
                    UNION ALL
                    SELECT st.id, st.sap_id, st.roll_number, st.name, h.class_id AS class_id, h.section_id, c.class_name, c.school, c.semester, sec.section_name, 1 AS is_historical
                    FROM student_class_history h
                    JOIN students st ON st.id = h.student_id
                    JOIN classes c ON h.class_id = c.id
                    LEFT JOIN sections sec ON h.section_id = sec.id
                    WHERE h.class_id = ? AND h.section_id = ?
                ) roster
                ORDER BY roster.is_historical, roster.roll_number, roster.name";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iiii", $class_id, $section_id, $class_id, $section_id);
    } else {
        $query = "SELECT st.id, st.sap_id, st.roll_number, st.name, st.class_id, st.section_id, c.class_name, c.school, c.semester, sec.section_name, 0 AS is_historical
                FROM students st
                JOIN classes c ON st.class_id = c.id
                LEFT JOIN sections sec ON st.section_id = sec.id
                WHERE st.class_id = ? AND st.section_id = ?
                ORDER BY st.roll_number, st.name";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $class_id, $section_id);
    }
} else {
    if ($history_table_exists) {
        $query = "SELECT * FROM (
                    SELECT st.id, st.sap_id, st.roll_number, st.name, st.class_id, st.section_id, c.class_name, c.school, c.semester, sec.section_name, 0 AS is_historical
                    FROM students st
                    JOIN classes c ON st.class_id = c.id
                    LEFT JOIN sections sec ON st.section_id = sec.id
                    WHERE st.class_id = ?
                    UNION ALL
                    SELECT st.id, st.sap_id, st.roll_number, st.name, h.class_id AS class_id, h.section_id, c.class_name, c.school, c.semester, sec.section_name, 1 AS is_historical
                    FROM student_class_history h
                    JOIN students st ON st.id = h.student_id
                    JOIN classes c ON h.class_id = c.id
                    LEFT JOIN sections sec ON h.section_id = sec.id
                    WHERE h.class_id = ?
                ) roster
                ORDER BY roster.is_historical, roster.roll_number, roster.name";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $class_id, $class_id);
    } else {
        $query = "SELECT st.id, st.sap_id, st.roll_number, st.name, st.class_id, st.section_id, c.class_name, c.school, c.semester, sec.section_name, 0 AS is_historical
                FROM students st
                JOIN classes c ON st.class_id = c.id
                LEFT JOIN sections sec ON st.section_id = sec.id
                WHERE st.class_id = ?
                ORDER BY st.roll_number, st.name";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $class_id);
    }
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['is_historical'] = isset($row['is_historical']) ? (int)$row['is_historical'] : 0;
    $students[] = $row;
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode($students);
