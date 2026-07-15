<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$session_id = $_GET['session_id'] ?? null;
$exam_details = null;
$questions_review = [];

if ($session_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                es.*,
                agq.questions,
                agq.file_name,
                agq.file_content
            FROM exam_sessions es
            LEFT JOIN ai_generated_questions agq ON es.question_set_id = agq.id
            WHERE es.id = ? AND es.user_id = ?
        ");
        $stmt->execute([$session_id, $user_id]);
        $exam_details = $stmt->fetch();
        
        if ($exam_details) {
            $questions = json_decode($exam_details['questions'], true);
            
            $stmt = $pdo->prepare("
                SELECT * FROM user_answers 
                WHERE session_id = ? 
                ORDER BY question_number
            ");
            $stmt->execute([$session_id]);
            $user_answers = $stmt->fetchAll();
            
            foreach ($user_answers as $answer) {
                $q_index = $answer['question_number'] - 1;
                $question = $questions[$q_index] ?? null;
                
                if ($question) {
                    $questions_review[] = [
                        'question' => $question,
                        'user_answer' => $answer,
                        'number' => $answer['question_number']
                    ];
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error loading exam details: " . $e->getMessage());
    }
}

function getScoreColor($score) {
    if ($score >= 90) return '#28a745';
    if ($score >= 80) return '#17a2b8';
    if ($score >= 70) return '#ffc107';
    if ($score >= 60) return '#fd7e14';
    return '#dc3545';
}

function getPerformanceText($score) {
    if ($score >= 90) return 'Excellent';
    if ($score >= 80) return 'Very Good';
    if ($score >= 70) return 'Good';
    if ($score >= 60) return 'Pass';
    return 'Needs Improvement';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Review - IGM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            text-align: center;
        }

        .score-display {
            width: 200px;
            height: 200px;
            margin: 0 auto 30px;
            border-radius: 50%;
            background: conic-gradient(
                <?php echo getScoreColor($exam_details['score'] ?? 0); ?> 0% <?php echo $exam_details['score'] ?? 0; ?>%,
                #e9ecef <?php echo $exam_details['score'] ?? 0; ?>% 100%
            );
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .score-inner {
            width: 160px;
            height: 160px;
            background: white;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .score-number {
            font-size: 3.5rem;
            font-weight: bold;
            color: <?php echo getScoreColor($exam_details['score'] ?? 0); ?>;
        }

        .score-label {
            font-size: 1rem;
            color: #666;
            margin-top: 5px;
        }

        .performance-badge {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 30px;
            font-size: 1.2rem;
            font-weight: bold;
            color: white;
            background: <?php echo getScoreColor($exam_details['score'] ?? 0); ?>;
            margin-bottom: 20px;
        }

        .exam-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .info-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .info-value {
            font-size: 2rem;
            font-weight: bold;
            color: #1e5596;
            margin-bottom: 5px;
        }

        .info-label {
            color: #666;
            font-size: 0.9rem;
        }

        .questions-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.8rem;
            color: #1e5596;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .question-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .question-card.correct {
            border-left: 5px solid #28a745;
            background: #f0fff4;
        }

        .question-card.wrong {
            border-left: 5px solid #dc3545;
            background: #fff5f5;
        }

        .question-card.skipped {
            border-left: 5px solid #ffc107;
            background: #fffbf0;
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .question-number {
            font-size: 1.1rem;
            font-weight: bold;
            color: #1e5596;
        }

        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
        }

        .status-badge.correct { background: #28a745; }
        .status-badge.wrong { background: #dc3545; }
        .status-badge.skipped { background: #ffc107; }

        .question-text {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .answers-comparison {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 15px;
        }

        .answer-box {
            padding: 15px;
            border-radius: 10px;
            border: 2px solid;
        }

        .answer-box.user-answer {
            border-color: #3498db;
            background: #e3f2fd;
        }

        .answer-box.user-answer.wrong {
            border-color: #dc3545;
            background: #ffe6e6;
        }

        .answer-box.user-answer.correct {
            border-color: #28a745;
            background: #e6ffe6;
        }

        .answer-box.correct-answer {
            border-color: #28a745;
            background: #f0fff4;
        }

        .answer-label {
            font-weight: bold;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .answer-text {
            color: #2c3e50;
            line-height: 1.5;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 15px 35px;
            border: none;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            color: white;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1e5596, #2980b9);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .btn-warning {
            background: linear-gradient(135deg, #fd7e14, #ffc107);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }

        @media (max-width: 768px) {
            .answers-comparison {
                grid-template-columns: 1fr;
            }

            .score-display {
                width: 150px;
                height: 150px;
            }

            .score-inner {
                width: 120px;
                height: 120px;
            }

            .score-number {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($exam_details): ?>
        <div class="header-card">
            <h1 style="color: #1e5596; margin-bottom: 20px;">
                <i class="fas fa-clipboard-check"></i> Exam Results
            </h1>
            <p style="color: #666; font-size: 1.1rem; margin-bottom: 30px;">
                <?php echo htmlspecialchars($exam_details['file_name']); ?>
            </p>
            
            <div class="score-display">
                <div class="score-inner">
                    <div class="score-number"><?php echo round($exam_details['score']); ?>%</div>
                    <div class="score-label">Score</div>
                </div>
            </div>
            
            <div class="performance-badge">
                <i class="fas fa-trophy"></i>
                <?php echo getPerformanceText($exam_details['score']); ?>
            </div>
            
            <div class="exam-info">
                <div class="info-item">
                    <div class="info-value" style="color: #28a745;">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $exam_details['correct_answers']; ?>
                    </div>
                    <div class="info-label">Correct Answers</div>
                </div>
                
                <div class="info-item">
                    <div class="info-value" style="color: #dc3545;">
                        <i class="fas fa-times-circle"></i>
                        <?php echo $exam_details['wrong_answers']; ?>
                    </div>
                    <div class="info-label">Wrong Answers</div>
                </div>
                
                <div class="info-item">
                    <div class="info-value" style="color: #17a2b8;">
                        <i class="fas fa-question-circle"></i>
                        <?php echo $exam_details['total_questions']; ?>
                    </div>
                    <div class="info-label">Total Questions</div>
                </div>
                
                <div class="info-item">
                    <div class="info-value" style="color: #fd7e14;">
                        <i class="fas fa-clock"></i>
                        <?php echo round($exam_details['time_spent'] / 60); ?> min
                    </div>
                    <div class="info-label">Time Spent</div>
                </div>
            </div>
        </div>

        <div class="questions-section">
            <h2 class="section-title">
                <i class="fas fa-list-check"></i>
                Detailed Question Review
            </h2>
            
            <?php foreach ($questions_review as $review): 
                $is_correct = $review['user_answer']['is_correct'];
                $is_skipped = empty($review['user_answer']['user_answer']);
                $status_class = $is_skipped ? 'skipped' : ($is_correct ? 'correct' : 'wrong');
                $status_text = $is_skipped ? 'Skipped' : ($is_correct ? 'Correct' : 'Wrong');
            ?>
            <div class="question-card <?php echo $status_class; ?>">
                <div class="question-header">
                    <span class="question-number">
                        <i class="fas fa-hashtag"></i> Question <?php echo $review['number']; ?>
                    </span>
                    <span class="status-badge <?php echo $status_class; ?>">
                        <?php if ($is_correct): ?>
                            <i class="fas fa-check"></i>
                        <?php elseif ($is_skipped): ?>
                            <i class="fas fa-minus"></i>
                        <?php else: ?>
                            <i class="fas fa-times"></i>
                        <?php endif; ?>
                        <?php echo $status_text; ?>
                    </span>
                </div>
                
                <div class="question-text">
                    <?php echo htmlspecialchars($review['question']['question']); ?>
                </div>
                
                <div class="answers-comparison">
                    <div class="answer-box user-answer <?php echo $status_class; ?>">
                        <div class="answer-label">
                            <i class="fas fa-user"></i>
                            Your Answer:
                        </div>
                        <div class="answer-text">
                            <?php 
                            if ($is_skipped) {
                                echo '<em style="color: #856404;">Not Answered</em>';
                            } else {
                                // Display the actual option text if it's a multiple choice
                                $user_answer_display = $review['user_answer']['user_answer'];
                                if ($review['question']['type'] === 'multiple_choice') {
                                    $user_index = is_numeric($user_answer_display) ? (int)$user_answer_display : $user_answer_display;
                                    if (isset($review['question']['options'][$user_index])) {
                                        $user_answer_display = $review['question']['options'][$user_index];
                                    }
                                }
                                echo htmlspecialchars($user_answer_display);
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="answer-box correct-answer">
                        <div class="answer-label">
                            <i class="fas fa-check-circle"></i>
                            Correct Answer:
                        </div>
                        <div class="answer-text">
                            <?php echo htmlspecialchars($review['user_answer']['correct_answer']); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="action-buttons">
            <a href="start_exam.php?material_id=<?php echo $exam_details['question_set_id']; ?>" class="btn btn-warning">
                <i class="fas fa-redo"></i>
                Retake Exam
            </a>
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
            <a href="upload_file.php" class="btn btn-success">
                <i class="fas fa-upload"></i>
                New Study Material
            </a>
        </div>

        <?php else: ?>
        <div class="header-card">
            <h1 style="color: #dc3545;">
                <i class="fas fa-exclamation-triangle"></i>
                No Results Found
            </h1>
            <p style="color: #666; margin: 20px 0;">
                Sorry, we couldn't find the exam results you're looking for.
            </p>
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-home"></i>
                Back to Dashboard
            </a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>