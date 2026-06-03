<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('admin');

$pageTitle = 'Trip Oversight';

// Fetch all tourists with their arrival details and arrangements
$stmt = $pdo->query("
    SELECT t.id as tourist_id, t.name as tourist_name, t.email as tourist_email,
           COALESCE(arr.arrival_date) as arrival_date, 1 as people_count, t.purpose_of_travel as places_to_visit,
           arr.hotel_id, arr.cab_no, arr.assigned_by,
           h.hotel_name, c.vehicle_number, u.username as employee_name
    FROM tourist_entries t
    LEFT JOIN arrangements arr ON t.id = arr.tourist_id
    LEFT JOIN hotels h ON arr.hotel_id = h.id
    LEFT JOIN cabs c ON arr.cab_no = c.id
    LEFT JOIN users u ON t.employee_id = u.id
    ORDER BY t.created_at DESC
");
$trips = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="card border-0 shadow-sm p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0 text-primary">Comprehensive Trip Oversight</h4>
            <p class="text-muted small mb-0">Monitor all tourist arrivals and employee assignments</p>
        </div>
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm rounded-pill px-3">
            <i class="fas fa-print me-2"></i>Export Report
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle border">
            <thead class="bg-light">
                <tr>
                    <th class="small text-uppercase fw-bold">Traveler & Arrival</th>
                    <th class="small text-uppercase fw-bold">Requested Places</th>
                    <th class="small text-uppercase fw-bold">Handling Employee</th>
                    <th class="small text-uppercase fw-bold">Current Arrangements</th>
                    <th class="small text-uppercase fw-bold">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trips as $trip): ?>
                <tr>
                    <td>
                        <div class="fw-bold"><?php echo $trip['tourist_name']; ?></div>
                        <div class="text-muted small"><?php echo $trip['tourist_email']; ?></div>
                        <?php if ($trip['arrival_date']): ?>
                            <div class="badge bg-primary-subtle text-primary mt-1">
                                <i class="fas fa-calendar-alt me-1"></i> <?php echo date('d M Y', strtotime($trip['arrival_date'])); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="max-width: 200px;">
                        <div class="small text-truncate"><?php echo $trip['places_to_visit'] ?: '<span class="text-muted italic">Not submitted</span>'; ?></div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="bg-light p-2 rounded-circle me-2">
                                <i class="fas fa-user-tie text-secondary"></i>
                            </div>
                            <span class="fw-medium"><?php echo $trip['employee_name'] ?: 'Unassigned'; ?></span>
                        </div>
                    </td>
                    <td>
                        <div class="small">
                            <div class="mb-1">
                                <i class="fas fa-hotel text-emerald me-2" style="width: 15px;"></i>
                                <?php echo $trip['hotel_name'] ?: '<span class="text-muted">No Hotel</span>'; ?>
                            </div>
                            <div>
                                <i class="fas fa-car text-primary me-2" style="width: 15px;"></i>
                                <?php echo $trip['vehicle_number'] ?: '<span class="text-muted">No Cab</span>'; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ($trip['hotel_id'] && $trip['cab_no']): ?>
                            <span class="badge bg-success rounded-pill px-3">Fully Arranged</span>
                        <?php elseif ($trip['arrival_date']): ?>
                            <span class="badge bg-warning text-dark rounded-pill px-3">Planning...</span>
                        <?php else: ?>
                            <span class="badge bg-light text-muted border rounded-pill px-3">New Entry</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
