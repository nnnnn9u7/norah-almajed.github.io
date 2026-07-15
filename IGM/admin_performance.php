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

// Get performance statistics
try {
    // Overall statistics
    $total_students = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_exams = $pdo->query("SELECT COUNT(*) FROM exam_sessions")->fetchColumn();
    $total_question_files = $pdo->query("SELECT COUNT(*) FROM ai_generated_questions")->fetchColumn();
    $avg_exam_score = $pdo->query("SELECT AVG(score) FROM exam_sessions")->fetchColumn();
    
    // Top performing students
    $top_students = $pdo->query("
        SELECT u.id, u.full_name, u.username, u.email,
               COUNT(es.id) as exam_count,
               AVG(es.score) as avg_score,
               MAX(es.score) as best_score,
               SUM(es.time_spent) as total_study_time
        FROM users u
        LEFT JOIN exam_sessions es ON u.id = es.user_id
        GROUP BY u.id
        HAVING exam_count > 0
        ORDER BY avg_score DESC
        LIMIT 10
    ")->fetchAll();
    
    // Recent exam activity
    $recent_exams = $pdo->query("
        SELECT es.*, u.full_name, u.username, aq.file_name
        FROM exam_sessions es
        JOIN users u ON es.user_id = u.id
        LEFT JOIN ai_generated_questions aq ON es.question_set_id = aq.id
        ORDER BY es.created_at DESC
        LIMIT 15
    ")->fetchAll();
    
    // Study time statistics
    $study_stats = $pdo->query("
        SELECT 
            AVG(time_spent) as avg_study_time,
            MAX(time_spent) as max_study_time,
            MIN(time_spent) as min_study_time,
            COUNT(DISTINCT user_id) as active_students
        FROM exam_sessions
    ")->fetch();
    
    // Get study time distribution for chart
    $study_time_data = $pdo->query("
        SELECT 
            CASE 
                WHEN time_spent < 600 THEN '0-10 min'
                WHEN time_spent BETWEEN 600 AND 1200 THEN '10-20 min'
                WHEN time_spent BETWEEN 1200 AND 1800 THEN '20-30 min'
                WHEN time_spent BETWEEN 1800 AND 2400 THEN '30-40 min'
                ELSE '40+ min'
            END as time_range,
            COUNT(*) as count
        FROM exam_sessions
        GROUP BY time_range
        ORDER BY 
            CASE time_range
                WHEN '0-10 min' THEN 1
                WHEN '10-20 min' THEN 2
                WHEN '20-30 min' THEN 3
                WHEN '30-40 min' THEN 4
                ELSE 5
            END
    ")->fetchAll();
    
    // Get monthly performance data
    $monthly_performance = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            AVG(score) as avg_score,
            COUNT(*) as exam_count
        FROM exam_sessions
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
        LIMIT 6
    ")->fetchAll();
    
    // Get weekly registrations for chart
    $weekly_registrations = $pdo->query("
        SELECT 
            DAYNAME(created_at) as day,
            COUNT(*) as count
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DAYOFWEEK(created_at), DAYNAME(created_at)
        ORDER BY DAYOFWEEK(created_at)
    ")->fetchAll();
    
} catch (PDOException $e) {
    $total_students = 0;
    $total_exams = 0;
    $total_question_files = 0;
    $avg_exam_score = 0;
    $top_students = [];
    $recent_exams = [];
    $study_stats = [];
    $study_time_data = [];
    $monthly_performance = [];
    $weekly_registrations = [];
    error_log("Error fetching performance data: " . $e->getMessage());
}

// Prepare data for JavaScript
$study_time_labels = [];
$study_time_counts = [];
foreach ($study_time_data as $data) {
    $study_time_labels[] = $data['time_range'];
    $study_time_counts[] = $data['count'];
}

$monthly_labels = [];
$monthly_scores = [];
$monthly_counts = [];
foreach ($monthly_performance as $data) {
    $monthly_labels[] = date('M Y', strtotime($data['month'] . '-01'));
    $monthly_scores[] = round($data['avg_score'], 1);
    $monthly_counts[] = $data['exam_count'];
}

$weekly_labels = [];
$weekly_counts = [];
$week_days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
foreach ($week_days as $day) {
    $weekly_labels[] = $day;
    $weekly_counts[] = 0;
}
foreach ($weekly_registrations as $reg) {
    $index = array_search($reg['day'], $weekly_labels);
    if ($index !== false) {
        $weekly_counts[$index] = $reg['count'];
    }
}

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Tracking - IGM Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* استيراد CSS من ملف style.css */
        <?php echo file_get_contents('style.css'); ?>
        
        /* تحسينات إضافية للصفحة */
        .chart-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }
        
        .chart-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }
        
        .chart-container {
            height: 250px;
            position: relative;
            margin: 1rem 0;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--border-light);
        }
        
        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .stats-grid-small {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .stat-mini {
            background: var(--bg-lighter);
            padding: 1rem;
            border-radius: var(--radius);
            text-align: center;
            border-left: 4px solid var(--primary);
        }
        
        .stat-mini .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }
        
        .stat-mini .label {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        .refresh-btn {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .refresh-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .export-btn {
            background: var(--success);
            color: white;
            border: none;
            border-radius: var(--radius);
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .export-btn:hover {
            background: var(--success-dark);
            transform: translateY(-2px);
        }
        
        .chart-actions {
            display: flex;
            gap: 0.5rem;
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
                <span class="logo-text">IGM Admin</span>
            </div>
        </div>
        
        <div class="user-section">
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
                <a href="admin_dashboard.php" class="dropdown-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <form method="POST" style="display: contents;">
                    <button type="submit" name="logout" class="dropdown-item" style="background: none; border: none; width: 100%; text-align: left; cursor: pointer;">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="sidebar" id="sidebar">
        <ul class="nav-menu">
            <li class="nav-item">
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
            <li class="nav-item active">
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

    <div class="main-content" id="mainContent">
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">
                    <i class="fas fa-chart-line"></i> Performance Tracking
                </h1>
                <div class="welcome-message">
                    Monitor student performance, study patterns, and system analytics
                </div>
            </div>
            <div class="user-actions">
                <button onclick="refreshData()" class="refresh-btn">
                    <i class="fas fa-sync-alt"></i> Refresh Data
                </button>
                <button onclick="exportData()" class="export-btn">
                    <i class="fas fa-download"></i> Export Report
                </button>
            </div>
        </div>
        
        <!-- Statistics Overview -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $total_students; ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--success), var(--success-light));">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $total_exams; ?></h3>
                    <p>Exams Taken</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning), var(--warning-light));">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $total_question_files; ?></h3>
                    <p>Question Files</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--info), var(--info-light));">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo round($avg_exam_score, 1); ?>/100</h3>
                    <p>Average Score</p>
                </div>
            </div>
        </div>

        <div class="content-sections">
            <!-- Left Column -->
            <div>
                <!-- Study Time Analytics -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <i class="fas fa-clock"></i> Study Time Distribution
                        </div>
                        <div class="chart-actions">
                            <button onclick="toggleChartType('studyTimeChart')" class="refresh-btn" style="background: var(--accent);">
                                <i class="fas fa-exchange-alt"></i> Type
                            </button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="studyTimeChart"></canvas>
                    </div>
                    <div class="stats-grid-small">
                        <div class="stat-mini">
                            <div class="value"><?php echo round(($study_stats['avg_study_time'] ?? 0) / 60, 1); ?>h</div>
                            <div class="label">Avg Study Time</div>
                        </div>
                        <div class="stat-mini" style="border-left-color: var(--success);">
                            <div class="value"><?php echo $study_stats['active_students'] ?? 0; ?></div>
                            <div class="label">Active Students</div>
                        </div>
                        <div class="stat-mini" style="border-left-color: var(--warning);">
                            <div class="value"><?php echo round(($study_stats['max_study_time'] ?? 0) / 60, 1); ?>h</div>
                            <div class="label">Max Study Time</div>
                        </div>
                    </div>
                </div>

                <!-- Weekly Registrations -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <i class="fas fa-user-plus"></i> Weekly Registrations
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="registrationsChart"></canvas>
                    </div>
                    <div style="text-align: center; margin-top: 1rem; font-size: 0.9rem; color: var(--text-muted);">
                        <i class="fas fa-info-circle"></i> Student registrations over the past 7 days
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div>
                <!-- Performance Trends -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <i class="fas fa-chart-line"></i> Performance Trends
                        </div>
                        <div class="chart-actions">
                            <select onchange="changeTrendPeriod(this.value)" style="padding: 0.4rem; border-radius: var(--radius); border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-primary);">
                                <option value="6">6 Months</option>
                                <option value="12">12 Months</option>
                                <option value="24">24 Months</option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="performanceTrendsChart"></canvas>
                    </div>
                    <div style="text-align: center; margin-top: 1rem; font-size: 0.9rem; color: var(--text-muted);">
                        <i class="fas fa-info-circle"></i> Monthly average scores and exam count
                    </div>
                </div>

                <!-- Top Performing Students -->
                <div class="section-card">
                    <h2 class="section-title">
                        <i class="fas fa-trophy"></i> Top Performing Students
                    </h2>
                    <div class="data-table" style="max-height: 300px; overflow-y: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Avg Score</th>
                                    <th>Exams</th>
                                    <th>Study Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($top_students)): ?>
                                    <?php foreach ($top_students as $student): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--primary-light)); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">
                                                    <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($student['full_name']); ?></div>
                                                    <div style="font-size: 12px; color: var(--text-muted);">@<?php echo htmlspecialchars($student['username']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <span style="font-weight: 700; color: var(--primary);">
                                                    <?php echo round($student['avg_score'], 1); ?>/100
                                                </span>
                                                <div style="width: 60px; height: 6px; background: var(--border-color); border-radius: 3px; overflow: hidden;">
                                                    <div style="height: 100%; background: linear-gradient(90deg, var(--primary), var(--primary-light)); width: <?php echo $student['avg_score']; ?>%;"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge" style="background: linear-gradient(135deg, var(--primary), var(--primary-light));">
                                                <?php echo $student['exam_count']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="color: var(--text-muted); font-size: 13px;">
                                                <?php echo round(($student['total_study_time'] ?? 0) / 60, 1); ?>h
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                            <i class="fas fa-user-graduate" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                            No student data available
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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

        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            initCharts();
        });

        // Study Time Distribution Chart
        function initCharts() {
            // Study Time Distribution Chart
            const studyTimeCtx = document.getElementById('studyTimeChart').getContext('2d');
            
            // Use PHP data passed to JavaScript
            const studyTimeLabels = <?php echo json_encode($study_time_labels); ?>;
            const studyTimeData = <?php echo json_encode($study_time_counts); ?>;
            
            // Colors from your style sheet
            const primaryColor = '#1a4b8c';
            const primaryLightColor = '#2a6fb8';
            const accentColor = '#ff9f1c';
            const successColor = '#2ecc71';
            const infoColor = '#3498db';
            
            window.studyTimeChart = new Chart(studyTimeCtx, {
                type: 'bar',
                data: {
                    labels: studyTimeLabels,
                    datasets: [{
                        label: 'Number of Exams',
                        data: studyTimeData,
                        backgroundColor: [
                            'rgba(26, 75, 140, 0.7)',
                            'rgba(42, 111, 184, 0.7)',
                            'rgba(46, 204, 113, 0.7)',
                            'rgba(52, 152, 219, 0.7)',
                            'rgba(255, 159, 28, 0.7)'
                        ],
                        borderColor: [
                            'rgb(26, 75, 140)',
                            'rgb(42, 111, 184)',
                            'rgb(46, 204, 113)',
                            'rgb(52, 152, 219)',
                            'rgb(255, 159, 28)'
                        ],
                        borderWidth: 1,
                        borderRadius: 6,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(26, 75, 140, 0.9)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            cornerRadius: 6,
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return `Exams: ${context.parsed.y}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                color: '#64748b',
                                font: {
                                    size: 11
                                }
                            },
                            title: {
                                display: true,
                                text: 'Number of Exams',
                                color: '#475569',
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#475569',
                                font: {
                                    size: 11,
                                    weight: '500'
                                }
                            },
                            title: {
                                display: true,
                                text: 'Study Time Range',
                                color: '#475569',
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    }
                }
            });

            // Weekly Registrations Chart
            const registrationsCtx = document.getElementById('registrationsChart').getContext('2d');
            
            // Use PHP data passed to JavaScript
            const weeklyLabels = <?php echo json_encode($weekly_labels); ?>;
            const weeklyData = <?php echo json_encode($weekly_counts); ?>;
            
            window.registrationsChart = new Chart(registrationsCtx, {
                type: 'line',
                data: {
                    labels: weeklyLabels,
                    datasets: [{
                        label: 'Registrations',
                        data: weeklyData,
                        borderColor: '#2ecc71',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#2ecc71',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(46, 204, 113, 0.9)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            cornerRadius: 6,
                            padding: 12,
                            displayColors: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                color: '#64748b',
                                font: {
                                    size: 11
                                }
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.03)'
                            },
                            ticks: {
                                color: '#475569',
                                font: {
                                    size: 11,
                                    weight: '500'
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    }
                }
            });

            // Performance Trends Chart
            const trendsCtx = document.getElementById('performanceTrendsChart').getContext('2d');
            
            // Use PHP data passed to JavaScript
            const monthlyLabels = <?php echo json_encode($monthly_labels); ?>;
            const monthlyScores = <?php echo json_encode($monthly_scores); ?>;
            const monthlyCounts = <?php echo json_encode($monthly_counts); ?>;
            
            window.performanceTrendsChart = new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: monthlyLabels,
                    datasets: [
                        {
                            label: 'Average Score',
                            data: monthlyScores,
                            borderColor: '#1a4b8c',
                            backgroundColor: 'rgba(26, 75, 140, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            yAxisID: 'y',
                            pointStyle: 'circle',
                            pointRadius: 6,
                            pointHoverRadius: 8
                        },
                        {
                            label: 'Number of Exams',
                            data: monthlyCounts,
                            borderColor: '#ff9f1c',
                            backgroundColor: 'rgba(255, 159, 28, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: false,
                            yAxisID: 'y1',
                            pointStyle: 'rect',
                            pointRadius: 6,
                            pointHoverRadius: 8
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: '#475569',
                                font: {
                                    size: 12,
                                    weight: '600'
                                },
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(26, 75, 140, 0.9)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            cornerRadius: 6,
                            padding: 12,
                            usePointStyle: true
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Average Score',
                                color: '#475569',
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            },
                            min: 0,
                            max: 100,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                color: '#64748b',
                                font: {
                                    size: 11
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Number of Exams',
                                color: '#475569',
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                            min: 0,
                            ticks: {
                                color: '#64748b',
                                font: {
                                    size: 11
                                }
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.03)'
                            },
                            ticks: {
                                color: '#475569',
                                font: {
                                    size: 11,
                                    weight: '500'
                                }
                            }
                        }
                    }
                }
            });
        }

        // Toggle chart type
        function toggleChartType(chartId) {
            if (chartId === 'studyTimeChart') {
                const chart = window.studyTimeChart;
                const newType = chart.config.type === 'bar' ? 'doughnut' : 'bar';
                
                chart.destroy();
                
                const ctx = document.getElementById(chartId).getContext('2d');
                const studyTimeLabels = <?php echo json_encode($study_time_labels); ?>;
                const studyTimeData = <?php echo json_encode($study_time_counts); ?>;
                
                const primaryColor = '#1a4b8c';
                const primaryLightColor = '#2a6fb8';
                const accentColor = '#ff9f1c';
                const successColor = '#2ecc71';
                const infoColor = '#3498db';
                
                const colors = [
                    'rgba(26, 75, 140, 0.7)',
                    'rgba(42, 111, 184, 0.7)',
                    'rgba(46, 204, 113, 0.7)',
                    'rgba(52, 152, 219, 0.7)',
                    'rgba(255, 159, 28, 0.7)'
                ];
                
                if (newType === 'doughnut') {
                    window.studyTimeChart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: studyTimeLabels,
                            datasets: [{
                                data: studyTimeData,
                                backgroundColor: colors,
                                borderColor: colors.map(color => color.replace('0.7', '1')),
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: {
                                        color: '#475569',
                                        font: {
                                            size: 11
                                        },
                                        padding: 15
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(26, 75, 140, 0.9)',
                                    titleColor: '#ffffff',
                                    bodyColor: '#ffffff',
                                    borderColor: 'rgba(255, 255, 255, 0.1)',
                                    borderWidth: 1,
                                    cornerRadius: 6,
                                    padding: 12
                                }
                            },
                            cutout: '60%',
                            radius: '80%'
                        }
                    });
                } else {
                    window.studyTimeChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: studyTimeLabels,
                            datasets: [{
                                label: 'Number of Exams',
                                data: studyTimeData,
                                backgroundColor: colors,
                                borderColor: colors.map(color => color.replace('0.7', '1')),
                                borderWidth: 1,
                                borderRadius: 6,
                                borderSkipped: false,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(26, 75, 140, 0.9)',
                                    titleColor: '#ffffff',
                                    bodyColor: '#ffffff',
                                    borderColor: 'rgba(255, 255, 255, 0.1)',
                                    borderWidth: 1,
                                    cornerRadius: 6,
                                    padding: 12,
                                    displayColors: false,
                                    callbacks: {
                                        label: function(context) {
                                            return `Exams: ${context.parsed.y}`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    },
                                    ticks: {
                                        color: '#64748b',
                                        font: {
                                            size: 11
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Number of Exams',
                                        color: '#475569',
                                        font: {
                                            size: 12,
                                            weight: '600'
                                        }
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: '#475569',
                                        font: {
                                            size: 11,
                                            weight: '500'
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Study Time Range',
                                        color: '#475569',
                                        font: {
                                            size: 12,
                                            weight: '600'
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }

        // Change trend period
        function changeTrendPeriod(months) {
            // Show loading
            document.querySelector('.refresh-btn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            document.querySelector('.refresh-btn').disabled = true;
            
            // Simulate API call
            setTimeout(() => {
                // In real implementation, this would fetch new data from the server
                console.log(`Fetching data for ${months} months`);
                
                // For demo, just reload the page with new period
                window.location.href = `admin_performance.php?period=${months}`;
            }, 1000);
        }

        // Refresh data function
        function refreshData() {
            const refreshBtn = event.currentTarget;
            const originalHTML = refreshBtn.innerHTML;
            
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
            
            // Simulate API call
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        }

        // Export data function
        function exportData() {
            // Create a temporary link element
            const link = document.createElement('a');
            
            // Create CSV content
            let csvContent = "Performance Report\n";
            csvContent += "Generated: " + new Date().toLocaleString() + "\n\n";
            csvContent += "Metric,Value\n";
            csvContent += "Total Students," + <?php echo $total_students; ?> + "\n";
            csvContent += "Total Exams," + <?php echo $total_exams; ?> + "\n";
            csvContent += "Average Score," + <?php echo round($avg_exam_score, 1); ?> + "/100\n";
            csvContent += "Active Students," + <?php echo $study_stats['active_students'] ?? 0; ?> + "\n";
            csvContent += "Avg Study Time," + <?php echo round(($study_stats['avg_study_time'] ?? 0) / 60, 1); ?> + " hours\n";
            
            // Convert to blob
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            
            // Set link properties
            link.href = url;
            link.download = `performance-report-${new Date().toISOString().split('T')[0]}.csv`;
            
            // Trigger download
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Show success message
            alert('Report exported successfully!');
        }

        // Auto-refresh data every 5 minutes (300000ms)
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                console.log('Auto-refreshing performance data...');
                // You can uncomment the next line to enable auto-refresh
                // refreshData();
            }
        }, 300000);
    </script>
</body>
</html>