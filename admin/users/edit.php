<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Edit User';
$base_path = '../../';
$breadcrumb = [
    'Admin' => '../dashboard.php',
    'Users' => 'index.php'
];

$errors = [];
$user_id = $_GET['id'] ?? 0;

// Get user data
$user_data = dbSingle("SELECT * FROM users WHERE id = :id", [':id' => $user_id]);

if (!$user_data) {
    setFlash('error', 'User not found');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_type = $_POST['user_type'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate
    if (empty($full_name)) $errors[] = 'Full name is required';
    if (empty($email)) $errors[] = 'Email is required';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    
    // Check if email exists (excluding current user)
    if (empty($errors) && $email !== $user_data['email']) {
        $existing = dbSingle(
            "SELECT COUNT(*) as count FROM users WHERE email = :email AND id != :id", 
            [':email' => $email, ':id' => $user_id]
        );
        if ($existing['count'] > 0) $errors[] = 'Email already in use';
    }
    
    // Validate password if provided
    if (!empty($password)) {
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
        }
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }
    }
    
    if (empty($errors)) {
        // Build update query
        $updateQuery = "UPDATE users SET full_name = :full_name, email = :email, phone = :phone, 
                        user_type = :user_type, is_active = :is_active";
        $updateParams = [
            ':full_name' => $full_name,
            ':email' => $email,
            ':phone' => $phone,
            ':user_type' => $user_type,
            ':is_active' => $is_active,
            ':id' => $user_id
        ];
        
        // Add password if provided
        if (!empty($password)) {
            $updateQuery .= ", password = :password";
            $updateParams[':password'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
        }
        
        $updateQuery .= " WHERE id = :id";
        
        dbExecute($updateQuery, $updateParams);
        
        logActivity('User Updated', 'users', $user_id);
        
        setFlash('success', 'User updated successfully');
        redirect('index.php');
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit User: <?php echo htmlspecialchars($user_data['full_name']); ?></h5>
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
                    <!-- CSRF Token -->
                    <?php echo csrfField(); ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? $user_data['full_name']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['username']); ?>" disabled>
                            <small class="text-muted">Username cannot be changed</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? $user_data['email']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? $user_data['phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <small class="text-muted">Leave blank to keep current password</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="user_type" class="form-label">User Type *</label>
                            <select class="form-select" id="user_type" name="user_type" required>
                                <option value="employee" <?php echo (isset($_POST['user_type']) ? $_POST['user_type'] : $user_data['user_type']) == 'employee' ? 'selected' : ''; ?>>
                                    Employee
                                </option>
                                <option value="admin" <?php echo (isset($_POST['user_type']) ? $_POST['user_type'] : $user_data['user_type']) == 'admin' ? 'selected' : ''; ?>>
                                    Admin
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                       <?php echo (isset($_POST['is_active']) ? $_POST['is_active'] : ($user_data['is_active'] ?? 1)) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    Active Account
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>