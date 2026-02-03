<?php
session_start();
include 'db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/assignment_helpers.php';
require_once __DIR__ . '/includes/academic_context.php';

ensure_assignment_schema($conn);
$storagePaths = ensure_assignment_storage();

$teacherId = (int)$_SESSION['user_id'];
$teacherNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$teacherName = $teacherNameRaw;
$teacherNameDisplay = $teacherNameRaw !== '' ? format_person_display($teacherNameRaw) : '';
$teacherSchool = isset($_SESSION['school']) ? trim((string)$_SESSION['school']) : '';

$activeTermId = null;
if ($teacherSchool !== '') {
    $academicContext = resolveAcademicContext($conn, [
        'school_name' => $teacherSchool
    ]);
    $activeTerm = $academicContext['active'] ?? null;
    if ($activeTerm && isset($activeTerm['id'])) {
        $activeTermId = (int)$activeTerm['id'];
    }
} else {
    $academicContext = resolveAcademicContext($conn, []);
    $activeTerm = $academicContext['active'] ?? null;
    if ($activeTerm && isset($activeTerm['id'])) {
        $activeTermId = (int)$activeTerm['id'];
    }
}

$classOptions = [];
$subjectLookup = [];
$classSubjectsMap = [];

$classSql = "SELECT tsa.class_id, tsa.section_id, tsa.subject_id, c.class_name, c.semester, c.school, sec.section_name, s.subject_name\n    FROM teacher_subject_assignments tsa\n    JOIN classes c ON c.id = tsa.class_id\n    LEFT JOIN sections sec ON sec.id = tsa.section_id\n    JOIN subjects s ON s.id = tsa.subject_id\n    WHERE tsa.teacher_id = ?";
if ($activeTermId !== null) {
    $classSql .= " AND (c.academic_term_id = " . (int)$activeTermId . " OR c.academic_term_id IS NULL)";
}
$classSql .= "\n    ORDER BY c.class_name, sec.section_name, s.subject_name";
$classStmt = mysqli_prepare($conn, $classSql);
if ($classStmt) {
    mysqli_stmt_bind_param($classStmt, 'i', $teacherId);
    mysqli_stmt_execute($classStmt);
    $classResult = mysqli_stmt_get_result($classStmt);
    while ($classResult && ($row = mysqli_fetch_assoc($classResult))) {
        $classId = (int)$row['class_id'];
        $sectionId = isset($row['section_id']) ? (int)$row['section_id'] : 0;
        $subjectId = isset($row['subject_id']) ? (int)$row['subject_id'] : 0;
        if ($classId <= 0 || $subjectId <= 0) {
            continue;
        }
        $classKey = $classId . ':' . $sectionId;
        if (!isset($classOptions[$classKey])) {
            $labelText = format_class_label(
                $row['class_name'] ?? '',
                $row['section_name'] ?? '',
                $row['semester'] ?? '',
                $row['school'] ?? ''
            );
            $classOptions[$classKey] = [
                'class_id' => $classId,
                'section_id' => $sectionId,
                'label' => $labelText,
                'subjects' => []
            ];
        }
        $subjectName = trim((string)$row['subject_name']);
        $classOptions[$classKey]['subjects'][$subjectId] = $subjectName;
        if (!isset($subjectLookup[$subjectId])) {
            $subjectLookup[$subjectId] = $subjectName;
        }
    }
    if ($classResult) {
        mysqli_free_result($classResult);
    }
    mysqli_stmt_close($classStmt);
}

foreach ($classOptions as $key => $meta) {
    $classSubjectsMap[$key] = [];
    foreach ($meta['subjects'] as $id => $name) {
        $classSubjectsMap[$key][] = [
            'id' => $id,
            'name' => $name
        ];
    }
}

// Build student lists per class/section for targeted assignment
$classStudentsMap = [];
foreach ($classOptions as $key => $meta) {
    $classId = (int)$meta['class_id'];
    $sectionId = (int)$meta['section_id'];
    $studentSql = 'SELECT id, name, roll_number, sap_id FROM students WHERE class_id = ?';
    $hasSection = $sectionId > 0;
    if ($hasSection) {
        $studentSql .= ' AND (section_id = ? OR section_id IS NULL)';
    }
        $studentSql .= ' ORDER BY name';
    $stmt = mysqli_prepare($conn, $studentSql);
    if ($stmt) {
        if ($hasSection) {
            mysqli_stmt_bind_param($stmt, 'ii', $classId, $sectionId);
        } else {
            mysqli_stmt_bind_param($stmt, 'i', $classId);
        }
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $classStudentsMap[$key] = [];
        while ($res && ($row = mysqli_fetch_assoc($res))) {
            $classStudentsMap[$key][] = [
                'id' => (int)$row['id'],
                'name' => $row['name'] ?? 'Student',
                'roll' => $row['roll_number'] ?? '',
                'sap' => $row['sap_id'] ?? ''
            ];
        }
        if ($res) {
            mysqli_free_result($res);
        }
        mysqli_stmt_close($stmt);
    }
}

$formErrors = [];
$formSuccess = '';
$selectedAssignmentId = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
$formState = [
    'class_key' => '',
    'subject_id' => '',
    'title' => '',
    'assignment_type' => '',
    'assignment_number' => '',
    'start_at' => '',
    'due_at' => '',
    'max_marks' => '',
    'description' => '',
    'student_ids' => []
];

$allowedTypes = [
    'Written Assignment',
    'Lab Work',
    'Project',
    'Presentation',
    'Quiz',
    'Other'
];

function parse_datetime_local(string $input): ?string {
    if ($input === '') {
        return null;
    }
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $input);
    if ($dt === false) {
        return null;
    }
    return $dt->format('Y-m-d H:i:00');
}

