<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('employee');

$pageTitle = 'Dashboard';

$empId = $_SESSION['user_id'];

// 1. Total Requests (All incoming)
$totalRequestsStmt = $pdo->query("SELECT COUNT(*) FROM tourist_entries");
$totalRequests = $totalRequestsStmt->fetchColumn();

// 1.5 Today's Requests
$todayRequestsStmt = $pdo->query("SELECT COUNT(*) FROM tourist_entries WHERE DATE(created_at) = CURDATE()");
$todayRequests = $todayRequestsStmt->fetchColumn();

// 2. Handled by Me
$handledByMeStmt = $pdo->prepare("SELECT COUNT(*) FROM tourist_entries WHERE employee_id = ?");
$handledByMeStmt->execute([$empId]);
$handledByMe = $handledByMeStmt->fetchColumn();

// 3. Fully Arranged Trips
$fullyArrangedStmt = $pdo->prepare("
    SELECT COUNT(*) FROM tourist_entries t
    JOIN arrangements a ON t.id = a.tourist_id
    WHERE t.employee_id = ? AND a.hotel_name IS NOT NULL AND a.cab_details IS NOT NULL
");
$fullyArrangedStmt->execute([$empId]);
$fullyArranged = $fullyArrangedStmt->fetchColumn();

// Recent Entries (Assigned to me)
$recentTouristsStmt = $pdo->prepare("
    SELECT t.*, 
        (SELECT COUNT(*) FROM arrangements WHERE tourist_id = t.id) as has_arrangement,
        (SELECT COUNT(*) FROM itineraries WHERE tourist_id = t.id) as itinerary_count,
        (SELECT COUNT(*) FROM food_arrangements WHERE tourist_id = t.id) as food_count
    FROM tourist_entries t 
    WHERE t.employee_id = ?
    ORDER BY t.created_at DESC LIMIT 10
");
$recentTouristsStmt->execute([$empId]);
$tourists = $recentTouristsStmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card p-4 bg-info text-white shadow-sm border-0 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1 text-uppercase text-white-50 small fw-bold">Today's Requests</h6>
                    <h1 class="fw-bold mb-0"><?php echo $todayRequests; ?></h1>
                </div>
                <i class="fas fa-calendar-day fa-3x opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-4 bg-dark text-white shadow-sm border-0 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1 text-uppercase text-white-50 small fw-bold">Fully Arranged</h6>
                    <h1 class="fw-bold mb-0"><?php echo $fullyArranged; ?></h1>
                </div>
                <i class="fas fa-check-circle fa-3x opacity-25"></i>
            </div>
        </div>
    </div>
</div>

<div class="card p-4 border-0 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold m-0"><i class="fas fa-clock text-primary me-2"></i>Recent Trip Requests</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase fw-bold text-muted">Traveler Details</th>
                    <th class="small text-uppercase fw-bold text-muted">Travel Dates</th>
                    <!-- <th class="small text-uppercase fw-bold text-muted">Assignment</th> -->
                    <th class="small text-uppercase fw-bold text-muted">Status</th>
                    <th class="small text-uppercase fw-bold text-muted text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tourists as $row): ?>
                <tr>
                    <td>
                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['name']); ?></div>
                        <div class="small text-muted"><?php echo htmlspecialchars($row['email']); ?></div>
                    </td>
                    <td>
                        <?php if ($row['travel_start_date']): ?>
                            <div class="small fw-medium text-dark"><?php echo date('d M', strtotime($row['travel_start_date'])); ?> - <?php echo date('d M Y', strtotime($row['travel_end_date'])); ?></div>
                        <?php else: ?>
                            <span class="text-muted small">Not set</span>
                        <?php endif; ?>
                    </td>
                    <!-- <td>
                        <?php if ($row['employee_id'] == $empId): ?>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="fas fa-user-tie me-1"></i> Assigned to You</span>
                        <?php elseif ($row['employee_id']): ?>
                            <span class="badge bg-secondary-subtle text-secondary border"><i class="fas fa-user-lock me-1"></i> Taken</span>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="fas fa-clock me-1"></i> Unassigned</span>
                        <?php endif; ?>
                    </td> -->
                    <td>
                        <?php if (isset($row['status']) && $row['status'] === 'Completed'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill shadow-sm"><i class="fas fa-check-circle me-1"></i> Completed</span>
                        <?php elseif (isset($row['status']) && $row['status'] === 'Processing'): ?>
                            <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2 rounded-pill shadow-sm"><i class="fas fa-spinner fa-spin me-1"></i> Processing</span>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill shadow-sm"><i class="fas fa-clock me-1"></i> Pending</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php 
                            $tooltipContent = "<b>Arrangements:</b> " . ($row['has_arrangement'] ? 'Done' : 'Pending') . "<br>";
                            $tooltipContent .= "<b>Itinerary:</b> " . $row['itinerary_count'] . " days<br>";
                            $tooltipContent .= "<b>Food Plan:</b> " . $row['food_count'] . " days";
                        ?>
                        <button class="btn btn-sm  rounded-pill shadow-sm px-3 me-1" title="view details" data-bs-toggle="modal" data-bs-target="#passModal<?php echo $row['id']; ?>">
                            <i class="fas fa-eye"></i>
                        </button>
                        <!-- <a href="checklist.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-light text-primary rounded-circle shadow-sm" style="width: 35px; height: 35px; line-height: 22px;" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-html="true" title="<?php echo $tooltipContent; ?>">
                            <i class="fas fa-eye"></i>
                        </a> -->
                        
                        <!-- Modals generated outside table -->
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($tourists)): ?>
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                        <h5>No requests yet.</h5>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Generate Pass Modals -->
<?php foreach ($tourists as $row): 
    $companionsStmt = $pdo->prepare("SELECT * FROM tourist_companions WHERE tourist_id = ?");
    $companionsStmt->execute([$row['id']]);
    $companions = $companionsStmt->fetchAll();
    
    $arrStmt = $pdo->prepare("SELECT * FROM arrangements WHERE tourist_id = ?");
    $arrStmt->execute([$row['id']]);
    $arrangement = $arrStmt->fetch();
?>
<div class="modal fade text-start" id="passModal<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 bg-transparent">
            <div class="modal-body p-0">
                <div class="bg-white d-flex flex-column flex-md-row" style="border-radius: 16px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
                    <div class="position-relative bg-white" style="flex: 3;">
                        <div class="d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white; padding: 20px 30px;">
                            <h4 class="m-0 fw-bold"><i class="fas fa-plane-departure me-2"></i> Traveler Pass</h4>
                            <span class="badge bg-white text-primary border-0 shadow-sm fs-6 px-3 py-2 rounded-pill">ID: <?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="p-4">
                            <div class="row g-4">
                                <div class="col-sm-6">
                                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Passenger Name</div>
                                    <div style="font-size: 1.25rem; color: #0f172a; font-weight: 700;"><?php echo htmlspecialchars($row['name']); ?></div>
                                </div>
                                <div class="col-sm-6">
                                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Passport Number</div>
                                    <div style="font-size: 1.25rem; color: #0f172a; font-weight: 700;"><?php echo htmlspecialchars($row['passport_number'] ?? 'N/A'); ?></div>
                                </div>
                                <div class="col-sm-4">
                                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Age</div>
                                    <div style="font-size: 1.1rem; color: #0f172a; font-weight: 600;"><?php echo htmlspecialchars($row['age'] ?? 'N/A'); ?> Yrs</div>
                                </div>
                                <div class="col-sm-4">
                                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Travel Start</div>
                                    <div style="font-size: 1.1rem; color: #0f172a; font-weight: 600;"><?php echo $row['travel_start_date'] ? date('d M Y', strtotime($row['travel_start_date'])) : 'N/A'; ?></div>
                                </div>
                                <div class="col-sm-4">
                                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Travel End</div>
                                    <div style="font-size: 1.1rem; color: #0f172a; font-weight: 600;"><?php echo $row['travel_end_date'] ? date('d M Y', strtotime($row['travel_end_date'])) : 'N/A'; ?></div>
                                </div>
                                <div class="col-sm-6">
                                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Purpose</div>
                                    <div style="font-size: 1.1rem; color: #0f172a; font-weight: 600;"><?php echo htmlspecialchars($row['purpose_of_travel'] ?? 'N/A'); ?></div>
                                </div>
                                <div class="col-sm-6">
                                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Visa Type</div>
                                    <div style="font-size: 1.1rem; color: #0f172a; font-weight: 600;"><?php echo htmlspecialchars($row['visa_type'] ?? 'N/A'); ?></div>
                                </div>
                                <?php if ($arrangement && (!empty($arrangement['flight_details']) || !empty($arrangement['return_flight_details']))): ?>
                                <div class="col-sm-12 mt-3 border-top pt-3">
                                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; margin-bottom: 10px;"><i class="fas fa-plane text-primary me-1"></i> Flight Details</div>
                                    <div class="row g-2">
                                        <div class="col-sm-6">
                                            <div style="font-size: 0.85rem; color: #64748b;">Onward Flight</div>
                                            <div style="font-size: 1rem; color: #0f172a; font-weight: 600;"><?php echo htmlspecialchars($arrangement['flight_details'] ?: 'N/A'); ?></div>
                                            <?php if(!empty($arrangement['pnr_number'])): ?>
                                                <div style="font-size: 0.8rem; color: #64748b;">PNR: <span class="fw-bold"><?php echo htmlspecialchars($arrangement['pnr_number']); ?></span></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-sm-6">
                                            <div style="font-size: 0.85rem; color: #64748b;">Return Flight</div>
                                            <div style="font-size: 1rem; color: #0f172a; font-weight: 600;"><?php echo htmlspecialchars($arrangement['return_flight_details'] ?: 'N/A'); ?></div>
                                            <?php if(!empty($arrangement['return_pnr_number'])): ?>
                                                <div style="font-size: 0.8rem; color: #64748b;">PNR: <span class="fw-bold"><?php echo htmlspecialchars($arrangement['return_pnr_number']); ?></span></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="col-sm-12 mt-3 border-top pt-3">
                                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; margin-bottom: 10px;">Uploaded Documents</div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <?php if (!empty($row['passport_scan'])): ?>
                                            <a href="../<?php echo htmlspecialchars($row['passport_scan']); ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm"><i class="fas fa-passport me-1"></i> Passport</a>
                                        <?php endif; ?>
                                        <?php if (!empty($row['signature'])): ?>
                                            <a href="../<?php echo htmlspecialchars($row['signature']); ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm"><i class="fas fa-signature me-1"></i> Signature</a>
                                        <?php endif; ?>
                                        <?php if (!empty($row['visa_scan'])): ?>
                                            <a href="../<?php echo htmlspecialchars($row['visa_scan']); ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm"><i class="fas fa-file-image me-1"></i> Visa Scan</a>
                                        <?php endif; ?>
                                        <?php if ($arrangement && !empty($arrangement['flight_ticket'])): ?>
                                            <a href="../<?php echo htmlspecialchars($arrangement['flight_ticket']); ?>" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill px-3 shadow-sm"><i class="fas fa-ticket-alt me-1"></i> Ticket</a>
                                        <?php endif; ?>
                                        <?php if ($arrangement && !empty($arrangement['return_flight_ticket'])): ?>
                                            <a href="../<?php echo htmlspecialchars($arrangement['return_flight_ticket']); ?>" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill px-3 shadow-sm"><i class="fas fa-plane-departure me-1"></i> Return Ticket</a>
                                        <?php endif; ?>
                                        <?php if ($arrangement && !empty($arrangement['hotel_voucher'])): ?>
                                            <a href="../<?php echo htmlspecialchars($arrangement['hotel_voucher']); ?>" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-3 shadow-sm"><i class="fas fa-hotel me-1"></i> Hotel</a>
                                        <?php endif; ?>
                                        <?php if ($arrangement && !empty($arrangement['cab_voucher'])): ?>
                                            <a href="../<?php echo htmlspecialchars($arrangement['cab_voucher']); ?>" target="_blank" class="btn btn-sm btn-outline-info rounded-pill px-3 shadow-sm"><i class="fas fa-car me-1"></i> Cab</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!empty($companions)): ?>
                                <div class="col-sm-12 mt-3 border-top pt-3">
                                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; margin-bottom: 10px;">Companions (<?php echo count($companions); ?>)</div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless m-0" style="font-size: 0.85rem;">
                                            <thead class="border-bottom">
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Age</th>
                                                    <th>Passport</th>
                                                    <th>Docs</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($companions as $c): ?>
                                                <tr>
                                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($c['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($c['age']); ?></td>
                                                    <td><?php echo htmlspecialchars($c['passport_number']); ?></td>
                                                    <td>
                                                        <div class="d-flex gap-1">
                                                            <?php if (!empty($c['passport_scan'])): ?>
                                                                <a href="../<?php echo htmlspecialchars($c['passport_scan']); ?>" target="_blank" class="badge bg-primary text-decoration-none" title="Passport">P</a>
                                                            <?php endif; ?>
                                                            <?php if (!empty($c['signature'])): ?>
                                                                <a href="../<?php echo htmlspecialchars($c['signature']); ?>" target="_blank" class="badge bg-secondary text-decoration-none" title="Signature">S</a>
                                                            <?php endif; ?>
                                                            <?php if (!empty($c['visa_scan'])): ?>
                                                                <a href="../<?php echo htmlspecialchars($c['visa_scan']); ?>" target="_blank" class="badge bg-info text-decoration-none" title="Visa">V</a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="bg-light d-flex flex-column justify-content-center align-items-center p-4" style="flex: 1; border-left: 2px dashed #cbd5e1; position: relative;">
                        <div class="d-none d-md-block" style="position: absolute; top: -15px; left: -15px; width: 30px; height: 30px; background: rgba(0,0,0,0.5); border-radius: 50%;"></div>
                        <div class="d-none d-md-block" style="position: absolute; bottom: -15px; left: -15px; width: 30px; height: 30px; background: rgba(0,0,0,0.5); border-radius: 50%;"></div>
                        
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=TOURIST-PASS-<?php echo $row['id']; ?>" class="mb-3 rounded shadow-sm" alt="QR Code">
                        <div class="text-uppercase text-muted fw-bold" style="font-size: 0.85rem; letter-spacing: 3px;">Boarding</div>
                        <div class="fs-3 fw-bold text-dark mt-1">PASS</div>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-4 w-100 rounded-pill" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php require_once '../includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
});
</script>
