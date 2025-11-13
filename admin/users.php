<?php
$pageTitle = "Manage Users";
require_once '../header.php';
requireAdmin();

// Handle user approval
if ($_POST && isset($_POST['approve_user'])) {
    $user_id = $_POST['user_id'];
    
    $update_query = "UPDATE users SET is_accept = 1 WHERE id = ?";
    $stmt = $db->prepare($update_query);
    
    if ($stmt->execute([$user_id])) {
        $_SESSION['success'] = "User approved successfully!";
    } else {
        $_SESSION['error'] = "Error approving user.";
    }
    
        echo "<script>window.location.href = 'users.php';</script>";
    exit();
}

// Handle user rejection/removal
if ($_POST && isset($_POST['reject_user'])) {
    $user_id = $_POST['user_id'];
    
    $delete_query = "DELETE FROM users WHERE id = ? AND role = 'user'";
    $stmt = $db->prepare($delete_query);
    
    if ($stmt->execute([$user_id])) {
        $_SESSION['success'] = "User removed successfully!";
    } else {
        $_SESSION['error'] = "Error removing user.";
    }
    
        echo "<script>window.location.href = 'users.php';</script>";
    exit();
}

// Get filter parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build query based on filter
if ($filter === 'pending') {
    $users_query = "SELECT id, name, email, role, created_at, is_accept FROM users 
                   WHERE is_accept = 0 AND role = 'user' 
                   ORDER BY created_at DESC";
    $page_title = "Pending Users";
} elseif ($filter === 'approved') {
    $users_query = "SELECT id, name, email, role, created_at, is_accept FROM users 
                   WHERE is_accept = 1 
                   ORDER BY created_at DESC";
    $page_title = "Approved Users";
} else {
    $users_query = "SELECT id, name, email, role, created_at, is_accept FROM users 
                   ORDER BY created_at DESC";
    $page_title = "All Users";
}

$stmt = $db->prepare($users_query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts for filters
$counts_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_accept = 0 AND role = 'user' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN is_accept = 1 THEN 1 ELSE 0 END) as approved
    FROM users";
$stmt = $db->prepare($counts_query);
$stmt->execute();
$counts = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="card">
    <h1 class="card-title">Manage Users</h1>
    <p style="color: var(--light-text); margin-bottom: 2rem;">
        View and manage all users in the system. Track their progress and engagement.
    </p>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="message success">
        <i class="fas fa-check-circle"></i>
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="message error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<!-- Quick Stats -->
<div class="grid grid-3">
    <div class="card stat-card">
        <div class="stat-number"><?php echo $counts['total']; ?></div>
        <div class="stat-label">Total Users</div>
        <a href="users.php?filter=all" class="btn btn-outline" style="margin-top: 1rem; width: 100%;">
            <i class="fas fa-users"></i> View All
        </a>
    </div>
    
    <div class="card stat-card">
        <div class="stat-number" style="color: <?php echo $counts['pending'] > 0 ? 'var(--accent)' : 'inherit'; ?>">
            <?php echo $counts['pending']; ?>
        </div>
        <div class="stat-label">Pending Approval</div>
        <a href="users.php?filter=pending" class="btn btn-outline" style="margin-top: 1rem; width: 100%;">
            <i class="fas fa-user-clock"></i> Review Pending
        </a>
    </div>
    
    <div class="card stat-card">
        <div class="stat-number"><?php echo $counts['approved']; ?></div>
        <div class="stat-label">Approved Users</div>
        <a href="users.php?filter=approved" class="btn btn-outline" style="margin-top: 1rem; width: 100%;">
            <i class="fas fa-user-check"></i> View Approved
        </a>
    </div>
</div>

