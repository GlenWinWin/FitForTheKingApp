<?php
$pageTitle = "Today's Workout";
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

// Calculate current day in plan (cycle through plan days)
$selected_at = new DateTime($user_plan['selected_at']);
$today = new DateTime();
$days_since_start = $selected_at->diff($today)->days;

// Get total days in plan
$days_count_query = "SELECT COUNT(*) as total_days FROM workout_plan_days WHERE plan_id = ?";
$stmt = $db->prepare($days_count_query);
$stmt->execute([$user_plan['id']]);
$total_days = $stmt->fetch(PDO::FETCH_ASSOC)['total_days'];

$current_day_index = ($days_since_start % $total_days) + 1;

// Get today's workout day
$day_query = "SELECT * FROM workout_plan_days 
             WHERE plan_id = ? AND day_order = ?";
$stmt = $db->prepare($day_query);
$stmt->execute([$user_plan['id'], $current_day_index]);
$workout_day = $stmt->fetch(PDO::FETCH_ASSOC);

// Get exercises for today
$exercises_query = "SELECT * FROM workout_exercises 
                   WHERE plan_day_id = ? 
                   ORDER BY id";
$stmt = $db->prepare($exercises_query);
$stmt->execute([$workout_day['id']]);
$exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle workout completion
if ($_POST && isset($_POST['complete_workout'])) {
    foreach ($exercises as $exercise) {
        if (isset($_POST['sets'][$exercise['id']])) {
            // Create workout log
            $log_query = "INSERT INTO workout_logs (user_id, plan_id, plan_day_id, exercise_id) 
                         VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($log_query);
            $stmt->execute([$user_id, $user_plan['id'], $workout_day['id'], $exercise['id']]);
            $log_id = $db->lastInsertId();
            
            // Log sets
            foreach ($_POST['sets'][$exercise['id']] as $set_num => $set_data) {
                if (!empty($set_data['reps'])) {
                    $set_query = "INSERT INTO workout_log_sets (workout_log_id, set_number, reps, weight, unit) 
                                 VALUES (?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($set_query);
                    $stmt->execute([
                        $log_id, 
                        $set_num + 1, 
                        $set_data['reps'], 
                        $set_data['weight'] ?? null, 
                        'kg'
                    ]);
                }
            }
        }
    }
    
    echo "<script>window.location.href = 'dashboard.php?message=workout_completed';</script>";
    exit();
}
?>

<style>
    .workout-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    
    .workout-header {
        text-align: center;
        margin-bottom: 2rem;
        padding: 2rem;
        background: var(--glass-bg);
        border-radius: var(--radius);
        border: 1px solid var(--glass-border);
    }
    
    .workout-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 0.5rem;
    }
    
    .workout-subtitle {
        font-size: 1.2rem;
        color: var(--light-text);
        margin-bottom: 1rem;
    }
    
    .workout-meta {
        display: flex;
        justify-content: center;
        gap: 2rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--light-text);
        font-size: 1rem;
    }
    
    /* Tabs Navigation */
    .tabs-container {
        margin-bottom: 2rem;
    }
    
    .tabs-nav {
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        padding: 0.5rem;
        margin-bottom: 0;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    
    .tabs-nav::-webkit-scrollbar {
        display: none;
    }
    
    .tab-button {
        flex: 0 0 auto;
        padding: 1rem 1.5rem;
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius) var(--radius) 0 0;
        color: var(--light-text);
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .tab-button:hover {
        background: rgba(26, 35, 126, 0.05);
        color: var(--text);
    }
    
    .tab-button.active {
        background: var(--gradient-accent);
        color: white;
        border-color: var(--accent);
    }
    
    .tab-button .exercise-number {
        background: rgba(255, 255, 255, 0.2);
        color: inherit;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 700;
    }
    
    /* Tab Content */
    .tab-content {
        display: none;
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: 0 var(--radius) var(--radius) var(--radius);
        padding: 2rem;
        animation: fadeIn 0.3s ease;
    }
    
    .tab-content.active {
        display: block;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .exercise-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid var(--glass-border);
    }
    
    .exercise-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--text);
        margin: 0;
    }
    
    .exercise-progress {
        background: var(--gradient-primary);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        color: var(--text);
        font-size: 0.9rem;
    }
    
    .video-container {
        position: relative;
        padding-bottom: 56.25%;
        height: 0;
        overflow: hidden;
        border-radius: var(--radius);
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow);
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
        padding: 1.25rem;
        border-radius: 0 var(--radius) var(--radius) 0;
        margin-bottom: 2rem;
    }
    
    .exercise-notes p {
        margin: 0;
        color: var(--text);
        line-height: 1.6;
        font-size: 1rem;
    }
    
    .sets-table {
        width: 100%;
        background: white;
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        margin-bottom: 2rem;
    }
    
    .table-header {
        background: var(--gradient-primary);
        padding: 1.25rem 1.5rem;
        display: grid;
        grid-template-columns: 80px 1fr 1fr 120px;
        gap: 1rem;
        align-items: center;
        font-weight: 600;
        color: var(--text);
        border-bottom: 1px solid var(--glass-border);
    }
    
    .table-row {
        padding: 1.25rem 1.5rem;
        display: grid;
        grid-template-columns: 80px 1fr 1fr 120px;
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
        font-size: 1.1rem;
    }
    
    .input-field {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid var(--glass-border);
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.8);
        color: var(--text);
        font-size: 1rem;
        transition: var(--transition);
        text-align: center;
    }
    
    .input-field:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
        outline: none;
    }
    
    .timer-section {
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .timer-button {
        width: 50px;
        height: 50px;
        background: var(--gradient-accent);
        border: 2px solid var(--glass-border);
        border-radius: 50%;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .timer-button:hover {
        background: var(--gradient-accent);
        color: white;
        transform: scale(1.1);
        box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
    }
    
    .timer-button.active {
        background: var(--gradient-accent);
        color: white;
        border-color: var(--accent);
    }
    
    .navigation-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid var(--glass-border);
    }
    
    .nav-button {
        flex: 1;
        padding: 1rem;
        font-weight: 600;
    }
    
    .completion-section {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius);
        padding: 2.5rem;
        text-align: center;
        margin-top: 3rem;
    }
    
    .complete-button {
        width: 100%;
        padding: 1.25rem;
        font-size: 1.1rem;
        font-weight: 600;
        background: var(--gradient-accent);
        border: none;
    }
    
    .complete-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(26, 35, 126, 0.3);
    }
    
    /* Timer Modal - Light Design */
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
        padding: 2.5rem;
        text-align: center;
        max-width: 450px;
        width: 90%;
        box-shadow: var(--shadow-lg);
    }
    
    .timer-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 2rem;
    }
    
    .timer-display {
        font-size: 4rem;
        font-weight: 700;
        color: var(--accent);
        margin-bottom: 1rem;
        background: var(--gradient-blue);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        font-family: 'Courier New', monospace;
        letter-spacing: 2px;
    }
    
    .timer-label {
        color: var(--light-text);
        font-size: 1.1rem;
        margin-bottom: 2rem;
    }
    
    .timer-input-section {
        margin-bottom: 2rem;
    }
    
    .timer-input-group {
        display: flex;
        gap: 1rem;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
    }
    
    .timer-input {
        width: 100px;
        padding: 1rem;
        border: 2px solid var(--glass-border);
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.9);
        color: var(--text);
        font-size: 1.2rem;
        text-align: center;
        font-weight: 600;
    }
    
    .timer-input:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
        outline: none;
    }
    
    .timer-input-label {
        color: var(--light-text);
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }
    
    .timer-actions {
        display: flex;
        gap: 1rem;
    }
    
    .timer-actions .btn {
        flex: 1;
    }
    
    .timer-active-overlay {
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--gradient-accent);
        color: white;
        padding: 1rem 1.5rem;
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
        font-size: 1.2rem;
        font-weight: 700;
    }
    
    /* Custom Notification Styles */
    .timer-notification {
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
        max-width: 400px;
        width: 90%;
        animation: slideInDown 0.5s ease;
    }
    
    .timer-notification::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--gradient-accent);
        border-radius: var(--radius) var(--radius) 0 0;
    }
    
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translate(-50%, -60%);
        }
        to {
            opacity: 1;
            transform: translate(-50%, -50%);
        }
    }
    
    .notification-icon {
        font-size: 3rem;
        color: #4CAF50;
        margin-bottom: 1rem;
    }
    
    .notification-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 0.5rem;
    }
    
    .notification-message {
        color: var(--light-text);
        margin-bottom: 1.5rem;
        line-height: 1.6;
    }
    
    .notification-actions {
        display: flex;
        gap: 1rem;
    }
    
    .notification-actions .btn {
        flex: 1;
    }
    
    .notification-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
        z-index: 1999;
        display: none;
    }
    
    .notification-overlay.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @media (max-width: 768px) {
        .workout-title {
            font-size: 2rem;
        }
        
        .workout-meta {
            gap: 1rem;
        }
        
        .tabs-nav {
            gap: 0.25rem;
        }
        
        .tab-button {
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
        }
        
        .table-header,
        .table-row {
            grid-template-columns: 60px 1fr 1fr 80px;
            gap: 0.75rem;
            padding: 1rem;
        }
        
        .timer-card {
            padding: 2rem;
        }
        
        .timer-display {
            font-size: 3rem;
        }
        
        .exercise-header {
            flex-direction: column;
            gap: 1rem;
            align-items: flex-start;
        }
        
        .navigation-buttons {
            flex-direction: column;
        }
        
        .timer-button {
            width: 45px;
            height: 45px;
            font-size: 1.1rem;
        }
    }
    
    @media (max-width: 480px) {
        .table-header,
        .table-row {
            grid-template-columns: 50px 1fr 1fr 70px;
            font-size: 0.9rem;
        }
        
        .input-field {
            padding: 0.6rem 0.5rem;
            font-size: 0.9rem;
        }
        
        .tab-button {
            padding: 0.6rem 0.8rem;
            font-size: 0.8rem;
        }
        
        .timer-button {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }
    }
