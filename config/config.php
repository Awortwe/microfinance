<?php
/**
 * Application Configuration
 * Contains all application-wide constants and settings
 */

// Prevent direct access
if (!defined('APP_RUNNING')) {
    define('APP_RUNNING', true);
}

// ============================================
// APPLICATION SETTINGS
// ============================================

define('APP_NAME', 'Nkwa Microfinance');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/microfinance/');
define('APP_ENV', 'development');
define('DEFAULT_LANGUAGE', 'en');
define('DEFAULT_TIMEZONE', 'Africa/Accra');
date_default_timezone_set(DEFAULT_TIMEZONE);

// ============================================
// COMPANY SETTINGS
// ============================================

define('COMPANY_NAME', 'Nkwa Microfinance');
define('COMPANY_ADDRESS', '123 Independence Avenue, Adjacent to ECG Office, Kumasi, Ghana');
define('COMPANY_PHONE', '+233 24 567 8901');
define('COMPANY_EMAIL', 'info@nkwa.com');
define('COMPANY_WEBSITE', 'www.nkwa.com');

// ============================================
// FINANCIAL SETTINGS
// ============================================

define('DEFAULT_CURRENCY', 'GHS');
define('CURRENCY_SYMBOL', 'GHS ');
define('MIN_SAVINGS_BALANCE', 50.00);
define('MAX_ACTIVE_LOANS', 3);
define('DEFAULT_SUSU_AMOUNT', 10.00);
define('LOAN_PROCESSING_FEE', 2.00);
define('LATE_PAYMENT_FEE', 5.00);

// ============================================
// SECURITY SETTINGS
// ============================================

define('SESSION_LIFETIME', 28800); // 8 hours
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 15); // minutes
define('PASSWORD_RESET_EXPIRY', 24); // hours
define('CSRF_PROTECTION', true);

// ============================================
// UPLOAD SETTINGS
// ============================================

define('MAX_UPLOAD_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOC_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx']);
define('UPLOAD_DIR', 'assets/uploads/');
define('CUSTOMER_PHOTO_DIR', UPLOAD_DIR . 'customers/');
define('DOCUMENT_DIR', UPLOAD_DIR . 'documents/');
define('DEFAULT_CUSTOMER_PHOTO', 'assets/images/default-avatar.png');

// ============================================
// PAGINATION SETTINGS
// ============================================

define('RECORDS_PER_PAGE', 20);
define('MAX_RECORDS_PER_PAGE', 100);

// ============================================
// MODULE SETTINGS
// ============================================

define('AUDIT_TRAIL_ENABLED', true);
define('NOTIFICATIONS_ENABLED', true);
define('EMAIL_NOTIFICATIONS', false);
define('SMS_NOTIFICATIONS', false);

// ============================================
// DATE FORMATS
// ============================================

define('DATE_FORMAT', 'M d, Y');
define('DATE_FORMAT_DB', 'Y-m-d');
define('TIME_FORMAT', 'h:i A');
define('DATETIME_FORMAT', 'M d, Y h:i A');
define('DATETIME_FORMAT_DB', 'Y-m-d H:i:s');

// ============================================
// APPLICATION PATHS
// ============================================

define('BASE_PATH', realpath(__DIR__ . '/..') . '/');
define('INCLUDE_PATH', BASE_PATH . 'includes/');
define('CLASS_PATH', BASE_PATH . 'classes/');
define('ADMIN_PATH', BASE_PATH . 'admin/');
define('EMPLOYEE_PATH', BASE_PATH . 'employee/');
define('AUTH_PATH', BASE_PATH . 'auth/');

// ============================================
// ERROR HANDLING
// ============================================

define('DISPLAY_ERRORS', APP_ENV === 'development');
define('LOG_ERRORS', true);
define('ERROR_LOG_FILE', BASE_PATH . 'logs/error.log');

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Create logs directory if it doesn't exist
$logs_dir = BASE_PATH . 'logs';
if (!is_dir($logs_dir)) {
    @mkdir($logs_dir, 0755, true);
}

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Format amount with currency symbol
 * @param float $amount
 * @return string
 */
function formatMoney($amount) {
    return CURRENCY_SYMBOL . number_format((float)$amount, 2);
}

/**
 * Get client IP address
 * @return string
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

/**
 * Redirect to URL
 * @param string $url
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Generate CSRF token input field for forms
 * @return string
 */
function csrfField() {
    if (CSRF_PROTECTION && isset($_SESSION['csrf_token'])) {
        return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
    }
    return '';
}

/**
 * Execute a query and return all results
 * @param string $sql SQL query with :param placeholders
 * @param array $params Associative array of parameters
 * @return array
 */
function dbQuery($sql, $params = []) {
    $db = getDB();
    $db->query($sql);
    if (!empty($params)) {
        $db->bindMultiple($params);
    }
    return $db->resultSet();
}

