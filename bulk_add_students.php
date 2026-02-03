<?php
session_start();
include 'db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

$class_meta_map = [];
$class_sections_map = [];
$semesters_by_school = [];

function ensure_class_timetable_column(mysqli $conn, string $column, string $definition): void
{
    $safeColumn = preg_replace('/[^A-Za-z0-9_]/', '', $column);
    if ($safeColumn === '') {
        return;
    }

    $existsSql = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'class_timetables' AND COLUMN_NAME = '" . mysqli_real_escape_string($conn, $safeColumn) . "'";
    $existsResult = mysqli_query($conn, $existsSql);
    if ($existsResult) {
        $row = mysqli_fetch_assoc($existsResult);
        mysqli_free_result($existsResult);
        if ((int)($row['cnt'] ?? 0) > 0) {
            return;
        }
    }

    @mysqli_query($conn, "ALTER TABLE `class_timetables` ADD COLUMN `{$safeColumn}` {$definition}");
}

ensure_class_timetable_column($conn, 'timeline', "VARCHAR(50) DEFAULT NULL");
ensure_class_timetable_column($conn, 'is_broadcast', 'TINYINT(1) NOT NULL DEFAULT 0');
ensure_class_timetable_column($conn, 'broadcast_token', "VARCHAR(64) DEFAULT NULL");

function format_timetable_timeline_label(string $timeline): string
{
    $trimmed = trim($timeline);
    if ($trimmed === '') {
        return 'General';
    }
    if (preg_match('/^week_(\d+)$/i', $trimmed, $match)) {
        return 'Week ' . (int)$match[1];
    }
    $normalized = str_replace(['_', '-'], ' ', $trimmed);
    return ucwords($normalized);
}

function generate_timetable_week_options(int $maxWeeks = 20): array
{
    $options = [];
    for ($i = 1; $i <= max(1, $maxWeeks); $i++) {
        $value = 'week_' . $i;
        $options[$value] = 'Week ' . $i;
    }
    return $options;
}

$timeline_options = generate_timetable_week_options(20);

$class_sql = "SELECT c.id, c.class_name, c.school, c.semester, s.id AS section_id, s.section_name\n              FROM classes c\n              LEFT JOIN sections s ON s.class_id = c.id\n              ORDER BY c.school, c.semester, c.class_name, s.section_name";
$class_result = mysqli_query($conn, $class_sql);
if ($class_result) {
    while ($row = mysqli_fetch_assoc($class_result)) {
        $class_id = (int)($row['id'] ?? 0);
        $school = trim((string)($row['school'] ?? ''));
        $semester = trim((string)($row['semester'] ?? ''));
        $section_id = isset($row['section_id']) ? (int)$row['section_id'] : 0;
        $section_name = trim((string)($row['section_name'] ?? ''));

        if (!isset($class_meta_map[$class_id])) {
            $baseClassName = $row['class_name'] ?? '';
            $class_meta_map[$class_id] = [
                'id' => $class_id,
                'class_name' => $baseClassName,
                'school' => $school,
                'semester' => $semester,
                'label' => format_class_label($baseClassName, '', $semester, $school)
            ];
        }

        if ($school !== '') {
            if (!isset($semesters_by_school[$school])) {
                $semesters_by_school[$school] = [];
            }
            if ($semester !== '' && !in_array($semester, $semesters_by_school[$school], true)) {
                $semesters_by_school[$school][] = $semester;
            }
        }

        if (!isset($class_sections_map[$class_id])) {
            $class_sections_map[$class_id] = [];
        }

        if ($section_id > 0 && !isset($class_sections_map[$class_id][$section_id])) {
            $class_sections_map[$class_id][$section_id] = [
                'id' => $section_id,
                'name' => $section_name,
                'label' => format_class_label($class_meta_map[$class_id]['class_name'] ?? '', $section_name, $semester, $school)
            ];
        }
    }
    mysqli_free_result($class_result);
}

foreach ($class_sections_map as $cid => $sections) {
    $class_sections_map[$cid] = array_values($sections);
}

function respond_json_error($message, $code = 400)
{
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function respond_json_success($payload = [])
{
    echo json_encode(array_merge(['success' => true], $payload));
    exit;
}

function handle_custom_section($conn, $class_id, $section_name)
{
    $class_id = (int)$class_id;
    $section_name = trim((string)$section_name);

    if ($class_id <= 0 || $section_name === '') {
        return null;
    }

    $select_sql = "SELECT id FROM sections WHERE class_id = ? AND section_name = ? LIMIT 1";
    $select_stmt = mysqli_prepare($conn, $select_sql);
    if (!$select_stmt) {
        return null;
    }

    mysqli_stmt_bind_param($select_stmt, "is", $class_id, $section_name);
    mysqli_stmt_execute($select_stmt);
    mysqli_stmt_bind_result($select_stmt, $existing_id);
    $has_existing = mysqli_stmt_fetch($select_stmt);
    mysqli_stmt_close($select_stmt);

    if ($has_existing) {
        return (int)$existing_id;
    }

    $insert_sql = "INSERT INTO sections (class_id, section_name) VALUES (?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    if (!$insert_stmt) {
        return null;
    }

    mysqli_stmt_bind_param($insert_stmt, "is", $class_id, $section_name);
    if (!mysqli_stmt_execute($insert_stmt)) {
        mysqli_stmt_close($insert_stmt);
        return null;
    }

    $new_id = mysqli_insert_id($conn);
    mysqli_stmt_close($insert_stmt);

    return $new_id ? (int)$new_id : null;
}

