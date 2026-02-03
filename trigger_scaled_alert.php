<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'teacher') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid payload.']);
    exit;
}

$subject_id = isset($data['subject_id']) ? (int)$data['subject_id'] : 0;
$total_scaled = isset($data['total_scaled']) ? (float)$data['total_scaled'] : 0.0;

if ($subject_id <= 0 || $total_scaled <= 50) {
    echo json_encode(['status' => 'ignored']);
    exit;
}

include 'db_connect.php';
require_once 'alert_helpers.php';

$teacher_id = (int)$_SESSION['user_id'];
$inserted = send_scaled_marks_alert($conn, $teacher_id, $subject_id, $total_scaled);

echo json_encode([
    'status' => $inserted > 0 ? 'ok' : 'noop',
    'inserted' => $inserted
]);

