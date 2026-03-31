<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/activity_logger.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'system_admin') {
    header('Location: admin_login.php');
    exit;
}

$systemAdminId = (int)$_SESSION['user_id'];
$generatedAt = date('d-m-Y H-i-s');
$filename = 'ica_tracker ' . $generatedAt . '.sql';

log_activity($conn, [
    'actor_id' => $systemAdminId,
    'event_type' => 'db_snapshot_downloaded',
    'event_label' => 'Database SQL snapshot downloaded',
    'description' => 'System admin generated and downloaded a SQL snapshot.',
    'object_type' => 'database',
    'object_id' => $filename,
    'metadata' => [
        'filename' => $filename,
        'generated_at' => date('Y-m-d H:i:s'),
    ],
]);

header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

mysqli_set_charset($conn, 'utf8mb4');

echo "-- ICA Tracker SQL Snapshot\n";
echo "-- Generated at: " . date('Y-m-d H:i:s') . "\n\n";
echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
echo "START TRANSACTION;\n";
echo "SET time_zone = \"+00:00\";\n\n";

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

    echo "-- ------------------------------\n";
    echo "-- Table structure for " . $safeTable . "\n";
    echo "-- ------------------------------\n\n";
    echo 'DROP TABLE IF EXISTS ' . $safeTable . ";\n";
    if ($createRow && isset($createRow['Create Table'])) {
        echo $createRow['Create Table'] . ";\n\n";
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

    echo "-- Dumping data for " . $safeTable . "\n\n";

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
        echo 'INSERT INTO ' . $safeTable . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n";
    }
    echo "\n";
    mysqli_free_result($dataRes);
}

echo "COMMIT;\n";

mysqli_close($conn);
exit;
