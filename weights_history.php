<?php
date_default_timezone_set("Asia/Hong_Kong");

$pageTitle = "Weight History";
require_once 'header.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get weight history
$weights_query = "SELECT entry_date, weight_kg FROM weights 
                 WHERE user_id = ? 
                 ORDER BY entry_date DESC";
$stmt = $db->prepare($weights_query);
$stmt->execute([$user_id]);
$weights = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate weekly averages (Sunday to Saturday)
$weekly_data = [];
foreach ($weights as $weight) {
    $week_start = date('Y-m-d', strtotime('sunday last week', strtotime($weight['entry_date'])));
    $week_end = date('Y-m-d', strtotime('saturday this week', strtotime($weight['entry_date'])));
    $week_key = $week_start . '_' . $week_end;
    
    if (!isset($weekly_data[$week_key])) {
        $weekly_data[$week_key] = [
            'week_start' => $week_start,
            'week_end' => $week_end,
            'weights' => [],
            'average' => 0
        ];
    }
    
    $weekly_data[$week_key]['weights'][] = (float)$weight['weight_kg'];
}

// Calculate monthly averages
$monthly_data = [];
foreach ($weights as $weight) {
    $month_start = date('Y-m-01', strtotime($weight['entry_date']));
    $month_end = date('Y-m-t', strtotime($weight['entry_date']));
    $month_key = $month_start . '_' . $month_end;
    
    if (!isset($monthly_data[$month_key])) {
        $monthly_data[$month_key] = [
            'month_start' => $month_start,
            'month_end' => $month_end,
            'weights' => [],
            'average' => 0
        ];
    }
    
    $monthly_data[$month_key]['weights'][] = (float)$weight['weight_kg'];
}

// Calculate yearly averages
$yearly_data = [];
foreach ($weights as $weight) {
    $year_start = date('Y-01-01', strtotime($weight['entry_date']));
    $year_end = date('Y-12-31', strtotime($weight['entry_date']));
    $year_key = $year_start . '_' . $year_end;
    
    if (!isset($yearly_data[$year_key])) {
        $yearly_data[$year_key] = [
            'year_start' => $year_start,
            'year_end' => $year_end,
            'weights' => [],
            'average' => 0
        ];
    }
    
    $yearly_data[$year_key]['weights'][] = (float)$weight['weight_kg'];
}

// Calculate averages for all periods
foreach ($weekly_data as $key => $week) {
    $weekly_data[$key]['average'] = array_sum($week['weights']) / count($week['weights']);
}

foreach ($monthly_data as $key => $month) {
    $monthly_data[$key]['average'] = array_sum($month['weights']) / count($month['weights']);
}

foreach ($yearly_data as $key => $year) {
    $yearly_data[$key]['average'] = array_sum($year['weights']) / count($year['weights']);
}

// Sort data by date descending
usort($weekly_data, function($a, $b) {
    return strcmp($b['week_start'], $a['week_start']);
});

usort($monthly_data, function($a, $b) {
    return strcmp($b['month_start'], $a['month_start']);
});

usort($yearly_data, function($a, $b) {
    return strcmp($b['year_start'], $a['year_start']);
});

// Prepare data for charts
$chart_data = [];
foreach ($weights as $weight) {
    $chart_data[] = [
        'date' => $weight['entry_date'],
        'weight' => (float)$weight['weight_kg']
    ];
}
$chart_json = json_encode(array_reverse($chart_data));

// Prepare weekly data for chart
$weekly_chart_data = [];
foreach ($weekly_data as $week) {
    $weekly_chart_data[] = [
        'week_start' => $week['week_start'],
        'week_end' => $week['week_end'],
        'average' => round($week['average'], 1)
    ];
}
$weekly_chart_json = json_encode(array_reverse($weekly_chart_data));

// Prepare monthly data for chart
$monthly_chart_data = [];
foreach ($monthly_data as $month) {
    $monthly_chart_data[] = [
        'month_start' => $month['month_start'],
        'month_end' => $month['month_end'],
        'average' => round($month['average'], 1)
    ];
}
$monthly_chart_json = json_encode(array_reverse($monthly_chart_data));

// Prepare yearly data for chart
$yearly_chart_data = [];
foreach ($yearly_data as $year) {
    $yearly_chart_data[] = [
        'year_start' => $year['year_start'],
        'year_end' => $year['year_end'],
        'average' => round($year['average'], 1)
    ];
}
$yearly_chart_json = json_encode(array_reverse($yearly_chart_data));

