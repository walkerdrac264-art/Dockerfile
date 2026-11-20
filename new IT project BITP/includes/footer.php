    </main>

    <!-- Public Site Footer -->
    <footer class="rfc-footer no-print">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="<?= CLUB_NAME ?>" height="42">
                        <h5 class="mb-0 fw-bold text-white"><?= CLUB_NAME ?></h5>
                    </div>
                    <p class="small text-secondary mb-3">
                        The official digital ticketing portal for Riverside FC home matches at <?= STADIUM_NAME ?>, Accra. Skip the ticket lines, support the Riverians, and scan your digital QR ticket at the gate.
                    </p>
                    <div class="d-flex gap-2 text-white">
                        <span class="badge bg-secondary"><i class="fas fa-shield-alt me-1"></i> Anti-Scalping</span>
                        <span class="badge bg-secondary"><i class="fas fa-qrcode me-1"></i> Digital QR Pass</span>
                        <span class="badge bg-primary"><i class="fas fa-coins me-1"></i> Ghana Cedis (GH₵)</span>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h6 class="text-white fw-bold mb-3 text-uppercase small" style="letter-spacing: 1px;">Quick Links</h6>
                    <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                        <li><a href="<?= BASE_URL ?>/"><i class="fas fa-angle-right me-1 text-info"></i> Home</a></li>
                        <li><a href="<?= BASE_URL ?>/fixtures.php"><i class="fas fa-angle-right me-1 text-info"></i> All Fixtures</a></li>
                        <li><a href="<?= BASE_URL ?>/#stadium"><i class="fas fa-angle-right me-1 text-info"></i> Stadium Map</a></li>
                        <li><a href="<?= BASE_URL ?>/my-tickets.php"><i class="fas fa-angle-right me-1 text-info"></i> My Tickets</a></li>
                        <li><a href="<?= BASE_URL ?>/install.php"><i class="fas fa-database me-1 text-info"></i> System &amp; DB Setup</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-bold mb-3 text-uppercase small" style="letter-spacing: 1px;">Matchday Assistance</h6>
                    <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                        <li><i class="fas fa-map-marker-alt text-warning me-2"></i> <?= STADIUM_NAME ?>, High Street, Accra</li>
                        <li><i class="fas fa-clock text-warning me-2"></i> Gates open 2 hours before kick-off</li>
                        <li><i class="fas fa-mobile-alt text-warning me-2"></i> MTN MoMo &amp; Telecel Cash Accepted</li>
                        <li><i class="fas fa-envelope text-warning me-2"></i> <?= CLUB_EMAIL ?></li>
                        <li><i class="fas fa-phone text-warning me-2"></i> <?= CLUB_PHONE ?></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-bold mb-3 text-uppercase small" style="letter-spacing: 1px;">Club Admin Portal</h6>
                    <p class="small text-secondary mb-3">
                        Secure fixture and capacity control for club stewards and ticketing administrators.
                    </p>
                    <a href="<?= BASE_URL ?>/admin/login.php" class="btn btn-outline-info btn-sm rounded-pill px-3">
                        <i class="fas fa-lock me-1"></i> Admin Portal
                    </a>
                </div>
            </div>

            <hr class="border-secondary my-4 opacity-25">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-secondary gap-2">
                <div>
                    &copy; <?= date('Y') ?> <?= CLUB_NAME ?>. All rights reserved. Powered by Riverside FC Digital Systems.
                </div>
                <div class="d-flex gap-3">
                    <span class="text-secondary">Official Ticketing Platform • Ghana Premier League</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- RiverBot Intelligent Matchday Chatbot -->
    <?php require_once __DIR__ . '/chatbot.php'; ?>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/chatbot.js"></script>
</body>
</html>
