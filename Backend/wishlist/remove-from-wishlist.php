<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Get product ID from POST or JSON
$product_id = 0;
if (isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    $product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
}

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Delete specific product from wishlist
$query = "DELETE FROM wishlist WHERE user_id = $user_id AND product_id = $product_id";

if (mysqli_query($conn, $query)) {
    // Get updated count
    $count_query = "SELECT COUNT(*) as total FROM wishlist WHERE user_id = $user_id";
    $count_result = mysqli_query($conn, $count_query);
    $count_row = mysqli_fetch_assoc($count_result);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Removed from wishlist',
        'wishlist_count' => $count_row['total'] ?? 0
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . mysqli_error($conn)
    ]);
}
?>