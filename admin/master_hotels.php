<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('admin');

$pageTitle = 'Hotel Management';
$success = '';
$error = '';

// Handle Hotel Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_hotel'])) {
    $name = $_POST['hotel_name'];
    $location = $_POST['location'];
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $rating = $_POST['star_rating'];
    $description = $_POST['description'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO hotels (hotel_name, location, address, contact_number, email, star_rating, description, added_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $location, $address, $contact, $email, $rating, $description, $_SESSION['user_id']])) {
            $success = 'Hotel added successfully!';
        }
    } catch (PDOException $e) {
        $error = 'Error adding hotel: ' . $e->getMessage();
    }
}

// Handle Hotel Editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_hotel'])) {
    $id = $_POST['hotel_id'];
    $name = $_POST['hotel_name'];
    $location = $_POST['location'];
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $rating = $_POST['star_rating'];
    $status = $_POST['status'];
    $description = $_POST['description'];
    
    try {
        $stmt = $pdo->prepare("UPDATE hotels SET hotel_name=?, location=?, address=?, contact_number=?, email=?, star_rating=?, status=?, description=? WHERE id=?");
        if ($stmt->execute([$name, $location, $address, $contact, $email, $rating, $status, $description, $id])) {
            $success = 'Hotel details updated successfully!';
        }
    } catch (PDOException $e) {
        $error = 'Error updating hotel: ' . $e->getMessage();
    }
}

// Handle Hotel Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_hotel') {
    $id = $_GET['id'];
    
    // Check if hotel is assigned anywhere (if applicable in future, for now just delete)
    try {
        $stmt = $pdo->prepare("DELETE FROM hotels WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Hotel deleted successfully!';
        header("Location: master_hotels.php?success=" . urlencode($success));
        exit();
    } catch (PDOException $e) {
        $error = 'Error deleting hotel: ' . $e->getMessage();
    }
}

if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

$filter_location = isset($_GET['filter_location']) ? $_GET['filter_location'] : '';

