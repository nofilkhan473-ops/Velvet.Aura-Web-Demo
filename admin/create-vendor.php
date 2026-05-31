<?php
session_start();
require_once '../backend/config/database.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $store_name = mysqli_real_escape_string($conn, $_POST['store_name']);
    
    // Admin se password input le rahe hain
    $custom_password = $_POST['password'];
    if(empty($custom_password)) {
        $custom_password = 'vendor123'; // default agar admin ne nahi bhara to
    }
    $hashed_password = password_hash($custom_password, PASSWORD_DEFAULT);
    
    // Check if email exists
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    if(mysqli_num_rows($check) > 0) {
        $error = "Email already exists!";
    } else {
        $query = "INSERT INTO users (name, email, password, is_vendor, vendor_status, store_name) 
                  VALUES ('$name', '$email', '$hashed_password', 1, 'approved', '$store_name')";
        
        if(mysqli_query($conn, $query)) {
            $success = "✅ Vendor created successfully!<br>
                        📧 Email: $email<br>
                        🔑 Password: $custom_password<br>
                        🏪 Store: $store_name<br>
                        <small class='text-muted'>⚠️ Share this password with vendor. They can change it after login.</small>";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Vendor - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body{background:#f0f2f8;font-family:'Segoe UI',sans-serif;}
        .container{max-width:550px;margin:60px auto;}
        .card{background:white;border-radius:20px;padding:35px;box-shadow:0 10px 30px rgba(0,0,0,0.08);}
        .card h2{color:#1e293b;margin-bottom:10px;font-size:24px;}
        .card h2 i{color:#517a96;margin-right:10px;}
        .form-control{padding:12px 15px;border-radius:12px;border:1px solid #e2e8f0;}
        .form-control:focus{border-color:#517a96;box-shadow:0 0 0 3px rgba(81,122,150,0.1);}
        .btn-submit{background:linear-gradient(135deg,#517a96,#3a6b85);color:white;padding:12px;border:none;border-radius:50px;width:100%;font-weight:600;}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(81,122,150,0.3);}
        .alert-success{background:#d1fae5;color:#059669;padding:15px;border-radius:12px;border-left:4px solid #059669;}
        .alert-danger{background:#fee2e2;color:#dc2626;padding:15px;border-radius:12px;border-left:4px solid #dc2626;}
        .info-box{background:#f1f5f9;padding:12px;border-radius:10px;margin-bottom:20px;font-size:13px;color:#475569;}
        .info-box i{color:#517a96;margin-right:8px;}
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h2><i class="fa-solid fa-user-plus"></i> Create Vendor</h2>
        
        <div class="info-box">
            <i class="fa-solid fa-info-circle"></i> You can set any password for vendor. They can change it later.
        </div>
        
        <?php if($success): ?>
            <div class="alert-success">
                <i class="fa-solid fa-check-circle"></i> <?php echo $success; ?>
            </div>
            <a href="vendors.php" class="btn btn-primary w-100 mt-3" style="background:#517a96; border:none; padding:10px; border-radius:50px;">View All Vendors</a>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert-danger">
                <i class="fa-solid fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if(!$success): ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" class="form-control" placeholder="e.g., Rajesh Kumar" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email Address *</label>
                <input type="email" name="email" class="form-control" placeholder="vendor@example.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Store Name *</label>
                <input type="text" name="store_name" class="form-control" placeholder="e.g., Raj Fashion Store" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Set Password *</label>
                <input type="text" name="password" class="form-control" placeholder="e.g., Raj@123 or leave empty for default" value="vendor123">
                <small class="text-muted">Default: vendor123 | You can set any password</small>
            </div>
            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-check"></i> Create Vendor
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>