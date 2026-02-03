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
        <!-- Comparison Section - Simplified -->
        <div class="mobile-card comparison-section">
            <!-- Date Selection -->
            <div class="date-selection-simple">
                <div class="date-item" onclick="openDatePicker('start')">
                    <div class="date-label">Start date</div>
                    <div class="date-value" id="startDateValue">
                        <span><?php echo !empty($dates[1]) ? date('F j, Y', strtotime($dates[1])) : 'Select date'; ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <input type="hidden" id="startDateHidden" value="<?php echo !empty($dates[1]) ? $dates[1] : ''; ?>">
                </div>
                
                <div class="date-item" onclick="openDatePicker('end')">
                    <div class="date-label">End date</div>
                    <div class="date-value" id="endDateValue">
                        <span><?php echo !empty($dates[0]) ? date('F j, Y', strtotime($dates[0])) : 'Select date'; ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <input type="hidden" id="endDateHidden" value="<?php echo !empty($dates[0]) ? $dates[0] : ''; ?>">
                </div>
            </div>
            
            <!-- Comparison Photos Section -->
            <div id="comparisonContainer">
                <?php if (count($dates) >= 2): ?>
                    <!-- Show comparison by default if we have 2+ dates -->
                    <div class="comparison-view-tabs">
                        <button class="comparison-tab-button active" data-view="front">
                            <i class="fas fa-user"></i>
                            <span>Front</span>
                        </button>
                        <button class="comparison-tab-button" data-view="side">
                            <i class="fas fa-user-friends"></i>
                            <span>Side</span>
                        </button>
                        <button class="comparison-tab-button" data-view="back">
                            <i class="fas fa-user-circle"></i>
                            <span>Back</span>
                        </button>
                    </div>
                    
                    <!-- Front View (Default) -->
                    <div class="comparison-view active" data-view="front">
                        <div class="comparison-row">
                            <div class="comparison-column">
                                <div class="photo-date" id="comparisonStartDate">
                                    <?php echo !empty($dates[1]) ? date('F j, Y', strtotime($dates[1])) : 'Select date'; ?>
                                </div>
                                <div class="photo-wrapper">
                                    <img id="startFrontPhoto" 
                                         src="<?php echo !empty($photos[1]['front_photo']) ? $photos[1]['front_photo'] : 'https://via.placeholder.com/300x400/1a237e/ffffff?text=Front+View'; ?>" 
                                         alt="Start Front View" 
                                         class="comparison-photo">
                                </div>
                            </div>
                            <div class="comparison-column">
                                <div class="photo-date" id="comparisonEndDate">
                                    <?php echo !empty($dates[0]) ? date('F j, Y', strtotime($dates[0])) : 'Select date'; ?>
                                </div>
                                <div class="photo-wrapper">
                                    <img id="endFrontPhoto" 
                                         src="<?php echo !empty($photos[0]['front_photo']) ? $photos[0]['front_photo'] : 'https://via.placeholder.com/300x400/1a237e/ffffff?text=Front+View'; ?>" 
                                         alt="End Front View" 
                                         class="comparison-photo">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Side View -->
                    <div class="comparison-view" data-view="side">
                        <div class="comparison-row">
                            <div class="comparison-column">
                                <div class="photo-date">
                                    <?php echo !empty($dates[1]) ? date('F j, Y', strtotime($dates[1])) : 'Select date'; ?>
                                </div>
                                <div class="photo-wrapper">
                                    <img id="startSidePhoto" 
                                         src="<?php echo !empty($photos[1]['side_photo']) ? $photos[1]['side_photo'] : 'https://via.placeholder.com/300x400/1a237e/ffffff?text=Side+View'; ?>" 
                                         alt="Start Side View" 
                                         class="comparison-photo">
                                </div>
                            </div>
                            <div class="comparison-column">
                                <div class="photo-date">
                                    <?php echo !empty($dates[0]) ? date('F j, Y', strtotime($dates[0])) : 'Select date'; ?>
                                </div>
                                <div class="photo-wrapper">
                                    <img id="endSidePhoto" 
                                         src="<?php echo !empty($photos[0]['side_photo']) ? $photos[0]['side_photo'] : 'https://via.placeholder.com/300x400/1a237e/ffffff?text=Side+View'; ?>" 
                                         alt="End Side View" 
                                         class="comparison-photo">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Back View -->
                    <div class="comparison-view" data-view="back">
                        <div class="comparison-row">
                            <div class="comparison-column">
                                <div class="photo-date">
                                    <?php echo !empty($dates[1]) ? date('F j, Y', strtotime($dates[1])) : 'Select date'; ?>
                                </div>
                                <div class="photo-wrapper">
                                    <img id="startBackPhoto" 
                                         src="<?php echo !empty($photos[1]['back_photo']) ? $photos[1]['back_photo'] : 'https://via.placeholder.com/300x400/1a237e/ffffff?text=Back+View'; ?>" 
                                         alt="Start Back View" 
                                         class="comparison-photo">
                                </div>
                            </div>
                            <div class="comparison-column">
                                <div class="photo-date">
                                    <?php echo !empty($dates[0]) ? date('F j, Y', strtotime($dates[0])) : 'Select date'; ?>
                                </div>
                                <div class="photo-wrapper">
                                    <img id="endBackPhoto" 
                                         src="<?php echo !empty($photos[0]['back_photo']) ? $photos[0]['back_photo'] : 'https://via.placeholder.com/300x400/1a237e/ffffff?text=Back+View'; ?>" 
                                         alt="End Back View" 
                                         class="comparison-photo">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons-simple">
                        <button class="simple-btn primary" id="compareNowBtn">
                            <span>Compare Now</span>
                        </button>
                        <button class="simple-btn secondary" id="resetBtn">
                            <span>Reset</span>
                        </button>
                    </div>
                <?php else: ?>
                    <!-- Empty state -->
                    <div class="empty-comparison">
                        <div class="empty-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <h3>No Photos to Compare</h3>
                        <p>Add at least 2 progress photos to start comparing</p>
                        <a href="progress_photos.php" class="simple-btn primary">
                            <i class="fas fa-camera"></i>
                            <span>Add Photos</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Photo History Section -->
        <?php if ($photos): ?>
        <div class="mobile-card photos-section">
            <div class="section-header">
                <h2 class="section-title">Photo History</h2>
                <p class="section-subtitle">Track your transformation journey</p>
            </div>
            
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
                    </div>
                    
                    <div class="photo-view-container">
                        <?php foreach (['front', 'side', 'back'] as $view): ?>
                        <div class="photo-view-pane <?php echo $view === 'front' ? 'active' : ''; ?>" data-view="<?php echo $view; ?>">
                            <div class="photo-view">
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

