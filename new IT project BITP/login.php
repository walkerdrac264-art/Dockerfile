<?php
/**
 * Riverside FC - Supporter & Admin Login
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? ADMIN_URL . '/' : BASE_URL . '/my-tickets.php'));
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = "Session validation failed (CSRF). Please try again.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = "Please enter both email and password.";
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                loginUser($user);
                setFlash('success', "Welcome back, {$user['name']}!");
                
                $redirect = $_SESSION['redirect_after_login'] ?? ($user['role'] === 'admin' ? ADMIN_URL . '/' : BASE_URL . '/my-tickets.php');
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect);
                exit;
            } else {
                $error = "Invalid email or password combination.";
            }
        }
    }
}

$pageTitle = "Supporter Sign In";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card rfc-card p-4 p-md-5 shadow-sm border-0">
                <div class="text-center mb-4">
                    <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="RFC Logo" height="64" class="mb-3">
                    <h3 class="fw-bold brand-font text-dark mb-1">Sign In to Your Account</h3>
                    <p class="text-muted small">Manage your Riverside FC match tickets and booking history.</p>
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
                        <label class="form-label small fw-semibold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" name="email" class="form-control" required placeholder="you@example.com" value="<?= h($_POST['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label small fw-semibold">Password</label>
                            <span class="small text-muted">admin123 / user123</span>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" name="password" class="form-control" required placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-rfc-primary w-100 py-2 fs-6 shadow-sm mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i> Sign In
                    </button>
                </form>

                <div class="text-center small text-muted pt-3 border-top">
                    Don't have a supporter account? 
                    <a href="<?= BASE_URL ?>/register.php" class="fw-bold text-primary">Register here</a>
                </div>

                <!-- Quick Test Credentials Box -->
                <div class="mt-4 p-3 bg-light rounded-3 border small">
                    <div class="fw-bold text-dark mb-1"><i class="fas fa-info-circle text-primary me-1"></i> Quick Test Accounts:</div>
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <span>Supporter: <code>kwame@example.com</code></span>
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="document.querySelector('input[name=email]').value='kwame@example.com'; document.querySelector('input[name=password]').value='user123';">Fill</button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <span>Admin: <code>admin@riversidefc.com</code></span>
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="document.querySelector('input[name=email]').value='admin@riversidefc.com'; document.querySelector('input[name=password]').value='admin123';">Fill</button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
