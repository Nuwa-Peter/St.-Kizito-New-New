<?php

require_once 'Student.php';
require_once 'Score.php';
require_once 'Subject.php';
require_once 'ReportSummary.php';
require_once 'RemarkGenerator.php';
require_once 'ReportBatch.php';

class ReportCalculator
{
    private $pdo;
    private $studentModel;
    private $scoreModel;
    private $subjectModel;
    private $summaryModel;
    private $remarkGenerator;
    private $batchModel;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->studentModel = new Student($pdo);
        $this->scoreModel = new Score($pdo);
        $this->subjectModel = new Subject($pdo);
        $this->summaryModel = new ReportSummary($pdo);
        $this->remarkGenerator = new RemarkGenerator();
        $this->batchModel = new ReportBatch($pdo);
    }

    public function calculateForBatch($batchId)
    {
        $batch = $this->batchModel->findById($batchId);
        if (!$batch) {
            return ['success' => false, 'message' => 'Batch not found.'];
        }

        $students = $this->studentModel->getAllByStream($batch['stream_id']);
        $scores = $this->scoreModel->getScoresByBatch($batchId);
        $subjects = $this->subjectModel->getAll();

        $results = [];

        // --- Step 1: Calculate individual student totals and aggregates ---
        foreach ($students as $student) {
            $totalMarks = 0;
            $aggregatePoints = 0;
            $coreSubjectsCount = 0;

            // Assuming EOT is the final mark for aggregation
            $examType = 'EOT';

            foreach ($subjects as $subject) {
                $mark = $scores[$student['id']][$subject['id']][$examType] ?? null;
                if ($mark !== null) {
                    $totalMarks += $mark;
                    // Assuming the 4 core subjects are the first 4 in the DB
                    if ($subject['id'] <= 4) {
                        $aggregatePoints += $this->getGradePoint($mark);
                        $coreSubjectsCount++;
                    }
                }
            }

            // Only calculate division if all core subjects are marked
            $division = ($coreSubjectsCount == 4) ? $this->getDivision($aggregatePoints) : 'X';

            $results[$student['id']] = [
                'total_marks' => $totalMarks,
                'aggregate_points' => $aggregatePoints,
                'division' => $division
            ];
        }

        // --- Step 2: Rank students based on total marks ---
        // Sort students by total_marks descending
        uasort($results, function ($a, $b) {
            return $b['total_marks'] <=> $a['total_marks'];
        });

        $rank = 0;
        $last_score = -1;
        $students_at_rank = 0;
        foreach ($results as $studentId => &$result) {
            if ($result['total_marks'] != $last_score) {
                $rank += $students_at_rank;
                $rank++;
                $students_at_rank = 1;
            } else {
                $students_at_rank++;
            }
            $result['position_in_stream'] = $rank;
            $last_score = $result['total_marks'];
        }
        unset($result); // Unset reference

        // --- Step 3: Generate remarks and save summary for each student ---
        foreach ($students as $student) {
            $studentId = $student['id'];
            if (isset($results[$studentId])) {
                $resultData = $results[$studentId];
                $resultData['class_teacher_remarks'] = $this->remarkGenerator->generateClassTeacherRemark($student['last_name'], $resultData['aggregate_points']);
                $resultData['headteacher_remarks'] = $this->remarkGenerator->generateHeadTeacherRemark($student['last_name'], $resultData['aggregate_points']);

                $this->summaryModel->upsertSummary($studentId, $batchId, $resultData);
            }
        }

        // --- Step 4: Update batch status ---
        $stmt = $this->pdo->prepare("UPDATE report_batch_settings SET status = 'calculated' WHERE id = ?");
        $stmt->execute([$batchId]);

        return ['success' => true, 'message' => 'Calculations completed successfully.'];
    }

    private function getGradePoint($mark)
    {
        if ($mark >= 90) return 1; // D1
        if ($mark >= 80) return 2; // D2
        if ($mark >= 70) return 3; // C3
        if ($mark >= 65) return 4; // C4
        if ($mark >= 60) return 5; // C5
        if ($mark >= 55) return 6; // C6
        if ($mark >= 50) return 7; // P7
        if ($mark >= 45) return 8; // P8
        return 9; // F9
    }

    private function getDivision($aggregate)
    {
        if ($aggregate <= 12) return 'ONE';
        if ($aggregate <= 24) return 'TWO';
        if ($aggregate <= 34) return 'THREE';
        if ($aggregate <= 36) return 'FOUR';
        return 'U';
    }
}
