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
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - ICA Tracker</title>
<link rel="icon" type="image/png" href="nmimsvertical.jpg">
<link rel="apple-touch-icon" href="nmimsvertical.jpg">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600;700&display=swap');

    :root {
        --brand: #A6192E;
        --brand-dark: #7f1422;
        --ink: #2c3e50;
        --muted: #63666A;
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

    .actions {
        margin-top: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .btn {
        height: 46px;
        border: none;
        border-radius: 24px;
        background: var(--brand);
        color: #ffffff;
        font-size: 0.96rem;
        font-weight: 700;
        cursor: pointer;
        transition: background 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
        padding: 0 24px;
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

    .link {
        color: var(--brand);
        font-weight: 600;
        text-decoration: none;
    }

    .link:hover {
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
                <h2>Reset Password</h2>
                <p>Create a new secure password to continue using ICA Tracker.</p>
            </div>

            <?php
            $hasMessage = trim($msg) !== '';
            $isSuccessMessage = $hasMessage && (
                stripos($msg, 'success') !== false ||
                stripos($msg, 'updated') !== false
            ) && stripos($msg, 'failed') === false;
            ?>

            <?php if ($hasMessage): ?>
                <p class="msg<?php echo $isSuccessMessage ? ' success' : ''; ?>"><?php echo htmlspecialchars($msg); ?></p>
            <?php endif; ?>

            <?php if ($canReset): ?>
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="field">
                        <label for="password">New password</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="field">
                        <label for="confirm_password">Confirm new password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <div class="actions">
                        <button class="btn" type="submit">Set Password</button>
                        <a class="link" href="login.php">Back to Login</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="actions">
                    <a class="link" href="forgot_password.php">Request New Reset Link</a>
                    <a class="link" href="login.php">Back to Login</a>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <div class="footer-bottom">
        &copy; <?php echo date('Y'); ?> Kuchuru Sai Krishna Reddy - STME. All rights reserved.
    </div>
</body>
</html>
