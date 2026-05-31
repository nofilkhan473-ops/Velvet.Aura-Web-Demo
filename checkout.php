<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'backend/config/database.php';
require_once 'backend/includes/functions.php';

$is_logged_in = isLoggedIn();

// Agar user login nahi hai to login page pe bhejo
if (!$is_logged_in) {
    header('Location: login.php?redirect=checkout.php');
    exit();
}

$user_id = $_SESSION['user_id'];

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

// Get cart items from database
$cart_items = [];
$subtotal = 0;

$cart_query = "SELECT c.*, p.name, p.price, p.image, p.old_price 
               FROM cart c 
               JOIN products p ON c.product_id = p.id 
               WHERE c.user_id = $user_id";
$cart_result = mysqli_query($conn, $cart_query);

if ($cart_result && mysqli_num_rows($cart_result) > 0) {
    while ($row = mysqli_fetch_assoc($cart_result)) {
        $cart_items[] = [
            'product_id' => $row['product_id'],
            'name' => $row['name'],
            'price' => floatval($row['price']),
            'quantity' => intval($row['quantity']),
            'image' => $row['image'],
            'total' => floatval($row['price']) * intval($row['quantity'])
        ];
        $subtotal += floatval($row['price']) * intval($row['quantity']);
    }
}

$shipping = $subtotal > 100 ? 0 : 10;
$total = $subtotal + $shipping;

// Agar cart empty hai to cart page pe redirect karo
if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}

// Handle form submission - DATABASE INSERT
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $full_name = $first_name . ' ' . $last_name;
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $state = mysqli_real_escape_string($conn, $_POST['state']);
    $zip = mysqli_real_escape_string($conn, $_POST['zip']);
    $country = mysqli_real_escape_string($conn, $_POST['country']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    
    // Generate unique order number
    $order_number = 'VA-' . strtoupper(uniqid()) . '-' . rand(100, 999);
    
    // Insert into orders table
    $insert_order = "INSERT INTO orders (order_number, user_id, full_name, email, phone, address, city, state, zip, country, subtotal, shipping, total, payment_method, order_status, notes, created_at) 
                     VALUES ('$order_number', $user_id, '$full_name', '$email', '$phone', '$address', '$city', '$state', '$zip', '$country', $subtotal, $shipping, $total, '$payment_method', 'pending', '$notes', NOW())";
    
    if (mysqli_query($conn, $insert_order)) {
        $order_id = mysqli_insert_id($conn);
        
        // Insert order items
        foreach ($cart_items as $item) {
            $product_id = $item['product_id'];
            $product_name = mysqli_real_escape_string($conn, $item['name']);
            $product_price = $item['price'];
            $quantity = $item['quantity'];
            $item_total = $item['total'];
            
            $insert_item = "INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, total) 
                           VALUES ($order_id, $product_id, '$product_name', $product_price, $quantity, $item_total)";
            mysqli_query($conn, $insert_item);
        }
        
        // Clear the cart
        $clear_cart = "DELETE FROM cart WHERE user_id = $user_id";
        mysqli_query($conn, $clear_cart);
        
        // Store in session for confirmation page
        $_SESSION['last_order'] = [
            'order_id' => $order_id,
            'order_number' => $order_number,
            'total' => $total
        ];
        
        // Redirect to confirmation page
        header("Location: order-confirmation.php?order_id=" . $order_id);
        exit();
        
    } else {
        $error_message = "Failed to place order: " . mysqli_error($conn);
    }
}

