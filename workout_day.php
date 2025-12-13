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

html, body {
    height: 100%;
    width: 100%;
    overflow: hidden;
    touch-action: manipulation;
}

body {
    margin: 0;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Helvetica Neue', sans-serif;
    background: var(--background, #F2F2F7);
    color: var(--text, #000000);
    -webkit-overflow-scrolling: touch;
    position: relative;
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

/* ===== APP CONTAINER ===== */
.app-container {
    height: 100vh;
    height: -webkit-fill-available;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
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
    flex-shrink: 0;
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

/* ===== EXERCISE TABS CONTAINER ===== */
.exercise-tabs-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* ===== EXERCISE HEADER ===== */
.exercise-header {
    padding: 20px 16px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-bottom: 0.5px solid rgba(0, 0, 0, 0.1);
    flex-shrink: 0;
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

/* ===== EXERCISE TABS NAVIGATION ===== */
.exercise-tabs-nav {
    display: flex;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-bottom: 0.5px solid rgba(0, 0, 0, 0.1);
    flex-shrink: 0;
}

.exercise-tabs-nav::-webkit-scrollbar {
    display: none;
}

.exercise-tab {
    padding: 16px 20px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    font-size: 15px;
    font-weight: 500;
    color: var(--light-text, #666666);
    white-space: nowrap;
    flex-shrink: 0;
    transition: all 0.2s ease;
    position: relative;
}

.exercise-tab.active {
    color: var(--accent, #667eea);
    border-bottom-color: var(--accent, #667eea);
    font-weight: 600;
}

.exercise-tab-number {
    display: inline-block;
    width: 24px;
    height: 24px;
    background: rgba(102, 126, 234, 0.1);
    border-radius: 12px;
    text-align: center;
    line-height: 24px;
    margin-right: 8px;
    font-size: 14px;
    font-weight: 600;
}

.exercise-tab.active .exercise-tab-number {
    background: var(--accent, #667eea);
    color: white;
}

/* ===== EXERCISE CONTENT (TAB CONTENT) - SCROLLABLE ===== */
.exercise-tab-content {
    flex: 1;
    overflow-y: auto !important;
    -webkit-overflow-scrolling: touch !important;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    display: none;
    padding-bottom: 140px; /* Space for tab navigation and history button */
    position: relative;
    height: 100%;
}

.exercise-tab-content.active {
    display: block;
}

.exercise-tab-content::-webkit-scrollbar {
    display: none;
}

.exercise-tab-content {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* Enable smooth scrolling */
.exercise-tab-content {
    scroll-behavior: smooth;
}

/* ===== EXERCISE INFO ===== */
.exercise-info {
    padding: 20px 16px;
    border-bottom: 0.5px solid rgba(0, 0, 0, 0.1);
}

.exercise-name {
    font-size: 24px;
    font-weight: 700;
    margin: 0 0 8px 0;
    color: var(--text, #000000);
}

.exercise-meta {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
}

.exercise-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    color: var(--light-text, #666666);
}

.exercise-instructions {
    font-size: 15px;
    color: var(--text, #000000);
    line-height: 1.5;
    margin: 0;
}

/* ===== EXERCISE MEDIA ===== */
.exercise-media {
    padding: 20px 16px;
    border-bottom: 0.5px solid rgba(0, 0, 0, 0.1);
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
    padding: 16px;
    background: rgba(102, 126, 234, 0.05);
    border-left: 3px solid var(--accent, #667eea);
    border-radius: 0 8px 8px 0;
    margin-top: 16px;
    font-size: 14px;
    color: var(--text, #000000);
    line-height: 1.5;
}

/* ===== SETS SECTION ===== */
.sets-section {
    padding: 20px 16px;
}

.sets-header {
    font-size: 17px;
    font-weight: 600;
    margin: 0 0 20px 0;
    color: var(--text, #000000);
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
    gap: 12px;
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
    padding: 12px;
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

.set-timer {
    width: 44px;
    height: 44px;
    border-radius: 22px;
    background: var(--gradient-accent, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
    border: none;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(102, 126, 234, 0.3);
    transition: all 0.2s ease;
}

.set-timer:active {
    transform: scale(0.95);
}

.set-timer i {
    font-size: 18px;
}

/* ===== HISTORY BUTTON SECTION ===== */
.history-section {
    padding: 16px;
    border-top: 0.5px solid rgba(0, 0, 0, 0.1);
    margin-top: 20px;
    background: rgba(255, 255, 255, 0.95);
}

.history-button {
    width: 100%;
    padding: 16px;
    background: rgba(255, 255, 255, 0.9);
    border: 0.5px solid rgba(0, 0, 0, 0.1);
    border-radius: 12px;
    font-size: 15px;
    color: var(--accent, #667eea);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    transition: all 0.2s ease;
    font-weight: 500;
}

.history-button:active {
    background: rgba(102, 126, 234, 0.05);
    transform: scale(0.98);
}

/* ===== TAB NAVIGATION CONTROLS ===== */
.tab-navigation {
    position: fixed;
    bottom: 100px; /* Above footer */
    left: 0;
    right: 0;
    padding: 16px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-top: 0.5px solid rgba(0, 0, 0, 0.1);
    display: flex;
    gap: 12px;
    z-index: 998;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
}

.tab-nav-button {
    flex: 1;
    padding: 16px;
    border-radius: 12px;
    border: none;
    font-size: 17px;
    font-weight: 600;
    min-height: 50px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.tab-nav-button.prev {
    background: rgba(255, 255, 255, 0.9);
    border: 2px solid #e0e0e0;
    color: var(--text, #000000);
}

.tab-nav-button.next {
    background: var(--gradient-accent, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.tab-nav-button.complete {
    background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
}

.tab-nav-button:active {
    transform: translateY(-2px);
}

.tab-nav-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

/* ===== WORKOUT HISTORY BUTTON ===== */
.workout-history-section {
    position: fixed;
    bottom: 160px; /* Above tab navigation */
    left: 0;
    right: 0;
    padding: 0 16px;
    z-index: 997;
}

.workout-history-button {
    width: 100%;
    padding: 16px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 0.5px solid rgba(0, 0, 0, 0.1);
    border-radius: 12px;
    font-size: 15px;
    color: var(--accent, #667eea);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    transition: all 0.2s ease;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.workout-history-button:active {
    background: rgba(102, 126, 234, 0.05);
    transform: scale(0.98);
}

/* ===== NATIVE MODALS - CENTERED POPUP ===== */
.native-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 20px;
    animation: fadeIn 0.3s ease;
}

.modal-content {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(30px);
    -webkit-backdrop-filter: blur(30px);
    border-radius: 20px;
    width: 100%;
    max-width: 400px;
    max-height: 80vh;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    animation: scaleIn 0.3s ease;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    transform-origin: center center;
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 0.5px solid rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    background: inherit;
    backdrop-filter: inherit;
    border-radius: 20px 20px 0 0;
}

.modal-title {
    font-size: 18px;
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
    font-weight: 500;
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
    padding: 0 24px 24px;
    flex-wrap: wrap;
}

.timer-button {
    flex: 1;
    padding: 16px;
    border-radius: 12px;
    border: none;
    font-size: 17px;
    font-weight: 600;
    min-height: 50px;
    transition: all 0.2s ease;
    min-width: 120px;
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
    padding: 24px;
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

/* ===== WORKOUT HISTORY MODAL ===== */
.workout-history-content {
    padding: 24px;
}

.workout-history-session {
    padding: 16px 0;
    border-bottom: 0.5px solid rgba(0, 0, 0, 0.1);
}

.workout-history-session:last-child {
    border-bottom: none;
}

.workout-history-date {
    font-size: 15px;
    font-weight: 600;
    color: var(--text, #000000);
    margin-bottom: 12px;
}

.workout-history-exercises {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.workout-history-exercise {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 15px;
    padding: 8px 0;
}

.workout-history-exercise-name {
    font-weight: 500;
    color: var(--text, #000000);
}

.workout-history-exercise-stats {
    display: flex;
    gap: 12px;
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
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes scaleIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
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
    .exercise-name {
        font-size: 20px;
    }
    
    .native-input {
        padding: 10px;
        font-size: 16px;
    }
    
    .tab-navigation {
        bottom: 90px;
    }
    
    .workout-history-section {
        bottom: 150px;
    }
    
    .modal-content {
        max-width: 90%;
        margin: 20px;
    }
}

@media (max-width: 350px) {
    .exercise-name {
        font-size: 18px;
    }
    
    .native-input {
        padding: 8px;
        font-size: 15px;
    }
    
    .set-inputs {
        flex-direction: column;
        gap: 8px;
    }
    
    .timer-button {
        min-width: 100px;
        padding: 14px;
    }
}

/* ===== iOS SCROLLING FIXES ===== */
@supports (-webkit-touch-callout: none) {
    .exercise-tab-content {
        overflow-y: scroll !important;
        -webkit-overflow-scrolling: touch !important;
        height: 100%;
    }
    
    /* Force GPU acceleration for smoother scrolling */
    .exercise-tab-content {
        -webkit-transform: translate3d(0,0,0);
        transform: translate3d(0,0,0);
    }
    
    .tab-navigation {
        padding-bottom: env(safe-area-inset-bottom);
    }
    
    .workout-history-section {
        bottom: calc(160px + env(safe-area-inset-bottom));
    }
}

/* Fix for scrolling in iOS Safari */
@media (pointer: coarse) and (hover: none) {
    .exercise-tab-content {
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
}

/* Prevent pull-to-refresh */
.exercise-tab-content {
    overscroll-behavior: contain;
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

    <!-- Day Picker -->
    <div class="day-picker">
        <?php foreach ($all_days as $day): ?>
        <button class="day-pill <?php echo $day['day_order'] == $current_day_index ? 'active current' : ''; ?>"
                data-day="<?php echo $day['day_order']; ?>"
                onclick="switchDay(<?php echo $day['day_order']; ?>)">
            Day <?php echo $day['day_order']; ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Day Content - Each day has its own exercise tabs -->
    <?php foreach ($all_days as $day): 
        $exercises = $all_exercises[$day['day_order']];
        $exercise_count = count($exercises);
    ?>
    <div class="exercise-tabs-container" id="day-<?php echo $day['day_order']; ?>"
         style="<?php echo $day['day_order'] != $current_day_index ? 'display: none;' : ''; ?>">
        
        <!-- Day Header -->
        <div class="exercise-header">
            <h2 class="day-title"><?php echo htmlspecialchars($day['title']); ?></h2>
            <p class="day-description"><?php echo htmlspecialchars($day['description']); ?></p>
            <div class="workout-stats">
                <div class="stat-item">
                    <i class="fas fa-dumbbell stat-icon"></i>
                    <span><?php echo $exercise_count; ?> Exercises</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-clock stat-icon"></i>
                    <span>45-60 min</span>
                </div>
            </div>
        </div>

        <!-- Exercise Tabs Navigation -->
        <div class="exercise-tabs-nav" id="exercise-tabs-<?php echo $day['day_order']; ?>">
            <?php foreach ($exercises as $index => $exercise): ?>
            <button class="exercise-tab <?php echo $index === 0 ? 'active' : ''; ?>"
                    data-tab="<?php echo $index + 1; ?>"
                    onclick="switchExerciseTab(<?php echo $day['day_order']; ?>, <?php echo $index + 1; ?>)">
                <span class="exercise-tab-number"><?php echo $index + 1; ?></span>
                <?php echo htmlspecialchars(substr($exercise['exercise_name'], 0, 15)); ?>
                <?php echo strlen($exercise['exercise_name']) > 15 ? '...' : ''; ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Exercise Tab Contents -->
        <form class="workout-form" id="workout-form-<?php echo $day['day_order']; ?>" 
              data-day-id="<?php echo $day['id']; ?>">
            <input type="hidden" name="day_id" value="<?php echo $day['id']; ?>">
            
            <?php foreach ($exercises as $index => $exercise): 
                $last_workout = $last_workout_data[$exercise['id']] ?? [];
                $is_last_exercise = ($index + 1) === $exercise_count;
            ?>
            <div class="exercise-tab-content <?php echo $index === 0 ? 'active' : ''; ?>"
                 id="exercise-<?php echo $day['day_order']; ?>-<?php echo $index + 1; ?>">
                
                <!-- Exercise Info -->
                <div class="exercise-info">
                    <h3 class="exercise-name"><?php echo htmlspecialchars($exercise['exercise_name']); ?></h3>
                    <div class="exercise-meta">
                        <div class="exercise-meta-item">
                            <i class="fas fa-repeat"></i>
                            <span><?php echo isset($exercise['default_sets']) ? $exercise['default_sets'] : 3; ?> sets</span>
                        </div>
                        <div class="exercise-meta-item">
                            <i class="fas fa-redo"></i>
                            <span><?php echo isset($exercise['default_reps']) ? $exercise['default_reps'] : 10; ?> reps</span>
                        </div>
                    </div>
                    <?php if (isset($exercise['instructions']) && !empty(trim($exercise['instructions']))): ?>
                    <p class="exercise-instructions"><?php echo htmlspecialchars($exercise['instructions']); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Video -->
                <?php if (isset($exercise['youtube_link']) && !empty(trim($exercise['youtube_link']))): ?>
                <div class="exercise-media">
                    <div class="video-container">
                        <iframe src="<?php echo htmlspecialchars($exercise['youtube_link']); ?>"
                                allowfullscreen></iframe>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Notes -->
                <?php if (isset($exercise['notes']) && !empty(trim($exercise['notes']))): ?>
                <div class="exercise-media">
                    <div class="exercise-notes">
                        <?php echo htmlspecialchars($exercise['notes']); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Sets -->
                <div class="sets-section">
                    <h4 class="sets-header">Sets</h4>
                    <?php 
                    $sets_count = isset($exercise['default_sets']) ? $exercise['default_sets'] : 3;
                    for ($i = 1; $i <= $sets_count; $i++): 
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
                </div>

                <!-- Exercise History Button -->
                <div class="history-section">
                    <button type="button" class="history-button"
                            onclick="showExerciseHistory(<?php echo $exercise['id']; ?>, '<?php echo htmlspecialchars($exercise['exercise_name']); ?>')">
                        <i class="fas fa-history"></i>
                        View Exercise History
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </form>
    </div>
    <?php endforeach; ?>

    <!-- Workout History Button (Complete Workout History) -->
    <?php foreach ($all_days as $day): ?>
    <div class="workout-history-section" id="workout-history-<?php echo $day['day_order']; ?>"
         style="<?php echo $day['day_order'] != $current_day_index ? 'display: none;' : ''; ?>">
        <button class="workout-history-button" onclick="showWorkoutHistory(<?php echo $day['id']; ?>, '<?php echo htmlspecialchars($day['title']); ?>')">
            <i class="fas fa-chart-line"></i>
            View Workout History
        </button>
    </div>
    <?php endforeach; ?>

    <!-- Tab Navigation Controls -->
    <?php foreach ($all_days as $day): 
        $exercise_count = count($all_exercises[$day['day_order']]);
    ?>
    <div class="tab-navigation" id="tab-nav-<?php echo $day['day_order']; ?>"
         style="<?php echo $day['day_order'] != $current_day_index ? 'display: none;' : ''; ?>">
        <button class="tab-nav-button prev" 
                onclick="prevExercise(<?php echo $day['day_order']; ?>)">
            <i class="fas fa-arrow-left"></i>
            Previous
        </button>
        <button class="tab-nav-button <?php echo $exercise_count === 1 ? 'complete' : 'next'; ?>"
                onclick="<?php echo $exercise_count === 1 ? "submitWorkout({$day['day_order']})" : "nextExercise({$day['day_order']})"; ?>"
                id="nav-button-<?php echo $day['day_order']; ?>">
            <?php if ($exercise_count === 1): ?>
                <i class="fas fa-check-circle"></i>
                Complete
            <?php else: ?>
                <i class="fas fa-arrow-right"></i>
                Next
            <?php endif; ?>
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

<!-- Exercise History Modal -->
<div class="native-modal" id="historyModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="historyTitle">Exercise History</h3>
            <button class="modal-close" onclick="hideHistoryModal()">Done</button>
        </div>
        <div class="history-list" id="historyContent">
            <!-- History will be loaded here -->
        </div>
    </div>
</div>

<!-- Workout History Modal -->
<div class="native-modal" id="workoutHistoryModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="workoutHistoryTitle">Workout History</h3>
            <button class="modal-close" onclick="hideWorkoutHistoryModal()">Done</button>
        </div>
        <div class="workout-history-content" id="workoutHistoryContent">
            <!-- Workout history will be loaded here -->
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
// ===== TAB MANAGEMENT =====
let currentTabs = {};

// Initialize tabs for each day
<?php foreach ($all_days as $day): ?>
currentTabs[<?php echo $day['day_order']; ?>] = 1;
<?php endforeach; ?>

function switchDay(dayNumber) {
    triggerHaptic();
    
    // Update day pills
    document.querySelectorAll('.day-pill').forEach(pill => {
        pill.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Hide all day containers
    document.querySelectorAll('.exercise-tabs-container').forEach(container => {
        container.style.display = 'none';
    });
    
    // Hide all tab navigations
    document.querySelectorAll('.tab-navigation').forEach(nav => {
        nav.style.display = 'none';
    });
    
    // Hide all workout history buttons
    document.querySelectorAll('.workout-history-section').forEach(section => {
        section.style.display = 'none';
    });
    
    // Show selected day
    document.getElementById('day-' + dayNumber).style.display = 'flex';
    document.getElementById('tab-nav-' + dayNumber).style.display = 'flex';
    document.getElementById('workout-history-' + dayNumber).style.display = 'block';
    
    // Reset to first exercise tab
    switchExerciseTab(dayNumber, 1);
}

function switchExerciseTab(dayNumber, tabNumber) {
    triggerHaptic();
    
    // Update current tab
    currentTabs[dayNumber] = tabNumber;
    
    // Update tab buttons
    const tabNav = document.getElementById('exercise-tabs-' + dayNumber);
    tabNav.querySelectorAll('.exercise-tab').forEach(tab => {
        tab.classList.remove('active');
        if (parseInt(tab.dataset.tab) === tabNumber) {
            tab.classList.add('active');
            
            // Scroll tab into view
            tab.scrollIntoView({
                behavior: 'smooth',
                inline: 'center',
                block: 'nearest'
            });
        }
    });
    
    // Update tab content
    const container = document.getElementById('day-' + dayNumber);
    container.querySelectorAll('.exercise-tab-content').forEach(content => {
        content.classList.remove('active');
    });
    const activeTab = document.getElementById('exercise-' + dayNumber + '-' + tabNumber);
    activeTab.classList.add('active');
    
    // Scroll to top of active tab
    setTimeout(() => {
        activeTab.scrollTop = 0;
    }, 100);
    
    // Update navigation buttons
    updateNavigationButtons(dayNumber);
}

function prevExercise(dayNumber) {
    if (currentTabs[dayNumber] > 1) {
        switchExerciseTab(dayNumber, currentTabs[dayNumber] - 1);
    }
}

function nextExercise(dayNumber) {
    const exerciseCount = document.querySelectorAll('#exercise-tabs-' + dayNumber + ' .exercise-tab').length;
    if (currentTabs[dayNumber] < exerciseCount) {
        switchExerciseTab(dayNumber, currentTabs[dayNumber] + 1);
    }
}

function updateNavigationButtons(dayNumber) {
    const exerciseCount = document.querySelectorAll('#exercise-tabs-' + dayNumber + ' .exercise-tab').length;
    const currentTab = currentTabs[dayNumber];
    const navButton = document.getElementById('nav-button-' + dayNumber);
    const prevButton = document.querySelector('#tab-nav-' + dayNumber + ' .prev');
    
    // Update previous button
    if (currentTab === 1) {
        prevButton.disabled = true;
        prevButton.style.opacity = '0.5';
    } else {
        prevButton.disabled = false;
        prevButton.style.opacity = '1';
    }
    
    // Update next/complete button
    if (currentTab === exerciseCount) {
        // Last exercise - show Complete button
        navButton.innerHTML = '<i class="fas fa-check-circle"></i> Complete';
        navButton.className = 'tab-nav-button complete';
        navButton.onclick = function() { submitWorkout(dayNumber); };
    } else {
        // Not last exercise - show Next button
        navButton.innerHTML = '<i class="fas fa-arrow-right"></i> Next';
        navButton.className = 'tab-nav-button next';
        navButton.onclick = function() { nextExercise(dayNumber); };
    }
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

// ===== WORKOUT SUBMISSION =====
function submitWorkout(dayNumber) {
    const form = document.getElementById('workout-form-' + dayNumber);
    const navButton = document.getElementById('nav-button-' + dayNumber);
    
    // Validate at least one set has data
    const hasData = Array.from(form.querySelectorAll('.reps-input'))
        .some(input => input.value.trim() !== '' && parseInt(input.value) > 0);
    
    if (!hasData) {
        alert('Please enter at least one set to complete your workout.');
        return;
    }
    
    // Prepare data
    const formData = new FormData(form);
    formData.append('ajax_complete_workout', 'true');
    
    const originalHTML = navButton.innerHTML;
    
    // Show loading
    navButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    navButton.disabled = true;
    
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
            navButton.innerHTML = originalHTML;
            navButton.disabled = false;
        }
    })
    .catch(error => {
        alert('Network error. Please try again.');
        navButton.innerHTML = originalHTML;
        navButton.disabled = false;
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

// ===== EXERCISE HISTORY MODAL =====
function showExerciseHistory(exerciseId, exerciseName) {
    triggerHaptic();
    document.getElementById('historyTitle').textContent = exerciseName + ' - History';
    document.getElementById('historyModal').style.display = 'flex';
    
    // Show loading
    const content = document.getElementById('historyContent');
    content.innerHTML = `
        <div style="text-align: center; padding: 40px 20px;">
            <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--accent);"></i>
            <p style="margin-top: 16px; color: var(--light-text);">Loading exercise history...</p>
        </div>
    `;
    
    // Load exercise history
    loadExerciseHistory(exerciseId, content);
}

function hideHistoryModal() {
    triggerHaptic();
    document.getElementById('historyModal').style.display = 'none';
}

function loadExerciseHistory(exerciseId, container) {
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
                    day: 'numeric',
                    year: 'numeric'
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
                    <i class="fas fa-chart-line" style="font-size: 32px; color: var(--light-text); margin-bottom: 16px;"></i>
                    <h3 style="margin: 0 0 8px 0; color: var(--text);">No History Yet</h3>
                    <p style="margin: 0; color: var(--light-text);">Complete this exercise to track your progress</p>
                </div>
            `;
        }
    })
    .catch(error => {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px 20px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 32px; color: #f44336; margin-bottom: 16px;"></i>
                <h3 style="margin: 0 0 8px 0; color: var(--text);">Error Loading History</h3>
                <p style="margin: 0; color: var(--light-text);">Failed to load exercise history</p>
            </div>
        `;
    });
}

// ===== WORKOUT HISTORY MODAL =====
function showWorkoutHistory(dayId, dayTitle) {
    triggerHaptic();
    document.getElementById('workoutHistoryTitle').textContent = dayTitle + ' - Workout History';
    document.getElementById('workoutHistoryModal').style.display = 'flex';
    
    // Show loading
    const content = document.getElementById('workoutHistoryContent');
    content.innerHTML = `
        <div style="text-align: center; padding: 40px 20px;">
            <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--accent);"></i>
            <p style="margin-top: 16px; color: var(--light-text);">Loading workout history...</p>
        </div>
    `;
    
    // Load workout history
    loadWorkoutHistory(dayId, content);
}

function hideWorkoutHistoryModal() {
    triggerHaptic();
    document.getElementById('workoutHistoryModal').style.display = 'none';
}

function loadWorkoutHistory(dayId, container) {
    const formData = new FormData();
    formData.append('ajax_get_workout_history', 'true');
    formData.append('day_id', dayId);
    
    fetch('ajax_get_workout_history.php', {
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
                    weekday: 'long',
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                html += `
                    <div class="workout-history-session">
                        <div class="workout-history-date">${formattedDate}</div>
                        <div class="workout-history-exercises">
                `;
                
                // Group exercises by workout session
                const exercisesBySession = {};
                session.exercises.forEach(exercise => {
                    if (!exercisesBySession[exercise.exercise_name]) {
                        exercisesBySession[exercise.exercise_name] = [];
                    }
                    exercisesBySession[exercise.exercise_name].push(exercise);
                });
                
                Object.entries(exercisesBySession).forEach(([exerciseName, sets]) => {
                    const totalSets = sets.length;
                    const bestSet = sets.reduce((best, set) => {
                        return set.weight > best.weight ? set : best;
                    }, { weight: 0, reps: 0 });
                    
                    html += `
                        <div class="workout-history-exercise">
                            <span class="workout-history-exercise-name">${exerciseName}</span>
                            <div class="workout-history-exercise-stats">
                                <span>${totalSets} sets</span>
                                ${bestSet.weight > 0 ? `<span>Best: ${bestSet.weight}kg × ${bestSet.reps}</span>` : ''}
                            </div>
                        </div>
                    `;
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
                    <i class="fas fa-dumbbell" style="font-size: 32px; color: var(--light-text); margin-bottom: 16px;"></i>
                    <h3 style="margin: 0 0 8px 0; color: var(--text);">No Workout History</h3>
                    <p style="margin: 0; color: var(--light-text);">Complete this workout to see your history here</p>
                </div>
            `;
        }
    })
    .catch(error => {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px 20px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 32px; color: #f44336; margin-bottom: 16px;"></i>
                <h3 style="margin: 0 0 8px 0; color: var(--text);">Error Loading History</h3>
                <p style="margin: 0; color: var(--light-text);">Failed to load workout history</p>
            </div>
        `;
    });
}

// ===== HAPTIC FEEDBACK =====
function triggerHaptic(type = 'light') {
    // Simulate haptic feedback with animation
    const element = event?.target || document.body;
    element.classList.add('haptic-feedback');
    setTimeout(() => {
        element.classList.remove('haptic-feedback');
    }, 100);
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

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', function() {
    // Initialize navigation buttons for current day
    updateNavigationButtons(<?php echo $current_day_index; ?>);
    
    // Scroll first tab into view
    const firstTab = document.querySelector('.exercise-tab.active');
    if (firstTab) {
        setTimeout(() => {
            firstTab.scrollIntoView({
                inline: 'center',
                block: 'nearest'
            });
        }, 300);
    }
    
    // Fix iOS scrolling for tab contents
    const tabContents = document.querySelectorAll('.exercise-tab-content');
    tabContents.forEach(content => {
        // Force iOS to recognize as scrollable
        content.style.webkitOverflowScrolling = 'touch';
        content.style.overflow = 'scroll';
        
        // Prevent body from scrolling when reaching top/bottom
        content.addEventListener('touchmove', function(e) {
            if (this.scrollTop === 0 && e.touches[0].clientY > 0) {
                e.preventDefault();
            }
            if (this.scrollHeight <= this.scrollTop + this.clientHeight && e.touches[0].clientY < 0) {
                e.preventDefault();
            }
        }, { passive: false });
    });
    
    // Initialize first tab content scroll
    const firstTabContent = document.querySelector('.exercise-tab-content.active');
    if (firstTabContent) {
        firstTabContent.scrollTop = 0;
    }
});

// Prevent body scrolling
document.body.style.overflow = 'hidden';
document.body.style.position = 'fixed';
document.body.style.width = '100%';
document.body.style.height = '100%';

// Fix for modal backdrop scrolling
const modals = document.querySelectorAll('.native-modal');
modals.forEach(modal => {
    modal.addEventListener('touchmove', function(e) {
        e.preventDefault();
    }, { passive: false });
});
</script>

<?php require_once 'footer.php'; ?>