<?php
// Ensure user is logged in and is a teacher or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['teacher', 'superadmin'])) {
    echo "<h1>Access Denied</h1><p>You do not have permission to view this page.</p>";
    return;
}

$user_role = $_SESSION['role'];
$teacher_stream_id = null;

if ($user_role === 'teacher') {
    // Fetch teacher's stream ID from their user profile
    require_once 'User.php';
    $userModel = new User($pdo);
    $teacher = $userModel->findById($_SESSION['user_id']);

    if (empty($teacher['stream_id'])) {
        echo "<h1>Configuration Error</h1><p>You have not been assigned to a stream. Please contact an administrator.</p>";
        return;
    }
    $teacher_stream_id = $teacher['stream_id'];
}

require_once 'ReportBatch.php';
require_once 'Score.php';
require_once 'Student.php';
require_once 'Subject.php';
require_once 'Stream.php';

$batchModel = new ReportBatch($pdo);
$scoreModel = new Score($pdo);
$studentModel = new Student($pdo);
$subjectModel = new Subject($pdo);
$streamModel = new Stream($pdo);

$message = '';
$error = '';

$year = $_REQUEST['year'] ?? date('Y');
$term = $_REQUEST['term'] ?? '';
$batch_id = $_REQUEST['batch_id'] ?? null;

// Determine the stream to use
$stream_id_to_use = null;
if ($user_role === 'superadmin') {
    $stream_id_to_use = $_REQUEST['stream_id_selector'] ?? null;
} else { // 'teacher'
    $stream_id_to_use = $teacher_stream_id;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action_batch_id = $_POST['batch_id'] ?? $batch_id;

    if ($_POST['action'] === 'save_marks' && $action_batch_id) {
        $marks = $_POST['marks'];
        $success = true;
        foreach ($marks as $studentId => $subjectMarks) {
            foreach ($subjectMarks as $subjectId => $examMarks) {
                foreach ($examMarks as $examType => $mark) {
                    if (!$scoreModel->upsertScore($studentId, $subjectId, $action_batch_id, $examType, $mark)) {
                        $success = false;
                        $error = "An error occurred while saving marks.";
                    }
                }
            }
        }
        if ($success) {
            $message = "Marks saved successfully!";
        }
    } elseif ($_POST['action'] === 'update_dates' && $action_batch_id) {
        $termEndDate = $_POST['term_end_date'];
        $nextTermBeginDate = $_POST['next_term_begin_date'];
        if ($batchModel->updateDates($action_batch_id, $termEndDate, $nextTermBeginDate)) {
            $message = "Report dates updated successfully.";
            // Re-fetch batch data to show updated dates
            if ($batch) {
                $batch = $batchModel->findById($action_batch_id);
            }
        } else {
            $error = "Failed to update report dates.";
        }
    }
}

$batch = null;
if ($year && $term && $stream_id_to_use) {
    // If a batch ID is passed, use it, otherwise find/create one
    if ($batch_id) {
        $batch = $batchModel->findById($batch_id);
    } else {
        $batch = $batchModel->findOrCreate($year, $term, $stream_id_to_use);
    }
    if ($batch) {
        $batch_id = $batch['id'];
    } else {
        $error = "Could not create or find a report batch for the selected period.";
    }
}

?>

<h1>Enter Student Marks</h1>
<p>Select a year and term to begin entering marks for your assigned stream.</p>

<?php if ($message): ?><div class="alert alert-success" role="alert"><?php echo $message; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo $error; ?></div><?php endif; ?>