/**
 * Execute a query and return single result
 * @param string $sql SQL query with :param placeholders
 * @param array $params Associative array of parameters
 * @return array|false
 */
function dbSingle($sql, $params = []) {
    $db = getDB();
    $db->query($sql);
    if (!empty($params)) {
        $db->bindMultiple($params);
    }
    return $db->single();
}

/**
 * Execute an INSERT/UPDATE/DELETE query
 * @param string $sql SQL query with :param placeholders
 * @param array $params Associative array of parameters
 * @return bool
 */
function dbExecute($sql, $params = []) {
    $db = getDB();
    $db->query($sql);
    if (!empty($params)) {
        $db->bindMultiple($params);
    }
    return $db->execute();
}

/**
 * Get last inserted ID
 * @return string
 */
function dbLastInsertId() {
    $db = getDB();
    return $db->lastInsertId();
}

/**
 * Sanitize input string for HTML output
 * @param string $input
 * @return string
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim((string)$input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize for database (remove HTML tags only)
 * @param string $input
 * @return string
 */
function sanitizeDB($input) {
    return strip_tags(trim((string)$input));
}

/**
 * Generate random string
 * @param int $length
 * @return string
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes((int)($length / 2)));
}

/**
 * Check if request is AJAX
 * @return bool
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Get application setting from database
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getSetting($key, $default = null) {
    try {
        $result = dbSingle(
            "SELECT setting_value FROM system_settings WHERE setting_key = :key LIMIT 1",
            [':key' => $key]
        );
        return $result ? $result['setting_value'] : $default;
    } catch(Exception $e) {
        return $default;
    }
}

/**
 * Check if application is in development mode
 * @return bool
 */
function isDevelopment() {
    return APP_ENV === 'development';
}

/**
 * Get asset URL
 * @param string $path
 * @return string
 */
function assetUrl($path) {
    return APP_URL . 'assets/' . ltrim($path, '/');
}

/**
 * Format date for display
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = null) {
    if ($format === null) {
        $format = DATE_FORMAT;
    }
    if (empty($date) || $date == '0000-00-00') return 'N/A';
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 * @param string $datetime
 * @param string $format
 * @return string
 */
function formatDateTime($datetime, $format = null) {
    if ($format === null) {
        $format = DATETIME_FORMAT;
    }
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00') return 'N/A';
    return date($format, strtotime($datetime));
}

/**
 * Validate email address
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate Ghana phone number
 * @param string $phone
 * @return bool
 */
function isValidGhanaPhone($phone) {
    $phone = preg_replace('/\s+/', '', $phone);
    return preg_match('/^(\+233|0)[235467]\d{8}$/', $phone) === 1;
}

/**
 * Get status badge HTML
 * @param string $status
 * @param string $type (customer|loan|savings)
 * @return string
 */
function getStatusBadge($status, $type = 'customer') {
    $badges = [
        'customer' => [
            'active' => '<span class="badge bg-success">Active</span>',
            'inactive' => '<span class="badge bg-secondary">Inactive</span>',
            'blacklisted' => '<span class="badge bg-danger">Blacklisted</span>'
        ],
        'loan' => [
            'pending' => '<span class="badge bg-warning">Pending</span>',
            'approved' => '<span class="badge bg-info">Approved</span>',
            'disbursed' => '<span class="badge bg-primary">Disbursed</span>',
            'active' => '<span class="badge bg-success">Active</span>',
            'completed' => '<span class="badge bg-success">Completed</span>',
            'defaulted' => '<span class="badge bg-danger">Defaulted</span>',
            'written_off' => '<span class="badge bg-dark">Written Off</span>',
            'rejected' => '<span class="badge bg-danger">Rejected</span>'
        ],
        'savings' => [
            'active' => '<span class="badge bg-success">Active</span>',
            'dormant' => '<span class="badge bg-warning">Dormant</span>',
            'closed' => '<span class="badge bg-secondary">Closed</span>'
        ]
    ];
    
    if (isset($badges[$type][$status])) {
        return $badges[$type][$status];
    }
    
    return '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

/**
 * Get transaction type badge HTML
 * @param string $type
 * @return string
 */
function getTransactionBadge($type) {
    $badges = [
        'deposit' => '<span class="badge bg-success"><i class="bi bi-arrow-down"></i> Deposit</span>',
        'withdrawal' => '<span class="badge bg-danger"><i class="bi bi-arrow-up"></i> Withdrawal</span>',
        'interest' => '<span class="badge bg-info"><i class="bi bi-percent"></i> Interest</span>',
        'fee' => '<span class="badge bg-warning"><i class="bi bi-cash"></i> Fee</span>',
        'susu_collection' => '<span class="badge bg-primary"><i class="bi bi-collection"></i> Susu</span>'
    ];
    
    if (isset($badges[$type])) {
        return $badges[$type];
    }
    
    return '<span class="badge bg-secondary">' . ucfirst($type) . '</span>';
}

/**
 * Get pagination data
 * @param int $total_records
 * @param int $current_page
 * @param int|null $per_page
 * @return array
 */
function getPagination($total_records, $current_page = 1, $per_page = null) {
    if ($per_page === null) {
        $per_page = RECORDS_PER_PAGE;
    }
    
    $total_records = (int)$total_records;
    $current_page = (int)$current_page;
    $per_page = (int)$per_page;
    
    $total_pages = ceil($total_records / $per_page);
    $offset = ($current_page - 1) * $per_page;
    
    return [
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'per_page' => $per_page,
        'offset' => $offset,
        'has_previous' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
        'previous_page' => $current_page - 1,
        'next_page' => $current_page + 1
    ];
}

/**
 * Display pagination links HTML
 * @param array $pagination
 * @param string $base_url
 * @return string
 */
function displayPagination($pagination, $base_url) {
    if ($pagination['total_pages'] <= 1) return '';
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    $disabled = $pagination['has_previous'] ? '' : 'disabled';
    $html .= '<li class="page-item ' . $disabled . '">';
    $html .= '<a class="page-link" href="' . $base_url . '?page=' . $pagination['previous_page'] . '">
                <i class="bi bi-chevron-left"></i> Previous</a>';
    $html .= '</li>';
    
    // Page numbers
    for ($i = 1; $i <= $pagination['total_pages']; $i++) {
        $active = ($i == $pagination['current_page']) ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '">';
        $html .= '<a class="page-link" href="' . $base_url . '?page=' . $i . '">' . $i . '</a>';
        $html .= '</li>';
    }
    
    // Next button
    $disabled = $pagination['has_next'] ? '' : 'disabled';
    $html .= '<li class="page-item ' . $disabled . '">';
    $html .= '<a class="page-link" href="' . $base_url . '?page=' . $pagination['next_page'] . '">
                Next <i class="bi bi-chevron-right"></i></a>';
    $html .= '</li>';
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Get user-friendly time ago string
 * @param string $datetime
 * @return string
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date(DATE_FORMAT, $time);
    }
}

