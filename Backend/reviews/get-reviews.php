<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$product_id = (int)$_GET['product_id'];

if($product_id <= 0) {
    echo json_encode(['success' => false, 'reviews' => [], 'average_rating' => 0, 'total_reviews' => 0]);
    exit();
}

// Get approved reviews only
$query = "SELECT r.*, u.name as user_name 
          FROM reviews r 
          JOIN users u ON r.user_id = u.id 
          WHERE r.product_id = $product_id AND r.is_approved = 1 
          ORDER BY r.created_at DESC";
$result = mysqli_query($conn, $query);
$reviews = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get average rating
$avg_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total 
              FROM reviews 
              WHERE product_id = $product_id AND is_approved = 1";
$avg_result = mysqli_query($conn, $avg_query);
$avg_data = mysqli_fetch_assoc($avg_result);

echo json_encode([
    'success' => true,
    'reviews' => $reviews,
    'average_rating' => round($avg_data['avg_rating'] ?? 0, 1),
    'total_reviews' => (int)($avg_data['total'] ?? 0)
]);
?>