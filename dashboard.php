<?php
date_default_timezone_set("Asia/Hong_Kong");

$pageTitle = "Dashboard";
require_once 'header.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get user stats
// Get devotion streak
$streak_query = "SELECT COUNT(*) as streak FROM devotional_reads 
                WHERE user_id = ? AND date_read >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ORDER BY date_read DESC";
$stmt = $db->prepare($streak_query);
$stmt->execute([$user_id]);
$streak = $stmt->fetch(PDO::FETCH_ASSOC)['streak'];

// Get latest weight
$weight_query = "SELECT weight_kg FROM weights WHERE user_id = ? ORDER BY entry_date DESC LIMIT 1";
$stmt = $db->prepare($weight_query);
$stmt->execute([$user_id]);
$latest_weight = $stmt->fetch(PDO::FETCH_ASSOC);

// Get today's steps
$steps_query = "SELECT steps_count FROM steps WHERE user_id = ? AND entry_date = CURDATE()";
$stmt = $db->prepare($steps_query);
$stmt->execute([$user_id]);
$today_steps = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if today's devotion is completed
$devotion_query = "SELECT dr.id FROM devotional_reads dr 
                  JOIN devotions d ON dr.devotion_id = d.id 
                  WHERE dr.user_id = ? AND dr.date_read = CURDATE()";
$stmt = $db->prepare($devotion_query);
$stmt->execute([$user_id]);
$devotion_completed = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if workout completed today
$workout_query = "SELECT id FROM workout_logs WHERE user_id = ? AND DATE(completed_at) = CURDATE()";
$stmt = $db->prepare($workout_query);
$stmt->execute([$user_id]);
$workout_completed = $stmt->fetch(PDO::FETCH_ASSOC);

// Progress Photos Friday Check
$is_friday = (date('N') == 5);
$today = date('Y-m-d');
$photo_check_query = "SELECT id FROM progress_photos WHERE user_id = ? AND photo_date = ?";
$stmt = $db->prepare($photo_check_query);
$stmt->execute([$user_id, $today]);
$already_uploaded = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent progress photos for preview
$recent_photos_query = "SELECT * FROM progress_photos 
                       WHERE user_id = ? 
                       ORDER BY photo_date DESC 
                       LIMIT 3";
$stmt = $db->prepare($recent_photos_query);
$stmt->execute([$user_id]);
$recent_photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get weekly stats for progress
$week_start = date('Y-m-d', strtotime('sunday this week'));
$week_steps_query = "SELECT SUM(steps_count) as total_steps FROM steps 
                    WHERE user_id = ? AND entry_date >= ?";
$stmt = $db->prepare($week_steps_query);
$stmt->execute([$user_id, $week_start]);
$week_steps = $stmt->fetch(PDO::FETCH_ASSOC)['total_steps'] ?? 0;

// Get workout count for the week
$week_workouts_query = "SELECT COUNT(*) as workout_count FROM workout_logs 
                       WHERE user_id = ? AND DATE(completed_at) >= ?";
$stmt = $db->prepare($week_workouts_query);
$stmt->execute([$user_id, $week_start]);
$week_workouts = $stmt->fetch(PDO::FETCH_ASSOC)['workout_count'] ?? 0;

// Get devotion count for the week
$week_devotions_query = "SELECT COUNT(*) as devotion_count FROM devotional_reads 
                        WHERE user_id = ? AND date_read >= ?";
$stmt = $db->prepare($week_devotions_query);
$stmt->execute([$user_id, $week_start]);
$week_devotions = $stmt->fetch(PDO::FETCH_ASSOC)['devotion_count'] ?? 0;
?>