<!-- Date Picker Modal -->
<div id="datePickerModal" class="date-picker-modal">
    <div class="date-picker-content">
        <div class="date-picker-header">
            <h3 id="datePickerTitle">Select Date</h3>
            <span class="close-date-picker">&times;</span>
        </div>
        <div class="date-picker-body">
            <div class="available-dates-list">
                <?php if ($dates && count($dates) > 0): ?>
                    <?php foreach ($dates as $date): ?>
                    <div class="available-date" data-date="<?php echo $date; ?>">
                        <i class="fas fa-calendar"></i>
                        <span class="date-text"><?php echo date('F j, Y', strtotime($date)); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-dates-message">
                        <i class="fas fa-calendar-times"></i>
                        <p>No dates available</p>
                        <p class="sub-message">Add photos first</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Photo Modal -->
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

/* Simplified Date Selection */
.date-selection-simple {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 20px;
}

.date-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.date-label {
    font-size: 13px;
    color: var(--light-text);
    font-weight: 500;
    padding-left: 4px;
}

.date-value {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: var(--bg-color);
    border: 1px solid var(--border);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    min-height: 44px;
}

.date-value:active {
    background: rgba(var(--accent-rgb), 0.05);
    border-color: var(--accent);
}

.date-value span {
    font-size: 15px;
    font-weight: 500;
    color: var(--text);
}

.date-value i {
    color: var(--light-text);
    font-size: 14px;
}

/* Comparison View Tabs */
.comparison-view-tabs {
    display: flex;
    background: var(--bg-color);
    border-radius: 10px;
    padding: 4px;
    border: 1px solid var(--border);
    margin-bottom: 20px;
}

.comparison-tab-button {
    flex: 1;
    height: 40px;
    border: none;
    background: transparent;
    color: var(--light-text);
    font-size: 13px;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    min-height: 40px;
}

