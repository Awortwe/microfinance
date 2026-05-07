<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Approve Loans';
$base_path = '../../';
$breadcrumb = ['Admin' => '../dashboard.php'];

// Get pending loans
$pending_loans = dbQuery(
    "SELECT l.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name,
     c.phone as customer_phone, c.customer_code,
     lp.product_name, lp.interest_type
     FROM loans l 
     JOIN customers c ON l.customer_id = c.id 
     JOIN loan_products lp ON l.product_id = lp.id 
     WHERE l.status = 'pending' 
     ORDER BY l.application_date ASC"
);

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-check-circle"></i> Pending Loan Approvals</h5>
    </div>
    <div class="card-body">
        <?php if (count($pending_loans) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Loan #</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Amount</th>
                            <th>Interest</th>
                            <th>Duration</th>
                            <th>Monthly Payment</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_loans as $loan): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($loan['loan_number']); ?></code></td>
                            <td>
                                <strong><?php echo htmlspecialchars($loan['customer_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($loan['customer_code']); ?></small>
                                <br><small><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($loan['customer_phone']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($loan['product_name']); ?></td>
                            <td><strong><?php echo formatMoney($loan['principal_amount']); ?></strong></td>
                            <td>
                                <?php echo $loan['interest_rate']; ?>%<br>
                                <small class="text-muted"><?php echo formatMoney($loan['total_interest']); ?></small><br>
                                <small class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $loan['interest_type'])); ?></small>
                            </td>
                            <td><?php echo $loan['duration_months']; ?> months</td>
                            <td><?php echo formatMoney($loan['monthly_payment']); ?></td>
                            <td><small><?php echo date('M d, Y', strtotime($loan['application_date'])); ?></small></td>
                            <td>
                                <div class="btn-group">
                                    <a href="approve_action.php?id=<?php echo $loan['id']; ?>&action=approve" 
                                       class="btn btn-sm btn-success"
                                       onclick="return confirm('Approve this loan application?')">
                                        <i class="bi bi-check-lg"></i> Approve
                                    </a>
                                    <a href="approve_action.php?id=<?php echo $loan['id']; ?>&action=reject" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Reject this loan application?')">
                                        <i class="bi bi-x-lg"></i> Reject
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="bi bi-check-circle display-4 text-success"></i>
                <p class="text-muted mb-0 mt-2">No pending loan approvals</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>