<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Company Profile';
$base_path = '../../';
$breadcrumb = [
    'Admin' => '../dashboard.php',
    'Settings' => 'index.php'
];

// Get company settings
$settings = dbQuery("SELECT * FROM system_settings WHERE setting_key LIKE 'company_%' OR setting_key LIKE 'default_%'");

// Convert to associative array
$settings_assoc = [];
foreach ($settings as $setting) {
    $settings_assoc[$setting['setting_key']] = $setting['setting_value'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $fields = [
            'company_name', 'company_address', 'company_phone', 
            'company_email', 'default_currency'
        ];
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                dbExecute(
                    "UPDATE system_settings SET setting_value = :value, updated_by = :user_id WHERE setting_key = :key",
                    [
                        ':value' => $_POST[$field],
                        ':user_id' => $_SESSION['user_id'],
                        ':key' => $field
                    ]
                );
            }
        }
        
        logActivity('Company Profile Updated', 'settings');
        setFlash('success', 'Company profile updated successfully');
        redirect('company.php');
        
    } catch (Exception $e) {
        error_log("Update Company Error: " . $e->getMessage());
        setFlash('error', 'Failed to update company profile');
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-building"></i> Company Profile</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Company Name *</label>
                        <input type="text" name="company_name" class="form-control" 
                               value="<?php echo htmlspecialchars($settings_assoc['company_name'] ?? APP_NAME); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="company_address" class="form-control" rows="3"><?php echo htmlspecialchars($settings_assoc['company_address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="company_phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings_assoc['company_phone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="company_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings_assoc['company_email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Default Currency</label>
                        <input type="text" name="default_currency" class="form-control" 
                               value="<?php echo htmlspecialchars($settings_assoc['default_currency'] ?? 'GHS'); ?>">
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Back</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>