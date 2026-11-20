<?php
/**
 * Riverside FC - Sales & Capacity Financial Reports
 */

$adminTitle = "Sales Reports";
require_once __DIR__ . '/../includes/admin-header.php';

$db = getDB();

// CSV Export Handler
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="riverside_fc_sales_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Booking Reference', 'Customer Name', 'Email', 'Phone', 'Fixture', 'Amount (GHS)', 'Payment Method', 'Status', 'Date']);

    $exportStmt = $db->query("
        SELECT b.booking_reference, b.customer_name, b.customer_email, b.customer_phone,
               f.opponent, b.total_amount, b.payment_method, b.payment_status, b.created_at
        FROM bookings b
        JOIN fixtures f ON b.fixture_id = f.id
        ORDER BY b.created_at DESC
    ");
    while ($row = $exportStmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// 1. Fixture Sales Summary
$fixtureReportsStmt = $db->query("
    SELECT f.id, f.opponent, f.match_date, f.competition, f.status,
           SUM(tt.capacity) as total_capacity,
           SUM(tt.capacity - tt.remaining) as tickets_sold,
           COALESCE(SUM(b.total_amount), 0) as revenue
    FROM fixtures f
    LEFT JOIN ticket_types tt ON f.id = tt.fixture_id
    LEFT JOIN bookings b ON f.id = b.fixture_id AND b.payment_status = 'paid'
    GROUP BY f.id
    ORDER BY f.match_date DESC
");
$fixtureReports = $fixtureReportsStmt->fetchAll();

// 2. Stand Performance
$standReportStmt = $db->query("
    SELECT tt.name, 
           COUNT(t.id) as tickets_sold,
           COALESCE(SUM(tt.price), 0) as stand_revenue
    FROM tickets t
    JOIN ticket_types tt ON t.ticket_type_id = tt.id
    WHERE t.status != 'cancelled'
    GROUP BY tt.name
    ORDER BY stand_revenue DESC
");
$standReports = $standReportStmt->fetchAll();

// 3. Payment Method Distribution
$payDistStmt = $db->query("
    SELECT payment_method, COUNT(*) as count, SUM(total_amount) as total
    FROM bookings
    WHERE payment_status = 'paid'
    GROUP BY payment_method
");
$payDistribution = $payDistStmt->fetchAll();

// Overall Totals
$grandTotalRevenue = array_sum(array_column($fixtureReports, 'revenue'));
$grandTotalTickets = array_sum(array_column($fixtureReports, 'tickets_sold'));
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
    <div>
        <h1 class="h2 fw-bold brand-font text-dark mb-1">Financial &amp; Stadium Sales Reports</h1>
        <p class="text-muted small mb-0">Detailed breakdown of ticket yield, stand demand, and payment channel distribution.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="?export=csv" class="btn btn-outline-success btn-sm">
            <i class="fas fa-file-csv me-1"></i> Export to CSV
        </a>
        <button type="button" class="btn btn-outline-dark btn-sm" onclick="window.print();">
            <i class="fas fa-print me-1"></i> Print Report
        </button>
    </div>
</div>

<!-- Financial Summary KPIs -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 shadow-sm border-0 bg-primary text-white rounded-3">
            <div class="small text-white-50 text-uppercase fw-bold">Aggregate Revenue</div>
            <div class="fs-3 fw-bold mt-1"><?= formatMoney($grandTotalRevenue) ?></div>
            <div class="small text-white-50 mt-1">Across all home fixtures</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 shadow-sm border-0 bg-success text-white rounded-3">
            <div class="small text-white-50 text-uppercase fw-bold">Total Match Tickets Sold</div>
            <div class="fs-3 fw-bold mt-1"><?= number_format($grandTotalTickets) ?></div>
            <div class="small text-white-50 mt-1">Confirmed digital gate passes</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 shadow-sm border-0 bg-dark text-white rounded-3">
            <div class="small text-white-50 text-uppercase fw-bold">Average Ticket Price</div>
            <div class="fs-3 fw-bold mt-1">
                <?= $grandTotalTickets > 0 ? formatMoney($grandTotalRevenue / $grandTotalTickets) : formatMoney(0) ?>
            </div>
            <div class="small text-white-50 mt-1">Average yield per supporter</div>
        </div>
    </div>
</div>

<!-- Fixture Performance Table -->
<div class="card rfc-card p-4 shadow-sm mb-4">
    <h5 class="fw-bold brand-font text-dark mb-3">
        <i class="fas fa-calendar-alt text-primary me-2"></i> Match Fixture Financial Breakdown
    </h5>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th>Match Fixture</th>
                    <th>Competition</th>
                    <th>Match Date</th>
                    <th>Capacity</th>
                    <th>Tickets Sold</th>
                    <th>Occupancy %</th>
                    <th>Revenue (GH₵)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fixtureReports as $fr): 
                    $cap = (int)$fr['total_capacity'];
                    $sold = (int)$fr['tickets_sold'];
                    $occupancy = $cap > 0 ? round(($sold / $cap) * 100, 1) : 0;
                ?>
                    <tr>
                        <td><strong class="text-dark fs-6">Riverside FC vs <?= h($fr['opponent']) ?></strong></td>
                        <td><span class="badge bg-light text-dark border"><?= h($fr['competition']) ?></span></td>
                        <td class="small text-muted"><?= formatDate($fr['match_date']) ?></td>
                        <td><?= number_format($cap) ?> seats</td>
                        <td><strong class="text-primary"><?= number_format($sold) ?></strong></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                    <div class="progress-bar bg-primary" style="width: <?= min(100, $occupancy) ?>%;"></div>
                                </div>
                                <span class="small fw-bold"><?= $occupancy ?>%</span>
                            </div>
                        </td>
                        <td><strong class="text-success fs-6"><?= formatMoney($fr['revenue']) ?></strong></td>
                        <td>
                            <span class="badge <?= $fr['status'] === 'upcoming' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= ucfirst($fr['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Stand Category Performance -->
    <div class="col-lg-6">
        <div class="card rfc-card p-4 shadow-sm h-100">
            <h5 class="fw-bold brand-font text-dark mb-3">
                <i class="fas fa-layer-group text-primary me-2"></i> Stand Category Performance
            </h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Stand Enclosure</th>
                            <th>Tickets Sold</th>
                            <th>Revenue (GH₵)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($standReports as $sr): ?>
                            <tr>
                                <td><strong><?= h($sr['name']) ?></strong></td>
                                <td><?= number_format($sr['tickets_sold']) ?></td>
                                <td><strong class="text-success"><?= formatMoney($sr['stand_revenue']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Payment Channels Distribution -->
    <div class="col-lg-6">
        <div class="card rfc-card p-4 shadow-sm h-100">
            <h5 class="fw-bold brand-font text-dark mb-3">
                <i class="fas fa-credit-card text-primary me-2"></i> Payment Channels Distribution
            </h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Payment Channel</th>
                            <th>Transactions</th>
                            <th>Total Processed (GH₵)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payDistribution as $pd): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark border text-uppercase">
                                        <?= h(str_replace('_', ' ', $pd['payment_method'])) ?>
                                    </span>
                                </td>
                                <td><?= number_format($pd['count']) ?></td>
                                <td><strong class="text-success"><?= formatMoney($pd['total']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
