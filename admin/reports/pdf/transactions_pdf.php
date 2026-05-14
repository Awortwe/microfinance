<?php
require_once '../../../config/init.php';
require_once '../../../includes/admin_check.php';
require_once '../../../classes/PDFReport.php';

$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';
$filter_info = ($search ? 'Search: "' . $search . '" | ' : '') . date('M d, Y', strtotime($date_from)) . ' - ' . date('M d, Y', strtotime($date_to));

$searchCondition = '';
$searchParams = [':df1'=>$date_from, ':dt1'=>$date_to, ':df2'=>$date_from, ':dt2'=>$date_to];
if ($search) {
    $searchCondition = " AND (c1.first_name LIKE :s1 OR c1.last_name LIKE :s2 OR l.loan_number LIKE :s3 OR c2.first_name LIKE :s4 OR c2.last_name LIKE :s5 OR sa.account_number LIKE :s6)";
    $st = "%$search%";
    $searchParams[':s1']=$st;$searchParams[':s2']=$st;$searchParams[':s3']=$st;
    $searchParams[':s4']=$st;$searchParams[':s5']=$st;$searchParams[':s6']=$st;
}

$query = "SELECT 'Loan Repayment' as ttype, lr.amount, lr.payment_date as tdate, lr.payment_time as ttime, CONCAT(c1.first_name,' ',c1.last_name) as cname, l.loan_number as ref, u.full_name as pby, lr.payment_method as pm FROM loan_repayments lr JOIN loans l ON lr.loan_id=l.id JOIN customers c1 ON l.customer_id=c1.id LEFT JOIN users u ON lr.processed_by=u.id WHERE lr.payment_date BETWEEN :df1 AND :dt1 $searchCondition
UNION ALL
SELECT CONCAT('Savings ',st.transaction_type), st.amount, st.transaction_date, st.transaction_time, CONCAT(c2.first_name,' ',c2.last_name), sa.account_number, u2.full_name, st.payment_method FROM savings_transactions st JOIN savings_accounts sa ON st.account_id=sa.id JOIN customers c2 ON sa.customer_id=c2.id LEFT JOIN users u2 ON st.processed_by=u2.id WHERE st.transaction_date BETWEEN :df2 AND :dt2 $searchCondition ORDER BY tdate DESC, ttime DESC LIMIT 300";
$transactions = dbQuery($query, $searchParams);

$pdf = new PDFReport('Transactions Log', 'L', $filter_info);
$pdf->AddPage();

if (count($transactions) > 0) {
    $headers = ['Date', 'Type', 'Customer', 'Reference', 'Amount', 'Method', 'Processed By'];
    $widths = [35, 35, 50, 40, 35, 25, 40];
    $data = [];
    foreach ($transactions as $t) {
        $data[] = [
            date('M d, Y', strtotime($t['tdate'])) . ' ' . date('h:i A', strtotime($t['ttime'])),
            $t['ttype'],
            $t['cname'],
            $t['ref'],
            formatMoney($t['amount']),
            ucfirst($t['pm']),
            $t['pby'] ?? 'N/A'
        ];
    }
    $total = array_sum(array_column($transactions, 'amount'));
    $data[] = ['', '', '', '', formatMoney($total), '', ''];
    $pdf->addStyledTable($headers, $data, $widths, 'Transactions (' . count($transactions) . ' records)');
}

$pdf->Output('Transactions_Log.pdf', 'D');