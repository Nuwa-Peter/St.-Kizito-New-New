<?php
// This script generates the PDF and outputs it directly to the browser.
// It should not be wrapped in the standard HTML layout.

// Basic security check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin', 'teacher'])) {
    die("Access Denied.");
}

$studentId = $_GET['student_id'] ?? 0;
$batchId = $_GET['batch_id'] ?? 0;

if (!$studentId || !$batchId) {
    die("Error: Missing required parameters.");
}

require_once 'ReportGenerator.php';

try {
    $reportGenerator = new ReportGenerator($pdo);
    $reportGenerator->generateStudentReport($studentId, $batchId);
} catch (Exception $e) {
    // Log the error if a logging system was in place
    die("An error occurred while generating the report. Please check the data and try again.");
}

// The generateStudentReport method handles the exit.
exit;
