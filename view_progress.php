<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/academic_context.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}

if (!function_exists('has_db_column')) {
    function has_db_column(mysqli $conn, string $table, string $column): bool
    {
        $tableSafe = mysqli_real_escape_string($conn, preg_replace('/[^A-Za-z0-9_]/', '', $table));
        $columnSafe = mysqli_real_escape_string($conn, preg_replace('/[^A-Za-z0-9_]/', '', $column));
        $sql = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tableSafe}' AND COLUMN_NAME = '{$columnSafe}'";
        $result = mysqli_query($conn, $sql);
        if (!$result) {
            return false;
        }
        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        return (int)($row['cnt'] ?? 0) > 0;
    }
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

$classId = $studentInfo['class_id'] ? (int)$studentInfo['class_id'] : 0;
$sectionId = $studentInfo['section_id'] ? (int)$studentInfo['section_id'] : 0;
$className = $studentInfo['class_name'] ?? 'N/A';
$sectionName = $studentInfo['section_name'] ?? 'N/A';
$classSemester = $studentInfo['semester'] ?? null;
$semesterLabel = $classSemester ?? 'N/A';
$schoolLabel = $studentInfo['school'] ?? 'N/A';

$classYearNumber = null;
if (preg_match('/\b([1-4])(?:st|nd|rd|th)?\b/i', (string)$className, $classYearMatch)) {
    $classYearNumber = (int)$classYearMatch[1];
}

$academicContext = resolveAcademicContext($conn, [
    'school_name' => $schoolLabel,
    'default_semester' => is_numeric((string)$semesterLabel) ? (int)$semesterLabel : null,
]);
$activeTerm = $academicContext['active'] ?? null;
$activeTermId = isset($activeTerm['id']) ? (int)$activeTerm['id'] : 0;
$termDateFilter = $academicContext['date_filter'] ?? null;
$termStartDate = $termDateFilter['start'] ?? null;
$termEndDate = $termDateFilter['end'] ?? null;
$termStartBound = $termStartDate ? $termStartDate . ' 00:00:00' : null;
$termEndBound = $termEndDate ? $termEndDate . ' 23:59:59' : null;

$effectiveSemester = null;
if ($classYearNumber !== null && !empty($activeTerm['semester_term'])) {
    $termType = strtolower((string)$activeTerm['semester_term']);
    if ($termType === 'odd' || $termType === 'even') {
        $effectiveSemester = (($classYearNumber - 1) * 2) + ($termType === 'odd' ? 1 : 2);
    }
}

if ($effectiveSemester === null && isset($activeTerm['semester_number']) && $activeTerm['semester_number'] !== null) {
    $activeSemesterNumber = (int)$activeTerm['semester_number'];
    if ($classYearNumber !== null && $activeSemesterNumber >= 1 && $activeSemesterNumber <= 2) {
        $effectiveSemester = (($classYearNumber - 1) * 2) + $activeSemesterNumber;
    } elseif ($activeSemesterNumber > 0) {
        $effectiveSemester = $activeSemesterNumber;
    }
}

if ($effectiveSemester === null && $classSemester !== null && $classSemester !== '') {
    $effectiveSemester = (int)$classSemester;
}

if ($effectiveSemester !== null && $effectiveSemester > 0) {
    $semesterLabel = (string)$effectiveSemester;
} elseif ($classSemester !== null && $classSemester !== '') {
    $semesterLabel = (string)$classSemester;
} else {
    $semesterLabel = 'N/A';
}

$termHeadlineParts = [];
if (isset($activeTerm['semester_number']) && $activeTerm['semester_number'] !== null && (int)$activeTerm['semester_number'] > 0) {
    $termHeadlineParts[] = 'Semester ' . (int)$activeTerm['semester_number'];
} elseif ($effectiveSemester !== null && $effectiveSemester > 0) {
    $termHeadlineParts[] = 'Semester ' . $effectiveSemester;
}
if (!empty($activeTerm['semester_term'])) {
    $termHeadlineParts[] = ucfirst((string)$activeTerm['semester_term']) . ' Term';
}
if (!empty($activeTerm['academic_year'])) {
    $termHeadlineParts[] = 'AY ' . (string)$activeTerm['academic_year'];
}
$termHeadline = !empty($termHeadlineParts) ? implode(' • ', $termHeadlineParts) : 'Current academic timeline';
$termDateLine = ($termStartDate && $termEndDate)
    ? date('d M Y', strtotime($termStartDate)) . ' - ' . date('d M Y', strtotime($termEndDate))
    : 'Timeline dates unavailable';

$timelineProgressPercent = null;
$currentWeekLabel = 'N/A';
$timelineFactor = 1.0;
if ($termStartDate && $termEndDate) {
    try {
        $startDateObj = new DateTime($termStartDate);
        $endDateObj = new DateTime($termEndDate);
        $todayObj = new DateTime('today');

        $totalDays = max(1, $startDateObj->diff($endDateObj)->days + 1);
        if ($todayObj < $startDateObj) {
            $timelineProgressPercent = 0.0;
            $currentWeekLabel = 'Not started';
        } elseif ($todayObj > $endDateObj) {
            $timelineProgressPercent = 100.0;
            $currentWeekLabel = 'Term completed';
        } else {
            $elapsedDays = max(0, $startDateObj->diff($todayObj)->days + 1);
            $timelineProgressPercent = round(($elapsedDays / $totalDays) * 100, 1);
            $currentWeekLabel = 'Week ' . ((int)floor(($elapsedDays - 1) / 7) + 1);
        }
        $timelineFactor = max(0.0, min(1.0, ((float)$timelineProgressPercent) / 100));
    } catch (Exception $exception) {
        $timelineProgressPercent = null;
        $currentWeekLabel = 'N/A';
        $timelineFactor = 1.0;
    }
}

