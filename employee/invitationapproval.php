<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../config/mail_config.php';
requireRole('employee');

$pageTitle = 'Invitation Approvals';
$empId = $_SESSION['user_id'];

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $requestId = $_POST['request_id'];
    
    // File upload for accepted file
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $accepted_file = '';
    if (isset($_FILES['accepted_file']) && $_FILES['accepted_file']['error'] === UPLOAD_ERR_OK) {
        $fileName = time() . '_accepted_' . basename($_FILES['accepted_file']['name']);
        $filePath = $upload_dir . $fileName;
        $dbFilePath = 'uploads/' . $fileName;
        
        if (move_uploaded_file($_FILES['accepted_file']['tmp_name'], $filePath)) {
            $accepted_file = $dbFilePath;
        }
    }
    
    if ($accepted_file) {
        try {
            // Update DB
            $stmt = $pdo->prepare("UPDATE travellerrequest SET status = 'Completed', accepted_file = ? WHERE id = ? AND employee_id = ?");
            $stmt->execute([$accepted_file, $requestId, $empId]);
            
            // Get user email
            $uStmt = $pdo->prepare("SELECT email, fullname FROM travellerrequest WHERE id = ?");
            $uStmt->execute([$requestId]);
            $userReq = $uStmt->fetch();
            
            if ($userReq) {
                $userEmail = $userReq['email'];
                $userFullName = $userReq['fullname'];
                
                // Generate link to accepted file
                $baseUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['PHP_SELF'])) . "/";
                $fileUrl = $baseUrl . $accepted_file;
                $scheduleLink = "http://" . $_SERVER['HTTP_HOST'] . "/tourist/invitation.php?req_id=" . urlencode(base64_encode($requestId));
                
                $userSubject = "Invitation Accepted - Travel Management System";
                $userBody = "<h3>Dear $userFullName,</h3>
                             <p>Your invitation request has been <strong>accepted</strong>.</p>
                             <p>You can view/download your accepted invitation document by clicking the link below:</p>
                             <p><a href='$fileUrl' style='background:#3b82f6;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>View Invitation Document</a></p>
                             <br>
                             <p>Please proceed to complete your travel request using the link below:</p>
                             <p><a href='$scheduleLink' style='background:#10b981;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;margin-top:10px;'>Complete Travel Request</a></p>
                             <br>
                             <p>If the buttons don't work, copy and paste these links in your browser:<br>Document: $fileUrl<br>Invitation Page: $scheduleLink</p>
                             <p>Regards,<br>Team TMS</p>";
                             
                sendMail($userEmail, $userSubject, $userBody);
            }
            $success = "Invitation request accepted successfully.";
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = "Please upload the accepted document.";
    }
}

// Fetch requests
$stmt = $pdo->prepare("SELECT * FROM travellerrequest WHERE employee_id = ? ORDER BY created_at DESC");
$stmt->execute([$empId]);
$requests = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i> <?php echo $success; ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card p-4 border-0 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold m-0"><i class="fas fa-envelope-open-text text-primary me-2"></i>Invitation Requests</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase fw-bold text-muted">Traveler Details</th>
                    <th class="small text-uppercase fw-bold text-muted">Country</th>
                    <th class="small text-uppercase fw-bold text-muted">Status</th>
                    <th class="small text-uppercase fw-bold text-muted text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $row): ?>
                <tr>
                    <td>
                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['fullname']); ?></div>
                        <div class="small text-muted"><?php echo htmlspecialchars($row['email']); ?></div>
                    </td>
                    <td>
                        <span class="badge bg-secondary-subtle text-secondary border"><?php echo htmlspecialchars($row['travel_country']); ?></span>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'Completed'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill shadow-sm"><i class="fas fa-check-circle me-1"></i> Completed</span>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill shadow-sm"><i class="fas fa-clock me-1"></i> Pending</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-light text-primary rounded-circle shadow-sm" style="width: 35px; height: 35px; line-height: 22px;" title="View Details" data-bs-toggle="modal" data-bs-target="#reqModal<?php echo $row['id']; ?>">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($requests)): ?>
                <tr>
                    <td colspan="4" class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                        <h5>No invitation requests yet.</h5>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modals -->
