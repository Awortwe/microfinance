<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Agent Management';
$base_path = '../../';
$breadcrumb = ['Admin' => '../dashboard.php'];

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$query = "SELECT a.*, CONCAT(u.full_name) as created_by_name 
          FROM agents a 
          LEFT JOIN users u ON a.created_by = u.id 
          WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (a.first_name LIKE :search1 OR a.last_name LIKE :search2 OR a.phone LIKE :search3 OR a.agent_code LIKE :search4)";
    $searchTerm = "%$search%";
    $params[':search1'] = $searchTerm;
    $params[':search2'] = $searchTerm;
    $params[':search3'] = $searchTerm;
    $params[':search4'] = $searchTerm;
}

if ($status) {
    $query .= " AND a.status = :status";
    $params[':status'] = $status;
}

$query .= " ORDER BY a.created_at DESC";

$agents = dbQuery($query, $params);

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-person-badge"></i> Agent Management</h5>
        <a href="add.php" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Add New Agent
        </a>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" 
                       placeholder="Search by name, phone, or code..."
                       value="<?php echo htmlspecialchars($search); ?>">
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

        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Agent Code</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($agents) > 0): ?>
                        <?php foreach ($agents as $agent): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($agent['agent_code']); ?></code></td>
                            <td>
                                <strong><?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($agent['phone']); ?></td>
                            <td><?php echo $agent['city'] ? htmlspecialchars($agent['city']) : 'N/A'; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $agent['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($agent['status']); ?>
                                </span>
                            </td>
                            <td><small><?php echo htmlspecialchars($agent['created_by_name'] ?? 'N/A'); ?></small></td>
                            <td><small><?php echo date('M d, Y', strtotime($agent['created_at'])); ?></small></td>
                            <td>
                                <div class="btn-group">
                                    <a href="view.php?id=<?php echo $agent['id']; ?>" class="btn btn-sm btn-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $agent['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $agent['id']; ?>" class="btn btn-sm btn-danger" 
                                       title="<?php echo $agent['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>"
                                       onclick="return confirm('Are you sure?')">
                                        <i class="bi bi-<?php echo $agent['status'] == 'active' ? 'x-circle' : 'check-circle'; ?>"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">No agents found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>