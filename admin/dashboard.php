<?php
$page_title = 'Dashboard';
require_once 'includes/header.php';
require_once '../backend/config/database.php';

// Get all stats
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products"))['count'] ?? 0;
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders"))['count'] ?? 0;
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE is_admin = 0"))['count'] ?? 0;
$pending_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending'"))['count'] ?? 0;
$processing_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE order_status = 'processing'"))['count'] ?? 0;
$shipped_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE order_status = 'shipped'"))['count'] ?? 0;
$delivered_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE order_status = 'delivered'"))['count'] ?? 0;
$cancelled_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE order_status = 'cancelled'"))['count'] ?? 0;
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total) as total FROM orders WHERE order_status != 'cancelled'"))['total'] ?? 0;
$total_messages = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM contacts WHERE is_read = 0"))['count'] ?? 0;
$pending_reviews = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM reviews WHERE is_approved = 0"))['count'] ?? 0;

// Get low stock products
$low_stock_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE stock_quantity < 5 AND stock_quantity > 0"))['count'] ?? 0;
$out_of_stock_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE stock_quantity = 0"))['count'] ?? 0;

// Get today's orders
$today_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()"))['count'] ?? 0;
$today_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total) as total FROM orders WHERE DATE(created_at) = CURDATE() AND order_status != 'cancelled'"))['total'] ?? 0;

// Get this month stats
$this_month_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"))['count'] ?? 0;
$this_month_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total) as total FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND order_status != 'cancelled'"))['total'] ?? 0;

// Get recent orders
$recent_orders = mysqli_query($conn, "SELECT * FROM orders ORDER BY created_at DESC LIMIT 8");

// Get top selling products
$top_products = mysqli_query($conn, "SELECT p.id, p.name, p.image, SUM(oi.quantity) as total_sold, SUM(oi.total) as revenue 
                                     FROM order_items oi 
                                     JOIN products p ON oi.product_id = p.id 
                                     GROUP BY oi.product_id 
                                     ORDER BY total_sold DESC LIMIT 5");

// Get recent customers
$recent_customers = mysqli_query($conn, "SELECT * FROM users WHERE is_admin = 0 ORDER BY created_at DESC LIMIT 5");

// Get low stock products list for alert
$low_stock_products = mysqli_query($conn, "SELECT p.*, c.name as category_name 
                                           FROM products p 
                                           LEFT JOIN categories c ON p.category_id = c.id 
                                           WHERE p.stock_quantity < 5 AND p.stock_quantity > 0 
                                           ORDER BY p.stock_quantity ASC LIMIT 5");
?>

<style>
    .stat-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .order-status-card {
        background: white;
        border-radius: 16px;
        padding: 15px;
        text-align: center;
        transition: all 0.3s;
        border: 1px solid #eef2f6;
    }
    .order-status-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    .order-status-card .status-icon {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
    }
    .quick-action-btn {
        padding: 12px;
        border-radius: 12px;
        text-align: center;
        text-decoration: none;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: #f8fafc;
        color: #1e293b;
        border: 1px solid #eef2f6;
    }
    .quick-action-btn:hover {
        background: #517a96;
        color: white;
        transform: translateY(-2px);
    }
    .recent-activity-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.2s;
    }
    .recent-activity-item:hover {
        background: #f8fafc;
        transform: translateX(5px);
        padding-left: 10px;
    }
    .progress-bar-custom {
        height: 6px;
        background: #e2e8f0;
        border-radius: 3px;
        overflow: hidden;
    }
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #517a96, #6b9fbf);
        border-radius: 3px;
        width: 0%;
        transition: width 1s ease;
    }
    .alert-low-stock {
        background: #fef3c7;
        border: 1px solid #fde68a;
        border-radius: 16px;
        padding: 15px 20px;
    }
</style>

