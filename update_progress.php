<?php
session_start();
include 'db_connect.php';
require_once __DIR__ . '/includes/academic_context.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header('Location: login.php');
    exit;
}

$teacher_id = (int)$_SESSION['user_id'];
$teacherSchool = '';
$stmtTeacherSchool = mysqli_prepare($conn, "SELECT school FROM users WHERE id = ?");
if ($stmtTeacherSchool) {
    mysqli_stmt_bind_param($stmtTeacherSchool, "i", $teacher_id);
    mysqli_stmt_execute($stmtTeacherSchool);
    $resTeacherSchool = mysqli_stmt_get_result($stmtTeacherSchool);
    if ($resTeacherSchool && ($rowSchool = mysqli_fetch_assoc($resTeacherSchool))) {
        $teacherSchool = trim((string)($rowSchool['school'] ?? ''));
        mysqli_free_result($resTeacherSchool);
    }
    mysqli_stmt_close($stmtTeacherSchool);
}

$academicContext = resolveAcademicContext($conn, [
    'school_name' => $teacherSchool
]);
$activeTerm = $academicContext['active'] ?? null;
$activeTermId = $activeTerm && isset($activeTerm['id']) ? (int)$activeTerm['id'] : 0;
$error = '';
$success = '';
$teacherNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$teacherNameDisplay = $teacherNameRaw !== '' ? format_person_display($teacherNameRaw) : '';

function ensure_column_exists(mysqli $conn, string $table, string $column, string $definition): void {
    $table_safe = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    $column_safe = preg_replace('/[^A-Za-z0-9_]/', '', $column);
    if ($table_safe === '' || $column_safe === '') {
        return;
    }

    $exists_sql = sprintf(
        "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '%s' AND COLUMN_NAME = '%s'",
        mysqli_real_escape_string($conn, $table_safe),
        mysqli_real_escape_string($conn, $column_safe)
    );
    $exists_result = mysqli_query($conn, $exists_sql);
    if ($exists_result) {
        $row = mysqli_fetch_assoc($exists_result);
        mysqli_free_result($exists_result);
        if ((int)($row['cnt'] ?? 0) > 0) {
            return;
        }
    }

    $alter_sql = sprintf("ALTER TABLE `%s` ADD COLUMN `%s` %s", $table_safe, $column_safe, $definition);
    @mysqli_query($conn, $alter_sql);
}

ensure_column_exists($conn, 'syllabus_progress', 'extra_classes', 'DECIMAL(6,2) NOT NULL DEFAULT 0');
ensure_column_exists($conn, 'syllabus_progress', 'actual_theory_hours', 'DECIMAL(6,2) NOT NULL DEFAULT 0');
ensure_column_exists($conn, 'syllabus_progress', 'actual_practical_hours', 'DECIMAL(6,2) NOT NULL DEFAULT 0');
ensure_column_exists($conn, 'syllabus_progress', 'extra_theory_hours', 'DECIMAL(6,2) NOT NULL DEFAULT 0');
ensure_column_exists($conn, 'syllabus_progress', 'extra_practical_hours', 'DECIMAL(6,2) NOT NULL DEFAULT 0');
ensure_column_exists($conn, 'syllabus_progress', 'class_id', 'INT NOT NULL DEFAULT 0');
ensure_column_exists($conn, 'syllabus_progress', 'section_id', 'INT NOT NULL DEFAULT 0');
ensure_column_exists($conn, 'syllabus_progress', 'class_label', "VARCHAR(255) NOT NULL DEFAULT ''");

if (!function_exists('ensure_progress_unique_index')) {
    function ensure_progress_unique_index(mysqli $conn): void
    {
        $indexName = 'uniq_progress';
        $expected = ['teacher_id', 'subject', 'class_id', 'section_id', 'timeline'];
        $current = [];
        $hasIndex = false;
        $result = mysqli_query($conn, "SHOW INDEX FROM syllabus_progress WHERE Key_name = '" . $indexName . "'");
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $hasIndex = true;
                $seq = isset($row['Seq_in_index']) ? (int)$row['Seq_in_index'] : 0;
                $current[$seq] = $row['Column_name'] ?? '';
            }
            mysqli_free_result($result);
        }

        ksort($current);
        $ordered = array_values(array_filter($current, static fn($value) => $value !== ''));
        if ($ordered === $expected) {
            return;
        }

        if ($hasIndex) {
            @mysqli_query($conn, "ALTER TABLE syllabus_progress DROP INDEX `{$indexName}`");
        }
        @mysqli_query($conn, "ALTER TABLE syllabus_progress ADD UNIQUE KEY `{$indexName}` (`teacher_id`,`subject`,`class_id`,`section_id`,`timeline`)");
    }
}

ensure_progress_unique_index($conn);

if (!function_exists('build_assignment_key')) {
    function build_assignment_key(string $subjectName, int $classId, ?int $sectionId = null): string
    {
        $payload = json_encode([
            'subject' => $subjectName,
            'class_id' => $classId,
            'section_id' => $sectionId !== null ? (int)$sectionId : 0
        ], JSON_UNESCAPED_UNICODE);
        if ($payload === false) {
            return '';
        }
        $encoded = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        return $encoded;
    }
}

// Fetch assignment-specific subject records for the teacher
$assignments_list = [];
$assignment_meta = [];
$assignmentKeyBySubjectId = [];
$assignmentKeyBySubjectName = [];

$assignmentsSql = "
    SELECT
        tsa.id AS assignment_id,
        s.id AS subject_id,
        s.subject_name,
        s.total_planned_hours,
        COALESCE(sd.subject_type, 'regular') AS subject_type,
        COALESCE(sd.theory_hours, 0) AS theory_hours,
        COALESCE(sd.practical_hours, 0) AS practical_hours,
        COALESCE(sd.tutorial_label, 'Practical') AS practical_label,
        c.id AS class_id,
        c.class_name,
        c.semester,
        c.school,
        COALESCE(sec.section_name, '') AS section_name
    FROM teacher_subject_assignments tsa
    JOIN classes c ON c.id = tsa.class_id
    LEFT JOIN sections sec ON sec.id = tsa.section_id
    JOIN subjects s ON s.id = tsa.subject_id
    LEFT JOIN subject_details sd ON sd.subject_id = s.id
    WHERE tsa.teacher_id = ?
";
if ($activeTermId > 0) {
    $assignmentsSql .= " AND c.academic_term_id = ?";
}
$assignmentsSql .= " ORDER BY s.subject_name, c.class_name, sec.section_name";

$stmtAssignments = mysqli_prepare($conn, $assignmentsSql);
if ($stmtAssignments) {
    if ($activeTermId > 0) {
        mysqli_stmt_bind_param($stmtAssignments, "ii", $teacher_id, $activeTermId);
    } else {
        mysqli_stmt_bind_param($stmtAssignments, "i", $teacher_id);
    }
    mysqli_stmt_execute($stmtAssignments);
    $assignmentsResult = mysqli_stmt_get_result($stmtAssignments);
    if ($assignmentsResult) {
        while ($row = mysqli_fetch_assoc($assignmentsResult)) {
            $subjectId = isset($row['subject_id']) ? (int)$row['subject_id'] : 0;
            $subjectName = isset($row['subject_name']) ? trim((string)$row['subject_name']) : '';
            if ($subjectName === '') {
                continue;
            }

            $theoryTotal = (float)($row['theory_hours'] ?? 0);
            $practicalTotal = (float)($row['practical_hours'] ?? 0);
            $overallTotal = (float)($row['total_planned_hours'] ?? 0);
            if ($overallTotal <= 0 && ($theoryTotal > 0 || $practicalTotal > 0)) {
                $overallTotal = $theoryTotal + $practicalTotal;
            }

            $classId = isset($row['class_id']) ? (int)$row['class_id'] : 0;
            $sectionId = isset($row['section_id']) ? (int)$row['section_id'] : 0;
            $classLabel = format_class_label(
                $row['class_name'] ?? '',
                $row['section_name'] ?? '',
                $row['semester'] ?? '',
                $row['school'] ?? ''
            );
            if ($classLabel === '' && !empty($row['class_name'])) {
                $classLabel = format_subject_display($row['class_name']);
            }

            $assignmentKey = build_assignment_key($subjectName, $classId, $sectionId);

            $practicalLabelRaw = isset($row['practical_label']) ? trim((string)$row['practical_label']) : '';
            if ($practicalLabelRaw === '' && stripos($subjectName, 'tutorial') !== false) {
                $practicalLabelRaw = 'Tutorial';
            }
            $practicalLabel = $practicalLabelRaw !== '' ? $practicalLabelRaw : 'Practical';

            $assignmentRow = [
                'assignment_key' => $assignmentKey,
                'assignment_id' => isset($row['assignment_id']) ? (int)$row['assignment_id'] : 0,
                'subject_id' => $subjectId,
                'subject_name' => $subjectName,
                'subject_display' => format_subject_display($subjectName),
                'total_planned_hours' => $overallTotal,
                'theory_total_hours' => $theoryTotal,
                'practical_total_hours' => $practicalTotal,
                'subject_type' => strtolower($row['subject_type'] ?? 'regular'),
                'practical_label' => $practicalLabel,
                'class_id' => $classId,
                'section_id' => $sectionId,
                'class_label' => $classLabel,
                'school' => $row['school'] ?? '',
                'semester' => $row['semester'] ?? ''
            ];

            $assignments_list[] = $assignmentRow;
            $assignment_meta[$assignmentKey] = $assignmentRow;

            $classSectionKey = $classId . ':' . $sectionId;
            if ($subjectId > 0) {
                if (!isset($assignmentKeyBySubjectId[$subjectId])) {
                    $assignmentKeyBySubjectId[$subjectId] = [];
                }
                $assignmentKeyBySubjectId[$subjectId][$classSectionKey] = $assignmentKey;
            }
            $nameKey = strtolower($subjectName);
            if ($nameKey !== '') {
                if (!isset($assignmentKeyBySubjectName[$nameKey])) {
                    $assignmentKeyBySubjectName[$nameKey] = [];
                }
                $assignmentKeyBySubjectName[$nameKey][$classSectionKey] = $assignmentKey;
            }
        }
        mysqli_free_result($assignmentsResult);
    }
    mysqli_stmt_close($stmtAssignments);
}

usort($assignments_list, static function (array $a, array $b): int {
    $subjectCompare = strcasecmp($a['subject_display'] ?? $a['subject_name'], $b['subject_display'] ?? $b['subject_name']);
    if ($subjectCompare !== 0) {
        return $subjectCompare;
    }
    return strcasecmp($a['class_label'] ?? '', $b['class_label'] ?? '');
});

$subjects_list = $assignments_list;

// Determine current academic week to suggest a timeline
$current_week = 0;
$total_weeks = 0;
$instructional_weeks = 0;
$semester_start = null;
$semester_end = null;
$today = new DateTime();

$user_school_query = "SELECT school FROM users WHERE id = ?";
$stmt_dept = mysqli_prepare($conn, $user_school_query);
if ($stmt_dept) {
    mysqli_stmt_bind_param($stmt_dept, "i", $teacher_id);
    mysqli_stmt_execute($stmt_dept);
    $dept_result = mysqli_stmt_get_result($stmt_dept);
    if ($user_dept = mysqli_fetch_assoc($dept_result)) {
        $department_name = $user_dept['school'];
        $cal_query = "SELECT start_date, end_date FROM academic_calendar WHERE school_name = ? AND CURDATE() BETWEEN start_date AND end_date LIMIT 1";
        $stmt_cal = mysqli_prepare($conn, $cal_query);
        if ($stmt_cal) {
            mysqli_stmt_bind_param($stmt_cal, "s", $department_name);
            mysqli_stmt_execute($stmt_cal);
            $cal_result = mysqli_stmt_get_result($stmt_cal);
            if ($calendar = mysqli_fetch_assoc($cal_result)) {
                $semester_start = new DateTime($calendar['start_date']);
                $semester_end = new DateTime($calendar['end_date']);
                $total_days = $semester_start->diff($semester_end)->days + 1;
                $total_weeks = max(1, (int)ceil($total_days / 7));
                $instructional_weeks = min(15, $total_weeks);

                if ($today < $semester_start) {
                    $current_week = 1;
                } else {
                    $days_passed = $semester_start->diff($today)->days;
                    $current_week = min($total_weeks, (int)floor($days_passed / 7) + 1);
                }
            }
            mysqli_free_result($cal_result);
            mysqli_stmt_close($stmt_cal);
        }
    }
    mysqli_free_result($dept_result);
    mysqli_stmt_close($stmt_dept);
}

