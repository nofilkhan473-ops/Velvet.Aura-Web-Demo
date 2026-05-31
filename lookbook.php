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
    <title>Lookbook — Velvet Aura</title>
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
        
        /* Hero Section */
        .lookbook-hero { 
            background: linear-gradient(135deg, #5C2E1A 0%, #3D2314 50%, #2C1810 100%); 
            padding: 100px 0; 
            text-align: center; 
            position: relative; 
            overflow: hidden;
        }
        .lookbook-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(212,181,167,0.08) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .lookbook-hero h1 { font-size: 64px; font-weight: 800; margin-bottom: 20px; color: #F5E6D3; letter-spacing: -1px; }
        .lookbook-hero p { font-size: 18px; color: #D4B5A7; max-width: 600px; margin: 0 auto; }
        .hero-badge { 
            display: inline-block; 
            background: rgba(212,181,167,0.15); 
            padding: 8px 20px; 
            border-radius: 50px; 
            font-size: 12px; 
            letter-spacing: 2px; 
            margin-bottom: 20px;
            backdrop-filter: blur(5px);
        }
        
        /* Featured Collection */
        .featured-collection { padding: 80px 0; background: #2C1810; }
        .section-title { text-align: center; margin-bottom: 60px; }
        .section-title h2 { font-size: 42px; font-weight: 700; margin-bottom: 15px; position: relative; display: inline-block; }
        .section-title h2::after { content: ''; position: absolute; bottom: -15px; left: 50%; transform: translateX(-50%); width: 60px; height: 3px; background: #5C2E1A; border-radius: 3px; }
        .section-title p { color: #D4B5A7; font-size: 16px; }
        
        .featured-card {
            background: #3D2314;
            border-radius: 24px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(92,46,26,0.3);
            height: 100%;
        }
        .featured-card:hover { transform: translateY(-10px); border-color: #5C2E1A; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
        .featured-card img { width: 100%; height: 300px; object-fit: cover; transition: transform 0.5s; }
        .featured-card:hover img { transform: scale(1.05); }
        .featured-card-body { padding: 25px; text-align: center; }
        .featured-card-body h3 { font-size: 20px; font-weight: 700; margin-bottom: 10px; }
        .featured-card-body p { color: #D4B5A7; font-size: 14px; margin-bottom: 15px; }
        .featured-btn { 
            background: transparent; 
            border: 2px solid #5C2E1A; 
            color: #b38470; 
            padding: 10px 25px; 
            border-radius: 50px; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 13px;
            display: inline-block;
            transition: all 0.3s;
        }
        .featured-btn:hover { background: #5C2E1A; color: #F5E6D3; transform: translateY(-2px); }
        
        /* Lookbook Grid - Masonry Style */
        .lookbook-grid-section { padding: 80px 0; background: linear-gradient(180deg, #2C1810 0%, #1A0F08 100%); }
        .lookbook-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
        .lookbook-item { 
            position: relative; 
            border-radius: 20px; 
            overflow: hidden; 
            cursor: pointer;
            aspect-ratio: 3/4;
        }
        .lookbook-item.large { grid-column: span 2; aspect-ratio: 3/2; }
        .lookbook-item img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
        .lookbook-item:hover img { transform: scale(1.08); }
        .lookbook-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
            padding: 30px;
            transform: translateY(100%);
            transition: transform 0.4s ease;
        }
        .lookbook-item:hover .lookbook-overlay { transform: translateY(0); }
        .lookbook-overlay h4 { font-size: 22px; font-weight: 700; margin-bottom: 8px; color: #F5E6D3; }
        .lookbook-overlay p { font-size: 13px; color: #D4B5A7; margin-bottom: 10px; }
        .lookbook-overlay a { color: #5C2E1A; text-decoration: none; font-weight: 600; font-size: 13px; }
        .lookbook-overlay a:hover { color: #D4B5A7; }
        
        /* Category Showcase */
        .category-showcase { padding: 80px 0; background: #2C1810; }
        .showcase-card {
            text-align: center;
            padding: 40px 20px;
            background: #3D2314;
            border-radius: 20px;
            border: 1px solid rgba(92,46,26,0.3);
            transition: all 0.3s;
            text-decoration: none;
            display: block;
        }
        .showcase-card:hover { transform: translateY(-8px); border-color: #5C2E1A; }
        .showcase-card i { font-size: 48px; color: #b77b61; margin-bottom: 20px; display: inline-block; }
        .showcase-card h4 { font-size: 18px; font-weight: 700; color: #F5E6D3; margin-bottom: 8px; }
        .showcase-card p { color: #D4B5A7; font-size: 12px; }
        
        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #5C2E1A, #3D2314);
            padding: 80px 0;
            text-align: center;
        }
        .cta-section h2 { font-size: 42px; font-weight: 800; margin-bottom: 20px; }
        .cta-section p { font-size: 16px; color: #D4B5A7; margin-bottom: 30px; max-width: 500px; margin-left: auto; margin-right: auto; }
        .cta-btn { background: #F5E6D3; color: #3D2314; padding: 14px 40px; border-radius: 50px; text-decoration: none; font-weight: 700; transition: all 0.3s; display: inline-block; }
        .cta-btn:hover { background: #D4B5A7; transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        
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
        
        @media (max-width: 992px) { 
            .desktop-nav { display: none; } 
            .mobile-toggle { display: block; } 
            .navbar .container { padding: 10px 20px; } 
            .navbar-right { margin-left: auto; margin-right: 15px; }
            .lookbook-hero h1 { font-size: 48px; }
            .lookbook-grid { grid-template-columns: repeat(2, 1fr); }
            .footer-main { grid-template-columns: repeat(2, 1fr); }
            .footer-brand-section { grid-column: span 2; text-align: center; }
            .footer-logo { justify-content: center; }
            .footer-contact { align-items: center; }
            .footer-newsletter-section { grid-column: span 2; max-width: 100%; text-align: center; }
            .footer-bottom-content { flex-direction: column; text-align: center; }
            .payment-methods { justify-content: center; }
        }
        @media (max-width: 768px) { 
            .lookbook-hero h1 { font-size: 36px; }
            .lookbook-hero { padding: 60px 0; }
            .featured-collection, .lookbook-grid-section, .category-showcase { padding: 60px 0; }
            .section-title h2 { font-size: 32px; }
            .lookbook-grid { grid-template-columns: 1fr; }
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
                <a href="lookbook.php" class="nav-link active"><i class="fas fa-camera"></i> LOOKBOOK</a>
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
            <div class="mobile-nav-item"><a href="lookbook.php" class="mobile-nav-link active"><i class="fas fa-camera"></i> LOOKBOOK</a></div>
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

<!-- Hero Section -->
<section class="lookbook-hero">
    <div class="container">
        <div class="hero-badge" data-aos="fade-down">✨ 2026 COLLECTION ✨</div>
        <h1 data-aos="fade-up" data-aos-delay="100">Style &<br>Inspiration</h1>
        <p data-aos="fade-up" data-aos-delay="200">Explore our curated looks and find your perfect aesthetic</p>
    </div>
</section>

<!-- Featured Collection -->
<section class="featured-collection">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Featured Looks</h2>
            <p>Handpicked styles for every occasion</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                <div class="featured-card">
                    <img src="assets/images/summer.jpg" alt="Summer Look" onerror="this.src='https://placehold.co/400x500/3D2314/D4B5A7?text=Summer+Elegance'">
                    <div class="featured-card-body">
                        <h3>Summer Elegance</h3>
                        <p>Light fabrics, flowy silhouettes</p>
                        <a href="shop.php?category=dresses" class="featured-btn">Shop Now →</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="featured-card">
                    <img src="assets/images/outerware.jpg" alt="Street Style" onerror="this.src='https://placehold.co/400x500/3D2314/D4B5A7?text=Urban+Edge'">
                    <div class="featured-card-body">
                        <h3>Urban Edge</h3>
                        <p>Bold, confident, street-ready</p>
                        <a href="shop.php?category=tops" class="featured-btn">Shop Now →</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="featured-card">
                    <img src="assets/images/Vintage Watch.jpg" alt="Minimalist" onerror="this.src='https://placehold.co/400x500/3D2314/D4B5A7?text=Clean+Cuts'">
                    <div class="featured-card-body">
                        <h3>Clean Cuts</h3>
                        <p>Minimalist, timeless, versatile</p>
                        <a href="shop.php" class="featured-btn">Shop Now →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Lookbook Grid -->
<section class="lookbook-grid-section">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Latest Inspirations</h2>
            <p>Get inspired by our newest looks</p>
        </div>
        <div class="lookbook-grid">
            <div class="lookbook-item" data-aos="fade-up" data-aos-delay="0">
                <img src="assets/images/pastel kurtas suit.jpg" alt="Look 1" onerror="this.src='https://placehold.co/600x800/3D2314/D4B5A7?text=Bohemian+Dream'">
                <div class="lookbook-overlay">
                    <h4>Dress Dream</h4>
                    <p>Flowy dresses & earthy tones</p>
                    <a href="shop.php?category=dresses">Shop Now →</a>
                </div>
            </div>
            <div class="lookbook-item" data-aos="fade-up" data-aos-delay="100">
                <img src="assets/images/Blue Top.jpg" alt="Look 2" onerror="this.src='https://placehold.co/600x800/3D2314/D4B5A7?text=Chic+Office'">
                <div class="lookbook-overlay">
                    <h4>Chic Office</h4>
                    <p>Professional & stylish</p>
                    <a href="shop.php?category=tops">Shop Now →</a>
                </div>
            </div>
            <div class="lookbook-item large" data-aos="fade-up" data-aos-delay="150">
                <img src="assets/images/cozy hood.jpg" alt="Look 3" onerror="this.src='https://placehold.co/1200x800/3D2314/D4B5A7?text=Weekend+Getaway'">
                <div class="lookbook-overlay">
                    <h4>Weekend Getaway</h4>
                    <p>Comfort meets style</p>
                    <a href="shop.php">Shop Now →</a>
                </div>
            </div>
            <div class="lookbook-item" data-aos="fade-up" data-aos-delay="200">
                <img src="assets/images/Casual.jpg" alt="Look 4" onerror="this.src='https://placehold.co/600x800/3D2314/D4B5A7?text=Evening+Glow'">
                <div class="lookbook-overlay">
                    <h4>Evening Glow</h4>
                    <p>Elegant night out</p>
                    <a href="shop.php?category=dresses">Shop Now →</a>
                </div>
            </div>
            <div class="lookbook-item" data-aos="fade-up" data-aos-delay="250">
                <img src="assets/images/Puffer Jacket.jpg" alt="Look 5" onerror="this.src='https://placehold.co/600x800/3D2314/D4B5A7?text=Cozy+Layer'">
                <div class="lookbook-overlay">
                    <h4>Cozy Layers</h4>
                    <p>Warm & comfortable</p>
                    <a href="shop.php?category=outerwear">Shop Now →</a>
                </div>
            </div>
            <div class="lookbook-item" data-aos="fade-up" data-aos-delay="300">
                <img src="assets/images/Y2K shirt.jpg" alt="Look 6" onerror="this.src='https://placehold.co/600x800/3D2314/D4B5A7?text=Active+Wear'">
                <div class="lookbook-overlay">
                    <h4>Active Lifestyle</h4>
                    <p>Style meets performance</p>
                    <a href="shop.php">Shop Now →</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Shop by Category -->
<section class="category-showcase">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Shop by Category</h2>
            <p>Find what speaks to your style</p>
        </div>
        <div class="row g-4">
            <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="0">
                <a href="shop.php?category=dresses" class="showcase-card">
                    <i class="fas fa-female"></i>
                    <h4>Dresses</h4>
                    <p>Shop Now →</p>
                </a>
            </div>
            <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="100">
                <a href="shop.php?category=tops" class="showcase-card">
                    <i class="fas fa-tshirt"></i>
                    <h4>Tops</h4>
                    <p>Shop Now →</p>
                </a>
            </div>
            <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="200">
                <a href="shop.php?category=outerwear" class="showcase-card">
                    <i class="fas fa-vest"></i>
                    <h4>Outerwear</h4>
                    <p>Shop Now →</p>
                </a>
            </div>
            <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="300">
                <a href="shop.php?category=accessories" class="showcase-card">
                    <i class="fas fa-gem"></i>
                    <h4>Accessories</h4>
                    <p>Shop Now →</p>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <h2 data-aos="fade-up">Ready to Elevate Your Style?</h2>
        <p data-aos="fade-up" data-aos-delay="100">Discover pieces that speak to your soul</p>
        <a href="shop.php" class="cta-btn" data-aos="fade-up" data-aos-delay="200">Shop Now →</a>
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
                <p class="footer-description">Ethical fashion for the conscious soul. Timeless pieces designed to last beyond seasons.</p>
                <div class="footer-contact">
                    <div class="contact-item"><i class="fas fa-envelope"></i><a href="mailto:hello@velvetaura.com">hello@velvetaura.com</a></div>
                    <div class="contact-item"><i class="fas fa-phone-alt"></i><a href="tel:+15551234567">+1 (555) 123-4567</a></div>
                    <div class="contact-item"><i class="fas fa-map-marker-alt"></i><span>New York, NY</span></div>
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
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <div class="copyright"><i class="far fa-copyright"></i> <?php echo date('Y'); ?> Velvet Aura — All rights reserved.</div>
                <div class="payment-methods"><i class="fab fa-cc-visa"></i><i class="fab fa-cc-mastercard"></i><i class="fab fa-cc-amex"></i><i class="fab fa-cc-paypal"></i></div>
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
        document.querySelectorAll('a, button, .featured-card, .lookbook-item, .showcase-card, .icon-btn').forEach(el => {
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
    
    function showToast(msg, ok = true) {
        const wrap = document.getElementById('toastWrap');
        const toast = document.createElement('div');
        toast.className = 'toast-msg';
        toast.innerHTML = `<i class="fas ${ok ? 'fa-check-circle' : 'fa-exclamation-circle'}" style="color:#5C2E1A;"></i> ${msg}`;
        wrap.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, 2500);
    }
    
    document.getElementById('footerNewsletterForm')?.addEventListener('submit', (e) => {
        e.preventDefault();
        showToast('Thanks for subscribing! Check your email for 15% off ✨');
        e.target.reset();
    });
</script>
</body>
</html>