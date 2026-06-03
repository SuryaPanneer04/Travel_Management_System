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

// Handle Arrangement Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_arrangements'])) {
    $tId = $_POST['tourist_id'];
    $cabId = $_POST['cab_id'];
    $driver = $_POST['driver_name'];
    $contact = $_POST['driver_contact'];
    $pickup = $_POST['pickup_time'];
    $hotelId = $_POST['hotel_id'];
    $checkIn = $_POST['check_in'];
    $checkOut = $_POST['check_out'];
    $room = $_POST['room_no'];
    
    // Relocated arrival configuration fields
    $flight_details = $_POST['flight_details'] ?? '';
    $pnr_number = $_POST['pnr_number'] ?? '';
    $reach_time = !empty($_POST['reach_time']) ? $_POST['reach_time'] : null;
    $arrival_date = !empty($_POST['arrival_date']) ? $_POST['arrival_date'] : null;
    $arrival_time = !empty($_POST['arrival_time']) ? $_POST['arrival_time'] : null;
    
    try {
        $check = $pdo->prepare("SELECT id FROM arrangements WHERE tourist_id = ?");
        $check->execute([$tId]);
        if ($check->fetch()) {
            $stmt = $pdo->prepare("UPDATE arrangements SET cab_no = ?, driver_name = ?, driver_contact = ?, pickup_time = ?, hotel_id = ?, check_in = ?, check_out = ?, room_no = ?, flight_details = ?, reach_time = ?, arrival_date = ?, arrival_time = ?, pnr_number = ?, assigned_by = ? WHERE tourist_id = ?");
            $stmt->execute([$cabId, $driver, $contact, $pickup, $hotelId, $checkIn, $checkOut, $room, $flight_details, $reach_time, $arrival_date, $arrival_time, $pnr_number, $_SESSION['user_id'], $tId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO arrangements (tourist_id, cab_no, driver_name, driver_contact, pickup_time, hotel_id, check_in, check_out, room_no, flight_details, reach_time, arrival_date, arrival_time, pnr_number, assigned_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tId, $cabId, $driver, $contact, $pickup, $hotelId, $checkIn, $checkOut, $room, $flight_details, $reach_time, $arrival_date, $arrival_time, $pnr_number, $_SESSION['user_id']]);
        }
        // Just update employee ID (auto-assign if not set)
        $pdo->prepare("UPDATE tourist_entries SET employee_id = ? WHERE id = ? AND employee_id IS NULL")->execute([$_SESSION['user_id'], $tId]);
        $success = 'Arrangements saved successfully as draft.';
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
        $subject = "Your Trip Schedule is Ready";
        $body = "Your schedule has been finalized. Link: $scheduleLink";
        
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
        $success = 'Trip finalized and email sent to traveler successfully!';
    } catch (PDOException $e) {
        $error = 'Error finalizing trip: ' . $e->getMessage();
    }
}

// Fetch Master Data
$cabsMaster = $pdo->query("SELECT c.*, l.location_name FROM cabs c LEFT JOIN locations l ON c.location = l.id WHERE c.status = 'available' ORDER BY c.provider_name ASC")->fetchAll();
$hotelsMaster = $pdo->query("SELECT h.*, l.location_name FROM hotels h LEFT JOIN locations l ON h.location = l.id WHERE h.status = 'active' ORDER BY h.hotel_name ASC")->fetchAll();

// Fetch Tourist List
$tourists = $pdo->prepare("SELECT * FROM tourist_entries WHERE employee_id = ? ORDER BY id DESC");
$tourists->execute([$_SESSION['user_id']]);
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
}

