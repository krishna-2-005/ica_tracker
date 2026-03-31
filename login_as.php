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
        }

        .login-panel {
            width: 100%;
            max-width: 520px;
            padding: 28px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 90% 10%, rgba(166, 25, 46, 0.10) 0%, rgba(166, 25, 46, 0) 48%), var(--soft-bg);
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--panel);
            border-radius: 14px;
            border: 1px solid #e5eaf1;
            box-shadow: 0 14px 34px rgba(21, 33, 50, 0.14);
            padding: 30px 28px;
            text-align: center;
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
            font-size: 1.5rem;
            margin-bottom: 6px;
        }

        .login-head p {
            color: var(--muted);
            font-size: 0.9rem;
        }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn {
            width: 100%;
            height: 48px;
            border: none;
            border-radius: 24px;
            font-size: 0.98rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
        }

        .btn-primary {
            background: var(--brand);
            color: #fff;
        }

        .btn-secondary {
            background: #ffffff;
            color: var(--brand);
            border: 1.5px solid var(--line);
        }

        .btn-primary:hover {
            background: var(--brand-dark);
            box-shadow: 0 8px 20px rgba(166, 25, 46, 0.28);
            transform: translateY(-1px);
        }

        .btn-secondary:hover {
            border-color: var(--brand);
            background: #fbfcfe;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(47, 58, 73, 0.12);
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn:active {
            transform: translateY(0);
            box-shadow: none;
        }

        .trust-text {
            margin-top: 10px;
            text-align: center;
            color: #6b7280;
            font-size: 0.78rem;
        }

        .footer-bottom {
            padding: 11px 14px;
            background: #333333;
            color: rgba(255, 255, 255, 0.82);
            font-size: 0.78rem;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.14);
        }

        @media (max-width: 980px) {
            .login-panel {
                padding: 22px 16px 24px;
            }
        }

        @media (max-width: 520px) {
            .login-card {
                padding: 22px 16px;
                border-radius: 12px;
            }

            .logo-wrap img {
                height: 66px;
            }

            .login-head h2 {
                font-size: 1.3rem;
            }

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
    <div class="auth-layout">
        <section class="login-panel">
            <div class="login-card">
                <div class="logo-wrap">
                    <img src="nmimslogo.png" alt="NMIMS Logo">
                </div>

                <div class="login-head">
                    <h2>ICA Tracker</h2>
                    <p>Choose how you would like to continue</p>
                </div>

                <form method="POST" class="btn-group">
                    <button type="submit" name="role_choice" value="teacher" class="btn btn-secondary">Login as Teacher</button>
                    <button type="submit" name="role_choice" value="program_chair" class="btn btn-primary">Login as Program Chair</button>
                </form>

                <p class="trust-text">Use your authorized access mode for ICA Tracker.</p>
            </div>
        </section>
    </div>

    <div class="footer-bottom">
        &copy; <?php echo date("Y"); ?> Kuchuru Sai Krishna Reddy – STME. All rights reserved.
    </div>
</body>
</html>

