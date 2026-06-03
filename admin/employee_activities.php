<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('admin');

$pageTitle = 'Employee Activities & Oversight';

$query = "SELECT t.*, u.full_name as employee_name, 
          t.stay_days, t.purpose_of_travel as places_to_visit, 
          COALESCE(arr.flight_details) as flight_details, 
          COALESCE(arr.reach_time) as reach_time, 
          COALESCE(arr.arrival_date) as arrival_date, 
          COALESCE(arr.arrival_time) as arrival_time, 
          t.purpose_of_travel as requirements,
          arr.cab_no as cab_id, arr.hotel_id, arr.driver_name, arr.driver_contact, arr.pickup_time, arr.room_no, arr.check_in, arr.check_out, arr.pnr_number,
          h.hotel_name, h.location as hotel_location, h.star_rating,
          c.vehicle_number, c.vehicle_type, c.provider_name as cab_provider,
          (SELECT COUNT(*) FROM itineraries WHERE tourist_id = t.id) as itinerary_count,
          (SELECT GROUP_CONCAT(CONCAT('Day ', day_number, ' | Place: ', place_name, ' | Activity: ', activity, ' | Starting: ', start_time, ' | Ending: ', end_time) ORDER BY day_number, start_time SEPARATOR '<br>') FROM itineraries WHERE tourist_id = t.id) as itinerary_list
          FROM tourist_entries t
          LEFT JOIN users u ON t.employee_id = u.id
          LEFT JOIN arrangements arr ON t.id = arr.tourist_id
          LEFT JOIN hotels h ON arr.hotel_id = h.id
          LEFT JOIN cabs c ON arr.cab_no = c.id
          ORDER BY t.created_at DESC";
$activities = $pdo->query($query)->fetchAll();

require_once '../includes/header.php';
?>

<div class="card border-0 shadow-sm p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0 text-slate-900">Operations Oversight</h4>
            <p class="text-muted small mb-0">Track employee progress and trip fulfillment status</p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Fully Arranged: <?php echo count(array_filter($activities, fn($a) => $a['hotel_id'] && $a['cab_id'])); ?></span>
            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2">In Progress: <?php echo count(array_filter($activities, fn($a) => $a['arrival_date'] && (!$a['hotel_id'] || !$a['cab_id']))); ?></span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle border-top">
            <thead class="bg-light">
                <tr>
                    <th class="small text-uppercase fw-bold text-muted px-3">Traveler / Employee</th>
                    <th class="small text-uppercase fw-bold text-muted px-3">Requirements</th>
                    <th class="small text-uppercase fw-bold text-muted px-3">Arrangements</th>
                    <th class="small text-uppercase fw-bold text-muted px-3">Itinerary</th>
                    <th class="small text-uppercase fw-bold text-muted px-3 text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activities as $row): ?>
                <tr>
                    <td class="px-3">
                        <div class="fw-bold text-dark"><?php echo $row['name']; ?></div>
                        <div class="text-muted" style="font-size: 11px;"><?php echo $row['email']; ?></div>
                        <div class="mt-2">
                            <span class="badge bg-light text-dark border fw-medium small">
                                <i class="fas fa-user-tie me-1 text-primary"></i> <?php echo $row['employee_name'] ?: 'Unassigned'; ?>
                            </span>
                        </div>


                        
                    </td>
                    <td class="px-3">
                        <?php if ($row['arrival_date']): ?>
                            <div class="small">
                                <div class="mb-1"><i class="fas fa-users me-1 text-muted"></i> <strong><?php echo $row['people_count']; ?></strong> Pax | <strong><?php echo $row['stay_days']; ?></strong> Days</div>
                                <div class="text-emerald fw-bold" style="font-size: 11px;"><i class="fas fa-map-marker-alt me-1"></i><?php echo $row['places_to_visit']; ?></div>
                            </div>
                        <?php else: ?>
                            <span class="text-muted italic small">Waiting for tourist input...</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-3">
                        <?php if ($row['hotel_id'] || $row['cab_id']): ?>
                            <div class="small">
                                <?php if ($row['hotel_name']): ?>
                                    <div class="mb-1"><i class="fas fa-hotel me-1 text-emerald"></i> <?php echo $row['hotel_name']; ?></div>
                                <?php endif; ?>
                                <?php if ($row['vehicle_number']): ?>
                                    <div><i class="fas fa-car me-1 text-primary"></i> <?php echo $row['vehicle_number']; ?></div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <?php if ($row['arrival_date']): ?>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending Action</span>
                            <?php else: ?>
                                <span class="text-muted small">---</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td class="px-3">
                        <?php if ($row['itinerary_count'] > 0): ?>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-emerald text-white rounded-pill me-2"><?php echo $row['itinerary_count']; ?></span>
                                <span class="small text-muted">Items set</span>
                            </div>
                        <?php else: ?>
                            <span class="text-muted small">Not started</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 text-center">
                        <button class="btn btn-sm btn-white border shadow-sm px-3" onclick='viewFullDetails(<?php echo json_encode($row); ?>)'>
                            <i class="fas fa-expand-alt text-primary me-1"></i> Full View
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Full Details Modal -->
<div class="modal fade" id="tripDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold m-0"><i class="fas fa-file-invoice text-primary me-2"></i>Full Trip Assignment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modal_content">
                <!-- Content will be injected by JS -->
            </div>
        </div>
    </div>
