<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = 'Make Loan Repayment';
$base_path = '../../';
$breadcrumb = [
    'Dashboard' => '../dashboard.php',
    'Loans' => 'index.php'
];

$errors = [];
$loan_id = $_GET['loan_id'] ?? 0;

// Get loan details
$loan = dbSingle(
    "SELECT l.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name,
     lp.product_name
     FROM loans l
     JOIN customers c ON l.customer_id = c.id
     JOIN loan_products lp ON l.product_id = lp.id
     WHERE l.id = :id",
    [':id' => $loan_id]
);

if (!$loan) {
    setFlash('error', 'Loan not found');
    redirect('index.php');
}

// Get recent repayments
$recent_repayments = dbQuery(
    "SELECT * FROM loan_repayments WHERE loan_id = :loan_id ORDER BY payment_date DESC LIMIT 5",
    [':loan_id' => $loan_id]
);

// Get active agents for dropdown
$agents = getActiveAgents();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = $_POST['amount'] ?? 0;
    $agent_id = $_POST['agent_id'] ?? null;
    
    if ($amount <= 0) $errors[] = 'Valid amount is required';
    if ($amount > $loan['balance']) $errors[] = 'Amount exceeds loan balance of ' . formatMoney($loan['balance']);
    
    if (empty($errors)) {
        try {
            $db = getDB();
            $db->beginTransaction();
            
            $interest_portion = ($loan['total_interest'] / $loan['total_amount']) * $amount;
            $principal_portion = $amount - $interest_portion;
            $balance_before = $loan['balance'];
            $balance_after = $balance_before - $amount;
            
            // Insert repayment with agent_id
            $db->query("INSERT INTO loan_repayments 
                     (loan_id, amount, principal_paid, interest_paid, balance_before, balance_after,
                      payment_date, payment_time, payment_method, agent_id, processed_by)
                     VALUES 
                     (:loan_id, :amount, :principal, :interest, :balance_before, :balance_after,
                      CURDATE(), CURTIME(), :method, :agent_id, :processed_by)");
            
            $db->bindMultiple([
                ':loan_id' => $loan_id,
                ':amount' => $amount,
                ':principal' => $principal_portion,
                ':interest' => $interest_portion,
                ':balance_before' => $balance_before,
                ':balance_after' => $balance_after,
                ':method' => $_POST['payment_method'] ?? 'cash',
                ':agent_id' => $agent_id,
                ':processed_by' => $_SESSION['user_id']
            ]);
            $db->execute();
            
            // Update loan balance
            $new_status = $balance_after <= 0 ? 'completed' : 'active';
            $db->query("UPDATE loans SET 
                      amount_paid = amount_paid + :amount,
                      balance = balance - :amount2,
                      status = :status,
                      actual_end_date = IF(:balance_after <= 0, CURDATE(), NULL)
                      WHERE id = :id");
            $db->bindMultiple([
                ':amount' => $amount,
                ':amount2' => $amount,
                ':status' => $new_status,
                ':balance_after' => $balance_after,
                ':id' => $loan_id
            ]);
            $db->execute();
            
            $db->commit();
            logActivity('Repayment Made', 'loans', $loan_id);
            
            $message = 'Repayment of ' . formatMoney($amount) . ' successful!';
            if ($balance_after <= 0) $message .= ' Loan fully paid!';
            setFlash('success', $message);
            redirect('schedule.php?loan_id=' . $loan_id);
            
        } catch (Exception $e) {
            if (isset($db)) $db->rollback();
            error_log("Repayment Error: " . $e->getMessage());
            $errors[] = 'Failed to process repayment';
        }
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <!-- Loan Details -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Loan Details</h6></div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Loan #:</strong></td>
                        <td><code><?php echo htmlspecialchars($loan['loan_number']); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>Customer:</strong></td>
                        <td><?php echo htmlspecialchars($loan['customer_name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Product:</strong></td>
                        <td><?php echo htmlspecialchars($loan['product_name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Principal:</strong></td>
                        <td><?php echo formatMoney($loan['principal_amount']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Interest:</strong></td>
                        <td><?php echo formatMoney($loan['total_interest']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Payable:</strong></td>
                        <td><?php echo formatMoney($loan['total_amount']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Paid:</strong></td>
                        <td class="text-success fw-bold"><?php echo formatMoney($loan['amount_paid']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Balance:</strong></td>
                        <td class="text-danger fw-bold fs-5"><?php echo formatMoney($loan['balance']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Monthly Payment:</strong></td>
                        <td><?php echo formatMoney($loan['monthly_payment']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td><?php echo getStatusBadge($loan['status'], 'loan'); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Recent Repayments -->
        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0">Recent Repayments</h6></div>
            <div class="card-body">
                <?php if (count($recent_repayments) > 0): ?>
                    <?php foreach ($recent_repayments as $rep): ?>
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <strong><?php echo formatMoney($rep['amount']); ?></strong>
                            <small class="text-muted"><?php echo date('M d, Y', strtotime($rep['payment_date'])); ?></small>
                        </div>
                        <small class="text-muted">
                            Balance after: <?php echo formatMoney($rep['balance_after']); ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center mb-0">No repayments yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Repayment Form -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-cash-coin"></i> Make Repayment</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    
                    <div class="mb-4">
                        <label class="form-label">Amount (GHS) *</label>
                        <input type="number" name="amount" class="form-control form-control-lg" 
                               step="0.01" min="0.01" max="<?php echo $loan['balance']; ?>" 
                               required autofocus
                               value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>">
                        <small class="text-muted">
                            Maximum payable: <strong><?php echo formatMoney($loan['balance']); ?></strong>
                        </small>
                        
                        <div class="mt-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" 
                                    onclick="document.querySelector('input[name=amount]').value=<?php echo $loan['monthly_payment']; ?>">
                                <i class="bi bi-calendar-check"></i> Monthly: <?php echo formatMoney($loan['monthly_payment']); ?>
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm" 
                                    onclick="document.querySelector('input[name=amount]').value=<?php echo $loan['balance']; ?>">
                                <i class="bi bi-check-circle"></i> Pay Full Balance: <?php echo formatMoney($loan['balance']); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Agent Selection -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-person-badge"></i> Collected By (Agent)
                        </label>
                        <select name="agent_id" class="form-select">
                            <option value="">Select Agent (Optional)</option>
                            <?php foreach ($agents as $agt): ?>
                                <option value="<?php echo $agt['id']; ?>" 
                                    <?php echo (isset($_POST['agent_id']) && $_POST['agent_id'] == $agt['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($agt['first_name'] . ' ' . $agt['last_name'] . ' - ' . $agt['phone']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Select the agent who collected this repayment</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash">Cash</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg"
                                onclick="return confirm('Confirm repayment of this amount?')">
                            <i class="bi bi-check-circle"></i> Confirm Repayment
                        </button>
                        <a href="schedule.php?loan_id=<?php echo $loan_id; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-calendar3"></i> View Schedule
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Loans
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Loan Summary Card -->
        <div class="card mt-3">
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <small class="text-muted d-block">Principal</small>
                        <strong><?php echo formatMoney($loan['principal_amount']); ?></strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Interest Rate</small>
                        <strong><?php echo $loan['interest_rate']; ?>%</strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Duration</small>
                        <strong><?php echo $loan['duration_months']; ?> months</strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Repayment Progress</small>
                        <?php 
                        $progress = ($loan['total_amount'] > 0) ? ($loan['amount_paid'] / $loan['total_amount']) * 100 : 0;
                        ?>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <small><?php echo round($progress, 1); ?>%</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>