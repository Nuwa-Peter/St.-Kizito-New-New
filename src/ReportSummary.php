<?php

class ReportSummary
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Updates or inserts a student's report summary.
     *
     * @param int $studentId
     * @param int $batchId
     * @param array $summaryData
     * @return bool
     */
    public function upsertSummary($studentId, $batchId, $summaryData)
    {
        // Check if a summary already exists
        $stmt = $this->pdo->prepare(
            "SELECT id FROM student_report_summary WHERE student_id = ? AND batch_id = ?"
        );
        $stmt->execute([$studentId, $batchId]);
        $existingSummaryId = $stmt->fetchColumn();

        if ($existingSummaryId) {
            // Update existing summary
            $sql = "UPDATE student_report_summary SET total_marks = ?, aggregate_points = ?, division = ?, position_in_stream = ?, class_teacher_remarks = ?, headteacher_remarks = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $summaryData['total_marks'],
                $summaryData['aggregate_points'],
                $summaryData['division'],
                $summaryData['position_in_stream'],
                $summaryData['class_teacher_remarks'],
                $summaryData['headteacher_remarks'],
                $existingSummaryId
            ]);
        } else {
            // Insert new summary
            $sql = "INSERT INTO student_report_summary (student_id, batch_id, total_marks, aggregate_points, division, position_in_stream, class_teacher_remarks, headteacher_remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $studentId,
                $batchId,
                $summaryData['total_marks'],
                $summaryData['aggregate_points'],
                $summaryData['division'],
                $summaryData['position_in_stream'],
                $summaryData['class_teacher_remarks'],
                $summaryData['headteacher_remarks']
            ]);
        }
    }

    public function getSummaryForBatch($batchId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM student_report_summary WHERE batch_id = ?");
        $stmt->execute([$batchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
