<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = 'Disburse Loan';
$base_path = '../../';
$breadcrumb = [
    'Dashboard' => '../dashboard.php',
    'Loans' => 'index.php'
];

$errors = [];

// Get approved loans ready for disbursement
$approved_loans = dbQuery(
    "SELECT l.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name,
     c.phone as customer_phone, c.customer_code,
     lp.product_name, lp.interest_type as product_interest_type
     FROM loans l
     JOIN customers c ON l.customer_id = c.id
     JOIN loan_products lp ON l.product_id = lp.id
     WHERE l.status = 'approved'
     ORDER BY l.approval_date ASC"
);

// Handle disbursement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['loan_id'])) {
    $loan_id = $_POST['loan_id'];
    $disbursement_date = $_POST['disbursement_date'] ?? date('Y-m-d');
    $disbursement_method = $_POST['disbursement_method'] ?? 'cash';
    $reference = $_POST['reference_number'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if (empty($disbursement_date)) {
        $errors[] = 'Disbursement date is required';
    }
    
    if (empty($errors)) {
        try {
            $db = getDB();
            $db->beginTransaction();
            
            // Get loan details
            $loan = dbSingle(
                "SELECT l.*, lp.interest_type, lp.interest_rate 
                 FROM loans l 
                 JOIN loan_products lp ON l.product_id = lp.id 
                 WHERE l.id = :id AND l.status = 'approved'",
                [':id' => $loan_id]
            );
            
            if (!$loan) {
                $errors[] = 'Loan not found or not in approved status';
            } else {
                $expected_end_date = date('Y-m-d', strtotime($disbursement_date . ' + ' . $loan['duration_months'] . ' months'));
                
                $notes_text = "Disbursed on $disbursement_date via $disbursement_method";
                if ($reference) $notes_text .= " (Ref: $reference)";
                if ($notes) $notes_text .= "\nNotes: $notes";
                
                // Update loan
                $db->query("UPDATE loans SET 
                    status = 'disbursed',
                    disbursed_by = :disbursed_by,
                    disbursement_date = :disbursement_date,
                    expected_end_date = :expected_end_date,
                    notes = CONCAT(COALESCE(notes, ''), '\n', :notes_text)
                    WHERE id = :id");
                
                $db->bindMultiple([
                    ':disbursed_by' => $_SESSION['user_id'],
                    ':disbursement_date' => $disbursement_date,
                    ':expected_end_date' => $expected_end_date,
                    ':notes_text' => $notes_text,
                    ':id' => $loan_id
                ]);
                $db->execute();
                
                // Generate repayment schedule
                $principal = $loan['principal_amount'];
                $rate = $loan['interest_rate'];
                $months = $loan['duration_months'];
                $type = $loan['interest_type'];
                
                $monthly_principal = $principal / $months;
                $balance = $principal;
                
                // Delete existing schedule
                $db->query("DELETE FROM loan_schedule WHERE loan_id = :loan_id");
                $db->bind(':loan_id', $loan_id);
                $db->execute();
                
                // Create new schedule
                for ($i = 1; $i <= $months; $i++) {
                    if ($type == 'flat') {
                        $monthly_interest = ($principal * ($rate / 100)) / 12;
                    } else {
                        $monthly_interest = ($balance * ($rate / 100)) / 12;
                        $balance -= $monthly_principal;
                    }
                    
                    $monthly_total = $monthly_principal + $monthly_interest;
                    $due_date = date('Y-m-d', strtotime($disbursement_date . ' + ' . $i . ' months'));
                    
                    $db->query("INSERT INTO loan_schedule 
                        (loan_id, installment_number, due_date, principal_amount, interest_amount, total_amount, balance_after, status) 
                        VALUES 
                        (:loan_id, :installment, :due_date, :principal, :interest, :total, :balance, 'pending')");
                    
                    $db->bindMultiple([
                        ':loan_id' => $loan_id,
                        ':installment' => $i,
                        ':due_date' => $due_date,
                        ':principal' => round($monthly_principal, 2),
                        ':interest' => round($monthly_interest, 2),
                        ':total' => round($monthly_total, 2),
                        ':balance' => round($balance, 2)
                    ]);
                    $db->execute();
                }
                
                $db->commit();
                logActivity('Loan Disbursed', 'loans', $loan_id);
                
                setFlash('success', 'Loan #' . $loan['loan_number'] . ' has been disbursed successfully!');
                redirect('schedule.php?loan_id=' . $loan_id);
            }
            
        } catch (Exception $e) {
            if (isset($db)) $db->rollback();
            error_log("Disburse Error: " . $e->getMessage());
            $errors[] = 'Failed to disburse loan';
        }
    }
}

include '../../includes/header.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-send"></i> Approved Loans Ready for Disbursement</h5>
            </div>
            <div class="card-body">
                <?php if (count($approved_loans) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead class="table-light">
                                <tr>
                                    <th>Loan #</th>
                                    <th>Customer</th>
                                    <th>Product</th>
                                    <th>Amount</th>
                                    <th>Monthly</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($approved_loans as $loan): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($loan['loan_number']); ?></code></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($loan['customer_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($loan['customer_code']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($loan['product_name']); ?></td>
                                    <td><?php echo formatMoney($loan['principal_amount']); ?></td>
                                    <td><?php echo formatMoney($loan['monthly_payment']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($loan['approval_date'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-success" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#disburseModal<?php echo $loan['id']; ?>">
                                            <i class="bi bi-send"></i> Disburse
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-inbox display-4 text-muted"></i>
                        <p class="text-muted mb-0">No approved loans waiting for disbursement</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Disbursement Modals -->
<?php foreach ($approved_loans as $loan): ?>
<div class="modal fade" id="disburseModal<?php echo $loan['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-send"></i> Disburse Loan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                    
                    <div class="alert alert-info">
                        <strong>Loan:</strong> <?php echo htmlspecialchars($loan['loan_number']); ?><br>
                        <strong>Customer:</strong> <?php echo htmlspecialchars($loan['customer_name']); ?><br>
                        <strong>Amount:</strong> <?php echo formatMoney($loan['principal_amount']); ?><br>
                        <strong>Monthly Payment:</strong> <?php echo formatMoney($loan['monthly_payment']); ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Disbursement Date *</label>
                        <input type="date" name="disbursement_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Method *</label>
                        <select name="disbursement_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reference Number</label>
                        <input type="text" name="reference_number" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" 
                            onclick="return confirm('Confirm disbursement of this loan?')">
                        <i class="bi bi-send"></i> Confirm Disbursement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include '../../includes/footer.php'; ?>