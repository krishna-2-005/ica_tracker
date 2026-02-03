<?php
session_start();
include 'db_connect.php';
require_once __DIR__ . '/includes/academic_context.php';
require_once __DIR__ . '/includes/term_switcher_ui.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header('Location: login.php');
    exit;
}

$teacher_id = (int)$_SESSION['user_id'];
$teacherNameRaw = isset($_SESSION['name']) ? (string)$_SESSION['name'] : '';
$teacherNameDisplay = $teacherNameRaw !== '' ? format_person_display($teacherNameRaw) : 'FACULTY';

$error = '';
$success = '';
if (!empty($_SESSION['timetable_success'])) {
    $success = (string)$_SESSION['timetable_success'];
    unset($_SESSION['timetable_success']);
}
if (!empty($_SESSION['timetable_error'])) {
    $error = (string)$_SESSION['timetable_error'];
    unset($_SESSION['timetable_error']);
}

$teacherSchool = isset($_SESSION['school']) ? trim((string)$_SESSION['school']) : '';
$contextOptions = [];
if ($teacherSchool !== '') {
    $contextOptions['school_name'] = $teacherSchool;
}
$academicContext = resolveAcademicContext($conn, $contextOptions);

$activeTerm = $academicContext['active'] ?? null;
$activeTermId = ($activeTerm && isset($activeTerm['id'])) ? (int)$activeTerm['id'] : null;

$timetableHasTermColumn = false;
$columnCheck = mysqli_query($conn, "SHOW COLUMNS FROM timetables LIKE 'academic_term_id'");
if ($columnCheck) {
    if (mysqli_num_rows($columnCheck) > 0) {
        $timetableHasTermColumn = true;
    }
    mysqli_free_result($columnCheck);
}

$terms = $academicContext['terms'] ?? [];
$termLabelById = [];
$validTermIds = [];
foreach ($terms as $termRow) {
    $termId = (int)$termRow['id'];
    $termLabelById[$termId] = $termRow['label'] ?? '';
    $validTermIds[] = $termId;
}

$editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$editingTimetable = null;

function sanitize_timetable_filename(string $name): string
{
    $safe = preg_replace("/[^a-zA-Z0-9\._-]/", "", $name);
    return $safe !== '' ? $safe : 'timetable';
}

