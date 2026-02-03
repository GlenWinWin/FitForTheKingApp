<?php
date_default_timezone_set("Asia/Hong_Kong");
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

// Get unique dates for filter dropdowns
$dates = array_column($photos, 'photo_date');
?>

<!-- Premium Background (Original) -->
<div class="premium-bg"></div>
<div class="particles-container" id="particles-container"></div>

<!-- Mobile Native Container -->
<div class="mobile-native-container">
    <!-- Sticky Mobile Header -->
    <div class="mobile-sticky-header">
        <div class="mobile-header-content">
            <button class="mobile-back-btn" onclick="window.location.href='dashboard.php'">
                <i class="fas fa-chevron-left"></i>
                <span>Dashboard</span>
            </button>
            <h1 class="mobile-header-title">Progress Photos</h1>
            <a href="progress_photos.php" class="mobile-add-btn">
                <i class="fas fa-plus"></i>
                <span class="mobile-hidden">Add</span>
            </a>
        </div>
    </div>

    <!-- Success Message -->
    <?php if ($success_message): ?>
    <div class="mobile-alert success">
        <i class="fas fa-check-circle"></i>
        <span><?php echo htmlspecialchars($success_message); ?></span>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="mobile-main-content">
        <?php if ($photos): ?>
            <!-- Only show comparison section if there are at least 2 dates -->
            <?php if (count($dates) >= 2): ?>
            <div class="mobile-card comparison-section">
                <div class="card-header-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <h2 class="mobile-card-title">Compare Progress</h2>
                <p class="mobile-card-subtitle">Select two dates to see your transformation</p>
                
                <!-- Date Selection exactly like screenshot -->
                <div class="date-selection-screenshot">
                    <?php 
                    // Show first two dates by default
                    for ($i = 0; $i < 2; $i++):
                        if (isset($dates[$i])):
                    ?>
                    <div class="date-item <?php echo $i === 0 ? 'selected' : ''; ?>" data-date="<?php echo $dates[$i]; ?>">
                        <div class="date-dot"></div>
                        <span class="date-label"><?php echo date('F j, Y', strtotime($dates[$i])); ?></span>
                    </div>
                    <?php 
                        else:
                    ?>
                    <div class="date-item <?php echo $i === 0 ? 'selected' : ''; ?>">
                        <div class="date-dot"></div>
                        <span class="date-label">Select date</span>
                    </div>
                    <?php 
                        endif;
                    endfor; 
                    ?>
                </div>
                
                <!-- Divider -->
                <div class="divider"></div>
                
                <!-- Preview Result Section exactly like screenshot -->
                <div class="preview-result-screenshot">
                    <h3>Preview Result</h3>
                    
                    <div class="preview-dates-screenshot">
                        <div class="preview-date-item">
                            <span class="preview-date-label" id="previewDate1">
                                <?php echo isset($dates[0]) ? date('F j, Y', strtotime($dates[0])) : 'Select date'; ?>
                            </span>
                        </div>
                        <div class="preview-date-item">
                            <span class="preview-date-label" id="previewDate2">
                                <?php echo isset($dates[1]) ? date('F j, Y', strtotime($dates[1])) : 'Select date'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Stats exactly like screenshot -->
                    <div class="stats-screenshot">
                        <div class="stat-row">
                            <span class="stat-label">Weight:</span>
                            <span class="stat-value" id="weightStat">- kg</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Fasting:</span>
                            <span class="stat-value" id="fastingStat">0 days</span>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons exactly like screenshot -->
                <div class="action-buttons-screenshot">
                    <button class="screenshot-btn primary" id="compareNowBtn">
                        <span>Compare Now</span>
                    </button>
                    <button class="screenshot-btn secondary" id="resetBtn">
                        <span>Reset</span>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Photos Timeline (Always show if there are photos) -->
            <div class="mobile-card photos-section">
                <div class="card-header-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h2 class="mobile-card-title">Photo History</h2>
                <p class="mobile-card-subtitle">Track your transformation journey</p>
                
                <!-- Only show view tabs if there are multiple photos -->
                <?php if (count($photos) > 0): ?>
                <div class="mobile-view-tabs">
                    <button class="view-tab-button active" data-view="front">
                        <i class="fas fa-user"></i>
                        <span>Front View</span>
                    </button>
                    <button class="view-tab-button" data-view="side">
                        <i class="fas fa-user-friends"></i>
                        <span>Side View</span>
                    </button>
                    <button class="view-tab-button" data-view="back">
                        <i class="fas fa-user-circle"></i>
                        <span>Back View</span>
                    </button>
                </div>
                
                <div class="photos-timeline">
                    <?php foreach ($photos as $photo): ?>
                    <div class="timeline-card" data-date="<?php echo $photo['photo_date']; ?>">
                        <div class="timeline-header">
                            <div class="date-info">
                                <i class="fas fa-calendar"></i>
                                <span class="date-text"><?php echo date('F j, Y', strtotime($photo['photo_date'])); ?></span>
                                <?php if ($photo['photo_date'] == date('Y-m-d')): ?>
                                    <span class="today-badge">Today</span>
                                <?php endif; ?>
                            </div>
                            <div class="days-ago">
                                <i class="fas fa-clock"></i>
                                <span><?php echo floor((time() - strtotime($photo['photo_date'])) / (60 * 60 * 24)); ?> days ago</span>
                            </div>
                        </div>
                        
                        <div class="photo-view-container">
                            <?php foreach (['front', 'side', 'back'] as $view): ?>
                            <div class="photo-view-pane <?php echo $view === 'front' ? 'active' : ''; ?>" data-view="<?php echo $view; ?>">
                                <div class="photo-view">
                                    <div class="view-label">
                                        <i class="fas fa-<?php echo $view === 'front' ? 'user' : ($view === 'side' ? 'user-friends' : 'user-circle'); ?>"></i>
                                        <?php echo ucfirst($view); ?> View
                                    </div>
                                    <div class="photo-container">
                                        <img src="<?php echo $photo[$view . '_photo'] ?: 'https://via.placeholder.com/300x350/1a237e/ffffff?text=' . ucfirst($view) . '+View'; ?>" 
                                             alt="<?php echo ucfirst($view); ?> View" 
                                             class="progress-photo"
                                             data-date="<?php echo date('F j, Y', strtotime($photo['photo_date'])); ?>"
                                             data-src="<?php echo $photo[$view . '_photo'] ?: 'https://via.placeholder.com/300x350/1a237e/ffffff?text=' . ucfirst($view) . '+View'; ?>">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($photo['notes']): ?>
                        <div class="photo-notes">
                            <div class="notes-header">
                                <i class="fas fa-sticky-note"></i>
                                <span>Notes</span>
                            </div>
                            <p class="notes-content"><?php echo nl2br(htmlspecialchars($photo['notes'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state-inline">
                    <div class="empty-icon-inline">
                        <i class="fas fa-images"></i>
                    </div>
                    <h4>No Photos Yet</h4>
                    <p>Add your first progress photos to start tracking</p>
                </div>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            <!-- Empty State - Show when there are NO photos at all -->
            <div class="mobile-card empty-state">
                <div class="empty-icon">
                    <i class="fas fa-camera"></i>
                </div>
                <h3>No Progress Photos Yet</h3>
                <p>Start tracking your transformation by uploading your first progress photos!</p>
                <div class="empty-actions">
                    <a href="progress_photos.php" class="mobile-btn primary large">
                        <i class="fas fa-camera"></i>
                        <span>Add First Photos</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Bottom Tab Navigation -->
    <nav class="mobile-bottom-nav">
        <a href="dashboard.php" class="nav-item">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="workouts.php" class="nav-item">
            <i class="fas fa-dumbbell"></i>
            <span>Workouts</span>
        </a>
        <a href="progress_photos.php" class="nav-item active">
            <i class="fas fa-camera"></i>
            <span>Photos</span>
        </a>
        <a href="measurements.php" class="nav-item">
            <i class="fas fa-ruler-combined"></i>
            <span>Measure</span>
        </a>
        <a href="profile.php" class="nav-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </nav>
</div>

<!-- Photo Modal (Original) -->
<div id="photoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalDate"></h3>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <img id="modalImage" src="" alt="Progress Photo">
        </div>
    </div>
</div>

<style>
/* Disable zoom for native feel */
.mobile-native-container {
    max-width: 100%;
    overflow-x: hidden;
    touch-action: pan-y;
    -webkit-text-size-adjust: 100%;
}

.mobile-main-content {
    padding-bottom: 80px; /* Space for bottom nav */
    min-height: 100vh;
}

/* Mobile Sticky Header */
.mobile-sticky-header {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: var(--card-bg);
    border-bottom: 1px solid var(--border);
    padding: 12px 16px;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.mobile-header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.mobile-back-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(var(--accent-rgb), 0.1);
    border: 1px solid rgba(var(--accent-rgb), 0.2);
    color: var(--accent);
    padding: 10px 16px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    min-height: 44px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.mobile-back-btn:active {
    background: rgba(var(--accent-rgb), 0.2);
    transform: scale(0.98);
}

.mobile-header-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
    margin: 0;
    flex: 1;
    text-align: center;
}

.mobile-add-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--accent);
    color: white;
    padding: 10px 16px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    min-height: 44px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.mobile-add-btn:active {
    opacity: 0.9;
    transform: scale(0.98);
}

/* Mobile Alert */
.mobile-alert {
    margin: 16px;
    padding: 12px 16px;
    background: rgba(76, 175, 80, 0.1);
    border-left: 4px solid #4CAF50;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #4CAF50;
    font-size: 14px;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Mobile Card */
.mobile-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 20px;
    margin: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.card-header-icon {
    width: 48px;
    height: 48px;
    background: var(--accent);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    margin-bottom: 16px;
}

.mobile-card-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
    margin: 0 0 8px 0;
    text-align: center;
}

.mobile-card-subtitle {
    font-size: 14px;
    color: var(--light-text);
    margin: 0 0 24px 0;
    text-align: center;
    line-height: 1.4;
}

/* EXACT SCREENSHOT STYLES */

/* Date Selection - like screenshot */
.date-selection-screenshot {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 24px;
    padding: 0 8px;
}

.date-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: var(--bg-color);
    border: 1px solid var(--border);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.date-item.selected {
    border-color: var(--accent);
    background: rgba(var(--accent-rgb), 0.05);
}

.date-dot {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid var(--border);
    position: relative;
}

.date-item.selected .date-dot {
    border-color: var(--accent);
    background: var(--accent);
}

.date-item.selected .date-dot::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 8px;
    height: 8px;
    background: white;
    border-radius: 50%;
}

.date-label {
    font-size: 15px;
    font-weight: 500;
    color: var(--text);
    flex: 1;
}

/* Divider */
.divider {
    height: 1px;
    background: var(--border);
    margin: 20px 0;
}

/* Preview Result Section - like screenshot */
.preview-result-screenshot {
    margin-bottom: 24px;
}

.preview-result-screenshot h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--text);
    margin: 0 0 20px 0;
    text-align: center;
}

