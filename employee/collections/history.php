<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = 'Collection History';
$base_path = '../../';
$breadcrumb = [
    'Dashboard' => '../dashboard.php',
    'Collections' => 'index.php'
];

$employee_id = $_SESSION['user_id'];

// Get filter parameters
$type = $_GET['type'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 25;
$offset = ($page - 1) * $per_page;

// Build combined query for count
$count_query = "SELECT COUNT(*) as total FROM (
    SELECT lr.id FROM loan_repayments lr
    JOIN loans l ON lr.loan_id = l.id
    JOIN customers c1 ON l.customer_id = c1.id
    WHERE lr.processed_by = :emp1
    AND DATE(lr.payment_date) BETWEEN :df1 AND :dt1
    UNION ALL
    SELECT st.id FROM savings_transactions st
    JOIN savings_accounts sa ON st.account_id = sa.id
    JOIN customers c2 ON sa.customer_id = c2.id
    WHERE st.processed_by = :emp2
    AND DATE(st.transaction_date) BETWEEN :df2 AND :dt2
    AND st.transaction_type = 'deposit'
) as combined";

$total = dbSingle($count_query, [
    ':emp1' => $employee_id, ':df1' => $date_from, ':dt1' => $date_to,
    ':emp2' => $employee_id, ':df2' => $date_from, ':dt2' => $date_to
])['total'];

$total_pages = ceil($total / $per_page);

// Get records
$query = "SELECT 
            'Loan Repayment' as collection_type,
            lr.amount,
            lr.payment_date as collection_date,
            lr.payment_time as collection_time,
            CONCAT(c1.first_name, ' ', c1.last_name) as customer_name,
            c1.customer_code,
            l.loan_number as reference_number,
            lr.payment_method,
            lr.reference_number as transaction_ref,
            lr.notes,
            lr.balance_before,
            lr.balance_after
          FROM loan_repayments lr
          JOIN loans l ON lr.loan_id = l.id
          JOIN customers c1 ON l.customer_id = c1.id
          WHERE lr.processed_by = :emp1
          AND DATE(lr.payment_date) BETWEEN :df1 AND :dt1
          
          UNION ALL
          
          SELECT 
            'Savings Deposit' as collection_type,
            st.amount,
            st.transaction_date as collection_date,
            st.transaction_time as collection_time,
            CONCAT(c2.first_name, ' ', c2.last_name) as customer_name,
            c2.customer_code,
            sa.account_number as reference_number,
            st.payment_method,
            NULL as transaction_ref,
            st.description as notes,
            st.balance_before,
            st.balance_after
          FROM savings_transactions st
          JOIN savings_accounts sa ON st.account_id = sa.id
          JOIN customers c2 ON sa.customer_id = c2.id
          WHERE st.processed_by = :emp2
          AND DATE(st.transaction_date) BETWEEN :df2 AND :dt2
          AND st.transaction_type = 'deposit'
          
          ORDER BY collection_date DESC, collection_time DESC
          LIMIT :offset, :per_page";

// We need to use Database class directly for LIMIT with bind
$db = getDB();
$db->query($query);
$db->bind(':emp1', $employee_id);
$db->bind(':df1', $date_from);
$db->bind(':dt1', $date_to);
$db->bind(':emp2', $employee_id);
$db->bind(':df2', $date_from);
$db->bind(':dt2', $date_to);
$db->bind(':offset', $offset, PDO::PARAM_INT);
$db->bind(':per_page', $per_page, PDO::PARAM_INT);
$collections = $db->resultSet();