.comparison-tab-button.active {
    background: var(--card-bg);
    color: var(--accent);
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.comparison-tab-button:active {
    background: rgba(var(--accent-rgb), 0.1);
}

/* Comparison Views */
.comparison-view {
    display: none;
}

.comparison-view.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Comparison Layout */
.comparison-row {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
}

.comparison-column {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.photo-date {
    font-size: 14px;
    font-weight: 500;
    color: var(--text);
    text-align: center;
    padding: 8px 12px;
    background: var(--bg-color);
    border: 1px solid var(--border);
    border-radius: 8px;
    width: 100%;
}

.photo-wrapper {
    width: 100%;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.comparison-photo {
    width: 100%;
    height: auto;
    display: block;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.comparison-photo:active {
    transform: scale(0.98);
}

/* Action Buttons */
.action-buttons-simple {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

.simple-btn {
    flex: 1;
    height: 44px;
    border-radius: 10px;
    border: none;
    font-size: 15px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    min-height: 44px;
    text-decoration: none;
}

.simple-btn:active {
    transform: scale(0.98);
}

.simple-btn.primary {
    background: var(--accent);
    color: white;
    border: none;
}

.simple-btn.primary:active {
    opacity: 0.9;
}

.simple-btn.secondary {
    background: transparent;
    color: var(--accent);
    border: 1.5px solid var(--accent);
}

.simple-btn.secondary:active {
    background: rgba(var(--accent-rgb), 0.1);
}

/* Empty Comparison State */
.empty-comparison {
    text-align: center;
    padding: 40px 20px;
}

.empty-comparison .empty-icon {
    font-size: 64px;
    color: var(--light-text);
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-comparison h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 8px;
}

.empty-comparison p {
    font-size: 14px;
    color: var(--light-text);
    margin-bottom: 24px;
}

/* Section Header */
.section-header {
    margin-bottom: 20px;
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
    margin: 0 0 4px 0;
}

.section-subtitle {
    font-size: 14px;
    color: var(--light-text);
    margin: 0;
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

.photo-view {
    text-align: center;
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

/* Date Picker Modal */
.date-picker-modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    animation: fadeIn 0.3s ease;
}

.date-picker-content {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--card-bg);
    border-radius: 20px 20px 0 0;
    padding: 20px;
    max-height: 80vh;
    overflow-y: auto;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}

.date-picker-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border);
}

.date-picker-header h3 {
    margin: 0;
    color: var(--text);
    font-size: 18px;
    font-weight: 600;
}

.close-date-picker {
    font-size: 24px;
    color: var(--light-text);
    cursor: pointer;
    line-height: 1;
}

.date-picker-body {
    padding: 10px 0;
}

.available-dates-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.available-date {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: var(--bg-color);
    border: 1px solid var(--border);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.available-date:active {
    background: rgba(var(--accent-rgb), 0.1);
    border-color: var(--accent);
}

.available-date i {
    color: var(--accent);
    font-size: 16px;
    width: 24px;
}

.available-date .date-text {
    font-size: 15px;
    font-weight: 500;
    color: var(--text);
    flex: 1;
}

.no-dates-message {
    text-align: center;
    padding: 40px 20px;
}

.no-dates-message i {
    font-size: 48px;
    color: var(--light-text);
    margin-bottom: 16px;
    opacity: 0.5;
}

.no-dates-message p {
    color: var(--light-text);
    margin: 0;
    font-size: 14px;
}

.no-dates-message .sub-message {
    font-size: 13px;
    margin-top: 4px;
    opacity: 0.8;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 2001;
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
    
    .comparison-view-tabs,
    .mobile-view-tabs {
        flex-wrap: wrap;
    }
    
    .comparison-tab-button {
        flex: 1 0 calc(33.333% - 8px);
        min-width: 0;
        font-size: 12px;
        padding: 6px 4px;
    }
    
    .view-tab-button {
        flex: 1 0 calc(33.333% - 8px);
        min-width: 0;
        font-size: 12px;
        padding: 8px 4px;
    }
    
    .action-buttons-simple {
        flex-direction: column;
    }
    
    .comparison-row {
        flex-direction: column;
        gap: 16px;
    }
    
    .comparison-column {
        width: 100%;
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
}

/* Touch feedback */
@media (hover: none) and (pointer: coarse) {
    .simple-btn,
    .comparison-tab-button,
    .view-tab-button,
    .nav-item,
    .mobile-back-btn,
    .mobile-add-btn,
    .date-value,
    .available-date {
        min-height: 44px;
    }
    
    .progress-photo,
    .comparison-photo {
        cursor: default;
    }
    
    /* Remove hover effects */
    .simple-btn:hover,
    .comparison-tab-button:hover,
    .view-tab-button:hover,
    .nav-item:hover,
    .date-value:hover,
    .available-date:hover {
        transform: none;
    }
    
    .simple-btn:active,
    .comparison-tab-button:active,
    .view-tab-button:active,
    .nav-item:active,
    .date-value:active,
    .available-date:active {
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
    let currentDateType = ''; // 'start' or 'end'
    let allPhotos = <?php echo json_encode($photos); ?>;
    
    // Initialize with default dates
    let selectedStartDate = document.getElementById('startDateHidden').value;
    let selectedEndDate = document.getElementById('endDateHidden').value;
    
    // Open date picker
    window.openDatePicker = function(type) {
        currentDateType = type;
        const modal = document.getElementById('datePickerModal');
        const title = document.getElementById('datePickerTitle');
        
        if (type === 'start') {
            title.textContent = 'Select Start Date';
        } else {
            title.textContent = 'Select End Date';
        }
        
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    };
    
    // Close date picker
    document.querySelector('.close-date-picker').addEventListener('click', function() {
        document.getElementById('datePickerModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    });
    
    document.getElementById('datePickerModal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
    
    // Handle date selection
    document.querySelectorAll('.available-date').forEach(dateItem => {
        dateItem.addEventListener('click', function() {
            const date = this.getAttribute('data-date');
            const dateText = this.querySelector('.date-text').textContent;
            
            if (currentDateType === 'start') {
                selectedStartDate = date;
                document.getElementById('startDateValue').innerHTML = `<span>${dateText}</span><i class="fas fa-chevron-down"></i>`;
                document.getElementById('startDateHidden').value = date;
                
                // Update comparison start date
                document.querySelectorAll('#comparisonStartDate, .comparison-view .photo-date:first-child').forEach(el => {
                    el.textContent = dateText;
                });
            } else {
                selectedEndDate = date;
                document.getElementById('endDateValue').innerHTML = `<span>${dateText}</span><i class="fas fa-chevron-down"></i>`;
                document.getElementById('endDateHidden').value = date;
                
                // Update comparison end date
                document.querySelectorAll('#comparisonEndDate, .comparison-view .photo-date:nth-child(2)').forEach(el => {
                    el.textContent = dateText;
                });
            }
            
            // Close modal
            document.getElementById('datePickerModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Update photos if both dates are selected
            if (selectedStartDate && selectedEndDate) {
                updateComparisonPhotos();
            }
        });
    });
    
    // Compare Now button - FIXED: Should work with whatever dates are selected (even defaults)
    const compareBtn = document.getElementById('compareNowBtn');
    if (compareBtn) {
        compareBtn.addEventListener('click', function() {
            // Get current dates from hidden inputs
            const currentStartDate = document.getElementById('startDateHidden').value;
            const currentEndDate = document.getElementById('endDateHidden').value;
            
            // Check if we have valid dates
            if (!currentStartDate || !currentEndDate) {
                showToast('Please select both dates', 'error');
                return;
            }
            
            if (currentStartDate === currentEndDate) {
                showToast('Please select different dates', 'error');
                return;
            }
            
            // Update the variables
            selectedStartDate = currentStartDate;
            selectedEndDate = currentEndDate;
            
            updateComparisonPhotos();
            showToast('Comparison updated!', 'success');
        });
    }
    
    // Reset button
    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            // Reset to default dates from PHP
            const defaultStartDate = <?php echo !empty($dates[1]) ? "'{$dates[1]}'" : 'null'; ?>;
            const defaultEndDate = <?php echo !empty($dates[0]) ? "'{$dates[0]}'" : 'null'; ?>;
            
            if (defaultStartDate && defaultEndDate) {
                selectedStartDate = defaultStartDate;
                selectedEndDate = defaultEndDate;
                
                const startText = new Date(defaultStartDate).toLocaleDateString('en-US', { 
                    month: 'long', 
                    day: 'numeric',
                    year: 'numeric'
                });
                const endText = new Date(defaultEndDate).toLocaleDateString('en-US', { 
                    month: 'long', 
                    day: 'numeric',
                    year: 'numeric'
                });
                
                // Update display
                document.getElementById('startDateValue').innerHTML = `<span>${startText}</span><i class="fas fa-chevron-down"></i>`;
                document.getElementById('endDateValue').innerHTML = `<span>${endText}</span><i class="fas fa-chevron-down"></i>`;
                
                // Update hidden inputs
                document.getElementById('startDateHidden').value = defaultStartDate;
                document.getElementById('endDateHidden').value = defaultEndDate;
                
                // Update date displays in comparison
                document.querySelectorAll('#comparisonStartDate, .comparison-view .photo-date:first-child').forEach(el => {
                    el.textContent = startText;
                });
                document.querySelectorAll('#comparisonEndDate, .comparison-view .photo-date:nth-child(2)').forEach(el => {
                    el.textContent = endText;
                });
                
                updateComparisonPhotos();
                showToast('Comparison reset to default dates', 'info');
            } else {
                showToast('No dates available to reset to', 'error');
            }
        });
    }
    
    // Update comparison photos
    function updateComparisonPhotos() {
        // Find the selected photos
        const startPhoto = allPhotos.find(photo => photo.photo_date === selectedStartDate);
        const endPhoto = allPhotos.find(photo => photo.photo_date === selectedEndDate);
        
        // Update photos for each view
        const views = ['front', 'side', 'back'];
        views.forEach(view => {
            const startImg = document.getElementById(`start${capitalizeFirst(view)}Photo`);
            const endImg = document.getElementById(`end${capitalizeFirst(view)}Photo`);
            
            if (startPhoto && startPhoto[view + '_photo']) {
                startImg.src = startPhoto[view + '_photo'];
                startImg.alt = `Start ${view} View`;
            } else {
                startImg.src = `https://via.placeholder.com/300x400/1a237e/ffffff?text=Start+${capitalizeFirst(view)}+View`;
                startImg.alt = `Start ${view} View`;
            }
            
            if (endPhoto && endPhoto[view + '_photo']) {
                endImg.src = endPhoto[view + '_photo'];
                endImg.alt = `End ${view} View`;
            } else {
                endImg.src = `https://via.placeholder.com/300x400/1a237e/ffffff?text=End+${capitalizeFirst(view)}+View`;
                endImg.alt = `End ${view} View`;
            }
        });
        
        // Scroll to comparison section
        document.querySelector('.comparison-section').scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });
    }
    
    // Helper function to capitalize first letter
    function capitalizeFirst(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
    
    // Comparison view tabs
    const comparisonTabs = document.querySelectorAll('.comparison-tab-button');
    const comparisonViews = document.querySelectorAll('.comparison-view');
    
    comparisonTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const view = this.dataset.view;
            
            // Update active tab
            comparisonTabs.forEach(t => {
                t.classList.remove('active');
            });
            this.classList.add('active');
            
            // Update active view
            comparisonViews.forEach(v => {
                v.classList.remove('active');
                if (v.dataset.view === view) {
                    v.classList.add('active');
                }
            });
        });
    });
    
    // Photo timeline view tabs
    const timelineTabs = document.querySelectorAll('.mobile-view-tabs .view-tab-button');
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
    
    // Photo click handlers
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('progress-photo') || e.target.classList.contains('comparison-photo')) {
            const src = e.target.src;
            const alt = e.target.alt;
            
            document.getElementById('modalImage').src = src;
            document.getElementById('modalDate').textContent = alt;
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
        if (e.key === 'Escape') {
            if (document.getElementById('datePickerModal').style.display === 'block') {
                document.getElementById('datePickerModal').style.display = 'none';
                document.body.style.overflow = 'auto';
            } else if (document.getElementById('photoModal').style.display === 'block') {
                document.getElementById('photoModal').style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
    });
    
    // Toast notification
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.innerHTML = `
            <div class="toast-content ${type}">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Style
        toast.style.cssText = `
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2002;
            animation: toastIn 0.3s ease;
            max-width: 90%;
        `;
        
        const toastContent = toast.querySelector('.toast-content');
        toastContent.style.cssText = `
            background: ${type === 'success' ? 'rgba(76, 175, 80, 0.95)' : 
                         type === 'error' ? 'rgba(244, 67, 54, 0.95)' : 
                         'rgba(33, 150, 243, 0.95)'};
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        `;
        
        // Add animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes toastIn {
                from { opacity: 0; transform: translate(-50%, -20px); }
                to { opacity: 1; transform: translate(-50%, 0); }
            }
            @keyframes toastOut {
                from { opacity: 1; transform: translate(-50%, 0); }
                to { opacity: 0; transform: translate(-50%, -20px); }
            }
        `;
        document.head.appendChild(style);
        
        // Remove after delay
        setTimeout(() => {
            toast.style.animation = 'toastOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // Simulate haptic feedback
    function hapticFeedback() {
        if ('vibrate' in navigator) {
            navigator.vibrate(10);
        }
    }
    
    // Add haptic to interactive elements
    const interactiveElements = document.querySelectorAll('.simple-btn, .comparison-tab-button, .view-tab-button, .nav-item, .mobile-back-btn, .mobile-add-btn, .date-value, .available-date');
    interactiveElements.forEach(el => {
        el.addEventListener('touchstart', hapticFeedback);
    });
});
</script>

<?php require_once 'footer.php'; ?>