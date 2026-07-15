<?php
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);
ini_set('max_input_time', 300);
session_start();
require_once 'db_config.php';
require_once 'ai_handler.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = null;
$learning_style = [];
$success_message = '';
$error_message = '';
$warning_message = '';
$last_uploaded_file = null;
$redirect_to_settings = false;

try {
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!function_exists('getUserLearningStyle')) {
        throw new Exception("Function getUserLearningStyle is not available. Please check ai_handler.php file.");
    }

    $learning_style = getUserLearningStyle($user_id); 

    $stmt = $pdo->prepare("SELECT id, file_name, created_at FROM ai_generated_questions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $last_uploaded_file = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error_message = "Error loading initial data: " . $e->getMessage();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['study_file'])) {
    $file = $_FILES['study_file'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $allowed_extensions = ['txt', 'pdf', 'doc', 'docx'];

    if ($file_error !== 0) {
        $error_message = "Error uploading file. Code: " . $file_error;
    } elseif (!in_array($file_ext, $allowed_extensions)) {
        $error_message = "Invalid file type. Supported formats: TXT, PDF, DOC, DOCX (10MB limit) - AI processing for 50-150 questions";
    } elseif ($file_size > 10 * 1024 * 1024) {
        $error_message = "File too large. Maximum size: 10MB to preserve API tokens. Please use smaller files or split large content.";
    } else {
        try {
            $memory_limit = ini_get('memory_limit');
            $memory_usage = memory_get_usage();
            error_log("Starting file processing - Memory limit: {$memory_limit}, Current usage: " . round($memory_usage / 1024 / 1024, 2) . " MB");
            
            if ($memory_usage > 400 * 1024 * 1024) {
                $error_message = "System memory is low. Please try again later or use a smaller file.";
            } else {
            
            $content = '';
            
            if ($file_ext === 'txt') {
                $txt_size = filesize($file_tmp);
                if ($txt_size > 20 * 1024 * 1024) {
                    $content = file_get_contents($file_tmp, false, null, 0, 20 * 1024 * 1024);
                    $warning_message .= " Large TXT file truncated to 20MB for processing.";
                } else {
                    $content = file_get_contents($file_tmp);
                }
                $content = mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content));
            } elseif ($file_ext === 'pdf') {
                $warning_message = "Processing PDF file... Please ensure the content is readable (not scanned images) for the best results.";
                $content = extractTextFromPDF($file_tmp);
            } elseif ($file_ext === 'doc' || $file_ext === 'docx') {
                $warning_message = "Processing {$file_ext} file... Text extraction may vary in quality. TXT or PDF are recommended.";
                $content = extractTextFromDoc($file_tmp, $file_ext);
            } else {
                $content = extractTextFallback(file_get_contents($file_tmp));
            }
            
            if (empty($content) || strlen(trim($content ?? '')) < 30) {
                $content = extractTextFallback(file_get_contents($file_tmp));
                $warning_message .= " Using basic text extraction as primary failed. For better results, use TXT files.";
            }
            
            $memory_before = memory_get_usage();
            error_log("Memory usage before processing: " . round($memory_before / 1024 / 1024, 2) . " MB");
            
            $content = enhancedContentCleaning($content);
            
            $memory_after = memory_get_usage();
            error_log("Memory usage after cleaning: " . round($memory_after / 1024 / 1024, 2) . " MB");
            
            if (strlen($content) > 5 * 1024 * 1024) {
                $warning_message .= " Large file detected. Processing may take longer and some content may be truncated for optimal performance.";
            }
            
            if (empty($content) || strlen(trim($content ?? '')) < 30) {
                $error_message = "Unable to extract sufficient readable text from the file. Please ensure the file contains readable text content (Arabic or English).";
            } elseif (strlen($content) > 2 * 1024 * 1024) {
                $error_message = "Extracted content is too large (over 2MB). Please use a smaller file to preserve API tokens.";
            } else {
                $pages = splitContentIntoPagesOptimized($content);
                $estimated_pages = count($pages);
                
                $content_length = mb_strlen($content);
                $number_of_questions = calculateQuestionCount($content_length, $estimated_pages, $learning_style);
                
                $exam_time = calculateExamTime($number_of_questions);
                
                $questions_result = generateAIQuestions($content, $learning_style, $user_id, $file_name);

                if ($questions_result['success']) {
                    
                    $easy_count = 0;
                    $medium_count = 0;
                    $hard_count = 0;
                    foreach ($questions_result['questions'] as $q) {
                        if (strtolower($q['difficulty'] ?? '') === 'easy') $easy_count++;
                        if (strtolower($q['difficulty'] ?? '') === 'medium') $medium_count++;
                        if (strtolower($q['difficulty'] ?? '') === 'hard') $hard_count++;
                    }
                    
                    $total_generated = count($questions_result['questions']);

                    $_SESSION['exam_data'] = [
                        'title' => $file_name,
                        'total_time' => $exam_time,
                        'total_questions' => $total_generated,
                        'questions' => $questions_result['questions'],
                        'break_times' => [5, 7, 10],
                        'question_set_id' => $questions_result['saved_id'],
                        'difficulty_counts' => [
                            'Easy' => $easy_count,
                            'Medium' => $medium_count,
                            'Hard' => $hard_count,
                            'Total' => $total_generated
                        ],
                        'page_count' => $questions_result['page_count']
                    ];
                    
                    $success_message = "🎯 Successfully generated {$total_generated} questions from {$questions_result['page_count']} content units!  AI processing with comprehensive coverage. Allocated time: " . floor($exam_time/60) . " minutes. Please select your exam settings.";
                    $redirect_to_settings = true;
                    
                } else {
                    $error_message = "⚠️ AI service failed to generate questions: " . ($questions_result['error'] ?? 'Unknown error.');
                    
                    if (isset($questions_result['debug_info'])) {
                        $debug = $questions_result['debug_info'];
                        $error_message .= " <br><small>Debug: API Key Length: " . $debug['api_key_length'] . ", Model: " . $debug['model'] . "</small>";
                    }
                    
                    $error_message .= " <br><small>Please check: 1) File contains readable text (Arabic or English), 2) API connection is working. <a href='test_ai_connection.php' target='_blank'>Test AI Connection</a></small>";
                }
            }
            }

        } catch (Exception $e) {
            $error_message = "Internal Error during processing: " . $e->getMessage();
        }
    }
}

