<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'View Agent';
$base_path = '../../';
$breadcrumb = ['Admin' => '../dashboard.php', 'Agents' => 'index.php'];

$agent_id = $_GET['id'] ?? 0;

$agent = dbSingle("SELECT a.*, CONCAT(u.full_name) as created_by_name 
                   FROM agents a LEFT JOIN users u ON a.created_by = u.id 
                   WHERE a.id = :id", [':id' => $agent_id]);

if (!$agent) {
    setFlash('error', 'Agent not found');
    redirect('index.php');
}

// Get customers assigned to this agent
$customers = dbQuery(
    "SELECT * FROM customers WHERE agent_id = :agent_id ORDER BY created_at DESC",
    [':agent_id' => $agent_id]
);

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="bg-primary text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" 
                     style="width: 80px; height: 80px; font-size: 1.5rem;">
                    <?php echo strtoupper(substr($agent['first_name'], 0, 1) . substr($agent['last_name'], 0, 1)); ?>
                </div>
                <h4><?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?></h4>
                <code><?php echo htmlspecialchars($agent['agent_code']); ?></code><br>
                <span class="badge bg-<?php echo $agent['status'] == 'active' ? 'success' : 'secondary'; ?>">
                    <?php echo ucfirst($agent['status']); ?>
                </span>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0">Contact Info</h6></div>
            <div class="card-body">
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($agent['phone']); ?></p>
                <p><strong>Email:</strong> <?php echo $agent['email'] ? htmlspecialchars($agent['email']) : 'N/A'; ?></p>
                <p><strong>Address:</strong> <?php echo $agent['address'] ? htmlspecialchars($agent['address']) : 'N/A'; ?></p>
                <p><strong>City:</strong> <?php echo $agent['city'] ? htmlspecialchars($agent['city']) : 'N/A'; ?></p>
                <p><strong>Gender:</strong> <?php echo $agent['gender'] ?? 'N/A'; ?></p>
                <p><strong>Total Customers:</strong> <?php echo count($customers); ?></p>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="edit.php?id=<?php echo $agent['id']; ?>" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit Agent
            </a>
            <a href="index.php" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-people"></i> Customers Under This Agent (<?php echo count($customers); ?>)</h6></div>
            <div class="card-body">
                <?php if (count($customers) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr><th>Code</th><th>Name</th><th>Phone</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($customer['customer_code']); ?></code></td>
                                    <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                    <td><?php echo getStatusBadge($customer['status'], 'customer'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No customers assigned to this agent</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>