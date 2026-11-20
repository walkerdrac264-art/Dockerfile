<?php
/**
 * Riverside FC - Application Configuration
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Africa/Accra');

// Club and System Information
define('APP_NAME', 'Riverside FC Ticketing');
define('CLUB_NAME', 'Riverside Football Club');
define('CLUB_SHORT', 'Riverside FC');
define('CLUB_NICKNAME', 'The Riverians');
define('STADIUM_NAME', 'Riverside Arena');
define('STADIUM_CAPACITY', 25000);
define('CLUB_LOCATION', 'Accra, Ghana');
define('CLUB_EMAIL', 'tickets@riversidefc.com');
define('CLUB_PHONE', '+233 24 555 0192');

// Currency Configuration (Ghana Cedis)
define('CURRENCY_CODE', 'GHS');
define('CURRENCY_SYMBOL', 'GH₵');

// Dynamic Base URL detection for portable execution
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$scriptDir = str_replace('\\', '/', $scriptDir);

// Detect if we are in /admin or /api subfolder
if (preg_match('#/(admin|api)$#', $scriptDir)) {
    $basePath = preg_replace('#/(admin|api)$#', '', $scriptDir);
} else {
    $basePath = $scriptDir;
}
$basePath = rtrim($basePath, '/');

define('BASE_URL', $protocol . $host . $basePath);
define('ADMIN_URL', BASE_URL . '/admin');

// Security CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
