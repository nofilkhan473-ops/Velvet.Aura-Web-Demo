<?php
session_start();
require_once 'backend/config/database.php';

// Debug - show errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check login
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get form data
$product_id = (int)$_POST['product_id'];
$user_id = $_SESSION['user_id'];
$rating = (int)$_POST['rating'];
$title = mysqli_real_escape_string($conn, $_POST['title'] ?? '');
$comment = mysqli_real_escape_string($conn, $_POST['comment']);

// Validation
if($rating < 1 || $rating > 5) {
    $_SESSION['review_error'] = "Please select a rating";
    header("Location: product-detail.php?id=" . $product_id);
    exit();
}

if(empty($comment)) {
    $_SESSION['review_error'] = "Please write your review";
    header("Location: product-detail.php?id=" . $product_id);
    exit();
}

// Check existing review
$check = mysqli_query($conn, "SELECT id FROM reviews WHERE user_id = $user_id AND product_id = $product_id");
if(mysqli_num_rows($check) > 0) {
    $_SESSION['review_error'] = "You have already reviewed this product";
    header("Location: product-detail.php?id=" . $product_id);
    exit();
}

// Insert review
$insert = "INSERT INTO reviews (product_id, user_id, rating, title, comment, is_approved, created_at) 
           VALUES ($product_id, $user_id, $rating, '$title', '$comment', 1, NOW())";

if(mysqli_query($conn, $insert)) {
    // Update product average rating
    $update_rating = "UPDATE products p 
                      SET p.rating = (
                          SELECT AVG(rating) 
                          FROM reviews 
                          WHERE product_id = p.id AND is_approved = 1
                      ) 
                      WHERE p.id = $product_id";
    mysqli_query($conn, $update_rating);
    
    $_SESSION['review_success'] = "✅ Review submitted successfully!";
} else {
    $_SESSION['review_error'] = "Error: " . mysqli_error($conn);
}

header("Location: product-detail.php?id=" . $product_id);
exit();
?>