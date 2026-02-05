<?php
// Start session and ensure no output before this
ob_start();
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/activity_logger.php';

// Handle logout action for AJAX request
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $actorId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    if ($actorId && isset($conn) && $conn instanceof mysqli) {
        log_activity($conn, [
            'actor_id' => $actorId,
            'event_type' => 'logout',
            'event_label' => 'User logged out',
            'description' => 'Logout requested from logout modal.',
            'metadata' => [
                'session_id' => session_id(),
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
            ],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
    session_destroy();
    if (isset($conn) && $conn instanceof mysqli) {
        mysqli_close($conn);
    }
    ob_end_clean(); // Clear any output buffer
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit;
}
ob_end_flush(); // Flush output buffer for HTML content
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            transition: background 0.3s, color 0.3s;
        }

        body.dark-mode {
            background: linear-gradient(135deg, #2c3e50 0%, #4a4a4a 100%);
            color: #e0e0e0;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 450px;
            width: 90%;
            position: relative;
        }

        body.dark-mode .modal-content {
            background: #4a4a4a;
            color: #e0e0e0;
        }

        .modal-content h2 {
            color: #BA0C2F;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        body.dark-mode .modal-content h2 {
            color: #ecf0f1;
        }

        .modal-buttons {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-cancel {
            background: #ddd;
            color: #2C2A29;
        }

        .btn-cancel:hover {
            background: #ccc;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-confirm {
            background: #BA0C2F;
            color: white;
        }

        .btn-confirm:hover {
            background: #9a0a27;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(186, 12, 47, 0.3);
        }

        .error-message {
            color: #d32f2f;
            font-size: 14px;
            margin-top: 10px;
            display: none;
        }
    </style>
</head>
<body>
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <h2>Are you sure you want to logout?</h2>
            <div class="modal-buttons">
                <button class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn btn-confirm" onclick="confirmLogout()">Yes</button>
            </div>
            <div id="errorMessage" class="error-message"></div>
        </div>
    </div>

    <script>
        // Show the modal when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('logoutModal').style.display = 'flex';
        });

        function closeModal() {
            // Redirect to the appropriate dashboard based on role
            <?php
            $redirectUrl = 'login.php'; // Default fallback
            if (isset($_SESSION['role'])) {
                switch ($_SESSION['role']) {
                    case 'student':
                        $redirectUrl = 'student_dashboard.php';
                        break;
                    case 'teacher':
                        $redirectUrl = 'teacher_dashboard.php';
                        break;
                    case 'program_chair':
                        $redirectUrl = 'program_dashboard.php';
                        break;
                    case 'admin':
                        $redirectUrl = 'admin_dashboard.php';
                        break;
                }
            }
            ?>
            window.location.href = '<?php echo $redirectUrl; ?>';
        }

        function confirmLogout() {
            const errorMessage = document.getElementById('errorMessage');
            fetch('logout.php?action=logout', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = 'login.php';
                } else {
                    errorMessage.textContent = 'Logout failed. Please try again.';
                    errorMessage.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorMessage.textContent = 'An error occurred during logout. Please try again.';
                errorMessage.style.display = 'block';
            });
        }

        // Check for dark mode preference
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }
    </script>
</body>
</html>
