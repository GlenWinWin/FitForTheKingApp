<?php
$pageTitle = "Prayer & Testimony";
require_once 'header.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$active_tab = $_GET['tab'] ?? 'prayers';
?>

<style>
/* Your existing CSS styles remain exactly the same */
.prayer-testimonial-card {
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border: 1px solid var(--glass-border);
    border-radius: 16px;
    margin-bottom: 1.5rem;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.prayer-testimonial-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.prayer-testimonial-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--accent), var(--secondary));
}

.card-header {
    padding: 1.25rem 1.25rem 0.75rem;
    border-bottom: 1px solid var(--border-light);
}

.card-body {
    padding: 1.25rem;
}

.card-footer {
    padding: 1.25rem;
    background: rgba(255,255,255,0.03);
    border-top: 1px solid var(--border-light);
}

.meta-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--light-text);
    font-size: 0.8rem;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
}

.meta-info i {
    opacity: 0.7;
}

.category-badge {
    background: linear-gradient(135deg, var(--accent), var(--secondary));
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.3px;
}

.interaction-bar {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 1rem;
}

.like-btn, .comment-btn {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.6rem 1rem;
    border-radius: 20px;
    transition: all 0.3s ease;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 600;
    flex: 1;
    justify-content: center;
}

.like-btn {
    background: rgba(var(--accent-rgb), 0.1);
    color: var(--accent);
    border: 1px solid rgba(var(--accent-rgb), 0.3);
}

.like-btn:hover, .like-btn.active {
    background: var(--accent);
    color: white;
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(var(--accent-rgb), 0.3);
}

.comment-btn {
    background: rgba(var(--secondary-rgb), 0.1);
    color: var(--secondary);
    border: 1px solid rgba(var(--secondary-rgb), 0.3);
}

.comment-btn:hover {
    background: var(--secondary);
    color: white;
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(var(--secondary-rgb), 0.3);
}

/* Updated Stats Display */
.stats-display {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 1rem;
    padding: 0.75rem;
    background: rgba(255,255,255,0.03);
    border-radius: 12px;
    border: 1px solid var(--border-light);
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--light-text);
    font-size: 0.85rem;
    font-weight: 600;
}

.stat-item i {
    color: var(--accent);
    font-size: 0.9rem;
}

.stat-count {
    color: var(--text);
    font-weight: 700;
}

.comments-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-light);
}

.comment-card {
    background: rgba(255,255,255,0.05);
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    border-left: 3px solid var(--accent);
    transition: all 0.3s ease;
    position: relative;
    animation: slideInUp 0.3s ease;
}

.comment-card:hover {
    background: rgba(255,255,255,0.08);
    transform: translateX(3px);
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.comment-author {
    color: var(--accent);
    font-weight: 600;
    font-size: 0.9rem;
}

.comment-time {
    color: var(--light-text);
    font-size: 0.75rem;
    margin-top: 0.2rem;
}

.comment-actions {
    display: flex;
    gap: 0.4rem;
}

.comment-text {
    margin: 0;
    line-height: 1.5;
    font-size: 0.9rem;
    color: var(--text);
}

.comment-input-container {
    width: 100%;
    position: relative;
}

.comment-input-container textarea {
    width: 100%;
    border-radius: 12px;
    resize: vertical;
    padding: 1rem 1rem 4rem 1rem;
    border: 2px solid var(--border-light);
    background: var(--glass-bg);
    font-size: 0.95rem;
    transition: all 0.3s ease;
    line-height: 1.4;
    box-sizing: border-box;
    min-height: 100px;
}

.comment-input-container textarea:focus {
    border-color: var(--accent);
    background: rgba(var(--accent-rgb), 0.05);
    box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.1);
    outline: none;
}

.comment-input-container textarea::placeholder {
    color: var(--light-text);
    opacity: 0.7;
    font-size: 0.9rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 1.5rem;
    color: var(--light-text);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1.25rem;
    opacity: 0.5;
}

.empty-state h3 {
    color: var(--text);
    margin-bottom: 1rem;
    font-weight: 700;
    font-size: 1.3rem;
}

.empty-state p {
    margin-bottom: 2rem;
    max-width: 100%;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.5;
    font-size: 0.95rem;
}

.tab-content {
    animation: fadeIn 0.5s ease;
}

.content-text {
    line-height: 1.6;
    font-size: 0.95rem;
    color: var(--text);
    white-space: pre-line;
}

.btn-icon {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.3s ease;
}

