<?php
$pageTitle = "My Profile";
require_once 'header.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle profile picture upload
if ($_POST && isset($_FILES['profile_picture'])) {
    $upload_dir = 'uploads/profile_pictures/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file = $_FILES['profile_picture'];
    
    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error = "File size is too large. Maximum size is 5MB.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $error = "File was only partially uploaded.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $error = "No file was selected.";
                break;
            default:
                $error = "Upload failed with error code: " . $file['error'];
        }
    } else {
        $file_name = time() . '_' . uniqid() . '_' . preg_replace("/[^a-zA-Z0-9\.]/", "_", $file['name']);
        $target_path = $upload_dir . $file_name;
        
        // Check file type using both MIME type and extension
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_type = mime_content_type($file['tmp_name']);
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_type, $allowed_types) || !in_array($file_extension, $allowed_extensions)) {
            $error = "Only JPG, PNG, GIF, and WebP images are allowed.";
        } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            $error = "File size must be less than 5MB.";
        } else {
            // Validate image dimensions
            $image_info = getimagesize($file['tmp_name']);
            if ($image_info === false) {
                $error = "Uploaded file is not a valid image.";
            } else {
                list($width, $height) = $image_info;
                
                // Check if image is too small or too large
                if ($width < 50 || $height < 50) {
                    $error = "Image must be at least 50x50 pixels.";
                } elseif ($width > 4000 || $height > 4000) {
                    $error = "Image dimensions are too large. Maximum is 4000x4000 pixels.";
                } elseif (move_uploaded_file($file['tmp_name'], $target_path)) {
                    // Delete old profile picture if it exists and is not the default
                    $old_picture_query = "SELECT profile_picture FROM users WHERE id = ?";
                    $stmt = $db->prepare($old_picture_query);
                    $stmt->execute([$user_id]);
                    $old_picture = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($old_picture['profile_picture'] && 
                        !str_contains($old_picture['profile_picture'], 'via.placeholder.com') &&
                        file_exists($old_picture['profile_picture'])) {
                        unlink($old_picture['profile_picture']);
                    }
                    
                    // Update user profile picture in database
                    $update_query = "UPDATE users SET profile_picture = ? WHERE id = ?";
                    $stmt = $db->prepare($update_query);
                    if ($stmt->execute([$target_path, $user_id])) {
                        $_SESSION['user_profile_picture'] = $target_path;
                        $message = "Profile picture updated successfully!";
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

// Handle password update
if ($_POST && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current password hash
    $user_query = "SELECT password_hash FROM users WHERE id = ?";
    $stmt = $db->prepare($user_query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!password_verify($current_password, $user['password_hash'])) {
        $error = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE users SET password_hash = ? WHERE id = ?";
        $stmt = $db->prepare($update_query);
        
        if ($stmt->execute([$new_password_hash, $user_id])) {
            $message = "Password updated successfully!";
        } else {
            $error = "Failed to update password.";
        }
    }
}

// Get user data
$user_query = "SELECT name, email, profile_picture, created_at FROM users WHERE id = ?";
$stmt = $db->prepare($user_query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

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
/* Enhanced Upload Styles */
.upload-area {
    border: 2px dashed var(--accent);
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    background: rgba(var(--accent-rgb), 0.05);
    margin-bottom: 1.5rem;
}

.upload-area.drag-over {
    background: rgba(var(--accent-rgb), 0.1);
    border-color: var(--accent);
    transform: scale(1.02);
}

.upload-icon {
    font-size: 3rem;
    color: var(--accent);
    margin-bottom: 1rem;
}

.upload-text {
    color: var(--light-text);
    margin-bottom: 1rem;
}

.upload-features {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1rem;
    margin-top: 1rem;
}

.upload-feature {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: var(--light-text);
}

.upload-feature i {
    color: var(--accent);
    font-size: 0.8rem;
}

.file-info {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
    display: none;
}

.file-info.show {
    display: block;
    animation: fadeIn 0.3s ease;
}

.file-info-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.file-preview {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
}

.file-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.file-details {
    flex: 1;
}

.file-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.file-size {
    font-size: 0.85rem;
    color: var(--light-text);
}

.upload-progress {
    width: 100%;
    height: 4px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
    margin-top: 1rem;
    overflow: hidden;
    display: none;
}

.upload-progress.show {
    display: block;
}

.progress-bar {
    height: 100%;
    background: var(--accent);
    width: 0%;
    transition: width 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Existing styles remain the same */
.profile-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem;
}

.profile-header {
    text-align: center;
    margin-bottom: 2rem;
}

.profile-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

@media (min-width: 768px) {
    .profile-grid {
        grid-template-columns: 1fr 1fr;
    }
}

.profile-card {
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: var(--shadow);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.profile-picture-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 1.5rem;
}

.profile-picture-wrapper {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    overflow: hidden;
    margin-bottom: 1rem;
    border: 4px solid var(--accent);
    box-shadow: var(--shadow);
    position: relative;
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
    padding: 0.75rem 1rem;
    background: transparent;
    border: 2px dashed var(--accent);
    border-radius: 8px;
    color: var(--accent);
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 1rem;
}

.upload-btn:hover {
    background: rgba(var(--accent-rgb), 0.1);
}

/* ... rest of existing styles ... */
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
        <!-- Enhanced Profile Picture Section -->
        <div class="profile-card">
            <h2 class="card-title">Profile Picture</h2>
            
            <div class="profile-picture-container">
                <div class="profile-picture-wrapper">
                    <img id="profilePreview" class="profile-picture" 
                         src="<?php echo $user['profile_picture'] ?? 'imgs/profile.png'; ?>" 
                         alt="Profile Picture">
                </div>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="profile-picture-upload" id="uploadForm">
                <!-- Drag and Drop Area -->
                <div class="upload-area" id="uploadArea">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="upload-text">
                        <p>Drag & drop your image here</p>
                        <p style="font-size: 0.9rem; margin-top: 0.5rem;">or click to browse</p>
                    </div>
                    <div class="upload-features">
                        <div class="upload-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>JPG, PNG, GIF, WebP</span>
                        </div>
                        <div class="upload-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Max 5MB</span>
                        </div>
                        <div class="upload-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Min 50x50px</span>
                        </div>
                    </div>
                </div>
                
                <!-- File Info Display -->
                <div class="file-info" id="fileInfo">
                    <div class="file-info-content">
                        <div class="file-preview">
                            <img id="filePreview" src="" alt="File preview">
                        </div>
                        <div class="file-details">
                            <div class="file-name" id="fileName"></div>
                            <div class="file-size" id="fileSize"></div>
                        </div>
                        <button type="button" class="btn btn-outline" onclick="clearFile()" style="padding: 0.5rem;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="upload-progress" id="uploadProgress">
                        <div class="progress-bar" id="progressBar"></div>
                    </div>
                </div>
                
                <!-- Hidden File Input -->
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*" 
                       style="display: none;" onchange="handleFileSelect(this.files)">
                
                <button type="submit" class="btn btn-primary" style="width: 100%;" id="uploadButton" disabled>
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

    <!-- Password Update Section -->
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
        <p style="color: var(--light-text); margin-bottom: 2rem;">
            Ready to take a break? You can log out and come back anytime.
        </p>
        
        <a href="logout.php" class="logout-btn" 
           onclick="return confirm('Are you sure you want to log out?')">
            <i class="fas fa-sign-out-alt"></i> Log Out
        </a>
    </div>
</div>

<script>
// Enhanced Profile Picture Upload Functionality
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('profile_picture');
    const fileInfo = document.getElementById('fileInfo');
    const uploadButton = document.getElementById('uploadButton');
    const uploadForm = document.getElementById('uploadForm');
    
    // Click on upload area to trigger file input
    uploadArea.addEventListener('click', function() {
        fileInput.click();
    });
    
    // Drag and drop functionality
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight() {
        uploadArea.classList.add('drag-over');
    }
    
    function unhighlight() {
        uploadArea.classList.remove('drag-over');
    }
    
    // Handle dropped files
    uploadArea.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFileSelect(files);
    }
    
    // Password toggle functionality
    const passwordToggles = document.querySelectorAll('.password-toggle');
    
    passwordToggles.forEach(toggle => {
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
});

function handleFileSelect(files) {
    if (files.length > 0) {
        const file = files[0];
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a valid image file (JPG, PNG, GIF, or WebP).');
            return;
        }
        
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB.');
            return;
        }
        
        // Display file info
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = formatFileSize(file.size);
        document.getElementById('filePreview').src = URL.createObjectURL(file);
        document.getElementById('fileInfo').classList.add('show');
        
        // Update profile preview
        document.getElementById('profilePreview').src = URL.createObjectURL(file);
        
        // Enable upload button
        document.getElementById('uploadButton').disabled = false;
        
        // Show upload progress (simulated)
        simulateUploadProgress();
    }
}

function clearFile() {
    document.getElementById('profile_picture').value = '';
    document.getElementById('fileInfo').classList.remove('show');
    document.getElementById('uploadButton').disabled = true;
    document.getElementById('uploadProgress').classList.remove('show');
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function simulateUploadProgress() {
    const progressBar = document.getElementById('progressBar');
    const uploadProgress = document.getElementById('uploadProgress');
    
    uploadProgress.classList.add('show');
    progressBar.style.width = '0%';
    
    let width = 0;
    const interval = setInterval(() => {
        if (width >= 100) {
            clearInterval(interval);
        } else {
            width += Math.random() * 10;
            progressBar.style.width = Math.min(width, 100) + '%';
        }
    }, 100);
}

// Image preview functionality
function previewImage(input) {
    const preview = document.getElementById('profilePreview');
    const file = input.files[0];
    
    if (file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
        }
        
        reader.readAsDataURL(file);
    }
}
</script>

<?php require_once 'footer.php'; ?>