if (isset($_GET['success'])) {
    if($_GET['success'] == 'itinerary_deleted') $success = 'Itinerary item removed.';
    if($_GET['success'] == 'food_deleted') $success = 'Food plan item removed.';
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
        <div class="card border-0 shadow-sm p-4 mb-4">
            <h5 class="fw-bold mb-4 text-primary"><i class="fas fa-users-cog me-2"></i>Recent Travel Requests</h5>
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
                                <?php if ($t['employee_id']): ?>
                                    <span class="badge bg-success-subtle text-success">Processing</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning">New Request</span>
                                <?php endif; ?>
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
                    <h6 class="fw-bold text-dark mb-3 small text-uppercase"><i class="fas fa-id-card me-2 text-primary"></i>Traveler Profile</h6>
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

        <?php if ($touristData): ?>
        <!-- Smart Hotel Shortlist -->
        <div class="card border-0 shadow-sm p-4">
            <h6 class="fw-bold mb-3 text-primary"><i class="fas fa-hotel me-2"></i>Hotel Shortlist</h6>
            <p class="small text-muted mb-3">Hotels matching requested locations:</p>
            <div class="list-group list-group-flush">
                <?php 
                $matchesFound = false;
                $requestedPlaces = strtolower($touristData['purpose_of_travel'] ?? '');
                foreach ($hotelsMaster as $h): 
                    if (strpos($requestedPlaces, strtolower($h['location'])) !== false):
                        $matchesFound = true;
                ?>
                    <div class="list-group-item px-0 border-0 mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold small"><?php echo htmlspecialchars($h['hotel_name']); ?></div>
                                <div class="text-muted" style="font-size: 11px;"><?php echo htmlspecialchars($h['location_name'] ?? 'N/A'); ?></div>
                            </div>
                            <button class="btn btn-sm btn-outline-primary py-0" onclick="selectHotel(<?php echo $h['id']; ?>)">Select</button>
                        </div>
                    </div>
                <?php 
                    endif;
                endforeach; 
                
                if (!$matchesFound):
                ?>
                    <div class="text-muted small py-3 text-center bg-light rounded">No exact location matches found.</div>
                <?php endif; ?>
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
            <div class="accordion shadow-sm" id="plannerAccordion">
                
                <!-- Arrangement Form -->
                <div class="accordion-item border-0 mb-3 rounded overflow-hidden">
                    <h2 class="accordion-header" id="headingSetup">
                        <button class="accordion-button fw-bold bg-white text-primary border-bottom" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSetup" aria-expanded="true" aria-controls="collapseSetup">
                            <i class="fas fa-concierge-bell me-2"></i> 1. Trip Setup (Flight, Cab, Hotel)
                        </button>
                    </h2>
                    <div id="collapseSetup" class="accordion-collapse collapse show" aria-labelledby="headingSetup" data-bs-parent="#plannerAccordion">
                        <div class="accordion-body bg-white p-4">
                            <?php if ($arrangementData && !isset($_GET['edit_arrangements'])): ?>
                                <!-- Summary Card -->
                                <div class="card bg-light border-0 shadow-sm mb-3">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-primary mb-3"><i class="fas fa-clipboard-check me-2"></i>Trip Setup Saved</h6>
                                        <div class="row g-3 small">
                                            <div class="col-md-4">
                                                <span class="text-muted d-block">Flight/Train:</span>
                                                <span class="fw-bold"><?php echo htmlspecialchars($arrangementData['flight_details'] ?: 'N/A'); ?></span>
                                            </div>
                                            <div class="col-md-4">
                                                <span class="text-muted d-block">Cab Selection:</span>
                                                <span class="fw-bold">
                                                    <?php 
                                                        $cabName = 'N/A';
                                                        foreach ($cabsMaster as $c) {
                                                            if ($c['id'] == $arrangementData['cab_no']) $cabName = $c['vehicle_number'] . ' (' . $c['provider_name'] . ')';
                                                        }
                                                        echo htmlspecialchars($cabName);
                                                    ?>
                                                </span>
                                            </div>
                                            <div class="col-md-4">
                                                <span class="text-muted d-block">Hotel Selection:</span>
                                                <span class="fw-bold">
                                                    <?php 
                                                        $hotelName = 'N/A';
                                                        foreach ($hotelsMaster as $h) {
                                                            if ($h['id'] == $arrangementData['hotel_id']) $hotelName = $h['hotel_name'];
                                                        }
                                                        echo htmlspecialchars($hotelName);
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="mt-3 text-end">
                                            <a href="checklist.php?id=<?php echo $touristId; ?>&edit_arrangements=1#headingSetup" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit me-1"></i> Edit Trip Setup</a>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="tourist_id" value="<?php echo $touristId; ?>">
                                
                                <div class="row g-3 mb-4 bg-light p-3 rounded border">
                                    <div class="col-12 mt-0">
                                        <span class="fw-bold text-primary small text-uppercase"><i class="fas fa-plane-arrival me-2"></i>Flight & Arrival Configuration</span>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium text-muted small text-uppercase">Flight/Train Details <span class="text-danger">*</span></label>
                                        <input type="text" name="flight_details" class="form-control" placeholder="e.g. Indigo 6E-123" value="<?php echo htmlspecialchars($arrangementData['flight_details'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium text-muted small text-uppercase">Flight PNR Number <span class="text-danger">*</span></label>
                                        <input type="text" name="pnr_number" class="form-control" placeholder="e.g. AB12CD" value="<?php echo htmlspecialchars($arrangementData['pnr_number'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium text-muted small text-uppercase">Expected Reach Time <span class="text-danger">*</span></label>
                                        <input type="datetime-local" name="reach_time" class="form-control" value="<?php echo (isset($arrangementData['reach_time']) && $arrangementData['reach_time']) ? date('Y-m-d\TH:i', strtotime($arrangementData['reach_time'])) : ''; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium text-muted small text-uppercase">Arrival Date <span class="text-danger">*</span></label>
                                        <input type="date" name="arrival_date" class="form-control" value="<?php echo htmlspecialchars($arrangementData['arrival_date'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium text-muted small text-uppercase">Arrival Time <span class="text-danger">*</span></label>
                                        <input type="time" name="arrival_time" class="form-control" value="<?php echo htmlspecialchars($arrangementData['arrival_time'] ?? ''); ?>">
                                    </div>
                                </div>   

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium text-muted small text-uppercase">Vehicle Selection <span class="text-danger">*</span></label>
                                        <select name="cab_id" id="cab_select" class="form-select" onchange="updateDriverInfo()">
                                            <option value="">Choose a Cab...</option>
                                            <?php foreach ($cabsMaster as $c): ?>
                                                <option value="<?php echo $c['id']; ?>" 
                                                        data-driver="<?php echo htmlspecialchars($c['driver_name'] ?? ''); ?>" 
                                                        data-contact="<?php echo htmlspecialchars($c['driver_contact'] ?? ''); ?>"
                                                        <?php echo (isset($arrangementData['cab_no']) && $arrangementData['cab_no'] == $c['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($c['vehicle_number']); ?> (<?php echo htmlspecialchars($c['vehicle_type']); ?>) - <?php echo htmlspecialchars($c['location_name'] ?? 'N/A'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium text-muted small text-uppercase">Pickup Time <span class="text-danger">*</span></label>
                                        <input type="datetime-local" name="pickup_time" class="form-control" value="<?php echo (isset($arrangementData['pickup_time']) && $arrangementData['pickup_time']) ? date('Y-m-d\TH:i', strtotime($arrangementData['pickup_time'])) : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="driver_name" id="driver_name" class="form-control form-control-sm bg-light" value="<?php echo $arrangementData['driver_name'] ?? ''; ?>" placeholder="Driver Name" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="driver_contact" id="driver_contact" class="form-control form-control-sm bg-light" value="<?php echo $arrangementData['driver_contact'] ?? ''; ?>" placeholder="Driver Contact" readonly>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-12">
                                        <label class="form-label fw-medium text-muted small text-uppercase">Hotel Selection <span class="text-danger">*</span></label>
                                        <select name="hotel_id" id="hotel_select" class="form-select">
                                            <option value="">Choose a Hotel...</option>
                                            <?php foreach ($hotelsMaster as $h): ?>
                                                <option value="<?php echo $h['id']; ?>" <?php echo (isset($arrangementData['hotel_id']) && $arrangementData['hotel_id'] == $h['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($h['hotel_name']); ?> - <?php echo htmlspecialchars($h['location_name'] ?? 'N/A'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
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
                            <form method="POST" class="bg-light p-3 rounded-3 mb-4">
                                <input type="hidden" name="tourist_id" value="<?php echo $touristId; ?>">
                                <div class="row g-2">
                                    <div class="col-md-2"><input type="number" name="day_number" class="form-control" placeholder="Day" value="1"></div>
                                    <div class="col-md-2"><input type="time" name="start_time" class="form-control"></div>
                                    <div class="col-md-2"><input type="time" name="end_time" class="form-control"></div>
                                    <div class="col-md-4"><input type="text" name="place_name" class="form-control" placeholder="Place Name"></div>
                                    <div class="col-md-10 mt-2"><input type="text" name="activity" class="form-control" placeholder="Activity Details"></div>
                                    <div class="col-md-2 mt-2"><button type="submit" name="add_itinerary" class="btn btn-emerald w-100">Add</button></div>
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
                            <form method="POST" class="bg-light p-3 rounded-3 mb-4">
                                <input type="hidden" name="tourist_id" value="<?php echo $touristId; ?>">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold text-muted">Day <span class="text-danger">*</span></label>
                                        <input type="number" name="food_day_number" class="form-control" placeholder="Day" value="1" min="1" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold text-muted">Breakfast <span class="text-danger">*</span></label>
                                        <input type="text" name="breakfast" class="form-control" placeholder="e.g. Idly, Pongal or Hotel Buffet" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold text-muted">Lunch <span class="text-danger">*</span></label>
                                        <input type="text" name="lunch" class="form-control" placeholder="e.g. South Indian Meals / Veg Biryani" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold text-muted">Dinner <span class="text-danger">*</span></label>
                                        <input type="text" name="dinner" class="form-control" placeholder="e.g. Chappathi, Dosa or Chinese" required>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="submit" name="save_food_plan" class="btn btn-emerald w-100">Save</button>
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

<script>
function updateDriverInfo() {
    const select = document.getElementById('cab_select');
    const opt = select.options[select.selectedIndex];
    document.getElementById('driver_name').value = opt.getAttribute('data-driver') || '';
    document.getElementById('driver_contact').value = opt.getAttribute('data-contact') || '';
}

function selectHotel(id) {
    document.getElementById('hotel_select').value = id;
    document.getElementById('hotel_select').scrollIntoView({ behavior: 'smooth', block: 'center' });
    document.getElementById('hotel_select').classList.add('is-valid');
    setTimeout(() => document.getElementById('hotel_select').classList.remove('is-valid'), 2000);
}
</script>

<?php require_once '../includes/footer.php'; ?>
