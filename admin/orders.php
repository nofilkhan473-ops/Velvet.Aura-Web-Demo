<?php
$page_title = 'Orders';
require_once 'includes/header.php';
require_once '../backend/config/database.php';

// Update order status and tracking number
if(isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $tracking_number = mysqli_real_escape_string($conn, $_POST['tracking_number'] ?? '');
    
    $update_query = "UPDATE orders SET order_status = '$status', tracking_number = '$tracking_number', updated_at = NOW() WHERE id = $order_id";
    mysqli_query($conn, $update_query);
    echo "<script>showNotification('Order status updated successfully!');</script>";
}

// Get filter values
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Build query
$query = "SELECT * FROM orders WHERE 1=1";
if($status_filter && $status_filter != 'all') {
    $query .= " AND order_status = '$status_filter'";
}
if($search) {
    $query .= " AND (order_number LIKE '%$search%' OR full_name LIKE '%$search%' OR email LIKE '%$search%')";
}
if($date_filter) {
    $query .= " AND DATE(created_at) = '$date_filter'";
}
$query .= " ORDER BY created_at DESC";
$orders = mysqli_query($conn, $query);

// Get statistics
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders"))['count'] ?? 0;
$pending_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending'"))['count'] ?? 0;
$processing_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE order_status = 'processing'"))['count'] ?? 0;
$shipped_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE order_status = 'shipped'"))['count'] ?? 0;
$delivered_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE order_status = 'delivered'"))['count'] ?? 0;
$cancelled_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE order_status = 'cancelled'"))['count'] ?? 0;
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total) as total FROM orders WHERE order_status != 'cancelled'"))['total'] ?? 0;
$today_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()"))['count'] ?? 0;
$today_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total) as total FROM orders WHERE DATE(created_at) = CURDATE() AND order_status != 'cancelled'"))['total'] ?? 0;
?>

