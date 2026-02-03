<?php
session_start();
include 'db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/academic_context.php';
require_once __DIR__ . '/includes/term_switcher_ui.php';

$adminSchool = $_SESSION['school'] ?? '';
$adminNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$adminNameDisplay = $adminNameRaw !== '' ? format_person_display($adminNameRaw) : 'ADMIN';
$academicContext = resolveAcademicContext($conn, [
    'school_name' => $adminSchool
]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ICA Tracker</title>
   
 <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Custom styles for the new dashboard cards */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .dashboard-card {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .dashboard-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .dashboard-card i {
            font-size: 2.5rem;
            color: #A6192E; /* Main theme color */
            margin-bottom: 15px;
            background-color: rgba(166, 25, 46, 0.1);
            border-radius: 50%;
            width: 70px;
            height: 70px;
            line-height: 70px;
            transition: background-color 0.3s ease;
        }
        
        .dashboard-card:hover i {
            background-color: rgba(166, 25, 46, 0.2);
        }

        .dashboard-card span {
            font-size: 1.1rem;
            font-weight: 600;
            display: block;
        }
        
        body.dark-mode .dashboard-card {
            background-color: #5a5a5a;
            color: #FFFFFF;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
            <h2>ICA Tracker</h2>
            <a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            
            <a href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> <span>Manage Teachers</span></a>
            <a href="create_classes.php"><i class="fas fa-layer-group"></i> <span>Create Classes</span></a>
            <a href="create_subjects.php"><i class="fas fa-book"></i> <span>Create Subjects</span></a>
            <a href="assign_teachers.php"><i class="fas fa-user-tag"></i> <span>Assign Teachers</span></a>
            <a href="manage_electives.php"><i class="fas fa-user-friends"></i> <span>Manage Electives</span></a>
            <a href="change_roles.php"><i class="fas fa-user-cog"></i> <span>Change Roles</span></a>
            <a href="bulk_add_students.php"><i class="fas fa-file-upload"></i> <span>Add Students</span></a>
            <a href="manage_academic_calendar.php"><i class="fas fa-calendar-alt"></i> <span>Academic Calendar</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
        <div class="main-content">
            <div class="header">
                <h2>Welcome, <?php echo htmlspecialchars($adminNameDisplay !== '' ? $adminNameDisplay : $adminNameRaw); ?>!</h2>
                
            </div>
            <?php renderTermSwitcher($academicContext, ['school_name' => $adminSchool]); ?>
            <div class="container">
                <div class="dashboard-grid">
                    <a href="manage_teachers.php" class="dashboard-card">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Manage Teachers</span>
                    </a>
                    <a href="create_classes.php" class="dashboard-card">
                        <i class="fas fa-layer-group"></i>
                        <span>Create Classes</span>
                    </a>
                    <a href="create_subjects.php" class="dashboard-card">
                        <i class="fas fa-book"></i>
                        <span>Create Subjects</span>
                    </a>
                    <a href="assign_teachers.php" class="dashboard-card">
                        <i class="fas fa-user-tag"></i>
                        <span>Assign Teachers</span>
                    </a>
                    <a href="manage_electives.php" class="dashboard-card">
                        <i class="fas fa-user-friends"></i>
                        <span>Manage Electives</span>
                    </a>
                    <a href="change_roles.php" class="dashboard-card">
                        <i class="fas fa-user-cog"></i>
                        <span>Change Roles</span>
                    </a>
                    <a href="bulk_add_students.php" class="dashboard-card">
                        <i class="fas fa-file-upload"></i>
                        <span>Add Students</span>
                    </a>
                    <a href="manage_academic_calendar.php" class="dashboard-card">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Academic Calendar</span>
                    </a>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; <?php echo date("Y"); ?> Kuchuru Sai Krishna Reddy – STME. All rights reserved.
            </div>
        </div>
    </div>
    <script>
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        }
        if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');
    </script>
</body>
</html>
