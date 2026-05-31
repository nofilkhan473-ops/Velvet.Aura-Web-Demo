<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity < 1) $quantity = 1;
    
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        mysqli_query($conn, "UPDATE cart SET quantity = $quantity WHERE user_id = $user_id AND product_id = $product_id");
        echo json_encode(['success' => true]);
    } else {
        if (isset($_SESSION['guest_cart'])) {
            foreach ($_SESSION['guest_cart'] as &$item) {
                if ($item['id'] == $product_id) {
                    $item['quantity'] = $quantity;
                    break;
                }
            }
        }
        echo json_encode(['success' => true]);
    }
}
?>