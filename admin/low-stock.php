<?php
$page_title = 'Low Stock Products';
require_once 'includes/header.php';
require_once '../backend/config/database.php';

// Handle stock update
if(isset($_POST['update_stock'])) {
    $product_id = (int)$_POST['product_id'];
    $new_quantity = (int)$_POST['stock_quantity'];
    $in_stock = $new_quantity > 0 ? 1 : 0;
    
    $update_query = "UPDATE products SET stock_quantity = $new_quantity, in_stock = $in_stock WHERE id = $product_id";
    if(mysqli_query($conn, $update_query)) {
        echo "<script>showNotification('Stock updated successfully!'); window.location.href='low-stock.php';</script>";
    } else {
        echo "<script>showNotification('Update failed!', 'error');</script>";
    }
}

// Get all low stock products (quantity < 10)
$low_stock_query = "SELECT p.*, c.name as category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.stock_quantity < 10 AND p.stock_quantity > 0
                    ORDER BY p.stock_quantity ASC";
$low_stock_result = mysqli_query($conn, $low_stock_query);

// Get out of stock products
$out_of_stock_query = "SELECT p.*, c.name as category_name 
                       FROM products p 
                       LEFT JOIN categories c ON p.category_id = c.id 
                       WHERE p.stock_quantity = 0 
                       ORDER BY p.id DESC";
$out_of_stock_result = mysqli_query($conn, $out_of_stock_query);

// Get well stocked products for comparison
$well_stocked_query = "SELECT COUNT(*) as count FROM products WHERE stock_quantity >= 10";
$well_stocked_result = mysqli_fetch_assoc(mysqli_query($conn, $well_stocked_query));
$well_stocked_count = $well_stocked_result['count'] ?? 0;

$low_count = mysqli_num_rows($low_stock_result);
$out_count = mysqli_num_rows($out_of_stock_result);
$total_products = $low_count + $out_count + $well_stocked_count;
?>