function format_datetime_local(?string $value): string {
    if ($value === null || $value === '') {
        return '';
    }
    $dt = date_create($value);
    if (!$dt) {
        return '';
    }
    return $dt->format('Y-m-d\TH:i');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_assignment') {
        $classKey = trim($_POST['class_key'] ?? '');
        $subjectId = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $assignmentType = trim($_POST['assignment_type'] ?? '');
        $assignmentNumber = trim($_POST['assignment_number'] ?? '');
        $startInput = trim($_POST['start_at'] ?? '');
        $dueInput = trim($_POST['due_at'] ?? '');
        $maxMarksInput = trim($_POST['max_marks'] ?? '');
        $studentIdsInput = isset($_POST['student_ids']) && is_array($_POST['student_ids']) ? $_POST['student_ids'] : [];
        $selectedStudentIds = [];
        foreach ($studentIdsInput as $sid) {
            $sidInt = (int)$sid;
            if ($sidInt > 0) {
                $selectedStudentIds[$sidInt] = true;
            }
        }

        $formState = [
            'class_key' => $classKey,
            'subject_id' => $subjectId > 0 ? (string)$subjectId : '',
            'title' => $title,
            'assignment_type' => $assignmentType,
            'assignment_number' => $assignmentNumber,
            'start_at' => $startInput,
            'due_at' => $dueInput,
            'max_marks' => $maxMarksInput,
            'description' => $description,
            'student_ids' => array_keys($selectedStudentIds)
        ];

        if ($classKey === '' || !isset($classOptions[$classKey])) {
            $formErrors[] = 'Select a class for this assignment.';
        }
        if ($subjectId <= 0 || !isset($classOptions[$classKey]['subjects'][$subjectId])) {
            $formErrors[] = 'Select a subject taught for the chosen class.';
        }
        if ($title === '') {
            $formErrors[] = 'Assignment name is required.';
        }
        if ($assignmentNumber === '') {
            $formErrors[] = 'Assignment number is required.';
        }
        if ($assignmentType === '' || !in_array($assignmentType, $allowedTypes, true)) {
            $formErrors[] = 'Choose a valid assignment type.';
        }
        if ($description === '') {
            $formErrors[] = 'Provide assignment details or instructions.';
        }

        $startAt = parse_datetime_local($startInput);
        $dueAt = parse_datetime_local($dueInput);
        if ($startAt === null) {
            $formErrors[] = 'Enter a valid start date and time.';
        }
        if ($dueAt === null) {
            $formErrors[] = 'Enter a valid due date and time.';
        }
        if ($startAt !== null && $dueAt !== null && strtotime($dueAt) <= strtotime($startAt)) {
            $formErrors[] = 'Due date must be after the start date.';
        }

        $maxMarks = null;
        if ($maxMarksInput !== '') {
            if (!is_numeric($maxMarksInput)) {
                $formErrors[] = 'Marks should be a number.';
            } else {
                $maxMarks = (float)$maxMarksInput;
                if ($maxMarks < 0) {
                    $formErrors[] = 'Marks cannot be negative.';
                }
            }
        }

        $instructionsPath = null;
        if (!empty($_FILES['instructions_file']['name'])) {
            $fileError = $_FILES['instructions_file']['error'];
            $fileSize = $_FILES['instructions_file']['size'];
            if ($fileError !== UPLOAD_ERR_OK) {
                $formErrors[] = 'Failed to upload the assignment file.';
            } elseif ($fileSize > 20 * 1024 * 1024) {
                $formErrors[] = 'Assignment file must be 20MB or smaller.';
            } else {
                $safeName = assignment_safe_filename($_FILES['instructions_file']['name']);
                $targetPath = $storagePaths['teacher'] . DIRECTORY_SEPARATOR . $safeName;
                if (!move_uploaded_file($_FILES['instructions_file']['tmp_name'], $targetPath)) {
                    $formErrors[] = 'Unable to store the assignment file.';
                } else {
                    $instructionsPath = 'uploads/assignments/teacher_files/' . $safeName;
                }
            }
        }

        if (empty($formErrors)) {
            $classMeta = $classOptions[$classKey];
            $classId = (int)$classMeta['class_id'];
            $sectionId = (int)$classMeta['section_id'];
            $subjectName = $classMeta['subjects'][$subjectId];
            $deadlineDate = date('Y-m-d', strtotime((string)$dueAt));

            $studentsSql = 'SELECT id FROM students WHERE class_id = ?';
            if ($sectionId > 0) {
                $studentsSql .= ' AND section_id = ?';
            }
            $studentsStmt = mysqli_prepare($conn, $studentsSql);
            if (!$studentsStmt) {
                $formErrors[] = 'Unable to prepare student lookup.';
            } else {
                if ($sectionId > 0) {
                    mysqli_stmt_bind_param($studentsStmt, 'ii', $classId, $sectionId);
                } else {
                    mysqli_stmt_bind_param($studentsStmt, 'i', $classId);
                }
                mysqli_stmt_execute($studentsStmt);
                $studentsResult = mysqli_stmt_get_result($studentsStmt);
                $studentIds = [];
                while ($studentsResult && ($studentRow = mysqli_fetch_assoc($studentsResult))) {
                    $studentIds[] = (int)$studentRow['id'];
                }
                if ($studentsResult) {
                    mysqli_free_result($studentsResult);
                }
                mysqli_stmt_close($studentsStmt);

                if (!empty($selectedStudentIds)) {
                    // Limit to selected students that belong to this class/section
                    $studentIds = array_values(array_intersect($studentIds, array_keys($selectedStudentIds)));
                }

                if (empty($studentIds)) {
                    if (!empty($selectedStudentIds)) {
                        $formErrors[] = 'The chosen students are not in this class/section. Please reselect.';
                    } else {
                        $formErrors[] = 'No students found for the selected class. Assignments could not be created.';
                    }
                } else {
                    mysqli_begin_transaction($conn);
                    try {
                        $insertSql = "INSERT INTO assignments (teacher_id, class_id, section_id, subject_id, subject, title, description, assignment_type, assignment_number, start_at, due_at, deadline, max_marks, instructions_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $insertStmt = mysqli_prepare($conn, $insertSql);
                        if (!$insertStmt) {
                            throw new Exception('Unable to prepare assignment save.');
                        }
                        mysqli_stmt_bind_param(
                            $insertStmt,
                            'iiiissssssssss',
                            $teacherId,
                            $classId,
                            $sectionId,
                            $subjectId,
                            $subjectName,
                            $title,
                            $description,
                            $assignmentType,
                            $assignmentNumber,
                            $startAt,
                            $dueAt,
                            $deadlineDate,
                            $maxMarks,
                            $instructionsPath
                        );
                        mysqli_stmt_execute($insertStmt);
                        $assignmentId = mysqli_insert_id($conn);
                        mysqli_stmt_close($insertStmt);

                        $studentInsertSql = "INSERT INTO student_assignments (student_id, assignment_id, submission_status, assignment_status, submission_state, marks_obtained) VALUES (?, ?, 'pending', 'pending', 'pending', NULL)";
                        $studentInsertStmt = mysqli_prepare($conn, $studentInsertSql);
                        if (!$studentInsertStmt) {
                            throw new Exception('Unable to prepare student assignment entries.');
                        }
                        foreach ($studentIds as $studentId) {
                            mysqli_stmt_bind_param($studentInsertStmt, 'ii', $studentId, $assignmentId);
                            mysqli_stmt_execute($studentInsertStmt);
                        }
                        mysqli_stmt_close($studentInsertStmt);

                        mysqli_commit($conn);
                        $formSuccess = 'Assignment created and shared with the class.';
                        $formState = [
                            'class_key' => '',
                            'subject_id' => '',
                            'title' => '',
                            'assignment_type' => '',
                            'assignment_number' => '',
                            'start_at' => '',
                            'due_at' => '',
                            'max_marks' => '',
                            'description' => '',
                            'student_ids' => []
                        ];
                        $selectedAssignmentId = $assignmentId;
                    } catch (Throwable $ex) {
                        mysqli_rollback($conn);
                        $formErrors[] = $ex->getMessage();
                        if ($instructionsPath) {
                            @unlink(__DIR__ . '/' . $instructionsPath);
                        }
                    }
                }
            }
        }
    } elseif ($action === 'update_assignment') {
        $editId = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $assignmentType = trim($_POST['assignment_type'] ?? '');
        $assignmentNumber = trim($_POST['assignment_number'] ?? '');
        $startInput = trim($_POST['start_at'] ?? '');
        $dueInput = trim($_POST['due_at'] ?? '');
        $maxMarksInput = trim($_POST['max_marks'] ?? '');

        if ($editId <= 0) {
            $formErrors[] = 'Invalid assignment selected for update.';
        } else {
            $startAt = parse_datetime_local($startInput);
            $dueAt = parse_datetime_local($dueInput);
            if ($title === '') {
                $formErrors[] = 'Assignment name is required.';
            }
            if ($assignmentNumber === '') {
                $formErrors[] = 'Assignment number is required.';
            }
            if ($assignmentType === '' || !in_array($assignmentType, $allowedTypes, true)) {
                $formErrors[] = 'Choose a valid assignment type.';
            }
            if ($description === '') {
                $formErrors[] = 'Provide assignment details or instructions.';
            }
            if ($startAt === null || $dueAt === null) {
                $formErrors[] = 'Enter valid start and due dates.';
            } elseif (strtotime($dueAt) <= strtotime($startAt)) {
                $formErrors[] = 'Due date must be after the start date.';
            }
            $maxMarks = null;
            if ($maxMarksInput !== '') {
                if (!is_numeric($maxMarksInput)) {
                    $formErrors[] = 'Marks should be numeric.';
                } else {
                    $maxMarks = (float)$maxMarksInput;
                    if ($maxMarks < 0) {
                        $formErrors[] = 'Marks cannot be negative.';
                    }
                }
            }

            $currentFile = null;
            if (empty($formErrors)) {
                $lookupStmt = mysqli_prepare($conn, 'SELECT instructions_file FROM assignments WHERE id = ? AND teacher_id = ? LIMIT 1');
                if ($lookupStmt) {
                    mysqli_stmt_bind_param($lookupStmt, 'ii', $editId, $teacherId);
                    mysqli_stmt_execute($lookupStmt);
                    $lookupResult = mysqli_stmt_get_result($lookupStmt);
                    $row = $lookupResult ? mysqli_fetch_assoc($lookupResult) : null;
                    if ($lookupResult) {
                        mysqli_free_result($lookupResult);
                    }
                    mysqli_stmt_close($lookupStmt);
                    if (!$row) {
                        $formErrors[] = 'Assignment not found or access denied.';
                    } else {
                        $currentFile = $row['instructions_file'] ?? null;
                    }
                } else {
                    $formErrors[] = 'Unable to load assignment details.';
                }
            }

            $newFilePath = $currentFile;
            if (empty($formErrors) && !empty($_FILES['instructions_file']['name'])) {
                $fileError = $_FILES['instructions_file']['error'];
                $fileSize = $_FILES['instructions_file']['size'];
                if ($fileError !== UPLOAD_ERR_OK) {
                    $formErrors[] = 'Failed to upload the assignment file.';
                } elseif ($fileSize > 20 * 1024 * 1024) {
                    $formErrors[] = 'Assignment file must be 20MB or smaller.';
                } else {
                    $safeName = assignment_safe_filename($_FILES['instructions_file']['name']);
                    $targetPath = $storagePaths['teacher'] . DIRECTORY_SEPARATOR . $safeName;
                    if (!move_uploaded_file($_FILES['instructions_file']['tmp_name'], $targetPath)) {
                        $formErrors[] = 'Unable to store the assignment file.';
                    } else {
                        $newFilePath = 'uploads/assignments/teacher_files/' . $safeName;
                    }
                }
            }

            if (empty($formErrors)) {
                $deadlineDate = date('Y-m-d', strtotime((string)$dueAt));
                $updateSql = "UPDATE assignments SET title = ?, description = ?, assignment_type = ?, assignment_number = ?, start_at = ?, due_at = ?, deadline = ?, max_marks = ?, instructions_file = ?, updated_at = NOW() WHERE id = ? AND teacher_id = ?";
                $updateStmt = mysqli_prepare($conn, $updateSql);
                if ($updateStmt) {
                    mysqli_stmt_bind_param(
                        $updateStmt,
                        'sssssssssii',
                        $title,
                        $description,
                        $assignmentType,
                        $assignmentNumber,
                        $startAt,
                        $dueAt,
                        $deadlineDate,
                        $maxMarks,
                        $newFilePath,
                        $editId,
                        $teacherId
                    );
                    mysqli_stmt_execute($updateStmt);
                    mysqli_stmt_close($updateStmt);
                    if ($newFilePath !== $currentFile && $currentFile) {
                        @unlink(__DIR__ . '/' . $currentFile);
                    }
                    $formSuccess = 'Assignment details updated.';
                    $selectedAssignmentId = $editId;
                } else {
                    $formErrors[] = 'Unable to update the assignment right now.';
                    if ($newFilePath !== $currentFile && $newFilePath) {
                        @unlink(__DIR__ . '/' . $newFilePath);
                    }
                }
            } elseif (isset($newFilePath) && $newFilePath !== $currentFile && $newFilePath) {
                @unlink(__DIR__ . '/' . $newFilePath);
            }
        }
    } elseif ($action === 'update_submission') {
        $assignmentId = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;
        $studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
        $statusRaw = trim($_POST['assignment_status'] ?? '');
        $marksInput = trim($_POST['graded_marks'] ?? '');
        $feedback = trim($_POST['teacher_feedback'] ?? '');

        if ($assignmentId <= 0 || $studentId <= 0) {
            $formErrors[] = 'Invalid submission selected.';
        } else {
            $status = assignment_normalize_status($statusRaw);
            if ($status === 'submitted') {
                $status = 'completed';
            }
            $allowedStatus = ['pending', 'late_submitted', 'completed', 'rejected'];
            if (!in_array($status, $allowedStatus, true)) {
                $formErrors[] = 'Choose a valid status update.';
            }
            $gradedMarks = null;
            if ($marksInput !== '') {
                if (!is_numeric($marksInput)) {
                    $formErrors[] = 'Marks should be numeric.';
                } else {
                    $gradedMarks = (float)$marksInput;
                }
            }
            if (empty($formErrors)) {
                $submissionSql = "SELECT sa.id, sa.assignment_status, sa.submitted_file_path, sa.feedback_file_path\n                    FROM student_assignments sa\n                    JOIN assignments a ON a.id = sa.assignment_id\n                    WHERE sa.assignment_id = ? AND sa.student_id = ? AND a.teacher_id = ?\n                    LIMIT 1";
                $submissionStmt = mysqli_prepare($conn, $submissionSql);
                if ($submissionStmt) {
                    mysqli_stmt_bind_param($submissionStmt, 'iii', $assignmentId, $studentId, $teacherId);
                    mysqli_stmt_execute($submissionStmt);
                    $submissionResult = mysqli_stmt_get_result($submissionStmt);
                    $submissionRow = $submissionResult ? mysqli_fetch_assoc($submissionResult) : null;
                    if ($submissionResult) {
                        mysqli_free_result($submissionResult);
                    }
                    mysqli_stmt_close($submissionStmt);
                    if (!$submissionRow) {
                        $formErrors[] = 'Submission not found or access denied.';
                    } else {
                        $feedbackFilePath = $submissionRow['feedback_file_path'] ?? null;
                        if (!empty($_FILES['feedback_file']['name'])) {
                            $fileError = $_FILES['feedback_file']['error'];
                            $fileSize = $_FILES['feedback_file']['size'];
                            if ($fileError !== UPLOAD_ERR_OK) {
                                $formErrors[] = 'Failed to upload the feedback file.';
                            } elseif ($fileSize > 20 * 1024 * 1024) {
                                $formErrors[] = 'Feedback file must be 20MB or smaller.';
                            } else {
                                $safeName = assignment_safe_filename($_FILES['feedback_file']['name']);
                                $targetPath = $storagePaths['feedback'] . DIRECTORY_SEPARATOR . $safeName;
                                if (!move_uploaded_file($_FILES['feedback_file']['tmp_name'], $targetPath)) {
                                    $formErrors[] = 'Unable to store the feedback file.';
                                } else {
                                    if ($feedbackFilePath) {
                                        @unlink(__DIR__ . '/' . $feedbackFilePath);
                                    }
                                    $feedbackFilePath = 'uploads/assignments/feedback/' . $safeName;
                                }
                            }
                        }

                        if (empty($formErrors)) {
                            $submissionState = ($status === 'completed') ? 'evaluated' : 'review_pending';
                            if ($status === 'pending') {
                                $submissionState = 'pending';
                                $feedbackFilePath = $feedbackFilePath ?: null;
                            }
                            $updateSql = "UPDATE student_assignments SET assignment_status = ?, submission_state = ?, graded_marks = ?, teacher_feedback = ?, feedback_file_path = ?, reviewed_at = NOW(), reviewed_by = ? WHERE assignment_id = ? AND student_id = ?";
                            $updateStmt = mysqli_prepare($conn, $updateSql);
                            if ($updateStmt) {
                                $feedbackValue = ($feedback !== '') ? $feedback : null;
                                mysqli_stmt_bind_param(
                                    $updateStmt,
                                    'sssssiii',
                                    $status,
                                    $submissionState,
                                    $gradedMarks,
                                    $feedbackValue,
                                    $feedbackFilePath,
                                    $teacherId,
                                    $assignmentId,
                                    $studentId
                                );
                                mysqli_stmt_execute($updateStmt);
                                mysqli_stmt_close($updateStmt);
                                $formSuccess = 'Submission updated successfully.';
                                $selectedAssignmentId = $assignmentId;
                            } else {
                                $formErrors[] = 'Unable to update the submission.';
                                if (!empty($_FILES['feedback_file']['name']) && isset($feedbackFilePath)) {
                                    @unlink(__DIR__ . '/' . $feedbackFilePath);
                                }
                            }
                        }
                    }
                } else {
                    $formErrors[] = 'Unable to load submission details.';
                }
            }
        }
    }
}

