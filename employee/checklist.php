<?php
require_once '../config/db.php';
require_once '../config/mail_config.php';
require_once '../includes/auth.php';
requireRole('employee');

$pageTitle = 'Trip Planner';
$success = '';
$error = '';
$touristId = $_GET['id'] ?? null;

if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $error = "You are not authorized to view or manage that travel request.";
}

// Handle Manual Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $tId = $_POST['tourist_id'];
    $newStatus = $_POST['status'];
    try {
        $pdo->prepare("UPDATE tourist_entries SET status = ? WHERE id = ?")->execute([$newStatus, $tId]);
        $success = "Status updated to $newStatus.";
    } catch (PDOException $e) {
        $error = 'Error updating status: ' . $e->getMessage();
    }
}

// Handle Arrangement Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_arrangements'])) {
    $tId = $_POST['tourist_id'];
    $cabDetails = $_POST['cab_details'];
    $driver = $_POST['driver_name'];
    $contact = $_POST['driver_contact'];
    $pickup = $_POST['pickup_time'];
    $hotelName = $_POST['hotel_name'];
    $hotelLocation = $_POST['hotel_location'] ?? '';
    $hotelContact = $_POST['hotel_contact'] ?? '';
    $hotelEmail = $_POST['hotel_email'] ?? '';
    $checkIn = $_POST['check_in'];
    $checkOut = $_POST['check_out'];
    $room = $_POST['room_no'];
    
    // Flight configuration fields are now handled in invitation.php
    
    // File upload handler
    $uploadDir = '../uploads/documents/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $uploadedFiles = [];
    $fileFields = ['hotel_voucher', 'cab_voucher'];
    $dateStr = date('Ymd');

    foreach ($fileFields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES[$field]['tmp_name'];
            $cleanName = preg_replace('/[^A-Za-z0-9.\-]/', '_', basename($_FILES[$field]['name']));
            $newName = $tId . '_' . $dateStr . '_' . $field . '_' . $cleanName;
            if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
                $uploadedFiles[$field] = 'uploads/documents/' . $newName;
            }
        }
    }

    try {
        $check = $pdo->prepare("SELECT * FROM arrangements WHERE tourist_id = ?");
        $check->execute([$tId]);
        $existing = $check->fetch();

        $hv = $uploadedFiles['hotel_voucher'] ?? ($existing['hotel_voucher'] ?? null);
        $cv = $uploadedFiles['cab_voucher'] ?? ($existing['cab_voucher'] ?? null);

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE arrangements SET cab_details = ?, driver_name = ?, driver_contact = ?, pickup_time = ?, hotel_name = ?, hotel_location = ?, hotel_contact = ?, hotel_email = ?, check_in = ?, check_out = ?, room_no = ?, hotel_voucher = ?, cab_voucher = ?, assigned_by = ? WHERE tourist_id = ?");
            $stmt->execute([$cabDetails, $driver, $contact, $pickup, $hotelName, $hotelLocation, $hotelContact, $hotelEmail, $checkIn, $checkOut, $room, $hv, $cv, $_SESSION['user_id'], $tId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO arrangements (tourist_id, cab_details, driver_name, driver_contact, pickup_time, hotel_name, hotel_location, hotel_contact, hotel_email, check_in, check_out, room_no, hotel_voucher, cab_voucher, assigned_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tId, $cabDetails, $driver, $contact, $pickup, $hotelName, $hotelLocation, $hotelContact, $hotelEmail, $checkIn, $checkOut, $room, $hv, $cv, $_SESSION['user_id']]);
        }
        // Just update employee ID and status
        $pdo->prepare("UPDATE tourist_entries SET employee_id = ?, status = IF(status = 'Pending', 'Processing', status) WHERE id = ?")->execute([$_SESSION['user_id'], $tId]);
        header("Location: checklist.php?id=$tId&success=arrangements_saved#headingSetup");
        exit();
    } catch (PDOException $e) {
        $error = 'Error saving arrangements: ' . $e->getMessage();
    }
}

// Handle Itinerary Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_itinerary'])) {
    $tId = $_POST['tourist_id'];
    $day = $_POST['day_number'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $place = $_POST['place_name'];
    $activity = $_POST['activity'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO itineraries (tourist_id, day_number, start_time, end_time, place_name, activity, assigned_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tId, $day, $start, $end, $place, $activity, $_SESSION['user_id']]);
        $pdo->prepare("UPDATE tourist_entries SET status = IF(status = 'Pending', 'Processing', status) WHERE id = ?")->execute([$tId]);
        $success = 'Itinerary item added!';
    } catch (PDOException $e) {
        $error = 'Error adding itinerary: ' . $e->getMessage();
    }
}

// Handle Itinerary Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_itinerary') {
    $itid = $_GET['itid'];
    $tId = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM itineraries WHERE id = ?");
    $stmt->execute([$itid]);
    header("Location: checklist.php?id=$tId&success=itinerary_deleted");
    exit();
}

