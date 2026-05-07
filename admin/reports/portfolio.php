<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Portfolio Report';
$base_path = '../../';
$breadcrumb = [
    'Admin' => '../dashboard.php',
    'Reports' => 'index.php'
];

// Get portfolio summary
$portfolio = dbSingle("SELECT 
    COUNT(*) as total_loans,
    COALESCE(SUM(principal_amount), 0) as total_disbursed,
    COALESCE(SUM(amount_paid), 0) as total_repaid,
    COALESCE(SUM(balance), 0) as total_outstanding,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
    COUNT(CASE WHEN status = 'defaulted' THEN 1 END) as defaulted_count,
    COUNT(CASE WHEN status = 'written_off' THEN 1 END) as written_off_count
    FROM loans");

// Get portfolio by product
$portfolio_by_product = dbQuery(
    "SELECT 
    lp.product_name,
    COUNT(l.id) as loan_count,
    COALESCE(SUM(l.principal_amount), 0) as total_amount,
    COALESCE(SUM(l.balance), 0) as outstanding,
    ROUND(AVG(l.interest_rate), 2) as avg_rate
    FROM loan_products lp
    LEFT JOIN loans l ON lp.id = l.product_id
    GROUP BY lp.id, lp.product_name
    ORDER BY total_amount DESC"
);

include '../../includes/header.php';
?>

<div class="row">
    <!-- Portfolio Summary Cards -->
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6>Total Loans</h6>
                <h3><?php echo number_format($portfolio['total_loans']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Total Disbursed</h6>
                <h3><?php echo formatMoney($portfolio['total_disbursed']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h6>Outstanding</h6>
                <h3><?php echo formatMoney($portfolio['total_outstanding']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6>Total Repaid</h6>
                <h3><?php echo formatMoney($portfolio['total_repaid']); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Loan Status Distribution -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Loan Status Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="loanStatusChart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Portfolio by Product -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Portfolio by Product</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Loans</th>
                                <th>Amount</th>
                                <th>Outstanding</th>
                                <th>Avg Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($portfolio_by_product as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo $product['loan_count']; ?></td>
                                <td><?php echo formatMoney($product['total_amount']); ?></td>
                                <td><?php echo formatMoney($product['outstanding']); ?></td>
                                <td><?php echo $product['avg_rate']; ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (count($portfolio_by_product) == 0): ?>
                            <tr><td colspan="5" class="text-center text-muted">No data</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Risk Metrics -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">Portfolio at Risk</h6>
                <?php 
                $par = $portfolio['total_disbursed'] > 0 ? 
                    ($portfolio['total_outstanding'] / $portfolio['total_disbursed']) * 100 : 0;
                ?>
                <h2 class="text-<?php echo $par > 30 ? 'danger' : 'success'; ?>"><?php echo round($par, 2); ?>%</h2>
                <small class="text-muted">Outstanding / Disbursed</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">Default Rate</h6>
                <?php 
                $default_rate = $portfolio['total_loans'] > 0 ? 
                    ($portfolio['defaulted_count'] / $portfolio['total_loans']) * 100 : 0;
                ?>
                <h2 class="text-<?php echo $default_rate > 10 ? 'danger' : 'success'; ?>"><?php echo round($default_rate, 2); ?>%</h2>
                <small class="text-muted">Defaulted / Total Loans</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">Recovery Rate</h6>
                <?php 
                $recovery_rate = $portfolio['total_disbursed'] > 0 ? 
                    ($portfolio['total_repaid'] / $portfolio['total_disbursed']) * 100 : 0;
                ?>
                <h2 class="text-<?php echo $recovery_rate > 70 ? 'success' : 'warning'; ?>"><?php echo round($recovery_rate, 2); ?>%</h2>
                <small class="text-muted">Repaid / Disbursed</small>
            </div>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('loanStatusChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Active', 'Completed', 'Defaulted', 'Written Off'],
        datasets: [{
            data: [
                <?php echo $portfolio['active_count']; ?>,
                <?php echo $portfolio['completed_count']; ?>,
                <?php echo $portfolio['defaulted_count']; ?>,
                <?php echo $portfolio['written_off_count']; ?>
            ],
            backgroundColor: ['#3498db', '#27ae60', '#e74c3c', '#95a5a6']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

<div class="mt-3">
    <button onclick="window.print()" class="btn btn-secondary">
        <i class="bi bi-printer"></i> Print Report
    </button>
</div>

<?php include '../../includes/footer.php' ?>