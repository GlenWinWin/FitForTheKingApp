<?php
$pageTitle = "Progress Photos";
require_once 'header.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Check if today is Friday
$is_friday = (date('N') == 5);
$today = date('Y-m-d');

// Check if already uploaded today
$photo_check_query = "SELECT id FROM progress_photos WHERE user_id = ? AND photo_date = ?";
$stmt = $db->prepare($photo_check_query);
$stmt->execute([$user_id, $today]);
$already_uploaded = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_FILES['front_photo']) || isset($_FILES['side_photo']) || isset($_FILES['back_photo']))) {
    $upload_dir = 'uploads/progress_photos/' . $user_id . '/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            $error = "Failed to create upload directory.";
        }
    }
    
    if (empty($error)) {
        $photos = [];
        $upload_success = true;
        $processed_files = [];
        
        // Process each photo
        $photo_types = ['front_photo', 'side_photo', 'back_photo'];
        
        foreach ($photo_types as $type) {
            if (isset($_FILES[$type]) && $_FILES[$type]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$type];
                
                // Validate file type
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file_type = mime_content_type($file['tmp_name']);
                
                if (!in_array($file_type, $allowed_types)) {
                    $error = "Only JPG, PNG, GIF, and WebP images are allowed for " . str_replace('_', ' ', $type) . ".";
                    $upload_success = false;
                    break;
                } elseif ($file['size'] > 10 * 1024 * 1024) { // 10MB limit before compression
                    $error = "Each photo must be less than 10MB before compression.";
                    $upload_success = false;
                    break;
                } else {
                    // Generate unique filename
                    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $file_name = $type . '_' . $user_id . '_' . time() . '.' . $file_extension;
                    $target_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($file['tmp_name'], $target_path)) {
                        $photos[$type] = $target_path;
                        $processed_files[] = $target_path;
                    } else {
                        $error = "Failed to upload " . str_replace('_', ' ', $type);
                        $upload_success = false;
                        break;
                    }
                }
            }
        }
        
        if ($upload_success && empty($error)) {
            $notes = sanitize($_POST['notes'] ?? '');
            
            // Use existing photos if not updated
            if ($already_uploaded) {
                $existing_query = "SELECT front_photo, side_photo, back_photo FROM progress_photos WHERE id = ?";
                $stmt = $db->prepare($existing_query);
                $stmt->execute([$already_uploaded['id']]);
                $existing_photos = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $front_photo = $photos['front_photo'] ?? $existing_photos['front_photo'];
                $side_photo = $photos['side_photo'] ?? $existing_photos['side_photo'];
                $back_photo = $photos['back_photo'] ?? $existing_photos['back_photo'];
                
                // Update existing entry
                $update_query = "UPDATE progress_photos SET front_photo = ?, side_photo = ?, back_photo = ?, notes = ? WHERE id = ?";
                $stmt = $db->prepare($update_query);
                if ($stmt->execute([$front_photo, $side_photo, $back_photo, $notes, $already_uploaded['id']])) {
                    $message = "Progress photos updated successfully!";
                } else {
                    $error = "Failed to update photos in database.";
                }
            } else {
                $front_photo = $photos['front_photo'] ?? null;
                $side_photo = $photos['side_photo'] ?? null;
                $back_photo = $photos['back_photo'] ?? null;
                
                // Insert new entry
                $insert_query = "INSERT INTO progress_photos (user_id, front_photo, side_photo, back_photo, photo_date, notes) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($insert_query);
                if ($stmt->execute([$user_id, $front_photo, $side_photo, $back_photo, $today, $notes])) {
                    $message = "Progress photos uploaded successfully!";
                } else {
                    $error = "Failed to save photos to database.";
                }
            }
            
            // Return JSON response for AJAX
            if (!empty($message) || !empty($error)) {
                echo "<script>window.location.href = 'progress_photos_history.php?message=" . urlencode($message)."';</script>";
                exit();
            }
        } else {
            // Clean up uploaded files on error
            foreach ($processed_files as $file_path) {
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            // Return JSON error response
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $error
            ]);
            exit();
        }
    }
}

