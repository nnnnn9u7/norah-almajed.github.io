<?php
session_start();
require_once 'db_config.php';

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get admin data
$admin_id = $_SESSION['admin_id'];
try {
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        session_destroy();
        header("Location: admin_login.php");
        exit();
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Handle form actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // General Settings
    if (isset($_POST['save_general'])) {
        $site_name = trim($_POST['site_name']);
        $site_email = trim($_POST['site_email']);
        $site_phone = trim($_POST['site_phone']);
        $site_address = trim($_POST['site_address']);
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        
        try {
            // In a real application, you would save these to a settings table
            $message = "General settings updated successfully!";
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "Error updating settings: " . $e->getMessage();
            $message_type = "error";
        }
    }
    
    // Email Settings
    if (isset($_POST['save_email'])) {
        $smtp_host = trim($_POST['smtp_host']);
        $smtp_port = trim($_POST['smtp_port']);
        $smtp_username = trim($_POST['smtp_username']);
        $smtp_password = trim($_POST['smtp_password']);
        $smtp_encryption = $_POST['smtp_encryption'];
        
        try {
            // In a real application, you would save these to a settings table
            $message = "Email settings updated successfully!";
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "Error updating email settings: " . $e->getMessage();
            $message_type = "error";
        }
    }
    
    // Security Settings
    if (isset($_POST['save_security'])) {
        $password_min_length = (int)$_POST['password_min_length'];
        $password_require_numbers = isset($_POST['password_require_numbers']) ? 1 : 0;
        $password_require_special = isset($_POST['password_require_special']) ? 1 : 0;
        $session_timeout = (int)$_POST['session_timeout'];
        $max_login_attempts = (int)$_POST['max_login_attempts'];
        
        try {
            // In a real application, you would save these to a settings table
            $message = "Security settings updated successfully!";
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "Error updating security settings: " . $e->getMessage();
            $message_type = "error";
        }
    }
    
    // Profile Settings
    if (isset($_POST['save_profile'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        try {
            // Verify current password if changing password
            if (!empty($new_password)) {
                if (password_verify($current_password, $admin['password'])) {
                    if ($new_password === $confirm_password) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE admin_users SET full_name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                        $stmt->execute([$full_name, $email, $phone, $hashed_password, $admin_id]);
                        $message = "Profile and password updated successfully!";
                    } else {
                        $message = "New passwords do not match!";
                        $message_type = "error";
                    }
                } else {
                    $message = "Current password is incorrect!";
                    $message_type = "error";
                }
            } else {
                // Update profile without changing password
                $stmt = $pdo->prepare("UPDATE admin_users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $phone, $admin_id]);
                $message = "Profile updated successfully!";
                $message_type = "success";
            }
            
            // Update session data
            $_SESSION['admin_name'] = $full_name;
            
        } catch (PDOException $e) {
            $message = "Error updating profile: " . $e->getMessage();
            $message_type = "error";
        }
    }
    
    // Backup Database
    if (isset($_POST['backup_database'])) {
        try {
            // In a real application, you would implement database backup functionality
            $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $message = "Database backup created successfully: " . $backup_file;
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "Error creating backup: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Get current settings (in a real app, these would come from a settings table)
$current_settings = [
    'site_name' => 'IGM Learning Platform',
    'site_email' => 'admin@igm.com',
    'site_phone' => '+966 12 345 6789',
    'site_address' => 'Riyadh, Saudi Arabia',
    'maintenance_mode' => false,
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => '587',
    'smtp_username' => 'your-email@gmail.com',
    'smtp_encryption' => 'tls',
    'password_min_length' => 8,
    'password_require_numbers' => true,
    'password_require_special' => true,
    'session_timeout' => 60,
    'max_login_attempts' => 5
];

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - IGM Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="js/theme-toggle.js"></script>
    <style>
        :root {
            --primary: #1e5596;
            --secondary: #21a7de;
            --accent: #fcb408;
            --light: #f5f9ff;
            --dark: #1d4c82;
            --success: #7ebb38;
            --danger: #e5313c;
            --warning: #fa8528;
            --info: #17a2b8;
            --admin-dark: #0f2d53;
            --sidebar-width: 250px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        /* Top Navigation Bar */
        .top-nav {
            background-color: var(--admin-dark);
            color: white;
            padding: 12px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 60px;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .menu-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .menu-toggle:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo img {
            height: 35px;
            width: auto;
        }

        .logo-text {
            font-weight: bold;
            font-size: 18px;
        }

        .user-section {
            position: relative;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .user-info:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #fcb408;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }

        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            width: 180px;
            z-index: 1001;
            display: none;
            overflow: hidden;
        }

        .user-dropdown.show {
            display: block;
        }

        .dropdown-item {
            padding: 12px 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
            text-decoration: none;
            transition: background-color 0.3s;
            border-bottom: 1px solid #f0f0f0;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background-color: #f5f7fa;
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
        }

        /* Sidebar */
        .sidebar {
            background-color: var(--admin-dark);
            color: white;
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 60px;
            left: -250px;
            transition: left 0.3s ease;
            z-index: 999;
            overflow-y: auto;
            padding: 20px 0;
        }

        .sidebar.open {
            left: 0;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            padding: 12px 20px;
            margin: 5px 0;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            border-left: 3px solid transparent;
        }

        .nav-item:hover, .nav-item.active {
            background-color: rgba(255,255,255,0.1);
            border-left: 3px solid #fcb408;
        }

        .nav-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .nav-item a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            width: 100%;
        }

        /* Main Content */
        .main-content {
            margin-top: 70px;
            padding: 30px;
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 70px);
        }

        .sidebar-open .main-content {
            margin-left: 250px;
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 998;
            display: none;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eaeaea;
        }

        .page-title {
            font-size: 28px;
            color: var(--admin-dark);
            font-weight: 700;
        }

        .page-actions {
            display: flex;
            gap: 15px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-info {
            background: var(--info);
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-sm {
            padding: 8px 12px;
            font-size: 12px;
        }

        /* Settings Tabs */
        .settings-tabs {
            display: flex;
            background: white;
            border-radius: 12px;
            padding: 10px;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            overflow-x: auto;
        }

        .tab-btn {
            padding: 12px 20px;
            background: none;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
        }

        .tab-btn:hover:not(.active) {
            background: #f0f0f0;
        }

        /* Settings Sections */
        .settings-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-title {
            font-size: 20px;
            color: var(--admin-dark);
            font-weight: 600;
        }

        .section-description {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        /* Forms */
        .form-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        @media (max-width: 768px) {
            .form-container {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--admin-dark);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
            background: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(30, 85, 150, 0.1);
        }

        .form-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        /* Checkboxes and Radio Buttons */
        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .form-check-label {
            font-weight: 500;
            color: #333;
            cursor: pointer;
        }

        /* Switch Toggle */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--success);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        /* System Status Cards */
        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .status-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            text-align: center;
            border-left: 4px solid var(--primary);
        }

        .status-card.good {
            border-left-color: var(--success);
        }

        .status-card.warning {
            border-left-color: var(--warning);
        }

        .status-card.danger {
            border-left-color: var(--danger);
        }

        .status-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .status-card.good .status-icon { color: var(--success); }
        .status-card.warning .status-icon { color: var(--warning); }
        .status-card.danger .status-icon { color: var(--danger); }

        .status-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--admin-dark);
            margin: 10px 0 5px;
        }

        .status-label {
            color: #666;
            font-size: 14px;
            font-weight: 600;
        }

        /* Backup Section */
        .backup-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
            border: 2px dashed #ddd;
        }

        .backup-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Danger Zone */
        .danger-zone {
            background: #fee;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
            border: 2px solid var(--danger);
        }

        .danger-zone .section-title {
            color: var(--danger);
        }

        /* Messages */
        .alert {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #f0f9f4;
            color: var(--success);
            border: 1px solid #d1f0e0;
        }

        .alert-error {
            background-color: #fee;
            color: var(--danger);
            border: 1px solid #fdd;
        }

        /* Test Button */
        .test-btn {
            background: var(--info);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar.open {
                left: 0;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
            
            .sidebar-open .main-content {
                margin-left: 0;
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .page-actions {
                width: 100%;
                justify-content: flex-start;
            }
            
            .settings-tabs {
                flex-wrap: wrap;
            }
            
            .status-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .backup-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 20px 15px;
            }
            
            .logo-text {
                display: none;
            }
            
            .user-details {
                display: none;
            }
            
            .status-cards {
                grid-template-columns: 1fr;
            }
            
            .settings-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <div class="top-nav">
        <div class="nav-left">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">
                <img src="https://framerusercontent.com/images/hOzQ20FaHtHA2R9E2cxsUJ47M.png?width=67&height=74" alt="IGM Logo">
                <span class="logo-text">IGM Admin</span>
            </div>
        </div>
        
        <div class="user-section">
            <!-- Theme Toggle Button -->
            <button class="theme-toggle" id="themeToggle">
                <i class="fas fa-moon"></i>
                <span id="themeLabel">Dark</span>
            </button>
            
            <div class="user-info" id="userInfo">
                <div class="user-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($admin['full_name']); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars($admin['role']); ?></div>
                </div>
                <i class="fas fa-chevron-down" style="font-size: 12px;"></i>
            </div>
            
            <div class="user-dropdown" id="userDropdown">
                <a href="admin_dashboard.php" class="dropdown-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <form method="POST" style="display: contents;">
                    <button type="submit" name="logout" class="dropdown-item" style="background: none; border: none; width: 100%; text-align: left; cursor: pointer;">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="admin_dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_students.php">
                    <i class="fas fa-users"></i>
                    <span class="nav-text">Students Management</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_hackathons.php">
                    <i class="fas fa-code"></i>
                    <span class="nav-text">Hackathons</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_courses.php">
                    <i class="fas fa-book"></i>
                    <span class="nav-text">Courses</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_complaints.php">
                    <i class="fas fa-comments"></i>
                    <span class="nav-text">Complaints & Support</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_exams.php">
                    <i class="fas fa-file-alt"></i>
                    <span class="nav-text">Exam Results</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_reports.php">
                    <i class="fas fa-chart-bar"></i>
                    <span class="nav-text">Reports & Analytics</span>
                </a>
            </li>
            <li class="nav-item active">
                <a href="admin_settings.php">
                    <i class="fas fa-cog"></i>
                    <span class="nav-text">System Settings</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content" id="mainContent">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-cog"></i> System Settings
            </h1>
            <div class="page-actions">
                <a href="admin_dashboard.php" class="btn" style="background: #6c757d; color: white;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check' : 'exclamation'; ?>-circle"></i> 
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- System Status -->
        <div class="status-cards">
            <div class="status-card good">
                <div class="status-icon">
                    <i class="fas fa-server"></i>
                </div>
                <div class="status-value">Online</div>
                <div class="status-label">System Status</div>
            </div>
            <div class="status-card good">
                <div class="status-icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="status-value">Healthy</div>
                <div class="status-label">Database</div>
            </div>
            <div class="status-card good">
                <div class="status-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="status-value">Secure</div>
                <div class="status-label">Security</div>
            </div>
            <div class="status-card warning">
                <div class="status-icon">
                    <i class="fas fa-hdd"></i>
                </div>
                <div class="status-value">75%</div>
                <div class="status-label">Storage Used</div>
            </div>
        </div>

        <!-- Settings Tabs -->
        <div class="settings-tabs">
            <button class="tab-btn active" onclick="showTab('general')">
                <i class="fas fa-sliders-h"></i> General
            </button>
            <button class="tab-btn" onclick="showTab('email')">
                <i class="fas fa-envelope"></i> Email
            </button>
            <button class="tab-btn" onclick="showTab('security')">
                <i class="fas fa-shield-alt"></i> Security
            </button>
            <button class="tab-btn" onclick="showTab('profile')">
                <i class="fas fa-user-cog"></i> Profile
            </button>
            <button class="tab-btn" onclick="showTab('backup')">
                <i class="fas fa-database"></i> Backup
            </button>
        </div>

        <!-- General Settings -->
        <div class="settings-section" id="general-tab">
            <div class="section-header">
                <div>
                    <h3 class="section-title">General Settings</h3>
                    <p class="section-description">Configure basic system settings and preferences</p>
                </div>
            </div>
            
            <form method="POST">
                <div class="form-container">
                    <div class="form-group">
                        <label class="form-label">Site Name</label>
                        <input type="text" class="form-control" name="site_name" value="<?php echo htmlspecialchars($current_settings['site_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Site Email</label>
                        <input type="email" class="form-control" name="site_email" value="<?php echo htmlspecialchars($current_settings['site_email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Contact Phone</label>
                        <input type="text" class="form-control" name="site_phone" value="<?php echo htmlspecialchars($current_settings['site_phone']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Site Address</label>
                        <textarea class="form-control" name="site_address" rows="3"><?php echo htmlspecialchars($current_settings['site_address']); ?></textarea>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="maintenance_mode" id="maintenance_mode" <?php echo $current_settings['maintenance_mode'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="maintenance_mode">
                            Enable Maintenance Mode
                        </label>
                    </div>
                    <div class="form-text">
                        When enabled, the site will be unavailable to regular users and display a maintenance message.
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <button type="submit" name="save_general" class="btn btn-success">
                        <i class="fas fa-save"></i> Save General Settings
                    </button>
                </div>
            </form>
        </div>

        <!-- Email Settings -->
        <div class="settings-section" id="email-tab" style="display: none;">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Email Settings</h3>
                    <p class="section-description">Configure SMTP settings for system emails</p>
                </div>
            </div>
            
            <form method="POST">
                <div class="form-container">
                    <div class="form-group">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" class="form-control" name="smtp_host" value="<?php echo htmlspecialchars($current_settings['smtp_host']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">SMTP Port</label>
                        <input type="number" class="form-control" name="smtp_port" value="<?php echo htmlspecialchars($current_settings['smtp_port']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">SMTP Username</label>
                        <input type="text" class="form-control" name="smtp_username" value="<?php echo htmlspecialchars($current_settings['smtp_username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">SMTP Password</label>
                        <input type="password" class="form-control" name="smtp_password" placeholder="Enter SMTP password">
                        <div class="form-text">Leave blank to keep current password</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Encryption</label>
                        <select class="form-control" name="smtp_encryption" required>
                            <option value="none" <?php echo $current_settings['smtp_encryption'] === 'none' ? 'selected' : ''; ?>>None</option>
                            <option value="ssl" <?php echo $current_settings['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            <option value="tls" <?php echo $current_settings['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <button type="submit" name="save_email" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Email Settings
                    </button>
                    <button type="button" class="test-btn" onclick="testEmailSettings()">
                        <i class="fas fa-paper-plane"></i> Test Email
                    </button>
                </div>
            </form>
        </div>

        <!-- Security Settings -->
        <div class="settings-section" id="security-tab" style="display: none;">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Security Settings</h3>
                    <p class="section-description">Configure security policies and access controls</p>
                </div>
            </div>
            
            <form method="POST">
                <div class="form-container">
                    <div class="form-group">
                        <label class="form-label">Minimum Password Length</label>
                        <input type="number" class="form-control" name="password_min_length" value="<?php echo $current_settings['password_min_length']; ?>" min="6" max="20" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Session Timeout (minutes)</label>
                        <input type="number" class="form-control" name="session_timeout" value="<?php echo $current_settings['session_timeout']; ?>" min="15" max="480" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Max Login Attempts</label>
                        <input type="number" class="form-control" name="max_login_attempts" value="<?php echo $current_settings['max_login_attempts']; ?>" min="3" max="10" required>
                        <div class="form-text">Number of failed login attempts before account lockout</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="password_require_numbers" id="password_require_numbers" <?php echo $current_settings['password_require_numbers'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="password_require_numbers">
                            Require numbers in passwords
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="password_require_special" id="password_require_special" <?php echo $current_settings['password_require_special'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="password_require_special">
                            Require special characters in passwords
                        </label>
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <button type="submit" name="save_security" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Security Settings
                    </button>
                </div>
            </form>
        </div>

        <!-- Profile Settings -->
        <div class="settings-section" id="profile-tab" style="display: none;">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Profile Settings</h3>
                    <p class="section-description">Update your personal information and password</p>
                </div>
            </div>
            
            <form method="POST">
                <div class="form-container">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-container" style="margin-top: 20px;">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" placeholder="Enter current password to change">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" placeholder="Enter new password">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" placeholder="Confirm new password">
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <button type="submit" name="save_profile" class="btn btn-success">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </div>
            </form>
        </div>

        <!-- Backup Settings -->
        <div class="settings-section" id="backup-tab" style="display: none;">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Backup & Restore</h3>
                    <p class="section-description">Manage database backups and system restoration</p>
                </div>
            </div>
            
            <div class="backup-section">
                <h4 style="margin-bottom: 20px; color: var(--admin-dark);">Database Backup</h4>
                <p style="margin-bottom: 20px; color: #666;">
                    Create a backup of your database. This will download a SQL file containing all your data.
                </p>
                
                <form method="POST">
                    <div class="backup-actions">
                        <button type="submit" name="backup_database" class="btn btn-primary">
                            <i class="fas fa-download"></i> Create Backup Now
                        </button>
                        <button type="button" class="btn btn-info" onclick="scheduleBackup()">
                            <i class="fas fa-clock"></i> Schedule Automatic Backup
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="danger-zone">
                <div class="section-header">
                    <div>
                        <h3 class="section-title">Danger Zone</h3>
                        <p class="section-description">Critical operations that cannot be undone</p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-warning" onclick="clearCache()">
                        <i class="fas fa-broom"></i> Clear System Cache
                    </button>
                    <button type="button" class="btn btn-danger" onclick="resetSystem()">
                        <i class="fas fa-redo"></i> Reset to Defaults
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mainContent = document.getElementById('mainContent');
        const userInfo = document.getElementById('userInfo');
        const userDropdown = document.getElementById('userDropdown');
        const body = document.body;

        // Toggle sidebar
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            body.classList.toggle('sidebar-open');
            sidebarOverlay.classList.toggle('show');
        });

        // Close sidebar when clicking on overlay
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            body.classList.remove('sidebar-open');
            sidebarOverlay.classList.remove('show');
        });

        // Toggle user dropdown
        userInfo.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            userDropdown.classList.remove('show');
        });

        // Prevent closing when clicking on dropdown
        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Tab switching functionality
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.settings-section').forEach(tab => {
                tab.style.display = 'none';
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab and activate button
            document.getElementById(tabName + '-tab').style.display = 'block';
            event.target.classList.add('active');
        }

        // Test email settings
        function testEmailSettings() {
            alert('Testing email configuration... This would send a test email in a real application.');
            // In a real application, this would make an AJAX call to test email settings
        }

        // Schedule backup
        function scheduleBackup() {
            const frequency = prompt('Enter backup frequency (daily, weekly, monthly):', 'weekly');
            if (frequency) {
                alert(`Automatic backup scheduled ${frequency}. This feature would be implemented in a real application.`);
            }
        }

        // Clear cache
        function clearCache() {
            if (confirm('Are you sure you want to clear all system cache? This may temporarily affect performance.')) {
                alert('System cache cleared successfully!');
                // In a real application, this would clear cache files
            }
        }

        // Reset system
        function resetSystem() {
            if (confirm('⚠️ DANGER: This will reset all system settings to defaults. This action cannot be undone! Are you absolutely sure?')) {
                if (confirm('This will delete all your custom settings. Please type "RESET" to confirm:')) {
                    alert('System reset initiated. This feature would reset settings in a real application.');
                }
            }
        }

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebarOverlay.classList.remove('show');
            }
        });

        // Add active state to navigation items
        document.querySelectorAll('.nav-item a').forEach(link => {
            if (link.href === window.location.href) {
                link.parentElement.classList.add('active');
            }
            
            link.addEventListener('click', function() {
                document.querySelectorAll('.nav-item').forEach(item => {
                    item.classList.remove('active');
                });
                this.parentElement.classList.add('active');
                
                // On mobile, close sidebar after clicking a link
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('open');
                    body.classList.remove('sidebar-open');
                    sidebarOverlay.classList.remove('show');
                }
            });
        });

        // Password strength indicator (for profile tab)
        document.addEventListener('DOMContentLoaded', function() {
            const newPassword = document.querySelector('input[name="new_password"]');
            if (newPassword) {
                newPassword.addEventListener('input', function() {
                    const password = this.value;
                    const strength = checkPasswordStrength(password);
                    // You could add a visual strength indicator here
                });
            }
        });

        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            return strength;
        }

        // Auto-save functionality (optional)
        let autoSaveTimer;
        document.addEventListener('input', function(e) {
            if (e.target.matches('.form-control')) {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => {
                    // In a real application, you could implement auto-save here
                    console.log('Auto-saving...');
                }, 2000);
            }
        });
    </script>
</body>
</html>