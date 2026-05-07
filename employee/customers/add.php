<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = 'Add New Customer';
$base_path = '../../';
$breadcrumb = [
    'Dashboard' => '../dashboard.php',
    'Customers' => 'index.php'
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $alternate_phone = trim($_POST['alternate_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $id_type = $_POST['id_type'] ?? '';
    $id_number = trim($_POST['id_number'] ?? '');
    $occupation = trim($_POST['occupation'] ?? '');
    $business_name = trim($_POST['business_name'] ?? '');
    $business_address = trim($_POST['business_address'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $marital_status = $_POST['marital_status'] ?? '';
    
    // Validate
    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name)) $errors[] = 'Last name is required';
    if (empty($phone)) $errors[] = 'Phone number is required';
    
    if (empty($errors)) {
        // Generate customer code
        $result = dbSingle("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM customers");
        $customer_code = 'CUS' . str_pad($result['next_id'], 6, '0', STR_PAD_LEFT);
        
        dbExecute(
            "INSERT INTO customers 
             (customer_code, first_name, last_name, email, phone, alternate_phone,
              address, city, region, id_type, id_number, occupation, business_name,
              business_address, date_of_birth, gender, marital_status, created_by)
             VALUES 
             (:code, :first_name, :last_name, :email, :phone, :alt_phone,
              :address, :city, :region, :id_type, :id_number, :occupation, :business_name,
              :business_address, :date_of_birth, :gender, :marital_status, :created_by)",
            [
                ':code' => $customer_code,
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':email' => $email ?: null,
                ':phone' => $phone,
                ':alt_phone' => $alternate_phone ?: null,
                ':address' => $address ?: null,
                ':city' => $city ?: null,
                ':region' => $region ?: null,
                ':id_type' => $id_type ?: null,
                ':id_number' => $id_number ?: null,
                ':occupation' => $occupation ?: null,
                ':business_name' => $business_name ?: null,
                ':business_address' => $business_address ?: null,
                ':date_of_birth' => $date_of_birth ?: null,
                ':gender' => $gender ?: null,
                ':marital_status' => $marital_status ?: null,
                ':created_by' => $_SESSION['user_id']
            ]
        );
        
        $customer_id = dbLastInsertId();
        logActivity('Customer Created', 'customers', $customer_id);
        
        setFlash('success', 'Customer added successfully! Code: ' . $customer_code);
        redirect('view.php?id=' . $customer_id);
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-plus"></i> Add New Customer</h5>
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
                    
                    <h6 class="text-primary mb-3">Personal Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                                   placeholder="e.g., 024XXXXXXXX" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Alternate Phone</label>
                            <input type="tel" name="alternate_phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['alternate_phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select</option>
                                <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <hr>
                    <h6 class="text-primary mb-3">Location Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Region</label>
                            <input type="text" name="region" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['region'] ?? ''); ?>">
                        </div>
                    </div>

                    <hr>
                    <h6 class="text-primary mb-3">Identification & Occupation</h6>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">ID Type</label>
                            <select name="id_type" class="form-select">
                                <option value="">Select ID Type</option>
                                <option value="Ghana Card">Ghana Card</option>
                                <option value="Voter ID">Voter ID</option>
                                <option value="Passport">Passport</option>
                                <option value="Driver License">Driver License</option>
                                <option value="NHIS">NHIS</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">ID Number</label>
                            <input type="text" name="id_number" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['id_number'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Occupation</label>
                            <input type="text" name="occupation" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['occupation'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Marital Status</label>
                            <select name="marital_status" class="form-select">
                                <option value="">Select</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Divorced">Divorced</option>
                                <option value="Widowed">Widowed</option>
                            </select>
                        </div>
                    </div>

                    <hr>
                    <h6 class="text-primary mb-3">Business Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Business Name</label>
                            <input type="text" name="business_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['business_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Business Address</label>
                            <input type="text" name="business_address" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['business_address'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Customer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>