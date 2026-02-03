<?php
session_start();
include 'db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'program_chair') {
    header('Location: login.php');
    exit;
}

$feedback = '';
if (isset($_SESSION['alert_feedback'])) {
    $feedback = $_SESSION['alert_feedback'];
    unset($_SESSION['alert_feedback']);
}

// Handle sending alert
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_alert'])) {
    $recipient = trim($_POST['teacher_id'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($recipient === '' || $message === '') {
        $_SESSION['alert_feedback'] = 'Please pick a teacher (or All Teachers) and enter a message.';
        header('Location: send_alerts.php');
        exit;
    }

    $insert_stmt = mysqli_prepare($conn, "INSERT INTO alerts (teacher_id, message, status, created_at) VALUES (?, ?, 'pending', NOW())");
    if (!$insert_stmt) {
        $_SESSION['alert_feedback'] = 'Could not prepare alert insert. Please try again later.';
        header('Location: send_alerts.php');
        exit;
    }

    $teacher_id_param = 0;
    $message_param = $message;
    mysqli_stmt_bind_param($insert_stmt, "is", $teacher_id_param, $message_param);

    $insert_count = 0;

    if ($recipient === 'all') {
        $teachers_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE role = 'teacher'");
        if ($teachers_stmt) {
            mysqli_stmt_execute($teachers_stmt);
            $teachers_result = mysqli_stmt_get_result($teachers_stmt);
            while ($teacher_row = mysqli_fetch_assoc($teachers_result)) {
                $teacher_id_param = (int)$teacher_row['id'];
                if (mysqli_stmt_execute($insert_stmt)) {
                    $insert_count++;
                }
            }
            mysqli_free_result($teachers_result);
            mysqli_stmt_close($teachers_stmt);
        }
    } else {
        $teacher_id_param = (int)$recipient;
        if (mysqli_stmt_execute($insert_stmt)) {
            $insert_count = 1;
        }
    }

    mysqli_stmt_close($insert_stmt);

    if ($insert_count > 0) {
        $_SESSION['alert_feedback'] = $insert_count === 1
            ? 'Alert sent successfully.'
            : "Alert sent to {$insert_count} teachers.";
    } else {
        $_SESSION['alert_feedback'] = 'No alerts were sent. Please verify your selection and try again.';
    }

    header('Location: send_alerts.php');
    exit;
}

// Fetch teachers for the dropdown
$teachers_query = "SELECT id, name FROM users WHERE role='teacher'";
$teachers_result = mysqli_query($conn, $teachers_query);

// Fetch sent alerts and their responses, including both timestamps
$alerts_query = "SELECT a.id, u.name AS teacher_name, a.message, a.response, a.status, a.created_at, a.responded_at 
                 FROM alerts a 
                 JOIN users u ON a.teacher_id = u.id 
                 WHERE a.teacher_id IN (SELECT id FROM users WHERE role='teacher')
                 ORDER BY a.created_at DESC";
$alerts_result = mysqli_query($conn, $alerts_query);

$programChairNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$programChairNameDisplay = $programChairNameRaw !== '' ? format_person_display($programChairNameRaw) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Alerts - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="program-chair">
    <div class="dashboard">
        <!-- Sidebar -->
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
        <div class="sidebar">
            <h2>ICA Tracker</h2>
            <a href="program_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="teacher_progress.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a>
            <a href="student_progress.php"><i class="fas fa-user-graduate"></i> Students</a>
            <a href="course_progress.php"><i class="fas fa-book"></i> Courses</a>
            <a href="program_reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="send_alerts.php" class="active"><i class="fas fa-bell"></i> Alerts</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="edit_profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h2>Welcome, <?php echo htmlspecialchars($programChairNameDisplay !== '' ? $programChairNameDisplay : $programChairNameRaw); ?>!</h2>
            </div>
            <div class="container">
                <h2>Send Alerts</h2>
                <?php if (!empty($feedback)) : ?>
                    <div style="margin-bottom:15px; padding:12px 16px; border-radius:10px; background:#f6fbf4; border:1px solid #b7e4c7; color:#1b4332; font-weight:500;">
                        <?php echo htmlspecialchars($feedback); ?>
                    </div>
                <?php endif; ?>
                <div class="card">
                    <form method="POST">
                        <label>Select Teacher</label>
                        <select name="teacher_id" required>
                            <option value="">Select a Teacher</option>
                            <option value="all">All Teachers</option>
                            <?php while ($teacher = mysqli_fetch_assoc($teachers_result)) { ?>
                                <?php
                                $teacherNameRaw = isset($teacher['name']) ? trim((string)$teacher['name']) : '';
                                $teacherNameDisplay = $teacherNameRaw !== '' ? format_person_display($teacherNameRaw) : '';
                                ?>
                                <option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacherNameDisplay !== '' ? $teacherNameDisplay : $teacherNameRaw); ?></option>
                            <?php } ?>
                        </select>
                        <label>Message</label>
                        <textarea name="message" rows="4" required></textarea>
                        <button type="submit" name="send_alert">Send Alert</button>
                    </form>
                </div>

                <!-- Teacher Responses -->
                <div class="card">
                    <h3>Teacher Responses</h3>
                    <table>
                        <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Message</th>
                            <th>Response</th>
                            <th>Sent At</th>
                            <th>Responded At</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($alert = mysqli_fetch_assoc($alerts_result)) { ?>
                            <?php
                            $alertTeacherRaw = isset($alert['teacher_name']) ? trim((string)$alert['teacher_name']) : '';
                            $alertTeacherDisplay = $alertTeacherRaw !== '' ? format_person_display($alertTeacherRaw) : '';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($alertTeacherDisplay !== '' ? $alertTeacherDisplay : $alertTeacherRaw); ?></td>
                                <td><?php echo htmlspecialchars($alert['message']); ?></td>
                                <td><?php echo htmlspecialchars($alert['response'] ?? 'No response yet'); ?></td>
                                <td><?php echo htmlspecialchars($alert['created_at']); ?></td>
                                <td><?php echo htmlspecialchars($alert['responded_at'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>

                <p><a href="program_dashboard.php">Back to Dashboard</a></p>
            </div>
        </div>
    </div>

    <script>
        // Theme toggle persistence
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }
        document.querySelector('.theme-toggle').addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        });
    </script>
</body>
</html>

