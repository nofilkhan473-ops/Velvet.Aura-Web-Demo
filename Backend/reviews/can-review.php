<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo json_encode([
        'can_review' => false, 
        'message' => 'Please login to submit review'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = (int)$_GET['product_id'];

if($product_id <= 0) {
    echo json_encode([
        'can_review' => false, 
        'message' => 'Invalid product'
    ]);
    exit();
}

// Check if user has already reviewed this product
$check_query = "SELECT id FROM reviews WHERE user_id = $user_id AND product_id = $product_id";
$check_result = mysqli_query($conn, $check_query);

if(mysqli_num_rows($check_result) > 0) {
    echo json_encode([
        'can_review' => false, 
        'message' => 'You have already reviewed this product'
    ]);
    exit();
}

// OPTION 1: Allow everyone to review (no purchase required)
// Use this if you want all logged-in users to review
echo json_encode([
    'can_review' => true, 
    'message' => 'You can review this product'
]);
exit();

// OPTION 2: Only allow users who purchased this product (uncomment below and comment OPTION 1)
/*
$query = "SELECT o.id FROM orders o 
          JOIN order_items oi ON o.id = oi.order_id 
          WHERE o.user_id = $user_id 
          AND oi.product_id = $product_id 
          AND o.order_status = 'delivered'";
$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) == 0) {
    echo json_encode([
        'can_review' => false, 
        'message' => 'You can only review products you have purchased and received'
    ]);
    exit();
}

echo json_encode([
    'can_review' => true, 
    'message' => 'You can review this product'
]);
*/
?>