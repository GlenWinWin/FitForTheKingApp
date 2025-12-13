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
    /* Native App Base Styles - Clean Design with Cards */
    :root {
        --safe-area-top: env(safe-area-inset-top);
        --safe-area-bottom: env(safe-area-inset-bottom);
        --tap-target-min: 44px;
        --radius-large: 20px;
        --radius-medium: 16px;
        --radius-small: 12px;
        --ease-out: cubic-bezier(0.175, 0.885, 0.32, 1.1);
        --spring-bounce: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        --card-spacing: 20px;
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
        padding-bottom: 80px; /* Space for bottom nav from footer.php */
    }
    
    /* Enable text selection only in content areas */
    .devotion-content,
    .scripture-text,
    .reflection-content {
        -webkit-user-select: text;
        user-select: text;
    }
    
    /* Native-like scrolling */
    .devotion-scroll-container {
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
        max-width: 100%;
        margin: 0 auto;
    }
    
    /* Main Container - Clean, with proper spacing */
    .devotion-scroll-container {
        padding: calc(var(--safe-area-top) + 16px) 20px 32px;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        gap: var(--card-spacing);
    }
    
    /* Header Card - Clean Native Design */
    .header-card {
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-radius: var(--radius-large);
        padding: 24px;
        border: 1px solid var(--glass-border);
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.04);
        margin-bottom: 4px;
    }
    
    /* Day Indicator - Clean */
    .day-indicator {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(var(--accent-rgb), 0.12);
        color: var(--accent);
        font-size: 14px;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 100px;
        margin-bottom: 16px;
    }
    
    /* Main Title - Clean Typography */
    .devotion-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 20px;
        line-height: 1.25;
        letter-spacing: -0.3px;
        word-break: break-word;
    }
    
    /* Date & Info - Clean */
    .info-row {
        display: flex;
        align-items: center;
        gap: 20px;
        padding-top: 16px;
        border-top: 1px solid rgba(var(--glass-border-rgb), 0.5);
    }
    
    .info-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--light-text);
        font-size: 14px;
        font-weight: 500;
    }
    
    .info-item i {
        font-size: 13px;
        opacity: 0.8;
    }
    
    /* Cards - Consistent styling */
    .content-card {
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-radius: var(--radius-large);
        padding: 28px;
        border: 1px solid var(--glass-border);
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.04);
    }
    
    /* Scripture Card */
    .scripture-card {
        position: relative;
        overflow: hidden;
    }
    
    .scripture-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 5px;
        height: 100%;
        background: var(--gradient-accent);
        border-radius: var(--radius-large) 0 0 var(--radius-large);
    }
    
    .scripture-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 24px;
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
    
    .scripture-label {
        font-size: 18px;
        font-weight: 700;
        color: var(--text);
        letter-spacing: -0.2px;
    }
    
    .scripture-text {
        font-size: 20px;
        line-height: 1.6;
        color: var(--text);
        text-align: left;
        font-style: italic;
        margin-bottom: 20px;
        padding: 20px;
        background: rgba(var(--accent-rgb), 0.06);
        border-radius: var(--radius-medium);
        border-left: 3px solid var(--accent);
    }
    
    .scripture-reference {
        text-align: right;
        color: var(--accent);
        font-weight: 700;
        font-size: 16px;
        padding-right: 4px;
    }
    
    /* Content Card */
    .content-card {
        margin: 0;
    }
    
    .content-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 24px;
    }
    
    .content-icon {
        width: 44px;
        height: 44px;
        border-radius: var(--radius-medium);
        background: rgba(var(--accent-rgb), 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent);
        font-size: 18px;
        flex-shrink: 0;
    }
    
    .content-label {
        font-size: 18px;
        font-weight: 700;
        color: var(--text);
        letter-spacing: -0.2px;
    }
    
    .devotion-content {
        line-height: 1.7;
        font-size: 17px;
        color: var(--text);
    }
    
    .devotion-content p {
        margin-bottom: 20px;
        letter-spacing: -0.1px;
        text-align: left;
    }
    
    /* Reflection Card */
    .reflection-card {
        background: rgba(var(--accent-rgb), 0.08);
        border-color: rgba(var(--accent-rgb), 0.15);
    }
    
    .reflection-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 20px;
    }
    
    .reflection-icon {
        width: 44px;
        height: 44px;
        border-radius: var(--radius-medium);
        background: rgba(var(--accent-rgb), 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent);
        font-size: 18px;
        flex-shrink: 0;
    }
    
    .reflection-label {
        font-size: 18px;
        font-weight: 700;
        color: var(--accent);
        letter-spacing: -0.2px;
    }
    
    .reflection-content {
        font-size: 17px;
        line-height: 1.6;
        color: var(--text);
        margin: 0;
    }
    
    .reflection-content p {
        margin-bottom: 16px;
    }
    
    .reflection-content p:last-child {
        margin-bottom: 0;
    }
    
    /* Action Section */
    .action-section {
        text-align: center;
        margin-top: 8px;
    }
    
    /* Status Indicator */
    .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        padding: 16px 28px;
        border-radius: 100px;
        font-weight: 600;
        font-size: 16px;
        margin-bottom: 24px;
        min-height: var(--tap-target-min);
        background: rgba(var(--glass-border-rgb), 0.1);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
    }
    
    .status-pending {
        color: #FF9800;
        background: rgba(255, 152, 0, 0.1);
        border-color: rgba(255, 152, 0, 0.2);
    }
    
    .status-completed {
        color: #4CAF50;
        background: rgba(76, 175, 80, 0.1);
        border-color: rgba(76, 175, 80, 0.2);
    }
    
    /* Native Button */
    .btn-primary {
        min-height: 56px;
        width: 100%;
        max-width: 320px;
        padding: 0 24px;
        border-radius: var(--radius-medium);
        font-weight: 600;
        font-size: 17px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        transition: all 0.2s var(--spring-bounce);
        transform-origin: center;
        border: none;
        position: relative;
        overflow: hidden;
        background: var(--gradient-accent);
        color: white;
        box-shadow: 0 8px 32px rgba(var(--accent-rgb), 0.2);
    }
    
    .btn-primary:active {
        transform: scale(0.98);
        box-shadow: 0 4px 20px rgba(var(--accent-rgb), 0.15);
    }
    
    /* Completion Card */
    .completion-card {
        background: linear-gradient(135deg, rgba(76, 175, 80, 0.1) 0%, rgba(76, 175, 80, 0.05) 100%);
        border: 1px solid rgba(76, 175, 80, 0.2);
        text-align: center;
        padding: 32px 28px;
        border-radius: var(--radius-large);
    }
    
    .completion-icon {
        font-size: 56px;
        color: #4CAF50;
        margin-bottom: 24px;
    }
    
    .completion-title {
        font-size: 24px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 12px;
    }
    
    .completion-message {
        font-size: 16px;
        color: var(--light-text);
        margin-bottom: 28px;
        line-height: 1.6;
    }
    
    .btn-outline {
        min-height: var(--tap-target-min);
        padding: 16px 32px;
        border: 2px solid var(--glass-border);
        border-radius: var(--radius-medium);
        color: var(--text);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
        font-size: 16px;
        background: rgba(255, 255, 255, 0.05);
        transition: all 0.2s;
    }
    
    .btn-outline:active {
        background: rgba(var(--glass-border-rgb), 0.15);
        transform: scale(0.98);
    }
    
    /* Responsive Design */
    @media (max-width: 370px) {
        .devotion-scroll-container {
            padding: calc(var(--safe-area-top) + 12px) 16px 24px;
            gap: 16px;
        }
        
        .header-card,
        .content-card,
        .scripture-card,
        .reflection-card,
        .completion-card {
            padding: 20px;
            border-radius: var(--radius-medium);
        }
        
        .devotion-title {
            font-size: 24px;
        }
        
        .scripture-text {
            font-size: 18px;
            padding: 16px;
        }
        
        .devotion-content {
            font-size: 16px;
        }
        
        .day-indicator {
            font-size: 13px;
            padding: 6px 14px;
        }
        
        .scripture-icon,
        .content-icon,
        .reflection-icon {
            width: 40px;
            height: 40px;
            font-size: 16px;
        }
    }
    
    @media (min-width: 400px) and (max-width: 500px) {
        .devotion-scroll-container {
            padding-left: 20px;
            padding-right: 20px;
        }
    }
    
    /* Tablet Optimization */
    @media (min-width: 768px) {
        .devotion-scroll-container {
            max-width: 680px;
            margin: 0 auto;
            padding-left: 24px;
            padding-right: 24px;
            gap: 24px;
        }
        
        .devotion-title {
            font-size: 32px;
        }
        
        .scripture-text {
            font-size: 22px;
        }
        
        .devotion-content {
            font-size: 18px;
        }
        
        .btn-primary {
            max-width: 360px;
        }
    }
    
    /* Dark Mode */
    @media (prefers-color-scheme: dark) {
        .header-card,
        .content-card,
        .scripture-card {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        .scripture-text {
            background: rgba(var(--accent-rgb), 0.08);
            border-left-color: var(--accent);
        }
        
        .reflection-card {
            background: rgba(var(--accent-rgb), 0.12);
            border-color: rgba(var(--accent-rgb), 0.2);
        }
        
        .completion-card {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.15) 0%, rgba(76, 175, 80, 0.08) 100%);
            border-color: rgba(76, 175, 80, 0.25);
        }
        
        .btn-outline {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.15);
        }
    }
    
    /* Loading Animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .header-card,
    .scripture-card,
    .content-card,
    .reflection-card,
    .action-section {
        animation: fadeIn 0.5s var(--ease-out) forwards;
        opacity: 0;
    }
    
    .header-card { animation-delay: 0.1s; }
    .scripture-card { animation-delay: 0.2s; }
    .content-card { animation-delay: 0.3s; }
    .reflection-card { animation-delay: 0.4s; }
    .action-section { animation-delay: 0.5s; }
</style>

<!-- Main Content - Clean Design with Properly Spaced Cards -->
<div class="devotion-scroll-container">
    <!-- Header Card -->
    <div class="header-card">
        <div class="day-indicator">
            <i class="fas fa-calendar-alt"></i>
            Day <?php echo $day_offset; ?> of 365
        </div>
        
        <h1 class="devotion-title"><?php echo htmlspecialchars($devotion['title']); ?></h1>
        
        <div class="info-row">
            <div class="info-item">
                <i class="fas fa-calendar"></i>
                <span><?php echo date('F j, Y'); ?></span>
            </div>
            <div class="info-item">
                <i class="fas fa-clock"></i>
                <span><?php echo date('g:i A'); ?></span>
            </div>
        </div>
    </div>

    <!-- Scripture Card -->
    <div class="content-card scripture-card">
        <div class="scripture-header">
            <div class="scripture-icon">
                <i class="fas fa-bible"></i>
            </div>
            <div class="scripture-label">Today's Scripture</div>
        </div>
        
        <div class="scripture-text">
            <?php echo htmlspecialchars($devotion['verse_text']); ?>
        </div>
        
        <div class="scripture-reference">
            <?php echo htmlspecialchars($devotion['verse_reference']); ?>
        </div>
    </div>

    <!-- Content Card -->
    <div class="content-card">
        <div class="content-header">
            <div class="content-icon">
                <i class="fas fa-book-open"></i>
            </div>
            <div class="content-label">Devotional</div>
        </div>
        
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

    <!-- Reflection Card -->
    <div class="content-card reflection-card">
        <div class="reflection-header">
            <div class="reflection-icon">
                <i class="fas fa-lightbulb"></i>
            </div>
            <div class="reflection-label">Reflection</div>
        </div>
        
        <div class="reflection-content">
            <?php 
            $reflection = $devotion['reflection_question'];
            $reflection = html_entity_decode($reflection, ENT_QUOTES, 'UTF-8');
            $reflectionParagraphs = explode("\n\n", $reflection);
            foreach ($reflectionParagraphs as $paragraph) {
                if (trim($paragraph)) {
                    echo '<p>' . nl2br(htmlspecialchars(trim($paragraph), ENT_QUOTES, 'UTF-8')) . '</p>';
                }
            }
            ?>
        </div>
    </div>

    <!-- Completion Action -->
    <?php if (!$completed): ?>
        <div class="action-section">
            <div class="status-indicator status-pending">
                <i class="fas fa-clock"></i>
                <span>Ready to Complete</span>
            </div>
            
            <p style="color: var(--light-text); margin-bottom: 28px; font-size: 16px; line-height: 1.5;">
                Take a moment to reflect on today's message before marking it complete.
            </p>
            
            <form method="POST" style="margin: 0;">
                <button type="submit" name="mark_completed" class="btn-primary">
                    <i class="fas fa-check-circle"></i> Mark as Completed
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="completion-card">
            <div class="completion-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 class="completion-title">Devotion Complete!</h3>
            <p class="completion-message">
                Great job completing Day <?php echo $day_offset; ?>. 
                Come back tomorrow for Day <?php echo $day_offset + 1; ?> of your spiritual journey.
            </p>
            <a href="dashboard.php" class="btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
    // Native-like interactions
    document.addEventListener('DOMContentLoaded', function() {
        // Button feedback for devotion page buttons
        const buttons = document.querySelectorAll('.btn-primary, .btn-outline');
        
        buttons.forEach(btn => {
            btn.addEventListener('touchstart', function() {
                this.style.opacity = '0.9';
                this.style.transform = 'scale(0.99)';
            }, { passive: true });
            
            btn.addEventListener('touchend', function() {
                this.style.opacity = '1';
                this.style.transform = 'scale(1)';
            }, { passive: true });
            
            btn.addEventListener('touchcancel', function() {
                this.style.opacity = '1';
                this.style.transform = 'scale(1)';
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
        
        // Form submission feedback for THIS page
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
        
        // Add haptic to primary button
        const primaryButton = document.querySelector('.btn-primary');
        if (primaryButton) {
            primaryButton.addEventListener('touchstart', simulateHaptic, { passive: true });
        }
    });
</script>

<?php require_once 'footer.php'; ?>