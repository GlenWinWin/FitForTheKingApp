<?php
$pageTitle = 'Workout Plan';
require_once 'header.php';
requireLogin();

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

// Get all days in plan with their exercises
$days_query = 'SELECT * FROM workout_plan_days WHERE plan_id = ? ORDER BY day_order';
$stmt = $db->prepare($days_query);
$stmt->execute([$user_plan['id']]);
$all_days = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_days = count($all_days);

// Get selected day from URL or use current day
if (isset($_GET['day']) && is_numeric($_GET['day']) && $_GET['day'] >= 1 && $_GET['day'] <= $total_days) {
    $selected_day = intval($_GET['day']);
} else {
    // Calculate current day in plan based on start date
    $selected_at = new DateTime($user_plan['selected_at']);
    $today = new DateTime();
    $days_since_start = $selected_at->diff($today)->days;
    $selected_day = ($days_since_start % $total_days) + 1;
}

// Get selected day details
$current_day = null;
foreach ($all_days as $day) {
    if ($day['day_order'] == $selected_day) {
        $current_day = $day;
        break;
    }
}

// Get exercises for ALL days to populate forms
$all_exercises_by_day = [];
foreach ($all_days as $day) {
    $exercises_query = "SELECT * FROM workout_exercises 
                       WHERE plan_day_id = ? 
                       ORDER BY id";
    $stmt = $db->prepare($exercises_query);
    $stmt->execute([$day['id']]);
    $all_exercises_by_day[$day['day_order']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get exercises for current day
$day_exercises = $all_exercises_by_day[$selected_day] ?? [];

// Get last workout data for progressive overload
$last_workout_data = [];
if (!empty($day_exercises)) {
    $exercise_ids = [];
    foreach ($day_exercises as $exercise) {
        $exercise_ids[] = $exercise['id'];
    }
    
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
    :root {
        --mobile-padding: 0.75rem;
        --mobile-font-sm: 0.85rem;
        --mobile-font-xs: 0.75rem;
        --section-spacing: 1rem;
        --border-radius: 12px;
        --card-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        --active-shadow: 0 4px 15px rgba(26, 35, 126, 0.15);
        --modal-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    /* Mobile-First Container */
    .workout-container {
        max-width: 100%;
        margin: 0;
        padding: 0;
        min-height: 100vh;
        background: #f8f9fa;
    }

    /* Simplified Header */
    .workout-header {
        background: white;
        padding: 1rem var(--mobile-padding);
        border-bottom: 1px solid #e0e0e0;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .workout-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #1a237e;
        margin-bottom: 0.25rem;
        line-height: 1.2;
    }

    .workout-subtitle {
        font-size: 0.85rem;
        color: #666;
        margin-bottom: 0.75rem;
        line-height: 1.3;
    }

    .workout-meta {
        display: flex;
        gap: 1rem;
        flex-wrap: nowrap;
        overflow-x: auto;
        padding: 0.5rem 0;
        -webkit-overflow-scrolling: touch;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        color: #666;
        font-size: 0.8rem;
        white-space: nowrap;
        padding: 0.3rem 0.5rem;
        background: #f5f5f5;
        border-radius: 20px;
    }

    /* Day Navigation */
    .day-navigation {
        background: white;
        padding: 0.75rem var(--mobile-padding);
        border-bottom: 1px solid #e0e0e0;
        position: sticky;
        top: 125px;
        z-index: 90;
    }

    .day-tabs {
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        padding: 0.25rem 0;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }

    .day-tabs::-webkit-scrollbar {
        display: none;
    }

    .day-tab {
        flex: 0 0 auto;
        padding: 0.6rem 1rem;
        background: #f8f9ff;
        border: 1.5px solid #e8eaf6;
        border-radius: 10px;
        color: #3f51b5;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
        font-size: 0.85rem;
        min-width: 70px;
        text-align: center;
    }

    .day-tab:hover {
        border-color: #3f51b5;
    }

    .day-tab.active {
        background: linear-gradient(135deg, #3f51b5 0%, #1a237e 100%);
        color: white;
        border-color: #3f51b5;
        box-shadow: var(--active-shadow);
    }

    .day-tab.current-day {
        position: relative;
    }

    .day-tab.current-day::after {
        content: '';
        position: absolute;
        top: -4px;
        right: -4px;
        width: 8px;
        height: 8px;
        background: #4CAF50;
        border-radius: 50%;
        border: 2px solid white;
    }

    /* Exercise List - Like Design */
    .exercise-list {
        padding: var(--mobile-padding);
    }

    .exercise-item {
        background: white;
        border-radius: var(--border-radius);
        padding: 1rem;
        margin-bottom: 0.75rem;
        box-shadow: var(--card-shadow);
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .exercise-item:hover {
        transform: translateY(-2px);
        box-shadow: var(--active-shadow);
    }

    .exercise-item.completed {
        border-color: #4CAF50;
        background: rgba(76, 175, 80, 0.05);
    }

    .exercise-item.in-progress {
        border-color: #3f51b5;
        background: rgba(63, 81, 181, 0.05);
    }

    .exercise-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .exercise-name {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1a237e;
        margin: 0;
    }

    .exercise-status {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.2rem 0.5rem;
        border-radius: 12px;
        background: #f5f5f5;
        color: #666;
    }

    .exercise-status.complete {
        background: #4CAF50;
        color: white;
    }

    .exercise-status.in-progress {
        background: #3f51b5;
        color: white;
    }

    .exercise-muscles {
        font-size: 0.85rem;
        color: #666;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .exercise-target {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .exercise-sets {
        font-weight: 600;
        color: #333;
    }

    .exercise-last {
        color: #666;
        font-size: 0.85rem;
    }

    .exercise-last strong {
        color: #3f51b5;
    }

    .exercise-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }

    .action-button {
        flex: 1;
        padding: 0.6rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
    }

    .watch-button {
        background: #f8f9ff;
        color: #3f51b5;
        border: 1.5px solid #e8eaf6;
    }

    .watch-button:hover {
        background: #e8eaf6;
        border-color: #3f51b5;
    }

    .start-button {
        background: linear-gradient(135deg, #3f51b5 0%, #1a237e 100%);
        color: white;
    }

    .start-button:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(63, 81, 181, 0.2);
    }

    /* Exercise Modal - Matches Design */
    .exercise-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: white;
        z-index: 1000;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }

    .modal-header {
        position: sticky;
        top: 0;
        background: white;
        padding: 1rem;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        z-index: 100;
    }

    .modal-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #1a237e;
        margin: 0;
    }

    .close-modal {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #666;
        cursor: pointer;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .close-modal:hover {
        background: #f5f5f5;
    }

    .modal-content {
        padding: 1rem;
    }

    /* Set Counter */
    .set-counter {
        text-align: center;
        margin-bottom: 1.5rem;
        padding: 0.75rem;
        background: #f8f9ff;
        border-radius: var(--border-radius);
        font-size: 0.9rem;
        font-weight: 600;
        color: #3f51b5;
    }

    /* Demo Video Section - FIXED YOUTUBE */
    .demo-section {
        margin-bottom: 1.5rem;
    }

    .demo-video {
        position: relative;
        width: 100%;
        height: 200px;
        border-radius: var(--border-radius);
        overflow: hidden;
        margin-bottom: 0.75rem;
        background: #000;
    }

    .demo-video iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    .video-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #1a237e 0%, #3f51b5 100%);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: white;
        text-align: center;
        padding: 1rem;
    }

    .video-placeholder i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.8;
    }

    .video-placeholder span {
        font-size: 0.9rem;
        opacity: 0.9;
    }

    .video-info {
        font-size: 0.8rem;
        color: #666;
        text-align: center;
        margin-top: 0.5rem;
    }

    /* Set Logging Section */
    .set-logging {
        background: white;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--card-shadow);
    }

    .set-logging h3 {
        margin: 0 0 1rem 0;
        font-size: 1.1rem;
        color: #333;
        text-align: center;
    }

    .log-form {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .form-group {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .form-group label {
        font-weight: 600;
        color: #333;
        min-width: 80px;
        font-size: 0.9rem;
    }

    .input-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex: 1;
    }

    .number-input {
        flex: 1;
        padding: 0.75rem;
        border: 1.5px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1rem;
        text-align: center;
        background: white;
        color: #333;
        -webkit-appearance: none;
        -moz-appearance: textfield;
    }

    .number-input::-webkit-inner-spin-button,
    .number-input::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .number-input:focus {
        border-color: #3f51b5;
        outline: none;
        box-shadow: 0 0 0 2px rgba(63, 81, 181, 0.1);
    }

    .unit-label {
        font-size: 0.9rem;
        color: #666;
        min-width: 30px;
    }

    .action-buttons {
        display: flex;
        gap: 0.75rem;
        margin-top: 0.5rem;
    }

    .action-buttons button {
        flex: 1;
        padding: 0.8rem;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .complete-set-btn {
        background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .complete-set-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
    }

    /* Upcoming Exercises */
    .upcoming-exercises {
        margin-top: 2rem;
    }

    .upcoming-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e0e0e0;
    }

    .upcoming-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .upcoming-item {
        padding: 0.75rem;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 3px solid #e0e0e0;
    }

    .upcoming-item.current {
        border-left-color: #3f51b5;
        background: #f8f9ff;
    }

    .upcoming-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.25rem;
    }

    .upcoming-details {
        font-size: 0.85rem;
        color: #666;
        display: flex;
        justify-content: space-between;
    }

    /* Bottom Navigation */
    .bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        border-top: 1px solid #e0e0e0;
        padding: 0.75rem var(--mobile-padding);
        display: flex;
        gap: 0.5rem;
        z-index: 200;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
    }

    .nav-button {
        flex: 1;
        padding: 0.8rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .nav-button.prev {
        background: white;
        color: #666;
        border: 1.5px solid #e0e0e0;
    }

    .nav-button.prev:hover:not(:disabled) {
        border-color: #3f51b5;
        color: #3f51b5;
    }

    .nav-button.complete {
        background: linear-gradient(135deg, #3f51b5 0%, #1a237e 100%);
        color: white;
    }

    .nav-button.complete:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(63, 81, 181, 0.2);
    }

    .nav-button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Rest Timer */
    .rest-timer-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1100;
        justify-content: center;
        align-items: center;
        padding: 1rem;
        backdrop-filter: blur(3px);
    }

    .timer-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        width: 100%;
        max-width: 300px;
        text-align: center;
        box-shadow: var(--modal-shadow);
    }

    .timer-display {
        font-size: 2.5rem;
        font-weight: 700;
        color: #3f51b5;
        margin: 1rem 0;
        font-family: 'Courier New', monospace;
        letter-spacing: 2px;
    }

    /* Progress Bars */
    .progress-section {
        margin: 1rem 0;
    }

    .progress-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
        color: #666;
    }

    .progress-bar {
        height: 6px;
        background: #e0e0e0;
        border-radius: 3px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(135deg, #3f51b5 0%, #1a237e 100%);
        transition: width 0.3s ease;
    }

    /* Video Modal */
    .video-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.95);
        z-index: 2000;
        justify-content: center;
        align-items: center;
        padding: 1rem;
    }

    .video-modal-content {
        width: 100%;
        max-width: 800px;
        position: relative;
    }

    .video-modal-iframe {
        width: 100%;
        height: 450px;
        border: none;
        border-radius: var(--border-radius);
    }

    .close-video {
        position: absolute;
        top: -50px;
        right: 0;
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .close-video:hover {
        color: #3f51b5;
        transform: scale(1.1);
    }

    /* Success Modal */
    .success-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 3000;
        justify-content: center;
        align-items: center;
        padding: 1rem;
        backdrop-filter: blur(3px);
    }

    .success-card {
        background: white;
        border-radius: 16px;
        padding: 2rem 1.5rem;
        text-align: center;
        width: 100%;
        max-width: 300px;
        box-shadow: var(--modal-shadow);
        animation: successPop 0.5s ease;
    }

    @keyframes successPop {
        0% { transform: scale(0.9); opacity: 0; }
        70% { transform: scale(1.05); }
        100% { transform: scale(1); opacity: 1; }
    }

    .success-icon {
        font-size: 3rem;
        color: #4CAF50;
        margin-bottom: 1rem;
        animation: bounce 0.5s ease;
    }

    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }

    /* Hidden Form Container */
    .hidden-forms-container {
        display: none;
    }

    /* Animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .exercise-modal.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    .video-modal.active {
        display: flex;
        animation: fadeIn 0.3s ease;
    }

    .success-modal.active {
        display: flex;
        animation: fadeIn 0.3s ease;
    }

    /* Tablet and Desktop */
    @media (min-width: 768px) {
        .workout-container {
            max-width: 768px;
            margin: 0 auto;
        }

        .exercise-modal {
            max-width: 500px;
            margin: 2rem auto;
            border-radius: var(--border-radius);
            box-shadow: var(--modal-shadow);
            height: calc(100vh - 4rem);
        }

        .bottom-nav {
            max-width: 500px;
            margin: 0 auto;
            left: 50%;
            transform: translateX(-50%);
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            width: calc(100% - 2rem);
            bottom: 1rem;
        }

        .demo-video {
            height: 250px;
        }

        .video-modal-iframe {
            height: 500px;
        }
    }

    @media (max-width: 380px) {
        .form-group {
            flex-direction: column;
            align-items: stretch;
            gap: 0.5rem;
        }

        .form-group label {
            min-width: auto;
        }

        .action-buttons {
            flex-direction: column;
        }

        .demo-video {
            height: 180px;
        }
    }
