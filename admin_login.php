<?php
session_start();
include 'db_connect.php';
require_once __DIR__ . '/includes/activity_logger.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminId = trim($_POST['admin_id'] ?? '');
    $plainPassword = $_POST['password'] ?? '';

    if ($adminId === '' || $plainPassword === '') {
        $error = 'Please enter your Admin ID and password.';
    } else {
        $username = mysqli_real_escape_string($conn, $adminId);

        $query = "SELECT id, username, password, role, name, status, teacher_unique_id FROM users WHERE role = 'admin' AND (username = ? OR teacher_unique_id = ?) LIMIT 1";
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
                    $_SESSION['user_role'] = 'admin';
                    $_SESSION['role'] = 'admin';

                    $_SESSION['post_login_redirect'] = 'admin_dashboard.php';
                    $_SESSION['force_password_change'] = password_verify('123456', $admin['password']) || md5('123456') === $admin['password'];

                    log_activity($conn, [
                        'actor_id' => (int)$admin['id'],
                        'event_type' => 'admin_login_success',
                        'event_label' => 'Administrator logged in',
                        'description' => 'Admin portal authentication succeeded.',
                        'metadata' => [
                            'identifier_used' => $adminId,
                            'teacher_unique_id' => $admin['teacher_unique_id'] ?? null,
                            'session_id' => session_id(),
                            'force_password_change' => !empty($_SESSION['force_password_change']),
                        ],
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    ]);

                    if (!empty($_SESSION['force_password_change'])) {
                        header('Location: change_password.php');
                    } else {
                        header('Location: admin_dashboard.php');
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
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');
        :root {
            --primary: #A6192E;
            --secondary: #63666A;
            --dark: #2C2A29;
            --light: #f7f8fc;
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
            background: var(--light);
            min-height: 100vh;
            height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
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
            z-index: 1;
            filter: drop-shadow(0 10px 5px rgba(0,0,0,0.1));
        }
        .floating-logo img {
            height: 110px;
            pointer-events: none;
        }
        .container {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 12px 28px rgba(166, 25, 46, 0.12);
            padding: 32px;
            max-width: 420px;
            width: 100%;
            position: relative;
            z-index: 2;
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
        h1 {
            color: var(--primary);
            font-size: 28px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 10px;
            opacity: 0;
            animation: fadeIn 0.8s 0.6s forwards;
        }
        p.subtitle {
            color: var(--secondary);
            text-align: center;
            margin-bottom: 25px;
            font-size: 0.95rem;
            opacity: 0;
            animation: fadeIn 0.8s 0.8s forwards;
        }
        .form-group {
            margin-bottom: 20px;
            opacity: 0;
            transform: translateX(-20px);
            animation: slideIn 0.5s forwards;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-size: 0.95rem;
        }
        input[type='text'],
        input[type='password'] {
            width: 100%;
            padding: 12px 16px;
            border-radius: 10px;
            border: 1px solid #dcdde7;
            font-size: 1rem;
            transition: border-color 0.2s ease;
        }
        input[type='text']:focus,
        input[type='password']:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(166, 25, 46, 0.18);
        }
        .error-message {
            color: #d64545;
            font-size: 0.9rem;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 500;
        }
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: var(--primary);
            color: #fff;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s 1.4s forwards;
            position: relative;
            overflow: hidden;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 28px rgba(166, 25, 46, 0.25);
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
        .links {
            margin-top: 16px;
            text-align: center;
            opacity: 0;
            animation: fadeIn 0.8s 1.6s forwards;
        }
        .links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        .footer-bottom {
            width: 100%;
            margin-top: 24px;
            padding: 14px 12px;
            background: #292929;
            color: rgba(255, 255, 255, 0.75);
            font-size: 0.9rem;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
        }
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
            pointer-events: none;
        }
        .particle {
            position: absolute;
            border-radius: 50%;
            opacity: 0;
        }
        @keyframes float {
            0% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
            100% { transform: translateY(0); }
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
            .content-wrapper {
                padding: 24px 20px;
            }
            .container {
                padding: 28px;
            }
            .floating-logo img {
                height: 96px;
            }
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>
    <div class="content-wrapper">
        <div class="floating-logo">
            <img src="nmimslogo.png" alt="NMIMS Logo">
        </div>
        <div class="container">
        <h1>Administrator Login</h1>
        <p class="subtitle">Restricted access. Use your Admin ID to continue.</p>
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="admin_id">Admin ID</label>
                <input type="text" id="admin_id" name="admin_id" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <div class="links">
            <a href="login.php">Go back to standard login</a>
        </div>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?php echo date("Y"); ?> Kuchuru Sai Krishna Reddy – STME. All rights reserved.
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const particlesContainer = document.getElementById('particles');
            if (!particlesContainer) {
                return;
            }

            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach((group, index) => {
                group.style.animationDelay = `${1.0 + index * 0.2}s`;
            });

            const particleCount = 15;
            for (let i = 0; i < particleCount; i++) {
                createParticle();
            }

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
                setTimeout(() => {
                    particle.remove();
                    createParticle();
                }, duration * 1000);
            }
        });
    </script>
</body>
</html>