// Get today's photos if they exist
$today_photos = null;
if ($already_uploaded) {
    $photos_query = "SELECT * FROM progress_photos WHERE id = ?";
    $stmt = $db->prepare($photos_query);
    $stmt->execute([$already_uploaded['id']]);
    $today_photos = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<style>
/* Enhanced Mobile-First Progress Photos Styles */
.progress-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0.5rem;
}

.progress-header {
    text-align: center;
    margin-bottom: 1.5rem;
    padding: 0 0.5rem;
}

.progress-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

@media (min-width: 480px) {
    .progress-container {
        padding: 1rem;
    }
    
    .progress-grid {
        gap: 1.25rem;
    }
}

@media (min-width: 768px) {
    .progress-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
    }
}

.progress-card {
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: var(--shadow);
    border: 1px solid rgba(255, 255, 255, 0.1);
    text-align: center;
}

@media (min-width: 480px) {
    .progress-card {
        border-radius: 16px;
        padding: 1.5rem;
    }
}

.photo-preview-container {
    width: 100%;
    max-width: 200px;
    height: 250px;
    margin: 0 auto 1rem;
    border: 2px dashed var(--accent);
    border-radius: 12px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.05);
    position: relative;
}

.photo-preview {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: opacity 0.3s ease;
}

.photo-upload-btn {
    display: flex;
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
    -webkit-tap-highlight-color: transparent;
}

.photo-upload-btn:hover {
    background: rgba(var(--accent-rgb), 0.1);
}

.upload-progress {
    display: none;
    width: 100%;
    margin: 1rem 0;
    text-align: center;
}

.progress-bar {
    width: 100%;
    height: 6px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-fill {
    height: 100%;
    background: var(--accent);
    border-radius: 3px;
    transition: width 0.3s ease;
    width: 0%;
}

.progress-text {
    font-size: 0.8rem;
    color: var(--light-text);
    margin-bottom: 0.5rem;
}

.compression-info {
    font-size: 0.75rem;
    color: var(--light-text);
    margin-top: 0.5rem;
    text-align: center;
}

.quality-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: var(--light-text);
}

.quality-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--accent);
    opacity: 0.3;
    cursor: pointer;
}

.quality-dot.active {
    opacity: 1;
}

.notes-section {
    margin-top: 1.5rem;
}

.notes-card {
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: var(--shadow);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

@media (min-width: 480px) {
    .notes-card {
        border-radius: 16px;
        padding: 1.5rem;
    }
}

.notes-textarea {
    width: 100%;
    min-height: 120px;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    color: var(--text-color);
    font-size: 1rem;
    resize: vertical;
    transition: all 0.3s ease;
}

.notes-textarea:focus {
    outline: none;
    border-color: var(--accent);
    background: rgba(255, 255, 255, 0.12);
    box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.1);
}

.actions-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    margin-top: 1.5rem;
}

@media (min-width: 480px) {
    .actions-grid {
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
    }
}

.action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    width: 100%;
    padding: 1rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    -webkit-tap-highlight-color: transparent;
}

.btn-outline {
    background: transparent;
    border: 2px solid var(--accent);
    color: var(--accent);
}

.btn-outline:hover {
    background: rgba(var(--accent-rgb), 0.1);
}