<style>
    /* Dashboard Specific Styles */
    .welcome-banner {
        background: var(--gradient-blue);
        color: white;
        padding: 2rem;
        border-radius: var(--radius);
        margin-bottom: 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .welcome-banner::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
        background-size: cover;
    }
    
    .welcome-banner h1 {
        font-size: 1.8rem;
        margin-bottom: 0.5rem;
        position: relative;
    }
    
    .welcome-banner p {
        opacity: 0.9;
        margin: 0;
        position: relative;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius);
        padding: 1.5rem;
        text-align: center;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(26, 35, 126, 0.05), transparent);
        transition: left 0.7s;
    }
    
    .stat-card:hover::before {
        left: 100%;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        margin: 0 auto 1rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        background: var(--gradient-accent);
        color: white;
    }
    
    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        background: var(--gradient-blue);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    
    .stat-label {
        color: var(--light-text);
        font-size: 1rem;
        margin-bottom: 1rem;
    }
    
    .progress-section {
        margin-bottom: 2rem;
    }
    
    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: var(--text);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .section-title i {
        color: var(--accent);
    }
    
    /* Clean Quick Actions */
    .quick-actions {
        display: flex;
        gap: 1rem;
        overflow-x: auto;
        padding: 0.5rem 0;
        margin-bottom: 2rem;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    
    .quick-actions::-webkit-scrollbar {
        display: none;
    }
    
    .quick-action {
        flex: 0 0 auto;
        width: 120px;
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius);
        padding: 1.5rem 1rem;
        text-align: center;
        text-decoration: none;
        color: var(--text);
        transition: var(--transition);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
    }
    
    .quick-action:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow);
        color: var(--text);
    }
    
    .quick-action-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        background: var(--gradient-accent);
        color: white;
    }
    
    .quick-action-title {
        font-weight: 600;
        font-size: 0.9rem;
        line-height: 1.3;
    }
    
    .progress-alert {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        border-radius: var(--radius);
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .progress-alert-icon {
        font-size: 2rem;
        flex-shrink: 0;
    }
    
    .progress-alert-content {
        flex: 1;
    }
    
    .progress-alert h3 {
        margin-bottom: 0.5rem;
    }
    
    .photo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .photo-item {
        text-align: center;
    }
    
    .photo-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        overflow: hidden;
        margin: 0 auto 0.5rem;
        border: 3px solid var(--accent);
        background: var(--glass-bg);
    }
    
    .photo-circle img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .photo-date {
        font-size: 0.8rem;
        color: var(--light-text);
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        margin-top: 0.5rem;
    }
    
    .status-complete {
        background: rgba(76, 175, 80, 0.1);
        color: #4CAF50;
    }
    
    .status-pending {
        background: rgba(255, 152, 0, 0.1);
        color: #FF9800;
    }
    
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .quick-actions {
            gap: 0.75rem;
        }
        
        .quick-action {
            width: 110px;
            padding: 1.25rem 0.75rem;
        }
        
        .welcome-banner {
            padding: 1.5rem 1rem;
        }
        
        .welcome-banner h1 {
            font-size: 1.5rem;
        }
    }
    
    @media (max-width: 480px) {
        .quick-action {
            width: 100px;
            padding: 1rem 0.5rem;
        }
        
        .quick-action-icon {
            width: 45px;
            height: 45px;
            font-size: 1.1rem;
        }
        
        .quick-action-title {
            font-size: 0.85rem;
        }
    }
</style>

<!-- Welcome Banner -->
<div class="welcome-banner">
    <h1>Honor God with your body and your habits</h1>
    <p>Welcome to your fitness journey with Christ</p>
</div>

