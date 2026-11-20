<?php
/**
 * Riverside FC - Web Database Setup & System Health Installer
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$message = null;
$error = null;
$configSaved = false;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_config' || $action === 'run_seed') {
        $driver = $_POST['driver'] ?? 'mysql';
        $host   = trim($_POST['host'] ?? '127.0.0.1');
        $port   = trim($_POST['port'] ?? '3306');
        $name   = trim($_POST['name'] ?? 'riverside_fc');
        $user   = trim($_POST['user'] ?? 'root');
        $pass   = $_POST['pass'] ?? '';

        // Save credentials file
        $configData = [
            'driver' => $driver,
            'host'   => $host,
            'port'   => $port,
            'name'   => $name,
            'user'   => $user,
            'pass'   => $pass,
        ];
        
        file_put_contents(__DIR__ . '/config/db_credentials.json', json_encode($configData, JSON_PRETTY_PRINT));
        $configSaved = true;

        if ($action === 'run_seed') {
            try {
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ];

                if ($driver === 'mysql') {
                    // Try connecting to server first to create DB if needed
                    $serverDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                    $serverPdo = new PDO($serverDsn, $user, $pass, $options);
                    $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

                    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
                    $pdo = new PDO($dsn, $user, $pass, $options);

                    // Import schema.sql
                    $sqlFile = __DIR__ . '/database/schema.sql';
                    if (file_exists($sqlFile)) {
                        $sql = file_get_contents($sqlFile);
                        $pdo->exec($sql);
                    }
                    $message = "MySQL database `{$name}` created, tables initialized, and seed data loaded successfully!";
                } else {
                    // SQLite setup
                    $sqlitePath = __DIR__ . '/database/riverside_fc.sqlite';
                    $pdo = new PDO("sqlite:" . $sqlitePath, null, null, $options);
                    $pdo->exec("PRAGMA foreign_keys = ON;");
                    require_once __DIR__ . '/database/seed.php';
                    seedDatabase($pdo, 'sqlite');
                    $message = "SQLite database initialized and seeded successfully!";
                }
            } catch (Exception $e) {
                $error = "Setup Error: " . $e->getMessage();
            }
        } else {
            $message = "Database configuration saved successfully!";
        }
    }
}

// Check current connection
$status = 'unknown';
$connectionError = null;
$stats = [];

try {
    require_once __DIR__ . '/config/database.php';
    $db = getDB();
    $status = 'connected';
    
    // Check tables
    $userCount = $db->query("SELECT COUNT(*) as cnt FROM users")->fetch()['cnt'] ?? 0;
    $fixtureCount = $db->query("SELECT COUNT(*) as cnt FROM fixtures")->fetch()['cnt'] ?? 0;
    $bookingCount = $db->query("SELECT COUNT(*) as cnt FROM bookings")->fetch()['cnt'] ?? 0;
    $ticketCount = $db->query("SELECT COUNT(*) as cnt FROM tickets")->fetch()['cnt'] ?? 0;

    $stats = [
        'users' => $userCount,
        'fixtures' => $fixtureCount,
        'bookings' => $bookingCount,
        'tickets' => $ticketCount,
    ];
} catch (Exception $e) {
    $status = 'failed';
    $connectionError = $e->getMessage();
}

$pageTitle = "System & Database Setup";
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center py-4">
    <div class="col-lg-10">
        <div class="card rfc-card shadow-sm border-0 mb-4">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom mb-4">
                    <div>
                        <h2 class="fw-bold mb-1 brand-font text-primary">
                            <i class="fas fa-database text-info me-2"></i> Riverside FC Database Installer
                        </h2>
                        <p class="text-muted mb-0 small">
                            Configure MySQL / MariaDB connection parameters, run schema migrations, and seed sample fixtures.
                        </p>
                    </div>
                    <div>
                        <?php if ($status === 'connected'): ?>
                            <span class="badge bg-success fs-6 px-3 py-2 rounded-pill">
                                <i class="fas fa-check-circle me-1"></i> Database Online &amp; Ready
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger fs-6 px-3 py-2 rounded-pill">
                                <i class="fas fa-exclamation-triangle me-1"></i> Connection Required
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success d-flex align-items-center mb-4">
                        <i class="fas fa-check-circle fs-4 me-2"></i>
                        <div><?= h($message) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($error || $connectionError): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4">
                        <i class="fas fa-exclamation-circle fs-4 me-2"></i>
                        <div><?= h($error ?: $connectionError) ?></div>
                    </div>
                <?php endif; ?>

                <!-- Current Status Card -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-6">
                        <div class="p-3 bg-light rounded-3 text-center border">
                            <div class="text-muted small">Registered Users</div>
                            <div class="fs-4 fw-bold text-dark"><?= $stats['users'] ?? 0 ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-3 bg-light rounded-3 text-center border">
                            <div class="text-muted small">Fixtures Loaded</div>
                            <div class="fs-4 fw-bold text-primary"><?= $stats['fixtures'] ?? 0 ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-3 bg-light rounded-3 text-center border">
                            <div class="text-muted small">Sample Bookings</div>
                            <div class="fs-4 fw-bold text-success"><?= $stats['bookings'] ?? 0 ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-3 bg-light rounded-3 text-center border">
                            <div class="text-muted small">Digital Tickets</div>
                            <div class="fs-4 fw-bold text-info"><?= $stats['tickets'] ?? 0 ?></div>
                        </div>
                    </div>
                </div>

                <!-- Database Configuration Form -->
                <form method="POST" action="">
                    <h5 class="fw-bold mb-3 brand-font text-dark">
                        <i class="fas fa-sliders-h me-2 text-primary"></i> Connection Settings
                    </h5>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Database Engine</label>
                            <select name="driver" class="form-select">
                                <option value="mysql" <?= DB_DRIVER === 'mysql' ? 'selected' : '' ?>>MySQL / MariaDB (Recommended)</option>
                                <option value="sqlite" <?= DB_DRIVER === 'sqlite' ? 'selected' : '' ?>>SQLite (Zero-Config Standalone)</option>
                            </select>
                            <div class="form-text small">Select MySQL for XAMPP / Laragon, or SQLite for instant local file storage.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Host</label>
                            <input type="text" name="host" class="form-control" value="<?= h(DB_HOST) ?>" required>
                            <div class="form-text small">Typically 127.0.0.1 or localhost</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Port</label>
                            <input type="text" name="port" class="form-control" value="<?= h(DB_PORT) ?>" required>
                            <div class="form-text small">Default MySQL port: 3306</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Database Name</label>
                            <input type="text" name="name" class="form-control" value="<?= h(DB_NAME) ?>" required>
                            <div class="form-text small">Will be auto-created if it does not exist</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Username</label>
                            <input type="text" name="user" class="form-control" value="<?= h(DB_USER) ?>" required>
                            <div class="form-text small">Usually 'root' on local dev</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Password</label>
                            <input type="password" name="pass" class="form-control" value="<?= h(DB_PASS) ?>" placeholder="Leave blank if no password">
                            <div class="form-text small">Blank by default in XAMPP/Laragon</div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                        <button type="submit" name="action" value="run_seed" class="btn btn-rfc-primary px-4 py-2">
                            <i class="fas fa-play me-2"></i> Initialize &amp; Seed Database Now
                        </button>
                        <button type="submit" name="action" value="save_config" class="btn btn-outline-secondary px-3 py-2">
                            <i class="fas fa-save me-2"></i> Save Configuration Only
                        </button>
                        <a href="<?= BASE_URL ?>/" class="btn btn-outline-dark px-3 py-2 ms-auto">
                            <i class="fas fa-home me-2"></i> Go to Homepage
                        </a>
                    </div>
                </form>

                <!-- Default Credentials Card -->
                <div class="mt-5 p-4 bg-light rounded-3 border">
                    <h6 class="fw-bold text-dark mb-2"><i class="fas fa-key me-2 text-warning"></i> Pre-configured Test Accounts:</h6>
                    <div class="row g-3 small">
                        <div class="col-md-6">
                            <div class="card p-3 border h-100">
                                <div class="badge bg-danger mb-2 align-self-start">Administrator Role</div>
                                <div><strong>Email:</strong> <code>admin@riversidefc.com</code></div>
                                <div><strong>Password:</strong> <code>admin123</code></div>
                                <div><strong>Portal:</strong> <a href="<?= BASE_URL ?>/admin/login.php">Admin Dashboard</a></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card p-3 border h-100">
                                <div class="badge bg-primary mb-2 align-self-start">Supporter Role</div>
                                <div><strong>Email:</strong> <code>kwame@example.com</code></div>
                                <div><strong>Password:</strong> <code>user123</code></div>
                                <div><strong>Portal:</strong> <a href="<?= BASE_URL ?>/login.php">Supporter Login</a></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
