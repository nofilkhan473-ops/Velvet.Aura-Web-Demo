<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id > 0) {
    $product = getProduct($product_id);
    if ($product) {
        echo json_encode([
            'success' => true,
            'product' => [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'description' => $product['description'],
                'in_stock' => $product['in_stock']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
}
?>