</style>

<div class="workout-container">
    <!-- Workout Header -->
    <div class="workout-header">
        <h1 class="workout-title"><?php echo htmlspecialchars($workout_day['title']); ?></h1>
        <div class="workout-subtitle">Complete your workout and track your progress</div>
        
        <div class="workout-meta">
            <div class="meta-item">
                <i class="fas fa-calendar"></i>
                <span>Day <?php echo $current_day_index; ?> of <?php echo $total_days; ?></span>
            </div>
            <div class="meta-item">
                <i class="fas fa-dumbbell"></i>
                <span><?php echo count($exercises); ?> Exercises</span>
            </div>
            <div class="meta-item">
                <i class="fas fa-clock"></i>
                <span>45-60 min</span>
            </div>
        </div>
    </div>

    <form method="POST">
        <!-- Tabs Navigation -->
        <div class="tabs-container">
            <div class="tabs-nav">
                <?php foreach ($exercises as $index => $exercise): ?>
                <button type="button" class="tab-button <?php echo $index === 0 ? 'active' : ''; ?>" 
                        data-tab="exercise-<?php echo $index + 1; ?>">
                    <span class="exercise-number"><?php echo $index + 1; ?></span>
                    <?php echo htmlspecialchars($exercise['exercise_name']); ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tab Contents -->
        <?php foreach ($exercises as $index => $exercise): ?>
        <div class="tab-content <?php echo $index === 0 ? 'active' : ''; ?>" id="exercise-<?php echo $index + 1; ?>">
            <!-- Exercise Header -->
            <div class="exercise-header">
                <h2 class="exercise-title"><?php echo htmlspecialchars($exercise['exercise_name']); ?></h2>
                <div class="exercise-progress">
                    Exercise <?php echo $index + 1; ?> of <?php echo count($exercises); ?>
                </div>
            </div>
            
            <!-- Video -->
            <?php if ($exercise['youtube_link']): ?>
            <div class="video-container">
                <iframe src="<?php echo htmlspecialchars($exercise['youtube_link']); ?>" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen></iframe>
            </div>
            <?php endif; ?>
            
            <!-- Exercise Notes -->
            <?php if ($exercise['notes']): ?>
            <div class="exercise-notes">
                <p><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($exercise['notes']); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Sets Table -->
            <div class="sets-table">
                <!-- Table Header -->
                <div class="table-header">
                    <div>SET</div>
                    <div>WEIGHT</div>
                    <div>REPS</div>
                    <div>TIMER</div>
                </div>
                
                <!-- Table Rows -->
                <?php for ($i = 0; $i < $exercise['default_sets']; $i++): ?>
                <div class="table-row">
                    <div class="set-number"><?php echo $i + 1; ?></div>
                    
                    <div>
                        <input type="number" 
                               name="sets[<?php echo $exercise['id']; ?>][<?php echo $i; ?>][weight]" 
                               class="input-field" 
                               placeholder="kg" 
                               step="0.5" 
                               min="0">
                    </div>
                    
                    <div>
                        <input type="number" 
                               name="sets[<?php echo $exercise['id']; ?>][<?php echo $i; ?>][reps]" 
                               class="input-field" 
                               placeholder="Reps" 
                               min="0" 
                               max="100"
                               required>
                    </div>
                    
                    <div class="timer-section">
                        <button type="button" class="timer-button" data-set="<?php echo $exercise['id'] . '_' . $i; ?>">
                            <i class="fas fa-clock"></i>
                        </button>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            
            <!-- Navigation Buttons -->
            <div class="navigation-buttons">
                <?php if ($index > 0): ?>
                <button type="button" class="btn btn-outline nav-button prev-tab">
                    <i class="fas fa-arrow-left"></i> Previous Exercise
                </button>
                <?php endif; ?>
                
                <?php if ($index < count($exercises) - 1): ?>
                <button type="button" class="btn btn-primary nav-button next-tab">
                    Next Exercise <i class="fas fa-arrow-right"></i>
                </button>
                <?php else: ?>
                <button type="submit" name="complete_workout" class="btn btn-primary nav-button">
                    <i class="fas fa-check-circle"></i> Complete Workout
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </form>
</div>

