<?php
session_start();
include 'db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: login.php');
    exit;
}
$user_id = (int)$_SESSION['user_id'];
$student_sap = $_SESSION['unique_id'] ?? null;
if ($student_sap === null) {
    $sap_stmt = mysqli_prepare($conn, "SELECT username FROM users WHERE id = ? LIMIT 1");
    if ($sap_stmt) {
        mysqli_stmt_bind_param($sap_stmt, 'i', $user_id);
        mysqli_stmt_execute($sap_stmt);
        $sap_res = mysqli_stmt_get_result($sap_stmt);
        if ($sap_row = mysqli_fetch_assoc($sap_res)) {
            $student_sap = $sap_row['username'];
        }
        mysqli_stmt_close($sap_stmt);
    }
}

if (!$student_sap) {
    echo 'Unable to locate your student record. Please contact the administrator.';
    mysqli_close($conn);
    exit;
}

require_once __DIR__ . '/includes/student_context.php';
$studentName = $_SESSION['name'] ?? '';
$studentContext = buildStudentTermContext($conn, $student_sap, [
    'student_name' => $studentName,
]);
if (!empty($studentContext['error'])) {
    echo htmlspecialchars($studentContext['error']);
    mysqli_close($conn);
    exit;
}

$studentName = $studentContext['student_name'];
$student_id = $studentContext['student_id'];
$student_class_id = $studentContext['class_id'];
$student_section_id = $studentContext['section_id'];
$student_semester = $studentContext['semester_label'];
$school_label = $studentContext['school_label'];
$timelineMismatch = $studentContext['timeline_mismatch'];
$termStartDate = $studentContext['term_start_date'];
$termEndDate = $studentContext['term_end_date'];
$termStartBound = $studentContext['term_start_bound'];
$termEndBound = $studentContext['term_end_bound'];
$academicContext = $studentContext['academic_context'];
$activeTerm = $studentContext['active_term'];

if (!$student_id) {
    $timelineMismatch = true;
}

require_once __DIR__ . '/includes/term_switcher_ui.php';

$formatScoreLabel = static function (?float $value): ?string {
    if ($value === null) {
        return null;
    }
    if (abs($value - round($value)) < 0.05) {
        return (string)round($value);
    }
    return number_format($value, 1);
};

$subjects_list = [];
$subject_names = [];
$assignedElectiveIds = [];
if ($student_id && $student_class_id) {
    $electiveStmt = mysqli_prepare($conn, "SELECT subject_id FROM student_elective_choices WHERE student_id = ? AND class_id = ?");
    if ($electiveStmt) {
        mysqli_stmt_bind_param($electiveStmt, 'ii', $student_id, $student_class_id);
        mysqli_stmt_execute($electiveStmt);
        $electiveResult = mysqli_stmt_get_result($electiveStmt);
        while ($electiveResult && ($row = mysqli_fetch_assoc($electiveResult))) {
            $subjectId = isset($row['subject_id']) ? (int)$row['subject_id'] : 0;
            if ($subjectId > 0) {
                $assignedElectiveIds[$subjectId] = true;
            }
        }
        if ($electiveResult) {
            mysqli_free_result($electiveResult);
        }
        mysqli_stmt_close($electiveStmt);
    }
}

if ($student_class_id && !$timelineMismatch) {
    $subjects_sql = "SELECT DISTINCT s.id, s.subject_name, COALESCE(sd.subject_type, 'regular') AS subject_type
                      FROM teacher_subject_assignments tsa
                      INNER JOIN subjects s ON s.id = tsa.subject_id
                      LEFT JOIN subject_details sd ON sd.subject_id = s.id
                      WHERE tsa.class_id = ?";
    if ($student_section_id) {
        $subjects_sql .= " AND (tsa.section_id = ? OR tsa.section_id IS NULL)";
    }
    $subjects_sql .= " ORDER BY s.subject_name";
    $stmt_subjects = mysqli_prepare($conn, $subjects_sql);
    if ($stmt_subjects) {
        if ($student_section_id) {
            mysqli_stmt_bind_param($stmt_subjects, 'ii', $student_class_id, $student_section_id);
        } else {
            mysqli_stmt_bind_param($stmt_subjects, 'i', $student_class_id);
        }
        mysqli_stmt_execute($stmt_subjects);
        $subjects_result = mysqli_stmt_get_result($stmt_subjects);
        while ($subjects_result && ($row = mysqli_fetch_assoc($subjects_result))) {
            $subjectId = isset($row['id']) ? (int)$row['id'] : 0;
            if ($subjectId > 0) {
                $subjectType = isset($row['subject_type']) ? strtolower((string)$row['subject_type']) : 'regular';
                if ($subjectType === 'elective' && empty($assignedElectiveIds[$subjectId])) {
                    continue;
                }
                $subjectNameRaw = isset($row['subject_name']) ? trim((string)$row['subject_name']) : '';
                $subject_names[$subjectId] = format_subject_display($subjectNameRaw);
            }
        }
        if ($subjects_result) {
            mysqli_free_result($subjects_result);
        }
        mysqli_stmt_close($stmt_subjects);
    }
}

