<?php
require_once '../config/init.php';
require_once '../includes/employee_check.php';

$page_title = 'Employee Dashboard';
$base_path = '../';
$breadcrumb = [];

// Get employee dashboard data
$stats = getEmployeeDashboardData();

$db = new Database();

// Get today's pending loans I created
$db->query("SELECT l.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name 
          FROM loans l 
          JOIN customers c ON l.customer_id = c.id 
          WHERE l.created_by = :employee_id AND l.status = 'pending'
          ORDER BY l.created_at DESC LIMIT 5");
$db->bind(':employee_id', $_SESSION['user_id']);
$pending_loans = $db->resultSet();

// Get today's savings transactions I processed
$db->query("SELECT st.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name,
          sa.account_number
          FROM savings_transactions st
          JOIN savings_accounts sa ON st.account_id = sa.id
          JOIN customers c ON sa.customer_id = c.id
          WHERE st.processed_by = :employee_id 
          AND DATE(st.transaction_date) = CURDATE()
          ORDER BY st.transaction_time DESC LIMIT 10");
$db->bind(':employee_id', $_SESSION['user_id']);
$today_transactions = $db->resultSet();

// Get today's loan collections I processed
$db->query("SELECT lr.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name,
          l.loan_number
          FROM loan_repayments lr
          JOIN loans l ON lr.loan_id = l.id
          JOIN customers c ON l.customer_id = c.id
          WHERE lr.processed_by = :employee_id 
          AND DATE(lr.payment_date) = CURDATE()
          ORDER BY lr.payment_time DESC LIMIT 10");
$db->bind(':employee_id', $_SESSION['user_id']);
$today_repayments = $db->resultSet();

// Get my recent customers
$db->query("SELECT * FROM customers 
          WHERE created_by = :employee_id AND status = 'active'
          ORDER BY created_at DESC LIMIT 5");
$db->bind(':employee_id', $_SESSION['user_id']);
$my_customers = $db->resultSet();

include '../includes/header.php';
?>

<!-- Welcome Message -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info">
            <h5 class="mb-0">
                <i class="bi bi-hand-wave"></i> 
                Welcome back, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>!
                <small class="text-muted ms-3">
                    <i class="bi bi-calendar"></i> <?php echo date('l, F d, Y'); ?>
                </small>
            </h5>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">My Customers</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['my_customers']); ?></h3>
                    </div>
                    <i class="bi bi-people display-6 text-white-50"></i>
                </div>
                <hr class="bg-white-50">
                <small class="text-white-50">
                    Total active: <?php echo number_format($stats['total_customers']); ?>
                </small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">Today's Collections</h6>
                        <h3 class="mb-0"><?php echo formatMoney($stats['my_today_collections']); ?></h3>
                    </div>
                    <i class="bi bi-cash-coin display-6 text-white-50"></i>
                </div>
                <hr class="bg-white-50">
                <small class="text-white-50">
                    All collections today: <?php echo formatMoney($stats['today_collections']); ?>
                </small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">My Loans</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['my_loans']); ?></h3>
                    </div>
                    <i class="bi bi-file-text display-6 text-white-50"></i>
                </div>
                <hr class="bg-white-50">
                <small class="text-white-50">
                    Pending approval: <?php echo $stats['my_pending_loans']; ?>
                </small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">Today's Deposits</h6>
                        <h3 class="mb-0"><?php echo formatMoney($stats['my_today_deposits']); ?></h3>
                    </div>
                    <i class="bi bi-piggy-bank display-6 text-white-50"></i>
                </div>
                <hr class="bg-white-50">
                <small class="text-white-50">
                    Total savings: <?php echo formatMoney($stats['total_savings']); ?>
                </small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Today's Repayments -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-cash-coin"></i> Today's Repayments</h5>
                <a href="collections/index.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (count($today_repayments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Customer</th>
                                    <th>Loan #</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($today_repayments as $repayment): ?>
                                <tr>
                                    <td><small><?php echo date('h:i A', strtotime($repayment['payment_time'])); ?></small></td>
                                    <td><?php echo htmlspecialchars($repayment['customer_name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($repayment['loan_number']); ?></code></td>
                                    <td class="text-success fw-bold"><?php echo formatMoney($repayment['amount']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-success">
                                <tr>
                                    <td colspan="3"><strong>Total</strong></td>
                                    <td>
                                        <strong>
                                            <?php 
                                            $total_repayments = array_sum(array_column($today_repayments, 'amount'));
                                            echo formatMoney($total_repayments);
                                            ?>
                                        </strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="bi bi-inbox display-4 text-muted"></i>
                        <p class="text-muted mb-0">No repayments processed today</p>
                        <a href="loans/repay.php" class="btn btn-sm btn-outline-success mt-2">
                            <i class="bi bi-cash-coin"></i> Record Repayment
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Today's Savings Transactions -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-piggy-bank"></i> Today's Savings Transactions</h5>
                <a href="savings/index.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (count($today_transactions) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Customer</th>
                                    <th>Account #</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($today_transactions as $trans): ?>
                                <tr>
                                    <td><small><?php echo date('h:i A', strtotime($trans['transaction_time'])); ?></small></td>
                                    <td><?php echo htmlspecialchars($trans['customer_name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($trans['account_number']); ?></code></td>
                                    <td>
                                        <span class="badge bg-<?php echo $trans['transaction_type'] == 'deposit' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($trans['transaction_type']); ?>
                                        </span>
                                    </td>
                                    <td class="<?php echo $trans['transaction_type'] == 'deposit' ? 'text-success' : 'text-danger'; ?> fw-bold">
                                        <?php echo formatMoney($trans['amount']); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4"><strong>Total Deposits</strong></td>
                                    <td class="text-success fw-bold">
                                        <?php 
                                        $total_deposits = 0;
                                        foreach ($today_transactions as $trans) {
                                            if ($trans['transaction_type'] == 'deposit') {
                                                $total_deposits += $trans['amount'];
                                            }
                                        }
                                        echo formatMoney($total_deposits);
                                        ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="bi bi-inbox display-4 text-muted"></i>
                        <p class="text-muted mb-0">No savings transactions today</p>
                        <a href="savings/deposit.php" class="btn btn-sm btn-outline-success mt-2">
                            <i class="bi bi-plus-circle"></i> Record Deposit
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Pending Loan Applications -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-hourglass-split"></i> My Pending Loan Applications</h5>
                <a href="loans/index.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (count($pending_loans) > 0): ?>
                    <?php foreach ($pending_loans as $loan): ?>
                    <div class="border rounded p-3 mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($loan['customer_name']); ?></strong>
                                <br>
                                <small class="text-muted">
                                    Loan: <code><?php echo htmlspecialchars($loan['loan_number']); ?></code> | 
                                    Amount: <?php echo formatMoney($loan['principal_amount']); ?> | 
                                    <?php echo $loan['duration_months']; ?> months |
                                    Monthly: <?php echo formatMoney($loan['monthly_payment']); ?>
                                </small>
                            </div>
                            <span class="badge bg-warning">Pending Approval</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="bi bi-check-circle display-4 text-success"></i>
                        <p class="text-muted mb-0">No pending loan applications</p>
                        <a href="loans/create.php" class="btn btn-sm btn-outline-primary mt-2">
                            <i class="bi bi-file-plus"></i> New Loan Application
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- My Customers -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-person-lines-fill"></i> My Recent Customers</h5>
                <a href="customers/index.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (count($my_customers) > 0): ?>
                    <?php foreach ($my_customers as $customer): ?>
                    <div class="d-flex align-items-center mb-3 p-2 border rounded">
                        <div class="flex-shrink-0">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px; font-weight: 600; font-size: 0.85rem;">
                                <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h6>
                            <small class="text-muted">
                                <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($customer['phone']); ?>
                                <span class="ms-2">
                                    <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($customer['city'] ?? 'N/A'); ?>
                                </span>
                            </small>
                        </div>
                        <a href="customers/view.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="bi bi-people display-4 text-muted"></i>
                        <p class="text-muted mb-0">No customers assigned yet</p>
                        <a href="customers/add.php" class="btn btn-sm btn-primary mt-2">
                            <i class="bi bi-person-plus"></i> Add Your First Customer
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning-charge"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-3">
                        <a href="customers/add.php" class="btn btn-primary w-100">
                            <i class="bi bi-person-plus"></i> New Customer
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="loans/create.php" class="btn btn-success w-100">
                            <i class="bi bi-file-plus"></i> New Loan Application
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="savings/deposit.php" class="btn btn-info w-100">
                            <i class="bi bi-plus-circle"></i> Savings Deposit
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="collections/record.php" class="btn btn-warning w-100">
                            <i class="bi bi-cash-coin"></i> Record Collection
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>