<!-- Active Timer Overlay -->
<div class="timer-active-overlay" id="timerActiveOverlay">
    <i class="fas fa-clock"></i>
    <span class="timer-active-display" id="timerActiveDisplay">00:00</span>
    <button type="button" class="btn btn-outline" id="stopActiveTimer" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
        <i class="fas fa-stop"></i> Stop
    </button>
</div>

<!-- Rest Timer Modal -->
<div class="timer-modal" id="restTimerModal">
    <div class="timer-card">
        <h3 class="timer-title">Set Rest Timer</h3>
        
        <div class="timer-input-section">
            <div class="timer-input-group">
                <div style="text-align: center;">
                    <div class="timer-input-label">Minutes</div>
                    <input type="number" id="timerMinutes" class="timer-input" value="2" min="0" max="10">
                </div>
                <div style="font-size: 2rem; color: var(--light-text); margin-top: 1rem;">:</div>
                <div style="text-align: center;">
                    <div class="timer-input-label">Seconds</div>
                    <input type="number" id="timerSeconds" class="timer-input" value="0" min="0" max="59">
                </div>
            </div>
        </div>
        
        <div class="timer-display" id="timerDisplay">02:00</div>
        <p class="timer-label" id="timerLabel">Set your rest time</p>
        
        <div class="timer-actions">
            <button id="startTimer" class="btn btn-primary">
                <i class="fas fa-play"></i> Start Timer
            </button>
            <button id="closeTimerModal" class="btn btn-outline">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
