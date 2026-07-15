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
$exam_data = $_SESSION['exam_data'] ?? null;
$error_message = '';

try {
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (Exception $e) {
    $error_message = "System error: " . $e->getMessage();
}

// Check for required exam data
if (!$exam_data || empty($exam_data['questions'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM ai_generated_questions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $material = $stmt->fetch();
        
        if ($material) {
            $all_questions = json_decode($material['questions'], true);
            
            $easy_count = 0;
            $medium_count = 0;
            $hard_count = 0;
            foreach ($all_questions as $q) {
                $difficulty = strtolower($q['difficulty'] ?? 'medium');
                if ($difficulty === 'easy') $easy_count++;
                if ($difficulty === 'medium') $medium_count++;
                if ($difficulty === 'hard') $hard_count++;
            }
            $total_generated = count($all_questions);
            $exam_time = calculateExamTime($total_generated);
            
            $exam_data = [
                'title' => $material['file_name'],
                'total_time' => $exam_time,
                'total_questions' => $total_generated,
                'questions' => $all_questions,
                'question_set_id' => $material['id'],
                'difficulty_counts' => [
                    'Easy' => $easy_count,
                    'Medium' => $medium_count,
                    'Hard' => $hard_count,
                    'Total' => $total_generated
                ]
            ];
            $_SESSION['exam_data'] = $exam_data;
        } else {
             $error_message = "No exam data found. Please upload a study file first.";
        }
    } catch (Exception $e) {
        $error_message = "Error loading last material: " . $e->getMessage();
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['difficulty']) && $exam_data) {
    $selected_difficulty = trim($_POST['difficulty']);
    
    $filtered_questions = [];
    $valid_difficulties = ['easy', 'medium', 'hard', 'all'];
    $selected_difficulty_lower = strtolower($selected_difficulty);

    if (in_array($selected_difficulty_lower, $valid_difficulties)) {
        
        if ($selected_difficulty_lower === 'all') {
            $filtered_questions = $exam_data['questions'];
        } else {
            foreach ($exam_data['questions'] as $question) {
                if (strtolower($question['difficulty'] ?? 'medium') === $selected_difficulty_lower) {
                    $filtered_questions[] = $question;
                }
            }
        }
        
        if (empty($filtered_questions)) {
            $error_message = "No questions available for the selected difficulty: " . ucfirst($selected_difficulty);
        } else {
            $total_questions = count($filtered_questions);
            $total_time = calculateExamTime($total_questions);

            $_SESSION['exam_data']['questions_to_use'] = $filtered_questions;
            $_SESSION['exam_data']['total_questions_to_use'] = $total_questions;
            $_SESSION['exam_data']['total_time_to_use'] = $total_time;
            $_SESSION['exam_data']['selected_difficulty'] = ucfirst($selected_difficulty);
            
            header("Location: start_exam.php");
            exit();
        }
    } else {
        $error_message = "Invalid difficulty level selected.";
    }
}

// Prepare difficulty counts for display
if ($exam_data && !empty($exam_data['questions'])) {
    $easy_count = 0;
    $medium_count = 0;
    $hard_count = 0;
    
    foreach ($exam_data['questions'] as $question) {
        $difficulty = strtolower($question['difficulty'] ?? 'medium');
        if ($difficulty === 'easy') $easy_count++;
        if ($difficulty === 'medium') $medium_count++;
        if ($difficulty === 'hard') $hard_count++;
    }
    
    $total_generated = count($exam_data['questions']);
    
    $difficulty_counts = [
        'Easy' => $easy_count,
        'Medium' => $medium_count,
        'Hard' => $hard_count,
        'Total' => $total_generated
    ];
} else {
    $difficulty_counts = [
        'Easy' => 0,
        'Medium' => 0,
        'Hard' => 0,
        'Total' => 0
    ];
}

function getDifficultyColor($difficulty) {
    switch(strtolower($difficulty)) {
        case 'easy': return '#28a745';
        case 'medium': return '#ffc107';
        case 'hard': return '#dc3545';
        case 'all': return '#1e5596';
        default: return '#6c757d';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Settings - IGM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary-blue: #1e5596;
            --success-green: #28a745;
            --warning-orange: #ffc107;
            --danger-red: #dc3545;
            --light-blue: #e3f2fd;
            --white: #ffffff;
            --text-dark: #2c3e50;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 30px;
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            text-align: center;
        }

        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }

        .header h1 {
            color: var(--primary-blue);
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            color: var(--text-dark);
            font-size: 1.1rem;
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error {
            background: #f8d7da;
            color: var(--danger-red);
            border: 1px solid #f5c6cb;
        }

        .difficulty-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .difficulty-card {
            background: var(--light-blue);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .difficulty-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .card-easy { border-color: var(--success-green); }
        .card-medium { border-color: var(--warning-orange); }
        .card-hard { border-color: var(--danger-red); }
        .card-all { border-color: var(--primary-blue); }

        .difficulty-card h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .difficulty-card .count {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .difficulty-card p {
            font-size: 0.9rem;
            color: #666;
            min-height: 40px;
        }

        .btn-select {
            background: var(--primary-blue);
            color: var(--white);
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 15px;
            width: 100%;
        }

        .btn-select:hover {
            background: #16417a;
        }
        
        .text-easy { color: var(--success-green); }
        .text-medium { color: var(--warning-orange); }
        .text-hard { color: var(--danger-red); }
        .text-all { color: var(--primary-blue); }

        .distribution-info {
            background: #e8f5e9;
            border: 1px solid #4caf50;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
        }

        .distribution-info h4 {
            color: #2e7d32;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .distribution-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .stat-item {
            text-align: center;
            padding: 8px;
            border-radius: 6px;
            background: white;
        }

        .stat-item.easy { border-left: 4px solid var(--success-green); }
        .stat-item.medium { border-left: 4px solid var(--warning-orange); }
        .stat-item.hard { border-left: 4px solid var(--danger-red); }
        .stat-item.total { border-left: 4px solid var(--primary-blue); }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #666;
        }

        @media (max-width: 600px) {
            .difficulty-grid {
                grid-template-columns: 1fr;
            }
            
            .distribution-stats {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-cogs"></i> Exam Settings</h1>
            <?php if ($exam_data): ?>
                <p>Generated Exam: <strong><?php echo htmlspecialchars($exam_data['title']); ?></strong></p>
                <p>Total Questions Generated: <strong><?php echo $difficulty_counts['Total']; ?></strong></p>
                <p style="margin-top: 15px;">Please select the level of difficulty you wish to be tested on.</p>
            <?php else: ?>
                <p>No exam data loaded. Please upload a file via the Start Learning page.</p>
            <?php endif; ?>
        </div>

        <?php if ($error_message): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($exam_data): ?>
            <div class="distribution-info">
                <h4>
                    <i class="fas fa-chart-pie"></i>
                    Question Distribution
                </h4>
                <p>Your study material has been analyzed and questions have been generated with balanced difficulty levels.</p>
                
                <div class="distribution-stats">
                    <div class="stat-item easy">
                        <div class="stat-number text-easy"><?php echo $difficulty_counts['Easy']; ?></div>
                        <div class="stat-label">Easy Questions</div>
                    </div>
                    <div class="stat-item medium">
                        <div class="stat-number text-medium"><?php echo $difficulty_counts['Medium']; ?></div>
                        <div class="stat-label">Medium Questions</div>
                    </div>
                    <div class="stat-item hard">
                        <div class="stat-number text-hard"><?php echo $difficulty_counts['Hard']; ?></div>
                        <div class="stat-label">Hard Questions</div>
                    </div>
                    <div class="stat-item total">
                        <div class="stat-number text-all"><?php echo $difficulty_counts['Total']; ?></div>
                        <div class="stat-label">Total Questions</div>
                    </div>
                </div>
            </div>

            <form method="POST">
                <div class="difficulty-grid">
                    
                    <div class="difficulty-card card-easy">
                        <h3 class="text-easy"><i class="fas fa-leaf"></i> Easy</h3>
                        <div class="count text-easy"><?php echo $difficulty_counts['Easy']; ?></div>
                        <p>Focus on <strong>basic facts</strong> and definitions (Recall questions).</p>
                        <button type="submit" name="difficulty" value="Easy" class="btn-select" style="background: <?php echo getDifficultyColor('easy'); ?>;" <?php echo $difficulty_counts['Easy'] == 0 ? 'disabled' : ''; ?>>
                            <?php echo $difficulty_counts['Easy'] > 0 ? 'Start Easy Test' : 'No Easy Questions'; ?>
                        </button>
                    </div>

                    <div class="difficulty-card card-medium">
                        <h3 class="text-medium"><i class="fas fa-star-half-alt"></i> Medium</h3>
                        <div class="count text-medium"><?php echo $difficulty_counts['Medium']; ?></div>
                        <p>Focus on <strong>applying</strong> concepts and comprehension.</p>
                        <button type="submit" name="difficulty" value="Medium" class="btn-select" style="background: <?php echo getDifficultyColor('medium'); ?>;" <?php echo $difficulty_counts['Medium'] == 0 ? 'disabled' : ''; ?>>
                            <?php echo $difficulty_counts['Medium'] > 0 ? 'Start Medium Test' : 'No Medium Questions'; ?>
                        </button>
                    </div>
                    
                    <div class="difficulty-card card-hard">
                        <h3 class="text-hard"><i class="fas fa-fire-alt"></i> Hard</h3>
                        <div class="count text-hard"><?php echo $difficulty_counts['Hard']; ?></div>
                        <p>Focus on <strong>analysis</strong> and synthesis (Challenging scenarios).</p>
                        <button type="submit" name="difficulty" value="Hard" class="btn-select" style="background: <?php echo getDifficultyColor('hard'); ?>;" <?php echo $difficulty_counts['Hard'] == 0 ? 'disabled' : ''; ?>>
                            <?php echo $difficulty_counts['Hard'] > 0 ? 'Start Hard Test' : 'No Hard Questions'; ?>
                        </button>
                    </div>
                    
                    <div class="difficulty-card card-all">
                        <h3 class="text-all"><i class="fas fa-layer-group"></i> All Levels</h3>
                        <div class="count text-all"><?php echo $difficulty_counts['Total']; ?></div>
                        <p>A complete test covering all <strong>Easy, Medium, and Hard</strong> questions.</p>
                        <button type="submit" name="difficulty" value="All" class="btn-select" <?php echo $difficulty_counts['Total'] == 0 ? 'disabled' : ''; ?>>
                            <?php echo $difficulty_counts['Total'] > 0 ? 'Start Full Test' : 'No Questions Available'; ?>
                        </button>
                    </div>

                </div>
            </form>
        <?php else: ?>
             <a href="upload_file.php" class="btn-select" style="width: auto; padding: 15px 40px; margin-top: 40px;">
                <i class="fas fa-upload"></i> Upload Study Material
            </a>
        <?php endif; ?>
        
        <a href="dashboard.php" style="display: block; margin-top: 30px; color: var(--primary-blue); text-decoration: none;">
            <i class="fas fa-home"></i> Back to Dashboard
        </a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const difficultyCards = document.querySelectorAll('.difficulty-card');
            
            difficultyCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>