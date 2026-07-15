<?php
session_start();
require_once 'db_config.php';

if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            header("Location: login.php");
            exit();
        }
    } catch(PDOException $e) {
        $user = [
            'full_name' => 'User Name',
            'username' => 'Guest', 
            'email' => ''
        ];
    }
} else {
    $user = [
        'full_name' => 'User Name',
        'username' => 'Guest', 
        'email' => ''
    ];
}

$page_title = "Courses & Hackathons";
$page_description = "Explore our courses and hackathons";

try {
    $courses_stmt = $pdo->prepare("
        SELECT 
            id,
            title,
            date,
            image_url as image,
            'Courses' as type,
            description,
            duration,
            level,
            instructor,
            format,
            price,
            certificate,
            language,
            status,
            external_link,
            created_at
        FROM courses 
        WHERE status IN ('active', 'upcoming')
        ORDER BY created_at DESC
    ");
    $courses_stmt->execute();
    $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $courses = [];
    error_log("Error fetching courses: " . $e->getMessage());
}

try {
    $debug_stmt = $pdo->query("SELECT id, title, status, date FROM hackathons");
    $all_hackathons = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("All hackathons in database: " . print_r($all_hackathons, true));
    echo "<!-- DEBUG: All hackathons in DB: " . count($all_hackathons) . " -->";
    
    $hackathons_stmt = $pdo->prepare("
        SELECT 
            id,
            title,
            date,
            image_url as image,
            'Hackathons' as type,
            description,
            duration,
            location,
            format,
            prize,
            participants_limit,
            registration_deadline,
            organizer,
            status,
            external_link,
            created_at
        FROM hackathons 
        ORDER BY created_at DESC
    ");
    $hackathons_stmt->execute();
    $hackathons = $hackathons_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Hackathons fetched for display: " . count($hackathons));
    echo "<!-- DEBUG: Hackathons fetched: " . count($hackathons) . " -->";
    
} catch (PDOException $e) {
    $hackathons = [];
    error_log("Error fetching hackathons: " . $e->getMessage());
    echo "<!-- DEBUG: Error fetching hackathons: " . $e->getMessage() . " -->";
}

error_log("Courses found: " . count($courses));
error_log("Hackathons found: " . count($hackathons));

$all_items = array_merge($courses, $hackathons);

foreach ($all_items as &$item) {
    $item['type'] = $item['type'] ?? 'Unknown';
    
    $item['expired'] = false;
    
    if ($item['type'] === 'Hackathons') {
        $current_date = date('Y-m-d');
        $item_date = $item['date'] ?? null;
        
        if (in_array($item['status'], ['completed', 'cancelled'])) {
            $item['expired'] = true;
        }
        elseif ($item_date && $item_date < $current_date && !in_array($item['status'], ['active', 'ongoing'])) {
            $item['expired'] = true;
        }
    } else {
        $item['expired'] = ($item['status'] === 'completed' || $item['status'] === 'cancelled');
    }
    
    $image_url = $item['image'] ?? '';
    
    $is_local_file = !empty($image_url) && 
                    !filter_var($image_url, FILTER_VALIDATE_URL) && 
                    file_exists($image_url);
    
    if ($is_local_file) {
        $item['image'] = '/' . ltrim(str_replace('\\', '/', $image_url), '/');
    } 
    elseif (empty($image_url) || 
            strpos($image_url, 'google.com/imgres') !== false) {
        
        $item['image'] = 'https://via.placeholder.com/300x200/1e5596/ffffff?text=' . 
                        urlencode(($item['type'] ?? 'Item') . ': ' . substr($item['title'] ?? 'No Title', 0, 20));
    } 
    elseif (!preg_match('/^https?:\/\//', $image_url)) {
        $item['image'] = 'https://' . ltrim($image_url, '/');
    }
    
    if (empty($item['date']) || $item['date'] == '0000-00-00') {
        $item['date'] = null;
    }
    
    $item['duration'] = $item['duration'] ?? 'Not specified';
    $item['level'] = $item['level'] ?? 'All Levels';
    $item['description'] = $item['description'] ?? 'No description available';
    $item['date'] = $item['date'] ?? null;
    
    if (strlen($item['description']) > 100) {
        $item['description'] = substr($item['description'], 0, 100) . '...';
    }
}
unset($item);

$search_query = '';
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = trim($_GET['search']);
    $filtered_items = array_filter($all_items, function($item) use ($search_query) {
        return stripos($item['title'], $search_query) !== false || 
               stripos($item['description'], $search_query) !== false;
    });
} else {
    $filtered_items = $all_items;
}

$current_filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$valid_filters = ['all', 'courses', 'hackathons'];

if (!in_array($current_filter, $valid_filters)) {
    $current_filter = 'all';
}

if ($current_filter !== 'all') {
    $filtered_items = array_filter($filtered_items, function($item) use ($current_filter) {
        $item_type = strtolower($item['type'] ?? 'unknown');
        $filter_type = strtolower($current_filter);
        return $item_type === $filter_type;
    });
}

error_log("Final filtered items: " . count($filtered_items));
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- External CSS -->
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
            <li class="nav-item active">
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
        <div class="main-container">
            <div class="noise-pattern">
                <div class="background-overlay"></div>
            </div>
            
            <div class="content-container">
                <div class="variant-1">
                    <h1 class="page-title">Courses & Hackathons</h1>
                    
                    <!-- Debug Info -->
                    <?php
                    $debug_info = "<!-- Debug Info: ";
                    $debug_info .= "Courses: " . count($courses) . ", ";
                    $debug_info .= "Hackathons: " . count($hackathons) . ", ";
                    $debug_info .= "Filter: " . $current_filter . ", ";
                    $debug_info .= "Search: " . $search_query . " -->";
                    echo $debug_info;
                    ?>
                    
                    <!-- Search Bar -->
                    <div class="search-container">
                        <form method="GET" class="search-form">
                            <input type="text" name="search" class="search-input" placeholder="Search for courses or hackathons..." value="<?php echo htmlspecialchars($search_query); ?>">
                            <button type="submit" class="search-button">Search</button>
                        </form>
                    </div>
                    
                    <!-- Filter Tabs -->
                    <div class="filter-tabs">
                        <div class="tab-container">
                            <a href="?filter=all<?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>" class="tab <?php echo $current_filter === 'all' ? 'active' : ''; ?>">
                                <p>All</p>
                            </a>
                        </div>
                        <div class="tab-container">
                            <a href="?filter=courses<?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>" class="tab <?php echo $current_filter === 'courses' ? 'active' : ''; ?>">
                                <p>Courses</p>
                            </a>
                        </div>
                        <div class="tab-container">
                            <a href="?filter=hackathons<?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>" class="tab <?php echo $current_filter === 'hackathons' ? 'active' : ''; ?>">
                                <p>Hackathons</p>
                            </a>
                        </div>
                    </div>

                    <!-- Results Count -->
                    <div class="results-count">
                        Found <?php echo count($filtered_items); ?> <?php echo count($filtered_items) === 1 ? 'item' : 'items'; ?>
                        <?php if ($search_query): ?>
                            for "<?php echo htmlspecialchars($search_query); ?>"
                        <?php endif; ?>
                        <?php if ($current_filter !== 'all'): ?>
                            in <?php echo ucfirst($current_filter); ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Items Grid -->
                    <div class="hackathons-grid">
                        <?php if (empty($filtered_items)): ?>
                            <div class="no-results">
                                <i class="fas fa-search"></i>
                                <p>No courses or hackathons found matching your criteria.</p>
                                <p>Try adjusting your search or filter.</p>
                                <?php if (count($all_items) === 0): ?>
                                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                                    <p style="color: #666; font-size: 14px;">
                                    </p>
                                    <p style="color: #666; font-size: 12px; margin-top: 10px;">
                                        Debug Info:<br>
                                        Courses: <?php echo count($courses); ?><br>
                                        Hackathons: <?php echo count($hackathons); ?><br>
                                        Total: <?php echo count($all_items); ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <?php foreach ($filtered_items as $item): 
                                $item_type = $item['type'] ?? 'Unknown';
                                $type_lower = strtolower($item_type);
                            ?>
                            <a href="course_details.php?id=<?php echo $item['id']; ?>&type=<?php echo $type_lower; ?>" class="hackathon-card <?php echo $item['expired'] ? 'expired' : ''; ?>" data-type="<?php echo $item_type; ?>">
                                <?php if ($item['expired']): ?>
                                    <div class="expired-badge">Ended</div>
                                <?php endif; ?>
                                <div class="card-image">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                         onerror="this.src='https://via.placeholder.com/300x200/1e5596/ffffff?text=<?php echo urlencode($item['type'] . ': ' . substr($item['title'], 0, 15)); ?>'"
                                         style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div class="card-content">
                                    <h6 class="card-title <?php echo $item['expired'] ? 'expired-title' : ''; ?>"><?php echo htmlspecialchars($item['title']); ?></h6>
                                    <p class="card-date <?php echo $item['expired'] ? 'expired-date' : ''; ?>">
                                        <?php 
                                        if (!empty($item['date']) && $item['date'] != '0000-00-00') {
                                            echo date('M j, Y', strtotime($item['date']));
                                        } else {
                                            echo 'Date not set';
                                        }
                                        ?>
                                    </p>
                                    <p class="card-description"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <div class="card-tag">
                                        <div class="tag">
                                            <p><?php echo $item_type; ?></p>
                                        </div>
                                        <?php if ($item_type === 'Courses'): ?>
                                            <span class="type-badge"><?php echo htmlspecialchars($item['level'] ?? 'All Levels'); ?></span>
                                        <?php else: ?>
                                            <span class="type-badge"><?php echo htmlspecialchars($item['duration'] ?? 'Not specified'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
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
            window.location.href = 'logout.php';
        });

        // Active navigation item
        const currentPage = window.location.pathname.split('/').pop();
        document.querySelectorAll('.nav-item a').forEach(link => {
            if(link.getAttribute('href') === currentPage) {
                link.parentElement.classList.add('active');
            }
        });

        // Improved filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Update active states based on current filter
            const currentFilter = '<?php echo $current_filter; ?>';
            const tabs = document.querySelectorAll('.tab');
            
            tabs.forEach(tab => {
                // Extract filter type from URL
                const urlParams = new URLSearchParams(tab.href.split('?')[1]);
                const tabFilter = urlParams.get('filter') || 'all';
                
                if (tabFilter === currentFilter) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });

            // Add loading state to cards
            const cards = document.querySelectorAll('.hackathon-card:not(.expired)');
            cards.forEach(card => {
                card.addEventListener('click', function(e) {
                    // Only apply loading for non-expired cards
                    if (!this.classList.contains('expired')) {
                        this.style.opacity = '0.7';
                        this.style.pointerEvents = 'none';
                    }
                });
            });

            // Prevent clicking on expired cards
            const expiredCards = document.querySelectorAll('.hackathon-card.expired');
            expiredCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    e.preventDefault();
                    alert('This event has ended. Please check our upcoming events.');
                });
            });
            
            // Debug info
            console.log('Current Filter:', currentFilter);
            console.log('Total Items:', <?php echo count($filtered_items); ?>);
            console.log('Courses:', <?php echo count($courses); ?>);
            console.log('Hackathons:', <?php echo count($hackathons); ?>);
        });
    </script>
</body>
</html>