$assignments = [];
$assignmentSummarySql = "SELECT a.id, a.title, a.assignment_type, a.assignment_number, a.start_at, a.due_at, a.deadline, a.max_marks, a.instructions_file, a.subject_id, a.subject, a.class_id, a.section_id, c.class_name, c.semester, c.school, sec.section_name, s.subject_name,\n    SUM(CASE WHEN sa.assignment_status = 'completed' THEN 1 ELSE 0 END) AS completed_count,\n    SUM(CASE WHEN sa.assignment_status = 'pending' THEN 1 ELSE 0 END) AS pending_count,\n    SUM(CASE WHEN sa.assignment_status = 'late_submitted' THEN 1 ELSE 0 END) AS late_count,\n    SUM(CASE WHEN sa.assignment_status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,\n    COUNT(sa.id) AS total_students\n    FROM assignments a\n    LEFT JOIN classes c ON c.id = a.class_id\n    LEFT JOIN sections sec ON sec.id = a.section_id\n    LEFT JOIN subjects s ON s.id = a.subject_id\n    LEFT JOIN student_assignments sa ON sa.assignment_id = a.id\n    WHERE a.teacher_id = ?";
if ($activeTermId !== null) {
    $assignmentSummarySql .= " AND (c.academic_term_id = " . (int)$activeTermId . " OR c.academic_term_id IS NULL)";
}
$assignmentSummarySql .= "\n    GROUP BY a.id, a.title, a.assignment_type, a.assignment_number, a.start_at, a.due_at, a.deadline, a.max_marks, a.instructions_file, a.subject_id, a.subject, a.class_id, a.section_id, c.class_name, c.semester, c.school, sec.section_name, s.subject_name\n    ORDER BY (a.due_at IS NULL), a.due_at DESC, a.created_at DESC";
$assignmentSummaryStmt = mysqli_prepare($conn, $assignmentSummarySql);
if ($assignmentSummaryStmt) {
    mysqli_stmt_bind_param($assignmentSummaryStmt, 'i', $teacherId);
    mysqli_stmt_execute($assignmentSummaryStmt);
    $assignmentSummaryResult = mysqli_stmt_get_result($assignmentSummaryStmt);
    while ($assignmentSummaryResult && ($row = mysqli_fetch_assoc($assignmentSummaryResult))) {
        $assignments[] = $row;
    }
    if ($assignmentSummaryResult) {
        mysqli_free_result($assignmentSummaryResult);
    }
    mysqli_stmt_close($assignmentSummaryStmt);
}

