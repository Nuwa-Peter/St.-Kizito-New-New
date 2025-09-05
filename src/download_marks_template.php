<?php
// This script generates and downloads an Excel file.
// It should not be wrapped in the standard HTML layout.
session_start();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Basic security check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    die("Access Denied.");
}

require_once 'database.php';
require_once 'ReportBatch.php';
require_once 'Student.php';
require_once 'Subject.php';

$batchId = $_GET['batch_id'] ?? 0;
if (!$batchId) {
    die("Error: No batch selected.");
}

// Fetch data
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$batchModel = new ReportBatch($pdo);
$studentModel = new Student($pdo);
$subjectModel = new Subject($pdo);

$batch = $batchModel->findById($batchId);
if (!$batch) {
    die("Error: Batch not found.");
}

$students = $studentModel->getAllByStream($batch['stream_id']);
$subjects = $subjectModel->getAll();

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// --- Create Header Row ---
$header = ['LIN', 'FirstName', 'LastName', 'OtherName'];
$examTypes = ['BOT', 'MOT', 'EOT'];
foreach ($subjects as $subject) {
    // Replace spaces with a character that is safe for a header name, like a hyphen
    $safeSubjectName = str_replace(' ', '-', $subject['name']);
    foreach ($examTypes as $examType) {
        $header[] = $safeSubjectName . '_' . $examType;
    }
}
$sheet->fromArray($header, NULL, 'A1');

// --- Populate Student Data ---
$rowIndex = 2;
foreach ($students as $student) {
    $sheet->getCell('A' . $rowIndex)->setValue($student['lin']);
    $sheet->getCell('B' . $rowIndex)->setValue($student['first_name']);
    $sheet->getCell('C' . $rowIndex)->setValue($student['last_name']);
    $sheet->getCell('D' . $rowIndex)->setValue($student['other_name']);
    $rowIndex++;
}

// --- Set Headers for Download ---
$fileName = "marks_template_batch_{$batchId}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
