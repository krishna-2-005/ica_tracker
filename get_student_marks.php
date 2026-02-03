<?php
session_start();
include 'db_connect.php';

// Default response
$response = [];

if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'teacher' && isset($_GET['component_id'])) {
    $teacher_id = (int)$_SESSION['user_id'];
    $component_id = (int)$_GET['component_id'];

    $query = "SELECT student_id, instance_number, marks FROM ica_student_marks WHERE teacher_id = ? AND component_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $teacher_id, $component_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $value = $row['marks'];
        if (is_null($value)) {
            $value = 'AB';
        }
        // Create a nested structure: { student_id: { instance_number: marks } }
        $response[$row['student_id']][$row['instance_number']] = $value;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>
