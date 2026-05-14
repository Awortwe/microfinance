<?php
require_once '../../../config/init.php';
require_once '../../../includes/employee_check.php';
require_once '../../../classes/PDFReport.php';

$employee_id = $_SESSION['user_id'];
$employee_name = $_SESSION['full_name'];

$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');
$search = $_GET['search'] ?? '';

// Build filter info
$filter_info = ($search ? 'Search: "' . $search . '" | ' : '') . date('F Y', mktime(0, 0, 0, $month, 1, $year));

// Overall statistics
$overall = dbSingle(
    "SELECT 
    (SELECT COUNT(*) FROM customers WHERE created_by = :emp1) as total_customers,
    (SELECT COUNT(*) FROM customers WHERE created_by = :emp2 AND MONTH(created_at) = :month1 AND YEAR(created_at) = :year1) as new_customers_month,
    (SELECT COUNT(*) FROM loans WHERE created_by = :emp3) as total_loans_created,
    (SELECT COUNT(*) FROM loans WHERE created_by = :emp4 AND MONTH(created_at) = :month2 AND YEAR(created_at) = :year2) as loans_created_month,
    (SELECT COALESCE(SUM(principal_amount), 0) FROM loans WHERE created_by = :emp5 AND MONTH(created_at) = :month3 AND YEAR(created_at) = :year3) as loan_amount_month,
    (SELECT COUNT(*) FROM loans WHERE disbursed_by = :emp6 AND MONTH(disbursement_date) = :month4 AND YEAR(disbursement_date) = :year4) as loans_disbursed_month,
    (SELECT COALESCE(SUM(amount), 0) FROM loan_repayments WHERE processed_by = :emp7) as total_collections,
    (SELECT COALESCE(SUM(amount), 0) FROM loan_repayments WHERE processed_by = :emp8 AND MONTH(payment_date) = :month5 AND YEAR(payment_date) = :year5) as collections_month,
    (SELECT COUNT(*) FROM loan_repayments WHERE processed_by = :emp9 AND MONTH(payment_date) = :month6 AND YEAR(payment_date) = :year6) as collections_count_month,
    (SELECT COALESCE(SUM(amount), 0) FROM savings_transactions WHERE processed_by = :emp10 AND transaction_type = 'deposit') as total_deposits,
    (SELECT COALESCE(SUM(amount), 0) FROM savings_transactions WHERE processed_by = :emp11 AND MONTH(transaction_date) = :month7 AND YEAR(transaction_date) = :year7 AND transaction_type = 'deposit') as deposits_month,
    (SELECT COUNT(*) FROM savings_transactions WHERE processed_by = :emp12 AND MONTH(transaction_date) = :month8 AND YEAR(transaction_date) = :year8) as savings_transactions_month
    FROM dual",
    [
        ':emp1' => $employee_id, ':emp2' => $employee_id, ':emp3' => $employee_id,
        ':emp4' => $employee_id, ':emp5' => $employee_id, ':emp6' => $employee_id,
        ':emp7' => $employee_id, ':emp8' => $employee_id, ':emp9' => $employee_id,
        ':emp10' => $employee_id, ':emp11' => $employee_id, ':emp12' => $employee_id,
        ':month1' => $month, ':year1' => $year, ':month2' => $month, ':year2' => $year,
        ':month3' => $month, ':year3' => $year, ':month4' => $month, ':year4' => $year,
        ':month5' => $month, ':year5' => $year, ':month6' => $month, ':year6' => $year,
        ':month7' => $month, ':year7' => $year, ':month8' => $month, ':year8' => $year
    ]
);

// Build search condition for daily collections
$searchCondition = '';
$searchParams = [':emp' => $employee_id, ':month' => $month, ':year' => $year];
if ($search) {
    $searchCondition = " AND (c.first_name LIKE :search1 OR c.last_name LIKE :search2 OR l.loan_number LIKE :search3)";
    $searchTerm = "%$search%";
    $searchParams[':search1'] = $searchTerm;
    $searchParams[':search2'] = $searchTerm;
    $searchParams[':search3'] = $searchTerm;
}

