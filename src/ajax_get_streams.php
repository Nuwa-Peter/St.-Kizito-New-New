<?php
// This is a simple endpoint for AJAX requests to fetch streams for a class.
// It should not be wrapped in the standard HTML layout.
session_start();

// Basic security check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access Denied']);
    exit;
}

require_once 'database.php';
require_once 'Stream.php';

$classId = $_GET['class_id'] ?? 0;

if (!$classId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing class_id']);
    exit;
}

$streamModel = new Stream($pdo);
$streams = $streamModel->getAllByClass($classId);

header('Content-Type: application/json');
echo json_encode($streams);
exit;
