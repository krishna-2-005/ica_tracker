<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
	header('Location: login.php');
	exit;
}

$userId = (int)$_SESSION['user_id'];
$studentName = $_SESSION['name'] ?? '';
$studentSapId = $_SESSION['unique_id'] ?? null;

if ($studentSapId === null) {
	$sapLookup = mysqli_prepare($conn, "SELECT username FROM users WHERE id = ? LIMIT 1");
	if ($sapLookup) {
		mysqli_stmt_bind_param($sapLookup, 'i', $userId);
		mysqli_stmt_execute($sapLookup);
		$sapResult = mysqli_stmt_get_result($sapLookup);
		if ($row = mysqli_fetch_assoc($sapResult)) {
			$studentSapId = $row['username'];
		}
		mysqli_stmt_close($sapLookup);
	}
}

if (!$studentSapId) {
	echo 'Unable to determine your SAP ID. Please contact the administrator.';
	mysqli_close($conn);
	exit;
}

require_once __DIR__ . '/includes/student_context.php';

$studentProfiles = fetchStudentProfilesBySap($conn, $studentSapId);
if (empty($studentProfiles)) {
	echo 'Student record could not be found. Please contact the administrator.';
	mysqli_close($conn);
	exit;
}

$defaultProfile = $studentProfiles[0];
if (!empty($defaultProfile['name'])) {
	$studentName = $defaultProfile['name'];
}
$rollNumber = $defaultProfile['roll_number'] ?? '—';
$schoolLabel = $defaultProfile['school'] ?? '';
$semesterLabel = $defaultProfile['semester'] ?? '';
$sectionLabel = $defaultProfile['section_name'] ?? 'N/A';

$studentInfo = null;
$studentId = null;
$classId = null;
$sectionId = null;
$timelineMismatch = false;

$performanceThreshold = 40;
$settingsStmt = mysqli_prepare($conn, "SELECT setting_value FROM settings WHERE setting_key = 'performance_threshold' LIMIT 1");
if ($settingsStmt) {
	mysqli_stmt_execute($settingsStmt);
	$settingsResult = mysqli_stmt_get_result($settingsStmt);
	if ($settingsRow = mysqli_fetch_assoc($settingsResult)) {
		$thresholdValue = isset($settingsRow['setting_value']) ? (float)$settingsRow['setting_value'] : null;
		if ($thresholdValue !== null && $thresholdValue > 0) {
			$performanceThreshold = $thresholdValue;
		}
	}
	mysqli_stmt_close($settingsStmt);
}

require_once __DIR__ . '/includes/academic_context.php';
require_once __DIR__ . '/includes/term_switcher_ui.php';

$academicContext = resolveAcademicContext($conn, [
	'school_name' => $schoolLabel,
	'default_semester' => $semesterLabel,
]);
$activeTerm = $academicContext['active'] ?? null;
$termDateFilter = $academicContext['date_filter'] ?? null;
$termStartDate = $termDateFilter['start'] ?? null;
$termEndDate = $termDateFilter['end'] ?? null;
$termStartBound = $termStartDate ? $termStartDate . ' 00:00:00' : null;
$termEndBound = $termEndDate ? $termEndDate . ' 23:59:59' : null;
$currentTimestamp = date('Y-m-d H:i:s');
if ($termEndBound !== null && $termEndBound < $currentTimestamp) {
	$termEndBound = $currentTimestamp;
}

$columnInspectorCache = [];
$columnExists = static function (mysqli $connection, $table, $column) use (&$columnInspectorCache) {
	$tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$table);
	$columnSafe = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$column);
	if ($tableSafe === '' || $columnSafe === '') {
		return false;
	}
	$tableKey = strtolower($tableSafe);
	$columnKey = strtolower($columnSafe);
	if (isset($columnInspectorCache[$tableKey][$columnKey])) {
		return $columnInspectorCache[$tableKey][$columnKey];
	}
	$sql = "SHOW COLUMNS FROM `{$tableSafe}` LIKE '{$columnSafe}'";
	$result = mysqli_query($connection, $sql);
	$exists = ($result && mysqli_fetch_assoc($result)) ? true : false;
	if ($result) {
		mysqli_free_result($result);
	}
	$columnInspectorCache[$tableKey][$columnKey] = $exists;
	return $exists;
};

$activeTermId = $activeTerm && isset($activeTerm['id']) ? (int)$activeTerm['id'] : null;
$studentInfo = selectStudentProfileForTerm($studentProfiles, $activeTermId);
if ($studentInfo) {
	$studentId = isset($studentInfo['id']) ? (int)$studentInfo['id'] : null;
	if ($studentId === 0) {
		$studentId = null;
	}
	$classId = isset($studentInfo['class_id']) ? (int)$studentInfo['class_id'] : null;
	if ($classId === 0) {
		$classId = null;
	}
	$sectionId = isset($studentInfo['section_id']) ? (int)$studentInfo['section_id'] : null;
	if ($sectionId === 0) {
		$sectionId = null;
	}
	if (!empty($studentInfo['name'])) {
		$studentName = $studentInfo['name'];
	}
	if (!empty($studentInfo['roll_number'])) {
		$rollNumber = $studentInfo['roll_number'];
	}
	if (!empty($studentInfo['school'])) {
		$schoolLabel = $studentInfo['school'];
	}
	$classLabelCandidate = format_class_label(
		$studentInfo['class_name'] ?? '',
		$studentInfo['section_name'] ?? '',
		$studentInfo['semester'] ?? '',
		$studentInfo['school'] ?? ''
	);
	$classLabel = $classLabelCandidate !== '' ? $classLabelCandidate : 'N/A';
	$sectionLabel = $studentInfo['section_name'] !== '' ? format_subject_display($studentInfo['section_name']) : 'N/A';
	$semesterLabel = $studentInfo['semester'] ?? $semesterLabel;
} else {
	if ($activeTermId !== null) {
		$timelineMismatch = true;
	}
	$classLabel = 'No class assigned for this semester';
	$sectionLabel = 'N/A';
	$studentId = null;
	$classId = null;
	$sectionId = null;
}

$academicSummary = null;
if ($activeTerm && $termStartDate && $termEndDate) {
	$startObj = new DateTime($termStartDate);
	$endObj = new DateTime($termEndDate);
	$todayObj = new DateTime('today');
	$clampedToday = $todayObj;
	if ($clampedToday < $startObj) {
		$clampedToday = $startObj;
	}
	if ($clampedToday > $endObj) {
		$clampedToday = $endObj;
	}
	$totalDays = max(1, $startObj->diff($endObj)->days);
	$elapsedDays = $startObj->diff($clampedToday)->days;
	$daysRemaining = $clampedToday <= $endObj ? $clampedToday->diff($endObj)->days : 0;
	$termTitleParts = [];
	if (!empty($activeTerm['semester_number'])) {
		$termTitleParts[] = 'Semester ' . $activeTerm['semester_number'];
	}
	if (!empty($activeTerm['semester_term'])) {
		$termTitleParts[] = ucfirst((string)$activeTerm['semester_term']) . ' Term';
	}
	if (!empty($activeTerm['academic_year'])) {
		$termTitleParts[] = 'AY ' . $activeTerm['academic_year'];
	}
	if (!$termTitleParts && $semesterLabel !== '') {
		$termTitleParts[] = 'Semester ' . $semesterLabel;
	}
	$academicSummary = [
		'term' => implode(' • ', $termTitleParts),
		'start' => $termStartDate,
		'end' => $termEndDate,
		'days_remaining' => $daysRemaining,
		'progress' => min(100, max(0, round(($elapsedDays / $totalDays) * 100))),
		'is_override' => isset($academicContext['override_id']) && $academicContext['override_id'] !== null,
	];
}