$submissionDetails = [];
$selectedAssignmentMeta = null;
if ($selectedAssignmentId > 0) {
    foreach ($assignments as $item) {
        if ((int)$item['id'] === $selectedAssignmentId) {
            $selectedAssignmentMeta = $item;
            break;
        }
    }
    if ($selectedAssignmentMeta) {
        $submissionSql = "SELECT sa.assignment_id, sa.student_id, sa.assignment_status, sa.submission_state, sa.submission_date, sa.last_submission_at, sa.submitted_file_path, sa.graded_marks, sa.teacher_feedback, sa.feedback_file_path, st.name, st.roll_number, st.sap_id\n            FROM student_assignments sa\n            JOIN students st ON st.id = sa.student_id\n            WHERE sa.assignment_id = ?\n            ORDER BY st.name";
        $submissionStmt = mysqli_prepare($conn, $submissionSql);
        if ($submissionStmt) {
            mysqli_stmt_bind_param($submissionStmt, 'i', $selectedAssignmentId);
            mysqli_stmt_execute($submissionStmt);
            $submissionResult = mysqli_stmt_get_result($submissionStmt);
            while ($submissionResult && ($row = mysqli_fetch_assoc($submissionResult))) {
                $submissionDetails[] = $row;
            }
            if ($submissionResult) {
                mysqli_free_result($submissionResult);
            }
            mysqli_stmt_close($submissionStmt);
        }
    }
}

