<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ?page=login");
    exit;
}
?>

<div class="jumbotron">
    <h1 class="display-4">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    <p class="lead">This is your dashboard. Your role is: <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong></p>
    <hr class="my-4">
    <p>From here, you can manage the school's report card system based on your permissions.</p>
</div>
