<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Edit Loan Product';
$base_path = '../../';
$breadcrumb = [
    'Admin' => '../dashboard.php',
    'Products' => 'index.php'
];

$product_id = $_GET['id'] ?? 0;
$errors = [];

// Get product data
$product = dbSingle("SELECT * FROM loan_products WHERE id = :id", [':id' => $product_id]);

if (!$product) {
    setFlash('error', 'Product not found');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $min_amount = $_POST['min_amount'] ?? 0;
    $max_amount = $_POST['max_amount'] ?? 0;
    $interest_rate = $_POST['interest_rate'] ?? 0;
    $interest_type = $_POST['interest_type'] ?? 'reducing_balance';
    $duration_min = $_POST['duration_min_months'] ?? 0;
    $duration_max = $_POST['duration_max_months'] ?? 0;
    $processing_fee_percent = $_POST['processing_fee_percentage'] ?? 0;
    $processing_fee_fixed = $_POST['processing_fee_fixed'] ?? 0;
    $late_fee_percent = $_POST['late_fee_percentage'] ?? 0;
    $late_fee_fixed = $_POST['late_fee_fixed'] ?? 0;
    $collateral_required = isset($_POST['collateral_required']) ? 1 : 0;
    $guarantor_required = isset($_POST['guarantor_required']) ? 1 : 0;
    $min_savings = $_POST['min_savings_balance'] ?? 0;
    $status = $_POST['status'] ?? 'active';
    
    // Validate
    if (empty($product_name)) $errors[] = 'Product name is required';
    if (empty($min_amount) || !is_numeric($min_amount) || $min_amount <= 0) $errors[] = 'Valid minimum amount is required';
    if (empty($max_amount) || !is_numeric($max_amount) || $max_amount <= 0) $errors[] = 'Valid maximum amount is required';
    if ($max_amount <= $min_amount) $errors[] = 'Maximum amount must be greater than minimum amount';
    if (empty($interest_rate) || !is_numeric($interest_rate) || $interest_rate <= 0) $errors[] = 'Valid interest rate is required';
    
    if (empty($errors)) {
        dbExecute(
            "UPDATE loan_products SET 
             product_name = :product_name,
             description = :description,
             min_amount = :min_amount,
             max_amount = :max_amount,
             interest_rate = :interest_rate,
             interest_type = :interest_type,
             duration_min_months = :duration_min,
             duration_max_months = :duration_max,
             processing_fee_percentage = :processing_fee_percent,
             processing_fee_fixed = :processing_fee_fixed,
             late_fee_percentage = :late_fee_percent,
             late_fee_fixed = :late_fee_fixed,
             collateral_required = :collateral_required,
             guarantor_required = :guarantor_required,
             min_savings_balance = :min_savings,
             status = :status
             WHERE id = :id",
            [
                ':product_name' => $product_name,
                ':description' => $description ?: null,
                ':min_amount' => $min_amount,
                ':max_amount' => $max_amount,
                ':interest_rate' => $interest_rate,
                ':interest_type' => $interest_type,
                ':duration_min' => $duration_min,
                ':duration_max' => $duration_max,
                ':processing_fee_percent' => $processing_fee_percent,
                ':processing_fee_fixed' => $processing_fee_fixed,
                ':late_fee_percent' => $late_fee_percent,
                ':late_fee_fixed' => $late_fee_fixed,
                ':collateral_required' => $collateral_required,
                ':guarantor_required' => $guarantor_required,
                ':min_savings' => $min_savings,
                ':status' => $status,
                ':id' => $product_id
            ]
        );
        
        logActivity('Loan Product Updated', 'products', $product_id);
        
        setFlash('success', 'Loan product updated successfully');
        redirect('index.php');
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit Loan Product: <?php echo htmlspecialchars($product['product_name']); ?></h5>
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
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product Name *</label>
                            <input type="text" name="product_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['product_name'] ?? $product['product_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product Code</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($product['product_code']); ?>" disabled>
                            <small class="text-muted">Product code cannot be changed</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? $product['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Minimum Amount (GHS) *</label>
                            <input type="number" name="min_amount" class="form-control" step="0.01" 
                                   value="<?php echo htmlspecialchars($_POST['min_amount'] ?? $product['min_amount']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Maximum Amount (GHS) *</label>
                            <input type="number" name="max_amount" class="form-control" step="0.01" 
                                   value="<?php echo htmlspecialchars($_POST['max_amount'] ?? $product['max_amount']); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Interest Rate (%) *</label>
                            <input type="number" name="interest_rate" class="form-control" step="0.01" 
                                   value="<?php echo htmlspecialchars($_POST['interest_rate'] ?? $product['interest_rate']); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Interest Type *</label>
                            <select name="interest_type" class="form-select" required>
                                <option value="reducing_balance" <?php echo (isset($_POST['interest_type']) ? $_POST['interest_type'] : $product['interest_type']) == 'reducing_balance' ? 'selected' : ''; ?>>Reducing Balance</option>
                                <option value="flat" <?php echo (isset($_POST['interest_type']) ? $_POST['interest_type'] : $product['interest_type']) == 'flat' ? 'selected' : ''; ?>>Flat Rate</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?php echo (isset($_POST['status']) ? $_POST['status'] : $product['status']) == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (isset($_POST['status']) ? $_POST['status'] : $product['status']) == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Min Duration (Months) *</label>
                            <input type="number" name="duration_min_months" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['duration_min_months'] ?? $product['duration_min_months']); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Max Duration (Months) *</label>
                            <input type="number" name="duration_max_months" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['duration_max_months'] ?? $product['duration_max_months']); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Late Fee (%)</label>
                            <input type="number" name="late_fee_percentage" class="form-control" step="0.01" 
                                   value="<?php echo htmlspecialchars($_POST['late_fee_percentage'] ?? $product['late_fee_percentage']); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fixed Processing Fee (GHS)</label>
                            <input type="number" name="processing_fee_fixed" class="form-control" step="0.01" 
                                   value="<?php echo htmlspecialchars($_POST['processing_fee_fixed'] ?? $product['processing_fee_fixed']); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fixed Late Fee (GHS)</label>
                            <input type="number" name="late_fee_fixed" class="form-control" step="0.01" 
                                   value="<?php echo htmlspecialchars($_POST['late_fee_fixed'] ?? $product['late_fee_fixed']); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Min Savings Balance (GHS)</label>
                            <input type="number" name="min_savings_balance" class="form-control" step="0.01" 
                                   value="<?php echo htmlspecialchars($_POST['min_savings_balance'] ?? $product['min_savings_balance']); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="guarantor_required" class="form-check-input" id="guarantor" 
                                       <?php echo (isset($_POST['guarantor_required']) ? $_POST['guarantor_required'] : $product['guarantor_required']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="guarantor">Guarantor Required</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="collateral_required" class="form-check-input" id="collateral"
                                       <?php echo (isset($_POST['collateral_required']) ? $_POST['collateral_required'] : $product['collateral_required']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="collateral">Collateral Required</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php' ?>