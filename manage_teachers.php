<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Handle toggling teacher status
if (isset($_POST['toggle_status'])) {
    $user_id = (int)$_POST['user_id'];
    $current_status = $_POST['current_status'];
    $new_status = ($current_status == 'active') ? 'inactive' : 'active';

    $stmt = mysqli_prepare($conn, "UPDATE users SET status = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_status, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        if ($new_status === 'inactive') {
            $success = "Teacher account deactivated. They can no longer log in.";
        } else {
            $success = "Teacher account reactivated.";
        }
    } else {
        $error = "Error updating status: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Handle editing an existing teacher
if (isset($_POST['edit_teacher'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    $username = mysqli_real_escape_string($conn, $_POST['username_edit'] ?? '');
    $email = mysqli_real_escape_string($conn, $_POST['email_edit']);
    $name = mysqli_real_escape_string($conn, $_POST['name_edit']);
    $school = mysqli_real_escape_string($conn, $_POST['school_edit']);
    $teacher_unique_id = mysqli_real_escape_string($conn, $_POST['teacher_unique_id_edit'] ?? '');

    if ($username === '' && $teacher_unique_id !== '') {
        $username = $teacher_unique_id;
    }

    if ($teacher_id > 0) {
        $emailValid = filter_var($email, FILTER_VALIDATE_EMAIL);
        if (!$emailValid) {
            $error = "Teacher email must be a valid email address.";
        }

        if ($teacher_unique_id === '') {
            $error = $error ? $error . ' ' : '';
            $error .= "Teacher unique ID is required.";
        }

        if ($username === '') {
            $error = $error ? $error . ' ' : '';
            $error .= "Username is required.";
        }

        if (!$error) {
            $checkStmt = mysqli_prepare($conn, "SELECT id FROM users WHERE (username = ? OR teacher_unique_id = ?) AND id != ? LIMIT 1");
            if ($checkStmt) {
                mysqli_stmt_bind_param($checkStmt, "ssi", $username, $teacher_unique_id, $teacher_id);
                mysqli_stmt_execute($checkStmt);
                mysqli_stmt_store_result($checkStmt);
                if (mysqli_stmt_num_rows($checkStmt) > 0) {
                    $error = "Another user already uses this username or teacher unique ID.";
                }
                mysqli_stmt_close($checkStmt);
            } else {
                $error = "Unable to validate teacher uniqueness.";
            }
        }

        if (!$error) {
            $stmt = mysqli_prepare($conn, "UPDATE users SET username = ?, email = ?, name = ?, school = ?, teacher_unique_id = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sssssi", $username, $email, $name, $school, $teacher_unique_id, $teacher_id);
            }

            if ($stmt) {
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Teacher details updated successfully!";
                } else {
                    $error = "Error updating teacher: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            } else {
                $error = "Unable to prepare statement for updating teacher.";
            }
        }
    } else {
        $error = "Invalid teacher selected for editing.";
    }
}

// Reset teacher/program chair password to default 123456
if (isset($_POST['reset_password'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    if ($user_id > 0) {
        $defaultHash = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $defaultHash, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Password reset to 123456. User must change it on next login.";
            } else {
                $error = "Unable to reset password right now.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "Unable to prepare password reset.";
        }
    } else {
        $error = "Invalid user selected for password reset.";
    }
}

// Handle adding a new teacher
if (isset($_POST['add_teacher'])) {
    $teacher_unique_id = mysqli_real_escape_string($conn, $_POST['teacher_unique_id'] ?? '');
    $username = $teacher_unique_id;
    $password = password_hash('123456', PASSWORD_DEFAULT);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $school = mysqli_real_escape_string($conn, $_POST['school']);
    if ($teacher_unique_id === '') {
        $error = "Teacher unique ID is required.";
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = $error ? $error . ' ' : '';
        $error .= "Teacher email must be valid.";
    }
    // By default, new teachers are 'active'
    $status = 'active'; 

    if (!$error) {
        $checkStmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? OR teacher_unique_id = ? LIMIT 1");
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, "ss", $username, $teacher_unique_id);
            mysqli_stmt_execute($checkStmt);
            mysqli_stmt_store_result($checkStmt);
            if (mysqli_stmt_num_rows($checkStmt) > 0) {
                $error = "Another user already uses this username or teacher unique ID.";
            }
            mysqli_stmt_close($checkStmt);
        } else {
            $error = "Unable to validate teacher uniqueness.";
        }
    }

    if (!$error) {
        $query = "INSERT INTO users (username, password, role, email, name, school, status, teacher_unique_id) VALUES (?, ?, 'teacher', ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssssss", $username, $password, $email, $name, $school, $status, $teacher_unique_id);
        if (mysqli_stmt_execute($stmt)) {
            $success = "Teacher added successfully!";
        } else {
            $error = "Error adding teacher: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch all users who are teachers or program chairs
$teachers_query = "SELECT id, username, name, email, school, role, status, teacher_unique_id FROM users WHERE role IN ('teacher', 'program_chair') ORDER BY id ASC";
$teachers_result = mysqli_query($conn, $teachers_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - ICA Tracker</title>
   
  <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">   <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        table {
            table-layout: fixed;
            width: 100%;
            border-collapse: collapse;
        }

        col.col-username { width: 14%; }
        col.col-name { width: 28%; }
        col.col-school { width: 12%; }
        col.col-unique { width: 16%; }
        col.col-role { width: 15%; }
        col.col-status { width: 9%; }
        col.col-actions { width: 6%; }

        th, td {
            padding: 10px 12px;
            text-align: left;
        }
        
        td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        td.name-cell {
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
        }

        .table-actions {
            display: flex;
            align-items: center;
        }

        .table-actions .action-btn {
            padding: 4px 14px;
            border: 1px solid #A6192E;
            border-radius: 8px;
            background: #fff;
            color: #A6192E;
            font-size: 0.85rem;
            font-weight: 600;
            transition: background 0.2s, color 0.2s;
        }

        .table-actions .action-btn:hover {
            background: #A6192E;
            color: #fff;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            color: #fff;
            font-size: 0.8em;
            font-weight: 600;
            text-align: center;
            display: inline-block;
            min-width: 60px;
        }

        .status-badge.active {
            background-color: #28a745;
        }

        .status-badge.inactive {
            background-color: #dc3545;
        }

        .account-actions {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #ddd;
        }

        .account-actions h6 {
            margin-bottom: 12px;
            font-size: 1rem;
        }

        .account-actions p {
            margin-bottom: 12px;
            color: #63666a;
        }

        .account-action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn-outline {
            background: #fff;
            color: #A6192E;
            border: 1px solid #A6192E;
        }

        .btn-outline:hover {
            background: #A6192E;
            color: #fff;
        }

        .btn-outline-secondary {
            border-color: #6c757d;
            color: #6c757d;
        }

        .btn-outline-secondary:hover {
            background: #6c757d;
            color: #fff;
        }

        .btn-outline-danger {
            border-color: #dc3545;
            color: #dc3545;
        }

        .btn-outline-danger:hover {
            background: #dc3545;
            color: #fff;
        }

        .btn-outline-success {
            border-color: #28a745;
            color: #28a745;
        }

        .btn-outline-success:hover {
            background: #28a745;
            color: #fff;
        }

        .card-header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .card-header-actions h5 {
            margin: 0;
        }

        #addTeacherBtn {
            padding: 6px 14px;
            font-size: 0.9rem;
        }

        .add-teacher-container {
            display: none;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #ddd;
        }
        
        .card-body .form-group {
            margin-bottom: 14px;
        }

        .card-body .form-group label {
            margin-bottom: 6px;
        }

        .card-body .form-group input,
        .card-body .form-group select {
            margin-bottom: 0;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 999;
        }

        .modal {
            background: #fff;
            padding: 20px;
            border-radius: 6px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.4rem;
            font-weight: 600;
            color: #A6192E;
            cursor: pointer;
        }

        .modal-close:hover {
            color: #7f1422;
        }

        .modal.show,
        .modal-overlay.show {
            display: flex;
        }

        body.modal-open {
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
            <h2>ICA Tracker</h2>
            <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            
            <a href="manage_teachers.php" class="active"><i class="fas fa-chalkboard-teacher"></i> <span>Manage Teachers</span></a>
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
                <h2>Manage Teachers</h2>
            </div>
            <div class="container">
                <div class="card">
                    <div class="card-header card-header-actions">
                        <h5>Teacher & Staff Management</h5>
                        <button id="addTeacherBtn" class="btn">
                            <i class="fas fa-plus"></i> Add New Teacher
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if ($error) echo "<p style='color: #d32f2f; font-weight: bold;'>$error</p>"; ?>
                        <?php if ($success) echo "<p style='color: #388e3c; font-weight: bold;'>$success</p>"; ?>
                        
                        <div id="addTeacherFormContainer" class="add-teacher-container">
                            <div class="card-header" style="padding-left: 0; background: transparent; border: none;"><h5>Add New Teacher</h5></div>
                            <form method="POST">
                                <div class="form-group">
                                    <label>Teacher unique ID</label>
                                    <input type="text" name="teacher_unique_id" required>
                                </div>
                                <div class="form-group">
                                    <label>Teacher email</label>
                                    <input type="email" name="email" required>
                                </div>
                                <div class="form-group">
                                    <label>Teacher full name</label>
                                    <input type="text" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label>Which School/Department will this teacher belong to?</label>
                                    <select name="school" required>
                                        <option value="">Select School</option>
                                        <option value="STME">STME</option>
                                        <option value="SOL">SOL</option>
                                        <option value="SPTM">SPTM</option>
                                        <option value="SBM">SBM</option>
                                        <option value="SOC">SOC</option>
                                    </select>
                                </div>
                                <p style="font-size:0.9rem; color:#63666a; margin-bottom:16px;">New faculty accounts start with password 123456 and must update it on first login.</p>
                                <button type="submit" name="add_teacher" class="btn">Add Teacher</button>
                            </form>
                        </div>

                        <table>
                            <colgroup>
                                <col class="col-username">
                                <col class="col-name">
                                <col class="col-school">
                                <col class="col-unique">
                                <col class="col-role">
                                <col class="col-status">
                                <col class="col-actions">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Name</th>
                                    <th>School</th>
                                    <th>Unique ID</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($teacher = mysqli_fetch_assoc($teachers_result)): ?>
                                <?php
                                    $teacherNameRaw = isset($teacher['name']) ? trim((string)$teacher['name']) : '';
                                    $teacherNameDisplay = $teacherNameRaw !== '' ? format_person_display($teacherNameRaw) : '';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                                    <td class="name-cell"><?php echo htmlspecialchars($teacherNameDisplay !== '' ? $teacherNameDisplay : $teacherNameRaw); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['school']); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['teacher_unique_id'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php 
                                            // If role is program_chair, display both roles
                                            if ($teacher['role'] == 'program_chair') {
                                                echo 'Program Chair, Teacher';
                                            } else {
                                                echo ucfirst($teacher['role']);
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $teacher['status']; ?>">
                                            <?php echo ucfirst($teacher['status']); ?>
                                        </span>
                                    </td>
                                    <td class="table-actions">
                                        <button type="button" class="btn action-btn action-edit edit-teacher-btn"
                                            data-id="<?php echo $teacher['id']; ?>"
                                            data-username="<?php echo htmlspecialchars($teacher['username'], ENT_QUOTES); ?>"
                                            data-name="<?php echo htmlspecialchars($teacher['name'], ENT_QUOTES); ?>"
                                            data-email="<?php echo htmlspecialchars($teacher['email'], ENT_QUOTES); ?>"
                                            data-school="<?php echo htmlspecialchars($teacher['school'], ENT_QUOTES); ?>"
                                            data-unique-id="<?php echo htmlspecialchars($teacher['teacher_unique_id'], ENT_QUOTES); ?>"
                                            data-status="<?php echo htmlspecialchars($teacher['status'], ENT_QUOTES); ?>"
                                            title="Edit Teacher">
                                            Edit
                                        </button>
                                        </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="editModalOverlay" class="modal-overlay">
        <div class="modal" id="editTeacherModal">
            <div class="modal-header">
                <h5>Edit Teacher</h5>
                <button class="modal-close" id="closeEditModal" aria-label="Close edit form">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="teacher_id" id="editTeacherId">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username_edit" id="editUsername" required>
                </div>
                <div class="form-group">
                    <label>Teacher email</label>
                    <input type="email" name="email_edit" id="editEmail" required>
                </div>
                <div class="form-group">
                    <label>Teacher full name</label>
                    <input type="text" name="name_edit" id="editName" required>
                </div>
                <div class="form-group">
                    <label>Teacher unique ID</label>
                    <input type="text" name="teacher_unique_id_edit" id="editUniqueId" required>
                </div>
                <div class="form-group">
                    <label>Which School/Department?</label>
                    <select name="school_edit" id="editSchool" required>
                        <option value="STME">STME</option>
                        <option value="SOL">SOL</option>
                        <option value="SPTM">SPTM</option>
                        <option value="SBM">SBM</option>
                        <option value="SOC">SOC</option>
                    </select>
                </div>
                <p style="font-size:0.9rem; color:#63666a;">Passwords reset automatically to 123456 when an account is recreated; faculty must change it on first login.</p>
                <button type="submit" name="edit_teacher" class="btn">Save Changes</button>
            </form>
            <div class="account-actions">
                <h6>Account Actions</h6>
                <p>Current status: <span id="editStatusBadge" class="status-badge">Active</span></p>
                <div class="account-action-buttons">
                    <form method="POST">
                        <input type="hidden" name="user_id" id="resetTeacherId">
                        <button type="submit" name="reset_password" class="btn btn-outline btn-outline-secondary">Reset Password</button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="user_id" id="statusTeacherId">
                        <input type="hidden" name="current_status" id="statusCurrentValue">
                        <button type="submit" name="toggle_status" id="statusToggleButton" class="btn btn-outline">Deactivate Account</button>
                    </form>
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

        // JavaScript to toggle the visibility of the Add Teacher form
        document.getElementById('addTeacherBtn').addEventListener('click', function() {
            var formContainer = document.getElementById('addTeacherFormContainer');
            if (formContainer.style.display === 'none' || formContainer.style.display === '') {
                formContainer.style.display = 'block';
                this.innerHTML = '<i class="fas fa-minus"></i> Hide Form';
            } else {
                formContainer.style.display = 'none';
                this.innerHTML = '<i class="fas fa-plus"></i> Add New Teacher';
            }
        });

        const editButtons = document.querySelectorAll('.edit-teacher-btn');
        const editModalOverlay = document.getElementById('editModalOverlay');
        const closeEditModal = document.getElementById('closeEditModal');
        const editTeacherId = document.getElementById('editTeacherId');
        const editUsername = document.getElementById('editUsername');
        const editEmail = document.getElementById('editEmail');
    const editName = document.getElementById('editName');
    const editUniqueId = document.getElementById('editUniqueId');
    const editSchool = document.getElementById('editSchool');
    const resetTeacherId = document.getElementById('resetTeacherId');
    const statusTeacherId = document.getElementById('statusTeacherId');
    const statusCurrentValue = document.getElementById('statusCurrentValue');
    const statusToggleButton = document.getElementById('statusToggleButton');
    const editStatusBadge = document.getElementById('editStatusBadge');

        function hideEditModal() {
            editModalOverlay.style.display = 'none';
            document.body.classList.remove('modal-open');
        }

        function openEditModal(button) {
            editTeacherId.value = button.dataset.id;
            editUsername.value = button.dataset.username;
            editEmail.value = button.dataset.email;
            editName.value = button.dataset.name;
            editUniqueId.value = button.dataset.uniqueId || '';
            editSchool.value = button.dataset.school;
            resetTeacherId.value = button.dataset.id;
            statusTeacherId.value = button.dataset.id;
            const currentStatus = (button.dataset.status || 'inactive').toLowerCase();
            statusCurrentValue.value = currentStatus;
            const isActive = currentStatus === 'active';
            statusToggleButton.textContent = isActive ? 'Deactivate Account' : 'Activate Account';
            statusToggleButton.classList.remove('btn-outline-danger', 'btn-outline-success');
            statusToggleButton.classList.add(isActive ? 'btn-outline-danger' : 'btn-outline-success');
            editStatusBadge.textContent = isActive ? 'Active' : 'Inactive';
            editStatusBadge.classList.remove('active', 'inactive');
            editStatusBadge.classList.add(isActive ? 'active' : 'inactive');
            editModalOverlay.style.display = 'flex';
            document.body.classList.add('modal-open');
        }

        editButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                openEditModal(this);
            });
        });

        closeEditModal.addEventListener('click', hideEditModal);
        editModalOverlay.addEventListener('click', function(event) {
            if (event.target === editModalOverlay) {
                hideEditModal();
            }
        });
    </script>
</body>
</html>
