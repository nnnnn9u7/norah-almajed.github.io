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

// Get student ID from URL
$student_id = $_GET['id'] ?? null;
if (!$student_id) {
    header("Location: admin_students.php");
    exit();
}

// Get student data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        header("Location: admin_students.php");
        exit();
    }
    
    // Get student's exam history
    $stmt = $pdo->prepare("
        SELECT es.*, aq.file_name, aq.ai_model
        FROM exam_sessions es
        LEFT JOIN ai_generated_questions aq ON es.question_set_id = aq.id
        WHERE es.user_id = ?
        ORDER BY es.created_at DESC
    ");
    $stmt->execute([$student_id]);
    $exam_history = $stmt->fetchAll();
    
    // Get student's question files
    $stmt = $pdo->prepare("
        SELECT aq.*, COUNT(es.id) as exam_count
        FROM ai_generated_questions aq
        LEFT JOIN exam_sessions es ON aq.id = es.question_set_id
        WHERE aq.user_id = ?
        GROUP BY aq.id
        ORDER BY aq.created_at DESC
    ");
    $stmt->execute([$student_id]);
    $question_files = $stmt->fetchAll();
    
    // Calculate statistics - SCORE OUT OF 100
    $total_exams = count($exam_history);
    $total_study_time = array_sum(array_column($exam_history, 'time_spent'));
    $avg_score = $total_exams > 0 ? array_sum(array_column($exam_history, 'score')) / $total_exams : 0;
    $best_score = $total_exams > 0 ? max(array_column($exam_history, 'score')) : 0;
    
    // Get study behavior answers
    $stmt = $pdo->prepare("SELECT * FROM study_behavior_answers WHERE user_id = ? ORDER BY question_number");
    $stmt->execute([$student_id]);
    $behavior_answers = $stmt->fetchAll();
    
    // Get study schedules
    $stmt = $pdo->prepare("SELECT * FROM study_schedules WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$student_id]);
    $study_schedules = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Error loading student data: " . $e->getMessage());
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
    <title>Student Profile - IGM Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
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

        /* Student Profile Header */
        .profile-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 36px;
            flex-shrink: 0;
        }

        .profile-info {
            flex: 1;
        }

        .profile-name {
            font-size: 32px;
            font-weight: 700;
            color: var(--admin-dark);
            margin-bottom: 5px;
        }

        .profile-username {
            font-size: 18px;
            color: #666;
            margin-bottom: 15px;
        }

        .profile-contact {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid var(--primary);
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--admin-dark);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
        }

        /* Content Sections */
        .content-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 1200px) {
            .content-sections {
                grid-template-columns: 1fr;
            }
        }
        
        .section-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            transition: transform 0.3s ease;
        }
        
        .section-card:hover {
            transform: translateY(-5px);
        }
        
        .section-title {
            margin-bottom: 25px;
            color: var(--admin-dark);
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 20px;
            font-weight: 600;
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

        .file-list {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }

        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #e1e5e9;
        }

        .file-item:last-child {
            border-bottom: none;
        }

        .file-info {
            flex: 1;
        }

        .file-name {
            font-weight: 600;
            color: var(--admin-dark);
        }

        .file-meta {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e1e5e9;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }

        .progress-fill {
            height: 100%;
            background: var(--success);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .behavior-answers {
            display: grid;
            gap: 15px;
        }

        .behavior-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        .behavior-question {
            font-weight: 600;
            color: var(--admin-dark);
            margin-bottom: 8px;
        }

        .behavior-answer {
            color: #666;
            font-size: 14px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #888;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #ddd;
        }

        .empty-state p {
            font-size: 16px;
            margin-bottom: 10px;
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
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-contact {
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .profile-stats {
                grid-template-columns: repeat(2, 1fr);
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
            
            .profile-stats {
                grid-template-columns: 1fr;
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
                <img src="https://framerusercontent.com/images/hOzQ20FaHtHA2R9E2cxsUJ47M.png?width=67&height=74" alt="IGM Logo">
                <span class="logo-text">IGM Admin</span>
            </div>
        </div>
        
        <div class="user-section">
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
                <a href="admin_performance.php">
                    <i class="fas fa-chart-line"></i>
                    <span class="nav-text">Performance Tracking</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content" id="mainContent">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-user-graduate"></i> Student Profile
            </h1>
            <div class="page-actions">
                <a href="admin_students.php" class="btn" style="background: #6c757d; color: white;">
                    <i class="fas fa-arrow-left"></i> Back to Students
                </a>
                <a href="admin_performance.php" class="btn btn-info">
                    <i class="fas fa-chart-line"></i> Performance Overview
                </a>
            </div>
        </div>

        <!-- Student Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
            </div>
            <div class="profile-info">
                <h1 class="profile-name"><?php echo htmlspecialchars($student['full_name']); ?></h1>
                <div class="profile-username">@<?php echo htmlspecialchars($student['username']); ?></div>
                <div class="profile-contact">
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo htmlspecialchars($student['email']); ?></span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span><?php echo htmlspecialchars($student['phone'] ?? 'Not provided'); ?></span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-calendar"></i>
                        <span>Joined <?php echo date('M j, Y', strtotime($student['created_at'])); ?></span>
                    </div>
                </div>
                <div class="profile-stats">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $total_exams; ?></div>
                        <div class="stat-label">Total Exams</div>
                    </div>
                    <div class="stat-card">
                        <!-- SCORE OUT OF 100 -->
                        <div class="stat-value"><?php echo round($avg_score, 1); ?>/100</div>
                        <div class="stat-label">Average Score</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo round($total_study_time / 3600, 1); ?>h</div>
                        <div class="stat-label">Study Time</div>
                    </div>
                    <div class="stat-card">
                        <!-- SCORE OUT OF 100 -->
                        <div class="stat-value"><?php echo round($best_score, 1); ?>/100</div>
                        <div class="stat-label">Best Score</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-sections">
            <!-- Left Column -->
            <div>
                <!-- Exam History -->
                <div class="section-card">
                    <h2 class="section-title">
                        <i class="fas fa-history"></i> Exam History
                    </h2>
                    <?php if (!empty($exam_history)): ?>
                        <div class="data-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>File</th>
                                        <th>Score</th>
                                        <th>Time</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($exam_history as $exam): 
                                        // SCORE OUT OF 100 (directly use score field)
                                        $score = $exam['score'];
                                        $badge_class = $score >= 80 ? 'badge-success' : ($score >= 60 ? 'badge-warning' : 'badge-danger');
                                    ?>
                                    <tr>
                                        <td>
                                            <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <?php echo htmlspecialchars($exam['file_name'] ?? 'Unknown File'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <!-- SCORE OUT OF 100 -->
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo $score; ?>/100
                                            </span>
                                        </td>
                                        <td>
                                            <span style="color: #666; font-size: 14px;">
                                                <?php echo round($exam['time_spent'] / 60, 1); ?>m
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-size: 12px; color: #666;">
                                                <?php echo date('M j, Y', strtotime($exam['created_at'])); ?>
                                                <div><?php echo date('g:i A', strtotime($exam['created_at'])); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary"><?php echo ucfirst($exam['status']); ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <p>No exam history available</p>
                            <p>This student hasn't taken any exams yet</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Study Behavior -->
                <div class="section-card">
                    <h2 class="section-title">
                        <i class="fas fa-brain"></i> Learning Behavior
                    </h2>
                    <?php if (!empty($behavior_answers)): ?>
                        <div class="behavior-answers">
                            <?php foreach ($behavior_answers as $answer): ?>
                            <div class="behavior-item">
                                <div class="behavior-question">
                                    <?php echo htmlspecialchars($answer['question_text']); ?>
                                </div>
                                <div class="behavior-answer">
                                    <strong>Answer:</strong> <?php echo htmlspecialchars($answer['answer_text']); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-brain"></i>
                            <p>No behavior data available</p>
                            <p>Learning behavior analysis not completed</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right Column -->
            <div>
                <!-- Question Files -->
                <div class="section-card">
                    <h2 class="section-title">
                        <i class="fas fa-file"></i> Question Files
                    </h2>
                    <?php if (!empty($question_files)): ?>
                        <div class="file-list">
                            <?php foreach ($question_files as $file): ?>
                            <div class="file-item">
                                <div class="file-info">
                                    <div class="file-name">
                                        <?php echo htmlspecialchars($file['file_name']); ?>
                                    </div>
                                    <div class="file-meta">
                                        Created: <?php echo date('M j, Y', strtotime($file['created_at'])); ?> • 
                                        AI Model: <?php echo htmlspecialchars($file['ai_model']); ?> •
                                        Exams: <?php echo $file['exam_count']; ?>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo min(($file['exam_count'] * 20), 100); ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file"></i>
                            <p>No question files found</p>
                            <p>This student hasn't generated any question files yet</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Study Schedules -->
                <div class="section-card">
                    <h2 class="section-title">
                        <i class="fas fa-calendar-alt"></i> Study Schedules
                    </h2>
                    <?php if (!empty($study_schedules)): ?>
                        <div class="file-list">
                            <?php foreach ($study_schedules as $schedule): 
                                $schedule_data = json_decode($schedule['schedule_data'], true);
                                $learning_style = json_decode($schedule['learning_style'], true);
                            ?>
                            <div class="file-item">
                                <div class="file-info">
                                    <div class="file-name">
                                        <?php echo htmlspecialchars($schedule['subject_name']); ?>
                                    </div>
                                    <div class="file-meta">
                                        Exam Date: <?php echo date('M j, Y', strtotime($schedule['exam_date'])); ?> • 
                                        Study Hours: <?php echo $schedule['study_hours']; ?>h •
                                        Total Pages: <?php echo $schedule_data['total_pages'] ?? 'N/A'; ?>
                                    </div>
                                    <?php if (!empty($learning_style)): ?>
                                    <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                        <strong>Learning Style:</strong> 
                                        <?php echo implode(', ', array_map(function($key, $value) {
                                            return "$key: $value";
                                        }, array_keys($learning_style), $learning_style)); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-alt"></i>
                            <p>No study schedules found</p>
                            <p>This student hasn't created any study schedules yet</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Performance Insights -->
                <div class="section-card">
                    <h2 class="section-title">
                        <i class="fas fa-chart-line"></i> Performance Insights
                    </h2>
                    <div style="padding: 20px; background: #f8f9fa; border-radius: 10px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                            <div style="text-align: center;">
                                <!-- SCORE OUT OF 100 -->
                                <div style="font-size: 24px; font-weight: 700; color: var(--primary);">
                                    <?php echo round($avg_score, 1); ?>/100
                                </div>
                                <div style="font-size: 12px; color: #666;">Average Score</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: var(--success);">
                                    <?php echo round($total_study_time / 3600, 1); ?>h
                                </div>
                                <div style="font-size: 12px; color: #666;">Total Study Time</div>
                            </div>
                        </div>
                        
                        <?php if ($total_exams > 0): ?>
                        <div style="margin-top: 15px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span style="font-size: 14px; color: #666;">Exam Completion Rate</span>
                                <span style="font-size: 14px; font-weight: 600; color: var(--admin-dark);">100%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 100%; background: var(--success);"></div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 15px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span style="font-size: 14px; color: #666;">Consistency Score</span>
                                <!-- SCORE OUT OF 100 -->
                                <span style="font-size: 14px; font-weight: 600; color: var(--admin-dark);">
                                    <?php echo round($avg_score, 1); ?>/100
                                </span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $avg_score; ?>%;"></div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="empty-state" style="padding: 20px;">
                            <i class="fas fa-chart-line"></i>
                            <p>No performance data</p>
                            <p>Complete exams to see insights</p>
                        </div>
                        <?php endif; ?>
                    </div>
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

        // Animate progress bars
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 500);
            });
        });
    </script>
</body>
</html>