</style>

<div class="workout-container">
    <!-- Plan Overview -->
    <div class="workout-header">
        <h1 class="workout-title"><?php echo htmlspecialchars($user_plan['name']); ?></h1>
        <div class="workout-subtitle"><?php echo htmlspecialchars($user_plan['description']); ?></div>

        <div class="workout-meta">
            <div class="meta-item">
                <i class="fas fa-calendar"></i>
                <span><?php echo $total_days; ?>-Day Program</span>
            </div>
            <div class="meta-item">
                <i class="fas fa-dumbbell"></i>
                <span><?php echo count($day_exercises); ?> Exercises</span>
            </div>
            <div class="meta-item">
                <i class="fas fa-clock"></i>
                <span>Current: Day <?php echo $selected_day; ?></span>
            </div>
        </div>
    </div>

    <!-- Day Navigation -->
    <div class="day-navigation">
        <div class="day-tabs">
            <?php 
            // Calculate current day based on start date
            $selected_at = new DateTime($user_plan['selected_at']);
            $today = new DateTime();
            $days_since_start = $selected_at->diff($today)->days;
            $calculated_current_day = ($days_since_start % $total_days) + 1;
            
            foreach ($all_days as $day): 
                $is_current_day = ($day['day_order'] == $calculated_current_day);
            ?>
                <div class="day-tab <?php echo $day['day_order'] == $selected_day ? 'active' : ''; ?> <?php echo $is_current_day ? 'current-day' : ''; ?>" 
                     data-day="<?php echo $day['day_order']; ?>"
                     onclick="changeDay(<?php echo $day['day_order']; ?>)">
                    Day <?php echo $day['day_order']; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Current Day Header -->
    <?php if ($current_day): ?>
    <div style="background: linear-gradient(135deg, #3f51b5 0%, #1a237e 100%); color: white; padding: 1rem var(--mobile-padding);">
        <div style="font-size: 0.9rem; opacity: 0.9;">Day <?php echo $selected_day; ?> of <?php echo $total_days; ?></div>
        <div style="font-size: 1.2rem; font-weight: 700;"><?php echo htmlspecialchars($current_day['title']); ?></div>
        <?php if ($current_day['description']): ?>
            <div style="font-size: 0.85rem; opacity: 0.8; margin-top: 0.25rem;"><?php echo htmlspecialchars($current_day['description']); ?></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Exercise List -->
    <div class="exercise-list">
        <?php if (empty($day_exercises)): ?>
            <div style="text-align: center; padding: 3rem 1rem; color: #666;">
                <i class="fas fa-dumbbell" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                <p>No exercises scheduled for this day.</p>
            </div>
        <?php else: ?>
            <?php foreach ($day_exercises as $index => $exercise): 
                $last_workout = $last_workout_data[$exercise['id']] ?? [];
                $last_set = !empty($last_workout) ? end($last_workout) : null;
                $last_weight = $last_set['weight'] ?? 0;
                $last_reps = $last_set['reps'] ?? 0;
                $total_sets = $exercise['default_sets'];
                
                // Get muscles
                $muscles = [];
                if ($exercise['primary_muscle']) $muscles[] = $exercise['primary_muscle'];
                if ($exercise['secondary_muscle']) $muscles[] = $exercise['secondary_muscle'];
                if ($exercise['tertiary_muscle']) $muscles[] = $exercise['tertiary_muscle'];
                
                // Extract YouTube video ID
                $youtube_id = '';
                if ($exercise['youtube_link']) {
                    $url = $exercise['youtube_link'];
                    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches)) {
                        $youtube_id = $matches[1];
                    }
                }
            ?>
                <div class="exercise-item" 
                     data-exercise-id="<?php echo $exercise['id']; ?>"
                     data-exercise-index="<?php echo $index; ?>"
                     onclick="openExerciseModal(<?php echo $index; ?>)">
                    <div class="exercise-header">
                        <h3 class="exercise-name"><?php echo htmlspecialchars($exercise['exercise_name']); ?></h3>
                        <span class="exercise-status"><?php echo $total_sets; ?> sets</span>
                    </div>
                    
                    <div class="exercise-muscles">
                        <i class="fas fa-running"></i>
                        <?php echo htmlspecialchars(implode(' · ', array_filter($muscles))); ?>
                    </div>
                    
                    <div class="exercise-target">
                        <div class="exercise-sets">Target: <?php echo $total_sets; ?> × <?php echo $exercise['default_reps']; ?></div>
                        <?php if ($last_weight > 0 || $last_reps > 0): ?>
                            <div class="exercise-last">
                                Last: <strong><?php echo $last_weight; ?> lbs × <?php echo $last_reps; ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="exercise-actions">
                        <?php if ($exercise['youtube_link'] && $youtube_id): ?>
                            <button class="action-button watch-button" 
                                    onclick="event.stopPropagation(); openVideoModal('<?php echo $youtube_id; ?>', '<?php echo htmlspecialchars($exercise['exercise_name']); ?>')">
                                <i class="fas fa-play-circle"></i> Watch Demo
                            </button>
                        <?php endif; ?>
                        <button class="action-button start-button" 
                                onclick="event.stopPropagation(); openExerciseModal(<?php echo $index; ?>)">
                            <i class="fas fa-play"></i> Start Exercise
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Hidden Forms for ALL Days -->
<div class="hidden-forms-container">
    <?php foreach ($all_days as $day): 
        $day_exercises_for_form = $all_exercises_by_day[$day['day_order']] ?? [];
    ?>
        <form id="workoutForm-<?php echo $day['day_order']; ?>" class="workout-form" data-day-id="<?php echo $day['id']; ?>">
            <input type="hidden" name="day_id" value="<?php echo $day['id']; ?>">
            
            <?php foreach ($day_exercises_for_form as $exercise): ?>
                <?php for ($i = 1; $i <= $exercise['default_sets']; $i++): ?>
                    <input type="hidden" 
                           name="sets[<?php echo $exercise['id']; ?>][<?php echo $i; ?>][weight]" 
                           id="form-weight-<?php echo $day['day_order']; ?>-<?php echo $exercise['id']; ?>-<?php echo $i; ?>"
                           value="">
                    <input type="hidden" 
                           name="sets[<?php echo $exercise['id']; ?>][<?php echo $i; ?>][reps]" 
                           id="form-reps-<?php echo $day['day_order']; ?>-<?php echo $exercise['id']; ?>-<?php echo $i; ?>"
                           value="">
                <?php endfor; ?>
            <?php endforeach; ?>
        </form>
    <?php endforeach; ?>
