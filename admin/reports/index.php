<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Reports Dashboard';
$base_path = '../../';
$breadcrumb = ['Admin' => '../dashboard.php'];

// Get summary statistics for reports
$stats = dbSingle("SELECT 
    (SELECT COUNT(*) FROM customers WHERE status = 'active') as total_customers,
    (SELECT COALESCE(SUM(balance), 0) FROM savings_accounts WHERE status = 'active') as total_savings,
    (SELECT COUNT(*) FROM loans WHERE status IN ('active', 'disbursed')) as active_loans,
    (SELECT COALESCE(SUM(balance), 0) FROM loans WHERE status IN ('active', 'disbursed')) as outstanding_loans,
    (SELECT COALESCE(SUM(amount), 0) FROM loan_repayments WHERE DATE(payment_date) = CURDATE()) as today_collections,
    (SELECT COALESCE(SUM(amount), 0) FROM loan_repayments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())) as month_collections
    FROM dual");

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h4 class="mb-4"><i class="bi bi-graph-up"></i> Reports & Analytics</h4>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card bg-primary text-white">
            <div class="card-body text-center py-3">
                <h6 class="small">Customers</h6>
                <h4 class="mb-0"><?php echo number_format($stats['total_customers']); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-success text-white">
            <div class="card-body text-center py-3">
                <h6 class="small">Total Savings</h6>
                <h4 class="mb-0"><?php echo formatMoney($stats['total_savings']); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-white">
            <div class="card-body text-center py-3">
                <h6 class="small">Active Loans</h6>
                <h4 class="mb-0"><?php echo number_format($stats['active_loans']); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-danger text-white">
            <div class="card-body text-center py-3">
                <h6 class="small">Outstanding</h6>
                <h4 class="mb-0"><?php echo formatMoney($stats['outstanding_loans']); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-info text-white">
            <div class="card-body text-center py-3">
                <h6 class="small">Today</h6>
                <h4 class="mb-0"><?php echo formatMoney($stats['today_collections']); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-dark text-white">
            <div class="card-body text-center py-3">
                <h6 class="small">This Month</h6>
                <h4 class="mb-0"><?php echo formatMoney($stats['month_collections']); ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- Report Cards -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-pie-chart display-4 text-primary"></i>
                <h5 class="mt-3">Portfolio Report</h5>
                <p class="text-muted">View loan portfolio summary, risk analysis, and performance metrics</p>
                <a href="portfolio.php" class="btn btn-primary">
                    <i class="bi bi-eye"></i> View Report
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-cash-stack display-4 text-success"></i>
                <h5 class="mt-3">Profit & Loss</h5>
                <p class="text-muted">Analyze income from interest and fees versus operational expenses</p>
                <a href="profit_loss.php" class="btn btn-success">
                    <i class="bi bi-eye"></i> View Report
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-arrow-left-right display-4 text-info"></i>
                <h5 class="mt-3">Transactions Log</h5>
                <p class="text-muted">View all financial transactions including deposits, withdrawals, and repayments</p>
                <a href="transactions.php" class="btn btn-info">
                    <i class="bi bi-eye"></i> View Report
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-journal-text display-4 text-warning"></i>
                <h5 class="mt-3">Audit Trail</h5>
                <p class="text-muted">Track all system activities, user actions, and changes made</p>
                <a href="audit_trail.php" class="btn btn-warning">
                    <i class="bi bi-eye"></i> View Report
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-people-fill display-4 text-danger"></i>
                <h5 class="mt-3">Customer Report</h5>
                <p class="text-muted">Customer demographics, growth trends, and account summaries</p>
                <a href="customer_report.php" class="btn btn-danger">
                    <i class="bi bi-eye"></i> View Report
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php' ?>