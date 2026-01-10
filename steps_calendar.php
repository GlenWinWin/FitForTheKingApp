<?php
date_default_timezone_set("Asia/Hong_Kong");

$pageTitle = "Steps Calendar";
require_once 'header.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$current_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Check for success message
$success_message = $_GET['message'] ?? '';

// Check for selected date from URL or form submission
$selected_date = isset($_GET['selected_date']) ? $_GET['selected_date'] : (isset($_POST['entry_date']) ? $_POST['entry_date'] : '');
$is_selected_today = ($selected_date == date('Y-m-d'));

// Get steps for the current month
$start_date = date('Y-m-01', strtotime($current_month));
$end_date = date('Y-m-t', strtotime($current_month));

$steps_query = "SELECT entry_date, steps_count FROM steps 
               WHERE user_id = ? AND entry_date BETWEEN ? AND ?";
$stmt = $db->prepare($steps_query);
$stmt->execute([$user_id, $start_date, $end_date]);
$steps_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create associative array for easy lookup
$steps_by_date = [];
foreach ($steps_data as $step) {
    $steps_by_date[$step['entry_date']] = $step['steps_count'];
}

// Generate calendar - Sunday as first day (0)
$first_day = date('w', strtotime($start_date)); // 0 (Sun) to 6 (Sat)
$days_in_month = date('t', strtotime($start_date));

// Get monthly summary
$summary_query = "SELECT 
                 SUM(steps_count) as total_steps,
                 AVG(steps_count) as avg_steps,
                 MAX(steps_count) as max_steps
                 FROM steps 
                 WHERE user_id = ? AND entry_date BETWEEN ? AND ?";