// Check for success message
$success_message = $_GET['message'] ?? '';
?>

<!-- Premium Background -->
<div class="premium-bg"></div>
<div class="particles-container" id="particles-container"></div>

<!-- Native Mobile App Container -->
<div class="native-app-container" id="nativeAppContainer">
    
    <!-- Sticky Header -->
    <header class="native-app-header">
        <div class="header-content">
            <h1 class="app-header-title">Weight Progress</h1>
            <a href="weights_add.php" class="native-icon-button" aria-label="Add Weight">
                <i class="fas fa-plus"></i>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="native-app-content" id="mainContent">
        
        <?php if ($success_message): ?>
        <div class="native-toast success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
        <?php endif; ?>

        <!-- Stats Overview Cards -->
        <?php if ($weights): ?>
        <section class="native-section">
            <div class="native-stats-grid">
                <div class="native-stat-card">
                    <div class="stat-label">Current</div>
                    <div class="stat-value"><?php echo $weights[0]['weight_kg']; ?> kg</div>
                    <div class="stat-date"><?php echo date('M j', strtotime($weights[0]['entry_date'])); ?></div>
                </div>
                <?php if (count($weights) > 1): ?>
                <div class="native-stat-card">
                    <div class="stat-label">Change</div>
                    <?php 
                    $change = $weights[0]['weight_kg'] - $weights[1]['weight_kg'];
                    $trend = $change >= 0 ? 'up' : 'down';
                    ?>
                    <div class="stat-value trend-<?php echo $trend; ?>">
                        <?php echo $trend === 'up' ? '+' : ''; ?><?php echo number_format($change, 1); ?> kg
                    </div>
                    <div class="stat-date">vs previous</div>
                </div>
                <?php endif; ?>
                <div class="native-stat-card">
                    <div class="stat-label">Entries</div>
                    <div class="stat-value"><?php echo count($weights); ?></div>
                    <div class="stat-date">total</div>
                </div>
            </div>
        </section>

        <!-- Chart Section -->
        <section class="native-section">
            <div class="native-pill-tabs">
                <button class="pill-tab active" onclick="switchChart('daily')" aria-label="Daily view">
                    <span>Daily</span>
                </button>
                <button class="pill-tab" onclick="switchChart('weekly')" aria-label="Weekly view">
                    <span>Weekly</span>
                </button>
                <button class="pill-tab" onclick="switchChart('monthly')" aria-label="Monthly view">
                    <span>Monthly</span>
                </button>
                <button class="pill-tab" onclick="switchChart('yearly')" aria-label="Yearly view">
                    <span>Yearly</span>
                </button>
            </div>
            
            <div class="native-chart-container active" id="dailyChart">
                <canvas id="chartCanvas"></canvas>
            </div>
            
            <div class="native-chart-container" id="weeklyChart">
                <canvas id="weeklyChartCanvas"></canvas>
            </div>
            
            <div class="native-chart-container" id="monthlyChart">
                <canvas id="monthlyChartCanvas"></canvas>
            </div>
            
            <div class="native-chart-container" id="yearlyChart">
                <canvas id="yearlyChartCanvas"></canvas>
            </div>
        </section>

        <!-- Time Period Sections -->
        <section class="native-section">
            <h2 class="native-section-title">Weekly Averages</h2>
            <?php if ($weekly_data): ?>
                <div class="native-list">
                    <?php foreach ($weekly_data as $week): ?>
                    <div class="native-list-item">
                        <div class="list-item-main">
                            <div class="list-item-title">
                                <?php echo date('F j', strtotime($week['week_start'])); ?> - <?php echo date('j', strtotime($week['week_end'])); ?>
                            </div>
                            <div class="list-item-subtitle">
                                <?php echo date('M j', strtotime($week['week_start'])); ?> (Sun) - <?php echo date('M j, Y', strtotime($week['week_end'])); ?> (Sat)
                            </div>
                        </div>
                        <div class="list-item-trailing">
                            <span class="weight-value"><?php echo round($week['average'], 1); ?> kg</span>
                            <span class="reading-count"><?php echo count($week['weights']); ?> reading<?php echo count($week['weights']) > 1 ? 's' : ''; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="native-empty-state">
                    <i class="fas fa-chart-line"></i>
                    <p>No weekly data yet</p>
                </div>
            <?php endif; ?>
        </section>

        <section class="native-section">
            <h2 class="native-section-title">Monthly Averages</h2>
            <?php if ($monthly_data): ?>
                <div class="native-list">
                    <?php foreach ($monthly_data as $month): ?>
                    <div class="native-list-item">
                        <div class="list-item-main">
                            <div class="list-item-title"><?php echo date('F Y', strtotime($month['month_start'])); ?></div>
                            <div class="list-item-subtitle">
                                <?php echo date('M j', strtotime($month['month_start'])); ?> - <?php echo date('M j, Y', strtotime($month['month_end'])); ?>
                            </div>
                        </div>
                        <div class="list-item-trailing">
                            <span class="weight-value"><?php echo round($month['average'], 1); ?> kg</span>
                            <span class="reading-count"><?php echo count($month['weights']); ?> reading<?php echo count($month['weights']) > 1 ? 's' : ''; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="native-empty-state">
                    <i class="fas fa-chart-column"></i>
                    <p>No monthly data yet</p>
                </div>
            <?php endif; ?>
        </section>

        <section class="native-section">
            <h2 class="native-section-title">Daily History</h2>
            <?php if ($weights): ?>
                <div class="native-list">
                    <?php foreach ($weights as $weight): ?>
                    <div class="native-list-item">
                        <div class="list-item-main">
                            <div class="list-item-title"><?php echo date('F j, Y', strtotime($weight['entry_date'])); ?></div>
                            <div class="list-item-subtitle"><?php echo date('l', strtotime($weight['entry_date'])); ?></div>
                        </div>
                        <div class="list-item-trailing">
                            <span class="weight-value"><?php echo $weight['weight_kg']; ?> kg</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="native-empty-state">
                    <i class="fas fa-weight-scale"></i>
                    <p>No weight entries yet</p>
                    <a href="weights_add.php" class="native-button primary">
                        <i class="fas fa-plus"></i>
                        <span>Add First Entry</span>
                    </a>
                </div>
            <?php endif; ?>
        </section>
        
        <?php else: ?>
        <!-- Empty State -->
        <section class="native-empty-section">
            <div class="native-empty-state large">
                <i class="fas fa-weight-scale"></i>
                <h2>No Weight Data</h2>
                <p>Start tracking your weight to see progress over time</p>
                <a href="weights_add.php" class="native-button primary large">
                    <i class="fas fa-plus"></i>
                    <span>Add First Weight Entry</span>
                </a>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Bottom Safe Area Spacer -->
        <div class="safe-area-bottom"></div>
    </main>

    <!-- Floating Action Button -->
    <?php if ($weights): ?>
    <a href="weights_add.php" class="native-fab" aria-label="Add weight entry">
        <i class="fas fa-plus"></i>
    </a>
    <?php endif; ?>
