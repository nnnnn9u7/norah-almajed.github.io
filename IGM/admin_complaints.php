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
    if (isset($_POST['update_status'])) {
        $complaint_id = $_POST['complaint_id'];
        $status = $_POST['status'];
        $admin_response = trim($_POST['admin_response']);
        
        try {
            // First get user_id before updating
            $stmt = $pdo->prepare("SELECT user_id FROM contact_messages WHERE id = ?");
            $stmt->execute([$complaint_id]);
            $complaint_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_id = $complaint_data['user_id'];
            
            // Update complaint status
            $stmt = $pdo->prepare("UPDATE contact_messages SET status = ?, admin_response = ?, responded_by = ?, responded_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $admin_response, $admin_id, $complaint_id]);
            
            $message = "Complaint status updated successfully!";
            $message_type = "success";
            
            // Send notification to user about complaint response
            if (!empty($admin_response) && $user_id) {
                // You can activate the notification system here when ready
                // $notificationManager->notifyComplaintResponse($user_id, $complaint_id);
                
                // Temporary alternative: Insert notification into notifications table
                try {
                    $notification_message = "Your complaint has been responded to by admin. Status: " . ucfirst(str_replace('_', ' ', $status));
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, 'Complaint Response', ?, 'complaint_response', ?)");
                    $stmt->execute([$user_id, $notification_message, $complaint_id]);
                } catch (PDOException $e) {
                    // Ignore notification errors if table doesn't exist yet
                    error_log("Notification error: " . $e->getMessage());
                }
            }
            
        } catch (PDOException $e) {
            $message = "Error updating complaint: " . $e->getMessage();
            $message_type = "error";
        }
    }
    
    if (isset($_POST['delete_complaint'])) {
        $complaint_id = $_POST['complaint_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->execute([$complaint_id]);
            
            $message = "Complaint deleted successfully!";
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "Error deleting complaint: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search_term = $_GET['search'] ?? '';

// Build query
$query = "
    SELECT cm.*, u.full_name as user_full_name, u.email as user_email, u.phone as user_phone, 
           a.username as responded_by_name 
    FROM contact_messages cm 
    LEFT JOIN users u ON cm.user_id = u.id 
    LEFT JOIN admin_users a ON cm.responded_by = a.id 
    WHERE 1=1
";

$params = [];

if ($status_filter !== 'all') {
    $query .= " AND cm.status = ?";
    $params[] = $status_filter;
}

if (!empty($search_term)) {
    $query .= " AND (cm.full_name LIKE ? OR cm.email LIKE ? OR cm.notes LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $search_like = "%$search_term%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
}

$query .= " ORDER BY cm.created_at DESC";

// Get complaints
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $total_complaints = $pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
    $pending_complaints = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'pending'")->fetchColumn();
    $resolved_complaints = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'resolved'")->fetchColumn();
    $in_progress_complaints = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'in_progress'")->fetchColumn();
    
} catch (PDOException $e) {
    $complaints = [];
    $total_complaints = 0;
    $pending_complaints = 0;
    $resolved_complaints = 0;
    $in_progress_complaints = 0;
    error_log("Database error in admin_complaints: " . $e->getMessage());
}

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
    <title>Complaints & Support - IGM Admin</title>
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

        /* Stats Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            text-align: center;
            border-top: 4px solid var(--primary);
        }
        
        .stat-card:nth-child(2) { border-top-color: var(--warning); }
        .stat-card:nth-child(3) { border-top-color: var(--info); }
        .stat-card:nth-child(4) { border-top-color: var(--success); }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--admin-dark);
            margin: 10px 0 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            font-weight: 600;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }

        .filter-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--admin-dark);
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
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

        /* Tables */
        .data-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th, table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        table th {
            background-color: var(--light);
            color: var(--dark);
            font-weight: 600;
            font-size: 14px;
        }

        table tr:hover {
            background-color: #fafafa;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            color: white;
        }

        .badge-success { background: var(--success); }
        .badge-primary { background: var(--primary); }
        .badge-warning { background: var(--warning); }
        .badge-danger { background: var(--danger); }
        .badge-info { background: var(--info); }

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

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1100;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--admin-dark);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #999;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        /* Complaint Details */
        .complaint-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .complaint-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .user-details h4 {
            margin: 0 0 5px 0;
            color: var(--admin-dark);
        }

        .user-details p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        .complaint-meta {
            text-align: right;
        }

        .complaint-date {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .complaint-content {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary);
        }

        .response-section {
            background: #f0f9f4;
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid var(--success);
        }

        .response-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .response-header h5 {
            margin: 0;
            color: var(--admin-dark);
        }

        .response-date {
            color: #666;
            font-size: 12px;
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
            
            .filter-form {
                grid-template-columns: 1fr;
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
            
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .complaint-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .complaint-meta {
                text-align: left;
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
            
            table {
                font-size: 12px;
            }
            
            table th, table td {
                padding: 10px 8px;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
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
                <img src="img/logo.png" alt="IGM Logo">
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
                <a href="admin_students.php">
                    <i class="fas fa-users"></i>
                    <span>Students Management</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_hackathons.php">
                    <i class="fas fa-code"></i>
                    <span>Hackathons</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_courses.php">
                    <i class="fas fa-book"></i>
                    <span>Courses</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_complaints.php">
                    <i class="fas fa-comments"></i>
                    <span>Complaints & Support</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_performance.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Performance Tracking</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="aboutus.php">
                    <i class="fas fa-info-circle"></i>
                    <span>About Us</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="contactus.php">
                    <i class="fas fa-headset"></i>
                    <span>Contact Us</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-comments"></i> Complaints & Support
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

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_complaints; ?></div>
                <div class="stat-label">Total Complaints</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $pending_complaints; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $in_progress_complaints; ?></div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $resolved_complaints; ?></div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label class="form-label">Status Filter</label>
                    <select class="form-control" name="status">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Search by name, email, or message...">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Complaints Table -->
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($complaints)): ?>
                        <?php foreach ($complaints as $complaint): 
                            $status_badge = [
                                'pending' => 'badge-warning',
                                'in_progress' => 'badge-info', 
                                'resolved' => 'badge-success'
                            ][$complaint['status']] ?? 'badge-secondary';
                            
                            // Determine user name to display
                            $user_name = !empty($complaint['full_name']) ? $complaint['full_name'] : 
                                       (!empty($complaint['user_full_name']) ? $complaint['user_full_name'] : 'Unknown User');
                            $user_email = !empty($complaint['email']) ? $complaint['email'] : 
                                        (!empty($complaint['user_email']) ? $complaint['user_email'] : 'No email');
                            $message_preview = !empty($complaint['notes']) ? 
                                (strlen($complaint['notes']) > 100 ? 
                                    substr($complaint['notes'], 0, 100) . '...' : 
                                    $complaint['notes']) : 
                                'No message';
                        ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="user-avatar-small">
                                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($user_name); ?></strong>
                                        <div style="font-size: 12px; color: #666;">
                                            <?php echo htmlspecialchars($user_email); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="max-width: 300px;">
                                    <?php echo htmlspecialchars($message_preview); ?>
                                </div>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($complaint['created_at'])); ?>
                                <div style="font-size: 12px; color: #666;">
                                    <?php echo date('g:i A', strtotime($complaint['created_at'])); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?php echo $status_badge; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <button class="btn btn-primary btn-sm" onclick="viewComplaint(<?php echo $complaint['id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                                        <button type="submit" name="delete_complaint" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this complaint?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #666;">
                                <i class="fas fa-comments" style="font-size: 48px; margin-bottom: 15px; color: #ddd;"></i>
                                <p>No complaints found</p>
                                <p><?php echo empty($search_term) ? 'All complaints are handled!' : 'Try adjusting your filters'; ?></p>
                                <?php if (empty($search_term) && $total_complaints == 0): ?>
                                <div style="margin-top: 20px;">
                                    <p>You can add sample data to test the system:</p>
                                    <form method="POST" action="add_sample_data.php" style="display: inline;">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Add Sample Data
                                        </button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- View Complaint Modal -->
    <div class="modal" id="viewComplaintModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Complaint Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="complaintDetails">
                <!-- Dynamic content will be loaded here via AJAX -->
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
        const viewComplaintModal = document.getElementById('viewComplaintModal');

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

        // Modal functions
        function viewComplaint(complaintId) {
            // Show loading state
            document.getElementById('complaintDetails').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #1e5596;"></i>
                    <p>Loading complaint details...</p>
                </div>
            `;
            
            viewComplaintModal.classList.add('show');
            
            // AJAX request to fetch complaint details
            fetch('get_complaint_details.php?id=' + complaintId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const complaint = data.complaint;
                        const complaintDetails = `
                            <div class="complaint-details">
                                <div class="complaint-header">
                                    <div class="user-info">
                                        <div class="user-avatar-small">
                                            ${complaint.full_name ? complaint.full_name.charAt(0).toUpperCase() : 'U'}
                                        </div>
                                        <div class="user-details">
                                            <h4>${complaint.full_name || 'Unknown User'}</h4>
                                            <p>${complaint.email || 'No email'} • ${complaint.phone || 'No phone'}</p>
                                        </div>
                                    </div>
                                    <div class="complaint-meta">
                                        <div class="complaint-date">
                                            Submitted: ${new Date(complaint.created_at).toLocaleDateString()}
                                        </div>
                                        <span class="badge ${getStatusBadgeClass(complaint.status)}">${formatStatus(complaint.status)}</span>
                                    </div>
                                </div>
                                
                                <div class="complaint-content">
                                    <h5>Complaint Message:</h5>
                                    <p>${complaint.notes || 'No message provided'}</p>
                                </div>
                                
                                ${complaint.admin_response ? `
                                <div class="response-section">
                                    <div class="response-header">
                                        <h5>Admin Response</h5>
                                        <div class="response-date">
                                            ${complaint.responded_at ? 'Responded: ' + new Date(complaint.responded_at).toLocaleDateString() : ''}
                                            ${complaint.responded_by_name ? ' by ' + complaint.responded_by_name : ''}
                                        </div>
                                    </div>
                                    <p>${complaint.admin_response}</p>
                                </div>
                                ` : ''}
                                
                                <form method="POST">
                                    <input type="hidden" name="complaint_id" value="${complaint.id}">
                                    <div class="form-group">
                                        <label class="form-label">Status</label>
                                        <select class="form-control" name="status" required>
                                            <option value="pending" ${complaint.status === 'pending' ? 'selected' : ''}>Pending</option>
                                            <option value="in_progress" ${complaint.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                                            <option value="resolved" ${complaint.status === 'resolved' ? 'selected' : ''}>Resolved</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Admin Response</label>
                                        <textarea class="form-control" name="admin_response" rows="4" placeholder="Enter your response to the user...">${complaint.admin_response || ''}</textarea>
                                    </div>
                                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                                        <button type="submit" name="update_status" class="btn btn-success">
                                            <i class="fas fa-save"></i> Update Status
                                        </button>
                                        <button type="button" class="btn" onclick="closeModal()" style="background: #6c757d; color: white;">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        `;
                        document.getElementById('complaintDetails').innerHTML = complaintDetails;
                    } else {
                        document.getElementById('complaintDetails').innerHTML = `
                            <div style="text-align: center; padding: 40px; color: #e5313c;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 48px;"></i>
                                <p>Error loading complaint details</p>
                                <p>${data.message || 'Please try again later'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('complaintDetails').innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #e5313c;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 48px;"></i>
                            <p>Error loading complaint details</p>
                            <p>Please try again later</p>
                        </div>
                    `;
                });
        }

        function getStatusBadgeClass(status) {
            const statusClasses = {
                'pending': 'badge-warning',
                'in_progress': 'badge-info',
                'resolved': 'badge-success'
            };
            return statusClasses[status] || 'badge-secondary';
        }

        function formatStatus(status) {
            return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        }

        function closeModal() {
            viewComplaintModal.classList.remove('show');
        }

        // Close modal when clicking outside
        viewComplaintModal.addEventListener('click', function(e) {
            if (e.target === viewComplaintModal) {
                closeModal();
            }
        });

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

        // Auto-refresh page every 30 seconds to show new complaints
        setInterval(() => {
            // Only refresh if no modal is open and no filters are active
            if (!viewComplaintModal.classList.contains('show') && 
                window.location.search === '') {
                window.location.reload();
            }
        }, 30000);
    </script>
</body>
</html>