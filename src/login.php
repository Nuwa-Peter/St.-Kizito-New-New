<?php
require_once 'User.php';

$user = new User($pdo);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $loggedInUser = $user->login($username, $password);

    if ($loggedInUser) {
        // Set session variables
        $_SESSION['user_id'] = $loggedInUser['id'];
        $_SESSION['username'] = $loggedInUser['username'];
        $_SESSION['role'] = $loggedInUser['role'];
        $_SESSION['last_activity'] = time(); // Set initial activity time

        header("Location: ?page=dashboard");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}

// Display login form
?>
<div class="login-container">
    <img src="images/logo.png" alt="School Logo" class="login-logo">
    <div class="card">
        <div class="card-header text-center">
            <h3>Login</h3>
        </div>
        <div class="card-body">
            <?php if (isset($_GET['timeout'])): ?>
                <div class="alert alert-warning">Your session has expired due to inactivity. Please log in again.</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form action="?page=login" method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
        </div>
        <div class="card-footer text-center">
            <a href="?page=register">Don't have an account? Register</a>
        </div>
    </div>
</div>
