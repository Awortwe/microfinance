<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = 'Repayment Schedule';
$base_path = '../../';
$breadcrumb = [
    'Dashboard' => '../dashboard.php',
    'Loans' => 'index.php'
];

$loan_id = $_GET['loan_id'] ?? 0;

// Get loan details
$loan = dbSingle(
    "SELECT l.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name,
     lp.product_name
     FROM loans l
     JOIN customers c ON l.customer_id = c.id
     JOIN loan_products lp ON l.product_id = lp.id
     WHERE l.id = :id",
    [':id' => $loan_id]
);

if (!$loan) {
    setFlash('error', 'Loan not found');
    redirect('index.php');
}

// Get schedule
$schedule = dbQuery(
    "SELECT * FROM loan_schedule WHERE loan_id = :loan_id ORDER BY installment_number",
    [':loan_id' => $loan_id]
);

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Loan Summary</h6></div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr><td><strong>Loan #:</strong></td><td><code><?php echo htmlspecialchars($loan['loan_number']); ?></code></td></tr>
                    <tr><td><strong>Customer:</strong></td><td><?php echo htmlspecialchars($loan['customer_name']); ?></td></tr>
                    <tr><td><strong>Amount:</strong></td><td><?php echo formatMoney($loan['principal_amount']); ?></td></tr>
                    <tr><td><strong>Total Payable:</strong></td><td><?php echo formatMoney($loan['total_amount']); ?></td></tr>
                    <tr><td><strong>Paid:</strong></td><td class="text-success"><?php echo formatMoney($loan['amount_paid']); ?></td></tr>
                    <tr><td><strong>Balance:</strong></td><td class="text-danger"><?php echo formatMoney($loan['balance']); ?></td></tr>
                    <tr><td><strong>Status:</strong></td><td><?php echo getStatusBadge($loan['status'], 'loan'); ?></td></tr>
                </table>
                
                <?php if (in_array($loan['status'], ['disbursed', 'active'])): ?>
                    <a href="repay.php?loan_id=<?php echo $loan_id; ?>" class="btn btn-success w-100">
                        <i class="bi bi-cash-coin"></i> Make Repayment
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-calendar3"></i> Repayment Schedule</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Due Date</th>
                                <th>Principal</th>
                                <th>Interest</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($schedule) > 0): ?>
                                <?php foreach ($schedule as $installment): ?>
                                <tr class="<?php echo $installment['status'] == 'overdue' ? 'table-danger' : ($installment['status'] == 'paid' ? 'table-success' : ''); ?>">
                                    <td><?php echo $installment['installment_number']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($installment['due_date'])); ?></td>
                                    <td><?php echo formatMoney($installment['principal_amount']); ?></td>
                                    <td><?php echo formatMoney($installment['interest_amount']); ?></td>
                                    <td><?php echo formatMoney($installment['total_amount']); ?></td>
                                    <td><?php echo formatMoney($installment['amount_paid']); ?></td>
                                    <td><?php echo formatMoney($installment['balance_after']); ?></td>
                                    <td>
                                        <?php if ($installment['status'] == 'paid'): ?>
                                            <span class="badge bg-success">Paid</span>
                                        <?php elseif ($installment['status'] == 'partial'): ?>
                                            <span class="badge bg-warning">Partial</span>
                                        <?php elseif ($installment['status'] == 'overdue'): ?>
                                            <span class="badge bg-danger">Overdue</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">Schedule not generated yet</td>
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