<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/activity_logger.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'system_admin') {
    header('Location: admin_login.php');
    exit;
}

$systemAdminId = (int)$_SESSION['user_id'];
$systemAdminNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$systemAdminNameDisplay = $systemAdminNameRaw !== '' ? format_person_display($systemAdminNameRaw) : 'SYSTEM ADMIN';

$todayStart = date('Y-m-d 00:00:00');
$todayEnd = date('Y-m-d 23:59:59');
$weekStart = date('Y-m-d 00:00:00', strtotime('monday this week'));
$weekEnd = date('Y-m-d H:i:s');
$todayLabel = date('d M Y') . ', 12:00 AM - 11:59 PM';
$weekLabel = date('d M Y', strtotime($weekStart)) . ' - ' . date('d M Y');

$unlockableActions = ['marks_csv_bulk_upload', 'marks_manual_update'];
$sensitiveActions = [
    'marks_csv_bulk_upload',
    'marks_manual_update',
    'db_snapshot_downloaded',
    'assignment_created',
    'assignment_deleted',
    'assignment_updated',
    'subject_created',
    'subject_deleted',
    'password_reset_requested',
    'admin_login_failed',
    'login_failed',
];

if (!isset($_SESSION['system_admin_unlocked_activity_details']) || !is_array($_SESSION['system_admin_unlocked_activity_details'])) {
    $_SESSION['system_admin_unlocked_activity_details'] = [];
}
foreach ($_SESSION['system_admin_unlocked_activity_details'] as $entryId => $expiryTs) {
    if ((int)$expiryTs < time()) {
        unset($_SESSION['system_admin_unlocked_activity_details'][$entryId]);
    }
}

$scope = $_GET['scope'] ?? 'today';
if (!in_array($scope, ['today', 'week', 'all'], true)) {
    $scope = 'today';
}

$startDate = trim((string)($_GET['start_date'] ?? ''));
$endDate = trim((string)($_GET['end_date'] ?? ''));
$actionFilter = trim((string)($_GET['activity_action'] ?? ''));
$activityPreset = trim((string)($_GET['activity_preset'] ?? ''));

$allowedPresets = ['active_logins', 'failed_logins', 'sensitive_actions'];
if ($activityPreset !== '' && !in_array($activityPreset, $allowedPresets, true)) {
    $activityPreset = '';
}

$datePattern = '/^\d{4}-\d{2}-\d{2}$/';
if ($startDate !== '' && !preg_match($datePattern, $startDate)) {
    $startDate = '';
}
if ($endDate !== '' && !preg_match($datePattern, $endDate)) {
    $endDate = '';
}
if ($startDate !== '' && $endDate !== '' && strtotime($startDate) > strtotime($endDate)) {
    [$startDate, $endDate] = [$endDate, $startDate];
}

if (!function_exists('build_system_admin_dashboard_url')) {
    function build_system_admin_dashboard_url(array $params = []): string
    {
        $clean = [];
        foreach ($params as $key => $value) {
            if ($value === null) {
                continue;
            }
            $stringValue = trim((string)$value);
            if ($stringValue === '') {
                continue;
            }
            $clean[$key] = $stringValue;
        }
        return 'system_admin_dashboard.php' . (!empty($clean) ? ('?' . http_build_query($clean)) : '');
    }
}

