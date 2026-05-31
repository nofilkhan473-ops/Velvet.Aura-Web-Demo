<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'backend/config/database.php';
require_once 'backend/includes/functions.php';

// Get categories for navbar
$categories_query = "SELECT * FROM categories WHERE is_active = 1";
$categories_result = mysqli_query($conn, $categories_query);
$db_categories = mysqli_fetch_all($categories_result, MYSQLI_ASSOC);

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    
    $stmt = $conn->prepare("INSERT INTO contacts (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
    
    if ($stmt->execute()) {
        $success_message = 'Thank you for contacting us. We will get back to you soon!';
    } else {
        $error_message = 'Something went wrong. Please try again.';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Velvet Aura</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #fef9f1 0%, #faf5ea 100%);
            color: #1a1a2e;
        }
        
        /* Top Bar */
        .top-bar { 
            background: linear-gradient(135deg, #8B5A2B, #6B3E1A);
            padding: 10px 0; 
            font-size: 13px; 
            color: white; 
            text-align: center; 
            letter-spacing: 0.5px;
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-100%); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* ========== MODERN NAVBAR ========== */
        .navbar {
            padding: 15px 0;
            background: #1f1511;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar-brand {
            font-size: 26px;
            font-weight: 800;
            text-decoration: none;
            color: white;
            letter-spacing: 1px;
        }
        
        .navbar-brand span {
            color: #D4B5A7;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.75) !important;
            font-weight: 500;
            font-size: 14px;
            margin: 0 12px;
            padding: 8px 0;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: #D4B5A7;
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100%;
        }
        
        .nav-link:hover {
            color: #D4B5A7 !important;
        }
        
        .nav-link i {
            font-size: 14px;
            margin-right: 6px;
        }
        
        .nav-link.dropdown-toggle::after { 
            content: '\f078'; 
            font-family: 'Font Awesome 6 Free'; 
            font-weight: 600; 
            border: none; 
            margin-left: 8px; 
            font-size: 10px;
        }
        
        .dropdown-menu {
            border: none;
            border-radius: 16px;
            background: #2a1f1a;
            padding: 10px 0;
            min-width: 220px;
            margin-top: 10px;
            display: block;
            opacity: 0;
            visibility: hidden;
            transform: translateY(15px);
            transition: all 0.3s ease;
        }
        
        .nav-item.dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-item {
            padding: 10px 20px;
            font-size: 13px;
            color: rgba(255,255,255,0.8);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .dropdown-item i {
            width: 18px;
            font-size: 13px;
            color: #D4B5A7;
        }
        
        .dropdown-item:hover {
            background: #D4B5A7;
            color: #1f1511;
            padding-left: 28px;
        }
        
        .dropdown-item:hover i {
            color: #1f1511;
        }
        
        .dropdown-divider {
            margin: 8px 0;
            background: rgba(255,255,255,0.1);
        }
        
        .dropdown-header {
            padding: 8px 20px;
            font-size: 10px;
            font-weight: 700;
            color: #D4B5A7;
            text-transform: uppercase;
        }
        
        /* Search Bar */
        .search-wrapper { margin: 0 15px; }
        
        .search-form { 
            position: relative; 
            display: flex; 
            align-items: center; 
        }
        
        .search-form input { 
            background: rgba(255,255,255,0.08); 
            border: 1px solid rgba(255,255,255,0.15); 
            border-radius: 40px; 
            padding: 10px 18px; 
            padding-right: 38px; 
            font-size: 13px; 
            width: 210px; 
            outline: none; 
            color: white;
        }
        
        .search-form input::placeholder { color: rgba(255,255,255,0.4); }
        
        .search-form input:focus { 
            background: rgba(255,255,255,0.12); 
            border-color: #D4B5A7; 
            width: 240px; 
        }
        
        .search-form button { 
            position: absolute; 
            right: 12px; 
            background: none; 
            border: none; 
            color: rgba(255,255,255,0.5); 
            cursor: pointer; 
        }
        
        /* Nav Icons */
        .nav-icons { display: flex; align-items: center; gap: 20px; }
        
        .icon-link { 
            position: relative; 
            color: rgba(255,255,255,0.7); 
            font-size: 18px; 
            text-decoration: none; 
            transition: all 0.3s ease;
        }
        
        .icon-link:hover { color: #D4B5A7; transform: translateY(-2px); }
        
        .icon-link .badge { 
            position: absolute; 
            top: -8px; 
            right: -12px; 
            background: #D4B5A7;
            color: #1f1511; 
            font-size: 10px; 
            font-weight: 700;
            border-radius: 50%; 
            padding: 2px 6px; 
        }
        
        .user-icon { 
            width: 36px; 
            height: 36px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            border-radius: 50%; 
            background: rgba(255,255,255,0.08);
            transition: all 0.3s ease;
        }
        
        .user-icon:hover { background: #D4B5A7; color: #1f1511; transform: translateY(-2px); }
        
        .navbar-toggler { 
            border: 1px solid rgba(255,255,255,0.2); 
            background: transparent; 
        }
        
        .navbar-toggler-icon { filter: invert(1); }
        
        @media (max-width: 991px) {
            .navbar-collapse { 
                background: #1f1511;
                padding: 20px; 
                border-radius: 16px; 
                margin-top: 15px; 
            }
            .dropdown-menu { 
                background: rgba(255,255,255,0.05);
                padding-left: 20px; 
                opacity: 1;
                visibility: visible;
                transform: none;
            }
            .search-wrapper { margin: 15px 0; width: 100%; }
            .search-form input { width: 100%; }
            .search-form input:focus { width: 100%; }
            .nav-icons { justify-content: center; margin-top: 15px; }
        }
        
        /* ========== PAGE HEADER ========== */
        .page-header {
            background: linear-gradient(135deg, #D4B5A7 0%, #E8D5CB 50%, #F5EAE0 100%);
            padding: 100px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .page-header h1 {
            font-size: 56px;
            font-weight: 800;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #1f1511, #5a3a2a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: fadeInUp 0.8s ease;
        }
        
        .page-header p {
            color: #5a3a2a;
            font-size: 18px;
            font-weight: 500;
            animation: fadeInUp 1s ease;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* ========== CONTACT SECTION ========== */
        .contact-section {
            padding: 80px 0;
        }
        
        /* Contact Info Cards */
        .contact-info-card {
            background: white;
            border-radius: 24px;
            padding: 35px 25px;
            margin-bottom: 25px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 25px rgba(0,0,0,0.05);
        }
        
        .contact-info-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .contact-info-card i {
            font-size: 48px;
            color: #D4B5A7;
            margin-bottom: 20px;
            display: inline-block;
            transition: transform 0.3s ease;
        }
        
        .contact-info-card:hover i {
            transform: scale(1.1);
        }
        
        .contact-info-card h4 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
            color: #1f1511;
        }
        
        .contact-info-card p {
            color: #666;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        /* Contact Form */
        .contact-form {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .contact-form:hover {
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }
        
        .contact-form h3 {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .contact-form h3 i {
            color: #D4B5A7;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            font-size: 14px;
        }
        
        .form-group label i {
            color: #D4B5A7;
            width: 20px;
            margin-right: 6px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #eee;
            border-radius: 16px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #D4B5A7;
            outline: none;
            box-shadow: 0 0 0 3px rgba(212,181,167,0.2);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #1f1511, #3a251a);
            color: white;
            padding: 14px 35px;
            border-radius: 50px;
            border: none;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            font-size: 16px;
        }
        
        .btn-submit:hover {
            background: #D4B5A7;
            color: #1f1511;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(212,181,167,0.3);
        }
        
        /* Alert Messages */
        .alert {
            padding: 14px 18px;
            border-radius: 16px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInRight 0.5s ease;
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert i {
            font-size: 20px;
        }
        
        /* Map Section */
        .map-section {
            padding: 0 0 80px 0;
        }
        
        .map-container {
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .map-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .map-container iframe {
            width: 100%;
            height: 350px;
            border: none;
        }
        
        /* Footer */
        .footer { background: #faf9f8; padding: 60px 0 30px; margin-top: 60px; }
        .footer h4, .footer h5 { font-weight: 700; margin-bottom: 20px; }
        .footer ul { list-style: none; padding: 0; }
        .footer ul li { margin-bottom: 10px; }
        .footer ul li a { color: #666; text-decoration: none; font-size: 14px; transition: 0.3s; }
        .footer ul li a:hover { color: #D4B5A7; }
        .social-icons a { display: inline-block; width: 38px; height: 38px; background: white; border-radius: 50%; text-align: center; line-height: 38px; color: #111; margin-right: 10px; transition: 0.3s; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .social-icons a:hover { background: #D4B5A7; color: white; transform: translateY(-3px); }
        .footer-bottom { margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; font-size: 13px; color: #666; }
        .contact-info { list-style: none; padding: 0; margin: 0; }
        .contact-info li { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; font-size: 14px; color: #666; }
        .contact-info li i { width: 20px; color: #D4B5A7; }
        
        .notification-toast { 
            position: fixed; 
            bottom: 30px; 
            right: 30px; 
            background: #1f1511; 
            color: white; 
            padding: 14px 28px; 
            border-radius: 60px; 
            display: flex; 
            gap: 12px; 
            opacity: 0;
            transform: translateX(400px);
            transition: all 0.3s ease;
            z-index: 1000; 
            font-weight: 500; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.15); 
        }
        .notification-toast.show { opacity: 1; transform: translateX(0); }
        
        @media (max-width: 991px) {
            .page-header h1 { font-size: 40px; }
            .contact-form { margin-top: 30px; }
        }
        
        @media (max-width: 768px) {
            .page-header h1 { font-size: 32px; }
            .contact-info-card { padding: 25px; }
            .contact-form { padding: 25px; }
        }
    </style>
</head>
<body>

<div class="top-bar">✨ Free shipping on orders over $100 | 30-day returns | 100% Sustainable ✨</div>

<!-- ========== MODERN NAVBAR ========== -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">VELVET<span>.</span>AURA</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#">
                        <i class="fas fa-home"></i> Home
                    </a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">EXPLORE</li>
                        <li><a class="dropdown-item" href="index.php#newArrivals"><i class="fas fa-sparkles"></i> New Arrivals</a></li>
                        <li><a class="dropdown-item" href="index.php#bestSellers"><i class="fas fa-fire"></i> Best Sellers</a></li>
                        <li><a class="dropdown-item" href="index.php#categories"><i class="fas fa-th-large"></i> Shop by Category</a></li>
                        <li><a class="dropdown-item" href="index.php#featured"><i class="fas fa-gem"></i> Featured Collection</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="lookbook.php"><i class="fas fa-camera"></i> Lookbook</a></li>
                        <li><a class="dropdown-item" href="about.php"><i class="fas fa-sparkles"></i> About Us</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#">
                        <i class="fas fa-store"></i> Shop
                    </a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">ALL PRODUCTS</li>
                        <li><a class="dropdown-item" href="shop.php"><i class="fas fa-bag-shopping"></i> All Products</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li class="dropdown-header">CATEGORIES</li>
                        <?php foreach($db_categories as $cat): ?>
                        <li><a class="dropdown-item" href="shop.php?category=<?php echo $cat['slug']; ?>"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($cat['name']); ?></a></li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li class="dropdown-header">TRENDING</li>
                        <li><a class="dropdown-item" href="shop.php?filter=new"><i class="fas fa-sparkles"></i> New Arrivals</a></li>
                        <li><a class="dropdown-item" href="shop.php?filter=bestseller"><i class="fas fa-crown"></i> Best Sellers</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="lookbook.php"><i class="fas fa-camera"></i> Lookbook</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php"><i class="fas fa-sparkles"></i> About</a></li>
                <li class="nav-item"><a class="nav-link active" href="contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
                <li class="nav-item"><a class="nav-link" href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                <li class="nav-item"><a class="nav-link" href="my-orders.php"><i class="fas fa-box"></i> My Orders</a></li>
                <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="login.php"><i class="fas fa-user"></i> Login</a></li>
                <?php endif; ?>
            </ul>
            <div class="search-wrapper">
                <form class="search-form" id="searchForm" action="shop.php" method="GET">
                    <input type="text" name="search" id="searchInput" placeholder="Search...">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="nav-icons">
                <a href="wishlist.php" class="icon-link"><i class="fas fa-heart"></i><span class="badge" id="wishlistCount">0</span></a>
                <a href="cart.php" class="icon-link"><i class="fas fa-bag-shopping"></i><span class="badge" id="cartCount">0</span></a>
                <a href="login.php" class="icon-link user-icon"><i class="fas fa-user"></i></a>
            </div>
        </div>
    </div>
</nav>

<!-- ========== PAGE HEADER ========== -->
<section class="page-header">
    <div class="container">
        <h1>Contact Us</h1>
        <p>We'd love to hear from you</p>
    </div>
</section>

<!-- ========== CONTACT SECTION ========== -->
<section class="contact-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-4">
                <div class="contact-info-card">
                    <i class="fas fa-envelope"></i>
                    <h4>Email Us</h4>
                    <p>hello@velvetaura.com</p>
                    <p>support@velvetaura.com</p>
                </div>
                <div class="contact-info-card">
                    <i class="fas fa-phone-alt"></i>
                    <h4>Call Us</h4>
                    <p>+1 (555) 123-4567</p>
                    <p>+1 (555) 987-6543</p>
                    <p style="font-size: 12px; color: #aaa;">Mon-Fri: 9AM - 6PM EST</p>
                </div>
                <div class="contact-info-card">
                    <i class="fas fa-map-marker-alt"></i>
                    <h4>Visit Us</h4>
                    <p>123 Fashion Street</p>
                    <p>New York, NY 10001</p>
                    <p>United States</p>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="contact-form">
                    <h3><i class="fas fa-paper-plane"></i> Send us a Message</h3>
                    
                    <?php if($success_message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($error_message): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="contactForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-user"></i> Your Name *</label>
                                    <input type="text" name="name" required placeholder="John Doe">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-envelope"></i> Email Address *</label>
                                    <input type="email" name="email" required placeholder="john@example.com">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-phone"></i> Phone Number</label>
                                    <input type="tel" name="phone" placeholder="+1 234 567 8900">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-tag"></i> Subject *</label>
                                    <input type="text" name="subject" required placeholder="Order Inquiry">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-comment"></i> Message *</label>
                            <textarea name="message" rows="5" required placeholder="Tell us what you think..."></textarea>
                        </div>
                        <button type="submit" class="btn-submit">Send Message <i class="fas fa-arrow-right"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== MAP SECTION ========== -->
<section class="map-section">
    <div class="container">
        <div class="map-container">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.215!2d-74.006!3d40.7128!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25a316bed6bdb%3A0x6cdfe3c5e8e5b8e!2sFashion%20District%2C%20New%20York%2C%20NY!5e0!3m2!1sen!2sus!4v1700000000000!5m2!1sen!2sus" 
                allowfullscreen="" 
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
</section>

<!-- ========== FOOTER ========== -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
                <h4>VELVET AURA</h4>
                <p>Ethical fashion for the conscious soul. Timeless pieces designed to last.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-pinterest"></i></a>
                    <a href="#"><i class="fab fa-tiktok"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6 mb-4">
                <h5>Shop</h5>
                <ul>
                    <li><a href="shop.php">All Products</a></li>
                    <li><a href="shop.php?filter=new">New Arrivals</a></li>
                    <li><a href="shop.php?filter=bestseller">Best Sellers</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-6 mb-4">
                <h5>Explore</h5>
                <ul>
                    <li><a href="lookbook.php">Lookbook</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-6 mb-4">
                <h5>Account</h5>
                <ul>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="my-orders.php">My Orders</a></li>
                    <li><a href="wishlist.php">Wishlist</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <h5>Contact Us</h5>
                <ul class="contact-info">
                    <li><i class="fas fa-envelope"></i> <a href="mailto:hello@velvetaura.com">hello@velvetaura.com</a></li>
                    <li><i class="fas fa-phone"></i> <a href="tel:+15551234567">+1 (555) 123-4567</a></li>
                    <li><i class="fas fa-location-dot"></i> 123 Fashion Street, New York, NY 10001</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Velvet Aura. All rights reserved. | <a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a></p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
    
    function updateCounts() { 
        const cartCount = cart.reduce((s, i) => s + i.quantity, 0);
        document.querySelectorAll('#cartCount').forEach(b => b.textContent = cartCount);
        document.querySelectorAll('#wishlistCount').forEach(b => b.textContent = wishlist.length);
    }
    
    function showNotification(msg, type = 'success') {
        let n = document.createElement('div');
        n.className = 'notification-toast';
        const icon = type === 'success' ? '✅' : '❌';
        n.innerHTML = `<span>${icon}</span><span>${msg}</span>`;
        document.body.appendChild(n);
        setTimeout(() => n.classList.add('show'), 50);
        setTimeout(() => { 
            n.classList.remove('show'); 
            setTimeout(() => n.remove(), 300); 
        }, 2500);
    }
    
    document.getElementById('searchForm')?.addEventListener('submit', (e) => {
        e.preventDefault();
        const query = document.getElementById('searchInput').value.trim();
        if (query) { 
            localStorage.setItem('searchQuery', query); 
            window.location.href = 'shop.php?search=' + encodeURIComponent(query); 
        }
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
    
    document.addEventListener('DOMContentLoaded', updateCounts);
</script>
</body>
</html>