// Handle Food Plan Saving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_food_plan'])) {
    $tId = $_POST['tourist_id'];
    $day = $_POST['food_day_number'];
    $breakfast = $_POST['breakfast'];
    $lunch = $_POST['lunch'];
    $dinner = $_POST['dinner'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO food_arrangements (tourist_id, day_number, breakfast, lunch, dinner) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE breakfast = ?, lunch = ?, dinner = ?");
        $stmt->execute([$tId, $day, $breakfast, $lunch, $dinner, $breakfast, $lunch, $dinner]);
        $pdo->prepare("UPDATE tourist_entries SET status = IF(status = 'Pending', 'Processing', status) WHERE id = ?")->execute([$tId]);
        $success = "Food plan for Day $day saved successfully!";
    } catch (PDOException $e) {
        $error = 'Error saving food plan: ' . $e->getMessage();
    }
}

// Handle Food Plan Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_food') {
    $foodId = $_GET['food_id'];
    $tId = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM food_arrangements WHERE id = ?");
    $stmt->execute([$foodId]);
    header("Location: checklist.php?id=$tId&success=food_deleted");
    exit();
}

// Handle Trip Finalization
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalize_trip'])) {
    $tId = $_POST['tourist_id'];
    try {
        $tCheck = $pdo->prepare("SELECT email, name, token FROM tourist_entries WHERE id = ?");
        $tCheck->execute([$tId]);
        $touristInfo = $tCheck->fetch();
        
        $token = $touristInfo['token'];
        if (empty($token)) {
            $token = bin2hex(random_bytes(16));
            $pdo->prepare("UPDATE tourist_entries SET token = ? WHERE id = ?")->execute([$token, $tId]);
        }
        
        $scheduleLink = "http://" . $_SERVER['HTTP_HOST'] . "/tourist/user/view_schedule.php?token=" . $token;
        $subject = "Your Trip Schedule is Ready - Confirmed ✈️";
        
        $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;'>
            <div style='background-color: #10b981; padding: 20px; text-align: center; color: white;'>
                <h2 style='margin: 0;'>Your Trip is Confirmed!</h2>
            </div>
            <div style='padding: 30px; background-color: #ffffff; color: #334155;'>
                <p style='font-size: 16px;'>Hello <strong>{$touristInfo['name']}</strong>,</p>
                <p style='font-size: 16px; line-height: 1.5;'>Great news! Your travel arrangements, including your flights, accommodation, and daily itinerary, have been professionally curated and finalized. You can also view and download your travel documents and vouchers directly from your portal.</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$scheduleLink}' style='background-color: #10b981; color: white; padding: 12px 25px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block;'>View Full Itinerary</a>
                </div>
                <p style='font-size: 14px; color: #64748b;'>If the button doesn't work, copy and paste this link into your browser:</p>
                <p style='font-size: 12px; word-break: break-all; color: #3b82f6;'>{$scheduleLink}</p>
            </div>
            <div style='background-color: #f8fafc; padding: 15px; text-align: center; font-size: 12px; color: #94a3b8;'>
                <p style='margin: 0;'>Have a safe and wonderful journey!</p>
            </div>
        </div>";
        
        try {
            $templateStmt = $pdo->prepare("SELECT subject, body FROM mail_templates WHERE template_key = 'schedule_ready' AND status = 'active'");
            $templateStmt->execute();
            $template = $templateStmt->fetch();
            if ($template) {
                $subject = $template['subject'];
                $body = str_replace(['{{name}}', '{{schedule_link}}'], [$touristInfo['name'], $scheduleLink], $template['body']);
            }
        } catch (Exception $e) {}
        
        sendMail($touristInfo['email'], $subject, $body);
        $pdo->prepare("UPDATE tourist_entries SET status = 'Completed' WHERE id = ?")->execute([$tId]);
        // $success = 'Trip finalized and email sent to traveler successfully!';
        header("Location: checklist.php?success=trip_finalized");
        exit();
    } catch (PDOException $e) {
        $error = 'Error finalizing trip: ' . $e->getMessage();
    }
}

// Fetch Master Data (Commented out as user requested to type manually)
// $cabsMaster = $pdo->query("SELECT c.*, l.location_name FROM cabs c LEFT JOIN locations l ON c.location = l.id WHERE c.status = 'available' ORDER BY c.provider_name ASC")->fetchAll();
// $hotelsMaster = $pdo->query("SELECT h.*, l.location_name FROM hotels h LEFT JOIN locations l ON h.location = l.id WHERE h.status = 'active' ORDER BY h.hotel_name ASC")->fetchAll();

// Handle filtering
$statusFilter = $_GET['filter'] ?? 'All';
$query = "SELECT * FROM tourist_entries WHERE employee_id = ?";
$params = [$_SESSION['user_id']];

if ($statusFilter !== 'All') {
    $query .= " AND status = ?";
    $params[] = $statusFilter;
}
$query .= " ORDER BY id DESC";

$tourists = $pdo->prepare($query);
$tourists->execute($params);
$touristList = $tourists->fetchAll();

$touristData = null;
$arrangementData = null;
$itineraryData = [];
$foodArrangements = [];

