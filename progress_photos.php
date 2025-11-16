<?php
date_default_timezone_set("Asia/Hong_Kong");

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
if ($_POST && isset($_FILES['front_photo'])) {
    $upload_dir = 'uploads/progress_photos/' . $user_id . '/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $photos = [];
    $upload_success = true;
    
    // Process each photo
    $photo_types = ['front_photo', 'side_photo', 'back_photo'];
    
    foreach ($photo_types as $type) {
        if ($_FILES[$type]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$type];
            $file_name = $type . '_' . time() . '_' . basename($file['name']);
            $target_path = $upload_dir . $file_name;
            
            // Check file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = mime_content_type($file['tmp_name']);
            
            if (!in_array($file_type, $allowed_types)) {
                $error = "Only JPG, PNG, GIF, and WebP images are allowed.";
                $upload_success = false;
                break;
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error = "Each photo must be less than 5MB.";
                $upload_success = false;
                break;
            } elseif (move_uploaded_file($file['tmp_name'], $target_path)) {
                $photos[$type] = $target_path;
            } else {
                $error = "Failed to upload " . str_replace('_', ' ', $type);
                $upload_success = false;
                break;
            }
        }
    }
    
    if ($upload_success && empty($error)) {
        $notes = '';
        
        if ($already_uploaded) {
            // Update existing entry
            $update_query = "UPDATE progress_photos SET front_photo = ?, side_photo = ?, back_photo = ?, notes = ? WHERE id = ?";
            $stmt = $db->prepare($update_query);
            if ($stmt->execute([$photos['front_photo'], $photos['side_photo'], $photos['back_photo'], $notes, $already_uploaded['id']])) {
                $message = "Progress photos updated successfully!";
            } else {
                $error = "Failed to update photos in database.";
            }
        } else {
            // Insert new entry
            $insert_query = "INSERT INTO progress_photos (user_id, front_photo, side_photo, back_photo, photo_date, notes) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($insert_query);
            if ($stmt->execute([$user_id, $photos['front_photo'], $photos['side_photo'], $photos['back_photo'], $today, $notes])) {
                $message = "Progress photos uploaded successfully!";
            } else {
                $error = "Failed to save photos to database.";
            }
        }
        
        if ($message) {
            echo "<script>window.location.href = 'progress_photos_history.php?message=" . urlencode($message)."';</script>";
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

<!-- Premium Background -->
<div class="premium-bg"></div>
<div class="particles-container" id="particles-container"></div>

<!-- Add the missing orientation warning element -->
<div class="orientation-warning">
    <i class="fas fa-mobile-alt"></i> For best results, please use portrait orientation when taking progress photos.
</div>

<div class="card">
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
    
    <div class="info-box">
        <h3 class="info-title">
            <i class="fas fa-camera"></i> Track Your Transformation
        </h3>
        <p class="info-text">
            <strong>Why progress photos?</strong> Photos provide visual evidence of your hard work and help you stay motivated.
        </p>
        <p class="info-text">
            <strong>Tip:</strong> Wear similar clothing each time and take photos in the same location with consistent lighting.
        </p>
        <p class="info-text" style="font-size: 0.8rem; margin-top: 0.5rem;">
            <i class="fas fa-info-circle"></i> Photos are automatically optimized for faster upload and storage.
        </p>
    </div>
</div>

<form method="POST" enctype="multipart/form-data" id="photoForm">
    <div class="photo-grid">
        <!-- Front View -->
        <div class="photo-card">
            <h3 class="photo-title">Front View</h3>
            <div class="photo-preview-container">
                <img id="frontPreview" src="<?php echo $today_photos['front_photo'] ?? 'imgs/template.jpg'; ?>" 
                     alt="Front View" class="photo-preview">
                <div class="compression-info" id="frontInfo"></div>
            </div>
            <div class="form-group">
                <label for="front_photo" class="btn btn-outline photo-upload-btn">
                    <i class="fas fa-camera"></i> Upload Front Photo
                </label>
                <input type="file" id="front_photo" name="front_photo" accept="image/*" 
                       class="file-input" data-preview="frontPreview" data-info="frontInfo" <?php echo !$today_photos ? 'required' : ''; ?>>
            </div>
        </div>

        <!-- Side View -->
        <div class="photo-card">
            <h3 class="photo-title">Side View</h3>
            <div class="photo-preview-container">
                <img id="sidePreview" src="<?php echo $today_photos['side_photo'] ?? 'imgs/template.jpg'; ?>" 
                     alt="Side View" class="photo-preview">
                <div class="compression-info" id="sideInfo"></div>
            </div>
            <div class="form-group">
                <label for="side_photo" class="btn btn-outline photo-upload-btn">
                    <i class="fas fa-camera"></i> Upload Side Photo
                </label>
                <input type="file" id="side_photo" name="side_photo" accept="image/*" 
                       class="file-input" data-preview="sidePreview" data-info="sideInfo" <?php echo !$today_photos ? 'required' : ''; ?>>
            </div>
        </div>

        <!-- Back View -->
        <div class="photo-card">
            <h3 class="photo-title">Back View</h3>
            <div class="photo-preview-container">
                <img id="backPreview" src="<?php echo $today_photos['back_photo'] ?? 'imgs/template.jpg'; ?>" 
                     alt="Back View" class="photo-preview">
                <div class="compression-info" id="backInfo"></div>
            </div>
            <div class="form-group">
                <label for="back_photo" class="btn btn-outline photo-upload-btn">
                    <i class="fas fa-camera"></i> Upload Back Photo
                </label>
                <input type="file" id="back_photo" name="back_photo" accept="image/*" 
                       class="file-input" data-preview="backPreview" data-info="backInfo" <?php echo !$today_photos ? 'required' : ''; ?>>
            </div>
        </div>
    </div>

    <div class="card action-buttons">        
        <div class="button-group">
            <a href="dashboard.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <button type="submit" class="btn btn-primary" id="submitBtn">
                <i class="fas fa-save"></i> 
                <?php echo $today_photos ? 'Update Photos' : 'Save Progress Photos'; ?>
            </button>
        </div>
    </div>
</form>

<!-- Include image compression library -->
<script src="https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.2/dist/browser-image-compression.js"></script>

<style>
/* Mobile-first responsive styles for portrait photos */
:root {
    --mobile-padding: 1rem;
    --card-padding: 1.25rem;
    --photo-preview-height: 280px; /* Taller for portrait photos */
    --photo-preview-width: 180px; /* Narrower for portrait photos */
    --border-radius: 12px;
    --primary-blue: #3498db; /* Solid blue color */
    --primary-blue-dark: #2980b9; /* Darker shade for hover */
}

.card {
    padding: var(--card-padding);
    margin-bottom: 1.5rem;
    border-radius: var(--border-radius);
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border: 1px solid var(--border);
}

.card-title {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    text-align: center;
}

.info-box {
    background: var(--gradient-primary);
    padding: 1.25rem;
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
}

.info-title {
    color: var(--accent);
    margin-bottom: 1rem;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-text {
    margin-bottom: 0.75rem;
    font-size: 0.9rem;
    line-height: 1.4;
}

.info-text:last-child {
    margin-bottom: 0;
}

.photo-grid {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.photo-card {
    padding: var(--card-padding);
    border-radius: var(--border-radius);
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    align-items: center;
}

.photo-title {
    text-align: center;
    margin-bottom: 1rem;
    font-size: 1.1rem;
    width: 100%;
}

.photo-preview-container {
    width: var(--photo-preview-width);
    height: var(--photo-preview-height);
    margin: 0 auto 1rem;
    border: 2px dashed var(--border);
    border-radius: var(--border-radius);
    overflow: hidden;
    background: var(--glass-bg-light);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.photo-preview {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: top; /* Focus on upper body for fitness photos */
}

.compression-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
    text-align: center;
    display: none;
}

.photo-upload-btn {
    width: 100%;
    max-width: var(--photo-preview-width);
    text-align: center;
    cursor: pointer;
    padding: 0.75rem;
    border-radius: var(--border-radius);
    font-size: 0.9rem;
    margin-top: auto;
}

.file-input {
    display: none;
}

.action-buttons {
    margin-top: 1rem;
}

.button-group {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.btn {
    padding: 0.75rem 1rem;
    border-radius: var(--border-radius);
    text-decoration: none;
    text-align: center;
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    border: none;
    cursor: pointer;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text);
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

.message {
    padding: 0.75rem 1rem;
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.message.success {
    background: rgba(76, 175, 80, 0.2);
    border: 1px solid rgba(76, 175, 80, 0.5);
    color: #4CAF50;
}

.message.error {
    background: rgba(244, 67, 54, 0.2);
    border: 1px solid rgba(244, 67, 54, 0.5);
    color: #F44336;
}

.loading {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    border: 2px solid #ffffff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 1s ease-in-out infinite;
    margin-right: 0.5rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Orientation warning styles */
.orientation-warning {
    display: none;
    background: rgba(255, 193, 7, 0.2);
    border: 1px solid rgba(255, 193, 7, 0.5);
    color: #FFC107;
    padding: 0.75rem 1rem;
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    text-align: center;
    font-size: 0.9rem;
}

.orientation-warning i {
    margin-right: 0.5rem;
}

/* Portrait orientation helper */
.portrait-hint {
    display: none;
    text-align: center;
    font-size: 0.8rem;
    color: var(--text-light);
    margin-top: 0.5rem;
}

/* Tablet and larger screens */
@media (min-width: 768px) {
    :root {
        --mobile-padding: 1.5rem;
        --card-padding: 1.5rem;
        --photo-preview-height: 320px;
        --photo-preview-width: 210px;
    }
    
    .photo-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
    }
    
    .button-group {
        flex-direction: row;
    }
    
    .btn {
        flex: 1;
    }
    
    .card-title {
        font-size: 1.75rem;
    }
    
    .photo-title {
        font-size: 1.2rem;
    }
    
    /* Show portrait hint on tablet/desktop */
    .portrait-hint {
        display: block;
    }
}

/* Desktop screens */
@media (min-width: 1024px) {
    :root {
        --mobile-padding: 2rem;
        --photo-preview-height: 350px;
        --photo-preview-width: 230px;
    }
}

/* Small mobile screens (iPhone SE, etc) */
@media (max-width: 375px) {
    :root {
        --mobile-padding: 0.75rem;
        --card-padding: 1rem;
        --photo-preview-height: 250px;
        --photo-preview-width: 150px;
    }
    
    .card-title {
        font-size: 1.35rem;
    }
    
    .photo-title {
        font-size: 1rem;
    }
    
    .btn {
        padding: 0.65rem 0.85rem;
        font-size: 0.85rem;
    }
}

/* Landscape orientation warning */
@media (max-height: 500px) and (orientation: landscape) {
    .orientation-warning {
        display: block !important;
    }
}
</style>

<script>
// Image compression configuration
const compressionOptions = {
    maxSizeMB: 1, // Maximum file size in MB
    maxWidthOrHeight: 1200, // Maximum width or height
    useWebWorker: true, // Use web worker for better performance
    fileType: 'image/jpeg', // Convert to JPEG for better compression
    initialQuality: 0.8 // Initial quality (0.8 = 80%)
};

// Store compressed files
const compressedFiles = {
    front_photo: null,
    side_photo: null,
    back_photo: null
};

// Initialize file inputs
document.addEventListener('DOMContentLoaded', function() {
    const fileInputs = document.querySelectorAll('.file-input');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', async function(e) {
            const file = e.target.files[0];
            const previewId = this.getAttribute('data-preview');
            const infoId = this.getAttribute('data-info');
            const fieldName = this.getAttribute('name');
            
            if (!file) return;
            
            // Show loading state
            const infoElement = document.getElementById(infoId);
            infoElement.textContent = 'Compressing...';
            infoElement.style.display = 'block';
            
            try {
                // Show original file size
                const originalSize = (file.size / 1024 / 1024).toFixed(2);
                
                // Compress the image
                const compressedFile = await imageCompression(file, compressionOptions);
                
                // Calculate compressed size
                const compressedSize = (compressedFile.size / 1024 / 1024).toFixed(2);
                const sizeReduction = ((1 - compressedFile.size / file.size) * 100).toFixed(1);
                
                // Store compressed file
                compressedFiles[fieldName] = compressedFile;
                
                // Update preview
                const preview = document.getElementById(previewId);
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    infoElement.textContent = `Reduced: ${originalSize}MB → ${compressedSize}MB (${sizeReduction}% smaller)`;
                    infoElement.style.display = 'block';
                };
                reader.readAsDataURL(compressedFile);
                
            } catch (error) {
                console.error('Compression error:', error);
                infoElement.textContent = 'Compression failed, using original';
                infoElement.style.display = 'block';
                
                // Fallback to original file
                compressedFiles[fieldName] = file;
                previewImage(this, previewId);
            }
        });
        
        // Make file inputs easier to tap on mobile
        input.addEventListener('touchstart', function(e) {
            e.stopPropagation();
        }, { passive: true });
    });
    
    // Add visual feedback for touch interactions
    const uploadButtons = document.querySelectorAll('.photo-upload-btn');
    uploadButtons.forEach(button => {
        button.addEventListener('touchstart', function() {
            this.style.opacity = '0.7';
        });
        
        button.addEventListener('touchend', function() {
            this.style.opacity = '1';
        });
    });
    
    // Handle form submission with compressed files
    const form = document.getElementById('photoForm');
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading"></span> Processing...';
        
        try {
            // Replace original files with compressed ones
            const formData = new FormData(form);
            
            for (const [fieldName, compressedFile] of Object.entries(compressedFiles)) {
                if (compressedFile) {
                    formData.set(fieldName, compressedFile, compressedFile.name);
                }
            }
            
            // Submit the form with compressed files
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            // If we get here, the form was submitted successfully
            window.location.reload();
            
        } catch (error) {
            console.error('Upload error:', error);
            alert('Error uploading photos. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
    
    // Check orientation on load and resize with error handling
    function checkOrientation() {
        const warning = document.querySelector('.orientation-warning');
        if (warning) {
            if (window.innerHeight < 500 && window.matchMedia("(orientation: landscape)").matches) {
                warning.style.display = 'block';
            } else {
                warning.style.display = 'none';
            }
        }
    }
    
    // Safe event listener with error handling
    if (window.addEventListener) {
        window.addEventListener('resize', checkOrientation);
        checkOrientation();
    }
});

// Fallback preview function
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    
    if (file && preview) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}
</script>

<?php require_once 'footer.php'; ?>