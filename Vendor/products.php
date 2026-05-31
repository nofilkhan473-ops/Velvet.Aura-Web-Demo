<?php
session_start();
require_once '../backend/config/database.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_vendor'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$products = mysqli_query($conn, "SELECT * FROM products WHERE vendor_id = $user_id ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - Vendor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body{background: #0B2E33;font-family:'Segoe UI',sans-serif;}
        .sidebar{width:260px;background:#0B2E33;height:100vh;position:fixed;border-right:1px solid #4F7C82;padding:20px;}
        .sidebar a{display:block;padding:12px 15px;margin:8px 0;border-radius:10px;color:#C4A484;text-decoration:none;}
        .sidebar a:hover,.sidebar a.active{background:#4F7C82;color:#fff;}
        .main{margin-left:260px;padding:30px;}
        .table-custom{background:#1a4a4f;border-radius:15px;overflow:hidden;width:100%;}
        .table-custom th{background:  #4da6b1;padding:15px;}
        .table-custom td{padding:15px;border-bottom:1px solid #9ac7cd; color: white;}
        .badge-approved{background:#10b981;padding:4px 12px;border-radius:20px;font-size:12px;}
        .badge-pending{background:#f59e0b;padding:4px 12px;border-radius:20px;font-size:12px;}
        .btn-edit{background:#D4A574;color:#0B2E33;padding:5px 15px;border-radius:20px;text-decoration:none;font-size:12px;}
        h4{
             color: #7dd1dd;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="products.php" class="active"><i class="fas fa-box"></i> Products</a>
    <a href="add-product.php"><i class="fas fa-plus-circle"></i> Add Product</a>
    <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <h4><i class="fas fa-box"></i> My Products</h4>
    <table class="table-custom mt-3">
        <thead><tr><th>Product</th><th>Price</th><th>Stock</th><th>Status</th><th>Approval</th><th>Action</th></tr></thead>
        <tbody>
            <?php while($p = mysqli_fetch_assoc($products)): ?>
            <tr>
                <td><?php echo htmlspecialchars($p['name']); ?></td>
                <td>$<?php echo number_format($p['price'],2); ?></td>
                <td><?php echo $p['stock_quantity'] ?? 0; ?></td>
                <td><?php echo $p['in_stock'] ? 'In Stock' : 'Out'; ?></td>
                <td><span class="<?php echo $p['is_approved']?'badge-approved':'badge-pending'; ?>"><?php echo $p['is_approved']?'Approved':'Pending'; ?></span></td>
                <td><a href="edit-product.php?id=<?php echo $p['id']; ?>" class="btn-edit">Edit</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>