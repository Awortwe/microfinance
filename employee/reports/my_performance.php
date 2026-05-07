<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = 'My Performance';
$base_path = '../../';
$breadcrumb = [
    'Dashboard' => '../dashboard.php',
    'Reports' => 'index.php'
];

$employee_id = $_SESSION['user_id'];
$employee_name = $_SESSION['full_name'];

// Get date range
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Overall statistics
$overall = dbSingle(
    "SELECT 
    (SELECT COUNT(*) FROM customers WHERE created_by = :emp1) as total_customers,
    (SELECT COUNT(*) FROM customers WHERE created_by = :emp2 AND MONTH(created_at) = :month1 AND YEAR(created_at) = :year1) as new_customers_month,
    (SELECT COUNT(*) FROM loans WHERE created_by = :emp3) as total_loans_created,
    (SELECT COUNT(*) FROM loans WHERE created_by = :emp4 AND MONTH(created_at) = :month2 AND YEAR(created_at) = :year2) as loans_created_month,
    (SELECT COALESCE(SUM(principal_amount), 0) FROM loans WHERE created_by = :emp5 AND MONTH(created_at) = :month3 AND YEAR(created_at) = :year3) as loan_amount_month,
    (SELECT COUNT(*) FROM loans WHERE disbursed_by = :emp6 AND MONTH(disbursement_date) = :month4 AND YEAR(disbursement_date) = :year4) as loans_disbursed_month,
    (SELECT COALESCE(SUM(amount), 0) FROM loan_repayments WHERE processed_by = :emp7) as total_collections,
    (SELECT COALESCE(SUM(amount), 0) FROM loan_repayments WHERE processed_by = :emp8 AND MONTH(payment_date) = :month5 AND YEAR(payment_date) = :year5) as collections_month,
    (SELECT COUNT(*) FROM loan_repayments WHERE processed_by = :emp9 AND MONTH(payment_date) = :month6 AND YEAR(payment_date) = :year6) as collections_count_month,
    (SELECT COALESCE(SUM(amount), 0) FROM savings_transactions WHERE processed_by = :emp10 AND transaction_type = 'deposit') as total_deposits,
    (SELECT COALESCE(SUM(amount), 0) FROM savings_transactions WHERE processed_by = :emp11 AND MONTH(transaction_date) = :month7 AND YEAR(transaction_date) = :year7 AND transaction_type = 'deposit') as deposits_month,
    (SELECT COUNT(*) FROM savings_transactions WHERE processed_by = :emp12 AND MONTH(transaction_date) = :month8 AND YEAR(transaction_date) = :year8) as savings_transactions_month
    FROM dual",
    [
        ':emp1' => $employee_id, ':emp2' => $employee_id, ':emp3' => $employee_id,
        ':emp4' => $employee_id, ':emp5' => $employee_id, ':emp6' => $employee_id,
        ':emp7' => $employee_id, ':emp8' => $employee_id, ':emp9' => $employee_id,
        ':emp10' => $employee_id, ':emp11' => $employee_id, ':emp12' => $employee_id,
        ':month1' => $month, ':year1' => $year, ':month2' => $month, ':year2' => $year,
        ':month3' => $month, ':year3' => $year, ':month4' => $month, ':year4' => $year,
        ':month5' => $month, ':year5' => $year, ':month6' => $month, ':year6' => $year,
        ':month7' => $month, ':year7' => $year, ':month8' => $month, ':year8' => $year
    ]
);

// Daily collections for the month
$daily_collections = dbQuery(
    "SELECT 
    DATE(payment_date) as date,
    COALESCE(SUM(amount), 0) as total,
    COUNT(*) as count
    FROM loan_repayments 
    WHERE processed_by = :emp 
    AND MONTH(payment_date) = :month 
    AND YEAR(payment_date) = :year
    GROUP BY DATE(payment_date)
    ORDER BY date",
    [':emp' => $employee_id, ':month' => $month, ':year' => $year]
);

// Monthly performance for the year
$monthly_performance = dbQuery(
    "SELECT 
    MONTH(payment_date) as month,
    COALESCE(SUM(amount), 0) as total,
    COUNT(*) as count
    FROM loan_repayments 
    WHERE processed_by = :emp 
    AND YEAR(payment_date) = :year
    GROUP BY MONTH(payment_date)
    ORDER BY month",
    [':emp' => $employee_id, ':year' => $year]
);

// Best day
$best_day = dbSingle(
    "SELECT DATE(payment_date) as date, SUM(amount) as total, COUNT(*) as count
     FROM loan_repayments 
     WHERE processed_by = :emp 
     GROUP BY DATE(payment_date) 
     ORDER BY total DESC LIMIT 1",
    [':emp' => $employee_id]
);

// Prepare chart data
$months_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$monthly_data = array_fill(0, 12, 0);
foreach ($monthly_performance as $mp) {
    $monthly_data[$mp['month'] - 1] = (float)$mp['total'];
}

// Calculate daily totals
$total_month_collections = 0;
$total_month_transactions = 0;
foreach ($daily_collections as $day) {
    $total_month_collections += $day['total'];
    $total_month_transactions += $day['count'];
}

include '../../includes/header.php';
?>

