<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$product_id = $_GET['id'] ?? 0;

// Get product
$product = dbSingle("SELECT * FROM loan_products WHERE id = :id", [':id' => $product_id]);

if (!$product) {
    setFlash('error', 'Product not found');
    redirect('index.php');
}

// Toggle status (activate/deactivate)
$new_status = ($product['status'] == 'active') ? 'inactive' : 'active';
$action_word = ($new_status == 'active') ? 'activated' : 'deactivated';

dbExecute(
    "UPDATE loan_products SET status = :status WHERE id = :id",
    [
        ':status' => $new_status,
        ':id' => $product_id
    ]
);

logActivity('Product ' . ucfirst($action_word), 'products', $product_id);

setFlash('success', 'Product "' . $product['product_name'] . '" has been ' . $action_word . ' successfully');
redirect('index.php');
?>