<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
header('Location: login.php');
exit;
}

$userId = (int)$_SESSION['user_id'];
$studentSap = $_SESSION['unique_id'] ?? null;
if ($studentSap === null) {
$sapStmt = mysqli_prepare($conn, 'SELECT username FROM users WHERE id = ? LIMIT 1');
if ($sapStmt) {
mysqli_stmt_bind_param($sapStmt, 'i', $userId);
mysqli_stmt_execute($sapStmt);
$sapRes = mysqli_stmt_get_result($sapStmt);
if ($sapRow = mysqli_fetch_assoc($sapRes)) {
$studentSap = $sapRow['username'];
}
mysqli_stmt_close($sapStmt);
}
}

if (!$studentSap) {
echo 'Unable to determine your student record. Please contact the administrator.';
mysqli_close($conn);
exit;
}

$studentSql = "SELECT s.id, s.class_id, s.section_id, s.sap_id,
      c.class_name, c.semester, c.school,
      sec.section_name
       FROM students s
       LEFT JOIN classes c ON s.class_id = c.id
       LEFT JOIN sections sec ON s.section_id = sec.id
       WHERE s.sap_id = ? LIMIT 1";
$stmtStudent = mysqli_prepare($conn, $studentSql);
$studentInfo = null;
if ($stmtStudent) {
mysqli_stmt_bind_param($stmtStudent, 's', $studentSap);
mysqli_stmt_execute($stmtStudent);
$studentRes = mysqli_stmt_get_result($stmtStudent);
$studentInfo = mysqli_fetch_assoc($studentRes) ?: null;
}

if (!$studentInfo) {
echo 'Student record not found.';
if ($stmtStudent) {
mysqli_stmt_close($stmtStudent);
}
mysqli_close($conn);
exit;
}

$classId = $studentInfo['class_id'] ? (int)$studentInfo['class_id'] : null;
$className = $studentInfo['class_name'] ?? 'N/A';
$sectionName = $studentInfo['section_name'] ?? 'N/A';
$semesterLabel = $studentInfo['semester'] ?? 'N/A';
$schoolLabel = $studentInfo['school'] ?? 'N/A';

$progressRecords = [];
$averageCompletion = null;
$subjectsBehind = 0;
$subjectsOnTrack = 0;
$lastProgressUpdate = null;

$stmtProgress = null;
if ($classId) {
$progressSql = "SELECT s.subject_name,
   sp.topic,
   sp.timeline,
   sp.modules_completed,
   sp.planned_hours,
   sp.actual_hours,
   sp.completion_percentage,
   sp.updated_at,
   u.name AS teacher_name
FROM syllabus_progress sp
INNER JOIN (
SELECT subject, teacher_id, MAX(updated_at) AS latest_update
FROM syllabus_progress
GROUP BY subject, teacher_id
) latest ON latest.subject = sp.subject
AND latest.teacher_id = sp.teacher_id
AND latest.latest_update = sp.updated_at
INNER JOIN subjects s ON s.subject_name = sp.subject
INNER JOIN teacher_subject_assignments tsa ON tsa.subject_id = s.id AND tsa.teacher_id = sp.teacher_id
LEFT JOIN users u ON u.id = sp.teacher_id
WHERE tsa.class_id = ?
ORDER BY s.subject_name";
$stmtProgress = mysqli_prepare($conn, $progressSql);
if ($stmtProgress) {
mysqli_stmt_bind_param($stmtProgress, 'i', $classId);
mysqli_stmt_execute($stmtProgress);
$progRes = mysqli_stmt_get_result($stmtProgress);
while ($row = mysqli_fetch_assoc($progRes)) {
$teacherNameRaw = isset($row['teacher_name']) ? trim((string)$row['teacher_name']) : '';
$row['teacher_name_display'] = $teacherNameRaw !== '' ? format_person_display($teacherNameRaw) : 'NOT ASSIGNED';
$progressRecords[] = $row;
$pct = (float)($row['completion_percentage'] ?? 0);
if ($pct < 50) {
$subjectsBehind++;
} else {
$subjectsOnTrack++;
}
if (!empty($row['updated_at'])) {
$ts = strtotime($row['updated_at']);
if ($ts && ($lastProgressUpdate === null || $ts > $lastProgressUpdate)) {
$lastProgressUpdate = $ts;
}
}
}
mysqli_stmt_close($stmtProgress);
$stmtProgress = null;
}
}

if (!empty($progressRecords)) {
$totalPct = 0;
foreach ($progressRecords as $record) {
$totalPct += (float)($record['completion_percentage'] ?? 0);
}
$averageCompletion = round($totalPct / count($progressRecords), 1);
}

