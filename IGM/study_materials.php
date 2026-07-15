<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = null;
$study_materials = [];
$success_message = '';
$error_message = '';

try {
    // Get user data
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Get all study materials and exam attempts
    $stmt = $pdo->prepare("
        SELECT agq.*, 
               COUNT(es.id) as exam_count,
               MAX(es.score) as best_score,
               MAX(es.created_at) as last_attempt,
               MAX(es.id) as last_session_id
        FROM ai_generated_questions agq 
        LEFT JOIN exam_sessions es ON agq.id = es.question_set_id 
        WHERE agq.user_id = ? 
        GROUP BY agq.id 
        ORDER BY agq.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $study_materials = $stmt->fetchAll();

} catch (Exception $e) {
    $error_message = "Error loading study materials: " . $e->getMessage();
}

// Delete study material
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_material'])) {
    $material_id = $_POST['material_id'];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete related exam sessions first
        $stmt = $pdo->prepare("DELETE FROM exam_sessions WHERE question_set_id = ?");
        $stmt->execute([$material_id]);
        
        // Delete the study material
        $stmt = $pdo->prepare("DELETE FROM ai_generated_questions WHERE id = ? AND user_id = ?");
        $stmt->execute([$material_id, $user_id]);
        
        $pdo->commit();
        $success_message = "Study material deleted successfully";
        
        // Refresh the page
        header("Location: study_materials.php");
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Error deleting material: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Materials - IGM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary-blue: #1e5596;
            --dark-blue: #1d4c82;
            --light-blue: #e3f2fd;
            --accent-yellow: #fcb408;
            --success-green: #28a745;
            --danger-red: #dc3545;
            --warning-orange: #fd7e14;
            --text-dark: #2c3e50;
            --text-light: #6c757d;
            --bg-light: #f8f9fa;
            --white: #ffffff;
            --border-radius: 12px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-light);
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Top Navigation Bar */
        .top-nav {
            background-color: var(--primary-blue);
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
            background-color: var(--accent-yellow);
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
            background-color: var(--dark-blue);
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
            border-left: 3px solid var(--accent-yellow);
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
            margin-top: 60px;
            padding: 20px;
            position: relative;
            transition: margin-left 0.3s ease;
        }

        .main-content.sidebar-open {
            margin-left: 250px;
        }

        /* Overlay for sidebar on mobile */
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

        .sidebar-overlay.show {
            display: block;
        }

        /* Page specific styles */
        .header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .materials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .material-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-left: 5px solid var(--primary-blue);
            position: relative;
        }

        .material-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .material-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .material-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--primary-blue);
            margin: 0;
        }

        .delete-btn {
            background: var(--danger-red);
            color: white;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .delete-btn:hover {
            background: #c82333;
            transform: scale(1.1);
        }

        .material-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin: 15px 0;
        }

        .stat {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .stat-number {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-blue);
        }

        .stat-label {
            font-size: 0.8rem;
            color: #666;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.3s;
            text-align: center;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: var(--primary-blue);
            color: white;
        }

        .btn-success {
            background: var(--success-green);
            color: white;
        }

        .btn-warning {
            background: var(--warning-orange);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ccc;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .materials-grid {
                grid-template-columns: 1fr;
            }
            
            .material-stats {
                grid-template-columns: 1fr;
            }

            .main-content.sidebar-open {
                margin-left: 0;
            }
            
            .sidebar.open {
                width: 280px;
            }
            
            .top-nav {
                padding: 10px 15px;
            }
        }

        @media (max-width: 480px) {
            .top-nav {
                padding: 10px 15px;
            }
            
            .logo-text {
                display: none;
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
                <span class="logo-text">IGM</span>
            </div>
        </div>
        
        <div class="user-section">
            <div class="user-info" id="userInfo">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <span id="userDisplayName"><?php echo htmlspecialchars($user['full_name']); ?></span>
                <i class="fas fa-chevron-down" style="font-size: 12px;"></i>
            </div>
            
            <div class="user-dropdown" id="userDropdown">
                <a href="dashboard.php" class="dropdown-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="logout.php" class="dropdown-item" id="logoutBtn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="home.php">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="questionlevel.php">
                    <i class="fas fa-book"></i>
                    <span>Study Behavior Questions</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="upload_file.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Start Learning</span>
                </a>
            </li>
            <li class="nav-item active">
                <a href="study_materials.php">
                    <i class="fas fa-book"></i>
                    <span>Study Materials</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="timing_schedule.php">
                    <i class="fas fa-trophy"></i>
                    <span>Timing Schedule</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="hakathons.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Courses & Hackathons</span>
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
        <div class="container">
            <div class="header">
                <h1>My Study Materials</h1>
                <p>Manage and review all your study materials and previous tests</p>
            </div>

            <?php if ($success_message): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($study_materials)): ?>
                <div class="empty-state">
                    <i class="fas fa-book-open"></i>
                    <h3>No Study Materials</h3>
                    <p>Upload a study file to start learning and create custom tests</p>
                    <a href="upload_file.php" class="btn btn-primary" style="display: inline-block; width: auto; padding: 12px 25px;">
                        <i class="fas fa-upload"></i>
                        Upload New File
                    </a>
                </div>
            <?php else: ?>
                <div class="materials-grid">
                    <?php foreach ($study_materials as $material): 
                        $questions = json_decode($material['questions'] ?? '[]', true);
                        $question_count = is_array($questions) ? count($questions) : 0;
                    ?>
                    <div class="material-card">
                        <div class="material-header">
                            <h3 class="material-title">
                                <i class="fas fa-file-alt"></i>
                                <?php echo htmlspecialchars($material['file_name']); ?>
                            </h3>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this material? All associated tests will be deleted.');">
                                <input type="hidden" name="material_id" value="<?php echo $material['id']; ?>">
                                <button type="submit" name="delete_material" class="delete-btn" title="Delete Material">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </div>

                        <p style="color: #666; margin-bottom: 15px;">
                            <i class="fas fa-calendar"></i>
                            <?php echo date('F j, Y', strtotime($material['created_at'])); ?>
                        </p>

                        <div class="material-stats">
                            <div class="stat">
                                <div class="stat-number"><?php echo $question_count; ?></div>
                                <div class="stat-label">Questions</div>
                            </div>
                            <div class="stat">
                                <div class="stat-number"><?php echo $material['exam_count'] ?? 0; ?></div>
                                <div class="stat-label">Tests Taken</div>
                            </div>
                            <div class="stat">
                                <div class="stat-number">
                                    <?php echo $material['best_score'] ? $material['best_score'] . '%' : '--'; ?>
                                </div>
                                <div class="stat-label">Best Score</div>
                            </div>
                            <div class="stat">
                                <div class="stat-number">
                                    <?php echo $material['last_attempt'] ? date('m/d', strtotime($material['last_attempt'])) : '--'; ?>
                                </div>
                                <div class="stat-label">Last Attempt</div>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <a href="start_exam.php?material_id=<?php echo $material['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-play-circle"></i>
                                Take Test
                            </a>
                            <?php if ($material['exam_count'] > 0): ?>
                            <a href="exam_results.php?session_id=<?php echo $material['last_session_id']; ?>" class="btn btn-success">
                                <i class="fas fa-chart-line"></i>
                                Results
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // DOM Elements for Navigation
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mainContent = document.getElementById('mainContent');
        const userInfo = document.getElementById('userInfo');
        const userDropdown = document.getElementById('userDropdown');
        const logoutBtn = document.getElementById('logoutBtn');

        // Set username dynamically
        const userName = "<?php echo htmlspecialchars($user['full_name']); ?>";
        document.getElementById("userDisplayName").textContent = userName;

        // Toggle sidebar
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            mainContent.classList.toggle('sidebar-open');
            sidebarOverlay.classList.toggle('show');
        });

        // Close sidebar when clicking on overlay
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            mainContent.classList.remove('sidebar-open');
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

        // Logout functionality
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if(confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        });

        // Confirm deletion
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this study material? All associated tests will be deleted.')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>