<?php
$pageTitle = "My Profile";
require_once 'header.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
    $upload_dir = 'uploads/profile_pictures/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            $error = "Failed to create upload directory.";
        }
    }
    
    if (empty($error)) {
        $file = $_FILES['profile_picture'];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error = "File size too large. Maximum 5MB allowed.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error = "File upload was incomplete.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error = "No file was selected.";
                    break;
                default:
                    $error = "File upload failed with error code: " . $file['error'];
            }
        } else {
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = mime_content_type($file['tmp_name']);
            
            if (!in_array($file_type, $allowed_types)) {
                $error = "Only JPG, PNG, GIF, and WebP images are allowed.";
            } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
                $error = "File size must be less than 5MB.";
            } else {
                // Generate unique filename
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $file_name = $user_id . '_' . time() . '.' . $file_extension;
                $target_path = $upload_dir . $file_name;
                
                // Delete old profile picture if it exists and is not the default
                if (!empty($user['profile_picture']) && $user['profile_picture'] !== 'imgs/profile.png') {
                    if (file_exists($user['profile_picture'])) {
                        unlink($user['profile_picture']);
                    }
                }
                
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    // Update user profile picture in database
                    $update_query = "UPDATE users SET profile_picture = ? WHERE id = ?";
                    $stmt = $db->prepare($update_query);
                    if ($stmt->execute([$target_path, $user_id])) {
                        $_SESSION['user_profile_picture'] = $target_path;
                        $message = "Profile picture updated successfully!";
                        
                        // Refresh user data to show new picture immediately
                        $user_query = "SELECT name, email, profile_picture, created_at FROM users WHERE id = ?";
                        $stmt = $db->prepare($user_query);
                        $stmt->execute([$user_id]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $error = "Failed to update profile picture in database.";
                        // Delete the uploaded file if database update failed
                        if (file_exists($target_path)) {
                            unlink($target_path);
                        }
                    }
                } else {
                    $error = "Failed to upload file. Please try again.";
                }
            }
        }
    }
}

// Handle password update (your existing code remains the same)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    // ... your existing password update code ...
}

// Get user data (make sure this runs AFTER potential updates)
if (!isset($user)) {
    $user_query = "SELECT name, email, profile_picture, created_at FROM users WHERE id = ?";
    $stmt = $db->prepare($user_query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get user stats for profile
$stats_query = "SELECT 
               (SELECT COUNT(*) FROM devotional_reads WHERE user_id = ?) as devotions_read,
               (SELECT COUNT(*) FROM weights WHERE user_id = ?) as weight_entries,
               (SELECT COUNT(*) FROM steps WHERE user_id = ?) as steps_entries,
               (SELECT COUNT(*) FROM workout_logs WHERE user_id = ?) as workouts_completed";
$stmt = $db->prepare($stats_query);
$stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<style>
/* Enhanced Mobile-First Responsive Profile Styles */
.profile-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0.5rem;
}

.profile-header {
    text-align: center;
    margin-bottom: 1.5rem;
    padding: 0 0.5rem;
}

.profile-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

@media (min-width: 480px) {
    .profile-container {
        padding: 1rem;
    }
    
    .profile-grid {
        gap: 1.25rem;
    }
}

@media (min-width: 768px) {
    .profile-grid {
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
}

.profile-card {
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: var(--shadow);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

@media (min-width: 480px) {
    .profile-card {
        border-radius: 16px;
        padding: 1.5rem;
    }
}

.profile-picture-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 1.25rem;
}

.profile-picture-wrapper {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    overflow: hidden;
    margin-bottom: 1rem;
    border: 3px solid var(--accent);
    box-shadow: var(--shadow);
    position: relative;
}

@media (min-width: 375px) {
    .profile-picture-wrapper {
        width: 140px;
        height: 140px;
    }
}

@media (min-width: 480px) {
    .profile-picture-wrapper {
        width: 150px;
        height: 150px;
        border-width: 4px;
    }
}

.profile-picture {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-picture-upload {
    width: 100%;
    text-align: center;
}

.upload-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.875rem 1rem;
    background: transparent;
    border: 2px dashed var(--accent);
    border-radius: 8px;
    color: var(--accent);
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 1rem;
    font-size: 0.95rem;
}

.upload-btn:hover {
    background: rgba(var(--accent-rgb), 0.1);
}

.account-info {
    display: flex;
    flex-direction: column;
    gap: 0.875rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
}

@media (min-width: 480px) {
    .info-item {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }
}

.info-label {
    font-weight: 600;
    color: var(--light-text);
    font-size: 0.9rem;
}

.info-value {
    color: var(--text-color);
    font-size: 0.95rem;
    word-break: break-word;
}

.progress-section {
    margin-top: 1.25rem;
}

.progress-title {
    color: var(--accent);
    margin-bottom: 1rem;
    font-size: 1.1rem;
    text-align: center;
}

@media (min-width: 480px) {
    .progress-title {
        text-align: left;
    }
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.875rem;
}

@media (min-width: 480px) {
    .stats-grid {
        gap: 1rem;
    }
}

.stat-card {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    padding: 1rem;
    text-align: center;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--accent);
    margin-bottom: 0.5rem;
}

@media (min-width: 480px) {
    .stat-number {
        font-size: 1.8rem;
    }
}

.stat-label {
    font-size: 0.85rem;
    color: var(--light-text);
}

@media (min-width: 480px) {
    .stat-label {
        font-size: 0.9rem;
    }
}

/* Updated Password Section Styles */
.password-section {
    margin-top: 1.5rem;
}

.password-card {
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: var(--shadow);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

@media (min-width: 480px) {
    .password-card {
        border-radius: 16px;
        padding: 1.5rem;
    }
}

.password-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    text-align: center;
}

@media (min-width: 480px) {
    .password-header {
        flex-direction: row;
        text-align: left;
    }
}

.password-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(var(--accent-rgb), 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--accent);
    flex-shrink: 0;
}

