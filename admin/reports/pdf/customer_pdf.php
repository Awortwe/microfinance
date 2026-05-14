<?php
require_once '../../../config/init.php';
require_once '../../../includes/admin_check.php';
require_once '../../../classes/PDFReport.php';

$status = $_GET['status'] ?? '';
$agent_id = $_GET['agent_id'] ?? '';
$search = $_GET['search'] ?? '';
$filter_info = ($search ? 'Search: "' . $search . '"' : '') . 
               ($status ? ' | Status: ' . $status : '') . 
               ($agent_id ? ' | Agent filtered' : '');

$query = "SELECT c.*, CONCAT(u.full_name) as created_by_name, 
    (SELECT COUNT(*) FROM savings_accounts WHERE customer_id=c.id AND status='active') as savings_count,
    (SELECT COALESCE(SUM(balance),0) FROM savings_accounts WHERE customer_id=c.id AND status='active') as total_savings,
    (SELECT COUNT(*) FROM loans WHERE customer_id=c.id) as total_loans,
    (SELECT COUNT(*) FROM loans WHERE customer_id=c.id AND status IN ('active','disbursed')) as active_loans,
    (SELECT COALESCE(SUM(balance),0) FROM loans WHERE customer_id=c.id AND status IN ('active','disbursed')) as outstanding_loans,
    (SELECT COALESCE(SUM(amount_paid),0) FROM loans WHERE customer_id=c.id) as total_repaid
    FROM customers c LEFT JOIN users u ON c.created_by=u.id WHERE 1=1";
$params = [];
if($status){$query.=" AND c.status=:status";$params[':status']=$status;}
if($agent_id){$query.=" AND c.agent_id=:aid";$params[':aid']=$agent_id;}
if($search){
    $query.=" AND (c.first_name LIKE :s1 OR c.last_name LIKE :s2 OR c.phone LIKE :s3 OR c.customer_code LIKE :s4)";
    $st="%$search%";
    $params[':s1']=$st;$params[':s2']=$st;$params[':s3']=$st;$params[':s4']=$st;
}
$query.=" ORDER BY c.created_at DESC";
$customers = dbQuery($query, $params);

$pdf = new PDFReport('Customer Report', 'L', $filter_info);
$pdf->AddPage();

if(count($customers)>0){
    $headers = ['Code', 'Customer Name', 'Phone', 'Status', 'Agent', 'Savings', 'Loans', 'Active', 'Outstanding'];
    $widths = [22, 55, 28, 18, 28, 28, 15, 15, 30];
    $data = [];
    foreach($customers as $c){
        $data[] = [
            $c['customer_code'],
            $c['first_name'].' '.$c['last_name'],
            $c['phone'],
            $c['status'],
            getAgentName($c['agent_id']),
            formatMoney($c['total_savings']),
            $c['total_loans'],
            $c['active_loans'],
            formatMoney($c['outstanding_loans'])
        ];
    }
    $data[] = ['TOTAL', count($customers).' customers', '', '', '', 
        formatMoney(array_sum(array_column($customers,'total_savings'))),
        array_sum(array_column($customers,'total_loans')),
        array_sum(array_column($customers,'active_loans')),
        formatMoney(array_sum(array_column($customers,'outstanding_loans')))];
    $pdf->addStyledTable($headers, $data, $widths, 'Customer List');
}

$filename = 'Customer_Report' . ($search ? '_Filtered' : '') . '.pdf';
$pdf->Output($filename, 'D');