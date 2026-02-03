<?php
session_start();
include 'db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';
$adminNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$adminNameDisplay = $adminNameRaw !== '' ? format_person_display($adminNameRaw) : 'ADMIN';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = (int)$_POST['user_id'];
    $new_role = mysqli_real_escape_string($conn, $_POST['new_role']);
    $school = mysqli_real_escape_string($conn, $_POST['school']);

    // Proceed only if a user and department are selected
    if ($user_id > 0 && !empty($school)) {
        // If the new role is 'program_chair', check for existing ones in the department
        if ($new_role == 'program_chair') {
            $check_pc_query = "SELECT id FROM users WHERE school = ? AND role = 'program_chair' AND id != ?";
            $stmt_pc = mysqli_prepare($conn, $check_pc_query);
            mysqli_stmt_bind_param($stmt_pc, "si", $school, $user_id);
            mysqli_stmt_execute($stmt_pc);
            mysqli_stmt_store_result($stmt_pc);

            if (mysqli_stmt_num_rows($stmt_pc) > 0) {
                $error = "A Program Chair already exists for the " . htmlspecialchars($school) . " school. You can only have one per school.";
            }
            mysqli_stmt_close($stmt_pc);
        }

        // If no error was found, proceed with the update
        if (empty($error)) {
            $query = "UPDATE users SET role=? WHERE id=?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "si", $new_role, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Role updated successfully!";
            } else {
                $error = "Error updating role: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error = "Please select a school and a user.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Roles - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
            <h2>ICA Tracker</h2>
            <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> <span>Manage Teachers</span></a>
            <a href="create_classes.php"><i class="fas fa-layer-group"></i> <span>Create Classes</span></a>
            <a href="create_subjects.php"><i class="fas fa-book"></i> <span>Create Subjects</span></a>
            <a href="assign_teachers.php"><i class="fas fa-user-tag"></i> <span>Assign Teachers</span></a>
            <a href="manage_electives.php"><i class="fas fa-user-friends"></i> <span>Manage Electives</span></a>
            <a href="change_roles.php" class="active"><i class="fas fa-user-cog"></i> <span>Change Roles</span></a>
            <a href="bulk_add_students.php"><i class="fas fa-file-upload"></i> <span>Add Students</span></a>
                        <a href="manage_academic_calendar.php"><i class="fas fa-calendar-alt"></i> <span>Academic Calendar</span></a>

            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
        <div class="main-content">
            <div class="header">
                <h2>Welcome, <?php echo htmlspecialchars($adminNameDisplay !== '' ? $adminNameDisplay : $adminNameRaw); ?>!</h2>
            </div>
            <div class="container">
                <div class="card">
                    <div class="card-header"><h5>Change User Role</h5></div>
                    <div class="card-body">
                        <?php if ($error) echo "<p style='color: #d32f2f; font-weight: bold;'>$error</p>"; ?>
                        <?php if ($success) echo "<p style='color: #388e3c; font-weight: bold;'>$success</p>"; ?>
                        <form method="POST">
                            <div class="form-group">
                                <label>Select School</label>
                                <select name="school" id="school" required>
                                    <option value="">Select School</option>
                                    <option value="STME">STME</option>
                                    <option value="SOL">SOL</option>
                                    <option value="SPTM">SPTM</option>
                                    <option value="SBM">SBM</option>
                                    <option value="SOC">SOC</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Select User</label>
                                <select name="user_id" id="user_id" required>
                                    <option value="">-- Select School First --</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Select New Role</label>
                                <select name="new_role" required>
                                    <option value="">Select New Role</option>
                                    <option value="teacher">Teacher</option>
                                    <option value="admin">Admin</option>
                                    <option value="program_chair">Program Chair</option>
                                </select>
                            </div>
                            <button type="submit" class="btn">Change Role</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        }
        if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');

        document.getElementById('school').addEventListener('change', function() {
            const school = this.value;
            const userSelect = document.getElementById('user_id');
            
                userSelect.innerHTML = '<option value="">Loading...</option>';

            if (school) {
                fetch('get_users.php?school=' + school)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        userSelect.innerHTML = '<option value="">-- Select User --</option>';
                        if (data.length > 0) {
                            data.forEach(user => {
                                const option = document.createElement('option');
                                option.value = user.id;
                                const displayLabel = user.name_display && user.name_display !== '' ? user.name_display : user.name;
                                option.textContent = displayLabel + ' (' + user.role + ')';
                                userSelect.appendChild(option);
                            });
                        } else {
                             userSelect.innerHTML = '<option value="">No users found in this school</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching users:', error);
                        userSelect.innerHTML = '<option value="">Error loading users</option>';
                        alert('An error occurred while fetching the user list. Make sure the get_users.php file exists and has no errors.');
                    });
            } else {
                userSelect.innerHTML = '<option value="">-- Select School First --</option>';
            }
        });
    </script>
</body>
</html>