.password-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.password-input-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.password-label {
    font-weight: 600;
    color: var(--text-color);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
}

.password-input-container {
    position: relative;
    width: 100%;
}

.password-input {
    width: 100%;
    padding: 0.875rem 1rem;
    padding-right: 3rem;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    color: var(--text-color);
    font-size: 1rem;
    transition: all 0.3s ease;
    -webkit-appearance: none;
}

.password-input:focus {
    outline: none;
    border-color: var(--accent);
    background: rgba(255, 255, 255, 0.12);
    box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.1);
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--light-text);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 4px;
    transition: all 0.2s ease;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.password-toggle:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-color);
}

.password-requirements {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    padding: 1rem;
    margin-top: 0.5rem;
}

.requirements-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--light-text);
    margin-bottom: 0.5rem;
}

.requirements-list {
    list-style: none;
    padding: 0;
    margin: 0;
    font-size: 0.8rem;
    color: var(--light-text);
}

.requirements-list li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.requirements-list li i {
    font-size: 0.7rem;
    color: var(--accent);
}

.password-submit-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    width: 100%;
    padding: 1rem;
    background: var(--accent);
    border: none;
    border-radius: 8px;
    color: white;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 0.5rem;
    -webkit-tap-highlight-color: transparent;
}

.password-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(var(--accent-rgb), 0.3);
}

.account-actions {
    text-align: center;
    padding: 1.5rem 1rem;
}

@media (min-width: 480px) {
    .account-actions {
        padding: 2rem 1rem;
    }
}

.logout-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    background: transparent;
    border: 2px solid #f44336;
    border-radius: 8px;
    color: #f44336;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.95rem;
    min-width: 140px;
    -webkit-tap-highlight-color: transparent;
}

.logout-btn:hover {
    background: #f44336;
    color: white;
}

/* Enhanced Message styles for mobile */
.message {
    padding: 0.875rem 1rem;
    border-radius: 8px;
    margin-bottom: 1.25rem;
    text-align: center;
    font-size: 0.95rem;
    line-height: 1.4;
}

.message.success {
    background: rgba(76, 175, 80, 0.2);
    border: 1px solid rgba(76, 175, 80, 0.5);
    color: #4caf50;
}

.message.error {
    background: rgba(244, 67, 54, 0.2);
    border: 1px solid rgba(244, 67, 54, 0.5);
    color: #f44336;
}

