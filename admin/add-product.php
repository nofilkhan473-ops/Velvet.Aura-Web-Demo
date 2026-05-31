<?php
$page_title = 'Add Product';
require_once 'includes/header.php';
require_once '../backend/config/database.php';

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
    
    // Handle image upload
    $image_name = 'default.jpg';
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            // Create unique filename
            $image_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
            $upload_path = '../assets/images/' . $image_name;
            
            // Create directory if not exists
            if(!is_dir('../assets/images')) {
                mkdir('../assets/images', 0777, true);
            }
            
            // Move uploaded file
            if(move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Image uploaded successfully
            } else {
                $error = "Failed to upload image. Check folder permissions.";
            }
        } else {
            $error = "Invalid image format. Allowed: jpg, jpeg, png, gif, webp";
        }
    }
    
    // Insert into database (only if no upload error)
    if(!isset($error)) {
        $query = "INSERT INTO products (name, slug, description, price, old_price, category_id, image, stock_quantity, in_stock, is_new, is_bestseller, rating) 
                  VALUES ('$name', '$slug', '$description', $price, " . ($old_price ? $old_price : 'NULL') . ", $category_id, '$image_name', $stock_quantity, $in_stock, $is_new, $is_bestseller, $rating)";
        
        if(mysqli_query($conn, $query)) {
            echo "<script>showNotification('Product added successfully!'); setTimeout(() => { window.location.href = 'products.php'; }, 1500);</script>";
        } else {
            $error = "Database Error: " . mysqli_error($conn);
        }
    }
}
?>

<div class="form-container">
    <h2 style="margin-bottom: 25px;">Add New Product</h2>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 12px; margin-bottom: 20px;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Product Name *</label>
            <input type="text" name="name" required placeholder="e.g., Summer Maxi Dress">
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="4" placeholder="Product description..."></textarea>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Price ($) *</label>
                    <input type="number" step="0.01" name="price" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Old Price ($) (Optional)</label>
                    <input type="number" step="0.01" name="old_price">
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
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Stock Quantity *</label>
                    <input type="number" name="stock_quantity" value="10" required>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label>Product Image</label>
            <input type="file" name="image" accept="image/*">
            <small class="text-muted">Recommended size: 400x500px. Leave empty for default image.</small>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Rating (1-5)</label>
                    <input type="number" step="0.1" name="rating" value="4.5" min="1" max="5">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Features</label>
                    <div style="display: flex; gap: 20px;">
                        <label><input type="checkbox" name="is_new"> New Arrival</label>
                        <label><input type="checkbox" name="is_bestseller"> Best Seller</label>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="products.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
            <button type="submit" class="btn-submit">Add Product →</button>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>