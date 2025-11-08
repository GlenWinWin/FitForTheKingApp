<?php
$pageTitle = "User Progress Photos";
require_once '../header.php';
requireAdmin();

$user_id = $_GET['user_id'] ?? null;

if ($user_id) {
    // View specific user's progress photos
    $user_query = "SELECT name FROM users WHERE id = ?";
    $stmt = $db->prepare($user_query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $photos_query = "SELECT * FROM progress_photos WHERE user_id = ? ORDER BY photo_date DESC";
    $stmt = $db->prepare($photos_query);
    $stmt->execute([$user_id]);
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // List all users with progress photos
    $users_query = "SELECT u.id, u.name, u.email, 
                   COUNT(pp.id) as photo_count,
                   MAX(pp.photo_date) as latest_photo
                   FROM users u 
                   LEFT JOIN progress_photos pp ON u.id = pp.user_id 
                   WHERE u.role = 'user'
                   GROUP BY u.id 
                   HAVING photo_count > 0
                   ORDER BY latest_photo DESC";
    $stmt = $db->prepare($users_query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">
            <?php echo $user_id ? "Progress Photos: " . $user['name'] : "User Progress Photos"; ?>
        </h1>
        <div>
            <?php if ($user_id): ?>
                <a href="progress_photos.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> All Users
                </a>
            <?php endif; ?>
            <a href="users.php" class="btn btn-outline">
                <i class="fas fa-users"></i> Manage Users
            </a>
        </div>
    </div>
</div>

<?php if ($user_id): ?>
    <!-- Specific User's Progress Photos -->
    <?php if ($photos): ?>
        <div class="card">
            <h2 class="card-title">Progress Timeline (<?php echo count($photos); ?> entries)</h2>
            
            <div style="display: grid; gap: 2rem;">
                <?php foreach ($photos as $photo): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <?php echo date('F j, Y', strtotime($photo['photo_date'])); ?>
                            (<?php echo floor((time() - strtotime($photo['photo_date'])) / (60 * 60 * 24)); ?> days ago)
                        </h3>
                    </div>
                    
                    <div class="grid grid-3">
                        <div style="text-align: center;">
                            <h4 style="color: var(--accent); margin-bottom: 1rem;">Front View</h4>
                            <?php if ($photo['front_photo']): ?>
                                <div style="width: 100%; max-width: 250px; margin: 0 auto; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow);">
                                    <img src="<?php echo $photo['front_photo']; ?>" 
                                         alt="Front View" style="width: 100%; height: auto; display: block;">
                                </div>
                            <?php else: ?>
                                <div style="padding: 2rem; background: var(--glass-bg); border-radius: var(--radius);">
                                    <i class="fas fa-times" style="color: var(--light-text); font-size: 2rem;"></i>
                                    <p style="color: var(--light-text); margin: 0.5rem 0 0;">Not uploaded</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="text-align: center;">
                            <h4 style="color: var(--accent); margin-bottom: 1rem;">Side View</h4>
                            <?php if ($photo['side_photo']): ?>
                                <div style="width: 100%; max-width: 250px; margin: 0 auto; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow);">
                                    <img src="<?php echo $photo['side_photo']; ?>" 
                                         alt="Side View" style="width: 100%; height: auto; display: block;">
                                </div>
                            <?php else: ?>
                                <div style="padding: 2rem; background: var(--glass-bg); border-radius: var(--radius);">
                                    <i class="fas fa-times" style="color: var(--light-text); font-size: 2rem;"></i>
                                    <p style="color: var(--light-text); margin: 0.5rem 0 0;">Not uploaded</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="text-align: center;">
                            <h4 style="color: var(--accent); margin-bottom: 1rem;">Back View</h4>
                            <?php if ($photo['back_photo']): ?>
                                <div style="width: 100%; max-width: 250px; margin: 0 auto; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow);">
                                    <img src="<?php echo $photo['back_photo']; ?>" 
                                         alt="Back View" style="width: 100%; height: auto; display: block;">
                                </div>
                            <?php else: ?>
                                <div style="padding: 2rem; background: var(--glass-bg); border-radius: var(--radius);">
                                    <i class="fas fa-times" style="color: var(--light-text); font-size: 2rem;"></i>
                                    <p style="color: var(--light-text); margin: 0.5rem 0 0;">Not uploaded</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($photo['notes']): ?>
                    <div style="border-top: 1px solid var(--border); padding-top: 1rem; margin-top: 1rem;">
                        <h4 style="color: var(--text); margin-bottom: 0.5rem;">User Notes</h4>
                        <p style="color: var(--light-text); margin: 0; font-style: italic;">"<?php echo $photo['notes']; ?>"</p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card" style="text-align: center; padding: 3rem;">
            <i class="fas fa-camera" style="font-size: 3rem; color: var(--light-text); margin-bottom: 1rem;"></i>
            <h3 style="color: var(--light-text); margin-bottom: 1rem;">No Progress Photos</h3>
            <p style="color: var(--light-text); margin-bottom: 0;">
                <?php echo $user['name']; ?> hasn't uploaded any progress photos yet.
            </p>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- All Users List -->
    <div class="card">
        <h2 class="card-title">Users with Progress Photos</h2>
        
        <?php if ($users): ?>
            <div style="display: grid; gap: 1rem;">
                <?php foreach ($users as $user): ?>
                <div class="card" style="padding: 1.5rem;">
                    <div style="display: flex; justify-content: between; align-items: start;">
                        <div style="flex: 1;">
                            <h3 style="color: var(--accent); margin-bottom: 0.5rem;"><?php echo $user['name']; ?></h3>
                            <p style="color: var(--light-text); margin-bottom: 0.5rem;"><?php echo $user['email']; ?></p>
                            <div style="display: flex; gap: 1rem; font-size: 0.9rem; color: var(--light-text);">
                                <span><i class="fas fa-camera"></i> <?php echo $user['photo_count']; ?> photo sessions</span>
                                <span><i class="fas fa-calendar"></i> Latest: <?php echo date('M j, Y', strtotime($user['latest_photo'])); ?></span>
                            </div>
                        </div>
                        
                        <a href="progress_photos.php?user_id=<?php echo $user['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View Progress
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; color: var(--light-text);">
                <i class="fas fa-camera" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <p>No users have uploaded progress photos yet.</p>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once '../footer.php'; ?>