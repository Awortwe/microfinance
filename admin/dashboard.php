<?php
require_once '../config/init.php';
require_once '../includes/admin_check.php';

$page_title = 'Admin Dashboard';
$base_path = '../';
$breadcrumb = [];

// Get admin dashboard data
$stats = getAdminDashboardData();

// Create Database instance
$db = new Database();

// Get recent activities
$db->query("SELECT al.*, u.full_name as user_name 
          FROM audit_trail al 
          LEFT JOIN users u ON al.user_id = u.id 
          ORDER BY al.created_at DESC LIMIT 10");
$activities = $db->resultSet();

// Get pending loans
$db->query("SELECT l.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name 
          FROM loans l 
          JOIN customers c ON l.customer_id = c.id 
          WHERE l.status = 'pending' 
          ORDER BY l.created_at DESC LIMIT 5");
$pending_loans = $db->resultSet();

// Get recent customers
$db->query("SELECT * FROM customers ORDER BY created_at DESC LIMIT 5");
$recent_customers = $db->resultSet();

include '../includes/header.php';
?>

<!-- Welcome Message -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info">
            <h5 class="mb-0">
                <i class="bi bi-hand-wave"></i> 
                Welcome back, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>!
                <small class="text-muted ms-3">
                    <i class="bi bi-calendar"></i> <?php echo date('l, F d, Y'); ?>
                </small>
            </h5>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">Total Customers</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['total_customers']); ?></h3>
                    </div>
                    <i class="bi bi-people display-6 text-white-50"></i>
                </div>
                <hr class="bg-white-50">
                <small class="text-white-50">
                    <i class="bi bi-person-plus"></i> 
                    <?php echo $stats['today_new_customers']; ?> new today
                </small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">Total Savings</h6>
                        <h3 class="mb-0"><?php echo formatMoney($stats['total_savings']); ?></h3>
                    </div>
                    <i class="bi bi-piggy-bank display-6 text-white-50"></i>
                </div>
                <hr class="bg-white-50">
                <small class="text-white-50">Active savings accounts</small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">Active Loans</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['active_loans']); ?></h3>
                    </div>
                    <i class="bi bi-cash-stack display-6 text-white-50"></i>
                </div>
                <hr class="bg-white-50">
                <small class="text-white-50">
                    Outstanding: <?php echo formatMoney($stats['outstanding_loans']); ?>
                </small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">Today's Collection</h6>
                        <h3 class="mb-0"><?php echo formatMoney($stats['today_collections']); ?></h3>
                    </div>
                    <i class="bi bi-wallet2 display-6 text-white-50"></i>
                </div>
                <hr class="bg-white-50">
                <small class="text-white-50">
                    This month: <?php echo formatMoney($stats['month_collections']); ?>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Additional Stats -->
<div class="row">
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-danger h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small">Pending Loans</h6>
                        <h4 class="mb-0"><?php echo $stats['pending_loans']; ?></h4>
                    </div>
                    <i class="bi bi-hourglass-split display-6 text-danger opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-warning h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small">Total Employees</h6>
                        <h4 class="mb-0"><?php echo $stats['total_employees']; ?></h4>
                    </div>
                    <i class="bi bi-person-badge display-6 text-warning opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-info h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small">Default Rate</h6>
                        <h4 class="mb-0"><?php echo $stats['default_rate']; ?>%</h4>
                    </div>
                    <i class="bi bi-exclamation-triangle display-6 text-info opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pending Loans & Recent Customers -->
<div class="row">
    <!-- Pending Loans Table -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-hourglass-split"></i> Pending Loan Approvals</h5>
                <a href="loans/approve.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (count($pending_loans) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Loan #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Duration</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_loans as $loan): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($loan['loan_number']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($loan['customer_name']); ?></td>
                                <td><strong><?php echo formatMoney($loan['principal_amount']); ?></strong></td>
                                <td><?php echo $loan['duration_months']; ?> months</td>
                                <td>
                                    <small><?php echo date('M d, Y', strtotime($loan['application_date'])); ?></small>
                                </td>
                                <td>
                                    <a href="loans/approve.php?id=<?php echo $loan['id']; ?>" 
                                       class="btn btn-sm btn-success">
                                        <i class="bi bi-check-lg"></i> Review
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-check-circle display-4 text-success"></i>
                        <p class="text-muted mb-0 mt-2">No pending loan approvals</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Customers -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-person-plus"></i> Recent Customers</h5>
                <a href="customers/index.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (count($recent_customers) > 0): ?>
                    <?php foreach ($recent_customers as $customer): ?>
                    <div class="d-flex align-items-center mb-3 p-2 border rounded">
                        <div class="flex-shrink-0">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 45px; height: 45px; font-weight: 600;">
                                <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h6>
                            <small class="text-muted">
                                <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($customer['phone']); ?>
                            </small>
                        </div>
                        <a href="customers/view.php?id=<?php echo $customer['id']; ?>" 
                           class="btn btn-sm btn-outline-primary" title="View">
                            <i class="bi bi-eye"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-people display-4 text-muted"></i>
                        <p class="text-muted mb-0 mt-2">No customers yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-activity"></i> Recent Activities</h5>
                <a href="reports/audit_trail.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Module</th>
                                <th>Date/Time</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($activities) > 0): ?>
                                <?php foreach ($activities as $activity): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?></td>
                                    <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($activity['module']); ?></span>
                                    </td>
                                    <td>
                                        <small><?php echo date('M d, Y h:i A', strtotime($activity['created_at'])); ?></small>
                                    </td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($activity['ip_address']); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="bi bi-inbox display-4 text-muted"></i>
                                        <p class="text-muted mb-0 mt-2">No recent activities</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning-charge"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-3">
                        <a href="users/add.php" class="btn btn-primary w-100">
                            <i class="bi bi-person-plus"></i> Add New User
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="customers/index.php" class="btn btn-info text-white w-100">
                            <i class="bi bi-people"></i> View All Customers
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="loans/approve.php" class="btn btn-warning w-100">
                            <i class="bi bi-check-circle"></i> Approve Loans
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="reports/index.php" class="btn btn-success w-100">
                            <i class="bi bi-graph-up"></i> View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$page_scripts = '
<script>
    setTimeout(function() {
        location.reload();
    }, 300000);
</script>
';

include '../includes/footer.php'; 
?>