if ($filter_location) {
    $stmt = $pdo->prepare("SELECT h.*, l.location_name FROM hotels h LEFT JOIN locations l ON h.location = l.id WHERE h.location = ? ORDER BY h.id DESC");
    $stmt->execute([$filter_location]);
    $hotels = $stmt->fetchAll();
} else {
    $hotels = $pdo->query("SELECT h.*, l.location_name FROM hotels h LEFT JOIN locations l ON h.location = l.id ORDER BY h.id DESC")->fetchAll();
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
            <h5 class="fw-bold mb-4 text-primary"><i class="fas fa-plus-circle me-2"></i>Add New Hotel</h5>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-medium">Hotel Name</label>
                    <input type="text" name="hotel_name" class="form-control" placeholder="Enter hotel name" required>
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
                <div class="mb-3">
                    <label class="form-label fw-medium">Address</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="Full address"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-medium">Contact No</label>
                        <input type="text" name="contact" class="form-control" placeholder="Phone" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-medium">Star Rating</label>
                        <select name="star_rating" class="form-select">
                            <option value="1">1 Star</option>
                            <option value="2">2 Star</option>
                            <option value="3" selected>3 Star</option>
                            <option value="4">4 Star</option>
                            <option value="5">5 Star</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="hotel@example.com">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-medium">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Brief description"></textarea>
                </div>
                <button type="submit" name="add_hotel" class="btn btn-primary w-100 py-2 fw-bold">
                    <i class="fas fa-save me-2"></i>Save Hotel Details
                </button>
            </form>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold m-0 text-primary"><i class="fas fa-list me-2"></i>Hotel Master List</h5>
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
                            <th>Hotel Details</th>
                            <th>Contact Info</th>
                            <th>Rating & Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hotels as $hotel): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?php echo $hotel['hotel_name']; ?></div>
                                <div class="small text-muted"><i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($hotel['location_name'] ?? 'N/A'); ?></div>
                            </td>
                            <td>
                                <div class="fw-medium"><i class="fas fa-phone-alt me-1 small"></i> <?php echo $hotel['contact_number']; ?></div>
                                <div class="small text-muted"><?php echo $hotel['email']; ?></div>
                            </td>
                            <td>
                                <div class="mb-1">
                                    <?php for($i=0; $i<$hotel['star_rating']; $i++): ?>
                                        <i class="fas fa-star text-warning small"></i>
                                    <?php endfor; ?>
                                </div>
                                <?php if ($hotel['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success px-3">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger px-3">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick='viewHotelDetails(<?php echo json_encode($hotel); ?>)' 
                                            title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" 
                                            onclick='editHotel(<?php echo json_encode($hotel); ?>)' 
                                            title="Edit Hotel">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?action=delete_hotel&id=<?php echo $hotel['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Are you sure you want to delete this hotel?')" 
                                       title="Delete Hotel">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($hotels)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No hotels found in the master list.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Hotel Modal -->
<div class="modal fade" id="editHotelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Edit Hotel Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="hotel_id" id="edit_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Hotel Name</label>
                            <input type="text" name="hotel_name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Location</label>
                            <select name="location" id="edit_location" class="form-select" required>
                                <option value="" disabled>Select Location</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?php echo htmlspecialchars($loc['id']); ?>"><?php echo htmlspecialchars($loc['location_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Address</label>
                        <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Contact Number</label>
                            <input type="text" name="contact" id="edit_contact" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Star Rating</label>
                            <select name="star_rating" id="edit_rating" class="form-select">
                                <option value="1">1 Star</option>
                                <option value="2">2 Star</option>
                                <option value="3">3 Star</option>
                                <option value="4">4 Star</option>
                                <option value="5">5 Star</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-medium">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_hotel" class="btn btn-primary px-4">Update Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="viewHotelDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-info-circle me-2"></i>Full Hotel Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="view_hotel_content">
                <!-- Content populated by JS -->
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function editHotel(hotel) {
    document.getElementById('edit_id').value = hotel.id;
    document.getElementById('edit_name').value = hotel.hotel_name;
    document.getElementById('edit_location').value = hotel.location;
    document.getElementById('edit_address').value = hotel.address || '';
    document.getElementById('edit_contact').value = hotel.contact_number;
    document.getElementById('edit_email').value = hotel.email || '';
    document.getElementById('edit_rating').value = hotel.star_rating;
    document.getElementById('edit_status').value = hotel.status;
    document.getElementById('edit_description').value = hotel.description || '';
    
    var myModal = new bootstrap.Modal(document.getElementById('editHotelModal'));
    myModal.show();
}

function viewHotelDetails(hotel) {
    let ratingHtml = '';
    for(let i=0; i<hotel.star_rating; i++) {
        ratingHtml += '<i class="fas fa-star text-warning"></i>';
    }

    let content = `
        <div class="text-center mb-4">
            <h4 class="fw-bold mb-1">${hotel.hotel_name}</h4>
            <div class="mb-2">${ratingHtml}</div>
            <span class="badge ${hotel.status === 'active' ? 'bg-success' : 'bg-danger'}">${hotel.status.toUpperCase()}</span>
        </div>

        <div class="mb-4">
            <label class="small text-muted text-uppercase fw-bold">General Information</label>
            <div class="py-2 border-bottom">
                <div class="small text-muted">Location</div>
                <div class="fw-semibold">${hotel.location_name || 'N/A'}</div>
            </div>
            <div class="py-2 border-bottom">
                <div class="small text-muted">Full Address</div>
                <div class="fw-semibold">${hotel.address || 'N/A'}</div>
            </div>
        </div>
        
        <div class="mb-4">
            <label class="small text-muted text-uppercase fw-bold">Contact Details</label>
            <div class="d-flex justify-content-between border-bottom py-2">
                <span>Phone:</span>
                <span class="fw-bold">${hotel.contact_number}</span>
            </div>
            <div class="d-flex justify-content-between border-bottom py-2">
                <span>Email:</span>
                <span class="fw-bold">${hotel.email || 'N/A'}</span>
            </div>
        </div>
        
        <div>
            <label class="small text-muted text-uppercase fw-bold">Description</label>
            <div class="p-2 bg-light rounded small">
                ${hotel.description || 'No description provided.'}
            </div>
        </div>
    `;
    document.getElementById('view_hotel_content').innerHTML = content;
    var myModal = new bootstrap.Modal(document.getElementById('viewHotelDetailsModal'));
    myModal.show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
