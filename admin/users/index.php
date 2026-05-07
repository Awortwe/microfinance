<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'User Management';
$base_path = '../../';
$breadcrumb = ['Admin' => '../dashboard.php'];

// Get filter parameters
$user_type = $_GET['type'] ?? null;
$search = $_GET['search'] ?? null;
$is_active = isset($_GET['status']) ? ($_GET['status'] == 'active' ? 1 : 0) : null;

// Build query
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($user_type) {
    $query .= " AND user_type = :user_type";
    $params[':user_type'] = $user_type;
}

if ($search) {
    $query .= " AND (full_name LIKE :search1 OR username LIKE :search2 OR email LIKE :search3)";
    $searchTerm = "%$search%";
    $params[':search1'] = $searchTerm;
    $params[':search2'] = $searchTerm;
    $params[':search3'] = $searchTerm;
}

if ($is_active !== null) {
    $query .= " AND is_active = :is_active";
    $params[':is_active'] = $is_active;
}

$query .= " ORDER BY created_at DESC";

$users = dbQuery($query, $params);

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-people-fill"></i> User Management</h5>
        <a href="add.php" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Add New User
        </a>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by name, username, or email..."
                           value="<?php echo htmlspecialchars($search ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="admin" <?php echo ($user_type == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="employee" <?php echo ($user_type == 'employee') ? 'selected' : ''; ?>>Employee</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo (isset($_GET['status']) && $_GET['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-info w-100">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            </div>
        </form>

        <!-- Users Table -->
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php $count = 1; foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-<?php echo $u['user_type'] == 'admin' ? 'danger' : 'primary'; ?> text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                                         style="width: 35px; height: 35px; font-size: 0.8rem; font-weight: 600;">
                                        <?php echo strtoupper(substr($u['full_name'], 0, 2)); ?>
                                    </div>
                                    <span class="fw-semibold"><?php echo htmlspecialchars($u['full_name']); ?></span>
                                </div>
                            </td>
                            <td><code><?php echo htmlspecialchars($u['username']); ?></code></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo $u['phone'] ? htmlspecialchars($u['phone']) : '<span class="text-muted">N/A</span>'; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $u['user_type'] == 'admin' ? 'danger' : 'primary'; ?>">
                                    <?php echo ucfirst($u['user_type']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $u['is_active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <small>
                                    <?php echo $u['last_login'] ? date('M d, Y h:i A', strtotime($u['last_login'])) : '<span class="text-muted">Never</span>'; ?>
                                </small>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="view.php?id=<?php echo $u['id']; ?>" 
                                       class="btn btn-sm btn-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $u['id']; ?>" 
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <a href="delete.php?id=<?php echo $u['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       title="Deactivate"
                                       onclick="return confirm('Are you sure you want to deactivate this user?')">
                                        <i class="bi bi-person-x"></i>
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
                                <p class="text-muted mb-0 mt-2">No users found</p>
                                <?php if (!empty($_GET)): ?>
                                    <a href="index.php" class="btn btn-sm btn-outline-primary mt-2">Clear Filters</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="text-muted mt-2">
            <small>Total Users: <strong><?php echo count($users); ?></strong></small>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>