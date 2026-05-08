<?php
/**
 * Microfinance System
 * Entry Point - Redirects based on user role or shows landing page
 */

require_once 'config/init.php';

// If user is logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] == 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: employee/dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo companyName(); ?> - Microfinance Management System</title>
    
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'><path fill='%23ffd700' d='M8.277.084a.5.5 0 0 0-.554 0l-7.5 5A.5.5 0 0 0 .5 6h1.875v7H1.5a.5.5 0 0 0 0 1h13a.5.5 0 0 0 0-1h-.875V6H15.5a.5.5 0 0 0 .277-.916l-7.5-5z'/></svg>">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            height: 100%;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            min-height: 100dvh; /* Dynamic viewport height for mobile */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto; /* Allow scrolling on mobile */
        }

        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            opacity: 0.1;
            pointer-events: none;
        }

        .bg-animation span {
            position: absolute;
            display: block;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.3);
            animation: animate 25s linear infinite;
            bottom: -150px;
        }

        .bg-animation span:nth-child(1) { left: 25%; width: 80px; height: 80px; animation-delay: 0s; }
        .bg-animation span:nth-child(2) { left: 10%; width: 20px; height: 20px; animation-delay: 2s; animation-duration: 12s; }
        .bg-animation span:nth-child(3) { left: 70%; width: 20px; height: 20px; animation-delay: 4s; }
        .bg-animation span:nth-child(4) { left: 40%; width: 60px; height: 60px; animation-delay: 0s; animation-duration: 18s; }
        .bg-animation span:nth-child(5) { left: 65%; width: 20px; height: 20px; animation-delay: 0s; }
        .bg-animation span:nth-child(6) { left: 75%; width: 110px; height: 110px; animation-delay: 3s; }
        .bg-animation span:nth-child(7) { left: 35%; width: 150px; height: 150px; animation-delay: 7s; }
        .bg-animation span:nth-child(8) { left: 50%; width: 25px; height: 25px; animation-delay: 15s; animation-duration: 45s; }
        .bg-animation span:nth-child(9) { left: 20%; width: 15px; height: 15px; animation-delay: 2s; animation-duration: 35s; }
        .bg-animation span:nth-child(10) { left: 85%; width: 150px; height: 150px; animation-delay: 0s; animation-duration: 11s; }

        @keyframes animate {
            0% { transform: translateY(0) rotate(0deg); opacity: 1; border-radius: 0; }
            100% { transform: translateY(-1000px) rotate(720deg); opacity: 0; border-radius: 50%; }
        }

        /* Main Container */
        .main-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            margin: auto;
        }

        /* Logo Section */
        .logo-section {
            text-align: center;
            margin-bottom: 25px;
            color: white;
        }

        .logo-icon {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 2rem;
            color: #ffd700;
            border: 3px solid rgba(255, 215, 0, 0.3);
            backdrop-filter: blur(10px);
        }

        .logo-section h1 {
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 3px;
            color: white;
            line-height: 1.3;
            word-wrap: break-word;
        }

        .logo-section p {
            opacity: 0.8;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.9);
        }

        /* Login Card */
        .login-card {
            background: white;
            border-radius: 16px;
            padding: 25px 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .login-card h3 {
            color: #333;
            font-weight: 600;
            margin-bottom: 5px;
            text-align: center;
            font-size: 1.2rem;
        }

        .login-card .subtitle {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 20px;
            text-align: center;
        }

        /* Login Buttons */
        .btn-admin-login,
        .btn-employee-login {
            display: block;
            width: 100%;
            padding: 15px;
            background: white;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
            text-align: left;
            margin-bottom: 12px;
        }

        .btn-admin-login {
            border: 2px solid #e74c3c;
        }

        .btn-admin-login:hover,
        .btn-admin-login:active {
            background: #e74c3c;
            border-color: #e74c3c;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(231, 76, 60, 0.3);
        }

        .btn-admin-login:hover *,
        .btn-admin-login:active * {
            color: white !important;
        }

        .btn-employee-login {
            border: 2px solid #667eea;
        }

        .btn-employee-login:hover,
        .btn-employee-login:active {
            background: #667eea;
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-employee-login:hover *,
        .btn-employee-login:active * {
            color: white !important;
        }

        .btn-admin-login strong,
        .btn-employee-login strong {
            color: #333;
            display: block;
            font-size: 0.95rem;
            line-height: 1.3;
        }

        .btn-admin-login small,
        .btn-employee-login small {
            color: #999;
            font-size: 0.75rem;
        }

        .login-icon-admin,
        .login-icon-employee {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .login-icon-admin {
            background: #fdecea;
            color: #e74c3c;
        }

        .btn-admin-login:hover .login-icon-admin,
        .btn-admin-login:active .login-icon-admin {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .login-icon-employee {
            background: #eef1fd;
            color: #667eea;
        }

        .btn-employee-login:hover .login-icon-employee,
        .btn-employee-login:active .login-icon-employee {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        /* Footer */
        .footer-text {
            text-align: center;
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.8rem;
            padding-bottom: 10px;
        }

        .footer-text a {
            color: #ffd700;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-text a:hover {
            color: white;
        }

        /* Mobile Responsive */
        @media (max-width: 480px) {
            body {
                padding: 12px;
                align-items: flex-start;
                padding-top: 30px;
            }

            .main-container {
                max-width: 100%;
            }

            .login-card {
                padding: 20px 15px;
                border-radius: 14px;
            }

            .logo-section {
                margin-bottom: 20px;
            }

            .logo-icon {
                width: 60px;
                height: 60px;
                font-size: 1.6rem;
                border-width: 2px;
            }

            .logo-section h1 {
                font-size: 1.3rem;
            }

            .logo-section p {
                font-size: 0.8rem;
            }

            .login-card h3 {
                font-size: 1.1rem;
            }

            .login-card .subtitle {
                font-size: 0.8rem;
                margin-bottom: 15px;
            }

            .btn-admin-login,
            .btn-employee-login {
                padding: 12px 15px;
            }

            .btn-admin-login strong,
            .btn-employee-login strong {
                font-size: 0.9rem;
            }

            .btn-admin-login small,
            .btn-employee-login small {
                font-size: 0.7rem;
            }

            .login-icon-admin,
            .login-icon-employee {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
                border-radius: 8px;
            }

            .footer-text {
                font-size: 0.75rem;
                margin-top: 15px;
            }
        }

        @media (max-width: 360px) {
            body {
                padding: 10px;
                padding-top: 20px;
            }

            .login-card {
                padding: 18px 12px;
            }

            .logo-icon {
                width: 50px;
                height: 50px;
                font-size: 1.4rem;
            }

            .logo-section h1 {
                font-size: 1.2rem;
            }

            .btn-admin-login,
            .btn-employee-login {
                padding: 10px 12px;
            }

            .login-icon-admin,
            .login-icon-employee {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }

            .btn-admin-login strong,
            .btn-employee-login strong {
                font-size: 0.85rem;
            }

            .btn-admin-login small,
            .btn-employee-login small {
                font-size: 0.65rem;
            }
        }

        /* For very small screens */
        @media (max-height: 600px) {
            body {
                align-items: flex-start;
                padding-top: 15px;
            }

            .logo-section {
                margin-bottom: 15px;
            }

            .logo-icon {
                width: 50px;
                height: 50px;
                font-size: 1.4rem;
            }

            .logo-section h1 {
                font-size: 1.2rem;
            }

            .logo-section p {
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <span></span><span></span><span></span><span></span><span></span>
        <span></span><span></span><span></span><span></span><span></span>
    </div>

    <div class="main-container">
        <!-- Logo Section -->
        <div class="logo-section">
            <div class="logo-icon">
                <i class="bi bi-bank2"></i>
            </div>
            <h1><?php echo companyName(); ?></h1>
            <p>Microfinance Management System</p>
        </div>

        <!-- Login Card -->
        <div class="login-card">
            <h3>Sign In</h3>
            <p class="subtitle">Choose your login portal</p>
            
            <!-- Admin Login Button -->
            <a href="admin/login.php" class="btn-admin-login">
                <div class="d-flex align-items-center">
                    <span class="login-icon-admin">
                        <i class="bi bi-shield-lock-fill"></i>
                    </span>
                    <div class="ms-3">
                        <strong>Administrator Login</strong>
                        <small>Access admin dashboard & settings</small>
                    </div>
                </div>
            </a>
            
            <!-- Employee Login Button -->
            <a href="employee/login.php" class="btn-employee-login">
                <div class="d-flex align-items-center">
                    <span class="login-icon-employee">
                        <i class="bi bi-person-badge"></i>
                    </span>
                    <div class="ms-3">
                        <strong>Employee Login</strong>
                        <small>Access employee portal</small>
                    </div>
                </div>
            </a>
        </div>

        <!-- Footer -->
        <div class="footer-text">
            <p class="mb-0">
                &copy; <?php echo date('Y'); ?> <?php echo companyName(); ?>. All rights reserved.
            </p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>