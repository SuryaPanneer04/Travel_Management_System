<?php
session_start();
require_once '../config/db.php';

if (isset($_GET['token'])) {
    $stmt = $pdo->prepare("SELECT id FROM tourist_entries WHERE token = ?");
    $stmt->execute([$_GET['token']]);
    $tourist = $stmt->fetch();
    if ($tourist) {
        $_SESSION['tourist_id'] = $tourist['id'];
    }
}

if (!isset($_SESSION['tourist_id'])) {
    die("Invalid access. Please use the link sent to your email.");
}

$touristId = $_SESSION['tourist_id'];

// Fetch tourist info
$stmt = $pdo->prepare("SELECT name FROM tourist_entries WHERE id = ?");
$stmt->execute([$touristId]);
$tourist = $stmt->fetch();

// Fetch arrangements with related Master Data and Staff Info
$stmt = $pdo->prepare("
    SELECT a.*, 
           COALESCE(a.flight_details) as flight_details,
           COALESCE(a.reach_time) as reach_time,
           COALESCE(a.arrival_date) as arrival_date,
           COALESCE(a.arrival_time) as arrival_time,
           h.hotel_name, h.address as hotel_address, h.location as hotel_location, 
           h.star_rating, h.contact_number as hotel_contact, h.email as hotel_email,
           c.vehicle_number, c.vehicle_type, c.provider_name as cab_provider,
           u.username as staff_name
    FROM arrangements a
    LEFT JOIN hotels h ON a.hotel_id = h.id
    LEFT JOIN cabs c ON a.cab_no = c.id
    LEFT JOIN users u ON a.assigned_by = u.id
    WHERE a.tourist_id = ?
");
$stmt->execute([$touristId]);
$arrangements = $stmt->fetch();

// Fetch granular itinerary
$stmt = $pdo->prepare("SELECT * FROM itineraries WHERE tourist_id = ? ORDER BY day_number ASC, start_time ASC");
$stmt->execute([$touristId]);
$itinerary = $stmt->fetchAll();

// Fetch granular day-wise food arrangements
$stmt = $pdo->prepare("SELECT * FROM food_arrangements WHERE tourist_id = ? ORDER BY day_number ASC");
$stmt->execute([$touristId]);
$foodArrangements = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Schedule - <?php echo $tourist['name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --emerald: #10b981;
            --emerald-dark: #059669;
            --slate-900: #0f172a;
        }
        body { background: #f8fafc; font-family: 'Inter', sans-serif; color: #334155; }
        .card { border: none; border-radius: 1.25rem; }
        .timeline-item { border-left: 2px dashed #e2e8f0; position: relative; padding-left: 35px; margin-left: 10px; }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -9px;
            top: 5px;
            width: 16px;
            height: 16px;
            background: var(--emerald);
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 0 0 2px var(--emerald);
        }
        .day-badge { background: var(--slate-900); color: white; padding: 6px 18px; border-radius: 50px; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; }
        .star-rating { color: #f59e0b; }
        .info-label { font-size: 0.7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; display: block; }
        .info-value { font-weight: 600; font-size: 0.9rem; color: #1e293b; }
        .contact-pill { background: #f1f5f9; border-radius: 8px; padding: 8px 12px; display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
        .contact-pill i { color: var(--emerald); width: 15px; }
        @media print {
            .no-print { display: none; }
            body { background: white; }
            .card { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
            .container { max-width: 100% !important; width: 100% !important; }
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-11 mx-auto">
            
            <div class="d-flex justify-content-between align-items-center mb-5 no-print">
                <!-- <a href="arrival_details.php" class="btn btn-light rounded-pill px-4 btn-sm shadow-sm">
                    <i class="fas fa-arrow-left me-2"></i>Edit Arrival Details
                </a> -->
                <button onclick="window.print()" class="btn btn-dark rounded-pill px-4 btn-sm shadow-sm">
                    <i class="fas fa-print me-2"></i>Print PDF
                </button>
            </div>

            <div class="text-center mb-5">
                <span class="badge bg-emerald-subtle text-emerald px-3 py-2 rounded-pill mb-3 fw-bold">OFFICIAL TRIP ITINERARY</span>
                <h1 class="fw-bold text-slate-900 display-5">Welcome to Your Journey</h1>
                <p class="text-muted">Hello <strong><?php echo $tourist['name']; ?></strong>, your trip has been professionally curated by <strong><?php echo $arrangements['staff_name'] ?? 'our team'; ?></strong>.</p>
            </div>

            <?php if ($arrangements): ?>
            <!-- Trip Summary Table Section -->
            <div class="card shadow-sm mb-5 border-0 overflow-hidden">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold m-0"><i class="fas fa-file-invoice text-primary me-2"></i>Booking Summary</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered m-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-uppercase small fw-bold px-4">Service Type</th>
                                <th class="text-uppercase small fw-bold px-4">Provider / Details</th>
                                <th class="text-uppercase small fw-bold px-4">Contact Information</th>
                                <th class="text-uppercase small fw-bold px-4">Scheduled Timing</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Hotel Row -->
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-emerald-subtle text-emerald p-2 rounded-3 me-3">
                                            <i class="fas fa-hotel"></i>
                                        </div>
                                        <span class="fw-bold">Accommodation</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="fw-bold"><?php echo $arrangements['hotel_name']; ?></div>
                                    <div class="small text-muted"><?php echo $arrangements['hotel_location']; ?></div>
                                    <div class="badge bg-light text-dark border mt-1">Room: <?php echo $arrangements['room_no'] ?: 'TBD'; ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="small mb-1"><i class="fas fa-phone-alt me-2 text-muted"></i><?php echo $arrangements['hotel_contact']; ?></div>
                                    <div class="small"><i class="fas fa-envelope me-2 text-muted"></i><?php echo $arrangements['hotel_email']; ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="small">Check-in: <span class="fw-bold text-dark"><?php echo $arrangements['check_in'] ? date('d M, H:i', strtotime($arrangements['check_in'])) : 'TBD'; ?></span></div>
                                    <div class="small">Check-out: <span class="fw-bold text-dark"><?php echo $arrangements['check_out'] ? date('d M, H:i', strtotime($arrangements['check_out'])) : 'TBD'; ?></span></div>
                                </td>
                            </tr>
                            <!-- Cab Row -->
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary-subtle text-primary p-2 rounded-3 me-3">
                                            <i class="fas fa-car"></i>
                                        </div>
                                        <span class="fw-bold">Transport</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="fw-bold"><?php echo $arrangements['vehicle_number']; ?></div>
                                    <div class="small text-muted"><?php echo $arrangements['vehicle_type']; ?> (<?php echo $arrangements['cab_provider']; ?>)</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="fw-bold small"><?php echo $arrangements['driver_name']; ?> (Driver)</div>
                                    <div class="small"><i class="fas fa-phone-alt me-2 text-muted"></i><?php echo $arrangements['driver_contact']; ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="bg-primary-subtle text-primary p-2 rounded text-center small fw-bold">
                                        Pickup: <?php echo date('d M Y | H:i', strtotime($arrangements['pickup_time'])); ?>
                                    </div>
                                </td>
                            </tr>
                            <!-- Flight / Arrival Row -->
                            <?php if (!empty($arrangements['flight_details']) || !empty($arrangements['arrival_date'])): ?>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-warning-subtle text-warning p-2 rounded-3 me-3">
                                            <i class="fas fa-plane-arrival"></i>
                                        </div>
                                        <span class="fw-bold">Flight / Arrival</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="fw-bold"><?php echo htmlspecialchars($arrangements['flight_details']); ?></div>
                                    <?php if (!empty($arrangements['pnr_number'])): ?>
                                        <div class="badge bg-warning-subtle text-warning border mt-1">PNR: <?php echo htmlspecialchars($arrangements['pnr_number']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if (!empty($arrangements['reach_time'])): ?>
                                        <div class="small"><span class="text-muted small text-uppercase fw-bold">Reach:</span> <span class="fw-bold text-dark"><?php echo date('d M Y | H:i', strtotime($arrangements['reach_time'])); ?></span></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="bg-warning-subtle text-warning p-2 rounded text-center small fw-bold">
                                        Arrival: <?php 
                                            if (!empty($arrangements['arrival_date'])) {
                                                echo date('d M Y', strtotime($arrangements['arrival_date']));
                                                if (!empty($arrangements['arrival_time'])) {
                                                    echo ' | ' . date('H:i', strtotime($arrangements['arrival_time']));
                                                }
                                            } else {
                                                echo 'TBD';
                                            }
                                        ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <!-- Status & Support Row -->
                            <tr class="bg-light-subtle">
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-dark text-white p-2 rounded-3 me-3">
                                            <i class="fas fa-user-check"></i>
                                        </div>
                                        <span class="fw-bold">Management</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="small text-muted">Assigned Specialist:</div>
                                    <div class="fw-bold"><?php echo $arrangements['staff_name'] ?: 'Trip Coordinator'; ?></div>
                                </td>
                                <td class="px-4 py-3" colspan="2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-muted">Trip Status:</div>
                                            <span class="badge bg-success rounded-pill px-3">CONFIRMED & READY</span>
                                        </div>
                                        <div class="text-end">
                                            <div class="small text-muted">Booking Reference:</div>
                                            <div class="fw-bold">#TRP-<?php echo str_pad($arrangements['id'], 5, '0', STR_PAD_LEFT); ?></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <!-- Stay Details -->
                <div class="col-lg-4">
                    <div class="card h-100 shadow-sm border-0 border-top border-4 border-emerald">
                        <div class="card-body p-4 p-lg-5">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <h5 class="fw-bold m-0"><i class="fas fa-hotel text-emerald me-3"></i>Hotel Accommodation</h5>
                                <div class="star-rating">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <i class="fa<?php echo ($i <= $arrangements['star_rating']) ? 's' : 'r'; ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h4 class="fw-bold mb-1 text-slate-900"><?php echo $arrangements['hotel_name']; ?></h4>
                                <p class="text-muted mb-0"><i class="fas fa-map-marker-alt me-2"></i><?php echo $arrangements['hotel_address']; ?>, <?php echo $arrangements['hotel_location']; ?></p>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-sm-6">
                                    <div class="p-2 bg-light rounded-3 text-center">
                                        <span class="info-label">Check-In</span>
                                        <span class="info-value" style="font-size: 0.8rem;"><?php echo $arrangements['check_in'] ? date('d M, H:i', strtotime($arrangements['check_in'])) : 'TBD'; ?></span>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-2 bg-light rounded-3 text-center">
                                        <span class="info-label">Check-Out</span>
                                        <span class="info-value" style="font-size: 0.8rem;"><?php echo $arrangements['check_out'] ? date('d M, H:i', strtotime($arrangements['check_out'])) : 'TBD'; ?></span>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="p-2 bg-emerald-subtle rounded-3 text-center">
                                        <span class="info-label text-emerald">Room No.</span>
                                        <span class="info-value text-emerald"><?php echo $arrangements['room_no'] ?: 'Wait...'; ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="contact-pill small mb-2">
                                        <i class="fas fa-phone-alt"></i>
                                        <span><?php echo $arrangements['hotel_contact'] ?: 'Contact not available'; ?></span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="contact-pill small mb-0">
                                        <i class="fas fa-envelope"></i>
                                        <span><?php echo $arrangements['hotel_email'] ?: 'Email not available'; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cab Details -->
                <div class="col-lg-4">
                    <div class="card h-100 shadow-sm border-0 border-top border-4 border-primary">
                        <div class="card-body p-4 p-lg-5">
                            <h5 class="fw-bold mb-4"><i class="fas fa-car-side text-primary me-3"></i>Transport Details</h5>
                            
                            <div class="p-3 bg-primary-subtle rounded-4 mb-4 text-center">
                                <span class="info-label text-primary">Scheduled Pickup</span>
                                <div class="h4 fw-bold text-primary m-0"><?php echo date('H:i', strtotime($arrangements['pickup_time'])); ?></div>
                                <div class="fw-bold small text-primary opacity-75"><?php echo date('d M Y', strtotime($arrangements['pickup_time'])); ?></div>
                            </div>

                            <div class="mb-4">
                                <span class="info-label">Vehicle Details</span>
                                <div class="d-flex align-items-center gap-2">
                                    <h6 class="fw-bold mb-0 text-slate-900"><?php echo $arrangements['vehicle_number']; ?></h6>
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill small"><?php echo $arrangements['vehicle_type']; ?></span>
                                </div>
                                <p class="text-muted small m-0"><?php echo $arrangements['cab_provider']; ?></p>
                            </div>

                            <div class="bg-light p-4 rounded-4 border">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-white p-2 rounded-circle shadow-sm me-3">
                                        <i class="fas fa-user-tie text-primary fa-lg"></i>
                                    </div>
                                    <div>
                                        <span class="info-label">Driver Assigned</span>
                                        <div class="fw-bold h6 m-0"><?php echo $arrangements['driver_name']; ?></div>
                                    </div>
                                </div>
                                <a href="tel:<?php echo $arrangements['driver_contact']; ?>" class="btn btn-primary w-100 rounded-pill shadow-sm">
                                    <i class="fas fa-phone me-2"></i>Call Driver
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Flight & Arrival Details -->
                <div class="col-lg-4">
                    <div class="card h-100 shadow-sm border-0 border-top border-4 border-warning">
                        <div class="card-body p-4 p-lg-5 d-flex flex-column justify-content-between">
                            <div>
                                <h5 class="fw-bold mb-4"><i class="fas fa-plane-arrival text-warning me-3"></i>Flight & Arrival</h5>
                                
                                <div class="p-3 bg-warning-subtle rounded-4 mb-4 text-center">
                                    <span class="info-label text-warning">Expected Arrival</span>
                                    <div class="h4 fw-bold text-warning m-0">
                                        <?php echo !empty($arrangements['arrival_time']) ? date('H:i', strtotime($arrangements['arrival_time'])) : 'TBD'; ?>
                                    </div>
                                    <div class="fw-bold small text-warning opacity-75">
                                        <?php echo !empty($arrangements['arrival_date']) ? date('d M Y', strtotime($arrangements['arrival_date'])) : 'TBD'; ?>
                                    </div>
                                </div>

                                <div class="row g-2 mb-4">
                                    <div class="col-6">
                                        <span class="info-label">Flight/Train Info</span>
                                        <h6 class="fw-bold mb-0 text-slate-900 small"><?php echo htmlspecialchars($arrangements['flight_details'] ?: 'TBD'); ?></h6>
                                    </div>
                                    <div class="col-6">
                                        <span class="info-label">PNR Number</span>
                                        <h6 class="fw-bold mb-0 text-slate-900 small"><?php echo htmlspecialchars($arrangements['pnr_number'] ?: 'TBD'); ?></h6>
                                    </div>
                                </div>
                                
                                <?php if (!empty($arrangements['reach_time'])): ?>
                                    <div class="mb-4">
                                        <span class="info-label">Expected Reach Time</span>
                                        <p class="text-slate-900 fw-medium small mb-0"><i class="far fa-clock me-2 text-warning"></i><?php echo date('d M Y, H:i', strtotime($arrangements['reach_time'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Food Arrangements -->
                            <div class="mt-auto pt-3 border-top">
                                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-utensils text-warning me-2"></i>Daily Food Plan</h6>
                                <?php if (empty($foodArrangements)): ?>
                                    <div class="p-3 bg-light rounded text-center small text-muted">No meal plan structured yet.</div>
                                <?php else: ?>
                                    <div class="d-flex flex-column gap-2" style="max-height: 250px; overflow-y: auto;">
                                        <?php foreach ($foodArrangements as $food): ?>
                                            <div class="p-2 rounded bg-light border small">
                                                <div class="fw-bold text-primary mb-1" style="font-size: 0.75rem;">Day <?php echo $food['day_number']; ?></div>
                                                <div class="d-flex flex-column gap-1" style="font-size: 11px;">
                                                    <div><i class="fas fa-mug-hot text-warning me-1" style="width: 12px;"></i><span class="text-muted">Breakfast:</span> <?php echo htmlspecialchars($food['breakfast']); ?></div>
                                                    <div><i class="fas fa-utensils text-danger me-1" style="width: 12px;"></i><span class="text-muted">Lunch:</span> <?php echo htmlspecialchars($food['lunch']); ?></div>
                                                    <div><i class="fas fa-pizza-slice text-success me-1" style="width: 12px;"></i><span class="text-muted">Dinner:</span> <?php echo htmlspecialchars($food['dinner']); ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Itinerary Section -->
            <div class="card shadow-sm mb-5">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <h5 class="fw-bold m-0"><i class="fas fa-route text-emerald me-3"></i>Trip Itinerary</h5>
                        <div class="small text-muted">Last updated: <?php echo date('d M, Y'); ?></div>
                    </div>

                    <?php if (empty($itinerary)): ?>
                        <div class="text-center py-5">
                            <div class="mb-3 text-muted opacity-25">
                                <i class="fas fa-hourglass-start fa-4x"></i>
                            </div>
                            <h5 class="fw-bold">Your schedule is almost ready</h5>
                            <p class="text-muted">Our travel experts are building your perfect day-wise plan.</p>
                        </div>
                    <?php else: ?>
                        <?php 
                        $currDay = 0;
                        foreach ($itinerary as $item): 
                            if ($item['day_number'] != $currDay):
                                $currDay = $item['day_number'];
                        ?>
                            <div class="d-flex align-items-center mb-5 mt-5 <?php echo ($currDay == 1) ? 'mt-0' : ''; ?>">
                                <div class="day-badge">DAY <?php echo $currDay; ?></div>
                                <div class="flex-grow-1 ms-3 border-bottom opacity-25"></div>
                            </div>
                        <?php endif; ?>

                        <div class="timeline-item mb-5">
                            <div class="row">
                                <div class="col-md-2 mb-3 mb-md-0">
                                    <div class="bg-white border p-2 rounded-3 text-center shadow-sm" style="max-width: 100px;">
                                        <div class="fw-bold text-slate-900 small"><?php echo date('H:i', strtotime($item['start_time'])); ?></div>
                                        <div class="text-muted my-1" style="font-size: 10px;">to</div>
                                        <div class="fw-bold text-emerald small"><?php echo date('H:i', strtotime($item['end_time'])); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-10">
                                    <div class="p-4 bg-light rounded-4 border-0">
                                        <h5 class="fw-bold text-slate-900 mb-2"><?php echo $item['place_name']; ?></h5>
                                        <p class="text-muted mb-0 lh-lg"><?php echo $item['activity']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Footer Section -->
            <div class="text-center py-4 no-print border-top mt-5">
                <p class="text-muted small mb-4">Have an amazing trip! For any emergencies, please use the contact buttons above.</p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="tel:+919876543210" class="btn btn-outline-dark rounded-pill px-4 btn-sm">24/7 Support</a>
                    <a href="../logout.php" class="btn btn-link text-danger text-decoration-none fw-bold btn-sm">Logout Session</a>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
