<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'View User';
$base_path = '../../';
$breadcrumb = [
    'Admin' => '../dashboard.php',
    'Users' => 'index.php'
];

$user_id = $_GET['id'] ?? 0;

// Get user data
$user_data = dbSingle("SELECT * FROM users WHERE id = :id", [':id' => $user_id]);

if (!$user_data) {
    setFlash('error', 'User not found');
    redirect('index.php');
}

// Get user activities
$activities = dbQuery(
    "SELECT * FROM audit_trail WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 20",
    [':user_id' => $user_id]
);

// Get activity summary
$summary = dbQuery(
    "SELECT module, COUNT(*) as count FROM audit_trail WHERE user_id = :user_id GROUP BY module ORDER BY count DESC",
    [':user_id' => $user_id]
);

include '../../includes/header.php';
?>

<div class="row">
    <!-- User Profile Card -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="bg-primary text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" 
                     style="width: 100px; height: 100px; font-size: 2rem; font-weight: 600;">
                    <?php echo strtoupper(substr($user_data['full_name'], 0, 2)); ?>
                </div>
                <h4><?php echo htmlspecialchars($user_data['full_name']); ?></h4>
                <span class="badge bg-<?php echo $user_data['user_type'] == 'admin' ? 'danger' : 'primary'; ?> mb-2">
                    <i class="bi bi-<?php echo $user_data['user_type'] == 'admin' ? 'shield-lock' : 'person-badge'; ?>"></i>
                    <?php echo ucfirst($user_data['user_type']); ?>
                </span>
                <br>
                <span class="badge bg-<?php echo $user_data['is_active'] ? 'success' : 'secondary'; ?>">
                    <i class="bi bi-<?php echo $user_data['is_active'] ? 'check-circle' : 'x-circle'; ?>"></i>
                    <?php echo $user_data['is_active'] ? 'Active' : 'Inactive'; ?>
                </span>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Contact Information</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm">
                    <tr>
                        <td width="40%"><strong>Email:</strong></td>
                        <td><?php echo htmlspecialchars($user_data['email']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Phone:</strong></td>
                        <td><?php echo $user_data['phone'] ? htmlspecialchars($user_data['phone']) : '<span class="text-muted">N/A</span>'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Username:</strong></td>
                        <td><code><?php echo htmlspecialchars($user_data['username']); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>Last Login:</strong></td>
                        <td>
                            <?php if ($user_data['last_login']): ?>
                                <span class="text-success">
                                    <i class="bi bi-clock"></i> 
                                    <?php echo date('M d, Y h:i A', strtotime($user_data['last_login'])); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">Never</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Member Since:</strong></td>
                        <td><?php echo date('M d, Y', strtotime($user_data['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Last Updated:</strong></td>
                        <td><?php echo date('M d, Y', strtotime($user_data['updated_at'])); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-gear"></i> Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="edit.php?id=<?php echo $user_data['id']; ?>" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Edit User
                    </a>
                    <?php if ($user_data['id'] != $_SESSION['user_id']): ?>
                        <a href="delete.php?id=<?php echo $user_data['id']; ?>" 
                           class="btn btn-danger"
                           onclick="return confirm('Are you sure you want to deactivate this user?')">
                            <i class="bi bi-person-x"></i> Deactivate User
                        </a>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Users List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Activities Column -->
    <div class="col-md-8">
        <!-- Recent Activities -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-activity"></i> Recent Activities</h6>
                <span class="badge bg-info"><?php echo count($activities); ?> records</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Action</th>
                                <th>Module</th>
                                <th>Record ID</th>
                                <th>Date/Time</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($activities) > 0): ?>
                                <?php $count = 1; foreach ($activities as $activity): ?>
                                <tr>
                                    <td><?php echo $count++; ?></td>
                                    <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            $module_colors = [
                                                'users' => 'primary',
                                                'customers' => 'success',
                                                'loans' => 'warning',
                                                'savings' => 'info',
                                                'auth' => 'danger',
                                                'system' => 'dark'
                                            ];
                                            echo $module_colors[$activity['module']] ?? 'secondary';
                                        ?>">
                                            <?php echo htmlspecialchars($activity['module']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($activity['record_id']): ?>
                                            <code><?php echo $activity['record_id']; ?></code>
                                        <?php else: ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small>
                                            <?php echo date('M d, Y', strtotime($activity['created_at'])); ?>
                                            <br>
                                            <?php echo date('h:i A', strtotime($activity['created_at'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($activity['ip_address']); ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="bi bi-inbox display-4 text-muted"></i>
                                        <p class="text-muted mb-0 mt-2">No activities found for this user</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Activity Summary -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-bar-chart"></i> Activity Summary</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php 
                    $colors = ['primary', 'success', 'warning', 'info', 'danger', 'dark'];
                    $color_index = 0;
                    
                    if (count($summary) > 0):
                        foreach ($summary as $item):
                            $color = $colors[$color_index % count($colors)];
                            $color_index++;
                    ?>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="border rounded p-3 text-center">
                            <h3 class="text-<?php echo $color; ?> mb-1"><?php echo $item['count']; ?></h3>
                            <small class="text-muted text-uppercase"><?php echo $item['module']; ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="col-12 text-center text-muted">
                        <p>No activity data available</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>