<?php foreach ($requests as $row): ?>
<div class="modal fade text-start" id="reqModal<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 bg-transparent">
            <div class="modal-body p-0">
                <div class="bg-white d-flex flex-column flex-md-row" style="border-radius: 16px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
                    <div class="position-relative bg-white" style="flex: 3;">
                        <div class="d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white; padding: 20px 30px;">
                            <h4 class="m-0 fw-bold"><i class="fas fa-id-card me-2"></i> Traveler Details</h4>
                            <span class="badge bg-white text-primary border-0 shadow-sm fs-6 px-3 py-2 rounded-pill">ID: <?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="p-4">
                            <div class="row g-4">
                                <div class="col-sm-6">
                                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Full Name</div>
                                    <div style="font-size: 1.25rem; color: #0f172a; font-weight: 700;"><?php echo htmlspecialchars($row['fullname']); ?></div>
                                </div>
                                <div class="col-sm-6">
                                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Email</div>
                                    <div style="font-size: 1.1rem; color: #0f172a; font-weight: 600;"><?php echo htmlspecialchars($row['email']); ?></div>
                                </div>
                                <div class="col-sm-4">
                                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Age</div>
                                    <div style="font-size: 1.1rem; color: #0f172a; font-weight: 600;"><?php echo htmlspecialchars($row['age']); ?> Yrs</div>
                                </div>
                                <div class="col-sm-4">
                                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Passport No.</div>
                                    <div style="font-size: 1.1rem; color: #0f172a; font-weight: 600;"><?php echo htmlspecialchars($row['passport_number']); ?></div>
                                </div>
                                <div class="col-sm-4">
                                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Passport Val.</div>
                                    <div style="font-size: 1.1rem; color: #0f172a; font-weight: 600;"><?php echo date('d M Y', strtotime($row['passport_validation'])); ?></div>
                                </div>
                                <div class="col-sm-12">
                                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Address</div>
                                    <div style="font-size: 1.1rem; color: #0f172a; font-weight: 600;"><?php echo nl2br(htmlspecialchars($row['address'])); ?></div>
                                </div>

                                
                                <div class="col-sm-12 mt-3 border-top pt-3">
                                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; margin-bottom: 10px;">Uploaded Documents</div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <?php if (!empty($row['passport_scan'])): ?>
                                            <a href="../<?php echo htmlspecialchars($row['passport_scan']); ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm"><i class="fas fa-passport me-1"></i> Passport Scan</a>
                                        <?php endif; ?>
                                        <?php if (!empty($row['invitation_doc'])): ?>
                                            <a href="../<?php echo htmlspecialchars($row['invitation_doc']); ?>" target="_blank" class="btn btn-sm btn-outline-info rounded-pill px-3 shadow-sm"><i class="fas fa-file-alt me-1"></i> Invitation Doc</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($row['status'] === 'Completed' && !empty($row['accepted_file'])): ?>
                                <div class="col-sm-12 mt-3 border-top pt-3">
                                    <div style="font-size: 0.75rem; color: #10b981; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; margin-bottom: 10px;">Sent Invitation Document</div>
                                    <a href="../<?php echo htmlspecialchars($row['accepted_file']); ?>" target="_blank" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm"><i class="fas fa-check-circle me-1"></i> View Accepted File</a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="bg-light d-flex flex-column justify-content-center align-items-center p-4" style="flex: 1; border-left: 2px dashed #cbd5e1; position: relative;">
                        <div class="d-none d-md-block" style="position: absolute; top: -15px; left: -15px; width: 30px; height: 30px; background: rgba(0,0,0,0.5); border-radius: 50%;"></div>
                        <div class="d-none d-md-block" style="position: absolute; bottom: -15px; left: -15px; width: 30px; height: 30px; background: rgba(0,0,0,0.5); border-radius: 50%;"></div>
                        
                        <div class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.85rem; letter-spacing: 3px;">Action</div>
                        
                        <?php if ($row['status'] === 'Pending'): ?>
                            <form method="POST" action="" enctype="multipart/form-data" class="w-100 text-center">
                                <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-dark">Upload Invitation Doc</label>
                                    <input type="file" name="accepted_file" class="form-control form-control-sm" required accept=".pdf,.doc,.docx,image/*">
                                </div>
                                <button type="submit" class="btn btn-primary w-100 rounded-pill shadow-sm"><i class="fas fa-upload me-1"></i> Accept</button>
                            </form>
                        <?php else: ?>
                            <div class="text-success text-center">
                                <i class="fas fa-check-circle fa-3x mb-2"></i>
                                <div class="fw-bold fs-5">Accepted</div>
                            </div>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-4 w-100 rounded-pill" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php require_once '../includes/footer.php'; ?>
