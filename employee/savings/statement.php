<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = 'Account Statement';
$base_path = '../../';
$breadcrumb = [
    'Dashboard' => '../dashboard.php',
    'Savings' => 'index.php'
];

$account_id = $_GET['account_id'] ?? 0;

// Get account details
$account = dbSingle(
    "SELECT sa.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name,
     c.phone, c.customer_code, c.address
     FROM savings_accounts sa
     JOIN customers c ON sa.customer_id = c.id
     WHERE sa.id = :id",
    [':id' => $account_id]
);

if (!$account) {
    setFlash('error', 'Account not found');
    redirect('index.php');
}

// Get transactions
$transactions = dbQuery(
    "SELECT * FROM savings_transactions 
     WHERE account_id = :account_id 
     ORDER BY transaction_date DESC, transaction_time DESC",
    [':account_id' => $account_id]
);

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Account Information</h6></div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr><td><strong>Account #:</strong></td><td><code><?php echo htmlspecialchars($account['account_number']); ?></code></td></tr>
                    <tr><td><strong>Customer:</strong></td><td><?php echo htmlspecialchars($account['customer_name']); ?></td></tr>
                    <tr><td><strong>Phone:</strong></td><td><?php echo htmlspecialchars($account['phone']); ?></td></tr>
                    <tr><td><strong>Type:</strong></td><td><?php echo ucfirst($account['account_type']); ?></td></tr>
                    <tr><td><strong>Balance:</strong></td><td><strong class="text-success"><?php echo formatMoney($account['balance']); ?></strong></td></tr>
                    <tr><td><strong>Status:</strong></td><td><?php echo getStatusBadge($account['status'], 'savings'); ?></td></tr>
                    <tr><td><strong>Opened:</strong></td><td><?php echo date('M d, Y', strtotime($account['opened_date'])); ?></td></tr>
                </table>
                
                <div class="d-grid gap-2 mt-3">
                    <a href="deposit.php?account_id=<?php echo $account_id; ?>" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Deposit
                    </a>
                    <a href="withdraw.php?account_id=<?php echo $account_id; ?>" class="btn btn-warning">
                        <i class="bi bi-dash-circle"></i> Withdraw
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h6 class="mb-0"><i class="bi bi-list-ul"></i> Transaction History</h6>
                <button onclick="window.print()" class="btn btn-sm btn-secondary no-print">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($transactions) > 0): ?>
                                <?php foreach ($transactions as $trans): ?>
                                <tr>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($trans['transaction_date'])); ?>
                                        <br><small class="text-muted"><?php echo date('h:i A', strtotime($trans['transaction_time'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $trans['transaction_type'] == 'deposit' ? 'success' : 
                                                ($trans['transaction_type'] == 'withdrawal' ? 'danger' : 'info'); 
                                        ?>">
                                            <?php echo ucfirst($trans['transaction_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($trans['description'] ?? '-'); ?></td>
                                    <td class="<?php echo in_array($trans['transaction_type'], ['deposit', 'interest']) ? 'text-success' : 'text-danger'; ?>">
                                        <strong>
                                            <?php echo in_array($trans['transaction_type'], ['deposit', 'interest']) ? '+' : '-'; ?>
                                            <?php echo formatMoney($trans['amount']); ?>
                                        </strong>
                                    </td>
                                    <td><?php echo formatMoney($trans['balance_after']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No transactions yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>