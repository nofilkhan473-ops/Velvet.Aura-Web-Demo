<?php
$page_title = 'Admin Profile';
require_once 'includes/header.php';
require_once '../backend/config/database.php';

$admin_id = $_SESSION['admin_id'];
$query = "SELECT * FROM users WHERE id = $admin_id";
$result = mysqli_query($conn, $query);
$admin = mysqli_fetch_assoc($result);

// Update profile
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    if(!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $update = "UPDATE users SET name='$name', email='$email', password='$hashed' WHERE id=$admin_id";
    } else {
        $update = "UPDATE users SET name='$name', email='$email' WHERE id=$admin_id";
    }
    
    if(mysqli_query($conn, $update)) {
        $_SESSION['admin_name'] = $name;
        echo "<script>showNotification('Profile updated successfully!');</script>";
        // Refresh data
        $result = mysqli_query($conn, $query);
        $admin = mysqli_fetch_assoc($result);
    } else {
        echo "<script>showNotification('Update failed!', 'error');</script>";
    }
}
?>

<div class="form-container">
    <h2 style="margin-bottom: 25px;">Admin Profile</h2>
    
    <form method="POST">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>New Password (leave empty to keep current)</label>
            <input type="password" name="password" placeholder="Enter new password">
        </div>
        
        <div style="margin-top: 30px;">
            <a href="index.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
            <button type="submit" class="btn-submit">Update Profile →</button>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>