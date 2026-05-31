-- =====================================================
-- VELVET AURA DATABASE - COMPLETE
-- =====================================================

-- Drop database if exists (optional - be careful!)
-- DROP DATABASE IF EXISTS velvet_aura;

-- Create Database
CREATE DATABASE IF NOT EXISTS velvet_aura;
USE velvet_aura;

-- =====================================================
-- TABLE: users (User accounts)
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLE: categories (Product categories)
-- =====================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLE: products (All products)
-- =====================================================
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    old_price DECIMAL(10,2),
    category_id INT,
    image VARCHAR(255),
    images TEXT,
    rating DECIMAL(3,2) DEFAULT 4.5,
    rating_count INT DEFAULT 0,
    in_stock BOOLEAN DEFAULT TRUE,
    stock_quantity INT DEFAULT 10,
    is_new BOOLEAN DEFAULT FALSE,
    is_bestseller BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- =====================================================
-- TABLE: cart (Shopping cart items)
-- =====================================================
CREATE TABLE IF NOT EXISTS cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    session_id VARCHAR(255),
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id)
);

-- =====================================================
-- TABLE: wishlist (User wishlist)
-- =====================================================
CREATE TABLE IF NOT EXISTS wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id)
);

-- =====================================================
-- TABLE: orders (Customer orders)
-- =====================================================
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT,
    guest_email VARCHAR(100),
    guest_name VARCHAR(200),
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
    tax DECIMAL(10,2) DEFAULT 0,
    discount DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    order_status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order_number (order_number),
    INDEX idx_user_id (user_id),
    INDEX idx_email (email)
);

-- =====================================================
-- TABLE: order_items (Items in each order)
-- =====================================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    product_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id)
);

-- =====================================================
-- TABLE: reviews (Product reviews)
-- =====================================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    order_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (user_id, product_id, order_id)
);

