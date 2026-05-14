<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Audit Trail';
$base_path = '../../';
$breadcrumb = [
    'Admin' => '../dashboard.php',
    'Reports' => 'index.php'
];

$user_id = $_GET['user_id'] ?? '';
$module = $_GET['module'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

$query = "SELECT al.*, u.full_name as user_name 
          FROM audit_trail al 
          LEFT JOIN users u ON al.user_id = u.id 
          WHERE DATE(al.created_at) BETWEEN :date_from AND :date_to";
$params = [':date_from' => $date_from, ':date_to' => $date_to];

if ($user_id) { $query .= " AND al.user_id = :user_id"; $params[':user_id'] = $user_id; }
if ($module) { $query .= " AND al.module = :module"; $params[':module'] = $module; }
if ($search) {
    $query .= " AND (al.action LIKE :s1 OR u.full_name LIKE :s2)";
    $st = "%$search%";
    $params[':s1'] = $st; $params[':s2'] = $st;
}

$query .= " ORDER BY al.created_at DESC LIMIT 500";
$trails = dbQuery($query, $params);

$users = dbQuery("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name");

$pdf_params = http_build_query(['date_from' => $date_from, 'date_to' => $date_to, 'user_id' => $user_id, 'module' => $module, 'search' => $search]);

include '../../includes/header.php';
?>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" 
                       placeholder="Search actions or user..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">User</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">All Users</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $user_id == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Module</label>
                <select name="module" class="form-select form-select-sm">
                    <option value="">All Modules</option>
                    <option value="users" <?php echo $module == 'users' ? 'selected' : ''; ?>>Users</option>
                    <option value="customers" <?php echo $module == 'customers' ? 'selected' : ''; ?>>Customers</option>
                    <option value="loans" <?php echo $module == 'loans' ? 'selected' : ''; ?>>Loans</option>
                    <option value="savings" <?php echo $module == 'savings' ? 'selected' : ''; ?>>Savings</option>
                    <option value="auth" <?php echo $module == 'auth' ? 'selected' : ''; ?>>Auth</option>
                    <option value="system" <?php echo $module == 'system' ? 'selected' : ''; ?>>System</option>
                    <option value="agents" <?php echo $module == 'agents' ? 'selected' : ''; ?>>Agents</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo $date_from; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo $date_to; ?>">
            </div>
            <div class="col-md-1">
                <label class="form-label small">&nbsp;</label>
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm" title="Filter"><i class="bi bi-funnel"></i></button>
                    <a href="audit_trail.php" class="btn btn-outline-secondary btn-sm" title="Clear"><i class="bi bi-x"></i></a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-journal-text"></i> Audit Trail Log</h5>
        <div class="d-flex gap-2 align-items-center">
            <small class="text-muted"><?php echo count($trails); ?> records</small>
            <button onclick="window.print()" class="btn btn-sm btn-secondary"><i class="bi bi-printer"></i> Print</button>
            <a href="pdf/audit_trail_pdf.php?<?php echo $pdf_params; ?>" class="btn btn-sm btn-danger" target="_blank"><i class="bi bi-file-pdf"></i> PDF</a>
        </div>
    </div>
    <div class="card-body">
        <?php if ($search): ?>
        <div class="alert alert-info py-2 mb-3"><small>Search: "<strong><?php echo htmlspecialchars($search); ?></strong>" - <?php echo count($trails); ?> results</small></div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Date/Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($trails) > 0): ?>
                        <?php foreach ($trails as $trail): ?>
                        <tr>
                            <td><small><?php echo date('M d, h:i A', strtotime($trail['created_at'])); ?></small></td>
                            <td><?php echo $trail['user_name'] ? htmlspecialchars($trail['user_name']) : '<span class="text-muted">System</span>'; ?></td>
                            <td><small><?php echo htmlspecialchars($trail['action']); ?></small></td>
                            <td><span class="badge bg-<?php 
                                $colors = ['users'=>'primary','customers'=>'success','loans'=>'warning','savings'=>'info','auth'=>'danger','system'=>'dark','agents'=>'secondary'];
                                echo $colors[$trail['module']] ?? 'secondary';
                            ?>"><?php echo $trail['module']; ?></span></td>
                            <td><small class="text-muted"><?php echo htmlspecialchars($trail['ip_address']); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4">No records found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>