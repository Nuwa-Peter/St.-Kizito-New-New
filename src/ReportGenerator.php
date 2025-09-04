<?php
// Note: The TCPDF library is included via the Composer autoloader.
// Make sure the autoloader is included in the entry script (e.g., public/index.php)

class ReportGenerator
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Generates a PDF report card for a single student.
     *
     * @param int $studentId
     * @param int $batchId
     */
    public function generateStudentReport($studentId, $batchId)
    {
        // --- 1. Fetch all data ---
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

        // Organize scores for easy access
        $scoresBySubject = [];
        foreach ($scores as $score) {
            $scoresBySubject[$score['subject_name']][$score['exam_type']] = $score['marks'];
        }

        $subjectsStmt = $this->pdo->query("SELECT * FROM subjects ORDER BY id ASC");
        $subjects = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);


        // --- 2. Create new PDF document ---
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('St. Kizito Seminary Prep. School');
        $pdf->SetTitle('Report Card - ' . $student['first_name'] . ' ' . $student['last_name']);
        $pdf->SetSubject('Term Report');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);

        // Add a page
        $pdf->AddPage();

        // --- 3. Build the PDF content ---

        // School Header
        $logoPath = '../public/images/logo.png';
        // Check if logo exists to avoid TCPDF error
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 15, 10, 25, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }

        $pdf->SetFont('helvetica', 'B', 16); // Adjusted font size for longer name
        $pdf->Cell(0, 10, 'ST. KIZITO PREPARATORY SEMINARY RWEBISHURI', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, 'END OF ' . strtoupper($batch['term']) . ' ' . $batch['year'] . ' REPORT', 0, 1, 'C');
        $pdf->Ln(15);

        // Student Information
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'STUDENT\'S REPORT', 0, 1, 'C');

        $studentPhoto = '../public/' . ($student['profile_photo_path'] ?? 'assets/images/default_avatar.png');
        if (file_exists($studentPhoto)) {
            $pdf->Image($studentPhoto, 170, 50, 25, 30, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }

        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(30, 7, 'NAME:', 0, 0);
        $pdf->Cell(0, 7, strtoupper($student['first_name'] . ' ' . $student['last_name']), 0, 1);
        $pdf->Cell(30, 7, 'CLASS:', 0, 0);
        $pdf->Cell(0, 7, $student['class_name'] . ' ' . $student['stream_name'], 0, 1);
        $pdf->Ln(5);

        // Scores Table
        $html = '<table border="1" cellpadding="4">
            <tr style="background-color:#D3D3D3; text-align:center; font-weight:bold;">
                <th width="25%">SUBJECT</th>
                <th width="15%">B.O.T (100)</th>
                <th width="15%">M.O.T (100)</th>
                <th width="15%">E.O.T (100)</th>
                <th width="15%">GRADE</th>
                <th width="15%">REMARK</th>
            </tr>';

        foreach ($subjects as $subject) {
            $eot_mark = $scoresBySubject[$subject['name']]['EOT'] ?? null;
            $grade = $eot_mark !== null ? $this->getGradeFromMark($eot_mark) : '-';
            $remark = $eot_mark !== null ? $this->getRemarkFromGrade($grade) : '-';

            $html .= '<tr>
                <td>' . htmlspecialchars($subject['name']) . '</td>
                <td align="center">' . ($scoresBySubject[$subject['name']]['BOT'] ?? '-') . '</td>
                <td align="center">' . ($scoresBySubject[$subject['name']]['MOT'] ?? '-') . '</td>
                <td align="center">' . ($eot_mark ?? '-') . '</td>
                <td align="center">' . $grade . '</td>
                <td>' . $remark . '</td>
            </tr>';
        }
        $html .= '</table>';
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(5);

        // Summary Section
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(40, 7, 'Total Marks:', 0, 0);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(50, 7, $summary['total_marks'] . ' / ' . (count($subjects) * 100), 0, 0);

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(40, 7, 'Aggregates:', 0, 0);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 7, $summary['aggregate_points'], 0, 1);

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(40, 7, 'Division:', 0, 0);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(50, 7, $summary['division'], 0, 0);

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(40, 7, 'Position:', 0, 0);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 7, $summary['position_in_stream'] . ' out of ' . count($this->pdo->query("SELECT id FROM students WHERE stream_id = " . $student['stream_id'])->fetchAll()), 0, 1);
        $pdf->Ln(5);

        // Remarks
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, 'Class Teacher\'s Remarks:', 0, 1);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->MultiCell(0, 10, $summary['class_teacher_remarks'], 0, 'L');
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, 'Headteacher\'s Remarks:', 0, 1);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->MultiCell(0, 10, $summary['headteacher_remarks'], 0, 'L');
        $pdf->Ln(10);

        $pdf->Cell(0, 7, 'Next term begins on: ............................................', 0, 1);

        // --- 4. Output the PDF ---
        $pdf->Output('report_card_' . $student['id'] . '.pdf', 'I');
    }

    private function getGradeFromMark($mark)
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

    private function getRemarkFromGrade($grade)
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
