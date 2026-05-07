<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = 'Customer Details';
$base_path = '../../';
$breadcrumb = [
    'Dashboard' => '../dashboard.php',
    'Customers' => 'index.php'
];

$customer_id = $_GET['id'] ?? 0;

// Get customer details
$customer = dbSingle("SELECT * FROM customers WHERE id = :id", [':id' => $customer_id]);

if (!$customer) {
    setFlash('error', 'Customer not found');
    redirect('index.php');
}

// Get savings accounts
$savings_accounts = dbQuery(
    "SELECT * FROM savings_accounts WHERE customer_id = :id ORDER BY opened_date DESC",
    [':id' => $customer_id]
);

// Get loans
$loans = dbQuery(
    "SELECT l.*, lp.product_name FROM loans l 
     JOIN loan_products lp ON l.product_id = lp.id 
     WHERE l.customer_id = :id ORDER BY l.created_at DESC",
    [':id' => $customer_id]
);

include '../../includes/header.php';
?>

<div class="row">
    <!-- Customer Profile -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="bg-primary text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" 
                     style="width: 80px; height: 80px; font-size: 1.5rem; font-weight: 600;">
                    <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                </div>
                <h4><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h4>
                <code><?php echo htmlspecialchars($customer['customer_code']); ?></code>
                <br>
                <?php echo getStatusBadge($customer['status'], 'customer'); ?>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0">Personal Details</h6></div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr><td width="40%"><strong>Phone:</strong></td><td><?php echo htmlspecialchars($customer['phone']); ?></td></tr>
                    <tr><td><strong>Alt Phone:</strong></td><td><?php echo $customer['alternate_phone'] ? htmlspecialchars($customer['alternate_phone']) : 'N/A'; ?></td></tr>
                    <tr><td><strong>Email:</strong></td><td><?php echo $customer['email'] ?: 'N/A'; ?></td></tr>
                    <tr><td><strong>Gender:</strong></td><td><?php echo $customer['gender'] ?: 'N/A'; ?></td></tr>
                    <tr><td><strong>DOB:</strong></td><td><?php echo $customer['date_of_birth'] ? date('M d, Y', strtotime($customer['date_of_birth'])) : 'N/A'; ?></td></tr>
                    <tr><td><strong>ID Type:</strong></td><td><?php echo $customer['id_type'] ?: 'N/A'; ?></td></tr>
                    <tr><td><strong>ID Number:</strong></td><td><?php echo $customer['id_number'] ?: 'N/A'; ?></td></tr>
                    <tr><td><strong>Occupation:</strong></td><td><?php echo $customer['occupation'] ?: 'N/A'; ?></td></tr>
                    <tr><td><strong>Business:</strong></td><td><?php echo $customer['business_name'] ? htmlspecialchars($customer['business_name']) : 'N/A'; ?></td></tr>
                    <tr><td><strong>Address:</strong></td><td><?php echo $customer['address'] ?: 'N/A'; ?></td></tr>
                    <tr><td><strong>City:</strong></td><td><?php echo $customer['city'] ?: 'N/A'; ?></td></tr>
                    <tr><td><strong>Region:</strong></td><td><?php echo $customer['region'] ?: 'N/A'; ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Accounts & Loans -->
    <div class="col-md-8">
        <!-- Savings Accounts -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-piggy-bank"></i> Savings Accounts</h6>
                <a href="../savings/create.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus"></i> New Account
                </a>
            </div>
            <div class="card-body">
                <?php if (count($savings_accounts) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Account #</th>
                                    <th>Type</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Opened</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($savings_accounts as $acc): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($acc['account_number']); ?></code></td>
                                    <td><?php echo ucfirst($acc['account_type']); ?></td>
                                    <td><strong><?php echo formatMoney($acc['balance']); ?></strong></td>
                                    <td><?php echo getStatusBadge($acc['status'], 'savings'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($acc['opened_date'])); ?></td>
                                    <td>
                                        <a href="../savings/deposit.php?account_id=<?php echo $acc['id']; ?>" class="btn btn-sm btn-success">Deposit</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center mb-0">No savings accounts</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Loans -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-cash-stack"></i> Loans</h6>
                <a href="../loans/create.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus"></i> New Loan
                </a>
            </div>
            <div class="card-body">
                <?php if (count($loans) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Loan #</th>
                                    <th>Product</th>
                                    <th>Amount</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($loans as $loan): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($loan['loan_number']); ?></code></td>
                                    <td><?php echo htmlspecialchars($loan['product_name']); ?></td>
                                    <td><?php echo formatMoney($loan['principal_amount']); ?></td>
                                    <td><?php echo formatMoney($loan['balance']); ?></td>
                                    <td><?php echo getStatusBadge($loan['status'], 'loan'); ?></td>
                                    <td>
                                        <?php if (in_array($loan['status'], ['disbursed', 'active'])): ?>
                                            <a href="../loans/repay.php?loan_id=<?php echo $loan['id']; ?>" class="btn btn-sm btn-success">Repay</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center mb-0">No loans</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-warning">
        <i class="bi bi-pencil"></i> Edit Customer
    </a>
    <a href="index.php" class="btn btn-secondary">Back to List</a>
</div>

<?php include '../../includes/footer.php'; ?>