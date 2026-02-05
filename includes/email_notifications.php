<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as MailException;

if (!defined('APP_BOOTSTRAPPED')) {
    require_once __DIR__ . '/init.php';
}

require_once __DIR__ . '/mailer.php';

const EMAIL_SCENARIO_ASSIGNMENT_CREATED = 'assignment_created';
const EMAIL_SCENARIO_PROGRAM_ALERT = 'program_alert';
const EMAIL_SCENARIO_ICA_MARKS_PUBLISHED = 'ica_marks_published';
const EMAIL_SCENARIO_SUBJECT_ASSIGNMENT = 'subject_assignment';
const EMAIL_SCENARIO_TIMETABLE_PUBLISHED = 'timetable_published';
const EMAIL_SCENARIO_PASSWORD_RESET = 'password_reset';

if (!function_exists('email_notification_app_url')) {
    function email_notification_app_url(array $overrides = []): string
    {
        $url = trim((string)($overrides['app_url'] ?? config('app.url', 'http://localhost/ica_tracker')));
        if ($url === '') {
            $url = 'http://localhost/ica_tracker';
        }
        return rtrim($url, '/');
    }
}

if (!function_exists('email_notification_logo_url')) {
    function email_notification_logo_url(array $overrides = []): string
    {
        $logo = trim((string)($overrides['nmims_logo_url'] ?? ''));
        if ($logo !== '') {
            return $logo;
        }
        $appUrl = email_notification_app_url($overrides);
        return $appUrl . '/nmimsvertical.jpg';
    }
}

if (!function_exists('email_notification_template')) {
    function email_notification_template(): string
    {
        static $template = null;
        if ($template !== null) {
            return $template;
        }
        $path = base_path('emailtemplate.html');
        if (!is_file($path)) {
            throw new RuntimeException('Email template not found at ' . $path);
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('Unable to read email template at ' . $path);
        }
        $template = $contents;
        return $template;
    }
}

if (!function_exists('email_notification_escape_html')) {
    function email_notification_escape_html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('email_notification_prepare_value')) {
    function email_notification_prepare_value($value): array
    {
        if ($value instanceof DateTimeInterface) {
            $formatted = $value->format('d M Y, h:i A');
            return [
                'html' => email_notification_escape_html($formatted),
                'text' => $formatted,
            ];
        }

        $string = trim((string)$value);
        if ($string === '') {
            return [
                'html' => '',
                'text' => '',
            ];
        }

        return [
            'html' => nl2br(email_notification_escape_html($string), false),
            'text' => $string,
        ];
    }
}

if (!function_exists('email_notification_format_detail_block')) {
    function email_notification_format_detail_block(array $rows): array
    {
        $htmlParts = [];
        $textParts = [];
        foreach ($rows as $row) {
            if (!is_array($row) || count($row) < 2) {
                continue;
            }
            [$label, $value] = $row;
            $prepared = email_notification_prepare_value($value);
            if ($prepared['html'] === '' && $prepared['text'] === '') {
                continue;
            }
            $labelText = email_notification_escape_html((string)$label);
            $htmlParts[] = '<p style="margin:0 0 6px; font-size:14px;"><strong>' . $labelText . ':</strong> ' . $prepared['html'] . '</p>';
            $textParts[] = $label . ': ' . $prepared['text'];
        }

        return [
            'html' => implode('', $htmlParts),
            'text' => implode(PHP_EOL, $textParts),
        ];
    }
}

if (!function_exists('email_notification_render_template')) {
    function email_notification_render_template(array $data): string
    {
        $template = email_notification_template();

        $defaults = [
            'nmims_logo_url' => email_notification_logo_url($data),
            'email_title' => '',
            'recipient_name' => '',
            'email_message' => '',
            'email_details' => '',
            'action_link' => email_notification_app_url($data),
            'button_text' => 'Open ICA Tracker',
            'college_name' => config('app.name', 'ICA Tracker'),
            'current_year' => date('Y'),
        ];

        $payload = array_merge($defaults, $data);

        $search = [];
        $replace = [];
        foreach ($payload as $key => $value) {
            $search[] = '{{' . $key . '}}';
            $replace[] = (string)$value;
        }

        return str_replace($search, $replace, $template);
    }
}

