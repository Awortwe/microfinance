<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Database Backup';
$base_path = '../../';
$breadcrumb = [
    'Admin' => '../dashboard.php',
    'Settings' => 'index.php'
];

$message = '';
$message_type = '';

// Handle backup creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['backup'])) {
    try {
        $db = getDB();
        
        // Get all tables
        $tables = [];
        $db->query("SHOW TABLES");
        $result = $db->resultSet();
        
        foreach ($result as $row) {
            $tables[] = current($row);
        }
        
        $sql = "-- ============================================\n";
        $sql .= "-- " . APP_NAME . " Database Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Total Tables: " . count($tables) . "\n";
        $sql .= "-- ============================================\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $sql .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
        $sql .= "START TRANSACTION;\n\n";
        
        // Backup each table
        foreach ($tables as $table) {
            $sql .= "--\n-- Table structure for `$table`\n--\n\n";
            
            // Get create table syntax
            $db->query("SHOW CREATE TABLE `$table`");
            $create_table = $db->single();
            $create_sql = current(array_slice($create_table, 1, 1));
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $create_sql . ";\n\n";
            
            // Get table data
            $sql .= "--\n-- Dumping data for `$table`\n--\n\n";
            
            $db->query("SELECT * FROM `$table`");
            $rows = $db->resultSet();
            
            if (count($rows) > 0) {
                // Get column names
                $columns = array_keys($rows[0]);
                $column_list = '`' . implode('`, `', $columns) . '`';
                
                $sql .= "/*!40000 ALTER TABLE `$table` DISABLE KEYS */;\n";
                
                // Insert in batches
                $batch_size = 50;
                $batches = array_chunk($rows, $batch_size);
                
                foreach ($batches as $batch) {
                    $sql .= "INSERT INTO `$table` ($column_list) VALUES\n";
                    
                    $value_sets = [];
                    foreach ($batch as $row) {
                        $values = array_map(function($value) {
                            if ($value === null) return 'NULL';
                            if (is_numeric($value)) return $value;
                            $value = str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
                            return "'" . $value . "'";
                        }, array_values($row));
                        
                        $value_sets[] = '(' . implode(', ', $values) . ')';
                    }
                    
                    $sql .= implode(",\n", $value_sets) . ";\n";
                }
                
                $sql .= "/*!40000 ALTER TABLE `$table` ENABLE KEYS */;\n\n";
            } else {
                $sql .= "-- Table `$table` has no data\n\n";
            }
        }
        
        $sql .= "COMMIT;\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        // Create backup directory
        $backup_dir = '../../backups/';
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        // Save backup file
        $filename = 'backup_' . APP_NAME . '_' . date('Y-m-d_H-i-s') . '.sql';
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        $filepath = $backup_dir . $filename;
        
        file_put_contents($filepath, $sql);
        
        // Try to compress
        if (function_exists('gzopen')) {
            $gz_filepath = $filepath . '.gz';
            $fp = gzopen($gz_filepath, 'w9');
            if ($fp) {
                gzwrite($fp, $sql);
                gzclose($fp);
                unlink($filepath);
                $filepath = $gz_filepath;
                $filename .= '.gz';
            }
        }
        
        $file_size = round(filesize($filepath) / 1024, 2);
        
        $message = "Backup created successfully!<br>File: $filename<br>Size: $file_size KB";
        $message_type = 'success';
        
        logActivity('Database Backup Created', 'system');
        
    } catch (Exception $e) {
        error_log("Backup Error: " . $e->getMessage());
        $message = "Backup failed: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Handle backup deletion
if (isset($_GET['delete']) && !empty($_GET['file'])) {
    $file = basename($_GET['file']);
    $filepath = '../../backups/' . $file;
    
    if (file_exists($filepath)) {
        if (unlink($filepath)) {
            logActivity('Backup File Deleted', 'system');
            setFlash('success', "Backup file deleted successfully");
        } else {
            setFlash('error', "Failed to delete backup file");
        }
    } else {
        setFlash('error', "Backup file not found");
    }
    redirect('backup.php');
}

// Get existing backups
$backup_dir = '../../backups/';
$backups = [];
if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && is_file($backup_dir . $file)) {
            $filepath = $backup_dir . $file;
            $backups[] = [
                'name' => $file,
                'size' => filesize($filepath),
                'date' => date('Y-m-d H:i:s', filemtime($filepath))
            ];
        }
    }
    // Sort by date descending
    usort($backups, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });
}

include '../../includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Create Backup -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-database-down"></i> Create Backup</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Create a complete backup of your database including all tables, structure, and data.</p>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Backup includes:</strong>
                    <ul class="mb-0 mt-2">
                        <li>All table structures</li>
                        <li>All data records</li>
                        <li>Triggers and views</li>
                    </ul>
                </div>
                
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <button type="submit" name="backup" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-download"></i> Create Backup Now
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Backup History -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Backup History</h5>
                <span class="badge bg-info"><?php echo count($backups); ?> backups</span>
            </div>
            <div class="card-body">
                <?php if (count($backups) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Size</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                <tr>
                                    <td>
                                        <small>
                                            <i class="bi bi-file-earmark-<?php echo strpos($backup['name'], '.gz') !== false ? 'zip' : 'code'; ?>"></i>
                                            <?php echo htmlspecialchars($backup['name']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small>
                                            <?php 
                                            if ($backup['size'] > 1048576) {
                                                echo round($backup['size'] / 1048576, 2) . ' MB';
                                            } elseif ($backup['size'] > 1024) {
                                                echo round($backup['size'] / 1024, 2) . ' KB';
                                            } else {
                                                echo $backup['size'] . ' bytes';
                                            }
                                            ?>
                                        </small>
                                    </td>
                                    <td><small><?php echo date('M d, Y H:i', strtotime($backup['date'])); ?></small></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="../../backups/<?php echo urlencode($backup['name']); ?>" 
                                               class="btn btn-sm btn-success" download title="Download">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <a href="backup.php?delete=1&file=<?php echo urlencode($backup['name']); ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Delete this backup file?')" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-inbox display-4 text-muted"></i>
                        <p class="text-muted mb-0 mt-2">No backups found</p>
                        <small class="text-muted">Click "Create Backup Now" to create your first backup</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>