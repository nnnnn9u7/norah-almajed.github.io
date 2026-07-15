<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    $test_stmt = $pdo->query("SELECT 1");
    error_log("Database connection successful");
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your settings.");
}

$user_id = $_SESSION['user_id'];
$user = null;
$error_message = '';
$success_message = '';

$exam_started = isset($_SESSION['exam_started']) && $_SESSION['exam_started'];
$current_question = isset($_SESSION['current_question']) ? $_SESSION['current_question'] : 0;

$exam_data = [
    'title' => 'No Exam Available',
    'total_time' => 1800,
    'total_questions' => 0,
    'questions' => [],
    'break_times' => [5, 7, 10]
];

function generateDefaultExamQuestions($num_questions = 10) {
    $all_questions = [
        [
            'id' => 1,
            'question' => 'What is the primary definition of International Business?',
            'type' => 'multiple_choice',
            'options' => [
                'Performance of trade and investment activities by firms across national borders',
                'Exchange of products and services within domestic markets',
                'Transfer of assets within the same country',
                'Acquisition of local companies only'
            ],
            'correct_answer' => 0,
            'difficulty' => 'Easy',
            'points' => 5
        ],
        [
            'id' => 2,
            'question' => 'Which of the following is NOT one of the four major risks in international business?',
            'type' => 'multiple_choice',
            'options' => [
                'Cross-Cultural Risk',
                'Country Risk', 
                'Currency Risk',
                'Marketing Risk'
            ],
            'correct_answer' => 3,
            'difficulty' => 'Easy',
            'points' => 5
        ],
        [
            'id' => 3,
            'question' => 'What does FDI stand for in international business?',
            'type' => 'multiple_choice',
            'options' => [
                'Foreign Direct Investment',
                'Financial Development Index',
                'Foreign Domestic Income',
                'Free Development Initiative'
            ],
            'correct_answer' => 0,
            'difficulty' => 'Medium',
            'points' => 5
        ]
    ];
    
    return array_slice($all_questions, 0, min($num_questions, count($all_questions)));
}

function calculateExamScore($user_answers, $questions) {
    $score = 0;
    $total_points = 0;
    $correct_answers = 0;
    $wrong_answers = 0;
    
    if (!$questions || !is_array($questions)) {
        error_log("❌ Invalid questions array");
        return [
            'score' => 0,
            'total_points' => 0,
            'percentage' => 0,
            'correct' => 0,
            'wrong' => 0,
            'total' => 0
        ];
    }
    
    error_log("=== Calculating Score ===");
    error_log("Total questions: " . count($questions));
    error_log("User answers count: " . count($user_answers));
    
    foreach ($questions as $index => $question) {
        $points = isset($question['points']) ? (int)$question['points'] : 5;
        $total_points += $points;
        
        error_log("Q" . ($index + 1) . " - Points: " . $points);
        
        if (isset($user_answers[$index]) && $user_answers[$index] !== '' && $user_answers[$index] !== null) {
            if ($question['type'] === 'multiple_choice') {
                $user_answer = is_numeric($user_answers[$index]) ? (int)$user_answers[$index] : (int)$user_answers[$index];
                $correct_answer = is_numeric($question['correct_answer']) ? (int)$question['correct_answer'] : (int)$question['correct_answer'];
                
                error_log("Q" . ($index + 1) . " - User: $user_answer (type: " . gettype($user_answer) . "), Correct: $correct_answer (type: " . gettype($correct_answer) . ")");
                
                if ($user_answer === $correct_answer) {
                    $score += $points;
                    $correct_answers++;
                    error_log("✅ Q" . ($index + 1) . " CORRECT (+{$points} points)");
                } else {
                    $wrong_answers++;
                    error_log("❌ Q" . ($index + 1) . " WRONG (User: {$user_answer}, Correct: {$correct_answer})");
                }
            } else {
                $score += $points * 0.7;
                $correct_answers++;
                error_log("✅ Q" . ($index + 1) . " Essay - partial credit");
            }
        } else {
            $wrong_answers++; 
            error_log("⚠️ Q" . ($index + 1) . " NOT ANSWERED");
        }
    }
    
    $percentage = $total_points > 0 ? round(($score / $total_points) * 100, 1) : 0;
    
    error_log("=== Final Score ===");
    error_log("Score: $score/$total_points = $percentage%");
    error_log("Correct: $correct_answers, Wrong: $wrong_answers, Total: " . count($questions));
    
    return [
        'score' => $score,
        'total_points' => $total_points,
        'percentage' => $percentage,
        'correct' => $correct_answers,
        'wrong' => $wrong_answers,
        'total' => count($questions)
    ];
}

