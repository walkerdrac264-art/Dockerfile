<?php
/**
 * Riverside FC - Admin Booking Detail & Cancellation
 */

$adminTitle = "Booking Details";
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/qrcode.php';

$db = getDB();
$bookingId = (int)($_GET['id'] ?? 0);

if ($bookingId <= 0) {
    header('Location: ' . ADMIN_URL . '/bookings.php');
    exit;
}

// Handle Cancel Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    if (!verifyCsrfToken()) {
        setFlash('danger', 'CSRF validation failed.');
    } else {
        $db->beginTransaction();
        try {
            // Fetch booking items to restock inventory
            $itemStmt = $db->prepare("SELECT * FROM booking_items WHERE booking_id = ?");
            $itemStmt->execute([$bookingId]);
            $items = $itemStmt->fetchAll();

            foreach ($items as $item) {
                $restockStmt = $db->prepare("UPDATE ticket_types SET remaining = remaining + ? WHERE id = ?");
                $restockStmt->execute([$item['quantity'], $item['ticket_type_id']]);
            }

            // Update booking status
            $updBook = $db->prepare("UPDATE bookings SET payment_status = 'cancelled' WHERE id = ?");
            $updBook->execute([$bookingId]);

            // Update all tickets to cancelled
            $updTkt = $db->prepare("UPDATE tickets SET status = 'cancelled' WHERE booking_id = ?");
            $updTkt->execute([$bookingId]);

            $db->commit();
            setFlash('success', 'Booking and tickets cancelled. Capacity has been returned to the stadium stand.');
            header("Location: " . ADMIN_URL . "/booking-detail.php?id={$bookingId}");
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            setFlash('danger', 'Error cancelling booking: ' . $e->getMessage());
        }
    }
}

