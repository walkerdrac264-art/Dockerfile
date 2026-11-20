<?php
/**
 * Riverside FC - Admin Portal Login
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (isAdmin()) {
    header('Location: ' . ADMIN_URL . '/');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = "CSRF verification failed.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            loginUser($user);
            setFlash('success', "Welcome to the Administration Console, {$user['name']}!");
            header('Location: ' . ADMIN_URL . '/');
            exit;
        } else {
            $error = "Invalid administrator credentials or access restricted.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal Sign In | <?= CLUB_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/logo.svg">
</head>
<body class="bg-dark d-flex align-items-center justify-content-center" style="min-height: 100vh;">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card p-4 p-md-5 rounded-4 shadow-lg border-0 bg-white">
                <div class="text-center mb-4">
                    <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="RFC Logo" height="70" class="mb-3">
                    <h4 class="fw-bold brand-font text-dark mb-1">Club Admin Console</h4>
                    <span class="badge bg-danger text-uppercase px-3 py-1">Authorized Staff Only</span>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <div><?= h($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Admin Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user-shield text-muted"></i></span>
                            <input type="email" name="email" class="form-control" required placeholder="admin@riversidefc.com" value="<?= h($_POST['email'] ?? 'admin@riversidefc.com') ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key text-muted"></i></span>
                            <input type="password" name="password" class="form-control" required placeholder="admin123" value="admin123">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-dark w-100 py-2 fs-6 fw-bold shadow-sm mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i> Access Dashboard
                    </button>
                </form>

                <div class="text-center small text-muted pt-3 border-top">
                    <a href="<?= BASE_URL ?>/" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i> Return to Public Site
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
