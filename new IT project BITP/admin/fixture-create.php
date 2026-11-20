<?php
/**
 * Riverside FC - Create New Fixture & Stand Allocations
 */

$adminTitle = "Add New Fixture";
require_once __DIR__ . '/../includes/admin-header.php';

$db = getDB();
$error = null;

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

        $sections    = $_POST['sections'] ?? [];

        if (empty($opponent) || empty($matchDate) || empty($kickOff)) {
            $error = "Please fill in all required fixture fields.";
        } elseif (empty($sections)) {
            $error = "Please configure at least one stadium ticket section.";
        } else {
            $db->beginTransaction();
            try {
                // Insert Fixture
                $fStmt = $db->prepare("
                    INSERT INTO fixtures (opponent, opponent_logo, competition, match_date, kick_off, venue, status, description)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $fStmt->execute([$opponent, $oppLogo, $competition, $matchDate, $kickOff, $venue, $status, $description]);
                $fixtureId = $db->lastInsertId();

                // Insert Sections
                $sStmt = $db->prepare("
                    INSERT INTO ticket_types (fixture_id, name, description, gate, price, capacity, remaining)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                foreach ($sections as $sec) {
                    $secName  = trim($sec['name'] ?? '');
                    $secDesc  = trim($sec['description'] ?? '');
                    $secGate  = trim($sec['gate'] ?? 'Gate A');
                    $secPrice = (float)($sec['price'] ?? 0);
                    $secCap   = (int)($sec['capacity'] ?? 0);

                    if (!empty($secName) && $secCap > 0) {
                        $sStmt->execute([$fixtureId, $secName, $secDesc, $secGate, $secPrice, $secCap, $secCap]);
                    }
                }

                $db->commit();
                setFlash('success', "Fixture vs {$opponent} successfully created with stadium stand allocations!");
                header('Location: ' . ADMIN_URL . '/fixtures.php');
                exit;

            } catch (Exception $e) {
                $db->rollBack();
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
    <div>
        <h1 class="h2 fw-bold brand-font text-dark mb-1">Add New Home Fixture</h1>
        <p class="text-muted small mb-0">Schedule an upcoming match at Riverside Arena and set stand capacities &amp; prices.</p>
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
        <!-- Fixture Details (Left Column) -->
        <div class="col-lg-6">
            <div class="card rfc-card p-4 shadow-sm mb-4">
                <h5 class="fw-bold brand-font mb-3 text-dark">
                    <i class="fas fa-futbol text-primary me-2"></i> Match Information
                </h5>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Opponent Name *</label>
                        <input type="text" name="opponent" class="form-control" required placeholder="e.g. Accra Hearts of Oak" value="<?= h($_POST['opponent'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Competition *</label>
                        <select name="competition" class="form-select">
                            <option value="Ghana Premier League">Ghana Premier League</option>
                            <option value="MTN FA Cup">MTN FA Cup</option>
                            <option value="CAF Champions League">CAF Champions League</option>
                            <option value="Club Friendly">Club Friendly</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Opponent Crest Badge</label>
                        <select name="opponent_logo" class="form-select">
                            <option value="hearts_of_oak.svg">Hearts of Oak</option>
                            <option value="kotoko.svg">Asante Kotoko</option>
                            <option value="medeama.svg">Medeama SC</option>
                            <option value="dreams.svg">Dreams FC</option>
                            <option value="olympics.svg">Great Olympics</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Match Date *</label>
                        <input type="date" name="match_date" class="form-control" required value="<?= h($_POST['match_date'] ?? date('Y-m-d', strtotime('+7 days'))) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Kick-Off Time *</label>
                        <input type="time" name="kick_off" class="form-control" required value="<?= h($_POST['kick_off'] ?? '15:00') ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Venue *</label>
                        <input type="text" name="venue" class="form-control" required value="Riverside Arena, Accra">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Match Preview / Slogan</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Exciting match preview for supporters..."><?= h($_POST['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stand Allocations & Pricing (Right Column) -->
        <div class="col-lg-6">
            <div class="card rfc-card p-4 shadow-sm mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold brand-font mb-0 text-dark">
                        <i class="fas fa-layer-group text-primary me-2"></i> Stadium Stands &amp; Pricing (GH₵)
                    </h5>
                    <span class="badge bg-primary text-uppercase"><?= CURRENCY_SYMBOL ?></span>
                </div>

                <p class="small text-muted mb-3">Configure capacity and ticket price per section. Pre-filled with Riverside Arena standards.</p>

                <div id="stands-container" class="d-flex flex-column gap-3">
                    <!-- Stand 1: VIP -->
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Stand Name</label>
                                <input type="text" name="sections[0][name]" class="form-control form-control-sm" value="VIP Executive Lounge" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Turnstile Gate</label>
                                <input type="text" name="sections[0][gate]" class="form-control form-control-sm" value="Gate A - VIP">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Price (GH₵)</label>
                                <input type="number" step="0.5" name="sections[0][price]" class="form-control form-control-sm" value="150.00" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Capacity</label>
                                <input type="number" name="sections[0][capacity]" class="form-control form-control-sm" value="200" required>
                            </div>
                            <div class="col-12">
                                <input type="text" name="sections[0][description]" class="form-control form-control-sm text-muted" value="Padded luxury seats, lounge hospitality & refreshments">
                            </div>
                        </div>
                    </div>

                    <!-- Stand 2: Main Grandstand -->
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Stand Name</label>
                                <input type="text" name="sections[1][name]" class="form-control form-control-sm" value="Main Grandstand" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Turnstile Gate</label>
                                <input type="text" name="sections[1][gate]" class="form-control form-control-sm" value="Gate B - West">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Price (GH₵)</label>
                                <input type="number" step="0.5" name="sections[1][price]" class="form-control form-control-sm" value="80.00" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Capacity</label>
                                <input type="number" name="sections[1][capacity]" class="form-control form-control-sm" value="3000" required>
                            </div>
                            <div class="col-12">
                                <input type="text" name="sections[1][description]" class="form-control form-control-sm text-muted" value="Covered central seating with premium elevated pitch view">
                            </div>
                        </div>
                    </div>

                    <!-- Stand 3: Popular Stand -->
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Stand Name</label>
                                <input type="text" name="sections[2][name]" class="form-control form-control-sm" value="Popular Stand (East)" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Turnstile Gate</label>
                                <input type="text" name="sections[2][gate]" class="form-control form-control-sm" value="Gate C - East">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Price (GH₵)</label>
                                <input type="number" step="0.5" name="sections[2][price]" class="form-control form-control-sm" value="40.00" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Capacity</label>
                                <input type="number" name="sections[2][capacity]" class="form-control form-control-sm" value="8000" required>
                            </div>
                            <div class="col-12">
                                <input type="text" name="sections[2][description]" class="form-control form-control-sm text-muted" value="Atmospheric covered stand directly opposite players tunnel">
                            </div>
                        </div>
                    </div>

                    <!-- Stand 4: North Terrace (Ultras) -->
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Stand Name</label>
                                <input type="text" name="sections[3][name]" class="form-control form-control-sm" value="North Terrace (Ultras Stand)" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Turnstile Gate</label>
                                <input type="text" name="sections[3][gate]" class="form-control form-control-sm" value="Gate D - North">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Price (GH₵)</label>
                                <input type="number" step="0.5" name="sections[3][price]" class="form-control form-control-sm" value="30.00" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Capacity</label>
                                <input type="number" name="sections[3][capacity]" class="form-control form-control-sm" value="5000" required>
                            </div>
                            <div class="col-12">
                                <input type="text" name="sections[3][description]" class="form-control form-control-sm text-muted" value="Active singing and drumming section behind home goal">
                            </div>
                        </div>
                    </div>

                    <!-- Stand 5: Family Enclosure -->
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Stand Name</label>
                                <input type="text" name="sections[4][name]" class="form-control form-control-sm" value="Riverside Family Enclosure" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Turnstile Gate</label>
                                <input type="text" name="sections[4][gate]" class="form-control form-control-sm" value="Gate E - South">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Price (GH₵)</label>
                                <input type="number" step="0.5" name="sections[4][price]" class="form-control form-control-sm" value="50.00" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Capacity</label>
                                <input type="number" name="sections[4][capacity]" class="form-control form-control-sm" value="1500" required>
                            </div>
                            <div class="col-12">
                                <input type="text" name="sections[4][description]" class="form-control form-control-sm text-muted" value="Alcohol-free family section with junior fan amenities">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-rfc-primary w-100 py-3 fs-5 shadow">
                        <i class="fas fa-check-circle me-2"></i> Save &amp; Publish Home Fixture
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
