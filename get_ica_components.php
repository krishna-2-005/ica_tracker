<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher' || !isset($_GET['subject_id'])) {
    echo json_encode([]);
    exit;
}

$teacher_id = (int)$_SESSION['user_id'];
$subject_id = (int)$_GET['subject_id'];
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

$fetchComponents = static function(mysqli $conn, int $teacherId, int $subjectId, ?int $classId): array {
    $sql = "SELECT id, component_name, instances, marks_per_instance, total_marks, scaled_total_marks, class_id
            FROM ica_components
            WHERE teacher_id = ? AND subject_id = ?";
    $types = "ii";
    $params = [$teacherId, $subjectId];

    if ($classId !== null) {
        $sql .= " AND class_id = ?";
        $types .= "i";
        $params[] = $classId;
    } else {
        $sql .= " AND (class_id IS NULL OR class_id = 0)";
    }

    $sql .= " ORDER BY id";

    $stmtLocal = mysqli_prepare($conn, $sql);
    if (!$stmtLocal) {
        return [];
    }

    mysqli_stmt_bind_param($stmtLocal, $types, ...$params);
    mysqli_stmt_execute($stmtLocal);
    $resultLocal = mysqli_stmt_get_result($stmtLocal);

    $items = [];
    if ($resultLocal) {
        while ($row = mysqli_fetch_assoc($resultLocal)) {
            $totalMarks = (float)($row['total_marks'] ?? 0);
            $scaledTotalMarks = (float)($row['scaled_total_marks'] ?? 0);
            if ($totalMarks === 0 || $scaledTotalMarks === 0) {
                error_log("Warning: total_marks or scaled_total_marks is 0 for component_id {$row['id']}", 3, 'C:\xampp\php\logs\php_error_log');
            }
            $items[] = [
                'id' => (int)$row['id'],
                'component_name' => $row['component_name'],
                'instances' => (int)$row['instances'],
                'marks_per_instance' => (float)$row['marks_per_instance'],
                'total_marks' => $totalMarks,
                'scaled_total_marks' => $scaledTotalMarks,
                'class_id' => isset($row['class_id']) ? (int)$row['class_id'] : null
            ];
        }
        mysqli_free_result($resultLocal);
    }

    mysqli_stmt_close($stmtLocal);
    return $items;
};

$components = [];
if ($class_id > 0) {
    $components = $fetchComponents($conn, $teacher_id, $subject_id, $class_id);
    if (empty($components)) {
        $components = $fetchComponents($conn, $teacher_id, $subject_id, null);
    }
} else {
    $components = $fetchComponents($conn, $teacher_id, $subject_id, null);
}

header('Content-Type: application/json');
echo json_encode($components);
?>