$stmt = $db->prepare($summary_query);
$stmt->execute([$user_id, $start_date, $end_date]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Get steps for previous and next months for navigation
$prev_month = date('Y-m', strtotime($current_month . ' -1 month'));
$next_month = date('Y-m', strtotime($current_month . ' +1 month'));
?>

<!-- Premium Background -->
<div class="premium-bg"></div>
<div class="particles-container" id="particles-container"></div>

<div class="native-app-wrapper">
    <?php if ($success_message): ?>
    <div class="native-message-toast">
        <div class="toast-content">
            <i class="fas fa-check-circle toast-icon"></i>
            <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sticky App Header -->
    <header class="native-app-header">
        <div class="header-container">
            <div class="header-title-section">
                <h1 class="app-title">Steps Calendar</h1>
                <div class="header-subtitle">Track your daily progress</div>
            </div>
            <div class="header-month-display">
                <?php echo date('F Y', strtotime($current_month)); ?>
            </div>
        </div>
    </header>

    <main class="native-app-content">
        <!-- Month Navigation -->
        <div class="native-month-navigation">
            <a href="steps_calendar.php?month=<?php echo $prev_month; ?>&selected_date=<?php echo $selected_date; ?>" class="nav-btn prev-month" aria-label="Previous month">
                <i class="fas fa-chevron-left"></i>
            </a>
            
            <div class="current-month-title">
                <div class="month-name"><?php echo date('M Y', strtotime($current_month)); ?></div>
                <div class="month-stats"><?php echo number_format($summary['total_steps'] ?? 0); ?> steps</div>
            </div>
            
            <a href="steps_calendar.php?month=<?php echo $next_month; ?>&selected_date=<?php echo $selected_date; ?>" class="nav-btn next-month" aria-label="Next month">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>

        <!-- Calendar Card -->
        <div class="native-calendar-card">
            <!-- Day headers -->
            <div class="calendar-weekdays">
                <div class="weekday">S</div>
                <div class="weekday">M</div>
                <div class="weekday">T</div>
                <div class="weekday">W</div>
                <div class="weekday">T</div>
                <div class="weekday">F</div>
                <div class="weekday">S</div>
            </div>
            
            <!-- Calendar grid -->
            <div class="calendar-grid-native">
                <!-- Empty cells for days before the first day of month -->
                <?php for ($i = 0; $i < $first_day; $i++): ?>
                    <div class="calendar-cell empty"></div>
                <?php endfor; ?>
                
                <!-- Days of the month -->
                <?php for ($day = 1; $day <= $days_in_month; $day++): 
                    $current_date = date('Y-m-d', strtotime($current_month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT)));
                    $steps = $steps_by_date[$current_date] ?? 0;
                    $is_today = $current_date == date('Y-m-d');
                    $is_selected = !$is_today && $current_date == $selected_date; // Only mark as selected if it's not today
                    $is_weekend = in_array(date('w', strtotime($current_date)), [0, 6]);
                    $steps_class = '';
                    
                    if ($steps > 0) {
                        if ($steps < 5000) $steps_class = 'steps-low';
                        elseif ($steps < 10000) $steps_class = 'steps-medium';
                        else $steps_class = 'steps-high';
                    }
                ?>
                    <div class="calendar-cell 
                        <?php echo $is_today ? 'today' : ''; ?>
                        <?php echo $is_selected ? 'selected' : ''; ?>
                        <?php echo $is_weekend ? 'weekend' : ''; ?>
                        <?php echo $steps_class; ?>"
                        data-date="<?php echo $current_date; ?>"
                        data-steps="<?php echo $steps; ?>"
                        role="button"
                        tabindex="0">
                        <div class="day-number"><?php echo $day; ?></div>
                        <?php if ($steps > 0): ?>
                            <div class="steps-count"><?php echo number_format($steps); ?></div>
                        <?php else: ?>
                            <div class="no-steps">-</div>
                        <?php endif; ?>
                        <?php if ($is_today): ?>
                            <div class="today-ring"></div>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Quick Add Form -->
        <div class="native-quick-add-card">
            <div class="card-header-native">
                <h2 class="card-title-native">Add Steps Entry</h2>
            </div>
            
            <form action="steps_add.php" method="POST" class="native-steps-form">
                <input type="hidden" name="redirect_to" value="steps_calendar.php?month=<?php echo $current_month; ?>&selected_date=<?php echo $selected_date; ?>">
                <div class="form-row-native">
                    <div class="form-field-native">
                        <label for="native_entry_date" class="form-label-native">Date</label>
                        <div class="input-container">
                            <input type="date" 
                                   id="native_entry_date" 
                                   name="entry_date" 
                                   value="<?php echo $selected_date ?: date('Y-m-d'); ?>" 
                                   required 
                                   class="form-input-native"
                                   max="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-field-native">
                        <label for="native_steps_count" class="form-label-native">Steps Count</label>
                        <div class="input-container">
                            <input type="number" 
                                   id="native_steps_count" 
                                   name="steps_count" 
                                   min="1" 
                                   max="100000" 
                                   placeholder="Enter steps" 
                                   value="<?php echo isset($steps_by_date[$selected_date]) ? $steps_by_date[$selected_date] : ''; ?>"
                                   required 
                                   class="form-input-native">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="native-primary-action-btn">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add Steps</span>
                </button>
            </form>
        </div>

        <!-- Monthly Summary -->
        <div class="native-summary-card">
            <div class="card-header-native">
                <h2 class="card-title-native">Monthly Summary</h2>
                <div class="card-subtitle"><?php echo date('M Y', strtotime($current_month)); ?></div>
            </div>
            
            <div class="stats-grid-native">
                <div class="stat-card-native">
                    <div class="stat-icon-native">
                        <i class="fas fa-shoe-prints"></i>
                    </div>
                    <div class="stat-content-native">
                        <div class="stat-number-native"><?php echo number_format($summary['total_steps'] ?? 0); ?></div>
                        <div class="stat-label-native">Total Steps</div>
                    </div>
                </div>
                
                <div class="stat-card-native">
                    <div class="stat-icon-native">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content-native">
                        <div class="stat-number-native"><?php echo number_format(round($summary['avg_steps'] ?? 0)); ?></div>
                        <div class="stat-label-native">Daily Average</div>
                    </div>
                </div>
                
                <div class="stat-card-native">
                    <div class="stat-icon-native">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-content-native">
                        <div class="stat-number-native"><?php echo number_format($summary['max_steps'] ?? 0); ?></div>
                        <div class="stat-label-native">Most Steps</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bottom spacing for mobile -->
        <div class="native-bottom-spacing"></div>
    </main>
</div>

<style>
/* Native App Base Styles - Using EXISTING theme variables only */
.native-app-wrapper {
    min-height: 100vh;
    position: relative;
    padding-bottom: env(safe-area-inset-bottom);
}

.native-app-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--glass-border);
    padding: 0.75rem 1rem;
    padding-top: calc(0.75rem + env(safe-area-inset-top));
}

.header-container {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
}

.header-title-section {
    flex: 1;
}

.app-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text);
    margin: 0 0 0.25rem 0;
    letter-spacing: -0.3px;
}

.header-subtitle {
    font-size: 0.875rem;
    color: var(--text-light);
    font-weight: 500;
}