function upsert_student($conn, $sap_id, $full_name, $roll_number, $class_id, $section_id = null)
{
    $sap_id = trim((string)$sap_id);
    $full_name = trim((string)$full_name);
    $roll_number = trim((string)$roll_number);
    $class_id = (int)$class_id;
    $section_id = $section_id !== null ? (int)$section_id : null;

    if ($sap_id === '' || $full_name === '' || $roll_number === '' || $class_id <= 0) {
        return 'error';
    }

    $section_for_query = $section_id ? $section_id : 0;

    $current_stmt = mysqli_prepare($conn, "SELECT id FROM students WHERE sap_id = ? AND class_id = ? LIMIT 1");
    if (!$current_stmt) {
        return 'error';
    }
    mysqli_stmt_bind_param($current_stmt, "si", $sap_id, $class_id);
    mysqli_stmt_execute($current_stmt);
    mysqli_stmt_bind_result($current_stmt, $existing_id);
    $found_current = mysqli_stmt_fetch($current_stmt);
    mysqli_stmt_close($current_stmt);

    if ($found_current) {
        $student_id = (int)$existing_id;
        $update_sql = "UPDATE students SET name = ?, roll_number = ?, section_id = NULLIF(?, 0) WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        if (!$update_stmt) {
            return 'error';
        }
        mysqli_stmt_bind_param($update_stmt, "ssii", $full_name, $roll_number, $section_for_query, $student_id);
        $ok = mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
        return $ok ? 'updated' : 'error';
    }

    $insert_sql = "INSERT INTO students (sap_id, name, roll_number, class_id, section_id) VALUES (?, ?, ?, ?, NULLIF(?, 0))";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    if (!$insert_stmt) {
        return 'error';
    }

    mysqli_stmt_bind_param($insert_stmt, "sssii", $sap_id, $full_name, $roll_number, $class_id, $section_for_query);
    $ok = mysqli_stmt_execute($insert_stmt);
    mysqli_stmt_close($insert_stmt);

    return $ok ? 'inserted' : 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'update_student_inline') {
        $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
        $full_name = trim($_POST['full_name'] ?? '');
        $sap_id = trim($_POST['sap_id'] ?? '');
        $roll_number = trim($_POST['roll_number'] ?? '');
        $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
        $section_mode = $_POST['section_mode'] ?? 'none';
        $section_raw = $_POST['section_id'] ?? '';
        $section_custom = trim($_POST['section_name_custom'] ?? '');

        if ($student_id <= 0) {
            respond_json_error('Invalid student selection.');
        }
        if ($full_name === '' || $sap_id === '' || $roll_number === '') {
            respond_json_error('Full name, SAP ID, and roll number are required.');
        }
        if ($class_id <= 0 || !isset($class_meta_map[$class_id])) {
            respond_json_error('Please choose a valid class.');
        }

        $pending_section_id = null;

        if ($section_mode === 'custom') {
            if ($section_custom === '') {
                respond_json_error('Please provide a name for the new division.');
            }
            $pending_section_id = handle_custom_section($conn, $class_id, $section_custom);
            if (!$pending_section_id) {
                respond_json_error('Unable to create the requested division.');
            }
            $class_base = $class_meta_map[$class_id] ?? [];
            $class_sections_map[$class_id][] = [
                'id' => $pending_section_id,
                'name' => $section_custom,
                'label' => format_class_label($class_base['class_name'] ?? '', $section_custom, $class_base['semester'] ?? '', $class_base['school'] ?? '')
            ];
        } elseif ($section_mode === 'existing') {
            $pending_section_id = (int)$section_raw;
            if ($pending_section_id > 0) {
                $valid_sections = array_map(function ($entry) {
                    return (int)$entry['id'];
                }, $class_sections_map[$class_id] ?? []);
                if (!in_array($pending_section_id, $valid_sections, true)) {
                    respond_json_error('Selected division does not belong to the chosen class.');
                }
            } else {
                $pending_section_id = null;
            }
        } else {
            $pending_section_id = null;
        }

        $dup_sql = "SELECT id FROM students WHERE sap_id = ? AND class_id = ? AND id <> ? LIMIT 1";
        $dup_stmt = mysqli_prepare($conn, $dup_sql);
        if (!$dup_stmt) {
            respond_json_error('Unable to verify SAP ID uniqueness.');
        }
        mysqli_stmt_bind_param($dup_stmt, "sii", $sap_id, $class_id, $student_id);
        mysqli_stmt_execute($dup_stmt);
        mysqli_stmt_store_result($dup_stmt);
        $has_duplicate = mysqli_stmt_num_rows($dup_stmt) > 0;
        mysqli_stmt_close($dup_stmt);
        if ($has_duplicate) {
            respond_json_error('Another student already uses this SAP ID.');
        }

        if (!mysqli_begin_transaction($conn)) {
            respond_json_error('Unable to start the update transaction.');
        }

        $section_for_update = $pending_section_id ? $pending_section_id : 0;

        $update_sql = "UPDATE students SET sap_id = ?, roll_number = ?, name = ?, class_id = ?, section_id = NULLIF(?, 0) WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        if (!$update_stmt) {
            mysqli_rollback($conn);
            respond_json_error('Unable to prepare the student update.');
        }
        mysqli_stmt_bind_param($update_stmt, "sssiii", $sap_id, $roll_number, $full_name, $class_id, $section_for_update, $student_id);
        if (!mysqli_stmt_execute($update_stmt)) {
            mysqli_stmt_close($update_stmt);
            mysqli_rollback($conn);
            respond_json_error('Unable to save the student changes.');
        }
        mysqli_stmt_close($update_stmt);

        $elective_stmt = mysqli_prepare($conn, "UPDATE student_elective_choices SET class_id = ? WHERE student_id = ?");
        if ($elective_stmt) {
            mysqli_stmt_bind_param($elective_stmt, "ii", $class_id, $student_id);
            mysqli_stmt_execute($elective_stmt);
            mysqli_stmt_close($elective_stmt);
        }

        if (!mysqli_commit($conn)) {
            mysqli_rollback($conn);
            respond_json_error('Unable to complete the update.');
        }

        $fetch_sql = "SELECT st.id, st.sap_id, st.roll_number, st.name, st.class_id, st.section_id, c.class_name, c.school, c.semester, sec.section_name\n                      FROM students st\n                      JOIN classes c ON st.class_id = c.id\n                      LEFT JOIN sections sec ON st.section_id = sec.id\n                      WHERE st.id = ?";
        $fetch_stmt = mysqli_prepare($conn, $fetch_sql);
        if (!$fetch_stmt) {
            respond_json_error('Unable to load the updated student.');
        }
        mysqli_stmt_bind_param($fetch_stmt, "i", $student_id);
        mysqli_stmt_execute($fetch_stmt);
        $fetch_result = mysqli_stmt_get_result($fetch_stmt);
        $updated_student = $fetch_result ? mysqli_fetch_assoc($fetch_result) : null;
        mysqli_stmt_close($fetch_stmt);

        if (!$updated_student) {
            respond_json_error('Unable to fetch the updated student record.');
        }

        respond_json_success([
            'message' => 'Student updated successfully.',
            'student' => $updated_student
        ]);
    } elseif ($action === 'delete_student_inline') {
        $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
        if ($student_id <= 0) {
            respond_json_error('Invalid student selection.');
        }

        if (!mysqli_begin_transaction($conn)) {
            respond_json_error('Unable to start the delete transaction.');
        }

        $delete_marks_stmt = mysqli_prepare($conn, "DELETE FROM ica_student_marks WHERE student_id = ?");
        if ($delete_marks_stmt) {
            mysqli_stmt_bind_param($delete_marks_stmt, "i", $student_id);
            if (!mysqli_stmt_execute($delete_marks_stmt)) {
                mysqli_stmt_close($delete_marks_stmt);
                mysqli_rollback($conn);
                respond_json_error('Unable to remove associated ICA marks.');
            }
            mysqli_stmt_close($delete_marks_stmt);
        }

        $delete_elective_stmt = mysqli_prepare($conn, "DELETE FROM student_elective_choices WHERE student_id = ?");
        if ($delete_elective_stmt) {
            mysqli_stmt_bind_param($delete_elective_stmt, "i", $student_id);
            if (!mysqli_stmt_execute($delete_elective_stmt)) {
                mysqli_stmt_close($delete_elective_stmt);
                mysqli_rollback($conn);
                respond_json_error('Unable to remove associated elective mappings.');
            }
            mysqli_stmt_close($delete_elective_stmt);
        }

        $delete_student_stmt = mysqli_prepare($conn, "DELETE FROM students WHERE id = ?");
        if (!$delete_student_stmt) {
            mysqli_rollback($conn);
            respond_json_error('Unable to prepare the student deletion.');
        }
        mysqli_stmt_bind_param($delete_student_stmt, "i", $student_id);
        if (!mysqli_stmt_execute($delete_student_stmt)) {
            mysqli_stmt_close($delete_student_stmt);
            mysqli_rollback($conn);
            respond_json_error('Unable to delete the student.');
        }
        mysqli_stmt_close($delete_student_stmt);

        if (!mysqli_commit($conn)) {
            mysqli_rollback($conn);
            respond_json_error('Unable to complete the deletion.');
        }

        respond_json_success(['message' => 'Student deleted successfully.']);
    } else {
        respond_json_error('Unsupported action requested.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['copy_students_submit'])) {
    $source_class_id = isset($_POST['copy_source_class']) ? (int)$_POST['copy_source_class'] : 0;
    $target_class_id = isset($_POST['copy_target_class']) ? (int)$_POST['copy_target_class'] : 0;

    if ($source_class_id <= 0 || $target_class_id <= 0) {
        $error = $error ? $error . ' ' : '';
        $error .= 'Please choose both a source class and a destination class before copying.';
    } elseif ($source_class_id === $target_class_id) {
        $error = $error ? $error . ' ' : '';
        $error .= 'Source and destination classes must be different.';
    } else {
        $class_lookup_stmt = mysqli_prepare($conn, "SELECT id, class_name, school FROM classes WHERE id = ?");
        $source_class = null;
        $target_class = null;
        if ($class_lookup_stmt) {
            mysqli_stmt_bind_param($class_lookup_stmt, "i", $source_class_id);
            mysqli_stmt_execute($class_lookup_stmt);
            $result = mysqli_stmt_get_result($class_lookup_stmt);
            $source_class = $result ? mysqli_fetch_assoc($result) : null;
            if ($result) {
                mysqli_free_result($result);
            }
            mysqli_stmt_bind_param($class_lookup_stmt, "i", $target_class_id);
            mysqli_stmt_execute($class_lookup_stmt);
            $result = mysqli_stmt_get_result($class_lookup_stmt);
            $target_class = $result ? mysqli_fetch_assoc($result) : null;
            if ($result) {
                mysqli_free_result($result);
            }
            mysqli_stmt_close($class_lookup_stmt);
        }

        if (!$source_class || !$target_class) {
            $error = $error ? $error . ' ' : '';
            $error .= 'Unable to load the selected classes. Please try again.';
        } else {
            if (!mysqli_begin_transaction($conn)) {
                $error = $error ? $error . ' ' : '';
                $error .= 'Unable to start the copy transaction.';
            } else {
                $section_map = [];
                $source_sections = [];
                $section_stmt = mysqli_prepare($conn, "SELECT id, section_name FROM sections WHERE class_id = ?");
                if ($section_stmt) {
                    mysqli_stmt_bind_param($section_stmt, "i", $source_class_id);
                    mysqli_stmt_execute($section_stmt);
                    $res = mysqli_stmt_get_result($section_stmt);
                    while ($res && ($row = mysqli_fetch_assoc($res))) {
                        $source_sections[] = $row;
                    }
                    if ($res) {
                        mysqli_free_result($res);
                    }
                    mysqli_stmt_close($section_stmt);
                }

                $target_sections_by_name = [];
                $target_sections_stmt = mysqli_prepare($conn, "SELECT id, section_name FROM sections WHERE class_id = ?");
                if ($target_sections_stmt) {
                    mysqli_stmt_bind_param($target_sections_stmt, "i", $target_class_id);
                    mysqli_stmt_execute($target_sections_stmt);
                    $res = mysqli_stmt_get_result($target_sections_stmt);
                    while ($res && ($row = mysqli_fetch_assoc($res))) {
                        $name_key = strtolower(trim((string)$row['section_name']));
                        if ($name_key !== '') {
                            $target_sections_by_name[$name_key] = (int)$row['id'];
                        }
                    }
                    if ($res) {
                        mysqli_free_result($res);
                    }
                    mysqli_stmt_close($target_sections_stmt);
                }

                $section_insert_stmt = mysqli_prepare($conn, "INSERT INTO sections (class_id, section_name) VALUES (?, ?)");
                if ($section_insert_stmt) {
                    foreach ($source_sections as $section) {
                        $source_section_id = (int)$section['id'];
                        $section_name = trim((string)$section['section_name']);
                        if ($section_name === '') {
                            $section_map[$source_section_id] = null;
                            continue;
                        }
                        $name_key = strtolower($section_name);
                        if (!isset($target_sections_by_name[$name_key])) {
                            mysqli_stmt_bind_param($section_insert_stmt, "is", $target_class_id, $section_name);
                            if (!mysqli_stmt_execute($section_insert_stmt)) {
                                mysqli_stmt_close($section_insert_stmt);
                                mysqli_rollback($conn);
                                $error = $error ? $error . ' ' : '';
                                $error .= 'Unable to create matching divisions in the destination class.';
                                goto copy_students_end;
                            }
                            $new_section_id = mysqli_insert_id($conn);
                            $target_sections_by_name[$name_key] = $new_section_id;
                        }
                        $section_map[$source_section_id] = $target_sections_by_name[$name_key];
                    }
                    mysqli_stmt_close($section_insert_stmt);
                } else {
                    mysqli_rollback($conn);
                    $error = $error ? $error . ' ' : '';
                    $error .= 'Unable to prepare division creation during copy.';
                    goto copy_students_end;
                }

                $students = [];
                $student_stmt = mysqli_prepare($conn, "SELECT name, roll_number, sap_id, section_id, college_email FROM students WHERE class_id = ?");
                if ($student_stmt) {
                    mysqli_stmt_bind_param($student_stmt, "i", $source_class_id);
                    mysqli_stmt_execute($student_stmt);
                    $res = mysqli_stmt_get_result($student_stmt);
                    while ($res && ($row = mysqli_fetch_assoc($res))) {
                        $students[] = $row;
                    }
                    if ($res) {
                        mysqli_free_result($res);
                    }
                    mysqli_stmt_close($student_stmt);
                }

                if (empty($students)) {
                    mysqli_commit($conn);
                    $success = $success ? $success . ' ' : '';
                    $success .= 'No students found in the source class to copy.';
                } else {
                    $check_stmt = mysqli_prepare($conn, "SELECT id FROM students WHERE sap_id = ? AND class_id = ? LIMIT 1");
                    $insert_stmt = mysqli_prepare($conn, "INSERT INTO students (name, roll_number, sap_id, class_id, section_id, college_email) VALUES (?, ?, ?, ?, ?, ?)");
                    if (!$check_stmt || !$insert_stmt) {
                        if ($check_stmt) {
                            mysqli_stmt_close($check_stmt);
                        }
                        if ($insert_stmt) {
                            mysqli_stmt_close($insert_stmt);
                        }
                        mysqli_rollback($conn);
                        $error = $error ? $error . ' ' : '';
                        $error .= 'Unable to prepare the copy statements.';
                    } else {
                        $check_sap = '';
                        $insert_name = '';
                        $insert_roll = '';
                        $insert_sap = '';
                        $insert_class_id = $target_class_id;
                        $insert_section_id = null;
                        $insert_email = null;

                        mysqli_stmt_bind_param($check_stmt, "si", $check_sap, $insert_class_id);
                        mysqli_stmt_bind_param($insert_stmt, "sssiss", $insert_name, $insert_roll, $insert_sap, $insert_class_id, $insert_section_id, $insert_email);

                        $inserted_count = 0;
                        $skipped_count = 0;
                        foreach ($students as $student_row) {
                            $check_sap = (string)($student_row['sap_id'] ?? '');
                            mysqli_stmt_execute($check_stmt);
                            mysqli_stmt_store_result($check_stmt);
                            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                                $skipped_count++;
                                mysqli_stmt_free_result($check_stmt);
                                continue;
                            }
                            mysqli_stmt_free_result($check_stmt);

                            $insert_name = $student_row['name'] ?? '';
                            $insert_roll = $student_row['roll_number'] ?? '';
                            $insert_sap = (string)($student_row['sap_id'] ?? '');
                            $insert_section_id = null;
                            $source_section_id = isset($student_row['section_id']) ? (int)$student_row['section_id'] : 0;
                            if ($source_section_id > 0 && isset($section_map[$source_section_id])) {
                                $insert_section_id = $section_map[$source_section_id];
                            }
                            $insert_email = isset($student_row['college_email']) && $student_row['college_email'] !== ''
                                ? $student_row['college_email']
                                : null;

                            if (!mysqli_stmt_execute($insert_stmt)) {
                                mysqli_stmt_close($check_stmt);
                                mysqli_stmt_close($insert_stmt);
                                mysqli_rollback($conn);
                                $error = $error ? $error . ' ' : '';
                                $error .= 'Copy failed while inserting student records.';
                                goto copy_students_end;
                            }
                            $inserted_count++;
                        }

                        mysqli_stmt_close($check_stmt);
                        mysqli_stmt_close($insert_stmt);

                        if (!mysqli_commit($conn)) {
                            mysqli_rollback($conn);
                            $error = $error ? $error . ' ' : '';
                            $error .= 'Unable to finalise the student copy.';
                        } else {
                            $success = $success ? $success . ' ' : '';
                            $success .= sprintf(
                                'Copied %d student(s) from %s to %s.%s',
                                $inserted_count,
                                $source_class['class_name'] ?? 'source class',
                                $target_class['class_name'] ?? 'destination class',
                                $skipped_count > 0 ? ' Skipped ' . $skipped_count . ' duplicate SAP ID(s) already present in the destination.' : ''
                            );
                        }
                    }
                }
            }
        }
    }
}

