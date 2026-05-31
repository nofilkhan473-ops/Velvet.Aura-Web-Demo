<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

$user_id = $_SESSION['user_id'];
$order_number = 'VA-' . date('Ymd') . '-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);

$first_name = mysqli_real_escape_string($conn, $data['first_name']);
$last_name = mysqli_real_escape_string($conn, $data['last_name']);
$email = mysqli_real_escape_string($conn, $data['email']);
$phone = mysqli_real_escape_string($conn, $data['phone']);
$address = mysqli_real_escape_string($conn, $data['address']);
$city = mysqli_real_escape_string($conn, $data['city']);
$state = mysqli_real_escape_string($conn, $data['state']);
$zip = mysqli_real_escape_string($conn, $data['zip']);
$country = mysqli_real_escape_string($conn, $data['country']);
$notes = isset($data['notes']) ? mysqli_real_escape_string($conn, $data['notes']) : '';
$payment_method = mysqli_real_escape_string($conn, $data['payment_method']);
$subtotal = floatval($data['subtotal']);
$shipping = floatval($data['shipping']);
$total = floatval($data['total']);

$order_query = "INSERT INTO orders (order_number, user_id, full_name, email, phone, address, city, state, zip, country, subtotal, shipping, total, payment_method, order_status, created_at) 
                VALUES ('$order_number', $user_id, '$first_name $last_name', '$email', '$phone', '$address', '$city', '$state', '$zip', '$country', $subtotal, $shipping, $total, '$payment_method', 'pending', NOW())";

if (mysqli_query($conn, $order_query)) {
    $order_id = mysqli_insert_id($conn);
    
    $items = $data['items'];
    foreach ($items as $item) {
        $product_id = intval($item['id']);
        $product_name = mysqli_real_escape_string($conn, $item['name']);
        $product_price = floatval($item['price']);
        $quantity = intval($item['quantity']);
        $item_total = $product_price * $quantity;
        
        $item_query = "INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, total) 
                       VALUES ($order_id, $product_id, '$product_name', $product_price, $quantity, $item_total)";
        mysqli_query($conn, $item_query);
    }
    
    // Clear cart
    mysqli_query($conn, "DELETE FROM cart WHERE user_id = $user_id");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Order placed successfully!',
        'order_id' => $order_id,
        'order_number' => $order_number
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . mysqli_error($conn)
    ]);
}
?>