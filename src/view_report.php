<?php
// Ensure user is logged in and is an admin or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    echo "<h1>Access Denied</h1><p>You do not have permission to view this page.</p>";
    return;
}

$batchId = $_GET['batch_id'] ?? 0;

if (!$batchId) {
    echo "<h1>Error</h1><p>No batch selected.</p>";
    return;
}

require_once 'ReportBatch.php';
$batchModel = new ReportBatch($pdo);
$batch = $batchModel->findById($batchId);

if (!$batch) {
    echo "<h1>Error</h1><p>Batch not found.</p>";
    return;
}

// Fetch students with summaries for this batch
$stmt = $pdo->prepare(
    "SELECT s.id, s.first_name, s.last_name, s.lin
     FROM students s
     JOIN student_report_summary srs ON s.id = srs.student_id
     WHERE srs.batch_id = ?
     ORDER BY s.last_name, s.first_name"
);
$stmt->execute([$batchId]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<h1>View Reports for <?php echo htmlspecialchars($batch['year'] . ' - ' . $batch['term']); ?></h1>
<p>Download individual report cards for students in this batch.</p>

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>LIN</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="3" class="text-center">No student reports have been calculated for this batch yet.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($student['lin']); ?></td>
                    <td class="text-end">
                        <a href="?page=generate_pdf&student_id=<?php echo $student['id']; ?>&batch_id=<?php echo $batchId; ?>"
                           class="btn btn-success btn-sm"
                           target="_blank">Download Report</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<br>
<a href="?page=manage_reports" class="btn btn-secondary">Back to All Batches</a>
