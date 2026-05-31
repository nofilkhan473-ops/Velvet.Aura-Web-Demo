<?php
// =====================================================
// DATABASE CONFIGURATION - VELVET AURA
// =====================================================

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'velvet_aura');

// Site Configuration
define('SITE_NAME', 'Velvet Aura');
define('SITE_URL', 'http://localhost/velvet-aura/');
define('SITE_EMAIL', 'hello@velvetaura.com');

// =====================================================
// CREATE DATABASE CONNECTION
// =====================================================
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8mb4");

// =====================================================
// START SESSION (if not already started)
// =====================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// SET TIMEZONE
// =====================================================
date_default_timezone_set('Asia/Karachi');

// =====================================================
// ERROR REPORTING (for development)
// =====================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =====================================================
// OPTIONAL: CREATE TABLES IF NOT EXISTS
// (Run this once to ensure all tables exist)
// =====================================================
function createTablesIfNotExist($conn) {
    
    // Users table
    $users_table = "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        city VARCHAR(100),
        state VARCHAR(100),
        zip VARCHAR(20),
        country VARCHAR(100) DEFAULT 'Pakistan',
        is_admin BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $users_table);
    
    // Categories table
    $categories_table = "CREATE TABLE IF NOT EXISTS categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) UNIQUE NOT NULL,
        image VARCHAR(255),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $categories_table);
    
    // Products table
    $products_table = "CREATE TABLE IF NOT EXISTS products (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(200) NOT NULL,
        slug VARCHAR(200) UNIQUE NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        old_price DECIMAL(10,2),
        category_id INT,
        image VARCHAR(255),
        rating DECIMAL(3,2) DEFAULT 4.5,
        in_stock BOOLEAN DEFAULT TRUE,
        stock_quantity INT DEFAULT 10,
        is_new BOOLEAN DEFAULT FALSE,
        is_bestseller BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    )";
    mysqli_query($conn, $products_table);
    
    // Cart table
    $cart_table = "CREATE TABLE IF NOT EXISTS cart (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        product_id INT NOT NULL,
        quantity INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $cart_table);
    
    // Wishlist table
    $wishlist_table = "CREATE TABLE IF NOT EXISTS wishlist (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY unique_wishlist (user_id, product_id)
    )";
    mysqli_query($conn, $wishlist_table);
    
    // Orders table
    $orders_table = "CREATE TABLE IF NOT EXISTS orders (
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
        order_status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    mysqli_query($conn, $orders_table);
    
    // Order Items table
    $order_items_table = "CREATE TABLE IF NOT EXISTS order_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        product_name VARCHAR(200) NOT NULL,
        product_price DECIMAL(10,2) NOT NULL,
        quantity INT NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $order_items_table);
    
    // Reviews table
    $reviews_table = "CREATE TABLE IF NOT EXISTS reviews (
        id INT PRIMARY KEY AUTO_INCREMENT,
        product_id INT NOT NULL,
        user_id INT NOT NULL,
        order_id INT NOT NULL,
        rating INT CHECK (rating >= 1 AND rating <= 5),
        title VARCHAR(200),
        comment TEXT,
        is_approved BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $reviews_table);
    
    // Contacts table
    $contacts_table = "CREATE TABLE IF NOT EXISTS contacts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        subject VARCHAR(200),
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $contacts_table);
    
    // Newsletter table
    $newsletter_table = "CREATE TABLE IF NOT EXISTS newsletter (
        id INT PRIMARY KEY AUTO_INCREMENT,
        email VARCHAR(100) UNIQUE NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $newsletter_table);
    
    // Insert default admin if not exists
    $check_admin = mysqli_query($conn, "SELECT id FROM users WHERE email = 'admin@velvetaura.com'");
    if(mysqli_num_rows($check_admin) == 0) {
        mysqli_query($conn, "INSERT INTO users (name, email, password, is_admin) VALUES 
            ('Administrator', 'admin@velvetaura.com', 'admin123', 1)");
    }
    
    // Insert default categories if not exists
    $check_cat = mysqli_query($conn, "SELECT id FROM categories LIMIT 1");
    if(mysqli_num_rows($check_cat) == 0) {
        mysqli_query($conn, "INSERT INTO categories (name, slug) VALUES 
            ('Dresses', 'dresses'), ('Tops', 'tops'), ('Bottoms', 'bottoms'),
            ('Outerwear', 'outerwear'), ('Hoodies', 'hoodies'), ('T-Shirts', 't-shirts'),
            ('Accessories', 'accessories'), ('Footwear', 'footwear')");
    }
}

// Run table creation (comment this after first run if you want)
createTablesIfNotExist($conn);

?>