<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = 'Customer Report';
$base_path = '../../';
$breadcrumb = [
    'Dashboard' => '../dashboard.php',
    'Reports' => 'index.php'
];

$employee_id = $_SESSION['user_id'];
$search = $_GET['search'] ?? '';

// Build query with search
$query = "SELECT 
    c.*,
    (SELECT COUNT(*) FROM savings_accounts WHERE customer_id = c.id AND status = 'active') as savings_count,
    (SELECT COALESCE(SUM(balance), 0) FROM savings_accounts WHERE customer_id = c.id AND status = 'active') as total_savings,
    (SELECT COUNT(*) FROM loans WHERE customer_id = c.id) as total_loans,
    (SELECT COUNT(*) FROM loans WHERE customer_id = c.id AND status IN ('active', 'disbursed')) as active_loans,
    (SELECT COALESCE(SUM(principal_amount), 0) FROM loans WHERE customer_id = c.id) as total_loan_amount,
    (SELECT COALESCE(SUM(balance), 0) FROM loans WHERE customer_id = c.id AND status IN ('active', 'disbursed')) as outstanding_loans,
    (SELECT COALESCE(SUM(amount_paid), 0) FROM loans WHERE customer_id = c.id) as total_repaid
    FROM customers c
    WHERE c.created_by = :employee_id AND c.status = 'active'";
$params = [':employee_id' => $employee_id];

if ($search) {
    $query .= " AND (c.first_name LIKE :search1 OR c.last_name LIKE :search2 OR c.phone LIKE :search3 OR c.customer_code LIKE :search4 OR c.city LIKE :search5)";
    $searchTerm = "%$search%";
    $params[':search1'] = $searchTerm;
    $params[':search2'] = $searchTerm;
    $params[':search3'] = $searchTerm;
    $params[':search4'] = $searchTerm;
    $params[':search5'] = $searchTerm;
}

$query .= " ORDER BY c.first_name ASC";

$customers = dbQuery($query, $params);

// Get summary totals
$total_customers = count($customers);
$total_savings = array_sum(array_column($customers, 'total_savings'));
$total_outstanding = array_sum(array_column($customers, 'outstanding_loans'));
$total_loans = array_sum(array_column($customers, 'total_loans'));
$total_repaid = array_sum(array_column($customers, 'total_repaid'));
$total_savings_count = array_sum(array_column($customers, 'savings_count'));
$total_active_loans = array_sum(array_column($customers, 'active_loans'));

include '../../includes/header.php';
?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6>Total Customers</h6>
                <h3><?php echo number_format($total_customers); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Total Savings</h6>
                <h3><?php echo formatMoney($total_savings); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h6>Outstanding Loans</h6>
                <h3><?php echo formatMoney($total_outstanding); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6>Total Loans Issued</h6>
                <h3><?php echo number_format($total_loans); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-4">
                <h5 class="mb-0"><i class="bi bi-person-lines-fill"></i> My Customers Report</h5>
            </div>
            <div class="col-md-4">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control form-control-sm" 
                           placeholder="Search by name, phone, code, city..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>
            <div class="col-md-4 text-end">
                <div class="btn-group">
                    <?php if ($search): ?>
                    <a href="customer.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                    <?php endif; ?>
                    <button onclick="window.print()" class="btn btn-sm btn-secondary">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <a href="pdf/customer_pdf.php?search=<?php echo urlencode($search); ?>" 
                       class="btn btn-sm btn-danger" target="_blank">
                        <i class="bi bi-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if ($search): ?>
        <div class="alert alert-info py-2 mb-3">
            <small>Filtered by: <strong>"<?php echo htmlspecialchars($search); ?>"</strong> - <?php echo count($customers); ?> results</small>
        </div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Customer Name</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>Agent</th>
                        <th>Savings A/Cs</th>
                        <th>Total Savings</th>
                        <th>Active Loans</th>
                        <th>Total Loans</th>
                        <th>Outstanding</th>
                        <th>Total Repaid</th>
                        <th>Actions</th>
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
                            <td><?php echo $customer['city'] ? htmlspecialchars($customer['city']) : 'N/A'; ?></td>
                            <td><small><?php echo getAgentName($customer['agent_id']); ?></small></td>
                            <td class="text-center">
                                <?php if ($customer['savings_count'] > 0): ?>
                                    <span class="badge bg-success"><?php echo $customer['savings_count']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-success"><?php echo formatMoney($customer['total_savings']); ?></td>
                            <td class="text-center">
                                <?php if ($customer['active_loans'] > 0): ?>
                                    <span class="badge bg-warning"><?php echo $customer['active_loans']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format($customer['total_loans']); ?></td>
                            <td class="text-danger">
                                <?php echo $customer['outstanding_loans'] > 0 ? formatMoney($customer['outstanding_loans']) : '-'; ?>
                            </td>
                            <td class="text-success">
                                <?php echo $customer['total_repaid'] > 0 ? formatMoney($customer['total_repaid']) : '-'; ?>
                            </td>
                            <td>
                                <a href="../customers/view.php?id=<?php echo $customer['id']; ?>" 
                                   class="btn btn-sm btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" class="text-center py-4">
                                <i class="bi bi-people display-4 text-muted"></i>
                                <p class="text-muted mb-0 mt-2">No customers found</p>
                                <?php if ($search): ?>
                                    <a href="customer.php" class="btn btn-sm btn-outline-primary mt-2">Clear Search</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if (count($customers) > 0): ?>
                <tfoot class="table-secondary fw-bold">
                    <tr>
                        <td colspan="5">Totals (<?php echo count($customers); ?> customers)</td>
                        <td class="text-center"><?php echo $total_savings_count; ?></td>
                        <td class="text-success"><?php echo formatMoney($total_savings); ?></td>
                        <td class="text-center"><?php echo $total_active_loans; ?></td>
                        <td><?php echo $total_loans; ?></td>
                        <td class="text-danger"><?php echo formatMoney($total_outstanding); ?></td>
                        <td class="text-success"><?php echo formatMoney($total_repaid); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>