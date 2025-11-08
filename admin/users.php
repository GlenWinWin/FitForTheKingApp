<?php
$pageTitle = "Manage Users";
require_once '../header.php';
requireAdmin();

// Get all users
$users_query = "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC";
$stmt = $db->prepare($users_query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <h1 class="card-title">Manage Users</h1>
    <p style="color: var(--light-text); margin-bottom: 2rem;">
        View and manage all users in the system. Track their progress and engagement.
    </p>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">All Users (<?php echo count($users); ?>)</h2>
    </div>
    
    <?php if ($users): ?>
        <div style="display: grid; gap: 1rem;">
            <?php foreach ($users as $user): ?>
            <div class="card" style="padding: 1.5rem;">
                <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 1rem;">
                    <div>
                        <h3 style="color: var(--accent); margin-bottom: 0.5rem;">
                            <?php echo $user['name']; ?>
                            <?php if ($user['role'] === 'admin'): ?>
                                <span style="background: var(--gradient-accent); color: white; padding: 0.25rem 0.5rem; 
                                            border-radius: var(--radius); font-size: 0.8rem; margin-left: 0.5rem;">
                                    Admin
                                </span>
                            <?php endif; ?>
                        </h3>
                        <p style="color: var(--light-text); margin-bottom: 0.5rem;">
                            <?php echo $user['email']; ?>
                        </p>
                        <p style="color: var(--light-text); font-size: 0.9rem;">
                            Joined: <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                        </p>
                    </div>
                    <a href="user_detail.php?id=<?php echo $user['id']; ?>" class="btn btn-outline">
                        <i class="fas fa-chart-line"></i> View Progress
                    </a>
                </div>
                
                <?php
                // Get user stats
                $user_stats_query = "SELECT 
                                   (SELECT COUNT(*) FROM devotional_reads WHERE user_id = ?) as devotions_read,
                                   (SELECT COUNT(*) FROM weights WHERE user_id = ?) as weight_entries,
                                   (SELECT COUNT(*) FROM steps WHERE user_id = ?) as steps_entries,
                                   (SELECT COUNT(*) FROM workout_logs WHERE user_id = ?) as workouts_completed";
                $stmt = $db->prepare($user_stats_query);
                $stmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                ?>
                
                <div class="grid grid-4" style="gap: 1rem;">
                    <div style="text-align: center; padding: 1rem; background: var(--glass-bg); border-radius: var(--radius);">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--accent);">
                            <?php echo $stats['devotions_read']; ?>
                        </div>
                        <div style="font-size: 0.9rem; color: var(--light-text);">Devotions</div>
                    </div>
                    <div style="text-align: center; padding: 1rem; background: var(--glass-bg); border-radius: var(--radius);">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--accent);">
                            <?php echo $stats['weight_entries']; ?>
                        </div>
                        <div style="font-size: 0.9rem; color: var(--light-text);">Weight Entries</div>
                    </div>
                    <div style="text-align: center; padding: 1rem; background: var(--glass-bg); border-radius: var(--radius);">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--accent);">
                            <?php echo $stats['steps_entries']; ?>
                        </div>
                        <div style="font-size: 0.9rem; color: var(--light-text);">Steps Entries</div>
                    </div>
                    <div style="text-align: center; padding: 1rem; background: var(--glass-bg); border-radius: var(--radius);">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--accent);">
                            <?php echo $stats['workouts_completed']; ?>
                        </div>
                        <div style="font-size: 0.9rem; color: var(--light-text);">Workouts</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: var(--light-text); padding: 3rem;">
            No users found.
        </p>
    <?php endif; ?>
</div>

<?php require_once '../footer.php'; ?>