<!-- Filter Tabs -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?php echo $page_title; ?> (<?php echo count($users); ?>)</h2>
        <div style="display: flex; gap: 0.5rem;">
            <a href="users.php?filter=all" class="btn btn-outline <?php echo $filter === 'all' ? 'active' : ''; ?>">
                All (<?php echo $counts['total']; ?>)
            </a>
            <a href="users.php?filter=pending" class="btn btn-outline <?php echo $filter === 'pending' ? 'active' : ''; ?>" 
               style="<?php echo $counts['pending'] > 0 ? 'color: var(--accent); border-color: var(--accent);' : ''; ?>">
                Pending (<?php echo $counts['pending']; ?>)
            </a>
            <a href="users.php?filter=approved" class="btn btn-outline <?php echo $filter === 'approved' ? 'active' : ''; ?>">
                Approved (<?php echo $counts['approved']; ?>)
            </a>
        </div>
    </div>
    
    <?php if ($users): ?>
        <div style="display: grid; gap: 1rem;">
            <?php foreach ($users as $user): ?>
            <div class="card" style="padding: 1.5rem; border-left: 4px solid <?php 
                echo $user['role'] === 'admin' ? 'var(--accent)' : 
                     ($user['is_accept'] == 0 ? 'var(--accent)' : 'var(--success)'); 
            ?>;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <h3 style="color: var(--accent); margin-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($user['name']); ?>
                            <span style="display: inline-flex; gap: 0.25rem; margin-left: 0.5rem;">
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span style="background: var(--gradient-accent); color: white; padding: 0.25rem 0.5rem; 
                                                border-radius: var(--radius); font-size: 0.8rem;">
                                        Admin
                                    </span>
                                <?php endif; ?>
                                <?php if ($user['is_accept'] == 0 && $user['role'] === 'user'): ?>
                                    <span style="background: var(--accent); color: white; padding: 0.25rem 0.5rem; 
                                                border-radius: var(--radius); font-size: 0.8rem;">
                                        Pending Approval
                                    </span>
                                <?php elseif ($user['is_accept'] == 1 && $user['role'] === 'user'): ?>
                                    <span style="background: var(--success); color: white; padding: 0.25rem 0.5rem; 
                                                border-radius: var(--radius); font-size: 0.8rem;">
                                        Approved
                                    </span>
                                <?php endif; ?>
                            </span>
                        </h3>
                        <p style="color: var(--light-text); margin-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </p>
                        <p style="color: var(--light-text); font-size: 0.9rem;">
                            Joined: <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                        </p>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <?php if ($user['is_accept'] == 0 && $user['role'] === 'user'): ?>
                            <!-- Approve Button -->
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="approve_user" class="btn btn-success" 
                                        onclick="return confirm('Are you sure you want to approve this user?')">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            
                            <!-- Reject Button -->
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="reject_user" class="btn btn-danger" 
                                        onclick="return confirm('Are you sure you want to reject and remove this user? This action cannot be undone.')">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <a href="user_detail.php?id=<?php echo $user['id']; ?>" class="btn btn-outline">
                            <i class="fas fa-chart-line"></i> View Progress
                        </a>
                    </div>
                </div>
                
                <?php
                // Get user stats (only for approved users or if we want to show for all)
                if ($user['is_accept'] == 1 || $user['role'] === 'admin') {
                    $user_stats_query = "SELECT 
                                       (SELECT COUNT(*) FROM devotional_reads WHERE user_id = ?) as devotions_read,
                                       (SELECT COUNT(*) FROM weights WHERE user_id = ?) as weight_entries,
                                       (SELECT COUNT(*) FROM steps WHERE user_id = ?) as steps_entries,
                                       (SELECT COUNT(*) FROM workout_logs WHERE user_id = ?) as workouts_completed";
                    $stmt = $db->prepare($user_stats_query);
                    $stmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
                    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $stats = ['devotions_read' => 0, 'weight_entries' => 0, 'steps_entries' => 0, 'workouts_completed' => 0];
                }
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
                
                <?php if ($user['is_accept'] == 0 && $user['role'] === 'user'): ?>
                <div style="margin-top: 1rem; padding: 1rem; background: rgba(26, 35, 126, 0.05); border-radius: var(--radius);">
                    <p style="color: var(--accent); margin: 0; font-size: 0.9rem;">
                        <i class="fas fa-clock" style="margin-right: 0.5rem;"></i>
                        This user is waiting for approval. They cannot access the system until approved.
                    </p>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: var(--light-text); padding: 3rem;">
            <?php if ($filter === 'pending'): ?>
                No pending users waiting for approval.
            <?php elseif ($filter === 'approved'): ?>
                No approved users found.
            <?php else: ?>
                No users found.
            <?php endif; ?>
        </p>
    <?php endif; ?>
</div>

