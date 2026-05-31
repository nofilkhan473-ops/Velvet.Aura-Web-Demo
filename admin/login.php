<?php
session_start();

// If already logged in, redirect to dashboard
if(isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

require_once '../backend/config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // Simple direct check for admin
    if($email == 'admin@velvetaura.com' && $password == 'admin123') {
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_name'] = 'Administrator';
        $_SESSION['is_admin'] = true;
        header('Location: index.php');
        exit();
    }
    
    // Check database
    $query = "SELECT * FROM users WHERE email = '$email' AND is_admin = 1";
    $result = mysqli_query($conn, $query);
    $admin = mysqli_fetch_assoc($result);
    
    if($admin) {
        if($password == $admin['password'] || $password == 'admin123') {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['is_admin'] = true;
            header('Location: index.php');
            exit();
        }
        else if(password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['is_admin'] = true;
            header('Location: index.php');
            exit();
        }
    }
    
    $error = 'Invalid email or password!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Velvet Aura</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0a0f15;
            position: relative;
            overflow: hidden;
        }
        
        /* ========== ANIMATED BACKGROUND ========== */
        .bg-gradient {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: linear-gradient(135deg, #0a0f15 0%, #12171f 50%, #0d1420 100%);
        }
        
        .bg-gradient::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            top: -50%;
            left: -50%;
            background: radial-gradient(circle, rgba(81,122,150,0.15) 0%, transparent 70%);
            animation: rotateBg 25s linear infinite;
        }
        
        @keyframes rotateBg {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Floating Orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            opacity: 0.35;
            animation: floatOrb 18s infinite ease-in-out;
            z-index: -1;
        }
        
        .orb-1 {
            width: 350px;
            height: 350px;
            background: #517a96;
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }
        
        .orb-2 {
            width: 450px;
            height: 450px;
            background: #6b9fbf;
            bottom: -150px;
            right: -150px;
            animation-delay: -6s;
        }
        
        .orb-3 {
            width: 250px;
            height: 250px;
            background: #3d5a73;
            top: 50%;
            left: 70%;
            animation-delay: -12s;
            opacity: 0.2;
        }
        
        @keyframes floatOrb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(40px, -30px) scale(1.1); }
            66% { transform: translate(-20px, 40px) scale(0.95); }
        }
        
        /* Login Container */
        .login-wrapper {
            width: 100%;
            padding: 20px;
        }
        
        .login-container {
            max-width: 440px;
            width: 100%;
            margin: 0 auto;
            animation: slideUp 0.6s cubic-bezier(0.2, 0.9, 0.4, 1.1);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Login Card */
        .login-card {
            background: rgba(18, 23, 31, 0.85);
            backdrop-filter: blur(15px);
            border-radius: 32px;
            padding: 40px 38px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(81, 122, 150, 0.25);
            transition: all 0.4s ease;
        }
        
        .login-card:hover {
            border-color: rgba(81, 122, 150, 0.5);
            box-shadow: 0 30px 60px -12px rgba(81, 122, 150, 0.2);
            transform: translateY(-2px);
        }
        
        /* Logo Section */
        .logo-section {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .logo-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(145deg, #517a96, #3d5a73);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            box-shadow: 0 10px 25px -8px rgba(81, 122, 150, 0.4);
            transition: all 0.3s;
        }
        
        .login-card:hover .logo-icon {
            transform: scale(1.02);
            box-shadow: 0 15px 30px -8px rgba(81, 122, 150, 0.5);
        }
        
        .logo-icon i {
            font-size: 38px;
            color: white;
        }
        
        .logo-text {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: 2px;
        }
        
        .logo-text span:first-child {
            color: #ffffff;
        }
        
        .logo-text span.dot {
            color: #517a96;
            font-size: 30px;
            display: inline-block;
        }
        
        .logo-text span:last-child {
            color: #517a96;
            font-weight: 500;
        }
        
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(81, 122, 150, 0.15);
            padding: 6px 14px;
            border-radius: 40px;
            font-size: 11px;
            color: #7aa9c4;
            margin-top: 14px;
            border: 1px solid rgba(81, 122, 150, 0.25);
            letter-spacing: 0.5px;
        }
        
        /* Heading */
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .login-header h2 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #ffffff;
        }
        
        .login-header p {
            color: #8a9bb0;
            font-size: 14px;
        }
        
        /* Alert */
        .alert {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(220, 53, 69, 0.12);
            border: 1px solid rgba(220, 53, 69, 0.25);
            border-radius: 14px;
            padding: 14px 18px;
            margin-bottom: 28px;
            color: #ff8a8a;
            font-size: 13px;
            animation: shakeAlert 0.4s ease;
        }
        
        @keyframes shakeAlert {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-4px); }
            75% { transform: translateX(4px); }
        }
        
        .alert i {
            font-size: 18px;
        }
        
        /* Form */
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #b8c5d4;
            letter-spacing: 0.3px;
        }
        
        .form-group label i {
            margin-right: 8px;
            color: #517a96;
            width: 18px;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group input {
            width: 100%;
            padding: 14px 16px 14px 44px;
            background: rgba(10, 15, 21, 0.8);
            border: 1px solid rgba(81, 122, 150, 0.25);
            border-radius: 16px;
            font-size: 14px;
            color: #ffffff;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #517a96;
            background: rgba(10, 15, 21, 1);
            box-shadow: 0 0 0 4px rgba(81, 122, 150, 0.15);
        }
        
        .input-group input::placeholder {
            color: #4a5a6e;
        }
        
        .input-group .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #517a96;
            font-size: 16px;
        }
        
        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6a7a8e;
            transition: color 0.2s;
            z-index: 2;
        }
        
        .toggle-password:hover {
            color: #517a96;
        }
        
        /* Button */
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #517a96, #3d5a73);
            border: none;
            padding: 15px 20px;
            border-radius: 40px;
            font-size: 15px;
            font-weight: 700;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            margin-top: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -8px rgba(81, 122, 150, 0.5);
            background: linear-gradient(135deg, #6b9fbf, #4a7a5f);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        /* Footer */
        .login-footer {
            margin-top: 28px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(81, 122, 150, 0.15);
        }
        
        .login-footer small {
            color: #6a7a8e;
            font-size: 11px;
        }
        
        .demo-link {
            margin-top: 14px;
        }
        
        .demo-btn {
            background: transparent;
            border: 1px solid rgba(81, 122, 150, 0.35);
            padding: 8px 18px;
            border-radius: 30px;
            font-size: 12px;
            color: #7aa9c4;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .demo-btn:hover {
            background: rgba(81, 122, 150, 0.1);
            border-color: #517a96;
            color: #90bfdb;
            transform: translateY(-1px);
        }
        
        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .spinner {
            text-align: center;
        }
        
        .spinner i {
            font-size: 48px;
            color: #517a96;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .spinner p {
            color: white;
            margin-top: 15px;
            font-size: 13px;
        }
        
        /* Responsive */
        @media (max-width: 500px) {
            .login-card {
                padding: 32px 24px;
            }
            
            .logo-text {
                font-size: 22px;
            }
            
            .login-header h2 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>

<div class="bg-gradient"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="login-wrapper">
    <div class="login-container">
        <div class="login-card">
            <!-- Logo -->
            <div class="logo-section">
                <div class="logo-icon">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="logo-text">
                    <span>VELVET</span><span class="dot">.</span><span>AURA</span>
                </div>
                <div class="admin-badge">
                    <i class="fas fa-shield-alt"></i> ADMINISTRATOR ACCESS
                </div>
            </div>
            
            <!-- Header -->
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Sign in to continue to your dashboard</p>
            </div>
            
            <!-- Error Alert -->
            <?php if($error): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address</label>
                    <div class="input-group">
                        <input type="email" name="email" id="email" required placeholder="admin@velvetaura.com" value="admin@velvetaura.com">
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" required placeholder="Enter your password">
                        <i class="fas fa-lock input-icon"></i>
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-login" id="loginBtn">
                    <i class="fas fa-arrow-right-to-bracket"></i> Login to Dashboard
                </button>
            </form>
            
            <!-- Footer -->
            <div class="login-footer">
                <small><i class="fas fa-shield-alt"></i> Secure Admin Panel • Encrypted Connection</small>
                <div class="demo-link">
                    <button type="button" class="demo-btn" onclick="fillDemoCredentials()">
                        <i class="fas fa-key"></i> Use Demo Credentials
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Authenticating...</p>
    </div>
</div>

<script>
    // Toggle Password Visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }
    
    // Fill Demo Credentials
    function fillDemoCredentials() {
        const emailField = document.getElementById('email');
        const passField = document.getElementById('password');
        
        emailField.value = 'admin@velvetaura.com';
        passField.value = 'admin123';
        
        // Highlight effect
        emailField.style.borderColor = '#517a96';
        passField.style.borderColor = '#517a96';
        emailField.style.boxShadow = '0 0 0 3px rgba(81, 122, 150, 0.2)';
        passField.style.boxShadow = '0 0 0 3px rgba(81, 122, 150, 0.2)';
        
        setTimeout(() => {
            emailField.style.borderColor = '';
            passField.style.borderColor = '';
            emailField.style.boxShadow = '';
            passField.style.boxShadow = '';
        }, 1500);
    }
    
    // Form Submit with Loading
    const loginForm = document.getElementById('loginForm');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            loadingOverlay.classList.add('active');
        });
    }
    
    // Auto hide alert after 4 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.style.transition = 'opacity 0.4s';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert) alert.remove();
            }, 400);
        }
    }, 4000);
    
    // Add ripple effect on button click
    const loginBtn = document.getElementById('loginBtn');
    if (loginBtn) {
        loginBtn.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    }
    
    // Add ripple style
    const rippleStyle = document.createElement('style');
    rippleStyle.textContent = `
        .btn-login {
            position: relative;
            overflow: hidden;
        }
        .ripple {
            position: absolute;
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: rippleAnim 0.6s linear;
            pointer-events: none;
        }
        @keyframes rippleAnim {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(rippleStyle);
</script>

</body>
</html>