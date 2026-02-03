<?php
session_start();
include 'db_connect.php';
require_once __DIR__ . '/includes/assignment_helpers.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$errorMessage = '';
$studentSap = $_SESSION['unique_id'] ?? null;
$submissionNotice = '';
$submissionError = '';
$highlightAssignmentId = isset($_GET['focus']) ? (int)$_GET['focus'] : null;

if ($studentSap === null) {
    $sapStmt = mysqli_prepare($conn, 'SELECT username FROM users WHERE id = ? LIMIT 1');
    if ($sapStmt) {
        mysqli_stmt_bind_param($sapStmt, 'i', $userId);
        mysqli_stmt_execute($sapStmt);
        $sapResult = mysqli_stmt_get_result($sapStmt);
        if ($sapRow = mysqli_fetch_assoc($sapResult)) {
            $studentSap = $sapRow['username'];
        }
        mysqli_stmt_close($sapStmt);
    }
}

if (!$studentSap) {
    $errorMessage = 'Unable to determine your student identifier. Please contact the administrator.';
}

require_once __DIR__ . '/includes/student_context.php';
$studentContext = null;
$studentId = null;
$timelineMismatch = false;
$termStartDate = null;
$termEndDate = null;
$termStartBound = null;
$termEndBound = null;
$academicContext = null;
$studentName = $_SESSION['name'] ?? '';

if ($errorMessage === '') {
    $studentContext = buildStudentTermContext($conn, $studentSap, [
        'student_name' => $studentName,
    ]);
    if (!empty($studentContext['error'])) {
        $errorMessage = $studentContext['error'];
    } else {
        $studentName = $studentContext['student_name'];
        $studentId = $studentContext['student_id'] ? (int)$studentContext['student_id'] : 0;
        $timelineMismatch = $studentContext['timeline_mismatch'];
        $termStartDate = $studentContext['term_start_date'];
        $termEndDate = $studentContext['term_end_date'];
        $termStartBound = $studentContext['term_start_bound'];
        $termEndBound = $studentContext['term_end_bound'];
        $academicContext = $studentContext['academic_context'];
        if (!$studentId) {
            $timelineMismatch = true;
        }
    }
}

