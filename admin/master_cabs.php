<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('admin');

$pageTitle = 'Cab Management';
$success = '';
$error = '';

// Handle Cab Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cab'])) {
    $provider = $_POST['provider_name'];
    $location = $_POST['location'];
    $type = $_POST['vehicle_type'];
    $number = $_POST['vehicle_number'];
    $contact = $_POST['contact'];
    $driver = $_POST['driver_name'];
    $driver_contact = $_POST['driver_contact'];
    $capacity = $_POST['capacity'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO cabs (provider_name, location, vehicle_type, vehicle_number, contact_number, driver_name, driver_contact, capacity, added_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$provider, $location, $type, $number, $contact, $driver, $driver_contact, $capacity, $_SESSION['user_id']])) {
            $success = 'Cab provider added successfully!';
        }
    } catch (PDOException $e) {
        $error = 'Error adding cab: ' . $e->getMessage();
    }
}

// Handle Cab Editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_cab'])) {
    $id = $_POST['cab_id'];
    $provider = $_POST['provider_name'];
    $location = $_POST['location'];
    $type = $_POST['vehicle_type'];
    $number = $_POST['vehicle_number'];
    $contact = $_POST['contact'];
    $driver = $_POST['driver_name'];
    $driver_contact = $_POST['driver_contact'];
    $capacity = $_POST['capacity'];
    $status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE cabs SET provider_name=?, location=?, vehicle_type=?, vehicle_number=?, contact_number=?, driver_name=?, driver_contact=?, capacity=?, status=? WHERE id=?");
        if ($stmt->execute([$provider, $location, $type, $number, $contact, $driver, $driver_contact, $capacity, $status, $id])) {
            $success = 'Cab details updated successfully!';
        }
    } catch (PDOException $e) {
        $error = 'Error updating cab: ' . $e->getMessage();
    }
}

// Handle Cab Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_cab') {
    $id = $_GET['id'];
    
    // Check if cab is assigned to any arrangement
    $check = $pdo->prepare("SELECT id FROM arrangements WHERE cab_no = ?");
    $check->execute([$id]);
    
    if ($check->fetch()) {
        $error = 'This cab is already assigned to a tourist arrangement. Please reassign the cab before deleting.';
    } else {
        // Delete the cab
        $stmt = $pdo->prepare("DELETE FROM cabs WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Cab provider deleted successfully!';
        header("Location: master_cabs.php?success=" . urlencode($success));
        exit();
    }
}

if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

$filter_location = isset($_GET['filter_location']) ? $_GET['filter_location'] : '';

if ($filter_location) {
    $stmt = $pdo->prepare("SELECT c.*, l.location_name FROM cabs c LEFT JOIN locations l ON c.location = l.id WHERE c.location = ? ORDER BY c.id DESC");
    $stmt->execute([$filter_location]);
    $cabs = $stmt->fetchAll();
} else {
    $cabs = $pdo->query("SELECT c.*, l.location_name FROM cabs c LEFT JOIN locations l ON c.location = l.id ORDER BY c.id DESC")->fetchAll();
}

$locations = $pdo->query("SELECT * FROM locations ORDER BY location_name ASC")->fetchAll();

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-lg-12">
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm p-4 mb-4">
            <h5 class="fw-bold mb-4 text-primary"><i class="fas fa-plus-circle me-2"></i>Add New Cab</h5>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-medium">Provider Name</label>
                    <input type="text" name="provider_name" class="form-control" placeholder="Enter provider name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">Location</label>
                    <select name="location" class="form-select" required>
                        <option value="" disabled selected>Select Location</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo htmlspecialchars($loc['id']); ?>"><?php echo htmlspecialchars($loc['location_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-medium">Vehicle Type</label>
                        <input type="text" name="vehicle_type" class="form-control" placeholder="SUV, Sedan" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-medium">Vehicle No</label>
                        <input type="text" name="vehicle_number" class="form-control" placeholder="TN 01 AB 1234" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">Contact Number</label>
                    <input type="text" name="contact" class="form-control" placeholder="Provider contact" required>
                </div>
                <hr class="my-4">
                <h6 class="fw-bold mb-3">Driver Details (Optional)</h6>
                <div class="mb-3">
                    <label class="form-label fw-medium">Driver Name</label>
                    <input type="text" name="driver_name" class="form-control" placeholder="Enter driver name">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">Driver Contact</label>
                    <input type="text" name="driver_contact" class="form-control" placeholder="Driver contact">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-medium">Capacity (Persons)</label>
                    <input type="number" name="capacity" class="form-control" value="4" min="1">
                </div>
                <button type="submit" name="add_cab" class="btn btn-primary w-100 py-2 fw-bold">
                    <i class="fas fa-save me-2"></i>Save Cab Details
                </button>
            </form>
        </div>
    </div>
    
    
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold m-0 text-primary"><i class="fas fa-list me-2"></i>Cab Master List</h5>
                <form method="GET" class="d-flex gap-2">
                    <select name="filter_location" class="form-select form-select-sm" style="min-width: 200px;" onchange="this.form.submit()">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo htmlspecialchars($loc['id']); ?>" <?php echo $filter_location == $loc['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($loc['location_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Cab Info</th>
                            <th>Provider & Contact</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cabs as $cab): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?php echo $cab['vehicle_number']; ?></div>
                                <div class="small text-muted"><?php echo $cab['vehicle_type']; ?> (<?php echo $cab['capacity']; ?> Seater)</div>
                            </td>
                            <td>
                                <div class="fw-medium"><?php echo $cab['provider_name']; ?></div>
                                <div class="small text-muted"><i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($cab['location_name'] ?? 'N/A'); ?></div>
                                <div class="small text-muted mt-1"><i class="fas fa-phone-alt me-1"></i> <?php echo $cab['contact_number']; ?></div>
                            </td>
                            <td>
                                <?php if ($cab['status'] === 'available'): ?>
                                    <span class="badge bg-success-subtle text-success px-3">Available</span>
                                <?php elseif ($cab['status'] === 'booked'): ?>
                                    <span class="badge bg-primary-subtle text-primary px-3">Booked</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger px-3">Maintenance</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick='viewDetails(<?php echo json_encode($cab); ?>)' 
                                            title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" 
                                            onclick='editCab(<?php echo json_encode($cab); ?>)' 
                                            title="Edit Cab">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?action=delete_cab&id=<?php echo $cab['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Are you sure you want to delete this cab?')" 
                                       title="Delete Cab">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($cabs)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No cabs found in the master list.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Cab Modal -->
