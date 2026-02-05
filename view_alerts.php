<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];
$teacherNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$teacherNameDisplay = $teacherNameRaw !== '' ? format_person_display($teacherNameRaw) : '';

// Handle alert response
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['respond_alert'])) {
    $alert_id = (int)$_POST['alert_id'];
    $response = mysqli_real_escape_string($conn, $_POST['response']);
    $query = "UPDATE alerts SET status='responded', response=?, responded_at=NOW() WHERE id=? AND teacher_id=?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sii", $response, $alert_id, $teacher_id);
    if (mysqli_stmt_execute($stmt)) {
        header('Location: view_alerts.php');
        exit;
    }
    mysqli_stmt_close($stmt);
}

// Fetch pending alerts
$pending_alerts_query = "SELECT * FROM alerts WHERE teacher_id = ? AND status = 'pending'";
$stmt_pending = mysqli_prepare($conn, $pending_alerts_query);
mysqli_stmt_bind_param($stmt_pending, "i", $teacher_id);
mysqli_stmt_execute($stmt_pending);
$pending_alerts_result = mysqli_stmt_get_result($stmt_pending);

// Fetch responded alerts
$responded_alerts_query = "SELECT * FROM alerts WHERE teacher_id = ? AND status = 'responded' ORDER BY responded_at DESC";
$stmt_responded = mysqli_prepare($conn, $responded_alerts_query);
mysqli_stmt_bind_param($stmt_responded, "i", $teacher_id);
mysqli_stmt_execute($stmt_responded);
$responded_alerts_result = mysqli_stmt_get_result($stmt_responded);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Alerts - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
            <h2>ICA Tracker</h2>
            <a href="teacher_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="update_progress.php"><i class="fas fa-chart-line"></i> <span>Update Progress</span></a>
            <a href="create_ica_components.php"><i class="fas fa-cogs"></i> <span>ICA Components</span></a>
            <a href="manage_ica_marks.php"><i class="fas fa-book"></i> <span>Manage ICA Marks</span></a>
            <a href="assignments.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a>
            <a href="view_alerts.php" class="active"><i class="fas fa-bell"></i> <span>View Alerts</span></a>
            <a href="view_reports.php"><i class="fas fa-file-alt"></i> <span>View Reports</span></a>
            <a href="timetable.php"><i class="fas fa-calendar-alt"></i> <span>Timetable</span></a>
            <a href="edit_profile.php"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
        <div class="main-content">
            <div class="header">
                <h2>Welcome, <?php echo htmlspecialchars($teacherNameDisplay !== '' ? $teacherNameDisplay : $teacherNameRaw); ?>!</h2>
            </div>
            <div class="container">
                <h2>View Alerts</h2>
                <div class="card">
                    <h3>Pending Alerts</h3>
                    <table>
                        <thead>
                        <tr>
                            <th>Message</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (mysqli_num_rows($pending_alerts_result) > 0): ?>
                            <?php while ($alert = mysqli_fetch_assoc($pending_alerts_result)) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($alert['message']); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                            <textarea name="response" placeholder="Enter response" required></textarea>
                                            <button type="submit" name="respond_alert">Respond</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" style="text-align: center;">No pending alerts.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card">
                    <h3>Responded Alerts</h3>
                    <table>
                        <thead>
                        <tr>
                            <th>Message</th>
                            <th>Your Response</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (mysqli_num_rows($responded_alerts_result) > 0): ?>
                            <?php while ($alert = mysqli_fetch_assoc($responded_alerts_result)) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($alert['message']); ?></td>
                                    <td><?php echo htmlspecialchars($alert['response'] ?? 'No response'); ?></td>
                                </tr>
                            <?php } ?>
                        <?php else: ?>
                             <tr>
                                <td colspan="2" style="text-align: center;">You have not responded to any alerts yet.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <p><a href="teacher_dashboard.php">Back to Dashboard</a></p>
            </div>
        </div>
    </div>
    <script>
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

