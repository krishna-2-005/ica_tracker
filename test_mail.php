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
$targetAudience = 'single';
$targetClassIds = [];
$otherReasonText = '';

$scenarioOptions = [
    EMAIL_SCENARIO_SUBJECT_ASSIGNMENT => 'Subject Assignment to Faculty',
    EMAIL_SCENARIO_ASSIGNMENT_CREATED => 'Assignment Created for Student',
    EMAIL_SCENARIO_PROGRAM_ALERT => 'Program Alert to Faculty',
    EMAIL_SCENARIO_ICA_MARKS_PUBLISHED => 'ICA Marks Published',
    EMAIL_SCENARIO_TIMETABLE_PUBLISHED => 'Timetable Published',
    EMAIL_SCENARIO_PASSWORD_RESET => 'Password Reset',
    'other_reason' => 'Other Reason',
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

function build_test_email_payload(mysqli $conn, string $scenario, string $recipientName, int $actorUserId, string $otherReasonText = ''): array
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

        case 'other_reason':
            return [
                'recipient_name' => $displayName,
                'alert_title' => 'Important Update from ICA Tracker',
                'alert_message' => $otherReasonText,
                'sender_name' => 'ICA Tracker Admin',
                'sender_type' => 'Admin',
                'alerts_url' => $baseUrl . '/index.php',
            ];

        default:
            throw new InvalidArgumentException('Unsupported test email scenario.');
    }
}

function fetch_mail_targets(mysqli $conn, string $audience, array $classIds = []): array
{
    $targets = [];

    if ($audience === 'faculty' || $audience === 'program_chair' || $audience === 'everyone') {
        $roleSql = "SELECT role, name, email FROM users WHERE role IN ('teacher', 'program_chair') AND email IS NOT NULL AND email <> ''";
        $roleRes = mysqli_query($conn, $roleSql);
        if ($roleRes) {
            while ($row = mysqli_fetch_assoc($roleRes)) {
                $email = strtolower(trim((string)($row['email'] ?? '')));
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                $role = trim((string)($row['role'] ?? ''));
                if ($audience === 'faculty' && $role !== 'teacher') {
                    continue;
                }
                if ($audience === 'program_chair' && $role !== 'program_chair') {
                    continue;
                }
                if (!isset($targets[$email])) {
                    $targets[$email] = trim((string)($row['name'] ?? ''));
                }
            }
            mysqli_free_result($roleRes);
        }
    }

    if ($audience === 'classes' || $audience === 'everyone') {
        $studentSql = "SELECT name, college_email FROM students WHERE college_email IS NOT NULL AND college_email <> ''";
        $types = '';
        $params = [];
        if ($audience === 'classes') {
            $normalizedIds = [];
            foreach ($classIds as $classId) {
                $id = (int)$classId;
                if ($id > 0) {
                    $normalizedIds[$id] = $id;
                }
            }
            if (empty($normalizedIds)) {
                return [];
            }
            $placeholders = implode(',', array_fill(0, count($normalizedIds), '?'));
            $studentSql .= ' AND class_id IN (' . $placeholders . ')';
            $types = str_repeat('i', count($normalizedIds));
            $params = array_values($normalizedIds);
        }

        $studentStmt = mysqli_prepare($conn, $studentSql);
        if ($studentStmt) {
            if ($types !== '') {
                mysqli_stmt_bind_param($studentStmt, $types, ...$params);
            }
            mysqli_stmt_execute($studentStmt);
            $studentRes = mysqli_stmt_get_result($studentStmt);
            if ($studentRes) {
                while ($row = mysqli_fetch_assoc($studentRes)) {
                    $email = strtolower(trim((string)($row['college_email'] ?? '')));
                    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        continue;
                    }
                    if (!isset($targets[$email])) {
                        $targets[$email] = trim((string)($row['name'] ?? ''));
                    }
                }
                mysqli_free_result($studentRes);
            }
            mysqli_stmt_close($studentStmt);
        }
    }

    $recipientList = [];
    foreach ($targets as $email => $name) {
        $recipientList[] = [
            'email' => $email,
            'name' => $name
        ];
    }
    return $recipientList;
}

$classOptions = [];
$classOptionsRes = mysqli_query($conn, 'SELECT id, class_name, semester, school FROM classes ORDER BY school, semester, class_name');
if ($classOptionsRes) {
    while ($row = mysqli_fetch_assoc($classOptionsRes)) {
        $classOptions[] = [
            'id' => (int)($row['id'] ?? 0),
            'label' => format_class_label(
                $row['class_name'] ?? '',
                '',
                $row['semester'] ?? '',
                $row['school'] ?? ''
            )
        ];
    }
    mysqli_free_result($classOptionsRes);
}

