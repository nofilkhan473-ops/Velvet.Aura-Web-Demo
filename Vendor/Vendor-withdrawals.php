<?php
session_start();
require_once '../backend/config/database.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_vendor'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$vendor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
$error = '';
$success = '';

// Request withdrawal
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_withdrawal'])) {
    $amount = (float)$_POST['amount'];
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $payment_details = mysqli_real_escape_string($conn, $_POST['payment_details']);
    
    if($amount <= 0) {
        $error = "Please enter a valid amount!";
    } elseif($amount < 50) {
        $error = "Minimum withdrawal amount is $50!";
    } elseif($amount > $vendor['balance']) {
        $error = "Insufficient balance! Your current balance is $" . number_format($vendor['balance'], 2);
    } else {
        $query = "INSERT INTO vendor_withdrawals (vendor_id, amount, payment_method, payment_details, status) 
                  VALUES ($user_id, $amount, '$payment_method', '$payment_details', 'pending')";
        
        if(mysqli_query($conn, $query)) {
            // Deduct from balance
            mysqli_query($conn, "UPDATE users SET balance = balance - $amount WHERE id = $user_id");
            $success = "Withdrawal request submitted successfully! Admin will review it.";
            // Refresh vendor balance
            $vendor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

// Get withdrawal history
$withdrawals = mysqli_query($conn, "SELECT * FROM vendor_withdrawals WHERE vendor_id = $user_id ORDER BY created_at DESC");
$total_withdrawn = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM vendor_withdrawals WHERE vendor_id = $user_id AND status = 'completed'"))['total'] ?? 0;
$pending_withdrawals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM vendor_withdrawals WHERE vendor_id = $user_id AND status = 'pending'"))['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawals - Vendor Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fb;
            color: #1e293b;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: #ffffff;
            height: 100vh;
            position: fixed;
            border-right: 1px solid #e2e8f0;
            padding: 25px 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.02);
        }
        
        .sidebar .logo {
            font-size: 22px;
            font-weight: 800;
            text-align: center;
            margin-bottom: 35px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .sidebar .logo a {
            text-decoration: none;
            color: #1e293b;
        }
        
        .sidebar .logo span {
            color: #517a96;
        }
        
        .sidebar .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            margin: 8px 0;
            border-radius: 12px;
            color: #64748b;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .sidebar .nav-item:hover {
            background: #f1f5f9;
            color: #517a96;
            transform: translateX(5px);
        }
        
        .sidebar .nav-item.active {
            background: linear-gradient(135deg, #517a96, #3a6b85);
            color: white;
            box-shadow: 0 4px 12px rgba(81,122,150,0.2);
        }
        
        .sidebar .nav-item i {
            width: 22px;
            font-size: 16px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 30px 35px;
            min-height: 100vh;
        }
        
        /* Header */
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .page-header p {
            color: #64748b;
            font-size: 14px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.08);
            border-color: #cbd5e1;
        }
        
        .stat-card .icon {
            width: 55px;
            height: 55px;
            background: #f1f5f9;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
        }
        
        .stat-card .icon i {
            font-size: 28px;
            color: #517a96;
        }
        
        .stat-card .amount {
            font-size: 32px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: #64748b;
            font-size: 13px;
        }
        
        /* Form Card */
        .form-card {
            background: white;
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 40px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        
        .form-card h3 {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .form-card .subtitle {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
            font-size: 14px;
        }
        
        .form-group label i {
            color: #517a96;
            margin-right: 8px;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s;
            background: white;
            color: #1e293b;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #517a96;
            box-shadow: 0 0 0 3px rgba(81,122,150,0.1);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #517a96, #3a6b85);
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(81,122,150,0.3);
        }
        
        /* Table */
        .table-container {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        
        .table-container h3 {
            padding: 20px 25px;
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            border-bottom: 1px solid #e2e8f0;
            background: #fafcff;
        }
        
        .table-container h3 i {
            color: #517a96;
            margin-right: 8px;
        }
        
        .withdrawal-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .withdrawal-table th {
            background: #f8fafc;
            padding: 15px 20px;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .withdrawal-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-size: 14px;
        }
        
        .withdrawal-table tr:last-child td {
            border-bottom: none;
        }
        
        .withdrawal-table tr:hover {
            background: #f8fafc;
        }
        
        /* Status Badges */
        .status-pending {
            background: #fef3c7;
            color: #d97706;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-approved {
            background: #dbeafe;
            color: #2563eb;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-completed {
            background: #d1fae5;
            color: #059669;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-rejected {
            background: #fee2e2;
            color: #dc2626;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        /* Alerts */
        .alert-success {
            background: #d1fae5;
            color: #059669;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #059669;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #dc2626;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #dc2626;
        }
        
        .info-note {
            background: #f1f5f9;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 12px;
            color: #475569;
            margin-top: 15px;
        }
        
        .info-note i {
            color: #517a96;
            margin-right: 8px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #94a3b8;
        }
        
        .empty-state i {
            font-size: 50px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar { width: 85px; padding: 20px 10px; }
            .sidebar .logo span, .sidebar .nav-item span { display: none; }
            .sidebar .nav-item { justify-content: center; padding: 12px; }
            .sidebar .nav-item i { margin-right: 0; }
            .main-content { margin-left: 85px; padding: 20px; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">
        <a href="dashboard.php">VELVET<span>.</span>AURA</a>
    </div>
    
    <a href="dashboard.php" class="nav-item">
        <i class="fas fa-home"></i> <span>Dashboard</span>
    </a>
    <a href="products.php" class="nav-item">
        <i class="fas fa-box"></i> <span>Products</span>
    </a>
    <a href="add-product.php" class="nav-item">
        <i class="fas fa-plus-circle"></i> <span>Add Product</span>
    </a>
    <a href="orders.php" class="nav-item">
        <i class="fas fa-shopping-cart"></i> <span>Orders</span>
    </a>
    <a href="withdrawals.php" class="nav-item active">
        <i class="fas fa-wallet"></i> <span>Withdrawals</span>
    </a>
    <a href="settings.php" class="nav-item">
        <i class="fas fa-cog"></i> <span>Settings</span>
    </a>
    <a href="../logout.php" class="nav-item">
        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
    </a>
</div>

<!-- Main Content -->
<div class="main-content">
    
    <div class="page-header">
        <h1><i class="fas fa-wallet"></i> Withdrawals</h1>
        <p>Request payouts and track your withdrawal history</p>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon"><i class="fas fa-dollar-sign"></i></div>
            <div class="amount">$<?php echo number_format($vendor['balance'], 2); ?></div>
            <div class="label">Available Balance</div>
        </div>
        <div class="stat-card">
            <div class="icon"><i class="fas fa-clock"></i></div>
            <div class="amount">$<?php echo number_format($pending_withdrawals, 2); ?></div>
            <div class="label">Pending Withdrawals</div>
        </div>
        <div class="stat-card">
            <div class="icon"><i class="fas fa-check-circle"></i></div>
            <div class="amount">$<?php echo number_format($total_withdrawn, 2); ?></div>
            <div class="label">Total Withdrawn</div>
        </div>
    </div>
    
    <!-- Request Withdrawal Form -->
    <div class="form-card">
        <h3><i class="fas fa-hand-holding-usd"></i> Request Withdrawal</h3>
        <div class="subtitle">Minimum withdrawal amount: $50</div>
        
        <?php if($success): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-dollar-sign"></i> Amount (USD)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" 
                               placeholder="Enter amount" min="50" max="<?php echo $vendor['balance']; ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-university"></i> Payment Method</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="">Select Method</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="paypal">PayPal</option>
                            <option value="upi">UPI / Google Pay</option>
                            <option value="phonepe">PhonePe</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-info-circle"></i> Payment Details</label>
                <textarea name="payment_details" class="form-control" rows="3" 
                          placeholder="Bank Account Number / PayPal Email / UPI ID" required></textarea>
            </div>
            <button type="submit" name="request_withdrawal" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Request Withdrawal
            </button>
            <div class="info-note">
                <i class="fas fa-info-circle"></i> Withdrawal requests are processed within 2-3 business days after admin approval.
            </div>
        </form>
    </div>
    
    <!-- Withdrawal History -->
    <div class="table-container">
        <h3><i class="fas fa-history"></i> Withdrawal History</h3>
        
        <?php if(mysqli_num_rows($withdrawals) > 0): ?>
        <table class="withdrawal-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($w = mysqli_fetch_assoc($withdrawals)): ?>
                <tr>
                    <td><?php echo date('d M Y, h:i A', strtotime($w['created_at'])); ?></td>
                    <td><strong>$<?php echo number_format($w['amount'], 2); ?></strong></td>
                    <td>
                        <?php echo ucfirst($w['payment_method']); ?>
                        <small class="d-block text-muted"><?php echo htmlspecialchars(substr($w['payment_details'], 0, 30)); ?></small>
                    </td>
                    <td>
                        <span class="status-<?php echo $w['status']; ?>">
                            <?php echo ucfirst($w['status']); ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-receipt"></i>
            <p>No withdrawal requests yet</p>
            <small>Request your first payout above</small>
        </div>
        <?php endif; ?>
    </div>
    
</div>

</body>
</html>