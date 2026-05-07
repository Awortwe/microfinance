<?php
require_once '../config/init.php';

// If already logged in as admin, redirect to dashboard
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'admin') {
    header("Location: dashboard.php");
    exit();
}

// If logged in as employee, redirect to employee dashboard
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'employee') {
    header("Location: ../employee/dashboard.php");
    exit();
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $db = getDB();

        // Check login attempts
        $db->query("SELECT COUNT(*) as attempts FROM audit_trail 
                  WHERE action = 'login_failed' 
                  AND ip_address = :ip 
                  AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $db->bind(':ip', getClientIP());
        $attempts = $db->single()['attempts'];

        if ($attempts >= 5) {
            $error = 'Too many login attempts. Please try again in 15 minutes.';
        } else {
            // Get user - Must be admin
            $db->query("SELECT id, username, password, full_name, user_type, is_active 
                      FROM users WHERE username = :username AND user_type = 'admin' LIMIT 1");
            $db->bind(':username', $username);
            $user = $db->single();

            if ($user) {
                if (!$user['is_active']) {
                    $error = 'Your account has been deactivated. Please contact the administrator.';
                    logActivity('Login Failed - Inactive Account', 'auth', $user['id']);
                } elseif (password_verify($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['user_type'] = $user['user_type'];

                    // Update last login
                    $db->query("UPDATE users SET last_login = NOW() WHERE id = :id");
                    $db->bind(':id', $user['id']);
                    $db->execute();

                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
                        $db->query("UPDATE users SET remember_token = :token WHERE id = :id");
                        $db->bind(':token', $token);
                        $db->bind(':id', $user['id']);
                        $db->execute();
                    }

                    logActivity('Admin Login Successful', 'auth', $user['id']);
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = 'Invalid username or password';
                    logActivity('Login Failed - Wrong Password', 'auth', $user['id']);
                }
            } else {
                $error = 'Invalid admin credentials. This login is for administrators only.';
                logActivity('Login Failed - Unknown Admin', 'auth', null);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo APP_NAME; ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'><path fill='%23ffd700' d='M8.277.084a.5.5 0 0 0-.554 0l-7.5 5A.5.5 0 0 0 .5 6h1.875v7H1.5a.5.5 0 0 0 0 1h13a.5.5 0 0 0 0-1h-.875V6H15.5a.5.5 0 0 0 .277-.916l-7.5-5z'/></svg>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 2rem;
        }

        .login-header h3 {
            color: #333;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }

        .login-header .badge-admin {
            background: #e74c3c;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.75rem;
            display: inline-block;
            margin-top: 5px;
        }

        .form-label {
            font-weight: 500;
            color: #555;
            font-size: 0.9rem;
        }

        .input-group-text {
            background: #f8f9fa;
            border-right: none;
            color: #666;
        }

        .form-control {
            border-left: none;
            padding: 12px 15px;
            border-color: #e0e0e0;
        }

        .form-control:focus {
            border-color: #e74c3c;
            box-shadow: none;
        }

        .input-group:focus-within {
            box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.25);
            border-radius: 10px;
        }

        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control {
            border-color: #e74c3c;
        }

        .btn-login {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            border: none;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(231, 76, 60, 0.4);
            color: white;
        }

        /* Back Button */
        .back-btn {
            display: block;
            width: 100%;
            padding: 12px;
            margin-top: 15px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            color: #666;
            text-decoration: none;
            text-align: center;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #f8f9fa;
            border-color: #e74c3c;
            color: #e74c3c;
            transform: translateY(-2px);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: white;
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="icon"><i class="bi bi-shield-lock-fill"></i></div>
                <h3>Admin Login</h3>
                <p><?php echo companyName(); ?></p>
                <span class="badge-admin">Administrator Access Only</span>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="off">
                <div class="mb-3">
                    <label for="username" class="form-label">Admin Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                        <input type="text" class="form-control" id="username" name="username"
                            placeholder="Enter admin username"
                            value="<?php echo htmlspecialchars($username); ?>" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                            placeholder="Enter password" required>
                        <span class="input-group-text" style="cursor: pointer;" onclick="togglePassword()">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label" for="remember" style="font-size: 0.85rem; color: #666;">Remember me</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> Sign In as Admin
                </button>
            </form>

            <!-- Back to Home Button -->
            <a href="../index.php" class="back-btn">
                <i class="bi bi-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>

</html>