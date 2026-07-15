<?php
session_start();
require_once 'db_config.php';
require_once 'admin_notification_functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullName = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // التحقق من الحقول الفارغة
    if (empty($fullName) || empty($phone) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } 
    // التحقق من صحة البريد الإلكتروني
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    }
    // التحقق من صحة رقم الجوال (سعودي)
    elseif (!preg_match('/^05\d{8}$/', $phone)) {
        $error = "Please enter a valid mobile number (starting with 05 and followed by 8 digits)";
    }
    // التحقق من قوة كلمة المرور
    elseif (strlen($password) < 6) {
        $error = " Password must be at least 6 characters long";
    } else {
        try {
            // إنشاء اسم مستخدم تلقائي من البريد الإلكتروني
            $username = strtolower(explode('@', $email)[0]);
            
            // التأكد من أن اسم المستخدم فريد
            $baseUsername = $username;
            $counter = 1;
            
            do {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                
                if ($stmt->rowCount() > 0) {
                    $username = $baseUsername . $counter;
                    $counter++;
                } else {
                    break;
                }
            } while (true);
            
            // Check that there is no user with the same email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error = "The email already exists.";
            } else {
                // Password encryption
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // New user entry
                $stmt = $pdo->prepare("INSERT INTO users (full_name, phone, email, username, password) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$fullName, $phone, $email, $username, $hashedPassword]);
                
                $user_id = $pdo->lastInsertId();
                
                // إرسال إشعار للإدمن عن المستخدم الجديد
                if ($user_id) {
                    $adminNotificationManager->notifyAllAdmins("New User Registration", "User {$fullName} ({$email}) has registered in the system.", 'new_user');
                }
                
                $success = "Registration successful! You can now log in.";
                
                $_POST = array();
            }
        } catch(PDOException $e) {
            $error = "An error occurred while registering: " . $e->getMessage();
        }
    }
}

