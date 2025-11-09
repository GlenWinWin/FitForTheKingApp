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

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Manage Workout Plans</h1>
        <p class="page-description">
            Create and manage workout plans that users can select. Each plan can have multiple days with specific exercises.
        </p>
    </div>

    <!-- Create New Plan Form -->
    
    <!-- Create New Plan Form -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-plus-circle"></i>
                Create New Workout Plan
            </h2>
        </div>
        <div class="card-body">
            <form method="POST" class="simple-form">
                <div class="form-group">
                    <label for="name" class="form-label">Plan Name</label>
                    <input type="text" id="name" name="name" class="form-input" 
                        placeholder="e.g., Beginners Strength, 4-Day Split, Christian Warrior" required>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Plan Description</label>
                    <textarea id="description" name="description" class="form-input" 
                            rows="3" placeholder="Describe this workout plan..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="create_plan" class="btn btn-outline">
                        <i class="fas fa-plus"></i> Create Workout Plan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Existing Plans -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Existing Workout Plans</h2>
            <span class="badge"><?php echo count($plans); ?> total</span>
        </div>
        
        <div class="card-body">
            <?php if ($plans): ?>
                <div class="grid-layout">
                    <?php foreach ($plans as $plan): ?>
                    <div class="plan-card">
                        <div class="plan-header">
                            <div class="plan-info">
                                <h3 class="plan-title"><?php echo $plan['name']; ?></h3>
                                <p class="plan-description"><?php echo $plan['description']; ?></p>
                                <div class="plan-meta">
                                    <span class="meta-item">
                                        <i class="fas fa-calendar"></i> <?php echo $plan['days_count']; ?> days
                                    </span>
                                    <span class="meta-item">
                                        <i class="fas fa-users"></i> <?php echo $plan['users_count']; ?> users
                                    </span>
                                    <span class="meta-item">
                                        <i class="fas fa-user"></i> <?php echo $plan['creator_name']; ?>
                                    </span>
                                    <span class="meta-item">
                                        <i class="fas fa-clock"></i> <?php echo date('M j, Y', strtotime($plan['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="plan-actions">
                                <a href="workout_plan_days.php?plan_id=<?php echo $plan['id']; ?>" class="btn btn-outline">
                                    <i class="fas fa-calendar-day"></i> Manage Days
                                </a>
                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                    <button type="submit" name="delete_plan" class="btn btn-danger" 
                                            onclick="return confirm('Are you sure you want to delete this workout plan? This will also delete all associated days and exercises.')">
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
                        <div class="plan-days">
                            <h4 class="section-title">Plan Days</h4>
                            <div class="days-grid">
                                <?php foreach ($days as $day): 
                                    $exercises_query = "SELECT COUNT(*) as exercise_count FROM workout_exercises WHERE plan_day_id = ?";
                                    $stmt = $db->prepare($exercises_query);
                                    $stmt->execute([$day['id']]);
                                    $exercise_count = $stmt->fetch(PDO::FETCH_ASSOC)['exercise_count'];
                                ?>
                                <div class="day-card">
                                    <div class="day-header">
                                        <span class="day-order">Day <?php echo $day['day_order']; ?></span>
                                        <span class="exercise-count"><?php echo $exercise_count; ?> ex</span>
                                    </div>
                                    <div class="day-title"><?php echo $day['title']; ?></div>
                                    <?php if ($day['description']): ?>
                                    <div class="day-description"><?php echo $day['description']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-plus empty-icon"></i>
                            <p>No days added to this plan yet.</p>
                            <a href="workout_plan_days.php?plan_id=<?php echo $plan['id']; ?>" class="btn btn-outline">
                                <i class="fas fa-plus"></i> Add Workout Days
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-dumbbell empty-icon"></i>
                    <h3>No Workout Plans</h3>
                    <p>No workout plans created yet. Create your first plan above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.page-header {
    margin-bottom: 2rem;
}

.page-title {
    color: var(--accent);
    margin-bottom: 0.5rem;
    font-size: 2rem;
}

.page-description {
    color: var(--light-text);
    font-size: 1.1rem;
    margin-bottom: 0;
}

.card-form {
    margin-bottom: 2rem;
}

.form-grid {
    display: grid;
    gap: 1.5rem;
}

.form-actions {
    margin-top: 1.5rem;
}

.plan-card {
    background: var(--card-bg);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    border: 1px solid var(--border);
    margin-bottom: 1.5rem;
}

.plan-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    gap: 1rem;
}

.plan-info {
    flex: 1;
}

.plan-title {
    color: var(--accent);
    margin-bottom: 0.5rem;
    font-size: 1.4rem;
}

.plan-description {
    color: var(--light-text);
    margin-bottom: 1rem;
    line-height: 1.5;
}

.plan-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    font-size: 0.9rem;
}

.meta-item {
    color: var(--light-text);
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.plan-actions {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
}

.plan-days {
    border-top: 1px solid var(--border);
    padding-top: 1.5rem;
}

.section-title {
    color: var(--text);
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.days-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
}

.day-card {
    background: var(--glass-bg);
    padding: 1rem;
    border-radius: var(--radius);
    border: 1px solid var(--border-light);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.day-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.day-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.day-order {
    font-weight: 600;
    color: var(--accent);
    font-size: 0.9rem;
}

.exercise-count {
    background: var(--primary);
    color: white;
    padding: 0.2rem 0.5rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.day-title {
    font-weight: 600;
    color: var(--text);
    margin-bottom: 0.3rem;
}

.day-description {
    font-size: 0.85rem;
    color: var(--light-text);
    line-height: 1.4;
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: var(--light-text);
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h3 {
    margin-bottom: 0.5rem;
    color: var(--text);
}

.badge {
    background: var(--primary);
    color: white;
    padding: 0.3rem 0.7rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.inline-form {
    display: inline;
}

.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
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

@media (max-width: 768px) {
    .plan-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .plan-actions {
        justify-content: flex-start;
        margin-top: 1rem;
    }
    
    .days-grid {
        grid-template-columns: 1fr;
    }
    
    .plan-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
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

.btn-primary {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.btn-primary:hover {
    background: var(--primary-dark);
    border-color: var(--primary-dark);
    transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 768px) {
    .card-body {
        padding: 1.25rem;
    }
    
    .card-header {
        padding: 1rem 1.25rem;
    }
}
</style>

<?php require_once '../footer.php'; ?>