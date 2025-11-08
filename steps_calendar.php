<?php
date_default_timezone_set("Asia/Hong_Kong");

$pageTitle = "Steps Calendar";
require_once 'header.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$current_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Check for success message
$success_message = $_GET['message'] ?? '';

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

<?php if ($success_message): ?>
<div class="card">
    <div class="message success">
        <?php echo htmlspecialchars($success_message); ?>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <!-- Calendar Header -->
    <div class="calendar-header-compact">
        <div class="calendar-nav-section">
            <a href="steps_calendar.php?month=<?php echo $prev_month; ?>" class="nav-arrow next-btn">
                <i class="fas fa-chevron-left"></i>
            </a>
            
            <div class="calendar-title">
                <div class="month-display">
                    <?php echo date('M Y', strtotime($current_month)); ?>
                </div>
            </div>
            
            <a href="steps_calendar.php?month=<?php echo $next_month; ?>" class="nav-arrow prev-btn">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        
        <div class="calendar-stats-section">
            <div class="month-steps-total">
                <?php echo number_format($summary['total_steps'] ?? 0); ?> steps
            </div>
        </div>
    </div>

    <!-- Calendar Grid -->
    <div class="calendar-container">
        <!-- Day headers - Sunday first -->
        <div class="calendar-days-header">
            <div class="day-header">S</div>
            <div class="day-header">M</div>
            <div class="day-header">T</div>
            <div class="day-header">W</div>
            <div class="day-header">T</div>
            <div class="day-header">F</div>
            <div class="day-header">S</div>
        </div>
        
        <!-- Calendar grid -->
        <div class="calendar-grid">
            <!-- Empty cells for days before the first day of month -->
            <?php for ($i = 0; $i < $first_day; $i++): ?>
                <div class="calendar-day empty"></div>
            <?php endfor; ?>
            
            <!-- Days of the month -->
            <?php for ($day = 1; $day <= $days_in_month; $day++): 
                $current_date = date('Y-m-d', strtotime($current_month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT)));
                $steps = $steps_by_date[$current_date] ?? 0;
                $is_today = $current_date == date('Y-m-d');
                $is_weekend = in_array(date('w', strtotime($current_date)), [0, 6]); // 0=Sun, 6=Sat
                $steps_class = '';
                
                if ($steps > 0) {
                    if ($steps < 5000) $steps_class = 'steps-low';
                    elseif ($steps < 10000) $steps_class = 'steps-medium';
                    else $steps_class = 'steps-high';
                }
            ?>
                <div class="calendar-day <?php echo $is_today ? 'today' : ''; ?> <?php echo $is_weekend ? 'weekend' : ''; ?> <?php echo $steps_class; ?>">
                    <div class="day-number"><?php echo $day; ?></div>
                    <?php if ($steps > 0): ?>
                        <div class="steps-indicator"><?php echo number_format($steps); ?></div>
                    <?php else: ?>
                        <div class="no-steps">-</div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- Add Steps Card -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Add Steps Entry</h2>
    </div>
    <div class="add-steps-form">
        <form action="steps_add.php" method="POST" class="steps-form">
            <div class="form-group">
                <label for="entry_date">Date</label>
                <input type="date" id="entry_date" name="entry_date" value="<?php echo date('Y-m-d'); ?>" required class="form-input">
            </div>
            <div class="form-group">
                <label for="steps_count">Steps Count</label>
                <input type="number" id="steps_count" name="steps_count" min="1" max="100000" placeholder="Enter steps count" required class="form-input">
            </div>
            <button type="submit" class="btn btn-primary btn-full">
                <i class="fas fa-plus"></i> Add Steps
            </button>
        </form>
    </div>
</div>