$selectedEntryId = isset($_GET['entry']) ? (int)$_GET['entry'] : 0;
$unlockError = '';
$unlockSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_entry_id'])) {
    $unlockEntryId = (int)($_POST['unlock_entry_id'] ?? 0);
    $unlockPassword = $_POST['unlock_password'] ?? '';

    $stmtPassword = mysqli_prepare($conn, 'SELECT password FROM users WHERE id = ? LIMIT 1');
    $passwordVerified = false;
    if ($stmtPassword) {
        mysqli_stmt_bind_param($stmtPassword, 'i', $systemAdminId);
        mysqli_stmt_execute($stmtPassword);
        $resPassword = mysqli_stmt_get_result($stmtPassword);
        $passwordRow = $resPassword ? mysqli_fetch_assoc($resPassword) : null;
        if ($resPassword) {
            mysqli_free_result($resPassword);
        }
        mysqli_stmt_close($stmtPassword);

        if ($passwordRow) {
            $storedPassword = (string)$passwordRow['password'];
            $passwordVerified = password_verify($unlockPassword, $storedPassword) || md5($unlockPassword) === $storedPassword;
        }
    }

    if ($unlockEntryId <= 0 || $unlockPassword === '' || !$passwordVerified) {
        $unlockError = 'Password verification failed. Unable to open this entry.';
        log_activity($conn, [
            'actor_id' => $systemAdminId,
            'event_type' => 'audit_unlock_failed',
            'event_label' => 'Sensitive entry unlock failed',
            'description' => 'System admin failed to unlock a sensitive activity entry.',
            'object_type' => 'activity_logs',
            'object_id' => (string)$unlockEntryId,
            'metadata' => [
                'entry_id' => $unlockEntryId,
            ],
        ]);
    } else {
        $_SESSION['system_admin_unlocked_activity_details'][$unlockEntryId] = time() + 300;
        $unlockSuccess = 'Entry unlocked for 5 minutes.';
        log_activity($conn, [
            'actor_id' => $systemAdminId,
            'event_type' => 'audit_unlock_success',
            'event_label' => 'Sensitive entry unlocked',
            'description' => 'System admin unlocked a sensitive activity entry.',
            'object_type' => 'activity_logs',
            'object_id' => (string)$unlockEntryId,
            'metadata' => [
                'entry_id' => $unlockEntryId,
                'unlock_expires_at' => date('Y-m-d H:i:s', $_SESSION['system_admin_unlocked_activity_details'][$unlockEntryId]),
            ],
        ]);
        header('Location: ' . build_system_admin_dashboard_url([
            'scope' => $scope,
            'entry' => $unlockEntryId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'activity_action' => $actionFilter,
            'activity_preset' => $activityPreset,
        ]) . '#entry-' . $unlockEntryId);
        exit;
    }
}

function fetch_count_scalar(mysqli $conn, string $sql, array $params = [], string $types = ''): int
{
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return 0;
    }
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $count = 0;
    if ($result && ($row = mysqli_fetch_assoc($result))) {
        $count = (int)array_values($row)[0];
    }
    if ($result) {
        mysqli_free_result($result);
    }
    mysqli_stmt_close($stmt);
    return $count;
}

function format_metadata_label(string $key): string
{
    $label = str_replace(['_', '-'], ' ', $key);
    return ucwords(trim($label));
}

function flatten_metadata_rows(array $metadata, string $prefix = ''): array
{
    $rows = [];
    foreach ($metadata as $key => $value) {
        $fullKey = $prefix === '' ? (string)$key : ($prefix . '.' . (string)$key);
        if (is_array($value)) {
            if (empty($value)) {
                $rows[] = ['key' => $fullKey, 'value' => '[]'];
                continue;
            }
            if (array_keys($value) === range(0, count($value) - 1)) {
                $serialized = [];
                foreach ($value as $item) {
                    $serialized[] = is_scalar($item) || $item === null ? (string)$item : json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                $rows[] = ['key' => $fullKey, 'value' => implode(', ', $serialized)];
                continue;
            }
            $rows = array_merge($rows, flatten_metadata_rows($value, $fullKey));
            continue;
        }

        if ($value === null) {
            $display = 'null';
        } elseif (is_bool($value)) {
            $display = $value ? 'true' : 'false';
        } else {
            $display = trim((string)$value);
            if ($display === '') {
                $display = '(empty)';
            }
        }
        $rows[] = ['key' => $fullKey, 'value' => $display];
    }
    return $rows;
}

function fetch_name_by_id(mysqli $conn, string $table, string $nameColumn, int $id): string
{
    if ($id <= 0) {
        return '';
    }
    $sql = sprintf('SELECT %s AS label FROM %s WHERE id = ? LIMIT 1', $nameColumn, $table);
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return '';
    }
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $label = '';
    if ($result && ($row = mysqli_fetch_assoc($result))) {
        $label = trim((string)($row['label'] ?? ''));
    }
    if ($result) {
        mysqli_free_result($result);
    }
    mysqli_stmt_close($stmt);
    return $label;
}

$todayActiveUsers = fetch_count_scalar(
    $conn,
    "SELECT COUNT(DISTINCT actor_id) AS total
     FROM activity_logs
     WHERE actor_id IS NOT NULL
       AND action IN ('login_success', 'admin_login_success')
       AND created_at BETWEEN ? AND ?",
    [$todayStart, $todayEnd],
    'ss'
);

