<?php
session_start();

// Admin check
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != true) {
    header('Location: ../login.php');
    exit();
}

require_once '../backend/config/database.php';

$id = (int)$_GET['id'];

// Get image path to delete
$product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM products WHERE id = $id"));
if($product && $product['image'] && file_exists('../assets/images/' . $product['image'])) {
    unlink('../assets/images/' . $product['image']);
}

// Delete product
mysqli_query($conn, "DELETE FROM products WHERE id = $id");

header('Location: products.php?msg=deleted');
exit();
?>