<?php
session_start();
require_once 'db_config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // إصلاح: استخدام المتغير الصحيح واستخدام isset() للتحقق
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    // إصلاح: التحقق من الحقول الفارغة باستخدام المتغير الصحيح
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            // إصلاح: البحث باستخدام البريد الإلكتروني فقط
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['phone'] = $user['phone'];
                    $_SESSION['user_type'] = 'user'; // إضافة نوع المستخدم
                    
                    $success = "Login successful! Redirecting...";
                    header("refresh:2; url=dashboard.php");
                    exit();
                } else {
                    $error = "Invalid email or password.";
                }
            } 
            // ثانياً: إذا لم يكن مستخدم عادي، البحث في جدول المسؤولين
            else {
                $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email = ? AND is_active = TRUE");
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    $admin = $stmt->fetch();
                    
                    if (password_verify($password, $admin['password'])) {
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        $_SESSION['admin_role'] = $admin['role'];
                        $_SESSION['admin_name'] = $admin['full_name'];
                        $_SESSION['user_type'] = 'admin'; // إضافة نوع المستخدم
                        
                        $success = "Admin login successful! Redirecting to dashboard...";
                        header("refresh:2; url=admin_dashboard.php");
                        exit();
                    } else {
                        $error = "Invalid email or password.";
                    }
                } else {
                    $error = "Invalid email or password.";
                }
            }
        } catch(PDOException $e) {
            $error = "An error occurred while logging in: " . $e->getMessage();
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
} elseif (isset($_SESSION['admin_id'])) {
    $user = [
        'full_name' => $_SESSION['admin_name'],
        'username' => $_SESSION['admin_username'],
        'email' => ''
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
<html lang="en"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Login </title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&family=Tajawal:wght@400;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page-body">
    <div class="top-nav">
        <div class="nav-left"> 
            <button class="menu-toggle" id="sidebarOpenBtn">
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
                <span id="userDisplayName"><?php echo htmlspecialchars($user['full_name'] ?? 'User Name'); ?></span>
                <i class="fas fa-chevron-down" style="font-size: 12px;"></i>
            </div>
            
            <div class="user-dropdown" id="userDropdown">
                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'): ?>
                    <!-- قائمة المسؤول -->
                    <a href="admin_dashboard.php" class="dropdown-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Admin Dashboard</span>
                    </a>
                    <a href="admin_users.php" class="dropdown-item">
                        <i class="fas fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                    <a href="admin_reports.php" class="dropdown-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                    <a href="logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                <?php else: ?>
                    <!-- قائمة المستخدم العادي -->
                    <a href="dashboard.php" class="dropdown-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="registration.php" class="dropdown-item">
                        <i class="fas fa-user-plus"></i>
                        <span>Register</span>
                    </a>
                    <a href="logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <ul class="nav-menu">
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'): ?>
                <!-- قائمة المسؤول -->
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
           
            <?php else: ?>
                <!-- قائمة المستخدم العادي -->
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

            <?php endif; ?>
        </ul>
    </div>

    <div class="main-content" id="mainContent">
        <div class="auth-form-container">
            <form class="auth-form" method="POST" action="">
                <h1 class="form-title">User Login</h1>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input" name="email" placeholder="Enter your email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-input" name="password" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" class="submit-btn">Log in</button>
                
                <p class="register-link">
                   Don't have an account? <a href="registration.php">Register here</a>
                </p>

                <!-- قسم بيانات الدخول للمسؤولين 
                <div class="admin-login-section">
                    <h4><i class="fas fa-shield-alt"></i> Admin Login</h4>
                    <p>Administrators can use the same form with admin credentials</p>
                    <div class="credentials">
                        <p><strong>Super Admin:</strong> admin / admin123</p>
                        <p><strong>Moderator:</strong> moderator / password123</p>
                    </div>
                </div>-->
            </form>
        </div>

        <footer class="footer">
            <p>&copy; 2025 IGM. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // DOM Elements
        const sidebarOpenBtn = document.getElementById('sidebarOpenBtn');
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mainContent = document.getElementById('mainContent');
        const userInfo = document.getElementById('userInfo');
        const userDropdown = document.getElementById('userDropdown');

        // Set username dynamically
        document.getElementById("userDisplayName").textContent = "<?php echo htmlspecialchars($user['full_name'] ?? 'User Name'); ?>";

        // فتح السايد بار
        sidebarOpenBtn.addEventListener('click', function() {
            sidebar.classList.add('open');
            // تم تفعيل إزاحة المحتوى الرئيسي ليتناسب مع موقع الشريط الجانبي في LTR
            mainContent.classList.add('sidebar-open');
            sidebarOverlay.classList.add('show');
        });

        // إغلاق السايد بار
        menuToggle.addEventListener('click', function() {
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

        // Add loading state to form
        document.querySelector('form').addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
            btn.disabled = true;
        });
    </script>
</body>
</html>