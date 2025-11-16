<?php
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

<!-- Premium Background -->
<div class="premium-bg"></div>
<div class="particles-container" id="particles-container"></div>

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

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="dashboard.php" class="btn btn-outline btn-sm" style="display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-arrow-left"></i>
                <span class="mobile-hidden">Dashboard</span>
            </a>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <h1 class="card-title">Progress Photos History</h1>
                <a href="progress_photos.php" class="btn btn-primary btn-sm" style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-camera"></i>
                    <span class="mobile-hidden">Add Photos</span>
                </a>
            </div>
        </div>
    </div>
    <p style="color: var(--light-text); margin-top: 0.5rem;">
        Track your transformation journey through weekly progress photos.
    </p>
</div>

<?php if ($success_message): ?>
<div class="card">
    <div class="message success">
        <?php echo htmlspecialchars($success_message); ?>
    </div>
</div>
<?php endif; ?>

<!-- Comparison Filter -->
<?php if (count($dates) >= 2): ?>
<div class="card comparison-card">
    <div class="comparison-header">
        <div class="comparison-icon">
            <i class="fas fa-exchange-alt"></i>
        </div>
        <div>
            <h3 class="comparison-title">Compare Your Progress</h3>
            <p class="comparison-subtitle">Select two dates to see your transformation</p>
        </div>
    </div>
    
    <div class="comparison-form-container">
        <form id="compareForm" class="comparison-form">
            <div class="date-selector-group">
                <div class="date-selector start-date">
                    <div class="date-header">
                        <div class="date-icon">
                            <i class="fas fa-flag"></i>
                        </div>
                        <div>
                            <label class="date-label">Start Date</label>
                            <div class="date-subtitle">Beginning of journey</div>
                        </div>
                    </div>
                    <select name="start_date" class="date-select">
                        <option value="">Choose start date</option>
                        <?php foreach ($dates as $date): ?>
                            <option value="<?php echo $date; ?>"><?php echo date('F j, Y', strtotime($date)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="comparison-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
                
                <div class="date-selector end-date">
                    <div class="date-header">
                        <div class="date-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div>
                            <label class="date-label">End Date</label>
                            <div class="date-subtitle">Current progress</div>
                        </div>
                    </div>
                    <select name="end_date" class="date-select">
                        <option value="">Choose end date</option>
                        <?php foreach ($dates as $date): ?>
                            <option value="<?php echo $date; ?>"><?php echo date('F j, Y', strtotime($date)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="comparison-actions">
                <button type="button" id="compareBtn" class="btn btn-primary comparison-btn">
                    <i class="fas fa-chart-line"></i>
                    <span>Compare Progress</span>
                </button>
                <button type="button" id="resetCompare" class="btn btn-outline reset-btn">
                    <i class="fas fa-redo"></i>
                    <span>Reset</span>
                </button>
            </div>
        </form>
    </div>
    
    <!-- Comparison Results - Initially Hidden -->
    <div id="comparisonResults" class="comparison-results" style="display: none;">
        <div class="results-header">
            <h4 class="results-title">
                <i class="fas fa-chart-bar"></i>
                Comparison Results
            </h4>
            <div class="time-difference" id="timeDifference"></div>
        </div>
        
        <div id="comparisonTabs" class="tabs comparison-tabs">
            <button class="tab-button active" data-view="front">
                <i class="fas fa-user"></i>
                <span>Front View</span>
            </button>
            <button class="tab-button" data-view="side">
                <i class="fas fa-user-friends"></i>
                <span>Side View</span>
            </button>
            <button class="tab-button" data-view="back">
                <i class="fas fa-user-circle"></i>
                <span>Back View</span>
            </button>
        </div>
        
        <div id="comparisonContent" class="comparison-content">
            <!-- Comparison content will be loaded here -->
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($photos): ?>
    <div class="card photos-section">
        <!-- Tabs Navigation - Only 3 Tabs Now -->
        <div class="view-tabs-container">
            <div class="view-tabs">
                <button class="view-tab active" data-view="front">
                    <div class="tab-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <span class="tab-text">Front View</span>
                </button>
                <button class="view-tab" data-view="side">
                    <div class="tab-icon">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <span class="tab-text">Side View</span>
                </button>
                <button class="view-tab" data-view="back">
                    <div class="tab-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <span class="tab-text">Back View</span>
                </button>
            </div>
        </div>

        <!-- Tab Content -->
        <div id="tabContent">
            <!-- Individual View Tabs -->
            <?php foreach (['front', 'side', 'back'] as $view): ?>
            <div class="view-pane <?php echo $view === 'front' ? 'active' : ''; ?>" id="<?php echo $view; ?>-view">
                <div class="single-view-grid">
                    <?php foreach ($photos as $photo): ?>
                    <div class="single-photo-card">
                        <div class="photo-card-header">
                            <div class="photo-date">
                                <i class="fas fa-calendar"></i>
                                <?php echo date('F j, Y', strtotime($photo['photo_date'])); ?>
                                <?php if ($photo['photo_date'] == date('Y-m-d')): ?>
                                    <span class="date-badge">Today</span>
                                <?php endif; ?>
                            </div>
                            <div class="photo-days-ago">
                                <i class="fas fa-clock"></i>
                                <?php echo floor((time() - strtotime($photo['photo_date'])) / (60 * 60 * 24)); ?> days ago
                            </div>
                        </div>
                        
                        <div class="single-photo-view">
                            <div class="view-label large">
                                <i class="fas fa-<?php echo $view === 'front' ? 'user' : ($view === 'side' ? 'user-friends' : 'user-circle'); ?>"></i>
                                <?php echo ucfirst($view); ?> View
                            </div>
                            <div class="single-photo-container">
                                <img src="<?php echo $photo[$view . '_photo'] ?: 'https://via.placeholder.com/300x350/1a237e/ffffff?text=' . ucfirst($view) . '+View'; ?>" 
                                     alt="<?php echo ucfirst($view); ?> View" 
                                     class="single-progress-photo"
                                     data-date="<?php echo date('F j, Y', strtotime($photo['photo_date'])); ?>"
                                     data-src="<?php echo $photo[$view . '_photo'] ?: 'https://via.placeholder.com/300x350/1a237e/ffffff?text=' . ucfirst($view) . '+View'; ?>">
                            </div>
                        </div>
                        
                        <?php if ($photo['notes']): ?>
                        <div class="photo-notes">
                            <div class="notes-label">
                                <i class="fas fa-sticky-note"></i>
                                Notes
                            </div>
                            <p class="notes-content">"<?php echo nl2br($photo['notes']); ?>"</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <div class="card empty-state">
        <div class="empty-icon">
            <i class="fas fa-camera"></i>
        </div>
        <h3>No Progress Photos Yet</h3>
        <p>Start tracking your transformation by uploading your first progress photos!</p>
        <div class="empty-actions">
            <a href="dashboard.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
            <a href="progress_photos.php" class="btn btn-primary">
                <i class="fas fa-camera"></i>
                <span>Add First Photos</span>
            </a>
        </div>
    </div>
<?php endif; ?>

<style>
/* Modal Styles */
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

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
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

@keyframes slideIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
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

/* Make photos clickable */
.single-progress-photo,
.comparison-photo {
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.single-progress-photo:hover,
.comparison-photo:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}

/* Enhanced Tab Design */
.view-tabs-container {
    background: var(--bg-color);
    border-radius: 16px;
    padding: 0.75rem;
    margin-bottom: 2rem;
    border: 1px solid var(--border);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.view-tabs {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
}

.view-tab {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 1.25rem 0.5rem;
    background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.05) 0%, transparent 100%);
    border: 2px solid transparent;
    border-radius: 12px;
    color: var(--light-text);
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    min-height: 80px;
}

.view-tab::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--accent), var(--primary));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.view-tab:hover {
    background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1) 0%, rgba(var(--primary-rgb), 0.05) 100%);
    color: var(--accent);
    transform: translateY(-2px);
    border-color: rgba(var(--accent-rgb), 0.2);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}

