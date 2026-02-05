<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/academic_context.php';

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
    mysqli_close($conn);
    echo 'Unable to determine your student record. Please contact the administrator.';
    exit;
}

$studentSql = "SELECT s.id, s.class_id, s.section_id, s.sap_id,
                      c.class_name, c.semester, c.school, c.academic_term_id AS class_term_id,
                      sec.section_name
               FROM students s
               LEFT JOIN classes c ON s.class_id = c.id
               LEFT JOIN sections sec ON s.section_id = sec.id
               WHERE s.sap_id = ?
               LIMIT 1";
$studentInfo = null;
if ($stmtStudent = mysqli_prepare($conn, $studentSql)) {
    mysqli_stmt_bind_param($stmtStudent, 's', $studentSap);
    mysqli_stmt_execute($stmtStudent);
    $studentRes = mysqli_stmt_get_result($stmtStudent);
    $studentInfo = mysqli_fetch_assoc($studentRes) ?: null;
    mysqli_stmt_close($stmtStudent);
}

if (!$studentInfo) {
    mysqli_close($conn);
    echo 'Student record not found.';
    exit;
}

$classId = $studentInfo['class_id'] ? (int)$studentInfo['class_id'] : null;
$className = $studentInfo['class_name'] ?? 'N/A';
$sectionName = $studentInfo['section_name'] ?? 'N/A';
$semesterLabel = $studentInfo['semester'] ?? 'N/A';
$schoolLabel = $studentInfo['school'] ?? 'N/A';
$classTermId = isset($studentInfo['class_term_id']) ? (int)$studentInfo['class_term_id'] : null;

$activeTermId = null;
if ($schoolLabel !== 'N/A') {
    $academicContext = resolveAcademicContext($conn, [
        'school_name' => $schoolLabel
    ]);
    $activeTerm = $academicContext['active'] ?? null;
    if ($activeTerm && isset($activeTerm['id'])) {
        $activeTermId = (int)$activeTerm['id'];
    }
}

if ($classId !== null && $activeTermId !== null && $classTermId !== $activeTermId) {
    $normalizedClassName = trim((string)$className);
    $lookupSql = "SELECT id, class_name, semester, school FROM classes WHERE LOWER(class_name) = LOWER(?) AND school = ? AND academic_term_id = ? LIMIT 1";
    if ($lookupStmt = mysqli_prepare($conn, $lookupSql)) {
        mysqli_stmt_bind_param($lookupStmt, 'ssi', $normalizedClassName, $schoolLabel, $activeTermId);
        mysqli_stmt_execute($lookupStmt);
        if ($lookupRes = mysqli_stmt_get_result($lookupStmt)) {
            if ($updatedClass = mysqli_fetch_assoc($lookupRes)) {
                $classId = (int)$updatedClass['id'];
                $className = $updatedClass['class_name'] ?? $className;
                $semesterLabel = $updatedClass['semester'] ?? $semesterLabel;
                $schoolLabel = $updatedClass['school'] ?? $schoolLabel;
                $classTermId = $activeTermId;
            }
            mysqli_free_result($lookupRes);
        }
        mysqli_stmt_close($lookupStmt);
    }
}

$sectionRaw = strcasecmp($sectionName, 'N/A') === 0 ? '' : $sectionName;
$semesterRaw = strcasecmp($semesterLabel, 'N/A') === 0 ? '' : $semesterLabel;
$schoolRaw = strcasecmp($schoolLabel, 'N/A') === 0 ? '' : $schoolLabel;
$classDisplay = format_class_label($className, $sectionRaw, $semesterRaw, $schoolRaw);
if ($classDisplay === '') {
    $classDisplay = 'N/A';
}
$sectionDisplay = $sectionRaw !== '' ? format_subject_display($sectionRaw) : 'N/A';
$semesterDisplay = $semesterRaw !== '' ? format_subject_display($semesterRaw) : 'N/A';
$schoolDisplay = $schoolRaw !== '' ? format_subject_display($schoolRaw) : 'N/A';
$classNameDisplay = $classDisplay !== 'N/A' ? $classDisplay : (strcasecmp($className, 'N/A') === 0 ? 'N/A' : format_subject_display($className));

function format_timetable_timeline_label(string $timeline): string
{
    $trimmed = trim($timeline);
    if ($trimmed === '') {
        return '';
    }
    if (preg_match('/^week_(\d+)$/i', $trimmed, $match)) {
        return 'Week ' . (int)$match[1];
    }
    $normalized = str_replace(['_', '-'], ' ', $trimmed);
    return ucwords($normalized);
}

