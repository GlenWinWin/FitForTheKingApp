<?php
$pageTitle = "Add Prayer Request";
require_once 'header.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle form submission
if ($_POST && isset($_POST['title'])) {
    $title = sanitize($_POST['title']);
    $category = sanitize($_POST['category']);
    $prayer_text = sanitize($_POST['prayer_text']);
    
    $insert_query = "INSERT INTO prayers (user_id, title, category, prayer_text) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($insert_query);
    
    if ($stmt->execute([$user_id, $title, $category, $prayer_text])) {
        echo "<script>window.location.href = 'prayers_testimonials.php?tab=prayers&message=prayer_added';</script>";
        exit();
    } else {
        $error = "Failed to add prayer request. Please try again.";
    }
}
?>

<!-- Premium Background -->
<div class="premium-bg"></div>
<div class="particles-container" id="particles-container"></div>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Share Prayer Request</h1>
    </div>
    
    <?php if ($error): ?>
    <div class="message error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <div class="form-container">
        <form method="POST" class="prayer-form">
            <div class="form-group">
                <label for="title" class="form-label">
                    <i class="fas fa-heading"></i>
                    Prayer Title
                </label>
                <div class="input-with-icon">
                    <input type="text" id="title" name="title" class="form-input" 
                           placeholder="Brief title for your prayer request" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="category" class="form-label">
                    <i class="fas fa-tag"></i>
                    Category
                </label>
                <div class="input-with-icon">
                    <select id="category" name="category" class="form-input">
                        <option value="">Select a category (optional)</option>
                        <option value="Health">Health</option>
                        <option value="Family">Family</option>
                        <option value="Finances">Finances</option>
                        <option value="Spiritual Growth">Spiritual Growth</option>
                        <option value="Work/Career">Work/Career</option>
                        <option value="Relationships">Relationships</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="prayer_text" class="form-label">
                    <i class="fas fa-pray"></i>
                    Prayer Request
                </label>
                <div class="textarea-container">
                    <textarea id="prayer_text" name="prayer_text" class="form-textarea" 
                              rows="6" placeholder="Share your prayer needs and how we can pray for you..." required></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="prayers_testimonials.php?tab=prayers" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Back to Prayers
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Share Prayer Request
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Form Styles */
.form-container {
    padding: 1rem 0;
}

.prayer-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: var(--text);
    font-size: 0.95rem;
}

.form-label i {
    color: var(--accent);
    width: 16px;
}

.input-with-icon {
    position: relative;
    display: flex;
    align-items: center;
}

.form-input {
    width: 100%;
    padding: 0.875rem 1rem;
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

/* Textarea Styles */
.textarea-container {
    position: relative;
}

.form-textarea {
    width: 100%;
    padding: 1rem;
    border: 1px solid var(--glass-border);
    border-radius: var(--radius);
    background: var(--glass-bg);
    font-size: 1rem;
    font-family: inherit;
    line-height: 1.5;
    resize: vertical;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    min-height: 150px;
}

.form-textarea:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.1);
}

.textarea-footer {
    display: flex;
    justify-content: flex-end;
    margin-top: 0.5rem;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.form-actions .btn {
    flex: 1;
    padding: 0.875rem 1.5rem;
    font-weight: 600;
}

/* Card Header */
.card-header {
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--glass-border);
    margin-bottom: 1.5rem;
}

.card-subtitle {
    color: var(--text-light);
    margin: 0.5rem 0 0 0;
    font-size: 1rem;
    line-height: 1.5;
}

/* Messages */
.message {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: var(--radius);
    margin-bottom: 1.5rem;
    font-weight: 500;
}

.message.error {
    background: rgba(244, 67, 54, 0.1);
    color: #f44336;
    border: 1px solid rgba(244, 67, 54, 0.2);
}

.message.error i {
    font-size: 1.1rem;
}

/* Select Input Styling */
.form-input select {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 1em;
    padding-right: 2.5rem;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        flex: none;
    }
    
    .card-header {
        padding-bottom: 1rem;
        margin-bottom: 1rem;
    }
    
    .card-title {
        font-size: 1.5rem;
    }
    
    .card-subtitle {
        font-size: 0.9rem;
    }
    
    .form-input, .form-textarea {
        padding: 0.75rem;
    }
    
    .form-label {
        font-size: 0.9rem;
    }
}

@media (max-width: 480px) {
    .form-container {
        padding: 0.5rem 0;
    }
    
    .prayer-form {
        gap: 1.25rem;
    }
    
    .form-textarea {
        min-height: 120px;
        padding: 0.875rem;
    }
    
    .message {
        padding: 0.875rem 1rem;
        font-size: 0.9rem;
    }
}

/* Animation */
.form-input, .form-textarea, .btn {
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

/* Focus States */
.form-input:focus, .form-textarea:focus {
    transform: translateY(-1px);
}
</style>

<?php require_once 'footer.php'; ?>