$hasTutorialHoursColumn = has_db_column($conn, 'subject_details', 'tutorial_hours');
$hasActualTheoryColumn = has_db_column($conn, 'syllabus_progress', 'actual_theory_hours');
$hasActualPracticalColumn = has_db_column($conn, 'syllabus_progress', 'actual_practical_hours');
$hasExtraTheoryColumn = has_db_column($conn, 'syllabus_progress', 'extra_theory_hours');
$hasExtraPracticalColumn = has_db_column($conn, 'syllabus_progress', 'extra_practical_hours');
$hasSpClassColumn = has_db_column($conn, 'syllabus_progress', 'class_id');
$hasSpSectionColumn = has_db_column($conn, 'syllabus_progress', 'section_id');

$tutorialSelectExpr = $hasTutorialHoursColumn ? 'COALESCE(sd.tutorial_hours, 0)' : '0';
$actualTheoryExpr = $hasActualTheoryColumn ? 'COALESCE(sp.actual_theory_hours, 0)' : '0';
$actualPracticalExpr = $hasActualPracticalColumn ? 'COALESCE(sp.actual_practical_hours, 0)' : '0';
$extraTheoryExpr = $hasExtraTheoryColumn ? 'COALESCE(sp.extra_theory_hours, 0)' : '0';
$extraPracticalExpr = $hasExtraPracticalColumn ? 'COALESCE(sp.extra_practical_hours, 0)' : '0';

$subjectMetrics = [];
if ($classId > 0) {
    $subjectPlanSql = "SELECT s.subject_name,
            COALESCE(s.total_planned_hours, 0) AS total_planned_hours,
            COALESCE(sd.theory_hours, 0) AS theory_hours,
            COALESCE(sd.practical_hours, 0) AS practical_hours,
            {$tutorialSelectExpr} AS tutorial_hours
        FROM teacher_subject_assignments tsa
        INNER JOIN subjects s ON s.id = tsa.subject_id
        LEFT JOIN subject_details sd ON sd.subject_id = s.id
        INNER JOIN classes c ON c.id = tsa.class_id
        WHERE tsa.class_id = " . (int)$classId;

    if ($activeTermId > 0) {
        $subjectPlanSql .= " AND c.academic_term_id = " . (int)$activeTermId;
    }
    if ($sectionId > 0) {
        $subjectPlanSql .= " AND (tsa.section_id IS NULL OR tsa.section_id = 0 OR tsa.section_id = " . (int)$sectionId . ")";
    }

    $subjectPlanSql .= " GROUP BY s.id, s.subject_name, s.total_planned_hours, sd.theory_hours, sd.practical_hours";

    $subjectPlanResult = mysqli_query($conn, $subjectPlanSql);
    if ($subjectPlanResult) {
        while ($planRow = mysqli_fetch_assoc($subjectPlanResult)) {
            $subjectNameRaw = trim((string)($planRow['subject_name'] ?? ''));
            if ($subjectNameRaw === '') {
                continue;
            }
            $subjectKey = strtolower($subjectNameRaw);

            $theoryPlan = max(0.0, (float)($planRow['theory_hours'] ?? 0));
            $practicalPlan = max(0.0, (float)($planRow['practical_hours'] ?? 0));
            $tutorialPlan = max(0.0, (float)($planRow['tutorial_hours'] ?? 0));
            $totalPlan = max(0.0, (float)($planRow['total_planned_hours'] ?? 0));
            if (($theoryPlan + $practicalPlan + $tutorialPlan) <= 0.0 && $totalPlan > 0.0) {
                // Fallback when component split is not configured in subject_details.
                $theoryPlan = $totalPlan;
            }

            $subjectMetrics[$subjectKey] = [
                'subject_name' => $subjectNameRaw,
                'theory_plan' => $theoryPlan,
                'practical_plan' => $practicalPlan,
                'tutorial_plan' => $tutorialPlan,
                'theory_actual' => 0.0,
                'practical_actual' => 0.0,
                'tutorial_actual' => 0.0,
                'completion_percentage' => 0.0,
                'topic' => null,
                'timeline' => null,
                'updated_at' => null,
                'teacher_name_display' => 'NOT ASSIGNED',
            ];
        }
        mysqli_free_result($subjectPlanResult);
    }
}

$progressRecords = [];
$averageCompletion = null;
$subjectsBehind = 0;
$subjectsOnTrack = 0;
$lastProgressUpdate = null;