mysqli_close($conn);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Assignments - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .card-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
        .status-pill { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-size:0.8rem; font-weight:600; text-transform:capitalize; }
        .status-pill.pending { background:#fff4e5; color:#a86a08; }
        .status-pill.completed { background:#e6f5ed; color:#1e7042; }
        .status-pill.late_submitted { background:#fff1f1; color:#b01928; }
        .status-pill.rejected { background:#ffe7ed; color:#9f1d35; }
        .form-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); gap:16px; }
        .form-grid .form-group { margin-bottom:0; }
        .table-responsive { overflow-x:auto; }
        .assignments-table { width:100%; border-collapse:collapse; }
        .assignments-table th, .assignments-table td { padding:10px 12px; border-bottom:1px solid #e6e6e6; text-align:left; }
        .assignments-table th { font-weight:600; font-size:0.9rem; }
        .assignments-table tbody tr:hover { background:#fafafa; }
        .badge-count { display:inline-flex; align-items:center; padding:4px 8px; border-radius:12px; font-size:0.8rem; margin-right:4px; background:#f1f3f5; }
        .badge-count.success { background:#e6f5ed; color:#1e7042; }
        .badge-count.warning { background:#fff4e5; color:#a86a08; }
        .badge-count.danger { background:#ffe7ed; color:#9f1d35; }
        .inline-form { display:flex; flex-wrap:wrap; gap:8px; align-items:flex-end; }
        .inline-form .form-group { min-width:140px; margin-bottom:0; }
        .inline-form button { margin-top:0; }
        .file-links { display:flex; flex-direction:column; gap:4px; }
        .section-divider { border-top:1px solid #e1e1e1; margin:24px 0; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h2>ICA Tracker</h2>
            <a href="teacher_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="update_progress.php"><i class="fas fa-chart-line"></i> <span>Update Progress</span></a>
            <a href="create_ica_components.php"><i class="fas fa-cogs"></i> <span>ICA Components</span></a>
            <a href="manage_ica_marks.php"><i class="fas fa-book"></i> <span>Manage ICA Marks</span></a>
            <a href="assignments.php" class="active"><i class="fas fa-tasks"></i> <span>Assignments</span></a>
            <a href="view_alerts.php"><i class="fas fa-bell"></i> <span>View Alerts</span></a>
            <a href="view_reports.php"><i class="fas fa-file-alt"></i> <span>View Reports</span></a>
            <a href="timetable.php"><i class="fas fa-calendar-alt"></i> <span>Timetable</span></a>
            <a href="edit_profile.php"><i class="fas fa-user-edit"></i> <span>Edit Profile</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
        <div class="main-content">
            <div class="header">
                <div>
                    <h2>Assignments Hub</h2>
                    <p>Plan assignments, track submissions, and respond to student uploads in one place.</p>
                </div>
            </div>
            <div class="container">
                <?php if (!empty($formErrors)): ?>
                    <div class="alert alert-error">
                        <strong>Fix the following:</strong>
                        <ul style="margin:8px 0 0 18px;">
                            <?php foreach ($formErrors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php elseif ($formSuccess !== ''): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($formSuccess); ?></div>
                <?php endif; ?>

                <div class="card" style="border-left:4px solid #A6192E;">
                    <div class="card-header"><h5>Create Assignment</h5></div>
                    <div class="card-body">
                        <?php if (empty($classOptions)): ?>
                            <p class="table-note">No classes are linked to your profile yet. Please check with the program chair.</p>
                        <?php else: ?>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="create_assignment">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Select Class</label>
                                    <select name="class_key" required>
                                        <option value="">Choose class</option>
                                        <?php foreach ($classOptions as $key => $meta): ?>
                                            <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $formState['class_key'] === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($meta['label']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Select Subject</label>
                                    <select name="subject_id" id="subject-select" required>
                                        <option value="">Choose subject</option>
                                        <?php if ($formState['class_key'] && isset($classOptions[$formState['class_key']])): ?>
                                            <?php foreach ($classOptions[$formState['class_key']]['subjects'] as $id => $label): ?>
                                                <option value="<?php echo (int)$id; ?>" <?php echo (string)$id === $formState['subject_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="form-group" style="grid-column:1 / -1;">
                                    <label style="display:flex; align-items:center; gap:8px;">
                                        Assign to Specific Students (optional)
                                        <button type="button" id="toggle-students" class="btn" style="padding:6px 10px; background:#6c757d; color:#fff; font-size:0.85rem;">Choose</button>
                                    </label>
                                    <div id="student-chooser" style="display:none; border:1px solid #e1e1e1; border-radius:8px; padding:0; background:#fafafa;">
                                        <div id="student-chooser-inner" style="max-height:260px; overflow-y:auto;"></div>
                                    </div>
                                    <p class="table-note">Leave empty to assign to every student in the class.</p>
                                </div>
                                <div class="form-group">
                                    <label>Assignment Name</label>
                                    <input type="text" name="title" value="<?php echo htmlspecialchars($formState['title']); ?>" placeholder="e.g., Priority Scheduling" required>
                                </div>
                                <div class="form-group">
                                    <label>Assignment Number</label>
                                    <input type="text" name="assignment_number" value="<?php echo htmlspecialchars($formState['assignment_number']); ?>" placeholder="e.g., Assignment 2" required>
                                </div>
                                <div class="form-group">
                                    <label>Assignment Type</label>
                                    <select name="assignment_type" required>
                                        <option value="">Choose type</option>
                                        <?php foreach ($allowedTypes as $type): ?>
                                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $formState['assignment_type'] === $type ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Start Date &amp; Time</label>
                                    <input type="datetime-local" name="start_at" value="<?php echo htmlspecialchars($formState['start_at']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Due Date &amp; Time</label>
                                    <input type="datetime-local" name="due_at" value="<?php echo htmlspecialchars($formState['due_at']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Total Marks (optional)</label>
                                    <input type="number" step="0.01" name="max_marks" value="<?php echo htmlspecialchars($formState['max_marks']); ?>" placeholder="e.g., 10">
                                </div>
                                <div class="form-group" style="grid-column:1 / -1;">
                                    <label>Attach Assignment File (optional)</label>
                                    <input type="file" name="instructions_file" accept="*/*">
                                    <p class="table-note">Upload guidelines, problem statements, or supplementary files (max 20MB).</p>
                                </div>
                                <div class="form-group" style="grid-column:1 / -1;">
                                    <label>Description &amp; Instructions</label>
                                    <textarea name="description" rows="4" placeholder="Outline expectations, submission format, evaluation criteria." required><?php echo htmlspecialchars($formState['description']); ?></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn" style="margin-top:16px;">Publish Assignment</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                        <h5 style="margin:0;">Assignments Overview</h5>
                        <span class="tag">Teacher: <?php echo htmlspecialchars($teacherNameDisplay !== '' ? $teacherNameDisplay : $teacherName); ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($assignments)): ?>
                            <div class="empty-state">No assignments yet. Publish one to get started.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="assignments-table">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>Assignment</th>
                                            <th>Class</th>
                                            <th>Start</th>
                                            <th>Due</th>
                                            <th>Marks</th>
                                            <th>Status</th>
                                            <th>Files</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assignments as $index => $assignment): ?>
                                            <?php
                                                $rowId = (int)$assignment['id'];
                                                $isActive = $selectedAssignmentId === $rowId;
                                                $classLabel = format_class_label(
                                                    $assignment['class_name'] ?? '',
                                                    $assignment['section_name'] ?? '',
                                                    $assignment['semester'] ?? '',
                                                    $assignment['school'] ?? ''
                                                );
                                                if ($classLabel === '') {
                                                    $classLabel = 'Unassigned';
                                                }
                                                $subjectLabel = $assignment['subject_name'] ?: $assignment['subject'];
                                                $dueDisplay = $assignment['due_at'] ? date('d M Y, h:i A', strtotime($assignment['due_at'])) : '—';
                                                $startDisplay = $assignment['start_at'] ? date('d M Y, h:i A', strtotime($assignment['start_at'])) : '—';
                                                $instructionsFile = $assignment['instructions_file'] ?: null;
                                            ?>
                                            <tr<?php echo $isActive ? ' style="background:#fff5f7;"' : ''; ?>>
                                                <td><?php echo $index + 1; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($assignment['title']); ?></strong><br>
                                                    <span class="text-muted"><?php echo htmlspecialchars($subjectLabel); ?> · <?php echo htmlspecialchars($assignment['assignment_type']); ?> · <?php echo htmlspecialchars($assignment['assignment_number']); ?></span>
                                                </td>
                                                <td><?php echo htmlspecialchars($classLabel); ?></td>
                                                <td><?php echo htmlspecialchars($startDisplay); ?></td>
                                                <td><?php echo htmlspecialchars($dueDisplay); ?></td>
                                                <td><?php echo $assignment['max_marks'] !== null ? htmlspecialchars(number_format((float)$assignment['max_marks'], 2)) : '—'; ?></td>
                                                <td>
                                                    <span class="badge-count success">Completed: <?php echo (int)$assignment['completed_count']; ?></span>
                                                    <span class="badge-count warning">Pending: <?php echo (int)$assignment['pending_count']; ?></span>
                                                    <span class="badge-count warning">Late: <?php echo (int)$assignment['late_count']; ?></span>
                                                    <span class="badge-count danger">Rejected: <?php echo (int)$assignment['rejected_count']; ?></span>
                                                    <div class="text-muted" style="margin-top:4px;">Total students: <?php echo (int)$assignment['total_students']; ?></div>
                                                </td>
                                                <td>
                                                    <div class="file-links">
                                                        <?php if ($instructionsFile): ?>
                                                            <a href="<?php echo htmlspecialchars($instructionsFile); ?>" target="_blank" class="link">Download brief</a>
                                                        <?php else: ?>
                                                            <span class="text-muted">No file</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="card-actions">
                                                        <a class="btn" style="background:#A6192E;" href="assignments.php?assignment_id=<?php echo $rowId; ?>">View</a>
                                                        <button type="button" class="btn" style="background:#6c757d;" data-edit-assignment="<?php echo $rowId; ?>">Edit</button>
                                                    </div>
                                                    <form method="POST" enctype="multipart/form-data" class="edit-assignment-form" data-assignment-form="<?php echo $rowId; ?>" style="display:none; margin-top:12px; padding:12px; border:1px solid #e1e1e1; border-radius:8px;">
                                                        <input type="hidden" name="action" value="update_assignment">
                                                        <input type="hidden" name="assignment_id" value="<?php echo $rowId; ?>">
                                                        <div class="form-group">
                                                            <label>Assignment Name</label>
                                                            <input type="text" name="title" value="<?php echo htmlspecialchars($assignment['title']); ?>" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Assignment Number</label>
                                                            <input type="text" name="assignment_number" value="<?php echo htmlspecialchars($assignment['assignment_number']); ?>" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Assignment Type</label>
                                                            <select name="assignment_type" required>
                                                                <?php foreach ($allowedTypes as $type): ?>
                                                                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $assignment['assignment_type'] === $type ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Start Date &amp; Time</label>
                                                            <input type="datetime-local" name="start_at" value="<?php echo htmlspecialchars(format_datetime_local($assignment['start_at'])); ?>" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Due Date &amp; Time</label>
                                                            <input type="datetime-local" name="due_at" value="<?php echo htmlspecialchars(format_datetime_local($assignment['due_at'])); ?>" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Total Marks (optional)</label>
                                                            <input type="number" step="0.01" name="max_marks" value="<?php echo $assignment['max_marks'] !== null ? htmlspecialchars((string)$assignment['max_marks']) : ''; ?>">
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Replace Assignment File</label>
                                                            <input type="file" name="instructions_file" accept="*/*">
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Description &amp; Instructions</label>
                                                            <textarea name="description" rows="3" required><?php echo htmlspecialchars($assignment['description'] ?? ''); ?></textarea>
                                                        </div>
                                                        <button type="submit" class="btn" style="background:#28a745;">Save Changes</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($selectedAssignmentMeta): ?>
                <div class="section-divider"></div>
                <div class="card" style="border-left:4px solid #1e7042;">
                    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                        <div>
                            <h5 style="margin:0;">Manage Submissions</h5>
                            <p class="table-note" style="margin:4px 0 0;">Review uploads, accept late submissions, or request resubmission.</p>
                        </div>
                        <span class="tag">Assignment: <?php echo htmlspecialchars($selectedAssignmentMeta['title']); ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($submissionDetails)): ?>
                            <div class="empty-state">No submissions tracked yet.</div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="assignments-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Status</th>
                                        <th>Last Submission</th>
                                        <th>Files</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($submissionDetails as $submission): ?>
                                        <?php
                                            $statusLabel = assignment_format_status($submission['assignment_status'] ?? 'pending');
                                            $statusSlug = strtolower(str_replace(' ', '_', $statusLabel));
                                            $submittedAt = $submission['last_submission_at'] ?: $submission['submission_date'];
                                            $submittedDisplay = $submittedAt ? date('d M Y, h:i A', strtotime((string)$submittedAt)) : '—';
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($submission['name']); ?></strong><br>
                                                <span class="text-muted">Roll: <?php echo htmlspecialchars($submission['roll_number'] ?? '—'); ?> · SAP: <?php echo htmlspecialchars($submission['sap_id'] ?? '—'); ?></span>
                                            </td>
                                            <td><span class="status-pill <?php echo htmlspecialchars($statusSlug); ?>"><?php echo htmlspecialchars($statusLabel); ?></span></td>
                                            <td><?php echo htmlspecialchars($submittedDisplay); ?></td>
                                            <td>
                                                <div class="file-links">
                                                    <?php if (!empty($submission['submitted_file_path'])): ?>
                                                        <a href="<?php echo htmlspecialchars($submission['submitted_file_path']); ?>" target="_blank">Student file</a>
                                                    <?php else: ?>
                                                        <span class="text-muted">No submission</span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($submission['feedback_file_path'])): ?>
                                                        <a href="<?php echo htmlspecialchars($submission['feedback_file_path']); ?>" target="_blank">Feedback file</a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <form method="POST" enctype="multipart/form-data" class="inline-form">
                                                    <input type="hidden" name="action" value="update_submission">
                                                    <input type="hidden" name="assignment_id" value="<?php echo (int)$submission['assignment_id']; ?>">
                                                    <input type="hidden" name="student_id" value="<?php echo (int)$submission['student_id']; ?>">
                                                    <div class="form-group">
                                                        <label>Status</label>
                                                        <select name="assignment_status">
                                                            <option value="pending" <?php echo $statusSlug === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                            <option value="completed" <?php echo $statusSlug === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                            <option value="late_submitted" <?php echo $statusSlug === 'late_submitted' ? 'selected' : ''; ?>>Late Submitted</option>
                                                            <option value="rejected" <?php echo $statusSlug === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Marks</label>
                                                        <input type="number" step="0.01" name="graded_marks" value="<?php echo $submission['graded_marks'] !== null ? htmlspecialchars((string)$submission['graded_marks']) : ''; ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Remarks</label>
                                                        <input type="text" name="teacher_feedback" value="<?php echo $submission['teacher_feedback'] ? htmlspecialchars($submission['teacher_feedback']) : ''; ?>" placeholder="Optional comment">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Feedback File</label>
                                                        <input type="file" name="feedback_file" accept="*/*">
                                                    </div>
                                                    <button type="submit" class="btn" style="background:#28a745;">Update</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        (function() {
            const classMap = <?php echo json_encode($classSubjectsMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
            const studentsMap = <?php echo json_encode($classStudentsMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
            const preselectedStudents = new Set(<?php echo json_encode($formState['student_ids'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?> || []);
            const classSelect = document.querySelector('select[name="class_key"]');
            const subjectSelect = document.getElementById('subject-select');
            const studentChooser = document.getElementById('student-chooser');
            const studentChooserInner = document.getElementById('student-chooser-inner');
            const toggleStudentsBtn = document.getElementById('toggle-students');

            function renderStudents(classKey) {
                if (!studentChooserInner) return;
                studentChooserInner.innerHTML = '';

                if (!(studentsMap && studentsMap[classKey] && studentsMap[classKey].length)) {
                    const note = document.createElement('p');
                    note.className = 'table-note';
                    note.style.margin = '12px';
                    note.textContent = 'Choose a class to load students.';
                    studentChooserInner.appendChild(note);
                    return;
                }

                const studentList = studentsMap[classKey];
                const selectedCount = studentList.filter(s => preselectedStudents.has(s.id)).length;

                const info = document.createElement('div');
                info.style.padding = '12px 16px';
                info.style.background = '#eef4ff';
                info.style.borderBottom = '1px solid #d8e2ff';
                info.style.fontSize = '0.95rem';
                info.innerHTML = 'You are assigning this work. Check the box for each student who should receive it. Currently, <strong>' + selectedCount + '</strong> student' + (selectedCount === 1 ? '' : 's') + ' selected.';
                studentChooserInner.appendChild(info);

                const tableWrap = document.createElement('div');
                tableWrap.style.maxHeight = '220px';
                tableWrap.style.overflowY = 'auto';

                const table = document.createElement('table');
                table.style.width = '100%';
                table.style.borderCollapse = 'collapse';
                table.style.fontSize = '0.95rem';

                const thead = document.createElement('thead');
                const headRow = document.createElement('tr');
                ['Roll Number', 'Student Name', 'Assign'].forEach(function(h, idx) {
                    const th = document.createElement('th');
                    th.textContent = h;
                    th.style.position = 'sticky';
                    th.style.top = '0';
                    th.style.background = '#a6192e';
                    th.style.color = '#fff';
                    th.style.padding = '10px';
                    th.style.textAlign = idx === 2 ? 'center' : 'left';
                    th.style.borderBottom = '1px solid #8d1527';
                    headRow.appendChild(th);
                });
                thead.appendChild(headRow);
                table.appendChild(thead);

                const tbody = document.createElement('tbody');

                studentList.forEach(function(s, index) {
                    const tr = document.createElement('tr');
                    tr.style.background = index % 2 === 0 ? '#fff' : '#f9fbff';

                    const tdRoll = document.createElement('td');
                    tdRoll.textContent = s.roll || '—';
                    tdRoll.style.padding = '10px 12px';
                    tdRoll.style.fontWeight = '600';
                    tdRoll.style.color = '#212529';

                    const tdName = document.createElement('td');
                    tdName.textContent = s.name || 'Student';
                    tdName.style.padding = '10px 12px';
                    tdName.style.color = '#212529';

                    const tdCheck = document.createElement('td');
                    tdCheck.style.padding = '10px 12px';
                    tdCheck.style.textAlign = 'center';

                    const label = document.createElement('label');
                    label.style.display = 'flex';
                    label.style.alignItems = 'center';
                    label.style.justifyContent = 'center';
                    label.style.gap = '6px';
                    label.style.cursor = 'pointer';

                    const cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.name = 'student_ids[]';
                    cb.value = s.id;
                    cb.checked = preselectedStudents.has(s.id);
                    cb.addEventListener('change', function() {
                        if (this.checked) {
                            preselectedStudents.add(s.id);
                        } else {
                            preselectedStudents.delete(s.id);
                        }
                        const newCount = Array.from(preselectedStudents).filter(id => studentList.some(st => st.id === id)).length;
                        info.innerHTML = 'You are assigning this work. Check the box for each student who should receive it. Currently, <strong>' + newCount + '</strong> student' + (newCount === 1 ? '' : 's') + ' selected.';
                    });

                    const span = document.createElement('span');
                    span.textContent = 'Assign';
                    span.style.fontSize = '0.9rem';

                    label.appendChild(cb);
                    label.appendChild(span);
                    tdCheck.appendChild(label);

                    tr.appendChild(tdRoll);
                    tr.appendChild(tdName);
                    tr.appendChild(tdCheck);
                    tbody.appendChild(tr);
                });

                table.appendChild(tbody);
                tableWrap.appendChild(table);
                studentChooserInner.appendChild(tableWrap);
            }

            function populateStudents(classKey) {
                renderStudents(classKey);
            }
            if (classSelect && subjectSelect) {
                classSelect.addEventListener('change', function() {
                    const selectedClass = this.value;
                    subjectSelect.innerHTML = '<option value="">Choose subject</option>';
                    if (classMap && classMap[selectedClass]) {
                        classMap[selectedClass].forEach(function(item) {
                            const option = document.createElement('option');
                            option.value = item.id;
                            option.textContent = item.name;
                            subjectSelect.appendChild(option);
                        });
                    }
                    populateStudents(selectedClass);
                });
            }
            if (classSelect && classSelect.value) {
                populateStudents(classSelect.value);
            }

            if (toggleStudentsBtn && studentChooser) {
                toggleStudentsBtn.addEventListener('click', function() {
                    const isHidden = studentChooser.style.display === 'none' || studentChooser.style.display === '';
                    studentChooser.style.display = isHidden ? 'block' : 'none';
                });
            }

            document.querySelectorAll('[data-edit-assignment]').forEach(function(button) {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-edit-assignment');
                    const form = document.querySelector('[data-assignment-form="' + id + '"]');
                    if (form) {
                        form.style.display = form.style.display === 'none' || form.style.display === '' ? 'block' : 'none';
                    }
                });
            });
        })();
    </script>
</body>
</html>
