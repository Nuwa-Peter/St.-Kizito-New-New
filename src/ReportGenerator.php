<?php

class ReportGenerator
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Generates a PDF report card for a single student by rendering an HTML template.
     *
     * @param int $studentId
     * @param int $batchId
     */
    public function generateStudentReport($studentId, $batchId)
    {
        // --- 1. Fetch all data required for the template ---
        $studentStmt = $this->pdo->prepare(
            "SELECT s.*, st.name as stream_name, c.name as class_name
             FROM students s
             JOIN streams st ON s.stream_id = st.id
             JOIN classes c ON st.class_id = c.id
             WHERE s.id = ?"
        );
        $studentStmt->execute([$studentId]);
        $student = $studentStmt->fetch(PDO::FETCH_ASSOC);

        $summaryStmt = $this->pdo->prepare("SELECT * FROM student_report_summary WHERE student_id = ? AND batch_id = ?");
        $summaryStmt->execute([$studentId, $batchId]);
        $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

        $batchStmt = $this->pdo->prepare("SELECT * FROM report_batch_settings WHERE id = ?");
        $batchStmt->execute([$batchId]);
        $batch = $batchStmt->fetch(PDO::FETCH_ASSOC);

        $scoresStmt = $this->pdo->prepare("SELECT s.*, sub.name as subject_name FROM scores s JOIN subjects sub ON s.subject_id = sub.id WHERE s.student_id = ? AND s.batch_id = ?");
        $scoresStmt->execute([$studentId, $batchId]);
        $scores = $scoresStmt->fetchAll(PDO::FETCH_ASSOC);

        $subjectsStmt = $this->pdo->query("SELECT * FROM subjects ORDER BY id ASC");
        $subjects = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);

        $totalStudentsStmt = $this->pdo->prepare("SELECT COUNT(id) FROM students WHERE stream_id = ?");
        $totalStudentsStmt->execute([$student['stream_id']]);
        $totalStudentsInStream = $totalStudentsStmt->fetchColumn();

        // Organize scores for easy access in the template
        $scoresBySubject = [];
        foreach ($scores as $score) {
            $scoresBySubject[$score['subject_name']][$score['exam_type']] = $score['marks'];
        }

        // --- 2. Prepare variables for the template ---
        $schoolName = 'ST. KIZITO PREPARATORY SEMINARY RWEBISHURI';
        $schoolMotto = 'MANE NOBISCUM DOMINE';
        $logoPath = __DIR__ . '/../public/images/logo.png';
        $studentPhotoPath = __DIR__ . '/../public/' . $student['profile_photo_path'];

        // --- 3. Render the HTML template into a variable ---
        ob_start();
        // The 'include' will have access to all variables defined above in this method's scope
        include __DIR__ . '/../templates/report_card_template.php';
        $html = ob_get_clean();

        // --- 4. Create new PDF document ---
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($schoolName);
        $pdf->SetTitle('Report Card - ' . $student['first_name'] . ' ' . $student['last_name']);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 10);
        $pdf->AddPage();

        // --- 5. Write the HTML to the PDF ---
        $pdf->writeHTML($html, true, false, true, false, '');

        // --- 6. Output the PDF ---
        $pdf->Output('report_card_' . $student['id'] . '.pdf', 'I');
    }

    // These helper methods are now used inside the template, so they need to be public
    // or the logic needs to be moved into the template itself.
    // For simplicity, we'll make them public to be accessible via $this-> in the template.
    public function getGradeFromMark($mark)
    {
        if ($mark >= 90) return 'D1';
        if ($mark >= 80) return 'D2';
        if ($mark >= 70) return 'C3';
        if ($mark >= 65) return 'C4';
        if ($mark >= 60) return 'C5';
        if ($mark >= 55) return 'C6';
        if ($mark >= 50) return 'P7';
        if ($mark >= 45) return 'P8';
        return 'F9';
    }

    public function getRemarkFromGrade($grade)
    {
        switch ($grade) {
            case 'D1': return 'Excellent';
            case 'D2': return 'Very Good';
            case 'C3': return 'Good';
            case 'C4': return 'Good';
            case 'C5': return 'Fair';
            case 'C6': return 'Fair';
            case 'P7': return 'Pass';
            case 'P8': return 'Pass';
            default: return 'Fail';
        }
    }
}
