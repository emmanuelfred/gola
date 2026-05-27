<?php
session_start();

// Log activity before destroying session
if (isset($_SESSION['admin_id'])) {
    require_once '../config/database.php';
    
    $admin_id = $_SESSION['admin_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, 'logout', 'User logged out', ?)");
    $stmt->bind_param("is", $admin_id, $ip);
    $stmt->execute();
}

// Destroy session
session_destroy();

// Redirect to login
header('Location: login.php');
exit;
?>