<!-- Month/Year Selector -->
<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="month" class="form-select">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" 
                                <?php echo $month == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="year" class="form-select">
                    <?php for ($y = date('Y') - 2; $y <= date('Y'); $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter"></i> View
                </button>
            </div>
            <div class="col-md-3">
                <button onclick="window.print()" class="btn btn-secondary w-100">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Employee Info -->
<div class="alert alert-info">
    <h5 class="mb-0">
        <i class="bi bi-person-badge"></i> 
        Performance Report for <strong><?php echo htmlspecialchars($employee_name); ?></strong>
        <span class="ms-3">
            <i class="bi bi-calendar3"></i> 
            <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?>
        </span>
    </h5>
</div>

<!-- Overall Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6>Total Customers</h6>
                <h3><?php echo number_format($overall['total_customers']); ?></h3>
                <small>+<?php echo $overall['new_customers_month']; ?> this month</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Monthly Collections</h6>
                <h3><?php echo formatMoney($overall['collections_month']); ?></h3>
                <small><?php echo $overall['collections_count_month']; ?> repayments</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6>Loans This Month</h6>
                <h3><?php echo number_format($overall['loans_created_month']); ?></h3>
                <small><?php echo formatMoney($overall['loan_amount_month']); ?> disbursed</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h6>Savings Deposits</h6>
                <h3><?php echo formatMoney($overall['deposits_month']); ?></h3>
                <small><?php echo $overall['savings_transactions_month']; ?> transactions</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Monthly Performance Chart -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-graph-up"></i> Monthly Collection Performance (<?php echo $year; ?>)</h6>
            </div>
            <div class="card-body">
                <canvas id="performanceChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Best Performance -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-trophy"></i> Performance Highlights</h6>
            </div>
            <div class="card-body">
                <?php if ($best_day && $best_day['total'] > 0): ?>
                <div class="text-center mb-3">
                    <i class="bi bi-star-fill text-warning display-4"></i>
                    <h6 class="mt-2">Best Day Ever</h6>
                    <h4 class="text-success"><?php echo formatMoney($best_day['total']); ?></h4>
                    <small class="text-muted">
                        <?php echo date('F d, Y', strtotime($best_day['date'])); ?><br>
                        <?php echo $best_day['count']; ?> transactions
                    </small>
                </div>
                <hr>
                <?php endif; ?>
                
                <div class="mb-2">
                    <small class="text-muted">Total Collections (All Time)</small>
                    <h5 class="text-success"><?php echo formatMoney($overall['total_collections']); ?></h5>
                </div>
                
                <div class="mb-2">
                    <small class="text-muted">Total Deposits (All Time)</small>
                    <h5 class="text-info"><?php echo formatMoney($overall['total_deposits']); ?></h5>
                </div>
                
                <div class="mb-2">
                    <small class="text-muted">Total Loans Created</small>
                    <h5><?php echo number_format($overall['total_loans_created']); ?></h5>
                </div>
                
                <div>
                    <small class="text-muted">Loans Disbursed This Month</small>
                    <h5><?php echo number_format($overall['loans_disbursed_month']); ?></h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Daily Performance Table -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-calendar3"></i> Daily Collections - <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr><th>Date</th><th>Day</th><th>Transactions</th><th>Total Collected</th></tr>
                        </thead>
                        <tbody>
                            <?php if (count($daily_collections) > 0): ?>
                                <?php foreach ($daily_collections as $day): ?>
                                <tr>
                                    <td><?php echo date('M d', strtotime($day['date'])); ?></td>
                                    <td><?php echo date('l', strtotime($day['date'])); ?></td>
                                    <td><?php echo $day['count']; ?></td>
                                    <td class="text-success fw-bold"><?php echo formatMoney($day['total']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted">No collections this month</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if (count($daily_collections) > 0): ?>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="2">Total</td>
                                <td><?php echo $total_month_transactions; ?></td>
                                <td class="text-success"><?php echo formatMoney($total_month_collections); ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Performance Summary -->
<div class="row mt-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">Average Daily Collection</h6>
                <?php 
                $days_with_collections = count($daily_collections);
                $avg_daily = $days_with_collections > 0 ? $total_month_collections / $days_with_collections : 0;
                ?>
                <h3><?php echo formatMoney($avg_daily); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">Collection Efficiency</h6>
                <?php 
                $working_days = 0;
                $current = strtotime("$year-$month-01");
                $last = strtotime(date('Y-m-t', $current));
                while ($current <= $last) {
                    if (date('N', $current) < 6) $working_days++;
                    $current = strtotime('+1 day', $current);
                }
                $efficiency = $working_days > 0 ? ($days_with_collections / $working_days) * 100 : 0;
                ?>
                <h3><?php echo round($efficiency, 1); ?>%</h3>
                <small class="text-muted"><?php echo $days_with_collections; ?> of <?php echo $working_days; ?> working days</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">Avg Transaction Value</h6>
                <?php 
                $avg_transaction = $total_month_transactions > 0 ? $total_month_collections / $total_month_transactions : 0;
                ?>
                <h3><?php echo formatMoney($avg_transaction); ?></h3>
            </div>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('performanceChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($months_labels); ?>,
        datasets: [{
            label: 'Collections (GHS)',
            data: <?php echo json_encode($monthly_data); ?>,
            backgroundColor: 'rgba(52, 152, 219, 0.7)',
            borderColor: 'rgba(52, 152, 219, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'GHS ' + value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'GHS ' + context.parsed.y.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>