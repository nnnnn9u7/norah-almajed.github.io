<?php
session_start();
require_once 'db_config.php';
require_once 'ai_handler.php';

$user_id = $_SESSION['user_id'] ?? 0;
$response_text = '';
$user_question = '';

if ($user_id === 0) {
    header("Location: login.php");
    exit();
}

// Get user data
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_question'])) {
    $user_question = trim($_POST['user_question']);
    
    if (!empty($user_question)) {
        // You can get the content the student was last studying from the database for more personalization
        $study_context = ""; 
        
        // Call AI helper function
        $response_text = getAIHelp($user_question, $study_context);
    } else {
        $response_text = "Please write your question clearly before sending.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Study Tutor - IGM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            line-height: 1.6;
            color: #333;
            background-color: #f0f2f5;
        }

        /* Top Navigation Bar */
        .top-nav {
            background-color: #1e5596;
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
            color: white;
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

        /* Sidebar */
        .sidebar {
            background-color: #1d4c82;
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

        .nav-item a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            width: 100%;
        }

        /* Main Content & Page Specific */
        .main-content {
            margin-top: 60px;
            padding: 20px;
            position: relative;
            transition: margin-left 0.3s ease;
        }

        .main-content.sidebar-open {
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

        .sidebar-overlay.show {
            display: block;
        }

        /* Chat Specific Styles */
        .chat-container {
            max-width: 800px;
            margin: 80px auto 50px;
            padding: 30px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .chat-header {
            text-align: center;
            color: #1e5596;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
        }

        .chat-box {
            min-height: 350px;
            max-height: 500px;
            overflow-y: auto;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .message {
            max-width: 80%;
            padding: 10px 15px;
            border-radius: 15px;
            line-height: 1.5;
            word-wrap: break-word;
        }

        .user-message {
            background-color: #1e5596;
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }

        .ai-message {
            background-color: #fcb408;
            color: #333;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
            font-size: 14px;
        }

        .input-form {
            display: flex;
            gap: 10px;
        }

        .input-form textarea {
            flex-grow: 1;
            padding: 10px 15px;
            border: 2px solid #1e5596;
            border-radius: 20px;
            resize: none;
            height: 50px;
            transition: all 0.3s;
        }

        .input-form textarea:focus {
            border-color: #fcb408;
            outline: none;
            box-shadow: 0 0 5px rgba(252, 180, 8, 0.5);
        }

        .input-form button {
            background: #1e5596;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .input-form button:hover {
            background: #16417a;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-content.sidebar-open {
                margin-left: 0;
            }
            .sidebar.open {
                width: 280px;
            }
        }
        @media (max-width: 480px) {
            .logo-text {
                display: none;
            }
            .chat-container {
                padding: 15px;
            }
            .input-form {
                flex-direction: column;
            }
            .input-form button {
                width: 100%;
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

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

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
            <li class="nav-item active">
                <a href="ai_tutor.php">
                    <i class="fas fa-headset"></i>
                    <span>AI Tutor</span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="main-content" id="mainContent">
        <div class="chat-container">
            <h1 class="chat-header"><i class="fas fa-robot"></i> IGM AI Tutor</h1>
            <p style="text-align: center; color: #666; font-size: 14px;">Ask me anything about your studies or a difficult concept!</p>
            
            <div class="chat-box" id="chatBox">
                <div class="message ai-message">
                    Welcome <?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?>! I'm your smart tutor at IGM. How can I help you with your studies today?
                </div>
                
                <?php if (!empty($user_question) && !empty($response_text)): ?>
                    <div class="message user-message"><?php echo htmlspecialchars($user_question); ?></div>
                    <div class="message ai-message">
                        <?php echo nl2br(htmlspecialchars($response_text)); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <form method="POST" class="input-form">
                <textarea name="user_question" placeholder="Write your question here..." required></textarea>
                <button type="submit"><i class="fas fa-paper-plane"></i> Send</button>
            </form>
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
        const userDisplayName = document.getElementById("userDisplayName");

        // Set username dynamically
        if (userDisplayName) {
            userDisplayName.textContent = "<?php echo htmlspecialchars($user['full_name']); ?>";
        }

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
        if (userInfo) {
            userInfo.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle('show');
            });
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            if(userDropdown) userDropdown.classList.remove('show');
        });
        
        // Logout functionality
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = 'logout.php';
            });
        }
        
        // Chat scroll to bottom
        const chatBox = document.getElementById('chatBox');
        chatBox.scrollTop = chatBox.scrollHeight;
    </script>
</body>
</html>