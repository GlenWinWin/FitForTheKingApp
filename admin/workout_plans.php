<?php
$pageTitle = "Manage Workout Plans";
require_once '../header.php';
requireAdmin();

$user_id = $_SESSION['user_id'];

// Handle form submissions
if ($_POST) {
    if (isset($_POST['create_plan'])) {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        
        $insert_query = "INSERT INTO workout_plans (name, description, created_by) VALUES (?, ?, ?)";
        $stmt = $db->prepare($insert_query);
        $stmt->execute([$name, $description, $user_id]);
        
        echo "<script>window.location.href = 'workout_plans.php';</script>";
        exit();
    }
    
    if (isset($_POST['delete_plan'])) {
        $plan_id = sanitize($_POST['plan_id']);
        
        $delete_query = "DELETE FROM workout_plans WHERE id = ?";
        $stmt = $db->prepare($delete_query);
        $stmt->execute([$plan_id]);
        
        echo "<script>window.location.href = 'workout_plans.php';</script>";
        exit();
    }
}

// Get all workout plans
$plans_query = "SELECT wp.*, u.name as creator_name, 
               (SELECT COUNT(*) FROM workout_plan_days WHERE plan_id = wp.id) as days_count,
               (SELECT COUNT(*) FROM user_selected_plans WHERE plan_id = wp.id) as users_count
               FROM workout_plans wp 
               JOIN users u ON wp.created_by = u.id 
               ORDER BY wp.created_at DESC";
$stmt = $db->prepare($plans_query);
$stmt->execute();
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <h1 class="card-title">Manage Workout Plans</h1>
    <p style="color: var(--light-text); margin-bottom: 2rem;">
        Create and manage workout plans that users can select. Each plan can have multiple days with specific exercises.
    </p>
</div>

<!-- Create New Plan Form -->
<div class="card">
    <h2 class="card-title">Create New Workout Plan</h2>
    <form method="POST">
        <div class="form-group">
            <label for="name">Plan Name</label>
            <input type="text" id="name" name="name" class="input" 
                   placeholder="e.g., Beginners Strength, 4-Day Split, Christian Warrior" required>
        </div>
        
        <div class="form-group">
            <label for="description">Plan Description</label>
            <textarea id="description" name="description" class="input" 
                      rows="3" placeholder="Describe this workout plan..."></textarea>
        </div>
        
        <button type="submit" name="create_plan" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Workout Plan
        </button>
    </form>
</div>

<!-- Existing Plans -->
<div class="card">
    <h2 class="card-title">Existing Workout Plans</h2>
    
    <?php if ($plans): ?>
        <div style="display: grid; gap: 1.5rem;">
            <?php foreach ($plans as $plan): ?>
            <div class="card" style="padding: 1.5rem;">
                <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <h3 style="color: var(--accent); margin-bottom: 0.5rem;"><?php echo $plan['name']; ?></h3>
                        <p style="color: var(--light-text); margin-bottom: 0.5rem;">
                            <?php echo $plan['description']; ?>
                        </p>
                        <div style="display: flex; gap: 1rem; font-size: 0.9rem; color: var(--light-text);">
                            <span><i class="fas fa-calendar"></i> <?php echo $plan['days_count']; ?> days</span>
                            <span><i class="fas fa-users"></i> <?php echo $plan['users_count']; ?> users</span>
                            <span><i class="fas fa-user"></i> Created by <?php echo $plan['creator_name']; ?></span>
                            <span><i class="fas fa-clock"></i> <?php echo date('M j, Y', strtotime($plan['created_at'])); ?></span>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="workout_plan_days.php?plan_id=<?php echo $plan['id']; ?>" class="btn btn-outline">
                            <i class="fas fa-calendar-day"></i> Manage Days
                        </a>
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                            <button type="submit" name="delete_plan" class="btn btn-outline" 
                                    onclick="return confirm('Are you sure you want to delete this workout plan? This will also delete all associated days and exercises.')"
                                    style="color: #f44336; border-color: #f44336;">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Show plan days preview -->
                <?php
                $days_query = "SELECT * FROM workout_plan_days WHERE plan_id = ? ORDER BY day_order";
                $stmt = $db->prepare($days_query);
                $stmt->execute([$plan['id']]);
                $days = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                
                <?php if ($days): ?>
                <div style="border-top: 1px solid var(--border); padding-top: 1rem;">
                    <h4 style="color: var(--text); margin-bottom: 1rem;">Plan Days</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <?php foreach ($days as $day): 
                            $exercises_query = "SELECT COUNT(*) as exercise_count FROM workout_exercises WHERE plan_day_id = ?";
                            $stmt = $db->prepare($exercises_query);
                            $stmt->execute([$day['id']]);
                            $exercise_count = $stmt->fetch(PDO::FETCH_ASSOC)['exercise_count'];
                        ?>
                        <div style="background: var(--glass-bg); padding: 1rem; border-radius: var(--radius);">
                            <div style="font-weight: 600; color: var(--accent);">Day <?php echo $day['day_order']; ?></div>
                            <div style="font-size: 0.9rem; color: var(--text); margin-bottom: 0.5rem;">
                                <?php echo $day['title']; ?>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--light-text);">
                                <?php echo $exercise_count; ?> exercises
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div style="border-top: 1px solid var(--border); padding-top: 1rem; text-align: center;">
                    <p style="color: var(--light-text); margin-bottom: 1rem;">
                        No days added to this plan yet.
                    </p>
                    <a href="workout_plan_days.php?plan_id=<?php echo $plan['id']; ?>" class="btn btn-outline">
                        <i class="fas fa-plus"></i> Add Workout Days
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 3rem; color: var(--light-text);">
            <i class="fas fa-dumbbell" style="font-size: 3rem; margin-bottom: 1rem;"></i>
            <p>No workout plans created yet. Create your first plan above.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../footer.php'; ?>