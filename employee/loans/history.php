<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = 'Loan History';
$base_path = '../../';
$breadcrumb = [
    'Dashboard' => '../dashboard.php',
    'Loans' => 'index.php'
];

// Get filter parameters
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-01-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT l.*, 
          CONCAT(c.first_name, ' ', c.last_name) as customer_name,
          c.phone as customer_phone, c.customer_code,
          lp.product_name,
          CASE 
            WHEN l.status = 'completed' AND l.actual_end_date <= l.expected_end_date THEN 'On Time'
            WHEN l.status = 'completed' AND l.actual_end_date > l.expected_end_date THEN 'Late Completion'
            WHEN l.status = 'defaulted' THEN 'Defaulted'
            WHEN l.status = 'written_off' THEN 'Written Off'
            ELSE 'Other'
          END as completion_status
          FROM loans l
          JOIN customers c ON l.customer_id = c.id
          JOIN loan_products lp ON l.product_id = lp.id
          WHERE l.created_by = :employee_id
          AND l.status IN ('completed', 'defaulted', 'written_off', 'rejected')";
$params = [':employee_id' => $_SESSION['user_id']];

if ($status) {
    $query .= " AND l.status = :status";
    $params[':status'] = $status;
}
if ($date_from) {
    $query .= " AND DATE(l.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}
if ($date_to) {
    $query .= " AND DATE(l.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}
if ($search) {
    $query .= " AND (c.first_name LIKE :search1 OR c.last_name LIKE :search2 OR l.loan_number LIKE :search3)";
    $searchTerm = "%$search%";
    $params[':search1'] = $searchTerm;
    $params[':search2'] = $searchTerm;
    $params[':search3'] = $searchTerm;
}

$query .= " ORDER BY l.updated_at DESC";

$loans_history = dbQuery($query, $params);

// Get summary
$summary = dbSingle(
    "SELECT 
    COUNT(*) as total_closed,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
    COUNT(CASE WHEN status = 'defaulted' THEN 1 END) as defaulted,
    COUNT(CASE WHEN status = 'written_off' THEN 1 END) as written_off,
    COALESCE(SUM(principal_amount), 0) as total_principal,
    COALESCE(SUM(amount_paid), 0) as total_repaid
    FROM loans 
    WHERE created_by = :employee_id
    AND status IN ('completed', 'defaulted', 'written_off', 'rejected')",
    [':employee_id' => $_SESSION['user_id']]
);

include '../../includes/header.php';
?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6>Total Closed</h6>
                <h3><?php echo number_format($summary['total_closed']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Completed</h6>
                <h3><?php echo number_format($summary['completed']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h6>Defaulted/Written Off</h6>
                <h3><?php echo number_format($summary['defaulted'] + $summary['written_off']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6>Total Repaid</h6>
                <h3><?php echo formatMoney($summary['total_repaid']); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Loan History</h5>
        <button onclick="window.print()" class="btn btn-sm btn-secondary no-print">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" class="row g-3 mb-4 no-print">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" 
                       placeholder="Search name or loan #..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="defaulted" <?php echo $status == 'defaulted' ? 'selected' : ''; ?>>Defaulted</option>
                    <option value="written_off" <?php echo $status == 'written_off' ? 'selected' : ''; ?>>Written Off</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-info w-100">
                    <i class="bi bi-funnel"></i> Apply Filters
                </button>
            </div>
        </form>

        <!-- History Table - NO datatable class -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Loan #</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Status</th>
                        <th>Completion</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($loans_history) > 0): ?>
                        <?php foreach ($loans_history as $loan): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($loan['loan_number']); ?></code></td>
                            <td>
                                <strong><?php echo htmlspecialchars($loan['customer_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($loan['customer_code']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($loan['product_name']); ?></td>
                            <td><?php echo formatMoney($loan['principal_amount']); ?></td>
                            <td class="text-success"><?php echo formatMoney($loan['amount_paid']); ?></td>
                            <td><?php echo getStatusBadge($loan['status'], 'loan'); ?></td>
                            <td>
                                <?php if ($loan['completion_status'] == 'On Time'): ?>
                                    <span class="badge bg-success">On Time</span>
                                <?php elseif ($loan['completion_status'] == 'Late Completion'): ?>
                                    <span class="badge bg-warning">Late</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo $loan['completion_status']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="schedule.php?loan_id=<?php echo $loan['id']; ?>" 
                                       class="btn btn-sm btn-info" title="Schedule">
                                        <i class="bi bi-calendar3"></i>
                                    </a>
                                    <a href="../customers/view.php?id=<?php echo $loan['customer_id']; ?>" 
                                       class="btn btn-sm btn-primary" title="Customer">
                                        <i class="bi bi-person"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="bi bi-inbox display-4 text-muted"></i>
                                <p class="text-muted mb-0">No loan history found</p>
                                <?php if (!empty($_GET)): ?>
                                    <a href="history.php" class="btn btn-sm btn-outline-primary mt-2">Clear Filters</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="text-muted mt-2">
            <small>Showing <strong><?php echo count($loans_history); ?></strong> loans</small>
            <?php if (!empty($_GET)): ?>
                <a href="history.php" class="btn btn-sm btn-outline-secondary ms-2">Clear Filters</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>