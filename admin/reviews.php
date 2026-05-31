<?php
$page_title = 'Product Reviews';
require_once 'includes/header.php';
require_once '../backend/config/database.php';

// Approve review
if(isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    mysqli_query($conn, "UPDATE reviews SET is_approved = 1 WHERE id = $id");
    echo "<script>showNotification('Review approved successfully!'); window.location.href='reviews.php';</script>";
}

// Delete review
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM reviews WHERE id = $id");
    echo "<script>showNotification('Review deleted!'); window.location.href='reviews.php';</script>";
}

$reviews = mysqli_query($conn, "SELECT r.*, u.name as user_name, u.email as user_email, p.name as product_name, p.image as product_image 
                                FROM reviews r 
                                JOIN users u ON r.user_id = u.id 
                                JOIN products p ON r.product_id = p.id 
                                ORDER BY r.created_at DESC");

// Get stats
$total_reviews = mysqli_num_rows($reviews);
$pending_count = 0;
$approved_count = 0;
$avg_rating = 0;
$total_rating = 0;

mysqli_data_seek($reviews, 0);
while($rev = mysqli_fetch_assoc($reviews)) {
    if($rev['is_approved']) {
        $approved_count++;
    } else {
        $pending_count++;
    }
    $total_rating += $rev['rating'];
}
mysqli_data_seek($reviews, 0);

$avg_rating = $total_reviews > 0 ? round($total_rating / $total_reviews, 1) : 0;
?>

<style>
    /* Stats Row */
    .stats-reviews {
        display: flex;
        gap: 25px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }
    
    .stat-review {
        background: white;
        border-radius: 20px;
        padding: 20px 28px;
        min-width: 160px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        border: 1px solid #eef2f6;
        transition: all 0.3s;
    }
    
    .stat-review:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(81,122,150,0.1);
        border-color: #517a96;
    }
    
    .stat-review .number {
        font-size: 32px;
        font-weight: 800;
        color: #1e293b;
        line-height: 1;
    }
    
    .stat-review .label {
        font-size: 13px;
        color: #64748b;
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .stat-review.total .number { color: #517a96; }
    .stat-review.approved .number { color: #10b981; }
    .stat-review.pending .number { color: #f59e0b; }
    .stat-review.rating .number { color: #fbbf24; }
    
    /* Header */
    .reviews-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .reviews-header h2 {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .reviews-header h2 i {
        color: #517a96;
        margin-right: 10px;
    }
    
    /* Table Container */
    .reviews-table-container {
        background: white;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        border: 1px solid #eef2f6;
    }
    
    .reviews-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .reviews-table th {
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
    
    .reviews-table td {
        padding: 16px 20px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 14px;
        color: #334155;
    }
    
    .reviews-table tr {
        transition: all 0.2s;
    }
    
    .reviews-table tr:hover {
        background: #fafcfc;
    }
    
    .reviews-table tr:last-child td {
        border-bottom: none;
    }
    
    /* Product Info */
    .product-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .product-image {
        width: 45px;
        height: 45px;
        background: #f1f5f9;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    
    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .product-name {
        font-weight: 600;
        color: #1e293b;
        font-size: 14px;
    }
    
    /* Customer Info */
    .customer-name {
        font-weight: 500;
        color: #1e293b;
        margin-bottom: 4px;
    }
    
    .customer-email {
        font-size: 11px;
        color: #94a3b8;
    }
    
    /* Stars */
    .stars {
        display: flex;
        gap: 3px;
    }
    
    .stars i {
        font-size: 14px;
    }
    
    .rating-number {
        font-size: 13px;
        font-weight: 600;
        margin-left: 8px;
        color: #fbbf24;
    }
    
    /* Review Text */
    .review-text {
        max-width: 250px;
        font-size: 13px;
        color: #475569;
        line-height: 1.4;
    }
    
    /* Status Badges */
    .status-badge-review {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-approved {
        background: #e8f5e9;
        color: #2e7d32;
    }
    
    .status-pending {
        background: #fef3c7;
        color: #d97706;
    }
    
    /* Action Buttons */
    .action-buttons-review {
        display: flex;
        gap: 8px;
    }
    
    .btn-action-review {
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    
    .btn-approve {
        background: #e3f2fd;
        color: #1976d2;
    }
    
    .btn-approve:hover {
        background: #1976d2;
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-delete-review {
        background: #ffebee;
        color: #d32f2f;
    }
    
    .btn-delete-review:hover {
        background: #d32f2f;
        color: white;
        transform: translateY(-2px);
    }
    
    /* Empty State */
    .empty-reviews {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-reviews i {
        font-size: 65px;
        color: #cbd5e1;
        margin-bottom: 20px;
        display: block;
    }
    
    .empty-reviews h4 {
        color: #1e293b;
        margin-bottom: 8px;
        font-size: 18px;
    }
    
    .empty-reviews p {
        color: #64748b;
        margin-bottom: 20px;
    }
    
    @media (max-width: 992px) {
        .reviews-table {
            min-width: 800px;
        }
    }
    
    @media (max-width: 768px) {
        .stats-reviews {
            gap: 15px;
        }
        .stat-review {
            padding: 15px 20px;
            min-width: 120px;
        }
        .stat-review .number {
            font-size: 24px;
        }
    }
</style>

<!-- Stats Cards -->
<div class="stats-reviews">
    <div class="stat-review total">
        <div class="number"><?php echo $total_reviews; ?></div>
        <div class="label"><i class="fa-regular fa-star"></i> Total Reviews</div>
    </div>
    <div class="stat-review approved">
        <div class="number"><?php echo $approved_count; ?></div>
        <div class="label"><i class="fa-regular fa-check-circle"></i> Approved</div>
    </div>
    <div class="stat-review pending">
        <div class="number"><?php echo $pending_count; ?></div>
        <div class="label"><i class="fa-regular fa-clock"></i> Pending</div>
    </div>
    <div class="stat-review rating">
        <div class="number"><?php echo $avg_rating; ?></div>
        <div class="label"><i class="fa-solid fa-star"></i> Average Rating</div>
    </div>
</div>

<!-- Header -->
<div class="reviews-header">
    <h2><i class="fa-solid fa-star"></i> Product Reviews</h2>
    <div style="font-size: 13px; color: #64748b;">
        <i class="fa-regular fa-message"></i> <?php echo $total_reviews; ?> total reviews
    </div>
</div>

<!-- Reviews Table -->
<div class="reviews-table-container">
    <div class="table-responsive">
        <table class="reviews-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Customer</th>
                    <th>Rating</th>
                    <th>Review</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($total_reviews > 0): ?>
                    <?php while($review = mysqli_fetch_assoc($reviews)): ?>
                    <tr>
                        <td>
                            <div class="product-info">
                                <div class="product-image">
                                    <img src="../assets/images/<?php echo $review['product_image'] ?? 'default.jpg'; ?>" 
                                         onerror="this.src='https://placehold.co/45x45?text=📦'">
                                </div>
                                <div class="product-name">
                                    <?php echo htmlspecialchars(substr($review['product_name'], 0, 30)); ?>
                                    <?php if(strlen($review['product_name']) > 30) echo '...'; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="customer-name">
                                <i class="fa-regular fa-user" style="color: #517a96;"></i>
                                <?php echo htmlspecialchars($review['user_name'] ?? 'Guest'); ?>
                            </div>
                            <div class="customer-email">
                                <?php echo htmlspecialchars($review['user_email'] ?? ''); ?>
                            </div>
                        </td>
                        <td>
                            <div class="stars">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <i class="fa-<?php echo $i <= $review['rating'] ? 'solid' : 'regular'; ?> fa-star" style="color:#fbbf24;"></i>
                                <?php endfor; ?>
                                <span class="rating-number">(<?php echo $review['rating']; ?>)</span>
                            </div>
                        </td>
                        <td>
                            <div class="review-text" title="<?php echo htmlspecialchars($review['comment']); ?>">
                                <?php echo htmlspecialchars(substr($review['comment'], 0, 60)); ?>
                                <?php if(strlen($review['comment']) > 60) echo '...'; ?>
                            </div>
                        </td>
                        <td>
                            <?php if($review['is_approved']): ?>
                                <span class="status-badge-review status-approved">
                                    <i class="fa-regular fa-check-circle"></i> Approved
                                </span>
                            <?php else: ?>
                                <span class="status-badge-review status-pending">
                                    <i class="fa-regular fa-hourglass-half"></i> Pending
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space: nowrap;">
                            <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                        </td>
                        <td>
                            <div class="action-buttons-review">
                                <?php if(!$review['is_approved']): ?>
                                    <a href="?approve=<?php echo $review['id']; ?>" class="btn-action-review btn-approve" onclick="return confirm('Approve this review?')">
                                        <i class="fa-regular fa-thumbs-up"></i> Approve
                                    </a>
                                <?php endif; ?>
                                <a href="?delete=<?php echo $review['id']; ?>" class="btn-action-review btn-delete-review" onclick="return confirm('Delete this review? This action cannot be undone!')">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-reviews">
                                <i class="fa-regular fa-star"></i>
                                <h4>No Reviews Yet</h4>
                                <p>Customer reviews will appear here once they are submitted.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>