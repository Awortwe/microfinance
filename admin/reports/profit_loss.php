<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Profit & Loss Report';
$base_path = '../../';
$breadcrumb = [
    'Admin' => '../dashboard.php',
    'Reports' => 'index.php'
];

// Get date range filter
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Interest income from loans
$interest_income = dbSingle(
    "SELECT COALESCE(SUM(interest_paid), 0) as total FROM loan_repayments WHERE payment_date BETWEEN :start AND :end",
    [':start' => $start_date, ':end' => $end_date]
)['total'];

// Processing fees
$fee_income = dbSingle(
    "SELECT COALESCE(SUM(processing_fee), 0) as total FROM loans WHERE disbursement_date BETWEEN :start AND :end",
    [':start' => $start_date, ':end' => $end_date]
)['total'];

// Late fees
$late_fee_income = dbSingle(
    "SELECT COALESCE(SUM(late_fee), 0) as total FROM loan_repayments WHERE payment_date BETWEEN :start AND :end",
    [':start' => $start_date, ':end' => $end_date]
)['total'];

// Total income
$total_income = $interest_income + $fee_income + $late_fee_income;

// Savings interest paid (expense)
$savings_interest = dbSingle(
    "SELECT COALESCE(SUM(amount), 0) as total FROM savings_transactions WHERE transaction_type = 'interest' AND transaction_date BETWEEN :start AND :end",
    [':start' => $start_date, ':end' => $end_date]
)['total'];

$total_expenses = $savings_interest;
$net_profit = $total_income - $total_expenses;

include '../../includes/header.php';
?>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter"></i> Generate Report
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Total Income</h6>
                <h3><?php echo formatMoney($total_income); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h6>Total Expenses</h6>
                <h3><?php echo formatMoney($total_expenses); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-<?php echo $net_profit >= 0 ? 'primary' : 'warning'; ?> text-white">
            <div class="card-body text-center">
                <h6>Net Profit</h6>
                <h3><?php echo formatMoney($net_profit); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6>Profit Margin</h6>
                <h3><?php echo $total_income > 0 ? round(($net_profit / $total_income) * 100, 2) : 0; ?>%</h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Income Breakdown</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <td>Interest Income from Loans</td>
                        <td class="text-end text-success"><?php echo formatMoney($interest_income); ?></td>
                    </tr>
                    <tr>
                        <td>Processing Fees</td>
                        <td class="text-end text-success"><?php echo formatMoney($fee_income); ?></td>
                    </tr>
                    <tr>
                        <td>Late Payment Fees</td>
                        <td class="text-end text-success"><?php echo formatMoney($late_fee_income); ?></td>
                    </tr>
                    <tr class="table-success fw-bold">
                        <td>Total Income</td>
                        <td class="text-end"><?php echo formatMoney($total_income); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Expense Breakdown</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <td>Savings Interest Paid to Customers</td>
                        <td class="text-end text-danger"><?php echo formatMoney($savings_interest); ?></td>
                    </tr>
                    <tr class="table-danger fw-bold">
                        <td>Total Expenses</td>
                        <td class="text-end"><?php echo formatMoney($total_expenses); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <button onclick="window.print()" class="btn btn-secondary">
        <i class="bi bi-printer"></i> Print Report
    </button>
</div>

<?php include '../../includes/footer.php'; ?>