<!-- Monthly Summary -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Monthly Summary - <?php echo date('M Y', strtotime($current_month)); ?></h2>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-shoe-prints"></i>
            </div>
            <div class="stat-number"><?php echo number_format($summary['total_steps'] ?? 0); ?></div>
            <div class="stat-label">Total Steps</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-number"><?php echo number_format(round($summary['avg_steps'] ?? 0)); ?></div>
            <div class="stat-label">Daily Average</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="stat-number"><?php echo number_format($summary['max_steps'] ?? 0); ?></div>
            <div class="stat-label">Most Steps</div>
        </div>
    </div>
</div>

<style>
/* Calendar Header */
.calendar-header-compact {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--glass-border);
}

.calendar-nav-section {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.calendar-stats-section {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex: 1;
}

.nav-arrow {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    background: var(--glass-bg);
    border-radius: var(--radius);
    text-decoration: none;
    color: var(--text);
    transition: all 0.3s ease;
    border: 1px solid var(--glass-border);
    flex-shrink: 0;
}

.nav-arrow:hover {
    background: var(--accent);
    color: white;
    transform: translateY(-2px);
}

.next-btn {
    color: var(--accent);
    order: 1;
}

.prev-btn {
    color: var(--accent);
    order: 3;
}

.calendar-title {
    order: 2;
    flex: 1;
    text-align: center;
}

.month-display {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--accent);
}

.month-steps-total {
    font-size: 1rem;
    color: var(--accent);
    font-weight: 600;
    background: var(--glass-bg);
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius);
    border: 1px solid var(--glass-border);
    backdrop-filter: blur(10px);
}

/* Calendar Container - Mobile Responsive */
.calendar-container {
    background: var(--glass-bg);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    border: 1px solid var(--glass-border);
    backdrop-filter: blur(10px);
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.calendar-days-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.5rem;
    margin-bottom: 1rem;
    min-width: 300px;
}

.day-header {
    text-align: center;
    font-weight: 600;
    padding: 0.75rem 0;
    color: var(--accent);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.5rem;
    min-width: 300px;
}

.calendar-day {
    aspect-ratio: 1;
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.8);
    border-radius: var(--radius);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    position: relative;
    border: 1px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
    min-height: 60px;
}

.calendar-day.empty {
    background: transparent;
    border: 1px dashed var(--glass-border);
    backdrop-filter: none;
}

.calendar-day.today {
    background: var(--gradient-accent);
    color: white;
    box-shadow: 0 4px 12px rgba(var(--accent-rgb), 0.3);
    transform: scale(1.05);
}

.calendar-day.weekend {
    background: rgba(var(--accent-rgb), 0.05);
}

.calendar-day:hover:not(.empty) {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    background: rgba(255, 255, 255, 0.9);
}

.calendar-day.today:hover {
    background: var(--gradient-accent);
    transform: translateY(-2px) scale(1.07);
}

.day-number {
    font-weight: 600;
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
}

.steps-indicator {
    color: var(--primary);
    font-size: 0.75rem;
    font-weight: 600;
    text-align: center;
    line-height: 1;
}

.no-steps {
    color: var(--text-light);
    font-size: 0.75rem;
    opacity: 0.5;
}

/* Steps color coding */
.calendar-day.steps-low .steps-indicator {
    color: #ff6b6b;
}

.calendar-day.steps-medium .steps-indicator {
    color: #ffa726;
}

.calendar-day.steps-high .steps-indicator {
    color: #4caf50;
}

.calendar-day.today .steps-indicator {
    color: white;
}

/* Add Steps Form */
.add-steps-form {
    padding: 1rem 0;
}

.steps-form {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 1rem;
    align-items: end;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text);
    font-size: 0.9rem;
}

.form-input {
    padding: 0.75rem 1rem;
    border: 1px solid var(--glass-border);
    border-radius: var(--radius);
    background: var(--glass-bg);
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-input:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.1);
}

.btn-full {
    width: 100%;
    padding: 0.75rem 1.5rem;
}

