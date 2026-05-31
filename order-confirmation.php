<?php
session_start();
require_once 'backend/config/database.php';
require_once 'backend/includes/functions.php';

$is_logged_in = isLoggedIn();

// Agar login nahi hai toh login page pe bhejo
if (!$is_logged_in) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order = null;

// Pehle database se order fetch karo
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id > 0) {
    $query = "SELECT * FROM orders WHERE id = $order_id AND user_id = $user_id";
    $result = mysqli_query($conn, $query);
    $order = mysqli_fetch_assoc($result);
}

// Agar database mein order nahi mila, toh localStorage wala format use karo
if (!$order && isset($_SESSION['last_order'])) {
    $session_order = $_SESSION['last_order'];
    $order = [
        'order_number' => $session_order['order_number'],
        'created_at' => date('Y-m-d H:i:s'),
        'full_name' => $session_order['full_name'] ?? 'Customer',
        'email' => $session_order['email'] ?? '',
        'phone' => $session_order['phone'] ?? '',
        'address' => $session_order['address'] ?? '',
        'city' => $session_order['city'] ?? '',
        'state' => $session_order['state'] ?? '',
        'zip' => $session_order['zip'] ?? '',
        'country' => $session_order['country'] ?? '',
        'payment_method' => $session_order['payment_method'] ?? 'cod',
        'subtotal' => $session_order['subtotal'] ?? 0,
        'shipping' => $session_order['shipping'] ?? 0,
        'total' => $session_order['total'] ?? 0,
        'order_status' => 'pending'
    ];
    // Clear session after reading
    unset($_SESSION['last_order']);
}

// Agar koi order nahi mila
if (!$order) {
    header('Location: my-orders.php');
    exit();
}

// Get order items from database if order_id exists
$order_items = [];
if ($order_id > 0) {
    $items_query = "SELECT * FROM order_items WHERE order_id = $order_id";
    $items_result = mysqli_query($conn, $items_query);
    if ($items_result) {
        while ($row = mysqli_fetch_assoc($items_result)) {
            $order_items[] = $row;
        }
    }
}

// Get categories for navbar
$categories_query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);
$db_categories = $categories_result ? mysqli_fetch_all($categories_result, MYSQLI_ASSOC) : [];

// Get products for dropdown
$products_query = "SELECT * FROM products WHERE in_stock = 1 ORDER BY created_at DESC LIMIT 6";
$products_result = mysqli_query($conn, $products_query);
$dropdown_products = $products_result ? mysqli_fetch_all($products_result, MYSQLI_ASSOC) : [];