if (!function_exists('email_notification_build_payload')) {
    function email_notification_build_payload(string $scenario, array $data): array
    {
        $recipientName = trim((string)($data['recipient_name'] ?? 'ICA Tracker Member'));
        $recipientName = $recipientName !== '' ? $recipientName : 'ICA Tracker Member';
        $collegeName = trim((string)($data['college_name'] ?? config('app.name', 'ICA Tracker')));
        $appUrl = email_notification_app_url($data);

        $result = [
            'subject' => '',
            'title' => '',
            'message_html' => '',
            'message_text' => '',
            'details_html' => '',
            'details_text' => '',
            'action_link' => $appUrl,
            'button_text' => 'Open ICA Tracker',
            'recipient_name' => $recipientName,
            'college_name' => $collegeName,
        ];

        switch ($scenario) {
            case EMAIL_SCENARIO_ASSIGNMENT_CREATED:
                $subjectName = trim((string)($data['subject_name'] ?? 'Subject'));
                $assignmentTitle = trim((string)($data['assignment_title'] ?? 'Assignment'));
                $facultyName = trim((string)($data['faculty_name'] ?? 'Faculty'));
                $startAt = $data['start_at'] ?? '';
                $dueAt = $data['due_at'] ?? '';
                $details = email_notification_format_detail_block([
                    ['Subject', $subjectName],
                    ['Assignment Title', $assignmentTitle],
                    ['Assigned By', $facultyName],
                    ['Start Date', $startAt],
                    ['Due Date', $dueAt],
                    ['Description', $data['assignment_description'] ?? ''],
                ]);

                $result['subject'] = 'New Assignment: ' . $assignmentTitle . ' (' . $subjectName . ')';
                $result['title'] = 'New Assignment Published';
                $result['message_html'] = 'A new assignment has been published for <strong>' . email_notification_escape_html($subjectName) . '</strong>. Please review the details below.';
                $result['message_text'] = 'A new assignment has been published for ' . $subjectName . '. Please review the details below.';
                $result['details_html'] = $details['html'];
                $result['details_text'] = $details['text'];
                $result['action_link'] = trim((string)($data['assignment_url'] ?? ($appUrl . '/student_dashboard.php')));
                $result['button_text'] = 'View Assignment';
                break;

            case EMAIL_SCENARIO_PROGRAM_ALERT:
                $alertTitle = trim((string)($data['alert_title'] ?? 'ICA Tracker Alert'));
                $senderName = trim((string)($data['sender_name'] ?? 'Program Chair'));
                $senderType = trim((string)($data['sender_type'] ?? 'Program Chair'));
                $details = email_notification_format_detail_block([
                    ['Alert Title', $alertTitle],
                    ['Alert Message', $data['alert_message'] ?? ''],
                    ['Sender', $senderName !== '' ? $senderName : $senderType],
                ]);

                $result['subject'] = 'Alert: ' . $alertTitle;
                $result['title'] = 'New Program Alert';
                $result['message_html'] = 'You have received a new alert from <strong>' . email_notification_escape_html($senderName !== '' ? $senderName : $senderType) . '</strong>.';
                $result['message_text'] = 'You have received a new alert from ' . ($senderName !== '' ? $senderName : $senderType) . '.';
                $result['details_html'] = $details['html'];
                $result['details_text'] = $details['text'];
                $result['action_link'] = trim((string)($data['alerts_url'] ?? ($appUrl . '/view_alerts.php')));
                $result['button_text'] = 'View Alert';
                break;

            case EMAIL_SCENARIO_ICA_MARKS_PUBLISHED:
                $subjectName = trim((string)($data['subject_name'] ?? 'Subject'));
                $componentName = trim((string)($data['component_name'] ?? 'ICA Component'));
                $marksObtained = trim((string)($data['marks_obtained'] ?? ''));
                $maxMarks = trim((string)($data['max_marks'] ?? ''));
                $marksDisplay = $marksObtained;
                if ($marksDisplay !== '' && $maxMarks !== '') {
                    $marksDisplay = $marksDisplay . ' / ' . $maxMarks;
                }
                $details = email_notification_format_detail_block([
                    ['Subject', $subjectName],
                    ['ICA Component', $componentName],
                    ['Marks Obtained', $marksDisplay !== '' ? $marksDisplay : $marksObtained],
                    ['Evaluated By', $data['faculty_name'] ?? ''],
                    ['Feedback', $data['feedback'] ?? ''],
                ]);

                $result['subject'] = 'Marks Published: ' . $componentName . ' - ' . $subjectName;
                $result['title'] = 'ICA Marks Published';
                $result['message_html'] = 'Your marks for <strong>' . email_notification_escape_html($componentName) . '</strong> in <strong>' . email_notification_escape_html($subjectName) . '</strong> are now available.';
                $result['message_text'] = 'Your marks for ' . $componentName . ' in ' . $subjectName . ' are now available.';
                $result['details_html'] = $details['html'];
                $result['details_text'] = $details['text'];
                $result['action_link'] = trim((string)($data['marks_url'] ?? ($appUrl . '/view_marks.php')));
                $result['button_text'] = 'View Marks';
                break;

            case EMAIL_SCENARIO_SUBJECT_ASSIGNMENT:
                $subjectName = trim((string)($data['subject_name'] ?? 'Subject'));
                $role = trim((string)($data['assigned_role'] ?? 'Faculty'));
                $details = email_notification_format_detail_block([
                    ['Subject', $subjectName],
                    ['Academic Year', $data['academic_year'] ?? ''],
                    ['Semester', $data['semester'] ?? ''],
                    ['Class / Section', $data['class_section'] ?? ''],
                    ['Total Students', $data['student_count'] ?? ''],
                    ['Assigned Role', $role],
                ]);

                $result['subject'] = 'Subject Assigned: ' . $subjectName;
                $result['title'] = 'Teaching Assignment Updated';
                $result['message_html'] = 'You have been assigned to <strong>' . email_notification_escape_html($subjectName) . '</strong> as ' . email_notification_escape_html(strtolower($role)) . '.';
                $result['message_text'] = 'You have been assigned to ' . $subjectName . ' as ' . strtolower($role) . '.';
                $result['details_html'] = $details['html'];
                $result['details_text'] = $details['text'];
                $result['action_link'] = trim((string)($data['subjects_url'] ?? ($appUrl . '/teacher_dashboard.php')));
                $result['button_text'] = 'View Assignment';
                break;

            case EMAIL_SCENARIO_TIMETABLE_PUBLISHED:
                $classSection = trim((string)($data['class_section'] ?? 'Class'));
                $semester = trim((string)($data['semester'] ?? 'Semester'));
                $timelineLabel = trim((string)($data['timetable_label'] ?? ''));
                $studentCount = $data['student_count'] ?? '';
                $details = email_notification_format_detail_block([
                    ['Academic Year', $data['academic_year'] ?? ''],
                    ['Semester', $semester],
                    ['Class / Section', $classSection],
                    ['Timeline', $timelineLabel],
                    ['Total Students', $studentCount],
                ]);

                $subjectParts = [];
                if ($classSection !== '') {
                    $subjectParts[] = $classSection;
                }
                if ($semester !== '') {
                    $subjectParts[] = '(' . $semester . ')';
                }
                if ($timelineLabel !== '') {
                    $subjectParts[] = '- ' . $timelineLabel;
                }
                $subjectText = trim(implode(' ', array_filter($subjectParts)));
                $result['subject'] = $subjectText !== '' ? 'Timetable Published: ' . $subjectText : 'Timetable Published';
                $contextLabelText = trim($classSection . ' ' . ($semester !== '' ? '(' . $semester . ')' : ''));
                if ($contextLabelText === '') {
                    $contextLabelText = 'your class';
                }
                $result['title'] = 'New Timetable Available';
                $result['message_html'] = 'The timetable for <strong>' . email_notification_escape_html($contextLabelText) . '</strong> has been published.';
                $result['message_text'] = 'The timetable for ' . $contextLabelText . ' has been published.';
                $result['details_html'] = $details['html'];
                $result['details_text'] = $details['text'];
                $result['action_link'] = trim((string)($data['timetable_url'] ?? ($appUrl . '/view_timetable.php')));
                $result['button_text'] = 'View Timetable';
                break;

            case EMAIL_SCENARIO_PASSWORD_RESET:
                $resetLink = trim((string)($data['reset_link'] ?? ''));
                $expiryText = trim((string)($data['link_expires_at'] ?? '1 hour'));
                if ($resetLink === '') {
                    throw new InvalidArgumentException('Reset link is required for password reset emails.');
                }
                $result['subject'] = 'Password Reset Instructions';
                $result['title'] = 'Reset Your Password';
                $result['message_html'] = 'We received a request to reset your ICA Tracker password. Use the secure link below to set a new password.';
                $result['message_text'] = 'We received a request to reset your ICA Tracker password. Use the secure link below to set a new password.';
                $resetLinkHtml = '<a href="' . email_notification_escape_html($resetLink) . '">' . email_notification_escape_html($resetLink) . '</a>';
                $expiryHtml = $expiryText !== '' ? '<p style="margin:0 0 6px; font-size:14px;"><strong>Expires In:</strong> ' . email_notification_escape_html($expiryText) . '</p>' : '';
                $result['details_html'] = '<p style="margin:0 0 6px; font-size:14px;"><strong>Reset Link:</strong> ' . $resetLinkHtml . '</p>' . $expiryHtml;
                $result['details_text'] = 'Reset Link: ' . $resetLink . ($expiryText !== '' ? PHP_EOL . 'Expires In: ' . $expiryText : '');
                $result['action_link'] = $resetLink;
                $result['button_text'] = 'Reset Password';
                break;

            default:
                throw new InvalidArgumentException('Unsupported email scenario: ' . $scenario);
        }

        return $result;
    }
}

