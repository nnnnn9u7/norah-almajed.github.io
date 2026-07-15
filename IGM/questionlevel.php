<?php
session_start();
require_once 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data from database
$user_id = $_SESSION['user_id'];
$user = null;
$answers = [];
$success_message = '';
$error_message = '';
$questions = [];

try {
    // Verify user exists
    $stmt = $pdo->prepare("SELECT full_name, username, email, phone FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        session_destroy();
        header("Location: login.php");
        exit();
    }

    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_answers'])) {
        
        // Create table if it doesn't exist
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `study_behavior_answers` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL,
                `question_number` INT NOT NULL,
                `question_text` TEXT NOT NULL,
                `answer_value` VARCHAR(100) NOT NULL,
                `answer_text` TEXT NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `unique_user_question` (`user_id`, `question_number`)
            )");
        } catch (Exception $e) {
            // Ignore error if table already exists
        }

        $pdo->beginTransaction();
        
        try {
            // Delete old answers for this user
            $delete_stmt = $pdo->prepare("DELETE FROM study_behavior_answers WHERE user_id = ?");
            $delete_stmt->execute([$user_id]);
            
            // Insert new answers
            $insert_stmt = $pdo->prepare("INSERT INTO study_behavior_answers (user_id, question_number, question_text, answer_value, answer_text) VALUES (?, ?, ?, ?, ?)");
            
            $has_answers = false;
            if (isset($_POST['answers'])) {
                foreach ($_POST['answers'] as $question_number => $answer_data) {
                    if (!empty(trim($answer_data['value']))) {
                        $question_text = getQuestionText($question_number);
                        $insert_stmt->execute([
                            $user_id, 
                            $question_number, 
                            $question_text,
                            $answer_data['value'], 
                            $answer_data['text']
                        ]);
                        $has_answers = true;
                    }
                }
            }
            
            $pdo->commit();
            
            if ($has_answers) {
                $success_message = "Your answers have been saved successfully!";
            } else {
                $success_message = "You haven't selected any answers.";
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "An error occurred while saving your answers: " . $e->getMessage();
        }
    }

    // Load previous answers
    try {
        $stmt = $pdo->prepare("SELECT question_number, answer_value, answer_text FROM study_behavior_answers WHERE user_id = ? ORDER BY question_number");
        $stmt->execute([$user_id]);
        $saved_answers = $stmt->fetchAll();

        foreach ($saved_answers as $answer) {
            $answers[$answer['question_number']] = [
                'value' => $answer['answer_value'],
                'text' => $answer['answer_text']
            ];
        }
    } catch (Exception $e) {
        // If table doesn't exist, ignore error
        error_log("Error loading answers: " . $e->getMessage());
    }

} catch (Exception $e) {
    $error_message = "A system error occurred: " . $e->getMessage();
}

// Function to get question text
function getQuestionText($question_number) {
    $questions = [
        1 => "How do you plan a typical study day?",
        2 => "When facing a difficult subject",
        3 => "Preferred time to study:",
        4 => "What is your biggest challenge in studying?",
        5 => "When you forget a piece of information",
        6 => "In the final exam, I believe what best measures my understanding is",
        7 => "When I face an essay question",
        8 => "In multiple-choice questions",
        9 => "When preparing for an exam, I feel most comfortable when"
    ];
    
    return $questions[$question_number] ?? "Question $question_number";
}

