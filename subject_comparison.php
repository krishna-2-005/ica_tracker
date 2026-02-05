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
$studentContext = buildStudentTermContext($conn, $studentSapId, [
    'student_name' => $studentName,
]);
if (!empty($studentContext['error'])) {
    echo htmlspecialchars($studentContext['error']);
    mysqli_close($conn);
    exit;
}

$studentName = $studentContext['student_name'];
$studentId = $studentContext['student_id'] ? (int)$studentContext['student_id'] : 0;
$classId = $studentContext['class_id'] ? (int)$studentContext['class_id'] : null;
$sectionId = $studentContext['section_id'] ? (int)$studentContext['section_id'] : null;
$classLabel = $studentContext['class_label'] ?? 'N/A';
$semesterLabel = $studentContext['semester_label'] ?? '';
$schoolLabel = $studentContext['school_label'] ?? '';
$timelineMismatch = $studentContext['timeline_mismatch'];
$termStartDate = $studentContext['term_start_date'];
$termEndDate = $studentContext['term_end_date'];
$termStartBound = $studentContext['term_start_bound'];
$termEndBound = $studentContext['term_end_bound'];
$academicContext = $studentContext['academic_context'];
$activeTerm = $studentContext['active_term'];

if (!$studentId) {
    $timelineMismatch = true;
}

require_once __DIR__ . '/includes/term_switcher_ui.php';

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
$currentTimestamp = date('Y-m-d H:i:s');
if ($termEndBound !== null && $termEndBound < $currentTimestamp) {
    $termEndBound = $currentTimestamp;
}

$hasActiveProfile = ($studentId > 0 && !$timelineMismatch);

$assignedElectiveIds = ($hasActiveProfile && $classId) ? getAssignedElectiveSubjectIds($conn, $studentId, $classId) : [];

$subjects = [];
$subjectIndex = [];
$subjectTeacherMap = [];
if ($hasActiveProfile && $classId) {
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
                $subjectIndex[$subjectIdValue] = [
                    'id' => $subjectIdValue,
                    'subject_name' => $row['subject_name'] ?? ''
                ];
            }
        }
        if ($subjectsResult) {
            mysqli_free_result($subjectsResult);
        }
        mysqli_stmt_close($stmtSubjects);
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

$marksDateClause = ($termStartBound && $termEndBound);

$icaMarks = [];
if ($hasActiveProfile) {
    $icaMarksQuery = "SELECT s.subject_name, ic.component_name, ism.marks, ism.updated_at, ism.instance_number
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
            $icaMarks[] = $row;
        }
        mysqli_stmt_close($stmtIcaMarks);
    }

    if (empty($icaMarks)) {
        $fallbackMarksSql = "SELECT s.subject_name, ic.component_name, ism.marks, ism.updated_at, ism.instance_number
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
                $icaMarks[] = $row;
            }
            if ($fallbackResult) {
                mysqli_free_result($fallbackResult);
            }
            mysqli_stmt_close($fallbackStmt);
        }
    }
}

