<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();
require_once 'backend/config/database.php';
require_once 'backend/includes/functions.php';

$is_logged_in = isLoggedIn();

// Get categories
$categories_query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);
$db_categories = $categories_result ? mysqli_fetch_all($categories_result, MYSQLI_ASSOC) : [];

// Get products for dropdown
$products_query = "SELECT * FROM products WHERE in_stock = 1 ORDER BY created_at DESC LIMIT 6";
$products_result = mysqli_query($conn, $products_query);
$dropdown_products = $products_result ? mysqli_fetch_all($products_result, MYSQLI_ASSOC) : [];

// Sanitize and validate inputs
$category_slug = isset($_GET['category']) ? trim($_GET['category']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 500;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';

$allowed_sorts = ['default', 'price-low', 'price-high', 'newest'];
if (!in_array($sort, $allowed_sorts)) {
    $sort = 'default';
}

$where = "1=1";
$params = [];
$types = "";

if (!empty($category_slug)) {
    $where .= " AND c.slug = ?";
    $params[] = $category_slug;
    $types .= "s";
}

if (!empty($search)) {
    $where .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

$where .= " AND p.price BETWEEN ? AND ?";
$params[] = $min_price;
$params[] = $max_price;
$types .= "dd";

$order = "p.created_at DESC";
if ($sort == 'price-low') $order = "p.price ASC";
if ($sort == 'price-high') $order = "p.price DESC";
if ($sort == 'newest') $order = "p.created_at DESC";

$query = "SELECT p.*, c.name as category_name, c.slug as category_slug 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE $where 
          ORDER BY $order";

$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$products = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$wishlist_ids = [];
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $wishlist_stmt = mysqli_prepare($conn, "SELECT product_id FROM wishlist WHERE user_id = ?");
    if ($wishlist_stmt) {
        mysqli_stmt_bind_param($wishlist_stmt, "i", $user_id);
        mysqli_stmt_execute($wishlist_stmt);
        $wishlist_result = mysqli_stmt_get_result($wishlist_stmt);
        $wishlist_ids = array_column(mysqli_fetch_all($wishlist_result, MYSQLI_ASSOC), 'product_id');
        mysqli_stmt_close($wishlist_stmt);
    }
}

$cart_count = getCartCount();
$wishlist_count = getWishlistCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop — Velvet Aura</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
   <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #2C1810; color: #F5E6D3; cursor: none; }
        .cursor-dot { width: 8px; height: 8px; background: #D4A574; border-radius: 50%; position: fixed; pointer-events: none; z-index: 99999; }
        .cursor-outline { width: 40px; height: 40px; border: 2px solid #D4A574; border-radius: 50%; position: fixed; pointer-events: none; z-index: 99998; transition: all 0.15s ease; }
        .top-bar { background: #5C2E1A; padding: 8px 0; text-align: center; font-size: 11px; letter-spacing: 2px; color: #D4A574; text-transform: uppercase; font-weight: 500; }
        .navbar { background: #3D2314; border-bottom: 1px solid #5C3A24; padding: 0; position: sticky; top: 0; z-index: 1000; }
        .navbar .container { display: flex; justify-content: space-between; align-items: center; padding: 0 40px; flex-wrap: wrap; }
        .navbar-brand { font-family: 'Inter', sans-serif; font-size: 22px; font-weight: 800; letter-spacing: 3px; color: #D4A574 !important; text-decoration: none; text-transform: uppercase; }
        .navbar-brand span { color: #F5E6D3; }
        .desktop-nav { display: flex; gap: 40px; margin: 0 auto; }
        .nav-item { position: relative; }
        .nav-link { display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #D4A574 !important; text-decoration: none; padding: 28px 0; transition: all 0.3s ease; }
        .nav-link i { font-size: 16px; }
        .nav-link:hover { color: #F5E6D3 !important; transform: translateY(-2px); }
        .dropdown { position: absolute; top: 100%; left: 0; background: #3D2314; min-width: 240px; border-radius: 12px; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.3s ease; z-index: 100; border: 1px solid #5C3A24; box-shadow: 0 15px 30px rgba(0,0,0,0.3); }
        .nav-item:hover .dropdown { opacity: 1; visibility: visible; transform: translateY(0); }
        .dropdown a { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #D4A574; text-decoration: none; font-size: 13px; }
        .dropdown a:hover { background: #5C3A24; color: #F5E6D3; }
        .dropdown-divider { height: 1px; background: #5C3A24; margin: 5px 0; }
        .dropdown-header { padding: 10px 20px; font-size: 11px; font-weight: 700; color: #F5E6D3; text-transform: uppercase; background: #2C1810; border-radius: 12px 12px 0 0; }
        .mobile-toggle { display: none; background: transparent; border: none; color: #D4A574; font-size: 24px; cursor: pointer; padding: 10px; }
        .mobile-menu { display: none; width: 100%; background: #3D2314; border-top: 1px solid #5C3A24; padding: 15px 20px; }
        .mobile-menu.active { display: block; }
        .mobile-nav-item { border-bottom: 1px solid #5C3A24; }
        .mobile-nav-link { display: flex; align-items: center; gap: 12px; padding: 14px 0; color: #D4A574; text-decoration: none; font-size: 14px; font-weight: 600; }
        .mobile-dropdown-toggle { margin-left: auto; cursor: pointer; transition: transform 0.3s; }
        .mobile-dropdown-toggle.rotated { transform: rotate(180deg); }
        .mobile-dropdown { display: none; padding-left: 36px; padding-bottom: 10px; }
        .mobile-dropdown.active { display: block; }
        .mobile-dropdown a { display: flex; align-items: center; gap: 12px; padding: 10px 0; color: #C4A484; text-decoration: none; font-size: 13px; }
        .navbar-right { display: flex; gap: 12px; align-items: center; }
        .icon-btn { position: relative; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; color: #D4A574; font-size: 18px; transition: all 0.3s; border-radius: 50%; text-decoration: none; }
        .icon-btn:hover { color: #F5E6D3; background: rgba(212,165,116,0.1); transform: translateY(-2px); }
        .badge-count { position: absolute; top: -3px; right: -3px; background: #D4A574; color: #3D2314; font-size: 9px; font-weight: 700; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .shop-hero { background: #5C2E1A; padding: 40px 0; text-align: center; }
        .hero-badge { font-size: 11px; letter-spacing: 3px; color: #D4A574; margin-bottom: 15px; }
        .hero-title { font-size: 42px; font-weight: 800; color: #F5E6D3; margin-bottom: 15px; }
        .hero-title em { font-style: italic; color: #D4A574; }
        .filter-bar { background: #3D2314; border-bottom: 1px solid #5C3A24; padding: 15px 0; position: sticky; top: 70px; z-index: 99; }
        .filter-wrapper { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .category-filters { display: flex; gap: 8px; flex-wrap: wrap; }
        .filter-chip { padding: 6px 16px; font-size: 11px; font-weight: 600; background: transparent; border: 1px solid #5C3A24; color: #D4A574; cursor: pointer; border-radius: 30px; transition: all 0.3s; }
        .filter-chip:hover, .filter-chip.active { background: #D4A574; border-color: #D4A574; color: #3D2314; transform: translateY(-2px); }
        .filter-controls { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        .price-slider { display: flex; align-items: center; gap: 12px; }
        .price-slider label { font-size: 11px; color: #D4A574; }
        .price-slider input { width: 130px; height: 3px; -webkit-appearance: none; background: #5C3A24; }
        .price-slider input::-webkit-slider-thumb { -webkit-appearance: none; width: 14px; height: 14px; background: #D4A574; border-radius: 50%; cursor: pointer; }
        .price-value { font-size: 12px; color: #D4A574; min-width: 45px; }
        .sort-select { background: transparent; border: 1px solid #5C3A24; padding: 6px 12px; font-size: 11px; color: #D4A574; cursor: pointer; border-radius: 30px; }
        .reset-btn { background: transparent; border: 1px solid #5C3A24; padding: 6px 12px; font-size: 11px; color: #D4A574; cursor: pointer; border-radius: 30px; transition: all 0.3s; }
        .reset-btn:hover { border-color: #D4A574; color: #F5E6D3; }
        
        /* Products Section */
        .products-section { padding: 60px 0 100px; background: linear-gradient(180deg, #2C1810 0%, #1A0F08 100%); }
        .results-count { font-size: 13px; color: #D4A574; margin-bottom: 30px; letter-spacing: 1px; border-left: 3px solid #D4A574; padding-left: 15px; }
        .results-count span { font-weight: 700; color: #F5E6D3; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; }
        
        .product-card { background: rgba(61, 35, 20, 0.9); backdrop-filter: blur(5px); border-radius: 24px; overflow: hidden; transition: all 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1); border: 1px solid rgba(212, 165, 116, 0.2); height: 100%; display: flex; flex-direction: column; position: relative; }
        .product-card::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(212,165,116,0.1), transparent); transition: left 0.5s; z-index: 1; }
        .product-card:hover::before { left: 100%; }
        .product-card:hover { transform: translateY(-8px); border-color: #D4A574; box-shadow: 0 20px 40px rgba(0,0,0,0.4); }
        
        .card-media { position: relative; height: 340px; overflow: hidden; background: linear-gradient(135deg, #3D2314, #2C1810); }
        .card-media img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .product-card:hover .card-media img { transform: scale(1.08); }
        
        .card-badges { position: absolute; top: 15px; left: 15px; display: flex; gap: 10px; z-index: 2; }
        .badge { padding: 5px 14px; font-size: 9px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; border-radius: 30px; backdrop-filter: blur(5px); }
        .badge-new { background: rgba(212, 165, 116, 0.95); color: #3D2314; }
        .badge-sale { background: rgba(139, 69, 19, 0.95); color: #F5E6D3; }
        
        .card-actions { position: absolute; bottom: -60px; left: 0; right: 0; display: flex; justify-content: center; gap: 15px; padding: 15px; background: linear-gradient(to top, rgba(0,0,0,0.9), transparent); transition: bottom 0.35s ease; z-index: 2; }
        .product-card:hover .card-actions { bottom: 0; }
        
        .action-btn { width: 44px; height: 44px; background: #F5E6D3; border: none; border-radius: 50%; color: #3D2314; font-size: 16px; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        .action-btn:hover { background: #D4A574; transform: scale(1.1) translateY(-3px); }
        .action-btn.active { background: #D4A574; color: #3D2314; }
        .action-btn.quick-view { background: #3D2314; color: #D4A574; border: 1px solid #D4A574; }
        
        .card-info { padding: 20px; flex: 1; display: flex; flex-direction: column; background: #3D2314; }
        .card-category { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: #D4A574; margin-bottom: 8px; font-weight: 500; }
        .card-title { font-size: 17px; font-weight: 700; color: #F5E6D3; margin-bottom: 10px; line-height: 1.4; transition: color 0.3s; }
        .product-card:hover .card-title { color: #D4A574; }
        .card-rating { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
        .stars i { font-size: 11px; color: #5C3A24; }
        .stars i.filled { color: #D4A574; }
        
        .card-price { display: flex; align-items: baseline; gap: 10px; margin-bottom: 18px; }
        .current-price { font-size: 20px; font-weight: 800; color: #D4A574; }
        .old-price { font-size: 13px; text-decoration: line-through; color: #8B6B4A; }
        
        .add-to-bag-btn { width: 100%; background: linear-gradient(135deg, #D4A574, #C4956A); border: none; padding: 12px; font-size: 11px; font-weight: 700; letter-spacing: 2.5px; text-transform: uppercase; color: #3D2314; cursor: pointer; transition: all 0.3s ease; border-radius: 50px; margin-top: auto; position: relative; overflow: hidden; }
        .add-to-bag-btn::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent); transition: left 0.5s; }
        .add-to-bag-btn:hover::before { left: 100%; }
        .add-to-bag-btn:hover { transform: translateY(-3px); box-shadow: 0 5px 20px rgba(212,165,116,0.4); background: #F5E6D3; }
        
        .empty-state { text-align: center; padding: 80px 40px; background: rgba(61,35,20,0.8); border-radius: 24px; border: 1px dashed #D4A574; }
        .empty-icon { font-size: 70px; color: #D4A574; margin-bottom: 20px; opacity: 0.7; }
        .empty-state h3 { font-size: 22px; margin-bottom: 10px; color: #D4A574; }
        .empty-state p { color: #C4A484; margin-bottom: 25px; }
        .empty-btn { background: #D4A574; border: none; padding: 12px 30px; font-size: 11px; font-weight: 600; letter-spacing: 1px; color: #3D2314; cursor: pointer; border-radius: 50px; transition: all 0.3s; }
        .empty-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(212,165,116,0.3); }
        
        /* Footer */
        .footer {
            background: linear-gradient(135deg, #1A0F08 0%, #2C1810 100%);
            padding: 60px 0 0;
            border-top: 1px solid rgba(212,165,116,0.2);
            position: relative;
            overflow: hidden;
        }
        
        .footer::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(45deg, transparent, transparent 40px, rgba(212,165,116,0.02) 40px, rgba(212,165,116,0.02) 80px);
            pointer-events: none;
        }
        
        .footer-main {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 50px;
            position: relative;
            z-index: 1;
        }
        
        .footer-brand-section { max-width: 320px; }
        .footer-logo { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
        .logo-icon { font-size: 28px; color: #D4A574; animation: rotateIcon 3s ease infinite; }
        @keyframes rotateIcon { 0%, 100% { transform: rotate(0deg); } 50% { transform: rotate(15deg); } }
        .logo-text { font-size: 22px; font-weight: 800; letter-spacing: 3px; color: #D4A574; }
        .logo-text span { color: #F5E6D3; }
        .footer-description { color: #A08874; font-size: 13px; line-height: 1.6; margin-bottom: 20px; }
        .footer-contact { display: flex; flex-direction: column; gap: 12px; }
        .contact-item { display: flex; align-items: center; gap: 12px; font-size: 12px; color: #A08874; }
        .contact-item i { width: 20px; color: #D4A574; font-size: 14px; }
        .contact-item a { color: #A08874; text-decoration: none; transition: color 0.3s; }
        .contact-item a:hover { color: #D4A574; }
        
        .footer-title { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: #D4A574; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid rgba(212,165,116,0.2); position: relative; }
        .footer-title::after { content: ''; position: absolute; bottom: -1px; left: 0; width: 40px; height: 2px; background: #D4A574; }
        .footer-title i { font-size: 14px; }
        .footer-links-section ul { list-style: none; display: flex; flex-direction: column; gap: 12px; }
        .footer-links-section ul li a { display: flex; align-items: center; gap: 10px; color: #A08874; text-decoration: none; font-size: 13px; transition: all 0.3s ease; }
        .footer-links-section ul li a i { font-size: 10px; transition: transform 0.3s ease; }
        .footer-links-section ul li a:hover { color: #D4A574; transform: translateX(5px); }
        .footer-newsletter-section { max-width: 350px; }
        .newsletter-text { color: #A08874; font-size: 12px; margin-bottom: 15px; }
        .footer-newsletter-form { margin-bottom: 20px; }
        .input-group { display: flex; background: rgba(61, 35, 20, 0.6); border: 1px solid rgba(212,165,116,0.3); border-radius: 50px; overflow: hidden; transition: all 0.3s; }
        .input-group:focus-within { border-color: #D4A574; box-shadow: 0 0 10px rgba(212,165,116,0.2); }
        .input-group input { flex: 1; background: transparent; border: none; padding: 12px 18px; font-size: 12px; color: #F5E6D3; outline: none; }
        .input-group input::placeholder { color: #8B6B4A; }
        .input-group button { background: #D4A574; border: none; padding: 0 20px; color: #3D2314; cursor: pointer; transition: all 0.3s; }
        .input-group button:hover { background: #F5E6D3; transform: scale(1.05); }
        
        .footer-social { display: flex; gap: 12px; flex-wrap: wrap; }
        .social-icon { width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; background: rgba(61, 35, 20, 0.8); border-radius: 50%; color: #D4A574; font-size: 16px; text-decoration: none; transition: all 0.3s ease; border: 1px solid rgba(212,165,116,0.2); }
        .social-icon:hover { background: #D4A574; color: #3D2314; transform: translateY(-5px) scale(1.1); border-color: #D4A574; }
        
        .footer-bottom { padding: 25px 0; border-top: 1px solid rgba(212,165,116,0.1); margin-top: 20px; }
        .footer-bottom-content { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .copyright { font-size: 11px; color: #8B6B4A; letter-spacing: 1px; }
        .copyright i { color: #D4A574; margin-right: 3px; }
        .payment-methods { display: flex; gap: 15px; font-size: 24px; color: #A08874; }
        .payment-methods i:hover { color: #D4A574; transform: translateY(-3px); }
        .footer-badges { display: flex; gap: 15px; }
        .badge-eco { display: flex; align-items: center; gap: 6px; background: rgba(212,165,116,0.1); padding: 5px 12px; border-radius: 20px; font-size: 10px; font-weight: 600; letter-spacing: 1px; color: #D4A574; border: 1px solid rgba(212,165,116,0.2); }
        .badge-eco i { font-size: 10px; }
        
        .toast-wrap { position: fixed; bottom: 30px; right: 30px; z-index: 9999; }
        .toast-msg { background: #3D2314; border-left: 3px solid #D4A574; padding: 12px 20px; margin-top: 8px; border-radius: 12px; color: #F5E6D3; font-size: 13px; transform: translateX(120%); transition: transform 0.3s; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .toast-msg.show { transform: translateX(0); }
        
        @media (max-width: 992px) { 
            .desktop-nav { display: none; } 
            .mobile-toggle { display: block; } 
            .navbar .container { padding: 10px 20px; } 
            .navbar-right { margin-left: auto; margin-right: 15px; } 
            .filter-wrapper { flex-direction: column; }
            .footer-main { grid-template-columns: repeat(2, 1fr); gap: 30px; }
            .footer-brand-section { grid-column: span 2; max-width: 100%; text-align: center; }
            .footer-logo { justify-content: center; }
            .footer-contact { align-items: center; }
            .footer-newsletter-section { grid-column: span 2; max-width: 100%; text-align: center; }
            .footer-bottom-content { flex-direction: column; text-align: center; }
            .payment-methods { justify-content: center; }
            .footer-badges { justify-content: center; }
        }
        
        @media (max-width: 768px) { 
            .hero-title { font-size: 32px; } 
            .products-grid { grid-template-columns: 1fr; } 
            .card-media { height: 280px; }
            .footer-main { grid-template-columns: 1fr; }
            .footer-brand-section { grid-column: span 1; }
            .footer-newsletter-section { grid-column: span 1; }
        }
    </style>
</head>
<body>
<div class="cursor-dot"></div>
<div class="cursor-outline"></div>
<div class="top-bar">✦ FREE SHIPPING ON ORDERS OVER $100 ✦ ETHICAL FASHION ✦ 30-DAY RETURNS ✦</div>
<nav class="navbar">
    <div class="container">
        <a class="navbar-brand" href="index.php">VELVET<span>.</span>AURA</a>
        <div class="desktop-nav">
            <div class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-home"></i> HOME</a>
                <div class="dropdown"><a href="index.php#newArrivals"><i class="fas fa-sparkles"></i> New Arrivals</a><a href="index.php#bestSellers"><i class="fas fa-fire"></i> Best Sellers</a><a href="index.php#categories"><i class="fas fa-th-large"></i> Shop by Category</a><div class="dropdown-divider"></div><a href="lookbook.php"><i class="fas fa-camera"></i> Lookbook</a><a href="about.php"><i class="fas fa-heart"></i> About Us</a></div>
            </div>
            <div class="nav-item"><a href="shop.php" class="nav-link"><i class="fas fa-store"></i> SHOP</a>
                <div class="dropdown"><div class="dropdown-header"><i class="fas fa-tags"></i> SHOP BY CATEGORY</div><?php foreach($db_categories as $cat): ?><a href="shop.php?category=<?php echo htmlspecialchars($cat['slug']); ?>"><i class="fas fa-chevron-right"></i> <?php echo htmlspecialchars($cat['name']); ?></a><?php endforeach; ?><div class="dropdown-divider"></div><a href="shop.php"><i class="fas fa-bag-shopping"></i> All Products</a><a href="shop.php?filter=new"><i class="fas fa-sparkles"></i> New Arrivals</a><a href="shop.php?filter=bestseller"><i class="fas fa-crown"></i> Best Sellers</a></div>
            </div>
            <div class="nav-item"><a href="about.php" class="nav-link"><i class="fas fa-info-circle"></i> ABOUT</a>
                <div class="dropdown"><a href="about.php#our-story"><i class="fas fa-leaf"></i> Our Story</a><a href="about.php#sustainability"><i class="fas fa-globe"></i> Sustainability</a><a href="about.php#careers"><i class="fas fa-briefcase"></i> Careers</a><div class="dropdown-divider"></div><a href="contact.php"><i class="fas fa-envelope"></i> Contact Us</a></div>
            </div>
            <div class="nav-item"><a href="lookbook.php" class="nav-link"><i class="fas fa-camera"></i> LOOKBOOK</a></div>
            <?php if($is_logged_in): ?><div class="nav-item"><a href="my-orders.php" class="nav-link"><i class="fas fa-box"></i> MY ORDERS</a></div><?php endif; ?>
        </div>
        <button class="mobile-toggle" id="mobileToggle"><i class="fas fa-bars"></i></button>
        <div class="navbar-right">
            <a href="wishlist.php" class="icon-btn" id="wishlistIcon"><i class="far fa-heart"></i><span class="badge-count" id="wishlistCount"><?php echo $wishlist_count; ?></span></a>
            <a href="cart.php" class="icon-btn" id="cartIcon"><i class="fas fa-shopping-bag"></i><span class="badge-count" id="cartCount"><?php echo $cart_count; ?></span></a>
            <?php if($is_logged_in): ?><a href="logout.php" class="icon-btn"><i class="fas fa-sign-out-alt"></i></a><?php else: ?><a href="login.php" class="icon-btn"><i class="far fa-user"></i></a><?php endif; ?>
        </div>
        <div class="mobile-menu" id="mobileMenu">
            <div class="mobile-nav-item"><div class="mobile-nav-link"><i class="fas fa-home"></i> HOME <i class="fas fa-chevron-down mobile-dropdown-toggle"></i></div><div class="mobile-dropdown"><a href="index.php#newArrivals"><i class="fas fa-sparkles"></i> New Arrivals</a><a href="index.php#bestSellers"><i class="fas fa-fire"></i> Best Sellers</a><a href="index.php#categories"><i class="fas fa-th-large"></i> Shop by Category</a><a href="lookbook.php"><i class="fas fa-camera"></i> Lookbook</a><a href="about.php"><i class="fas fa-heart"></i> About Us</a></div></div>
            <div class="mobile-nav-item"><div class="mobile-nav-link"><i class="fas fa-store"></i> SHOP <i class="fas fa-chevron-down mobile-dropdown-toggle"></i></div><div class="mobile-dropdown"><div class="dropdown-header">📁 SHOP BY CATEGORY</div><?php foreach($db_categories as $cat): ?><a href="shop.php?category=<?php echo htmlspecialchars($cat['slug']); ?>"><i class="fas fa-chevron-right"></i> <?php echo htmlspecialchars($cat['name']); ?></a><?php endforeach; ?><div class="dropdown-divider"></div><a href="shop.php"><i class="fas fa-bag-shopping"></i> All Products</a><a href="shop.php?filter=new"><i class="fas fa-sparkles"></i> New Arrivals</a><a href="shop.php?filter=bestseller"><i class="fas fa-crown"></i> Best Sellers</a></div></div>
            <div class="mobile-nav-item"><div class="mobile-nav-link"><i class="fas fa-info-circle"></i> ABOUT <i class="fas fa-chevron-down mobile-dropdown-toggle"></i></div><div class="mobile-dropdown"><a href="about.php"><i class="fas fa-leaf"></i> Our Story</a><a href="#"><i class="fas fa-globe"></i> Sustainability</a><a href="#"><i class="fas fa-briefcase"></i> Careers</a><a href="contact.php"><i class="fas fa-envelope"></i> Contact Us</a></div></div>
            <div class="mobile-nav-item"><a href="lookbook.php" class="mobile-nav-link" style="justify-content: space-between;"><span><i class="fas fa-camera"></i> LOOKBOOK</span></a></div>
            <?php if($is_logged_in): ?><div class="mobile-nav-item"><a href="my-orders.php" class="mobile-nav-link" style="justify-content: space-between;"><span><i class="fas fa-box"></i> MY ORDERS</span></a></div><?php endif; ?>
        </div>
    </div>
</nav>
<section class="shop-hero"><div class="container"><div class="hero-badge">✦ CURATED COLLECTION ✦</div><h1 class="hero-title">Discover Your <em>Signature</em> Style</h1><p class="hero-subtitle">Timeless pieces designed for the modern aesthetic</p></div></section>
<div class="filter-bar"><div class="container"><div class="filter-wrapper"><div class="category-filters"><button class="filter-chip <?php echo empty($category_slug) ? 'active' : ''; ?>" data-cat="">All</button><?php foreach($db_categories as $cat): ?><button class="filter-chip <?php echo $category_slug === $cat['slug'] ? 'active' : ''; ?>" data-cat="<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['name']); ?></button><?php endforeach; ?></div><div class="filter-controls"><div class="price-slider"><label>MAX PRICE</label><input type="range" id="priceRange" min="0" max="500" value="<?php echo $max_price; ?>" step="10"><span class="price-value" id="priceValue">$<?php echo $max_price; ?></span></div><select class="sort-select" id="sortSelect"><option value="default" <?php echo $sort=='default'?'selected':''; ?>>Default</option><option value="newest" <?php echo $sort=='newest'?'selected':''; ?>>Newest First</option><option value="price-low" <?php echo $sort=='price-low'?'selected':''; ?>>Price: Low to High</option><option value="price-high" <?php echo $sort=='price-high'?'selected':''; ?>>Price: High to Low</option></select><button class="reset-btn" id="resetBtn"><i class="fas fa-undo-alt"></i> Reset</button></div></div></div></div>
<section class="products-section"><div class="container"><div class="results-count">✨ Showing <span><?php echo count($products); ?></span> exclusive products<?php if(!empty($search)): ?> for "<strong><?php echo htmlspecialchars($search); ?></strong>"<?php endif; ?></div><div class="products-grid"><?php if(empty($products)): ?><div class="empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><h3>No products found</h3><p>Try adjusting your filters or search terms</p><button class="empty-btn" onclick="window.location.href='shop.php'">Clear all filters</button></div><?php else: ?><?php foreach($products as $product): $discount = isset($product['old_price']) && $product['old_price'] > 0 ? round((($product['old_price'] - $product['price']) / $product['old_price']) * 100) : null; $is_in_wishlist = in_array($product['id'], $wishlist_ids); $rating = floatval($product['rating'] ?? 4.5); $fullStars = floor($rating); $halfStar = ($rating - $fullStars) >= 0.5; ?>
<div class="product-card" data-product-id="<?php echo $product['id']; ?>"><div class="card-media"><img src="assets/images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='https://placehold.co/400x500/5C3A24/D4A574?text=VA'"><div class="card-badges"><?php if(isset($product['is_new']) && $product['is_new']): ?><span class="badge badge-new">New</span><?php endif; ?><?php if($discount): ?><span class="badge badge-sale">-<?php echo $discount; ?>%</span><?php endif; ?></div><div class="card-actions"><button class="action-btn add-to-cart" data-id="<?php echo $product['id']; ?>"><i class="fas fa-shopping-bag"></i></button><button class="action-btn add-to-wishlist <?php echo $is_in_wishlist ? 'active' : ''; ?>" data-id="<?php echo $product['id']; ?>"><i class="fas fa-heart"></i></button><button class="action-btn quick-view" onclick="window.location.href='product-detail.php?id=<?php echo $product['id']; ?>'"><i class="fas fa-eye"></i></button></div></div><div class="card-info"><?php if(!empty($product['category_name'])): ?><div class="card-category"><?php echo htmlspecialchars($product['category_name']); ?></div><?php endif; ?><h3 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h3><div class="card-rating"><div class="stars"><?php for($i=1; $i<=$fullStars; $i++): ?><i class="fas fa-star filled"></i><?php endfor; ?><?php if($halfStar): ?><i class="fas fa-star-half-alt filled"></i><?php endif; ?><?php for($i=1; $i<=5-ceil($rating); $i++): ?><i class="far fa-star"></i><?php endfor; ?></div><span class="rating-count">(<?php echo rand(12, 89); ?>)</span></div><div class="card-price"><span class="current-price">$<?php echo number_format($product['price'], 2); ?></span><?php if(isset($product['old_price']) && $product['old_price']): ?><span class="old-price">$<?php echo number_format($product['old_price'], 2); ?></span><?php endif; ?></div><button class="add-to-bag-btn add-to-cart" data-id="<?php echo $product['id']; ?>">ADD TO BAG</button></div></div><?php endforeach; ?><?php endif; ?></div></div></section>
<footer class="footer">
    <div class="container">
        <div class="footer-main">
            <div class="footer-brand-section">
                <div class="footer-logo"><span class="logo-icon">✦</span><span class="logo-text">VELVET<span>AURA</span></span></div>
                <p class="footer-description">Ethical fashion for the conscious soul. Timeless pieces designed to last beyond seasons, crafted with love and intention.</p>
                <div class="footer-contact">
                    <div class="contact-item"><i class="fas fa-envelope"></i><a href="mailto:hello@velvetaura.com">hello@velvetaura.com</a></div>
                    <div class="contact-item"><i class="fas fa-phone-alt"></i><a href="tel:+15551234567">+1 (555) 123-4567</a></div>
                    <div class="contact-item"><i class="fas fa-map-marker-alt"></i><span>New York, NY 10001</span></div>
                </div>
            </div>
            <div class="footer-links-section"><h4 class="footer-title"><i class="fas fa-compass"></i> Quick Links</h4><ul><li><a href="shop.php"><i class="fas fa-chevron-right"></i> Shop All</a></li><li><a href="shop.php?filter=new"><i class="fas fa-chevron-right"></i> New Arrivals</a></li><li><a href="shop.php?filter=bestseller"><i class="fas fa-chevron-right"></i> Best Sellers</a></li><li><a href="lookbook.php"><i class="fas fa-chevron-right"></i> Lookbook</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> About Us</a></li></ul></div>
            <div class="footer-links-section"><h4 class="footer-title"><i class="fas fa-tags"></i> Categories</h4><ul><?php $footer_cats = array_slice($db_categories, 0, 6); foreach($footer_cats as $cat): ?><li><a href="shop.php?category=<?php echo htmlspecialchars($cat['slug']); ?>"><i class="fas fa-chevron-right"></i> <?php echo htmlspecialchars($cat['name']); ?></a></li><?php endforeach; ?></ul></div>
            <div class="footer-links-section"><h4 class="footer-title"><i class="fas fa-headset"></i> Support</h4><ul><li><a href="#"><i class="fas fa-chevron-right"></i> FAQ</a></li><li><a href="#"><i class="fas fa-chevron-right"></i> Shipping Info</a></li><li><a href="#"><i class="fas fa-chevron-right"></i> Returns & Exchanges</a></li><li><a href="#"><i class="fas fa-chevron-right"></i> Size Guide</a></li><li><a href="#"><i class="fas fa-chevron-right"></i> Privacy Policy</a></li></ul></div>
            <div class="footer-newsletter-section"><h4 class="footer-title"><i class="fas fa-envelope-open-text"></i> Stay Connected</h4><p class="newsletter-text">Subscribe to get 15% off your first order and exclusive updates!</p><form class="footer-newsletter-form" id="footerNewsletterForm"><div class="input-group"><input type="email" placeholder="Your email address" required><button type="submit"><i class="fas fa-paper-plane"></i></button></div></form><div class="footer-social"><a href="#" class="social-icon"><i class="fab fa-instagram"></i></a><a href="#" class="social-icon"><i class="fab fa-pinterest"></i></a><a href="#" class="social-icon"><i class="fab fa-tiktok"></i></a><a href="#" class="social-icon"><i class="fab fa-youtube"></i></a><a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a><a href="#" class="social-icon"><i class="fab fa-twitter"></i></a></div></div>
        </div>
        <div class="footer-bottom"><div class="footer-bottom-content"><div class="copyright"><i class="far fa-copyright"></i> <?php echo date('Y'); ?> Velvet Aura — All rights reserved.</div><div class="payment-methods"><i class="fab fa-cc-visa"></i><i class="fab fa-cc-mastercard"></i><i class="fab fa-cc-amex"></i><i class="fab fa-cc-paypal"></i><i class="fab fa-apple-pay"></i></div><div class="footer-badges"><span class="badge-eco"><i class="fas fa-leaf"></i> Eco-Friendly</span><span class="badge-eco"><i class="fas fa-recycle"></i> Sustainable</span></div></div></div>
    </div>
</footer>
<div class="toast-wrap" id="toastWrap"></div>
<script>
const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
let currentCat = '<?php echo addslashes($category_slug); ?>' || '';
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
    document.querySelectorAll('a, button, .filter-chip, .action-btn, .product-card, .icon-btn').forEach(el => {
        el.addEventListener('mouseenter', () => { cursorOutline.style.transform = `scale(1.5)`; cursorOutline.style.background = 'rgba(212,165,116,0.1)'; cursorOutline.style.borderColor = '#F5E6D3'; });
        el.addEventListener('mouseleave', () => { cursorOutline.style.transform = `scale(1)`; cursorOutline.style.background = 'transparent'; cursorOutline.style.borderColor = '#D4A574'; });
    });
}

// Update cart badge
function updateCartBadge(count) {
    cartCount = count;
    const cartBadge = document.getElementById('cartCount');
    if (cartBadge) {
        if (count > 0) { cartBadge.textContent = count; cartBadge.style.display = 'flex'; }
        else { cartBadge.style.display = 'none'; }
    }
}

function updateWishlistBadge(count) {
    wishlistCount = count;
    const wishlistBadge = document.getElementById('wishlistCount');
    if (wishlistBadge) {
        if (count > 0) { wishlistBadge.textContent = count; wishlistBadge.style.display = 'flex'; }
        else { wishlistBadge.style.display = 'none'; }
    }
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
    toast.innerHTML = `<i class="fas ${ok ? 'fa-check-circle' : 'fa-exclamation-circle'}" style="color: #D4A574;"></i> ${msg}`;
    wrap.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, 2500);
}

// Add to Cart - Single function, no duplicate
async function addToCart(id, btnElement) {
    if (!isLoggedIn) { 
        showToast('Please login to continue', false); 
        setTimeout(() => window.location.href = 'login.php?redirect=shop.php', 1500); 
        return; 
    }
    
    // Prevent double click
    if (btnElement && btnElement.disabled) return;
    
    if (btnElement) {
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    }
    
    try { 
        const res = await fetch('backend/cart/add-to-cart.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, 
            body: 'product_id=' + id + '&quantity=1' 
        }); 
        const data = await res.json(); 
        if (data.success) {
            cartCount++;
            updateCartBadge(cartCount);
            showToast('Added to bag ✨');
            if (btnElement) {
                btnElement.style.transform = 'scale(0.9)';
                setTimeout(() => { btnElement.style.transform = ''; }, 200);
            }
        } else { 
            showToast(data.message || 'Error', false); 
        }
    } catch(e) { 
        console.error('Add to cart error:', e);
        showToast('Something went wrong', false); 
    }
    
    if (btnElement) {
        btnElement.disabled = false;
        btnElement.innerHTML = 'ADD TO BAG';
    }
}

async function addToWishlist(id, btn) {
    if (!isLoggedIn) { 
        showToast('Please login to continue', false); 
        setTimeout(() => window.location.href = 'login.php?redirect=shop.php', 1500); 
        return; 
    }
    if (btn.disabled) return;
    btn.disabled = true;
    
    try { 
        const res = await fetch('backend/wishlist/add-to-wishlist.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, 
            body: 'product_id=' + id 
        }); 
        const data = await res.json(); 
        if (data.success) { 
            btn.classList.toggle('active', data.action === 'added');
            if (data.action === 'added') { 
                wishlistCount++; 
                showToast('Saved to wishlist ❤️'); 
            } else { 
                wishlistCount--; 
                showToast('Removed from wishlist 💔'); 
            }
            updateWishlistBadge(wishlistCount);
            btn.style.transform = 'scale(1.2)';
            setTimeout(() => { btn.style.transform = ''; }, 200);
        } 
    } catch(e) { 
        console.error('Wishlist error:', e);
        showToast('Something went wrong', false); 
    }
    btn.disabled = false;
}

// ✅ ONLY ONE EVENT LISTENER FOR ADD TO CART
document.querySelectorAll('.add-to-cart').forEach(btn => { 
    btn.addEventListener('click', (e) => { 
        e.preventDefault(); 
        e.stopPropagation(); 
        if (btn.disabled) return;
        addToCart(parseInt(btn.dataset.id), btn); 
    }); 
});

document.querySelectorAll('.add-to-wishlist').forEach(btn => { 
    btn.addEventListener('click', (e) => { 
        e.preventDefault(); 
        e.stopPropagation(); 
        addToWishlist(parseInt(btn.dataset.id), btn); 
    }); 
});

// Filters
const priceRange = document.getElementById('priceRange');
const priceValue = document.getElementById('priceValue');
const sortSelect = document.getElementById('sortSelect');
const resetBtn = document.getElementById('resetBtn');
const filterChips = document.querySelectorAll('.filter-chip');
if (priceRange) { priceRange.addEventListener('input', () => { priceValue.textContent = '$' + priceRange.value; }); }
function applyFilters() {
    let url = 'shop.php?';
    if (currentCat && currentCat !== '') url += 'category=' + encodeURIComponent(currentCat) + '&';
    if (priceRange) url += 'max_price=' + priceRange.value + '&';
    if (sortSelect) url += 'sort=' + sortSelect.value;
    const searchQuery = new URLSearchParams(window.location.search).get('search');
    if (searchQuery) url += '&search=' + encodeURIComponent(searchQuery);
    window.location.href = url;
}
filterChips.forEach(chip => { chip.addEventListener('click', () => { filterChips.forEach(c => c.classList.remove('active')); chip.classList.add('active'); currentCat = chip.dataset.cat; applyFilters(); }); });
if (priceRange) priceRange.addEventListener('change', applyFilters);
if (sortSelect) sortSelect.addEventListener('change', applyFilters);
if (resetBtn) { resetBtn.addEventListener('click', () => window.location.href = 'shop.php'); }

// Footer Newsletter Form
document.getElementById('footerNewsletterForm')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const email = e.target.querySelector('input').value;
    if (email) {
        showToast('Thanks for subscribing! Check your email for 15% off ✨');
        e.target.reset();
    }
});

</script>
</body>
</html>