<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Kizito Seminary Preparatory School</title>
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2e7d32">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Croppie CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="?page=dashboard">
            <img src="images/logo.png" alt="Logo" width="30" height="30" class="d-inline-block align-top me-2">
            ST. KIZITO PREPARATORY SEMINARY
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
                <!-- Install App Button -->
                <li class="nav-item">
                    <button id="installApp" class="btn btn-warning ms-lg-2 d-none">Install App</button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
    // Service Worker Registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('sw.js')
                .then(reg => console.log('Service Worker registered', reg))
                .catch(err => console.log('Service Worker not registered', err));
        });
    }

    // PWA Install Prompt
    let deferredPrompt;
    const installBtn = document.getElementById('installApp');

    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent Chrome 67 and earlier from automatically showing the prompt
        e.preventDefault();
        // Stash the event so it can be triggered later.
        deferredPrompt = e;
        // Update UI to notify the user they can add to home screen
        installBtn.classList.remove('d-none');

        installBtn.addEventListener('click', (e) => {
            // hide our user interface that shows our A2HS button
            installBtn.classList.add('d-none');
            // Show the prompt
            deferredPrompt.prompt();
            // Wait for the user to respond to the prompt
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('User accepted the A2HS prompt');
                } else {
                    console.log('User dismissed the A2HS prompt');
                }
                deferredPrompt = null;
            });
        });
    });
</script>

<main class="flex-shrink-0">
    <div class="container mt-4">
