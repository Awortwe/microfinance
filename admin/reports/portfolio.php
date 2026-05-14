<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Portfolio Report';
$base_path = '../../';
$breadcrumb = [
    'Admin' => '../dashboard.php',
    'Reports' => 'index.php'
];

$search = $_GET['search'] ?? '';

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

// Build product query with search
$productQuery = "SELECT 
    lp.product_name,
    COUNT(l.id) as loan_count,
    COALESCE(SUM(l.principal_amount), 0) as total_amount,
    COALESCE(SUM(l.balance), 0) as outstanding,
    ROUND(AVG(l.interest_rate), 2) as avg_rate
    FROM loan_products lp
    LEFT JOIN loans l ON lp.id = l.product_id
    WHERE 1=1";
$productParams = [];

if ($search) {
    $productQuery .= " AND (lp.product_name LIKE :search1 OR lp.product_code LIKE :search2)";
    $searchTerm = "%$search%";
    $productParams[':search1'] = $searchTerm;
    $productParams[':search2'] = $searchTerm;
}

$productQuery .= " GROUP BY lp.id, lp.product_name ORDER BY total_amount DESC";
$portfolio_by_product = dbQuery($productQuery, $productParams);

include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4><i class="bi bi-pie-chart"></i> Portfolio Report</h4>
            <div class="d-flex gap-2">
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control form-control-sm" 
                           placeholder="Search product name or code..."
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-filter"></i></button>
                    <?php if ($search): ?>
                        <a href="portfolio.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i></a>
                    <?php endif; ?>
                </form>
                <button onclick="window.print()" class="btn btn-secondary btn-sm"><i class="bi bi-printer"></i> Print</button>
                <a href="pdf/portfolio_pdf.php?search=<?php echo urlencode($search); ?>" class="btn btn-danger btn-sm" target="_blank"><i class="bi bi-file-pdf"></i> PDF</a>
            </div>
        </div>
    </div>
</div>

<!-- Portfolio Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6>Total Loans</h6>
                <h3><?php echo number_format($portfolio['total_loans']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Total Disbursed</h6>
                <h3><?php echo formatMoney($portfolio['total_disbursed']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h6>Outstanding</h6>
                <h3><?php echo formatMoney($portfolio['total_outstanding']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6>Total Repaid</h6>
                <h3><?php echo formatMoney($portfolio['total_repaid']); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Loan Status Chart -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Loan Status Distribution</h5></div>
            <div class="card-body">
                <canvas id="loanStatusChart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Portfolio by Product -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Portfolio by Product</h5>
                <?php if ($search): ?>
                    <small class="text-muted">Filtered: "<?php echo htmlspecialchars($search); ?>"</small>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr><th>Product</th><th>Loans</th><th>Amount</th><th>Outstanding</th><th>Avg Rate</th></tr>
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
                            <tr><td colspan="5" class="text-center text-muted">No products found</td></tr>
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
                <?php $par = $portfolio['total_disbursed'] > 0 ? ($portfolio['total_outstanding'] / $portfolio['total_disbursed']) * 100 : 0; ?>
                <h2 class="text-<?php echo $par > 30 ? 'danger' : 'success'; ?>"><?php echo round($par, 2); ?>%</h2>
                <small class="text-muted">Outstanding / Disbursed</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">Default Rate</h6>
                <?php $default_rate = $portfolio['total_loans'] > 0 ? ($portfolio['defaulted_count'] / $portfolio['total_loans']) * 100 : 0; ?>
                <h2 class="text-<?php echo $default_rate > 10 ? 'danger' : 'success'; ?>"><?php echo round($default_rate, 2); ?>%</h2>
                <small class="text-muted">Defaulted / Total Loans</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">Recovery Rate</h6>
                <?php $recovery_rate = $portfolio['total_disbursed'] > 0 ? ($portfolio['total_repaid'] / $portfolio['total_disbursed']) * 100 : 0; ?>
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
            data: [<?php echo $portfolio['active_count']; ?>, <?php echo $portfolio['completed_count']; ?>, <?php echo $portfolio['defaulted_count']; ?>, <?php echo $portfolio['written_off_count']; ?>],
            backgroundColor: ['#3498db', '#27ae60', '#e74c3c', '#95a5a6']
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php include '../../includes/footer.php'; ?>