if ($classId > 0) {
    $subqueryWhere = [];
    if ($hasSpClassColumn) {
        $subqueryWhere[] = 'class_id = ' . (int)$classId;
    }
    if ($termStartBound && $termEndBound) {
        $termStartEsc = mysqli_real_escape_string($conn, $termStartBound);
        $termEndEsc = mysqli_real_escape_string($conn, $termEndBound);
        $subqueryWhere[] = "updated_at BETWEEN '{$termStartEsc}' AND '{$termEndEsc}'";
    }
    $subqueryWhereSql = !empty($subqueryWhere) ? ('WHERE ' . implode(' AND ', $subqueryWhere)) : '';

    $latestSelect = 'subject, teacher_id';
    $latestGroup = 'subject, teacher_id';
    $latestJoin = 'latest.subject = sp.subject AND latest.teacher_id = sp.teacher_id';

    if ($hasSpClassColumn) {
        $latestSelect .= ', class_id';
        $latestGroup .= ', class_id';
        $latestJoin .= ' AND latest.class_id = sp.class_id';
    }
    if ($hasSpSectionColumn) {
        $latestSelect .= ', COALESCE(section_id, 0) AS section_key';
        $latestGroup .= ', COALESCE(section_id, 0)';
        $latestJoin .= ' AND latest.section_key = COALESCE(sp.section_id, 0)';
    }

    $progressSql = "SELECT
            s.subject_name,
            COALESCE(s.total_planned_hours, 0) AS total_planned_hours,
            sp.topic,
            sp.timeline,
            sp.modules_completed,
            sp.planned_hours,
            sp.actual_hours,
            sp.completion_percentage,
            sp.updated_at,
            {$actualTheoryExpr} AS actual_theory_hours,
            {$actualPracticalExpr} AS actual_practical_hours,
            {$extraTheoryExpr} AS extra_theory_hours,
            {$extraPracticalExpr} AS extra_practical_hours,
            COALESCE(sd.theory_hours, 0) AS theory_hours,
            COALESCE(sd.practical_hours, 0) AS practical_hours,
            {$tutorialSelectExpr} AS tutorial_hours,
            u.name AS teacher_name
        FROM syllabus_progress sp
        INNER JOIN (
            SELECT {$latestSelect}, MAX(updated_at) AS latest_update
            FROM syllabus_progress
            {$subqueryWhereSql}
            GROUP BY {$latestGroup}
        ) latest ON {$latestJoin} AND latest.latest_update = sp.updated_at
        INNER JOIN subjects s ON s.subject_name = sp.subject
        LEFT JOIN subject_details sd ON sd.subject_id = s.id
        INNER JOIN teacher_subject_assignments tsa ON tsa.subject_id = s.id AND tsa.teacher_id = sp.teacher_id
        INNER JOIN classes c ON c.id = tsa.class_id
        LEFT JOIN users u ON u.id = sp.teacher_id
        WHERE tsa.class_id = " . (int)$classId;

    if ($activeTermId > 0) {
        $progressSql .= " AND c.academic_term_id = " . (int)$activeTermId;
    }
    if ($sectionId > 0) {
        $progressSql .= " AND (tsa.section_id IS NULL OR tsa.section_id = 0 OR tsa.section_id = " . (int)$sectionId . ")";
    }
    if ($hasSpClassColumn) {
        $progressSql .= ' AND (sp.class_id = tsa.class_id OR sp.class_id = 0)';
    }
    if ($hasSpSectionColumn && $sectionId > 0) {
        $progressSql .= ' AND (COALESCE(sp.section_id, 0) = 0 OR COALESCE(sp.section_id, 0) = ' . (int)$sectionId . ')';
    }
    if ($termStartBound && $termEndBound) {
        $termStartEsc = mysqli_real_escape_string($conn, $termStartBound);
        $termEndEsc = mysqli_real_escape_string($conn, $termEndBound);
        $progressSql .= " AND sp.updated_at BETWEEN '{$termStartEsc}' AND '{$termEndEsc}'";
    }

    $progressSql .= ' ORDER BY s.subject_name';

    $progressRes = mysqli_query($conn, $progressSql);
    if ($progressRes) {
        while ($row = mysqli_fetch_assoc($progressRes)) {
            $subjectNameRaw = trim((string)($row['subject_name'] ?? ''));
            if ($subjectNameRaw === '') {
                continue;
            }
            $subjectKey = strtolower($subjectNameRaw);
            if (!isset($subjectMetrics[$subjectKey])) {
                $theoryPlan = max(0.0, (float)($row['theory_hours'] ?? 0));
                $practicalPlan = max(0.0, (float)($row['practical_hours'] ?? 0));
                $tutorialPlan = max(0.0, (float)($row['tutorial_hours'] ?? 0));
                $totalPlan = max(0.0, (float)($row['total_planned_hours'] ?? 0));
                if (($theoryPlan + $practicalPlan + $tutorialPlan) <= 0.0 && $totalPlan > 0.0) {
                    $theoryPlan = $totalPlan;
                }

                $subjectMetrics[$subjectKey] = [
                    'subject_name' => $subjectNameRaw,
                    'theory_plan' => $theoryPlan,
                    'practical_plan' => $practicalPlan,
                    'tutorial_plan' => $tutorialPlan,
                    'theory_actual' => 0.0,
                    'practical_actual' => 0.0,
                    'tutorial_actual' => 0.0,
                    'completion_percentage' => 0.0,
                    'topic' => null,
                    'timeline' => null,
                    'updated_at' => null,
                    'teacher_name_display' => 'NOT ASSIGNED',
                ];
            }

            $teacherNameRaw = isset($row['teacher_name']) ? trim((string)$row['teacher_name']) : '';
            $teacherNameDisplay = $teacherNameRaw !== '' ? format_person_display($teacherNameRaw) : 'NOT ASSIGNED';

            $existingPlanTotal =
                max(0.0, (float)($subjectMetrics[$subjectKey]['theory_plan'] ?? 0)) +
                max(0.0, (float)($subjectMetrics[$subjectKey]['practical_plan'] ?? 0)) +
                max(0.0, (float)($subjectMetrics[$subjectKey]['tutorial_plan'] ?? 0));
            if ($existingPlanTotal <= 0.0) {
                $fallbackTotalPlan = max(0.0, (float)($row['total_planned_hours'] ?? 0));
                if ($fallbackTotalPlan <= 0.0) {
                    $fallbackTotalPlan = max(0.0, (float)($row['planned_hours'] ?? 0));
                }
                if ($fallbackTotalPlan > 0.0) {
                    $subjectMetrics[$subjectKey]['theory_plan'] = $fallbackTotalPlan;
                    $subjectMetrics[$subjectKey]['practical_plan'] = 0.0;
                    $subjectMetrics[$subjectKey]['tutorial_plan'] = 0.0;
                }
            }

            $plannedTheory = max(0.0, (float)($subjectMetrics[$subjectKey]['theory_plan'] ?? 0));
            $plannedPractical = max(0.0, (float)($subjectMetrics[$subjectKey]['practical_plan'] ?? 0));
            $plannedTutorial = max(0.0, (float)($subjectMetrics[$subjectKey]['tutorial_plan'] ?? 0));

            $actualTheory = max(0.0, (float)($row['actual_theory_hours'] ?? 0) + (float)($row['extra_theory_hours'] ?? 0));
            $actualLab = max(0.0, (float)($row['actual_practical_hours'] ?? 0) + (float)($row['extra_practical_hours'] ?? 0));
            $actualTotal = (float)($row['actual_hours'] ?? 0);

            if ($actualTheory <= 0 && $actualLab <= 0 && $actualTotal > 0) {
                $planTotal = $plannedTheory + $plannedPractical + $plannedTutorial;
                if ($planTotal > 0) {
                    $actualTheory = $actualTotal * ($plannedTheory / $planTotal);
                    $actualLab = $actualTotal - $actualTheory;
                } else {
                    $actualLab = $actualTotal;
                }
            }

            $labPlan = $plannedPractical + $plannedTutorial;
            if ($labPlan > 0) {
                $actualPractical = $actualLab * ($plannedPractical / $labPlan);
                $actualTutorial = $actualLab * ($plannedTutorial / $labPlan);
            } else {
                $actualPractical = $actualLab;
                $actualTutorial = 0.0;
            }

            $currentUpdatedTs = !empty($row['updated_at']) ? strtotime((string)$row['updated_at']) : 0;
            $existingUpdatedTs = !empty($subjectMetrics[$subjectKey]['updated_at']) ? strtotime((string)$subjectMetrics[$subjectKey]['updated_at']) : 0;

            if ($currentUpdatedTs >= $existingUpdatedTs) {
                $subjectMetrics[$subjectKey]['theory_actual'] = max(0.0, $actualTheory);
                $subjectMetrics[$subjectKey]['practical_actual'] = max(0.0, $actualPractical);
                $subjectMetrics[$subjectKey]['tutorial_actual'] = max(0.0, $actualTutorial);
                $subjectMetrics[$subjectKey]['completion_percentage'] = (float)($row['completion_percentage'] ?? 0);
                $subjectMetrics[$subjectKey]['topic'] = $row['topic'] ?? null;
                $subjectMetrics[$subjectKey]['timeline'] = $row['timeline'] ?? null;
                $subjectMetrics[$subjectKey]['updated_at'] = $row['updated_at'] ?? null;
                $subjectMetrics[$subjectKey]['teacher_name_display'] = $teacherNameDisplay;

                if ($currentUpdatedTs > 0 && ($lastProgressUpdate === null || $currentUpdatedTs > $lastProgressUpdate)) {
                    $lastProgressUpdate = $currentUpdatedTs;
                }
            }
        }
        mysqli_free_result($progressRes);
    }
}

