<?php
/**
 * Riverside FC - Fixture Details & Stand / Ticket Selection
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$db = getDB();

$fixtureId = (int)($_GET['id'] ?? 0);
if ($fixtureId <= 0) {
    header('Location: ' . BASE_URL . '/fixtures.php');
    exit;
}

// Fetch Fixture Details
$stmt = $db->prepare("SELECT * FROM fixtures WHERE id = ?");
$stmt->execute([$fixtureId]);
$fixture = $stmt->fetch();

if (!$fixture) {
    setFlash('danger', 'Match fixture not found.');
    header('Location: ' . BASE_URL . '/fixtures.php');
    exit;
}

// Fetch Ticket Types / Sections for this fixture
$typeStmt = $db->prepare("SELECT * FROM ticket_types WHERE fixture_id = ? ORDER BY price DESC");
$typeStmt->execute([$fixtureId]);
$sections = $typeStmt->fetchAll();

$oppLogo = !empty($fixture['opponent_logo']) ? $fixture['opponent_logo'] : 'hearts_of_oak.svg';
$pageTitle = "Tickets: Riverside FC vs " . $fixture['opponent'];
require_once __DIR__ . '/includes/header.php';
?>

<!-- Fixture Header Banner -->
<div class="bg-dark text-white py-5 border-bottom border-secondary position-relative">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2 small">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/" class="text-info text-decoration-none">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/fixtures.php" class="text-info text-decoration-none">Fixtures</a></li>
                        <li class="breadcrumb-item active text-secondary" aria-current="page">vs <?= h($fixture['opponent']) ?></li>
                    </ol>
                </nav>

                <span class="badge bg-primary text-uppercase px-3 py-2 mb-2"><?= h($fixture['competition']) ?></span>
                <h1 class="display-5 fw-bold brand-font text-white mb-2">
                    <?= CLUB_SHORT ?> <span class="text-secondary fw-normal">vs</span> <?= h($fixture['opponent']) ?>
                </h1>

                <div class="d-flex flex-wrap gap-4 text-light small mt-3">
                    <div><i class="fas fa-calendar-day text-warning me-2"></i> <strong><?= formatDate($fixture['match_date'], 'l, d F Y') ?></strong></div>
                    <div><i class="fas fa-clock text-warning me-2"></i> <strong><?= formatKickOff($fixture['kick_off']) ?></strong></div>
                    <div><i class="fas fa-map-marker-alt text-warning me-2"></i> <strong><?= h($fixture['venue']) ?></strong></div>
                </div>

                <?php if (!empty($fixture['description'])): ?>
                    <p class="text-light opacity-75 mt-3 mb-0 small" style="max-width: 650px;">
                        <?= nl2br(h($fixture['description'])) ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="col-lg-4 text-center">
                <div class="p-3 bg-black bg-opacity-40 rounded-4 border border-secondary d-inline-flex align-items-center gap-4">
                    <div>
                        <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="Riverside FC" style="height: 60px;">
                        <div class="small fw-bold mt-1 text-white"><?= CLUB_SHORT ?></div>
                    </div>
                    <div class="vs-badge">VS</div>
                    <div>
                        <img src="<?= BASE_URL ?>/assets/images/<?= h($oppLogo) ?>" alt="<?= h($fixture['opponent']) ?>" style="height: 60px;">
                        <div class="small fw-bold mt-1 text-white"><?= h($fixture['opponent']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <?php if ($fixture['status'] !== 'upcoming'): ?>
        <div class="alert alert-warning p-4 rounded-3 text-center mb-5">
            <i class="fas fa-exclamation-triangle fs-3 me-2"></i>
            <h5 class="fw-bold d-inline-block">This fixture is not currently open for ticket sales.</h5>
            <p class="mb-0 small text-muted">Status: <?= ucfirst($fixture['status']) ?>. Check upcoming fixtures for active matches.</p>
        </div>
    <?php elseif (empty($sections)): ?>
        <div class="alert alert-warning p-4 rounded-3 text-center mb-5">
            <i class="fas fa-info-circle fs-3 me-2"></i>
            <h5 class="fw-bold d-inline-block">Ticket sections are being configured by club administration.</h5>
            <p class="mb-0 small text-muted">Please check back shortly.</p>
        </div>
    <?php else: ?>
        <form method="POST" action="<?= BASE_URL ?>/checkout.php" id="ticket-selection-form">
            <?= csrfField() ?>
            <input type="hidden" name="fixture_id" value="<?= $fixture['id'] ?>">
            <input type="hidden" name="ticket_type_id" id="input-selected-type-id" value="">
            <input type="hidden" name="quantity" id="input-quantity" value="1">

            <div class="row g-4">
                <!-- Stand & Enclosure Selector (Left Column) -->
                <div class="col-lg-8">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold brand-font mb-0 text-dark">
                            <i class="fas fa-layer-group text-primary me-2"></i> 1. Select Stadium Stand / Section
                        </h4>
                        <span class="small text-muted"><i class="fas fa-shield-alt text-success me-1"></i> Official Gate Access</span>
                    </div>

                    <div class="d-flex flex-column gap-3 mb-4">
                        <?php foreach ($sections as $index => $sec): 
                            $isSoldOut = ($sec['remaining'] <= 0);
                            $capStatus = getCapacityStatus((int)$sec['remaining'], (int)$sec['capacity']);
                        ?>
                            <div class="stand-option-card <?= $isSoldOut ? 'opacity-50' : '' ?>"
                                 data-id="<?= $sec['id'] ?>"
                                 data-name="<?= h($sec['name']) ?>"
                                 data-price="<?= $sec['price'] ?>"
                                 data-remaining="<?= $sec['remaining'] ?>"
                                 data-gate="<?= h($sec['gate']) ?>">
                                
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <h5 class="fw-bold mb-0 text-dark"><?= h($sec['name']) ?></h5>
                                            <span class="badge bg-secondary-subtle text-secondary small border">
                                                <i class="fas fa-door-open me-1"></i> <?= h($sec['gate']) ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($sec['description'])): ?>
                                            <p class="small text-muted mb-0 mt-1"><?= h($sec['description']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-end">
                                        <div class="fs-4 fw-bold text-primary brand-font">
                                            <?= formatMoney($sec['price']) ?>
                                        </div>
                                        <span class="small text-muted">per ticket</span>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge <?= $capStatus['badge'] ?> rounded-pill px-2 py-1 small">
                                            <?= $capStatus['text'] ?>
                                        </span>
                                        <span class="small text-muted">
                                            <strong><?= number_format($sec['remaining']) ?></strong> seats remaining
                                        </span>
                                    </div>
                                    <div>
                                        <?php if ($isSoldOut): ?>
                                            <span class="badge bg-danger">Sold Out</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-primary border select-indicator">
                                                <i class="fas fa-check-circle me-1"></i> Click to Select
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Quantity Stepper -->
                    <div class="card rfc-card p-4 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h5 class="fw-bold brand-font mb-1 text-dark">
                                    <i class="fas fa-users text-primary me-2"></i> 2. Number of Tickets
                                </h5>
                                <div class="text-muted small">Max 10 tickets per supporter booking order.</div>
                            </div>
                            <div>
                                <div class="qty-stepper">
                                    <button type="button" class="btn-qty-minus"><i class="fas fa-minus"></i></button>
                                    <input type="text" id="qty-display" value="1" readonly>
                                    <button type="button" class="btn-qty-plus"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary Sticky Sidebar (Right Column) -->
                <div class="col-lg-4">
                    <div class="card rfc-card p-4 shadow sticky-top" style="top: 90px;">
                        <h5 class="fw-bold brand-font pb-2 border-bottom mb-3 text-dark">
                            <i class="fas fa-receipt text-primary me-2"></i> Booking Summary
                        </h5>

                        <div class="small mb-3">
                            <div class="text-muted">Fixture:</div>
                            <div class="fw-bold text-dark fs-6"><?= CLUB_SHORT ?> vs <?= h($fixture['opponent']) ?></div>
                            <div class="text-secondary"><?= formatDate($fixture['match_date']) ?> • <?= formatKickOff($fixture['kick_off']) ?></div>
                            <div class="text-secondary"><?= h($fixture['venue']) ?></div>
                        </div>

                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <div class="d-flex justify-content-between text-muted small mb-1">
                                <span>Selected Stand:</span>
                                <strong id="summary-section-name" class="text-dark">Select a stand above</strong>
                            </div>
                            <div class="d-flex justify-content-between text-muted small mb-1">
                                <span>Unit Price:</span>
                                <strong id="summary-unit-price" class="text-dark">GH₵ 0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between text-muted small mb-1">
                                <span>Quantity:</span>
                                <strong id="summary-qty" class="text-dark">1</strong>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark">Total Payable:</span>
                                <span class="fs-4 fw-bold text-primary brand-font" id="summary-total">GH₵ 0.00</span>
                            </div>
                        </div>

                        <div class="mb-3 small text-muted">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fas fa-check text-success"></i> Instant QR Ticket on checkout
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fas fa-check text-success"></i> MTN MoMo &amp; Telecel Cash supported
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-check text-success"></i> Zero booking fee
                            </div>
                        </div>

                        <button type="submit" id="btn-checkout" class="btn btn-rfc-primary w-100 py-3 fs-6 shadow">
                            <i class="fas fa-ticket-alt me-2"></i> Proceed to Checkout
                        </button>

                        <div class="text-center mt-3 small text-muted">
                            <i class="fas fa-lock me-1"></i> Secure 256-bit SSL encrypted checkout
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