$instructional_weeks = $instructional_weeks ?: ($total_weeks > 0 ? min(15, $total_weeks) : 0);
$current_week_label = $current_week > 0 ? $current_week : 'N/A';

$week_start_for_current = null;
$week_end_for_current = null;
if ($semester_start && $total_weeks > 0 && $current_week > 0) {
    $week_start_for_current = clone $semester_start;
    $week_start_for_current->modify('+' . ($current_week - 1) * 7 . ' days');
    $week_end_for_current = clone $week_start_for_current;
    $week_end_for_current->modify('+6 days');
    if ($semester_end && $week_end_for_current > $semester_end) {
        $week_end_for_current = clone $semester_end;
    }
}

$plan_weeks = $instructional_weeks > 0 ? $instructional_weeks : $total_weeks;
if ($plan_weeks <= 0) {
    $plan_weeks = 15;
}

$subject_meta = [];
foreach ($subjects_list as $subject_row) {
    $assignmentKey = $subject_row['assignment_key'] ?? '';
    $name = trim((string)($subject_row['subject_name'] ?? ''));
    if ($assignmentKey === '' || $name === '') {
        continue;
    }

    $semester_total_hours = (float)($subject_row['total_planned_hours'] ?? 0);
    $semester_theory_hours_input = (float)($subject_row['theory_total_hours'] ?? 0);
    $semester_practical_hours_input = (float)($subject_row['practical_total_hours'] ?? 0);

    if ($semester_total_hours <= 0 && ($semester_theory_hours_input > 0 || $semester_practical_hours_input > 0)) {
        $semester_total_hours = $semester_theory_hours_input + $semester_practical_hours_input;
    }

    $effective_plan_weeks = $plan_weeks > 0 ? $plan_weeks : 15;

    $weekly_theory_hours = ($effective_plan_weeks > 0 && $semester_theory_hours_input > 0)
        ? $semester_theory_hours_input / $effective_plan_weeks
        : 0.0;
    $weekly_practical_hours = ($effective_plan_weeks > 0 && $semester_practical_hours_input > 0)
        ? $semester_practical_hours_input / $effective_plan_weeks
        : 0.0;

    $weekly_total_hours = $weekly_theory_hours + $weekly_practical_hours;
    if ($weekly_total_hours <= 0 && $effective_plan_weeks > 0 && $semester_total_hours > 0) {
        $weekly_total_hours = $semester_total_hours / $effective_plan_weeks;
    }

    $semester_theory_hours = $semester_theory_hours_input > 0
        ? $semester_theory_hours_input
        : ($weekly_theory_hours > 0 && $effective_plan_weeks > 0 ? $weekly_theory_hours * $effective_plan_weeks : 0);
    $semester_practical_hours = $semester_practical_hours_input > 0
        ? $semester_practical_hours_input
        : ($weekly_practical_hours > 0 && $effective_plan_weeks > 0 ? $weekly_practical_hours * $effective_plan_weeks : 0);
    if ($semester_total_hours <= 0 && $effective_plan_weeks > 0) {
        $semester_total_hours = $weekly_total_hours * $effective_plan_weeks;
    }

    $subject_meta[$assignmentKey] = [
        'subject_name' => $name,
        'class_id' => (int)($subject_row['class_id'] ?? 0),
        'section_id' => (int)($subject_row['section_id'] ?? 0),
        'class_label' => trim((string)($subject_row['class_label'] ?? '')),
        'practical_label' => isset($subject_row['practical_label']) && $subject_row['practical_label'] !== ''
            ? trim((string)$subject_row['practical_label'])
            : (stripos($name, 'tutorial') !== false ? 'Tutorial' : 'Practical'),
        'semester_total_hours' => $semester_total_hours,
        'theory_total_hours' => $semester_theory_hours,
        'practical_total_hours' => $semester_practical_hours,
        'weekly_total_hours' => $weekly_total_hours,
        'weekly_theory_hours' => $weekly_theory_hours,
        'weekly_practical_hours' => $weekly_practical_hours,
        'plan_weeks' => $effective_plan_weeks,
        'total_planned_hours' => $weekly_total_hours,
        'theory_hours' => $weekly_theory_hours,
        'practical_hours' => $weekly_practical_hours,
        'last_completed_week' => 0,
        'submitted_weeks' => [],
        'latest_week' => 0,
        'latest_updated_at' => null,
        'latest_planned_hours' => 0.0,
        'latest_actual_hours' => 0.0,
        'latest_actual_theory' => 0.0,
        'latest_actual_practical' => 0.0,
        'latest_extra_theory' => 0.0,
        'latest_extra_practical' => 0.0,
        'latest_extra_total' => 0.0,
        'max_actual_total' => 0.0,
        'max_actual_theory' => 0.0,
        'max_actual_practical' => 0.0,
        'max_extra_total' => 0.0,
        'max_extra_theory' => 0.0,
        'max_extra_practical' => 0.0
    ];
}

$progress_stmt_sql = "SELECT subject,
                  class_id,
                  section_id,
                  class_label,
                  timeline,
                  planned_hours,
                  actual_hours,
                  actual_theory_hours,
                  actual_practical_hours,
                  extra_theory_hours,
                  extra_practical_hours,
                  extra_classes,
                  completion_percentage,
                  topic,
                  updated_at
                       FROM syllabus_progress
                       WHERE teacher_id = ?
                       ORDER BY subject, class_id, section_id, updated_at ASC";
$progress_stmt = mysqli_prepare($conn, $progress_stmt_sql);
if ($progress_stmt instanceof mysqli_stmt) {
    mysqli_stmt_bind_param($progress_stmt, "i", $teacher_id);
    if (mysqli_stmt_execute($progress_stmt)) {
        $progress_result = mysqli_stmt_get_result($progress_stmt);
        while ($progress_row = mysqli_fetch_assoc($progress_result)) {
            $timeline = $progress_row['timeline'] ?? '';
            $subjectName = isset($progress_row['subject']) ? trim((string)$progress_row['subject']) : '';
            if ($subjectName === '') {
                continue;
            }

            $classId = isset($progress_row['class_id']) ? (int)$progress_row['class_id'] : 0;
            $sectionId = isset($progress_row['section_id']) ? (int)$progress_row['section_id'] : 0;
            $classSectionKey = $classId . ':' . $sectionId;
            $nameKey = strtolower($subjectName);
            $assignmentKey = null;

            if ($nameKey !== '' && isset($assignmentKeyBySubjectName[$nameKey])) {
                $lookup = $assignmentKeyBySubjectName[$nameKey];
                if ($classId > 0 && isset($lookup[$classSectionKey])) {
                    $assignmentKey = $lookup[$classSectionKey];
                } elseif ($classId > 0 && isset($lookup['0:0'])) {
                    $assignmentKey = $lookup['0:0'];
                } else {
                    $firstKey = reset($lookup);
                    if (is_string($firstKey)) {
                        $assignmentKey = $firstKey;
                    }
                }
            }

            if ($assignmentKey === null) {
                $assignmentKey = build_assignment_key($subjectName, $classId, $sectionId);
                if (!isset($subject_meta[$assignmentKey])) {
                    $subject_meta[$assignmentKey] = [
                        'subject_name' => $subjectName,
                        'class_id' => $classId,
                        'section_id' => $sectionId,
                        'class_label' => trim((string)($progress_row['class_label'] ?? '')),
                        'semester_total_hours' => 0,
                        'theory_total_hours' => 0,
                        'practical_total_hours' => 0,
                        'weekly_total_hours' => 0,
                        'weekly_theory_hours' => 0,
                        'weekly_practical_hours' => 0,
                        'plan_weeks' => $plan_weeks,
                        'total_planned_hours' => 0,
                        'theory_hours' => 0,
                        'practical_hours' => 0,
                        'last_completed_week' => 0,
                        'submitted_weeks' => [],
                        'latest_week' => 0,
                        'latest_updated_at' => null,
                        'latest_planned_hours' => 0.0,
                        'latest_actual_hours' => 0.0,
                        'latest_actual_theory' => 0.0,
                        'latest_actual_practical' => 0.0,
                        'latest_extra_theory' => 0.0,
                        'latest_extra_practical' => 0.0,
                        'latest_extra_total' => 0.0,
                        'max_actual_total' => 0.0,
                        'max_actual_theory' => 0.0,
                        'max_actual_practical' => 0.0,
                        'max_extra_total' => 0.0,
                        'max_extra_theory' => 0.0,
                        'max_extra_practical' => 0.0
                    ];
                }
            }

            $meta =& $subject_meta[$assignmentKey];
            if (empty($meta['class_id']) && $classId > 0) {
                $meta['class_id'] = $classId;
            }
            if (empty($meta['section_id']) && $sectionId > 0) {
                $meta['section_id'] = $sectionId;
            }
            if (($meta['class_label'] ?? '') === '' && !empty($progress_row['class_label'])) {
                $meta['class_label'] = $progress_row['class_label'];
            }

            $week_completed = 0;
            if (preg_match('/week_(\d+)/i', $timeline, $matches)) {
                $week_completed = (int)$matches[1];
                $is_holiday_entry = detect_holiday_week(
                    $timeline,
                    $progress_row['planned_hours'] ?? 0,
                    $progress_row['actual_hours'] ?? 0,
                    $progress_row['extra_classes'] ?? 0,
                    $progress_row['topic'] ?? '',
                    $meta['weekly_total_hours'] ?? 0,
                    $instructional_weeks
                );
                if ($week_completed > 0) {
                    $meta['submitted_weeks'][(string)$week_completed] = $is_holiday_entry ? 'holiday' : 'submitted';
                    if (!empty($progress_row['topic']) && stripos($progress_row['topic'], 'auto') !== false) {
                        $meta['submitted_weeks'][(string)$week_completed] = 'auto-holiday';
                    }
                }
            } elseif ($total_weeks > 0) {
                if ($timeline === 'mid1') {
                    $week_completed = max(1, (int)round($total_weeks / 3));
                } elseif ($timeline === 'mid2') {
                    $week_completed = max(1, (int)round(($total_weeks / 3) * 2));
                } elseif ($timeline === 'final') {
                    $week_completed = $total_weeks;
                }
            }

            if ($week_completed > ($meta['last_completed_week'] ?? 0)) {
                $meta['last_completed_week'] = $week_completed;
            }

            if ($week_completed > 0 && stripos($timeline, 'week_') === 0) {
                $should_update_latest = false;
                $current_latest_week = $meta['latest_week'] ?? 0;
                $current_latest_updated = $meta['latest_updated_at'];
                $row_updated_at = $progress_row['updated_at'] ?? null;

                if ($week_completed > $current_latest_week) {
                    $should_update_latest = true;
                } elseif ($week_completed === $current_latest_week && $row_updated_at !== null) {
                    if ($current_latest_updated === null || strcmp($row_updated_at, $current_latest_updated) >= 0) {
                        $should_update_latest = true;
                    }
                }

                if ($should_update_latest) {
                    $meta['latest_week'] = $week_completed;
                    $meta['latest_updated_at'] = $row_updated_at;
                    $meta['latest_planned_hours'] = (float)($progress_row['planned_hours'] ?? 0);
                    $meta['latest_actual_hours'] = (float)($progress_row['actual_hours'] ?? 0);
                    $meta['latest_actual_theory'] = (float)($progress_row['actual_theory_hours'] ?? 0);
                    $meta['latest_actual_practical'] = (float)($progress_row['actual_practical_hours'] ?? 0);
                    $meta['latest_extra_theory'] = (float)($progress_row['extra_theory_hours'] ?? 0);
                    $meta['latest_extra_practical'] = (float)($progress_row['extra_practical_hours'] ?? 0);
                    $meta['latest_extra_total'] = (float)($progress_row['extra_classes'] ?? 0);
                }

                $row_actual_total = (float)($progress_row['actual_hours'] ?? 0);
                $current_max_total = $meta['max_actual_total'] ?? 0;
                if ($row_actual_total >= $current_max_total) {
                    $meta['max_actual_total'] = $row_actual_total;
                    $meta['max_actual_theory'] = (float)($progress_row['actual_theory_hours'] ?? 0);
                    $meta['max_actual_practical'] = (float)($progress_row['actual_practical_hours'] ?? 0);
                }

                $row_extra_total = (float)($progress_row['extra_classes'] ?? 0);
                $current_max_extra = $meta['max_extra_total'] ?? 0;
                if ($row_extra_total >= $current_max_extra) {
                    $meta['max_extra_total'] = $row_extra_total;
                    $meta['max_extra_theory'] = (float)($progress_row['extra_theory_hours'] ?? 0);
                    $meta['max_extra_practical'] = (float)($progress_row['extra_practical_hours'] ?? 0);
                }
            }
        }
        mysqli_free_result($progress_result);
    } else {
        error_log('Failed to execute syllabus_progress lookup: ' . mysqli_error($conn));
    }
    mysqli_stmt_close($progress_stmt);
} else {
    error_log('Failed to prepare syllabus_progress lookup: ' . mysqli_error($conn));
}