/* Card title adjustments */
.card-title {
    font-size: 1.4rem;
    margin-bottom: 1rem;
    text-align: center;
}

@media (min-width: 480px) {
    .card-title {
        font-size: 1.5rem;
        text-align: left;
    }
}

/* Button improvements for mobile */
.btn {
    padding: 0.875rem 1rem;
    font-size: 0.95rem;
    border-radius: 8px;
    -webkit-tap-highlight-color: transparent;
    min-height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

/* Improved touch targets for mobile */
@media (max-width: 479px) {
    .password-toggle,
    .upload-btn,
    .logout-btn {
        min-height: 44px;
    }
    
    .info-item {
        padding: 0.875rem;
    }
    
    .stat-card {
        padding: 0.875rem;
    }
}

/* Safe area insets for notched devices */
@supports(padding: max(0px)) {
    .profile-container {
        padding-left: max(0.5rem, env(safe-area-inset-left));
        padding-right: max(0.5rem, env(safe-area-inset-right));
    }
}

/* Reduced motion for accessibility */
@media (prefers-reduced-motion: reduce) {
    .profile-card,
    .stat-card,
    .password-submit-btn,
    .logout-btn {
        transition: none;
    }
    
    .stat-card:hover {
        transform: none;
    }
}
</style>

<!-- Premium Background -->
<div class="premium-bg"></div>
<div class="particles-container" id="particles-container"></div>

<div class="profile-container">
    <div class="profile-header">
        <h1 class="card-title">My Profile</h1>
        
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
    </div>

    <div class="profile-grid">
        <!-- Profile Picture Section -->
        <div class="profile-card">
            <h2 class="card-title">Profile Picture</h2>
            
            <div class="profile-picture-container">
                <div class="profile-picture-wrapper">
                    <img id="profilePreview" class="profile-picture" 
                         src="<?php echo $user['profile_picture'] ?? 'imgs/profile.png'; ?>"
                         alt="Profile Picture">
                </div>
                <p style="color: var(--light-text); text-align: center; margin-bottom: 1.25rem; font-size: 0.9rem; line-height: 1.4;">
                    Click below to upload a new profile picture
                </p>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="profile-picture-upload" id="profilePictureForm">
                <div class="form-group">
                    <label for="profile_picture" class="upload-btn">
                        <i class="fas fa-camera"></i> Choose New Picture
                    </label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif,image/webp" 
                        style="display: none;" onchange="previewImage(this)">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;" name="update_picture">
                    <i class="fas fa-save"></i> Update Picture
                </button>
            </form>
        </div>

        <!-- User Information -->
        <div class="profile-card">
            <h2 class="card-title">Account Information</h2>
            
            <div class="account-info">
                <div class="info-item">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?php echo $user['name']; ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo $user['email']; ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Member Since:</span>
                    <span class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                </div>
            </div>
            
            <div class="progress-section">
                <h3 class="progress-title">Your Progress</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['devotions_read']; ?></div>
                        <div class="stat-label">Devotions</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['workouts_completed']; ?></div>
                        <div class="stat-label">Workouts</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Updated Password Update Section -->
    <div class="password-section">
        <div class="password-card">
            <div class="password-header">
                <div class="password-icon">
                    <i class="fas fa-key"></i>
                </div>
                <h2 class="card-title" style="margin: 0;">Update Password</h2>
            </div>
            
            <form method="POST" class="password-form">
                <input type="hidden" name="update_password" value="1">
                
                <div class="password-input-group">
                    <label for="current_password" class="password-label">
                        <i class="fas fa-lock"></i> Current Password
                    </label>
                    <div class="password-input-container">
                        <input type="password" id="current_password" name="current_password" class="password-input" 
                               placeholder="Enter your current password" required>
                        <button type="button" class="password-toggle" data-target="current_password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="password-input-group">
                    <label for="new_password" class="password-label">
                        <i class="fas fa-key"></i> New Password
                    </label>
                    <div class="password-input-container">
                        <input type="password" id="new_password" name="new_password" class="password-input" 
                               placeholder="Enter new password (min 6 characters)" minlength="6" required>
                        <button type="button" class="password-toggle" data-target="new_password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <div class="password-requirements">
                        <div class="requirements-title">Password Requirements:</div>
                        <ul class="requirements-list">
                            <li><i class="fas fa-check-circle"></i> At least 6 characters long</li>
                            <li><i class="fas fa-check-circle"></i> Use a combination of letters and numbers</li>
                            <li><i class="fas fa-check-circle"></i> Avoid common words and patterns</li>
                        </ul>
                    </div>
                </div>
                
                <div class="password-input-group">
                    <label for="confirm_password" class="password-label">
                        <i class="fas fa-redo"></i> Confirm New Password
                    </label>
                    <div class="password-input-container">
                        <input type="password" id="confirm_password" name="confirm_password" class="password-input" 
                               placeholder="Confirm your new password" minlength="6" required>
                        <button type="button" class="password-toggle" data-target="confirm_password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="password-submit-btn">
                    <i class="fas fa-key"></i> Update Password
                </button>
            </form>
        </div>
    </div>

    <!-- Logout Section -->
    <div class="profile-card account-actions">
        <h2 class="card-title">Account Actions</h2>
        <p style="color: var(--light-text); margin-bottom: 1.5rem; font-size: 0.9rem; line-height: 1.4;">
            Ready to take a break? You can log out and come back anytime.
        </p>
        
        <a href="logout.php" class="logout-btn" 
           onclick="return confirm('Are you sure you want to log out?')">
            <i class="fas fa-sign-out-alt"></i> Log Out
        </a>
    </div>
</div>

<script>
// Enhanced mobile-friendly image preview with better error handling
function previewImage(input) {
    const preview = document.getElementById('profilePreview');
    const file = input.files[0];
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    if (file) {
        // Check file size
        if (file.size > maxSize) {
            alert('File size must be less than 5MB');
            input.value = '';
            return;
        }
        
        // Check file type
        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            alert('Please select a valid image file (JPG, PNG, GIF, or WebP)');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            // Add loading state
            preview.style.opacity = '0.7';
            setTimeout(() => {
                preview.style.opacity = '1';
            }, 200);
        }
        
        reader.onerror = function() {
            alert('Error reading file. Please try another image.');
            input.value = '';
            preview.style.opacity = '1';
        }
        
        reader.readAsDataURL(file);
    }
}

