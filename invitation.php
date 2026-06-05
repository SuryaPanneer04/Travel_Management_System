<?php
session_start();
require_once 'config/db.php';
require_once 'config/mail_config.php';

$success = '';
$error = '';

$reqId = null;
$travelerData = null;
if (isset($_GET['req_id'])) {
    $reqId = base64_decode(urldecode($_GET['req_id']));
} elseif (isset($_POST['req_id'])) {
    $reqId = $_POST['req_id'];
}

if ($reqId) {
    $trStmt = $pdo->prepare("SELECT * FROM travellerrequest WHERE id = ?");
    $trStmt->execute([$reqId]);
    $travelerData = $trStmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($travelerData) {
        $name = $travelerData['fullname'];
        $email = $travelerData['email'];
        $age = $travelerData['age'];
        $passport = $travelerData['passport_number'];
        $passport_val = $travelerData['passport_validation'];
    } else {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $age = $_POST['age'] ?? '';
        $passport = $_POST['passport_number'] ?? '';
        $passport_val = $_POST['passport_validation'] ?? '';
    }
    
    $purpose = $_POST['purpose_of_travel'] ?? '';
    $visa_type = $_POST['visa_type'] ?? '';
    $travel_country = $_POST['travel_country'];
    $start_date = $_POST['travel_start_date'];
    $end_date = $_POST['travel_end_date'];
    
    // File uploads handling
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $passport_scan = '';
    if (isset($_POST['req_id']) && !empty($_POST['req_id'])) {
        $trStmt = $pdo->prepare("SELECT passport_scan FROM travellerrequest WHERE id = ?");
        $trStmt->execute([$_POST['req_id']]);
        $trData = $trStmt->fetch();
        if ($trData && !empty($trData['passport_scan'])) {
            $passport_scan = $trData['passport_scan'];
        }
    } else {
        if (isset($_FILES['passport_scan']) && $_FILES['passport_scan']['error'] === UPLOAD_ERR_OK) {
            $passport_scan = $upload_dir . time() . '_' . basename($_FILES['passport_scan']['name']);
            move_uploaded_file($_FILES['passport_scan']['tmp_name'], $passport_scan);
        }
    }
    
    $signature = '';
    if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
        $signature = $upload_dir . time() . '_' . basename($_FILES['signature']['name']);
        move_uploaded_file($_FILES['signature']['tmp_name'], $signature);
    }
    
    $visa_scan = '';
    if (isset($_FILES['visa_scan']) && $_FILES['visa_scan']['error'] === UPLOAD_ERR_OK) {
        $visa_scan = $upload_dir . time() . '_' . basename($_FILES['visa_scan']['name']);
        move_uploaded_file($_FILES['visa_scan']['tmp_name'], $visa_scan);
    }
    
    // Calculate stay days
    $dStart = new DateTime($start_date);
    $dEnd  = new DateTime($end_date);
    $dDiff = $dStart->diff($dEnd);
    $stay_days = $dDiff->days > 0 ? $dDiff->days : 1;
    
    try {
        // Fetch HR employee for the selected country
        $hrStmt = $pdo->prepare("SELECT id, email FROM users WHERE (department = 'HR' OR role = 'hr') AND country = ? LIMIT 1");
        $hrStmt->execute([$travel_country]);
        $hrUser = $hrStmt->fetch();
        
        $emp_id = $hrUser ? $hrUser['id'] : NULL;

        $stmt = $pdo->prepare("INSERT INTO tourist_entries 
            (name, email, age, passport_number, passport_validation, purpose_of_travel, visa_type, travel_country, travel_start_date, travel_end_date, stay_days, passport_scan, signature, visa_scan, employee_id, req_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
        $stmt->execute([$name, $email, $age, $passport, $passport_val, $purpose, $visa_type, $travel_country, $start_date, $end_date, $stay_days, $passport_scan, $signature, $visa_scan, $emp_id, $reqId]);
        $tId = $pdo->lastInsertId();

        // Handle Companions
        $companionCount = 0;
        $companionDetailsHtml = '';
        
        if (isset($_POST['companion_name']) && is_array($_POST['companion_name'])) {
            for ($i = 0; $i < count($_POST['companion_name']); $i++) {
                $cName = trim($_POST['companion_name'][$i]);
                $cAge = trim($_POST['companion_age'][$i]);
                $cPassport = trim($_POST['companion_passport_number'][$i]);
                $cPassportVal = trim($_POST['companion_passport_validation'][$i]);
                
                if (empty($cName)) continue; // Skip empty rows
                
                $companionCount++;
                $companionDetailsHtml .= "<li><strong>" . htmlspecialchars($cName) . "</strong> (Age: " . htmlspecialchars($cAge) . ", Passport: " . htmlspecialchars($cPassport) . ")</li>";
                
                $cPassportScan = '';
                if (isset($_FILES['companion_passport_scan']['name'][$i]) && $_FILES['companion_passport_scan']['error'][$i] === UPLOAD_ERR_OK) {
                    $cPassportScan = $upload_dir . time() . '_c' . $i . '_' . basename($_FILES['companion_passport_scan']['name'][$i]);
                    move_uploaded_file($_FILES['companion_passport_scan']['tmp_name'][$i], $cPassportScan);
                }
                
                $cSignature = '';
                if (isset($_FILES['companion_signature']['name'][$i]) && $_FILES['companion_signature']['error'][$i] === UPLOAD_ERR_OK) {
                    $cSignature = $upload_dir . time() . '_c' . $i . '_' . basename($_FILES['companion_signature']['name'][$i]);
                    move_uploaded_file($_FILES['companion_signature']['tmp_name'][$i], $cSignature);
                }
                
                $cVisaScan = '';
                if (isset($_FILES['companion_visa_scan']['name'][$i]) && $_FILES['companion_visa_scan']['error'][$i] === UPLOAD_ERR_OK) {
                    $cVisaScan = $upload_dir . time() . '_c' . $i . '_' . basename($_FILES['companion_visa_scan']['name'][$i]);
                    move_uploaded_file($_FILES['companion_visa_scan']['tmp_name'][$i], $cVisaScan);
                }
                
                $cStmt = $pdo->prepare("INSERT INTO tourist_companions (tourist_id, name, age, passport_number, passport_validation, passport_scan, signature, visa_scan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $cStmt->execute([$tId, $cName, $cAge, $cPassport, $cPassportVal, $cPassportScan, $cSignature, $cVisaScan]);
            }
        }

        $totalPersons = 1 + $companionCount;
        $companionStr = "";
        if ($companionCount > 0) {
            $companionStr = "<h4>Companions ($companionCount)</h4><ul>$companionDetailsHtml</ul>";
        }

        // Send confirmation email to the User
        $userSubject = "Travel Request Received - TMS";
        $userBody = "<h3>Dear $name,</h3>
                     <p>We have successfully received your travel request for a total of <strong>$totalPersons person(s)</strong>.</p>
                     $companionStr
                     <p>Our team will review your details and contact you shortly.</p>";
        sendMail($email, $userSubject, $userBody);

        // Notify the assigned HR Employee
        if ($emp_id && !empty($hrUser['email'])) {
            $employeeEmail = $hrUser['email'];
            $empSubject = "New Travel Request from $name ($totalPersons Persons)";
            $empBody = "<h3>New Request Details</h3>
                        <p><strong>Primary Traveler:</strong> $name</p>
                        <p><strong>Email:</strong> $email</p>
                        <p><strong>Age:</strong> $age</p>
                        <p><strong>Passport Number:</strong> $passport</p>
                        <p><strong>Visa Type:</strong> $visa_type</p>
                        <p><strong>Travel Dates:</strong> $start_date to $end_date</p>
                        <p><strong>Total Persons:</strong> $totalPersons</p>
                        $companionStr
                        <p>Please log in to the employee panel to view full details and documents.</p>";
            sendMail($employeeEmail, $empSubject, $empBody);
        }

        $success = 'Your travel request has been submitted successfully! Our team will contact you shortly with your trip schedule.';
    } catch (PDOException $e) {
        $error = 'Error submitting request: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Travel Request - Travel Management System</title>
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
            <h1 class="display-5 fw-bold mb-3"><i class="fas fa-plane-departure me-3"></i>Travel Management System</h1>
            <!-- <p class="lead">Fill out the details below and our team will prepare a custom itinerary for you.</p> -->
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
                            <?php if ($reqId): ?>
                                <input type="hidden" name="req_id" value="<?php echo htmlspecialchars($reqId); ?>">
                            <?php endif; ?>


                            <h4 class="fw-bold mb-4 text-dark border-bottom pb-2 mt-5">Trip Details</h4>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">Period of Travel (Start) <span class="text-danger">*</span></label>
                                    <input type="date" name="travel_start_date" class="form-control" required>
                                    <div class="helper-text"><i class="fas fa-info-circle"></i> Your expected departure date.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Period of Travel (End) <span class="text-danger">*</span></label>
                                    <input type="date" name="travel_end_date" class="form-control" required>
                                    <div class="helper-text"><i class="fas fa-info-circle"></i> Your expected return date.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Purpose of Travel <span class="text-danger">*</span></label>
                                    <select name="purpose_of_travel" class="form-control" required>
                                        <option value="">Select Purpose...</option>
                                        <option value="Tourism">Tourism / Leisure</option>
                                        <option value="Business">Business</option>
                                        <option value="Medical">Medical</option>
                                        <option value="Family">Visiting Family</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <div class="helper-text"><i class="fas fa-info-circle"></i> Select the main reason for your travel.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Visa Type <span class="text-danger">*</span></label>
                                    <select name="visa_type" class="form-control" required>
                                        <option value="">Select Visa Type...</option>
                                        <option value="Business Visa">Business Visa</option>
                                        <option value="Multiple Entry Visa">Multiple Entry Visa</option>
                                        <option value="Employment Visa">Employment Visa</option>
                                    </select>
                                    <div class="helper-text"><i class="fas fa-info-circle"></i> Select the type of visa you are applying for.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Travel to Country <span class="text-danger">*</span></label>
                                    <select name="travel_country" class="form-control" required>
                                        <option value="">Select Country...</option>
                                        <option value="India" <?php echo ($travelerData && $travelerData['travel_country'] === 'India') ? 'selected' : ''; ?>>India</option>
                                        <option value="Japan" <?php echo ($travelerData && $travelerData['travel_country'] === 'Japan') ? 'selected' : ''; ?>>Japan</option>
                                    </select>
                                    <div class="helper-text"><i class="fas fa-info-circle"></i> Select your destination country.</div>
                                </div>

                            </div>

                            <h4 class="fw-bold mb-4 text-dark border-bottom pb-2 mt-5">Upload Documents</h4>
                            <div class="row g-4">

                                <div class="col-md-4">
                                    <label class="form-label">Signature <span class="text-danger">*</span></label>
                                    <input type="file" name="signature" class="form-control" required accept="image/*">
                                    <div class="helper-text"><i class="fas fa-info-circle"></i> Upload a clear image of your signature on a white background.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Visa Scan</label>
                                    <input type="file" name="visa_scan" class="form-control" accept="image/*,.pdf,.doc,.docx">
                                    <div class="helper-text"><i class="fas fa-info-circle"></i> Upload any existing visas or supporting documents if applicable.</div>
                                </div>
                            </div>
                            
                            <!-- Companions Section -->
                            <div class="mt-4 border-top pt-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="fw-bold text-dark m-0">Family / Companions</h4>
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm" onclick="addCompanion()">
                                        <i class="fas fa-plus me-1"></i> Add Person
                                    </button>
                                </div>
                                <div class="helper-text mb-4"><i class="fas fa-info-circle"></i> If you are traveling with family members, add them here.</div>
                                
                                <div id="companions-container"></div>
                            </div>
                            
                            <div class="mt-5">
                                <button type="submit" class="btn btn-submit w-100 shadow-sm"><i class="fas fa-paper-plane me-2"></i> Submit Request</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<script>
    let companionIndex = 0;
    function addCompanion() {
        companionIndex++;
        const container = document.getElementById('companions-container');
        
        const html = `
            <div class="card border border-primary-subtle shadow-sm mb-4" id="companion-${companionIndex}">
                <div class="card-header bg-primary-subtle text-primary d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="fas fa-user-friends me-2"></i>Companion ${companionIndex}</span>
                    <button type="button" class="btn btn-sm btn-danger rounded-pill px-3" onclick="removeCompanion(${companionIndex})"><i class="fas fa-times me-1"></i> Remove</button>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="companion_name[]" class="form-control form-control-sm" required placeholder="Full Name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Age <span class="text-danger">*</span></label>
                            <input type="number" name="companion_age[]" class="form-control form-control-sm" required min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Passport Number <span class="text-danger">*</span></label>
                            <input type="text" name="companion_passport_number[]" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Passport Validation <span class="text-danger">*</span></label>
                            <input type="date" name="companion_passport_validation[]" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Passport Scan <span class="text-danger">*</span></label>
                            <input type="file" name="companion_passport_scan[]" class="form-control form-control-sm" required accept="image/*,.pdf">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Signature <span class="text-danger">*</span></label>
                            <input type="file" name="companion_signature[]" class="form-control form-control-sm" required accept="image/*">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Visa Scan</label>
                            <input type="file" name="companion_visa_scan[]" class="form-control form-control-sm" accept="image/*,.pdf,.doc,.docx">
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', html);
    }
    
    function removeCompanion(id) {
        document.getElementById('companion-' + id).remove();
    }
</script>
</body>
</html>
