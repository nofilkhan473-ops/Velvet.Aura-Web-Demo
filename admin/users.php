<?php
$page_title = 'Manage Users';
require_once 'includes/header.php';
require_once '../backend/config/database.php';

// Handle Delete
if(isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    // Prevent self deletion
    if($user_id != $_SESSION['admin_id']) {
        mysqli_query($conn, "DELETE FROM users WHERE id = $user_id");
        echo "<script>showNotification('User deleted successfully!'); window.location.href='users.php';</script>";
    } else {
        echo "<script>showNotification('You cannot delete yourself!', 'error');</script>";
    }
}

// Handle Make Admin
if(isset($_GET['make_admin'])) {
    $user_id = (int)$_GET['make_admin'];
    mysqli_query($conn, "UPDATE users SET is_admin = 1 WHERE id = $user_id");
    echo "<script>showNotification('User is now an admin!'); window.location.href='users.php';</script>";
}

// Handle Remove Admin
if(isset($_GET['remove_admin'])) {
    $user_id = (int)$_GET['remove_admin'];
    if($user_id != $_SESSION['admin_id']) {
        mysqli_query($conn, "UPDATE users SET is_admin = 0 WHERE id = $user_id");
        echo "<script>showNotification('Admin rights removed!'); window.location.href='users.php';</script>";
    } else {
        echo "<script>showNotification('You cannot remove your own admin rights!', 'error');</script>";
    }
}

// Fetch all users
$users_query = "SELECT * FROM users ORDER BY id DESC";
$users_result = mysqli_query($conn, $users_query);
$users = $users_result ? mysqli_fetch_all($users_result, MYSQLI_ASSOC) : [];

// Get stats
$total_users = count($users);
$admin_count = 0;
$customer_count = 0;
foreach($users as $user) {
    if(isset($user['is_admin']) && $user['is_admin'] == 1) {
        $admin_count++;
    } else {
        $customer_count++;
    }
}
?>

