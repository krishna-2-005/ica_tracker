<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db_connect.php';
$result = mysqli_query($conn, "SELECT id, name FROM users WHERE role = 'teacher' ORDER BY id LIMIT 10");
if (!$result) {
    echo 'Query error: ' . mysqli_error($conn) . PHP_EOL;
    exit(1);
}
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['id'] . ' - ' . $row['name'] . PHP_EOL;
}
