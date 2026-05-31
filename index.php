<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

// Get products
$new_arrivals_query = "SELECT * FROM products WHERE is_new = 1 AND in_stock = 1 ORDER BY created_at DESC LIMIT 8";
$new_arrivals_result = mysqli_query($conn, $new_arrivals_query);
$new_arrivals = $new_arrivals_result ? mysqli_fetch_all($new_arrivals_result, MYSQLI_ASSOC) : [];

$best_sellers_query = "SELECT * FROM products WHERE is_bestseller = 1 AND in_stock = 1 LIMIT 8";
$best_sellers_result = mysqli_query($conn, $best_sellers_query);
$best_sellers = $best_sellers_result ? mysqli_fetch_all($best_sellers_result, MYSQLI_ASSOC) : [];

// Order count with prepared statement
$order_count = 0;
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $order_count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
    if ($order_count_stmt) {
        mysqli_stmt_bind_param($order_count_stmt, "i", $user_id);
        mysqli_stmt_execute($order_count_stmt);
        $order_count_result = mysqli_stmt_get_result($order_count_stmt);
        $order_count_data = mysqli_fetch_assoc($order_count_result);
        $order_count = $order_count_data['count'] ?? 0;
        mysqli_stmt_close($order_count_stmt);
    }
}

// Wishlist IDs with prepared statement
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
    <title>Velvet Aura — Aesthetic Clothing</title>
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
        .nav-link:hover::after { width: 100%; }
        
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
        .mobile-product-item .product-name { font-size: 11px; color: #D4B5A7; }
        .mobile-product-item .product-price { font-size: 10px; color: #F5E6D3; }
        
        /* Right Icons */
        .navbar-right { display: flex; gap: 12px; align-items: center; }
        .icon-btn { position: relative; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; color: #D4B5A7; font-size: 18px; transition: all 0.3s; border-radius: 50%; text-decoration: none; }
        .icon-btn:hover { color: #F5E6D3; background: rgba(92,46,26,0.2); transform: translateY(-2px); }
        .badge-count { position: absolute; top: -3px; right: -3px; background: #5C2E1A; color: #F5E6D3; font-size: 9px; font-weight: 700; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        
        /* Hero Section */
        .hero { min-height: 85vh; background: linear-gradient(145deg, #915e41 0%, #2C1810 100%); display: flex; align-items: center; position: relative; overflow: hidden; }
        .hero::before { content: ''; position: absolute; top: -50%; right: -20%; width: 80%; height: 150%; background: radial-gradient(circle, rgba(92,46,26,0.15) 0%, transparent 70%); pointer-events: none; }
        .hero-label { font-size: 11px; letter-spacing: 4px; text-transform: uppercase; color: #C4A484; margin-bottom: 20px; font-weight: 500; }
        .hero-title { font-size: 72px; font-weight: 800; line-height: 1.1; margin-bottom: 25px; color: #F5E6D3; letter-spacing: -2px; }
        .hero-title em { font-style: italic; color: #5C2E1A; }
        .hero-text { font-size: 16px; color: #C4A484; max-width: 500px; margin-bottom: 35px; line-height: 1.6; }
        .hero-buttons { display: flex; gap: 20px; flex-wrap: wrap; }
        .btn-primary {   background: #5C2E1A; color: #F5E6D3; padding: 14px 38px; border-radius: 50px; text-decoration: none; font-size: 11px; letter-spacing: 2px; text-transform: uppercase; font-weight: 700; transition: all 0.3s; display: inline-block; border: none; cursor: pointer; }
        .btn-primary:hover { background: #946c55; transform: translateY(-3px); box-shadow: 0 10px 25px rgba(92,46,26,0.4); }
        .btn-outline { border: 1.5px solid #b37b63; color:white ; padding: 14px 38px; border-radius: 50px; text-decoration: none; font-size: 11px; letter-spacing: 2px; text-transform: uppercase; font-weight: 600; transition: all 0.3s; background: transparent; display: inline-block; cursor: pointer; }
        .btn-outline:hover { background: #5C2E1A; color: #F5E6D3; transform: translateY(-3px); }
        .hero-image img { width: 100%; border-radius: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); transition: transform 0.5s ease; }
        .hero-image img:hover { transform: scale(1.02); }
        
        /* Sections */
        .categories-section, .section { padding: 80px 0; }
        .section-header { text-align: center; margin-bottom: 50px; }
        .section-header h2 { font-size: 38px; font-weight: 700; color: #F5E6D3; margin-bottom: 10px; letter-spacing: -1px; position: relative; display: inline-block; }
        .section-header h2::after { content: ''; position: absolute; bottom: -10px; left: 50%; transform: translateX(-50%); width: 50px; height: 2px; background: #5C2E1A; }
        .section-header p { font-size: 12px; letter-spacing: 3px; text-transform: uppercase; color: #C4A484; margin-top: 15px; }
        
        /* Category Cards */
        .category-card { background: #3D2314; border-radius: 28px; overflow: hidden; transition: all 0.5s ease; box-shadow: 0 8px 25px rgba(0,0,0,0.2); text-decoration: none; display: block; position: relative; border: 1px solid rgba(92,46,26,0.3); }
        .category-card::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(92,46,26,0.1), transparent); transition: left 0.5s; z-index: 1; }
        .category-card:hover::before { left: 100%; }
        .category-card:hover { transform: translateY(-12px); box-shadow: 0 30px 45px rgba(0,0,0,0.3); border-color: #5C2E1A; }
        .category-image { height: 260px; overflow: hidden; position: relative; }
        .category-image img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.7s ease; }
        .category-card:hover .category-image img { transform: scale(1.08); }
        .category-overlay { position: absolute; bottom: -100%; left: 0; width: 100%; text-align: center; transition: all 0.4s; background: linear-gradient(transparent, rgba(0,0,0,0.8)); padding: 20px; color: white; }
        .category-card:hover .category-overlay { bottom: 0; }
        .category-overlay h3 { font-size: 18px; font-weight: 700; margin-bottom: 6px; color: #bc3425; }
        .category-overlay p { font-size: 12px; color: #F5E6D3; letter-spacing: 2px; text-transform: uppercase; font-weight: 500; }
        
        /* Product Cards */
        .product-card { background: #3D2314; border-radius: 24px; overflow: hidden; transition: all 0.4s ease; border: 1px solid rgba(92,46,26,0.3); height: 100%; display: flex; flex-direction: column; position: relative; }
        .product-card::after { content: ''; position: absolute; bottom: 0; left: 0; width: 0; height: 3px; background: linear-gradient(90deg, #5C2E1A, #D4B5A7); transition: width 0.5s; }
        .product-card:hover::after { width: 100%; }
        .product-card:hover { transform: translateY(-12px); box-shadow: 0 30px 45px rgba(0,0,0,0.3); border-color: #5C2E1A; }
        .product-image { position: relative; height: 300px; overflow: hidden; background: #2C1810; }
        .product-image img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.7s ease; }
        .product-card:hover .product-image img { transform: scale(1.08); }
        .product-badge { position: absolute; top: 12px; left: 12px; background: #5C2E1A; color: #F5E6D3; padding: 4px 12px; border-radius: 30px; font-size: 10px; font-weight: 700; letter-spacing: 1px; z-index: 2; }
        .product-badge.sale { background: #3D2314; color: #F5E6D3; left: auto; right: 12px; }
        .product-actions { position: absolute; bottom: -55px; left: 0; width: 100%; display: flex; justify-content: center; gap: 12px; transition: all 0.3s; z-index: 2; }
        .product-card:hover .product-actions { bottom: 15px; }
        .product-actions button { width: 40px; height: 40px; background: #F5E6D3; border: none; border-radius: 50%; cursor: pointer; transition: all 0.3s; font-size: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); color: #3D2314; }
        .product-actions button:hover { background: #5C2E1A; color: #F5E6D3; transform: translateY(-2px); }
        .product-actions button.active { background: #5C2E1A; color: #F5E6D3; }
        .product-info { padding: 18px; text-align: center; background: #3D2314; }
        .product-info h4 { font-size: 16px; font-weight: 700; margin-bottom: 6px; color: #F5E6D3; }
        .product-price { font-weight: 700; font-size: 17px; color: #C4A484; }
        .product-old-price { text-decoration: line-through; color: #8B6B4A; font-size: 12px; margin-left: 6px; font-weight: normal; }
        .product-rating { margin: 6px 0; }
        .product-rating i { font-size: 10px; color: #ffc107; }
        .btn-buy { width: 100%; background: #5C2E1A; color: #F5E6D3; border: none; padding: 12px; border-radius: 50px; font-size: 11px; letter-spacing: 1.5px; font-weight: 700; cursor: pointer; transition: all 0.3s; margin-top: 10px; }
        .btn-buy:hover { background: #3D2314; transform: translateY(-2px); }
        
        /* Banner */
        .banner { background: linear-gradient(135deg, #3D2314 0%, #2C1810 100%); border-radius: 32px; overflow: hidden; border: 1px solid rgba(92,46,26,0.3); }
        .banner-content { padding: 55px; text-align: center; }
        .banner-content h2 { font-size: 45px; font-weight: 700; margin-bottom: 12px; color: #5C2E1A; }
        .banner-content p { color: #C4A484; margin-bottom: 25px; }
        
        /* Features Section */
        .features-section { padding: 60px 0; background: #2C1810; }
        .feature-card { text-align: center; padding: 30px 20px; transition: all 0.4s; border-radius: 24px; background: #3D2314; border: 1px solid rgba(92,46,26,0.2); }
        .feature-card:hover { transform: translateY(-8px); box-shadow: 0 25px 40px rgba(0,0,0,0.2); border-color: #5C2E1A; }
        .feature-icon { width: 65px; height: 65px; background: #956b5b; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; font-size: 26px; color: #5C2E1A; transition: all 0.3s; }
        .feature-card:hover .feature-icon { background: #5C2E1A; color: #F5E6D3; }
        .feature-card h4 { font-size: 17px; font-weight: 700; margin-bottom: 10px; color: #F5E6D3; }
        .feature-card p { font-size: 12px; color: #A08874; line-height: 1.5; }
        
        /* Newsletter */
        .newsletter { background: #3D2314; padding: 60px 0; border-top: 1px solid rgba(92,46,26,0.2); border-bottom: 1px solid rgba(92,46,26,0.2); }
        .newsletter-content { text-align: center; max-width: 500px; margin: 0 auto; }
        .newsletter-content h3 { font-size: 34px; font-weight: 700; color:white; margin-bottom: 10px; }
        .newsletter-content p { color: #C4A484; margin-bottom: 25px; }
        .newsletter-form { display: flex; gap: 12px; }
        .newsletter-form input { flex: 1; padding: 14px 20px; border: 1px solid rgba(92,46,26,0.3); border-radius: 50px; outline: none; font-size: 13px; background: #2C1810; color: #F5E6D3; }
        .newsletter-form input:focus { border-color: #5C2E1A; }
        .newsletter-form button { background: #5C2E1A; color: #F5E6D3; border: none; padding: 14px 28px; border-radius: 50px; font-weight: 700; letter-spacing: 1px; cursor: pointer; transition: all 0.3s; }
        .newsletter-form button:hover { background: #2C1810; color: #F5E6D3; transform: translateY(-2px); }
        
        /* Footer */
        .footer { background: #2C1810; padding: 60px 0 0; border-top: 1px solid rgba(92,46,26,0.2); }
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
        .badge-eco { display: flex; align-items: center; gap: 6px; background: #3D2314; padding: 5px 12px; border-radius: 20px; font-size: 10px; font-weight: 600; color: #caa695; border: 1px solid rgba(92,46,26,0.3); }
        
        /* Toast */
        .toast-wrap { position: fixed; bottom: 30px; right: 30px; z-index: 9999; }
        .toast-msg { background: #3D2314; border-left: 3px solid #5C2E1A; padding: 12px 20px; margin-top: 8px; border-radius: 12px; color: #F5E6D3; font-size: 12px; transform: translateX(120%); transition: transform 0.3s; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .toast-msg.show { transform: translateX(0); }
        
        /* Responsive */
        @media (max-width: 1200px) { .hero-title { font-size: 56px; } }
        @media (max-width: 992px) { 
            .desktop-nav { display: none; } 
            .mobile-toggle { display: block; } 
            .navbar .container { padding: 10px 20px; } 
            .navbar-right { margin-left: auto; margin-right: 15px; }
            .hero-title { font-size: 48px; }
            .footer-main { grid-template-columns: repeat(2, 1fr); }
            .footer-brand-section { grid-column: span 2; text-align: center; }
            .footer-logo { justify-content: center; }
            .footer-contact { align-items: center; }
            .footer-newsletter-section { grid-column: span 2; max-width: 100%; text-align: center; }
            .footer-bottom-content { flex-direction: column; text-align: center; }
            .payment-methods { justify-content: center; }
        }
        @media (max-width: 768px) { 
            .hero-title { font-size: 36px; }
            .hero { text-align: center; padding: 60px 0; }
            .hero-text { margin-left: auto; margin-right: auto; }
            .hero-buttons { justify-content: center; }
            .banner-content h2 { font-size: 28px; }
            .newsletter-form { flex-direction: column; }
            .categories-section, .section { padding: 60px 0; }
            .footer-main { grid-template-columns: 1fr; }
            .footer-brand-section { grid-column: span 1; }
            .footer-newsletter-section { grid-column: span 1; }
        }
                /* ADD TO BAG Button Styling */
        .add-to-cart-btn {
            width: 100%;
            background: linear-gradient(135deg, #D4A574, #C4956A);
            border: none;
            padding: 12px 20px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: #3D2314;
            cursor: pointer;
            border-radius: 50px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .add-to-cart-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .add-to-cart-btn:hover::before {
            left: 100%;
        }

        .add-to-cart-btn:hover {
            background: #F5E6D3;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(212,165,116,0.4);
        }

        .add-to-cart-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Loading State */
        .add-to-cart-btn.loading {
            background: #3D2314;
            color: #D4A574;
        }

        .add-to-cart-btn.loading::before {
            display: none;
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
                <div class="dropdown"><a href="#newArrivals"><i class="fas fa-sparkles"></i> New Arrivals</a><a href="#bestSellers"><i class="fas fa-fire"></i> Best Sellers</a><a href="#categories"><i class="fas fa-th-large"></i> Shop by Category</a><a href="#featured"><i class="fas fa-gem"></i> Featured Collection</a><div class="dropdown-divider"></div><a href="lookbook.php"><i class="fas fa-camera"></i> Lookbook</a><a href="about.php"><i class="fas fa-heart"></i> About Us</a></div>
            </div>
            <div class="nav-item"><a href="shop.php" class="nav-link"><i class="fas fa-store"></i> SHOP</a>
                <div class="dropdown dropdown-products"><div class="dropdown-header"><i class="fas fa-gem"></i> ✨ FEATURED PRODUCTS</div><div class="row g-2 p-2"><?php $count = 0; foreach($dropdown_products as $prod): if($count++ >= 4) break; ?><div class="col-6"><a href="product-detail.php?id=<?php echo $prod['id']; ?>" class="product-item"><img src="assets/images/<?php echo htmlspecialchars($prod['image']); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>" onerror="this.src='https://placehold.co/50x50/F0E8DF/8B5A2B?text=VA'"><div><div class="product-name"><?php echo htmlspecialchars($prod['name']); ?></div><div class="product-price">$<?php echo number_format($prod['price'], 2); ?></div></div></a></div><?php endforeach; ?></div><div class="dropdown-divider"></div><div class="dropdown-header"><i class="fas fa-tags"></i> 📁 SHOP BY CATEGORY</div><?php foreach($db_categories as $cat): ?><a href="shop.php?category=<?php echo htmlspecialchars($cat['slug']); ?>"><i class="fas fa-chevron-right"></i> <?php echo htmlspecialchars($cat['name']); ?></a><?php endforeach; ?><div class="dropdown-divider"></div><a href="shop.php"><i class="fas fa-bag-shopping"></i> View All Products →</a><a href="shop.php?filter=new"><i class="fas fa-sparkles"></i> New Arrivals</a><a href="shop.php?filter=bestseller"><i class="fas fa-crown"></i> Best Sellers</a></div>
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
            <div class="mobile-nav-item"><div class="mobile-nav-link"><i class="fas fa-home"></i> HOME <i class="fas fa-chevron-down mobile-dropdown-toggle"></i></div><div class="mobile-dropdown"><a href="#newArrivals"><i class="fas fa-sparkles"></i> New Arrivals</a><a href="#bestSellers"><i class="fas fa-fire"></i> Best Sellers</a><a href="#categories"><i class="fas fa-th-large"></i> Shop by Category</a><a href="#featured"><i class="fas fa-gem"></i> Featured Collection</a><a href="lookbook.php"><i class="fas fa-camera"></i> Lookbook</a><a href="about.php"><i class="fas fa-heart"></i> About Us</a></div></div>
            <div class="mobile-nav-item"><div class="mobile-nav-link"><i class="fas fa-store"></i> SHOP <i class="fas fa-chevron-down mobile-dropdown-toggle"></i></div><div class="mobile-dropdown"><div class="dropdown-header">✨ FEATURED PRODUCTS</div><div class="mobile-product-grid"><?php $count2 = 0; foreach($dropdown_products as $prod): if($count2++ >= 4) break; ?><a href="product-detail.php?id=<?php echo $prod['id']; ?>" class="mobile-product-item"><img src="assets/images/<?php echo htmlspecialchars($prod['image']); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>"><div><div class="product-name"><?php echo htmlspecialchars($prod['name']); ?></div><div class="product-price">$<?php echo number_format($prod['price'], 2); ?></div></div></a><?php endforeach; ?></div><div class="dropdown-divider"></div><div class="dropdown-header">📁 SHOP BY CATEGORY</div><?php foreach($db_categories as $cat): ?><a href="shop.php?category=<?php echo htmlspecialchars($cat['slug']); ?>"><i class="fas fa-chevron-right"></i> <?php echo htmlspecialchars($cat['name']); ?></a><?php endforeach; ?><div class="dropdown-divider"></div><a href="shop.php"><i class="fas fa-bag-shopping"></i> View All Products</a><a href="shop.php?filter=new"><i class="fas fa-sparkles"></i> New Arrivals</a><a href="shop.php?filter=bestseller"><i class="fas fa-crown"></i> Best Sellers</a></div></div>
            <div class="mobile-nav-item"><a href="lookbook.php" class="mobile-nav-link"><i class="fas fa-camera"></i> LOOKBOOK</a></div>
            <div class="mobile-nav-item"><div class="mobile-nav-link"><i class="fas fa-info-circle"></i> ABOUT <i class="fas fa-chevron-down mobile-dropdown-toggle"></i></div><div class="mobile-dropdown"><a href="about.php#our-story"><i class="fas fa-leaf"></i> Our Story</a><a href="about.php#sustainability"><i class="fas fa-globe"></i> Sustainability</a><a href="about.php#careers"><i class="fas fa-briefcase"></i> Careers</a><a href="contact.php"><i class="fas fa-envelope"></i> Contact Us</a></div></div>
            <?php if($is_logged_in): ?><div class="mobile-nav-item"><a href="my-orders.php" class="mobile-nav-link"><i class="fas fa-box"></i> MY ORDERS</a></div><?php endif; ?>
        </div>
    </div>
</nav>

<section class="hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6" data-aos="fade-right" data-aos-duration="800">
                <p class="hero-label">✦ EST. 2026 ✦</p>
                <h1 class="hero-title">Elevate Your<br><em>Everyday</em> Style</h1>
                <p class="hero-text">Discover timeless pieces designed for the modern soul. Ethical, sustainable, and effortlessly chic.</p>
                <div class="hero-buttons">
                    <a href="shop.php" class="btn-primary">Shop Now →</a>
                    <a href="lookbook.php" class="btn-outline">View Lookbook →</a>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left" data-aos-duration="800" data-aos-delay="200">
                <div class="hero-image">
                    <img src="assets/images/about.jpg" alt="Velvet Aura Fashion Model" onerror="this.src='https://placehold.co/600x600/F0E8DF/8B5A2B?text=Velvet+Aura'">
                </div>
            </div>
        </div>
    </div>
</section>

<section class="categories-section" id="categories">
    <div class="container">
        <div class="section-header" data-aos="fade-up"><h2>Shop by Category</h2><p>Find your perfect style</p></div>
        <div class="row g-4">
            <div class="col-lg-3 col-md-6"><a href="shop.php?category=dresses" class="category-card"><div class="category-image"><img src="assets/images/Dress.jpg" alt="Dresses" onerror="this.src='https://placehold.co/400x320/F0E8DF/8B5A2B?text=Dresses'"><div class="category-overlay"><h3>Dresses</h3><p>Shop Now →</p></div></div></a></div>
            <div class="col-lg-3 col-md-6"><a href="shop.php?category=tops" class="category-card"><div class="category-image"><img src="assets/images/top.jpg" alt="Tops" onerror="this.src='https://placehold.co/400x320/F0E8DF/8B5A2B?text=Tops'"><div class="category-overlay"><h3>Tops</h3><p>Shop Now →</p></div></div></a></div>
            <div class="col-lg-3 col-md-6"><a href="shop.php?category=bottoms" class="category-card"><div class="category-image"><img src="assets/images/bottom.jpg" alt="Bottoms" onerror="this.src='https://placehold.co/400x320/F0E8DF/8B5A2B?text=Bottoms'"><div class="category-overlay"><h3>Bottoms</h3><p>Shop Now →</p></div></div></a></div>
            <div class="col-lg-3 col-md-6"><a href="shop.php?category=outerwear" class="category-card"><div class="category-image"><img src="assets/images/outerware.jpg" alt="Outerwear" onerror="this.src='https://placehold.co/400x320/F0E8DF/8B5A2B?text=Outerwear'"><div class="category-overlay"><h3>Outerwear</h3><p>Shop Now →</p></div></div></a></div>
            <div class="col-lg-3 col-md-6"><a href="shop.php?category=hoodies" class="category-card"><div class="category-image"><img src="assets/images/hoodies.jpg" alt="Hoodies" onerror="this.src='https://placehold.co/400x320/F0E8DF/8B5A2B?text=Hoodies'"><div class="category-overlay"><h3>Hoodies</h3><p>Shop Now →</p></div></div></a></div>
            <div class="col-lg-3 col-md-6"><a href="shop.php?category=t-shirts" class="category-card"><div class="category-image"><img src="assets/images/T-shirts.jpg" alt="T-Shirts" onerror="this.src='https://placehold.co/400x320/F0E8DF/8B5A2B?text=T-Shirts'"><div class="category-overlay"><h3>T-Shirts</h3><p>Shop Now →</p></div></div></a></div>
            <div class="col-lg-3 col-md-6"><a href="shop.php?category=accessories" class="category-card"><div class="category-image"><img src="assets/images/accessories aesthetic.jpg" alt="Accessories" onerror="this.src='https://placehold.co/400x320/F0E8DF/8B5A2B?text=Accessories'"><div class="category-overlay"><h3>Accessories</h3><p>Shop Now →</p></div></div></a></div>
            <div class="col-lg-3 col-md-6"><a href="shop.php?category=footwear" class="category-card"><div class="category-image"><img src="assets/images/foootware.jpg" alt="Footwear" onerror="this.src='https://placehold.co/400x320/F0E8DF/8B5A2B?text=Footwear'"><div class="category-overlay"><h3>Footwear</h3><p>Shop Now →</p></div></div></a></div>
        </div>
    </div>
</section>

<section class="section" id="newArrivals">
    <div class="container">
        <div class="section-header" data-aos="fade-up"><h2>New Arrivals</h2><p>Fresh drops just for you</p></div>
        <?php if(!empty($new_arrivals)): ?>
        <div class="row g-4">
            <?php foreach($new_arrivals as $index => $product):
                $discount = isset($product['old_price']) && $product['old_price'] > 0 ? round((($product['old_price'] - $product['price']) / $product['old_price']) * 100) : null;
                $is_in_wishlist = in_array($product['id'], $wishlist_ids);
                $rating = floatval($product['rating'] ?? 4.5);
                $fullStars = floor($rating);
                $halfStar = ($rating - $fullStars) >= 0.5;
            ?>
            <div class="col-lg-3 col-md-6" data-aos="zoom-in" data-aos-delay="<?php echo $index * 50; ?>">
                <div class="product-card">
                    <a href="product-detail.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit;">
                        <div class="product-image">
                            <img src="assets/images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='https://placehold.co/400x500/F0E8DF/8B5A2B?text=<?php echo urlencode($product['name']); ?>'">
                            <span class="product-badge">NEW</span>
                            <?php if($discount): ?><span class="product-badge sale">-<?php echo $discount; ?>%</span><?php endif; ?>
                            <div class="product-actions">
                                <button class="add-to-cart" data-id="<?php echo $product['id']; ?>"><i class="fas fa-shopping-bag"></i></button>
                                <button class="add-to-wishlist <?php echo $is_in_wishlist ? 'active' : ''; ?>" data-id="<?php echo $product['id']; ?>"><i class="far fa-heart"></i></button>
                            </div>
                        </div>
                        <div class="product-info">
                            <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                            <div class="product-price">
                                $<?php echo number_format($product['price'], 2); ?>
                                <?php if(isset($product['old_price']) && $product['old_price']): ?>
                                <span class="product-old-price">$<?php echo number_format($product['old_price'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="product-rating">
                                <?php for($i=1; $i<=$fullStars; $i++): ?><i class="fas fa-star"></i><?php endfor; ?>
                                <?php if($halfStar): ?><i class="fas fa-star-half-alt"></i><?php endif; ?>
                                <?php for($i=1; $i<=5-ceil($rating); $i++): ?><i class="far fa-star"></i><?php endfor; ?>
                            </div>
                        </div>
                    </a>
                    <div style="padding: 0 18px 18px;"><button class="add-to-cart-btn" data-id="<?php echo $product['id']; ?>">ADD TO BAG</button></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5"><a href="shop.php?filter=new" class="btn-outline" style="border-color: #5C2E1A; color: #d09e89;">View All New Arrivals →</a></div>
        <?php else: ?>
        <div class="text-center"><p>No new arrivals available at the moment.</p></div>
        <?php endif; ?>
    </div>
</section>

<section class="section" id="featured">
    <div class="container">
        <div class="banner" data-aos="zoom-in">
            <div class="banner-content">
                <h2>Limited Edition Drop</h2>
                <p>Discover our exclusive collection - only 100 pieces available</p>
                <a href="shop.php" class="btn-primary" style="background: #5C2E1A;">Shop Now →</a>
            </div>
        </div>
    </div>
</section>

<section class="section" id="bestSellers">
    <div class="container">
        <div class="section-header" data-aos="fade-up"><h2>Best Sellers</h2><p>Most loved by our community</p></div>
        <?php if(!empty($best_sellers)): ?>
        <div class="row g-4">
            <?php foreach($best_sellers as $index => $product):
                $discount = isset($product['old_price']) && $product['old_price'] > 0 ? round((($product['old_price'] - $product['price']) / $product['old_price']) * 100) : null;
                $is_in_wishlist = in_array($product['id'], $wishlist_ids);
                $rating = floatval($product['rating'] ?? 4.8);
                $fullStars = floor($rating);
                $halfStar = ($rating - $fullStars) >= 0.5;
            ?>
            <div class="col-lg-3 col-md-6" data-aos="zoom-in" data-aos-delay="<?php echo $index * 50; ?>">
                <div class="product-card">
                    <a href="product-detail.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit;">
                        <div class="product-image">
                            <img src="assets/images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='https://placehold.co/400x500/F0E8DF/8B5A2B?text=<?php echo urlencode($product['name']); ?>'">
                            <span class="product-badge">BESTSELLER</span>
                            <?php if($discount): ?><span class="product-badge sale">-<?php echo $discount; ?>%</span><?php endif; ?>
                            <div class="product-actions">
                                <button class="add-to-cart" data-id="<?php echo $product['id']; ?>"><i class="fas fa-shopping-bag"></i></button>
                                <button class="add-to-wishlist <?php echo $is_in_wishlist ? 'active' : ''; ?>" data-id="<?php echo $product['id']; ?>"><i class="far fa-heart"></i></button>
                            </div>
                        </div>
                        <div class="product-info">
                            <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                            <div class="product-price">
                                $<?php echo number_format($product['price'], 2); ?>
                                <?php if(isset($product['old_price']) && $product['old_price']): ?>
                                <span class="product-old-price">$<?php echo number_format($product['old_price'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="product-rating">
                                <?php for($i=1; $i<=$fullStars; $i++): ?><i class="fas fa-star"></i><?php endfor; ?>
                                <?php if($halfStar): ?><i class="fas fa-star-half-alt"></i><?php endif; ?>
                                <?php for($i=1; $i<=5-ceil($rating); $i++): ?><i class="far fa-star"></i><?php endfor; ?>
                            </div>
                        </div>
                    </a>
                    <div style="padding: 0 18px 18px;"><button class="add-to-cart-btn" data-id="<?php echo $product['id']; ?>">ADD TO BAG</button></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5"><a href="shop.php?filter=bestseller" class="btn-outline" style="border-color: #5C2E1A; color: #e1a991;">View All Best Sellers →</a></div>
        <?php else: ?>
        <div class="text-center"><p>No best sellers available at the moment.</p></div>
        <?php endif; ?>
    </div>
</section>

<section class="features-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up"><h2>Why Choose Us</h2><p>What makes Velvet Aura different</p></div>
        <div class="row g-4">
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="0"><div class="feature-card"><div class="feature-icon"><i class="fas fa-leaf"></i></div><h4>Sustainable Materials</h4><p>100% organic, recycled, and eco-friendly fabrics.</p></div></div>
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100"><div class="feature-card"><div class="feature-icon"><i class="fas fa-hand-sparkles"></i></div><h4>Ethical Production</h4><p>Fair wages and safe working conditions for all artisans.</p></div></div>
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200"><div class="feature-card"><div class="feature-icon"><i class="fas fa-infinity"></i></div><h4>Timeless Design</h4><p>Pieces designed to last beyond seasonal trends.</p></div></div>
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300"><div class="feature-card"><div class="feature-icon"><i class="fas fa-truck-fast"></i></div><h4>Free Shipping</h4><p>Free shipping on all orders over $100.</p></div></div>
        </div>
    </div>
</section>

<section class="newsletter">
    <div class="container">
        <div class="newsletter-content" data-aos="fade-up">
            <h3>Join the Community</h3>
            <p>Get 15% off your first order & exclusive updates</p>
            <form class="newsletter-form" id="newsletterForm">
                <input type="email" placeholder="Enter your email address" required>
                <button type="submit">Subscribe →</button>
            </form>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <div class="footer-main">
            <div class="footer-brand-section"><div class="footer-logo"><span class="logo-icon">✦</span><span class="logo-text">VELVET<span>AURA</span></span></div><p class="footer-description">Ethical fashion for the conscious soul. Timeless pieces designed to last beyond seasons.</p><div class="footer-contact"><div class="contact-item"><i class="fas fa-envelope"></i><a href="mailto:hello@velvetaura.com">hello@velvetaura.com</a></div><div class="contact-item"><i class="fas fa-phone-alt"></i><a href="tel:+15551234567">+1 (555) 123-4567</a></div><div class="contact-item"><i class="fas fa-map-marker-alt"></i><span>New York, NY</span></div></div></div>
            <div class="footer-links-section"><h4 class="footer-title"><i class="fas fa-compass"></i> Quick Links</h4><ul><li><a href="shop.php"><i class="fas fa-chevron-right"></i> Shop All</a></li><li><a href="shop.php?filter=new"><i class="fas fa-chevron-right"></i> New Arrivals</a></li><li><a href="shop.php?filter=bestseller"><i class="fas fa-chevron-right"></i> Best Sellers</a></li><li><a href="lookbook.php"><i class="fas fa-chevron-right"></i> Lookbook</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> About Us</a></li></ul></div>
            <div class="footer-links-section"><h4 class="footer-title"><i class="fas fa-tags"></i> Categories</h4><ul><?php $footer_cats = array_slice($db_categories, 0, 6); foreach($footer_cats as $cat): ?><li><a href="shop.php?category=<?php echo htmlspecialchars($cat['slug']); ?>"><i class="fas fa-chevron-right"></i> <?php echo htmlspecialchars($cat['name']); ?></a></li><?php endforeach; ?></ul></div>
            <div class="footer-links-section"><h4 class="footer-title"><i class="fas fa-headset"></i> Support</h4><ul><li><a href="#"><i class="fas fa-chevron-right"></i> FAQ</a></li><li><a href="#"><i class="fas fa-chevron-right"></i> Shipping Info</a></li><li><a href="#"><i class="fas fa-chevron-right"></i> Returns</a></li><li><a href="#"><i class="fas fa-chevron-right"></i> Size Guide</a></li></ul></div>
            <div class="footer-newsletter-section"><h4 class="footer-title"><i class="fas fa-envelope-open-text"></i> Stay Connected</h4><p class="newsletter-text">Get 15% off your first order!</p><form class="footer-newsletter-form" id="footerNewsletterForm"><div class="input-group"><input type="email" placeholder="Your email address" required><button type="submit"><i class="fas fa-paper-plane"></i></button></div></form><div class="footer-social"><a href="#" class="social-icon"><i class="fab fa-instagram"></i></a><a href="#" class="social-icon"><i class="fab fa-pinterest"></i></a><a href="#" class="social-icon"><i class="fab fa-tiktok"></i></a><a href="#" class="social-icon"><i class="fab fa-youtube"></i></a></div></div>
        </div>
        <div class="footer-bottom"><div class="footer-bottom-content"><div class="copyright"><i class="far fa-copyright"></i> <?php echo date('Y'); ?> Velvet Aura. All rights reserved.</div><div class="payment-methods"><i class="fab fa-cc-visa"></i><i class="fab fa-cc-mastercard"></i><i class="fab fa-cc-amex"></i><i class="fab fa-cc-paypal"></i></div><div class="footer-badges"><span class="badge-eco"><i class="fas fa-leaf"></i> Eco-Friendly</span><span class="badge-eco"><i class="fas fa-recycle"></i> Sustainable</span></div></div></div>
    </div>
</footer>

<div class="toast-wrap" id="toastWrap"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: true, offset: 100 });
    
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
        document.querySelectorAll('a, button, .product-card, .icon-btn, .category-card').forEach(el => {
            el.addEventListener('mouseenter', () => { cursorOutline.style.transform = `scale(1.5)`; cursorOutline.style.background = 'rgba(139,90,43,0.1)'; cursorOutline.style.borderColor = '#F5E6D3'; });
            el.addEventListener('mouseleave', () => { cursorOutline.style.transform = `scale(1)`; cursorOutline.style.background = 'transparent'; cursorOutline.style.borderColor = '#8B5A2B'; });
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
        toast.innerHTML = `<i class="fas ${ok ? 'fa-check-circle' : 'fa-exclamation-circle'}" style="color:#8B5A2B;"></i> ${msg}`;
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
    
    // ✅ ONLY ONE ADD TO CART FUNCTION
    async function addToCart(id, btnElement) {
        if (!isLoggedIn) { showToast('Please login to continue', false); setTimeout(() => window.location.href = 'login.php?redirect=index.php', 1500); return; }
        if (btnElement && btnElement.disabled) return;
        if (btnElement) { btnElement.disabled = true; btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }
        try {
            const res = await fetch('backend/cart/add-to-cart.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'product_id=' + id + '&quantity=1' });
            const data = await res.json();
            if (data.success) {
                cartCount++; updateCartBadge(cartCount);
                showToast('Added to bag ✨');
                if (btnElement) { btnElement.style.transform = 'scale(0.9)'; setTimeout(() => { btnElement.style.transform = ''; }, 200); }
            } else { showToast(data.message || 'Error', false); }
        } catch(e) { showToast('Something went wrong', false); }
        if (btnElement) { btnElement.disabled = false; btnElement.innerHTML = 'ADD TO BAG'; }
    }
    
    async function addToWishlist(id, btn) {
        if (!isLoggedIn) { showToast('Please login to continue', false); setTimeout(() => window.location.href = 'login.php?redirect=index.php', 1500); return; }
        btn.disabled = true;
        try {
            const res = await fetch('backend/wishlist/add-to-wishlist.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'product_id=' + id });
            const data = await res.json();
            if (data.success) {
                btn.classList.toggle('active', data.action === 'added');
                if (data.action === 'added') { wishlistCount++; showToast('Saved to wishlist ❤️'); }
                else { wishlistCount--; showToast('Removed from wishlist 💔'); }
                updateWishlistBadge(wishlistCount);
                btn.style.transform = 'scale(1.2)'; setTimeout(() => { btn.style.transform = ''; }, 200);
            }
        } catch(e) { showToast('Something went wrong', false); }
        btn.disabled = false;
    }
    
    function buyNow(id) {
        if (!isLoggedIn) { showToast('Please login to continue', false); setTimeout(() => window.location.href = 'login.php?redirect=index.php', 1500); return; }
        fetch('backend/cart/add-to-cart.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'product_id=' + id + '&quantity=1' }).then(() => window.location.href = 'checkout.php');
    }
    
    // ✅ ONLY ONE EVENT LISTENER
    document.querySelectorAll('.add-to-cart, .add-to-cart-btn').forEach(btn => { 
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
    document.querySelectorAll('.buy-now').forEach(btn => { 
        btn.addEventListener('click', (e) => { 
            e.preventDefault(); 
            e.stopPropagation(); 
            buyNow(parseInt(btn.dataset.id)); 
        }); 
    });
    
    document.getElementById('newsletterForm')?.addEventListener('submit', (e) => { e.preventDefault(); showToast('Thanks for subscribing! Check your email for 15% off.'); e.target.reset(); });
    document.getElementById('footerNewsletterForm')?.addEventListener('submit', (e) => { e.preventDefault(); showToast('Thanks for subscribing! Check your email for 15% off ✨'); e.target.reset(); });
    
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== "#" && href !== "#categories" && href !== "#newArrivals" && href !== "#bestSellers" && href !== "#featured") return;
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) { target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });
    });
</script>
</body>
</html>