$subjects = [];
$subjectIndex = [];
$subjectTeacherMap = [];
if ($classId) {
	$subjectsQuery = "SELECT DISTINCT s.id, s.subject_name
			  FROM subjects s
			  INNER JOIN teacher_subject_assignments tsa ON s.id = tsa.subject_id
			  LEFT JOIN subject_details sd ON sd.subject_id = s.id
			  LEFT JOIN student_elective_choices sec ON sec.subject_id = s.id AND sec.student_id = ? AND sec.class_id = ?
			  WHERE tsa.class_id = ?";
	$sectionFilter = '';
	$bindTypes = 'iii';
	$bindValues = [$studentId, $classId, $classId];
	if ($sectionId !== null) {
		$sectionFilter = " AND (tsa.section_id IS NULL OR tsa.section_id = ?)";
		$bindTypes .= 'i';
		$bindValues[] = $sectionId;
	}
	$subjectsQuery .= $sectionFilter;
	$subjectsQuery .= " AND (COALESCE(sd.subject_type, 'regular') <> 'elective' OR sec.id IS NOT NULL)
			  ORDER BY s.subject_name";
	$stmtSubjects = mysqli_prepare($conn, $subjectsQuery);
	if ($stmtSubjects) {
		$bindParams = [];
		foreach ($bindValues as $index => $value) {
			$bindParams[$index] = &$bindValues[$index];
		}
		array_unshift($bindParams, $stmtSubjects, $bindTypes);
		call_user_func_array('mysqli_stmt_bind_param', $bindParams);
		mysqli_stmt_execute($stmtSubjects);
		$subjectsResult = mysqli_stmt_get_result($stmtSubjects);
		while ($row = mysqli_fetch_assoc($subjectsResult)) {
			$subjectIdValue = isset($row['id']) ? (int)$row['id'] : 0;
			if ($subjectIdValue > 0) {
				$subjectNameRaw = isset($row['subject_name']) ? trim((string)$row['subject_name']) : '';
				$subjectIndex[$subjectIdValue] = [
					'id' => $subjectIdValue,
					'subject_name' => $subjectNameRaw,
					'subject_name_display' => format_subject_display($subjectNameRaw)
				];
			}
		}
		if ($subjectsResult) {
			mysqli_free_result($subjectsResult);
		}
		mysqli_stmt_close($stmtSubjects);
		$stmtSubjects = null;
	}

	$teacherSql = "SELECT tsa.subject_id, u.name AS teacher_name
			FROM teacher_subject_assignments tsa
			INNER JOIN users u ON tsa.teacher_id = u.id
			WHERE tsa.class_id = ?";
	if ($sectionId !== null) {
		$teacherSql .= " AND (tsa.section_id = ? OR tsa.section_id IS NULL)";
	}
	$teacherSql .= " ORDER BY u.name";
	$stmtTeacherMap = mysqli_prepare($conn, $teacherSql);
	if ($stmtTeacherMap) {
		if ($sectionId !== null) {
			mysqli_stmt_bind_param($stmtTeacherMap, 'ii', $classId, $sectionId);
		} else {
			mysqli_stmt_bind_param($stmtTeacherMap, 'i', $classId);
		}
		mysqli_stmt_execute($stmtTeacherMap);
		$teacherMapResult = mysqli_stmt_get_result($stmtTeacherMap);
		while ($row = mysqli_fetch_assoc($teacherMapResult)) {
			$subjectId = (int)($row['subject_id'] ?? 0);
			$name = trim((string)($row['teacher_name'] ?? ''));
			if ($subjectId > 0 && $name !== '') {
				$subjectTeacherMap[$subjectId][] = $name;
			}
		}
		if ($teacherMapResult) {
			mysqli_free_result($teacherMapResult);
		}
		mysqli_stmt_close($stmtTeacherMap);
	}
}

$marksSubjectSql = "SELECT DISTINCT s.id, s.subject_name
				  FROM ica_student_marks ism
				  INNER JOIN ica_components ic ON ic.id = ism.component_id
				  INNER JOIN subjects s ON s.id = ic.subject_id
				  WHERE ism.student_id = ?";
if ($classId) {
	$marksSubjectSql .= " AND (ic.class_id IS NULL OR ic.class_id = 0 OR ic.class_id = ?)";
}
$marksSubjectSql .= " ORDER BY s.subject_name";
$stmtMarksSubjects = mysqli_prepare($conn, $marksSubjectSql);
if ($stmtMarksSubjects) {
	if ($classId) {
		mysqli_stmt_bind_param($stmtMarksSubjects, 'ii', $studentId, $classId);
	} else {
		mysqli_stmt_bind_param($stmtMarksSubjects, 'i', $studentId);
	}
	mysqli_stmt_execute($stmtMarksSubjects);
	$marksSubjectsResult = mysqli_stmt_get_result($stmtMarksSubjects);
	while ($marksSubjectsResult && ($row = mysqli_fetch_assoc($marksSubjectsResult))) {
		$subjectIdValue = isset($row['id']) ? (int)$row['id'] : 0;
		if ($subjectIdValue > 0) {
			$subjectNameRaw = isset($row['subject_name']) ? trim((string)$row['subject_name']) : '';
			$subjectIndex[$subjectIdValue] = [
				'id' => $subjectIdValue,
				'subject_name' => $subjectNameRaw,
				'subject_name_display' => format_subject_display($subjectNameRaw)
			];
		}
	}
	if ($marksSubjectsResult) {
		mysqli_free_result($marksSubjectsResult);
	}
	mysqli_stmt_close($stmtMarksSubjects);
}

$subjects = array_values($subjectIndex);

$subjectPerformance = [];
$marksDateClause = ($termStartBound && $termEndBound);

$subjectComparisons = [];

$performanceStats = [
	'overall' => 0,
	'graded' => 0,
	'pending' => 0,
	'last_update' => null
];

$assignments = [];
$assignmentDateClause = ($termStartDate && $termEndDate) ? " AND a.deadline BETWEEN ? AND ?" : '';
$hasAssignmentSubjectId = $columnExists($conn, 'assignments', 'subject_id');
$hasStatusColumn = $columnExists($conn, 'student_assignments', 'assignment_status');
$hasSubmissionState = $columnExists($conn, 'student_assignments', 'submission_state');
$hasTeacherFeedback = $columnExists($conn, 'student_assignments', 'teacher_feedback');
$hasGradedMarks = $columnExists($conn, 'student_assignments', 'graded_marks');
$hasGradedAt = $columnExists($conn, 'student_assignments', 'graded_at');
$hasLastSubmissionAt = $columnExists($conn, 'student_assignments', 'last_submission_at');

$assignmentSelect = [
	'a.id',
	'a.title',
	'a.deadline',
	'a.description'
];

if ($hasAssignmentSubjectId) {
	$assignmentSelect[] = 'a.subject_id';
	$assignmentSelect[] = 'COALESCE(sub.subject_name, a.subject) AS subject_name';
} else {
	$assignmentSelect[] = 'NULL AS subject_id';
	$assignmentSelect[] = 'a.subject AS subject_name';
}

if ($hasStatusColumn) {
	$assignmentSelect[] = 'sa.assignment_status';
} else {
	$assignmentSelect[] = 'sa.submission_status AS assignment_status';
}

if ($hasSubmissionState) {
	$assignmentSelect[] = 'sa.submission_state';
} else {
	$assignmentSelect[] = "NULL AS submission_state";
}

if ($hasTeacherFeedback) {
	$assignmentSelect[] = 'sa.teacher_feedback';
} else {
	$assignmentSelect[] = "NULL AS teacher_feedback";
}

if ($hasGradedMarks) {
	$assignmentSelect[] = 'sa.graded_marks';
} else {
	$assignmentSelect[] = 'sa.marks_obtained AS graded_marks';
}

if ($hasGradedAt) {
	$assignmentSelect[] = 'sa.graded_at';
} else {
	$assignmentSelect[] = "NULL AS graded_at";
}

if ($hasLastSubmissionAt) {
	$assignmentSelect[] = 'sa.last_submission_at';
} else {
	$assignmentSelect[] = 'sa.submission_date AS last_submission_at';
}

$assignmentsQuery = "SELECT " . implode(",\n\t\t\t\t", $assignmentSelect) . "
  FROM student_assignments sa
  INNER JOIN assignments a ON a.id = sa.assignment_id";

if ($hasAssignmentSubjectId) {
	$assignmentsQuery .= "
  LEFT JOIN subjects sub ON a.subject_id = sub.id";
}

$assignmentsQuery .= "
  WHERE sa.student_id = ?" . $assignmentDateClause . "
  ORDER BY CASE
  	WHEN LOWER(assignment_status) = 'pending' THEN 0
  	WHEN LOWER(assignment_status) = 'submitted' THEN 1
  	WHEN LOWER(assignment_status) = 'late submitted' THEN 2
  	WHEN LOWER(assignment_status) = 'late_submitted' THEN 2
  	WHEN LOWER(assignment_status) = 'rejected' THEN 3
  	WHEN LOWER(assignment_status) = 'completed' THEN 4
  	WHEN LOWER(assignment_status) = 'graded' THEN 4
  	ELSE 5
  END, a.deadline ASC";
$stmtAssignments = mysqli_prepare($conn, $assignmentsQuery);
if ($stmtAssignments) {
	if ($assignmentDateClause !== '' && $termStartDate && $termEndDate) {
		mysqli_stmt_bind_param($stmtAssignments, 'iss', $studentId, $termStartDate, $termEndDate);
	} else {
		mysqli_stmt_bind_param($stmtAssignments, 'i', $studentId);
	}
	mysqli_stmt_execute($stmtAssignments);
	$assignmentsResult = mysqli_stmt_get_result($stmtAssignments);
	while ($row = mysqli_fetch_assoc($assignmentsResult)) {
		$subjectRaw = isset($row['subject_name']) ? trim((string)$row['subject_name']) : '';
		$row['subject_name'] = $subjectRaw;
		$row['subject_name_display'] = $subjectRaw !== '' ? format_subject_display($subjectRaw) : '—';
		$assignments[] = $row;
	}
	mysqli_stmt_close($stmtAssignments);
}

$normalizeAssignmentStatus = static function ($rawStatus) {
	$status = strtolower(trim((string)$rawStatus));
	if ($status === '') {
		return 'pending';
	}
	return str_replace(' ', '_', $status);
};

$outstandingAssignments = [];
$completedAssignments = [];
foreach ($assignments as $assignment) {
	$rawStatus = (string)($assignment['assignment_status'] ?? '');
	$statusSlug = $normalizeAssignmentStatus($rawStatus);
	if ($statusSlug === 'graded') {
		$statusSlug = 'completed';
	}
	if ($statusSlug === '') {
		$statusSlug = 'pending';
	}
	$assignment['status_label'] = ucwords(str_replace('_', ' ', $statusSlug));
	$assignment['status_slug'] = $statusSlug;

	if ($statusSlug === 'completed') {
		$completedAssignments[] = $assignment;
	} else {
		$outstandingAssignments[] = $assignment;
	}
}

$pendingAssignmentCount = count($outstandingAssignments);
$completedAssignmentCount = count($completedAssignments);

