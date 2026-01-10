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

// Get weekly steps
$week_start = date('Y-m-d', strtotime('sunday this week'));
$week_steps_query = "SELECT SUM(steps_count) as total_steps FROM steps 
                    WHERE user_id = ? AND entry_date >= ?";
$stmt = $db->prepare($week_steps_query);
$stmt->execute([$user_id, $week_start]);
$week_steps = $stmt->fetch(PDO::FETCH_ASSOC)['total_steps'] ?? 0;
?>

<style>
    /* Native Mobile App Reset */
    * {
        -webkit-tap-highlight-color: transparent;
        -webkit-user-select: none;
        user-select: none;
        touch-action: manipulation;
    }
    
    input, textarea, button, select {
        -webkit-user-select: text;
        user-select: text;
    }
    
    html, body {
        overscroll-behavior-y: contain;
        -webkit-overflow-scrolling: touch;
        overflow-x: hidden;
        max-width: 100vw;
    }
    
    /* Dashboard Native Mobile Styles */
    .native-container {
        padding: 0;
        margin: 0;
        width: 100%;
        overflow-x: hidden;
    }
    
    .welcome-banner {
        background: var(--gradient-blue);
        color: white;
        padding: 2rem 1.5rem 1.5rem;
        margin: 0 0 1.5rem;
        border-radius: 0;
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
        background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
    }
    
    .welcome-banner h1 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        position: relative;
        line-height: 1.3;
    }
    
    .welcome-banner p {
        opacity: 0.9;
        margin: 0;
        position: relative;
        font-size: 0.95rem;
    }
    
    /* Native Stats Cards */
    .stats-grid {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 0 1rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        border: 1px solid var(--glass-border);
        border-radius: 16px;
        padding: 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        min-height: 88px;
    }
    
    .stat-card:active {
        transform: scale(0.98);
        background: rgba(var(--accent-rgb, 26, 35, 126), 0.05);
    }
    
    .stat-icon {
        width: 52px;
        height: 52px;
        min-width: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        background: var(--gradient-accent);
        color: white;
    }
    
    .stat-content {
        flex: 1;
    }
    
    .stat-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 0.25rem;
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        background: var(--gradient-blue);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        line-height: 1;
    }
    
    .stat-label {
        color: var(--light-text);
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.375rem 0.75rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        min-height: 28px;
    }
    
    .status-complete {
        background: rgba(76, 175, 80, 0.1);
        color: #4CAF50;
    }
    
    .status-pending {
        background: rgba(255, 152, 0, 0.1);
        color: #FF9800;
    }
    
    /* Native Progress Alert */
    .progress-alert {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.25rem;
        border-radius: 16px;
        margin: 0 1rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .progress-alert:active {
        opacity: 0.95;
    }
    
    .progress-alert-icon {
        width: 44px;
        height: 44px;
        min-width: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }
    
    .progress-alert-content {
        flex: 1;
    }
    
    .progress-alert h3 {
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    
    .progress-alert p {
        font-size: 0.875rem;
        opacity: 0.9;
        margin-bottom: 0.75rem;
    }
    
    /* Native Photo Grid */
    .photo-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
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
        font-size: 0.75rem;
        color: var(--light-text);
    }
    
    /* Native Progress Section */
    .progress-section {
        padding: 0 1rem 2rem;
    }
    
    .progress-title {
        font-size: 1.125rem;
        font-weight: 700;
        margin-bottom: 1rem;
        color: var(--text);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .progress-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }
    
    .progress-stat {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: 16px;
        padding: 1rem;
        text-align: center;
    }
    
    .progress-stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        background: var(--gradient-primary);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    
    .progress-stat-label {
        font-size: 0.75rem;
        color: var(--light-text);
        font-weight: 500;
    }
    
    /* Native Button Styles */
    .btn-native {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.875rem 1.25rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9375rem;
        border: none;
        text-decoration: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        min-height: 48px;
    }
    
    .btn-native:active {
        transform: scale(0.98);
    }
    
    .btn-primary {
        background: var(--gradient-accent);
        color: white;
    }
    
    .btn-outline {
        background: transparent;
        border: 1px solid var(--glass-border);
        color: var(--text);
    }
    
    /* Native Card */
    .native-card {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: 16px;
        padding: 1.25rem;
        margin: 0 1rem 1.5rem;
    }
    
    /* Responsive */
    @media (min-width: 768px) {
        .native-container {
            max-width: 420px;
            margin: 0 auto;
        }
        
        .welcome-banner {
            border-radius: 0 0 24px 24px;
        }
    }
    
    @media (max-width: 360px) {
        .progress-stats {
            grid-template-columns: 1fr;
        }
        
        .photo-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    /* Dark mode adjustments */
    @media (prefers-color-scheme: dark) {
        .stat-card,
        .progress-stat,
        .native-card {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.1);
        }
    }
    
    /* Loading skeleton animation */
    @keyframes skeleton-loading {
        0% { opacity: 0.5; }
        50% { opacity: 0.8; }
        100% { opacity: 0.5; }
    }
    
    .skeleton {
        animation: skeleton-loading 1.5s ease-in-out infinite;
        background: var(--glass-bg);
        border-radius: 8px;
    }
</style>

<script>
    // Prevent zoom gestures
    document.addEventListener('touchstart', function(event) {
        if (event.touches.length > 1) {
            event.preventDefault();
        }
    }, { passive: false });

    document.addEventListener('gesturestart', function(event) {
        event.preventDefault();
    });

    // Add native-like tap feedback
    document.addEventListener('touchstart', function() {}, {passive: true});
</script>

<div class="native-container">
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <h1>Honor God with your body and your habits</h1>
        <p>Welcome to your fitness journey with Christ</p>
    </div>

    <!-- Main Stats -->
    <div class="stats-grid">
        <!-- Devotion Card -->
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-bible"></i>
            </div>
            <div class="stat-content">
                <div class="stat-row">
                    <div class="stat-value"><?php echo $streak; ?></div>
                    <div class="status-badge <?php echo $devotion_completed ? 'status-complete' : 'status-pending'; ?>">
                        <i class="fas <?php echo $devotion_completed ? 'fa-check' : 'fa-clock'; ?>"></i>
                        <?php echo $devotion_completed ? 'Done' : 'Pending'; ?>
                    </div>
                </div>
                <div class="stat-label">Devotion Streak</div>
            </div>
        </div>

        <!-- Weight Card -->
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-weight-scale"></i>
            </div>
            <div class="stat-content">
                <div class="stat-row">
                    <div class="stat-value">
                        <?php echo $latest_weight ? $latest_weight['weight_kg'] . ' kg' : '--'; ?>
                    </div>
                    <div class="status-badge status-pending">
                        <i class="fas fa-ruler"></i>
                        Track
                    </div>
                </div>
                <div class="stat-label">Current Weight</div>
            </div>
        </div>

        <!-- Steps Card -->
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-walking"></i>
            </div>
            <div class="stat-content">
                <div class="stat-row">
                    <div class="stat-value">
                        <?php echo $today_steps ? number_format($today_steps['steps_count']) : '0'; ?>
                    </div>
                    <div class="status-badge <?php echo ($today_steps && $today_steps['steps_count'] > 0) ? 'status-complete' : 'status-pending'; ?>">
                        <i class="fas <?php echo ($today_steps && $today_steps['steps_count'] > 0) ? 'fa-check' : 'fa-shoe-prints'; ?>"></i>
                        <?php echo ($today_steps && $today_steps['steps_count'] > 0) ? 'Active' : 'Move'; ?>
                    </div>
                </div>
                <div class="stat-label">Today's Steps</div>
            </div>
        </div>
    </div>

    <!-- Progress Photos Friday -->
    <?php if ($is_friday): ?>
    <div class="native-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <div style="font-size: 1.125rem; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-camera"></i>
                Progress Photos Friday
            </div>
            <?php if (!$already_uploaded): ?>
                <span style="background: var(--gradient-accent); color: white; padding: 0.375rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                    <i class="fas fa-camera"></i> Today
                </span>
            <?php endif; ?>
        </div>
        
        <?php if (!$already_uploaded): ?>
            <div class="progress-alert" onclick="location.href='progress_photos.php'">
                <div class="progress-alert-icon">
                    <i class="fas fa-camera"></i>
                </div>
                <div class="progress-alert-content">
                    <h3>Progress Photo Friday!</h3>
                    <p>Upload your weekly progress photos to track your transformation</p>
                    <button class="btn-native btn-primary" style="margin-top: 0.5rem;">
                        <i class="fas fa-camera"></i> Upload Photos
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 1rem 0;">
                <i class="fas fa-check-circle" style="color: #4CAF50; font-size: 2.5rem; margin-bottom: 0.75rem;"></i>
                <h3 style="color: var(--text); margin-bottom: 0.25rem; font-size: 1rem;">Photos Uploaded!</h3>
                <p style="color: var(--light-text); font-size: 0.875rem; margin-bottom: 1.5rem;">Great job tracking progress!</p>
                
                <?php if ($recent_photos): ?>
                    <div style="margin-top: 1rem;">
                        <h4 style="color: var(--text); margin-bottom: 1rem; font-size: 0.9375rem; text-align: left;">Recent Progress</h4>
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
                        
                        <button class="btn-native btn-outline" onclick="location.href='progress_photos_history.php'" style="margin-top: 1rem; width: 100%;">
                            <i class="fas fa-history"></i> View All Photos
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Progress Section -->
    <div class="progress-section">
        <div class="progress-title">
            <i class="fas fa-chart-line"></i>
            Progress
        </div>
        
        <div class="progress-stats">
            <div class="progress-stat">
                <div class="progress-stat-value"><?php echo number_format($week_steps); ?></div>
                <div class="progress-stat-label">Weekly Steps</div>
            </div>
            
            <div class="progress-stat">
                <div class="progress-stat-value"><?php echo $workout_completed ? '1' : '0'; ?></div>
                <div class="progress-stat-label">Workouts</div>
            </div>
            
            <div class="progress-stat">
                <div class="progress-stat-value"><?php echo $devotion_completed ? '1' : '0'; ?></div>
                <div class="progress-stat-label">Devotions</div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>