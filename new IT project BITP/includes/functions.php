<?php
/**
 * Riverside FC - Helper Functions
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Format currency in Ghana Cedis
 */
function formatMoney(float|int|string|null $amount): string {
    $num = (float)($amount ?? 0);
    return CURRENCY_SYMBOL . ' ' . number_format($num, 2);
}

/**
 * Format date for friendly football display
 */
function formatDate(string|null $dateStr, string $format = 'D, d M Y'): string {
    if (!$dateStr) return '';
    $timestamp = strtotime($dateStr);
    return date($format, $timestamp);
}

/**
 * Format kick-off time
 */
function formatKickOff(string|null $timeStr): string {
    if (!$timeStr) return '';
    $timestamp = strtotime($timeStr);
    return date('H:i', $timestamp) . ' GMT';
}

/**
 * Sanitize string output
 */
function h(mixed $str): string {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF hidden input field
 */
function csrfField(): string {
    $token = $_SESSION['csrf_token'] ?? '';
    return '<input type="hidden" name="csrf_token" value="' . h($token) . '">';
}

/**
 * Verify CSRF Token
 */
function verifyCsrfToken(): bool {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        return false;
    }
    return true;
}

/**
 * Set flash alert message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash_message'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlash(): ?array {
    if (!empty($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Render flash alert component
 */
function renderFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';
    
    $type = h($flash['type']);
    $msg = h($flash['message']);
    $icon = match($type) {
        'success' => 'fa-check-circle',
        'danger' => 'fa-exclamation-triangle',
        'warning' => 'fa-exclamation-circle',
        default => 'fa-info-circle'
    };

    return "
    <div class='alert alert-{$type} alert-dismissible fade show d-flex align-items-center shadow-sm rounded-3 my-3' role='alert'>
        <i class='fas {$icon} me-2 fs-5'></i>
        <div class='flex-grow-1'>{$msg}</div>
        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
    </div>";
}

/**
 * Generate unique booking reference code
 */
function generateBookingRef(): string {
    $year = date('Y');
    $bytes = bin2hex(random_bytes(3)); // 6 hex characters
    return 'RFC-' . $year . '-' . strtoupper($bytes);
}

/**
 * Generate unique ticket code
 */
function generateTicketCode(): string {
    $bytes = bin2hex(random_bytes(3));
    return 'RFC-TKT-' . strtoupper($bytes);
}

/**
 * Return percentage and visual badge for capacity remaining
 */
function getCapacityStatus(int $remaining, int $capacity): array {
    if ($capacity <= 0) {
        return ['status' => 'sold_out', 'badge' => 'bg-danger', 'text' => 'Sold Out', 'percent' => 0];
    }
    $percent = round(($remaining / $capacity) * 100);
    if ($remaining <= 0) {
        return ['status' => 'sold_out', 'badge' => 'bg-danger', 'text' => 'Sold Out', 'percent' => 0];
    } elseif ($percent <= 15) {
        return ['status' => 'critical', 'badge' => 'bg-danger', 'text' => 'Selling Fast (<15% left)', 'percent' => $percent];
    } elseif ($percent <= 35) {
        return ['status' => 'warning', 'badge' => 'bg-warning text-dark', 'text' => 'Limited Seats', 'percent' => $percent];
    } else {
        return ['status' => 'available', 'badge' => 'bg-success', 'text' => 'Available', 'percent' => $percent];
    }
}