<style>
    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card-order {
        background: white;
        border-radius: 20px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        transition: all 0.3s;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border: 1px solid #f0f0f0;
    }
    
    .stat-card-order:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    }
    
    .stat-icon-order {
        width: 55px;
        height: 55px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    
    .stat-icon-order.primary { background: #e3f2fd; color: #1976d2; }
    .stat-icon-order.success { background: #e8f5e9; color: #388e3c; }
    .stat-icon-order.warning { background: #fff3e0; color: #f57c00; }
    .stat-icon-order.info { background: #e0f7fa; color: #00838f; }
    
    .stat-info-order h3 {
        font-size: 28px;
        font-weight: 700;
        margin: 0;
        color: #1e293b;
    }
    
    .stat-info-order p {
        margin: 0;
        color: #64748b;
        font-size: 13px;
    }
    
    /* Status Cards Row */
    .status-cards {
        display: flex;
        gap: 12px;
        margin-bottom: 25px;
        flex-wrap: wrap;
    }
    
    .status-card {
        flex: 1;
        min-width: 100px;
        background: white;
        border-radius: 16px;
        padding: 12px;
        text-align: center;
        text-decoration: none;
        transition: all 0.2s;
        border: 1px solid #f0f0f0;
    }
    
    .status-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    
    .status-card.active {
        border: 2px solid #517a96;
        background: #f0f7ff;
    }
    
    .status-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 8px;
        font-size: 18px;
    }
    
    .status-count {
        font-size: 20px;
        font-weight: 700;
        margin: 5px 0;
    }
    
    .status-label {
        font-size: 11px;
        color: #64748b;
    }
    
    /* Filter Bar */
    .filter-bar {
        background: white;
        border-radius: 20px;
        padding: 15px 20px;
        margin-bottom: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    
    .search-wrapper {
        display: flex;
        gap: 10px;
    }
    
    .search-wrapper input {
        padding: 10px 18px;
        border: 1px solid #e2e8f0;
        border-radius: 40px;
        width: 250px;
        font-size: 13px;
    }
    
    .search-wrapper input:focus {
        border-color: #517a96;
        outline: none;
    }
    
    .search-wrapper button {
        padding: 10px 22px;
        background: #517a96;
        color: white;
        border: none;
        border-radius: 40px;
        cursor: pointer;
    }
    
    /* Table Styles */
    .orders-table-container {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    
    .orders-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .orders-table th {
        background: #f8fafc;
        padding: 16px 15px;
        text-align: left;
        font-weight: 600;
        font-size: 12px;
        color: #475569;
        border-bottom: 1px solid #eef2f6;
    }
    
    .orders-table td {
        padding: 16px 15px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
        color: #334155;
    }
    
    .orders-table tr {
        transition: all 0.2s;
    }
    
    .orders-table tr:hover {
        background: #f8fafc;
    }
    
    .order-number {
        font-weight: 700;
        color: #1e293b;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 30px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-processing { background: #dbeafe; color: #2563eb; }
    .status-shipped { background: #cff4fc; color: #0891b2; }
    .status-delivered { background: #d1fae5; color: #059669; }
    .status-cancelled { background: #fee2e2; color: #dc2626; }
    
    .tracking-input {
        padding: 6px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 12px;
        width: 120px;
    }
    
    .tracking-input:focus {
        border-color: #517a96;
        outline: none;
    }
    
    .status-select {
        padding: 6px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 12px;
        background: white;
        cursor: pointer;
    }
    
    .btn-update {
        background: #10b981;
        color: white;
        border: none;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 11px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-update:hover {
        background: #059669;
        transform: translateY(-1px);
    }
    
    .btn-view-order {
        background: #517a96;
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        text-decoration: none;
        font-size: 11px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.2s;
    }
    
    .btn-view-order:hover {
        background: #3d5a73;
        transform: translateY(-1px);
        color: white;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-state i {
        font-size: 70px;
        color: #cbd5e1;
        margin-bottom: 20px;
    }
    
    @media (max-width: 992px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .status-cards {
            overflow-x: auto;
            flex-wrap: nowrap;
        }
        .status-card {
            min-width: 90px;
        }
    }
    
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        .filter-bar {
            flex-direction: column;
        }
        .search-wrapper {
            width: 100%;
        }
        .search-wrapper input {
            flex: 1;
        }
        .orders-table {
            min-width: 800px;
        }
    }
</style>

<!-- Header -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <div>
        <h1 style="font-size: 24px; font-weight: 700; margin: 0; color: #1e293b;">
            <i class="fa-solid fa-truck-fast" style="color: #517a96; margin-right: 10px;"></i> Orders
        </h1>
        <p style="color: #64748b; margin: 5px 0 0; font-size: 13px;">Manage and track customer orders</p>
    </div>
    <div class="stat-info-order" style="text-align: right;">
        <div style="font-size: 13px; color: #64748b;">Today's Revenue</div>
        <div style="font-size: 24px; font-weight: 700; color: #1e293b;">$<?php echo number_format($today_revenue, 0); ?></div>
        <div style="font-size: 11px; color: #10b981;"><i class="fa-solid fa-chart-line"></i> +<?php echo $today_orders; ?> orders today</div>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card-order">
        <div class="stat-icon-order primary">
            <i class="fa-solid fa-shopping-cart"></i>
        </div>
        <div class="stat-info-order">
            <h3><?php echo $total_orders; ?></h3>
            <p>Total Orders</p>
        </div>
    </div>
    <div class="stat-card-order">
        <div class="stat-icon-order success">
            <i class="fa-solid fa-dollar-sign"></i>
        </div>
        <div class="stat-info-order">
            <h3>$<?php echo number_format($total_revenue, 0); ?></h3>
            <p>Total Revenue</p>
        </div>
    </div>
    <div class="stat-card-order">
        <div class="stat-icon-order warning">
            <i class="fa-solid fa-clock"></i>
        </div>
        <div class="stat-info-order">
            <h3><?php echo $pending_count + $processing_count; ?></h3>
            <p>Active Orders</p>
        </div>
    </div>
    <div class="stat-card-order">
        <div class="stat-icon-order info">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="stat-info-order">
            <h3><?php echo $delivered_count; ?></h3>
            <p>Completed</p>
        </div>
    </div>
</div>

<!-- Status Filter Cards -->
<div class="status-cards">
    <a href="orders.php" class="status-card <?php echo !$status_filter || $status_filter == 'all' ? 'active' : ''; ?>">
        <div class="status-icon" style="background: #e2e8f0; color: #475569;">
            <i class="fa-solid fa-list"></i>
        </div>
        <div class="status-count"><?php echo $total_orders; ?></div>
        <div class="status-label">All Orders</div>
    </a>
    <a href="orders.php?status=pending" class="status-card <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
        <div class="status-icon" style="background: #fef3c7; color: #d97706;">
            <i class="fa-solid fa-clock"></i>
        </div>
        <div class="status-count"><?php echo $pending_count; ?></div>
        <div class="status-label">Pending</div>
    </a>
    <a href="orders.php?status=processing" class="status-card <?php echo $status_filter == 'processing' ? 'active' : ''; ?>">
        <div class="status-icon" style="background: #dbeafe; color: #2563eb;">
            <i class="fa-solid fa-cog"></i>
        </div>
        <div class="status-count"><?php echo $processing_count; ?></div>
        <div class="status-label">Processing</div>
    </a>
    <a href="orders.php?status=shipped" class="status-card <?php echo $status_filter == 'shipped' ? 'active' : ''; ?>">
        <div class="status-icon" style="background: #cff4fc; color: #0891b2;">
            <i class="fa-solid fa-truck"></i>
        </div>
        <div class="status-count"><?php echo $shipped_count; ?></div>
        <div class="status-label">Shipped</div>
    </a>
    <a href="orders.php?status=delivered" class="status-card <?php echo $status_filter == 'delivered' ? 'active' : ''; ?>">
        <div class="status-icon" style="background: #d1fae5; color: #059669;">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="status-count"><?php echo $delivered_count; ?></div>
        <div class="status-label">Delivered</div>
    </a>
    <a href="orders.php?status=cancelled" class="status-card <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">
        <div class="status-icon" style="background: #fee2e2; color: #dc2626;">
            <i class="fa-solid fa-ban"></i>
        </div>
        <div class="status-count"><?php echo $cancelled_count; ?></div>
        <div class="status-label">Cancelled</div>
    </a>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <form class="search-wrapper" method="GET">
        <input type="text" name="search" placeholder="Search by order #, customer, email..." value="<?php echo htmlspecialchars($search); ?>">
        <?php if($status_filter): ?>
            <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
        <?php endif; ?>
        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        <?php if($search || $status_filter): ?>
            <a href="orders.php" style="padding: 10px 18px; background: #e2e8f0; border-radius: 40px; color: #475569; text-decoration: none;">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Orders Table -->
<div class="orders-table-container">
    <div class="table-responsive">
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Tracking #</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($orders) > 0): ?>
                    <?php while($order = mysqli_fetch_assoc($orders)): ?>
                    <form method="POST" onsubmit="return confirm('Update this order?')">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <input type="hidden" name="update_status" value="1">
                        
                        <tr>
                            <td class="order-number">#<?php echo $order['order_number']; ?></td>
                            <td><strong><?php echo htmlspecialchars($order['full_name']); ?></strong></td>
                            <td><?php echo $order['email']; ?></td>
                            <td><strong>$<?php echo number_format($order['total'], 2); ?></strong></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></td>
                            <td>
                                <input type="text" name="tracking_number" class="tracking-input" 
                                       value="<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>" 
                                       placeholder="Enter tracking #">
                            </td>
                            <td>
                                <select name="status" class="status-select">
                                    <option value="pending" <?php echo ($order['order_status'] ?? 'pending') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo ($order['order_status'] ?? '') == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="shipped" <?php echo ($order['order_status'] ?? '') == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="delivered" <?php echo ($order['order_status'] ?? '') == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo ($order['order_status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            <td style="white-space: nowrap;">
                                <button type="submit" class="btn-update">
                                    <i class="fa-solid fa-rotate"></i> Update
                                </button>
                                <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn-view-order">
                                    <i class="fa-regular fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    </form>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="fa-solid fa-inbox"></i>
                                <h4>No Orders Found</h4>
                                <p>Orders will appear here once customers place them.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>