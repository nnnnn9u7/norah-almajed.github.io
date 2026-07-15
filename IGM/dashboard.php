<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT full_name, username, email, phone FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$recent_results = [];
$performance_stats = [
    'total_tests' => 0,
    'total_correct' => 0,
    'total_questions' => 0,
    'total_score' => 0,
    'average_score' => 0,
    'success_rate' => 0,
    'best_score' => 0,
    'weakest_area' => 'Not enough data',
    'strength_area' => 'Not enough data',
    'progress_trend' => 'stable'
];

try {
    $stmt = $pdo->prepare("
        SELECT 
            es.id, es.score, es.total_questions, es.correct_answers,
            es.wrong_answers, es.time_spent, es.average_time_per_question,
            es.created_at, es.status, agq.file_name
        FROM exam_sessions es 
        LEFT JOIN ai_generated_questions agq ON es.question_set_id = agq.id
        WHERE es.user_id = ? 
        ORDER BY es.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $all_results = $stmt->fetchAll();
    
    $recent_results = array_slice($all_results, 0, 5);
    
    $total_tests = count($all_results);
    $total_correct = 0;
    $total_questions = 0;
    $total_score = 0;
    $best_score = 0;
    $scores = [];
    
    foreach ($all_results as $result) {
        $total_correct += $result['correct_answers'];
        $total_questions += $result['total_questions'];
        $total_score += $result['score'];
        $scores[] = $result['score'];
        
        if ($result['score'] > $best_score) {
            $best_score = $result['score'];
        }
    }
    
    $success_rate = $total_questions > 0 ? round(($total_correct / $total_questions) * 100, 1) : 0;
    $average_score = $total_tests > 0 ? round($total_score / $total_tests, 1) : 0;
    
    if (count($scores) >= 2) {
        $recent_avg = array_sum(array_slice($scores, 0, 3)) / min(3, count($scores));
        $older_avg = array_sum(array_slice($scores, 3)) / max(1, count($scores) - 3);
        $performance_stats['progress_trend'] = $recent_avg > $older_avg ? 'improving' : ($recent_avg < $older_avg ? 'declining' : 'stable');
    }
    
    if (count($recent_results) > 0) {
        $lowest_score = min($scores);
        $performance_stats['weakest_area'] = $lowest_score < 70 ? 'Concept Understanding' : 'Time Management';
        $performance_stats['strength_area'] = $average_score >= 80 ? 'Analytical Skills' : ($average_score >= 70 ? 'Knowledge Retention' : 'Basic Concepts');
    }
    
    $performance_stats = [
        'total_tests' => $total_tests,
        'total_correct' => $total_correct,
        'total_questions' => $total_questions,
        'total_score' => $total_score,
        'average_score' => $average_score,
        'success_rate' => $success_rate,
        'best_score' => $best_score,
        'weakest_area' => $performance_stats['weakest_area'],
        'strength_area' => $performance_stats['strength_area'],
        'progress_trend' => $performance_stats['progress_trend']
    ];

} catch (Exception $e) {
    error_log("Error fetching exam results: " . $e->getMessage());
    $recent_results = [];
}

$study_schedules = [];
$upcoming_exams = [];
try {
    $stmt = $pdo->prepare("
        SELECT subject_name, exam_date, study_hours, schedule_data, created_at 
        FROM study_schedules 
        WHERE user_id = ? 
        ORDER BY exam_date ASC
    ");
    $stmt->execute([$user_id]);
    $study_schedules = $stmt->fetchAll();
    
    $today = new DateTime();
    foreach ($study_schedules as $schedule) {
        $exam_date = new DateTime($schedule['exam_date']);
        if ($exam_date >= $today) {
            $upcoming_exams[] = $schedule;
        }
    }
} catch (Exception $e) {
}

$learning_style = [];
try {
    $stmt = $pdo->prepare("
        SELECT question_number, answer_value, answer_text 
        FROM study_behavior_answers 
        WHERE user_id = ? 
        ORDER BY question_number
    ");
    $stmt->execute([$user_id]);
    $learning_style_data = $stmt->fetchAll();
    
    if (!empty($learning_style_data)) {
        $learning_style = [
            'has_data' => true,
            'answers' => $learning_style_data
        ];
    }
} catch (Exception $e) {
}

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

function getPerformanceBadge($score) {
    if ($score >= 90) return 'success';
    if ($score >= 80) return 'primary';
    if ($score >= 70) return 'warning';
    return 'danger';
}

function getTrendIcon($trend) {
    switch ($trend) {
        case 'improving': return 'fa-arrow-up';
        case 'declining': return 'fa-arrow-down';
        default: return 'fa-minus';
    }
}

function getTrendColor($trend) {
    switch ($trend) {
        case 'improving': return 'success';
        case 'declining': return 'danger';
        default: return 'secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - IGM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <link rel="stylesheet" href="style.css">
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
            <li class="nav-item active">
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
                <a href="study_materials.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Study Materials</span>
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
                    <span>Contact us</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content" id="mainContent">
        <div class="dashboard-header">
            <div>
                <h1 id="welcomeMessage">Welcome back, <?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?>! 👋</h1>
                <p>Track your learning progress and achieve your goals</p>
            </div>
            <div class="user-actions">
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="card-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $performance_stats['total_tests']; ?></h3>
                    <p>Tests Completed</p>
                </div>
            </div>

            <div class="stat-card success">
                <div class="card-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $performance_stats['success_rate']; ?>%</h3>
                    <p>Success Rate</p>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="card-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $performance_stats['best_score']; ?>%</h3>
                    <p>Best Score</p>
                </div>
            </div>

            <div class="stat-card info">
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $performance_stats['average_score']; ?>%</h3>
                    <p>Average Score</p>
                </div>
            </div>
        </div>
        
        <!-- Learning Style Overview -->
        <?php if (!empty($learning_style) && $learning_style['has_data']): ?>
        <div class="learning-style-card">
            <h3><i class="fas fa-user-graduate"></i> Your Learning Profile</h3>
            <div class="style-item">
                <strong>Learning Type:</strong> 
                <?php 
                $learning_type = 'Visual Learner';
                foreach ($learning_style['answers'] as $answer) {
                    if ($answer['question_number'] == 5) {
                        if (strpos($answer['answer_value'], 'remember_location') !== false) {
                            $learning_type = 'Visual Learner';
                        } elseif (strpos($answer['answer_value'], 'recall_voice') !== false) {
                            $learning_type = 'Auditory Learner';
                        } else {
                            $learning_type = 'Kinesthetic Learner';
                        }
                    }
                }
                echo $learning_type;
                ?>
            </div>
            <div class="style-item">
                <strong>Study Approach:</strong> 
                <?php 
                $study_approach = 'Structured';
                foreach ($learning_style['answers'] as $answer) {
                    if ($answer['question_number'] == 1) {
                        if (strpos($answer['answer_value'], 'precise_timetable') !== false) {
                            $study_approach = 'Structured Planner';
                        } elseif (strpos($answer['answer_value'], 'based_on_feeling') !== false) {
                            $study_approach = 'Flexible Learner';
                        } else {
                            $study_approach = 'Collaborative Learner';
                        }
                    }
                }
                echo $study_approach;
                ?>
            </div>
            <div class="style-item">
                <strong>Problem Solving:</strong> 
                <?php 
                $problem_solving = 'Analytical';
                foreach ($learning_style['answers'] as $answer) {
                    if ($answer['question_number'] == 2) {
                        if (strpos($answer['answer_value'], 'break_into_parts') !== false) {
                            $problem_solving = 'Analytical';
                        } elseif (strpos($answer['answer_value'], 'additional_resources') !== false) {
                            $problem_solving = 'Research-oriented';
                        } else {
                            $problem_solving = 'Collaborative';
                        }
                    }
                }
                echo $problem_solving;
                ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="insights-section">
            <div class="insight-card">
                <div class="insight-header">
                    <h3><i class="fas fa-chart-bar"></i> Performance Trend</h3>
                    <span class="trend-badge <?php echo $performance_stats['progress_trend']; ?>">
                        <i class="fas <?php echo getTrendIcon($performance_stats['progress_trend']); ?>"></i>
                        <?php echo ucfirst($performance_stats['progress_trend']); ?>
                    </span>
                </div>
                <p>Your recent performance is <?php echo $performance_stats['progress_trend']; ?></p>
            </div>

            <div class="insight-card">
                <div class="insight-header">
                    <h3><i class="fas fa-lightbulb"></i> Correct Answers</h3>
                </div>
                <p><?php echo $performance_stats['total_correct']; ?> out of <?php echo $performance_stats['total_questions']; ?> questions</p>
            </div>
        </div>

        <!-- Calendar Section -->
        <div class="content-row">
            <div class="chart-container">
                <h2 class="section-title">Your Progress This Week <a href="#">View Details</a></h2>
                <?php if (!empty($recent_results)): ?>
                    <div style="padding: 20px;">
                        <div style="display: flex; align-items: end; height: 200px; gap: 10px; margin-bottom: 20px;">
                            <?php 
                            $recent_scores = array_slice(array_column($recent_results, 'score'), 0, 5);
                            $max_score = max($recent_scores) ?: 100;
                            foreach ($recent_scores as $score): 
                                $height = ($score / $max_score) * 150;
                            ?>
                            <div style="display: flex; flex-direction: column; align-items: center; flex: 1;">
                                <div style="width: 30px; background: linear-gradient(to top, var(--primary), var(--secondary)); height: <?php echo $height; ?>px; border-radius: 5px 5px 0 0;"></div>
                                <div style="margin-top: 5px; font-size: 12px;"><?php echo $score; ?>%</div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <p style="text-align: center; color: #666;">Recent test scores showing your performance trend</p>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-chart-bar"></i>
                        <p>No learning data available yet</p>
                        <p>Start learning to see your progress</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="calendar-container">
                <h2 class="section-title">Study Calendar <a href="#">View All</a></h2>
                <div class="calendar-header">
                    <h3 id="currentMonth"><?php echo date('F Y'); ?></h3>
                    <div class="calendar-nav">
                        <button id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                        <button id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="calendar-weekdays">
                    <div>Sun</div>
                    <div>Mon</div>
                    <div>Tue</div>
                    <div>Wed</div>
                    <div>Thu</div>
                    <div>Fri</div>
                    <div>Sat</div>
                </div>
                <div class="calendar-days" id="calendarDays">
                    <!-- Will be filled by JavaScript -->
                </div>
                
                <?php if (empty($upcoming_exams)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>No study schedules yet</p>
                        <p>Create your first schedule to get started</p>
                        <a href="timing_schedule.php" style="display: inline-block; margin-top: 15px; padding: 10px 20px; background: #1e5596; color: white; text-decoration: none; border-radius: 5px;">
                            <i class="fas fa-plus"></i> Create Schedule
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Exams Alert -->
        <?php if (!empty($upcoming_exams)): ?>
        <div class="chart-container exam-warning">
            <h2 class="section-title">
                <i class="fas fa-exclamation-triangle"></i> Upcoming Exams - Important!
                <button type="button" class="dismiss-btn" title="Dismiss alert">
                    <i class="fas fa-times"></i>
                </button>
            </h2>
            <div style="background: #fff5f5; padding: 15px; border-radius: 8px;">
                <?php foreach ($upcoming_exams as $exam): 
                    $exam_date = new DateTime($exam['exam_date']);
                    $today = new DateTime();
                    $days_left = $today->diff($exam_date)->days;
                ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #ffe0e0;">
                    <div>
                        <h4 style="margin: 0; color: #c0392b;">
                            <i class="fas fa-book"></i>
                            <?php echo htmlspecialchars($exam['subject_name']); ?>
                        </h4>
                        <p style="margin: 5px 0 0 0; color: #7f8c8d;">
                            <i class="fas fa-calendar"></i>
                            Exam Date: <?php echo $exam['exam_date']; ?>
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 18px; font-weight: bold; color: #e74c3c;">
                            <?php echo $days_left; ?> days left
                        </span>
                        <br>
                        <span style="color: #7f8c8d; font-size: 12px;">
                            <?php echo $exam['study_hours']; ?>h study/day
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="chart-container">
            <h2 class="section-title">Latest Exam Results <a href="#">View All</a></h2>
            <?php if (!empty($recent_results)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Exam Title</th>
                            <th>Date</th>
                            <th>Score</th>
                            <th>Correct/Total</th>
                            <th>Performance</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_results as $result): 
                            $result_success_rate = $result['total_questions'] > 0 ? 
                                round(($result['correct_answers'] / $result['total_questions']) * 100, 1) : 0;
                            $time_minutes = floor($result['time_spent'] / 60);
                            $time_seconds = $result['time_spent'] % 60;
                            $performance_badge = getPerformanceBadge($result['score']);
                        ?>
                        <tr>
                            <td style="font-weight: bold; color: #1e5596;">
                                <i class="fas fa-file-alt"></i>
                                <?php echo htmlspecialchars($result['file_name'] ?? 'Untitled Exam'); ?>
                            </td>
                            <td>
                                <i class="fas fa-calendar"></i>
                                <?php echo date('M j, Y', strtotime($result['created_at'])); ?>
                            </td>
                            <td>
                                <span class="performance-badge badge-<?php echo $performance_badge; ?>" style="font-size: 12px;">
                                    <?php echo $result['score']; ?>%
                                </span>
                            </td>
                            <td>
                                <span style="color: #27ae60; font-weight: bold;">
                                    <?php echo $result['correct_answers']; ?>
                                </span>
                                /
                                <span style="color: #e74c3c;">
                                    <?php echo $result['wrong_answers']; ?>
                                </span>
                                (<?php echo $result['total_questions']; ?>)
                            </td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-value progress-<?php echo $performance_badge; ?>" 
                                         style="width: <?php echo $result['score']; ?>%;">
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="exam_results.php?session_id=<?php echo $result['id']; ?>" style="color: #1e5596; text-decoration: none;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-file-signature"></i>
                    <p>No exam results found</p>
                    <p>Complete your first exam to see results here</p>
                    <a href="upload_file.php" style="display: inline-block; margin-top: 15px; padding: 10px 20px; background: #1e5596; color: white; text-decoration: none; border-radius: 5px;">
                        <i class="fas fa-upload"></i> Start Learning
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Additional Performance Insights -->
        <?php if ($performance_stats['total_tests'] > 0): ?>
        <div class="content-row">
            <div class="chart-container">
                <h2 class="section-title">Performance Insights</h2>
                <div style="padding: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <h4 style="color: var(--primary); margin-bottom: 15px;">
                                <i class="fas fa-tachometer-alt"></i> Performance Level
                            </h4>
                            <?php if ($performance_stats['average_score'] >= 90): ?>
                                <p>Excellent! You're performing at an expert level.</p>
                            <?php elseif ($performance_stats['average_score'] >= 80): ?>
                                <p>Great job! You have a strong understanding of the material.</p>
                            <?php elseif ($performance_stats['average_score'] >= 70): ?>
                                <p>Good progress! You're building a solid foundation.</p>
                            <?php elseif ($performance_stats['average_score'] >= 60): ?>
                                <p>Keep working! You're on the right track.</p>
                            <?php else: ?>
                                <p>Focus on fundamentals and practice regularly.</p>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <h4 style="color: var(--info); margin-bottom: 15px;">
                                <i class="fas fa-lightbulb"></i> Recommendations
                            </h4>
                            <ul style="padding-left: 20px; color: #666;">
                                <?php if ($performance_stats['success_rate'] < 70): ?>
                                    <li>Focus on improving accuracy in your answers</li>
                                <?php endif; ?>
                                <?php if ($performance_stats['total_tests'] < 3): ?>
                                    <li>Take more practice tests to build confidence</li>
                                <?php endif; ?>
                                <li>Review incorrect answers to learn from mistakes</li>
                                <li>Use the AI tutor for difficult concepts</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="calendar-container">
                <h2 class="section-title">Quick Actions</h2>
                <div style="padding: 15px;">
                    <a href="upload_file.php" style="display: block; padding: 12px; background: var(--primary); color: white; text-decoration: none; border-radius: 5px; margin-bottom: 10px; text-align: center;">
                        <i class="fas fa-upload"></i> Upload New Material
                    </a>
                    <a href="start_exam.php" style="display: block; padding: 12px; background: var(--success); color: white; text-decoration: none; border-radius: 5px; margin-bottom: 10px; text-align: center;">
                        <i class="fas fa-play-circle"></i> Take Practice Test
                    </a>
                    <a href="questionlevel.php" style="display: block; padding: 12px; background: var(--info); color: white; text-decoration: none; border-radius: 5px; text-align: center;">
                        <i class="fas fa-book"></i> Study Materials
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mainContent = document.getElementById('mainContent');
        const userInfo = document.getElementById('userInfo');
        const userDropdown = document.getElementById('userDropdown');
        const logoutBtn = document.getElementById('logoutBtn');
        
        const userName = "<?php echo htmlspecialchars($user['full_name']); ?>";
        document.getElementById("welcomeMessage").textContent = `Welcome, ${userName.split(' ')[0]}!`;
        document.getElementById("userDisplayName").textContent = userName;

        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            mainContent.classList.toggle('sidebar-open');
            sidebarOverlay.classList.toggle('show');
        });
        
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            mainContent.classList.remove('sidebar-open');
            sidebarOverlay.classList.remove('show');
        });
        
        userInfo.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });
        
        document.addEventListener('click', function() {
            userDropdown.classList.remove('show');
        });
        
        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if(confirm('Are you sure you want to logout?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const logoutInput = document.createElement('input');
                logoutInput.type = 'hidden';
                logoutInput.name = 'logout';
                logoutInput.value = '1';
                
                form.appendChild(logoutInput);
                document.body.appendChild(form);
                form.submit();
            }
        });

        const dismissBtn = document.querySelector('.dismiss-btn');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', function() {
                const examAlert = document.querySelector('.exam-warning');
                if (examAlert) {
                    examAlert.style.display = 'none';
                }
            });
        }

        let currentDate = moment();
        
        function renderCalendar() {
            const calendarDays = document.getElementById('calendarDays');
            const currentMonthElement = document.getElementById('currentMonth');
            
            const month = currentDate.format('MMMM');
            const year = currentDate.format('YYYY');
            currentMonthElement.textContent = `${month} ${year}`;
            
            const startOfMonth = currentDate.clone().startOf('month');
            const endOfMonth = currentDate.clone().endOf('month');
            
            const startOfCalendar = startOfMonth.clone().startOf('week');
            const endOfCalendar = endOfMonth.clone().endOf('week');
            
            calendarDays.innerHTML = '';
            
            let day = startOfCalendar.clone();
            while (day.isBefore(endOfCalendar, 'day')) {
                const dayElement = document.createElement('div');
                
                if (day.isBefore(startOfMonth, 'day') || day.isAfter(endOfMonth, 'day')) {
                    dayElement.classList.add('other-month');
                } else if (day.isSame(moment(), 'day')) {
                    dayElement.classList.add('today');
                }
                
                const upcomingExams = <?php echo json_encode($upcoming_exams); ?>;
                upcomingExams.forEach(exam => {
                    const examDate = moment(exam.exam_date);
                    if (day.isSame(examDate, 'day')) {
                        dayElement.classList.add('event');
                        dayElement.style.backgroundColor = '#fff5f5';
                        dayElement.style.border = '1px solid #e74c3c';
                        dayElement.title = `Exam: ${exam.subject_name}`;
                    }
                });
                
                dayElement.textContent = day.format('D');
                calendarDays.appendChild(dayElement);
                
                day.add(1, 'day');
            }
        }
        
        document.getElementById('prevMonth').addEventListener('click', function() {
            currentDate.subtract(1, 'month');
            renderCalendar();
        });
        
        document.getElementById('nextMonth').addEventListener('click', function() {
            currentDate.add(1, 'month');
            renderCalendar();
        });
        
        renderCalendar();
    </script>
</body>
</html>