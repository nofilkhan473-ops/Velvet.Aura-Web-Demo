<?php
session_start();
require_once 'backend/config/database.php';
require_once 'backend/includes/functions.php';

if(!isLoggedIn()) {
    header('Location: login.php?redirect=become-vendor.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$check = mysqli_query($conn, "SELECT is_vendor FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($check);

if($user['is_vendor'] == 1) {
    header('Location: vendor/dashboard.php');
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $store_name = mysqli_real_escape_string($conn, $_POST['store_name']);
    $store_description = mysqli_real_escape_string($conn, $_POST['store_description']);
    
    mysqli_query($conn, "UPDATE users SET is_vendor = 1, vendor_status = 'pending', store_name = '$store_name', store_description = '$store_description' WHERE id = $user_id");
    $success = "Application submitted! Admin will review.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Vendor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body{background:#0B2E33;font-family:'Segoe UI',sans-serif;}
        .container{max-width:600px;margin:80px auto;padding:0 20px;}
        .card{background:#1a4a4f;border-radius:25px;padding:35px;border:1px solid #4F7C82;}
        .form-control{background:#0B2E33;border:1px solid #4F7C82;color:#fff;padding:12px;border-radius:10px;}
        .btn-submit{background:#4F7C82;color:#fff;padding:12px;border:none;border-radius:50px;width:100%;font-weight:700;}
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h3><i class="fas fa-store"></i> Become a Vendor</h3>
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3"><input type="text" name="store_name" class="form-control" placeholder="Store Name" required></div>
            <div class="mb-3"><textarea name="store_description" rows="4" class="form-control" placeholder="Store Description" required></textarea></div>
            <button type="submit" class="btn-submit">Submit Application</button>
        </form>
    </div>
</div>
</body>
</html>