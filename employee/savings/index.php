<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = 'Savings Accounts';
$base_path = '../../';
$breadcrumb = ['Dashboard' => '../dashboard.php'];

// Get filter parameters
$status = $_GET['status'] ?? 'active';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT sa.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name,
          c.phone as customer_phone, c.customer_code
          FROM savings_accounts sa
          JOIN customers c ON sa.customer_id = c.id
          WHERE 1=1";
$params = [];

if ($status) {
    $query .= " AND sa.status = :status";
    $params[':status'] = $status;
}

if ($search) {
    $query .= " AND (c.first_name LIKE :search1 OR c.last_name LIKE :search2 
               OR sa.account_number LIKE :search3 OR c.phone LIKE :search4)";
    $searchTerm = "%$search%";
    $params[':search1'] = $searchTerm;
    $params[':search2'] = $searchTerm;
    $params[':search3'] = $searchTerm;
    $params[':search4'] = $searchTerm;
}

$query .= " ORDER BY sa.created_at DESC";

$accounts = dbQuery($query, $params);

// Get summary
$summary = dbSingle("SELECT 
    COUNT(*) as total,
    COALESCE(SUM(balance), 0) as total_balance,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count
    FROM savings_accounts");

// Get all active accounts for quick lookup dropdown
$all_active_accounts = dbQuery(
    "SELECT sa.id, sa.account_number, CONCAT(c.first_name, ' ', c.last_name) as customer_name, sa.balance
     FROM savings_accounts sa
     JOIN customers c ON sa.customer_id = c.id
     WHERE sa.status = 'active'
     ORDER BY c.first_name"
);

include '../../includes/header.php';
?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6>Total Accounts</h6>
                <h3><?php echo number_format($summary['total']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Total Balance</h6>
                <h3><?php echo formatMoney($summary['total_balance']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6>Active Accounts</h6>
                <h3><?php echo number_format($summary['active_count']); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-piggy-bank"></i> Savings Accounts</h5>
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Account
        </a>
    </div>
    <div class="card-body">
        <!-- Quick Statement Lookup -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card bg-light border">
                    <div class="card-body">
                        <form method="GET" action="statement.php" class="row g-2 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-search"></i> Quick Statement Lookup
                                </label>
                                <select name="account_id" class="form-select" required>
                                    <option value="">Select an account to view statement...</option>
                                    <?php foreach ($all_active_accounts as $acc): ?>
                                        <option value="<?php echo $acc['id']; ?>">
                                            <?php echo htmlspecialchars($acc['account_number'] . ' - ' . $acc['customer_name'] . ' (Balance: ' . formatMoney($acc['balance']) . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-file-text"></i> View Statement
                                </button>
                            </div>
                        </form>
                        <small class="text-muted mt-2 d-block">
                            <i class="bi bi-info-circle"></i> Select an account from the dropdown to quickly view its transaction statement.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by name, account number, phone..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="dormant" <?php echo $status == 'dormant' ? 'selected' : ''; ?>>Dormant</option>
                    <option value="closed" <?php echo $status == 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-info w-100">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            </div>
        </form>

        <!-- Accounts Table -->
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead class="table-light">
                    <tr>
                        <th>Account #</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Balance</th>
                        <th>Interest Rate</th>
                        <th>Status</th>
                        <th>Opened Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($accounts) > 0): ?>
                        <?php foreach ($accounts as $account): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($account['account_number']); ?></code></td>
                            <td>
                                <strong><?php echo htmlspecialchars($account['customer_name']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($account['customer_code']); ?></small>
                            </td>
                            <td>
                                <?php if ($account['account_type'] == 'susu'): ?>
                                    <span class="badge bg-info">Susu</span>
                                <?php elseif ($account['account_type'] == 'fixed_deposit'): ?>
                                    <span class="badge bg-primary">Fixed Deposit</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Regular</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo formatMoney($account['balance']); ?></strong></td>
                            <td><?php echo $account['interest_rate']; ?>%</td>
                            <td><?php echo getStatusBadge($account['status'], 'savings'); ?></td>
                            <td><small><?php echo date('M d, Y', strtotime($account['opened_date'])); ?></small></td>
                            <td>
                                <div class="btn-group">
                                    <a href="statement.php?account_id=<?php echo $account['id']; ?>" 
                                       class="btn btn-sm btn-primary" title="View Statement">
                                        <i class="bi bi-file-text"></i> Statement
                                    </a>
                                    <a href="deposit.php?account_id=<?php echo $account['id']; ?>" 
                                       class="btn btn-sm btn-success" title="Deposit">
                                        <i class="bi bi-plus-circle"></i>
                                    </a>
                                    <a href="withdraw.php?account_id=<?php echo $account['id']; ?>" 
                                       class="btn btn-sm btn-warning" title="Withdraw">
                                        <i class="bi bi-dash-circle"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="bi bi-piggy-bank display-4 text-muted"></i>
                                <p class="text-muted mb-0 mt-2">No savings accounts found</p>
                                <a href="create.php" class="btn btn-sm btn-primary mt-2">
                                    <i class="bi bi-plus-circle"></i> Create First Account
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>