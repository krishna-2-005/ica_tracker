<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];
$teacherNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$teacherNameDisplay = $teacherNameRaw !== '' ? format_person_display($teacherNameRaw) : 'FACULTY';

// Fetch syllabus progress
$progress_query = "SELECT s.subject_name, sp.topic, sp.completion_percentage 
                   FROM syllabus_progress sp 
                   JOIN subjects s ON sp.subject = s.subject_name 
                   JOIN teacher_subject_assignments tsa ON s.id = tsa.subject_id 
                   WHERE tsa.teacher_id = ?";
$stmt = mysqli_prepare($conn, $progress_query);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$progress_result = mysqli_stmt_get_result($stmt);

// Generate PDF
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
    $format_segment = static function (string $value): string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $value = str_replace(['/', '\\'], '-', $value);
        $value = preg_replace('/[^A-Za-z0-9-]+/', '_', $value);
        $value = preg_replace('/_+/', '_', $value);
        return trim($value, '_');
    };

    $teacher_segment = $teacherNameRaw !== '' ? $format_segment($teacherNameRaw) : '';
    $date_segment = date('Y-m-d');
    $filename = $teacher_segment !== '' ? $teacher_segment . '_Syllabus_Progress_' . $date_segment . '.pdf' : 'Syllabus_Progress_' . $date_segment . '.pdf';

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = "%PDF-1.4\n";
    $output .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $output .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $output .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
    $output .= "4 0 obj\n<< /Length 6 0 R >>\nstream\n";
    $output .= "BT /F1 16 Tf 50 800 Td (Syllabus Progress Report) Tj ET\n";
    $output .= "BT /F1 12 Tf 50 770 Td (Subject) Tj 200 770 Td (Topic) Tj 350 770 Td (Completion %) Tj ET\n";
    
    $y = 750;
    while ($row = mysqli_fetch_assoc($progress_result)) {
        $subject = htmlspecialchars($row['subject_name']);
        $topic = htmlspecialchars($row['topic'] ?: 'N/A');
        $completion = round($row['completion_percentage'], 2);
        $output .= "BT /F1 12 Tf 50 $y Td ($subject) Tj 200 $y Td ($topic) Tj 350 $y Td ($completion%) Tj ET\n";
        $y -= 20;
    }
    mysqli_data_seek($progress_result, 0); // Reset result pointer for HTML display

    $output .= "endstream\nendobj\n";
    $output .= "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n";
    $output .= "6 0 obj\n" . strlen($output) . "\nendobj\n";
    $output .= "xref\n0 7\n0000000000 65535 f \n0000000010 00000 n \n0000000060 00000 n \n0000000110 00000 n \n0000000200 00000 n \n0000000400 00000 n \n0000000500 00000 n \ntrailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n600\n%%EOF";

    echo $output;
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Reports - ICA Tracker</title>
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
            <h2>ICA Tracker</h2>
            <a href="teacher_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="update_progress.php"><i class="fas fa-chart-line"></i> <span>Update Progress</span></a>
            <a href="create_ica_components.php"><i class="fas fa-cogs"></i> <span>ICA Components</span></a>
            <a href="manage_ica_marks.php"><i class="fas fa-book"></i> <span>Manage ICA Marks</span></a>
            <a href="assignments.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a>
            <a href="view_alerts.php"><i class="fas fa-bell"></i> <span>View Alerts</span></a>
            <a href="view_reports.php" class="active"><i class="fas fa-file-alt"></i> <span>View Reports</span></a>
            <a href="timetable.php"><i class="fas fa-calendar-alt"></i> <span>Timetable</span></a>
            <a href="edit_profile.php"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
        <div class="main-content">
            <div class="header">
                <h2>Welcome, <?php echo htmlspecialchars($teacherNameDisplay); ?>!</h2>
            </div>
            <div class="container">
                <div class="card">
                    <div class="card-header"><h5>Syllabus Progress Report</h5></div>
                    <div class="card-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Topic</th>
                                    <th>Completion (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($progress_result)) { ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['topic'] ?: 'N/A'); ?></td>
                                        <td><?php echo round($row['completion_percentage'], 2); ?>%</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <a href="?export=pdf" class="btn">Export to PDF</a>
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
    </script>
</body>
</html>
