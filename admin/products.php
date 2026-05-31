<?php
$page_title = 'Products';
require_once 'includes/header.php';
require_once '../backend/config/database.php';

// Handle delete
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $img = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM products WHERE id = $id"));
    if($img && $img['image'] && file_exists("../assets/images/" . $img['image'])) {
        unlink("../assets/images/" . $img['image']);
    }
    mysqli_query($conn, "DELETE FROM products WHERE id = $id");
    echo "<script>showNotification('Product deleted successfully!');</script>";
}

// Handle bulk delete
if(isset($_POST['bulk_delete']) && isset($_POST['selected_products'])) {
    $selected = implode(',', array_map('intval', $_POST['selected_products']));
    if(!empty($selected)) {
        $img_query = mysqli_query($conn, "SELECT image FROM products WHERE id IN ($selected)");
        while($img = mysqli_fetch_assoc($img_query)) {
            if($img['image'] && file_exists("../assets/images/" . $img['image'])) {
                unlink("../assets/images/" . $img['image']);
            }
        }
        mysqli_query($conn, "DELETE FROM products WHERE id IN ($selected)");
        echo "<script>showNotification('Products deleted successfully!');</script>";
    }
}

// Get filter values
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : '';
$stock_filter = isset($_GET['stock']) ? $_GET['stock'] : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Build query
$query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";

if($category_filter) {
    $query .= " AND p.category_id = $category_filter";
}
if($stock_filter == 'low') {
    $query .= " AND p.stock_quantity < 5 AND p.stock_quantity > 0";
} elseif($stock_filter == 'out') {
    $query .= " AND p.stock_quantity = 0";
} elseif($stock_filter == 'instock') {
    $query .= " AND p.stock_quantity > 0";
}
if($search) {
    $query .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%')";
}

$query .= " ORDER BY p.id DESC";
$products = mysqli_query($conn, $query);

// Get categories for filter
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

// Get counts
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products"))['count'] ?? 0;
$low_stock_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE stock_quantity < 5 AND stock_quantity > 0"))['count'] ?? 0;
$out_stock_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE stock_quantity = 0"))['count'] ?? 0;
?>

