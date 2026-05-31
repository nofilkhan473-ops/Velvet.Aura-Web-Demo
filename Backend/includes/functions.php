<?php
// =====================================================
// HELPER FUNCTIONS - COMPLETE
// =====================================================

// Sanitize input
function sanitize($input) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim($input))));
}

// Get cart count
function getCartCount() {
    global $conn;
    
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = $user_id";
        $result = mysqli_query($conn, $query);
        $data = mysqli_fetch_assoc($result);
        return $data['total'] ?? 0;
    } elseif (isset($_SESSION['guest_cart'])) {
        $total = 0;
        foreach ($_SESSION['guest_cart'] as $item) {
            $total += $item['quantity'];
        }
        return $total;
    }
    return 0;
}

// Get wishlist count
function getWishlistCount() {
    global $conn;
    
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $query = "SELECT COUNT(*) as total FROM wishlist WHERE user_id = $user_id";
        $result = mysqli_query($conn, $query);
        $data = mysqli_fetch_assoc($result);
        return $data['total'] ?? 0;
    }
    return 0;
}

// Get product by ID
function getProduct($id) {
    global $conn;
    $id = (int)$id;
    $query = "SELECT * FROM products WHERE id = $id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// Get all categories
function getAllCategories() {
    global $conn;
    $query = "SELECT * FROM categories WHERE is_active = 1";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Get new arrivals
function getNewArrivals($limit = 8) {
    global $conn;
    $query = "SELECT * FROM products WHERE is_new = 1 AND in_stock = 1 ORDER BY created_at DESC LIMIT $limit";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Get best sellers
function getBestSellers($limit = 8) {
    global $conn;
    $query = "SELECT * FROM products WHERE is_bestseller = 1 AND in_stock = 1 LIMIT $limit";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Generate order number
function generateOrderNumber() {
    return 'VA-' . date('Ymd') . '-' . strtoupper(uniqid());
}

// Format price
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

// Redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Set flash message
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Get flash message
function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Check if logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if admin
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == true;
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please login to continue');
        redirect('login.php');
        exit();
    }
}

// Get user cart items
function getUserCart() {
    global $conn;
    $cart_items = [];
    
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $query = "SELECT c.*, p.name, p.price, p.image, p.old_price, p.in_stock 
                  FROM cart c 
                  JOIN products p ON c.product_id = p.id 
                  WHERE c.user_id = $user_id";
        $result = mysqli_query($conn, $query);
        $cart_items = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        // Rename product_id to product_id for consistency
        foreach($cart_items as &$item) {
            $item['product_id'] = $item['product_id'];
        }
    } elseif (isset($_SESSION['guest_cart'])) {
        foreach ($_SESSION['guest_cart'] as $item) {
            $product = getProduct($item['id']);
            if ($product) {
                $cart_items[] = [
                    'product_id' => $product['id'],
                    'quantity' => $item['quantity'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'image' => $product['image']
                ];
            }
        }
    }
    
    return $cart_items;
}

// Get user orders
function getUserOrders() {
    global $conn;
    if (!isset($_SESSION['user_id'])) {
        return [];
    }
    
    $user_id = $_SESSION['user_id'];
    $query = "SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}
?>
