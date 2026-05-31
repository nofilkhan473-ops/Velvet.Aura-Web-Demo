<?php
session_start();
require_once '../backend/config/database.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id']) || $_SESSION['admin_id'] != 1) {
    header('Location: login.php');
    exit();
}

// Approve vendor
if(isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    mysqli_query($conn, "UPDATE users SET vendor_status = 'approved' WHERE id = $id");
    header('Location: vendors.php');
}

// Delete vendor
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM users WHERE id = $id");
    header('Location: vendors.php');
}

// Reset password to default
if(isset($_GET['reset'])) {
    $id = (int)$_GET['reset'];
    $new_pass = password_hash('vendor123', PASSWORD_DEFAULT);
    mysqli_query($conn, "UPDATE users SET password = '$new_pass' WHERE id = $id");
    header('Location: vendors.php?msg=Password reset to vendor123');
}

$vendors = mysqli_query($conn, "SELECT * FROM users WHERE is_vendor = 1 ORDER BY created_at DESC");
$total_vendors = mysqli_num_rows($vendors);
$approved = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE is_vendor=1 AND vendor_status='approved'"));
$pending = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE is_vendor=1 AND vendor_status='pending'"));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Vendors - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:#f0f2f8;font-family:'Segoe UI',sans-serif;}
        .container{max-width:1400px;margin:30px auto;padding:0 20px;}
        .header{background:white;border-radius:20px;padding:20px 25px;margin-bottom:25px;box-shadow:0 2px 8px rgba(0,0,0,0.04);display:flex;justify-content:space-between;align-items:center;}
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
        .btn-reset{background:#f59e0b;color:white;}
        .btn-delete{background:#ef4444;color:white;}
        .btn-create{background:#517a96;color:white;padding:10px 20px;border-radius:30px;text-decoration:none;}
        .alert-success{background:#d1fae5;color:#059669;padding:12px 18px;border-radius:12px;margin-bottom:20px;}
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2><i class="fas fa-store"></i> All Vendors</h2>
        <a href="create-vendor.php" class="btn-create"><i class="fas fa-plus"></i> Create New Vendor</a>
    </div>
    
    <?php if(isset($_GET['msg'])): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $_GET['msg']; ?></div>
    <?php endif; ?>
    
    <div class="stats">
        <div class="stat-card"><div class="number"><?php echo $total_vendors; ?></div><div class="label">Total Vendors</div></div>
        <div class="stat-card"><div class="number"><?php echo $approved; ?></div><div class="label">Approved</div></div>
        <div class="stat-card"><div class="number"><?php echo $pending; ?></div><div class="label">Pending</div></div>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr><th>Store Name</th><th>Owner Name</th><th>Email</th><th>Status</th><th>Balance</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($vendors) > 0): ?>
                    <?php while($v = mysqli_fetch_assoc($vendors)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($v['store_name'] ?? 'N/A'); ?></strong></td>
                        <td><?php echo htmlspecialchars($v['name']); ?></td>
                        <td><?php echo htmlspecialchars($v['email']); ?></td>
                        <td><span class="<?php echo $v['vendor_status']=='approved'?'badge-approved':'badge-pending'; ?>"><?php echo ucfirst($v['vendor_status']); ?></span></td>
                        <td>$<?php echo number_format($v['balance'] ?? 0, 2); ?></td>
                        <td>
                            <?php if($v['vendor_status'] != 'approved'): ?>
                                <a href="?approve=<?php echo $v['id']; ?>" class="btn-sm btn-approve" onclick="return confirm('Approve this vendor?')"><i class="fas fa-check"></i> Approve</a>
                            <?php endif; ?>
                            <a href="?reset=<?php echo $v['id']; ?>" class="btn-sm btn-reset" onclick="return confirm('Reset password to vendor123?')"><i class="fas fa-key"></i> Reset</a>
                            <a href="?delete=<?php echo $v['id']; ?>" class="btn-sm btn-delete" onclick="return confirm('Delete this vendor permanently?')"><i class="fas fa-trash"></i> Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center; padding:40px;">No vendors found. <a href="create-vendor.php">Create your first vendor</a></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>