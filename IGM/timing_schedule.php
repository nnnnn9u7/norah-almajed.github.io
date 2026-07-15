<?php
session_start();
require_once 'db_config.php';
require_once 'ai_handler.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = null;
$schedules = [];
$success_message = '';
$error_message = '';
$last_study_file = null;

try {
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM study_schedules WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $raw_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($raw_schedules as $schedule) {
        if (isset($schedule['schedule_data'])) {
            $schedule['schedule_data'] = json_decode($schedule['schedule_data'], true);
        } else {
            $schedule['schedule_data'] = [];
        }
        
        if (isset($schedule['learning_style'])) {
            $schedule['learning_style'] = json_decode($schedule['learning_style'], true);
        } else {
            $schedule['learning_style'] = [];
        }
        
        $stmt = $pdo->prepare("SELECT study_date, pages_studied FROM study_logs WHERE user_id = ? AND schedule_id = ?");
        $stmt->execute([$user_id, $schedule['id']]);
        $schedule['study_logs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $schedules[] = $schedule;
    }
    
    $stmt = $pdo->prepare("SELECT id, file_name, created_at FROM ai_generated_questions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $last_study_file = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error_message = "Error loading data: " . $e->getMessage();
}

if (!function_exists('getEnglishStyleName')) {
    function getEnglishStyleName($style) {
        $styles = [
            'Organized' => 'Organized',
            'Flexible' => 'Flexible',
            'Visual' => 'Visual',
            'Auditory' => 'Auditory',
            'Reading' => 'Reading/Writing',
            'Kinesthetic' => 'Kinesthetic'
        ];
        return $styles[$style] ?? $style;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_schedule'])) {
        $subject_name = $_POST['subject_name'] ?? '';
        $total_pages = $_POST['total_pages'] ?? 0;
        $exam_date = $_POST['exam_date'] ?? '';

        if (empty($subject_name) || $total_pages <= 0 || empty($exam_date)) {
            $error_message = "Please fill all required fields";
        } else {
            $schedule_data = generateSmartSchedule($user_id, $subject_name, $total_pages, $exam_date);
            
            if ($schedule_data['success']) {
                $success_message = "✅ Smart study schedule created successfully!";
                header("Location: timing_schedule.php");
                exit();
            } else {
                $error_message = "⚠️ " . $schedule_data['error'];
            }
        }
    }
    
    if (isset($_POST['mark_studied'])) {
        $schedule_id = $_POST['schedule_id'];
        $study_date = $_POST['study_date'];
        
        try {
            $stmt = $pdo->prepare("SELECT id FROM study_logs WHERE user_id = ? AND schedule_id = ? AND study_date = ?");
            $stmt->execute([$user_id, $schedule_id, $study_date]);
            $existing = $stmt->fetch();
            
            if (!$existing) {
                $daily_pages = 0;
                $daily_minutes_tracked = 0; 
                
                foreach ($schedules as $schedule) {
                    if ($schedule['id'] == $schedule_id && isset($schedule['schedule_data']['daily_plan'])) {
                        foreach ($schedule['schedule_data']['daily_plan'] as $day) {
                            if ($day['date'] == $study_date) {
                                $daily_pages = $day['pages'];
                                
                                foreach ($day['time_slots'] as $slot) {
                                    if (strpos($slot['activity'], 'Focused Study Session') !== false) {
                                        $daily_minutes_tracked += 60;
                                    } elseif (strpos($slot['activity'], 'Review on Site') !== false) {
                                        $daily_minutes_tracked += 15;
                                    }
                                }
                                break;
                            }
                        }
                    }
                }
                
                $study_minutes = max(90, $daily_minutes_tracked);
                
                $stmt = $pdo->prepare("
                    INSERT INTO study_logs (user_id, schedule_id, study_date, pages_studied, study_minutes) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$user_id, $schedule_id, $study_date, $daily_pages, $study_minutes]);
                
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, title, message, type) 
                    VALUES (?, 'Study Progress', ?, 'success')
                ");
                $message = "Great job! You've completed your study session for " . date('F j, Y', strtotime($study_date));
                $stmt->execute([$user_id, $message]);
                
                $success_message = "Study session marked as completed!";
                
                header("Location: timing_schedule.php");
                exit();
            } else {
                $error_message = "This day is already marked as studied.";
            }
            
        } catch (Exception $e) {
            $error_message = "Error marking study day: " . $e->getMessage();
        }
    }
}

