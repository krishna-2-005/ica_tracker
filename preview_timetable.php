<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(403);
    echo 'Access denied.';
    exit;
}

$allowedRoles = ['student', 'teacher', 'program_chair', 'admin'];
if (!in_array($_SESSION['role'], $allowedRoles, true)) {
    http_response_code(403);
    echo 'Access denied.';
    exit;
}

$pathParam = isset($_GET['path']) ? (string)$_GET['path'] : '';
if ($pathParam === '') {
    http_response_code(400);
    echo 'File not specified.';
    exit;
}

$decodedPath = rawurldecode($pathParam);
$normalizedPath = str_replace('\\', '/', $decodedPath);
$normalizedPath = ltrim($normalizedPath, '/');

$allowedPrefix = 'uploads/class_timetables/';
if (strpos(strtolower($normalizedPath), strtolower($allowedPrefix)) !== 0) {
    http_response_code(403);
    echo 'Invalid file path.';
    exit;
}

$baseDirectory = realpath(__DIR__ . '/uploads/class_timetables');
if ($baseDirectory === false) {
    http_response_code(500);
    echo 'Timetable directory not available.';
    exit;
}

$absolutePath = realpath(__DIR__ . '/' . $normalizedPath);
if ($absolutePath === false || strpos($absolutePath, $baseDirectory) !== 0 || !is_file($absolutePath)) {
    http_response_code(404);
    echo 'File not found.';
    exit;
}

$finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
$mimeType = $finfo ? finfo_file($finfo, $absolutePath) : null;
if ($finfo) {
    finfo_close($finfo);
}
if ($mimeType === null || $mimeType === false) {
    $mimeType = 'application/octet-stream';
}

$filename = basename($absolutePath);
$sanitizedFilename = str_replace('"', '', $filename);

header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . $sanitizedFilename . '"');
header('Content-Length: ' . filesize($absolutePath));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=600');

$chunkSize = 8192;
$handle = fopen($absolutePath, 'rb');
if ($handle === false) {
    http_response_code(500);
    echo 'Unable to read file.';
    exit;
}
while (!feof($handle)) {
    $buffer = fread($handle, $chunkSize);
    if ($buffer === false) {
        break;
    }
    echo $buffer;
    flush();
}
fclose($handle);
exit;
