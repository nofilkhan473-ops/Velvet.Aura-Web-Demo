<?php
session_start();
require_once '../backend/config/database.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_vendor'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$vendor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE vendor_id = $user_id"))['total'];
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT order_id) as total FROM vendor_orders WHERE vendor_id = $user_id"))['total'];
$total_earnings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(vendor_earnings) as total FROM vendor_orders WHERE vendor_id = $user_id"))['total'] ?? 0;
$pending_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE vendor_id = $user_id AND is_approved = 0"))['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',sans-serif;background:#0B2E33;color:#fff;}
        .header{background:#0B2E33;padding:15px 0;border-bottom:2px solid #4F7C82;}
        .sidebar{width:260px;background:#0B2E33;height:100vh;position:fixed;border-right:1px solid #4F7C82;padding:20px;}
        .sidebar a{display:flex;align-items:center;gap:12px;padding:12px 15px;margin:8px 0;border-radius:10px;color:#C4A484;text-decoration:none;}
        .sidebar a:hover,.sidebar a.active{background:#4F7C82;color:#fff;}
        .main{margin-left:260px;padding:30px;}
        .stat-card{background:#1a4a4f;border-radius:15px;padding:20px;text-align:center;border:1px solid #4F7C82;}
        .stat-card .num{font-size:32px;font-weight:800;color:#4F7C82;}
        .btn-add{background:#4F7C82;color:#fff;padding:10px 25px;border-radius:50px;text-decoration:none;}
    </style>
</head>
<body>

<div class="header">
    <div class="container d-flex justify-content-between align-items-center">
        <h3><i class="fas fa-store"></i> <?php echo htmlspecialchars($vendor['store_name']); ?></h3>
        <div>Welcome, <?php echo $_SESSION['user_name']; ?> | <a href="../logout.php" style="color:#4F7C82;">Logout</a></div>
    </div>
</div>

<div class="sidebar">
    <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
    <a href="products.php"><i class="fas fa-box"></i> Products</a>
    <a href="add-product.php"><i class="fas fa-plus-circle"></i> Add Product</a>
    <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
</div>

<div class="main">
    <div class="row g-4 mb-4">
        <div class="col-md-3"><div class="stat-card"><div class="num"><?php echo $total_products; ?></div><div>Total Products</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="num"><?php echo $total_orders; ?></div><div>Total Orders</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="num">$<?php echo number_format($total_earnings,2); ?></div><div>Total Earnings</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="num"><?php echo $pending_products; ?></div><div>Pending Approval</div></div></div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center">
        <h4>Quick Actions</h4>
        <a href="add-product.php" class="btn-add"><i class="fas fa-plus"></i> Add New Product</a>
    </div>
</div>
</body>
</html>