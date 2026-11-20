<?php
/**
 * Riverside FC - Sign Out
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

logoutUser();

// Start a fresh session for the flash message
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
setFlash('info', 'You have been signed out successfully.');
header('Location: ' . BASE_URL . '/login.php');
exit;
