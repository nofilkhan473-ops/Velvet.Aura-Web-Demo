<?php
$page_title = 'Shopping Cart';
require_once 'backend/includes/header.php';

$cart_items = getUserCart();
$subtotal = 0;

foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$shipping = $subtotal > 100 ? 0 : 10;
$total = $subtotal + $shipping;

// Hidden input to check login status for JS
echo '<input type="hidden" id="userLoggedIn" value="' . (isLoggedIn() ? 'true' : 'false') . '">';
?>

<section class="page-header">
    <div class="container">
        <h1>Shopping Cart</h1>
        <p>Review your items</p>
    </div>
</section>

<section class="cart-section">
    <div class="container">
        <?php if(empty($cart_items)): ?>
        <div class="empty-cart">
            <i class="fa-regular fa-bag-shopping"></i>
            <h3>Your cart is empty</h3>
            <p>Looks like you haven't added any items yet</p>
            <a href="shop.php" class="btn-primary-custom">Start Shopping</a>
        </div>
        <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="cart-table-container">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($cart_items as $item): ?>
                            <tr>
                                <td data-label="Product">
                                    <div class="cart-product">
                                        <img src="assets/images/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="cart-product-img">
                                        <div>
                                            <strong><?php echo $item['name']; ?></strong>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Price">$<?php echo number_format($item['price'], 2); ?></td>
                                <td data-label="Quantity">
                                    <div class="quantity-control">
                                        <button class="quantity-btn qty-minus" data-id="<?php echo $item['product_id']; ?>">-</button>
                                        <input type="text" class="quantity-input" id="qty-<?php echo $item['product_id']; ?>" value="<?php echo $item['quantity']; ?>" readonly>
                                        <button class="quantity-btn qty-plus" data-id="<?php echo $item['product_id']; ?>">+</button>
                                    </div>
                                </td>
                                <td data-label="Total">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                <td data-label="Remove">
                                    <button class="btn-remove remove-from-cart" data-id="<?php echo $item['product_id']; ?>">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="cart-actions mt-4">
                    <a href="shop.php" class="btn-continue">← Continue Shopping</a>
                    <button onclick="clearCart()" class="btn-clear-cart">Clear Cart</button>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="cart-summary">
                    <h3>Order Summary</h3>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span><?php echo $shipping == 0 ? 'Free' : '$' . number_format($shipping, 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    <a href="checkout.php" class="btn-checkout">Proceed to Checkout →</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.page-header {
    background: #faf9f8;
    padding: 100px 0 60px;
    text-align: center;
}

.page-header h1 {
    font-size: 48px;
    font-weight: 700;
}

.cart-section {
    padding: 60px 0;
}

.cart-table {
    width: 100%;
    border-collapse: collapse;
}

.cart-table th {
    text-align: left;
    padding: 15px 0;
    border-bottom: 1px solid #eee;
    font-weight: 600;
}

.cart-table td {
    padding: 20px 0;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}

.cart-product {
    display: flex;
    align-items: center;
    gap: 15px;
}

.cart-product-img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 12px;
}

.quantity-control {
    display: flex;
    align-items: center;
    gap: 8px;
}

.quantity-btn {
    width: 30px;
    height: 30px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 50%;
    cursor: pointer;
    transition: 0.3s;
}

.quantity-btn:hover {
    background: #111;
    color: white;
}

.quantity-input {
    width: 50px;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 5px;
}

.btn-remove {
    background: none;
    border: none;
    color: #ff6b6b;
    cursor: pointer;
    font-size: 18px;
    transition: 0.3s;
}

.btn-remove:hover {
    color: #ff4444;
}

.cart-summary {
    background: #faf9f8;
    border-radius: 20px;
    padding: 30px;
    position: sticky;
    top: 100px;
}

.cart-summary h3 {
    margin-bottom: 20px;
    font-size: 20px;
    font-weight: 700;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}

.summary-row.total {
    font-size: 20px;
    font-weight: 700;
    border-bottom: none;
    padding-top: 15px;
}

.btn-checkout {
    width: 100%;
    background: #111;
    color: white;
    padding: 16px;
    border: none;
    border-radius: 40px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    margin-top: 20px;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-checkout:hover {
    background: #333;
}

.btn-continue {
    background: transparent;
    color: #111;
    padding: 12px 24px;
    border: 1px solid #ddd;
    border-radius: 40px;
    text-decoration: none;
    display: inline-block;
}

.btn-clear-cart {
    background: #ff6b6b;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 40px;
    cursor: pointer;
    margin-left: 10px;
}

.empty-cart {
    text-align: center;
    padding: 60px 20px;
}

.empty-cart i {
    font-size: 64px;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-cart h3 {
    font-size: 24px;
    margin-bottom: 10px;
}

.cart-actions {
    display: flex;
    gap: 10px;
}

@media (max-width: 768px) {
    .cart-table thead {
        display: none;
    }
    
    .cart-table td {
        display: block;
        text-align: right;
        padding: 10px;
    }
    
    .cart-table td:before {
        content: attr(data-label);
        float: left;
        font-weight: 600;
    }
    
    .cart-product {
        justify-content: flex-end;
    }
    
    .cart-actions {
        flex-direction: column;
    }
    
    .btn-clear-cart {
        margin-left: 0;
    }
}
</style>

<?php require_once 'backend/includes/footer.php'; ?>