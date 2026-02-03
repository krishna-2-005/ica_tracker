<?php
session_start();
include 'db_connect.php';
require_once __DIR__ . '/includes/activity_logger.php';

$msg = '';
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
<title>Reset Password</title>
<link rel="icon" type="image/png" href="nmimsvertical.jpg">
<link rel="apple-touch-icon" href="nmimsvertical.jpg">
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
    <h2>Reset Password</h2>
    <p class="msg"><?php echo htmlspecialchars($msg); ?></p>
    <?php if (strpos($msg, 'Invalid') === false && strpos($msg, 'expired') === false): ?>
    <form method="POST">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <label>New password</label>
        <input type="password" name="password" required>
        <label>Confirm new password</label>
        <input type="password" name="confirm_password" required>
        <button class="btn" type="submit">Set password</button>
    </form>
    <?php endif; ?>
    <p style="margin-top:12px"><a href="login.php">Back to Login</a></p>
</div>
</body>
</html>
