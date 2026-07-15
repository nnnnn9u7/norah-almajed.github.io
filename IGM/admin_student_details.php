<?php
session_start();
require_once 'db_config.php';

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get student ID
$student_id = $_GET['id'] ?? 0;

try {
    // Get student details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        header("Location: admin_students.php");
        exit();
    }
    
    // Get student's exam history
    $exams_stmt = $pdo->prepare("
        SELECT es.*, aq.file_name 
        FROM exam_sessions es 
        LEFT JOIN ai_generated_questions aq ON es.question_set_id = aq.id 
        WHERE es.user_id = ? 
        ORDER BY es.created_at DESC
    ");
    $exams_stmt->execute([$student_id]);
    $exams = $exams_stmt->fetchAll();
    
    // Get study schedules
    $schedules_stmt = $pdo->prepare("SELECT * FROM study_schedules WHERE user_id = ? ORDER BY exam_date DESC");
    $schedules_stmt->execute([$student_id]);
    $schedules = $schedules_stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
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
    <title>Student Details - IGM Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1><i class="fas fa-user-graduate"></i> Student Details</h1>
            <a href="admin_students.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Students
            </a>
        </div>
        
        <div style="background: white; border-radius: 10px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 30px;">
            <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
                <div style="width: 80px; height: 80px; background: #1e5596; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 32px; font-weight: bold;">
                    <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                </div>
                <div>
                    <h2 style="margin: 0; color: #333;"><?php echo htmlspecialchars($student['full_name']); ?></h2>
                    <p style="margin: 5px 0; color: #666;">@<?php echo htmlspecialchars($student['username']); ?></p>
                    <p style="margin: 0; color: #888;">Joined: <?php echo date('F j, Y', strtotime($student['created_at'])); ?></p>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div>
                    <h4 style="color: #666; margin-bottom: 5px;">Email</h4>
                    <p style="font-size: 18px; margin: 0;"><?php echo htmlspecialchars($student['email']); ?></p>
                </div>
                <div>
                    <h4 style="color: #666; margin-bottom: 5px;">Phone</h4>
                    <p style="font-size: 18px; margin: 0;"><?php echo htmlspecialchars($student['phone'] ?? 'Not provided'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Exam History -->
        <div style="background: white; border-radius: 10px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 30px;">
            <h3 style="margin-top: 0; margin-bottom: 20px;">
                <i class="fas fa-file-alt"></i> Exam History
            </h3>
            
            <?php if (!empty($exams)): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 15px; text-align: left;">Exam</th>
                            <th style="padding: 15px; text-align: left;">Score</th>
                            <th style="padding: 15px; text-align: left;">Questions</th>
                            <th style="padding: 15px; text-align: left;">Time</th>
                            <th style="padding: 15px; text-align: left;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exams as $exam): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 15px;"><?php echo htmlspecialchars($exam['file_name'] ?? 'Unknown'); ?></td>
                            <td style="padding: 15px;">
                                <span style="padding: 5px 10px; border-radius: 20px; background: <?php echo $exam['score'] >= 60 ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $exam['score'] >= 60 ? '#155724' : '#721c24'; ?>;">
                                    <?php echo $exam['score']; ?>/100
                                </span>
                            </td>
                            <td style="padding: 15px;"><?php echo $exam['total_questions']; ?></td>
                            <td style="padding: 15px;"><?php echo round($exam['time_spent'] / 60, 1); ?>m</td>
                            <td style="padding: 15px;"><?php echo date('M j, Y g:i A', strtotime($exam['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">No exam history available</p>
            <?php endif; ?>
        </div>
        
        <!-- Study Schedules -->
        <div style="background: white; border-radius: 10px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; margin-bottom: 20px;">
                <i class="fas fa-calendar-alt"></i> Study Schedules
            </h3>
            
            <?php if (!empty($schedules)): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 15px; text-align: left;">Subject</th>
                            <th style="padding: 15px; text-align: left;">Pages</th>
                            <th style="padding: 15px; text-align: left;">Exam Date</th>
                            <th style="padding: 15px; text-align: left;">Hours/Day</th>
                            <th style="padding: 15px; text-align: left;">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 15px;"><?php echo htmlspecialchars($schedule['subject_name']); ?></td>
                            <td style="padding: 15px;"><?php echo $schedule['total_pages']; ?></td>
                            <td style="padding: 15px;"><?php echo date('M j, Y', strtotime($schedule['exam_date'])); ?></td>
                            <td style="padding: 15px;"><?php echo $schedule['study_hours']; ?></td>
                            <td style="padding: 15px;"><?php echo date('M j, Y', strtotime($schedule['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">No study schedules created</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>