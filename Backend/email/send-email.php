<?php
// SIMPLE EMAIL FOR DEMO (Using mail() function)
// For production, use PHPMailer

function sendOrderConfirmation($to, $order_number, $total) {
    $subject = "Order Confirmation - Velvet Aura #$order_number";
    $message = "
    <html>
    <head>
        <title>Order Confirmation</title>
    </head>
    <body>
        <h2>Thank you for your order!</h2>
        <p>Dear Customer,</p>
        <p>Your order <strong>#$order_number</strong> has been confirmed.</p>
        <p><strong>Total Amount:</strong> $$total</p>
        <p>We will notify you once your order is shipped.</p>
        <br>
        <p>Thanks,<br>Velvet Aura Team</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: orders@velvetaura.com" . "\r\n";
    
    // For demo, just log it
    error_log("Order confirmation email would be sent to: $to");
    
    // Uncomment for actual email (if mail server configured)
    // mail($to, $subject, $message, $headers);
    
    return true;
}

function sendWelcomeEmail($to, $name) {
    $subject = "Welcome to Velvet Aura!";
    $message = "
    <html>
    <head>
        <title>Welcome to Velvet Aura</title>
    </head>
    <body>
        <h2>Welcome $name!</h2>
        <p>Thank you for joining Velvet Aura.</p>
        <p>Use code <strong>WELCOME15</strong> for 15% off your first order!</p>
        <br>
        <p>Happy Shopping!<br>Velvet Aura Team</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: welcome@velvetaura.com" . "\r\n";
    
    error_log("Welcome email would be sent to: $to");
    // mail($to, $subject, $message, $headers);
    
    return true;
}
?>