<!-- Main Stats Grid -->
<div class="stats-grid">
    <!-- Devotion Card -->
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-bible"></i>
        </div>
        <div class="stat-value"><?php echo $streak; ?></div>
        <div class="stat-label">Devotion Streak</div>
        <div class="status-badge <?php echo $devotion_completed ? 'status-complete' : 'status-pending'; ?>">
            <i class="fas <?php echo $devotion_completed ? 'fa-check' : 'fa-clock'; ?>"></i>
            <?php echo $devotion_completed ? 'Completed' : 'Pending'; ?>
        </div>
    </div>

    <!-- Weight Card -->
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-weight-scale"></i>
        </div>
        <div class="stat-value">
            <?php echo $latest_weight ? $latest_weight['weight_kg'] . ' kg' : '--'; ?>
        </div>
        <div class="stat-label">Current Weight</div>
        <div class="status-badge status-pending">
            <i class="fas fa-ruler"></i>
            Track Progress
        </div>
    </div>

    <!-- Steps Card -->
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-walking"></i>
        </div>
        <div class="stat-value">
            <?php echo $today_steps ? number_format($today_steps['steps_count']) : '0'; ?>
        </div>
        <div class="stat-label">Today's Steps</div>
        <div class="status-badge <?php echo ($today_steps && $today_steps['steps_count'] > 0) ? 'status-complete' : 'status-pending'; ?>">
            <i class="fas <?php echo ($today_steps && $today_steps['steps_count'] > 0) ? 'fa-check' : 'fa-shoe-prints'; ?>"></i>
            <?php echo ($today_steps && $today_steps['steps_count'] > 0) ? 'Active' : 'Get Moving'; ?>
        </div>
    </div>
</div>

<!-- Progress Photos Section - Only show on Friday -->
<?php if ($is_friday): ?>
<div class="progress-section">
    <div class="card">
        <div class="card-header">
            <h2 class="section-title" style="margin: 0;">
                <i class="fas fa-camera"></i>
                Progress Photos Friday
            </h2>
            <?php if (!$already_uploaded): ?>
                <span class="badge" style="background: var(--gradient-accent);">
                    <i class="fas fa-camera"></i> Photo Day!
                </span>
            <?php endif; ?>
        </div>
        
        <?php if (!$already_uploaded): ?>
            <div class="progress-alert">
                <div class="progress-alert-icon">
                    <i class="fas fa-camera"></i>
                </div>
                <div class="progress-alert-content">
                    <h3>It's Progress Photo Friday!</h3>
                    <p>Track your transformation by uploading your progress photos. Document your journey to see the amazing changes!</p>
                    <a href="progress_photos.php" class="btn btn-primary" style="margin-top: 0.5rem;">
                        <i class="fas fa-camera"></i> Upload Photos
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 1.5rem;">
                <i class="fas fa-check-circle" style="color: #4CAF50; font-size: 3rem; margin-bottom: 1rem;"></i>
                <h3 style="color: var(--text); margin-bottom: 0.5rem;">Photos Uploaded!</h3>
                <p style="color: var(--light-text);">Great job tracking your progress today!</p>
                
                <?php if ($recent_photos): ?>
                    <div style="margin-top: 1.5rem;">
                        <h4 style="color: var(--text); margin-bottom: 1rem;">Your Recent Progress</h4>
                        <div class="photo-grid">
                            <?php foreach ($recent_photos as $photo): ?>
                            <div class="photo-item">
                                <div class="photo-circle">
                                    <img src="<?php echo $photo['front_photo'] ?: 'https://via.placeholder.com/100/1a237e/ffffff?text=F'; ?>" 
                                         alt="Progress Photo">
                                </div>
                                <div class="photo-date">
                                    <?php echo date('M j', strtotime($photo['photo_date'])); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div style="text-align: center; margin-top: 1.5rem;">
                            <a href="progress_photos_history.php" class="btn btn-outline">
                                <i class="fas fa-history"></i> View All Progress Photos
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Weekly Progress -->
<div class="progress-section">
    <div class="card">
        <h2 class="section-title" style="margin: 0 0 1.5rem 0;">
            <i class="fas fa-chart-line"></i>
            Weekly Progress
        </h2>
        
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--gradient-primary);">
                    <i class="fas fa-walking"></i>
                </div>
                <div class="stat-value"><?php echo number_format($week_steps); ?></div>
                <div class="stat-label">Weekly Steps</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--gradient-primary);">
                    <i class="fas fa-dumbbell"></i>
                </div>
                <div class="stat-value"><?php echo $week_workouts; ?></div>
                <div class="stat-label">Workouts This Week</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--gradient-primary);">
                    <i class="fas fa-bible"></i>
                </div>
                <div class="stat-value"><?php echo $week_devotions; ?></div>
                <div class="stat-label">Devotions This Week</div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>