</div>

<?php if ($weights): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Disable zoom gestures for native app feel
document.addEventListener('touchstart', function(e) {
    if (e.touches.length > 1) {
        e.preventDefault();
    }
}, { passive: false });

let lastTouchEnd = 0;
document.addEventListener('touchend', function(e) {
    const now = (new Date()).getTime();
    if (now - lastTouchEnd <= 300) {
        e.preventDefault();
    }
    lastTouchEnd = now;
}, false);

document.documentElement.style.touchAction = 'manipulation';

// Charts initialization
const chartData = <?php echo $chart_json; ?>;
const weeklyChartData = <?php echo $weekly_chart_json; ?>;
const monthlyChartData = <?php echo $monthly_chart_json; ?>;
const yearlyChartData = <?php echo $yearly_chart_json; ?>;

// Chart configuration
// Chart configuration with fixed tooltip colors
const chartConfig = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false
    },
    plugins: {
        legend: { display: false },
        tooltip: {
            enabled: true,
            mode: 'nearest',
            intersect: false,
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            borderColor: 'rgba(255, 255, 255, 0.2)',
            borderWidth: 1,
            cornerRadius: 8,
            padding: 12,
            displayColors: false,
            callbacks: {
                label: function(context) {
                    return `Weight: ${context.parsed.y} kg`;
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: false,
            grid: { color: 'var(--border-color)' },
            ticks: { color: 'var(--light-text)' }
        },
        x: {
            grid: { display: false },
            ticks: { color: 'var(--light-text)', maxTicksLimit: 6 }
        }
    },
    elements: {
        point: {
            radius: 4,
            hoverRadius: 6,
            borderColor: '#ffffff',
            borderWidth: 2
        },
        line: {
            tension: 0.4,
            borderWidth: 3
        }
    }
};

// Daily Chart - Blue/Indigo color
const ctx = document.getElementById('chartCanvas').getContext('2d');
const weightChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: chartData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }),
        datasets: [{
            data: chartData.map(item => item.weight),
            borderColor: '#1a237e', // Dark blue/indigo
            backgroundColor: 'rgba(26, 35, 126, 0.1)',
            fill: true,
            pointBackgroundColor: '#1a237e',
            pointBorderColor: '#ffffff',
        }]
    },
    options: chartConfig
});