$weekActiveUsers = fetch_count_scalar(
    $conn,
    "SELECT COUNT(DISTINCT actor_id) AS total
     FROM activity_logs
     WHERE actor_id IS NOT NULL
       AND action IN ('login_success', 'admin_login_success')
       AND created_at BETWEEN ? AND ?",
    [$weekStart, $weekEnd],
    'ss'
);

$todayFailedLogins = fetch_count_scalar(
    $conn,
    "SELECT COUNT(*) AS total
     FROM activity_logs
     WHERE action IN ('login_failed', 'admin_login_failed')
       AND created_at BETWEEN ? AND ?",
    [$todayStart, $todayEnd],
    'ss'
);

$sensitivePlaceholders = implode(',', array_fill(0, count($sensitiveActions), '?'));
$sensitiveTypes = str_repeat('s', count($sensitiveActions)) . 'ss';
$sensitiveParams = array_merge($sensitiveActions, [$todayStart, $todayEnd]);
$todaySensitiveActions = fetch_count_scalar(
    $conn,
    "SELECT COUNT(*) AS total
     FROM activity_logs
     WHERE action IN ($sensitivePlaceholders)
       AND created_at BETWEEN ? AND ?",
    $sensitiveParams,
    $sensitiveTypes
);

$availableActions = [];
$actionRes = mysqli_query($conn, "SELECT DISTINCT action FROM activity_logs WHERE action IS NOT NULL AND action <> '' ORDER BY action ASC");
if ($actionRes) {
    while ($actionRow = mysqli_fetch_assoc($actionRes)) {
        $actionName = trim((string)($actionRow['action'] ?? ''));
        if ($actionName !== '') {
            $availableActions[] = $actionName;
        }
    }
    mysqli_free_result($actionRes);
}
if ($actionFilter !== '' && !in_array($actionFilter, $availableActions, true)) {
    $actionFilter = '';
}

$logsSql = "SELECT id, action, event_label, details, actor_name, actor_username, actor_role, created_at, metadata
            FROM activity_logs";
$logsTypes = '';
$logsParams = [];
$whereClauses = [];

if ($startDate !== '' || $endDate !== '') {
    $rangeStart = $startDate !== '' ? ($startDate . ' 00:00:00') : '1970-01-01 00:00:00';
    $rangeEnd = $endDate !== '' ? ($endDate . ' 23:59:59') : date('Y-m-d H:i:s');
    $whereClauses[] = 'created_at BETWEEN ? AND ?';
    $logsTypes .= 'ss';
    $logsParams[] = $rangeStart;
    $logsParams[] = $rangeEnd;
} elseif ($scope === 'today') {
    $whereClauses[] = 'created_at BETWEEN ? AND ?';
    $logsTypes .= 'ss';
    $logsParams[] = $todayStart;
    $logsParams[] = $todayEnd;
} elseif ($scope === 'week') {
    $whereClauses[] = 'created_at BETWEEN ? AND ?';
    $logsTypes .= 'ss';
    $logsParams[] = $weekStart;
    $logsParams[] = $weekEnd;
}

if ($actionFilter !== '') {
    $whereClauses[] = 'action = ?';
    $logsTypes .= 's';
    $logsParams[] = $actionFilter;
} elseif ($activityPreset !== '') {
    if ($activityPreset === 'active_logins') {
        $whereClauses[] = "action IN ('login_success', 'admin_login_success')";
    } elseif ($activityPreset === 'failed_logins') {
        $whereClauses[] = "action IN ('login_failed', 'admin_login_failed')";
    } elseif ($activityPreset === 'sensitive_actions') {
        $presetPlaceholders = implode(',', array_fill(0, count($sensitiveActions), '?'));
        $whereClauses[] = "action IN ($presetPlaceholders)";
        $logsTypes .= str_repeat('s', count($sensitiveActions));
        foreach ($sensitiveActions as $sensitiveAction) {
            $logsParams[] = $sensitiveAction;
        }
    }
}

if (!empty($whereClauses)) {
    $logsSql .= ' WHERE ' . implode(' AND ', $whereClauses);
}
$logsSql .= " ORDER BY created_at DESC, id DESC LIMIT 120";

