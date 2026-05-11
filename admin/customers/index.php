<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'All Customers';
$base_path = '../../';
$breadcrumb = ['Admin' => '../dashboard.php'];

// Get filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$gender = $_GET['gender'] ?? '';

// Build query
$query = "SELECT c.*, CONCAT(u.full_name) as created_by_name 
          FROM customers c 
          LEFT JOIN users u ON c.created_by = u.id 
          WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (c.first_name LIKE :search1 OR c.last_name LIKE :search2 
               OR c.phone LIKE :search3 OR c.customer_code LIKE :search4)";
    $searchTerm = "%$search%";
    $params[':search1'] = $searchTerm;
    $params[':search2'] = $searchTerm;
    $params[':search3'] = $searchTerm;
    $params[':search4'] = $searchTerm;
}

if ($status) {
    $query .= " AND c.status = :status";
    $params[':status'] = $status;
}

if ($gender) {
    $query .= " AND c.gender = :gender";
    $params[':gender'] = $gender;
}

$query .= " ORDER BY c.created_at DESC";

$customers = dbQuery($query, $params);

// Get summary counts
$counts = dbSingle("SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive,
    COUNT(CASE WHEN status = 'blacklisted' THEN 1 END) as blacklisted
    FROM customers");

include '../../includes/header.php';
?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6>Total Customers</h6>
                <h3><?php echo number_format($counts['total']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Active</h6>
                <h3><?php echo number_format($counts['active']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <h6>Inactive</h6>
                <h3><?php echo number_format($counts['inactive']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h6>Blacklisted</h6>
                <h3><?php echo number_format($counts['blacklisted']); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-people-fill"></i> All Customers</h5>
        <div>
            <a href="add.php" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> Add New Customer
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" 
                       placeholder="Search by name, phone, or code..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="blacklisted" <?php echo $status == 'blacklisted' ? 'selected' : ''; ?>>Blacklisted</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="gender" class="form-select">
                    <option value="">All Gender</option>
                    <option value="Male" <?php echo $gender == 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo $gender == 'Female' ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-info w-100">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            </div>
        </form>

        <!-- Customers Table (NO datatable class - plain table) -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Customer Name</th>
                        <th>Phone</th>
                        <th>Gender</th>
                        <th>City</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Date</th>
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
                                         style="width: 35px; height: 35px; font-size: 0.75rem; font-weight: 600;">
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
                            <td><?php echo $customer['gender'] ?? 'N/A'; ?></td>
                            <td><?php echo $customer['city'] ? htmlspecialchars($customer['city']) : 'N/A'; ?></td>
                            <td><?php echo getStatusBadge($customer['status'], 'customer'); ?></td>
                            <td>
                                <small><?php echo htmlspecialchars($customer['created_by_name'] ?? 'N/A'); ?></small>
                            </td>
                            <td>
                                <small><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></small>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="view.php?id=<?php echo $customer['id']; ?>" 
                                       class="btn btn-sm btn-info" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $customer['id']; ?>" 
                                       class="btn btn-sm btn-warning" title="Edit Customer">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($customer['status'] != 'blacklisted'): ?>
                                    <a href="blacklist.php?id=<?php echo $customer['id']; ?>" 
                                       class="btn btn-sm btn-dark" 
                                       title="Blacklist"
                                       onclick="return confirm('Are you sure you want to blacklist this customer?')">
                                        <i class="bi bi-shield-x"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($customer['status'] == 'inactive'): ?>
                                    <a href="delete.php?id=<?php echo $customer['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       title="Delete"
                                       onclick="return confirm('Are you sure you want to delete this customer? This action cannot be undone.')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="bi bi-people display-4 text-muted"></i>
                                <p class="text-muted mb-0 mt-2">No customers found</p>
                                <a href="add.php" class="btn btn-sm btn-primary mt-2">
                                    <i class="bi bi-person-plus"></i> Add New Customer
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="text-muted mt-2">
            <small>Showing <strong><?php echo count($customers); ?></strong> customers</small>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>