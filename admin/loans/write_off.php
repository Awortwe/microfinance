<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Write Off Loans';
$base_path = '../../';
$breadcrumb = ['Admin' => '../dashboard.php'];

// Handle write-off action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['loan_id'])) {
    $loan_id = $_POST['loan_id'];
    $write_off_reason = trim($_POST['write_off_reason'] ?? '');
    
    if (empty($write_off_reason)) {
        setFlash('error', 'Please provide a reason for writing off this loan');
    } else {
        // Get loan details
        $loan = dbSingle("SELECT * FROM loans WHERE id = :id", [':id' => $loan_id]);
        
        if (!$loan) {
            setFlash('error', 'Loan not found');
        } elseif (!in_array($loan['status'], ['defaulted', 'active', 'disbursed'])) {
            setFlash('error', 'Only defaulted or active loans can be written off');
        } else {
            // Write off the loan
            $notes = "Written off on " . date('Y-m-d') . " by admin. Reason: " . $write_off_reason;
            if ($loan['notes']) {
                $notes = $loan['notes'] . "\n" . $notes;
            }
            
            dbExecute(
                "UPDATE loans SET status = 'written_off', notes = :notes WHERE id = :id",
                [
                    ':notes' => $notes,
                    ':id' => $loan_id
                ]
            );
            
            logActivity('Loan Written Off', 'loans', $loan_id, 
                       ['status' => $loan['status']], 
                       ['status' => 'written_off', 'reason' => $write_off_reason]);
            
            setFlash('success', 'Loan #' . $loan['loan_number'] . ' has been written off successfully');
        }
    }
    redirect('write_off.php');
}

// Get defaulted loans and active loans that can be written off
$loans = dbQuery(
    "SELECT l.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name,
     c.phone as customer_phone, c.customer_code
     FROM loans l 
     JOIN customers c ON l.customer_id = c.id 
     WHERE l.status IN ('defaulted', 'active', 'disbursed')
     ORDER BY l.status ASC, l.updated_at DESC"
);

// Get recently written off loans
$written_off = dbQuery(
    "SELECT l.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name
     FROM loans l 
     JOIN customers c ON l.customer_id = c.id 
     WHERE l.status = 'written_off' 
     AND DATE(l.updated_at) = CURDATE()
     ORDER BY l.updated_at DESC"
);

include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h6>Defaulted Loans</h6>
                <h3>
                    <?php 
                    $defaulted = array_filter($loans, function($l) { return $l['status'] == 'defaulted'; });
                    echo count($defaulted); 
                    ?>
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6>Active Loans</h6>
                <h3>
                    <?php 
                    $active = array_filter($loans, function($l) { return in_array($l['status'], ['active', 'disbursed']); });
                    echo count($active); 
                    ?>
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h6>Written Off Today</h6>
                <h3><?php echo count($written_off); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark text-white">
            <div class="card-body text-center">
                <h6>Total Write-offs</h6>
                <h3>
                    <?php 
                    $total = dbSingle("SELECT COUNT(*) as count FROM loans WHERE status = 'written_off'");
                    echo $total['count']; 
                    ?>
                </h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-x-circle"></i> Write Off Loans</h5>
    </div>
    <div class="card-body">
        <?php if (count($loans) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Loan #</th>
                            <th>Customer</th>
                            <th>Principal</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loans as $loan): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($loan['loan_number']); ?></code></td>
                            <td>
                                <strong><?php echo htmlspecialchars($loan['customer_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($loan['customer_code']); ?></small><br>
                                <small><?php echo htmlspecialchars($loan['customer_phone']); ?></small>
                            </td>
                            <td><?php echo formatMoney($loan['principal_amount']); ?></td>
                            <td class="text-success"><?php echo formatMoney($loan['amount_paid']); ?></td>
                            <td class="text-danger fw-bold"><?php echo formatMoney($loan['balance']); ?></td>
                            <td><?php echo getStatusBadge($loan['status'], 'loan'); ?></td>
                            <td><small><?php echo date('M d, Y', strtotime($loan['updated_at'])); ?></small></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#writeOffModal<?php echo $loan['id']; ?>">
                                    <i class="bi bi-trash"></i> Write Off
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="bi bi-check-circle display-4 text-success"></i>
                <p class="text-muted mb-0 mt-2">No loans available for write-off</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Write Off Modals -->
<?php foreach ($loans as $loan): ?>
<div class="modal fade" id="writeOffModal<?php echo $loan['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle"></i> Write Off Loan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                    
                    <div class="alert alert-warning">
                        <strong>Loan:</strong> <?php echo htmlspecialchars($loan['loan_number']); ?><br>
                        <strong>Customer:</strong> <?php echo htmlspecialchars($loan['customer_name']); ?><br>
                        <strong>Outstanding Balance:</strong> <?php echo formatMoney($loan['balance']); ?><br>
                        <strong>Amount Paid:</strong> <?php echo formatMoney($loan['amount_paid']); ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Write-off *</label>
                        <textarea name="write_off_reason" class="form-control" rows="3" required
                                  placeholder="Explain why this loan needs to be written off..."></textarea>
                    </div>
                    
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>Warning:</strong> This action will write off the outstanding balance of 
                        <?php echo formatMoney($loan['balance']); ?>. This cannot be undone.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" 
                            onclick="return confirm('Are you absolutely sure you want to write off this loan?')">
                        <i class="bi bi-trash"></i> Confirm Write Off
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include ('../../includes/footer.php'); ?>