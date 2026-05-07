<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'System Settings';
$base_path = '../../';
$breadcrumb = ['Admin' => '../dashboard.php'];

// Get current settings
$settings = dbQuery("SELECT * FROM system_settings ORDER BY setting_key");

// Convert to associative array
$settings_assoc = [];
foreach ($settings as $setting) {
    $settings_assoc[$setting['setting_key']] = $setting['setting_value'];
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        foreach ($_POST['settings'] as $key => $value) {
            dbExecute(
                "UPDATE system_settings SET setting_value = :value, updated_by = :user_id WHERE setting_key = :key",
                [
                    ':value' => $value,
                    ':user_id' => $_SESSION['user_id'],
                    ':key' => $key
                ]
            );
        }
        
        logActivity('Settings Updated', 'system');
        setFlash('success', 'Settings updated successfully');
        redirect('index.php');
        
    } catch (Exception $e) {
        error_log("Update Settings Error: " . $e->getMessage());
        setFlash('error', 'Failed to update settings');
    }
}

include '../../includes/header.php';
?>

<!-- Quick Links -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-building display-4 text-primary"></i>
                <h5 class="mt-2">Company Profile</h5>
                <p class="text-muted small">Update company name, address, and contact details</p>
                <a href="company.php" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit Profile
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-database-down display-4 text-success"></i>
                <h5 class="mt-2">Database Backup</h5>
                <p class="text-muted small">Create and download database backups</p>
                <a href="backup.php" class="btn btn-success">
                    <i class="bi bi-download"></i> Manage Backups
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-gear-fill display-4 text-warning"></i>
                <h5 class="mt-2">General Settings</h5>
                <p class="text-muted small">Configure financial and system parameters</p>
                <a href="#settingsForm" class="btn btn-warning">
                    <i class="bi bi-sliders"></i> Configure Below
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card" id="settingsForm">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-sliders"></i> General Settings</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    
                    <!-- Company Information -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3">
                            <i class="bi bi-building"></i> Company Information
                        </h6>
                        <hr>
                        
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="settings[company_name]" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings_assoc['company_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Company Address</label>
                            <textarea name="settings[company_address]" class="form-control" rows="2"><?php echo htmlspecialchars($settings_assoc['company_address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company Phone</label>
                                <input type="text" name="settings[company_phone]" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings_assoc['company_phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company Email</label>
                                <input type="email" name="settings[company_email]" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings_assoc['company_email'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Financial Settings -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3">
                            <i class="bi bi-cash-coin"></i> Financial Settings
                        </h6>
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Default Currency</label>
                                <input type="text" name="settings[default_currency]" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings_assoc['default_currency'] ?? 'GHS'); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Max Active Loans</label>
                                <input type="number" name="settings[loan_max_active]" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings_assoc['loan_max_active'] ?? '3'); ?>">
                                <small class="text-muted">Per customer</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Min Savings Balance</label>
                                <input type="number" name="settings[savings_min_balance]" class="form-control" step="0.01" 
                                       value="<?php echo htmlspecialchars($settings_assoc['savings_min_balance'] ?? '50.00'); ?>">
                                <small class="text-muted">In GHS</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Default Susu Amount</label>
                                <input type="number" name="settings[susu_default_amount]" class="form-control" step="0.01" 
                                       value="<?php echo htmlspecialchars($settings_assoc['susu_default_amount'] ?? '10.00'); ?>">
                                <small class="text-muted">In GHS</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Late Payment Fee (%)</label>
                                <input type="number" name="settings[late_fee_percentage]" class="form-control" step="0.01" 
                                       value="<?php echo htmlspecialchars($settings_assoc['late_fee_percentage'] ?? '5.00'); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Session Lifetime</label>
                                <input type="number" name="settings[session_lifetime]" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings_assoc['session_lifetime'] ?? '28800'); ?>">
                                <small class="text-muted">In seconds</small>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="../dashboard.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>