</div>

<!-- Exercise Modal -->
<div class="exercise-modal" id="exerciseModal">
    <div class="modal-header">
        <h2 class="modal-title" id="modalExerciseName">Exercise Name</h2>
        <button class="close-modal" onclick="closeExerciseModal()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="modal-content">
        <!-- Set Counter -->
        <div class="set-counter" id="setCounter">
            SET <span id="currentSet">1</span> of <span id="totalSets">4</span>
        </div>
        
        <!-- Demo Video Section -->
        <div class="demo-section" id="demoSection">
            <div class="demo-video" id="demoVideo">
                <!-- YouTube iframe will be inserted here -->
            </div>
            <div class="video-info" id="videoInfo">
                Click play to watch demo
            </div>
        </div>
        
        <!-- Set Logging -->
        <div class="set-logging">
            <h3>Log Current Set</h3>
            <div class="log-form" id="setLogForm">
                <div class="form-group">
                    <label for="weightInput">Weight</label>
                    <div class="input-group">
                        <input type="number" 
                               id="weightInput" 
                               class="number-input" 
                               placeholder="125"
                               step="0.5"
                               min="0"
                               value="">
                        <span class="unit-label">lbs</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="repsInput">Reps</label>
                    <div class="input-group">
                        <input type="number" 
                               id="repsInput" 
                               class="number-input" 
                               placeholder="8"
                               min="0"
                               max="100"
                               value="">
                        <span class="unit-label">reps</span>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button type="button" class="action-button complete-set-btn" onclick="completeCurrentSet()">
                        <i class="fas fa-check-circle"></i> Complete Set
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Progress Section -->
        <div class="progress-section">
            <div class="progress-label">
                <span>Progress</span>
                <span id="progressText">0/4 sets</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill" style="width: 0%;"></div>
            </div>
        </div>
        
        <!-- Upcoming Exercises -->
        <div class="upcoming-exercises">
            <div class="upcoming-title">Upcoming Exercises</div>
            <div class="upcoming-list" id="upcomingList">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    </div>
    
    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <button class="nav-button prev" id="prevExercise" onclick="prevExercise()" disabled>
            <i class="fas fa-chevron-left"></i> Previous
        </button>
        <button class="nav-button complete" id="completeWorkout" onclick="completeWorkout()">
            <i class="fas fa-check-circle"></i> Complete Workout
        </button>
        <button class="nav-button prev" id="nextExercise" onclick="nextExercise()">
            Next <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</div>

