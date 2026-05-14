<?php
require_once '../../../config/init.php';
require_once '../../../includes/employee_check.php';
require_once '../../../classes/PDFReport.php';

$date = $_GET['date'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';
$employee_id = $_SESSION['user_id'];
$employee_name = $_SESSION['full_name'];

// Build filter info
$filter_info = '';
if ($search) {
    $filter_info = 'Search: "' . $search . '" | Date: ' . date('F d, Y', strtotime($date));
} else {
    $filter_info = 'Date: ' . date('F d, Y', strtotime($date));
}

// My collections for the day
$summary = dbSingle(
    "SELECT 
    (SELECT COALESCE(SUM(amount), 0) FROM loan_repayments WHERE processed_by = :id1 AND DATE(payment_date) = :date1) as loan_collections,
    (SELECT COALESCE(SUM(amount), 0) FROM savings_transactions WHERE processed_by = :id2 AND DATE(transaction_date) = :date2 AND transaction_type = 'deposit') as savings_deposits,
    (SELECT COALESCE(SUM(amount), 0) FROM savings_transactions WHERE processed_by = :id3 AND DATE(transaction_date) = :date3 AND transaction_type = 'withdrawal') as savings_withdrawals,
    (SELECT COUNT(*) FROM loan_repayments WHERE processed_by = :id4 AND DATE(payment_date) = :date4) as loan_count,
    (SELECT COUNT(*) FROM savings_transactions WHERE processed_by = :id5 AND DATE(transaction_date) = :date5) as savings_count
    FROM dual",
    [
        ':id1' => $employee_id, ':date1' => $date,
        ':id2' => $employee_id, ':date2' => $date,
        ':id3' => $employee_id, ':date3' => $date,
        ':id4' => $employee_id, ':date4' => $date,
        ':id5' => $employee_id, ':date5' => $date
    ]
);

// Build search condition
$searchCondition = '';
$searchParams = [':emp' => $employee_id, ':date' => $date];
if ($search) {
    $searchCondition = " AND (c.first_name LIKE :search1 OR c.last_name LIKE :search2 OR l.loan_number LIKE :search3)";
    $searchTerm = "%$search%";
    $searchParams[':search1'] = $searchTerm;
    $searchParams[':search2'] = $searchTerm;
    $searchParams[':search3'] = $searchTerm;
}

// Get loan repayments
$repayments = dbQuery(
    "SELECT lr.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name, l.loan_number
     FROM loan_repayments lr
     JOIN loans l ON lr.loan_id = l.id
     JOIN customers c ON l.customer_id = c.id
     WHERE lr.processed_by = :emp AND DATE(lr.payment_date) = :date $searchCondition
     ORDER BY lr.payment_time ASC",
    $searchParams
);

// Build search condition for deposits
$depositSearchParams = [':emp' => $employee_id, ':date' => $date];
$depositSearchCondition = '';
if ($search) {
    $depositSearchCondition = " AND (c.first_name LIKE :search1 OR c.last_name LIKE :search2 OR sa.account_number LIKE :search3)";
    $depositSearchParams[':search1'] = $searchTerm;
    $depositSearchParams[':search2'] = $searchTerm;
    $depositSearchParams[':search3'] = $searchTerm;
}

// Get savings deposits
$deposits = dbQuery(
    "SELECT st.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name, sa.account_number
     FROM savings_transactions st
     JOIN savings_accounts sa ON st.account_id = sa.id
     JOIN customers c ON sa.customer_id = c.id
     WHERE st.processed_by = :emp AND DATE(st.transaction_date) = :date AND st.transaction_type = 'deposit' $depositSearchCondition
     ORDER BY st.transaction_time ASC",
    $depositSearchParams
);

// Create PDF
$pdf = new PDFReport('Daily Collection Report - ' . date('F d, Y', strtotime($date)), 'P', $filter_info);
$pdf->AddPage();

// Summary Box
$summary_items = [
    'Employee Name' => $employee_name,
    'Report Date' => date('F d, Y', strtotime($date)),
    'Total Loan Collections' => formatMoney($summary['loan_collections']),
    'Total Savings Deposits' => formatMoney($summary['savings_deposits']),
    'Total Withdrawals' => formatMoney($summary['savings_withdrawals']),
    'Grand Total' => formatMoney($summary['loan_collections'] + $summary['savings_deposits']),
    'Loan Transactions' => $summary['loan_count'],
    'Savings Transactions' => $summary['savings_count']
];

$pdf->addSummaryBox('Daily Summary', $summary_items, 3);
$pdf->Ln(3);

// Loan Repayments Table
if (count($repayments) > 0) {
    $total_loan = array_sum(array_column($repayments, 'amount'));
    $headers = ['Time', 'Customer Name', 'Loan Number', 'Amount (GHS)'];
    $widths = [30, 70, 50, 35];
    $data = [];
    
    foreach ($repayments as $rep) {
        $data[] = [
            date('h:i A', strtotime($rep['payment_time'])),
            $rep['customer_name'],
            $rep['loan_number'],
            formatMoney($rep['amount'])
        ];
    }
    
    $data[] = ['', '', 'TOTAL', formatMoney($total_loan)];
    
    $pdf->addStyledTable($headers, $data, $widths, 'Loan Repayments (' . count($repayments) . ')');
}

// Savings Deposits Table
if (count($deposits) > 0) {
    $total_dep = array_sum(array_column($deposits, 'amount'));
    $headers = ['Time', 'Customer Name', 'Account Number', 'Amount (GHS)'];
    $widths = [30, 70, 50, 35];
    $data = [];
    
    foreach ($deposits as $dep) {
        $data[] = [
            date('h:i A', strtotime($dep['transaction_time'])),
            $dep['customer_name'],
            $dep['account_number'],
            formatMoney($dep['amount'])
        ];
    }
    
    $data[] = ['', '', 'TOTAL', formatMoney($total_dep)];
    
    $pdf->addStyledTable($headers, $data, $widths, 'Savings Deposits (' . count($deposits) . ')');
}

// Output PDF
$filename = 'Daily_Report_' . $date;
if ($search) $filename .= '_Filtered';
$filename .= '.pdf';
$pdf->Output($filename, 'D');