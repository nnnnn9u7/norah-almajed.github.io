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

// Get statistics
try {
    $total_students = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_complaints = $pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
    $total_exams = $pdo->query("SELECT COUNT(*) FROM exam_sessions")->fetchColumn();
    $today_registrations = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    
    // Recent students with IDs
    $recent_students = $pdo->query("SELECT id, full_name, username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
    
    // Recent complaints
    $recent_complaints = $pdo->query("SELECT full_name, email, notes, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 5")->fetchAll();
    
    // Recent exams
    $recent_exams = $pdo->query("SELECT es.score, es.total_questions, es.correct_answers, es.created_at, u.full_name 
                                FROM exam_sessions es 
                                JOIN users u ON es.user_id = u.id 
                                ORDER BY es.created_at DESC LIMIT 5")->fetchAll();
                                
} catch (PDOException $e) {
    // Set default values if errors
    $total_students = 0;
    $total_complaints = 0;
    $total_exams = 0;
    $today_registrations = 0;
    $recent_students = [];
    $recent_complaints = [];
    $recent_exams = [];
}

// Handle student actions
if (isset($_GET['action']) && isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    $action = $_GET['action'];
    
    if ($action == 'view') {
        // Redirect to student details page
        header("Location: admin_student_details.php?id=" . $student_id);
        exit();
    } elseif ($action == 'delete') {
        // Confirm deletion
        echo "<script>
            if (confirm('Are you sure you want to delete this student?')) {
                window.location.href = 'admin_delete_student.php?id=" . $student_id . "';
            }
        </script>";
    }
}

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - IGM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/theme-toggle.js"></script>
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
            <!-- Theme Toggle Button -->
            <button class="theme-toggle" id="themeToggle">
                <i class="fas fa-moon"></i>
                <span id="themeLabel">Dark</span>
            </button>
            
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
                <form method="POST" style="display: contents;">
                    <button type="submit" name="logout" class="dropdown-item" style="background: none; border: none; width: 100%; text-align: left; cursor: pointer;">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul class="nav-menu">
            <li class="nav-item active">
                <a href="admin_dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
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
        <div class="dashboard-header">
            <h1 class="dashboard-title">Admin Dashboard</h1>
            <div class="welcome-message">
                <i class="fas fa-user-shield"></i> Welcome back, <?php echo htmlspecialchars($admin['full_name']); ?>! 
                You have <strong><?php echo $total_complaints; ?> pending complaints</strong> to review.
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Total Students</h3>
                <div class="stat-value"><?php echo $total_students; ?></div>
                <div class="stat-change">+<?php echo $today_registrations; ?> today</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h3>Complaints</h3>
                <div class="stat-value"><?php echo $total_complaints; ?></div>
                <div class="stat-change">Need attention</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3>Exams Taken</h3>
                <div class="stat-value"><?php echo $total_exams; ?></div>
                <div class="stat-change">Total attempts</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>System Health</h3>
                <div class="stat-value">100%</div>
                <div class="stat-change">All systems operational</div>
            </div>
        </div>

        <div class="content-sections">
            <!-- Left Column -->
            <div>
                <!-- Recent Students -->
                <div class="section-card">
                    <h2 class="section-title">
                        <i class="fas fa-users"></i> Recent Students
                        <a href="admin_students.php">View All →</a>
                    </h2>
                    <?php if (!empty($recent_students)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Email</th>
                                    <th>Joined Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_students as $student): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($student['created_at'])); ?></td>
                                    <td>
                                        <a href="admin_student_details.php?id=<?php echo $student['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="javascript:void(0);" 
                                           onclick="confirmDelete(<?php echo $student['id']; ?>)" 
                                           class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p>No students registered yet</p>
                            <p>Students will appear here once they register</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Recent Complaints -->
                <div class="section-card">
                    <h2 class="section-title">
                        <i class="fas fa-comments"></i> Recent Complaints
                        <a href="admin_complaints.php">View All →</a>
                    </h2>
                    <?php if (!empty($recent_complaints)): ?>
                        <div class="complaints-list">
                            <?php foreach ($recent_complaints as $complaint): ?>
                            <div class="complaint-item">
                                <div class="complaint-header">
                                    <div class="complaint-info">
                                        <strong class="complaint-name"><?php echo htmlspecialchars($complaint['full_name']); ?></strong>
                                        <div class="complaint-email"><?php echo htmlspecialchars($complaint['email']); ?></div>
                                    </div>
                                    <span class="badge badge-warning">Pending</span>
                                </div>
                                <p class="complaint-message"><?php echo htmlspecialchars(substr($complaint['notes'], 0, 150) . (strlen($complaint['notes']) > 150 ? '...' : '')); ?></p>
                                <div class="complaint-actions">
                                    <a href="mailto:<?php echo htmlspecialchars($complaint['email']); ?>" class="btn btn-primary btn-sm" title="Send email response">
                                        <i class="fas fa-reply"></i> Respond
                                    </a>
                                    <button class="btn btn-success btn-sm" onclick="resolveComplaint(this)" title="Mark as resolved">
                                        <i class="fas fa-check"></i> Resolve
                                    </button>
                                    <a href="admin_complaints.php" class="btn btn-info btn-sm" title="View all complaints">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                                <div class="complaint-date"><?php echo date('M j, Y g:i A', strtotime($complaint['created_at'])); ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-comments"></i>
                            <p>No complaints received yet</p>
                            <p>User complaints will appear here</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right Column -->
            <div>
                <!-- Quick Actions -->
                <div class="section-card">
                    <h2 class="section-title">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h2>
                    <div class="actions-list">
                        <a href="admin_hackathons.php?action=add" class="action-btn">
                            <div class="action-icon" style="background: var(--warning);">
                                <i class="fas fa-code"></i>
                            </div>
                            <div class="action-text">
                                <div class="action-title">Create Hackathon</div>
                                <div class="action-desc">Setup a new hackathon event</div>
                            </div>
                            <i class="fas fa-chevron-right" style="color: #ccc;"></i>
                        </a>
                        <a href="admin_students.php" class="action-btn">
                            <div class="action-icon" style="background: var(--success);">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="action-text">
                                <div class="action-title">Students Management</div>
                                <div class="action-desc">Student Account Management</div>
                            </div>
                            <i class="fas fa-chevron-right" style="color: #ccc;"></i>
                        </a>
                        <a href="admin_courses.php?action=add" class="action-btn">
                            <div class="action-icon" style="background: var(--info);">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="action-text">
                                <div class="action-title">Add Course</div>
                                <div class="action-desc">Create new learning material</div>
                            </div>
                            <i class="fas fa-chevron-right" style="color: #ccc;"></i>
                        </a>
                        <a href="admin_complaints.php" class="action-btn">
                            <div class="action-icon" style="background: var(--danger);">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="action-text">
                                <div class="action-title">Manage Complaints</div>
                                <div class="action-desc">Respond to user inquiries</div>
                            </div>
                            <i class="fas fa-chevron-right" style="color: #ccc;"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Recent Exam Results -->
                <div class="section-card">
                    <h2 class="section-title">
                        <i class="fas fa-chart-bar"></i> Recent Exams
                    </h2>
                    <?php if (!empty($recent_exams)): ?>
                        <div class="exams-list">
                            <?php foreach ($recent_exams as $exam): 
                                $percentage = $exam['total_questions'] > 0 ? round(($exam['correct_answers'] / $exam['total_questions']) * 100, 1) : 0;
                                $badge_class = $percentage >= 80 ? 'badge-success' : ($percentage >= 60 ? 'badge-warning' : 'badge-danger');
                            ?>
                            <div class="exam-item">
                                <div class="exam-header">
                                    <strong class="exam-name"><?php echo htmlspecialchars($exam['full_name']); ?></strong>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $percentage; ?>%</span>
                                </div>
                                <div class="exam-details">
                                    Score: <?php echo $exam['score']; ?>/<?php echo $exam['total_questions']; ?> • 
                                    <?php echo date('M j, g:i A', strtotime($exam['created_at'])); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <p>No exam results yet</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Registration Chart -->
                <div class="section-card">
                    <h2 class="section-title">
                        <i class="fas fa-chart-line"></i> Weekly Registrations
                    </h2>
                    <canvas id="registrationsChart" height="150"></canvas>
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

        // Student deletion confirmation
        function confirmDelete(studentId) {
            if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
                window.location.href = 'admin_delete_student.php?id=' + studentId;
            }
        }

        // Resolve complaint function
        function resolveComplaint(button) {
            const complaintItem = button.closest('.complaint-item');
            const badge = complaintItem.querySelector('.badge');
            
            if (confirm('Are you sure you want to mark this complaint as resolved?')) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resolving...';
                
                setTimeout(() => {
                    badge.textContent = 'Resolved';
                    badge.className = 'badge badge-success';
                    button.innerHTML = '<i class="fas fa-check"></i> Resolved';
                    button.disabled = true;
                    button.style.opacity = '0.6';
                }, 500);
            }
        }

        // Registrations Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('registrationsChart').getContext('2d');
            
            // This would typically come from an AJAX request
            // For now, using sample data
            const registrationsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'New Registrations',
                        data: [5, 8, 12, 6, 9, 4, 7],
                        borderColor: '#1e5596',
                        backgroundColor: 'rgba(30, 85, 150, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                stepSize: 2
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>