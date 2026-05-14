<?php
require_once '../../../config/init.php';
require_once '../../../includes/admin_check.php';
require_once '../../../classes/PDFReport.php';

$user_id = $_GET['user_id'] ?? '';
$module = $_GET['module'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';
$filter_info = ($search ? 'Search: "' . $search . '" | ' : '') . date('M d, Y', strtotime($date_from)) . ' - ' . date('M d, Y', strtotime($date_to));

$query = "SELECT al.*, u.full_name as user_name FROM audit_trail al LEFT JOIN users u ON al.user_id = u.id WHERE DATE(al.created_at) BETWEEN :df AND :dt";
$params = [':df' => $date_from, ':dt' => $date_to];
if ($user_id) { $query .= " AND al.user_id = :uid"; $params[':uid'] = $user_id; }
if ($module) { $query .= " AND al.module = :mod"; $params[':mod'] = $module; }
if ($search) { $query .= " AND (al.action LIKE :s1 OR u.full_name LIKE :s2)"; $st = "%$search%"; $params[':s1']=$st; $params[':s2']=$st; }
$query .= " ORDER BY al.created_at DESC LIMIT 500";
$trails = dbQuery($query, $params);

$pdf = new PDFReport('Audit Trail Report', 'L', $filter_info);
$pdf->AddPage();

if (count($trails) > 0) {
    $headers = ['Date/Time', 'User', 'Action', 'Module', 'IP Address'];
    $widths = [45, 45, 80, 30, 35];
    $data = [];
    foreach ($trails as $t) {
        $data[] = [
            date('M d, Y h:i A', strtotime($t['created_at'])),
            $t['user_name'] ?? 'System',
            $t['action'],
            $t['module'],
            $t['ip_address']
        ];
    }
    $pdf->addStyledTable($headers, $data, $widths, 'Audit Trail (' . count($trails) . ' records)');
}

$filename = 'Audit_Trail' . ($search ? '_Filtered' : '') . '.pdf';
$pdf->Output($filename, 'D');