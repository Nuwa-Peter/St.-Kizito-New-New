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
    $batchId = $_POST['batch_id'];

    if ($_POST['action'] === 'calculate') {
        $result = $calculator->calculateForBatch($batchId);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}


// Fetch all batches to display. A more optimized query could be used here.
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
<p>Calculate results and generate reports for each batch.</p>

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
                        <form action="?page=manage_reports" method="post" style="display:inline;" onsubmit="return confirm('Are you sure? This will re-calculate all results for this batch.');">
                            <input type="hidden" name="action" value="calculate">
                            <input type="hidden" name="batch_id" value="<?php echo $batch['id']; ?>">
                            <button type="submit" class="btn btn-primary btn-sm">Calculate Results</button>
                        </form>
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
