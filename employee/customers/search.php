<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$page_title = 'Search Customers';
$base_path = '../../';
$breadcrumb = [
    'Dashboard' => '../dashboard.php',
    'Customers' => 'index.php'
];

$results = [];
$searched = false;

if (isset($_GET['search_term']) && !empty(trim($_GET['search_term']))) {
    $searched = true;
    $search_term = $_GET['search_term'];
    
    $results = dbQuery(
        "SELECT * FROM customers 
         WHERE (first_name LIKE :term1 
         OR last_name LIKE :term2 
         OR phone LIKE :term3 
         OR customer_code LIKE :term4
         OR email LIKE :term5
         OR occupation LIKE :term6
         OR business_name LIKE :term7)
         ORDER BY 
         CASE WHEN status = 'active' THEN 0 ELSE 1 END,
         first_name ASC
         LIMIT 50",
        [
            ':term1' => "%$search_term%",
            ':term2' => "%$search_term%",
            ':term3' => "%$search_term%",
            ':term4' => "%$search_term%",
            ':term5' => "%$search_term%",
            ':term6' => "%$search_term%",
            ':term7' => "%$search_term%"
        ]
    );
}

include '../../includes/header.php';
?>

<!-- Search Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="">
            <div class="row">
                <div class="col-md-10">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search_term" class="form-control" 
                               placeholder="Search by name, phone number, customer code, email, occupation..."
                               value="<?php echo htmlspecialchars($_GET['search_term'] ?? ''); ?>"
                               autofocus>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </div>
            <small class="text-muted mt-2 d-block">
                <i class="bi bi-info-circle"></i> 
                You can search by any customer information - name, phone, code, email, occupation, or business name
            </small>
        </form>
    </div>
</div>

<!-- Search Results -->
<?php if ($searched): ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-list-ul"></i> 
            Search Results 
            <span class="badge bg-info"><?php echo count($results); ?> found</span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (count($results) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Occupation</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $customer): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($customer['customer_code']); ?></code></td>
                            <td>
                                <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong>
                                <?php if ($customer['business_name']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($customer['business_name']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                            <td><?php echo $customer['email'] ? htmlspecialchars($customer['email']) : 'N/A'; ?></td>
                            <td><?php echo $customer['occupation'] ? htmlspecialchars($customer['occupation']) : 'N/A'; ?></td>
                            <td><?php echo getStatusBadge($customer['status'], 'customer'); ?></td>
                            <td>
                                <a href="view.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="bi bi-search display-4 text-muted"></i>
                <p class="text-muted mb-0 mt-2">No customers found matching your search</p>
                <small class="text-muted">Try different search terms or <a href="add.php">add a new customer</a></small>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="text-center py-5">
    <i class="bi bi-search display-1 text-muted"></i>
    <h4 class="text-muted mt-3">Search for Customers</h4>
    <p class="text-muted">Enter a search term above to find customers</p>
</div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>