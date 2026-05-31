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

$cart_count = getCartCount();
$wishlist_count = getWishlistCount();

// Get user's wishlist items from database
$wishlist_items = [];
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $wishlist_query = "SELECT p.* FROM products p 
                        INNER JOIN wishlist w ON p.id = w.product_id 
                        WHERE w.user_id = $user_id 
                        ORDER BY w.created_at DESC";
    $wishlist_result = mysqli_query($conn, $wishlist_query);
    if ($wishlist_result) {
        $wishlist_items = mysqli_fetch_all($wishlist_result, MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist — Velvet Aura</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #2C1810; color: #F5E6D3; cursor: none; }
        
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
        .page-header::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(184, 133, 112, 0.15) 0%, transparent 70%); animation: rotate 20s linear infinite; }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .page-header h1 { font-size: 56px; font-weight: 800; margin-bottom: 15px; color: #F5E6D3; animation: fadeInUp 0.8s ease; }
        .page-header p { color: #D4B5A7; font-size: 18px; animation: fadeInUp 1s ease; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        
        .wishlist-section { padding: 80px 0; min-height: 60vh; background: #2C1810; }
        
        .product-card { background: #3D2314; border-radius: 24px; overflow: hidden; transition: all 0.4s ease; border: 1px solid rgba(92,46,26,0.3); height: 100%; display: flex; flex-direction: column; position: relative; }
        .product-card:hover { transform: translateY(-8px); border-color: #5C2E1A; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
        .product-image { position: relative; overflow: hidden; height: 280px; background: #2C1810; }
        .product-image img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .product-card:hover .product-image img { transform: scale(1.05); }
        .product-badge { position: absolute; top: 12px; right: 12px; background: #5C2E1A; color: #F5E6D3; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; z-index: 2; }
        .product-info { padding: 20px; text-align: center; flex: 1; display: flex; flex-direction: column; }
        .product-info h4 { font-size: 16px; font-weight: 700; margin-bottom: 8px; color: #F5E6D3; }
        .product-price { font-weight: 800; font-size: 20px; color: #5C2E1A; }
        .product-old-price { text-decoration: line-through; color: #8B6B4A; font-size: 13px; margin-left: 6px; }
        .product-rating { margin: 10px 0; color: #D4A574; font-size: 12px; }
        
        .btn-add-cart { background: #5C2E1A; color: #F5E6D3; padding: 12px; border-radius: 50px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; transition: all 0.3s; width: 100%; margin-bottom: 8px; }
        .btn-add-cart:hover { background: #3D2314; transform: translateY(-2px); }
        
        .btn-remove-wishlist { background: transparent; border: 1px solid #8B4513; color: #8B4513; padding: 12px; border-radius: 50px; font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.3s; width: 100%; }
        .btn-remove-wishlist:hover { background: #8B4513; color: #F5E6D3; transform: translateY(-2px); }
        
        .login-required { text-align: center; padding: 80px 40px; background: #3D2314; border-radius: 24px; border: 1px solid rgba(92,46,26,0.3); max-width: 500px; margin: 0 auto; }
        .login-required i { font-size: 80px; color: #5C2E1A; margin-bottom: 20px; display: inline-block; }
        .login-required h3 { font-size: 28px; font-weight: 800; color: #F5E6D3; margin-bottom: 12px; }
        .login-required p { color: #C4A484; margin-bottom: 30px; }
        .btn-login { background: #5C2E1A; color: #F5E6D3; padding: 14px 40px; border-radius: 50px; text-decoration: none; font-weight: 700; display: inline-block; transition: all 0.3s; }
        .btn-login:hover { background: #3D2314; transform: translateY(-3px); box-shadow: 0 10px 25px rgba(92,46,26,0.4); }
        
        .empty-state { text-align: center; padding: 80px 40px; background: #3D2314; border-radius: 24px; border: 1px solid rgba(92,46,26,0.3); }
        .empty-state i { font-size: 80px; color: #a6694e; margin-bottom: 20px; display: inline-block; }
        .empty-state h3 { font-size: 28px; font-weight: 800; color: #F5E6D3; margin-bottom: 12px; }
        .empty-state p { color: #C4A484; margin-bottom: 30px; }
        .btn-primary-custom { background: #5C2E1A; color: #F5E6D3; padding: 14px 40px; border-radius: 50px; text-decoration: none; font-weight: 700; display: inline-block; transition: all 0.3s; border: none; cursor: pointer; }
        .btn-primary-custom:hover { background: #3D2314; transform: translateY(-3px); box-shadow: 0 10px 25px rgba(92,46,26,0.4); }
        
        .btn-clear-wishlist { background: transparent; border: 1px solid #5C2E1A; color: #5C2E1A; padding: 14px 30px; border-radius: 50px; cursor: pointer; margin-top: 40px; font-weight: 700; font-size: 14px; transition: all 0.3s; }
        .btn-clear-wishlist:hover { background: #5C2E1A; color: #F5E6D3; transform: translateY(-3px); }
        
        .footer { background: linear-gradient(135deg, #1A0F08 0%, #2C1810 100%); padding: 60px 0 0; border-top: 1px solid rgba(92,46,26,0.2); }
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
        
        .toast-wrap { position: fixed; bottom: 30px; right: 30px; z-index: 9999; }
        .toast-msg { background: #3D2314; border-left: 3px solid #5C2E1A; padding: 12px 20px; margin-top: 8px; border-radius: 12px; color: #F5E6D3; font-size: 12px; transform: translateX(120%); transition: transform 0.3s; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .toast-msg.show { transform: translateX(0); }
        
        /* Spinner Animation */
        .btn-spinner { display: inline-block; width: 14px; height: 14px; border: 2px solid #fff; border-top-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite; margin-right: 8px; vertical-align: middle; }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        @media (max-width: 992px) { 
            .desktop-nav { display: none; } 
            .mobile-toggle { display: block; } 
            .navbar .container { padding: 10px 20px; } 
            .navbar-right { margin-left: auto; margin-right: 15px; }
            .page-header h1 { font-size: 48px; }
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
            .wishlist-section { padding: 60px 0; }
            .product-image img { height: 220px; }
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
            <div class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-home"></i> HOME</a><div class="dropdown"><a href="index.php#newArrivals"><i class="fas fa-sparkles"></i> New Arrivals</a><a href="index.php#bestSellers"><i class="fas fa-fire"></i> Best Sellers</a><a href="index.php#categories"><i class="fas fa-th-large"></i> Shop by Category</a><a href="index.php#featured"><i class="fas fa-gem"></i> Featured Collection</a><div class="dropdown-divider"></div><a href="lookbook.php"><i class="fas fa-camera"></i> Lookbook</a><a href="about.php"><i class="fas fa-heart"></i> About Us</a></div></div>
            <div class="nav-item"><a href="shop.php" class="nav-link"><i class="fas fa-store"></i> SHOP</a><div class="dropdown dropdown-products"><div class="dropdown-header"><i class="fas fa-gem"></i> ✨ FEATURED PRODUCTS</div><div class="row g-2 p-2"><?php $count = 0; foreach($dropdown_products as $prod): if($count++ >= 4) break; ?><div class="col-6"><a href="product-detail.php?id=<?php echo $prod['id']; ?>" class="product-item"><img src="assets/images/<?php echo htmlspecialchars($prod['image']); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>" onerror="this.src='https://placehold.co/50x50/3D2314/5C2E1A?text=VA'"><div><div class="product-name"><?php echo htmlspecialchars($prod['name']); ?></div><div class="product-price">$<?php echo number_format($prod['price'], 2); ?></div></div></a></div><?php endforeach; ?></div><div class="dropdown-divider"></div><div class="dropdown-header"><i class="fas fa-tags"></i> 📁 SHOP BY CATEGORY</div><?php foreach($db_categories as $cat): ?><a href="shop.php?category=<?php echo htmlspecialchars($cat['slug']); ?>"><i class="fas fa-chevron-right"></i> <?php echo htmlspecialchars($cat['name']); ?></a><?php endforeach; ?><div class="dropdown-divider"></div><a href="shop.php"><i class="fas fa-bag-shopping"></i> View All Products →</a><a href="shop.php?filter=new"><i class="fas fa-sparkles"></i> New Arrivals</a><a href="shop.php?filter=bestseller"><i class="fas fa-crown"></i> Best Sellers</a></div></div>
            <div class="nav-item"><a href="lookbook.php" class="nav-link"><i class="fas fa-camera"></i> LOOKBOOK</a></div>
            <div class="nav-item"><a href="about.php" class="nav-link"><i class="fas fa-info-circle"></i> ABOUT</a><div class="dropdown"><a href="about.php#our-story"><i class="fas fa-leaf"></i> Our Story</a><a href="about.php#sustainability"><i class="fas fa-globe"></i> Sustainability</a><a href="about.php#careers"><i class="fas fa-briefcase"></i> Careers</a><div class="dropdown-divider"></div><a href="contact.php"><i class="fas fa-envelope"></i> Contact Us</a></div></div>
            <?php if($is_logged_in): ?><div class="nav-item"><a href="my-orders.php" class="nav-link"><i class="fas fa-box"></i> MY ORDERS</a></div><?php endif; ?>
        </div>
        
        <button class="mobile-toggle" id="mobileToggle"><i class="fas fa-bars"></i></button>
        
        <div class="navbar-right">
            <a href="wishlist.php" class="icon-btn"><i class="far fa-heart"></i><span class="badge-count" id="wishlistCount"><?php echo $wishlist_count; ?></span></a>
            <a href="cart.php" class="icon-btn"><i class="fas fa-shopping-bag"></i><span class="badge-count" id="cartCount"><?php echo $cart_count; ?></span></a>
            <?php if($is_logged_in): ?><a href="logout.php" class="icon-btn"><i class="fas fa-sign-out-alt"></i></a><?php else: ?><a href="login.php" class="icon-btn"><i class="far fa-user"></i></a><?php endif; ?>
        </div>
        
        <div class="mobile-menu" id="mobileMenu">
            <div class="mobile-nav-item"><div class="mobile-nav-link"><i class="fas fa-home"></i> HOME <i class="fas fa-chevron-down mobile-dropdown-toggle"></i></div><div class="mobile-dropdown"><a href="index.php#newArrivals"><i class="fas fa-sparkles"></i> New Arrivals</a><a href="index.php#bestSellers"><i class="fas fa-fire"></i> Best Sellers</a><a href="index.php#categories"><i class="fas fa-th-large"></i> Shop by Category</a><a href="index.php#featured"><i class="fas fa-gem"></i> Featured Collection</a><a href="lookbook.php"><i class="fas fa-camera"></i> Lookbook</a><a href="about.php"><i class="fas fa-heart"></i> About Us</a></div></div>
            <div class="mobile-nav-item"><div class="mobile-nav-link"><i class="fas fa-store"></i> SHOP <i class="fas fa-chevron-down mobile-dropdown-toggle"></i></div><div class="mobile-dropdown"><div class="dropdown-header">✨ FEATURED PRODUCTS</div><div class="mobile-product-grid"><?php $count2 = 0; foreach($dropdown_products as $prod): if($count2++ >= 4) break; ?><a href="product-detail.php?id=<?php echo $prod['id']; ?>" class="mobile-product-item"><img src="assets/images/<?php echo htmlspecialchars($prod['image']); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>"><div><div class="product-name"><?php echo htmlspecialchars($prod['name']); ?></div><div class="product-price">$<?php echo number_format($prod['price'], 2); ?></div></div></a><?php endforeach; ?></div><div class="dropdown-divider"></div><div class="dropdown-header">📁 SHOP BY CATEGORY</div><?php foreach($db_categories as $cat): ?><a href="shop.php?category=<?php echo htmlspecialchars($cat['slug']); ?>"><i class="fas fa-chevron-right"></i> <?php echo htmlspecialchars($cat['name']); ?></a><?php endforeach; ?><div class="dropdown-divider"></div><a href="shop.php"><i class="fas fa-bag-shopping"></i> View All Products</a><a href="shop.php?filter=new"><i class="fas fa-sparkles"></i> New Arrivals</a><a href="shop.php?filter=bestseller"><i class="fas fa-crown"></i> Best Sellers</a></div></div>
            <div class="mobile-nav-item"><a href="lookbook.php" class="mobile-nav-link"><i class="fas fa-camera"></i> LOOKBOOK</a></div>
            <div class="mobile-nav-item"><div class="mobile-nav-link"><i class="fas fa-info-circle"></i> ABOUT <i class="fas fa-chevron-down mobile-dropdown-toggle"></i></div><div class="mobile-dropdown"><a href="about.php#our-story"><i class="fas fa-leaf"></i> Our Story</a><a href="about.php#sustainability"><i class="fas fa-globe"></i> Sustainability</a><a href="about.php#careers"><i class="fas fa-briefcase"></i> Careers</a><a href="contact.php"><i class="fas fa-envelope"></i> Contact Us</a></div></div>
            <?php if($is_logged_in): ?><div class="mobile-nav-item"><a href="my-orders.php" class="mobile-nav-link"><i class="fas fa-box"></i> MY ORDERS</a></div><?php endif; ?>
        </div>
    </div>
</nav>

<section class="page-header">
    <div class="container">
        <h1 data-aos="fade-up">My Wishlist</h1>
        <p data-aos="fade-up" data-aos-delay="100">Your favorite items saved here</p>
    </div>
</section>

<section class="wishlist-section">
    <div class="container">
        <?php if(!$is_logged_in): ?>
            <div class="login-required" data-aos="fade-up"><i class="far fa-heart"></i><h3>Login to View Your Wishlist</h3><p>Please login to see your saved items and continue shopping</p><a href="login.php?redirect=wishlist.php" class="btn-login">Login Now →</a><p style="margin-top:20px;font-size:12px;">Don't have an account? <a href="register.php" style="color:#5C2E1A;">Register here</a></p></div>
        <?php elseif(empty($wishlist_items)): ?>
            <div class="empty-state" data-aos="fade-up"><i class="far fa-heart"></i><h3>Your wishlist is empty</h3><p>Save your favorite items here</p><a href="shop.php" class="btn-primary-custom">Explore Products →</a></div>
        <?php else: ?>
            <div class="row g-4" id="wishlistGrid">
                <?php $index = 0; foreach($wishlist_items as $product): 
                    $discount = isset($product['old_price']) && $product['old_price'] > 0 ? round((($product['old_price'] - $product['price']) / $product['old_price']) * 100) : null;
                    $rating = floatval($product['rating'] ?? 4.5);
                    $fullStars = floor($rating);
                    $halfStar = ($rating - $fullStars) >= 0.5;
                ?>
                <div class="col-lg-3 col-md-6">
                    <div class="product-card">
                        <a href="product-detail.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit;">
                            <div class="product-image">
                                <img src="assets/images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='https://placehold.co/400x500/3D2314/D4B5A7?text=<?php echo urlencode($product['name']); ?>'">
                                <?php if(isset($product['in_stock']) && $product['in_stock'] == 0): ?><span class="product-badge">Sold Out</span><?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                <div class="product-price">$<?php echo number_format($product['price'], 2); ?><?php if(isset($product['old_price']) && $product['old_price']): ?><span class="product-old-price">$<?php echo number_format($product['old_price'], 2); ?></span><?php endif; ?></div>
                                <div class="product-rating"><?php for($i=1;$i<=$fullStars;$i++): ?><i class="fas fa-star"></i><?php endfor; ?><?php if($halfStar): ?><i class="fas fa-star-half-alt"></i><?php endif; ?><?php for($i=1;$i<=5-ceil($rating);$i++): ?><i class="far fa-star"></i><?php endfor; ?></div>
                            </div>
                        </a>
                        <div style="padding:0 20px 20px 20px;">
                            <button class="btn-add-cart add-to-cart" data-id="<?php echo $product['id']; ?>">🛒 Add to Cart</button>
                            <button class="btn-remove-wishlist remove-from-wishlist" data-id="<?php echo $product['id']; ?>">❤️ Remove</button>
                        </div>
                    </div>
                </div>
                <?php $index++; endforeach; ?>
            </div>
            <div class="row"><div class="col-12 text-center"><button onclick="clearAllWishlist()" class="btn-clear-wishlist" id="clearWishlistBtn"><i class="fas fa-trash-alt me-2"></i> Clear All Wishlist</button></div></div>
        <?php endif; ?>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <div class="footer-main">
            <div class="footer-brand-section"><div class="footer-logo"><span class="logo-icon">✦</span><span class="logo-text">VELVET<span>AURA</span></span></div><p class="footer-description">Ethical fashion for the conscious soul. Timeless pieces designed to last beyond seasons, crafted with love and intention.</p><div class="footer-contact"><div class="contact-item"><i class="fas fa-envelope"></i><a href="mailto:hello@velvetaura.com">hello@velvetaura.com</a></div><div class="contact-item"><i class="fas fa-phone-alt"></i><a href="tel:+15551234567">+1 (555) 123-4567</a></div><div class="contact-item"><i class="fas fa-map-marker-alt"></i><span>New York, NY 10001</span></div></div></div>
            <div class="footer-links-section"><h4 class="footer-title"><i class="fas fa-compass"></i> Quick Links</h4><ul><li><a href="shop.php"><i class="fas fa-chevron-right"></i> Shop All</a></li><li><a href="shop.php?filter=new"><i class="fas fa-chevron-right"></i> New Arrivals</a></li><li><a href="shop.php?filter=bestseller"><i class="fas fa-chevron-right"></i> Best Sellers</a></li><li><a href="lookbook.php"><i class="fas fa-chevron-right"></i> Lookbook</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> About Us</a></li></ul></div>
            <div class="footer-links-section"><h4 class="footer-title"><i class="fas fa-tags"></i> Categories</h4><ul><?php $footer_cats = array_slice($db_categories, 0, 6); foreach($footer_cats as $cat): ?><li><a href="shop.php?category=<?php echo htmlspecialchars($cat['slug']); ?>"><i class="fas fa-chevron-right"></i> <?php echo htmlspecialchars($cat['name']); ?></a></li><?php endforeach; ?></ul></div>
            <div class="footer-links-section"><h4 class="footer-title"><i class="fas fa-headset"></i> Support</h4><ul><li><a href="#"><i class="fas fa-chevron-right"></i> FAQ</a></li><li><a href="#"><i class="fas fa-chevron-right"></i> Shipping Info</a></li><li><a href="#"><i class="fas fa-chevron-right"></i> Returns</a></li><li><a href="#"><i class="fas fa-chevron-right"></i> Size Guide</a></li></ul></div>
            <div class="footer-newsletter-section"><h4 class="footer-title"><i class="fas fa-envelope-open-text"></i> Stay Connected</h4><p class="newsletter-text">Get 15% off your first order!</p><form class="footer-newsletter-form" id="footerNewsletterForm"><div class="input-group"><input type="email" placeholder="Your email address" required><button type="submit"><i class="fas fa-paper-plane"></i></button></div></form><div class="footer-social"><a href="#" class="social-icon"><i class="fab fa-instagram"></i></a><a href="#" class="social-icon"><i class="fab fa-pinterest"></i></a><a href="#" class="social-icon"><i class="fab fa-tiktok"></i></a><a href="#" class="social-icon"><i class="fab fa-youtube"></i></a><a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a><a href="#" class="social-icon"><i class="fab fa-twitter"></i></a></div></div>
        </div>
        <div class="footer-bottom"><div class="footer-bottom-content"><div class="copyright"><i class="far fa-copyright"></i> <?php echo date('Y'); ?> Velvet Aura — All rights reserved.</div><div class="payment-methods"><i class="fab fa-cc-visa"></i><i class="fab fa-cc-mastercard"></i><i class="fab fa-cc-amex"></i><i class="fab fa-cc-paypal"></i><i class="fab fa-apple-pay"></i></div><div class="footer-badges"><span class="badge-eco"><i class="fas fa-leaf"></i> Eco-Friendly</span><span class="badge-eco"><i class="fas fa-recycle"></i> Sustainable</span></div></div></div>
    </div>
</footer>

<div class="toast-wrap" id="toastWrap"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: true, offset: 50 });
    
    const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
    let cartCountElement = document.getElementById('cartCount');
    let wishlistCountElement = document.getElementById('wishlistCount');
    
    // Custom Cursor
    const cursorDot = document.querySelector('.cursor-dot');
    const cursorOutline = document.querySelector('.cursor-outline');
    if (cursorDot && cursorOutline) {
        window.addEventListener('mousemove', function(e) {
            cursorDot.style.transform = `translate(${e.clientX - 4}px, ${e.clientY - 4}px)`;
            cursorOutline.style.transform = `translate(${e.clientX - 20}px, ${e.clientY - 20}px)`;
        });
        document.querySelectorAll('a, button, .product-card, .icon-btn').forEach(el => {
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
    
    // Add to Cart
    async function addToCart(id, btn) {
        if (!isLoggedIn) {
            showToast('Please login to continue', false);
            setTimeout(() => window.location.href = 'login.php?redirect=wishlist.php', 1500);
            return;
        }
        btn.disabled = true;
        btn.innerHTML = '<span class="btn-spinner"></span> Adding...';
        try {
            const res = await fetch('backend/cart/add-to-cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'product_id=' + id + '&quantity=1'
            });
            const data = await res.json();
            if (data.success) {
                showToast('Added to cart 🛍️');
                if (cartCountElement && data.cart_count) cartCountElement.textContent = data.cart_count;
                btn.style.transform = 'scale(0.95)';
                setTimeout(() => btn.style.transform = '', 200);
            } else {
                showToast(data.message || 'Error', false);
            }
        } catch(e) { 
            showToast('Something went wrong', false);
        }
        btn.disabled = false;
        btn.innerHTML = '🛒 Add to Cart';
    }
    
    // Remove from Wishlist
    async function removeFromWishlist(id, btn) {
        if (!confirm('Are you sure you want to remove this item from your wishlist?')) return;
        btn.disabled = true;
        btn.innerHTML = '<span class="btn-spinner"></span> Removing...';
        try {
            const res = await fetch('backend/wishlist/remove-from-wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'product_id=' + id
            });
            const data = await res.json();
            if (data.success) {
                showToast('Removed from wishlist 💔');
                const card = btn.closest('.col-lg-3');
                if (card) { card.style.transition = 'opacity 0.3s'; card.style.opacity = '0'; setTimeout(() => card.remove(), 300); }
                if (wishlistCountElement) {
                    let current = parseInt(wishlistCountElement.textContent) || 0;
                    wishlistCountElement.textContent = current - 1;
                }
                setTimeout(() => { if (document.querySelectorAll('.product-card').length === 0) window.location.reload(); }, 400);
            } else {
                showToast(data.message || 'Error', false);
                btn.disabled = false;
                btn.innerHTML = '❤️ Remove';
            }
        } catch(e) { 
            showToast('Something went wrong', false);
            btn.disabled = false;
            btn.innerHTML = '❤️ Remove';
        }
    }
    
    // Clear All Wishlist
    async function clearAllWishlist() {
        if (!confirm('💔 Are you sure you want to clear your entire wishlist? This action cannot be undone.')) return;
        
        const clearBtn = document.getElementById('clearWishlistBtn');
        const originalText = clearBtn.innerHTML;
        clearBtn.disabled = true;
        clearBtn.innerHTML = '<span class="btn-spinner"></span> Clearing...';
        
        try {
            const res = await fetch('backend/wishlist/clear-wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });
            const data = await res.json();
            if (data.success) {
                showToast('Wishlist cleared successfully! 🗑️');
                const grid = document.getElementById('wishlistGrid');
                if (grid) { grid.style.transition = 'opacity 0.3s'; grid.style.opacity = '0'; }
                setTimeout(() => window.location.reload(), 500);
            } else {
                showToast(data.message || 'Error clearing wishlist', false);
                clearBtn.disabled = false;
                clearBtn.innerHTML = originalText;
            }
        } catch(e) {
            showToast('Something went wrong. Please try again.', false);
            clearBtn.disabled = false;
            clearBtn.innerHTML = originalText;
        }
    }
    
    // Attach event listeners
    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); addToCart(parseInt(btn.dataset.id), btn); });
    });
    document.querySelectorAll('.remove-from-wishlist').forEach(btn => {
        btn.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); removeFromWishlist(parseInt(btn.dataset.id), btn); });
    });
    
    // Newsletter
    document.getElementById('footerNewsletterForm')?.addEventListener('submit', (e) => {
        e.preventDefault();
        showToast('Thanks for subscribing! Check your email for 15% off ✨');
        e.target.reset();
    });
</script>
<script src="assets/js/cart.js"></script>
<script>
// Page-specific code
document.querySelectorAll('.add-to-cart-btn, .add-to-cart, .btn-add-cart').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const productId = btn.dataset.id;
        addToCart(productId, btn);
    });
});
</script>
</body>
</html>