</div>

<script>
function viewFullDetails(data) {
    let content = `
        <div class="row g-4">
            <div class="col-md-6">
                <div class="p-3 bg-light rounded-3 h-100">
                    <h6 class="fw-bold mb-3 text-uppercase small text-primary border-bottom pb-2">Traveler Profile & Arrival</h6>
                    <table class="table table-sm table-borderless m-0 small">
                        <tr><td class="text-muted">Name:</td><td class="fw-bold">${data.name}</td></tr>
                        <tr><td class="text-muted">Email:</td><td>${data.email}</td></tr>
                        <tr><td class="text-muted">Arrival:</td><td class="fw-bold text-dark">${data.arrival_date || 'N/A'} @ ${data.arrival_time || ''}</td></tr>
                        <tr><td class="text-muted">Pax:</td><td class="fw-bold">${data.people_count || 'N/A'}</td></tr>
                        <tr><td class="text-muted">Flight:</td><td>${data.flight_details || 'N/A'}</td></tr>
                        <tr><td class="text-muted">PNR:</td><td class="fw-bold text-warning">${data.pnr_number || 'N/A'}</td></tr>
                        <tr><td class="text-muted">Reqs:</td><td class="text-wrap">${data.requirements || 'None'}</td></tr>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-primary-subtle rounded-3 h-100">
                    <h6 class="fw-bold mb-3 text-uppercase small text-primary border-bottom pb-2">Assigned Arrangements</h6>
                    <div class="mb-3">
                        <label class="small text-muted d-block">Hotel Allocation</label>
                        <div class="fw-bold">${data.hotel_name || '<span class="text-muted">Not Assigned</span>'}</div>
                        <div class="small">${data.hotel_location || ''} | Room: ${data.room_no || '---'}</div>
                    </div>
                    <div>
                        <label class="small text-muted d-block">Transport Allocation</label>
                        <div class="fw-bold">${data.vehicle_number || '<span class="text-muted">Not Assigned</span>'}</div>
                        <div class="small">${data.vehicle_type || ''} (${data.cab_provider || ''})</div>
                        <div class="small">Driver: ${data.driver_name || '---'} (${data.driver_contact || ''})</div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="p-3 border rounded-3 bg-emerald-subtle">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="w-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold m-0 text-emerald">Itinerary Status</h6>
                                <span class="badge bg-emerald rounded-pill px-4 py-2">Assigned By: ${data.employee_name || 'System'}</span>
                            </div>
                            <p class="small m-0 text-muted mb-2">${data.itinerary_count} items have been scheduled for this trip.</p>
                            <div class="small text-emerald border-top border-emerald-subtle pt-2 mt-2" style="max-height: 150px; overflow-y: auto;">
                                ${data.itinerary_list ? data.itinerary_list : '<span class="text-muted">No places scheduled yet.</span>'}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.getElementById('modal_content').innerHTML = content;
    var myModal = new bootstrap.Modal(document.getElementById('tripDetailsModal'));
    myModal.show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
