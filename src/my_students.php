<?php
// Ensure user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo "<h1>Access Denied</h1><p>You do not have permission to view this page.</p>";
    return;
}

// Fetch teacher's stream ID from their user profile
require_once 'User.php';
$userModel = new User($pdo);
$teacher = $userModel->findById($_SESSION['user_id']);

if (empty($teacher['stream_id'])) {
    echo "<h1>Configuration Error</h1><p>You have not been assigned to a stream. Please contact an administrator.</p>";
    return;
}

require_once 'Student.php';
$studentModel = new Student($pdo);
$students = $studentModel->getAllByStream($teacher['stream_id']);

// Get stream and class name for the header
require_once 'Stream.php';
$streamModel = new Stream($pdo);
$streamInfo = $streamModel->findByIdWithClass($teacher['stream_id']);
$currentStreamName = $streamInfo ? ($streamInfo['class_name'] . ' - ' . $streamInfo['stream_name']) : 'Unknown Stream';

?>

<h1>My Students (<?php echo htmlspecialchars($currentStreamName); ?>)</h1>
<p>This is the official roster for your assigned stream.</p>

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th style="width: 10%;">Photo</th>
                    <th>Name</th>
                    <th>Learner ID (LIN)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="3" class="text-center">There are no students registered in your stream yet.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td><img src="<?php echo htmlspecialchars($student['profile_photo_path']); ?>" alt="Photo" width="60" class="rounded-circle"></td>
                    <td class="align-middle"><?php echo htmlspecialchars($student['first_name'] . ' ' . ($student['other_name'] ?? '') . ' ' . $student['last_name']); ?></td>
                    <td class="align-middle"><?php echo htmlspecialchars($student['lin']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
