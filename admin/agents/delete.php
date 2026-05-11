<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$agent_id = $_GET['id'] ?? 0;

$agent = dbSingle("SELECT * FROM agents WHERE id = :id", [':id' => $agent_id]);

if (!$agent) {
    setFlash('error', 'Agent not found');
    redirect('index.php');
}

$new_status = ($agent['status'] == 'active') ? 'inactive' : 'active';
$action = ($new_status == 'active') ? 'activated' : 'deactivated';

dbExecute("UPDATE agents SET status = :status WHERE id = :id", [
    ':status' => $new_status,
    ':id' => $agent_id
]);

logActivity('Agent ' . ucfirst($action), 'agents', $agent_id);
setFlash('success', 'Agent ' . $action . ' successfully');
redirect('index.php');
?>