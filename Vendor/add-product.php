<?php
session_start();
require_once '../backend/config/database.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_vendor'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$categories = mysqli_query($conn, "SELECT * FROM categories WHERE is_active = 1");

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = (float)$_POST['price'];
    $category_id = (int)$_POST['category_id'];
    $stock = (int)$_POST['stock'];
    
    if(!is_dir('../assets/images')) mkdir('../assets/images', 0777, true);
    
    $image = '';
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $image = time() . '_' . $_FILES['image']['name'];
            move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/' . $image);
        }
    }
    
    $query = "INSERT INTO products (name, description, price, category_id, vendor_id, stock_quantity, image, in_stock, is_approved) 
              VALUES ('$name', '$description', $price, $category_id, $user_id, $stock, '$image', 1, 0)";
    
    if(mysqli_query($conn, $query)) {
        $success = "Product added! Waiting for admin approval.";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Vendor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body{background: #0B2E33;font-family:'Segoe UI',sans-serif;}
        .container{max-width:700px;margin:50px auto;padding:0 20px;}
        .card{background: #043439;border-radius:20px;padding:35px;border:1px solid #2d737d;}
        .form-control{background: #1d565f;border:1px solid #4F7C82;color:#fff;padding:12px;border-radius:10px;}
        .form-control:focus{background:#0B2E33;color:#fff;border-color:#D4A574;}
        .btn-submit{background:#4F7C82;color:#fff;padding:12px;border:none;border-radius:50px;width:100%;font-weight:700;}
        .alert-success{background:#10b981;color:#fff;padding:15px;border-radius:10px;}
        .alert-danger{background:#ef4444;color:#fff;padding:15px;border-radius:10px;}
        label{
            color:white;
        }
        h3{
            color: #4abcce;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <h3><i class="fas fa-plus-circle"></i> Add New Product</h3>
        <p style="color:#C4A484;">Product will be visible after admin approval</p>
        
        <?php if($success): ?>
            <div class="alert-success"><?php echo $success; ?> <a href="products.php" style="color:#fff;">View Products</a></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3"><label>Product Name</label><input type="text" name="name" class="form-control" required></div>
            <div class="mb-3"><label>Description</label><textarea name="description" rows="4" class="form-control" required></textarea></div>
            <div class="row">
                <div class="col-md-6"><div class="mb-3"><label>Price</label><input type="number" step="0.01" name="price" class="form-control" required></div></div>
                <div class="col-md-6"><div class="mb-3"><label>Stock</label><input type="number" name="stock" class="form-control" value="10" required></div></div>
            </div>
            <div class="mb-3"><label>Category</label><select name="category_id" class="form-control" required><option value="">Select</option><?php while($c=mysqli_fetch_assoc($categories)): ?><option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option><?php endwhile; ?></select></div>
            <div class="mb-3"><label>Product Image</label><input type="file" name="image" class="form-control" accept="image/*" required></div>
            <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Add Product</button>
            <a href="dashboard.php" class="btn btn-link mt-3 d-block text-center" style="color:#4F7C82;">Back to Dashboard</a>
        </form>
    </div>
</div>
</body>
</html>