<?php
/**
 * Riverside FC - Booking Confirmation & E-Ticket Preview
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/qrcode.php';

$db = getDB();
$bookingRef = trim($_GET['ref'] ?? '');

if (empty($bookingRef)) {
    setFlash('warning', 'Booking reference not specified.');
    header('Location: ' . BASE_URL . '/');
    exit;
}

// Fetch Booking
$bookStmt = $db->prepare("
    SELECT b.*, f.opponent, f.opponent_logo, f.competition, f.match_date, f.kick_off, f.venue
    FROM bookings b
    JOIN fixtures f ON b.fixture_id = f.id
    WHERE b.booking_reference = ?
");
$bookStmt->execute([$bookingRef]);
$booking = $bookStmt->fetch();

if (!$booking) {
    setFlash('danger', 'Booking record not found.');
    header('Location: ' . BASE_URL . '/');
    exit;
}

// Fetch Tickets generated for this booking
$tktStmt = $db->prepare("
    SELECT t.*, tt.name as section_name, tt.gate, tt.price
    FROM tickets t
    JOIN ticket_types tt ON t.ticket_type_id = tt.id
    WHERE t.booking_id = ?
    ORDER BY t.id ASC
");
$tktStmt->execute([$booking['id']]);
$tickets = $tktStmt->fetchAll();

$pageTitle = "Order Confirmed - " . $booking['booking_reference'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <!-- Success Celebration Alert -->
    <div class="text-center mb-5 no-print">
        <div class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-circle mb-3 shadow" style="width: 72px; height: 72px;">
            <i class="fas fa-check fs-2"></i>
        </div>
        <h1 class="display-6 fw-bold brand-font text-dark mb-2">Booking Confirmed!</h1>
        <p class="text-muted lead mb-2">
            Thank you, <strong><?= h($booking['customer_name']) ?></strong>! Your digital match passes are ready.
        </p>
        <div class="d-inline-block px-3 py-1 bg-light border rounded-pill small font-monospace">
            Booking Reference: <strong><?= h($booking['booking_reference']) ?></strong>
        </div>
    </div>

    <!-- Booking Summary Card -->
    <div class="card rfc-card p-4 mb-5 shadow-sm no-print">
        <div class="row g-4 align-items-center">
            <div class="col-md-7">
                <span class="badge bg-primary text-uppercase mb-2"><?= h($booking['competition']) ?></span>
                <h3 class="fw-bold brand-font text-dark mb-1">
                    <?= CLUB_SHORT ?> vs <?= h($booking['opponent']) ?>
                </h3>
                <div class="d-flex flex-wrap gap-3 small text-muted mt-2">
                    <div><i class="fas fa-calendar-day text-info me-1"></i> <?= formatDate($booking['match_date'], 'l, d F Y') ?></div>
                    <div><i class="fas fa-clock text-info me-1"></i> <?= formatKickOff($booking['kick_off']) ?></div>
                    <div><i class="fas fa-map-marker-alt text-danger me-1"></i> <?= h($booking['venue']) ?></div>
                </div>
            </div>

            <div class="col-md-5 text-md-end border-start-md">
                <div class="small text-muted">Total Paid (Ghana Cedis):</div>
                <div class="fs-2 fw-bold text-success brand-font mb-2"><?= formatMoney($booking['total_amount']) ?></div>
                <div class="d-flex justify-content-md-end gap-2">
                    <button type="button" class="btn btn-outline-dark btn-sm rounded-pill px-3 btn-print-ticket">
                        <i class="fas fa-print me-1"></i> Print All Tickets
                    </button>
                    <a href="<?= BASE_URL ?>/my-tickets.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        <i class="fas fa-ticket-alt me-1"></i> My Tickets
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Digital Ticket Passes Grid -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold brand-font text-dark mb-0">
            <i class="fas fa-qrcode text-primary me-2"></i> Official Digital Match Passes (<?= count($tickets) ?>)
        </h4>
        <span class="small text-muted no-print"><i class="fas fa-mobile-alt me-1 text-primary"></i> Scan QR at Gate</span>
    </div>

    <div class="row g-4">
        <?php foreach ($tickets as $index => $tkt): ?>
            <div class="col-lg-6">
                <div class="digital-ticket-pass mb-3">
                    <div class="ticket-notch-left"></div>
                    <div class="ticket-notch-right"></div>

                    <!-- Ticket Header -->
                    <div class="ticket-header">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="RFC" height="32">
                                <span class="fw-bold text-white small brand-font"><?= CLUB_NAME ?></span>
                            </div>
                            <span class="badge bg-warning text-dark fw-bold text-uppercase" style="font-size: 0.65rem;">
                                <?= $tkt['status'] === 'valid' ? 'OFFICIAL PASS' : strtoupper($tkt['status']) ?>
                            </span>
                        </div>
                        <h4 class="fw-bold text-white mb-1 brand-font">
                            <?= CLUB_SHORT ?> vs <?= h($booking['opponent']) ?>
                        </h4>
                        <div class="small text-light opacity-80">
                            <?= formatDate($booking['match_date'], 'D, d M Y') ?> • Kick-Off: <?= formatKickOff($booking['kick_off']) ?>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="ticket-divider"></div>

                    <!-- Ticket Body -->
                    <div class="ticket-body">
                        <div class="row g-3 align-items-center">
                            <!-- Left Ticket Info -->
                            <div class="col-sm-7">
                                <div class="mb-2">
                                    <span class="text-muted small d-block">Stand / Enclosure:</span>
                                    <span class="fw-bold text-dark fs-6"><?= h($tkt['section_name']) ?></span>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <span class="text-muted small d-block">Turnstile:</span>
                                        <span class="badge bg-primary fs-6 px-2 py-1"><?= h($tkt['gate']) ?></span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted small d-block">Price:</span>
                                        <span class="fw-bold text-dark"><?= formatMoney($tkt['price']) ?></span>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <span class="text-muted small d-block">Ticket Holder:</span>
                                    <span class="fw-semibold text-dark"><?= h($booking['customer_name']) ?></span>
                                </div>
                                <div>
                                    <span class="text-muted small d-block">Ticket Code:</span>
                                    <span class="ticket-barcode small"><?= h($tkt['ticket_code']) ?></span>
                                </div>
                            </div>

                            <!-- Right QR Code -->
                            <div class="col-sm-5 text-center">
                                <?= RiversideQR::renderSvg($tkt['ticket_code'], 160) ?>
                                <div class="small text-muted mt-2" style="font-size: 0.7rem;">
                                    <i class="fas fa-barcode me-1"></i> Scan at Turnstile Gate
                                </div>
                            </div>
                        </div>

                        <!-- Action Bar -->
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-3 no-print">
                            <span class="small text-muted">Pass #<?= $index + 1 ?> of <?= count($tickets) ?></span>
                            <div class="d-flex gap-2">
                                <a href="<?= BASE_URL ?>/ticket.php?code=<?= urlencode($tkt['ticket_code']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    <i class="fas fa-external-link-alt me-1"></i> View Full Pass
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Matchday Entry Instructions -->
    <div class="card bg-light border p-4 rounded-4 mt-4 no-print">
        <h5 class="fw-bold brand-font text-dark mb-2"><i class="fas fa-info-circle text-primary me-2"></i> Matchday Entry Guidelines</h5>
        <ul class="small text-muted mb-0 ps-3">
            <li>Have your QR code open on your phone or printed on paper before arriving at the turnstile gate.</li>
            <li>Proceed to the entrance gate indicated on your ticket pass (<strong><?= h($tickets[0]['gate'] ?? 'Gate A') ?></strong>).</li>
            <li>Turnstiles open 2 hours before kick-off. Please allow extra time for security bag checks.</li>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
