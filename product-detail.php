<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();
require_once 'backend/config/database.php';
require_once 'backend/includes/functions.php';

$is_logged_in = isLoggedIn();

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id == 0) {
    header('Location: shop.php');
    exit();
}

// Fetch product from database
$query = "SELECT p.*, c.name as category_name, c.slug as category_slug 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.id = $product_id";
$result = mysqli_query($conn, $query);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    header('Location: shop.php');
    exit();
}

// Get related products (same category)
$related_query = "SELECT * FROM products WHERE category_id = {$product['category_id']} AND id != $product_id AND in_stock = 1 LIMIT 4";
$related_result = mysqli_query($conn, $related_query);
$related_products = mysqli_fetch_all($related_result, MYSQLI_ASSOC);

// Get wishlist status
$in_wishlist = false;
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $wish_check = mysqli_query($conn, "SELECT id FROM wishlist WHERE user_id = $user_id AND product_id = $product_id");
    $in_wishlist = mysqli_num_rows($wish_check) > 0;
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
    <title><?php echo htmlspecialchars($product['name']); ?> - Velvet Aura</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0F0A08; color: #F5E6D3; cursor: none; overflow-x: hidden; }
        
        .cursor-dot { width: 8px; height: 8px; background: #D4A574; border-radius: 50%; position: fixed; pointer-events: none; z-index: 99999; }
        .cursor-outline { width: 40px; height: 40px; border: 2px solid #D4A574; border-radius: 50%; position: fixed; pointer-events: none; z-index: 99998; transition: all 0.15s ease; }
        
        .top-bar { background: linear-gradient(135deg, #5C2E1A, #8B4513); padding: 8px 0; text-align: center; font-size: 11px; letter-spacing: 2px; color: #F5E6D3; text-transform: uppercase; font-weight: 500; }
        
        .navbar { background: rgba(61, 35, 20, 0.95); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(92,46,26,0.3); padding: 0; position: sticky; top: 0; z-index: 1000; }
        .navbar .container { display: flex; justify-content: space-between; align-items: center; padding: 0 40px; flex-wrap: wrap; }
        .navbar-brand { font-family: 'Inter', sans-serif; font-size: 22px; font-weight: 800; letter-spacing: 3px; color: #D4B5A7 !important; text-decoration: none; text-transform: uppercase; transition: all 0.3s; }
        .navbar-brand span { color: #F5E6D3; }
        .desktop-nav { display: flex; gap: 40px; margin: 0 auto; }
        .nav-item { position: relative; }
        .nav-link { display: flex; align-items: center; gap: 10px; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #D4B5A7 !important; text-decoration: none; padding: 28px 0; transition: all 0.3s ease; }
        .nav-link i { font-size: 14px; }
        .nav-link:hover { color: #F5E6D3 !important; transform: translateY(-2px); }
        .nav-link::after { content: ''; position: absolute; bottom: 20px; left: 0; width: 0; height: 2px; background: #D4B5A7; transition: width 0.3s ease; }
        .nav-link:hover::after { width: 100%; }
        
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
        
        .product-detail { padding: 60px 0; background: #1A0F08; }
        .product-gallery { background: #3D2314; border-radius: 30px; overflow: hidden; border: 1px solid rgba(212,165,116,0.2); transition: all 0.3s; }
        .product-gallery:hover { transform: translateY(-5px); border-color: #D4A574; }
        .product-gallery img { width: 100%; height: 500px; object-fit: cover; transition: transform 0.3s; }
        .product-info { background: #3D2314; border-radius: 30px; padding: 35px; border: 1px solid rgba(212,165,116,0.2); transition: all 0.3s; }
        .product-info:hover { transform: translateY(-5px); border-color: #D4A574; }
        .product-info h1 { font-size: 32px; font-weight: 800; margin-bottom: 15px; color: #F5E6D3; }
        .product-price { font-size: 32px; font-weight: 800; color: #D4A574; margin: 20px 0; }
        .product-old-price { text-decoration: line-through; color: #8B6B4A; font-size: 20px; margin-left: 10px; font-weight: normal; }
        .product-rating { margin: 15px 0; }
        .product-rating i { color: #D4A574; font-size: 16px; }
        .product-description { color: #C4A484; line-height: 1.8; margin: 20px 0; padding-bottom: 20px; border-bottom: 1px solid rgba(212,165,116,0.2); }
        .product-meta { margin: 20px 0; }
        .product-meta p { margin-bottom: 10px; font-size: 14px; }
        .product-meta strong { width: 120px; display: inline-block; color: #D4B5A7; }
        
        .quantity-selector { display: flex; align-items: center; gap: 15px; margin: 25px 0; }
        .qty-btn { width: 44px; height: 44px; border: 1px solid #5C2E1A; background: #2C1810; border-radius: 50%; cursor: pointer; font-size: 20px; transition: all 0.3s; color: #D4B5A7; }
        .qty-btn:hover { background: #5C2E1A; color: #F5E6D3; }
        .qty-input { width: 70px; text-align: center; font-size: 18px; font-weight: 700; padding: 10px; border: 1px solid #5C2E1A; border-radius: 15px; background: #2C1810; color: #F5E6D3; }
        
        .action-buttons { display: flex; gap: 15px; margin: 25px 0; flex-wrap: wrap; }
        .btn-add-cart { flex: 2; background: linear-gradient(135deg, #D4A574, #C4956A); color: #2C1810; padding: 14px; border: none; border-radius: 50px; font-weight: 800; cursor: pointer; transition: all 0.3s; letter-spacing: 1px; }
        .btn-add-cart:hover { background: #F5E6D3; transform: translateY(-3px); box-shadow: 0 10px 25px rgba(212,165,116,0.4); }
        .btn-add-cart:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-wishlist { width: 54px; height: 54px; border-radius: 50%; background: #2C1810; border: 1px solid #5C2E1A; cursor: pointer; transition: all 0.3s; font-size: 20px; color: #D4B5A7; }
        .btn-wishlist:hover { background: #8B4513; color: #F5E6D3; }
        .btn-wishlist.active { background: #8B4513; color: #F5E6D3; border-color: #D4A574; }
        
        .stock-status { display: inline-block; padding: 6px 14px; border-radius: 30px; font-size: 12px; font-weight: 600; margin-top: 10px; }
        .stock-in { background: rgba(76,175,80,0.2); color: #4CAF50; border: 1px solid rgba(76,175,80,0.3); }
        .stock-out { background: rgba(255,68,68,0.2); color: #ff6b6b; border: 1px solid rgba(255,68,68,0.3); }
        
        /* Reviews Section */
        .reviews-section { padding: 60px 0; background: #1A0F08; border-top: 1px solid rgba(212,165,116,0.1); }
        .reviews-header { text-align: center; margin-bottom: 40px; }
        .reviews-header h2 { font-size: 32px; font-weight: 800; color: #F5E6D3; }
        .reviews-header p { color: #D4B5A7; }
        .average-rating { text-align: center; margin-bottom: 40px; padding: 20px; background: #3D2314; border-radius: 20px; }
        .avg-score { font-size: 48px; font-weight: 800; color: #D4A574; }
        .avg-stars { margin: 10px 0; }
        .avg-stars i { font-size: 20px; color: #D4A574; margin: 0 2px; }
        .total-reviews { color: #C4A484; font-size: 13px; }
        .write-review-btn { background: #5C2E1A; color: #F5E6D3; padding: 12px 30px; border: none; border-radius: 50px; font-weight: 700; cursor: pointer; transition: all 0.3s; margin-bottom: 30px; }
        .write-review-btn:hover { transform: translateY(-3px); background: #D4A574; color: #2C1810; }
        .review-card { background: #3D2314; border-radius: 20px; padding: 20px; margin-bottom: 20px; border: 1px solid rgba(212,165,116,0.15); transition: all 0.3s; }
        .review-card:hover { transform: translateY(-3px); border-color: #D4A574; }
        .reviewer-name { font-weight: 700; color: #F5E6D3; margin-bottom: 5px; }
        .review-date { font-size: 11px; color: #8B6B4A; margin-bottom: 10px; }
        .review-rating { margin-bottom: 10px; }
        .review-rating i { color: #D4A574; font-size: 12px; }
        .review-title { font-size: 16px; font-weight: 700; color: #F5E6D3; margin-bottom: 8px; }
        .review-comment { color: #C4A484; font-size: 13px; line-height: 1.6; }
        
        /* Review Modal */
        .review-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center; z-index: 10000; opacity: 0; visibility: hidden; transition: all 0.3s; }
        .review-modal.active { opacity: 1; visibility: visible; }
        .review-modal-content { background: #3D2314; border-radius: 24px; padding: 35px; max-width: 500px; width: 90%; border: 1px solid #D4A574; }
        .review-modal-content h3 { color: #F5E6D3; margin-bottom: 20px; text-align: center; }
        .rating-select { display: flex; justify-content: center; gap: 12px; margin-bottom: 25px; }
        .rating-star { font-size: 35px; cursor: pointer; color: #5C3A24; transition: all 0.3s; }
        .rating-star:hover, .rating-star.active { color: #D4A574; transform: scale(1.1); }
        .review-modal input, .review-modal textarea { width: 100%; padding: 12px 16px; margin-bottom: 15px; border: 1px solid rgba(212,165,116,0.3); border-radius: 12px; background: #2C1810; color: #F5E6D3; }
        .review-modal input:focus, .review-modal textarea:focus { outline: none; border-color: #D4A574; }
        .review-modal textarea { resize: vertical; min-height: 100px; }
        .submit-review-btn { background: linear-gradient(135deg, #D4A574, #C4956A); color: #2C1810; padding: 12px; border: none; border-radius: 50px; font-weight: 700; cursor: pointer; width: 100%; transition: all 0.3s; margin-bottom: 10px; }
        .submit-review-btn:hover { transform: translateY(-2px); }
        .submit-review-btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .close-modal { background: transparent; border: 1px solid #5C2E1A; color: #D4B5A7; padding: 10px; border-radius: 50px; cursor: pointer; width: 100%; transition: all 0.3s; }
        .close-modal:hover { background: #5C2E1A; color: #F5E6D3; }
        
        .related-section { padding: 60px 0; background: #1A0F08; border-top: 1px solid rgba(212,165,116,0.1); }
        .section-header { text-align: center; margin-bottom: 40px; }
        .section-header h2 { font-size: 32px; font-weight: 800; color: #F5E6D3; }
        .related-card { background: #3D2314; border-radius: 20px; overflow: hidden; transition: all 0.3s; text-decoration: none; display: block; border: 1px solid rgba(212,165,116,0.15); }
        .related-card:hover { transform: translateY(-5px); border-color: #D4A574; }
        .related-card img { width: 100%; height: 250px; object-fit: cover; transition: transform 0.3s; }
        .related-card:hover img { transform: scale(1.05); }
        .related-card .info { padding: 15px; text-align: center; }
        .related-card h4 { font-size: 14px; font-weight: 700; margin-bottom: 5px; color: #F5E6D3; }
        .related-card .price { font-weight: 800; color: #D4A574; }
        
        .footer { background: linear-gradient(135deg, #0F0A08 0%, #1A0F08 100%); padding: 60px 0 0; border-top: 1px solid rgba(212,165,116,0.1); margin-top: 60px; }
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
        .footer-title { font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: #D4B5A7; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid rgba(212,165,116,0.2); position: relative; display: inline-block; }
        .footer-title::after { content: ''; position: absolute; bottom: -1px; left: 0; width: 40px; height: 2px; background: #D4A574; }
        .footer-links-section ul { list-style: none; display: flex; flex-direction: column; gap: 12px; }
        .footer-links-section ul li a { display: flex; align-items: center; gap: 10px; color: #A08874; text-decoration: none; font-size: 13px; transition: all 0.3s; }
        .footer-links-section ul li a i { font-size: 10px; }
        .footer-links-section ul li a:hover { color: #D4A574; transform: translateX(5px); }
        .footer-newsletter-section { max-width: 350px; }
        .newsletter-text { color: #A08874; font-size: 12px; margin-bottom: 15px; }
        .footer-newsletter-form { margin-bottom: 20px; }
        .input-group { display: flex; background: #3D2314; border: 1px solid rgba(212,165,116,0.3); border-radius: 50px; overflow: hidden; }
        .input-group input { flex: 1; background: transparent; border: none; padding: 12px 18px; font-size: 12px; color: #F5E6D3; outline: none; }
        .input-group input::placeholder { color: #8B6B4A; }
        .input-group button { background: #5C2E1A; border: none; padding: 0 20px; color: #F5E6D3; cursor: pointer; transition: all 0.3s; }
        .input-group button:hover { background: #D4A574; }
        .footer-social { display: flex; gap: 12px; }
        .social-icon { width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; background: #3D2314; border-radius: 50%; color: #D4B5A7; font-size: 16px; text-decoration: none; transition: all 0.3s; border: 1px solid rgba(212,165,116,0.2); }
        .social-icon:hover { background: #5C2E1A; color: #F5E6D3; transform: translateY(-3px); border-color: #D4A574; }
        .footer-bottom { padding: 25px 0; border-top: 1px solid rgba(212,165,116,0.1); text-align: center; }
        .copyright { font-size: 11px; color: #8B6B4A; }
        
        .toast-wrap { position: fixed; bottom: 30px; right: 30px; z-index: 9999; }
        .toast-msg { background: #3D2314; border-left: 3px solid #D4A574; padding: 12px 20px; margin-top: 8px; border-radius: 12px; color: #F5E6D3; font-size: 12px; transform: translateX(120%); transition: transform 0.3s; }
        .toast-msg.show { transform: translateX(0); }
        
        @media (max-width: 992px) { 
            .desktop-nav { display: none; } 
            .mobile-toggle { display: block; } 
            .navbar .container { padding: 10px 20px; } 
            .navbar-right { margin-left: auto; margin-right: 15px; }
            .product-gallery img { height: 350px; }
            .footer-main { grid-template-columns: repeat(2, 1fr); }
            .footer-brand-section { grid-column: span 2; text-align: center; }
            .footer-logo { justify-content: center; }
            .footer-contact { align-items: center; }
            .footer-newsletter-section { grid-column: span 2; max-width: 100%; text-align: center; }
        }
        @media (max-width: 768px) { 
            .product-info h1 { font-size: 24px; }
            .product-price { font-size: 26px; }
            .related-card img { height: 200px; }
            .footer-main { grid-template-columns: 1fr; }
            .footer-brand-section { grid-column: span 1; text-align: center; }
            .footer-newsletter-section { grid-column: span 1; }
            .reviews-header h2 { font-size: 28px; }
            .avg-score { font-size: 36px; }
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

<section class="product-detail">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="product-gallery" data-aos="fade-right">
                    <img src="assets/images/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='https://placehold.co/600x500/3D2314/D4B5A7?text=No+Image'">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="product-info" data-aos="fade-left">
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <div class="product-rating" id="productRatingDisplay">
                        <?php 
                        $rating = floatval($product['rating'] ?? 4.5);
                        $full = floor($rating);
                        $half = ($rating - $full) >= 0.5;
                        for($i=1; $i<=$full; $i++) echo '<i class="fas fa-star"></i>';
                        if($half) echo '<i class="fas fa-star-half-alt"></i>';
                        for($i=1; $i<=5-ceil($rating); $i++) echo '<i class="far fa-star"></i>';
                        ?>
                        <span style="color: #C4A484; margin-left: 10px;">(<?php echo number_format($rating, 1); ?> / 5)</span>
                    </div>
                    
                    <div class="product-price">
                        $<?php echo number_format($product['price'], 2); ?>
                        <?php if($product['old_price']): ?>
                        <span class="product-old-price">$<?php echo number_format($product['old_price'], 2); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['description'] ?? 'This beautiful piece is crafted with care using premium materials. Perfect for any occasion.')); ?>
                    </div>
                    
                    <div class="product-meta">
                        <p><strong><i class="fas fa-tag"></i> Category:</strong> <?php echo htmlspecialchars($product['category_name'] ?? 'Fashion'); ?></p>
                        <p><strong><i class="fas fa-box"></i> SKU:</strong> VA-<?php echo str_pad($product['id'], 4, '0', STR_PAD_LEFT); ?></p>
                    </div>
                    
                    <div class="stock-status <?php echo $product['in_stock'] ? 'stock-in' : 'stock-out'; ?>">
                        <i class="fas <?php echo $product['in_stock'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                        <?php echo $product['in_stock'] ? 'In Stock' : 'Out of Stock'; ?>
                    </div>
                    
                    <div class="quantity-selector">
                        <button class="qty-btn" id="qtyMinus">-</button>
                        <input type="number" id="quantity" class="qty-input" value="1" min="1" max="<?php echo $product['stock_quantity'] ?? 10; ?>">
                        <button class="qty-btn" id="qtyPlus">+</button>
                    </div>
                    
                    <div class="action-buttons">
                        <button class="btn-add-cart add-to-cart-btn" data-id="<?php echo $product['id']; ?>" <?php echo !$product['in_stock'] ? 'disabled style="opacity:0.5;"' : ''; ?>>
                            <i class="fas fa-shopping-bag"></i> ADD TO CART
                        </button>
                        <button class="btn-wishlist wishlist-btn <?php echo $in_wishlist ? 'active' : ''; ?>" data-id="<?php echo $product['id']; ?>">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Reviews Section - Anyone can review -->
<section class="reviews-section">
    <div class="container">
        <div class="reviews-header" data-aos="fade-up">
            <h2>✨ Customer Reviews</h2>
            <p>Share your thoughts about this product</p>
        </div>
        
        <div class="average-rating" id="averageRatingDiv" data-aos="fade-up">
            <div class="avg-score" id="avgScore">--</div>
            <div class="avg-stars" id="avgStars"></div>
            <div class="total-reviews" id="totalReviews">Based on -- reviews</div>
        </div>
        
        <?php if($is_logged_in): ?>
        <div class="text-center" data-aos="fade-up">
            <button class="write-review-btn" id="writeReviewBtn">
                <i class="fas fa-pen"></i> Write a Review
            </button>
        </div>
        <?php else: ?>
        <div class="text-center" data-aos="fade-up">
            <p style="color: #C4A484;"><a href="login.php?redirect=product-detail.php?id=<?php echo $product_id; ?>" style="color: #D4A574; text-decoration: none; border-bottom: 1px solid #D4A574;">Login</a> to write a review</p>
        </div>
        <?php endif; ?>
        
        <div id="reviewsList" data-aos="fade-up"></div>
    </div>
</section>

<!-- Review Modal -->
<!-- Review Modal - Simple Version -->
<div class="review-modal" id="reviewModal">
    <div class="review-modal-content">
        <h3><i class="fas fa-star"></i> Write Your Review</h3>
        
        <form action="submit-review-simple.php" method="POST">
            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
            <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id'] ?? 0; ?>">
            
            <div class="rating-select" id="ratingSelect">
                <label>Rating:</label><br>
                <select name="rating" required style="width:100%; padding:10px; margin-bottom:15px;">
                    <option value="">Select Rating</option>
                    <option value="5">⭐⭐⭐⭐⭐ (5 - Excellent)</option>
                    <option value="4">⭐⭐⭐⭐ (4 - Good)</option>
                    <option value="3">⭐⭐⭐ (3 - Average)</option>
                    <option value="2">⭐⭐ (2 - Poor)</option>
                    <option value="1">⭐ (1 - Terrible)</option>
                </select>
            </div>
            
            <input type="text" name="title" id="reviewTitle" placeholder="Review title (optional)">
            <textarea name="comment" id="reviewComment" placeholder="Share your experience..." required></textarea>
            
            <button type="submit" class="submit-review-btn">Submit Review</button>
            <button type="button" class="close-modal" id="closeModalBtn">Cancel</button>
        </form>
    </div>
</div>

<?php if(!empty($related_products)): ?>
<section class="related-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2>✨ You May Also Like</h2>
            <p>Complete your look with these pieces</p>
        </div>
        <div class="row g-4">
            <?php foreach($related_products as $index => $related): ?>
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                <a href="product-detail.php?id=<?php echo $related['id']; ?>" class="related-card">
                    <img src="assets/images/<?php echo $related['image']; ?>" alt="<?php echo htmlspecialchars($related['name']); ?>" onerror="this.src='https://placehold.co/400x250/3D2314/D4B5A7?text=No+Image'">
                    <div class="info">
                        <h4><?php echo htmlspecialchars($related['name']); ?></h4>
                        <div class="price">$<?php echo number_format($related['price'], 2); ?></div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

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
    const productId = <?php echo $product_id; ?>;
    let cartCount = <?php echo $cart_count; ?>;
    let wishlistCount = <?php echo $wishlist_count; ?>;
    let selectedRating = 0;
    
    // Custom Cursor
    const cursorDot = document.querySelector('.cursor-dot');
    const cursorOutline = document.querySelector('.cursor-outline');
    if (cursorDot && cursorOutline) {
        window.addEventListener('mousemove', function(e) {
            cursorDot.style.transform = `translate(${e.clientX - 4}px, ${e.clientY - 4}px)`;
            cursorOutline.style.transform = `translate(${e.clientX - 20}px, ${e.clientY - 20}px)`;
        });
        document.querySelectorAll('a, button, .icon-btn, .review-card, .related-card').forEach(el => {
            el.addEventListener('mouseenter', () => { cursorOutline.style.transform = `scale(1.5)`; cursorOutline.style.background = 'rgba(212,165,116,0.1)'; cursorOutline.style.borderColor = '#F5E6D3'; });
            el.addEventListener('mouseleave', () => { cursorOutline.style.transform = `scale(1)`; cursorOutline.style.background = 'transparent'; cursorOutline.style.borderColor = '#D4A574'; });
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
        toast.innerHTML = `<i class="fas ${ok ? 'fa-check-circle' : 'fa-exclamation-circle'}" style="color: #D4A574;"></i> ${msg}`;
        wrap.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, 2500);
    }
    
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
    
    // Quantity controls
    let currentQty = 1;
    const maxQty = <?php echo $product['stock_quantity'] ?? 10; ?>;
    const qtyInput = document.getElementById('quantity');
    const qtyMinus = document.getElementById('qtyMinus');
    const qtyPlus = document.getElementById('qtyPlus');
    
    if (qtyMinus) {
        qtyMinus.addEventListener('click', () => {
            if (currentQty > 1) {
                currentQty--;
                qtyInput.value = currentQty;
            }
        });
    }
    
    if (qtyPlus) {
        qtyPlus.addEventListener('click', () => {
            if (currentQty < maxQty) {
                currentQty++;
                qtyInput.value = currentQty;
            } else {
                showToast('Only ' + maxQty + ' items available!', false);
            }
        });
    }
    
    if (qtyInput) {
        qtyInput.addEventListener('change', () => {
            let val = parseInt(qtyInput.value);
            if (isNaN(val) || val < 1) val = 1;
            if (val > maxQty) val = maxQty;
            currentQty = val;
            qtyInput.value = currentQty;
        });
    }
    
    // Add to Cart
    async function addToCart(id, btnElement) {
        if (!isLoggedIn) {
            showToast('Please login to continue', false);
            setTimeout(() => window.location.href = 'login.php?redirect=product-detail.php?id=' + id, 1500);
            return;
        }
        if (btnElement && btnElement.disabled) return;
        if (btnElement) {
            btnElement.disabled = true;
            btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        }
        try {
            const res = await fetch('backend/cart/add-to-cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'product_id=' + id + '&quantity=' + currentQty
            });
            const data = await res.json();
            if (data.success) {
                cartCount++;
                updateCartBadge(cartCount);
                showToast('Added to cart 🛍️');
                if (btnElement) {
                    btnElement.style.transform = 'scale(0.95)';
                    setTimeout(() => { btnElement.style.transform = ''; }, 200);
                }
            } else {
                showToast(data.message || 'Error', false);
            }
        } catch(e) { 
            showToast('Something went wrong', false);
        }
        if (btnElement) {
            btnElement.disabled = false;
            btnElement.innerHTML = '<i class="fas fa-shopping-bag"></i> ADD TO CART';
        }
    }
    
    // Wishlist
    async function addToWishlist(id, btn) {
        if (!isLoggedIn) {
            showToast('Please login to continue', false);
            setTimeout(() => window.location.href = 'login.php?redirect=product-detail.php?id=' + id, 1500);
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
            showToast('Something went wrong', false);
        }
        btn.disabled = false;
    }
    
    function escapeHtml(text) {
        if(!text) return '';
        return text.replace(/[&<>]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;'})[m] || m);
    }
    
    // ============ REVIEW SYSTEM ============
    
    // Load Reviews
    async function loadReviews() {
        try {
            const res = await fetch('backend/reviews/get-reviews.php?product_id=' + productId);
            const data = await res.json();
            if (data.success) {
                document.getElementById('avgScore').textContent = data.average_rating || '0';
                document.getElementById('totalReviews').textContent = `Based on ${data.total_reviews} reviews`;
                
                let avgStarsHtml = '';
                let avgRating = data.average_rating || 0;
                let fullStars = Math.floor(avgRating);
                let halfStar = (avgRating - fullStars) >= 0.5;
                for (let i = 1; i <= fullStars; i++) avgStarsHtml += '<i class="fas fa-star"></i>';
                if (halfStar) avgStarsHtml += '<i class="fas fa-star-half-alt"></i>';
                for (let i = 1; i <= 5 - Math.ceil(avgRating); i++) avgStarsHtml += '<i class="far fa-star"></i>';
                document.getElementById('avgStars').innerHTML = avgStarsHtml;
                
                let reviewsHtml = '';
                if (data.reviews.length === 0) {
                    reviewsHtml = '<div style="text-align:center; padding:40px;"><i class="fas fa-comment" style="font-size:48px; color:#5C2E1A; margin-bottom:15px;"></i><p style="color:#C4A484;">No reviews yet. Be the first to share your experience!</p></div>';
                } else {
                    data.reviews.forEach(review => {
                        let starsHtml = '';
                        for (let i = 1; i <= review.rating; i++) starsHtml += '<i class="fas fa-star"></i>';
                        for (let i = review.rating + 1; i <= 5; i++) starsHtml += '<i class="far fa-star"></i>';
                        reviewsHtml += `
                            <div class="review-card">
                                <div class="reviewer-name">${escapeHtml(review.user_name)}</div>
                                <div class="review-date">${new Date(review.created_at).toLocaleDateString()}</div>
                                <div class="review-rating">${starsHtml}</div>
                                ${review.title ? `<div class="review-title">"${escapeHtml(review.title)}"</div>` : ''}
                                <div class="review-comment">${escapeHtml(review.comment)}</div>
                            </div>
                        `;
                    });
                }
                document.getElementById('reviewsList').innerHTML = reviewsHtml;
            }
        } catch(e) {
            console.error('Error loading reviews:', e);
        }
    }
    
    // Simple Modal Controls - NO AJAX, just form submit
    const writeReviewBtn = document.getElementById('writeReviewBtn');
    const reviewModal = document.getElementById('reviewModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    
    if (writeReviewBtn) {
        writeReviewBtn.addEventListener('click', function() {
            reviewModal.classList.add('active');
        });
    }
    
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
            reviewModal.classList.remove('active');
        });
    }
    
    if (reviewModal) {
        reviewModal.addEventListener('click', function(e) {
            if (e.target === reviewModal) {
                reviewModal.classList.remove('active');
            }
        });
    }
    
    // Event Listeners for Add to Cart and Wishlist
    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            addToCart(parseInt(btn.dataset.id), btn);
        });
    });
    
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            addToWishlist(parseInt(btn.dataset.id), btn);
        });
    });
    
    // Newsletter
    document.getElementById('footerNewsletterForm')?.addEventListener('submit', (e) => {
        e.preventDefault();
        showToast('Thanks for subscribing! Check your email for 15% off ✨');
        e.target.reset();
    });
    
    // Load reviews on page load
    loadReviews();
</script>
</body>
</html>