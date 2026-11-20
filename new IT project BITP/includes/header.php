<?php
/**
 * Riverside FC - Public Site Header
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? h($pageTitle) . ' | ' : '' ?><?= CLUB_NAME ?> Official Ticketing</title>
    <meta name="description" content="Official match ticket portal for Riverside FC home fixtures at Riverside Arena, Accra. Safe, fast, and digital QR ticketing.">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Custom Design System -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/chatbot.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/print-ticket.css" rel="stylesheet" media="print">
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/logo.svg">
</head>
<body>

    <!-- Top Announcement Bar -->
    <div class="bg-dark text-white py-1 px-3 small border-bottom border-secondary d-none d-md-block no-print" style="font-size: 0.8rem;">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-map-marker-alt text-info me-1"></i> <?= STADIUM_NAME ?>, <?= CLUB_LOCATION ?>
                <span class="mx-2 text-secondary">|</span>
                <i class="fas fa-shield-alt text-warning me-1"></i> 100% Guaranteed Official Match Tickets
            </div>
            <div>
                <i class="fas fa-phone-alt me-1 text-info"></i> <?= CLUB_PHONE ?>
                <span class="mx-2 text-secondary">|</span>
                <span class="badge bg-primary text-uppercase"><?= CURRENCY_SYMBOL ?> (Ghana Cedis)</span>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark rfc-navbar sticky-top no-print">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/">
                <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="<?= CLUB_SHORT ?> Crest" class="rfc-brand-logo">
                <div>
                    <span class="fw-bold fs-5 tracking-tight text-white d-block lh-1 brand-font"><?= CLUB_SHORT ?></span>
                    <span class="text-info text-uppercase" style="font-size: 0.65rem; letter-spacing: 1.5px; font-weight: 700;">Ticket Office</span>
                </div>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-テク="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/">
                            <i class="fas fa-home me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'fixtures.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/fixtures.php">
                            <i class="fas fa-calendar-alt me-1"></i> Fixtures &amp; Tickets
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/#stadium">
                            <i class="fas fa-landmark me-1"></i> Riverside Arena
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'my-tickets.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/my-tickets.php">
                            <i class="fas fa-qrcode me-1"></i> My Tickets
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2">
                    <?php if ($user): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-light btn-sm dropdown-toggle rounded-pill px-3 py-2 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle text-info fs-5"></i>
                                <span class="fw-semibold"><?= h($user['name']) ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                                <li><h6 class="dropdown-header small text-muted"><?= h($user['email']) ?></h6></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/my-tickets.php"><i class="fas fa-ticket-alt text-primary me-2"></i> My Match Tickets</a></li>
                                <?php if (isAdmin()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-primary fw-bold" href="<?= BASE_URL ?>/admin/"><i class="fas fa-tachometer-alt me-2"></i> Admin Dashboard</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Sign Out</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-light btn-sm px-3 py-2 rounded-pill">
                            <i class="fas fa-sign-in-alt me-1"></i> Sign In
                        </a>
                        <a href="<?= BASE_URL ?>/fixtures.php" class="btn btn-rfc-primary btn-sm px-3 py-2 rounded-pill">
                            <i class="fas fa-ticket-alt me-1"></i> Buy Tickets
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Container with Flash Message -->
    <main class="flex-grow-1">
        <div class="container mt-3 no-print">
            <?= renderFlash() ?>
        </div>