.view-tab.active {
    background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.15) 0%, rgba(var(--primary-rgb), 0.1) 100%);
    color: var(--accent);
    border-color: rgba(var(--accent-rgb), 0.3);
    box-shadow: 0 8px 25px rgba(var(--accent-rgb), 0.15);
}

.view-tab.active::before {
    opacity: 1;
}

.tab-icon {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    background: rgba(var(--accent-rgb), 0.1);
    border-radius: 8px;
    transition: all 0.3s ease;
}

.view-tab.active .tab-icon {
    background: rgba(var(--accent-rgb), 0.2);
    transform: scale(1.1);
}

.tab-text {
    font-size: 0.85rem;
    font-weight: 600;
    text-align: center;
    transition: all 0.3s ease;
}

.view-tab.active .tab-text {
    font-weight: 700;
}

/* Comparison Card Styles */
.comparison-card {
    background: linear-gradient(135deg, var(--card-bg) 0%, rgba(var(--accent-rgb), 0.05) 100%);
    border: 1px solid rgba(var(--accent-rgb), 0.1);
}

.comparison-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.comparison-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--accent), var(--primary));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.comparison-title {
    color: var(--text);
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.comparison-subtitle {
    color: var(--light-text);
    margin: 0.25rem 0 0 0;
    font-size: 0.9rem;
}

/* Improved Date Selector Styles */
/* Improved Date Selector Styles */
.date-selector-group {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 1.5rem;
    align-items: start;
    margin-bottom: 2rem;
}

.date-selector {
    background: var(--bg-color);
    border: 2px solid var(--border);
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.date-selector::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--accent), var(--primary));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.date-selector.start-date::before {
    background: linear-gradient(90deg, #4CAF50, #8BC34A);
}

.date-selector.end-date::before {
    background: linear-gradient(90deg, #FF6B6B, #FFA726);
}

.date-selector:hover::before,
.date-selector:focus-within::before {
    opacity: 1;
}

.date-selector:hover {
    border-color: var(--accent);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.date-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.date-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.start-date .date-icon {
    background: linear-gradient(135deg, #4CAF50, #8BC34A);
    color: white;
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.end-date .date-icon {
    background: linear-gradient(135deg, #FF6B6B, #FFA726);
    color: white;
    box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
}

.date-label {
    display: block;
    color: var(--text);
    font-weight: 600;
    font-size: 1rem;
    margin-bottom: 0.25rem;
}

.date-subtitle {
    color: var(--light-text);
    font-size: 0.8rem;
}

.date-select {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: var(--bg-color);
    color: var(--text);
    font-size: 0.9rem;
    transition: all 0.3s ease;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23666'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 1rem;
    min-height: 44px;
    font-weight: 500;
}

.date-select:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.1);
    background-color: rgba(var(--accent-rgb), 0.02);
}

/* Custom select option styling */
.date-select option {
    padding: 0.75rem;
    background: var(--bg-color);
    color: var(--text);
    font-size: 0.9rem;
    border-bottom: 1px solid var(--border);
}

.date-select option:first-child {
    color: var(--light-text);
    font-style: italic;
}

.date-select option:checked {
    background: linear-gradient(135deg, var(--accent), var(--primary));
    color: white;
    font-weight: 600;
}

/* Modern select dropdown styling */
.date-select:hover {
    border-color: var(--accent);
    background-color: rgba(var(--accent-rgb), 0.02);
}

.date-select:active {
    transform: scale(0.98);
}

/* Selected state styling */
.date-select:valid {
    border-color: var(--accent);
    background-color: rgba(var(--accent-rgb), 0.05);
    font-weight: 600;
}

.date-select:invalid {
    color: var(--light-text);
}

/* Mobile optimizations for date selectors */
@media (max-width: 480px) {
    .date-selector-group {
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .date-selector {
        padding: 1.25rem;
    }
    
    .date-header {
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }
    
    .date-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
        flex-shrink: 0;
    }
    
    .date-label {
        font-size: 0.95rem;
    }
    
    .date-subtitle {
        font-size: 0.75rem;
    }
    
    .date-select {
        padding: 0.75rem;
        font-size: 0.85rem;
        background-position: right 0.75rem center;
    }
    
    /* Prevent zoom on iOS */
    select.date-select {
        font-size: 16px;
    }
}

/* Extra small devices */
@media (max-width: 360px) {
    .date-selector {
        padding: 1rem;
    }
    
    .date-icon {
        width: 36px;
        height: 36px;
        font-size: 0.9rem;
    }
    
    .date-select {
        padding: 0.675rem 0.75rem;
        font-size: 0.8rem;
    }
}

/* Touch device improvements */
@media (hover: none) and (pointer: coarse) {
    .date-select {
        min-height: 44px;
    }
    
    .date-selector:hover {
        transform: none;
        border-color: var(--border);
    }
    
    .date-selector:focus-within {
        border-color: var(--accent);
        transform: none;
    }
}

/* Enhanced focus states for accessibility */
.date-select:focus-visible {
    outline: 2px solid var(--accent);
    outline-offset: 2px;
}

/* Loading state */
.date-select:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background-color: rgba(var(--light-text-rgb), 0.1);
}

/* Success state when date is selected */
.date-select.valid {
    border-color: #4CAF50;
    background-color: rgba(76, 175, 80, 0.05);
}

/* Comparison arrow mobile adjustment */
@media (max-width: 480px) {
    .comparison-arrow {
        padding: 0.5rem 0;
        transform: rotate(90deg);
        font-size: 1.25rem;
    }
}

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.1); }
}

