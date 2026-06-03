<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('admin');

$pageTitle = 'Location Management';
$success = '';
$error = '';

// Handle Location Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_location'])) {
    $location_name = trim($_POST['location_name']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO locations (location_name) VALUES (?)");
        if ($stmt->execute([$location_name])) {
            $success = 'Location added successfully!';
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Integrity constraint violation (duplicate)
            $error = 'Location already exists!';
        } else {
            $error = 'Error adding location: ' . $e->getMessage();
        }
    }
}

// Handle Location Editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_location'])) {
    $id = $_POST['location_id'];
    $location_name = trim($_POST['location_name']);
    
    try {
        $stmt = $pdo->prepare("UPDATE locations SET location_name=? WHERE id=?");
        if ($stmt->execute([$location_name, $id])) {
            $success = 'Location updated successfully!';
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = 'Another location with this name already exists!';
        } else {
            $error = 'Error updating location: ' . $e->getMessage();
        }
    }
}

// Handle Location Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_location') {
    $id = $_GET['id'];
    
    try {
        // Check if location is used in hotels
        $hotelCheck = $pdo->prepare("SELECT COUNT(*) as count FROM hotels WHERE location = ?");
        $hotelCheck->execute([$id]);
        $hotelCount = $hotelCheck->fetch()['count'];

        // Check if location is used in cabs
        $cabCheck = $pdo->prepare("SELECT COUNT(*) as count FROM cabs WHERE location = ?");
        $cabCheck->execute([$id]);
        $cabCount = $cabCheck->fetch()['count'];

        $totalCount = $hotelCount + $cabCount;
        
        if ($totalCount > 0) {
            $error = "Cannot delete this location as it is assigned to {$hotelCount} hotel(s) and {$cabCount} cab(s).";
        } else {
            $stmt = $pdo->prepare("DELETE FROM locations WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Location deleted successfully!';
            header("Location: master_locations.php?success=" . urlencode($success));
            exit();
        }
    } catch (PDOException $e) {
        $error = 'Error deleting location: ' . $e->getMessage();
    }
}

if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

$locations = $pdo->query("SELECT * FROM locations ORDER BY location_name ASC")->fetchAll();

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-lg-12">
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm p-4 mb-4">
            <h5 class="fw-bold mb-4 text-primary"><i class="fas fa-plus-circle me-2"></i>Add New Location</h5>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-medium">Location Name</label>
                    <input type="text" name="location_name" class="form-control" placeholder="Enter city or area name" required>
                </div>
                <button type="submit" name="add_location" class="btn btn-primary w-100 py-2 fw-bold">
                    <i class="fas fa-save me-2"></i>Save Location
                </button>
            </form>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold mb-4 text-primary"><i class="fas fa-list me-2"></i>Location Master List</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Location Name</th>
                            <th>Date Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locations as $loc): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($loc['location_name']); ?></div>
                            </td>
                            <td>
                                <div class="small text-muted"><?php echo date('d M Y, h:i A', strtotime($loc['created_at'])); ?></div>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-secondary" 
                                            onclick='editLocation(<?php echo json_encode($loc); ?>)' 
                                            title="Edit Location">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?action=delete_location&id=<?php echo $loc['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Are you sure you want to delete this location?')" 
                                       title="Delete Location">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($locations)): ?>
                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">No locations found in the master list.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Location Modal -->
<div class="modal fade" id="editLocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Edit Location</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="location_id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Location Name</label>
                        <input type="text" name="location_name" id="edit_name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_location" class="btn btn-primary px-4">Update Location</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editLocation(loc) {
    document.getElementById('edit_id').value = loc.id;
    document.getElementById('edit_name').value = loc.location_name;
    var myModal = new bootstrap.Modal(document.getElementById('editLocationModal'));
    myModal.show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
