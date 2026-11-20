<?php
/**
 * Riverside FC - Authentication & Authorization Helpers
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Check if any user is currently logged in
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Check if the logged-in user is an administrator
 */
function isAdmin(): bool {
    return isLoggedIn() && (($_SESSION['user_role'] ?? '') === 'admin');
}

/**
 * Get current user data from session
 */
function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? 'Supporter',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'supporter',
        'phone' => $_SESSION['user_phone'] ?? '',
    ];
}

/**
 * Guard for pages requiring login
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        setFlash('warning', 'Please sign in or create an account to continue.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Guard for admin pages
 */
function requireAdmin(): void {
    if (!isAdmin()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        setFlash('danger', 'Access restricted. Administrator privileges required.');
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

/**
 * Set user session data upon login
 */
function loginUser(array $user): void {
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['user_phone'] = $user['phone'] ?? '';
    // Regenerate session id for security
    session_regenerate_id(true);
}

/**
 * Log out user and destroy session
 */
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