if ($redirect_to_settings) {
    header("Location: exam_settings.php");
    exit();
}

/**
 * 🆕 دالة محسنة لتقسيم المحتوى إلى صفحات (بدون حدود)
 */
function splitContentIntoPagesOptimized($content) {
    $pages = [];
    
    $content = trim($content);
    if (empty($content)) {
        return $pages;
    }
    
    $content_length = strlen($content);
    
    $pattern = '/(\b(?:Chapter|Unit|Section|الفصل|الباب|CHAPTER|UNIT|SECTION|\d+\.\s+[A-Z]|[IVXLCDM]+\.\s+[A-Z])\.?\s*\n)/i';
    
    $sections = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    
    if (count($sections) > 1) {
        $current_section = '';
        for ($i = 0; $i < count($sections); $i++) {
            if (preg_match($pattern, $sections[$i])) {
                if (!empty(trim($current_section)) && strlen(trim($current_section)) > 200) {
                    $pages[] = trim($current_section);
                }
                $current_section = '';
            }
            $current_section .= $sections[$i];
        }
        if (!empty(trim($current_section)) && strlen(trim($current_section)) > 200) {
            $pages[] = trim($current_section);
        }
    }
    
    if (empty($pages)) {
        $page_size = 2500;
        $num_pages = ceil($content_length / $page_size);
        
        for ($i = 0; $i < $num_pages; $i++) {
            $start = $i * $page_size;
            $page_content = substr($content, $start, $page_size);
            
            if ($page_content === false || empty($page_content)) {
                break;
            }
            
            $last_space = strrpos($page_content, ' ');
            if ($last_space !== false && $last_space > $page_size * 0.8 && $i < $num_pages - 1) {
                $page_content = substr($page_content, 0, $last_space);
            }
            
            $trimmed_content = trim($page_content);
            if (strlen($trimmed_content) > 200) {
                $pages[] = $trimmed_content;
            }
            
            unset($page_content, $trimmed_content);
            
            if (memory_get_usage() > 400 * 1024 * 1024) {
                error_log("Memory limit approaching, stopping content processing");
                break;
            }
        }
    }
    
    if (empty($pages)) {
        $pages[] = substr($content, 0, 3000);
    }
    
    return $pages;
}