if (!function_exists('email_notification_generate_bodies')) {
    function email_notification_generate_bodies(array $payload): array
    {
        $htmlMessage = $payload['message_html'] ?? '';
        $textMessage = $payload['message_text'] ?? strip_tags((string)($payload['message_html'] ?? ''));

        $templateData = [
            'email_title' => $payload['title'] ?? '',
            'recipient_name' => $payload['recipient_name'] ?? '',
            'email_message' => $htmlMessage,
            'email_details' => $payload['details_html'] ?? '',
            'action_link' => $payload['action_link'] ?? email_notification_app_url(),
            'button_text' => $payload['button_text'] ?? 'Open ICA Tracker',
            'college_name' => $payload['college_name'] ?? config('app.name', 'ICA Tracker'),
            'nmims_logo_url' => $payload['nmims_logo_url'] ?? email_notification_logo_url($payload),
            'current_year' => date('Y'),
        ];

        $htmlBody = email_notification_render_template($templateData);

        $textLines = [];
        $title = trim((string)($payload['title'] ?? ''));
        if ($title !== '') {
            $textLines[] = $title;
            $textLines[] = '';
        }
        $textLines[] = 'Dear ' . ($payload['recipient_name'] ?? 'Member') . ',';
        $textLines[] = '';
        $textLines[] = $textMessage;
        if (!empty($payload['details_text'])) {
            $textLines[] = '';
            $textLines[] = $payload['details_text'];
        }
        $textLines[] = '';
        $textLines[] = 'Regards,';
        $textLines[] = 'ICA Tracker Team';
        $textLines[] = $payload['college_name'] ?? config('app.name', 'ICA Tracker');

        $textBody = implode(PHP_EOL, array_filter($textLines, static function ($line) {
            return $line !== null;
        }));

        return [$htmlBody, $textBody];
    }
}

