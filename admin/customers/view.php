<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Customer Details';
$base_path = '../../';
$breadcrumb = [
    'Admin' => '../dashboard.php',
    'Customers' => 'index.php'
];

$customer_id = $_GET['id'] ?? 0;

// Get customer details
$customer = dbSingle(
    "SELECT c.*, CONCAT(u.full_name) as created_by_name 
     FROM customers c 
     LEFT JOIN users u ON c.created_by = u.id 
     WHERE c.id = :id",
    [':id' => $customer_id]
);

if (!$customer) {
    setFlash('error', 'Customer not found');
    redirect('index.php');
}

// Get customer savings accounts
$savings_accounts = dbQuery(
    "SELECT * FROM savings_accounts WHERE customer_id = :customer_id ORDER BY opened_date DESC",
    [':customer_id' => $customer_id]
);

// Get customer loans
$loans = dbQuery(
    "SELECT l.*, lp.product_name 
     FROM loans l 
     JOIN loan_products lp ON l.product_id = lp.id 
     WHERE l.customer_id = :customer_id 
     ORDER BY l.created_at DESC",
    [':customer_id' => $customer_id]
);

include '../../includes/header.php';
?>

<div class="row">
    <!-- Customer Profile -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="bg-primary text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" 
                     style="width: 100px; height: 100px; font-size: 2rem; font-weight: 600;">
                    <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                </div>
                <h4><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h4>
                <p class="text-muted"><?php echo htmlspecialchars($customer['customer_code']); ?></p>
                <?php echo getStatusBadge($customer['status'], 'customer'); ?>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0">Personal Information</h6></div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr><td width="40%"><strong>Phone:</strong></td><td><?php echo htmlspecialchars($customer['phone']); ?></td></tr>
                    <tr><td><strong>Alt Phone:</strong></td><td><?php echo $customer['alternate_phone'] ? htmlspecialchars($customer['alternate_phone']) : 'N/A'; ?></td></tr>
                    <tr><td><strong>Email:</strong></td><td><?php echo $customer['email'] ? htmlspecialchars($customer['email']) : 'N/A'; ?></td></tr>
                    <tr><td><strong>Gender:</strong></td><td><?php echo $customer['gender'] ?? 'N/A'; ?></td></tr>
                    <tr><td><strong>DOB:</strong></td><td><?php echo $customer['date_of_birth'] ? date('M d, Y', strtotime($customer['date_of_birth'])) : 'N/A'; ?></td></tr>
                    <tr><td><strong>Marital Status:</strong></td><td><?php echo $customer['marital_status'] ?? 'N/A'; ?></td></tr>
                    <tr><td><strong>ID Type:</strong></td><td><?php echo $customer['id_type'] ?? 'N/A'; ?></td></tr>
                    <tr><td><strong>ID Number:</strong></td><td><?php echo $customer['id_number'] ?? 'N/A'; ?></td></tr>
                    <tr><td><strong>Occupation:</strong></td><td><?php echo $customer['occupation'] ?? 'N/A'; ?></td></tr>
                    <tr><td><strong>Business:</strong></td><td><?php echo $customer['business_name'] ? htmlspecialchars($customer['business_name']) : 'N/A'; ?></td></tr>
                    <tr><td><strong>Address:</strong></td><td><?php echo $customer['address'] ? htmlspecialchars($customer['address']) : 'N/A'; ?></td></tr>
                    <tr><td><strong>City:</strong></td><td><?php echo $customer['city'] ?? 'N/A'; ?></td></tr>
                    <tr><td><strong>Region:</strong></td><td><?php echo $customer['region'] ?? 'N/A'; ?></td></tr>
                    <tr><td><strong>Created By:</strong></td><td><?php echo htmlspecialchars($customer['created_by_name'] ?? 'N/A'); ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Accounts & Loans -->
    <div class="col-md-8">
        <!-- Savings Accounts -->
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-piggy-bank"></i> Savings Accounts</h6></div>
            <div class="card-body">
                <?php if (count($savings_accounts) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Account #</th>
                                    <th>Type</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Opened Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($savings_accounts as $account): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($account['account_number']); ?></code></td>
                                    <td><?php echo ucfirst($account['account_type']); ?></td>
                                    <td><strong><?php echo formatMoney($account['balance']); ?></strong></td>
                                    <td><?php echo getStatusBadge($account['status'], 'savings'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($account['opened_date'])); ?></td>
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
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-cash-stack"></i> Loans</h6></div>
            <div class="card-body">
                <?php if (count($loans) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Loan #</th>
                                    <th>Product</th>
                                    <th>Amount</th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($loans as $loan): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($loan['loan_number']); ?></code></td>
                                    <td><?php echo htmlspecialchars($loan['product_name']); ?></td>
                                    <td><?php echo formatMoney($loan['principal_amount']); ?></td>
                                    <td class="text-success"><?php echo formatMoney($loan['amount_paid']); ?></td>
                                    <td class="<?php echo $loan['balance'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo formatMoney($loan['balance']); ?>
                                    </td>
                                    <td><?php echo getStatusBadge($loan['status'], 'loan'); ?></td>
                                    <td><small><?php echo date('M d, Y', strtotime($loan['created_at'])); ?></small></td>
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
    <a href="index.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Customers
    </a>
    <?php if ($customer['status'] != 'blacklisted'): ?>
    <a href="blacklist.php?id=<?php echo $customer['id']; ?>" 
       class="btn btn-danger"
       onclick="return confirm('Are you sure you want to blacklist this customer?')">
        <i class="bi bi-shield-x"></i> Blacklist Customer
    </a>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>