/* Comparison Actions */
.comparison-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.comparison-btn {
    padding: 0.875rem 2rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-radius: 10px;
    transition: all 0.3s ease;
    min-height: 44px;
}

.comparison-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(var(--accent-rgb), 0.3);
}

.reset-btn {
    padding: 0.875rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-radius: 10px;
    transition: all 0.3s ease;
    min-height: 44px;
}

.reset-btn:hover {
    transform: translateY(-1px);
}

/* Comparison Results */
.comparison-results {
    display: none;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border);
}

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.results-title {
    color: var(--text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.1rem;
}

.time-difference {
    background: rgba(var(--accent-rgb), 0.1);
    color: var(--accent);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
}

.comparison-tabs {
    display: flex;
    border-bottom: 1px solid var(--border);
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.tab-button {
    background: none;
    border: none;
    padding: 0.75rem 1.5rem;
    color: var(--light-text);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
    border-radius: var(--radius) var(--radius) 0 0;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-height: 44px;
}

.tab-button:hover {
    color: var(--accent);
    background: rgba(var(--accent-rgb), 0.1);
}

.tab-button.active {
    color: var(--accent);
    border-bottom-color: var(--accent);
    background: rgba(var(--accent-rgb), 0.1);
}

.comparison-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    align-items: start;
}

.comparison-item {
    text-align: center;
}

.comparison-date {
    color: var(--text);
    font-weight: 600;
    margin-bottom: 1rem;
    font-size: 1rem;
    padding: 0.5rem 1rem;
    background: rgba(var(--accent-rgb), 0.05);
    border-radius: 8px;
    display: inline-block;
}

.comparison-image {
    width: 100%;
    max-width: 300px;
    margin: 0 auto;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    transition: transform 0.3s ease;
}

.comparison-image:hover {
    transform: scale(1.02);
}

.comparison-image img {
    width: 100%;
    height: auto;
    display: block;
}

/* View Panes */
.view-pane {
    display: none;
}

.view-pane.active {
    display: block;
}

/* Photo Cards Layout */
.photos-grid,
.single-view-grid {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.photo-card,
.single-photo-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
}

.photo-card:hover,
.single-photo-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: var(--accent);
}