$logs = [];
$stmtLogs = mysqli_prepare($conn, $logsSql);
if ($stmtLogs) {
    if (!empty($logsParams)) {
        mysqli_stmt_bind_param($stmtLogs, $logsTypes, ...$logsParams);
    }
    mysqli_stmt_execute($stmtLogs);
    $resLogs = mysqli_stmt_get_result($stmtLogs);
    if ($resLogs) {
        while ($row = mysqli_fetch_assoc($resLogs)) {
            $logs[] = $row;
        }
        mysqli_free_result($resLogs);
    }
    mysqli_stmt_close($stmtLogs);
}

$selectedEntry = null;
if ($selectedEntryId > 0) {
    $stmtEntry = mysqli_prepare($conn, "SELECT id, action, event_label, details, actor_name, actor_username, actor_role, created_at, metadata FROM activity_logs WHERE id = ? LIMIT 1");
    if ($stmtEntry) {
        mysqli_stmt_bind_param($stmtEntry, 'i', $selectedEntryId);
        mysqli_stmt_execute($stmtEntry);
        $resEntry = mysqli_stmt_get_result($stmtEntry);
        $selectedEntry = $resEntry ? mysqli_fetch_assoc($resEntry) : null;
        if ($resEntry) {
            mysqli_free_result($resEntry);
        }
        mysqli_stmt_close($stmtEntry);
    }
}

