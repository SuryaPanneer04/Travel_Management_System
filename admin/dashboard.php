<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('admin');

$pageTitle = 'Admin Dashboard';

// Fetch stats
$totalTourists = $pdo->query("SELECT COUNT(*) FROM tourist_entries")->fetchColumn();
$totalHotels = $pdo->query("SELECT COUNT(*) FROM hotels")->fetchColumn();
$totalCabs = $pdo->query("SELECT COUNT(*) FROM cabs")->fetchColumn();
$recentEntries = $pdo->query("SELECT * FROM tourist_entries ORDER BY created_at DESC LIMIT 5")->fetchAll();

require_once '../includes/header.php';
?>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">Total Tourists</h6>
                    <h2 class="fw-bold mb-0"><?php echo $totalTourists; ?></h2>
                </div>
                <div class="bg-primary text-white p-3 rounded-3">
                    <i class="fas fa-users fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">Total Hotels</h6>
                    <h2 class="fw-bold mb-0"><?php echo $totalHotels; ?></h2>
                </div>
                <div class="bg-emerald text-white p-3 rounded-3">
                    <i class="fas fa-hotel fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">Total Cabs</h6>
                    <h2 class="fw-bold mb-0"><?php echo $totalCabs; ?></h2>
                </div>
                <div class="bg-info text-white p-3 rounded-3">
                    <i class="fas fa-car fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold m-0">Recent Travel Requests</h5>
        <div class="d-flex gap-2">
            <a href="trip_oversight.php" class="btn btn-primary btn-sm rounded-pill px-3">Trip Oversight</a>
            <a href="employee_activities.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">View All</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Stay Days</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentEntries as $row): ?>
                <tr>
                    <td class="fw-medium"><?php echo $row['name']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['stay_days']; ?> Days</td>
                    <td>
                        <?php if ($row['status']): ?>
                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">Verified</span>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning px-3 py-2 rounded-pill">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recentEntries)): ?>
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">No recent entries found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
