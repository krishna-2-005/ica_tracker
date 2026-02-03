<?php
session_start();
include 'db_connect.php';
require_once __DIR__ . '/includes/academic_context.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header('Location: login.php');
    exit;
}
function removeQueryParams(array $keys): string {
    $params = $_GET;
    foreach ($keys as $key) {
        unset($params[$key]);
    }
    $base = basename($_SERVER['PHP_SELF'] ?? 'view_reports.php');
    return $base . (empty($params) ? '' : '?' . http_build_query($params));
}

function buildStatusFilterUrl(string $statusKey): string {
    $params = $_GET;
    if ($statusKey === '') {
        unset($params['status_filter']);
    } else {
        $params['status_filter'] = $statusKey;
    }
    unset($params['bin_min'], $params['bin_max'], $params['bin_index']);
    $base = basename($_SERVER['PHP_SELF'] ?? 'view_reports.php');
    return $base . (empty($params) ? '' : '?' . http_build_query($params));
}

function determineStandingKey(float $finalMark, float $allocatedMarks, bool $hasRecords, bool $hasNumericMarks): string {
    if ($allocatedMarks <= 0.0 || !$hasRecords) {
        return 'not_allocated';
    }

    if (!$hasNumericMarks) {
        return 'absent';
    }

    $normalizedMark = max(0.0, min($finalMark, $allocatedMarks));
    $ratio = $allocatedMarks > 0 ? ($normalizedMark / $allocatedMarks) : 0.0;

    if ($ratio < 0.5) {
        return 'at_risk';
    }
    if ($ratio <= 0.75) {
        return 'average';
    }
    return 'good';
}

function format_mark_display(float $value): string {
    $formatted = number_format($value, 2, '.', '');
    $formatted = rtrim(rtrim($formatted, '0'), '.');
    return $formatted === '' ? '0' : $formatted;
}

function buildReportingLabel(array $calendarRow): string {
    if (!empty($calendarRow['label_override'])) {
        return (string)$calendarRow['label_override'];
    }

    $parts = [];
    if (isset($calendarRow['semester_number']) && $calendarRow['semester_number'] !== null && $calendarRow['semester_number'] !== '') {
        $parts[] = 'Semester ' . $calendarRow['semester_number'];
    }
    if (!empty($calendarRow['semester_term'])) {
        $parts[] = ucfirst((string)$calendarRow['semester_term']) . ' Term';
    }

    $academicYear = isset($calendarRow['academic_year']) ? trim((string)$calendarRow['academic_year']) : '';
    if ($academicYear !== '') {
        $parts[] = (stripos($academicYear, 'AY') === 0) ? $academicYear : 'AY ' . $academicYear;
    }

    return implode(' • ', array_filter($parts));
}

