<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
                <h4>VELVET AURA</h4>
                <p>Ethical fashion for the conscious soul. Timeless pieces designed to last.</p>
                <div class="social-icons">
                    <a href="#"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#"><i class="fa-brands fa-pinterest"></i></a>
                    <a href="#"><i class="fa-brands fa-tiktok"></i></a>
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
                    <?php if(!isLoggedIn()): ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                    <li><a href="my-orders.php">My Orders</a></li>
                    <li><a href="wishlist.php">Wishlist</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <h5>Contact Us</h5>
                <ul class="contact-info">
                    <li><i class="fa-regular fa-envelope"></i> <a href="mailto:hello@velvetaura.com">hello@velvetaura.com</a></li>
                    <li><i class="fa-regular fa-phone"></i> <a href="tel:+15551234567">+1 (555) 123-4567</a></li>
                    <li><i class="fa-regular fa-location-dot"></i> 123 Fashion Street, NY 10001</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Velvet Aura. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function showNotification(msg, type = 'success') {
        let n = document.createElement('div');
        n.className = 'notification-toast';
        n.innerHTML = `<i class="fa-regular fa-${type === 'success' ? 'circle-check' : 'circle-exclamation'}"></i><span>${msg}</span>`;
        document.body.appendChild(n);
        setTimeout(() => n.classList.add('show'), 100);
        setTimeout(() => { 
            n.classList.remove('show'); 
            setTimeout(() => n.remove(), 300); 
        }, 3000);
    }
    
    function addToCart(productId, quantity = 1) {
        const isLoggedIn = document.getElementById('userLoggedIn')?.value === 'true';
        
        if(isLoggedIn) {
            fetch('backend/cart/add-to-cart.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'product_id=' + productId + '&quantity=' + quantity
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    showNotification(data.message);
                    document.querySelectorAll('#cartCount').forEach(el => el.textContent = data.cart_count);
                } else {
                    showNotification(data.message, 'error');
                }
            });
        } else {
            let cart = JSON.parse(localStorage.getItem('guest_cart') || '[]');
            let existing = cart.find(item => item.id == productId);
            if(existing) existing.quantity += parseInt(quantity);
            else cart.push({id: productId, quantity: parseInt(quantity)});
            localStorage.setItem('guest_cart', JSON.stringify(cart));
            let total = cart.reduce((sum, item) => sum + item.quantity, 0);
            document.querySelectorAll('#cartCount').forEach(el => el.textContent = total);
            showNotification('Added to cart!');
        }
    }
    
    function toggleWishlist(productId) {
        const isLoggedIn = document.getElementById('userLoggedIn')?.value === 'true';
        const btn = event.currentTarget;
        
        if(isLoggedIn) {
            fetch('backend/wishlist/add-to-wishlist.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    showNotification(data.message);
                    if(data.action === 'added') {
                        btn.classList.add('active');
                        btn.innerHTML = '<i class="fa-solid fa-heart"></i>';
                    } else {
                        btn.classList.remove('active');
                        btn.innerHTML = '<i class="fa-regular fa-heart"></i>';
                    }
                    document.querySelectorAll('#wishlistCount').forEach(el => {
                        let count = parseInt(el.textContent);
                        el.textContent = data.action === 'added' ? count + 1 : count - 1;
                    });
                }
            });
        } else {
            let wishlist = JSON.parse(localStorage.getItem('guest_wishlist') || '[]');
            let index = wishlist.indexOf(productId);
            if(index === -1) {
                wishlist.push(productId);
                showNotification('Added to wishlist');
                btn.classList.add('active');
                btn.innerHTML = '<i class="fa-solid fa-heart"></i>';
            } else {
                wishlist.splice(index, 1);
                showNotification('Removed from wishlist');
                btn.classList.remove('active');
                btn.innerHTML = '<i class="fa-regular fa-heart"></i>';
            }
            localStorage.setItem('guest_wishlist', JSON.stringify(wishlist));
            document.querySelectorAll('#wishlistCount').forEach(el => el.textContent = wishlist.length);
        }
    }
</script>
</body>
</html>