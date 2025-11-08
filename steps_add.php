<?php
$pageTitle = "Add Steps";
require_once 'header.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle form submission
if ($_POST && isset($_POST['steps_count'])) {
    $steps_count = sanitize($_POST['steps_count']);
    $entry_date = sanitize($_POST['entry_date']);
    
    // Check if entry already exists for this date
    $check_query = "SELECT id FROM steps WHERE user_id = ? AND entry_date = ?";
    $stmt = $db->prepare($check_query);
    $stmt->execute([$user_id, $entry_date]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update existing entry
        $update_query = "UPDATE steps SET steps_count = ? WHERE id = ?";
        $stmt = $db->prepare($update_query);
        if ($stmt->execute([$steps_count, $existing['id']])) {
            $message = "Steps updated successfully!";
        } else {
            $error = "Failed to update steps entry.";
        }
    } else {
        // Insert new entry
        $insert_query = "INSERT INTO steps (user_id, steps_count, entry_date) VALUES (?, ?, ?)";
        $stmt = $db->prepare($insert_query);
        if ($stmt->execute([$user_id, $steps_count, $entry_date])) {
            $message = "Steps added successfully!";
        } else {
            $error = "Failed to add steps entry.";
        }
    }
    
    if ($message) {
        echo "<script>window.location.href = 'steps_calendar.php?message=" . urlencode($message)."';</script>";
        exit();
    }
}

// Get recent steps
$recent_query = "SELECT entry_date, steps_count FROM steps 
                WHERE user_id = ? 
                ORDER BY entry_date DESC 
                LIMIT 7";
$stmt = $db->prepare($recent_query);
$stmt->execute([$user_id]);
$recent_steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Premium Background -->
<div class="premium-bg"></div>
<div class="particles-container" id="particles-container"></div>

<div class="card">
    <h1 class="card-title">Add Steps</h1>
    
    <?php if ($message): ?>
    <div class="message success">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="message error">
        <?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="steps_count">Number of Steps</label>
            <div class="input-container">
                <input type="number" id="steps_count" name="steps_count" class="input" 
                       min="0" max="100000" required placeholder="Enter steps count">
            </div>
        </div>
        
        <div class="form-group">
            <label for="entry_date">Date</label>
            <div class="input-container">
                <input type="date" id="entry_date" name="entry_date" class="input" 
                       value="<?php echo date('Y-m-d'); ?>" required>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%;">
            <i class="fas fa-save"></i> Save Steps
        </button>
    </form>
</div>

<div class="card">
    <h2 class="card-title">Recent Steps</h2>
    
    <?php if ($recent_steps): ?>
        <div style="display: grid; gap: 1rem;">
            <?php foreach ($recent_steps as $step): ?>
            <div class="card" style="padding: 1rem; background: var(--glass-bg);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 600;"><?php echo date('M j, Y', strtotime($step['entry_date'])); ?></div>
                        <div style="font-size: 0.9rem; color: var(--light-text);">
                            <?php echo date('l', strtotime($step['entry_date'])); ?>
                        </div>
                    </div>
                    <div style="font-weight: 700; color: var(--accent); font-size: 1.2rem;">
                        <?php echo number_format($step['steps_count']); ?> steps
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 2rem; color: var(--light-text);">
            <i class="fas fa-walking" style="font-size: 2rem; margin-bottom: 1rem;"></i>
            <p>No steps recorded yet. Start tracking your daily activity!</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>