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

if ($_POST && isset($_POST['prayer_id']) && isset($_POST['comment_text'])) {
    try {
        $user_id = $_SESSION['user_id'];
        $prayer_id = sanitize($_POST['prayer_id']);
        $comment_text = sanitize($_POST['comment_text']);
        
        if (empty(trim($comment_text))) {
            echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
            exit;
        }
        
        // Insert comment
        $insert_query = "INSERT INTO prayer_comments (prayer_id, user_id, comment_text) VALUES (?, ?, ?)";
        $stmt = $db->prepare($insert_query);
        $stmt->execute([$prayer_id, $user_id, $comment_text]);
        $comment_id = $db->lastInsertId();
        
        // Get comment with user info
        $comment_query = "SELECT pc.*, u.name as user_name 
                         FROM prayer_comments pc 
                         JOIN users u ON pc.user_id = u.id 
                         WHERE pc.id = ?";
        $stmt = $db->prepare($comment_query);
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get updated comment count
        $count_query = "SELECT COUNT(*) as comment_count FROM prayer_comments WHERE prayer_id = ?";
        $stmt = $db->prepare($count_query);
        $stmt->execute([$prayer_id]);
        $comment_count = $stmt->fetch(PDO::FETCH_ASSOC)['comment_count'];
        
        echo json_encode([
            'success' => true,
            'comment' => $comment,
            'comment_count' => $comment_count
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>