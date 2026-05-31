<?php
$page_title = 'Register';
require_once 'backend/includes/header.php';

// If already logged in, redirect to home
if (isLoggedIn()) {
    redirect('index.php');
}

$errors = $_SESSION['register_errors'] ?? [];
unset($_SESSION['register_errors']);
?>

<section class="auth-section">
    <div class="container">
        <div class="auth-card">
            <h2>Create Account</h2>
            <p>Join the Velvet Aura community</p>
            
            <?php if(!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach($errors as $error): ?>
                    <div><?php echo $error; ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <form action="backend/auth/register-process.php" method="POST" id="registerForm">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="Enter your full name" required>
                </div>
                
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" class="form-control" placeholder="Enter your phone number">
                </div>
                
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" class="form-control" placeholder="Create a password (min 6 characters)" required>
                    <small class="text-muted">Password must be at least 6 characters long</small>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="terms" class="form-check-input" id="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn-auth">Create Account →</button>
            </form>
            
            <div class="auth-footer">
                Already have an account? <a href="login.php">Login</a>
            </div>
        </div>
    </div>
</section>

<style>
.auth-section {
    min-height: 80vh;
    display: flex;
    align-items: center;
    padding: 80px 0;
    background: #faf9f8;
}

.auth-card {
    background: white;
    border-radius: 24px;
    padding: 40px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
    max-width: 500px;
    margin: 0 auto;
}

.auth-card h2 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 10px;
}

.auth-card p {
    color: #666;
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-weight: 500;
    margin-bottom: 8px;
    display: block;
}

.form-group input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #ddd;
    border-radius: 12px;
    font-size: 14px;
    outline: none;
    transition: 0.3s;
}

.form-group input:focus {
    border-color: #D4B5A7;
    box-shadow: 0 0 0 2px rgba(212, 181, 167, 0.2);
}

.btn-auth {
    width: 100%;
    background: #111;
    color: white;
    padding: 14px;
    border: none;
    border-radius: 40px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: 0.3s;
    margin-top: 10px;
}

.btn-auth:hover {
    background: #333;
}

.auth-footer {
    text-align: center;
    margin-top: 25px;
    font-size: 14px;
    color: #666;
}

.auth-footer a {
    color: #111;
    font-weight: 600;
    text-decoration: none;
}

.alert {
    padding: 12px 16px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.alert-danger {
    background: #fee;
    color: #c00;
    border: 1px solid #fcc;
}

.form-check-label a {
    color: #111;
}

small.text-muted {
    font-size: 12px;
    color: #666;
    display: block;
    margin-top: 5px;
}
</style>

<?php require_once 'backend/includes/footer.php'; ?>