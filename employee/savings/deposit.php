<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = 'Make Deposit';
$base_path = '../../';
$breadcrumb = [
    'Dashboard' => '../dashboard.php',
    'Savings' => 'index.php'
];

$errors = [];
$account_id = $_GET['account_id'] ?? 0;
$account = null;

if ($account_id) {
    $account = dbSingle(
        "SELECT sa.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name,
         c.phone, c.customer_code
         FROM savings_accounts sa
         JOIN customers c ON sa.customer_id = c.id
         WHERE sa.id = :id",
        [':id' => $account_id]
    );
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $account_id = $_POST['account_id'];
    $amount = $_POST['amount'] ?? 0;
    
    if (!$account_id) $errors[] = 'Account is required';
    if ($amount <= 0) $errors[] = 'Valid amount is required';
    
    if (empty($errors)) {
        try {
            $db = getDB();
            
            // Get current account
            $account = dbSingle("SELECT * FROM savings_accounts WHERE id = :id", [':id' => $account_id]);
            
            if (!$account) {
                $errors[] = 'Account not found';
            } elseif ($account['status'] != 'active') {
                $errors[] = 'Account is not active';
            } else {
                $db->beginTransaction();
                
                $balance_before = $account['balance'];
                $balance_after = $balance_before + $amount;
                
                // Insert transaction
                $db->query("INSERT INTO savings_transactions 
                         (account_id, transaction_type, amount, balance_before, balance_after,
                          description, transaction_date, transaction_time, payment_method, processed_by)
                         VALUES 
                         (:account_id, 'deposit', :amount, :balance_before, :balance_after,
                          :description, CURDATE(), CURTIME(), :payment_method, :processed_by)");
                
                $db->bindMultiple([
                    ':account_id' => $account_id,
                    ':amount' => $amount,
                    ':balance_before' => $balance_before,
                    ':balance_after' => $balance_after,
                    ':description' => $_POST['description'] ?? null,
                    ':payment_method' => $_POST['payment_method'] ?? 'cash',
                    ':processed_by' => $_SESSION['user_id']
                ]);
                $db->execute();
                
                // Update balance
                $db->query("UPDATE savings_accounts SET balance = balance + :amount WHERE id = :id");
                $db->bind(':amount', $amount);
                $db->bind(':id', $account_id);
                $db->execute();
                
                $db->commit();
                logActivity('Deposit Made', 'savings', $account_id);
                
                setFlash('success', 'Deposit of ' . formatMoney($amount) . ' successful!');
                redirect('statement.php?account_id=' . $account_id);
            }
        } catch (Exception $e) {
            if (isset($db)) $db->rollback();
            error_log("Deposit Error: " . $e->getMessage());
            $errors[] = 'Failed to process deposit';
        }
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Make Deposit</h5>
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

                <?php if ($account): ?>
                <div class="alert alert-info">
                    <strong>Account:</strong> <?php echo htmlspecialchars($account['account_number']); ?><br>
                    <strong>Customer:</strong> <?php echo htmlspecialchars($account['customer_name']); ?><br>
                    <strong>Current Balance:</strong> <?php echo formatMoney($account['balance']); ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="account_id" value="<?php echo $account_id; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Amount (GHS) *</label>
                        <input type="number" name="amount" class="form-control form-control-lg" 
                               step="0.01" min="0.01" required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash">Cash</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="transfer">Bank Transfer</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-control" 
                               placeholder="Optional note...">
                    </div>
                    
                    <button type="submit" class="btn btn-success btn-lg w-100">
                        <i class="bi bi-check-circle"></i> Confirm Deposit
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>