<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Profit & Loss Report';
$base_path = '../../';
$breadcrumb = [
    'Admin' => '../dashboard.php',
    'Reports' => 'index.php'
];

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$search = $_GET['search'] ?? '';

// Interest income from loans (with optional search)
$searchCondition = '';
$searchParams = [':start' => $start_date, ':end' => $end_date];
if ($search) {
    $searchCondition = " AND (c.first_name LIKE :s1 OR c.last_name LIKE :s2 OR l.loan_number LIKE :s3)";
    $st = "%$search%";
    $searchParams[':s1'] = $st; $searchParams[':s2'] = $st; $searchParams[':s3'] = $st;
}

$interest_income = dbSingle(
    "SELECT COALESCE(SUM(lr.interest_paid), 0) as total 
     FROM loan_repayments lr
     JOIN loans l ON lr.loan_id = l.id
     JOIN customers c ON l.customer_id = c.id
     WHERE lr.payment_date BETWEEN :start AND :end $searchCondition",
    $searchParams
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

$total_income = $interest_income + $fee_income + $late_fee_income;

// Savings interest paid (expense)
$savings_interest = dbSingle(
    "SELECT COALESCE(SUM(amount), 0) as total FROM savings_transactions WHERE transaction_type = 'interest' AND transaction_date BETWEEN :start AND :end",
    [':start' => $start_date, ':end' => $end_date]
)['total'];

$total_expenses = $savings_interest;
$net_profit = $total_income - $total_expenses;

$pdf_params = http_build_query(['start_date' => $start_date, 'end_date' => $end_date, 'search' => $search]);

include '../../includes/header.php';
?>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Search (Customer/Loan)</label>
                <input type="text" name="search" class="form-control" 
                       placeholder="Filter income by customer/loan..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-filter"></i> Generate</button>
                    <a href="profit_loss.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Clear</a>
                    <button onclick="window.print()" class="btn btn-secondary"><i class="bi bi-printer"></i> Print</button>
                    <a href="pdf/profit_loss_pdf.php?<?php echo $pdf_params; ?>" class="btn btn-danger" target="_blank"><i class="bi bi-file-pdf"></i> PDF</a>
                </div>
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
            <div class="card-header"><h5 class="mb-0">Income Breakdown</h5></div>
            <div class="card-body">
                <table class="table">
                    <tr><td>Interest Income from Loans</td><td class="text-end text-success"><?php echo formatMoney($interest_income); ?></td></tr>
                    <tr><td>Processing Fees</td><td class="text-end text-success"><?php echo formatMoney($fee_income); ?></td></tr>
                    <tr><td>Late Payment Fees</td><td class="text-end text-success"><?php echo formatMoney($late_fee_income); ?></td></tr>
                    <tr class="table-success fw-bold"><td>Total Income</td><td class="text-end"><?php echo formatMoney($total_income); ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Expense Breakdown</h5></div>
            <div class="card-body">
                <table class="table">
                    <tr><td>Savings Interest Paid to Customers</td><td class="text-end text-danger"><?php echo formatMoney($savings_interest); ?></td></tr>
                    <tr class="table-danger fw-bold"><td>Total Expenses</td><td class="text-end"><?php echo formatMoney($total_expenses); ?></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($search): ?>
<div class="mt-3">
    <div class="alert alert-info">
        <small>Income filtered by: "<strong><?php echo htmlspecialchars($search); ?></strong>"</small>
    </div>
</div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>