<?php
// admin/devotions.php - CRUD for 365 devotions
$pageTitle = "Manage Devotions";
require_once '../header.php';
requireAdmin();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_devotion'])) {
        $devotion_day = sanitize($_POST['devotion_day']);
        $title = sanitize($_POST['title']);
        $verse_reference = sanitize($_POST['verse_reference']);
        $verse_text = sanitize($_POST['verse_text']);
        $devotional_text = sanitize($_POST['devotional_text']);
        $reflection_question = sanitize($_POST['reflection_question']);
        
        $insert_query = "INSERT INTO devotions (devotion_day, title, verse_reference, verse_text, devotional_text, reflection_question) 
                        VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($insert_query);
        $stmt->execute([$devotion_day, $title, $verse_reference, $verse_text, $devotional_text, $reflection_question]);
        
        echo "<script>window.location.href = 'devotions.php';</script>";
        exit();
    }
    
    if (isset($_POST['update_devotion'])) {
        $devotion_id = sanitize($_POST['devotion_id']);
        $title = sanitize($_POST['title']);
        $verse_reference = sanitize($_POST['verse_reference']);
        $verse_text = sanitize($_POST['verse_text']);
        $devotional_text = sanitize($_POST['devotional_text']);
        $reflection_question = sanitize($_POST['reflection_question']);
        
        $update_query = "UPDATE devotions SET title = ?, verse_reference = ?, verse_text = ?, devotional_text = ?, reflection_question = ? 
                        WHERE id = ?";
        $stmt = $db->prepare($update_query);
        $stmt->execute([$title, $verse_reference, $verse_text, $devotional_text, $reflection_question, $devotion_id]);
        
        echo "<script>window.location.href = 'devotions.php';</script>";
        exit();
    }
    
    if (isset($_POST['delete_devotion'])) {
        $devotion_id = sanitize($_POST['devotion_id']);
        
        $delete_query = "DELETE FROM devotions WHERE id = ?";
        $stmt = $db->prepare($delete_query);
        $stmt->execute([$devotion_id]);
        
        echo "<script>window.location.href = 'devotions.php';</script>";
        exit();
    }
}

// Get all devotions
$devotions_query = "SELECT * FROM devotions ORDER BY devotion_day";
$stmt = $db->prepare($devotions_query);
$stmt->execute();
$devotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <h1 class="card-title">Manage Devotions (<?php echo count($devotions); ?>/365)</h1>
    <p style="color: var(--light-text); margin-bottom: 2rem;">
        Manage the 365-day devotional content. Users will see devotions based on their account creation date.
    </p>
</div>

<!-- Add New Devotion Form -->
<div class="card">
    <h2 class="card-title">Add New Devotion</h2>
    <form method="POST">
        <div style="display: grid; grid-template-columns: 1fr 3fr; gap: 1rem;">
            <div class="form-group">
                <label for="devotion_day">Day Number</label>
                <input type="number" id="devotion_day" name="devotion_day" class="input" 
                       min="1" max="365" required placeholder="1-365">
            </div>
            
            <div class="form-group">
                <label for="title">Devotion Title</label>
                <input type="text" id="title" name="title" class="input" 
                       placeholder="Title of the devotion" required>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="verse_reference">Bible Reference</label>
                <input type="text" id="verse_reference" name="verse_reference" class="input" 
                       placeholder="e.g., 1 Corinthians 6:19-20" required>
            </div>
            
            <div class="form-group">
                <label for="verse_text">Verse Text</label>
                <textarea id="verse_text" name="verse_text" class="input" 
                          rows="2" placeholder="Full verse text..." required></textarea>
            </div>
        </div>
        
        <div class="form-group">
            <label for="devotional_text">Devotional Content</label>
            <textarea id="devotional_text" name="devotional_text" class="input" 
                      rows="6" placeholder="Write the devotional message..." required></textarea>
        </div>
        
        <div class="form-group">
            <label for="reflection_question">Reflection Question</label>
            <textarea id="reflection_question" name="reflection_question" class="input" 
                      rows="2" placeholder="Question for personal reflection..." required></textarea>
        </div>
        
        <button type="submit" name="add_devotion" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Devotion
        </button>
    </form>
</div>

<!-- Existing Devotions -->
<div class="card">
    <h2 class="card-title">All Devotions</h2>
    
    <?php if ($devotions): ?>
        <div style="display: grid; gap: 1rem;">
            <?php foreach ($devotions as $devotion): ?>
            <div class="card" style="padding: 1.5rem;">
                <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <h3 style="color: var(--accent); margin-bottom: 0.5rem;">
                            Day <?php echo $devotion['devotion_day']; ?>: <?php echo $devotion['title']; ?>
                        </h3>
                        <p style="color: var(--light-text); margin-bottom: 0.5rem;">
                            <strong>Scripture:</strong> <?php echo $devotion['verse_reference']; ?>
                        </p>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" class="btn btn-outline" onclick="editDevotion(<?php echo $devotion['id']; ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="devotion_id" value="<?php echo $devotion['id']; ?>">
                            <button type="submit" name="delete_devotion" class="btn btn-outline" 
                                    onclick="return confirm('Are you sure you want to delete this devotion?')"
                                    style="color: #f44336; border-color: #f44336;">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
                
                <div style="background: var(--glass-bg); padding: 1rem; border-radius: var(--radius); margin-bottom: 1rem;">
                    <p style="font-style: italic; margin-bottom: 0.5rem;">
                        "<?php echo $devotion['verse_text']; ?>"
                    </p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <p style="line-height: 1.6;"><?php echo nl2br($devotion['devotional_text']); ?></p>
                </div>
                
                <div style="background: var(--gradient-primary); padding: 1rem; border-radius: var(--radius);">
                    <p style="font-weight: 600; margin: 0;"><?php echo $devotion['reflection_question']; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 3rem; color: var(--light-text);">
            <i class="fas fa-bible" style="font-size: 3rem; margin-bottom: 1rem;"></i>
            <p>No devotions created yet. Add your first devotion above.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function editDevotion(devotionId) {
    // This would open a modal or redirect to edit page
    // For now, we'll just alert
    alert('Edit functionality would open here for devotion ID: ' + devotionId);
    // In a full implementation, you'd have:
    // window.location.href = 'edit_devotion.php?id=' + devotionId;
}
</script>

<?php require_once '../footer.php'; ?>