.preview-dates-screenshot {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 20px;
}

.preview-date-item {
    display: flex;
    align-items: center;
    justify-content: center;
}

.preview-date-label {
    font-size: 14px;
    font-weight: 500;
    color: var(--text);
    padding: 12px 16px;
    background: var(--bg-color);
    border: 1px solid var(--border);
    border-radius: 8px;
    width: 100%;
    text-align: center;
    max-width: 280px;
}

/* Stats Section - like screenshot */
.stats-screenshot {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding-top: 20px;
    border-top: 1px solid var(--border);
}

.stat-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
}

.stat-label {
    font-size: 14px;
    color: var(--light-text);
    font-weight: 500;
}

.stat-value {
    font-size: 15px;
    font-weight: 600;
    color: var(--accent);
}

/* Action Buttons - like screenshot */
.action-buttons-screenshot {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

.screenshot-btn {
    flex: 1;
    height: 44px;
    border-radius: 10px;
    border: none;
    font-size: 15px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    min-height: 44px;
}

.screenshot-btn:active {
    transform: scale(0.98);
}

.screenshot-btn.primary {
    background: var(--accent);
    color: white;
    border: none;
}

.screenshot-btn.primary:active {
    opacity: 0.9;
}

.screenshot-btn.secondary {
    background: transparent;
    color: var(--accent);
    border: 1.5px solid var(--accent);
}

.screenshot-btn.secondary:active {
    background: rgba(var(--accent-rgb), 0.1);
}

/* Mobile View Tabs */
.mobile-view-tabs {
    display: flex;
    background: var(--bg-color);
    border-radius: 12px;
    padding: 4px;
    border: 1px solid var(--border);
    margin-bottom: 20px;
}

.view-tab-button {
    flex: 1;
    height: 44px;
    border: none;
    background: transparent;
    color: var(--light-text);
    font-size: 14px;
    font-weight: 500;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    min-height: 44px;
}

.view-tab-button.active {
    background: var(--card-bg);
    color: var(--accent);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.view-tab-button:active {
    background: rgba(var(--accent-rgb), 0.1);
}

/* Photos Timeline */
.photos-timeline {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.timeline-card {
    background: var(--bg-color);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 16px;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 8px;
}

.date-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    font-weight: 600;
    color: var(--text);
}

.date-text {
    font-size: 15px;
    font-weight: 600;
}

.today-badge {
    background: var(--accent);
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.days-ago {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--light-text);
}

/* Photo View Container */
.photo-view-container {
    position: relative;
    min-height: 300px;
}

.photo-view-pane {
    display: none;
}

.photo-view-pane.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.photo-view {
    text-align: center;
}

.view-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: var(--accent);
    font-weight: 600;
    margin-bottom: 16px;
    font-size: 14px;
}

.photo-container {
    width: 100%;
    max-width: 280px;
    margin: 0 auto;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.progress-photo {
    width: 100%;
    height: auto;
    display: block;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.progress-photo:active {
    transform: scale(0.98);
}

/* Photo Notes */
.photo-notes {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--border);
}

.notes-header {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text);
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 14px;
}

.notes-content {
    color: var(--light-text);
    font-size: 14px;
    line-height: 1.5;
    margin: 0;
}

/* Empty State Inline */
.empty-state-inline {
    text-align: center;
    padding: 40px 20px;
}

.empty-icon-inline {
    font-size: 48px;
    color: var(--light-text);
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state-inline h4 {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 8px;
}

.empty-state-inline p {
    font-size: 14px;
    color: var(--light-text);
    margin-bottom: 0;
}

/* Bottom Navigation */
.mobile-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--card-bg);
    border-top: 1px solid var(--border);
    padding: 8px 16px;
    display: flex;
    justify-content: space-around;
    z-index: 1000;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 12px;
    transition: all 0.2s ease;
    min-height: 44px;
    justify-content: center;
}

