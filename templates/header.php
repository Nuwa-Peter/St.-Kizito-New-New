<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Kizito Seminary Preparatory School</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Croppie CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="?page=dashboard">
            <img src="images/logo.png" alt="Logo" width="30" height="30" class="d-inline-block align-top me-2">
            ST. KIZITO PREPARATORY SEMINARY RWEBISHURI
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="?page=dashboard">Dashboard</a>
                    </li>
                    <?php
                        $role = $_SESSION['role'];
                        if ($role == 'superadmin') {
                            echo '<li class="nav-item"><a class="nav-link" href="?page=manage_users">Users</a></li>';
                            echo '<li class="nav-item"><a class="nav-link" href="?page=manage_classes">Classes</a></li>';
                            echo '<li class="nav-item"><a class="nav-link" href="?page=manage_students">Students</a></li>';
                            echo '<li class="nav-item"><a class="nav-link" href="?page=manage_subjects">Subjects</a></li>';
                            echo '<li class="nav-item"><a class="nav-link" href="?page=enter_marks">Enter Marks</a></li>';
                            echo '<li class="nav-item"><a class="nav-link" href="?page=manage_reports">Reports</a></li>';
                        } elseif ($role == 'admin') {
                            echo '<li class="nav-item"><a class="nav-link" href="?page=manage_classes">Classes</a></li>';
                            echo '<li class="nav-item"><a class="nav-link" href="?page=manage_students">Students</a></li>';
                            echo '<li class="nav-item"><a class="nav-link" href="?page=manage_subjects">Subjects</a></li>';
                            echo '<li class="nav-item"><a class="nav-link" href="?page=manage_reports">Reports</a></li>';
                        } elseif ($role == 'teacher') {
                            echo '<li class="nav-item"><a class="nav-link" href="?page=my_students">My Students</a></li>';
                            echo '<li class="nav-item"><a class="nav-link" href="?page=enter_marks">Enter Marks</a></li>';
                        }
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="?page=logout">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="?page=login">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?page=register">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="flex-shrink-0">
    <div class="container mt-4">
