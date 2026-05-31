<?php
session_start();

// Check if admin is logged in (except login page)
$current_page = basename($_SERVER['PHP_SELF']);
$allowed_pages = ['login.php'];
if(!isset($_SESSION['admin_id']) && !in_array($current_page, $allowed_pages)) {
    header('Location: login.php');
    exit();
}

// Get admin name from session
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_initial = strtoupper(substr($admin_name, 0, 1));

// Get unread messages count
$unread_count = 0;
if(file_exists('../backend/config/database.php')) {
    require_once '../backend/config/database.php';
    $unread_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM contacts WHERE is_read = 0");
    if($unread_result) {
        $unread = mysqli_fetch_assoc($unread_result);
        $unread_count = $unread['count'] ?? 0;
    }
}

// Get low stock count for badge
$low_badge_count = 0;
if(isset($conn)) {
    $low_badge = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE stock_quantity < 5 AND stock_quantity > 0"));
    $low_badge_count = $low_badge['count'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin Panel'; ?> - Velvet Aura Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f8;
            overflow-x: hidden;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            color: #fff;
            position: fixed;
            height: 100vh;
            display: flex;
            flex-direction: column;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            overflow: hidden;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar.collapsed {
            width: 85px;
        }
        
        .sidebar.collapsed .logo-text,
        .sidebar.collapsed .user-info span,
        .sidebar.collapsed .nav-link span,
        .sidebar.collapsed .sidebar-footer span,
        .sidebar.collapsed .nav-section-title {
            display: none;
        }
        
        .sidebar.collapsed .logo {
            justify-content: center;
            padding: 20px 0;
        }
        
        .sidebar.collapsed .user-avatar {
            margin: 0 auto;
        }
        
        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 12px;
        }
        
        .sidebar.collapsed .nav-link i {
            margin-right: 0;
            font-size: 20px;
        }
        
        .sidebar.collapsed .sidebar-footer .nav-link {
            justify-content: center;
        }
        
        .sidebar.collapsed .nav-links-wrapper {
            padding: 0 10px;
        }
        
        .toggle-sidebar {
            position: absolute;
            right: -12px;
            top: 30px;
            width: 26px;
            height: 26px;
            background: #517a96;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            font-size: 11px;
            transition: all 0.3s;
            z-index: 1001;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .toggle-sidebar:hover {
            transform: scale(1.1);
            background: #6b9fbf;
        }
        
        .sidebar.collapsed .toggle-sidebar i {
            transform: rotate(180deg);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 20px 20px;
            border-bottom: 1px solid rgba(81, 122, 150, 0.3);
            flex-shrink: 0;
        }
        
        .logo-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #517a96, #6b9fbf);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: logoGlow 2s infinite;
        }
        
        @keyframes logoGlow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(81, 122, 150, 0.4); }
            50% { box-shadow: 0 0 0 6px rgba(81, 122, 150, 0); }
        }
        
        .logo-icon i {
            font-size: 20px;
            color: white;
        }
        
        .logo-text {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 1px;
        }
        
        .logo-text span:first-child {
            color: white;
        }
        
        .logo-text span.dot {
            color: #517a96;
            font-size: 22px;
        }
        
        .logo-text span:last-child {
            color: #517a96;
            font-weight: 500;
        }
        
        .user-info {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(81, 122, 150, 0.2);
            flex-shrink: 0;
        }
        
        .user-avatar {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, #517a96, #6b9fbf);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 22px;
            font-weight: 700;
            color: white;
            box-shadow: 0 4px 12px rgba(81, 122, 150, 0.3);
            transition: all 0.3s;
        }
        
        .user-info:hover .user-avatar {
            transform: scale(1.05);
        }
        
        .user-name {
            font-weight: 700;
            margin-bottom: 3px;
            font-size: 14px;
        }
        
        .user-role {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .nav-links-wrapper {
            flex: 1;
            overflow-y: auto;
            padding: 10px 15px;
            margin-bottom: 5px;
        }
        
        .nav-links-wrapper::-webkit-scrollbar {
            width: 4px;
        }
        
        .nav-links-wrapper::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 10px;
        }
        
        .nav-links-wrapper::-webkit-scrollbar-thumb {
            background: #517a96;
            border-radius: 10px;
        }
        
        .nav-links {
            padding-bottom: 10px;
        }
        
        .nav-section {
            margin-bottom: 20px;
        }
        
        .nav-section-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(255, 255, 255, 0.4);
            padding: 0 10px;
            margin-bottom: 10px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 10px;
            margin: 3px 0;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 0;
            height: 100%;
            background: rgba(81, 122, 150, 0.15);
            transition: width 0.3s ease;
            border-radius: 10px;
        }
        
        .nav-link:hover::before {
            width: 100%;
        }
        
        .nav-link i {
            width: 22px;
            font-size: 16px;
            position: relative;
            z-index: 1;
        }
        
        .nav-link span {
            position: relative;
            z-index: 1;
        }
        
        .nav-link:hover {
            color: white;
            transform: translateX(3px);
        }
        
        .nav-link.active {
            background: #517a96;
            color: white;
            box-shadow: 0 2px 8px rgba(81, 122, 150, 0.3);
        }
        
        .nav-link.active i {
            color: white;
        }
        
        .nav-badge {
            margin-left: auto;
            background: #d97706;
            color: white;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 20px;
            position: relative;
            z-index: 1;
        }
        
        .sidebar-footer {
            padding: 15px 15px 20px 15px;
            border-top: 1px solid rgba(81, 122, 150, 0.2);
            background: rgba(15, 23, 42, 0.95);
            flex-shrink: 0;
        }
        
        .sidebar-footer .nav-link {
            margin: 0;
            justify-content: flex-start;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 20px 25px;
            min-height: 100vh;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .main-content.expanded {
            margin-left: 85px;
        }
        
        /* Top Navbar */
        .top-navbar {
            background: white;
            border-radius: 20px;
            padding: 12px 25px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .page-title h1 {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            color: #1e293b;
        }
        
        .page-title p {
            color: #64748b;
            margin: 0;
            font-size: 13px;
        }
        
        .right-icons {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        /* Create Vendor Button in Header */
        .btn-create-vendor {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 8px 20px;
            border-radius: 40px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .btn-create-vendor:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
            color: white;
            text-decoration: none;
        }
        
        .icon-btn {
            position: relative;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            border-radius: 50%;
            color: #475569;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
        }
        
        .icon-btn:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
            color: #517a96;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .admin-dropdown {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 6px 15px 6px 8px;
            border-radius: 50px;
            background: #f1f5f9;
            transition: all 0.3s;
            position: relative;
        }
        
        .admin-dropdown:hover {
            background: #e2e8f0;
        }
        
        .admin-avatar-small {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #517a96, #6b9fbf);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
        }
        
        .admin-info-small {
            text-align: left;
        }
        
        .admin-info-small .name {
            font-weight: 600;
            font-size: 13px;
            color: #1e293b;
        }
        
        .admin-info-small .role {
            font-size: 10px;
            color: #64748b;
        }
        
        .dropdown-menu-custom {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 10px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s;
            z-index: 100;
        }
        
        .admin-dropdown:hover .dropdown-menu-custom {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-item-custom {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            text-decoration: none;
            color: #475569;
            transition: all 0.2s;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .dropdown-item-custom:last-child {
            border-bottom: none;
        }
        
        .dropdown-item-custom:hover {
            background: #f8fafc;
            color: #517a96;
        }
        
        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            border-radius: 20px;
            padding: 20px 25px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .welcome-text h3 {
            color: white;
            margin-bottom: 5px;
            font-size: 18px;
        }
        
        .welcome-text p {
            color: rgba(255, 255, 255, 0.6);
            margin: 0;
            font-size: 13px;
        }
        
        .welcome-text i {
            color: #517a96;
            margin-right: 5px;
        }
        
        .date-badge {
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 16px;
            border-radius: 40px;
            font-size: 13px;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            transition: all 0.3s ease;
            border: 1px solid #eef2f6;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #517a96, #6b9fbf);
            transform: scaleX(0);
            transition: transform 0.4s ease;
            transform-origin: left;
        }
        
        .stat-card:hover::before {
            transform: scaleX(1);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(81, 122, 150, 0.12);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            background: rgba(81, 122, 150, 0.1);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .stat-icon i {
            font-size: 24px;
            color: #517a96;
        }
        
        .stat-card h3 {
            font-size: 28px;
            font-weight: 800;
            margin: 0;
            color: #1e293b;
        }
        
        .stat-card p {
            color: #64748b;
            margin: 5px 0 0;
            font-size: 13px;
        }
        
        /* Table Container */
        .table-container {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            margin-bottom: 25px;
        }
        
        .table-header {
            padding: 18px 22px;
            border-bottom: 1px solid #eef2f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .table-header h2, .table-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .table-header h2 i, .table-header h3 i {
            color: #517a96;
            margin-right: 8px;
        }
        
        .table {
            margin: 0;
        }
        
        .table th {
            background: #f8fafc;
            font-weight: 600;
            padding: 14px 18px;
            color: #475569;
            font-size: 13px;
            border-bottom: 1px solid #eef2f6;
        }
        
        .table td {
            padding: 14px 18px;
            vertical-align: middle;
            color: #334155;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .table tr {
            transition: all 0.2s;
        }
        
        .table tr:hover {
            background: #f8fafc;
            transform: translateX(3px);
        }
        
        /* Product Image */
        .product-img {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .product-img:hover {
            transform: scale(1.1);
        }
        
        /* Buttons */
        .btn-edit {
            background: #3b82f6;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn-edit:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        
        .btn-view {
            background: #10b981;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        
        .btn-view:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        .btn-add {
            background: linear-gradient(135deg, #517a96, #6b9fbf);
            color: white;
            padding: 10px 22px;
            border-radius: 40px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(81, 122, 150, 0.3);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #517a96, #6b9fbf);
            color: white;
            padding: 12px 30px;
            border-radius: 40px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(81, 122, 150, 0.3);
        }
        
        .btn-back {
            background: #e2e8f0;
            color: #475569;
            padding: 12px 30px;
            border-radius: 40px;
            text-decoration: none;
            margin-right: 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-back:hover {
            background: #cbd5e1;
            transform: translateY(-2px);
        }
        
        /* Form Container */
        .form-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 22px;
        }
        
        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            color: #1e293b;
            font-size: 13px;
        }
        
        .form-group label i {
            color: #517a96;
            margin-right: 6px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #517a96;
            outline: none;
            box-shadow: 0 0 0 3px rgba(81, 122, 150, 0.1);
        }
        
        /* Badges */
        .badge-pending, .badge-processing, .badge-shipped, .badge-delivered, .badge-cancelled {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-processing { background: #dbeafe; color: #2563eb; }
        .badge-shipped { background: #cff4fc; color: #0891b2; }
        .badge-delivered { background: #d1fae5; color: #059669; }
        .badge-cancelled { background: #fee2e2; color: #dc2626; }
        .badge-read { background: #d1fae5; color: #059669; padding: 4px 12px; border-radius: 20px; font-size: 11px; }
        .badge-unread { background: #fee2e2; color: #dc2626; padding: 4px 12px; border-radius: 20px; font-size: 11px; }
        
        .stars i {
            color: #fbbf24;
            margin-right: 2px;
        }
        
        /* Notification Toast */
        .notification-toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #1e293b;
            color: white;
            padding: 12px 22px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateX(450px);
            transition: transform 0.3s ease;
            z-index: 1100;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            border-left: 3px solid #517a96;
        }
        
        .notification-toast.show {
            transform: translateX(0);
        }
        
        /* Modal */
        .modal-content {
            border-radius: 20px;
            border: none;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 18px 22px;
        }
        
        .modal-header .btn-close {
            filter: invert(1);
        }
        
        .modal-body {
            padding: 22px;
        }
        
        .modal-footer {
            border-top: 1px solid #eef2f6;
            padding: 18px 22px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar { width: 85px; }
            .sidebar .logo-text, .sidebar .user-info span, .sidebar .nav-link span, 
            .sidebar .sidebar-footer span, .sidebar .nav-section-title { display: none; }
            .sidebar .logo { justify-content: center; padding: 20px 0; }
            .sidebar .user-avatar { margin: 0 auto; }
            .sidebar .nav-link { justify-content: center; padding: 12px; }
            .sidebar .nav-link i { margin-right: 0; }
            .main-content { margin-left: 85px; }
            .toggle-sidebar { display: none; }
        }
        
        @media (max-width: 768px) {
            .main-content { padding: 15px; }
            .top-navbar { flex-direction: column; gap: 15px; }
            .right-icons { width: 100%; justify-content: center; }
            .welcome-banner { flex-direction: column; text-align: center; }
            .table-header { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="toggle-sidebar" onclick="toggleSidebar()">
        <i class="fa-solid fa-chevron-left"></i>
    </div>
    
    <div class="logo">
        <div class="logo-icon">
            <i class="fa-solid fa-crown"></i>
        </div>
        <div class="logo-text">
            <span>VELVET</span><span class="dot">.</span><span>AURA</span>
        </div>
    </div>
    
    <div class="user-info">
        <div class="user-avatar">
            <?php echo $admin_initial; ?>
        </div>
        <div class="user-name"><?php echo htmlspecialchars($admin_name); ?></div>
        <div class="user-role">Administrator</div>
    </div>
    
    <div class="nav-links-wrapper">
        <div class="nav-links">
            <div class="nav-section">
                <div class="nav-section-title">MAIN</div>
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span>
                </a>
                <a href="products.php" class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['products.php', 'add-product.php', 'edit-product.php']) ? 'active' : ''; ?>">
                    <i class="fa-solid fa-box"></i> <span>Products</span>
                </a>
                <a href="orders.php" class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['orders.php', 'order-detail.php']) ? 'active' : ''; ?>">
                    <i class="fa-solid fa-truck-fast"></i> <span>Orders</span>
                </a>
                <a href="categories.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-tags"></i> <span>Categories</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">MANAGEMENT</div>
                <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-users"></i> <span>Users</span>
                </a>
                <a href="reviews.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-star"></i> <span>Reviews</span>
                </a>
                <a href="contacts.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contacts.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-envelope"></i> <span>Messages</span>
                    <?php if($unread_count > 0): ?>
                    <span class="nav-badge" style="background: #ef4444;"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="low-stock.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'low-stock.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-line"></i> <span>Low Stock</span>
                    <?php if($low_badge_count > 0): ?>
                    <span class="nav-badge"><?php echo $low_badge_count; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            
            <!-- VENDOR SECTION -->
            <div class="nav-section">
                <div class="nav-section-title">VENDORS</div>
                
                <a href="/velvet-aura/admin/vendors.php" class="nav-link">
                    <i class="fa-solid fa-store"></i> <span>All Vendors</span>
                </a>
                
                <a href="/velvet-aura/admin/create-vendor.php" class="nav-link">
                    <i class="fa-solid fa-user-plus"></i> <span>Create Vendor</span>
                </a>
                
                <a href="/velvet-aura/admin/vendor-products.php" class="nav-link">
                    <i class="fa-solid fa-box"></i> <span>Vendor Products</span>
                </a>
                
            </div>
        </div>
    </div>
    
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link">
            <i class="fa-solid fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">

<!-- Top Navbar -->
<div class="top-navbar">
    <div class="page-title">
        <h1><?php echo $page_title ?? 'Dashboard'; ?></h1>
        <p><i class="fa-regular fa-calendar"></i> <?php echo date('l, F j, Y'); ?></p>
    </div>
    
    <div class="right-icons">
        <!-- CREATE VENDOR BUTTON - HEADER MEIN -->
        <a href="/velvet-aura/admin/create-vendor.php" class="btn-create-vendor">
            <i class="fa-solid fa-user-plus"></i> Create Vendor
        </a>
        
        <div class="icon-btn">
            <i class="fa-regular fa-bell"></i>
        </div>
        
        <div class="admin-dropdown">
            <div class="admin-avatar-small">
                <?php echo $admin_initial; ?>
            </div>
            <div class="admin-info-small">
                <div class="name"><?php echo htmlspecialchars($admin_name); ?></div>
                <div class="role">Administrator</div>
            </div>
            <i class="fa-solid fa-chevron-down" style="font-size: 11px; color: #94a3b8;"></i>
            
            <div class="dropdown-menu-custom">
                <a href="logout.php" class="dropdown-item-custom">
                    <i class="fa-solid fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Welcome Banner -->
<div class="welcome-banner">
    <div class="welcome-text">
        <h3><i class="fa-regular fa-hand-wave"></i> Welcome back, <?php echo htmlspecialchars($admin_name); ?>!</h3>
        <p>Here's what's happening with your store today.</p>
    </div>
    <div class="date-badge">
        <i class="fa-regular fa-calendar-check"></i> <?php echo date('F j, Y'); ?>
    </div>
</div>