<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/academic_context.php';

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
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;

// Determine if subject is elective or regular
$subject_type = 'regular';
$type_stmt = mysqli_prepare($conn, "SELECT COALESCE(sd.subject_type, 'regular') AS subject_type FROM subjects s LEFT JOIN subject_details sd ON sd.subject_id = s.id WHERE s.id = ?");
if ($type_stmt) {
    mysqli_stmt_bind_param($type_stmt, 'i', $subject_id);
    mysqli_stmt_execute($type_stmt);
    $type_result = mysqli_stmt_get_result($type_stmt);
    if ($type_result && ($type_row = mysqli_fetch_assoc($type_result))) {
        $subject_type = strtolower(trim((string)$type_row['subject_type']));
    }
    if ($type_result) {
        mysqli_free_result($type_result);
    }
    mysqli_stmt_close($type_stmt);
}

$is_elective = ($subject_type === 'elective');

// Pull students based on explicit teacher-subject-class assignments
$query = "
    SELECT DISTINCT s.id, s.name, s.roll_number, s.sap_id
    FROM teacher_subject_assignments tsa
    JOIN classes c ON c.id = tsa.class_id
    JOIN students s ON s.class_id = tsa.class_id";

if ($is_elective) {
    $query .= "
        JOIN student_elective_choices sec ON sec.student_id = s.id
            AND sec.class_id = tsa.class_id
            AND sec.subject_id = tsa.subject_id";
}

$query .= "
        WHERE tsa.teacher_id = ?
            AND tsa.subject_id = ?";

$types = "ii";
$params = [$teacher_id, $subject_id];

if ($class_id > 0) {
    $query .= " AND tsa.class_id = ?";
    $types .= "i";
    $params[] = $class_id;
}

if ($section_id > 0) {
    $query .= " AND COALESCE(tsa.section_id, 0) = ?";
    $types .= "i";
    $params[] = $section_id;
}

$query .= "
            AND (tsa.section_id IS NULL OR COALESCE(tsa.section_id, 0) = COALESCE(s.section_id, 0))";
if ($activeTermId > 0) {
    $query .= " AND c.academic_term_id = ?";
    $types .= "i";
    $params[] = $activeTermId;
}

if ($section_id > 0) {
    $query .= " AND COALESCE(s.section_id, 0) = ?";
    $types .= "i";
    $params[] = $section_id;
}

$query .= "
        ORDER BY s.roll_number ASC";

$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = false;
}

$students = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($students);
?>
