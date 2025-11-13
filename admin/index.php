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

$pending_users_query = "SELECT COUNT(*) as count FROM users WHERE role = 'user' AND is_accept = 0";
$stmt = $db->prepare($pending_users_query);
$stmt->execute();
$pending_users_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

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
        <div class="stat-number" style="color: <?php echo $pending_users_count > 0 ? 'var(--accent)' : 'inherit'; ?>">
            <?php echo $pending_users_count; ?>
        </div>
        <div class="stat-label">Pending Users</div>
        <a href="users.php?filter=pending" class="btn btn-outline" style="margin-top: 1rem; width: 100%;">
            <i class="fas fa-user-clock"></i> Review Pending
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
</div>

<div class="grid grid-2">
    <div class="card">
        <h2 class="card-title">Quick Actions</h2>
        <div style="display: grid; gap: 1rem;">
            <?php if ($pending_users_count > 0): ?>
            <a href="users.php?filter=pending" class="btn btn-primary">
                <i class="fas fa-user-check"></i> Review Pending Users (<?php echo $pending_users_count; ?>)
            </a>
            <?php endif; ?>
            <a href="workout_plans.php" class="btn btn-outline">
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
        
        <!-- Pending Users Section -->
        <?php
        // Get pending users
        $pending_users_query = "SELECT name, email, created_at FROM users 
                              WHERE role = 'user' AND is_accept = 0
                              ORDER BY created_at DESC 
                              LIMIT 5";
        $stmt = $db->prepare($pending_users_query);
        $stmt->execute();
        $pending_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <h3 style="color: var(--accent); margin-bottom: 1rem;">Pending User Approvals</h3>
        <?php if ($pending_users): ?>
            <div style="display: grid; gap: 0.5rem; margin-bottom: 2rem;">
                <?php foreach ($pending_users as $user): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; 
                           padding: 0.75rem; background: var(--glass-bg); border-radius: var(--radius);">
                    <div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($user['name']); ?></div>
                        <div style="font-size: 0.9rem; color: var(--light-text);">
                            <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                        </div>
                    </div>
                    <span style="color: var(--light-text); font-size: 0.9rem;">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--light-text); padding: 1rem;">
                No pending users.
            </p>
        <?php endif; ?>
        
        <!-- Recent Users Section -->
        <?php
        // Get recent user registrations
        $recent_users_query = "SELECT name, email, created_at, is_accept FROM users 
                              WHERE role = 'user' 
                              ORDER BY created_at DESC 
                              LIMIT 5";
        $stmt = $db->prepare($recent_users_query);
        $stmt->execute();
        $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <h3 style="color: var(--accent); margin-bottom: 1rem;">Recent Users</h3>
        <?php if ($recent_users): ?>
            <div style="display: grid; gap: 0.5rem;">
                <?php foreach ($recent_users as $user): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; 
                           padding: 0.75rem; background: var(--glass-bg); border-radius: var(--radius);">
                    <div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($user['name']); ?></div>
                        <div style="font-size: 0.9rem; color: var(--light-text);">
                            <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                            <span style="margin-left: 0.5rem; padding: 0.2rem 0.5rem; border-radius: 1rem; 
                                  background: <?php echo $user['is_accept'] ? 'var(--success)' : 'var(--accent)'; ?>; 
                                  color: white; font-size: 0.8rem;">
                                <?php echo $user['is_accept'] ? 'Approved' : 'Pending'; ?>
                            </span>
                        </div>
                    </div>
                    <span style="color: var(--light-text); font-size: 0.9rem;">
                        <?php echo htmlspecialchars($user['email']); ?>
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