.photo-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.photo-date {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text);
    font-weight: 600;
    font-size: 1.1rem;
}

.photo-date i {
    color: var(--accent);
}

.date-badge {
    background: var(--accent);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.photo-days-ago {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--light-text);
    font-size: 0.9rem;
}

/* Single Photo View */
.single-photo-view {
    text-align: center;
}

.view-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    color: var(--accent);
    font-weight: 600;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.view-label.large {
    font-size: 1.1rem;
    margin-bottom: 1.5rem;
}

.single-photo-container {
    width: 100%;
    max-width: 400px;
    margin: 0 auto;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.single-photo-container:hover {
    transform: scale(1.02);
}

.progress-photo,
.single-progress-photo {
    width: 100%;
    height: auto;
    display: block;
}

/* Notes */
.photo-notes {
    border-top: 1px solid var(--border);
    padding-top: 1rem;
    margin-top: 1rem;
}

.notes-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text);
    font-weight: 600;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.notes-content {
    color: var(--light-text);
    margin: 0;
    font-style: italic;
    font-size: 0.9rem;
    line-height: 1.5;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
}

.empty-icon {
    font-size: 4rem;
    color: var(--light-text);
    margin-bottom: 1.5rem;
}

.empty-state h3 {
    color: var(--light-text);
    margin-bottom: 1rem;
}

