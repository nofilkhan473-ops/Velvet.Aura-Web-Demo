<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        mysqli_query($conn, "DELETE FROM cart WHERE user_id = $user_id");
    } else {
        unset($_SESSION['guest_cart']);
    }
    
    echo json_encode(['success' => true]);
}
?>