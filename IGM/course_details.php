<?php
// Start session and check user authentication
session_start();
require_once 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header("Location: login.php");
        exit();
    }
} catch(PDOException $e) {
    // Handle error
    die("Database error: " . $e->getMessage());
}

// Page settings
$page_title = "Course Details";
$page_description = "Detailed information about the course or hackathon";

// Get item ID and type from URL
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$item_type = isset($_GET['type']) ? $_GET['type'] : '';

$selected_item = null;

// Get item details from database based on type
if ($item_id > 0 && in_array($item_type, ['courses', 'hackathons'])) {
    $table = $item_type;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
        $stmt->execute([$item_id]);
        $selected_item = $stmt->fetch();
        
        if ($selected_item) {
            // Add type to the item
            $selected_item['type'] = ucfirst($item_type);
        }
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    }
}

// If item not found, redirect to main page
if (!$selected_item) {
    header("Location: hakathons.php");
    exit();
}

// Update page title with item name
$page_title = $selected_item['title'] . " - " . $page_title;
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(#97c6de 0%, #1e3296 71%);
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
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
            background: white;
            border-radius: 25px;
            padding: 40px;
            margin-top: 80px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .back-button {
            background: #1e5596;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: #fcb408;
            transform: translateY(-2px);
        }

        .item-header {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .item-image {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .item-image img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .item-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .item-title {
            font-size: 32px;
            font-weight: 700;
            color: #1e5596;
            line-height: 1.2;
        }

        .item-meta {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .meta-badge {
            background: #f0f8ff;
            color: #1e5596;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            border: 2px solid #1e5596;
        }

        .format-badge {
            background: #fcb408;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .item-description {
            font-size: 16px;
            line-height: 1.6;
            color: #555;
            margin-top: 10px;
        }

        /* Details Section */
        .details-section {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 30px;
            margin: 30px 0;
        }

        .details-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e5596;
            margin-bottom: 25px;
            text-align: center;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 20px;
            background: white;
            border-radius: 15px;
            border-left: 4px solid #1e5596;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .detail-label {
            font-weight: 600;
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-weight: 700;
            color: #1e5596;
            font-size: 16px;
        }

        /* Action Button */
        .action-section {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e9ecef;
        }

        .action-button {
            background: #1e5596;
            color: white;
            border: none;
            padding: 18px 50px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            text-align: center;
        }

        .action-button:hover {
            background: #fcb408;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(252, 180, 8, 0.3);
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .main-content {
                padding: 25px 20px;
                border-radius: 20px;
                margin-top: 70px;
            }

            .item-header {
                grid-template-columns: 1fr;
                gap: 25px;
            }

            .item-image img {
                height: 200px;
            }

            .item-title {
                font-size: 26px;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .nav-container {
                padding: 15px 20px;
            }
            
            .desktop-nav {
                flex-direction: column;
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .item-meta {
                flex-direction: column;
                gap: 10px;
            }

            .meta-badge, .format-badge {
                width: fit-content;
            }

            .item-title {
                font-size: 22px;
            }

            .details-title {
                font-size: 20px;
            }

            .action-button {
                width: 100%;
                padding: 16px 30px;
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
                <a href="#" class="dropdown-item" id="logoutBtn">
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
    <a href="notifications.php">
        <i class="fas fa-bell"></i>
        <span>Notifications</span>
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

    <div class="container">
        <div class="main-content">
            <a href="hakathons.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Courses & Hackathons
            </a>

            <?php if ($selected_item): ?>
            <div class="item-header">
                <div class="item-image">
                    <img src="<?php echo $selected_item['image_url'] ?: 'https://via.placeholder.com/500x300?text=No+Image'; ?>" alt="<?php echo $selected_item['title']; ?>">
                </div>
                <div class="item-info">
                    <h1 class="item-title"><?php echo $selected_item['title']; ?></h1>
                    <div class="item-meta">
                        <span class="meta-badge"><?php echo date('M j, Y', strtotime($selected_item['date'])); ?></span>
                        <span class="meta-badge"><?php echo $selected_item['type']; ?></span>
                        <span class="format-badge"><?php echo $selected_item['format'] ?? 'Online'; ?></span>
                    </div>
                    <p class="item-description"><?php echo $selected_item['description']; ?></p>
                </div>
            </div>

            <div class="details-section">
                <h2 class="details-title"><?php echo $selected_item['type']; ?> Details</h2>
                <div class="details-grid">
                    <?php if ($selected_item['type'] === 'Hackathons'): ?>
                        <div class="detail-item">
                            <div class="detail-label">Duration</div>
                            <div class="detail-value"><?php echo $selected_item['duration']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Location</div>
                            <div class="detail-value"><?php echo $selected_item['location']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Format</div>
                            <div class="detail-value"><?php echo $selected_item['format']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Prize Pool</div>
                            <div class="detail-value"><?php echo $selected_item['prize']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Registration Deadline</div>
                            <div class="detail-value"><?php echo date('M j, Y', strtotime($selected_item['registration_deadline'])); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Organizer</div>
                            <div class="detail-value"><?php echo $selected_item['organizer']; ?></div>
                        </div>
                    <?php else: ?>
                        <div class="detail-item">
                            <div class="detail-label">Start Date</div>
                            <div class="detail-value"><?php echo date('M j, Y', strtotime($selected_item['date'])); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Duration</div>
                            <div class="detail-value"><?php echo $selected_item['duration']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Level</div>
                            <div class="detail-value"><?php echo $selected_item['level']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Instructor</div>
                            <div class="detail-value"><?php echo $selected_item['instructor']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Format</div>
                            <div class="detail-value"><?php echo $selected_item['format']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Price</div>
                            <div class="detail-value"><?php echo $selected_item['price']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Certificate</div>
                            <div class="detail-value"><?php echo $selected_item['certificate']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Language</div>
                            <div class="detail-value"><?php echo $selected_item['language']; ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="action-section">
                <?php if (!empty($selected_item['external_link'])): ?>
                    <a href="<?php echo $selected_item['external_link']; ?>" target="_blank" class="action-button">
                        <i class="fas fa-external-link-alt"></i> 
                        <?php echo $selected_item['type'] === 'Hackathons' ? 'View Hackathon Details' : 'View Course Details'; ?>
                    </a>
                <?php else: ?>
                    <button class="action-button" style="background: #6c757d;">
                        <i class="fas fa-info-circle"></i> 
                        No External Link Available
                    </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
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
        const logoutBtn = document.getElementById('logoutBtn');

        // Set username dynamically
        document.getElementById("userDisplayName").textContent = "<?php echo htmlspecialchars($user['full_name']); ?>";

        // Toggle sidebar
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('show');
        });

        // Close sidebar when clicking on overlay
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
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
            window.location.href = 'logout.php';
        });

        // Active navigation item
        const currentPage = window.location.pathname.split('/').pop();
        document.querySelectorAll('.nav-item a').forEach(link => {
            if(link.getAttribute('href') === currentPage) {
                link.parentElement.classList.add('active');
            }
        });
    </script>
</body>
</html>