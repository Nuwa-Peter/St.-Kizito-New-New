<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require_once 'Score.php';
require_once 'Student.php';

class MarksImporter
{
    private $pdo;
    private $scoreModel;
    private $studentModel;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->scoreModel = new Score($pdo);
        $this->studentModel = new Student($pdo);
    }

    /**
     * Imports marks from an Excel file for a specific batch.
     *
     * @param string $filePath The path to the uploaded Excel file.
     * @param int $batchId The ID of the report batch.
     * @return array An array containing the status.
     */
    public function import($filePath, $batchId)
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            $header = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1', NULL, TRUE, FALSE)[0];
            $dataRows = $sheet->rangeToArray('A2:' . $sheet->getHighestColumn() . $sheet->getHighestRow(), NULL, TRUE, FALSE);

            // Create a map from header name to column index
            $headerMap = array_flip($header);

            $updatedScores = 0;
            $errors = [];

            foreach ($dataRows as $row) {
                $lin = $row[$headerMap['LIN']] ?? null;
                if (!$lin) continue; // Skip rows without a LIN

                // Find student by LIN. A more optimized approach would be to fetch all students in the stream once.
                $stmt = $this->pdo->prepare("SELECT id FROM students WHERE lin = ?");
                $stmt->execute([$lin]);
                $studentId = $stmt->fetchColumn();

                if (!$studentId) {
                    $errors[] = "Student with LIN '{$lin}' not found.";
                    continue;
                }

                // Loop through the rest of the columns to find marks
                foreach ($headerMap as $headerName => $colIndex) {
                    if (in_array($headerName, ['LIN', 'FirstName', 'LastName', 'OtherName'])) continue;

                    // Parse the header e.g., "Mathematics_EOT"
                    $parts = explode('_', $headerName);
                    if (count($parts) !== 2) continue;

                    $subjectName = str_replace('-', ' ', $parts[0]); // In case spaces were replaced
                    $examType = $parts[1];

                    // Find subject ID by name. A more optimized approach would be to fetch all subjects once.
                    $subjStmt = $this->pdo->prepare("SELECT id FROM subjects WHERE name = ?");
                    $subjStmt->execute([$subjectName]);
                    $subjectId = $subjStmt->fetchColumn();

                    if ($subjectId) {
                        $mark = $row[$colIndex];
                        $this->scoreModel->upsertScore($studentId, $subjectId, $batchId, $examType, $mark);
                        $updatedScores++;
                    }
                }
            }

            return ['success' => true, 'message' => "Import complete. Processed {$updatedScores} scores. " . count($errors) . " errors.", 'errors' => $errors];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()];
        }
    }
}