<!-- Batch Selection Form -->
<div class="card mb-4">
    <div class="card-body">
        <form action="?page=enter_marks" method="get" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="enter_marks">

            <?php if ($user_role === 'superadmin'): ?>
                <div class="col-md-4">
                    <label for="stream_id_selector" class="form-label">Select Stream</label>
                    <?php
                    $allStreams = $streamModel->getAllWithClass();
                    $hasStreams = !empty($allStreams);
                    ?>
                    <select name="stream_id_selector" id="stream_id_selector" class="form-select" required <?php if (!$hasStreams) echo 'disabled'; ?>>
                        <option value="">
                            <?php echo $hasStreams ? '-- Select a Stream --' : 'No streams found. Please create one.'; ?>
                        </option>
                        <?php foreach ($allStreams as $stream): ?>
                        <option value="<?php echo $stream['id']; ?>" <?php echo (($_GET['stream_id_selector'] ?? '') == $stream['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($stream['class_name'] . ' - ' . $stream['stream_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="year" class="form-label">Academic Year</label>
                    <select name="year" id="year" class="form-select" <?php if (!$hasStreams) echo 'disabled'; ?>>
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo ($year == $y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="term" class="form-label">Term</label>
                    <select name="term" id="term" class="form-select" required <?php if (!$hasStreams) echo 'disabled'; ?>>
                        <option value="">-- Select Term --</option>
                        <option value="Term 1" <?php echo ($term == 'Term 1') ? 'selected' : ''; ?>>Term 1</option>
                        <option value="Term 2" <?php echo ($term == 'Term 2') ? 'selected' : ''; ?>>Term 2</option>
                        <option value="Term 3" <?php echo ($term == 'Term 3') ? 'selected' : ''; ?>>Term 3</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100" <?php if (!$hasStreams) echo 'disabled'; ?>>Load</button>
                </div>
            <?php else: ?>
                <div class="col-md-5">
                    <label for="year" class="form-label">Academic Year</label>
                    <select name="year" id="year" class="form-select">
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo ($year == $y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="term" class="form-label">Term</label>
                    <select name="term" id="term" class="form-select" required>
                        <option value="">-- Select Term --</option>
                        <option value="Term 1" <?php echo ($term == 'Term 1') ? 'selected' : ''; ?>>Term 1</option>
                        <option value="Term 2" <?php echo ($term == 'Term 2') ? 'selected' : ''; ?>>Term 2</option>
                        <option value="Term 3" <?php echo ($term == 'Term 3') ? 'selected' : ''; ?>>Term 3</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Load Sheet</button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>


<?php if ($batch):
    // Fetch data needed for the grid
    $students = $studentModel->getAllByStream($stream_id_to_use);
    $subjects = $subjectModel->getAll();
    $scores = $scoreModel->getScoresByBatch($batch['id']);
    $examTypes = ['BOT', 'MOT', 'EOT'];
?>
<hr>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Marks Entry for <?php echo htmlspecialchars($batch['year'] . ' - ' . $batch['term']); ?></h3>
    <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#reportDatesModal">
        Set Report Dates
    </button>
</div>
<form action="?page=enter_marks" method="post">
    <input type="hidden" name="action" value="save_marks">
    <input type="hidden" name="year" value="<?php echo $year; ?>">
    <input type="hidden" name="term" value="<?php echo $term; ?>">
    <input type="hidden" name="batch_id" value="<?php echo $batch['id']; ?>">

    <div class="table-responsive">
        <table class="table table-bordered table-sm" style="font-size: 0.8em;">
            <thead class="table-dark text-center" style="font-size: 0.9em;">
                <tr>
                    <th rowspan="2" class="align-middle">Student Name</th>
                    <?php foreach ($subjects as $subject): ?>
                        <th colspan="<?php echo count($examTypes); ?>"><?php echo htmlspecialchars($subject['name']); ?></th>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <?php foreach ($subjects as $subject): ?>
                        <?php foreach ($examTypes as $examType): ?>
                            <th><?php echo $examType; ?></th>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                    <?php foreach ($subjects as $subject): ?>
                        <?php foreach ($examTypes as $examType):
                            $currentMark = $scores[$student['id']][$subject['id']][$examType] ?? '';
                        ?>
                        <td>
                            <input type="number" class="form-control form-control-sm"
                                name="marks[<?php echo $student['id']; ?>][<?php echo $subject['id']; ?>][<?php echo $examType; ?>]"
                                value="<?php echo htmlspecialchars($currentMark); ?>"
                                min="0" max="100">
                        </td>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="d-grid">
        <button type="submit" class="btn btn-success btn-lg">Save All Marks</button>
    </div>
</form>

<!-- Set Report Dates Modal -->
<div class="modal fade" id="reportDatesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Set Report Dates</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="?page=enter_marks" method="post">
                    <input type="hidden" name="action" value="update_dates">
                    <input type="hidden" name="batch_id" value="<?php echo $batch['id']; ?>">
                    <input type="hidden" name="year" value="<?php echo $year; ?>">
                    <input type="hidden" name="term" value="<?php echo $term; ?>">
                    <?php if ($user_role === 'superadmin') {
                        echo '<input type="hidden" name="stream_id_selector" value="' . htmlspecialchars($stream_id_to_use) . '">';
                    } ?>
                    <div class="mb-3">
                        <label for="term_end_date" class="form-label">This Term Ended On:</label>
                        <input type="date" name="term_end_date" id="modal_term_end_date" class="form-control" value="<?php echo htmlspecialchars($batch['term_end_date'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="next_term_begin_date" class="form-label">Next Term Begins On:</label>
                        <input type="date" name="next_term_begin_date" id="modal_next_term_begin_date" class="form-control" value="<?php echo htmlspecialchars($batch['next_term_begin_date'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Dates</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>
