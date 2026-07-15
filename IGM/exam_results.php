<?php
// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$session_id = $_GET['session_id'] ?? ($_SESSION['last_session_id'] ?? 0);
$exam_data = [];
$user_answers = [];
$score_details = [];
$user = null;
$previous_attempts = []; // New array for previous attempts

error_log("=== Loading Exam Results ===");
error_log("Session ID: " . $session_id);
error_log("User ID: " . $user_id);

try {
    // Get user data
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($session_id) {
        // Get exam session data
        $stmt = $pdo->prepare("
            SELECT es.*, agq.questions, agq.file_name, agq.id as question_set_id
            FROM exam_sessions es 
            LEFT JOIN ai_generated_questions agq ON es.question_set_id = agq.id 
            WHERE es.id = ? AND es.user_id = ?
        ");
        $stmt->execute([$session_id, $user_id]);
        $exam_data = $stmt->fetch();

        if ($exam_data) {
            error_log("Exam data found: " . $exam_data['file_name']);
            
            // Get user answers
            $stmt = $pdo->prepare("SELECT * FROM user_answers WHERE session_id = ? ORDER BY question_number");
            $stmt->execute([$session_id]);
            $user_answers = $stmt->fetchAll();
            
            error_log("User answers count: " . count($user_answers));

            // Decode questions - full set of generated questions
            $questions = json_decode($exam_data['questions'] ?? '[]', true);
            
            // Calculate detailed scores
            $score_details = calculateDetailedScore($user_answers, $questions, $exam_data['correct_answers'], $exam_data['wrong_answers']);
            
            // --- New: Fetch Previous Attempts ---
            $stmt = $pdo->prepare("
                SELECT es.id, es.score, es.created_at, es.difficulty_level
                FROM exam_sessions es 
                WHERE es.user_id = ? AND es.question_set_id = ?
                ORDER BY es.created_at DESC
            ");
            $stmt->execute([$user_id, $exam_data['question_set_id']]);
            $all_attempts = $stmt->fetchAll();
            
            // Filter out the current attempt and store the rest
            foreach ($all_attempts as $attempt) {
                // Only keep attempts that are NOT the current one (ID check)
                if ($attempt['id'] != $session_id) {
                    $previous_attempts[] = $attempt;
                }
            }
            // --- End New: Fetch Previous Attempts ---

        } else {
            error_log("No exam data found for session ID: " . $session_id);
        }
    } else {
        error_log("No session ID provided");
    }
} catch (Exception $e) {
    error_log("Error loading exam results: " . $e->getMessage());
}

function calculateDetailedScore($user_answers, $questions, $correct_count, $wrong_count) {
    $details = [
        'total_questions' => $correct_count + $wrong_count + (count($questions) - count($user_answers)), // Estimate
        'correct_answers' => $correct_count,
        'wrong_answers' => $wrong_count,
        'skipped_answers' => 0,
        'score_by_type' => [],
        'time_analysis' => [],
        'weak_areas' => []
    ];
    
    // Recalculate skipped from user_answers directly if possible
    foreach ($user_answers as $answer) {
        if (!$answer['is_correct'] && empty($answer['user_answer'])) {
            $details['skipped_answers']++;
        }
    }
    
    // Final check for skipped count based on total questions in session table
    $total_in_session = $correct_count + $wrong_count;
    if ($total_in_session > count($user_answers)) {
        // This means there were fewer user_answers records than total questions, indicating true skipped questions not saved as records.
        $details['skipped_answers'] += $total_in_session - count($user_answers);
    }
    
    // We rely on the total_questions saved in exam_sessions table
    $details['total_questions'] = $total_in_session + $details['skipped_answers'];

    return $details;
}

function getPerformanceLevel($score) {
    if ($score >= 90) return ['Excellent', 'success', 'fa-trophy'];
    if ($score >= 80) return ['Very Good', 'primary', 'fa-star'];
    if ($score >= 70) return ['Good', 'warning', 'fa-check-circle'];
    if ($score >= 60) return ['Average', 'info', 'fa-clock'];
    return ['Poor', 'danger', 'fa-exclamation-triangle'];
}

function getDifficultyBadgeColor($difficulty) {
    switch (strtolower($difficulty)) {
        case 'easy': return 'badge-success';
        case 'medium': return 'badge-warning';
        case 'hard': return 'badge-danger';
        case 'all': return 'badge-primary';
        default: return 'badge-info'; 
    }
}

// Clear exam completion flag
unset($_SESSION['exam_completed']);
// unset($_SESSION['last_session_id']); // Keep this for potential internal use, but we rely on GET
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results - IGM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <?php if ($exam_data): ?>
        <div class="results-header">
            <h1>Exam Results</h1>
            <p>Exam: <?php echo htmlspecialchars($exam_data['file_name']); ?></p>
            <p>Date: <?php echo date('F j, Y g:i A', strtotime($exam_data['created_at'])); ?></p>
            <?php if (!empty($exam_data['difficulty_level'])): ?>
                <p>Difficulty: 
                    <span class="performance-badge badge-info" style="margin-top: 0;">
                        <i class="fas fa-brain"></i>
                        <?php echo htmlspecialchars($exam_data['difficulty_level']); ?>
                    </span>
                </p>
            <?php endif; ?>
            
            <?php
                $score = $exam_data['score'];
                if ($score >= 70) {
                    $fill_color = 'var(--success)';
                } elseif ($score >= 50) {
                    $fill_color = 'var(--warning)';
                } else {
                    $fill_color = 'var(--danger)';
                }
            ?>
            <div class="score-circle" style="background: conic-gradient(<?php echo $fill_color; ?> <?php echo $score; ?>%, var(--light-gray) <?php echo $score; ?>%);">
                <div class="score-inner">
                    <?php echo $score; ?>%
                </div>
            </div>
            
            <?php 
            list($performance, $badge_class, $icon) = getPerformanceLevel($exam_data['score']);
            ?>
            <div class="performance-badge badge-<?php echo $badge_class; ?>">
                <i class="fas <?php echo $icon; ?>"></i>
                <?php echo $performance; ?>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number text-success">
                    <?php echo $score_details['correct_answers']; ?>
                </div>
                <div>Correct Answers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-danger">
                    <?php echo $score_details['wrong_answers']; ?>
                </div>
                <div>Wrong Answers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-warning">
                    <?php echo $score_details['skipped_answers']; ?>
                </div>
                <div>Skipped Questions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-primary">
                    <?php echo round($exam_data['time_spent'] / 60); ?> min
                </div>
                <div>Time Spent</div>
            </div>
        </div>
        
        <?php if (!empty($previous_attempts)): ?>
        <div class="attempts-section">
            <h3><i class="fas fa-history"></i> Previous Attempts History</h3>
            <table class="attempts-table">
                <thead>
                    <tr>
                        <th>Attempt Date</th>
                        <th>Score</th>
                        <th>Difficulty</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <span class="performance-badge badge-current" style="margin-top: 0;">
                                Current Attempt
                            </span>
                        </td>
                        <td>
                            <span class="performance-badge badge-<?php echo $badge_class; ?>">
                                <?php echo $exam_data['score']; ?>%
                            </span>
                        </td>
                         <td>
                            <span class="performance-badge <?php echo getDifficultyBadgeColor($exam_data['difficulty_level'] ?? 'All'); ?>">
                                <?php echo htmlspecialchars($exam_data['difficulty_level'] ?? 'All'); ?>
                            </span>
                        </td>
                        <td>
                            <a href="exam_results.php?session_id=<?php echo $exam_data['id']; ?>" style="color: var(--info-cyan); text-decoration: none;">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php foreach ($previous_attempts as $index => $attempt): 
                        list($p_performance, $p_badge_class, $p_icon) = getPerformanceLevel($attempt['score']);
                    ?>
                    <tr>
                        <td><?php echo date('M j, Y g:i A', strtotime($attempt['created_at'])); ?></td>
                        <td>
                            <span class="performance-badge badge-<?php echo $p_badge_class; ?>">
                                <?php echo $attempt['score']; ?>%
                            </span>
                        </td>
                        <td>
                            <span class="performance-badge <?php echo getDifficultyBadgeColor($attempt['difficulty_level'] ?? 'All'); ?>">
                                <?php echo htmlspecialchars($attempt['difficulty_level'] ?? 'All'); ?>
                            </span>
                        </td>
                        <td>
                            <a href="exam_results.php?session_id=<?php echo $attempt['id']; ?>" style="color: var(--primary-blue); text-decoration: none;">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="questions-review">
            <h3>Question Review</h3>
            <?php
            // The $questions here are the full set, we should only review the questions answered/attempted in this session.
            // We rely on $user_answers which contains the correct_answer.
            $full_questions_set = json_decode($exam_data['questions'] ?? '[]', true); // Full set of generated questions
            
            foreach ($user_answers as $index => $answer): 
                // We try to find the question details from the full set using the question number
                $question = $full_questions_set[$answer['question_number'] - 1] ?? [];
                
                // If question is empty, it means the current session used a subset and we only have the data in $answer
                $question_text_display = $question['question'] ?? 'Question text not available (Check if this was a subset test)';
                
                $status_class = $answer['is_correct'] ? 'correct' : (empty($answer['user_answer']) ? 'skipped' : 'wrong');
            ?>
            <div class="question-item <?php echo $status_class; ?>">
                <div class="question-text">
                    Q <?php echo $answer['question_number']; ?>: <?php echo $question_text_display; ?>
                </div>
                
                <div class="answer-comparison">
                    <div class="user-answer <?php echo $status_class; ?>">
                        <strong>Your Answer:</strong><br>
                        <?php 
                        if (empty($answer['user_answer'])) {
                            echo '<span style="color: #856404;">Not Answered</span>';
                        } else {
                            $user_answer_display = htmlspecialchars($answer['user_answer']);
                            $question_data = $full_questions_set[$answer['question_number'] - 1] ?? null;
                            
                            if ($question_data && $question_data['type'] === 'multiple_choice') {
                                $user_option_index = -1;
                                foreach ($question_data['options'] as $opt_idx => $opt_text) {
                                    if (trim($opt_text) === trim($answer['user_answer'])) {
                                        $user_option_index = $opt_idx;
                                        break;
                                    }
                                }
                                
                                if ($user_option_index >= 0) {
                                    $user_answer_display = chr(65 + $user_option_index) . ') ' . htmlspecialchars($answer['user_answer']);
                                }
                            }
                            
                            echo $user_answer_display;
                        }
                        ?>
                    </div>

                    <div class="correct-answer <?php echo !$answer['is_correct'] ? 'blurred' : ''; ?>" id="answer-<?php echo $index; ?>">
                        <?php if (!$answer['is_correct']): ?>
                        <?php endif; ?>
                        <strong>Correct Answer:</strong><br>
                        <?php 
                        $correct_answer_display = htmlspecialchars($answer['correct_answer']);
                        $question_data = $full_questions_set[$answer['question_number'] - 1] ?? null;
                        
                        if ($question_data && $question_data['type'] === 'multiple_choice') {
                            $correct_option_index = -1;
                            foreach ($question_data['options'] as $opt_idx => $opt_text) {
                                if (trim($opt_text) === trim($answer['correct_answer'])) {
                                    $correct_option_index = $opt_idx;
                                    break;
                                }
                            }
                            
                            if ($correct_option_index >= 0) {
                                $correct_answer_display = chr(65 + $correct_option_index) . ') ' . htmlspecialchars($answer['correct_answer']);
                            }
                        }
                        
                        echo $correct_answer_display;
                        ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="action-buttons">
            <a href="exam_settings.php" class="btn btn-primary">
                <i class="fas fa-redo"></i>
                Retake Test
            </a>
            <a href="dashboard.php" class="btn btn-success">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="upload_file.php" class="btn btn-warning">
                <i class="fas fa-upload"></i>
                Upload New Material
            </a>
        </div>

        <?php else: ?>
        <div class="results-header" style="text-align: center;">
            <h1>No Results Found</h1>
            <p>Sorry, we couldn't find the exam results you're looking for.</p>
            
            <div class="debug-info">
                <h4>Debug Information:</h4>
                <p>Session ID: <?php echo htmlspecialchars($session_id); ?></p>
                <p>User ID: <?php echo htmlspecialchars($user_id); ?></p>
                <p>Check the error logs for more information.</p>
            </div>
            
            <div class="action-buttons">
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-home"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>