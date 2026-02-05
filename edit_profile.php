<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];
$message = '';
$error = '';

// Load user row
$user_q = mysqli_prepare($conn, "SELECT id, username, email, name, school, teacher_unique_id, role FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($user_q, 'i', $user_id);
mysqli_stmt_execute($user_q);
$user_res = mysqli_stmt_get_result($user_q);
$user = mysqli_fetch_assoc($user_res) ?: null;
mysqli_stmt_close($user_q);

// For students, try to load students table row by sap_id (username)
$student = null;
if ($user && $role === 'student') {
    $sap = $user['username'];
    $stu_q = mysqli_prepare($conn, "SELECT id, sap_id, name, roll_number, class_id, section_id, college_email FROM students WHERE sap_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stu_q, 's', $sap);
    mysqli_stmt_execute($stu_q);
    $stu_res = mysqli_stmt_get_result($stu_q);
    $student = mysqli_fetch_assoc($stu_res) ?: null;
    mysqli_stmt_close($stu_q);
}

$studentClassName = '';
$studentSectionName = '';
if ($role === 'student' && $student) {
    if (!empty($student['class_id'])) {
        $class_stmt = mysqli_prepare($conn, "SELECT class_name FROM classes WHERE id = ? LIMIT 1");
        if ($class_stmt) {
            mysqli_stmt_bind_param($class_stmt, 'i', $student['class_id']);
            mysqli_stmt_execute($class_stmt);
            $class_res = mysqli_stmt_get_result($class_stmt);
            if ($class_res && ($class_row = mysqli_fetch_assoc($class_res))) {
                $studentClassName = $class_row['class_name'] ?? '';
            }
            if ($class_res) {
                mysqli_free_result($class_res);
            }
            mysqli_stmt_close($class_stmt);
        }
    }
    if (!empty($student['section_id'])) {
        $section_stmt = mysqli_prepare($conn, "SELECT section_name FROM sections WHERE id = ? LIMIT 1");
        if ($section_stmt) {
            mysqli_stmt_bind_param($section_stmt, 'i', $student['section_id']);
            mysqli_stmt_execute($section_stmt);
            $section_res = mysqli_stmt_get_result($section_stmt);
            if ($section_res && ($section_row = mysqli_fetch_assoc($section_res))) {
                $studentSectionName = $section_row['section_name'] ?? '';
            }
            if ($section_res) {
                mysqli_free_result($section_res);
            }
            mysqli_stmt_close($section_stmt);
        }
    }
}

// handle POST update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    if (!$user) {
        $error = 'User not found.';
    } else {
        if ($role === 'student') {
            $full_name = trim($_POST['full_name'] ?? '');
            $sap_id = trim($_POST['sap_id'] ?? '');
            $roll = trim($_POST['roll_number'] ?? '');
            $college_email = trim($_POST['college_email'] ?? '');

            if ($college_email === '') {
                $error = 'College email is required.';
            } elseif (!preg_match('/^[^\s@]+@(?:nmims\.in|nmims\.edu(?:\.in)?)$/i', $college_email)) {
                $error = 'Please enter a valid NMIMS college email (nmims.in, nmims.edu, nmims.edu.in).';
            }

            // update users table (username and name)
            if ($error === '') {
                $stmt_u = mysqli_prepare($conn, "UPDATE users SET username = ?, name = ?, email = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt_u, 'sssi', $sap_id, $full_name, $college_email, $user_id);
                if (!mysqli_stmt_execute($stmt_u)) {
                    $error = 'Failed updating user: ' . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt_u);
            }

            // update students table
            if ($error === '') {
                if ($student) {
                    $stmt_s = mysqli_prepare($conn, "UPDATE students SET sap_id = ?, name = ?, roll_number = ?, college_email = ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt_s, 'ssssi', $sap_id, $full_name, $roll, $college_email, $student['id']);
                    if (!mysqli_stmt_execute($stmt_s)) {
                        $error = 'Failed updating student record: ' . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt_s);
                } else {
                    $stmt_ins = mysqli_prepare($conn, "INSERT INTO students (sap_id, name, roll_number, college_email) VALUES (?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt_ins, 'ssss', $sap_id, $full_name, $roll, $college_email);
                    if (!mysqli_stmt_execute($stmt_ins)) {
                        $error = 'Failed creating student record: ' . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt_ins);
                }
            }

            // optional password change
            if ($error === '' && !empty($_POST['new_password'])) {
                $pwd = $_POST['new_password'];
                $hash = password_hash($pwd, PASSWORD_DEFAULT);
                $pwd_q = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
                mysqli_stmt_bind_param($pwd_q, 'si', $hash, $user_id);
                if (!mysqli_stmt_execute($pwd_q)) {
                    $error = 'Failed updating password.';
                }
                mysqli_stmt_close($pwd_q);
            }

            if ($error === '') $message = 'Profile updated successfully.';

        } else {
            // teacher / program_chair or other user types
            $username = trim($_POST['username'] ?? $user['username']);
            $email = trim($_POST['email'] ?? $user['email']);
            $name = trim($_POST['name'] ?? $user['name']);
            $school = trim($_POST['school'] ?? $user['school']);
            $teacher_unique_id = trim($_POST['teacher_unique_id'] ?? $user['teacher_unique_id']);

            $stmt = null;
            if (!empty($_POST['new_password'])) {
                $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE users SET username = ?, email = ?, name = ?, school = ?, teacher_unique_id = ?, password = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, 'ssssssi', $username, $email, $name, $school, $teacher_unique_id, $hash, $user_id);
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE users SET username = ?, email = ?, name = ?, school = ?, teacher_unique_id = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, 'sssssi', $username, $email, $name, $school, $teacher_unique_id, $user_id);
            }

            if ($stmt) {
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Profile updated successfully.';
                    // update session name if changed
                    $_SESSION['name'] = $name;
                } else {
                    $error = 'Error updating profile: ' . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            } else {
                $error = 'Could not prepare update statement.';
            }
        }
    }

    // refresh user data
    $user_q = mysqli_prepare($conn, "SELECT id, username, email, name, school, teacher_unique_id, role FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($user_q, 'i', $user_id);
    mysqli_stmt_execute($user_q);
    $user_res = mysqli_stmt_get_result($user_q);
    $user = mysqli_fetch_assoc($user_res) ?: null;
    mysqli_stmt_close($user_q);

    if ($role === 'student') {
        $sap = $user['username'];
        $stu_q = mysqli_prepare($conn, "SELECT id, sap_id, name, roll_number, class_id, section_id, college_email FROM students WHERE sap_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stu_q, 's', $sap);
        mysqli_stmt_execute($stu_q);
        $stu_res = mysqli_stmt_get_result($stu_q);
        $student = mysqli_fetch_assoc($stu_res) ?: null;
        mysqli_stmt_close($stu_q);
        if ($student) {
            $studentClassName = '';
            $studentSectionName = '';
            if (!empty($student['class_id'])) {
                $class_stmt = mysqli_prepare($conn, "SELECT class_name FROM classes WHERE id = ? LIMIT 1");
                if ($class_stmt) {
                    mysqli_stmt_bind_param($class_stmt, 'i', $student['class_id']);
                    mysqli_stmt_execute($class_stmt);
                    $class_res = mysqli_stmt_get_result($class_stmt);
                    if ($class_res && ($class_row = mysqli_fetch_assoc($class_res))) {
                        $studentClassName = $class_row['class_name'] ?? '';
                    }
                    if ($class_res) {
                        mysqli_free_result($class_res);
                    }
                    mysqli_stmt_close($class_stmt);
                }
            }
            if (!empty($student['section_id'])) {
                $section_stmt = mysqli_prepare($conn, "SELECT section_name FROM sections WHERE id = ? LIMIT 1");
                if ($section_stmt) {
                    mysqli_stmt_bind_param($section_stmt, 'i', $student['section_id']);
                    mysqli_stmt_execute($section_stmt);
                    $section_res = mysqli_stmt_get_result($section_stmt);
                    if ($section_res && ($section_row = mysqli_fetch_assoc($section_res))) {
                        $studentSectionName = $section_row['section_name'] ?? '';
                    }
                    if ($section_res) {
                        mysqli_free_result($section_res);
                    }
                    mysqli_stmt_close($section_stmt);
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Edit Profile - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body<?php echo $role === 'program_chair' ? ' class="program-chair"' : ''; ?>>
    <div class="dashboard">
        <div class="sidebar">
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
            <h2>ICA Tracker</h2>
            <?php if ($role === 'teacher'): ?>
                <a href="teacher_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
                <a href="update_progress.php"><i class="fas fa-chart-line"></i> <span>Update Progress</span></a>
                <a href="create_ica_components.php"><i class="fas fa-cogs"></i> <span>ICA Components</span></a>
                <a href="manage_ica_marks.php"><i class="fas fa-book"></i> <span>Manage ICA Marks</span></a>
                <a href="assignments.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a>
                <a href="view_alerts.php"><i class="fas fa-bell"></i> <span>View Alerts</span></a>
                <a href="view_reports.php"><i class="fas fa-file-alt"></i> <span>View Reports</span></a>
                <a href="timetable.php"><i class="fas fa-calendar-alt"></i> <span>Timetable</span></a>
                <a href="edit_profile.php" class="active"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            <?php elseif ($role === 'program_chair'): ?>
                <a href="program_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
                <a href="teacher_progress.php"><i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span></a>
                <a href="student_progress.php"><i class="fas fa-user-graduate"></i> <span>Students</span></a>
                <a href="course_progress.php"><i class="fas fa-book"></i> <span>Courses</span></a>
                <a href="program_reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
                <a href="send_alerts.php"><i class="fas fa-bell"></i> <span>Alerts</span></a>
                <a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
                <a href="edit_profile.php" class="active"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            <?php else: ?>
                <a href="student_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
                <a href="view_marks.php"><i class="fas fa-chart-line"></i> <span>Marks</span></a>
                <a href="subject_comparison.php"><i class="fas fa-balance-scale"></i> <span>Subject Comparison</span></a>
                <a href="view_assignment_marks.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a>
                <a href="view_timetable.php"><i class="fas fa-calendar-alt"></i> <span>Timetable</span></a>
                <a href="view_progress.php"><i class="fas fa-book"></i> <span>Syllabus Progress</span></a>
                <a href="edit_profile.php" class="active"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            <?php endif; ?>
        </div>
        <div class="main-content">
            <div class="header">
                <div>
                    <h2>Edit Profile</h2>
                    <p style="margin:4px 0 0;color:#555;font-size:0.95rem;">Review and update your account information.</p>
                </div>
            </div>
            <div class="container">
                <div class="card">
                  
                    <div class="card-body">
                        <?php if ($error): ?><div style="color:#d32f2f;font-weight:600;padding:8px;"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                        <?php if ($message): ?><div style="color:#388e3c;font-weight:600;padding:8px;"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

                        <form method="POST">
                            <?php if ($role === 'student'): ?>
                                <div class="form-group">
                                    <label>Full name (e.g., John Doe)</label>
                                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>SAP ID</label>
                                    <input type="text" name="sap_id" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Roll Number</label>
                                    <input type="text" name="roll_number" value="<?php echo htmlspecialchars($student['roll_number'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Class</label>
                                    <input type="text" value="<?php echo htmlspecialchars($studentClassName ?: 'Not Assigned'); ?>" disabled>
                                </div>
                                <div class="form-group">
                                    <label>Section</label>
                                    <input type="text" value="<?php echo htmlspecialchars($studentSectionName ?: 'Not Assigned'); ?>" disabled>
                                </div>
                                <div class="form-group">
                                    <label>College Email (NMIMS only)</label>
                                    <input type="email" name="college_email" value="<?php echo htmlspecialchars($student['college_email'] ?? $user['email'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>New Password (leave blank to keep current)</label>
                                    <input type="password" name="new_password">
                                </div>
                            <?php else: ?>
                                <div class="form-group">
                                    <label>Username (editable)</label>
                                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Email (e.g., name@college.edu)</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Full name (e.g., John Doe)</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>What is the teacher's unique ID? (e.g., EMP123)</label>
                                    <input type="text" name="teacher_unique_id" value="<?php echo htmlspecialchars($user['teacher_unique_id'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Which School/Department?</label>
                                    <select name="school">
                                        <option value="">Select School</option>
                                        <option value="STME" <?php if (($user['school'] ?? '')==='STME') echo 'selected'; ?>>STME</option>
                                        <option value="SOL" <?php if (($user['school'] ?? '')==='SOL') echo 'selected'; ?>>SOL</option>
                                        <option value="SPTM" <?php if (($user['school'] ?? '')==='SPTM') echo 'selected'; ?>>SPTM</option>
                                        <option value="SBM" <?php if (($user['school'] ?? '')==='SBM') echo 'selected'; ?>>SBM</option>
                                        <option value="SOC" <?php if (($user['school'] ?? '')==='SOC') echo 'selected'; ?>>SOC</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>New Password (leave blank to keep current)</label>
                                    <input type="password" name="new_password">
                                </div>
                            <?php endif; ?>

                            <div style="margin-top:12px;">
                                <button type="submit" name="save_profile" class="btn">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>

