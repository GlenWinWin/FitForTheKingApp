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

// Prepare data for chart
$chart_data = [];
foreach ($weights as $weight) {
    $chart_data[] = [
        'date' => $weight['entry_date'],
        'weight' => (float)$weight['weight_kg']
    ];
}
$chart_json = json_encode(array_reverse($chart_data));

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
    <div class="card-header">
        <h1 class="card-title">Weight Progress</h1>
        <a href="weights_add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Weight
        </a>
    </div>
    
    <?php if ($weights): ?>
        <div id="weightChart" style="height: 300px; margin: 2rem 0;">
            <canvas id="chartCanvas"></canvas>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 3rem; color: var(--light-text);">
            <i class="fas fa-weight-scale" style="font-size: 3rem; margin-bottom: 1rem;"></i>
            <p>No weight data yet. Start tracking to see your progress!</p>
            <a href="weights_add.php" class="btn btn-primary" style="margin-top: 1rem;">
                <i class="fas fa-plus"></i> Add First Weight Entry
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2 class="card-title">Weight History</h2>
    
    <?php if ($weights): ?>
        <div style="display: grid; gap: 0.5rem;">
            <?php foreach ($weights as $weight): ?>
            <div class="card" style="padding: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 600;"><?php echo date('F j, Y', strtotime($weight['entry_date'])); ?></div>
                        <div style="font-size: 0.9rem; color: var(--light-text);">
                            <?php echo date('l', strtotime($weight['entry_date'])); ?>
                        </div>
                    </div>
                    <div style="font-weight: 700; color: var(--accent); font-size: 1.2rem;">
                        <?php echo $weight['weight_kg']; ?> kg
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 2rem; color: var(--light-text);">
            <p>No weight entries yet. <a href="weights_add.php" class="btn btn-outline">Add your first weight entry</a>.</p>
        </div>
    <?php endif; ?>
</div>

<?php if ($weights): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const chartData = <?php echo $chart_json; ?>;
    
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
                        color: '#1a237e'
                    }
                }
            }
        }
    });
</script>
<?php endif; ?>

<?php require_once 'footer.php'; ?>