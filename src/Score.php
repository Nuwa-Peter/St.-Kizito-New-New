<?php

class Score
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Gets all scores for a given batch, formatted for easy use in a form.
     *
     * @param int $batchId The ID of the report batch.
     * @return array The scores, indexed by [student_id][subject_id][exam_type].
     */
    public function getScoresByBatch($batchId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM scores WHERE batch_id = ?");
        $stmt->execute([$batchId]);
        $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formattedScores = [];
        foreach ($scores as $score) {
            $formattedScores[$score['student_id']][$score['subject_id']][$score['exam_type']] = $score['marks'];
        }

        return $formattedScores;
    }

    /**
     * Updates or inserts a score.
     *
     * @param int $studentId
     * @param int $subjectId
     * @param int $batchId
     * @param string $examType
     * @param int|null $marks
     * @return bool
     */
    public function upsertScore($studentId, $subjectId, $batchId, $examType, $marks)
    {
        // Check if a score already exists
        $stmt = $this->pdo->prepare(
            "SELECT id FROM scores WHERE student_id = ? AND subject_id = ? AND batch_id = ? AND exam_type = ?"
        );
        $stmt->execute([$studentId, $subjectId, $batchId, $examType]);
        $existingScoreId = $stmt->fetchColumn();

        // The value from the form can be an empty string, which should be treated as NULL in the database
        $marks = ($marks === '' || $marks === null) ? null : (int)$marks;

        if ($existingScoreId) {
            // Update existing score
            $stmt = $this->pdo->prepare("UPDATE scores SET marks = ? WHERE id = ?");
            return $stmt->execute([$marks, $existingScoreId]);
        } else {
            // Insert new score, but only if marks are not null
            if ($marks !== null) {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO scores (student_id, subject_id, batch_id, exam_type, marks) VALUES (?, ?, ?, ?, ?)"
                );
                return $stmt->execute([$studentId, $subjectId, $batchId, $examType, $marks]);
            }
        }
        return true; // Return true if no action was needed (e.g., inserting a null mark)
    }
}
