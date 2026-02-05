<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'program_chair') {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$userNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$userNameDisplay = $userNameRaw !== '' ? format_person_display($userNameRaw) : 'PROGRAM CHAIR';
$success = '';
$error = '';

// Handle saving settings
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    $settings_to_save = [
        'syllabus_threshold' => (int)$_POST['syllabus_threshold'],
        'performance_threshold' => (int)$_POST['performance_threshold'],
        'email_notifications' => isset($_POST['email_notifications']) ? '1' : '0'
    ];

    $query = "INSERT INTO settings (user_id, setting_key, setting_value) VALUES (?, ?, ?) 
              ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
    $stmt = mysqli_prepare($conn, $query);

    foreach ($settings_to_save as $key => $value) {
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $key, $value);
        mysqli_stmt_execute($stmt);
    }
    
    mysqli_stmt_close($stmt);
    $success = "Settings saved successfully!";
}

// Fetch current settings for the Program Chair
$current_settings_query = "SELECT setting_key, setting_value FROM settings WHERE user_id = ?";
$stmt_fetch = mysqli_prepare($conn, $current_settings_query);
mysqli_stmt_bind_param($stmt_fetch, "i", $user_id);
mysqli_stmt_execute($stmt_fetch);
$result = mysqli_stmt_get_result($stmt_fetch);

$settings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
mysqli_stmt_close($stmt_fetch);

// Set default values if not found in the database
$syllabus_threshold = $settings['syllabus_threshold'] ?? 75;
$performance_threshold = $settings['performance_threshold'] ?? 50;
$email_notifications = isset($settings['email_notifications']) && $settings['email_notifications'] == '1';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="program-chair">
    <div class="dashboard">
        <div class="sidebar">
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
            <h2>ICA Tracker</h2>
            <a href="program_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="teacher_progress.php"><i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span></a>
            <a href="student_progress.php"><i class="fas fa-user-graduate"></i> <span>Students</span></a>
            <a href="course_progress.php"><i class="fas fa-book"></i> <span>Courses</span></a>
            <a href="program_reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
            <a href="send_alerts.php"><i class="fas fa-bell"></i> <span>Alerts</span></a>
            <a href="settings.php" class="active"><i class="fas fa-cog"></i> <span>Settings</span></a>
            <a href="edit_profile.php"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>

        <div class="main-content">
            <div class="header">
                <h2>Welcome, <?php echo htmlspecialchars($userNameDisplay); ?>!</h2>
            </div>
            <div class="container">
                <div class="card">
                    <div class="card-header">
                        <h5>Settings</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <p style="color: #388e3c; font-weight: bold; margin-bottom: 15px;"><?php echo $success; ?></p>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="card" style="margin-bottom: 20px;">
                                <div class="card-header"><h6>Alert Thresholds</h6></div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="syllabus_threshold">Syllabus Progress Threshold (%)</label>
                                        <p style="font-size: 0.9em; color: #666;">Trigger an alert if a teacher's progress is below this percentage of the expected progress.</p>
                                        <input type="number" id="syllabus_threshold" name="syllabus_threshold" min="1" max="100" value="<?php echo htmlspecialchars($syllabus_threshold); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="performance_threshold">Low Student Performance Threshold (%)</label>
                                        <p style="font-size: 0.9em; color: #666;">Flag a student as "at-risk" if their average ICA mark falls below this percentage.</p>
                                        <input type="number" id="performance_threshold" name="performance_threshold" min="1" max="100" value="<?php echo htmlspecialchars($performance_threshold); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header"><h6>Notification Preferences</h6></div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="email_notifications" style="display: flex; align-items: center;">
                                            <input type="checkbox" id="email_notifications" name="email_notifications" value="1" <?php echo $email_notifications ? 'checked' : ''; ?> style="width: auto; margin-right: 10px;">
                                            Receive a daily email summary of new alerts.
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="save_settings" class="btn" style="margin-top: 20px;">Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        }
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }
    </script>
</body>
</html>