$marks = [];
$marks_by_subject = [];
$subject_stats = [];
$marksDateClause = ($termStartBound && $termEndBound);
if ($student_id && !$timelineMismatch) {
    $marks_query = "
        SELECT s.id AS subject_id, s.subject_name, ic.id AS component_id, ic.component_name, ic.instances, ic.marks_per_instance, ic.total_marks, ic.scaled_total_marks, ism.marks, ism.updated_at, ism.instance_number
          FROM ica_student_marks ism
          JOIN ica_components ic ON ism.component_id = ic.id
          JOIN subjects s ON ic.subject_id = s.id
          JOIN students st ON st.id = ism.student_id
          WHERE ism.student_id = ?
              AND (ic.class_id IS NULL OR ic.class_id = 0 OR ic.class_id = st.class_id)";
    if ($marksDateClause) {
        $marks_query .= " AND ((ism.updated_at BETWEEN ? AND ?) OR ism.updated_at IS NULL)";
    }
    $marks_query .= "
          ORDER BY s.subject_name, ic.component_name, ism.instance_number";
    $stmt_marks = mysqli_prepare($conn, $marks_query);
    if ($stmt_marks) {
        if ($marksDateClause) {
            mysqli_stmt_bind_param($stmt_marks, 'iss', $student_id, $termStartBound, $termEndBound);
        } else {
            mysqli_stmt_bind_param($stmt_marks, 'i', $student_id);
        }
        mysqli_stmt_execute($stmt_marks);
        $marks_result = mysqli_stmt_get_result($stmt_marks);
        while ($marks_result && ($row = mysqli_fetch_assoc($marks_result))) {
            $subjectNameRaw = isset($row['subject_name']) ? trim((string)$row['subject_name']) : '';
            $row['subject_name'] = $subjectNameRaw;
            $row['subject_name_display'] = format_subject_display($subjectNameRaw);
            $subjectId = isset($row['subject_id']) ? (int)$row['subject_id'] : 0;
            if ($subjectId > 0 && !isset($subject_names[$subjectId])) {
                $subjectNameRaw = isset($row['subject_name']) ? trim((string)$row['subject_name']) : '';
                $subject_names[$subjectId] = format_subject_display($subjectNameRaw);
            }
            $instances = isset($row['instances']) ? (int)$row['instances'] : 0;
            if ($instances <= 0) {
                $instances = 1;
            }
            $marksPerInstance = isset($row['marks_per_instance']) ? (float)$row['marks_per_instance'] : 0.0;
            $rawTotal = isset($row['total_marks']) ? (float)$row['total_marks'] : 0.0;
            if ($rawTotal <= 0 && $marksPerInstance > 0) {
                $rawTotal = $marksPerInstance * $instances;
            }
            $scaledTotal = isset($row['scaled_total_marks']) ? (float)$row['scaled_total_marks'] : 0.0;
            if ($scaledTotal <= 0 && $rawTotal > 0) {
                $scaledTotal = $rawTotal;
            }
            $perInstanceCap = null;
            if ($marksPerInstance > 0) {
                $perInstanceCap = $marksPerInstance;
            } elseif ($rawTotal > 0) {
                $perInstanceCap = $rawTotal / $instances;
            } elseif ($scaledTotal > 0) {
                $perInstanceCap = $scaledTotal / $instances;
            }
            $row['per_instance_cap'] = $perInstanceCap;
            $formattedCap = $perInstanceCap !== null ? $formatScoreLabel($perInstanceCap) : null;
            $formattedScore = $row['marks'] !== null ? $formatScoreLabel((float)$row['marks']) : null;
            if ($formattedScore !== null && $formattedCap !== null) {
                $row['marks_display'] = $formattedScore . ' / ' . $formattedCap;
            } elseif ($formattedScore !== null) {
                $row['marks_display'] = $formattedScore;
            } else {
                $row['marks_display'] = null;
            }
            $row['marks_denominator'] = $formattedCap;
            $row['marks_formatted'] = $formattedScore;
            $marks[] = $row;
            if ($subjectId > 0) {
                $marks_by_subject[$subjectId][] = $row;
                if (!isset($subject_stats[$subjectId])) {
                    $subject_stats[$subjectId] = [
                        'total' => 0,
                        'graded' => 0,
                        'pending' => 0,
                        'last_updated' => null,
                    ];
                }
                $subject_stats[$subjectId]['total']++;
                if ($row['marks'] !== null) {
                    $subject_stats[$subjectId]['graded']++;
                } else {
                    $subject_stats[$subjectId]['pending']++;
                }
                if (!empty($row['updated_at'])) {
                    $timestamp = strtotime($row['updated_at']);
                    if ($timestamp && ($subject_stats[$subjectId]['last_updated'] === null || $timestamp > $subject_stats[$subjectId]['last_updated'])) {
                        $subject_stats[$subjectId]['last_updated'] = $timestamp;
                    }
                }
            }
        }
        if ($marks_result) {
            mysqli_free_result($marks_result);
        }
        mysqli_stmt_close($stmt_marks);
    }
}

