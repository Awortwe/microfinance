<?php
/**
 * Application Initialization File
 * This file is included at the top of every page
 * Handles session, autoloading, and basic setup
 */

// Start output buffering
ob_start();

// Define application running constant
define('APP_RUNNING', true);

// ============================================
// INCLUDE CONFIGURATION FILES
// ============================================

// Include database configuration
require_once __DIR__ . '/database.php';

// Include application configuration
require_once __DIR__ . '/config.php';

// ============================================
// SESSION HANDLING
// ============================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Configure session before starting
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 100);
    
    session_name('MFI_SESSION');
    session_start();
}

// ============================================
// REGENERATE SESSION ID PERIODICALLY
// ============================================

// Regenerate session ID every 30 minutes
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// ============================================
// AUTOLOAD CLASSES
// ============================================

// Autoload function for classes
spl_autoload_register(function ($class_name) {
    $class_file = __DIR__ . '/../classes/' . $class_name . '.php';
    
    if (file_exists($class_file)) {
        require_once $class_file;
    }
});

// ============================================
// AUTO-INCLUDE HELPER FUNCTIONS
// ============================================

// Include common functions
$functions_file = __DIR__ . '/../includes/functions.php';
if (file_exists($functions_file)) {
    require_once $functions_file;
}

// ============================================
// DATABASE CONNECTION
// ============================================

/**
 * Get database connection instance
 * Returns a Database object
 * @return Database
 */
function getDB() {
    static $db = null;
    
    if ($db === null) {
        $db = new Database();
    }
    
    return $db;
}

// ============================================
// CSRF PROTECTION
// ============================================

if (CSRF_PROTECTION) {
    // Generate CSRF token if not exists
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    // Validate CSRF token on POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Skip CSRF check for login page
        $current_page = basename($_SERVER['PHP_SELF']);
        $skip_csrf_pages = ['login.php', 'logout.php'];
        
        if (!in_array($current_page, $skip_csrf_pages)) {
            if (!isset($_POST['csrf_token']) || 
                !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                
                // Log CSRF attempt
                if (LOG_ERRORS) {
                    error_log("CSRF token validation failed from IP: " . getClientIP());
                }
                
                // Invalid CSRF token
                setFlash('error', 'Invalid request. Please try again.');
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        }
    }
}

// ============================================
// SECURITY HEADERS
// ============================================

// Set security headers
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Set content security policy
if (APP_ENV === 'production') {
    header("Content-Security-Policy: default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com; " .
           "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; " .
           "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; " .
           "img-src 'self' data: https:; " .
           "frame-src https://www.google.com; " .
           "connect-src 'self'");
}

// ============================================
// SET DEFAULT TIMEZONE
// ============================================

date_default_timezone_set(DEFAULT_TIMEZONE);

// ============================================
// ERROR HANDLING FOR PRODUCTION
// ============================================

if (APP_ENV === 'production') {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
    
    register_shutdown_function(function() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            if (LOG_ERRORS) {
                error_log("Fatal Error: " . json_encode($error), 3, ERROR_LOG_FILE);
            }
            
            if (!headers_sent()) {
                header('HTTP/1.1 500 Internal Server Error');
                if (file_exists(BASE_PATH . '500.php')) {
                    include BASE_PATH . '500.php';
                }
                exit();
            }
        }
    });
}

// ============================================
// AUDIT TRAIL HELPER
// ============================================

/**
 * Log user activity to audit trail
 */
function logActivity($action, $module, $record_id = null, $old_values = null, $new_values = null) {
    if (!AUDIT_TRAIL_ENABLED) {
        return;
    }
    
    try {
        $db = getDB();
        
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $ip_address = getClientIP();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        // Use Database class methods
        $query = "INSERT INTO audit_trail (user_id, action, module, record_id, old_values, new_values, ip_address, user_agent) 
                  VALUES (:user_id, :action, :module, :record_id, :old_values, :new_values, :ip_address, :user_agent)";
        
        $db->query($query);
        $db->bind(':user_id', $user_id);
        $db->bind(':action', $action);
        $db->bind(':module', $module);
        $db->bind(':record_id', $record_id);
        $db->bind(':old_values', $old_values ? json_encode($old_values) : null);
        $db->bind(':new_values', $new_values ? json_encode($new_values) : null);
        $db->bind(':ip_address', $ip_address);
        $db->bind(':user_agent', $user_agent);
        $db->execute();
        
    } catch (Exception $e) {
        error_log("Audit Trail Error: " . $e->getMessage());
    }
}

// ============================================
// FLASH MESSAGE HELPERS
// ============================================

/**
 * Set flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get flash message and clear it
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Display flash message HTML
 */
function displayFlash() {
    $flash = getFlash();
    if ($flash) {
        $type = $flash['type'];
        $message = $flash['message'];
        
        $alert_class = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ];
        
        $class = isset($alert_class[$type]) ? $alert_class[$type] : 'alert-info';
        
        $icon = [
            'success' => 'bi-check-circle-fill',
            'error' => 'bi-exclamation-triangle-fill',
            'warning' => 'bi-exclamation-circle-fill',
            'info' => 'bi-info-circle-fill'
        ];
        
        $icon_class = isset($icon[$type]) ? $icon[$type] : 'bi-info-circle-fill';
        
        echo '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">
                <i class="bi ' . $icon_class . ' me-2"></i> ' . htmlspecialchars($message) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
}

// ============================================
// DATABASE CONNECTION TEST
// ============================================

try {
    $test_db = getDB();
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    // Don't show error on login page
    if (!in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'index.php', '404.php'])) {
        setFlash('error', 'Database connection error. Please contact administrator.');
    }
}

// ============================================
// LOG PAGE ACCESS (Optional)
// ============================================

if (isset($_SESSION['user_id']) && AUDIT_TRAIL_ENABLED) {
    $current_page = basename($_SERVER['PHP_SELF']);
    $skip_logging = ['ajax.php', 'api.php', 'get_data.php', 'logout.php'];
    
    if (!in_array($current_page, $skip_logging)) {
        $significant_pages = ['dashboard.php', 'add.php', 'create.php', 'approve.php', 'delete.php'];
        
        if (in_array($current_page, $significant_pages)) {
            logActivity("Page Access: {$current_page}", 'system');
        }
    }
}

// ============================================
// END OF INITIALIZATION
// ============================================
?>