</div>

<!-- Custom Notification Overlay -->
<div class="notification-overlay" id="notificationOverlay"></div>

<script>
// Tab functionality
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', function() {
        const tabId = this.getAttribute('data-tab');
        
        // Remove active class from all tabs
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Add active class to current tab
        this.classList.add('active');
        document.getElementById(tabId).classList.add('active');
    });
});

// Navigation buttons
document.querySelectorAll('.next-tab').forEach(button => {
    button.addEventListener('click', function() {
        const currentTab = this.closest('.tab-content');
        const nextTab = currentTab.nextElementSibling;
        const nextTabButton = document.querySelector(`[data-tab="${nextTab.id}"]`);
        
        if (nextTabButton) {
            nextTabButton.click();
        }
    });
});

document.querySelectorAll('.prev-tab').forEach(button => {
    button.addEventListener('click', function() {
        const currentTab = this.closest('.tab-content');
        const prevTab = currentTab.previousElementSibling;
        const prevTabButton = document.querySelector(`[data-tab="${prevTab.id}"]`);
        
        if (prevTabButton) {
            prevTabButton.click();
        }
    });
});

// Rest timer functionality
let timerInterval;
let remainingSeconds = 0;
let isPaused = false;
let currentTimerSet = '';

// Preload completion sound
function preloadCompletionSound() {
    // Sound will be generated using Web Audio API when needed
    console.log('Sound system ready');
}

