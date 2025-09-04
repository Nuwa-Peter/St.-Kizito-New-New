<?php
// Ensure user is logged in and is an admin or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    echo "<h1>Access Denied</h1>";
    echo "<p>You do not have permission to view this page.</p>";
    return;
}

require_once 'Class.php';
require_once 'Stream.php';

$classModel = new SchoolClass($pdo);
$streamModel = new Stream($pdo);

$message = '';

// Handle Class Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_class' && !empty($_POST['class_name'])) {
        if ($classModel->create($_POST['class_name'])) {
            $message = "Class created successfully.";
        } else {
            $message = "Error: Class name already exists.";
        }
    }

    if ($_POST['action'] === 'delete_class' && isset($_POST['class_id'])) {
        $classModel->delete($_POST['class_id']);
        $message = "Class deleted successfully.";
    }

    // Handle Stream Actions
    if ($_POST['action'] === 'add_stream' && !empty($_POST['stream_name']) && isset($_POST['class_id'])) {
        $streamModel->create($_POST['stream_name'], $_POST['class_id']);
        $message = "Stream created successfully.";
    }

    if ($_POST['action'] === 'delete_stream' && isset($_POST['stream_id'])) {
        $streamModel->delete($_POST['stream_id']);
        $message = "Stream deleted successfully.";
    }
}

$classes = $classModel->getAll();

?>

<h1>Manage School Structure</h1>
<p>Here you can manage classes and their corresponding streams.</p>

<?php if ($message): ?>
<div class="alert alert-info"><?php echo $message; ?></div>
<?php endif; ?>

<!-- Add Class Button -->
<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addClassModal">
    Add New Class
</button>

<!-- Class List -->
<div class="row">
    <?php foreach ($classes as $class): ?>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><?php echo htmlspecialchars($class['name']); ?></strong>
                <form action="?page=manage_classes" method="post" onsubmit="return confirm('Are you sure you want to delete this class and all its streams?');" style="display: inline;">
                    <input type="hidden" name="action" value="delete_class">
                    <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Delete Class</button>
                </form>
            </div>
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Streams</h6>
                <ul class="list-group">
                    <?php
                    $streams = $streamModel->getAllByClass($class['id']);
                    foreach ($streams as $stream):
                    ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo htmlspecialchars($stream['name']); ?>
                        <form action="?page=manage_classes" method="post" onsubmit="return confirm('Are you sure you want to delete this stream?');" style="display: inline;">
                            <input type="hidden" name="action" value="delete_stream">
                            <input type="hidden" name="stream_id" value="<?php echo $stream['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="card-footer">
                <form action="?page=manage_classes" method="post" class="row g-2">
                    <input type="hidden" name="action" value="add_stream">
                    <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                    <div class="col-auto">
                        <input type="text" class="form-control" name="stream_name" placeholder="New Stream Name" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-secondary btn-sm">Add Stream</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-labelledby="addClassModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addClassModalLabel">Add New Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="?page=manage_classes" method="post">
                    <input type="hidden" name="action" value="add_class">
                    <div class="mb-3">
                        <label for="class_name" class="form-label">Class Name</label>
                        <input type="text" class="form-control" id="class_name" name="class_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Class</button>
                </form>
            </div>
        </div>
    </div>
</div>
