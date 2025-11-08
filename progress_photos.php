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

if (!$is_friday && !$already_uploaded) {
    echo "<script>window.location.href = 'dashboard.php';</script>";
    exit();
}

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
        $notes = sanitize($_POST['notes'] ?? '');
        
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
    
    <div style="background: var(--gradient-primary); padding: 1.5rem; border-radius: var(--radius); margin-bottom: 2rem;">
        <h3 style="color: var(--accent); margin-bottom: 1rem;">
            <i class="fas fa-camera"></i> Track Your Transformation
        </h3>
        <p style="margin-bottom: 0.5rem;">
            <strong>Why progress photos?</strong> Photos provide visual evidence of your hard work and help you stay motivated.
        </p>
        <p style="margin-bottom: 0;">
            <strong>Tip:</strong> Wear similar clothing each time and take photos in the same location with consistent lighting.
        </p>
    </div>
</div>

<form method="POST" enctype="multipart/form-data">
    <div class="grid grid-3">
        <!-- Front View -->
        <div class="card">
            <h3 class="card-title" style="text-align: center;">Front View</h3>
            <div style="text-align: center; margin-bottom: 1rem;">
                <div style="width: 100%; max-width: 200px; height: 250px; margin: 0 auto; border: 2px dashed var(--border); border-radius: var(--radius); overflow: hidden; background: var(--glass-bg);">
                    <img id="frontPreview" src="<?php echo $today_photos['front_photo'] ?? 'imgs/template.jpg'; ?>" 
                         alt="Front View" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            </div>
            <div class="form-group">
                <label for="front_photo" class="btn btn-outline" style="width: 100%; text-align: center; cursor: pointer;">
                    <i class="fas fa-camera"></i> Upload Front Photo
                </label>
                <input type="file" id="front_photo" name="front_photo" accept="image/*" 
                       style="display: none;" onchange="previewImage(this, 'frontPreview')" <?php echo !$today_photos ? 'required' : ''; ?>>
            </div>
        </div>

        <!-- Side View -->
        <div class="card">
            <h3 class="card-title" style="text-align: center;">Side View</h3>
            <div style="text-align: center; margin-bottom: 1rem;">
                <div style="width: 100%; max-width: 200px; height: 250px; margin: 0 auto; border: 2px dashed var(--border); border-radius: var(--radius); overflow: hidden; background: var(--glass-bg);">
                    <img id="sidePreview" src="<?php echo $today_photos['side_photo'] ?? 'imgs/template.jpg'; ?>" 
                         alt="Side View" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            </div>
            <div class="form-group">
                <label for="side_photo" class="btn btn-outline" style="width: 100%; text-align: center; cursor: pointer;">
                    <i class="fas fa-camera"></i> Upload Side Photo
                </label>
                <input type="file" id="side_photo" name="side_photo" accept="image/*" 
                       style="display: none;" onchange="previewImage(this, 'sidePreview')" <?php echo !$today_photos ? 'required' : ''; ?>>
            </div>
        </div>

        <!-- Back View -->
        <div class="card">
            <h3 class="card-title" style="text-align: center;">Back View</h3>
            <div style="text-align: center; margin-bottom: 1rem;">
                <div style="width: 100%; max-width: 200px; height: 250px; margin: 0 auto; border: 2px dashed var(--border); border-radius: var(--radius); overflow: hidden; background: var(--glass-bg);">
                    <img id="backPreview" src="<?php echo $today_photos['back_photo'] ?? 'imgs/template.jpg'; ?>" 
                         alt="Back View" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            </div>
            <div class="form-group">
                <label for="back_photo" class="btn btn-outline" style="width: 100%; text-align: center; cursor: pointer;">
                    <i class="fas fa-camera"></i> Upload Back Photo
                </label>
                <input type="file" id="back_photo" name="back_photo" accept="image/*" 
                       style="display: none;" onchange="previewImage(this, 'backPreview')" <?php echo !$today_photos ? 'required' : ''; ?>>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="form-group">
            <label for="notes">Notes (Optional)</label>
            <div class="input-container">
                <textarea id="notes" name="notes" class="input" rows="3" placeholder="How are you feeling? Any observations about your progress?"><?php echo $today_photos['notes'] ?? ''; ?></textarea>
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem;">
            <a href="dashboard.php" class="btn btn-outline" style="flex: 1;">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <button type="submit" class="btn btn-primary" style="flex: 1;">
                <i class="fas fa-save"></i> 
                <?php echo $today_photos ? 'Update Photos' : 'Save Progress Photos'; ?>
            </button>
        </div>
    </div>
</form>

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
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