.header-month-display {
    font-size: 0.875rem;
    color: var(--accent);
    font-weight: 600;
    background: var(--glass-bg);
    padding: 0.5rem 0.875rem;
    border-radius: var(--radius);
    border: 1px solid var(--glass-border);
    backdrop-filter: blur(10px);
}

/* Native App Content */
.native-app-content {
    padding: 1rem;
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
    padding-bottom: 2rem;
}

/* Toast Message - Using existing success colors */
.native-message-toast {
    position: fixed;
    top: calc(4rem + env(safe-area-inset-top));
    left: 50%;
    transform: translateX(-50%) translateY(-20px);
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-lg);
    padding: 0.875rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    z-index: 1000;
    backdrop-filter: blur(20px);
    animation: toastSlideIn 0.3s ease-out forwards;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    max-width: calc(100% - 2rem);
    width: auto;
}

.toast-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.toast-icon {
    color: var(--accent);
    font-size: 1.125rem;
}

@keyframes toastSlideIn {
    from {
        opacity: 0;
        transform: translateX(-50%) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
}

/* Month Navigation - Using existing nav styles */
.native-month-navigation {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--glass-bg);
    border-radius: var(--radius-lg);
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid var(--glass-border);
    backdrop-filter: blur(10px);
}

.nav-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    background: var(--glass-bg);
    border-radius: var(--radius);
    text-decoration: none;
    color: var(--accent);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid var(--glass-border);
    flex-shrink: 0;
    -webkit-tap-highlight-color: transparent;
}

.nav-btn:active {
    transform: scale(0.95);
    background: rgba(var(--accent-rgb), 0.1);
}

.current-month-title {
    text-align: center;
    flex: 1;
    padding: 0 1rem;
}

.month-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--accent);
    margin-bottom: 0.25rem;
}

.month-stats {
    font-size: 0.875rem;
    color: var(--text-light);
    font-weight: 500;
}

/* Native Calendar Card - Using EXACT existing calendar styles */
.native-calendar-card {
    background: var(--glass-bg);
    border-radius: var(--radius-lg);
    padding: 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid var(--glass-border);
    backdrop-filter: blur(10px);
}

.calendar-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.weekday {
    text-align: center;
    font-weight: 600;
    padding: 0.5rem 0;
    color: var(--accent);
    font-size: 0.8125rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.calendar-grid-native {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.5rem;
}

/* Calendar cell base styles */
.calendar-cell {
    aspect-ratio: 1;
    min-height: 50px;
    background: rgba(255, 255, 255, 0.8);
    border-radius: var(--radius);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    border: 1px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
    -webkit-tap-highlight-color: transparent;
    cursor: pointer;
}

.calendar-cell.empty {
    background: transparent;
    border: 1px dashed var(--glass-border);
    backdrop-filter: none;
    cursor: default;
}

/* TODAY STYLE - Must come BEFORE weekend and selected styles */
.calendar-cell.today {
    background: var(--gradient-accent);
    color: white;
    box-shadow: 0 4px 12px rgba(var(--accent-rgb), 0.3);
    transform: scale(1.05);
    border: none;
}

/* SELECTED DATE STYLE - Only for non-today dates */
.calendar-cell.selected {
    background: var(--gradient-primary);
    color: white;
    box-shadow: 0 4px 12px rgba(var(--primary-rgb, 76, 175, 80), 0.3);
    transform: scale(1.05);
    border: none;
}

.calendar-cell.weekend {
    background: rgba(var(--accent-rgb), 0.05);
}

/* Day number styling */
.day-number {
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.125rem;
    color: var(--text); /* Default color for regular days */
}

/* Today and selected dates should have white text */
.calendar-cell.today .day-number,
.calendar-cell.selected .day-number {
    color: white !important;
    font-weight: 700;
}

/* Steps count styling */
.steps-count {
    color: var(--primary);
    font-size: 0.75rem;
    font-weight: 600;
    text-align: center;
    line-height: 1;
}

/* Today and selected dates should have light steps count */
.calendar-cell.today .steps-count,
.calendar-cell.selected .steps-count {
    color: rgba(255, 255, 255, 0.9) !important;
}

/* No steps indicator */
.no-steps {
    color: var(--text-light);
    font-size: 0.75rem;
    opacity: 0.5;
}

.calendar-cell.today .no-steps,
.calendar-cell.selected .no-steps {
    color: rgba(255, 255, 255, 0.7) !important;
}

/* Steps color coding - Override for today/selected */
.calendar-cell.steps-low .steps-count {
    color: #ff6b6b;
}

.calendar-cell.steps-medium .steps-count {
    color: #ffa726;
}

.calendar-cell.steps-high .steps-count {
    color: #4caf50;
}

/* Today and selected dates override the steps color coding */
.calendar-cell.today.steps-low .steps-count,
.calendar-cell.today.steps-medium .steps-count,
.calendar-cell.today.steps-high .steps-count,
.calendar-cell.selected.steps-low .steps-count,
.calendar-cell.selected.steps-medium .steps-count,
.calendar-cell.selected.steps-high .steps-count {
    color: rgba(255, 255, 255, 0.9) !important;
}

.today-ring {
    position: absolute;
    top: 4px;
    width: 4px;
    height: 4px;
    background: white;
    border-radius: 50%;
}

/* Native Quick Add Card */
.native-quick-add-card {
    background: var(--glass-bg);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    margin-bottom: 1rem;
    border: 1px solid var(--glass-border);
    backdrop-filter: blur(10px);
}

.card-header-native {
    margin-bottom: 1.25rem;
}

.card-title-native {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text);
    margin: 0 0 0.25rem 0;
}