$todoAssignments = [];
if (!empty($outstandingAssignments)) {
	$todoAssignments = $outstandingAssignments;
	usort($todoAssignments, static function (array $a, array $b): int {
		$aDeadline = isset($a['deadline']) && $a['deadline'] ? strtotime((string)$a['deadline']) : PHP_INT_MAX;
		$bDeadline = isset($b['deadline']) && $b['deadline'] ? strtotime((string)$b['deadline']) : PHP_INT_MAX;
		if ($aDeadline === $bDeadline) {
			return strcasecmp((string)($a['title'] ?? ''), (string)($b['title'] ?? ''));
		}
		return $aDeadline <=> $bDeadline;
	});
	$todoAssignments = array_slice($todoAssignments, 0, 5);
	$todayFloor = new DateTime('today');
	foreach ($todoAssignments as &$todoItem) {
		$todoItem['todo_deadline_label'] = null;
		$todoItem['todo_deadline_hint'] = null;
		$todoItem['todo_is_overdue'] = false;
		$rawDeadline = $todoItem['deadline'] ?? null;
		if (!empty($rawDeadline)) {
			$deadlineTs = strtotime((string)$rawDeadline);
			if ($deadlineTs !== false) {
				$todoItem['todo_deadline_label'] = date('d M Y', $deadlineTs);
				$deadlineDate = new DateTime(date('Y-m-d', $deadlineTs));
				$daysDiff = (int)$todayFloor->diff($deadlineDate)->format('%r%a');
				if ($daysDiff > 0) {
					$todoItem['todo_deadline_hint'] = 'Due in ' . $daysDiff . ' day' . ($daysDiff !== 1 ? 's' : '');
				} elseif ($daysDiff === 0) {
					$todoItem['todo_deadline_hint'] = 'Due today';
				} else {
					$todoItem['todo_deadline_hint'] = 'Overdue by ' . abs($daysDiff) . ' day' . (abs($daysDiff) !== 1 ? 's' : '');
					$todoItem['todo_is_overdue'] = true;
				}
			}
		}
	}
	unset($todoItem);
}

$icaMarks = [];
$icaMarksQuery = "SELECT s.subject_name, ic.component_name, ic.id AS component_id, ism.marks, ism.updated_at, ism.instance_number
			FROM ica_student_marks ism
			INNER JOIN ica_components ic ON ism.component_id = ic.id
			INNER JOIN subjects s ON ic.subject_id = s.id
				  INNER JOIN students st ON st.id = ism.student_id
			WHERE ism.student_id = ?
						AND (ic.class_id IS NULL OR ic.class_id = 0 OR ic.class_id = st.class_id)";
if ($marksDateClause && $termStartBound && $termEndBound) {
	$icaMarksQuery .= " AND ((ism.updated_at BETWEEN ? AND ?) OR ism.updated_at IS NULL)";
}
$icaMarksQuery .= " ORDER BY s.subject_name, ic.component_name, ism.instance_number";
$stmtIcaMarks = mysqli_prepare($conn, $icaMarksQuery);
if ($stmtIcaMarks) {
	if ($marksDateClause && $termStartBound && $termEndBound) {
		mysqli_stmt_bind_param($stmtIcaMarks, 'iss', $studentId, $termStartBound, $termEndBound);
	} else {
		mysqli_stmt_bind_param($stmtIcaMarks, 'i', $studentId);
	}
	mysqli_stmt_execute($stmtIcaMarks);
	$icaMarksResult = mysqli_stmt_get_result($stmtIcaMarks);
	while ($row = mysqli_fetch_assoc($icaMarksResult)) {
		$subjectRaw = isset($row['subject_name']) ? trim((string)$row['subject_name']) : '';
		$row['subject_name'] = $subjectRaw;
		$row['subject_name_display'] = format_subject_display($subjectRaw);
		$icaMarks[] = $row;
	}
	mysqli_stmt_close($stmtIcaMarks);
}

if (empty($icaMarks)) {
	$fallbackMarksSql = "SELECT s.subject_name, ic.component_name, ic.id AS component_id, ism.marks, ism.updated_at, ism.instance_number
						 FROM ica_student_marks ism
						 INNER JOIN ica_components ic ON ism.component_id = ic.id
						 INNER JOIN subjects s ON ic.subject_id = s.id
						 INNER JOIN students st ON st.id = ism.student_id
						 WHERE ism.student_id = ?
							   AND (ic.class_id IS NULL OR ic.class_id = 0 OR ic.class_id = st.class_id)
						 ORDER BY s.subject_name, ic.component_name, ism.instance_number";
	$fallbackStmt = mysqli_prepare($conn, $fallbackMarksSql);
	if ($fallbackStmt) {
		mysqli_stmt_bind_param($fallbackStmt, 'i', $studentId);
		mysqli_stmt_execute($fallbackStmt);
		$fallbackResult = mysqli_stmt_get_result($fallbackStmt);
		while ($fallbackResult && ($row = mysqli_fetch_assoc($fallbackResult))) {
			$subjectRaw = isset($row['subject_name']) ? trim((string)$row['subject_name']) : '';
			$row['subject_name'] = $subjectRaw;
			$row['subject_name_display'] = format_subject_display($subjectRaw);
			$icaMarks[] = $row;
		}
		if ($fallbackResult) {
			mysqli_free_result($fallbackResult);
		}
		mysqli_stmt_close($fallbackStmt);
	}
}

$recentMarks = [];
$recentMarksQuery = "SELECT s.subject_name, ic.component_name, ic.id AS component_id, ism.marks, ism.updated_at, ism.instance_number
			   FROM ica_student_marks ism
			   INNER JOIN ica_components ic ON ism.component_id = ic.id
			   INNER JOIN subjects s ON ic.subject_id = s.id
			   INNER JOIN students st ON st.id = ism.student_id
			   WHERE ism.student_id = ? AND ism.marks IS NOT NULL
				   AND (ic.class_id IS NULL OR ic.class_id = 0 OR ic.class_id = st.class_id)";
if ($marksDateClause && $termStartBound && $termEndBound) {
	$recentMarksQuery .= " AND ((ism.updated_at BETWEEN ? AND ?) OR ism.updated_at IS NULL)";
}
$recentMarksQuery .= " ORDER BY ism.updated_at DESC
					 LIMIT 5";
$stmtRecentMarks = mysqli_prepare($conn, $recentMarksQuery);
if ($stmtRecentMarks) {
	if ($marksDateClause && $termStartBound && $termEndBound) {
		mysqli_stmt_bind_param($stmtRecentMarks, 'iss', $studentId, $termStartBound, $termEndBound);
	} else {
		mysqli_stmt_bind_param($stmtRecentMarks, 'i', $studentId);
	}
	mysqli_stmt_execute($stmtRecentMarks);
	$recentResult = mysqli_stmt_get_result($stmtRecentMarks);
	while ($row = mysqli_fetch_assoc($recentResult)) {
		$subjectRaw = isset($row['subject_name']) ? trim((string)$row['subject_name']) : '';
		$row['subject_name'] = $subjectRaw;
		$row['subject_name_display'] = format_subject_display($subjectRaw);
		$recentMarks[] = $row;
	}
	mysqli_stmt_close($stmtRecentMarks);
}

if (empty($recentMarks)) {
	$fallbackRecentSql = "SELECT s.subject_name, ic.component_name, ic.id AS component_id, ism.marks, ism.updated_at, ism.instance_number
			FROM ica_student_marks ism
			INNER JOIN ica_components ic ON ism.component_id = ic.id
			INNER JOIN subjects s ON ic.subject_id = s.id
			INNER JOIN students st ON st.id = ism.student_id
			WHERE ism.student_id = ? AND ism.marks IS NOT NULL
				AND (ic.class_id IS NULL OR ic.class_id = 0 OR ic.class_id = st.class_id)
			ORDER BY ism.updated_at DESC LIMIT 5";
	$fallbackRecentStmt = mysqli_prepare($conn, $fallbackRecentSql);
	if ($fallbackRecentStmt) {
		mysqli_stmt_bind_param($fallbackRecentStmt, 'i', $studentId);
		mysqli_stmt_execute($fallbackRecentStmt);
		$fallbackRecentResult = mysqli_stmt_get_result($fallbackRecentStmt);
		while ($fallbackRecentResult && ($row = mysqli_fetch_assoc($fallbackRecentResult))) {
			$subjectRaw = isset($row['subject_name']) ? trim((string)$row['subject_name']) : '';
			$row['subject_name'] = $subjectRaw;
			$row['subject_name_display'] = format_subject_display($subjectRaw);
			$recentMarks[] = $row;
		}
		if ($fallbackRecentResult) {
			mysqli_free_result($fallbackRecentResult);
		}
		mysqli_stmt_close($fallbackRecentStmt);
	}
}