<!-- Stats Overview -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card" data-aos="fade-up" data-aos-delay="0" onclick="window.location.href='products.php'">
            <div class="stat-icon">
                <i class="fa-solid fa-box"></i>
            </div>
            <h3><?php echo $total_products; ?></h3>
            <p>Total Products</p>
            <?php if($low_stock_count > 0): ?>
            <div class="stat-trend" style="font-size: 11px; margin-top: 8px; color: #d97706;">
                <i class="fa-solid fa-exclamation-triangle"></i> <?php echo $low_stock_count; ?> low on stock
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" data-aos="fade-up" data-aos-delay="50" onclick="window.location.href='orders.php'">
            <div class="stat-icon">
                <i class="fa-solid fa-shopping-cart"></i>
            </div>
            <h3><?php echo $total_orders; ?></h3>
            <p>Total Orders</p>
            <div class="stat-trend" style="font-size: 11px; margin-top: 8px; color: #059669;">
                <i class="fa-solid fa-calendar-day"></i> <?php echo $today_orders; ?> today
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" data-aos="fade-up" data-aos-delay="100" onclick="window.location.href='users.php'">
            <div class="stat-icon">
                <i class="fa-solid fa-users"></i>
            </div>
            <h3><?php echo $total_users; ?></h3>
            <p>Total Customers</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" data-aos="fade-up" data-aos-delay="150">
            <div class="stat-icon">
                <i class="fa-solid fa-dollar-sign"></i>
            </div>
            <h3>$<?php echo number_format($total_revenue, 0); ?></h3>
            <p>Total Revenue</p>
            <div class="stat-trend" style="font-size: 11px; margin-top: 8px; color: #059669;">
                <i class="fa-solid fa-chart-line"></i> $<?php echo number_format($today_revenue, 0); ?> today
            </div>
        </div>
    </div>
</div>

<!-- Order Status Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-2 col-6">
        <div class="order-status-card" onclick="window.location.href='orders.php?status=pending'">
            <div class="status-icon" style="background: #fef3c7;">
                <i class="fa-solid fa-clock" style="color: #d97706; font-size: 20px;"></i>
            </div>
            <h4 style="font-size: 18px; margin: 0;"><?php echo $pending_orders; ?></h4>
            <p style="font-size: 11px; color: #666; margin: 0;">Pending</p>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="order-status-card" onclick="window.location.href='orders.php?status=processing'">
            <div class="status-icon" style="background: #dbeafe;">
                <i class="fa-solid fa-cog" style="color: #2563eb; font-size: 20px;"></i>
            </div>
            <h4 style="font-size: 18px; margin: 0;"><?php echo $processing_orders; ?></h4>
            <p style="font-size: 11px; color: #666; margin: 0;">Processing</p>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="order-status-card" onclick="window.location.href='orders.php?status=shipped'">
            <div class="status-icon" style="background: #cff4fc;">
                <i class="fa-solid fa-truck" style="color: #0891b2; font-size: 20px;"></i>
            </div>
            <h4 style="font-size: 18px; margin: 0;"><?php echo $shipped_orders; ?></h4>
            <p style="font-size: 11px; color: #666; margin: 0;">Shipped</p>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="order-status-card" onclick="window.location.href='orders.php?status=delivered'">
            <div class="status-icon" style="background: #d1fae5;">
                <i class="fa-solid fa-circle-check" style="color: #059669; font-size: 20px;"></i>
            </div>
            <h4 style="font-size: 18px; margin: 0;"><?php echo $delivered_orders; ?></h4>
            <p style="font-size: 11px; color: #666; margin: 0;">Delivered</p>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="order-status-card" onclick="window.location.href='orders.php?status=cancelled'">
            <div class="status-icon" style="background: #fee2e2;">
                <i class="fa-solid fa-ban" style="color: #dc2626; font-size: 20px;"></i>
            </div>
            <h4 style="font-size: 18px; margin: 0;"><?php echo $cancelled_orders; ?></h4>
            <p style="font-size: 11px; color: #666; margin: 0;">Cancelled</p>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="order-status-card" onclick="window.location.href='orders.php'">
            <div class="status-icon" style="background: #e0e7ff;">
                <i class="fa-solid fa-eye" style="color: #4f46e5; font-size: 20px;"></i>
            </div>
            <h4 style="font-size: 18px; margin: 0;">View All</h4>
            <p style="font-size: 11px; color: #666; margin: 0;">Orders</p>
        </div>
    </div>
</div>

