<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Edit Agent';
$base_path = '../../';
$breadcrumb = ['Admin' => '../dashboard.php', 'Agents' => 'index.php'];

$agent_id = $_GET['id'] ?? 0;
$errors = [];

$agent = dbSingle("SELECT * FROM agents WHERE id = :id", [':id' => $agent_id]);

if (!$agent) {
    setFlash('error', 'Agent not found');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $status = $_POST['status'] ?? 'active';
    
    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name)) $errors[] = 'Last name is required';
    if (empty($phone)) $errors[] = 'Phone number is required';
    
    if (empty($errors)) {
        dbExecute(
            "UPDATE agents SET first_name = :first_name, last_name = :last_name, email = :email, 
             phone = :phone, address = :address, city = :city, gender = :gender, status = :status 
             WHERE id = :id",
            [
                ':first_name' => $first_name, ':last_name' => $last_name,
                ':email' => $email ?: null, ':phone' => $phone,
                ':address' => $address ?: null, ':city' => $city ?: null,
                ':gender' => $gender ?: null, ':status' => $status,
                ':id' => $agent_id
            ]
        );
        
        logActivity('Agent Updated', 'agents', $agent_id);
        setFlash('success', 'Agent updated successfully');
        redirect('index.php');
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit Agent: <?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?></h5>
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
                            <input type="text" name="first_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($agent['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($agent['last_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number *</label>
                            <input type="text" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($agent['phone']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($agent['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($agent['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" 
                                   value="<?php echo htmlspecialchars($agent['city'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select</option>
                                <option value="Male" <?php echo ($agent['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($agent['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?php echo $agent['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $agent['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Agent
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>