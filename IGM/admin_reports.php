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

// Get date range filters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month
$report_type = $_GET['report_type'] ?? 'overview';

// Get statistics and reports data
try {
    // Overall Statistics
    $total_students = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_courses = $pdo->query("SELECT COUNT(*) FROM courses WHERE status = 'active'")->fetchColumn();
    $total_hackathons = $pdo->query("SELECT COUNT(*) FROM hackathons")->fetchColumn();
    $total_exams = $pdo->query("SELECT COUNT(*) FROM exam_sessions")->fetchColumn();
    
    // Date-filtered statistics
    $new_students = $pdo->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) BETWEEN ? AND ?");
    $new_students->execute([$start_date, $end_date]);
    $new_students = $new_students->fetchColumn();
    
    $completed_exams = $pdo->prepare("SELECT COUNT(*) FROM exam_sessions WHERE DATE(created_at) BETWEEN ? AND ?");
    $completed_exams->execute([$start_date, $end_date]);
    $completed_exams = $completed_exams->fetchColumn();
    
    $avg_exam_score = $pdo->prepare("SELECT AVG(score/total_questions*100) FROM exam_sessions WHERE DATE(created_at) BETWEEN ? AND ? AND total_questions > 0");
    $avg_exam_score->execute([$start_date, $end_date]);
    $avg_exam_score = $avg_exam_score->fetchColumn();
    $avg_exam_score = round($avg_exam_score, 1);
    
    // Student growth data (last 6 months)
    $student_growth_data = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $month_name = date('M Y', strtotime("-$i months"));
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
        $stmt->execute([$month]);
        $count = $stmt->fetchColumn();
        
        $student_growth_data[] = [
            'month' => $month_name,
            'count' => $count
        ];
    }
    
    // Course enrollment data
    $course_enrollment = $pdo->query("
        SELECT c.title, COUNT(ec.course_id) as enrollment_count 
        FROM courses c 
        LEFT JOIN enrollments ec ON c.id = ec.course_id 
        WHERE c.status = 'active'
        GROUP BY c.id 
        ORDER BY enrollment_count DESC 
        LIMIT 10
    ")->fetchAll();
    
    // Exam performance data
    $exam_performance = $pdo->query("
        SELECT es.score, es.total_questions, u.full_name, es.created_at,
               ROUND((es.score/es.total_questions)*100, 1) as percentage
        FROM exam_sessions es 
        JOIN users u ON es.user_id = u.id 
        WHERE es.total_questions > 0 
        ORDER BY es.created_at DESC 
        LIMIT 10
    ")->fetchAll();
    
    // Hackathon participation
    $hackathon_participation = $pdo->query("
        SELECT h.title, h.date, h.status,
               (SELECT COUNT(*) FROM hackathon_participants hp WHERE hp.hackathon_id = h.id) as participants
        FROM hackathons h 
        ORDER BY h.date DESC 
        LIMIT 10
    ")->fetchAll();
    
    // Revenue data (if available)
    $revenue_data = $pdo->query("
        SELECT 
            SUM(CASE WHEN price > 0 THEN price ELSE 0 END) as total_revenue,
            SUM(CASE WHEN price = 0 THEN 1 ELSE 0 END) as free_courses,
            SUM(CASE WHEN price > 0 THEN 1 ELSE 0 END) as paid_courses
        FROM courses
    ")->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Set default values if errors
    $total_students = $total_courses = $total_hackathons = $total_exams = 0;
    $new_students = $completed_exams = $avg_exam_score = 0;
    $student_growth_data = $course_enrollment = $exam_performance = $hackathon_participation = [];
    $revenue_data = ['total_revenue' => 0, 'free_courses' => 0, 'paid_courses' => 0];
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
    <title>Reports & Analytics - IGM Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            grid-template-columns: 1fr 1fr 1fr auto;
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

        /* Report Tabs */
        .report-tabs {
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
        
        .stat-card:nth-child(2) { border-top-color: var(--success); }
        .stat-card:nth-child(3) { border-top-color: var(--info); }
        .stat-card:nth-child(4) { border-top-color: var(--warning); }
        .stat-card:nth-child(5) { border-top-color: var(--danger); }
        .stat-card:nth-child(6) { border-top-color: var(--secondary); }
        
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

        .stat-change {
            font-size: 12px;
            color: var(--success);
            font-weight: 600;
        }

        .stat-change.negative {
            color: var(--danger);
        }

        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        @media (max-width: 1200px) {
            .charts-section {
                grid-template-columns: 1fr;
            }
        }

        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--admin-dark);
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Tables */
        .data-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
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

        /* KPI Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .kpi-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            background: var(--primary);
        }

        .kpi-content {
            flex: 1;
        }

        .kpi-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--admin-dark);
            margin-bottom: 5px;
        }

        .kpi-label {
            color: #666;
            font-size: 14px;
            font-weight: 600;
        }

        /* Export Section */
        .export-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-top: 30px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            text-align: center;
        }

        .export-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
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
            
            .charts-section {
                grid-template-columns: 1fr;
            }
            
            .report-tabs {
                flex-wrap: wrap;
            }
            
            .export-buttons {
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
            
            table {
                font-size: 12px;
            }
            
            table th, table td {
                padding: 10px 8px;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .kpi-grid {
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
            <li class="nav-item ">
                <a href="admin_dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
           <!-- <li class="nav-item">
                <a href="admin_students.php">
                    <i class="fas fa-users"></i>
                    <span class="nav-text">Students Management</span>
                </a>-->
            </li>
            <li class="nav-item">
                <a href="admin_hackathons.php">
                    <i class="fas fa-code"></i>
                    <span class="nav-text">Hackathons</span>
                </a>
            </li>
            <li class="nav-item ">
                <a href="admin_courses.php">
                    <i class="fas fa-book"></i>
                    <span class="nav-text">Courses</span>
                </a>
            </li>
            <li class="nav-item active">
                <a href="admin_complaints.php">
                    <i class="fas fa-comments"></i>
                    <span class="nav-text">Complaints & Support</span>
                </a>
            </li>
        <!--   <li class="nav-item">
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
            <li class="nav-item">
                <a href="admin_settings.php">
                    <i class="fas fa-cog"></i>
                    <span class="nav-text">System Settings</span>
                </a>
            </li>-->
        </ul>
    </div>
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-chart-bar"></i> Reports & Analytics
            </h1>
            <div class="page-actions">
                <a href="admin_dashboard.php" class="btn" style="background: #6c757d; color: white;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Report Tabs -->
        <div class="report-tabs">
            <button class="tab-btn <?php echo $report_type === 'overview' ? 'active' : ''; ?>" onclick="changeReportType('overview')">
                <i class="fas fa-chart-pie"></i> Overview
            </button>
            <button class="tab-btn <?php echo $report_type === 'students' ? 'active' : ''; ?>" onclick="changeReportType('students')">
                <i class="fas fa-users"></i> Students
            </button>
            <button class="tab-btn <?php echo $report_type === 'courses' ? 'active' : ''; ?>" onclick="changeReportType('courses')">
                <i class="fas fa-book"></i> Courses
            </button>
            <button class="tab-btn <?php echo $report_type === 'exams' ? 'active' : ''; ?>" onclick="changeReportType('exams')">
                <i class="fas fa-file-alt"></i> Exams
            </button>
            <button class="tab-btn <?php echo $report_type === 'hackathons' ? 'active' : ''; ?>" onclick="changeReportType('hackathons')">
                <i class="fas fa-code"></i> Hackathons
            </button>
            <button class="tab-btn <?php echo $report_type === 'financial' ? 'active' : ''; ?>" onclick="changeReportType('financial')">
                <i class="fas fa-dollar-sign"></i> Financial
            </button>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <input type="hidden" name="report_type" value="<?php echo $report_type; ?>">
                <div class="form-group">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Report Type</label>
                    <select class="form-control" name="report_type" onchange="this.form.submit()">
                        <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>Overview</option>
                        <option value="students" <?php echo $report_type === 'students' ? 'selected' : ''; ?>>Students</option>
                        <option value="courses" <?php echo $report_type === 'courses' ? 'selected' : ''; ?>>Courses</option>
                        <option value="exams" <?php echo $report_type === 'exams' ? 'selected' : ''; ?>>Exams</option>
                        <option value="hackathons" <?php echo $report_type === 'hackathons' ? 'selected' : ''; ?>>Hackathons</option>
                        <option value="financial" <?php echo $report_type === 'financial' ? 'selected' : ''; ?>>Financial</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <?php if ($report_type === 'overview'): ?>
            <!-- Overview Report -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_students; ?></div>
                    <div class="stat-label">Total Students</div>
                    <div class="stat-change">+<?php echo $new_students; ?> this period</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_courses; ?></div>
                    <div class="stat-label">Active Courses</div>
                    <div class="stat-change">All time</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $completed_exams; ?></div>
                    <div class="stat-label">Exams Completed</div>
                    <div class="stat-change">This period</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $avg_exam_score; ?>%</div>
                    <div class="stat-label">Avg Exam Score</div>
                    <div class="stat-change">This period</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_hackathons; ?></div>
                    <div class="stat-label">Total Hackathons</div>
                    <div class="stat-change">All time</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">$<?php echo number_format($revenue_data['total_revenue'], 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-change">All time</div>
                </div>
            </div>

            <div class="charts-section">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Student Growth</h3>
                        <span class="badge badge-primary">Last 6 Months</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="studentGrowthChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Course Enrollment</h3>
                        <span class="badge badge-success">Top 10</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="courseEnrollmentChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: var(--success);">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value"><?php echo $completed_exams; ?></div>
                        <div class="kpi-label">Exams Completed This Period</div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: var(--info);">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value"><?php echo $new_students; ?></div>
                        <div class="kpi-label">New Students This Period</div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: var(--warning);">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value"><?php echo $avg_exam_score; ?>%</div>
                        <div class="kpi-label">Average Exam Score</div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: var(--danger);">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value"><?php echo $revenue_data['paid_courses']; ?></div>
                        <div class="kpi-label">Paid Courses</div>
                    </div>
                </div>
            </div>

        <?php elseif ($report_type === 'students'): ?>
            <!-- Students Report -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_students; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $new_students; ?></div>
                    <div class="stat-label">New Students</div>
                    <div class="stat-change">This period</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo round(($new_students / max($total_students, 1)) * 100, 1); ?>%</div>
                    <div class="stat-label">Growth Rate</div>
                    <div class="stat-change">This period</div>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Student Registration Trend</h3>
                    <span class="badge badge-primary">Monthly</span>
                </div>
                <div class="chart-container">
                    <canvas id="studentTrendChart"></canvas>
                </div>
            </div>

        <?php elseif ($report_type === 'courses'): ?>
            <!-- Courses Report -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_courses; ?></div>
                    <div class="stat-label">Total Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $revenue_data['paid_courses']; ?></div>
                    <div class="stat-label">Paid Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $revenue_data['free_courses']; ?></div>
                    <div class="stat-label">Free Courses</div>
                </div>
            </div>

            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Course Title</th>
                            <th>Instructor</th>
                            <th>Enrollments</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($course_enrollment)): ?>
                            <?php foreach ($course_enrollment as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['title']); ?></td>
                                <td>Instructor Name</td>
                                <td><?php echo $course['enrollment_count']; ?></td>
                                <td>
                                    <?php if (isset($course['price']) && $course['price'] > 0): ?>
                                        $<?php echo number_format($course['price'], 2); ?>
                                    <?php else: ?>
                                        <span class="badge badge-success">FREE</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-success">Active</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: #666;">
                                    <i class="fas fa-book" style="font-size: 48px; margin-bottom: 15px; color: #ddd;"></i>
                                    <p>No course data available</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($report_type === 'exams'): ?>
            <!-- Exams Report -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_exams; ?></div>
                    <div class="stat-label">Total Exams Taken</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $completed_exams; ?></div>
                    <div class="stat-label">Exams This Period</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $avg_exam_score; ?>%</div>
                    <div class="stat-label">Average Score</div>
                </div>
            </div>

            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($exam_performance)): ?>
                            <?php foreach ($exam_performance as $exam): 
                                $badge_class = $exam['percentage'] >= 80 ? 'badge-success' : 
                                             ($exam['percentage'] >= 60 ? 'badge-warning' : 'badge-danger');
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($exam['full_name']); ?></td>
                                <td><?php echo $exam['score']; ?>/<?php echo $exam['total_questions']; ?></td>
                                <td><?php echo $exam['percentage']; ?>%</td>
                                <td><?php echo date('M j, Y', strtotime($exam['created_at'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $exam['percentage'] >= 60 ? 'Passed' : 'Failed'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: #666;">
                                    <i class="fas fa-file-alt" style="font-size: 48px; margin-bottom: 15px; color: #ddd;"></i>
                                    <p>No exam data available</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($report_type === 'hackathons'): ?>
            <!-- Hackathons Report -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_hackathons; ?></div>
                    <div class="stat-label">Total Hackathons</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        $total_participants = 0;
                        foreach ($hackathon_participation as $hackathon) {
                            $total_participants += $hackathon['participants'];
                        }
                        echo $total_participants;
                        ?>
                    </div>
                    <div class="stat-label">Total Participants</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        $active_hackathons = 0;
                        foreach ($hackathon_participation as $hackathon) {
                            if ($hackathon['status'] === 'upcoming' || $hackathon['status'] === 'ongoing') {
                                $active_hackathons++;
                            }
                        }
                        echo $active_hackathons;
                        ?>
                    </div>
                    <div class="stat-label">Active Hackathons</div>
                </div>
            </div>

            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Hackathon</th>
                            <th>Date</th>
                            <th>Participants</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($hackathon_participation)): ?>
                            <?php foreach ($hackathon_participation as $hackathon): 
                                $status_badge = [
                                    'upcoming' => 'badge-primary',
                                    'ongoing' => 'badge-success', 
                                    'completed' => 'badge-info',
                                    'cancelled' => 'badge-danger'
                                ][$hackathon['status']] ?? 'badge-secondary';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($hackathon['title']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($hackathon['date'])); ?></td>
                                <td><?php echo $hackathon['participants']; ?></td>
                                <td>
                                    <span class="badge <?php echo $status_badge; ?>">
                                        <?php echo ucfirst($hackathon['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 40px; color: #666;">
                                    <i class="fas fa-code" style="font-size: 48px; margin-bottom: 15px; color: #ddd;"></i>
                                    <p>No hackathon data available</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($report_type === 'financial'): ?>
            <!-- Financial Report -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-value">$<?php echo number_format($revenue_data['total_revenue'], 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $revenue_data['paid_courses']; ?></div>
                    <div class="stat-label">Paid Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $revenue_data['free_courses']; ?></div>
                    <div class="stat-label">Free Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        $total_courses_count = $revenue_data['paid_courses'] + $revenue_data['free_courses'];
                        $paid_percentage = $total_courses_count > 0 ? round(($revenue_data['paid_courses'] / $total_courses_count) * 100, 1) : 0;
                        echo $paid_percentage;
                        ?>%
                    </div>
                    <div class="stat-label">Paid Courses Ratio</div>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Revenue Distribution</h3>
                    <span class="badge badge-success">All Time</span>
                </div>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

        <?php endif; ?>

        <!-- Export Section -->
        <div class="export-section">
            <h3 style="margin-bottom: 20px; color: var(--admin-dark);">Export Reports</h3>
            <div class="export-buttons">
                <button class="btn btn-primary" onclick="exportReport('pdf')">
                    <i class="fas fa-file-pdf"></i> Export as PDF
                </button>
                <button class="btn btn-success" onclick="exportReport('excel')">
                    <i class="fas fa-file-excel"></i> Export as Excel
                </button>
                <button class="btn btn-info" onclick="exportReport('csv')">
                    <i class="fas fa-file-csv"></i> Export as CSV
                </button>
                <button class="btn btn-warning" onclick="printReport()">
                    <i class="fas fa-print"></i> Print Report
                </button>
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

        // Report type switching
        function changeReportType(type) {
            const url = new URL(window.location);
            url.searchParams.set('report_type', type);
            window.location.href = url.toString();
        }

        // Export functions
        function exportReport(format) {
            alert(`Exporting ${format.toUpperCase()} report for: ${document.querySelector('.tab-btn.active').textContent.trim()}`);
            // In a real application, this would generate and download the report
        }

        function printReport() {
            window.print();
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

        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Student Growth Chart
            const studentGrowthCtx = document.getElementById('studentGrowthChart')?.getContext('2d');
            if (studentGrowthCtx) {
                const studentGrowthChart = new Chart(studentGrowthCtx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode(array_column($student_growth_data, 'month')); ?>,
                        datasets: [{
                            label: 'New Students',
                            data: <?php echo json_encode(array_column($student_growth_data, 'count')); ?>,
                            borderColor: '#1e5596',
                            backgroundColor: 'rgba(30, 85, 150, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Students'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Month'
                                }
                            }
                        }
                    }
                });
            }

            // Course Enrollment Chart
            const courseEnrollmentCtx = document.getElementById('courseEnrollmentChart')?.getContext('2d');
            if (courseEnrollmentCtx) {
                const courseEnrollmentChart = new Chart(courseEnrollmentCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode(array_slice(array_column($course_enrollment, 'title'), 0, 5)); ?>,
                        datasets: [{
                            label: 'Enrollments',
                            data: <?php echo json_encode(array_slice(array_column($course_enrollment, 'enrollment_count'), 0, 5)); ?>,
                            backgroundColor: [
                                '#1e5596', '#21a7de', '#fcb408', '#7ebb38', '#fa8528'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Enrollments'
                                }
                            }
                        }
                    }
                });
            }

            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart')?.getContext('2d');
            if (revenueCtx) {
                const revenueChart = new Chart(revenueCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Paid Courses', 'Free Courses'],
                        datasets: [{
                            data: [<?php echo $revenue_data['paid_courses']; ?>, <?php echo $revenue_data['free_courses']; ?>],
                            backgroundColor: ['#7ebb38', '#21a7de'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        });

        // Auto-refresh data every 2 minutes
        setInterval(() => {
            // Only refresh if no modal is open and user is active
            if (document.visibilityState === 'visible') {
                window.location.reload();
            }
        }, 120000);
    </script>
</body>
</html>