$uploadTermSelection = $activeTermId;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_term_id'])) {
    $rawSelection = trim((string)$_POST['selected_term_id']);
    if ($rawSelection === '' || $rawSelection === '0') {
        $uploadTermSelection = null;
    } else {
        $candidate = (int)$rawSelection;
        if (in_array($candidate, $validTermIds, true)) {
            $uploadTermSelection = $candidate;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_timetable'])) {
        $timetableId = isset($_POST['timetable_id']) ? (int)$_POST['timetable_id'] : 0;
        if ($timetableId <= 0) {
            $error = 'Invalid timetable selection.';
        } else {
            $selectSql = "SELECT id, file_path, file_name";
            if ($timetableHasTermColumn) {
                $selectSql .= ", academic_term_id";
            }
            $selectSql .= " FROM timetables WHERE id = ? AND teacher_id = ?";
            $stmtFetch = mysqli_prepare($conn, $selectSql);
            $existing = null;
            if ($stmtFetch) {
                mysqli_stmt_bind_param($stmtFetch, "ii", $timetableId, $teacher_id);
                mysqli_stmt_execute($stmtFetch);
                $resultFetch = mysqli_stmt_get_result($stmtFetch);
                if ($resultFetch) {
                    $existing = mysqli_fetch_assoc($resultFetch);
                    mysqli_free_result($resultFetch);
                }
                mysqli_stmt_close($stmtFetch);
            }
            if (!$existing) {
                $error = 'Timetable not found.';
            } else {
                $existingTermId = $timetableHasTermColumn ? ($existing['academic_term_id'] !== null ? (int)$existing['academic_term_id'] : null) : null;
                $newTermId = $existingTermId;
                if ($timetableHasTermColumn) {
                    $rawEditTerm = isset($_POST['edit_term_id']) ? trim((string)$_POST['edit_term_id']) : null;
                    if ($rawEditTerm === '' || $rawEditTerm === '0' || $rawEditTerm === null) {
                        $newTermId = null;
                    } else {
                        $candidate = (int)$rawEditTerm;
                        if (in_array($candidate, $validTermIds, true)) {
                            $newTermId = $candidate;
                        } else {
                            $error = 'Invalid timeline selection.';
                        }
                    }
                }

                $hasNewFile = isset($_FILES['edit_timetable_file']) && $_FILES['edit_timetable_file']['error'] !== UPLOAD_ERR_NO_FILE;
                $newFilePath = '';
                $newFileName = '';
                $fileUpdateApplied = false;

                if ($error === '' && $hasNewFile) {
                    if ($_FILES['edit_timetable_file']['error'] !== UPLOAD_ERR_OK) {
                        $error = 'File upload error. Please try again.';
                    } else {
                        $uploadDir = 'uploads/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        $originalName = basename($_FILES['edit_timetable_file']['name']);
                        $safeName = sanitize_timetable_filename($originalName);
                        $extension = pathinfo($safeName, PATHINFO_EXTENSION);
                        $uniqueName = $teacher_id . '_' . time();
                        if ($extension !== '') {
                            $uniqueName .= '.' . $extension;
                        }
                        $targetPath = $uploadDir . $uniqueName;
                        if (move_uploaded_file($_FILES['edit_timetable_file']['tmp_name'], $targetPath)) {
                            $newFilePath = $targetPath;
                            $newFileName = $safeName;
                        } else {
                            $error = 'Sorry, there was an error uploading your file.';
                        }
                    }
                }

                if ($error === '') {
                    if ($hasNewFile && $newFilePath !== '') {
                        $stmtUpdateFile = mysqli_prepare($conn, "UPDATE timetables SET file_name = ?, file_path = ?, uploaded_at = NOW() WHERE id = ? AND teacher_id = ?");
                        if ($stmtUpdateFile) {
                            mysqli_stmt_bind_param($stmtUpdateFile, "ssii", $newFileName, $newFilePath, $timetableId, $teacher_id);
                            if (mysqli_stmt_execute($stmtUpdateFile)) {
                                $fileUpdateApplied = true;
                                if (!empty($existing['file_path']) && $existing['file_path'] !== $newFilePath && file_exists($existing['file_path'])) {
                                    unlink($existing['file_path']);
                                }
                            } else {
                                $error = 'Database error: Could not update timetable file.';
                            }
                            mysqli_stmt_close($stmtUpdateFile);
                        } else {
                            $error = 'Database error: Could not update timetable file.';
                        }
                    }

                    if ($error === '' && $timetableHasTermColumn && $newTermId !== $existingTermId) {
                        if ($newTermId !== null) {
                            $stmtTerm = mysqli_prepare($conn, "UPDATE timetables SET academic_term_id = ? WHERE id = ? AND teacher_id = ?");
                            if ($stmtTerm) {
                                mysqli_stmt_bind_param($stmtTerm, "iii", $newTermId, $timetableId, $teacher_id);
                                if (!mysqli_stmt_execute($stmtTerm)) {
                                    $error = 'Database error: Could not update timeline.';
                                }
                                mysqli_stmt_close($stmtTerm);
                            } else {
                                $error = 'Database error: Could not update timeline.';
                            }
                        } else {
                            $stmtTerm = mysqli_prepare($conn, "UPDATE timetables SET academic_term_id = NULL WHERE id = ? AND teacher_id = ?");
                            if ($stmtTerm) {
                                mysqli_stmt_bind_param($stmtTerm, "ii", $timetableId, $teacher_id);
                                if (!mysqli_stmt_execute($stmtTerm)) {
                                    $error = 'Database error: Could not update timeline.';
                                }
                                mysqli_stmt_close($stmtTerm);
                            } else {
                                $error = 'Database error: Could not update timeline.';
                            }
                        }
                    }
                }

                if ($error === '') {
                    $_SESSION['timetable_success'] = 'Timetable updated successfully.';
                    header('Location: timetable.php');
                    exit;
                } else {
                    if ($hasNewFile && !$fileUpdateApplied && $newFilePath !== '' && file_exists($newFilePath)) {
                        unlink($newFilePath);
                    }
                }
            }
        }
    } elseif (isset($_POST['upload_timetable'])) {
        if (!isset($_FILES['timetable_file']) || $_FILES['timetable_file']['error'] === UPLOAD_ERR_NO_FILE) {
            $error = 'Please choose a timetable file.';
        } elseif ($_FILES['timetable_file']['error'] !== UPLOAD_ERR_OK) {
            $error = 'File upload error. Please try again.';
        } else {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $originalName = basename($_FILES['timetable_file']['name']);
            $safeOriginalName = sanitize_timetable_filename($originalName);
            $extension = pathinfo($safeOriginalName, PATHINFO_EXTENSION);
            $uniqueName = $teacher_id . '_' . time();
            if ($extension !== '') {
                $uniqueName .= '.' . $extension;
            }
            $targetPath = $uploadDir . $uniqueName;

            $termIdForInsert = null;
            if ($timetableHasTermColumn) {
                $submittedTermId = isset($_POST['selected_term_id']) ? (int)$_POST['selected_term_id'] : 0;
                if ($submittedTermId > 0 && in_array($submittedTermId, $validTermIds, true)) {
                    $termIdForInsert = $submittedTermId;
                } else {
                    $termIdForInsert = $activeTermId;
                }
            }

            if (move_uploaded_file($_FILES['timetable_file']['tmp_name'], $targetPath)) {
                $stmt = null;
                if ($timetableHasTermColumn) {
                    if ($termIdForInsert !== null) {
                        $stmt = mysqli_prepare($conn, "INSERT INTO timetables (teacher_id, file_name, file_path, academic_term_id) VALUES (?, ?, ?, ?)");
                        if ($stmt) {
                            mysqli_stmt_bind_param($stmt, "issi", $teacher_id, $safeOriginalName, $targetPath, $termIdForInsert);
                        }
                    } else {
                        $stmt = mysqli_prepare($conn, "INSERT INTO timetables (teacher_id, file_name, file_path, academic_term_id) VALUES (?, ?, ?, NULL)");
                        if ($stmt) {
                            mysqli_stmt_bind_param($stmt, "iss", $teacher_id, $safeOriginalName, $targetPath);
                        }
                    }
                } else {
                    $stmt = mysqli_prepare($conn, "INSERT INTO timetables (teacher_id, file_name, file_path) VALUES (?, ?, ?)");
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "iss", $teacher_id, $safeOriginalName, $targetPath);
                    }
                }

                if ($stmt && mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    $_SESSION['timetable_success'] = 'Timetable uploaded successfully.';
                    header('Location: timetable.php');
                    exit;
                }

                if ($stmt) {
                    mysqli_stmt_close($stmt);
                }
                $error = 'Database error: Could not save timetable information.';
                if (file_exists($targetPath)) {
                    unlink($targetPath);
                }
            } else {
                $error = 'Sorry, there was an error uploading your file.';
            }
        }
    }
}

