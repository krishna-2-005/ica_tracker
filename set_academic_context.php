<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/academic_context.php';

$action = isset($_POST['action']) ? trim((string)$_POST['action']) : 'set';

if ($action === 'reset') {
    setAcademicTermOverride(null);
    $context = resolveAcademicContext($conn);
    echo json_encode(['status' => 'ok', 'context' => $context]);
    exit;
}

$termId = isset($_POST['term_id']) ? (int)$_POST['term_id'] : 0;
if ($termId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing term identifier']);
    exit;
}

$school = isset($_POST['school']) ? trim((string)$_POST['school']) : '';
$terms = fetchAcademicTerms($conn, $school !== '' ? ['school_name' => $school] : []);
$matched = null;
foreach ($terms as $termRow) {
    if ($termRow['id'] === $termId) {
        $matched = $termRow;
        break;
    }
}

if ($matched === null) {
    // Attempt lookup without school restriction
    if ($school !== '') {
        $fallback = fetchAcademicTerms($conn);
        foreach ($fallback as $termRow) {
            if ($termRow['id'] === $termId) {
                $matched = $termRow;
                break;
            }
        }
    }
    if ($matched === null) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Term not found']);
        exit;
    }
}

setAcademicTermOverride($matched['id']);
$context = resolveAcademicContext($conn, ['school_name' => $school]);
echo json_encode(['status' => 'ok', 'context' => $context]);
