<?php
$pageTitle = "Prayer & Testimony";
require_once 'header.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$active_tab = $_GET['tab'] ?? 'prayers';

// Handle likes
if ($_POST && isset($_POST['like_prayer'])) {
    $prayer_id = sanitize($_POST['prayer_id']);
    
    // Check if already liked
    $check_query = "SELECT id FROM prayer_likes WHERE prayer_id = ? AND user_id = ?";
    $stmt = $db->prepare($check_query);
    $stmt->execute([$prayer_id, $user_id]);
    
    if (!$stmt->fetch()) {
        $insert_query = "INSERT INTO prayer_likes (prayer_id, user_id) VALUES (?, ?)";
        $stmt = $db->prepare($insert_query);
        $stmt->execute([$prayer_id, $user_id]);
    }
    echo "<script>window.location.href = 'prayers_testimonials.php?tab=prayers';</script>";
    exit();
}

if ($_POST && isset($_POST['like_testimonial'])) {
    $testimonial_id = sanitize($_POST['testimonial_id']);
    
    $check_query = "SELECT id FROM testimonial_likes WHERE testimonial_id = ? AND user_id = ?";
    $stmt = $db->prepare($check_query);
    $stmt->execute([$testimonial_id, $user_id]);
    
    if (!$stmt->fetch()) {
        $insert_query = "INSERT INTO testimonial_likes (testimonial_id, user_id) VALUES (?, ?)";
        $stmt = $db->prepare($insert_query);
        $stmt->execute([$testimonial_id, $user_id]);
    }
    echo "<script>window.location.href = 'prayers_testimonials.php?tab=testimonials';</script>";
    exit();
}

// Handle comments
if ($_POST && isset($_POST['comment_prayer'])) {
    $prayer_id = sanitize($_POST['prayer_id']);
    $comment_text = sanitize($_POST['comment_text']);
    
    $insert_query = "INSERT INTO prayer_comments (prayer_id, user_id, comment_text) VALUES (?, ?, ?)";
    $stmt = $db->prepare($insert_query);
    $stmt->execute([$prayer_id, $user_id, $comment_text]);
    echo "<script>window.location.href = 'prayers_testimonials.php?tab=prayers';</script>";
    exit();
}

if ($_POST && isset($_POST['comment_testimonial'])) {
    $testimonial_id = sanitize($_POST['testimonial_id']);
    $comment_text = sanitize($_POST['comment_text']);
    
    $insert_query = "INSERT INTO testimonial_comments (testimonial_id, user_id, comment_text) VALUES (?, ?, ?)";
    $stmt = $db->prepare($insert_query);
    $stmt->execute([$testimonial_id, $user_id, $comment_text]);
    echo "<script>window.location.href = 'prayers_testimonials.php?tab=testimonials';</script>";
    exit();
}
?>

<style>
.prayer-testimonial-card {
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    margin-bottom: 2rem;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.prayer-testimonial-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

.prayer-testimonial-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--accent), var(--secondary));
}

.card-header {
    padding: 1.5rem 1.5rem 1rem;
    border-bottom: 1px solid var(--border-light);
}

.card-body {
    padding: 1.5rem;
}

.card-footer {
    padding: 1.5rem;
    background: rgba(255,255,255,0.03);
    border-top: 1px solid var(--border-light);
}

.meta-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: var(--light-text);
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
}

.meta-info i {
    opacity: 0.7;
}

.category-badge {
    background: linear-gradient(135deg, var(--accent), var(--secondary));
    color: white;
    padding: 0.3rem 1rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.interaction-bar {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-top: 1rem;
}

.like-btn, .comment-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border-radius: 25px;
    transition: all 0.3s ease;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 600;
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
    box-shadow: 0 5px 15px rgba(var(--accent-rgb), 0.3);
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
    box-shadow: 0 5px 15px rgba(var(--secondary-rgb), 0.3);
}

.comments-section {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border-light);
}