// تعريف متغير $user للاستخدام في الصفحة
$user = [];
if (isset($_SESSION['user_id'])) {
    $user = [
        'full_name' => $_SESSION['full_name'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email']
    ];
} else {
    $user = [
        'full_name' => 'User Name',
        'username' => 'Guest',
        'email' => ''
    ];
}
?>
<!DOCTYPE html>
<html lang="en"> <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&family=Tajawal:wght@400;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary-color: #1e5596;
            --secondary-color: #fa5c40;
            --accent-color: #31aadf;
            --dark-color: #1d1d1d;
            --light-color: #ffffff;
            --purple-color: #5a105e;
            --text-color: #3c4a60;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Tajawal', sans-serif;
            background: linear-gradient(166deg, rgba(255, 255, 255, 0.6) 0%, #013575 50%, var(--purple-color) 135%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.6;
        }

        /* Top Navigation Bar - على اليمين */
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
            /* في تخطيط LTR، هذه العناصر تظهر على اليسار */
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
            /* في تخطيط LTR، هذه العناصر تظهر على اليمين */
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
            /* التعديل: لتظهر القائمة المنسدلة على اليمين ليتناسب مع موقع القسم الأيمن في LTR */
            right: 0; 
            left: auto;
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

        /* Sidebar - على اليسار */
        .sidebar {
            background-color: #1d4c82;
            color: white;
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            transition: transform 0.3s ease;
            z-index: 999;
            overflow-y: auto;
            padding: 20px 0;
            /* الإزاحة للإخفاء تكون لليسار في LTR */
            transform: translateX(-100%);
        }

        .sidebar.open {
            /* الإزاحة للإظهار تكون للموقع صفر من اليسار في LTR */
            transform: translateX(0);
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-logo img {
            height: 35px;
            width: auto;
        }

        .sidebar-logo-text {
            font-weight: bold;
            font-size: 18px;
            color: white;
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
            /* التحديد يكون على اليسار */
            border-left: 3px solid transparent; 
        }

        .nav-item:hover, .nav-item.active {
            background-color: rgba(255,255,255,0.1);
            border-left: 3px solid #fcb408;
        }

        .nav-item i {
            /* ترتيب الـ icon قبل النص في LTR */
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
            /* الانتقال مرتبط بـ margin-left */
            transition: margin-left 0.3s ease; 
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: calc(100vh - 60px);
        }

        .main-content.sidebar-open {
            /* الإزاحة تكون من اليسار في LTR */
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

        /* Responsive */
        @media (max-width: 768px) {
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

        /* Form styles */
        .registration-form-container {
            display: flex;
            justify-content: center;
            width: 100%;
            margin-top: 30px;
        }

        .registration-form {
            background: white;
            border-radius: 24px;
            padding: 40px 0 30px;
            width: 100%;
            max-width: 678px;
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.25);
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 28px;
        }

        .form-title {
            font-family: 'Cairo', sans-serif;
            font-size: 29px;
            font-weight: 700;
            color: var(--text-color);
            text-align: center;
            line-height: 2em;
            margin-bottom: 10px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
            max-width: 376px;
        }

        .form-label {
            font-family: 'Tajawal', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: #121111;
            text-align: center;
            letter-spacing: -0.04em;
        }

        .form-input {
            width: 100%;
            height: 40px;
            padding: 12px;
            border: 1px solid rgba(136, 136, 136, 0.1);
            border-radius: 10px;
            background: rgba(187, 187, 187, 0.15);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #999;
            transition: all 0.3s ease;
            /* التعديل: لمحاذاة النص لليسار في حقول الإدخال */
            text-align: left; 
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent-color);
            background: white;
        }

        .form-input::placeholder {
            color: #999;
        }

        .submit-btn {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0 30px;
            height: 35px;
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 182px;
        }

        .submit-btn:hover {
            background-color: #e04a30;
            transform: translateY(-2px);
        }

        .login-link {
            text-align: center;
            font-family: 'Cairo', sans-serif;
            font-weight: 700;
            color: var(--text-color);
            line-height: 2em;
        }

        .login-link a {
            color: var(--accent-color);
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* رسائل الخطأ والنجاح */
        .alert {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            width: 80%;
            text-align: center;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .registration-form {
                padding: 30px 20px;
                margin: 0 20px;
            }

            .form-group {
                max-width: 100%;
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
                <a href="login.php" class="dropdown-item">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
            </div>
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
           
            <button class="menu-toggle" id="closeSidebarBtn" style="color: white; margin-right: 15px;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
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
            <li class="nav-item">
                <a href="contactus.php">
                    <i class="fas fa-headset"></i>
                    <span>Contact Us</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content" id="mainContent">
        <div class="registration-form-container">
            <form class="registration-form" method="POST" action="">
                <h1 class="form-title">User Registration</h1>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-input" name="full_name" placeholder="Enter your full name" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number (05xxxxxx)</label>
                    <input type="text" class="form-input" name="phone" placeholder="Enter your phone number (05xxxxxxxx)" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input" name="email" placeholder="Enter your email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-input" name="password" placeholder="Enter a secure password (min 6 characters)" required>
                </div>
                
                <button type="submit" class="submit-btn">Register</button>
                
                <p class="login-link">
                   Already have an account? <a href="login.php">Login here</a>
                </p>
            </form>
        </div>

        <footer class="footer" style="text-align: center; padding: 20px; margin-top: 50px; background-color: #f5f5f5; color: #666; font-size: 14px; width: 100%; border-radius: 10px;">
            <p>&copy; 2025 IGM. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // DOM Elements
        const menuToggle = document.getElementById('menuToggle');
        const closeSidebarBtn = document.getElementById('closeSidebarBtn');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mainContent = document.getElementById('mainContent');
        const userInfo = document.getElementById('userInfo');
        const userDropdown = document.getElementById('userDropdown');

        // Set username dynamically
        document.getElementById("userDisplayName").textContent = "<?php echo htmlspecialchars($user['full_name']); ?>";

        // فتح السايد بار
        menuToggle.addEventListener('click', function() {
            sidebar.classList.add('open');
            mainContent.classList.add('sidebar-open');
            sidebarOverlay.classList.add('show');
        });

        // إغلاق السايد بار (باستخدام زر الإغلاق داخل السايد بار)
        closeSidebarBtn.addEventListener('click', function() {
            sidebar.classList.remove('open');
            mainContent.classList.remove('sidebar-open');
            sidebarOverlay.classList.remove('show');
        });

        // إغلاق السايد بار عند النقر على الـ Overlay
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

        // Active navigation item
        const currentPage = window.location.pathname.split('/').pop();
        document.querySelectorAll('.nav-item a').forEach(link => {
            if(link.getAttribute('href') === currentPage) {
                link.parentElement.classList.add('active');
            }
        });
        
        // Auto-hide messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
