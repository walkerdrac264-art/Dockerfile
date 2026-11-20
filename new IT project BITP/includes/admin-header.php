<?php
/**
 * Riverside FC - Admin Dashboard Header
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Enforce Admin access
requireAdmin();

$user = currentUser();
$adminCurrentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($adminTitle) ? h($adminTitle) . ' | ' : '' ?>Club Admin | <?= CLUB_NAME ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Chart.js for Admin Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom Style -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/logo.svg">
    <style>
        .admin-nav-item.active {
            background-color: #0284c7 !important;
            color: #ffffff !important;
        }
        .stat-card {
            border-radius: 14px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body class="bg-light">

    <!-- Top Admin Bar -->
    <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-2 shadow border-bottom border-secondary">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 fs-6 d-flex align-items-center gap-2 brand-font fw-bold" href="<?= ADMIN_URL ?>/">
            <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="RFC Logo" height="30">
            <span>RIVERSIDE FC ADMIN</span>
        </a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-nav flex-row ms-auto px-3 align-items-center gap-3">
            <a href="<?= BASE_URL ?>/" target="_blank" class="nav-link text-white-50 small d-none d-sm-inline-block">
                <i class="fas fa-external-link-alt me-1"></i> Public Website
            </a>
            <div class="nav-item text-nowrap">
                <span class="text-white small me-2"><i class="fas fa-user-shield text-info me-1"></i> <?= h($user['name']) ?></span>
                <a class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1" href="<?= BASE_URL ?>/logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <!-- Admin Sidebar Navigation -->
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block admin-sidebar collapse px-3 py-3 border-end">
                <div class="position-sticky pt-2">
                    <div class="text-uppercase small fw-bold text-secondary mb-2 px-2" style="font-size: 0.7rem; letter-spacing: 1px;">Operations</div>
                    <ul class="nav flex-column mb-3">
                        <li class="nav-item">
                            <a class="nav-link admin-nav-item <?= $adminCurrentPage === 'index.php' ? 'active' : '' ?>" href="<?= ADMIN_URL ?>/">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard Overview
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link admin-nav-item <?= in_array($adminCurrentPage, ['fixtures.php', 'fixture-edit.php']) ? 'active' : '' ?>" href="<?= ADMIN_URL ?>/fixtures.php">
                                <i class="fas fa-calendar-alt me-2"></i> Fixture Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link admin-nav-item <?= $adminCurrentPage === 'fixture-create.php' ? 'active' : '' ?>" href="<?= ADMIN_URL ?>/fixture-create.php">
                                <i class="fas fa-plus-circle me-2"></i> Add New Fixture
                            </a>
                        </li>
                    </ul>

                    <div class="text-uppercase small fw-bold text-secondary mb-2 px-2" style="font-size: 0.7rem; letter-spacing: 1px;">Ticketing &amp; Gate</div>
                    <ul class="nav flex-column mb-3">
                        <li class="nav-item">
                            <a class="nav-link admin-nav-item <?= in_array($adminCurrentPage, ['bookings.php', 'booking-detail.php']) ? 'active' : '' ?>" href="<?= ADMIN_URL ?>/bookings.php">
                                <i class="fas fa-receipt me-2"></i> All Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link admin-nav-item <?= $adminCurrentPage === 'scan.php' ? 'active' : '' ?>" href="<?= ADMIN_URL ?>/scan.php">
                                <i class="fas fa-qrcode me-2"></i> Gate QR Scanner
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link admin-nav-item <?= $adminCurrentPage === 'reports.php' ? 'active' : '' ?>" href="<?= ADMIN_URL ?>/reports.php">
                                <i class="fas fa-chart-line me-2"></i> Sales &amp; Capacity Reports
                            </a>
                        </li>
                    </ul>

                    <div class="text-uppercase small fw-bold text-secondary mb-2 px-2" style="font-size: 0.7rem; letter-spacing: 1px;">System</div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>/install.php">
                                <i class="fas fa-database me-2"></i> DB &amp; System Health
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content Area -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <?= renderFlash() ?>