.card-subtitle {
    font-size: 0.875rem;
    color: var(--text-light);
}

/* Native Form - Using existing form styles */
.native-steps-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.form-row-native {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-field-native {
    display: flex;
    flex-direction: column;
}

.form-label-native {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text);
    font-size: 0.875rem;
}

.input-container {
    position: relative;
}

.form-input-native {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 1px solid var(--glass-border);
    border-radius: var(--radius);
    background: var(--glass-bg);
    font-size: 1rem;
    transition: all 0.3s ease;
    -webkit-appearance: none;
    appearance: none;
    font-family: inherit;
}

.form-input-native:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.1);
}

/* Native Primary Action Button - Using existing button colors */
.native-primary-action-btn {
    width: 100%;
    padding: 1rem;
    background: var(--gradient-accent);
    border: none;
    border-radius: var(--radius);
    color: white;
    font-size: 1rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    margin-top: 0.5rem;
    -webkit-tap-highlight-color: transparent;
}

.native-primary-action-btn:active {
    transform: scale(0.98);
    opacity: 0.9;
}

.native-primary-action-btn i {
    font-size: 1.125rem;
}

/* Native Summary Card */
.native-summary-card {
    background: var(--glass-bg);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    margin-bottom: 1rem;
    border: 1px solid var(--glass-border);
    backdrop-filter: blur(10px);
}

.stats-grid-native {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1rem;
}

.stat-card-native {
    background: var(--glass-bg);
    border-radius: var(--radius);
    padding: 1.25rem;
    text-align: center;
    transition: all 0.3s ease;
    border: 1px solid var(--glass-border);
    backdrop-filter: blur(10px);
}

.stat-card-native:active {
    transform: translateY(-2px);
}

.stat-icon-native {
    font-size: 1.5rem;
    color: var(--accent);
    margin-bottom: 0.75rem;
    opacity: 0.9;
}

.stat-number-native {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--accent);
    margin-bottom: 0.25rem;
}

.stat-label-native {
    font-size: 0.875rem;
    color: var(--text-light);
    font-weight: 500;
}