foreach ($subjectMetrics as $metric) {
    $progressRecords[] = [
        'subject_name' => $metric['subject_name'],
        'teacher_name_display' => $metric['teacher_name_display'],
        'topic' => $metric['topic'],
        'timeline' => $metric['timeline'],
        'modules_completed' => null,
        'theory_plan' => (float)$metric['theory_plan'],
        'theory_actual' => (float)$metric['theory_actual'],
        'practical_plan' => (float)$metric['practical_plan'],
        'practical_actual' => (float)$metric['practical_actual'],
        'tutorial_plan' => (float)$metric['tutorial_plan'],
        'tutorial_actual' => (float)$metric['tutorial_actual'],
        'planned_hours' => $metric['theory_plan'] + $metric['practical_plan'] + $metric['tutorial_plan'],
        'actual_hours' => $metric['theory_actual'] + $metric['practical_actual'] + $metric['tutorial_actual'],
        'completion_percentage' => $metric['completion_percentage'],
        'updated_at' => $metric['updated_at'],
    ];

    $pct = (float)$metric['completion_percentage'];
    if ($pct < 50) {
        $subjectsBehind++;
    } else {
        $subjectsOnTrack++;
    }
}

usort($progressRecords, static function (array $left, array $right): int {
    return strcmp((string)$left['subject_name'], (string)$right['subject_name']);
});

if (!empty($progressRecords)) {
    $totalPct = 0;
    foreach ($progressRecords as $record) {
        $totalPct += (float)($record['completion_percentage'] ?? 0);
    }
    $averageCompletion = round($totalPct / count($progressRecords), 1);
}