<!-- Video Modal -->
<div class="video-modal" id="videoModal">
    <div class="video-modal-content">
        <button class="close-video" onclick="closeVideoModal()">
            <i class="fas fa-times"></i>
        </button>
        <iframe class="video-modal-iframe" id="videoModalIframe" 
                src="" 
                frameborder="0" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                allowfullscreen>
        </iframe>
    </div>
</div>

<!-- Rest Timer Modal -->
<div class="rest-timer-modal" id="restTimerModal">
    <div class="timer-card">
        <h3 style="margin-bottom: 1rem;">Rest Timer</h3>
        <div class="timer-display" id="timerDisplay">02:00</div>
        <div style="margin: 1.5rem 0;">
            <select id="timerPreset" class="number-input" style="width: 100%;" onchange="updateTimerDisplay()">
                <option value="60">1 minute</option>
                <option value="90">1:30 minutes</option>
                <option value="120" selected>2 minutes</option>
                <option value="180">3 minutes</option>
                <option value="240">4 minutes</option>
            </select>
        </div>
        <div class="action-buttons">
            <button id="startTimer" class="action-button complete-set-btn" onclick="startTimer()">
                <i class="fas fa-play"></i> Start Timer
            </button>
            <button id="closeTimerModal" class="action-button watch-button" onclick="closeTimerModal()">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="success-modal" id="successModal">
    <div class="success-card">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3 style="margin: 0 0 1rem 0; color: #333;">Workout Completed!</h3>
        <p style="margin: 0 0 2rem 0; color: #666;">Great job! Your workout has been saved successfully.</p>
        <button class="action-button complete-set-btn" onclick="redirectToDashboard()" style="width: 100%;">
            <i class="fas fa-tachometer-alt"></i> Continue to Dashboard
        </button>
    </div>