.btn-primary {
    background: var(--accent);
    border: 2px solid var(--accent);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(var(--accent-rgb), 0.3);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

.btn-primary.loading {
    position: relative;
    color: transparent;
}

.btn-primary.loading::after {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 1s ease-in-out infinite;
}

.info-banner {
    background: var(--accent);
    color: white;
    padding: 1.25rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    text-align: center;
}

@media (min-width: 480px) {
    .info-banner {
        padding: 1.5rem;
        border-radius: 16px;
        margin-bottom: 2rem;
        text-align: left;
    }
}

.info-banner h3 {
    color: var(--accent);
    margin-bottom: 1rem;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

@media (min-width: 480px) {
    .info-banner h3 {
        justify-content: flex-start;
    }
}

.info-banner p {
    margin-bottom: 0.75rem;
    font-size: 0.9rem;
    line-height: 1.4;
}

.info-banner p:last-child {
    margin-bottom: 0;
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

/* Improved touch targets for mobile */
@media (max-width: 479px) {
    .photo-upload-btn,
    .action-btn {
        min-height: 48px;
    }
    
    .progress-card {
        padding: 1rem;
    }
}

/* Safe area insets for notched devices */
@supports(padding: max(0px)) {
    .progress-container {
        padding-left: max(0.5rem, env(safe-area-inset-left));
        padding-right: max(0.5rem, env(safe-area-inset-right));
    }
}

/* Reduced motion for accessibility */
@media (prefers-reduced-motion: reduce) {
    .progress-card,
    .action-btn,
    .photo-preview {
        transition: none;
    }
    
    .btn-primary:hover {
        transform: none;
    }
}

/* Loading animation */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 1s ease-in-out infinite;
}

/* FontAwesome spin animation */
.fa-spinner.fa-spin {
    animation: fa-spin 1s infinite linear;
}

@keyframes fa-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Shake animation for errors */
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

/* Toast notification */
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    z-index: 1000;
    animation: slideIn 0.3s ease, fadeOut 0.3s ease 2.7s forwards;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.toast.success {
    background: #4caf50;
}

.toast.error {
    background: #f44336;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
    }
}
</style>

<!-- Include browser-image-compression library -->
<script src="https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.2/dist/browser-image-compression.min.js"></script>

<!-- Premium Background -->
<div class="premium-bg"></div>
<div class="particles-container" id="particles-container"></div>

<div class="progress-container">
    <div class="progress-header">
        <h1 class="card-title">Progress Photos</h1>
        
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

    <div class="info-banner">
        <h3 style="color:white">
            <i class="fas fa-camera" style="color:white"></i> Track Your Transformation
        </h3>
        <p>
            <strong>Why progress photos?</strong> Photos provide visual evidence of your hard work and help you stay motivated.
        </p>
        <p style="margin-bottom: 0;">
            <strong>Tip:</strong> Wear similar clothing each time and take photos in the same location with consistent lighting.
        </p>
    </div>

    <form method="POST" enctype="multipart/form-data" id="progressPhotosForm">
        <div class="progress-grid">
            <!-- Front View -->
            <div class="progress-card">
                <h3 class="card-title" style="text-align: center;">Front View</h3>
                
                <div class="photo-preview-container">
                    <img id="frontPreview" class="photo-preview" 
                         src="<?php echo $today_photos['front_photo'] ?? 'imgs/template.jpg'; ?>" 
                         alt="Front View">
                </div>
                
                <!-- Upload Progress -->
                <div class="upload-progress" id="frontUploadProgress">
                    <div class="progress-bar">
                        <div class="progress-fill" id="frontProgressFill"></div>
                    </div>
                    <div class="progress-text" id="frontProgressText">Compressing image... 0%</div>
                    <div class="compression-info" id="frontCompressionInfo"></div>
                </div>
                
                <div class="form-group">
                    <label for="front_photo" class="photo-upload-btn" id="frontUploadBtn">
                        <i class="fas fa-camera"></i> Upload Front Photo
                    </label>
                    <input type="file" id="front_photo" name="front_photo" accept="image/jpeg,image/png,image/gif,image/webp" 
                        style="display: none;" onchange="handleProgressPhotoUpload(this, 'front')">
                </div>
                
                <div class="quality-indicator">
                    <span>Quality:</span>
                    <div class="quality-dot active" data-quality="0.8" onclick="setCompressionQuality(0.8, this)"></div>
                    <div class="quality-dot" data-quality="0.6" onclick="setCompressionQuality(0.6, this)"></div>
                    <div class="quality-dot" data-quality="0.4" onclick="setCompressionQuality(0.4, this)"></div>
                    <small>Auto</small>
                </div>
            </div>

            <!-- Side View -->
            <div class="progress-card">
                <h3 class="card-title" style="text-align: center;">Side View</h3>
                
                <div class="photo-preview-container">
                    <img id="sidePreview" class="photo-preview" 
                         src="<?php echo $today_photos['side_photo'] ?? 'imgs/template.jpg'; ?>" 
                         alt="Side View">
                </div>
                
                <!-- Upload Progress -->
                <div class="upload-progress" id="sideUploadProgress">
                    <div class="progress-bar">
                        <div class="progress-fill" id="sideProgressFill"></div>
                    </div>
                    <div class="progress-text" id="sideProgressText">Compressing image... 0%</div>
                    <div class="compression-info" id="sideCompressionInfo"></div>
                </div>
                
                <div class="form-group">
                    <label for="side_photo" class="photo-upload-btn" id="sideUploadBtn">
                        <i class="fas fa-camera"></i> Upload Side Photo
                    </label>
                    <input type="file" id="side_photo" name="side_photo" accept="image/jpeg,image/png,image/gif,image/webp" 
                        style="display: none;" onchange="handleProgressPhotoUpload(this, 'side')">
                </div>
                
                <div class="quality-indicator">
                    <span>Quality:</span>
                    <div class="quality-dot active" data-quality="0.8" onclick="setCompressionQuality(0.8, this)"></div>
                    <div class="quality-dot" data-quality="0.6" onclick="setCompressionQuality(0.6, this)"></div>
                    <div class="quality-dot" data-quality="0.4" onclick="setCompressionQuality(0.4, this)"></div>
                    <small>Auto</small>
                </div>
            </div>

            <!-- Back View -->
            <div class="progress-card">
                <h3 class="card-title" style="text-align: center;">Back View</h3>
                
                <div class="photo-preview-container">
                    <img id="backPreview" class="photo-preview" 
                         src="<?php echo $today_photos['back_photo'] ?? 'imgs/template.jpg'; ?>" 
                         alt="Back View">
                </div>
                
                <!-- Upload Progress -->
                <div class="upload-progress" id="backUploadProgress">
                    <div class="progress-bar">
                        <div class="progress-fill" id="backProgressFill"></div>
                    </div>
                    <div class="progress-text" id="backProgressText">Compressing image... 0%</div>
                    <div class="compression-info" id="backCompressionInfo"></div>
                </div>
                
                <div class="form-group">
                    <label for="back_photo" class="photo-upload-btn" id="backUploadBtn">
                        <i class="fas fa-camera"></i> Upload Back Photo
                    </label>
                    <input type="file" id="back_photo" name="back_photo" accept="image/jpeg,image/png,image/gif,image/webp" 
                        style="display: none;" onchange="handleProgressPhotoUpload(this, 'back')">
                </div>
                
                <div class="quality-indicator">
                    <span>Quality:</span>
                    <div class="quality-dot active" data-quality="0.8" onclick="setCompressionQuality(0.8, this)"></div>
                    <div class="quality-dot" data-quality="0.6" onclick="setCompressionQuality(0.6, this)"></div>
                    <div class="quality-dot" data-quality="0.4" onclick="setCompressionQuality(0.4, this)"></div>
                    <small>Auto</small>
                </div>
            </div>
        </div>

        <div class="notes-section">
            <div class="notes-card">
                <h3 class="card-title">Notes</h3>
                <div class="form-group">
                    <textarea id="notes" name="notes" class="notes-textarea" 
                              placeholder="How are you feeling? Any observations about your progress?"><?php echo $today_photos['notes'] ?? ''; ?></textarea>
                </div>
            </div>
        </div>

        <div class="actions-grid">
            <a href="dashboard.php" class="action-btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <button type="submit" class="action-btn btn-primary" id="submitBtn">
                <i class="fas fa-save"></i> 
                <?php echo $today_photos ? 'Update Photos' : 'Save Progress Photos'; ?>
            </button>
        </div>
    </form>
</div>

<script>
// Compression configuration
const compressionOptions = {
    maxSizeMB: 1, // Maximum size in MB
    maxWidthOrHeight: 1024, // Maximum width or height
    useWebWorker: true, // Use web worker for better performance
    fileType: 'image/jpeg', // Output file type
    initialQuality: 0.8, // Initial quality
};

let currentCompressionQuality = 0.8;

// Set compression quality
function setCompressionQuality(quality, element) {
    // Update active dot
    const qualityDots = document.querySelectorAll('.quality-dot');
    qualityDots.forEach(dot => dot.classList.remove('active'));
    element.classList.add('active');
    
    // Update compression quality
    currentCompressionQuality = quality;
}

// Enhanced image upload with compression for progress photos
async function handleProgressPhotoUpload(input, photoType) {
    const file = input.files[0];
    const preview = document.getElementById(photoType + 'Preview');
    const uploadProgress = document.getElementById(photoType + 'UploadProgress');
    const progressFill = document.getElementById(photoType + 'ProgressFill');
    const progressText = document.getElementById(photoType + 'ProgressText');
    const compressionInfo = document.getElementById(photoType + 'CompressionInfo');
    const uploadBtn = document.getElementById(photoType + 'UploadBtn');
    
    if (!file) return;

    // Show upload progress
    uploadProgress.style.display = 'block';
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

    try {
        // Validate file type
        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            throw new Error('Please select a valid image file (JPG, PNG, GIF, or WebP)');
        }

        // Validate file size (max 10MB before compression)
        const maxSizeBeforeCompression = 10 * 1024 * 1024;
        if (file.size > maxSizeBeforeCompression) {
            throw new Error('File size must be less than 10MB before compression');
        }

        // Update progress
        updateProgressPhoto(photoType, 10, 'Reading image...');

        // Show original file info
        const originalSize = (file.size / 1024 / 1024).toFixed(2);
        compressionInfo.innerHTML = `Original: ${originalSize}MB`;

        // Update compression options with current quality
        const options = {
            ...compressionOptions,
            initialQuality: currentCompressionQuality,
            onProgress: (progress) => {
                const percent = Math.round(progress);
                updateProgressPhoto(photoType, 10 + percent * 0.8, `Compressing... ${percent}%`);
            }
        };

        // Compress image
        updateProgressPhoto(photoType, 20, 'Starting compression...');
        
        const compressedFile = await imageCompression(file, options);

        // Update progress
        updateProgressPhoto(photoType, 95, 'Finalizing...');

        // Show compression results
        const compressedSize = (compressedFile.size / 1024 / 1024).toFixed(2);
        const savings = ((1 - compressedFile.size / file.size) * 100).toFixed(1);
        
        compressionInfo.innerHTML = `
            Original: ${originalSize}MB → Compressed: ${compressedSize}MB (${savings}% smaller)
        `;

        // Create preview
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.opacity = '0.7';
            setTimeout(() => {
                preview.style.opacity = '1';
            }, 200);
        };
        reader.readAsDataURL(compressedFile);

        // Replace the original file with compressed one
        const compressedFileWithName = new File([compressedFile], file.name, {
            type: compressedFile.type,
            lastModified: new Date().getTime()
        });
        
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(compressedFileWithName);
        input.files = dataTransfer.files;

        // Complete progress
        updateProgressPhoto(photoType, 100, 'Ready to upload!');
        
        setTimeout(() => {
            uploadBtn.innerHTML = '<i class="fas fa-check"></i> Image Ready!';
        }, 500);

    } catch (error) {
        console.error('Compression error:', error);
        
        // Show error state
        progressFill.style.background = '#f44336';
        progressText.innerHTML = `Error: ${error.message}`;
        compressionInfo.innerHTML = 'Compression failed. Please try another image.';
        uploadBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Try Again';
        
        // Reset file input
        input.value = '';
        
        // Hide progress after delay
        setTimeout(() => {
            uploadProgress.style.display = 'none';
            uploadBtn.innerHTML = '<i class="fas fa-camera"></i> Upload ' + photoType.charAt(0).toUpperCase() + photoType.slice(1).replace('_', ' ') + ' Photo';
        }, 3000);
    }
}

