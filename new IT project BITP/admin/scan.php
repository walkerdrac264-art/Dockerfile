<?php
/**
 * Riverside FC - Gate QR Ticket Scanner & Turnstile Validator
 */

$adminTitle = "Gate QR Scanner";
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/qrcode.php';

$db = getDB();

$scannedCode = trim($_REQUEST['code'] ?? '');
$action = $_POST['action'] ?? '';
$validationResult = null;

// Handle Check-in Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'admit') {
    if (!verifyCsrfToken()) {
        setFlash('danger', 'CSRF validation failed.');
    } else {
        $tktId = (int)($_POST['ticket_id'] ?? 0);
        $checkStmt = $db->prepare("SELECT * FROM tickets WHERE id = ?");
        $checkStmt->execute([$tktId]);
        $t = $checkStmt->fetch();

        if ($t && $t['status'] === 'valid') {
            $upd = $db->prepare("UPDATE tickets SET status = 'used', used_at = NOW() WHERE id = ?");
            $upd->execute([$tktId]);
            setFlash('success', "PASS VALIDATED! Turnstile opened. Supporter admitted at Gate.");
            $scannedCode = $t['ticket_code'];
        } else {
            setFlash('danger', "Cannot admit: Ticket already used or invalid.");
        }
    }
}

// Inspect Ticket if code is submitted
if (!empty($scannedCode)) {
    // Clean string in case raw QR payload was scanned
    if (str_contains($scannedCode, '|')) {
        $parts = explode('|', $scannedCode);
        $scannedCode = $parts[0];
    }

    $tktStmt = $db->prepare("
        SELECT t.*, 
               tt.name as section_name, tt.gate, tt.price,
               b.booking_reference, b.customer_name, b.customer_email, b.payment_status,
               f.id as fixture_id, f.opponent, f.match_date, f.kick_off, f.venue
        FROM tickets t
        JOIN ticket_types tt ON t.ticket_type_id = tt.id
        JOIN bookings b ON t.booking_id = b.id
        JOIN fixtures f ON b.fixture_id = f.id
        WHERE t.ticket_code = ?
    ");
    $tktStmt->execute([$scannedCode]);
    $ticket = $tktStmt->fetch();

    if ($ticket) {
        $validationResult = [
            'ticket' => $ticket,
            'is_valid' => ($ticket['status'] === 'valid'),
            'is_used'  => ($ticket['status'] === 'used'),
            'is_cancelled' => ($ticket['status'] === 'cancelled')
        ];
    } else {
        $validationResult = [
            'not_found' => true,
            'code' => $scannedCode
        ];
    }
}

// Fetch recent check-ins today
$recentAdmissionsStmt = $db->query("
    SELECT t.ticket_code, t.used_at, tt.name as section_name, tt.gate, b.customer_name, f.opponent
    FROM tickets t
    JOIN ticket_types tt ON t.ticket_type_id = tt.id
    JOIN bookings b ON t.booking_id = b.id
    JOIN fixtures f ON b.fixture_id = f.id
    WHERE t.status = 'used' AND t.used_at IS NOT NULL
    ORDER BY t.used_at DESC
    LIMIT 10
");
$recentAdmissions = $recentAdmissionsStmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
    <div>
        <h1 class="h2 fw-bold brand-font text-dark mb-1">Gate QR Ticket Scanner &amp; Validator</h1>
        <p class="text-muted small mb-0">Turnstile admission terminal. Scan supporter mobile QR codes or enter ticket code.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Scanner & Search Input -->
    <div class="col-lg-6">
        <div class="card rfc-card p-4 shadow-sm mb-4">
            <h5 class="fw-bold brand-font mb-3 text-dark">
                <i class="fas fa-barcode text-primary me-2"></i> Scan or Enter Ticket Code
            </h5>

            <!-- Manual / Barcode Gun Form -->
            <form method="GET" action="" class="mb-4">
                <label class="form-label small fw-semibold">Turnstile Code Input</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-white"><i class="fas fa-qrcode text-primary"></i></span>
                    <input type="text" name="code" id="scanner-input" class="form-control font-monospace fw-bold" 
                           placeholder="e.g. RFC-TKT-KM01A" value="<?= h($scannedCode) ?>" autofocus>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="fas fa-check me-1"></i> Verify
                    </button>
                </div>
                <div class="form-text small">
                    <i class="fas fa-keyboard me-1"></i> Compatible with USB/Bluetooth 2D barcode scanners. Auto-submits on enter.
                </div>
            </form>

            <!-- Quick Test Code Buttons -->
            <div class="p-3 bg-light rounded-3 border mb-3">
                <span class="small fw-bold text-dark d-block mb-2">Test with Seeded Tickets:</span>
                <div class="d-flex flex-wrap gap-2">
                    <a href="?code=RFC-TKT-KM01A" class="badge bg-primary text-decoration-none p-2 font-monospace">RFC-TKT-KM01A (Valid)</a>
                    <a href="?code=RFC-TKT-AB01X" class="badge bg-primary text-decoration-none p-2 font-monospace">RFC-TKT-AB01X (Valid)</a>
                    <a href="?code=RFC-TKT-KA01Y" class="badge bg-primary text-decoration-none p-2 font-monospace">RFC-TKT-KA01Y (Valid)</a>
                    <a href="?code=RFC-TKT-INVALID" class="badge bg-secondary text-decoration-none p-2 font-monospace">Invalid Code</a>
                </div>
            </div>

            <!-- Camera Scanner Accordion -->
            <div class="accordion" id="cameraAccordion">
                <div class="accordion-item rounded-3 overflow-hidden border">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#cameraCollapse">
                            <i class="fas fa-camera text-primary me-2"></i> Use Device Camera QR Scanner
                        </button>
                    </h2>
                    <div id="cameraCollapse" class="accordion-collapse collapse">
                        <div class="accordion-body text-center p-3">
                            <div id="qr-reader" style="width: 100%; max-width: 400px; margin: 0 auto;"></div>
                            <div class="small text-muted mt-2">Position supporter ticket QR code in front of camera.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Verification Result Screen -->
    <div class="col-lg-6">
        <?php if ($validationResult): ?>
            <?php if (!empty($validationResult['not_found'])): ?>
                <!-- INVALID / NOT FOUND SCREEN -->
                <div class="card border-danger border-2 rounded-4 shadow p-4 text-center bg-danger-subtle">
                    <div class="display-3 text-danger mb-2"><i class="fas fa-times-circle"></i></div>
                    <h2 class="fw-bold text-danger brand-font mb-1">INVALID TICKET</h2>
                    <p class="text-danger mb-3">No record found for code: <strong class="font-monospace"><?= h($validationResult['code']) ?></strong></p>
                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-ban me-1"></i> <strong>ENTRY DENIED.</strong> This is not an official Riverside FC match ticket.
                    </div>
                </div>

            <?php else: 
                $t = $validationResult['ticket'];
            ?>
                <?php if ($validationResult['is_valid']): ?>
                    <!-- VALID TICKET - READY FOR ADMISSION -->
                    <div class="card border-success border-2 rounded-4 shadow p-4 text-center bg-success-subtle">
                        <div class="display-3 text-success mb-2"><i class="fas fa-check-circle"></i></div>
                        <h2 class="fw-bold text-success brand-font mb-1">VALID TICKET - ADMIT SUPPORTER</h2>
                        <span class="badge bg-success fs-6 px-3 py-2 rounded-pill mb-3">100% VERIFIED PASS</span>

                        <div class="card bg-white p-3 rounded-3 text-start mb-3 border">
                            <div class="row g-2 small">
                                <div class="col-6">
                                    <span class="text-muted d-block">TICKET CODE:</span>
                                    <strong class="font-monospace text-primary fs-6"><?= h($t['ticket_code']) ?></strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">HOLDER NAME:</span>
                                    <strong class="text-dark fs-6"><?= h($t['customer_name']) ?></strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">STAND / ENCLOSURE:</span>
                                    <strong class="text-dark"><?= h($t['section_name']) ?></strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">ASSIGNED GATE:</span>
                                    <strong class="badge bg-primary fs-6"><?= h($t['gate']) ?></strong>
                                </div>
                                <div class="col-12 mt-2 pt-2 border-top">
                                    <span class="text-muted d-block">MATCH:</span>
                                    <strong class="text-dark">Riverside FC vs <?= h($t['opponent']) ?></strong> (<?= formatDate($t['match_date']) ?>)
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="admit">
                            <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                            <button type="submit" class="btn btn-success btn-lg w-100 py-3 fs-5 shadow">
                                <i class="fas fa-door-open me-2"></i> ADMIT &amp; RECORD GATE CHECK-IN
                            </button>
                        </form>
                    </div>

                <?php elseif ($validationResult['is_used']): ?>
                    <!-- ALREADY USED ALERT -->
                    <div class="card border-warning border-2 rounded-4 shadow p-4 text-center bg-warning-subtle">
                        <div class="display-3 text-warning mb-2"><i class="fas fa-exclamation-triangle"></i></div>
                        <h2 class="fw-bold text-dark brand-font mb-1">ALREADY SCANNED / USED</h2>
                        <span class="badge bg-danger fs-6 px-3 py-2 rounded-pill mb-3">ENTRY DENIED - PASS DUPLICATED</span>

                        <div class="alert alert-danger text-start small mb-3">
                            <i class="fas fa-shield-alt me-1"></i> This ticket was already redeemed at: 
                            <strong><?= formatDate($t['used_at'], 'd M Y, H:i:s') ?></strong>.
                            Do not admit again (anti-passback protection).
                        </div>

                        <div class="card bg-white p-3 rounded-3 text-start border small">
                            <div><strong>Holder:</strong> <?= h($t['customer_name']) ?></div>
                            <div><strong>Code:</strong> <span class="font-monospace"><?= h($t['ticket_code']) ?></span></div>
                            <div><strong>Stand:</strong> <?= h($t['section_name']) ?> (<?= h($t['gate']) ?>)</div>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- CANCELLED TICKET -->
                    <div class="card border-danger border-2 rounded-4 shadow p-4 text-center bg-danger-subtle">
                        <div class="display-3 text-danger mb-2"><i class="fas fa-ban"></i></div>
                        <h2 class="fw-bold text-danger brand-font mb-1">TICKET CANCELLED</h2>
                        <p class="text-muted small">This booking was cancelled or refunded. Entry not permitted.</p>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        <?php else: ?>
            <div class="card rfc-card p-5 text-center shadow-sm h-100 d-flex justify-content-center align-items-center">
                <i class="fas fa-qrcode fs-1 text-muted mb-3 opacity-50"></i>
                <h5 class="fw-bold text-dark">Turnstile Ready for Scanning</h5>
                <p class="text-muted small mb-0" style="max-width: 350px;">
                    Scan the supporter's mobile QR ticket or enter the ticket code to verify access at the gate turnstile.
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Live Turnstile Check-Ins Log -->
<div class="card rfc-card p-4 shadow-sm mt-4">
    <h5 class="fw-bold brand-font text-dark mb-3">
        <i class="fas fa-list-check text-primary me-2"></i> Recent Gate Turnstile Admissions
    </h5>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Time Scanned</th>
                    <th>Ticket Code</th>
                    <th>Supporter Name</th>
                    <th>Gate</th>
                    <th>Stand</th>
                    <th>Match</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentAdmissions)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No gate check-ins logged yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentAdmissions as $ra): ?>
                        <tr>
                            <td><strong class="text-dark"><?= formatDate($ra['used_at'], 'H:i:s') ?></strong></td>
                            <td><span class="font-monospace text-primary"><?= h($ra['ticket_code']) ?></span></td>
                            <td><?= h($ra['customer_name']) ?></td>
                            <td><span class="badge bg-secondary-subtle text-secondary border"><?= h($ra['gate']) ?></span></td>
                            <td><?= h($ra['section_name']) ?></td>
                            <td>vs <?= h($ra['opponent']) ?></td>
                            <td><span class="badge bg-success">Admitted</span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- HTML5 QR Code Library for Camera Scanner -->
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const collapseEl = document.getElementById('cameraCollapse');
    let html5QrcodeScanner = null;

    if (collapseEl) {
        collapseEl.addEventListener('shown.bs.collapse', () => {
            if (!html5QrcodeScanner) {
                html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", { fps: 10, qrbox: 250 });
                html5QrcodeScanner.render((decodedText) => {
                    html5QrcodeScanner.clear();
                    window.location.href = "?code=" + encodeURIComponent(decodedText);
                });
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
