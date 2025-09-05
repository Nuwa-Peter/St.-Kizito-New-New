<?php
// Ensure user is logged in and is an admin or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    echo "<h1>Access Denied</h1><p>You do not have permission to view this page.</p>";
    return;
}

require_once 'ReportCalculator.php';
require_once 'ReportBatch.php';
require_once 'Class.php';

$calculator = new ReportCalculator($pdo);
$batchModel = new ReportBatch($pdo);
$classModel = new SchoolClass($pdo);

$message = '';
$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $batchId = $_POST['batch_id'] ?? 0;

    if ($_POST['action'] === 'calculate') {
        $result = $calculator->calculateForBatch($batchId);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    } elseif ($_POST['action'] === 'update_dates') {
        $termEndDate = $_POST['term_end_date'];
        $nextTermBeginDate = $_POST['next_term_begin_date'];
        if ($batchModel->updateDates($batchId, $termEndDate, $nextTermBeginDate)) {
            $message = "Report dates updated successfully.";
        } else {
            $error = "Failed to update report dates.";
        }
    } elseif ($_POST['action'] === 'import_marks') {
        if (isset($_FILES['marks_file']) && $_FILES['marks_file']['error'] == UPLOAD_ERR_OK) {
            require_once 'MarksImporter.php';
            $importer = new MarksImporter($pdo);
            $filePath = $_FILES['marks_file']['tmp_name'];
            $result = $importer->import($filePath, $batchId);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = "Import failed: " . $result['message'];
            }
        } else {
            $error = "File upload failed. Please try again.";
        }
    }
}


// Fetch all batches to display.
$stmt = $pdo->query(
    "SELECT rb.*, c.name as class_name, s.name as stream_name
     FROM report_batch_settings rb
     JOIN streams s ON rb.stream_id = s.id
     JOIN classes c ON s.class_id = c.id
     ORDER BY rb.year DESC, c.name, s.name, rb.term"
);
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<h1>Manage Reports</h1>
<p>Manage batches, import marks, calculate results, and generate reports.</p>

<?php if ($message): ?><div class="alert alert-success" role="alert"><?php echo $message; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo $error; ?></div><?php endif; ?>

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Term</th>
                    <th>Class</th>
                    <th>Stream</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($batches)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No report batches found. Marks are entered by teachers, which automatically creates a batch.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($batches as $batch): ?>
                <tr>
                    <td><?php echo htmlspecialchars($batch['year']); ?></td>
                    <td><?php echo htmlspecialchars($batch['term']); ?></td>
                    <td><?php echo htmlspecialchars($batch['class_name']); ?></td>
                    <td><?php echo htmlspecialchars($batch['stream_name']); ?></td>
                    <td>
                        <span class="badge bg-<?php echo ($batch['status'] == 'calculated') ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($batch['status']); ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#manageBatchModal"
                            data-batch-id="<?php echo $batch['id']; ?>"
                            data-term-end-date="<?php echo htmlspecialchars($batch['term_end_date'] ?? ''); ?>"
                            data-next-term-begin-date="<?php echo htmlspecialchars($batch['next_term_begin_date'] ?? ''); ?>">
                            Manage
                        </button>
                        <a href="?page=view_report&batch_id=<?php echo $batch['id']; ?>" class="btn btn-info btn-sm <?php if ($batch['status'] !== 'calculated') echo 'disabled'; ?>" title="Generate after calculating">
                            View Reports
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Manage Batch Modal -->
<div class="modal fade" id="manageBatchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Report Batch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="?page=manage_reports" method="post">
                    <input type="hidden" name="action" value="update_dates">
                    <input type="hidden" name="batch_id" id="modal_batch_id_dates">
                    <div class="mb-3">
                        <label for="term_end_date" class="form-label">This Term Ended On:</label>
                        <input type="date" name="term_end_date" id="modal_term_end_date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="next_term_begin_date" class="form-label">Next Term Begins On:</label>
                        <input type="date" name="next_term_begin_date" id="modal_next_term_begin_date" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-success">Save Dates</button>
                </form>
                <hr>
                <h6>Batch Marks Import</h6>
                <p class="text-muted small">Download the template, fill it with marks, and upload it here. This will overwrite any existing marks for this batch.</p>
                <a href="?page=download_marks_template&batch_id=" id="download_template_link" class="btn btn-secondary mb-3">Download Marks Template (.xlsx)</a>

                <form action="?page=manage_reports" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="import_marks">
                    <input type="hidden" name="batch_id" id="modal_batch_id_import">
                    <div class="input-group">
                        <input type="file" name="marks_file" class="form-control" required accept=".xlsx,.xls">
                        <button class="btn btn-outline-primary" type="submit">Upload</button>
                    </div>
                </form>
                <hr>
                <h6>Calculate Results</h6>
                <p class="text-muted small">This will process all saved marks for this batch (from manual entry or Excel import) and generate the final results. This action cannot be undone.</p>
                <form action="?page=manage_reports" method="post" class="d-grid">
                    <input type="hidden" name="action" value="calculate">
                    <input type="hidden" name="batch_id" id="modal_batch_id_calc">
                    <button type="submit" class="btn btn-primary">Calculate All Results</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var manageBatchModal = document.getElementById('manageBatchModal');
    manageBatchModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;

        var batchId = button.getAttribute('data-batch-id');
        var termEndDate = button.getAttribute('data-term-end-date');
        var nextTermBeginDate = button.getAttribute('data-next-term-begin-date');

        var modal = this;
        modal.querySelector('#modal_batch_id_dates').value = batchId;
        modal.querySelector('#modal_batch_id_calc').value = batchId;
        modal.querySelector('#modal_batch_id_import').value = batchId;
        modal.querySelector('#modal_term_end_date').value = termEndDate;
        modal.querySelector('#modal_next_term_begin_date').value = nextTermBeginDate;
        // Update the download link href
        modal.querySelector('#download_template_link').href = '?page=download_marks_template&batch_id=' + batchId;
    });
});
</script>
