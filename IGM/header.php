<?php
// header.php
// يجب أن يكون المتغير $user معرفاً قبل تضمين هذا الملف
?>
<!DOCTYPE html>
<html dir="ltr" lang="<?php echo isset($_GET['lang']) ? $_GET['lang'] : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="js/theme-toggle.js"></script>
    <title><?php echo isset($pageTitle) ? $pageTitle : 'IGM'; ?></title>
</head>
<body class="<?php echo isset($_GET['lang']) && $_GET['lang'] == 'ar' ? 'rtl-lang' : ''; ?>">
    <!-- Top Navigation Bar -->
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
            <!-- زر تبديل الثيم -->
            <button class="theme-toggle" id="themeToggle">
                <i class="fas fa-moon"></i>
                <span id="themeLabel">Dark</span>
            </button>

            <!-- زر الترجمة - ثابت في أقصى اليمين -->
            <?php
            $current_lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
            $switch_lang = ($current_lang == 'ar') ? 'en' : 'ar';
            ?>
            <div class="language-switcher">
                <a href="?lang=<?php echo $switch_lang; ?>" class="language-btn">
                    <i class="fas fa-globe"></i>
                    <span class="language-text">
                        <?php echo ($current_lang == 'ar') ? 'English' : 'العربية'; ?>
                    </span>
                </a>
            </div>

            <?php if (basename($_SERVER['PHP_SELF']) == 'home.php'): ?>
            <!-- جرس الإشعارات فقط في الصفحة الرئيسية -->
            <div class="notification-bell" id="notificationBell">
                <i class="fas fa-bell"></i>
                <span class="notification-count" style="display: none;">2</span>
            </div>
            <?php endif; ?>
            
            <div class="user-info" id="userInfo">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <span id="userDisplayName"><?php echo htmlspecialchars($user['full_name']); ?></span>
                <i class="fas fa-chevron-down" style="font-size: 12px;"></i>
            </div>
            
            <div class="user-dropdown" id="userDropdown">
                <a href="dashboard.php<?php echo isset($_GET['lang']) ? '?lang='.$_GET['lang'] : ''; ?>" class="dropdown-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="logout.php" class="dropdown-item" id="logoutBtn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
            
            <?php if (basename($_SERVER['PHP_SELF']) == 'home.php'): ?>
            <!-- Dropdown الإشعارات -->
            <div class="notification-dropdown" id="notificationDropdown">
                <div class="notification-header">
                    <h3>Notifications</h3>
                    <button class="clear-all" id="clearAllBtn">Clear All</button>
                </div>
                <div class="notification-list" id="notificationList">
                    <div class="notification-item">
                        <div class="notification-icon"><i class="fas fa-home"></i></div>
                        <div class="notification-content">
                            <p>Welcome to IGM Learning Platform!</p>
                            <span class="notification-time">Just now</span>
                        </div>
                    </div>
                    <div class="notification-item">
                        <div class="notification-icon"><i class="fas fa-book"></i></div>
                        <div class="notification-content">
                            <p>Complete your study behavior assessment</p>
                            <span class="notification-time">3 min ago</span>
                        </div>
                    </div>
                </div>
                <div class="notification-footer">
                    <a href="#" class="view-all">View All</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>