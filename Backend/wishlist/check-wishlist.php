<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['in_wishlist' => false]);
    exit();
}

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if ($product_id > 0) {
    $in_wishlist = isInWishlist($product_id);
    echo json_encode(['in_wishlist' => $in_wishlist]);
} else {
    echo json_encode(['in_wishlist' => false]);
}
?>