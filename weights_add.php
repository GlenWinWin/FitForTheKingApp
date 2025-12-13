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

<!-- Native Mobile App Container -->
<div class="native-app-container" data-native-mobile="true">
    
    <!-- Premium Background -->
    <div class="premium-bg"></div>
    <div class="particles-container" id="particles-container"></div>
    
    <!-- Sticky Header -->
    <header class="native-header">
        <div class="native-header-content">
            <h1 class="native-title">Track Your Weight</h1>
        </div>
    </header>
    
    <!-- Main Content (Vertical Scroll Only) -->
    <main class="native-main-content" id="native-main-content">
        
        <!-- Messages (Auto-dismissible) -->
        <?php if ($message): ?>
        <div class="native-message success" data-auto-dismiss="3000">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $message; ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="native-message error" data-auto-dismiss="5000">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
        </div>
        <?php endif; ?>
        
        <!-- Input Card -->
        <section class="native-card" data-card-type="input">
            <div class="native-card-inner">
                <form method="POST" class="native-form">
                    <!-- Weight Input -->
                    <div class="native-form-group">
                        <label for="weight_kg" class="native-label">
                            <i class="fas fa-weight-scale"></i>
                            <span>Weight (kg)</span>
                        </label>
                        <div class="native-input-wrapper">
                            <input type="number" 
                                   id="weight_kg" 
                                   name="weight_kg" 
                                   class="native-input"
                                   inputmode="decimal"
                                   step="0.01" 
                                   min="30" 
                                   max="300" 
                                   required 
                                   placeholder="Enter weight in kg"
                                   aria-label="Weight in kilograms">
                        </div>
                    </div>
                    
                    <!-- Date Input -->
                    <div class="native-form-group">
                        <label for="entry_date" class="native-label">
                            <i class="fas fa-calendar"></i>
                            <span>Date</span>
                        </label>
                        <div class="native-input-wrapper">
                            <input type="date" 
                                   id="entry_date" 
                                   name="entry_date" 
                                   class="native-input"
                                   value="<?php echo date('Y-m-d'); ?>" 
                                   required
                                   aria-label="Entry date">
                        </div>
                    </div>
                    
                    <!-- Save Button (Large Touch Target) -->
                    <button type="submit" 
                            class="native-primary-button"
                            aria-label="Save weight entry">
                        <i class="fas fa-save"></i>
                        <span>Save Weight</span>
                    </button>
                </form>
            </div>
        </section>
        
        <!-- Recent Weights Card -->
        <section class="native-card" data-card-type="list">
            <div class="native-card-header">
                <h2 class="native-card-title">Recent Weights</h2>
            </div>
            
            <div class="native-card-content">
                <?php if ($recent_weights): ?>
                    <div class="native-list" aria-label="Recent weight entries">
                        <?php foreach ($recent_weights as $index => $weight): ?>
                        <div class="native-list-item" 
                             data-item-index="<?php echo $index; ?>"
                             role="listitem"
                             aria-label="Weight entry for <?php echo date('F j, Y', strtotime($weight['entry_date'])); ?>">
                            <div class="native-list-item-content">
                                <div class="native-list-item-main">
                                    <div class="native-list-item-title">
                                        <?php echo date('M j, Y', strtotime($weight['entry_date'])); ?>
                                    </div>
                                    <div class="native-list-item-subtitle">
                                        <?php echo date('l', strtotime($weight['entry_date'])); ?>
                                    </div>
                                </div>
                                <div class="native-list-item-trailing">
                                    <span class="native-value">
                                        <?php echo $weight['weight_kg']; ?> kg
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="native-empty-state" aria-label="No weight entries">
                        <div class="native-empty-state-icon">
                            <i class="fas fa-weight-scale"></i>
                        </div>
                        <div class="native-empty-state-text">
                            <p>No weight entries yet.</p>
                            <p class="native-empty-state-hint">Start tracking to see your progress!</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Bottom Spacer for Scroll -->
        <div class="native-bottom-spacer"></div>
    </main>
</div>