foreach ($subject_meta as $assignmentKey => &$meta) {
    if (!empty($meta['submitted_weeks'])) {
        $week_numbers = array_map('intval', array_keys($meta['submitted_weeks']));
        $highest_week = max($week_numbers);
        if ($highest_week > ($meta['last_completed_week'] ?? 0)) {
            $meta['last_completed_week'] = $highest_week;
        }
    }

    if (($meta['max_actual_total'] ?? 0) <= 0 && ($meta['latest_actual_hours'] ?? 0) > 0) {
        $meta['max_actual_total'] = (float)$meta['latest_actual_hours'];
        $meta['max_actual_theory'] = (float)($meta['latest_actual_theory'] ?? 0);
        $meta['max_actual_practical'] = (float)($meta['latest_actual_practical'] ?? 0);
    }
    if (($meta['max_extra_total'] ?? 0) <= 0 && ($meta['latest_extra_total'] ?? 0) > 0) {
        $meta['max_extra_total'] = (float)$meta['latest_extra_total'];
        $meta['max_extra_theory'] = (float)($meta['latest_extra_theory'] ?? 0);
        $meta['max_extra_practical'] = (float)($meta['latest_extra_practical'] ?? 0);
    }
}
unset($meta);

$show_reminder = false;
$reminder_message = '';
if ($week_end_for_current && $today->format('Y-m-d') === $week_end_for_current->format('Y-m-d') && $current_week <= $total_weeks) {
    foreach ($subject_meta as $subject_details) {
        if ($subject_details['weekly_total_hours'] > 0 && $subject_details['last_completed_week'] < $current_week) {
            $show_reminder = true;
            $reminder_message = "Reminder: Today marks the end of Week {$current_week}. Please update your syllabus progress.";
            break;
        }
    }
}

$calendar_available = ($semester_start !== null && $semester_end !== null && $total_weeks > 0);

$calendar_script_data = [
    'startDate' => $semester_start ? $semester_start->format('Y-m-d') : null,
    'endDate' => $semester_end ? $semester_end->format('Y-m-d') : null,
    'totalWeeks' => $total_weeks,
    'currentWeek' => $current_week,
    'instructionalWeeks' => $instructional_weeks,
    'calendarAvailable' => $calendar_available,
    'showReminder' => $show_reminder
];

function detect_holiday_week($timeline, $stored_planned_hours, $actual_hours, $extra_classes, $topic, $weekly_total_hours, $instructional_weeks) {
    if (!preg_match('/week_(\d+)/i', $timeline, $matches)) {
        return false;
    }

    $week_number = (int)$matches[1];

    if ($instructional_weeks > 0 && $week_number > $instructional_weeks) {
        return true;
    }

    if ($topic && stripos($topic, 'holiday') !== false) {
        return true;
    }

    $weekly_total_hours = (float)$weekly_total_hours;
    if ($weekly_total_hours <= 0 || $instructional_weeks <= 0) {
        return false;
    }

    $actual_total = (float)$actual_hours + (float)$extra_classes;
    $effective_week = min($week_number, $instructional_weeks);
    $expected_for_week = $weekly_total_hours * $effective_week;
    $expected_for_previous = $weekly_total_hours * max(0, $effective_week - 1);
    $tolerance = max(0.1, $weekly_total_hours * 0.1);

    if (abs($actual_total - $expected_for_previous) <= $tolerance && ($actual_total + $tolerance) < $expected_for_week) {
        return true;
    }

    $stored_planned_hours = (float)$stored_planned_hours;
    $diff_current = abs($stored_planned_hours - $expected_for_week);
    $diff_previous = abs($stored_planned_hours - $expected_for_previous);
    if ($diff_previous <= $tolerance && $diff_current > $tolerance && abs($actual_total) < 0.01) {
        return true;
    }

    return false;
}

function format_progress_timeline($timeline, $instructional_limit = 0, $is_holiday = false) {
    if (preg_match('/week_(\d+)/i', $timeline, $matches)) {
        $week_number = (int)$matches[1];
        $label = 'Week ' . $week_number;
        if ($is_holiday || ($instructional_limit && $week_number > $instructional_limit)) {
            $label .= ' (Holiday)';
        }
        return $label;
    }
    return ucfirst($timeline);
}

function is_multiple_of_two($value): bool {
    $value = (float)$value;
    if (abs($value) < 0.0001) {
        return true;
    }
    $remainder = fmod($value, 2.0);
    $tolerance = 0.0001;
    return ($remainder < 0 ? abs($remainder + 2.0) : abs($remainder)) < $tolerance;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $assignmentKey = trim($_POST['subject'] ?? '');
    $timeline = trim($_POST['timeline'] ?? '');
    $actual_theory_week = isset($_POST['actual_hours_theory']) ? (float)$_POST['actual_hours_theory'] : 0.0;
    $actual_practical_week = isset($_POST['actual_hours_practical']) ? (float)$_POST['actual_hours_practical'] : 0.0;
    $extra_theory_week = isset($_POST['extra_classes_theory']) ? (float)$_POST['extra_classes_theory'] : 0.0;
    $extra_practical_week = isset($_POST['extra_classes_practical']) ? (float)$_POST['extra_classes_practical'] : 0.0;
    $is_holiday = !empty($_POST['holiday_week']);

    $numeric_fields = [&$actual_theory_week, &$actual_practical_week, &$extra_theory_week, &$extra_practical_week];
    foreach ($numeric_fields as &$value) {
        if ($value < 0) {
            $value = 0; // guard against accidental negative submissions
        }
    }
    unset($value);

    $modules_completed = 0;

    if (!$calendar_available) {
        $error = "Cannot update progress because an active academic calendar is not set for your school.";
    } elseif ($assignmentKey === '' || !isset($subject_meta[$assignmentKey])) {
        $error = "Invalid subject selected.";
    } elseif (!preg_match('/^week_(\d+)$/i', $timeline, $timeline_match)) {
        $error = "Invalid week selection submitted.";
    } else {
        $week_number = (int)$timeline_match[1];
        $plan = $subject_meta[$assignmentKey];
        $subject = trim((string)($plan['subject_name'] ?? ''));
        if ($subject === '') {
            $subject = $assignmentKey;
        }
        $class_id_meta = (int)($plan['class_id'] ?? 0);
        $section_id_meta = (int)($plan['section_id'] ?? 0);
        $class_label_meta = trim((string)($plan['class_label'] ?? ''));

        $weekly_total_hours = $plan['weekly_total_hours'] ?? 0;
        $weekly_theory_hours = $plan['weekly_theory_hours'] ?? 0;
        $weekly_practical_hours = $plan['weekly_practical_hours'] ?? 0;
        $semester_total_hours = $plan['semester_total_hours'] ?? 0;
        $semester_theory_hours = $plan['theory_total_hours'] ?? 0;
        $semester_practical_hours = $plan['practical_total_hours'] ?? 0;
        $plan_weeks_for_subject = $plan['plan_weeks'] ?? ($instructional_weeks > 0 ? $instructional_weeks : $total_weeks);
        $last_completed_week = $plan['last_completed_week'] ?? 0;

        if ($plan_weeks_for_subject <= 0) {
            $plan_weeks_for_subject = $instructional_weeks > 0 ? $instructional_weeks : $total_weeks;
        }
        if ($plan_weeks_for_subject <= 0) {
            $plan_weeks_for_subject = $total_weeks > 0 ? $total_weeks : 15;
        }

        if ($weekly_theory_hours <= 0 && $plan_weeks_for_subject > 0 && $semester_theory_hours > 0) {
            $weekly_theory_hours = $semester_theory_hours / $plan_weeks_for_subject;
        }
        if ($weekly_practical_hours <= 0 && $plan_weeks_for_subject > 0 && $semester_practical_hours > 0) {
            $weekly_practical_hours = $semester_practical_hours / $plan_weeks_for_subject;
        }
        if ($weekly_total_hours <= 0) {
            $weekly_total_hours = $weekly_theory_hours + $weekly_practical_hours;
        }
        if ($weekly_total_hours <= 0 && $plan_weeks_for_subject > 0 && $semester_total_hours > 0) {
            $weekly_total_hours = $semester_total_hours / $plan_weeks_for_subject;
        }

        if ($weekly_total_hours <= 0 || $semester_total_hours <= 0) {
            $error = "Cannot update progress for a subject with 0 planned hours. Please contact the administrator.";
        } elseif ($week_number !== $last_completed_week + 1) {
            $next_expected = $last_completed_week + 1;
            $error = "Please submit progress sequentially. Update Week {$next_expected} next.";
        } elseif ($total_weeks > 0 && $week_number > $total_weeks) {
            $error = "Selected week exceeds the academic calendar duration.";
        } else {
            $previous_actual_theory = (float)($plan['max_actual_theory'] ?? 0.0);
            $previous_actual_practical = (float)($plan['max_actual_practical'] ?? 0.0);

            if ($is_holiday) {
                $actual_theory_week = 0.0;
                $actual_practical_week = 0.0;
                $extra_theory_week = 0.0;
                $extra_practical_week = 0.0;
            }

            if (!$is_holiday) {
                if (!is_multiple_of_two($actual_practical_week)) {
                    $error = "Practical hours must be entered in multiples of 2.";
                } elseif (!is_multiple_of_two($extra_practical_week)) {
                    $error = "Extra practical hours must be entered in multiples of 2.";
                }
            }

            if (empty($error)) {
                $effective_week = ($instructional_weeks > 0) ? min($week_number, $instructional_weeks) : $week_number;
                $holiday_marker = null;
                $holiday_weeks_before = 0;
                foreach ($plan['submitted_weeks'] as $submitted_week => $status) {
                    $submitted_week_int = (int)$submitted_week;
                    if ($submitted_week_int < $week_number && stripos((string)$status, 'holiday') !== false) {
                        $holiday_weeks_before++;
                    }
                }

                if ($instructional_weeks > 0) {
                    $effective_week = max(0, $effective_week - $holiday_weeks_before);
                }

                if ($is_holiday) {
                    if ($instructional_weeks > 0 && $week_number <= $instructional_weeks) {
                        $effective_week = max(0, $effective_week - 1);
                    } elseif ($week_number > $instructional_weeks) {
                        $holiday_marker = 'auto';
                    }
                }
                $effective_week = max(0, $effective_week);

                $planned_theory_cumulative = $weekly_theory_hours * $effective_week;
                $planned_practical_cumulative = $weekly_practical_hours * $effective_week;
                if ($semester_theory_hours > 0) {
                    $planned_theory_cumulative = min($planned_theory_cumulative, $semester_theory_hours);
                }
                if ($semester_practical_hours > 0) {
                    $planned_practical_cumulative = min($planned_practical_cumulative, $semester_practical_hours);
                }
                $cumulative_planned_hours = $planned_theory_cumulative + $planned_practical_cumulative;
                if ($semester_total_hours > 0) {
                    $cumulative_planned_hours = min($cumulative_planned_hours, $semester_total_hours);
                }

                $planned_hours_per_week = $weekly_total_hours;

                $actual_theory_cumulative = $previous_actual_theory + $actual_theory_week;
                $actual_practical_cumulative = $previous_actual_practical + $actual_practical_week;

                $actual_base_total = $actual_theory_cumulative + $actual_practical_cumulative;
                $extra_classes_total = $extra_theory_week + $extra_practical_week;
                $actual_total_hours = $actual_base_total + $extra_classes_total;

                if ($semester_theory_hours > 0 && $actual_theory_cumulative > $semester_theory_hours) {
                    $actual_theory_cumulative = $semester_theory_hours;
                }
                if ($semester_practical_hours > 0 && $actual_practical_cumulative > $semester_practical_hours) {
                    $actual_practical_cumulative = $semester_practical_hours;
                }
                if ($semester_total_hours > 0 && $actual_total_hours > $semester_total_hours) {
                    $actual_total_hours = $semester_total_hours;
                }

                $completion_percentage = ($cumulative_planned_hours > 0)
                    ? round(($actual_total_hours / $cumulative_planned_hours) * 100)
                    : 0;
                $completion_percentage = max(0, min(100, $completion_percentage));

                if ($is_holiday) {
                    $holiday_marker = $holiday_marker ?: 'manual';
                }

                $topic_label = $is_holiday ? "Week {$week_number} (Holiday)" : "Week {$week_number}";

                    $upsert_sql = "INSERT INTO syllabus_progress (teacher_id, subject, class_id, section_id, class_label, topic, timeline, modules_completed, planned_hours, actual_hours, actual_theory_hours, actual_practical_hours, extra_theory_hours, extra_practical_hours, extra_classes, completion_percentage, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                      ON DUPLICATE KEY UPDATE 
                        class_id = VALUES(class_id),
                        section_id = VALUES(section_id),
                        class_label = VALUES(class_label),
                      topic = VALUES(topic),
                      modules_completed = VALUES(modules_completed),
                      planned_hours = VALUES(planned_hours),
                      actual_hours = VALUES(actual_hours),
                      actual_theory_hours = VALUES(actual_theory_hours),
                      actual_practical_hours = VALUES(actual_practical_hours),
                      extra_theory_hours = VALUES(extra_theory_hours),
                      extra_practical_hours = VALUES(extra_practical_hours),
                      extra_classes = VALUES(extra_classes),
                      completion_percentage = VALUES(completion_percentage),
                      updated_at = NOW()";

                $stmt_upsert = mysqli_prepare($conn, $upsert_sql);
                if ($stmt_upsert) {
                    if ($holiday_marker === 'auto') {
                        $topic_label = "Week {$week_number} (Holiday - Auto)";
                    }

                    mysqli_stmt_bind_param(
                        $stmt_upsert,
                        "isiisssidddddddd",
                        $teacher_id,
                        $subject,
                        $class_id_meta,
                        $section_id_meta,
                        $class_label_meta,
                        $topic_label,
                        $timeline,
                        $modules_completed,
                        $cumulative_planned_hours,
                        $actual_total_hours,
                        $actual_theory_cumulative,
                        $actual_practical_cumulative,
                        $extra_theory_week,
                        $extra_practical_week,
                        $extra_classes_total,
                        $completion_percentage
                    );

                    if (!mysqli_stmt_execute($stmt_upsert)) {
                        $error = "Failed to save progress: " . mysqli_stmt_error($stmt_upsert);
                    }
                    mysqli_stmt_close($stmt_upsert);
                } else {
                    $error = "Failed to prepare save statement: " . mysqli_error($conn);
                }

                if (empty($error)) {
                    $milestones = [];
                    if ($instructional_weeks >= 3) {
                        $mid1_week = max(1, (int)round($instructional_weeks / 3));
                        if ($mid1_week < $instructional_weeks) {
                            $milestones['mid1'] = $mid1_week;
                        }

                        $mid2_week = max($mid1_week + 1, (int)round(($instructional_weeks / 3) * 2));
                        if ($mid2_week < $instructional_weeks) {
                            $milestones['mid2'] = $mid2_week;
                        }
                    }
                    if ($instructional_weeks > 0) {
                        $milestones['final'] = $instructional_weeks;
                    }

                    $checkpoint_sql = $upsert_sql; // reuse the same ON DUPLICATE KEY template
                    foreach ($milestones as $label => $milestone_week) {
                        if ($milestone_week <= 0 || $week_number < $milestone_week) {
                            continue;
                        }

                        $milestone_planned_hours = $planned_hours_per_week * $milestone_week;
                        if ($semester_total_hours > 0) {
                            $milestone_planned_hours = min($milestone_planned_hours, $semester_total_hours);
                        }

                        $milestone_completion = $milestone_planned_hours > 0
                            ? round(($actual_total_hours / $milestone_planned_hours) * 100)
                            : 0;
                        $milestone_completion = max(0, min(100, $milestone_completion));

                        $milestone_topic = ucfirst($label) . " Checkpoint (Week {$milestone_week})";
                        $stmt_checkpoint = mysqli_prepare($conn, $checkpoint_sql);
                        if ($stmt_checkpoint) {
                            mysqli_stmt_bind_param(
                                $stmt_checkpoint,
                                "isiisssidddddddd",
                                $teacher_id,
                                $subject,
                                $class_id_meta,
                                $section_id_meta,
                                $class_label_meta,
                                $milestone_topic,
                                $label,
                                $modules_completed,
                                $milestone_planned_hours,
                                $actual_total_hours,
                                $actual_theory_cumulative,
                                $actual_practical_cumulative,
                                $extra_theory_week,
                                $extra_practical_week,
                                $extra_classes_total,
                                $milestone_completion
                            );

                            if (!mysqli_stmt_execute($stmt_checkpoint) && empty($error)) {
                                $error = "Failed to update milestone '{$label}'.";
                            }
                            mysqli_stmt_close($stmt_checkpoint);
                        }
                    }
                }
            }

            if (empty($error)) {
                $subject_display = $subject;
                if ($class_label_meta !== '') {
                    $subject_display .= " ({$class_label_meta})";
                }
                $success = "Progress for '{$subject_display}' updated successfully!";
                header('Location: update_progress.php');
                exit;
            }
        }
    }
}