.nav-item i {
    font-size: 20px;
    color: var(--light-text);
    margin-bottom: 4px;
    transition: color 0.2s ease;
}

.nav-item span {
    font-size: 11px;
    color: var(--light-text);
    font-weight: 500;
    transition: color 0.2s ease;
}

.nav-item.active i,
.nav-item.active span {
    color: var(--accent);
}

.nav-item:active {
    background: rgba(var(--accent-rgb), 0.1);
}

/* Empty State (Full) */
.empty-state {
    text-align: center;
    padding: 40px 20px;
}

.empty-icon {
    font-size: 64px;
    color: var(--light-text);
    margin-bottom: 24px;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 20px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 12px;
}

.empty-state p {
    font-size: 15px;
    color: var(--light-text);
    margin-bottom: 32px;
}

.empty-actions {
    max-width: 280px;
    margin: 0 auto;
}

/* Modal (Keep Original) */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.9);
    animation: fadeIn 0.3s ease;
}

.modal-content {
    position: relative;
    margin: 2% auto;
    width: 90%;
    max-width: 800px;
    background: var(--card-bg);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(0,0,0,0.3);
    animation: slideIn 0.3s ease;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: var(--bg-color);
    border-bottom: 1px solid var(--border);
}

.modal-header h3 {
    margin: 0;
    color: var(--text);
    font-size: 1.25rem;
}

