<?php
error_reporting(0);
header('Content-Type: application/json');

if (!file_exists('config.php')) {
    echo json_encode(['success' => false, 'message' => 'Server configuration error']);
    exit;
}

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_POST && isset($_POST['prayer_id'])) {
    try {
        $user_id = $_SESSION['user_id'];
        $prayer_id = sanitize($_POST['prayer_id']);
        
        // Check if already liked
        $check_query = "SELECT id FROM prayer_likes WHERE prayer_id = ? AND user_id = ?";
        $stmt = $db->prepare($check_query);
        $stmt->execute([$prayer_id, $user_id]);
        
        if (!$stmt->fetch()) {
            // Add like
            $insert_query = "INSERT INTO prayer_likes (prayer_id, user_id) VALUES (?, ?)";
            $stmt = $db->prepare($insert_query);
            $stmt->execute([$prayer_id, $user_id]);
            $action = 'liked';
        } else {
            // Remove like
            $delete_query = "DELETE FROM prayer_likes WHERE prayer_id = ? AND user_id = ?";
            $stmt = $db->prepare($delete_query);
            $stmt->execute([$prayer_id, $user_id]);
            $action = 'unliked';
        }
        
        // Get updated like count
        $count_query = "SELECT COUNT(*) as like_count FROM prayer_likes WHERE prayer_id = ?";
        $stmt = $db->prepare($count_query);
        $stmt->execute([$prayer_id]);
        $like_count = $stmt->fetch(PDO::FETCH_ASSOC)['like_count'];
        
        // Check if user currently likes
        $user_liked_query = "SELECT id FROM prayer_likes WHERE prayer_id = ? AND user_id = ?";
        $stmt = $db->prepare($user_liked_query);
        $stmt->execute([$prayer_id, $user_id]);
        $user_liked = $stmt->fetch() ? true : false;
        
        echo json_encode([
            'success' => true,
            'action' => $action,
            'like_count' => $like_count,
            'user_liked' => $user_liked
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>