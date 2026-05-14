<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Transactions Log';
$base_path = '../../';
$breadcrumb = [
    'Admin' => '../dashboard.php',
    'Reports' => 'index.php'
];

$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

// Build combined query with search
$searchCondition = '';
$searchParams = [
    ':date_from1' => $date_from, ':date_to1' => $date_to,
    ':date_from2' => $date_from, ':date_to2' => $date_to
];

if ($search) {
    $searchCondition = " AND (c1.first_name LIKE :s1 OR c1.last_name LIKE :s2 OR l.loan_number LIKE :s3
                       OR c2.first_name LIKE :s4 OR c2.last_name LIKE :s5 OR sa.account_number LIKE :s6)";
    $st = "%$search%";
    $searchParams[':s1'] = $st; $searchParams[':s2'] = $st; $searchParams[':s3'] = $st;
    $searchParams[':s4'] = $st; $searchParams[':s5'] = $st; $searchParams[':s6'] = $st;
}

$query = "
    SELECT 'Loan Repayment' as transaction_type, lr.amount, lr.payment_date as transaction_date, lr.payment_time as transaction_time,
    CONCAT(c1.first_name, ' ', c1.last_name) as customer_name, l.loan_number as reference, u.full_name as processed_by, lr.payment_method
    FROM loan_repayments lr
    JOIN loans l ON lr.loan_id = l.id
    JOIN customers c1 ON l.customer_id = c1.id
    LEFT JOIN users u ON lr.processed_by = u.id
    WHERE lr.payment_date BETWEEN :date_from1 AND :date_to1 $searchCondition
    
    UNION ALL
    
    SELECT CONCAT('Savings ', st.transaction_type) as transaction_type, st.amount, st.transaction_date, st.transaction_time,
    CONCAT(c2.first_name, ' ', c2.last_name) as customer_name, sa.account_number as reference, u2.full_name as processed_by, st.payment_method
    FROM savings_transactions st
    JOIN savings_accounts sa ON st.account_id = sa.id
    JOIN customers c2 ON sa.customer_id = c2.id
    LEFT JOIN users u2 ON st.processed_by = u2.id
    WHERE st.transaction_date BETWEEN :date_from2 AND :date_to2 $searchCondition
    
    ORDER BY transaction_date DESC, transaction_time DESC
    LIMIT 300";

$transactions = dbQuery($query, $searchParams);
$total_amount = array_sum(array_column($transactions, 'amount'));

$pdf_params = http_build_query(['date_from' => $date_from, 'date_to' => $date_to, 'search' => $search]);

include '../../includes/header.php';
?>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" 
                       placeholder="Search customer, reference..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-filter"></i> Filter</button>
                    <a href="transactions.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Clear</a>
                    <button onclick="window.print()" class="btn btn-secondary"><i class="bi bi-printer"></i> Print</button>
                    <a href="pdf/transactions_pdf.php?<?php echo $pdf_params; ?>" class="btn btn-danger" target="_blank"><i class="bi bi-file-pdf"></i> PDF</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-arrow-left-right"></i> All Transactions</h5>
        <small class="text-muted"><?php echo count($transactions); ?> records</small>
    </div>
    <div class="card-body">
        <?php if ($search): ?>
        <div class="alert alert-info py-2 mb-3">
            <small>Filtered by: "<strong><?php echo htmlspecialchars($search); ?></strong>" - <?php echo count($transactions); ?> results</small>
        </div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Date/Time</th>
                        <th>Type</th>
                        <th>Customer</th>
                        <th>Reference</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Processed By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transactions) > 0): ?>
                        <?php foreach ($transactions as $trans): ?>
                        <tr>
                            <td>
                                <small><?php echo date('M d, Y', strtotime($trans['transaction_date'])); ?><br>
                                <?php echo date('h:i A', strtotime($trans['transaction_time'])); ?></small>
                            </td>
                            <td>
                                <?php
                                $badge = 'secondary';
                                if (strpos($trans['transaction_type'], 'Repayment') !== false) $badge = 'success';
                                if (strpos($trans['transaction_type'], 'deposit') !== false) $badge = 'primary';
                                if (strpos($trans['transaction_type'], 'withdrawal') !== false) $badge = 'danger';
                                ?>
                                <span class="badge bg-<?php echo $badge; ?>"><?php echo htmlspecialchars($trans['transaction_type']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($trans['customer_name']); ?></td>
                            <td><code><?php echo htmlspecialchars($trans['reference']); ?></code></td>
                            <td><strong><?php echo formatMoney($trans['amount']); ?></strong></td>
                            <td><?php echo ucfirst($trans['payment_method']); ?></td>
                            <td><?php echo htmlspecialchars($trans['processed_by'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-4">No transactions found</td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if (count($transactions) > 0): ?>
                <tfoot class="table-light fw-bold">
                    <tr><td colspan="4">Total (<?php echo count($transactions); ?> transactions)</td><td><?php echo formatMoney($total_amount); ?></td><td colspan="2"></td></tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>