<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/activity_logger.php';

$msg = '';
$canReset = false;
$token = $_GET['token'] ?? ($_POST['token'] ?? '');
if ($token === '') {
    $msg = 'Invalid or missing token.';
} else {
    // validate token
    $q = mysqli_prepare($conn, "SELECT pr.id as rid, pr.user_id, pr.expires_at, u.name FROM password_resets pr LEFT JOIN users u ON pr.user_id = u.id WHERE pr.token = ? LIMIT 1");
    if ($q) {
        mysqli_stmt_bind_param($q, 's', $token);
        mysqli_stmt_execute($q);
        $res = mysqli_stmt_get_result($q);
        if ($res && mysqli_num_rows($res) === 1) {
            $row = mysqli_fetch_assoc($res);
            if (strtotime($row['expires_at']) < time()) {
                $msg = 'Token expired.';
            } else {
                $canReset = true;
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $p1 = $_POST['password'] ?? '';
                    $p2 = $_POST['confirm_password'] ?? '';
                    if ($p1 === '' || $p2 === '') {
                        $msg = 'Please fill both fields.';
                    } elseif ($p1 !== $p2) {
                        $msg = 'Passwords do not match.';
                    } else {
                        $hash = password_hash($p1, PASSWORD_DEFAULT);
                        $upd = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
                        if ($upd) {
                            mysqli_stmt_bind_param($upd, 'si', $hash, $row['user_id']);
                            if (mysqli_stmt_execute($upd)) {
                                // delete all tokens for this user
                                $del = mysqli_prepare($conn, "DELETE FROM password_resets WHERE user_id = ?");
                                if ($del) { mysqli_stmt_bind_param($del, 'i', $row['user_id']); mysqli_stmt_execute($del); mysqli_stmt_close($del); }
                                log_activity($conn, (int) $row['user_id'], 'Password reset completed via emailed reset link');
                                mysqli_stmt_close($upd);
                                mysqli_close($conn);
                                header('Location: login.php');
                                exit;
                            } else {
                                $msg = 'Failed to update password.';
                            }
                        }
                    }
                }
            }
        } else {
            $msg = 'Invalid token.';
        }
        mysqli_stmt_close($q);
    } else {
        $msg = 'Failed to validate token.';
    }
}
mysqli_close($conn);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password</title>
<link rel="icon" type="image/png" href="nmimsvertical.jpg">
<link rel="apple-touch-icon" href="nmimsvertical.jpg">
<link rel="stylesheet" href="ica_tracker.css">
<style>
body { min-height: 100vh; }
.reset-shell {
    max-width: 560px;
    margin: 48px auto;
}
.reset-card {
    background: #fff;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 8px 26px rgba(0, 0, 0, 0.1);
}
.reset-title {
    font-size: 1.9rem;
    color: #A6192E;
    margin-bottom: 12px;
}
.msg {
    margin: 12px 0 18px;
    color: #34495e;
    font-weight: 600;
}
.reset-form label {
    margin-top: 10px;
}
.reset-actions {
    margin-top: 14px;
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}
.back-link {
    color: #A6192E;
    font-weight: 600;
    text-decoration: none;
}
.back-link:hover {
    text-decoration: underline;
}
</style>
</head>
<body>
<div class="reset-shell">
    <div class="reset-card">
        <h2 class="reset-title">Reset Password</h2>
        <p class="msg"><?php echo htmlspecialchars($msg); ?></p>
        <?php if ($canReset): ?>
        <form method="POST" class="reset-form">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <label>New password</label>
            <input type="password" name="password" required>
            <label>Confirm new password</label>
            <input type="password" name="confirm_password" required>
            <div class="reset-actions">
                <button class="btn" type="submit">Set Password</button>
                <a class="back-link" href="login.php">Back to Login</a>
            </div>
        </form>
        <?php else: ?>
        <div class="reset-actions">
            <a class="back-link" href="forgot_password.php">Request New Reset Link</a>
            <a class="back-link" href="login.php">Back to Login</a>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
