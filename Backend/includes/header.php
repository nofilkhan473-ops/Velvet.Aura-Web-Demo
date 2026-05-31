<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$cart_count = getCartCount();
$wishlist_count = getWishlistCount();
$flash = getFlashMessage();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; color: #111; background: #fff; }
        .top-bar { background: rgb(97, 63, 63); padding: 8px 0; font-size: 13px; color: white; text-align: center; }
        .navbar { padding: 16px 0; background: #1f1511; border-bottom: 1px solid rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000; }
        .navbar-brand { font-size: 26px; font-weight: 700; text-decoration: none; }
        .brand-text { color: white; font-weight: 800; }
        .brand-dot { color: white; font-size: 32px; display: inline-block; animation: pulse 2s infinite; }
        .brand-text-light { color: rgb(169,127,127); font-weight: 500; }
        @keyframes pulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.6; transform: scale(1.1); } }
        .nav-item.dropdown { position: relative; }
        .nav-link.dropdown-toggle { display: flex; align-items: center; gap: 8px; }
        .nav-link.dropdown-toggle::after { content: '\f078'; font-family: 'Font Awesome 6 Free'; font-weight: 600; border: none; margin-left: 6px; font-size: 10px; transition: transform 0.3s ease; }
        .nav-item.dropdown:hover .nav-link.dropdown-toggle::after { transform: rotate(180deg); }
        .dropdown-menu { display: block; opacity: 0; visibility: hidden; transform: translateY(20px); transition: all 0.3s ease; border: none; border-radius: 20px; background: rgb(156,113,113); backdrop-filter: blur(10px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); padding: 12px 0; min-width: 240px; margin-top: 15px; }
        .nav-item.dropdown:hover .dropdown-menu { opacity: 1; visibility: visible; transform: translateY(0); }
        .dropdown-item { padding: 10px 24px; font-size: 14px; font-weight: 500; color: #333; display: flex; align-items: center; gap: 12px; }
        .dropdown-item i { width: 20px; font-size: 14px; color: white; }
        .dropdown-item:hover { background: linear-gradient(90deg, #D4B5A7 0%, #e8d5cb 100%); color: white; padding-left: 32px; }
        .dropdown-item:hover i { color: white; }
        .dropdown-divider { margin: 8px 0; background: black; }
        .dropdown-header { padding: 8px 24px; font-size: 12px; font-weight: 700; color: rgb(49,11,11); text-transform: uppercase; }
        .nav-link { display: flex; align-items: center; gap: 8px; color: white !important; font-weight: 500; font-size: 14px; margin: 0 12px; padding: 8px 0; }
        .nav-link i { font-size: 16px; color: white; }
        .nav-link:hover { color: #D4B5A7 !important; }
        .nav-link:hover i { color: #D4B5A7; transform: translateY(-2px); }
        .nav-link.active { color: #D4B5A7 !important; }
        .search-wrapper { margin: 0 15px; }
        .search-form { position: relative; display: flex; align-items: center; }
        .search-form input { background: #f5f5f5; border: none; border-radius: 40px; padding: 10px 20px; padding-right: 40px; font-size: 13px; width: 220px; outline: none; }
        .search-form input:focus { background: white; box-shadow: 0 0 0 2px #D4B5A7; width: 260px; }
        .search-form button { position: absolute; right: 12px; background: none; border: none; color: #888; cursor: pointer; }
        .nav-icons { display: flex; align-items: center; gap: 20px; }
        .icon-link { position: relative; color: rgb(169,127,127); font-size: 18px; text-decoration: none; }
        .icon-link:hover { color: #D4B5A7; transform: translateY(-2px); }
        .icon-link .badge { position: absolute; top: -8px; right: -12px; background: #D4B5A7; color: white; font-size: 10px; border-radius: 50%; padding: 2px 6px; min-width: 18px; text-align: center; }
        .user-icon { background: #f5f5f5; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
        .user-icon:hover { background: #D4B5A7; color: black; transform: translateY(-2px); }
        .navbar-toggler { border: none; background: #f5f5f5; border-radius: 40px; padding: 8px 12px; }
        @media (max-width: 991px) { .navbar-collapse { background: white; padding: 20px; border-radius: 20px; margin-top: 15px; } .dropdown-menu { opacity: 1; visibility: visible; transform: none; position: static; box-shadow: none; background: transparent; padding-left: 20px; margin-top: 0; } .search-wrapper { margin: 15px 0; width: 100%; } .search-form input { width: 100%; } .nav-icons { justify-content: center; margin-top: 15px; } }
        .notification-toast { position: fixed; bottom: 30px; right: 30px; background: #111; color: white; padding: 12px 24px; border-radius: 50px; display: flex; gap: 10px; transform: translateX(400px); transition: 0.3s; z-index: 1000; }
        .notification-toast.show { transform: translateX(0); }
    </style>
</head>
<body>

<div class="top-bar">✨ Free shipping on orders over $100 | 30-day returns | 100% Sustainable ✨</div>

<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php"><span class="brand-text">VELVET</span><span class="brand-dot">.</span><span class="brand-text-light">AURA</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button">
                        <i class="fa-regular fa-house-chimney"></i><span>Home</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">✨ EXPLORE</li>
                        <li><a class="dropdown-item" href="index.php#newArrivals"><i class="fa-regular fa-sparkles"></i> New Arrivals</a></li>
                        <li><a class="dropdown-item" href="index.php#bestSellers"><i class="fa-regular fa-fire"></i> Best Sellers</a></li>
                        <li><a class="dropdown-item" href="shop.php"><i class="fa-regular fa-grid-2"></i> Shop by Category</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'shop.php' ? 'active' : ''; ?>" href="shop.php"><i class="fa-regular fa-store"></i><span>Shop</span></a></li>
                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'lookbook.php' ? 'active' : ''; ?>" href="lookbook.php"><i class="fa-regular fa-camera"></i><span>Lookbook</span></a></li>
                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'about.php' ? 'active' : ''; ?>" href="about.php"><i class="fa-regular fa-sparkles"></i><span>About</span></a></li>
                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'contact.php' ? 'active' : ''; ?>" href="contact.php"><i class="fa-regular fa-envelope"></i><span>Contact</span></a></li>
            </ul>
            <div class="search-wrapper">
                <form class="search-form" action="shop.php" method="GET">
                    <input type="text" name="search" id="searchInput" placeholder="Search aesthetic pieces...">
                    <button type="submit"><i class="fa-regular fa-magnifying-glass"></i></button>
                </form>
            </div>
            <div class="nav-icons">
                <a href="wishlist.php" class="icon-link">
                    <i class="fa-regular fa-heart"></i>
                    <span class="badge" id="wishlistCount"><?php echo $wishlist_count; ?></span>
                </a>
                <a href="cart.php" class="icon-link">
                    <i class="fa-regular fa-bag-shopping"></i>
                    <span class="badge" id="cartCount"><?php echo $cart_count; ?></span>
                </a>
                <?php if(isLoggedIn()): ?>
                <div class="dropdown">
                    <a href="#" class="icon-link user-icon dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fa-regular fa-user"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fa-regular fa-user"></i> My Account</a></li>
                        <li><a class="dropdown-item" href="my-orders.php"><i class="fa-regular fa-box"></i> My Orders</a></li>
                        <li><a class="dropdown-item" href="wishlist.php"><i class="fa-regular fa-heart"></i> Wishlist</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="backend/auth/logout.php"><i class="fa-regular fa-sign-out"></i> Logout</a></li>
                    </ul>
                </div>
                <?php else: ?>
                <a href="login.php" class="icon-link user-icon"><i class="fa-regular fa-user"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<?php if($flash): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        showNotification('<?php echo addslashes($flash['message']); ?>', '<?php echo $flash['type']; ?>');
    });
</script>
<?php endif; ?>

<input type="hidden" id="userLoggedIn" value="<?php echo isLoggedIn() ? 'true' : 'false'; ?>">