// Handle student submission upload
if ($errorMessage === '' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_assignment') {
    $submitAssignmentId = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;
    if (!$studentId) {
        $submissionError = 'Student profile not resolved; cannot submit right now.';
    } elseif ($submitAssignmentId <= 0) {
        $submissionError = 'Invalid assignment selected for submission.';
    } elseif (empty($_FILES['submission_file']['name'])) {
        $submissionError = 'Please attach your assignment file before submitting.';
    } else {
        $fileError = $_FILES['submission_file']['error'];
        $fileSize = $_FILES['submission_file']['size'];
        if ($fileError !== UPLOAD_ERR_OK) {
            $submissionError = 'File upload failed. Please retry.';
        } elseif ($fileSize > 20 * 1024 * 1024) {
            $submissionError = 'File must be 20MB or smaller.';
        } else {
            $storage = ensure_assignment_storage();
            $safeName = assignment_safe_filename($_FILES['submission_file']['name']);
            $targetPath = $storage['student'] . DIRECTORY_SEPARATOR . $safeName;
            if (!move_uploaded_file($_FILES['submission_file']['tmp_name'], $targetPath)) {
                $submissionError = 'Unable to save your file right now.';
            } else {
                $fileRelPath = 'uploads/assignments/student_submissions/' . $safeName;
                // Find due date for late determination and current submitted file for cleanup
                $metaSql = 'SELECT sa.submitted_file_path, COALESCE(a.due_at, a.deadline) AS due_at FROM student_assignments sa INNER JOIN assignments a ON a.id = sa.assignment_id WHERE sa.assignment_id = ? AND sa.student_id = ? LIMIT 1';
                $metaStmt = mysqli_prepare($conn, $metaSql);
                $dueAt = null;
                $oldFile = null;
                if ($metaStmt) {
                    mysqli_stmt_bind_param($metaStmt, 'ii', $submitAssignmentId, $studentId);
                    mysqli_stmt_execute($metaStmt);
                    $metaRes = mysqli_stmt_get_result($metaStmt);
                    if ($metaRes && ($metaRow = mysqli_fetch_assoc($metaRes))) {
                        $dueAt = $metaRow['due_at'] ?? null;
                        $oldFile = $metaRow['submitted_file_path'] ?? null;
                    }
                    if ($metaRes) {
                        mysqli_free_result($metaRes);
                    }
                    mysqli_stmt_close($metaStmt);
                }

                $nowTs = time();
                $assignmentStatus = 'completed';
                if (!empty($dueAt)) {
                    $dueTs = strtotime((string)$dueAt);
                    if ($dueTs !== false && $nowTs > $dueTs) {
                        $assignmentStatus = 'late_submitted';
                    }
                }

                $updateSql = "UPDATE student_assignments SET submission_status = 'submitted', submission_state = 'submitted', assignment_status = ?, submitted_file_path = ?, last_submission_at = NOW(), submission_date = IFNULL(submission_date, NOW()) WHERE assignment_id = ? AND student_id = ?";
                $updateStmt = mysqli_prepare($conn, $updateSql);
                if ($updateStmt) {
                    mysqli_stmt_bind_param($updateStmt, 'ssii', $assignmentStatus, $fileRelPath, $submitAssignmentId, $studentId);
                    mysqli_stmt_execute($updateStmt);
                    mysqli_stmt_close($updateStmt);
                    if ($oldFile && $oldFile !== $fileRelPath) {
                        @unlink(__DIR__ . '/' . $oldFile);
                    }
                    $submissionNotice = 'Assignment submitted successfully.';
                } else {
                    $submissionError = 'Could not record your submission. Please try again.';
                    @unlink($targetPath);
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/term_switcher_ui.php';

$rawAssignments = [];
$stmtAssignments = null;
if ($errorMessage === '' && $studentId && !$timelineMismatch) {
    $assignmentDateClause = ($termStartBound && $termEndBound) ? ' AND (COALESCE(a.due_at, a.deadline) BETWEEN ? AND ?)' : '';
    $assignmentsSql = "
        SELECT a.id, a.title, a.subject, a.deadline, a.description, a.assignment_type, a.assignment_number,
               a.start_at, a.due_at, a.max_marks, a.instructions_file,
               sa.submission_status, sa.assignment_status, sa.submission_state, sa.submitted_file_path,
               sa.graded_marks AS graded_marks, sa.marks_obtained AS marks
        FROM student_assignments sa
        INNER JOIN assignments a ON sa.assignment_id = a.id
        WHERE sa.student_id = ?" . $assignmentDateClause . "
        ORDER BY (sa.assignment_status = 'pending') DESC, COALESCE(a.due_at, a.deadline) ASC
    ";

    $runFetch = static function(mysqli $conn, string $sql, int $studentId, ?string $start, ?string $end) use (&$rawAssignments, &$stmtAssignments) {
        $stmtAssignments = mysqli_prepare($conn, $sql);
        if (!$stmtAssignments) {
            return false;
        }
        if ($start !== null && $end !== null) {
            mysqli_stmt_bind_param($stmtAssignments, 'iss', $studentId, $start, $end);
        } else {
            mysqli_stmt_bind_param($stmtAssignments, 'i', $studentId);
        }
        mysqli_stmt_execute($stmtAssignments);
        $assignmentsResult = mysqli_stmt_get_result($stmtAssignments);
        while ($assignmentsResult && ($row = mysqli_fetch_assoc($assignmentsResult))) {
            $rawAssignments[] = $row;
        }
        if ($assignmentsResult) {
            mysqli_free_result($assignmentsResult);
        }
        mysqli_stmt_close($stmtAssignments);
        $stmtAssignments = null;
        return true;
    };

    $filterStart = ($assignmentDateClause !== '') ? $termStartBound : null;
    $filterEnd = ($assignmentDateClause !== '') ? $termEndBound : null;
    $fetchOk = $runFetch($conn, $assignmentsSql, $studentId, $filterStart, $filterEnd);

    if ($fetchOk && empty($rawAssignments) && $assignmentDateClause !== '') {
        // Fallback: fetch all assignments if term filter returns nothing.
        $rawAssignments = [];
        $fallbackSql = str_replace($assignmentDateClause, '', $assignmentsSql);
        $runFetch($conn, $fallbackSql, $studentId, null, null);
    } elseif (!$fetchOk) {
        $errorMessage = 'Unable to retrieve assignment details at the moment.';
    }
}

if ($stmtAssignments) {
    mysqli_stmt_close($stmtAssignments);
}
mysqli_close($conn);

$stats = [
    'total' => count($rawAssignments),
    'pending' => 0,
    'submitted' => 0,
    'graded' => 0,
    'overdue' => 0,
    'average_mark' => null,
    'next_deadline_ts' => null
];

$gradedMarks = [];
$subjectSummary = [];
$displayAssignments = [];
$today = new DateTime('today');

foreach ($rawAssignments as $assignment) {
    $subjectNameRaw = isset($assignment['subject']) ? trim((string)$assignment['subject']) : '';
    $subjectDisplay = $subjectNameRaw !== '' ? format_subject_display($subjectNameRaw) : 'GENERAL';
    $dueAtRaw = $assignment['due_at'] ?? null;
    $deadlineRaw = $assignment['deadline'] ?? null;
    $startAtRaw = $assignment['start_at'] ?? null;
    $deadlineTs = null;
    $deadlineDisplay = '—';
    if (!empty($dueAtRaw)) {
        $deadlineTs = strtotime($dueAtRaw);
        $deadlineDisplay = date('d M Y, h:i A', $deadlineTs);
    } elseif (!empty($deadlineRaw)) {
        $deadlineTs = strtotime($deadlineRaw);
        $deadlineDisplay = date('d M Y', $deadlineTs);
    }
    $startDisplay = $startAtRaw ? date('d M Y, h:i A', strtotime((string)$startAtRaw)) : '—';

    $marksValue = $assignment['graded_marks'] ?? $assignment['marks'];
    $isGraded = $marksValue !== null && $marksValue !== '';
    $statusRaw = $assignment['assignment_status'] ?? $assignment['submission_status'] ?? '';
    $statusSlug = strtolower(trim((string)$statusRaw));
    if ($statusSlug === '') {
        $statusSlug = 'pending';
    }
    $statusLabel = ucwords(str_replace('_', ' ', $statusSlug));
    $subjectKey = $subjectDisplay !== '' ? $subjectDisplay : 'GENERAL';

    if ($isGraded) {
        $stats['graded']++;
        $gradedMarks[] = (float)$marksValue;
    } elseif (in_array($statusSlug, ['completed', 'submitted', 'late_submitted'], true)) {
        $stats['submitted']++;
    } else {
        $stats['pending']++;
    }

    if ($deadlineTs !== null) {
        $deadlineDate = new DateTime(date('Y-m-d', $deadlineTs));
        $daysDiff = (int)$today->diff($deadlineDate)->format('%r%a');
        $isPendingSubmission = !$isGraded && !in_array($statusSlug, ['completed', 'submitted'], true);
        if ($isPendingSubmission && $deadlineTs < $today->getTimestamp()) {
            $stats['overdue']++;
        }
        if ($isPendingSubmission && $deadlineTs >= $today->getTimestamp()) {
            if ($stats['next_deadline_ts'] === null || $deadlineTs < $stats['next_deadline_ts']) {
                $stats['next_deadline_ts'] = $deadlineTs;
            }
        }
        $deadlineHint = null;
        if ($daysDiff > 0) {
            $deadlineHint = 'Due in ' . $daysDiff . ' day' . ($daysDiff !== 1 ? 's' : '');
        } elseif ($daysDiff === 0) {
            $deadlineHint = 'Due today';
        } else {
            $deadlineHint = 'Overdue by ' . abs($daysDiff) . ' day' . (abs($daysDiff) !== 1 ? 's' : '');
        }
    } else {
        $deadlineHint = null;
    }

    if (!isset($subjectSummary[$subjectKey])) {
        $subjectSummary[$subjectKey] = [
            'subject' => $subjectDisplay,
            'total' => 0,
            'pending' => 0,
            'submitted' => 0,
            'graded' => 0,
            'overdue' => 0,
            'next_deadline_ts' => null
        ];
    }
    $subjectSummary[$subjectKey]['total']++;
    if ($isGraded) {
        $subjectSummary[$subjectKey]['graded']++;
    } elseif (in_array($statusSlug, ['completed', 'submitted', 'late_submitted'], true)) {
        $subjectSummary[$subjectKey]['submitted']++;
    } else {
        $subjectSummary[$subjectKey]['pending']++;
        if ($deadlineTs !== null && $deadlineTs < $today->getTimestamp()) {
            $subjectSummary[$subjectKey]['overdue']++;
        }
        if ($deadlineTs !== null && $deadlineTs >= $today->getTimestamp()) {
            if ($subjectSummary[$subjectKey]['next_deadline_ts'] === null || $deadlineTs < $subjectSummary[$subjectKey]['next_deadline_ts']) {
                $subjectSummary[$subjectKey]['next_deadline_ts'] = $deadlineTs;
            }
        }
    }

    $statusClass = 'status-pill pending';
    if ($isGraded || in_array($statusSlug, ['completed', 'submitted'], true)) {
        $statusClass = 'status-pill success';
    } elseif ($statusSlug === 'late_submitted') {
        $statusClass = 'status-pill pending';
        $statusLabel = 'Late Submitted';
    } elseif ($statusSlug === 'rejected') {
        $statusClass = 'status-pill';
        $statusLabel = 'Rejected';
    } elseif ($deadlineTs !== null && $deadlineTs < $today->getTimestamp()) {
        $statusLabel = 'Overdue';
    }

    $displayAssignments[] = [
        'id' => $assignment['id'],
        'title' => $assignment['title'],
        'subject' => $subjectDisplay,
        'subject_display' => $subjectDisplay,
        'start_display' => $startDisplay,
        'deadline_display' => $deadlineDisplay,
        'deadline_hint' => $deadlineHint,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'marks' => $marksValue,
        'max_marks' => $assignment['max_marks'],
        'type' => $assignment['assignment_type'] ?? '—',
        'assignment_number' => $assignment['assignment_number'] ?? '',
        'file_link' => $assignment['instructions_file'] ?? null,
        'submitted_file' => $assignment['submitted_file_path'] ?? null,
        'description' => $assignment['description']
    ];
}

if (!empty($gradedMarks)) {
    $stats['average_mark'] = round(array_sum($gradedMarks) / count($gradedMarks), 1);
}

$nextDeadlineLabel = null;
$nextDeadlineHint = null;
if ($stats['next_deadline_ts'] !== null) {
    $nextDeadlineLabel = date('d M Y', $stats['next_deadline_ts']);
    $deadlineDate = new DateTime(date('Y-m-d', $stats['next_deadline_ts']));
    $daysUntil = (int)$today->diff($deadlineDate)->format('%r%a');
    if ($daysUntil > 0) {
        $nextDeadlineHint = 'in ' . $daysUntil . ' day' . ($daysUntil !== 1 ? 's' : '');
    } else {
        $nextDeadlineHint = 'today';
    }
}

foreach ($subjectSummary as &$subjectMeta) {
    $completed = $subjectMeta['graded'] + $subjectMeta['submitted'];
    $subjectMeta['completion_pct'] = $subjectMeta['total'] > 0 ? round(($completed / $subjectMeta['total']) * 100) : 0;
    if ($subjectMeta['next_deadline_ts'] !== null) {
        $deadlineDate = new DateTime(date('Y-m-d', $subjectMeta['next_deadline_ts']));
        $daysUntil = (int)$today->diff($deadlineDate)->format('%r%a');
        if ($daysUntil > 0) {
            $subjectMeta['next_deadline_hint'] = 'Due in ' . $daysUntil . ' day' . ($daysUntil !== 1 ? 's' : '');
        } elseif ($daysUntil === 0) {
            $subjectMeta['next_deadline_hint'] = 'Due today';
        } else {
            $subjectMeta['next_deadline_hint'] = 'Overdue';
        }
        $subjectMeta['next_deadline_label'] = date('d M Y', $subjectMeta['next_deadline_ts']);
    } else {
        $subjectMeta['next_deadline_hint'] = null;
        $subjectMeta['next_deadline_label'] = null;
    }
    unset($subjectMeta['next_deadline_ts']);
}
unset($subjectMeta);
$subjectSummary = array_values($subjectSummary);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments - ICA Tracker</title>
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
            <a href="view_assignment_marks.php" class="active"><i class="fas fa-tasks"></i> <span>Assignments</span></a>
            <a href="view_timetable.php"><i class="fas fa-calendar-alt"></i> <span>Timetable</span></a>
            <a href="view_progress.php"><i class="fas fa-book"></i> <span>Syllabus Progress</span></a>
            <a href="edit_profile.php"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
        <div class="main-content">
            <div class="header">
                <div>
                    <h2>Assignments &amp; Feedback</h2>
                    <p>Track submissions, deadlines, and marks across every subject.</p>
                </div>
                <?php if ($submissionNotice !== ''): ?>
                    <div class="alert alert-success" style="margin-bottom:0;"><?php echo htmlspecialchars($submissionNotice); ?></div>
                <?php elseif ($submissionError !== ''): ?>
                    <div class="alert alert-error" style="margin-bottom:0;"><?php echo htmlspecialchars($submissionError); ?></div>
                <?php endif; ?>
                <?php if ($nextDeadlineLabel): ?>
                    <span class="tag">Next due: <?php echo htmlspecialchars($nextDeadlineLabel); ?> (<?php echo htmlspecialchars($nextDeadlineHint); ?>)</span>
                <?php elseif ($stats['total'] === 0): ?>
                    <span class="tag">No assignments posted yet</span>
                <?php elseif ($stats['pending'] === 0): ?>
                    <span class="tag">All submissions up to date</span>
                <?php endif; ?>
            </div>
            <div class="container">
                <?php if ($errorMessage !== ''): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
                <?php endif; ?>
                <?php if ($timelineMismatch && $errorMessage === ''): ?>
                    <div class="card" style="border-left:4px solid #A6192E;">
                        <h3 class="section-title">No assignments for this term</h3>
                        <p>The selected academic timeline does not have assignment records yet. Update the term from your dashboard to review submissions from another period.</p>
                    </div>
                <?php else: ?>
                    <div class="card-grid">
                        <div class="card stat-card">
                            <span class="stat-label">Assignments posted</span>
                            <h3><?php echo $stats['total']; ?></h3>
                            <p>Across all tracked subjects.</p>
                        </div>
                        <div class="card stat-card<?php echo $stats['pending'] === 0 ? ' success' : ''; ?>">
                            <span class="stat-label">Pending submissions</span>
                            <h3><?php echo $stats['pending']; ?></h3>
                            <p><?php echo $stats['pending'] > 0 ? 'Action needed on these tasks.' : 'Nothing pending right now.'; ?></p>
                        </div>
                        <div class="card stat-card<?php echo $stats['graded'] > 0 ? ' success' : ' secondary'; ?>">
                            <span class="stat-label">Graded assignments</span>
                            <h3><?php echo $stats['graded']; ?></h3>
                            <p><?php echo $stats['average_mark'] !== null ? 'Average mark: ' . number_format($stats['average_mark'], 1) : 'Awaiting first grade.'; ?></p>
                        </div>
                        <div class="card stat-card<?php echo $stats['overdue'] === 0 ? ' success' : ''; ?>">
                            <span class="stat-label">Overdue tasks</span>
                            <h3><?php echo $stats['overdue']; ?></h3>
                            <p><?php echo $stats['overdue'] > 0 ? 'Follow up with faculty soon.' : 'Great job staying ahead.'; ?></p>
                        </div>
                    </div>

                    <?php if (!empty($subjectSummary)): ?>
                    <div class="card">
                        <h3 class="section-title">Subject breakdown</h3>
                        <div class="progress-grid">
                            <?php foreach ($subjectSummary as $subjectMeta): ?>
                            <div class="progress-card">
                                <div class="progress-card-header">
                                    <h4><?php echo htmlspecialchars($subjectMeta['subject']); ?></h4>
                                    <span class="status-pill<?php echo $subjectMeta['pending'] > 0 ? ' pending' : ' success'; ?>">
                                        <?php echo $subjectMeta['pending'] > 0 ? $subjectMeta['pending'] . ' pending' : 'On track'; ?>
                                    </span>
                                </div>
                                <p class="text-muted"><?php echo $subjectMeta['graded']; ?> graded · <?php echo $subjectMeta['submitted']; ?> submitted</p>
                                <div class="progress-wrapper">
                                    <div class="progress-bar">
                                        <span style="width: <?php echo min(100, max(0, $subjectMeta['completion_pct'])); ?>%"></span>
                                    </div>
                                </div>
                                <p class="table-note"><?php echo $subjectMeta['completion_pct']; ?>% complete</p>
                                <?php if (!empty($subjectMeta['next_deadline_label'])): ?>
                                    <p class="table-note">Next due: <?php echo htmlspecialchars($subjectMeta['next_deadline_label']); ?><?php if (!empty($subjectMeta['next_deadline_hint'])): ?> (<?php echo htmlspecialchars($subjectMeta['next_deadline_hint']); ?>)<?php endif; ?></p>
                                <?php else: ?>
                                    <p class="table-note">No upcoming deadlines</p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="card">
                        <h3 class="section-title">Detailed assignment log</h3>
                        <?php if (!empty($displayAssignments)): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th class="text-left">Assignment</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Marks</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>File</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($displayAssignments as $idx => $assignment): ?>
                                    <?php
                                        $assignmentId = isset($assignment['id']) ? (int)$assignment['id'] : 0;
                                        $isFocusedRow = $highlightAssignmentId !== null && $assignmentId === $highlightAssignmentId;
                                    ?>
                                    <tr id="assignment-<?php echo $assignmentId; ?>"<?php echo $isFocusedRow ? ' class="assignment-focus-row"' : ''; ?>>
                                        <td><?php echo $idx + 1; ?></td>
                                        <td class="text-left">
                                            <div><?php echo htmlspecialchars($assignment['title']); ?><?php if (!empty($assignment['assignment_number'])): ?> <span class="text-muted">· <?php echo htmlspecialchars($assignment['assignment_number']); ?></span><?php endif; ?></div>
                                            <div class="text-muted"><?php echo htmlspecialchars($assignment['subject_display'] ?? $assignment['subject']); ?></div>
                                            <?php if (!empty($assignment['description'])): ?>
                                                <div class="text-muted" style="margin-top:4px;">Instruction: <?php echo htmlspecialchars($assignment['description']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($assignment['start_display']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($assignment['deadline_display']); ?>
                                            <?php if (!empty($assignment['deadline_hint'])): ?>
                                                <div class="text-muted"><?php echo htmlspecialchars($assignment['deadline_hint']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($assignment['marks'] !== null && $assignment['marks'] !== ''): ?>
                                                <?php echo number_format((float)$assignment['marks'], 2); ?>
                                                <?php if ($assignment['max_marks'] !== null && $assignment['max_marks'] !== ''): ?> / <?php echo number_format((float)$assignment['max_marks'], 0); ?><?php endif; ?>
                                            <?php elseif ($assignment['max_marks'] !== null && $assignment['max_marks'] !== ''): ?>
                                                — / <?php echo number_format((float)$assignment['max_marks'], 0); ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($assignment['type']); ?></td>
                                        <td><span class="<?php echo htmlspecialchars($assignment['status_class']); ?>"><?php echo htmlspecialchars($assignment['status_label']); ?></span></td>
                                        <td>
                                            <?php if (!empty($assignment['file_link'])): ?>
                                                <a href="<?php echo htmlspecialchars($assignment['file_link']); ?>" target="_blank">Download</a>
                                            <?php else: ?>
                                                <span class="text-muted">No file</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($assignment['submitted_file'])): ?>
                                                <div class="text-muted" style="margin-bottom:6px;">Latest upload available.</div>
                                                <a href="<?php echo htmlspecialchars($assignment['submitted_file']); ?>" target="_blank">View submission</a>
                                            <?php endif; ?>
                                            <form method="POST" enctype="multipart/form-data" style="margin-top:6px; display:flex; flex-direction:column; gap:6px;">
                                                <input type="hidden" name="action" value="submit_assignment">
                                                <input type="hidden" name="assignment_id" value="<?php echo (int)$assignment['id']; ?>">
                                                <input type="file" name="submission_file" accept="*/*" required>
                                                <button type="submit" class="btn" style="background:#A6192E;">Submit</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="table-note">Download assignments shared by faculty, track start/end windows, and view your submission state.</p>
                        <?php else: ?>
                        <div class="empty-state">Assignments will appear here once your faculty publish them.</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
            </div>
        <?php if ($highlightAssignmentId !== null && $highlightAssignmentId > 0): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var targetRow = document.getElementById('assignment-<?php echo $highlightAssignmentId; ?>');
            if (targetRow) {
                targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
        </script>
        <?php endif; ?>
        </body>
        </html>
