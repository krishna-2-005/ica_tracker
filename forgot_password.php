<?php
session_start();
include 'db_connect.php';
include_once __DIR__ . '/includes/activity_logger.php';
include_once __DIR__ . '/includes/mailer.php';

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
                            $resetLink = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/reset_password.php?token=$token";
                            $subject = 'Password reset for ICA Tracker';
                            $body = "<p>Hello {$user['name']},</p><p>Use the following link to reset your password (valid for 1 hour):</p><p><a href='" . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . "'>Reset Password</a></p><p>If you did not request this, you can safely ignore this email.</p>";

                            $registeredEmail = trim((string)($user['email'] ?? ''));
                            $emailSent = false;
                            $emailError = '';

                            if ($registeredEmail === '') {
                                $emailError = 'No email on record.';
                            } else {
                                try {
                                    $emailSent = send_app_mail($registeredEmail, $subject, $body);
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
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - ICA Tracker</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;background:#f7f8fb;padding:40px}
        .card{max-width:480px;margin:0 auto;background:#fff;padding:28px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.06)}
        input{width:100%;padding:12px;margin:8px 0;border-radius:8px;border:1px solid #ddd}
        .btn{background:#BA0C2F;color:#fff;padding:10px 14px;border-radius:8px;border:none;cursor:pointer}
        .msg{margin:12px 0;color:#333}
    </style>
</head>
<body>
<div class="card">
    <h2>Forgot Password</h2>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <p class="msg"><?php echo htmlspecialchars($message); ?></p>
    <form method="POST">
        <label>ID (Teacher unique ID or SAP ID)</label>
        <input type="text" name="identifier" required>
        <label>Email (optional)</label>
        <input type="email" name="email" placeholder="Registered email if available">
        <button class="btn" type="submit">Send Reset Link</button>
    </form>
    <p style="margin-top:12px"><a href="login.php">Back to Login</a></p>
</div>
</body>
</html>