$subjects = array_values($subjectIndex);
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
                $subjectIdMeta = isset($row['subject_id']) ? (int)$row['subject_id'] : 0;
                $componentMeta[$componentId] = [
                    'subject_id' => $subjectIdMeta,
                    'name' => $row['component_name'] ?? ('Component ' . $componentId),
                    'instances' => $instances,
                    'marks_per_instance' => $marksPerInstance,
                    'raw_total' => $rawTotal,
                    'scaled_total' => $scaledTotal,
                    'scale_ratio' => $scaleRatio
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
                $subjectIdMeta = isset($row['subject_id']) ? (int)$row['subject_id'] : 0;
                $componentMeta[$componentId] = [
                    'subject_id' => $subjectIdMeta,
                    'name' => $row['component_name'] ?? ('Component ' . $componentId),
                    'instances' => $instances,
                    'marks_per_instance' => $marksPerInstance,
                    'raw_total' => $rawTotal,
                    'scaled_total' => $scaledTotal,
                    'scale_ratio' => $scaleRatio
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
        mysqli_stmt_execute($stmtMarks);
        $resultMarks = mysqli_stmt_get_result($stmtMarks);
        if ($resultMarks) {
            while ($row = mysqli_fetch_assoc($resultMarks)) {
                $componentId = isset($row['component_id']) ? (int)$row['component_id'] : 0;
                $studentRowId = isset($row['student_id']) ? (int)$row['student_id'] : 0;
                if ($componentId <= 0 || $studentRowId <= 0 || !isset($componentMeta[$componentId])) {
                    continue;
                }
                $rawSum = isset($row['raw_sum']) ? (float)$row['raw_sum'] : 0.0;
                $numericCount = isset($row['numeric_count']) ? (int)$row['numeric_count'] : 0;
                $absentCount = isset($row['absent_count']) ? (int)$row['absent_count'] : 0;
                $ratio = $componentMeta[$componentId]['scale_ratio'];
                $scaledValue = $rawSum * $ratio;
                $scaledCap = $componentMeta[$componentId]['scaled_total'];
                if ($scaledCap > 0) {
                    $scaledValue = min($scaledValue, $scaledCap);
                }
                $studentComponentTotals[$studentRowId][$componentId] = $scaledValue;
                $studentComponentRecords[$studentRowId][$componentId] = [
                    'numeric_count' => $numericCount,
                    'absent_count' => $absentCount,
                    'has_records' => ($numericCount + $absentCount) > 0,
                    'last_update' => $row['last_update'] ?? null
                ];
            }
            mysqli_free_result($resultMarks);
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

$subjectsById = [];
foreach ($subjects as $subjectRow) {
    $subjectId = isset($subjectRow['id']) ? (int)$subjectRow['id'] : 0;
    if ($subjectId > 0) {
        $subjectsById[$subjectId] = $subjectRow['subject_name'];
    }
}
foreach ($subjectAllocated as $subjectIdValue => $allocatedTotal) {
    if (!isset($subjectsById[$subjectIdValue])) {
        $subjectsById[$subjectIdValue] = 'Subject ' . $subjectIdValue;
    }
}

$subjectSummaries = [];
$subjectChartPayload = [];
foreach ($subjectsById as $subjectId => $subjectName) {
    $obtained = $studentSubjectTotals[$studentId][$subjectId] ?? 0.0;
    $total = $subjectAllocated[$subjectId] ?? 0.0;
    $lastUpdateValue = $studentSubjectLastUpdate[$studentId][$subjectId] ?? null;
    $percentage = ($total > 0) ? round(($obtained / $total) * 100, 1) : null;
    $hasFullAllocation = $total >= 49.999;
    $formatMark = static function (?float $value): ?string {
        if ($value === null) {
            return null;
        }
        if (abs($value - round($value)) < 0.05) {
            return (string)round($value);
        }
        return number_format($value, 1);
    };
    $marksObtainedLabel = $formatMark($obtained) ?? '0';
    $marksTotalLabel = $formatMark($total) ?? '0';
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
    $lastUpdateLabel = $lastUpdateValue ? date('d M Y', strtotime($lastUpdateValue)) : '—';
    $classTotalsForSubject = [];
    foreach ($classStudentIds as $peerId) {
        if (!empty($studentSubjectHasRecords[$peerId][$subjectId])) {
            $classTotalsForSubject[] = $studentSubjectTotals[$peerId][$subjectId] ?? 0.0;
        }
    }
    $classHigh = !empty($classTotalsForSubject) ? max($classTotalsForSubject) : null;
    $classAverage = !empty($classTotalsForSubject) ? (array_sum($classTotalsForSubject) / count($classTotalsForSubject)) : null;
    $gapToHigh = ($classHigh !== null) ? round($classHigh - $obtained, 1) : null;
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
        'student_total' => $obtained,
        'total_possible' => $total,
        'class_high' => $classHigh,
        'class_average' => $classAverage,
        'class_high_pct' => $classHighPct,
        'class_average_pct' => $classAveragePct,
        'gap_to_high' => $gapToHigh
    ];

    if ($total > 0) {
        $subjectChartPayload[] = [
            'subject_id' => $subjectId,
            'subject_name' => $subjectName,
            'total_possible' => $total,
            'student_total' => $obtained,
            'class_high' => $classHigh,
            'class_average' => $classAverage,
            'student_percentage' => $percentage,
            'class_high_pct' => $classHighPct,
            'class_average_pct' => $classAveragePct,
            'gap_to_high' => $gapToHigh
        ];
    }
}

usort($subjectSummaries, static function ($a, $b) {
    return strcasecmp($a['subject_name'], $b['subject_name']);
});

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Comparison Detail - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .comparison-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .comparison-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 18px; margin-top: 20px; }
        .comparison-card { border: 1px solid #ececec; border-radius: 12px; padding: 18px; background: #fff; box-shadow: 0 8px 16px rgba(0,0,0,0.04); }
        .comparison-card h4 { margin: 0 0 8px; font-size: 1.1rem; }
        .comparison-card-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
        .comparison-card-header h4 { margin: 0; }
        .metric-list { list-style: none; padding: 0; margin: 12px 0 0; }
        .metric-list li { margin-bottom: 6px; font-size: 0.95rem; }
        .chart-wrapper { margin-top: 16px; }
        .pill-muted { display: inline-flex; align-items: center; gap: 6px; background: #f5f5f5; border-radius: 16px; padding: 4px 10px; font-weight: 600; font-size: 0.85rem; }
        .back-link { text-decoration: none; color: #A6192E; font-weight: 600; }
        .summary-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .summary-table th, .summary-table td { border: 1px solid #e0e0e0; padding: 10px; text-align: left; }
        .summary-table th { background: #A6192E; color: #fff; font-weight: 600; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h2>ICA Tracker</h2>
            <a href="student_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="view_marks.php"><i class="fas fa-chart-line"></i> <span>Marks</span></a>
            <a href="subject_comparison.php" class="active"><i class="fas fa-balance-scale"></i> <span>Subject Comparison</span></a>
            <a href="view_assignment_marks.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a>
            <a href="view_timetable.php"><i class="fas fa-calendar-alt"></i> <span>Timetable</span></a>
            <a href="view_progress.php"><i class="fas fa-book"></i> <span>Syllabus Progress</span></a>
            <a href="edit_profile.php"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
        <div class="main-content">
            <div class="header comparison-header">
                <div>
                    <h2>Subject Comparison Insights</h2>
                    <p>Review how you compare with your class across each course.</p>
                </div>
                <a class="back-link" href="student_dashboard.php"><i class="fas fa-arrow-left"></i> Back to dashboard</a>
            </div>
            <div class="container">
                <?php if ($timelineMismatch): ?>
                    <div class="card" style="border-left:4px solid #A6192E;">
                        <h3 class="section-title">Timeline not linked</h3>
                        <p>The selected academic term does not have comparison data yet. Update the timeline from your dashboard to review subject stats from another period.</p>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <h3 class="section-title">Comparison Snapshot</h3>
                        <?php if (!empty($subjectSummaries)): ?>
                            <table class="summary-table">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Your Marks</th>
                                        <th>Class High</th>
                                        <th>Class Avg</th>
                                        <th>Status</th>
                                        <th>Last Update</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subjectSummaries as $summary): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($summary['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($summary['marks_label']); ?> (<?php echo htmlspecialchars($summary['percentage_label']); ?>)</td>
                                            <td><?php echo $summary['class_high'] !== null ? number_format((float)$summary['class_high'], 1) : '—'; ?></td>
                                            <td><?php echo $summary['class_average'] !== null ? number_format((float)$summary['class_average'], 1) : '—'; ?></td>
                                            <td><span class="<?php echo htmlspecialchars($summary['status_class']); ?>"><?php echo htmlspecialchars($summary['status_label']); ?></span></td>
                                            <td><?php echo htmlspecialchars($summary['last_update']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">Comparison data will appear once ICA marks are recorded.</div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($subjectSummaries)): ?>
                        <div class="comparison-grid">
                            <?php foreach ($subjectSummaries as $summary): ?>
                                <div class="comparison-card">
                                    <div class="comparison-card-header">
                                        <h4><?php echo htmlspecialchars($summary['subject_name']); ?></h4>
                                        <span class="pill-muted">Faculty: <?php echo htmlspecialchars($summary['teachers']); ?></span>
                                    </div>
                                    <ul class="metric-list">
                                        <li><strong>Your score:</strong> <?php echo htmlspecialchars($summary['marks_label']); ?> (<?php echo htmlspecialchars($summary['percentage_label']); ?>)</li>
                                        <li><strong>Class high:</strong> <?php echo $summary['class_high'] !== null ? number_format((float)$summary['class_high'], 1) : '—'; ?></li>
                                        <li><strong>Class average:</strong> <?php echo $summary['class_average'] !== null ? number_format((float)$summary['class_average'], 1) : '—'; ?></li>
                                        <li><strong>Gap to topper:</strong> <?php echo $summary['gap_to_high'] !== null ? number_format((float)$summary['gap_to_high'], 1) : '—'; ?></li>
                                        <li><strong>Status:</strong> <span class="<?php echo htmlspecialchars($summary['status_class']); ?>" style="margin-left:6px;">&nbsp;<?php echo htmlspecialchars($summary['status_label']); ?></span></li>
                                    </ul>
                                    <?php
                                        $subjectData = null;
                                        foreach ($subjectChartPayload as $payload) {
                                            if ((int)$payload['subject_id'] === (int)$summary['subject_id']) {
                                                $subjectData = $payload;
                                                break;
                                            }
                                        }
                                    ?>
                                    <?php if ($subjectData !== null): ?>
                                        <div class="chart-wrapper">
                                            <canvas id="comparison-chart-<?php echo (int)$summary['subject_id']; ?>"
                                                data-chart="<?php echo htmlspecialchars(json_encode($subjectData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>"
                                                height="220"></canvas>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($subjectChartPayload)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canvases = document.querySelectorAll('canvas[id^="comparison-chart-"]');
            canvases.forEach(function (canvas) {
                const raw = canvas.getAttribute('data-chart');
                if (!raw) {
                    return;
                }
                let parsed;
                try {
                    parsed = JSON.parse(raw);
                } catch (error) {
                    return;
                }
                const labels = ['You', 'Class High', 'Class Avg'];
                const dataPoints = [
                    typeof parsed.student_total === 'number' ? parsed.student_total : 0,
                    typeof parsed.class_high === 'number' ? parsed.class_high : 0,
                    typeof parsed.class_average === 'number' ? parsed.class_average : 0
                ];
                const upperBound = Math.max.apply(null, dataPoints.concat(typeof parsed.total_possible === 'number' ? parsed.total_possible : 0));
                const context = canvas.getContext('2d');
                new Chart(context, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Marks',
                            data: dataPoints,
                            backgroundColor: ['#A6192E', '#2E7D32', '#1565C0']
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                suggestedMax: upperBound > 0 ? upperBound : 10
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
