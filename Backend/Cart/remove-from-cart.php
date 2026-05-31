<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = (int)$_POST['product_id'];
    
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        mysqli_query($conn, "DELETE FROM cart WHERE user_id = $user_id AND product_id = $product_id");
        echo json_encode(['success' => true]);
    } else {
        if (isset($_SESSION['guest_cart'])) {
            foreach ($_SESSION['guest_cart'] as $key => $item) {
                if ($item['id'] == $product_id) {
                    unset($_SESSION['guest_cart'][$key]);
                    break;
                }
            }
            $_SESSION['guest_cart'] = array_values($_SESSION['guest_cart']);
        }
        echo json_encode(['success' => true]);
    }
}
?>