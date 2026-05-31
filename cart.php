<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'backend/config/database.php';
require_once 'backend/includes/functions.php';

$is_logged_in = isLoggedIn();

// Get categories for navbar
$categories_query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);
$db_categories = $categories_result ? mysqli_fetch_all($categories_result, MYSQLI_ASSOC) : [];

// Get products for dropdown
$products_query = "SELECT * FROM products WHERE in_stock = 1 ORDER BY created_at DESC LIMIT 6";
$products_result = mysqli_query($conn, $products_query);
$dropdown_products = $products_result ? mysqli_fetch_all($products_result, MYSQLI_ASSOC) : [];

// Get cart items from database if logged in
$cart_items = [];
$subtotal = 0;

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $cart_query = "SELECT c.*, p.name, p.price, p.image, p.old_price 
                   FROM cart c 
                   JOIN products p ON c.product_id = p.id 
                   WHERE c.user_id = $user_id";
    $cart_result = mysqli_query($conn, $cart_query);
    if ($cart_result) {
        $cart_items = mysqli_fetch_all($cart_result, MYSQLI_ASSOC);
        foreach ($cart_items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
    }
}

$shipping = $subtotal > 100 ? 0 : 10;
$total = $subtotal + $shipping;
$cart_count = getCartCount();
$wishlist_count = getWishlistCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart — Velvet Aura</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #2C1810; color: #F5E6D3; cursor: none; overflow-x: hidden; }
        
        .cursor-dot { width: 8px; height: 8px; background: #5C2E1A; border-radius: 50%; position: fixed; pointer-events: none; z-index: 99999; }
        .cursor-outline { width: 40px; height: 40px; border: 2px solid #5C2E1A; border-radius: 50%; position: fixed; pointer-events: none; z-index: 99998; transition: all 0.15s ease; }
        
        .top-bar { background: #5C2E1A; padding: 8px 0; text-align: center; font-size: 11px; letter-spacing: 2px; color: #F5E6D3; text-transform: uppercase; font-weight: 500; }
        
        .navbar { background: #3D2314; border-bottom: 1px solid rgba(92,46,26,0.3); padding: 0; position: sticky; top: 0; z-index: 1000; }
        .navbar .container { display: flex; justify-content: space-between; align-items: center; padding: 0 40px; flex-wrap: wrap; }
        .navbar-brand { font-family: 'Inter', sans-serif; font-size: 22px; font-weight: 800; letter-spacing: 3px; color: #D4B5A7 !important; text-decoration: none; text-transform: uppercase; }
        .navbar-brand span { color: #F5E6D3; }
        .desktop-nav { display: flex; gap: 40px; margin: 0 auto; }
        .nav-item { position: relative; }
        .nav-link { display: flex; align-items: center; gap: 10px; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #D4B5A7 !important; text-decoration: none; padding: 28px 0; transition: all 0.3s ease; }
        .nav-link i { font-size: 14px; }
        .nav-link:hover { color: #F5E6D3 !important; transform: translateY(-2px); }
        .nav-link::after { content: ''; position: absolute; bottom: 20px; left: 0; width: 0; height: 2px; background: #D4B5A7; transition: width 0.3s ease; }
        .nav-link:hover::after, .nav-link.active::after { width: 100%; }
        
        .dropdown { position: absolute; top: 100%; left: 0; background: #2C1810; min-width: 240px; border-radius: 12px; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.3s ease; z-index: 100; border: 1px solid rgba(92,46,26,0.3); box-shadow: 0 15px 30px rgba(0,0,0,0.3); }
        .nav-item:hover .dropdown { opacity: 1; visibility: visible; transform: translateY(0); }
        .dropdown a { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #D4B5A7; text-decoration: none; font-size: 12px; transition: all 0.3s ease; }
        .dropdown a i { width: 20px; font-size: 12px; }
        .dropdown a:hover { background: #3D2314; color: #F5E6D3; padding-left: 28px; }
        .dropdown-divider { height: 1px; background: rgba(92,46,26,0.2); margin: 5px 0; }
        .dropdown-header { padding: 10px 20px; font-size: 10px; font-weight: 700; letter-spacing: 1px; color: #D4B5A7; text-transform: uppercase; background: #2C1810; border-radius: 12px 12px 0 0; }
        
        .dropdown-products { width: 520px; padding: 15px; }
        .dropdown-products .product-item { display: flex; align-items: center; gap: 10px; padding: 8px; border-radius: 8px; transition: all 0.3s; text-decoration: none; }
        .dropdown-products .product-item:hover { background: #3D2314; transform: translateX(5px); }
        .dropdown-products img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
        .dropdown-products .product-name { font-size: 12px; font-weight: 600; color: #D4B5A7; }
        .dropdown-products .product-price { font-size: 11px; color: #F5E6D3; }
        
        .mobile-toggle { display: none; background: transparent; border: none; color: #D4B5A7; font-size: 24px; cursor: pointer; padding: 10px; }
        .mobile-menu { display: none; width: 100%; background: #2C1810; border-top: 1px solid rgba(92,46,26,0.2); padding: 15px 20px; }
        .mobile-menu.active { display: block; }
        .mobile-nav-item { border-bottom: 1px solid rgba(92,46,26,0.2); }
        .mobile-nav-link { display: flex; align-items: center; gap: 12px; padding: 14px 0; color: #D4B5A7; text-decoration: none; font-size: 13px; font-weight: 600; }
        .mobile-nav-link i { width: 24px; }
        .mobile-dropdown-toggle { margin-left: auto; cursor: pointer; transition: transform 0.3s; }
        .mobile-dropdown-toggle.rotated { transform: rotate(180deg); }
        .mobile-dropdown { display: none; padding-left: 36px; padding-bottom: 10px; }
        .mobile-dropdown.active { display: block; }
        .mobile-dropdown a { display: flex; align-items: center; gap: 12px; padding: 10px 0; color: #C4A484; text-decoration: none; font-size: 12px; }
        .mobile-product-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 10px 0; }
        .mobile-product-item { display: flex; align-items: center; gap: 8px; padding: 6px; background: #3D2314; border-radius: 8px; text-decoration: none; }
        .mobile-product-item img { width: 35px; height: 35px; object-fit: cover; border-radius: 6px; }
        
        .navbar-right { display: flex; gap: 12px; align-items: center; }
        .icon-btn { position: relative; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; color: #D4B5A7; font-size: 18px; transition: all 0.3s; border-radius: 50%; text-decoration: none; }
        .icon-btn:hover { color: #F5E6D3; background: rgba(92,46,26,0.2); transform: translateY(-2px); }
        .badge-count { position: absolute; top: -3px; right: -3px; background: #5C2E1A; color: #F5E6D3; font-size: 9px; font-weight: 700; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        
        .page-header { background: linear-gradient(135deg, #3D2314 0%, #2C1810 100%); padding: 80px 0; text-align: center; position: relative; overflow: hidden; }
        .page-header::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(92,46,26,0.15) 0%, transparent 70%); animation: rotate 20s linear infinite; }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .page-header h1 { font-size: 56px; font-weight: 800; margin-bottom: 15px; color: #F5E6D3; animation: fadeInUp 0.8s ease; }
        .page-header p { color: #D4B5A7; font-size: 18px; animation: fadeInUp 1s ease; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        
        .cart-section { padding: 80px 0; min-height: 60vh; background: #2C1810; }
        .cart-table { width: 100%; border-collapse: collapse; background: #3D2314; border-radius: 24px; overflow: hidden; border: 1px solid rgba(92,46,26,0.3); }
        .cart-table th { text-align: left; padding: 18px; background: #2C1810; font-weight: 700; color: #D4B5A7; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; border-bottom: 1px solid rgba(92,46,26,0.3); }
        .cart-table td { padding: 18px; border-bottom: 1px solid rgba(92,46,26,0.2); vertical-align: middle; color: #F5E6D3; }
        .cart-product-img { width: 80px; height: 80px; object-fit: cover; border-radius: 16px; background: #2C1810; }
        .quantity-btn { width: 32px; height: 32px; border: 1px solid #5C2E1A; background: #2C1810; color: #D4B5A7; border-radius: 50%; cursor: pointer; transition: all 0.3s; font-size: 16px; font-weight: bold; }
        .quantity-btn:hover { background: #5C2E1A; color: #F5E6D3; }
        .btn-remove { background: none; border: none; color: #ff6b6b; cursor: pointer; transition: all 0.3s; font-size: 16px; }
        .btn-remove:hover { color: #ff4444; transform: scale(1.1); }
        
        .cart-summary { background: #3D2314; border-radius: 24px; padding: 30px; border: 1px solid rgba(92,46,26,0.3); transition: all 0.3s; position: sticky; top: 100px; }
        .cart-summary:hover { border-color: #5C2E1A; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .cart-summary h3 { font-size: 20px; font-weight: 800; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid rgba(92,46,26,0.3); color: #F5E6D3; }
        .summary-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(92,46,26,0.2); color: #D4B5A7; }
        .summary-row.total { font-size: 20px; font-weight: 800; border-bottom: none; padding-top: 15px; color: #d08f72; }
        .btn-checkout { width: 100%; background: #5C2E1A; color: #F5E6D3; padding: 14px; border: none; border-radius: 50px; font-weight: 700; font-size: 14px; cursor: pointer; transition: all 0.3s; letter-spacing: 2px; margin-top: 15px; text-decoration: none; display: block; text-align: center; }
        .btn-checkout:hover { background: #3D2314; transform: translateY(-2px); box-shadow: 0 10px 25px rgba(92,46,26,0.4); }
        
        .empty-cart { text-align: center; padding: 80px 40px; background: #3D2314; border-radius: 24px; border: 1px solid rgba(92,46,26,0.3); }
        .empty-cart i { font-size: 80px; color: #5C2E1A; margin-bottom: 20px; display: inline-block; }
        .empty-cart h3 { font-size: 28px; font-weight: 800; color: #F5E6D3; margin-bottom: 10px; }
        .empty-cart p { color: #C4A484; margin-bottom: 30px; }
        
        .toast-wrap { position: fixed; bottom: 30px; right: 30px; z-index: 9999; }
        .toast-msg { background: #3D2314; border-left: 3px solid #5C2E1A; padding: 12px 20px; margin-top: 8px; border-radius: 12px; color: #F5E6D3; font-size: 12px; transform: translateX(120%); transition: transform 0.3s; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .toast-msg.show { transform: translateX(0); }
        
        .footer { background: linear-gradient(135deg, #1A0F08 0%, #2C1810 100%); padding: 60px 0 0; border-top: 1px solid rgba(92,46,26,0.2); margin-top: 60px; }
        .footer-main { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 50px; }
        .footer-brand-section { max-width: 320px; }
        .footer-logo { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
        .logo-icon { font-size: 28px; color: #5C2E1A; }
        .logo-text { font-size: 22px; font-weight: 800; letter-spacing: 3px; color: #D4B5A7; }
        .logo-text span { color: #F5E6D3; }
        .footer-description { color: #A08874; font-size: 13px; line-height: 1.6; margin-bottom: 20px; }
        .footer-contact { display: flex; flex-direction: column; gap: 12px; }
        .contact-item { display: flex; align-items: center; gap: 12px; font-size: 12px; color: #A08874; }
        .contact-item i { width: 20px; color: #5C2E1A; }
        .contact-item a { color: #A08874; text-decoration: none; transition: color 0.3s; }
        .contact-item a:hover { color: #5C2E1A; }
        .footer-title { display: flex; align-items: center; gap: 10px; font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: #D4B5A7; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid rgba(92,46,26,0.2); position: relative; }
        .footer-title::after { content: ''; position: absolute; bottom: -1px; left: 0; width: 40px; height: 2px; background: #5C2E1A; }
        .footer-links-section ul { list-style: none; display: flex; flex-direction: column; gap: 12px; }
        .footer-links-section ul li a { display: flex; align-items: center; gap: 10px; color: #A08874; text-decoration: none; font-size: 13px; transition: all 0.3s ease; }
        .footer-links-section ul li a i { font-size: 10px; transition: transform 0.3s ease; }
        .footer-links-section ul li a:hover { color: #5C2E1A; transform: translateX(5px); }
        .footer-newsletter-section { max-width: 350px; }
        .newsletter-text { color: #A08874; font-size: 12px; margin-bottom: 15px; }
        .footer-newsletter-form { margin-bottom: 20px; }
        .input-group { display: flex; background: #3D2314; border: 1px solid rgba(92,46,26,0.3); border-radius: 50px; overflow: hidden; }
        .input-group:focus-within { border-color: #5C2E1A; }
        .input-group input { flex: 1; background: transparent; border: none; padding: 12px 18px; font-size: 12px; color: #F5E6D3; outline: none; }
        .input-group input::placeholder { color: #8B6B4A; }
        .input-group button { background: #5C2E1A; border: none; padding: 0 20px; color: #F5E6D3; cursor: pointer; transition: all 0.3s; }
        .input-group button:hover { background: #3D2314; transform: scale(1.05); }
        .footer-social { display: flex; gap: 12px; flex-wrap: wrap; }
        .social-icon { width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; background: #3D2314; border-radius: 50%; color: #D4B5A7; font-size: 16px; text-decoration: none; transition: all 0.3s ease; border: 1px solid rgba(92,46,26,0.3); }
        .social-icon:hover { background: #5C2E1A; color: #F5E6D3; transform: translateY(-5px) scale(1.1); }
        .footer-bottom { padding: 25px 0; border-top: 1px solid rgba(92,46,26,0.15); margin-top: 20px; text-align: center; }
        .footer-bottom-content { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .copyright { font-size: 11px; color: #8B6B4A; }
        .copyright i { color: #5C2E1A; }
        .payment-methods { display: flex; gap: 15px; font-size: 24px; color: #A08874; }
        .payment-methods i:hover { color: #5C2E1A; transform: translateY(-3px); }
        .badge-eco { display: flex; align-items: center; gap: 6px; background: #3D2314; padding: 5px 12px; border-radius: 20px; font-size: 10px; font-weight: 600; color: #eaa588; border: 1px solid rgba(92,46,26,0.3); }
        
        /* Loading Spinner */
        .btn-spinner { display: inline-block; width: 14px; height: 14px; border: 2px solid #fff; border-top-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite; margin-right: 8px; vertical-align: middle; }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        @media (max-width: 992px) { 
            .desktop-nav { display: none; } 
            .mobile-toggle { display: block; } 
            .navbar .container { padding: 10px 20px; } 
            .navbar-right { margin-left: auto; margin-right: 15px; }
            .page-header h1 { font-size: 48px; }
            .cart-summary { margin-top: 30px; position: static; }
            .footer-main { grid-template-columns: repeat(2, 1fr); }
            .footer-brand-section { grid-column: span 2; text-align: center; }
            .footer-logo { justify-content: center; }
            .footer-contact { align-items: center; }
            .footer-newsletter-section { grid-column: span 2; max-width: 100%; text-align: center; }
            .footer-bottom-content { flex-direction: column; text-align: center; }
            .payment-methods { justify-content: center; }
        }
        @media (max-width: 768px) { 
            .page-header h1 { font-size: 36px; }
            .page-header { padding: 60px 0; }
            .cart-section { padding: 60px 0; }
            .cart-table thead { display: none; }
            .cart-table td { display: block; text-align: right; padding: 12px 15px; }
            .cart-table td:before { content: attr(data-label); float: left; font-weight: 700; color: #D4B5A7; }
            .footer-main { grid-template-columns: 1fr; }
            .footer-brand-section { grid-column: span 1; text-align: center; }
            .footer-newsletter-section { grid-column: span 1; }
        }
    </style>
</head>
<body>

<div class="cursor-dot"></div>
<div class="cursor-outline"></div>

<div class="top-bar">✨ FREE SHIPPING ON ORDERS OVER $100 ✦ ETHICAL FASHION ✦ 30-DAY RETURNS ✨</div>

<nav class="navbar">
    <div class="container">
        <a class="navbar-brand" href="index.php">VELVET<span>.</span>AURA</a>
        
        <div class="desktop-nav">
            <div class="nav-item">
                <a href="index.php" class="nav-link"><i class="fas fa-home"></i> HOME</a>
                <div class="dropdown">
                    <a href="index.php#newArrivals"><i class="fas fa-sparkles"></i> New Arrivals</a>
                    <a href="index.php#bestSellers"><i class="fas fa-fire"></i> Best Sellers</a>
                    <a href="index.php#categories"><i class="fas fa-th-large"></i> Shop by Category</a>
                    <a href="index.php#featured"><i class="fas fa-gem"></i> Featured Collection</a>
                    <div class="dropdown-divider"></div>
                    <a href="lookbook.php"><i class="fas fa-camera"></i> Lookbook</a>
                    <a href="about.php"><i class="fas fa-heart"></i> About Us</a>
                </div>
            </div>
            
            <div class="nav-item">
                <a href="shop.php" class="nav-link"><i class="fas fa-store"></i> SHOP</a>
                <div class="dropdown dropdown-products">
                    <div class="dropdown-header"><i class="fas fa-gem"></i> ✨ FEATURED PRODUCTS</div>
                    <div class="row g-2 p-2">
                        <?php $count = 0; foreach($dropdown_products as $prod): if($count++ >= 4) break; ?>
                        <div class="col-6">
                            <a href="product-detail.php?id=<?php echo $prod['id']; ?>" class="product-item">
                                <img src="assets/images/<?php echo htmlspecialchars($prod['image']); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>" onerror="this.src='https://placehold.co/50x50/3D2314/5C2E1A?text=VA'">
                                <div>
                                    <div class="product-name"><?php echo htmlspecialchars($prod['name']); ?></div>
                                    <div class="product-price">$<?php echo number_format($prod['price'], 2); ?></div>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="dropdown-divider"></div>
                    <div class="dropdown-header"><i class="fas fa-tags"></i> 📁 SHOP BY CATEGORY</div>
                    <?php foreach($db_categories as $cat): ?>
                    <a href="shop.php?category=<?php echo htmlspecialchars($cat['slug']); ?>"><i class="fas fa-chevron-right"></i> <?php echo htmlspecialchars($cat['name']); ?></a>
                    <?php endforeach; ?>
                    <div class="dropdown-divider"></div>
                    <a href="shop.php"><i class="fas fa-bag-shopping"></i> View All Products →</a>
                    <a href="shop.php?filter=new"><i class="fas fa-sparkles"></i> New Arrivals</a>
                    <a href="shop.php?filter=bestseller"><i class="fas fa-crown"></i> Best Sellers</a>
                </div>
            </div>
            
            <div class="nav-item">
                <a href="lookbook.php" class="nav-link"><i class="fas fa-camera"></i> LOOKBOOK</a>
            </div>
            
            <div class="nav-item">
                <a href="about.php" class="nav-link"><i class="fas fa-info-circle"></i> ABOUT</a>
                <div class="dropdown">
                    <a href="about.php#our-story"><i class="fas fa-leaf"></i> Our Story</a>
                    <a href="about.php#sustainability"><i class="fas fa-globe"></i> Sustainability</a>
                    <a href="about.php#careers"><i class="fas fa-briefcase"></i> Careers</a>
                    <div class="dropdown-divider"></div>
                    <a href="contact.php"><i class="fas fa-envelope"></i> Contact Us</a>
                </div>
            </div>
            
            <?php if($is_logged_in): ?>
            <div class="nav-item">
                <a href="my-orders.php" class="nav-link"><i class="fas fa-box"></i> MY ORDERS</a>
            </div>
            <?php endif; ?>
        </div>
        
        <button class="mobile-toggle" id="mobileToggle"><i class="fas fa-bars"></i></button>
        
        <div class="navbar-right">
            <a href="wishlist.php" class="icon-btn"><i class="far fa-heart"></i><span class="badge-count" id="wishlistCount"><?php echo $wishlist_count; ?></span></a>
            <a href="cart.php" class="icon-btn"><i class="fas fa-shopping-bag"></i><span class="badge-count" id="cartCount"><?php echo $cart_count; ?></span></a>
            <?php if($is_logged_in): ?><a href="logout.php" class="icon-btn"><i class="fas fa-sign-out-alt"></i></a><?php else: ?><a href="login.php" class="icon-btn"><i class="far fa-user"></i></a><?php endif; ?>
        </div>
        
        <div class="mobile-menu" id="mobileMenu">
            <div class="mobile-nav-item">
                <div class="mobile-nav-link"><i class="fas fa-home"></i> HOME <i class="fas fa-chevron-down mobile-dropdown-toggle"></i></div>
                <div class="mobile-dropdown">
                    <a href="index.php#newArrivals"><i class="fas fa-sparkles"></i> New Arrivals</a>
                    <a href="index.php#bestSellers"><i class="fas fa-fire"></i> Best Sellers</a>
                    <a href="index.php#categories"><i class="fas fa-th-large"></i> Shop by Category</a>
                    <a href="index.php#featured"><i class="fas fa-gem"></i> Featured Collection</a>
                    <a href="lookbook.php"><i class="fas fa-camera"></i> Lookbook</a>
                    <a href="about.php"><i class="fas fa-heart"></i> About Us</a>
                </div>
            </div>
            <div class="mobile-nav-item">
                <div class="mobile-nav-link"><i class="fas fa-store"></i> SHOP <i class="fas fa-chevron-down mobile-dropdown-toggle"></i></div>
                <div class="mobile-dropdown">
                    <div class="dropdown-header">✨ FEATURED PRODUCTS</div>
                    <div class="mobile-product-grid">
                        <?php $count2 = 0; foreach($dropdown_products as $prod): if($count2++ >= 4) break; ?>
                        <a href="product-detail.php?id=<?php echo $prod['id']; ?>" class="mobile-product-item">
                            <img src="assets/images/<?php echo htmlspecialchars($prod['image']); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>">
                            <div><div class="product-name"><?php echo htmlspecialchars($prod['name']); ?></div><div class="product-price">$<?php echo number_format($prod['price'], 2); ?></div></div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="dropdown-divider"></div>
                    <div class="dropdown-header">📁 SHOP BY CATEGORY</div>
                    <?php foreach($db_categories as $cat): ?>
                    <a href="shop.php?category=<?php echo htmlspecialchars($cat['slug']); ?>"><i class="fas fa-chevron-right"></i> <?php echo htmlspecialchars($cat['name']); ?></a>
                    <?php endforeach; ?>
                    <div class="dropdown-divider"></div>
                    <a href="shop.php"><i class="fas fa-bag-shopping"></i> View All Products</a>
                    <a href="shop.php?filter=new"><i class="fas fa-sparkles"></i> New Arrivals</a>
                    <a href="shop.php?filter=bestseller"><i class="fas fa-crown"></i> Best Sellers</a>
                </div>
            </div>
            <div class="mobile-nav-item"><a href="lookbook.php" class="mobile-nav-link"><i class="fas fa-camera"></i> LOOKBOOK</a></div>
            <div class="mobile-nav-item">
                <div class="mobile-nav-link"><i class="fas fa-info-circle"></i> ABOUT <i class="fas fa-chevron-down mobile-dropdown-toggle"></i></div>
                <div class="mobile-dropdown">
                    <a href="about.php#our-story"><i class="fas fa-leaf"></i> Our Story</a>
                    <a href="about.php#sustainability"><i class="fas fa-globe"></i> Sustainability</a>
                    <a href="about.php#careers"><i class="fas fa-briefcase"></i> Careers</a>
                    <a href="contact.php"><i class="fas fa-envelope"></i> Contact Us</a>
                </div>
            </div>
            <?php if($is_logged_in): ?>
            <div class="mobile-nav-item"><a href="my-orders.php" class="mobile-nav-link"><i class="fas fa-box"></i> MY ORDERS</a></div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<section class="page-header">
    <div class="container">
        <h1 data-aos="fade-up">Shopping Cart</h1>
        <p data-aos="fade-up" data-aos-delay="100">Review your items</p>
    </div>
</section>

<section class="cart-section">
    <div class="container">
        <?php if(empty($cart_items)): ?>
        <div class="empty-cart" data-aos="fade-up">
            <i class="fas fa-shopping-bag"></i>
            <h3>Your cart is empty</h3>
            <p>Looks like you haven't added any items yet</p>
            <a href="shop.php" class="btn-checkout" style="display: inline-block; width: auto; padding: 12px 30px; text-decoration: none;">Start Shopping →</a>
        </div>
        <?php else: ?>
        <div class="row" data-aos="fade-up">
            <div class="col-lg-8">
                <div class="cart-table-wrapper" style="overflow-x: auto;">
                    <table class="cart-table">
                        <thead>
                            <tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($cart_items as $item): ?>
                            <tr data-product-id="<?php echo $item['product_id']; ?>" data-price="<?php echo $item['price']; ?>">
                                <td data-label="Product">
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <img src="assets/images/<?php echo htmlspecialchars($item['image']); ?>" class="cart-product-img" onerror="this.src='https://placehold.co/80x80/2C1810/D4B5A7?text=No+Image'">
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                    </div>
                                </td>
                                <td data-label="Price" class="item-price">$<?php echo number_format($item['price'], 2); ?></td>
                                <td data-label="Quantity">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <button class="quantity-btn qty-minus" data-id="<?php echo $item['product_id']; ?>">-</button>
                                        <span class="qty-value" id="qty-<?php echo $item['product_id']; ?>"><?php echo $item['quantity']; ?></span>
                                        <button class="quantity-btn qty-plus" data-id="<?php echo $item['product_id']; ?>">+</button>
                                    </div>
                                </td>
                                <td data-label="Total" class="item-total" id="total-<?php echo $item['product_id']; ?>">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                <td data-label="Remove">
                                    <button class="btn-remove remove-item" data-id="<?php echo $item['product_id']; ?>"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="cart-summary">
                    <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="cartSubtotal">$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span id="cartShipping"><?php echo $shipping > 0 ? '$'.number_format($shipping,2) : 'Free'; ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span id="cartTotal">$<?php echo number_format($total, 2); ?></span>
                    </div>
                    <a href="checkout.php" class="btn-checkout">Proceed to Checkout →</a>
                    <a href="shop.php" style="display: block; text-align: center; margin-top: 15px; color: #9d6953; text-decoration: none;">← Continue Shopping</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <div class="footer-main">
            <div class="footer-brand-section">
                <div class="footer-logo">
                    <span class="logo-icon">✦</span>
                    <span class="logo-text">VELVET<span>AURA</span></span>
                </div>
                <p class="footer-description">Ethical fashion for the conscious soul. Timeless pieces designed to last beyond seasons, crafted with love and intention.</p>
                <div class="footer-contact">
                    <div class="contact-item"><i class="fas fa-envelope"></i><a href="mailto:hello@velvetaura.com">hello@velvetaura.com</a></div>
                    <div class="contact-item"><i class="fas fa-phone-alt"></i><a href="tel:+15551234567">+1 (555) 123-4567</a></div>
                    <div class="contact-item"><i class="fas fa-map-marker-alt"></i><span>New York, NY 10001</span></div>
                </div>
            </div>
            <div class="footer-links-section">
                <h4 class="footer-title"><i class="fas fa-compass"></i> Quick Links</h4>
                <ul>
                    <li><a href="shop.php"><i class="fas fa-chevron-right"></i> Shop All</a></li>
                    <li><a href="shop.php?filter=new"><i class="fas fa-chevron-right"></i> New Arrivals</a></li>
                    <li><a href="shop.php?filter=bestseller"><i class="fas fa-chevron-right"></i> Best Sellers</a></li>
                    <li><a href="lookbook.php"><i class="fas fa-chevron-right"></i> Lookbook</a></li>
                    <li><a href="about.php"><i class="fas fa-chevron-right"></i> About Us</a></li>
                </ul>
            </div>
            <div class="footer-links-section">
                <h4 class="footer-title"><i class="fas fa-tags"></i> Categories</h4>
                <ul>
                    <?php $footer_cats = array_slice($db_categories, 0, 6); foreach($footer_cats as $cat): ?>
                    <li><a href="shop.php?category=<?php echo htmlspecialchars($cat['slug']); ?>"><i class="fas fa-chevron-right"></i> <?php echo htmlspecialchars($cat['name']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="footer-links-section">
                <h4 class="footer-title"><i class="fas fa-headset"></i> Support</h4>
                <ul>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> FAQ</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Shipping Info</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Returns</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Size Guide</a></li>
                </ul>
            </div>
            <div class="footer-newsletter-section">
                <h4 class="footer-title"><i class="fas fa-envelope-open-text"></i> Stay Connected</h4>
                <p class="newsletter-text">Get 15% off your first order!</p>
                <form class="footer-newsletter-form" id="footerNewsletterForm">
                    <div class="input-group">
                        <input type="email" placeholder="Your email address" required>
                        <button type="submit"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </form>
                <div class="footer-social">
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-pinterest"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-tiktok"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <div class="copyright"><i class="far fa-copyright"></i> <?php echo date('Y'); ?> Velvet Aura — All rights reserved.</div>
                <div class="payment-methods"><i class="fab fa-cc-visa"></i><i class="fab fa-cc-mastercard"></i><i class="fab fa-cc-amex"></i><i class="fab fa-cc-paypal"></i><i class="fab fa-apple-pay"></i></div>
                <div class="footer-badges"><span class="badge-eco"><i class="fas fa-leaf"></i> Eco-Friendly</span><span class="badge-eco"><i class="fas fa-recycle"></i> Sustainable</span></div>
            </div>
        </div>
    </div>
</footer>

<div class="toast-wrap" id="toastWrap"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: true, offset: 50 });
    
    const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
    
    // Custom Cursor
    const cursorDot = document.querySelector('.cursor-dot');
    const cursorOutline = document.querySelector('.cursor-outline');
    if (cursorDot && cursorOutline) {
        window.addEventListener('mousemove', function(e) {
            cursorDot.style.transform = `translate(${e.clientX - 4}px, ${e.clientY - 4}px)`;
            cursorOutline.style.transform = `translate(${e.clientX - 20}px, ${e.clientY - 20}px)`;
        });
        document.querySelectorAll('a, button, .icon-btn, .quantity-btn, .btn-remove').forEach(el => {
            el.addEventListener('mouseenter', () => { cursorOutline.style.transform = `scale(1.5)`; cursorOutline.style.background = 'rgba(92,46,26,0.1)'; cursorOutline.style.borderColor = '#F5E6D3'; });
            el.addEventListener('mouseleave', () => { cursorOutline.style.transform = `scale(1)`; cursorOutline.style.background = 'transparent'; cursorOutline.style.borderColor = '#5C2E1A'; });
        });
    }
    
    // Mobile Menu Toggle
    const mobileToggle = document.getElementById('mobileToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    if (mobileToggle && mobileMenu) {
        mobileToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');
            const icon = mobileToggle.querySelector('i');
            if (mobileMenu.classList.contains('active')) { icon.classList.remove('fa-bars'); icon.classList.add('fa-times'); }
            else { icon.classList.remove('fa-times'); icon.classList.add('fa-bars'); }
        });
    }
    
    document.querySelectorAll('.mobile-dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const parent = toggle.closest('.mobile-nav-item');
            const dropdown = parent.querySelector('.mobile-dropdown');
            toggle.classList.toggle('rotated');
            dropdown.classList.toggle('active');
        });
    });
    
    function showToast(msg, ok = true) {
        const wrap = document.getElementById('toastWrap');
        const toast = document.createElement('div');
        toast.className = 'toast-msg';
        toast.innerHTML = `<i class="fas ${ok ? 'fa-check-circle' : 'fa-exclamation-circle'}" style="color:#5C2E1A;"></i> ${msg}`;
        wrap.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, 2500);
    }
    
    // Update UI totals
    function updateUITotals() {
        let newSubtotal = 0;
        document.querySelectorAll('.cart-table tbody tr').forEach(row => {
            const price = parseFloat(row.querySelector('.item-price').textContent.replace('$', ''));
            const qty = parseInt(row.querySelector('.qty-value').textContent);
            const total = price * qty;
            row.querySelector('.item-total').textContent = '$' + total.toFixed(2);
            newSubtotal += total;
        });
        
        const shipping = newSubtotal > 100 ? 0 : 10;
        const total = newSubtotal + shipping;
        
        document.getElementById('cartSubtotal').textContent = '$' + newSubtotal.toFixed(2);
        document.getElementById('cartShipping').textContent = shipping === 0 ? 'Free' : '$' + shipping.toFixed(2);
        document.getElementById('cartTotal').textContent = '$' + total.toFixed(2);
    }
    
    // Update cart quantity via AJAX
    async function updateCartQuantity(productId, newQuantity) {
        if (!isLoggedIn) {
            showToast('Please login to update cart', false);
            setTimeout(() => window.location.href = 'login.php?redirect=cart.php', 1500);
            return;
        }
        
        if (newQuantity < 1) {
            // Remove item if quantity becomes 0
            await removeFromCart(productId);
            return;
        }
        
        try {
            const res = await fetch('backend/cart/update-cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'product_id=' + productId + '&quantity=' + newQuantity
            });
            const data = await res.json();
            if (data.success) {
                // Update UI without reload
                const qtySpan = document.getElementById('qty-' + productId);
                if (qtySpan) qtySpan.textContent = newQuantity;
                
                // Update cart badge count
                const cartBadge = document.getElementById('cartCount');
                if (cartBadge && data.cart_count) {
                    cartBadge.textContent = data.cart_count;
                }
                
                updateUITotals();
            } else {
                showToast(data.message || 'Error updating cart', false);
                window.location.reload();
            }
        } catch(e) { 
            showToast('Something went wrong', false);
            window.location.reload();
        }
    }
    
    // Remove item from cart
    async function removeFromCart(productId) {
        try {
            const res = await fetch('backend/cart/remove-from-cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'product_id=' + productId
            });
            const data = await res.json();
            if (data.success) {
                // Remove row with animation
                const row = document.querySelector(`tr[data-product-id="${productId}"]`);
                if (row) {
                    row.style.transition = 'opacity 0.3s';
                    row.style.opacity = '0';
                    setTimeout(() => row.remove(), 300);
                }
                
                // Update cart badge
                const cartBadge = document.getElementById('cartCount');
                if (cartBadge && data.cart_count) {
                    cartBadge.textContent = data.cart_count;
                }
                
                updateUITotals();
                
                // Check if cart is empty
                if (document.querySelectorAll('.cart-table tbody tr').length === 0) {
                    window.location.reload();
                }
            } else {
                showToast(data.message || 'Error removing item', false);
            }
        } catch(e) { 
            showToast('Something went wrong', false);
        }
    }
    
    // Plus button event
    document.querySelectorAll('.qty-plus').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const productId = parseInt(this.dataset.id);
            const qtySpan = document.getElementById('qty-' + productId);
            let currentQty = parseInt(qtySpan.textContent);
            updateCartQuantity(productId, currentQty + 1);
        });
    });
    
    // Minus button event
    document.querySelectorAll('.qty-minus').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const productId = parseInt(this.dataset.id);
            const qtySpan = document.getElementById('qty-' + productId);
            let currentQty = parseInt(qtySpan.textContent);
            if (currentQty > 1) {
                updateCartQuantity(productId, currentQty - 1);
            } else {
                removeFromCart(productId);
            }
        });
    });
    
    // Remove button event
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const productId = parseInt(this.dataset.id);
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                removeFromCart(productId);
            }
        });
    });
    
    document.getElementById('footerNewsletterForm')?.addEventListener('submit', (e) => {
        e.preventDefault();
        showToast('Thanks for subscribing! Check your email for 15% off ✨');
        e.target.reset();
    });
</script>
</body>
</html>