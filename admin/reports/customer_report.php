<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Customer Report';
$base_path = '../../';
$breadcrumb = ['Admin' => '../dashboard.php', 'Reports' => 'index.php'];

$status = $_GET['status'] ?? '';
$agent_id = $_GET['agent_id'] ?? '';
$search = $_GET['search'] ?? '';

$query = "SELECT 
    c.*, CONCAT(u.full_name) as created_by_name,
    (SELECT COUNT(*) FROM savings_accounts WHERE customer_id = c.id AND status = 'active') as savings_count,
    (SELECT COALESCE(SUM(balance), 0) FROM savings_accounts WHERE customer_id = c.id AND status = 'active') as total_savings,
    (SELECT COUNT(*) FROM loans WHERE customer_id = c.id) as total_loans,
    (SELECT COUNT(*) FROM loans WHERE customer_id = c.id AND status IN ('active', 'disbursed')) as active_loans,
    (SELECT COALESCE(SUM(balance), 0) FROM loans WHERE customer_id = c.id AND status IN ('active', 'disbursed')) as outstanding_loans,
    (SELECT COALESCE(SUM(amount_paid), 0) FROM loans WHERE customer_id = c.id) as total_repaid
    FROM customers c LEFT JOIN users u ON c.created_by = u.id WHERE 1=1";
$params = [];

if ($status) { $query .= " AND c.status = :status"; $params[':status'] = $status; }
if ($agent_id) { $query .= " AND c.agent_id = :agent_id"; $params[':agent_id'] = $agent_id; }
if ($search) {
    $query .= " AND (c.first_name LIKE :s1 OR c.last_name LIKE :s2 OR c.phone LIKE :s3 OR c.customer_code LIKE :s4)";
    $st = "%$search%";
    $params[':s1'] = $st; $params[':s2'] = $st; $params[':s3'] = $st; $params[':s4'] = $st;
}

$query .= " ORDER BY c.created_at DESC LIMIT 200";
$customers = dbQuery($query, $params);

$summary = dbSingle("SELECT COUNT(*) as total, COUNT(CASE WHEN status='active' THEN 1 END) as active, COUNT(CASE WHEN status='inactive' THEN 1 END) as inactive, COUNT(CASE WHEN status='blacklisted' THEN 1 END) as blacklisted FROM customers");
$agents = getActiveAgents();

$pdf_params = http_build_query(['status'=>$status, 'agent_id'=>$agent_id, 'search'=>$search]);

include '../../includes/header.php';
?>

<!-- Summary Cards -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card bg-primary text-white"><div class="card-body text-center py-2"><small>Total</small><h5 class="mb-0"><?php echo number_format($summary['total']); ?></h5></div></div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white"><div class="card-body text-center py-2"><small>Active</small><h5 class="mb-0"><?php echo number_format($summary['active']); ?></h5></div></div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-white"><div class="card-body text-center py-2"><small>Inactive</small><h5 class="mb-0"><?php echo number_format($summary['inactive']); ?></h5></div></div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white"><div class="card-body text-center py-2"><small>Blacklisted</small><h5 class="mb-0"><?php echo number_format($summary['blacklisted']); ?></h5></div></div>
    </div>
</div>

<div class="card">
    <div class="card-header py-2">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="bi bi-people-fill"></i> Customer Report</h5>
            <div class="d-flex gap-2 align-items-center">
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control form-control-sm" style="width: 180px;" 
                           placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="status" class="form-select form-select-sm" style="width: 130px;">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status=='active'?'selected':''; ?>>Active</option>
                        <option value="inactive" <?php echo $status=='inactive'?'selected':''; ?>>Inactive</option>
                        <option value="blacklisted" <?php echo $status=='blacklisted'?'selected':''; ?>>Blacklisted</option>
                    </select>
                    <select name="agent_id" class="form-select form-select-sm" style="width: 150px;">
                        <option value="">All Agents</option>
                        <?php foreach($agents as $agt): ?>
                            <option value="<?php echo $agt['id']; ?>" <?php echo $agent_id==$agt['id']?'selected':''; ?>>
                                <?php echo htmlspecialchars($agt['first_name'].' '.$agt['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm" title="Filter"><i class="bi bi-funnel"></i></button>
                    <a href="customer_report.php" class="btn btn-outline-secondary btn-sm" title="Clear"><i class="bi bi-x"></i></a>
                </form>
                <div class="border-start ps-2 d-flex gap-1">
                    <button onclick="window.print()" class="btn btn-secondary btn-sm" title="Print"><i class="bi bi-printer"></i></button>
                    <a href="pdf/customer_pdf.php?<?php echo $pdf_params; ?>" class="btn btn-danger btn-sm" title="PDF" target="_blank"><i class="bi bi-file-pdf"></i></a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if($search || $status || $agent_id): ?>
        <div class="alert alert-info py-1 px-3 mb-2 small">
            Filters: 
            <?php if($search): ?><strong>"<?php echo htmlspecialchars($search); ?>"</strong> <?php endif; ?>
            <?php if($status): ?>| Status: <strong><?php echo $status; ?></strong> <?php endif; ?>
            <?php if($agent_id): ?>| Agent filtered <?php endif; ?>
            - <strong><?php echo count($customers); ?></strong> results
        </div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Agent</th>
                        <th class="text-end">Savings</th>
                        <th class="text-center">Loans</th>
                        <th class="text-center">Active</th>
                        <th class="text-end">Outstanding</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($customers)>0): ?>
                        <?php foreach($customers as $c): ?>
                        <tr>
                            <td><code class="small"><?php echo htmlspecialchars($c['customer_code']); ?></code></td>
                            <td><strong><?php echo htmlspecialchars($c['first_name'].' '.$c['last_name']); ?></strong></td>
                            <td class="small"><?php echo htmlspecialchars($c['phone']); ?></td>
                            <td><?php echo getStatusBadge($c['status'],'customer'); ?></td>
                            <td class="small"><?php echo getAgentName($c['agent_id']); ?></td>
                            <td class="text-end text-success"><?php echo $c['savings_count']>0?formatMoney($c['total_savings']):'-'; ?></td>
                            <td class="text-center"><?php echo number_format($c['total_loans']); ?></td>
                            <td class="text-center"><?php echo $c['active_loans']>0?'<span class="badge bg-warning">'.$c['active_loans'].'</span>':'0'; ?></td>
                            <td class="text-end text-danger"><?php echo $c['outstanding_loans']>0?formatMoney($c['outstanding_loans']):'-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">No customers found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>