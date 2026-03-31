<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/activity_logger.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminId = trim($_POST['admin_id'] ?? '');
    $plainPassword = $_POST['password'] ?? '';

    if ($adminId === '' || $plainPassword === '') {
        $error = 'Please enter your Admin ID and password.';
    } else {
        $username = mysqli_real_escape_string($conn, $adminId);

        $query = "SELECT id, username, password, role, name, status, teacher_unique_id FROM users WHERE role IN ('admin', 'system_admin') AND (username = ? OR teacher_unique_id = ?) LIMIT 1";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ss', $username, $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            $result = false;
        }

        if ($result && mysqli_num_rows($result) === 1) {
            $admin = mysqli_fetch_assoc($result);
            $stored = $admin['password'];
            $verified = false;
            if (strlen($stored) > 50 || substr($stored,0,1) === '$') {
                $verified = password_verify($plainPassword, $stored);
            } else {
                if (md5($plainPassword) === $stored) { $verified = true; }
            }

            if ($verified) {
                // migrate legacy md5
                if (!password_verify($plainPassword, $stored)) {
                    $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
                    $upd = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
                    if ($upd) { mysqli_stmt_bind_param($upd, 'si', $newHash, $admin['id']); mysqli_stmt_execute($upd); mysqli_stmt_close($upd); }
                }

                if (isset($admin['status']) && $admin['status'] === 'inactive') {
                    $error = 'Your administrator account has been deactivated.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int)$admin['id'];
                    $_SESSION['name'] = $admin['name'];
                    $_SESSION['unique_id'] = $admin['username'];
                    $_SESSION['user_role'] = $admin['role'];
                    $_SESSION['role'] = $admin['role'];

                    $postLoginRedirect = ($admin['role'] === 'system_admin') ? 'system_admin_dashboard.php' : 'admin_dashboard.php';
                    $_SESSION['post_login_redirect'] = $postLoginRedirect;
                    $_SESSION['force_password_change'] = password_verify('123456', $admin['password']) || md5('123456') === $admin['password'];

                    log_activity($conn, [
                        'actor_id' => (int)$admin['id'],
                        'event_type' => 'admin_login_success',
                        'event_label' => $admin['role'] === 'system_admin' ? 'System Administrator logged in' : 'Administrator logged in',
                        'description' => 'Admin portal authentication succeeded.',
                        'metadata' => [
                            'identifier_used' => $adminId,
                            'role' => $admin['role'],
                            'teacher_unique_id' => $admin['teacher_unique_id'] ?? null,
                            'session_id' => session_id(),
                            'force_password_change' => !empty($_SESSION['force_password_change']),
                            'post_login_redirect' => $postLoginRedirect,
                        ],
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    ]);

                    if (!empty($_SESSION['force_password_change'])) {
                        header('Location: change_password.php');
                    } else {
                        header('Location: ' . $postLoginRedirect);
                    }

                    mysqli_stmt_close($stmt);
                    mysqli_close($conn);
                    exit;
                }
            } else {
                $error = 'Invalid Admin ID or password!';
            }
        } else {
            $error = 'Invalid Admin ID or password!';
        }

        if ($stmt) { mysqli_stmt_close($stmt); }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error !== '') {
    log_activity($conn, [
        'event_type' => 'admin_login_failed',
        'event_label' => 'Admin portal login failed',
        'description' => 'Failed authentication attempt on admin login.',
        'metadata' => [
            'identifier_used' => isset($adminId) ? $adminId : null,
            'error' => $error,
        ],
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - ICA Tracker</title>
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
            min-height: calc(100vh - 56px);
            padding: 26px 18px;
        }

        .auth-shell {
            width: min(1120px, 100%);
            min-height: 620px;
            display: grid;
            grid-template-columns: 1.65fr 1fr;
            background: rgba(245, 248, 252, 0.78);
            border: 1px solid rgba(150, 163, 181, 0.32);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 22px 46px rgba(18, 30, 45, 0.2);
            backdrop-filter: blur(2px);
        }

        .brand-panel {
            position: relative;
            padding: 40px 38px 34px;
            background:
                linear-gradient(0deg, rgba(28, 33, 41, 0.88), rgba(28, 33, 41, 0.88)),
                radial-gradient(circle at 15% 20%, rgba(255, 255, 255, 0.16) 0%, rgba(255, 255, 255, 0) 52%),
                linear-gradient(145deg, #778192 0%, #9fa9ba 100%);
            color: #f5f7fb;
            display: flex;
            align-items: center;
        }

        .brand-inner {
            position: relative;
            z-index: 1;
            max-width: 540px;
        }

        .brand-tag {
            display: inline-block;
            padding: 7px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.22);
            color: #f5f7fb;
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            margin-bottom: 22px;
        }

        .brand-panel h1 {
            color: #ffffff;
            font-size: 2.15rem;
            line-height: 1.2;
            margin-bottom: 14px;
        }

        .brand-panel p {
            color: rgba(240, 244, 250, 0.9);
            font-size: 0.98rem;
            line-height: 1.6;
            margin-bottom: 18px;
        }

        .brand-copy {
            margin-bottom: 22px;
        }

        .brand-points {
            list-style: none;
            display: grid;
            gap: 12px;
            margin-bottom: 16px;
        }

        .brand-points li {
            padding: 11px 13px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-left: 4px solid #d8dde7;
            color: #f7f9fc;
            font-size: 0.9rem;
        }

        .quick-actions {
            margin-top: 22px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .quick-actions a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 140px;
            padding: 9px 12px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            background: var(--brand);
            color: #ffffff;
            text-decoration: none;
            font-size: 0.78rem;
            font-weight: 700;
            transition: background 0.2s ease, transform 0.15s ease;
        }

        .quick-actions a:hover {
            background: #8f1425;
            transform: translateY(-1px);
        }

        .left-meta {
            margin-top: 18px;
            border-top: 1px solid rgba(255, 255, 255, 0.22);
            padding-top: 14px;
            font-size: 0.86rem;
            color: rgba(233, 238, 246, 0.9);
            line-height: 1.5;
        }

        .login-panel {
            width: 100%;
            max-width: none;
            padding: 32px 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 90% 10%, rgba(166, 25, 46, 0.10) 0%, rgba(166, 25, 46, 0) 48%), var(--soft-bg);
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: transparent;
            border-radius: 0;
            border: none;
            box-shadow: none;
            padding: 0;
        }

        .logo-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 10px;
        }

        .logo-wrap img {
            width: auto;
            height: 78px;
            object-fit: contain;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.14));
        }

        .login-head {
            text-align: center;
            margin-bottom: 20px;
        }

        .login-head h2 {
            color: var(--brand);
            font-size: 1.85rem;
            margin-bottom: 6px;
        }

        .login-head p {
            color: var(--muted);
            font-size: 0.9rem;
        }

        .login-error-message {
            border: 1px solid #f3c1c7;
            background: #fdeff1;
            color: #8a1726;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 14px;
            font-size: 0.86rem;
            line-height: 1.4;
            display: <?php echo $error ? 'block' : 'none'; ?>;
        }

        .field {
            margin-bottom: 14px;
        }

        .field label {
            display: block;
            margin-bottom: 6px;
            color: #374151;
            font-size: 0.86rem;
            font-weight: 600;
        }

        .field input {
            width: 100%;
            height: 48px;
            border-radius: 8px;
            border: 1.5px solid var(--line);
            background: #fbfcfe;
            padding: 0 13px;
            font-size: 0.94rem;
            color: var(--ink);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .field input:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(166, 25, 46, 0.16);
            outline: none;
            background: #ffffff;
        }

        .btn-login {
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
            margin-top: 4px;
        }

        .btn-login:hover {
            background: var(--brand-dark);
            box-shadow: 0 8px 20px rgba(166, 25, 46, 0.28);
            transform: translateY(-1px);
        }

        .btn-login:active {
            transform: translateY(0);
            box-shadow: none;
        }

        .trust-text {
            margin-top: 10px;
            text-align: center;
            color: #6b7280;
            font-size: 0.78rem;
        }

        .signup-link {
            text-align: center;
            margin-top: 14px;
        }

        .signup-link a {
            color: var(--brand);
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
        }

        .signup-link a:hover {
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

        @media (max-width: 980px) {
            .auth-shell {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .brand-panel {
                padding: 26px 20px 22px;
            }

            .login-panel {
                padding: 22px 16px 24px;
            }
        }

        @media (max-width: 520px) {
            .brand-panel {
                padding: 24px 16px;
            }

            .brand-tag {
                margin-bottom: 14px;
            }

            .brand-panel h1 {
                font-size: 1.5rem;
                margin-bottom: 10px;
            }

            .brand-panel p {
                font-size: 0.9rem;
                margin-bottom: 16px;
            }

            .brand-points li {
                font-size: 0.84rem;
                padding: 10px 11px;
            }

            .quick-actions {
                gap: 8px;
            }

            .quick-actions a {
                min-width: 128px;
                font-size: 0.75rem;
            }

            .login-card {
                padding: 0;
                border-radius: 0;
            }

            .logo-wrap img {
                height: 66px;
            }

            .login-head h2 {
                font-size: 1.3rem;
            }

            .field input,
            .btn-login {
                height: 46px;
            }

            .footer-bottom {
                font-size: 0.73rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-layout">
        <div class="auth-shell">
            <section class="brand-panel">
                <div class="brand-inner">
                    <span class="brand-tag">NMIMS Administration Portal</span>
                    <h1>Administrator Access</h1>
                    <p class="brand-copy">Manage faculty operations, class configuration, reporting, and institutional workflows from one secure console.</p>

                    <ul class="brand-points">
                        <li>Control teacher, class, and subject setup across departments.</li>
                        <li>Monitor academic progress, reports, and communication workflows.</li>
                        <li>Access sensitive controls through role-protected administrator sign in.</li>
                    </ul>

                    <div class="quick-actions">
                        <a href="login.php">Faculty/Student Login</a>
                        <a href="index.php">Portal Home</a>
                    </div>

                    <p class="left-meta">Use only authorized administrator credentials. All access attempts are securely logged.</p>
                </div>
            </section>

            <section class="login-panel">
                <div class="login-card">
                    <div class="logo-wrap">
                        <img src="nmimslogo.png" alt="NMIMS Logo">
                    </div>

                    <div class="login-head">
                        <h2>Admin Sign In</h2>
                        <p>Restricted access. Use your Admin ID to continue.</p>
                    </div>

                    <div class="login-error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>

                    <form method="POST" autocomplete="off">
                        <div class="field">
                            <label for="admin_id">Admin ID</label>
                            <input type="text" id="admin_id" name="admin_id" required>
                        </div>

                        <div class="field">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>

                        <button type="submit" class="btn-login">Login</button>

                        <p class="trust-text">Administrator-only area. Unauthorized access is prohibited.</p>

                        <div class="signup-link">
                            <a href="login.php">Go back to standard login</a>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>

    <div class="footer-bottom">
        &copy; <?php echo date("Y"); ?> Kuchuru Sai Krishna Reddy – STME. All rights reserved.
    </div>
</body>
</html>

