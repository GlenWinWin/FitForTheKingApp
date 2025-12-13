<?php
$pageTitle = 'Workout Plan';
require_once 'header.php';
requireLogin();

// Add workout page class to body
echo '<script>document.body.classList.add("workout-page");</script>';

$user_id = $_SESSION['user_id'];

// Get user's selected plan
$plan_query = "SELECT p.*, up.selected_at FROM user_selected_plans up 
              JOIN workout_plans p ON up.plan_id = p.id 
              WHERE up.user_id = ?";
$stmt = $db->prepare($plan_query);
$stmt->execute([$user_id]);
$user_plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user_plan) {
    echo "<script>window.location.href = 'workouts.php';</script>";
    exit();
}

// Calculate current day in plan
$selected_at = new DateTime($user_plan['selected_at']);
$today = new DateTime();
$days_since_start = $selected_at->diff($today)->days;

// Get all days in plan
$days_query = 'SELECT * FROM workout_plan_days WHERE plan_id = ? ORDER BY day_order';
$stmt = $db->prepare($days_query);
$stmt->execute([$user_plan['id']]);
$all_days = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_days = count($all_days);
$current_day_index = ($days_since_start % $total_days) + 1;

// Get exercises for ALL days
$all_exercises = [];
foreach ($all_days as $day) {
    $exercises_query = "SELECT * FROM workout_exercises 
                       WHERE plan_day_id = ? 
                       ORDER BY id";
    $stmt = $db->prepare($exercises_query);
    $stmt->execute([$day['id']]);
    $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $all_exercises[$day['day_order']] = $exercises;
}

// Get last workout data for progressive overload - OPTIMIZED QUERY
$last_workout_data = [];

// Get all exercise IDs for this plan
$exercise_ids = [];
foreach ($all_exercises as $exercises) {
    foreach ($exercises as $exercise) {
        $exercise_ids[] = $exercise['id'];
    }
}

if (!empty($exercise_ids)) {
    $placeholders = str_repeat('?,', count($exercise_ids) - 1) . '?';
    
    $last_workout_query = "
        SELECT wl.exercise_id, wls.set_number, wls.weight, wls.reps, wl.completed_at
        FROM workout_logs wl
        JOIN workout_log_sets wls ON wl.id = wls.workout_log_id
        WHERE wl.user_id = ? 
        AND wl.exercise_id IN ($placeholders)
        AND wl.completed_at = (
            SELECT MAX(completed_at) 
            FROM workout_logs 
            WHERE user_id = wl.user_id 
            AND exercise_id = wl.exercise_id
        )
        ORDER BY wl.exercise_id, wls.set_number
    ";
    
    $stmt = $db->prepare($last_workout_query);
    $stmt->execute(array_merge([$user_id], $exercise_ids));
    $last_workout_sets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize by exercise ID and set number
    foreach ($last_workout_sets as $set) {
        $last_workout_data[$set['exercise_id']][$set['set_number']] = $set;
    }
}
?>

<style>
/* ===== NATIVE APP RESET ===== */
:root {
    --safe-area-inset-top: env(safe-area-inset-top, 20px);
    --safe-area-inset-bottom: env(safe-area-inset-bottom, 20px);
}

* {
    box-sizing: border-box;
    -webkit-tap-highlight-color: transparent;
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    user-select: none;
}

html {
    height: 100%;
    overflow: auto;
    touch-action: manipulation;
}