<style>
    /* Stats Cards Row */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card-custom {
        background: white;
        border-radius: 20px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        transition: all 0.3s;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border: 1px solid #f0f0f0;
    }
    
    .stat-card-custom:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    }
    
    .stat-icon-custom {
        width: 55px;
        height: 55px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    
    .stat-icon-custom.blue { background: #e3f2fd; color: #1976d2; }
    .stat-icon-custom.orange { background: #fff3e0; color: #f57c00; }
    .stat-icon-custom.red { background: #ffebee; color: #d32f2f; }
    .stat-icon-custom.green { background: #e8f5e9; color: #388e3c; }
    
    .stat-info-custom h3 {
        font-size: 28px;
        font-weight: 700;
        margin: 0;
        color: #1e293b;
    }
    
    .stat-info-custom p {
        margin: 0;
        color: #64748b;
        font-size: 13px;
    }
    
    /* Filter Bar */
    .filter-bar {
        background: white;
        border-radius: 20px;
        padding: 15px 20px;
        margin-bottom: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    
    .filter-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .filter-chip {
        padding: 8px 18px;
        border-radius: 40px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s;
        background: #f1f5f9;
        color: #475569;
    }
    
    .filter-chip:hover {
        background: #e2e8f0;
        transform: translateY(-1px);
    }
    
    .filter-chip.active {
        background: #517a96;
        color: white;
        box-shadow: 0 2px 8px rgba(81,122,150,0.3);
    }
    
    .search-wrapper {
        display: flex;
        gap: 8px;
    }
    
    .search-wrapper input {
        padding: 10px 18px;
        border: 1px solid #e2e8f0;
        border-radius: 40px;
        width: 260px;
        font-size: 13px;
        transition: all 0.2s;
    }
    
    .search-wrapper input:focus {
        border-color: #517a96;
        outline: none;
        box-shadow: 0 0 0 3px rgba(81,122,150,0.1);
    }
    
    .search-wrapper button {
        padding: 10px 22px;
        background: #517a96;
        color: white;
        border: none;
        border-radius: 40px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .search-wrapper button:hover {
        background: #3d5a73;
        transform: translateY(-1px);
    }
    
    /* Table Styles */
    .products-table-container {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    
    .products-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .products-table th {
        background: #f8fafc;
        padding: 16px 18px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        color: #475569;
        border-bottom: 1px solid #eef2f6;
    }
    
    .products-table td {
        padding: 16px 18px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
        color: #334155;
    }
    
    .products-table tr {
        transition: all 0.2s;
    }
    
    .products-table tr:hover {
        background: #f8fafc;
    }
    
    .product-img {
        width: 52px;
        height: 52px;
        object-fit: cover;
        border-radius: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        transition: all 0.2s;
    }
    
    .product-img:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    
    .product-name {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 4px;
    }
    
    .badge-new {
        background: linear-gradient(135deg, #517a96, #3d5a73);
        color: white;
        font-size: 9px;
        padding: 2px 8px;
        border-radius: 20px;
        margin-left: 8px;
    }
    
    .badge-bestseller {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        font-size: 9px;
        padding: 2px 8px;
        border-radius: 20px;
        margin-left: 5px;
    }
    
    .stock-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 30px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .stock-good {
        background: #e8f5e9;
        color: #2e7d32;
    }
    
    .stock-low {
        background: #fff3e0;
        color: #ed6c02;
    }
    
    .stock-out {
        background: #ffebee;
        color: #d32f2f;
    }
    
    .price-current {
        font-weight: 700;
        color: #1e293b;
        font-size: 15px;
    }
    
    .price-old {
        text-decoration: line-through;
        color: #94a3b8;
        font-size: 11px;
        margin-left: 6px;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .btn-action {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.2s;
        font-size: 13px;
    }
    
    .btn-edit-action {
        background: #e3f2fd;
        color: #1976d2;
    }
    
    .btn-edit-action:hover {
        background: #1976d2;
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-delete-action {
        background: #ffebee;
        color: #d32f2f;
    }
    
    .btn-delete-action:hover {
        background: #d32f2f;
        color: white;
        transform: translateY(-2px);
    }
    
    /* Bulk Actions Bar */
    .bulk-actions-bar {
        background: #f1f5f9;
        border-radius: 16px;
        padding: 12px 20px;
        margin-bottom: 20px;
        display: none;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .bulk-actions-bar.show {
        display: flex;
    }
    
    .checkbox-col {
        width: 30px;
    }
    
    .btn-bulk-delete {
        background: #d32f2f;
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 30px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .btn-bulk-delete:hover {
        background: #b71c1c;
        transform: translateY(-1px);
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-state i {
        font-size: 70px;
        color: #cbd5e1;
        margin-bottom: 20px;
        display: block;
    }
    
    .empty-state h4 {
        color: #475569;
        margin-bottom: 10px;
    }
    
    .empty-state p {
        color: #94a3b8;
        margin-bottom: 20px;
    }
    
    @media (max-width: 992px) {
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .stats-row {
            grid-template-columns: 1fr;
        }
        .filter-bar {
            flex-direction: column;
        }
        .search-wrapper {
            width: 100%;
        }
        .search-wrapper input {
            flex: 1;
        }
    }
</style>

<!-- Header -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <div>
        <h1 style="font-size: 24px; font-weight: 700; margin: 0; color: #1e293b;">
            <i class="fa-solid fa-box" style="color: #517a96; margin-right: 10px;"></i> Products
        </h1>
        <p style="color: #64748b; margin: 5px 0 0; font-size: 13px;">Manage your product inventory</p>
    </div>
    <a href="add-product.php" class="btn-add" style="background: linear-gradient(135deg, #517a96, #3d5a73);">
        <i class="fa-solid fa-plus"></i> Add New Product
    </a>
</div>

<!-- Stats Cards -->
<div class="stats-row">
    <div class="stat-card-custom">
        <div class="stat-icon-custom blue">
            <i class="fa-solid fa-box"></i>
        </div>
        <div class="stat-info-custom">
            <h3><?php echo $total_products; ?></h3>
            <p>Total Products</p>
        </div>
    </div>
    <div class="stat-card-custom">
        <div class="stat-icon-custom orange">
            <i class="fa-solid fa-chart-line"></i>
        </div>
        <div class="stat-info-custom">
            <h3><?php echo $low_stock_count; ?></h3>
            <p>Low Stock (&#60;5)</p>
        </div>
    </div>
    <div class="stat-card-custom">
        <div class="stat-icon-custom red">
            <i class="fa-solid fa-ban"></i>
        </div>
        <div class="stat-info-custom">
            <h3><?php echo $out_stock_count; ?></h3>
            <p>Out of Stock</p>
        </div>
    </div>
    <div class="stat-card-custom">
        <div class="stat-icon-custom green">
            <i class="fa-solid fa-dollar-sign"></i>
        </div>
        <div class="stat-info-custom">
            <h3>$<?php echo number_format($total_products * 50, 0); ?></h3>
            <p>Inventory Value</p>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <div class="filter-buttons">
        <a href="products.php" class="filter-chip <?php echo !$category_filter && !$stock_filter ? 'active' : ''; ?>">
            <i class="fa-solid fa-grid-2"></i> All
        </a>
        <a href="products.php?stock=low" class="filter-chip <?php echo $stock_filter == 'low' ? 'active' : ''; ?>">
            <i class="fa-solid fa-exclamation-triangle"></i> Low Stock
        </a>
        <a href="products.php?stock=out" class="filter-chip <?php echo $stock_filter == 'out' ? 'active' : ''; ?>">
            <i class="fa-solid fa-ban"></i> Out of Stock
        </a>
        <a href="products.php?stock=instock" class="filter-chip <?php echo $stock_filter == 'instock' ? 'active' : ''; ?>">
            <i class="fa-solid fa-check-circle"></i> In Stock
        </a>
    </div>
    
    <form class="search-wrapper" method="GET">
        <input type="text" name="search" placeholder="Search by name or description..." value="<?php echo htmlspecialchars($search); ?>">
        <?php if($category_filter): ?>
            <input type="hidden" name="category" value="<?php echo $category_filter; ?>">
        <?php endif; ?>
        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        <?php if($search || $category_filter || $stock_filter): ?>
            <a href="products.php" style="padding: 10px 18px; background: #e2e8f0; border-radius: 40px; color: #475569; text-decoration: none;">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Bulk Actions -->
<form method="POST" id="bulkForm">
    <div class="bulk-actions-bar" id="bulkActions">
        <div>
            <i class="fa-regular fa-square-check"></i>
            <span id="selectedCount">0</span> products selected
        </div>
        <button type="submit" name="bulk_delete" class="btn-bulk-delete" onclick="return confirm('Delete selected products? This action cannot be undone!')">
            <i class="fa-solid fa-trash"></i> Delete Selected
        </button>
    </div>
    
    <!-- Products Table -->
    <div class="products-table-container">
        <table class="products-table">
            <thead>
                <tr>
                    <th class="checkbox-col"><input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"></th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($products) > 0): ?>
                    <?php while($product = mysqli_fetch_assoc($products)): 
                        $stock = $product['stock_quantity'];
                        if($stock <= 0) {
                            $stock_class = 'stock-out';
                            $stock_icon = 'fa-circle-exclamation';
                            $stock_text = 'Out of Stock';
                        } elseif($stock < 5) {
                            $stock_class = 'stock-low';
                            $stock_icon = 'fa-clock';
                            $stock_text = 'Low Stock';
                        } else {
                            $stock_class = 'stock-good';
                            $stock_icon = 'fa-check-circle';
                            $stock_text = 'In Stock';
                        }
                    ?>
                    <tr>
                        <td class="checkbox-col"><input type="checkbox" name="selected_products[]" value="<?php echo $product['id']; ?>" class="product-checkbox" onclick="updateBulkActions()"></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="../assets/images/<?php echo $product['image']; ?>" class="product-img" 
                                     onerror="this.src='https://placehold.co/52x52?text=📦'">
                                <div>
                                    <div class="product-name">
                                        <?php echo htmlspecialchars(substr($product['name'], 0, 35)); ?>
                                        <?php if(strlen($product['name']) > 35) echo '...'; ?>
                                        <?php if($product['is_new']): ?>
                                            <span class="badge-new">NEW</span>
                                        <?php endif; ?>
                                        <?php if($product['is_bestseller']): ?>
                                            <span class="badge-bestseller">BEST</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 11px; color: #94a3b8;">
                                        ID: #<?php echo $product['id']; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo $product['category_name'] ?? 'Uncategorized'; ?></td>
                        <td>
                            <span class="price-current">$<?php echo number_format($product['price'], 2); ?></span>
                            <?php if($product['old_price']): ?>
                                <span class="price-old">$<?php echo number_format($product['old_price'], 2); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="stock-badge <?php echo $stock_class; ?>">
                                <i class="fa-solid <?php echo $stock_icon; ?>"></i> <?php echo $stock_text; ?> (<?php echo $stock; ?>)
                            </span>
                        </td>
                        <td>
                            <?php if($stock <= 0): ?>
                                <span style="color: #d32f2f; font-size: 11px;"><i class="fa-solid fa-bell"></i> Urgent</span>
                            <?php elseif($stock < 5): ?>
                                <span style="color: #ed6c02; font-size: 11px;"><i class="fa-solid fa-truck"></i> Restock soon</span>
                            <?php else: ?>
                                <span style="color: #2e7d32; font-size: 11px;"><i class="fa-solid fa-check"></i> Available</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn-action btn-edit-action" title="Edit Product">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="javascript:void(0)" onclick="confirmDelete('products.php?delete=<?php echo $product['id']; ?>')" class="btn-action btn-delete-action" title="Delete Product">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fa-solid fa-store-slash"></i>
                                <h4>No Products Found</h4>
                                <p>Get started by adding your first product to the store.</p>
                                <a href="add-product.php" class="btn-add" style="display: inline-flex;">
                                    <i class="fa-solid fa-plus"></i> Add Your First Product
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</form>

<script>
    function toggleSelectAll(source) {
        const checkboxes = document.querySelectorAll('.product-checkbox');
        checkboxes.forEach(cb => cb.checked = source.checked);
        updateBulkActions();
    }
    
    function updateBulkActions() {
        const checkboxes = document.querySelectorAll('.product-checkbox:checked');
        const count = checkboxes.length;
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');
        
        if(count > 0) {
            bulkActions.classList.add('show');
            selectedCount.textContent = count;
        } else {
            bulkActions.classList.remove('show');
        }
        
        const selectAll = document.getElementById('selectAll');
        const allBoxes = document.querySelectorAll('.product-checkbox');
        if(selectAll) {
            selectAll.checked = allBoxes.length > 0 && allBoxes.length === checkboxes.length;
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        updateBulkActions();
    });
</script>

<?php require_once 'includes/footer.php'; ?>