/* Stats grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.stat-card {
    background: var(--glass-bg);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    border: 1px solid var(--glass-border);
    backdrop-filter: blur(10px);
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    font-size: 1.5rem;
    color: var(--accent);
    margin-bottom: 0.75rem;
    opacity: 0.9;
}

.stat-number {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--accent);
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.9rem;
    color: var(--text-light);
    font-weight: 500;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .calendar-header-compact {
        flex-direction: column;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .calendar-nav-section {
        width: 100%;
        justify-content: space-between;
    }
    
    .calendar-stats-section {
        width: 100%;
        justify-content: center;
    }
    
    .month-display {
        font-size: 1.3rem;
    }
    
    .month-steps-total {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
    
    .nav-arrow {
        width: 2.25rem;
        height: 2.25rem;
    }
    
    .calendar-container {
        padding: 1rem;
        border-radius: var(--radius);
        margin: 0 -0.5rem;
    }
    
    .calendar-days-header {
        gap: 0.25rem;
        margin-bottom: 0.75rem;
        min-width: 280px;
    }
    
    .day-header {
        padding: 0.5rem 0;
        font-size: 0.75rem;
    }
    
    .calendar-grid {
        gap: 0.25rem;
        min-width: 280px;
    }
    
    .calendar-day {
        padding: 0.5rem;
        border-radius: var(--radius-sm);
        min-height: 50px;
    }
    
    .day-number {
        font-size: 0.8rem;
        margin-bottom: 0.15rem;
    }
    
    .steps-indicator, .no-steps {
        font-size: 0.7rem;
    }
    
    .steps-form {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .stat-card {
        padding: 1.25rem 1rem;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
    
    .stat-icon {
        font-size: 1.25rem;
        margin-bottom: 0.5rem;
    }
}

@media (max-width: 480px) {
    .calendar-container {
        padding: 0.75rem;
    }
    
    .calendar-days-header {
        gap: 0.15rem;
        min-width: 260px;
    }
    
    .day-header {
        padding: 0.4rem 0;
        font-size: 0.7rem;
    }
    
    .calendar-grid {
        gap: 0.15rem;
        min-width: 260px;
    }
    
    .calendar-day {
        padding: 0.4rem;
        min-height: 45px;
    }
    
    .day-number {
        font-size: 0.75rem;
    }
    
    .steps-indicator, .no-steps {
        font-size: 0.65rem;
    }
    
    .month-display {
        font-size: 1.2rem;
    }
    
    .month-steps-total {
        font-size: 0.85rem;
    }
    
    .nav-arrow {
        width: 2rem;
        height: 2rem;
    }
}

@media (max-width: 360px) {
    .calendar-container {
        padding: 0.5rem;
    }
    
    .calendar-days-header {
        gap: 0.1rem;
        min-width: 240px;
    }
    
    .day-header {
        padding: 0.3rem 0;
        font-size: 0.65rem;
    }
    
    .calendar-grid {
        gap: 0.1rem;
        min-width: 240px;
    }
    
    .calendar-day {
        padding: 0.3rem;
        min-height: 40px;
    }
    
    .day-number {
        font-size: 0.7rem;
    }
    
    .steps-indicator, .no-steps {
        font-size: 0.6rem;
    }
    
    .month-display {
        font-size: 1.1rem;
    }
    
    .month-steps-total {
        font-size: 0.8rem;
    }
    
    .nav-arrow {
        width: 1.75rem;
        height: 1.75rem;
    }
}

/* Ensure horizontal scrolling works properly */
.calendar-container::-webkit-scrollbar {
    height: 6px;
}

.calendar-container::-webkit-scrollbar-track {
    background: var(--glass-bg);
    border-radius: 3px;
}

.calendar-container::-webkit-scrollbar-thumb {
    background: var(--accent);
    border-radius: 3px;
}

.calendar-container::-webkit-scrollbar-thumb:hover {
    background: var(--accent-dark);
}

/* Maintain theme consistency */
.calendar-day {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
}

.calendar-day.today {
    background: var(--gradient-accent);
}

.calendar-day:hover:not(.empty) {
    background: var(--glass-bg-hover);
}
</style>

<?php require_once 'footer.php'; ?>