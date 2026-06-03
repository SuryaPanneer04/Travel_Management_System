<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('admin');

$pageTitle = 'Staff Management';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $uname = $_POST['username'];
    $fname = $_POST['full_name'];
    $email = $_POST['email'] ?? null;
    $number = $_POST['number'] ?? null;
    $department = $_POST['department'] ?? null;
    $country = $_POST['country'] ?? null;
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name, email, number, department, country) VALUES (?, ?, 'employee',?,?, ?, ?, ?)");
    if ($stmt->execute([$uname, $pass, $fname, $email, $number, $department, $country])) {
        $success = 'Employee added successfully!';
    }
}



if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'employee'");
    $stmt->execute([$id]);

    header("Location: master_staff.php?deleted=1");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_staff'])) {
    $id = $_POST['staff_id'];
    $uname = $_POST['username'];
    $fname = $_POST['full_name'];
    $email = $_POST['email'] ?? null;
    $number = $_POST['number'] ?? null;
    $department = $_POST['department'] ?? null;
    $country = $_POST['country'] ?? null;
    
    if (!empty($_POST['password'])) {
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET username=?, full_name=?, email=?, number=?, department=?, country=?, password=? WHERE id=? AND role='employee'");
        $stmt->execute([$uname, $fname, $email, $number, $department, $country, $pass, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username=?, full_name=?, email=?, number=?, department=?, country=? WHERE id=? AND role='employee'");
        $stmt->execute([$uname, $fname, $email, $number, $department, $country, $id]);
    }
    $success = 'Employee updated successfully!';
}

$staff = $pdo->query("SELECT * FROM users WHERE role = 'employee' ORDER BY id DESC")->fetchAll();

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-lg-4">
        <div class="card p-4 mb-4">
            <h5 class="fw-bold mb-4">Add New Employee</h5>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">department</label>
                    <select name="department" class="form-control" required>
                        <option value="">Select department...</option>
                        <option value="HR">HR</option>
                        <!-- <option value="Japan">manager</option> -->
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Country</label>
                    <select name="country" class="form-control" required>
                        <option value="">Select Country...</option>
                        <option value="India">India</option>
                        <option value="Japan">Japan</option>
                    </select>
                </div>    
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="number" class="form-control">
                </div>
                <div class="mb-4">
                    <label class="form-label">Temporary Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" name="add_staff" class="btn btn-primary w-100">Save Employee</button>
            </form>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card p-4">
            <h5 class="fw-bold mb-4">Staff List</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role / Location</th>
                            <th>Contact</th>
                            <th>Date Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff as $s): ?>
                        <tr>
                            <td class="fw-medium"><?php echo htmlspecialchars($s['full_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($s['username'] ?? ''); ?></td>
                            <td>
                                <div><small class="fw-bold text-primary"><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($s['department'] ?: 'N/A'); ?></small></div>
                                <div><small class="text-muted"><i class="fas fa-globe"></i> <?php echo htmlspecialchars($s['country'] ?: 'N/A'); ?></small></div>
                            </td>
                            <td>
                                <div><small><i class="fas fa-envelope text-muted"></i> <?php echo htmlspecialchars($s['email'] ?: 'N/A'); ?></small></div>
                                <div><small><i class="fas fa-phone text-muted"></i> <?php echo htmlspecialchars($s['number'] ?: 'N/A'); ?></small></div>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($s['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick='editStaff(<?php echo json_encode($s); ?>)'><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteRecord(<?= $s['id']; ?>)"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Staff Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="staff_id" id="edit_staff_id">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" id="edit_username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="number" id="edit_number" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="department" id="edit_department" class="form-control" required>
                            <option value="">Select department...</option>
                            <option value="HR">HR</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Country</label>
                        <select name="country" id="edit_country" class="form-control" required>
                            <option value="">Select Country...</option>
                            <option value="India">India</option>
                            <option value="Japan">Japan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password (Leave blank to keep current)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_staff" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editStaff(staff) {
    document.getElementById('edit_staff_id').value = staff.id;
    document.getElementById('edit_full_name').value = staff.full_name || '';
    document.getElementById('edit_username').value = staff.username || '';
    document.getElementById('edit_email').value = staff.email || '';
    document.getElementById('edit_number').value = staff.number || '';
    document.getElementById('edit_department').value = staff.department || '';
    document.getElementById('edit_country').value = staff.country || '';
    
    new bootstrap.Modal(document.getElementById('editStaffModal')).show();
}

function deleteRecord(id) {
    if (confirm("Are you sure you want to delete this employee?")) {
         window.location.href = "?delete_id=" + id;
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
