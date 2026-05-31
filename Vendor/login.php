<?php
session_start();
require_once '../backend/config/database.php';

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE email = '$email' AND is_vendor = 1";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if(password_verify($password, $user['password'])) {
            if($user['vendor_status'] == 'approved') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['is_vendor'] = true;
                header('Location: dashboard.php');
                exit();
            } else {
                $error = "Account pending approval!";
            }
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Vendor account not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Login - Velvet Aura</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body{background:#0B2E33;font-family:'Segoe UI',sans-serif;height:100vh;display:flex;align-items:center;justify-content:center;}
        .login-card{background:#1a4a4f;border-radius:25px;padding:40px;width:100%;max-width:400px;border:1px solid #4F7C82;}
        .login-card h2{color:#fff;margin-bottom:10px;}
        .form-control{background:#0B2E33;border:1px solid #4F7C82;color:#fff;padding:12px;border-radius:10px;}
        .btn-login{background:#4F7C82;color:#fff;padding:12px;border:none;border-radius:50px;width:100%;font-weight:700;}
        .alert-danger{background:#ef4444;color:#fff;padding:12px;border-radius:10px;}
    </style>
</head>
<body>
    <div class="login-card">
        <h2><i class="fas fa-store"></i> Vendor Login</h2>
        <p style="color:#C4A484;">Login to manage your store</p>
        <?php if($error): ?>
            <div class="alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3"><input type="email" name="email" class="form-control" placeholder="Email Address" required></div>
            <div class="mb-3"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
            <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Login</button>
        </form>
        <p class="text-center mt-3"><a href="../index.php" style="color:#4F7C82;">← Back to Website</a></p>
    </div>
</body>
</html>