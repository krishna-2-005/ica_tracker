<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/email_notifications.php';
require_once __DIR__ . '/includes/activity_logger.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$adminNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$adminNameDisplay = $adminNameRaw !== '' ? format_person_display($adminNameRaw) : 'ADMIN';
$error = '';
$success = '';
$selectedScenario = 'subject_assignment';
$recipientEmail = '';
$recipientName = '';

$scenarioOptions = [
    EMAIL_SCENARIO_SUBJECT_ASSIGNMENT => 'Subject Assignment to Faculty',
    EMAIL_SCENARIO_ASSIGNMENT_CREATED => 'Assignment Created for Student',
    EMAIL_SCENARIO_PROGRAM_ALERT => 'Program Alert to Faculty',
    EMAIL_SCENARIO_ICA_MARKS_PUBLISHED => 'ICA Marks Published',
    EMAIL_SCENARIO_TIMETABLE_PUBLISHED => 'Timetable Published',
    EMAIL_SCENARIO_PASSWORD_RESET => 'Password Reset',
];

function create_test_password_reset_token(mysqli $conn, int $userId): array
{
    $createTableSql = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        token VARCHAR(128) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX(token(64))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conn, $createTableSql);

    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);
    $insertStmt = mysqli_prepare($conn, 'INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
    if (!$insertStmt) {
        throw new RuntimeException('Unable to prepare password reset token for test email.');
    }

    mysqli_stmt_bind_param($insertStmt, 'iss', $userId, $token, $expiresAt);
    if (!mysqli_stmt_execute($insertStmt)) {
        mysqli_stmt_close($insertStmt);
        throw new RuntimeException('Unable to create password reset token for test email.');
    }
    mysqli_stmt_close($insertStmt);

    return [
        'reset_link' => email_notification_app_url() . '/reset_password.php?token=' . rawurlencode($token),
        'link_expires_at' => '1 hour (expires ' . date('d M Y, h:i A', strtotime($expiresAt)) . ')',
    ];
}

