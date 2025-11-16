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

<?php if ($success_message): ?>
<div class="card">
    <div class="message success">
        <?php echo htmlspecialchars($success_message); ?>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header mobile-card-header">
        <h1 class="card-title">Weight Progress</h1>
        <a href="weights_add.php" class="btn btn-primary mobile-btn">
            <i class="fas fa-plus"></i> <span class="btn-text">Add Weight</span>
        </a>
    </div>
    
    <?php if ($weights): ?>
        <div class="chart-tabs">
            <button class="tab-btn active" onclick="switchChart('daily')">Daily</button>
            <button class="tab-btn" onclick="switchChart('weekly')">Weekly</button>
            <button class="tab-btn" onclick="switchChart('monthly')">Monthly</button>
            <button class="tab-btn" onclick="switchChart('yearly')">Yearly</button>
        </div>
        
        <div id="dailyChart" class="chart-container">
            <div class="chart-wrapper">
                <canvas id="chartCanvas"></canvas>
            </div>
        </div>
        
        <div id="weeklyChart" class="chart-container" style="display: none;">
            <div class="chart-wrapper">
                <canvas id="weeklyChartCanvas"></canvas>
            </div>
        </div>
        
        <div id="monthlyChart" class="chart-container" style="display: none;">
            <div class="chart-wrapper">
                <canvas id="monthlyChartCanvas"></canvas>
            </div>
        </div>
        
        <div id="yearlyChart" class="chart-container" style="display: none;">
            <div class="chart-wrapper">
                <canvas id="yearlyChartCanvas"></canvas>
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-weight-scale"></i>
            <p>No weight data yet. Start tracking to see your progress!</p>
            <a href="weights_add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add First Weight Entry
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Weekly Weight Averages Card -->
<?php if ($weekly_data): ?>
<div class="card">
    <h2 class="card-title">Weekly Averages (Sunday to Saturday)</h2>
    <div class="data-grid">
        <?php foreach ($weekly_data as $week): ?>
        <div class="data-card">
            <div class="data-card-header">
                <div class="data-card-info">
                    <div class="data-card-title">
                        Week of <?php echo date('F j', strtotime($week['week_start'])); ?> - <?php echo date('j', strtotime($week['week_end'])); ?>
                    </div>
                    <div class="data-card-subtitle">
                        <?php echo date('M j', strtotime($week['week_start'])); ?> (Sun) - <?php echo date('M j, Y', strtotime($week['week_end'])); ?> (Sat)
                    </div>
                </div>
                <div class="data-card-value">
                    <?php echo round($week['average'], 1); ?> kg
                </div>
            </div>
            <div class="data-card-footer">
                <?php echo count($week['weights']); ?> reading<?php echo count($week['weights']) > 1 ? 's' : ''; ?> this week
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Monthly Weight Averages Card -->
<?php if ($monthly_data): ?>
<div class="card">
    <h2 class="card-title">Monthly Averages</h2>
    <div class="data-grid">
        <?php foreach ($monthly_data as $month): ?>
        <div class="data-card">
            <div class="data-card-header">
                <div class="data-card-info">
                    <div class="data-card-title">
                        <?php echo date('F Y', strtotime($month['month_start'])); ?>
                    </div>
                    <div class="data-card-subtitle">
                        <?php echo date('M j', strtotime($month['month_start'])); ?> - <?php echo date('M j, Y', strtotime($month['month_end'])); ?>
                    </div>
                </div>
                <div class="data-card-value">
                    <?php echo round($month['average'], 1); ?> kg
                </div>
            </div>
            <div class="data-card-footer">
                <?php echo count($month['weights']); ?> reading<?php echo count($month['weights']) > 1 ? 's' : ''; ?> this month
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Yearly Weight Averages Card -->
<?php if ($yearly_data): ?>
<div class="card">
    <h2 class="card-title">Yearly Averages</h2>
    <div class="data-grid">
        <?php foreach ($yearly_data as $year): ?>
        <div class="data-card">
            <div class="data-card-header">
                <div class="data-card-info">
                    <div class="data-card-title">
                        <?php echo date('Y', strtotime($year['year_start'])); ?>
                    </div>
                    <div class="data-card-subtitle">
                        <?php echo date('M j, Y', strtotime($year['year_start'])); ?> - <?php echo date('M j, Y', strtotime($year['year_end'])); ?>
                    </div>
                </div>
                <div class="data-card-value">
                    <?php echo round($year['average'], 1); ?> kg
                </div>
            </div>
            <div class="data-card-footer">
                <?php echo count($year['weights']); ?> reading<?php echo count($year['weights']) > 1 ? 's' : ''; ?> this year
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <h2 class="card-title">Daily Weight History</h2>
    
    <?php if ($weights): ?>
        <div class="data-grid">
            <?php foreach ($weights as $weight): ?>
            <div class="data-card">
                <div class="data-card-header">
                    <div class="data-card-info">
                        <div class="data-card-title"><?php echo date('F j, Y', strtotime($weight['entry_date'])); ?></div>
                        <div class="data-card-subtitle">
                            <?php echo date('l', strtotime($weight['entry_date'])); ?>
                        </div>
                    </div>
                    <div class="data-card-value">
                        <?php echo $weight['weight_kg']; ?> kg
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p>No weight entries yet. <a href="weights_add.php" class="btn btn-outline">Add your first weight entry</a>.</p>
        </div>
    <?php endif; ?>
