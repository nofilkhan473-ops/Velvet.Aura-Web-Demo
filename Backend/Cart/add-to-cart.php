<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
$user_id = $_SESSION['user_id'];

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit();
}

// Check if product already in cart
$check = mysqli_query($conn, "SELECT id, quantity FROM cart WHERE user_id = $user_id AND product_id = $product_id");

if (mysqli_num_rows($check) > 0) {
    $row = mysqli_fetch_assoc($check);
    $new_qty = $row['quantity'] + $quantity;
    mysqli_query($conn, "UPDATE cart SET quantity = $new_qty WHERE user_id = $user_id AND product_id = $product_id");
} else {
    mysqli_query($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES ($user_id, $product_id, $quantity)");
}

// Get updated cart count
$count_result = mysqli_query($conn, "SELECT SUM(quantity) as total FROM cart WHERE user_id = $user_id");
$count_row = mysqli_fetch_assoc($count_result);
$cart_count = $count_row['total'] ?? 0;

echo json_encode(['success' => true, 'message' => 'Added to cart', 'cart_count' => $cart_count]);
?>