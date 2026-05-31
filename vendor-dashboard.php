<?php
session_start();
require_once 'backend/config/database.php';
require_once 'backend/includes/functions.php';

if(!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if user is vendor
$vendor_check = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id AND is_vendor = 1 AND vendor_status = 'approved'");
if(mysqli_num_rows($vendor_check) == 0) {
    header('Location: become-vendor.php');
    exit();
}

$vendor = mysqli_fetch_assoc($vendor_check);

// Get stats
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE vendor_id = $user_id"))['total'];
$total_sales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(vendor_earnings) as total FROM vendor_orders WHERE vendor_id = $user_id AND is_settled = 1"))['total'] ?? 0;
$pending_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT order_id) as total FROM vendor_orders WHERE vendor_id = $user_id AND is_settled = 0"))['total'] ?? 0;

// Get recent products
$recent_products = mysqli_query($conn, "SELECT * FROM products WHERE vendor_id = $user_id ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard - Velvet Aura</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0F0A08; color: #F5E6D3; }
        
        .dashboard-header {
            background: linear-gradient(135deg, #1A0F08, #2C1810);
            padding: 30px 0;
            border-bottom: 1px solid rgba(212,165,116,0.2);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin: 40px 0;
        }
        
        .stat-card {
            background: #3D2314;
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(212,165,116,0.15);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: #D4A574;
        }
        
        .stat-card i {
            font-size: 40px;
            color: #D4A574;
            margin-bottom: 15px;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: 800;
            color: #F5E6D3;
        }
        
        .stat-card .label {
            color: #C4A484;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
            margin: 30px 0 20px;
            color: #F5E6D3;
            border-left: 4px solid #D4A574;
            padding-left: 15px;
        }
        
        .product-table {
            width: 100%;
            background: #3D2314;
            border-radius: 20px;
            overflow: hidden;
        }
        
        .product-table th {
            background: #2C1810;
            padding: 15px;
            text-align: left;
            color: #D4B5A7;
            font-weight: 600;
        }
        
        .product-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(212,165,116,0.1);
            color: #C4A484;
        }
        
        .btn-add-product {
            background: linear-gradient(135deg, #D4A574, #C4956A);
            color: #2C1810;
            padding: 12px 25px;
            border: none;
            border-radius: 50px;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-add-product:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(212,165,116,0.4);
            color: #2C1810;
        }
        
        .btn-edit {
            background: #5C2E1A;
            color: #F5E6D3;
            padding: 5px 12px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 12px;
        }
        
        .btn-edit:hover {
            background: #D4A574;
            color: #2C1810;
        }
    </style>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar" style="background: linear-gradient(135deg, #5C2E1A, #8B4513); padding: 8px 0; text-align: center; font-size: 11px;">
    ✨ VENDOR DASHBOARD ✦ MANAGE YOUR STORE ✦ TRACK SALES ✨
</div>

<!-- Navigation -->
<nav style="background: rgba(61, 35, 20, 0.95); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(92,46,26,0.3); padding: 15px 0;">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
        <a href="index.php" style="font-family: 'Inter', sans-serif; font-size: 22px; font-weight: 800; letter-spacing: 3px; color: #D4B5A7; text-decoration: none;">VELVET<span style="color: #F5E6D3;">.</span>AURA</a>
        <div style="display: flex; gap: 20px;">
            <a href="vendor-add-product.php" class="btn-add-product" style="padding: 8px 20px; font-size: 14px;"><i class="fas fa-plus"></i> Add Product</a>
            <a href="index.php" style="color: #D4B5A7; text-decoration: none;"><i class="fas fa-home"></i> Home</a>
            <a href="logout.php" style="color: #D4B5A7; text-decoration: none;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</nav>

<!-- Dashboard Header -->
<section class="dashboard-header">
    <div class="container">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="width: 80px; height: 80px; background: #3D2314; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #D4A574;">
                <i class="fas fa-store" style="font-size: 40px; color: #D4A574;"></i>
            </div>
            <div>
                <h1 style="margin: 0;"><?php echo htmlspecialchars($vendor['store_name']); ?></h1>
                <p style="color: #C4A484; margin: 5px 0 0;">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
            </div>
        </div>
    </div>
</section>

<!-- Stats -->
<div class="container">
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-box"></i>
            <div class="number"><?php echo $total_products; ?></div>
            <div class="label">Total Products</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-dollar-sign"></i>
            <div class="number">$<?php echo number_format($total_sales, 2); ?></div>
            <div class="label">Total Earnings</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-shopping-cart"></i>
            <div class="number"><?php echo $pending_orders; ?></div>
            <div class="label">Pending Orders</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-chart-line"></i>
            <div class="number">--</div>
            <div class="label">This Month Sales</div>
        </div>
    </div>
</div>

<!-- Recent Products -->
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="section-title"><i class="fas fa-box"></i> Your Products</h2>
        <a href="vendor-add-product.php" class="btn-add-product"><i class="fas fa-plus"></i> Add New Product</a>
    </div>
    
    <table class="product-table">
        <thead>
            <tr><th>Product</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php if(mysqli_num_rows($recent_products) > 0): ?>
                <?php while($product = mysqli_fetch_assoc($recent_products)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                    <td><?php echo $product['stock_quantity'] ?? 0; ?></td>
                    <td><?php echo $product['in_stock'] ? '✅ In Stock' : '❌ Out of Stock'; ?></td>
                    <td><a href="vendor-edit-product.php?id=<?php echo $product['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align: center;">No products yet. <a href="vendor-add-product.php" style="color: #D4A574;">Add your first product</a></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<br><br>
</body>
</html>