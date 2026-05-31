<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to add to wishlist']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = (int)$_POST['product_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if product exists
    $product_check = "SELECT id FROM products WHERE id = $product_id";
    $product_result = mysqli_query($conn, $product_check);
    
    if (mysqli_num_rows($product_result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }
    
    // Check if already in wishlist
    $check_query = "SELECT id FROM wishlist WHERE user_id = $user_id AND product_id = $product_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Remove from wishlist
        $delete_query = "DELETE FROM wishlist WHERE user_id = $user_id AND product_id = $product_id";
        if (mysqli_query($conn, $delete_query)) {
            // Get updated count
            $count_query = "SELECT COUNT(*) as total FROM wishlist WHERE user_id = $user_id";
            $count_result = mysqli_query($conn, $count_query);
            $count_data = mysqli_fetch_assoc($count_result);
            
            echo json_encode([
                'success' => true, 
                'action' => 'removed', 
                'message' => 'Removed from wishlist',
                'wishlist_count' => $count_data['total']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove from wishlist']);
        }
    } else {
        // Add to wishlist
        $insert_query = "INSERT INTO wishlist (user_id, product_id) VALUES ($user_id, $product_id)";
        if (mysqli_query($conn, $insert_query)) {
            // Get updated count
            $count_query = "SELECT COUNT(*) as total FROM wishlist WHERE user_id = $user_id";
            $count_result = mysqli_query($conn, $count_query);
            $count_data = mysqli_fetch_assoc($count_result);
            
            echo json_encode([
                'success' => true, 
                'action' => 'added', 
                'message' => 'Added to wishlist',
                'wishlist_count' => $count_data['total']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add to wishlist']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>