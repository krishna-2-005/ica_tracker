<?php
require_once __DIR__ . '/includes/init.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'program_chair') {
    header('Location: login.php');
    exit;
}

if (!empty($_SESSION['force_password_change'])) {
    header('Location: change_password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $choice = $_POST['role_choice'] ?? '';
    if ($choice === 'teacher') {
        $_SESSION['role'] = 'teacher';
        header('Location: teacher_dashboard.php');
        exit;
    }
    if ($choice === 'program_chair') {
        $_SESSION['role'] = 'program_chair';
        header('Location: program_dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Role - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');
        :root {
            --primary: #BA0C2F;
            --secondary: #63666A;
            --light: #f7f8fb;
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
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
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
            padding: 32px 16px;
        }
        .floating-logo {
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
        .card {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 18px 36px rgba(0, 0, 0, 0.12);
            padding: 36px;
            max-width: 440px;
            width: 100%;
            text-align: center;
        }
        h2 {
            color: var(--primary);
            margin-bottom: 12px;
            font-weight: 600;
        }
        p {
            color: var(--secondary);
            margin-bottom: 25px;
            font-size: 0.95rem;
        }
        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .btn {
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-primary {
            background: var(--primary);
            color: #fff;
        }
        .btn-secondary {
            background: #ffffff;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px rgba(186, 12, 47, 0.2);
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

        @keyframes float {
            0% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
            100% { transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 24px 20px;
            }
            .card {
                padding: 30px;
            }
            .floating-logo img {
                height: 96px;
            }
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <div class="floating-logo">
            <img src="nmimslogo.png" alt="NMIMS Logo">
        </div>
        <div class="card">
            <h2>Select Login Mode</h2>
            <p>Choose how you would like to access the ICA Tracker today.</p>
            <form method="POST" class="btn-group">
                <button type="submit" name="role_choice" value="teacher" class="btn btn-secondary">Login as Teacher</button>
                <button type="submit" name="role_choice" value="program_chair" class="btn btn-primary">Login as Program Chair</button>
            </form>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?php echo date("Y"); ?> Kuchuru Sai Krishna Reddy – STME. All rights reserved.
    </div>
</body>
</html>