if ($stmtStudent) {
mysqli_stmt_close($stmtStudent);
}
if ($stmtProgress) {
mysqli_stmt_close($stmtProgress);
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Syllabus Progress - ICA Tracker</title>
<link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
<link rel="stylesheet" href="ica_tracker.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<div class="dashboard">
<div class="sidebar">
<h2>ICA Tracker</h2>
<a href="student_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
<a href="view_marks.php"><i class="fas fa-chart-line"></i> <span>Marks</span></a>
<a href="subject_comparison.php"><i class="fas fa-balance-scale"></i> <span>Subject Comparison</span></a>
<a href="view_assignment_marks.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a>
<a href="view_timetable.php"><i class="fas fa-calendar-alt"></i> <span>Timetable</span></a>
<a href="view_progress.php" class="active"><i class="fas fa-book"></i> <span>Syllabus Progress</span></a>
<a href="edit_profile.php"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
<a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
</div>
<div class="main-content">
<div class="header">
<div>
<h2>Syllabus Progress</h2>
<p>Monitor how each subject advances through the syllabus.</p>
</div>
<?php if ($lastProgressUpdate): ?>
<span class="tag">Last update: <?php echo date('d M Y', $lastProgressUpdate); ?></span>
<?php endif; ?>
</div>
<div class="container">
<div class="card-grid">
<div class="card stat-card">
<span class="stat-label">Average completion</span>
<h3><?php echo $averageCompletion !== null ? $averageCompletion . '%': 'N/A'; ?></h3>
<p>Across all tracked subjects.</p>
</div>
<div class="card stat-card<?php echo $subjectsOnTrack > 0 ? ' success' : ''; ?>">
<span class="stat-label">Subjects on track</span>
<h3><?php echo $subjectsOnTrack; ?></h3>
<p><?php echo $subjectsOnTrack > 0 ? 'Meeting or exceeding 50% completion.' : 'No subjects are on track yet.'; ?></p>
</div>
<div class="card stat-card<?php echo $subjectsBehind > 0 ? '' : ' success'; ?>">
<span class="stat-label">Needs attention</span>
<h3><?php echo $subjectsBehind; ?></h3>
<p><?php echo $subjectsBehind > 0 ? 'Below the 50% completion mark.' : 'All subjects are on pace.'; ?></p>
</div>
</div>

<div class="card">
<h3 class="section-title">Class snapshot</h3>
<div class="info-list">
<div class="info-row"><dt>Class</dt><dd><?php echo htmlspecialchars($className); ?></dd></div>
<div class="info-row"><dt>Section</dt><dd><?php echo htmlspecialchars($sectionName); ?></dd></div>
<div class="info-row"><dt>Semester</dt><dd><?php echo htmlspecialchars($semesterLabel); ?></dd></div>
<div class="info-row"><dt>School</dt><dd><?php echo htmlspecialchars($schoolLabel); ?></dd></div>
</div>
</div>

<?php if (!empty($progressRecords)): ?>
<div class="card">
<h3 class="section-title">Subject status overview</h3>
<div class="progress-grid">
<?php foreach ($progressRecords as $record): ?>
<?php
$pct = round((float)($record['completion_percentage'] ?? 0));
$statusLabel = $pct < 50 ? 'Needs attention' : 'On track';
$statusClass = $pct < 50 ? 'status-pill pending' : 'status-pill success';
?>
<div class="progress-card">
<div class="progress-card-header">
<h4><?php echo htmlspecialchars($record['subject_name']); ?></h4>
<span class="<?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
</div>
<p class="text-muted">Faculty: <?php echo htmlspecialchars($record['teacher_name_display'] ?? ($record['teacher_name'] !== '' ? format_person_display($record['teacher_name']) : 'NOT ASSIGNED')); ?></p>
<p class="text-muted">Latest topic: <?php echo htmlspecialchars($record['topic'] ?? 'Not updated'); ?></p>
<p class="text-muted">Timeline: <?php echo htmlspecialchars($record['timeline'] ?? 'N/A'); ?></p>
<div class="progress-wrapper">
<div class="progress-bar"><span style="width: <?php echo min(100, max(0, $pct)); ?>%"></span></div>
</div>
<p class="table-note">Completion: <?php echo $pct; ?>%</p>
<p class="table-note">Updated <?php echo $record['updated_at'] ? date('d M Y', strtotime($record['updated_at'])) : 'N/A'; ?></p>
</div>
<?php endforeach; ?>
</div>
</div>

<div class="card">
<h3 class="section-title">Detailed breakdown</h3>
<div class="table-responsive">
<table>
<thead>
<tr>
<th class="text-left">Subject</th>
<th class="text-left">Faculty</th>
<th class="text-left">Topic</th>
<th class="text-left">Timeline</th>
<th>Modules Completed</th>
<th>Planned Hours</th>
<th>Actual Hours</th>
<th>Completion %</th>
<th>Updated</th>
</tr>
</thead>
<tbody>
<?php foreach ($progressRecords as $record): ?>
<tr>
<td class="text-left"><?php echo htmlspecialchars($record['subject_name']); ?></td>
<td class="text-left"><?php echo htmlspecialchars($record['teacher_name_display'] ?? ($record['teacher_name'] !== '' ? format_person_display($record['teacher_name']) : 'NOT ASSIGNED')); ?></td>
<td class="text-left"><?php echo htmlspecialchars($record['topic'] ?? 'N/A'); ?></td>
<td class="text-left"><?php echo htmlspecialchars($record['timeline'] ?? 'N/A'); ?></td>
<td><?php echo htmlspecialchars($record['modules_completed'] ?? 'N/A'); ?></td>
<td><?php echo htmlspecialchars($record['planned_hours'] ?? 'N/A'); ?></td>
<td><?php echo htmlspecialchars($record['actual_hours'] ?? 'N/A'); ?></td>
<td><?php echo round((float)($record['completion_percentage'] ?? 0), 1); ?>%</td>
<td><?php echo $record['updated_at'] ? date('d M Y', strtotime($record['updated_at'])) : 'N/A'; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<p class="table-note">Latest updates from faculty are displayed above. Contact your instructor if details look outdated.</p>
</div>
<?php else: ?>
<div class="card">
<div class="empty-state">Your faculty have not submitted syllabus progress updates for this class yet.</div>
</div>
<?php endif; ?>
</div>
</div>
</div>
</body>
</html>

