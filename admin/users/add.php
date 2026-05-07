<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Add New User';
$base_path = '../../';
$breadcrumb = [
    'Admin' => '../dashboard.php',
    'Users' => 'index.php'
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_type = $_POST['user_type'] ?? '';
    
    // Validate
    if (empty($username)) $errors[] = 'Username is required';
    if (empty($full_name)) $errors[] = 'Full name is required';
    if (empty($email)) $errors[] = 'Email is required';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (empty($password)) $errors[] = 'Password is required';
    elseif (strlen($password) < PASSWORD_MIN_LENGTH) $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match';
    if (empty($user_type)) $errors[] = 'User type is required';
    
    // Check if username exists
    if (empty($errors)) {
        $existing = dbSingle("SELECT COUNT(*) as count FROM users WHERE username = :username", [':username' => $username]);
        if ($existing['count'] > 0) $errors[] = 'Username already exists';
    }
    
    // Check if email exists
    if (empty($errors)) {
        $existing = dbSingle("SELECT COUNT(*) as count FROM users WHERE email = :email", [':email' => $email]);
        if ($existing['count'] > 0) $errors[] = 'Email already exists';
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
        
        dbExecute(
            "INSERT INTO users (username, password, full_name, email, phone, user_type) 
             VALUES (:username, :password, :full_name, :email, :phone, :user_type)",
            [
                ':username' => $username,
                ':password' => $hashed_password,
                ':full_name' => $full_name,
                ':email' => $email,
                ':phone' => $phone,
                ':user_type' => $user_type
            ]
        );
        
        $user_id = dbLastInsertId();
        logActivity('User Created', 'users', $user_id);
        
        setFlash('success', 'User created successfully');
        redirect('index.php');
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-plus"></i> Add New User</h5>
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
                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                   placeholder="e.g., 024XXXXXXXX">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <small class="text-muted">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="user_type" class="form-label">User Type *</label>
                        <select class="form-select" id="user_type" name="user_type" required>
                            <option value="">Select User Type</option>
                            <option value="employee" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'employee') ? 'selected' : ''; ?>>
                                Employee
                            </option>
                            <option value="admin" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'admin') ? 'selected' : ''; ?>>
                                Admin
                            </option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>