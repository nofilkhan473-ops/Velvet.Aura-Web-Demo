<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'backend/config/database.php';
require_once 'backend/includes/functions.php';

$is_logged_in = isLoggedIn();

// Check if user is logged in
if (!$is_logged_in) {
    $_SESSION['redirect_after_login'] = 'my-orders.php';
    header('Location: login.php?redirect=my-orders.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all orders for this user
$orders_query = "SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC";
$orders_result = mysqli_query($conn, $orders_query);

if (!$orders_result) {
    // If table doesn't exist, create it
    $create_table = "CREATE TABLE IF NOT EXISTS orders (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_number VARCHAR(50) UNIQUE NOT NULL,
        user_id INT,
        full_name VARCHAR(200) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        address TEXT NOT NULL,
        city VARCHAR(100) NOT NULL,
        state VARCHAR(100) NOT NULL,
        zip VARCHAR(20) NOT NULL,
        country VARCHAR(100) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        shipping DECIMAL(10,2) DEFAULT 0,
        total DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        order_status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
        tracking_number VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    mysqli_query($conn, $create_table);
    
    $create_items_table = "CREATE TABLE IF NOT EXISTS order_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        product_name VARCHAR(200) NOT NULL,
        product_price DECIMAL(10,2) NOT NULL,
        quantity INT NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $create_items_table);
    
    $orders_result = mysqli_query($conn, $orders_query);
}

$orders = mysqli_fetch_all($orders_result, MYSQLI_ASSOC);

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

// Order stats
$total_orders = count($orders);
$total_spent = array_sum(array_column($orders, 'total'));
$pending_orders = count(array_filter($orders, function($o) { return $o['order_status'] == 'pending'; }));
$delivered_orders = count(array_filter($orders, function($o) { return $o['order_status'] == 'delivered'; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Velvet Aura</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0F0A08; color: #F5E6D3; cursor: none; overflow-x: hidden; }
        
        .cursor-dot { width: 8px; height: 8px; background: #D4A574; border-radius: 50%; position: fixed; pointer-events: none; z-index: 99999; }
        .cursor-outline { width: 40px; height: 40px; border: 2px solid #D4A574; border-radius: 50%; position: fixed; pointer-events: none; z-index: 99998; transition: all 0.15s ease; }
        
        .bg-animation { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2; background: linear-gradient(135deg, #0F0A08 0%, #1A0F08 50%, #0F0A08 100%); }
        .bg-animation::before { content: ''; position: absolute; width: 100%; height: 100%; background: repeating-linear-gradient(45deg, transparent, transparent 40px, rgba(212,165,116,0.03) 40px, rgba(212,165,116,0.03) 80px); pointer-events: none; }
        
        .orb { position: absolute; border-radius: 50%; filter: blur(60px); opacity: 0.4; animation: float 20s infinite ease-in-out; }
        .orb-1 { width: 300px; height: 300px; background: radial-gradient(circle, #5C2E1A, transparent); top: 10%; left: -100px; }
        .orb-2 { width: 400px; height: 400px; background: radial-gradient(circle, #D4A574, transparent); bottom: -150px; right: -150px; animation-delay: -5s; }
        .orb-3 { width: 250px; height: 250px; background: radial-gradient(circle, #3D2314, transparent); top: 50%; left: 40%; animation-delay: -10s; }
        .orb-4 { width: 200px; height: 200px; background: radial-gradient(circle, #8B6B4A, transparent); bottom: 20%; left: 20%; animation-delay: -15s; }
        @keyframes float { 0%,100% { transform: translate(0,0) scale(1); } 25% { transform: translate(50px,-50px) scale(1.1); } 50% { transform: translate(-30px,30px) scale(0.9); } 75% { transform: translate(30px,50px) scale(1.05); } }
        
        .top-bar { background: linear-gradient(135deg, #5C2E1A, #8B4513); padding: 8px 0; text-align: center; font-size: 11px; letter-spacing: 2px; color: #F5E6D3; text-transform: uppercase; font-weight: 500; }
        
        .navbar { background: rgba(61, 35, 20, 0.95); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(92,46,26,0.3); padding: 0; position: sticky; top: 0; z-index: 1000; }
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
        
        .dropdown { position: absolute; top: 100%; left: 0; background: rgba(44,24,16,0.95); backdrop-filter: blur(10px); min-width: 240px; border-radius: 12px; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.3s ease; z-index: 100; border: 1px solid rgba(92,46,26,0.3); box-shadow: 0 15px 30px rgba(0,0,0,0.3); }
        .nav-item:hover .dropdown { opacity: 1; visibility: visible; transform: translateY(0); }
        .dropdown a { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #D4B5A7; text-decoration: none; font-size: 12px; transition: all 0.3s ease; }
        .dropdown a i { width: 20px; font-size: 12px; }
        .dropdown a:hover { background: #3D2314; color: #F5E6D3; padding-left: 28px; }
        .dropdown-divider { height: 1px; background: rgba(92,46,26,0.2); margin: 5px 0; }
        .dropdown-header { padding: 10px 20px; font-size: 10px; font-weight: 700; letter-spacing: 1px; color: #D4B5A7; text-transform: uppercase; background: rgba(44,24,16,0.5); border-radius: 12px 12px 0 0; }
        
        .dropdown-products { width: 520px; padding: 15px; }
        .dropdown-products .product-item { display: flex; align-items: center; gap: 10px; padding: 8px; border-radius: 8px; transition: all 0.3s; text-decoration: none; }
        .dropdown-products .product-item:hover { background: #3D2314; transform: translateX(5px); }
        .dropdown-products img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
        .dropdown-products .product-name { font-size: 12px; font-weight: 600; color: #D4B5A7; }
        .dropdown-products .product-price { font-size: 11px; color: #F5E6D3; }
        
        .mobile-toggle { display: none; background: transparent; border: none; color: #D4B5A7; font-size: 24px; cursor: pointer; padding: 10px; }
        .mobile-menu { display: none; width: 100%; background: rgba(44,24,16,0.95); backdrop-filter: blur(10px); border-top: 1px solid rgba(92,46,26,0.2); padding: 15px 20px; }
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
        .icon-btn:hover { color: #F5E6D3; background: rgba(212,165,116,0.1); transform: translateY(-2px); }
        .badge-count { position: absolute; top: -3px; right: -3px; background: #D4A574; color: #2C1810; font-size: 9px; font-weight: 700; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        
        .page-header { background: linear-gradient(135deg, #3D2314 0%, #2C1810 100%); padding: 80px 0; text-align: center; position: relative; overflow: hidden; }
        .page-header::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(92,46,26,0.15) 0%, transparent 70%); animation: rotate 20s linear infinite; }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .page-header h1 { font-size: 56px; font-weight: 800; margin-bottom: 15px; color: #F5E6D3; animation: fadeInUp 0.8s ease; }
        .page-header p { color: #D4B5A7; font-size: 18px; animation: fadeInUp 1s ease; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        
        .orders-section { padding: 60px 0; background: #2C1810; min-height: 60vh; }
        
        .order-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: rgba(61, 35, 20, 0.8); backdrop-filter: blur(10px); border-radius: 20px; padding: 20px; text-align: center; transition: all 0.3s; border: 1px solid rgba(212,165,116,0.2); }
        .stat-card:hover { transform: translateY(-5px); border-color: #D4A574; }
        .stat-card i { font-size: 32px; color: #D4A574; margin-bottom: 10px; }
        .stat-card h3 { font-size: 28px; font-weight: 800; margin-bottom: 5px; color: #F5E6D3; }
        .stat-card p { color: #C4A484; font-size: 12px; }
        
        .order-card { background: rgba(61, 35, 20, 0.8); backdrop-filter: blur(10px); border-radius: 24px; padding: 25px; margin-bottom: 25px; border: 1px solid rgba(212,165,116,0.2); transition: all 0.3s; }
        .order-card:hover { transform: translateY(-5px); border-color: #D4A574; box-shadow: 0 15px 35px rgba(0,0,0,0.3); }
        .order-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; padding-bottom: 18px; border-bottom: 1px solid rgba(212,165,116,0.1); margin-bottom: 18px; }
        .order-info { display: flex; flex-wrap: wrap; gap: 25px; }
        .order-info-item .label { font-size: 11px; color: #C4A484; margin-bottom: 4px; letter-spacing: 1px; }
        .order-info-item .value { font-weight: 700; font-size: 14px; color: #F5E6D3; }
        .order-status { padding: 6px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
        .status-pending { background: rgba(255,193,7,0.2); color: #FFC107; border: 1px solid rgba(255,193,7,0.3); }
        .status-processing { background: rgba(13,110,253,0.2); color: #0d6efd; border: 1px solid rgba(13,110,253,0.3); }
        .status-shipped { background: rgba(13,202,240,0.2); color: #0dcaf0; border: 1px solid rgba(13,202,240,0.3); }
        .status-delivered { background: rgba(25,135,84,0.2); color: #198754; border: 1px solid rgba(25,135,84,0.3); }
        .status-cancelled { background: rgba(220,53,69,0.2); color: #dc3545; border: 1px solid rgba(220,53,69,0.3); }
        
        .order-body { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .order-items-preview { display: flex; gap: 10px; align-items: center; }
        .order-items-count { background: rgba(15,10,8,0.6); padding: 6px 12px; border-radius: 20px; font-size: 12px; }
        .order-total .label { font-size: 11px; color: #C4A484; }
        .order-total .value { font-size: 24px; font-weight: 800; color: #D4A574; }
        .order-actions { display: flex; gap: 12px; }
        .btn-track { background: linear-gradient(135deg, #5C2E1A, #8B4513); color: #F5E6D3; padding: 10px 24px; border-radius: 40px; text-decoration: none; font-size: 12px; font-weight: 600; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-track:hover { background: #D4A574; color: #2C1810; transform: translateY(-2px); }
        .btn-reorder { background: transparent; border: 1px solid #5C2E1A; color: #D4B5A7; padding: 10px 24px; border-radius: 40px; text-decoration: none; font-size: 12px; font-weight: 600; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-reorder:hover { background: #5C2E1A; color: #F5E6D3; transform: translateY(-2px); }
        
        .empty-state { text-align: center; padding: 80px 40px; background: rgba(61,35,20,0.8); backdrop-filter: blur(10px); border-radius: 24px; border: 1px solid rgba(212,165,116,0.2); }
        .empty-state i { font-size: 80px; color: #D4A574; margin-bottom: 20px; display: inline-block; animation: float 3s ease-in-out infinite; }
        @keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        .empty-state h3 { font-size: 28px; font-weight: 800; color: #F5E6D3; margin-bottom: 10px; }
        .empty-state p { color: #C4A484; margin-bottom: 30px; }
        .btn-shop { background: #D4A574; color: #2C1810; padding: 14px 40px; border-radius: 50px; text-decoration: none; font-weight: 700; display: inline-block; transition: all 0.3s; }
        .btn-shop:hover { background: #F5E6D3; transform: translateY(-3px); }
        
        .toast-wrap { position: fixed; bottom: 30px; right: 30px; z-index: 9999; }
        .toast-msg { background: #3D2314; border-left: 3px solid #D4A574; padding: 12px 20px; margin-top: 8px; border-radius: 12px; color: #F5E6D3; font-size: 12px; transform: translateX(120%); transition: transform 0.3s; }
        .toast-msg.show { transform: translateX(0); }
        
        .footer { background: linear-gradient(135deg, #0F0A08 0%, #1A0F08 100%); padding: 60px 0 0; border-top: 1px solid rgba(92,46,26,0.2); margin-top: 60px; }
        .footer-main { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 50px; }
        .footer-brand-section { max-width: 320px; }
        .footer-logo { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
        .logo-icon { font-size: 28px; color: #5C2E1A; }
        .logo-text { font-size: 22px; font-weight: 800; letter-spacing: 3px; color: #D4B5A7; }
        .logo-text span { color: #F5E6D3; }
        .footer-description { color: #A08874; font-size: 13px; line-height: 1.6; margin-bottom: 20px; }
        .footer-contact { display: flex; flex-direction: column; gap: 12px; }
        .contact-item { display: flex; align-items: center; gap: 12px; font-size: 12px; color: #A08874; }
        .contact-item i { width: 20px; color: #D4A574; }
        .contact-item a { color: #A08874; text-decoration: none; transition: color 0.3s; }
        .contact-item a:hover { color: #D4A574; }
        .footer-title { font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: #D4B5A7; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid rgba(92,46,26,0.2); }
        .footer-title::after { content: ''; position: absolute; bottom: -1px; left: 0; width: 40px; height: 2px; background: #D4A574; }
        .footer-links-section ul { list-style: none; display: flex; flex-direction: column; gap: 12px; }
        .footer-links-section ul li a { display: flex; align-items: center; gap: 10px; color: #A08874; text-decoration: none; font-size: 13px; transition: all 0.3s; }
        .footer-links-section ul li a i { font-size: 10px; }
        .footer-links-section ul li a:hover { color: #D4A574; transform: translateX(5px); }
        .footer-newsletter-section { max-width: 350px; }
        .newsletter-text { color: #A08874; font-size: 12px; margin-bottom: 15px; }
        .footer-newsletter-form { margin-bottom: 20px; }
        .input-group { display: flex; background: #3D2314; border: 1px solid rgba(92,46,26,0.3); border-radius: 50px; overflow: hidden; }
        .input-group input { flex: 1; background: transparent; border: none; padding: 12px 18px; font-size: 12px; color: #F5E6D3; outline: none; }
        .input-group input::placeholder { color: #8B6B4A; }
        .input-group button { background: #5C2E1A; border: none; padding: 0 20px; color: #F5E6D3; cursor: pointer; }
        .input-group button:hover { background: #D4A574; }
        .footer-social { display: flex; gap: 12px; }
        .social-icon { width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; background: #3D2314; border-radius: 50%; color: #D4B5A7; font-size: 16px; text-decoration: none; transition: all 0.3s; border: 1px solid rgba(92,46,26,0.3); }
        .social-icon:hover { background: #5C2E1A; color: #F5E6D3; transform: translateY(-3px); border-color: #D4A574; }
        .footer-bottom { padding: 25px 0; border-top: 1px solid rgba(92,46,26,0.15); text-align: center; }
        .copyright { font-size: 11px; color: #8B6B4A; }
        
        @media (max-width: 992px) { 
            .desktop-nav { display: none; } 
            .mobile-toggle { display: block; } 
            .navbar .container { padding: 10px 20px; } 
            .navbar-right { margin-left: auto; margin-right: 15px; }
            .page-header h1 { font-size: 48px; }
            .order-stats { grid-template-columns: repeat(2, 1fr); }
            .footer-main { grid-template-columns: repeat(2, 1fr); }
            .footer-brand-section { grid-column: span 2; text-align: center; }
            .footer-logo { justify-content: center; }
            .footer-contact { align-items: center; }
            .footer-newsletter-section { grid-column: span 2; max-width: 100%; text-align: center; }
        }
        @media (max-width: 768px) { 
            .page-header h1 { font-size: 36px; }
            .order-header { flex-direction: column; align-items: flex-start; }
            .order-body { flex-direction: column; align-items: flex-start; }
            .order-actions { width: 100%; }
            .btn-track, .btn-reorder { flex: 1; text-align: center; justify-content: center; }
            .order-stats { grid-template-columns: 1fr; }
            .footer-main { grid-template-columns: 1fr; }
            .footer-brand-section { grid-column: span 1; text-align: center; }
            .footer-newsletter-section { grid-column: span 1; }
        }
    </style>
</head>
<body>

<div class="bg-animation"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>
<div class="orb orb-4"></div>

<div class="cursor-dot"></div>
<div class="cursor-outline"></div>

<div class="top-bar">✨ FREE SHIPPING ON ORDERS OVER $100 ✦ ETHICAL FASHION ✦ 30-DAY RETURNS ✨</div>

<nav class="navbar">
    <div class="container">
        <a class="navbar-brand" href="index.php">VELVET<span>.</span>AURA</a>
        
        <div class="desktop-nav">
            <div class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-home"></i> HOME</a>
                <div class="dropdown"><a href="index.php#newArrivals"><i class="fas fa-sparkles"></i> New Arrivals</a><a href="index.php#bestSellers"><i class="fas fa-fire"></i> Best Sellers</a><a href="index.php#categories"><i class="fas fa-th-large"></i> Shop by Category</a><a href="index.php#featured"><i class="fas fa-gem"></i> Featured Collection</a><div class="dropdown-divider"></div><a href="lookbook.php"><i class="fas fa-camera"></i> Lookbook</a><a href="about.php"><i class="fas fa-heart"></i> About Us</a></div>
            </div>
            <div class="nav-item"><a href="shop.php" class="nav-link"><i class="fas fa-store"></i> SHOP</a>
                <div class="dropdown dropdown-products"><div class="dropdown-header"><i class="fas fa-gem"></i> ✨ FEATURED PRODUCTS</div><div class="row g-2 p-2"><?php $count = 0; foreach($dropdown_products as $prod): if($count++ >= 4) break; ?><div class="col-6"><a href="product-detail.php?id=<?php echo $prod['id']; ?>" class="product-item"><img src="assets/images/<?php echo htmlspecialchars($prod['image']); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>" onerror="this.src='https://placehold.co/50x50/3D2314/5C2E1A?text=VA'"><div><div class="product-name"><?php echo htmlspecialchars($prod['name']); ?></div><div class="product-price">$<?php echo number_format($prod['price'], 2); ?></div></div></a></div><?php endforeach; ?></div><div class="dropdown-divider"></div><div class="dropdown-header"><i class="fas fa-tags"></i> 📁 SHOP BY CATEGORY</div><?php foreach($db_categories as $cat): ?><a href="shop.php?category=<?php echo htmlspecialchars($cat['slug']); ?>"><i class="fas fa-chevron-right"></i> <?php echo htmlspecialchars($cat['name']); ?></a><?php endforeach; ?><div class="dropdown-divider"></div><a href="shop.php"><i class="fas fa-bag-shopping"></i> View All Products →</a><a href="shop.php?filter=new"><i class="fas fa-sparkles"></i> New Arrivals</a><a href="shop.php?filter=bestseller"><i class="fas fa-crown"></i> Best Sellers</a></div>
            </div>
            <div class="nav-item"><a href="lookbook.php" class="nav-link"><i class="fas fa-camera"></i> LOOKBOOK</a></div>
            <div class="nav-item"><a href="about.php" class="nav-link"><i class="fas fa-info-circle"></i> ABOUT</a>
                <div class="dropdown"><a href="about.php#our-story"><i class="fas fa-leaf"></i> Our Story</a><a href="about.php#sustainability"><i class="fas fa-globe"></i> Sustainability</a><a href="about.php#careers"><i class="fas fa-briefcase"></i> Careers</a><div class="dropdown-divider"></div><a href="contact.php"><i class="fas fa-envelope"></i> Contact Us</a></div>
            </div>
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
        <h1 data-aos="fade-up">My Orders</h1>
        <p data-aos="fade-up" data-aos-delay="100">Track and manage your orders</p>
    </div>
</section>

<section class="orders-section">
    <div class="container">
        <?php if(empty($orders)): ?>
            <div class="empty-state" data-aos="fade-up">
                <i class="fas fa-shopping-bag"></i>
                <h3>No Orders Yet</h3>
                <p>You haven't placed any orders yet. Start shopping to see your orders here!</p>
                <a href="shop.php" class="btn-shop">Start Shopping →</a>
            </div>
        <?php else: ?>
            <div class="order-stats">
                <div class="stat-card" data-aos="fade-up" data-aos-delay="0">
                    <i class="fas fa-shopping-cart"></i>
                    <h3><?php echo $total_orders; ?></h3>
                    <p>Total Orders</p>
                </div>
                <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                    <i class="fas fa-dollar-sign"></i>
                    <h3>$<?php echo number_format($total_spent, 0); ?></h3>
                    <p>Total Spent</p>
                </div>
                <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                    <i class="fas fa-clock"></i>
                    <h3><?php echo $pending_orders; ?></h3>
                    <p>Pending Orders</p>
                </div>
                <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
                    <i class="fas fa-check-circle"></i>
                    <h3><?php echo $delivered_orders; ?></h3>
                    <p>Delivered</p>
                </div>
            </div>
            
            <?php foreach($orders as $index => $order): 
                $items_query = "SELECT COUNT(*) as count FROM order_items WHERE order_id = {$order['id']}";
                $items_result = mysqli_query($conn, $items_query);
                $items_count = mysqli_fetch_assoc($items_result)['count'];
            ?>
                <div class="order-card" data-aos="fade-up" data-aos-delay="<?php echo $index * 50; ?>">
                    <div class="order-header">
                        <div class="order-info">
                            <div class="order-info-item">
                                <span class="label">ORDER NUMBER</span>
                                <span class="value">#<?php echo $order['order_number']; ?></span>
                            </div>
                            <div class="order-info-item">
                                <span class="label">ORDER DATE</span>
                                <span class="value"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="order-info-item">
                                <span class="label">PAYMENT</span>
                                <span class="value"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></span>
                            </div>
                        </div>
                        <div>
                            <span class="order-status status-<?php echo $order['order_status']; ?>">
                                <i class="fas <?php 
                                    echo $order['order_status'] == 'pending' ? 'fa-clock' : 
                                        ($order['order_status'] == 'processing' ? 'fa-cog' : 
                                        ($order['order_status'] == 'shipped' ? 'fa-truck' : 
                                        ($order['order_status'] == 'delivered' ? 'fa-check-circle' : 'fa-times-circle'))); 
                                ?>"></i>
                                <?php echo ucfirst($order['order_status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="order-body">
                        <div class="order-items-preview">
                            <i class="fas fa-box" style="color: #D4A574;"></i>
                            <span class="order-items-count"><?php echo $items_count; ?> item(s)</span>
                        </div>
                        <div class="order-total">
                            <div class="label">Total Amount</div>
                            <div class="value">$<?php echo number_format($order['total'], 2); ?></div>
                        </div>
                        <div class="order-actions">
                            <a href="order-tracking.php?id=<?php echo $order['id']; ?>" class="btn-track">
                                <i class="fas fa-map-marker-alt"></i> Track Order
                            </a>
                            <a href="shop.php" class="btn-reorder">
                                <i class="fas fa-redo-alt"></i> Reorder
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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
        <div class="footer-bottom"><div class="copyright"><i class="far fa-copyright"></i> <?php echo date('Y'); ?> Velvet Aura. All rights reserved.</div></div>
    </div>
</footer>

<div class="toast-wrap" id="toastWrap"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: true, offset: 50 });
    
    const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
    
    const cursorDot = document.querySelector('.cursor-dot');
    const cursorOutline = document.querySelector('.cursor-outline');
    if (cursorDot && cursorOutline) {
        window.addEventListener('mousemove', function(e) {
            cursorDot.style.transform = `translate(${e.clientX - 4}px, ${e.clientY - 4}px)`;
            cursorOutline.style.transform = `translate(${e.clientX - 20}px, ${e.clientY - 20}px)`;
        });
        document.querySelectorAll('a, button, .icon-btn, .order-card, .stat-card').forEach(el => {
            el.addEventListener('mouseenter', () => { cursorOutline.style.transform = `scale(1.5)`; cursorOutline.style.background = 'rgba(212,165,116,0.1)'; cursorOutline.style.borderColor = '#F5E6D3'; });
            el.addEventListener('mouseleave', () => { cursorOutline.style.transform = `scale(1)`; cursorOutline.style.background = 'transparent'; cursorOutline.style.borderColor = '#D4A574'; });
        });
    }
    
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
        toast.innerHTML = `<i class="fas ${ok ? 'fa-check-circle' : 'fa-exclamation-circle'}" style="color:#D4A574;"></i> ${msg}`;
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