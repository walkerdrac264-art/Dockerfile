<?php
/**
 * Riverside FC - Admin Bookings Management
 */

$adminTitle = "All Bookings";
require_once __DIR__ . '/../includes/admin-header.php';

$db = getDB();

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$sql = "
    SELECT b.*, f.opponent, f.match_date, f.kick_off,
           COUNT(t.id) as ticket_count
    FROM bookings b
    JOIN fixtures f ON b.fixture_id = f.id
    LEFT JOIN tickets t ON b.id = t.booking_id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (b.booking_reference LIKE ? OR b.customer_name LIKE ? OR b.customer_email LIKE ? OR f.opponent LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($statusFilter)) {
    $sql .= " AND b.payment_status = ?";
    $params[] = $statusFilter;
}

$sql .= " GROUP BY b.id ORDER BY b.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
    <div>
        <h1 class="h2 fw-bold brand-font text-dark mb-1">Ticket Bookings &amp; Orders</h1>
        <p class="text-muted small mb-0">Search, inspect supporter orders, verify payment details, and check generated digital passes.</p>
    </div>
</div>

<!-- Search & Filters -->
<div class="card rfc-card p-3 mb-4 shadow-sm">
    <form method="GET" action="" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search reference, supporter, email, opponent..." value="<?= h($search) ?>">
            </div>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All Payment Statuses</option>
                <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>>Paid (Confirmed)</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Payment</option>
                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-dark px-3">Filter Orders</button>
            <?php if (!empty($search) || !empty($statusFilter)): ?>
                <a href="<?= ADMIN_URL ?>/bookings.php" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Bookings Table -->
<div class="card rfc-card shadow-sm border-0">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>Booking Ref</th>
                        <th>Supporter Name</th>
                        <th>Match Fixture</th>
                        <th>Tickets</th>
                        <th>Total Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr><td colspan="9" class="text-center py-5 text-muted">No bookings match the search criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td>
                                    <strong class="font-monospace text-primary"><?= h($b['booking_reference']) ?></strong>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= h($b['customer_name']) ?></div>
                                    <div class="small text-muted"><?= h($b['customer_email']) ?></div>
                                    <div class="small text-muted"><?= h($b['customer_phone']) ?></div>
                                </td>
                                <td>
                                    <strong class="text-dark">vs <?= h($b['opponent']) ?></strong>
                                    <div class="small text-muted"><?= formatDate($b['match_date']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= $b['ticket_count'] ?> passes</span>
                                </td>
                                <td>
                                    <strong class="text-success"><?= formatMoney($b['total_amount']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border small text-uppercase">
                                        <?= h(str_replace('_', ' ', $b['payment_method'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($b['payment_status'] === 'paid'): ?>
                                        <span class="badge bg-success">Paid</span>
                                    <?php elseif ($b['payment_status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Cancelled</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?= formatDate($b['created_at'], 'd M Y, H:i') ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= ADMIN_URL ?>/booking-detail.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
