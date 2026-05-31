<?php
session_start();
require_once '../backend/config/database.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id']) || $_SESSION['admin_id'] != 1) {
    header('Location: login.php');
    exit();
}

// Approve product
if(isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    mysqli_query($conn, "UPDATE products SET is_approved = 1 WHERE id = $id");
    header('Location: vendor-products.php');
}

// Delete product
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM products WHERE id = $id");
    header('Location: vendor-products.php');
}

$products = mysqli_query($conn, "SELECT p.*, u.store_name, u.name as vendor_name 
                                 FROM products p 
                                 LEFT JOIN users u ON p.vendor_id = u.id 
                                 WHERE p.vendor_id IS NOT NULL 
                                 ORDER BY p.created_at DESC");

$total_products = mysqli_num_rows($products);
$pending_products = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM products WHERE is_approved = 0 AND vendor_id IS NOT NULL"));
$approved_products = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM products WHERE is_approved = 1 AND vendor_id IS NOT NULL"));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Products - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:#f0f2f8;font-family:'Segoe UI',sans-serif;}
        .container{max-width:1400px;margin:30px auto;padding:0 20px;}
        .header{background:white;border-radius:20px;padding:20px 25px;margin-bottom:25px;box-shadow:0 2px 8px rgba(0,0,0,0.04);}
        .header h2{color:#1e293b;font-weight:700;}
        .stats{display:flex;gap:20px;margin-bottom:25px;flex-wrap:wrap;}
        .stat-card{background:white;border-radius:16px;padding:20px;flex:1;min-width:150px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.04);}
        .stat-card .number{font-size:32px;font-weight:800;color:#517a96;}
        .stat-card .label{color:#64748b;font-size:13px;}
        .table-container{background:white;border-radius:20px;overflow:auto;box-shadow:0 2px 8px rgba(0,0,0,0.04);}
        table{width:100%;border-collapse:collapse;}
        th{background:#f8fafc;padding:15px;text-align:left;font-weight:600;color:#475569;border-bottom:1px solid #e2e8f0;}
        td{padding:15px;border-bottom:1px solid #e2e8f0;color:#334155;}
        .badge-approved{background:#d1fae5;color:#059669;padding:4px 12px;border-radius:30px;font-size:12px;display:inline-block;}
        .badge-pending{background:#fef3c7;color:#d97706;padding:4px 12px;border-radius:30px;font-size:12px;display:inline-block;}
        .btn-sm{padding:5px 12px;border-radius:20px;font-size:12px;text-decoration:none;margin:2px;display:inline-block;}
        .btn-approve{background:#10b981;color:white;}
        .btn-delete{background:#ef4444;color:white;}
        .product-img{width:50px;height:50px;object-fit:cover;border-radius:10px;background:#f1f5f9;}
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2><i class="fas fa-box"></i> Vendor Products</h2>
        <p style="color:#64748b; margin-top:5px;">Approve or reject products added by vendors</p>
    </div>
    
    <div class="stats">
        <div class="stat-card"><div class="number"><?php echo $total_products; ?></div><div class="label">Total Products</div></div>
        <div class="stat-card"><div class="number"><?php echo $pending_products; ?></div><div class="label">Pending Approval</div></div>
        <div class="stat-card"><div class="number"><?php echo $approved_products; ?></div><div class="label">Approved</div></div>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr><th>Image</th><th>Product Name</th><th>Vendor</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($products) > 0): ?>
                    <?php while($p = mysqli_fetch_assoc($products)): ?>
                    <tr>
                        <td><img src="../assets/images/<?php echo $p['image']; ?>" class="product-img" onerror="this.src='https://placehold.co/50x50?text=No+Image'"></td>
                        <td><strong><?php echo htmlspecialchars($p['name']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars(substr($p['description'], 0, 40)); ?>...</small></td>
                        <td><?php echo htmlspecialchars($p['store_name'] ?? $p['vendor_name']); ?></td>
                        <td>$<?php echo number_format($p['price'], 2); ?></td>
                        <td><?php echo $p['stock_quantity'] ?? 0; ?></td>
                        <td><span class="<?php echo $p['is_approved']?'badge-approved':'badge-pending'; ?>"><?php echo $p['is_approved']?'Approved':'Pending'; ?></span></td>
                        <td>
                            <?php if(!$p['is_approved']): ?>
                                <a href="?approve=<?php echo $p['id']; ?>" class="btn-sm btn-approve" onclick="return confirm('Approve this product? It will be visible on website.')"><i class="fas fa-check"></i> Approve</a>
                            <?php endif; ?>
                            <a href="?delete=<?php echo $p['id']; ?>" class="btn-sm btn-delete" onclick="return confirm('Delete this product permanently?')"><i class="fas fa-trash"></i> Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center; padding:40px;">No vendor products found. Products added by vendors will appear here.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>