<!-- Bulk Actions for Pending Users -->
<?php if ($filter === 'pending' && $users): ?>
<div class="card">
    <h3 class="card-title">Bulk Actions</h3>
    <p style="color: var(--light-text); margin-bottom: 1rem;">
        Quickly approve or reject multiple pending users at once.
    </p>
    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <button type="button" class="btn btn-success" onclick="bulkApproveAll()">
            <i class="fas fa-check-double"></i> Approve All Pending Users
        </button>
        
        <button type="button" class="btn btn-danger" onclick="bulkRejectAll()">
            <i class="fas fa-times-circle"></i> Reject All Pending Users
        </button>
    </div>
</div>

<script>
function bulkApproveAll() {
    if (confirm('Are you sure you want to approve ALL pending users?')) {
        // Create a form to submit all approve actions
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        <?php foreach ($users as $user): ?>
            <?php if ($user['is_accept'] == 0 && $user['role'] === 'user'): ?>
                const input<?php echo $user['id']; ?> = document.createElement('input');
                input<?php echo $user['id']; ?>.type = 'hidden';
                input<?php echo $user['id']; ?>.name = 'approve_user';
                input<?php echo $user['id']; ?>.value = '1';
                form.appendChild(input<?php echo $user['id']; ?>);
                
                const userId<?php echo $user['id']; ?> = document.createElement('input');
                userId<?php echo $user['id']; ?>.type = 'hidden';
                userId<?php echo $user['id']; ?>.name = 'user_id';
                userId<?php echo $user['id']; ?>.value = '<?php echo $user['id']; ?>';
                form.appendChild(userId<?php echo $user['id']; ?>);
            <?php endif; ?>
        <?php endforeach; ?>
        
        document.body.appendChild(form);
        form.submit();
    }
}

function bulkRejectAll() {
    if (confirm('Are you sure you want to reject ALL pending users? This action cannot be undone.')) {
        // Create a form to submit all reject actions
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        <?php foreach ($users as $user): ?>
            <?php if ($user['is_accept'] == 0 && $user['role'] === 'user'): ?>
                const input<?php echo $user['id']; ?> = document.createElement('input');
                input<?php echo $user['id']; ?>.type = 'hidden';
                input<?php echo $user['id']; ?>.name = 'reject_user';
                input<?php echo $user['id']; ?>.value = '1';
                form.appendChild(input<?php echo $user['id']; ?>);
                
                const userId<?php echo $user['id']; ?> = document.createElement('input');
                userId<?php echo $user['id']; ?>.type = 'hidden';
                userId<?php echo $user['id']; ?>.name = 'user_id';
                userId<?php echo $user['id']; ?>.value = '<?php echo $user['id']; ?>';
                form.appendChild(userId<?php echo $user['id']; ?>);
            <?php endif; ?>
        <?php endforeach; ?>
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
<?php endif; ?>

<style>
.btn.active {
    background: var(--gradient-accent);
    color: white;
    border-color: var(--accent);
}

.btn-success {
    background: #2e7d32;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius);
    cursor: pointer;
    transition: var(--transition);
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
}

.btn-success:hover {
    background: #2e7d32;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.btn-danger {
    background: #f44336;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius);
    cursor: pointer;
    transition: var(--transition);
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
}

.btn-danger:hover {
    background: #d32f2f;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(244, 67, 54, 0.3);
}

.message {
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    text-align: center;
    font-weight: 600;
    animation: slideIn 0.5s ease;
    border: 1px solid transparent;
}

.message.success {
    background: rgba(76, 175, 80, 0.1);
    color: #4caf50;
    border-color: rgba(76, 175, 80, 0.2);
}

.message.error {
    background: rgba(244, 67, 54, 0.1);
    color: #f44336;
    border-color: rgba(244, 67, 54, 0.2);
}

/* Ensure buttons are always visible */
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.card-header .btn-outline {
    opacity: 1;
    visibility: visible;
}

/* User action buttons container */
.user-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: wrap;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive design */
@media (max-width: 768px) {
    .card-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .user-actions {
        width: 100%;
        justify-content: flex-start;
        margin-top: 1rem;
    }
    
    .btn-success,
    .btn-danger {
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
    }
}
</style>

<?php require_once '../footer.php'; ?>