<?php
session_start();
require_once 'config/db.php';
require_once 'config/mail_config.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $age = $_POST['age'];
    $passport = $_POST['passport_number'];
    $passport_val = $_POST['passport_validation'];
    $address = $_POST['address'];
    $travel_country = $_POST['travel_country'];
    // $traveler_details = $_POST['traveler_details'];
    
    // File uploads handling
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $passport_scan = '';
    if (isset($_FILES['passport_scan']) && $_FILES['passport_scan']['error'] === UPLOAD_ERR_OK) {
        $passport_scan = $upload_dir . time() . '_ps_' . basename($_FILES['passport_scan']['name']);
        move_uploaded_file($_FILES['passport_scan']['tmp_name'], $passport_scan);
    }
    
    $invitation_doc = '';
    if (isset($_FILES['invitation_doc']) && $_FILES['invitation_doc']['error'] === UPLOAD_ERR_OK) {
        $invitation_doc = $upload_dir . time() . '_id_' . basename($_FILES['invitation_doc']['name']);
        move_uploaded_file($_FILES['invitation_doc']['tmp_name'], $invitation_doc);
    }
    
    try {
        // Fetch HR employee for the selected country
        // $hrStmt = $pdo->prepare("SELECT id, email FROM users WHERE (department = 'HR' OR role = 'hr' OR username = 'japanHR') AND country = ? LIMIT 1");
        // $hrStmt->execute([$travel_country]);
        // $hrUser = $hrStmt->fetch();
        // Fetch HR employee for the selected country
        $hrStmt = $pdo->prepare("SELECT id, email FROM users WHERE (department = 'HR' OR role = 'hr') AND country = ? LIMIT 1");
        $hrStmt->execute([$travel_country]);
        $hrUser = $hrStmt->fetch();
        
        $emp_id = $hrUser ? $hrUser['id'] : NULL;

        $stmt = $pdo->prepare("INSERT INTO travellerrequest 
            (fullname, email, age, passport_number, passport_validation, address, travel_country, passport_scan, invitation_doc, employee_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
        $stmt->execute([$fullname, $email, $age, $passport, $passport_val, $address, $travel_country, $passport_scan, $invitation_doc, $emp_id]);
        $tId = $pdo->lastInsertId();

        // Send confirmation email to the User
        $userSubject = "Invitation Request - Travel Management System";
        $userBody = "<h3>Dear $fullname,</h3>
                     <p>invitation request send sucessfully.</p>
                     <p>Our team will review your details and contact you shortly.</p>";
        sendMail($email, $userSubject, $userBody);

        // Notify the assigned HR Employee
        if ($emp_id && !empty($hrUser['email'])) {
            $employeeEmail = $hrUser['email'];
            $empSubject = "New Invitation Request from $fullname";
            
            // Generate full URLs for attachments
            $baseUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/";
            $passportUrl = $passport_scan ? $baseUrl . $passport_scan : 'Not Provided';
            $invitationUrl = $invitation_doc ? $baseUrl . $invitation_doc : 'Not Provided';
            
            $empBody = "<h3>New Invitation Request Details</h3>
                        <p><strong>Full Name:</strong> $fullname</p>
                        <p><strong>Email:</strong> $email</p>
                        <p><strong>Age:</strong> $age</p>
                        <p><strong>Passport Number:</strong> $passport</p>
                        <p><strong>Passport Validation:</strong> $passport_val</p>
                        <p><strong>Address:</strong> $address</p>
                        <p><strong>Travel Country:</strong> $travel_country</p>
                        <h4>Documents Uploaded:</h4>
                        <ul>
                            <li><strong>Passport Scan:</strong> <a href='$passportUrl'>View/Download</a></li>
                            <li><strong>Invitation Document:</strong> <a href='$invitationUrl'>View/Download</a></li>
                        </ul>
                        <p>Please review this request.</p>";
            sendMail($employeeEmail, $empSubject, $empBody);
        }

        $success = 'Your invitation request has been submitted successfully! Our team will contact you shortly.';
    } catch (PDOException $e) {
        $error = 'Error submitting request: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invitation Request - Travel Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .hero-section {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
        }
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            padding: 40px;
            margin-top: -40px;
            margin-bottom: 40px;
        }
        .form-label { font-weight: 600; color: #475569; font-size: 0.9rem; }
        .form-control { border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 15px; }
        .form-control:focus { box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); border-color: #3b82f6; }
        .btn-submit { background: #3b82f6; color: white; font-weight: 600; padding: 12px; border-radius: 8px; border: none; transition: 0.3s; }
        .btn-submit:hover { background: #2563eb; }
        .helper-text {
            font-size: 0.8rem;
            color: #0c4a6e;
            background: #f0f9ff;
            margin-top: 8px;
            padding: 8px 12px;
            border-radius: 6px;
            border-left: 4px solid #0ea5e9;
            display: flex;
            align-items: center;
        }
        .helper-text i { margin-right: 8px; color: #0ea5e9; font-size: 0.9rem; }
    </style>
</head>
<body>

    <div class="hero-section">
        <div class="container">
            <h1 class="display-5 fw-bold mb-3"><i class="fas fa-envelope-open-text me-3"></i>Travel Management System</h1>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="form-card">
                    <?php if ($success): ?>
                        <div class="text-center py-5">
                            <div class="display-1 text-success mb-4"><i class="fas fa-check-circle"></i></div>
                            <h3 class="fw-bold text-dark">Success!</h3>
                            <p class="text-muted"><?php echo $success; ?></p>
                        </div>
                    <?php else: ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <h4 class="fw-bold mb-4 text-dark border-bottom pb-2">Traveler Invitation Request</h4>
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="fullname" class="form-control" required placeholder="John Doe">
                                    <div class="helper-text"><i class="fas fa-info-circle"></i> Enter your full name exactly as it appears on your passport.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" required placeholder="john@example.com">
                                    <div class="helper-text"><i class="fas fa-info-circle"></i> We will send updates to this email.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Age <span class="text-danger">*</span></label>
                                    <input type="number" name="age" class="form-control" required min="1">
                                    <div class="helper-text"><i class="fas fa-info-circle"></i> Enter your current age.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Passport Number <span class="text-danger">*</span></label>
                                    <input type="text" name="passport_number" class="form-control" required>
                                    <div class="helper-text"><i class="fas fa-info-circle"></i> Enter your valid passport number.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Passport Validation <span class="text-danger">*</span></label>
                                    <input type="date" name="passport_validation" class="form-control" required>
                                    <div class="helper-text"><i class="fas fa-info-circle"></i> Enter your passport expiry date.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Address <span class="text-danger">*</span></label>
                                    <textarea name="address" class="form-control" rows="2" required></textarea>
                                    <div class="helper-text"><i class="fas fa-info-circle"></i> Enter your current residential address.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Travel to Country <span class="text-danger">*</span></label>
                                    <select name="travel_country" class="form-control" required>
                                        <option value="">Select Country...</option>
                                        <option value="India">India</option>
                                        <option value="Japan">Japan</option>
                                    </select>
                                    <div class="helper-text"><i class="fas fa-info-circle"></i> Select the country you plan to travel to.</div>
                                </div>
                                <!-- <div class="col-md-6">
                                    <label class="form-label">Traveler Details <span class="text-danger">*</span></label>
                                    <textarea name="traveler_details" class="form-control" rows="2" required></textarea>
                                </div> -->
                            </div>

                            <h4 class="fw-bold mb-4 text-dark border-bottom pb-2 mt-5">Upload Documents</h4>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">Passport Scan <span class="text-danger">*</span></label>
                                    <input type="file" name="passport_scan" class="form-control" required accept="image/*,.pdf">
                                    <div class="helper-text"><i class="fas fa-info-circle"></i> Upload a clear, scanned copy of your passport (PDF or Image).</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Invitation Document <span class="text-danger">*</span></label>
                                    <input type="file" name="invitation_doc" class="form-control" required accept="image/*,.pdf,.doc,.docx">
                                    <div class="helper-text"><i class="fas fa-info-circle"></i> Upload your invitation document.</div>
                                </div>
                            </div>
                            
                            <div class="mt-5">
                                <button type="submit" class="btn btn-submit w-100 shadow-sm"><i class="fas fa-paper-plane me-2"></i> Invitation Request</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
