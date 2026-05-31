<?php
session_start();
require_once '../backend/config/database.php';

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Update order status
if(isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['order_status'];
    $tracking_number = mysqli_real_escape_string($conn, $_POST['tracking_number']);
    
    $update_query = "UPDATE orders SET order_status = '$status', tracking_number = '$tracking_number', updated_at = NOW() WHERE id = $order_id";
    mysqli_query($conn, $update_query);
    
    header('Location: orders.php?msg=updated');
    exit();
}

// Get order details
$order_id = (int)$_GET['id'];
$order_query = "SELECT o.*, u.username, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = $order_id";
$order_result = mysqli_query($conn, $order_query);
$order = mysqli_fetch_assoc($order_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Order - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Update Order #<?php echo $order['order_number']; ?></h2>
    
    <form method="POST" class="mt-4">
        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
        
        <div class="mb-3">
            <label>Order Status</label>
            <select name="order_status" class="form-control" required>
                <option value="pending" <?php echo $order['order_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="processing" <?php echo $order['order_status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                <option value="shipped" <?php echo $order['order_status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                <option value="delivered" <?php echo $order['order_status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="cancelled" <?php echo $order['order_status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        
        <div class="mb-3">
            <label>Tracking Number (Optional)</label>
            <input type="text" name="tracking_number" class="form-control" value="<?php echo $order['tracking_number']; ?>">
        </div>
        
        <button type="submit" name="update_status" class="btn btn-primary">Update Order</button>
        <a href="orders.php" class="btn btn-secondary">Back</a>
    </form>
</div>
</body>
</html>