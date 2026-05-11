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

// Get all active savings accounts for dropdown
$all_accounts = dbQuery(
    "SELECT sa.id, sa.account_number, sa.balance,
     CONCAT(c.first_name, ' ', c.last_name) as customer_name
     FROM savings_accounts sa
     JOIN customers c ON sa.customer_id = c.id
     WHERE sa.status = 'active'
     ORDER BY c.first_name"
);

$agents = getActiveAgents();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $account_id = $_POST['account_id'] ?? 0;
    $amount = $_POST['amount'] ?? 0;
    $agent_id = $_POST['agent_id'] ?? null;
    
    if (!$account_id) $errors[] = 'Please select an account';
    if ($amount <= 0) $errors[] = 'Valid amount is required';
    
    if (empty($errors)) {
        try {
            $db = getDB();
            
            $account = dbSingle("SELECT * FROM savings_accounts WHERE id = :id", [':id' => $account_id]);
            
            if (!$account) {
                $errors[] = 'Account not found';
            } elseif ($account['status'] != 'active') {
                $errors[] = 'Account is not active';
            } else {
                $db->beginTransaction();
                
                $balance_before = $account['balance'];
                $balance_after = $balance_before + $amount;
                
                $db->query("INSERT INTO savings_transactions 
                         (account_id, transaction_type, amount, balance_before, balance_after,
                          description, transaction_date, transaction_time, payment_method, agent_id, processed_by)
                         VALUES 
                         (:account_id, 'deposit', :amount, :balance_before, :balance_after,
                          :description, CURDATE(), CURTIME(), :payment_method, :agent_id, :processed_by)");
                
                $db->bindMultiple([
                    ':account_id' => $account_id,
                    ':amount' => $amount,
                    ':balance_before' => $balance_before,
                    ':balance_after' => $balance_after,
                    ':description' => $_POST['description'] ?? null,
                    ':payment_method' => $_POST['payment_method'] ?? 'cash',
                    ':agent_id' => $agent_id,
                    ':processed_by' => $_SESSION['user_id']
                ]);
                $db->execute();
                
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
                        <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    
                    <!-- Account Selection (always show) -->
                    <div class="mb-3">
                        <label class="form-label">Select Account *</label>
                        <select name="account_id" class="form-select" required id="accountSelect" onchange="updateAccountInfo()">
                            <option value="">Choose an account...</option>
                            <?php foreach ($all_accounts as $acc): ?>
                                <option value="<?php echo $acc['id']; ?>"
                                        data-balance="<?php echo $acc['balance']; ?>"
                                        data-customer="<?php echo htmlspecialchars($acc['customer_name']); ?>"
                                        <?php echo ($account_id == $acc['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($acc['customer_name'] . ' - ' . $acc['account_number'] . ' (Balance: ' . formatMoney($acc['balance']) . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Account Info (shown when account is selected) -->
                    <div id="accountInfo" class="alert alert-info" style="display: <?php echo $account ? 'block' : 'none'; ?>;">
                        <?php if ($account): ?>
                            <strong>Account:</strong> <?php echo htmlspecialchars($account['account_number']); ?><br>
                            <strong>Customer:</strong> <?php echo htmlspecialchars($account['customer_name']); ?><br>
                            <strong>Current Balance:</strong> <?php echo formatMoney($account['balance']); ?>
                        <?php else: ?>
                            <strong>Account:</strong> <span id="infoAccountNumber">-</span><br>
                            <strong>Customer:</strong> <span id="infoCustomer">-</span><br>
                            <strong>Current Balance:</strong> <span id="infoBalance">-</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount (GHS) *</label>
                        <input type="number" name="amount" class="form-control form-control-lg" 
                               step="0.01" min="0.01" required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Collected By (Agent)</label>
                        <select name="agent_id" class="form-select">
                            <option value="">Select Agent (Optional)</option>
                            <?php foreach ($agents as $agt): ?>
                                <option value="<?php echo $agt['id']; ?>">
                                    <?php echo htmlspecialchars($agt['first_name'] . ' ' . $agt['last_name'] . ' - ' . $agt['phone']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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

<script>
function updateAccountInfo() {
    const select = document.getElementById('accountSelect');
    const option = select.options[select.selectedIndex];
    const infoDiv = document.getElementById('accountInfo');
    
    if (select.value) {
        document.getElementById('infoAccountNumber').textContent = option.text.split(' - ')[1] || '-';
        document.getElementById('infoCustomer').textContent = option.dataset.customer || '-';
        document.getElementById('infoBalance').textContent = 'GHS ' + parseFloat(option.dataset.balance).toFixed(2);
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
}
</script>

<?php include '../../includes/footer.php'; ?>