<style>
/* Native Mobile App Base */
.native-app-container {
    position: relative;
    min-height: 100vh;
    width: 100%;
    max-width: 500px;
    margin: 0 auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
    touch-action: pan-y;
}

/* Disable Zoom */
.native-app-container * {
    touch-action: pan-y;
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    user-select: none;
}

.native-input,
[contenteditable="true"] {
    -webkit-user-select: text;
    user-select: text;
}

/* Sticky Header */
.native-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: var(--glass-bg);
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border-bottom: 1px solid var(--glass-border);
    padding: env(safe-area-inset-top) 1rem 0.75rem;
}

.native-header-content {
    padding: 0.5rem 0;
}

.native-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text);
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
}

/* Main Content */
.native-main-content {
    padding: 1rem;
    padding-bottom: calc(1rem + env(safe-area-inset-bottom));
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

/* Cards */
.native-card {
    background: var(--glass-bg);
    border-radius: 16px;
    border: 1px solid var(--glass-border);
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.native-card-inner {
    padding: 1.5rem;
}

.native-card-header {
    padding: 1.25rem 1.5rem 0.75rem;
    border-bottom: 1px solid var(--glass-border);
}

.native-card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text);
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
}

.native-card-content {
    padding: 0.75rem 0;
}

/* Form Styles */
.native-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.native-form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.native-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: var(--text);
    font-size: 0.95rem;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
}

.native-label i {
    color: var(--accent);
    font-size: 0.9rem;
}

.native-input-wrapper {
    position: relative;
}

.native-input {
    width: 100%;
    min-height: 56px;
    padding: 0 1rem;
    border: 2px solid var(--glass-border);
    border-radius: 12px;
    background: var(--glass-bg);
    font-size: 1rem;
    color: var(--text);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    -webkit-appearance: none;
    appearance: none;
}

.native-input:focus {
    outline: none;
    border-color: var(--accent);
    background: var(--glass-bg-hover);
}

.native-input::placeholder {
    color: var(--text-light);
    opacity: 0.7;
}

/* Primary Button (Large Touch Target) */
.native-primary-button {
    min-height: 56px;
    padding: 0 1.5rem;
    background: var(--accent);
    color: white;
    border: none;
    border-radius: 14px;
    font-size: 1.125rem;
    font-weight: 600;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
    position: relative;
    overflow: hidden;
}

.native-primary-button::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.1);
    opacity: 0;
    transition: opacity 0.2s;
}

.native-primary-button:active::after {
    opacity: 1;
}

/* List Styles */
.native-list {
    display: flex;
    flex-direction: column;
    gap: 1px;
    background: var(--glass-border);
}

.native-list-item {
    background: var(--glass-bg);
    min-height: 64px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    -webkit-tap-highlight-color: transparent;
}

.native-list-item:active {
    background: var(--glass-bg-hover);
}

.native-list-item-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    min-height: 64px;
}

.native-list-item-main {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.native-list-item-title {
    font-size: 1rem;
    font-weight: 500;
    color: var(--text);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
}

.native-list-item-subtitle {
    font-size: 0.85rem;
    color: var(--text-light);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
}

.native-list-item-trailing {
    display: flex;
    align-items: center;
}

.native-value {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--accent);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
}

/* Empty State */
.native-empty-state {
    padding: 3rem 2rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    text-align: center;
}