body {
    margin: 0;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Helvetica Neue', sans-serif;
    height: 100vh;
    height: -webkit-fill-available;
    overflow: auto;
    /* Keep your original background */
    background: var(--background, #F2F2F7);
    color: var(--text, #000000);
}

input, button, textarea {
    font-family: inherit;
    -webkit-appearance: none;
    appearance: none;
}

input {
    -webkit-user-select: text;
    user-select: text;
}

/* Disable zoom and double-tap */
* {
    touch-action: pan-y;
}

input, textarea {
    touch-action: pan-y;
}

/* ===== APP CONTAINER ===== */
.app-container {
    height: 100vh;
    height: -webkit-fill-available;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
    /* Keep your gradient background */
}

/* ===== STATUS BAR ===== */
.status-bar {
    height: calc(var(--safe-area-inset-top) + 44px);
    padding-top: var(--safe-area-inset-top);
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 0.5px solid rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-left: 16px;
    padding-right: 16px;
    flex-shrink: 0;
    z-index: 1000;
}

.status-title {
    font-size: 17px;
    font-weight: 600;
    color: var(--text, #000000);
}

.status-actions {
    display: flex;
    gap: 16px;
}

.status-action {
    background: none;
    border: none;
    color: var(--accent, #667eea);
    font-size: 17px;
    font-weight: 400;
    padding: 8px;
    min-height: 44px;
    min-width: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ===== MAIN CONTENT ===== */
.main-content {
    flex: 1;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
    padding: 0;
    padding-bottom: 100px; /* Space for complete button */
    /* Glass morphism effect */
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

/* Hide scrollbar but keep functionality */
.main-content::-webkit-scrollbar {
    display: none;
}

/* ===== WORKOUT HEADER ===== */
.workout-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    padding: 20px 16px;
    border-bottom: 0.5px solid rgba(0, 0, 0, 0.1);
}

.workout-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 4px 0;
    line-height: 1.2;
    color: var(--text, #000000);
    background: var(--gradient-accent, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.workout-subtitle {
    font-size: 15px;
    color: var(--light-text, #666666);
    margin: 0 0 16px 0;
    line-height: 1.4;
}

.workout-stats {
    display: flex;
    gap: 16px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    color: var(--light-text, #666666);
}

.stat-icon {
    font-size: 16px;
    color: var(--accent, #667eea);
}

/* ===== DAY PICKER ===== */
.day-picker {
    display: flex;
    gap: 8px;
    padding: 16px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-bottom: 0.5px solid rgba(0, 0, 0, 0.1);
}

.day-picker::-webkit-scrollbar {
    display: none;
}

.day-pill {
    padding: 12px 20px;
    background: rgba(255, 255, 255, 0.9);
    border: 0.5px solid rgba(0, 0, 0, 0.1);
    border-radius: 20px;
    font-size: 15px;
    font-weight: 500;
    color: var(--text, #000000);
    white-space: nowrap;
    flex-shrink: 0;
    transition: all 0.2s ease;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.day-pill.active {
    background: var(--gradient-accent, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
    color: white;
    border-color: transparent;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.day-pill.current {
    position: relative;
}

.day-pill.current::after {
    content: '';
    position: absolute;
    top: -2px;
    right: -2px;
    width: 8px;
    height: 8px;
    background: var(--success, #4CAF50);
    border-radius: 4px;
    border: 2px solid white;
}

/* ===== DAY CONTENT ===== */
.day-content {
    display: none;
    animation: slideIn 0.3s ease;
}

.day-content.active {
    display: block;
}

.day-info {
    padding: 20px 16px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-bottom: 0.5px solid rgba(0, 0, 0, 0.1);
}

.day-title {
    font-size: 22px;
    font-weight: 700;
    margin: 0 0 8px 0;
    color: var(--text, #000000);
}

.day-description {
    font-size: 15px;
    color: var(--light-text, #666666);
    margin: 0 0 16px 0;
    line-height: 1.4;
}

/* ===== EXERCISE LIST ===== */
.exercise-list {
    padding: 0;
    margin-bottom: 20px;
}

.exercise-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    margin-bottom: 8px;
    border-bottom: 0.5px solid rgba(0, 0, 0, 0.1);
}

.exercise-header {
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 60px;
}

.exercise-title {
    font-size: 17px;
    font-weight: 600;
    color: var(--text, #000000);
    margin: 0;
    flex: 1;
}

.exercise-number {
    color: var(--accent, #667eea);
    margin-right: 8px;
}

.exercise-toggle {
    width: 30px;
    height: 30px;
    border-radius: 15px;
    background: rgba(0, 0, 0, 0.05);
    border: none;
    color: var(--light-text, #666666);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.exercise-card.open .exercise-toggle {
    transform: rotate(180deg);
    background: var(--gradient-accent, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
    color: white;
}

/* ===== EXERCISE CONTENT ===== */
.exercise-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.exercise-card.open .exercise-content {
    max-height: 1000px;
}

.exercise-media {
    padding: 0 16px 16px;
}

.video-container {
    position: relative;
    width: 100%;
    padding-bottom: 56.25%;
    border-radius: 12px;
    overflow: hidden;
    background: #000;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.video-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: none;
}

.exercise-notes {
    padding: 12px;
    background: rgba(102, 126, 234, 0.05);
    border-left: 3px solid var(--accent, #667eea);
    border-radius: 0 8px 8px 0;
    margin-top: 12px;
    font-size: 14px;
    color: var(--text, #000000);
    line-height: 1.4;
}

/* ===== SETS TABLE ===== */
.sets-section {
    padding: 16px;
}

.set-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 0.5px solid rgba(0, 0, 0, 0.1);
    min-height: 60px;
}

.set-row:last-child {
    border-bottom: none;
}

.set-number {
    width: 40px;
    height: 40px;
    border-radius: 20px;
    background: rgba(102, 126, 234, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    font-weight: 600;
    color: var(--accent, #667eea);
    flex-shrink: 0;
}

.set-inputs {
    display: flex;
    gap: 8px;
    flex: 1;
}

.input-group {
    flex: 1;
}

.input-label {
    display: block;
    font-size: 12px;
    color: var(--light-text, #666666);
    margin-bottom: 4px;
}

.native-input {
    width: 100%;
    padding: 10px 12px;
    background: rgba(255, 255, 255, 0.9);
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 17px;
    color: var(--text, #000000);
    text-align: center;
    -webkit-appearance: none;
    transition: all 0.2s ease;
}

.native-input:focus {
    border-color: var(--accent, #667eea);
    outline: none;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.native-input.progress-up {
    border-color: #4CAF50;
    background: rgba(76, 175, 80, 0.05);
}

.native-input.progress-down {
    border-color: #f44336;
    background: rgba(244, 67, 54, 0.05);
}

.set-timer {
    width: 40px;
    height: 40px;
    border-radius: 20px;
    background: var(--gradient-accent, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
    border: none;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(102, 126, 234, 0.3);
}

.set-timer i {
    font-size: 16px;
}

/* ===== HISTORY BUTTON ===== */
.history-button {
    width: 100%;
    padding: 16px;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 0;
    font-size: 15px;
    color: var(--accent, #667eea);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    border-top: 0.5px solid rgba(0, 0, 0, 0.1);
}

/* ===== COMPLETE BUTTON - FIXED FOR FOOTER ===== */
.complete-section {
    position: fixed;
    bottom: 100px; /* Position above footer (approx 49px nav + 20px buffer) */
    left: 0;
    right: 0;
    padding: 12px 16px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-top: 0.5px solid rgba(0, 0, 0, 0.1);
    z-index: 998;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
}

.complete-button {
    width: 100%;
    padding: 18px;
    background: var(--gradient-accent, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
    border: none;
    border-radius: 12px;
    color: white;
    font-size: 17px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 50px;
    transition: all 0.2s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.complete-button:active {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.complete-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
}

/* Adjust for smaller screens */
@media (max-height: 700px) {
    .complete-section {
        bottom: 90px;
    }
}

/* ===== NATIVE MODALS ===== */
.native-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: none;
    align-items: flex-end;
    z-index: 1000;
}

.modal-content {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
    width: 100%;
    max-height: 80vh;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    animation: slideUp 0.3s ease;
}

.modal-header {
    padding: 16px;
    border-bottom: 0.5px solid rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-title {
    font-size: 17px;
    font-weight: 600;
    margin: 0;
    color: var(--text, #000000);
}

.modal-close {
    background: none;
    border: none;
    color: var(--accent, #667eea);
    font-size: 17px;
    padding: 8px;
    min-height: 44px;
    min-width: 44px;
}

/* ===== TIMER MODAL ===== */
.timer-display {
    font-size: 48px;
    font-weight: 700;
    text-align: center;
    padding: 40px 20px;
    font-family: 'Courier New', monospace;
    color: var(--text, #000000);
    letter-spacing: 2px;
}

.timer-controls {
    display: flex;
    gap: 12px;
    padding: 0 16px 20px;
}

.timer-button {
    flex: 1;
    padding: 18px;
    border-radius: 12px;
    border: none;
    font-size: 17px;
    font-weight: 600;
    min-height: 50px;
    transition: all 0.2s ease;
}

.timer-primary {
    background: var(--gradient-accent, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.timer-primary:active {
    transform: translateY(-2px);
}

.timer-secondary {
    background: rgba(255, 255, 255, 0.9);
    border: 2px solid #e0e0e0;
    color: var(--text, #000000);
}

/* ===== HISTORY MODAL ===== */
.history-list {
    padding: 16px;
}

.history-session {
    padding: 16px 0;
    border-bottom: 0.5px solid rgba(0, 0, 0, 0.1);
}

.history-session:last-child {
    border-bottom: none;
}

.history-date {
    font-size: 15px;
    font-weight: 600;
    color: var(--text, #000000);
    margin-bottom: 12px;
}

.history-sets {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.history-set {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 15px;
}

.set-values {
    display: flex;
    gap: 12px;
}

.weight-value {
    color: var(--accent, #667eea);
    font-weight: 600;
}

.reps-value {
    color: var(--light-text, #666666);
}

/* ===== SUCCESS MODAL ===== */
.success-content {
    padding: 40px 20px;
    text-align: center;
}

.success-icon {
    font-size: 64px;
    color: #4CAF50;
    margin-bottom: 20px;
    animation: bounce 1s ease;
}

.success-title {
    font-size: 22px;
    font-weight: 700;
    margin: 0 0 8px 0;
    color: var(--text, #000000);
}

.success-message {
    font-size: 15px;
    color: var(--light-text, #666666);
    margin: 0 0 24px 0;
}

/* ===== ACTIVE TIMER OVERLAY ===== */
.active-timer {
    position: fixed;
    top: calc(var(--safe-area-inset-top) + 44px + 8px);
    right: 16px;
    background: var(--gradient-accent, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
    color: white;
    padding: 12px 16px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    font-weight: 600;
    z-index: 999;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

/* ===== ANIMATIONS ===== */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}

/* ===== HAPTIC FEEDBACK SIMULATION ===== */
.haptic-feedback {
    animation: haptic 0.1s ease;
}

@keyframes haptic {
    0% { transform: scale(1); }
    50% { transform: scale(0.98); }
    100% { transform: scale(1); }
}

/* ===== RESPONSIVE ADJUSTMENTS ===== */
@media (max-width: 768px) {
    .workout-title {
        font-size: 24px;
    }
    
    .day-title {
        font-size: 20px;
    }
    
    .exercise-title {
        font-size: 16px;
    }
    
    .native-input {
        padding: 8px 10px;
        font-size: 16px;
    }
    
    .day-pill {
        padding: 10px 16px;
        font-size: 14px;
    }
    
    .complete-button {
        padding: 16px;
        font-size: 16px;
    }
}

@media (max-width: 350px) {
    .workout-title {
        font-size: 22px;
    }
    
    .day-title {
        font-size: 18px;
    }
    
    .exercise-title {
        font-size: 15px;
    }
    
    .native-input {
        padding: 8px;
        font-size: 15px;
    }
    
    .set-inputs {
        flex-direction: column;
        gap: 8px;
    }
    
    .complete-button {
        padding: 14px;
        font-size: 15px;
    }
}
</style>

<div class="app-container">
    <!-- Status Bar -->
    <div class="status-bar">
        <div class="status-title">Today's Workout</div>
        <div class="status-actions">
            <button class="status-action" onclick="showTimerModal()">
                <i class="fas fa-clock"></i>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Workout Header -->
        <div class="workout-header">
            <h1 class="workout-title"><?php echo htmlspecialchars($user_plan['name']); ?></h1>
            <p class="workout-subtitle"><?php echo htmlspecialchars($user_plan['description']); ?></p>
            <div class="workout-stats">
                <div class="stat-item">
                    <i class="fas fa-calendar stat-icon"></i>
                    <span><?php echo $total_days; ?> Days</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-dumbbell stat-icon"></i>
                    <span>
                        <?php
                        $total_exercises = 0;
                        foreach ($all_exercises as $exercises) {
                            $total_exercises += count($exercises);
                        }
                        echo $total_exercises;
                        ?> Exercises
                    </span>
                </div>
            </div>
        </div>

        <!-- Day Picker -->
        <div class="day-picker">
            <?php foreach ($all_days as $day): ?>
            <button class="day-pill <?php echo $day['day_order'] == $current_day_index ? 'active' : ''; ?>"
                    data-day="<?php echo $day['day_order']; ?>"
                    onclick="switchDay(<?php echo $day['day_order']; ?>)">
                Day <?php echo $day['day_order']; ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Day Content -->
        <?php foreach ($all_days as $day): ?>
        <div class="day-content <?php echo $day['day_order'] == $current_day_index ? 'active' : ''; ?>" 
             id="day-<?php echo $day['day_order']; ?>">
            
            <!-- Day Info -->
            <div class="day-info">
                <h2 class="day-title"><?php echo htmlspecialchars($day['title']); ?></h2>
                <p class="day-description"><?php echo htmlspecialchars($day['description']); ?></p>
                <div class="workout-stats">
                    <div class="stat-item">
                        <i class="fas fa-dumbbell stat-icon"></i>
                        <span><?php echo count($all_exercises[$day['day_order']]); ?> Exercises</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-clock stat-icon"></i>
                        <span>45-60 min</span>
                    </div>
                </div>
            </div>

            <!-- Exercise List -->
            <form class="workout-form" id="workout-form-<?php echo $day['day_order']; ?>" 
                  data-day-id="<?php echo $day['id']; ?>">
                <input type="hidden" name="day_id" value="<?php echo $day['id']; ?>">
                
                <div class="exercise-list">
                    <?php foreach ($all_exercises[$day['day_order']] as $index => $exercise): 
                        $last_workout = $last_workout_data[$exercise['id']] ?? [];
                    ?>
                    <div class="exercise-card" id="exercise-<?php echo $exercise['id']; ?>">
                        <div class="exercise-header" onclick="toggleExercise(<?php echo $exercise['id']; ?>)">
                            <h3 class="exercise-title">
                                <span class="exercise-number">#<?php echo $index + 1; ?></span>
                                <?php echo htmlspecialchars($exercise['exercise_name']); ?>
                            </h3>
                            <button type="button" class="exercise-toggle">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>

                        <div class="exercise-content">
                            <!-- Video -->
                            <?php if ($exercise['youtube_link']): ?>
                            <div class="exercise-media">
                                <div class="video-container">
                                    <iframe src="<?php echo htmlspecialchars($exercise['youtube_link']); ?>"
                                            allowfullscreen></iframe>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Notes -->
                            <?php if ($exercise['notes']): ?>
                            <div class="exercise-media">
                                <div class="exercise-notes">
                                    <?php echo htmlspecialchars($exercise['notes']); ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Sets -->
                            <div class="sets-section">
                                <?php for ($i = 1; $i <= $exercise['default_sets']; $i++): 
                                    $last_set = $last_workout[$i] ?? null;
                                    $last_weight = $last_set['weight'] ?? 0;
                                    $last_reps = $last_set['reps'] ?? 0;
                                ?>
                                <div class="set-row">
                                    <div class="set-number"><?php echo $i; ?></div>
                                    
                                    <div class="set-inputs">
                                        <div class="input-group">
                                            <label class="input-label">Weight (kg)</label>
                                            <input type="number"
                                                   name="sets[<?php echo $exercise['id']; ?>][<?php echo $i; ?>][weight]"
                                                   class="native-input weight-input"
                                                   placeholder="0"
                                                   step="0.5"
                                                   min="0"
                                                   value="<?php echo $last_weight > 0 ? $last_weight : ''; ?>"
                                                   data-last-weight="<?php echo $last_weight; ?>">
                                        </div>
                                        
                                        <div class="input-group">
                                            <label class="input-label">Reps</label>
                                            <input type="number"
                                                   name="sets[<?php echo $exercise['id']; ?>][<?php echo $i; ?>][reps]"
                                                   class="native-input reps-input"
                                                   placeholder="0"
                                                   min="0"
                                                   max="100"
                                                   value="<?php echo $last_reps > 0 ? $last_reps : ''; ?>"
                                                   data-last-reps="<?php echo $last_reps; ?>">
                                        </div>
                                    </div>
                                    
                                    <button type="button" class="set-timer"
                                            onclick="startTimerForSet(<?php echo $exercise['id']; ?>, <?php echo $i; ?>)">
                                        <i class="fas fa-clock"></i>
                                    </button>
                                </div>
                                <?php endfor; ?>
                                
                                <!-- History Button -->
                                <button type="button" class="history-button"
                                        onclick="showExerciseHistory(<?php echo $exercise['id']; ?>, '<?php echo htmlspecialchars($exercise['exercise_name']); ?>')">
                                    <i class="fas fa-history"></i>
                                    View History
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Complete Button - Fixed position -->
    <?php foreach ($all_days as $day): ?>
    <div class="complete-section" id="complete-section-<?php echo $day['day_order']; ?>"
         style="<?php echo $day['day_order'] != $current_day_index ? 'display: none;' : ''; ?>">
        <button type="button" class="complete-button" 
                onclick="submitWorkout(<?php echo $day['day_order']; ?>)"
                id="complete-button-<?php echo $day['day_order']; ?>">
            <i class="fas fa-check-circle"></i>
            Complete Day <?php echo $day['day_order']; ?>
        </button>
    </div>
    <?php endforeach; ?>

</div>

<!-- Timer Modal -->
<div class="native-modal" id="timerModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Rest Timer</h3>
            <button class="modal-close" onclick="hideTimerModal()">Done</button>
        </div>
        <div class="timer-display" id="timerDisplay">02:00</div>
        <div class="timer-controls">
            <button class="timer-button timer-secondary" onclick="setTimer(60)">1:00</button>
            <button class="timer-button timer-secondary" onclick="setTimer(90)">1:30</button>
            <button class="timer-button timer-primary" onclick="setTimer(120)">2:00</button>
            <button class="timer-button timer-secondary" onclick="setTimer(180)">3:00</button>
        </div>
        <div class="timer-controls">
            <button class="timer-button timer-secondary" onclick="pauseTimer()">Pause</button>
            <button class="timer-button timer-primary" onclick="startTimer()">Start</button>
        </div>
    </div>
</div>

<!-- History Modal -->
<div class="native-modal" id="historyModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="historyTitle">Workout History</h3>
            <button class="modal-close" onclick="hideHistoryModal()">Done</button>
        </div>
        <div class="history-list" id="historyContent">
            <!-- History will be loaded here -->
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="native-modal" id="successModal">
    <div class="modal-content">
        <div class="success-content">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 class="success-title">Workout Completed!</h3>
            <p class="success-message">Great job! Your progress has been saved.</p>
            <button class="timer-button timer-primary" onclick="goToDashboard()">
                Continue to Dashboard
            </button>
        </div>
    </div>
</div>

<!-- Active Timer Overlay -->
<div class="active-timer" id="activeTimer" style="display: none;">
    <i class="fas fa-clock"></i>
    <span id="activeTimerDisplay">02:00</span>
</div>

<script>
// ===== NATIVE APP BEHAVIOR =====

// Disable zoom
document.addEventListener('gesturestart', function(e) {
    e.preventDefault();
});

document.addEventListener('touchmove', function(e) {
    if(e.scale !== 1) {
        e.preventDefault();
    }
}, { passive: false });

// Prevent pull-to-refresh on main content
let startY;
document.querySelector('.main-content').addEventListener('touchstart', function(e) {
    startY = e.touches[0].clientY;
});

document.querySelector('.main-content').addEventListener('touchmove', function(e) {
    const currentY = e.touches[0].clientY;
    if (currentY < startY && window.scrollY === 0) {
        e.preventDefault();
    }
}, { passive: false });

// ===== DAY NAVIGATION =====
function switchDay(dayNumber) {
    // Add haptic feedback
    triggerHaptic();
    
    // Update pills
    document.querySelectorAll('.day-pill').forEach(pill => {
        pill.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Switch content
    document.querySelectorAll('.day-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById('day-' + dayNumber).classList.add('active');
    
    // Switch complete button
    document.querySelectorAll('.complete-section').forEach(section => {
        section.style.display = 'none';
    });
    document.getElementById('complete-section-' + dayNumber).style.display = 'block';
    
    // Scroll to top
    document.querySelector('.main-content').scrollTop = 0;
}

// ===== EXERCISE TOGGLE =====
function toggleExercise(exerciseId) {
    triggerHaptic();
    const card = document.getElementById('exercise-' + exerciseId);
    card.classList.toggle('open');
}

// ===== TIMER SYSTEM =====
let timerSeconds = 120;
let timerInterval = null;
let isTimerRunning = false;

function showTimerModal() {
    triggerHaptic();
    document.getElementById('timerModal').style.display = 'flex';
    updateTimerDisplay();
}

function hideTimerModal() {
    triggerHaptic();
    document.getElementById('timerModal').style.display = 'none';
    if (isTimerRunning) {
        stopTimer();
    }
}

function setTimer(seconds) {
    triggerHaptic();
    timerSeconds = seconds;
    updateTimerDisplay();
}

function startTimer() {
    triggerHaptic();
    if (!isTimerRunning) {
        isTimerRunning = true;
        timerInterval = setInterval(updateTimer, 1000);
        document.getElementById('activeTimer').style.display = 'flex';
        hideTimerModal();
    }
}

function pauseTimer() {
    triggerHaptic();
    if (isTimerRunning) {
        clearInterval(timerInterval);
        isTimerRunning = false;
    }
}

function stopTimer() {
    clearInterval(timerInterval);
    isTimerRunning = false;
    document.getElementById('activeTimer').style.display = 'none';
}

function updateTimer() {
    timerSeconds--;
    updateTimerDisplay();
    
    if (timerSeconds <= 0) {
        stopTimer();
        showTimerComplete();
    }
}

function updateTimerDisplay() {
    const minutes = Math.floor(timerSeconds / 60);
    const seconds = timerSeconds % 60;
    const display = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    
    document.getElementById('timerDisplay').textContent = display;
    document.getElementById('activeTimerDisplay').textContent = display;
}

function startTimerForSet(exerciseId, setNumber) {
    triggerHaptic();
    showTimerModal();
}

function showTimerComplete() {
    // Play sound if supported
    try {
        const audio = new Audio('data:audio/wav;base64,UklGRigAAABXQVZFZm10IBIAAAABAAEAQB8AAEAfAAABAAgAZGF0YQ');
        audio.play();
    } catch (e) {}
    
    // Show notification
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--success);
        color: white;
        padding: 12px 24px;
        border-radius: 20px;
        font-size: 15px;
        font-weight: 600;
        z-index: 1001;
        animation: fadeIn 0.3s ease;
    `;
    notification.textContent = 'Rest time complete!';
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'fadeIn 0.3s ease reverse';
        setTimeout(() => notification.remove(), 300);
    }, 2000);
}

// ===== HISTORY MODAL =====
function showExerciseHistory(exerciseId, exerciseName) {
    triggerHaptic();
    document.getElementById('historyTitle').textContent = exerciseName;
    document.getElementById('historyModal').style.display = 'flex';
    
    // Show loading
    const content = document.getElementById('historyContent');
    content.innerHTML = `
        <div style="text-align: center; padding: 40px 20px;">
            <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--primary);"></i>
            <p style="margin-top: 16px; color: var(--text-secondary);">Loading history...</p>
        </div>
    `;
    
    // Load history
    loadHistory(exerciseId, content);
}

function hideHistoryModal() {
    triggerHaptic();
    document.getElementById('historyModal').style.display = 'none';
}

function loadHistory(exerciseId, container) {
    const formData = new FormData();
    formData.append('ajax_get_exercise_history', 'true');
    formData.append('exercise_id', exerciseId);
    
    fetch('ajax_get_exercise_history.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.history.length > 0) {
            let html = '';
            data.history.forEach(session => {
                const date = new Date(session.completed_at);
                const formattedDate = date.toLocaleDateString('en-US', {
                    weekday: 'short',
                    month: 'short',
                    day: 'numeric'
                });
                
                html += `
                    <div class="history-session">
                        <div class="history-date">${formattedDate}</div>
                        <div class="history-sets">
                `;
                
                session.sets.forEach(set => {
                    if (set.weight > 0) {
                        html += `
                            <div class="history-set">
                                <span>Set ${set.set_number}</span>
                                <div class="set-values">
                                    <span class="weight-value">${set.weight}kg</span>
                                    <span class="reps-value">× ${set.reps}</span>
                                </div>
                            </div>
                        `;
                    } else {
                        html += `
                            <div class="history-set">
                                <span>Set ${set.set_number}</span>
                                <span class="reps-value">${set.reps} reps</span>
                            </div>
                        `;
                    }
                });
                
                html += `
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        } else {
            container.innerHTML = `
                <div style="text-align: center; padding: 40px 20px;">
                    <i class="fas fa-chart-line" style="font-size: 32px; color: var(--text-tertiary); margin-bottom: 16px;"></i>
                    <h3 style="margin: 0 0 8px 0; color: var(--text-primary);">No History Yet</h3>
                    <p style="margin: 0; color: var(--text-secondary);">Complete this exercise to track your progress</p>
                </div>
            `;
        }
    })
    .catch(error => {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px 20px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 32px; color: var(--danger); margin-bottom: 16px;"></i>
                <p style="margin: 0; color: var(--text-secondary);">Failed to load history</p>
            </div>
        `;
    });
}

// ===== WORKOUT SUBMISSION =====
function submitWorkout(dayNumber) {
    const form = document.getElementById('workout-form-' + dayNumber);
    const button = document.getElementById('complete-button-' + dayNumber);
    
    // Validate
    const hasData = Array.from(form.querySelectorAll('.reps-input'))
        .some(input => input.value.trim() !== '' && parseInt(input.value) > 0);
    
    if (!hasData) {
        alert('Please enter at least one set to complete your workout.');
        return;
    }
    
    // Prepare data
    const formData = new FormData(form);
    formData.append('ajax_complete_workout', 'true');
    
    const originalHTML = button.innerHTML;
    
    // Show loading
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    button.disabled = true;
    
    // Submit
    fetch('ajax_save_workout.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessModal();
            triggerHaptic('success');
        } else {
            alert(data.message || 'Error saving workout');
            button.innerHTML = originalHTML;
            button.disabled = false;
        }
    })
    .catch(error => {
        alert('Network error. Please try again.');
        button.innerHTML = originalHTML;
        button.disabled = false;
    });
}

// ===== SUCCESS MODAL =====
function showSuccessModal() {
    document.getElementById('successModal').style.display = 'flex';
    createConfetti();
}

function goToDashboard() {
    triggerHaptic();
    window.location.href = 'dashboard.php?message=workout_completed';
}

// ===== HAPTIC FEEDBACK =====
function triggerHaptic(type = 'light') {
    // Simulate haptic feedback with animation
    const element = event?.target || document.body;
    element.classList.add('haptic-feedback');
    setTimeout(() => {
        element.classList.remove('haptic-feedback');
    }, 100);
    
    // If on iOS with haptic support
    if (window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.haptic) {
        window.webkit.messageHandlers.haptic.postMessage(type);
    }
}

// ===== CONFETTI EFFECT =====
function createConfetti() {
    const colors = ['#FF2D55', '#5856D6', '#007AFF', '#5AC8FA', '#FF9500', '#FFCC00'];
    
    for (let i = 0; i < 30; i++) {
        const confetti = document.createElement('div');
        confetti.style.cssText = `
            position: fixed;
            width: 8px;
            height: 8px;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            top: -10px;
            left: ${Math.random() * 100}vw;
            border-radius: 4px;
            z-index: 1001;
            pointer-events: none;
        `;
        
        document.body.appendChild(confetti);
        
        // Animation
        confetti.animate([
            { transform: 'translateY(0) rotate(0deg)', opacity: 1 },
            { transform: `translateY(${window.innerHeight}px) rotate(${360 + Math.random() * 360}deg)`, opacity: 0 }
        ], {
            duration: 1000 + Math.random() * 1000,
            easing: 'cubic-bezier(0.1, 0.8, 0.2, 1)'
        }).onfinish = () => confetti.remove();
    }
}

// ===== PROGRESSIVE OVERLOAD INDICATORS =====
document.querySelectorAll('.weight-input, .reps-input').forEach(input => {
    input.addEventListener('input', function() {
        const lastValue = parseFloat(this.dataset.lastWeight || this.dataset.lastReps) || 0;
        const currentValue = parseFloat(this.value) || 0;
        
        if (lastValue > 0) {
            if (currentValue > lastValue) {
                this.style.borderColor = 'var(--success)';
            } else if (currentValue < lastValue) {
                this.style.borderColor = 'var(--danger)';
            } else {
                this.style.borderColor = 'var(--separator)';
            }
        }
    });
});

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', function() {
    // Open first exercise of active day
    const firstExercise = document.querySelector('.day-content.active .exercise-card');
    if (firstExercise) {
        firstExercise.classList.add('open');
    }
    
    // Auto-scroll day picker to current day
    const currentPill = document.querySelector('.day-pill.current');
    if (currentPill) {
        setTimeout(() => {
            currentPill.scrollIntoView({
                behavior: 'smooth',
                inline: 'center'
            });
        }, 300);
    }
});

// ===== PULL TO REFRESH SIMULATION =====
let pullStartY = 0;
let pulling = false;

document.querySelector('.main-content').addEventListener('touchstart', function(e) {
    if (this.scrollTop === 0) {
        pullStartY = e.touches[0].pageY;
        pulling = true;
    }
});

document.querySelector('.main-content').addEventListener('touchmove', function(e) {
    if (!pulling) return;
    
    const pullDistance = e.touches[0].pageY - pullStartY;
    if (pullDistance > 0) {
        e.preventDefault();
        // Could implement pull-to-refresh UI here
    }
});

document.querySelector('.main-content').addEventListener('touchend', function(e) {
    if (pulling) {
        const pullDistance = e.changedTouches[0].pageY - pullStartY;
        if (pullDistance > 100) {
            // Refresh workout data
            location.reload();
        }
        pulling = false;
    }
});
</script>

<?php require_once 'footer.php'; ?>