$subjects_list = [];
foreach ($subject_names as $subjectId => $subjectNameLabel) {
    $label = trim((string)$subjectNameLabel);
    if ($label === '') {
        $label = 'Subject ' . $subjectId;
    }
    $displayLabel = format_subject_display($label);
    $subjects_list[] = [
        'id' => (int)$subjectId,
        'name' => $displayLabel,
        'name_display' => $displayLabel
    ];
}
usort($subjects_list, static function ($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});

$selected_subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : null;
if ($selected_subject_id === 0) {
    $selected_subject_id = null;
}
if ($selected_subject_id === null && !empty($subjects_list)) {
    $selected_subject_id = $subjects_list[0]['id'];
}
if ($selected_subject_id === null && !empty($marks_by_subject)) {
    $selected_subject_id = (int)array_key_first($marks_by_subject);
}
$selected_subject_name = $selected_subject_id !== null && isset($subject_names[$selected_subject_id])
    ? $subject_names[$selected_subject_id]
    : 'Subject';
$selected_subject_marks = $selected_subject_id !== null && isset($marks_by_subject[$selected_subject_id])
    ? $marks_by_subject[$selected_subject_id]
    : [];
$selected_stats = $selected_subject_id !== null && isset($subject_stats[$selected_subject_id])
    ? $subject_stats[$selected_subject_id]
    : ['total' => 0, 'graded' => 0, 'pending' => 0, 'last_updated' => null];
