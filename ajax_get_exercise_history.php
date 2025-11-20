<?php
require_once 'config.php';

requireLogin();

if (isset($_POST['ajax_get_exercise_history']) && isset($_POST['exercise_id'])) {
    $user_id = $_SESSION['user_id'];
    $exercise_id = $_POST['exercise_id'];
    
    try {
        $history_query = "
            SELECT 
                wl.id as log_id,
                wl.completed_at,
                wls.set_number,
                wls.weight,
                wls.reps
            FROM workout_logs wl
            JOIN workout_log_sets wls ON wl.id = wls.workout_log_id
            WHERE wl.user_id = ? AND wl.exercise_id = ?
            ORDER BY wl.completed_at DESC, wls.set_number ASC
            LIMIT 50
        ";
        
        $stmt = $db->prepare($history_query);
        $stmt->execute([$user_id, $exercise_id]);
        $sets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group sets by workout session
        $sessions = [];
        foreach ($sets as $set) {
            $session_id = $set['log_id'];
            if (!isset($sessions[$session_id])) {
                $sessions[$session_id] = [
                    'log_id' => $session_id,
                    'completed_at' => $set['completed_at'],
                    'sets' => []
                ];
            }
            $sessions[$session_id]['sets'][] = [
                'set_number' => $set['set_number'],
                'weight' => $set['weight'],
                'reps' => $set['reps']
            ];
        }
        
        // Convert to indexed array and limit to 10 sessions
        $sessions = array_slice(array_values($sessions), 0, 10);
        
        echo json_encode([
            'success' => true,
            'history' => $sessions
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>