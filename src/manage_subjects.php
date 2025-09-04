<?php
// Ensure user is logged in and is an admin or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    echo "<h1>Access Denied</h1><p>You do not have permission to view this page.</p>";
    return;
}

require_once 'Subject.php';
$subjectModel = new Subject($pdo);

$message = '';
$error = '';

// Handle form submissions for CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $subjectName = $_POST['subject_name'] ?? '';
    $subjectId = $_POST['subject_id'] ?? 0;

    switch ($action) {
        case 'add_subject':
            if (!empty($subjectName)) {
                if ($subjectModel->create($subjectName)) {
                    $message = "Subject created successfully.";
                } else {
                    $error = "Failed to create subject. It may already exist.";
                }
            } else {
                $error = "Subject name cannot be empty.";
            }
            break;

        case 'update_subject':
            if (!empty($subjectName) && !empty($subjectId)) {
                if ($subjectModel->update($subjectId, $subjectName)) {
                    $message = "Subject updated successfully.";
                } else {
                    $error = "Failed to update subject.";
                }
            } else {
                $error = "Invalid data for updating subject.";
            }
            break;

        case 'delete_subject':
            if (!empty($subjectId)) {
                if ($subjectModel->delete($subjectId)) {
                    $message = "Subject deleted successfully.";
                } else {
                    $error = "Failed to delete subject.";
                }
            } else {
                $error = "Invalid subject ID for deletion.";
            }
            break;
    }
}

$subjects = $subjectModel->getAll();
?>

<h1>Manage Subjects</h1>
<p>Add, edit, or remove academic subjects from the system.</p>

<?php if ($message): ?><div class="alert alert-success" role="alert"><?php echo $message; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo $error; ?></div><?php endif; ?>

<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
    Add New Subject
</button>

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Subject Name</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subjects as $subject): ?>
                <tr>
                    <td><?php echo htmlspecialchars($subject['name']); ?></td>
                    <td class="text-end">
                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editSubjectModal"
                            data-subject-id="<?php echo $subject['id']; ?>"
                            data-subject-name="<?php echo htmlspecialchars($subject['name']); ?>">
                            Edit
                        </button>
                        <form action="?page=manage_subjects" method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this subject?');">
                            <input type="hidden" name="action" value="delete_subject">
                            <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="?page=manage_subjects" method="post">
                    <input type="hidden" name="action" value="add_subject">
                    <div class="mb-3">
                        <label class="form-label">Subject Name</label>
                        <input type="text" name="subject_name" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Subject</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Subject Modal -->
<div class="modal fade" id="editSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="?page=manage_subjects" method="post">
                    <input type="hidden" name="action" value="update_subject">
                    <input type="hidden" name="subject_id" id="edit_subject_id">
                    <div class="mb-3">
                        <label class="form-label">Subject Name</label>
                        <input type="text" name="subject_name" id="edit_subject_name" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Subject</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var editSubjectModal = document.getElementById('editSubjectModal');
    editSubjectModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var subjectId = button.getAttribute('data-subject-id');
        var subjectName = button.getAttribute('data-subject-name');

        var modal = this;
        modal.querySelector('#edit_subject_id').value = subjectId;
        modal.querySelector('#edit_subject_name').value = subjectName;
    });
});
</script>
