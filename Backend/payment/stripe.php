<?php
// Demo Mode - For Presentation Tomorrow
// This simulates payment processing

session_start();
require_once '../../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = $_POST['payment_method'] ?? 'card';
    $amount = $_POST['amount'] ?? 0;
    
    // SIMULATE PAYMENT SUCCESS (for demo)
    // In real scenario, integrate Stripe API here
    
    $response = [
        'success' => true,
        'message' => 'Payment processed successfully (Demo Mode)',
        'transaction_id' => 'DEMO-' . time() . '-' . rand(1000, 9999)
    ];
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
