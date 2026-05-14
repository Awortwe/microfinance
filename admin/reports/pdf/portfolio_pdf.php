<?php
require_once '../../../config/init.php';
require_once '../../../includes/admin_check.php';
require_once '../../../classes/PDFReport.php';

$search = $_GET['search'] ?? '';
$filter_info = $search ? 'Search: "' . $search . '"' : '';

$portfolio = dbSingle("SELECT COUNT(*) as total_loans, COALESCE(SUM(principal_amount),0) as total_disbursed, COALESCE(SUM(amount_paid),0) as total_repaid, COALESCE(SUM(balance),0) as total_outstanding, COUNT(CASE WHEN status='active' THEN 1 END) as active_count, COUNT(CASE WHEN status='completed' THEN 1 END) as completed_count, COUNT(CASE WHEN status='defaulted' THEN 1 END) as defaulted_count FROM loans");

$productQuery = "SELECT lp.product_name, COUNT(l.id) as loan_count, COALESCE(SUM(l.principal_amount),0) as total_amount, COALESCE(SUM(l.balance),0) as outstanding, ROUND(AVG(l.interest_rate),2) as avg_rate FROM loan_products lp LEFT JOIN loans l ON lp.id=l.product_id WHERE 1=1";
$productParams = [];
if ($search) { $productQuery .= " AND (lp.product_name LIKE :s1 OR lp.product_code LIKE :s2)"; $st="%$search%"; $productParams[':s1']=$st; $productParams[':s2']=$st; }
$productQuery .= " GROUP BY lp.id, lp.product_name ORDER BY total_amount DESC";
$portfolio_by_product = dbQuery($productQuery, $productParams);

$pdf = new PDFReport('Loan Portfolio Report', 'L', $filter_info);
$pdf->AddPage();

$par = $portfolio['total_disbursed'] > 0 ? ($portfolio['total_outstanding'] / $portfolio['total_disbursed']) * 100 : 0;
$default_rate = $portfolio['total_loans'] > 0 ? ($portfolio['defaulted_count'] / $portfolio['total_loans']) * 100 : 0;
$recovery_rate = $portfolio['total_disbursed'] > 0 ? ($portfolio['total_repaid'] / $portfolio['total_disbursed']) * 100 : 0;

$summary_items = [
    'Total Loans' => number_format($portfolio['total_loans']),
    'Active Loans' => number_format($portfolio['active_count']),
    'Completed Loans' => number_format($portfolio['completed_count']),
    'Defaulted Loans' => number_format($portfolio['defaulted_count']),
    'Total Disbursed' => formatMoney($portfolio['total_disbursed']),
    'Total Repaid' => formatMoney($portfolio['total_repaid']),
    'Outstanding' => formatMoney($portfolio['total_outstanding']),
    'Portfolio at Risk' => round($par, 2) . '%',
    'Default Rate' => round($default_rate, 2) . '%',
    'Recovery Rate' => round($recovery_rate, 2) . '%'
];
$pdf->addSummaryBox('Portfolio Summary', $summary_items, 2);

if (count($portfolio_by_product) > 0) {
    $headers = ['Product', 'Loans', 'Total Amount', 'Outstanding', 'Avg Rate'];
    $widths = [65, 30, 45, 45, 30];
    $data = [];
    foreach ($portfolio_by_product as $p) {
        $data[] = [$p['product_name'], $p['loan_count'], formatMoney($p['total_amount']), formatMoney($p['outstanding']), $p['avg_rate'].'%'];
    }
    $pdf->addStyledTable($headers, $data, $widths, 'Portfolio by Product');
}

$pdf->Output('Portfolio_Report.pdf', 'D');