.close-modal {
    font-size: 2rem;
    color: var(--light-text);
    cursor: pointer;
    transition: color 0.3s ease;
    line-height: 1;
}

.close-modal:hover {
    color: var(--accent);
}

.modal-body {
    padding: 2rem;
    text-align: center;
}

.modal-body img {
    max-width: 100%;
    max-height: 70vh;
    border-radius: 8px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}

/* Mobile Responsive */
@media (max-width: 480px) {
    .mobile-sticky-header {
        padding: 10px 12px;
    }
    
    .mobile-header-title {
        font-size: 16px;
    }
    
    .mobile-back-btn,
    .mobile-add-btn {
        padding: 8px 12px;
        font-size: 13px;
    }
    
    .mobile-back-btn span,
    .mobile-add-btn .mobile-hidden {
        display: none;
    }
    
    .mobile-card {
        margin: 12px;
        padding: 16px;
    }
    
    .mobile-card-title {
        font-size: 16px;
    }
    
    .mobile-card-subtitle {
        font-size: 13px;
    }
    
    .date-label,
    .preview-date-label {
        font-size: 14px;
    }
    
    .action-buttons-screenshot {
        flex-direction: column;
    }
    
    .mobile-view-tabs {
        flex-wrap: wrap;
    }
    
    .view-tab-button {
        flex: 1 0 calc(33.333% - 8px);
        min-width: 0;
        font-size: 12px;
        padding: 8px 4px;
    }
    
    .photo-container {
        max-width: 100%;
    }
    
    .mobile-bottom-nav {
        padding: 8px;
    }
    
    .nav-item {
        padding: 8px;
        min-width: 56px;
    }
    
    .nav-item span {
        font-size: 10px;
    }
    
    .modal-content {
        margin: 5% auto;
        width: 95%;
    }
    
    .modal-body {
        padding: 1rem;
    }
    
    .modal-header {
        padding: 1rem;
    }
    
    .modal-header h3 {
        font-size: 1.1rem;
    }
}