</div>

<?php if ($weights): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const chartData = <?php echo $chart_json; ?>;
    const weeklyChartData = <?php echo $weekly_chart_json; ?>;
    const monthlyChartData = <?php echo $monthly_chart_json; ?>;
    const yearlyChartData = <?php echo $yearly_chart_json; ?>;
    
    // Daily Chart
    const ctx = document.getElementById('chartCanvas').getContext('2d');
    const weightChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.map(item => {
                const date = new Date(item.date);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            }),
            datasets: [{
                label: 'Weight (kg)',
                data: chartData.map(item => item.weight),
                borderColor: '#1a237e',
                backgroundColor: 'rgba(26, 35, 126, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#1a237e',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(26, 35, 126, 0.9)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#1a237e',
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    grid: {
                        color: 'rgba(26, 35, 126, 0.1)'
                    },
                    ticks: {
                        color: '#1a237e'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(26, 35, 126, 0.1)'
                    },
                    ticks: {
                        color: '#1a237e',
                        maxTicksLimit: 6
                    }
                }
            }
        }
    });
    
    // Weekly Chart
    const weeklyCtx = document.getElementById('weeklyChartCanvas').getContext('2d');
    const weeklyWeightChart = new Chart(weeklyCtx, {
        type: 'line',
        data: {
            labels: weeklyChartData.map(item => {
                const startDate = new Date(item.week_start);
                const endDate = new Date(item.week_end);
                return startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' - ' + 
                       endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            }),
            datasets: [{
                label: 'Weekly Average (kg)',
                data: weeklyChartData.map(item => item.average),
                borderColor: '#4caf50',
                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#4caf50',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(76, 175, 80, 0.9)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#4caf50',
                    borderWidth: 1,
                    callbacks: {
                        title: function(tooltipItems) {
                            const index = tooltipItems[0].dataIndex;
                            const week = weeklyChartData[index];
                            const startDate = new Date(week.week_start);
                            const endDate = new Date(week.week_end);
                            return 'Week: ' + startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + 
                                   ' (Sun) - ' + endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' (Sat)';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    grid: {
                        color: 'rgba(76, 175, 80, 0.1)'
                    },
                    ticks: {
                        color: '#4caf50'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(76, 175, 80, 0.1)'
                    },
                    ticks: {
                        color: '#4caf50',
                        maxTicksLimit: 6
                    }
                }
            }
        }
    });
    
    // Monthly Chart
    const monthlyCtx = document.getElementById('monthlyChartCanvas').getContext('2d');
    const monthlyWeightChart = new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: monthlyChartData.map(item => {
                const date = new Date(item.month_start);
                return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            }),
            datasets: [{
                label: 'Monthly Average (kg)',
                data: monthlyChartData.map(item => item.average),
                borderColor: '#ff9800',
                backgroundColor: 'rgba(255, 152, 0, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#ff9800',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 152, 0, 0.9)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#ff9800',
                    borderWidth: 1,
                    callbacks: {
                        title: function(tooltipItems) {
                            const index = tooltipItems[0].dataIndex;
                            const month = monthlyChartData[index];
                            const startDate = new Date(month.month_start);
                            const endDate = new Date(month.month_end);
                            return startDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) + 
                                   ' (' + startDate.toLocaleDateString('en-US', { day: 'numeric' }) + 
                                   ' - ' + endDate.toLocaleDateString('en-US', { day: 'numeric' }) + ')';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    grid: {
                        color: 'rgba(255, 152, 0, 0.1)'
                    },
                    ticks: {
                        color: '#ff9800'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(255, 152, 0, 0.1)'
                    },
                    ticks: {
                        color: '#ff9800',
                        maxTicksLimit: 6
                    }
                }
            }
        }
    });
    
    // Yearly Chart
    const yearlyCtx = document.getElementById('yearlyChartCanvas').getContext('2d');
    const yearlyWeightChart = new Chart(yearlyCtx, {
        type: 'line',
        data: {
            labels: yearlyChartData.map(item => {
                const date = new Date(item.year_start);
                return date.toLocaleDateString('en-US', { year: 'numeric' });
            }),
            datasets: [{
                label: 'Yearly Average (kg)',
                data: yearlyChartData.map(item => item.average),
                borderColor: '#9c27b0',
                backgroundColor: 'rgba(156, 39, 176, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#9c27b0',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(156, 39, 176, 0.9)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#9c27b0',
                    borderWidth: 1,
                    callbacks: {
                        title: function(tooltipItems) {
                            const index = tooltipItems[0].dataIndex;
                            const year = yearlyChartData[index];
                            const startDate = new Date(year.year_start);
                            const endDate = new Date(year.year_end);
                            return startDate.toLocaleDateString('en-US', { year: 'numeric' }) + 
                                   ' (' + startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + 
                                   ' - ' + endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ')';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    grid: {
                        color: 'rgba(156, 39, 176, 0.1)'
                    },
                    ticks: {
                        color: '#9c27b0'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(156, 39, 176, 0.1)'
                    },
                    ticks: {
                        color: '#9c27b0',
                        maxTicksLimit: 6
                    }
                }
            }
        }
    });
    
    // Chart switching function
    function switchChart(type) {
        const charts = ['daily', 'weekly', 'monthly', 'yearly'];
        const tabs = document.querySelectorAll('.tab-btn');
        
        charts.forEach(chart => {
            const element = document.getElementById(chart + 'Chart');
            if (element) {
                element.style.display = chart === type ? 'block' : 'none';
            }
        });
        
        tabs.forEach((tab, index) => {
            if (charts[index] === type) {
                tab.classList.add('active');
            } else {
                tab.classList.remove('active');
            }
        });
    }
    
    // Handle window resize for charts
    window.addEventListener('resize', function() {
        weightChart.resize();
        weeklyWeightChart.resize();
        monthlyWeightChart.resize();
        yearlyWeightChart.resize();
    });
</script>

<style>
/* Mobile-first responsive styles */
.chart-tabs {
    display: flex;
    border-bottom: 2px solid var(--border-color);
    margin-bottom: 1rem;
    flex-wrap: wrap;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.tab-btn {
    background: none;
    border: none;
    padding: 0.75rem 1rem;
    cursor: pointer;
    color: var(--light-text);
    font-weight: 600;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
    flex: 1;
    min-width: 70px;
    text-align: center;
    white-space: nowrap;
    font-size: 0.9rem;
}

.tab-btn.active {
    color: var(--accent);
    border-bottom-color: var(--accent);
}

.tab-btn:hover:not(.active) {
    color: var(--text-color);
    background: rgba(26, 35, 126, 0.05);
}

.chart-container {
    transition: all 0.3s ease;
}

.chart-wrapper {
    height: 300px;
    margin: 1rem 0;
    position: relative;
}

.data-grid {
    display: grid;
    gap: 0.75rem;
}

.data-card {
    padding: 1rem;
    border-radius: 8px;
    background: var(--card-bg);
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.data-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
}

.data-card-info {
    flex: 1;
    min-width: 0; /* Prevent flex item from overflowing */
}

.data-card-title {
    font-weight: 600;
    font-size: 1rem;
    margin-bottom: 0.25rem;
    word-break: break-word;
}

.data-card-subtitle {
    font-size: 0.85rem;
    color: var(--light-text);
    line-height: 1.3;
}

.data-card-value {
    font-weight: 700;
    color: var(--accent);
    font-size: 1.1rem;
    white-space: nowrap;
}

.data-card-footer {
    margin-top: 0.5rem;
    font-size: 0.8rem;
    color: var(--light-text);
}

.empty-state {
    text-align: center;
    padding: 2rem 1rem;
    color: var(--light-text);
}

.empty-state i {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    display: block;
}

.empty-state p {
    margin-bottom: 1.5rem;
    line-height: 1.5;
}

.mobile-card-header {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
}

.mobile-btn {
    width: 100%;
    justify-content: center;
}

.btn-text {
    margin-left: 0.5rem;
}

/* Responsive adjustments */
@media (max-width: 480px) {
    .card {
        margin: 0.5rem;
        padding: 1rem;
    }
    
    .card-title {
        font-size: 1.3rem;
    }
    
    .data-card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .data-card-value {
        align-self: flex-start;
    }
    
    .chart-wrapper {
        height: 250px;
    }
    
    .tab-btn {
        padding: 0.6rem 0.8rem;
        font-size: 0.85rem;
        min-width: 60px;
    }
    
    .mobile-card-header {
        align-items: stretch;
    }
}

@media (min-width: 481px) and (max-width: 768px) {
    .mobile-card-header {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
    
    .mobile-btn {
        width: auto;
    }
    
    .data-card-header {
        flex-direction: row;
        align-items: center;
    }
}

@media (min-width: 769px) {
    .mobile-card-header {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
    
    .mobile-btn {
        width: auto;
    }
}

/* Improve touch targets for mobile */
.btn, .tab-btn {
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Prevent horizontal scrolling on mobile */
body {
    overflow-x: hidden;
}

/* Improve readability on small screens */
@media (max-width: 360px) {
    .data-card-title {
        font-size: 0.95rem;
    }
    
    .data-card-subtitle {
        font-size: 0.8rem;
    }
    
    .data-card-value {
        font-size: 1rem;
    }
    
    .tab-btn {
        padding: 0.5rem 0.6rem;
        font-size: 0.8rem;
        min-width: 55px;
    }
}
</style>
<?php endif; ?>

<?php require_once 'footer.php'; ?>