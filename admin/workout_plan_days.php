<?php
$pageTitle = "Manage Workout Days";
require_once '../header.php';
requireAdmin();

$plan_id = $_GET['plan_id'] ?? null;

if (!$plan_id) {
    echo "<script>window.location.href = 'workout_plans.php';</script>";
    exit();
}

// Get plan details
$plan_query = "SELECT * FROM workout_plans WHERE id = ?";
$stmt = $db->prepare($plan_query);
$stmt->execute([$plan_id]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    echo "<script>window.location.href = 'workout_plans.php';</script>";
    exit();
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_day'])) {
        $day_order = sanitize($_POST['day_order']);
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        
        // Check if day order already exists
        $check_query = "SELECT id FROM workout_plan_days WHERE plan_id = ? AND day_order = ?";
        $stmt = $db->prepare($check_query);
        $stmt->execute([$plan_id, $day_order]);
        
        if ($stmt->fetch()) {
            $error = "Day order {$day_order} already exists for this plan.";
        } else {
            $insert_query = "INSERT INTO workout_plan_days (plan_id, day_order, title, description) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($insert_query);
            $stmt->execute([$plan_id, $day_order, $title, $description]);
            
            echo "<script>window.location.href = 'workout_plan_days.php?plan_id=" . $plan_id."';</script>";
            exit();
        }
    }
    
    if (isset($_POST['delete_day'])) {
        $day_id = sanitize($_POST['day_id']);
        
        $delete_query = "DELETE FROM workout_plan_days WHERE id = ?";
        $stmt = $db->prepare($delete_query);
        $stmt->execute([$day_id]);
        
        echo "<script>window.location.href = 'workout_plan_days.php?plan_id=" . $plan_id."';</script>";
        exit();
    }
}

// Get plan days
$days_query = "SELECT * FROM workout_plan_days WHERE plan_id = ? ORDER BY day_order";
$stmt = $db->prepare($days_query);
$stmt->execute([$plan_id]);
$days = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="page-header">
        <div class="header-actions">
            <h1 class="page-title">Manage Workout Days</h1>
            <a href="workout_plans.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Plans
            </a>
        </div>
        <div class="plan-info-banner">
            <h2 class="plan-name"><?php echo $plan['name']; ?></h2>
            <p class="plan-description"><?php echo $plan['description']; ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-plus-circle"></i>
                Add New Workout Day
            </h2>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="simple-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="day_order" class="form-label">Day Order</label>
                        <input type="number" id="day_order" name="day_order" class="form-input" 
                            min="1" max="7" required placeholder="e.g., 1, 2, 3...">
                    </div>
                    
                    <div class="form-group">
                        <label for="title" class="form-label">Day Title</label>
                        <input type="text" id="title" name="title" class="form-input" 
                            placeholder="e.g., Chest & Triceps, Leg Day, Full Body" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Description (Optional)</label>
                    <textarea id="description" name="description" class="form-input" 
                            rows="2" placeholder="Describe this workout day..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="add_day" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Workout Day
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Existing Days -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Workout Days</h2>
            <span class="badge"><?php echo count($days); ?> days</span>
        </div>
        
        <div class="card-body">
            <?php if ($days): ?>
                <div class="days-list">
                    <?php foreach ($days as $day): ?>
                    <div class="day-card">
                        <div class="day-header">
                            <div class="day-info">
                                <div class="day-title-section">
                                    <span class="day-order-badge">Day <?php echo $day['day_order']; ?></span>
                                    <h3 class="day-title"><?php echo $day['title']; ?></h3>
                                </div>
                                <?php if ($day['description']): ?>
                                <p class="day-description"><?php echo $day['description']; ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="day-actions">
                                <a href="workout_exercises.php?day_id=<?php echo $day['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-dumbbell"></i> Manage Exercises
                                </a>
                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="day_id" value="<?php echo $day['id']; ?>">
                                    <button type="submit" name="delete_day" class="btn btn-danger" 
                                            onclick="return confirm('Are you sure you want to delete this workout day? This will also delete all associated exercises.')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Show exercises preview -->
                        <?php
                        $exercises_query = "SELECT * FROM workout_exercises WHERE plan_day_id = ? ORDER BY id";
                        $stmt = $db->prepare($exercises_query);
                        $stmt->execute([$day['id']]);
                        $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <?php if ($exercises): ?>
                        <div class="exercises-preview">
                            <h4 class="section-title">Exercises (<?php echo count($exercises); ?>)</h4>
                            <div class="exercises-grid">
                                <?php foreach ($exercises as $exercise): ?>
                                <div class="exercise-item">
                                    <div class="exercise-info">
                                        <div class="exercise-name"><?php echo $exercise['exercise_name']; ?></div>
                                        <?php if ($exercise['notes']): ?>
                                        <div class="exercise-notes"><?php echo $exercise['notes']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="exercise-sets">
                                        <?php echo $exercise['default_sets']; ?> sets × <?php echo $exercise['default_reps']; ?> reps
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="empty-state-small">
                            <i class="fas fa-dumbbell"></i>
                            <p>No exercises added to this day yet.</p>
                            <a href="workout_exercises.php?day_id=<?php echo $day['id']; ?>" class="btn btn-outline btn-sm">
                                <i class="fas fa-plus"></i> Add Exercises
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-day empty-icon"></i>
                    <h3>No Workout Days</h3>
                    <p>No workout days created yet. Add your first day above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.header-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.btn-danger {
    background: #f44336;
    color: white;
    border: 1px solid #f44336;
}