$classTimetables = [];
$latestUpload = null;
$fileCount = 0;
$pageError = '';

if ($classId === null) {
    $pageError = 'Your student record is missing a class assignment. Please contact the administrator.';
} else {
    $ttSql = "SELECT file_name, file_path, uploaded_at, timeline, is_broadcast
              FROM class_timetables
              WHERE class_id = ?
              ORDER BY uploaded_at DESC";
    if ($stmtTimetable = mysqli_prepare($conn, $ttSql)) {
        mysqli_stmt_bind_param($stmtTimetable, 'i', $classId);
        mysqli_stmt_execute($stmtTimetable);
        $ttRes = mysqli_stmt_get_result($stmtTimetable);
        while ($row = mysqli_fetch_assoc($ttRes)) {
            $uploadedTs = null;
            if (!empty($row['uploaded_at'])) {
                $uploadedTs = strtotime($row['uploaded_at']);
                if ($uploadedTs !== false && ($latestUpload === null || $uploadedTs > $latestUpload)) {
                    $latestUpload = $uploadedTs;
                }
            }
            $timelineRaw = isset($row['timeline']) ? trim((string)$row['timeline']) : '';
            $timelineLabel = $timelineRaw !== '' ? format_timetable_timeline_label($timelineRaw) : '';
            $isBroadcast = !empty($row['is_broadcast']);
            $row['is_broadcast'] = $isBroadcast ? 1 : 0;
            $row['timeline_label'] = $timelineLabel;
            $row['meta_parts'] = array_filter([
                $timelineLabel,
                $isBroadcast ? 'Broadcast' : ''
            ]);
            $classTimetables[] = $row;
        }
        mysqli_stmt_close($stmtTimetable);
        $fileCount = count($classTimetables);
    } else {
        $pageError = 'Unable to load timetable records right now. Please try again later.';
    }
}

