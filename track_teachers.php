<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'program_chair') {
    header('Location: login.php');
    exit;
}

$teachers_query = "SELECT id, name FROM users WHERE role='teacher'";
$teachers_result = mysqli_query($conn, $teachers_query);

$teacher_id = isset($_GET['teacher_id']) ? $_GET['teacher_id'] : null;
$progress = [];
if ($teacher_id) {
    $progress_query = "SELECT subject, topic, completion_percentage FROM syllabus_progress WHERE teacher_id='$teacher_id'";
    $progress_result = mysqli_query($conn, $progress_query);
    while ($row = mysqli_fetch_assoc($progress_result)) {
        $progress[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Track Teachers</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>Track Teachers' Progress</h2>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
        <h3>Teachers</h3>
        <ul>
            <?php while ($teacher = mysqli_fetch_assoc($teachers_result)) { ?>
                <?php
                    $teacherNameRaw = isset($teacher['name']) ? trim((string)$teacher['name']) : '';
                    $teacherNameDisplay = $teacherNameRaw !== '' ? format_person_display($teacherNameRaw) : '';
                ?>
                <li><a href="?teacher_id=<?php echo (int)$teacher['id']; ?>"><?php echo htmlspecialchars($teacherNameDisplay !== '' ? $teacherNameDisplay : $teacherNameRaw); ?></a></li>
            <?php } ?>
        </ul>
        <?php if ($teacher_id) { ?>
            <h3>Progress for Selected Teacher</h3>
            <table>
                <tr><th>Subject</th><th>Topic</th><th>Completion (%)</th></tr>
                <?php foreach ($progress as $row) { ?>
                    <tr>
                        <td><?php echo $row['subject']; ?></td>
                        <td><?php echo $row['topic']; ?></td>
                        <td><?php echo $row['completion_percentage']; ?>%</td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>
        <p><a href="program_dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>