$theoryPlannedTotal = 0.0;
$practicalPlannedTotal = 0.0;
$tutorialPlannedTotal = 0.0;
$theoryActualTotal = 0.0;
$practicalActualTotal = 0.0;
$tutorialActualTotal = 0.0;

foreach ($subjectMetrics as $metric) {
    $theoryPlannedTotal += max(0.0, (float)$metric['theory_plan']);
    $practicalPlannedTotal += max(0.0, (float)$metric['practical_plan']);
    $tutorialPlannedTotal += max(0.0, (float)$metric['tutorial_plan']);

    $theoryActualTotal += max(0.0, (float)$metric['theory_actual']);
    $practicalActualTotal += max(0.0, (float)$metric['practical_actual']);
    $tutorialActualTotal += max(0.0, (float)$metric['tutorial_actual']);
}

$theoryPlannedTillNow = $theoryPlannedTotal * $timelineFactor;
$practicalPlannedTillNow = $practicalPlannedTotal * $timelineFactor;
$tutorialPlannedTillNow = $tutorialPlannedTotal * $timelineFactor;

$theoryCompletedTillNow = min($theoryActualTotal, $theoryPlannedTillNow);
$practicalCompletedTillNow = min($practicalActualTotal, $practicalPlannedTillNow);
$tutorialCompletedTillNow = min($tutorialActualTotal, $tutorialPlannedTillNow);

$theoryPendingTillNow = max(0.0, $theoryPlannedTillNow - $theoryCompletedTillNow);
$practicalPendingTillNow = max(0.0, $practicalPlannedTillNow - $practicalCompletedTillNow);
$tutorialPendingTillNow = max(0.0, $tutorialPlannedTillNow - $tutorialCompletedTillNow);

$totalPlannedTillNow = $theoryPlannedTillNow + $practicalPlannedTillNow + $tutorialPlannedTillNow;
$totalCompletedTillNow = $theoryCompletedTillNow + $practicalCompletedTillNow + $tutorialCompletedTillNow;
$totalPendingTillNow = $theoryPendingTillNow + $practicalPendingTillNow + $tutorialPendingTillNow;

$subjectTimelineChartData = [];
foreach ($subjectMetrics as $metric) {
    $subjectName = trim((string)($metric['subject_name'] ?? ''));
    if ($subjectName === '') {
        continue;
    }

    $subjectTheoryPlan = max(0.0, (float)$metric['theory_plan']);
    $subjectPracticalPlan = max(0.0, (float)$metric['practical_plan']);
    $subjectTutorialPlan = max(0.0, (float)$metric['tutorial_plan']);

    $subjectTheoryActual = max(0.0, (float)$metric['theory_actual']);
    $subjectPracticalActual = max(0.0, (float)$metric['practical_actual']);
    $subjectTutorialActual = max(0.0, (float)$metric['tutorial_actual']);

    $subjectTheoryPlannedTillNow = $subjectTheoryPlan * $timelineFactor;
    $subjectPracticalPlannedTillNow = $subjectPracticalPlan * $timelineFactor;
    $subjectTutorialPlannedTillNow = $subjectTutorialPlan * $timelineFactor;

    $subjectTheoryCompletedTillNow = min($subjectTheoryActual, $subjectTheoryPlannedTillNow);
    $subjectPracticalCompletedTillNow = min($subjectPracticalActual, $subjectPracticalPlannedTillNow);
    $subjectTutorialCompletedTillNow = min($subjectTutorialActual, $subjectTutorialPlannedTillNow);

    $subjectTheoryPendingTillNow = max(0.0, $subjectTheoryPlannedTillNow - $subjectTheoryCompletedTillNow);
    $subjectPracticalPendingTillNow = max(0.0, $subjectPracticalPlannedTillNow - $subjectPracticalCompletedTillNow);
    $subjectTutorialPendingTillNow = max(0.0, $subjectTutorialPlannedTillNow - $subjectTutorialCompletedTillNow);

    $subjectTimelineChartData[$subjectName] = [
        'completed' => [
            round($subjectTheoryCompletedTillNow, 1),
            round($subjectPracticalCompletedTillNow, 1),
            round($subjectTutorialCompletedTillNow, 1),
        ],
        'pending' => [
            round($subjectTheoryPendingTillNow, 1),
            round($subjectPracticalPendingTillNow, 1),
            round($subjectTutorialPendingTillNow, 1),
        ],
        'plannedTillNow' => [
            round($subjectTheoryPlannedTillNow, 1),
            round($subjectPracticalPlannedTillNow, 1),
            round($subjectTutorialPlannedTillNow, 1),
        ],
        'actual' => [
            round($subjectTheoryActual, 1),
            round($subjectPracticalActual, 1),
            round($subjectTutorialActual, 1),
        ],
    ];
}
if (!empty($subjectTimelineChartData)) {
    ksort($subjectTimelineChartData, SORT_NATURAL | SORT_FLAG_CASE);
}

$timelineChartData = [
    'labels' => ['Theory', 'Practical', 'Tutorial'],
    'overall' => [
        'completed' => [
            round($theoryCompletedTillNow, 1),
            round($practicalCompletedTillNow, 1),
            round($tutorialCompletedTillNow, 1),
        ],
        'pending' => [
            round($theoryPendingTillNow, 1),
            round($practicalPendingTillNow, 1),
            round($tutorialPendingTillNow, 1),
        ],
        'plannedTillNow' => [
            round($theoryPlannedTillNow, 1),
            round($practicalPlannedTillNow, 1),
            round($tutorialPlannedTillNow, 1),
        ],
        'actual' => [
            round($theoryActualTotal, 1),
            round($practicalActualTotal, 1),
            round($tutorialActualTotal, 1),
        ],
    ],
    'subjects' => $subjectTimelineChartData,
];

