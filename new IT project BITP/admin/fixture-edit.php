<?php
/**
 * Riverside FC - Edit Fixture & Stand Capacity
 */

$adminTitle = "Edit Fixture";
require_once __DIR__ . '/../includes/admin-header.php';

$db = getDB();
$fixtureId = (int)($_GET['id'] ?? 0);
$error = null;

if ($fixtureId <= 0) {
    header('Location: ' . ADMIN_URL . '/fixtures.php');
    exit;
}

// Fetch Fixture
$stmt = $db->prepare("SELECT * FROM fixtures WHERE id = ?");
$stmt->execute([$fixtureId]);
$fixture = $stmt->fetch();

if (!$fixture) {
    setFlash('danger', 'Fixture not found.');
    header('Location: ' . ADMIN_URL . '/fixtures.php');
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = "CSRF verification failed.";
    } else {
        $opponent    = trim($_POST['opponent'] ?? '');
        $competition = trim($_POST['competition'] ?? 'Ghana Premier League');
        $matchDate   = trim($_POST['match_date'] ?? '');
        $kickOff     = trim($_POST['kick_off'] ?? '15:00:00');
        $venue       = trim($_POST['venue'] ?? 'Riverside Arena, Accra');
        $status      = trim($_POST['status'] ?? 'upcoming');
        $description = trim($_POST['description'] ?? '');
        $oppLogo     = trim($_POST['opponent_logo'] ?? 'hearts_of_oak.svg');

        $sectionsData = $_POST['sections'] ?? [];

        if (empty($opponent) || empty($matchDate) || empty($kickOff)) {
            $error = "Please fill in all required fields.";
        } else {
            $db->beginTransaction();
            try {
                // Update Fixture
                $fStmt = $db->prepare("
                    UPDATE fixtures 
                    SET opponent = ?, opponent_logo = ?, competition = ?, match_date = ?, 
                        kick_off = ?, venue = ?, status = ?, description = ?
                    WHERE id = ?
                ");
                $fStmt->execute([$opponent, $oppLogo, $competition, $matchDate, $kickOff, $venue, $status, $description, $fixtureId]);

                // Update Existing Sections
                $secUpdateStmt = $db->prepare("
                    UPDATE ticket_types
                    SET name = ?, description = ?, gate = ?, price = ?, capacity = ?, remaining = ?
                    WHERE id = ? AND fixture_id = ?
                ");

                foreach ($sectionsData as $sId => $s) {
                    $sName  = trim($s['name'] ?? '');
                    $sDesc  = trim($s['description'] ?? '');
                    $sGate  = trim($s['gate'] ?? 'Gate A');
                    $sPrice = (float)($s['price'] ?? 0);
                    $sCap   = (int)($s['capacity'] ?? 0);
                    $sRem   = (int)($s['remaining'] ?? $sCap);
                    if ($sRem > $sCap) $sRem = $sCap;

                    $secUpdateStmt->execute([$sName, $sDesc, $sGate, $sPrice, $sCap, $sRem, $sId, $fixtureId]);
                }

                // Check if new section was added
                $newSecName = trim($_POST['new_sec_name'] ?? '');
                $newSecPrice = (float)($_POST['new_sec_price'] ?? 0);
                $newSecCap = (int)($_POST['new_sec_capacity'] ?? 0);
                $newSecGate = trim($_POST['new_sec_gate'] ?? 'Gate A');
                $newSecDesc = trim($_POST['new_sec_desc'] ?? '');

                if (!empty($newSecName) && $newSecCap > 0) {
                    $insertNew = $db->prepare("
                        INSERT INTO ticket_types (fixture_id, name, description, gate, price, capacity, remaining)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insertNew->execute([$fixtureId, $newSecName, $newSecDesc, $newSecGate, $newSecPrice, $newSecCap, $newSecCap]);
                }

                $db->commit();
                setFlash('success', 'Fixture and stand details updated successfully!');
                header('Location: ' . ADMIN_URL . '/fixtures.php');
                exit;

            } catch (Exception $e) {
                $db->rollBack();
                $error = "Error updating fixture: " . $e->getMessage();
            }
        }
    }
}

// Fetch Sections
$secStmt = $db->prepare("SELECT * FROM ticket_types WHERE fixture_id = ? ORDER BY price DESC");
$secStmt->execute([$fixtureId]);
$sections = $secStmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
    <div>
        <h1 class="h2 fw-bold brand-font text-dark mb-1">Edit Fixture: vs <?= h($fixture['opponent']) ?></h1>
        <p class="text-muted small mb-0">Modify match schedule, status, and stand capacities.</p>
    </div>
    <a href="<?= ADMIN_URL ?>/fixtures.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back to Fixtures
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4">
        <i class="fas fa-exclamation-triangle me-2 fs-5"></i>
        <div><?= h($error) ?></div>
    </div>
<?php endif; ?>

<form method="POST" action="">
    <?= csrfField() ?>
    
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card rfc-card p-4 shadow-sm mb-4">
                <h5 class="fw-bold brand-font mb-3 text-dark">
                    <i class="fas fa-info-circle text-primary me-2"></i> Match Details
                </h5>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Opponent Name *</label>
                        <input type="text" name="opponent" class="form-control" required value="<?= h($fixture['opponent']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Competition *</label>
                        <select name="competition" class="form-select">
                            <option value="Ghana Premier League" <?= $fixture['competition'] === 'Ghana Premier League' ? 'selected' : '' ?>>Ghana Premier League</option>
                            <option value="MTN FA Cup" <?= $fixture['competition'] === 'MTN FA Cup' ? 'selected' : '' ?>>MTN FA Cup</option>
                            <option value="CAF Champions League" <?= $fixture['competition'] === 'CAF Champions League' ? 'selected' : '' ?>>CAF Champions League</option>
                            <option value="Club Friendly" <?= $fixture['competition'] === 'Club Friendly' ? 'selected' : '' ?>>Club Friendly</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Opponent Badge</label>
                        <select name="opponent_logo" class="form-select">
                            <option value="hearts_of_oak.svg" <?= $fixture['opponent_logo'] === 'hearts_of_oak.svg' ? 'selected' : '' ?>>Hearts of Oak</option>
                            <option value="kotoko.svg" <?= $fixture['opponent_logo'] === 'kotoko.svg' ? 'selected' : '' ?>>Asante Kotoko</option>
                            <option value="medeama.svg" <?= $fixture['opponent_logo'] === 'medeama.svg' ? 'selected' : '' ?>>Medeama SC</option>
                            <option value="dreams.svg" <?= $fixture['opponent_logo'] === 'dreams.svg' ? 'selected' : '' ?>>Dreams FC</option>
                            <option value="olympics.svg" <?= $fixture['opponent_logo'] === 'olympics.svg' ? 'selected' : '' ?>>Great Olympics</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Match Date *</label>
                        <input type="date" name="match_date" class="form-control" required value="<?= h($fixture['match_date']) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Kick-Off *</label>
                        <input type="time" name="kick_off" class="form-control" required value="<?= h(substr($fixture['kick_off'], 0, 5)) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Fixture Status *</label>
                        <select name="status" class="form-select">
                            <option value="upcoming" <?= $fixture['status'] === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            <option value="completed" <?= $fixture['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $fixture['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Venue *</label>
                        <input type="text" name="venue" class="form-control" required value="<?= h($fixture['venue']) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= h($fixture['description']) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card rfc-card p-4 shadow-sm mb-4">
                <h5 class="fw-bold brand-font mb-3 text-dark">
                    <i class="fas fa-layer-group text-primary me-2"></i> Stand Capacities &amp; Pricing (GH₵)
                </h5>

                <div class="d-flex flex-column gap-3 mb-4">
                    <?php foreach ($sections as $s): ?>
                        <div class="border rounded-3 p-3 bg-light">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Stand Name</label>
                                    <input type="text" name="sections[<?= $s['id'] ?>][name]" class="form-control form-control-sm" value="<?= h($s['name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Turnstile Gate</label>
                                    <input type="text" name="sections[<?= $s['id'] ?>][gate]" class="form-control form-control-sm" value="<?= h($s['gate']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Price (GH₵)</label>
                                    <input type="number" step="0.5" name="sections[<?= $s['id'] ?>][price]" class="form-control form-control-sm" value="<?= $s['price'] ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Total Capacity</label>
                                    <input type="number" name="sections[<?= $s['id'] ?>][capacity]" class="form-control form-control-sm" value="<?= $s['capacity'] ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Remaining Seats</label>
                                    <input type="number" name="sections[<?= $s['id'] ?>][remaining]" class="form-control form-control-sm" value="<?= $s['remaining'] ?>" required>
                                </div>
                                <div class="col-12">
                                    <input type="text" name="sections[<?= $s['id'] ?>][description]" class="form-control form-control-sm text-muted" value="<?= h($s['description']) ?>">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Add Extra Stand Section -->
                <div class="p-3 border rounded-3 bg-white mb-3">
                    <h6 class="fw-bold small mb-2 text-dark"><i class="fas fa-plus-circle text-success me-1"></i> Add Another Section (Optional)</h6>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <input type="text" name="new_sec_name" class="form-control form-control-sm" placeholder="Section Name">
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="new_sec_gate" class="form-control form-control-sm" placeholder="Gate (e.g. Gate F)">
                        </div>
                        <div class="col-md-6">
                            <input type="number" step="0.5" name="new_sec_price" class="form-control form-control-sm" placeholder="Price (GH₵)">
                        </div>
                        <div class="col-md-6">
                            <input type="number" name="new_sec_capacity" class="form-control form-control-sm" placeholder="Capacity">
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-top">
                    <button type="submit" class="btn btn-rfc-primary w-100 py-3 fs-5 shadow">
                        <i class="fas fa-save me-2"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