.btn-danger:hover {
    background: #d32f2f;
    border-color: #d32f2f;
}

.plan-info-banner {
    background: var(--gradient-primary);
    padding: 1.5rem;
    border-radius: var(--radius-lg);
    margin-bottom: 2rem;
}

.plan-name {
    color: var(--accent);
    margin-bottom: 0.5rem;
    font-size: 1.5rem;
}

.alert {
    padding: 1rem;
    border-radius: var(--radius);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-error {
    background: rgba(244, 67, 54, 0.1);
    color: #f44336;
    border: 1px solid rgba(244, 67, 54, 0.2);
}

.days-list {
    display: grid;
    gap: 1.5rem;
}

.day-card {
    background: var(--card-bg);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    border: 1px solid var(--border);
}

.day-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    gap: 1rem;
}

.day-info {
    flex: 1;
}

.day-title-section {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
}

.day-order-badge {
    background: var(--primary);
    color: white;
    padding: 0.3rem 0.7rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    flex-shrink: 0;
}

.day-title {
    color: var(--accent);
    margin: 0;
    font-size: 1.3rem;
}

.day-description {
    color: var(--light-text);
    margin: 0;
    line-height: 1.5;
}

.day-actions {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
}

.exercises-preview {
    border-top: 1px solid var(--border);
    padding-top: 1.5rem;
}

.exercises-grid {
    display: grid;
    gap: 0.75rem;
}

.exercise-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--glass-bg);
    border-radius: var(--radius);
    border: 1px solid var(--border-light);
}

.exercise-info {
    flex: 1;
}

.exercise-name {
    font-weight: 600;
    color: var(--text);
    margin-bottom: 0.25rem;
}

.exercise-notes {
    font-size: 0.85rem;
    color: var(--light-text);
    line-height: 1.4;
}

.exercise-sets {
    font-size: 0.9rem;
    color: var(--light-text);
    font-weight: 600;
    flex-shrink: 0;
    margin-left: 1rem;
}

.empty-state-small {
    border-top: 1px solid var(--border);
    padding-top: 1.5rem;
    text-align: center;
    color: var(--light-text);
}

.empty-state-small i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
}

@media (max-width: 768px) {
    .header-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .day-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .day-actions {
        justify-content: flex-start;
        margin-top: 1rem;
    }
    
    .exercise-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .exercise-sets {
        margin-left: 0;
        align-self: flex-end;
    }
    
    .day-title-section {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
.simple-form .form-row {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.simple-form .form-group {
    margin-bottom: 1.5rem;
}

.simple-form .form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--text);
    font-size: 0.95rem;
}

.simple-form .form-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: var(--input-bg, var(--glass-bg));
    color: var(--text);
    font-size: 0.95rem;
    transition: border-color 0.2s ease;
    box-sizing: border-box;
}

.simple-form .form-input:focus {
    outline: none;
    border-color: var(--primary);
}

.simple-form .form-input::placeholder {
    color: var(--light-text);
    opacity: 0.7;
}

.simple-form .form-actions {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-light);
}

.card {
    background: var(--card-bg);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border);
    margin-bottom: 1.5rem;
}

.card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border);
    background: var(--glass-bg);
}

.card-title {
    color: var(--accent);
    margin: 0;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-title i {
    color: var(--primary);
}

.card-body {
    padding: 1.5rem;
}

.alert {
    padding: 1rem;
    border-radius: var(--radius);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-error {
    background: rgba(244, 67, 54, 0.1);
    color: #f44336;
    border: 1px solid rgba(244, 67, 54, 0.2);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: 1px solid;
    border-radius: var(--radius);
    font-weight: 600;
    font-size: 0.95rem;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

/* Responsive */
@media (max-width: 768px) {
    .card-body {
        padding: 1.25rem;
    }
    
    .card-header {
        padding: 1rem 1.25rem;
    }
    
    .simple-form .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
}
</style>

<?php require_once '../footer.php'; ?>