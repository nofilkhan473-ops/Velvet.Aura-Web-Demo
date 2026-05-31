<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'backend/config/database.php';
require_once 'backend/includes/functions.php';

$is_logged_in = isLoggedIn();

// If already logged in, redirect to home
if ($is_logged_in) {
    header('Location: index.php');
    exit();
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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Email validation - must be like name@gmail.com
    $email_pattern = '/^[a-zA-Z0-9._%+-]+@(gmail\.com|yahoo\.com|outlook\.com|hotmail\.com|[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})$/';
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill all required fields!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match($email_pattern, $email)) {
        $error = 'Please enter a valid email address (e.g., name@gmail.com, name@yahoo.com)!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long!';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } else {
        // Check if email already exists
        $check_query = "SELECT id FROM users WHERE email = '$email'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Email already registered! Please <a href="login.php">login here</a>';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $insert_query = "INSERT INTO users (name, email, phone, password, created_at) 
                            VALUES ('$name', '$email', '$phone', '$hashed_password', NOW())";
            
            if (mysqli_query($conn, $insert_query)) {
                $user_id = mysqli_insert_id($conn);
                
                // Auto login
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['is_admin'] = 0;
                
                // Merge guest cart with user cart
                if (isset($_SESSION['guest_cart']) && !empty($_SESSION['guest_cart'])) {
                    foreach ($_SESSION['guest_cart'] as $guest_item) {
                        $product_id = $guest_item['id'];
                        $quantity = $guest_item['quantity'];
                        
                        $check = mysqli_query($conn, "SELECT id, quantity FROM cart WHERE user_id = $user_id AND product_id = $product_id");
                        if (mysqli_num_rows($check) > 0) {
                            $existing = mysqli_fetch_assoc($check);
                            $new_qty = $existing['quantity'] + $quantity;
                            mysqli_query($conn, "UPDATE cart SET quantity = $new_qty WHERE user_id = $user_id AND product_id = $product_id");
                        } else {
                            mysqli_query($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES ($user_id, $product_id, $quantity)");
                        }
                    }
                    unset($_SESSION['guest_cart']);
                }
                
                // Merge guest wishlist
                if (isset($_SESSION['guest_wishlist']) && !empty($_SESSION['guest_wishlist'])) {
                    foreach ($_SESSION['guest_wishlist'] as $product_id) {
                        $check = mysqli_query($conn, "SELECT id FROM wishlist WHERE user_id = $user_id AND product_id = $product_id");
                        if (mysqli_num_rows($check) == 0) {
                            mysqli_query($conn, "INSERT INTO wishlist (user_id, product_id) VALUES ($user_id, $product_id)");
                        }
                    }
                    unset($_SESSION['guest_wishlist']);
                }
                
                $success = 'Account created successfully! Redirecting...';
                echo "<script>setTimeout(function(){ window.location.href = 'index.php'; }, 1500);</script>";
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Velvet Aura</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0F0A08; color: #F5E6D3; cursor: none; overflow-x: hidden; }
        
        .bg-animation { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2; background: linear-gradient(135deg, #0F0A08 0%, #1A0F08 50%, #0F0A08 100%); }
        .bg-animation::before { content: ''; position: absolute; width: 100%; height: 100%; background: repeating-linear-gradient(45deg, transparent, transparent 40px, rgba(212,165,116,0.03) 40px, rgba(212,165,116,0.03) 80px); pointer-events: none; }
        
        .orb { position: absolute; border-radius: 50%; filter: blur(60px); opacity: 0.4; animation: float 20s infinite ease-in-out; }
        .orb-1 { width: 300px; height: 300px; background: radial-gradient(circle, #5C2E1A, transparent); top: 10%; left: -100px; }
        .orb-2 { width: 400px; height: 400px; background: radial-gradient(circle, #D4A574, transparent); bottom: -150px; right: -150px; animation-delay: -5s; }
        .orb-3 { width: 250px; height: 250px; background: radial-gradient(circle, #3D2314, transparent); top: 50%; left: 40%; animation-delay: -10s; }
        .orb-4 { width: 200px; height: 200px; background: radial-gradient(circle, #8B6B4A, transparent); bottom: 20%; left: 20%; animation-delay: -15s; }
        
        @keyframes float { 0%,100% { transform: translate(0,0) scale(1); } 25% { transform: translate(50px,-50px) scale(1.1); } 50% { transform: translate(-30px,30px) scale(0.9); } 75% { transform: translate(30px,50px) scale(1.05); } }
        
        .cursor-dot { width: 8px; height: 8px; background: #D4A574; border-radius: 50%; position: fixed; pointer-events: none; z-index: 99999; }
        .cursor-outline { width: 40px; height: 40px; border: 2px solid #D4A574; border-radius: 50%; position: fixed; pointer-events: none; z-index: 99998; transition: all 0.15s ease; }
        
        .top-bar { background: #5C2E1A; padding: 8px 0; text-align: center; font-size: 11px; letter-spacing: 2px; color: #F5E6D3; text-transform: uppercase; font-weight: 500; position: relative; z-index: 100; }
        
        .navbar { background: rgba(61, 35, 20, 0.9); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(92,46,26,0.3); padding: 0; position: sticky; top: 0; z-index: 1000; }
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
        
        .dropdown { position: absolute; top: 100%; left: 0; background: rgba(44, 24, 16, 0.95); backdrop-filter: blur(10px); min-width: 240px; border-radius: 12px; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.3s ease; z-index: 100; border: 1px solid rgba(92,46,26,0.3); box-shadow: 0 15px 30px rgba(0,0,0,0.3); }
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
        .badge-count { position: absolute; top: -3px; right: -3px; background: #5C2E1A; color: #F5E6D3; font-size: 9px; font-weight: 700; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        
        .register-section { min-height: calc(100vh - 200px); display: flex; align-items: center; justify-content: center; padding: 60px 0; position: relative; z-index: 10; }
        .register-card { background: rgba(30, 18, 12, 0.7); backdrop-filter: blur(20px); border-radius: 48px; padding: 50px; max-width: 550px; width: 100%; margin: 0 auto; border: 1px solid rgba(212,165,116,0.3); box-shadow: 0 25px 50px rgba(0,0,0,0.3); transition: transform 0.3s ease; }
        .register-card:hover { transform: translateY(-5px); border-color: rgba(212,165,116,0.5); }
        .register-card h2 { font-size: 42px; font-weight: 800; margin-bottom: 10px; text-align: center; background: linear-gradient(135deg, #F5E6D3, #D4A574); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .register-card p { color: #D4B5A7; margin-bottom: 35px; text-align: center; font-size: 14px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { font-weight: 600; margin-bottom: 8px; display: block; color: #D4B5A7; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; }
        .form-group label i { margin-right: 8px; }
        .form-group input { width: 100%; padding: 14px 20px; border: 1px solid rgba(212,165,116,0.3); border-radius: 60px; font-size: 14px; outline: none; transition: all 0.3s; background: rgba(15, 10, 8, 0.6); color: #F5E6D3; font-family: 'Inter', sans-serif; }
        .form-group input:focus { border-color: #D4A574; box-shadow: 0 0 0 3px rgba(212,165,116,0.2); background: rgba(15, 10, 8, 0.8); }
        .form-group input::placeholder { color: #8B6B4A; }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        .password-strength { margin-top: 8px; height: 4px; border-radius: 4px; transition: all 0.3s ease; background: rgba(255,255,255,0.1); }
        .strength-weak { background: #ff6b6b; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #4CAF50; width: 100%; }
        .strength-text { font-size: 11px; margin-top: 5px; }
        .strength-text.weak { color: #ff6b6b; }
        .strength-text.medium { color: #ffc107; }
        .strength-text.strong { color: #4CAF50; }
        
        .checkbox-group { margin: 20px 0; }
        .checkbox-group label { display: flex; align-items: center; gap: 10px; font-size: 12px; cursor: pointer; color: #C4A484; text-transform: none; }
        .checkbox-group input { width: 18px; height: 18px; accent-color: #D4A574; margin: 0; }
        .checkbox-group a { color: #D4A574; text-decoration: none; }
        .checkbox-group a:hover { text-decoration: underline; }
        
        .btn-register { width: 100%; background: linear-gradient(135deg, #5C2E1A, #8B4513); color: #F5E6D3; padding: 15px; border: none; border-radius: 60px; font-weight: 700; font-size: 14px; cursor: pointer; transition: all 0.3s; letter-spacing: 2px; margin-top: 10px; position: relative; overflow: hidden; }
        .btn-register::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); transition: left 0.5s; }
        .btn-register:hover::before { left: 100%; }
        .btn-register:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(92,46,26,0.5); background: linear-gradient(135deg, #8B4513, #5C2E1A); }
        
        /* Google Signup Button */
        .google-btn { width: 100%; background: rgba(255,255,255,0.1); border: 1px solid rgba(212,165,116,0.3); color: #F5E6D3; padding: 14px; border-radius: 60px; font-weight: 500; font-size: 14px; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 12px; text-decoration: none; margin-top: 15px; }
        .google-btn:hover { background: rgba(255,255,255,0.2); transform: translateY(-2px); border-color: #D4A574; }
        .google-btn i { font-size: 18px; color: #D4A574; }
        
        .divider { display: flex; align-items: center; text-align: center; margin: 25px 0; }
        .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid rgba(212,165,116,0.2); }
        .divider span { padding: 0 15px; color: #8B6B4A; font-size: 12px; }
        
        .register-footer { text-align: center; margin-top: 25px; font-size: 13px; color: #C4A484; }
        .register-footer a { color: #D4A574; font-weight: 600; text-decoration: none; transition: color 0.3s; }
        .register-footer a:hover { color: #F5E6D3; text-decoration: underline; }
        
        .alert { padding: 14px 20px; border-radius: 60px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 13px; }
        .alert-success { background: rgba(76,175,80,0.15); color: #4CAF50; border: 1px solid rgba(76,175,80,0.3); backdrop-filter: blur(5px); }
        .alert-danger { background: rgba(255,68,68,0.15); color: #ff6b6b; border: 1px solid rgba(255,68,68,0.3); backdrop-filter: blur(5px); }
        
        /* Footer */
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
        
        @media (max-width: 992px) { 
            .desktop-nav { display: none; } .mobile-toggle { display: block; } .navbar .container { padding: 10px 20px; } .navbar-right { margin-left: auto; margin-right: 15px; }
            .register-card { padding: 30px; margin: 0 20px; }
            .footer-main { grid-template-columns: repeat(2, 1fr); }
            .footer-brand-section { grid-column: span 2; text-align: center; }
            .footer-logo { justify-content: center; }
            .footer-contact { align-items: center; }
            .footer-newsletter-section { grid-column: span 2; max-width: 100%; text-align: center; }
            .footer-bottom-content { flex-direction: column; text-align: center; }
            .payment-methods { justify-content: center; }
        }
        @media (max-width: 768px) { 
            .register-card h2 { font-size: 32px; }
            .register-section { padding: 40px 0; }
            .form-row { grid-template-columns: 1fr; gap: 0; }
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

<section class="register-section">
    <div class="container">
        <div class="register-card" data-aos="fade-up" data-aos-duration="800">
            <h2>Join Velvet Aura ✨</h2>
            <p>Create your account to start shopping</p>
            
            <?php if($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" id="registerForm">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name *</label>
                    <input type="text" name="name" id="name" required placeholder="John Doe" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address *</label>
                    <input type="email" name="email" id="email" required placeholder="name@gmail.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="tel" name="phone" id="phone" placeholder="+1 234 567 8900" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password *</label>
                    <input type="password" name="password" id="password" required placeholder="Create a password (min 6 characters)">
                    <div class="password-strength" id="passwordStrength"></div>
                    <div class="strength-text" id="strengthText"></div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Confirm Password *</label>
                    <input type="password" name="confirm_password" id="confirm_password" required placeholder="Confirm your password">
                    <div class="password-match" id="passwordMatch"></div>
                </div>
                
                <div class="checkbox-group">
                    <label><input type="checkbox" name="terms" id="terms" required> I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
                </div>
                
                <button type="submit" class="btn-register">Create Account →</button>
            </form>
            
            <div class="divider"><span>OR</span></div>
            
            <!-- Google Signup Button -->
            <a href="google-login.php" class="google-btn">
                <i class="fab fa-google"></i> Sign up with Google
            </a>
            
            <div class="register-footer">Already have an account? <a href="login.php">Login here</a></div>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <div class="footer-main">
            <div class="footer-brand-section"><div class="footer-logo"><span class="logo-icon">✦</span><span class="logo-text">VELVET<span>AURA</span></span></div><p class="footer-description">Ethical fashion for the conscious soul. Timeless pieces designed to last beyond seasons, crafted with love and intention.</p><div class="footer-contact"><div class="contact-item"><i class="fas fa-envelope"></i><a href="mailto:hello@velvetaura.com">hello@velvetaura.com</a></div><div class="contact-item"><i class="fas fa-phone-alt"></i><a href="tel:+15551234567">+1 (555) 123-4567</a></div><div class="contact-item"><i class="fas fa-map-marker-alt"></i><span>New York, NY 10001</span></div></div></div>
            <div class="footer-links-section"><h4 class="footer-title"><i class="fas fa-compass"></i> Quick Links</h4><ul><li><a href="shop.php"><i class="fas fa-chevron-right"></i> Shop All</a></li><li><a href="shop.php?filter=new"><i class="fas fa-chevron-right"></i> New Arrivals</a></li><li><a href="shop.php?filter=bestseller"><i class="fas fa-chevron-right"></i> Best Sellers</a></li><li><a href="lookbook.php"><i class="fas fa-chevron-right"></i> Lookbook</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> About Us</a></li></ul></div>
            <div class="footer-links-section"><h4 class="footer-title"><i class="fas fa-tags"></i> Categories</h4><ul><?php $footer_cats = array_slice($db_categories, 0, 6); foreach($footer_cats as $cat): ?><li><a href="shop.php?category=<?php echo htmlspecialchars($cat['slug']); ?>"><i class="fas fa-chevron-right"></i> <?php echo htmlspecialchars($cat['name']); ?></a></li><?php endforeach; ?></ul></div>
            <div class="footer-links-section"><h4 class="footer-title"><i class="fas fa-headset"></i> Support</h4><ul><li><a href="#"><i class="fas fa-chevron-right"></i> FAQ</a></li><li><a href="#"><i class="fas fa-chevron-right"></i> Shipping Info</a></li><li><a href="#"><i class="fas fa-chevron-right"></i> Returns</a></li><li><a href="#"><i class="fas fa-chevron-right"></i> Size Guide</a></li></ul></div>
            <div class="footer-newsletter-section"><h4 class="footer-title"><i class="fas fa-envelope-open-text"></i> Stay Connected</h4><p class="newsletter-text">Get 15% off your first order!</p><form class="footer-newsletter-form" id="footerNewsletterForm"><div class="input-group"><input type="email" placeholder="Your email address" required><button type="submit"><i class="fas fa-paper-plane"></i></button></div></form><div class="footer-social"><a href="#" class="social-icon"><i class="fab fa-instagram"></i></a><a href="#" class="social-icon"><i class="fab fa-pinterest"></i></a><a href="#" class="social-icon"><i class="fab fa-tiktok"></i></a><a href="#" class="social-icon"><i class="fab fa-youtube"></i></a></div></div>
        </div>
        <div class="footer-bottom"><div class="footer-bottom-content"><div class="copyright"><i class="far fa-copyright"></i> <?php echo date('Y'); ?> Velvet Aura — All rights reserved.</div><div class="payment-methods"><i class="fab fa-cc-visa"></i><i class="fab fa-cc-mastercard"></i><i class="fab fa-cc-amex"></i><i class="fab fa-cc-paypal"></i></div><div class="footer-badges"><span class="badge-eco"><i class="fas fa-leaf"></i> Eco-Friendly</span><span class="badge-eco"><i class="fas fa-recycle"></i> Sustainable</span></div></div></div>
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
        document.querySelectorAll('a, button, .icon-btn').forEach(el => {
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
        toast.innerHTML = `<i class="fas ${ok ? 'fa-check-circle' : 'fa-exclamation-circle'}" style="color:#5C2E1A;"></i> ${msg}`;
        wrap.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, 2500);
    }
    
    function checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 6) strength++;
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        if (strength <= 2) return { level: 'weak', text: 'Weak password', class: 'weak' };
        if (strength <= 4) return { level: 'medium', text: 'Medium password', class: 'medium' };
        return { level: 'strong', text: 'Strong password!', class: 'strong' };
    }
    
    function checkPasswordMatch() {
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;
        const matchDiv = document.getElementById('passwordMatch');
        if (confirm === '') { matchDiv.innerHTML = ''; return false; }
        if (password === confirm) { matchDiv.innerHTML = '<i class="fas fa-check-circle" style="color:#4CAF50;"></i> Passwords match!'; matchDiv.style.color = '#4CAF50'; return true; }
        else { matchDiv.innerHTML = '<i class="fas fa-exclamation-circle" style="color:#ff6b6b;"></i> Passwords do not match!'; matchDiv.style.color = '#ff6b6b'; return false; }
    }
    
    document.getElementById('password')?.addEventListener('input', function() {
        const password = this.value;
        const strength = checkPasswordStrength(password);
        const strengthDiv = document.getElementById('passwordStrength');
        const strengthText = document.getElementById('strengthText');
        strengthDiv.className = 'password-strength';
        if (password.length > 0) {
            if (strength.level === 'weak') strengthDiv.classList.add('strength-weak');
            else if (strength.level === 'medium') strengthDiv.classList.add('strength-medium');
            else strengthDiv.classList.add('strength-strong');
            strengthText.textContent = strength.text;
            strengthText.className = 'strength-text ' + strength.class;
        } else { strengthDiv.className = 'password-strength'; strengthText.textContent = ''; }
        checkPasswordMatch();
    });
    
    document.getElementById('confirm_password')?.addEventListener('input', checkPasswordMatch);
    
    document.getElementById('registerForm')?.addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;
        const terms = document.getElementById('terms').checked;
        if (password !== confirm) { e.preventDefault(); alert('Passwords do not match!'); return false; }
        if (password.length < 6) { e.preventDefault(); alert('Password must be at least 6 characters long!'); return false; }
        if (!terms) { e.preventDefault(); alert('Please agree to the Terms of Service and Privacy Policy!'); return false; }
        return true;
    });
    
    document.getElementById('footerNewsletterForm')?.addEventListener('submit', (e) => {
        e.preventDefault();
        showToast('Thanks for subscribing! Check your email for 15% off ✨');
        e.target.reset();
    });
</script>
</body>
</html>