$latestUploadLabel = $latestUpload ? date('d M Y, h:i A', $latestUpload) : null;

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable Downloads - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .upload-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fde6eb;
            color: #A6192E;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
        }
        .upload-tag i {
            font-size: 0.85rem;
        }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid rgba(166, 25, 46, 0.08);
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .stat-card.secondary {
            background: #fdf4f6;
        }
        .stat-label {
            font-size: 0.75rem;
            color: #6b6b6b;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .stat-card h3 {
            margin: 4px 0 0;
            font-size: 1.5rem;
            color: #1d1d1f;
            font-weight: 700;
        }
        .stat-card p {
            margin: 2px 0 0;
            color: #6b6b6b;
            font-size: 0.85rem;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }
        .detail-box {
            background: #f5f5f7;
            border-radius: 12px;
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            border: 1px solid rgba(0,0,0,0.04);
        }
        .detail-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6b6b6b;
            font-weight: 600;
        }
        .detail-value {
            font-size: 0.98rem;
            font-weight: 600;
            color: #1d1d1f;
        }
        .download-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 12px;
        }
        .download-item {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: center;
            padding: 12px 16px;
            border-radius: 12px;
            background: rgba(166, 25, 46, 0.08);
            border: 1px solid rgba(166, 25, 46, 0.15);
            flex-wrap: wrap;
        }
        .download-meta {
            font-size: 0.8rem;
            color: #555;
        }
        .download-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .download-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: #A6192E;
            color: #fff;
            padding: 7px 14px;
            border-radius: 999px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            transition: background 0.2s ease, color 0.2s ease;
            white-space: nowrap;
        }
        .download-action:hover {
            background: #7f0f22;
        }
        .download-action.view {
            background: #fff;
            color: #A6192E;
            border: 1px solid #A6192E;
        }
        .download-action.view:hover {
            background: rgba(166, 25, 46, 0.1);
            color: #7f0f22;
        }
        .empty-state {
            text-align: center;
            color: #666;
            padding: 28px 18px;
            border-radius: 12px;
            background: #fafafa;
            border: 1px dashed #ddd;
        }
        .notice {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 10px;
            background: #fff6e5;
            color: #8a5a00;
            border: 1px solid #fcd59a;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h2>ICA Tracker</h2>
            <a href="student_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="view_marks.php"><i class="fas fa-chart-line"></i> <span>Marks</span></a>
            <a href="subject_comparison.php"><i class="fas fa-balance-scale"></i> <span>Subject Comparison</span></a>
            <a href="view_assignment_marks.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a>
            <a href="view_timetable.php" class="active"><i class="fas fa-calendar-alt"></i> <span>Timetable</span></a>
            <a href="view_progress.php"><i class="fas fa-book"></i> <span>Syllabus Progress</span></a>
            <a href="edit_profile.php"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
        <div class="main-content">
            <div class="header">
                <div>
                    <h2>Your Timetables</h2>
                    <p>Download the latest schedules shared with your class.</p>
                </div>
                <?php if ($latestUploadLabel): ?>
                    <span class="upload-tag"><i class="fas fa-cloud-upload-alt"></i> Latest upload: <?php echo htmlspecialchars($latestUploadLabel); ?></span>
                <?php endif; ?>
            </div>
            <div class="container">
                <?php if ($pageError !== ''): ?>
                    <div class="notice"><?php echo htmlspecialchars($pageError); ?></div>
                <?php endif; ?>

                <div class="card-grid">
                    <div class="card stat-card">
                        <span class="stat-label">Available files</span>
                        <h3><?php echo $fileCount; ?></h3>
                        <p><?php echo $fileCount > 0 ? 'Timetable downloads ready for you.' : 'No files uploaded yet.'; ?></p>
                    </div>
                    <div class="card stat-card secondary">
                        <span class="stat-label">Class</span>
                        <h3><?php echo htmlspecialchars($classNameDisplay); ?></h3>
                        <p>Section <?php echo htmlspecialchars($sectionDisplay); ?></p>
                    </div>
                    <div class="card stat-card">
                        <span class="stat-label">Semester</span>
                        <h3><?php echo htmlspecialchars($semesterDisplay); ?></h3>
                        <p><?php echo htmlspecialchars($schoolDisplay); ?></p>
                    </div>
                </div>

                    <div class="card">
                        <h3 class="section-title">Class Details</h3>
                        <div class="detail-grid">
                            <div class="detail-box">
                                <span class="detail-label">Class</span>
                                <span class="detail-value"><?php echo htmlspecialchars($classNameDisplay); ?></span>
                            </div>
                            <div class="detail-box">
                                <span class="detail-label">Section</span>
                                <span class="detail-value"><?php echo htmlspecialchars($sectionDisplay); ?></span>
                            </div>
                            <div class="detail-box">
                                <span class="detail-label">Semester</span>
                                <span class="detail-value"><?php echo htmlspecialchars($semesterDisplay); ?></span>
                            </div>
                            <div class="detail-box">
                                <span class="detail-label">School</span>
                                <span class="detail-value"><?php echo htmlspecialchars($schoolDisplay); ?></span>
                            </div>
                        </div>
                    </div>

                <div class="card">
                    <h3 class="section-title">Timetable Files</h3>
                    <?php if ($fileCount > 0): ?>
                        <ul class="download-list">
                            <?php foreach ($classTimetables as $file): ?>
                                <?php
                                    $filePath = $file['file_path'] ?? '';
                                    $safePath = htmlspecialchars($filePath, ENT_QUOTES, 'UTF-8');
                                    $fileName = trim((string)($file['file_name'] ?? 'Timetable'));
                                    if ($fileName === '') {
                                        $fileName = 'Timetable';
                                    }
                                    $downloadName = $fileName;
                                    if ($filePath !== '') {
                                        $pathBasename = basename($filePath);
                                        if (strpos($downloadName, '.') === false && $pathBasename !== '') {
                                            $downloadName = $pathBasename;
                                        }
                                    }
                                    $uploadedAt = !empty($file['uploaded_at']) ? strtotime($file['uploaded_at']) : null;
                                    $uploadedLabel = $uploadedAt ? date('d M Y, h:i A', $uploadedAt) : '—';
                                    $metaParts = [];
                                    if (isset($file['meta_parts']) && is_array($file['meta_parts'])) {
                                        $metaParts = array_values(array_filter($file['meta_parts'], static function ($value) {
                                            return trim((string)$value) !== '';
                                        }));
                                    }
                                ?>
                                <li class="download-item">
                                    <div>
                                        <strong><?php echo htmlspecialchars($fileName); ?></strong>
                                        <div class="download-meta">Uploaded <?php echo htmlspecialchars($uploadedLabel); ?></div>
                                        <?php if (!empty($metaParts)): ?>
                                            <div class="download-meta"><?php echo htmlspecialchars(implode(' • ', $metaParts)); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="download-actions">
                                        <a class="download-action" href="<?php echo $safePath; ?>" download="<?php echo htmlspecialchars($downloadName, ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="download-meta">Use Download to save a copy of the timetable.</p>
                    <?php else: ?>
                        <div class="empty-state">Timetables have not been uploaded for your class yet. Please check back later.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