.comment-card {
    background: rgba(255,255,255,0.05);
    border-radius: 16px;
    padding: 1.25rem;
    margin-bottom: 1rem;
    border-left: 4px solid var(--accent);
    transition: all 0.3s ease;
    position: relative;
    animation: slideInUp 0.3s ease;
}

.comment-card:hover {
    background: rgba(255,255,255,0.08);
    transform: translateX(5px);
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.comment-author {
    color: var(--accent);
    font-weight: 600;
    font-size: 0.95rem;
}

.comment-time {
    color: var(--light-text);
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

.comment-actions {
    display: flex;
    gap: 0.5rem;
}

.comment-text {
    margin: 0;
    line-height: 1.6;
    font-size: 0.95rem;
    color: var(--text);
}

.comment-input-container {
    width: 100%;
    position: relative;
}

.comment-input-container textarea {
    width: 100%;
    border-radius: 16px;
    resize: vertical;
    padding: 1.5rem 1rem 3.5rem 1rem;
    border: 2px solid var(--border-light);
    background: var(--glass-bg);
    font-size: 1rem;
    transition: all 0.3s ease;
    line-height: 1.5;
    box-sizing: border-box;
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
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--light-text);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    opacity: 0.5;
}

.empty-state h3 {
    color: var(--text);
    margin-bottom: 1rem;
    font-weight: 700;
}

.empty-state p {
    margin-bottom: 2rem;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
}

.tab-content {
    animation: fadeIn 0.5s ease;
}

.content-text {
    line-height: 1.7;
    font-size: 1.05rem;
    color: var(--text);
    white-space: pre-line;
}

.btn-icon {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.comment-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), var(--secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.no-comments {
    text-align: center;
    padding: 2.5rem;
    color: var(--light-text);
    background: rgba(255,255,255,0.03);
    border-radius: 16px;
    margin-bottom: 2rem;
    border: 2px dashed var(--border-light);
}

.comment-tips {
    background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1), rgba(var(--secondary-rgb), 0.1));
    padding: 1.25rem;
    border-radius: 16px;
    border-left: 4px solid var(--accent);
    margin-top: 1.5rem;
}

