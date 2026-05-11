<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = 'My Loans';
$base_path = '../../';
$breadcrumb = ['Dashboard' => '../dashboard.php'];

// Get filter parameters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT l.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name,
          lp.product_name
          FROM loans l
          JOIN customers c ON l.customer_id = c.id
          JOIN loan_products lp ON l.product_id = lp.id
          WHERE l.created_by = :employee_id";
$params = [':employee_id' => $_SESSION['user_id']];

if ($status) {
    $query .= " AND l.status = :status";
    $params[':status'] = $status;
}

if ($search) {
    $query .= " AND (c.first_name LIKE :search1 OR c.last_name LIKE :search2 OR l.loan_number LIKE :search3)";
    $searchTerm = "%$search%";
    $params[':search1'] = $searchTerm;
    $params[':search2'] = $searchTerm;
    $params[':search3'] = $searchTerm;
}

$query .= " ORDER BY l.created_at DESC";

$loans = dbQuery($query, $params);

// Get summary
$summary = dbSingle(
    "SELECT 
    COUNT(*) as total,
    COALESCE(SUM(principal_amount), 0) as total_amount,
    COALESCE(SUM(balance), 0) as total_outstanding,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
    COUNT(CASE WHEN status IN ('active', 'disbursed') THEN 1 END) as active_count
    FROM loans WHERE created_by = :employee_id",
    [':employee_id' => $_SESSION['user_id']]
);

include '../../includes/header.php';
?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6>Total Loans</h6>
                <h3><?php echo $summary['total']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h6>Pending</h6>
                <h3><?php echo $summary['pending_count']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Active</h6>
                <h3><?php echo $summary['active_count']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h6>Outstanding</h6>
                <h3><?php echo formatMoney($summary['total_outstanding']); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-cash-stack"></i> My Loans</h5>
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-file-plus"></i> New Loan Application
        </a>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" 
                       placeholder="Search by customer or loan number..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status == 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="disbursed" <?php echo $status == 'disbursed' ? 'selected' : ''; ?>>Disbursed</option>
                    <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-info w-100">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            </div>
        </form>

        <!-- Loans Table - NO datatable class -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Loan #</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($loans) > 0): ?>
                        <?php foreach ($loans as $loan): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($loan['loan_number']); ?></code></td>
                            <td><?php echo htmlspecialchars($loan['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($loan['product_name']); ?></td>
                            <td><?php echo formatMoney($loan['principal_amount']); ?></td>
                            <td class="<?php echo $loan['balance'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo formatMoney($loan['balance']); ?>
                            </td>
                            <td><?php echo getStatusBadge($loan['status'], 'loan'); ?></td>
                            <td><small><?php echo date('M d', strtotime($loan['created_at'])); ?></small></td>
                            <td>
                                <div class="btn-group">
                                    <a href="schedule.php?loan_id=<?php echo $loan['id']; ?>" 
                                       class="btn btn-sm btn-info" title="Schedule">
                                        <i class="bi bi-calendar3"></i>
                                    </a>
                                    <?php if (in_array($loan['status'], ['disbursed', 'active'])): ?>
                                        <a href="repay.php?loan_id=<?php echo $loan['id']; ?>" 
                                           class="btn btn-sm btn-success" title="Repay">
                                            <i class="bi bi-cash-coin"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="bi bi-cash-stack display-4 text-muted"></i>
                                <p class="text-muted mb-0 mt-2">No loans found</p>
                                <a href="create.php" class="btn btn-sm btn-primary mt-2">
                                    <i class="bi bi-file-plus"></i> Create First Loan
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="text-muted mt-2">
            <small>Showing <strong><?php echo count($loans); ?></strong> loans</small>
            <?php if (!empty($_GET)): ?>
                <a href="index.php" class="btn btn-sm btn-outline-secondary ms-2">Clear Filters</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>