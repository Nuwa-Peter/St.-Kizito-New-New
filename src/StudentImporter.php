<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require_once 'Student.php';

class StudentImporter
{
    private $pdo;
    private $studentModel;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->studentModel = new Student($pdo);
    }

    /**
     * Imports students from an Excel file into a specific stream.
     *
     * @param string $filePath The path to the uploaded Excel file.
     * @param int $streamId The ID of the stream to import students into.
     * @return array An array containing the status, e.g., ['success' => true, 'imported' => 10, 'errors' => 0].
     */
    public function import($filePath, $streamId)
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Get the highest row number
            $highestRow = $sheet->getHighestRow();

            $importedCount = 0;
            $errorCount = 0;

            // Start from row 2 to skip the header row
            for ($row = 2; $row <= $highestRow; $row++) {
                $firstName = $sheet->getCell('A' . $row)->getValue();
                $lastName = $sheet->getCell('B' . $row)->getValue();
                $otherName = $sheet->getCell('C' . $row)->getValue();
                $lin = $sheet->getCell('D' . $row)->getValue();

                // Skip empty rows
                if (empty($firstName) && empty($lastName)) {
                    continue;
                }

                // A very basic validation
                if (empty($firstName) || empty($lastName)) {
                    $errorCount++;
                    continue;
                }

                // Create the student
                // The create method in Student.php handles the default photo
                if ($this->studentModel->create($firstName, $lastName, $otherName, $lin, $streamId)) {
                    $importedCount++;
                } else {
                    // This could happen if, for example, the LIN is a unique key and there's a duplicate
                    $errorCount++;
                }
            }

            return ['success' => true, 'imported' => $importedCount, 'errors' => $errorCount];

        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            return ['success' => false, 'message' => 'Error reading the spreadsheet file.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()];
        }
    }
}
