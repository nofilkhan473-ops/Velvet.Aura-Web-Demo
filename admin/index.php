<?php
$page_title = 'Dashboard';
require_once 'includes/header.php';
require_once '../backend/config/database.php';

// Get stats
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products"))['count'];
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders"))['count'];
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE is_admin = 0"))['count'];
$total_messages = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM contacts WHERE is_read = 0"))['count'];
?>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <i class="fa-solid fa-box"></i>
            <h3><?php echo $total_products; ?></h3>
            <p>Total Products</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <i class="fa-solid fa-shopping-cart"></i>
            <h3><?php echo $total_orders; ?></h3>
            <p>Total Orders</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <i class="fa-solid fa-users"></i>
            <h3><?php echo $total_users; ?></h3>
            <p>Total Users</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <i class="fa-solid fa-envelope"></i>
            <h3><?php echo $total_messages; ?></h3>
            <p>Unread Messages</p>
        </div>
    </div>
</div>

<div class="table-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0;">Recent Orders</h3>
        <a href="orders.php" style="color: #D4B5A7;">View All →</a>
    </div>
    <table class="table table-hover">
        <thead>
            <tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr>
        </thead>
        <tbody>
            <?php
            $recent = mysqli_query($conn, "SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
            while($order = mysqli_fetch_assoc($recent)):
            ?>
            <tr>
                <td><strong><?php echo $order['order_number']; ?></strong></td>
                <td><?php echo $order['full_name']; ?></td>
                <td>$<?php echo number_format($order['total'], 2); ?></td>
                <td><span class="badge-<?php echo $order['order_status']; ?>"><?php echo ucfirst($order['order_status']); ?></span></td>
                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                <td><a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn-view"><i class="fa-regular fa-eye"></i> View</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>