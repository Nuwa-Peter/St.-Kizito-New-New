<?php
session_start();

// Session timeout logic (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    // Last request was more than 30 minutes ago
    session_unset();     // Unset $_SESSION variable for the run-time
    session_destroy();   // Destroy session data in storage
    header("Location: ?page=login&timeout=1"); // Redirect to login page
    exit;
}
$_SESSION['last_activity'] = time(); // Update last activity time stamp

// Include Composer's autoloader
require_once '../vendor/autoload.php';

// Include configuration and database connection
require_once '../src/config.php';
require_once '../src/database.php';

// Basic router
$page = $_GET['page'] ?? 'dashboard';

// For raw pages like PDF generation or AJAX, we don't include the layout.
$rawPages = ['generate_pdf', 'ajax_get_streams', 'download_marks_template'];
if (in_array($page, $rawPages)) {
    require_once '../src/' . $page . '.php';
} else {
    // Include header
    require_once '../templates/header.php';

    // Page content
    switch ($page) {
        case 'login':
            require_once '../src/login.php';
            break;
        case 'register':
            require_once '../src/register.php';
            break;
        case 'logout':
            require_once '../src/logout.php';
            break;
        case 'manage_users':
            require_once '../src/manage_users.php';
            break;
        case 'manage_classes':
            require_once '../src/manage_classes.php';
            break;
        case 'manage_students':
            require_once '../src/manage_students.php';
            break;
        case 'manage_subjects':
            require_once '../src/manage_subjects.php';
            break;
        case 'manage_reports':
            require_once '../src/manage_reports.php';
            break;
        case 'my_students':
            require_once '../src/my_students.php';
            break;
        case 'enter_marks':
            require_once '../src/enter_marks.php';
            break;
        case 'view_report':
            require_once '../src/view_report.php';
            break;
        case 'dashboard':
        default:
            require_once '../src/dashboard.php';
            break;
    }

    // Include footer
    require_once '../templates/footer.php';
}
?>