// Get summary
$summary = dbSingle(
    "SELECT 
    COALESCE(SUM(CASE WHEN type = 'loan' THEN amount ELSE 0 END), 0) as total_loans,
    COALESCE(SUM(CASE WHEN type = 'savings' THEN amount ELSE 0 END), 0) as total_savings,
    COUNT(CASE WHEN type = 'loan' THEN 1 END) as loan_count,
    COUNT(CASE WHEN type = 'savings' THEN 1 END) as savings_count
    FROM (
        SELECT 'loan' as type, lr.amount
        FROM loan_repayments lr
        WHERE lr.processed_by = :emp1 
        AND DATE(lr.payment_date) BETWEEN :df1 AND :dt1
        UNION ALL
        SELECT 'savings' as type, st.amount
        FROM savings_transactions st
        WHERE st.processed_by = :emp2 
        AND DATE(st.transaction_date) BETWEEN :df2 AND :dt2
        AND st.transaction_type = 'deposit'
    ) as summary_data",
    [
        ':emp1' => $employee_id, ':df1' => $date_from, ':dt1' => $date_to,
        ':emp2' => $employee_id, ':df2' => $date_from, ':dt2' => $date_to
    ]
);

include '../../includes/header.php';
?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6>Total Collections</h6>
                <h3><?php echo formatMoney($summary['total_loans'] + $summary['total_savings']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Loan Repayments</h6>
                <h3><?php echo formatMoney($summary['total_loans']); ?></h3>
                <small><?php echo $summary['loan_count']; ?> txns</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6>Savings Deposits</h6>
                <h3><?php echo formatMoney($summary['total_savings']); ?></h3>
                <small><?php echo $summary['savings_count']; ?> txns</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h6>Total Transactions</h6>
                <h3><?php echo $summary['loan_count'] + $summary['savings_count']; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Collection History</h5>
        <div>
            <button onclick="window.print()" class="btn btn-sm btn-secondary me-2 no-print">
                <i class="bi bi-printer"></i> Print
            </button>
            <a href="record.php" class="btn btn-sm btn-success">
                <i class="bi bi-plus-circle"></i> New Collection
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" class="row g-3 mb-4 no-print">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" 
                       placeholder="Search customer, reference..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="loan" <?php echo $type == 'loan' ? 'selected' : ''; ?>>Loan Repayments</option>
                    <option value="savings" <?php echo $type == 'savings' ? 'selected' : ''; ?>>Savings Deposits</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-info w-100">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            </div>
        </form>

        <!-- Collections Table -->
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead class="table-light">
                    <tr>
                        <th>Date/Time</th>
                        <th>Type</th>
                        <th>Customer</th>
                        <th>Reference</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Balance Before</th>
                        <th>Balance After</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($collections) > 0): ?>
                        <?php foreach ($collections as $col): ?>
                        <tr>
                            <td>
                                <small>
                                    <?php echo date('M d, Y', strtotime($col['collection_date'])); ?>
                                    <br>
                                    <?php echo date('h:i A', strtotime($col['collection_time'])); ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $col['collection_type'] == 'Loan Repayment' ? 'success' : 'primary'; ?>">
                                    <?php echo $col['collection_type']; ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($col['customer_name']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($col['customer_code']); ?></small>
                            </td>
                            <td><code><?php echo htmlspecialchars($col['reference_number']); ?></code></td>
                            <td class="fw-bold text-success"><?php echo formatMoney($col['amount']); ?></td>
                            <td><?php echo ucfirst($col['payment_method']); ?></td>
                            <td><?php echo formatMoney($col['balance_before']); ?></td>
                            <td><?php echo formatMoney($col['balance_after']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="bi bi-inbox display-4 text-muted"></i>
                                <p class="text-muted mb-0 mt-2">No collections found for this period</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if (count($collections) > 0): ?>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="4">Total (<?php echo count($collections); ?> records)</td>
                        <td class="text-success">
                            <?php echo formatMoney(array_sum(array_column($collections, 'amount'))); ?>
                        </td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav class="no-print">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&type=<?php echo $type; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&search=<?php echo $search; ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&type=<?php echo $type; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&search=<?php echo $search; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&type=<?php echo $type; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&search=<?php echo $search; ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>