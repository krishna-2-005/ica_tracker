<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $department = trim($_POST['department']);

    if (empty($username) || empty($password) || empty($role) || empty($name) || empty($email) || empty($department)) {
        $error = "All fields are required!";
    } elseif (!in_array($role, ['teacher', 'program_chair', 'admin', 'student'])) {
        $error = "Invalid role selected!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else {
        $check_query = "SELECT id FROM users WHERE username = ?";
        $stmt_check = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt_check, "s", $username);
        mysqli_stmt_execute($stmt_check);
        $check_result = mysqli_stmt_get_result($stmt_check);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Username already exists!";
        } else {
            $hashed_password = md5($password); // Matches login.php
            $status = 'active';
            $query = "INSERT INTO users (username, password, role, name, email, department, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssssss", $username, $hashed_password, $role, $name, $email, $department, $status);
            
            if (mysqli_stmt_execute($stmt)) {
                $user_id = mysqli_insert_id($conn); // Get new user ID
                $success = "Sign up successful! Please log in.";
                
                if ($role === 'student') {
                    $class_id = 10; // 3rd Year CSEDS
                    $roll_number = 'D' . rand(100, 999); // e.g., D123
                    $student_query = "INSERT INTO students (name, roll_number, sap_id, class_id) VALUES (?, ?, ?, ?)";
                    $stmt_student = mysqli_prepare($conn, $student_query);
                    mysqli_stmt_bind_param($stmt_student, "sssi", $name, $roll_number, $user_id, $class_id);
                    if (!mysqli_stmt_execute($stmt_student)) {
                        $error = "Error adding student record: " . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt_student);
                }
                
                $username = $name = $email = $department = $role = '';
            } else {
                $error = "Error signing up: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_stmt_close($stmt_check);
    }
}
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Sign Up - ICA Tracker</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        :root {
            --primary: #BA0C2F;
            --secondary: #63666A;
            --dark: #2C2A29;
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
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(186, 12, 47, 0.05) 0%, rgba(186, 12, 47, 0.05) 90%),
                radial-gradient(circle at 90% 80%, rgba(99, 102, 106, 0.05) 0%, rgba(99, 102, 106, 0.05) 90%);
        }

        .floating-logo {
            position: absolute;
            top: 30px;
            animation: float 6s ease-in-out infinite;
            filter: drop-shadow(0 10px 5px rgba(0,0,0,0.1));
        }

        .floating-logo img {
            height: 120px;
            width: auto;
            object-fit: contain;
        }

        .container {
            position: relative;
            width: 100%;
            max-width: 450px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin-top: 150px;
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
            margin-bottom: 30px;
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
        .form-group:nth-child(4) { animation-delay: 1.6s; }
        .form-group:nth-child(5) { animation-delay: 1.8s; }
        .form-group:nth-child(6) { animation-delay: 2.0s; }

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
            animation: fadeInUp 0.8s 2.2s forwards;
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

        .login-link {
            text-align: center;
            margin-top: 20px;
            opacity: 0;
            animation: fadeIn 0.8s 2.4s forwards;
        }

        .login-link p {
            color: var(--secondary);
            font-size: 14px;
            display: inline-block;
            margin-right: 5px;
        }

        .login-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            position: relative;
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .login-link a:hover {
            background-color: rgba(186, 12, 47, 0.1);
            transform: translateY(-2px);
        }

        .login-link a:active {
            transform: translateY(0);
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

        .error-message {
            color: #d32f2f;
            font-size: 14px;
            margin-top: 5px;
            text-align: center;
            display: <?php echo $error ? 'block' : 'none'; ?>;
        }

        .success-message {
            color: #388e3c;
            font-size: 14px;
            margin-top: 5px;
            text-align: center;
            display: <?php echo $success ? 'block' : 'none'; ?>;
            margin-bottom: 15px;
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
            .container { margin: 150px 20px 40px; padding: 30px; }
            .floating-logo img { height: 100px; }
        }
    </style>
</head>
<body>
    <div class="floating-logo">
        <img src="nmimslogo.png" alt="College Logo">
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    </div>

    <div class="container">
        <div class="header">
            <h1>Create Account</h1>
            <p>Join us to get started</p>
        </div>

        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php elseif ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form id="signupForm" method="POST">
            <div class="form-group">
                <input type="text" id="name" name="name" placeholder=" " value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                <label for="name">Full Name</label>
            </div>

            <div class="form-group">
                <input type="text" id="username" name="username" placeholder=" " value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                <label for="username">Username</label>
            </div>

            <div class="form-group">
                <input type="password" id="password" name="password" placeholder=" " required>
                <label for="password">Password</label>
            </div>

            <div class="form-group">
                <input type="email" id="email" name="email" placeholder=" " value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                <label for="email">Email</label>
            </div>

            <div class="form-group">
                <input type="text" id="department" name="department" placeholder=" " value="<?php echo isset($department) ? htmlspecialchars($department) : ''; ?>" required>
                <label for="department">Department</label>
            </div>

            <div class="form-group">
                <select id="role" name="role" required>
                    <option value="" selected disabled></option>
                    <option value="admin" <?php echo (isset($role) && $role === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="teacher" <?php echo (isset($role) && $role === 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                    <option value="program_chair" <?php echo (isset($role) && $role === 'program_chair') ? 'selected' : ''; ?>>Program Chair</option>
                    <option value="student" <?php echo (isset($role) && $role === 'student') ? 'selected' : ''; ?>>Student</option>
                </select>
                <label for="role">Role</label>
            </div>

            <button type="submit" class="btn">Sign Up</button>

            <div class="login-link">
                <p>Already have an account?</p>
                <a href="login.php">Login</a>
            </div>
        </form>
    </div>

    <div class="particles" id="particles"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Create floating particles
            const particlesContainer = document.getElementById('particles');
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

            // Form validation
            const form = document.getElementById('signupForm');
            form.addEventListener('submit', function(e) {
                const name = document.getElementById('name').value.trim();
                const username = document.getElementById('username').value.trim();
                const password = document.getElementById('password').value;
                const email = document.getElementById('email').value.trim();
                const department = document.getElementById('department').value.trim();
                const role = document.getElementById('role').value;
                const btn = document.querySelector('.btn');
                
                // Reset error messages
                document.querySelectorAll('.error-message').forEach(el => {
                    el.style.display = 'none';
                    el.textContent = '';
                });
                
                // Validate inputs
                let isValid = true;
                
                if (!name) {
                    document.querySelector('.error-message').textContent = 'Full name is required';
                    document.querySelector('.error-message').style.display = 'block';
                    isValid = false;
                }
                
                if (!username) {
                    document.querySelector('.error-message').textContent = 'Username is required';
                    document.querySelector('.error-message').style.display = 'block';
                    isValid = false;
                }
                
                if (!password) {
                    document.querySelector('.error-message').textContent = 'Password is required';
                    document.querySelector('.error-message').style.display = 'block';
                    isValid = false;
                }
                
                if (!email) {
                    document.querySelector('.error-message').textContent = 'Email is required';
                    document.querySelector('.error-message').style.display = 'block';
                    isValid = false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    document.querySelector('.error-message').textContent = 'Invalid email format';
                    document.querySelector('.error-message').style.display = 'block';
                    isValid = false;
                }
                
                if (!department) {
                    document.querySelector('.error-message').textContent = 'Department is required';
                    document.querySelector('.error-message').style.display = 'block';
                    isValid = false;
                }
                
                if (!role) {
                    document.querySelector('.error-message').textContent = 'Please select a role';
                    document.querySelector('.error-message').style.display = 'block';
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                } else {
                    btn.disabled = true;
                    btn.textContent = 'Creating Account...';
                    createConfetti();
                }
            });

            function createConfetti() {
                const confettiCount = 100;
                const container = document.querySelector('.container');
                
                for (let i = 0; i < confettiCount; i++) {
                    const confetti = document.createElement('div');
                    confetti.classList.add('confetti');
                    
                    const size = Math.random() * 10 + 5;
                    const posX = Math.random() * container.offsetWidth;
                    const color = `hsl(${Math.random() * 60 + 330}, 100%, 50%)`;
                    const rotation = Math.random() * 360;
                    const animationDuration = Math.random() * 3 + 2;
                    
                    confetti.style.width = `${size}px`;
                    confetti.style.height = `${size}px`;
                    confetti.style.left = `${posX}px`;
                    confetti.style.top = '-10px';
                    confetti.style.backgroundColor = color;
                    confetti.style.position = 'absolute';
                    confetti.style.borderRadius = '50%';
                    confetti.style.transform = `rotate(${rotation}deg)`;
                    confetti.style.animation = `confetti-fall ${animationDuration}s linear forwards`;
                    
                    container.appendChild(confetti);
                    
                    setTimeout(() => {
                        confetti.remove();
                    }, animationDuration * 1000);
                }
                
                const style = document.createElement('style');
                style.textContent = `
                    @keyframes confetti-fall {
                        0% { transform: translateY(0) rotate(0deg); opacity: 1; }
                        100% { transform: translateY(600px) rotate(360deg); opacity: 0; }
                    }
                `;
                document.head.appendChild(style);
            }

            // Handle select dropdown styling
            const roleSelect = document.getElementById('role');
            roleSelect.addEventListener('change', function() {
                if (this.value) {
                    this.classList.add('has-value');
                } else {
                    this.classList.remove('has-value');
                }
            });

            // Initialize fields that have values
            document.querySelectorAll('input, select').forEach(el => {
                if (el.value) {
                    const label = el.nextElementSibling;
                    if (label && label.tagName === 'LABEL') {
                        label.style.top = '-10px';
                        label.style.left = '15px';
                        label.style.fontSize = '12px';
                        label.style.color = 'var(--primary)';
                        label.style.background = 'white';
                    }
                }
            });
        });
    </script>
</body>
</html>
