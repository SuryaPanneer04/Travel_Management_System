<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('admin');

$pageTitle = 'Mail Configuration';
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $smtp_host = $_POST['smtp_host'] ?? '';
    $smtp_user = $_POST['smtp_user'] ?? '';
    $smtp_pass = $_POST['smtp_pass'] ?? '';
    $smtp_port = intval($_POST['smtp_port'] ?? 587);
    $smtp_secure = $_POST['smtp_secure'] ?? 'TLS';
    $from_email = $_POST['from_email'] ?? '';
    $from_name = $_POST['from_name'] ?? '';
    $global_footer = $_POST['global_footer'] ?? '';

    if (empty($smtp_host) || empty($smtp_user) || empty($from_email) || empty($from_name)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            // Check if settings row exists
            $stmt = $pdo->query("SELECT id FROM mail_settings LIMIT 1");
            $row = $stmt->fetch();

            if ($row) {
                // Update
                if (!empty($smtp_pass)) {
                    $updateStmt = $pdo->prepare("UPDATE mail_settings SET smtp_host = ?, smtp_user = ?, smtp_pass = ?, smtp_port = ?, smtp_secure = ?, from_email = ?, from_name = ?, global_footer = ? WHERE id = ?");
                    $updateStmt->execute([$smtp_host, $smtp_user, $smtp_pass, $smtp_port, $smtp_secure, $from_email, $from_name, $global_footer, $row['id']]);
                } else {
                    // Do not update password if left blank
                    $updateStmt = $pdo->prepare("UPDATE mail_settings SET smtp_host = ?, smtp_user = ?, smtp_port = ?, smtp_secure = ?, from_email = ?, from_name = ?, global_footer = ? WHERE id = ?");
                    $updateStmt->execute([$smtp_host, $smtp_user, $smtp_port, $smtp_secure, $from_email, $from_name, $global_footer, $row['id']]);
                }
            } else {
                // Insert
                $insertStmt = $pdo->prepare("INSERT INTO mail_settings (smtp_host, smtp_user, smtp_pass, smtp_port, smtp_secure, from_email, from_name, global_footer) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $insertStmt->execute([$smtp_host, $smtp_user, $smtp_pass, $smtp_port, $smtp_secure, $from_email, $from_name, $global_footer]);
            }
            $success = 'Configuration saved successfully!';
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch current configurations
$smtp = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_user' => '',
    'smtp_pass' => '',
    'smtp_port' => 587,
    'smtp_secure' => 'TLS',
    'from_email' => '',
    'from_name' => 'Travel Management System',
    'global_footer' => 'Regards, Team Travel Management System'
];

try {
    $stmt = $pdo->query("SELECT * FROM mail_settings LIMIT 1");
    $dbSettings = $stmt->fetch();
    if ($dbSettings) {
        $smtp = $dbSettings;
    }
} catch (PDOException $e) {
    // Ignore and use default
}

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card border shadow-sm p-4 rounded-4" style="background-color: #fff;">
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

            <form method="POST" action="">
                <!-- SMTP Settings -->
                <h5 class="fw-bold mb-3" style="color: #1e293b;">SMTP Settings</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-secondary" style="font-size: 0.85rem;">SMTP Host</label>
                        <input type="text" name="smtp_host" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($smtp['smtp_host']); ?>" placeholder="smtp.gmail.com" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-secondary" style="font-size: 0.85rem;">SMTP User (Email)</label>
                        <input type="email" name="smtp_user" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($smtp['smtp_user']); ?>" placeholder="example@gmail.com" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-secondary" style="font-size: 0.85rem;">SMTP Password / App Password</label>
                        <input type="password" name="smtp_pass" class="form-control bg-light border-0" value="" placeholder="••••••••••••••••">
                        <div class="form-text text-muted" style="font-size: 0.75rem;">Leave empty to keep the existing password.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-secondary" style="font-size: 0.85rem;">Port</label>
                        <input type="number" name="smtp_port" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($smtp['smtp_port']); ?>" placeholder="587" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-secondary" style="font-size: 0.85rem;">Secure</label>
                        <select name="smtp_secure" class="form-select bg-light border-0">
                            <option value="TLS" <?php echo $smtp['smtp_secure'] === 'TLS' ? 'selected' : ''; ?>>TLS</option>
                            <option value="SSL" <?php echo $smtp['smtp_secure'] === 'SSL' ? 'selected' : ''; ?>>SSL</option>
                            <option value="None" <?php echo $smtp['smtp_secure'] === 'None' ? 'selected' : ''; ?>>None</option>
                        </select>
                    </div>
                </div>

                <hr class="my-4 text-muted opacity-25">

                <!-- Sender Details -->
                <h5 class="fw-bold mb-3" style="color: #1e293b;">Sender Details</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-secondary" style="font-size: 0.85rem;">From Email</label>
                        <input type="email" name="from_email" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($smtp['from_email']); ?>" placeholder="noreply@example.com" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-secondary" style="font-size: 0.85rem;">From Name</label>
                        <input type="text" name="from_name" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($smtp['from_name']); ?>" placeholder="Travel Management System" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold text-secondary" style="font-size: 0.85rem;">Global Email Footer (HTML Supported)</label>
                        <textarea name="global_footer" class="form-control bg-light border-0" rows="3" placeholder="Regards, Team Travel Management System"><?php echo htmlspecialchars($smtp['global_footer']); ?></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-2 px-4 shadow-sm" style="border-radius: 8px;">
                        <i class="fas fa-save"></i> Save Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
