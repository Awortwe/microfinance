<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = 'Create Savings Account';
$base_path = '../../';
$breadcrumb = [
    'Dashboard' => '../dashboard.php',
    'Savings' => 'index.php'
];

$errors = [];

// Get customer ID from URL if provided
$customer_id = $_GET['customer_id'] ?? 0;
$customer = null;

if ($customer_id) {
    $customer = dbSingle("SELECT * FROM customers WHERE id = :id", [':id' => $customer_id]);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_POST['customer_id'] ?? 0;
    $account_type = $_POST['account_type'] ?? '';
    $interest_rate = $_POST['interest_rate'] ?? 0;
    $susu_amount = $_POST['susu_amount'] ?? 0;
    $susu_collection_day = $_POST['susu_collection_day'] ?? 'daily';
    $opened_date = $_POST['opened_date'] ?? date('Y-m-d');
    
    if (empty($customer_id)) $errors[] = 'Customer is required';
    if (empty($account_type)) $errors[] = 'Account type is required';
    
    if (empty($errors)) {
        // Generate account number
        $result = dbSingle("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM savings_accounts");
        $account_number = 'SAV' . str_pad($result['next_id'], 8, '0', STR_PAD_LEFT);
        
        dbExecute(
            "INSERT INTO savings_accounts 
             (customer_id, account_number, account_type, interest_rate, 
              susu_amount, susu_collection_day, opened_date, created_by)
             VALUES 
             (:customer_id, :account_number, :account_type, :interest_rate,
              :susu_amount, :susu_collection_day, :opened_date, :created_by)",
            [
                ':customer_id' => $customer_id,
                ':account_number' => $account_number,
                ':account_type' => $account_type,
                ':interest_rate' => $interest_rate,
                ':susu_amount' => $susu_amount,
                ':susu_collection_day' => $susu_collection_day,
                ':opened_date' => $opened_date,
                ':created_by' => $_SESSION['user_id']
            ]
        );
        
        $account_id = dbLastInsertId();
        logActivity('Savings Account Created', 'savings', $account_id);
        
        setFlash('success', 'Savings account created successfully! Account #: ' . $account_number);
        redirect('statement.php?account_id=' . $account_id);
    }
}

// Get customers for dropdown
$customers = dbQuery("SELECT id, customer_code, first_name, last_name, phone FROM customers WHERE status = 'active' ORDER BY first_name");

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-piggy-bank"></i> Create Savings Account</h5>
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
                    
                    <div class="mb-3">
                        <label class="form-label">Customer *</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">Select Customer</option>
                            <?php foreach ($customers as $cust): ?>
                                <option value="<?php echo $cust['id']; ?>" 
                                    <?php echo (isset($_POST['customer_id']) ? $_POST['customer_id'] : $customer_id) == $cust['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name'] . ' - ' . $cust['phone']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Account Type *</label>
                        <select name="account_type" class="form-select" id="accountType" required>
                            <option value="regular">Regular Savings</option>
                            <option value="susu" <?php echo (isset($_POST['account_type']) && $_POST['account_type'] == 'susu') ? 'selected' : ''; ?>>Susu (Daily Collection)</option>
                            <option value="fixed_deposit" <?php echo (isset($_POST['account_type']) && $_POST['account_type'] == 'fixed_deposit') ? 'selected' : ''; ?>>Fixed Deposit</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Interest Rate (%)</label>
                        <input type="number" name="interest_rate" class="form-control" step="0.01" 
                               value="<?php echo htmlspecialchars($_POST['interest_rate'] ?? '0'); ?>">
                    </div>

                    <div class="mb-3" id="susuFields" style="display: <?php echo (isset($_POST['account_type']) && $_POST['account_type'] == 'susu') ? 'block' : 'none'; ?>">
                        <label class="form-label">Daily Susu Amount (GHS)</label>
                        <input type="number" name="susu_amount" class="form-control" step="0.01" 
                               value="<?php echo htmlspecialchars($_POST['susu_amount'] ?? DEFAULT_SUSU_AMOUNT); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Account Opening Date</label>
                        <input type="date" name="opened_date" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['opened_date'] ?? date('Y-m-d')); ?>">
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Create Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('accountType').addEventListener('change', function() {
    document.getElementById('susuFields').style.display = this.value === 'susu' ? 'block' : 'none';
});
</script>

<?php include '../../includes/footer.php'; ?>