function build_test_email_payload(mysqli $conn, string $scenario, string $recipientName, int $actorUserId): array
{
    $baseUrl = email_notification_app_url();
    $displayName = trim($recipientName) !== '' ? trim($recipientName) : 'Test User';

    switch ($scenario) {
        case EMAIL_SCENARIO_ASSIGNMENT_CREATED:
            return [
                'recipient_name' => $displayName,
                'subject_name' => 'Data Structures and Algorithms',
                'assignment_title' => 'Assignment 2: Trees and Traversals',
                'assignment_description' => 'Complete the coding and theory tasks for binary trees, BST insertion, and traversal analysis.',
                'faculty_name' => 'Dr. Ananya Rao',
                'start_at' => date('d M Y, h:i A'),
                'due_at' => date('d M Y, h:i A', strtotime('+5 days')),
                'assignment_url' => $baseUrl . '/student_dashboard.php',
            ];

        case EMAIL_SCENARIO_PROGRAM_ALERT:
            return [
                'recipient_name' => $displayName,
                'alert_title' => 'Attendance Review Required',
                'alert_message' => 'Please review the latest attendance trend and update your pending mentor remarks before Friday.',
                'sender_name' => 'Program Chair Office',
                'sender_type' => 'Program Chair',
                'alerts_url' => $baseUrl . '/view_alerts.php',
            ];

        case EMAIL_SCENARIO_ICA_MARKS_PUBLISHED:
            return [
                'recipient_name' => $displayName,
                'subject_name' => 'Database Management Systems',
                'component_name' => 'ICA 1 - Quiz',
                'marks_obtained' => '18',
                'max_marks' => '20',
                'faculty_name' => 'Prof. Kiran Mehta',
                'feedback' => 'Good understanding of normalization and SQL joins.',
                'marks_url' => $baseUrl . '/view_marks.php',
            ];

        case EMAIL_SCENARIO_SUBJECT_ASSIGNMENT:
            return [
                'recipient_name' => $displayName,
                'subject_name' => 'Operating Systems',
                'academic_year' => '2025-26',
                'semester' => 'Semester 4',
                'class_section' => '2nd Year CSEDS - Section A - STME',
                'student_count' => '62',
                'assigned_role' => 'Faculty',
                'subjects_url' => $baseUrl . '/teacher_dashboard.php',
            ];

        case EMAIL_SCENARIO_TIMETABLE_PUBLISHED:
            return [
                'recipient_name' => $displayName,
                'academic_year' => '2025-26',
                'semester' => 'Semester 6',
                'class_section' => '3rd Year CSEDS - STME',
                'timetable_url' => $baseUrl . '/view_timetable.php',
                'timetable_label' => 'Semester Timeline 2',
                'student_count' => '58',
            ];

        case EMAIL_SCENARIO_PASSWORD_RESET:
            $resetData = create_test_password_reset_token($conn, $actorUserId);
            return [
                'recipient_name' => $displayName,
                'reset_link' => $resetData['reset_link'],
                'link_expires_at' => $resetData['link_expires_at'],
            ];

        default:
            throw new InvalidArgumentException('Unsupported test email scenario.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test_mail'])) {
    $selectedScenario = trim((string)($_POST['scenario'] ?? $selectedScenario));
    $recipientEmail = trim((string)($_POST['recipient_email'] ?? ''));
    $recipientName = trim((string)($_POST['recipient_name'] ?? ''));

    if (!isset($scenarioOptions[$selectedScenario])) {
        $error = 'Please choose a valid email scenario.';
    } elseif ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid recipient email address.';
    } else {
        try {
            $payload = build_test_email_payload($conn, $selectedScenario, $recipientName, (int)$_SESSION['user_id']);
            $sent = send_notification_email($recipientEmail, $selectedScenario, $payload);

            if ($sent) {
                $success = 'Test email sent successfully to ' . $recipientEmail . '.';
                log_activity($conn, [
                    'actor_id' => (int)$_SESSION['user_id'],
                    'event_type' => 'test_email_sent',
                    'event_label' => 'Test email sent',
                    'description' => 'Admin sent a test email for notification preview.',
                    'object_type' => 'email_notification',
                    'object_id' => $selectedScenario,
                    'object_label' => $scenarioOptions[$selectedScenario],
                    'metadata' => [
                        'recipient_email' => $recipientEmail,
                        'recipient_name' => $recipientName,
                        'scenario' => $selectedScenario,
                    ],
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ]);
            } else {
                $error = 'Mail could not be sent. Check SMTP settings and storage/logs/app.log for details.';
            }
        } catch (Throwable $exception) {
            $error = 'Mail could not be sent: ' . $exception->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Mail - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .test-mail-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
            gap: 20px;
        }
        .helper-list {
            margin: 0;
            padding-left: 18px;
            color: #555;
            line-height: 1.7;
        }
        .status-box {
            margin-bottom: 15px;
            padding: 12px 16px;
            border-radius: 10px;
            font-weight: 600;
        }
        .status-box.success {
            background: #f6fbf4;
            border: 1px solid #b7e4c7;
            color: #1b4332;
        }
        .status-box.error {
            background: #fff5f5;
            border: 1px solid #f0b4b4;
            color: #9b2226;
        }
        .helper-card p {
            color: #555;
            line-height: 1.7;
        }
        .inline-note {
            margin-top: 8px;
            font-size: 0.9rem;
            color: #666;
        }
        textarea,
        select,
        input {
            width: 100%;
        }
        @media (max-width: 900px) {
            .test-mail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h2>ICA Tracker</h2>
            <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> <span>Manage Teachers</span></a>
            <a href="create_classes.php"><i class="fas fa-layer-group"></i> <span>Create Classes</span></a>
            <a href="create_subjects.php"><i class="fas fa-book"></i> <span>Create Subjects</span></a>
            <a href="assign_teachers.php"><i class="fas fa-user-tag"></i> <span>Assign Teachers</span></a>
            <a href="manage_electives.php"><i class="fas fa-user-friends"></i> <span>Manage Electives</span></a>
            <a href="change_roles.php"><i class="fas fa-user-cog"></i> <span>Change Roles</span></a>
            <a href="bulk_add_students.php"><i class="fas fa-file-upload"></i> <span>Add Students</span></a>
            <a href="manage_academic_calendar.php"><i class="fas fa-calendar-alt"></i> <span>Academic Calendar</span></a>
            <a href="test_mail.php" class="active"><i class="fas fa-envelope-open-text"></i> <span>Test Mail</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
        <div class="main-content">
            <div class="header">
                <h2>Welcome, <?php echo htmlspecialchars($adminNameDisplay); ?>!</h2>
                <p>Send a sample notification through the same template and SMTP pipeline used by the app.</p>
            </div>
            <div class="container">
                <div class="test-mail-grid">
                    <div class="card">
                        <div class="card-header">
                            <h5>Send Test Mail</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($success !== ''): ?>
                                <div class="status-box success"><?php echo htmlspecialchars($success); ?></div>
                            <?php endif; ?>
                            <?php if ($error !== ''): ?>
                                <div class="status-box error"><?php echo htmlspecialchars($error); ?></div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="form-group">
                                    <label for="recipient_email">Recipient Email</label>
                                    <input type="email" id="recipient_email" name="recipient_email" value="<?php echo htmlspecialchars($recipientEmail); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="recipient_name">Recipient Name</label>
                                    <input type="text" id="recipient_name" name="recipient_name" value="<?php echo htmlspecialchars($recipientName); ?>" placeholder="Optional display name">
                                    <div class="inline-note">If left empty, the email will use Test User as the display name.</div>
                                </div>

                                <div class="form-group">
                                    <label for="scenario">Email Scenario</label>
                                    <select id="scenario" name="scenario" required>
                                        <?php foreach ($scenarioOptions as $scenarioKey => $scenarioLabel): ?>
                                            <option value="<?php echo htmlspecialchars($scenarioKey); ?>" <?php echo $selectedScenario === $scenarioKey ? 'selected' : ''; ?>><?php echo htmlspecialchars($scenarioLabel); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <button type="submit" name="send_test_mail" class="btn">Send Test Mail</button>
                            </form>
                        </div>
                    </div>

                    <div class="card helper-card">
                        <div class="card-header">
                            <h5>What This Sends</h5>
                        </div>
                        <div class="card-body">
                            <p>This page does not create real assignments, alerts, marks, or timetable broadcasts. It only sends a sample email using the live mail configuration.</p>
                            <ul class="helper-list">
                                <li>Uses the shared template from emailtemplate.html</li>
                                <li>Uses the same SMTP account configured in .env</li>
                                <li>Lets you test the NMIMS logo and layout in Gmail</li>
                                <li>Helps isolate mail problems without changing real data</li>
                            </ul>
                            <p class="inline-note">If sending fails, review storage/logs/app.log for the SMTP error.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
