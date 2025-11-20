<?php
$pageTitle = "Add Weight";
require_once 'header.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle form submission
if ($_POST && isset($_POST['weight_kg'])) {
    $weight_kg = sanitize($_POST['weight_kg']);
    $entry_date = sanitize($_POST['entry_date']);
    
    // Check if entry already exists for this date
    $check_query = "SELECT id FROM weights WHERE user_id = ? AND entry_date = ?";
    $stmt = $db->prepare($check_query);
    $stmt->execute([$user_id, $entry_date]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update existing entry
        $update_query = "UPDATE weights SET weight_kg = ? WHERE id = ?";
        $stmt = $db->prepare($update_query);
        if ($stmt->execute([$weight_kg, $existing['id']])) {
            $message = "Weight updated successfully!";
        } else {
            $error = "Failed to update weight entry.";
        }
    } else {
        // Insert new entry
        $insert_query = "INSERT INTO weights (user_id, weight_kg, entry_date) VALUES (?, ?, ?)";
        $stmt = $db->prepare($insert_query);
        if ($stmt->execute([$user_id, $weight_kg, $entry_date])) {
            $message = "Weight added successfully!";
        } else {
            $error = "Failed to add weight entry.";
        }
    }
    
    if ($message) {
        echo "<script>window.location.href = 'weights_history.php?message=" . urlencode($message)."';</script>";
        exit();
    }
}

// Get recent weights
$recent_query = "SELECT entry_date, weight_kg FROM weights 
                WHERE user_id = ? 
                ORDER BY entry_date DESC 
                LIMIT 7";
$stmt = $db->prepare($recent_query);
$stmt->execute([$user_id]);
$recent_weights = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Premium Background -->
<div class="premium-bg"></div>
<div class="particles-container" id="particles-container"></div>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Track Your Weight</h1>
    </div>
    
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
    
    <div class="form-container">
        <form method="POST" class="weight-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="weight_kg" class="form-label">Weight (kg)</label>
                    <div class="input-with-icon">
                        <i class="fas fa-weight-scale"></i>
                        <input type="number" id="weight_kg" name="weight_kg" class="form-input" 
                           step="0.01" min="30" max="300" required placeholder="Enter weight in kg">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="entry_date" class="form-label">Date</label>
                    <div class="input-with-icon">
                        <i class="fas fa-calendar"></i>
                        <input type="date" id="entry_date" name="entry_date" class="form-input" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-full">
                <i class="fas fa-save"></i> Save Weight
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Recent Weights</h2>
    </div>
    
    <div class="recent-weights">
        <?php if ($recent_weights): ?>
            <div class="weights-list">
                <?php foreach ($recent_weights as $weight): ?>
                <div class="weight-item">
                    <div class="weight-date">
                        <div class="date-main"><?php echo date('M j, Y', strtotime($weight['entry_date'])); ?></div>
                        <div class="date-secondary"><?php echo date('l', strtotime($weight['entry_date'])); ?></div>
                    </div>
                    <div class="weight-value">
                        <?php echo $weight['weight_kg']; ?> kg
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-weight-scale"></i>
                <p>No weight entries yet. Start tracking to see your progress!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Form Styles */
.form-container {
    padding: 1rem 0;
}

.weight-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text);
    font-size: 0.9rem;
}

.input-with-icon {
    position: relative;
    display: flex;
    align-items: center;
}

.input-with-icon i {
    position: absolute;
    left: 1rem;
    color: var(--text-light);
    z-index: 2;
}

.form-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 1px solid var(--glass-border);
    border-radius: var(--radius);
    background: var(--glass-bg);
    font-size: 1rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.form-input:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.1);
}

.btn-full {
    width: 100%;
    padding: 0.875rem 1.5rem;
    font-size: 1rem;
    font-weight: 600;
}

/* Recent Weights Styles */
.recent-weights {
    padding: 0.5rem 0;
}

.weights-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.weight-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    background: var(--glass-bg);
    border-radius: var(--radius);
    border: 1px solid var(--glass-border);
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.weight-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    background: var(--glass-bg-hover);
}

.weight-date {
    display: flex;
    flex-direction: column;
}

.date-main {
    font-weight: 600;
    color: var(--text);
    font-size: 0.95rem;
}

.date-secondary {
    font-size: 0.8rem;
    color: var(--text-light);
    margin-top: 0.25rem;
}

.weight-value {
    font-weight: 700;
    color: var(--accent);
    font-size: 1.1rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: var(--text-light);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state p {
    margin: 0;
    font-size: 1rem;
    opacity: 0.8;
}

/* Messages */
.message {
    padding: 1rem 1.25rem;
    border-radius: var(--radius);
    margin-bottom: 1.5rem;
    font-weight: 500;
}

.message.success {
    background: rgba(76, 175, 80, 0.1);
    color: #4caf50;
    border: 1px solid rgba(76, 175, 80, 0.2);
}

.message.error {
    background: rgba(244, 67, 54, 0.1);
    color: #f44336;
    border: 1px solid rgba(244, 67, 54, 0.2);
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .weight-item {
        padding: 0.875rem 1rem;
    }
    
    .date-main {
        font-size: 0.9rem;
    }
    
    .weight-value {
        font-size: 1rem;
    }
    
    .empty-state {
        padding: 2rem 1rem;
    }
    
    .empty-state i {
        font-size: 2.5rem;
    }
}

@media (max-width: 480px) {
    .weight-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .weight-value {
        align-self: flex-end;
    }
    
    .form-input {
        padding: 0.75rem 1rem 0.75rem 2.25rem;
    }
    
    .input-with-icon i {
        left: 0.75rem;
    }
}
</style>

<?php require_once 'footer.php'; ?>