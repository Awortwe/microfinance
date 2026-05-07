<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = 'Daily Report';
$base_path = '../../';
$breadcrumb = [
    'Dashboard' => '../dashboard.php',
    'Reports' => 'index.php'
];

$employee_id = $_SESSION['user_id'];
$date = $_GET['date'] ?? date('Y-m-d');

// My collections for the day
$summary = dbSingle(
    "SELECT 
    (SELECT COALESCE(SUM(amount), 0) FROM loan_repayments WHERE processed_by = :id1 AND DATE(payment_date) = :date1) as loan_collections,
    (SELECT COALESCE(SUM(amount), 0) FROM savings_transactions WHERE processed_by = :id2 AND DATE(transaction_date) = :date2 AND transaction_type = 'deposit') as savings_deposits,
    (SELECT COALESCE(SUM(amount), 0) FROM savings_transactions WHERE processed_by = :id3 AND DATE(transaction_date) = :date3 AND transaction_type = 'withdrawal') as savings_withdrawals,
    (SELECT COUNT(*) FROM loan_repayments WHERE processed_by = :id4 AND DATE(payment_date) = :date4) as loan_count,
    (SELECT COUNT(*) FROM savings_transactions WHERE processed_by = :id5 AND DATE(transaction_date) = :date5) as savings_count
    FROM dual",
    [
        ':id1' => $employee_id, ':date1' => $date,
        ':id2' => $employee_id, ':date2' => $date,
        ':id3' => $employee_id, ':date3' => $date,
        ':id4' => $employee_id, ':date4' => $date,
        ':id5' => $employee_id, ':date5' => $date
    ]
);

// Get loan repayments for the day
$repayments = dbQuery(
    "SELECT lr.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name, l.loan_number
     FROM loan_repayments lr
     JOIN loans l ON lr.loan_id = l.id
     JOIN customers c ON l.customer_id = c.id
     WHERE lr.processed_by = :emp AND DATE(lr.payment_date) = :date
     ORDER BY lr.payment_time ASC",
    [':emp' => $employee_id, ':date' => $date]
);

// Get savings deposits for the day
$deposits = dbQuery(
    "SELECT st.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name, sa.account_number
     FROM savings_transactions st
     JOIN savings_accounts sa ON st.account_id = sa.id
     JOIN customers c ON sa.customer_id = c.id
     WHERE st.processed_by = :emp AND DATE(st.transaction_date) = :date AND st.transaction_type = 'deposit'
     ORDER BY st.transaction_time ASC",
    [':emp' => $employee_id, ':date' => $date]
);

// Get withdrawals for the day
$withdrawals = dbQuery(
    "SELECT st.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name, sa.account_number
     FROM savings_transactions st
     JOIN savings_accounts sa ON st.account_id = sa.id
     JOIN customers c ON sa.customer_id = c.id
     WHERE st.processed_by = :emp AND DATE(st.transaction_date) = :date AND st.transaction_type = 'withdrawal'
     ORDER BY st.transaction_time ASC",
    [':emp' => $employee_id, ':date' => $date]
);

include '../../includes/header.php';
?>

<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Select Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo $date; ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-filter"></i> View Report
                </button>
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </form>
    </div>
</div>

<h4 class="mb-4">Daily Report: <?php echo date('l, F d, Y', strtotime($date)); ?></h4>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Loan Collections</h6>
                <h3><?php echo formatMoney($summary['loan_collections']); ?></h3>
                <small><?php echo $summary['loan_count']; ?> payments</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6>Savings Deposits</h6>
                <h3><?php echo formatMoney($summary['savings_deposits']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h6>Withdrawals</h6>
                <h3><?php echo formatMoney($summary['savings_withdrawals']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6>Total Transactions</h6>
                <h3><?php echo $summary['loan_count'] + $summary['savings_count']; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Loan Repayments -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-cash-coin"></i> Loan Repayments</h6></div>
            <div class="card-body">
                <?php if (count($repayments) > 0): ?>
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Time</th><th>Customer</th><th>Loan #</th><th>Amount</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($repayments as $rep): ?>
                            <tr>
                                <td><?php echo date('h:i A', strtotime($rep['payment_time'])); ?></td>
                                <td><?php echo htmlspecialchars($rep['customer_name']); ?></td>
                                <td><code><?php echo htmlspecialchars($rep['loan_number']); ?></code></td>
                                <td class="text-success"><?php echo formatMoney($rep['amount']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr><td colspan="3">Total</td><td class="text-success"><?php echo formatMoney($summary['loan_collections']); ?></td></tr>
                        </tfoot>
                    </table>
                <?php else: ?>
                    <p class="text-muted text-center">No loan repayments</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Savings Deposits -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-piggy-bank"></i> Savings Deposits</h6></div>
            <div class="card-body">
                <?php if (count($deposits) > 0): ?>
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Time</th><th>Customer</th><th>Account #</th><th>Amount</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deposits as $dep): ?>
                            <tr>
                                <td><?php echo date('h:i A', strtotime($dep['transaction_time'])); ?></td>
                                <td><?php echo htmlspecialchars($dep['customer_name']); ?></td>
                                <td><code><?php echo htmlspecialchars($dep['account_number']); ?></code></td>
                                <td class="text-success"><?php echo formatMoney($dep['amount']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr><td colspan="3">Total</td><td class="text-success"><?php echo formatMoney($summary['savings_deposits']); ?></td></tr>
                        </tfoot>
                    </table>
                <?php else: ?>
                    <p class="text-muted text-center">No savings deposits</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>