</div>

<script>
    // Global variables
    let currentExerciseIndex = 0;
    let currentSet = 1;
    let totalSets = 4;
    let exercises = <?php echo json_encode($day_exercises); ?>;
    let completedSets = {};
    let timerInterval = null;
    let remainingSeconds = 120;
    let currentDay = <?php echo $selected_day; ?>;

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        updateUpcomingExercises();
        
        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Only handle shortcuts when exercise modal is open
            if (!document.getElementById('exerciseModal').classList.contains('active')) {
                return;
            }
            
            // Enter to complete set
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                completeCurrentSet();
            }
            
            // Space to play/pause timer
            if (e.key === ' ' && document.getElementById('restTimerModal').style.display === 'flex') {
                e.preventDefault();
                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                } else {
                    startTimer();
                }
            }
            
            // Arrow keys for navigation
            if (e.key === 'ArrowRight') {
                e.preventDefault();
                nextExercise();
            }
            
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                prevExercise();
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                if (document.getElementById('restTimerModal').style.display === 'flex') {
                    closeTimerModal();
                } else if (document.getElementById('videoModal').classList.contains('active')) {
                    closeVideoModal();
                } else {
                    closeExerciseModal();
                }
            }
        });
    });

    // Change day function
    function changeDay(dayNumber) {
        window.location.href = 'workout_plan.php?day=' + dayNumber;
    }

    // Open exercise modal
    function openExerciseModal(index) {
        currentExerciseIndex = index;
        currentSet = 1;
        
        const exercise = exercises[index];
        if (!exercise) return;
        
        // Update modal content
        document.getElementById('modalExerciseName').textContent = exercise.exercise_name;
        document.getElementById('currentSet').textContent = currentSet;
        document.getElementById('totalSets').textContent = exercise.default_sets;
        totalSets = exercise.default_sets;
        
        // Get last workout data for this exercise
        const lastWeight = exercise.last_weight || '';
        const lastReps = exercise.last_reps || exercise.default_reps;
        
        document.getElementById('weightInput').value = lastWeight;
        document.getElementById('repsInput').value = lastReps;
        
        // Update progress
        const completedCount = completedSets[exercise.id]?.completed || 0;
        updateProgress(completedCount, exercise.default_sets);
        
        // Setup YouTube video
        setupYouTubeVideo(exercise);
        
        // Update navigation buttons
        updateNavButtons();
        
        // Update upcoming exercises
        updateUpcomingExercises();
        
        // Show modal
        document.getElementById('exerciseModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Focus on weight input
        setTimeout(() => {
            document.getElementById('weightInput').focus();
        }, 300);
    }

    // Setup YouTube video
    function setupYouTubeVideo(exercise) {
        const demoVideo = document.getElementById('demoVideo');
        const videoInfo = document.getElementById('videoInfo');
        
        if (exercise.youtube_link) {
            // Extract YouTube video ID
            const url = exercise.youtube_link;
            let videoId = '';
            
            if (url.includes('youtu.be/')) {
                videoId = url.split('youtu.be/')[1].split('?')[0];
            } else if (url.includes('youtube.com/watch?v=')) {
                videoId = url.split('v=')[1].split('&')[0];
            } else if (url.includes('youtube.com/embed/')) {
                videoId = url.split('embed/')[1].split('?')[0];
            }
            
            if (videoId) {
                // Create YouTube embed URL
                const embedUrl = `https://www.youtube.com/embed/${videoId}?rel=0&modestbranding=1`;
                
                // Create iframe
                demoVideo.innerHTML = `
                    <iframe 
                        src="${embedUrl}" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                    </iframe>
                `;
                
                videoInfo.textContent = "Demo Video";
            } else {
                // Invalid YouTube URL
                demoVideo.innerHTML = `
                    <div class="video-placeholder">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Invalid YouTube URL</span>
                    </div>
                `;
                videoInfo.textContent = "Invalid video link";
            }
        } else {
            // No video available
            demoVideo.innerHTML = `
                <div class="video-placeholder">
                    <i class="fas fa-video-slash"></i>
                    <span>No demo video available</span>
                </div>
            `;
            videoInfo.textContent = "No demo available";
        }
    }

    // Open video modal
    function openVideoModal(videoId, exerciseName) {
        const videoModal = document.getElementById('videoModal');
        const iframe = document.getElementById('videoModalIframe');
        
        // Set iframe source with autoplay
        iframe.src = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0&modestbranding=1&showinfo=0`;
        
        // Show modal
        videoModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // Close video modal
    function closeVideoModal() {
        const videoModal = document.getElementById('videoModal');
        const iframe = document.getElementById('videoModalIframe');
        
        // Stop video
        iframe.src = iframe.src.replace('autoplay=1', 'autoplay=0');
        
        // Hide modal
        videoModal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    // Close exercise modal
    function closeExerciseModal() {
        document.getElementById('exerciseModal').classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    // Complete current set
    function completeCurrentSet() {
        const weight = document.getElementById('weightInput').value;
        const reps = document.getElementById('repsInput').value;
        
        if (!reps || parseInt(reps) <= 0) {
            alert('Please enter the number of reps completed.');
            return;
        }
        
        const exercise = exercises[currentExerciseIndex];
        if (!exercise) return;
        
        // Initialize completed sets for this exercise
        if (!completedSets[exercise.id]) {
            completedSets[exercise.id] = {
                completed: 0,
                sets: []
            };
        }
        
        // Save this set
        completedSets[exercise.id].sets.push({
            set: currentSet,
            weight: parseFloat(weight) || 0,
            reps: parseInt(reps)
        });
        
        completedSets[exercise.id].completed++;
        
        // Update progress
        updateProgress(completedSets[exercise.id].completed, exercise.default_sets);
        
        // Move to next set or show timer
        if (currentSet < exercise.default_sets) {
            currentSet++;
            document.getElementById('currentSet').textContent = currentSet;
            
            // Clear inputs for next set
            document.getElementById('weightInput').value = '';
            document.getElementById('repsInput').value = exercise.default_reps;
            
            // Focus on weight input
            document.getElementById('weightInput').focus();
            
            // Show rest timer
            showRestTimer();
        } else {
            // Exercise completed
            // Mark exercise as completed in list
            const exerciseItem = document.querySelector(`.exercise-item[data-exercise-index="${currentExerciseIndex}"]`);
            if (exerciseItem) {
                exerciseItem.classList.add('completed');
                const status = exerciseItem.querySelector('.exercise-status');
                if (status) {
                    status.textContent = 'Complete';
                    status.classList.add('complete');
                }
            }
            
            // Move to next exercise
            setTimeout(nextExercise, 500);
        }
        
        // Update exercise list status
        updateExerciseStatus();
        updateUpcomingExercises();
    }

    // Update progress bar
    function updateProgress(completed, total) {
        const percentage = (completed / total) * 100;
        document.getElementById('progressFill').style.width = percentage + '%';
        document.getElementById('progressText').textContent = `${completed}/${total} sets`;
    }

    // Navigate to next exercise
    function nextExercise() {
        if (currentExerciseIndex < exercises.length - 1) {
            currentExerciseIndex++;
            openExerciseModal(currentExerciseIndex);
        } else {
            // Last exercise completed
            if (confirm('You have completed all exercises! Would you like to finish the workout?')) {
                completeWorkout();
            }
        }
    }

    // Navigate to previous exercise
    function prevExercise() {
        if (currentExerciseIndex > 0) {
            currentExerciseIndex--;
            openExerciseModal(currentExerciseIndex);
        }
    }

    // Update navigation buttons
    function updateNavButtons() {
        document.getElementById('prevExercise').disabled = currentExerciseIndex === 0;
        document.getElementById('nextExercise').disabled = currentExerciseIndex === exercises.length - 1;
    }

    // Update exercise status in list
    function updateExerciseStatus() {
        exercises.forEach((exercise, index) => {
            const completed = completedSets[exercise.id]?.completed || 0;
            const exerciseItem = document.querySelector(`.exercise-item[data-exercise-index="${index}"]`);
            
            if (exerciseItem) {
                if (completed > 0) {
                    exerciseItem.classList.add('in-progress');
                    const status = exerciseItem.querySelector('.exercise-status');
                    if (status) {
                        status.textContent = `${completed}/${exercise.default_sets}`;
                        status.classList.add('in-progress');
                    }
                }
                
                if (completed === exercise.default_sets) {
                    exerciseItem.classList.remove('in-progress');
                    exerciseItem.classList.add('completed');
                    const status = exerciseItem.querySelector('.exercise-status');
                    if (status) {
                        status.textContent = 'Complete';
                        status.classList.remove('in-progress');
                        status.classList.add('complete');
                    }
                }
            }
        });
    }

    // Update upcoming exercises list
    function updateUpcomingExercises() {
        const upcomingList = document.getElementById('upcomingList');
        if (!upcomingList) return;
        
        upcomingList.innerHTML = '';
        
        exercises.forEach((exercise, index) => {
            const completed = completedSets[exercise.id]?.completed || 0;
            const total = exercise.default_sets;
            
            const item = document.createElement('div');
            item.className = 'upcoming-item';
            if (index === currentExerciseIndex) {
                item.classList.add('current');
            }
            
            item.innerHTML = `
                <div class="upcoming-name">${exercise.exercise_name}</div>
                <div class="upcoming-details">
                    <span>${total} × ${exercise.default_reps}</span>
                    <span>${completed}/${total} sets</span>
                </div>
            `;
            
            upcomingList.appendChild(item);
        });
    }

    // Show rest timer
    function showRestTimer() {
        remainingSeconds = parseInt(document.getElementById('timerPreset').value);
        updateTimerDisplay();
        document.getElementById('restTimerModal').style.display = 'flex';
    }

    // Close timer modal
    function closeTimerModal() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
        document.getElementById('restTimerModal').style.display = 'none';
    }

    // Start timer
    function startTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
        }
        
        remainingSeconds = parseInt(document.getElementById('timerPreset').value);
        
        timerInterval = setInterval(() => {
            remainingSeconds--;
            updateTimerDisplay();
            
            if (remainingSeconds <= 0) {
                clearInterval(timerInterval);
                timerInterval = null;
                
                // Play sound if available
                try {
                    const audio = new Audio('https://assets.mixkit.co/sfx/preview/mixkit-alarm-digital-clock-beep-989.mp3');
                    audio.play();
                } catch (e) {
                    // Sound not supported
                }
                
                // Vibrate if supported
                if (navigator.vibrate) {
                    navigator.vibrate([200, 100, 200]);
                }
                
                setTimeout(closeTimerModal, 1000);
            }
        }, 1000);
    }

    // Update timer display
    function updateTimerDisplay() {
        const minutes = Math.floor(remainingSeconds / 60);
        const seconds = remainingSeconds % 60;
        document.getElementById('timerDisplay').textContent = 
            `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }

    // Complete workout - UPDATED FOR MULTIPLE DAYS
    async function completeWorkout() {
        if (!confirm('Are you sure you want to complete this workout? All logged sets will be saved.')) {
            return;
        }
        
        // Get the form for the current day
        const formId = 'workoutForm-' + currentDay;
        const form = document.getElementById(formId);
        if (!form) {
            alert('Form not found for day ' + currentDay + '. Please refresh the page.');
            return;
        }
        
        // Clear all form values first
        const inputs = form.querySelectorAll('input[type="hidden"]');
        inputs.forEach(input => input.value = '');
        
        // Update form with completed sets
        let hasValidData = false;
        
        for (const [exerciseId, data] of Object.entries(completedSets)) {
            if (data.sets && data.sets.length > 0) {
                data.sets.forEach((set) => {
                    if (set.reps && set.reps > 0) {
                        hasValidData = true;
                        
                        const weightInput = document.getElementById(`form-weight-${currentDay}-${exerciseId}-${set.set}`);
                        const repsInput = document.getElementById(`form-reps-${currentDay}-${exerciseId}-${set.set}`);
                        
                        if (weightInput && repsInput) {
                            weightInput.value = set.weight || 0;
                            repsInput.value = set.reps || 0;
                        }
                    }
                });
            }
        }
        
        // Also add current set from modal if not saved yet
        const currentExercise = exercises[currentExerciseIndex];
        if (currentExercise) {
            const weight = document.getElementById('weightInput').value;
            const reps = document.getElementById('repsInput').value;
            
            if (reps && parseInt(reps) > 0) {
                hasValidData = true;
                
                const weightInput = document.getElementById(`form-weight-${currentDay}-${currentExercise.id}-${currentSet}`);
                const repsInput = document.getElementById(`form-reps-${currentDay}-${currentExercise.id}-${currentSet}`);
                
                if (weightInput && repsInput) {
                    weightInput.value = parseFloat(weight) || 0;
                    repsInput.value = parseInt(reps) || 0;
                }
            }
        }
        
        if (!hasValidData) {
            alert('Please enter at least one set of reps to complete your workout.');
            return;
        }
        
        // Create FormData
        const formData = new FormData(form);
        formData.append('ajax_complete_workout', 'true');
        
        // Show loading
        const completeBtn = document.getElementById('completeWorkout');
        const originalText = completeBtn.innerHTML;
        completeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        completeBtn.disabled = true;
        
        try {
            const response = await fetch('ajax_save_workout.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Show success modal
                document.getElementById('successModal').classList.add('active');
                document.getElementById('exerciseModal').classList.remove('active');
            } else {
                alert('Error: ' + result.message);
                completeBtn.innerHTML = originalText;
                completeBtn.disabled = false;
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Network error. Please try again.');
            completeBtn.innerHTML = originalText;
            completeBtn.disabled = false;
        }
    }

    // Redirect to dashboard
    function redirectToDashboard() {
        window.location.href = 'dashboard.php?workout_completed=true';
    }

    // Close success modal
    function closeSuccessModal() {
        document.getElementById('successModal').classList.remove('active');
    }
</script>

<?php require_once 'footer.php'; ?>