$lastUpdatedAt = $selected_stats['last_updated'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Marks - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .semester-banner {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 12px 0 4px;
        }
        .subject-chip-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 12px 0 24px;
        }
        .subject-chip {
            border: 1px solid #A6192E;
            color: #A6192E;
            padding: 6px 14px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .subject-chip:hover {
            background-color: #A6192E;
            color: #fff;
        }
        .subject-chip.active {
            background-color: #A6192E;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h2>ICA Tracker</h2>
            <a href="student_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="view_marks.php" class="active"><i class="fas fa-chart-line"></i> <span>Marks</span></a>
            <a href="subject_comparison.php"><i class="fas fa-balance-scale"></i> <span>Subject Comparison</span></a>
            <a href="view_assignment_marks.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a>
            <a href="view_timetable.php"><i class="fas fa-calendar-alt"></i> <span>Timetable</span></a>
            <a href="view_progress.php"><i class="fas fa-book"></i> <span>Syllabus Progress</span></a>
            <a href="edit_profile.php"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
        <div class="main-content">
            <div class="header">
                <div>
                    <h2>ICA Marks</h2>
                    <p>Track every ICA component and your latest scores.</p>
                </div>
                <?php if ($lastUpdatedAt): ?>
                    <span class="tag">Last updated: <?php echo date('d M Y', $lastUpdatedAt); ?></span>
                <?php endif; ?>
            </div>
            <div class="container">
                <?php if ($timelineMismatch): ?>
                    <div class="card" style="border-left:4px solid #A6192E;">
                        <h3 class="section-title">No records for this term</h3>
                        <p>The selected academic timeline does not have a class assignment yet. Update the term from your dashboard to review marks from another period.</p>
                    </div>
                <?php else: ?>
                    <div class="semester-banner">
                        <?php if ($student_semester !== null): ?>
                            Semester <?php echo htmlspecialchars($student_semester); ?>
                        <?php else: ?>
                            Semester not specified
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($subjects_list)): ?>
                        <div class="subject-chip-group">
                            <?php foreach ($subjects_list as $subjectItem): ?>
                                <?php $isActive = ($selected_subject_id === (int)$subjectItem['id']); ?>
                                <a class="subject-chip<?php echo $isActive ? ' active' : ''; ?>" href="?subject_id=<?php echo (int)$subjectItem['id']; ?>">
                                    <?php echo htmlspecialchars($subjectItem['name']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">Subjects will appear here once your class assignments are published.</div>
                    <?php endif; ?>

                    <div class="card-grid">
                        <div class="card stat-card">
                            <span class="stat-label">Components graded</span>
                            <h3><?php echo (int)$selected_stats['graded']; ?></h3>
                            <p>Total components evaluated for this subject.</p>
                        </div>
                        <?php $pendingCount = (int)$selected_stats['pending']; ?>
                        <div class="card stat-card<?php echo $pendingCount > 0 ? '' : ' success'; ?>">
                            <span class="stat-label">Pending evaluations</span>
                            <h3><?php echo $pendingCount; ?></h3>
                            <p><?php echo $pendingCount > 0 ? 'Awaiting grades on these components.' : 'All components graded.'; ?></p>
                        </div>
                        <div class="card stat-card secondary">
                            <span class="stat-label">Total components</span>
                            <h3><?php echo (int)$selected_stats['total']; ?></h3>
                            <p>Evaluations planned for this subject.</p>
                        </div>
                    </div>

                    <div class="card">
                        <h3 class="section-title"><?php echo htmlspecialchars($selected_subject_name); ?> ICA Marks</h3>
                        <?php if (!empty($selected_subject_marks)): ?>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="text-left">Subject</th>
                                            <th class="text-left">Component</th>
                                            <th>Marks Obtained</th>
                                            <th>Updated</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($selected_subject_marks as $mark): ?>
                                            <?php
                                                $instanceNumber = isset($mark['instance_number']) ? (int)$mark['instance_number'] : null;
                                                $componentLabel = $mark['component_name'] ?? '';
                                                if ($instanceNumber !== null && $instanceNumber > 0) {
                                                    $componentLabel = rtrim($componentLabel) . ' ' . $instanceNumber;
                                                }
                                            ?>
                                            <tr>
                                                <td class="text-left"><?php echo htmlspecialchars($mark['subject_name_display'] ?? $mark['subject_name']); ?></td>
                                                <td class="text-left"><?php echo htmlspecialchars($componentLabel); ?></td>
                                                <td>
                                                    <?php
                                                        if ($mark['marks'] !== null) {
                                                            $display = $mark['marks_display'] ?? $formatScoreLabel((float)$mark['marks']);
                                                            echo htmlspecialchars($display ?? '');
                                                        } else {
                                                            echo 'AB';
                                                        }
                                                    ?>
                                                </td>
                                                <td><?php echo $mark['updated_at'] ? date('d M Y', strtotime($mark['updated_at'])) : '—'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <p class="table-note">Marks shown above are the raw scores awarded for each ICA component.</p>
                        <?php else: ?>
                            <div class="empty-state">ICA marks for <?php echo htmlspecialchars($selected_subject_name); ?> will appear here once evaluations begin.</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php
mysqli_close($conn);
?>