.stat-content-native {
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* Bottom spacing */
.native-bottom-spacing {
    height: 2rem;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .native-app-header {
        padding: 0.625rem 0.875rem;
        padding-top: calc(0.625rem + env(safe-area-inset-top));
    }
    
    .app-title {
        font-size: 1.25rem;
    }
    
    .header-subtitle {
        font-size: 0.8125rem;
    }
    
    .header-month-display {
        font-size: 0.8125rem;
        padding: 0.375rem 0.75rem;
    }
    
    .native-app-content {
        padding: 0.875rem;
    }
    
    .native-month-navigation {
        padding: 0.875rem;
        margin-bottom: 0.875rem;
    }
    
    .nav-btn {
        width: 40px;
        height: 40px;
    }
    
    .month-name {
        font-size: 1.125rem;
    }
    
    .month-stats {
        font-size: 0.8125rem;
    }
    
    .native-calendar-card {
        padding: 1rem;
        border-radius: var(--radius);
    }
    
    .calendar-weekdays {
        gap: 0.375rem;
        margin-bottom: 0.625rem;
    }
    
    .weekday {
        padding: 0.375rem 0;
        font-size: 0.75rem;
    }
    
    .calendar-grid-native {
        gap: 0.375rem;
    }
    
    .calendar-cell {
        min-height: 44px;
        border-radius: 10px;
    }
    
    .day-number {
        font-size: 0.8125rem;
    }
    
    .steps-count, .no-steps {
        font-size: 0.6875rem;
    }
    
    .native-quick-add-card {
        padding: 1.25rem;
    }
    
    .card-title-native {
        font-size: 1.125rem;
    }
    
    .form-row-native {
        grid-template-columns: 1fr;
        gap: 0.875rem;
    }
    
    .form-input-native {
        padding: 0.75rem 0.875rem;
        font-size: 1rem;
    }
    
    .native-primary-action-btn {
        padding: 0.875rem;
        font-size: 0.9375rem;
    }
    
    .native-summary-card {
        padding: 1.25rem;
    }
    
    .stats-grid-native {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .stat-card-native {
        padding: 1rem;
    }
    
    .stat-number-native {
        font-size: 1.375rem;
    }
    
    .stat-icon-native {
        font-size: 1.375rem;
        margin-bottom: 0.5rem;
    }
}

@media (max-width: 480px) {
    .native-app-header {
        padding: 0.5rem 0.75rem;
        padding-top: calc(0.5rem + env(safe-area-inset-top));
    }
    
    .header-container {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .header-month-display {
        align-self: flex-start;
    }
    
    .native-app-content {
        padding: 0.75rem;
    }
    
    .calendar-grid-native {
        gap: 0.25rem;
    }
    
    .calendar-cell {
        min-height: 40px;
        border-radius: 8px;
    }
    
    .day-number {
        font-size: 0.75rem;
    }
    
    .steps-count, .no-steps {
        font-size: 0.625rem;
    }
    
    .native-message-toast {
        top: calc(3.5rem + env(safe-area-inset-top));
        padding: 0.75rem 0.875rem;
        font-size: 0.875rem;
    }
    
    .native-primary-action-btn {
        padding: 0.75rem;
    }
}

@media (max-width: 360px) {
    .calendar-cell {
        min-height: 36px;
    }
    
    .day-number {
        font-size: 0.6875rem;
    }
    
    .steps-count, .no-steps {
        font-size: 0.5625rem;
    }
    
    .native-app-content {
        padding: 0.625rem;
    }
}

/* Native App Interactions */
* {
    -webkit-tap-highlight-color: transparent;
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    user-select: none;
}

input, textarea {
    -webkit-user-select: text;
    user-select: text;
}

/* Prevent zoom */
html, body {
    touch-action: pan-y;
    overscroll-behavior: none;
    -webkit-text-size-adjust: 100%;
    text-size-adjust: 100%;
}

/* Disable double-tap zoom */
@media (hover: none) and (pointer: coarse) {
    button, a, .calendar-cell {
        touch-action: manipulation;
    }
}

/* Date input specific */
input[type="date"]::-webkit-calendar-picker-indicator {
    opacity: 0;
    position: absolute;
    width: 100%;
    height: 100%;
    cursor: pointer;
    left: 0;
}

/* Number input */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type="number"] {
    -moz-appearance: textfield;
}

/* Focus states for accessibility */
.nav-btn:focus-visible,
.calendar-cell:focus-visible,
.native-primary-action-btn:focus-visible,
.form-input-native:focus-visible {
    outline: 2px solid var(--accent);
    outline-offset: 2px;
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* iOS safe area support */
@supports (-webkit-touch-callout: none) {
    .native-app-header {
        padding-top: calc(1rem + env(safe-area-inset-top));
    }
    
    .native-app-wrapper {
        padding-bottom: calc(1rem + env(safe-area-inset-bottom));
    }
}
</style>

<script>
// Native mobile app interactions
document.addEventListener('DOMContentLoaded', function() {
    // Prevent zoom gestures
    document.addEventListener('gesturestart', function(e) {
        e.preventDefault();
    });
    
    // Disable double-tap to zoom
    let lastTouchEnd = 0;
    document.addEventListener('touchend', function(e) {
        const now = Date.now();
        if (now - lastTouchEnd <= 300) {
            e.preventDefault();
        }
        lastTouchEnd = now;
    }, { passive: false });
    
    // Calendar cell interactions
    const calendarCells = document.querySelectorAll('.calendar-cell:not(.empty)');
    let selectedCell = null;
    
    // Find and highlight initially selected cell (from PHP) - but not if it's today
    const initialSelectedDate = "<?php echo $selected_date; ?>";
    const today = new Date().toISOString().split('T')[0];
    
    if (initialSelectedDate && initialSelectedDate !== today) {
        const initialSelectedCell = document.querySelector(`.calendar-cell[data-date="${initialSelectedDate}"]`);
        if (initialSelectedCell && !initialSelectedCell.classList.contains('today')) {
            selectedCell = initialSelectedCell;
            selectedCell.classList.add('selected');
        }
    }
    
    calendarCells.forEach(cell => {
        // Touch feedback
        cell.addEventListener('touchstart', function() {
            if (!this.classList.contains('empty') && !this.classList.contains('today')) {
                this.style.transform = 'scale(0.95)';
            }
        }, { passive: true });
        
        cell.addEventListener('touchend', function() {
            this.style.transform = '';
        }, { passive: true });
        
        cell.addEventListener('touchcancel', function() {
            this.style.transform = '';
        }, { passive: true });
        
        // Click to select date and populate form
        cell.addEventListener('click', function() {
            const date = this.getAttribute('data-date');
            const steps = this.getAttribute('data-steps');
            const isToday = this.classList.contains('today');
            
            // Only allow selection of non-today dates
            if (!isToday) {
                // Remove selected class from previously selected cell
                if (selectedCell && selectedCell !== this) {
                    selectedCell.classList.remove('selected');
                }
                
                // Add selected class to current cell
                this.classList.add('selected');
                selectedCell = this;
            }
            
            // Update form fields regardless of selection
            const dateInput = document.getElementById('native_entry_date');
            const stepsInput = document.getElementById('native_steps_count');
            
            if (dateInput && stepsInput) {
                dateInput.value = date;
                
                if (steps > 0) {
                    stepsInput.value = steps;
                    stepsInput.focus();
                } else {
                    stepsInput.value = '';
                    stepsInput.focus();
                }
                
                // Update URL to reflect selected date (without page reload)
                const url = new URL(window.location);
                url.searchParams.set('selected_date', date);
                window.history.replaceState({}, '', url);
                
                // Scroll to form
                const form = document.querySelector('.native-quick-add-card');
                if (form) {
                    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });
    
    // Form submission handling
    const form = document.querySelector('.native-steps-form');
    if (form) {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('.native-primary-action-btn');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Adding...</span>';
                submitBtn.disabled = true;
            }
        });
    }
    
    // Auto-hide toast message
    const toast = document.querySelector('.native-message-toast');
    if (toast) {
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(-50%) translateY(-20px)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
    
    // Set max date to today
    const dateInput = document.getElementById('native_entry_date');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.max = today;
        
        // When date changes in form, update selected calendar cell
        dateInput.addEventListener('change', function() {
            const selectedDate = this.value;
            const cells = document.querySelectorAll('.calendar-cell:not(.empty)');
            
            // Remove selected class from all non-today cells
            cells.forEach(cell => {
                if (!cell.classList.contains('today')) {
                    cell.classList.remove('selected');
                }
            });
            
            // Add selected class to matching date cell if it's not today
            const matchingCell = document.querySelector(`.calendar-cell[data-date="${selectedDate}"]`);
            if (matchingCell && !matchingCell.classList.contains('today')) {
                matchingCell.classList.add('selected');
                selectedCell = matchingCell;
                
                // Update URL
                const url = new URL(window.location);
                url.searchParams.set('selected_date', selectedDate);
                window.history.replaceState({}, '', url);
            } else if (matchingCell && matchingCell.classList.contains('today')) {
                // If selecting today, clear the selected cell
                selectedCell = null;
                // Update URL with today's date
                const url = new URL(window.location);
                url.searchParams.set('selected_date', selectedDate);
                window.history.replaceState({}, '', url);
            }
        });
    }
    
    // Steps input validation
    const stepsInput = document.getElementById('native_steps_count');
    if (stepsInput) {
        stepsInput.addEventListener('input', function() {
            let value = parseInt(this.value);
            if (isNaN(value)) return;
            
            if (value > 100000) {
                this.value = 100000;
            } else if (value < 0) {
                this.value = '';
            }
        });
    }
    
    // Update viewport height for mobile
    function updateViewportHeight() {
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }
    
    window.addEventListener('resize', updateViewportHeight);
    window.addEventListener('orientationchange', updateViewportHeight);
    updateViewportHeight();
    
    // Smooth scroll to top when header is clicked
    const header = document.querySelector('.native-app-header');
    if (header) {
        header.addEventListener('click', function(e) {
            if (e.target === this || e.target.classList.contains('app-title')) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }
});
</script>

<?php require_once 'footer.php'; ?>