-- =====================================================
-- TABLE: contacts (Contact form messages)
-- =====================================================
CREATE TABLE IF NOT EXISTS contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(200),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLE: newsletter (Newsletter subscribers)
-- =====================================================
CREATE TABLE IF NOT EXISTS newsletter (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- =====================================================
-- INSERT DATA
-- =====================================================
-- =====================================================

-- =====================================================
-- Insert Categories
-- =====================================================
INSERT INTO categories (name, slug, image, is_active) VALUES
('Dresses', 'dresses', 'dress-category.jpg', 1),
('Tops', 'tops', 'top-category.jpg', 1),
('Bottoms', 'bottoms', 'bottom-category.jpg', 1),
('Outerwear', 'outerwear', 'outerwear-category.jpg', 1),
('Hoodies', 'hoodies', 'hoodie-category.jpg', 1),
('T-Shirts', 't-shirts', 'tshirt-category.jpg', 1),
('Accessories', 'accessories', 'accessory-category.jpg', 1),
('Footwear', 'footwear', 'footwear-category.jpg', 1);

-- =====================================================
-- Insert Admin User
-- Password: admin123 (hashed)
-- =====================================================
INSERT INTO users (name, email, password, is_admin) VALUES
('Admin', 'admin@velvetaura.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- =====================================================
-- Insert Sample User (for testing)
-- Password: user123
-- =====================================================
INSERT INTO users (name, email, password, phone, address, city, state, zip, country, is_admin) VALUES
('John Doe', 'user@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+92 300 1234567', '123 Main Street', 'Karachi', 'Sindh', '75500', 'Pakistan', 0);

-- =====================================================
-- Insert Products - DRESSES (Category ID: 1)
-- =====================================================
INSERT INTO products (name, slug, description, price, old_price, category_id, image, rating, in_stock, stock_quantity, is_new, is_bestseller) VALUES
('Salwar Kameez', 'salwar-kameez', 'Beautiful traditional Salwar Kameez perfect for festive occasions. Made with premium quality fabric.', 89.00, NULL, 1, 'Salwar Kameez.jpg', 4.8, 1, 15, 1, 1),
('Embroidered Kurta', 'embroidered-kurta', 'Intricately embroidered kurta with premium fabric. Perfect for weddings and special events.', 129.00, 159.00, 1, 'Embroidered Kurta.jpg', 4.9, 1, 10, 1, 0),
('Summer Maxi Dress', 'summer-maxi-dress', 'Light and breezy maxi dress for sunny days. Perfect for beach and casual outings.', 79.00, NULL, 1, 'summer maxi dress.jpg', 4.7, 1, 20, 0, 1),
('Floral Dress', 'floral-dress', 'Pretty floral print dress for everyday elegance. Comfortable and stylish.', 69.00, 89.00, 1, 'floral Dress.jpg', 4.5, 1, 12, 0, 0),
('Long Shirt', 'long-shirt', 'Versatile long shirt that pairs with everything. Perfect for casual wear.', 59.00, NULL, 1, 'long shirt.jpg', 4.6, 1, 18, 1, 0),
('Kaftan', 'kaftan', 'Luxurious kaftan for a relaxed yet chic look. Perfect for lounging.', 149.00, 199.00, 1, 'Kaftan.jpg', 4.9, 0, 0, 0, 1),
('Long Frok', 'long-frok', 'Elegant long frok for special occasions.', 84.00, NULL, 1, 'long frok.jpg', 4.4, 1, 8, 0, 0),
('Silk Suit', 'silk-suit', 'Premium silk suit for formal events.', 94.00, 114.00, 1, 'Silk Suit.jpg', 4.7, 1, 6, 1, 0),
('Embroidered Salwar Kameez', 'embroidered-salwar-kameez', 'Heavily embroidered salwar kameez for weddings.', 199.00, NULL, 1, 'Embroidered Salwar Kameez.jpg', 4.6, 1, 5, 0, 0),
('Silk Angrakha', 'silk-angrakha', 'Beautiful silk angrakha style suit.', 79.00, NULL, 1, 'silk angrakha.jpg', 4.8, 1, 10, 0, 1),
('Pastel Kurtas', 'pastel-kurtas', 'Soft pastel colored kurtas for everyday wear.', 89.00, 109.00, 1, 'pastel kurtas suit.jpg', 4.5, 1, 14, 0, 0),
('Farsi Salwar Suit', 'farsi-salwar-suit', 'Traditional Farsi style salwar suit.', 119.00, NULL, 1, 'Farsi Salwar Suit.jpg', 4.9, 1, 7, 1, 0);

-- =====================================================
-- Insert Products - TOPS (Category ID: 2)
-- =====================================================
INSERT INTO products (name, slug, description, price, old_price, category_id, image, rating, in_stock, stock_quantity, is_new, is_bestseller) VALUES
('White Top', 'white-top', 'Crisp white top, a wardrobe essential. Perfect for any occasion.', 45.00, NULL, 2, 'White Top.jpg', 4.8, 1, 25, 1, 1),
('Puff Sleeve Plaid Top', 'puff-sleeve-plaid-top', 'Trendy puff sleeve plaid top with vintage vibes.', 99.00, NULL, 2, 'Puff Sleeve Plaid top.jpg', 4.8, 1, 12, 1, 0),
('Trendy Top', 'trendy-top', 'Stylish top for a modern look.', 65.00, 85.00, 2, 'Trendy Top.jpg', 4.7, 1, 18, 0, 1),
('Brown Top', 'brown-top', 'Elegant brown top for casual and formal wear.', 35.00, NULL, 2, 'Brown Top.jpg', 4.6, 0, 0, 1, 0),
('Shirtlet', 'shirtlet', 'Stylish shirtlet for a chic look.', 85.00, NULL, 2, 'Shirtlet.jpg', 4.7, 1, 9, 0, 0),
('Blue Top', 'blue-top', 'Beautiful blue top for everyday elegance.', 69.00, NULL, 2, 'Blue Top.jpg', 4.6, 1, 14, 0, 1),
('Bow Top', 'bow-top', 'Cute bow top with elegant design.', 129.00, 169.00, 2, 'Bow Top.jpg', 4.9, 1, 6, 0, 1),
('Vintage Top', 'vintage-top', 'Retro vintage style top.', 55.00, NULL, 2, 'vintage top.jpg', 4.5, 1, 11, 1, 0),
('Pink Top', 'pink-top', 'Pretty pink top for a feminine look.', 79.00, NULL, 2, 'pink top.jpg', 4.7, 1, 13, 0, 0);

-- =====================================================
-- Insert Products - BOTTOMS (Category ID: 3)
-- =====================================================
INSERT INTO products (name, slug, description, price, old_price, category_id, image, rating, in_stock, stock_quantity, is_new, is_bestseller) VALUES
('Linen Trousers', 'linen-trousers', 'Breathable linen trousers for summer. Lightweight and comfortable.', 79.00, NULL, 3, 'Linen Trousers.jpg', 4.7, 1, 16, 0, 1),
('Wide Leg Pants', 'wide-leg-pants', 'Comfortable wide leg pants for everyday wear.', 69.00, 89.00, 3, 'Wide Leg Pants.jpg', 4.6, 1, 12, 0, 0),
('Cargo Pants', 'cargo-pants', 'Stylish cargo pants with multiple pockets.', 75.00, 95.00, 3, 'Cargo Pants.jpg', 4.7, 1, 10, 0, 1),
('Sweatpants', 'sweatpants', 'Cozy sweatpants for casual days.', 55.00, NULL, 3, 'Sweatpants.jpg', 4.5, 1, 20, 0, 0),
('Black Baggy Jeans', 'black-baggy-jeans', 'Trendy black baggy jeans for a street style look.', 89.00, NULL, 3, 'black baggy jeans.jpg', 4.7, 1, 14, 0, 1),
('Casual Straight Leg Pants', 'casual-straight-leg-pants', 'Classic straight leg pants for everyday wear.', 59.00, 79.00, 3, 'Casual Straight Leg Pants.jpg', 4.6, 1, 18, 1, 0),
('Pleated Skirt', 'pleated-skirt', 'Elegant pleated skirt for a sophisticated look.', 65.00, NULL, 3, 'Pleated Skirt.jpg', 4.5, 1, 11, 1, 0),
('Formal Pants', 'formal-pants', 'Professional formal pants for office wear.', 129.00, 159.00, 3, 'Formal pants.jpg', 4.8, 0, 0, 0, 1),
('Split Denim Skirt', 'split-denim-skirt', 'Trendy split denim skirt.', 49.00, NULL, 3, 'Split Denim Skir.jpg', 4.4, 1, 15, 1, 0),
('Straight Bow Pant', 'straight-bow-pant', 'Elegant straight pants with bow detail.', 69.00, NULL, 3, 'stright Bow pant.jpg', 4.6, 1, 9, 0, 0),
('Vintage Corduroy Long Pant', 'vintage-corduroy-long-pant', 'Retro corduroy pants for a vintage look.', 85.00, NULL, 3, 'Vintage Corduroy Long pants.jpg', 4.5, 1, 8, 0, 0),
('Emo Pant', 'emo-pant', 'Edgy emo style pants.', 79.00, NULL, 3, 'emo pants.jpg', 4.7, 1, 7, 1, 0);

-- =====================================================
-- Insert Products - OUTERWEAR (Category ID: 4)
-- =====================================================
INSERT INTO products (name, slug, description, price, old_price, category_id, image, rating, in_stock, stock_quantity, is_new, is_bestseller) VALUES
('Long Coat', 'long-coat', 'Elegant long coat for cold weather. Perfect for winter.', 199.00, 249.00, 4, 'long coat.jpg', 4.9, 1, 8, 0, 1),
('Leather Jacket', 'leather-jacket', 'Classic leather jacket for edgy style.', 159.00, 199.00, 4, 'Leather Jacket.jpg', 4.8, 1, 10, 0, 1),
('Denim Jacket', 'denim-jacket', 'Classic denim jacket for everyday wear.', 89.00, 119.00, 4, 'Denim Jacket.jpg', 4.7, 1, 15, 0, 1),
('Puffer Jacket', 'puffer-jacket', 'Warm puffer jacket for extreme cold.', 149.00, NULL, 4, 'Puffer Jacket.jpg', 4.7, 1, 7, 1, 0),
('Trench Coat', 'trench-coat', 'Classic trench coat for a sophisticated look.', 179.00, 229.00, 4, 'Trench Coat.jpg', 4.9, 1, 6, 0, 1),
('Varsity Jacket', 'varsity-jacket', 'Stylish varsity jacket for a sporty look.', 99.00, NULL, 4, 'varsity jacket.jpg', 4.6, 1, 12, 1, 0),
('Cardigan', 'cardigan', 'Cozy cardigan for layering.', 129.00, NULL, 4, 'Cardigan .jpg', 4.7, 1, 9, 0, 0),
('Jacket', 'jacket', 'Versatile jacket for all seasons.', 89.00, NULL, 4, 'jacket.jpg', 4.5, 1, 14, 1, 0),
('Cropped Coat', 'cropped-coat', 'Trendy cropped coat for a modern look.', 189.00, 249.00, 4, 'crooped coat.jpg', 4.8, 1, 5, 0, 1),
('Zip Up Cardigan', 'zip-up-cardigan', 'Convenient zip up cardigan.', 209.00, NULL, 4, 'zip-up.jpg', 4.6, 1, 4, 1, 0);

-- =====================================================
-- Insert Products - HOODIES (Category ID: 5)
-- =====================================================
INSERT INTO products (name, slug, description, price, old_price, category_id, image, rating, in_stock, stock_quantity, is_new, is_bestseller) VALUES
('Oversized Hoodie', 'oversized-hoodie', 'Cozy oversized hoodie for casual days.', 65.00, NULL, 5, 'oversize.jpg', 4.8, 1, 20, 1, 1),
('Graphic Hoodie', 'graphic-hoodie', 'Trendy graphic hoodie with unique design.', 75.00, NULL, 5, 'graphic.jpg', 4.7, 1, 18, 1, 0),
('Cozy Hoodie', 'cozy-hoodie', 'Soft and warm hoodie for cold days.', 69.00, 89.00, 5, 'cozy hood.jpg', 4.7, 0, 0, 0, 1),
('Oversized Blazer', 'oversized-blazer', 'Trendy oversized blazer for a chic look.', 119.00, 149.00, 5, 'oversize blazer.jpg', 4.8, 1, 8, 1, 0),
('Zip Hood', 'zip-hood', 'Convenient zip up hoodie.', 39.00, NULL, 5, 'ziper hood.jpg', 4.6, 1, 25, 0, 1),
('Cropped Hoodie', 'cropped-hoodie', 'Stylish cropped hoodie.', 59.00, NULL, 5, 'cropped hood.jpg', 4.5, 1, 15, 1, 0),
('Emo Hoodie', 'emo-hoodie', 'Edgy emo style hoodie.', 89.00, NULL, 5, 'emo hood.jpg', 4.7, 1, 12, 0, 0),
('Sweater', 'sweater', 'Warm and comfortable sweater.', 29.00, NULL, 5, 'Sweater.jpg', 4.4, 1, 30, 1, 0),
('Hooded Jacket', 'hooded-jacket', 'Jacket with hood for extra warmth.', 25.00, NULL, 5, 'hooded jacket.jpg', 4.5, 1, 22, 0, 1),
('Plain Hoodie', 'plain-hoodie', 'Simple plain hoodie for everyday wear.', 45.00, NULL, 5, 'plain hoodie.jpg', 4.6, 1, 28, 0, 0),
('Plain Blue Hoodie', 'plain-blue-hoodie', 'Classic blue hoodie.', 15.00, NULL, 5, 'loose hoodie.jpg', 4.3, 1, 35, 1, 0),
('Cardigan Sweater', 'cardigan-sweater', 'Elegant cardigan sweater.', 69.00, NULL, 5, 'Cardigan Sweater.jpg', 4.7, 1, 14, 0, 1);

-- =====================================================
-- Insert Products - T-SHIRTS (Category ID: 6)
-- =====================================================
INSERT INTO products (name, slug, description, price, old_price, category_id, image, rating, in_stock, stock_quantity, is_new, is_bestseller) VALUES
('Crew Neck T-Shirt', 'crew-neck-tshirt', 'Classic crew neck t-shirt.', 59.00, NULL, 6, 'Crew Neck T-Shir.jpg', 4.8, 1, 25, 1, 1),
('Jersey Shirt', 'jersey-shirt', 'Comfortable jersey shirt.', 35.00, NULL, 6, 'jersey shirt.jpg', 4.7, 1, 30, 0, 1),
('Emo Shirt', 'emo-shirt', 'Edgy emo style shirt.', 29.00, NULL, 6, 'emo shirts.jpg', 4.5, 1, 20, 1, 0),
('Plain Shirt', 'plain-shirt', 'Simple plain shirt.', 39.00, NULL, 6, 'plain shirt.jpg', 4.6, 1, 28, 0, 0),
('Short-Sleeved T-Shirt', 'short-sleeved-tshirt', 'Classic short sleeve t-shirt.', 69.00, 89.00, 6, 'Short-Sleeved T-Shirt.jpg', 4.7, 1, 22, 0, 1),
('Tee Shirt', 'tee-shirt', 'Basic tee shirt.', 55.00, NULL, 6, 'Tee Shirt.jpg', 4.6, 1, 26, 1, 0),
('Y2K Shirt', 'y2k-shirt', 'Retro Y2K style shirt.', 25.00, NULL, 6, 'Y2K shirt.jpg', 4.4, 1, 32, 0, 0),
('Navy Blue T-Shirt', 'navy-blue-tshirt', 'Classic navy blue t-shirt.', 79.00, NULL, 6, 'Navy Blue T shirt.jpg', 4.7, 1, 18, 1, 0),
('Y2K Black Shirt', 'y2k-black-shirt', 'Black Y2K style shirt.', 45.00, NULL, 6, 'Y2K tshirt.jpg', 4.5, 1, 24, 0, 0),
('Summer T-Shirt', 'summer-tshirt', 'Light summer t-shirt.', 35.00, NULL, 6, 'Summer T-shirt.jpg', 4.8, 1, 35, 0, 1);

-- =====================================================
-- Insert Products - ACCESSORIES (Category ID: 7)
-- =====================================================
INSERT INTO products (name, slug, description, price, old_price, category_id, image, rating, in_stock, stock_quantity, is_new, is_bestseller) VALUES
('Leather Belt', 'leather-belt', 'Genuine leather belt.', 29.00, NULL, 7, 'Leather Belt.jpg', 4.5, 1, 50, 1, 1),
('Sunglasses', 'sunglasses', 'Stylish UV protection sunglasses.', 49.00, NULL, 7, 'Sunglasses.jpg', 4.7, 1, 40, 1, 0),
('Vintage Watch', 'vintage-watch', 'Classic vintage style watch.', 89.00, 119.00, 7, 'Vintage Watch.jpg', 4.8, 1, 15, 0, 1),
('Scarf', 'scarf', 'Warm and stylish scarf.', 25.00, NULL, 7, 'scarf.jpg', 4.4, 1, 35, 0, 0),
('Hat', 'hat', 'Stylish cap/hat.', 35.00, NULL, 7, 'cap.jpg', 4.6, 1, 28, 1, 0),
('Daisy Wallet', 'daisy-wallet', 'Cute daisy print wallet.', 39.00, NULL, 7, 'Daisy Wallet.jpg', 4.7, 0, 0, 0, 1),
('Necklace', 'necklace', 'Elegant necklace.', 45.00, NULL, 7, 'Necklace.jpg', 4.8, 1, 30, 1, 0),
('Earrings', 'earrings', 'Beautiful earrings.', 29.00, NULL, 7, 'Earrings.jpg', 4.6, 1, 45, 0, 0),
('Bracelet', 'bracelet', 'Stylish bracelet.', 35.00, NULL, 7, 'Bracelet.jpg', 4.5, 1, 38, 1, 0),
('Hair Clip', 'hair-clip', 'Cute hair clip.', 15.00, NULL, 7, 'Hair Clip.jpg', 4.3, 1, 60, 1, 0),
('Bag Charm', 'bag-charm', 'Adorable bag charm.', 19.00, NULL, 7, 'Bag Charms.jpg', 4.4, 1, 55, 0, 0),
('Keychain', 'keychain', 'Stylish keychain.', 25.00, NULL, 7, 'Keychain.jpg', 4.6, 1, 42, 0, 1);

-- =====================================================
-- Insert Products - FOOTWEAR (Category ID: 8)
-- =====================================================
INSERT INTO products (name, slug, description, price, old_price, category_id, image, rating, in_stock, stock_quantity, is_new, is_bestseller) VALUES
('Sneakers', 'sneakers', 'Comfortable everyday sneakers.', 89.00, NULL, 8, 'Sneaker.jpg', 4.8, 1, 25, 1, 1),
('Loafers', 'loafers', 'Classic loafers for formal wear.', 79.00, NULL, 8, 'Loafer.jpg', 4.7, 1, 18, 0, 1),
('Boots', 'boots', 'Stylish boots for winter.', 129.00, 159.00, 8, 'Boots.jpg', 4.9, 1, 12, 0, 1),
('Sandals', 'sandals', 'Comfortable summer sandals.', 49.00, NULL, 8, 'Sandals.jpg', 4.6, 1, 30, 1, 0),
('Heels', 'heels', 'Elegant heels for parties.', 99.00, 129.00, 8, 'Heels.jpg', 4.8, 1, 15, 0, 1),
('Flats', 'flats', 'Comfortable everyday flats.', 59.00, NULL, 8, 'Flats.jpg', 4.5, 1, 22, 0, 0),
('Running Shoes', 'running-shoes', 'Sports running shoes.', 99.00, NULL, 8, 'Running Shoes.jpg', 4.7, 1, 20, 1, 0),
('Converse', 'converse', 'Classic Converse style shoes.', 69.00, NULL, 8, 'converse.jpg', 4.5, 1, 28, 0, 0),
('Crocs', 'crocs', 'Comfortable crocs for casual wear.', 79.00, NULL, 8, 'Crocs.jpg', 4.6, 1, 35, 1, 0);

-- =====================================================
-- Insert Sample Newsletter Subscribers
-- =====================================================
INSERT INTO newsletter (email) VALUES
('user1@example.com'),
('user2@example.com');

-- =====================================================
-- Insert Sample Contact Messages
-- =====================================================
INSERT INTO contacts (name, email, phone, subject, message, is_read) VALUES
('John Doe', 'john@example.com', '+92 300 1234567', 'Product Inquiry', 'I have a question about the Salwar Kameez.', 0),
('Jane Smith', 'jane@example.com', '+92 321 7654321', 'Order Issue', 'My order is delayed. Please help.', 0);

-- =====================================================
-- Insert Sample Orders
-- =====================================================
INSERT INTO orders (order_number, user_id, full_name, email, phone, address, city, state, zip, country, subtotal, shipping, total, payment_method, order_status) VALUES
('VA-20241201-ABC123', 2, 'John Doe', 'john@example.com', '+92 300 1234567', '123 Main Street', 'Karachi', 'Sindh', '75500', 'Pakistan', 89.00, 10.00, 99.00, 'cod', 'delivered'),
('VA-20241202-DEF456', 2, 'John Doe', 'john@example.com', '+92 300 1234567', '123 Main Street', 'Karachi', 'Sindh', '75500', 'Pakistan', 129.00, 0.00, 129.00, 'credit_card', 'processing');

-- =====================================================
-- Insert Sample Order Items
-- =====================================================
INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, total) VALUES
(1, 1, 'Salwar Kameez', 89.00, 1, 89.00),
(2, 2, 'Embroidered Kurta', 129.00, 1, 129.00);

-- =====================================================
-- Insert Sample Cart Items
-- =====================================================
INSERT INTO cart (user_id, product_id, quantity) VALUES
(2, 3, 2),
(2, 5, 1);

-- =====================================================
-- Insert Sample Wishlist Items
-- =====================================================
INSERT INTO wishlist (user_id, product_id) VALUES
(2, 4),
(2, 7);

-- =====================================================
-- Display all tables data count
-- =====================================================
SELECT 'users' as table_name, COUNT(*) as count FROM users
UNION ALL
SELECT 'categories', COUNT(*) FROM categories
UNION ALL
SELECT 'products', COUNT(*) FROM products
UNION ALL
SELECT 'orders', COUNT(*) FROM orders
UNION ALL
SELECT 'order_items', COUNT(*) FROM order_items
UNION ALL
SELECT 'cart', COUNT(*) FROM cart
UNION ALL
SELECT 'wishlist', COUNT(*) FROM wishlist
UNION ALL
SELECT 'contacts', COUNT(*) FROM contacts
UNION ALL
SELECT 'newsletter', COUNT(*) FROM newsletter;

-- =====================================================
-- THE END
-- =====================================================