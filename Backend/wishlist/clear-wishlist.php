<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Delete from wishlist
$query = "DELETE FROM wishlist WHERE user_id = " . (int)$user_id;

if (mysqli_query($conn, $query)) {
    // Get remaining count
    $countQuery = "SELECT COUNT(*) as total FROM wishlist WHERE user_id = " . (int)$user_id;
    $countResult = mysqli_query($conn, $countQuery);
    $countRow = mysqli_fetch_assoc($countResult);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Wishlist cleared successfully',
        'wishlist_count' => $countRow['total']
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . mysqli_error($conn)
    ]);
}
?>