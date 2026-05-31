<?php
$page_title = 'Categories';
require_once 'includes/header.php';
require_once '../backend/config/database.php';

// Add Category
if(isset($_POST['add_category'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $slug = strtolower(str_replace(' ', '-', $name));
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    $query = "INSERT INTO categories (name, slug, description, is_active) VALUES ('$name', '$slug', '$description', 1)";
    if(mysqli_query($conn, $query)) {
        echo "<script>showNotification('Category added successfully!'); window.location.href='categories.php';</script>";
    } else {
        echo "<script>showNotification('Failed to add category!', 'error');</script>";
    }
}

// Delete Category
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM categories WHERE id = $id");
    echo "<script>showNotification('Category deleted!'); window.location.href='categories.php';</script>";
}

// Toggle Status
if(isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $current = mysqli_fetch_assoc(mysqli_query($conn, "SELECT is_active FROM categories WHERE id = $id"));
    $new = $current['is_active'] ? 0 : 1;
    mysqli_query($conn, "UPDATE categories SET is_active = $new WHERE id = $id");
    echo "<script>showNotification('Status updated!'); window.location.href='categories.php';</script>";
}

$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY id DESC");
?>

<style>
    /* Header */
    .cats-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .cats-header h2 {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .cats-header h2 i {
        color: #517a96;
        margin-right: 10px;
    }
    
    /* Table Container */
    .cats-table-container {
        background: white;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        border: 1px solid #eef2f6;
    }
    
    .cats-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .cats-table th {
        background: #fafcfc;
        padding: 16px 20px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #eef2f6;
    }
    
    .cats-table td {
        padding: 16px 20px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 14px;
        color: #334155;
    }
    
    .cats-table tr {
        transition: all 0.2s;
    }
    
    .cats-table tr:hover {
        background: #fafcfc;
    }
    
    .cats-table tr:last-child td {
        border-bottom: none;
    }
    
    /* Category Name with Icon */
    .cat-name {
        font-weight: 600;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .cat-icon {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #e3f2fd, #e8f5e9);
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #517a96;
        font-size: 16px;
    }
    
    /* Slug */
    .cat-slug {
        font-family: monospace;
        font-size: 12px;
        background: #f1f5f9;
        padding: 5px 12px;
        border-radius: 20px;
        display: inline-block;
        color: #517a96;
    }
    
    /* Status Badges */
    .status-badge-cat {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 16px;
        border-radius: 40px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-active-cat {
        background: #e8f5e9;
        color: #2e7d32;
    }
    
    .status-inactive-cat {
        background: #ffebee;
        color: #c62828;
    }
    
    /* Action Buttons */
    .action-buttons-cat {
        display: flex;
        gap: 8px;
    }
    
    .btn-action-cat {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.2s;
        font-size: 14px;
    }
    
    .btn-toggle-cat {
        background: #f1f5f9;
        color: #64748b;
    }
    
    .btn-toggle-cat:hover {
        background: #517a96;
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-delete-cat {
        background: #ffebee;
        color: #d32f2f;
    }
    
    .btn-delete-cat:hover {
        background: #d32f2f;
        color: white;
        transform: translateY(-2px);
    }
    
    /* Empty State */
    .empty-cats {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-cats i {
        font-size: 65px;
        color: #cbd5e1;
        margin-bottom: 20px;
        display: block;
    }
    
    .empty-cats h4 {
        color: #1e293b;
        margin-bottom: 8px;
        font-size: 18px;
    }
    
    .empty-cats p {
        color: #64748b;
        margin-bottom: 20px;
    }
    
    /* Modal Styles */
    .modal-custom .modal-content {
        border-radius: 24px;
        border: none;
        box-shadow: 0 20px 35px rgba(0,0,0,0.1);
    }
    
    .modal-custom .modal-header {
        background: linear-gradient(135deg, #f8fafc, #ffffff);
        border-bottom: 1px solid #eef2f6;
        padding: 20px 25px;
    }
    
    .modal-custom .modal-header .modal-title {
        font-weight: 700;
        color: #1e293b;
    }
    
    .modal-custom .modal-header .modal-title i {
        color: #517a96;
        margin-right: 8px;
    }
    
    .modal-custom .modal-body {
        padding: 25px;
    }
    
    .modal-custom .modal-footer {
        border-top: 1px solid #eef2f6;
        padding: 18px 25px;
    }
    
    .form-group-cat {
        margin-bottom: 20px;
    }
    
    .form-group-cat label {
        font-weight: 600;
        margin-bottom: 8px;
        display: block;
        color: #1e293b;
        font-size: 13px;
    }
    
    .form-group-cat label i {
        color: #517a96;
        margin-right: 6px;
    }
    
    .form-group-cat input,
    .form-group-cat textarea {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .form-group-cat input:focus,
    .form-group-cat textarea:focus {
        border-color: #517a96;
        outline: none;
        box-shadow: 0 0 0 3px rgba(81,122,150,0.1);
    }
    
    .btn-cancel-cat {
        background: #f1f5f9;
        color: #475569;
        padding: 10px 24px;
        border-radius: 40px;
        border: none;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-cancel-cat:hover {
        background: #e2e8f0;
    }
    
    .btn-submit-cat {
        background: linear-gradient(135deg, #517a96, #3d5a73);
        color: white;
        padding: 10px 28px;
        border-radius: 40px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-submit-cat:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(81,122,150,0.3);
    }
    
    .btn-add-cat {
        background: linear-gradient(135deg, #517a96, #3d5a73);
        color: white;
        padding: 10px 24px;
        border-radius: 40px;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        transition: all 0.2s;
    }
    
    .btn-add-cat:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(81,122,150,0.3);
    }
    
    @media (max-width: 768px) {
        .cats-table th, .cats-table td {
            padding: 12px 15px;
        }
        .action-buttons-cat {
            gap: 6px;
        }
    }
</style>

<!-- Header -->
<div class="cats-header">
    <h2><i class="fa-solid fa-tags"></i> All Categories</h2>
    <button class="btn-add-cat" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="fa-solid fa-plus"></i> Add Category
    </button>
</div>

<!-- Categories Table -->
<div class="cats-table-container">
    <table class="cats-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Slug</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(mysqli_num_rows($categories) > 0): ?>
                <?php while($cat = mysqli_fetch_assoc($categories)): ?>
                <tr>
                    <td style="width: 60px;">#<?php echo $cat['id']; ?></td>
                    <td>
                        <div class="cat-name">
                            <span class="cat-icon"><i class="fa-solid fa-folder"></i></span>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </div>
                    </div>
                    <td><span class="cat-slug"><?php echo $cat['slug']; ?></span></div>
                    <td>
                        <?php if($cat['is_active']): ?>
                            <span class="status-badge-cat status-active-cat">
                                <i class="fa-solid fa-circle" style="font-size: 8px;"></i> Active
                            </span>
                        <?php else: ?>
                            <span class="status-badge-cat status-inactive-cat">
                                <i class="fa-solid fa-circle" style="font-size: 8px;"></i> Inactive
                            </span>
                        <?php endif; ?>
                    </div>
                    <td>
                        <div class="action-buttons-cat">
                            <a href="?toggle=<?php echo $cat['id']; ?>" class="btn-action-cat btn-toggle-cat" title="<?php echo $cat['is_active'] ? 'Disable' : 'Enable'; ?>">
                                <i class="fa-solid <?php echo $cat['is_active'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                            </a>
                            <a href="javascript:void(0)" onclick="confirmDelete('categories.php?delete=<?php echo $cat['id']; ?>')" class="btn-action-cat btn-delete-cat" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">
                        <div class="empty-cats">
                            <i class="fa-regular fa-folder-open"></i>
                            <h4>No Categories Yet</h4>
                            <p>Create your first category to organize products</p>
                            <button class="btn-add-cat" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="fa-solid fa-plus"></i> Create Category
                            </button>
                        </div>
                    </div>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Category Modal -->
<div class="modal fade modal-custom" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-plus-circle"></i> Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group-cat">
                        <label><i class="fa-regular fa-tag"></i> Category Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g., Women's Clothing">
                    </div>
                    <div class="form-group-cat">
                        <label><i class="fa-regular fa-file-lines"></i> Description (Optional)</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description about this category..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel-cat" data-bs-dismiss="modal"><i class="fa-solid fa-times"></i> Cancel</button>
                    <button type="submit" name="add_category" class="btn-submit-cat"><i class="fa-solid fa-save"></i> Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>