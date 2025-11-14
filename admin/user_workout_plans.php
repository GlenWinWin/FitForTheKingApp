<?php
// admin/user_workout_plans.php - Assign workout plans to users
$pageTitle = "Assign Workout Plans";
require_once '../header.php';
requireAdmin();

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    echo "<script>window.location.href = 'users.php';</script>";
    exit();
}

// Get user details
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($user_query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle plan assignment
if ($_POST && isset($_POST['assign_plan'])) {
    $plan_id = sanitize($_POST['plan_id']);
    
    // Check if user already has this plan assigned
    $check_query = "SELECT * FROM user_selected_plans WHERE user_id = ? AND plan_id = ?";
    $stmt = $db->prepare($check_query);
    $stmt->execute([$user_id, $plan_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing) {
        $insert_query = "INSERT INTO user_selected_plans (user_id, plan_id, selected_at) VALUES (?, ?, NOW())";
        $stmt = $db->prepare($insert_query);
        $stmt->execute([$user_id, $plan_id]);
        
        $success_message = "Workout plan assigned successfully!";
    } else {
        $error_message = "This user already has this workout plan assigned.";
    }
}

// Handle plan removal
if ($_POST && isset($_POST['remove_plan'])) {
    $user_plan_id = sanitize($_POST['user_plan_id']);
    
    $delete_query = "DELETE FROM user_selected_plans WHERE id = ?";
    $stmt = $db->prepare($delete_query);
    $stmt->execute([$user_plan_id]);
    
    $success_message = "Workout plan removed successfully!";
}

// Get all available workout plans
$plans_query = "SELECT wp.*, u.name as creator_name,
               (SELECT COUNT(*) FROM workout_plan_days WHERE plan_id = wp.id) as days_count,
               (SELECT COUNT(*) FROM user_selected_plans WHERE plan_id = wp.id) as users_count
               FROM workout_plans wp 
               JOIN users u ON wp.created_by = u.id 
               ORDER BY wp.created_at DESC";
$stmt = $db->prepare($plans_query);
$stmt->execute();
$available_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's current assigned plans - FIXED QUERY
$user_plans_query = "SELECT 
                    usp.*, 
                    wp.name, 
                    wp.description, 
                    u.name as creator_name,
                    (SELECT COUNT(*) FROM workout_plan_days WHERE plan_id = wp.id) as days_count
                    FROM user_selected_plans usp
                    JOIN workout_plans wp ON usp.plan_id = wp.id
                    JOIN users u ON wp.created_by = u.id
                    WHERE usp.user_id = ?
                    ORDER BY usp.selected_at DESC";
$stmt = $db->prepare($user_plans_query);
$stmt->execute([$user_id]);
$user_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Assign Workout Plans: <?php echo $user['name']; ?></h1>
        <div style="display: flex; gap: 1rem;">
            <a href="user_detail.php?id=<?php echo $user_id; ?>" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to User Details
            </a>
            <a href="users.php" class="btn btn-outline">
                <i class="fas fa-users"></i> All Users
            </a>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
        <div>
            <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
            <p><strong>Joined:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
        </div>
        <div>
            <p><strong>Role:</strong> <?php echo $user['role']; ?></p>
            <p><strong>Assigned Plans:</strong> <?php echo count($user_plans); ?></p>
        </div>
    </div>
</div>

<?php if (isset($success_message)): ?>
<div class="card">
    <div class="message success">
        <?php echo $success_message; ?>
    </div>
</div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
<div class="card">
    <div class="message error">
        <?php echo $error_message; ?>
    </div>
</div>
<?php endif; ?>

<!-- Assign New Plan -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-plus-circle"></i>
            Assign New Workout Plan
        </h2>
    </div>
    <div class="card-body">
        <form method="POST" class="simple-form">
            <div class="form-group">
                <label for="plan_id" class="form-label">Select Workout Plan</label>
                <select id="plan_id" name="plan_id" class="form-input" required>
                    <option value="">Choose a workout plan...</option>
                    <?php foreach ($available_plans as $plan): 
                        $is_assigned = false;
                        foreach ($user_plans as $user_plan) {
                            if ($user_plan['plan_id'] == $plan['id']) {
                                $is_assigned = true;
                                break;
                            }
                        }
                    ?>
                        <option value="<?php echo $plan['id']; ?>" <?php echo $is_assigned ? 'disabled' : ''; ?>>
                            <?php echo $plan['name']; ?> 
                            (<?php echo $plan['days_count']; ?> days, <?php echo $plan['users_count']; ?> users)
                            <?php echo $is_assigned ? ' - Already Assigned' : ''; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-help">Select a workout plan to assign to this user</div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="assign_plan" class="btn btn-primary">
                    <i class="fas fa-check"></i> Assign Workout Plan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- User's Current Plans -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Currently Assigned Plans</h2>
        <span class="badge"><?php echo count($user_plans); ?> assigned</span>
    </div>
    
    <div class="card-body">
        <?php if ($user_plans): ?>
            <div class="grid-layout">
                <?php foreach ($user_plans as $user_plan): ?>
                <div class="plan-card assigned">
                    <div class="plan-header">
                        <div class="plan-info">
                            <h3 class="plan-title"><?php echo $user_plan['name']; ?></h3>
                            <p class="plan-description"><?php echo $user_plan['description']; ?></p>
                            <div class="plan-meta">
                                <span class="meta-item">
                                    <i class="fas fa-calendar"></i> <?php echo $user_plan['days_count']; ?> days
                                </span>
                                <span class="meta-item">
                                    <i class="fas fa-user"></i> Created by <?php echo $user_plan['creator_name']; ?>
                                </span>
                                <span class="meta-item">
                                    <i class="fas fa-clock"></i> <?php echo date('M j, Y g:i A', strtotime($user_plan['selected_at'])); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="plan-actions">
                            <a href="workout_plan_days.php?plan_id=<?php echo $user_plan['plan_id']; ?>" class="btn btn-outline" target="_blank">
                                <i class="fas fa-eye"></i> View Plan
                            </a>
                            <form method="POST" class="inline-form">
                                <input type="hidden" name="user_plan_id" value="<?php echo $user_plan['id']; ?>">
                                <button type="submit" name="remove_plan" class="btn btn-danger" 
                                        onclick="return confirm('Are you sure you want to remove this workout plan from the user?')">
                                    <i class="fas fa-times"></i> Remove
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Show plan days preview -->
                    <?php
                    $days_query = "SELECT * FROM workout_plan_days WHERE plan_id = ? ORDER BY day_order";
                    $stmt = $db->prepare($days_query);
                    $stmt->execute([$user_plan['plan_id']]);
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
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-dumbbell empty-icon"></i>
                <h3>No Plans Assigned</h3>
                <p>This user doesn't have any workout plans assigned yet. Use the form above to assign their first plan.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Available Plans Overview -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Available Workout Plans</h2>
        <span class="badge"><?php echo count($available_plans); ?> total</span>
    </div>
    
    <div class="card-body">
        <?php if ($available_plans): ?>
            <div class="plans-overview">
                <?php foreach ($available_plans as $plan): 
                    $is_assigned = false;
                    foreach ($user_plans as $user_plan) {
                        if ($user_plan['plan_id'] == $plan['id']) {
                            $is_assigned = true;
                            break;
                        }
                    }
                ?>
                <div class="plan-overview-card <?php echo $is_assigned ? 'assigned' : ''; ?>">
                    <div class="plan-overview-header">
                        <h4 class="plan-overview-title">
                            <?php echo $plan['name']; ?>
                            <?php if ($is_assigned): ?>
                                <span class="assigned-badge">Assigned</span>
                            <?php endif; ?>
                        </h4>
                        <div class="plan-overview-stats">
                            <span class="stat"><?php echo $plan['days_count']; ?> days</span>
                            <span class="stat"><?php echo $plan['users_count']; ?> users</span>
                        </div>
                    </div>
                    
                    <p class="plan-overview-description"><?php echo $plan['description']; ?></p>
                    
                    <div class="plan-overview-meta">
                        <span class="meta">Created by <?php echo $plan['creator_name']; ?></span>
                        <span class="meta"><?php echo date('M j, Y', strtotime($plan['created_at'])); ?></span>
                    </div>
                    
                    <div class="plan-overview-actions">
                        <a href="workout_plan_days.php?plan_id=<?php echo $plan['id']; ?>" class="btn btn-sm btn-outline" target="_blank">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <?php if (!$is_assigned): ?>
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                            <button type="submit" name="assign_plan" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Assign
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-dumbbell empty-icon"></i>
                <h3>No Workout Plans Available</h3>
                <p>No workout plans have been created yet. <a href="workout_plans.php">Create the first workout plan</a>.</p>
            </div>
        <?php endif; ?>
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

.plan-card.assigned {
    border-left: 4px solid var(--success);
}

.plan-overview-card {
    background: var(--glass-bg);
    border-radius: var(--radius);
    padding: 1.5rem;
    border: 1px solid var(--border);
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.plan-overview-card.assigned {
    border-left: 4px solid var(--success);
    background: rgba(var(--success-rgb), 0.05);
}

.plan-overview-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.plan-overview-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    gap: 1rem;
}

.plan-overview-title {
    color: var(--accent);
    margin: 0;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.assigned-badge {
    background: var(--success);
    color: white;
    padding: 0.2rem 0.6rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
}

.plan-overview-stats {
    display: flex;
    gap: 1rem;
    flex-shrink: 0;
}

.plan-overview-stats .stat {
    background: var(--primary);
    color: white;
    padding: 0.3rem 0.7rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.plan-overview-description {
    color: var(--light-text);
    margin-bottom: 1rem;
    line-height: 1.5;
}

.plan-overview-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.85rem;
}

.plan-overview-meta .meta {
    color: var(--light-text);
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.plan-overview-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
}

.form-help {
    font-size: 0.85rem;
    color: var(--light-text);
    margin-top: 0.5rem;
}

.message {
    padding: 1rem 1.5rem;
    border-radius: var(--radius);
    margin-bottom: 1rem;
    font-weight: 600;
}

.message.success {
    background: rgba(var(--success-rgb), 0.1);
    color: var(--success);
    border: 1px solid rgba(var(--success-rgb), 0.3);
}

.message.error {
    background: rgba(var(--error-rgb), 0.1);
    color: var(--error);
    border: 1px solid rgba(var(--error-rgb), 0.3);
}

.grid-layout {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.plans-overview {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Simple Form Styles */
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

.simple-form .form-actions {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-light);
}

/* Card Styles */
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
    display: flex;
    justify-content: space-between;
    align-items: center;
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

/* Button Styles */
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
    background: var(--gradient-accent);
    color: white;
    box-shadow: 0 8px 20px rgba(26, 35, 126, 0.3);
}

.btn-outline {
    background: transparent;
    color: var(--text);
    border-color: var(--border);
}

.btn-outline:hover {
    background: var(--glass-bg);
    border-color: var(--accent);
    color: var(--accent);
}

.btn-danger {
    background: #f44336;
    color: white;
    border-color: #f44336;
}

.btn-danger:hover {
    background: #d32f2f;
    border-color: #d32f2f;
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

/* Plan Header Styles */
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

/* Days Grid Styles */
.days-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
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

/* Responsive */
@media (max-width: 768px) {
    .plan-overview-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .plan-overview-stats {
        justify-content: flex-start;
    }
    
    .plan-overview-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .plan-overview-actions {
        justify-content: flex-start;
    }
    
    .plan-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .plan-actions {
        justify-content: flex-start;
        margin-top: 1rem;
    }
    
    .card-body {
        padding: 1.25rem;
    }
    
    .card-header {
        padding: 1rem 1.25rem;
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .days-grid {
        grid-template-columns: 1fr;
    }
    
    .plan-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
}

@media (max-width: 480px) {
    .plan-overview-actions {
        flex-direction: column;
    }
    
    .plan-overview-actions .btn {
        width: 100%;
        justify-content: center;
    }
    
    .plan-actions {
        flex-direction: column;
    }
    
    .plan-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php require_once '../footer.php'; ?>