<?php
// Ensure user is logged in and is a superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    echo "<h1>Access Denied</h1><p>You do not have permission to view this page.</p>";
    return;
}

require_once 'User.php';
require_once 'Stream.php';
$userModel = new User($pdo);
$streamModel = new Stream($pdo);

$message = '';
$error = '';

// Handle form submissions for CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $userId = $_POST['user_id'] ?? 0;

    if ($action === 'update_user') {
        $firstName = $_POST['first_name'] ?? '';
        $lastName = $_POST['last_name'] ?? '';
        $username = $_POST['username'] ?? '';
        $role = $_POST['role'] ?? '';
        $streamId = $_POST['stream_id'] ?? null;

        if ($userModel->updateUser($userId, $firstName, $lastName, $username, $role, $streamId)) {
            $message = "User updated successfully.";
        } else {
            $error = "Failed to update user.";
        }
    } elseif ($action === 'delete_user') {
        if ($userId == $_SESSION['user_id']) {
            $error = "You cannot delete your own account.";
        } elseif (!$userModel->deleteUser($userId)) {
            $error = "Failed to delete user. You cannot delete the main superadmin account.";
        } else {
            $message = "User deleted successfully.";
        }
    }
}

$users = $userModel->getAll();
$allStreams = $streamModel->getAllWithClass();
?>

<h1>Manage Users</h1>
<p>Edit user roles and assign teachers to streams.</p>

<?php if ($message): ?><div class="alert alert-success" role="alert"><?php echo $message; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo $error; ?></div><?php endif; ?>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Assigned Stream</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><span class="badge bg-secondary"><?php echo ucfirst($user['role']); ?></span></td>
                    <td><?php echo $user['role'] === 'teacher' ? htmlspecialchars($user['stream_name']) : 'N/A'; ?></td>
                    <td class="text-end">
                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal"
                            data-user-id="<?php echo $user['id']; ?>"
                            data-first-name="<?php echo htmlspecialchars($user['first_name']); ?>"
                            data-last-name="<?php echo htmlspecialchars($user['last_name']); ?>"
                            data-username="<?php echo htmlspecialchars($user['username']); ?>"
                            data-role="<?php echo $user['role']; ?>"
                            data-stream-id="<?php echo $user['stream_id']; ?>">
                            Edit
                        </button>
                        <?php if ($user['id'] != 1 && $user['id'] != $_SESSION['user_id']): // Prevent deleting user 1 and self ?>
                        <form action="?page=manage_users" method="post" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="?page=manage_users" method="post">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" id="edit_last_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" id="edit_username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" id="edit_role" class="form-select" required>
                            <option value="teacher">Teacher</option>
                            <option value="admin">Admin</option>
                            <option value="superadmin">Superadmin</option>
                        </select>
                    </div>
                    <div class="mb-3" id="stream_assignment_div">
                        <label class="form-label">Assign to Stream</label>
                        <select name="stream_id" id="edit_stream_id" class="form-select">
                            <option value="">-- Unassigned --</option>
                            <?php foreach ($allStreams as $stream): ?>
                                <option value="<?php echo $stream['id']; ?>">
                                    <?php echo htmlspecialchars($stream['class_name'] . ' - ' . $stream['stream_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var editUserModal = document.getElementById('editUserModal');
    var roleSelect = editUserModal.querySelector('#edit_role');
    var streamDiv = editUserModal.querySelector('#stream_assignment_div');

    function toggleStreamAssignment() {
        if (roleSelect.value === 'teacher') {
            streamDiv.style.display = 'block';
        } else {
            streamDiv.style.display = 'none';
        }
    }

    roleSelect.addEventListener('change', toggleStreamAssignment);

    editUserModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;

        var userId = button.getAttribute('data-user-id');
        var firstName = button.getAttribute('data-first-name');
        var lastName = button.getAttribute('data-last-name');
        var username = button.getAttribute('data-username');
        var role = button.getAttribute('data-role');
        var streamId = button.getAttribute('data-stream-id');

        var modal = this;
        modal.querySelector('#edit_user_id').value = userId;
        modal.querySelector('#edit_first_name').value = firstName;
        modal.querySelector('#edit_last_name').value = lastName;
        modal.querySelector('#edit_username').value = username;
        modal.querySelector('#edit_role').value = role;
        modal.querySelector('#edit_stream_id').value = streamId;

        toggleStreamAssignment(); // Call on show
    });
});
</script>
