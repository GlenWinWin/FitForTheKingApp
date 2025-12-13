<?php
date_default_timezone_set("Asia/Hong_Kong");

$pageTitle = "Today's Devotion";
require_once 'header.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get user's creation date to calculate day offset
$user_query = "SELECT created_at FROM users WHERE id = ?";
$stmt = $db->prepare($user_query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate days since user creation - MIDNIGHT-BASED CALENDAR DAYS
$created_at = new DateTime($user['created_at']);
$today = new DateTime('today');

// Reset both dates to midnight for simple day counting
$created_midnight = new DateTime($user['created_at']);
$created_midnight->setTime(0, 0, 0);
$today_midnight = new DateTime();
$today_midnight->setTime(0, 0, 0);

$interval = $created_midnight->diff($today_midnight);
$day_offset = $interval->days + 1; // Start from day 1

// If beyond 365 days, loop back to day 1 (or show day 365 as you prefer)
if ($day_offset > 365) {
    $day_offset = 365; // or $day_offset = (($day_offset - 1) % 365) + 1; to loop continuously
}
// Get today's devotion
$devotion_query = "SELECT * FROM devotions WHERE devotion_day = ?";
$stmt = $db->prepare($devotion_query);
$stmt->execute([$day_offset]);
$devotion = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle case where devotion for calculated day doesn't exist
if (!$devotion) {
    // If no devotion found for calculated day, find the closest available one
    $max_day_query = "SELECT MAX(devotion_day) as max_day FROM devotions";
    $stmt = $db->prepare($max_day_query);
    $stmt->execute();
    $max_day = $stmt->fetch(PDO::FETCH_ASSOC)['max_day'];
    
    if ($day_offset > $max_day) {
        $day_offset = $max_day;
    }
    
    // Try again with the adjusted day offset
    $stmt = $db->prepare($devotion_query);
    $stmt->execute([$day_offset]);
    $devotion = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Check if already completed today - IMPORTANT: Check by date, not devotion_id
$completion_query = "SELECT id FROM devotional_reads WHERE user_id = ? AND date_read = CURDATE()";
$stmt = $db->prepare($completion_query);
$stmt->execute([$user_id]);
$completed = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_POST && isset($_POST['mark_completed'])) {
    if (!$completed && $devotion) {
        $insert_query = "INSERT INTO devotional_reads (user_id, devotion_id, date_read) VALUES (?, ?, CURDATE())";
        $stmt = $db->prepare($insert_query);
        $stmt->execute([$user_id, $devotion['id']]);
        $completed = true;
        echo "<script>window.location.href = 'devotion_today.php';</script>";
        exit();
    }
}

// If no devotion found for today's offset, show a message
if (!$devotion) {
    echo "<div class='alert alert-info'>No devotion found for today. Please check back tomorrow.</div>";
    require_once 'footer.php';
    exit();
}
?>

<style>
    /* Native App Base Styles */
    :root {
        --safe-area-top: env(safe-area-inset-top);
        --safe-area-bottom: env(safe-area-inset-bottom);
        --tap-target-min: 44px;
        --radius-large: 16px;
        --radius-medium: 12px;
        --radius-small: 8px;
        --elevation-1: 0 1px 3px rgba(0,0,0,0.12);
        --elevation-2: 0 4px 12px rgba(0,0,0,0.08);
        --ease-out: cubic-bezier(0.175, 0.885, 0.32, 1.1);
        --spring-bounce: cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    
    /* Disable zoom to mimic native app */
    body {
        touch-action: pan-y;
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        user-select: none;
        max-width: 100vw;
        overflow-x: hidden;
        background: var(--bg);
    }
    
    /* Enable text selection only in content areas */
    .devotion-content,
    .scripture-text {
        -webkit-user-select: text;
        user-select: text;
    }
    
    /* Native-like scrolling */
    .devotion-container {
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
    }
    
    /* Main Container */
    .devotion-container {
        max-width: 100%;
        margin: 0 auto;
        padding: 0 20px calc(var(--safe-area-bottom) + 80px);
        min-height: 100vh;
        position: relative;
    }
    
    /* Minimal Header - Clean Native Design */
    .devotion-header {
        padding: calc(var(--safe-area-top) + 16px) 0 20px;
        margin-bottom: 8px;
        position: relative;
    }
    
    /* Day Badge - Clean Design */
    .day-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(var(--accent-rgb), 0.1);
        color: var(--accent);
        font-size: 14px;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 20px;
        margin-bottom: 16px;
        border: 1px solid rgba(var(--accent-rgb), 0.2);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    
    .day-badge i {
        font-size: 12px;
    }
    
    /* Main Title - Clean Typography */
    .devotion-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 16px;
        line-height: 1.25;
        letter-spacing: -0.3px;
        word-break: break-word;
    }
    
    /* Date & Info Row - Clean Horizontal Layout */
    .info-row {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 4px;
        flex-wrap: wrap;
    }
    
    .info-item {
        display: flex;
        align-items: center;
        gap: 6px;
        color: var(--light-text);
        font-size: 14px;
        font-weight: 500;
    }
    
    .info-item i {
        font-size: 13px;
        opacity: 0.8;
    }
    
    /* Main Card - Native Style */
    .devotion-card {
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-radius: var(--radius-large);
        padding: 0;
        margin-bottom: 24px;
        border: 1px solid var(--glass-border);
        box-shadow: var(--elevation-2);
        overflow: hidden;
        position: relative;
    }
    
    /* Scripture Section */
    .scripture-section {
        padding: 24px;
        border-bottom: 1px solid var(--glass-border);
    }
    
    .scripture-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }
    
    .scripture-icon {
        width: 44px;
        height: 44px;
        border-radius: var(--radius-medium);
        background: var(--gradient-accent);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
        flex-shrink: 0;
    }
    
    .scripture-title {
        font-size: 18px;
        font-weight: 600;
        color: var(--text);
        letter-spacing: -0.1px;
    }
    
    .scripture-text {
        font-size: 18px;
        line-height: 1.6;
        color: var(--text);
        text-align: center;
        font-style: italic;
        margin-bottom: 16px;
        padding: 0 12px;
        font-weight: 500;
    }
    
    .scripture-reference {
        text-align: center;
        color: var(--accent);
        font-weight: 600;
        font-size: 15px;
        letter-spacing: 0.2px;
    }
    
    /* Content Section */
    .devotion-content-section {
        padding: 24px;
        border-bottom: 1px solid var(--glass-border);
    }
    
    .devotion-content {
        line-height: 1.7;
        font-size: 17px;
        color: var(--text);
    }
    
    .devotion-content p {
        margin-bottom: 20px;
        letter-spacing: -0.1px;
    }
    
    /* Reflection Section */
    .reflection-section {
        padding: 24px;
        background: rgba(var(--accent-rgb), 0.05);
        border-left: 0;
        border-top: 1px solid var(--glass-border);
        border-bottom: 1px solid var(--glass-border);
        margin: 0;
    }
    
    .reflection-title {
        font-weight: 600;
        color: var(--accent);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 16px;
    }
    
    /* Action Button */
    .action-section {
        padding: 32px 24px;
        text-align: center;
        background: rgba(var(--glass-border-rgb), 0.05);
    }
    
    /* Native Button */
    .btn-primary {
        min-height: var(--tap-target-min);
        padding: 16px 32px;
        border-radius: var(--radius-medium);
        font-weight: 600;
        font-size: 17px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.2s var(--spring-bounce);
        transform-origin: center;
        border: none;
        position: relative;
        overflow: hidden;
        min-width: 200px;
        background: var(--gradient-accent);
        color: white;
        box-shadow: 0 4px 20px rgba(var(--accent-rgb), 0.3);
    }
    
    .btn-primary:active {
        transform: scale(0.97);
        box-shadow: 0 2px 10px rgba(var(--accent-rgb), 0.2);
    }
    
    /* Completion State */
    .completion-section {
        padding: 32px 24px;
        text-align: center;
        background: linear-gradient(135deg, rgba(76, 175, 80, 0.1) 0%, rgba(76, 175, 80, 0.05) 100%);
        border-radius: var(--radius-large);
        border: 1px solid rgba(76, 175, 80, 0.2);
        margin: 24px;
    }
    
    /* Completion Badge */
    .completion-badge {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 14px 24px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 16px;
        margin-bottom: 24px;
        min-height: var(--tap-target-min);
    }
    
    .badge-completed {
        background: rgba(76, 175, 80, 0.15);
        color: #4CAF50;
        border: 1.5px solid rgba(76, 175, 80, 0.3);
    }
    
    .badge-pending {
        background: rgba(255, 152, 0, 0.15);
        color: #FF9800;
        border: 1.5px solid rgba(255, 152, 0, 0.3);
    }
    
    .nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        padding: 8px;
        min-height: var(--tap-target-min);
        min-width: var(--tap-target-min);
        justify-content: center;
        border-radius: var(--radius-medium);
        transition: all 0.2s;
        text-decoration: none;
    }
    
    .nav-item:active {
        background: rgba(var(--accent-rgb), 0.1);
        transform: scale(0.95);
    }
    
    .nav-icon {
        font-size: 20px;
        color: var(--light-text);
        transition: all 0.2s;
    }
    
    .nav-label {
        font-size: 11px;
        color: var(--light-text);
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .nav-item.active {
        background: rgba(var(--accent-rgb), 0.1);
    }
    
    .nav-item.active .nav-icon {
        color: var(--accent);
        transform: translateY(-1px);
    }
    
    .nav-item.active .nav-label {
        color: var(--accent);
        font-weight: 600;
    }
    
    /* Responsive Design */
    @media (max-width: 370px) {
        .devotion-title {
            font-size: 24px;
        }
        
        .scripture-text {
            font-size: 17px;
        }
        
        .devotion-content {
            font-size: 16px;
        }
        
        .devotion-header {
            padding: calc(var(--safe-area-top) + 12px) 0 16px;
        }
        
        .day-badge {
            font-size: 13px;
            padding: 6px 14px;
        }
    }
    
    @media (min-width: 400px) and (max-width: 500px) {
        .devotion-container {
            padding-left: 24px;
            padding-right: 24px;
        }
    }
    
    /* Tablet Optimization */
    @media (min-width: 768px) {
        .devotion-container {
            max-width: 768px;
            margin: 0 auto;
            padding-left: 24px;
            padding-right: 24px;
        }
        
        .devotion-card {
            border-radius: 20px;
        }
    }
    
    /* Dark Mode */
    @media (prefers-color-scheme: dark) {
        .devotion-card {
            background: var(--glass-bg);
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        .day-badge {
            background: rgba(var(--accent-rgb), 0.15);
            border-color: rgba(var(--accent-rgb), 0.3);
        }
    }
    
    /* Loading Animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .devotion-header,
    .devotion-card {
        animation: fadeIn 0.4s var(--ease-out);
    }
</style>

<!-- Main Content -->
<div class="devotion-container">
    <!-- Clean Header -->
    <div class="devotion-header">
        <div class="day-badge">
            <i class="fas fa-calendar-alt"></i>
            Day <?php echo $day_offset; ?> • <?php echo date('M j'); ?>
        </div>
        
        <h1 class="devotion-title"><?php echo htmlspecialchars($devotion['title']); ?></h1>
        
        <div class="info-row">
            <div class="info-item">
                <i class="fas fa-book"></i>
                <span>Daily Devotion</span>
            </div>
            <div class="info-item">
                <i class="fas fa-clock"></i>
                <span><?php echo date('g:i A'); ?></span>
            </div>
        </div>
    </div>

    <!-- Devotion Card -->
    <div class="devotion-card">
        <!-- Scripture -->
        <div class="scripture-section">
            <div class="scripture-header">
                <div class="scripture-icon">
                    <i class="fas fa-bible"></i>
                </div>
                <h2 class="scripture-title">Today's Scripture</h2>
            </div>
            
            <div class="scripture-text">
                "<?php echo htmlspecialchars($devotion['verse_text']); ?>"
            </div>
            
            <div class="scripture-reference">
                <?php echo htmlspecialchars($devotion['verse_reference']); ?>
            </div>
        </div>

        <!-- Content -->
        <div class="devotion-content-section">
            <div class="devotion-content">
                <?php 
                $content = $devotion['devotional_text'];
                $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
                $paragraphs = explode("\n\n", $content);
                foreach ($paragraphs as $paragraph) {
                    if (trim($paragraph)) {
                        echo '<p>' . nl2br(htmlspecialchars(trim($paragraph), ENT_QUOTES, 'UTF-8')) . '</p>';
                    }
                }
                ?>
            </div>
        </div>

        <!-- Reflection -->
        <div class="reflection-section">
            <div class="reflection-title">
                <i class="fas fa-lightbulb"></i>
                Reflection
            </div>
            <p><?php echo htmlspecialchars($devotion['reflection_question']); ?></p>
        </div>

        <!-- Completion Action -->
        <?php if (!$completed): ?>
            <div class="action-section">
                <div class="completion-badge badge-pending">
                    <i class="fas fa-clock"></i>
                    Ready to Complete
                </div>
                <p style="color: var(--light-text); margin-bottom: 24px; font-size: 15px;">
                    Take a moment to reflect before marking complete
                </p>
                <form method="POST" style="margin: 0;">
                    <button type="submit" name="mark_completed" class="btn btn-primary">
                        <i class="fas fa-check-circle"></i> Mark as Completed
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="completion-section">
                <i class="fas fa-check-circle" style="color: #4CAF50; font-size: 48px; margin-bottom: 16px;"></i>
                <h3 style="color: var(--text); margin-bottom: 8px; font-size: 20px; font-weight: 700;">Devotion Complete!</h3>
                <p style="color: var(--light-text); margin-bottom: 24px; font-size: 15px;">
                    Great job on Day <?php echo $day_offset; ?>. Come back tomorrow for Day <?php echo $day_offset + 1; ?>.
                </p>
                <a href="dashboard.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Native-like interactions
    document.addEventListener('DOMContentLoaded', function() {
        // Button feedback
        const buttons = document.querySelectorAll('.btn, .nav-item');
        
        buttons.forEach(btn => {
            btn.addEventListener('touchstart', function() {
                this.style.opacity = '0.8';
            }, { passive: true });
            
            btn.addEventListener('touchend', function() {
                this.style.opacity = '1';
            }, { passive: true });
            
            btn.addEventListener('touchcancel', function() {
                this.style.opacity = '1';
            }, { passive: true });
        });
        
        // Prevent double-tap zoom
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(event) {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, { passive: false });
        
        // Form submission feedback
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const button = this.querySelector('button[type="submit"]');
                if (button) {
                    const originalText = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                    button.disabled = true;
                    
                    // Restore button after 2 seconds if still disabled
                    setTimeout(() => {
                        if (button.disabled) {
                            button.innerHTML = originalText;
                            button.disabled = false;
                        }
                    }, 2000);
                }
            });
        }
        
        // Add haptic feedback simulation
        function simulateHaptic() {
            if (navigator.vibrate) {
                navigator.vibrate(10);
            }
        }
        
        // Add haptic to primary actions
        const primaryActions = document.querySelectorAll('.btn-primary, .nav-item.active');
        primaryActions.forEach(action => {
            action.addEventListener('touchstart', simulateHaptic, { passive: true });
        });
    });
</script>

<?php require_once 'footer.php'; ?>