// Timer buttons
document.querySelectorAll('.timer-button').forEach(button => {
    button.addEventListener('click', function() {
        currentTimerSet = this.getAttribute('data-set');
        showTimerModal();
    });
});

// Timer control buttons
document.getElementById('startTimer').addEventListener('click', startTimerFromModal);
document.getElementById('closeTimerModal').addEventListener('click', hideTimerModal);
document.getElementById('stopActiveTimer').addEventListener('click', stopActiveTimer);

// Input validation
document.getElementById('timerMinutes').addEventListener('input', updateTimerDisplay);
document.getElementById('timerSeconds').addEventListener('input', updateTimerDisplay);

function showTimerModal() {
    const modal = document.getElementById('restTimerModal');
    modal.style.display = 'flex';
    updateTimerDisplay();
}

function hideTimerModal() {
    const modal = document.getElementById('restTimerModal');
    modal.style.display = 'none';
}

function updateTimerDisplay() {
    const minutes = parseInt(document.getElementById('timerMinutes').value) || 0;
    const seconds = parseInt(document.getElementById('timerSeconds').value) || 0;
    const display = document.getElementById('timerDisplay');
    
    // Validate inputs
    if (minutes < 0) document.getElementById('timerMinutes').value = 0;
    if (seconds < 0) document.getElementById('timerSeconds').value = 0;
    if (seconds > 59) document.getElementById('timerSeconds').value = 59;
    
    display.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

function startTimerFromModal() {
    const minutes = parseInt(document.getElementById('timerMinutes').value) || 0;
    const seconds = parseInt(document.getElementById('timerSeconds').value) || 0;
    const totalSeconds = (minutes * 60) + seconds;
    
    if (totalSeconds > 0) {
        startTimer(totalSeconds);
        hideTimerModal();
    } else {
        showNotification('Invalid Time', 'Please set a timer duration greater than 0 seconds.', 'error');
    }
}

function startTimer(seconds) {
    remainingSeconds = seconds;
    isPaused = false;
    
    // Show active timer overlay
    const overlay = document.getElementById('timerActiveOverlay');
    overlay.style.display = 'flex';
    
    updateActiveTimerDisplay();
    
    timerInterval = setInterval(updateActiveTimer, 1000);
}

function updateActiveTimer() {
    if (!isPaused) {
        remainingSeconds--;
        updateActiveTimerDisplay();
        
        if (remainingSeconds <= 0) {
            completeTimer();
        }
    }
}

function updateActiveTimerDisplay() {
    const display = document.getElementById('timerActiveDisplay');
    display.textContent = formatTime(remainingSeconds);
}

function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

function stopActiveTimer() {
    clearInterval(timerInterval);
    const overlay = document.getElementById('timerActiveOverlay');
    overlay.style.display = 'none';
    showNotification('Timer Stopped', 'Your rest timer has been stopped.', 'info');
}

function completeTimer() {
    stopActiveTimer();
    
    // Play completion sound
    playCompletionSound();
    
    // Show beautiful notification
    showNotification(
        'Rest Time Complete!', 
        'Time to start your next set. Keep up the great work!', 
        'success'
    );
    
    // Show desktop notification
    if (Notification.permission === 'granted') {
        new Notification('Rest time complete!', {
            body: 'Time to start your next set.',
            icon: '/favicon.ico',
            badge: '/favicon.ico'
        });
    }
}

function playCompletionSound() {
    // Try to play using Web Audio API first
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        // Play a pleasant completion sound
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
        oscillator.frequency.setValueAtTime(1000, audioContext.currentTime + 0.1);
        oscillator.frequency.setValueAtTime(600, audioContext.currentTime + 0.2);
        
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0, audioContext.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.2, audioContext.currentTime + 0.1);
        gainNode.gain.linearRampToValueAtTime(0.2, audioContext.currentTime + 0.3);
        gainNode.gain.linearRampToValueAtTime(0, audioContext.currentTime + 0.5);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.5);
        
    } catch (e) {
        // Fallback: Use browser's built-in beep (if available)
        console.log('Web Audio API not supported');
        try {
            // This might work in some browsers
            const beep = new Audio('data:audio/wav;base64,UklGRigAAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQQAAAAAAA==');
            beep.play().catch(() => {
                // Last resort: Use a simple beep using the oldest method
                console.log('\u0007'); // ASCII bell character
            });
        } catch (fallbackError) {
            console.log('Sound not supported in this browser');
        }
    }
}