// Fetch recent progress snapshots for the sidebar table
$recent_query = "SELECT sp.subject,
                        sp.class_id,
                        sp.section_id,
                        sp.class_label,
                        sp.timeline,
                        sp.topic,
                        sp.modules_completed,
                        sp.planned_hours,
                        sp.actual_hours,
                        sp.actual_theory_hours,
                        sp.actual_practical_hours,
                        sp.extra_theory_hours,
                        sp.extra_practical_hours,
                        sp.extra_classes,
                        sp.completion_percentage,
                        sp.updated_at
                   FROM syllabus_progress sp
                   INNER JOIN (
                       SELECT subject, class_id, section_id, timeline, MAX(updated_at) AS latest
                       FROM syllabus_progress
                       WHERE teacher_id = ? AND timeline LIKE 'week_%'
                       GROUP BY subject, class_id, section_id, timeline
                   ) latest_progress
                   ON sp.subject = latest_progress.subject
                  AND sp.class_id = latest_progress.class_id
                  AND sp.section_id = latest_progress.section_id
                  AND sp.timeline = latest_progress.timeline
                  AND sp.updated_at = latest_progress.latest
                  WHERE sp.teacher_id = ? AND sp.timeline LIKE 'week_%'
                  ORDER BY sp.updated_at DESC
                  LIMIT 5";
