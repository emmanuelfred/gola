<link rel="icon" href="../asset/favicon.png" type="image/png">
<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'golauser');
define('DB_PASS', 'Gola@2024Strong!');
//define('DB_USER', 'root');
//define('DB_PASS', '');
define('DB_NAME', 'goodness_omogo_db');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Timezone
date_default_timezone_set('Africa/Lagos');
?>