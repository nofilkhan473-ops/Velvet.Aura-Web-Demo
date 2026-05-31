<?php
session_start();
require_once '../backend/config/database.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_vendor'])) {
    header('Location: login.php');
    exit();
}

$vendor_id = $_SESSION['user_id'];

$orders = mysqli_query($conn, "SELECT vo.*, o.order_number, o.order_status, o.created_at as order_date 
                               FROM vendor_orders vo 
                               LEFT JOIN orders o ON vo.order_id = o.id 
                               WHERE vo.vendor_id = $vendor_id 
                               ORDER BY vo.created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Vendor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body{background:#0B2E33;font-family:'Segoe UI',sans-serif;}
        .sidebar{width:260px;background:#0B2E33;height:100vh;position:fixed;border-right:1px solid #4F7C82;padding:20px;}
        .sidebar a{display:block;padding:12px 15px;margin:8px 0;border-radius:10px;color:#C4A484;text-decoration:none;}
        .sidebar a:hover,.sidebar a.active{background:#4F7C82;color:#fff;}
        .main{margin-left:260px;padding:30px;}
        .table-custom{background:#1a4a4f;border-radius:15px;overflow:hidden;width:100%;}
        .table-custom th{background:#1c4950;color:#a6e5ee;padding:15px;}
        .table-custom td{padding:15px;border-bottom:1px solid #4F7C82;color:#fff;}
        h4{color:#88e4f0;}
        .no-orders{text-align:center;padding:40px;color:#C4A484;}
    </style>
</head>
<body>

<div class="sidebar">
    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="products.php"><i class="fas fa-box"></i> Products</a>
    <a href="add-product.php"><i class="fas fa-plus-circle"></i> Add Product</a>
    <a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i> Orders</a>
    <a href="withdrawals.php"><i class="fas fa-wallet"></i> Withdrawals</a>
    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <h4><i class="fas fa-shopping-cart"></i> My Orders</h4>
    
    <?php if(mysqli_num_rows($orders) > 0): ?>
    <table class="table-custom mt-3">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Order #</th>
                <th>Product ID</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>My Earnings</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php while($o = mysqli_fetch_assoc($orders)): ?>
            <tr>
                <td>#<?php echo $o['order_id']; ?></td>
                <td><?php echo $o['order_number']; ?></td>
                <td><?php echo $o['product_id']; ?></td>
                <td><?php echo $o['quantity']; ?></td>
                <td>$<?php echo number_format($o['product_price'],2); ?></td>
                <td>$<?php echo number_format($o['vendor_earnings'],2); ?></td>
                <td><span class="badge bg-info"><?php echo $o['order_status'] ?? 'Pending'; ?></span></td>
                <td><?php echo date('d M Y', strtotime($o['order_date'] ?? $o['created_at'])); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div class="no-orders">
            <i class="fas fa-box-open fa-3x mb-3"></i>
            <p>No orders found yet.</p>
            <small>When customers order your products, they will appear here.</small>
        </div>
    <?php endif; ?>
</div>
</body>
</html>