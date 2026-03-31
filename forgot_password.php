<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
include_once __DIR__ . '/includes/activity_logger.php';
include_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/email_notifications.php';

// Ensure password_resets table exists
$create_table = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    token VARCHAR(128) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX(token(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $create_table);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($identifier === '') {
        $message = 'Please enter your ID or SAP ID.';
    } else {
        // try find user by teacher_unique_id or username
        $q = mysqli_prepare($conn, "SELECT id, username, name, role, email FROM users WHERE teacher_unique_id = ? OR username = ? LIMIT 1");
        if ($q) {
            mysqli_stmt_bind_param($q, 'ss', $identifier, $identifier);
            mysqli_stmt_execute($q);
            $res = mysqli_stmt_get_result($q);
            if ($res && mysqli_num_rows($res) === 1) {
                $user = mysqli_fetch_assoc($res);
                // optional: check email if provided
                if ($email !== '' && (!isset($user['email']) || stripos($user['email'], $email) === false)) {
                    $message = 'Provided email does not match our records.';
                } else {
                    // generate token
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', time() + 60*60); // 1 hour
                    $ins = mysqli_prepare($conn, "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                    if ($ins) {
                        mysqli_stmt_bind_param($ins, 'iss', $user['id'], $token, $expires);
                        if (mysqli_stmt_execute($ins)) {
                            $resetLink = email_notification_app_url() . '/reset_password.php?token=' . rawurlencode($token);
                            $recipientName = isset($user['name']) ? trim((string)$user['name']) : '';
                            $recipientDisplay = $recipientName !== '' ? format_person_display($recipientName) : 'ICA Tracker User';
                            $expiryDisplay = date('d M Y, h:i A', strtotime($expires));

                            $registeredEmail = trim((string)($user['email'] ?? ''));
                            $emailSent = false;
                            $emailError = '';

                            if ($registeredEmail === '') {
                                $emailError = 'No email on record.';
                            } else {
                                try {
                                    $emailSent = send_notification_email($registeredEmail, EMAIL_SCENARIO_PASSWORD_RESET, [
                                        'recipient_name' => $recipientDisplay,
                                        'reset_link' => $resetLink,
                                        'link_expires_at' => '1 hour (expires ' . $expiryDisplay . ')',
                                    ]);
                                    if (!$emailSent) {
                                        $emailError = 'Mailer returned no success flag.';
                                    }
                                } catch (Throwable $e) {
                                    $emailError = $e->getMessage();
                                    error_log('Failed to send reset email: ' . $emailError);
                                }
                            }

                            log_activity($conn, (int)$user['id'], 'password_reset_requested', 'Password reset link generated.', (int)$user['id']);

                            if ($emailSent) {
                                log_activity($conn, (int)$user['id'], 'password_reset_email_sent', 'Reset email dispatched.', (int)$user['id']);
                                $message = 'Reset link sent to your registered email address.';
                            } else {
                                if ($emailError !== '') {
                                    log_activity($conn, (int)$user['id'], 'password_reset_email_failed', $emailError, (int)$user['id']);
                                }
                                $message = $registeredEmail === ''
                                    ? 'Reset link created, but no email is stored for this account. Please contact the administrator to finish the reset.'
                                    : 'Reset link created, but the email could not be sent. Please contact the administrator.';
                            }
                        } else {
                            $message = 'Failed to create reset token. Try again later.';
                        }
                        mysqli_stmt_close($ins);
                    } else {
                        $message = 'Failed to prepare reset. Try again later.';
                    }
                }
            } else {
                $message = 'No account found with that ID.';
            }
            mysqli_stmt_close($q);
        } else {
            $message = 'Failed to process request.';
        }
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600;700&display=swap');

        :root {
            --brand: #A6192E;
            --brand-dark: #7f1422;
            --ink: #2c3e50;
            --muted: #63666A;
            --panel: #ffffff;
            --line: #dbe1e8;
            --soft-bg: #f5f7fb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #eef2f7 0%, #d9e1ec 100%);
            color: var(--ink);
            display: flex;
            flex-direction: column;
        }

        .auth-layout {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 26px 16px;
        }

        .reset-card {
            width: min(520px, 100%);
            background: radial-gradient(circle at 90% 8%, rgba(166, 25, 46, 0.09) 0%, rgba(166, 25, 46, 0) 42%), var(--soft-bg);
            border: 1px solid rgba(150, 163, 181, 0.3);
            border-radius: 16px;
            box-shadow: 0 16px 34px rgba(21, 33, 50, 0.16);
            padding: 28px 24px 22px;
        }

        .logo-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 10px;
        }

        .logo-wrap img {
            width: auto;
            height: 80px;
            object-fit: contain;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.12));
        }

        .head {
            text-align: center;
            margin-bottom: 18px;
        }

        .head h2 {
            color: var(--brand);
            font-size: 2rem;
            margin-bottom: 6px;
        }

        .head p {
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .msg {
            border: 1px solid #f3c1c7;
            background: #fdeff1;
            color: #8a1726;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 14px;
            font-size: 0.86rem;
            line-height: 1.4;
            display: <?php echo $message !== '' ? 'block' : 'none'; ?>;
        }

        .msg.success {
            border-color: #b9e3c9;
            background: #edf9f1;
            color: #256f45;
        }

        .field {
            margin-bottom: 14px;
        }

        .field label {
            display: block;
            margin-bottom: 6px;
            color: #374151;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .field input {
            width: 100%;
            height: 48px;
            border-radius: 8px;
            border: 1.5px solid var(--line);
            background: #fbfcfe;
            padding: 0 13px;
            font-size: 0.95rem;
            color: var(--ink);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .field input:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(166, 25, 46, 0.16);
            outline: none;
            background: #ffffff;
        }

        .btn {
            width: 100%;
            height: 48px;
            border: none;
            border-radius: 24px;
            background: var(--brand);
            color: #ffffff;
            font-size: 0.98rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
            margin-top: 2px;
        }

        .btn:hover {
            background: var(--brand-dark);
            box-shadow: 0 8px 20px rgba(166, 25, 46, 0.28);
            transform: translateY(-1px);
        }

        .btn:active {
            transform: translateY(0);
            box-shadow: none;
        }

        .help-row {
            margin-top: 14px;
            text-align: center;
        }

        .help-row a {
            color: var(--brand);
            font-weight: 600;
            text-decoration: none;
            font-size: 0.92rem;
        }

        .help-row a:hover {
            color: var(--brand-dark);
            text-decoration: underline;
        }

        .footer-bottom {
            padding: 11px 14px;
            background: #333333;
            color: rgba(255, 255, 255, 0.82);
            text-align: center;
            font-size: 0.78rem;
            border-top: 1px solid rgba(255, 255, 255, 0.14);
        }

        @media (max-width: 520px) {
            .reset-card {
                padding: 22px 14px 20px;
                border-radius: 13px;
            }

            .logo-wrap img {
                height: 66px;
            }

            .head h2 {
                font-size: 1.55rem;
            }

            .head p {
                font-size: 0.9rem;
            }

            .field input,
            .btn {
                height: 46px;
            }

            .footer-bottom {
                font-size: 0.73rem;
            }
        }
    </style>
</head>
<body>
    <main class="auth-layout">
        <section class="reset-card">
            <div class="logo-wrap">
                <img src="nmimslogo.png" alt="NMIMS Logo">
            </div>

            <div class="head">
                <h2>Forgot Password</h2>
                <p>Enter your ID and we will send a secure reset link to your registered email.</p>
            </div>

            <?php
            $isSuccessMessage = $message !== '' && (
                stripos($message, 'sent') !== false ||
                stripos($message, 'created') !== false
            ) && stripos($message, 'failed') === false;
            ?>
            <p class="msg<?php echo $isSuccessMessage ? ' success' : ''; ?>"><?php echo htmlspecialchars($message); ?></p>

            <form method="POST" autocomplete="off">
                <div class="field">
                    <label for="identifier">ID (Teacher Unique ID or SAP ID)</label>
                    <input type="text" id="identifier" name="identifier" required>
                </div>

                <div class="field">
                    <label for="email">Email (optional)</label>
                    <input type="email" id="email" name="email" placeholder="Registered email if available">
                </div>

                <button class="btn" type="submit">Send Reset Link</button>
            </form>

            <p class="help-row"><a href="login.php">Back to Login</a></p>
        </section>
    </main>

    <div class="footer-bottom">
        &copy; <?php echo date('Y'); ?> Kuchuru Sai Krishna Reddy - STME. All rights reserved.
    </div>
</body>
</html>
