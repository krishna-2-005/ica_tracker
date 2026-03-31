<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/activity_logger.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $plainPassword = $_POST['password'] ?? '';

    if ($identifier === '' || $plainPassword === '') {
        $error = 'Please enter both your ID and password.';
    } else {
    $username = mysqli_real_escape_string($conn, $identifier);

        // Fetch a matching user by teacher_unique_id or username (sap_id)
        $query = "SELECT id, username, password, role, name, status FROM users WHERE teacher_unique_id = ? OR username = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $username, $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            $result = false;
        }

        $user = null;
        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);
        }

        // If user found, verify password (support bcrypt via password_verify, else fallback to md5)
        if ($user) {
            $stored = $user['password'];
            $verified = false;
            if (strlen($stored) > 50 && (password_needs_rehash($stored, PASSWORD_DEFAULT) === false || substr($stored,0,1) === '$')) {
                // Likely a password_hash() value
                $verified = password_verify($plainPassword, $stored);
            } else {
                // Fallback to MD5 compare (legacy)
                if (md5($plainPassword) === $stored) {
                    $verified = true;
                }
            }

            if ($verified) {
                // If legacy MD5, rehash to password_hash
                if (!password_verify($plainPassword, $stored)) {
                    $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
                    $upd = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
                    if ($upd) {
                        mysqli_stmt_bind_param($upd, 'si', $newHash, $user['id']);
                        mysqli_stmt_execute($upd);
                        mysqli_stmt_close($upd);
                    }
                }

                $studentRequiresCollegeEmail = false;
                $studentProfileId = null;
                if ($user['role'] === 'student') {
                    $collegeStmt = mysqli_prepare($conn, "SELECT id, college_email FROM students WHERE sap_id = ? LIMIT 1");
                    if ($collegeStmt) {
                        mysqli_stmt_bind_param($collegeStmt, 's', $user['username']);
                        mysqli_stmt_execute($collegeStmt);
                        $collegeResult = mysqli_stmt_get_result($collegeStmt);
                        if ($collegeResult && mysqli_num_rows($collegeResult) === 1) {
                            $collegeRow = mysqli_fetch_assoc($collegeResult);
                            $studentProfileId = (int)($collegeRow['id'] ?? 0);
                            $studentRequiresCollegeEmail = empty(trim((string)($collegeRow['college_email'] ?? '')));
                        } else {
                            $studentRequiresCollegeEmail = true;
                        }
                        if ($collegeResult) {
                            mysqli_free_result($collegeResult);
                        }
                        mysqli_stmt_close($collegeStmt);
                    } else {
                        $studentRequiresCollegeEmail = true;
                    }
                }

                if (isset($user['status']) && $user['status'] === 'inactive') {
                    $error = 'Your account is deactivated.';
                } elseif ($user['role'] === 'admin' || $user['role'] === 'system_admin') {
                    $error = 'Please use the dedicated admin login page.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['unique_id'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['role'] = $user['role'];
                    if ($studentProfileId !== null) {
                        $_SESSION['student_profile_id'] = $studentProfileId;
                    } else {
                        unset($_SESSION['student_profile_id']);
                    }

                    $default = '123456';
                    $destination = 'login.php';
                    if ($user['role'] === 'student') {
                        $destination = 'student_dashboard.php';
                    } elseif ($user['role'] === 'teacher') {
                        $destination = 'teacher_dashboard.php';
                    } elseif ($user['role'] === 'program_chair') {
                        $destination = 'login_as.php';
                        $_SESSION['role'] = null;
                    }

                    $_SESSION['post_login_redirect'] = $destination;
                    // Set force change if current password matches default (support both hash types)
                    $isDefault = password_verify($default, $user['password']) || md5($default) === $user['password'];
                    $needsEmailUpdate = !empty($studentRequiresCollegeEmail);
                    $_SESSION['require_college_email'] = $needsEmailUpdate;
                    $_SESSION['force_password_change'] = $isDefault || $needsEmailUpdate;

                    log_activity($conn, [
                        'actor_id' => (int)$user['id'],
                        'event_type' => 'login_success',
                        'event_label' => 'User logged in',
                        'description' => 'Successful login via main portal.',
                        'metadata' => [
                            'identifier_used' => $identifier,
                            'role' => $user['role'],
                            'post_login_redirect' => $destination,
                            'session_id' => session_id(),
                            'force_password_change' => !empty($_SESSION['force_password_change']),
                            'student_profile_id' => $studentProfileId,
                            'require_college_email' => $studentRequiresCollegeEmail,
                        ],
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    ]);

                    if (!empty($_SESSION['force_password_change'])) {
                        header('Location: change_password.php');
                        mysqli_stmt_close($stmt);
                        mysqli_close($conn);
                        exit;
                    }

                    if ($destination === 'login_as.php') {
                        header('Location: login_as.php');
                    } else {
                        header("Location: $destination");
                    }
                    mysqli_stmt_close($stmt);
                    mysqli_close($conn);
                    exit;
                }
            } else {
                $error = 'Invalid ID or password!';
            }
        } else {
            // No user found: check students table for sap_id. If student exists, auto-create a user and force password change.
            $student_stmt = mysqli_prepare($conn, "SELECT id, sap_id, name FROM students WHERE sap_id = ? LIMIT 1");
            if ($student_stmt) {
                mysqli_stmt_bind_param($student_stmt, "s", $username);
                mysqli_stmt_execute($student_stmt);
                $student_res = mysqli_stmt_get_result($student_stmt);
                if ($student_res && mysqli_num_rows($student_res) === 1) {
                    $stu = mysqli_fetch_assoc($student_res);
                    mysqli_stmt_close($student_stmt);

                    // Check if a users row already exists for this sap_id
                    $ucheck = mysqli_prepare($conn, "SELECT id, password FROM users WHERE username = ? LIMIT 1");
                    if ($ucheck) {
                        mysqli_stmt_bind_param($ucheck, 's', $stu['sap_id']);
                        mysqli_stmt_execute($ucheck);
                        $u_res = mysqli_stmt_get_result($ucheck);
                        if ($u_res && mysqli_num_rows($u_res) === 1) {
                            // user exists unexpectedly, attempt verification
                            $exist = mysqli_fetch_assoc($u_res);
                            mysqli_stmt_close($ucheck);
                            if (password_verify($plainPassword, $exist['password']) || md5($plainPassword) === $exist['password']) {
                                // re-run login by redirecting to self so normal flow handles session (simple approach)
                                header('Location: login.php');
                                exit;
                            } else {
                                $error = 'Invalid ID or password!';
                            }
                        } else {
                            mysqli_stmt_close($ucheck);
                            // Create a new user account with default password
                            $defaultPass = '123456';
                            $hashed = password_hash($defaultPass, PASSWORD_DEFAULT);
                            $insert = mysqli_prepare($conn, "INSERT INTO users (username, password, role, name, status) VALUES (?, ?, 'student', ?, 'active')");
                            if ($insert) {
                                mysqli_stmt_bind_param($insert, 'sss', $stu['sap_id'], $hashed, $stu['name']);
                                if (mysqli_stmt_execute($insert)) {
                                    $newId = mysqli_insert_id($conn);
                                    mysqli_stmt_close($insert);
                                    // Log them in and force password change
                                    $_SESSION['user_id'] = (int)$newId;
                                    $_SESSION['name'] = $stu['name'];
                                    $_SESSION['unique_id'] = $stu['sap_id'];
                                    $_SESSION['user_role'] = 'student';
                                    $_SESSION['role'] = 'student';
                                    $_SESSION['post_login_redirect'] = 'student_dashboard.php';
                                    $_SESSION['force_password_change'] = true;
                                    $_SESSION['require_college_email'] = true;

                                    log_activity($conn, [
                                        'actor_id' => (int)$newId,
                                        'event_type' => 'login_success_auto_account',
                                        'event_label' => 'Auto-created student login',
                                        'description' => 'Student account generated from SAP sign-in and login initiated.',
                                        'metadata' => [
                                            'identifier_used' => $identifier,
                                            'session_id' => session_id(),
                                            'auto_account_created' => true,
                                            'student_record_id' => $stu['id'],
                                        ],
                                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                                    ]);
                                    header('Location: change_password.php');
                                    mysqli_close($conn);
                                    exit;
                                } else {
                                    $error = 'Failed to create user account. Contact admin.';
                                }
                            } else {
                                $error = 'Failed to prepare user creation. Contact admin.';
                            }
                        }
                    } else {
                        $error = 'Failed to check user account. Contact admin.';
                    }
                } else {
                    if ($student_stmt) { mysqli_stmt_close($student_stmt); }
                    $error = 'Invalid ID or password!';
                }
            } else {
                $error = 'Invalid ID or password!';
            }
        }

        if ($stmt) {
            mysqli_stmt_close($stmt);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error !== '') {
    log_activity($conn, [
        'event_type' => 'login_failed',
        'event_label' => 'Portal login failed',
        'description' => 'Failed authentication attempt on main login.',
        'metadata' => [
            'identifier_used' => isset($identifier) ? $identifier : null,
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
    <title>College Login - ICA Tracker</title>
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

        .brand-panel::before,
        .brand-panel::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle at center, rgba(166, 25, 46, 0.12) 0%, rgba(166, 25, 46, 0) 70%);
            filter: blur(1px);
        }

        .brand-panel::before {
            width: 280px;
            height: 280px;
            top: -120px;
            right: -90px;
        }

        .brand-panel::after {
            width: 240px;
            height: 240px;
            bottom: -110px;
            left: -100px;
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
                    <span class="brand-tag">NMIMS ICA Portal</span>
                    <h1>Welcome to NMIMS University</h1>
                    <p class="brand-copy">A single place for students and faculty to manage internal assessments, coursework progress, and academic updates with clarity.</p>

                    <ul class="brand-points">
                        <li>Track ICA components, marks, and overall progress in one place.</li>
                        <li>Receive alerts quickly to avoid missing key academic deadlines.</li>
                        <li>Access faculty and student workflows through role-based dashboards.</li>
                    </ul>

                    <div class="quick-actions">
                        <a href="http://localhost/ica_tracker/index.php#contact">Contact</a>
                        <a href="index.php">Portal Home</a>
                    </div>

                    <p class="left-meta">For support, contact your department coordinator or the portal administrator.</p>
                </div>
            </section>

            <section class="login-panel">
                <div class="login-card">
                    <div class="logo-wrap">
                        <img src="nmimslogo.png" alt="NMIMS Logo">
                    </div>

                    <div class="login-head">
                        <h2>Sign In to Continue</h2>
                        <p>Use your official SAP ID or Faculty ID to access your dashboard.</p>
                    </div>

                    <div class="login-error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>

                    <form id="loginForm" method="POST">
                        <div class="field">
                            <label for="identifier">ID (SAP ID / Faculty ID)</label>
                            <input type="text" id="identifier" name="identifier" required>
                        </div>

                        <div class="field">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>

                        <button type="submit" class="btn-login">Login</button>

                        <p class="trust-text">Secure academic access for students, faculty, and coordinators.</p>

                        <div class="signup-link">
                            <a href="forgot_password.php">Forgot Password?</a>
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
