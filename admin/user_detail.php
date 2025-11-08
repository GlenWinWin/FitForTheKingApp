<?php
// admin/user_detail.php - View individual user progress
$pageTitle = "User Progress Details";
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

// Get user stats
$stats_query = "SELECT 
               (SELECT COUNT(*) FROM devotional_reads WHERE user_id = ?) as devotions_read,
               (SELECT COUNT(*) FROM weights WHERE user_id = ?) as weight_entries,
               (SELECT COUNT(*) FROM steps WHERE user_id = ?) as steps_entries,
               (SELECT COUNT(*) FROM workout_logs WHERE user_id = ?) as workouts_completed,
               (SELECT COUNT(*) FROM prayers WHERE user_id = ?) as prayers_posted,
               (SELECT COUNT(*) FROM testimonials WHERE user_id = ?) as testimonials_posted";
$stmt = $db->prepare($stats_query);
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent activity
$recent_devotions = $db->prepare("SELECT d.title, dr.date_read FROM devotional_reads dr JOIN devotions d ON dr.devotion_id = d.id WHERE dr.user_id = ? ORDER BY dr.date_read DESC LIMIT 5");
$recent_devotions->execute([$user_id]);

$recent_workouts = $db->prepare("SELECT wl.completed_at, wp.name as plan_name FROM workout_logs wl LEFT JOIN workout_plans wp ON wl.plan_id = wp.id WHERE wl.user_id = ? ORDER BY wl.completed_at DESC LIMIT 5");
$recent_workouts->execute([$user_id]);
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">User Progress: <?php echo $user['name']; ?></h1>
        <a href="users.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
        <div>
            <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
            <p><strong>Joined:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
        </div>
        <div>
            <p><strong>Role:</strong> <?php echo $user['role']; ?></p>
            <p><strong>Account Age:</strong> <?php echo floor((time() - strtotime($user['created_at'])) / (60 * 60 * 24)); ?> days</p>
        </div>
    </div>
</div>

<div class="grid grid-3">
    <div class="card stat-card">
        <div class="stat-number"><?php echo $stats['devotions_read']; ?></div>
        <div class="stat-label">Devotions Read</div>
    </div>
    <div class="card stat-card">
        <div class="stat-number"><?php echo $stats['weight_entries']; ?></div>
        <div class="stat-label">Weight Entries</div>
    </div>
    <div class="card stat-card">
        <div class="stat-number"><?php echo $stats['workouts_completed']; ?></div>
        <div class="stat-label">Workouts Completed</div>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h2 class="card-title">Recent Devotions</h2>
        <?php if ($recent_devotions->rowCount() > 0): ?>
            <div style="display: grid; gap: 0.5rem;">
                <?php while ($devotion = $recent_devotions->fetch()): ?>
                <div style="display: flex; justify-content: between; align-items: center; padding: 1rem; background: var(--glass-bg); border-radius: var(--radius);">
                    <div>
                        <div style="font-weight: 600;"><?php echo $devotion['title']; ?></div>
                        <div style="font-size: 0.9rem; color: var(--light-text);">
                            <?php echo date('M j, Y', strtotime($devotion['date_read'])); ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--light-text); padding: 2rem;">No devotions read yet.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 class="card-title">Recent Workouts</h2>
        <?php if ($recent_workouts->rowCount() > 0): ?>
            <div style="display: grid; gap: 0.5rem;">
                <?php while ($workout = $recent_workouts->fetch()): ?>
                <div style="display: flex; justify-content: between; align-items: center; padding: 1rem; background: var(--glass-bg); border-radius: var(--radius);">
                    <div>
                        <div style="font-weight: 600;"><?php echo $workout['plan_name'] ?: 'Workout'; ?></div>
                        <div style="font-size: 0.9rem; color: var(--light-text);">
                            <?php echo date('M j, Y g:i A', strtotime($workout['completed_at'])); ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--light-text); padding: 2rem;">No workouts completed yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../footer.php'; ?>