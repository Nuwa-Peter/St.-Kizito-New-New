<?php

class ReportBatch
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Finds an existing report batch or creates a new one if it doesn't exist.
     *
     * @param int $year The academic year.
     * @param string $term The term (e.g., 'Term 1').
     * @param int $streamId The ID of the stream.
     * @return array|false The report batch data or false on failure.
     */
    public function findOrCreate($year, $term, $streamId)
    {
        // First, try to find the batch
        $stmt = $this->pdo->prepare(
            "SELECT * FROM report_batch_settings WHERE year = ? AND term = ? AND stream_id = ?"
        );
        $stmt->execute([$year, $term, $streamId]);
        $batch = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($batch) {
            return $batch;
        }

        // If not found, create it
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO report_batch_settings (year, term, stream_id) VALUES (?, ?, ?)"
            );
            $stmt->execute([$year, $term, $streamId]);

            // Return the newly created batch
            $id = $this->pdo->lastInsertId();
            return [
                'id' => $id,
                'year' => $year,
                'term' => $term,
                'stream_id' => $streamId,
                'status' => 'pending',
            ];
        } catch (PDOException $e) {
            // Handle potential errors, e.g., race conditions
            return false;
        }
    }

    public function findById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM report_batch_settings WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateDates($batchId, $termEndDate, $nextTermBeginDate)
    {
        // Use null for empty date strings
        $termEndDate = empty($termEndDate) ? null : $termEndDate;
        $nextTermBeginDate = empty($nextTermBeginDate) ? null : $nextTermBeginDate;

        $stmt = $this->pdo->prepare(
            "UPDATE report_batch_settings SET term_end_date = ?, next_term_begin_date = ? WHERE id = ?"
        );
        return $stmt->execute([$termEndDate, $nextTermBeginDate, $batchId]);
    }
}
