<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = 'Record Collection';
$base_path = '../../';
$breadcrumb = [
    'Dashboard' => '../dashboard.php',
    'Collections' => 'index.php'
];

$errors = [];
$collection_type = $_GET['type'] ?? 'loan'; // loan or savings

// Get customers for dropdown
$customers = dbQuery("SELECT id, customer_code, first_name, last_name, phone FROM customers WHERE status = 'active' ORDER BY first_name");

// Get active loans for dropdown
$active_loans = [];
if ($collection_type == 'loan') {
    $active_loans = dbQuery(
        "SELECT l.id, l.loan_number, l.balance, l.monthly_payment,
         CONCAT(c.first_name, ' ', c.last_name) as customer_name
         FROM loans l
         JOIN customers c ON l.customer_id = c.id
         WHERE l.status IN ('disbursed', 'active')
         ORDER BY c.first_name"
    );
}

// Get savings accounts for dropdown
$savings_accounts = [];
if ($collection_type == 'savings') {
    $savings_accounts = dbQuery(
        "SELECT sa.id, sa.account_number, sa.balance,
         CONCAT(c.first_name, ' ', c.last_name) as customer_name
         FROM savings_accounts sa
         JOIN customers c ON sa.customer_id = c.id
         WHERE sa.status = 'active'
         ORDER BY c.first_name"
    );
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $collection_type = $_POST['collection_type'];
    $amount = $_POST['amount'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $reference = $_POST['reference_number'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if ($amount <= 0) {
        $errors[] = 'Valid amount is required';
    }
    
    if (empty($errors)) {
        try {
            $db = getDB();
            $db->beginTransaction();
            
            if ($collection_type == 'loan') {
                $loan_id = $_POST['loan_id'] ?? 0;
                
                if (!$loan_id) {
                    $errors[] = 'Please select a loan';
                } else {
                    $loan = dbSingle("SELECT * FROM loans WHERE id = :id", [':id' => $loan_id]);
                    
                    if (!$loan) {
                        $errors[] = 'Loan not found';
                    } elseif ($amount > $loan['balance']) {
                        $errors[] = 'Amount exceeds loan balance of ' . formatMoney($loan['balance']);
                    } else {
                        $interest_portion = ($loan['total_interest'] / $loan['total_amount']) * $amount;
                        $principal_portion = $amount - $interest_portion;
                        $balance_before = $loan['balance'];
                        $balance_after = $balance_before - $amount;
                        
                        $db->query("INSERT INTO loan_repayments 
                                 (loan_id, amount, principal_paid, interest_paid, balance_before, balance_after,
                                  payment_date, payment_time, payment_method, reference_number, notes, processed_by)
                                 VALUES 
                                 (:loan_id, :amount, :principal, :interest, :balance_before, :balance_after,
                                  CURDATE(), CURTIME(), :method, :reference, :notes, :processed_by)");
                        
                        $db->bindMultiple([
                            ':loan_id' => $loan_id,
                            ':amount' => $amount,
                            ':principal' => $principal_portion,
                            ':interest' => $interest_portion,
                            ':balance_before' => $balance_before,
                            ':balance_after' => $balance_after,
                            ':method' => $payment_method,
                            ':reference' => $reference,
                            ':notes' => $notes,
                            ':processed_by' => $_SESSION['user_id']
                        ]);
                        $db->execute();
                        
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
                        logActivity('Loan Collection Recorded', 'collections', $loan_id);
                        
                        $message = 'Loan repayment of ' . formatMoney($amount) . ' recorded successfully!';
                        if ($balance_after <= 0) $message .= ' Loan fully paid!';
                        setFlash('success', $message);
                        redirect('index.php');
                    }
                }
                
            } elseif ($collection_type == 'savings') {
                $account_id = $_POST['account_id'] ?? 0;
                
                if (!$account_id) {
                    $errors[] = 'Please select a savings account';
                } else {
                    $account = dbSingle("SELECT * FROM savings_accounts WHERE id = :id", [':id' => $account_id]);
                    
                    if (!$account) {
                        $errors[] = 'Account not found';
                    } elseif ($account['status'] != 'active') {
                        $errors[] = 'Account is not active';
                    } else {
                        $balance_before = $account['balance'];
                        $balance_after = $balance_before + $amount;
                        
                        $db->query("INSERT INTO savings_transactions 
                                 (account_id, transaction_type, amount, balance_before, balance_after,
                                  description, transaction_date, transaction_time, payment_method, processed_by)
                                 VALUES 
                                 (:account_id, 'deposit', :amount, :balance_before, :balance_after,
                                  :description, CURDATE(), CURTIME(), :method, :processed_by)");
                        
                        $db->bindMultiple([
                            ':account_id' => $account_id,
                            ':amount' => $amount,
                            ':balance_before' => $balance_before,
                            ':balance_after' => $balance_after,
                            ':description' => $notes ?: 'Collection deposit',
                            ':method' => $payment_method,
                            ':processed_by' => $_SESSION['user_id']
                        ]);
                        $db->execute();
                        
                        $db->query("UPDATE savings_accounts SET balance = balance + :amount WHERE id = :id");
                        $db->bind(':amount', $amount);
                        $db->bind(':id', $account_id);
                        $db->execute();
                        
                        $db->commit();
                        logActivity('Savings Collection Recorded', 'collections', $account_id);
                        
                        setFlash('success', 'Savings deposit of ' . formatMoney($amount) . ' recorded successfully!');
                        redirect('index.php');
                    }
                }
            }
            
            if (!empty($errors)) {
                $db->rollback();
            }
            
        } catch (Exception $e) {
            if (isset($db)) $db->rollback();
            error_log("Collection Error: " . $e->getMessage());
            $errors[] = 'Failed to record collection';
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
    <div class="col-md-8 mx-auto">
        <!-- Collection Type Selection -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <a href="?type=loan" class="btn btn-<?php echo $collection_type == 'loan' ? 'success' : 'outline-success'; ?> w-100 btn-lg">
                            <i class="bi bi-cash-stack"></i><br>
                            Loan Repayment
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="?type=savings" class="btn btn-<?php echo $collection_type == 'savings' ? 'primary' : 'outline-primary'; ?> w-100 btn-lg">
                            <i class="bi bi-piggy-bank"></i><br>
                            Savings Deposit
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Collection Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-<?php echo $collection_type == 'loan' ? 'cash-stack' : 'piggy-bank'; ?>"></i>
                    Record <?php echo $collection_type == 'loan' ? 'Loan Repayment' : 'Savings Deposit'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="collection_type" value="<?php echo $collection_type; ?>">
                    
                    <?php if ($collection_type == 'loan'): ?>
                    <div class="mb-3">
                        <label class="form-label">Select Active Loan *</label>
                        <select name="loan_id" class="form-select" required id="loanSelect" onchange="updateLoanInfo()">
                            <option value="">Choose a loan...</option>
                            <?php foreach ($active_loans as $loan): ?>
                                <option value="<?php echo $loan['id']; ?>"
                                        data-balance="<?php echo $loan['balance']; ?>"
                                        data-monthly="<?php echo $loan['monthly_payment']; ?>">
                                    <?php echo htmlspecialchars($loan['customer_name'] . ' - ' . $loan['loan_number'] . ' (Balance: ' . formatMoney($loan['balance']) . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="loanInfo" class="alert alert-info" style="display:none;">
                        <div class="row">
                            <div class="col-6">
                                <small><strong>Balance:</strong></small><br>
                                <span id="loanBalance" class="fw-bold text-danger">-</span>
                            </div>
                            <div class="col-6">
                                <small><strong>Monthly:</strong></small><br>
                                <span id="monthlyPayment" class="fw-bold">-</span>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label">Select Savings Account *</label>
                        <select name="account_id" class="form-select" required id="accountSelect" onchange="updateAccountInfo()">
                            <option value="">Choose an account...</option>
                            <?php foreach ($savings_accounts as $account): ?>
                                <option value="<?php echo $account['id']; ?>"
                                        data-balance="<?php echo $account['balance']; ?>">
                                    <?php echo htmlspecialchars($account['customer_name'] . ' - ' . $account['account_number'] . ' (Balance: ' . formatMoney($account['balance']) . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="accountInfo" class="alert alert-info" style="display:none;">
                        <small><strong>Current Balance:</strong></small><br>
                        <span id="accountBalance" class="fw-bold">-</span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount (GHS) *</label>
                        <input type="number" name="amount" class="form-control form-control-lg" 
                               step="0.01" min="0.01" required autofocus id="amountInput">
                        
                        <?php if ($collection_type == 'loan'): ?>
                        <div class="mt-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnMonthly">
                                Monthly Payment
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="btnFull">
                                Pay Full Balance
                            </button>
                        </div>
                        <?php endif; ?>
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
                    
                    <div class="mb-3">
                        <label class="form-label">Reference Number (Optional)</label>
                        <input type="text" name="reference_number" class="form-control" 
                               placeholder="Transaction ID, receipt number...">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" 
                                  placeholder="Any additional notes..."></textarea>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-<?php echo $collection_type == 'loan' ? 'success' : 'primary'; ?> btn-lg">
                            <i class="bi bi-check-circle"></i> 
                            Record <?php echo $collection_type == 'loan' ? 'Loan Repayment' : 'Savings Deposit'; ?>
                        </button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function updateLoanInfo() {
    const select = document.getElementById('loanSelect');
    const option = select.options[select.selectedIndex];
    const infoDiv = document.getElementById('loanInfo');
    
    if (select.value) {
        const balance = option.dataset.balance;
        const monthly = option.dataset.monthly;
        
        document.getElementById('loanBalance').textContent = 'GHS ' + parseFloat(balance).toFixed(2);
        document.getElementById('monthlyPayment').textContent = 'GHS ' + parseFloat(monthly).toFixed(2);
        infoDiv.style.display = 'block';
        
        document.getElementById('btnMonthly').onclick = function() {
            document.getElementById('amountInput').value = monthly;
        };
        document.getElementById('btnFull').onclick = function() {
            document.getElementById('amountInput').value = balance;
        };
    } else {
        infoDiv.style.display = 'none';
    }
}

function updateAccountInfo() {
    const select = document.getElementById('accountSelect');
    const option = select.options[select.selectedIndex];
    const infoDiv = document.getElementById('accountInfo');
    
    if (select.value) {
        document.getElementById('accountBalance').textContent = 'GHS ' + parseFloat(option.dataset.balance).toFixed(2);
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
}
</script>

<?php include '../../includes/footer.php'; ?>