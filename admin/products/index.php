<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$page_title = 'Loan Products Management';
$base_path = '../../';
$breadcrumb = ['Admin' => '../dashboard.php'];

// Get all loan products
$products = dbQuery("SELECT * FROM loan_products ORDER BY product_name");

// Get summary
$summary = dbSingle("SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count,
    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_count
    FROM loan_products");

include '../../includes/header.php';
?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6>Total Products</h6>
                <h3><?php echo $summary['total']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Active</h6>
                <h3><?php echo $summary['active_count']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <h6>Inactive</h6>
                <h3><?php echo $summary['inactive_count']; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-box-seam"></i> Loan Products Management</h5>
        <a href="add.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Product
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Product Name</th>
                        <th>Code</th>
                        <th>Amount Range</th>
                        <th>Interest Rate</th>
                        <th>Duration</th>
                        <th>Processing Fee</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($products) > 0): ?>
                        <?php $count = 1; foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                <?php if ($product['description']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($product['description'], 0, 60)); ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td><code><?php echo htmlspecialchars($product['product_code']); ?></code></td>
                            <td>
                                <?php echo formatMoney($product['min_amount']); ?> - 
                                <?php echo formatMoney($product['max_amount']); ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo $product['interest_rate']; ?>%</span>
                                <br>
                                <small class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $product['interest_type'])); ?></small>
                            </td>
                            <td>
                                <?php echo $product['duration_min_months']; ?> - 
                                <?php echo $product['duration_max_months']; ?> months
                            </td>
                            <td>
                                <?php echo $product['processing_fee_percentage']; ?>%
                                <?php if ($product['processing_fee_fixed'] > 0): ?>
                                    + <?php echo formatMoney($product['processing_fee_fixed']); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $product['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="edit.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-<?php echo $product['status'] == 'active' ? 'danger' : 'success'; ?>" 
                                       title="<?php echo $product['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>"
                                       onclick="return confirm('Are you sure you want to <?php echo $product['status'] == 'active' ? 'deactivate' : 'activate'; ?> this product?')">
                                        <i class="bi bi-<?php echo $product['status'] == 'active' ? 'x-circle' : 'check-circle'; ?>"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="bi bi-inbox display-4 text-muted"></i>
                                <p class="text-muted mb-0 mt-2">No loan products found</p>
                                <a href="add.php" class="btn btn-sm btn-primary mt-2">
                                    <i class="bi bi-plus-circle"></i> Add First Product
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="text-muted ms-3 mb-3">
        <small>Total Products: <strong><?php echo count($products); ?></strong></small>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>