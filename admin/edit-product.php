<?php
$page_title = 'Edit Product';
require_once 'includes/header.php';
require_once '../backend/config/database.php';

$id = (int)$_GET['id'];
$product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id = $id"));

if(!$product) {
    header('Location: products.php');
    exit();
}

$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $slug = strtolower(str_replace(' ', '-', $name));
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = (float)$_POST['price'];
    $old_price = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : NULL;
    $category_id = (int)$_POST['category_id'];
    $stock_quantity = (int)$_POST['stock_quantity'];
    $is_new = isset($_POST['is_new']) ? 1 : 0;
    $is_bestseller = isset($_POST['is_bestseller']) ? 1 : 0;
    $rating = (float)$_POST['rating'];
    $in_stock = $stock_quantity > 0 ? 1 : 0;
    
    $image_name = $product['image'];
    
    // Handle new image upload
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            // Delete old image
            if($product['image'] && file_exists("../assets/images/" . $product['image'])) {
                unlink("../assets/images/" . $product['image']);
            }
            
            $image_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
            move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/' . $image_name);
        }
    }
    
    $query = "UPDATE products SET 
              name='$name', slug='$slug', description='$description', price=$price, 
              old_price=" . ($old_price ? $old_price : 'NULL') . ", category_id=$category_id, 
              image='$image_name', stock_quantity=$stock_quantity, in_stock=$in_stock, 
              is_new=$is_new, is_bestseller=$is_bestseller, rating=$rating 
              WHERE id=$id";
    
    if(mysqli_query($conn, $query)) {
        echo "<script>showNotification('Product updated successfully!'); setTimeout(() => { window.location.href = 'products.php'; }, 1500);</script>";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>

<div class="form-container">
    <h2 style="margin-bottom: 25px;">Edit Product</h2>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Product Name *</label>
            <input type="text" name="name" required value="<?php echo htmlspecialchars($product['name']); ?>">
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Price ($) *</label>
                    <input type="number" step="0.01" name="price" required value="<?php echo $product['price']; ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Old Price ($)</label>
                    <input type="number" step="0.01" name="old_price" value="<?php echo $product['old_price']; ?>">
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Category *</label>
                    <select name="category_id" required>
                        <option value="">Select Category</option>
                        <?php while($cat = mysqli_fetch_assoc($categories)): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo $cat['name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Stock Quantity *</label>
                    <input type="number" name="stock_quantity" value="<?php echo $product['stock_quantity']; ?>" required>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label>Product Image</label>
            <input type="file" name="image" accept="image/*">
            <?php if($product['image']): ?>
                <div style="margin-top: 10px;">
                    <img src="../assets/images/<?php echo $product['image']; ?>" style="width: 80px; border-radius: 10px;">
                    <small class="text-muted d-block">Current image (upload new to replace)</small>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Rating (1-5)</label>
                    <input type="number" step="0.1" name="rating" value="<?php echo $product['rating']; ?>" min="1" max="5">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Features</label>
                    <div style="display: flex; gap: 20px;">
                        <label><input type="checkbox" name="is_new" <?php echo $product['is_new'] ? 'checked' : ''; ?>> New Arrival</label>
                        <label><input type="checkbox" name="is_bestseller" <?php echo $product['is_bestseller'] ? 'checked' : ''; ?>> Best Seller</label>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="products.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
            <button type="submit" class="btn-submit">Update Product →</button>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>