$classAudienceCounts = [];
$classAudienceRows = mysqli_query($conn, "SELECT class_id, college_email FROM students WHERE class_id IS NOT NULL AND class_id > 0 AND college_email IS NOT NULL AND college_email <> ''");
if ($classAudienceRows) {
    while ($row = mysqli_fetch_assoc($classAudienceRows)) {
        $classId = (int)($row['class_id'] ?? 0);
        $email = strtolower(trim((string)($row['college_email'] ?? '')));
        if ($classId <= 0 || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            continue;
        }
        if (!isset($classAudienceCounts[$classId])) {
            $classAudienceCounts[$classId] = 0;
        }
        $classAudienceCounts[$classId]++;
    }
    mysqli_free_result($classAudienceRows);
}

$audiencePreviewCounts = [
    'single' => 1,
    'classes' => 0,
    'faculty' => count(fetch_mail_targets($conn, 'faculty', [])),
    'program_chair' => count(fetch_mail_targets($conn, 'program_chair', [])),
    'everyone' => count(fetch_mail_targets($conn, 'everyone', [])),
];
foreach ($targetClassIds as $classId) {
    $audiencePreviewCounts['classes'] += (int)($classAudienceCounts[(int)$classId] ?? 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test_mail'])) {
    $selectedScenario = trim((string)($_POST['scenario'] ?? $selectedScenario));
    $recipientEmail = trim((string)($_POST['recipient_email'] ?? ''));
    $targetAudience = trim((string)($_POST['target_audience'] ?? $targetAudience));
    $rawTargetClassIds = $_POST['target_class_ids'] ?? [];
    $otherReasonText = trim((string)($_POST['other_reason_text'] ?? ''));
    if (!is_array($rawTargetClassIds)) {
        $rawTargetClassIds = [];
    }
    $targetClassIds = [];
    foreach ($rawTargetClassIds as $classId) {
        $id = (int)$classId;
        if ($id > 0) {
            $targetClassIds[$id] = $id;
        }
    }
    $targetClassIds = array_values($targetClassIds);

    $validAudiences = ['single', 'classes', 'faculty', 'program_chair', 'everyone'];

    if (!isset($scenarioOptions[$selectedScenario])) {
        $error = 'Please choose a valid email scenario.';
    } elseif (!in_array($targetAudience, $validAudiences, true)) {
        $error = 'Please choose a valid target audience.';
    } elseif ($targetAudience === 'single' && ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL))) {
        $error = 'Please enter a valid recipient email address.';
    } elseif ($targetAudience === 'classes' && empty($targetClassIds)) {
        $error = 'Please select at least one class for class-wise mailing.';
    } elseif ($selectedScenario === 'other_reason' && $otherReasonText === '') {
        $error = 'Please type the other reason message before sending.';
    } else {
        if ($targetAudience === 'classes') {
            $audiencePreviewCounts['classes'] = 0;
            foreach ($targetClassIds as $classId) {
                $audiencePreviewCounts['classes'] += (int)($classAudienceCounts[(int)$classId] ?? 0);
            }
        }
        try {
            $sentCount = 0;
            $failedCount = 0;
            $effectiveScenario = $selectedScenario === 'other_reason' ? EMAIL_SCENARIO_PROGRAM_ALERT : $selectedScenario;

            if ($targetAudience === 'single') {
                $payload = build_test_email_payload($conn, $selectedScenario, 'ICA Tracker Member', (int)$_SESSION['user_id'], $otherReasonText);
                $sent = send_notification_email($recipientEmail, $effectiveScenario, $payload);
                if ($sent) {
                    $sentCount = 1;
                } else {
                    $failedCount = 1;
                }
            } else {
                $targets = fetch_mail_targets($conn, $targetAudience, $targetClassIds);
                if (empty($targets)) {
                    $error = 'No recipients found for the selected audience.';
                } else {
                    foreach ($targets as $target) {
                        $targetMail = trim((string)($target['email'] ?? ''));
                        if ($targetMail === '') {
                            continue;
                        }
                        $targetName = trim((string)($target['name'] ?? ''));
                        $payload = build_test_email_payload($conn, $selectedScenario, $targetName, (int)$_SESSION['user_id'], $otherReasonText);
                        if (send_notification_email($targetMail, $effectiveScenario, $payload)) {
                            $sentCount++;
                        } else {
                            $failedCount++;
                        }
                    }
                }
            }

            if ($error === '') {
                $success = 'Mailing completed. Sent: ' . $sentCount . ', Failed: ' . $failedCount . '.';
                log_activity($conn, [
                    'actor_id' => (int)$_SESSION['user_id'],
                    'event_type' => 'test_email_sent',
                    'event_label' => 'Manual mailing sent',
                    'description' => 'Admin sent notification email(s) from Test Mail page.',
                    'object_type' => 'email_notification',
                    'object_id' => $selectedScenario,
                    'object_label' => $scenarioOptions[$selectedScenario],
                    'metadata' => [
                        'recipient_email' => $recipientEmail,
                        'target_audience' => $targetAudience,
                        'target_class_ids' => $targetClassIds,
                        'sent_count' => $sentCount,
                        'failed_count' => $failedCount,
                        'scenario' => $selectedScenario,
                        'other_reason_text' => $otherReasonText,
                    ],
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ]);
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
            display: block;
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
                                    <label for="target_audience">Target Audience</label>
                                    <select id="target_audience" name="target_audience" required>
                                        <option value="single" <?php echo $targetAudience === 'single' ? 'selected' : ''; ?>>Single Email</option>
                                        <option value="classes" <?php echo $targetAudience === 'classes' ? 'selected' : ''; ?>>Classes (Students)</option>
                                        <option value="faculty" <?php echo $targetAudience === 'faculty' ? 'selected' : ''; ?>>Faculty</option>
                                        <option value="program_chair" <?php echo $targetAudience === 'program_chair' ? 'selected' : ''; ?>>Program Chair</option>
                                        <option value="everyone" <?php echo $targetAudience === 'everyone' ? 'selected' : ''; ?>>Everyone</option>
                                    </select>
                                </div>

                                <div class="form-group" id="target_class_group" style="display:none;">
                                    <label for="target_class_ids">Target Classes</label>
                                    <select id="target_class_ids" name="target_class_ids[]" multiple size="6">
                                        <?php foreach ($classOptions as $classOption): ?>
                                            <option value="<?php echo (int)$classOption['id']; ?>" <?php echo in_array((int)$classOption['id'], $targetClassIds, true) ? 'selected' : ''; ?>><?php echo htmlspecialchars($classOption['label']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="inline-note">Hold Ctrl (or Cmd on Mac) to select multiple classes.</div>
                                </div>

                                <div class="form-group">
                                    <label>Recipient Preview</label>
                                    <div class="inline-note" id="recipient_preview_text">Estimated recipients: 1</div>
                                </div>

                                <div class="form-group">
                                    <label for="recipient_email">Recipient Email</label>
                                    <input type="email" id="recipient_email" name="recipient_email" value="<?php echo htmlspecialchars($recipientEmail); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="scenario">Email Scenario</label>
                                    <select id="scenario" name="scenario" required>
                                        <?php foreach ($scenarioOptions as $scenarioKey => $scenarioLabel): ?>
                                            <option value="<?php echo htmlspecialchars($scenarioKey); ?>" <?php echo $selectedScenario === $scenarioKey ? 'selected' : ''; ?>><?php echo htmlspecialchars($scenarioLabel); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group" id="other_reason_group" style="display:none;">
                                    <label for="other_reason_text">Other Reason Message</label>
                                    <textarea id="other_reason_text" name="other_reason_text" rows="4" placeholder="Type the reason/message to send to selected audience..."><?php echo htmlspecialchars($otherReasonText); ?></textarea>
                                </div>

                                <button type="submit" name="send_test_mail" class="btn">Send Test Mail</button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <script>
        (function () {
            const audience = document.getElementById('target_audience');
            const classGroup = document.getElementById('target_class_group');
            const classSelect = document.getElementById('target_class_ids');
            const recipientEmail = document.getElementById('recipient_email');
            const scenarioSelect = document.getElementById('scenario');
            const otherReasonGroup = document.getElementById('other_reason_group');
            const otherReasonText = document.getElementById('other_reason_text');
            const previewText = document.getElementById('recipient_preview_text');
            const audiencePreviewCounts = <?php echo json_encode($audiencePreviewCounts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
            const classAudienceCounts = <?php echo json_encode($classAudienceCounts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

            function updateRecipientPreview() {
                if (!previewText || !audience) {
                    return;
                }
                const value = audience.value || 'single';
                let count = 0;
                if (value === 'single') {
                    count = 1;
                } else if (value === 'classes') {
                    if (classSelect && classSelect.options) {
                        for (let i = 0; i < classSelect.options.length; i++) {
                            const option = classSelect.options[i];
                            if (option.selected) {
                                const classId = String(option.value || '');
                                count += Number(classAudienceCounts[classId] || 0);
                            }
                        }
                    }
                } else {
                    count = Number(audiencePreviewCounts[value] || 0);
                }
                previewText.textContent = 'Estimated recipients: ' + count;
            }

            function updateScenarioFields() {
                const isOtherReason = scenarioSelect && scenarioSelect.value === 'other_reason';
                if (otherReasonGroup) {
                    otherReasonGroup.style.display = isOtherReason ? 'block' : 'none';
                }
                if (otherReasonText) {
                    otherReasonText.required = !!isOtherReason;
                }
            }

            function updateAudienceFields() {
                const value = audience ? audience.value : 'single';
                const isSingle = value === 'single';
                const isClass = value === 'classes';

                if (classGroup) {
                    classGroup.style.display = isClass ? 'block' : 'none';
                }
                if (classSelect) {
                    classSelect.required = isClass;
                }
                if (recipientEmail) {
                    recipientEmail.required = isSingle;
                    recipientEmail.disabled = !isSingle;
                }
                updateRecipientPreview();
            }

            if (audience) {
                audience.addEventListener('change', updateAudienceFields);
            }
            if (classSelect) {
                classSelect.addEventListener('change', updateRecipientPreview);
            }
            if (scenarioSelect) {
                scenarioSelect.addEventListener('change', updateScenarioFields);
            }
            updateAudienceFields();
            updateScenarioFields();
        })();
    </script>
</body>
</html>
