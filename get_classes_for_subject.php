<?php
session_start();
include 'db_connect.php';
require_once __DIR__ . '/includes/academic_context.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher' || !isset($_GET['subject_id'])) {
    echo json_encode([]);
    exit;
}

$teacher_id = (int)$_SESSION['user_id'];
$teacherSchool = '';
$stmtTeacherSchool = mysqli_prepare($conn, "SELECT school FROM users WHERE id = ?");
if ($stmtTeacherSchool) {
    mysqli_stmt_bind_param($stmtTeacherSchool, "i", $teacher_id);
    mysqli_stmt_execute($stmtTeacherSchool);
    $resTeacherSchool = mysqli_stmt_get_result($stmtTeacherSchool);
    if ($resTeacherSchool && ($rowSchool = mysqli_fetch_assoc($resTeacherSchool))) {
        $teacherSchool = trim((string)($rowSchool['school'] ?? ''));
        mysqli_free_result($resTeacherSchool);
    }
    mysqli_stmt_close($stmtTeacherSchool);
}

$academicContext = resolveAcademicContext($conn, [
    'school_name' => $teacherSchool
]);
$activeTerm = $academicContext['active'] ?? null;
$activeTermId = $activeTerm && isset($activeTerm['id']) ? (int)$activeTerm['id'] : 0;

$subject_id = (int)$_GET['subject_id'];
$separate_divisions = false;
if (isset($_GET['separate_divisions'])) {
    $flag = strtolower(trim((string)$_GET['separate_divisions']));
    $separate_divisions = in_array($flag, ['1', 'true', 'yes', 'on'], true);
}

if ($separate_divisions) {
    $query = "SELECT DISTINCT
                     c.id AS class_id,
                     c.class_name,
                     c.semester,
                     c.school,
                     sec.id AS section_id,
                     sec.section_name
              FROM classes c
              JOIN teacher_subject_assignments tsa ON c.id = tsa.class_id
              LEFT JOIN sections sec ON sec.id = tsa.section_id
              WHERE tsa.teacher_id = ? AND tsa.subject_id = ?";
    if ($activeTermId > 0) {
        $query .= " AND c.academic_term_id = ?";
    }
    $query .= "
              ORDER BY c.class_name,
                       CASE WHEN sec.section_name IS NULL OR sec.section_name = '' THEN 1 ELSE 0 END,
                       sec.section_name";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        echo json_encode([]);
        exit;
    }

    if ($activeTermId > 0) {
        mysqli_stmt_bind_param($stmt, "iii", $teacher_id, $subject_id, $activeTermId);
    } else {
        mysqli_stmt_bind_param($stmt, "ii", $teacher_id, $subject_id);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $classes = [];
    $seen = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $class_id = isset($row['class_id']) ? (int)$row['class_id'] : 0;
        $section_id = isset($row['section_id']) ? (int)$row['section_id'] : null;
        $section_name = isset($row['section_name']) ? trim((string)$row['section_name']) : '';
        $semester = $row['semester'] ?? '';
        $key = $class_id . '-' . ($section_id ?? 0);
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;

        $classes[] = [
            'id' => $class_id,
            'section_id' => $section_id,
            'section_name' => $section_name,
            'semester' => $semester,
            'class_name' => format_class_label($row['class_name'] ?? '', $section_name, $semester, $row['school'] ?? '')
        ];
    }

    mysqli_stmt_close($stmt);
    echo json_encode($classes);
    exit;
}

// Default grouping keeps combined divisions for backwards compatibility
$query = "SELECT c.id,
                 c.class_name,
                 c.semester,
                 c.school,
                 COALESCE(GROUP_CONCAT(DISTINCT sec.section_name ORDER BY sec.section_name SEPARATOR '/'), '') AS divisions
          FROM classes c
          JOIN teacher_subject_assignments tsa ON c.id = tsa.class_id
          LEFT JOIN sections sec ON sec.id = tsa.section_id
          WHERE tsa.teacher_id = ? AND tsa.subject_id = ?";
if ($activeTermId > 0) {
    $query .= " AND c.academic_term_id = ?";
}
$query .= "
          GROUP BY c.id, c.class_name, c.semester, c.school
          ORDER BY c.class_name";

$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    echo json_encode([]);
    exit;
}

if ($activeTermId > 0) {
    mysqli_stmt_bind_param($stmt, "iii", $teacher_id, $subject_id, $activeTermId);
} else {
    mysqli_stmt_bind_param($stmt, "ii", $teacher_id, $subject_id);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$classes = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['class_name'] = format_class_label($row['class_name'] ?? '', $row['divisions'] ?? '', $row['semester'] ?? '', $row['school'] ?? '');
    $classes[] = $row;
}

mysqli_stmt_close($stmt);
echo json_encode($classes);
?>