<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';
$adminNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$adminNameDisplay = $adminNameRaw !== '' ? format_person_display($adminNameRaw) : 'ADMIN';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_section'])) {
    $class_id = (int)$_POST['class_id'];
    $section_name = trim(mysqli_real_escape_string($conn, $_POST['section_name']));

    if ($class_id > 0 && !empty($section_name)) {
        $check_q = "SELECT id FROM sections WHERE class_id = ? AND section_name = ?";
        $stmt_check = mysqli_prepare($conn, $check_q);
        mysqli_stmt_bind_param($stmt_check, "is", $class_id, $section_name);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);

        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $error = "This division/section already exists for the selected class.";
        } else {
            $insert_q = "INSERT INTO sections (class_id, section_name) VALUES (?, ?)";
            $stmt_insert = mysqli_prepare($conn, $insert_q);
            mysqli_stmt_bind_param($stmt_insert, "is", $class_id, $section_name);
            if (mysqli_stmt_execute($stmt_insert)) {
                $success = "Division '{$section_name}' added successfully!";
            } else {
                $error = "Error adding division.";
            }
            mysqli_stmt_close($stmt_insert);
        }
        mysqli_stmt_close($stmt_check);
    } else {
        $error = "Please select a class and provide a division name.";
    }
}

$classes_query = "SELECT c.id,
        c.class_name,
        c.semester,
        c.school,
        COALESCE(GROUP_CONCAT(DISTINCT sec.section_name ORDER BY sec.section_name SEPARATOR '/'), '') AS divisions
    FROM classes c
    LEFT JOIN sections sec ON sec.class_id = c.id
    GROUP BY c.id, c.class_name, c.semester, c.school
    ORDER BY c.class_name";
$classes_result = mysqli_query($conn, $classes_query);

$sections_query = "SELECT s.id,
        s.section_name,
        c.class_name,
        c.semester,
        c.school
    FROM sections s
    JOIN classes c ON s.class_id = c.id
    ORDER BY c.class_name, s.section_name";
$sections_result = mysqli_query($conn, $sections_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Divisions/Sections</title>
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
            <a href="manage_sections.php" class="active"><i class="fas fa-sitemap"></i> <span>Manage Divisions</span></a>
            <a href="create_subjects.php"><i class="fas fa-book"></i> <span>Create Subjects</span></a>
            <a href="assign_teachers.php"><i class="fas fa-user-tag"></i> <span>Assign Teachers</span></a>
            <a href="manage_electives.php"><i class="fas fa-user-friends"></i> <span>Manage Electives</span></a>
            <a href="change_roles.php"><i class="fas fa-user-cog"></i> <span>Change Roles</span></a>
            <a href="bulk_add_students.php"><i class="fas fa-user-plus"></i> <span>Add Students</span></a>
                        <a href="manage_academic_calendar.php"><i class="fas fa-calendar-alt"></i> <span>Academic Calendar</span></a>

            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
        <div class="main-content">
            <div class="header">
                <h2>Welcome, <?php echo htmlspecialchars($adminNameDisplay); ?>!</h2>
            </div>
            <div class="container">
                <?php if ($error) echo "<div class='card' style='color: #d32f2f; font-weight: bold; padding: 15px;'>$error</div>"; ?>
                <?php if ($success) echo "<div class='card' style='color: #388e3c; font-weight: bold; padding: 15px;'>$success</div>"; ?>

                <div class="card">
                    <div class="card-header"><h5>Add New Division/Section</h5></div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group"><label>Select Class</label>
                                <select name="class_id" required>
                                    <option value="">-- Select a Class --</option>
                                    <?php while($class = mysqli_fetch_assoc($classes_result)): ?>
                                        <?php
                                            $classLabel = format_class_label(
                                                $class['class_name'] ?? '',
                                                $class['divisions'] ?? '',
                                                $class['semester'] ?? '',
                                                $class['school'] ?? ''
                                            );
                                            if ($classLabel === '') {
                                                $classLabel = format_subject_display($class['class_name'] ?? '');
                                            }
                                        ?>
                                        <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($classLabel); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group"><label>Division/Section Name (e.g., A, B, C)</label><input type="text" name="section_name" required></div>
                            <button type="submit" name="add_section" class="btn">Add Division</button>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h5>Existing Divisions</h5></div>
                    <div class="card-body">
                        <table><thead><tr><th>Class Name</th><th>Division/Section</th></tr></thead>
                            <tbody>
                                <?php while($section = mysqli_fetch_assoc($sections_result)): ?>
                                <?php
                                    $sectionClassLabel = format_class_label(
                                        $section['class_name'] ?? '',
                                        $section['section_name'] ?? '',
                                        $section['semester'] ?? '',
                                        $section['school'] ?? ''
                                    );
                                    if ($sectionClassLabel === '') {
                                        $sectionClassLabel = format_subject_display($section['class_name'] ?? '');
                                    }
                                    $sectionLabelDisplay = $section['section_name'] !== '' ? format_subject_display($section['section_name']) : 'N/A';
                                ?>
                                <tr><td><?php echo htmlspecialchars($sectionClassLabel); ?></td><td><?php echo htmlspecialchars($sectionLabelDisplay); ?></td></tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function toggleTheme() { document.body.classList.toggle('dark-mode'); localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light'); }
        if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');
    </script>
</body>
</html>
