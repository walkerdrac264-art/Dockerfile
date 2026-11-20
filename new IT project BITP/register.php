<?php
/**
 * Riverside FC - Supporter Registration
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/my-tickets.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = "Session validation failed (CSRF).";
    } else {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            $error = "Please fill in all required fields.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } else {
            $db = getDB();
            $check = $db->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = "An account with this email address already exists. Please log in.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'supporter')");
                $stmt->execute([$name, $email, $phone, $hash]);
                $newId = $db->lastInsertId();

                $newUser = [
                    'id'    => $newId,
                    'name'  => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'role'  => 'supporter',
                ];
                loginUser($newUser);

                setFlash('success', "Welcome to the Riverside FC family, {$name}!");
                header('Location: ' . BASE_URL . '/fixtures.php');
                exit;
            }
        }
    }
}

$pageTitle = "Supporter Registration";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card rfc-card p-4 p-md-5 shadow-sm border-0">
                <div class="text-center mb-4">
                    <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="RFC Logo" height="64" class="mb-3">
                    <h3 class="fw-bold brand-font text-dark mb-1">Create Supporter Account</h3>
                    <p class="text-muted small">Join the Riverians and keep all your match passes in one place.</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center small py-2">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <div><?= h($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Full Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Kwame Mensah" value="<?= h($_POST['name'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email Address *</label>
                        <input type="email" name="email" class="form-control" required placeholder="you@example.com" value="<?= h($_POST['email'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Ghana Phone / MoMo Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="024 123 4567" value="<?= h($_POST['phone'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Password *</label>
                        <input type="password" name="password" class="form-control" required placeholder="At least 6 characters">
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Confirm Password *</label>
                        <input type="password" name="confirm_password" class="form-control" required placeholder="Re-type password">
                    </div>

                    <button type="submit" class="btn btn-rfc-primary w-100 py-2 fs-6 shadow-sm mb-3">
                        <i class="fas fa-user-plus me-2"></i> Create Account
                    </button>
                </form>

                <div class="text-center small text-muted pt-3 border-top">
                    Already have an account? 
                    <a href="<?= BASE_URL ?>/login.php" class="fw-bold text-primary">Sign in</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