/* Prevent zoom */
@media (max-width: 480px) {
    input, select, textarea {
        font-size: 16px !important;
    }
}

/* Touch feedback */
@media (hover: none) and (pointer: coarse) {
    .screenshot-btn,
    .view-tab-button,
    .nav-item,
    .mobile-back-btn,
    .mobile-add-btn,
    .date-item {
        min-height: 44px;
    }
    
    .progress-photo {
        cursor: default;
    }
    
    /* Remove hover effects */
    .screenshot-btn:hover,
    .view-tab-button:hover,
    .nav-item:hover,
    .date-item:hover {
        transform: none;
    }
    
    .screenshot-btn:active,
    .view-tab-button:active,
    .nav-item:active,
    .date-item:active {
        transform: scale(0.98);
    }
}

/* Disable zoom */
html {
    touch-action: pan-y;
    -webkit-text-size-adjust: 100%;
}

body {
    overflow-x: hidden;
}

/* Keep original particles and premium bg */
.particles-container,
.premium-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
}
</style>

<script>
// Prevent zoom and double-tap
document.addEventListener('touchstart', function(e) {
    if (e.touches.length > 1) {
        e.preventDefault();
    }
}, { passive: false });

document.addEventListener('gesturestart', function(e) {
    e.preventDefault();
});

document.addEventListener('dblclick', function(e) {
    e.preventDefault();
}, { passive: false });

