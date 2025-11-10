<?php
$pageTitle = "Workout Plan";
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
$days_query = "SELECT * FROM workout_plan_days WHERE plan_id = ? ORDER BY day_order";
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

// Get last workout data for progressive overload
$last_workout_data = [];
foreach ($all_exercises as $exercises) {
    foreach ($exercises as $exercise) {
        $limit_sets = (int)$exercise['default_sets'];
        $last_workout_query = "SELECT wls.*, wl.completed_at 
                              FROM workout_log_sets wls
                              JOIN workout_logs wl ON wls.workout_log_id = wl.id
                              WHERE wl.user_id = ? AND wl.exercise_id = ?
                              ORDER BY wl.completed_at DESC, wls.set_number ASC
                              LIMIT $limit_sets";
        $stmt = $db->prepare($last_workout_query);
        $stmt->execute([$user_id, $exercise['id']]);
        $last_workout_data[$exercise['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<style>
    .workout-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem;
    }
    
    .workout-header {
        text-align: center;
        margin-bottom: 1.5rem;
        padding: 1.5rem;
        background: var(--glass-bg);
        border-radius: var(--radius);
        border: 1px solid var(--glass-border);
    }
    
    .workout-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 0.5rem;
    }
    
    .workout-subtitle {
        font-size: 1rem;
        color: var(--light-text);
        margin-bottom: 1rem;
    }
    
    .workout-meta {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--light-text);
        font-size: 0.9rem;
    }
    
    /* Days Navigation - FIXED FOR MOBILE */
    .days-navigation {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 2rem;
        overflow-x: auto;
        padding: 0.5rem 0;
        scrollbar-width: thin;
        scrollbar-color: var(--accent) transparent;
        -webkit-overflow-scrolling: touch;
    }
    
    .days-navigation::-webkit-scrollbar {
        height: 6px;
    }
    
    .days-navigation::-webkit-scrollbar-track {
        background: transparent;
        border-radius: 10px;
    }
    
    .days-navigation::-webkit-scrollbar-thumb {
        background: var(--accent);
        border-radius: 10px;
    }
    
    .days-navigation::-webkit-scrollbar-thumb:hover {
        background: var(--accent-dark);
    }
    
    .day-tab {
        flex: 0 0 auto;
        padding: 1rem 1rem;
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius);
        color: var(--light-text);
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        white-space: nowrap;
        text-align: center;
        min-width: 100px;
        font-size: 0.9rem;
    }
    
    .day-tab:hover {
        background: rgba(26, 35, 126, 0.05);
        color: var(--text);
    }
    
    .day-tab.active {
        background: var(--gradient-accent);
        color: white;
        border-color: var(--accent);
    }
    
    .day-tab.current {
        position: relative;
    }
    
    .day-tab.current::after {
        content: 'TODAY';
        position: absolute;
        top: -6px;
        right: -4px;
        background: #4CAF50;
        color: white;
        font-size: 0.6rem;
        padding: 2px 4px;
        border-radius: 8px;
        font-weight: 700;
    }
    
    .day-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }
    
    .day-content.active {
        display: block;
    }
    
    .day-header {
        text-align: center;
        margin-bottom: 2rem;
        padding: 1.5rem;
        background: var(--glass-bg);
        border-radius: var(--radius);
        border: 1px solid var(--glass-border);
    }
    
    .day-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 0.5rem;
    }
    
    .day-subtitle {
        font-size: 1rem;
        color: var(--light-text);
        margin-bottom: 1rem;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Progress Overview */
    .progress-overview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .progress-card {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius);
        padding: 1.5rem;
        text-align: center;
    }
    
    .progress-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--accent);
        margin-bottom: 0.5rem;
    }
    
    .progress-label {
        font-size: 0.9rem;
        color: var(--light-text);
    }
    
    /* Exercise Cards */
    .exercise-cards {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .exercise-card {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius);
        overflow: hidden;
    }
    
    .exercise-card-header {
        padding: 1.5rem;
        background: rgba(26, 35, 126, 0.05);
        border-bottom: 1px solid var(--glass-border);
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .exercise-card-header h3 {
        margin: 0;
        font-size: 1.2rem;
        color: var(--text);
        flex: 1;
    }
    
    .exercise-toggle {
        background: none;
        border: none;
        color: var(--accent);
        font-size: 1.2rem;
        cursor: pointer;
        padding: 0.5rem;
    }
    
    .exercise-card-content {
        display: none;
        padding: 0;
    }
    
    .exercise-card-content.active {
        display: block;
    }
    
    /* Video and Notes */
    .exercise-media {
        padding: 1.5rem;
        border-bottom: 1px solid var(--glass-border);
    }
    
    .video-container {
        position: relative;
        padding-bottom: 56.25%;
        height: 0;
        overflow: hidden;
        border-radius: var(--radius);
        margin-bottom: 1rem;
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
        background: rgba(26, 35, 126, 0.05);
        border-left: 4px solid var(--accent);
        padding: 1rem;
        border-radius: 0 var(--radius) var(--radius) 0;
        margin-top: 1rem;
    }
    
    .exercise-notes p {
        margin: 0;
        color: var(--text);
        line-height: 1.5;
        font-size: 0.9rem;
    }
    
    /* Sets Table */
    .sets-section {
        padding: 1.5rem;
    }
    
    .sets-table {
        width: 100%;
        background: white;
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
    }
    
    .table-header {
        background: var(--gradient-primary);
        padding: 1rem;
        display: grid;
        grid-template-columns: 60px 1fr 1fr 1fr 80px;
        gap: 1rem;
        align-items: center;
        font-weight: 600;
        color: var(--text);
        border-bottom: 1px solid var(--glass-border);
        font-size: 0.9rem;
    }
    
    .table-row {
        padding: 1rem;
        display: grid;
        grid-template-columns: 60px 1fr 1fr 1fr 80px;
        gap: 1rem;
        align-items: center;
        border-bottom: 1px solid var(--glass-border);
        transition: var(--transition);
    }
    
    .table-row:last-child {
        border-bottom: none;
    }
    
    .table-row:hover {
        background: rgba(26, 35, 126, 0.02);
    }
    
    .set-number {
        font-weight: 600;
        color: var(--accent);
        font-size: 1rem;
        text-align: center;
    }
    
    .last-workout {
        background: rgba(76, 175, 80, 0.1);
        border: 1px solid rgba(76, 175, 80, 0.3);
        border-radius: 8px;
        padding: 0.5rem;
        text-align: center;
        font-size: 0.8rem;
        color: #2e7d32;
    }
    
    .last-workout .weight {
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .input-field {
        width: 100%;
        padding: 0.6rem 0.5rem;
        border: 2px solid var(--glass-border);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.9);
        color: var(--text);
        font-size: 0.9rem;
        transition: var(--transition);
        text-align: center;
    }
    
    .input-field:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 2px rgba(26, 35, 126, 0.1);
        outline: none;
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
        width: 40px;
        height: 40px;
        background: var(--gradient-accent);
        border: 2px solid var(--glass-border);
        border-radius: 50%;
        color: white;
        font-size: 1rem;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .timer-button:hover {
        transform: scale(1.1);
        box-shadow: 0 3px 10px rgba(26, 35, 126, 0.3);
    }
    
    /* Completion Section */
    .completion-section {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius);
        padding: 2rem;
        text-align: center;
        margin-top: 2rem;
        position: sticky;
        bottom: 1rem;
        backdrop-filter: blur(10px);
    }
    
    .complete-button {
        width: 100%;
        padding: 1rem;
        font-size: 1.1rem;
        font-weight: 600;
        background: var(--gradient-accent);
        border: none;
    }
    
    .complete-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(26, 35, 126, 0.3);
    }
    
    .complete-button:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    /* Timer Modal */
    .timer-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.95);
        z-index: 1000;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(10px);
    }
    
    .timer-card {
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius);
        padding: 2rem;
        text-align: center;
        max-width: 400px;
        width: 90%;
        box-shadow: var(--shadow-lg);
    }
    
    .timer-display {
        font-size: 3rem;
        font-weight: 700;
        color: var(--accent);
        margin: 1rem 0;
        font-family: 'Courier New', monospace;
        letter-spacing: 2px;
    }
    
    .timer-actions {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }
    
    .timer-actions .btn {
        flex: 1;
    }
    
    /* Active Timer */
    .timer-active-overlay {
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--gradient-accent);
        color: white;
        padding: 1rem;
        border-radius: var(--radius);
        box-shadow: var(--shadow-lg);
        z-index: 1001;
        display: none;
        align-items: center;
        gap: 1rem;
        font-weight: 600;
    }
    
    .timer-active-display {
        font-family: 'Courier New', monospace;
        font-size: 1.1rem;
        font-weight: 700;
    }
    
    /* Success Modal */
    .success-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.95);
        z-index: 1000;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(10px);
    }
    
    .success-card {
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius);
        padding: 3rem 2rem;
        text-align: center;
        max-width: 400px;
        width: 90%;
        box-shadow: var(--shadow-lg);
        animation: successPop 0.5s ease;
    }
    
    @keyframes successPop {
        0% { transform: scale(0.8); opacity: 0; }
        70% { transform: scale(1.05); }
        100% { transform: scale(1); opacity: 1; }
    }
    
    .success-icon {
        font-size: 4rem;
        color: #4CAF50;
        margin-bottom: 1.5rem;
        animation: bounce 1s ease;
    }
    
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
        40% {transform: translateY(-10px);}
        60% {transform: translateY(-5px);}
    }
    
    /* Scroll hint for mobile */
    .scroll-hint {
        text-align: center;
        font-size: 0.8rem;
        color: var(--light-text);
        margin-top: -1rem;
        margin-bottom: 1rem;
        opacity: 0.7;
        display: none;
    }
    
    /* Mobile Optimizations */
    @media (max-width: 768px) {
        .workout-container {
            padding: 0 0.5rem;
        }
        
        .workout-header {
            padding: 1rem;
        }
        
        .workout-title {
            font-size: 1.5rem;
        }
        
        .workout-meta {
            gap: 1rem;
        }
        
        .days-navigation {
            margin-bottom: 1.5rem;
            padding: 0.5rem 0.5rem;
            gap: 0.4rem;
        }
        
        .day-tab {
            min-width: 90px;
            padding: 0.8rem 0.7rem;
            font-size: 0.85rem;
        }
        
        .day-tab small {
            font-size: 0.75rem;
        }
        
        .day-tab.current::after {
            font-size: 0.55rem;
            padding: 1px 3px;
            top: -5px;
            right: -3px;
        }
        
        .scroll-hint {
            display: block;
        }
        
        .progress-overview {
            grid-template-columns: 1fr;
        }
        
        .table-header,
        .table-row {
            grid-template-columns: 50px 1fr 1fr 1fr 60px;
            gap: 0.5rem;
            padding: 0.8rem;
            font-size: 0.8rem;
        }
        
        .input-field {
            padding: 0.5rem 0.3rem;
            font-size: 0.8rem;
        }
        
        .last-workout {
            padding: 0.3rem;
            font-size: 0.7rem;
        }
        
        .timer-button {
            width: 35px;
            height: 35px;
            font-size: 0.9rem;
        }
        
        .completion-section {
            padding: 1.5rem;
            margin: 1rem -0.5rem 0 -0.5rem;
            border-radius: var(--radius) var(--radius) 0 0;
        }
        
        .timer-active-overlay {
            top: 10px;
            right: 10px;
            left: 10px;
            justify-content: space-between;
        }
        
        .success-card {
            padding: 2rem 1.5rem;
        }
        
        .success-icon {
            font-size: 3rem;
        }
    }
    
    @media (max-width: 480px) {
        .days-navigation {
            gap: 0.3rem;
            padding: 0.5rem 0.25rem;
        }
        
        .day-tab {
            min-width: 85px;
            padding: 0.7rem 0.5rem;
            font-size: 0.8rem;
        }
        
        .day-tab small {
            font-size: 0.7rem;
        }
        
        /* Ensure all days are visible with horizontal scroll */
        .days-navigation::after {
            content: '';
            min-width: 0.5rem;
            height: 1px;
            flex: 0 0 auto;
        }
        
        .table-header,
        .table-row {
            grid-template-columns: 40px 1fr 1fr;
            gap: 0.5rem;
        }
        
        .table-header div:nth-child(3),
        .table-header div:nth-child(4),
        .table-row div:nth-child(3),
        .table-row div:nth-child(4) {
            display: none;
        }
        
        .exercise-card-header {
            padding: 1rem;
        }
        
        .exercise-card-header h3 {
            font-size: 1.1rem;
        }
        
        .sets-section {
            padding: 1rem;
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
    <div class="days-navigation">
        <?php foreach ($all_days as $day): ?>
        <div class="day-tab <?php echo $day['day_order'] == $current_day_index ? 'active current' : ''; ?>" 
             data-day="<?php echo $day['day_order']; ?>">
            Day <?php echo $day['day_order']; ?><br>
            <small><?php echo htmlspecialchars($day['title']); ?></small>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Days Content -->
    <?php foreach ($all_days as $day): ?>
    <div class="day-content <?php echo $day['day_order'] == $current_day_index ? 'active' : ''; ?>" 
         id="day-<?php echo $day['day_order']; ?>">
        
        <!-- Day Header -->
        <div class="day-header">
            <h2 class="day-title"><?php echo htmlspecialchars($day['title']); ?></h2>
            <div class="day-subtitle"><?php echo htmlspecialchars($day['description']); ?></div>
            
            <div class="workout-meta">
                <div class="meta-item">
                    <i class="fas fa-dumbbell"></i>
                    <span><?php echo count($all_exercises[$day['day_order']]); ?> Exercises</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-clock"></i>
                    <span>45-60 min</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-fire"></i>
                    <span>Strength Training</span>
                </div>
            </div>
        </div>

        <!-- Progress Overview for this day -->
        <div class="progress-overview">
            <div class="progress-card">
                <div class="progress-value">Day <?php echo $day['day_order']; ?></div>
                <div class="progress-label">Program Day</div>
            </div>
            <div class="progress-card">
                <div class="progress-value"><?php echo count($all_exercises[$day['day_order']]); ?></div>
                <div class="progress-label">Exercises</div>
            </div>
            <div class="progress-card">
                <div class="progress-value">
                    <?php 
                    $day_sets = 0;
                    foreach ($all_exercises[$day['day_order']] as $exercise) {
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
            
            <!-- Exercise Cards -->
            <div class="exercise-cards">
                <?php foreach ($all_exercises[$day['day_order']] as $index => $exercise): 
                    $last_workout = $last_workout_data[$exercise['id']] ?? [];
                ?>
                <div class="exercise-card">
                    <div class="exercise-card-header" onclick="toggleExercise(this)">
                        <h3>
                            <span style="color: var(--accent); margin-right: 0.5rem;">#<?php echo $index + 1; ?></span>
                            <?php echo htmlspecialchars($exercise['exercise_name']); ?>
                        </h3>
                        <button type="button" class="exercise-toggle">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    
                    <div class="exercise-card-content">
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
                        <div class="sets-section">
                            <div class="sets-table">
                                <!-- Table Header -->
                                <div class="table-header">
                                    <div>SET</div>
                                    <div>LAST WORKOUT</div>
                                    <div>WEIGHT</div>
                                    <div>REPS</div>
                                    <div>TIMER</div>
                                </div>
                                
                                <!-- Table Rows -->
                                <?php for ($i = 0; $i < $exercise['default_sets']; $i++): 
                                    $last_set = $last_workout[$i] ?? null;
                                    $last_weight = $last_set['weight'] ?? 0;
                                    $last_reps = $last_set['reps'] ?? 0;
                                ?>
                                <div class="table-row">
                                    <div class="set-number"><?php echo $i + 1; ?></div>
                                    
                                    <div class="last-workout">
                                        <?php if ($last_set): ?>
                                            <div class="weight"><?php echo $last_weight; ?> kg</div>
                                            <div><?php echo $last_reps; ?> reps</div>
                                        <?php else: ?>
                                            <div>First time</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div>
                                        <input type="number" 
                                               name="sets[<?php echo $exercise['id']; ?>][<?php echo $i; ?>][weight]" 
                                               class="input-field weight-input" 
                                               placeholder="kg" 
                                               step="0.5" 
                                               min="0"
                                               value="<?php echo $last_weight ? $last_weight + 2.5 : ''; ?>"
                                               data-last-weight="<?php echo $last_weight; ?>">
                                    </div>
                                    
                                    <div>
                                        <input type="number" 
                                               name="sets[<?php echo $exercise['id']; ?>][<?php echo $i; ?>][reps]" 
                                               class="input-field reps-input" 
                                               placeholder="Reps" 
                                               min="0" 
                                               max="100"
                                               value="<?php echo $last_reps ? $last_reps + 1 : ''; ?>"
                                               data-last-reps="<?php echo $last_reps; ?>">
                                    </div>
                                    
                                    <div class="timer-section">
                                        <button type="button" class="timer-button" data-set="<?php echo $exercise['id'] . '_' . $i; ?>">
                                            <i class="fas fa-clock"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Completion Section -->
            <div class="completion-section">
                <button type="submit" class="btn btn-primary complete-button" id="complete-button-<?php echo $day['day_order']; ?>">
                    <i class="fas fa-check-circle"></i> Complete Day <?php echo $day['day_order']; ?> Workout
                </button>
                <p style="margin-top: 1rem; color: var(--light-text); font-size: 0.9rem;">
                    Track your progress and aim for progressive overload
                </p>
            </div>
        </form>
    </div>
    <?php endforeach; ?>
</div>

<!-- Active Timer Overlay -->
<div class="timer-active-overlay" id="timerActiveOverlay">
    <div>
        <i class="fas fa-clock"></i>
        <span class="timer-active-display" id="timerActiveDisplay">00:00</span>
    </div>
    <button type="button" class="btn btn-outline" id="stopActiveTimer" style="padding: 0.5rem 1rem; font-size: 0.9rem; background: rgba(255,255,255,0.2);">
        <i class="fas fa-stop"></i> Stop
    </button>
</div>

<!-- Rest Timer Modal -->
<div class="timer-modal" id="restTimerModal">
    <div class="timer-card">
        <h3 style="margin-bottom: 1rem;">Rest Timer</h3>
        
        <div class="timer-display" id="timerDisplay">02:00</div>
        
        <div style="margin: 1.5rem 0;">
            <label style="display: block; margin-bottom: 0.5rem; color: var(--light-text);">Rest Time</label>
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

<!-- Success Modal -->
<div class="success-modal" id="successModal">
    <div class="success-card">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3 style="margin: 0 0 1rem 0; color: var(--text);">Workout Completed!</h3>
        <p style="margin: 0 0 2rem 0; color: var(--light-text);">Great job! Your workout has been saved successfully.</p>
        <button id="successContinue" class="btn btn-primary" style="width: 100%;">
            <i class="fas fa-tachometer-alt"></i> Continue to Dashboard
        </button>
    </div>
</div>

<script>
// Mobile navigation enhancements
function setupMobileNavigation() {
    const daysNav = document.querySelector('.days-navigation');
    const scrollHint = document.createElement('div');
    scrollHint.className = 'scroll-hint';
    scrollHint.innerHTML = '← Scroll to see all days →';
    
    if (daysNav && isMobile()) {
        // Add scroll hint
        daysNav.parentNode.insertBefore(scrollHint, daysNav.nextSibling);
        
        // Check if scrolling is needed
        const checkScrollNeeded = () => {
            const isScrollable = daysNav.scrollWidth > daysNav.clientWidth;
            if (isScrollable) {
                daysNav.classList.add('scrollable');
                scrollHint.style.display = 'block';
            } else {
                daysNav.classList.remove('scrollable');
                scrollHint.style.display = 'none';
            }
        };
        
        // Check on load and resize
        checkScrollNeeded();
        window.addEventListener('resize', checkScrollNeeded);
        
        // Auto-scroll to current day on mobile
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
}

// Enhanced mobile detection
function isMobile() {
    return window.innerWidth <= 768;
}

// Day navigation functionality
document.querySelectorAll('.day-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const dayNumber = this.getAttribute('data-day');
        
        // Remove active class from all tabs and contents
        document.querySelectorAll('.day-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.day-content').forEach(c => c.classList.remove('active'));
        
        // Add active class to current tab and content
        this.classList.add('active');
        document.getElementById('day-' + dayNumber).classList.add('active');
        
        // Close all exercise cards when switching days
        document.querySelectorAll('.exercise-card-content').forEach(content => {
            content.classList.remove('active');
        });
        document.querySelectorAll('.exercise-toggle i').forEach(icon => {
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        });
        
        // Open first exercise of the new day
        openFirstExercise();
    });
});

// Exercise card toggle
function toggleExercise(header) {
    const content = header.nextElementSibling;
    const toggle = header.querySelector('.exercise-toggle i');
    
    if (content.classList.contains('active')) {
        content.classList.remove('active');
        toggle.classList.remove('fa-chevron-up');
        toggle.classList.add('fa-chevron-down');
    } else {
        content.classList.add('active');
        toggle.classList.remove('fa-chevron-down');
        toggle.classList.add('fa-chevron-up');
    }
}

// Auto-open first exercise of active day
function openFirstExercise() {
    const activeDay = document.querySelector('.day-content.active');
    if (activeDay) {
        const firstExercise = activeDay.querySelector('.exercise-card-header');
        if (firstExercise) {
            toggleExercise(firstExercise);
        }
    }
}

// Progressive overload indicators
document.querySelectorAll('.weight-input, .reps-input').forEach(input => {
    input.addEventListener('input', function() {
        const lastValue = parseFloat(this.getAttribute('data-last-weight') || this.getAttribute('data-last-reps'));
        const currentValue = parseFloat(this.value) || 0;
        
        if (lastValue > 0) {
            if (currentValue > lastValue) {
                this.classList.add('progress-up');
                this.classList.remove('progress-down');
            } else if (currentValue < lastValue) {
                this.classList.add('progress-down');
                this.classList.remove('progress-up');
            } else {
                this.classList.remove('progress-up', 'progress-down');
            }
        }
    });
});

// Rest timer functionality
let timerInterval;
let remainingSeconds = 0;

// Timer buttons
document.querySelectorAll('.timer-button').forEach(button => {
    button.addEventListener('click', function() {
        showTimerModal();
    });
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
    
    // Play sound and show notification
    playCompletionSound();
    showCompletionNotification();
}

function playCompletionSound() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
        oscillator.frequency.setValueAtTime(1000, audioContext.currentTime + 0.1);
        
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0, audioContext.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.2, audioContext.currentTime + 0.1);
        gainNode.gain.linearRampToValueAtTime(0, audioContext.currentTime + 0.3);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.3);
    } catch (e) {
        console.log('Sound not supported');
    }
}

