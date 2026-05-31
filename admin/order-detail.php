<?php
$page_title = 'Order Details';
require_once 'includes/header.php';
require_once '../backend/config/database.php';

$order_id = (int)$_GET['id'];

// Fetch order details
$order_query = "SELECT * FROM orders WHERE id = $order_id";
$order_result = mysqli_query($conn, $order_query);
$order = mysqli_fetch_assoc($order_result);

if(!$order) {
    echo "<script>window.location.href='orders.php';</script>";
    exit();
}

// Fetch order items
$items_query = "SELECT * FROM order_items WHERE order_id = $order_id";
$items_result = mysqli_query($conn, $items_query);
$items = mysqli_fetch_all($items_result, MYSQLI_ASSOC);

// Update status
if(isset($_POST['update_status'])) {
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $tracking = mysqli_real_escape_string($conn, $_POST['tracking_number'] ?? '');
    
    $update = "UPDATE orders SET order_status = '$status', tracking_number = '$tracking' WHERE id = $order_id";
    if(mysqli_query($conn, $update)) {
        echo "<script>showNotification('Order updated successfully!'); window.location.href='order-detail.php?id=$order_id';</script>";
    } else {
        echo "<script>showNotification('Update failed!', 'error');</script>";
    }
}
?>

<style>
    .page-header-order { margin-bottom: 25px; }
    .page-header-order h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
    .page-header-order p { color: #64748b; margin: 5px 0 0; font-size: 13px; }
    
    .detail-card {
        background: white;
        border-radius: 20px;
        margin-bottom: 25px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border: 1px solid #f0f0f0;
        overflow: hidden;
    }
    
    .detail-card-header {
        padding: 18px 22px;
        border-bottom: 1px solid #eef2f6;
        background: #fafcfc;
    }
    
    .detail-card-header h4 {
        font-size: 16px;
        font-weight: 700;
        margin: 0;
        color: #1e293b;
    }
    
    .detail-card-header h4 i { color: #517a96; margin-right: 8px; }
    .detail-card-body { padding: 22px; }
    
    .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    .info-item { margin-bottom: 15px; }
    .info-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; margin-bottom: 5px; }
    .info-value { font-size: 14px; font-weight: 600; color: #1e293b; }
    
    .status-badge-large {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 20px;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
    }
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-processing { background: #dbeafe; color: #2563eb; }
    .status-shipped { background: #cff4fc; color: #0891b2; }
    .status-delivered { background: #d1fae5; color: #059669; }
    .status-cancelled { background: #fee2e2; color: #dc2626; }
    
    .items-table { width: 100%; border-collapse: collapse; }
    .items-table th { text-align: left; padding: 12px 0; font-size: 12px; font-weight: 600; color: #64748b; border-bottom: 1px solid #eef2f6; }
    .items-table td { padding: 12px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; }
    .items-table tr:last-child td { border-bottom: none; }
    
    .total-row { background: #f8fafc; margin-top: 15px; padding: 15px; border-radius: 12px; }
    .total-line { display: flex; justify-content: space-between; padding: 8px 0; }
    .total-line strong { font-size: 16px; color: #1e293b; }
    
    .form-control-order {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        font-size: 14px;
    }
    .form-control-order:focus { border-color: #517a96; outline: none; }
    .form-label-order { font-size: 12px; font-weight: 600; margin-bottom: 6px; display: block; color: #475569; }
    
    .btn-update-order {
        background: #10b981;
        color: white;
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 40px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 10px;
    }
    .btn-update-order:hover { background: #059669; }
    
    .btn-back-order {
        background: #e2e8f0;
        color: #475569;
        padding: 10px 22px;
        border-radius: 40px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
    }
    .btn-back-order:hover { background: #cbd5e1; }
    
    .address-block { background: #f8fafc; padding: 15px; border-radius: 16px; margin-top: 10px; }
    .address-block p { margin: 0 0 5px 0; line-height: 1.5; }
    
    @media (max-width: 768px) {
        .info-grid { grid-template-columns: 1fr; gap: 10px; }
        .detail-card-header { padding: 15px 18px; }
        .detail-card-body { padding: 18px; }
    }
</style>

<!-- Page Header -->
<div class="page-header-order">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1><i class="fa-solid fa-file-invoice" style="color: #517a96;"></i> Order Details</h1>
            <p>View and manage order #<?php echo htmlspecialchars($order['order_number']); ?></p>
        </div>
        <a href="orders.php" class="btn-back-order">
            <i class="fa-solid fa-arrow-left"></i> Back to Orders
        </a>
    </div>
</div>

<div class="row">
    <!-- Left Column - Order Items -->
    <div class="col-lg-7">
        <div class="detail-card">
            <div class="detail-card-header">
                <h4><i class="fa-solid fa-box"></i> Order Items</h4>
            </div>
            <div class="detail-card-body">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="text-align: center;">Qty</th>
                            <th style="text-align: right;">Price</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($items) > 0): ?>
                            <?php foreach($items as $item): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
                                <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                                <td style="text-align: right;">$<?php echo number_format($item['product_price'], 2); ?></td>
                                <td style="text-align: right;">$<?php echo number_format($item['total'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center;">No items found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="total-row">
                    <div class="total-line">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($order['subtotal'], 2); ?></span>
                    </div>
                    <div class="total-line">
                        <span>Shipping</span>
                        <span><?php echo $order['shipping'] == 0 ? 'Free' : '$' . number_format($order['shipping'], 2); ?></span>
                    </div>
                    <div class="total-line" style="border-top: 1px dashed #e2e8f0; margin-top: 8px; padding-top: 12px;">
                        <span><strong>Total</strong></span>
                        <span><strong style="font-size: 18px; color: #1e293b;">$<?php echo number_format($order['total'], 2); ?></strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column -->
    <div class="col-lg-5">
        <!-- Current Status Card -->
        <div class="detail-card">
            <div class="detail-card-header">
                <h4><i class="fa-solid fa-chart-line"></i> Current Status</h4>
            </div>
            <div class="detail-card-body" style="text-align: center;">
                <?php
                $status = $order['order_status'];
                if($status == 'pending'):
                ?>
                    <span class="status-badge-large status-pending"><i class="fa-solid fa-clock"></i> Pending</span>
                <?php elseif($status == 'processing'): ?>
                    <span class="status-badge-large status-processing"><i class="fa-solid fa-cog"></i> Processing</span>
                <?php elseif($status == 'shipped'): ?>
                    <span class="status-badge-large status-shipped"><i class="fa-solid fa-truck"></i> Shipped</span>
                <?php elseif($status == 'delivered'): ?>
                    <span class="status-badge-large status-delivered"><i class="fa-solid fa-circle-check"></i> Delivered</span>
                <?php elseif($status == 'cancelled'): ?>
                    <span class="status-badge-large status-cancelled"><i class="fa-solid fa-ban"></i> Cancelled</span>
                <?php else: ?>
                    <span class="status-badge-large status-pending"><i class="fa-solid fa-clock"></i> <?php echo ucfirst($status); ?></span>
                <?php endif; ?>
                
                <?php if(!empty($order['tracking_number'])): ?>
                <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #eef2f6;">
                    <span style="font-size: 11px; color: #64748b;">Tracking Number</span>
                    <div style="font-weight: 700; color: #1e293b; margin-top: 5px;"><?php echo htmlspecialchars($order['tracking_number']); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Update Order Card -->
        <div class="detail-card">
            <div class="detail-card-header">
                <h4><i class="fa-solid fa-gear"></i> Update Order</h4>
            </div>
            <div class="detail-card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label-order">Order Status</label>
                        <select name="status" class="form-control-order" required>
                            <option value="pending" <?php echo $order['order_status'] == 'pending' ? 'selected' : ''; ?>>🕐 Pending</option>
                            <option value="processing" <?php echo $order['order_status'] == 'processing' ? 'selected' : ''; ?>>⚙️ Processing</option>
                            <option value="shipped" <?php echo $order['order_status'] == 'shipped' ? 'selected' : ''; ?>>🚚 Shipped</option>
                            <option value="delivered" <?php echo $order['order_status'] == 'delivered' ? 'selected' : ''; ?>>✅ Delivered</option>
                            <option value="cancelled" <?php echo $order['order_status'] == 'cancelled' ? 'selected' : ''; ?>>❌ Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label-order">Tracking Number</label>
                        <input type="text" name="tracking_number" class="form-control-order" 
                               value="<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>" 
                               placeholder="Enter tracking number">
                    </div>
                    
                    <button type="submit" name="update_status" class="btn-update-order">
                        <i class="fa-solid fa-rotate"></i> Update Order
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Customer Information -->
        <div class="detail-card">
            <div class="detail-card-header">
                <h4><i class="fa-solid fa-user"></i> Customer Information</h4>
            </div>
            <div class="detail-card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['full_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Payment Method</div>
                        <div class="info-value"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Shipping Address -->
        <div class="detail-card">
            <div class="detail-card-header">
                <h4><i class="fa-solid fa-location-dot"></i> Shipping Address</h4>
            </div>
            <div class="detail-card-body">
                <div class="address-block">
                    <p><strong><?php echo htmlspecialchars($order['full_name']); ?></strong></p>
                    <p><?php echo nl2br(htmlspecialchars($order['address'])); ?></p>
                    <p><?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['state']); ?> - <?php echo htmlspecialchars($order['zip']); ?></p>
                    <p><?php echo htmlspecialchars($order['country']); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Order Information -->
        <div class="detail-card">
            <div class="detail-card-header">
                <h4><i class="fa-solid fa-info-circle"></i> Order Information</h4>
            </div>
            <div class="detail-card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Order Date</div>
                        <div class="info-value"><?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Order Number</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['order_number']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>