<!-- Low Stock Alert -->
<?php if($low_stock_count > 0 || $out_of_stock_count > 0): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert-low-stock">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 24px; color: #d97706;"></i>
                    <div>
                        <strong style="color: #92400e;">Inventory Alert!</strong>
                        <span style="color: #78350f; font-size: 13px;">
                            <?php echo $low_stock_count; ?> product(s) running low on stock,
                            <?php echo $out_of_stock_count; ?> product(s) out of stock.
                        </span>
                    </div>
                </div>
                <a href="low-stock.php" class="btn-add" style="padding: 8px 20px; font-size: 12px; background: #d97706;">
                    <i class="fa-solid fa-eye"></i> View Details
                </a>
            </div>
            <?php if($low_stock_count > 0): ?>
            <div class="row mt-3">
                <?php while($low_prod = mysqli_fetch_assoc($low_stock_products)): ?>
                <div class="col-md-4 col-sm-6 mb-2">
                    <div style="display: flex; align-items: center; gap: 10px; background: white; padding: 8px 12px; border-radius: 12px;">
                        <img src="../assets/images/<?php echo $low_prod['image'] ?? 'default.jpg'; ?>" style="width: 35px; height: 35px; object-fit: cover; border-radius: 8px;">
                        <div style="flex: 1;">
                            <div style="font-size: 12px; font-weight: 600;"><?php echo htmlspecialchars(substr($low_prod['name'], 0, 25)); ?></div>
                            <div style="font-size: 11px; color: #d97706;">Stock: <?php echo $low_prod['stock_quantity']; ?> left</div>
                        </div>
                        <a href="edit-product.php?id=<?php echo $low_prod['id']; ?>" class="btn-edit" style="padding: 4px 10px;">Restock</a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Main Content Row -->
<div class="row g-4">
    <!-- Recent Orders Table -->
    <div class="col-lg-8">
        <div class="table-container" data-aos="fade-up" data-aos-delay="200">
            <div class="table-header">
                <h3><i class="fa-solid fa-clock-rotate-left" style="color: #517a96;"></i> Recent Orders</h3>
                <a href="orders.php" style="color: #517a96; text-decoration: none; font-size: 13px;">
                    View All <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr><th>Order #</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($recent_orders) > 0): ?>
                            <?php while($order = mysqli_fetch_assoc($recent_orders)): ?>
                            <tr>
                                <td><strong>#<?php echo substr($order['order_number'], -8); ?></strong></td>
                                <td><?php echo htmlspecialchars(explode(' ', $order['full_name'])[0]); ?></td>
                                <td>$<?php echo number_format($order['total'], 2); ?></td>
                                <td><span class="badge-<?php echo $order['order_status']; ?>"><?php echo ucfirst($order['order_status']); ?></span></td>
                                <td><?php echo date('M d', strtotime($order['created_at'])); ?></td>
                                <td><a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn-view btn-sm">View</a></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4">No orders yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-lg-4">
        <div class="table-container" data-aos="fade-up" data-aos-delay="250">
            <div class="table-header">
                <h3><i class="fa-solid fa-bolt" style="color: #517a96;"></i> Quick Actions</h3>
            </div>
            <div style="padding: 15px;">
                <a href="add-product.php" class="quick-action-btn" style="margin-bottom: 10px;">
                    <i class="fa-solid fa-plus"></i> Add New Product
                </a>
                <a href="orders.php" class="quick-action-btn" style="margin-bottom: 10px;">
                    <i class="fa-solid fa-truck"></i> Manage Orders
                </a>
                <a href="categories.php" class="quick-action-btn" style="margin-bottom: 10px;">
                    <i class="fa-solid fa-tags"></i> Manage Categories
                </a>
                <a href="low-stock.php" class="quick-action-btn" style="margin-bottom: 10px;">
                    <i class="fa-solid fa-chart-line"></i> Check Low Stock
                    <?php if($low_stock_count > 0): ?>
                    <span class="badge-unread" style="margin-left: auto;"><?php echo $low_stock_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="contacts.php" class="quick-action-btn">
                    <i class="fa-solid fa-envelope"></i> View Messages
                    <?php if($total_messages > 0): ?>
                    <span class="badge-unread" style="margin-left: auto;"><?php echo $total_messages; ?> new</span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
        
        <!-- Monthly Summary -->
        <div class="table-container" data-aos="fade-up" data-aos-delay="300" style="margin-top: 20px;">
            <div class="table-header">
                <h3><i class="fa-solid fa-chart-simple" style="color: #517a96;"></i> This Month</h3>
            </div>
            <div style="padding: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: #64748b;">Orders</span>
                    <span><strong><?php echo $this_month_orders; ?></strong></span>
                </div>
                <div class="progress-bar-custom mb-3">
                    <div class="progress-fill" style="width: <?php echo min(100, ($this_month_orders / 100) * 100); ?>%"></div>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: #64748b;">Revenue</span>
                    <span><strong>$<?php echo number_format($this_month_revenue, 0); ?></strong></span>
                </div>
                <div class="progress-bar-custom">
                    <div class="progress-fill" style="width: <?php echo min(100, ($this_month_revenue / 10000) * 100); ?>%"></div>
                </div>
                <div class="mt-3 pt-2" style="border-top: 1px solid #eef2f6;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">Pending Reviews</span>
                        <span><strong><?php echo $pending_reviews; ?></strong> <a href="reviews.php" style="color: #517a96; font-size: 11px;">(manage)</a></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 8px;">
                        <span style="color: #64748b;">Unread Messages</span>
                        <span><strong><?php echo $total_messages; ?></strong> <a href="contacts.php" style="color: #517a96; font-size: 11px;">(view)</a></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Selling & New Customers -->
