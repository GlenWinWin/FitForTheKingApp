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
    :root {
        --mobile-padding: 0.75rem;
        --mobile-font-sm: 0.85rem;
        --mobile-font-xs: 0.75rem;
        --section-spacing: 1rem;
        --border-radius: 12px;
        --card-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        --active-shadow: 0 4px 15px rgba(26, 35, 126, 0.15);
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

    /* Days Navigation - Mobile Optimized */
    .days-navigation-container {
        background: white;
        padding: 0.75rem var(--mobile-padding);
        border-bottom: 1px solid #e0e0e0;
        position: sticky;
        top: 125px; /* Adjust based on header height */
        z-index: 90;
    }

    .days-navigation {
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        padding: 0.25rem 0;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none; /* Firefox */
    }

    .days-navigation::-webkit-scrollbar {
        display: none; /* Chrome, Safari, Edge */
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

    .day-tab.current {
        position: relative;
    }

    .day-tab.current::after {
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

    .scroll-hint {
        text-align: center;
        font-size: 0.7rem;
        color: #999;
        margin-top: 0.5rem;
        opacity: 0.7;
    }

    /* Main Content Area */
    .main-content {
        padding: var(--mobile-padding);
        padding-bottom: 80px; /* Space for exercise tabs */
    }

    /* Exercise Navigation Section */
    .exercise-navigation-section {
        background: white;
        padding: 1rem;
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
        margin-top: 1rem;
        margin-bottom: 1.5rem;
    }

    .nav-controls {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .nav-button {
        flex: 1;
        padding: 0.8rem;
        background: white;
        border: 1.5px solid #3f51b5;
        border-radius: 8px;
        color: #3f51b5;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .nav-button:hover {
        background: #f8f9ff;
        transform: translateY(-1px);
    }

    .nav-button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }

    .nav-button.prev {
        border-color: #666;
        color: #666;
    }

    .nav-button.next {
        background: linear-gradient(135deg, #3f51b5 0%, #1a237e 100%);
        color: white;
        border: none;
    }

    .nav-button.complete-exercise {
        background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
        color: white;
        border: none;
        font-weight: 700;
        width: 100%;
        margin-top: 0.5rem;
        padding: 0.9rem;
        font-size: 1rem;
    }

    .exercise-counter {
        text-align: center;
        color: #666;
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
        font-weight: 500;
        padding: 0.5rem;
        background: #f8f9ff;
        border-radius: 8px;
        border: 1px solid #e8eaf6;
    }

    /* Exercise Tabs Navigation */
    .exercise-tabs-container {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        border-top: 1px solid #e0e0e0;
        z-index: 200;
        padding: 0.75rem var(--mobile-padding);
        box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }

    .exercise-tabs-container::-webkit-scrollbar {
        display: none;
    }

    .exercise-tab {
        flex: 0 0 auto;
        padding: 0.6rem 0.8rem;
        background: #f8f9ff;
        border: 1.5px solid #e8eaf6;
        border-radius: 8px;
        color: #3f51b5;
        font-weight: 600;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 0.3rem;
        min-width: 40px;
        justify-content: center;
    }

    .exercise-tab:hover {
        border-color: #3f51b5;
    }

    .exercise-tab.active {
        background: linear-gradient(135deg, #3f51b5 0%, #1a237e 100%);
        color: white;
        border-color: #3f51b5;
        box-shadow: var(--active-shadow);
    }

    .exercise-tab.complete-tab {
        background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
        color: white;
        border-color: #4CAF50;
        padding: 0.6rem 1.2rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .exercise-tab.complete-tab i {
        font-size: 0.9rem;
    }

    /* Day Content - Simplified */
    .day-content {
        display: none;
    }

    .day-content.active {
        display: block;
    }

    .day-header {
        margin-bottom: 1.5rem;
        padding: 1rem;
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
    }

    .day-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1a237e;
        margin-bottom: 0.5rem;
    }

    .day-subtitle {
        font-size: 0.9rem;
        color: #666;
        line-height: 1.4;
    }

    /* Progress Overview - Compact */
    .progress-overview {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
        margin-bottom: 1.5rem;
    }

    .progress-card {
        text-align: center;
        padding: 0.8rem 0.5rem;
        background: white;
        border-radius: 8px;
        box-shadow: var(--card-shadow);
    }

    .progress-value {
        font-size: 1.2rem;
        font-weight: 700;
        color: #3f51b5;
        margin-bottom: 0.25rem;
    }

    .progress-label {
        font-size: 0.75rem;
        color: #666;
        font-weight: 500;
    }

    /* Exercise Content */
    .exercise-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .exercise-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Exercise Media */
    .exercise-media {
        margin-bottom: 1.5rem;
    }

    .video-container {
        position: relative;
        padding-bottom: 56.25%;
        height: 0;
        overflow: hidden;
        border-radius: var(--border-radius);
        margin-bottom: 1rem;
        box-shadow: var(--card-shadow);
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
        background: rgba(63, 81, 181, 0.05);
        border-left: 3px solid #3f51b5;
        padding: 0.8rem;
        border-radius: 0 6px 6px 0;
        font-size: 0.85rem;
        color: #333;
        line-height: 1.4;
    }

    /* Sets Table - Mobile Optimized */
    .sets-table {
        width: 100%;
        background: white;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--card-shadow);
        margin-bottom: 1.5rem;
    }

    .table-header {
        background: linear-gradient(135deg, #3f51b5 0%, #1a237e 100%);
        padding: 0.8rem;
        display: grid;
        grid-template-columns: 50px 1fr 1fr 60px;
        gap: 0.5rem;
        align-items: center;
        font-weight: 600;
        color: white;
        font-size: 0.8rem;
    }

    .table-row {
        padding: 0.8rem;
        display: grid;
        grid-template-columns: 50px 1fr 1fr 60px;
        gap: 0.5rem;
        align-items: center;
        border-bottom: 1px solid #f0f0f0;
    }

    .table-row:last-child {
        border-bottom: none;
    }

    .set-number {
        font-weight: 600;
        color: #3f51b5;
        font-size: 0.85rem;
        text-align: center;
    }

    .input-field {
        width: 100%;
        padding: 0.5rem 0.4rem;
        border: 1.5px solid #e0e0e0;
        border-radius: 6px;
        background: white;
        color: #333;
        font-size: 0.85rem;
        transition: all 0.2s ease;
        text-align: center;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }

    .input-field:focus {
        border-color: #3f51b5;
        outline: none;
        box-shadow: 0 0 0 2px rgba(63, 81, 181, 0.1);
    }

    .input-field.progress-up {
        border-color: #4CAF50;
        background: rgba(76, 175, 80, 0.05);
    }

    .input-field.progress-down {
        border-color: #f44336;
        background: rgba(244, 67, 54, 0.05);
    }

    .timer-section {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .timer-button {
        width: 34px;
        height: 34px;
        background: linear-gradient(135deg, #3f51b5 0%, #1a237e 100%);
        border: none;
        border-radius: 50%;
        color: white;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .timer-button:hover {
        transform: scale(1.05);
    }

    /* History Button */
    .history-button {
        width: 100%;
        padding: 0.8rem;
        background: linear-gradient(135deg, #666 0%, #444 100%);
        border: none;
        border-radius: 8px;
        color: white;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-top: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .history-button:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    /* Timer Modal - Mobile Native */
    .timer-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
        padding: 1rem;
        backdrop-filter: blur(3px);
    }

    .timer-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        text-align: center;
        width: 100%;
        max-width: 300px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .timer-display {
        font-size: 2.5rem;
        font-weight: 700;
        color: #3f51b5;
        margin: 1rem 0;
        font-family: 'Courier New', monospace;
        letter-spacing: 2px;
    }

    .timer-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1.5rem;
    }

    .timer-actions .btn {
        flex: 1;
        padding: 0.8rem;
        font-size: 0.9rem;
    }

    /* Active Timer Overlay */
    .timer-active-overlay {
        position: fixed;
        top: 10px;
        right: 10px;
        background: linear-gradient(135deg, #3f51b5 0%, #1a237e 100%);
        color: white;
        padding: 0.8rem 1rem;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        z-index: 1001;
        display: none;
        align-items: center;
        gap: 0.8rem;
        font-weight: 600;
        font-size: 0.9rem;
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .timer-active-display {
        font-family: 'Courier New', monospace;
        font-size: 1rem;
        font-weight: 700;
    }

    /* History Modal - Mobile Native */
    .history-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
        padding: 1rem;
        backdrop-filter: blur(3px);
    }

    .history-modal-content {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        width: 100%;
        max-width: 400px;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .history-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e0e0e0;
    }

    .history-modal-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #333;
        margin: 0;
    }

    .history-exercise-name {
        color: #3f51b5;
        font-weight: 600;
    }

    .close-history {
        background: none;
        border: none;
        font-size: 1.2rem;
        color: #666;
        cursor: pointer;
        padding: 0.5rem;
    }

    /* Success Modal - Mobile Native */
    .success-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
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
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
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

    /* Button Styles */
    .btn {
        padding: 0.8rem 1.2rem;
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

    .btn-primary {
        background: linear-gradient(135deg, #3f51b5 0%, #1a237e 100%);
        color: white;
    }

    .btn-outline {
        background: white;
        color: #3f51b5;
        border: 1.5px solid #3f51b5;
    }

    .btn-success {
        background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
        color: white;
    }

    /* Tablet and Desktop */
    @media (min-width: 768px) {
        .workout-container {
            max-width: 768px;
            margin: 0 auto;
        }

        .exercise-tabs-container {
            max-width: 768px;
            margin: 0 auto;
            left: 50%;
            transform: translateX(-50%);
        }

        .days-navigation-container {
            top: 140px;
        }
    }

    @media (max-width: 380px) {
        .workout-meta {
            gap: 0.5rem;
        }

        .meta-item {
            font-size: 0.7rem;
            padding: 0.25rem 0.4rem;
        }

        .table-header,
        .table-row {
            grid-template-columns: 40px 1fr 1fr 50px;
            gap: 0.3rem;
            padding: 0.6rem;
        }

        .exercise-tab {
            padding: 0.5rem 0.6rem;
            font-size: 0.75rem;
            min-width: 35px;
        }

        .exercise-tab.complete-tab {
            padding: 0.5rem 0.8rem;
        }

        .nav-button {
            padding: 0.7rem;
            font-size: 0.85rem;
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
            <div class="meta-item">
                <i class="fas fa-clock"></i>
                <span>Current: Day <?php echo $current_day_index; ?></span>
            </div>
        </div>
    </div>

    <!-- Days Navigation -->
    <div class="days-navigation-container">
        <div class="days-navigation">
            <?php foreach ($all_days as $day): ?>
            <div class="day-tab <?php echo $day['day_order'] == $current_day_index ? 'active current' : ''; ?>" data-day="<?php echo $day['day_order']; ?>">
                Day <?php echo $day['day_order']; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="scroll-hint">← Scroll to see all days →</div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Days Content -->
        <?php foreach ($all_days as $day): 
            $day_exercises = $all_exercises[$day['day_order']];
            $total_exercises_day = count($day_exercises);
        ?>
        <div class="day-content <?php echo $day['day_order'] == $current_day_index ? 'active' : ''; ?>" id="day-<?php echo $day['day_order']; ?>">

            <!-- Day Header -->
            <div class="day-header">
                <h2 class="day-title"><?php echo htmlspecialchars($day['title']); ?></h2>
                <div class="day-subtitle"><?php echo htmlspecialchars($day['description']); ?></div>
            </div>

            <!-- Progress Overview -->
            <div class="progress-overview">
                <div class="progress-card">
                    <div class="progress-value">Day <?php echo $day['day_order']; ?></div>
                    <div class="progress-label">Program Day</div>
                </div>
                <div class="progress-card">
                    <div class="progress-value"><?php echo $total_exercises_day; ?></div>
                    <div class="progress-label">Exercises</div>
                </div>
                <div class="progress-card">
                    <div class="progress-value">
                        <?php
                        $day_sets = 0;
                        foreach ($day_exercises as $exercise) {
                            $day_sets += $exercise['default_sets'];
                        }
                        echo $day_sets;
                        ?>
                    </div>
                    <div class="progress-label">Total Sets</div>
                </div>
            </div>

            <!-- AJAX FORM FOR THIS DAY -->
            <form class="workout-form" id="workout-form-<?php echo $day['day_order']; ?>" data-day-id="<?php echo $day['id']; ?>">
                <input type="hidden" name="day_id" value="<?php echo $day['id']; ?>">

                <!-- Exercise Content -->
                <?php foreach ($day_exercises as $index => $exercise): 
                    $last_workout = $last_workout_data[$exercise['id']] ?? [];
                    $exercise_number = $index + 1;
                ?>
                <div class="exercise-content <?php echo $index == 0 ? 'active' : ''; ?>" 
                     id="exercise-<?php echo $day['day_order']; ?>-<?php echo $exercise_number; ?>"
                     data-exercise-number="<?php echo $exercise_number; ?>">
                    
                    <!-- Video and Notes -->
                    <?php if ($exercise['youtube_link'] || $exercise['notes']): ?>
                    <div class="exercise-media">
                        <?php if ($exercise['youtube_link']): ?>
                        <div class="video-container">
                            <iframe src="<?php echo htmlspecialchars($exercise['youtube_link']); ?>"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen></iframe>
                        </div>
                        <?php endif; ?>

                        <?php if ($exercise['notes']): ?>
                        <div class="exercise-notes">
                            <p><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($exercise['notes']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Sets Table -->
                    <div class="sets-table">
                        <!-- Table Header -->
                        <div class="table-header">
                            <div>SET</div>
                            <div>WEIGHT (kg)</div>
                            <div>REPS</div>
                            <div>TIMER</div>
                        </div>

                        <!-- Table Rows -->
                        <?php for ($i = 1; $i <= $exercise['default_sets']; $i++): 
                            $last_set = $last_workout[$i] ?? null;
                            $last_weight = $last_set['weight'] ?? 0;
                            $last_reps = $last_set['reps'] ?? 0;
                            
                            // Calculate suggested values for progressive overload
                            $suggested_weight = $last_weight ?? '';
                            $suggested_reps = $last_reps ?? '';
                        ?>
                        <div class="table-row">
                            <div class="set-number"><?php echo $i; ?></div>

                            <!-- Current Workout Inputs -->
                            <div>
                                <input type="number"
                                    name="sets[<?php echo $exercise['id']; ?>][<?php echo $i; ?>][weight]"
                                    class="input-field weight-input" placeholder="kg" step="0.5"
                                    min="0" value="<?php echo $suggested_weight; ?>"
                                    data-last-weight="<?php echo $last_weight; ?>">
                            </div>

                            <div>
                                <input type="number"
                                    name="sets[<?php echo $exercise['id']; ?>][<?php echo $i; ?>][reps]"
                                    class="input-field reps-input" placeholder="Reps" min="0"
                                    max="100" value="<?php echo $suggested_reps; ?>"
                                    data-last-reps="<?php echo $last_reps; ?>">
                            </div>

                            <div class="timer-section">
                                <button type="button" class="timer-button" data-set="<?php echo $exercise['id'] . '_' . $i; ?>">
                                    <i class="fas fa-clock"></i>
                                </button>
                            </div>
                        </div>
                        <?php endfor; ?>
                        
                        <!-- History Button -->
                        <div style="grid-column: 1 / -1; padding: 0.8rem;">
                            <button type="button" class="history-button" 
                                    data-exercise-id="<?php echo $exercise['id']; ?>"
                                    data-exercise-name="<?php echo htmlspecialchars($exercise['exercise_name']); ?>">
                                <i class="fas fa-history"></i> View Workout History
                            </button>
                        </div>
                    </div>

                    <!-- Exercise Navigation Section (below history button) -->
                    <div class="exercise-navigation-section" id="exercise-nav-<?php echo $day['day_order']; ?>-<?php echo $exercise_number; ?>" style="display: <?php echo $exercise_number === 1 && $day['day_order'] == $current_day_index ? 'block' : 'none'; ?>;">
                        <div class="exercise-counter" id="exercise-counter-<?php echo $day['day_order']; ?>-<?php echo $exercise_number; ?>">
                            Exercise <?php echo $exercise_number; ?> of <?php echo $total_exercises_day; ?>
                        </div>
                        
                        <div class="nav-controls">
                            <button type="button" class="nav-button prev" data-day="<?php echo $day['day_order']; ?>" data-exercise="<?php echo $exercise_number; ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </button>
                            <button type="button" class="nav-button next" data-day="<?php echo $day['day_order']; ?>" data-exercise="<?php echo $exercise_number; ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        
                        <button type="button" class="nav-button complete-exercise" data-day="<?php echo $day['day_order']; ?>" data-action="complete">
                            <i class="fas fa-check-circle"></i> Complete Day <?php echo $day['day_order']; ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </form>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Exercise Tabs Navigation (Fixed at bottom) -->
    <div class="exercise-tabs-container" id="exerciseTabsContainer">
        <?php foreach ($all_days as $day): 
            $day_exercises = $all_exercises[$day['day_order']];
            if ($day['day_order'] != $current_day_index) continue;
        ?>
            <?php foreach ($day_exercises as $index => $exercise): ?>
                <div class="exercise-tab <?php echo $index == 0 ? 'active' : ''; ?>" 
                     data-day="<?php echo $day['day_order']; ?>"
                     data-exercise="<?php echo $index + 1; ?>">
                    <i class="fas fa-dumbbell"></i>
                    <span><?php echo $index + 1; ?></span>
                </div>
            <?php endforeach; ?>
            <!-- Complete Button as Last Tab -->
            <div class="exercise-tab complete-tab" data-day="<?php echo $day['day_order']; ?>" data-action="complete-tab">
                <i class="fas fa-check-circle"></i>
                <span>Complete</span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Active Timer Overlay -->
<div class="timer-active-overlay" id="timerActiveOverlay">
    <div>
        <i class="fas fa-clock"></i>
        <span class="timer-active-display" id="timerActiveDisplay">00:00</span>
    </div>
    <button type="button" class="btn btn-outline" id="stopActiveTimer">
        <i class="fas fa-stop"></i> Stop
    </button>
</div>

<!-- Rest Timer Modal -->
<div class="timer-modal" id="restTimerModal">
    <div class="timer-card">
        <h3 style="margin-bottom: 1rem;">Rest Timer</h3>

        <div class="timer-display" id="timerDisplay">02:00</div>

        <div style="margin: 1.5rem 0;">
            <label style="display: block; margin-bottom: 0.5rem; color: #666;">Rest Time</label>
            <select id="timerPreset" class="input-field" style="width: 100%; text-align: center;">
                <option value="60">1 minute</option>
                <option value="90">1:30 minutes</option>
                <option value="120" selected>2 minutes</option>
                <option value="180">3 minutes</option>
                <option value="240">4 minutes</option>
            </select>
        </div>

        <div class="timer-actions">
            <button id="startTimer" class="btn btn-primary">
                <i class="fas fa-play"></i> Start
            </button>
            <button id="closeTimerModal" class="btn btn-outline">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
</div>

<!-- History Modal -->
<div class="history-modal" id="historyModal">
    <div class="history-modal-content">
        <div class="history-modal-header">
            <h3 class="history-modal-title">
                Workout History: <span class="history-exercise-name" id="historyExerciseName"></span>
            </h3>
            <button type="button" class="close-history" id="closeHistoryModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="history-sessions" id="historySessions">
            <!-- History content will be loaded here -->
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
        <p style="margin: 0 0 2rem 0; color: #666;">Great job! Your workout has been saved successfully.
        </p>
        <button id="successContinue" class="btn btn-primary" style="width: 100%;">
            <i class="fas fa-tachometer-alt"></i> Continue to Dashboard
        </button>
    </div>
</div>

<script>
    // Mobile navigation enhancements
    function setupMobileNavigation() {
        const daysNav = document.querySelector('.days-navigation');
        const exerciseTabs = document.querySelector('.exercise-tabs-container');
        const scrollHint = document.querySelector('.scroll-hint');

        // Days navigation scrolling
        if (daysNav) {
            const checkScrollNeeded = () => {
                const isScrollable = daysNav.scrollWidth > daysNav.clientWidth;
                if (isScrollable) {
                    scrollHint.style.display = 'block';
                } else {
                    scrollHint.style.display = 'none';
                }
            };

            checkScrollNeeded();
            window.addEventListener('resize', checkScrollNeeded);

            // Auto-scroll to current day
            const currentTab = daysNav.querySelector('.day-tab.current');
            if (currentTab) {
                setTimeout(() => {
                    const scrollPosition = currentTab.offsetLeft - (daysNav.clientWidth / 2) + (currentTab.clientWidth / 2);
                    daysNav.scrollTo({
                        left: scrollPosition,
                        behavior: 'smooth'
                    });
                }, 300);
            }
        }

        // Exercise tabs scrolling
        if (exerciseTabs) {
            const checkExerciseScroll = () => {
                const isScrollable = exerciseTabs.scrollWidth > exerciseTabs.clientWidth;
                // Auto-scroll to active exercise
                const activeTab = exerciseTabs.querySelector('.exercise-tab.active');
                if (activeTab && isScrollable) {
                    setTimeout(() => {
                        const scrollPosition = activeTab.offsetLeft - (exerciseTabs.clientWidth / 2) + (activeTab.clientWidth / 2);
                        exerciseTabs.scrollTo({
                            left: scrollPosition,
                            behavior: 'smooth'
                        });
                    }, 100);
                }
            };

            setTimeout(checkExerciseScroll, 300);
            window.addEventListener('resize', checkExerciseScroll);
        }
    }

    // Day navigation
    document.querySelectorAll('.day-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const dayNumber = this.getAttribute('data-day');

            // Update active states
            document.querySelectorAll('.day-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.day-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.exercise-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.exercise-navigation-section').forEach(c => c.style.display = 'none');

            this.classList.add('active');
            const dayContent = document.getElementById('day-' + dayNumber);
            dayContent.classList.add('active');

            // Show first exercise of new day
            const firstExercise = dayContent.querySelector('.exercise-content');
            if (firstExercise) {
                firstExercise.classList.add('active');
            }

            // Show navigation for first exercise
            const firstNav = document.getElementById(`exercise-nav-${dayNumber}-1`);
            if (firstNav) {
                firstNav.style.display = 'block';
            }

            // Update exercise tabs for this day
            updateExerciseTabs(dayNumber);

            // Close modals
            hideTimerModal();
            document.getElementById('historyModal').style.display = 'none';
            
            // Update counter for first exercise
            updateExerciseCounter(dayNumber, 1);
        });
    });

    // Exercise navigation
    function setupExerciseNavigation() {
        // Previous button
        document.addEventListener('click', function(e) {
            if (e.target.closest('.nav-button.prev')) {
                const button = e.target.closest('.nav-button.prev');
                const day = button.getAttribute('data-day');
                const exercise = parseInt(button.getAttribute('data-exercise'));
                navigateToExercise(day, exercise, -1);
            }
        });

        // Next button
        document.addEventListener('click', function(e) {
            if (e.target.closest('.nav-button.next')) {
                const button = e.target.closest('.nav-button.next');
                const day = button.getAttribute('data-day');
                const exercise = parseInt(button.getAttribute('data-exercise'));
                navigateToExercise(day, exercise, +1);
            }
        });

        // Complete button (in navigation section)
        document.addEventListener('click', function(e) {
            if (e.target.closest('.nav-button.complete-exercise')) {
                const button = e.target.closest('.nav-button.complete-exercise');
                const day = button.getAttribute('data-day');
                submitWorkout(day);
            }
        });

        // Complete button (in bottom tabs)
        document.addEventListener('click', function(e) {
            if (e.target.closest('.exercise-tab.complete-tab')) {
                const tab = e.target.closest('.exercise-tab.complete-tab');
                const day = tab.getAttribute('data-day');
                submitWorkout(day);
            }
        });
    }

    function navigateToExercise(day, currentExercise, direction) {
        const totalExercises = document.querySelectorAll(`#day-${day} .exercise-content`).length;
        const newExercise = currentExercise + direction;

        // Check bounds
        if (newExercise < 1 || newExercise > totalExercises) {
            return;
        }

        // Switch to exercise
        switchToExercise(day, newExercise);
    }

    function switchToExercise(day, exercise) {
        const totalExercises = document.querySelectorAll(`#day-${day} .exercise-content`).length;
        
        // Hide all exercise content for this day
        document.querySelectorAll(`#day-${day} .exercise-content`).forEach(content => {
            content.classList.remove('active');
        });
        
        // Hide all navigation sections for this day
        document.querySelectorAll(`.exercise-navigation-section[id^="exercise-nav-${day}-"]`).forEach(nav => {
            nav.style.display = 'none';
        });

        // Show selected exercise
        const exerciseContent = document.getElementById(`exercise-${day}-${exercise}`);
        if (exerciseContent) {
            exerciseContent.classList.add('active');
            // Scroll to top of exercise
            exerciseContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Show navigation for this exercise
        const exerciseNav = document.getElementById(`exercise-nav-${day}-${exercise}`);
        if (exerciseNav) {
            exerciseNav.style.display = 'block';
        }

        // Update active tab
        document.querySelectorAll('.exercise-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        const activeTab = document.querySelector(`.exercise-tab[data-day="${day}"][data-exercise="${exercise}"]`);
        if (activeTab) {
            activeTab.classList.add('active');
            // Scroll tab into view
            activeTab.scrollIntoView({ behavior: 'smooth', inline: 'center' });
        }

        // Update counters
        updateExerciseCounter(day, exercise);
        
        // Update navigation buttons state
        updateNavButtons(day, exercise, totalExercises);
    }

    function updateNavButtons(day, currentExercise, totalExercises) {
        // Update previous button
        const prevButton = document.querySelector(`.nav-button.prev[data-day="${day}"][data-exercise="${currentExercise}"]`);
        if (prevButton) {
            prevButton.disabled = currentExercise === 1;
            // Update data-exercise attribute for the new position
            prevButton.setAttribute('data-exercise', currentExercise);
        }
        
        // Update next button
        const nextButton = document.querySelector(`.nav-button.next[data-day="${day}"][data-exercise="${currentExercise}"]`);
        if (nextButton) {
            nextButton.disabled = currentExercise === totalExercises;
            // Update data-exercise attribute for the new position
            nextButton.setAttribute('data-exercise', currentExercise);
        }
    }

    function updateExerciseCounter(day, currentExercise) {
        const totalExercises = document.querySelectorAll(`#day-${day} .exercise-content`).length;
        const counter = document.getElementById(`exercise-counter-${day}-${currentExercise}`);
        if (counter) {
            counter.textContent = `Exercise ${currentExercise} of ${totalExercises}`;
        }
    }

    // Exercise tabs navigation
    function setupExerciseTabs() {
        const tabsContainer = document.getElementById('exerciseTabsContainer');
        
        tabsContainer.addEventListener('click', function(e) {
            const tab = e.target.closest('.exercise-tab');
            if (!tab) return;

            const day = tab.getAttribute('data-day');
            const exercise = tab.getAttribute('data-exercise');
            const action = tab.getAttribute('data-action');

            if (action === 'complete-tab') {
                // Handle complete workout from bottom tab
                submitWorkout(day);
            } else if (exercise) {
                // Switch to specific exercise
                const exerciseNum = parseInt(exercise);
                const totalExercises = document.querySelectorAll(`#day-${day} .exercise-content`).length;
                
                switchToExercise(day, exerciseNum);
                updateNavButtons(day, exerciseNum, totalExercises);
            }
        });
    }

    function updateExerciseTabs(day) {
        const tabsContainer = document.getElementById('exerciseTabsContainer');
        const activeDay = document.querySelector('.day-content.active');
        const dayExercises = activeDay.querySelectorAll('.exercise-content');
        const totalExercises = dayExercises.length;
        
        // Clear existing tabs
        tabsContainer.innerHTML = '';

        // Add exercise tabs
        dayExercises.forEach((exercise, index) => {
            const exerciseNum = index + 1;
            const tab = document.createElement('div');
            tab.className = `exercise-tab ${index === 0 ? 'active' : ''}`;
            tab.setAttribute('data-day', day);
            tab.setAttribute('data-exercise', exerciseNum);
            tab.innerHTML = `
                <i class="fas fa-dumbbell"></i>
                <span>${exerciseNum}</span>
            `;
            tabsContainer.appendChild(tab);
        });

        // Add complete tab
        const completeTab = document.createElement('div');
        completeTab.className = 'exercise-tab complete-tab';
        completeTab.setAttribute('data-day', day);
        completeTab.setAttribute('data-action', 'complete-tab');
        completeTab.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <span>Complete</span>
        `;
        tabsContainer.appendChild(completeTab);

        // Re-attach event listeners
        setupExerciseTabs();
        
        // Update navigation buttons for first exercise
        updateNavButtons(day, 1, totalExercises);
    }

    // Progressive overload indicators
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('weight-input') || e.target.classList.contains('reps-input')) {
            const lastValue = parseFloat(e.target.getAttribute('data-last-weight') || e.target.getAttribute('data-last-reps'));
            const currentValue = parseFloat(e.target.value) || 0;

            if (lastValue > 0) {
                e.target.classList.remove('progress-up', 'progress-down');
                
                if (currentValue > lastValue) {
                    e.target.classList.add('progress-up');
                } else if (currentValue < lastValue) {
                    e.target.classList.add('progress-down');
                }
            }
        }
    });

    // Rest timer functionality
    let timerInterval;
    let remainingSeconds = 0;

    // Timer buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.timer-button')) {
            showTimerModal();
        }
    });

    // Timer control buttons
    document.getElementById('startTimer').addEventListener('click', startTimer);
    document.getElementById('closeTimerModal').addEventListener('click', hideTimerModal);
    document.getElementById('stopActiveTimer').addEventListener('click', stopActiveTimer);

    // Timer preset
    document.getElementById('timerPreset').addEventListener('change', function() {
        updateTimerDisplay(parseInt(this.value));
    });

    function showTimerModal() {
        const modal = document.getElementById('restTimerModal');
        modal.style.display = 'flex';
        updateTimerDisplay(parseInt(document.getElementById('timerPreset').value));
    }

    function hideTimerModal() {
        const modal = document.getElementById('restTimerModal');
        modal.style.display = 'none';
    }

    function updateTimerDisplay(seconds) {
        const display = document.getElementById('timerDisplay');
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        display.textContent = `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    function startTimer() {
        const seconds = parseInt(document.getElementById('timerPreset').value);
        remainingSeconds = seconds;

        // Show active timer overlay
        const overlay = document.getElementById('timerActiveOverlay');
        overlay.style.display = 'flex';

        updateActiveTimerDisplay();
        hideTimerModal();

        timerInterval = setInterval(updateActiveTimer, 1000);
    }

    function updateActiveTimer() {
        remainingSeconds--;
        updateActiveTimerDisplay();

        if (remainingSeconds <= 0) {
            completeTimer();
        }
    }

    function updateActiveTimerDisplay() {
        const display = document.getElementById('timerActiveDisplay');
        const minutes = Math.floor(remainingSeconds / 60);
        const seconds = remainingSeconds % 60;
        display.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }

    function stopActiveTimer() {
        clearInterval(timerInterval);
        const overlay = document.getElementById('timerActiveOverlay');
        overlay.style.display = 'none';
    }

    function completeTimer() {
        stopActiveTimer();
        playCompletionSound();
        showCompletionNotification();
    }

    function playCompletionSound() {
        // Simple beep using Web Audio API
        try {
            const context = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = context.createOscillator();
            const gainNode = context.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(context.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, context.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, context.currentTime + 0.5);
            
            oscillator.start(context.currentTime);
            oscillator.stop(context.currentTime + 0.5);
        } catch (e) {
            // Sound not supported
        }
    }

    function showCompletionNotification() {
        // Simple vibration if supported
        if (navigator.vibrate) {
            navigator.vibrate([200]);
        }
        
        // Visual feedback
        const overlay = document.getElementById('timerActiveOverlay');
        overlay.style.backgroundColor = '#4CAF50';
        
        setTimeout(() => {
            overlay.style.backgroundColor = '';
            overlay.style.display = 'none';
        }, 1000);
    }

    // History modal functionality
    function setupHistoryButtons() {
        document.addEventListener('click', function(e) {
            if (e.target.closest('.history-button')) {
                const button = e.target.closest('.history-button');
                const exerciseId = button.getAttribute('data-exercise-id');
                const exerciseName = button.getAttribute('data-exercise-name');
                showHistoryModal(exerciseId, exerciseName);
            }
        });
    }

    function showHistoryModal(exerciseId, exerciseName) {
        const modal = document.getElementById('historyModal');
        const exerciseNameElement = document.getElementById('historyExerciseName');
        const sessionsContainer = document.getElementById('historySessions');
        
        exerciseNameElement.textContent = exerciseName;
        
        // Show loading
        sessionsContainer.innerHTML = `
            <div style="text-align: center; padding: 2rem;">
                <i class="fas fa-spinner fa-spin" style="font-size: 1.5rem; color: #3f51b5;"></i>
                <p style="margin-top: 1rem; color: #666;">Loading history...</p>
            </div>
        `;
        
        modal.style.display = 'flex';
        loadExerciseHistory(exerciseId, sessionsContainer);
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
                displayHistorySessions(data.history, container);
            } else {
                container.innerHTML = `
                    <div style="text-align: center; padding: 3rem 1rem;">
                        <i class="fas fa-chart-line" style="font-size: 2.5rem; color: #ccc; margin-bottom: 1rem;"></i>
                        <h3 style="margin: 0 0 0.5rem 0; color: #333;">No Workout History</h3>
                        <p style="color: #666; font-size: 0.9rem;">Complete this exercise to start tracking your progress!</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading history:', error);
            container.innerHTML = `
                <div style="text-align: center; padding: 3rem 1rem;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2.5rem; color: #f44336; margin-bottom: 1rem;"></i>
                    <h3 style="margin: 0 0 0.5rem 0; color: #333;">Error Loading History</h3>
                    <p style="color: #666; font-size: 0.9rem;">Please try again later.</p>
                </div>
            `;
        });
    }

    function displayHistorySessions(sessions, container) {
        let html = '';
        
        sessions.forEach((session, index) => {
            const date = new Date(session.completed_at);
            const formattedDate = date.toLocaleDateString('en-US', { 
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
            
            html += `
                <div style="padding: 1rem 0; ${index !== sessions.length - 1 ? 'border-bottom: 1px solid #eee;' : ''}">
                    <div style="font-weight: 600; color: #333; font-size: 0.9rem; margin-bottom: 0.5rem;">
                        ${formattedDate}
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                        ${session.sets.map(set => {
                            if (set.weight === null || set.weight === 0) {
                                return `
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.25rem 0;">
                                        <span style="font-weight: 500; color: #333; font-size: 0.85rem;">Set ${set.set_number}</span>
                                        <span style="color: #2196F3; font-weight: 600; font-size: 0.85rem;">${set.reps} reps</span>
                                    </div>
                                `;
                            } else {
                                return `
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.25rem 0;">
                                        <span style="font-weight: 500; color: #333; font-size: 0.85rem;">Set ${set.set_number}</span>
                                        <span style="color: #333; font-size: 0.85rem;">
                                            <span style="color: #2196F3; font-weight: 600;">${set.reps}</span> × 
                                            <span style="color: #f44336; font-weight: 600; margin-left: 0.25rem;">${set.weight}kg</span>
                                        </span>
                                    </div>
                                `;
                            }
                        }).join('')}
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }

    // Close history modal
    document.getElementById('closeHistoryModal').addEventListener('click', function() {
        document.getElementById('historyModal').style.display = 'none';
    });

    // Close modal when clicking outside
    document.getElementById('historyModal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });

    // Workout submission
    function submitWorkout(day) {
        const form = document.getElementById('workout-form-' + day);
        const completeButton = document.querySelector(`.nav-button.complete-exercise[data-day="${day}"]`);
        const completeTab = document.querySelector(`.exercise-tab.complete-tab[data-day="${day}"]`);
        
        if (!form) return;

        // Check if we have at least one rep value
        const repInputs = form.querySelectorAll('input[name*="[reps]"]');
        let hasData = false;

        repInputs.forEach(input => {
            if (input.value.trim() !== '' && parseInt(input.value) > 0) {
                hasData = true;
            }
        });

        if (!hasData) {
            alert('Please enter at least one set of reps to complete your workout.');
            return;
        }

        // Get form data
        const formData = new FormData(form);
        const dayId = form.getAttribute('data-day-id');
        
        // Show loading state on complete button
        if (completeButton) {
            const originalHTML = completeButton.innerHTML;
            completeButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            completeButton.disabled = true;
        }
        
        // Show loading state on complete tab
        if (completeTab) {
            const originalHTML = completeTab.innerHTML;
            completeTab.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            completeTab.disabled = true;
        }

        // Add additional data
        formData.append('ajax_complete_workout', 'true');
        formData.append('day_id', dayId);

        // Send AJAX request
        fetch('ajax_save_workout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Server returned invalid JSON');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    showSuccessModal();
                } else {
                    alert(data.message || 'Error saving workout. Please try again.');
                    // Reset buttons
                    if (completeButton) {
                        completeButton.innerHTML = '<i class="fas fa-check-circle"></i> Complete Day ' + day;
                        completeButton.disabled = false;
                    }
                    if (completeTab) {
                        completeTab.innerHTML = '<i class="fas fa-check-circle"></i> Complete';
                        completeTab.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving workout. Please try again.');
                // Reset buttons
                if (completeButton) {
                    completeButton.innerHTML = '<i class="fas fa-check-circle"></i> Complete Day ' + day;
                    completeButton.disabled = false;
                }
                if (completeTab) {
                    completeTab.innerHTML = '<i class="fas fa-check-circle"></i> Complete';
                    completeTab.disabled = false;
                }
            });
    }

    // Success modal functionality
    function showSuccessModal() {
        const modal = document.getElementById('successModal');
        modal.style.display = 'flex';
        createConfetti();
    }

    document.getElementById('successContinue').addEventListener('click', function() {
        window.location.href = 'dashboard.php?message=workout_completed';
    });

    // Confetti effect for success
    function createConfetti() {
        const colors = ['#4CAF50', '#2196F3', '#FF9800', '#E91E63', '#9C27B0'];
        const container = document.body;

        for (let i = 0; i < 30; i++) {
            const confetti = document.createElement('div');
            confetti.style.cssText = `
                position: fixed;
                width: 8px;
                height: 8px;
                background: ${colors[Math.floor(Math.random() * colors.length)]};
                top: -10px;
                left: ${Math.random() * 100}vw;
                border-radius: 50%;
                opacity: 0.8;
                z-index: 1002;
                pointer-events: none;
            `;

            container.appendChild(confetti);

            // Animation
            const animation = confetti.animate([{
                    transform: 'translateY(0) rotate(0deg)',
                    opacity: 1
                },
                {
                    transform: `translateY(${window.innerHeight}px) rotate(${360 + Math.random() * 360}deg)`,
                    opacity: 0
                }
            ], {
                duration: 1000 + Math.random() * 2000,
                easing: 'cubic-bezier(0.1, 0.8, 0.2, 1)'
            });

            animation.onfinish = () => confetti.remove();
        }
    }

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        setupMobileNavigation();
        setupExerciseNavigation();
        setupExerciseTabs();
        setupHistoryButtons();

        // Open first exercise by default
        const activeDay = document.querySelector('.day-content.active');
        if (activeDay) {
            const firstExercise = activeDay.querySelector('.exercise-content');
            if (firstExercise) {
                firstExercise.classList.add('active');
            }
            
            // Initialize navigation for current day
            const day = activeDay.id.replace('day-', '');
            const totalExercises = activeDay.querySelectorAll('.exercise-content').length;
            updateNavButtons(day, 1, totalExercises);
        }

        // Re-check navigation on orientation change
        window.addEventListener('orientationchange', function() {
            setTimeout(setupMobileNavigation, 100);
        });

        // Handle back button
        window.addEventListener('popstate', function() {
            // Refresh the page to get updated day calculation
            window.location.reload();
        });

        // Add touch support for better mobile UX
        if ('ontouchstart' in window) {
            document.documentElement.style.cursor = 'pointer';
        }
    });

    // Handle keyboard navigation
    document.addEventListener('keydown', function(e) {
        // Tab navigation between inputs
        if (e.key === 'Enter' && e.target.classList.contains('input-field')) {
            e.preventDefault();
            const inputs = Array.from(document.querySelectorAll('.input-field'));
            const currentIndex = inputs.indexOf(e.target);
            if (currentIndex < inputs.length - 1) {
                inputs[currentIndex + 1].focus();
            }
        }
        
        // Arrow key navigation between exercises
        if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
            e.preventDefault();
            const activeDay = document.querySelector('.day-content.active');
            if (activeDay) {
                const day = activeDay.id.replace('day-', '');
                const activeExercise = activeDay.querySelector('.exercise-content.active');
                if (activeExercise) {
                    const currentExercise = parseInt(activeExercise.getAttribute('data-exercise-number'));
                    const direction = e.key === 'ArrowLeft' ? -1 : 1;
                    navigateToExercise(day, currentExercise, direction);
                }
            }
        }
    });
</script>

<?php require_once 'footer.php'; ?>