/**
 * Truncate text to specified length
 * @param string $text
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncate($text, $length = 100, $suffix = '...') {
    $text = (string)$text;
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Convert number to words
 * @param float|int $number
 * @return string
 */
function numberToWords($number) {
    $number = (int)$number;
    
    if ($number < 0 || $number > 999) {
        return 'Out of range';
    }
    
    $words = [
        0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
        14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
        18 => 'Eighteen', 19 => 'Nineteen'
    ];
    
    $tens = [
        2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty',
        5 => 'Fifty', 6 => 'Sixty', 7 => 'Seventy',
        8 => 'Eighty', 9 => 'Ninety'
    ];
    
    if ($number < 20) {
        return $words[$number];
    }
    
    if ($number < 100) {
        $ten = (int)floor($number / 10);
        $unit = $number % 10;
        $result = isset($tens[$ten]) ? $tens[$ten] : '';
        if ($unit > 0) {
            $result .= '-' . $words[$unit];
        }
        return $result;
    }
    
    if ($number < 1000) {
        $hundred = (int)floor($number / 100);
        $remainder = $number % 100;
        $result = $words[$hundred] . ' Hundred';
        if ($remainder > 0) {
            $result .= ' and ' . numberToWords($remainder);
        }
        return $result;
    }
    
    return 'Out of range';
}

/**
 * Get dynamic company name from database
 * @return string
 */
function companyName() {
    try {
        $result = dbSingle("SELECT setting_value FROM system_settings WHERE setting_key = 'company_name' LIMIT 1");
        if ($result && !empty($result['setting_value'])) {
            return $result['setting_value'];
        }
    } catch (Exception $e) {}
    return COMPANY_NAME;
}

/**
 * Get dynamic company address from database
 * @return string
 */
function companyAddress() {
    try {
        $result = dbSingle("SELECT setting_value FROM system_settings WHERE setting_key = 'company_address' LIMIT 1");
        if ($result && !empty($result['setting_value'])) {
            return $result['setting_value'];
        }
    } catch (Exception $e) {}
    return COMPANY_ADDRESS;
}

/**
 * Get dynamic company phone from database
 * @return string
 */
function companyPhone() {
    try {
        $result = dbSingle("SELECT setting_value FROM system_settings WHERE setting_key = 'company_phone' LIMIT 1");
        if ($result && !empty($result['setting_value'])) {
            return $result['setting_value'];
        }
    } catch (Exception $e) {}
    return COMPANY_PHONE;
}

/**
 * Get dynamic company email from database
 * @return string
 */
function companyEmail() {
    try {
        $result = dbSingle("SELECT setting_value FROM system_settings WHERE setting_key = 'company_email' LIMIT 1");
        if ($result && !empty($result['setting_value'])) {
            return $result['setting_value'];
        }
    } catch (Exception $e) {}
    return COMPANY_EMAIL;
}
// ============================================
// END OF CONFIGURATION
// ============================================
?>