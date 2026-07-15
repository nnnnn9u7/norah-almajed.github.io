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

// إنشاء مجلد الرفع للكورسات
$courses_upload_dir = "uploads/courses/";
if (!file_exists($courses_upload_dir)) {
    mkdir($courses_upload_dir, 0777, true);
}

// Get course data for editing
$edit_course = null;
if (isset($_GET['edit_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$_GET['edit_id']]);
        $edit_course = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Error loading course data: " . $e->getMessage();
        $message_type = "error";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_course'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $date = $_POST['date'];
        $duration = trim($_POST['duration']);
        $level = trim($_POST['level']);
        $instructor = trim($_POST['instructor']);
        $format = trim($_POST['format']);
        $price = trim($_POST['price']);
        $certificate = $_POST['certificate'];
        $language = trim($_POST['language']);
        $status = $_POST['status'];
        $external_link = trim($_POST['external_link']);
        
        // معالجة رفع الصورة
        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = $_FILES['image']['type'];
            $file_size = $_FILES['image']['size'];
            $file_tmp = $_FILES['image']['tmp_name'];
            
            if (in_array($file_type, $allowed_types)) {
                if ($file_size <= 5 * 1024 * 1024) {
                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $file_name = 'course_' . time() . '_' . uniqid() . '.' . $file_extension;
                    $file_path = $courses_upload_dir . $file_name;
                    
                    if (move_uploaded_file($file_tmp, $file_path)) {
                        $image_url = $file_path;
                    } else {
                        $message = "Error uploading image file.";
                        $message_type = "error";
                    }
                } else {
                    $message = "Image size is too large. Maximum size is 5MB.";
                    $message_type = "error";
                }
            } else {
                $message = "Invalid image format. Allowed formats: JPG, JPEG, PNG, GIF, WEBP.";
                $message_type = "error";
            }
        }
        
        // إذا لم يتم رفع صورة، استخدم صورة افتراضية
        if (empty($image_url)) {
            $image_url = 'https://images.unsplash.com/photo-1501504905252-473c47e087f8?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80';
        }
        
        if (!$message) {
            try {
                $stmt = $pdo->prepare("INSERT INTO courses (title, description, date, duration, level, instructor, format, price, certificate, language, status, image_url, external_link, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $date, $duration, $level, $instructor, $format, $price, $certificate, $language, $status, $image_url, $external_link, $admin_id]);
                
                $message = "Course created successfully!" . ($image_url ? " Image uploaded." : "");
                $message_type = "success";
            } catch (PDOException $e) {
                $message = "Error creating course: " . $e->getMessage();
                $message_type = "error";
            }
        }
    }
    
    if (isset($_POST['update_course'])) {
        $id = $_POST['course_id'];
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $date = $_POST['date'];
        $duration = trim($_POST['duration']);
        $level = trim($_POST['level']);
        $instructor = trim($_POST['instructor']);
        $format = trim($_POST['format']);
        $price = trim($_POST['price']);
        $certificate = $_POST['certificate'];
        $language = trim($_POST['language']);
        $status = $_POST['status'];
        $external_link = trim($_POST['external_link']);
        
        // الحصول على البيانات الحالية
        $current_course = $pdo->prepare("SELECT image_url FROM courses WHERE id = ?");
        $current_course->execute([$id]);
        $current_data = $current_course->fetch();
        
        $image_url = $current_data['image_url'];
        
        // معالجة رفع الصورة الجديدة
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = $_FILES['image']['type'];
            $file_size = $_FILES['image']['size'];
            $file_tmp = $_FILES['image']['tmp_name'];
            
            if (in_array($file_type, $allowed_types)) {
                if ($file_size <= 5 * 1024 * 1024) {
                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $file_name = 'course_' . time() . '_' . uniqid() . '.' . $file_extension;
                    $file_path = $courses_upload_dir . $file_name;
                    
                    if (move_uploaded_file($file_tmp, $file_path)) {
                        // حذف الصورة القديمة إذا كانت محلية
                        if ($image_url && strpos($image_url, 'uploads/') !== false && file_exists($image_url)) {
                            unlink($image_url);
                        }
                        $image_url = $file_path;
                    }
                }
            }
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE courses SET title=?, description=?, date=?, duration=?, level=?, instructor=?, format=?, price=?, certificate=?, language=?, status=?, image_url=?, external_link=? WHERE id=?");
            $stmt->execute([$title, $description, $date, $duration, $level, $instructor, $format, $price, $certificate, $language, $status, $image_url, $external_link, $id]);
            
            $message = "Course updated successfully!";
            $message_type = "success";
            $edit_course = null; // Clear edit mode
        } catch (PDOException $e) {
            $message = "Error updating course: " . $e->getMessage();
            $message_type = "error";
        }
    }
    
    if (isset($_POST['delete_course'])) {
        $id = $_POST['course_id'];
        
        // حذف الصورة المرتبطة
        try {
            $stmt = $pdo->prepare("SELECT image_url FROM courses WHERE id = ?");
            $stmt->execute([$id]);
            $course = $stmt->fetch();
            
            if ($course && $course['image_url'] && strpos($course['image_url'], 'uploads/') !== false && file_exists($course['image_url'])) {
                unlink($course['image_url']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM courses WHERE id=?");
            $stmt->execute([$id]);
            
            $message = "Course deleted successfully!";
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "Error deleting course: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Get all courses
try {
    $courses = $pdo->query("
        SELECT c.*, a.username as created_by_name 
        FROM courses c 
        LEFT JOIN admin_users a ON c.created_by = a.id 
        ORDER BY c.created_at DESC
    ")->fetchAll();
    
    // Set default values for empty fields
    foreach ($courses as &$course) {
        $course['date'] = $course['date'] ?? null;
        $course['duration'] = $course['duration'] ?? 'Not set';
        $course['level'] = $course['level'] ?? 'Not set';
        $course['instructor'] = $course['instructor'] ?? 'Not set';
        $course['price'] = $course['price'] ?? 'Free';
        $course['certificate'] = $course['certificate'] ?? 'No';
        $course['language'] = $course['language'] ?? 'Not set';
        $course['format'] = $course['format'] ?? 'Not set';
        $course['status'] = $course['status'] ?? 'draft';
        $course['image_url'] = $course['image_url'] ?? 'https://images.unsplash.com/photo-1501504905252-473c47e087f8?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80';
    }
    unset($course); // Break reference
    
} catch (PDOException $e) {
    $courses = [];
    error_log("Error fetching courses: " . $e->getMessage());
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
    <title>Manage Courses - IGM Admin</title>
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

        .main-content {
            margin-top: 70px;
            padding: 30px;
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 70px);
        }

        .sidebar-open .main-content {
            margin-left: 250px;
        }

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

        .form-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
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

        .file-input {
            padding: 10px;
        }

        .image-preview {
            margin-top: 10px;
            max-width: 200px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e1e5e9;
        }

        .image-preview img {
            width: 100%;
            height: auto;
            display: block;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

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

        .table-image {
            width: 60px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
        }

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
            
            .form-row {
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
        }
    </style>
</head>
<body>
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
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
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

    <div class="main-content" id="mainContent">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-book"></i> Manage Courses
            </h1>
            <div class="page-actions">
                <button class="btn btn-primary" onclick="openAddForm()">
                    <i class="fas fa-plus"></i> Add New Course
                </button>
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

        <div class="form-container" id="addForm" style="display: <?php echo $edit_course ? 'block' : 'none'; ?>;">
            <h3 style="margin-bottom: 20px; color: var(--admin-dark);">
                <?php echo $edit_course ? 'Edit Course' : 'Add New Course'; ?>
            </h3>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit_course): ?>
                    <input type="hidden" name="course_id" value="<?php echo $edit_course['id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Course Title *</label>
                        <input type="text" class="form-control" name="title" required 
                               placeholder="Enter course title"
                               value="<?php echo $edit_course ? htmlspecialchars($edit_course['title']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Start Date *</label>
                        <input type="date" class="form-control" name="date" required
                               value="<?php echo $edit_course ? $edit_course['date'] : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description *</label>
                    <textarea class="form-control" name="description" rows="4" required placeholder="Enter course description"><?php echo $edit_course ? htmlspecialchars($edit_course['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Duration *</label>
                        <input type="text" class="form-control" name="duration" placeholder="e.g., 6 Weeks" required
                               value="<?php echo $edit_course ? htmlspecialchars($edit_course['duration']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Level *</label>
                        <select class="form-control" name="level" required>
                            <option value="Beginner" <?php echo ($edit_course && $edit_course['level'] == 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                            <option value="Intermediate" <?php echo ($edit_course && $edit_course['level'] == 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="Advanced" <?php echo ($edit_course && $edit_course['level'] == 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                            <option value="Beginner to Intermediate" <?php echo ($edit_course && $edit_course['level'] == 'Beginner to Intermediate') ? 'selected' : ''; ?>>Beginner to Intermediate</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Instructor *</label>
                        <input type="text" class="form-control" name="instructor" placeholder="e.g., Dr. Sarah Johnson" required
                               value="<?php echo $edit_course ? htmlspecialchars($edit_course['instructor']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Format *</label>
                        <select class="form-control" name="format" required>
                            <option value="Online Self-Paced" <?php echo ($edit_course && $edit_course['format'] == 'Online Self-Paced') ? 'selected' : ''; ?>>Online Self-Paced</option>
                            <option value="Live Online Sessions" <?php echo ($edit_course && $edit_course['format'] == 'Live Online Sessions') ? 'selected' : ''; ?>>Live Online Sessions</option>
                            <option value="In-Person" <?php echo ($edit_course && $edit_course['format'] == 'In-Person') ? 'selected' : ''; ?>>In-Person</option>
                            <option value="Hybrid" <?php echo ($edit_course && $edit_course['format'] == 'Hybrid') ? 'selected' : ''; ?>>Hybrid</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Price</label>
                        <input type="text" class="form-control" name="price" placeholder="e.g., Free or $99"
                               value="<?php echo $edit_course ? htmlspecialchars($edit_course['price']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Certificate</label>
                        <select class="form-control" name="certificate">
                            <option value="Yes" <?php echo ($edit_course && $edit_course['certificate'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                            <option value="No" <?php echo ($edit_course && $edit_course['certificate'] == 'No') ? 'selected' : ''; ?>>No</option>
                            <option value="Yes with distinction" <?php echo ($edit_course && $edit_course['certificate'] == 'Yes with distinction') ? 'selected' : ''; ?>>Yes with distinction</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Language *</label>
                        <input type="text" class="form-control" name="language" placeholder="e.g., English" required
                               value="<?php echo $edit_course ? htmlspecialchars($edit_course['language']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select class="form-control" name="status" required>
                            <option value="active" <?php echo ($edit_course && $edit_course['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="upcoming" <?php echo ($edit_course && $edit_course['status'] == 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="completed" <?php echo ($edit_course && $edit_course['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="draft" <?php echo ($edit_course && $edit_course['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Course Image <?php echo $edit_course ? '' : '*'; ?></label>
                        <input type="file" class="form-control file-input" name="image" accept="image/*" <?php echo $edit_course ? '' : 'required'; ?>>
                        <div style="font-size: 12px; color: #666; margin-top: 5px;">
                            Supported formats: JPG, PNG, GIF, WEBP (Max: 5MB)
                            <?php if ($edit_course): ?>
                                <br>Leave empty to keep current image
                            <?php endif; ?>
                        </div>
                        <?php if ($edit_course && $edit_course['image_url']): ?>
                            <div class="image-preview" id="imagePreview" style="display: block;">
                                <img src="<?php echo $edit_course['image_url']; ?>" alt="Current Image">
                            </div>
                        <?php else: ?>
                            <div class="image-preview" id="imagePreview" style="display: none;">
                                <img src="" alt="Image Preview">
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">External Link</label>
                        <input type="url" class="form-control" name="external_link" placeholder="https://example.com/course-details"
                               value="<?php echo $edit_course ? htmlspecialchars($edit_course['external_link']) : ''; ?>">
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <?php if ($edit_course): ?>
                        <button type="submit" name="update_course" class="btn btn-success">
                            <i class="fas fa-save"></i> Update Course
                        </button>
                    <?php else: ?>
                        <button type="submit" name="add_course" class="btn btn-success">
                            <i class="fas fa-save"></i> Create Course
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn" onclick="closeAddForm()" style="background: #6c757d; color: white;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>

        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Duration</th>
                        <th>Level</th>
                        <th>Instructor</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($courses)): ?>
                        <?php foreach ($courses as $course): 
                            $status_badge = [
                                'active' => 'badge-success',
                                'upcoming' => 'badge-primary', 
                                'completed' => 'badge-info',
                                'draft' => 'badge-warning'
                            ][$course['status']] ?? 'badge-secondary';
                        ?>
                        <tr>
                            <td>
                                <?php if (!empty($course['image_url'])): ?>
                                    <img src="<?php echo $course['image_url']; ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" class="table-image" onerror="this.src='https://images.unsplash.com/photo-1501504905252-473c47e087f8?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=100&q=80'">
                                <?php else: ?>
                                    <img src="https://images.unsplash.com/photo-1501504905252-473c47e087f8?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=100&q=80" alt="No Image" class="table-image">
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                                <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                    <?php echo substr(htmlspecialchars($course['description'] ?? ''), 0, 50) . '...'; ?>
                                </div>
                            </td>
                            <td>
                                <?php 
                                if (!empty($course['date']) && $course['date'] != '0000-00-00') {
                                    echo date('M j, Y', strtotime($course['date']));
                                } else {
                                    echo 'Not set';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($course['duration']); ?></td>
                            <td><?php echo htmlspecialchars($course['level']); ?></td>
                            <td><?php echo htmlspecialchars($course['instructor']); ?></td>
                            <td><?php echo htmlspecialchars($course['price']); ?></td>
                            <td>
                                <span class="badge <?php echo $status_badge; ?>">
                                    <?php echo ucfirst($course['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <button class="btn btn-primary btn-sm" onclick="viewCourse(<?php echo $course['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="?edit_id=<?php echo $course['id']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <button type="submit" name="delete_course" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this course?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: #666;">
                                <i class="fas fa-book" style="font-size: 48px; margin-bottom: 15px; color: #ddd;"></i>
                                <p>No courses found</p>
                                <p>Create your first course to get started</p>
                                <button class="btn btn-primary" onclick="openAddForm()" style="margin-top: 15px;">
                                    <i class="fas fa-plus"></i> Add New Course
                                </button>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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

        // Form toggle functions
        function openAddForm() {
            document.getElementById('addForm').style.display = 'block';
            // Clear any edit data from URL
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.pathname);
            }
        }

        function closeAddForm() {
            document.getElementById('addForm').style.display = 'none';
            // Clear any edit data from URL
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.pathname);
            }
        }

        // Image preview functionality
        document.addEventListener('DOMContentLoaded', function() {
            const imageInput = document.querySelector('input[name="image"]');
            const imagePreview = document.getElementById('imagePreview');
            
            if (imageInput) {
                imageInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.style.display = 'block';
                            imagePreview.querySelector('img').src = e.target.result;
                        }
                        reader.readAsDataURL(file);
                    } else {
                        imagePreview.style.display = 'none';
                    }
                });
            }
        });

        function viewCourse(id) {
            alert('View course details for ID: ' + id);
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

        // Auto-open form if in edit mode
        <?php if ($edit_course): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('addForm').style.display = 'block';
        });
        <?php endif; ?>
    </script>
</body>
</html>