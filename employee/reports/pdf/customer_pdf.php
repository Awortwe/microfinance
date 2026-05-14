<?php
require_once '../../../config/init.php';
require_once '../../../includes/employee_check.php';
require_once '../../../classes/PDFReport.php';

$employee_id = $_SESSION['user_id'];
$employee_name = $_SESSION['full_name'];
$search = $_GET['search'] ?? '';

// Build filter info
$filter_info = $search ? 'Search: "' . $search . '"' : '';

// Build query with search
$query = "SELECT 
    c.*,
    (SELECT COUNT(*) FROM savings_accounts WHERE customer_id = c.id AND status = 'active') as savings_count,
    (SELECT COALESCE(SUM(balance), 0) FROM savings_accounts WHERE customer_id = c.id AND status = 'active') as total_savings,
    (SELECT COUNT(*) FROM loans WHERE customer_id = c.id) as total_loans,
    (SELECT COUNT(*) FROM loans WHERE customer_id = c.id AND status IN ('active', 'disbursed')) as active_loans,
    (SELECT COALESCE(SUM(principal_amount), 0) FROM loans WHERE customer_id = c.id) as total_loan_amount,
    (SELECT COALESCE(SUM(balance), 0) FROM loans WHERE customer_id = c.id AND status IN ('active', 'disbursed')) as outstanding_loans,
    (SELECT COALESCE(SUM(amount_paid), 0) FROM loans WHERE customer_id = c.id) as total_repaid
    FROM customers c
    WHERE c.created_by = :employee_id AND c.status = 'active'";
$params = [':employee_id' => $employee_id];

if ($search) {
    $query .= " AND (c.first_name LIKE :search1 OR c.last_name LIKE :search2 OR c.phone LIKE :search3 OR c.customer_code LIKE :search4 OR c.city LIKE :search5)";
    $searchTerm = "%$search%";
    $params[':search1'] = $searchTerm;
    $params[':search2'] = $searchTerm;
    $params[':search3'] = $searchTerm;
    $params[':search4'] = $searchTerm;
    $params[':search5'] = $searchTerm;
}

$query .= " ORDER BY c.first_name ASC";

$customers = dbQuery($query, $params);

// Get summary totals
$total_customers = count($customers);
$total_savings = array_sum(array_column($customers, 'total_savings'));
$total_outstanding = array_sum(array_column($customers, 'outstanding_loans'));
$total_loans = array_sum(array_column($customers, 'total_loans'));
$total_repaid = array_sum(array_column($customers, 'total_repaid'));

// Create PDF
$pdf = new PDFReport('Customer Report', 'L', $filter_info);
$pdf->AddPage();

// Summary Box
$summary_items = [
    'Employee' => $employee_name,
    'Total Customers' => number_format($total_customers),
    'Total Savings' => formatMoney($total_savings),
    'Total Loans Issued' => number_format($total_loans),
    'Outstanding Loans' => formatMoney($total_outstanding),
    'Total Repaid' => formatMoney($total_repaid)
];

$pdf->addSummaryBox('Report Summary', $summary_items, 3);
$pdf->Ln(3);

// Customers Table
if (count($customers) > 0) {
    $headers = ['Code', 'Customer Name', 'Phone', 'Savings', 'Total Savings', 'Active Loans', 'Total Loans', 'Outstanding', 'Repaid'];
    $widths = [24, 55, 28, 15, 30, 18, 18, 30, 30];
    $data = [];
    
    foreach ($customers as $customer) {
        $data[] = [
            $customer['customer_code'],
            $customer['first_name'] . ' ' . $customer['last_name'],
            $customer['phone'],
            $customer['savings_count'],
            formatMoney($customer['total_savings']),
            $customer['active_loans'],
            $customer['total_loans'],
            formatMoney($customer['outstanding_loans']),
            formatMoney($customer['total_repaid'])
        ];
    }
    
    // Add totals row
    $data[] = [
        'TOTAL',
        count($customers) . ' customers',
        '',
        array_sum(array_column($customers, 'savings_count')),
        formatMoney($total_savings),
        array_sum(array_column($customers, 'active_loans')),
        $total_loans,
        formatMoney($total_outstanding),
        formatMoney($total_repaid)
    ];
    
    $pdf->addStyledTable($headers, $data, $widths, 'Customer List');
}

// Output PDF
$filename = 'Customer_Report';
if ($search) $filename .= '_Filtered';
$filename .= '.pdf';
$pdf->Output($filename, 'D');