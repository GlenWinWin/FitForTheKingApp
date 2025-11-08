<?php
$pageTitle = "Manage Exercises";
require_once '../header.php';
requireAdmin();

$day_id = $_GET['day_id'] ?? null;

if (!$day_id) {
    echo "<script>window.location.href = 'workout_plans.php';</script>";
    exit();
}

// Get day and plan details
$day_query = "SELECT wd.*, wp.name as plan_name, wp.id as plan_id 
             FROM workout_plan_days wd 
             JOIN workout_plans wp ON wd.plan_id = wp.id 
             WHERE wd.id = ?";
$stmt = $db->prepare($day_query);
$stmt->execute([$day_id]);
$day = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$day) {
    echo "<script>window.location.href = 'workout_plans.php';</script>";
    exit();
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_exercise'])) {
        $exercise_name = sanitize($_POST['exercise_name']);
        $youtube_link = sanitize($_POST['youtube_link']);
        $notes = sanitize($_POST['notes']);
        $default_sets = sanitize($_POST['default_sets']);
        $default_reps = sanitize($_POST['default_reps']);
        
        // Convert YouTube URL to embed format if needed
        if ($youtube_link && !str_contains($youtube_link, 'embed')) {
            if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $youtube_link, $matches)) {
                $youtube_link = "https://www.youtube.com/embed/" . $matches[1];
            }
        }
        
        $insert_query = "INSERT INTO workout_exercises (plan_day_id, exercise_name, youtube_link, notes, default_sets, default_reps) 
                        VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($insert_query);
        $stmt->execute([$day_id, $exercise_name, $youtube_link, $notes, $default_sets, $default_reps]);
        
        echo "<script>window.location.href = 'workout_exercises.php?day_id=" . $day_id."';</script>";
        exit();
    }
    
    if (isset($_POST['delete_exercise'])) {
        $exercise_id = sanitize($_POST['exercise_id']);
        
        $delete_query = "DELETE FROM workout_exercises WHERE id = ?";
        $stmt = $db->prepare($delete_query);
        $stmt->execute([$exercise_id]);
        
        echo "<script>window.location.href = 'workout_exercises.php?day_id=" . $day_id."';</script>";
        exit();
    }
}

// Get exercises for this day
$exercises_query = "SELECT * FROM workout_exercises WHERE plan_day_id = ? ORDER BY id";
$stmt = $db->prepare($exercises_query);
$stmt->execute([$day_id]);
$exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sample YouTube links for common exercises (you can replace these)
$sample_youtube_links = [
    "Squats" => "https://www.youtube.com/embed/aclHkVaku9U",
    "Push-ups" => "https://www.youtube.com/embed/IODxDxX7oi4",
    "Lunges" => "https://www.youtube.com/embed/D7KaRcUTQeE",
    "Bench Press" => "https://www.youtube.com/embed/vcBig73ojpE",
    "Deadlifts" => "https://www.youtube.com/embed/ytGaGIn3SjE",
    "Pull-ups" => "https://www.youtube.com/embed/eGo4IYlbE5g",
    "Shoulder Press" => "https://www.youtube.com/embed/qEwKCR5JCog",
    "Bicep Curls" => "https://www.youtube.com/embed/ykJmrZ5v0Oo",
    "Tricep Extensions" => "https://www.youtube.com/embed/_gsUck-7M74",
    "Plank" => "https://www.youtube.com/embed/pSHjTRCQxIw"
];
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Manage Exercises</h1>
        <div style="display: flex; gap: 0.5rem;">
            <a href="workout_plan_days.php?plan_id=<?php echo $day['plan_id']; ?>" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Days
            </a>
            <a href="workout_plans.php" class="btn btn-outline">
                <i class="fas fa-list"></i> All Plans
            </a>
        </div>
    </div>
    
    <div style="background: var(--gradient-primary); padding: 1.5rem; border-radius: var(--radius); margin-top: 1rem;">
        <h3 style="color: var(--accent); margin-bottom: 0.5rem;">
            <?php echo $day['plan_name']; ?> - Day <?php echo $day['day_order']; ?>
        </h3>
        <p style="color: var(--text); font-weight: 600; margin-bottom: 0.5rem;">
            <?php echo $day['title']; ?>
        </p>
        <?php if ($day['description']): ?>
        <p style="color: var(--light-text); margin: 0;">
            <?php echo $day['description']; ?>
        </p>
        <?php endif; ?>
    </div>
