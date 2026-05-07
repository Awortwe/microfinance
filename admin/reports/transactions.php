<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Transactions Log';
$base_path = '../../';
$breadcrumb = [
    'Admin' => '../dashboard.php',
    'Reports' => 'index.php'
];

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$type = $_GET['type'] ?? '';

// Build combined query
$query = "
    SELECT 
        'Loan Repayment' as transaction_type,
        lr.amount,
        lr.payment_date as transaction_date,
        lr.payment_time as transaction_time,
        CONCAT(c.first_name, ' ', c.last_name) as customer_name,
        l.loan_number as reference,
        u.full_name as processed_by,
        lr.payment_method
    FROM loan_repayments lr
    JOIN loans l ON lr.loan_id = l.id
    JOIN customers c ON l.customer_id = c.id
    LEFT JOIN users u ON lr.processed_by = u.id
    WHERE lr.payment_date BETWEEN :date_from1 AND :date_to1
    
    UNION ALL
    
    SELECT 
        CONCAT('Savings ', st.transaction_type) as transaction_type,
        st.amount,
        st.transaction_date,
        st.transaction_time,
        CONCAT(c.first_name, ' ', c.last_name) as customer_name,
        sa.account_number as reference,
        u.full_name as processed_by,
        st.payment_method
    FROM savings_transactions st
    JOIN savings_accounts sa ON st.account_id = sa.id
    JOIN customers c ON sa.customer_id = c.id
    LEFT JOIN users u ON st.processed_by = u.id
    WHERE st.transaction_date BETWEEN :date_from2 AND :date_to2
    
    ORDER BY transaction_date DESC, transaction_time DESC
    LIMIT 200";

$transactions = dbQuery($query, [
    ':date_from1' => $date_from, ':date_to1' => $date_to,
    ':date_from2' => $date_from, ':date_to2' => $date_to
]);

// Totals
$total_amount = array_sum(array_column($transactions, 'amount'));

include '../../includes/header.php';
?>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-arrow-left-right"></i> All Transactions</h5>
        <button onclick="window.print()" class="btn btn-sm btn-secondary">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
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
                                <small>
                                    <?php echo date('M d, Y', strtotime($trans['transaction_date'])); ?>
                                    <br>
                                    <?php echo date('h:i A', strtotime($trans['transaction_time'])); ?>
                                </small>
                            </td>
                            <td>
                                <?php
                                $badge_class = 'secondary';
                                if (strpos($trans['transaction_type'], 'Repayment') !== false) $badge_class = 'success';
                                if (strpos($trans['transaction_type'], 'deposit') !== false) $badge_class = 'primary';
                                if (strpos($trans['transaction_type'], 'withdrawal') !== false) $badge_class = 'danger';
                                ?>
                                <span class="badge bg-<?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars($trans['transaction_type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($trans['customer_name']); ?></td>
                            <td><code><?php echo htmlspecialchars($trans['reference']); ?></code></td>
                            <td><strong><?php echo formatMoney($trans['amount']); ?></strong></td>
                            <td><?php echo ucfirst($trans['payment_method']); ?></td>
                            <td><?php echo htmlspecialchars($trans['processed_by'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="bi bi-inbox display-4 text-muted"></i>
                                <p class="text-muted mb-0 mt-2">No transactions found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if (count($transactions) > 0): ?>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="4">Total (<?php echo count($transactions); ?> transactions)</td>
                        <td><?php echo formatMoney($total_amount); ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>