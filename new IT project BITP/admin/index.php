<?php
/**
 * Riverside FC - Admin Dashboard Overview
 */

$adminTitle = "Dashboard Overview";
require_once __DIR__ . '/../includes/admin-header.php';

$db = getDB();

// 1. Calculate KPI Metrics
$revStmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total_rev FROM bookings WHERE payment_status = 'paid'");
$totalRevenue = (float)($revStmt->fetch()['total_rev'] ?? 0);

$tktCountStmt = $db->query("SELECT COUNT(*) as cnt FROM tickets WHERE status != 'cancelled'");
$totalTicketsSold = (int)($tktCountStmt->fetch()['cnt'] ?? 0);

$todayStmt = $db->query("SELECT COUNT(*) as cnt FROM tickets WHERE DATE(created_at) = CURRENT_DATE AND status != 'cancelled'");
$ticketsSoldToday = (int)($todayStmt->fetch()['cnt'] ?? 0);

$fixCountStmt = $db->query("SELECT COUNT(*) as cnt FROM fixtures WHERE status = 'upcoming'");
$activeFixturesCount = (int)($fixCountStmt->fetch()['cnt'] ?? 0);

// 2. Fetch Recent Bookings
$recentStmt = $db->query("
    SELECT b.*, f.opponent, f.match_date 
    FROM bookings b
    JOIN fixtures f ON b.fixture_id = f.id
    ORDER BY b.created_at DESC 
    LIMIT 6
");
$recentBookings = $recentStmt->fetchAll();

// 3. Fetch Real-time Stand Capacity Data for Upcoming Fixtures
$capacityStmt = $db->query("
    SELECT f.id, f.opponent, f.match_date, f.kick_off,
           tt.name as section_name, tt.gate, tt.capacity, tt.remaining, tt.price
    FROM ticket_types tt
    JOIN fixtures f ON tt.fixture_id = f.id
    WHERE f.status = 'upcoming'
    ORDER BY f.match_date ASC, tt.price DESC
");
$sectionCapacities = $capacityStmt->fetchAll();

// 4. Prepare Chart Data (Revenue per Fixture)
$chartRevStmt = $db->query("
    SELECT f.opponent, COALESCE(SUM(b.total_amount), 0) as revenue
    FROM fixtures f
    LEFT JOIN bookings b ON f.id = b.fixture_id AND b.payment_status = 'paid'
    GROUP BY f.id, f.opponent
    ORDER BY f.match_date ASC
");
$fixtureRevenues = $chartRevStmt->fetchAll();

$chartLabels = [];
$chartRevenueValues = [];
foreach ($fixtureRevenues as $fr) {
    $chartLabels[] = "vs " . $fr['opponent'];
    $chartRevenueValues[] = (float)$fr['revenue'];
}

// 5. Prepare Chart Data (Tickets Sold by Section Category)
$standSalesStmt = $db->query("
    SELECT tt.name, COUNT(t.id) as sold_count
    FROM tickets t
    JOIN ticket_types tt ON t.ticket_type_id = tt.id
    WHERE t.status != 'cancelled'
    GROUP BY tt.name
");
$standSales = $standSalesStmt->fetchAll();
$standLabels = [];
$standCounts = [];
foreach ($standSales as $ss) {
    $standLabels[] = $ss['name'];
    $standCounts[] = (int)$ss['sold_count'];
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
    <div>
        <h1 class="h2 fw-bold brand-font text-dark mb-1">Match Operations &amp; Ticketing Dashboard</h1>
        <p class="text-muted small mb-0">Overview of ticket revenue, real-time stadium capacity, and fixture bookings.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="<?= ADMIN_URL ?>/fixture-create.php" class="btn btn-rfc-primary btn-sm">
            <i class="fas fa-plus me-1"></i> New Fixture
        </a>
        <a href="<?= ADMIN_URL ?>/scan.php" class="btn btn-dark btn-sm">
            <i class="fas fa-qrcode me-1"></i> Gate QR Scanner
        </a>
    </div>
</div>

<!-- KPI Metric Cards -->
<div class="row g-3 mb-4">
    <!-- Total Revenue -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-0 shadow-sm bg-primary text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-white-50 small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Total Revenue</div>
                    <div class="fs-3 fw-bold mt-1"><?= formatMoney($totalRevenue) ?></div>
                </div>
                <div class="p-3 bg-white bg-opacity-20 rounded-circle">
                    <i class="fas fa-coins fs-4"></i>
                </div>
            </div>
            <div class="small text-white-50 mt-2">
                <i class="fas fa-arrow-up me-1"></i> Paid in Ghana Cedis (GH₵)
            </div>
        </div>
    </div>

    <!-- Total Tickets Sold -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-0 shadow-sm bg-success text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-white-50 small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Tickets Issued</div>
                    <div class="fs-3 fw-bold mt-1"><?= number_format($totalTicketsSold) ?></div>
                </div>
                <div class="p-3 bg-white bg-opacity-20 rounded-circle">
                    <i class="fas fa-ticket-alt fs-4"></i>
                </div>
            </div>
            <div class="small text-white-50 mt-2">
                <i class="fas fa-qrcode me-1"></i> Scannable digital passes
            </div>
        </div>
    </div>

    <!-- Tickets Sold Today -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-0 shadow-sm bg-warning text-dark p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-dark small fw-bold text-uppercase opacity-75" style="letter-spacing: 0.5px;">Sold Today</div>
                    <div class="fs-3 fw-bold mt-1"><?= number_format($ticketsSoldToday) ?></div>
                </div>
                <div class="p-3 bg-dark bg-opacity-10 rounded-circle">
                    <i class="fas fa-calendar-check fs-4"></i>
                </div>
            </div>
            <div class="small text-dark opacity-75 mt-2">
                <i class="fas fa-bolt me-1"></i> Active matchday demand
            </div>
        </div>
    </div>

    <!-- Upcoming Matches -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-0 shadow-sm bg-dark text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-white-50 small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Upcoming Fixtures</div>
                    <div class="fs-3 fw-bold mt-1"><?= $activeFixturesCount ?></div>
                </div>
                <div class="p-3 bg-white bg-opacity-10 rounded-circle">
                    <i class="fas fa-futbol fs-4"></i>
                </div>
            </div>
            <div class="small text-white-50 mt-2">
                <i class="fas fa-landmark me-1"></i> Riverside Arena schedule
            </div>
        </div>
    </div>
</div>

<!-- Visual Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card rfc-card p-4 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold brand-font text-dark mb-0">
                    <i class="fas fa-chart-bar text-primary me-2"></i> Match Revenue Overview (GH₵)
                </h5>
                <span class="badge bg-light text-muted border">Ghana Cedis</span>
            </div>
            <div style="position: relative; height: 260px;">
                <canvas id="revenueBarChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card rfc-card p-4 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold brand-font text-dark mb-0">
                    <i class="fas fa-chart-pie text-primary me-2"></i> Sales by Stand
                </h5>
                <span class="badge bg-light text-muted border">Distribution</span>
            </div>
            <div style="position: relative; height: 260px;">
                <canvas id="standDoughnutChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Real-time Stadium Capacity Monitoring Grid -->
<div class="card rfc-card p-4 shadow-sm mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold brand-font text-dark mb-1">
                <i class="fas fa-battery-half text-primary me-2"></i> Real-Time Stadium Capacity Monitor
            </h5>
            <p class="text-muted small mb-0">Live occupancy meters per stand. Alerts trigger automatically when sections are nearly sold out.</p>
        </div>
        <a href="<?= ADMIN_URL ?>/fixtures.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
            Manage Fixtures &amp; Pricing
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th>Match Fixture</th>
                    <th>Stand / Enclosure</th>
                    <th>Gate</th>
                    <th>Price</th>
                    <th>Remaining / Total</th>
                    <th>Occupancy</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sectionCapacities)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No upcoming sections to display.</td></tr>
                <?php else: ?>
                    <?php foreach ($sectionCapacities as $sc): 
                        $rem = (int)$sc['remaining'];
                        $cap = (int)$sc['capacity'];
                        $sold = $cap - $rem;
                        $soldPercent = $cap > 0 ? round(($sold / $cap) * 100) : 0;
                        $status = getCapacityStatus($rem, $cap);
                    ?>
                        <tr>
                            <td>
                                <strong class="text-dark">vs <?= h($sc['opponent']) ?></strong>
                                <div class="text-muted small"><?= formatDate($sc['match_date'], 'd M Y') ?></div>
                            </td>
                            <td>
                                <span class="fw-semibold"><?= h($sc['section_name']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border"><?= h($sc['gate']) ?></span>
                            </td>
                            <td>
                                <strong class="text-dark"><?= formatMoney($sc['price']) ?></strong>
                            </td>
                            <td>
                                <div class="small">
                                    <strong><?= number_format($rem) ?></strong> left / <?= number_format($cap) ?> total
                                </div>
                            </td>
                            <td style="min-width: 140px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar <?= $soldPercent > 85 ? 'bg-danger' : ($soldPercent > 60 ? 'bg-warning' : 'bg-primary') ?>" 
                                             role="progressbar" style="width: <?= $soldPercent ?>%;"></div>
                                    </div>
                                    <span class="small text-muted fw-bold"><?= $soldPercent ?>%</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $status['badge'] ?> rounded-pill px-2 py-1 small">
                                    <?= $status['text'] ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Bookings Table -->
<div class="card rfc-card p-4 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold brand-font text-dark mb-0">
            <i class="fas fa-history text-primary me-2"></i> Recent Supporter Bookings
        </h5>
        <a href="<?= ADMIN_URL ?>/bookings.php" class="btn btn-outline-dark btn-sm rounded-pill px-3">
            View All Bookings <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th>Booking Ref</th>
                    <th>Supporter Name</th>
                    <th>Match</th>
                    <th>Amount (GH₵)</th>
                    <th>Payment Method</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentBookings)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No recent bookings recorded.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentBookings as $rb): ?>
                        <tr>
                            <td><strong class="font-monospace text-primary"><?= h($rb['booking_reference']) ?></strong></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= h($rb['customer_name']) ?></div>
                                <div class="text-muted small"><?= h($rb['customer_email']) ?></div>
                            </td>
                            <td>vs <?= h($rb['opponent']) ?></td>
                            <td><strong class="text-success"><?= formatMoney($rb['total_amount']) ?></strong></td>
                            <td>
                                <span class="badge bg-light text-dark border small text-uppercase">
                                    <?= h(str_replace('_', ' ', $rb['payment_method'])) ?>
                                </span>
                            </td>
                            <td class="small text-muted"><?= formatDate($rb['created_at'], 'd M Y, H:i') ?></td>
                            <td>
                                <a href="<?= ADMIN_URL ?>/booking-detail.php?id=<?= $rb['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Initialize Chart.js Analytics -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Revenue Bar Chart
    const ctxRev = document.getElementById('revenueBarChart');
    if (ctxRev) {
        new Chart(ctxRev, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    label: 'Revenue (GH₵)',
                    data: <?= json_encode($chartRevenueValues) ?>,
                    backgroundColor: '#0284c7',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(val) { return 'GH₵ ' + val; }
                        }
                    }
                }
            }
        });
    }

    // 2. Stand Sales Doughnut Chart
    const ctxStand = document.getElementById('standDoughnutChart');
    if (ctxStand) {
        new Chart(ctxStand, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($standLabels) ?>,
                datasets: [{
                    data: <?= json_encode($standCounts) ?>,
                    backgroundColor: ['#f59e0b', '#0284c7', '#0b1e36', '#ef4444', '#10b981', '#8b5cf6'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { size: 10 } }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
