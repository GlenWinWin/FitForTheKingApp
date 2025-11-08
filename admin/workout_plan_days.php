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

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Manage Workout Days: <?php echo $plan['name']; ?></h1>
        <a href="workout_plans.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Back to Plans
        </a>
    </div>
    <p style="color: var(--light-text);">
        <?php echo $plan['description']; ?>
    </p>
</div>

<!-- Add New Day Form -->
<div class="card">
    <h2 class="card-title">Add New Workout Day</h2>
    
    <?php if (isset($error)): ?>
    <div style="background: rgba(244, 67, 54, 0.1); color: #f44336; padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem;">
        <?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <form method="POST">
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem;">
            <div class="form-group">
                <label for="day_order">Day Order</label>
                <input type="number" id="day_order" name="day_order" class="input" 
                       min="1" max="7" required placeholder="e.g., 1, 2, 3...">
            </div>
            
            <div class="form-group">
                <label for="title">Day Title</label>
                <input type="text" id="title" name="title" class="input" 
                       placeholder="e.g., Chest & Triceps, Leg Day, Full Body" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="description">Description (Optional)</label>
            <textarea id="description" name="description" class="input" 
                      rows="2" placeholder="Describe this workout day..."></textarea>
        </div>
        
        <button type="submit" name="add_day" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Workout Day
        </button>
    </form>
</div>

<!-- Existing Days -->
<div class="card">
    <h2 class="card-title">Workout Days (<?php echo count($days); ?>)</h2>
    
    <?php if ($days): ?>
        <div style="display: grid; gap: 1.5rem;">
            <?php foreach ($days as $day): ?>
            <div class="card" style="padding: 1.5rem;">
                <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <h3 style="color: var(--accent); margin-bottom: 0.5rem;">
                            Day <?php echo $day['day_order']; ?>: <?php echo $day['title']; ?>
                        </h3>
                        <?php if ($day['description']): ?>
                        <p style="color: var(--light-text); margin-bottom: 0.5rem;">
                            <?php echo $day['description']; ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="workout_exercises.php?day_id=<?php echo $day['id']; ?>" class="btn btn-outline">
                            <i class="fas fa-dumbbell"></i> Manage Exercises
                        </a>
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="day_id" value="<?php echo $day['id']; ?>">
                            <button type="submit" name="delete_day" class="btn btn-outline" 
                                    onclick="return confirm('Are you sure you want to delete this workout day? This will also delete all associated exercises.')"
                                    style="color: #f44336; border-color: #f44336;">
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
                <div style="border-top: 1px solid var(--border); padding-top: 1rem;">
                    <h4 style="color: var(--text); margin-bottom: 1rem;">Exercises (<?php echo count($exercises); ?>)</h4>
                    <div style="display: grid; gap: 0.5rem;">
                        <?php foreach ($exercises as $exercise): ?>
                        <div style="display: flex; justify-content: between; align-items: center; 
                                   padding: 1rem; background: var(--glass-bg); border-radius: var(--radius);">
                            <div>
                                <div style="font-weight: 600; color: var(--text);">
                                    <?php echo $exercise['exercise_name']; ?>
                                </div>
                                <?php if ($exercise['notes']): ?>
                                <div style="font-size: 0.9rem; color: var(--light-text);">
                                    <?php echo $exercise['notes']; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 0.9rem; color: var(--light-text);">
                                <?php echo $exercise['default_sets']; ?> sets × <?php echo $exercise['default_reps']; ?> reps
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div style="border-top: 1px solid var(--border); padding-top: 1rem; text-align: center;">
                    <p style="color: var(--light-text); margin-bottom: 1rem;">
                        No exercises added to this day yet.
                    </p>
                    <a href="workout_exercises.php?day_id=<?php echo $day['id']; ?>" class="btn btn-outline">
                        <i class="fas fa-plus"></i> Add Exercises
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 3rem; color: var(--light-text);">
            <i class="fas fa-calendar-day" style="font-size: 3rem; margin-bottom: 1rem;"></i>
            <p>No workout days created yet. Add your first day above.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../footer.php'; ?>