if ($touristId) {
    // Auto-assign employee if unassigned
    $pdo->prepare("UPDATE tourist_entries SET employee_id = ? WHERE id = ? AND employee_id IS NULL")->execute([$_SESSION['user_id'], $touristId]);

    $stmt = $pdo->prepare("SELECT * FROM tourist_entries WHERE id = ? AND employee_id = ?");
    $stmt->execute([$touristId, $_SESSION['user_id']]);
    $touristData = $stmt->fetch();

    if (!$touristData) {
        header("Location: checklist.php?error=unauthorized");
        exit;
    }

    $companionsStmt = $pdo->prepare("SELECT * FROM tourist_companions WHERE tourist_id = ?");
    $companionsStmt->execute([$touristId]);
    $companionsData = $companionsStmt->fetchAll();


    $stmt = $pdo->prepare("SELECT * FROM arrangements WHERE tourist_id = ?");
    $stmt->execute([$touristId]);
    $arrangementData = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT * FROM itineraries WHERE tourist_id = ? ORDER BY day_number ASC, start_time ASC");
    $stmt->execute([$touristId]);
    $itineraryData = $stmt->fetchAll();

    // Fetch day-wise food arrangements
    $stmt = $pdo->prepare("SELECT * FROM food_arrangements WHERE tourist_id = ? ORDER BY day_number ASC");
    $stmt->execute([$touristId]);
    $foodArrangements = $stmt->fetchAll();
    
    $invitationRequestData = null;
    if (!empty($touristData['req_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM travellerrequest WHERE id = ?");
        $stmt->execute([$touristData['req_id']]);
        $invitationRequestData = $stmt->fetch();
    }
}

if (isset($_GET['success'])) {
    if($_GET['success'] == 'itinerary_deleted') $success = 'Itinerary item removed.';
    if($_GET['success'] == 'food_deleted') $success = 'Food plan item removed.';
    if($_GET['success'] == 'arrangements_saved') $success = 'Arrangements saved successfully as draft.';
    if($_GET['success'] == 'trip_finalized') $success = 'Trip finalized and email sent to traveler successfully!';
}

require_once '../includes/header.php';
?>

<?php if (!$touristId): ?>
    <div class="col-12">
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <div class="card border-0 shadow-sm p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold text-primary m-0"><i class="fas fa-tasks me-2"></i>My Assigned Travel Requests</h5>
                <div class="btn-group shadow-sm">
                    <a href="checklist.php?filter=All" class="btn btn-sm <?php echo $statusFilter === 'All' ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
                    <a href="checklist.php?filter=Pending" class="btn btn-sm <?php echo $statusFilter === 'Pending' ? 'btn-primary' : 'btn-outline-primary'; ?>">Pending</a>
                    <a href="checklist.php?filter=Processing" class="btn btn-sm <?php echo $statusFilter === 'Processing' ? 'btn-primary' : 'btn-outline-primary'; ?>">Processing</a>
                    <a href="checklist.php?filter=Completed" class="btn btn-sm <?php echo $statusFilter === 'Completed' ? 'btn-primary' : 'btn-outline-primary'; ?>">Completed</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Dates</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($touristList as $t): ?>
                        <tr>
                            <td class="fw-bold"><?php echo htmlspecialchars($t['name']); ?></td>
                            <td><?php echo htmlspecialchars($t['email']); ?></td>
                            <td>
                                <?php if ($t['travel_start_date']): ?>
                                    <?php echo date('d M', strtotime($t['travel_start_date'])); ?> - <?php echo date('d M Y', strtotime($t['travel_end_date'])); ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    $badgeClass = 'bg-secondary';
                                    $iconClass = 'fa-circle';
                                    if ($t['status'] === 'Pending') { $badgeClass = 'bg-warning-subtle text-warning border border-warning-subtle'; $iconClass = 'fa-clock'; }
                                    if ($t['status'] === 'Processing') { $badgeClass = 'bg-primary-subtle text-primary border border-primary-subtle'; $iconClass = 'fa-spinner fa-spin'; }
                                    if ($t['status'] === 'Completed') { $badgeClass = 'bg-success-subtle text-success border border-success-subtle'; $iconClass = 'fa-check-circle'; }
                                ?>
                                <span class="badge <?php echo $badgeClass; ?> px-3 py-2 rounded-pill shadow-sm"><i class="fas <?php echo $iconClass; ?> me-1"></i> <?php echo $t['status']; ?></span>
                            </td>
                            <td>
                                <a href="?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-primary">Plan Trip <i class="fas fa-arrow-right ms-1"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($touristList)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No requests found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
<div class="row">
    <div class="col-lg-4">
        <!-- Tourist Selector -->
        <div class="card border-0 shadow-sm p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-primary m-0"><i class="fas fa-user-circle me-2"></i>Traveler Details</h5>
                <a href="checklist.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
            </div>
            
            <?php if ($touristData): ?>
                <div class="alert alert-info py-2 px-3 mb-3">
                    <h6 class="fw-bold m-0"><?php echo htmlspecialchars($touristData['name'] ?? 'N/A'); ?></h6>
                    <small><?php echo htmlspecialchars($touristData['email'] ?? 'N/A'); ?></small>
                </div>
                <div class="bg-light p-3 rounded-3 mb-3 border">
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                        <h6 class="fw-bold text-dark m-0 small text-uppercase"><i class="fas fa-id-card me-2 text-primary"></i>Traveler Profile</h6>
                        <form method="POST" class="m-0">
                            <input type="hidden" name="tourist_id" value="<?php echo $touristId; ?>">
                            <select name="status" class="form-select form-select-sm fw-bold border-secondary-subtle shadow-sm text-center" onchange="this.form.submit()" style="width: 125px; font-size: 0.75rem;">
                                <option value="Pending" <?php echo $touristData['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Processing" <?php echo $touristData['status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="Completed" <?php echo $touristData['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                        </form>
                    </div>
                    <div class="d-flex flex-column gap-2 small">
                        <div class="d-flex justify-content-between border-bottom pb-1">
                            <span class="text-muted">Age:</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($touristData['age'] ?? 'N/A'); ?> yrs</span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-1">
                            <span class="text-muted">Passport No:</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($touristData['passport_number'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-1">
                            <span class="text-muted">Passport Valid:</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($touristData['passport_validation'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-1">
                            <span class="text-muted">Duration:</span>
                            <span class="fw-bold">
                                <?php if (!empty($touristData['travel_start_date'])): ?>
                                    <?php echo date('d M', strtotime($touristData['travel_start_date'])); ?> - 
                                    <?php echo date('d M Y', strtotime($touristData['travel_end_date'])); ?>
                                    (<?php echo htmlspecialchars($touristData['stay_days'] ?? '0'); ?> Days)
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="mt-2">
                            <span class="text-muted d-block mb-1">Purpose of Travel:</span>
                            <div class="p-2 bg-emerald-subtle text-emerald fw-bold rounded border border-emerald-subtle small">
                                <?php echo htmlspecialchars($touristData['purpose_of_travel'] ?? 'N/A'); ?>
                            </div>
                        </div>
                    </div>
                    <?php if (isset($invitationRequestData) && $invitationRequestData): ?>
                        <div class="mt-3 pt-3 border-top">
                            <span class="text-muted d-block mb-2 fw-bold small text-uppercase"><i class="fas fa-link text-primary me-2"></i>Linked Invitation Request</span>
                            <div class="bg-white p-2 rounded border shadow-sm small">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted">Request ID:</span>
                                    <span class="fw-bold text-dark">#<?php echo htmlspecialchars($invitationRequestData['id']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted">Address:</span>
                                    <span class="fw-bold text-dark text-end" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($invitationRequestData['address']); ?>">
                                        <?php echo htmlspecialchars($invitationRequestData['address']); ?>
                                    </span>
                                </div>
                                <?php if (!empty($invitationRequestData['invitation_doc'])): ?>
                                    <div class="mt-2 text-end">
                                        <a href="../<?php echo htmlspecialchars($invitationRequestData['invitation_doc']); ?>" target="_blank" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-file-alt me-1"></i>View Invitation Doc</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($companionsData)): ?>
        <div class="card border-0 shadow-sm p-4 mb-4">
            <h6 class="fw-bold mb-3 text-primary"><i class="fas fa-user-friends me-2"></i>Companions (<?php echo count($companionsData); ?>)</h6>
            <div class="d-flex flex-column gap-3 small">
                <?php foreach ($companionsData as $c): ?>
                    <div class="bg-light p-2 rounded border">
                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($c['name']); ?> <span class="text-muted fw-normal">(<?php echo htmlspecialchars($c['age']); ?> yrs)</span></div>
                        <div class="text-muted mb-2">Passport: <span class="fw-bold text-dark"><?php echo htmlspecialchars($c['passport_number']); ?></span></div>
                        <div class="d-flex gap-2">
                            <?php if (!empty($c['passport_scan'])): ?>
                                <a href="../<?php echo htmlspecialchars($c['passport_scan']); ?>" target="_blank" class="badge bg-primary text-decoration-none px-2 py-1"><i class="fas fa-passport"></i></a>
                            <?php endif; ?>
                            <?php if (!empty($c['signature'])): ?>
                                <a href="../<?php echo htmlspecialchars($c['signature']); ?>" target="_blank" class="badge bg-secondary text-decoration-none px-2 py-1"><i class="fas fa-signature"></i></a>
                            <?php endif; ?>
                            <?php if (!empty($c['visa_scan'])): ?>
                                <a href="../<?php echo htmlspecialchars($c['visa_scan']); ?>" target="_blank" class="badge bg-info text-decoration-none px-2 py-1"><i class="fas fa-file-image"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>


    </div>

    <div class="col-lg-8">
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($touristId): ?>
            <!-- FLIGHT CONFIGURATIONS (READ-ONLY) - OUTSIDE ACCORDION -->
            <div class="card border border-primary-subtle shadow-sm mb-4 bg-light">
                <div class="card-header bg-primary-subtle text-primary fw-bold text-uppercase small py-2 d-flex justify-content-between align-items-center">
                    <div><i class="fas fa-plane me-2"></i>Flight Configurations (Read-Only)</div>
                    <?php 
                        $totalPax = 1 + count($companionsData ?? []);
                        echo "<span class='badge bg-primary rounded-pill'>$totalPax Passenger(s)</span>";
                    ?>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-2 bg-warning-subtle rounded-3 border border-warning-subtle">
                                <div class="bg-warning text-white rounded p-2 me-3 text-center" style="width: 50px;">
                                    <i class="fas fa-plane-arrival d-block mb-1"></i>
                                    <small class="fw-bold" style="font-size: 0.65rem;">ARR</small>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark" style="font-size: 0.85rem;"><?php echo htmlspecialchars($arrangementData['flight_details'] ?? 'TBD'); ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        <?php echo !empty($arrangementData['arrival_date']) ? date('d M Y', strtotime($arrangementData['arrival_date'])) : 'TBD'; ?> | 
                                        <?php echo (!empty($arrangementData['arrival_time']) && $arrangementData['arrival_time'] !== '00:00:00') ? date('H:i', strtotime($arrangementData['arrival_time'])) : 'TBD'; ?>
                                    </div>
                                    <?php if(!empty($arrangementData['pnr_number'])): ?>
                                        <div class="text-dark" style="font-size: 0.75rem;"><span class="text-muted">PNR:</span> <span class="fw-bold"><?php echo htmlspecialchars($arrangementData['pnr_number']); ?></span></div>
                                    <?php endif; ?>
                                </div>
                                <?php if(!empty($arrangementData['flight_ticket'])): ?>
                                    <a href="../<?php echo htmlspecialchars($arrangementData['flight_ticket']); ?>" target="_blank" class="btn btn-sm btn-outline-warning rounded-circle ms-2" title="View Ticket"><i class="fas fa-ticket-alt"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-2 bg-info-subtle rounded-3 border border-info-subtle">
                                <div class="bg-info text-white rounded p-2 me-3 text-center" style="width: 50px;">
                                    <i class="fas fa-plane-departure d-block mb-1"></i>
                                    <small class="fw-bold" style="font-size: 0.65rem;">DEP</small>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark" style="font-size: 0.85rem;"><?php echo htmlspecialchars($arrangementData['return_flight_details'] ?? 'TBD'); ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        <?php echo !empty($arrangementData['return_date']) ? date('d M Y', strtotime($arrangementData['return_date'])) : 'TBD'; ?> | 
                                        <?php echo (!empty($arrangementData['return_time']) && $arrangementData['return_time'] !== '00:00:00') ? date('H:i', strtotime($arrangementData['return_time'])) : 'TBD'; ?>
                                    </div>
                                    <?php if(!empty($arrangementData['return_pnr_number'])): ?>
                                        <div class="text-dark" style="font-size: 0.75rem;"><span class="text-muted">PNR:</span> <span class="fw-bold"><?php echo htmlspecialchars($arrangementData['return_pnr_number']); ?></span></div>
                                    <?php endif; ?>
                                </div>
                                <?php if(!empty($arrangementData['return_flight_ticket'])): ?>
                                    <a href="../<?php echo htmlspecialchars($arrangementData['return_flight_ticket']); ?>" target="_blank" class="btn btn-sm btn-outline-info rounded-circle ms-2" title="View Return Ticket"><i class="fas fa-ticket-alt"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion shadow-sm" id="plannerAccordion">
                
                <!-- Arrangement Form -->
                <div class="accordion-item border-0 mb-3 rounded overflow-hidden">
                    <h2 class="accordion-header" id="headingSetup">
                        <button class="accordion-button fw-bold bg-white text-primary border-bottom" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSetup" aria-expanded="true" aria-controls="collapseSetup">
                            <i class="fas fa-concierge-bell me-2"></i> 1. Trip Setup (Cab, Hotel)
                        </button>
                    </h2>
                    <div id="collapseSetup" class="accordion-collapse collapse show" aria-labelledby="headingSetup" data-bs-parent="#plannerAccordion">
                        <div class="accordion-body bg-white p-4">
                            <?php if ($arrangementData && (!empty($arrangementData['hotel_name']) || !empty($arrangementData['cab_details'])) && !isset($_GET['edit_arrangements'])): ?>
                                <!-- Summary Card -->
                                <div class="card bg-light border-0 shadow-sm mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                            <h6 class="fw-bold text-primary m-0"><i class="fas fa-clipboard-check me-2"></i>Trip Setup Saved</h6>
                                            <?php 
                                                $totalPax = 1 + count($companionsData);
                                                echo "<span class='badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill'>$totalPax Passenger(s)</span>";
                                            ?>
                                        </div>
                                        <div class="row g-3 small">
                                            <div class="col-md-6">
                                                <span class="text-muted d-block"><i class="fas fa-car me-1"></i> Cab Details:</span>
                                                <span class="fw-bold">
                                                   <div>
                                                        <div><strong>Cab No:</strong> <?php echo htmlspecialchars($arrangementData['cab_details'] ?: 'N/A'); ?></div>
                                                        <div><strong>Driver Name:</strong> <?php echo htmlspecialchars($arrangementData['driver_name'] ?: 'N/A'); ?></div>
                                                        <div><strong>Driver Contact:</strong> <?php echo htmlspecialchars($arrangementData['driver_contact'] ?: 'N/A'); ?></div>                                                    </div>

                                                </span>
                                                <?php if(!empty($arrangementData['cab_voucher'])): ?>
                                                    <div class="mt-1"><a href="../<?php echo htmlspecialchars($arrangementData['cab_voucher']); ?>" target="_blank" class="badge bg-primary text-decoration-none"><i class="fas fa-file-pdf me-1"></i>Transport Details</a></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6">
                                                <span class="text-muted d-block"><i class="fas fa-hotel me-1"></i> Hotel Details:</span>
                                                <span class="fw-bold">
                                                       <div>
                                                        <div><strong>Hotel Name:</strong> <?php echo htmlspecialchars($arrangementData['hotel_name'] ?: 'N/A'); ?></div>
                                                        <div><strong>Hotel Location:</strong> <?php echo htmlspecialchars($arrangementData['hotel_location'] ?: 'N/A'); ?></div>
                                                        <div><strong>Hotel Contact:</strong> <?php echo htmlspecialchars($arrangementData['hotel_contact'] ?: 'N/A'); ?></div>
                                                       </div>
                                                </span>
                                                <?php if(!empty($arrangementData['hotel_voucher'])): ?>
                                                    <div class="mt-1"><a href="../<?php echo htmlspecialchars($arrangementData['hotel_voucher']); ?>" target="_blank" class="badge bg-success text-decoration-none"><i class="fas fa-file-pdf me-1"></i>Hotel Voucher</a></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="mt-4 text-end">
                                            <a href="checklist.php?id=<?php echo $touristId; ?>&edit_arrangements=1#headingSetup" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit me-1"></i> Edit Trip Setup</a>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="tourist_id" value="<?php echo $touristId; ?>">
                                
                                <div class="card border border-info-subtle shadow-sm mb-4">
                                    <div class="card-header bg-info-subtle text-info-emphasis fw-bold text-uppercase small py-2">
                                        <i class="fas fa-car me-2"></i>Vehicle Details
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small text-uppercase">Vehicle Details <span class="text-danger">*</span></label>
                                                <input type="text" name="cab_details" class="form-control" value="<?php echo htmlspecialchars($arrangementData['cab_details'] ?? ''); ?>" placeholder="e.g. Innova / TN 67 BK 7890" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small text-uppercase">Pickup Time <span class="text-danger">*</span></label>
                                                <input type="datetime-local" name="pickup_time" class="form-control" value="<?php echo (isset($arrangementData['pickup_time']) && $arrangementData['pickup_time']) ? date('Y-m-d\TH:i', strtotime($arrangementData['pickup_time'])) : ''; ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small text-uppercase">Driver Name</label>
                                                <input type="text" name="driver_name" id="driver_name" class="form-control" value="<?php echo htmlspecialchars($arrangementData['driver_name'] ?? ''); ?>" placeholder="e.g. John Doe">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small text-uppercase">Driver Contact</label>
                                                <input type="text" name="driver_contact" id="driver_contact" class="form-control" value="<?php echo htmlspecialchars($arrangementData['driver_contact'] ?? ''); ?>" placeholder="e.g. +91 9876543210">
                                            </div>
                                            <div class="col-md-12 mt-2">
                                                <label class="form-label fw-medium text-muted small text-uppercase">Upload Cab/Transport Details <span class="text-lowercase fw-normal">(Optional PDF/Image)</span></label>
                                                <input type="file" name="cab_voucher" class="form-control form-control-sm" accept=".pdf,image/*">
                                                <?php if(!empty($arrangementData['cab_voucher'])): ?>
                                                    <div class="mt-1 small"><a href="../<?php echo htmlspecialchars($arrangementData['cab_voucher']); ?>" target="_blank" class="text-decoration-none"><i class="fas fa-file-pdf text-danger me-1"></i>View Current Voucher</a></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card border border-emerald-subtle shadow-sm mb-4">
                                    <div class="card-header bg-emerald-subtle text-emerald fw-bold text-uppercase small py-2">
                                        <i class="fas fa-hotel me-2"></i>Hotel Details
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="row g-3">
                                            <div class="col-md-12">
                                                <label class="form-label fw-medium small text-muted text-uppercase">Hotel Name <span class="text-danger">*</span></label>
                                                <input type="text" name="hotel_name" class="form-control" value="<?php echo htmlspecialchars($arrangementData['hotel_name'] ?? ''); ?>" placeholder="e.g. Taj Hotel" required>
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label fw-medium small text-muted text-uppercase">Hotel Location <span class="text-danger">*</span></label>
                                                <input type="text" name="hotel_location" class="form-control" value="<?php echo htmlspecialchars($arrangementData['hotel_location'] ?? ''); ?>" placeholder="e.g. MG Road, Bangalore" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium small text-muted text-uppercase">Hotel Contact</label>
                                                <input type="text" name="hotel_contact" class="form-control" value="<?php echo htmlspecialchars($arrangementData['hotel_contact'] ?? ''); ?>" placeholder="e.g. +91 80 1234 5678">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium small text-muted text-uppercase">Hotel Email</label>
                                                <input type="email" name="hotel_email" class="form-control" value="<?php echo htmlspecialchars($arrangementData['hotel_email'] ?? ''); ?>" placeholder="e.g. info@tajhotel.com">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-medium small text-muted text-uppercase">Check-In <span class="text-danger">*</span></label>
                                                <input type="datetime-local" name="check_in" class="form-control" value="<?php echo (isset($arrangementData['check_in']) && $arrangementData['check_in']) ? date('Y-m-d\TH:i', strtotime($arrangementData['check_in'])) : ''; ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-medium small text-muted text-uppercase">Check-Out <span class="text-danger">*</span></label>
                                                <input type="datetime-local" name="check_out" class="form-control" value="<?php echo (isset($arrangementData['check_out']) && $arrangementData['check_out']) ? date('Y-m-d\TH:i', strtotime($arrangementData['check_out'])) : ''; ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-medium small text-muted text-uppercase">Room No. <span class="text-danger">*</span></label>
                                                <input type="text" name="room_no" class="form-control" value="<?php echo htmlspecialchars($arrangementData['room_no'] ?? ''); ?>" placeholder="e.g. 201">
                                            </div>
                                            <div class="col-md-12 mt-2">
                                                <label class="form-label fw-medium small text-muted text-uppercase">Upload Hotel Voucher <span class="text-lowercase fw-normal">(Optional PDF/Image)</span></label>
                                                <input type="file" name="hotel_voucher" class="form-control form-control-sm" accept=".pdf,image/*">
                                                <?php if(!empty($arrangementData['hotel_voucher'])): ?>
                                                    <div class="mt-1 small"><a href="../<?php echo htmlspecialchars($arrangementData['hotel_voucher']); ?>" target="_blank" class="text-decoration-none"><i class="fas fa-file-pdf text-danger me-1"></i>View Current Voucher</a></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" name="save_arrangements" class="btn btn-primary w-100 py-2">
                                    <i class="fas fa-check-double me-2"></i>Confirm Arrangements
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Itinerary Section -->
                <div class="accordion-item border-0 mb-3 rounded overflow-hidden">
                    <h2 class="accordion-header" id="headingItinerary">
                        <button class="accordion-button collapsed fw-bold bg-white text-primary border-bottom" type="button" data-bs-toggle="collapse" data-bs-target="#collapseItinerary" aria-expanded="false" aria-controls="collapseItinerary">
                            <i class="fas fa-route me-2"></i> 2. Daily Itinerary Plan
                        </button>
                    </h2>
                    <div id="collapseItinerary" class="accordion-collapse collapse" aria-labelledby="headingItinerary" data-bs-parent="#plannerAccordion">
                        <div class="accordion-body bg-white p-4">
                            <form method="POST" class="card border border-primary-subtle shadow-sm mb-4">
                                <div class="card-header bg-primary-subtle text-primary fw-bold text-uppercase small py-2">
                                    <i class="fas fa-plus-circle me-1"></i> Add Itinerary Entry
                                </div>
                                <div class="card-body p-3">
                                    <input type="hidden" name="tourist_id" value="<?php echo $touristId; ?>">
                                    <div class="row g-3">
                                        <div class="col-md-2">
                                            <label class="form-label small fw-bold text-muted mb-1">Day</label>
                                            <input type="number" name="day_number" class="form-control" placeholder="Day" value="1">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small fw-bold text-muted mb-1">Start Time</label>
                                            <input type="time" name="start_time" class="form-control">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small fw-bold text-muted mb-1">End Time</label>
                                            <input type="time" name="end_time" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-muted mb-1">Place Name</label>
                                            <input type="text" name="place_name" class="form-control" placeholder="e.g. Taj Mahal">
                                        </div>
                                        <div class="col-md-10">
                                            <label class="form-label small fw-bold text-muted mb-1">Activity Details</label>
                                            <input type="text" name="activity" class="form-control" placeholder="Describe the activity...">
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="submit" name="add_itinerary" class="btn btn-primary w-100"><i class="fas fa-plus me-1"></i> Add</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover m-0 align-middle small">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="fw-bold text-center" style="width: 10%;">Day</th>
                                            <th class="fw-bold" style="width: 20%;">Time</th>
                                            <th class="fw-bold" style="width: 25%;">Place</th>
                                            <th class="fw-bold">Activity</th>
                                            <th class="fw-bold text-center" style="width: 10%;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($itineraryData)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">No itinerary items added yet.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($itineraryData as $item): ?>
                                                <tr>
                                                    <td class="text-center fw-bold bg-light">Day <?php echo $item['day_number']; ?></td>
                                                    <td class="text-emerald fw-bold"><?php echo date('H:i', strtotime($item['start_time'])); ?> - <?php echo date('H:i', strtotime($item['end_time'])); ?></td>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($item['place_name']); ?></td>
                                                    <td class="text-muted"><?php echo htmlspecialchars($item['activity']); ?></td>
                                                    <td class="text-center">
                                                        <a href="?action=delete_itinerary&itid=<?php echo $item['id']; ?>&id=<?php echo $touristId; ?>" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="return confirm('Delete this itinerary item?')"><i class="fas fa-trash-alt"></i></a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daily Food Planner Section -->
                <div class="accordion-item border-0 mb-3 rounded overflow-hidden">
                    <h2 class="accordion-header" id="headingFood">
                        <button class="accordion-button collapsed fw-bold bg-white text-primary border-bottom" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFood" aria-expanded="false" aria-controls="collapseFood">
                            <i class="fas fa-utensils me-2"></i> 3. Daily Food Plan
                        </button>
                    </h2>
                    <div id="collapseFood" class="accordion-collapse collapse" aria-labelledby="headingFood" data-bs-parent="#plannerAccordion">
                        <div class="accordion-body bg-white p-4">
                            <form method="POST" class="card border border-warning-subtle shadow-sm mb-4">
                                <div class="card-header bg-warning-subtle text-warning-emphasis fw-bold text-uppercase small py-2">
                                    <i class="fas fa-plus-circle me-1"></i> Add Food Plan
                                </div>
                                <div class="card-body p-3">
                                    <input type="hidden" name="tourist_id" value="<?php echo $touristId; ?>">
                                    <div class="row g-3">
                                        <div class="col-md-2">
                                            <label class="form-label small fw-bold text-muted mb-1">Day <span class="text-danger">*</span></label>
                                            <input type="number" name="food_day_number" class="form-control" placeholder="Day" value="1" min="1" required>
                                        </div>
                                        <div class="col-md-10 d-flex gap-2 mb-2 d-none d-md-flex"></div> <!-- spacer -->
                                        
                                        <div class="col-md-3">
                                            <label class="form-label small fw-bold text-muted mb-1">Breakfast <span class="text-danger">*</span></label>
                                            <input type="text" name="breakfast" class="form-control" placeholder="e.g. Idly, Pongal" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold text-muted mb-1">Lunch <span class="text-danger">*</span></label>
                                            <input type="text" name="lunch" class="form-control" placeholder="e.g. South Indian Meals" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-bold text-muted mb-1">Dinner <span class="text-danger">*</span></label>
                                            <input type="text" name="dinner" class="form-control" placeholder="e.g. Chappathi, Dosa" required>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="submit" name="save_food_plan" class="btn btn-warning w-100 text-dark fw-bold"><i class="fas fa-save me-1"></i> Save</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped m-0 align-middle small">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="fw-bold text-center" style="width: 10%;">Day</th>
                                            <th class="fw-bold"><i class="fas fa-mug-hot text-warning me-1"></i>Breakfast</th>
                                            <th class="fw-bold"><i class="fas fa-utensils text-danger me-1"></i>Lunch</th>
                                            <th class="fw-bold"><i class="fas fa-pizza-slice text-success me-1"></i>Dinner</th>
                                            <th class="fw-bold text-center" style="width: 10%;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($foodArrangements)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">No day-wise food arrangements entered yet.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($foodArrangements as $food): ?>
                                                <tr>
                                                    <td class="text-center fw-bold bg-light">Day <?php echo $food['day_number']; ?></td>
                                                    <td><?php echo htmlspecialchars($food['breakfast']); ?></td>
                                                    <td><?php echo htmlspecialchars($food['lunch']); ?></td>
                                                    <td><?php echo htmlspecialchars($food['dinner']); ?></td>
                                                    <td class="text-center">
                                                        <a href="?id=<?php echo $touristId; ?>&action=delete_food&food_id=<?php echo $food['id']; ?>" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="return confirm('Are you sure you want to delete this food plan?')"><i class="fas fa-trash-alt"></i></a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Finalize Trip Section -->
            <div class="card mt-4 border-0 shadow-lg" style="background: linear-gradient(135deg, #f8fafc, #e2e8f0);">
                <div class="card-body text-center p-5">
                    <h4 class="fw-bold text-dark mb-3">Ready to Send?</h4>
                    <p class="text-muted mb-4">Once all arrangements, itineraries, and food plans are finalized, generate the final schedule link and email it to the traveler.</p>
                    <form method="POST">
                        <input type="hidden" name="tourist_id" value="<?php echo $touristId; ?>">
                        <button type="submit" name="finalize_trip" class="btn btn-success btn-lg px-5 shadow-sm rounded-pill fw-bold">
                            <i class="fas fa-paper-plane me-2"></i> Finalize & Send Itinerary to Traveler
                        </button>
                    </form>
                </div>
            </div>
            
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>



<?php require_once '../includes/footer.php'; ?>
