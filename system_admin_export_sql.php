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

$error = '';
$success = '';

function ensure_backup_restore_history_table(mysqli $conn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS system_admin_backup_restore_history (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        admin_id INT NOT NULL,
        action_type VARCHAR(20) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
        table_count INT UNSIGNED NOT NULL DEFAULT 0,
        database_size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
        status VARCHAR(20) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_admin_created (admin_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    @mysqli_query($conn, $sql);
}

function get_database_size_bytes(mysqli $conn): int
{
    $size = 0;
    $sql = "SELECT COALESCE(SUM(data_length + index_length), 0) AS total_size
            FROM information_schema.TABLES
            WHERE table_schema = DATABASE()";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $size = isset($row['total_size']) ? (int)$row['total_size'] : 0;
        mysqli_free_result($res);
    }
    return max(0, $size);
}

function format_bytes_human(int $bytes): string
{
    if ($bytes <= 0) {
        return '0 B';
    }
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $power = (int)floor(log($bytes, 1024));
    $power = max(0, min($power, count($units) - 1));
    $value = $bytes / pow(1024, $power);
    return number_format($value, $power === 0 ? 0 : 2) . ' ' . $units[$power];
}

function log_backup_restore_history(
    mysqli $conn,
    int $adminId,
    string $actionType,
    string $fileName,
    int $fileSizeBytes,
    int $tableCount,
    int $databaseSizeBytes,
    string $status
): void {
    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO system_admin_backup_restore_history (admin_id, action_type, file_name, file_size_bytes, table_count, database_size_bytes, status) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$stmt) {
        return;
    }
    mysqli_stmt_bind_param(
        $stmt,
        'issiiis',
        $adminId,
        $actionType,
        $fileName,
        $fileSizeBytes,
        $tableCount,
        $databaseSizeBytes,
        $status
    );
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function verify_system_admin_password(mysqli $conn, int $adminId, string $plainPassword): bool
{
    if ($adminId <= 0 || $plainPassword === '') {
        return false;
    }

    $stmt = mysqli_prepare($conn, 'SELECT password FROM users WHERE id = ? AND role = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }

    $role = 'system_admin';
    mysqli_stmt_bind_param($stmt, 'is', $adminId, $role);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $userRow = $result ? mysqli_fetch_assoc($result) : null;
    if ($result) {
        mysqli_free_result($result);
    }
    mysqli_stmt_close($stmt);

    if (!$userRow || !isset($userRow['password'])) {
        return false;
    }

    $storedPassword = (string)$userRow['password'];
    if ($storedPassword === '') {
        return false;
    }

    if (strlen($storedPassword) > 50 || strpos($storedPassword, '$') === 0) {
        return password_verify($plainPassword, $storedPassword);
    }

    return md5($plainPassword) === $storedPassword;
}

function build_database_sql_snapshot(mysqli $conn): string
{
    mysqli_set_charset($conn, 'utf8mb4');
    $dump = '';
    $dump .= "-- ICA Tracker Full Database Backup\n";
    $dump .= "-- Generated at: " . date('Y-m-d H:i:s') . "\n\n";
    $dump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $dump .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $dump .= "START TRANSACTION;\n";
    $dump .= "SET time_zone = \"+00:00\";\n\n";

    $tables = [];
    $tablesRes = mysqli_query($conn, 'SHOW TABLES');
    if ($tablesRes) {
        while ($row = mysqli_fetch_row($tablesRes)) {
            if (!empty($row[0])) {
                $tables[] = $row[0];
            }
        }
        mysqli_free_result($tablesRes);
    }

    foreach ($tables as $table) {
        $safeTable = '`' . str_replace('`', '``', $table) . '`';

        $createRes = mysqli_query($conn, 'SHOW CREATE TABLE ' . $safeTable);
        $createRow = $createRes ? mysqli_fetch_assoc($createRes) : null;
        if ($createRes) {
            mysqli_free_result($createRes);
        }

        $dump .= "-- ------------------------------\n";
        $dump .= "-- Table structure for " . $safeTable . "\n";
        $dump .= "-- ------------------------------\n\n";
        $dump .= 'DROP TABLE IF EXISTS ' . $safeTable . ";\n";
        if ($createRow && isset($createRow['Create Table'])) {
            $dump .= $createRow['Create Table'] . ";\n\n";
        }

        $dataRes = mysqli_query($conn, 'SELECT * FROM ' . $safeTable);
        if (!$dataRes) {
            continue;
        }

        if (mysqli_num_rows($dataRes) === 0) {
            mysqli_free_result($dataRes);
            continue;
        }

        $fields = mysqli_fetch_fields($dataRes);
        $columns = [];
        foreach ($fields as $field) {
            $columns[] = '`' . str_replace('`', '``', $field->name) . '`';
        }

        $dump .= "-- Dumping data for " . $safeTable . "\n\n";

        while ($row = mysqli_fetch_assoc($dataRes)) {
            $values = [];
            foreach ($fields as $field) {
                $name = $field->name;
                $value = $row[$name];
                if ($value === null) {
                    $values[] = 'NULL';
                } else {
                    $escaped = mysqli_real_escape_string($conn, (string)$value);
                    $values[] = "'" . $escaped . "'";
                }
            }

            $dump .= 'INSERT INTO ' . $safeTable . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n";
        }

        $dump .= "\n";
        mysqli_free_result($dataRes);
    }

    $dump .= "COMMIT;\n";
    $dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";

    return $dump;
}

function execute_sql_restore(mysqli $conn, string $sql, string &$restoreError): bool
{
    $restoreError = '';
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
    if ($sql === null || trim($sql) === '') {
        $restoreError = 'Uploaded SQL file is empty.';
        return false;
    }

    mysqli_set_charset($conn, 'utf8mb4');
    mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS = 0');

    if (!mysqli_multi_query($conn, $sql)) {
        $restoreError = mysqli_error($conn);
        mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS = 1');
        return false;
    }

    do {
        $result = mysqli_store_result($conn);
        if ($result instanceof mysqli_result) {
            mysqli_free_result($result);
        }
        if (!mysqli_more_results($conn)) {
            break;
        }
        if (!mysqli_next_result($conn)) {
            $restoreError = mysqli_error($conn);
            mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS = 1');
            return false;
        }
    } while (true);

    mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS = 1');
    return true;
}

$tableCount = 0;
$tableCountResult = mysqli_query($conn, 'SHOW TABLES');
if ($tableCountResult) {
    $tableCount = (int)mysqli_num_rows($tableCountResult);
    mysqli_free_result($tableCountResult);
}

ensure_backup_restore_history_table($conn);

$historyRows = [];
$historyResult = mysqli_query($conn, 'SELECT created_at, file_name, file_size_bytes, table_count, database_size_bytes, status FROM system_admin_backup_restore_history ORDER BY id DESC LIMIT 100');
if ($historyResult) {
    while ($row = mysqli_fetch_assoc($historyResult)) {
        $historyRows[] = $row;
    }
    mysqli_free_result($historyResult);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $password = (string)($_POST['system_admin_password'] ?? '');

    if ($action !== 'download_backup' && $action !== 'restore_backup') {
        $error = 'Invalid request.';
    } elseif ($password === '') {
        $error = 'Enter password.';
    } elseif (!verify_system_admin_password($conn, $systemAdminId, $password)) {
        $error = 'Invalid password.';
    } elseif ($action === 'download_backup') {
        $generatedAt = date('d-m-Y_H-i-s');
        $filename = 'ica_tracker_full_backup_' . $generatedAt . '.sql';
        $databaseSizeBytes = get_database_size_bytes($conn);

        $snapshotSql = build_database_sql_snapshot($conn);
        $fileSizeBytes = strlen($snapshotSql);

        log_backup_restore_history(
            $conn,
            $systemAdminId,
            'backup',
            $filename,
            $fileSizeBytes,
            $tableCount,
            $databaseSizeBytes,
            'success'
        );

        log_activity($conn, [
            'actor_id' => $systemAdminId,
            'event_type' => 'db_backup_downloaded',
            'event_label' => 'Database backup downloaded',
            'description' => 'System admin downloaded full database backup SQL.',
            'object_type' => 'database',
            'object_id' => $filename,
            'metadata' => [
                'filename' => $filename,
                'generated_at' => date('Y-m-d H:i:s'),
                'table_count' => $tableCount,
                'file_size_bytes' => $fileSizeBytes,
                'database_size_bytes' => $databaseSizeBytes,
            ],
        ]);

        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $snapshotSql;
        mysqli_close($conn);
        exit;
    } elseif ($action === 'restore_backup') {
        if (!isset($_FILES['restore_sql_file']) || (int)($_FILES['restore_sql_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $error = 'Please choose a valid SQL file to restore.';
        } else {
            $originalName = (string)($_FILES['restore_sql_file']['name'] ?? '');
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if ($extension !== 'sql') {
                $error = 'Only .sql files are allowed for restore.';
            } else {
                $tmpPath = (string)($_FILES['restore_sql_file']['tmp_name'] ?? '');
                $sqlContent = $tmpPath !== '' ? @file_get_contents($tmpPath) : false;
                $fileSizeBytes = isset($_FILES['restore_sql_file']['size']) ? (int)$_FILES['restore_sql_file']['size'] : 0;
                if ($sqlContent === false) {
                    $error = 'Unable to read the uploaded SQL file.';
                } else {
                    $restoreError = '';
                    if (!execute_sql_restore($conn, $sqlContent, $restoreError)) {
                        log_backup_restore_history(
                            $conn,
                            $systemAdminId,
                            'restore',
                            $originalName !== '' ? $originalName : 'uploaded.sql',
                            $fileSizeBytes,
                            $tableCount,
                            get_database_size_bytes($conn),
                            'failed'
                        );
                        $error = 'Restore failed: ' . ($restoreError !== '' ? $restoreError : 'Unknown SQL execution error.');
                    } else {
                        $success = 'Database restore completed successfully.';

                        $newTableCount = 0;
                        $tableCountResult = mysqli_query($conn, 'SHOW TABLES');
                        if ($tableCountResult) {
                            $newTableCount = (int)mysqli_num_rows($tableCountResult);
                            mysqli_free_result($tableCountResult);
                        }

                        log_backup_restore_history(
                            $conn,
                            $systemAdminId,
                            'restore',
                            $originalName !== '' ? $originalName : 'uploaded.sql',
                            $fileSizeBytes,
                            $newTableCount,
                            get_database_size_bytes($conn),
                            'success'
                        );

                        log_activity($conn, [
                            'actor_id' => $systemAdminId,
                            'event_type' => 'db_restore_completed',
                            'event_label' => 'Database restore completed',
                            'description' => 'System admin restored the database from uploaded SQL backup.',
                            'object_type' => 'database',
                            'object_id' => $originalName !== '' ? $originalName : 'uploaded_sql',
                            'metadata' => [
                                'filename' => $originalName,
                                'restored_at' => date('Y-m-d H:i:s'),
                            ],
                        ]);
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup & Restore - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .ops-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 16px;
        }
        .ops-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
        }
        .ops-card h3 {
            margin: 0 0 8px;
            color: #A6192E;
            font-size: 1.2rem;
        }
        .ops-card p {
            margin: 0 0 12px;
            color: #4b5563;
            font-size: 0.92rem;
        }
        .ops-meta {
            margin: 0 0 12px;
            padding: 10px 12px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            color: #1f2937;
            font-size: 0.9rem;
        }
        .ops-form .form-group {
            margin-bottom: 10px;
        }
        .ops-form label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #374151;
        }
        .ops-form input[type="password"],
        .ops-form input[type="file"] {
            width: 100%;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: #fff;
            font-size: 0.92rem;
        }
        .ops-note {
            font-size: 0.82rem;
            color: #6b7280;
            margin-top: 8px;
        }
        .alert-block {
            margin-bottom: 14px;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .alert-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        .alert-success {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        .history-card {
            margin-top: 16px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
        }
        .history-card h3 {
            margin: 0 0 10px;
            color: #A6192E;
            font-size: 1.1rem;
        }
        .history-wrap {
            width: 100%;
            overflow-x: auto;
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            background: #fff;
        }
        .history-table th,
        .history-table td {
            border-bottom: 1px solid #edf2f7;
            padding: 9px 10px;
            text-align: left;
            white-space: nowrap;
        }
        .history-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 2px 10px;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-pill.success {
            background: #dcfce7;
            color: #166534;
        }
        .status-pill.failed {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
<div class="dashboard">
    <div class="sidebar">
        <h2>ICA Tracker</h2>
        <a href="system_admin_dashboard.php"><i class="fas fa-shield-alt"></i> <span>Dashboard</span></a>
        <a href="system_admin_activity_feed.php"><i class="fas fa-stream"></i> <span>Activity Feed</span></a>
        <a href="system_admin_export_sql.php" class="active"><i class="fas fa-database"></i> <span>Backup & Restore</span></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Database Backup & Restore</h2>
        </div>

        <div class="container">
            <?php if ($error !== ''): ?>
                <div class="alert-block alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success !== ''): ?>
                <div class="alert-block alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="ops-grid">
                <div class="ops-card">
                    <h3><i class="fas fa-download"></i> Download Full SQL Backup</h3>
                    <p>Downloads a complete SQL file of the current ICA Tracker database, including all project tables and data.</p>
                    <div class="ops-meta">
                        <strong>Current detected tables:</strong> <?php echo (int)$tableCount; ?>
                    </div>
                    <form method="POST" class="ops-form">
                        <input type="hidden" name="action" value="download_backup">
                        <div class="form-group">
                            <label for="download_password">Enter Password</label>
                            <input id="download_password" type="password" name="system_admin_password" placeholder="Enter password" required>
                        </div>
                        <button type="submit" class="btn"><i class="fas fa-file-download"></i> Download Backup SQL</button>
                        <div class="ops-note">Enter password to continue.</div>
                    </form>
                </div>

                <div class="ops-card">
                    <h3><i class="fas fa-upload"></i> Restore Database from SQL</h3>
                    <p>Restores database structure and data using an uploaded SQL file. Use only trusted project backups.</p>
                    <form method="POST" enctype="multipart/form-data" class="ops-form">
                        <input type="hidden" name="action" value="restore_backup">
                        <div class="form-group">
                            <label for="restore_file">SQL File (.sql)</label>
                            <input id="restore_file" type="file" name="restore_sql_file" accept=".sql" required>
                        </div>
                        <div class="form-group">
                            <label for="restore_password">Enter Password</label>
                            <input id="restore_password" type="password" name="system_admin_password" placeholder="Enter password" required>
                        </div>
                        <button type="submit" class="btn" onclick="return confirm('This will execute SQL restore on the database. Continue?');"><i class="fas fa-database"></i> Restore Database</button>
                        <div class="ops-note">Enter password to continue.</div>
                    </form>
                </div>
            </div>

            <div class="history-card">
                <h3><i class="fas fa-history"></i> Backup / Restore History</h3>
                <div class="history-wrap">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>File Name</th>
                                <th>File Size</th>
                                <th>Tables</th>
                                <th>DB Size</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historyRows)): ?>
                                <tr>
                                    <td colspan="6">No history available yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($historyRows as $history): ?>
                                    <?php
                                        $statusRaw = strtolower(trim((string)($history['status'] ?? '')));
                                        $statusClass = $statusRaw === 'success' ? 'success' : 'failed';
                                        $createdAt = trim((string)($history['created_at'] ?? ''));
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($createdAt !== '' ? date('d M Y, h:i A', strtotime($createdAt)) : 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars((string)($history['file_name'] ?? 'N/A')); ?></td>
                                        <td><?php echo htmlspecialchars(format_bytes_human((int)($history['file_size_bytes'] ?? 0))); ?></td>
                                        <td><?php echo (int)($history['table_count'] ?? 0); ?></td>
                                        <td><?php echo htmlspecialchars(format_bytes_human((int)($history['database_size_bytes'] ?? 0))); ?></td>
                                        <td><span class="status-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusRaw !== '' ? $statusRaw : 'failed'); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            &copy; <?php echo date('Y'); ?> Kuchuru Sai Krishna Reddy - STME. All rights reserved.
        </div>
    </div>
</div>
</body>
</html>
