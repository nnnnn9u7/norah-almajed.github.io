<?php
session_start();
require_once 'db_config.php';

$error = '';

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Log login attempt (for debugging)
    error_log("Login attempt - Username: $username");
    
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND is_active = TRUE");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            error_log("User found - Password hash: " . substr($admin['password'], 0, 20) . "...");
            
            // Verify password
            if (password_verify($password, $admin['password'])) {
                error_log("Password verification SUCCESS");
                
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_name'] = $admin['full_name'];
                
                header("Location: admin_dashboard.php");
                exit();
            } else {
                error_log("Password verification FAILED");
                $error = "Invalid username or password";
            }
        } else {
            error_log("User NOT found");
            $error = "Invalid username or password";
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $error = "Database error: Please check your setup";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - IGM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary: #1e5596;
            --secondary: #21a7de;
            --accent: #fcb408;
            --admin-dark: #0f2d53;
            --text-blue: #1e5596;
            --light-blue: #f0f7ff;
            --border-color: #e1e8f0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--admin-dark) 0%, var(--primary) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        /* Top Navigation Bar - White Theme */
        .top-nav {
            background-color: #ffffff;
            color: var(--text-blue);
            padding: 12px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 15px rgba(30, 85, 150, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 70px;
            border-bottom: 1px solid var(--border-color);
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .menu-toggle {
            background: none;
            border: none;
            color: var(--text-blue);
            font-size: 22px;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .menu-toggle:hover {
            background-color: var(--light-blue);
            transform: rotate(90deg);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo img {
            height: 40px;
            width: auto;
            filter: brightness(0.9);
        }

        .logo-text {
            font-weight: 700;
            font-size: 20px;
            color: var(--text-blue);
            letter-spacing: -0.5px;
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
            gap: 12px;
            cursor: pointer;
            padding: 8px 15px;
            border-radius: 10px;
            transition: all 0.3s;
            border: 1px solid transparent;
        }

        .user-info:hover {
            background-color: var(--light-blue);
            border-color: var(--border-color);
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            box-shadow: 0 3px 8px rgba(30, 85, 150, 0.2);
        }

        .user-details {
            text-align: left;
        }

        .user-name {
            font-weight: 600;
            font-size: 15px;
            color: var(--text-blue);
        }

        .user-role {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }

        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(30, 85, 150, 0.15);
            width: 200px;
            z-index: 1001;
            display: none;
            overflow: hidden;
            border: 1px solid var(--border-color);
            margin-top: 5px;
        }

        .user-dropdown.show {
            display: block;
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dropdown-item {
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-blue);
            text-decoration: none;
            transition: all 0.3s;
            border-bottom: 1px solid #f1f5f9;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background-color: var(--light-blue);
            color: var(--primary);
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
            color: var(--primary);
        }

        /* Sidebar - White Theme */
        .sidebar {
            background-color: #ffffff;
            color: var(--text-blue);
            width: 280px;
            height: 100vh;
            position: fixed;
            top: 70px;
            left: -280px;
            transition: left 0.3s ease;
            z-index: 999;
            overflow-y: auto;
            padding: 25px 0;
            border-right: 1px solid var(--border-color);
            box-shadow: 5px 0 20px rgba(30, 85, 150, 0.08);
        }

        .sidebar.open {
            left: 0;
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(30, 85, 150, 0.3);
            z-index: 998;
            display: none;
            backdrop-filter: blur(2px);
        }

        .sidebar-overlay.show {
            display: block;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            padding: 15px 25px;
            margin: 8px 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            border-left: 4px solid transparent;
            border-radius: 8px;
        }

        .nav-item:hover {
            background-color: var(--light-blue);
            border-left: 4px solid var(--primary);
            transform: translateX(5px);
        }

        .nav-item.active {
            background-color: var(--light-blue);
            border-left: 4px solid var(--accent);
            color: var(--text-blue);
        }

        .nav-item i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
            color: var(--primary);
            font-size: 18px;
        }

        .nav-item a {
            color: var(--text-blue);
            text-decoration: none;
            display: flex;
            align-items: center;
            width: 100%;
            font-weight: 500;
            font-size: 15px;
        }

        /* Login Form Styles - Enhanced and Better Sized */
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 420px;
            padding: 40px 35px;
            margin: 80px 20px 20px 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .login-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .login-logo img {
            height: 65px;
            width: auto;
            filter: brightness(0.9);
        }
        
        .login-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-blue);
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }
        
        .login-subtitle {
            color: #64748b;
            font-size: 15px;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-blue);
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            background: #f8fafc;
            color: var(--text-blue);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(30, 85, 150, 0.1);
        }
        
        .form-control::placeholder {
            color: #94a3b8;
        }
        
        .login-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(30, 85, 150, 0.25);
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 85, 150, 0.35);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 14px 18px;
            margin-bottom: 25px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .setup-help {
            background: var(--light-blue);
            border-radius: 10px;
            padding: 20px;
            margin-top: 25px;
            border-left: 4px solid var(--accent);
        }
        
        .setup-help h4 {
            color: var(--text-blue);
            margin-bottom: 10px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .setup-help p {
            color: #64748b;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 10px;
        }
        
        .setup-help a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .setup-help a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }
        
        .demo-credentials {
            background: linear-gradient(135deg, #f0f7ff 0%, #e6f3ff 100%);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            border: 2px dashed var(--primary);
        }
        
        .demo-credentials h4 {
            color: var(--text-blue);
            margin-bottom: 10px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .demo-credentials p {
            color: #475569;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 6px;
            font-weight: 500;
        }

        /* Default user info for login page */
        .default-user .user-name {
            font-weight: 700;
        }

        .default-user .user-role {
            font-style: italic;
            color: #94a3b8;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .top-nav {
                padding: 12px 20px;
            }
            
            .login-container {
                padding: 30px 25px;
                margin: 70px 15px 15px 15px;
                max-width: 380px;
            }
            
            .sidebar {
                width: 280px;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 25px 20px;
                margin: 70px 10px 10px 10px;
                max-width: 340px;
            }
            
            .login-title {
                font-size: 24px;
            }
            
            .login-logo img {
                height: 55px;
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
            <div class="user-info default-user" id="userInfo">
                <div class="user-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="user-details">
                    <div class="user-name">Administrator</div>
                    <div class="user-role">Please login</div>
                </div>
                <i class="fas fa-chevron-down" style="font-size: 12px;"></i>
            </div>
            
            <div class="user-dropdown" id="userDropdown">
                <a href="admin_dashboard.php" class="dropdown-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span> Admin Dashboard  </span>
                </a>
                <a href="dashboard.php" class="dropdown-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <button type="button" class="dropdown-item" onclick="alert('Please login first')">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
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
            <li class="nav-item active">
                <a href="admin_complaints.php">
                    <i class="fas fa-comments"></i>
                    <span class="nav-text">Complaints & Support</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <img src="img/logo.png" alt="IGM Logo">
            </div>
            <h1 class="login-title">Admin Login</h1>
            <p class="login-subtitle">Access the administration dashboard</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            
            <div class="setup-help">
                <h4><i class="fas fa-wrench"></i> Setup Required</h4>
                <p>It looks like the admin user hasn't been created yet or there's a configuration issue.</p>
                <p><strong>Quick Fix:</strong> Run the setup script to create the admin user:</p>
                <p><a href="create_admin.php" target="_blank">Run Admin Setup Script</a></p>
                <p><small>This will create the admin table and user with password: <strong>admin123</strong></small></p>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                       placeholder="Enter your username">
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" required 
                       placeholder="Enter your password">
            </div>
            
            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Login to Dashboard
            </button>
        </form>
        
        <!-- Demo credentials for testing 
        <div class="demo-credentials">
            <h4><i class="fas fa-info-circle"></i> Demo Credentials</h4>
            <p><strong>Username:</strong> admin</p>
            <p><strong>Password:</strong> admin123</p>
            <p><small>Make sure to run the setup script first if this is your first time.</small></p>
        </div>
    </div>-->
    
    <script>
        // Add loading state to form
        document.querySelector('form').addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
            btn.disabled = true;
        });

        // Get DOM elements
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
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

        // Prevent dropdown close when clicking inside dropdown
        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    </script>
</body>
</html>