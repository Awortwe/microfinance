<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Add New Agent';
$base_path = '../../';
$breadcrumb = ['Admin' => '../dashboard.php', 'Agents' => 'index.php'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $gender = $_POST['gender'] ?? '';
    
    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name)) $errors[] = 'Last name is required';
    if (empty($phone)) $errors[] = 'Phone number is required';
    
    if (empty($errors)) {
        $agent_code = generateAgentCode();
        
        dbExecute(
            "INSERT INTO agents (agent_code, first_name, last_name, email, phone, address, city, gender, created_by) 
             VALUES (:code, :first_name, :last_name, :email, :phone, :address, :city, :gender, :created_by)",
            [
                ':code' => $agent_code,
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':email' => $email ?: null,
                ':phone' => $phone,
                ':address' => $address ?: null,
                ':city' => $city ?: null,
                ':gender' => $gender ?: null,
                ':created_by' => $_SESSION['user_id']
            ]
        );
        
        logActivity('Agent Created', 'agents', dbLastInsertId());
        setFlash('success', 'Agent created successfully! Code: ' . $agent_code);
        redirect('index.php');
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-plus"></i> Add New Agent</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number *</label>
                            <input type="text" name="phone" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Agent
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>