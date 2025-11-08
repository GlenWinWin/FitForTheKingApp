<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'fit-for-the-king');
define('DB_USER', 'root');
define('DB_PASS', '');

// Initialize database
require_once 'database.php';
$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        echo "<script>window.location.href = 'auth.php';</script>";
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        echo "<script>window.location.href = '../admin/index.php';</script>";
        exit();
    }
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Get user profile picture for session
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $profile_query = "SELECT profile_picture FROM users WHERE id = ?";
    $stmt = $db->prepare($profile_query);
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['user_profile_picture'] = $user_data['profile_picture'];
}
?>