$teacherContacts = [];
if ($classId) {
	$teacherQuery = "SELECT DISTINCT u.id, u.name, u.email, s.subject_name
			 FROM teacher_subject_assignments tsa
			 INNER JOIN users u ON tsa.teacher_id = u.id
			 INNER JOIN subjects s ON tsa.subject_id = s.id
			 LEFT JOIN subject_details sd ON sd.subject_id = s.id
			 LEFT JOIN student_elective_choices sec ON sec.subject_id = s.id AND sec.student_id = ? AND sec.class_id = ?
			 WHERE tsa.class_id = ?";
	$teacherTypes = 'iii';
	$teacherValues = [$studentId, $classId, $classId];
	if ($sectionId !== null) {
		$teacherQuery .= " AND (tsa.section_id IS NULL OR tsa.section_id = ?)";
		$teacherTypes .= 'i';
		$teacherValues[] = $sectionId;
	}
	$teacherQuery .= " AND (COALESCE(sd.subject_type, 'regular') <> 'elective' OR sec.id IS NOT NULL)
			 ORDER BY s.subject_name";
	$stmtTeachers = mysqli_prepare($conn, $teacherQuery);
	if ($stmtTeachers) {
		$teacherBind = [];
		foreach ($teacherValues as $idx => $val) {
			$teacherBind[$idx] = &$teacherValues[$idx];
		}
		array_unshift($teacherBind, $stmtTeachers, $teacherTypes);
		call_user_func_array('mysqli_stmt_bind_param', $teacherBind);
		mysqli_stmt_execute($stmtTeachers);
		$teacherResult = mysqli_stmt_get_result($stmtTeachers);
		while ($row = mysqli_fetch_assoc($teacherResult)) {
			$teacherContacts[] = $row;
		}
		mysqli_stmt_close($stmtTeachers);
	}
} else {
	$stmtTeachers = null;
}

$teacherDirectory = [];
foreach ($teacherContacts as $contact) {
	$teacherId = (int)($contact['id'] ?? 0);
	if ($teacherId === 0) {
		continue;
	}
	if (!isset($teacherDirectory[$teacherId])) {
		$nameRaw = isset($contact['name']) ? trim((string)$contact['name']) : '';
		$teacherDirectory[$teacherId] = [
			'name' => $nameRaw,
			'name_display' => $nameRaw !== '' ? format_person_display($nameRaw) : 'FACULTY',
			'email' => $contact['email'] ?? '',
			'subjects' => []
		];
	}
	if (!empty($contact['subject_name'])) {
		$subjectNameRaw = trim((string)$contact['subject_name']);
		$teacherDirectory[$teacherId]['subjects'][] = format_subject_display($subjectNameRaw);
	}
}
foreach ($teacherDirectory as &$teacherMeta) {
	$teacherMeta['subjects'] = array_values(array_unique($teacherMeta['subjects']));
}
unset($teacherMeta);

$subjectIds = [];
foreach ($subjects as $subjectRow) {
	$subjectIdValue = isset($subjectRow['id']) ? (int)$subjectRow['id'] : 0;
	if ($subjectIdValue > 0) {
		$subjectIds[] = $subjectIdValue;
	}
}
$subjectIds = array_values(array_unique($subjectIds));