<div class="row g-4 mt-2">
    <!-- Top Selling Products -->
    <div class="col-md-6">
        <div class="table-container" data-aos="fade-up" data-aos-delay="350">
            <div class="table-header">
                <h3><i class="fa-solid fa-fire" style="color: #517a96;"></i> Top Selling Products</h3>
                <a href="products.php" style="color: #517a96; text-decoration: none; font-size: 13px;">View All</a>
            </div>
            <div style="padding: 0 20px 20px 20px;">
                <?php if(mysqli_num_rows($top_products) > 0): ?>
                    <?php while($product = mysqli_fetch_assoc($top_products)): ?>
                    <div class="recent-activity-item">
                        <img src="../assets/images/<?php echo $product['image'] ?? 'default.jpg'; ?>" style="width: 45px; height: 45px; object-fit: cover; border-radius: 10px;" onerror="this.src='https://placehold.co/45x45?text=No+Image'">
                        <div style="flex: 1;">
                            <div><strong><?php echo htmlspecialchars(substr($product['name'], 0, 30)); ?></strong></div>
                            <div style="font-size: 11px; color: #64748b;">Sold: <?php echo $product['total_sold']; ?> units</div>
                        </div>
                        <div style="text-align: right;">
                            <div><strong>$<?php echo number_format($product['revenue'], 0); ?></strong></div>
                            <div style="font-size: 11px; color: #10b981;">Revenue</div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-4" style="color: #64748b;">No sales data yet</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Customers -->
    <div class="col-md-6">
        <div class="table-container" data-aos="fade-up" data-aos-delay="400">
            <div class="table-header">
                <h3><i class="fa-solid fa-user-plus" style="color: #517a96;"></i> New Customers</h3>
                <a href="users.php" style="color: #517a96; text-decoration: none; font-size: 13px;">View All</a>
            </div>
            <div style="padding: 0 20px 20px 20px;">
                <?php if(mysqli_num_rows($recent_customers) > 0): ?>
                    <?php while($customer = mysqli_fetch_assoc($recent_customers)): ?>
                    <div class="recent-activity-item">
                        <div class="activity-icon" style="width: 35px; height: 35px; border-radius: 10px; background: #f1f5f9; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-user" style="color: #517a96;"></i>
                        </div>
                        <div style="flex: 1;">
                            <div><strong><?php echo htmlspecialchars($customer['name'] ?? $customer['username'] ?? 'Guest'); ?></strong></div>
                            <div style="font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($customer['email']); ?></div>
                        </div>
                        <div style="font-size: 11px; color: #64748b;">
                            <?php echo date('M d', strtotime($customer['created_at'] ?? 'now')); ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-4" style="color: #64748b;">No customers yet</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Animate numbers on load
    document.addEventListener('DOMContentLoaded', function() {
        const statNumbers = document.querySelectorAll('.stat-card h3');
        statNumbers.forEach(el => {
            const text = el.innerText;
            const isCurrency = text.includes('$');
            const finalValue = parseInt(text.replace(/[^0-9]/g, ''));
            if (!isNaN(finalValue) && finalValue > 0) {
                let current = 0;
                const increment = finalValue / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= finalValue) {
                        el.innerText = isCurrency ? '$' + finalValue.toLocaleString() : finalValue.toLocaleString();
                        clearInterval(timer);
                    } else {
                        el.innerText = isCurrency ? '$' + Math.floor(current).toLocaleString() : Math.floor(current).toLocaleString();
                    }
                }, 25);
            }
        });
        
        // Animate progress bars
        document.querySelectorAll('.progress-fill').forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = width;
            }, 300);
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>