.empty-state p {
    color: var(--light-text);
    margin-bottom: 2rem;
}

.empty-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

/* ==================== */
/* MOBILE RESPONSIVE STYLES */
/* ==================== */

/* Enhanced Mobile Responsive Styles */
@media (max-width: 480px) {
    /* Base card adjustments */
    .card {
        margin: 0.5rem;
        padding: 1rem;
        border-radius: 12px;
    }
    
    /* Header improvements */
    .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .card-header > div {
        width: 100%;
        justify-content: space-between;
    }
    
    /* Photo cards mobile optimization */
    .photo-card,
    .single-photo-card {
        padding: 1rem;
        margin: 0.5rem 0;
    }
    
    .photo-card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .photo-date {
        font-size: 1rem;
        flex-wrap: wrap;
    }
    
    .photo-days-ago {
        font-size: 0.8rem;
    }
    
    /* Single photo container */
    .single-photo-container {
        max-width: 100%;
        margin: 0 auto;
    }
    
    /* Date selector mobile optimization */
    .date-selector-group {
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .date-selector {
        padding: 1.25rem;
    }
    
    .date-header {
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }
    
    .date-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
        flex-shrink: 0;
    }
    
    .date-label {
        font-size: 0.95rem;
    }
    
    .date-subtitle {
        font-size: 0.75rem;
    }
    
    .date-select {
        padding: 0.75rem;
        font-size: 0.85rem;
    }
    
    /* Comparison arrow rotation for mobile */
    .comparison-arrow {
        padding: 0.5rem 0;
        transform: rotate(90deg);
        font-size: 1.25rem;
    }
    
    /* Comparison results mobile */
    .comparison-content {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .results-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .time-difference {
        font-size: 0.8rem;
        padding: 0.375rem 0.75rem;
    }
    
    /* Comparison actions mobile */
    .comparison-actions {
        flex-direction: column;
        width: 100%;
        gap: 0.75rem;
    }
    
    .comparison-btn,
    .reset-btn {
        width: 100%;
        justify-content: center;
        padding: 0.875rem 1rem;
    }
    
    /* Tabs mobile optimization */
    .view-tabs-container {
        padding: 0.5rem;
        margin-bottom: 1.5rem;
    }
    
    .view-tabs {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .view-tab {
        padding: 1rem 0.75rem;
        flex-direction: row;
        justify-content: flex-start;
        gap: 1rem;
        border-radius: 10px;
        min-height: 60px;
    }
    
    .tab-icon {
        width: 24px;
        height: 24px;
        font-size: 1rem;
        flex-shrink: 0;
    }
    
    .tab-text {
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    /* Comparison tabs mobile */
    .comparison-tabs {
        overflow-x: auto;
        flex-wrap: nowrap;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
        -webkit-overflow-scrolling: touch;
    }
    
    .tab-button {
        padding: 0.75rem 1rem;
        white-space: nowrap;
        font-size: 0.8rem;
        flex-shrink: 0;
    }
    
    /* Modal mobile optimization */
    .modal-content {
        margin: 5% auto;
        width: 95%;
        max-width: none;
        border-radius: 8px;
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
    
    .close-modal {
        font-size: 1.5rem;
        padding: 0.25rem;
    }
    
    /* Empty state mobile */
    .empty-state {
        padding: 2rem 1rem;
    }
    
    .empty-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    
    .empty-state h3 {
        font-size: 1.25rem;
    }
    
    .empty-state p {
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }
    
    .empty-actions {
        flex-direction: column;
        width: 100%;
        gap: 0.75rem;
    }
    
    .empty-actions .btn {
        width: 100%;
        justify-content: center;
    }
    
    /* View labels */
    .view-label.large {
        font-size: 1rem;
        margin-bottom: 1rem;
    }
    
    /* Notes section */
    .photo-notes {
        padding-top: 0.75rem;
        margin-top: 0.75rem;
    }
    
    .notes-label {
        font-size: 0.85rem;
    }
    
    .notes-content {
        font-size: 0.8rem;
        line-height: 1.4;
    }
}

/* Extra small devices (iPhone 5/SE - 320px and below) */
@media (max-width: 360px) {
    .card {
        margin: 0.25rem;
        padding: 0.75rem;
    }
    
    .photo-card,
    .single-photo-card {
        padding: 0.75rem;
        margin: 0.25rem 0;
    }
    
    .photo-date {
        font-size: 0.9rem;
    }
    
    .photo-days-ago {
        font-size: 0.75rem;
    }
    
    .date-selector {
        padding: 1rem;
    }
    
    .date-icon {
        width: 36px;
        height: 36px;
        font-size: 0.9rem;
    }
    
    .comparison-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .comparison-title {
        font-size: 1.1rem;
    }
    
    .view-tab {
        padding: 0.875rem 0.5rem;
        gap: 0.75rem;
    }
    
    .tab-icon {
        width: 22px;
        height: 22px;
        font-size: 0.9rem;
    }
    
    .tab-text {
        font-size: 0.85rem;
    }
    
    /* Button adjustments for very small screens */
    .btn {
        padding: 0.75rem 1rem;
        font-size: 0.85rem;
    }
    
    .btn-sm {
        padding: 0.5rem 0.75rem;
        font-size: 0.8rem;
    }
    
    /* Modal for very small screens */
    .modal-content {
        margin: 2% auto;
        width: 98%;
    }
    
    .modal-body {
        padding: 0.5rem;
    }
    
    .modal-header {
        padding: 0.75rem;
    }
    
    .modal-header h3 {
        font-size: 1rem;
    }
}

/* iPhone 12 Pro specific optimization (390px width) */
@media (max-width: 390px) and (min-width: 361px) {
    .card {
        margin: 0.5rem;
        padding: 1rem;
    }
    
    .single-photo-container {
        max-width: 340px;
    }
    
    .view-tab {
        padding: 1rem 0.5rem;
    }
}

/* Touch device improvements */
@media (hover: none) and (pointer: coarse) {
    /* Increase tap targets for mobile */
    .view-tab,
    .tab-button,
    .btn,
    .date-select {
        min-height: 44px;
    }
    
    .single-progress-photo,
    .comparison-photo {
        cursor: default;
    }
    
    /* Remove hover effects on touch devices */
    .view-tab:hover {
        transform: none;
        border-color: transparent;
    }
    
    .view-tab.active:hover {
        border-color: rgba(var(--accent-rgb), 0.3);
    }
    
    .single-photo-container:hover {
        transform: none;
    }
    
    .date-selector:hover {
        transform: none;
        border-color: var(--border);
    }
    
    .date-selector:focus-within {
        border-color: var(--accent);
        transform: none;
    }
}

/* Prevent zoom on input focus for iOS */
@media (max-width: 480px) {
    select.date-select {
        font-size: 16px; /* Prevents zoom on iOS */
    }
}

/* Safe area insets for notched devices */
@supports (padding: max(0px)) {
    @media (max-width: 480px) {
        .card {
            padding-left: max(1rem, env(safe-area-inset-left));
            padding-right: max(1rem, env(safe-area-inset-right));
        }
        
        .modal-content {
            margin-left: max(0.5rem, env(safe-area-inset-left));
            margin-right: max(0.5rem, env(safe-area-inset-right));
        }
        
        .empty-actions {
            padding-bottom: max(1rem, env(safe-area-inset-bottom));
        }
    }
}

/* High DPI screen optimizations */
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
    .single-progress-photo,
    .comparison-photo {
        image-rendering: -webkit-optimize-contrast;
        image-rendering: crisp-edges;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .view-tab,
    .single-photo-container,
    .date-selector,
    .comparison-image,
    .photo-card,
    .single-photo-card,
    .btn {
        transition: none;
        animation: none;
    }
    
    .comparison-arrow {
        animation: none;
    }
}

/* Mobile responsive styles */
@media (max-width: 768px) {
    .mobile-hidden {
        display: none;
    }
    
    /* Photo Cards Mobile */
    .photo-card-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .photo-card,
    .single-photo-card {
        padding: 1rem;
    }
    
    /* Empty State Mobile */
    .empty-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .empty-actions .btn {
        width: 100%;
        justify-content: center;
    }
    
    /* Comparison Tabs Mobile */
    .comparison-tabs {
        overflow-x: auto;
        flex-wrap: nowrap;
        padding-bottom: 0.5rem;
    }
    
    .tab-button {
        padding: 0.5rem 1rem;
        white-space: nowrap;
        font-size: 0.8rem;
    }
    
    /* Modal Mobile */
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

@media (max-width: 480px) {
    .photo-date {
        font-size: 1rem;
    }
    
    .single-photo-container {
        max-width: 100%;
    }
    
    .date-selector {
        padding: 1rem;
    }
    
    .date-icon {
        width: 36px;
        height: 36px;
        font-size: 0.9rem;
    }
    
    .comparison-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .comparison-title {
        font-size: 1.1rem;
    }
    
    .view-tab {
        padding: 0.875rem 0.5rem;
    }
    
    .tab-icon {
        width: 24px;
        height: 24px;
        font-size: 1rem;
    }
}

/* Small mobile devices */
@media (max-width: 360px) {
    .card {
        margin: 0.5rem;
    }
    
    .photo-card,
    .single-photo-card {
        padding: 0.75rem;
    }
    
    .photo-date {
        font-size: 0.9rem;
    }
    
    .photo-days-ago {
        font-size: 0.8rem;
    }
    
    .view-label.large {
        font-size: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modern Tab functionality
    const viewTabs = document.querySelectorAll('.view-tab');
    const viewPanes = document.querySelectorAll('.view-pane');
    
    viewTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const view = this.getAttribute('data-view');
            
            // Update tabs
            viewTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Update panes
            viewPanes.forEach(pane => pane.classList.remove('active'));
            document.getElementById(view + '-view').classList.add('active');
        });
    });
    
    // Modal functionality
    const modal = document.getElementById('photoModal');
    const modalImage = document.getElementById('modalImage');
    const modalDate = document.getElementById('modalDate');
    const closeModal = document.querySelector('.close-modal');
    
    // Add click event to all progress photos
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('single-progress-photo') || e.target.classList.contains('comparison-photo')) {
            const src = e.target.getAttribute('data-src');
            const date = e.target.getAttribute('data-date');
            
            modalImage.src = src;
            modalDate.textContent = date;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    });
    
    // Close modal
    closeModal.addEventListener('click', function() {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    });
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display === 'block') {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
    
    // Comparison functionality
    const compareBtn = document.getElementById('compareBtn');
    const resetBtn = document.getElementById('resetCompare');
    
    if (compareBtn) {
        compareBtn.addEventListener('click', function() {
            const startDate = document.querySelector('select[name="start_date"]').value;
            const endDate = document.querySelector('select[name="end_date"]').value;
            
            if (!startDate || !endDate) {
                showNotification('Please select both start and end dates for comparison.', 'error');
                return;
            }
            
            if (startDate === endDate) {
                showNotification('Please select different dates to see meaningful progress.', 'error');
                return;
            }
            
            // Find the photos for selected dates
            const photos = <?php echo json_encode($photos); ?>;
            const startPhoto = photos.find(photo => photo.photo_date === startDate);
            const endPhoto = photos.find(photo => photo.photo_date === endDate);
            
            if (!startPhoto || !endPhoto) {
                showNotification('Could not find photos for the selected dates.', 'error');
                return;
            }
            
            // Show comparison results
            const results = document.getElementById('comparisonResults');
            results.style.display = 'block';
            
            // Hide photos section when comparison is shown
            document.querySelector('.photos-section').style.display = 'none';
            
            // Scroll to results
            results.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            // Load initial comparison view
            loadComparisonView('front', startPhoto, endPhoto);
            
            // Add event listeners for comparison tabs
            const comparisonTabs = document.querySelectorAll('#comparisonTabs .tab-button');
            comparisonTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    comparisonTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    const view = this.getAttribute('data-view');
                    loadComparisonView(view, startPhoto, endPhoto);
                });
            });
            
            showNotification('Comparison loaded successfully! Switch between tabs to see different views.', 'success');
        });
    }
    
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            document.querySelector('select[name="start_date"]').value = '';
            document.querySelector('select[name="end_date"]').value = '';
            document.getElementById('comparisonResults').style.display = 'none';
            
            // Show photos section when comparison is reset
            document.querySelector('.photos-section').style.display = 'block';
            
            showNotification('Comparison reset. Select new dates to compare.', 'info');
        });
    }
    
    function loadComparisonView(view, startPhoto, endPhoto) {
        const comparisonContent = document.getElementById('comparisonContent');
        const startDateObj = new Date(startPhoto.photo_date);
        const endDateObj = new Date(endPhoto.photo_date);
        const daysDiff = Math.floor((endDateObj - startDateObj) / (1000 * 60 * 60 * 24));
        const monthsDiff = Math.floor(daysDiff / 30);
        const weeksDiff = Math.floor(daysDiff / 7);
        
        let timeText = '';
        if (monthsDiff >= 1) {
            timeText = `${monthsDiff} month${monthsDiff > 1 ? 's' : ''} progress`;
        } else if (weeksDiff >= 1) {
            timeText = `${weeksDiff} week${weeksDiff > 1 ? 's' : ''} progress`;
        } else {
            timeText = `${daysDiff} day${daysDiff > 1 ? 's' : ''} progress`;
        }
        
        document.getElementById('timeDifference').textContent = timeText;
        
        comparisonContent.innerHTML = `
            <div class="comparison-item">
                <div class="comparison-date">
                    <i class="fas fa-flag"></i>
                    ${formatDate(startPhoto.photo_date)}
                </div>
                <div class="comparison-image">
                    <img src="${startPhoto[view + '_photo'] || 'https://via.placeholder.com/300x350/1a237e/ffffff?text=' + view.charAt(0).toUpperCase() + view.slice(1) + '+View'}" 
                         alt="${view} view - ${formatDate(startPhoto.photo_date)}"
                         class="comparison-photo"
                         data-date="${formatDate(startPhoto.photo_date)}"
                         data-src="${startPhoto[view + '_photo'] || 'https://via.placeholder.com/300x350/1a237e/ffffff?text=' + view.charAt(0).toUpperCase() + view.slice(1) + '+View'}">
                </div>
                <div style="margin-top: 0.5rem; color: var(--light-text); font-size: 0.9rem;">
                    Start of journey
                </div>
            </div>
            <div class="comparison-item">
                <div class="comparison-date">
                    <i class="fas fa-bullseye"></i>
                    ${formatDate(endPhoto.photo_date)}
                </div>
                <div class="comparison-image">
                    <img src="${endPhoto[view + '_photo'] || 'https://via.placeholder.com/300x350/1a237e/ffffff?text=' + view.charAt(0).toUpperCase() + view.slice(1) + '+View'}" 
                         alt="${view} view - ${formatDate(endPhoto.photo_date)}"
                         class="comparison-photo"
                         data-date="${formatDate(endPhoto.photo_date)}"
                         data-src="${endPhoto[view + '_photo'] || 'https://via.placeholder.com/300x350/1a237e/ffffff?text=' + view.charAt(0).toUpperCase() + view.slice(1) + '+View'}">
                </div>
                <div style="margin-top: 0.5rem; color: var(--light-text); font-size: 0.9rem;">
                    Current progress
                </div>
            </div>
        `;
        
        // Add click events to comparison photos
        document.querySelectorAll('.comparison-photo').forEach(photo => {
            photo.addEventListener('click', function() {
                const src = this.getAttribute('data-src');
                const date = this.getAttribute('data-date');
                
                modalImage.src = src;
                modalDate.textContent = date;
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            });
        });
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    }
    
    function showNotification(message, type) {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.custom-notification');
        existingNotifications.forEach(notification => notification.remove());
        
        const notification = document.createElement('div');
        notification.className = `custom-notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            max-width: 400px;
            animation: slideIn 0.3s ease;
        `;
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    }
    
    // Add keyframe animations for notifications
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);

    // Better touch handling for mobile
    if ('ontouchstart' in window) {
        document.body.classList.add('touch-device');
    }
    
    // Prevent double-tap zoom on buttons
    const interactiveElements = document.querySelectorAll('.btn, .view-tab, .tab-button');
    interactiveElements.forEach(element => {
        element.addEventListener('touchstart', function() {
            // Add active state
            this.style.transform = 'scale(0.98)';
        });
        
        element.addEventListener('touchend', function() {
            // Remove active state
            this.style.transform = '';
        });
    });
});
</script>

<?php require_once 'footer.php'; ?>