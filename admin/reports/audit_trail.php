<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Audit Trail';
$base_path = '../../';
$breadcrumb = [
    'Admin' => '../dashboard.php',
    'Reports' => 'index.php'
];

// Get filter parameters
$user_id = $_GET['user_id'] ?? '';
$module = $_GET['module'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Build query
$query = "SELECT al.*, u.full_name as user_name 
          FROM audit_trail al 
          LEFT JOIN users u ON al.user_id = u.id 
          WHERE DATE(al.created_at) BETWEEN :date_from AND :date_to";
$params = [':date_from' => $date_from, ':date_to' => $date_to];

if ($user_id) {
    $query .= " AND al.user_id = :user_id";
    $params[':user_id'] = $user_id;
}

if ($module) {
    $query .= " AND al.module = :module";
    $params[':module'] = $module;
}

$query .= " ORDER BY al.created_at DESC LIMIT 300";

$trails = dbQuery($query, $params);

// Get users for filter dropdown
$users = dbQuery("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name");

// Get module summary
$module_summary = dbQuery(
    "SELECT module, COUNT(*) as count FROM audit_trail 
     WHERE DATE(created_at) BETWEEN :date_from AND :date_to 
     GROUP BY module ORDER BY count DESC",
    [':date_from' => $date_from, ':date_to' => $date_to]
);

include '../../includes/header.php';
?>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">User</label>
                <select name="user_id" class="form-select">
                    <option value="">All Users</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $user_id == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Module</label>
                <select name="module" class="form-select">
                    <option value="">All Modules</option>
                    <option value="users" <?php echo $module == 'users' ? 'selected' : ''; ?>>Users</option>
                    <option value="customers" <?php echo $module == 'customers' ? 'selected' : ''; ?>>Customers</option>
                    <option value="loans" <?php echo $module == 'loans' ? 'selected' : ''; ?>>Loans</option>
                    <option value="savings" <?php echo $module == 'savings' ? 'selected' : ''; ?>>Savings</option>
                    <option value="auth" <?php echo $module == 'auth' ? 'selected' : ''; ?>>Auth</option>
                    <option value="system" <?php echo $module == 'system' ? 'selected' : ''; ?>>System</option>
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
            <div class="col-md-4">
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

<!-- Module Summary -->
<div class="row mb-4">
    <?php 
    $colors = ['primary', 'success', 'warning', 'info', 'danger', 'dark'];
    $color_index = 0;
    foreach ($module_summary as $item):
        $color = $colors[$color_index % count($colors)];
        $color_index++;
    ?>
    <div class="col-md-2 col-4 mb-2">
        <div class="card bg-<?php echo $color; ?> text-white">
            <div class="card-body text-center py-2">
                <small class="text-white-50"><?php echo ucfirst($item['module']); ?></small>
                <h5 class="mb-0"><?php echo $item['count']; ?></h5>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-journal-text"></i> Audit Trail Log</h5>
        <small class="text-muted"><?php echo count($trails); ?> records</small>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead class="table-light">
                    <tr>
                        <th>Date/Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Record ID</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($trails) > 0): ?>
                        <?php foreach ($trails as $trail): ?>
                        <tr>
                            <td>
                                <small>
                                    <?php echo date('M d, Y', strtotime($trail['created_at'])); ?>
                                    <br>
                                    <?php echo date('h:i:s A', strtotime($trail['created_at'])); ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($trail['user_name']): ?>
                                    <strong><?php echo htmlspecialchars($trail['user_name']); ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">System</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($trail['action']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    $badge_colors = [
                                        'users' => 'primary', 'customers' => 'success', 
                                        'loans' => 'warning', 'savings' => 'info', 
                                        'auth' => 'danger', 'system' => 'dark'
                                    ];
                                    echo $badge_colors[$trail['module']] ?? 'secondary';
                                ?>">
                                    <?php echo htmlspecialchars($trail['module']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($trail['record_id']): ?>
                                    <code><?php echo $trail['record_id']; ?></code>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?php echo htmlspecialchars($trail['ip_address']); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="bi bi-inbox display-4 text-muted"></i>
                                <p class="text-muted mb-0 mt-2">No audit trail records found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>