<style>
    .stock-badge-critical {
        background: #fee2e2;
        color: #dc2626;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .stock-badge-low {
        background: #fef3c7;
        color: #d97706;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .stock-range {
        height: 6px;
        background: #e2e8f0;
        border-radius: 3px;
        overflow: hidden;
        width: 100px;
    }
    .stock-range-fill {
        height: 100%;
        border-radius: 3px;
    }
    .fill-critical { background: #dc2626; }
    .fill-low { background: #d97706; }
</style>

<!-- Stats Summary -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card" style="background: #fef2f2;">
            <div class="stat-icon" style="background: #fee2e2;">
                <i class="fa-solid fa-ban" style="color: #dc2626;"></i>
            </div>
            <h3 style="color: #dc2626;"><?php echo $out_count; ?></h3>
            <p>Out of Stock</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="background: #fffbeb;">
            <div class="stat-icon" style="background: #fef3c7;">
                <i class="fa-solid fa-triangle-exclamation" style="color: #d97706;"></i>
            </div>
            <h3 style="color: #d97706;"><?php echo $low_count; ?></h3>
            <p>Low Stock (&lt;10 units)</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="background: #ecfdf5;">
            <div class="stat-icon" style="background: #d1fae5;">
                <i class="fa-solid fa-check-circle" style="color: #059669;"></i>
            </div>
            <h3 style="color: #059669;"><?php echo $well_stocked_count; ?></h3>
            <p>Well Stocked (≥10)</p>
        </div>
    </div>
</div>

<!-- Out of Stock Section -->
<?php if($out_count > 0): ?>
<div class="table-container" data-aos="fade-up">
    <div class="table-header" style="background: #fef2f2; border-bottom-color: #fecaca;">
        <h3 style="margin: 0; color: #dc2626;">
            <i class="fa-solid fa-circle-exclamation"></i> Out of Stock - Urgent Restock Needed
        </h3>
        <span class="stock-badge-critical"><?php echo $out_count; ?> products</span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th><th>Category</th><th>Current Stock</th><th>Quick Update</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                mysqli_data_seek($out_of_stock_result, 0);
                while($product = mysqli_fetch_assoc($out_of_stock_result)): 
                ?>
                <tr style="background: #fef2f2;">
                    <td style="display: flex; align-items: center; gap: 12px;">
                        <img src="../assets/images/<?php echo $product['image'] ?? 'default.jpg'; ?>" style="width: 45px; height: 45px; object-fit: cover; border-radius: 10px;" onerror="this.src='https://placehold.co/45x45?text=No+Image'">
                        <div>
                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                            <div style="font-size: 11px; color: #64748b;">ID: #<?php echo $product['id']; ?></div>
                        </div>
                     </td>
                    <td><?php echo $product['category_name'] ?? 'Uncategorized'; ?></td>
                    <td><span class="stock-badge-critical">0 units</span></td>
                    <td>
                        <form method="POST" style="display: inline-flex; gap: 5px;">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="number" name="stock_quantity" value="0" style="width: 80px; padding: 5px; border-radius: 8px; border: 1px solid #ddd;" min="0" required>
                            <button type="submit" name="update_stock" class="btn-edit" style="padding: 5px 10px;">
                                <i class="fa-solid fa-rotate"></i> Update
                            </button>
                        </form>
                    </td>
                    <td>
                        <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn-view btn-sm">
                            <i class="fa-solid fa-pen"></i> Edit
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Low Stock Section -->
<?php if($low_count > 0): ?>
<div class="table-container" data-aos="fade-up" data-aos-delay="100">
    <div class="table-header" style="background: #fffbeb; border-bottom-color: #fde68a;">
        <h3 style="margin: 0; color: #d97706;">
            <i class="fa-solid fa-triangle-exclamation"></i> Low Stock Products (Less than 10 units)
        </h3>
        <span class="stock-badge-low"><?php echo $low_count; ?> products</span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th><th>Category</th><th>Current Stock</th><th>Stock Status</th><th>Quick Update</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                mysqli_data_seek($low_stock_result, 0);
                while($product = mysqli_fetch_assoc($low_stock_result)): 
                    $stock = $product['stock_quantity'];
                    $badgeClass = $stock <= 2 ? 'stock-badge-critical' : 'stock-badge-low';
                    $fillClass = $stock <= 2 ? 'fill-critical' : 'fill-low';
                    $fillWidth = ($stock / 10) * 100;
                    $statusText = $stock <= 2 ? 'Critical - Order Now!' : 'Low - Restock Soon';
                ?>
                <tr>
                    <td style="display: flex; align-items: center; gap: 12px;">
                        <img src="../assets/images/<?php echo $product['image'] ?? 'default.jpg'; ?>" style="width: 45px; height: 45px; object-fit: cover; border-radius: 10px;" onerror="this.src='https://placehold.co/45x45?text=No+Image'">
                        <div>
                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                            <div style="font-size: 11px; color: #64748b;">ID: #<?php echo $product['id']; ?></div>
                        </div>
                    </td>
                    <td><?php echo $product['category_name'] ?? 'Uncategorized'; ?></td>
                    <td><span class="<?php echo $badgeClass; ?>"><?php echo $stock; ?> units left</span></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div class="stock-range">
                                <div class="stock-range-fill <?php echo $fillClass; ?>" style="width: <?php echo $fillWidth; ?>%;"></div>
                            </div>
                            <span style="font-size: 11px; color: <?php echo $stock <= 2 ? '#dc2626' : '#d97706'; ?>;">
                                <?php echo $statusText; ?>
                            </span>
                        </div>
                    </td>
                    <td>
                        <form method="POST" style="display: inline-flex; gap: 5px;">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="number" name="stock_quantity" value="<?php echo $stock; ?>" style="width: 80px; padding: 5px; border-radius: 8px; border: 1px solid #ddd;" min="0" required>
                            <button type="submit" name="update_stock" class="btn-edit" style="padding: 5px 10px;">
                                <i class="fa-solid fa-rotate"></i> Update
                            </button>
                        </form>
                    </td>
                    <td>
                        <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn-view btn-sm">
                            <i class="fa-solid fa-pen"></i> Edit
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- No Issues Found -->
<?php if($low_count == 0 && $out_count == 0 && $total_products > 0): ?>
<div class="table-container text-center py-5" data-aos="fade-up">
    <i class="fa-solid fa-check-circle" style="font-size: 60px; color: #10b981; margin-bottom: 15px;"></i>
    <h3 style="color: #1e293b;">All Products Have Good Stock!</h3>
    <p style="color: #64748b;">No low stock or out of stock products found.</p>
    <a href="products.php" class="btn-add mt-3">View All Products</a>
</div>
<?php endif; ?>

<!-- No Products Yet -->
<?php if($total_products == 0): ?>
<div class="table-container text-center py-5" data-aos="fade-up">
    <i class="fa-solid fa-box-open" style="font-size: 60px; color: #94a3b8; margin-bottom: 15px;"></i>
    <h3 style="color: #1e293b;">No Products Found</h3>
    <p style="color: #64748b;">Add your first product to start selling!</p>
    <a href="add-product.php" class="btn-add mt-3"><i class="fa-solid fa-plus"></i> Add New Product</a>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>