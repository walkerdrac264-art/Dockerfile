<?php
/**
 * Riverside FC - Single Digital QR Ticket Pass
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/qrcode.php';

$db = getDB();
$ticketCode = trim($_GET['code'] ?? '');

if (empty($ticketCode)) {
    setFlash('danger', 'Ticket code required.');
    header('Location: ' . BASE_URL . '/my-tickets.php');
    exit;
}

// Fetch Ticket with Booking & Fixture details
$stmt = $db->prepare("
    SELECT t.*, 
           tt.name as section_name, tt.gate, tt.price,
           b.booking_reference, b.customer_name, b.customer_email, b.payment_status,
           f.id as fixture_id, f.opponent, f.opponent_logo, f.competition, f.match_date, f.kick_off, f.venue
    FROM tickets t
    JOIN ticket_types tt ON t.ticket_type_id = tt.id
    JOIN bookings b ON t.booking_id = b.id
    JOIN fixtures f ON b.fixture_id = f.id
    WHERE t.ticket_code = ?
");
$stmt->execute([$ticketCode]);
$ticket = $stmt->fetch();

if (!$ticket) {
    setFlash('danger', 'Ticket not found.');
    header('Location: ' . BASE_URL . '/');
    exit;
}

$oppLogo = !empty($ticket['opponent_logo']) ? $ticket['opponent_logo'] : 'hearts_of_oak.svg';
$pageTitle = "Match Pass: " . $ticket['ticket_code'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="text-center mb-4 no-print">
        <a href="<?= BASE_URL ?>/my-tickets.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 me-2">
            <i class="fas fa-arrow-left me-1"></i> Back to My Tickets
        </a>
        <button type="button" class="btn btn-rfc-primary btn-sm rounded-pill px-3 btn-print-ticket">
            <i class="fas fa-print me-1"></i> Print / Save Pass
        </button>
    </div>

    <!-- Digital Match Ticket Card -->
    <div class="digital-ticket-pass shadow-lg">
        <div class="ticket-notch-left"></div>
        <div class="ticket-notch-right"></div>

        <!-- Header -->
        <div class="ticket-header p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="RFC" height="38">
                    <div>
                        <div class="fw-bold text-white lh-1 brand-font"><?= CLUB_NAME ?></div>
                        <span class="text-info small" style="font-size: 0.7rem; letter-spacing: 1px;">OFFICIAL MATCHDAY PASS</span>
                    </div>
                </div>
                <div>
                    <?php if ($ticket['status'] === 'valid'): ?>
                        <span class="badge bg-success text-white px-3 py-2 rounded-pill fw-bold">
                            <i class="fas fa-check-circle me-1"></i> VALID • ADMIT ONE
                        </span>
                    <?php elseif ($ticket['status'] === 'used'): ?>
                        <span class="badge bg-secondary text-white px-3 py-2 rounded-pill fw-bold">
                            <i class="fas fa-check-double me-1"></i> CHECKED-IN (USED)
                        </span>
                    <?php else: ?>
                        <span class="badge bg-danger text-white px-3 py-2 rounded-pill fw-bold">
                            <i class="fas fa-times-circle me-1"></i> CANCELLED
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="p-3 bg-black bg-opacity-30 rounded-3 mb-2">
                <div class="text-info small text-uppercase fw-bold"><?= h($ticket['competition']) ?></div>
                <h3 class="fw-bold text-white mb-0 brand-font">
                    <?= CLUB_SHORT ?> vs <?= h($ticket['opponent']) ?>
                </h3>
            </div>

            <div class="d-flex flex-wrap justify-content-between small text-light opacity-90 mt-2">
                <span><i class="fas fa-calendar-day text-warning me-1"></i> <?= formatDate($ticket['match_date'], 'l, d F Y') ?></span>
                <span><i class="fas fa-clock text-warning me-1"></i> <?= formatKickOff($ticket['kick_off']) ?></span>
            </div>
        </div>

        <div class="ticket-divider"></div>

        <!-- Body -->
        <div class="ticket-body p-4">
            <!-- Seat / Stand Details Row -->
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3 border">
                        <span class="text-muted small d-block">STAND / ENCLOSURE</span>
                        <strong class="text-dark fs-5 brand-font"><?= h($ticket['section_name']) ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3 border">
                        <span class="text-muted small d-block">ENTRY TURNSTILE</span>
                        <strong class="text-primary fs-5 brand-font"><?= h($ticket['gate']) ?></strong>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4 small">
                <div class="col-6">
                    <span class="text-muted d-block">TICKET HOLDER:</span>
                    <strong class="text-dark fs-6"><?= h($ticket['customer_name']) ?></strong>
                </div>
                <div class="col-6">
                    <span class="text-muted d-block">PRICE PAID:</span>
                    <strong class="text-dark fs-6"><?= formatMoney($ticket['price']) ?></strong>
                </div>
                <div class="col-6">
                    <span class="text-muted d-block">STADIUM:</span>
                    <span class="text-dark"><?= h($ticket['venue']) ?></span>
                </div>
                <div class="col-6">
                    <span class="text-muted d-block">BOOKING REF:</span>
                    <span class="text-dark font-monospace"><?= h($ticket['booking_reference']) ?></span>
                </div>
            </div>

            <!-- Large Scannable QR Code -->
            <div class="text-center py-3 my-2 border-top border-bottom bg-light rounded-3">
                <div class="small text-uppercase fw-bold text-muted mb-2" style="letter-spacing: 1px;">
                    <i class="fas fa-qrcode me-1 text-primary"></i> Digital Gate Barcode Pass
                </div>
                <?= RiversideQR::renderSvg($ticket['ticket_code'], 220) ?>
                <div class="mt-2 font-monospace fw-bold text-dark fs-5 tracking-wide">
                    <?= h($ticket['ticket_code']) ?>
                </div>
                <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                    Hold screen up to gate turnstile optical scanner at <strong><?= h($ticket['gate']) ?></strong>
                </div>
            </div>

            <!-- Security Footer Note -->
            <div class="small text-muted text-center mt-3" style="font-size: 0.72rem;">
                Official ticket issued by <?= CLUB_NAME ?>. Duplicate transfers strictly prohibited. Gate stewards reserve the right to verify identification.
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
