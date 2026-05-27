<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once '../config/database.php';

// Get admin information
$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];
$admin_name = $_SESSION['admin_name'];
$admin_role = $_SESSION['admin_role'];

// Function to check if user has permission
function hasPermission($required_role) {
    global $admin_role;
    $roles = ['super_admin' => 3, 'admin' => 2, 'teacher' => 1];
    return $roles[$admin_role] >= $roles[$required_role];
}

// Function to log activity
function logActivity($action, $description) {
    global $conn, $admin_id;
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $admin_id, $action, $description, $ip);
    $stmt->execute();
}
?>