// Encode cart items for JavaScript
$cart_items_json = json_encode($cart_items);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — Velvet Aura</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* Your existing CSS here - same as before */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0F0A08; color: #F5E6D3; overflow-x: hidden; }
        .bg-animation { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2; background: linear-gradient(135deg, #0F0A08 0%, #1A0F08 50%, #0F0A08 100%); }
        .orb { position: absolute; border-radius: 50%; filter: blur(60px); opacity: 0.4; animation: float 20s infinite ease-in-out; }
        .orb-1 { width: 300px; height: 300px; background: radial-gradient(circle, #5C2E1A, transparent); top: 10%; left: -100px; }
        .orb-2 { width: 400px; height: 400px; background: radial-gradient(circle, #D4A574, transparent); bottom: -150px; right: -150px; }
        @keyframes float { 0%,100% { transform: translate(0,0) scale(1); } 50% { transform: translate(50px,-50px) scale(1.1); } }
        .top-bar { background: #5C2E1A; padding: 8px 0; text-align: center; font-size: 11px; letter-spacing: 2px; color: #F5E6D3; }
        .navbar { background: rgba(61, 35, 20, 0.95); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(92,46,26,0.3); padding: 0; position: sticky; top: 0; z-index: 1000; }
        .navbar .container { display: flex; justify-content: space-between; align-items: center; padding: 0 40px; flex-wrap: wrap; }
        .navbar-brand { font-family: 'Inter', sans-serif; font-size: 22px; font-weight: 800; letter-spacing: 3px; color: #D4B5A7 !important; text-decoration: none; text-transform: uppercase; }
        .navbar-brand span { color: #F5E6D3; }
        .desktop-nav { display: flex; gap: 40px; margin: 0 auto; }
        .nav-item { position: relative; }
        .nav-link { display: flex; align-items: center; gap: 10px; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #D4B5A7 !important; text-decoration: none; padding: 28px 0; }
        .nav-link:hover { color: #F5E6D3 !important; transform: translateY(-2px); }
        .mobile-toggle { display: none; background: transparent; border: none; color: #D4B5A7; font-size: 24px; cursor: pointer; }
        .mobile-menu { display: none; width: 100%; background: rgba(44,24,16,0.95); backdrop-filter: blur(10px); padding: 15px 20px; }
        .mobile-menu.active { display: block; }
        .navbar-right { display: flex; gap: 12px; align-items: center; }
        .icon-btn { position: relative; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; color: #D4B5A7; font-size: 18px; border-radius: 50%; text-decoration: none; }
        .icon-btn:hover { color: #F5E6D3; background: rgba(212,165,116,0.1); }
        .badge-count { position: absolute; top: -3px; right: -3px; background: #D4A574; color: #2C1810; font-size: 9px; font-weight: 700; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .checkout-hero { background: linear-gradient(135deg, #3D2314 0%, #2C1810 100%); padding: 60px 0; text-align: center; }
        .checkout-hero h1 { font-size: 56px; font-weight: 800; margin-bottom: 15px; color: #F5E6D3; }
        .checkout-section { padding: 80px 0; background: #2C1810; }
        .checkout-form { background: rgba(61, 35, 20, 0.8); backdrop-filter: blur(10px); border-radius: 32px; padding: 40px; border: 1px solid rgba(212,165,116,0.2); }
        .checkout-form h3 { font-size: 22px; font-weight: 800; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid rgba(212,165,116,0.2); color: #F5E6D3; }
        .form-group { margin-bottom: 22px; }
        .form-group label { font-weight: 600; margin-bottom: 8px; display: block; font-size: 13px; color: #D4B5A7; text-transform: uppercase; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 14px 18px; border: 1px solid rgba(212,165,116,0.2); border-radius: 16px; font-size: 14px; background: rgba(15,10,8,0.6); color: #F5E6D3; }
        .form-group input:focus, .form-group select:focus { border-color: #D4A574; outline: none; }
        .payment-methods { display: flex; gap: 15px; flex-wrap: wrap; margin-top: 15px; }
        .payment-method { display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 15px 25px; background: rgba(15,10,8,0.6); border-radius: 60px; border: 1px solid rgba(212,165,116,0.2); }
        .payment-method:hover { background: #3D2314; border-color: #D4A574; }
        .payment-method input { accent-color: #D4A574; width: 18px; height: 18px; }
        .payment-method i { font-size: 22px; color: #D4A574; }
        .order-summary { background: rgba(61, 35, 20, 0.8); backdrop-filter: blur(10px); border-radius: 32px; padding: 35px; position: sticky; top: 100px; border: 1px solid rgba(212,165,116,0.2); }
        .cart-item { display: flex; gap: 15px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid rgba(212,165,116,0.1); }
        .cart-item-img { width: 70px; height: 70px; object-fit: cover; border-radius: 16px; background: #2C1810; }
        .cart-item-info h5 { font-size: 15px; font-weight: 700; color: #F5E6D3; }
        .cart-item-total { font-weight: 700; color: #D4A574; }
        .summary-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(212,165,116,0.1); color: #D4B5A7; }
        .summary-row.total { font-size: 22px; font-weight: 800; border-bottom: none; padding-top: 20px; color: #D4A574; }
        .btn-place-order { width: 100%; background: linear-gradient(135deg, #D4A574, #C4956A); color: #2C1810; padding: 16px; border: none; border-radius: 60px; font-weight: 800; font-size: 14px; cursor: pointer; margin-top: 25px; }
        .btn-place-order:hover { transform: translateY(-3px); background: #F5E6D3; }
        .alert-danger { background: rgba(220,53,69,0.2); color: #dc3545; padding: 12px 20px; border-radius: 12px; margin-bottom: 20px; }
        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); display: flex; align-items: center; justify-content: center; z-index: 10000; opacity: 0; visibility: hidden; }
        .loading-overlay.active { opacity: 1; visibility: visible; }
        .spinner i { font-size: 50px; color: #D4A574; animation: spin 1s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
        .footer { background: linear-gradient(135deg, #0F0A08 0%, #1A0F08 100%); padding: 60px 0 0; border-top: 1px solid rgba(92,46,26,0.2); margin-top: 60px; }
        .footer-bottom { padding: 25px 0; text-align: center; }
        @media (max-width: 992px) { .desktop-nav { display: none; } .mobile-toggle { display: block; } .order-summary { margin-top: 30px; position: static; } }
        @media (max-width: 768px) { .checkout-hero h1 { font-size: 36px; } .checkout-form { padding: 25px; } }
    </style>
</head>
<body>

<div class="bg-animation"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

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

<section class="checkout-hero">
    <div class="container">
        <h1 data-aos="fade-up">Complete Your Order</h1>
        <p data-aos="fade-up" data-aos-delay="100">Secure checkout • Fast delivery • 100% satisfaction</p>
    </div>
</section>

<section class="checkout-section">
    <div class="container">
        <?php if($error_message): ?>
            <div class="alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="row" data-aos="fade-up">
            <div class="col-lg-7">
                <div class="checkout-form">
                    <h3><i class="fas fa-truck-moving"></i> Shipping Information</h3>
                    <form method="POST" id="checkoutForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-user"></i> First Name</label>
                                    <input type="text" name="first_name" id="firstName" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-user"></i> Last Name</label>
                                    <input type="text" name="last_name" id="lastName" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-envelope"></i> Email</label>
                                    <input type="email" name="email" id="email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-phone"></i> Phone</label>
                                    <input type="tel" name="phone" id="phone" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-location-dot"></i> Address</label>
                            <input type="text" name="address" id="address" required>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="fas fa-city"></i> City</label>
                                    <input type="text" name="city" id="city" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="fas fa-map-marker-alt"></i> State</label>
                                    <input type="text" name="state" id="state" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="fas fa-mail-bulk"></i> ZIP</label>
                                    <input type="text" name="zip" id="zip" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-globe"></i> Country</label>
                            <select name="country" id="country" required>
                                <option value="">Select Country</option>
                                <option value="Pakistan">Pakistan</option>
                                <option value="United States">United States</option>
                                <option value="United Kingdom">United Kingdom</option>
                                <option value="Canada">Canada</option>
                                <option value="Australia">Australia</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-pen"></i> Order Notes</label>
                            <textarea name="notes" id="notes" rows="3"></textarea>
                        </div>
                        
                        <h3><i class="fas fa-credit-card"></i> Payment Method</h3>
                        <div class="payment-methods">
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="cod" checked>
                                <i class="fas fa-money-bill-wave"></i><span>Cash on Delivery</span>
                            </label>
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="credit_card">
                                <i class="fas fa-credit-card"></i><span>Credit / Debit Card</span>
                            </label>
                        </div>
                        
                        <input type="hidden" name="place_order" value="1">
                    </form>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="order-summary">
                    <h3><i class="fas fa-receipt"></i> Your Order</h3>
                    <div id="orderItems"></div>
                    <div class="summary-row"><span>Subtotal</span><span id="summarySubtotal">$0.00</span></div>
                    <div class="summary-row"><span>Shipping</span><span id="summaryShipping">Free</span></div>
                    <div class="summary-row total"><span>Total</span><span id="summaryTotal">$0.00</span></div>
                    <button type="submit" form="checkoutForm" class="btn-place-order">Place Order →</button>
                </div>
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

<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"><i class="fas fa-spinner fa-spin"></i><p style="color:#F5E6D3; margin-top:15px;">Placing your order...</p></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: true });
    
    let cartItems = <?php echo $cart_items_json; ?>;
    let subtotal = <?php echo $subtotal; ?>;
    let shipping = <?php echo $shipping; ?>;
    let total = <?php echo $total; ?>;
    
    // Mobile menu
    const mobileToggle = document.getElementById('mobileToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    if (mobileToggle && mobileMenu) {
        mobileToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');
        });
    }
    
    function loadCheckout() {
        let html = '';
        for (let item of cartItems) {
            html += `
                <div class="cart-item">
                    <img src="assets/images/${item.image}" class="cart-item-img" onerror="this.src='https://placehold.co/70x70/2C1810/D4B5A7?text=VA'">
                    <div class="cart-item-info">
                        <h5>${escapeHtml(item.name)}</h5>
                        <p>Qty: ${item.quantity} × $${item.price.toFixed(2)}</p>
                    </div>
                    <div class="cart-item-total">$${(item.price * item.quantity).toFixed(2)}</div>
                </div>
            `;
        }
        document.getElementById('orderItems').innerHTML = html;
        document.getElementById('summarySubtotal').textContent = `$${subtotal.toFixed(2)}`;
        document.getElementById('summaryShipping').textContent = shipping === 0 ? 'Free' : `$${shipping.toFixed(2)}`;
        document.getElementById('summaryTotal').textContent = `$${total.toFixed(2)}`;
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/[&<>]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;'})[m] || m);
    }
    
    // Show loading on form submit
    document.getElementById('checkoutForm')?.addEventListener('submit', function() {
        document.getElementById('loadingOverlay').classList.add('active');
    });
    
    loadCheckout();
</script>
</body>
</html>