if (isset($_GET['delete_schedule'])) {
    $schedule_id = $_GET['delete_schedule'];
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("DELETE FROM study_logs WHERE schedule_id = ? AND user_id = ?");
        $stmt->execute([$schedule_id, $user_id]);
        
        $stmt = $pdo->prepare("DELETE FROM study_schedules WHERE id = ? AND user_id = ?");
        $stmt->execute([$schedule_id, $user_id]);
        
        $pdo->commit();
        $success_message = "Schedule deleted successfully!";
        
        header("Location: timing_schedule.php");
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Error deleting schedule: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Schedules - IGM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <span id="userDisplayName"><?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?></span>
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
                <a href="study_materials.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Study Materials</span>
                </a>
            </li>
            <li class="nav-item active">
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
        <div class="container">
            <div class="page-header">
                <div>
                    <h1 class="page-title"><i class="fas fa-calendar-check"></i> Study Schedules</h1>
                    <p class="page-subtitle">Create and manage your personalized study plans with AI-powered scheduling</p>
                </div>
            </div>

            <?php if ($last_study_file): ?>
            <div class="review-note">
                <div class="review-note-content">
                    <i class="fas fa-lightbulb"></i>
                    <div>
                        <strong> Review Tip:</strong> You last studied 
                        <strong><?php echo htmlspecialchars($last_study_file['file_name']); ?></strong> on 
                        <strong><?php echo date('M j, Y', strtotime($last_study_file['created_at'])); ?></strong>.
                        Ready to reinforce your learning?
                    </div>
                </div>
                <a href="start_exam.php?id=<?php echo $last_study_file['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-play-circle"></i> Review Now
                </a>
            </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($schedules); ?></h3>
                        <p>Active Schedules</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="stat-info">
                        <h3>
                            <?php 
                            $totalPages = 0;
                            foreach ($schedules as $schedule) {
                                $schedule_data = $schedule['schedule_data'] ?? [];
                                $totalPages += $schedule_data['total_pages'] ?? 0;
                            }
                            echo $totalPages;
                            ?>
                        </h3>
                        <p>Total Pages to Study</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>
                            <?php 
                            $totalHours = 0;
                            foreach ($schedules as $schedule) {
                                $schedule_data = $schedule['schedule_data'] ?? [];
                                $days = $schedule_data['days_remaining'] ?? 0;
                                $dailyHours = $schedule['study_hours'] ?? $schedule_data['daily_hours'] ?? 0; 
                                $totalHours += $days * $dailyHours;
                            }
                            echo $totalHours;
                            ?>
                        </h3>
                        <p>Study Hours Planned</p>
                    </div>
                </div>
            </div>
            
            <?php if (count($schedules) > 0): ?>
            <div class="warning-note">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Important Note:</strong> This system allows multiple schedules, but it does not check for time conflicts between them. Please ensure you manually check for overlaps between different subjects.
            </div>
            <?php endif; ?>

            <div class="main-layout">
                <div class="timing-form-container">
                    <div class="timing-form-header">
                        <h2>
                            <i class="fas fa-wand-magic-sparkles"></i>
                            Create Smart Schedule
                        </h2>
                        <p style="margin: 8px 0 0 0; font-size: 13px; opacity: 0.9;">AI-powered study planning</p>
                    </div>
                    <div class="timing-form-body">
                        <form method="POST">
                            <div class="form-group">
                                <label for="subject_name" class="form-label">
                                    <i class="fas fa-book-open"></i> Subject Name
                                </label>
                                <input type="text" id="subject_name" name="subject_name" class="form-control" 
                                       placeholder="e.g., Mathematics, Physics, History..." required>
                            </div>

                            <div class="form-group">
                                <label for="total_pages" class="form-label">
                                    <i class="fas fa-file-alt"></i> Number of Pages/Chapters
                                </label>
                                <input type="number" id="total_pages" name="total_pages" class="form-control" 
                                       min="1" placeholder="Total number of pages..." required>
                            </div>

                            <div class="form-group">
                                <label for="exam_date" class="form-label">
                                    <i class="fas fa-calendar-day"></i> Exam Date
                                </label>
                                <input type="date" id="exam_date" name="exam_date" class="form-control" required>
                            </div>

                            <div class="ai-info-box">
                                <h4>
                                    <i class="fas fa-robot"></i> Smart Study Hours
                                </h4>
                                <p style="margin-bottom: 8px;">The AI will automatically calculate optimal daily study hours based on:</p>
                                <ul>
                                    <li><i class="fas fa-check"></i> Number of pages to cover</li>
                                    <li><i class="fas fa-check"></i> Days remaining until exam</li>
                                    <li><i class="fas fa-check"></i> Your learning style and pace</li>
                                    <li><i class="fas fa-check"></i> Schedule sessions: 60 min study, 15 min break, 15 min review.</li>
                                    <li><i class="fas fa-check"></i> Minimum 10 pages per study day</li>
                                    <li><i class="fas fa-check"></i> Review day before exam (no new pages)</li>
                                </ul>
                            </div>

                            <button type="submit" name="generate_schedule" class="submit-btnTime">
                                <i class="fas fa-robot"></i>
                                Generate Smart Schedule
                            </button>
                        </form>
                    </div>
                </div>

                <div class="schedules-container">
                    <div class="schedules-header">
                        <h2>
                            <i class="fas fa-list-check"></i>
                            Your Schedules
                        </h2>
                        <span class="schedules-count">
                            <i class="fas fa-bookmark"></i> <?php echo count($schedules); ?> Active
                        </span>
                    </div>
                    <div class="schedules-body">
                        <?php if (empty($schedules)): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h3>No Schedules Yet</h3>
                                <p>Create your first study schedule to get started with AI-powered learning</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($schedules as $schedule): 
                                $schedule_data = $schedule['schedule_data'] ?? [];
                                $learning_style = $schedule['learning_style'] ?? [];
                            ?>
                            <div class="schedule-card" id="schedule-<?php echo $schedule['id']; ?>">
                                <div class="schedule-card-header">
                                    <div class="schedule-title-section">
                                        <h3 class="schedule-title">
                                            <i class="fas fa-book-open"></i> 
                                            <?php echo htmlspecialchars($schedule['subject_name']); ?>
                                        </h3>
                                        <div class="schedule-meta">
                                            <span class="meta-badge planning">
                                                <i class="fas fa-user-cog"></i>
                                                <?php echo getEnglishStyleName($learning_style['planning_style'] ?? 'Organized'); ?>
                                            </span>
                                            <span class="meta-badge learning">
                                                <i class="fas fa-graduation-cap"></i>
                                                <?php echo getEnglishStyleName($learning_style['learning_type'] ?? 'Visual'); ?> Learner
                                            </span>
                                            <span class="meta-badge">
                                                <i class="fas fa-calendar"></i>
                                                <?php echo date('M j, Y', strtotime($schedule['exam_date'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="schedule-actions">
                                        <a href="?delete_schedule=<?php echo $schedule['id']; ?>" class="delete-btn" 
                                           onclick="return confirm('Are you sure you want to delete this schedule? All study progress will be lost.');" title="Delete schedule">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="schedule-card-body">
                                    <div class="progress-section">
                                        <div class="progress-header">
                                            <span><i class="fas fa-chart-line"></i> Study Progress</span>
                                            <span class="progress-percent">
                                                <?php
                                                $total_days = $schedule_data['days_remaining'] ?? 0;
                                                $studied_days = count($schedule['study_logs'] ?? []);
                                                $progress = $total_days > 0 ? ($studied_days / $total_days) * 100 : 0;
                                                echo round($progress) . '%';
                                                ?>
                                            </span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="schedule-details">
                                        <div class="detail-item">
                                            <div class="detail-value"><?php echo $schedule_data['total_pages'] ?? 0; ?></div>
                                            <div class="detail-label">Total Pages</div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-value"><?php echo $schedule_data['days_remaining'] ?? 0; ?></div>
                                            <div class="detail-label">Days Remaining</div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-value"><?php echo $schedule['study_hours'] ?? $schedule_data['daily_hours'] ?? 3; ?></div>
                                            <div class="detail-label">Hours/Day</div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-value"><?php echo count($schedule['study_logs'] ?? []); ?></div>
                                            <div class="detail-label">Days Studied</div>
                                        </div>
                                    </div>
                                    
                                    <div class="daily-plan-preview">
                                        <h4>
                                            <i class="fas fa-list-ol"></i>
                                            Upcoming Study Days
                                        </h4>
                                        <div class="day-cards">
                                            <?php 
                                            $daily_plan_preview = $schedule_data['daily_plan'] ?? [];
                                            $counter = 0;
                                            foreach ($daily_plan_preview as $index => $day): 
                                                
                                                $is_studied = false;
                                                foreach ($schedule['study_logs'] as $log) {
                                                    if ($log['study_date'] == $day['date']) {
                                                        $is_studied = true;
                                                        break;
                                                    }
                                                }
                                                
                                                if ($is_studied && $index > 0) continue; 
                                                
                                                $counter++;
                                                $is_review_day = ($day['pages'] == 0);
                                            ?>
                                            <div class="day-card <?php echo $is_review_day ? 'review-day' : ''; ?>">
                                                <div class="day-header">
                                                    <span class="day-name"><?php echo date('D, M j', strtotime($day['date'])); ?></span>
                                                    <span class="day-pages <?php echo $is_review_day ? 'review' : ''; ?>">
                                                        <?php echo $is_review_day ? 'Review' : $day['pages'] . ' pgs'; ?>
                                                    </span>
                                                </div>
                                                <div class="study-status <?php echo $is_studied ? 'studied' : 'not-studied'; ?>">
                                                    <?php echo $is_studied ? 
                                                        '<i class="fas fa-check-circle"></i> Completed' : 
                                                        '<i class="fas fa-clock"></i> Pending'; ?>
                                                </div>
                                                
                                                <?php if ($is_review_day): ?>
                                                    <div class="review-indicator">
                                                        <i class="fas fa-sync-alt"></i> Review Day - No New Pages
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($day['time_slots'])): ?>
                                                    <ul class="time-slots">
                                                        <?php 
                                                        foreach ($day['time_slots'] as $slot): 
                                                            $slot_class = '';
                                                            if (strpos($slot['activity'], 'Break') !== false) {
                                                                $slot_class = 'break';
                                                            } elseif (strpos($slot['activity'], 'Review') !== false || $is_review_day) {
                                                                $slot_class = 'review';
                                                            }
                                                        ?>
                                                        <li class="time-slot <?php echo $slot_class; ?>">
                                                            <?php echo $slot['time']; ?> - 
                                                            <?php echo htmlspecialchars($slot['activity']); ?>
                                                        </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>
                                                
                                                <form method='POST' style='margin-top: 12px;'>
                                                    <input type='hidden' name='schedule_id' value='<?php echo $schedule['id']; ?>'>
                                                    <input type='hidden' name='study_date' value='<?php echo $day['date']; ?>'>
                                                    <button type='submit' name='mark_studied' 
                                                            class='study-btn<?php echo $is_studied ? ' studied' : ($is_review_day ? ' review-day' : ''); ?>'
                                                            <?php echo $is_studied ? 'disabled' : ''; ?>>
                                                        <?php echo $is_studied ? 'Completed' : ($is_review_day ? 'Mark Review Complete' : 'Mark as Studied'); ?>
                                                    </button>
                                                </form>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mainContent = document.getElementById('mainContent');
        const userInfo = document.getElementById('userInfo');
        const userDropdown = document.getElementById('userDropdown');
        const logoutBtn = document.getElementById('logoutBtn');

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
                window.location.href = 'logout.php';
            }
        });

        const examDateInput = document.getElementById('exam_date');
        if (examDateInput) {
             examDateInput.min = new Date().toISOString().split('T')[0];
        }
    </script>
</body>
</html>