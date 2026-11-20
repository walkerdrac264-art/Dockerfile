<?php
/**
 * Riverside FC - Homepage
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$db = getDB();

// Fetch next upcoming fixture for Hero Spotlight
$stmtNext = $db->query("
    SELECT * FROM fixtures 
    WHERE status = 'upcoming' AND match_date >= CURRENT_DATE 
    ORDER BY match_date ASC, kick_off ASC 
    LIMIT 1
");
$nextMatch = $stmtNext->fetch();

// Fetch upcoming fixtures
$stmtFixtures = $db->query("
    SELECT f.*, 
           MIN(t.price) as min_price, 
           MAX(t.price) as max_price,
           SUM(t.remaining) as total_remaining,
           SUM(t.capacity) as total_capacity
    FROM fixtures f
    LEFT JOIN ticket_types t ON f.id = t.fixture_id
    WHERE f.status = 'upcoming' AND f.match_date >= CURRENT_DATE
    GROUP BY f.id
    ORDER BY f.match_date ASC, f.kick_off ASC
    LIMIT 6
");
$upcomingFixtures = $stmtFixtures->fetchAll();

$pageTitle = "Official Match Ticketing | Home Fixtures";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Spotlight Slideshow Section -->
<section class="rfc-hero rfc-hero-slider">
    <div id="rfcHeroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="7000" data-bs-pause="hover">
        
        <!-- Slide Indicators -->
        <div class="carousel-indicators rfc-carousel-indicators">
            <button type="button" data-bs-target="#rfcHeroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1: Matchday Spotlight">
                <span class="slide-num">01</span> Matchday Spotlight
            </button>
            <button type="button" data-bs-target="#rfcHeroCarousel" data-bs-slide-to="1" aria-label="Slide 2: VIP &amp; Hospitality">
                <span class="slide-num">02</span> VIP Hospitality
            </button>
            <button type="button" data-bs-target="#rfcHeroCarousel" data-bs-slide-to="2" aria-label="Slide 3: Instant Digital QR Pass">
                <span class="slide-num">03</span> Digital QR Pass
            </button>
        </div>

        <div class="carousel-inner">
            
            <!-- SLIDE 1: Next Match Spotlight & Live Countdown -->
            <div class="carousel-item active rfc-hero-slide">
                <img src="<?= BASE_URL ?>/assets/images/hero-slide-1.jpg" class="rfc-hero-bg-img" alt="Riverside Arena Matchday Floodlights">
                <div class="rfc-hero-overlay"></div>
                <div class="rfc-hero-slide-content">
                    <div class="container position-relative">
                        <div class="row align-items-center g-4">
                            <div class="col-lg-7">
                                <div class="d-inline-flex align-items-center gap-2 mb-3">
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold text-uppercase">
                                        <i class="fas fa-futbol me-1"></i> Matchday Central
                                    </span>
                                    <span class="match-countdown-badge">
                                        <i class="fas fa-bolt me-1 text-warning"></i> Next Home Clash
                                    </span>
                                </div>

                                <h1 class="display-4 fw-black text-white brand-font mb-3">
                                    FEEL THE PASSION AT <span class="text-info"><?= STADIUM_NAME ?></span>
                                </h1>
                                
                                <p class="lead text-light mb-4 opacity-90" style="max-width: 600px;">
                                    Secure your official digital match tickets for <?= CLUB_NAME ?>. Instant mobile delivery with scannable gate QR codes. Back the boys to victory!
                                </p>

                                <?php if ($nextMatch): 
                                    $kickoffIso = $nextMatch['match_date'] . 'T' . $nextMatch['kick_off'];
                                ?>
                                    <!-- Next Match Live Countdown Widget -->
                                    <div class="bg-dark bg-opacity-75 p-3 rounded-4 border border-secondary mb-4 d-inline-block backdrop-blur">
                                        <div class="small text-secondary mb-2 text-uppercase fw-bold" style="letter-spacing: 1px;">
                                            <i class="fas fa-clock text-warning me-1"></i> Countdown to Kick-Off:
                                        </div>
                                        <div id="match-countdown" data-kickoff="<?= $kickoffIso ?>" class="d-flex align-items-center gap-2 text-center">
                                            <div class="px-3 py-2 bg-black bg-opacity-50 rounded-3 border border-secondary">
                                                <div id="cd-days" class="fs-4 fw-bold text-white lh-1">--</div>
                                                <div class="text-secondary small" style="font-size: 0.65rem;">DAYS</div>
                                            </div>
                                            <div class="fs-4 fw-bold text-secondary">:</div>
                                            <div class="px-3 py-2 bg-black bg-opacity-50 rounded-3 border border-secondary">
                                                <div id="cd-hours" class="fs-4 fw-bold text-white lh-1">--</div>
                                                <div class="text-secondary small" style="font-size: 0.65rem;">HOURS</div>
                                            </div>
                                            <div class="fs-4 fw-bold text-secondary">:</div>
                                            <div class="px-3 py-2 bg-black bg-opacity-50 rounded-3 border border-secondary">
                                                <div id="cd-mins" class="fs-4 fw-bold text-white lh-1">--</div>
                                                <div class="text-secondary small" style="font-size: 0.65rem;">MINS</div>
                                            </div>
                                            <div class="fs-4 fw-bold text-secondary">:</div>
                                            <div class="px-3 py-2 bg-black bg-opacity-50 rounded-3 border border-secondary">
                                                <div id="cd-secs" class="fs-4 fw-bold text-info lh-1">--</div>
                                                <div class="text-secondary small" style="font-size: 0.65rem;">SECS</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap gap-3">
                                        <a href="<?= BASE_URL ?>/fixture.php?id=<?= $nextMatch['id'] ?>" class="btn btn-rfc-gold btn-lg px-4 py-3 shadow fw-bold">
                                            <i class="fas fa-ticket-alt me-2"></i> Book Tickets vs <?= h($nextMatch['opponent']) ?>
                                        </a>
                                        <a href="<?= BASE_URL ?>/fixtures.php" class="btn btn-outline-light btn-lg px-4 py-3 fw-semibold">
                                            <i class="fas fa-list me-2"></i> All Fixtures
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>/fixtures.php" class="btn btn-rfc-primary btn-lg px-4 py-3">
                                        <i class="fas fa-calendar-alt me-2"></i> View Fixture Schedule
                                    </a>
                                <?php endif; ?>
                            </div>

                            <!-- Spotlight Card -->
                            <?php if ($nextMatch): ?>
                                <div class="col-lg-5">
                                    <div class="card hero-glass-card border-secondary border-2 rounded-4 p-4 shadow-lg text-white">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="badge bg-primary text-uppercase"><?= h($nextMatch['competition']) ?></span>
                                            <span class="text-warning small"><i class="fas fa-star me-1"></i> Feature Fixture</span>
                                        </div>

                                        <div class="text-center py-2">
                                            <div class="d-flex justify-content-around align-items-center mb-3">
                                                <div class="text-center">
                                                    <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="Riverside FC" style="height: 72px; width: 72px;">
                                                    <div class="fw-bold mt-2 small"><?= CLUB_SHORT ?></div>
                                                    <span class="badge bg-secondary" style="font-size: 0.65rem;">HOME</span>
                                                </div>
                                                <div class="vs-badge fs-6">VS</div>
                                                <div class="text-center">
                                                    <?php $oppLogo = !empty($nextMatch['opponent_logo']) ? $nextMatch['opponent_logo'] : 'hearts_of_oak.svg'; ?>
                                                    <img src="<?= BASE_URL ?>/assets/images/<?= h($oppLogo) ?>" alt="<?= h($nextMatch['opponent']) ?>" style="height: 72px; width: 72px;">
                                                    <div class="fw-bold mt-2 small"><?= h($nextMatch['opponent']) ?></div>
                                                    <span class="badge bg-dark border" style="font-size: 0.65rem;">AWAY</span>
                                                </div>
                                            </div>

                                            <hr class="border-secondary my-3">

                                            <div class="row g-2 text-start small mb-3">
                                                <div class="col-6">
                                                    <span class="text-secondary d-block"><i class="fas fa-calendar-alt me-1 text-info"></i> Date:</span>
                                                    <span class="fw-bold"><?= formatDate($nextMatch['match_date']) ?></span>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-secondary d-block"><i class="fas fa-clock me-1 text-info"></i> Kick-Off:</span>
                                                    <span class="fw-bold"><?= formatKickOff($nextMatch['kick_off']) ?></span>
                                                </div>
                                                <div class="col-12 mt-1">
                                                    <span class="text-secondary d-block"><i class="fas fa-map-marker-alt me-1 text-info"></i> Venue:</span>
                                                    <span class="fw-bold"><?= h($nextMatch['venue']) ?></span>
                                                </div>
                                            </div>

                                            <a href="<?= BASE_URL ?>/fixture.php?id=<?= $nextMatch['id'] ?>" class="btn btn-rfc-primary w-100 py-2 fw-semibold">
                                                <i class="fas fa-ticket-alt me-2"></i> Select Stand &amp; Seats
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 2: VIP & Executive Hospitality -->
            <div class="carousel-item rfc-hero-slide">
                <img src="<?= BASE_URL ?>/assets/images/hero-slide-2.jpg" class="rfc-hero-bg-img" alt="VIP Luxury Executive Lounge">
                <div class="rfc-hero-overlay"></div>
                <div class="rfc-hero-slide-content">
                    <div class="container position-relative">
                        <div class="row align-items-center g-4">
                            <div class="col-lg-7">
                                <div class="d-inline-flex align-items-center gap-2 mb-3">
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold text-uppercase">
                                        <i class="fas fa-crown me-1"></i> VIP Suites &amp; Hospitality
                                    </span>
                                    <span class="match-countdown-badge">
                                        <i class="fas fa-wine-glass-alt me-1 text-warning"></i> Exclusive Matchday
                                    </span>
                                </div>

                                <h1 class="display-4 fw-black text-white brand-font mb-3">
                                    WORLD-CLASS LUXURY AT <span class="text-warning">THE ARENA</span>
                                </h1>
                                
                                <p class="lead text-light mb-4 opacity-90" style="max-width: 620px;">
                                    Elevate your matchday experience with VIP hospitality. Enjoy panoramic pitch views, chef-curated cuisine, dedicated hostess service, and prime halfway-line grandstand seating.
                                </p>

                                <div class="d-flex flex-wrap gap-2 mb-4">
                                    <span class="hero-pill-feature"><i class="fas fa-utensils text-warning"></i> Gourmet Pre-Match Dining</span>
                                    <span class="hero-pill-feature"><i class="fas fa-cocktail text-info"></i> Complimentary Bar</span>
                                    <span class="hero-pill-feature"><i class="fas fa-chair text-success"></i> Cushioned Luxury Seats</span>
                                    <span class="hero-pill-feature"><i class="fas fa-parking text-primary"></i> Reserved VIP Parking</span>
                                </div>

                                <div class="d-flex flex-wrap gap-3">
                                    <a href="<?= BASE_URL ?>/fixtures.php" class="btn btn-warning btn-lg px-4 py-3 shadow fw-bold text-dark">
                                        <i class="fas fa-gem me-2"></i> Reserve VIP Hospitality
                                    </a>
                                    <a href="<?= BASE_URL ?>/#stadium" class="btn btn-outline-light btn-lg px-4 py-3 fw-semibold">
                                        <i class="fas fa-map-marked-alt me-2"></i> Explore Stadium Map
                                    </a>
                                </div>
                            </div>

                            <!-- VIP Showcase Card -->
                            <div class="col-lg-5">
                                <div class="card hero-glass-card border-warning border-opacity-50 border-2 rounded-4 p-4 shadow-lg text-white">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="badge bg-warning text-dark text-uppercase fw-bold"><i class="fas fa-star me-1"></i> Executive Club</span>
                                        <span class="text-info small"><i class="fas fa-shield-alt me-1"></i> All-Inclusive Pass</span>
                                    </div>

                                    <div class="text-center py-2">
                                        <div class="display-6 fw-bold text-warning mb-1">From GH₵ 150</div>
                                        <div class="text-secondary small mb-3">Per Guest / Matchday Package</div>

                                        <div class="list-group list-group-flush text-start small mb-3 bg-transparent">
                                            <div class="list-group-item bg-transparent text-light border-secondary border-opacity-25 px-0 py-2 d-flex justify-content-between">
                                                <span><i class="fas fa-check-circle text-success me-2"></i> Halfway Line VIP Box</span>
                                                <span class="text-info fw-bold">Gate VIP</span>
                                            </div>
                                            <div class="list-group-item bg-transparent text-light border-secondary border-opacity-25 px-0 py-2 d-flex justify-content-between">
                                                <span><i class="fas fa-check-circle text-success me-2"></i> Climate-Controlled Lounge</span>
                                                <span class="text-info fw-bold">Included</span>
                                            </div>
                                            <div class="list-group-item bg-transparent text-light border-secondary border-opacity-25 px-0 py-2 d-flex justify-content-between">
                                                <span><i class="fas fa-check-circle text-success me-2"></i> Match Programme &amp; Souvenir</span>
                                                <span class="text-info fw-bold">Free</span>
                                            </div>
                                            <div class="list-group-item bg-transparent text-light border-secondary border-opacity-25 px-0 py-2 d-flex justify-content-between">
                                                <span><i class="fas fa-check-circle text-success me-2"></i> Priority Fast-Track Gate</span>
                                                <span class="text-info fw-bold">Express Entry</span>
                                            </div>
                                        </div>

                                        <a href="<?= BASE_URL ?>/fixtures.php" class="btn btn-outline-warning w-100 py-2 fw-semibold">
                                            <i class="fas fa-calendar-check me-2"></i> Select Match For VIP Access
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 3: Instant Digital QR Pass & Mobile Money -->
            <div class="carousel-item rfc-hero-slide">
                <img src="<?= BASE_URL ?>/assets/images/hero-slide-3.jpg" class="rfc-hero-bg-img" alt="Supporters Matchday Passion">
                <div class="rfc-hero-overlay"></div>
                <div class="rfc-hero-slide-content">
                    <div class="container position-relative">
                        <div class="row align-items-center g-4">
                            <div class="col-lg-7">
                                <div class="d-inline-flex align-items-center gap-2 mb-3">
                                    <span class="badge bg-success px-3 py-2 rounded-pill fw-bold text-uppercase">
                                        <i class="fas fa-qrcode me-1"></i> Fast Digital Ticketing
                                    </span>
                                    <span class="match-countdown-badge">
                                        <i class="fas fa-mobile-alt me-1 text-info"></i> Zero Waiting Lines
                                    </span>
                                </div>

                                <h1 class="display-4 fw-black text-white brand-font mb-3">
                                    INSTANT MOBILE PASSES <span class="text-cyan" style="color: #38bdf8;">IN SECONDS</span>
                                </h1>
                                
                                <p class="lead text-light mb-4 opacity-90" style="max-width: 620px;">
                                    Say goodbye to long stadium ticket queues. Order tickets seamlessly with MTN MoMo or Telecel Cash, receive your encrypted QR pass on your phone, and breeze through the turnstiles.
                                </p>

                                <div class="d-flex flex-wrap gap-2 mb-4">
                                    <span class="hero-pill-feature"><i class="fas fa-bolt text-warning"></i> Instant MoMo Checkout</span>
                                    <span class="hero-pill-feature"><i class="fas fa-qrcode text-info"></i> Scannable Gate QR Code</span>
                                    <span class="hero-pill-feature"><i class="fas fa-shield-alt text-success"></i> 100% Anti-Scalping Protection</span>
                                    <span class="hero-pill-feature"><i class="fas fa-print text-primary"></i> Mobile PDF &amp; Print Pass</span>
                                </div>

                                <div class="d-flex flex-wrap gap-3">
                                    <a href="<?= BASE_URL ?>/fixtures.php" class="btn btn-info btn-lg px-4 py-3 shadow fw-bold text-dark" style="background-color: #38bdf8; border-color: #38bdf8;">
                                        <i class="fas fa-ticket-alt me-2"></i> Book Tickets Now
                                    </a>
                                    <a href="<?= BASE_URL ?>/my-tickets.php" class="btn btn-outline-light btn-lg px-4 py-3 fw-semibold">
                                        <i class="fas fa-mobile-screen-button me-2"></i> Access My Tickets
                                    </a>
                                </div>
                            </div>

                            <!-- Digital QR Pass Showcase Card -->
                            <div class="col-lg-5">
                                <div class="card hero-glass-card border-info border-opacity-50 border-2 rounded-4 p-4 shadow-lg text-white text-center">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="badge bg-success text-uppercase"><i class="fas fa-shield-alt me-1"></i> Verified Entry</span>
                                        <span class="text-info small"><i class="fas fa-wifi me-1"></i> Live Gate Sync</span>
                                    </div>

                                    <div class="py-2">
                                        <div class="digital-qr-preview-box mb-3 mx-auto">
                                            <div class="digital-qr-scanline"></div>
                                            <i class="fas fa-qrcode fa-5x text-dark"></i>
                                            <div class="text-dark small fw-bold mt-1" style="font-family: monospace; letter-spacing: 1px;">RFC-PASS-7492</div>
                                        </div>

                                        <h5 class="fw-bold mb-1 text-white">Direct Phone Gate Entry</h5>
                                        <p class="text-secondary small mb-3">
                                            Hold your phone under the barcode scanner at Gates A to E for instant green-light access in under 3 seconds.
                                        </p>

                                        <div class="row g-2 text-start small border-top border-secondary border-opacity-25 pt-3">
                                            <div class="col-6">
                                                <span class="text-secondary d-block">Supported Payments:</span>
                                                <span class="fw-bold text-warning"><i class="fas fa-coins me-1"></i> MTN MoMo / Telecel</span>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-secondary d-block">Gate Turnaround:</span>
                                                <span class="fw-bold text-success"><i class="fas fa-tachometer-alt me-1"></i> Under 3 Seconds</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Carousel Navigation Controls -->
        <button class="carousel-control-prev rfc-carousel-arrow" type="button" data-bs-target="#rfcHeroCarousel" data-bs-slide="prev" aria-label="Previous Slide">
            <span class="rfc-arrow-circle" aria-hidden="true"><i class="fas fa-chevron-left"></i></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next rfc-carousel-arrow" type="button" data-bs-target="#rfcHeroCarousel" data-bs-slide="next" aria-label="Next Slide">
            <span class="rfc-arrow-circle" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
            <span class="visually-hidden">Next</span>
        </button>

    </div>
</section>

<!-- Trust & Feature Badges Strip -->
<div class="bg-white py-3 border-bottom shadow-sm">
    <div class="container">
        <div class="row g-3 text-center">
            <div class="col-md-3 col-6">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <i class="fas fa-qrcode fs-3 text-primary"></i>
                    <div class="text-start">
                        <div class="fw-bold small lh-1">Instant QR Ticket</div>
                        <div class="text-muted" style="font-size: 0.75rem;">Scan directly from phone</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <i class="fas fa-mobile-alt fs-3 text-success"></i>
                    <div class="text-start">
                        <div class="fw-bold small lh-1">Mobile Money (MoMo)</div>
                        <div class="text-muted" style="font-size: 0.75rem;">MTN, Telecel, Card</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <i class="fas fa-shield-alt fs-3 text-warning"></i>
                    <div class="text-start">
                        <div class="fw-bold small lh-1">Verified Official</div>
                        <div class="text-muted" style="font-size: 0.75rem;">Direct club pricing</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <i class="fas fa-users fs-3 text-info"></i>
                    <div class="text-start">
                        <div class="fw-bold small lh-1">25,000 Capacity</div>
                        <div class="text-muted" style="font-size: 0.75rem;">Real-time seat counter</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming Home Fixtures Section -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
            <div>
                <span class="text-primary fw-bold text-uppercase small" style="letter-spacing: 1px;">Home Matches</span>
                <h2 class="display-6 fw-bold brand-font text-dark mb-1">Upcoming Home Fixtures</h2>
                <p class="text-muted mb-0">Experience every goal, tackle, and chant live at Riverside Arena.</p>
            </div>
            <a href="<?= BASE_URL ?>/fixtures.php" class="btn btn-outline-primary px-3 rounded-pill fw-semibold">
                View Full Fixtures List <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php if (empty($upcomingFixtures)): ?>
                <div class="col-12 text-center py-5">
                    <div class="p-5 bg-white rounded-4 border">
                        <i class="fas fa-calendar-times fs-1 text-muted mb-3"></i>
                        <h4 class="fw-bold">No Upcoming Home Matches Scheduled</h4>
                        <p class="text-muted">Check back soon for new matchday announcements.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($upcomingFixtures as $fixture): 
                    $rem = (int)($fixture['total_remaining'] ?? 0);
                    $cap = (int)($fixture['total_capacity'] ?? 1);
                    $capStatus = getCapacityStatus($rem, $cap);
                    $oppLogo = !empty($fixture['opponent_logo']) ? $fixture['opponent_logo'] : 'hearts_of_oak.svg';
                ?>
                    <div class="col-lg-6">
                        <div class="card rfc-card fixture-card h-100 p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="competition-tag"><?= h($fixture['competition']) ?></span>
                                <span class="badge <?= $capStatus['badge'] ?> rounded-pill px-3 py-1">
                                    <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> <?= $capStatus['text'] ?>
                                </span>
                            </div>

                            <div class="row align-items-center my-2">
                                <div class="col-4 text-center">
                                    <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="Riverside FC" class="team-badge mb-2">
                                    <h6 class="fw-bold mb-0 text-dark"><?= CLUB_SHORT ?></h6>
                                    <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">HOME</span>
                                </div>
                                <div class="col-4 text-center">
                                    <div class="vs-badge mx-auto mb-2">VS</div>
                                    <div class="fw-bold text-primary small"><?= formatKickOff($fixture['kick_off']) ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;"><?= formatDate($fixture['match_date'], 'd M Y') ?></div>
                                </div>
                                <div class="col-4 text-center">
                                    <img src="<?= BASE_URL ?>/assets/images/<?= h($oppLogo) ?>" alt="<?= h($fixture['opponent']) ?>" class="team-badge mb-2">
                                    <h6 class="fw-bold mb-0 text-dark"><?= h($fixture['opponent']) ?></h6>
                                    <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">AWAY</span>
                                </div>
                            </div>

                            <div class="my-3 p-3 bg-light rounded-3">
                                <div class="d-flex justify-content-between text-muted small mb-1">
                                    <span><i class="fas fa-map-marker-alt text-danger me-1"></i> <?= h($fixture['venue']) ?></span>
                                    <span><strong><?= number_format($rem) ?></strong> seats left</span>
                                </div>
                                <div class="capacity-meter">
                                    <div class="capacity-meter-bar <?= $rem > 0 ? 'bg-primary' : 'bg-danger' ?>" style="width: <?= $capStatus['percent'] ?>%;"></div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                                <div>
                                    <div class="text-muted small">Tickets from</div>
                                    <div class="fs-5 fw-bold text-primary brand-font">
                                        <?= formatMoney($fixture['min_price'] ?? 30) ?>
                                    </div>
                                </div>
                                <a href="<?= BASE_URL ?>/fixture.php?id=<?= $fixture['id'] ?>" class="btn btn-rfc-primary px-3 py-2">
                                    <i class="fas fa-ticket-alt me-1"></i> Select Tickets
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Stadium Layout & Stands Guide Section -->
<section id="stadium" class="py-5 bg-white border-top border-bottom">
    <div class="container">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-primary-subtle text-primary fw-bold text-uppercase px-3 py-2 rounded-pill mb-2">
                Stadium &amp; Matchday Experience
            </span>
            <h2 class="display-6 fw-bold brand-font text-dark">Explore Riverside Arena</h2>
            <p class="text-muted">
                Located on High Street, Accra with a seating capacity of 25,000 passionate supporters. Find your favorite spot in the ground.
            </p>
        </div>

        <div class="row align-items-center g-4 mb-5">
            <div class="col-lg-7 text-center">
                <div class="p-2 border rounded-4 shadow-sm bg-dark">
                    <img src="<?= BASE_URL ?>/assets/images/stadium_map.svg" alt="Riverside Arena Stadium Map" class="img-fluid rounded-3">
                </div>
                <div class="small text-muted mt-2">
                    <i class="fas fa-info-circle me-1"></i> Stadium Gate Plan: Gates A, B, C, D, E with electronic turnstile barcode/QR scanners.
                </div>
            </div>

            <div class="col-lg-5">
                <h4 class="fw-bold mb-3 brand-font text-dark"><i class="fas fa-chair text-primary me-2"></i> Stadium Enclosures</h4>
                
                <div class="d-flex flex-column gap-3">
                    <div class="p-3 border rounded-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-crown text-warning me-2"></i> VIP Executive Lounge</h6>
                            <span class="badge bg-warning text-dark fw-bold">GH₵ 150+</span>
                        </div>
                        <p class="small text-muted mb-0 mt-1">Gate A • Padded seating, matchday brochure, executive lounge refreshments &amp; AC.</p>
                    </div>

                    <div class="p-3 border rounded-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-eye text-primary me-2"></i> Main Grandstand</h6>
                            <span class="badge bg-primary fw-bold">GH₵ 80+</span>
                        </div>
                        <p class="small text-muted mb-0 mt-1">Gate B • Covered elevated pitch-side view with central vantage points.</p>
                    </div>

                    <div class="p-3 border rounded-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-users text-info me-2"></i> Popular Stand (East)</h6>
                            <span class="badge bg-info text-white fw-bold">GH₵ 40+</span>
                        </div>
                        <p class="small text-muted mb-0 mt-1">Gate C • High-energy covered stand with 8,000 roaring home supporters.</p>
                    </div>

                    <div class="p-3 border rounded-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-bullhorn text-danger me-2"></i> North Terrace (Ultras Stand)</h6>
                            <span class="badge bg-danger fw-bold">GH₵ 30+</span>
                        </div>
                        <p class="small text-muted mb-0 mt-1">Gate D • Dedicated singing &amp; drumming section directly behind the home goal.</p>
                    </div>

                    <div class="p-3 border rounded-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-child text-success me-2"></i> Riverside Family Area</h6>
                            <span class="badge bg-success fw-bold">GH₵ 50+</span>
                        </div>
                        <p class="small text-muted mb-0 mt-1">Gate E • Dedicated alcohol-free family area with junior fan activities.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Matchday Guidelines FAQ -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h3 class="fw-bold brand-font text-dark">Matchday Ticketing FAQs</h3>
            <p class="text-muted">Everything you need to know before arriving at Riverside Arena.</p>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-md-4">
                <div class="p-4 bg-white rounded-3 border h-100 shadow-sm">
                    <i class="fas fa-mobile-alt fs-2 text-primary mb-3"></i>
                    <h5 class="fw-bold">How does the digital QR ticket work?</h5>
                    <p class="small text-muted mb-0">
                        After ordering, your digital ticket is generated instantly with a secure unique QR code. Present the QR code on your phone screen at your designated turnstile gate for direct entry. You can also print it out.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-4 bg-white rounded-3 border h-100 shadow-sm">
                    <i class="fas fa-money-bill-wave fs-2 text-success mb-3"></i>
                    <h5 class="fw-bold">Which payment methods are accepted?</h5>
                    <p class="small text-muted mb-0">
                        We accept Ghana Cedis (GH₵) via all Mobile Money networks (MTN MoMo, Telecel Cash, AirtelTigo Money) as well as Debit / Credit cards (Visa, Mastercard).
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-4 bg-white rounded-3 border h-100 shadow-sm">
                    <i class="fas fa-clock fs-2 text-warning mb-3"></i>
                    <h5 class="fw-bold">What time do stadium gates open?</h5>
                    <p class="small text-muted mb-0">
                        Gates open 2 hours before the scheduled kick-off time. We encourage all fans to arrive at least 45 minutes prior to kick-off to enjoy pre-match music and avoid gate queues.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
