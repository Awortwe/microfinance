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
$search = $_GET['search'] ?? '';

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

// Build search condition
$searchCondition = '';
$searchParams = [':emp' => $employee_id, ':date' => $date];
if ($search) {
    $searchCondition = " AND (c.first_name LIKE :search1 OR c.last_name LIKE :search2 OR l.loan_number LIKE :search3)";
    $searchTerm = "%$search%";
    $searchParams[':search1'] = $searchTerm;
    $searchParams[':search2'] = $searchTerm;
    $searchParams[':search3'] = $searchTerm;
}

// Get loan repayments for the day with search
$repayments = dbQuery(
    "SELECT lr.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name, l.loan_number
     FROM loan_repayments lr
     JOIN loans l ON lr.loan_id = l.id
     JOIN customers c ON l.customer_id = c.id
     WHERE lr.processed_by = :emp AND DATE(lr.payment_date) = :date $searchCondition
     ORDER BY lr.payment_time ASC",
    $searchParams
);

// Build search condition for deposits
$depositSearchParams = [':emp' => $employee_id, ':date' => $date];
$depositSearchCondition = '';
if ($search) {
    $depositSearchCondition = " AND (c.first_name LIKE :search1 OR c.last_name LIKE :search2 OR sa.account_number LIKE :search3)";
    $depositSearchParams[':search1'] = $searchTerm;
    $depositSearchParams[':search2'] = $searchTerm;
    $depositSearchParams[':search3'] = $searchTerm;
}

// Get savings deposits for the day with search
$deposits = dbQuery(
    "SELECT st.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name, sa.account_number
     FROM savings_transactions st
     JOIN savings_accounts sa ON st.account_id = sa.id
     JOIN customers c ON sa.customer_id = c.id
     WHERE st.processed_by = :emp AND DATE(st.transaction_date) = :date AND st.transaction_type = 'deposit' $depositSearchCondition
     ORDER BY st.transaction_time ASC",
    $depositSearchParams
);

include '../../includes/header.php';
?>

<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Select Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo $date; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" 
                       placeholder="Search by customer name, loan number..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-filter"></i> View Report
                    </button>
                    <?php if (!empty($search) || $date != date('Y-m-d')): ?>
                    <a href="daily.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                    <?php endif; ?>
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <a href="pdf/daily_pdf.php?date=<?php echo $date; ?>&search=<?php echo urlencode($search); ?>" 
                       class="btn btn-danger" target="_blank">
                        <i class="bi bi-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<h4 class="mb-4">
    Daily Report: <?php echo date('l, F d, Y', strtotime($date)); ?>
    <?php if ($search): ?>
        <small class="text-muted">- Filtered: "<?php echo htmlspecialchars($search); ?>"</small>
    <?php endif; ?>
</h4>

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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-cash-coin"></i> Loan Repayments</h6>
                <small class="text-muted"><?php echo count($repayments); ?> records</small>
            </div>
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
                            <tr><td colspan="3">Total</td><td class="text-success"><?php echo formatMoney(array_sum(array_column($repayments, 'amount'))); ?></td></tr>
                        </tfoot>
                    </table>
                <?php else: ?>
                    <p class="text-muted text-center">No loan repayments found</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Savings Deposits -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-piggy-bank"></i> Savings Deposits</h6>
                <small class="text-muted"><?php echo count($deposits); ?> records</small>
            </div>
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
                            <tr><td colspan="3">Total</td><td class="text-success"><?php echo formatMoney(array_sum(array_column($deposits, 'amount'))); ?></td></tr>
                        </tfoot>
                    </table>
                <?php else: ?>
                    <p class="text-muted text-center">No savings deposits found</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>