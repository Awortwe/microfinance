<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Customer Report';
$base_path = '../../';
$breadcrumb = [
    'Admin' => '../dashboard.php',
    'Reports' => 'index.php'
];

// Get filter parameters
$status = $_GET['status'] ?? '';
$gender = $_GET['gender'] ?? '';
$created_by = $_GET['created_by'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$query = "SELECT 
    c.*,
    CONCAT(u.full_name) as created_by_name,
    (SELECT COUNT(*) FROM savings_accounts WHERE customer_id = c.id AND status = 'active') as savings_count,
    (SELECT COALESCE(SUM(balance), 0) FROM savings_accounts WHERE customer_id = c.id AND status = 'active') as total_savings,
    (SELECT COUNT(*) FROM loans WHERE customer_id = c.id) as total_loans,
    (SELECT COUNT(*) FROM loans WHERE customer_id = c.id AND status IN ('active', 'disbursed')) as active_loans,
    (SELECT COALESCE(SUM(principal_amount), 0) FROM loans WHERE customer_id = c.id) as total_loan_amount,
    (SELECT COALESCE(SUM(balance), 0) FROM loans WHERE customer_id = c.id AND status IN ('active', 'disbursed')) as outstanding_loans,
    (SELECT COALESCE(SUM(amount_paid), 0) FROM loans WHERE customer_id = c.id) as total_repaid
    FROM customers c
    LEFT JOIN users u ON c.created_by = u.id
    WHERE 1=1";
$params = [];

if ($status) {
    $query .= " AND c.status = :status";
    $params[':status'] = $status;
}

if ($gender) {
    $query .= " AND c.gender = :gender";
    $params[':gender'] = $gender;
}

if ($created_by) {
    $query .= " AND c.created_by = :created_by";
    $params[':created_by'] = $created_by;
}

if ($date_from) {
    $query .= " AND DATE(c.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(c.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

$query .= " ORDER BY c.created_at DESC";

$customers = dbQuery($query, $params);

// Get summary totals
$summary = dbSingle("SELECT 
    COUNT(*) as total_customers,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_customers,
    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_customers,
    COUNT(CASE WHEN status = 'blacklisted' THEN 1 END) as blacklisted_customers,
    COUNT(CASE WHEN gender = 'Male' THEN 1 END) as male_count,
    COUNT(CASE WHEN gender = 'Female' THEN 1 END) as female_count
    FROM customers");

// Get employees for filter
$employees = dbQuery("SELECT id, full_name FROM users WHERE user_type = 'employee' AND is_active = 1 ORDER BY full_name");

// Get customer growth by month (last 12 months)
$growth = dbQuery(
    "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as count
    FROM customers 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC"
);

include '../../includes/header.php';
?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card bg-primary text-white">
            <div class="card-body text-center py-3">
                <h6 class="small">Total</h6>
                <h4 class="mb-0"><?php echo number_format($summary['total_customers']); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-success text-white">
            <div class="card-body text-center py-3">
                <h6 class="small">Active</h6>
                <h4 class="mb-0"><?php echo number_format($summary['active_customers']); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center py-3">
                <h6 class="small">Inactive</h6>
                <h4 class="mb-0"><?php echo number_format($summary['inactive_customers']); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-danger text-white">
            <div class="card-body text-center py-3">
                <h6 class="small">Blacklisted</h6>
                <h4 class="mb-0"><?php echo number_format($summary['blacklisted_customers']); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-info text-white">
            <div class="card-body text-center py-3">
                <h6 class="small">Male</h6>
                <h4 class="mb-0"><?php echo number_format($summary['male_count']); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-white">
            <div class="card-body text-center py-3">
                <h6 class="small">Female</h6>
                <h4 class="mb-0"><?php echo number_format($summary['female_count']); ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="blacklisted" <?php echo $status == 'blacklisted' ? 'selected' : ''; ?>>Blacklisted</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select">
                    <option value="">All</option>
                    <option value="Male" <?php echo $gender == 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo $gender == 'Female' ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Created By</label>
                <select name="created_by" class="form-select">
                    <option value="">All Employees</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>" <?php echo $created_by == $emp['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($emp['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-filter"></i> Filter
                    </button>
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="bi bi-printer"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Customer Growth Chart -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Customer Growth (Last 12 Months)</h5>
            </div>
            <div class="card-body">
                <canvas id="growthChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Customers Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-people-fill"></i> Customer Details</h5>
        <small class="text-muted"><?php echo count($customers); ?> customers found</small>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Customer Name</th>
                        <th>Phone</th>
                        <th>Gender</th>
                        <th>Status</th>
                        <th>Savings A/Cs</th>
                        <th>Total Savings</th>
                        <th>Loans</th>
                        <th>Active Loans</th>
                        <th>Outstanding</th>
                        <th>Total Repaid</th>
                        <th>Created By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($customers) > 0): ?>
                        <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($customer['customer_code']); ?></code></td>
                            <td>
                                <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong>
                                <?php if ($customer['business_name']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($customer['business_name']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                            <td><?php echo $customer['gender'] ?? 'N/A'; ?></td>
                            <td><?php echo getStatusBadge($customer['status'], 'customer'); ?></td>
                            <td class="text-center">
                                <?php if ($customer['savings_count'] > 0): ?>
                                    <span class="badge bg-success"><?php echo $customer['savings_count']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-success"><?php echo formatMoney($customer['total_savings']); ?></td>
                            <td><?php echo number_format($customer['total_loans']); ?></td>
                            <td>
                                <?php if ($customer['active_loans'] > 0): ?>
                                    <span class="badge bg-warning"><?php echo $customer['active_loans']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-danger">
                                <?php echo $customer['outstanding_loans'] > 0 ? formatMoney($customer['outstanding_loans']) : '-'; ?>
                            </td>
                            <td class="text-success">
                                <?php echo $customer['total_repaid'] > 0 ? formatMoney($customer['total_repaid']) : '-'; ?>
                            </td>
                            <td><small><?php echo htmlspecialchars($customer['created_by_name'] ?? 'N/A'); ?></small></td>
                            <td><small><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="13" class="text-center py-4">
                                <i class="bi bi-people display-4 text-muted"></i>
                                <p class="text-muted mb-0 mt-2">No customers found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if (count($customers) > 0): ?>
                <tfoot class="table-secondary fw-bold">
                    <tr>
                        <td colspan="5">Totals (<?php echo count($customers); ?> customers)</td>
                        <td class="text-center"><?php echo array_sum(array_column($customers, 'savings_count')); ?></td>
                        <td class="text-success"><?php echo formatMoney(array_sum(array_column($customers, 'total_savings'))); ?></td>
                        <td><?php echo array_sum(array_column($customers, 'total_loans')); ?></td>
                        <td><?php echo array_sum(array_column($customers, 'active_loans')); ?></td>
                        <td class="text-danger"><?php echo formatMoney(array_sum(array_column($customers, 'outstanding_loans'))); ?></td>
                        <td class="text-success"><?php echo formatMoney(array_sum(array_column($customers, 'total_repaid'))); ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<?php if (count($growth) > 0): ?>
<script>
// Customer Growth Chart
const ctx = document.getElementById('growthChart').getContext('2d');
const months = [<?php 
    $labels = [];
    $data = [];
    foreach ($growth as $g) {
        $labels[] = "'" . date('M Y', strtotime($g['month'] . '-01')) . "'";
        $data[] = $g['count'];
    }
    echo implode(', ', $labels);
?>];
const counts = [<?php echo implode(', ', $data); ?>];

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: months,
        datasets: [{
            label: 'New Customers',
            data: counts,
            backgroundColor: 'rgba(52, 152, 219, 0.7)',
            borderColor: 'rgba(52, 152, 219, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>