// Define questions and options
$questions = [
    1 => [
        'text' => "How do you plan a typical study day?",
        'options' => [
            ['value' => 'precise_timetable', 'text' => 'I create a precise timetable', 'color' => 'color-1'],
            ['value' => 'based_on_feeling', 'text' => 'I study based on how I feel', 'color' => 'color-2'],
            ['value' => 'with_classmates', 'text' => 'I study with my classmates at specific times', 'color' => 'color-3'],
            ['value' => 'mood_circumstances', 'text' => 'I leave it to my mood and circumstances', 'color' => 'color-4']
        ]
    ],
    2 => [
        'text' => "When facing a difficult subject",
        'options' => [
            ['value' => 'break_into_parts', 'text' => 'I break it into smaller parts', 'color' => 'color-1'],
            ['value' => 'additional_resources', 'text' => 'I look for additional resources', 'color' => 'color-2'],
            ['value' => 'ask_help', 'text' => 'I ask for help immediately', 'color' => 'color-3'],
            ['value' => 'postpone', 'text' => 'I postpone it for later', 'color' => 'color-4']
        ]
    ],
    3 => [
        'text' => "Preferred time to study:",
        'options' => [
            ['value' => 'early_morning', 'text' => 'Early morning', 'color' => 'color-1'],
            ['value' => 'afternoon', 'text' => 'Afternoon', 'color' => 'color-2'],
            ['value' => 'evening', 'text' => 'Evening', 'color' => 'color-3'],
            ['value' => 'night', 'text' => 'Night', 'color' => 'color-4']
        ]
    ],
    4 => [
        'text' => "What is your biggest challenge in studying?",
        'options' => [
            ['value' => 'difficulty_focusing', 'text' => 'Difficulty focusing', 'color' => 'color-1'],
            ['value' => 'procrastination', 'text' => 'Procrastination and delay', 'color' => 'color-2'],
            ['value' => 'difficulty_understanding', 'text' => 'Difficulty understanding some subjects', 'color' => 'color-3'],
            ['value' => 'poor_time_management', 'text' => 'Poor time management', 'color' => 'color-4']
        ]
    ],
    5 => [
        'text' => "When you forget a piece of information",
        'options' => [
            ['value' => 'remember_location', 'text' => 'I try to remember where it was in the book', 'color' => 'color-1'],
            ['value' => 'visualize_layout', 'text' => 'I visualize the page layout', 'color' => 'color-2'],
            ['value' => 'recall_voice', 'text' => 'I recall the teacher\'s voice explaining it', 'color' => 'color-3'],
            ['value' => 'solve_similar', 'text' => 'I solve a similar problem again', 'color' => 'color-4']
        ]
    ],
    6 => [
        'text' => "In the final exam, I believe what best measures my understanding is",
        'options' => [
            ['value' => 'essay_questions', 'text' => 'Essay questions where I can explain my ideas', 'color' => 'color-1'],
            ['value' => 'multiple_choice', 'text' => 'Multiple-choice questions', 'color' => 'color-2'],
            ['value' => 'mix_both', 'text' => 'A mix of both', 'color' => 'color-3']
        ]
    ],
    7 => [
        'text' => "When I face an essay question",
        'options' => [
            ['value' => 'confident_expression', 'text' => 'I feel confident because I can express myself freely', 'color' => 'color-1'],
            ['value' => 'anxious_organizing', 'text' => 'I feel anxious about organizing my ideas', 'color' => 'color-2'],
            ['value' => 'prefer_multiple_choice', 'text' => 'I\'d prefer it to be multiple choice', 'color' => 'color-3'],
            ['value' => 'handle_both_prefer_mc', 'text' => 'I can handle both types, but I prefer multiple choice', 'color' => 'color-4']
        ]
    ],
    8 => [
        'text' => "In multiple-choice questions",
        'options' => [
            ['value' => 'limit_thinking', 'text' => 'I feel they limit my thinking', 'color' => 'color-1'],
            ['value' => 'comfortable_clear', 'text' => 'I feel comfortable because the answers are clear and specific', 'color' => 'color-2'],
            ['value' => 'prefer_combined', 'text' => 'I prefer them when combined with essay questions', 'color' => 'color-3'],
            ['value' => 'doubt_correct', 'text' => 'I sometimes doubt which option is correct', 'color' => 'color-4']
        ]
    ],
    9 => [
        'text' => "When preparing for an exam, I feel most comfortable when",
        'options' => [
            ['value' => 'expect_essay', 'text' => 'I expect essay questions where I can express my understanding', 'color' => 'color-1'],
            ['value' => 'expect_objective', 'text' => 'I expect objective questions (multiple choice)', 'color' => 'color-2'],
            ['value' => 'practice_both', 'text' => 'I practice both types equally', 'color' => 'color-3'],
            ['value' => 'prefer_short_answers', 'text' => 'I prefer short questions with specific answers', 'color' => 'color-4']
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Study Behavior </title>
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                <img src="img/logo.png" alt="IGM Logo">
                <span class="logo-text">IGM</span>
            </div>
        </div>
        
        <div class="user-section">
            <div class="user-info" id="userInfo">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <span id="userDisplayName"><?php echo htmlspecialchars($user['full_name'] ?? 'User Name'); ?></span>
                <i class="fas fa-chevron-down" style="font-size: 12px;"></i>
            </div>
            
            <div class="user-dropdown" id="userDropdown">
                <a href="dashboard.php" class="dropdown-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dropdown</span>
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
            <li class="nav-item active">
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
                    <span>Contact Us</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="main-content-area">
            <div class="noise-pattern"></div>
            
            <h1 class="hero-title">Discover Your</h1>
            <h2 class="hero-subtitle">Study Behavior</h2>
            
            <?php if ($user): ?>
            <div class="user-welcome">
                Hello <?php echo htmlspecialchars($user['full_name']); ?>! Choose the answers that best express you
            </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="message success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="message error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>

            <form method="POST" id="studyBehaviorForm">
                <div class="question-grid">
                    <?php foreach ($questions as $questionNumber => $question): ?>
                    <div class="question-section <?php echo $questionNumber == 9 ? 'full-width-section' : ''; ?>">
                        <h3 class="question-title"><?php echo $question['text']; ?></h3>
                        <div class="options-grid">
                            <?php foreach ($question['options'] as $option): ?>
                            <button type="button" class="option-button <?php echo $option['color']; ?>" 
                                    data-question="<?php echo $questionNumber; ?>" 
                                    data-value="<?php echo $option['value']; ?>"
                                    data-text="<?php echo htmlspecialchars($option['text']); ?>">
                                <?php echo $option['text']; ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="answers[<?php echo $questionNumber; ?>][value]" 
                               id="answer_value_<?php echo $questionNumber; ?>" 
                               value="<?php echo isset($answers[$questionNumber]['value']) ? htmlspecialchars($answers[$questionNumber]['value']) : ''; ?>">
                        <input type="hidden" name="answers[<?php echo $questionNumber; ?>][text]" 
                               id="answer_text_<?php echo $questionNumber; ?>" 
                               value="<?php echo isset($answers[$questionNumber]['text']) ? htmlspecialchars($answers[$questionNumber]['text']) : ''; ?>">
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="submit-container">
                    <button type="submit" name="submit_answers" class="submit-button">Save Answers</button>
                </div>
            </form>
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

        // Logout functionality
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if(confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        });

        // Active navigation item
        const currentPage = window.location.pathname.split('/').pop();
        document.querySelectorAll('.nav-item a').forEach(link => {
            if(link.getAttribute('href') === currentPage) {
                link.parentElement.classList.add('active');
            }
        });

        // Track user progress
        let answeredQuestions = 0;
        const totalQuestions = 9;

        // Update progress bar
        function updateProgress() {
            const progress = (answeredQuestions / totalQuestions) * 100;
            document.getElementById('progressFill').style.width = progress + '%';
        }

        // Handle option selection
        document.querySelectorAll('.option-button').forEach(button => {
            button.addEventListener('click', function() {
                const questionNumber = this.getAttribute('data-question');
                const answerValue = this.getAttribute('data-value');
                const answerText = this.getAttribute('data-text');
                
                // Remove selected class from siblings
                const siblings = this.parentElement.querySelectorAll('.option-button');
                siblings.forEach(sib => sib.classList.remove('selected'));
                
                // Add selected class to clicked button
                this.classList.add('selected');
                
                // Update hidden inputs
                document.getElementById(`answer_value_${questionNumber}`).value = answerValue;
                document.getElementById(`answer_text_${questionNumber}`).value = answerText;
                
                // Check if this is a new answer
                const questionSection = this.closest('.question-section');
                if (!questionSection.classList.contains('answered')) {
                    questionSection.classList.add('answered');
                    answeredQuestions++;
                    updateProgress();
                }
            });
        });

        // Restore saved answers on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedAnswers = <?php echo json_encode($answers); ?>;
            
            Object.keys(savedAnswers).forEach(questionNumber => {
                const answer = savedAnswers[questionNumber];
                if (answer && answer.value) {
                    const buttons = document.querySelectorAll(`[data-question="${questionNumber}"]`);
                    buttons.forEach(button => {
                        if (button.getAttribute('data-value') === answer.value) {
                            button.classList.add('selected');
                            document.getElementById(`answer_value_${questionNumber}`).value = answer.value;
                            document.getElementById(`answer_text_${questionNumber}`).value = answer.text;
                            
                            const questionSection = button.closest('.question-section');
                            if (!questionSection.classList.contains('answered')) {
                                questionSection.classList.add('answered');
                                answeredQuestions++;
                            }
                        }
                    });
                }
            });
            updateProgress();
        });
    </script>
</body>
</html>