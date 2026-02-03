<?php
session_start();
include 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher' || !isset($_GET['subject'])) {
    echo json_encode(['last_completed_week' => 0]);
    exit;
}

$teacher_id = (int)$_SESSION['user_id'];
$subject_name = $_GET['subject'];

$total_weeks = 0;
$current_school = '';
$school_query = "SELECT school FROM users WHERE id = ?";
$stmt_school = mysqli_prepare($conn, $school_query);
mysqli_stmt_bind_param($stmt_school, "i", $teacher_id);
mysqli_stmt_execute($stmt_school);
$school_result = mysqli_stmt_get_result($stmt_school);
if ($school_row = mysqli_fetch_assoc($school_result)) {
    $current_school = $school_row['school'];
}
mysqli_stmt_close($stmt_school);

if ($current_school) {
    $calendar_query = "SELECT start_date, end_date FROM academic_calendar WHERE school_name = ? AND CURDATE() BETWEEN start_date AND end_date LIMIT 1";
    $stmt_calendar = mysqli_prepare($conn, $calendar_query);
    mysqli_stmt_bind_param($stmt_calendar, "s", $current_school);
    mysqli_stmt_execute($stmt_calendar);
    $calendar_result = mysqli_stmt_get_result($stmt_calendar);
    if ($calendar_row = mysqli_fetch_assoc($calendar_result)) {
        $start_date = new DateTime($calendar_row['start_date']);
        $end_date = new DateTime($calendar_row['end_date']);
        $days = $start_date->diff($end_date)->days + 1;
        $total_weeks = max(1, (int)ceil($days / 7));
    }
    mysqli_stmt_close($stmt_calendar);
}

$query = "SELECT timeline FROM syllabus_progress WHERE teacher_id = ? AND subject = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "is", $teacher_id, $subject_name);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$last_completed_week = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $timeline = $row['timeline'];
    if (preg_match('/week_(\d+)/i', $timeline, $matches)) {
        $week = (int)$matches[1];
        if ($week > $last_completed_week) {
            $last_completed_week = $week;
        }
        continue;
    }

    if ($total_weeks > 0) {
        if ($timeline === 'mid1') {
            $week = max(1, (int)round($total_weeks / 3));
        } elseif ($timeline === 'mid2') {
            $week = max(1, (int)round(($total_weeks / 3) * 2));
        } elseif ($timeline === 'final') {
            $week = $total_weeks;
        } else {
            $week = 0;
        }
        if ($week > $last_completed_week) {
            $last_completed_week = $week;
        }
    }
}

mysqli_stmt_close($stmt);

echo json_encode(['last_completed_week' => $last_completed_week, 'total_weeks' => $total_weeks]);
?>