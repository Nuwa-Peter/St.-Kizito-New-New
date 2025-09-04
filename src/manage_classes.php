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
    switch ($_POST['action']) {
        case 'add_class':
            if (!empty($_POST['class_name'])) {
                if ($classModel->create($_POST['class_name'])) {
                    $message = "Class created successfully.";
                } else {
                    $message = "Error: Class name already exists.";
                }
            }
            break;
        case 'update_class':
            if (!empty($_POST['class_name']) && isset($_POST['class_id'])) {
                if ($classModel->update($_POST['class_id'], $_POST['class_name'])) {
                    $message = "Class updated successfully.";
                } else {
                    $message = "Error: Class name already exists or an error occurred.";
                }
            }
            break;
        case 'delete_class':
            if (isset($_POST['class_id'])) {
                $classModel->delete($_POST['class_id']);
                $message = "Class deleted successfully.";
            }
            break;
        case 'add_stream':
            if (!empty($_POST['stream_name']) && isset($_POST['class_id'])) {
                $streamModel->create($_POST['stream_name'], $_POST['class_id']);
                $message = "Stream created successfully.";
            }
            break;
        case 'update_stream':
            if (!empty($_POST['stream_name']) && isset($_POST['stream_id'])) {
                $streamModel->update($_POST['stream_id'], $_POST['stream_name']);
                $message = "Stream updated successfully.";
            }
            break;
        case 'delete_stream':
            if (isset($_POST['stream_id'])) {
                $streamModel->delete($_POST['stream_id']);
                $message = "Stream deleted successfully.";
            }
            break;
    }
}

$classes = $classModel->getAll();
?>

<h1>Manage School Structure</h1>
<p>Here you can manage classes and their corresponding streams.</p>

<?php if ($message): ?>
<div class="alert alert-info" role="alert"><?php echo $message; ?></div>
<?php endif; ?>

<!-- Add Class Button -->
<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addClassModal">
    <i class="fas fa-plus"></i> Add New Class
</button>

<!-- Class List -->
<div class="row">
    <?php foreach ($classes as $class): ?>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><?php echo htmlspecialchars($class['name']); ?></strong>
                <div>
                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editClassModal" data-class-id="<?php echo $class['id']; ?>" data-class-name="<?php echo htmlspecialchars($class['name']); ?>">Edit</button>
                    <form action="?page=manage_classes" method="post" onsubmit="return confirm('Are you sure you want to delete this class and all its streams?');" style="display: inline;">
                        <input type="hidden" name="action" value="delete_class">
                        <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Streams</h6>
                <ul class="list-group">
                    <?php
                    $streams = $streamModel->getAllByClass($class['id']);
                    if (empty($streams)) {
                        echo '<li class="list-group-item">No streams yet.</li>';
                    }
                    foreach ($streams as $stream):
                    ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo htmlspecialchars($stream['name']); ?>
                        <div>
                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editStreamModal" data-stream-id="<?php echo $stream['id']; ?>" data-stream-name="<?php echo htmlspecialchars($stream['name']); ?>">Edit</button>
                            <form action="?page=manage_classes" method="post" onsubmit="return confirm('Are you sure you want to delete this stream?');" style="display: inline;">
                                <input type="hidden" name="action" value="delete_stream">
                                <input type="hidden" name="stream_id" value="<?php echo $stream['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
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

<!-- Modals -->

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

<!-- Edit Class Modal -->
<div class="modal fade" id="editClassModal" tabindex="-1" aria-labelledby="editClassModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editClassModalLabel">Edit Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="?page=manage_classes" method="post">
                    <input type="hidden" name="action" value="update_class">
                    <input type="hidden" name="class_id" id="edit_class_id">
                    <div class="mb-3">
                        <label for="edit_class_name" class="form-label">Class Name</label>
                        <input type="text" class="form-control" id="edit_class_name" name="class_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Class</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Stream Modal -->
<div class="modal fade" id="editStreamModal" tabindex="-1" aria-labelledby="editStreamModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStreamModalLabel">Edit Stream</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="?page=manage_classes" method="post">
                    <input type="hidden" name="action" value="update_stream">
                    <input type="hidden" name="stream_id" id="edit_stream_id">
                    <div class="mb-3">
                        <label for="edit_stream_name" class="form-label">Stream Name</label>
                        <input type="text" class="form-control" id="edit_stream_name" name="stream_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Stream</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var editClassModal = document.getElementById('editClassModal');
    editClassModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var classId = button.getAttribute('data-class-id');
        var className = button.getAttribute('data-class-name');

        var modalTitle = editClassModal.querySelector('.modal-title');
        var modalBodyInputId = editClassModal.querySelector('#edit_class_id');
        var modalBodyInputName = editClassModal.querySelector('#edit_class_name');

        modalTitle.textContent = 'Edit Class: ' + className;
        modalBodyInputId.value = classId;
        modalBodyInputName.value = className;
    });

    var editStreamModal = document.getElementById('editStreamModal');
    editStreamModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var streamId = button.getAttribute('data-stream-id');
        var streamName = button.getAttribute('data-stream-name');

        var modalTitle = editStreamModal.querySelector('.modal-title');
        var modalBodyInputId = editStreamModal.querySelector('#edit_stream_id');
        var modalBodyInputName = editStreamModal.querySelector('#edit_stream_name');

        modalTitle.textContent = 'Edit Stream: ' + streamName;
        modalBodyInputId.value = streamId;
        modalBodyInputName.value = streamName;
    });
});
</script>