// Weekly Chart - Green color
const weeklyCtx = document.getElementById('weeklyChartCanvas').getContext('2d');
const weeklyWeightChart = new Chart(weeklyCtx, {
    type: 'line',
    data: {
        labels: weeklyChartData.map(item => {
            const startDate = new Date(item.week_start);
            return startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }),
        datasets: [{
            data: weeklyChartData.map(item => item.average),
            borderColor: '#4caf50', // Green
            backgroundColor: 'rgba(76, 175, 80, 0.1)',
            fill: true,
            pointBackgroundColor: '#4caf50',
            pointBorderColor: '#ffffff',
        }]
    },
    options: {
        ...chartConfig,
        plugins: {
            ...chartConfig.plugins,
            tooltip: {
                ...chartConfig.plugins.tooltip,
                callbacks: {
                    title: function(tooltipItems) {
                        const index = tooltipItems[0].dataIndex;
                        const week = weeklyChartData[index];
                        const startDate = new Date(week.week_start);
                        const endDate = new Date(week.week_end);
                        return `Week ${startDate.getDate()} - ${endDate.getDate()}`;
                    },
                    label: function(context) {
                        return `Average: ${context.parsed.y} kg`;
                    }
                }
            }
        }
    }
});

// Monthly Chart - Orange color
const monthlyCtx = document.getElementById('monthlyChartCanvas').getContext('2d');
const monthlyWeightChart = new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: monthlyChartData.map(item => {
            const date = new Date(item.month_start);
            return date.toLocaleDateString('en-US', { month: 'short' });
        }),
        datasets: [{
            data: monthlyChartData.map(item => item.average),
            borderColor: '#ff9800', // Orange
            backgroundColor: 'rgba(255, 152, 0, 0.1)',
            fill: true,
            pointBackgroundColor: '#ff9800',
            pointBorderColor: '#ffffff',
        }]
    },
    options: {
        ...chartConfig,
        plugins: {
            ...chartConfig.plugins,
            tooltip: {
                ...chartConfig.plugins.tooltip,
                callbacks: {
                    label: function(context) {
                        return `Average: ${context.parsed.y} kg`;
                    }
                }
            }
        }
    }
});

// Yearly Chart - Purple color
const yearlyCtx = document.getElementById('yearlyChartCanvas').getContext('2d');
const yearlyWeightChart = new Chart(yearlyCtx, {
    type: 'line',
    data: {
        labels: yearlyChartData.map(item => {
            const date = new Date(item.year_start);
            return date.getFullYear().toString();
        }),
        datasets: [{
            data: yearlyChartData.map(item => item.average),
            borderColor: '#9c27b0', // Purple
            backgroundColor: 'rgba(156, 39, 176, 0.1)',
            fill: true,
            pointBackgroundColor: '#9c27b0',
            pointBorderColor: '#ffffff',
        }]
    },
    options: {
        ...chartConfig,
        plugins: {
            ...chartConfig.plugins,
            tooltip: {
                ...chartConfig.plugins.tooltip,
                callbacks: {
                    label: function(context) {
                        return `Average: ${context.parsed.y} kg`;
                    }
                }
            }
        }
    }
});

// Chart switching with smooth transition
function switchChart(type) {
    const charts = ['daily', 'weekly', 'monthly', 'yearly'];
    const tabs = document.querySelectorAll('.pill-tab');
    
    // Update tabs
    tabs.forEach((tab, index) => {
        if (charts[index] === type) {
            tab.classList.add('active');
        } else {
            tab.classList.remove('active');
        }
    });
    
    // Update charts with transition
    charts.forEach(chart => {
        const element = document.getElementById(chart + 'Chart');
        if (element) {
            if (chart === type) {
                element.style.opacity = '0';
                element.classList.add('active');
                setTimeout(() => {
                    element.style.opacity = '1';
                }, 10);
            } else {
                element.classList.remove('active');
                element.style.opacity = '0';
            }
        }
    });
    
    // Trigger resize after transition
    setTimeout(() => {
        weightChart.resize();
        weeklyWeightChart.resize();
        monthlyWeightChart.resize();
        yearlyWeightChart.resize();
    }, 300);
}

