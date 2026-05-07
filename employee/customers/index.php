<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = 'My Customers';
$base_path = '../../';
$breadcrumb = ['Dashboard' => '../dashboard.php'];

// Get search parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'active';

// Build query
$query = "SELECT * FROM customers WHERE created_by = :employee_id";
$params = [':employee_id' => $_SESSION['user_id']];

if ($search) {
    $query .= " AND (first_name LIKE :search1 OR last_name LIKE :search2 
               OR phone LIKE :search3 OR customer_code LIKE :search4
               OR occupation LIKE :search5 OR business_name LIKE :search6)";
    $searchTerm = "%$search%";
    $params[':search1'] = $searchTerm;
    $params[':search2'] = $searchTerm;
    $params[':search3'] = $searchTerm;
    $params[':search4'] = $searchTerm;
    $params[':search5'] = $searchTerm;
    $params[':search6'] = $searchTerm;
}

if ($status) {
    $query .= " AND status = :status";
    $params[':status'] = $status;
}

$query .= " ORDER BY created_at DESC";

$customers = dbQuery($query, $params);

// Get summary counts
$counts = dbSingle(
    "SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count,
    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_count
    FROM customers WHERE created_by = :employee_id",
    [':employee_id' => $_SESSION['user_id']]
);

include '../../includes/header.php';
?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6>Total Customers</h6>
                <h3><?php echo $counts['total']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Active</h6>
                <h3><?php echo $counts['active_count']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <h6>Inactive</h6>
                <h3><?php echo $counts['inactive_count']; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-person-lines-fill"></i> My Customers</h5>
        <a href="add.php" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Add New Customer
        </a>
    </div>
    <div class="card-body">
        <!-- Search & Filters -->
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by name, phone, code, occupation..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-info w-100">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            </div>
        </form>

        <!-- Customers Table -->
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Customer Name</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>Occupation</th>
                        <th>Status</th>
                        <th>Date Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($customers) > 0): ?>
                        <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($customer['customer_code']); ?></code></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                                         style="width: 30px; height: 30px; font-size: 0.7rem;">
                                        <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong>
                                        <?php if ($customer['business_name']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($customer['business_name']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                            <td><?php echo $customer['city'] ? htmlspecialchars($customer['city']) : 'N/A'; ?></td>
                            <td><?php echo $customer['occupation'] ? htmlspecialchars($customer['occupation']) : 'N/A'; ?></td>
                            <td><?php echo getStatusBadge($customer['status'], 'customer'); ?></td>
                            <td><small><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></small></td>
                            <td>
                                <div class="btn-group">
                                    <a href="view.php?id=<?php echo $customer['id']; ?>" 
                                       class="btn btn-sm btn-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $customer['id']; ?>" 
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="bi bi-people display-4 text-muted"></i>
                                <p class="text-muted mb-0 mt-2">No customers found</p>
                                <a href="add.php" class="btn btn-sm btn-primary mt-2">
                                    <i class="bi bi-person-plus"></i> Add Your First Customer
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