if (isset($_GET['delete_id'])) {
    $deleteId = (int)$_GET['delete_id'];
    if ($deleteId > 0) {
        $stmt = mysqli_prepare($conn, "SELECT file_path FROM timetables WHERE id = ? AND teacher_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $deleteId, $teacher_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = $result ? mysqli_fetch_assoc($result) : null;
            if ($result) {
                mysqli_free_result($result);
            }
            mysqli_stmt_close($stmt);
            if ($row) {
                if (!empty($row['file_path']) && file_exists($row['file_path'])) {
                    unlink($row['file_path']);
                }
                $deleteStmt = mysqli_prepare($conn, "DELETE FROM timetables WHERE id = ? AND teacher_id = ?");
                if ($deleteStmt) {
                    mysqli_stmt_bind_param($deleteStmt, "ii", $deleteId, $teacher_id);
                    if (mysqli_stmt_execute($deleteStmt)) {
                        $_SESSION['timetable_success'] = 'Timetable deleted successfully.';
                    } else {
                        $_SESSION['timetable_error'] = 'Unable to delete timetable.';
                    }
                    mysqli_stmt_close($deleteStmt);
                } else {
                    $_SESSION['timetable_error'] = 'Unable to delete timetable.';
                }
            } else {
                $_SESSION['timetable_error'] = 'Timetable not found.';
            }
        } else {
            $_SESSION['timetable_error'] = 'Unable to delete timetable.';
        }
    } else {
        $_SESSION['timetable_error'] = 'Invalid timetable selection.';
    }
    header('Location: timetable.php');
    exit;
}