/**
 * Improved PDF text extraction
 */
function extractTextFromPDF($file_path) {
    $content = '';
    
    $content = extractPDFWithShell($file_path);
    
    if (empty($content)) {
        $content = extractPDFBasic($file_path);
    }
    
    return $content;
}

/**
 * Try to extract PDF using system commands (pdftotext)
 */
function extractPDFWithShell($file_path) {
    $content = '';
    
    if (function_exists('shell_exec')) {
        $command = "which pdftotext";
        $output = shell_exec($command);
        
        if (!empty($output)) {
            $temp_output = tempnam(sys_get_temp_dir(), 'pdf_text');
            $command = "pdftotext '{$file_path}' '{$temp_output}' 2>&1";
            shell_exec($command);
            
            if (file_exists($temp_output)) {
                $file_size = filesize($temp_output);
                if ($file_size > 10 * 1024 * 1024) {
                    error_log("PDF extracted text too large ({$file_size} bytes), truncating");
                    $content = file_get_contents($temp_output, false, null, 0, 10 * 1024 * 1024);
                } else {
                    $content = file_get_contents($temp_output);
                }
                unlink($temp_output);
            }
        }
    }
    
    return $content;
}


function extractPDFBasic($file_path) {
    $content = '';
    
    $file_size = filesize($file_path);
    if ($file_size > 20 * 1024 * 1024) {
        error_log("PDF file too large ({$file_size} bytes), skipping");
        return "PDF file too large for processing. Please use a smaller file or convert to TXT format.";
    }
    
    $raw_content = @file_get_contents($file_path, false, null, 0, 10 * 1024 * 1024);
    if ($raw_content === false) {
        return "Unable to read PDF file. Please try converting to TXT format.";
    }
    
    $content = preg_replace('/%PDF-\d\.\d.*?stream/s', '', $raw_content ?? '');
    $content = preg_replace('/endstream.*?endobj/s', '', $content ?? '');
    
    unset($raw_content);
    
    preg_match_all('/\((.*?)\)/', $content, $matches);
    if (!empty($matches[1])) {
        $text_parts = [];
        foreach ($matches[1] as $match) {
            $clean_text = preg_replace('/[\x{0600}-\x{06FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', ' ', $match ?? '');
            $clean_text = preg_replace('/[^\x20-\x7E]/u', ' ', $clean_text ?? '');
            $clean_text = trim($clean_text ?? '');
            if (strlen($clean_text) > 5) {
                $text_parts[] = $clean_text;
            }
        }
        $content = implode(' ', $text_parts);
    }
    
    return $content;
}

/**
 * Extract text from DOC/DOCX files
 */
function extractTextFromDoc($file_path, $file_ext) {
    $content = '';
    
    if ($file_ext === 'docx') {
        $zip = new ZipArchive;
        if ($zip->open($file_path) === TRUE) {
            if (($index = $zip->locateName('word/document.xml')) !== FALSE) {
                $content = $zip->getFromIndex($index);
                $content = strip_tags($content);
            }
            $zip->close();
        }
    }
    
    if (empty($content) || $file_ext === 'doc') {
        $file_size = filesize($file_path);
        if ($file_size > 20 * 1024 * 1024) {
            return "DOC file too large for processing. Please convert to TXT format.";
        }
        
        $raw_content = @file_get_contents($file_path, false, null, 0, 10 * 1024 * 1024);
        if ($raw_content !== false) {
            $content = extractTextFallback($raw_content);
            unset($raw_content);
        }
    }
    
    return $content;
}

