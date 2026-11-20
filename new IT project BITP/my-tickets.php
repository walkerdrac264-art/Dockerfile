<?php
/**
 * Riverside FC - Supporter "My Tickets" Portal
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/qrcode.php';

$db = getDB();
$currentUser = currentUser();

$guestLookupRef = trim($_GET['ref'] ?? '');
$guestLookupEmail = trim($_GET['email'] ?? '');

$tickets = [];

if ($currentUser) {
    // Fetch all tickets belonging to logged-in user
    $stmt = $db->prepare("
        SELECT t.*, 
               tt.name as section_name, tt.gate, tt.price,
               b.booking_reference, b.customer_name, b.payment_status,
               f.opponent, f.opponent_logo, f.competition, f.match_date, f.kick_off, f.venue
        FROM tickets t
        JOIN ticket_types tt ON t.ticket_type_id = tt.id
        JOIN bookings b ON t.booking_id = b.id
        JOIN fixtures f ON b.fixture_id = f.id
        WHERE b.user_id = ? OR b.customer_email = ?
        ORDER BY f.match_date ASC, t.id ASC
    ");
    $stmt->execute([$currentUser['id'], $currentUser['email']]);
    $tickets = $stmt->fetchAll();
} elseif (!empty($guestLookupRef) || !empty($guestLookupEmail)) {
    // Guest Lookup by Booking Reference or Email
    $sql = "
        SELECT t.*, 
               tt.name as section_name, tt.gate, tt.price,
               b.booking_reference, b.customer_name, b.payment_status,
               f.opponent, f.opponent_logo, f.competition, f.match_date, f.kick_off, f.venue
        FROM tickets t
        JOIN ticket_types tt ON t.ticket_type_id = tt.id
        JOIN bookings b ON t.booking_id = b.id
        JOIN fixtures f ON b.fixture_id = f.id
        WHERE 1=1
    ";
    $params = [];
    if (!empty($guestLookupRef)) {
        $sql .= " AND b.booking_reference = ?";
        $params[] = $guestLookupRef;
    }
    if (!empty($guestLookupEmail)) {
        $sql .= " AND b.customer_email = ?";
        $params[] = $guestLookupEmail;
    }
    $sql .= " ORDER BY f.match_date ASC, t.id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
}

$pageTitle = "My Match Tickets";
require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-dark text-white py-4 border-bottom border-secondary mb-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="h2 fw-bold brand-font mb-1 text-white">
                    <i class="fas fa-ticket-alt text-info me-2"></i> My Match Tickets
                </h1>
                <p class="text-secondary mb-0 small">
                    Access, view, and print your digital matchday passes for Riverside FC fixtures.
                </p>
            </div>
            <?php if (!$currentUser): ?>
                <div>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                        <i class="fas fa-sign-in-alt me-1"></i> Supporter Login
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container pb-5">
    <?php if (!$currentUser && empty($guestLookupRef) && empty($guestLookupEmail)): ?>
        <!-- Guest Ticket Lookup Banner -->
        <div class="row justify-content-center mb-5">
            <div class="col-md-8 col-lg-6">
                <div class="card rfc-card p-4 shadow-sm border-0">
                    <h4 class="fw-bold brand-font text-dark mb-2 text-center">
                        <i class="fas fa-search text-primary me-2"></i> Find Your Tickets
                    </h4>
                    <p class="small text-muted text-center mb-4">
                        Purchased as a guest? Enter your Booking Reference or the email address used during purchase.
                    </p>

                    <form method="GET" action="">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Booking Reference</label>
                            <input type="text" name="ref" class="form-control" placeholder="e.g. RFC-2026-A109B">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Or Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="e.g. kwame@example.com">
                        </div>
                        <button type="submit" class="btn btn-rfc-primary w-100 py-2">
                            <i class="fas fa-search me-1"></i> Retrieve Tickets
                        </button>
                    </form>

                    <div class="text-center mt-3 pt-3 border-top small text-muted">
                        Already have an account? <a href="<?= BASE_URL ?>/login.php" class="fw-bold text-primary">Log in here</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tickets Listing -->
    <?php if (!empty($tickets)): ?>
        <div class="row g-4">
            <?php foreach ($tickets as $tkt): 
                $isUpcoming = strtotime($tkt['match_date']) >= strtotime('today');
                $oppLogo = !empty($tkt['opponent_logo']) ? $tkt['opponent_logo'] : 'hearts_of_oak.svg';
            ?>
                <div class="col-lg-6">
                    <div class="card rfc-card h-100 p-4 shadow-sm border">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="competition-tag"><?= h($tkt['competition']) ?></span>
                            <?php if ($tkt['status'] === 'valid'): ?>
                                <span class="badge bg-success rounded-pill px-3 py-1">
                                    <i class="fas fa-check-circle me-1"></i> Active Pass
                                </span>
                            <?php elseif ($tkt['status'] === 'used'): ?>
                                <span class="badge bg-secondary rounded-pill px-3 py-1">
                                    <i class="fas fa-check-double me-1"></i> Checked-In
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger rounded-pill px-3 py-1">Cancelled</span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex align-items-center gap-3 mb-3">
                            <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="RFC" height="42">
                            <div>
                                <h5 class="fw-bold brand-font mb-0 text-dark">
                                    <?= CLUB_SHORT ?> vs <?= h($tkt['opponent']) ?>
                                </h5>
                                <div class="small text-muted">
                                    <?= formatDate($tkt['match_date'], 'l, d M Y') ?> • <?= formatKickOff($tkt['kick_off']) ?>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <div class="row g-2 small">
                                <div class="col-6">
                                    <span class="text-muted d-block">Stand:</span>
                                    <strong class="text-dark"><?= h($tkt['section_name']) ?></strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Gate:</span>
                                    <span class="badge bg-primary px-2 py-1"><?= h($tkt['gate']) ?></span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Holder:</span>
                                    <span class="text-dark"><?= h($tkt['customer_name']) ?></span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Price:</span>
                                    <strong class="text-dark"><?= formatMoney($tkt['price']) ?></strong>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                            <div>
                                <span class="small text-muted d-block font-monospace">Ticket Code:</span>
                                <strong class="text-primary font-monospace"><?= h($tkt['ticket_code']) ?></strong>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="<?= BASE_URL ?>/ticket.php?code=<?= urlencode($tkt['ticket_code']) ?>" class="btn btn-rfc-primary btn-sm px-3 py-2 rounded-pill">
                                    <i class="fas fa-qrcode me-1"></i> View QR Pass
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($currentUser || !empty($guestLookupRef) || !empty($guestLookupEmail)): ?>
        <div class="text-center py-5">
            <div class="p-5 bg-white rounded-4 border">
                <i class="fas fa-ticket-alt fs-1 text-muted mb-3"></i>
                <h4 class="fw-bold">No Match Tickets Found</h4>
                <p class="text-muted">You do not have any tickets under this account or query.</p>
                <a href="<?= BASE_URL ?>/fixtures.php" class="btn btn-rfc-primary rounded-pill px-4">Browse Upcoming Fixtures</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
