<?php
session_start();
include 'db_connect.php';
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
                } elseif ($user['role'] === 'admin') {
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
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        :root {
            --primary: #BA0C2F;
            --secondary: #63666A;
            --dark: #2C2A29;
            --light: #f8f9fa;
        }

        html, body {
            height: 100%;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--light);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            height: 100vh;
            overflow-x: hidden;
            overflow-y: hidden;
            background-image:
                radial-gradient(circle at 10% 20%, rgba(186, 12, 47, 0.05) 0%, rgba(186, 12, 47, 0.05) 90%),
                radial-gradient(circle at 90% 80%, rgba(99, 102, 106, 0.05) 0%, rgba(99, 102, 106, 0.05) 90%);
        }

        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 24px;
            width: 100%;
            padding: 24px 16px;
            min-height: 0;
        }

        .floating-logo {
            position: static;
            display: flex;
            justify-content: center;
            animation: float 6s ease-in-out infinite;
            filter: drop-shadow(0 10px 5px rgba(0,0,0,0.1));
        }

        .floating-logo img {
            height: 110px;
            width: auto;
            object-fit: contain;
        }

        .container {
            position: relative;
            width: 100%;
            max-width: 420px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
            padding: 32px;
            opacity: 0;
            transform: translateY(50px);
            animation: fadeInUp 0.8s 0.4s forwards;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--primary);
            animation: expandLine 1.2s 0.8s forwards;
            transform-origin: left;
            transform: scaleX(0);
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            color: var(--primary);
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
            opacity: 0;
            animation: fadeIn 0.8s 0.6s forwards;
        }

        .header p {
            color: var(--secondary);
            font-size: 14px;
            opacity: 0;
            animation: fadeIn 0.8s 0.8s forwards;
        }

        .form-group {
            position: relative;
            margin-bottom: 25px;
            opacity: 0;
            transform: translateX(-20px);
            animation: slideIn 0.5s forwards;
        }

        .form-group:nth-child(1) { animation-delay: 1.0s; }
        .form-group:nth-child(2) { animation-delay: 1.2s; }
        .form-group:nth-child(3) { animation-delay: 1.4s; }

        .form-group input, .form-group select {
            width: 100%;
            padding: 15px 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            color: var(--dark);
            transition: all 0.3s;
            background-color: rgba(255, 255, 255, 0.9);
            appearance: none;
            -webkit-appearance: none;
        }

        .form-group select {
            cursor: pointer;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(186, 12, 47, 0.2);
            outline: none;
        }

        .form-group label {
            position: absolute;
            top: 15px;
            left: 20px;
            color: var(--secondary);
            font-size: 16px;
            font-weight: 300;
            transition: all 0.3s;
            pointer-events: none;
            background: white;
            padding: 0 5px;
        }

        .form-group input:focus + label,
        .form-group input:not(:placeholder-shown) + label,
        .form-group select:focus + label,
        .form-group select:not([value=""]) + label {
            top: -10px;
            left: 15px;
            font-size: 12px;
            color: var(--primary);
            background: white;
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s 1.6s forwards;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            background: #9a0a27;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(186, 12, 47, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        .btn:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }

        .signup-link {
            text-align: center;
            margin-top: 16px;
            opacity: 0;
            animation: fadeIn 0.8s 1.8s forwards;
        }

        .signup-link p {
            color: var(--secondary);
            font-size: 14px;
            display: inline-block;
            margin-right: 5px;
        }

        .signup-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            position: relative;
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .signup-link a:hover {
            background-color: rgba(186, 12, 47, 0.1);
            transform: translateY(-2px);
        }

        .signup-link a:active {
            transform: translateY(0);
        }

        .footer-bottom {
            width: 100%;
            text-align: center;
            margin-top: 24px;
            padding: 14px 12px;
            background: #292929;
            color: rgba(255, 255, 255, 0.75);
            font-size: 0.9rem;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
        }

        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .particle {
            position: absolute;
            background: var(--primary);
            border-radius: 50%;
            opacity: 0;
        }

        .login-error-message {
            color: #d32f2f;
            text-align: center;
            font-size: 14px;
            margin-bottom: 15px;
            font-weight: 500;
            display: <?php echo $error ? 'block' : 'none'; ?>;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }

        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            to { opacity: 1; }
        }

        @keyframes slideIn {
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes expandLine {
            to { transform: scaleX(1); }
        }

        @keyframes ripple {
            0% { transform: scale(0, 0); opacity: 1; }
            100% { transform: scale(40, 40); opacity: 0; }
        }

        @keyframes particle-float {
            0% { transform: translateY(0) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            100% { transform: translateY(-1000px) rotate(720deg); opacity: 0; }
        }

        @media (max-width: 768px) {
            .content-wrapper { padding: 24px 20px; }
            .container { padding: 28px; }
            .floating-logo img { height: 96px; }
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <div class="floating-logo">
            <img src="nmimslogo.png" alt="NMIMS Logo">
        </div>
        <div class="container">
            <div class="header">
                <h1>Welcome to NMIMS University</h1>
                <p>Login to access your dashboard</p>
                
            </div>

            <div class="login-error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>

            <form id="loginForm" method="POST">
                <div class="form-group">
                    <input type="text" id="identifier" name="identifier" placeholder=" " required>
                    <label for="identifier">ID (SAP ID / Faculty ID)</label>
                </div>

                <div class="form-group">
                    <input type="password" id="password" name="password" placeholder=" " required>
                    <label for="password">Password</label>
                </div>

                <button type="submit" class="btn">Login</button>

                <div class="signup-link">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
            </form>
        </div>
    </div>

    <div class="footer-bottom">
        &copy; <?php echo date("Y"); ?> Kuchuru Sai Krishna Reddy – STME. All rights reserved.
    </div>

    <div class="particles" id="particles"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 15;
            for (let i = 0; i < particleCount; i++) { createParticle(); }
            function createParticle() {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                const size = Math.random() * 8 + 2;
                const posX = Math.random() * window.innerWidth;
                const delay = Math.random() * 5;
                const duration = Math.random() * 15 + 10;
                const opacity = Math.random() * 0.4 + 0.1;
                const color = `rgba(${Math.random() > 0.5 ? '186, 12, 47' : '99, 102, 106'}, ${opacity})`;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${posX}px`;
                particle.style.bottom = '-10px';
                particle.style.background = color;
                particle.style.animation = `particle-float ${duration}s linear ${delay}s infinite`;
                particlesContainer.appendChild(particle);
                setTimeout(() => { particle.remove(); createParticle(); }, duration * 1000);
            }
        });
    </script>
</body>
</html> 
