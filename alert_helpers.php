<?php
if (!function_exists('send_scaled_marks_alert')) {
    function send_scaled_marks_alert(mysqli $conn, int $teacher_id, int $subject_id, float $total_scaled_marks): int
    {
        if ($total_scaled_marks <= 50) {
            return 0;
        }

        $teacher_stmt = mysqli_prepare($conn, "SELECT name, school FROM users WHERE id = ?");
        if (!$teacher_stmt) {
            return 0;
        }
        mysqli_stmt_bind_param($teacher_stmt, "i", $teacher_id);
        mysqli_stmt_execute($teacher_stmt);
        $teacher_res = mysqli_stmt_get_result($teacher_stmt);
        $teacher = $teacher_res ? mysqli_fetch_assoc($teacher_res) : null;
        if ($teacher_res) {
            mysqli_free_result($teacher_res);
        }
        mysqli_stmt_close($teacher_stmt);
        if (!$teacher) {
            return 0;
        }

        $teacher_name = trim((string)$teacher['name']);
        $teacher_school = trim((string)($teacher['school'] ?? ''));

        $subject_stmt = mysqli_prepare($conn, "SELECT subject_name FROM subjects WHERE id = ?");
        if (!$subject_stmt) {
            return 0;
        }
        mysqli_stmt_bind_param($subject_stmt, "i", $subject_id);
        mysqli_stmt_execute($subject_stmt);
        $subject_res = mysqli_stmt_get_result($subject_stmt);
        $subject = $subject_res ? mysqli_fetch_assoc($subject_res) : null;
        if ($subject_res) {
            mysqli_free_result($subject_res);
        }
        mysqli_stmt_close($subject_stmt);
        if (!$subject) {
            return 0;
        }

        $subject_name = trim((string)$subject['subject_name']);
        $program_chairs = [];

        if ($teacher_school !== '') {
            $chair_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE role = 'program_chair' AND school = ?");
            if ($chair_stmt) {
                mysqli_stmt_bind_param($chair_stmt, "s", $teacher_school);
                mysqli_stmt_execute($chair_stmt);
                $chair_res = mysqli_stmt_get_result($chair_stmt);
                while ($chair_res && ($row = mysqli_fetch_assoc($chair_res))) {
                    $program_chairs[] = (int)$row['id'];
                }
                if ($chair_res) {
                    mysqli_free_result($chair_res);
                }
                mysqli_stmt_close($chair_stmt);
            }
        }

        if (empty($program_chairs)) {
            $chair_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE role = 'program_chair'");
            if ($chair_stmt) {
                mysqli_stmt_execute($chair_stmt);
                $chair_res = mysqli_stmt_get_result($chair_stmt);
                while ($chair_res && ($row = mysqli_fetch_assoc($chair_res))) {
                    $program_chairs[] = (int)$row['id'];
                }
                if ($chair_res) {
                    mysqli_free_result($chair_res);
                }
                mysqli_stmt_close($chair_stmt);
            }
        }

        if (empty($program_chairs)) {
            return 0;
        }

        $message = sprintf(
            "Alert: %s attempted to allocate %.2f scaled ICA marks for %s, exceeding the 50 mark limit.",
            $teacher_name !== '' ? $teacher_name : 'A teacher',
            $total_scaled_marks,
            $subject_name !== '' ? $subject_name : 'the selected subject'
        );

        $insert_stmt = mysqli_prepare($conn, "INSERT INTO alerts (teacher_id, message, status, created_at) VALUES (?, ?, 'pending', NOW())");
        $check_stmt = mysqli_prepare($conn, "SELECT id FROM alerts WHERE teacher_id = ? AND message = ? AND created_at >= (NOW() - INTERVAL 1 DAY) LIMIT 1");

        if (!$insert_stmt || !$check_stmt) {
            if ($insert_stmt) {
                mysqli_stmt_close($insert_stmt);
            }
            if ($check_stmt) {
                mysqli_stmt_close($check_stmt);
            }
            return 0;
        }

        $inserted = 0;
        foreach ($program_chairs as $chair_id) {
            mysqli_stmt_bind_param($check_stmt, "is", $chair_id, $message);
            mysqli_stmt_execute($check_stmt);
            $check_res = mysqli_stmt_get_result($check_stmt);
            $exists = $check_res ? mysqli_fetch_assoc($check_res) : null;
            if ($check_res) {
                mysqli_free_result($check_res);
            }
            if ($exists) {
                continue;
            }

            mysqli_stmt_bind_param($insert_stmt, "is", $chair_id, $message);
            if (mysqli_stmt_execute($insert_stmt)) {
                $inserted++;
            }
        }

        mysqli_stmt_close($insert_stmt);
        mysqli_stmt_close($check_stmt);

        return $inserted;
    }
}