if ($editId > 0) {
    $editSql = "SELECT id, file_name, file_path, uploaded_at";
    if ($timetableHasTermColumn) {
        $editSql .= ", academic_term_id";
    }
    $editSql .= " FROM timetables WHERE id = ? AND teacher_id = ?";
    $stmtEdit = mysqli_prepare($conn, $editSql);
    if ($stmtEdit) {
        mysqli_stmt_bind_param($stmtEdit, "ii", $editId, $teacher_id);
        mysqli_stmt_execute($stmtEdit);
        $resultEdit = mysqli_stmt_get_result($stmtEdit);
        if ($resultEdit) {
            $editingTimetable = mysqli_fetch_assoc($resultEdit);
            mysqli_free_result($resultEdit);
        }
        mysqli_stmt_close($stmtEdit);
    }
}

$timetablesQuery = "SELECT id, file_name, file_path, uploaded_at";
if ($timetableHasTermColumn) {
    $timetablesQuery .= ", academic_term_id";
}
$timetablesQuery .= " FROM timetables WHERE teacher_id = ?";
if ($timetableHasTermColumn && $activeTermId !== null) {
    $timetablesQuery .= " AND (academic_term_id = " . (int)$activeTermId . " OR academic_term_id IS NULL)";
}
$timetablesQuery .= " ORDER BY uploaded_at DESC";
$stmtTimetables = mysqli_prepare($conn, $timetablesQuery);
$timetablesResult = false;
if ($stmtTimetables) {
    mysqli_stmt_bind_param($stmtTimetables, "i", $teacher_id);
    mysqli_stmt_execute($stmtTimetables);
    $timetablesResult = mysqli_stmt_get_result($stmtTimetables);
    mysqli_stmt_close($stmtTimetables);
}

