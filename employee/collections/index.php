<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = "Today's Collections";
$base_path = '../../';
$breadcrumb = ['Dashboard' => '../dashboard.php'];

$employee_id = $_SESSION['user_id'];

// Get today's loan repayments
$repayments = dbQuery(
    "SELECT lr.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name,
     l.loan_number
     FROM loan_repayments lr
     JOIN loans l ON lr.loan_id = l.id
     JOIN customers c ON l.customer_id = c.id
     WHERE lr.processed_by = :employee_id 
     AND DATE(lr.payment_date) = CURDATE()
     ORDER BY lr.payment_time DESC",
    [':employee_id' => $employee_id]
);

// Get today's savings deposits
$deposits = dbQuery(
    "SELECT st.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name,
     sa.account_number
     FROM savings_transactions st
     JOIN savings_accounts sa ON st.account_id = sa.id
     JOIN customers c ON sa.customer_id = c.id
     WHERE st.processed_by = :employee_id 
     AND DATE(st.transaction_date) = CURDATE()
     AND st.transaction_type = 'deposit'
     ORDER BY st.transaction_time DESC",
    [':employee_id' => $employee_id]
);

// Totals
$total_repayments = array_sum(array_column($repayments, 'amount'));
$total_deposits = array_sum(array_column($deposits, 'amount'));
$grand_total = $total_repayments + $total_deposits;

include '../../includes/header.php';
?>

<!-- Today's Summary -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6 class="text-white-50">Loan Repayments</h6>
                <h3><?php echo formatMoney($total_repayments); ?></h3>
                <small><?php echo count($repayments); ?> transactions</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6 class="text-white-50">Savings Deposits</h6>
                <h3><?php echo formatMoney($total_deposits); ?></h3>
                <small><?php echo count($deposits); ?> transactions</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6 class="text-white-50">Grand Total</h6>
                <h3><?php echo formatMoney($grand_total); ?></h3>
                <small><?php echo count($repayments) + count($deposits); ?> total transactions</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Loan Repayments Today -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-cash-coin"></i> Loan Repayments Today</h6>
                <a href="record.php?type=loan" class="btn btn-sm btn-success">
                    <i class="bi bi-plus"></i> New
                </a>
            </div>
            <div class="card-body">
                <?php if (count($repayments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Customer</th>
                                    <th>Loan #</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($repayments as $rep): ?>
                                <tr>
                                    <td><small><?php echo date('h:i A', strtotime($rep['payment_time'])); ?></small></td>
                                    <td><?php echo htmlspecialchars($rep['customer_name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($rep['loan_number']); ?></code></td>
                                    <td class="text-success fw-bold"><?php echo formatMoney($rep['amount']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="3">Total</td>
                                    <td class="text-success"><?php echo formatMoney($total_repayments); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="bi bi-inbox display-4 text-muted"></i>
                        <p class="text-muted mb-0">No loan repayments today</p>
                        <a href="record.php?type=loan" class="btn btn-sm btn-outline-success mt-2">
                            <i class="bi bi-plus-circle"></i> Record Repayment
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Savings Deposits Today -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-piggy-bank"></i> Savings Deposits Today</h6>
                <a href="record.php?type=savings" class="btn btn-sm btn-success">
                    <i class="bi bi-plus"></i> New
                </a>
            </div>
            <div class="card-body">
                <?php if (count($deposits) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Customer</th>
                                    <th>Account #</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deposits as $dep): ?>
                                <tr>
                                    <td><small><?php echo date('h:i A', strtotime($dep['transaction_time'])); ?></small></td>
                                    <td><?php echo htmlspecialchars($dep['customer_name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($dep['account_number']); ?></code></td>
                                    <td class="text-success fw-bold"><?php echo formatMoney($dep['amount']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="3">Total</td>
                                    <td class="text-success"><?php echo formatMoney($total_deposits); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="bi bi-inbox display-4 text-muted"></i>
                        <p class="text-muted mb-0">No savings deposits today</p>
                        <a href="record.php?type=savings" class="btn btn-sm btn-outline-success mt-2">
                            <i class="bi bi-plus-circle"></i> Record Deposit
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="mt-3 no-print">
    <button onclick="window.print()" class="btn btn-secondary">
        <i class="bi bi-printer"></i> Print Report
    </button>
    <a href="history.php" class="btn btn-info">
        <i class="bi bi-clock-history"></i> Collection History
    </a>
</div>

<?php include '../../includes/footer.php'; ?>