if (!function_exists('send_notification_email')) {
    function send_notification_email($recipients, string $scenario, array $data): bool
    {
        $recipientsList = [];
        if (is_string($recipients)) {
            $recipientsList = [trim($recipients)];
        } elseif (is_array($recipients)) {
            foreach ($recipients as $email) {
                $trimmed = trim((string)$email);
                if ($trimmed !== '') {
                    $recipientsList[] = $trimmed;
                }
            }
        }

        $recipientsList = array_values(array_unique(array_filter($recipientsList)));
        if (empty($recipientsList)) {
            return false;
        }

        try {
            $payload = email_notification_build_payload($scenario, $data);
            [$htmlBody, $textBody] = email_notification_generate_bodies($payload);
            $subject = $payload['subject'] ?? 'ICA Tracker Notification';

            return send_app_mail($recipientsList, $subject, $htmlBody, $textBody);
        } catch (MailException $exception) {
            app_log('PHPMailer exception while sending notification.', [
                'scenario' => $scenario,
                'error' => $exception->getMessage(),
            ]);
        } catch (Throwable $exception) {
            app_log('Failed to send notification email.', [
                'scenario' => $scenario,
                'error' => $exception->getMessage(),
            ]);
        }

        return false;
    }
}

if (!function_exists('email_notification_fetch_students')) {
    function email_notification_fetch_students(mysqli $conn, array $studentIds): array
    {
        $ids = [];
        foreach ($studentIds as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $ids[$id] = true;
            }
        }
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = mysqli_prepare($conn, "SELECT id, name, college_email, sap_id, roll_number, class_id, section_id FROM students WHERE id IN ($placeholders)");
        if (!$stmt) {
            return [];
        }

        $types = str_repeat('i', count($ids));
        mysqli_stmt_bind_param($stmt, $types, ...array_keys($ids));
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $students = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $studentId = (int)$row['id'];
                $students[$studentId] = [
                    'id' => $studentId,
                    'name' => trim((string)($row['name'] ?? '')),
                    'email' => trim((string)($row['college_email'] ?? '')),
                    'sap_id' => trim((string)($row['sap_id'] ?? '')),
                    'roll_number' => trim((string)($row['roll_number'] ?? '')),
                    'class_id' => isset($row['class_id']) ? (int)$row['class_id'] : null,
                    'section_id' => isset($row['section_id']) ? (int)$row['section_id'] : null,
                ];
            }
            mysqli_free_result($result);
        }
        mysqli_stmt_close($stmt);
        return $students;
    }
}

