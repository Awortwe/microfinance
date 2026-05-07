<?php
require_once 'config/init.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | <?php echo companyName(); ?></title>
    
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'><path fill='%23ffd700' d='M8.277.084a.5.5 0 0 0-.554 0l-7.5 5A.5.5 0 0 0 .5 6h1.875v7H1.5a.5.5 0 0 0 0 1h13a.5.5 0 0 0 0-1h-.875V6H15.5a.5.5 0 0 0 .277-.916l-7.5-5z'/></svg>">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-container { text-align: center; color: white; max-width: 600px; }
        .error-code {
            font-size: 8rem; font-weight: 700; line-height: 1;
            text-shadow: 3px 3px 0 rgba(0,0,0,0.2);
            margin-bottom: 10px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        .error-icon { font-size: 4rem; margin-bottom: 20px; color: #ffd700; }
        .error-title { font-size: 2rem; font-weight: 600; margin-bottom: 15px; }
        .error-message { font-size: 1.1rem; opacity: 0.9; margin-bottom: 30px; line-height: 1.6; }
        .error-actions { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn-back {
            background: #ffd700; color: #333; padding: 12px 30px;
            border-radius: 50px; font-weight: 600; text-decoration: none;
            transition: all 0.3s ease; border: none;
        }
        .btn-back:hover { background: #ffed4a; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.2); color: #333; }
        .btn-home {
            background: rgba(255,255,255,0.2); color: white; padding: 12px 30px;
            border-radius: 50px; font-weight: 600; text-decoration: none;
            transition: all 0.3s ease; border: 2px solid rgba(255,255,255,0.3);
        }
        .btn-home:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); color: white; }
        .error-footer { margin-top: 40px; opacity: 0.7; font-size: 0.9rem; }
        .error-footer a { color: #ffd700; text-decoration: none; }
        @media (max-width: 576px) { .error-code { font-size: 5rem; } .error-title { font-size: 1.5rem; } .error-actions { flex-direction: column; align-items: center; } }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon"><i class="bi bi-compass"></i></div>
        <div class="error-code">404</div>
        <h1 class="error-title">Page Not Found</h1>
        <p class="error-message">
            Oops! The page you're looking for doesn't exist or has been moved. 
            It might have been removed, renamed, or is temporarily unavailable.
        </p>
        <div class="error-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php $dashboard = $_SESSION['user_type'] == 'admin' ? 'admin/dashboard.php' : 'employee/dashboard.php'; ?>
                <a href="<?php echo $dashboard; ?>" class="btn-back">
                    <i class="bi bi-speedometer2"></i> Go to Dashboard
                </a>
            <?php else: ?>
                <a href="index.php" class="btn-back">
                    <i class="bi bi-house"></i> Back to Home
                </a>
            <?php endif; ?>
            <a href="index.php" class="btn-home">
                <i class="bi bi-house"></i> Home Page
            </a>
        </div>
        <div class="error-footer">
            <p class="mb-0">
                &copy; <?php echo date('Y'); ?> <?php echo companyName(); ?>. All rights reserved. | 
                <a href="index.php#contact">Contact Support</a>
            </p>
        </div>
    </div>
</body>
</html>