// Fetch Booking
$bookStmt = $db->prepare("
    SELECT b.*, f.opponent, f.match_date, f.kick_off, f.venue, f.competition
    FROM bookings b
    JOIN fixtures f ON b.fixture_id = f.id
    WHERE b.id = ?
");
$bookStmt->execute([$bookingId]);
$booking = $bookStmt->fetch();

if (!$booking) {
    setFlash('danger', 'Booking not found.');
    header('Location: ' . ADMIN_URL . '/bookings.php');
    exit;
}

// Fetch Tickets
$tktStmt = $db->prepare("
    SELECT t.*, tt.name as section_name, tt.gate, tt.price
    FROM tickets t
    JOIN ticket_types tt ON t.ticket_type_id = tt.id
    WHERE t.booking_id = ?
    ORDER BY t.id ASC
");
$tktStmt->execute([$bookingId]);
$tickets = $tktStmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
    <div>
        <h1 class="h2 fw-bold brand-font text-dark mb-1">Booking: <?= h($booking['booking_reference']) ?></h1>
        <p class="text-muted small mb-0">Detailed order breakdown and individual digital ticket passes.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="<?= ADMIN_URL ?>/bookings.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Bookings
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Supporter & Order Info -->
    <div class="col-lg-6">
        <div class="card rfc-card p-4 shadow-sm h-100">
            <h5 class="fw-bold brand-font text-dark mb-3">
                <i class="fas fa-user-circle text-primary me-2"></i> Customer Details
            </h5>
            <table class="table table-sm table-borderless small mb-0">
                <tr>
                    <td class="text-muted" style="width: 140px;">Customer Name:</td>
                    <td><strong class="text-dark fs-6"><?= h($booking['customer_name']) ?></strong></td>
                </tr>
                <tr>
                    <td class="text-muted">Email Address:</td>
                    <td><a href="mailto:<?= h($booking['customer_email']) ?>"><?= h($booking['customer_email']) ?></a></td>
                </tr>
                <tr>
                    <td class="text-muted">Phone / MoMo:</td>
                    <td><strong><?= h($booking['customer_phone']) ?></strong></td>
                </tr>
                <tr>
                    <td class="text-muted">Booking Reference:</td>
                    <td><span class="font-monospace text-primary fw-bold"><?= h($booking['booking_reference']) ?></span></td>
                </tr>
                <tr>
                    <td class="text-muted">Booking Date:</td>
                    <td><?= formatDate($booking['created_at'], 'l, d M Y, H:i:s') ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Payment Method:</td>
                    <td><span class="badge bg-light text-dark border text-uppercase"><?= h(str_replace('_', ' ', $booking['payment_method'])) ?></span></td>
                </tr>
                <tr>
                    <td class="text-muted">Payment Status:</td>
                    <td>
                        <?php if ($booking['payment_status'] === 'paid'): ?>
                            <span class="badge bg-success">Paid (Confirmed)</span>
                        <?php elseif ($booking['payment_status'] === 'pending'): ?>
                            <span class="badge bg-warning text-dark">Pending</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Cancelled</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="text-muted">Total Amount:</td>
                    <td><strong class="text-success fs-5"><?= formatMoney($booking['total_amount']) ?></strong></td>
                </tr>
            </table>

            <?php if ($booking['payment_status'] !== 'cancelled'): ?>
                <hr class="my-3">
                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to cancel this booking? Tickets will be voided and capacity will be returned to the stadium stand.');">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="cancel_booking">
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-ban me-1"></i> Cancel Booking &amp; Restock Seats
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Match Fixture Info -->
    <div class="col-lg-6">
        <div class="card rfc-card p-4 shadow-sm h-100">
            <h5 class="fw-bold brand-font text-dark mb-3">
                <i class="fas fa-futbol text-primary me-2"></i> Match Information
            </h5>
            <div class="p-3 bg-light rounded-3 mb-3 border">
                <span class="badge bg-primary text-uppercase mb-2"><?= h($booking['competition']) ?></span>
                <h4 class="fw-bold text-dark brand-font mb-1">
                    <?= CLUB_SHORT ?> vs <?= h($booking['opponent']) ?>
                </h4>
                <div class="small text-muted mb-2">
                    <i class="fas fa-calendar-day text-info me-1"></i> <?= formatDate($booking['match_date'], 'l, d F Y') ?> • 
                    <i class="fas fa-clock text-info me-1"></i> <?= formatKickOff($booking['kick_off']) ?>
                </div>
                <div class="small text-muted">
                    <i class="fas fa-map-marker-alt text-danger me-1"></i> <?= h($booking['venue']) ?>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between p-3 border rounded-3 bg-white">
                <div>
                    <span class="small text-muted d-block">Public Ticket Link:</span>
                    <a href="<?= BASE_URL ?>/confirmation.php?ref=<?= urlencode($booking['booking_reference']) ?>" target="_blank" class="small fw-bold">
                        View Confirmation Page <i class="fas fa-external-link-alt ms-1"></i>
                    </a>
                </div>
                <span class="badge bg-secondary"><?= count($tickets) ?> Tickets</span>
            </div>
        </div>
    </div>
</div>

<!-- Individual Tickets -->
<div class="card rfc-card p-4 shadow-sm">
    <h5 class="fw-bold brand-font text-dark mb-3">
        <i class="fas fa-qrcode text-primary me-2"></i> Individual Gate Tickets (<?= count($tickets) ?>)
    </h5>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th>Ticket Code</th>
                    <th>Stand / Section</th>
                    <th>Gate</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Scanned At</th>
                    <th>QR Code</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $t): ?>
                    <tr>
                        <td>
                            <strong class="font-monospace text-primary fs-6"><?= h($t['ticket_code']) ?></strong>
                        </td>
                        <td>
                            <span class="fw-semibold text-dark"><?= h($t['section_name']) ?></span>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary border"><?= h($t['gate']) ?></span>
                        </td>
                        <td>
                            <strong class="text-dark"><?= formatMoney($t['price']) ?></strong>
                        </td>
                        <td>
                            <?php if ($t['status'] === 'valid'): ?>
                                <span class="badge bg-success">Valid</span>
                            <?php elseif ($t['status'] === 'used'): ?>
                                <span class="badge bg-secondary">Used (Checked-in)</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Cancelled</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <?= !empty($t['used_at']) ? formatDate($t['used_at'], 'd M Y, H:i') : '<span class="text-muted">Not yet scanned</span>' ?>
                        </td>
                        <td>
                            <?= RiversideQR::renderSvg($t['ticket_code'], 64) ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/ticket.php?code=<?= urlencode($t['ticket_code']) ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                <i class="fas fa-external-link-alt me-1"></i> View Pass
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