if (!function_exists('email_notification_fetch_users')) {
    function email_notification_fetch_users(mysqli $conn, array $userIds): array
    {
        $ids = [];
        foreach ($userIds as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $ids[$id] = true;
            }
        }
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = mysqli_prepare($conn, "SELECT id, name, email, role, school FROM users WHERE id IN ($placeholders)");
        if (!$stmt) {
            return [];
        }

        $types = str_repeat('i', count($ids));
        mysqli_stmt_bind_param($stmt, $types, ...array_keys($ids));
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $users = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $userId = (int)$row['id'];
                $users[$userId] = [
                    'id' => $userId,
                    'name' => trim((string)($row['name'] ?? '')),
                    'email' => trim((string)($row['email'] ?? '')),
                    'role' => trim((string)($row['role'] ?? '')),
                    'school' => trim((string)($row['school'] ?? '')),
                ];
            }
            mysqli_free_result($result);
        }
        mysqli_stmt_close($stmt);
        return $users;
    }
}

if (!function_exists('email_notification_format_class_section')) {
    function email_notification_format_class_section(mysqli $conn, ?int $classId, ?int $sectionId): string
    {
        if ($classId === null || $classId <= 0) {
            return '';
        }
        $classStmt = mysqli_prepare($conn, 'SELECT class_name, semester, school FROM classes WHERE id = ? LIMIT 1');
        if (!$classStmt) {
            return '';
        }
        mysqli_stmt_bind_param($classStmt, 'i', $classId);
        mysqli_stmt_execute($classStmt);
        $classRes = mysqli_stmt_get_result($classStmt);
        $className = '';
        $semester = '';
        $school = '';
        if ($classRes && ($row = mysqli_fetch_assoc($classRes))) {
            $className = trim((string)($row['class_name'] ?? ''));
            $semester = trim((string)($row['semester'] ?? ''));
            $school = trim((string)($row['school'] ?? ''));
        }
        if ($classRes) {
            mysqli_free_result($classRes);
        }
        mysqli_stmt_close($classStmt);

        $sectionName = '';
        if ($sectionId !== null && $sectionId > 0) {
            $sectionStmt = mysqli_prepare($conn, 'SELECT section_name FROM sections WHERE id = ? LIMIT 1');
            if ($sectionStmt) {
                mysqli_stmt_bind_param($sectionStmt, 'i', $sectionId);
                mysqli_stmt_execute($sectionStmt);
                $sectionRes = mysqli_stmt_get_result($sectionStmt);
                if ($sectionRes && ($row = mysqli_fetch_assoc($sectionRes))) {
                    $sectionName = trim((string)($row['section_name'] ?? ''));
                }
                if ($sectionRes) {
                    mysqli_free_result($sectionRes);
                }
                mysqli_stmt_close($sectionStmt);
            }
        }

        $parts = [];
        if ($className !== '') {
            $parts[] = $className;
        }
        if ($sectionName !== '') {
            $parts[] = 'Section ' . $sectionName;
        }
        if (empty($parts) && $semester !== '') {
            $parts[] = 'Semester ' . $semester;
        }
        if ($school !== '') {
            $parts[] = $school;
        }

        return implode(' - ', $parts);
    }
}

if (!function_exists('email_notification_fetch_subject_names')) {
    function email_notification_fetch_subject_names(mysqli $conn, array $subjectIds): array
    {
        $ids = [];
        foreach ($subjectIds as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $ids[$id] = true;
            }
        }
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = mysqli_prepare($conn, "SELECT id, subject_name FROM subjects WHERE id IN ($placeholders)");
        if (!$stmt) {
            return [];
        }

        $types = str_repeat('i', count($ids));
        mysqli_stmt_bind_param($stmt, $types, ...array_keys($ids));
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $names = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $subjectId = (int)$row['id'];
                $names[$subjectId] = trim((string)($row['subject_name'] ?? ''));
            }
            mysqli_free_result($result);
        }
        mysqli_stmt_close($stmt);
        return $names;
    }
}