$stmt_recent = mysqli_prepare($conn, $recent_query);
mysqli_stmt_bind_param($stmt_recent, "ii", $teacher_id, $teacher_id);
mysqli_stmt_execute($stmt_recent);
$recent_result = mysqli_stmt_get_result($stmt_recent);
$recent_updates = [];
if ($recent_result) {
    while ($row = mysqli_fetch_assoc($recent_result)) {
        $subject_name = $row['subject'] ?? '';
        $class_id_row = isset($row['class_id']) ? (int)$row['class_id'] : 0;
        $section_id_row = isset($row['section_id']) ? (int)$row['section_id'] : 0;
        $assignment_key_row = build_assignment_key($subject_name, $class_id_row, $section_id_row);

        $meta_row = $subject_meta[$assignment_key_row] ?? null;
        if ($meta_row === null && $subject_name !== '') {
            $name_key = strtolower($subject_name);
            if (isset($assignmentKeyBySubjectName[$name_key])) {
                $lookup = $assignmentKeyBySubjectName[$name_key];
                $class_section_key = $class_id_row . ':' . $section_id_row;
                if (isset($lookup[$class_section_key])) {
                    $assignment_key_row = $lookup[$class_section_key];
                    $meta_row = $subject_meta[$assignment_key_row] ?? null;
                } elseif (isset($lookup['0:0'])) {
                    $assignment_key_row = $lookup['0:0'];
                    $meta_row = $subject_meta[$assignment_key_row] ?? null;
                }
            }
        }

        $weekly_plan_for_subject = $meta_row['weekly_total_hours'] ?? 0;
        $class_label_display = $meta_row['class_label'] ?? ($row['class_label'] ?? '');
        $practical_label_display = isset($meta_row['practical_label']) && $meta_row['practical_label'] !== ''
            ? $meta_row['practical_label']
            : (stripos($subject_name, 'tutorial') !== false ? 'Tutorial' : 'Practical');
        $row['practical_label'] = $practical_label_display;
        $row['display_subject'] = $class_label_display !== ''
            ? $subject_name . ' (' . $class_label_display . ')'
            : $subject_name;
        $row['display_class'] = $class_label_display;
        $row['planned_hours'] = (float)($row['planned_hours'] ?? 0);
        $row['actual_hours'] = (float)($row['actual_hours'] ?? 0);
        $row['actual_theory_hours'] = (float)($row['actual_theory_hours'] ?? 0);
        $row['actual_practical_hours'] = (float)($row['actual_practical_hours'] ?? 0);
        $row['extra_theory_hours'] = (float)($row['extra_theory_hours'] ?? 0);
        $row['extra_practical_hours'] = (float)($row['extra_practical_hours'] ?? 0);
        $row['extra_classes'] = (float)($row['extra_classes'] ?? 0);
        $row['is_holiday'] = detect_holiday_week(
            $row['timeline'],
            $row['planned_hours'],
            $row['actual_hours'],
            $row['extra_classes'],
            $row['topic'] ?? '',
            $weekly_plan_for_subject,
            $instructional_weeks
        );
        $row['is_auto_holiday'] = !empty($row['topic']) && stripos($row['topic'], 'auto') !== false;
        $recent_updates[] = $row;
    }
    mysqli_free_result($recent_result);
}
mysqli_stmt_close($stmt_recent);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Progress - ICA Tracker</title>
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
            <a href="update_progress.php" class="active"><i class="fas fa-chart-line"></i> <span>Update Progress</span></a>
            <a href="create_ica_components.php"><i class="fas fa-cogs"></i> <span>ICA Components</span></a>
            <a href="manage_ica_marks.php"><i class="fas fa-book"></i> <span>Manage ICA Marks</span></a>
            <a href="assignments.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a>
            <a href="view_alerts.php"><i class="fas fa-bell"></i> <span>View Alerts</span></a>
            <a href="view_reports.php"><i class="fas fa-file-alt"></i> <span>View Reports</span></a>
            <a href="timetable.php"><i class="fas fa-calendar-alt"></i> <span>Timetable</span></a>
            <a href="edit_profile.php"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
        <div class="main-content">
            <div class="header">
                <h2>Welcome, <?php echo htmlspecialchars($teacherNameDisplay !== '' ? $teacherNameDisplay : $teacherNameRaw); ?>!</h2>
            </div>
            <div class="container">
                <div class="card">
                    <div class="card-header"><h5>Update Syllabus Progress</h5></div>
                    <div class="card-body">
                        <?php if (!$calendar_available): ?>
                            <p style="color: #A6192E; font-weight: bold;">No active academic calendar found for your school. Please contact the administrator before recording progress.</p>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <div style="background:#fdecea; border:1px solid #f5c6cb; color:#a94442; padding:10px 14px; border-radius:8px; margin-bottom:15px;">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($subjects_list)): ?>
                            <p style="color: #555;">No subjects assigned to you yet.</p>
                        <?php else: ?>
                            <?php if ($show_reminder): ?>
                                <div id="reminderBanner" class="reminder-banner" style="background: #fff4e5; border: 1px solid #f0b37a; color: #a15c12; padding: 10px 14px; border-radius: 8px; margin-bottom: 15px; display: none;">
                                    <?php echo htmlspecialchars($reminder_message); ?>
                                </div>
                            <?php endif; ?>

                            <div class="form-group">
                                <label>Subject</label>
                                <select id="subject" <?php echo !$calendar_available ? 'disabled' : ''; ?> required>
                                    <option value="">Select a Subject</option>
                                    <?php
                                    $format_hours_label = static function ($value) {
                                        if (!is_numeric($value)) {
                                            return '0';
                                        }
                                        $value = (float)$value;
                                        if (abs($value - round($value)) < 0.01) {
                                            return (string)round($value);
                                        }
                                        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
                                    };

                                    foreach ($subjects_list as $subject_row) {
                                        $assignment_value = $subject_row['assignment_key'] ?? '';
                                        $name = $subject_row['subject_name'] ?? '';
                                        if ($assignment_value === '' || $name === '') {
                                            continue;
                                        }

                                        $meta = $subject_meta[$assignment_value] ?? [];

                                        $semester_total = $meta['semester_total_hours'] ?? ($subject_row['total_planned_hours'] ?? 0);
                                        $semester_theory = $meta['theory_total_hours'] ?? ($subject_row['theory_total_hours'] ?? 0);
                                        $semester_practical = $meta['practical_total_hours'] ?? ($subject_row['practical_total_hours'] ?? 0);
                                        if ($semester_total <= 0 && ($semester_theory > 0 || $semester_practical > 0)) {
                                            $semester_total = $semester_theory + $semester_practical;
                                        }

                                        $weekly_total = $meta['weekly_total_hours'] ?? 0;
                                        $weekly_theory = $meta['weekly_theory_hours'] ?? 0;
                                        $weekly_practical = $meta['weekly_practical_hours'] ?? 0;
                                        $plan_weeks_subject = $meta['plan_weeks'] ?? ($plan_weeks ?? 0);

                                        if ($weekly_total <= 0 && $plan_weeks_subject > 0 && $semester_total > 0) {
                                            $weekly_total = $semester_total / $plan_weeks_subject;
                                        }
                                        if ($weekly_theory <= 0 && $plan_weeks_subject > 0 && $semester_theory > 0) {
                                            $weekly_theory = $semester_theory / $plan_weeks_subject;
                                        }
                                        if ($weekly_practical <= 0 && $plan_weeks_subject > 0 && $semester_practical > 0) {
                                            $weekly_practical = $semester_practical / $plan_weeks_subject;
                                        }

                                        $last_week = $meta['last_completed_week'] ?? 0;
                                        $week_status = $meta['submitted_weeks'] ?? [];
                                        $encoded_status = htmlspecialchars(json_encode($week_status), ENT_QUOTES, 'UTF-8');

                                        $display_name_parts = [$name];
                                        $class_label_display = $meta['class_label'] ?? ($subject_row['class_label'] ?? '');
                                        if ($class_label_display !== '') {
                                            $display_name_parts[] = '(' . $class_label_display . ')';
                                        }

                                        $semester_parts = [];
                                        if ($semester_theory > 0) {
                                            $semester_parts[] = $format_hours_label($semester_theory) . 'T';
                                        }
                                        if ($semester_practical > 0) {
                                            $semester_parts[] = $format_hours_label($semester_practical) . 'P';
                                        }
                                        $semester_caption = ($semester_total > 0 ? $format_hours_label($semester_total) : '0') . ' hrs';
                                        if (!empty($semester_parts)) {
                                            $semester_caption .= ' (' . implode(' + ', $semester_parts) . ')';
                                        }

                                        $weekly_caption = '';
                                        if ($weekly_total > 0) {
                                            $weekly_parts = [];
                                            if ($weekly_theory > 0) {
                                                $weekly_parts[] = $format_hours_label($weekly_theory) . 'T';
                                            }
                                            if ($weekly_practical > 0) {
                                                $weekly_parts[] = $format_hours_label($weekly_practical) . 'P';
                                            }
                                            $weekly_caption = $format_hours_label($weekly_total) . ' hrs';
                                            if (!empty($weekly_parts)) {
                                                $weekly_caption .= ' (' . implode(' + ', $weekly_parts) . ')';
                                            }
                                        }
                                        $option_label = implode(' ', array_filter($display_name_parts));
                                    ?>
                                        <option value="<?php echo htmlspecialchars($assignment_value); ?>"
                                            data-class-id="<?php echo (int)($meta['class_id'] ?? ($subject_row['class_id'] ?? 0)); ?>"
                                            data-section-id="<?php echo (int)($meta['section_id'] ?? ($subject_row['section_id'] ?? 0)); ?>"
                                            data-subject-name="<?php echo htmlspecialchars($name); ?>"
                                            data-class-label="<?php echo htmlspecialchars($class_label_display, ENT_QUOTES); ?>"
                                            data-semester-total="<?php echo htmlspecialchars($semester_total, ENT_QUOTES); ?>"
                                            data-semester-theory="<?php echo htmlspecialchars($semester_theory, ENT_QUOTES); ?>"
                                            data-semester-practical="<?php echo htmlspecialchars($semester_practical, ENT_QUOTES); ?>"
                                            data-weekly-total="<?php echo htmlspecialchars($weekly_total, ENT_QUOTES); ?>"
                                            data-weekly-theory="<?php echo htmlspecialchars($weekly_theory, ENT_QUOTES); ?>"
                                            data-weekly-practical="<?php echo htmlspecialchars($weekly_practical, ENT_QUOTES); ?>"
                                            data-practical-label="<?php echo htmlspecialchars($meta['practical_label'] ?? ($subject_row['practical_label'] ?? 'Practical'), ENT_QUOTES); ?>"
                                            data-plan-weeks="<?php echo (int)$plan_weeks_subject; ?>"
                                            data-last-week="<?php echo (int)$last_week; ?>"
                                            data-last-planned="<?php echo htmlspecialchars($meta['latest_planned_hours'] ?? 0, ENT_QUOTES); ?>"
                                            data-last-actual-total="<?php echo htmlspecialchars($meta['max_actual_total'] ?? 0, ENT_QUOTES); ?>"
                                            data-last-actual-theory="<?php echo htmlspecialchars($meta['max_actual_theory'] ?? 0, ENT_QUOTES); ?>"
                                            data-last-actual-practical="<?php echo htmlspecialchars($meta['max_actual_practical'] ?? 0, ENT_QUOTES); ?>"
                                            data-last-extra-total="<?php echo htmlspecialchars($meta['max_extra_total'] ?? 0, ENT_QUOTES); ?>"
                                            data-last-extra-theory="<?php echo htmlspecialchars($meta['max_extra_theory'] ?? 0, ENT_QUOTES); ?>"
                                            data-last-extra-practical="<?php echo htmlspecialchars($meta['max_extra_practical'] ?? 0, ENT_QUOTES); ?>"
                                            data-week-status="<?php echo $encoded_status; ?>">
                                            <?php echo htmlspecialchars($option_label); ?> (Semester Plan: <?php echo htmlspecialchars($semester_caption); ?><?php if ($weekly_caption !== '') { echo ' | Weekly Target: ' . htmlspecialchars($weekly_caption); } ?>)
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            
                            <div id="form-container" style="display:none;">
                                <form method="POST" id="progressForm">
                                    <input type="hidden" name="subject" id="form_subject">
                                    <input type="hidden" name="holiday_week" value="0">
                                    <div class="form-group">
                                        <label>Timeline <span style="font-weight: normal; color: #666;">(Current Week: <?php echo htmlspecialchars($current_week_label); ?>)</span></label>
                                        <select name="timeline" id="timeline" required <?php echo !$calendar_available ? 'disabled' : ''; ?>>
                                            <option value="">Select Week</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label style="display:flex; align-items:center; gap:8px; font-weight:500;">
                                            <input type="checkbox" name="holiday_week" id="holiday_week" value="1">
                                            Mark this week as a holiday / no classes
                                        </label>
                                        <small style="display:block; color:#666;">When marked as holiday, cumulative planned hours remain unchanged and module / hour fields will be disabled.</small>
                                    </div>
                                    <div id="progress_summary" class="progress-summary" style="background:#f7f7f7;border:1px solid #e0e0e0;border-radius:8px;padding:12px 14px;margin-bottom:16px;display:none;">
                                        <p style="margin:0 0 6px 0;"><strong>Total Classes Planned Till This Semester:</strong> <span id="semester_total_display">0</span> (<span id="semester_theory_display">Theory 0</span> + <span id="semester_practical_display">Practical 0</span>)</p>
                                        <p style="margin:0 0 6px 0;"><strong>Classes Should Be Completed Till Now:</strong> <span id="planned_cumulative_display">0</span> (<span id="planned_theory_display">Theory 0</span> + <span id="planned_practical_display">Practical 0</span>)</p>
                                        <p style="margin:0 0 6px 0;"><strong>Classes Completed Till Now:</strong> <span id="actual_cumulative_display">0</span> (<span id="actual_theory_display">Theory 0</span> + <span id="actual_practical_display">Practical 0</span>)</p>
                                        <p style="margin:0 0 6px 0;"><strong>Extra Classes Logged Till Now:</strong> <span id="extra_cumulative_display">0</span> (<span id="extra_cumulative_theory_display">Theory 0</span> + <span id="extra_cumulative_practical_display">Practical 0</span>)</p>
                                        <p style="margin:0 0 6px 0;"><strong>Total Classes Delivered Till Now (Actual + Extra):</strong> <span id="combined_cumulative_display">0</span> (<span id="combined_theory_display">Theory 0</span> + <span id="combined_practical_display">Practical 0</span>)</p>
                                        <p style="margin:0 0 6px 0;"><strong>Actual Hours This Week:</strong> <span id="actual_weekly_display">0</span> (<span id="actual_weekly_theory_display">Theory 0</span> + <span id="actual_weekly_practical_display">Practical 0</span>)</p>
                                        <p style="margin:0;"><strong>Extra Classes Logged This Week:</strong> <span id="extra_weekly_total_display">0</span> (<span id="extra_theory_display">Theory 0</span> + <span id="extra_practical_display">Practical 0</span>)</p>
                                    </div>
                                    <div class="form-group"><label id="actual_theory_label">Actual Theory Hours Conducted This Week</label><input type="number" name="actual_hours_theory" id="actual_hours_theory" min="0" step="0.25" required></div>
                                    <div class="form-group" id="actual_practical_group"><label id="actual_practical_label">Actual Practical Hours Conducted This Week</label><input type="number" name="actual_hours_practical" id="actual_hours_practical" min="0" step="0.25" required></div>
                                    <div class="form-group"><label id="extra_theory_label">Extra Theory Classes Conducted This Week</label><input type="number" name="extra_classes_theory" id="extra_classes_theory" min="0" step="0.25"></div>
                                    <div class="form-group" id="extra_practical_group"><label id="extra_practical_label">Extra Practical Classes Conducted This Week</label><input type="number" name="extra_classes_practical" id="extra_classes_practical" min="0" step="0.25"></div>
                                    <div class="form-group"><label>Percentage Completion</label><input type="text" id="completion_percentage_display" readonly></div>
                                    <button type="submit" <?php echo !$calendar_available ? 'disabled' : ''; ?>>Update Progress</button>
                                </form>
                            </div>
                        <?php endif; ?>
                        <div id="warning-message" style="display: none; color: #A6192E; font-weight: bold; text-align: center;">
                            <p>The selected subject has 0 planned hours. Please contact the administrator to update this subject before you can report progress.</p>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h5>Recent Updates</h5></div>
                    <div class="card-body">
                        <table>
                            <thead><tr><th>Subject</th><th>Class</th><th>Timeline</th><th>Planned Till Week</th><th>Actual Till Now</th><th>Extra Classes</th><th>Completion (%)</th><th>Updated</th></tr></thead>
                            <tbody>
                            <?php if (empty($recent_updates)) { ?>
                                <tr>
                                    <td colspan="8" style="text-align:center; color:#666;">No weekly submissions yet.</td>
                                </tr>
                            <?php } else { ?>
                                <?php
                                $format_recent_hours = static function ($value) {
                                    if (!is_numeric($value)) {
                                        return '0';
                                    }
                                    $value = (float)$value;
                                    if (abs($value - round($value)) < 0.01) {
                                        return (string)round($value);
                                    }
                                    return number_format($value, 2);
                                };
                                foreach ($recent_updates as $row) { ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['display_subject'] ?? $row['subject']); ?></td>
                                        <td><?php echo htmlspecialchars($row['display_class'] ?? ''); ?></td>
                                        <td>
                                            <?php
                                                $timeline_label = format_progress_timeline($row['timeline'], $instructional_weeks, !empty($row['is_holiday']));
                                                if (!empty($row['is_auto_holiday'])) {
                                                    $timeline_label .= ' — Auto Holiday';
                                                }
                                                echo htmlspecialchars($timeline_label);
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(number_format((float)$row['planned_hours'], 2)); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($format_recent_hours($row['actual_hours'] ?? 0)); ?>
                                            <br><small>T: <?php echo htmlspecialchars($format_recent_hours($row['actual_theory_hours'] ?? 0)); ?> | <?php echo htmlspecialchars($row['practical_label']); ?>: <?php echo htmlspecialchars($format_recent_hours($row['actual_practical_hours'] ?? 0)); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($format_recent_hours($row['extra_classes'] ?? 0)); ?>
                                            <br><small>T: <?php echo htmlspecialchars($format_recent_hours($row['extra_theory_hours'] ?? 0)); ?> | <?php echo htmlspecialchars($row['practical_label']); ?>: <?php echo htmlspecialchars($format_recent_hours($row['extra_practical_hours'] ?? 0)); ?></small>
                                        </td>
                                        <td><?php echo round($row['completion_percentage']); ?>%</td>
                                        <td><?php echo htmlspecialchars($row['updated_at']); ?></td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                            </tbody>
                        </table>
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

        document.addEventListener('DOMContentLoaded', function() {
            const calendarConfig = <?php echo json_encode($calendar_script_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
            const reminderMessage = <?php echo json_encode($reminder_message, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
            const subjectSelect = document.getElementById('subject');
            const timelineSelect = document.getElementById('timeline');
            const actualTheoryInput = document.getElementById('actual_hours_theory');
            const actualPracticalInput = document.getElementById('actual_hours_practical');
            const extraTheoryInput = document.getElementById('extra_classes_theory');
            const extraPracticalInput = document.getElementById('extra_classes_practical');
            const actualPracticalGroup = document.getElementById('actual_practical_group');
            const extraPracticalGroup = document.getElementById('extra_practical_group');
            const actualPracticalLabel = document.getElementById('actual_practical_label');
            const extraPracticalLabel = document.getElementById('extra_practical_label');
            const percentageDisplay = document.getElementById('completion_percentage_display');
            const formContainer = document.getElementById('form-container');
            const warningMessage = document.getElementById('warning-message');
            const formSubjectInput = document.getElementById('form_subject');
            const progressSummary = document.getElementById('progress_summary');
            const semesterTotalDisplay = document.getElementById('semester_total_display');
            const semesterTheoryDisplay = document.getElementById('semester_theory_display');
            const semesterPracticalDisplay = document.getElementById('semester_practical_display');
            const plannedCumulativeDisplay = document.getElementById('planned_cumulative_display');
            const plannedTheoryDisplay = document.getElementById('planned_theory_display');
            const plannedPracticalDisplay = document.getElementById('planned_practical_display');
            const actualCumulativeDisplay = document.getElementById('actual_cumulative_display');
            const actualTheoryDisplay = document.getElementById('actual_theory_display');
            const actualPracticalDisplay = document.getElementById('actual_practical_display');
            const actualWeeklyDisplay = document.getElementById('actual_weekly_display');
            const actualWeeklyTheoryDisplay = document.getElementById('actual_weekly_theory_display');
            const actualWeeklyPracticalDisplay = document.getElementById('actual_weekly_practical_display');
            const extraCumulativeDisplay = document.getElementById('extra_cumulative_display');
            const extraCumulativeTheoryDisplay = document.getElementById('extra_cumulative_theory_display');
            const extraCumulativePracticalDisplay = document.getElementById('extra_cumulative_practical_display');
            const extraWeeklyTotalDisplay = document.getElementById('extra_weekly_total_display');
            const extraTheoryDisplay = document.getElementById('extra_theory_display');
            const extraPracticalDisplay = document.getElementById('extra_practical_display');
            const combinedCumulativeDisplay = document.getElementById('combined_cumulative_display');
            const combinedTheoryDisplay = document.getElementById('combined_theory_display');
            const combinedPracticalDisplay = document.getElementById('combined_practical_display');
            const holidayCheckbox = document.getElementById('holiday_week');
            const reminderBanner = document.getElementById('reminderBanner');
            const defaultWarningHTML = warningMessage ? warningMessage.innerHTML : '';
            const totalWeeks = calendarConfig.totalWeeks || 0;
            const instructionalWeeks = calendarConfig.instructionalWeeks || totalWeeks;
            const semesterStart = calendarConfig.startDate ? new Date(calendarConfig.startDate + 'T00:00:00') : null;
            const semesterEnd = calendarConfig.endDate ? new Date(calendarConfig.endDate + 'T00:00:00') : null;
            const progressForm = document.getElementById('progressForm');
            let submittedWeekStatuses = {};
            let currentWeeklyTotal = 0;
            let currentTheoryHours = 0;
            let currentPracticalHours = 0;
            let semesterTotalHours = 0;
            let semesterTheoryHours = 0;
            let semesterPracticalHours = 0;
            let planWeeksForSubject = 0;
            let lastActualTheoryBaseline = 0;
            let lastActualPracticalBaseline = 0;
            let lastActualTotalBaseline = 0;
            let formDirty = false;
            let lastValidActualPractical = 0;
            let lastValidExtraPractical = 0;
            let lastPlannedHours = 0;
            let lastActualTotalHours = 0;
            let lastActualTheoryHours = 0;
            let lastActualPracticalHours = 0;
            let lastExtraTheoryHours = 0;
            let lastExtraPracticalHours = 0;
            let lastExtraTotalHours = 0;
            let currentPracticalLabel = 'Practical';
            let practicalFieldsEnabled = true;
            
            function countPreviousHolidayWeeks(targetWeek) {
                let count = 0;
                Object.keys(submittedWeekStatuses).forEach((weekKey) => {
                    const weekInt = parseInt(weekKey, 10);
                    if (!Number.isNaN(weekInt) && weekInt < targetWeek) {
                        const status = submittedWeekStatuses[weekKey];
                        if (status && status.toLowerCase().includes('holiday')) {
                            count++;
                        }
                    }
                });
                return count;
            }

            function formatHoursValue(value) {
                if (!Number.isFinite(value)) {
                    return '0';
                }
                const rounded = Math.round(value * 100) / 100;
                if (Number.isInteger(rounded)) {
                    return String(rounded);
                }
                return rounded.toFixed(2);
            }

            function normalizePracticalHours(value) {
                if (!Number.isFinite(value)) {
                    return 0;
                }
                const tolerance = 0.0001;
                if (Math.abs(value) < tolerance) {
                    return 0;
                }
                const remainder = value % 2;
                const normalized = remainder < 0 ? remainder + 2 : remainder;
                if (Math.abs(normalized) < tolerance || Math.abs(normalized - 2) < tolerance) {
                    return value;
                }
                return Math.round(value / 2) * 2;
            }

            function setPracticalLabel(label) {
                const normalized = typeof label === 'string' && label.trim() !== '' ? label.trim() : 'Practical';
                currentPracticalLabel = normalized;
                if (actualPracticalLabel) {
                    actualPracticalLabel.textContent = `Actual ${currentPracticalLabel} Hours Conducted This Week`;
                }
                if (extraPracticalLabel) {
                    extraPracticalLabel.textContent = `Extra ${currentPracticalLabel} Classes Conducted This Week`;
                }
                if (semesterPracticalDisplay) {
                    semesterPracticalDisplay.textContent = `${currentPracticalLabel} 0`;
                }
                if (plannedPracticalDisplay) {
                    plannedPracticalDisplay.textContent = `${currentPracticalLabel} 0`;
                }
                if (actualPracticalDisplay) {
                    actualPracticalDisplay.textContent = `${currentPracticalLabel} 0`;
                }
                if (actualWeeklyPracticalDisplay) {
                    actualWeeklyPracticalDisplay.textContent = `${currentPracticalLabel} 0`;
                }
                if (extraCumulativePracticalDisplay) {
                    extraCumulativePracticalDisplay.textContent = `${currentPracticalLabel} 0`;
                }
                if (combinedPracticalDisplay) {
                    combinedPracticalDisplay.textContent = `${currentPracticalLabel} 0`;
                }
                if (extraPracticalDisplay) {
                    extraPracticalDisplay.textContent = `${currentPracticalLabel} 0`;
                }
            }

            function togglePracticalFields(isEnabled) {
                practicalFieldsEnabled = !!isEnabled;
                if (actualPracticalGroup) {
                    actualPracticalGroup.style.display = practicalFieldsEnabled ? '' : 'none';
                }
                if (extraPracticalGroup) {
                    extraPracticalGroup.style.display = practicalFieldsEnabled ? '' : 'none';
                }
                if (actualPracticalInput) {
                    if (practicalFieldsEnabled) {
                        actualPracticalInput.readOnly = false;
                        if (!holidayCheckbox || !holidayCheckbox.checked) {
                            actualPracticalInput.required = true;
                        }
                        if (actualPracticalInput.value === '0') {
                            actualPracticalInput.value = '';
                        }
                    } else {
                        actualPracticalInput.value = '0';
                        actualPracticalInput.readOnly = true;
                        actualPracticalInput.required = false;
                    }
                }
                if (extraPracticalInput) {
                    if (practicalFieldsEnabled) {
                        extraPracticalInput.readOnly = false;
                    } else {
                        extraPracticalInput.value = '0';
                        extraPracticalInput.readOnly = true;
                    }
                }
            }

            function buildWeeklyPlanLabel() {
                if (currentWeeklyTotal <= 0) {
                    return '';
                }
                const parts = [];
                if (currentTheoryHours > 0) {
                    parts.push(`${formatHoursValue(currentTheoryHours)} Theory`);
                }
                if (currentPracticalHours > 0) {
                    parts.push(`${formatHoursValue(currentPracticalHours)} ${currentPracticalLabel}`);
                }
                const split = parts.length ? ` (${parts.join(' + ')})` : '';
                return `${formatHoursValue(currentWeeklyTotal)} hrs${split}`;
            }

            function formatDate(date) {
                return date.toLocaleDateString(undefined, { day: '2-digit', month: 'short' });
            }

            function formatWeekRange(weekNumber) {
                if (!semesterStart) return '';
                const start = new Date(semesterStart);
                start.setDate(start.getDate() + (weekNumber - 1) * 7);
                const end = new Date(start);
                end.setDate(end.getDate() + 6);
                if (semesterEnd && end > semesterEnd) {
                    end.setTime(semesterEnd.getTime());
                }
                return `${formatDate(start)} - ${formatDate(end)}`;
            }

            function resetFormFields() {
                if (timelineSelect) {
                    timelineSelect.innerHTML = '<option value="">Select Week</option>';
                    timelineSelect.disabled = false;
                }
                if (percentageDisplay) percentageDisplay.value = '';
                if (actualTheoryInput) actualTheoryInput.value = '';
                if (actualPracticalInput) actualPracticalInput.value = '';
                if (extraTheoryInput) extraTheoryInput.value = '';
                if (extraPracticalInput) extraPracticalInput.value = '';
                if (progressSummary) {
                    progressSummary.style.display = 'none';
                }
                practicalFieldsEnabled = true;
                currentPracticalLabel = 'Practical';
                setPracticalLabel(currentPracticalLabel);
                if (actualPracticalGroup) actualPracticalGroup.style.display = '';
                if (extraPracticalGroup) extraPracticalGroup.style.display = '';
                if (actualPracticalInput) {
                    actualPracticalInput.readOnly = false;
                    actualPracticalInput.required = true;
                }
                if (extraPracticalInput) {
                    extraPracticalInput.readOnly = false;
                }
                if (semesterTotalDisplay) semesterTotalDisplay.textContent = '0';
                if (semesterTheoryDisplay) semesterTheoryDisplay.textContent = 'Theory 0';
                if (semesterPracticalDisplay) semesterPracticalDisplay.textContent = `${currentPracticalLabel} 0`;
                if (plannedCumulativeDisplay) plannedCumulativeDisplay.textContent = '0';
                if (plannedTheoryDisplay) plannedTheoryDisplay.textContent = 'Theory 0';
                if (plannedPracticalDisplay) plannedPracticalDisplay.textContent = `${currentPracticalLabel} 0`;
                if (actualCumulativeDisplay) actualCumulativeDisplay.textContent = '0';
                if (actualTheoryDisplay) actualTheoryDisplay.textContent = 'Theory 0';
                if (actualPracticalDisplay) actualPracticalDisplay.textContent = `${currentPracticalLabel} 0`;
                if (actualWeeklyDisplay) actualWeeklyDisplay.textContent = '0';
                if (actualWeeklyTheoryDisplay) actualWeeklyTheoryDisplay.textContent = 'Theory 0';
                if (actualWeeklyPracticalDisplay) actualWeeklyPracticalDisplay.textContent = `${currentPracticalLabel} 0`;
                if (extraCumulativeDisplay) extraCumulativeDisplay.textContent = '0';
                if (extraCumulativeTheoryDisplay) extraCumulativeTheoryDisplay.textContent = 'Theory 0';
                if (extraCumulativePracticalDisplay) extraCumulativePracticalDisplay.textContent = `${currentPracticalLabel} 0`;
                if (combinedCumulativeDisplay) combinedCumulativeDisplay.textContent = '0';
                if (combinedTheoryDisplay) combinedTheoryDisplay.textContent = 'Theory 0';
                if (combinedPracticalDisplay) combinedPracticalDisplay.textContent = `${currentPracticalLabel} 0`;
                if (extraWeeklyTotalDisplay) extraWeeklyTotalDisplay.textContent = '0';
                if (extraTheoryDisplay) extraTheoryDisplay.textContent = 'Theory 0';
                if (extraPracticalDisplay) extraPracticalDisplay.textContent = `${currentPracticalLabel} 0`;
                currentWeeklyTotal = 0;
                currentTheoryHours = 0;
                currentPracticalHours = 0;
                semesterTotalHours = 0;
                semesterTheoryHours = 0;
                semesterPracticalHours = 0;
                planWeeksForSubject = 0;
                lastActualTheoryBaseline = 0;
                lastActualPracticalBaseline = 0;
                lastActualTotalBaseline = 0;
                formDirty = false;
                lastValidActualPractical = 0;
                lastValidExtraPractical = 0;
                lastPlannedHours = 0;
                lastActualTotalHours = 0;
                lastActualTheoryHours = 0;
                lastActualPracticalHours = 0;
                lastExtraTheoryHours = 0;
                lastExtraPracticalHours = 0;
                lastExtraTotalHours = 0;
            }

            function populateTimelineOptions(lastCompletedWeek) {
                if (!timelineSelect) return;
                timelineSelect.innerHTML = '<option value="">Select Week</option>';

                const nextWeek = lastCompletedWeek + 1;
                for (let week = 1; week <= totalWeeks; week++) {
                    const option = document.createElement('option');
                    option.value = `week_${week}`;
                    const rangeLabel = formatWeekRange(week);
                    const weeklyPlan = buildWeeklyPlanLabel();
                    let label = `Week ${week}`;
                    if (weeklyPlan) {
                        label += ` [${weeklyPlan}]`;
                    }
                    if (rangeLabel) {
                        label += ` — ${rangeLabel}`;
                    }
                    const statusKey = String(week);
                    const weekStatus = submittedWeekStatuses[statusKey];

                    if (week <= lastCompletedWeek) {
                        label += ' — Submitted';
                        if (weekStatus === 'holiday') {
                            label += ' (Holiday)';
                        } else if (weekStatus === 'auto-holiday') {
                            label += ' (Holiday - Auto)';
                        }
                        option.disabled = true;
                    } else if (week === nextWeek) {
                        if (instructionalWeeks && week > instructionalWeeks) {
                            label += ' (Holiday / No Classes)';
                        }
                        label += ' — Available';
                    } else {
                        if (instructionalWeeks && week > instructionalWeeks) {
                            label += ' (Holiday / No Classes) — Locked';
                        } else {
                            label += ' — Locked';
                        }
                        option.disabled = true;
                    }

                    option.textContent = label;
                    timelineSelect.appendChild(option);
                }

                if (nextWeek > totalWeeks) {
                    timelineSelect.value = '';
                    timelineSelect.disabled = true;
                } else {
                    timelineSelect.value = `week_${nextWeek}`;
                    timelineSelect.disabled = false;
                }

                applyHolidayState(nextWeek);
            }

            function updateCalculations() {
                if (!subjectSelect || !timelineSelect) return;
                const option = subjectSelect.options[subjectSelect.selectedIndex];
                if (!option || !option.value) {
                    if (percentageDisplay) percentageDisplay.value = '';
                    if (progressSummary) progressSummary.style.display = 'none';
                    return;
                }
                if (currentWeeklyTotal <= 0) {
                    if (percentageDisplay) percentageDisplay.value = '';
                    if (progressSummary) progressSummary.style.display = 'none';
                    return;
                }
                const timelineValue = timelineSelect.value;
                if (!timelineValue) {
                    if (percentageDisplay) percentageDisplay.value = '';
                    if (progressSummary) progressSummary.style.display = 'none';
                    return;
                }
                const match = timelineValue.match(/^week_(\d+)$/);
                if (!match) {
                    return;
                }

                const weekNumber = parseInt(match[1], 10);
                if (!Number.isFinite(weekNumber) || weekNumber <= 0) {
                    return;
                }

                let effectiveWeek = instructionalWeeks > 0 ? Math.min(weekNumber, instructionalWeeks) : weekNumber;
                if (instructionalWeeks > 0) {
                    const holidaysBefore = countPreviousHolidayWeeks(weekNumber);
                    effectiveWeek = Math.max(0, effectiveWeek - holidaysBefore);
                }

                const isHoliday = holidayCheckbox ? holidayCheckbox.checked : false;
                if (isHoliday && instructionalWeeks > 0) {
                    if (weekNumber <= instructionalWeeks) {
                        effectiveWeek = Math.max(0, effectiveWeek - 1);
                    } else {
                        effectiveWeek = instructionalWeeks;
                    }
                }

                let plannedTheoryTillWeek = currentTheoryHours * effectiveWeek;
                let plannedPracticalTillWeek = currentPracticalHours * effectiveWeek;
                if (semesterTheoryHours > 0) {
                    plannedTheoryTillWeek = Math.min(plannedTheoryTillWeek, semesterTheoryHours);
                }
                if (semesterPracticalHours > 0) {
                    plannedPracticalTillWeek = Math.min(plannedPracticalTillWeek, semesterPracticalHours);
                }
                let plannedTillWeek = plannedTheoryTillWeek + plannedPracticalTillWeek;
                if (semesterTotalHours > 0) {
                    plannedTillWeek = Math.min(plannedTillWeek, semesterTotalHours);
                }

                let scheduledTheory = actualTheoryInput ? parseFloat(actualTheoryInput.value) : 0;
                let scheduledPractical = actualPracticalInput ? parseFloat(actualPracticalInput.value) : 0;
                let extraTheory = extraTheoryInput ? parseFloat(extraTheoryInput.value) || 0 : 0;
                let extraPractical = extraPracticalInput ? parseFloat(extraPracticalInput.value) || 0 : 0;
                if (!Number.isFinite(scheduledTheory) || scheduledTheory < 0) {
                    scheduledTheory = 0;
                }
                if (!Number.isFinite(scheduledPractical) || scheduledPractical < 0) {
                    scheduledPractical = 0;
                }
                if (!Number.isFinite(extraTheory) || extraTheory < 0) {
                    extraTheory = 0;
                }
                if (!Number.isFinite(extraPractical) || extraPractical < 0) {
                    extraPractical = 0;
                }
                if (isHoliday) {
                    scheduledTheory = 0;
                    scheduledPractical = 0;
                    extraTheory = 0;
                    extraPractical = 0;
                }

                const weeklyTheoryHours = Math.max(0, scheduledTheory);
                const weeklyPracticalHours = Math.max(0, scheduledPractical);

                const baseExtraTheory = Math.max(0, lastExtraTheoryHours);
                const baseExtraPractical = Math.max(0, lastExtraPracticalHours);
                const fallbackExtraTotal = Number.isFinite(lastExtraTotalHours)
                    ? Math.max(0, lastExtraTotalHours)
                    : Math.max(0, baseExtraTheory + baseExtraPractical);
                const weeklyExtraTheory = isHoliday ? 0 : Math.max(0, extraTheory);
                const weeklyExtraPractical = isHoliday ? 0 : Math.max(0, extraPractical);
                const cumulativeExtraTheory = formDirty ? Math.max(0, baseExtraTheory + weeklyExtraTheory) : baseExtraTheory;
                const cumulativeExtraPractical = formDirty ? Math.max(0, baseExtraPractical + weeklyExtraPractical) : baseExtraPractical;
                const cumulativeExtraTotal = formDirty
                    ? Math.max(0, cumulativeExtraTheory + cumulativeExtraPractical)
                    : fallbackExtraTotal;

                let cumulativeTheory = Math.max(0, lastActualTheoryBaseline + weeklyTheoryHours);
                let cumulativePractical = Math.max(0, lastActualPracticalBaseline + weeklyPracticalHours);
                if (semesterTheoryHours > 0 && cumulativeTheory > semesterTheoryHours) {
                    cumulativeTheory = semesterTheoryHours;
                }
                if (semesterPracticalHours > 0 && cumulativePractical > semesterPracticalHours) {
                    cumulativePractical = semesterPracticalHours;
                }

                const displayActualTheory = formDirty ? cumulativeTheory : lastActualTheoryBaseline;
                const displayActualPractical = formDirty ? cumulativePractical : lastActualPracticalBaseline;
                const weeklyActualTheory = formDirty ? weeklyTheoryHours : 0;
                const weeklyActualPractical = formDirty ? weeklyPracticalHours : 0;

                const plannedPracticalValue = normalizePracticalHours(plannedPracticalTillWeek);
                const plannedTotalValue = Math.max(0, plannedTheoryTillWeek + plannedPracticalValue);

                const actualPracticalValue = normalizePracticalHours(displayActualPractical);
                const actualTotalValue = Math.max(0, displayActualTheory + actualPracticalValue);

                const weeklyActualPracticalValue = normalizePracticalHours(weeklyActualPractical);
                const weeklyActualTotalValue = Math.max(0, weeklyActualTheory + weeklyActualPracticalValue);

                const extraPracticalValue = normalizePracticalHours(cumulativeExtraPractical);
                const extraTotalValue = Math.max(0, cumulativeExtraTheory + extraPracticalValue);

                const weeklyExtraTheoryDisplay = formDirty ? weeklyExtraTheory : 0;
                const weeklyExtraPracticalRaw = formDirty ? weeklyExtraPractical : 0;
                const weeklyExtraPracticalValue = normalizePracticalHours(weeklyExtraPracticalRaw);
                const weeklyExtraTotalValue = Math.max(0, weeklyExtraTheoryDisplay + weeklyExtraPracticalValue);

                const combinedTheoryRaw = Math.max(0, displayActualTheory + cumulativeExtraTheory);
                const combinedPracticalValue = Math.max(0, actualPracticalValue + extraPracticalValue);
                const combinedTotalValue = Math.max(0, actualTotalValue + extraTotalValue);

                if (plannedCumulativeDisplay) {
                    plannedCumulativeDisplay.textContent = formatHoursValue(plannedTotalValue);
                }
                if (plannedTheoryDisplay) {
                    plannedTheoryDisplay.textContent = `Theory ${formatHoursValue(plannedTheoryTillWeek)}`;
                }
                if (plannedPracticalDisplay) {
                    plannedPracticalDisplay.textContent = `${currentPracticalLabel} ${formatHoursValue(plannedPracticalValue)}`;
                }
                if (actualCumulativeDisplay) {
                    actualCumulativeDisplay.textContent = formatHoursValue(actualTotalValue);
                }
                if (actualTheoryDisplay) {
                    actualTheoryDisplay.textContent = `Theory ${formatHoursValue(displayActualTheory)}`;
                }
                if (actualPracticalDisplay) {
                    actualPracticalDisplay.textContent = `${currentPracticalLabel} ${formatHoursValue(actualPracticalValue)}`;
                }
                if (actualWeeklyDisplay) {
                    actualWeeklyDisplay.textContent = formatHoursValue(weeklyActualTotalValue);
                }
                if (actualWeeklyTheoryDisplay) {
                    actualWeeklyTheoryDisplay.textContent = `Theory ${formatHoursValue(weeklyActualTheory)}`;
                }
                if (actualWeeklyPracticalDisplay) {
                    actualWeeklyPracticalDisplay.textContent = `${currentPracticalLabel} ${formatHoursValue(weeklyActualPracticalValue)}`;
                }
                if (extraCumulativeDisplay) {
                    extraCumulativeDisplay.textContent = formatHoursValue(extraTotalValue);
                }
                if (extraCumulativeTheoryDisplay) {
                    extraCumulativeTheoryDisplay.textContent = `Theory ${formatHoursValue(cumulativeExtraTheory)}`;
                }
                if (extraCumulativePracticalDisplay) {
                    extraCumulativePracticalDisplay.textContent = `${currentPracticalLabel} ${formatHoursValue(extraPracticalValue)}`;
                }
                if (combinedCumulativeDisplay) {
                    combinedCumulativeDisplay.textContent = formatHoursValue(combinedTotalValue);
                }
                if (combinedTheoryDisplay) {
                    combinedTheoryDisplay.textContent = `Theory ${formatHoursValue(combinedTheoryRaw)}`;
                }
                if (combinedPracticalDisplay) {
                    combinedPracticalDisplay.textContent = `${currentPracticalLabel} ${formatHoursValue(combinedPracticalValue)}`;
                }
                if (extraWeeklyTotalDisplay) {
                    extraWeeklyTotalDisplay.textContent = formatHoursValue(weeklyExtraTotalValue);
                }
                if (extraTheoryDisplay) {
                    extraTheoryDisplay.textContent = `Theory ${formatHoursValue(weeklyExtraTheoryDisplay)}`;
                }
                if (extraPracticalDisplay) {
                    extraPracticalDisplay.textContent = `${currentPracticalLabel} ${formatHoursValue(weeklyExtraPracticalValue)}`;
                }
                if (progressSummary) {
                    progressSummary.style.display = 'block';
                }

                if (percentageDisplay) {
                    const totalForPercentage = Math.max(0, (displayActualTheory + displayActualPractical) + (cumulativeExtraTheory + cumulativeExtraPractical));
                    const percentage = plannedTillWeek > 0 ? (totalForPercentage / plannedTillWeek) * 100 : 0;
                    percentageDisplay.value = `${percentage.toFixed(2)}%`;
                }
            }

            function setHolidayFields(isHoliday) {
                if (actualTheoryInput) {
                    if (isHoliday) {
                        actualTheoryInput.value = formatHoursValue(lastActualTheoryBaseline);
                    } else {
                        actualTheoryInput.value = '';
                    }
                    actualTheoryInput.disabled = isHoliday;
                    actualTheoryInput.required = !isHoliday;
                }

                if (actualPracticalInput) {
                    if (!practicalFieldsEnabled) {
                        actualPracticalInput.disabled = false;
                        actualPracticalInput.required = false;
                        actualPracticalInput.value = '0';
                    } else {
                        if (isHoliday) {
                            actualPracticalInput.value = formatHoursValue(lastActualPracticalBaseline);
                        } else {
                            actualPracticalInput.value = '';
                        }
                        actualPracticalInput.disabled = isHoliday;
                        actualPracticalInput.required = !isHoliday;
                    }
                }

                if (extraTheoryInput) {
                    if (isHoliday) {
                        extraTheoryInput.value = '0';
                    }
                    extraTheoryInput.disabled = isHoliday;
                }

                if (extraPracticalInput) {
                    if (!practicalFieldsEnabled) {
                        extraPracticalInput.disabled = false;
                        extraPracticalInput.value = '0';
                    } else {
                        if (isHoliday) {
                            extraPracticalInput.value = '0';
                        }
                        extraPracticalInput.disabled = isHoliday;
                    }
                }
                if (isHoliday) {
                    formDirty = false;
                }
            }

            function applyHolidayState(weekNumber) {
                if (!holidayCheckbox) return;
                const autoHoliday = instructionalWeeks > 0 && weekNumber > instructionalWeeks;
                if (autoHoliday) {
                    holidayCheckbox.checked = true;
                    holidayCheckbox.dataset.locked = '1';
                } else if (holidayCheckbox.dataset.locked === '1') {
                    holidayCheckbox.dataset.locked = '0';
                    holidayCheckbox.checked = false;
                }
                setHolidayFields(holidayCheckbox.checked);
                updateCalculations();
            }

            function showWarning(messageHTML) {
                if (!warningMessage) return;
                warningMessage.innerHTML = messageHTML;
                warningMessage.style.display = 'block';
            }

            function hideWarning() {
                if (!warningMessage) return;
                warningMessage.innerHTML = defaultWarningHTML;
                warningMessage.style.display = 'none';
            }

            function handleSubjectChange() {
                resetFormFields();
                if (!subjectSelect || !formContainer) return;
                const option = subjectSelect.options[subjectSelect.selectedIndex];

                if (!option || !option.value) {
                    if (formSubjectInput) formSubjectInput.value = '';
                    formContainer.style.display = 'none';
                    hideWarning();
                    return;
                }

                submittedWeekStatuses = {};
                if (option.dataset.weekStatus) {
                    try {
                        const parsed = JSON.parse(option.dataset.weekStatus);
                        if (parsed && typeof parsed === 'object') {
                            submittedWeekStatuses = parsed;
                            Object.keys(submittedWeekStatuses).forEach(key => {
                                if (submittedWeekStatuses[key] === 'holiday_auto') {
                                    submittedWeekStatuses[key] = 'auto-holiday';
                                }
                            });
                        }
                    } catch (error) {
                        submittedWeekStatuses = {};
                    }
                }

                currentWeeklyTotal = parseFloat(option.dataset.weeklyTotal || '0');
                currentTheoryHours = parseFloat(option.dataset.weeklyTheory || '0');
                currentPracticalHours = parseFloat(option.dataset.weeklyPractical || '0');
                semesterTotalHours = parseFloat(option.dataset.semesterTotal || '0');
                semesterTheoryHours = parseFloat(option.dataset.semesterTheory || '0');
                semesterPracticalHours = parseFloat(option.dataset.semesterPractical || '0');
                planWeeksForSubject = parseInt(option.dataset.planWeeks || '0', 10) || 0;
                lastPlannedHours = parseFloat(option.dataset.lastPlanned || '0') || 0;
                lastActualTotalHours = parseFloat(option.dataset.lastActualTotal || '0') || 0;
                lastActualTheoryHours = parseFloat(option.dataset.lastActualTheory || '0') || 0;
                lastActualPracticalHours = parseFloat(option.dataset.lastActualPractical || '0') || 0;
                lastExtraTheoryHours = parseFloat(option.dataset.lastExtraTheory || '0') || 0;
                lastExtraPracticalHours = parseFloat(option.dataset.lastExtraPractical || '0') || 0;
                lastExtraTotalHours = parseFloat(option.dataset.lastExtraTotal || '0') || 0;
                setPracticalLabel(option.dataset.practicalLabel || '');

                if (!Number.isFinite(lastExtraTheoryHours) || lastExtraTheoryHours < 0) {
                    lastExtraTheoryHours = 0;
                }
                if (!Number.isFinite(lastExtraPracticalHours) || lastExtraPracticalHours < 0) {
                    lastExtraPracticalHours = 0;
                }
                if (!Number.isFinite(lastExtraTotalHours) || lastExtraTotalHours < 0) {
                    lastExtraTotalHours = lastExtraTheoryHours + lastExtraPracticalHours;
                }

                lastActualTheoryBaseline = lastActualTheoryHours;
                lastActualPracticalBaseline = lastActualPracticalHours;
                lastActualTotalBaseline = lastActualTotalHours;
                formDirty = false;

                if (planWeeksForSubject <= 0) {
                    planWeeksForSubject = instructionalWeeks > 0 ? instructionalWeeks : totalWeeks;
                }
                if (currentWeeklyTotal <= 0 && planWeeksForSubject > 0 && semesterTotalHours > 0) {
                    currentWeeklyTotal = semesterTotalHours / planWeeksForSubject;
                }
                if (currentTheoryHours <= 0 && planWeeksForSubject > 0 && semesterTheoryHours > 0) {
                    currentTheoryHours = semesterTheoryHours / planWeeksForSubject;
                }
                if (currentPracticalHours <= 0 && planWeeksForSubject > 0 && semesterPracticalHours > 0) {
                    currentPracticalHours = semesterPracticalHours / planWeeksForSubject;
                }

                togglePracticalFields(semesterPracticalHours > 0 || currentPracticalHours > 0);

                const lastWeek = parseInt(option.dataset.lastWeek || '0', 10);
                if (formSubjectInput) formSubjectInput.value = option.value;
                if (holidayCheckbox) {
                    holidayCheckbox.checked = false;
                    holidayCheckbox.dataset.locked = '0';
                }
                setHolidayFields(false);

                if (actualTheoryInput) {
                    actualTheoryInput.value = '';
                }
                if (actualPracticalInput) {
                    actualPracticalInput.value = '';
                }
                if (extraTheoryInput) {
                    extraTheoryInput.value = '0';
                }
                if (extraPracticalInput) {
                    extraPracticalInput.value = '0';
                }
                lastValidActualPractical = 0;
                lastValidExtraPractical = 0;

                if (semesterTotalHours <= 0) {
                    formContainer.style.display = 'none';
                    showWarning(defaultWarningHTML);
                    return;
                }

                if (!calendarConfig.calendarAvailable || !semesterStart || totalWeeks === 0) {
                    formContainer.style.display = 'none';
                    showWarning('<p>An active academic calendar is required before recording weekly progress.</p>');
                    return;
                }

                if (semesterTotalDisplay) {
                    semesterTotalDisplay.textContent = formatHoursValue(semesterTotalHours);
                }
                if (semesterTheoryDisplay) {
                    semesterTheoryDisplay.textContent = `Theory ${formatHoursValue(semesterTheoryHours)}`;
                }
                if (semesterPracticalDisplay) {
                    semesterPracticalDisplay.textContent = `${currentPracticalLabel} ${formatHoursValue(semesterPracticalHours)}`;
                }
                if (progressSummary) {
                    progressSummary.style.display = 'block';
                }

                const nextWeek = lastWeek + 1;
                populateTimelineOptions(lastWeek);

                if (nextWeek > totalWeeks) {
                    formContainer.style.display = 'none';
                    showWarning(`<p>All ${totalWeeks} weeks have already been submitted for this subject.</p>`);
                    return;
                }

                hideWarning();
                formContainer.style.display = 'block';
                updateCalculations();
            }

            if (subjectSelect) {
                subjectSelect.addEventListener('change', handleSubjectChange);
            }
            if (timelineSelect) {
                timelineSelect.addEventListener('change', function() {
                    formDirty = false;
                    const match = timelineSelect.value.match(/^week_(\d+)$/);
                    if (match) {
                        applyHolidayState(parseInt(match[1], 10));
                    } else {
                        setHolidayFields(false);
                        updateCalculations();
                    }
                });
            }
            if (actualTheoryInput) {
                actualTheoryInput.addEventListener('input', () => {
                    formDirty = true;
                    updateCalculations();
                });
            }
            if (actualPracticalInput) {
                actualPracticalInput.addEventListener('input', () => {
                    formDirty = true;
                    updateCalculations();
                });
            }
            if (extraTheoryInput) {
                extraTheoryInput.addEventListener('input', () => {
                    formDirty = true;
                    updateCalculations();
                });
            }
            if (extraPracticalInput) {
                extraPracticalInput.addEventListener('input', () => {
                    formDirty = true;
                    updateCalculations();
                });
            }
            if (holidayCheckbox) {
                holidayCheckbox.addEventListener('change', function() {
                    if (holidayCheckbox.dataset.locked === '1') {
                        holidayCheckbox.checked = true;
                    }
                    setHolidayFields(holidayCheckbox.checked);
                    updateCalculations();
                });
            }

            if (calendarConfig.showReminder && reminderBanner) {
                reminderBanner.style.display = 'block';
                if (reminderMessage) {
                    setTimeout(() => alert(reminderMessage), 250);
                }
            }

            if (subjectSelect && subjectSelect.options.length === 2 && calendarConfig.calendarAvailable) {
                subjectSelect.selectedIndex = 1;
                handleSubjectChange();
            }

            function isEvenHours(value) {
                if (!Number.isFinite(value) || Math.abs(value) < 0.0001) {
                    return true;
                }
                const remainder = value % 2;
                const normalized = remainder < 0 ? remainder + 2 : remainder;
                return Math.abs(normalized) < 0.0001 || Math.abs(normalized - 2) < 0.0001;
            }

            if (progressForm) {
                progressForm.addEventListener('submit', function(event) {
                    if (holidayCheckbox && holidayCheckbox.checked) {
                        return;
                    }

                    const practicalValue = actualPracticalInput ? parseFloat(actualPracticalInput.value || '0') : 0;
                    const extraPracticalValue = extraPracticalInput ? parseFloat(extraPracticalInput.value || '0') : 0;

                    if (!isEvenHours(practicalValue) || !isEvenHours(extraPracticalValue)) {
                        event.preventDefault();
                        alert('Please enter practical hours in multiples of 2 (e.g., 0, 2, 4, ...).');
                    }
                });
            }
        });
    </script>
</body>
</html>
