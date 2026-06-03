<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('admin');

$pageTitle = 'Mail Templates';
$success = '';
$error = '';

// Handle actions (Add / Edit / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = $_POST['name'] ?? '';
        $template_key = preg_replace('/[^a-z0-9_]/', '', strtolower(str_replace(' ', '_', $_POST['template_key'] ?? '')));
        $subject = $_POST['subject'] ?? '';
        $body = $_POST['body'] ?? '';
        $placeholders = $_POST['placeholders'] ?? '';

        if (empty($name) || empty($template_key) || empty($subject) || empty($body)) {
            $error = 'All fields are required for adding a template.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO mail_templates (name, template_key, subject, body, placeholders) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $template_key, $subject, $body, $placeholders]);
                $success = 'Template added successfully!';
            } catch (PDOException $e) {
                $error = 'Failed to add template: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = $_POST['name'] ?? '';
        $subject = $_POST['subject'] ?? '';
        $body = $_POST['body'] ?? '';
        $placeholders = $_POST['placeholders'] ?? '';
        $status = $_POST['status'] ?? 'active';

        if (empty($name) || empty($subject) || empty($body)) {
            $error = 'Name, Subject, and Body are required.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE mail_templates SET name = ?, subject = ?, body = ?, placeholders = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $subject, $body, $placeholders, $status, $id]);
                $success = 'Template updated successfully!';
            } catch (PDOException $e) {
                $error = 'Failed to update template: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM mail_templates WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Template deleted successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to delete template: ' . $e->getMessage();
        }
    }
}

// Fetch all templates
$templates = [];
$activeCount = 0;
try {
    $templates = $pdo->query("SELECT * FROM mail_templates ORDER BY id ASC")->fetchAll();
    foreach ($templates as $t) {
        if ($t['status'] === 'active') {
            $activeCount++;
        }
    }
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h3 class="fw-bold mb-1">Mail Templates</h3>
        <p class="text-muted mb-0">Manage and customize your system's automated emails.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addTemplateModal" style="border-radius: 8px;">
            <i class="fas fa-plus"></i> Add Template
        </button>
        <span class="btn btn-light border d-flex align-items-center gap-2 fw-semibold text-secondary" style="border-radius: 8px; cursor: default;">
            <i class="fas fa-envelope-open text-primary"></i> <?php echo $activeCount; ?> Active Templates
        </span>
    </div>
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
    <?php foreach ($templates as $t): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border shadow-sm rounded-4 overflow-hidden" style="background-color: #fff;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                            <i class="fas fa-file-alt" style="font-size: 1.25rem;"></i>
                        </div>
                        <div class="overflow-hidden">
                            <h6 class="fw-bold text-truncate mb-0" style="color: #1e293b;"><?php echo htmlspecialchars($t['name']); ?></h6>
                            <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;"><?php echo htmlspecialchars($t['template_key']); ?></small>
                        </div>
                        <?php if ($t['status'] === 'inactive'): ?>
                            <span class="badge bg-secondary-subtle text-secondary ms-auto px-2 py-1" style="font-size: 0.75rem;">Inactive</span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-semibold mb-1" style="font-size: 0.8rem;">Subject Line</label>
                        <input type="text" class="form-control bg-light border-0 py-2" value="<?php echo htmlspecialchars($t['subject']); ?>" readonly style="font-size: 0.85rem; color: #475569;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-semibold mb-1" style="font-size: 0.8rem;">Placeholders</label>
                        <div class="d-flex flex-wrap gap-1">
                            <?php 
                            if (!empty($t['placeholders'])) {
                                $tags = explode(',', $t['placeholders']);
                                foreach ($tags as $tag) {
                                    echo '<span class="badge bg-light border text-dark font-monospace py-1.5 px-2.5" style="font-size: 0.75rem; border-radius: 6px;">' . htmlspecialchars(trim($tag)) . '</span>';
                                }
                            } else {
                                echo '<span class="text-muted small">None</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light border-top-0 d-flex gap-2 p-3">
                    <button class="btn btn-dark w-100 d-flex align-items-center justify-content-center gap-2 py-2" 
                            style="border-radius: 8px;"
                            data-bs-toggle="modal" 
                            data-bs-target="#editTemplateModal<?php echo $t['id']; ?>">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this template?');" class="d-inline">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                        <button type="submit" class="btn btn-outline-danger px-3 py-2" style="border-radius: 8px;" title="Delete Template">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Modal for each template -->
        <div class="modal fade" id="editTemplateModal<?php echo $t['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                    <div class="modal-header border-bottom-0 p-4 pb-0">
                        <h5 class="modal-title fw-bold" style="color: #1e293b;">Edit Template</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="">
                        <div class="modal-body p-4">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?php echo $t['id']; ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 0.85rem;">Template Name</label>
                                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($t['name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 0.85rem;">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active" <?php echo $t['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $t['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold" style="font-size: 0.85rem;">Subject Line</label>
                                    <input type="text" name="subject" class="form-control" value="<?php echo htmlspecialchars($t['subject']); ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold" style="font-size: 0.85rem;">Template Body (HTML Supported)</label>
                                    <textarea name="body" class="form-control" rows="8" required><?php echo htmlspecialchars($t['body']); ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold" style="font-size: 0.85rem;">Placeholders (comma-separated, e.g. {{name}},{{otp}})</label>
                                    <input type="text" name="placeholders" class="form-control" value="<?php echo htmlspecialchars($t['placeholders']); ?>" placeholder="{{name}},{{otp}}">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top-0 p-4 pt-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4" style="border-radius: 8px;"><i class="fas fa-save me-2"></i>Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($templates)): ?>
        <div class="col-12 text-center py-5">
            <i class="fas fa-envelope-open-text fa-3x text-muted mb-3"></i>
            <h5 class="text-secondary fw-semibold">No Mail Templates Found</h5>
            <p class="text-muted">Create a template to begin customizing system emails.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Add Template Modal -->
<div class="modal fade" id="addTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom-0 p-4 pb-0">
                <h5 class="modal-title fw-bold" style="color: #1e293b;">Add New Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="add">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 0.85rem;">Template Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Account Registration" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 0.85rem;">Template Key (Unique Identifier)</label>
                            <input type="text" name="template_key" class="form-control" placeholder="e.g. account_registration" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size: 0.85rem;">Subject Line</label>
                            <input type="text" name="subject" class="form-control" placeholder="Enter email subject" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size: 0.85rem;">Template Body (HTML Supported)</label>
                            <textarea name="body" class="form-control" rows="8" placeholder="<h2>Welcome!</h2><p>Thank you for signing up...</p>" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size: 0.85rem;">Placeholders (comma-separated, e.g. {{name}},{{otp}})</label>
                            <input type="text" name="placeholders" class="form-control" placeholder="{{name}},{{otp}}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 p-4 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4" style="border-radius: 8px;"><i class="fas fa-plus me-2"></i>Create Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
