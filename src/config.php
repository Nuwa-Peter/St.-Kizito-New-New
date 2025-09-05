<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'st_kizito_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Base URL
define('BASE_URL', 'http://localhost/stk/public');

// File Upload Paths
define('UPLOADS_DIR', 'uploads/'); // Relative to public folder
define('UPLOADS_PATH', __DIR__ . '/../public/' . UPLOADS_DIR);
define('UPLOADS_URL', BASE_URL . '/' . UPLOADS_DIR);


// Default timezone
date_default_timezone_set('Africa/Kampala');
?>
