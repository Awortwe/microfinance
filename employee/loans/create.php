<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = 'New Loan Application';
$base_path = '../../';
$breadcrumb = [
    'Dashboard' => '../dashboard.php',
    'Loans' => 'index.php'
];

$errors = [];

// Get loan products
$products = dbQuery("SELECT * FROM loan_products WHERE status = 'active' ORDER BY product_name");

// Get customers
$customers = dbQuery("SELECT id, customer_code, first_name, last_name, phone FROM customers WHERE status = 'active' ORDER BY first_name");

$customer_id = $_GET['customer_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_POST['customer_id'] ?? 0;
    $product_id = $_POST['product_id'] ?? 0;
    $principal_amount = $_POST['principal_amount'] ?? 0;
    $duration_months = $_POST['duration_months'] ?? 0;
    
    if (empty($customer_id)) $errors[] = 'Customer is required';
    if (empty($product_id)) $errors[] = 'Loan product is required';
    if (empty($principal_amount) || $principal_amount <= 0) $errors[] = 'Valid amount is required';
    if (empty($duration_months) || $duration_months <= 0) $errors[] = 'Duration is required';
    
    if (empty($errors)) {
        // Get product details
        $product = dbSingle("SELECT * FROM loan_products WHERE id = :id", [':id' => $product_id]);
        
        // Calculate interest
        $principal = $principal_amount;
        $rate = $product['interest_rate'];
        $months = $duration_months;
        $type = $product['interest_type'];
        
        if ($type == 'flat') {
            $total_interest = $principal * ($rate / 100) * ($months / 12);
        } else {
            $monthly_principal = $principal / $months;
            $balance = $principal;
            $total_interest = 0;
            for ($i = 1; $i <= $months; $i++) {
                $monthly_interest = ($balance * ($rate / 100)) / 12;
                $total_interest += $monthly_interest;
                $balance -= $monthly_principal;
            }
        }
        
        $processing_fee = $principal * ($product['processing_fee_percentage'] / 100);
        $total_amount = $principal + $total_interest + $processing_fee;
        $monthly_payment = $total_amount / $months;
        
        // Generate loan number
        $result = dbSingle("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM loans");
        $loan_number = 'LN' . str_pad($result['next_id'], 8, '0', STR_PAD_LEFT);
        
        // Insert loan
        dbExecute(
            "INSERT INTO loans 
             (customer_id, product_id, loan_number, principal_amount, interest_rate,
              interest_type, total_interest, processing_fee, total_amount,
              monthly_payment, duration_months, balance, application_date, created_by)
             VALUES 
             (:customer_id, :product_id, :loan_number, :principal, :rate,
              :interest_type, :total_interest, :fee, :total_amount,
              :monthly_payment, :months, :balance, CURDATE(), :created_by)",
            [
                ':customer_id' => $customer_id,
                ':product_id' => $product_id,
                ':loan_number' => $loan_number,
                ':principal' => $principal,
                ':rate' => $rate,
                ':interest_type' => $type,
                ':total_interest' => round($total_interest, 2),
                ':fee' => $processing_fee,
                ':total_amount' => round($total_amount, 2),
                ':monthly_payment' => round($monthly_payment, 2),
                ':months' => $months,
                ':balance' => round($total_amount, 2),
                ':created_by' => $_SESSION['user_id']
            ]
        );
        
        $loan_id = dbLastInsertId();
        logActivity('Loan Application Created', 'loans', $loan_id);
        
        setFlash('success', 'Loan application submitted! Loan #: ' . $loan_number);
        redirect('schedule.php?loan_id=' . $loan_id);
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-file-plus"></i> New Loan Application</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Customer *</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">Select Customer</option>
                            <?php foreach ($customers as $cust): ?>
                                <option value="<?php echo $cust['id']; ?>" <?php echo (isset($_POST['customer_id']) ? $_POST['customer_id'] : $customer_id) == $cust['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name'] . ' - ' . $cust['phone']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Loan Product *</label>
                        <select name="product_id" class="form-select" required id="productSelect">
                            <option value="">Select Product</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>"
                                    data-rate="<?php echo $product['interest_rate']; ?>"
                                    data-min="<?php echo $product['min_amount']; ?>"
                                    data-max="<?php echo $product['max_amount']; ?>"
                                    data-duration-min="<?php echo $product['duration_min_months']; ?>"
                                    data-duration-max="<?php echo $product['duration_max_months']; ?>">
                                    <?php echo $product['product_name']; ?> - 
                                    <?php echo $product['interest_rate']; ?>% | 
                                    <?php echo formatMoney($product['min_amount']); ?> - <?php echo formatMoney($product['max_amount']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Loan Amount (GHS) *</label>
                            <input type="number" name="principal_amount" class="form-control" 
                                   step="0.01" required id="amountInput">
                            <small class="text-muted" id="amountRange"></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration (Months) *</label>
                            <input type="number" name="duration_months" class="form-control" 
                                   min="1" required id="durationInput">
                            <small class="text-muted" id="durationRange"></small>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Submit Application
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('productSelect').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const min = option.dataset.min;
    const max = option.dataset.max;
    const durMin = option.dataset.durationMin;
    const durMax = option.dataset.durationMax;
    
    document.getElementById('amountInput').min = min;
    document.getElementById('amountInput').max = max;
    document.getElementById('amountRange').textContent = `Amount range: ${min} - ${max} GHS`;
    
    document.getElementById('durationInput').min = durMin;
    document.getElementById('durationInput').max = durMax;
    document.getElementById('durationRange').textContent = `Duration: ${durMin} - ${durMax} months`;
});
</script>

<?php include '../../includes/footer.php'; ?>