if ($stmtStudent) {
    mysqli_stmt_close($stmtStudent);
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.timeline-note {
    margin-top: 6px;
    color: #63666A;
    font-size: 0.9rem;
}
.view-progress-topbar {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: start;
    gap: 12px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    padding: 14px 16px;
    margin-bottom: 16px;
}
.view-progress-topbar-info {
    min-width: 0;
}
.view-progress-topbar-info h2 {
    margin: 0;
    line-height: 1.2;
    font-size: 1.6rem;
    color: #A6192E;
    font-weight: 600;
}
.view-progress-topbar-info p {
    margin: 6px 0 0;
    color: #63666A;
    font-size: 0.9rem;
}
.view-progress-topbar-side {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
}
.view-progress-topbar-logo {
    width: 170px;
    height: 56px;
    background: url('nmimshorizontal.jpg') center/contain no-repeat;
    flex-shrink: 0;
}
.timeline-card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}
.timeline-card-grid .stat-card {
    min-height: 106px;
}
.timeline-stat-value {
    font-size: 1.4rem;
    font-weight: 700;
    color: #1f2d3d;
    line-height: 1.15;
    overflow-wrap: anywhere;
}
.timeline-stat-sub {
    margin-top: 4px;
    font-size: 0.85rem;
    color: #63666A;
}
.chart-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 6px;
}
.chart-controls {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
}
.chart-filter-top {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 8px;
}
.chart-filter-label {
    color: #63666A;
    font-size: 0.85rem;
    font-weight: 600;
}
.chart-subject-list {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 6px;
    max-width: 620px;
}
.chart-subject-list-label {
    color: #63666A;
    font-size: 0.78rem;
    font-weight: 600;
}
.chart-subject-chip {
    border: 1px solid rgba(99, 102, 106, 0.28);
    background: rgba(99, 102, 106, 0.1);
    color: #2c3e50;
    border-radius: 999px;
    padding: 3px 10px;
    font-size: 0.76rem;
    line-height: 1.2;
    cursor: pointer;
    transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
}
.chart-subject-chip:hover,
.chart-subject-chip:focus-visible {
    border-color: rgba(166, 25, 46, 0.35);
    background: rgba(166, 25, 46, 0.09);
    color: #A6192E;
    outline: none;
}
.chart-subject-chip.active {
    border-color: rgba(166, 25, 46, 0.42);
    background: rgba(166, 25, 46, 0.16);
    color: #A6192E;
}
.chart-filter-select {
    min-width: 190px;
    max-width: 280px;
    width: auto;
    margin: 0;
    padding: 6px 10px;
    border-radius: 8px;
    border: 1px solid rgba(99, 102, 106, 0.45);
    background: #fff;
    color: #2c3e50;
    font-size: 0.85rem;
}
.chart-container {
    height: 280px;
}
.chart-summary-row {
    margin-top: 10px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 10px;
}
.chart-summary-pill {
    background: rgba(166, 25, 46, 0.06);
    border: 1px solid rgba(166, 25, 46, 0.18);
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 0.85rem;
    color: #2c3e50;
}
@media (max-width: 768px) {
    .view-progress-topbar {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    .view-progress-topbar-side {
        align-items: flex-start;
    }
    .view-progress-topbar-logo {
        width: 125px;
        height: 42px;
    }
    .chart-header-row {
        align-items: flex-start;
        flex-direction: column;
    }
    .chart-controls {
        width: 100%;
        align-items: flex-start;
    }
    .chart-filter-top {
        width: 100%;
        justify-content: flex-start;
    }
    .chart-subject-list {
        max-width: 100%;
        justify-content: flex-start;
    }
    .chart-subject-list-label {
        width: 100%;
    }
    .chart-filter-select {
        min-width: 0;
        width: 100%;
    }
    .timeline-card-grid .stat-card {
        min-height: 0;
    }
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
<a href="view_timetable.php"><i class="fas fa-calendar-alt"></i> <span>Timetable</span></a>
<a href="view_progress.php" class="active"><i class="fas fa-book"></i> <span>Syllabus Progress</span></a>
<a href="edit_profile.php"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
<a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
</div>
<div class="main-content">
<div class="view-progress-topbar">
<div class="view-progress-topbar-info">
<h2>Syllabus Progress</h2>
<p><?php echo htmlspecialchars($termHeadline); ?></p>
<p class="timeline-note"><?php echo htmlspecialchars($termDateLine); ?></p>
<p class="timeline-note">Viewing current semester timeline</p>
</div>
<div class="view-progress-topbar-side">
<?php if ($lastProgressUpdate): ?>
<span class="tag">Last update: <?php echo date('d M Y', $lastProgressUpdate); ?></span>
<?php endif; ?>
<span class="view-progress-topbar-logo" aria-hidden="true"></span>
</div>
</div>
<div class="container">
<div class="timeline-card-grid">
<div class="card stat-card">
<span class="stat-label">Timeline progress</span>
<div class="timeline-stat-value"><?php echo $timelineProgressPercent !== null ? number_format($timelineProgressPercent, 1) . '%' : 'N/A'; ?></div>
<div class="timeline-stat-sub"><?php echo htmlspecialchars($currentWeekLabel); ?></div>
</div>
<div class="card stat-card success">
<span class="stat-label">Planned till now</span>
<div class="timeline-stat-value"><?php echo number_format($totalPlannedTillNow, 1); ?> hrs</div>
<div class="timeline-stat-sub">As per active semester timeline</div>
</div>
<div class="card stat-card success">
<span class="stat-label">Completed till now</span>
<div class="timeline-stat-value"><?php echo number_format($totalCompletedTillNow, 1); ?> hrs</div>
<div class="timeline-stat-sub">Theory + Practical + Tutorial</div>
</div>
<div class="card stat-card<?php echo $totalPendingTillNow > 0 ? '' : ' success'; ?>">
<span class="stat-label">Pending till now</span>
<div class="timeline-stat-value"><?php echo number_format($totalPendingTillNow, 1); ?> hrs</div>
<div class="timeline-stat-sub"><?php echo $totalPendingTillNow > 0 ? 'Hours expected by now but not completed.' : 'No pending hours for current timeline.'; ?></div>
</div>
</div>

<div class="card">
<div class="chart-header-row">
<h3 class="section-title" style="margin-bottom:0;">Theory, Practical, Tutorial: Till-Now Progress</h3>
<div class="chart-controls">
<div class="chart-filter-top">
<label for="timelineSubjectFilter" class="chart-filter-label">Subject:</label>
<select id="timelineSubjectFilter" class="chart-filter-select" aria-label="Filter timeline chart by subject">
<option value="__all__">All subjects</option>
<?php foreach ($subjectTimelineChartData as $subjectName => $subjectSeries): ?>
<option value="<?php echo htmlspecialchars($subjectName); ?>"><?php echo htmlspecialchars($subjectName); ?></option>
<?php endforeach; ?>
</select>
<span class="tag">Current timeline only</span>
</div>
<?php if (!empty($subjectTimelineChartData)): ?>
<div class="chart-subject-list" aria-label="Subjects in current semester">
<span class="chart-subject-list-label">Subjects in this semester:</span>
<?php foreach ($subjectTimelineChartData as $subjectName => $subjectSeries): ?>
<button type="button" class="chart-subject-chip" data-subject-chip="<?php echo htmlspecialchars($subjectName); ?>"><?php echo htmlspecialchars($subjectName); ?></button>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>
<p class="text-muted">Stacked view of completed vs pending hours as of today based on active semester dates.</p>
<div class="chart-container">
<canvas id="timelineProgressChart"></canvas>
</div>
<div class="chart-summary-row">
<div class="chart-summary-pill"><strong>Theory:</strong> <span id="summaryTheoryDone"><?php echo number_format($theoryCompletedTillNow, 1); ?></span> / <span id="summaryTheoryPlan"><?php echo number_format($theoryPlannedTillNow, 1); ?></span> hrs</div>
<div class="chart-summary-pill"><strong>Practical:</strong> <span id="summaryPracticalDone"><?php echo number_format($practicalCompletedTillNow, 1); ?></span> / <span id="summaryPracticalPlan"><?php echo number_format($practicalPlannedTillNow, 1); ?></span> hrs</div>
<div class="chart-summary-pill"><strong>Tutorial:</strong> <span id="summaryTutorialDone"><?php echo number_format($tutorialCompletedTillNow, 1); ?></span> / <span id="summaryTutorialPlan"><?php echo number_format($tutorialPlannedTillNow, 1); ?></span> hrs</div>
</div>
</div>

<div class="card" style="margin-top:14px;">
<h3 class="section-title">Class snapshot</h3>
<div class="info-list">
<div class="info-row"><dt>Class</dt><dd><?php echo htmlspecialchars($className); ?></dd></div>
<div class="info-row"><dt>Section</dt><dd><?php echo htmlspecialchars($sectionName); ?></dd></div>
<div class="info-row"><dt>Semester</dt><dd><?php echo htmlspecialchars($semesterLabel); ?></dd></div>
<div class="info-row"><dt>School</dt><dd><?php echo htmlspecialchars($schoolLabel); ?></dd></div>
</div>
</div>

<?php if (!empty($progressRecords)): ?>
<div class="card" style="margin-top:14px;">
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
<p class="text-muted">Faculty: <?php echo htmlspecialchars($record['teacher_name_display'] ?? 'NOT ASSIGNED'); ?></p>
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

<div class="card" style="margin-top:14px;">
<h3 class="section-title">Detailed breakdown</h3>
<div class="table-responsive">
<table>
<thead>
<tr>
<th class="text-left">Subject</th>
<th class="text-left">Faculty</th>
<th class="text-left">Topic</th>
<th class="text-left">Timeline</th>
<th>Theory (Done/Plan)</th>
<th>Practical (Done/Plan)</th>
<th>Tutorial (Done/Plan)</th>
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
<td class="text-left"><?php echo htmlspecialchars($record['teacher_name_display'] ?? 'NOT ASSIGNED'); ?></td>
<td class="text-left"><?php echo htmlspecialchars($record['topic'] ?? 'N/A'); ?></td>
<td class="text-left"><?php echo htmlspecialchars($record['timeline'] ?? 'N/A'); ?></td>
<td><?php echo number_format((float)($record['theory_actual'] ?? 0), 1); ?> / <?php echo number_format((float)($record['theory_plan'] ?? 0), 1); ?></td>
<td><?php echo number_format((float)($record['practical_actual'] ?? 0), 1); ?> / <?php echo number_format((float)($record['practical_plan'] ?? 0), 1); ?></td>
<td><?php echo number_format((float)($record['tutorial_actual'] ?? 0), 1); ?> / <?php echo number_format((float)($record['tutorial_plan'] ?? 0), 1); ?></td>
<td><?php echo number_format((float)($record['planned_hours'] ?? 0), 1); ?></td>
<td><?php echo number_format((float)($record['actual_hours'] ?? 0), 1); ?></td>
<td><?php echo round((float)($record['completion_percentage'] ?? 0), 1); ?>%</td>
<td><?php echo $record['updated_at'] ? date('d M Y', strtotime($record['updated_at'])) : 'N/A'; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<p class="table-note">Only updates from the active semester timeline are shown here.</p>
</div>
<?php else: ?>
<div class="card" style="margin-top:14px;">
<div class="empty-state">Your faculty have not submitted syllabus progress updates for this class in the current timeline yet.</div>
</div>
<?php endif; ?>
</div>
</div>
</div>
<script>
(function () {
    const canvas = document.getElementById('timelineProgressChart');
    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    const timelineData = <?php echo json_encode($timelineChartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const subjectFilter = document.getElementById('timelineSubjectFilter');
    const summaryTheoryDone = document.getElementById('summaryTheoryDone');
    const summaryTheoryPlan = document.getElementById('summaryTheoryPlan');
    const summaryPracticalDone = document.getElementById('summaryPracticalDone');
    const summaryPracticalPlan = document.getElementById('summaryPracticalPlan');
    const summaryTutorialDone = document.getElementById('summaryTutorialDone');
    const summaryTutorialPlan = document.getElementById('summaryTutorialPlan');
    const subjectChips = Array.prototype.slice.call(document.querySelectorAll('[data-subject-chip]'));

    const getSeriesForSubject = function (subjectKey) {
        if (subjectKey && subjectKey !== '__all__' && timelineData.subjects && timelineData.subjects[subjectKey]) {
            return timelineData.subjects[subjectKey];
        }
        return timelineData.overall;
    };

    const formatHours = function (value) {
        const normalized = Number(value);
        if (!Number.isFinite(normalized)) {
            return '0.0';
        }
        return normalized.toFixed(1);
    };

    const syncSubjectChipState = function (subjectKey) {
        if (!subjectChips.length) {
            return;
        }
        subjectChips.forEach(function (chip) {
            const chipSubject = chip.getAttribute('data-subject-chip') || '';
            chip.classList.toggle('active', subjectKey !== '__all__' && chipSubject === subjectKey);
        });
    };

    let activeSubjectKey = subjectFilter ? subjectFilter.value : '__all__';
    let activeSeries = getSeriesForSubject(activeSubjectKey);

    const timelineChart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: timelineData.labels,
            datasets: [
                {
                    label: 'Completed till now',
                    data: activeSeries.completed,
                    backgroundColor: '#2e8b57',
                    borderRadius: 4,
                    barThickness: 28
                },
                {
                    label: 'Pending till now',
                    data: activeSeries.pending,
                    backgroundColor: '#A6192E',
                    borderRadius: 4,
                    barThickness: 28
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: {
                    stacked: true,
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Hours (till now)'
                    }
                },
                y: {
                    stacked: true
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        afterBody: function (items) {
                            if (!items || !items.length) {
                                return '';
                            }
                            const idx = items[0].dataIndex;
                            const planned = activeSeries.plannedTillNow[idx] ?? 0;
                            const actual = activeSeries.actual[idx] ?? 0;
                            return [
                                'Planned till now: ' + planned + ' hrs',
                                'Actual logged: ' + actual + ' hrs'
                            ];
                        }
                    }
                }
            }
        }
    });

    const applySeries = function (series, subjectKey) {
        activeSeries = series;
        timelineChart.data.datasets[0].data = series.completed;
        timelineChart.data.datasets[1].data = series.pending;
        timelineChart.update();
        syncSubjectChipState(subjectKey || '__all__');

        if (summaryTheoryDone) {
            summaryTheoryDone.textContent = formatHours(series.completed[0] ?? 0);
        }
        if (summaryTheoryPlan) {
            summaryTheoryPlan.textContent = formatHours(series.plannedTillNow[0] ?? 0);
        }
        if (summaryPracticalDone) {
            summaryPracticalDone.textContent = formatHours(series.completed[1] ?? 0);
        }
        if (summaryPracticalPlan) {
            summaryPracticalPlan.textContent = formatHours(series.plannedTillNow[1] ?? 0);
        }
        if (summaryTutorialDone) {
            summaryTutorialDone.textContent = formatHours(series.completed[2] ?? 0);
        }
        if (summaryTutorialPlan) {
            summaryTutorialPlan.textContent = formatHours(series.plannedTillNow[2] ?? 0);
        }
    };

    if (subjectFilter) {
        subjectFilter.addEventListener('change', function () {
            activeSubjectKey = this.value || '__all__';
            applySeries(getSeriesForSubject(activeSubjectKey), activeSubjectKey);
        });
    }

    if (subjectFilter && subjectChips.length) {
        subjectChips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                const chipSubject = this.getAttribute('data-subject-chip') || '__all__';
                subjectFilter.value = chipSubject;
                activeSubjectKey = chipSubject;
                applySeries(getSeriesForSubject(activeSubjectKey), activeSubjectKey);
            });
        });
    }

    applySeries(activeSeries, activeSubjectKey);
})();
</script>
</body>
</html>