// Enhanced form submission validation with mobile-friendly alerts
document.getElementById('profilePictureForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('profile_picture');
    if (fileInput.files.length === 0) {
        e.preventDefault();
        alert('Please select a profile picture to upload.');
        fileInput.closest('.form-group').style.animation = 'shake 0.5s ease-in-out';
        setTimeout(() => {
            fileInput.closest('.form-group').style.animation = '';
        }, 500);
        return false;
    }
});

// Enhanced password toggle functionality for mobile
document.addEventListener('DOMContentLoaded', function() {
    const passwordToggles = document.querySelectorAll('.password-toggle');
    
    passwordToggles.forEach(toggle => {
        // Add touch event for better mobile support
        toggle.addEventListener('touchstart', function(e) {
            e.preventDefault();
            this.style.transform = 'translateY(-50%) scale(0.95)';
        });
        
        toggle.addEventListener('touchend', function(e) {
            e.preventDefault();
            this.style.transform = 'translateY(-50%) scale(1)';
            this.click();
        });
        
        toggle.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    // Add shake animation for form errors
    const style = document.createElement('style');
    style.textContent = `
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    `;
    document.head.appendChild(style);
    
    // Enhanced file input click for mobile
    const fileInput = document.getElementById('profile_picture');
    const uploadBtn = document.querySelector('.upload-btn');
    
    if (uploadBtn && fileInput) {
        uploadBtn.addEventListener('touchstart', function(e) {
            this.style.backgroundColor = 'rgba(var(--accent-rgb), 0.1)';
        });
        
        uploadBtn.addEventListener('touchend', function(e) {
            this.style.backgroundColor = '';
        });
    }
});

// Prevent zoom on focus for iOS
document.addEventListener('DOMContentLoaded', function() {
    let viewport = document.querySelector('meta[name="viewport"]');
    
    if (viewport) {
        viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
    }
});
</script>

<?php require_once 'footer.php'; ?>