// Progress update function for progress photos
function updateProgressPhoto(photoType, percent, text) {
    const progressFill = document.getElementById(photoType + 'ProgressFill');
    const progressText = document.getElementById(photoType + 'ProgressText');
    
    progressFill.style.width = percent + '%';
    progressText.innerHTML = text;
}

// Show toast notification
function showToast(message, type = 'success') {
    // Remove existing toast
    const existingToast = document.querySelector('.toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Create new toast
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    // Remove toast after animation
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 3000);
}

// Enhanced form submission with AJAX
document.getElementById('progressPhotosForm').addEventListener('submit', async function(e) {
    e.preventDefault(); // Prevent default form submission
    
    console.log('Form submission started');
    console.log('Has existing photos:', <?php echo $today_photos ? 'true' : 'false'; ?>);
    
    const submitBtn = document.getElementById('submitBtn');
    const form = this;
    const formData = new FormData(form);
    
    // Show loading state
    const originalContent = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
    submitBtn.disabled = true;
    submitBtn.classList.add('loading');
    
    // Check if we have at least one photo (either new or existing)
    let hasFiles = false;
    const fileInputs = ['front_photo', 'side_photo', 'back_photo'];
    
    for (const inputName of fileInputs) {
        const input = document.getElementById(inputName);
        if (input && input.files.length > 0) {
            hasFiles = true;
            break;
        }
    }
    
    // If no files are selected but we have existing photos, allow submission
    const hasExistingPhotos = <?php echo $today_photos ? 'true' : 'false'; ?>;
    
    if (!hasFiles && !hasExistingPhotos) {
        showToast('Please upload at least one progress photo before saving.', 'error');
        submitBtn.innerHTML = originalContent;
        submitBtn.disabled = false;
        submitBtn.classList.remove('loading');
        return false;
    }
    
    try {
        // Submit form via AJAX
        const response = await fetch('progress_photos.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            const result = await response.json();
            
            if (result.success) {
                showToast(result.message, 'success');
                
                // Update button to show success
                submitBtn.innerHTML = '<i class="fas fa-check"></i> Uploaded!';
                submitBtn.style.background = '#4CAF50';
                
                // Redirect after delay
                setTimeout(() => {
                    if (result.redirect) {
                        window.location.href = result.redirect;
                    }
                }, 1500);
            } else {
                showToast(result.error || 'Upload failed. Please try again.', 'error');
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
            }
        } else {
            // Handle non-JSON response (fallback to regular form submission)
            console.log('Non-JSON response received, falling back to regular form submission');
            form.submit();
        }
    } catch (error) {
        console.error('Upload error:', error);
        showToast('An error occurred. Please try again.', 'error');
        
        // Reset button state
        submitBtn.innerHTML = originalContent;
        submitBtn.disabled = false;
        submitBtn.classList.remove('loading');
    }
});

// Reset form state when page loads
window.addEventListener('load', function() {
    const uploadProgresses = ['front', 'side', 'back'];
    
    uploadProgresses.forEach(type => {
        const uploadProgress = document.getElementById(type + 'UploadProgress');
        if (uploadProgress) uploadProgress.style.display = 'none';
    });
});

// Enhanced file input click for mobile
document.addEventListener('DOMContentLoaded', function() {
    const photoTypes = ['front', 'side', 'back'];
    
    photoTypes.forEach(type => {
        const fileInput = document.getElementById(type + '_photo');
        const uploadBtn = document.getElementById(type + 'UploadBtn');
        
        if (uploadBtn && fileInput) {
            uploadBtn.addEventListener('touchstart', function(e) {
                this.style.backgroundColor = 'rgba(var(--accent-rgb), 0.1)';
            });
            
            uploadBtn.addEventListener('touchend', function(e) {
                this.style.backgroundColor = '';
            });
        }
    });
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