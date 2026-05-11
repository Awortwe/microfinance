<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Edit Customer';
$base_path = '../../';
$breadcrumb = [
    'Admin' => '../dashboard.php',
    'Customers' => 'index.php'
];

$customer_id = $_GET['id'] ?? 0;
$errors = [];

$customer = dbSingle("SELECT * FROM customers WHERE id = :id", [':id' => $customer_id]);

if (!$customer) {
    setFlash('error', 'Customer not found');
    redirect('index.php');
}

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
    $status = $_POST['status'] ?? 'active';
    $agent_id = $_POST['agent_id'] ?? null;
    
    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name)) $errors[] = 'Last name is required';
    if (empty($phone)) $errors[] = 'Phone number is required';
    
    if (empty($errors)) {
        dbExecute(
            "UPDATE customers SET 
             first_name = :first_name, last_name = :last_name,
             email = :email, phone = :phone, alternate_phone = :alt_phone,
             address = :address, city = :city, region = :region,
             id_type = :id_type, id_number = :id_number,
             occupation = :occupation, business_name = :business_name,
             business_address = :business_address, date_of_birth = :dob,
             gender = :gender, marital_status = :marital_status,
             status = :status, agent_id = :agent_id
             WHERE id = :id",
            [
                ':first_name' => $first_name, ':last_name' => $last_name,
                ':email' => $email ?: null, ':phone' => $phone,
                ':alt_phone' => $alternate_phone ?: null,
                ':address' => $address ?: null, ':city' => $city ?: null, ':region' => $region ?: null,
                ':id_type' => $id_type ?: null, ':id_number' => $id_number ?: null,
                ':occupation' => $occupation ?: null,
                ':business_name' => $business_name ?: null,
                ':business_address' => $business_address ?: null,
                ':dob' => $date_of_birth ?: null,
                ':gender' => $gender ?: null, ':marital_status' => $marital_status ?: null,
                ':status' => $status, ':agent_id' => $agent_id ?: null,
                ':id' => $customer_id
            ]
        );
        
        logActivity('Customer Updated', 'customers', $customer_id);
        setFlash('success', 'Customer updated successfully');
        redirect('view.php?id=' . $customer_id);
    }
}

$agents = getActiveAgents();

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit Customer: 
                    <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                    <small class="text-muted ms-2">(<?php echo $customer['customer_code']; ?>)</small>
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    
                    <h6 class="text-primary mb-3">Personal Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? $customer['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? $customer['last_name']); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? $customer['phone']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Alternate Phone</label>
                            <input type="tel" name="alternate_phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['alternate_phone'] ?? $customer['alternate_phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? $customer['email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" 
                                   value="<?php echo $_POST['date_of_birth'] ?? $customer['date_of_birth'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select</option>
                                <option value="Male" <?php echo (isset($_POST['gender']) ? $_POST['gender'] : ($customer['gender'] ?? '')) == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (isset($_POST['gender']) ? $_POST['gender'] : ($customer['gender'] ?? '')) == 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo (isset($_POST['gender']) ? $_POST['gender'] : ($customer['gender'] ?? '')) == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Agent Assignment -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Assigned Agent (Susu Collector)</label>
                            <select name="agent_id" class="form-select">
                                <option value="">Select Agent</option>
                                <?php foreach ($agents as $agt): ?>
                                    <option value="<?php echo $agt['id']; ?>" 
                                        <?php echo (isset($_POST['agent_id']) ? $_POST['agent_id'] : ($customer['agent_id'] ?? '')) == $agt['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($agt['first_name'] . ' ' . $agt['last_name'] . ' - ' . $agt['phone']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Marital Status</label>
                            <select name="marital_status" class="form-select">
                                <option value="">Select</option>
                                <?php 
                                $statuses = ['Single', 'Married', 'Divorced', 'Widowed'];
                                $current_status = $_POST['marital_status'] ?? $customer['marital_status'] ?? '';
                                foreach ($statuses as $st): 
                                ?>
                                    <option value="<?php echo $st; ?>" <?php echo $current_status == $st ? 'selected' : ''; ?>>
                                        <?php echo $st; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <hr>
                    <h6 class="text-primary mb-3">Location Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($_POST['address'] ?? $customer['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['city'] ?? $customer['city'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Region</label>
                            <input type="text" name="region" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['region'] ?? $customer['region'] ?? ''); ?>">
                        </div>
                    </div>

                    <hr>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="text-primary">Business Information</h6>
                            <div class="mb-3">
                                <label class="form-label">Business Name</label>
                                <input type="text" name="business_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['business_name'] ?? $customer['business_name'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="form-label">Business Address</label>
                                <input type="text" name="business_address" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['business_address'] ?? $customer['business_address'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-primary">Status</h6>
                            <div class="mb-3">
                                <label class="form-label">Customer Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?php echo (isset($_POST['status']) ? $_POST['status'] : $customer['status']) == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo (isset($_POST['status']) ? $_POST['status'] : $customer['status']) == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="blacklisted" <?php echo (isset($_POST['status']) ? $_POST['status'] : $customer['status']) == 'blacklisted' ? 'selected' : ''; ?>>Blacklisted</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ID Type</label>
                                <select name="id_type" class="form-select">
                                    <option value="">Select</option>
                                    <?php 
                                    $id_types = ['Ghana Card', 'Voter ID', 'Passport', 'Driver License', 'NHIS'];
                                    $current_id = $_POST['id_type'] ?? $customer['id_type'] ?? '';
                                    foreach ($id_types as $type): 
                                    ?>
                                        <option value="<?php echo $type; ?>" <?php echo $current_id == $type ? 'selected' : ''; ?>>
                                            <?php echo $type; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">ID Number</label>
                                <input type="text" name="id_number" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['id_number'] ?? $customer['id_number'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="view.php?id=<?php echo $customer_id; ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Customer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>