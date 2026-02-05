<?php
require_once __DIR__ . '/includes/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$redirectAfter = $_SESSION['post_login_redirect'] ?? 'login.php';
$forceChange = !empty($_SESSION['force_password_change']);

if (!$forceChange && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirectAfter);
    exit;
}

require_once __DIR__ . '/db_connect.php';

$error = '';
$success = '';
$userRow = null;
$studentRow = null;

$userStmt = mysqli_prepare($conn, 'SELECT id, username, role, name, email FROM users WHERE id = ? LIMIT 1');
if ($userStmt) {
    mysqli_stmt_bind_param($userStmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($userStmt);
    $userResult = mysqli_stmt_get_result($userStmt);
    $userRow = $userResult ? mysqli_fetch_assoc($userResult) : null;
    if ($userResult) {
        mysqli_free_result($userResult);
    }
    mysqli_stmt_close($userStmt);
}

if (!$userRow) {
    mysqli_close($conn);
    session_destroy();
    header('Location: login.php');
    exit;
}

if ($userRow['role'] === 'student') {
    $studentStmt = mysqli_prepare($conn, 'SELECT id, sap_id, name, class_id, section_id, college_email FROM students WHERE sap_id = ? LIMIT 1');
    if ($studentStmt) {
        mysqli_stmt_bind_param($studentStmt, 's', $userRow['username']);
        mysqli_stmt_execute($studentStmt);
        $studentResult = mysqli_stmt_get_result($studentStmt);
        $studentRow = $studentResult ? mysqli_fetch_assoc($studentResult) : null;
        if ($studentResult) {
            mysqli_free_result($studentResult);
        }
        mysqli_stmt_close($studentStmt);
    }
}

$requireCollegeEmail = !empty($_SESSION['require_college_email']);
if ($userRow['role'] === 'student') {
    $requireCollegeEmail = $requireCollegeEmail || empty(trim((string)($studentRow['college_email'] ?? '')));
}
$_SESSION['require_college_email'] = $requireCollegeEmail;
$pendingCollegeEmail = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $submittedCollege = trim($_POST['college_email'] ?? '');

    if ($requireCollegeEmail) {
        if ($submittedCollege === '') {
            $error = 'Please enter your NMIMS college email address.';
        } elseif (!preg_match('/^[^\s@]+@(?:nmims\.in|nmims\.edu(?:\.in)?)$/i', $submittedCollege)) {
            $error = 'Only NMIMS email domains (nmims.in, nmims.edu, nmims.edu.in) are accepted.';
        } else {
            $pendingCollegeEmail = strtolower($submittedCollege);
        }
    }

    if ($error === '' && ($newPassword === '' || $confirmPassword === '')) {
        $error = 'Please fill in both password fields.';
    } elseif ($error === '' && $newPassword !== $confirmPassword) {
        $error = 'New password and confirmation do not match.';
    } elseif ($error === '' && $newPassword === '123456') {
        $error = 'Please choose a password other than the default password.';
    } elseif ($error === '' && strlen($newPassword) < 6) {
        $error = 'Password should be at least 6 characters long.';
    } elseif ($error === '') {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        if (!mysqli_begin_transaction($conn)) {
            $error = 'Unable to update credentials right now. Please retry.';
        } else {
            $allGood = true;

            if ($pendingCollegeEmail !== null) {
                $studentUpdateOk = false;
                if ($studentRow && isset($studentRow['id'])) {
                    $studentUpdateStmt = mysqli_prepare($conn, 'UPDATE students SET college_email = ? WHERE id = ?');
                    if ($studentUpdateStmt) {
                        mysqli_stmt_bind_param($studentUpdateStmt, 'si', $pendingCollegeEmail, $studentRow['id']);
                        $studentUpdateOk = mysqli_stmt_execute($studentUpdateStmt);
                        mysqli_stmt_close($studentUpdateStmt);
                    }
                } else {
                    $studentUpdateStmt = mysqli_prepare($conn, 'UPDATE students SET college_email = ? WHERE sap_id = ?');
                    if ($studentUpdateStmt) {
                        mysqli_stmt_bind_param($studentUpdateStmt, 'ss', $pendingCollegeEmail, $userRow['username']);
                        $studentUpdateOk = mysqli_stmt_execute($studentUpdateStmt);
                        $affected = mysqli_stmt_affected_rows($studentUpdateStmt);
                        mysqli_stmt_close($studentUpdateStmt);
                        if (!$studentUpdateOk || $affected <= 0) {
                            $studentInsertStmt = mysqli_prepare($conn, 'INSERT INTO students (sap_id, name, college_email) VALUES (?, ?, ?)');
                            if ($studentInsertStmt) {
                                mysqli_stmt_bind_param($studentInsertStmt, 'sss', $userRow['username'], $userRow['name'], $pendingCollegeEmail);
                                $studentUpdateOk = mysqli_stmt_execute($studentInsertStmt);
                                mysqli_stmt_close($studentInsertStmt);
                            }
                        }
                    }
                }

                if (!$studentUpdateOk) {
                    $allGood = false;
                } else {
                    $userEmailStmt = mysqli_prepare($conn, 'UPDATE users SET email = ? WHERE id = ?');
                    if ($userEmailStmt) {
                        mysqli_stmt_bind_param($userEmailStmt, 'si', $pendingCollegeEmail, $userRow['id']);
                        $allGood = mysqli_stmt_execute($userEmailStmt);
                        mysqli_stmt_close($userEmailStmt);
                    } else {
                        $allGood = false;
                    }
                }
            }

            if ($allGood) {
                $stmt = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE id = ?');
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'si', $hashedPassword, $_SESSION['user_id']);
                    if (!mysqli_stmt_execute($stmt)) {
                        $allGood = false;
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $allGood = false;
                }
            }

            if ($allGood) {
                mysqli_commit($conn);
                $_SESSION['force_password_change'] = false;
                $_SESSION['require_college_email'] = false;
                $redirectPath = $_SESSION['post_login_redirect'] ?? 'login.php';
                unset($_SESSION['post_login_redirect']);
                mysqli_close($conn);
                header('Location: ' . $redirectPath);
                exit;
            }

            mysqli_rollback($conn);
            $error = 'Failed to update password. Please try again.';
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
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <title>Change Password - ICA Tracker</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');
        :root {
            --primary: #BA0C2F;
            --secondary: #63666A;
            --light: #f8f9fa;
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
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .card {
            background: #ffffff;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            max-width: 420px;
            width: 90%;
        }
        h2 {
            margin-bottom: 10px;
            color: var(--primary);
            font-weight: 600;
            text-align: center;
        }
        p.description {
            color: var(--secondary);
            font-size: 0.95rem;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-size: 0.9rem;
        }
        input[type='password'],
        input[type='email'] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        input[type='password']:focus,
        input[type='email']:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(186, 12, 47, 0.15);
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 18px rgba(186, 12, 47, 0.25);
        }
        .error-message {
            color: #d32f2f;
            margin-bottom: 15px;
            font-size: 0.9rem;
            text-align: center;
        }
        .success-message {
            color: #2e7d32;
            margin-bottom: 15px;
            font-size: 0.9rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Change Password</h2>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
        <p class="description">Please set a new password before continuing to your dashboard.</p>
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form method="POST">
            <?php if ($requireCollegeEmail): ?>
                <div class="form-group">
                    <label for="college_email">NMIMS College Email</label>
                    <input type="email" id="college_email" name="college_email" value="<?php echo htmlspecialchars($submittedCollege ?? ($studentRow['college_email'] ?? '')); ?>" required>
                </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn">Update Password</button>
        </form>
    </div>
</body>
</html>