// Handle window resize
let resizeTimeout;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
        weightChart.resize();
        weeklyWeightChart.resize();
        monthlyWeightChart.resize();
        yearlyWeightChart.resize();
    }, 150);
});

// Add touch feedback to interactive elements
document.querySelectorAll('.pill-tab, .native-list-item, .native-button').forEach(element => {
    element.addEventListener('touchstart', function() {
        this.classList.add('touch-active');
    });
    
    element.addEventListener('touchend', function() {
        this.classList.remove('touch-active');
    });
    
    element.addEventListener('touchcancel', function() {
        this.classList.remove('touch-active');
    });
});

// Smooth scroll behavior
document.documentElement.style.scrollBehavior = 'smooth';
</script>

<style>
/* Native Mobile App Styles */
:root {
    --accent-rgb: 26, 35, 126;
    --safe-area-top: env(safe-area-inset-top, 0px);
    --safe-area-bottom: env(safe-area-inset-bottom, 0px);
}

/* Disable zoom */
html {
    touch-action: manipulation;
    -webkit-text-size-adjust: 100%;
    -ms-text-size-adjust: 100%;
    overflow-x: hidden;
}

body {
    margin: 0;
    padding: 0;
    background: var(--bg-color);
    overscroll-behavior-y: none;
    -webkit-overflow-scrolling: touch;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Inter', sans-serif;
}

/* Native App Container */
.native-app-container {
    position: relative;
    max-width: 768px;
    margin: 0 auto;
    background: var(--bg-color);
    min-height: 100vh;
    overflow-x: hidden;
}

/* Sticky Header */
.native-app-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: var(--card-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border-color);
    padding: calc(12px + var(--safe-area-top)) 16px 12px;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 44px;
}

.app-header-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-color);
    margin: 0;
    letter-spacing: -0.3px;
}

.native-icon-button {
    width: 44px;
    height: 44px;
    border-radius: 22px;
    background: var(--accent);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    border: none;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    -webkit-tap-highlight-color: transparent;
}

.native-icon-button:active {
    transform: scale(0.95);
    opacity: 0.8;
}

/* Main Content */
.native-app-content {
    padding: 16px;
    padding-bottom: calc(16px + var(--safe-area-bottom));
}

/* Sections */
.native-section {
    margin-bottom: 32px;
    animation: fadeInUp 0.4s ease-out;
}

.native-section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-color);
    margin: 0 0 16px 0;
    letter-spacing: -0.2px;
}

/* Stats Grid */
.native-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 24px;
}

.native-stat-card {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 16px;
    border: 1px solid var(--border-color);
    text-align: center;
    transition: all 0.2s ease;
}

.native-stat-card:active {
    transform: scale(0.98);
    opacity: 0.9;
}

.stat-label {
    font-size: 0.75rem;
    color: var(--light-text);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-color);
    margin-bottom: 4px;
    line-height: 1.2;
}

.stat-date {
    font-size: 0.75rem;
    color: var(--light-text);
}

.trend-up {
    color: #4caf50;
}

.trend-down {
    color: #f44336;
}

/* Pill Tabs */
.native-pill-tabs {
    display: flex;
    background: var(--border-color);
    border-radius: 24px;
    padding: 4px;
    margin-bottom: 24px;
}

.pill-tab {
    flex: 1;
    background: transparent;
    border: none;
    padding: 12px 16px;
    border-radius: 20px;
    font-size: 0.9375rem;
    font-weight: 500;
    color: var(--light-text);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    min-height: 44px;
    -webkit-tap-highlight-color: transparent;
    cursor: pointer;
}

