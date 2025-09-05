<?php
// We need to extend the TCPDF class to create a custom Footer.

class CustomTCPDF extends TCPDF {

    private $schoolMotto = '';
    private $gradingScale = [];

    public function setSchoolMotto($motto) {
        $this->schoolMotto = $motto;
    }

    public function setGradingScale($scale) {
        $this->gradingScale = $scale;
    }

    //Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-25);
        // Set font
        $this->SetFont('helvetica', 'I', 8);

        // --- Grading Scale ---
        $html = '<table border="1" cellpadding="2" cellspacing="0" style="font-size: 7pt;">';
        $html .= '<tr><td colspan="2" align="center" bgcolor="#E0E0E0"><b>GRADING SCALE</b></td></tr>';
        foreach($this->gradingScale as $grade => $range) {
            $html .= '<tr><td><b>' . $grade . '</b></td><td>' . $range . '</td></tr>';
        }
        $html .= '</table>';
        $this->writeHTMLCell(80, 0, '', '', $html, 0, 0, false, true, 'L', true);

        // --- Motto and Page Number ---
        $this->SetY(-15);
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(0, 10, $this->schoolMotto, 0, false, 'C', 0, '', 0, false, 'T', 'M');
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }
}

class CustomReportGenerator
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function generateStudentReport($studentId, $batchId)
    {
        // --- 1. Fetch all data required for the template ---
        // (This logic is the same as the old ReportGenerator)
        $studentStmt = $this->pdo->prepare(
            "SELECT s.*, st.name as stream_name, c.name as class_name
             FROM students s
             JOIN streams st ON s.stream_id = st.id
             JOIN classes c ON st.class_id = c.id
             WHERE s.id = ?"
        );
        $studentStmt->execute([$studentId]);
        $student = $studentStmt->fetch(PDO::FETCH_ASSOC);
        if (!$student) {
            throw new Exception("Student with ID {$studentId} not found.");
        }

        $summaryStmt = $this->pdo->prepare("SELECT * FROM student_report_summary WHERE student_id = ? AND batch_id = ?");
        $summaryStmt->execute([$studentId, $batchId]);
        $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);
        if (!$summary) {
            throw new Exception("Report summary not found for this student in this batch. Please calculate the results first.");
        }

        $batchStmt = $this->pdo->prepare("SELECT * FROM report_batch_settings WHERE id = ?");
        $batchStmt->execute([$batchId]);
        $batch = $batchStmt->fetch(PDO::FETCH_ASSOC);
        if (!$batch) {
            throw new Exception("Report batch with ID {$batchId} not found.");
        }

        $scoresStmt = $this->pdo->prepare("SELECT s.*, sub.name as subject_name, sub.teacher_initials FROM scores s JOIN subjects sub ON s.subject_id = sub.id WHERE s.student_id = ? AND s.batch_id = ?");
        $scoresStmt->execute([$studentId, $batchId]);
        $scores = $scoresStmt->fetchAll(PDO::FETCH_ASSOC);

        $subjectsStmt = $this->pdo->query("SELECT * FROM subjects ORDER BY id ASC");
        $subjects = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);

        $totalStudentsStmt = $this->pdo->prepare("SELECT COUNT(id) FROM students WHERE stream_id = ?");
        $totalStudentsStmt->execute([$student['stream_id']]);
        $totalStudentsInStream = $totalStudentsStmt->fetchColumn();

        $scoresBySubject = [];
        foreach ($scores as $score) {
            $scoresBySubject[$score['subject_name']][$score['exam_type']] = $score['marks'];
        }

        // --- 2. Prepare variables for the template ---
        $schoolName = 'ST. KIZITO PREPARATORY SEMINARY RWEBISHURI';
        $schoolMotto = 'MANE NOBISCUM DOMINE';
        $logoPath = __DIR__ . '/../public/images/logo.png';
        $studentPhotoPath = __DIR__ . '/../public/' . $student['profile_photo_path'];
        $gradingScale = [
            'D1' => '90-100', 'D2' => '80-89', 'C3' => '70-79',
            'C4' => '65-69', 'C5' => '60-64', 'C6' => '55-59',
            'P7' => '50-54', 'P8' => '45-49', 'F9' => '0-44'
        ];

        // --- 3. Render the HTML template into a variable ---
        ob_start();
        include __DIR__ . '/../templates/report_card_template.php';
        $html = ob_get_clean();

        // --- 4. Create new PDF document using our custom class ---
        $pdf = new CustomTCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->setSchoolMotto($schoolMotto);
        $pdf->setGradingScale($gradingScale);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($schoolName);
        $pdf->SetTitle('Report Card - ' . $student['first_name'] . ' ' . $student['last_name']);
        $pdf->setPrintHeader(false); // Disable default header
        $pdf->setPrintFooter(true); // Enable our custom footer
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 30); // Set bottom margin for footer
        $pdf->AddPage();

        // --- 5. Write the HTML to the PDF ---
        $pdf->writeHTML($html, true, false, true, false, '');

        // --- 6. Output the PDF ---
        $pdf->Output('report_card_' . $student['id'] . '.pdf', 'I'); // 'I' for inline
    }

    // Helper methods for the template
    public function getGradeFromMark($mark) {
        if ($mark >= 90) return 'D1'; if ($mark >= 80) return 'D2';
        if ($mark >= 70) return 'C3'; if ($mark >= 65) return 'C4';
        if ($mark >= 60) return 'C5'; if ($mark >= 55) return 'C6';
        if ($mark >= 50) return 'P7'; if ($mark >= 45) return 'P8';
        return 'F9';
    }
    public function getRemarkFromGrade($grade) {
        switch ($grade) {
            case 'D1': return 'Excellent'; case 'D2': return 'Very Good';
            case 'C3': return 'Good'; case 'C4': return 'Good';
            case 'C5': return 'Fair'; case 'C6': return 'Fair';
            case 'P7': return 'Pass'; case 'P8': return 'Pass';
            default: return 'Fail';
        }
    }
}
