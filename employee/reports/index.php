<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = 'Reports Dashboard';
$base_path = '../../';
$breadcrumb = ['Dashboard' => '../dashboard.php'];

$employee_id = $_SESSION['user_id'];

// Get summary statistics for reports
$stats = dbSingle(
    "SELECT 
    (SELECT COUNT(*) FROM customers WHERE created_by = :emp1 AND status = 'active') as my_customers,
    (SELECT COUNT(*) FROM loans WHERE created_by = :emp2) as my_loans,
    (SELECT COUNT(*) FROM loans WHERE created_by = :emp3 AND status IN ('active', 'disbursed')) as active_loans,
    (SELECT COALESCE(SUM(amount), 0) FROM loan_repayments WHERE processed_by = :emp4 AND DATE(payment_date) = CURDATE()) as today_collections,
    (SELECT COALESCE(SUM(amount), 0) FROM loan_repayments WHERE processed_by = :emp5 AND MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())) as month_collections,
    (SELECT COALESCE(SUM(amount), 0) FROM savings_transactions WHERE processed_by = :emp6 AND DATE(transaction_date) = CURDATE() AND transaction_type = 'deposit') as today_deposits,
    (SELECT COUNT(*) FROM savings_transactions WHERE processed_by = :emp7 AND DATE(transaction_date) = CURDATE()) as today_transactions
    FROM dual",
    [
        ':emp1' => $employee_id, ':emp2' => $employee_id, ':emp3' => $employee_id,
        ':emp4' => $employee_id, ':emp5' => $employee_id, ':emp6' => $employee_id,
        ':emp7' => $employee_id
    ]
);

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h4 class="mb-4"><i class="bi bi-file-earmark-bar-graph"></i> My Reports</h4>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <i class="bi bi-people display-6"></i>
                <h6 class="mt-2">My Customers</h6>
                <h3><?php echo number_format($stats['my_customers']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <i class="bi bi-cash-stack display-6"></i>
                <h6 class="mt-2">Today's Collections</h6>
                <h3><?php echo formatMoney($stats['today_collections']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <i class="bi bi-piggy-bank display-6"></i>
                <h6 class="mt-2">Today's Deposits</h6>
                <h3><?php echo formatMoney($stats['today_deposits']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <i class="bi bi-graph-up display-6"></i>
                <h6 class="mt-2">Month Collections</h6>
                <h3><?php echo formatMoney($stats['month_collections']); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Report Cards -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-calendar-check display-3 text-primary"></i>
                <h5 class="mt-3">Daily Report</h5>
                <p class="text-muted">View your daily collection summary including loan repayments and savings deposits</p>
                <a href="daily.php" class="btn btn-primary">
                    <i class="bi bi-eye"></i> View Daily Report
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-person-lines-fill display-3 text-success"></i>
                <h5 class="mt-3">Customer Report</h5>
                <p class="text-muted">View your customer list with their savings and loan summaries</p>
                <a href="customer.php" class="btn btn-success">
                    <i class="bi bi-eye"></i> View Customer Report
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-trophy display-3 text-warning"></i>
                <h5 class="mt-3">My Performance</h5>
                <p class="text-muted">Track your performance metrics including collections, customers, and loans</p>
                <a href="my_performance.php" class="btn btn-warning">
                    <i class="bi bi-eye"></i> View Performance
                </a>
            </div>
        </div>
    </div>
</div>

<!-- This Week's Summary -->
<div class="row mt-2">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-activity"></i> This Week's Collections</h6>
            </div>
            <div class="card-body">
                <?php
                $weekly = dbQuery(
                    "SELECT 
                    COALESCE(SUM(amount), 0) as total,
                    COUNT(*) as count,
                    DATE(payment_date) as date
                    FROM loan_repayments 
                    WHERE processed_by = :emp 
                    AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    GROUP BY DATE(payment_date)
                    ORDER BY date DESC",
                    [':emp' => $employee_id]
                );
                ?>
                
                <?php if (count($weekly) > 0): ?>
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Date</th><th>Transactions</th><th>Total</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($weekly as $day): ?>
                            <tr>
                                <td><?php echo date('D, M d', strtotime($day['date'])); ?></td>
                                <td><?php echo $day['count']; ?></td>
                                <td class="text-success"><?php echo formatMoney($day['total']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted text-center">No collections this week</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-star"></i> Quick Performance</h6>
            </div>
            <div class="card-body">
                <?php
                $new_customers = dbSingle(
                    "SELECT COUNT(*) as count FROM customers 
                     WHERE created_by = :emp 
                     AND MONTH(created_at) = MONTH(CURDATE()) 
                     AND YEAR(created_at) = YEAR(CURDATE())",
                    [':emp' => $employee_id]
                )['count'];
                
                $new_loans = dbSingle(
                    "SELECT COUNT(*) as count, COALESCE(SUM(principal_amount), 0) as total 
                     FROM loans WHERE created_by = :emp 
                     AND MONTH(created_at) = MONTH(CURDATE()) 
                     AND YEAR(created_at) = YEAR(CURDATE())",
                    [':emp' => $employee_id]
                );
                ?>
                
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <h4 class="text-primary"><?php echo $new_customers; ?></h4>
                        <small class="text-muted">New Customers<br>This Month</small>
                    </div>
                    <div class="col-6 mb-3">
                        <h4 class="text-success"><?php echo $new_loans['count']; ?></h4>
                        <small class="text-muted">New Loans<br>This Month</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-info"><?php echo formatMoney($stats['today_collections']); ?></h4>
                        <small class="text-muted">Collected<br>Today</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-warning"><?php echo $stats['today_transactions']; ?></h4>
                        <small class="text-muted">Transactions<br>Today</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>