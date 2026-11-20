<?php
/**
 * Riverside FC - Checkout & Payment Simulation (Ghana Cedis)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$db = getDB();
$currentUser = currentUser();

// 1. Process Form Submission (Payment Completion)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_payment') {
    if (!verifyCsrfToken()) {
        setFlash('danger', 'Security validation failed (CSRF). Please try again.');
        header('Location: ' . BASE_URL . '/fixtures.php');
        exit;
    }

    $fixtureId    = (int)($_POST['fixture_id'] ?? 0);
    $ticketTypeId = (int)($_POST['ticket_type_id'] ?? 0);
    $quantity     = (int)($_POST['quantity'] ?? 1);
    
    $name         = trim($_POST['customer_name'] ?? '');
    $email        = trim($_POST['customer_email'] ?? '');
    $phone        = trim($_POST['customer_phone'] ?? '');
    $paymentMethod= trim($_POST['payment_method'] ?? 'momo_mtn');
    $momoNumber   = trim($_POST['momo_number'] ?? '');

    // Validation
    if ($quantity < 1 || $quantity > 10) {
        setFlash('danger', 'Invalid ticket quantity (minimum 1, maximum 10).');
        header("Location: " . BASE_URL . "/fixture.php?id={$fixtureId}");
        exit;
    }

    if (empty($name) || empty($email) || empty($phone)) {
        setFlash('danger', 'Please provide your full name, email, and phone number.');
        header("Location: " . BASE_URL . "/fixture.php?id={$fixtureId}");
        exit;
    }

    // Begin Transaction to prevent race conditions & overselling
    $db->beginTransaction();
    try {
        // Atomic update on ticket remaining count
        $updateStmt = $db->prepare("
            UPDATE ticket_types 
            SET remaining = remaining - ? 
            WHERE id = ? AND remaining >= ?
        ");
        $updateStmt->execute([$quantity, $ticketTypeId, $quantity]);

        if ($updateStmt->rowCount() === 0) {
            // Not enough tickets remaining!
            $db->rollBack();
            setFlash('danger', 'Sorry, the requested tickets are no longer available in this section. Please select another stand.');
            header("Location: " . BASE_URL . "/fixture.php?id={$fixtureId}");
            exit;
        }

        // Fetch ticket type and fixture info
        $secStmt = $db->prepare("SELECT * FROM ticket_types WHERE id = ?");
        $secStmt->execute([$ticketTypeId]);
        $section = $secStmt->fetch();

        $unitPrice = (float)$section['price'];
        $totalAmount = $unitPrice * $quantity;
        $bookingRef = generateBookingRef();
        $userId = $currentUser ? $currentUser['id'] : null;

        // Insert Booking
        $bookStmt = $db->prepare("
            INSERT INTO bookings (
                booking_reference, user_id, customer_name, customer_email, 
                customer_phone, fixture_id, total_amount, payment_method, 
                payment_status, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'paid', ?)
        ");
        $note = "Simulated payment via " . strtoupper(str_replace('_', ' ', $paymentMethod));
        if (!empty($momoNumber)) {
            $note .= " (MoMo: {$momoNumber})";
        }
        $bookStmt->execute([
            $bookingRef, $userId, $name, $email, 
            $phone, $fixtureId, $totalAmount, $paymentMethod, $note
        ]);
        $bookingId = $db->lastInsertId();

        // Insert Booking Item
        $itemStmt = $db->prepare("
            INSERT INTO booking_items (booking_id, ticket_type_id, quantity, unit_price, subtotal)
            VALUES (?, ?, ?, ?, ?)
        ");
        $itemStmt->execute([$bookingId, $ticketTypeId, $quantity, $unitPrice, $totalAmount]);

        // Generate Individual Tickets with QR Code
        $tktStmt = $db->prepare("
            INSERT INTO tickets (booking_id, ticket_type_id, ticket_code, qr_data, status)
            VALUES (?, ?, ?, ?, 'valid')
        ");

        for ($i = 1; $i <= $quantity; $i++) {
            $tktCode = generateTicketCode();
            $qrData = "{$tktCode}|REF:{$bookingRef}|FIX:{$fixtureId}|STAND:{$section['name']}|HOLDER:{$name}|TKT:{$i}/{$quantity}";
            $tktStmt->execute([$bookingId, $ticketTypeId, $tktCode, $qrData]);
        }

        $db->commit();

        setFlash('success', 'Booking confirmed! Your official digital tickets have been generated.');
        header('Location: ' . BASE_URL . '/confirmation.php?ref=' . urlencode($bookingRef));
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        setFlash('danger', 'Booking failed due to a system error: ' . $e->getMessage());
        header("Location: " . BASE_URL . "/fixture.php?id={$fixtureId}");
        exit;
    }
}

// 2. Initial Display of Checkout Page
$fixtureId    = (int)($_REQUEST['fixture_id'] ?? 0);
$ticketTypeId = (int)($_REQUEST['ticket_type_id'] ?? 0);
$quantity     = (int)($_REQUEST['quantity'] ?? 1);

if ($quantity < 1) $quantity = 1;
if ($quantity > 10) $quantity = 10;

// Fetch Fixture and Section info
$fixStmt = $db->prepare("SELECT * FROM fixtures WHERE id = ?");
$fixStmt->execute([$fixtureId]);
$fixture = $fixStmt->fetch();

$secStmt = $db->prepare("SELECT * FROM ticket_types WHERE id = ? AND fixture_id = ?");
$secStmt->execute([$ticketTypeId, $fixtureId]);
$section = $secStmt->fetch();

if (!$fixture || !$section) {
    setFlash('danger', 'Invalid match fixture or ticket stand selected.');
    header('Location: ' . BASE_URL . '/fixtures.php');
    exit;
}

if ($section['remaining'] < $quantity) {
    setFlash('warning', 'Only ' . $section['remaining'] . ' tickets remaining in ' . $section['name'] . '. Please adjust your quantity.');
    header("Location: " . BASE_URL . "/fixture.php?id={$fixtureId}");
    exit;
}

$unitPrice = (float)$section['price'];
$totalPayable = $unitPrice * $quantity;

$pageTitle = "Checkout & Payment | " . CLUB_SHORT;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold brand-font text-dark mb-1">
                        <i class="fas fa-lock text-primary me-2"></i> Secure Checkout
                    </h2>
                    <p class="text-muted small mb-0">Confirm your details and receive your instant digital match pass.</p>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>/fixture.php?id=<?= $fixture['id'] ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="fas fa-arrow-left me-1"></i> Change Stand / Quantity
                    </a>
                </div>
            </div>

            <form method="POST" action="<?= BASE_URL ?>/checkout.php" id="checkout-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="process_payment">
                <input type="hidden" name="fixture_id" value="<?= $fixture['id'] ?>">
                <input type="hidden" name="ticket_type_id" value="<?= $section['id'] ?>">
                <input type="hidden" name="quantity" value="<?= $quantity ?>">

                <div class="row g-4">
                    <!-- Left: Supporter Details & Payment Method -->
                    <div class="col-lg-7">
                        
                        <!-- Contact Details Card -->
                        <div class="card rfc-card p-4 mb-4 shadow-sm">
                            <h5 class="fw-bold brand-font mb-3 text-dark">
                                <i class="fas fa-user-circle text-primary me-2"></i> 1. Ticket Holder Information
                            </h5>

                            <?php if (!$currentUser): ?>
                                <div class="alert alert-info py-2 px-3 small rounded-3 d-flex align-items-center mb-3">
                                    <i class="fas fa-info-circle me-2 fs-5"></i>
                                    <div>
                                        Guest checkout enabled. Have an account? 
                                        <a href="<?= BASE_URL ?>/login.php" class="fw-bold text-dark text-decoration-underline">Sign in</a> 
                                        to sync tickets to your profile.
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold small">Full Name (Ticket Holder) *</label>
                                    <input type="text" name="customer_name" class="form-control" required
                                           placeholder="e.g. Kwame Mensah" 
                                           value="<?= h($currentUser['name'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Email Address (for Digital Ticket) *</label>
                                    <input type="email" name="customer_email" class="form-control" required
                                           placeholder="e.g. kwame@example.com" 
                                           value="<?= h($currentUser['email'] ?? '') ?>">
                                    <div class="form-text small">Your QR e-ticket will be sent here.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Ghana Phone / MoMo Number *</label>
                                    <input type="text" name="customer_phone" class="form-control" required
                                           placeholder="e.g. 024 123 4567" 
                                           value="<?= h($currentUser['phone'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method Card -->
                        <div class="card rfc-card p-4 shadow-sm">
                            <h5 class="fw-bold brand-font mb-3 text-dark">
                                <i class="fas fa-credit-card text-primary me-2"></i> 2. Select Payment Method (Ghana Cedis)
                            </h5>

                            <div class="d-flex flex-column gap-3 mb-3">
                                <!-- MTN MoMo -->
                                <label class="border p-3 rounded-3 d-flex align-items-center justify-content-between cursor-pointer bg-light">
                                    <div class="d-flex align-items-center gap-3">
                                        <input type="radio" name="payment_method" value="momo_mtn" class="form-check-input" checked>
                                        <div>
                                            <div class="fw-bold text-dark">MTN Mobile Money</div>
                                            <div class="small text-muted">Pay directly with your MTN MoMo wallet</div>
                                        </div>
                                    </div>
                                    <span class="badge bg-warning text-dark fw-bold">MTN MoMo</span>
                                </label>

                                <!-- Telecel Cash -->
                                <label class="border p-3 rounded-3 d-flex align-items-center justify-content-between cursor-pointer bg-light">
                                    <div class="d-flex align-items-center gap-3">
                                        <input type="radio" name="payment_method" value="momo_telecel" class="form-check-input">
                                        <div>
                                            <div class="fw-bold text-dark">Telecel Cash</div>
                                            <div class="small text-muted">Instant prompt on Telecel network</div>
                                        </div>
                                    </div>
                                    <span class="badge bg-danger fw-bold">Telecel</span>
                                </label>

                                <!-- Card Payment -->
                                <label class="border p-3 rounded-3 d-flex align-items-center justify-content-between cursor-pointer bg-light">
                                    <div class="d-flex align-items-center gap-3">
                                        <input type="radio" name="payment_method" value="card" class="form-check-input">
                                        <div>
                                            <div class="fw-bold text-dark">Visa / Mastercard (GH₵)</div>
                                            <div class="small text-muted">Local &amp; international bank debit/credit card</div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <span class="badge bg-primary">VISA</span>
                                        <span class="badge bg-secondary">MC</span>
                                    </div>
                                </label>

                                <!-- Box Office Reservation -->
                                <label class="border p-3 rounded-3 d-flex align-items-center justify-content-between cursor-pointer bg-light">
                                    <div class="d-flex align-items-center gap-3">
                                        <input type="radio" name="payment_method" value="box_office" class="form-check-input">
                                        <div>
                                            <div class="fw-bold text-dark">Pay at Stadium Box Office</div>
                                            <div class="small text-muted">Reserve seat online &amp; pay cash at Gate A/B on matchday</div>
                                        </div>
                                    </div>
                                    <span class="badge bg-dark">Cash at Gate</span>
                                </label>
                            </div>

                            <!-- MoMo Interactive Panel -->
                            <div id="momo-details-panel" class="p-3 bg-white border rounded-3 mb-3">
                                <label class="form-label small fw-semibold">Enter Registered Mobile Money Number:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" name="momo_number" class="form-control" placeholder="024XXXXXXX or 050XXXXXXX" value="<?= h($currentUser['phone'] ?? '') ?>">
                                </div>
                                <div class="form-text small text-muted">
                                    <i class="fas fa-info-circle me-1 text-primary"></i> A USSD prompt will be sent to authorize payment of <strong><?= formatMoney($totalPayable) ?></strong>.
                                </div>
                            </div>

                            <!-- Card Interactive Panel -->
                            <div id="card-details-panel" class="p-3 bg-white border rounded-3 mb-3" style="display: none;">
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Card Number</label>
                                        <input type="text" class="form-control" placeholder="4123 •••• •••• 9876">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">Expiry Date</label>
                                        <input type="text" class="form-control" placeholder="MM/YY">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">CVV / CVC</label>
                                        <input type="password" class="form-control" maxlength="4" placeholder="123">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Right: Order Breakdown & Confirm Button -->
                    <div class="col-lg-5">
                        <div class="card rfc-card p-4 shadow-sm sticky-top" style="top: 90px;">
                            <h5 class="fw-bold brand-font pb-2 border-bottom mb-3 text-dark">
                                <i class="fas fa-clipboard-check text-primary me-2"></i> Order Summary
                            </h5>

                            <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                                <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="RFC" height="42">
                                <div>
                                    <div class="fw-bold text-dark"><?= CLUB_SHORT ?> vs <?= h($fixture['opponent']) ?></div>
                                    <div class="small text-muted"><?= formatDate($fixture['match_date']) ?> • <?= formatKickOff($fixture['kick_off']) ?></div>
                                    <div class="small text-primary"><i class="fas fa-map-marker-alt me-1"></i> <?= h($fixture['venue']) ?></div>
                                </div>
                            </div>

                            <div class="d-flex flex-column gap-2 mb-3">
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Stand / Section:</span>
                                    <span class="fw-bold text-dark"><?= h($section['name']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Turnstile Entry:</span>
                                    <span class="badge bg-secondary-subtle text-secondary border"><?= h($section['gate']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Unit Price:</span>
                                    <span class="fw-bold text-dark"><?= formatMoney($unitPrice) ?></span>
                                </div>
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Ticket Quantity:</span>
                                    <span class="fw-bold text-dark"><?= $quantity ?> <?= $quantity > 1 ? 'tickets' : 'ticket' ?></span>
                                </div>
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Processing Fee:</span>
                                    <span class="text-success fw-bold">FREE (GH₵ 0.00)</span>
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <span class="fw-bold fs-6 text-dark">Total Payable:</span>
                                <span class="fs-3 fw-bold text-primary brand-font"><?= formatMoney($totalPayable) ?></span>
                            </div>

                            <button type="submit" id="btn-pay-now" class="btn btn-rfc-gold w-100 py-3 fs-5 shadow">
                                <i class="fas fa-check-circle me-2"></i> Pay &amp; Generate QR Tickets
                            </button>

                            <div class="text-center mt-3 small text-muted">
                                <i class="fas fa-shield-alt text-success me-1"></i> Instant electronic ticket delivery with scannable QR code.
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