.pill-tab.active {
    background: var(--accent);
    color: white;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.pill-tab:active:not(.active) {
    opacity: 0.6;
}

/* Charts */
.native-chart-container {
    background: var(--card-bg);
    border-radius: 20px;
    padding: 20px;
    margin-bottom: 16px;
    border: 1px solid var(--border-color);
    height: 280px;
    opacity: 0;
    transition: opacity 0.3s ease;
    position: absolute;
    width: calc(100% - 32px);
    pointer-events: none;
}

.native-chart-container.active {
    opacity: 1;
    position: relative;
    pointer-events: auto;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.native-chart-container canvas {
    width: 100% !important;
    height: 100% !important;
}

/* Lists */
.native-list {
    background: var(--card-bg);
    border-radius: 16px;
    border: 1px solid var(--border-color);
    overflow: hidden;
}

.native-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    min-height: 60px;
    border-bottom: 1px solid var(--border-color);
    transition: all 0.2s ease;
    -webkit-tap-highlight-color: transparent;
}

.native-list-item:last-child {
    border-bottom: none;
}

.native-list-item:active {
    background: rgba(var(--accent-rgb), 0.05);
}

.list-item-main {
    flex: 1;
    min-width: 0;
}

.list-item-title {
    font-size: 1rem;
    font-weight: 500;
    color: var(--text-color);
    margin-bottom: 2px;
    line-height: 1.3;
}

.list-item-subtitle {
    font-size: 0.8125rem;
    color: var(--light-text);
    line-height: 1.3;
}

.list-item-trailing {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 2px;
    margin-left: 12px;
}

.weight-value {
    font-size: 1.0625rem;
    font-weight: 600;
    color: var(--accent);
    white-space: nowrap;
}

.reading-count {
    font-size: 0.75rem;
    color: var(--light-text);
    white-space: nowrap;
}

/* Empty States */
.native-empty-state {
    text-align: center;
    padding: 48px 24px;
}

.native-empty-state.large {
    padding: 80px 24px;
}

.native-empty-state i {
    font-size: 3rem;
    color: var(--light-text);
    margin-bottom: 16px;
    opacity: 0.5;
}

.native-empty-state h2 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-color);
    margin: 0 0 8px 0;
}

.native-empty-state p {
    font-size: 0.9375rem;
    color: var(--light-text);
    margin: 0 0 24px 0;
    line-height: 1.4;
}

/* Buttons */
.native-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: var(--accent);
    color: white;
    border: none;
    border-radius: 14px;
    padding: 16px 24px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    min-height: 44px;
    -webkit-tap-highlight-color: transparent;
}

.native-button.primary {
    background: var(--accent);
}

.native-button.large {
    padding: 18px 32px;
    font-size: 1.0625rem;
}

.native-button:active {
    transform: scale(0.96);
    opacity: 0.9;
}

/* Floating Action Button */
.native-fab {
    position: fixed;
    bottom: calc(24px + var(--safe-area-bottom));
    right: 24px;
    width: 56px;
    height: 56px;
    border-radius: 28px;
    background: var(--accent);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    box-shadow: 0 4px 20px rgba(var(--accent-rgb), 0.3);
    border: none;
    z-index: 1000;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    -webkit-tap-highlight-color: transparent;
}

.native-fab:active {
    transform: scale(0.92);
    box-shadow: 0 2px 10px rgba(var(--accent-rgb), 0.4);
}

/* Toast */
.native-toast {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 12px 16px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideInDown 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.native-toast.success {
    border-left: 4px solid #4caf50;
}

.native-toast i {
    color: #4caf50;
    font-size: 1.25rem;
}

.native-toast span {
    flex: 1;
    font-size: 0.9375rem;
    color: var(--text-color);
    line-height: 1.4;
}

/* Safe Area */
.safe-area-bottom {
    height: var(--safe-area-bottom);
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Touch Feedback */
.touch-active {
    opacity: 0.7 !important;
}

/* Responsive */
@media (max-width: 480px) {
    .native-stats-grid {
        grid-template-columns: 1fr;
        gap: 8px;
    }
    
    .native-chart-container {
        height: 240px;
        padding: 16px;
    }
    
    .native-section-title {
        font-size: 1.0625rem;
    }
    
    .native-fab {
        bottom: calc(20px + var(--safe-area-bottom));
        right: 20px;
        width: 52px;
        height: 52px;
    }
}

@media (min-width: 481px) and (max-width: 768px) {
    .native-stats-grid {
        gap: 16px;
    }
    
    .native-chart-container {
        height: 260px;
    }
}

/* Dark mode adjustments */
@media (prefers-color-scheme: dark) {
    .native-app-header {
        background: rgba(var(--card-bg-rgb), 0.8);
    }
    
    .native-stat-card,
    .native-chart-container,
    .native-list {
        background: rgba(var(--card-bg-rgb), 0.9);
    }
    
    .pill-tab.active {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }
}

/* Performance optimizations */
.native-app-container * {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Hide scrollbar but keep functionality */
.native-app-content::-webkit-scrollbar {
    display: none;
}

.native-app-content {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
<?php endif; ?>

<?php require_once 'footer.php'; ?>