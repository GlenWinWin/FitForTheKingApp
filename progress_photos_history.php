<?php
$pageTitle = "Progress Photos History";
require_once 'header.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Check for success message
$success_message = $_GET['message'] ?? '';

// Get all progress photos
$photos_query = "SELECT * FROM progress_photos 
                WHERE user_id = ? 
                ORDER BY photo_date DESC";
$stmt = $db->prepare($photos_query);
$stmt->execute([$user_id]);
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Premium Background -->
<div class="premium-bg"></div>
<div class="particles-container" id="particles-container"></div>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Progress Photos History</h1>
        <a href="dashboard.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>    
    </div>
    <p style="color: var(--light-text);">
        Track your transformation journey through weekly progress photos.
    </p>
</div>

<?php if ($success_message): ?>
<div class="card">
    <div class="message success">
        <?php echo htmlspecialchars($success_message); ?>
    </div>
</div>
<?php endif; ?>

<?php if ($photos): ?>
    <div style="display: grid; gap: 2rem;">
        <?php foreach ($photos as $photo): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <?php echo date('F j, Y', strtotime($photo['photo_date'])); ?>
                    <?php if ($photo['photo_date'] == date('Y-m-d')): ?>
                        <span class="badge">
                            Today
                        </span>
                    <?php endif; ?>
                </h2>
                <span style="color: var(--light-text);">
                    <?php echo floor((time() - strtotime($photo['photo_date'])) / (60 * 60 * 24)); ?> days ago
                </span>
            </div>
            
            <div class="grid grid-3">
                <div style="text-align: center;">
                    <h4 style="color: var(--accent); margin-bottom: 1rem;">Front View</h4>
                    <div style="width: 100%; max-width: 200px; margin: 0 auto; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow);">
                        <img src="<?php echo $photo['front_photo'] ?: 'https://via.placeholder.com/200x250/1a237e/ffffff?text=Front+View'; ?>" 
                             alt="Front View" style="width: 100%; height: auto; display: block;">
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <h4 style="color: var(--accent); margin-bottom: 1rem;">Side View</h4>
                    <div style="width: 100%; max-width: 200px; margin: 0 auto; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow);">
                        <img src="<?php echo $photo['side_photo'] ?: 'https://via.placeholder.com/200x250/1a237e/ffffff?text=Side+View'; ?>" 
                             alt="Side View" style="width: 100%; height: auto; display: block;">
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <h4 style="color: var(--accent); margin-bottom: 1rem;">Back View</h4>
                    <div style="width: 100%; max-width: 200px; margin: 0 auto; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow);">
                        <img src="<?php echo $photo['back_photo'] ?: 'https://via.placeholder.com/200x250/1a237e/ffffff?text=Back+View'; ?>" 
                             alt="Back View" style="width: 100%; height: auto; display: block;">
                    </div>
                </div>
            </div>
            
            <?php if ($photo['notes']): ?>
            <div style="border-top: 1px solid var(--border); padding-top: 1rem; margin-top: 1rem;">
                <h4 style="color: var(--text); margin-bottom: 0.5rem;">Notes</h4>
                <p style="color: var(--light-text); margin: 0; font-style: italic;">"<?php echo nl2br($photo['notes']); ?>"</p>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card" style="text-align: center; padding: 3rem;">
        <i class="fas fa-camera" style="font-size: 3rem; color: var(--light-text); margin-bottom: 1rem;"></i>
        <h3 style="color: var(--light-text); margin-bottom: 1rem;">No Progress Photos Yet</h3>
        <p style="color: var(--light-text); margin-bottom: 2rem;">
            Start tracking your transformation by uploading your first progress photos on Friday!
        </p>
        <a href="dashboard.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>