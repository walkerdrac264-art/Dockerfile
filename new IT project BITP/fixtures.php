<?php
/**
 * Riverside FC - Fixtures & Match Tickets Schedule
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$db = getDB();

$filter = $_GET['tab'] ?? 'upcoming';
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT f.*, 
           MIN(t.price) as min_price, 
           MAX(t.price) as max_price,
           SUM(t.remaining) as total_remaining,
           SUM(t.capacity) as total_capacity
    FROM fixtures f
    LEFT JOIN ticket_types t ON f.id = t.fixture_id
    WHERE 1=1
";

$params = [];

if ($filter === 'upcoming') {
    $sql .= " AND f.status = 'upcoming'";
} elseif ($filter === 'completed') {
    $sql .= " AND f.status = 'completed'";
}

if (!empty($search)) {
    $sql .= " AND (f.opponent LIKE ? OR f.competition LIKE ? OR f.venue LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$sql .= " GROUP BY f.id ORDER BY f.match_date " . ($filter === 'completed' ? 'DESC' : 'ASC') . ", f.kick_off ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$fixtures = $stmt->fetchAll();

$pageTitle = "Match Fixtures & Tickets";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Banner -->
<div class="bg-dark text-white py-4 border-bottom border-secondary mb-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="h2 fw-bold brand-font mb-1 text-white">
                    <i class="fas fa-calendar-alt text-info me-2"></i> Match Schedule &amp; Tickets
                </h1>
                <p class="text-secondary mb-0 small">
                    All Riverside FC home fixtures at <?= STADIUM_NAME ?>. Book tickets early to avoid box-office queues.
                </p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/my-tickets.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="fas fa-ticket-alt me-1"></i> Access My Purchased Tickets
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <!-- Filter Tabs & Search Bar -->
    <div class="card rfc-card p-3 mb-4 shadow-sm">
        <div class="row g-3 align-items-center">
            <div class="col-md-6">
                <ul class="nav nav-pills gap-2">
                    <li class="nav-item">
                        <a class="nav-link rounded-pill <?= $filter === 'upcoming' ? 'active bg-primary' : 'text-dark' ?>" 
                           href="<?= BASE_URL ?>/fixtures.php?tab=upcoming<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                            <i class="fas fa-play-circle me-1"></i> Upcoming Matches
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill <?= $filter === 'all' ? 'active bg-primary' : 'text-dark' ?>" 
                           href="<?= BASE_URL ?>/fixtures.php?tab=all<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                            <i class="fas fa-list me-1"></i> All Matches
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill <?= $filter === 'completed' ? 'active bg-primary' : 'text-dark' ?>" 
                           href="<?= BASE_URL ?>/fixtures.php?tab=completed<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                            <i class="fas fa-check-circle me-1"></i> Past Results
                        </a>
                    </li>
                </ul>
            </div>
            <div class="col-md-6">
                <form method="GET" action="" class="d-flex gap-2">
                    <input type="hidden" name="tab" value="<?= h($filter) ?>">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search opponent, competition..." value="<?= h($search) ?>">
                    </div>
                    <button type="submit" class="btn btn-dark px-3">Filter</button>
                    <?php if (!empty($search)): ?>
                        <a href="<?= BASE_URL ?>/fixtures.php?tab=<?= h($filter) ?>" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Fixtures List -->
    <div class="row g-4">
        <?php if (empty($fixtures)): ?>
            <div class="col-12 text-center py-5">
                <div class="p-5 bg-white rounded-4 border">
                    <i class="fas fa-futbol fs-1 text-muted mb-3"></i>
                    <h4 class="fw-bold">No Fixtures Found</h4>
                    <p class="text-muted">No matches found matching your selected criteria.</p>
                    <a href="<?= BASE_URL ?>/fixtures.php" class="btn btn-outline-primary rounded-pill px-4">Clear Filters</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($fixtures as $fix): 
                $rem = (int)($fix['total_remaining'] ?? 0);
                $cap = (int)($fix['total_capacity'] ?? 1);
                $capStatus = getCapacityStatus($rem, $cap);
                $oppLogo = !empty($fix['opponent_logo']) ? $fix['opponent_logo'] : 'hearts_of_oak.svg';
                $isUpcoming = ($fix['status'] === 'upcoming');
            ?>
                <div class="col-lg-6">
                    <div class="card rfc-card fixture-card h-100 p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="competition-tag"><?= h($fix['competition']) ?></span>
                            <?php if ($isUpcoming): ?>
                                <span class="badge <?= $capStatus['badge'] ?> rounded-pill px-3 py-1">
                                    <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> <?= $capStatus['text'] ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary rounded-pill px-3 py-1">
                                    <i class="fas fa-check me-1"></i> Match Concluded
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="row align-items-center my-2">
                            <div class="col-4 text-center">
                                <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="Riverside FC" class="team-badge mb-2">
                                <h6 class="fw-bold mb-0 text-dark"><?= CLUB_SHORT ?></h6>
                                <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">HOME</span>
                            </div>
                            <div class="col-4 text-center">
                                <div class="vs-badge mx-auto mb-2">VS</div>
                                <div class="fw-bold text-primary small"><?= formatKickOff($fix['kick_off']) ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;"><?= formatDate($fix['match_date'], 'd M Y') ?></div>
                            </div>
                            <div class="col-4 text-center">
                                <img src="<?= BASE_URL ?>/assets/images/<?= h($oppLogo) ?>" alt="<?= h($fix['opponent']) ?>" class="team-badge mb-2">
                                <h6 class="fw-bold mb-0 text-dark"><?= h($fix['opponent']) ?></h6>
                                <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">AWAY</span>
                            </div>
                        </div>

                        <div class="my-3 p-3 bg-light rounded-3">
                            <div class="d-flex justify-content-between text-muted small mb-1">
                                <span><i class="fas fa-map-marker-alt text-danger me-1"></i> <?= h($fix['venue']) ?></span>
                                <?php if ($isUpcoming): ?>
                                    <span><strong><?= number_format($rem) ?></strong> / <?= number_format($cap) ?> remaining</span>
                                <?php else: ?>
                                    <span>Match Completed</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($isUpcoming): ?>
                                <div class="capacity-meter">
                                    <div class="capacity-meter-bar <?= $rem > 0 ? 'bg-primary' : 'bg-danger' ?>" style="width: <?= $capStatus['percent'] ?>%;"></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                            <div>
                                <?php if ($isUpcoming): ?>
                                    <div class="text-muted small">Starting from</div>
                                    <div class="fs-5 fw-bold text-primary brand-font">
                                        <?= formatMoney($fix['min_price'] ?? 30) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="badge bg-light text-muted border p-2">Match Finished</div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if ($isUpcoming && $rem > 0): ?>
                                    <a href="<?= BASE_URL ?>/fixture.php?id=<?= $fix['id'] ?>" class="btn btn-rfc-primary px-3 py-2">
                                        <i class="fas fa-ticket-alt me-1"></i> Select Stand
                                    </a>
                                <?php elseif ($isUpcoming): ?>
                                    <button class="btn btn-secondary px-3 py-2" disabled>
                                        <i class="fas fa-ban me-1"></i> Sold Out
                                    </button>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>/fixture.php?id=<?= $fix['id'] ?>" class="btn btn-outline-secondary px-3 py-2">
                                        <i class="fas fa-info-circle me-1"></i> Match Info
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