$componentMeta = [];
$subjectAllocated = [];
if (!empty($subjectIds)) {
	$componentParams = $subjectIds;
	$componentTypes = str_repeat('i', count($subjectIds));
	$classFilterSql = '';
	if ($classId) {
		$classFilterSql = ' AND (ic.class_id IS NULL OR ic.class_id = 0 OR ic.class_id = ?)';
		$componentTypes .= 'i';
		$componentParams[] = $classId;
	} else {
		$classFilterSql = ' AND (ic.class_id IS NULL OR ic.class_id = 0)';
	}
	$placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
	$componentSql = "SELECT ic.id, ic.subject_id, ic.component_name, ic.instances, ic.marks_per_instance, ic.total_marks, ic.scaled_total_marks
		FROM ica_components ic
		WHERE ic.subject_id IN ($placeholders)$classFilterSql";
	$stmtComponents = mysqli_prepare($conn, $componentSql);
	if ($stmtComponents) {
		$componentBindParams = [];
		foreach ($componentParams as $index => $value) {
			$componentBindParams[$index] = &$componentParams[$index];
		}
		array_unshift($componentBindParams, $stmtComponents, $componentTypes);
		call_user_func_array('mysqli_stmt_bind_param', $componentBindParams);
		mysqli_stmt_execute($stmtComponents);
		$resultComponents = mysqli_stmt_get_result($stmtComponents);
		if ($resultComponents) {
			while ($row = mysqli_fetch_assoc($resultComponents)) {
				$componentId = isset($row['id']) ? (int)$row['id'] : 0;
				if ($componentId <= 0) {
					continue;
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
				$scaleRatio = ($rawTotal > 0 && $scaledTotal > 0) ? ($scaledTotal / $rawTotal) : 1.0;
				$perInstanceCap = null;
				if ($marksPerInstance > 0) {
					$perInstanceCap = $marksPerInstance;
				} elseif ($rawTotal > 0) {
					$perInstanceCap = $rawTotal / $instances;
				} elseif ($scaledTotal > 0) {
					$perInstanceCap = $scaledTotal / $instances;
				}
				$subjectIdMeta = isset($row['subject_id']) ? (int)$row['subject_id'] : 0;
				$componentMeta[$componentId] = [
					'subject_id' => $subjectIdMeta,
					'name' => $row['component_name'] ?? ('Component ' . $componentId),
					'instances' => $instances,
					'marks_per_instance' => $marksPerInstance,
					'raw_total' => $rawTotal,
					'scaled_total' => $scaledTotal,
					'scale_ratio' => $scaleRatio,
					'per_instance_cap' => $perInstanceCap
				];
				if ($subjectIdMeta > 0) {
					$subjectAllocated[$subjectIdMeta] = ($subjectAllocated[$subjectIdMeta] ?? 0.0) + $scaledTotal;
				}
			}
			mysqli_free_result($resultComponents);
		}
		mysqli_stmt_close($stmtComponents);
	}

	$fallbackComponentTypes = 'i' . str_repeat('i', count($subjectIds));
	$fallbackComponentParams = array_merge([$studentId], $subjectIds);
	$fallbackPlaceholders = implode(',', array_fill(0, count($subjectIds), '?'));
	$fallbackComponentSql = "SELECT ic.id, ic.subject_id, ic.component_name, ic.instances, ic.marks_per_instance, ic.total_marks, ic.scaled_total_marks
		FROM ica_components ic
		INNER JOIN ica_student_marks ism ON ism.component_id = ic.id
		INNER JOIN students st ON st.id = ism.student_id
		WHERE ism.student_id = ?
			AND ic.subject_id IN ($fallbackPlaceholders)
			AND (ic.class_id IS NULL OR ic.class_id = 0 OR ic.class_id = st.class_id)";
	$fallbackStmtComponents = mysqli_prepare($conn, $fallbackComponentSql);
	if ($fallbackStmtComponents) {
		$fallbackBindParams = [];
		foreach ($fallbackComponentParams as $index => $value) {
			$fallbackBindParams[$index] = &$fallbackComponentParams[$index];
		}
		array_unshift($fallbackBindParams, $fallbackStmtComponents, $fallbackComponentTypes);
		call_user_func_array('mysqli_stmt_bind_param', $fallbackBindParams);
		mysqli_stmt_execute($fallbackStmtComponents);
		$fallbackComponentResult = mysqli_stmt_get_result($fallbackStmtComponents);
		if ($fallbackComponentResult) {
			while ($row = mysqli_fetch_assoc($fallbackComponentResult)) {
				$componentId = isset($row['id']) ? (int)$row['id'] : 0;
				if ($componentId <= 0 || isset($componentMeta[$componentId])) {
					continue;
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
				$scaleRatio = ($rawTotal > 0 && $scaledTotal > 0) ? ($scaledTotal / $rawTotal) : 1.0;
				$perInstanceCap = null;
				if ($marksPerInstance > 0) {
					$perInstanceCap = $marksPerInstance;
				} elseif ($rawTotal > 0) {
					$perInstanceCap = $rawTotal / $instances;
				} elseif ($scaledTotal > 0) {
					$perInstanceCap = $scaledTotal / $instances;
				}
				$subjectIdMeta = isset($row['subject_id']) ? (int)$row['subject_id'] : 0;
				$componentMeta[$componentId] = [
					'subject_id' => $subjectIdMeta,
					'name' => $row['component_name'] ?? ('Component ' . $componentId),
					'instances' => $instances,
					'marks_per_instance' => $marksPerInstance,
					'raw_total' => $rawTotal,
					'scaled_total' => $scaledTotal,
					'scale_ratio' => $scaleRatio,
					'per_instance_cap' => $perInstanceCap
				];
				if ($subjectIdMeta > 0) {
					$subjectAllocated[$subjectIdMeta] = ($subjectAllocated[$subjectIdMeta] ?? 0.0) + $scaledTotal;
				}
			}
			mysqli_free_result($fallbackComponentResult);
		}
		mysqli_stmt_close($fallbackStmtComponents);
	}
}

$formatScoreLabel = static function (?float $value): ?string {
	if ($value === null) {
		return null;
	}
	if (abs($value - round($value)) < 0.05) {
		return (string)round($value);
	}
	return number_format($value, 1);
};

$classStudentIds = [$studentId];
if ($classId) {
	$studentIdSql = "SELECT id FROM students WHERE class_id = ?";
	$stmtClassStudents = mysqli_prepare($conn, $studentIdSql);
	if ($stmtClassStudents) {
		mysqli_stmt_bind_param($stmtClassStudents, 'i', $classId);
		mysqli_stmt_execute($stmtClassStudents);
		$resultClassStudents = mysqli_stmt_get_result($stmtClassStudents);
		if ($resultClassStudents) {
			while ($row = mysqli_fetch_assoc($resultClassStudents)) {
				$peerId = isset($row['id']) ? (int)$row['id'] : 0;
				if ($peerId > 0) {
					$classStudentIds[] = $peerId;
				}
			}
			mysqli_free_result($resultClassStudents);
		}
		mysqli_stmt_close($stmtClassStudents);
	}
}
$classStudentIds = array_values(array_unique(array_filter($classStudentIds)));

$studentComponentTotals = [];
$studentComponentRecords = [];
foreach ($classStudentIds as $sid) {
	$studentComponentTotals[$sid] = [];
	$studentComponentRecords[$sid] = [];
}

$componentIds = array_keys($componentMeta);
if (!empty($componentIds) && !empty($classStudentIds)) {
	$studentPlaceholders = implode(',', array_fill(0, count($classStudentIds), '?'));
	$componentPlaceholders = implode(',', array_fill(0, count($componentIds), '?'));
	$marksParams = array_merge($classStudentIds, $componentIds);
	$marksTypes = str_repeat('i', count($marksParams));
	$marksSql = "SELECT ism.student_id, ism.component_id,
		SUM(CASE WHEN ism.marks IS NOT NULL THEN ism.marks ELSE 0 END) AS raw_sum,
		SUM(CASE WHEN ism.marks IS NULL THEN 1 ELSE 0 END) AS absent_count,
		SUM(CASE WHEN ism.marks IS NOT NULL THEN 1 ELSE 0 END) AS numeric_count,
		MAX(ism.updated_at) AS last_update
	FROM ica_student_marks ism
	WHERE ism.student_id IN ($studentPlaceholders)
		AND ism.component_id IN ($componentPlaceholders)";
	if ($marksDateClause && $termStartBound && $termEndBound) {
		$marksSql .= " AND ((ism.updated_at BETWEEN ? AND ?) OR ism.updated_at IS NULL)";
		$marksTypes .= 'ss';
		$marksParams[] = $termStartBound;
		$marksParams[] = $termEndBound;
	}
	$marksSql .= " GROUP BY ism.student_id, ism.component_id";
	$stmtMarks = mysqli_prepare($conn, $marksSql);
	if ($stmtMarks) {
		$marksBindParams = [];
		foreach ($marksParams as $idx => $value) {
			$marksBindParams[$idx] = &$marksParams[$idx];
		}
		array_unshift($marksBindParams, $stmtMarks, $marksTypes);
		call_user_func_array('mysqli_stmt_bind_param', $marksBindParams);
		if (mysqli_stmt_execute($stmtMarks)) {
			$resultMarks = mysqli_stmt_get_result($stmtMarks);
			if ($resultMarks) {
				while ($row = mysqli_fetch_assoc($resultMarks)) {
					$rowStudentId = isset($row['student_id']) ? (int)$row['student_id'] : 0;
					$rowComponentId = isset($row['component_id']) ? (int)$row['component_id'] : 0;
					if ($rowStudentId <= 0 || $rowComponentId <= 0) {
						continue;
					}
					if (!isset($studentComponentTotals[$rowStudentId]) || !isset($componentMeta[$rowComponentId])) {
						continue;
					}
					$rawSum = isset($row['raw_sum']) ? (float)$row['raw_sum'] : 0.0;
					$absentCount = isset($row['absent_count']) ? (int)$row['absent_count'] : 0;
					$numericCount = isset($row['numeric_count']) ? (int)$row['numeric_count'] : 0;

					$meta = $componentMeta[$rowComponentId];
					$scaleRatio = isset($meta['scale_ratio']) ? (float)$meta['scale_ratio'] : 1.0;
					if ($scaleRatio <= 0) {
						$scaleRatio = 1.0;
					}
					$scaledValue = $rawSum * $scaleRatio;
					$instances = isset($meta['instances']) ? max(1, (int)$meta['instances']) : 1;
					$perInstanceCap = $meta['per_instance_cap'] ?? null;
					if ($perInstanceCap !== null) {
						$componentCap = $perInstanceCap * $instances;
						if ($scaledValue > $componentCap) {
							$scaledValue = $componentCap;
						}
					}
					$scaledTotal = isset($meta['scaled_total']) ? (float)$meta['scaled_total'] : 0.0;
					if ($scaledTotal > 0 && $scaledValue > $scaledTotal + 0.0001) {
						$scaledValue = min($scaledValue, $scaledTotal);
					}

					$studentComponentTotals[$rowStudentId][$rowComponentId] = $scaledValue;
					$studentComponentRecords[$rowStudentId][$rowComponentId] = [
						'numeric_count' => $numericCount,
						'absent_count' => $absentCount,
						'has_records' => ($numericCount + $absentCount) > 0,
						'last_update' => $row['last_update'] ?? null
					];
				}
				mysqli_free_result($resultMarks);
			}
		}
		mysqli_stmt_close($stmtMarks);
	}
}

$studentSubjectTotals = [];
$studentSubjectHasRecords = [];
$studentSubjectHasNumeric = [];
$studentSubjectAbsentOnly = [];
$studentSubjectLastUpdate = [];

foreach ($classStudentIds as $sid) {
	foreach ($componentMeta as $componentId => $meta) {
		$subjectIdMeta = $meta['subject_id'];
		if ($subjectIdMeta <= 0) {
			continue;
		}
		if (!isset($studentSubjectTotals[$sid][$subjectIdMeta])) {
			$studentSubjectTotals[$sid][$subjectIdMeta] = 0.0;
		}
		$value = $studentComponentTotals[$sid][$componentId] ?? 0.0;
		$studentSubjectTotals[$sid][$subjectIdMeta] += $value;
		$record = $studentComponentRecords[$sid][$componentId] ?? ['numeric_count' => 0, 'absent_count' => 0, 'has_records' => false, 'last_update' => null];
		if (!empty($record['has_records'])) {
			$studentSubjectHasRecords[$sid][$subjectIdMeta] = true;
		}
		if (!empty($record['numeric_count'])) {
			$studentSubjectHasNumeric[$sid][$subjectIdMeta] = true;
		}
		if (!empty($record['absent_count']) && empty($record['numeric_count'])) {
			$studentSubjectAbsentOnly[$sid][$subjectIdMeta] = true;
		}
		if (!empty($record['last_update'])) {
			$existingUpdate = $studentSubjectLastUpdate[$sid][$subjectIdMeta] ?? null;
			if ($existingUpdate === null || $record['last_update'] > $existingUpdate) {
				$studentSubjectLastUpdate[$sid][$subjectIdMeta] = $record['last_update'];
			}
		}
	}
}

foreach ($subjectIds as $subjectIdValue) {
	if (!isset($subjectAllocated[$subjectIdValue])) {
		$subjectAllocated[$subjectIdValue] = 0.0;
	}
}

foreach ($subjectAllocated as $subjectIdValue => $allocatedTotal) {
	if ($allocatedTotal > 50.0) {
		$subjectAllocated[$subjectIdValue] = 50.0;
	}
}

foreach ($classStudentIds as $sid) {
	foreach ($subjectAllocated as $subjectIdValue => $allocatedTotal) {
		if (!isset($studentSubjectTotals[$sid][$subjectIdValue])) {
			$studentSubjectTotals[$sid][$subjectIdValue] = 0.0;
		}
		if ($allocatedTotal > 0 && $studentSubjectTotals[$sid][$subjectIdValue] > $allocatedTotal + 0.0001) {
			$studentSubjectTotals[$sid][$subjectIdValue] = min($studentSubjectTotals[$sid][$subjectIdValue], $allocatedTotal);
		}
	}
}

foreach ($subjectIds as $subjectIdValue) {
	$subjectName = '';
	foreach ($subjects as $subjectRow) {
		if (isset($subjectRow['id']) && (int)$subjectRow['id'] === $subjectIdValue) {
			$subjectName = $subjectRow['subject_name'] ?? ('Subject ' . $subjectIdValue);
			break;
		}
	}
	$obtainedValue = $studentSubjectTotals[$studentId][$subjectIdValue] ?? 0.0;
	$totalValue = $subjectAllocated[$subjectIdValue] ?? 0.0;
	$lastUpdateValue = $studentSubjectLastUpdate[$studentId][$subjectIdValue] ?? null;
	$subjectPerformance[$subjectIdValue] = [
		'name' => $subjectName,
		'obtained' => $obtainedValue,
		'total' => $totalValue,
		'last_update' => $lastUpdateValue,
		'has_records' => !empty($studentSubjectHasRecords[$studentId][$subjectIdValue]),
		'has_numeric' => !empty($studentSubjectHasNumeric[$studentId][$subjectIdValue]),
		'absent_only' => !empty($studentSubjectAbsentOnly[$studentId][$subjectIdValue])
	];
	$classTotalsForSubject = [];
	foreach ($classStudentIds as $peerId) {
		if (!empty($studentSubjectHasRecords[$peerId][$subjectIdValue])) {
			$classTotalsForSubject[] = $studentSubjectTotals[$peerId][$subjectIdValue] ?? 0.0;
		}
	}
	$classHigh = !empty($classTotalsForSubject) ? max($classTotalsForSubject) : null;
	$classAverage = !empty($classTotalsForSubject) ? (array_sum($classTotalsForSubject) / count($classTotalsForSubject)) : null;
	$studentHasRecords = !empty($studentSubjectHasRecords[$studentId][$subjectIdValue]);
	$subjectComparisons[$subjectIdValue] = [
		'name' => $subjectName,
		'class_high' => $classHigh,
		'class_average' => $classAverage,
		'student_total' => $studentHasRecords ? ($studentSubjectTotals[$studentId][$subjectIdValue] ?? 0.0) : null,
	];
}

$totalAllocatedOverall = 0.0;
$totalObtainedOverall = 0.0;
$overallLastUpdate = null;
foreach ($subjectIds as $subjectIdValue) {
	$allocatedValue = $subjectAllocated[$subjectIdValue] ?? 0.0;
	$obtainedValue = $studentSubjectTotals[$studentId][$subjectIdValue] ?? 0.0;
	$totalAllocatedOverall += $allocatedValue;
	$totalObtainedOverall += $obtainedValue;
	$subjectUpdate = $studentSubjectLastUpdate[$studentId][$subjectIdValue] ?? null;
	if ($subjectUpdate && ($overallLastUpdate === null || $subjectUpdate > $overallLastUpdate)) {
		$overallLastUpdate = $subjectUpdate;
	}
}
if ($totalAllocatedOverall > 0 && $totalObtainedOverall > $totalAllocatedOverall + 0.0001) {
	$totalObtainedOverall = min($totalObtainedOverall, $totalAllocatedOverall);
}

$gradedCount = 0;
$pendingCount = 0;
foreach ($componentMeta as $componentId => $meta) {
	$instances = max(1, (int)$meta['instances']);
	$record = $studentComponentRecords[$studentId][$componentId] ?? ['numeric_count' => 0, 'absent_count' => 0, 'last_update' => null];
	$numericCount = (int)($record['numeric_count'] ?? 0);
	$absentCount = (int)($record['absent_count'] ?? 0);
	$gradedCount += $numericCount;
	$completedInstances = min($instances, $numericCount + $absentCount);
	$pendingCount += max(0, $instances - $completedInstances);
	if (!empty($record['last_update']) && ($overallLastUpdate === null || $record['last_update'] > $overallLastUpdate)) {
		$overallLastUpdate = $record['last_update'];
	}
}

$performanceStats['overall'] = $totalAllocatedOverall > 0 ? round(($totalObtainedOverall / $totalAllocatedOverall) * 100, 1) : 0;
$performanceStats['graded'] = $gradedCount;
$performanceStats['pending'] = $pendingCount;
$performanceStats['last_update'] = $overallLastUpdate;
$subjectsById = [];
foreach ($subjects as $subjectRow) {
	$subjectId = isset($subjectRow['id']) ? (int)$subjectRow['id'] : 0;
	if ($subjectId > 0) {
		$subjectNameRaw = $subjectRow['subject_name'] ?? '';
		$subjectsById[$subjectId] = $subjectRow['subject_name_display'] ?? format_subject_display($subjectNameRaw);
	}
}
foreach ($subjectPerformance as $subjectId => $meta) {
	if (!isset($subjectsById[$subjectId])) {
		$subjectNameRaw = $meta['name'] ?? ('Subject ' . $subjectId);
		$subjectsById[$subjectId] = format_subject_display($subjectNameRaw);
	}
}

$subjectSummaries = [];
$programChairAlerts = [];

foreach ($subjectsById as $subjectId => $subjectName) {
	$performance = $subjectPerformance[$subjectId] ?? null;
	$obtained = $performance['obtained'] ?? 0.0;
	$total = $performance['total'] ?? 0.0;
	$percentage = ($total > 0) ? round(($obtained / $total) * 100, 1) : null;
	$hasFullAllocation = $total >= 49.999;
	$marksObtainedLabel = $formatScoreLabel($obtained) ?? '0';
	$marksTotalLabel = $formatScoreLabel($total) ?? '0';
	$marksLabel = $total > 0 ? $marksObtainedLabel . ' / ' . $marksTotalLabel : 'Not evaluated';
	$percentageLabel = ($percentage !== null) ? number_format($percentage, 1) . '%' : '—';
	$statusLabel = $hasFullAllocation ? 'On Track' : 'In Progress';
	$statusClass = 'status-pill';
	if ($total > 0) {
		if ($hasFullAllocation && $percentage !== null && $percentage + 0.0001 < $performanceThreshold) {
			$statusLabel = 'At Risk';
			$statusClass = 'status-pill pending';
		} elseif ($percentage !== null && $hasFullAllocation) {
			$statusLabel = 'On Track';
			$statusClass = 'status-pill success';
		} elseif ($percentage !== null) {
			$statusClass = 'status-pill';
		}
	}
	$teacherNames = $subjectTeacherMap[$subjectId] ?? [];
	$teacherNamesDisplay = [];
	foreach ($teacherNames as $teacherName) {
		$trimmedTeacher = trim((string)$teacherName);
		if ($trimmedTeacher === '') {
			continue;
		}
		$teacherNamesDisplay[] = format_person_display($trimmedTeacher);
	}
	$teacherNamesDisplay = array_values(array_unique($teacherNamesDisplay));
	$teacherLabel = !empty($teacherNamesDisplay) ? implode(', ', $teacherNamesDisplay) : 'FACULTY TBD';
	$lastUpdateRaw = $performance['last_update'] ?? null;
	$lastUpdateLabel = $lastUpdateRaw ? date('d M Y', strtotime($lastUpdateRaw)) : '—';
	$comparison = $subjectComparisons[$subjectId] ?? null;
	$classHigh = $comparison['class_high'] ?? null;
	$classAverage = $comparison['class_average'] ?? null;
	$studentTotal = $obtained;
	$gapToHigh = ($classHigh !== null) ? round($classHigh - $studentTotal, 1) : null;
	if ($gapToHigh !== null && abs($gapToHigh) < 0.05) {
		$gapToHigh = 0.0;
	}
	$classHighPct = ($total > 0 && $classHigh !== null) ? round(($classHigh / $total) * 100, 1) : null;
	$classAveragePct = ($total > 0 && $classAverage !== null) ? round(($classAverage / $total) * 100, 1) : null;
	$subjectSummaries[] = [
		'subject_id' => $subjectId,
		'subject_name' => $subjectName,
		'teachers' => $teacherLabel,
		'marks_label' => $marksLabel,
		'percentage_label' => $percentageLabel,
		'status_label' => $statusLabel,
		'status_class' => $statusClass,
		'last_update' => $lastUpdateLabel,
		'percentage_value' => $percentage,
		'student_total' => $studentTotal,
		'total_possible' => $total,
		'class_high' => $classHigh,
		'class_average' => $classAverage,
		'class_high_pct' => $classHighPct,
		'class_average_pct' => $classAveragePct,
		'gap_to_high' => $gapToHigh
	];
	if ($statusLabel === 'At Risk' && $percentage !== null && $hasFullAllocation) {
		$alertMessage = sprintf(
			"Your ICA marks for %s are %s (%s), which is below the %s%% benchmark. Please focus on this subject to get back on track.",
			$subjectName,
			$marksLabel,
			$percentageLabel,
			number_format($performanceThreshold, 0)
		);
		$programChairAlerts[] = [
			'subject' => $subjectName,
			'message' => $alertMessage
		];
	}
}

usort($subjectSummaries, static function ($a, $b) {
	return strcasecmp($a['subject_name'], $b['subject_name']);
});

$subjectChartPayload = [];
foreach ($subjectSummaries as $summary) {
	$totalPossible = $summary['total_possible'] ?? 0;
	if ($totalPossible <= 0) {
		continue;
	}
	$subjectChartPayload[] = [
		'subject_id' => $summary['subject_id'],
		'subject_name' => $summary['subject_name'],
		'total_possible' => $totalPossible,
		'student_total' => $summary['student_total'] ?? 0,
		'class_high' => $summary['class_high'] ?? null,
		'class_average' => $summary['class_average'] ?? null,
		'student_percentage' => $summary['percentage_value'],
		'class_high_pct' => $summary['class_high_pct'],
		'class_average_pct' => $summary['class_average_pct'],
		'gap_to_high' => $summary['gap_to_high']
	];
}

$decorateMarkRows = static function (array &$rows) use ($componentMeta, $formatScoreLabel): void {
	foreach ($rows as &$row) {
		$componentId = isset($row['component_id']) ? (int)$row['component_id'] : 0;
		$perInstanceCap = null;
		if ($componentId > 0 && isset($componentMeta[$componentId])) {
			$perInstanceCap = $componentMeta[$componentId]['per_instance_cap'] ?? null;
		}
		$formattedCap = $perInstanceCap !== null ? $formatScoreLabel($perInstanceCap) : null;
		$formattedScore = null;
		if (isset($row['marks']) && $row['marks'] !== null) {
			$formattedScore = $formatScoreLabel((float)$row['marks']);
		}
		if ($formattedScore !== null && $formattedCap !== null) {
			$row['marks_display'] = $formattedScore . ' / ' . $formattedCap;
		} elseif ($formattedScore !== null) {
			$row['marks_display'] = $formattedScore;
		} else {
			$row['marks_display'] = null;
		}
		$row['marks_denominator'] = $formattedCap;
	}
	unset($row);
};

$decorateMarkRows($recentMarks);
$decorateMarkRows($icaMarks);

$atRiskCount = count($programChairAlerts);

$profileDetails = [
	'SAP ID' => $studentSapId,
	'Roll Number' => $rollNumber,
	'Class' => $classLabel,
	'Section' => $sectionLabel,
	'Semester' => $semesterLabel !== '' ? $semesterLabel : 'N/A',
	'School' => $schoolLabel !== '' ? $schoolLabel : 'N/A'
];

$subjectsCount = count($subjects);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Student Dashboard - ICA Tracker</title>
	<link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
	<link rel="stylesheet" href="ica_tracker.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
	<div class="dashboard">
		<div class="sidebar">
			<h2>ICA Tracker</h2>
			<a href="student_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
			<a href="view_marks.php"><i class="fas fa-chart-line"></i> <span>Marks</span></a>
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
					<h2>Welcome, <?php echo htmlspecialchars($studentName); ?>!</h2>
					<p>Here is the latest snapshot of your academic progress.</p>
				</div>
			</div>
			<?php if ($pendingAssignmentCount > 0): ?>
			<div class="alert alert-warning">
				<strong><?php echo $pendingAssignmentCount; ?> pending assignment<?php echo $pendingAssignmentCount !== 1 ? 's' : ''; ?>.</strong>
				<span> Review your tasks to stay on schedule.</span>
				<a class="alert-link" href="view_assignment_marks.php">View assignments</a>
			</div>
			<?php endif; ?>
			<?php renderTermSwitcher($academicContext, [
				'school_name' => $schoolLabel,
				'fallback_semester' => $semesterLabel,
			]); ?>
			<div class="container">
				<div class="card-grid">
					<div class="card stat-card">
						<span class="stat-label">Overall performance</span>
						<h3><?php echo $performanceStats['overall']; ?>%</h3>
						<p><?php echo $performanceStats['graded']; ?> components evaluated so far.</p>
					</div>
					<div class="card stat-card<?php echo $atRiskCount > 0 ? '' : ' success'; ?>">
						<span class="stat-label">At-risk subjects</span>
						<h3><?php echo $atRiskCount; ?></h3>
						<p><?php echo $atRiskCount > 0 ? 'Review the alerts below for action items.' : 'All subjects are currently on track.'; ?></p>
					</div>
					<div class="card stat-card secondary">
						<span class="stat-label">Subjects enrolled</span>
						<h3><?php echo $subjectsCount; ?></h3>
						<p><?php echo $pendingAssignmentCount; ?> assignments pending.</p>
					</div>
					<div class="card stat-card success">
						<span class="stat-label">Assignments completed</span>
						<h3><?php echo $completedAssignmentCount; ?></h3>
						<p>Keep going—stay ahead of deadlines!</p>
					</div>
					<?php if ($academicSummary): ?>
					<div class="card stat-card">
						<span class="stat-label">Term progress</span>
						<h3><?php echo $academicSummary['progress']; ?>%</h3>
						<p><?php echo htmlspecialchars($academicSummary['term']); ?><br><?php echo $academicSummary['days_remaining']; ?> days remaining.</p>
					</div>
					<?php endif; ?>
				</div>

					<div class="card">
						<div class="card-header subject-comparison-header">
							<h3 class="section-title">To-Do List</h3>
							<a class="card-quick-link" href="view_assignment_marks.php">View all assignments</a>
						</div>
						<?php if (!empty($todoAssignments)): ?>
						<ul class="todo-list">
							<?php foreach ($todoAssignments as $todo): ?>
							<li class="todo-item<?php echo !empty($todo['todo_is_overdue']) ? ' overdue' : ''; ?>">
								<div>
									<div class="todo-item-title"><?php echo htmlspecialchars($todo['title'] ?? 'Untitled Assignment'); ?></div>
									<div class="todo-item-meta">
										<?php echo htmlspecialchars($todo['subject_name_display'] ?? ($todo['subject_name'] ?? ($todo['subject'] ?? 'Subject TBD'))); ?>
										· <?php echo htmlspecialchars($todo['status_label'] ?? 'Pending'); ?>
										<?php if (!empty($todo['todo_deadline_label'])): ?>
											· Due <?php echo htmlspecialchars($todo['todo_deadline_label']); ?>
											<?php if (!empty($todo['todo_deadline_hint'])): ?> (<?php echo htmlspecialchars($todo['todo_deadline_hint']); ?>)<?php endif; ?>
										<?php endif; ?>
									</div>
								</div>
								<a class="todo-item-link" href="view_assignment_marks.php?focus=<?php echo (int)($todo['id'] ?? 0); ?>#assignment-<?php echo (int)($todo['id'] ?? 0); ?>">Open</a>
							</li>
							<?php endforeach; ?>
						</ul>
						<?php else: ?>
						<div class="empty-state">No tasks pending—enjoy the momentum.</div>
						<?php endif; ?>
					</div>

				<div class="card" style="border-left:4px solid #A6192E;">
					<h3 class="section-title">Alerts from Program Chair</h3>
					<?php if (!empty($programChairAlerts)): ?>
						<div class="info-list" style="grid-template-columns: 1fr;">
							<?php foreach ($programChairAlerts as $alert): ?>
							<div class="info-row" style="background: rgba(166, 25, 46, 0.08);">
								<p style="margin:0 0 4px; font-size:0.85rem; color:#A6192E; text-transform:uppercase; letter-spacing:0.05em;">Subject</p>
								<p style="margin:0 0 6px; font-size:1rem; color:#2c3e50; font-weight:700;"><?php echo htmlspecialchars($alert['subject']); ?></p>
								<p style="margin:0; font-size:0.9rem; color:#444; line-height:1.4;"><?php echo htmlspecialchars($alert['message']); ?></p>
							</div>
							<?php endforeach; ?>
						</div>
					<?php else: ?>
						<div class="empty-state">You're all caught up—no alerts from the program chair right now.</div>
					<?php endif; ?>
				</div>

				<div class="card">
					<h3 class="section-title">Profile Overview</h3>
					<div class="info-list">
						<?php foreach ($profileDetails as $label => $value): ?>
							<div class="info-row">
								<dt><?php echo htmlspecialchars($label); ?></dt>
								<dd><?php echo htmlspecialchars($value); ?></dd>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

			<?php if ($academicSummary): ?>
			<div class="card">
				<h3 class="section-title">Academic Term Timeline</h3>
				<div class="info-list" style="margin-bottom: 16px;">
					<div class="info-row">
						<dt>Term</dt>
						<dd><?php echo htmlspecialchars($academicSummary['term']); ?></dd>
					</div>
					<div class="info-row">
						<dt>Starts</dt>
						<dd><?php echo date('d M Y', strtotime($academicSummary['start'])); ?></dd>
					</div>
					<div class="info-row">
						<dt>Ends</dt>
						<dd><?php echo date('d M Y', strtotime($academicSummary['end'])); ?></dd>
					</div>
				</div>
				<div class="progress-wrapper">
					<div class="progress-bar">
						<span style="width: <?php echo min(100, max(0, (int)$academicSummary['progress'])); ?>%"></span>
					</div>
				</div>
				<p class="table-note">You have <?php echo $academicSummary['days_remaining']; ?> days left in this term.</p>
			</div>
			<?php endif; ?>

			<div class="card">
				<h3 class="section-title">Enrolled Subjects</h3>
				<?php if (!empty($subjects)): ?>
					<ul class="pill-list">
						<?php foreach ($subjects as $subject): ?>
							<li><?php echo htmlspecialchars($subject['subject_name_display'] ?? $subject['subject_name']); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php else: ?>
					<div class="empty-state">Your subjects will appear here once assigned.</div>
				<?php endif; ?>
			</div>

			<div class="card">
				<h3 class="section-title">Course Performance Overview</h3>
				<?php if (!empty($subjectSummaries)): ?>
					<div class="table-responsive">
						<table>
							<thead>
								<tr>
									<th class="text-left">Subject</th>
									<th class="text-left">Faculty</th>
									<th>ICA Marks</th>
									<th>Status</th>
									<th>Last Update</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($subjectSummaries as $summary): ?>
									<tr>
										<td class="text-left"><?php echo htmlspecialchars($summary['subject_name']); ?></td>
										<td class="text-left"><?php echo htmlspecialchars($summary['teachers']); ?></td>
										<td>
											<?php echo htmlspecialchars($summary['marks_label']); ?>
											<?php if ($summary['percentage_label'] !== '—'): ?>
												<div class="text-muted">Avg <?php echo htmlspecialchars($summary['percentage_label']); ?></div>
											<?php endif; ?>
										</td>
										<td><span class="<?php echo htmlspecialchars($summary['status_class']); ?>"><?php echo htmlspecialchars($summary['status_label']); ?></span></td>
										<td><?php echo htmlspecialchars($summary['last_update']); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else: ?>
					<div class="empty-state">Once ICA evaluations are recorded, you'll see each course's status here.</div>
				<?php endif; ?>
			</div>

		<?php if (!empty($subjectChartPayload)): ?>
		<div class="card">
			<div class="card-header subject-comparison-header">
				<h3 class="section-title">Subject Comparison</h3>
				<a class="card-quick-link" href="subject_comparison.php">Open full view</a>
			</div>
			<p class="table-note">Your cumulative ICA marks are benchmarked against the class high and class average for each subject.</p>
			<div class="subject-chart-grid">
				<?php foreach ($subjectChartPayload as $chartMeta): ?>
					<div class="subject-mini-card">
						<div class="subject-mini-header">
							<h4><?php echo htmlspecialchars($chartMeta['subject_name']); ?></h4>
							<?php if ($chartMeta['gap_to_high'] !== null): ?>
								<span class="chart-tag <?php echo ($chartMeta['gap_to_high'] <= 0) ? 'chart-tag-good' : 'chart-tag-gap'; ?>">
									<?php echo $chartMeta['gap_to_high'] <= 0 ? 'Matching topper' : 'Gap to topper: ' . number_format((float)$chartMeta['gap_to_high'], 1); ?>
								</span>
							<?php endif; ?>
						</div>
						<div class="chart-container chart-container-inline">
							<canvas id="subject-chart-<?php echo (int)$chartMeta['subject_id']; ?>"
								data-chart="<?php echo htmlspecialchars(json_encode($chartMeta, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>"></canvas>
						</div>
						<ul class="chart-legend">
							<li><span class="dot dot-student"></span> You</li>
							<li><span class="dot dot-high"></span> Class High</li>
							<li><span class="dot dot-average"></span> Class Avg</li>
						</ul>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

			<div class="card">
				<h3 class="section-title">Recent ICA Updates</h3>
				<?php if (!empty($recentMarks)): ?>
					<div class="table-responsive">
						<table>
							<thead>
								<tr>
									<th class="text-left">Subject</th>
									<th class="text-left">Component</th>
									<th>Marks</th>
									<th>Updated</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($recentMarks as $item): ?>
									<tr>
										<td class="text-left"><?php echo htmlspecialchars($item['subject_name_display'] ?? $item['subject_name']); ?></td>
										<?php
											$instanceNumber = isset($item['instance_number']) ? (int)$item['instance_number'] : null;
											$componentLabel = $item['component_name'] ?? '';
											if ($instanceNumber !== null && $instanceNumber > 0) {
												$componentLabel = rtrim($componentLabel) . ' ' . $instanceNumber;
											}
										?>
										<td class="text-left"><?php echo htmlspecialchars($componentLabel); ?></td>
										<td>
											<?php
												if ($item['marks'] !== null) {
													$display = $item['marks_display'] ?? $formatScoreLabel((float)$item['marks']);
													echo htmlspecialchars($display ?? '');
												} else {
													echo 'Pending';
												}
											?>
										</td>
										<td><?php echo $item['updated_at'] ? date('d M Y', strtotime($item['updated_at'])) : '—'; ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else: ?>
					<div class="empty-state">Once new marks are published, the latest updates will be listed here.</div>
				<?php endif; ?>
			</div>

			<div class="card">
				<h3 class="section-title">All ICA Marks</h3>
				<?php if (!empty($icaMarks)): ?>
					<div class="table-responsive">
						<table>
							<thead>
								<tr>
									<th class="text-left">Subject</th>
									<th class="text-left">Component</th>
									<th>Marks</th>
									<th>Updated</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($icaMarks as $mark): ?>
									<tr>
										<td class="text-left"><?php echo htmlspecialchars($mark['subject_name_display'] ?? $mark['subject_name']); ?></td>
										<?php
											$instanceNumber = isset($mark['instance_number']) ? (int)$mark['instance_number'] : null;
											$componentLabel = $mark['component_name'] ?? '';
											if ($instanceNumber !== null && $instanceNumber > 0) {
												$componentLabel = rtrim($componentLabel) . ' ' . $instanceNumber;
											}
										?>
										<td class="text-left"><?php echo htmlspecialchars($componentLabel); ?></td>
										<td>
											<?php
												if ($mark['marks'] !== null) {
													$display = $mark['marks_display'] ?? $formatScoreLabel((float)$mark['marks']);
													echo htmlspecialchars($display ?? '');
												} else {
													echo 'Pending';
												}
											?>
										</td>
										<td><?php echo $mark['updated_at'] ? date('d M Y', strtotime($mark['updated_at'])) : '—'; ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else: ?>
					<div class="empty-state">ICA marks will appear here once evaluations are recorded.</div>
				<?php endif; ?>
			</div>

			<div class="card">
				<h3 class="section-title">Assignments</h3>
				<?php if (!empty($outstandingAssignments)): ?>
					<h4 class="section-title-sm">Pending</h4>
					<div class="table-responsive">
						<table>
							<thead>
								<tr>
									<th class="text-left">Title</th>
									<th class="text-left">Subject</th>
									<th>Deadline</th>
									<th>Status</th>
									<th>Marks</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($outstandingAssignments as $assignment): ?>
									<?php
										$statusClass = 'status-pill ';
										switch ($assignment['status_slug']) {
											case 'submitted':
												$statusClass .= 'pending';
												break;
											case 'late_submitted':
												$statusClass .= 'warning';
												break;
											case 'rejected':
												$statusClass .= 'danger';
												break;
											case 'completed':
												$statusClass .= 'success';
												break;
											default:
												$statusClass .= 'pending';
										}
									?>
									<tr>
										<td class="text-left"><?php echo htmlspecialchars($assignment['title']); ?></td>
										<td class="text-left"><?php echo htmlspecialchars($assignment['subject_name_display'] ?? ($assignment['subject_name'] ?? '—')); ?></td>
										<td><?php echo $assignment['deadline'] ? date('d M Y', strtotime($assignment['deadline'])) : '—'; ?></td>
										<td><span class="<?php echo trim($statusClass); ?>"><?php echo htmlspecialchars($assignment['status_label']); ?></span></td>
										<td><?php echo $assignment['graded_marks'] !== null ? number_format((float)$assignment['graded_marks'], 2) : '—'; ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else: ?>
					<div class="empty-state">No pending assignments. Great job staying ahead!</div>
				<?php endif; ?>

				<?php if (!empty($completedAssignments)): ?>
					<h4 class="section-title-sm" style="margin-top: 24px;">Completed</h4>
					<div class="table-responsive">
						<table>
							<thead>
								<tr>
									<th class="text-left">Title</th>
									<th class="text-left">Subject</th>
									<th>Marks</th>
									<th>Updated</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($completedAssignments as $assignment): ?>
									<tr>
										<td class="text-left"><?php echo htmlspecialchars($assignment['title']); ?></td>
										<td class="text-left"><?php echo htmlspecialchars($assignment['subject_name_display'] ?? ($assignment['subject_name'] ?? '—')); ?></td>
										<td><?php echo $assignment['graded_marks'] !== null ? number_format((float)$assignment['graded_marks'], 2) : 'Not graded'; ?></td>
										<td>
											<?php
												$dateValue = $assignment['graded_at'] ?? $assignment['last_submission_at'] ?? $assignment['deadline'];
												echo $dateValue ? date('d M Y', strtotime($dateValue)) : '—';
											?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>

			<div class="card">
				<h3 class="section-title">Faculty Directory</h3>
				<?php if (!empty($teacherDirectory)): ?>
					<div class="table-responsive">
						<table>
							<thead>
								<tr>
									<th class="text-left">Name</th>
									<th class="text-left">Email</th>
									<th class="text-left">Subjects</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($teacherDirectory as $teacher): ?>
									<tr>
										<td class="text-left"><?php echo htmlspecialchars($teacher['name_display'] ?? ($teacher['name'] !== '' ? format_person_display($teacher['name']) : 'FACULTY')); ?></td>
										<td class="text-left"><?php echo htmlspecialchars($teacher['email'] ?: '—'); ?></td>
										<td class="text-left"><?php echo htmlspecialchars(implode(', ', $teacher['subjects'])); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else: ?>
					<div class="empty-state">Faculty assignments will appear here once published for your class.</div>
				<?php endif; ?>
			</div>
		</div>
		<div class="footer-bottom">
			&copy; <?php echo date("Y"); ?> Kuchuru Sai Krishna Reddy – STME. All rights reserved.
		</div>
	</div>
	</div>

	<?php if (!empty($subjectChartPayload)): ?>
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const charts = document.querySelectorAll('canvas[id^="subject-chart-"]');
			charts.forEach(function (canvas) {
				var raw = canvas.getAttribute('data-chart');
				if (!raw) {
					return;
				}
				var parsed;
				try {
					parsed = JSON.parse(raw);
				} catch (error) {
					return;
				}
				var studentValue = typeof parsed.student_total === 'number' ? parsed.student_total : 0;
				var classHigh = typeof parsed.class_high === 'number' ? parsed.class_high : null;
				var classAverage = typeof parsed.class_average === 'number' ? parsed.class_average : null;
				var totalPossible = typeof parsed.total_possible === 'number' ? parsed.total_possible : 0;
				var labels = ['You', 'Class High', 'Class Avg'];
				var dataPoints = [studentValue, classHigh !== null ? classHigh : 0, classAverage !== null ? classAverage : 0];
				var upperBound = Math.max.apply(null, dataPoints.concat(totalPossible));
				if (!isFinite(upperBound) || upperBound <= 0) {
					upperBound = 10;
				}
				var context = canvas.getContext('2d');
				new Chart(context, {
					type: 'bar',
					data: {
						labels: labels,
						datasets: [{
							label: 'Marks',
							data: dataPoints,
							backgroundColor: ['rgba(166, 25, 46, 0.75)', 'rgba(33, 150, 83, 0.65)', 'rgba(33, 133, 208, 0.55)'],
							borderColor: ['rgba(166, 25, 46, 1)', 'rgba(33, 150, 83, 1)', 'rgba(33, 133, 208, 1)'],
							borderWidth: 1.2,
							borderRadius: 6
						}]
					},
					options: {
						maintainAspectRatio: false,
						responsive: true,
						scales: {
							y: {
								beginAtZero: true,
								suggestedMax: upperBound + (upperBound * 0.1),
								title: {
									display: true,
									text: 'Marks',
									color: '#444'
								},
								ticks: { color: '#444' }
							},
							x: {
								ticks: { color: '#444' }
							}
						},
						plugins: {
							legend: { display: false },
							tooltip: {
								callbacks: {
									label: function (context) {
										var label = context.dataset.label || '';
										if (label) {
											label += ': ';
										}
										label += context.parsed.y.toFixed(1);
										return label;
									}
								}
							}
						}
					}
				});
			});
		});
	</script>
	<?php endif; ?>
</body>
</html>

<?php
mysqli_close($conn);
?>