function showCompletionNotification() {
    // Create a simple notification
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius);
        padding: 2rem;
        text-align: center;
        box-shadow: var(--shadow-lg);
        z-index: 2000;
        animation: slideIn 0.3s ease;
    `;
    
    notification.innerHTML = `
        <div style="font-size: 3rem; color: #4CAF50; margin-bottom: 1rem;">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3 style="margin: 0 0 0.5rem 0; color: var(--text);">Rest Time Complete!</h3>
        <p style="margin: 0; color: var(--light-text);">Time for your next set</p>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// AJAX WORKOUT SUBMISSION
document.querySelectorAll('.workout-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Only process the active day's form
        const activeDay = document.querySelector('.day-content.active');
        if (!activeDay.contains(this)) {
            return;
        }
        
        // Check if we have at least one rep value
        const repInputs = this.querySelectorAll('input[name*="[reps]"]');
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
        const formData = new FormData(this);
        const dayId = this.getAttribute('data-day-id');
        const submitBtn = this.querySelector('.complete-button');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving Workout...';
        submitBtn.disabled = true;
        
        // Add additional data
        formData.append('ajax_complete_workout', 'true');
        formData.append('day_id', dayId);
        
        console.log('Sending AJAX request...');
        
        // Send AJAX request to separate file
        fetch('ajax_save_workout.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response received:', response);
            
            // First check if response is OK
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // Get the response text first to see what we're getting
            return response.text().then(text => {
                console.log('Raw response:', text);
                
                try {
                    // Try to parse as JSON
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Server returned invalid JSON. Response: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            console.log('Parsed data:', data);
            
            if (data.success) {
                // Show success modal
                showSuccessModal();
            } else {
                // Show error
                alert(data.message || 'Error saving workout. Please try again.');
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Error saving workout: ' + error.message + '. Please try again.');
            // Reset button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
});

// Success modal functionality
function showSuccessModal() {
    const modal = document.getElementById('successModal');
    modal.style.display = 'flex';
    
    // Add confetti effect
    createConfetti();
}

document.getElementById('successContinue').addEventListener('click', function() {
    window.location.href = 'dashboard.php?message=workout_completed';
});

// Confetti effect for success
function createConfetti() {
    const colors = ['#4CAF50', '#2196F3', '#FF9800', '#E91E63', '#9C27B0'];
    const container = document.body;
    
    for (let i = 0; i < 50; i++) {
        const confetti = document.createElement('div');
        confetti.style.cssText = `
            position: fixed;
            width: 10px;
            height: 10px;
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
        const animation = confetti.animate([
            { transform: 'translateY(0) rotate(0deg)', opacity: 1 },
            { transform: `translateY(${window.innerHeight}px) rotate(${360 + Math.random() * 360}deg)`, opacity: 0 }
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
    openFirstExercise();
    
    // Re-check navigation on orientation change
    window.addEventListener('orientationchange', function() {
        setTimeout(setupMobileNavigation, 100);
    });
});
</script>

<?php require_once 'footer.php'; ?>