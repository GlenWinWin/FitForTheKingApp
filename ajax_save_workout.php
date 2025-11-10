<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

header('Content-Type: application/json');

// Only process AJAX requests
if ($_POST && isset($_POST['ajax_complete_workout'])) {
    $user_id = $_SESSION['user_id'];
    $completed_day_id = (int)$_POST['day_id'];
    
    try {
        // Get user's selected plan
        $plan_query = "SELECT p.*, up.selected_at FROM user_selected_plans up 
                      JOIN workout_plans p ON up.plan_id = p.id 
                      WHERE up.user_id = ?";
        $stmt = $db->prepare($plan_query);
        $stmt->execute([$user_id]);
        $user_plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user_plan) {
            echo json_encode(['success' => false, 'message' => 'Plan not found.']);
            exit();
        }

        // Get the specific day
        $day_query = "SELECT * FROM workout_plan_days WHERE id = ? AND plan_id = ?";
        $stmt = $db->prepare($day_query);
        $stmt->execute([$completed_day_id, $user_plan['id']]);
        $completed_day = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$completed_day) {
            echo json_encode(['success' => false, 'message' => 'Day not found.']);
            exit();
        }

        // Get exercises for this day
        $exercises_query = "SELECT * FROM workout_exercises 
                           WHERE plan_day_id = ? 
                           ORDER BY id";
        $stmt = $db->prepare($exercises_query);
        $stmt->execute([$completed_day_id]);
        $completed_exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Check if we have any valid data to save
        $has_valid_data = false;
        
        if (isset($_POST['sets']) && is_array($_POST['sets'])) {
            foreach ($_POST['sets'] as $exercise_id => $sets) {
                foreach ($sets as $set_data) {
                    if (!empty($set_data['reps']) && $set_data['reps'] > 0) {
                        $has_valid_data = true;
                        break 2;
                    }
                }
            }
        }
        
        if (!$has_valid_data) {
            echo json_encode(['success' => false, 'message' => 'Please enter at least one set of reps to complete your workout.']);
            exit();
        }
        
        $db->beginTransaction();
        $saved_exercises = 0;
        
        foreach ($completed_exercises as $exercise) {
            $exercise_id = $exercise['id'];
            
            if (isset($_POST['sets'][$exercise_id]) && is_array($_POST['sets'][$exercise_id])) {
                $sets_data = $_POST['sets'][$exercise_id];
                
                // Check if this exercise has any valid reps data
                $has_exercise_data = false;
                foreach ($sets_data as $set_data) {
                    if (!empty($set_data['reps']) && intval($set_data['reps']) > 0) {
                        $has_exercise_data = true;
                        break;
                    }
                }
                
                if ($has_exercise_data) {
                    // Create workout log entry
                    $log_query = "INSERT INTO workout_logs (user_id, plan_id, plan_day_id, exercise_id, completed_at) 
                                 VALUES (?, ?, ?, ?, NOW())";
                    $stmt = $db->prepare($log_query);
                    $stmt->execute([
                        $user_id, 
                        $user_plan['id'], 
                        $completed_day_id, 
                        $exercise_id
                    ]);
                    
                    $log_id = $db->lastInsertId();
                    
                    // Save each set
                    $set_number = 1;
                    foreach ($sets_data as $set_data) {
                        if (!empty($set_data['reps']) && intval($set_data['reps']) > 0) {
                            $weight = !empty($set_data['weight']) ? floatval($set_data['weight']) : null;
                            $reps = intval($set_data['reps']);
                            
                            $set_query = "INSERT INTO workout_log_sets (workout_log_id, set_number, reps, weight, unit) 
                                         VALUES (?, ?, ?, ?, ?)";
                            $stmt = $db->prepare($set_query);
                            $stmt->execute([
                                $log_id, 
                                $set_number, 
                                $reps, 
                                $weight, 
                                'kg'
                            ]);
                            
                            $set_number++;
                        }
                    }
                    $saved_exercises++;
                }
            }
        }
        
        $db->commit();
        
        if ($saved_exercises > 0) {
            echo json_encode(['success' => true, 'message' => 'Workout saved successfully!']);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'No workout data was saved.']);
            exit();
        }
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Workout save error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error saving workout data: ' . $e->getMessage()]);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}
?>