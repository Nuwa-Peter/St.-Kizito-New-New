<?php
session_start();

// Include configuration and database connection
require_once '../src/config.php';
require_once '../src/database.php';

// Basic router
$page = $_GET['page'] ?? 'dashboard';

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
    case 'dashboard':
    default:
        require_once '../src/dashboard.php';
        break;
}

// Include footer
require_once '../templates/footer.php';
?>