</div>

<!-- Add New Exercise Form -->
<div class="card">
    <h2 class="card-title">Add New Exercise</h2>
    
    <form method="POST">
        <div class="form-group">
            <label for="exercise_name">Exercise Name</label>
            <input type="text" id="exercise_name" name="exercise_name" class="input" 
                   placeholder="e.g., Barbell Squats, Push-ups, Dumbbell Rows" required>
        </div>
        
        <div class="form-group">
            <label for="youtube_link">YouTube Video Link</label>
            <input type="text" id="youtube_link" name="youtube_link" class="input" 
                   placeholder="https://www.youtube.com/embed/..." 
                   list="sample-links">
            <datalist id="sample-links">
                <?php foreach ($sample_youtube_links as $name => $link): ?>
                <option value="<?php echo $link; ?>"><?php echo $name; ?></option>
                <?php endforeach; ?>
            </datalist>
            <small style="color: var(--light-text); margin-top: 0.5rem; display: block;">
                Use YouTube embed links (format: https://www.youtube.com/embed/VIDEO_ID) or paste regular YouTube URLs
            </small>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="default_sets">Default Sets</label>
                <input type="number" id="default_sets" name="default_sets" class="input" 
                       min="1" max="10" value="3" required>
            </div>
            
            <div class="form-group">
                <label for="default_reps">Default Reps</label>
                <input type="number" id="default_reps" name="default_reps" class="input" 
                       min="1" max="50" value="10" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="notes">Exercise Notes (Optional)</label>
            <textarea id="notes" name="notes" class="input" 
                      rows="3" placeholder="Technique tips, variations, or instructions..."></textarea>
        </div>
        
        <button type="submit" name="add_exercise" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Exercise
        </button>
    </form>
</div>

<!-- Existing Exercises -->
<div class="card">
    <h2 class="card-title">Exercises (<?php echo count($exercises); ?>)</h2>
    
    <?php if ($exercises): ?>
        <div style="display: grid; gap: 1.5rem;">
            <?php foreach ($exercises as $exercise): ?>
            <div class="card" style="padding: 1.5rem;">
                <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <h3 style="color: var(--accent); margin-bottom: 0.5rem;">
                            <?php echo $exercise['exercise_name']; ?>
                        </h3>
                        <div style="display: flex; gap: 1rem; font-size: 0.9rem; color: var(--light-text); margin-bottom: 0.5rem;">
                            <span><i class="fas fa-layer-group"></i> <?php echo $exercise['default_sets']; ?> sets</span>
                            <span><i class="fas fa-repeat"></i> <?php echo $exercise['default_reps']; ?> reps</span>
                        </div>
                        <?php if ($exercise['notes']): ?>
                        <p style="color: var(--light-text); margin: 0;">
                            <?php echo $exercise['notes']; ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="exercise_id" value="<?php echo $exercise['id']; ?>">
                        <button type="submit" name="delete_exercise" class="btn btn-outline" 
                                onclick="return confirm('Are you sure you want to delete this exercise?')"
                                style="color: #f44336; border-color: #f44336;">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
                
                <?php if ($exercise['youtube_link']): ?>
                <div style="border-top: 1px solid var(--border); padding-top: 1rem;">
                    <h4 style="color: var(--text); margin-bottom: 1rem;">Exercise Video</h4>
                    <div class="video-container" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: var(--radius);">
                        <iframe src="<?php echo $exercise['youtube_link']; ?>" 
                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;"
                                allowfullscreen></iframe>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 3rem; color: var(--light-text);">
            <i class="fas fa-dumbbell" style="font-size: 3rem; margin-bottom: 1rem;"></i>
            <p>No exercises added yet. Add your first exercise above.</p>
        </div>
    <?php endif; ?>
</div>

<script>
// Auto-fill YouTube link when exercise name matches sample
document.getElementById('exercise_name').addEventListener('input', function() {
    const exerciseName = this.value.trim();
    const youtubeInput = document.getElementById('youtube_link');
    
    // Check if exercise name matches any sample
    const sampleLinks = <?php echo json_encode($sample_youtube_links); ?>;
    if (sampleLinks[exerciseName] && !youtubeInput.value) {
        youtubeInput.value = sampleLinks[exerciseName];
    }
});
</script>

<?php require_once '../footer.php'; ?>