.native-empty-state-icon {
    width: 64px;
    height: 64px;
    border-radius: 32px;
    background: rgba(var(--accent-rgb), 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.5rem;
}

.native-empty-state-icon i {
    font-size: 1.75rem;
    color: var(--accent);
}

.native-empty-state-text p {
    margin: 0;
    font-size: 1rem;
    color: var(--text);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
}

.native-empty-state-hint {
    font-size: 0.9rem;
    color: var(--text-light) !important;
    margin-top: 0.25rem !important;
}

/* Messages */
.native-message {
    min-height: 56px;
    padding: 0 1rem;
    border-radius: 14px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.95rem;
    font-weight: 500;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    animation: slideDown 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.native-message.success {
    background: rgba(76, 175, 80, 0.1);
    color: #4caf50;
    border: 1px solid rgba(76, 175, 80, 0.2);
}

.native-message.error {
    background: rgba(244, 67, 54, 0.1);
    color: #f44336;
    border: 1px solid rgba(244, 67, 54, 0.2);
}

.native-message i {
    font-size: 1.125rem;
}

/* Bottom Spacer */
.native-bottom-spacer {
    height: env(safe-area-inset-bottom);
    min-height: 20px;
}

/* Animations */
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

/* iOS/Mobile Optimizations */
@media (max-width: 768px) {
    .native-app-container {
        max-width: 100%;
    }
    
    .native-header {
        padding-top: calc(env(safe-area-inset-top) + 0.5rem);
    }
    
    .native-title {
        font-size: 1.5rem;
    }
    
    .native-main-content {
        padding: 0.75rem;
        padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));
    }
    
    .native-card {
        border-radius: 14px;
    }
    
    .native-card-inner {
        padding: 1.25rem;
    }
    
    .native-input {
        min-height: 52px;
        font-size: 1.125rem;
    }
    
    .native-primary-button {
        min-height: 52px;
        font-size: 1.0625rem;
    }
    
    .native-list-item {
        min-height: 60px;
    }
    
    .native-list-item-content {
        padding: 0.875rem 1.25rem;
        min-height: 60px;
    }
}

/* Small Mobile */
@media (max-width: 375px) {
    .native-title {
        font-size: 1.375rem;
    }
    
    .native-card-inner {
        padding: 1rem;
    }
    
    .native-card-header {
        padding: 1rem 1.25rem 0.5rem;
    }
    
    .native-input {
        min-height: 48px;
    }
    
    .native-primary-button {
        min-height: 48px;
    }
    
    .native-list-item-content {
        padding: 0.75rem 1rem;
    }
}

/* Dark Mode Adjustments */
@media (prefers-color-scheme: dark) {
    .native-card {
        background: var(--glass-bg);
        border-color: var(--glass-border);
    }
    
    .native-input {
        background: var(--glass-bg);
        border-color: var(--glass-border);
    }
    
    .native-list-item:active {
        background: var(--glass-bg-hover);
    }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    .native-card,
    .native-input,
    .native-primary-button,
    .native-list-item,
    .native-message {
        transition: none;
        animation: none;
    }
}
</style>

<script>
// Native App Interactions
document.addEventListener('DOMContentLoaded', function() {
    const appContainer = document.querySelector('.native-app-container');
    const mainContent = document.getElementById('native-main-content');
    
    // Disable zoom gestures
    function disableZoom() {
        document.addEventListener('touchstart', function(event) {
            if (event.touches.length > 1) {
                event.preventDefault();
            }
        }, { passive: false });
        
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(event) {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    }
    
    // Auto-dismiss messages
    function setupAutoDismiss() {
        const messages = document.querySelectorAll('.native-message[data-auto-dismiss]');
        messages.forEach(message => {
            const duration = parseInt(message.getAttribute('data-auto-dismiss'));
            setTimeout(() => {
                message.style.opacity = '0';
                message.style.transform = 'translateY(-10px)';
                setTimeout(() => message.remove(), 300);
            }, duration);
        });
    }
    
    // Native-like button feedback
    function setupButtonFeedback() {
        const buttons = document.querySelectorAll('.native-primary-button, .native-list-item');
        buttons.forEach(button => {
            button.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.98)';
            }, { passive: true });
            
            button.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            }, { passive: true });
            
            button.addEventListener('touchcancel', function() {
                this.style.transform = 'scale(1)';
            }, { passive: true });
        });
    }
    
    // Smooth scroll behavior
    mainContent.style.scrollBehavior = 'smooth';
    
    // Initialize
    disableZoom();
    setupAutoDismiss();
    setupButtonFeedback();
    
    // Add native app class for iOS/Android detection
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const isAndroid = /Android/.test(navigator.userAgent);
    
    if (isIOS || isAndroid) {
        appContainer.classList.add('native-mobile-' + (isIOS ? 'ios' : 'android'));
    }
});
</script>

<?php require_once 'footer.php'; ?>