// Daily collections for the month
$daily_collections = dbQuery(
    "SELECT 
    DATE(lr.payment_date) as date,
    COALESCE(SUM(lr.amount), 0) as total,
    COUNT(*) as count
    FROM loan_repayments lr
    JOIN loans l ON lr.loan_id = l.id
    JOIN customers c ON l.customer_id = c.id
    WHERE lr.processed_by = :emp 
    AND MONTH(lr.payment_date) = :month 
    AND YEAR(lr.payment_date) = :year
    $searchCondition
    GROUP BY DATE(lr.payment_date)
    ORDER BY date",
    $searchParams
);

// Best day
$best_day = dbSingle(
    "SELECT DATE(payment_date) as date, SUM(amount) as total, COUNT(*) as count
     FROM loan_repayments 
     WHERE processed_by = :emp 
     GROUP BY DATE(payment_date) 
     ORDER BY total DESC LIMIT 1",
    [':emp' => $employee_id]
);

// Calculate totals
$total_month_collections = 0;
$total_month_transactions = 0;
foreach ($daily_collections as $day) {
    $total_month_collections += $day['total'];
    $total_month_transactions += $day['count'];
}

$days_with_collections = count($daily_collections);
$avg_daily = $days_with_collections > 0 ? $total_month_collections / $days_with_collections : 0;
$avg_transaction = $total_month_transactions > 0 ? $total_month_collections / $total_month_transactions : 0;

// Create PDF
$pdf = new PDFReport('Performance Report - ' . date('F Y', mktime(0, 0, 0, $month, 1, $year)), 'P', $filter_info);
$pdf->AddPage();

// Performance Summary
$summary_items = [
    'Employee' => $employee_name,
    'Period' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
    'Total Customers' => number_format($overall['total_customers']) . ' (+' . $overall['new_customers_month'] . ' this month)',
    'Monthly Collections' => formatMoney($overall['collections_month']),
    'Collection Transactions' => $overall['collections_count_month'],
    'Loans Created This Month' => number_format($overall['loans_created_month']),
    'Loan Amount Disbursed' => formatMoney($overall['loan_amount_month']),
    'Savings Deposits' => formatMoney($overall['deposits_month']),
    'Avg Daily Collection' => formatMoney($avg_daily),
    'Avg Transaction Value' => formatMoney($avg_transaction)
];

$pdf->addSummaryBox('Performance Overview', $summary_items, 2);
$pdf->Ln(3);

// Best Day Highlight
if ($best_day && $best_day['total'] > 0) {
    $best_items = [
        'Best Day' => date('F d, Y', strtotime($best_day['date'])),
        'Amount Collected' => formatMoney($best_day['total']),
        'Transactions' => $best_day['count']
    ];
    $pdf->addSummaryBox('Best Day Performance', $best_items, 3);
    $pdf->Ln(3);
}

// Daily Collections Table
if (count($daily_collections) > 0) {
    $headers = ['Date', 'Day', 'Transactions', 'Amount (GHS)'];
    $widths = [35, 45, 35, 45];
    $data = [];
    
    foreach ($daily_collections as $day) {
        $data[] = [
            date('M d', strtotime($day['date'])),
            date('l', strtotime($day['date'])),
            $day['count'],
            formatMoney($day['total'])
        ];
    }
    
    $data[] = ['', 'TOTAL', $total_month_transactions, formatMoney($total_month_collections)];
    
    $pdf->addStyledTable($headers, $data, $widths, 'Daily Collections');
}

// Output PDF
$filename = 'Performance_Report_' . $year . '_' . $month;
if ($search) $filename .= '_Filtered';
$filename .= '.pdf';
$pdf->Output($filename, 'D');