<?php

session_start();
require_once 'check_session.php';
require_once 'db_config.php';
$user = checkUserSession();
$success_message = '';
$error_message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $notes = trim($_POST['notes']);
    
    // التحقق من الحقول المطلوبة
    if (empty($fullName) || empty($email)) {
        $error_message = 'Please fill in all required fields.';
    } 
    // التحقق من صحة البريد الإلكتروني
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        try {
            // إدخال البيانات في قاعدة البيانات
            $stmt = $pdo->prepare("INSERT INTO contact_messages (full_name, email, notes) VALUES (?, ?, ?)");
            $stmt->execute([$fullName, $email, $notes]);
            
            // رسالة نجاح
            $success_message = 'Thank you for your message! We will get back to you soon.';
            $success = true;
            
            // تفريغ الحقول بعد الإرسال الناجح
            $_POST = array();
            
        } catch(PDOException $e) {
            $error_message = 'Sorry, an error occurred while sending your message. Please try again later.';
        }
    }
}

// إرسال إشعار للإدمن عن الشكوى الجديدة
if ($success) {
    // يمكنك تفعيل هذا الجزء عندما يكون نظام الإشعارات جاهزاً
    // if (isset($adminNotificationManager) && method_exists($adminNotificationManager, 'notifyAllAdmins')) {
    //     $adminNotificationManager->notifyAllAdmins("New Support Request", "A new support request has been submitted.", 'new_complaint');
    // }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - IGM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
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
                <a href="#" class="dropdown-item" id="logoutBtn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Login</span>
                </a>
            </div>
        </div>
    </div>
    <!-- Sidebar Overlay (for mobile) -->
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
                <a href="contactus.php">
                    <i class="fas fa-headset"></i>
                    <span>Contact Us</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="form-container">
            <form class="contact-form" method="POST" action="">
                <h2 class="form-title">Support us</h2>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" class="form-input" name="full_name" placeholder="Enter your full name" required 
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-input" name="email" placeholder="example@gmail.com" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Your problem </label>
                    <textarea class="form-input" name="notes" placeholder="Enter any additional notes"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                </div>
                
                <button type="submit" class="submit-btn">Submit</button>
                
                <h2 class="form-title">Contact Information</h2>
                
                <div class="contact-details">
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div class="contact-item-content">
                            <div class="contact-item-label">Phone</div>
                            <a href="tel:+966503363943">+966 50 336 3943</a>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div class="contact-item-content">
                            <div class="contact-item-label">Email</div>
                            <a href="mailto:info@IGM.com">info@IGM.com</a>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div class="contact-item-content">
                            <div class="contact-item-label">Location</div>
                            <p>King Faisal University</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <?php include 'footer.php'; ?>
    </div>

    <script>
        // DOM Elements
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mainContent = document.getElementById('mainContent');
        const userInfo = document.getElementById('userInfo');
        const userDropdown = document.getElementById('userDropdown');
        const logoutBtn = document.getElementById('logoutBtn');

        // Set username dynamically
        document.getElementById("userDisplayName").textContent = "<?php echo htmlspecialchars($user['full_name']); ?>";

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

        // Login functionality
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'login.php';
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