function saveExamResultsToDB($user_id, $score, $time_spent, $avg_time_per_question, $exam_data) {
    global $pdo;
    
    error_log("=== Starting to save exam results ===");
    
    try {
        $question_set_id = $exam_data['question_set_id'] ?? null;
        
        if (!$question_set_id) { 
            $stmt = $pdo->prepare("
                INSERT INTO ai_generated_questions 
                (user_id, file_name, file_content, questions, ai_model) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $exam_title = $exam_data['title'] ?? 'Manual Exam';
            $file_content = "Exam generated from manual test - " . date('Y-m-d H:i:s');
            $questions_json = json_encode($exam_data['questions']);
            
            $stmt->execute([
                $user_id,
                $exam_title,
                $file_content,
                $questions_json,
                'Manual_Exam_System'
            ]);
            
            $question_set_id = $pdo->lastInsertId();
        }
        
        $difficulty_level = $exam_data['selected_difficulty'] ?? 'All';
        
        // Try with difficulty_level column first
        try {
            $stmt = $pdo->prepare("
                INSERT INTO exam_sessions 
                (user_id, question_set_id, score, total_questions, correct_answers, wrong_answers, time_spent, average_time_per_question, status, difficulty_level) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?)
            ");
            
            $result = $stmt->execute([
                $user_id,
                $question_set_id,
                $score['percentage'],
                $score['total'],
                $score['correct'],
                $score['wrong'],
                $time_spent,
                $avg_time_per_question,
                $difficulty_level
            ]);
            
            $session_id = $pdo->lastInsertId();
            error_log("✅ Exam results saved with difficulty_level column");
            
        } catch (Exception $e) {
            // If failed, try without difficulty_level column
            error_log("⚠️ difficulty_level column not found, trying without it: " . $e->getMessage());
            
            $stmt = $pdo->prepare("
                INSERT INTO exam_sessions 
                (user_id, question_set_id, score, total_questions, correct_answers, wrong_answers, time_spent, average_time_per_question, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed')
            ");
            
            $result = $stmt->execute([
                $user_id,
                $question_set_id,
                $score['percentage'],
                $score['total'],
                $score['correct'],
                $score['wrong'],
                $time_spent,
                $avg_time_per_question
            ]);
            
            $session_id = $pdo->lastInsertId();
            error_log("✅ Exam results saved without difficulty_level column");
        }
        
        // Save detailed answers
        $answers_saved = saveUserAnswersToDB($session_id, $_SESSION['user_answers'], $exam_data['questions']);
        
        if (!$answers_saved) {
            error_log("Warning: Some answers may not have been saved correctly");
        }
        
        return $session_id;
        
    } catch (Exception $e) {
        error_log("Error saving exam results: " . $e->getMessage());
        error_log("Error details: " . $e->getTraceAsString());
        throw $e;
    }
}

function saveUserAnswersToDB($session_id, $user_answers, $questions) {
    global $pdo;
    
    try {
        if (!$questions || !is_array($questions)) {
            error_log("❌ No questions provided for saving answers");
            return false;
        }
        
        $saved_count = 0;
        error_log("=== Saving " . count($questions) . " questions to database ===");
        
        foreach ($questions as $index => $question) {
            $user_answer_index = isset($user_answers[$index]) ? $user_answers[$index] : '';
            $is_correct = false;
            $time_spent = 60;
            
            error_log("Q" . ($index + 1) . " - Question: " . substr($question['question'], 0, 50) . "...");
            
            $user_answer_text = '';
            $correct_answer_text = '';
            
            if ($question['type'] === 'multiple_choice') {
                $user_idx = is_numeric($user_answer_index) ? (int)$user_answer_index : -1;
                $correct_idx = is_numeric($question['correct_answer']) ? (int)$question['correct_answer'] : (int)$question['correct_answer'];
                
                if ($user_idx >= 0 && isset($question['options'][$user_idx])) {
                    $user_answer_text = $question['options'][$user_idx];
                }
                
                if (isset($question['options'][$correct_idx])) {
                    $correct_answer_text = $question['options'][$correct_idx];
                } else {
                    $correct_answer_text = 'Option ' . ($correct_idx + 1);
                }
                
                $is_correct = ($user_idx === $correct_idx);
                
                error_log("Q" . ($index + 1) . " - User Index: $user_idx, Correct Index: $correct_idx, Match: " . ($is_correct ? 'YES' : 'NO'));
            } else {
                $user_answer_text = $user_answer_index;
                $correct_answer_text = 'Essay question';
                $is_correct = !empty($user_answer_index);
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO user_answers 
                (session_id, question_number, user_answer, correct_answer, is_correct, time_spent) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            error_log("Q" . ($index + 1) . " - Saving User: '$user_answer_text', Correct: '$correct_answer_text', Is Correct: " . ($is_correct ? 'YES' : 'NO'));
            
            $result = $stmt->execute([
                $session_id,
                $index + 1,
                $user_answer_text,
                $correct_answer_text,
                $is_correct ? 1 : 0,
                $time_spent
            ]);
            
            if ($result) {
                $saved_count++;
                error_log("✅ Q" . ($index + 1) . " saved successfully");
            } else {
                error_log("❌ Q" . ($index + 1) . " failed to save");
            }
        }
        
        error_log("=== Total saved: $saved_count out of " . count($questions) . " ===");
        
        return $saved_count > 0;
    } catch (Exception $e) {
        error_log("❌ Error saving user answers: " . $e->getMessage());
        return false;
    }
}

// Main processing
try {
    // Get user data
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Check if we have filtered exam data from exam_settings.php
    if (isset($_SESSION['exam_data']['questions_to_use'])) {
        $exam_data['questions'] = $_SESSION['exam_data']['questions_to_use'];
        $exam_data['total_questions'] = $_SESSION['exam_data']['total_questions_to_use'];
        $exam_data['total_time'] = $_SESSION['exam_data']['total_time_to_use'];
        $exam_data['title'] = $_SESSION['exam_data']['title'] ?? 'Generated Exam';
        $exam_data['question_set_id'] = $_SESSION['exam_data']['question_set_id'];
        $exam_data['selected_difficulty'] = $_SESSION['exam_data']['selected_difficulty'] ?? 'All';

        if (empty($success_message) && !$exam_started) {
            $success_message = "AI-generated exam loaded successfully for " . ($exam_data['selected_difficulty']) . " difficulty!";
        }
        
    } else if (isset($_SESSION['exam_data'])) {
        if (!isset($_SESSION['exam_data']['selected_difficulty'])) {
             header("Location: exam_settings.php");
             exit();
        }
    } else {
        $default_question_count = 10;
        $exam_data['questions'] = generateDefaultExamQuestions($default_question_count);
        $exam_data['total_questions'] = count($exam_data['questions']);
        $exam_data['total_time'] = $default_question_count * 180;
        $exam_data['title'] = "General Knowledge Test";
        $warning_message = "Using sample exam questions. Upload a file to generate personalized questions.";
        $exam_data['selected_difficulty'] = 'All (Sample)';
    }

    // Process POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['start_exam'])) {
            if (empty($exam_data['questions'])) {
                $error_message = "No questions available. Please upload a study file first.";
            } else {
                $start_time = time();
                $_SESSION['exam_started'] = true;
                $_SESSION['exam_start_time'] = $start_time; // Total exam timer start
                $_SESSION['question_start_time'] = $start_time; // Start question timer
                $_SESSION['current_question'] = 0;
                $_SESSION['user_answers'] = [];
                $_SESSION['score'] = 0;
                $_SESSION['incorrect_streak'] = 0; // NEW: Reset streaks
                $_SESSION['correct_streak'] = 0;   // NEW: Reset streaks
                $exam_started = true;
                $current_question = 0;
                $success_message = "Exam started successfully! Good luck!";
            }
            
        } elseif (isset($_POST['next_question']) && $exam_started) {
            if (isset($_POST['answer'])) {
                $_SESSION['user_answers'][$current_question] = $_POST['answer'];
                error_log("Saving answer for Q" . ($current_question + 1) . ": " . $_POST['answer']);
            }
            
            // --- NEW: Streak Tracking Logic (PHP) ---
            $is_correct = false;
            if (isset($exam_data['questions'][$current_question])) {
                $current_q_data = $exam_data['questions'][$current_question];
                $user_answer = $_POST['answer'] ?? null;
                
                if ($current_q_data['type'] === 'multiple_choice' && $user_answer !== null) {
                    $user_answer_index = is_numeric($user_answer) ? (int)$user_answer : $user_answer;
                    $correct_answer_index = is_numeric($current_q_data['correct_answer']) ? (int)$current_q_data['correct_answer'] : $current_q_data['correct_answer'];
                    $is_correct = ($user_answer_index === $correct_answer_index);
                }
            }
            
            if (!$is_correct) {
                $_SESSION['incorrect_streak'] = ($_SESSION['incorrect_streak'] ?? 0) + 1;
                $_SESSION['correct_streak'] = 0;
            } else {
                $_SESSION['correct_streak'] = ($_SESSION['correct_streak'] ?? 0) + 1;
                $_SESSION['incorrect_streak'] = 0;
            }

            $_SESSION['streak_status'] = [
                'incorrect' => $_SESSION['incorrect_streak'],
                'correct' => $_SESSION['correct_streak']
            ];
            // --- END NEW: Streak Tracking Logic ---

            $_SESSION['current_question']++;
            $current_question++;
            $_SESSION['question_start_time'] = time(); // Reset question timer
            
        } elseif (isset($_POST['previous_question']) && $exam_started && $current_question > 0) {
            if (isset($_POST['answer'])) {
                $_SESSION['user_answers'][$current_question] = $_POST['answer'];
            }
            $_SESSION['current_question']--;
            $current_question--;
            $_SESSION['question_start_time'] = time(); // Reset question timer
            
        } elseif (isset($_POST['finish_exam']) && $exam_started) {
            if (isset($_POST['answer'])) {
                $_SESSION['user_answers'][$current_question] = $_POST['answer'];
            }
            
            error_log("=== Final user answers in session ===");
            foreach ($_SESSION['user_answers'] as $q_num => $answer) {
                error_log("Session Q" . ($q_num + 1) . ": $answer");
            }
            
            $score = calculateExamScore($_SESSION['user_answers'], $exam_data['questions']);
            $_SESSION['exam_score'] = $score;
            
            $time_spent = time() - $_SESSION['exam_start_time'];
            $avg_time_per_question = count($exam_data['questions']) > 0 ? $time_spent / count($exam_data['questions']) : 0;
            
            try {
                $session_id = saveExamResultsToDB($user_id, $score, $time_spent, $avg_time_per_question, $exam_data);
                
                $_SESSION['last_session_id'] = $session_id;
                
                unset($_SESSION['exam_started']);
                unset($_SESSION['current_question']);
                unset($_SESSION['user_answers']);
                unset($_SESSION['exam_start_time']);
                unset($_SESSION['question_start_time']);
                unset($_SESSION['incorrect_streak']);
                unset($_SESSION['correct_streak']);
                unset($_SESSION['streak_status']);
                
                session_write_close();
                
                header("Location: exam_results.php?session_id=" . $session_id);
                exit();
            } catch (Exception $e) {
                $error_message = "Error saving exam results: " . $e->getMessage();
            }
        }
    }
    
    // Prepare data for JavaScript
    $js_question_start_time = $_SESSION['question_start_time'] ?? time();
    $js_exam_start_time = $_SESSION['exam_start_time'] ?? time();
    $js_streak_status = $_SESSION['streak_status'] ?? ['incorrect' => 0, 'correct' => 0];

} catch (Exception $e) {
    $error_message = "System error: " . $e->getMessage();
}

function getDifficultyBadgeColor($difficulty) {
    switch (strtolower($difficulty)) {
        case 'easy': return '#28a745';
        case 'medium': return '#ffc107';
        case 'hard': return '#dc3545';
        default: return '#17a2b8';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Exam - IGM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* ... (CSS is unchanged from original file) ... */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .top-nav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .menu-toggle {
            background: none;
            border: none;
            color: #1e5596;
            font-size: 20px;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .menu-toggle:hover {
            background: #f0f5ff;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo img {
            height: 40px;
            width: auto;
        }

        .logo-text {
            font-weight: bold;
            font-size: 20px;
            color: #1e5596;
        }

        .user-section {
            position: relative;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 8px 16px;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .user-info:hover {
            background: #f0f5ff;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fcb408, #ffd166);
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
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            width: 200px;
            z-index: 1001;
            display: none;
            overflow: hidden;
            margin-top: 5px;
        }

        .user-dropdown.show {
            display: block;
        }

        .dropdown-item {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #333;
            text-decoration: none;
            transition: background 0.3s;
            border-bottom: 1px solid #f0f0f0;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e5596 0%, #1d4c82 100%);
            color: white;
            width: 280px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: -280px;
            transition: left 0.3s ease;
            z-index: 999;
            padding: 80px 0 20px 0;
        }

        .sidebar.open {
            left: 0;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            padding: 15px 25px;
            margin: 5px 0;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            border-left: 4px solid transparent;
        }

        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.1);
            border-left: 4px solid #fcb408;
        }

        .nav-item i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
            font-size: 18px;
        }

        .nav-item a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            width: 100%;
            font-size: 16px;
        }

        .main-content {
            margin-top: 80px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .hero-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, #1e5596, #3498db);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .hero-subtitle {
            font-size: 1.5rem;
            color: #666;
            font-weight: 500;
        }

        .user-welcome {
            text-align: center;
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        .message {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 4px solid;
        }

        .success {
            background: #f0f9f4;
            color: #0f5132;
            border-left-color: #28a745;
        }

        .error {
            background: #fdf2f2;
            color: #721c24;
            border-left-color: #e74c3c;
        }

        .warning {
            background: #fffbf0;
            color: #856404;
            border-left-color: #ffc107;
        }

        .exam-section {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }

        .exam-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .exam-title {
            font-size: 2rem;
            color: #1e5596;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .ai-badge {
            background: linear-gradient(135deg, #73b86a, #8bc34a);
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .instructions-card {
            background: #e3f2fd;
            border: 2px solid #3498db;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
        }

        .instructions-card h3 {
            color: #1e5596;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .instructions-list {
            list-style: none;
        }

        .instructions-list li {
            padding: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #2c3e50;
        }

        .instructions-list li i {
            color: #27ae60;
        }

        .start-button {
            background: linear-gradient(135deg, #fcb408, #ffd166);
            color: white;
            border: none;
            padding: 20px 60px;
            border-radius: 50px;
            font-size: 1.3rem;
            font-weight: 700;
            cursor: pointer;
            display: block;
            margin: 40px auto 20px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(252, 180, 8, 0.3);
            transition: all 0.3s;
        }

        .start-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(252, 180, 8, 0.4);
        }

        .progress-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .progress-bar {
            flex-grow: 1;
            height: 10px;
            background: #e9ecef;
            border-radius: 5px;
            margin: 0 20px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #27ae60, #2ecc71);
            border-radius: 5px;
            transition: width 0.3s;
        }

        .question-container {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .question-text {
            font-size: 1.4rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .question-points {
            background: #3498db;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-left: 10px;
        }

        .difficulty-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            color: white;
            margin-bottom: 15px;
        }
        
        .options-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .option-label {
            display: flex;
            align-items: center;
            padding: 18px 20px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .option-label:hover {
            border-color: #3498db;
            background: #e3f2fd;
            transform: translateY(-2px);
        }

        .option-label.selected {
            border-color: #27ae60;
            background: #d5f4e6;
        }

        .option-input {
            margin-right: 15px;
            transform: scale(1.2);
        }

        .essay-textarea {
            width: 100%;
            min-height: 200px;
            padding: 20px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
            transition: border-color 0.3s;
        }

        .essay-textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            gap: 15px;
        }

        .nav-button {
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f39c12, #f1c40f);
            color: white;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }
        
        .btn-info {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
        }

        .nav-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 998;
            display: none;
        }

        .sidebar-overlay.show {
            display: block;
        }
        
        /* NEW: Timer Bar Styles (Fixed Position) */
        .timer-bar {
            position: fixed;
            top: 60px; /* Below top-nav (60px) */
            left: 0;
            right: 0;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 10px 30px;
            z-index: 990;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
            color: #1e5596;
        }

        .timer-value {
            font-size: 1.2rem;
            color: #27ae60;
        }
        
        .timer-bar .danger {
            color: #dc3545;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            /* Crucial Fix: Increase margin for main content to accommodate top bar (60px) and timer bar (approx 50px) */
            .main-content {
                margin-top: 120px; 
                padding: 20px;
            }
            
            .timer-bar {
                padding: 10px 15px;
            }
            
            .timer-value {
                font-size: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (min-width: 769px) {
             /* Crucial Fix: Ensure desktop view also has space for the timer bar */
            .main-content {
                margin-top: 110px; 
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
                <span class="logo-text">IGM Learning</span>
            </div>
        </div>
        
        <div class="user-section">
            <div class="user-info" id="userInfo">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <span id="userDisplayName"><?php echo htmlspecialchars($user['full_name'] ?? 'User Name'); ?></span>
                <i class="fas fa-chevron-down"></i>
            </div>
            
            <div class="user-dropdown" id="userDropdown">
                <a href="dashboard.php" class="dropdown-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="upload_file.php" class="dropdown-item">
                    <i class="fas fa-upload"></i>
                    <span>Upload New File</span>
                </a>
                <a href="exam_settings.php" class="dropdown-item">
                    <i class="fas fa-cog"></i>
                    <span>Exam Settings</span>
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
                    <span>Study Behavior</span>
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
                    <span>Study Schedule</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="hakathons.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Courses & Events</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="aboutus.php">
                    <i class="fas fa-info-circle"></i>
                    <span>About Us</span>
                </a>
            </li>
            <li class="nav-item active">
                <a href="start_exam.php">
                    <i class="fas fa-play-circle"></i>
                    <span>Take Test</span>
                </a>
            </li>
        </ul>
    </div>
    
    

    <div class="container">
        <main class="main-content">
            <div class="hero-section">
                <h1 class="hero-title">AI-Powered Exam</h1>
                <h2 class="hero-subtitle">Knowledge Assessment Platform</h2>
                
                <?php if ($user): ?>
                <div class="user-welcome">
                    Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>! Ready to test your knowledge?
                </div>
                <?php endif; ?>
            </div>

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

            <?php if (isset($warning_message)): ?>
                <div class="message warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $warning_message; ?>
                    <a href="upload_file.php" style="margin-left: 10px; color: #856404; text-decoration: underline;">
                        Upload File Now
                    </a>
                </div>
            <?php endif; ?>

            <?php if (!$exam_started): ?>
            <div class="exam-section">
                <div class="exam-header">
                    <h2 class="exam-title">
                        <i class="fas fa-file-alt"></i> <?php echo $exam_data['title']; ?>
                        <?php if (isset($exam_data['question_set_id'])): ?>
                            <span class="ai-badge">AI-Generated</span>
                        <?php endif; ?>
                    </h2>
                    <?php if (isset($exam_data['selected_difficulty'])): ?>
                        <div style="font-size: 1.1rem; color: #666; margin-top: 10px;">
                            Difficulty Selected: 
                            <strong style="color: <?php echo getDifficultyBadgeColor($exam_data['selected_difficulty']); ?>">
                                <?php echo htmlspecialchars($exam_data['selected_difficulty']); ?>
                            </strong>
                            <a href="exam_settings.php" style="margin-left: 10px; font-size: 0.9em; color: #3498db;">(Change)</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $exam_data['total_questions']; ?></div>
                        <div class="stat-label">Total Questions</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo floor($exam_data['total_time'] / 60); ?></div>
                        <div class="stat-label">Minutes Duration</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">MCQ</div>
                        <div class="stat-label">Question Type</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">Adaptive</div>
                        <div class="stat-label">Difficulty</div>
                    </div>
                </div>

                <?php if (isset($exam_data['question_set_id'])): ?>
                <div class="instructions-card">
                    <h3><i class="fas fa-robot"></i> Personalized Exam Features</h3>
                    <ul class="instructions-list">
                        <li><i class="fas fa-check"></i> Questions tailored to your learning style</li>
                        <li><i class="fas fa-check"></i> Based on your uploaded study material</li>
                        <li><i class="fas fa-check"></i> Adaptive difficulty levels</li>
                        <li><i class="fas fa-check"></i> Instant results and feedback</li>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="instructions-card">
                    <h3><i class="fas fa-info-circle"></i> Exam Instructions</h3>
                    <ul class="instructions-list">
                        <li><i class="fas fa-play"></i> Answer all questions to complete the exam</li>
                        <li><i class="fas fa-list"></i> Multiple choice questions have one correct answer</li>
                        <li><i class="fas fa-arrows-alt-h"></i> You can navigate between questions</li>
                        <li><i class="fas fa-chart-line"></i> Results will be shown immediately after completion</li>
                        <li><i class="fas fa-save"></i> The exam will be automatically saved</li>
                    </ul>
                </div>

                <?php if ($exam_data['total_questions'] > 0): ?>
                <form method="POST">
                    <button type="submit" name="start_exam" class="start-button">
                        <i class="fas fa-play-circle"></i> Start Exam Now
                    </button>
                </form>
                <?php else: ?>
                <div style="text-align: center; padding: 40px;">
                    <div style="font-size: 4rem; color: #e74c3c; margin-bottom: 20px;">
                        <i class="fas fa-file-excel"></i>
                    </div>
                    <h3 style="color: #e74c3c; margin-bottom: 15px;">No Questions Available</h3>
                    <p style="color: #666; margin-bottom: 25px;">Please upload a study file to generate exam questions.</p>
                    <a href="upload_file.php" style="display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #3498db, #2980b9); color: white; text-decoration: none; border-radius: 25px; font-weight: bold; transition: all 0.3s;">
                        <i class="fas fa-upload"></i> Upload Study Material
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <?php else: ?>
            <?php
            if (isset($exam_data['questions'][$current_question])) {
                $current_q = $exam_data['questions'][$current_question];
                $progress = (($current_question + 1) / $exam_data['total_questions']) * 100;
                $saved_answer = isset($_SESSION['user_answers'][$current_question]) ? (string)$_SESSION['user_answers'][$current_question] : '';
            }
            ?>
            
            <div class="progress-section">
                <div class="progress-info">
                    <div style="font-weight: 600; color: #2c3e50;">
                        Question <?php echo $current_question + 1; ?> of <?php echo $exam_data['total_questions']; ?>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                    </div>
                    <div style="font-weight: 600; color: #27ae60;">
                        <?php echo round($progress); ?>% Complete
                    </div>
                </div>
            </div>

            <div class="question-container">
                <form method="POST" id="examForm">
                    <?php if (isset($current_q['difficulty'])): ?>
                    <span class="difficulty-badge" style="background-color: <?php echo getDifficultyBadgeColor($current_q['difficulty']); ?>;">
                        <i class="fas fa-brain"></i> <?php echo htmlspecialchars($current_q['difficulty']); ?>
                    </span>
                    <?php endif; ?>
                    
                    <div class="question-text">
                        <?php echo $current_q['question']; ?>
                        <span class="question-points"><?php echo $current_q['points']; ?> pts</span>
                    </div>

                    <?php if ($current_q['type'] === 'multiple_choice'): ?>
                    <div class="options-container">
                        <?php foreach ($current_q['options'] as $key => $option): ?>
                        <label class="option-label <?php echo $saved_answer === (string)$key ? 'selected' : ''; ?>">
                            <input type="radio" name="answer" value="<?php echo $key; ?>" 
                                   class="option-input" <?php echo $saved_answer === (string)$key ? 'checked' : ''; ?>>
                            <strong style="color: #1e5596; min-width: 30px;"><?php echo chr(65 + $key); ?>.</strong> 
                            <span><?php echo $option; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <textarea name="answer" class="essay-textarea" placeholder="Type your detailed answer here..."><?php echo $saved_answer; ?></textarea>
                    <?php endif; ?>

                    <div class="navigation-buttons">
                        <button type="submit" name="previous_question" class="nav-button btn-warning" 
                                <?php echo $current_question === 0 ? 'disabled' : ''; ?>>
                            <i class="fas fa-arrow-left"></i> Previous Question
                        </button>
                        
                        <?php if ($current_question < $exam_data['total_questions'] - 1): ?>
                        <button type="submit" name="next_question" class="nav-button btn-primary">
                            Next Question <i class="fas fa-arrow-right"></i>
                        </button>
                        <?php else: ?>
                        <button type="submit" name="finish_exam" class="nav-button btn-success">
                            <i class="fas fa-flag-checkered"></i> Finish Exam
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <div id="breakModal" style="
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        display: none; /* Initially hidden */
        align-items: center;
        justify-content: center;
        z-index: 9999;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    ">
        <div style="background: white; border-radius: 15px; padding: 30px; max-width: 500px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
            <div style="font-size: 3rem; margin-bottom: 15px;">☕</div>
            <h2 style="color: #1e5596; margin-bottom: 15px;" id="modalTitle">Time for a Quick Break!</h2>
            <p style="color: #666; margin-bottom: 25px; line-height: 1.6;" id="modalMessage">
                A short break has automatically started to help you refresh your focus!
            </p>
            
            <div id="breakActivity" style="background: #f0f8ff; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #1e5596;">
                <h4 style="color: #1e5596; margin-bottom: 10px;">Break Activity (4:00)</h4>
                <div style="font-size: 2rem; margin-bottom: 10px;" id="breakEmoji">...</div>
                <div id="breakText" style="font-weight: bold; color: #2c3e50;">...</div>
                <div id="breakAnswer" style="margin-top: 10px; color: #27ae60; display: none; font-style: italic;"></div>
                <button id="revealAnswerBtn" style="margin-top: 10px; padding: 8px 15px; background: #3498db; color: white; border: none; border-radius: 5px; display: none;">Show Answer</button>
                <div id="breakTimerDisplay" style="margin-top: 15px; font-weight: bold; color: #2c3e50;">04:00</div>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button id="skipBreakBtn" class="nav-button btn-warning" style="display: none; padding: 12px 25px;">
                    <i class="fas fa-forward"></i> Skip Break
                </button>
                <button id="continueExamBtn" class="nav-button" style="padding: 12px 25px; background: #95a5a6; color: white; border: none; font-weight: bold; cursor: pointer;">
                    Continue Exam
                </button>
            </div>
        </div>
    </div>
    <script>
        // ... (Existing script code for options, form, and nav handlers) ...
        document.querySelectorAll('.options-container .option-label').forEach(label => {
            label.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                }
                
                document.querySelectorAll('.options-container .option-label').forEach(l => {
                    l.classList.remove('selected');
                });
                this.classList.add('selected');
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
                const label = radio.closest('.option-label');
                if (label) {
                    label.classList.add('selected');
                }
            });
        });

        let formSubmitting = false;
        
        const examForm = document.getElementById('examForm');
        if (examForm) {
            examForm.addEventListener('submit', function() {
                formSubmitting = true;
            });
        }
        
        window.addEventListener('beforeunload', function(e) {
            if (document.getElementById('examForm') && !formSubmitting) {
                e.preventDefault();
                e.returnValue = 'Are you sure you want to leave? Your exam progress may be lost.';
            }
        });

        document.getElementById("userDisplayName").textContent = "<?php echo htmlspecialchars($user['full_name'] ?? 'User Name'); ?>";

        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mainContent = document.getElementById('mainContent');
        const userInfo = document.getElementById('userInfo');
        const userDropdown = document.getElementById('userDropdown');
        const logoutBtn = document.getElementById('logoutBtn');

        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('show');
        });

        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
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

        const currentPage = window.location.pathname.split('/').pop();
        document.querySelectorAll('.nav-menu .nav-item a').forEach(link => {
            const linkHref = link.getAttribute('href').split('/').pop();
            if(linkHref === currentPage) {
                link.parentElement.classList.add('active');
            }
        });

        // --- EXAM TIMER LOGIC ---
        
        const totalExamDuration = <?php echo $exam_data['total_time']; ?>; // In seconds
        const examTimerDisplay = document.getElementById('examTimerDisplay');
        const examStartTime = <?php echo $js_exam_start_time; ?> * 1000; // In milliseconds
        
        function updateExamTimer() {
            const now = Date.now();
            const elapsedTime = Math.floor((now - examStartTime) / 1000); // In seconds
            let remainingTime = totalExamDuration - elapsedTime;
            
            if (remainingTime <= 0) {
                remainingTime = 0;
                // Force finish exam on timeout
                clearInterval(examTimerInterval);
                if (examForm) {
                     alert("Time's up! The exam will now be submitted automatically.");
                     const finishInput = document.createElement('input');
                     finishInput.type = 'hidden';
                     finishInput.name = 'finish_exam';
                     finishInput.value = '1';
                     examForm.appendChild(finishInput);
                     examForm.submit();
                }
            }

            const minutes = Math.floor(remainingTime / 60);
            const seconds = remainingTime % 60;
            const timeString = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            if (examTimerDisplay) {
                examTimerDisplay.textContent = timeString;
                
                // Change color if running low on time (e.g., last 10 minutes)
                if (remainingTime <= 600) { 
                    examTimerDisplay.classList.add('danger');
                } else {
                    examTimerDisplay.classList.remove('danger');
                }
            }
        }
        
        let examTimerInterval;
        if (examStarted) {
            updateExamTimer();
            examTimerInterval = setInterval(updateExamTimer, 1000);
        }
        // --- END EXAM TIMER LOGIC ---

        // --- Smart Break Logic Implementation ---
        
        // Time constants in milliseconds/seconds
        const QUESTION_STUCK_TIME_MS = 5 * 60 * 1000;   // 5 minutes
        const INLINE_BREAK_DURATION_S = 4 * 60;         // 4 minutes
        const SKIP_BREAK_DELAY_S = 60;                  // 1 minute delay before skip is allowed
        const ERROR_STREAK_THRESHOLD = 3;               // 3 incorrect answers
        const SUCCESS_STREAK_THRESHOLD = 4;             // 4 correct answers
        
        let questionStartTime = Date.now();
        let breakSuggested = false;
        let breakTimerInterval = null;
        let currentBreakTime = INLINE_BREAK_DURATION_S;

        // PHP data injected for streak check
        const jsQuestionStartTime = <?php echo $js_question_start_time; ?> * 1000;
        const streakStatus = <?php echo json_encode($_SESSION['streak_status'] ?? ['incorrect' => 0, 'correct' => 0]); ?>;
        
        // Adjust client-side timer based on server time only if exam is active
        if (examStarted) {
            // This ensures question time starts from the PHP session time, preventing false positives on reload.
            questionStartTime = jsQuestionStartTime; 
        }
        
        // Break activities list (Used for random, non-repeating break activity)
        const breakActivities = [
            { text: "Drink a glass of water", emoji: "🚰", type: "drink" },
            { text: "Have a glass of fresh juice", emoji: "🧃", type: "drink" },
            { text: "Enjoy a cup of coffee", emoji: "☕", type: "drink" },
            { text: "Eat a piece of cake", emoji: "🍰", type: "food" },
            { text: "Have some potato chips", emoji: "🍟", type: "food" },
            { text: "Read Surah Al-Fatiha", emoji: "📖", type: "spiritual" },
            { text: "Read this prayer: O Allah, I ask You for beneficial knowledge", emoji: "🙏", type: "spiritual" },
            { text: "Stand up and walk around the room", emoji: "🚶‍♂️", type: "movement" },
            { text: "Take 5 deep breaths", emoji: "🌬️", type: "relaxation" },
            { text: "Dance for one minute", emoji: "💃", type: "movement" },
            { text: "Jump in place 10 times", emoji: "🦘", type: "movement" },
            { text: "What has an eye but cannot see?", emoji: "❓", type: "puzzle", answer: "A needle" },
            { text: "What increases the more you take away from it?", emoji: "❓", type: "puzzle", answer: "A hole" },
            { text: "What has a head and a tail but no body?", emoji: "❓", type: "puzzle", answer: "A coin" },
            { text: "What can travel around the world while staying in a corner?", emoji: "❓", type: "puzzle", answer: "A stamp" },
            { text: "What gets wet while drying?", emoji: "❓", type: "puzzle", answer: "A towel" },
            { text: "Do some arm stretching exercises", emoji: "💪", type: "movement" },
            { text: "Open the window and breathe fresh air", emoji: "🌳", type: "relaxation" },
            { text: "Call a friend and talk for a minute", emoji: "📞", type: "social" },
            { text: "Listen to calming music for one minute", emoji: "🎵", type: "relaxation" }
        ];

        let usedActivities = JSON.parse(localStorage.getItem('usedBreakActivities') || '[]');
        
        // Helper to get a random non-repeating activity
        function getRandomActivity() {
            if (usedActivities.length >= breakActivities.length) {
                usedActivities = [];
            }
            const availableActivities = breakActivities.filter(activity => 
                !usedActivities.some(used => used.text === activity.text)
            );
            const randomIndex = Math.floor(Math.random() * availableActivities.length);
            const selectedActivity = availableActivities[randomIndex];
            usedActivities.push(selectedActivity);
            localStorage.setItem('usedBreakActivities', JSON.stringify(usedActivities));
            return selectedActivity;
        }

        // 1. Function to display and START the break modal
        function showBreakModal(title, message) {
            if (breakModal.style.display === 'flex' || !examStarted) return; 
            
            breakSuggested = true;
            modalTitle.textContent = title;
            modalMessage.textContent = message;
            
            // Reset and start break timer automatically
            startBreakTimer(); 

            // Load and display activity
            const activity = getRandomActivity();
            breakEmoji.textContent = activity.emoji;
            breakText.textContent = activity.text;
            
            if (activity.type === "puzzle") {
                breakAnswer.textContent = `Answer: ${activity.answer}`;
                breakAnswer.style.display = "none";
                revealAnswerBtn.style.display = "block";
            } else {
                breakAnswer.style.display = "none";
                revealAnswerBtn.style.display = "none";
            }
            
            // Hide Skip button initially
            skipBreakBtn.style.display = 'none';
            continueExamBtn.textContent = 'Continue Exam';
            continueExamBtn.style.backgroundColor = '#95a5a6'; // Gray/Neutral

            breakModal.style.display = 'flex';
        }
        
        // 2. Timer logic
        function startBreakTimer() {
            currentBreakTime = INLINE_BREAK_DURATION_S;
            updateBreakTimerDisplay();
            
            clearInterval(breakTimerInterval); // Clear any existing timer
            breakTimerInterval = setInterval(function() {
                currentBreakTime--;
                updateBreakTimerDisplay();
                
                // Show skip button after 60 seconds
                if (currentBreakTime <= (INLINE_BREAK_DURATION_S - SKIP_BREAK_DELAY_S) && currentBreakTime > 0) {
                    skipBreakBtn.style.display = 'inline-flex';
                }
                
                if (currentBreakTime <= 0) {
                    clearInterval(breakTimerInterval);
                    // Automatically end break if time runs out
                    continueExam();
                }
            }, 1000);
        }
        
        function updateBreakTimerDisplay() {
            const minutes = Math.floor(currentBreakTime / 60);
            const seconds = currentBreakTime % 60;
            breakTimerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            if (currentBreakTime <= 60) {
                breakTimerDisplay.style.color = '#e74c3c'; // Red for last minute
            } else {
                 breakTimerDisplay.style.color = '#2c3e50';
            }
        }
        
        // 3. Continue exam logic (User clicked "Continue Exam" or time ran out or skipped)
        function continueExam() {
            clearInterval(breakTimerInterval);
            breakModal.style.display = 'none';
            breakSuggested = false;
            
            // Reset question start time to NOW
            questionStartTime = Date.now(); 
        }

        // 4. Skip Break logic
        skipBreakBtn.addEventListener('click', continueExam);

        // 5. Continue Exam button (Minimize break)
        continueExamBtn.addEventListener('click', function() {
            // Hide modal but let the timer run in the background
            breakModal.style.display = 'none';
        });

        // 6. Main Break Condition Check (Run on DOMContentLoaded)
        if (examStarted) {
             // Check Streak status immediately upon next question load (Page Load)
            if (streakStatus.incorrect >= ERROR_STREAK_THRESHOLD) {
                showBreakModal(
                    "Frustration Alert! 🛑",
                    "You've missed " + streakStatus.incorrect + " questions in a row. A short break is suggested to refresh your mind."
                );
            } else if (streakStatus.correct >= SUCCESS_STREAK_THRESHOLD) {
                showBreakModal(
                    "Excellent Work! 🎉",
                    "You've answered " + streakStatus.correct + " questions correctly! Take a short, well-deserved break to consolidate your learning."
                );
            }

            // Set interval for 'Stuck on Question' check
            setInterval(function() {
                if (breakSuggested) return;
                
                const now = Date.now();
                const timeOnCurrentQuestion = now - questionStartTime;
                
                // Condition 1: Stuck on question (5 minutes)
                if (timeOnCurrentQuestion >= QUESTION_STUCK_TIME_MS) {
                    showBreakModal(
                        "Time-Out Suggestion ⏳",
                        "You've been focused on this question for over 5 minutes. A quick break will help you see the answer clearly."
                    );
                }
            }, 5000); // Check every 5 seconds for efficiency
            
            // Puzzle reveal button logic
            revealAnswerBtn.addEventListener('click', function() {
                breakAnswer.style.display = 'block';
                this.style.display = 'none';
            });
        }
    </script>
</body>
</html>