// Mobile Native Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Date selection for comparison (screenshot style)
    const dateItems = document.querySelectorAll('.date-item');
    dateItems.forEach(item => {
        item.addEventListener('click', function() {
            // Don't select if it says "Select date"
            if (this.querySelector('.date-label').textContent === 'Select date') {
                return;
            }
            
            // Remove selected from all
            dateItems.forEach(item => {
                item.classList.remove('selected');
            });
            
            // Add selected to clicked
            this.classList.add('selected');
            
            // Update preview with selected date
            updatePreview();
        });
    });
    
    // Compare Now button
    const compareBtn = document.getElementById('compareNowBtn');
    if (compareBtn) {
        compareBtn.addEventListener('click', function() {
            const selectedDates = document.querySelectorAll('.date-item.selected');
            const dateLabels = Array.from(selectedDates).map(item => 
                item.querySelector('.date-label').textContent
            );
            
            // Filter out "Select date" entries
            const validDates = dateLabels.filter(label => label !== 'Select date');
            
            if (validDates.length < 2) {
                alert('Please select two valid dates to compare');
                return;
            }
            
            // Show comparison (simulate for now)
            alert(`Comparing dates:\n${validDates[0]} vs ${validDates[1]}`);
            // In real app: window.location.href = `compare.php?date1=${validDates[0]}&date2=${validDates[1]}`;
        });
    }
    
    // Reset button
    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            // Reset to default selection (first two dates)
            dateItems.forEach((item, index) => {
                const label = item.querySelector('.date-label');
                // Only select if it's not "Select date"
                if (label.textContent !== 'Select date' && index < 2) {
                    item.classList.add('selected');
                } else {
                    item.classList.remove('selected');
                }
            });
            
            // Reset preview
            updatePreview();
        });
    }
    
    // Function to update preview based on selected dates
    function updatePreview() {
        const selectedItems = document.querySelectorAll('.date-item.selected');
        const selectedDates = Array.from(selectedItems).map(item => 
            item.querySelector('.date-label').textContent
        );
        
        // Update preview dates
        const previewDate1 = document.getElementById('previewDate1');
        const previewDate2 = document.getElementById('previewDate2');
        const weightStat = document.getElementById('weightStat');
        const fastingStat = document.getElementById('fastingStat');
        
        if (selectedDates.length >= 2) {
            previewDate1.textContent = selectedDates[0];
            previewDate2.textContent = selectedDates[1];
            
            // Update stats based on selection (in real app, fetch from server)
            if (selectedDates[0] !== 'Select date' && selectedDates[1] !== 'Select date') {
                weightStat.textContent = '-1.2 kg';
                fastingStat.textContent = '5 days';
            } else {
                weightStat.textContent = '- kg';
                fastingStat.textContent = '0 days';
            }
        }
    }
    
    // View tabs for photo timeline
    const timelineTabs = document.querySelectorAll('.mobile-view-tabs .view-tab-button');
    if (timelineTabs.length > 0) {
        timelineTabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                const view = this.dataset.view;
                const container = this.closest('.mobile-card');
                
                // Update active tab in this container
                container.querySelectorAll('.view-tab-button').forEach(t => {
                    t.classList.remove('active');
                });
                this.classList.add('active');
                
                // Update photo views in this timeline card
                container.querySelectorAll('.photo-view-pane').forEach(pane => {
                    pane.classList.remove('active');
                    if (pane.dataset.view === view) {
                        pane.classList.add('active');
                    }
                });
            });
        });
    }
    
    // Photo click handlers
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('progress-photo')) {
            const src = e.target.dataset.src;
            const date = e.target.dataset.date;
            
            document.getElementById('modalImage').src = src;
            document.getElementById('modalDate').textContent = date;
            document.getElementById('photoModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    });
    
    // Close modal
    document.querySelector('.close-modal').addEventListener('click', function() {
        document.getElementById('photoModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    });
    
    document.getElementById('photoModal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
    
    // Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('photoModal').style.display === 'block') {
            document.getElementById('photoModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
    
    // Simulate haptic feedback
    function hapticFeedback() {
        if ('vibrate' in navigator) {
            navigator.vibrate(10);
        }
    }
    
    // Add haptic to interactive elements
    const interactiveElements = document.querySelectorAll('.screenshot-btn, .view-tab-button, .nav-item, .mobile-back-btn, .mobile-add-btn, .date-item');
    interactiveElements.forEach(el => {
        el.addEventListener('touchstart', hapticFeedback);
    });
});
</script>

<?php require_once 'footer.php'; ?>