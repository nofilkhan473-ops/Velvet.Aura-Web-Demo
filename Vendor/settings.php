<?php
session_start();
require_once '../backend/config/database.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_vendor'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(!empty($_POST['new_password'])) {
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password = '$new_password' WHERE id = $user_id");
        $success = "Password changed successfully!";
    }
    
    if(isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, ['jpg','jpeg','png'])) {
            $logo = time() . '_' . $_FILES['logo']['name'];
            move_uploaded_file($_FILES['logo']['tmp_name'], '../assets/vendors/' . $logo);
            mysqli_query($conn, "UPDATE users SET store_logo = '$logo' WHERE id = $user_id");
            $success = "Store logo updated!";
        }
    }
}

$vendor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Vendor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body{background:#0B2E33;font-family:'Segoe UI',sans-serif;}
        .sidebar{width:260px;background:#0B2E33;height:100vh;position:fixed;border-right:1px solid #4F7C82;padding:20px;}
        .sidebar a{display:block;padding:12px 15px;margin:8px 0;border-radius:10px;color:#C4A484;text-decoration:none;}
        .sidebar a:hover,.sidebar a.active{background:#4F7C82;color:#fff;}
        .main{margin-left:260px;padding:30px;}
        .card{background:#1a4a4f;border-radius:20px;padding:25px;margin-bottom:25px;border:1px solid #4F7C82;}
        .form-control{background:#0B2E33;border:1px solid #4F7C82;color:#fff;padding:12px;border-radius:10px;}
        .btn-save{background:#4F7C82;color:#fff;padding:10px 25px;border:none;border-radius:50px;}
        h4{
            color: #9ddae2;
        }
        p{
            color:white;
        }
        h5{
            color:#C4A484;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="products.php"><i class="fas fa-box"></i> Products</a>
    <a href="add-product.php"><i class="fas fa-plus-circle"></i> Add Product</a>
    <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
    <a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <h4><i class="fas fa-cog"></i> Store Settings</h4>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <h5><i class="fas fa-store"></i> Store Information</h5>
        <p><strong>Store Name:</strong> <?php echo htmlspecialchars($vendor['store_name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($vendor['email']); ?></p>
        <p><strong>Balance:</strong> $<?php echo number_format($vendor['balance'],2); ?></p>
    </div>
    
    <div class="card">
        <h5><i class="fas fa-key"></i> Change Password</h5>
        <form method="POST">
            <div class="mb-3"><input type="password" name="new_password" class="form-control" placeholder="New Password" required></div>
            <button type="submit" class="btn-save"><i class="fas fa-save"></i> Update Password</button>
        </form>
    </div>
</div>
</body>
</html>