$selectedMetadata = [];
$selectedMetadataRows = [];
$selectedMetadataDisplayRows = [];
$selectedDetailRows = [];
$selectedNeedsUnlock = false;
$selectedUnlocked = false;
if ($selectedEntry) {
    $selectedNeedsUnlock = in_array((string)$selectedEntry['action'], $unlockableActions, true);
    $selectedUnlocked = isset($_SESSION['system_admin_unlocked_activity_details'][$selectedEntryId])
        && (int)$_SESSION['system_admin_unlocked_activity_details'][$selectedEntryId] >= time();

    if (!empty($selectedEntry['metadata'])) {
        $decoded = json_decode((string)$selectedEntry['metadata'], true);
        if (is_array($decoded)) {
            $selectedMetadata = $decoded;
            if (isset($decoded['details_rows']) && is_array($decoded['details_rows'])) {
                $selectedDetailRows = $decoded['details_rows'];
            }

            $metadataForDisplay = $selectedMetadata;
            unset($metadataForDisplay['details_rows']);
            $selectedMetadataRows = flatten_metadata_rows($metadataForDisplay);

            $subjectId = isset($decoded['subject_id']) ? (int)$decoded['subject_id'] : 0;
            $classId = isset($decoded['class_id']) ? (int)$decoded['class_id'] : 0;
            $sectionId = isset($decoded['section_id']) ? (int)$decoded['section_id'] : 0;

            $subjectName = fetch_name_by_id($conn, 'subjects', 'subject_name', $subjectId);
            if ($subjectName !== '') {
                $selectedMetadataRows[] = ['key' => 'resolved.subject_name', 'value' => $subjectName];
            }

            if ($classId > 0) {
                $stmtClass = mysqli_prepare($conn, 'SELECT class_name, semester, school FROM classes WHERE id = ? LIMIT 1');
                if ($stmtClass) {
                    mysqli_stmt_bind_param($stmtClass, 'i', $classId);
                    mysqli_stmt_execute($stmtClass);
                    $resClass = mysqli_stmt_get_result($stmtClass);
                    $classRow = $resClass ? mysqli_fetch_assoc($resClass) : null;
                    if ($resClass) {
                        mysqli_free_result($resClass);
                    }
                    mysqli_stmt_close($stmtClass);
                    if ($classRow) {
                        $className = trim((string)($classRow['class_name'] ?? ''));
                        $semester = trim((string)($classRow['semester'] ?? ''));
                        $school = trim((string)($classRow['school'] ?? ''));
                        if ($className !== '') {
                            $selectedMetadataRows[] = ['key' => 'resolved.class_name', 'value' => $className];
                        }
                        if ($semester !== '') {
                            $selectedMetadataRows[] = ['key' => 'resolved.semester', 'value' => $semester];
                        }
                        if ($school !== '') {
                            $selectedMetadataRows[] = ['key' => 'resolved.school', 'value' => $school];
                        }
                    }
                }
            }

            $sectionName = fetch_name_by_id($conn, 'sections', 'section_name', $sectionId);
            if ($sectionName !== '') {
                $selectedMetadataRows[] = ['key' => 'resolved.section_name', 'value' => $sectionName];
            }

            $metadataByKey = [];
            foreach ($selectedMetadataRows as $metaRow) {
                $metaKey = (string)($metaRow['key'] ?? '');
                if ($metaKey === '') {
                    continue;
                }
                $metadataByKey[$metaKey] = (string)($metaRow['value'] ?? '');
            }

            $preferredColumns = [
                ['label' => 'Teacher Id', 'key' => 'teacher_id'],
                ['label' => 'Teacher Unique Id', 'key' => 'teacher_unique_id'],
                ['label' => 'Subject Id', 'key' => 'subject_id'],
                ['label' => 'Subject Name', 'key' => 'subject_name'],
                ['label' => 'Class', 'key' => 'class_label'],
                ['label' => 'Resolved.Subject Name', 'key' => 'resolved.subject_name'],
                ['label' => 'Faculty Name', 'key' => 'faculty_name'],
            ];

            $resolvedFacultyName = '';
            $teacherIdFromMeta = isset($metadataByKey['teacher_id']) ? (int)$metadataByKey['teacher_id'] : 0;
            if ($teacherIdFromMeta > 0) {
                $stmtFaculty = mysqli_prepare($conn, 'SELECT name FROM users WHERE id = ? LIMIT 1');
                if ($stmtFaculty) {
                    mysqli_stmt_bind_param($stmtFaculty, 'i', $teacherIdFromMeta);
                    mysqli_stmt_execute($stmtFaculty);
                    $resFaculty = mysqli_stmt_get_result($stmtFaculty);
                    $facultyRow = $resFaculty ? mysqli_fetch_assoc($resFaculty) : null;
                    if ($resFaculty) {
                        mysqli_free_result($resFaculty);
                    }
                    mysqli_stmt_close($stmtFaculty);
                    if ($facultyRow) {
                        $resolvedFacultyName = trim((string)($facultyRow['name'] ?? ''));
                    }
                }
            }

            foreach ($preferredColumns as $column) {
                $columnKey = $column['key'];
                $value = '';
                if ($columnKey === 'faculty_name') {
                    $value = $resolvedFacultyName;
                    if ($value === '') {
                        $value = trim((string)($selectedEntry['actor_name'] ?? ''));
                    }
                    if ($value === '') {
                        $value = trim((string)($selectedEntry['actor_username'] ?? ''));
                    }
                } elseif ($columnKey === 'class_label') {
                    $resolvedClassName = trim((string)($metadataByKey['resolved.class_name'] ?? ''));
                    $resolvedSectionName = trim((string)($metadataByKey['resolved.section_name'] ?? ''));
                    if ($resolvedClassName !== '' && $resolvedSectionName !== '') {
                        $value = $resolvedClassName . ' - Section ' . $resolvedSectionName;
                    } elseif ($resolvedClassName !== '') {
                        $value = $resolvedClassName;
                    } else {
                        $value = trim((string)($metadataByKey['class_label'] ?? ''));
                    }
                } elseif (isset($metadataByKey[$columnKey])) {
                    $value = $metadataByKey[$columnKey];
                }

                if ($value !== '') {
                    $selectedMetadataDisplayRows[] = [
                        'key' => (string)$column['label'],
                        'value' => $value,
                    ];
                }
            }
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
    <title>System Admin Dashboard - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
            text-decoration: none;
            display: block;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            border-color: #d8c0c7;
        }
        .stat-card h4 {
            margin: 0 0 8px;
            color: #A6192E;
            font-size: 1rem;
        }
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            line-height: 1.2;
        }
        .stat-date {
            margin-top: 6px;
            color: #6b7280;
            font-size: 0.82rem;
        }
        .toolbar {
            background: #fff;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 12px 14px;
            margin-bottom: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .toolbar a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #d7dce3;
            background: #f8fafc;
            color: #2f3b4a;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
        }
        .toolbar a.active {
            background: #A6192E;
            border-color: #A6192E;
            color: #fff;
        }
        .filters-form {
            margin-bottom: 14px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px 14px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
            align-items: end;
        }
        .filters-form .field-block {
            margin: 0;
        }
        .filters-form label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.8rem;
            color: #4b5563;
            font-weight: 600;
        }
        .filters-form input,
        .filters-form select {
            width: 100%;
            margin: 0;
            padding: 8px 10px;
            border: 1px solid #d8dee7;
            border-radius: 8px;
            font-size: 0.85rem;
            background: #f8fafc;
        }
        .filters-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-filter {
            border: none;
            border-radius: 8px;
            background: #A6192E;
            color: #fff;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 8px 12px;
            cursor: pointer;
        }
        .btn-filter.secondary {
            background: #e7edf4;
            color: #2f3b4a;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .audit-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
        }
        .audit-table th,
        .audit-table td {
            border-bottom: 1px solid #e8edf3;
            padding: 10px;
            font-size: 0.88rem;
            text-align: left;
            vertical-align: top;
        }
        .audit-table th {
            background: #A6192E;
            color: #fff;
            font-size: 0.82rem;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        .audit-tag {
            display: inline-block;
            background: #fdf1f3;
            border: 1px solid #f4c6ce;
            color: #8b1d2d;
            border-radius: 999px;
            padding: 3px 10px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .inline-detail-cell {
            background: #fcfdff;
            border-top: none;
            padding: 8px;
        }
        .inline-detail-wrap {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px;
            background: #fff;
        }
        .inline-detail-wrap h5 {
            margin: 0 0 8px;
            color: #A6192E;
            font-size: 1.05rem;
        }
        .inline-detail-actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
            flex-wrap: wrap;
        }
        .panel {
            margin-top: 18px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
            padding: 14px;
        }
        .panel h4 {
            color: #A6192E;
            margin-bottom: 10px;
        }
        .unlock-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .unlock-form input {
            max-width: 280px;
            margin: 0;
        }
        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }
        .detail-table th,
        .detail-table td {
            border: 1px solid #e5e7eb;
            padding: 6px;
            font-size: 0.8rem;
            text-align: left;
        }
        .detail-table th {
            background: #f8fafc;
            color: #334155;
            text-transform: uppercase;
            font-size: 0.74rem;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .meta-table th,
        .meta-table td {
            border: 1px solid #e5e7eb;
            padding: 6px;
            font-size: 0.79rem;
            text-align: left;
            vertical-align: top;
            white-space: nowrap;
        }
        .meta-table th {
            background: #f8fafc;
            color: #475569;
            text-transform: uppercase;
            font-size: 0.73rem;
            letter-spacing: 0.02em;
        }
        .meta-table td {
            background: #ffffff;
            color: #1f2937;
            font-size: 0.82rem;
        }
        .meta-scroll,
        .detail-scroll {
            max-height: 220px;
            overflow: auto;
            border-radius: 8px;
        }
        .note {
            margin: 4px 0;
            font-size: 0.8rem;
        }
        .note.error { color: #b42318; }
        .note.success { color: #117a37; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h2>ICA Tracker</h2>
            <a href="system_admin_dashboard.php" class="active"><i class="fas fa-shield-alt"></i> <span>System Admin</span></a>
            <a href="system_admin_dashboard.php#activity"><i class="fas fa-stream"></i> <span>Activity Feed</span></a>
            <a href="system_admin_export_sql.php"><i class="fas fa-database"></i> <span>Download SQL Snapshot</span></a>
            <a href="admin_dashboard.php"><i class="fas fa-user-cog"></i> <span>Admin Dashboard</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>

        <div class="main-content">
            <div class="header">
                <h2>Welcome, <?php echo htmlspecialchars($systemAdminNameDisplay !== '' ? $systemAdminNameDisplay : $systemAdminNameRaw); ?>!</h2>
            </div>

            <div class="container">
                <div class="stats-grid">
                    <a class="stat-card" href="<?php echo htmlspecialchars(build_system_admin_dashboard_url([
                        'scope' => 'today',
                        'activity_preset' => 'active_logins',
                    ])); ?>">
                        <h4>Today Active Users</h4>
                        <div class="stat-value"><?php echo (int)$todayActiveUsers; ?></div>
                        <div class="stat-date"><?php echo htmlspecialchars($todayLabel); ?></div>
                    </a>
                    <a class="stat-card" href="<?php echo htmlspecialchars(build_system_admin_dashboard_url([
                        'scope' => 'week',
                        'activity_preset' => 'active_logins',
                    ])); ?>">
                        <h4>This Week Active Users</h4>
                        <div class="stat-value"><?php echo (int)$weekActiveUsers; ?></div>
                        <div class="stat-date"><?php echo htmlspecialchars($weekLabel); ?></div>
                    </a>
                    <a class="stat-card" href="<?php echo htmlspecialchars(build_system_admin_dashboard_url([
                        'scope' => 'today',
                        'activity_preset' => 'failed_logins',
                    ])); ?>">
                        <h4>Failed Login Attempts</h4>
                        <div class="stat-value"><?php echo (int)$todayFailedLogins; ?></div>
                        <div class="stat-date"><?php echo htmlspecialchars($todayLabel); ?></div>
                    </a>
                    <a class="stat-card" href="<?php echo htmlspecialchars(build_system_admin_dashboard_url([
                        'scope' => 'today',
                        'activity_preset' => 'sensitive_actions',
                    ])); ?>">
                        <h4>Sensitive Actions</h4>
                        <div class="stat-value"><?php echo (int)$todaySensitiveActions; ?></div>
                        <div class="stat-date"><?php echo htmlspecialchars($todayLabel); ?></div>
                    </a>
                </div>

                <div class="toolbar" id="activity">
                    <a class="<?php echo $scope === 'today' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(build_system_admin_dashboard_url(['scope' => 'today'])); ?>">Today</a>
                    <a class="<?php echo $scope === 'week' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(build_system_admin_dashboard_url(['scope' => 'week'])); ?>">This Week</a>
                    <a class="<?php echo $scope === 'all' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(build_system_admin_dashboard_url(['scope' => 'all'])); ?>">All</a>
                </div>

                <form method="GET" class="filters-form">
                    <input type="hidden" name="scope" value="<?php echo htmlspecialchars($scope); ?>">

                    <div class="field-block">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                    </div>

                    <div class="field-block">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                    </div>

                    <div class="field-block">
                        <label for="activity_action">Activity Filter</label>
                        <select id="activity_action" name="activity_action">
                            <option value="">All Activities</option>
                            <?php foreach ($availableActions as $actionName): ?>
                                <option value="<?php echo htmlspecialchars($actionName); ?>" <?php echo $actionFilter === $actionName ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($actionName); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field-block filters-actions">
                        <button type="submit" class="btn-filter">Apply Filters</button>
                        <a class="btn-filter secondary" href="<?php echo htmlspecialchars(build_system_admin_dashboard_url(['scope' => $scope])); ?>">Clear</a>
                    </div>
                </form>

                <table class="audit-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Time</th>
                            <th style="width: 17%;">Actor</th>
                            <th style="width: 16%;">Action</th>
                            <th>Summary</th>
                            <th style="width: 12%;">Inspect</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="5">No activity entries found for this range.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $row): ?>
                                <?php
                                    $actorLabel = trim((string)($row['actor_name'] ?? ''));
                                    if ($actorLabel === '') {
                                        $actorLabel = trim((string)($row['actor_username'] ?? 'System'));
                                    }
                                    $eventLabel = trim((string)($row['event_label'] ?? ''));
                                    if ($eventLabel === '') {
                                        $eventLabel = trim((string)$row['action']);
                                    }
                                    $isSensitive = in_array((string)$row['action'], $unlockableActions, true);
                                    $isCurrentEntry = $selectedEntry && (int)$selectedEntry['id'] === (int)$row['id'];
                                ?>
                                <tr id="entry-<?php echo (int)$row['id']; ?>">
                                    <td><?php echo htmlspecialchars((string)$row['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($actorLabel); ?><br><span class="audit-tag"><?php echo htmlspecialchars((string)($row['actor_role'] ?? 'n/a')); ?></span></td>
                                    <td><?php echo htmlspecialchars((string)$row['action']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($eventLabel); ?></strong><br><?php echo htmlspecialchars((string)($row['details'] ?? '')); ?></td>
                                    <td>
                                        <?php if ($isCurrentEntry): ?>
                                            <a href="<?php echo htmlspecialchars(build_system_admin_dashboard_url([
                                                'scope' => $scope,
                                                'start_date' => $startDate,
                                                'end_date' => $endDate,
                                                'activity_action' => $actionFilter,
                                                'activity_preset' => $activityPreset,
                                            ]) . '#entry-' . (int)$row['id']); ?>">Close</a>
                                        <?php else: ?>
                                            <a href="<?php echo htmlspecialchars(build_system_admin_dashboard_url([
                                                'scope' => $scope,
                                                'entry' => (int)$row['id'],
                                                'start_date' => $startDate,
                                                'end_date' => $endDate,
                                                'activity_action' => $actionFilter,
                                                'activity_preset' => $activityPreset,
                                            ]) . '#entry-' . (int)$row['id']); ?>">Open</a>
                                        <?php endif; ?>
                                        <?php if ($isSensitive): ?><br><span class="audit-tag">Password Required</span><?php endif; ?>
                                    </td>
                                </tr>

                                <?php if ($isCurrentEntry): ?>
                                    <tr>
                                        <td colspan="5" class="inline-detail-cell">
                                            <div class="inline-detail-wrap">
                                                <h5>Entry #<?php echo (int)$selectedEntry['id']; ?> - <?php echo htmlspecialchars((string)($selectedEntry['event_label'] ?: $selectedEntry['action'])); ?></h5>
                                                <p class="note">Action: <?php echo htmlspecialchars((string)$selectedEntry['action']); ?> | Time: <?php echo htmlspecialchars((string)$selectedEntry['created_at']); ?></p>

                                                <?php if ($unlockError !== ''): ?><p class="note error"><?php echo htmlspecialchars($unlockError); ?></p><?php endif; ?>
                                                <?php if ($unlockSuccess !== ''): ?><p class="note success"><?php echo htmlspecialchars($unlockSuccess); ?></p><?php endif; ?>

                                                <?php if ($selectedNeedsUnlock && !$selectedUnlocked): ?>
                                                    <p class="note">This entry contains sensitive marks-level details. Enter your password to view.</p>
                                                    <form class="unlock-form" method="POST">
                                                        <input type="hidden" name="unlock_entry_id" value="<?php echo (int)$selectedEntry['id']; ?>">
                                                        <input type="password" name="unlock_password" placeholder="System Admin password" required>
                                                        <div class="inline-detail-actions">
                                                            <button class="btn" type="submit">Unlock Entry</button>
                                                        </div>
                                                    </form>
                                                <?php else: ?>
                                                    <?php if (!empty($selectedMetadataDisplayRows)): ?>
                                                        <div class="meta-scroll" style="margin-top:8px;">
                                                            <table class="meta-table">
                                                                <thead>
                                                                    <tr>
                                                                        <?php foreach ($selectedMetadataDisplayRows as $metaRow): ?>
                                                                            <th><?php echo htmlspecialchars((string)($metaRow['key'] ?? '')); ?></th>
                                                                        <?php endforeach; ?>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <?php foreach ($selectedMetadataDisplayRows as $metaRow): ?>
                                                                            <td><?php echo htmlspecialchars((string)($metaRow['value'] ?? '')); ?></td>
                                                                        <?php endforeach; ?>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($selectedDetailRows)): ?>
                                                        <div class="detail-scroll" style="margin-top:8px;">
                                                            <table class="detail-table">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Student</th>
                                                                        <th>SAP ID</th>
                                                                        <th>Component</th>
                                                                        <th>Instance</th>
                                                                        <th>Marks</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($selectedDetailRows as $detail): ?>
                                                                        <tr>
                                                                            <td><?php echo htmlspecialchars((string)($detail['student_name'] ?? '')); ?></td>
                                                                            <td><?php echo htmlspecialchars((string)($detail['sap_id'] ?? '')); ?></td>
                                                                            <td><?php echo htmlspecialchars((string)($detail['component_name'] ?? '')); ?></td>
                                                                            <td><?php echo htmlspecialchars((string)($detail['instance_number'] ?? '')); ?></td>
                                                                            <td><?php echo htmlspecialchars((string)($detail['marks'] ?? '')); ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php else: ?>
                                                        <p class="note">No additional drill-down rows for this entry.</p>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="footer-bottom">
                &copy; <?php echo date('Y'); ?> Kuchuru Sai Krishna Reddy - STME. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
