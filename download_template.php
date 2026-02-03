<?php
// Secure template download endpoint
$file = __DIR__ . DIRECTORY_SEPARATOR . 'Classes' . DIRECTORY_SEPARATOR . 'Student List Template .csv';
if (!file_exists($file)) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    echo 'Template not found.';
    exit;
}
// Force download with a friendly filename
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="student_template.csv"');
header('Content-Length: ' . filesize($file));
readfile($file);
exit;
?>