function extractTextFallback($raw_content) {
    $content = $raw_content;
    
    // Keep Arabic and English text, remove only control characters and special symbols
    // Arabic: \x{0600}-\x{06FF}, \x{FB50}-\x{FDFF}, \x{FE70}-\x{FEFF}
    // English: \x20-\x7E
    $content = preg_replace('/[^\x{0600}-\x{06FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}\x20-\x7E\n\r\t]/u', ' ', $content ?? '');
    
    $content = preg_replace('/\s+/', ' ', $content ?? '');
    
    $content = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $content ?? '');
    
    return trim($content ?? '');
}


function enhancedContentCleaning($content) {
    if (empty($content)) return '';
    
    $content_size = strlen($content);
    if ($content_size > 10 * 1024 * 1024) {
        error_log("Content too large ({$content_size} bytes), truncating to 10MB");
        $content = substr($content, 0, 10 * 1024 * 1024);
    }
    
    $patterns = [
        '/Page \d+ of \d+/i' => '',
        '/\d{1,2}\/\d{1,2}\/\d{2,4}/' => '',
        '/Copyright \d{4}/i' => '',
        '/All rights reserved/i' => '',
        '/www\.[^\s]+/i' => '',
        '/https?:\/\/[^\s]+/i' => '',
        // Keep Arabic (\x{0600}-\x{06FF}, \x{FB50}-\x{FDFF}, \x{FE70}-\x{FEFF}) and English (\x20-\x7E)
        '/[^\x{0600}-\x{06FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}\x20-\x7E\n\r\t\.\,\;\:\?\!]/u' => ' ',
        '/\s+/' => ' ',
        '/\n{3,}/' => "\n\n"
    ];
    
    foreach ($patterns as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content ?? '');
        
        if (memory_get_usage() > 450 * 1024 * 1024) {
            error_log("Memory limit approaching during cleaning, stopping");
            break;
        }
    }
    
    $lines = explode("\n", $content);
    $clean_lines = [];
    $processed_lines = 0;
    $max_lines = 5000;
    
    foreach ($lines as $line) {
        if ($processed_lines >= $max_lines) {
            error_log("Reached maximum line limit ({$max_lines}), stopping processing");
            break;
        }
        
        $line = trim($line ?? '');
        
        if (strlen($line) < 15) continue;
        if (preg_match('/^(page|chapter|section|figure|table)\s*\d*$/i', $line)) continue;
        if (preg_match('/^\d+\s*$/', $line)) continue;
        if (preg_match('/^[\.]{3,}/', $line)) continue;
        if (preg_match('/^[-_=]{3,}/', $line)) continue;
        if (preg_match('/^[\d\s\.\-]+$/', $line)) continue;
        
        $clean_lines[] = $line;
        $processed_lines++;
        
        if ($processed_lines % 1000 === 0) {
            if (memory_get_usage() > 450 * 1024 * 1024) {
                error_log("Memory limit approaching at line {$processed_lines}, stopping");
                break;
            }
        }
    }
    
    unset($lines);
    
    $result = implode("\n", $clean_lines);
    unset($clean_lines);
    
    return trim($result);
}

/**
 * Legacy function - kept for compatibility
 */
function cleanExtractedContent($content) {
    return enhancedContentCleaning($content);
}

/**
 * Smart content truncation that preserves important content
 */