copy_students_end:

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_global_timetable'])) {
    $timeline_key = trim((string)($_POST['timeline_key'] ?? ''));
    if ($timeline_key === '') {
        $error = $error ? $error . ' ' : '';
        $error .= 'Please choose a timeline before uploading the timetable.';
    } elseif (!preg_match('/^[A-Za-z0-9_\-]+$/', $timeline_key)) {
        $error = $error ? $error . ' ' : '';
        $error .= 'Timeline contains unsupported characters. Use letters, numbers, dashes, or underscores only.';
    } elseif (!isset($_FILES['global_timetable_file']) || $_FILES['global_timetable_file']['error'] !== UPLOAD_ERR_OK) {
        $error = $error ? $error . ' ' : '';
        $error .= 'Please choose a file to broadcast to all classes.';
    } elseif (empty($class_meta_map)) {
        $error = $error ? $error . ' ' : '';
        $error .= 'No classes are available to receive the timetable broadcast.';
    } else {
        $upload_dir = __DIR__ . '/uploads/class_timetables/';
        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true) && !is_dir($upload_dir)) {
            $error = $error ? $error . ' ' : '';
            $error .= 'Failed to create the timetable upload directory.';
        } else {
            $original_name = $_FILES['global_timetable_file']['name'] ?? 'broadcast_timetable';
            $safe_display_name = trim($original_name) !== '' ? $original_name : 'broadcast_timetable';
            $extension = pathinfo($original_name, PATHINFO_EXTENSION);
            try {
                $random_segment = bin2hex(random_bytes(4));
            } catch (Exception $e) {
                $random_segment = uniqid();
            }
            $timeline_slug = strtolower($timeline_key);
            $sanitized_extension = $extension !== '' ? '.' . preg_replace('/[^A-Za-z0-9]/', '', $extension) : '';
            $new_filename = 'broadcast_' . $timeline_slug . '_' . date('YmdHis') . '_' . $random_segment . $sanitized_extension;
            $target_path = $upload_dir . $new_filename;
            $relative_path = 'uploads/class_timetables/' . $new_filename;

            if (!move_uploaded_file($_FILES['global_timetable_file']['tmp_name'], $target_path)) {
                $error = $error ? $error . ' ' : '';
                $error .= 'Failed to upload the timetable file. Please try again.';
            } else {
                $existing_file_paths = [];
                $existing_stmt = mysqli_prepare($conn, "SELECT file_path FROM class_timetables WHERE timeline = ? AND is_broadcast = 1");
                if ($existing_stmt) {
                    mysqli_stmt_bind_param($existing_stmt, 's', $timeline_key);
                    mysqli_stmt_execute($existing_stmt);
                    $res = mysqli_stmt_get_result($existing_stmt);
                    if ($res) {
                        while ($row = mysqli_fetch_assoc($res)) {
                            $path = trim((string)($row['file_path'] ?? ''));
                            if ($path !== '') {
                                $existing_file_paths[$path] = true;
                            }
                        }
                        mysqli_free_result($res);
                    }
                    mysqli_stmt_close($existing_stmt);
                }

                mysqli_begin_transaction($conn);
                $broadcast_token = '';
                try {
                    $broadcast_token = bin2hex(random_bytes(8));
                } catch (Exception $e) {
                    $broadcast_token = uniqid('broadcast_', true);
                }

                $delete_ok = true;
                $delete_stmt = mysqli_prepare($conn, "DELETE FROM class_timetables WHERE timeline = ? AND is_broadcast = 1");
                if ($delete_stmt) {
                    mysqli_stmt_bind_param($delete_stmt, 's', $timeline_key);
                    $delete_ok = mysqli_stmt_execute($delete_stmt);
                    mysqli_stmt_close($delete_stmt);
                } else {
                    $delete_ok = false;
                }

                if (!$delete_ok) {
                    mysqli_rollback($conn);
                    @unlink($target_path);
                    $error = $error ? $error . ' ' : '';
                    $error .= 'Could not reset the previous broadcast timetable for the selected timeline.';
                } else {
                    $insert_sql = "INSERT INTO class_timetables (class_id, file_name, file_path, timeline, is_broadcast, broadcast_token) VALUES (?, ?, ?, ?, 1, ?)";
                    $insert_stmt = mysqli_prepare($conn, $insert_sql);
                    if (!$insert_stmt) {
                        mysqli_rollback($conn);
                        @unlink($target_path);
                        $error = $error ? $error . ' ' : '';
                        $error .= 'Unable to prepare the broadcast timetable insertion.';
                    } else {
                        $insert_ok = true;
                        foreach ($class_meta_map as $class_meta) {
                            $class_id = isset($class_meta['id']) ? (int)$class_meta['id'] : 0;
                            if ($class_id <= 0) {
                                continue;
                            }
                            mysqli_stmt_bind_param($insert_stmt, 'issss', $class_id, $safe_display_name, $relative_path, $timeline_key, $broadcast_token);
                            if (!mysqli_stmt_execute($insert_stmt)) {
                                $insert_ok = false;
                                break;
                            }
                        }
                        mysqli_stmt_close($insert_stmt);

                        if (!$insert_ok) {
                            mysqli_rollback($conn);
                            @unlink($target_path);
                            $error = $error ? $error . ' ' : '';
                            $error .= 'Failed to broadcast the timetable to every class. No changes were applied.';
                        } else {
                            mysqli_commit($conn);
                            $success = $success ? $success . ' ' : '';
                            $success .= sprintf(
                                "Broadcast timetable for %s uploaded to %d class(es).",
                                format_timetable_timeline_label($timeline_key),
                                count($class_meta_map)
                            );

                            $new_absolute = realpath($target_path);
                            $base_dir = realpath($upload_dir);
                            foreach (array_keys($existing_file_paths) as $old_relative) {
                                $old_absolute = realpath(__DIR__ . '/' . ltrim($old_relative, '/'));
                                if (!$old_absolute || !$base_dir) {
                                    continue;
                                }
                                if ($new_absolute && $old_absolute === $new_absolute) {
                                    continue;
                                }
                                if (strpos($old_absolute, $base_dir) === 0) {
                                    @unlink($old_absolute);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_class_timetable'])) {
    $class_id = isset($_POST['class_id_timetable']) ? (int)$_POST['class_id_timetable'] : 0;

    if ($class_id <= 0) {
        $error = $error ? $error . ' ' : '';
        $error .= 'Invalid class selected for timetable upload.';
    } elseif (!isset($_FILES['class_timetable_file']) || $_FILES['class_timetable_file']['error'] !== UPLOAD_ERR_OK) {
        $error = $error ? $error . ' ' : '';
        $error .= 'Please choose a file to upload for the class timetable.';
    } else {
        $upload_dir = __DIR__ . '/uploads/class_timetables/';
        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true) && !is_dir($upload_dir)) {
            $error = $error ? $error . ' ' : '';
            $error .= 'Failed to create timetable upload directory.';
        } else {
            $original_name = $_FILES['class_timetable_file']['name'] ?? 'timetable';
            $safe_display_name = trim($original_name) !== '' ? $original_name : 'timetable';
            $extension = pathinfo($original_name, PATHINFO_EXTENSION);
            try {
                $random_segment = bin2hex(random_bytes(4));
            } catch (Exception $e) {
                $random_segment = uniqid();
            }
            $sanitized_extension = $extension !== '' ? '.' . preg_replace('/[^A-Za-z0-9]/', '', $extension) : '';
            $new_filename = 'class_' . $class_id . '_' . date('YmdHis') . '_' . $random_segment . $sanitized_extension;
            $target_path = $upload_dir . $new_filename;
            $relative_path = 'uploads/class_timetables/' . $new_filename;

            if (move_uploaded_file($_FILES['class_timetable_file']['tmp_name'], $target_path)) {
                $insert_sql = "INSERT INTO class_timetables (class_id, file_name, file_path) VALUES (?, ?, ?)";
                $stmt_insert_tt = mysqli_prepare($conn, $insert_sql);
                if ($stmt_insert_tt) {
                    mysqli_stmt_bind_param($stmt_insert_tt, "iss", $class_id, $safe_display_name, $relative_path);
                    mysqli_stmt_execute($stmt_insert_tt);
                    mysqli_stmt_close($stmt_insert_tt);
                    $success = $success ? $success . ' ' : '';
                    $success .= 'Timetable uploaded successfully.';
                } else {
                    $error = $error ? $error . ' ' : '';
                    $error .= 'Could not record the timetable in the database.';
                    @unlink($target_path);
                }
            } else {
                $error = $error ? $error . ' ' : '';
                $error .= 'Failed to upload timetable file. Please try again.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_class_timetable'])) {
    $timetable_id = isset($_POST['timetable_id']) ? (int)$_POST['timetable_id'] : 0;

    if ($timetable_id <= 0) {
        $error = $error ? $error . ' ' : '';
        $error .= 'Invalid timetable selected for deletion.';
    } else {
        $lookup_sql = "SELECT id, file_path FROM class_timetables WHERE id = ? LIMIT 1";
        $lookup_stmt = mysqli_prepare($conn, $lookup_sql);
        if ($lookup_stmt) {
            mysqli_stmt_bind_param($lookup_stmt, "i", $timetable_id);
            mysqli_stmt_execute($lookup_stmt);
            $result = mysqli_stmt_get_result($lookup_stmt);
            $record = $result ? mysqli_fetch_assoc($result) : null;
            if ($result) {
                mysqli_free_result($result);
            }
            mysqli_stmt_close($lookup_stmt);

            if (!$record) {
                $error = $error ? $error . ' ' : '';
                $error .= 'Timetable record not found.';
            } else {
                $delete_stmt = mysqli_prepare($conn, "DELETE FROM class_timetables WHERE id = ?");
                if ($delete_stmt) {
                    mysqli_stmt_bind_param($delete_stmt, "i", $timetable_id);
                    if (mysqli_stmt_execute($delete_stmt)) {
                        $file_path = $record['file_path'] ?? '';
                        if ($file_path !== '') {
                            $absolute = realpath(__DIR__ . '/' . $file_path);
                            if ($absolute && strpos($absolute, realpath(__DIR__ . '/uploads/class_timetables')) === 0) {
                                @unlink($absolute);
                            }
                        }
                        $success = $success ? $success . ' ' : '';
                        $success .= 'Timetable deleted successfully.';
                    } else {
                        $error = $error ? $error . ' ' : '';
                        $error .= 'Unable to delete the timetable record.';
                    }
                    mysqli_stmt_close($delete_stmt);
                } else {
                    $error = $error ? $error . ' ' : '';
                    $error .= 'Unable to prepare the timetable deletion.';
                }
            }
        } else {
            $error = $error ? $error . ' ' : '';
            $error .= 'Unable to load the timetable for deletion.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_single_student'])) {
    $class_id = isset($_POST['class_id_single']) ? (int)$_POST['class_id_single'] : 0;
    $section_id = null;
    if (isset($_POST['section_id_single']) && $_POST['section_id_single'] === 'other') {
        $custom_section_name = trim($_POST['section_name_other_single'] ?? '');
        if ($custom_section_name !== '') {
            $section_id = handle_custom_section($conn, $class_id, $custom_section_name);
            if (!$section_id) {
                $error = $error ? $error . ' ' : '';
                $error .= 'Unable to create the requested division for the student.';
            }
        } else {
            $section_id = null; // leave unassigned when no custom label provided
        }
    } elseif (isset($_POST['section_id_single']) && $_POST['section_id_single'] !== '') {
        $section_id = (int)$_POST['section_id_single'];
    }

    if (!$error) {
        $status = upsert_student($conn, $_POST['sap_id'] ?? '', $_POST['full_name'] ?? '', $_POST['roll_number'] ?? '', $class_id, $section_id);
        if ($status === 'inserted') {
            $success = $success ? $success . ' ' : '';
            $success .= 'New student added successfully.';
        } elseif ($status === 'updated') {
            $success = $success ? $success . ' ' : '';
            $success .= 'Student updated/promoted successfully.';
        } else {
            $error = $error ? $error . ' ' : '';
            $error .= 'Unable to save the student record.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $class_id = isset($_POST['class_id_bulk']) ? (int)$_POST['class_id_bulk'] : 0;
    $section_id = null;
    if (isset($_POST['section_id_bulk']) && $_POST['section_id_bulk'] === 'other') {
        $custom_section_name = trim($_POST['section_name_other_bulk'] ?? '');
        if ($custom_section_name !== '') {
            $section_id = handle_custom_section($conn, $class_id, $custom_section_name);
            if (!$section_id) {
                $error = $error ? $error . ' ' : '';
                $error .= 'Unable to create the requested division for the bulk upload.';
            }
        } else {
            $section_id = null; // treat as single-section class when left blank
        }
    } elseif (isset($_POST['section_id_bulk']) && $_POST['section_id_bulk'] !== '') {
        $section_id = (int)$_POST['section_id_bulk'];
    }

    if (!$error) {
        if (empty($class_id)) {
            $error = $error ? $error . ' ' : '';
            $error .= 'Please select a school, semester, and class before uploading the bulk file.';
        } elseif ($_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            $error = $error ? $error . ' ' : '';
            $error .= 'Error uploading file. Please try again.';
        } else {
            $file = $_FILES['excel_file']['tmp_name'];
            if (strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION)) === 'csv') {
                $handle = fopen($file, 'r');
                if ($handle !== false) {
                    $header = array_map('trim', fgetcsv($handle, 1000, ','));
                    $normalizedHeader = array_map('strtolower', $header);

                    $required = [
                        'sno' => ['s/n', 's.no', 's. no', 'sno', 's. no.', 's. no.'],
                        'roll' => ['roll no', 'rollnumber', 'roll number', 'rollno', 'roll'],
                        'sap' => ['sap id', 'sapid', 'sap id.', 'sap'],
                        'name' => ['name of the student', 'name of student', 'full name', 'name', 'student name']
                    ];
                    $friendlyLabels = [
                        'sno' => 'S/N',
                        'roll' => 'ROLL NO',
                        'sap' => 'SAP ID',
                        'name' => 'NAME OF THE STUDENT'
                    ];

                    $columnMap = [];
                    $missing = [];
                    foreach ($required as $key => $variants) {
                        $found = false;
                        foreach ($variants as $v) {
                            $pos = array_search(strtolower($v), $normalizedHeader, true);
                            if ($pos !== false) {
                                $columnMap[$key] = $pos;
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $missing[] = $friendlyLabels[$key] ?? strtoupper(str_replace('_', ' ', $key));
                        }
                    }

                    if (!empty($missing)) {
                        $error = $error ? $error . ' ' : '';
                        $error .= 'Invalid CSV format. Required columns missing: ' . implode(', ', $missing) . '. Please use the template.';
                    } else {
                        $updated_count = 0;
                        $inserted_count = 0;
                        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                            $sap_value = trim($data[$columnMap['sap']] ?? '');
                            $name_value = trim($data[$columnMap['name']] ?? '');
                            $roll_value = trim($data[$columnMap['roll']] ?? '');
                            if ($sap_value !== '' && $name_value !== '' && $roll_value !== '') {
                                $status = upsert_student($conn, $sap_value, $name_value, $roll_value, $class_id, $section_id);
                                if ($status === 'updated') {
                                    $updated_count++;
                                } elseif ($status === 'inserted') {
                                    $inserted_count++;
                                }
                            }
                        }
                        $success = $success ? $success . ' ' : '';
                        $success .= 'Bulk operation complete: ' . $inserted_count . ' new students added, ' . $updated_count . ' existing students updated/promoted.';
                    }
                    fclose($handle);
                } else {
                    $error = $error ? $error . ' ' : '';
                    $error .= 'Could not open the uploaded file.';
                }
            } else {
                $error = $error ? $error . ' ' : '';
                $error .= 'Invalid file type. Please upload a CSV file.';
            }
        }
    }
}

$class_timetable_summary = [];
$summary_sql = "SELECT ct.id, ct.class_id, ct.file_name, ct.file_path, ct.uploaded_at, ct.timeline, ct.is_broadcast\n                FROM class_timetables ct\n                INNER JOIN (\n                    SELECT class_id, MAX(id) AS latest_id\n                    FROM class_timetables\n                    GROUP BY class_id\n                ) latest ON latest.class_id = ct.class_id AND latest.latest_id = ct.id";
$summary_result = mysqli_query($conn, $summary_sql);
if ($summary_result) {
    while ($row = mysqli_fetch_assoc($summary_result)) {
        $class_timetable_summary[(int)$row['class_id']] = $row;
    }
    mysqli_free_result($summary_result);
}

$broadcast_summary = [];
$broadcast_sql = "SELECT timeline, MIN(file_name) AS file_name, MIN(file_path) AS file_path, MAX(uploaded_at) AS uploaded_at, COUNT(*) AS class_count\n                    FROM class_timetables\n                    WHERE is_broadcast = 1 AND timeline IS NOT NULL AND timeline <> ''\n                    GROUP BY timeline\n                    ORDER BY uploaded_at DESC";
$broadcast_result = mysqli_query($conn, $broadcast_sql);
if ($broadcast_result) {
    while ($row = mysqli_fetch_assoc($broadcast_result)) {
        $timeline_key = trim((string)($row['timeline'] ?? ''));
        if ($timeline_key === '') {
            continue;
        }
        $broadcast_summary[$timeline_key] = $row;
    }
    mysqli_free_result($broadcast_result);
}

$classes_query = "SELECT c.id AS class_id, c.class_name, c.school, c.semester, s.id AS section_id, s.section_name,\n                         COUNT(st.id) AS student_count\n                  FROM classes c\n                  LEFT JOIN sections s ON s.class_id = c.id\n                  LEFT JOIN students st ON st.class_id = c.id AND (s.id IS NULL OR st.section_id = s.id)\n                  GROUP BY c.id, s.id\n                  ORDER BY c.semester, c.class_name, s.section_name";
$classes_result_cards = mysqli_query($conn, $classes_query);
$cards_by_semester = [];
if ($classes_result_cards) {
    while ($row = mysqli_fetch_assoc($classes_result_cards)) {
        $row['class_label'] = format_class_label(
            $row['class_name'] ?? '',
            $row['section_name'] ?? '',
            $row['semester'] ?? '',
            $row['school'] ?? ''
        );
        $sem = $row['semester'] ?: 'Unknown';
        if (!isset($cards_by_semester[$sem])) {
            $cards_by_semester[$sem] = [];
        }
        $cards_by_semester[$sem][] = $row;
    }
    mysqli_free_result($classes_result_cards);
}

$classes_for_select = array_values(array_map(function ($entry) {
    return [
        'id' => (int)$entry['id'],
        'class_name' => $entry['label'] ?? ($entry['class_name'] ?? ''),
        'school' => $entry['school'] ?? '',
        'semester' => $entry['semester'] ?? ''
    ];
}, $class_meta_map));

$class_sections_map_json = [];
foreach ($class_sections_map as $cid => $sections) {
    $class_sections_map_json[$cid] = array_values($sections);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Students - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .tab-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }
        .tab-button {
            padding: 15px 25px;
            cursor: pointer;
            border: none;
            background-color: white;
            font-size: 1.1em;
            font-weight: 500;
            color: #555;
            transition: color 0.3s, border-bottom 0.3s, background-color 0.3s;
            border-bottom: 3px solid transparent;
        }
        .tab-button:hover {
            background-color: #f1f1f1;
        }
        .tab-button.active {
            color: #A6192E;
            border-bottom: 3px solid #A6192E;
            background-color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .card-body .form-group {
            margin-bottom: 18px;
        }
        .card-body .form-group input,
        .card-body .form-group select {
            margin-bottom: 0;
        }
        .bulk-actions { width: 100%; }
        .bulk-actions .btn { padding: 8px 14px; }
        .card-body .form-group input[type=file] { display: block; width: 100%; }
        .timetable-upload {
            margin-top: 12px;
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .timetable-upload-form {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .upload-timetable-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 6px;
            background-color: #A6192E;
            color: #fff;
            font-size: 0.9em;
            cursor: pointer;
            text-decoration: none;
            border: none;
        }
        .upload-timetable-btn:hover {
            background-color: #8b1425;
        }
        .upload-timetable-meta {
            font-size: 0.85em;
            color: #555;
        }
        .term-filter-group {
            display: flex;
            gap: 10px;
            margin: 8px 0 4px;
            flex-wrap: wrap;
        }
        .term-filter-button {
            background-color: #f3f4f6;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            color: #374151;
            cursor: pointer;
            font-size: 0.9em;
            padding: 6px 14px;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .term-filter-button.active {
            background-color: #A6192E;
            border-color: #A6192E;
            color: #fff;
        }
        .timetable-download-link {
            font-size: 0.85em;
            color: #A6192E;
            text-decoration: underline;
        }
        .timetable-edit {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
        }
        .timetable-edit-toggle {
            background: none;
            border: none;
            color: #A6192E;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
            font-size: 0.82em;
        }
        .timetable-edit-toggle:hover {
            text-decoration: underline;
        }
        .timetable-delete-form {
            display: none;
        }
        .timetable-delete-form.is-visible {
            display: block;
        }
        .timetable-delete-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff;
            border: 1px solid #A6192E;
            color: #A6192E;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 0.8em;
            cursor: pointer;
        }
        .timetable-delete-btn:hover {
            background: rgba(166, 25, 46, 0.1);
        }
        .sr-only-file {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }
        .card-expand {
            overflow-x: auto;
        }
        .broadcast-upload-form {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: flex-end;
        }
        .broadcast-upload-form .form-group {
            flex: 1 1 220px;
            min-width: 200px;
        }
        .broadcast-timeline-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            font-size: 0.92em;
        }
        .broadcast-timeline-table th,
        .broadcast-timeline-table td {
            border-bottom: 1px solid #ececec;
            padding: 10px 12px;
            text-align: left;
        }
        .broadcast-timeline-table th {
            background: #f7f7f7;
            text-transform: uppercase;
            font-size: 0.78em;
            letter-spacing: 0.04em;
            color: #555;
        }
        .broadcast-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .broadcast-meta {
            font-size: 0.82em;
            color: #5a6473;
        }
        .broadcast-note {
            margin-top: 10px;
            font-size: 0.85em;
            color: #4a5568;
        }
        .inline-student-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 0.92em;
        }
        .inline-student-table th,
        .inline-student-table td {
            border-bottom: 1px solid #f1f1f1;
            padding: 6px;
            vertical-align: top;
            text-align: left;
        }
        .inline-actions-btn {
            background-color: #fff;
            border: 1px solid #A6192E;
            color: #A6192E;
            padding: 4px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.84em;
            margin-right: 6px;
            transition: background-color 0.15s ease, color 0.15s ease;
        }
        .inline-actions-btn:last-child {
            margin-right: 0;
        }
        .inline-actions-btn:hover {
            background-color: #A6192E;
            color: #fff;
        }
        .inline-actions-btn.danger {
            border-color: #d32f2f;
            color: #d32f2f;
        }
        .inline-actions-btn.danger:hover {
            background-color: #d32f2f;
            color: #fff;
        }
        .inline-edit-input,
        .inline-edit-select {
            width: 100%;
            padding: 4px 6px;
            font-size: 0.9em;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .inline-custom-section {
            margin-top: 6px;
        }
        .inline-custom-section label {
            display: block;
            font-size: 0.76em;
            color: #555;
            margin-bottom: 2px;
        }
        .inline-status {
            margin-bottom: 6px;
            padding: 6px 8px;
            border-radius: 4px;
            font-size: 0.84em;
            display: none;
        }
        .class-card-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .class-card {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            padding: 18px 20px;
            border: 1px solid #dadde3;
            border-radius: 12px;
            background-color: #ffffff;
            box-shadow: 0 10px 18px rgba(14, 32, 56, 0.08);
            cursor: pointer;
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }
        .class-card:hover {
            box-shadow: 0 16px 24px rgba(14, 32, 56, 0.12);
            transform: translateY(-2px);
        }
        .class-card-main {
            flex: 1 1 320px;
            min-width: 260px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .class-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .class-card-header strong {
            font-size: 1.05em;
            color: #0a2239;
        }
        .student-count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 12px;
            border-radius: 999px;
            background-color: #eef2f7;
            color: #345;
            font-size: 0.85em;
            font-weight: 600;
            min-width: 110px;
            text-align: center;
        }
        .class-card-meta {
            color: #4a5568;
            font-size: 0.92em;
        }
        .class-card-side {
            flex: 0 0 320px;
            max-width: 100%;
        }
        .class-card .timetable-upload {
            height: 100%;
            background-color: #f6f8fb;
        }
        .class-card .card-expand {
            flex-basis: 100%;
            margin-top: 12px;
            width: 100%;
            background-color: #f9fafc;
            border-radius: 8px;
            padding: 12px 16px;
        }
        @media (max-width: 820px) {
            .class-card {
                flex-direction: column;
                align-items: stretch;
            }
            .class-card-side {
                flex-basis: auto;
            }
        }
        .inline-status.success {
            background-color: #e6f4ea;
            color: #256029;
            border: 1px solid #b7dfc1;
        }
        .inline-status.error {
            background-color: #fdecea;
            color: #611a15;
            border: 1px solid #f5c6cb;
        }
        .card-inline-status {
            margin-bottom: 6px;
            padding: 6px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            display: none;
        }
        .student-count-badge {
            background: #f7f7f7;
            padding: 6px 8px;
            border-radius: 12px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
            <h2>ICA Tracker</h2>
            <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> <span>Manage Teachers</span></a>
            <a href="create_classes.php"><i class="fas fa-layer-group"></i> <span>Create Classes</span></a>
            <a href="create_subjects.php"><i class="fas fa-book"></i> <span>Create Subjects</span></a>
            <a href="assign_teachers.php"><i class="fas fa-user-tag"></i> <span>Assign Teachers</span></a>
            <a href="manage_electives.php"><i class="fas fa-user-friends"></i> <span>Manage Electives</span></a>
            <a href="change_roles.php"><i class="fas fa-user-cog"></i> <span>Change Roles</span></a>
            <a href="bulk_add_students.php" class="active"><i class="fas fa-user-plus"></i> <span>Add Students</span></a>
            <a href="manage_academic_calendar.php"><i class="fas fa-calendar-alt"></i> <span>Academic Calendar</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
        <div class="main-content">
            <div class="header">
                <h2>Add Students</h2>
            </div>
            <div class="container">
                <?php if ($error) { echo "<div class='card' style='color: #d32f2f; font-weight: bold; padding: 15px;'>" . htmlspecialchars($error) . "</div>"; } ?>
                <?php if ($success) { echo "<div class='card' style='color: #388e3c; font-weight: bold; padding: 15px;'>" . htmlspecialchars($success) . "</div>"; } ?>

                <div class="tab-container">
                    <button class="tab-button active" type="button" onclick="showTab('single', this)">Add Single Student</button>
                    <button class="tab-button" type="button" onclick="showTab('bulk', this)">Bulk Add Students</button>
                    <button class="tab-button" type="button" onclick="showTab('copy', this)">Copy Students</button>
                </div>

                <div id="single" class="tab-content active">
                    <div class="card">
                        <div class="card-header"><h5>Add Single Student</h5></div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label>What is the student's Full Name? (e.g., John Doe)</label>
                                    <input type="text" name="full_name" required>
                                </div>
                                <div class="form-group">
                                    <label>What is the SAP ID? (e.g., 12345678)</label>
                                    <input type="text" name="sap_id" required>
                                </div>
                                <div class="form-group">
                                    <label>What is the Roll Number? (e.g., CE-01)</label>
                                    <input type="text" name="roll_number" required>
                                </div>
                                <div class="form-group">
                                    <label>Which School/Department does the student belong to?</label>
                                    <select name="school_single" id="school_single" required>
                                        <option value="">Select School</option>
                                        <option value="STME">STME</option>
                                        <option value="SOL">SOL</option>
                                        <option value="SPTM">SPTM</option>
                                        <option value="SBM">SBM</option>
                                        <option value="SOC">SOC</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Which Semester is this student in? (select after choosing School)</label>
                                    <div class="term-filter-group" id="semester_single_filters">
                                        <button type="button" class="term-filter-button" data-filter="odd">Odd Term</button>
                                        <button type="button" class="term-filter-button" data-filter="even">Even Term</button>
                                    </div>
                                    <select name="semester_single" id="semester_single" required>
                                        <option value="">-- Select School First --</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Which Class should this student be placed into? (This will set Semester & Department)</label>
                                    <select name="class_id_single" id="class_id_single" required>
                                        <option value="">-- Select Semester First --</option>
                                    </select>
                                </div>
                                <div class="form-group" id="section_container_single" style="display: none;">
                                    <label>Select Division/Section </label>
                                    <select name="section_id_single" id="section_id_single">
                                        <option value="">-- Select Class First --</option>
                                    </select>
                                </div>
                                <div class="form-group" id="section_other_container_single" style="display: none;">
                                    <label>Custom Division Name</label>
                                    <input type="text" name="section_name_other_single" id="section_name_other_single">
                                </div>
                                <button type="submit" name="add_single_student" class="btn">Add Student</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div id="bulk" class="tab-content">
                    <div class="card">
                        <div class="card-header"><h5>Bulk Add Students</h5></div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label>Select School</label>
                                    <select name="school_bulk" id="school_bulk" required>
                                        <option value="">Select School</option>
                                        <option value="STME">STME</option>
                                        <option value="SOL">SOL</option>
                                        <option value="SPTM">SPTM</option>
                                        <option value="SBM">SBM</option>
                                        <option value="SOC">SOC</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Select Semester</label>
                                    <div class="term-filter-group" id="semester_bulk_filters">
                                        <button type="button" class="term-filter-button" data-filter="odd">Odd Term</button>
                                        <button type="button" class="term-filter-button" data-filter="even">Even Term</button>
                                    </div>
                                    <select name="semester_bulk" id="semester_bulk" required>
                                        <option value="">-- Select School First --</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Select Class</label>
                                    <select name="class_id_bulk" id="class_id_bulk" required>
                                        <option value="">-- Select Semester First --</option>
                                    </select>
                                </div>
                                <div class="form-group" id="section_container_bulk" style="display: none;">
                                    <label>Select Division/Section (Optional)</label>
                                    <select name="section_id_bulk" id="section_id_bulk">
                                        <option value="">-- Select Class First --</option>
                                    </select>
                                </div>
                                <div class="form-group" id="section_other_container_bulk" style="display: none;">
                                    <label>Custom Division Name</label>
                                    <input type="text" name="section_name_other_bulk" id="section_name_other_bulk">
                                </div>
                                <div class="form-group">
                                    <label>Upload CSV File</label>
                                    <input type="file" name="excel_file" accept=".csv" required>
                                </div>
                                <div class="bulk-actions" style="display:flex;flex-direction:column;gap:10px;">
                                    <p style="margin:0;"><b>NOTE:</b> Required columns (in any order): <strong>S/N</strong>, <strong>ROLL NO</strong>, <strong>SAP ID</strong>, <strong>NAME OF STUDENT</strong>. Use the provided template for correct headers.</p>
                                    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-top:6px;">
                                        <button type="submit" class="btn">Upload Students</button>
                                        <a href="download_template.php" class="btn" style="text-decoration: none; background-color: #6c757d;">Download Template</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div id="copy" class="tab-content">
                    <div class="card">
                        <div class="card-header"><h5>Copy Students Between Classes</h5></div>
                        <div class="card-body">
                            <form method="POST">
                                <div style="display:flex;flex-direction:column;gap:20px;">
                                    <div>
                                        <h6 style="margin:0 0 10px;">Source Class</h6>
                                        <div class="form-group">
                                            <label>Select School</label>
                                            <select name="copy_source_school" id="copy_source_school" required>
                                                <option value="">Select School</option>
                                                <option value="STME">STME</option>
                                                <option value="SOL">SOL</option>
                                                <option value="SPTM">SPTM</option>
                                                <option value="SBM">SBM</option>
                                                <option value="SOC">SOC</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Select Semester</label>
                                            <div class="term-filter-group" id="copy_source_semester_filters">
                                                <button type="button" class="term-filter-button" data-filter="odd">Odd Term</button>
                                                <button type="button" class="term-filter-button" data-filter="even">Even Term</button>
                                            </div>
                                            <select name="copy_source_semester" id="copy_source_semester" required>
                                                <option value="">-- Select School First --</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Select Class</label>
                                            <select name="copy_source_class" id="copy_source_class" required>
                                                <option value="">-- Select Semester First --</option>
                                            </select>
                                        </div>
                                        <div id="copy_source_section_container" style="display:none;">
                                            <select id="copy_source_section_select"><option value="">-- Hidden --</option></select>
                                        </div>
                                        <div id="copy_source_section_other_container" style="display:none;">
                                            <input type="text" id="copy_source_section_custom" autocomplete="off">
                                        </div>
                                    </div>
                                    <div>
                                        <h6 style="margin:0 0 10px;">Destination Class</h6>
                                        <div class="form-group">
                                            <label>Select School</label>
                                            <select name="copy_target_school" id="copy_target_school" required>
                                                <option value="">Select School</option>
                                                <option value="STME">STME</option>
                                                <option value="SOL">SOL</option>
                                                <option value="SPTM">SPTM</option>
                                                <option value="SBM">SBM</option>
                                                <option value="SOC">SOC</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Select Semester</label>
                                            <div class="term-filter-group" id="copy_target_semester_filters">
                                                <button type="button" class="term-filter-button" data-filter="odd">Odd Term</button>
                                                <button type="button" class="term-filter-button" data-filter="even">Even Term</button>
                                            </div>
                                            <select name="copy_target_semester" id="copy_target_semester" required>
                                                <option value="">-- Select School First --</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Select Class</label>
                                            <select name="copy_target_class" id="copy_target_class" required>
                                                <option value="">-- Select Semester First --</option>
                                            </select>
                                        </div>
                                        <div id="copy_target_section_container" style="display:none;">
                                            <select id="copy_target_section_select"><option value="">-- Hidden --</option></select>
                                        </div>
                                        <div id="copy_target_section_other_container" style="display:none;">
                                            <input type="text" id="copy_target_section_custom" autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                                <p style="margin:15px 0 0;font-size:0.92em;color:#444;">Students from the source class (including their divisions) will be copied into the destination class. Existing SAP IDs in the destination will be skipped.</p>
                                <button type="submit" name="copy_students_submit" class="btn" style="margin-top:15px;">Copy Students</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card" style="margin-top:20px;">
                    <div class="card-header"><h5>Broadcast Timetable to All Classes</h5></div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" class="broadcast-upload-form">
                            <input type="hidden" name="upload_global_timetable" value="1">
                            <div class="form-group">
                                <label>Select Timeline</label>
                                <select name="timeline_key" required>
                                    <option value="">-- Select Timeline --</option>
                                    <?php foreach ($timeline_options as $timeline_value => $timeline_label): ?>
                                        <option value="<?php echo htmlspecialchars($timeline_value); ?>"><?php echo htmlspecialchars($timeline_label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Upload Timetable File</label>
                                <input type="file" name="global_timetable_file" required>
                            </div>
                            <button type="submit" class="btn">Upload for All Classes</button>
                        </form>
                        <p class="broadcast-note">Uploading replaces the existing timetable for the selected timeline across every class.</p>
                        <?php if (!empty($broadcast_summary)): ?>
                            <table class="broadcast-timeline-table">
                                <thead>
                                    <tr>
                                        <th>Timeline</th>
                                        <th>File</th>
                                        <th>Shared With</th>
                                        <th>Last Updated</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $broadcastIndex = 0; foreach ($broadcast_summary as $timeline_key => $timeline_row): ?>
                                        <?php
                                            $broadcastIndex++;
                                            $timelineLabel = format_timetable_timeline_label($timeline_key);
                                            $displayName = $timeline_row['file_name'] ?? '';
                                            $filePath = $timeline_row['file_path'] ?? '';
                                            $classCount = (int)($timeline_row['class_count'] ?? 0);
                                            $uploadedAtRaw = $timeline_row['uploaded_at'] ?? '';
                                            $uploadedTimestamp = $uploadedAtRaw ? strtotime($uploadedAtRaw) : false;
                                            $uploadedDisplay = $uploadedTimestamp ? date('d M Y, h:i A', $uploadedTimestamp) : 'N/A';
                                            $downloadLabel = $displayName !== '' ? $displayName : ($filePath !== '' ? basename($filePath) : 'N/A');
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($timelineLabel); ?></td>
                                            <td>
                                                <?php if ($filePath !== ''): ?>
                                                    <a href="<?php echo htmlspecialchars($filePath); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($downloadLabel); ?></a>
                                                <?php else: ?>
                                                    <span><?php echo htmlspecialchars($downloadLabel); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($classCount); ?> classes</td>
                                            <td><?php echo htmlspecialchars($uploadedDisplay); ?></td>
                                            <td>
                                                <div class="broadcast-actions">
                                                    <form method="POST" enctype="multipart/form-data">
                                                        <input type="hidden" name="upload_global_timetable" value="1">
                                                        <input type="hidden" name="timeline_key" value="<?php echo htmlspecialchars($timeline_key); ?>">
                                                        <input type="file" name="global_timetable_file" id="broadcast-file-<?php echo $broadcastIndex; ?>" class="sr-only-file" required onchange="this.form.submit()">
                                                        <label for="broadcast-file-<?php echo $broadcastIndex; ?>" class="upload-timetable-btn"><i class="fas fa-sync-alt"></i>Replace file</label>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="broadcast-note" style="margin-top:12px;">No broadcast timetables found yet. Upload a file to start sharing weekly schedules.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card" style="margin-top:20px;">
                    <div class="card-header"><h5>Existing Students by Year / Class</h5></div>
                    <div class="card-body">
                        <?php if (empty($cards_by_semester)): ?>
                            <p>No classes found. Create classes first.</p>
                        <?php else: ?>
                            <div class="term-filter-group" id="existing_students_filters">
                                <button type="button" class="term-filter-button" data-filter="odd">Odd Term</button>
                                <button type="button" class="term-filter-button" data-filter="even">Even Term</button>
                            </div>
                            <?php foreach ($cards_by_semester as $semester => $rows): ?>
                                <?php
                                    $semesterDigits = preg_replace('/[^0-9]/', '', (string)$semester);
                                    $semesterNumber = $semesterDigits !== '' ? (int)$semesterDigits : 0;
                                    $semesterParity = $semesterNumber > 0 ? (($semesterNumber % 2 === 0) ? 'even' : 'odd') : 'other';
                                ?>
                                <div class="semester-group" data-semester-group data-parity="<?php echo htmlspecialchars($semesterParity); ?>">
                                    <h3 style="margin-top:12px;"><?php echo htmlspecialchars('Year/Semester: ' . $semester); ?></h3>
                                    <div class="class-card-list">
                                        <?php foreach ($rows as $card): ?>
                                        <?php $latest_timetable = $class_timetable_summary[(int)$card['class_id']] ?? null; ?>
                                        <div class="class-card" data-class-id="<?php echo (int)$card['class_id']; ?>" data-section-id="<?php echo (int)($card['section_id'] ?? 0); ?>">
                                            <div class="class-card-main">
                                                <div class="class-card-header">
                                                    <strong><?php echo htmlspecialchars($card['class_label'] ?: $card['class_name']); ?></strong>
                                                    <span class="student-count-badge" data-student-count="<?php echo (int)$card['student_count']; ?>"><?php echo (int)$card['student_count']; ?> students</span>
                                                </div>
                                                <div class="class-card-meta">
                                                    <div>Division: <?php echo htmlspecialchars($card['section_name'] ?: 'N/A'); ?></div>
                                                    <div>School: <?php echo htmlspecialchars($card['school'] ?: 'N/A'); ?> • Semester: <?php echo htmlspecialchars($card['semester'] ?: 'N/A'); ?></div>
                                                </div>
                                            </div>
                                            <div class="class-card-side">
                                                <div class="timetable-upload">
                                                    <form class="timetable-upload-form" method="POST" enctype="multipart/form-data">
                                                        <input type="hidden" name="upload_class_timetable" value="1">
                                                        <input type="hidden" name="class_id_timetable" value="<?php echo (int)$card['class_id']; ?>">
                                                        <input type="file" name="class_timetable_file" id="class-timetable-file-<?php echo (int)$card['class_id']; ?>" class="sr-only-file" accept="*/*" onchange="this.form.submit()">
                                                        <label for="class-timetable-file-<?php echo (int)$card['class_id']; ?>" class="upload-timetable-btn"><i class="fas fa-upload"></i>Upload timetable</label>
                                                        <?php if ($latest_timetable): ?>
                                                            <a href="<?php echo htmlspecialchars($latest_timetable['file_path']); ?>" target="_blank" rel="noopener" class="timetable-download-link">View latest</a>
                                                        <?php endif; ?>
                                                    </form>
                                                    <?php if ($latest_timetable): ?>
                                                        <div class="timetable-edit">
                                                            <button type="button" class="timetable-edit-toggle">Edit</button>
                                                            <form method="POST" class="timetable-delete-form">
                                                                <input type="hidden" name="delete_class_timetable" value="1">
                                                                <input type="hidden" name="timetable_id" value="<?php echo (int)($latest_timetable['id'] ?? 0); ?>">
                                                                <button type="submit" class="timetable-delete-btn" onclick="return confirm('Delete the current timetable for this class?');"><i class="fas fa-trash-alt"></i> Delete timetable</button>
                                                            </form>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($latest_timetable): ?>
                                                        <?php
                                                            $lastUploadRaw = $latest_timetable['uploaded_at'] ?? '';
                                                            $lastUploadTs = $lastUploadRaw ? strtotime($lastUploadRaw) : false;
                                                            $lastUploadLabel = $lastUploadTs ? date('d M Y, h:i A', $lastUploadTs) : 'N/A';
                                                            $timelineMeta = '';
                                                            if (!empty($latest_timetable['timeline'])) {
                                                                $timelineMeta = format_timetable_timeline_label($latest_timetable['timeline']);
                                                            }
                                                            $scopeMeta = !empty($latest_timetable['is_broadcast']) ? 'Broadcast' : '';
                                                            $metaParts = array_filter([$timelineMeta, $scopeMeta]);
                                                        ?>
                                                        <div class="upload-timetable-meta">
                                                            Last upload: <?php echo htmlspecialchars($lastUploadLabel); ?>
                                                            <?php if (!empty($metaParts)): ?>
                                                                <br><small><?php echo htmlspecialchars(implode(' • ', $metaParts)); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="upload-timetable-meta">No timetable uploaded yet for this class.</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="card-expand" style="display:none;"></div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <script>
        (function () {
            const classMetaMap = <?php echo json_encode($class_meta_map, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
            const classSectionsMap = <?php echo json_encode($class_sections_map_json, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
            const classesList = <?php echo json_encode($classes_for_select, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

            function toUpper(value) {
                const trimmed = String(value || '').trim();
                return trimmed ? trimmed.toUpperCase() : '';
            }

            function formatClassLabelValue(className, division, semester, school) {
                const parts = [];
                const base = toUpper(className);
                if (base) {
                    parts.push(base);
                }

                const meta = [];
                const semRaw = String(semester || '').trim();
                if (semRaw) {
                    const semNormalized = semRaw.replace(/^sem\s*/i, '') || semRaw;
                    meta.push('SEM: ' + toUpper(semNormalized));
                }
                const schoolVal = toUpper(school);
                if (schoolVal) {
                    meta.push('SCHOOL: ' + schoolVal);
                }
                if (meta.length) {
                    parts.push('(' + meta.join(' - ') + ')');
                }

                const divisionValue = String(division || '').trim();
                if (divisionValue) {
                    const divisionPieces = divisionValue.split('/').map(piece => {
                        const cleaned = piece.replace(/\s+/g, ' ').trim();
                        if (!cleaned) {
                            return '';
                        }
                        const upperCleaned = toUpper(cleaned);
                        return upperCleaned.startsWith('DIV') ? upperCleaned : 'DIV ' + upperCleaned;
                    }).filter(Boolean);
                    if (divisionPieces.length) {
                        parts.push(divisionPieces.join(' / '));
                    }
                }

                return parts.length ? parts.join(' - ') : '';
            }

            function applyStoredTheme() {
                if (localStorage.getItem('theme') === 'dark') {
                    document.body.classList.add('dark-mode');
                }
            }

            function toggleTheme() {
                document.body.classList.toggle('dark-mode');
                localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
            }

            function showTab(tabName, trigger) {
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                document.querySelectorAll('.tab-button').forEach(button => {
                    button.classList.remove('active');
                });
                const target = document.getElementById(tabName);
                if (target) {
                    target.classList.add('active');
                }
                if (trigger) {
                    trigger.classList.add('active');
                }
            }

            function setupDependentDropdowns(deptId, semId, classId, sectionContainerId, sectionSelectId, sectionOtherContainerId, sectionOtherInputId, filterSelector) {
                const departmentSelect = document.getElementById(deptId);
                const semesterSelect = document.getElementById(semId);
                const classSelect = document.getElementById(classId);
                const sectionContainer = document.getElementById(sectionContainerId);
                const sectionSelect = document.getElementById(sectionSelectId);
                const sectionOtherContainer = document.getElementById(sectionOtherContainerId);
                const sectionOtherInput = document.getElementById(sectionOtherInputId);
                const filterContainer = filterSelector ? document.querySelector(filterSelector) : null;
                const filterButtons = filterContainer ? Array.from(filterContainer.querySelectorAll('[data-filter]')) : [];

                if (!departmentSelect || !semesterSelect || !classSelect) {
                    return;
                }

                let semesterCache = [];
                let activeSemesterParity = '';

                function parityForValue(rawValue) {
                    const match = String(rawValue ?? '').match(/\d+/);
                    if (!match) {
                        return 'other';
                    }
                    const parsed = parseInt(match[0], 10);
                    if (!Number.isFinite(parsed) || parsed === 0) {
                        return 'other';
                    }
                    return parsed % 2 === 0 ? 'even' : 'odd';
                }

                function setSemesterPlaceholder(message) {
                    semesterSelect.innerHTML = '<option value="">' + message + '</option>';
                }

                function setClassPlaceholder(message) {
                    classSelect.innerHTML = '<option value="">' + message + '</option>';
                }

                function resetSectionState() {
                    if (sectionContainer) {
                        sectionContainer.style.display = 'none';
                    }
                    if (sectionOtherContainer) {
                        sectionOtherContainer.style.display = 'none';
                    }
                    if (sectionSelect) {
                        sectionSelect.innerHTML = '<option value="">-- Select Class First --</option>';
                    }
                    if (sectionOtherInput) {
                        sectionOtherInput.required = false;
                        sectionOtherInput.value = '';
                    }
                }

                function resetSemesterFilterButtons() {
                    activeSemesterParity = '';
                    filterButtons.forEach(function (button) {
                        button.classList.remove('active');
                    });
                }

                function renderSemesters() {
                    if (!semesterCache.length) {
                        const emptyMessage = activeSemesterParity === 'odd'
                            ? '-- No odd term semesters --'
                            : activeSemesterParity === 'even'
                                ? '-- No even term semesters --'
                                : '-- No semesters found --';
                        setSemesterPlaceholder(emptyMessage);
                        setClassPlaceholder('-- Select Semester First --');
                        return;
                    }

                    const filtered = activeSemesterParity
                        ? semesterCache.filter(function (item) {
                            return parityForValue(item.semester) === activeSemesterParity;
                        })
                        : semesterCache.slice();

                    if (!filtered.length) {
                        const emptyMessage = activeSemesterParity === 'odd'
                            ? '-- No odd term semesters --'
                            : '-- No even term semesters --';
                        setSemesterPlaceholder(emptyMessage);
                        setClassPlaceholder('-- Select Semester First --');
                        return;
                    }

                    const previousValue = semesterSelect.value;
                    let options = '<option value="">-- Select Semester --</option>';
                    filtered.forEach(function (item) {
                        const value = String(item.semester);
                        options += '<option value="' + value + '">' + value + '</option>';
                    });
                    semesterSelect.innerHTML = options;

                    if (previousValue && filtered.some(function (item) { return String(item.semester) === previousValue; })) {
                        semesterSelect.value = previousValue;
                    } else {
                        semesterSelect.value = '';
                        setClassPlaceholder('-- Select Semester First --');
                    }
                }

                departmentSelect.addEventListener('change', function () {
                    const school = this.value;
                    semesterCache = [];
                    resetSemesterFilterButtons();
                    setSemesterPlaceholder('Loading...');
                    setClassPlaceholder('-- Select Semester First --');
                    resetSectionState();

                    if (school) {
                        fetch('get_semesters.php?school=' + encodeURIComponent(school))
                            .then(function (response) { return response.json(); })
                            .then(function (data) {
                                semesterCache = Array.isArray(data) ? data : [];
                                renderSemesters();
                            })
                            .catch(function () {
                                semesterCache = [];
                                setSemesterPlaceholder('-- Unable to load semesters --');
                                setClassPlaceholder('-- Select Semester First --');
                            });
                    } else {
                        setSemesterPlaceholder('-- Select School First --');
                        semesterCache = [];
                    }
                });

                semesterSelect.addEventListener('change', function () {
                    const department = departmentSelect.value;
                    const semesterValue = this.value;
                    setClassPlaceholder('Loading...');
                    resetSectionState();

                    if (department && semesterValue) {
                        fetch('get_classes.php?school=' + encodeURIComponent(department) + '&semester=' + encodeURIComponent(semesterValue))
                            .then(function (response) { return response.json(); })
                            .then(function (data) {
                                let options = '<option value="">-- Select Class --</option>';
                                data.forEach(function (cls) {
                                    options += '<option value="' + cls.id + '">' + String(cls.class_name) + '</option>';
                                });
                                classSelect.innerHTML = options;
                            })
                            .catch(function () {
                                setClassPlaceholder('-- Unable to load classes --');
                            });
                    } else if (department) {
                        setClassPlaceholder('-- Select Semester First --');
                    } else {
                        setClassPlaceholder('-- Select School First --');
                    }
                });

                if (filterButtons.length) {
                    filterButtons.forEach(function (button) {
                        button.addEventListener('click', function () {
                            if (!semesterCache.length) {
                                return;
                            }
                            const targetParity = this.dataset.filter || '';
                            if (activeSemesterParity === targetParity) {
                                activeSemesterParity = '';
                                filterButtons.forEach(function (btn) {
                                    btn.classList.remove('active');
                                });
                            } else {
                                activeSemesterParity = targetParity;
                                filterButtons.forEach(function (btn) {
                                    btn.classList.toggle('active', btn === button);
                                });
                            }
                            renderSemesters();
                        });
                    });
                }

                if (sectionSelect && sectionContainer) {
                    classSelect.addEventListener('change', function () {
                        const classIdValue = this.value;
                        resetSectionState();

                        if (classIdValue) {
                            fetch('get_sections.php?class_id=' + encodeURIComponent(classIdValue))
                                .then(function (response) { return response.json(); })
                                .then(function (data) {
                                    if (Array.isArray(data) && data.length > 0) {
                                        sectionContainer.style.display = 'block';
                                        let options = '<option value="">-- Select Division --</option>';
                                        const classMeta = classMetaMap[String(classIdValue)] || {};
                                        data.forEach(function (sec) {
                                            const label = sec.label || formatClassLabelValue(classMeta.class_name, sec.section_name, classMeta.semester, classMeta.school) || sec.section_name;
                                            options += '<option value="' + sec.id + '">' + escapeHtml(label) + '</option>';
                                        });
                                        options += '<option value="other">Other...</option>';
                                        sectionSelect.innerHTML = options;
                                    } else {
                                        sectionSelect.innerHTML = '<option value="other">Other...</option>';
                                        sectionContainer.style.display = 'block';
                                    }
                                })
                                .catch(function () {
                                    sectionSelect.innerHTML = '<option value="">-- Unable to load divisions --</option>';
                                });
                        }
                    });

                    sectionSelect.addEventListener('change', function () {
                        if (sectionOtherContainer) {
                            if (this.value === 'other') {
                                sectionOtherContainer.style.display = 'block';
                                if (sectionOtherInput) {
                                    sectionOtherInput.required = true;
                                }
                            } else {
                                sectionOtherContainer.style.display = 'none';
                                if (sectionOtherInput) {
                                    sectionOtherInput.required = false;
                                    sectionOtherInput.value = '';
                                }
                            }
                        }
                    });
                }
            }

            function toInt(value) {
                const parsed = parseInt(value, 10);
                return Number.isNaN(parsed) ? 0 : parsed;
            }

            function escapeHtml(text) {
                return String(text).replace(/[&<>"'`]/g, function (s) {
                    return ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;', '`': '&#x60;'})[s];
                });
            }

            function populateSectionOptions(select, classId, currentSectionId) {
                if (!select) {
                    return;
                }
                const key = String(classId);
                const sections = classSectionsMap[key] || [];
                const classMeta = classMetaMap[key] || {};
                select.innerHTML = '';
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = sections.length ? '-- Select Division --' : '-- No Divisions --';
                select.appendChild(placeholder);

                sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section.id;
                    const sectionLabel = section.label || formatClassLabelValue(classMeta.class_name, section.name, classMeta.semester, classMeta.school) || section.name || '';
                    option.textContent = sectionLabel;
                    if (toInt(section.id) === toInt(currentSectionId)) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });

                if (currentSectionId && !sections.some(section => toInt(section.id) === toInt(currentSectionId))) {
                    const fallback = document.createElement('option');
                    fallback.value = currentSectionId;
                    fallback.textContent = 'Current Division';
                    fallback.selected = true;
                    select.appendChild(fallback);
                }

                const customOption = document.createElement('option');
                customOption.value = 'other';
                customOption.textContent = 'Other...';
                select.appendChild(customOption);
            }

            function handleCustomSectionVisibility(sectionSelect, customWrapper) {
                if (!sectionSelect || !customWrapper) {
                    return;
                }
                if (sectionSelect.value === 'other') {
                    customWrapper.style.display = 'block';
                } else {
                    customWrapper.style.display = 'none';
                    const input = customWrapper.querySelector('input');
                    if (input) {
                        input.value = '';
                    }
                }
            }

            function showInlineStatus(row, message, isError) {
                if (!row || !row._statusBox) {
                    return;
                }
                const statusBox = row._statusBox;
                statusBox.textContent = message || '';
                statusBox.classList.remove('success', 'error');
                if (!message) {
                    statusBox.style.display = 'none';
                    return;
                }
                statusBox.classList.add(isError ? 'error' : 'success');
                statusBox.style.display = 'block';
                clearTimeout(statusBox._timer);
                statusBox._timer = setTimeout(() => {
                    statusBox.style.display = 'none';
                }, 4000);
            }

            function showCardStatus(card, message, isError) {
                if (!card) {
                    return;
                }
                const expand = card.querySelector('.card-expand');
                if (!expand) {
                    return;
                }
                let statusBox = expand._statusBox;
                if (!statusBox) {
                    statusBox = document.createElement('div');
                    statusBox.className = 'card-inline-status';
                    expand.insertBefore(statusBox, expand.firstChild);
                    expand._statusBox = statusBox;
                }
                statusBox.textContent = message || '';
                if (!message) {
                    statusBox.style.display = 'none';
                    return;
                }
                statusBox.style.display = 'block';
                statusBox.style.backgroundColor = isError ? '#fdecea' : '#e6f4ea';
                statusBox.style.color = isError ? '#611a15' : '#256029';
                statusBox.style.border = isError ? '1px solid #f5c6cb' : '1px solid #b7dfc1';
                clearTimeout(statusBox._timer);
                statusBox._timer = setTimeout(() => {
                    statusBox.style.display = 'none';
                }, 5000);
            }

            function updateCardStudentCount(card, delta) {
                if (!card) {
                    return;
                }
                const badge = card.querySelector('[data-student-count]');
                if (!badge) {
                    return;
                }
                const current = toInt(badge.dataset.studentCount || badge.textContent);
                const nextValue = Math.max(0, current + delta);
                badge.dataset.studentCount = String(nextValue);
                badge.textContent = nextValue + ' students';
            }

            function updateRowSerials(tbody) {
                if (!tbody) {
                    return;
                }
                Array.from(tbody.querySelectorAll('tr')).forEach((tr, idx) => {
                    const firstCell = tr.querySelector('td');
                    if (firstCell) {
                        firstCell.textContent = idx + 1;
                    }
                });
            }

            function renderStudentRow(row, student, index) {
                row.dataset.studentId = student.id;
                row.dataset.classId = student.class_id;
                row.dataset.sectionId = student.section_id || '';
                row._student = Object.assign({}, student);

                row.innerHTML = '';

                const serialCell = document.createElement('td');
                serialCell.textContent = index;
                row.appendChild(serialCell);

                const rollCell = document.createElement('td');
                rollCell.textContent = student.roll_number ? student.roll_number : '';
                row.appendChild(rollCell);

                const sapCell = document.createElement('td');
                sapCell.textContent = student.sap_id ? student.sap_id : '';
                row.appendChild(sapCell);

                const nameCell = document.createElement('td');
                nameCell.textContent = student.name ? student.name : '';
                row.appendChild(nameCell);

                const classCell = document.createElement('td');
                classCell.textContent = student.class_name ? student.class_name : '';
                row.appendChild(classCell);

                const sectionCell = document.createElement('td');
                sectionCell.textContent = student.section_name ? student.section_name : 'N/A';
                row.appendChild(sectionCell);

                const actionsCell = document.createElement('td');
                actionsCell.className = 'inline-actions';
                const statusBox = row._statusBox || document.createElement('div');
                statusBox.className = 'inline-status';
                statusBox.style.display = 'none';
                row._statusBox = statusBox;
                actionsCell.appendChild(statusBox);

                const editBtn = document.createElement('button');
                editBtn.type = 'button';
                editBtn.className = 'inline-actions-btn';
                editBtn.textContent = 'Edit';
                editBtn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    enterStudentEditMode(row);
                });
                actionsCell.appendChild(editBtn);

                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.className = 'inline-actions-btn danger';
                deleteBtn.textContent = 'Delete';
                deleteBtn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    deleteStudent(row);
                });
                actionsCell.appendChild(deleteBtn);

                row.appendChild(actionsCell);
            }

            function exitStudentEditMode(row, updatedStudent, message) {
                row.classList.remove('editing');
                const tbody = row.parentElement;
                const index = Array.from(tbody.children).indexOf(row) + 1;
                if (updatedStudent) {
                    row._student = Object.assign({}, updatedStudent);
                }
                renderStudentRow(row, row._student, index);
                if (message) {
                    showInlineStatus(row, message.text, message.isError);
                }
            }

            function enterStudentEditMode(row) {
                if (row.classList.contains('editing')) {
                    return;
                }
                row.classList.add('editing');
                const student = Object.assign({}, row._student);
                const cells = row.querySelectorAll('td');
                const rollCell = cells[1];
                const sapCell = cells[2];
                const nameCell = cells[3];
                const classCell = cells[4];
                const sectionCell = cells[5];
                const actionsCell = cells[6];

                rollCell.innerHTML = '';
                const rollInput = document.createElement('input');
                rollInput.type = 'text';
                rollInput.value = student.roll_number || '';
                rollInput.className = 'inline-edit-input';
                rollCell.appendChild(rollInput);

                sapCell.innerHTML = '';
                const sapInput = document.createElement('input');
                sapInput.type = 'text';
                sapInput.value = student.sap_id || '';
                sapInput.className = 'inline-edit-input';
                sapCell.appendChild(sapInput);

                nameCell.innerHTML = '';
                const nameInput = document.createElement('input');
                nameInput.type = 'text';
                nameInput.value = student.name || '';
                nameInput.className = 'inline-edit-input';
                nameCell.appendChild(nameInput);

                classCell.innerHTML = '';
                const classSelect = document.createElement('select');
                classSelect.className = 'inline-edit-select';
                const classPlaceholder = document.createElement('option');
                classPlaceholder.value = '';
                classPlaceholder.textContent = '-- Select Class --';
                classSelect.appendChild(classPlaceholder);
                classesList.forEach(cls => {
                    const option = document.createElement('option');
                    option.value = cls.id;
                    option.textContent = cls.class_name || '';
                    if (toInt(cls.id) === toInt(student.class_id)) {
                        option.selected = true;
                    }
                    classSelect.appendChild(option);
                });
                classCell.appendChild(classSelect);

                sectionCell.innerHTML = '';
                const sectionSelect = document.createElement('select');
                sectionSelect.className = 'inline-edit-select';
                sectionCell.appendChild(sectionSelect);

                const customWrapper = document.createElement('div');
                customWrapper.className = 'inline-custom-section';
                customWrapper.style.display = 'none';
                const customLabel = document.createElement('label');
                customLabel.textContent = 'Custom Division Name';
                const customInput = document.createElement('input');
                customInput.type = 'text';
                customInput.className = 'inline-edit-input';
                customWrapper.appendChild(customLabel);
                customWrapper.appendChild(customInput);
                sectionCell.appendChild(customWrapper);

                populateSectionOptions(sectionSelect, student.class_id, student.section_id);
                handleCustomSectionVisibility(sectionSelect, customWrapper);

                classSelect.addEventListener('change', () => {
                    const selectedClassId = toInt(classSelect.value);
                    populateSectionOptions(sectionSelect, selectedClassId, 0);
                    handleCustomSectionVisibility(sectionSelect, customWrapper);
                });

                sectionSelect.addEventListener('change', () => {
                    handleCustomSectionVisibility(sectionSelect, customWrapper);
                });

                actionsCell.innerHTML = '';
                const statusBox = row._statusBox || document.createElement('div');
                statusBox.className = 'inline-status';
                statusBox.style.display = 'none';
                row._statusBox = statusBox;
                actionsCell.appendChild(statusBox);

                const saveBtn = document.createElement('button');
                saveBtn.type = 'button';
                saveBtn.className = 'inline-actions-btn';
                saveBtn.textContent = 'Save';
                actionsCell.appendChild(saveBtn);

                const cancelBtn = document.createElement('button');
                cancelBtn.type = 'button';
                cancelBtn.className = 'inline-actions-btn danger';
                cancelBtn.textContent = 'Cancel';
                actionsCell.appendChild(cancelBtn);

                row._editControls = {
                    rollInput,
                    sapInput,
                    nameInput,
                    classSelect,
                    sectionSelect,
                    customInput,
                    customWrapper
                };

                saveBtn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    submitStudentUpdate(row);
                });
                cancelBtn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    exitStudentEditMode(row);
                });
            }

            function submitStudentUpdate(row) {
                const controls = row._editControls || {};
                const rollValue = controls.rollInput ? controls.rollInput.value.trim() : '';
                const sapValue = controls.sapInput ? controls.sapInput.value.trim() : '';
                const nameValue = controls.nameInput ? controls.nameInput.value.trim() : '';
                const classValue = controls.classSelect ? toInt(controls.classSelect.value) : 0;
                const sectionSelectValue = controls.sectionSelect ? controls.sectionSelect.value : '';
                const customName = controls.customInput ? controls.customInput.value.trim() : '';

                if (!nameValue || !sapValue || !rollValue) {
                    showInlineStatus(row, 'Name, SAP ID, and Roll No are required.', true);
                    return;
                }
                if (!classValue) {
                    showInlineStatus(row, 'Please select a class.', true);
                    return;
                }

                let sectionMode = 'none';
                let sectionId = '';
                if (sectionSelectValue === 'other') {
                    sectionMode = 'custom';
                    if (!customName) {
                        showInlineStatus(row, 'Please provide a name for the new division.', true);
                        return;
                    }
                } else if (sectionSelectValue) {
                    sectionMode = 'existing';
                    sectionId = sectionSelectValue;
                }

                const formData = new FormData();
                formData.append('action', 'update_student_inline');
                formData.append('student_id', row._student.id);
                formData.append('full_name', nameValue);
                formData.append('sap_id', sapValue);
                formData.append('roll_number', rollValue);
                formData.append('class_id', classValue);
                formData.append('section_mode', sectionMode);
                if (sectionMode === 'existing') {
                    formData.append('section_id', sectionId);
                }
                if (sectionMode === 'custom') {
                    formData.append('section_name_custom', customName);
                }

                showInlineStatus(row, 'Saving changes...', false);

                fetch('bulk_add_students.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            showInlineStatus(row, data.message || 'Unable to save changes.', true);
                            return;
                        }

                        const updated = data.student;
                        if (!updated) {
                            showInlineStatus(row, 'Update completed, but no details were returned.', false);
                            exitStudentEditMode(row, row._student);
                            return;
                        }

                        const previousClassId = toInt(row._student.class_id);
                        const newClassId = toInt(updated.class_id);
                        const card = row.closest('.class-card');

                        if (!classSectionsMap[String(newClassId)]) {
                            classSectionsMap[String(newClassId)] = [];
                        }
                        if (updated.section_id && updated.section_name) {
                            const exists = classSectionsMap[String(newClassId)].some(sec => toInt(sec.id) === toInt(updated.section_id));
                            if (!exists) {
                                const classMeta = classMetaMap[String(newClassId)] || {};
                                const generatedLabel = formatClassLabelValue(classMeta.class_name, updated.section_name, classMeta.semester, classMeta.school);
                                classSectionsMap[String(newClassId)].push({
                                    id: updated.section_id,
                                    name: updated.section_name,
                                    label: generatedLabel || updated.section_name
                                });
                            }
                        }

                        if (previousClassId !== newClassId) {
                            const tbody = row.parentElement;
                            tbody.removeChild(row);
                            updateRowSerials(tbody);
                            if (card) {
                                updateCardStudentCount(card, -1);
                                showCardStatus(card, 'Student moved to ' + (updated.class_name || 'another class') + '.', false);
                            }
                            return;
                        }

                        exitStudentEditMode(row, updated, { text: data.message || 'Student updated.', isError: false });
                    })
                    .catch(() => {
                        showInlineStatus(row, 'Failed to save changes.', true);
                    });
            }

            function deleteStudent(row) {
                if (!confirm('Are you sure you want to delete this student?')) {
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'delete_student_inline');
                formData.append('student_id', row._student.id);

                showInlineStatus(row, 'Deleting student...', false);

                fetch('bulk_add_students.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            showInlineStatus(row, data.message || 'Unable to delete student.', true);
                            return;
                        }
                        const card = row.closest('.class-card');
                        const tbody = row.parentElement;
                        tbody.removeChild(row);
                        updateRowSerials(tbody);
                        if (card) {
                            updateCardStudentCount(card, -1);
                            showCardStatus(card, data.message || 'Student deleted successfully.', false);
                        }
                    })
                    .catch(() => {
                        showInlineStatus(row, 'Failed to delete student.', true);
                    });
            }

            function attachClassCardHandlers() {
                document.querySelectorAll('.class-card').forEach(card => {
                    card.addEventListener('click', function (event) {
                        const headerHit = event.target.closest('.class-card-main');
                        if (!headerHit || event.target.closest('.timetable-upload-form') || event.target.closest('.card-expand')) {
                            return;
                        }
                        const expandDiv = this.querySelector('.card-expand');
                        if (!expandDiv) {
                            return;
                        }
                        if (expandDiv.dataset.loaded === '1') {
                            if (expandDiv.querySelector('tr.editing')) {
                                showCardStatus(this, 'Finish or cancel the active edit before collapsing the list.', true);
                                return;
                            }
                            expandDiv.style.display = expandDiv.style.display === 'none' ? 'block' : 'none';
                            return;
                        }

                        const classId = this.dataset.classId;
                        const sectionId = this.dataset.sectionId || 0;
                        expandDiv.style.display = 'block';
                        expandDiv.innerHTML = '<em>Loading students...</em>';

                        fetch('get_students_for_class.php?class_id=' + encodeURIComponent(classId) + '&section_id=' + encodeURIComponent(sectionId))
                            .then(response => response.json())
                            .then(students => {
                                if (!Array.isArray(students) || students.length === 0) {
                                    expandDiv.innerHTML = '<div>No students found for this class/section.</div>';
                                    expandDiv.dataset.loaded = '1';
                                    return;
                                }
                                expandDiv.innerHTML = '';
                                const statusBox = document.createElement('div');
                                statusBox.className = 'card-inline-status';
                                statusBox.style.display = 'none';
                                expandDiv._statusBox = statusBox;
                                expandDiv.appendChild(statusBox);

                                const table = document.createElement('table');
                                table.className = 'inline-student-table';
                                table.innerHTML = '<thead><tr><th>#</th><th>Roll No</th><th>SAP ID</th><th>Name</th><th>Class</th><th>Division</th><th>Actions</th></tr></thead>';
                                const tbody = document.createElement('tbody');
                                students.forEach((student, idx) => {
                                    const row = document.createElement('tr');
                                    renderStudentRow(row, student, idx + 1);
                                    tbody.appendChild(row);
                                });
                                table.appendChild(tbody);
                                expandDiv.appendChild(table);
                                expandDiv.dataset.loaded = '1';
                            })
                            .catch(() => {
                                expandDiv.innerHTML = '<div>Error loading student list.</div>';
                            });
                    });
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                applyStoredTheme();
                setupDependentDropdowns('school_single', 'semester_single', 'class_id_single', 'section_container_single', 'section_id_single', 'section_other_container_single', 'section_name_other_single', '#semester_single_filters');
                setupDependentDropdowns('school_bulk', 'semester_bulk', 'class_id_bulk', 'section_container_bulk', 'section_id_bulk', 'section_other_container_bulk', 'section_name_other_bulk', '#semester_bulk_filters');
                setupDependentDropdowns('copy_source_school', 'copy_source_semester', 'copy_source_class', 'copy_source_section_container', 'copy_source_section_select', 'copy_source_section_other_container', 'copy_source_section_custom', '#copy_source_semester_filters');
                setupDependentDropdowns('copy_target_school', 'copy_target_semester', 'copy_target_class', 'copy_target_section_container', 'copy_target_section_select', 'copy_target_section_other_container', 'copy_target_section_custom', '#copy_target_semester_filters');

                (function () {
                    const filterButtons = Array.from(document.querySelectorAll('#existing_students_filters .term-filter-button'));
                    const semesterGroups = Array.from(document.querySelectorAll('[data-semester-group]'));
                    if (!filterButtons.length || !semesterGroups.length) {
                        return;
                    }
                    let activeFilter = '';
                    filterButtons.forEach(function (button) {
                        button.addEventListener('click', function () {
                            const target = this.dataset.filter || '';
                            if (activeFilter === target) {
                                activeFilter = '';
                                filterButtons.forEach(function (btn) {
                                    btn.classList.remove('active');
                                });
                            } else {
                                activeFilter = target;
                                filterButtons.forEach(function (btn) {
                                    btn.classList.toggle('active', btn === button);
                                });
                            }
                            semesterGroups.forEach(function (group) {
                                const parity = group.getAttribute('data-parity');
                                group.style.display = !activeFilter || parity === activeFilter ? '' : 'none';
                            });
                        });
                    });
                })();

                attachClassCardHandlers();

                document.querySelectorAll('.timetable-edit-toggle').forEach(function (button) {
                    button.addEventListener('click', function (event) {
                        event.preventDefault();
                        const container = button.closest('.timetable-edit');
                        if (!container) {
                            return;
                        }
                        const form = container.querySelector('.timetable-delete-form');
                        if (!form) {
                            return;
                        }
                        const isVisible = form.classList.toggle('is-visible');
                        form.style.display = isVisible ? 'block' : 'none';
                    });
                });
            });

            window.toggleTheme = toggleTheme;
            window.showTab = showTab;
        })();
    </script>
</body>
</html>