<div class="modal fade" id="editCabModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Edit Cab Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="cab_id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Provider Name</label>
                        <input type="text" name="provider_name" id="edit_provider" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Location</label>
                        <select name="location" id="edit_location" class="form-select" required>
                            <option value="" disabled>Select Location</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo htmlspecialchars($loc['id']); ?>"><?php echo htmlspecialchars($loc['location_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Vehicle Type</label>
                            <input type="text" name="vehicle_type" id="edit_type" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Vehicle No</label>
                            <input type="text" name="vehicle_number" id="edit_number" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Contact Number</label>
                        <input type="text" name="contact" id="edit_contact" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Driver Name</label>
                            <input type="text" name="driver_name" id="edit_driver" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Driver Contact</label>
                            <input type="text" name="driver_contact" id="edit_driver_contact" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Capacity</label>
                            <input type="number" name="capacity" id="edit_capacity" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Status</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="available">Available</option>
                                <option value="booked">Booked</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_cab" class="btn btn-primary px-4">Update Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-info-circle me-2"></i>Full Cab Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="view_details_content">
                <!-- Content populated by JS -->
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function editCab(cab) {
    document.getElementById('edit_id').value = cab.id;
    document.getElementById('edit_provider').value = cab.provider_name;
    document.getElementById('edit_location').value = cab.location || '';
    document.getElementById('edit_type').value = cab.vehicle_type;
    document.getElementById('edit_number').value = cab.vehicle_number;
    document.getElementById('edit_contact').value = cab.contact_number;
    document.getElementById('edit_driver').value = cab.driver_name || '';
    document.getElementById('edit_driver_contact').value = cab.driver_contact || '';
    document.getElementById('edit_capacity').value = cab.capacity;
    document.getElementById('edit_status').value = cab.status;
    
    var myModal = new bootstrap.Modal(document.getElementById('editCabModal'));
    myModal.show();
}

function viewDetails(cab) {
    let content = `
        <div class="mb-4">
            <label class="small text-muted text-uppercase fw-bold">Vehicle Information</label>
            <div class="d-flex justify-content-between border-bottom py-2">
                <span>Vehicle Number:</span>
                <span class="fw-bold">${cab.vehicle_number}</span>
            </div>
            <div class="d-flex justify-content-between border-bottom py-2">
                <span>Vehicle Type:</span>
                <span class="fw-bold">${cab.vehicle_type}</span>
            </div>
            <div class="d-flex justify-content-between border-bottom py-2">
                <span>Capacity:</span>
                <span class="fw-bold">${cab.capacity} Persons</span>
            </div>
            <div class="d-flex justify-content-between border-bottom py-2">
                <span>Current Status:</span>
                <span class="badge ${cab.status === 'available' ? 'bg-success' : (cab.status === 'booked' ? 'bg-primary' : 'bg-danger')}">${cab.status.toUpperCase()}</span>
            </div>
        </div>
        
        <div class="mb-4">
            <label class="small text-muted text-uppercase fw-bold">Provider Information</label>
            <div class="d-flex justify-content-between border-bottom py-2">
                <span>Provider Name:</span>
                <span class="fw-bold">${cab.provider_name}</span>
            </div>
            <div class="d-flex justify-content-between border-bottom py-2">
                <span>Location:</span>
                <span class="fw-bold">${cab.location_name || 'N/A'}</span>
            </div>
            <div class="d-flex justify-content-between border-bottom py-2">
                <span>Contact Number:</span>
                <span class="fw-bold">${cab.contact_number}</span>
            </div>
        </div>
        
        <div>
            <label class="small text-muted text-uppercase fw-bold">Driver Information</label>
            <div class="d-flex justify-content-between border-bottom py-2">
                <span>Driver Name:</span>
                <span class="fw-bold">${cab.driver_name || 'Not Assigned'}</span>
            </div>
            <div class="d-flex justify-content-between border-bottom py-2">
                <span>Driver Contact:</span>
                <span class="fw-bold">${cab.driver_contact || 'Not Assigned'}</span>
            </div>
        </div>
    `;
    document.getElementById('view_details_content').innerHTML = content;
    var myModal = new bootstrap.Modal(document.getElementById('viewDetailsModal'));
    myModal.show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
