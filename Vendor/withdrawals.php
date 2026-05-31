<?php
session_start();
require_once '../backend/config/database.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_vendor'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$vendor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = (float)$_POST['amount'];
    if($amount <= 0) {
        $error = "Invalid amount!";
    } elseif($amount > $vendor['balance']) {
        $error = "Insufficient balance!";
    } else {
        mysqli_query($conn, "INSERT INTO vendor_withdrawals (vendor_id, amount) VALUES ($user_id, $amount)");
        mysqli_query($conn, "UPDATE users SET balance = balance - $amount WHERE id = $user_id");
        $success = "Withdrawal request submitted!";
        $vendor['balance'] -= $amount;
    }
}

$withdrawals = mysqli_query($conn, "SELECT * FROM vendor_withdrawals WHERE vendor_id = $user_id ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawals - Vendor</title>
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
        .btn-submit{background:#4F7C82;color:#fff;padding:12px;border:none;border-radius:50px;width:100%;font-weight:700;}
        .table-custom{background:#1a4a4f;border-radius:15px;overflow:hidden;width:100%;}
        .table-custom th{background:#0B2E33;padding:15px;}
        .table-custom td{padding:15px;border-bottom:1px solid #4F7C82;}
        .badge-pending{background:#f59e0b;padding:4px 12px;border-radius:20px;font-size:12px;}
        .badge-approved{background:#10b981;padding:4px 12px;border-radius:20px;font-size:12px;}
        .balance{font-size:36px;font-weight:800;color:#4F7C82;}
    </style>
</head>
<body>

<div class="sidebar">
    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="products.php"><i class="fas fa-box"></i> Products</a>
    <a href="add-product.php"><i class="fas fa-plus-circle"></i> Add Product</a>
    <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
    <a href="withdrawals.php" class="active"><i class="fas fa-wallet"></i> Withdrawals</a>
    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <div class="card text-center">
        <h5>Available Balance</h5>
        <div class="balance">$<?php echo number_format($vendor['balance'],2); ?></div>
    </div>
    
    <div class="row">
        <div class="col-md-5">
            <div class="card">
                <h5><i class="fas fa-hand-holding-usd"></i> Request Withdrawal</h5>
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Enter amount" required></div>
                    <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Request Withdrawal</button>
                </form>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card">
                <h5><i class="fas fa-history"></i> Withdrawal History</h5>
                <table class="table-custom">
                    <thead><tr><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php while($w = mysqli_fetch_assoc($withdrawals)): ?>
                        <tr>
                            <td>$<?php echo number_format($w['amount'],2); ?></td>
                            <td><span class="<?php echo $w['status']=='approved'?'badge-approved':'badge-pending'; ?>"><?php echo ucfirst($w['status']); ?></span></td>
                            <td><?php echo date('d M Y', strtotime($w['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>