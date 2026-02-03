<?php
if (!function_exists('assignment_column_exists')) {
    function assignment_column_exists(mysqli $conn, string $table, string $column): bool {
        $tableSafe = preg_replace('/[^A-Za-z0-9_]/', '', $table);
        $columnSafe = preg_replace('/[^A-Za-z0-9_]/', '', $column);
        if ($tableSafe === '' || $columnSafe === '') {
            return false;
        }
        $sql = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . mysqli_real_escape_string($conn, $tableSafe) . "' AND COLUMN_NAME = '" . mysqli_real_escape_string($conn, $columnSafe) . "'";
        $result = mysqli_query($conn, $sql);
        if (!$result) {
            return false;
        }
        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        return isset($row['cnt']) && (int)$row['cnt'] > 0;
    }
}

if (!function_exists('assignment_try_add_column')) {
    function assignment_try_add_column(mysqli $conn, string $table, string $column, string $definition): void {
        if (assignment_column_exists($conn, $table, $column)) {
            return;
        }
        $tableSafe = preg_replace('/[^A-Za-z0-9_]/', '', $table);
        $columnSafe = preg_replace('/[^A-Za-z0-9_]/', '', $column);
        if ($tableSafe === '' || $columnSafe === '') {
            return;
        }
        $sql = "ALTER TABLE `" . $tableSafe . "` ADD COLUMN `" . $columnSafe . "` " . $definition;
        @mysqli_query($conn, $sql);
    }
}

if (!function_exists('ensure_assignments_schema')) {
    function ensure_assignments_schema(mysqli $conn): void {
        assignment_try_add_column($conn, 'assignments', 'class_id', 'INT(11) DEFAULT NULL AFTER teacher_id');
        assignment_try_add_column($conn, 'assignments', 'section_id', 'INT(11) DEFAULT NULL AFTER class_id');
        assignment_try_add_column($conn, 'assignments', 'subject_id', 'INT(11) DEFAULT NULL AFTER section_id');
        assignment_try_add_column($conn, 'assignments', 'assignment_type', "VARCHAR(50) DEFAULT NULL AFTER subject");
        assignment_try_add_column($conn, 'assignments', 'assignment_number', "VARCHAR(50) DEFAULT NULL AFTER assignment_type");
        assignment_try_add_column($conn, 'assignments', 'start_at', 'DATETIME DEFAULT NULL AFTER description');
        assignment_try_add_column($conn, 'assignments', 'due_at', 'DATETIME DEFAULT NULL AFTER start_at');
        assignment_try_add_column($conn, 'assignments', 'max_marks', 'DECIMAL(6,2) DEFAULT NULL AFTER due_at');
        assignment_try_add_column($conn, 'assignments', 'instructions_file', "VARCHAR(255) DEFAULT NULL AFTER max_marks");
        assignment_try_add_column($conn, 'assignments', 'updated_at', 'TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at');

        // Ensure due_at populated when empty
        if (assignment_column_exists($conn, 'assignments', 'due_at')) {
            $populateSql = "UPDATE assignments SET due_at = CONCAT(deadline, ' 23:59:00') WHERE due_at IS NULL AND deadline IS NOT NULL";
            @mysqli_query($conn, $populateSql);
        }
    }
}

if (!function_exists('ensure_student_assignments_schema')) {
    function ensure_student_assignments_schema(mysqli $conn): void {
        assignment_try_add_column($conn, 'student_assignments', 'assignment_status', "VARCHAR(30) DEFAULT 'pending' AFTER submission_status");
        assignment_try_add_column($conn, 'student_assignments', 'submission_state', "VARCHAR(30) DEFAULT 'pending' AFTER assignment_status");
        assignment_try_add_column($conn, 'student_assignments', 'submitted_file_path', "VARCHAR(255) DEFAULT NULL AFTER submission_date");
        assignment_try_add_column($conn, 'student_assignments', 'last_submission_at', 'DATETIME DEFAULT NULL AFTER submitted_file_path');
        assignment_try_add_column($conn, 'student_assignments', 'graded_marks', 'DECIMAL(6,2) DEFAULT NULL AFTER marks_obtained');
        assignment_try_add_column($conn, 'student_assignments', 'graded_at', 'DATETIME DEFAULT NULL AFTER graded_marks');
        assignment_try_add_column($conn, 'student_assignments', 'teacher_feedback', 'TEXT DEFAULT NULL AFTER graded_at');
        assignment_try_add_column($conn, 'student_assignments', 'feedback_file_path', "VARCHAR(255) DEFAULT NULL AFTER teacher_feedback");
        assignment_try_add_column($conn, 'student_assignments', 'reviewed_at', 'DATETIME DEFAULT NULL AFTER feedback_file_path');
        assignment_try_add_column($conn, 'student_assignments', 'reviewed_by', 'INT(11) DEFAULT NULL AFTER reviewed_at');
    }
}

if (!function_exists('ensure_assignment_schema')) {
    function ensure_assignment_schema(mysqli $conn): void {
        ensure_assignments_schema($conn);
        ensure_student_assignments_schema($conn);
    }
}

if (!function_exists('ensure_assignment_storage')) {
    function ensure_assignment_storage(): array {
        $baseDir = __DIR__ . '/../uploads/assignments';
        $teacherDir = $baseDir . '/teacher_files';
        $studentDir = $baseDir . '/student_submissions';
        $feedbackDir = $baseDir . '/feedback';
        $dirs = [$baseDir, $teacherDir, $studentDir, $feedbackDir];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }
        return [
            'teacher' => realpath($teacherDir) ?: $teacherDir,
            'student' => realpath($studentDir) ?: $studentDir,
            'feedback' => realpath($feedbackDir) ?: $feedbackDir
        ];
    }
}

if (!function_exists('assignment_safe_filename')) {
    function assignment_safe_filename(string $original): string {
        $basename = pathinfo($original, PATHINFO_FILENAME);
        $extension = pathinfo($original, PATHINFO_EXTENSION);
        $slug = preg_replace('/[^A-Za-z0-9-_]/', '_', $basename);
        $slug = trim($slug, '_');
        if ($slug === '') {
            $slug = 'file';
        }
        $timestamp = date('Ymd_His');
        try {
            $random = bin2hex(random_bytes(4));
        } catch (Exception $ex) {
            $random = bin2hex(pack('N', mt_rand()));
        }
        $ext = $extension !== '' ? '.' . strtolower($extension) : '';
        return $slug . '_' . $timestamp . '_' . $random . $ext;
    }
}

if (!function_exists('assignment_format_status')) {
    function assignment_format_status(string $raw): string {
        $status = strtolower(trim($raw));
        if ($status === '') {
            return 'Pending';
        }
        $status = str_replace('_', ' ', $status);
        return ucwords($status);
    }
}

if (!function_exists('assignment_normalize_status')) {
    function assignment_normalize_status(string $raw): string {
        $status = strtolower(trim($raw));
        if ($status === '') {
            return 'pending';
        }
        $status = str_replace(' ', '_', $status);
        $allowed = ['pending', 'submitted', 'late_submitted', 'completed', 'rejected'];
        if (!in_array($status, $allowed, true)) {
            return 'pending';
        }
        return $status;
    }
}
?>
