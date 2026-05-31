<?php
$page_title = 'Forgot Password';
require_once 'backend/includes/header.php';

// If already logged in, redirect to home
if (isLoggedIn()) {
    redirect('index.php');
}
?>

<section class="auth-section">
    <div class="container">
        <div class="auth-card">
            <h2>Forgot Password?</h2>
            <p>Enter your email address and we'll send you a link to reset your password.</p>
            
            <?php if(isset($_GET['sent'])): ?>
                <div class="alert alert-success">
                    <i class="fa-regular fa-circle-check"></i> 
                    Password reset link has been sent to your email address.
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fa-regular fa-circle-exclamation"></i> 
                    Email address not found. Please try again.
                </div>
            <?php endif; ?>
            
            <form action="backend/auth/send-reset-link.php" method="POST" id="forgotForm">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter your registered email" required>
                    <div class="error-message" id="emailError">Please enter a valid email</div>
                </div>
                
                <button type="submit" class="btn-auth">Send Reset Link →</button>
            </form>
            
            <div class="auth-footer">
                <a href="login.php">← Back to Login</a>
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
    max-width: 450px;
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

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert i {
    margin-right: 8px;
}

.error-message {
    color: #ff6b6b;
    font-size: 12px;
    margin-top: 5px;
    display: none;
}
</style>

<script>
document.getElementById('forgotForm')?.addEventListener('submit', function(e) {
    const email = document.querySelector('input[name="email"]').value.trim();
    if (!email || !email.includes('@')) {
        e.preventDefault();
        document.getElementById('emailError').style.display = 'block';
    } else {
        document.getElementById('emailError').style.display = 'none';
    }
});
</script>

<?php require_once 'backend/includes/footer.php'; ?>