.comment-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), var(--secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 0.8rem;
    flex-shrink: 0;
}

.no-comments {
    text-align: center;
    padding: 2rem 1.5rem;
    color: var(--light-text);
    background: rgba(255,255,255,0.03);
    border-radius: 12px;
    margin-bottom: 1.5rem;
    border: 2px dashed var(--border-light);
}

.comment-tips {
    background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1), rgba(var(--secondary-rgb), 0.1));
    padding: 1rem;
    border-radius: 12px;
    border-left: 3px solid var(--accent);
    margin-top: 1.25rem;
}

.comment-input-actions {
    position: absolute;
    bottom: 1rem;
    right: 1rem;
    left: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Enhanced Mobile Responsive Design */
@media (max-width: 480px) {
    .card {
        margin: 0.5rem;
        border-radius: 12px;
    }
    
    .tabs {
        flex-direction: column;
        gap: 0.5rem;
        padding: 0.75rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
    }
    
    .tabs a {
        flex: none;
        padding: 0.875rem;
        font-size: 0.95rem;
        border-radius: 10px;
    }
    
    .prayer-testimonial-card {
        margin-bottom: 1.25rem;
        border-radius: 14px;
    }
    
    .card-header {
        padding: 1rem 1rem 0.5rem;
    }
    
    .card-header h3 {
        font-size: 1.2rem !important;
        margin-bottom: 0.4rem !important;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .card-footer {
        padding: 1rem;
    }
    
    .meta-info {
        gap: 0.5rem;
        font-size: 0.75rem;
    }
    
    .interaction-bar {
        gap: 0.75rem;
        margin-top: 0.75rem;
    }
    
    .like-btn, .comment-btn {
        padding: 0.5rem 0.75rem;
        font-size: 0.8rem;
        border-radius: 16px;
    }
    
    /* Updated Stats Display for Mobile */
    .stats-display {
        flex-direction: column;
        gap: 0.75rem;
        padding: 0.875rem;
        margin-top: 0.875rem;
    }
    
    .stat-item {
        justify-content: center;
        width: 100%;
        padding: 0.5rem;
        background: rgba(255,255,255,0.02);
        border-radius: 8px;
    }
    
    .comments-section {
        margin-top: 1.25rem;
        padding-top: 1.25rem;
    }
    
    .comments-section h4 {
        font-size: 1rem !important;
        margin-bottom: 1rem !important;
    }
    
    .comment-count {
        padding: 0.2rem 0.6rem !important;
        font-size: 0.7rem !important;
    }
    
    .comment-card {
        padding: 0.875rem;
        border-radius: 10px;
        margin-bottom: 0.625rem;
    }
    
    .comment-header {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
    }
    
    .comment-avatar {
        width: 28px;
        height: 28px;
        font-size: 0.75rem;
    }
    
    .comment-author {
        font-size: 0.85rem;
    }
    
    .comment-time {
        font-size: 0.7rem;
    }
    
    .comment-text {
        font-size: 0.85rem;
        line-height: 1.4;
    }
    
    .comment-input-container textarea {
        padding: 0.875rem 0.875rem 3.5rem 0.875rem;
        font-size: 0.9rem;
        border-radius: 10px;
        min-height: 90px;
    }
    
    .comment-input-actions {
        bottom: 0.75rem;
        right: 0.75rem;
        left: 0.75rem;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .comment-input-actions .btn {
        width: 100%;
        justify-content: center;
        padding: 0.75rem 1rem !important;
        font-size: 0.9rem;
        border-radius: 20px !important;
    }
    
    .comment-tips {
        padding: 0.875rem;
        border-radius: 10px;
        margin-top: 1rem;
    }
    
    .comment-tips ul {
        padding-left: 1.25rem;
        font-size: 0.8rem;
        line-height: 1.4;
    }
    
    .empty-state {
        padding: 2.5rem 1.25rem;
    }
    
    .empty-state i {
        font-size: 2.5rem;
    }
    
    .empty-state h3 {
        font-size: 1.2rem;
    }
    
    .empty-state p {
        font-size: 0.9rem;
    }
    
    .empty-state .btn {
        padding: 0.875rem 2rem !important;
        font-size: 1rem !important;
        width: 100%;
        max-width: 280px;
    }
    
    .no-comments {
        padding: 1.5rem 1rem;
    }
    
    .no-comments i {
        font-size: 2rem !important;
    }
    
    .no-comments p {
        font-size: 0.85rem !important;
    }
    
    /* Add button mobile optimization */
    .card > div:has(.btn-primary) {
        margin-bottom: 2rem;
    }
    
    .card > div:has(.btn-primary) .btn {
        padding: 0.875rem 2rem !important;
        font-size: 1rem !important;
        width: 100%;
        max-width: 280px;
        margin: 0 auto;
        display: block;
    }
}

@media (max-width: 360px) {
    .card {
        margin: 0.25rem;
    }
    
    .tabs {
        padding: 0.5rem;
    }
    
    .tabs a {
        padding: 0.75rem;
        font-size: 0.9rem;
    }
    
    .meta-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .interaction-bar {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .like-btn, .comment-btn {
        width: 100%;
    }
    
    .stats-display {
        padding: 0.75rem;
        gap: 0.5rem;
    }
    
    .comment-input-container textarea {
        font-size: 16px; /* Prevents zoom on iOS */
    }
}

/* Safe area insets for notch devices */
@supports(padding: max(0px)) {
    .card {
        padding-left: max(0.5rem, env(safe-area-inset-left));
        padding-right: max(0.5rem, env(safe-area-inset-right));
    }
}

/* Touch device optimizations */
@media (hover: none) and (pointer: coarse) {
    .prayer-testimonial-card:hover {
        transform: none;
    }
    
    .comment-card:hover {
        transform: none;
        background: rgba(255,255,255,0.05);
    }
    
    .like-btn:active, .comment-btn:active {
        transform: scale(0.95);
    }
}

/* High DPI screen optimizations */
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
    .prayer-testimonial-card {
        border-width: 0.5px;
    }
    
    .comment-card {
        border-left-width: 2px;
    }
}
</style>

<!-- Premium Background -->
<div class="premium-bg"></div>
<div class="particles-container" id="particles-container"></div>

<div class="card">
    <!-- Enhanced Mobile-Friendly Tabs -->
    <div class="tabs" style="display: flex; gap: 0.5rem; margin-bottom: 2rem; background: var(--glass-bg); padding: 0.5rem; border-radius: 16px; border: 1px solid var(--glass-border);">
        <a href="?tab=prayers" 
           class="btn <?php echo $active_tab === 'prayers' ? 'btn-primary' : 'btn-outline'; ?>" 
           style="flex: 1; text-align: center; padding: 1rem; border-radius: 12px; font-weight: 700; font-size: 1rem; transition: all 0.3s ease;">
            <i class="fas fa-hands-praying"></i> <span class="tab-text">Prayer Requests</span>
        </a>
        <a href="?tab=testimonials" 
           class="btn <?php echo $active_tab === 'testimonials' ? 'btn-primary' : 'btn-outline'; ?>" 
           style="flex: 1; text-align: center; padding: 1rem; border-radius: 12px; font-weight: 700; font-size: 1rem; transition: all 0.3s ease;">
            <i class="fas fa-heart"></i> <span class="tab-text">Testimonials</span>
        </a>
    </div>

    <!-- Add Button - Mobile Optimized -->
    <div style="text-align: center; margin-bottom: 3rem;">
        <a href="<?php echo $active_tab === 'prayers' ? 'prayer_add.php' : 'testimonial_add.php'; ?>" 
           class="btn btn-primary" style="padding: 1rem 2.5rem; border-radius: 50px; font-weight: 700; font-size: 1.1rem; box-shadow: 0 8px 25px rgba(var(--accent-rgb), 0.3);">
            <i class="fas fa-plus"></i> 
            Add <?php echo $active_tab === 'prayers' ? 'Prayer Request' : 'Testimony'; ?>
        </a>
    </div>

    <div class="tab-content">
    <?php if ($active_tab === 'prayers'): ?>
        <!-- Prayer Requests -->
        <?php
        $prayers_query = "SELECT p.*, u.name as user_name, 
                         COUNT(DISTINCT pl.id) as like_count,
                         COUNT(DISTINCT pc.id) as comment_count,
                         EXISTS(SELECT 1 FROM prayer_likes WHERE prayer_id = p.id AND user_id = ?) as user_liked
                         FROM prayers p 
                         JOIN users u ON p.user_id = u.id
                         LEFT JOIN prayer_likes pl ON p.id = pl.prayer_id
                         LEFT JOIN prayer_comments pc ON p.id = pc.prayer_id
                         GROUP BY p.id 
                         ORDER BY p.created_at DESC";
        $stmt = $db->prepare($prayers_query);
        $stmt->execute([$user_id]);
        $prayers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <?php if ($prayers): ?>
            <?php foreach ($prayers as $prayer): ?>
            <div class="prayer-testimonial-card">
                <div class="card-header">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div style="flex: 1;">
                            <h3 style="color: var(--accent); margin-bottom: 0.5rem; font-weight: 800; font-size: 1.4rem;"><?php echo $prayer['title']; ?></h3>
                            <div class="meta-info">
                                <span><i class="fas fa-user"></i> <?php echo $prayer['user_name']; ?></span>
                                <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($prayer['created_at'])); ?></span>
                                <?php if ($prayer['category']): ?>
                                <span class="category-badge"><?php echo $prayer['category']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <p class="content-text"><?php echo nl2br(htmlspecialchars($prayer['prayer_text'])); ?></p>
                </div>
                
                <div class="card-footer">
                    <!-- Updated Stats Display -->
                    <div class="stats-display">
                        <div class="stat-item">
                            <i class="fas fa-heart"></i>
                            <span class="stat-count"><?php echo $prayer['like_count']; ?></span>
                            <span>Prayers</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-comment"></i>
                            <span class="stat-count"><?php echo $prayer['comment_count']; ?></span>
                            <span>Comments</span>
                        </div>
                    </div>
                    
                    <div class="interaction-bar">
                        <!-- UPDATED: Prayer Like Button with AJAX -->
                        <button type="button" 
                                class="like-btn <?php echo $prayer['user_liked'] ? 'active' : ''; ?>" 
                                data-prayer-id="<?php echo $prayer['id']; ?>"
                                onclick="likePrayer(this)">
                            <i class="fas fa-heart"></i> 
                            <span><?php echo $prayer['user_liked'] ? 'Praying' : 'Pray for This'; ?></span>
                        </button>
                        
                        <button type="button" class="comment-btn" onclick="toggleComments(this)">
                            <i class="fas fa-comment"></i>
                            <span>Add Comment</span>
                        </button>
                    </div>
                    
                    <!-- Enhanced Comments Section -->
                    <div class="comments-section" style="display: none;">
                        <h4 style="color: var(--text); margin-bottom: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-comments" style="color: var(--accent);"></i> 
                            Comments <span class="comment-count" style="background: var(--accent); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; margin-left: 0.5rem;"><?php
                            $comments_query = "SELECT COUNT(*) as count FROM prayer_comments WHERE prayer_id = ?";
                            $stmt = $db->prepare($comments_query);
                            $stmt->execute([$prayer['id']]);
                            $comment_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                            echo $comment_count;
                            ?></span>
                        </h4>
                        
                        <?php
                        $comments_query = "SELECT pc.*, u.name as user_name 
                                         FROM prayer_comments pc 
                                         JOIN users u ON pc.user_id = u.id 
                                         WHERE pc.prayer_id = ? 
                                         ORDER BY pc.created_at ASC";
                        $stmt = $db->prepare($comments_query);
                        $stmt->execute([$prayer['id']]);
                        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <?php if ($comments): ?>
                        <div class="comments-list" style="margin-bottom: 2rem;">
                            <?php foreach ($comments as $comment): ?>
                            <div class="comment-card">
                                <div class="comment-header">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div class="comment-avatar">
                                            <?php echo strtoupper(substr($comment['user_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <span class="comment-author"><?php echo $comment['user_name']; ?></span>
                                            <div class="comment-time"><?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?></div>
                                        </div>
                                    </div>
                                    <div class="comment-actions">
                                        <i class="fas fa-heart" style="color: var(--light-text); cursor: pointer; transition: color 0.3s ease;"></i>
                                    </div>
                                </div>
                                <p class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="no-comments">
                            <i class="fas fa-comment-slash" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p style="margin: 0; font-size: 0.95rem; line-height: 1.5;">No comments yet. Be the first to share encouragement and prayers!</p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- UPDATED: Prayer Comment Form with AJAX -->
                        <form method="POST" class="comment-form" onsubmit="submitPrayerComment(event, this)">
                            <input type="hidden" name="prayer_id" value="<?php echo $prayer['id']; ?>">
                            
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <div class="comment-input-container">
                                    <textarea name="comment_text" placeholder="Share your encouragement, prayer, or thoughts..." 
                                             rows="3" required></textarea>
                                    
                                    <div class="comment-input-actions">                                        
                                        <button type="submit" 
                                                class="btn btn-primary" 
                                                style="padding: 0.75rem 1.5rem; border-radius: 25px; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(var(--accent-rgb), 0.3);">
                                            <i class="fas fa-paper-plane"></i>
                                            Post Comment
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="comment-tips">
                                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                    <i class="fas fa-lightbulb" style="color: var(--accent); font-size: 1.1rem;"></i>
                                    <strong style="color: var(--text); font-size: 0.95rem;">Tips for great comments:</strong>
                                </div>
                                <ul style="margin: 0; padding-left: 1.5rem; color: var(--light-text); font-size: 0.9rem; line-height: 1.6;">
                                    <li>Share words of encouragement and support</li>
                                    <li>Offer prayers or scripture verses</li>
                                    <li>Keep comments positive and uplifting</li>
                                    <li>Respect different perspectives</li>
                                </ul>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-hands-praying"></i>
                <h3>No Prayer Requests Yet</h3>
                <p>Be the first to share a prayer request and let the community support you in prayer and encouragement!</p>
                <a href="prayer_add.php" class="btn btn-primary" style="padding: 1rem 2.5rem; border-radius: 50px; font-weight: 700; font-size: 1.1rem; box-shadow: 0 8px 25px rgba(var(--accent-rgb), 0.3);">
                    <i class="fas fa-plus"></i> Add First Prayer Request
                </a>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Testimonials -->
        <?php
        $testimonials_query = "SELECT t.*, u.name as user_name, 
                              COUNT(DISTINCT tl.id) as like_count,
                              COUNT(DISTINCT tc.id) as comment_count,
                              EXISTS(SELECT 1 FROM testimonial_likes WHERE testimonial_id = t.id AND user_id = ?) as user_liked
                              FROM testimonials t 
                              JOIN users u ON t.user_id = u.id
                              LEFT JOIN testimonial_likes tl ON t.id = tl.testimonial_id
                              LEFT JOIN testimonial_comments tc ON t.id = tc.testimonial_id
                              GROUP BY t.id 
                              ORDER BY t.created_at DESC";
        $stmt = $db->prepare($testimonials_query);
        $stmt->execute([$user_id]);
        $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <?php if ($testimonials): ?>
            <?php foreach ($testimonials as $testimonial): ?>
            <div class="prayer-testimonial-card">
                <div class="card-header">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div style="flex: 1;">
                            <h3 style="color: var(--accent); margin-bottom: 0.5rem; font-weight: 800; font-size: 1.4rem;"><?php echo $testimonial['title']; ?></h3>
                            <div class="meta-info">
                                <span><i class="fas fa-user"></i> <?php echo $testimonial['user_name']; ?></span>
                                <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($testimonial['created_at'])); ?></span>
                                <?php if ($testimonial['category']): ?>
                                <span class="category-badge"><?php echo $testimonial['category']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <p class="content-text"><?php echo nl2br(htmlspecialchars($testimonial['testimony_text'])); ?></p>
                </div>
                
                <div class="card-footer">
                    <!-- Updated Stats Display -->
                    <div class="stats-display">
                        <div class="stat-item">
                            <i class="fas fa-heart"></i>
                            <span class="stat-count"><?php echo $testimonial['like_count']; ?></span>
                            <span>Encouragements</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-comment"></i>
                            <span class="stat-count"><?php echo $testimonial['comment_count']; ?></span>
                            <span>Comments</span>
                        </div>
                    </div>
                    
                    <div class="interaction-bar">
                        <!-- UPDATED: Testimonial Like Button with AJAX -->
                        <button type="button" 
                                class="like-btn <?php echo $testimonial['user_liked'] ? 'active' : ''; ?>" 
                                data-testimonial-id="<?php echo $testimonial['id']; ?>"
                                onclick="likeTestimonial(this)">
                            <i class="fas fa-heart"></i> 
                            <span><?php echo $testimonial['user_liked'] ? 'Encouraged' : 'Encourage'; ?></span>
                        </button>
                        
                        <button type="button" class="comment-btn" onclick="toggleComments(this)">
                            <i class="fas fa-comment"></i>
                            <span>Add Comment</span>
                        </button>
                    </div>
                    
                    <!-- Enhanced Comments Section -->
                    <div class="comments-section" style="display: none;">
                        <h4 style="color: var(--text); margin-bottom: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-comments" style="color: var(--accent);"></i> 
                            Comments <span class="comment-count" style="background: var(--accent); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; margin-left: 0.5rem;"><?php
                            $comments_query = "SELECT COUNT(*) as count FROM testimonial_comments WHERE testimonial_id = ?";
                            $stmt = $db->prepare($comments_query);
                            $stmt->execute([$testimonial['id']]);
                            $comment_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                            echo $comment_count;
                            ?></span>
                        </h4>
                        
                        <?php
                        $comments_query = "SELECT tc.*, u.name as user_name 
                                         FROM testimonial_comments tc 
                                         JOIN users u ON tc.user_id = u.id 
                                         WHERE tc.testimonial_id = ? 
                                         ORDER BY tc.created_at ASC";
                        $stmt = $db->prepare($comments_query);
                        $stmt->execute([$testimonial['id']]);
                        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <?php if ($comments): ?>
                        <div class="comments-list" style="margin-bottom: 2rem;">
                            <?php foreach ($comments as $comment): ?>
                            <div class="comment-card">
                                <div class="comment-header">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div class="comment-avatar">
                                            <?php echo strtoupper(substr($comment['user_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <span class="comment-author"><?php echo $comment['user_name']; ?></span>
                                            <div class="comment-time"><?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?></div>
                                        </div>
                                    </div>
                                    <div class="comment-actions">
                                        <i class="fas fa-heart" style="color: var(--light-text); cursor: pointer; transition: color 0.3s ease;"></i>
                                    </div>
                                </div>
                                <p class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="no-comments">
                            <i class="fas fa-comment-slash" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p style="margin: 0; font-size: 0.95rem; line-height: 1.5;">No comments yet. Be the first to share encouragement and celebrate this testimony!</p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- UPDATED: Testimonial Comment Form with AJAX -->
                        <form method="POST" class="comment-form" onsubmit="submitTestimonialComment(event, this)">
                            <input type="hidden" name="testimonial_id" value="<?php echo $testimonial['id']; ?>">
                            
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <div class="comment-input-container">
                                    <textarea name="comment_text" placeholder="Share your encouragement, thoughts, or celebration..." 
                                             rows="3" required></textarea>
                                    
                                    <div class="comment-input-actions">                                        
                                        <button type="submit" 
                                                class="btn btn-primary" 
                                                style="padding: 0.75rem 1.5rem; border-radius: 25px; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(var(--accent-rgb), 0.3);">
                                            <i class="fas fa-paper-plane"></i>
                                            Post Comment
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="comment-tips">
                                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                    <i class="fas fa-lightbulb" style="color: var(--accent); font-size: 1.1rem;"></i>
                                    <strong style="color: var(--text); font-size: 0.95rem;">Tips for great comments:</strong>
                                </div>
                                <ul style="margin: 0; padding-left: 1.5rem; color: var(--light-text); font-size: 0.9rem; line-height: 1.6;">
                                    <li>Celebrate and encourage the testimony shared</li>
                                    <li>Share how this testimony inspires you</li>
                                    <li>Keep comments positive and uplifting</li>
                                    <li>Share related experiences or scriptures</li>
                                </ul>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-heart"></i>
                <h3>No Testimonials Yet</h3>
                <p>Share how God has been working in your life through your fitness and wellness journey!</p>
                <a href="testimonial_add.php" class="btn btn-primary" style="padding: 1rem 2.5rem; border-radius: 50px; font-weight: 700; font-size: 1.1rem; box-shadow: 0 8px 25px rgba(var(--accent-rgb), 0.3);">
                    <i class="fas fa-plus"></i> Share First Testimony
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    </div>
</div>

<script>
// AJAX Like and Comment Functions
async function likePrayer(button) {
    const prayerId = button.getAttribute('data-prayer-id');
    const icon = button.querySelector('i');
    const likeCountElement = button.closest('.card-footer').querySelector('.stat-item:first-child .stat-count');
    
    // Add loading animation
    button.disabled = true;
    icon.style.transform = 'scale(1.2)';
    
    try {
        const response = await fetch('ajax_like_prayer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `prayer_id=${prayerId}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Update like count
            likeCountElement.textContent = result.like_count;
            
            // Update button state
            if (result.user_liked) {
                button.classList.add('active');
                button.querySelector('span').textContent = 'Praying';
            } else {
                button.classList.remove('active');
                button.querySelector('span').textContent = 'Pray for This';
            }
            
            // Add success animation
            button.style.transform = 'scale(1.05)';
            setTimeout(() => {
                button.style.transform = 'scale(1)';
            }, 200);
        } else {
            showNotification(result.message || 'Error liking prayer', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    } finally {
        button.disabled = false;
        icon.style.transform = 'scale(1)';
    }
}

async function likeTestimonial(button) {
    const testimonialId = button.getAttribute('data-testimonial-id');
    const icon = button.querySelector('i');
    const likeCountElement = button.closest('.card-footer').querySelector('.stat-item:first-child .stat-count');
    
    // Add loading animation
    button.disabled = true;
    icon.style.transform = 'scale(1.2)';
    
    try {
        const response = await fetch('ajax_like_testimonial.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `testimonial_id=${testimonialId}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Update like count
            likeCountElement.textContent = result.like_count;
            
            // Update button state
            if (result.user_liked) {
                button.classList.add('active');
                button.querySelector('span').textContent = 'Encouraged';
            } else {
                button.classList.remove('active');
                button.querySelector('span').textContent = 'Encourage';
            }
            
            // Add success animation
            button.style.transform = 'scale(1.05)';
            setTimeout(() => {
                button.style.transform = 'scale(1)';
            }, 200);
        } else {
            showNotification(result.message || 'Error liking testimony', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    } finally {
        button.disabled = false;
        icon.style.transform = 'scale(1)';
    }
}

async function submitPrayerComment(event, form) {
    event.preventDefault();
    
    const prayerId = form.querySelector('input[name="prayer_id"]').value;
    const commentText = form.querySelector('textarea[name="comment_text"]').value.trim();
    const submitButton = form.querySelector('button[type="submit"]');
    const commentsList = form.closest('.comments-section').querySelector('.comments-list');
    const noCommentsElement = form.closest('.comments-section').querySelector('.no-comments');
    const commentCountElement = form.closest('.comments-section').querySelector('.comment-count');
    
    if (!commentText) {
        showNotification('Please enter a comment', 'error');
        return;
    }
    
    // Add loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting...';
    
    try {
        const response = await fetch('ajax_comment_prayer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `prayer_id=${prayerId}&comment_text=${encodeURIComponent(commentText)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Clear textarea
            form.querySelector('textarea[name="comment_text"]').value = '';
            
            // Update comment count
            commentCountElement.textContent = result.comment_count;
            
            // Remove "no comments" message if it exists
            if (noCommentsElement) {
                noCommentsElement.remove();
            }
            
            // Create comments list if it doesn't exist
            if (!commentsList) {
                const commentsSection = form.closest('.comments-section');
                const commentsHeading = commentsSection.querySelector('h4');
                const newCommentsList = document.createElement('div');
                newCommentsList.className = 'comments-list';
                newCommentsList.style.marginBottom = '2rem';
                commentsHeading.parentNode.insertBefore(newCommentsList, form);
            }
            
            // Add new comment to list
            const commentHTML = createCommentHTML(result.comment);
            const commentsListContainer = form.previousElementSibling;
            if (commentsListContainer && commentsListContainer.classList.contains('comments-list')) {
                commentsListContainer.insertAdjacentHTML('beforeend', commentHTML);
            }
            
            // Scroll to new comment
            const newComment = commentsListContainer.lastElementChild;
            newComment.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            // Add highlight animation
            newComment.style.animation = 'highlightPulse 2s ease';
            
            showNotification('Comment posted successfully!', 'success');
        } else {
            showNotification(result.message || 'Error posting comment', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    } finally {
        // Reset button
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Post Comment';
    }
}

async function submitTestimonialComment(event, form) {
    event.preventDefault();
    
    const testimonialId = form.querySelector('input[name="testimonial_id"]').value;
    const commentText = form.querySelector('textarea[name="comment_text"]').value.trim();
    const submitButton = form.querySelector('button[type="submit"]');
    const commentsList = form.closest('.comments-section').querySelector('.comments-list');
    const noCommentsElement = form.closest('.comments-section').querySelector('.no-comments');
    const commentCountElement = form.closest('.comments-section').querySelector('.comment-count');
    
    if (!commentText) {
        showNotification('Please enter a comment', 'error');
        return;
    }
    
    // Add loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting...';
    
    try {
        const response = await fetch('ajax_comment_testimonial.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `testimonial_id=${testimonialId}&comment_text=${encodeURIComponent(commentText)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Clear textarea
            form.querySelector('textarea[name="comment_text"]').value = '';
            
            // Update comment count
            commentCountElement.textContent = result.comment_count;
            
            // Remove "no comments" message if it exists
            if (noCommentsElement) {
                noCommentsElement.remove();
            }
            
            // Create comments list if it doesn't exist
            if (!commentsList) {
                const commentsSection = form.closest('.comments-section');
                const commentsHeading = commentsSection.querySelector('h4');
                const newCommentsList = document.createElement('div');
                newCommentsList.className = 'comments-list';
                newCommentsList.style.marginBottom = '2rem';
                commentsHeading.parentNode.insertBefore(newCommentsList, form);
            }
            
            // Add new comment to list
            const commentHTML = createCommentHTML(result.comment);
            const commentsListContainer = form.previousElementSibling;
            if (commentsListContainer && commentsListContainer.classList.contains('comments-list')) {
                commentsListContainer.insertAdjacentHTML('beforeend', commentHTML);
            }
            
            // Scroll to new comment
            const newComment = commentsListContainer.lastElementChild;
            newComment.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            // Add highlight animation
            newComment.style.animation = 'highlightPulse 2s ease';
            
            showNotification('Comment posted successfully!', 'success');
        } else {
            showNotification(result.message || 'Error posting comment', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    } finally {
        // Reset button
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Post Comment';
    }
}

// Helper function to create comment HTML
function createCommentHTML(comment) {
    const initial = comment.user_name.charAt(0).toUpperCase();
    const date = new Date(comment.created_at).toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true 
    });
    
    return `
        <div class="comment-card">
            <div class="comment-header">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div class="comment-avatar">${initial}</div>
                    <div>
                        <span class="comment-author">${comment.user_name}</span>
                        <div class="comment-time">${date}</div>
                    </div>
                </div>
                <div class="comment-actions">
                    <i class="fas fa-heart" style="color: var(--light-text); cursor: pointer; transition: color 0.3s ease;"></i>
                </div>
            </div>
            <p class="comment-text">${comment.comment_text.replace(/\n/g, '<br>')}</p>
        </div>
    `;
}

// Notification system
function showNotification(message, type = 'info') {
    // Remove existing notification
    const existingNotification = document.querySelector('.ajax-notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = `ajax-notification ${type}`;
    notification.innerHTML = `
        <div style="position: fixed; top: 20px; right: 20px; background: ${type === 'error' ? '#e74c3c' : type === 'success' ? '#27ae60' : '#3498db'}; 
                    color: white; padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
                    z-index: 10000; animation: slideInRight 0.3s ease; max-width: 300px;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i>
                <span>${message}</span>
            </div>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }
    }, 3000);
}

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes highlightPulse {
        0% { background: rgba(var(--accent-rgb), 0.1); }
        50% { background: rgba(var(--accent-rgb), 0.2); }
        100% { background: rgba(255,255,255,0.05); }
    }
    
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .like-btn:disabled, .comment-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .fa-spinner {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

document.addEventListener('DOMContentLoaded', function() {
    // Existing DOMContentLoaded code...
    const textareas = document.querySelectorAll('textarea[name="comment_text"]');
    
    // Smooth focus on comment form
    const commentForms = document.querySelectorAll('.comment-form');
    commentForms.forEach(form => {
        const textarea = form.querySelector('textarea');
        const commentCards = form.closest('.comments-section').querySelectorAll('.comment-card');
        
        // Focus textarea when clicking anywhere in the form
        form.addEventListener('click', function(e) {
            if (e.target !== textarea && !e.target.closest('.comment-input-actions')) {
                textarea.focus();
            }
        });
        
        // Add hover effects to comment cards
        commentCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });
    });
    
    // Mobile-specific optimizations
    function isMobile() {
        return window.innerWidth <= 480;
    }
    
    // Adjust textarea behavior for mobile
    if (isMobile()) {
        textareas.forEach(textarea => {
            textarea.addEventListener('focus', function() {
                setTimeout(() => {
                    this.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
            });
        });
    }
});

// Toggle comments section
function toggleComments(button) {
    const card = button.closest('.prayer-testimonial-card');
    const commentsSection = card.querySelector('.comments-section');
    const isHidden = commentsSection.style.display === 'none';
    
    commentsSection.style.display = isHidden ? 'block' : 'none';
    button.querySelector('span').textContent = isHidden ? 'Hide Comments' : 'Add Comment';
    
    // Smooth scroll to comments when opening
    if (isHidden) {
        setTimeout(() => {
            commentsSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
    }
}
</script>

<?php require_once 'footer.php'; ?>