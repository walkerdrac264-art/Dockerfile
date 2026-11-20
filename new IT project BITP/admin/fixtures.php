<?php
/**
 * Riverside FC - Admin Fixtures Management
 */

$adminTitle = "Fixture Management";
require_once __DIR__ . '/../includes/admin-header.php';

$db = getDB();

// Handle Delete Fixture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_fixture') {
    if (!verifyCsrfToken()) {
        setFlash('danger', 'CSRF validation failed.');
    } else {
        $delId = (int)($_POST['fixture_id'] ?? 0);
        // Check if there are bookings
        $checkStmt = $db->prepare("SELECT COUNT(*) as cnt FROM bookings WHERE fixture_id = ?");
        $checkStmt->execute([$delId]);
        $hasBookings = ($checkStmt->fetch()['cnt'] ?? 0) > 0;

        if ($hasBookings) {
            setFlash('danger', 'Cannot delete fixture because bookings already exist for it. You can change its status to Cancelled or Completed.');
        } else {
            $delStmt = $db->prepare("DELETE FROM fixtures WHERE id = ?");
            $delStmt->execute([$delId]);
            setFlash('success', 'Fixture deleted successfully.');
        }
    }
    header('Location: ' . ADMIN_URL . '/fixtures.php');
    exit;
}

// Fetch all fixtures with aggregated stats
$stmt = $db->query("
    SELECT f.*,
           COUNT(DISTINCT tt.id) as section_count,
           SUM(tt.capacity) as total_capacity,
           SUM(tt.remaining) as total_remaining,
           COALESCE(SUM(b.total_amount), 0) as total_revenue,
           COUNT(DISTINCT b.id) as total_bookings
    FROM fixtures f
    LEFT JOIN ticket_types tt ON f.id = tt.fixture_id
    LEFT JOIN bookings b ON f.id = b.fixture_id AND b.payment_status = 'paid'
    GROUP BY f.id
    ORDER BY f.match_date DESC
");
$fixtures = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
    <div>
        <h1 class="h2 fw-bold brand-font text-dark mb-1">Fixture &amp; Stadium Capacity Management</h1>
        <p class="text-muted small mb-0">Create new home matches, adjust ticket allocations per stand, and manage match schedules.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= ADMIN_URL ?>/fixture-create.php" class="btn btn-rfc-primary btn-sm">
            <i class="fas fa-plus me-1"></i> Add New Home Fixture
        </a>
    </div>
</div>

<div class="card rfc-card shadow-sm border-0">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>Fixture / Opponent</th>
                        <th>Competition</th>
                        <th>Date &amp; Kick-Off</th>
                        <th>Venue</th>
                        <th>Capacity Status</th>
                        <th>Revenue (GH₵)</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fixtures)): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">No fixtures found. Click "Add New Home Fixture" to create one.</td></tr>
                    <?php else: ?>
                        <?php foreach ($fixtures as $f): 
                            $rem = (int)($f['total_remaining'] ?? 0);
                            $cap = (int)($f['total_capacity'] ?? 0);
                            $oppLogo = !empty($f['opponent_logo']) ? $f['opponent_logo'] : 'hearts_of_oak.svg';
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= BASE_URL ?>/assets/images/<?= h($oppLogo) ?>" alt="Opponent" height="36" class="rounded">
                                        <div>
                                            <strong class="text-dark fs-6"><?= CLUB_SHORT ?> vs <?= h($f['opponent']) ?></strong>
                                            <div class="small text-muted"><?= $f['section_count'] ?> stands configured</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border small"><?= h($f['competition']) ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= formatDate($f['match_date']) ?></div>
                                    <div class="small text-muted"><?= formatKickOff($f['kick_off']) ?></div>
                                </td>
                                <td class="small text-muted"><?= h($f['venue']) ?></td>
                                <td>
                                    <div class="small mb-1">
                                        <strong><?= number_format($rem) ?></strong> / <?= number_format($cap) ?> remaining
                                    </div>
                                    <div class="capacity-meter" style="width: 120px;">
                                        <?php $pct = $cap > 0 ? round(($rem / $cap) * 100) : 0; ?>
                                        <div class="capacity-meter-bar <?= $rem > 0 ? 'bg-primary' : 'bg-danger' ?>" style="width: <?= $pct ?>%;"></div>
                                    </div>
                                </td>
                                <td>
                                    <strong class="text-success"><?= formatMoney($f['total_revenue']) ?></strong>
                                    <div class="small text-muted"><?= $f['total_bookings'] ?> bookings</div>
                                </td>
                                <td>
                                    <?php if ($f['status'] === 'upcoming'): ?>
                                        <span class="badge bg-success">Upcoming</span>
                                    <?php elseif ($f['status'] === 'completed'): ?>
                                        <span class="badge bg-secondary">Completed</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Cancelled</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="<?= ADMIN_URL ?>/fixture-edit.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Fixture & Stands">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <?php if ($f['total_bookings'] == 0): ?>
                                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this fixture?');">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="delete_fixture">
                                                <input type="hidden" name="fixture_id" value="<?= $f['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Fixture">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
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