.comment-input-actions {
    position: absolute;
    bottom: 1rem;
    right: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive design */
@media (max-width: 768px) {
    .comment-input-actions {
        position: static !important;
        margin-top: 1rem;
        justify-content: space-between;
        flex-direction: column;
        gap: 1rem;
    }
    
    .comment-input-actions .btn {
        width: 100%;
        justify-content: center;
    }
    
    .meta-info {
        gap: 0.5rem;
    }
    
    .interaction-bar {
        gap: 1rem;
    }
    
    .like-btn, .comment-btn {
        padding: 0.6rem 1rem;
        font-size: 0.85rem;
    }
    
    .comment-input-container textarea {
        padding: 1.25rem 1rem 1rem 1rem;
    }
}
</style>

<!-- Premium Background -->
<div class="premium-bg"></div>
<div class="particles-container" id="particles-container"></div>

<div class="card">
    <!-- Enhanced Tabs -->
    <div class="tabs" style="display: flex; gap: 0.5rem; margin-bottom: 2rem; background: var(--glass-bg); padding: 0.5rem; border-radius: 16px; border: 1px solid var(--glass-border);">
        <a href="?tab=prayers" 
           class="btn <?php echo $active_tab === 'prayers' ? 'btn-primary' : 'btn-outline'; ?>" 
           style="flex: 1; text-align: center; padding: 1rem; border-radius: 12px; font-weight: 700; font-size: 1rem; transition: all 0.3s ease;">
            <i class="fas fa-hands-praying"></i> Prayer Requests
        </a>
        <a href="?tab=testimonials" 
           class="btn <?php echo $active_tab === 'testimonials' ? 'btn-primary' : 'btn-outline'; ?>" 
           style="flex: 1; text-align: center; padding: 1rem; border-radius: 12px; font-weight: 700; font-size: 1rem; transition: all 0.3s ease;">
            <i class="fas fa-heart"></i> Testimonials
        </a>
    </div>

    <!-- Add Button -->
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
                    <div class="interaction-bar">
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="prayer_id" value="<?php echo $prayer['id']; ?>">
                            <button type="submit" name="like_prayer" class="like-btn <?php echo $prayer['user_liked'] ? 'active' : ''; ?>">
                                <i class="fas fa-heart"></i> 
                                <span><?php echo $prayer['like_count']; ?> Prayers</span>
                            </button>
                        </form>
                        
                        <div class="comment-btn">
                            <i class="fas fa-comment"></i>
                            <span><?php echo $prayer['comment_count']; ?> Comments</span>
                        </div>
                    </div>
                    
                    <!-- Enhanced Comments Section -->
                    <div class="comments-section">
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
                        
                        <!-- Enhanced Comment Input -->
                        <form method="POST" class="comment-form">
                            <input type="hidden" name="prayer_id" value="<?php echo $prayer['id']; ?>">
                            
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <div class="comment-input-container">
                                    <textarea name="comment_text" placeholder="Share your encouragement, prayer, or thoughts..." 
                                             rows="4" required></textarea>
                                    
                                    <div class="comment-input-actions">                                        
                                        <button type="submit" name="comment_prayer" 
                                                class="btn btn-primary" 
                                                style="
                                                padding: 0.75rem 1.5rem;
                                                border-radius: 25px;
                                                font-weight: 700;
                                                display: flex;
                                                align-items: center;
                                                gap: 0.5rem;
                                                transition: all 0.3s ease;
                                                box-shadow: 0 4px 15px rgba(var(--accent-rgb), 0.3);
                                                ">
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
                    <div class="interaction-bar">
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="testimonial_id" value="<?php echo $testimonial['id']; ?>">
                            <button type="submit" name="like_testimonial" class="like-btn <?php echo $testimonial['user_liked'] ? 'active' : ''; ?>">
                                <i class="fas fa-heart"></i> 
                                <span><?php echo $testimonial['like_count']; ?> Encouragements</span>
                            </button>
                        </form>
                        
                        <div class="comment-btn">
                            <i class="fas fa-comment"></i>
                            <span><?php echo $testimonial['comment_count']; ?> Comments</span>
                        </div>
                    </div>
                    
                    <!-- Enhanced Comments Section -->
                    <div class="comments-section">
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
                        
                        <!-- Enhanced Comment Input -->
                        <form method="POST" class="comment-form">
                            <input type="hidden" name="testimonial_id" value="<?php echo $testimonial['id']; ?>">
                            
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <div class="comment-input-container">
                                    <textarea name="comment_text" placeholder="Share your encouragement, thoughts, or celebration..." 
                                             rows="4" required></textarea>
                                    
                                    <div class="comment-input-actions">                                        
                                        <button type="submit" name="comment_testimonial" 
                                                class="btn btn-primary" 
                                                style="
                                                padding: 0.75rem 1.5rem;
                                                border-radius: 25px;
                                                font-weight: 700;
                                                display: flex;
                                                align-items: center;
                                                gap: 0.5rem;
                                                transition: all 0.3s ease;
                                                box-shadow: 0 4px 15px rgba(var(--accent-rgb), 0.3);
                                                ">
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
document.addEventListener('DOMContentLoaded', function() {
    // Character counter for comment textarea
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
                this.style.transform = 'translateX(8px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });
    });
    
    // Add loading animation to like buttons
    const likeButtons = document.querySelectorAll('.like-btn');
    likeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const icon = this.querySelector('i');
            icon.style.transform = 'scale(1.2)';
            setTimeout(() => {
                icon.style.transform = 'scale(1)';
            }, 300);
        });
    });
});
</script>

<?php require_once 'footer.php'; ?>