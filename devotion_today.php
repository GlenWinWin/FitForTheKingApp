<?php
$pageTitle = "Today's Devotion";
require_once 'header.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get user's creation date to calculate day offset
$user_query = "SELECT created_at FROM users WHERE id = ?";
$stmt = $db->prepare($user_query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$created_at = new DateTime($user['created_at']);
$today = new DateTime();
$interval = $created_at->diff($today);
$day_offset = $interval->days + 1; // Start from day 1

// If beyond 365 days, loop back (or show day 365)
if ($day_offset > 365) {
    $day_offset = 365;
}

// Get today's devotion
$devotion_query = "SELECT * FROM devotions WHERE devotion_day = ?";
$stmt = $db->prepare($devotion_query);
$stmt->execute([$day_offset]);
$devotion = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if already completed today
$completion_query = "SELECT id FROM devotional_reads WHERE user_id = ? AND devotion_id = ? AND date_read = CURDATE()";
$stmt = $db->prepare($completion_query);
$stmt->execute([$user_id, $devotion['id']]);
$completed = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_POST && isset($_POST['mark_completed'])) {
    if (!$completed) {
        $insert_query = "INSERT INTO devotional_reads (user_id, devotion_id, date_read) VALUES (?, ?, CURDATE())";
        $stmt = $db->prepare($insert_query);
        $stmt->execute([$user_id, $devotion['id']]);
        $completed = true;
        echo "<script>window.location.href = 'devotion_today.php';</script>";
        exit();
    }
}
?>

<style>
    .devotion-container {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .devotion-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .devotion-day {
        color: var(--light-text);
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
    }
    
    .devotion-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 1rem;
        line-height: 1.3;
    }
    
    .devotion-meta {
        display: flex;
        justify-content: center;
        gap: 2rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--light-text);
        font-size: 0.9rem;
    }
    
    .devotion-card {
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius);
        padding: 2.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow);
        position: relative;
        overflow: hidden;
    }
    
    .devotion-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: var(--gradient-accent);
    }
    
    .scripture-section {
        margin-bottom: 2.5rem;
    }
    
    .scripture-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }
    
    .scripture-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--gradient-accent);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.1rem;
    }
    
    .scripture-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--text);
    }
    
    .scripture-text {
        font-size: 1.2rem;
        line-height: 1.8;
        color: var(--text);
        text-align: center;
        font-style: italic;
        margin-bottom: 1.5rem;
        padding: 0 1rem;
    }
    
    .scripture-reference {
        text-align: center;
        color: var(--accent);
        font-weight: 600;
        font-size: 1.1rem;
    }
    
    .devotion-content {
        line-height: 1.8;
        font-size: 1.1rem;
        color: var(--text);
        margin-bottom: 2rem;
    }
    
    .devotion-content p {
        margin-bottom: 1.5rem;
    }
    
    .reflection-section {
        background: rgba(26, 35, 126, 0.05);
        border-left: 4px solid var(--accent);
        padding: 1.5rem;
        border-radius: 0 var(--radius) var(--radius) 0;
        margin: 2rem 0;
    }
    
    .reflection-title {
        font-weight: 600;
        color: var(--accent);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .action-section {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius);
        padding: 2rem;
        text-align: center;
        margin-top: 2rem;
    }
    
    .completion-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 2rem;
        border-radius: var(--radius);
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 1.5rem;
    }
    
    .badge-completed {
        background: rgba(76, 175, 80, 0.1);
        color: #4CAF50;
        border: 2px solid rgba(76, 175, 80, 0.3);
    }
    
    .badge-pending {
        background: rgba(255, 152, 0, 0.1);
        color: #FF9800;
        border: 2px solid rgba(255, 152, 0, 0.3);
    }
    
    .completion-section {
        text-align: center;
        padding: 2rem;
        background: linear-gradient(135deg, rgba(76, 175, 80, 0.1) 0%, rgba(76, 175, 80, 0.05) 100%);
        border-radius: var(--radius);
        border: 2px solid rgba(76, 175, 80, 0.2);
    }
    
    @media (max-width: 768px) {
        .devotion-card {
            padding: 1.5rem;
        }
        
        .devotion-title {
            font-size: 1.6rem;
        }
        
        .scripture-text {
            font-size: 1.1rem;
            padding: 0;
        }
        
        .devotion-meta {
            gap: 1rem;
        }
    }
</style>

<div class="devotion-container">
    <!-- Header Section -->
    <div class="devotion-header">
        <div class="devotion-day">
            <i class="fas fa-calendar-check"></i>
            Day <?php echo $day_offset; ?> of 365
        </div>
        <h1 class="devotion-title"><?php echo htmlspecialchars($devotion['title']); ?></h1>
        
        <div class="devotion-meta">
            <div class="meta-item">
                <i class="fas fa-clock"></i>
                <span><?php echo date('F j, Y'); ?></span>
            </div>
            <div class="meta-item">
                <i class="fas fa-book-bible"></i>
                <span>Daily Devotion</span>
            </div>
            <div class="meta-item">
                <i class="fas fa-street-view"></i>
                <span>Spiritual Journey</span>
            </div>
        </div>
    </div>

    <!-- Main Devotion Card -->
    <div class="devotion-card">
        <!-- Scripture Section -->
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
                — <?php echo htmlspecialchars($devotion['verse_reference']); ?>
            </div>
        </div>

        <!-- Devotional Content -->
        <div class="devotion-content">
            <?php 
            $content = $devotion['devotional_text'];
            // Convert line breaks to paragraphs
            $paragraphs = explode("\n\n", $content);
            foreach ($paragraphs as $paragraph) {
                if (trim($paragraph)) {
                    echo '<p>' . nl2br(htmlspecialchars(trim($paragraph))) . '</p>';
                }
            }
            ?>
        </div>

        <!-- Reflection Section -->
        <div class="reflection-section">
            <div class="reflection-title">
                <i class="fas fa-lightbulb"></i>
                Reflection Question
            </div>
            <p><?php echo htmlspecialchars($devotion['reflection_question']); ?></p>
        </div>

        <!-- Completion Section -->
        <?php if (!$completed): ?>
            <div class="action-section">
                <div class="completion-badge badge-pending">
                    <i class="fas fa-clock"></i>
                    Ready to Complete
                </div>
                <p style="color: var(--light-text); margin-bottom: 1.5rem;">
                    Take a moment to reflect on today's devotion before marking it complete.
                </p>
                <form method="POST" style="margin: 0;">
                    <button type="submit" name="mark_completed" class="btn btn-primary" style="min-width: 200px;">
                        <i class="fas fa-check-circle"></i> Mark as Completed
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="completion-section">
                <i class="fas fa-check-circle" style="color: #4CAF50; font-size: 3rem; margin-bottom: 1rem;"></i>
                <h3 style="color: var(--text); margin-bottom: 0.5rem;">Devotion Completed!</h3>
                <p style="color: var(--light-text); margin-bottom: 1.5rem;">
                    Great job completing Day <?php echo $day_offset; ?>. Come back tomorrow for Day <?php echo $day_offset + 1; ?>.
                </p>
                <a href="dashboard.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>