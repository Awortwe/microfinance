<?php
require_once '../../../config/init.php';
require_once '../../../includes/admin_check.php';
require_once '../../../classes/PDFReport.php';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$search = $_GET['search'] ?? '';
$filter_info = date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date)) . ($search ? ' | Filtered: "' . $search . '"' : '');

$interest_income = dbSingle("SELECT COALESCE(SUM(interest_paid),0) as total FROM loan_repayments WHERE payment_date BETWEEN :start AND :end", [':start'=>$start_date, ':end'=>$end_date])['total'];
$fee_income = dbSingle("SELECT COALESCE(SUM(processing_fee),0) as total FROM loans WHERE disbursement_date BETWEEN :start AND :end", [':start'=>$start_date, ':end'=>$end_date])['total'];
$late_fee_income = dbSingle("SELECT COALESCE(SUM(late_fee),0) as total FROM loan_repayments WHERE payment_date BETWEEN :start AND :end", [':start'=>$start_date, ':end'=>$end_date])['total'];
$total_income = $interest_income + $fee_income + $late_fee_income;
$savings_interest = dbSingle("SELECT COALESCE(SUM(amount),0) as total FROM savings_transactions WHERE transaction_type='interest' AND transaction_date BETWEEN :start AND :end", [':start'=>$start_date, ':end'=>$end_date])['total'];
$total_expenses = $savings_interest;
$net_profit = $total_income - $total_expenses;
$profit_margin = $total_income > 0 ? round(($net_profit / $total_income) * 100, 2) : 0;

$pdf = new PDFReport('Profit & Loss Report', 'P', $filter_info);
$pdf->AddPage();

$summary_items = [
    'Period' => date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date)),
    'Interest Income' => formatMoney($interest_income),
    'Processing Fees' => formatMoney($fee_income),
    'Late Payment Fees' => formatMoney($late_fee_income),
    'Total Income' => formatMoney($total_income),
    'Savings Interest (Expense)' => formatMoney($savings_interest),
    'Total Expenses' => formatMoney($total_expenses),
    'Net Profit' => formatMoney($net_profit),
    'Profit Margin' => $profit_margin . '%'
];
$pdf->addSummaryBox('Profit & Loss Summary', $summary_items, 2);

$pdf->Output('Profit_Loss_Report.pdf', 'D');