$activeTimelineLabel = ($activeTermId !== null && isset($termLabelById[$activeTermId])) ? $termLabelById[$activeTermId] : '';
$uploadPlaceholder = $activeTimelineLabel !== '' ? 'Use ' . $activeTimelineLabel : 'No timeline';
$columnCount = $timetableHasTermColumn ? 4 : 3;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Timetable - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
                <h2>ICA Tracker</h2>
                <a href="teacher_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
                <a href="update_progress.php"><i class="fas fa-chart-line"></i> <span>Update Progress</span></a>
                <a href="create_ica_components.php"><i class="fas fa-cogs"></i> <span>ICA Components</span></a>
                <a href="manage_ica_marks.php"><i class="fas fa-book"></i> <span>Manage ICA Marks</span></a>
                <a href="assignments.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a>
                <a href="view_alerts.php"><i class="fas fa-bell"></i> <span>View Alerts</span></a>
                <a href="view_reports.php"><i class="fas fa-file-alt"></i> <span>View Reports</span></a>
                <a href="timetable.php" class="active"><i class="fas fa-calendar-alt"></i> <span>Timetable</span></a>
                <a href="edit_profile.php"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>

        <div class="main-content">
                <div class="header">
                    <h2>Welcome, <?php echo htmlspecialchars($teacherNameDisplay); ?>!</h2>
                </div>
                <div class="container">
                    <?php renderTermSwitcher($academicContext, ['school_name' => $teacherSchool]); ?>

                    <?php if ($error !== ''): ?>
                        <div class="card" style="background-color: #fdecea; border: 1px solid #f5c2c7; color: #842029; padding: 12px 16px; margin-bottom: 20px;">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success !== ''): ?>
                        <div class="card" style="background-color: #edf7ed; border: 1px solid #c6e6c6; color: #0f5132; padding: 12px 16px; margin-bottom: 20px;">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                <div class="card">
                        <div class="card-header"><h5>Upload New Timetable</h5></div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="upload_timetable" value="1">
                                <div class="form-group">
                                    <label>Select File (PDF, PNG, JPG, CSV, XLSX, etc.)</label>
                                    <input type="file" name="timetable_file" required>
                                </div>
                                <?php if ($timetableHasTermColumn && !empty($terms)): ?>
                                    <div class="form-group">
                                        <label>Select Timeline</label>
                                        <select name="selected_term_id">
                                            <option value=""><?php echo htmlspecialchars($uploadPlaceholder); ?></option>
                                            <?php foreach ($terms as $termRow): ?>
                                                <?php $termId = (int)$termRow['id']; ?>
                                                <option value="<?php echo $termId; ?>"<?php echo ($uploadTermSelection !== null && $termId === (int)$uploadTermSelection) ? ' selected' : ''; ?>><?php echo htmlspecialchars($termRow['label']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                                <button type="submit" class="btn">Upload</button>
                            </form>
                        </div>
                    </div>

                <?php if ($editingTimetable): ?>
                        <div class="card">
                            <div class="card-header"><h5>Edit Timetable</h5></div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="update_timetable" value="1">
                                    <input type="hidden" name="timetable_id" value="<?php echo (int)$editingTimetable['id']; ?>">
                                    <?php if ($timetableHasTermColumn && !empty($terms)): ?>
                                        <div class="form-group">
                                            <label>Timeline</label>
                                            <select name="edit_term_id">
                                                <option value="">No timeline</option>
                                                <?php foreach ($terms as $termRow): ?>
                                                    <?php $termId = (int)$termRow['id']; ?>
                                                    <option value="<?php echo $termId; ?>"<?php echo (isset($editingTimetable['academic_term_id']) && $editingTimetable['academic_term_id'] !== null && $termId === (int)$editingTimetable['academic_term_id']) ? ' selected' : ''; ?>><?php echo htmlspecialchars($termRow['label']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>
                                    <div class="form-group">
                                        <label>Replace File (optional)</label>
                                        <input type="file" name="edit_timetable_file">
                                    </div>
                                    <button type="submit" class="btn">Save Changes</button>
                                    <a href="timetable.php" class="btn" style="background-color: #6c757d;">Cancel</a>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                <div class="card">
                        <div class="card-header"><h5>My Timetables</h5></div>
                        <div class="card-body">
                            <table>
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <?php if ($timetableHasTermColumn): ?>
                                            <th>Timeline</th>
                                        <?php endif; ?>
                                        <th>Uploaded At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($timetablesResult && mysqli_num_rows($timetablesResult) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($timetablesResult)): ?>
                                            <?php
                                                $rowTimelineLabel = 'No timeline';
                                                $rowTermId = $timetableHasTermColumn ? ($row['academic_term_id'] ?? null) : null;
                                                if ($timetableHasTermColumn) {
                                                    if ($rowTermId !== null && isset($termLabelById[(int)$rowTermId])) {
                                                        $rowTimelineLabel = $termLabelById[(int)$rowTermId];
                                                    } elseif ($rowTermId !== null) {
                                                        $rowTimelineLabel = 'Timeline #' . (int)$rowTermId;
                                                    }
                                                }
                                                $isEditingRow = ($editId > 0 && (int)$row['id'] === $editId);
                                            ?>
                                            <tr<?php echo $isEditingRow ? ' style="background-color: #fff3cd;"' : ''; ?>>
                                                <td><?php echo htmlspecialchars($row['file_name']); ?></td>
                                                <?php if ($timetableHasTermColumn): ?>
                                                    <td><?php echo htmlspecialchars($rowTimelineLabel); ?></td>
                                                <?php endif; ?>
                                                <td><?php echo date("d-M-Y H:i", strtotime($row['uploaded_at'])); ?></td>
                                                <td>
                                                    <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn">View</a>
                                                    <a href="timetable.php?edit_id=<?php echo (int)$row['id']; ?>" class="btn" style="background-color: #0d6efd;">Edit</a>
                                                    <a href="timetable.php?delete_id=<?php echo (int)$row['id']; ?>" class="btn" style="background-color: #dc3545;" onclick="return confirm('Are you sure you want to delete this timetable?');">Delete</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="<?php echo $columnCount; ?>" style="text-align: center;">No timetables have been uploaded yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
