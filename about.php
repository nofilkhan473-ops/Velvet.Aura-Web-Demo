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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us — Velvet Aura</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #2C1810; color: #F5E6D3; cursor: none; }
        
        /* Custom Cursor */
        .cursor-dot { width: 8px; height: 8px; background: #5C2E1A; border-radius: 50%; position: fixed; pointer-events: none; z-index: 99999; }
        .cursor-outline { width: 40px; height: 40px; border: 2px solid #5C2E1A; border-radius: 50%; position: fixed; pointer-events: none; z-index: 99998; transition: all 0.15s ease; }
        
        /* Top Bar */
        .top-bar { background: #5C2E1A; padding: 8px 0; text-align: center; font-size: 11px; letter-spacing: 2px; color: #F5E6D3; text-transform: uppercase; font-weight: 500; }
        
        /* Navbar */
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
        
        /* Dropdown */
        .dropdown { position: absolute; top: 100%; left: 0; background: #2C1810; min-width: 240px; border-radius: 12px; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.3s ease; z-index: 100; border: 1px solid rgba(92,46,26,0.3); box-shadow: 0 15px 30px rgba(0,0,0,0.3); }
        .nav-item:hover .dropdown { opacity: 1; visibility: visible; transform: translateY(0); }
        .dropdown a { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #D4B5A7; text-decoration: none; font-size: 12px; transition: all 0.3s ease; }
        .dropdown a i { width: 20px; font-size: 12px; }
        .dropdown a:hover { background: #3D2314; color: #F5E6D3; padding-left: 28px; }
        .dropdown-divider { height: 1px; background: rgba(92,46,26,0.2); margin: 5px 0; }
        .dropdown-header { padding: 10px 20px; font-size: 10px; font-weight: 700; letter-spacing: 1px; color: #D4B5A7; text-transform: uppercase; background: #2C1810; border-radius: 12px 12px 0 0; }
        
        /* Products Dropdown */
        .dropdown-products { width: 520px; padding: 15px; }
        .dropdown-products .product-item { display: flex; align-items: center; gap: 10px; padding: 8px; border-radius: 8px; transition: all 0.3s; text-decoration: none; }
        .dropdown-products .product-item:hover { background: #3D2314; transform: translateX(5px); }
        .dropdown-products img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
        .dropdown-products .product-name { font-size: 12px; font-weight: 600; color: #D4B5A7; }
        .dropdown-products .product-price { font-size: 11px; color: #F5E6D3; }
        
        /* Mobile */
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
        
        /* Right Icons */
        .navbar-right { display: flex; gap: 12px; align-items: center; }
        .icon-btn { position: relative; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; color: #D4B5A7; font-size: 18px; transition: all 0.3s; border-radius: 50%; text-decoration: none; }
        .icon-btn:hover { color: #F5E6D3; background: rgba(92,46,26,0.2); transform: translateY(-2px); }
        .badge-count { position: absolute; top: -3px; right: -3px; background: #5C2E1A; color: #F5E6D3; font-size: 9px; font-weight: 700; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        
        /* Page Header */
        .page-header { background: linear-gradient(135deg, #3D2314 0%, #2C1810 100%); padding: 80px 0; text-align: center; position: relative; overflow: hidden; }
        .page-header::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(92,46,26,0.15) 0%, transparent 70%); animation: rotate 20s linear infinite; }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .page-header h1 { font-size: 56px; font-weight: 800; margin-bottom: 15px; color: #F5E6D3; animation: fadeInUp 0.8s ease; }
        .page-header p { color: #D4B5A7; font-size: 18px; animation: fadeInUp 1s ease; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        
        /* About Section */
        #our-story { scroll-margin-top: 100px; }
        .about-section { padding: 80px 0; background: #2C1810; }
        .about-image img { width: 100%; border-radius: 24px; border: 1px solid rgba(92,46,26,0.3); transition: transform 0.5s; }
        .about-image img:hover { transform: scale(1.02); }
        .about-content h2 { font-size: 36px; font-weight: 700; margin-bottom: 20px; color: #F5E6D3; }
        .about-content p { color: #C4A484; line-height: 1.8; margin-bottom: 20px; }
        
        /* Mission Section */
        .mission-section { background: #3D2314; padding: 80px 0; }
        .mission-card { text-align: center; padding: 40px; background: #2C1810; border-radius: 24px; transition: all 0.4s ease; border: 1px solid rgba(92,46,26,0.3); }
        .mission-card:hover { transform: translateY(-8px); border-color: #5C2E1A; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
        .mission-card i { font-size: 48px; color: #F5E6D3; margin-bottom: 20px; transition: transform 0.3s; display: inline-block; }
        .mission-card:hover i { transform: scale(1.1); }
        .mission-card h3 { font-size: 24px; font-weight: 600; margin-bottom: 15px; color: #825e49; }
        .mission-card p { color: #C4A484; }
        
        /* Sustainability Section */
        #sustainability { scroll-margin-top: 100px; }
        .sustainability-section { padding: 80px 0; background: #2C1810; }
        .sustainability-card { text-align: center; padding: 30px; background: #3D2314; border-radius: 20px; border: 1px solid rgba(92,46,26,0.3); transition: all 0.3s; margin-bottom: 20px; }
        .sustainability-card:hover { transform: translateY(-5px); border-color: #5C2E1A; }
        .sustainability-card i { font-size: 48px; color: white; margin-bottom: 20px; }
        .sustainability-card h3 { font-size: 22px; font-weight: 600; margin-bottom: 15px; color: #a6865d; }
        .sustainability-card p { color: #C4A484; }
        
        /* Values Section */
        .values-section { padding: 80px 0; background: #2C1810; }
        .value-card { text-align: center; padding: 30px; transition: all 0.3s ease; border-radius: 20px; background: #3D2314; border: 1px solid rgba(92,46,26,0.3); margin-bottom: 20px; }
        .value-card:hover { transform: translateY(-5px); border-color: #5C2E1A; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .value-card i { font-size: 40px; color: white; margin-bottom: 20px; display: inline-block; transition: transform 0.3s; }
        .value-card:hover i { transform: scale(1.1) rotate(5deg); }
        .value-card h4 { font-size: 20px; font-weight: 600; margin-bottom: 10px; color: #ba9668; }
        .value-card p { color: #C4A484; font-size: 14px; }
        
        /* Careers Section */
        #careers { scroll-margin-top: 100px; }
        .careers-section { padding: 80px 0; background: #3D2314; }
        .career-card { background: #2C1810; padding: 30px; border-radius: 20px; border: 1px solid rgba(92,46,26,0.3); transition: all 0.3s; margin-bottom: 20px; }
        .career-card:hover { transform: translateY(-5px); border-color: #5C2E1A; }
        .career-card h3 { font-size: 22px; font-weight: 600; margin-bottom: 10px; color: #F5E6D3; }
        .career-card p { color: #C4A484; margin-bottom: 15px; }
        .career-card .location { color: #c7876c; font-size: 13px; margin-bottom: 15px; }
        
        /* Button */
        .btn-primary-custom { background: #5C2E1A; color: #F5E6D3; padding: 14px 35px; border-radius: 50px; text-decoration: none; font-weight: 600; display: inline-block; transition: all 0.3s ease; border: none; }
        .btn-primary-custom:hover { background: #3D2314; color: #F5E6D3; transform: translateY(-3px); box-shadow: 0 10px 25px rgba(92,46,26,0.4); }
        
        /* Section Headers */
        .section-header { text-align: center; margin-bottom: 50px; }
        .section-header h2 { font-size: 38px; font-weight: 700; color: #F5E6D3; margin-bottom: 10px; position: relative; display: inline-block; }
        .section-header h2::after { content: ''; position: absolute; bottom: -10px; left: 50%; transform: translateX(-50%); width: 50px; height: 2px; background: #5C2E1A; }
        .section-header p { font-size: 12px; letter-spacing: 3px; text-transform: uppercase; color: #F5E6D3; margin-top: 15px; }
        
        /* Footer */
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
        
        /* Toast */
        .toast-wrap { position: fixed; bottom: 30px; right: 30px; z-index: 9999; }
        .toast-msg { background: #3D2314; border-left: 3px solid #5C2E1A; padding: 12px 20px; margin-top: 8px; border-radius: 12px; color: #F5E6D3; font-size: 12px; transform: translateX(120%); transition: transform 0.3s; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .toast-msg.show { transform: translateX(0); }
        
        @media (max-width: 1200px) { .page-header h1 { font-size: 56px; } }
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
            .about-section { padding: 60px 0; }
            .mission-section { padding: 60px 0; }
            .values-section { padding: 60px 0; }
            .footer-main { grid-template-columns: 1fr; }
            .footer-brand-section { grid-column: span 1; text-align: center; }
            .footer-newsletter-section { grid-column: span 1; }
        }
    </style>
</head>
<body>

<!-- Custom Cursors -->
<div class="cursor-dot"></div>
<div class="cursor-outline"></div>

<!-- Top Bar -->
<div class="top-bar">✨ FREE SHIPPING ON ORDERS OVER $100 ✦ ETHICAL FASHION ✦ 30-DAY RETURNS ✨</div>

<!-- Navbar -->
<nav class="navbar">
    <div class="container">
        <a class="navbar-brand" href="index.php">VELVET<span>.</span>AURA</a>
        
        <!-- Desktop Navigation -->
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
                <a href="about.php" class="nav-link active"><i class="fas fa-info-circle"></i> ABOUT</a>
                <div class="dropdown">
                    <a href="#our-story"><i class="fas fa-leaf"></i> Our Story</a>
                    <a href="#sustainability"><i class="fas fa-globe"></i> Sustainability</a>
                    <a href="#careers"><i class="fas fa-briefcase"></i> Careers</a>
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
        
        <!-- Mobile Toggle -->
        <button class="mobile-toggle" id="mobileToggle"><i class="fas fa-bars"></i></button>
        
        <!-- Right Icons -->
        <div class="navbar-right">
            <a href="wishlist.php" class="icon-btn"><i class="far fa-heart"></i><span class="badge-count" id="wishlistCount"><?php echo $wishlist_count; ?></span></a>
            <a href="cart.php" class="icon-btn"><i class="fas fa-shopping-bag"></i><span class="badge-count" id="cartCount"><?php echo $cart_count; ?></span></a>
            <?php if($is_logged_in): ?><a href="logout.php" class="icon-btn"><i class="fas fa-sign-out-alt"></i></a><?php else: ?><a href="login.php" class="icon-btn"><i class="far fa-user"></i></a><?php endif; ?>
        </div>
        
        <!-- Mobile Menu -->
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
                    <a href="#our-story"><i class="fas fa-leaf"></i> Our Story</a>
                    <a href="#sustainability"><i class="fas fa-globe"></i> Sustainability</a>
                    <a href="#careers"><i class="fas fa-briefcase"></i> Careers</a>
                    <a href="contact.php"><i class="fas fa-envelope"></i> Contact Us</a>
                </div>
            </div>
            <?php if($is_logged_in): ?>
            <div class="mobile-nav-item"><a href="my-orders.php" class="mobile-nav-link"><i class="fas fa-box"></i> MY ORDERS</a></div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1 data-aos="fade-up">Our Story</h1>
        <p data-aos="fade-up" data-aos-delay="100">Born from a love for aesthetics and sustainability</p>
    </div>
</section>

<!-- Our Story Section -->
<section id="our-story" class="about-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 about-image mb-4 mb-lg-0" data-aos="fade-right">
                <img src="assets/images/about.jpg" alt="About Us" onerror="this.src='https://placehold.co/600x600/3D2314/5C2E1A?text=Velvet+Aura'">
            </div>
            <div class="col-lg-6 about-content" data-aos="fade-left">
                <h2>Where Aesthetics Meets Purpose</h2>
                <p>Velvet Aura was founded in 2024 with a simple vision: create clothing that feels as good as it looks. We believe fashion should be a reflection of your inner self - calm, confident, and conscious.</p>
                <p>Every piece in our collection is thoughtfully designed using sustainable materials and ethical production practices. Our aesthetic is minimal yet expressive, timeless yet contemporary.</p>
                <p>We're not just selling clothes; we're curating a lifestyle of mindful consumption and authentic self-expression.</p>
                <a href="shop.php" class="btn-primary-custom">Shop Our Collection →</a>
            </div>
        </div>
    </div>
</section>

<!-- Mission Section -->
<section class="mission-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2>Our Mission</h2>
            <p>Creating fashion that respects both people and the planet</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                <div class="mission-card">
                    <i class="fas fa-leaf"></i>
                    <h3>Sustainability</h3>
                    <p>We use organic, recycled, and eco-friendly materials in all our products.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="mission-card">
                    <i class="fas fa-hand-sparkles"></i>
                    <h3>Ethical Production</h3>
                    <p>Fair wages and safe working conditions for all our artisans.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="mission-card">
                    <i class="fas fa-infinity"></i>
                    <h3>Timeless Design</h3>
                    <p>Pieces designed to last beyond seasonal trends.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Sustainability Section -->
<section id="sustainability" class="sustainability-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2>Our Sustainability Commitment</h2>
            <p>Fashion that doesn't cost the earth</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                <div class="sustainability-card">
                    <i class="fas fa-recycle"></i>
                    <h3>Eco-Friendly Materials</h3>
                    <p>We use organic cotton, recycled polyester, and sustainable fabrics in all our products.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="sustainability-card">
                    <i class="fas fa-hand-holding-heart"></i>
                    <h3>Ethical Manufacturing</h3>
                    <p>Our partners are certified for fair wages and safe working conditions.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="sustainability-card">
                    <i class="fas fa-box-open"></i>
                    <h3>Plastic-Free Packaging</h3>
                    <p>All our packaging is 100% recyclable and biodegradable.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="values-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2>What We Stand For</h2>
            <p>Our core values guide everything we do</p>
        </div>
        <div class="row g-4">
            <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="0">
                <div class="value-card">
                    <i class="fas fa-heart"></i>
                    <h4>Authenticity</h4>
                    <p>True to our vision</p>
                </div>
            </div>
            <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="100">
                <div class="value-card">
                    <i class="fas fa-star"></i>
                    <h4>Quality</h4>
                    <p>Premium materials</p>
                </div>
            </div>
            <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="200">
                <div class="value-card">
                    <i class="fas fa-globe"></i>
                    <h4>Global Community</h4>
                    <p>Inclusive fashion</p>
                </div>
            </div>
            <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="300">
                <div class="value-card">
                    <i class="fas fa-lightbulb"></i>
                    <h4>Innovation</h4>
                    <p>Modern design</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Careers Section -->
<section id="careers" class="careers-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2>Join Our Team</h2>
            <p>Be part of the Velvet Aura family</p>
        </div>
        <div class="row">
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="0">
                <div class="career-card">
                    <h3>Fashion Designer</h3>
                    <p class="location"><i class="fas fa-map-marker-alt"></i> New York, NY (On-site)</p>
                    <p>We're looking for a creative fashion designer with 3+ years of experience in sustainable fashion.</p>
                    <a href="#" class="btn-primary-custom" style="padding: 10px 25px; font-size: 12px;">Apply Now →</a>
                </div>
            </div>
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="career-card">
                    <h3>Digital Marketing Specialist</h3>
                    <p class="location"><i class="fas fa-map-marker-alt"></i> Remote (Worldwide)</p>
                    <p>Join our marketing team to help grow our brand presence across social media platforms.</p>
                    <a href="#" class="btn-primary-custom" style="padding: 10px 25px; font-size: 12px;">Apply Now →</a>
                </div>
            </div>
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
                <div class="career-card">
                    <h3>Customer Experience Associate</h3>
                    <p class="location"><i class="fas fa-map-marker-alt"></i> Remote (US Only)</p>
                    <p>Help us deliver exceptional customer service to our growing community.</p>
                    <a href="#" class="btn-primary-custom" style="padding: 10px 25px; font-size: 12px;">Apply Now →</a>
                </div>
            </div>
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="300">
                <div class="career-card">
                    <h3>Supply Chain Coordinator</h3>
                    <p class="location"><i class="fas fa-map-marker-alt"></i> New York, NY (Hybrid)</p>
                    <p>Manage our ethical supply chain and vendor relationships.</p>
                    <a href="#" class="btn-primary-custom" style="padding: 10px 25px; font-size: 12px;">Apply Now →</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
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
    let cartCount = <?php echo $cart_count; ?>;
    let wishlistCount = <?php echo $wishlist_count; ?>;
    
    // Custom Cursor
    const cursorDot = document.querySelector('.cursor-dot');
    const cursorOutline = document.querySelector('.cursor-outline');
    if (cursorDot && cursorOutline) {
        window.addEventListener('mousemove', function(e) {
            cursorDot.style.transform = `translate(${e.clientX - 4}px, ${e.clientY - 4}px)`;
            cursorOutline.style.transform = `translate(${e.clientX - 20}px, ${e.clientY - 20}px)`;
        });
        document.querySelectorAll('a, button, .mission-card, .value-card, .icon-btn').forEach(el => {
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
    
    // Mobile Dropdown Toggles
    document.querySelectorAll('.mobile-dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const parent = toggle.closest('.mobile-nav-item');
            const dropdown = parent.querySelector('.mobile-dropdown');
            toggle.classList.toggle('rotated');
            dropdown.classList.toggle('active');
        });
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === "#") return;
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
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
    
    // Newsletter
    document.getElementById('footerNewsletterForm')?.addEventListener('submit', (e) => {
        e.preventDefault();
        showToast('Thanks for subscribing! Check your email for 15% off ✨');
        e.target.reset();
    });
</script>
</body>
</html>