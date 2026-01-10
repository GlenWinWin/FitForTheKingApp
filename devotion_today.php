<?php
date_default_timezone_set("Asia/Hong_Kong");

$pageTitle = "This Week's Devotion";
require_once 'header.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// DEBUG: Check if POST is working
error_log("POST data: " . print_r($_POST, true));

// Get user's creation date to calculate week offset
$user_query = "SELECT created_at FROM users WHERE id = ?";
$stmt = $db->prepare($user_query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate the week starting from user creation (Sunday-based)
$created_at = new DateTime($user['created_at']);
$today = new DateTime('today');

// Get the Sunday of the creation week
$creation_sunday = clone $created_at;
// If creation day is not Sunday, get the previous Sunday
if ($creation_sunday->format('w') != 0) { // 0 = Sunday
    $creation_sunday->modify('last sunday');
}
$creation_sunday->setTime(0, 0, 0);

// Get the current week's Sunday (start of the week)
$current_sunday = clone $today;
// If today is not Sunday, get the most recent Sunday
if ($current_sunday->format('w') != 0) { // 0 = Sunday
    $current_sunday->modify('last sunday');
}
$current_sunday->setTime(0, 0, 0);
$current_sunday_str = $current_sunday->format('Y-m-d');

// Calculate week offset based on Sundays
$interval = $creation_sunday->diff($current_sunday);
$week_offset = floor($interval->days / 7) + 1; // Start from week 1

// Use week number as devotion day
$devotion_day = $week_offset;

// If beyond 52 weeks (1 year), loop back to week 1 (or show week 52)
if ($week_offset > 52) {
    $week_offset = 52; // or $week_offset = (($week_offset - 1) % 52) + 1; to loop continuously
    $devotion_day = $week_offset;
}

// Get this week's devotion using devotion_day (which now represents week number)
$devotion_query = "SELECT * FROM devotions WHERE devotion_day = ?";
$stmt = $db->prepare($devotion_query);
$stmt->execute([$devotion_day]);
$devotion = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle case where devotion for calculated week doesn't exist
if (!$devotion) {
    // If no devotion found, find the closest available one
    $max_day_query = "SELECT MAX(devotion_day) as max_day FROM devotions";
    $stmt = $db->prepare($max_day_query);
    $stmt->execute();
    $max_day = $stmt->fetch(PDO::FETCH_ASSOC)['max_day'];
    
    if ($devotion_day > $max_day) {
        $devotion_day = $max_day;
    }
    
    // Try again with the adjusted devotion day
    $stmt = $db->prepare($devotion_query);
    $stmt->execute([$devotion_day]);
    $devotion = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ========== FIXED COMPLETION CHECK ==========
// Check if already completed THIS SPECIFIC DEVOTION for THIS WEEK
$completion_query = "SELECT dr.id 
                     FROM devotional_reads dr 
                     WHERE dr.user_id = ? 
                     AND dr.devotion_id = ?
                     AND DATE(dr.date_read) >= ? 
                     AND DATE(dr.date_read) <= ?";
$stmt = $db->prepare($completion_query);

// Calculate date range for this week (Sunday to Saturday)
$week_start_date = $current_sunday->format('Y-m-d');
$week_end_date = clone $current_sunday;
$week_end_date->modify('+6 days');
$week_end_date_str = $week_end_date->format('Y-m-d');

// DEBUG: Log the values being checked
error_log("User ID: $user_id");
error_log("Devotion ID: " . ($devotion ? $devotion['id'] : 'NULL'));
error_log("Week start: $week_start_date");
error_log("Week end: $week_end_date_str");

// Execute the query with the correct parameters
if ($devotion) {
    $stmt->execute([$user_id, $devotion['id'], $week_start_date, $week_end_date_str]);
    $completed = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // DEBUG: Log completion check result
    error_log("Completion check result: " . print_r($completed, true));
} else {
    $completed = false;
}
// ========== END FIX ==========

// ========== FIXED FORM SUBMISSION HANDLING ==========
// Handle form submission - MOVE THIS BEFORE ANY OUTPUT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_completed'])) {
    error_log("Form submitted!");
    
    if (!$completed && $devotion) {
        error_log("Attempting to save devotion read...");
        
        $insert_query = "INSERT INTO devotional_reads (user_id, devotion_id, date_read) VALUES (?, ?, CURDATE())";
        $stmt = $db->prepare($insert_query);
        
        // DEBUG: Check the insert query
        error_log("Insert query: $insert_query");
        error_log("Values: user_id=$user_id, devotion_id=" . $devotion['id']);
        
        try {
            $result = $stmt->execute([$user_id, $devotion['id']]);
            $insert_id = $db->lastInsertId();
            
            error_log("Insert result: " . ($result ? "Success" : "Failed"));
            error_log("Last insert ID: $insert_id");
            
            if ($result) {
                $completed = true;
                // Force page refresh to show updated status
                header("Location: devotion_today.php");
                exit();
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
        }
    } else {
        error_log("Cannot save: Already completed or no devotion found");
        error_log("Completed: " . ($completed ? "Yes" : "No"));
        error_log("Devotion: " . ($devotion ? "Exists" : "NULL"));
    }
}
// ========== END FORM SUBMISSION FIX ==========

// If no devotion found for this week, show a message
if (!$devotion) {
    echo "<div class='alert alert-info'>No devotion found for this week. Please check back next week.</div>";
    require_once 'footer.php';
    exit();
}

// Get the date range for this week (Sunday to Saturday)
$week_start = clone $current_sunday;
$week_end = clone $current_sunday;
$week_end->modify('+6 days'); // Sunday + 6 days = Saturday
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
    
    /* Week Indicator - Clean */
    .week-indicator {
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
    
    /* Week Range Display */
    .week-range {
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--light-text);
        font-size: 14px;
        margin-bottom: 16px;
        padding: 8px 0;
    }
    
    .week-range i {
        opacity: 0.8;
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
        font-size: 22px;
        line-height: 1.6;
        color: var(--text);
        text-align: center;
        font-style: italic;
        margin-bottom: 20px;
        padding: 28px;
        background: rgba(var(--accent-rgb), 0.06);
        border-radius: var(--radius-medium);
        border: 2px solid rgba(var(--accent-rgb), 0.1);
        font-weight: 500;
    }
    
    .scripture-reference {
        text-align: center;
        color: var(--accent);
        font-weight: 700;
        font-size: 18px;
        padding-top: 10px;
        border-top: 1px solid rgba(var(--accent-rgb), 0.2);
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
    
    /* Weekly Focus Section */
    .weekly-focus {
        background: rgba(var(--accent-rgb), 0.05);
        border-left: 4px solid var(--accent);
        padding: 20px;
        border-radius: var(--radius-medium);
        margin: 24px 0;
    }
    
    .focus-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--accent);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .focus-text {
        font-size: 18px;
        font-weight: 500;
        color: var(--text);
        line-height: 1.5;
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
            font-size: 20px;
            padding: 20px;
        }
        
        .devotion-content {
            font-size: 16px;
        }
        
        .week-indicator {
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
            font-size: 24px;
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
            border-color: rgba(var(--accent-rgb), 0.15);
        }
        
        .reflection-card {
            background: rgba(var(--accent-rgb), 0.12);
            border-color: rgba(var(--accent-rgb), 0.2);
        }
        
        .weekly-focus {
            background: rgba(var(--accent-rgb), 0.08);
            border-left-color: var(--accent);
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
        <div class="week-indicator">
            <i class="fas fa-calendar-week"></i>
            Week <?php echo $week_offset; ?> of 52
        </div>
        
        <div class="week-range">
            <i class="fas fa-calendar-alt"></i>
            <span><?php echo $week_start->format('F j') . ' - ' . $week_end->format('F j, Y'); ?></span>
        </div>
        
        <h1 class="devotion-title"><?php echo htmlspecialchars($devotion['title']); ?></h1>
        
        <div class="info-row">
            <div class="info-item">
                <i class="fas fa-calendar"></i>
                <span>Updated Weekly on Sunday</span>
            </div>
            <div class="info-item">
                <i class="fas fa-clock"></i>
                <span><?php echo date('g:i A'); ?></span>
            </div>
        </div>
    </div>

    <!-- Scripture Card - Larger for Weekly Meditation -->
    <div class="content-card scripture-card">
        <div class="scripture-header">
            <div class="scripture-icon">
                <i class="fas fa-bible"></i>
            </div>
            <div class="scripture-label">This Week's Scripture Focus</div>
        </div>
        
        <div class="scripture-text">
            <?php echo htmlspecialchars($devotion['verse_text']); ?>
        </div>
        
        <div class="scripture-reference">
            <?php echo htmlspecialchars($devotion['verse_reference']); ?>
        </div>
        
        <div class="weekly-focus">
            <div class="focus-title">This Week's Meditation</div>
            <div class="focus-text">Meditate on this scripture throughout the week. Let it guide your thoughts and actions from Sunday to Saturday.</div>
        </div>
    </div>

    <!-- Content Card -->
    <div class="content-card">
        <div class="content-header">
            <div class="content-icon">
                <i class="fas fa-book-open"></i>
            </div>
            <div class="content-label">Weekly Devotional</div>
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
            <div class="reflection-label">Weekly Reflection</div>
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
                <span>Ready to Begin This Week's Journey</span>
            </div>
            
            <p style="color: var(--light-text); margin-bottom: 28px; font-size: 16px; line-height: 1.5;">
                Commit to meditating on this scripture throughout the week (Sunday to Saturday). 
                You can mark it complete anytime this week after reflecting on it.
            </p>
            
            <form method="POST" style="margin: 0;">
                <button type="submit" name="mark_completed" class="btn-primary">
                    <i class="fas fa-check-circle"></i> Commit to This Week's Meditation
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="completion-card">
            <div class="completion-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 class="completion-title">Week <?php echo $week_offset; ?> Committed!</h3>
            <p class="completion-message">
                You've committed to meditating on this scripture throughout the week. 
                Come back next Sunday for Week <?php echo $week_offset + 1; ?> of your spiritual journey.
            </p>
            <a href="dashboard.php" class="btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
    // Add some debugging JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Devotion page loaded');
        
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                console.log('Form submitted');
                const button = this.querySelector('button[type="submit"]');
                if (button) {
                    console.log('Button found, disabling...');
                    const originalText = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                    button.disabled = true;
                }
            });
        }
    });
</script>

<?php require_once 'footer.php'; ?>