function smartContentTruncation($content, $max_length) {
    if (empty($content)) return '';
    
    if (mb_strlen($content) <= $max_length) {
        return $content;
    }
    
    $break_points = [
        mb_strrpos($content, '.', $max_length - 1000),
        mb_strrpos($content, "\n\n", $max_length - 1000),
        mb_strrpos($content, ' ', $max_length - 100)
    ];
    
    $best_break = max(array_filter($break_points));
    
    if ($best_break > $max_length * 0.5) {
        return mb_substr($content, 0, $best_break + 1);
    }
    
    $truncated = mb_substr($content, 0, $max_length);
    $last_space = mb_strrpos($truncated, ' ');
    
    if ($last_space > $max_length * 0.8) {
        return mb_substr($content, 0, $last_space) . '...';
    }
    
    return $truncated . '...';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Learning - IGM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <i class="fas fa-brain fa-spin fa-4x" style="color: var(--primary-blue);"></i>
        <p> AI processing your entire file...</p>
        <p style="margin-top: 5px; font-size: 0.9rem; opacity: 0.8;">Generating 50+ personalized questions with comprehensive coverage</p>
        <p style="margin-top: 3px; font-size: 0.8rem; opacity: 0.7;">Processing may take longer for comprehensive analysis</p>
    </div>

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
            <li class="nav-item active">
                <a href="upload_file.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Start Learning</span>
                </a>
                <li class="nav-item">
                <a href="study_materials.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Study Materials</span>
                </a>
            </li>
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

    <div class="main-content" id="mainContent">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">Start AI-Powered Learning</h1>
                <p class="page-subtitle">Upload your study material and let the AI generate a customized exam for you.</p>
            </div>

            <?php if ($success_message && !$redirect_to_settings): ?>
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
            
            <?php if ($warning_message): ?>
                <div class="message warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $warning_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="page-based-info">
                <h4><i class="fas fa-cogs"></i> Processing Capabilities</h4>
                <p>• <strong>File size limit: 10MB</strong> - optimized for token efficiency</p>
                <p>• <strong>Intelligent content filtering</strong> - removes non-educational content</p>
                <p>• <strong>Arabic text filtering</strong> - focuses on English content only</p>
                <p>• <strong>Smart page detection</strong> - automatically identifies meaningful content sections</p>
                <p>• <strong>50-150 questions generated</strong> - balanced for quality and efficiency</p>
            </div>
            
            <div class="main-layout">
                <div class="upload-card">
                    <h2>Upload Study Material</h2>
                    
                    <div class="file-processing-info">
                        <h4><i class="fas fa-info-circle"></i> Important Notes for File Upload:</h4>
                        <ul>
                            <li><strong>PDF files</strong> require **readable text** (not scanned images) and are recommended.</li>
                            <li>**TXT files** also work well for accurate text extraction.</li>
                            <li>**DOC/DOCX files** should contain extractable text (extraction quality may vary).</li>
                            <li>Minimum 100 characters of text required</li>
                            <li><strong>File size limit: 10MB maximum</strong> to preserve API tokens</li>
                        </ul>
                    </div>
                    
                    <div class="page-based-info">
                        <h4><i class="fas fa-robot"></i> AI Question Generation:</h4>
                        <p>• The AI will <strong>analyze your ENTIRE file</strong> and intelligently divide it into content units</p>
                        <p>• Generates <strong>50-150 questions</strong> optimized for token efficiency</p>
                        <p>• For each content unit: <strong>5-6 questions</strong> (Easy, Medium, Hard distribution)</p>
                        <p>• <strong>Automatically skips</strong> table of contents, chapter titles, and non-content pages</p>
                        <p>• <strong>Ignores Arabic text</strong> and focuses on English content only</p>
                        <p>• Questions are <strong>personalized</strong> based on your learning style</p>
                        <p>• Ensures <strong>comprehensive coverage</strong> of ALL meaningful content</p>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <div class="file-drop-area" id="fileDropArea">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Drag & Drop your file here, or Click to select</p>
                            <small>Supported formats: TXT, PDF, DOC, DOCX AI processing for 50+ questions</small>
                            <input type="file" name="study_file" id="studyFileInput" class="hidden-file-input" accept=".txt,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
                        </div>
                        
                        <div id="fileNameDisplay" style="margin-top: 10px; font-weight: bold; color: var(--primary-blue);"></div>
                        <div id="fileInfoDisplay" style="margin-top: 5px; font-size: 14px; color: var(--text-light);"></div>

                        <button type="submit" class="submit-btn" id="submitBtn">
                            <i class="fas fa-brain"></i>
                            Generate AI Exam (50+ Questions)
                        </button>
                    </form>

                    <?php if ($last_uploaded_file): ?>
                    <div class="last-file-info">
                        <p>Last Material Studied:</p>
                        <p>File: <span><?php echo htmlspecialchars($last_uploaded_file['file_name']); ?></span></p>
                        <p>Date: <span><?php echo date('M j, Y H:i', strtotime($last_uploaded_file['created_at'])); ?></span></p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="info-card">
                    <h3>Your Learning Profile</h3>
                    
                    <div class="style-item">
                        <strong>Planning Style</strong>
                        <span><?php echo htmlspecialchars(getEnglishStyleName($learning_style['planning_style'] ?? 'Organized')); ?></span>
                    </div>

                    <div class="style-item">
                        <strong>Problem Solving</strong>
                        <span><?php echo htmlspecialchars(getEnglishStyleName($learning_style['problem_solving'] ?? 'Analytical')); ?></span>
                    </div>

                    <div class="style-item">
                        <strong>Test Preference</strong>
                        <span><?php echo htmlspecialchars(getEnglishStyleName($learning_style['test_preference'] ?? 'Mixed')); ?></span>
                    </div>
                    
                    <div class="style-item">
                        <strong>Learning Type</strong>
                        <span><?php echo htmlspecialchars(getEnglishStyleName($learning_style['learning_type'] ?? 'Balanced')); ?></span>
                    </div>
                    
                    <p style="font-size: 0.9rem; margin-top: 20px; color: var(--text-light);">
                        Your profile helps the AI generate questions tailored to your needs. 
                        You can update it in the Study Behavior Questions section.
                    </p>
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
        const fileDropArea = document.getElementById('fileDropArea');
        const studyFileInput = document.getElementById('studyFileInput');
        const fileNameDisplay = document.getElementById('fileNameDisplay');
        const fileInfoDisplay = document.getElementById('fileInfoDisplay');
        const uploadForm = document.getElementById('uploadForm');
        const loadingOverlay = document.getElementById('loadingOverlay');

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

        const currentPage = window.location.pathname.split('/').pop();
        document.querySelectorAll('.nav-item a').forEach(link => {
            if(link.getAttribute('href') === currentPage) {
                link.parentElement.classList.add('active');
            }
        });
        
        fileDropArea.addEventListener('click', () => {
            studyFileInput.click();
        });

        studyFileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                fileNameDisplay.textContent = 'Selected File: ' + file.name;
                
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                const fileType = file.name.split('.').pop().toUpperCase();
                fileInfoDisplay.textContent = `Size: ${fileSize} MB | Type: ${fileType}`;
                
                if (file.size > 5242880) {
                    fileInfoDisplay.innerHTML += ` <span style="color: #2e7d32;">(Large file - comprehensive processing)</span>`;
                }
                
            } else {
                fileNameDisplay.textContent = '';
                fileInfoDisplay.textContent = '';
            }
        });

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileDropArea.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileDropArea.addEventListener(eventName, () => fileDropArea.classList.add('dragover'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileDropArea.addEventListener(eventName, () => fileDropArea.classList.remove('dragover'), false);
        });

        fileDropArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            studyFileInput.files = files;
            
            if (files.length > 0) {
                const file = files[0];
                fileNameDisplay.textContent = 'Selected File: ' + file.name;
                
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                const fileType = file.name.split('.').pop().toUpperCase();
                fileInfoDisplay.textContent = `Size: ${fileSize} MB | Type: ${fileType}`;
                
                if (file.size > 5242880) {
                    fileInfoDisplay.innerHTML += ` <span style="color: #2e7d32;">(Large file - comprehensive processing)</span>`;
                }
            } else {
                fileNameDisplay.textContent = '';
                fileInfoDisplay.textContent = '';
            }
        }

        uploadForm.addEventListener('submit', function(e) {
            if (studyFileInput.files.length === 0) {
                e.preventDefault();
                alert('Please select a file first.');
                return;
            }
            
            const file = studyFileInput.files[0];
            const fileExt = file.name.split('.').pop().toLowerCase();
            
            if (fileExt !== 'txt' && fileExt !== 'pdf') {
                if (!confirm('For best results, we recommend using TXT or PDF files. Complex formats like DOC and DOCX may not extract text perfectly. Continue anyway?')) {
                    e.preventDefault();
                    return;
                }
            }
            
            if (file.size > 10485760) {
                alert('Large file detected. AI processing will handle the entire content and generate comprehensive questions. Processing may take longer.');
            }
            
            loadingOverlay.style.display = 'flex';
        });

    </script>
</body>
</html>