<style>
    /* Stats Row */
    .stats-users {
        display: flex;
        gap: 25px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }
    
    .stat-user {
        background: white;
        border-radius: 20px;
        padding: 20px 28px;
        min-width: 160px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        border: 1px solid #eef2f6;
        transition: all 0.3s;
    }
    
    .stat-user:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(81,122,150,0.1);
        border-color: #517a96;
    }
    
    .stat-user .number {
        font-size: 32px;
        font-weight: 800;
        color: #1e293b;
        line-height: 1;
    }
    
    .stat-user .label {
        font-size: 13px;
        color: #64748b;
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .stat-user.total .number { color: #517a96; }
    .stat-user.admins .number { color: #10b981; }
    .stat-user.customers .number { color: #f59e0b; }
    
    /* Header */
    .users-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .users-header h2 {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .users-header h2 i {
        color: #517a96;
        margin-right: 10px;
    }
    
    /* Table Container */
    .users-table-container {
        background: white;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        border: 1px solid #eef2f6;
    }
    
    .users-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .users-table th {
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
    
    .users-table td {
        padding: 16px 20px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 14px;
        color: #334155;
    }
    
    .users-table tr {
        transition: all 0.2s;
    }
    
    .users-table tr:hover {
        background: #fafcfc;
    }
    
    .users-table tr:last-child td {
        border-bottom: none;
    }
    
    /* User Name with Avatar */
    .user-name {
        font-weight: 600;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .user-avatar {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #e3f2fd, #e8f5e9);
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #517a96;
        font-size: 14px;
        font-weight: 600;
    }
    
    /* Email Style */
    .user-email {
        font-size: 13px;
        color: #517a96;
    }
    
    /* Role Badges */
    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 16px;
        border-radius: 40px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .role-admin {
        background: #e8f5e9;
        color: #2e7d32;
    }
    
    .role-customer {
        background: #f1f5f9;
        color: #64748b;
    }
    
    /* Action Buttons */
    .action-buttons-user {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .btn-action-user {
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
    
    .btn-make-admin {
        background: #e3f2fd;
        color: #1976d2;
    }
    
    .btn-make-admin:hover {
        background: #1976d2;
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-remove-admin {
        background: #fff3e0;
        color: #ed6c02;
    }
    
    .btn-remove-admin:hover {
        background: #ed6c02;
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-delete-user {
        background: #ffebee;
        color: #d32f2f;
    }
    
    .btn-delete-user:hover {
        background: #d32f2f;
        color: white;
        transform: translateY(-2px);
    }
    
    /* Empty State */
    .empty-users {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-users i {
        font-size: 65px;
        color: #cbd5e1;
        margin-bottom: 20px;
        display: block;
    }
    
    .empty-users h4 {
        color: #1e293b;
        margin-bottom: 8px;
        font-size: 18px;
    }
    
    .empty-users p {
        color: #64748b;
        margin-bottom: 20px;
    }
    
    /* Self indicator */
    .self-badge {
        background: #e2e8f0;
        color: #475569;
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 20px;
        margin-left: 8px;
    }
    
    @media (max-width: 768px) {
        .stats-users {
            gap: 15px;
        }
        .stat-user {
            padding: 15px 20px;
            min-width: 120px;
        }
        .stat-user .number {
            font-size: 24px;
        }
        .users-table th, .users-table td {
            padding: 12px 15px;
        }
        .action-buttons-user {
            flex-direction: column;
            gap: 5px;
        }
        .btn-action-user {
            justify-content: center;
        }
    }
</style>

<!-- Stats Cards -->
<div class="stats-users">
    <div class="stat-user total">
        <div class="number"><?php echo $total_users; ?></div>
        <div class="label"><i class="fas fa-users"></i> Total Users</div>
    </div>
    <div class="stat-user admins">
        <div class="number"><?php echo $admin_count; ?></div>
        <div class="label"><i class="fa-solid fa-crown"></i> Administrators</div>
    </div>
    <div class="stat-user customers">
        <div class="number"><?php echo $customer_count; ?></div>
        <div class="label"><i class="fa-regular fa-user"></i> Customers</div>
    </div>
</div>

<!-- Header -->
<div class="users-header">
    <h2><i class="fa-solid fa-users"></i> Manage Users</h2>
    <div style="font-size: 13px; color: #64748b;">
        <i class="fa-regular fa-shield"></i> Total: <?php echo $total_users; ?> users
    </div>
</div>

<!-- Users Table -->
<div class="users-table-container">
    <div class="table-responsive">
        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($users) > 0): ?>
                    <?php foreach($users as $user): 
                        $is_self = ($user['id'] == $_SESSION['admin_id']);
                    ?>
                    <tr>
                        <td style="width: 60px;">#<?php echo $user['id']; ?> </td>
                        <td>
                            <div class="user-name">
                                <span class="user-avatar">
                                    <?php echo strtoupper(substr($user['name'] ?? $user['username'] ?? 'U', 0, 1)); ?>
                                </span>
                                <?php echo htmlspecialchars($user['name'] ?? $user['username'] ?? '-'); ?>
                                <?php if($is_self): ?>
                                    <span class="self-badge"><i class="fa-regular fa-user"></i> You</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="user-email"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                        <td>
                            <?php if(isset($user['is_admin']) && $user['is_admin'] == 1): ?>
                                <span class="role-badge role-admin">
                                    <i class="fa-solid fa-crown"></i> Admin
                                </span>
                            <?php else: ?>
                                <span class="role-badge role-customer">
                                    <i class="fa-regular fa-user"></i> Customer
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'] ?? 'now')); ?></td>
                        <td>
                            <div class="action-buttons-user">
                                <?php if(isset($user['is_admin']) && $user['is_admin'] == 1): ?>
                                    <?php if(!$is_self): ?>
                                        <a href="?remove_admin=<?php echo $user['id']; ?>" class="btn-action-user btn-remove-admin" onclick="return confirm('Remove admin rights from this user?')">
                                            <i class="fa-solid fa-user-minus"></i> Remove Admin
                                        </a>
                                    <?php else: ?>
                                        <span class="btn-action-user btn-remove-admin" style="opacity: 0.5; cursor: not-allowed;">
                                            <i class="fa-solid fa-lock"></i> Current You
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="?make_admin=<?php echo $user['id']; ?>" class="btn-action-user btn-make-admin" onclick="return confirm('Make this user an admin?')">
                                        <i class="fa-solid fa-crown"></i> Make Admin
                                    </a>
                                <?php endif; ?>
                                
                                <?php if(!$is_self): ?>
                                    <a href="?delete=<?php echo $user['id']; ?>" class="btn-action-user btn-delete-user" onclick="return confirm('Delete this user? This action cannot be undone!')">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-users">
                                <i class="fa-regular fa-users-slash"></i>
                                <h4>No Users Found</h4>
                                <p>Users will appear here once they register.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>