function format_report_date(?string $date): string {
    if (!$date) {
        return '';
    }
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    return date('d M Y', $timestamp);
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
$termDateFilter = $academicContext['date_filter'] ?? null;
$termStartBound = isset($termDateFilter['start']) ? $termDateFilter['start'] . ' 00:00:00' : null;
$termEndBound = isset($termDateFilter['end']) ? $termDateFilter['end'] . ' 23:59:59' : null;
$selected_subject = isset($_GET['subject_id']) ? max(0, (int)$_GET['subject_id']) : 0;
$selected_class = isset($_GET['class_id']) ? max(0, (int)$_GET['class_id']) : 0;

$error_message = '';
$subjects_list = [];
$classes_list = [];
$student_reports = [];
$display_reports = [];
$component_headers = [];
$component_maxes = [];
$subject_name_for_file = '';
$class_name_for_file = '';
$class_label_for_print = '';
$school_for_file = '';
$start_date_for_file = '';
$end_date_for_file = '';
$reporting_label_for_file = '';
$start_date_display_for_file = '';
$end_date_display_for_file = '';
$js_distribution = null;
$js_labels = null;
$js_ranges = null;
$js_mean = null;
$js_stddev = null;
$active_bin_label = null;
$teacher_name_raw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$teacher_name = $teacher_name_raw;
$teacher_name_display = $teacher_name_raw !== '' ? format_person_display($teacher_name_raw) : '';
$final_mark_values = [];
$final_stats = [
    'average' => null,
    'min' => null,
    'max' => null
];
$subject_meta = [
    'type' => 'regular',
    'theory_hours' => 0,
    'practical_hours' => 0
];
$status_filter = isset($_GET['status_filter']) ? strtolower(trim((string)$_GET['status_filter'])) : '';
$standing_definitions = [
    'not_allocated' => ['label' => 'Not Allocated'],
    'absent' => ['label' => 'Ab'],
    'at_risk' => ['label' => 'At Risk'],
    'average' => ['label' => 'Average'],
    'good' => ['label' => 'Good Standing'],
];
if (!array_key_exists($status_filter, $standing_definitions)) {
    $status_filter = '';
}
$category_counts = array_fill_keys(array_keys($standing_definitions), 0);
$total_student_count = 0;
$filtered_student_count = 0;

if (function_exists('ensureAcademicCalendarSchema')) {
    ensureAcademicCalendarSchema($conn);
}

// Fetch all subjects assigned to the teacher
$subjects_query = "
    SELECT DISTINCT s.id, s.subject_name
    FROM teacher_subject_assignments tsa
    JOIN subjects s ON s.id = tsa.subject_id
    JOIN classes c ON c.id = tsa.class_id
    WHERE tsa.teacher_id = ?";
if ($activeTermId > 0) {
    $subjects_query .= " AND c.academic_term_id = ?";
}
$subjects_query .= "
    ORDER BY s.subject_name
";
$stmt_subjects = mysqli_prepare($conn, $subjects_query);
if ($stmt_subjects) {
    if ($activeTermId > 0) {
        mysqli_stmt_bind_param($stmt_subjects, "ii", $teacher_id, $activeTermId);
    } else {
        mysqli_stmt_bind_param($stmt_subjects, "i", $teacher_id);
    }
    if (mysqli_stmt_execute($stmt_subjects)) {
        $subjects_result = mysqli_stmt_get_result($stmt_subjects);
        if ($subjects_result) {
            while ($row = mysqli_fetch_assoc($subjects_result)) {
                $subjects_list[] = [
                    'id' => isset($row['id']) ? (int)$row['id'] : 0,
                    'subject_name' => $row['subject_name'] ?? ''
                ];
            }
            mysqli_free_result($subjects_result);
        }
    }
    mysqli_stmt_close($stmt_subjects);
}

// Fetch classes mapped to the teacher (optionally filtered by selected subject)
$classes_query = "
    SELECT DISTINCT
        c.id AS id,
        c.class_name AS base_class_name,
        c.semester AS semester_value,
        c.school,
        COALESCE(sec.id, 0) AS section_id,
        COALESCE(sec.section_name, '') AS section_label
    FROM teacher_subject_assignments tsa
    JOIN classes c ON c.id = tsa.class_id
    LEFT JOIN sections sec ON sec.id = tsa.section_id
    WHERE tsa.teacher_id = ?";
if ($activeTermId > 0) {
    $classes_query .= " AND c.academic_term_id = ?";
}
if ($selected_subject > 0) {
    $classes_query .= " AND tsa.subject_id = ?";
}
$classes_query .= " ORDER BY base_class_name, section_label";
$stmt_classes = mysqli_prepare($conn, $classes_query);
if ($stmt_classes) {
    if ($activeTermId > 0 && $selected_subject > 0) {
        mysqli_stmt_bind_param($stmt_classes, "iii", $teacher_id, $activeTermId, $selected_subject);
    } elseif ($activeTermId > 0) {
        mysqli_stmt_bind_param($stmt_classes, "ii", $teacher_id, $activeTermId);
    } elseif ($selected_subject > 0) {
        mysqli_stmt_bind_param($stmt_classes, "ii", $teacher_id, $selected_subject);
    } else {
        mysqli_stmt_bind_param($stmt_classes, "i", $teacher_id);
    }
    if (mysqli_stmt_execute($stmt_classes)) {
        $classes_result = mysqli_stmt_get_result($stmt_classes);
        if ($classes_result) {
            while ($row = mysqli_fetch_assoc($classes_result)) {
                $classLabel = format_class_label(
                    $row['base_class_name'] ?? '',
                    $row['section_label'] ?? '',
                    $row['semester_value'] ?? '',
                    $row['school'] ?? ''
                );

                $classes_list[] = [
                    'id' => isset($row['id']) ? (int)$row['id'] : 0,
                    'class_name' => $classLabel,
                    'base_class_name' => $row['base_class_name'] ?? '',
                    'school' => $row['school'] ?? '',
                    'section_id' => isset($row['section_id']) ? (int)$row['section_id'] : 0,
                    'semester' => $row['semester_value'] ?? null,
                ];
            }
            mysqli_free_result($classes_result);
        }
    }
    mysqli_stmt_close($stmt_classes);
}

if ($selected_class > 0) {
    $valid_class_ids = array_column($classes_list, 'id');
    if (!in_array($selected_class, $valid_class_ids, true)) {
        $selected_class = 0;
    }
}

if ($selected_subject > 0) {
    $meta_stmt = mysqli_prepare($conn, "SELECT COALESCE(sd.subject_type, 'regular') AS subject_type, COALESCE(sd.theory_hours, 0) AS theory_hours, COALESCE(sd.practical_hours, 0) AS practical_hours FROM subjects s LEFT JOIN subject_details sd ON sd.subject_id = s.id WHERE s.id = ?");
    if ($meta_stmt) {
        mysqli_stmt_bind_param($meta_stmt, "i", $selected_subject);
        mysqli_stmt_execute($meta_stmt);
        $meta_res = mysqli_stmt_get_result($meta_stmt);
        if ($meta_res && ($meta_row = mysqli_fetch_assoc($meta_res))) {
            $subject_meta['type'] = strtolower($meta_row['subject_type'] ?? 'regular');
            $subject_meta['theory_hours'] = (int)($meta_row['theory_hours'] ?? 0);
            $subject_meta['practical_hours'] = (int)($meta_row['practical_hours'] ?? 0);
        }
        if ($meta_res) {
            mysqli_free_result($meta_res);
        }
        mysqli_stmt_close($meta_stmt);
    }
}

// If filters are selected, generate the detailed report data
if ($selected_subject > 0 && $selected_class > 0) {
    $assignment_sections = [];
    $assignment_sql = "
        SELECT DISTINCT COALESCE(section_id, 0) AS section_key
        FROM teacher_subject_assignments
        WHERE teacher_id = ? AND subject_id = ? AND class_id = ?
    ";
    $assignment_stmt = mysqli_prepare($conn, $assignment_sql);
    if ($assignment_stmt) {
        mysqli_stmt_bind_param($assignment_stmt, "iii", $teacher_id, $selected_subject, $selected_class);
        mysqli_stmt_execute($assignment_stmt);
        $assignment_res = mysqli_stmt_get_result($assignment_stmt);
        while ($assignment_res && ($assign_row = mysqli_fetch_assoc($assignment_res))) {
            $assignment_sections[] = (int)$assign_row['section_key'];
        }
        if ($assignment_res) {
            mysqli_free_result($assignment_res);
        }
        mysqli_stmt_close($assignment_stmt);
    }

    if (empty($assignment_sections)) {
        $error_message = 'You are not assigned to the selected subject and class combination.';
    } else {
        $limit_sections = in_array(0, $assignment_sections, true) ? [] : array_filter($assignment_sections, static fn($val) => $val > 0);

        // Populate filename metadata
        $sn_q = mysqli_prepare($conn, "SELECT subject_name, school FROM subjects WHERE id = ?");
        mysqli_stmt_bind_param($sn_q, "i", $selected_subject);
        mysqli_stmt_execute($sn_q);
        $sn_res = mysqli_stmt_get_result($sn_q);
        if ($sn_res && ($row = mysqli_fetch_assoc($sn_res))) {
            $subject_name_for_file = $row['subject_name'];
            if ($school_for_file === '') {
                $school_for_file = $row['school'] ?? '';
            }
        }
        if ($sn_res) {
            mysqli_free_result($sn_res);
        }
        mysqli_stmt_close($sn_q);

        $cn_q = mysqli_prepare($conn, "SELECT class_name, school, semester FROM classes WHERE id = ?");
        mysqli_stmt_bind_param($cn_q, "i", $selected_class);
        mysqli_stmt_execute($cn_q);
        $cn_res = mysqli_stmt_get_result($cn_q);
        if ($cn_res && ($row = mysqli_fetch_assoc($cn_res))) {
            $class_name_for_file = $row['class_name'] ?? '';
            $school_for_file = $row['school'] ?? '';
            $classSemesterForContext = $row['semester'] ?? null;
            $class_label_for_print = format_class_label(
                $row['class_name'] ?? '',
                '',
                $row['semester'] ?? '',
                $row['school'] ?? ''
            );

            if ($school_for_file !== '') {
                $contextForReport = resolveAcademicContext($conn, [
                    'school_name' => $school_for_file,
                    'default_semester' => $classSemesterForContext
                ]);
                $activeTermForReport = $contextForReport['active'] ?? null;
                if (!$activeTermForReport && !empty($contextForReport['terms'])) {
                    $activeTermForReport = $contextForReport['terms'][0];
                }
                if ($activeTermForReport) {
                    $start_date_for_file = $activeTermForReport['start_date'] ?? '';
                    $end_date_for_file = $activeTermForReport['end_date'] ?? '';
                    $reporting_label_for_file = $activeTermForReport['label'] ?? '';
                }
            }

            if ($start_date_for_file === '' || $end_date_for_file === '') {
                $fallbackSql = "SELECT start_date, end_date, semester_term, academic_year, semester_number, label_override FROM academic_calendar WHERE school_name = ? ORDER BY start_date DESC LIMIT 1";
                $fallbackStmt = mysqli_prepare($conn, $fallbackSql);
                if ($fallbackStmt) {
                    mysqli_stmt_bind_param($fallbackStmt, "s", $school_for_file);
                    if (mysqli_stmt_execute($fallbackStmt)) {
                        $fallbackRes = mysqli_stmt_get_result($fallbackStmt);
                        if ($fallbackRes && ($fallbackRow = mysqli_fetch_assoc($fallbackRes))) {
                            $start_date_for_file = $start_date_for_file !== '' ? $start_date_for_file : ($fallbackRow['start_date'] ?? '');
                            $end_date_for_file = $end_date_for_file !== '' ? $end_date_for_file : ($fallbackRow['end_date'] ?? '');
                            if ($reporting_label_for_file === '') {
                                $reporting_label_for_file = buildReportingLabel($fallbackRow);
                            }
                        }
                        if ($fallbackRes) {
                            mysqli_free_result($fallbackRes);
                        }
                    }
                    mysqli_stmt_close($fallbackStmt);
                }
            }
        }
        if ($cn_res) {
            mysqli_free_result($cn_res);
        }
        mysqli_stmt_close($cn_q);

        $start_date_display_for_file = format_report_date($start_date_for_file);
        $end_date_display_for_file = format_report_date($end_date_for_file);
        if ($reporting_label_for_file === '' && $start_date_display_for_file !== '' && $end_date_display_for_file !== '') {
            $reporting_label_for_file = $start_date_display_for_file . ' - ' . $end_date_display_for_file;
        }

        $component_maxes = [];
        $component_ids = [];
        $component_metadata = [];
        $total_allocated = 50;

        $fetchComponentRows = static function(mysqli $conn, string $sql, string $types, array $params): array {
            $rows = [];
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                return $rows;
            }
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $rows[] = $row;
                }
                mysqli_free_result($result);
            }
            mysqli_stmt_close($stmt);
            return $rows;
        };

        $base_component_sql = "SELECT id, component_name, total_marks, scaled_total_marks, instances, marks_per_instance FROM ica_components WHERE subject_id = ? AND teacher_id = ?";
        $base_types = "ii";
        $base_params = [$selected_subject, $teacher_id];

        $component_rows = [];
        $attempts = [];

        if ($selected_class > 0) {
            $attempts[] = [
                'sql' => $base_component_sql . " AND class_id = ? ORDER BY id",
                'types' => $base_types . 'i',
                'params' => array_merge($base_params, [$selected_class])
            ];
            $attempts[] = [
                'sql' => $base_component_sql . " AND (class_id IS NULL OR class_id = 0) ORDER BY id",
                'types' => $base_types,
                'params' => $base_params
            ];
        } else {
            $attempts[] = [
                'sql' => $base_component_sql . " AND (class_id IS NULL OR class_id = 0) ORDER BY id",
                'types' => $base_types,
                'params' => $base_params
            ];
        }

        $attempts[] = [
            'sql' => $base_component_sql . " ORDER BY id",
            'types' => $base_types,
            'params' => $base_params
        ];

        foreach ($attempts as $attempt) {
            $component_rows = $fetchComponentRows($conn, $attempt['sql'], $attempt['types'], $attempt['params']);
            if (!empty($component_rows)) {
                break;
            }
        }

        if (!empty($component_rows)) {
            $component_headers = [];
            $sum_allocated = 0.0;
            foreach ($component_rows as $row) {
                $component_name = $row['component_name'];
                $instances = isset($row['instances']) ? max(1, (int)$row['instances']) : 1;
                $marks_per_instance = isset($row['marks_per_instance']) ? (float)$row['marks_per_instance'] : 0.0;
                $raw_total = isset($row['total_marks']) ? (float)$row['total_marks'] : 0.0;
                if ($raw_total <= 0 && $marks_per_instance > 0) {
                    $raw_total = $marks_per_instance * $instances;
                }

                $scaled_total = isset($row['scaled_total_marks']) ? (float)$row['scaled_total_marks'] : 0.0;
                if ($scaled_total <= 0 && $raw_total > 0) {
                    $scaled_total = $raw_total;
                }

                $scale_ratio = ($raw_total > 0 && $scaled_total > 0)
                    ? ($scaled_total / $raw_total)
                    : 1.0;

                if (!in_array($component_name, $component_headers, true)) {
                    $component_headers[] = $component_name;
                }

                $component_maxes[$component_name] = $scaled_total;
                $component_ids[(int)$row['id']] = $component_name;
                $component_metadata[(int)$row['id']] = [
                    'name' => $component_name,
                    'raw_total' => $raw_total,
                    'scaled_total' => $scaled_total,
                    'scale_ratio' => $scale_ratio,
                    'instances' => $instances,
                    'marks_per_instance' => $marks_per_instance
                ];
                $sum_allocated += $scaled_total;
            }
            $total_allocated = max(0.0, $sum_allocated);
        }

        if (!empty($component_headers)) {
            $section_filter_sql = '';
            $section_filter_sql_with_alias = '';
            if (!empty($limit_sections)) {
                $section_list = implode(',', array_map('intval', $limit_sections));
                $section_filter_sql = " AND COALESCE(section_id, 0) IN (" . $section_list . ")";
                $section_filter_sql_with_alias = " AND COALESCE(st.section_id, 0) IN (" . $section_list . ")";
            }

            if ($subject_meta['type'] === 'elective') {
                $students_query = "SELECT st.id, st.name, st.roll_number
                                    FROM students st
                                    INNER JOIN student_elective_choices sec ON sec.student_id = st.id AND sec.subject_id = ?
                                    WHERE st.class_id = ?" . $section_filter_sql_with_alias . "
                                    ORDER BY st.roll_number ASC";
                $stmt_students = mysqli_prepare($conn, $students_query);
                mysqli_stmt_bind_param($stmt_students, "ii", $selected_subject, $selected_class);
            } else {
                $students_query = "SELECT id, name, roll_number FROM students WHERE class_id = ?" . $section_filter_sql . " ORDER BY roll_number ASC";
                $stmt_students = mysqli_prepare($conn, $students_query);
                mysqli_stmt_bind_param($stmt_students, "i", $selected_class);
            }
            mysqli_stmt_execute($stmt_students);
            $students_result = mysqli_stmt_get_result($stmt_students);
            $component_value_template = [];
            $component_absent_template = [];
            $component_recorded_template = [];
            $component_numeric_template = [];
            $component_absent_count_template = [];
            foreach ($component_headers as $header) {
                $component_value_template[$header] = 0.0;
                $component_absent_template[$header] = false;
                $component_recorded_template[$header] = false;
                $component_numeric_template[$header] = 0;
                $component_absent_count_template[$header] = 0;
            }

            while ($student = mysqli_fetch_assoc($students_result)) {
                $student_reports[$student['id']] = [
                    'name' => $student['name'],
                    'roll_number' => $student['roll_number'],
                    'components' => $component_value_template,
                    'component_absent' => $component_absent_template,
                    'component_recorded' => $component_recorded_template,
                    'component_numeric_counts' => $component_numeric_template,
                    'component_absent_counts' => $component_absent_count_template,
                    'final_mark' => 0,
                    'final_mark_display' => '0',
                    'standing' => '',
                    'standing_key' => '',
                    'has_records' => false,
                    'has_numeric_marks' => false
                ];
            }
            mysqli_stmt_close($stmt_students);

            if (!empty($component_ids) && !empty($student_reports)) {
                $student_ids_sql = implode(',', array_map('intval', array_keys($student_reports)));
                $marks_query = "SELECT ism.student_id, ic.id AS component_id, ism.marks
                                FROM ica_student_marks ism
                                JOIN ica_components ic ON ism.component_id = ic.id
                                WHERE ic.subject_id = ? AND ism.student_id IN ($student_ids_sql)";
                $stmt_marks = mysqli_prepare($conn, $marks_query);
                mysqli_stmt_bind_param($stmt_marks, "i", $selected_subject);
                mysqli_stmt_execute($stmt_marks);
                $marks_result = mysqli_stmt_get_result($stmt_marks);
                while ($marks_result && ($mark_row = mysqli_fetch_assoc($marks_result))) {
                    $comp_id = (int)$mark_row['component_id'];
                    if (!isset($component_ids[$comp_id])) {
                        continue;
                    }
                    $comp_name = $component_ids[$comp_id];
                    $student_id = (int)$mark_row['student_id'];
                    if (isset($student_reports[$student_id])) {
                        $student_reports[$student_id]['component_recorded'][$comp_name] = true;
                        $student_reports[$student_id]['has_records'] = true;
                        if ($mark_row['marks'] === null) {
                            $student_reports[$student_id]['component_absent_counts'][$comp_name]++;
                        } else {
                            $numeric_mark = (float)$mark_row['marks'];
                            $ratio = 1.0;
                            if (isset($component_metadata[$comp_id]['scale_ratio'])) {
                                $ratio = (float)$component_metadata[$comp_id]['scale_ratio'];
                            }
                            $scaled_value = $numeric_mark * $ratio;
                            $student_reports[$student_id]['components'][$comp_name] += $scaled_value;
                            if (isset($component_maxes[$comp_name]) && $component_maxes[$comp_name] > 0) {
                                $student_reports[$student_id]['components'][$comp_name] = min(
                                    $student_reports[$student_id]['components'][$comp_name],
                                    (float)$component_maxes[$comp_name]
                                );
                            }
                            $student_reports[$student_id]['component_numeric_counts'][$comp_name]++;
                        }
                    }
                }
                mysqli_stmt_close($stmt_marks);
            }

            // $total_allocated already reflects the matched component set

            foreach ($student_reports as $sid => &$sr) {
                $total_mark = 0.0;
                $has_numeric = false;
                foreach ($component_headers as $component_name) {
                    if (!array_key_exists($component_name, $sr['components'])) {
                        continue;
                    }
                    $total_mark += (float)$sr['components'][$component_name];
                    $absent_count = $sr['component_absent_counts'][$component_name] ?? 0;
                    $numeric_count = $sr['component_numeric_counts'][$component_name] ?? 0;
                    if ($sr['component_recorded'][$component_name] && $numeric_count === 0 && $absent_count > 0) {
                        $sr['component_absent'][$component_name] = true;
                    }
                    if ($numeric_count > 0) {
                        $has_numeric = true;
                    }
                }
                $sr['final_mark'] = $total_mark;
                if ($total_allocated > 0) {
                    $sr['final_mark'] = min($sr['final_mark'], (float)$total_allocated);
                }
                $sr['has_numeric_marks'] = $has_numeric;
                if (!$sr['has_records']) {
                    $sr['final_mark_display'] = 'N/A';
                } elseif (!$has_numeric) {
                    $sr['final_mark_display'] = 'AB';
                } else {
                    $sr['final_mark_display'] = format_mark_display($total_mark);
                }
            }
            unset($sr);

            $final_marks_list = [];
            $final_mark_values = [];
            foreach ($student_reports as $sid => $sr) {
                $final_marks_list[$sid] = (float)$sr['final_mark'];
                $final_mark_values[] = (float)$sr['final_mark'];
            }
            if (!empty($final_mark_values)) {
                $final_stats['average'] = round(array_sum($final_mark_values) / count($final_mark_values), 2);
                $final_stats['min'] = round(min($final_mark_values), 2);
                $final_stats['max'] = round(max($final_mark_values), 2);
            }

            foreach ($student_reports as $sid => &$sr) {
                $standing_key = determineStandingKey((float)$sr['final_mark'], (float)$total_allocated, (bool)$sr['has_records'], (bool)$sr['has_numeric_marks']);
                $sr['standing_key'] = $standing_key;
                $sr['standing'] = $standing_definitions[$standing_key]['label'] ?? '';
                if (isset($category_counts[$standing_key])) {
                    $category_counts[$standing_key]++;
                }
            }
            unset($sr);

            $effective_total = $total_allocated > 0 ? (float)$total_allocated : 50.0;
            $bin_count = 5;
            $step = $effective_total / $bin_count;
            if ($step < 1.0) {
                $step = 1.0;
            }

            $distribution_bins = [];
            $distribution_ranges = [];
            $lower_bound = 0.0;
            for ($i = 0; $i < $bin_count; $i++) {
                $upper_bound = ($i === $bin_count - 1) ? $effective_total : min($effective_total, $step * ($i + 1));
                if ($i === 0) {
                    $distribution_bins[] = '<=' . format_mark_display($upper_bound);
                    $distribution_ranges[] = ['type' => 'lt', 'max' => $upper_bound];
                } else {
                    $distribution_bins[] = format_mark_display($lower_bound) . '-' . format_mark_display($upper_bound);
                    $distribution_ranges[] = [
                        'type' => 'range',
                        'min' => $lower_bound,
                        'max' => $upper_bound,
                        'is_last' => ($i === $bin_count - 1)
                    ];
                }
                $lower_bound = $upper_bound;
            }

            $distribution_counts = array_fill(0, count($distribution_bins), 0);
            foreach ($final_marks_list as $fm) {
                $value = (float)$fm;
                $placed = false;
                foreach ($distribution_ranges as $idx => $range) {
                    if ($range['type'] === 'lt') {
                        if ($value < $range['max'] + 0.0001) {
                            $distribution_counts[$idx]++;
                            $placed = true;
                            break;
                        }
                    } else {
                        $min = $range['min'];
                        $max = $range['max'];
                        $is_last = !empty($range['is_last']);
                        $upper_check = $is_last ? ($value <= $max + 0.0001) : ($value < $max + 0.0001);
                        if ($value >= $min - 0.0001 && $upper_check) {
                            $distribution_counts[$idx]++;
                            $placed = true;
                            break;
                        }
                    }
                }

                if (!$placed && !empty($distribution_counts)) {
                    $distribution_counts[count($distribution_counts) - 1]++;
                }
            }

            $mean = 0;
            $stddev = 0;
            if (count($final_marks_list) > 0) {
                $mean = array_sum($final_marks_list) / count($final_marks_list);
                $variance = 0;
                foreach ($final_marks_list as $v) {
                    $variance += pow($v - $mean, 2);
                }
                $variance = $variance / count($final_marks_list);
                $stddev = sqrt($variance);
            }

            $js_distribution = json_encode(array_values($distribution_counts));
            $js_labels = json_encode(array_values($distribution_bins));
            $js_ranges = json_encode(array_values($distribution_ranges));
            $js_mean = json_encode($mean);
            $js_stddev = json_encode($stddev);

            $display_reports = $student_reports;
            if ($status_filter !== '') {
                $display_reports = array_filter($display_reports, static function (array $row) use ($status_filter) {
                    return isset($row['standing_key']) && $row['standing_key'] === $status_filter;
                });
            }
            $total_student_count = count($student_reports);

            $epsilon = 0.0001;
            if (isset($_GET['bin_index'])) {
                $bin_idx = max(0, (int)$_GET['bin_index']);
                if (isset($distribution_ranges[$bin_idx])) {
                    $range = $distribution_ranges[$bin_idx];
                    $active_bin_label = $distribution_bins[$bin_idx] ?? null;
                    $display_reports = array_filter(
                        $display_reports,
                        static function (array $row) use ($range, $epsilon) {
                            $value = isset($row['final_mark']) ? (float)$row['final_mark'] : 0.0;
                            if (($range['type'] ?? '') === 'lt') {
                                return $value < (($range['max'] ?? 0.0) + $epsilon);
                            }
                            $min = $range['min'] ?? 0.0;
                            $max = $range['max'] ?? 0.0;
                            $is_last = !empty($range['is_last']);
                            $upperCheck = $is_last ? ($value <= $max + $epsilon) : ($value < $max + $epsilon);
                            return $value >= $min - $epsilon && $upperCheck;
                        }
                    );
                }
            } elseif (isset($_GET['bin_min']) && isset($_GET['bin_max'])) {
                $bin_min = (float)$_GET['bin_min'];
                $bin_max = (float)$_GET['bin_max'];
                $active_bin_label = format_mark_display($bin_min) . ' - ' . format_mark_display($bin_max);
                $display_reports = array_filter(
                    $display_reports,
                    static function (array $row) use ($bin_min, $bin_max) {
                        $value = isset($row['final_mark']) ? (float)$row['final_mark'] : 0.0;
                        return $value >= $bin_min && $value <= $bin_max;
                    }
                );
            }

            $filtered_student_count = count($display_reports);
        }
    }
}
// --- HANDLE CSV EXPORT ---
if (isset($_GET['export']) && $_GET['export'] == 'csv_summary' && !empty($student_reports)) {
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

    if ($start_date_for_file === '') {
        $start_date_for_file = date('Y-m-d');
    }
    if ($end_date_for_file === '') {
        $end_date_for_file = $start_date_for_file;
    }

    $filename_parts = [];
    if ($school_for_file !== '') {
        $filename_parts[] = $format_segment($school_for_file);
    }
    if ($subject_name_for_file !== '') {
        $filename_parts[] = $format_segment($subject_name_for_file);
    }
    if ($class_name_for_file !== '') {
        $filename_parts[] = $format_segment($class_name_for_file);
    }
    $filename_parts[] = 'ICA_Marks';

    $date_segment = $start_date_for_file && $end_date_for_file ? $start_date_for_file . '_to_' . $end_date_for_file : date('Y-m-d');

    $filename = trim(implode('_', array_filter($filename_parts)), '_');
    if ($filename !== '') {
        $filename .= '_' . $date_segment . '.csv';
    } else {
        $filename = 'ICA_Marks_' . $date_segment . '.csv';
    }
    $filename = preg_replace('/_+/', '_', $filename);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    // Build CSV headers with component max marks if available
    $csv_comp_headers = [];
    if (!empty($component_headers) && isset($component_maxes)) {
        foreach ($component_headers as $ch) {
            $max = isset($component_maxes[$ch]) ? format_mark_display((float)$component_maxes[$ch]) : '';
            $csv_comp_headers[] = $ch . ($max !== '' ? " (/ $max)" : '');
        }
    }
    $final_den = isset($total_allocated) ? format_mark_display((float)$total_allocated) : '50';
    $csv_headers = array_merge(['Roll Number', 'Student Name'], $csv_comp_headers, ["Final ICA Mark (/ $final_den)", 'Standing'] );
    fputcsv($output, $csv_headers);
    foreach ($student_reports as $report_row) {
        $csv_row = [$report_row['roll_number'], $report_row['name']];
        foreach ($report_row['components'] as $component_name => $mark_value) {
            $csv_row[] = ($report_row['component_absent'][$component_name] ?? false)
                ? 'AB'
                : format_mark_display((float)$mark_value);
        }
        $csv_row[] = $report_row['final_mark_display'];
        $csv_row[] = $report_row['standing'] ?? '';
        fputcsv($output, $csv_row);
    }
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Reports - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    .print-only { display: none !important; }
    .print-inline-only { display: none !important; }
        .screen-only { display: block; }
        .report-logo { text-align: left; margin-bottom: 10px; }
        .report-logo img { max-height: 60px; }
    .marks-summary-row { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 12px; font-weight: 600; }
        .marks-summary-row span { background: #f5f5f5; padding: 6px 12px; border-radius: 6px; }
    .summary-print-row { justify-content: flex-end; }
        .report-stats-footer { margin-top: 18px; display: flex; flex-wrap: wrap; gap: 16px; font-weight: 600; }
        .report-stats-footer span { min-width: 160px; }
        .signature-block { margin-top: 14px; }
        .signature-line { margin-top: 8px; border-top: 1px solid #333; width: 280px; }
        .remarks-line { margin-top: 16px; border-top: 1px solid #333; width: 100%; padding-top: 6px; font-style: italic; }
        .signature-cell { min-width: 120px; }
        .status-filter-row { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin: 12px 0; }
        .status-filter-row span.label { font-weight: 600; }
        .status-filter-btn { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid #A6192E; border-radius: 18px; color: #A6192E; text-decoration: none; font-weight: 600; transition: background-color 0.2s ease, color 0.2s ease; }
        .status-filter-btn.active,
        .status-filter-btn:hover { background-color: #A6192E; color: #fff; }
        .status-filter-btn .count { font-size: 0.85em; opacity: 0.9; }
        .status-tag { display: inline-block; padding: 4px 10px; border-radius: 14px; font-weight: 600; font-size: 0.85em; background: #f1f1f1; color: #333; }
        .status-tag.status-at-risk { background: #fdecea; color: #c62828; }
        .status-tag.status-average { background: #fff7e6; color: #ef6c00; }
        .status-tag.status-good { background: #e8f5e9; color: #2e7d32; }
        .status-tag.status-absent { background: #f3e5f5; color: #6a1b9a; }
        th.standing-header { text-align: center; }
        td.standing-cell { text-align: center; }
        th.signature-header,
        td.signature-cell { display: none; }
        @media print {
            .print-only { display: block !important; }
            .print-inline-only { display: inline !important; }
            .screen-only { display: none !important; }
            .marks-summary-row span { background: none; border: 1px solid #ccc; }
            .report-logo { text-align: left; }
            th.signature-header,
            td.signature-cell { display: table-cell !important; }
            th.standing-header,
            td.standing-cell { display: none !important; }
            .status-filter-row { display: none !important; }
            .card { page-break-inside: avoid; }
            .sidebar { display: none !important; }
            .dashboard { display: block !important; }
            .main-content { width: 100%; margin: 0 !important; }
            body, html { height: auto; }
        }
    </style>
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
                <h2>Welcome, <?php echo htmlspecialchars($teacher_name_display !== '' ? $teacher_name_display : $teacher_name); ?>!</h2>
            </div>
            <div class="container">
                
                <div class="card">
                    <div class="report-logo print-only">
                        <img src="nmimslogo.png" alt="NMIMS Logo">
                    </div>
                    <div class="card-header"><h5>Student ICA Marks Summary</h5></div>
                    <div class="card-body">
                        <form method="GET" action="view_reports.php" id="filterForm">
                            <div class="form-group">
                                <label>Select Subject</label>
                                <select name="subject_id" onchange="this.form.submit()">
                                    <option value="">-- Select Subject --</option>
                                    <?php foreach ($subjects_list as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>" <?php if ($selected_subject == $subject['id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Select Class</label>
                                <select name="class_id" onchange="this.form.submit()">
                                    <option value="">-- Select Class --</option>
                                    <?php foreach ($classes_list as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" <?php if ($selected_class == $class['id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($class['class_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>

                        <?php if ($error_message !== ''): ?>
                            <div style="margin-top:15px; padding:12px; border:1px solid #d32f2f; border-radius:8px; color:#d32f2f; font-weight:600;">
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($selected_subject > 0 && $selected_class > 0 && $error_message === ''): ?>
                            <div class="actions" style="margin-top:20px; text-align:right;">
                                <a href="?export=csv_summary&subject_id=<?php echo $selected_subject; ?>&class_id=<?php echo $selected_class; ?>" class="btn">Download CSV</a>
                                <button onclick="printReport()" class="btn" id="printBtn">Print / Save as PDF</button>
                            </div>
                            <?php if (empty($component_headers)): ?>
                                <p style="text-align: center; margin-top: 20px; font-weight: bold;">No ICA components have been defined for this subject yet.</p>
                            <?php else: ?>
                                <?php if (!empty($student_reports)): ?>
                                    <?php if ($final_stats['average'] !== null): ?>
                                        <div class="marks-summary-row screen-only">
                                            <span>Average ICA Mark: <?php echo number_format($final_stats['average'], 2); ?></span>
                                            <span>Minimum ICA Mark: <?php echo number_format($final_stats['min'], 2); ?></span>
                                            <span>Maximum ICA Mark: <?php echo number_format($final_stats['max'], 2); ?></span>
                                        </div>
                                        <div class="marks-summary-row print-only summary-print-row">
                                            <span>Average ICA Mark: <?php echo number_format($final_stats['average'], 2); ?></span>
                                            <span>Minimum ICA Mark: <?php echo number_format($final_stats['min'], 2); ?></span>
                                            <span>Maximum ICA Mark: <?php echo number_format($final_stats['max'], 2); ?></span>
                                            <span>Faculty: <?php echo htmlspecialchars($teacher_name_display !== '' ? $teacher_name_display : $teacher_name); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="status-filter-row screen-only">
                                        <span class="label">Filter by standing:</span>
                                        <a class="status-filter-btn <?php echo $status_filter === '' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(buildStatusFilterUrl('')); ?>">
                                            All Students
                                            <span class="count"><?php echo $filtered_student_count; ?></span>
                                        </a>
                                        <?php foreach ($standing_definitions as $key => $definition): ?>
                                            <?php $isActive = ($status_filter === $key); ?>
                                            <a class="status-filter-btn <?php echo $isActive ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(buildStatusFilterUrl($key)); ?>">
                                                <?php echo htmlspecialchars($definition['label']); ?>
                                                <span class="count"><?php echo $category_counts[$key] ?? 0; ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                    <div style="margin-top:20px;">
                                        <p class="allocation-note" style="font-weight:bold;">Allocated marks available so far: <?php echo htmlspecialchars(format_mark_display((float)$total_allocated)); ?> out of 50.</p>
                                        <?php if ($active_bin_label !== null): ?>
                                            <p class="allocation-note" style="font-weight:bold;">Showing <?php echo $filtered_student_count; ?> of <?php echo $total_student_count; ?> students with final marks in the <?php echo htmlspecialchars($active_bin_label); ?> range. <a href="<?php echo htmlspecialchars(removeQueryParams(['bin_min','bin_max','bin_index'])); ?>">Show all students</a></p>
                                        <?php endif; ?>
                                        <canvas id="histChart" width="800" height="300"></canvas>
                                    </div>
                                <?php endif; ?>

                                <div id="report-table-container" style="overflow-x:auto;">
                                    <table style="margin-top: 20px;">
                                        <thead>
                                            <tr>
                                                <th>Roll Number</th>
                                                <th>Student Name</th>
                                                <?php foreach ($component_headers as $header): ?>
                                                    <th>
                                                        <?php echo htmlspecialchars($header);
                                                        if (isset($component_maxes[$header])) {
                                                            echo ' (/ ' . htmlspecialchars(format_mark_display((float)$component_maxes[$header])) . ')';
                                                        }
                                                        ?>
                                                    </th>
                                                <?php endforeach; ?>
                                                <th>Final ICA Mark (/ <?php echo htmlspecialchars(format_mark_display((float)$total_allocated)); ?>)</th>
                                                <th class="standing-header">Standing</th>
                                                <th class="signature-header">Signature</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($student_reports)): ?>
                                                <?php $empty_message = ($subject_meta['type'] === 'elective') ? 'No students have been assigned to this elective yet.' : 'No students found in this class.'; ?>
                                                <tr><td colspan="<?php echo count($component_headers) + 5; ?>" style="text-align: center; "><?php echo htmlspecialchars($empty_message); ?></td></tr>
                                            <?php else: ?>
                                                <?php if (empty($display_reports)): ?>
                                                    <tr><td colspan="<?php echo count($component_headers) + 5; ?>" style="text-align: center;">No students match the applied filters.</td></tr>
                                                <?php else: ?>
                                                    <?php foreach ($display_reports as $report_row): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($report_row['roll_number']); ?></td>
                                                            <td><?php echo htmlspecialchars($report_row['name']); ?></td>
                                                            <?php foreach ($report_row['components'] as $component_name => $mark_value): ?>
                                                                <?php
                                                                    $cell_display = ($report_row['component_absent'][$component_name] ?? false)
                                                                        ? 'AB'
                                                                        : format_mark_display((float)$mark_value);
                                                                ?>
                                                                <td><?php echo htmlspecialchars($cell_display); ?></td>
                                                            <?php endforeach; ?>
                                                            <td><strong><?php echo htmlspecialchars($report_row['final_mark_display']); ?></strong></td>
                                                            <td class="standing-cell">
                                                                <?php if (!empty($report_row['standing'])): ?>
                                                                    <?php $standingClass = 'status-' . str_replace('_', '-', $report_row['standing_key']); ?>
                                                                    <span class="status-tag <?php echo htmlspecialchars($standingClass); ?>"><?php echo htmlspecialchars($report_row['standing']); ?></span>
                                                                <?php else: ?>
                                                                    -
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="signature-cell"></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if ($final_stats['average'] !== null): ?>
                                    <div class="signature-block print-only">
                                        <div>Faculty Signature:</div>
                                        <div class="signature-line"></div>
                                    </div>
                                    <div class="remarks-line print-only">Remarks:</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php elseif ($selected_subject > 0 && $selected_class > 0 && $error_message !== ''): ?>
                            <!-- Error message displayed above -->
                        <?php else: ?>
                            <p style="text-align: center; margin-top: 20px; font-weight: bold;">Please select a subject and a class to view the final marks summary.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5>Syllabus Progress Report</h5></div>
                    <div class="card-body">
                        <?php if (empty($reports)): ?><p>No syllabus reports available.</p><?php else: ?>
                            <table>
                                <thead><tr><th>Subject</th><th>Timeline</th><th>Average Completion (%)</th></tr></thead>
                                <tbody>
                                    <?php foreach ($reports as $report): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($report['subject']); ?></td>
                                            <td><?php echo htmlspecialchars($report['timeline']); ?></td>
                                            <td><?php echo round($report['avg_completion'], 2); ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Chart data placeholders provided by PHP when available
        <?php if (isset($js_distribution)): ?>
            const distLabels = <?php echo $js_labels; ?>;
            const distCounts = <?php echo $js_distribution; ?>;
            const distRanges = <?php echo $js_ranges; ?>;
            const distMean = <?php echo $js_mean; ?>;
            const distStd = <?php echo $js_stddev; ?>;
        <?php else: ?>
            const distLabels = [];
            const distCounts = [];
            const distRanges = [];
            const distMean = 0;
            const distStd = 0;
        <?php endif; ?>

        function drawHistogram() {
            if (!distLabels.length) return;
            const ctx = document.getElementById('histChart').getContext('2d');
            // Build approximate normal curve values across the bin midpoints to scale overlay line
            const defaultStd = distStd > 0 ? distStd : 1;
            const midpoints = distRanges.length
                ? distRanges.map(range => {
                    if ((range.type || '') === 'lt') {
                        return (range.max || 0) / 2;
                    }
                    const min = range.min || 0;
                    const max = range.max || 0;
                    return (min + max) / 2;
                })
                : [5, 15, 25, 35, 45];
            const pdf = midpoints.map(x => {
                const exponent = -0.5 * Math.pow((x - distMean) / defaultStd, 2);
                const coefficient = 1 / (Math.sqrt(2 * Math.PI) * defaultStd);
                return coefficient * Math.exp(exponent);
            });
            const maxPdf = Math.max(...pdf);
            const maxCount = Math.max(...distCounts, 1);
            const scale = maxCount / (maxPdf || 1);
            const scaledPdf = pdf.map(p => p * scale);

            const data = {
                labels: distLabels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Number of Students',
                        data: distCounts,
                        backgroundColor: 'rgba(166,25,46,0.7)'
                    },
                    {
                        type: 'line',
                        label: 'Normal curve (approx)',
                        data: scaledPdf,
                        borderColor: 'rgba(54,162,235,0.9)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3
                    }
                ]
            };

            const histChart = new Chart(ctx, {
                data: data,
                options: {
                    responsive: true,
                    onHover: (evt, elements, chart) => {
                        const cursor = elements.length ? 'pointer' : 'default';
                        if (evt?.native?.target) {
                            evt.native.target.style.cursor = cursor;
                        }
                    },
                    onClick: (evt, elements, chart) => {
                        const points = chart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
                        if (points.length) {
                            const idx = points[0].index;
                            const params = new URLSearchParams(window.location.search);
                            params.set('bin_index', idx);
                            params.delete('bin_min');
                            params.delete('bin_max');
                            window.location.search = params.toString();
                        }
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', drawHistogram);
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        }
        if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');

        function ensurePrintStyles() {
            if (document.getElementById('report-print-style')) {
                return;
            }
            const style = document.createElement('style');
            style.id = 'report-print-style';
            style.textContent = `
                @media print {
                    body {
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                        background: #fff;
                        margin: 0;
                    }
                    body > *:not(#print-area) {
                        display: none !important;
                    }
                    #print-area {
                        display: block !important;
                        width: 100%;
                        margin: 0;
                        padding: 24px;
                        background: #fff;
                        color: #000;
                        font-family: Arial, sans-serif;
                    }
                    #print-area * {
                        visibility: visible !important;
                    }
                    #print-area table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 20px;
                        border: 1px solid #333;
                    }
                    #print-area th,
                    #print-area td {
                        border: 1px solid #333 !important;
                        padding: 6px;
                        text-align: left;
                        color: #000 !important;
                        background: #fff !important;
                        font-size: 11px;
                    }
                    #print-area th {
                        background: #8b0000 !important;
                        color: #fff !important;
                        font-weight: 600;
                    }
                    #print-area tbody tr:nth-child(odd) td {
                        background: #fafafa !important;
                    }
                    #print-area .print-header {
                        display: flex;
                        flex-direction: column;
                        gap: 16px;
                    }
                    #print-area .print-branding {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        border-bottom: 2px solid #8b0000;
                        padding-bottom: 12px;
                    }
                    #print-area .print-logo {
                        height: 90px;
                        max-width: 260px;
                        object-fit: contain;
                    }
                    #print-area .print-title-block {
                        text-align: right;
                        font-family: 'Times New Roman', serif;
                    }
                    #print-area .print-title-block h1 {
                        margin: 0;
                        font-size: 20px;
                        letter-spacing: 0.08em;
                        color: #8b0000;
                    }
                    #print-area .print-title-block h2 {
                        margin: 6px 0 0;
                        font-size: 18px;
                        color: #000;
                    }
                    #print-area .print-meta {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 12px;
                        font-size: 12px;
                    }
                    #print-area .print-meta td {
                        border: 1px solid #333;
                        padding: 6px 10px;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        function printReport() {
            const container = document.getElementById('report-table-container');
            if (!container || !container.innerHTML.trim()) {
                alert('Nothing to print yet. Please load the report first.');
                return;
            }

            ensurePrintStyles();

            const existingArea = document.getElementById('print-area');
            if (existingArea) {
                existingArea.remove();
            }

            const subjectName = "<?php echo addslashes($subject_name_for_file); ?>";
            const className = "<?php echo addslashes($class_label_for_print !== '' ? $class_label_for_print : $class_name_for_file); ?>";
            const startDate = "<?php echo addslashes($start_date_display_for_file); ?>";
            const endDate = "<?php echo addslashes($end_date_display_for_file); ?>";
            const reportLabel = "<?php echo addslashes($reporting_label_for_file); ?>";
            const teacherName = "<?php echo addslashes($teacher_name_display); ?>";
            const schoolName = "<?php echo addslashes($school_for_file); ?>";

            const escapeHtmlValue = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            })[char] || char);

            const reportingText = (() => {
                const hasDates = startDate && endDate;
                const dateRange = hasDates ? `${startDate} to ${endDate}` : '';
                const labelHasDates = reportLabel && hasDates && reportLabel.includes(startDate) && reportLabel.includes(endDate);
                if (reportLabel && dateRange) {
                    return labelHasDates ? reportLabel : `${reportLabel} (${dateRange})`;
                }
                if (reportLabel) {
                    return reportLabel;
                }
                if (dateRange) {
                    return dateRange;
                }
                return 'Not specified';
            })();

            const subjectDisplay = subjectName ? escapeHtmlValue(subjectName) : 'N/A';
            const classDisplay = className ? escapeHtmlValue(className) : 'N/A';
            const teacherDisplay = teacherName ? escapeHtmlValue(teacherName) : 'N/A';
            const reportingDisplay = reportingText ? escapeHtmlValue(reportingText) : 'Not specified';

            const headerHtml = `
                <div class="print-header">
                    <div class="print-branding">
                        <img src="nmimshorizontal.jpg" alt="NMIMS Logo" class="print-logo">
                        <div class="print-title-block">
                            <h1>SVKM'S NMIMS</h1>
                            <h2>Student Marks Summary</h2>
                        </div>
                    </div>
                    <table class="print-meta">
                        <tbody>
                            <tr>
                                <td><strong>Subject:</strong> ${subjectDisplay}</td>
                                <td><strong>Faculty:</strong> ${teacherDisplay}</td>
                            </tr>
                            <tr>
                                <td><strong>Class:</strong> ${classDisplay}</td>
                                <td><strong>Reporting period:</strong> ${reportingDisplay}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;

            const printArea = document.createElement('div');
            printArea.id = 'print-area';
            printArea.style.display = 'none';
            const sanitizedTableHtml = container.innerHTML
                .replace(/<tfoot[\s\S]*?<\/tfoot>/gi, '')
                .replace(/<div[^>]*class="[^"]*report-footer[^"]*"[\s\S]*?<\/div>/gi, '');
            printArea.innerHTML = `${headerHtml}${sanitizedTableHtml}`;
            document.body.appendChild(printArea);

            const cleanUp = () => {
                if (printArea.parentNode) {
                    printArea.parentNode.removeChild(printArea);
                }
                window.removeEventListener('afterprint', cleanUp);
            };

            window.addEventListener('afterprint', cleanUp);

            setTimeout(() => {
                window.print();
            }, 50);
        }
    </script>
</body>
</html>
