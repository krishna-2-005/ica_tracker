<?php
if (!function_exists('get_numeric_setting')) {
    function get_numeric_setting(mysqli $conn, string $setting_key, ?int $user_id = null, ?float $fallback = null): ?float
    {
        $value = null;

        if ($user_id !== null && $user_id > 0) {
            $stmt = mysqli_prepare($conn, "SELECT setting_value FROM settings WHERE user_id = ? AND setting_key = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'is', $user_id, $setting_key);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if ($result && ($row = mysqli_fetch_assoc($result))) {
                    $value = (float)$row['setting_value'];
                }
                if ($result) {
                    mysqli_free_result($result);
                }
                mysqli_stmt_close($stmt);
            }
        }

        if ($value === null) {
            $stmt = mysqli_prepare($conn, "SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 's', $setting_key);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if ($result && ($row = mysqli_fetch_assoc($result))) {
                    $value = (float)$row['setting_value'];
                }
                if ($result) {
                    mysqli_free_result($result);
                }
                mysqli_stmt_close($stmt);
            }
        }

        if ($value === null) {
            return $fallback;
        }

        if ($value < 0) {
            $value = 0.0;
        } elseif ($value > 100) {
            $value = 100.0;
        }

        return $value;
    }
}

if (!function_exists('get_syllabus_threshold')) {
    function get_syllabus_threshold(mysqli $conn, ?int $program_chair_id = null): float
    {
        $default = 80.0;
        $value = get_numeric_setting($conn, 'syllabus_threshold', $program_chair_id, $default);
        if ($value === null) {
            return $default;
        }
        return $value;
    }
}

if (!function_exists('get_performance_threshold')) {
    function get_performance_threshold(mysqli $conn, ?int $program_chair_id = null): float
    {
        $default = 50.0;
        $value = get_numeric_setting($conn, 'performance_threshold', $program_chair_id, $default);
        if ($value === null) {
            return $default;
        }
        return $value;
    }
}