function showNotification(title, message, type = 'info') {
    // Remove existing notification if any
    const existingNotification = document.getElementById('customTimerNotification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    const existingOverlay = document.getElementById('notificationOverlay');
    if (existingOverlay) {
        existingOverlay.remove();
    }
    
    // Create overlay
    const overlay = document.createElement('div');
    overlay.id = 'notificationOverlay';
    overlay.className = 'notification-overlay active';
    document.body.appendChild(overlay);
    
    // Create notification
    const notification = document.createElement('div');
    notification.id = 'customTimerNotification';
    notification.className = 'timer-notification';
    
    // Set icon based on type
    let icon = 'fas fa-info-circle';
    let iconColor = '#2196F3';
    
    switch (type) {
        case 'success':
            icon = 'fas fa-check-circle';
            iconColor = '#4CAF50';
            break;
        case 'error':
            icon = 'fas fa-exclamation-circle';
            iconColor = '#f44336';
            break;
        case 'warning':
            icon = 'fas fa-exclamation-triangle';
            iconColor = '#FF9800';
            break;
    }
    
    notification.innerHTML = `
        <div class="notification-icon">
            <i class="${icon}" style="color: ${iconColor}"></i>
        </div>
        <h3 class="notification-title">${title}</h3>
        <p class="notification-message">${message}</p>
        <div class="notification-actions">
            <button class="btn btn-primary" onclick="closeNotification()">
                <i class="fas fa-check"></i> Got It
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-close after 5 seconds
    setTimeout(() => {
        closeNotification();
    }, 5000);
}

function closeNotification() {
    const notification = document.getElementById('customTimerNotification');
    const overlay = document.getElementById('notificationOverlay');
    
    if (notification) {
        notification.style.animation = 'slideInDown 0.3s ease reverse';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }
    
    if (overlay) {
        overlay.style.animation = 'fadeIn 0.3s ease reverse';
        setTimeout(() => {
            overlay.remove();
        }, 300);
    }
}

// Preload sound when page loads
document.addEventListener('DOMContentLoaded', function() {
    preloadCompletionSound();
});

// Request notification permission
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}

// Add input validation for workout fields
document.querySelectorAll('input[type="number"]').forEach(input => {
    input.addEventListener('input', function() {
        if (this.value < 0) this.value = 0;
    });
});
</script>

<?php require_once 'footer.php'; ?>