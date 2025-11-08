<?php
$pageTitle = "Workouts";
require_once 'header.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Handle plan selection
if ($_POST && isset($_POST['select_plan'])) {
    $plan_id = sanitize($_POST['plan_id']);
    
    // Remove any existing plan selection
    $delete_query = "DELETE FROM user_selected_plans WHERE user_id = ?";
    $stmt = $db->prepare($delete_query);
    $stmt->execute([$user_id]);
    
    // Insert new selection
    $insert_query = "INSERT INTO user_selected_plans (user_id, plan_id) VALUES (?, ?)";
    $stmt = $db->prepare($insert_query);
    if ($stmt->execute([$user_id, $plan_id])) {
        echo "<script>window.location.href = 'workout_day.php';</script>";
        exit();
    }
}

// Get available workout plans
$plans_query = "SELECT * FROM workout_plans ORDER BY created_at DESC";
$stmt = $db->prepare($plans_query);
$stmt->execute();
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's current plan
$current_plan_query = "SELECT p.* FROM user_selected_plans up 
                      JOIN workout_plans p ON up.plan_id = p.id 
                      WHERE up.user_id = ?";
$stmt = $db->prepare($current_plan_query);
$stmt->execute([$user_id]);
$current_plan = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<style>
    .workouts-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .page-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .page-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 1rem;
    }
    
    .page-subtitle {
        font-size: 1.2rem;
        color: var(--light-text);
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }
    
    .current-plan-card {
        background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
        color: white;
        border-radius: var(--radius);
        padding: 2rem;
        margin-bottom: 3rem;
        position: relative;
        overflow: hidden;
    }
    
    .current-plan-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transform: translate(50%, -50%);
    }
    
    .current-plan-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(255, 255, 255, 0.2);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }
    
    .current-plan-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .current-plan-description {
        opacity: 0.9;
        margin-bottom: 1.5rem;
        line-height: 1.6;
    }
    
    .plans-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    .plan-card {
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius);
        padding: 2rem;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }
    
    .plan-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(26, 35, 126, 0.05), transparent);
        transition: left 0.7s;
    }
    
    .plan-card:hover::before {
        left: 100%;
    }
    
    .plan-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }
    
    .plan-header {
        display: flex;
        justify-content: between;
        align-items: flex-start;
        margin-bottom: 1.5rem;
    }
    
    .plan-title {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 0.5rem;
        flex: 1;
    }
    
    .plan-badge {
        background: var(--gradient-accent);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .plan-description {
        color: var(--light-text);
        line-height: 1.6;
        margin-bottom: 1.5rem;
        flex-grow: 1;
    }
    
    .plan-features {
        display: flex;
        justify-content: space-between;
        margin-bottom: 2rem;
        padding: 1rem 0;
        border-top: 1px solid var(--glass-border);
        border-bottom: 1px solid var(--glass-border);
    }
    
    .feature {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 0.5rem;
    }
    
    .feature-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--gradient-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent);
        font-size: 1rem;
    }
    
    .feature-label {
        font-size: 0.8rem;
        color: var(--light-text);
    }
    
    .feature-value {
        font-weight: 600;
        color: var(--text);
    }
    
    .plan-action {
        width: 100%;
    }
    
    .btn-selected {
        background: rgba(76, 175, 80, 0.1);
        color: #4CAF50;
        border: 2px solid rgba(76, 175, 80, 0.3);
        cursor: not-allowed;
    }
    
    .btn-selected:hover {
        transform: none;
        box-shadow: none;
    }
    
    @media (max-width: 768px) {
        .plans-grid {
            grid-template-columns: 1fr;
        }
        
        .page-title {
            font-size: 2rem;
        }
        
        .current-plan-card {
            padding: 1.5rem;
        }
        
        .plan-card {
            padding: 1.5rem;
        }
        
        .plan-features {
            flex-direction: column;
            gap: 1rem;
        }
    }
</style>

<div class="workouts-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">Workout Plans</h1>
        <p class="page-subtitle">
            Choose a workout plan that fits your fitness level and goals. Each plan is designed to help you honor God with your physical health.
        </p>
    </div>

    <!-- Current Plan Section -->
    <?php if ($current_plan): 
        // Get plan days count
        $days_query = "SELECT COUNT(*) as days_count FROM workout_plan_days WHERE plan_id = ?";
        $stmt = $db->prepare($days_query);
        $stmt->execute([$current_plan['id']]);
        $days_count = $stmt->fetch(PDO::FETCH_ASSOC)['days_count'];
    ?>
    <div class="current-plan-card">
        <div class="current-plan-badge">
            <i class="fas fa-crown"></i>
            Active Plan
        </div>
        <h2 class="current-plan-title"><?php echo htmlspecialchars($current_plan['name']); ?></h2>
        <p class="current-plan-description"><?php echo htmlspecialchars($current_plan['description']); ?></p>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="workout_day.php" class="btn" style="background: rgba(255, 255, 255, 0.2); color: white; border: 1px solid rgba(255, 255, 255, 0.3);">
                <i class="fas fa-dumbbell"></i> Start Today's Workout
            </a>
            <a href="workout_progress.php" class="btn" style="background: transparent; color: white; border: 1px solid rgba(255, 255, 255, 0.3);">
                <i class="fas fa-chart-line"></i> View Progress
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Workout Plans Grid -->
    <div class="plans-grid">
        <?php foreach ($plans as $plan): 
            // Get plan days count
            $days_query = "SELECT COUNT(*) as days_count FROM workout_plan_days WHERE plan_id = ?";
            $stmt = $db->prepare($days_query);
            $stmt->execute([$plan['id']]);
            $days_count = $stmt->fetch(PDO::FETCH_ASSOC)['days_count'];
            
            $is_current = $current_plan && $current_plan['id'] == $plan['id'];
        ?>
        <div class="plan-card">
            <div class="plan-header">
                <div style="flex: 1;">
                    <h3 class="plan-title"><?php echo htmlspecialchars($plan['name']); ?></h3>
                </div>
                <?php if ($is_current): ?>
                    <div class="plan-badge">
                        <i class="fas fa-check"></i> Active
                    </div>
                <?php endif; ?>
            </div>
            
            <p class="plan-description"><?php echo htmlspecialchars($plan['description']); ?></p>
            
            <div class="plan-features">
                <div class="feature">
                    <div class="feature-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="feature-value"><?php echo $days_count; ?> days</div>
                    <div class="feature-label">Duration</div>
                </div>
                
                <div class="feature">
                    <div class="feature-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <div class="feature-value">Strength</div>
                    <div class="feature-label">Focus</div>
                </div>
                
                <div class="feature">
                    <div class="feature-icon">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div class="feature-value">Intermediate</div>
                    <div class="feature-label">Level</div>
                </div>
            </div>
            
            <form method="POST">
                <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                <button type="submit" name="select_plan" class="btn <?php echo $is_current ? 'btn-selected' : 'btn-primary'; ?> plan-action" <?php echo $is_current ? 'disabled' : ''; ?>>
                    <?php if ($is_current): ?>
                        <i class="fas fa-check-circle"></i> Currently Selected
                    <?php else: ?>
                        <i class="fas fa-play-circle"></i> Select This Plan
                    <?php endif; ?>
                </button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>