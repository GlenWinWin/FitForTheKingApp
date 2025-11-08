<?php
$pageTitle = "Admin Dashboard";
require_once '../header.php';
requireAdmin();

$user_id = $_SESSION['user_id'];

// Get stats for admin dashboard
$users_count_query = "SELECT COUNT(*) as count FROM users WHERE role = 'user'";
$stmt = $db->prepare($users_count_query);
$stmt->execute();
$users_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$prayers_count_query = "SELECT COUNT(*) as count FROM prayers";
$stmt = $db->prepare($prayers_count_query);
$stmt->execute();
$prayers_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$testimonials_count_query = "SELECT COUNT(*) as count FROM testimonials";
$stmt = $db->prepare($testimonials_count_query);
$stmt->execute();
$testimonials_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$workouts_count_query = "SELECT COUNT(*) as count FROM workout_plans";
$stmt = $db->prepare($workouts_count_query);
$stmt->execute();
$workouts_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>

<div class="card">
    <h1 class="card-title">Admin Dashboard</h1>
    <p style="color: var(--light-text); margin-bottom: 2rem;">
        Welcome to the admin panel. Manage users, workout plans, and track community engagement.
    </p>
</div>

<div class="grid grid-4">
    <div class="card stat-card">
        <div class="stat-number"><?php echo $users_count; ?></div>
        <div class="stat-label">Total Users</div>
        <a href="users.php" class="btn btn-outline" style="margin-top: 1rem; width: 100%;">
            <i class="fas fa-users"></i> Manage Users
        </a>
    </div>
    
    <div class="card stat-card">
        <div class="stat-number"><?php echo $prayers_count; ?></div>
        <div class="stat-label">Prayer Requests</div>
        <a href="../prayers_testimonials.php?tab=prayers" class="btn btn-outline" style="margin-top: 1rem; width: 100%;">
            <i class="fas fa-hands-praying"></i> View Prayers
        </a>
    </div>
    
    <div class="card stat-card">
        <div class="stat-number"><?php echo $testimonials_count; ?></div>
        <div class="stat-label">Testimonials</div>
        <a href="../prayers_testimonials.php?tab=testimonials" class="btn btn-outline" style="margin-top: 1rem; width: 100%;">
            <i class="fas fa-heart"></i> View Testimonies
        </a>
    </div>
    
    <div class="card stat-card">
        <div class="stat-number"><?php echo $workouts_count; ?></div>
        <div class="stat-label">Workout Plans</div>
        <a href="workout_plans.php" class="btn btn-outline" style="margin-top: 1rem; width: 100%;">
            <i class="fas fa-dumbbell"></i> Manage Plans
        </a>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h2 class="card-title">Quick Actions</h2>
        <div style="display: grid; gap: 1rem;">
            <a href="workout_plans.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Workout Plan
            </a>
            <a href="users.php" class="btn btn-outline">
                <i class="fas fa-user-cog"></i> Manage Users
            </a>
            <a href="../devotion_today.php" class="btn btn-outline">
                <i class="fas fa-bible"></i> View Today's Devotion
            </a>
        </div>
    </div>
    
    <div class="card">
        <h2 class="card-title">Recent Activity</h2>
        <?php
        // Get recent user registrations
        $recent_users_query = "SELECT name, email, created_at FROM users 
                              WHERE role = 'user' 
                              ORDER BY created_at DESC 
                              LIMIT 5";
        $stmt = $db->prepare($recent_users_query);
        $stmt->execute();
        $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <h3 style="color: var(--accent); margin-bottom: 1rem;">New Users</h3>
        <?php if ($recent_users): ?>
            <div style="display: grid; gap: 0.5rem;">
                <?php foreach ($recent_users as $user): ?>
                <div style="display: flex; justify-content: between; align-items: center; 
                           padding: 0.75rem; background: var(--glass-bg); border-radius: var(--radius);">
                    <div>
                        <div style="font-weight: 600;"><?php echo $user['name']; ?></div>
                        <div style="font-size: 0.9rem; color: var(--light-text);">
                            <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                        </div>
                    </div>
                    <span style="color: var(--light-text); font-size: 0.9rem;">
                        <?php echo $user['email']; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--light-text); padding: 2rem;">
                No users yet.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../footer.php'; ?>