<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Restructure Loans';
$base_path = '../../';
$breadcrumb = ['Admin' => '../dashboard.php'];

$errors = [];

// Handle loan restructuring
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['loan_id'])) {
    $loan_id = $_POST['loan_id'];
    $new_duration = (int)($_POST['new_duration_months'] ?? 0);
    $new_interest_rate = (float)($_POST['new_interest_rate'] ?? 0);
    $reason = trim($_POST['restructure_reason'] ?? '');
    
    // Validate
    if ($new_duration <= 0) $errors[] = 'Valid duration is required';
    if ($new_duration > 24) $errors[] = 'New duration cannot exceed 24 months';
    if ($new_interest_rate <= 0 || $new_interest_rate > 50) $errors[] = 'Interest rate must be between 1% and 50%';
    if (empty($reason)) $errors[] = 'Reason for restructuring is required';
    
    if (empty($errors)) {
        // Get current loan details
        $loan = dbSingle("SELECT l.*, lp.interest_type FROM loans l JOIN loan_products lp ON l.product_id = lp.id WHERE l.id = :id", [':id' => $loan_id]);
        
        if (!$loan) {
            $errors[] = 'Loan not found';
        } elseif (!in_array($loan['status'], ['active', 'disbursed', 'defaulted'])) {
            $errors[] = 'Only active or defaulted loans can be restructured';
        } else {
            // Calculate new terms
            $remaining_balance = $loan['balance'];
            $remaining_months = $new_duration;
            
            // Calculate new interest
            if ($loan['interest_type'] == 'flat') {
                $new_total_interest = $remaining_balance * ($new_interest_rate / 100) * ($remaining_months / 12);
            } else {
                $monthly_principal = $remaining_balance / $remaining_months;
                $temp_balance = $remaining_balance;
                $new_total_interest = 0;
                
                for ($i = 1; $i <= $remaining_months; $i++) {
                    $monthly_interest = ($temp_balance * ($new_interest_rate / 100)) / 12;
                    $new_total_interest += $monthly_interest;
                    $temp_balance -= $monthly_principal;
                }
            }
            
            $new_total_amount = $remaining_balance + $new_total_interest;
            $new_monthly_payment = $new_total_amount / $remaining_months;
            $new_expected_end_date = date('Y-m-d', strtotime('+' . $remaining_months . ' months'));
            
            try {
                $db = getDB();
                $db->beginTransaction();
                
                // Store old values
                $old_values = [
                    'duration_months' => $loan['duration_months'],
                    'interest_rate' => $loan['interest_rate'],
                    'total_interest' => $loan['total_interest'],
                    'total_amount' => $loan['total_amount'],
                    'monthly_payment' => $loan['monthly_payment'],
                    'expected_end_date' => $loan['expected_end_date']
                ];
                
                // Update loan
                $db->query("UPDATE loans SET 
                    interest_rate = :rate,
                    total_interest = :total_interest,
                    total_amount = :total_amount,
                    monthly_payment = :monthly_payment,
                    duration_months = :duration,
                    expected_end_date = :end_date,
                    status = 'active',
                    notes = CONCAT(COALESCE(notes, ''), '\nRestructured on ', CURDATE(), ': ', :reason)
                    WHERE id = :id");
                
                $db->bindMultiple([
                    ':rate' => $new_interest_rate,
                    ':total_interest' => round($new_total_interest, 2),
                    ':total_amount' => round($new_total_amount, 2),
                    ':monthly_payment' => round($new_monthly_payment, 2),
                    ':duration' => $remaining_months,
                    ':end_date' => $new_expected_end_date,
                    ':reason' => $reason,
                    ':id' => $loan_id
                ]);
                $db->execute();
                
                // Delete old pending schedule
                $db->query("DELETE FROM loan_schedule WHERE loan_id = :loan_id AND status IN ('pending', 'partial')");
                $db->bind(':loan_id', $loan_id);
                $db->execute();
                
                // Generate new schedule
                $monthly_principal = $remaining_balance / $remaining_months;
                $temp_balance = $remaining_balance;
                $start_installment = $loan['duration_months'] - $remaining_months;
                
                for ($i = 1; $i <= $remaining_months; $i++) {
                    if ($loan['interest_type'] == 'flat') {
                        $monthly_interest = ($remaining_balance * ($new_interest_rate / 100)) / 12;
                    } else {
                        $monthly_interest = ($temp_balance * ($new_interest_rate / 100)) / 12;
                        $temp_balance -= $monthly_principal;
                    }
                    
                    $monthly_total = $monthly_principal + $monthly_interest;
                    $due_date = date('Y-m-d', strtotime('+' . $i . ' months'));
                    
                    $db->query("INSERT INTO loan_schedule 
                        (loan_id, installment_number, due_date, principal_amount, interest_amount, total_amount, balance_after, status) 
                        VALUES 
                        (:loan_id, :installment, :due_date, :principal, :interest, :total, :balance, 'pending')");
                    
                    $db->bindMultiple([
                        ':loan_id' => $loan_id,
                        ':installment' => $start_installment + $i,
                        ':due_date' => $due_date,
                        ':principal' => round($monthly_principal, 2),
                        ':interest' => round($monthly_interest, 2),
                        ':total' => round($monthly_total, 2),
                        ':balance' => round($temp_balance, 2)
                    ]);
                    $db->execute();
                }
                
                $db->commit();
                
                $new_values = [
                    'duration_months' => $remaining_months,
                    'interest_rate' => $new_interest_rate,
                    'monthly_payment' => round($new_monthly_payment, 2)
                ];
                logActivity('Loan Restructured', 'loans', $loan_id, $old_values, $new_values);
                
                setFlash('success', 'Loan #' . $loan['loan_number'] . ' has been restructured successfully');
                redirect('restructure.php');
                
            } catch (Exception $e) {
                $db->rollback();
                error_log("Restructure Error: " . $e->getMessage());
                $errors[] = 'Failed to restructure loan: ' . $e->getMessage();
            }
        }
    }
}

// Get loans available for restructuring
$loans = dbQuery(
    "SELECT l.*, 
     CONCAT(c.first_name, ' ', c.last_name) as customer_name,
     c.phone as customer_phone, c.customer_code,
     lp.product_name, lp.interest_type as product_interest_type,
     DATEDIFF(l.expected_end_date, CURDATE()) as days_remaining,
     ROUND((l.amount_paid / l.total_amount) * 100, 2) as repayment_percentage
     FROM loans l 
     JOIN customers c ON l.customer_id = c.id 
     JOIN loan_products lp ON l.product_id = lp.id 
     WHERE l.status IN ('active', 'disbursed', 'defaulted') 
     AND l.balance > 0
     ORDER BY l.status ASC, l.expected_end_date ASC"
);

// Get today's restructured loans
$today_restructured = dbQuery(
    "SELECT l.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name
     FROM loans l 
     JOIN customers c ON l.customer_id = c.id 
     WHERE l.notes LIKE '%Restructured on%' 
     AND DATE(l.updated_at) = CURDATE()
     ORDER BY l.updated_at DESC"
);

include '../../includes/header.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Stats -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h6>Available for Restructure</h6>
                <h3><?php echo count($loans); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6>Restructured Today</h6>
                <h3><?php echo count($today_restructured); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Total Outstanding</h6>
                <h3><?php echo formatMoney(array_sum(array_column($loans, 'balance'))); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Loans List -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-arrow-repeat"></i> Loans Available for Restructuring</h5>
            </div>
            <div class="card-body">
                <?php if (count($loans) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead class="table-light">
                                <tr>
                                    <th>Loan #</th>
                                    <th>Customer</th>
                                    <th>Current Terms</th>
                                    <th>Status</th>
                                    <th>Repayment</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($loans as $loan): ?>
                                <tr>
                                    <td>
                                        <code><?php echo htmlspecialchars($loan['loan_number']); ?></code>
                                        <?php if ($loan['days_remaining'] < 30 && $loan['days_remaining'] > 0): ?>
                                            <span class="badge bg-danger">Due Soon</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($loan['customer_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($loan['customer_code']); ?></small>
                                    </td>
                                    <td>
                                        <small>
                                            Balance: <?php echo formatMoney($loan['balance']); ?><br>
                                            Rate: <?php echo $loan['interest_rate']; ?>%<br>
                                            Monthly: <?php echo formatMoney($loan['monthly_payment']); ?><br>
                                            Ends: <?php echo date('M d, Y', strtotime($loan['expected_end_date'])); ?>
                                        </small>
                                    </td>
                                    <td><?php echo getStatusBadge($loan['status'], 'loan'); ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-<?php 
                                                echo $loan['repayment_percentage'] >= 80 ? 'success' : 
                                                    ($loan['repayment_percentage'] >= 50 ? 'warning' : 'danger'); 
                                            ?>" 
                                            style="width: <?php echo $loan['repayment_percentage']; ?>%">
                                                <?php echo $loan['repayment_percentage']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#restructureModal<?php echo $loan['id']; ?>">
                                            <i class="bi bi-arrow-repeat"></i> Restructure
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
                        <p class="text-muted mb-0 mt-2">No loans available for restructuring</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Restructured Today -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Restructured Today</h5>
            </div>
            <div class="card-body">
                <?php if (count($today_restructured) > 0): ?>
                    <?php foreach ($today_restructured as $loan): ?>
                    <div class="border rounded p-3 mb-2">
                        <div class="d-flex justify-content-between">
                            <strong><?php echo htmlspecialchars($loan['loan_number']); ?></strong>
                            <span class="badge bg-warning">Restructured</span>
                        </div>
                        <small class="text-muted">
                            <?php echo htmlspecialchars($loan['customer_name']); ?><br>
                            New Rate: <?php echo $loan['interest_rate']; ?>%<br>
                            Balance: <?php echo formatMoney($loan['balance']); ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center mb-0">No loans restructured today</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Restructure Modals -->
<?php foreach ($loans as $loan): ?>
<div class="modal fade" id="restructureModal<?php echo $loan['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="bi bi-arrow-repeat"></i> Restructure Loan: <?php echo htmlspecialchars($loan['loan_number']); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                    
                    <div class="alert alert-info">
                        <h6>Current Loan Details</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <small>
                                    <strong>Customer:</strong> <?php echo htmlspecialchars($loan['customer_name']); ?><br>
                                    <strong>Product:</strong> <?php echo htmlspecialchars($loan['product_name']); ?>
                                </small>
                            </div>
                            <div class="col-md-6">
                                <small>
                                    <strong>Balance:</strong> <?php echo formatMoney($loan['balance']); ?><br>
                                    <strong>Current Rate:</strong> <?php echo $loan['interest_rate']; ?>%<br>
                                    <strong>Monthly:</strong> <?php echo formatMoney($loan['monthly_payment']); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">New Duration (Months) *</label>
                            <input type="number" name="new_duration_months" class="form-control" 
                                   min="1" max="24" value="<?php echo $loan['duration_months']; ?>" required>
                            <small class="text-muted">Maximum 24 months</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">New Interest Rate (%) *</label>
                            <input type="number" name="new_interest_rate" class="form-control" 
                                   step="0.01" min="1" max="50" value="<?php echo $loan['interest_rate']; ?>" required>
                            <small class="text-muted">Between 1% and 50%</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Restructuring *</label>
                        <textarea name="restructure_reason" class="form-control" rows="3" required
                                  placeholder="Explain why this loan needs to be restructured..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" 
                            onclick="return confirm('Are you sure you want to restructure this loan?')">
                        <i class="bi bi-arrow-repeat"></i> Confirm Restructure
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include ('../../includes/footer.php'); ?>