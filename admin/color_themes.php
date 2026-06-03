<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('admin');

$pageTitle = 'Theme Settings';
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sidebar_bg_color = $_POST['sidebar_bg_color'] ?? '#0f172a';

    // Validate hex color
    if (!preg_match('/^#[a-f0-9]{6}$/i', $sidebar_bg_color)) {
        $error = 'Invalid color format. Please select a valid HEX color.';
    } else {
        try {
            // Check if settings exist
            $stmt = $pdo->query("SELECT id FROM theme_settings LIMIT 1");
            $row = $stmt->fetch();

            if ($row) {
                // Update
                $updateStmt = $pdo->prepare("UPDATE theme_settings SET sidebar_bg_color = ? WHERE id = ?");
                $updateStmt->execute([$sidebar_bg_color, $row['id']]);
            } else {
                // Insert
                $insertStmt = $pdo->prepare("INSERT INTO theme_settings (sidebar_bg_color) VALUES (?)");
                $insertStmt->execute([$sidebar_bg_color]);
            }
            $success = 'Sidebar theme settings saved successfully!';
            // Update the local variable for immediately rendering the header with the updated theme color
            $sidebar_bg = $sidebar_bg_color;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch current theme color
$themeColor = '#0f172a';
try {
    $stmt = $pdo->query("SELECT sidebar_bg_color FROM theme_settings LIMIT 1");
    $dbColor = $stmt->fetch();
    if ($dbColor) {
        $themeColor = $dbColor['sidebar_bg_color'];
    }
} catch (PDOException $e) {
    // Ignore and use default
}

require_once '../includes/header.php';
?>

<div class="mb-4">
    <h3 class="fw-bold mb-1">Theme Settings</h3>
    <p class="text-muted mb-0">Customize the appearance of your VMS Pro workspace.</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
        <i class="fas fa-check-circle"></i>
        <div><?php echo $success; ?></div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
        <i class="fas fa-exclamation-circle"></i>
        <div><?php echo $error; ?></div>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Left Column: Config Panel -->
    <div class="col-md-6 col-lg-5">
        <div class="card border shadow-sm p-4 rounded-4" style="background-color: #fff; height: 100%;">
            <h5 class="fw-bold mb-4" style="color: #1e293b; font-size: 1.1rem; letter-spacing: -0.2px;">Sidebar Configuration</h5>
            
            <form method="POST" action="">
                <label class="form-label fw-bold text-secondary text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 0.8px;">Choose Sidebar Background Color</label>
                
                <!-- Color Selector Box -->
                <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-4 mb-4" style="border: 1px solid #f1f5f9;">
                    <div style="position: relative; width: 50px; height: 50px; border-radius: 10px; overflow: hidden; cursor: pointer; border: 1px solid #e2e8f0; box-shadow: inset 0 2px 4px rgba(0,0,0,0.06);">
                        <input type="color" id="colorPicker" name="sidebar_bg_color" value="<?php echo htmlspecialchars($themeColor); ?>" style="position: absolute; top: -10px; left: -10px; width: 80px; height: 80px; border: none; cursor: pointer;">
                    </div>
                    <div>
                        <div class="fw-bold mb-0" id="colorHexText" style="color: #1e293b; font-size: 1.05rem; font-family: monospace;"><?php echo htmlspecialchars($themeColor); ?></div>
                        <small class="text-muted" style="font-size: 0.8rem;">Select a color to change the sidebar theme instantly.</small>
                    </div>
                </div>

                <!-- Contrast Warning / Note -->
                <div class="alert alert-primary d-flex gap-3 p-3 rounded-4 mb-4 border-0" style="background-color: #eff6ff; color: #1d4ed8; font-size: 0.85rem;">
                    <div style="font-size: 1.2rem; line-height: 1;">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div>
                        This change will apply globally to all users' sidebars. Choose a color that maintains high contrast for text readability.
                    </div>
                </div>

                <button type="submit" class="btn btn-primary d-flex align-items-center gap-2 px-4 py-2.5 shadow-sm" style="border-radius: 8px;">
                    <i class="fas fa-save"></i> Save Theme Changes
                </button>
            </form>
        </div>
    </div>

    <!-- Right Column: Live Preview Mockup -->
    <div class="col-md-6 col-lg-5">
        <div class="card border shadow-sm p-4 rounded-4 text-center" style="background-color: #fff; height: 100%;">
            <h5 class="fw-bold mb-4 text-start" style="color: #1e293b; font-size: 1.1rem; letter-spacing: -0.2px;">Live Preview</h5>
            
            <!-- Sidebar Miniature Mockup -->
            <div class="d-flex flex-column align-items-center justify-content-center p-4 bg-light rounded-4 border-0 mb-3" style="min-height: 280px;">
                <div id="sidebarMockup" class="p-3 shadow-lg rounded-3 d-flex flex-column gap-3 align-items-start" style="width: 170px; height: 230px; background-color: <?php echo htmlspecialchars($themeColor); ?>; transition: background-color 0.15s ease;">
                    <!-- Logo mock -->
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="bg-primary rounded-circle" style="width: 14px; height: 14px; background-color: #3b82f6 !important;"></div>
                        <div style="width: 50px; height: 6px; background-color: rgba(255,255,255,0.4); border-radius: 3px;"></div>
                    </div>
                    <!-- Menu item 1 active -->
                    <div class="d-flex align-items-center gap-2 w-100 p-1.5 rounded" style="background-color: rgba(255,255,255,0.1);">
                        <div class="bg-primary rounded-circle" style="width: 10px; height: 10px; background-color: #10b981 !important;"></div>
                        <div style="width: 60px; height: 5px; background-color: #10b981; border-radius: 3px;"></div>
                    </div>
                    <!-- Menu item 2 -->
                    <div class="d-flex align-items-center gap-2 w-100 p-1.5">
                        <div class="rounded-circle" style="width: 10px; height: 10px; background-color: rgba(255,255,255,0.3);"></div>
                        <div style="width: 45px; height: 5px; background-color: rgba(255,255,255,0.3); border-radius: 3px;"></div>
                    </div>
                    <!-- Menu item 3 -->
                    <div class="d-flex align-items-center gap-2 w-100 p-1.5">
                        <div class="rounded-circle" style="width: 10px; height: 10px; background-color: rgba(255,255,255,0.3);"></div>
                        <div style="width: 55px; height: 5px; background-color: rgba(255,255,255,0.3); border-radius: 3px;"></div>
                    </div>
                </div>
            </div>
            <small class="text-muted" style="font-size: 0.8rem;">Your sidebar will look like this.</small>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const colorPicker = document.getElementById('colorPicker');
    const colorHexText = document.getElementById('colorHexText');
    const sidebarMockup = document.getElementById('sidebarMockup');

    colorPicker.addEventListener('input', function() {
        const selectedColor = colorPicker.value;
        colorHexText.textContent = selectedColor.toUpperCase();
        sidebarMockup.style.backgroundColor = selectedColor;
        
        // Also update the actual sidebar page element in real time to show live effect
        const sidebarEl = document.getElementById('sidebar');
        if (sidebarEl) {
            sidebarEl.style.backgroundColor = selectedColor;
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