$cart_count = getCartCount();
$wishlist_count = getWishlistCount();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation — Velvet Aura</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* Your existing CSS - keep it exactly the same */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0F0A08; color: #F5E6D3; cursor: none; overflow-x: hidden; }
        
        .confetti-bg { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2; background: linear-gradient(135deg, #0F0A08 0%, #1A0F08 50%, #0F0A08 100%); }
        
        .cursor-dot { width: 8px; height: 8px; background: #D4A574; border-radius: 50%; position: fixed; pointer-events: none; z-index: 99999; }
        .cursor-outline { width: 40px; height: 40px; border: 2px solid #D4A574; border-radius: 50%; position: fixed; pointer-events: none; z-index: 99998; transition: all 0.15s ease; }
        
        .top-bar { background: #5C2E1A; padding: 8px 0; text-align: center; font-size: 11px; letter-spacing: 2px; color: #F5E6D3; }
        
        .navbar { background: rgba(61, 35, 20, 0.95); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(92,46,26,0.3); padding: 0; position: sticky; top: 0; z-index: 1000; }
        .navbar .container { display: flex; justify-content: space-between; align-items: center; padding: 0 40px; flex-wrap: wrap; }
        .navbar-brand { font-family: 'Inter', sans-serif; font-size: 22px; font-weight: 800; letter-spacing: 3px; color: #D4B5A7 !important; text-decoration: none; text-transform: uppercase; }
        .navbar-brand span { color: #F5E6D3; }
        .desktop-nav { display: flex; gap: 40px; margin: 0 auto; }
        .nav-item { position: relative; }
        .nav-link { display: flex; align-items: center; gap: 10px; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #D4B5A7 !important; text-decoration: none; padding: 28px 0; transition: all 0.3s ease; }
        .nav-link:hover { color: #F5E6D3 !important; transform: translateY(-2px); }
        
        .mobile-toggle { display: none; background: transparent; border: none; color: #D4B5A7; font-size: 24px; cursor: pointer; }
        .mobile-menu { display: none; width: 100%; background: rgba(44,24,16,0.95); backdrop-filter: blur(10px); padding: 15px 20px; }
        .mobile-menu.active { display: block; }
        
        .navbar-right { display: flex; gap: 12px; align-items: center; }
        .icon-btn { position: relative; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; color: #D4B5A7; font-size: 18px; border-radius: 50%; text-decoration: none; }
        .icon-btn:hover { color: #F5E6D3; background: rgba(212,165,116,0.1); }
        .badge-count { position: absolute; top: -3px; right: -3px; background: #D4A574; color: #2C1810; font-size: 9px; font-weight: 700; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        
        .confirmation-hero { padding: 60px 0; text-align: center; }
        .success-icon { width: 100px; height: 100px; background: linear-gradient(135deg, #D4A574, #C4956A); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; animation: bounceIn 0.6s ease; }
        @keyframes bounceIn { 0% { opacity: 0; transform: scale(0.3); } 50% { opacity: 1; transform: scale(1.05); } 100% { transform: scale(1); } }
        .success-icon i { font-size: 50px; color: #2C1810; }
        .confirmation-hero h1 { font-size: 48px; font-weight: 800; margin-bottom: 15px; background: linear-gradient(135deg, #F5E6D3, #D4A574); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .order-number { font-size: 24px; font-weight: 700; color: #D4A574; letter-spacing: 2px; }
        
        .email-card { background: #FFF9F0; border-radius: 32px; max-width: 700px; margin: 0 auto; box-shadow: 0 25px 50px rgba(0,0,0,0.2); overflow: hidden; margin-bottom: 60px; }
        .email-header { background: linear-gradient(135deg, #D4A574, #C4956A); padding: 30px; text-align: center; }
        .email-header h2 { color: #2C1810; font-size: 24px; font-weight: 800; }
        .email-header p { color: #3D2314; font-size: 13px; }
        .email-body { padding: 30px; color: #2C1810; }
        .greeting { font-size: 16px; margin-bottom: 20px; color: #3D2314; border-left: 3px solid #D4A574; padding-left: 15px; }
        .order-details-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .order-details-table th { background: #F0E8DF; padding: 12px; text-align: left; font-size: 13px; color: #3D2314; }
        .order-details-table td { padding: 12px; border-bottom: 1px solid #E8DCCF; color: #5A3A2A; font-size: 13px; }
        .shipping-info { background: #F8F4EE; padding: 20px; border-radius: 16px; margin: 20px 0; }
        .shipping-info h4 { font-size: 14px; font-weight: 700; margin-bottom: 10px; color: #5C2E1A; }
        .email-footer { background: #F0E8DF; padding: 25px; text-align: center; border-top: 1px solid #E8DCCF; }
        .btn-shop { background: #5C2E1A; color: #F5E6D3; padding: 12px 30px; border-radius: 50px; text-decoration: none; font-weight: 700; display: inline-block; transition: all 0.3s; }
        .btn-shop:hover { background: #3D2314; transform: translateY(-2px); }
        
        .footer { background: linear-gradient(135deg, #0F0A08 0%, #1A0F08 100%); padding: 60px 0 0; border-top: 1px solid rgba(92,46,26,0.2); margin-top: 60px; }
        .footer-bottom { padding: 25px 0; border-top: 1px solid rgba(92,46,26,0.15); text-align: center; }
        .copyright { font-size: 11px; color: #8B6B4A; }
        
        @media (max-width: 992px) { .desktop-nav { display: none; } .mobile-toggle { display: block; } .navbar .container { padding: 10px 20px; } .navbar-right { margin-left: auto; margin-right: 15px; } }
        @media (max-width: 768px) { .confirmation-hero h1 { font-size: 28px; } .email-body { padding: 20px; } }
    </style>
</head>
<body>

<div class="confetti-bg"></div>
<div class="cursor-dot"></div>
<div class="cursor-outline"></div>

<div class="top-bar">✨ FREE SHIPPING ON ORDERS OVER $100 ✦ ETHICAL FASHION ✦ 30-DAY RETURNS ✨</div>

<nav class="navbar">
    <div class="container">
        <a class="navbar-brand" href="index.php">VELVET<span>.</span>AURA</a>
        <div class="desktop-nav">
            <div class="nav-item"><a href="index.php" class="nav-link">HOME</a></div>
            <div class="nav-item"><a href="shop.php" class="nav-link">SHOP</a></div>
            <div class="nav-item"><a href="lookbook.php" class="nav-link">LOOKBOOK</a></div>
            <div class="nav-item"><a href="about.php" class="nav-link">ABOUT</a></div>
            <?php if($is_logged_in): ?><div class="nav-item"><a href="my-orders.php" class="nav-link">MY ORDERS</a></div><?php endif; ?>
        </div>
        <button class="mobile-toggle" id="mobileToggle"><i class="fas fa-bars"></i></button>
        <div class="navbar-right">
            <a href="wishlist.php" class="icon-btn"><i class="far fa-heart"></i><span class="badge-count"><?php echo $wishlist_count; ?></span></a>
            <a href="cart.php" class="icon-btn"><i class="fas fa-shopping-bag"></i><span class="badge-count"><?php echo $cart_count; ?></span></a>
            <?php if($is_logged_in): ?><a href="logout.php" class="icon-btn"><i class="fas fa-sign-out-alt"></i></a><?php else: ?><a href="login.php" class="icon-btn"><i class="far fa-user"></i></a><?php endif; ?>
        </div>
        <div class="mobile-menu" id="mobileMenu">
            <a href="index.php" class="mobile-nav-link">HOME</a>
            <a href="shop.php" class="mobile-nav-link">SHOP</a>
            <a href="lookbook.php" class="mobile-nav-link">LOOKBOOK</a>
            <a href="about.php" class="mobile-nav-link">ABOUT</a>
        </div>
    </div>
</nav>

<section class="confirmation-hero">
    <div class="container">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        <h1>Thank You for Your Order! 🎉</h1>
        <div class="order-number">
            <i class="fas fa-receipt"></i> Order #<?php echo htmlspecialchars($order['order_number']); ?>
        </div>
    </div>
</section>

<section style="padding: 40px 0 80px;">
    <div class="container">
        <div class="email-card">
            <div class="email-header">
                <h2>✨ Order Confirmation ✨</h2>
                <p>Thank you for shopping with Velvet Aura</p>
            </div>
            <div class="email-body">
                <div class="greeting">
                    <i class="fas fa-envelope"></i> Dear <strong><?php echo htmlspecialchars(explode(' ', $order['full_name'])[0] ?? 'Valued Customer'); ?></strong>,<br>
                    Thank you for shopping with Velvet Aura! Your order has been successfully placed.
                </div>
                
                <table class="order-details-table">
                    <tr><th colspan="2">📋 Order Details</th></tr>
                    <tr><td style="width:40%;">Order Number:</td><td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td></tr>
                    <tr><td>Order Date:</td><td><?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></td></tr>
                    <tr><td>Payment Method:</td><td><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></td></tr>
                    <tr><td>Email:</td><td><?php echo htmlspecialchars($order['email']); ?></td></tr>
                    <tr><td>Phone:</td><td><?php echo htmlspecialchars($order['phone']); ?></td></tr>
                </table>
                
                <table class="order-details-table">
                    <tr><th>Product</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Price</th><th style="text-align:right;">Total</th></tr>
                    <?php 
                    if (!empty($order_items)) {
                        foreach ($order_items as $item) { 
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td style="text-align:center;"><?php echo $item['quantity']; ?></td>
                            <td style="text-align:right;">$<?php echo number_format($item['product_price'], 2); ?></td>
                            <td style="text-align:right;">$<?php echo number_format($item['total'], 2); ?></td>
                        </tr>
                    <?php 
                        }
                    } else {
                        // Fallback agar items nahi mile
                    ?>
                        <tr><td colspan="4" style="text-align:center;">Order items will be updated soon</td></tr>
                    <?php } ?>
                    <tr style="border-top:1px solid #E8DCCF;">
                        <td colspan="3" style="text-align:right;"><strong>Subtotal:</strong></td>
                        <td style="text-align:right;">$<?php echo number_format($order['subtotal'], 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align:right;"><strong>Shipping:</strong></td>
                        <td style="text-align:right;"><?php echo $order['shipping'] == 0 ? 'Free' : '$' . number_format($order['shipping'], 2); ?></td>
                    </tr>
                    <tr style="background:#F8F4EE;">
                        <td colspan="3" style="text-align:right;"><strong>Total:</strong></td>
                        <td style="text-align:right; font-size:18px; font-weight:800; color:#5C2E1A;">$<?php echo number_format($order['total'], 2); ?></td>
                    </tr>
                </table>
                
                <div class="shipping-info">
                    <h4><i class="fas fa-truck"></i> Shipping Information</h4>
                    <p><?php echo htmlspecialchars($order['address']); ?></p>
                    <p><?php echo htmlspecialchars($order['city']) . ', ' . htmlspecialchars($order['state']) . ' ' . htmlspecialchars($order['zip']); ?></p>
                    <p><?php echo htmlspecialchars($order['country']); ?></p>
                    <p><strong>Estimated Delivery:</strong> 3-5 business days</p>
                    <p><strong>Tracking:</strong> You will receive tracking details via email once shipped.</p>
                </div>
                
                <div style="background:#F0E8DF; border-radius:12px; padding:15px; margin-top:15px; text-align:center;">
                    <p style="margin:0; font-size:12px; color:#5A3A2A;">
                        <i class="fas fa-envelope"></i> A confirmation email has been sent to your email address.<br>
                        For any queries, contact us at <strong>hello@velvetaura.com</strong>
                    </p>
                </div>
            </div>
            <div class="email-footer">
                <p>❤️ Thank you for choosing Velvet Aura! ❤️</p>
                <a href="my-orders.php" class="btn-shop" style="margin-right:10px;"><i class="fas fa-box"></i> View My Orders</a>
                <a href="shop.php" class="btn-shop">Continue Shopping →</a>
            </div>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <div class="footer-bottom">
            <div class="copyright"><i class="far fa-copyright"></i> <?php echo date('Y'); ?> Velvet Aura. All rights reserved.</div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: true });
    
    // Custom cursor
    const cursorDot = document.querySelector('.cursor-dot');
    const cursorOutline = document.querySelector('.cursor-outline');
    if (cursorDot && cursorOutline) {
        window.addEventListener('mousemove', function(e) {
            cursorDot.style.transform = `translate(${e.clientX - 4}px, ${e.clientY - 4}px)`;
            cursorOutline.style.transform = `translate(${e.clientX - 20}px, ${e.clientY - 20}px)`;
        });
    }
    
    // Mobile menu
    const mobileToggle = document.getElementById('mobileToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    if (mobileToggle && mobileMenu) {
        mobileToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');
        });
    }
</script>
</body>
</html>