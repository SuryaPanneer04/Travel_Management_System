<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$pageTitle = 'Change Password';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'All fields are required.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } else {
        try {
            // Fetch current password hash
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if ($user && password_verify($currentPassword, $user['password'])) {
                // Update password
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($updateStmt->execute([$newHash, $_SESSION['user_id']])) {
                    $success = 'Password changed successfully!';
                } else {
                    $error = 'Failed to update password in database.';
                }
            } else {
                $error = 'Incorrect current password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card p-4">
            <h5 class="fw-bold mb-4" style="color: #1e293b;"><i class="fas fa-key text-primary me-2"></i> Update Password</h5>

            <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    <div><?php echo $success; ?></div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo $error; ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label fw-medium">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required placeholder="Enter current password">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">New Password</label>
                    <input type="password" name="new_password" class="form-control" required placeholder="Enter new password (min 6 characters)">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-medium">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required placeholder="Confirm new password">
                </div>
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i> Save Changes</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
