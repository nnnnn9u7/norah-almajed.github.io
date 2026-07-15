<?php
require_once 'db_config.php';

class AIQuestionGenerator {
    
    private $api_key;
    private $api_url;
    private $model;
    
    public function __construct() {
        $this->api_key = EXTERNAL_SERVICE_TOKEN;
        $this->api_url = EXTERNAL_API_ENDPOINT;
        $this->model = AI_MODEL_VERSION;
    }
    
    public function generateQuestions($content, $learning_style, $user_id, $file_name = 'uploaded_file.txt') {
        try {
            if (empty($this->api_key)) {
                throw new Exception('Service configuration error: API key is missing.');
            }
            
            $original_length = strlen($content);
            $content = $this->cleanContent($content);
            $cleaned_length = strlen($content);
            
            if (empty($content) || strlen($content) < 50) {
                throw new Exception("Content is too short after cleaning ({$cleaned_length} chars). Original was {$original_length} chars. Please upload a file with more readable text content.");
            }
            
            $content_analysis = $this->analyzeContentStructure($content);
            
            $pages = $this->splitContentIntoPagesOptimized($content);
            $content_units = count($pages);
            
            $number_of_questions = $this->calculateOptimalQuestionCount($content, $content_units);
            
            $smart_questions = $this->generateSmartQuestions($content_analysis, $pages, $number_of_questions);
            
            $prompt = $this->buildEnhancedPrompt($pages, $learning_style, $content_units, $number_of_questions, $content_analysis, $smart_questions);
            
            $questions = $this->callExternalAPI($prompt, $number_of_questions, $content_analysis);
            
            if (empty($questions)) {
                throw new Exception('AI response failed to generate valid questions. The file content might be too short or complex.');
            }
            
            $questions = $this->ensureDifficultyDistribution($questions);
            
            $hard_count = 0;
            foreach ($questions as $q) {
                if (strtolower($q['difficulty'] ?? '') === 'hard') $hard_count++;
            }
            
            if ($hard_count == 0) {
                $questions = $this->addFallbackHardQuestions($questions, $content_analysis, $number_of_questions);
            }
            
            $saved_id = $this->saveQuestionsToDB($user_id, $file_name, $content, $learning_style, $questions);
            
            return [
                'success' => true,
                'questions' => $questions,
                'saved_id' => $saved_id,
                'ai_model' => 'AI Assistant Pro',
                'question_count' => count($questions),
                'page_count' => $content_units
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => "AI Service failed: " . $e->getMessage(),
                'debug_info' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'api_key_length' => strlen($this->api_key ?? ''),
                    'api_url' => $this->api_url ?? 'Not set',
                    'model' => $this->model ?? 'Not set'
                ]
            ];
        }
    }
    

    private function calculateOptimalQuestionCount($content, $page_count) {
        $content_length = strlen($content);
        
        $length_based = min(100, max(50, floor($content_length / 400)));
        $page_based = $page_count * 4;
        
        $optimal_count = max(50, max($length_based, $page_based));
        
        $final_count = min(150, $optimal_count);
        
        return $final_count;
    }
    

    private function splitContentIntoPagesOptimized($content) {
        $pages = [];
        
        $content = trim($content);
        if (empty($content)) {
            return $pages;
        }
        
        $content_length = strlen($content);
        
        if ($content_length <= 2000) {
            $paragraphs = preg_split('/\n\s*\n/', $content);
            if (count($paragraphs) > 1) {
                foreach ($paragraphs as $para) {
                    if (strlen(trim($para)) > 100) {
                        $pages[] = trim($para);
                    }
                }
            } else {
                $pages[] = $content;
            }
            
            return $pages;
        }
        
        $heading_patterns = [
            '/(\b(?:Chapter|Unit|Section|CHAPTER|UNIT|SECTION)\s+\d+[\.:\-]?\s*\n)/i',
            '/(\b(?:Chapter|Unit|Section|CHAPTER|UNIT|SECTION)\s+[IVXLCDM]+[\.:\-]?\s*\n)/i',
            '/(\n\d+\.\s+[A-Z][^\n]{10,100}\n)/',
            '/(\n[IVXLCDM]+\.\s+[A-Z][^\n]{10,100}\n)/',
            '/(\n[A-Z][A-Z\s]{10,100}\n)/',
            '/(\n\*{3,}.+\*{3,}\n)/',
            '/(\n-{3,}.+-{3,}\n)/',
            '/(\n#{1,6}\s+.+\n)/',
            '/(\n\s*[A-Z][^\.!?]{10,80}:\s*\n)/',
            '/(\n\s*Part\s+[IVXLCDM0-9]+[\.:]*\s*\n)/i',
            '/(\n\s*Lesson\s+\d+[\.:]*\s*\n)/i',
        ];
        
        $best_split_content = [];
        $best_split_method = "length_fallback";
        
        foreach ($heading_patterns as $pattern_index => $pattern) {
            $sections = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            
            if (count($sections) > 2) {
                $valid_sections = 0;
                $current_section = "";
                
                foreach ($sections as $section) {
                    $is_heading = preg_match($pattern, $section);
                    
                    if ($is_heading || strlen($current_section) > 1200) {
                        if (strlen(trim($current_section)) > 150) {
                            $best_split_content[] = trim($current_section);
                            $valid_sections++;
                        }
                        $current_section = $section;
                    } else {
                        $current_section .= $section;
                    }
                }
                
                if (!empty(trim($current_section)) && strlen(trim($current_section)) > 150) {
                    $best_split_content[] = trim($current_section);
                    $valid_sections++;
                }
                
                if ($valid_sections >= 2) {
                    $best_split_method = "pattern_" . $pattern_index;
                    break;
                }
            }
            $best_split_content = [];
        }
        
        if (!empty($best_split_content)) {
            $pages = $this->filterInitialPages($best_split_content);
        }
        
        if (empty($pages)) {
            
            $paragraphs = preg_split('/\n\s*\n/', $content);
            $filtered_paragraphs = $this->filterContentParagraphs($paragraphs);
            
            $current_page = "";
            $page_size_target = 800;
            $page_size_min = 300;
            $page_size_max = 1500;
            
            foreach ($filtered_paragraphs as $paragraph) {
                $paragraph = trim($paragraph);
                if (empty($paragraph)) continue;
                
                $current_length = strlen($current_page);
                $paragraph_length = strlen($paragraph);
                
                if ($current_length > $page_size_min && 
                    ($current_length + $paragraph_length) > $page_size_target) {
                    $pages[] = $current_page;
                    $current_page = $paragraph;
                } else {
                    if (!empty($current_page)) {
                        $current_page .= "\n\n";
                    }
                    $current_page .= $paragraph;
                }
                
                if (strlen($current_page) > $page_size_max) {
                    $pages[] = substr($current_page, 0, $page_size_max);
                    $current_page = substr($current_page, $page_size_max);
                }
            }
            

            if (!empty(trim($current_page)) && strlen(trim($current_page)) > 150) {
                $pages[] = trim($current_page);
            }
        }
        
        if (empty($pages) || count($pages) < 5) {
            
            $sentences = preg_split('/(?<=[.!?])\s+/', $content);
            $filtered_sentences = $this->filterContentSentences($sentences);
            
            $current_page = "";
            $page_size_target = 600;
            
            foreach ($filtered_sentences as $sentence) {
                $sentence = trim($sentence);
                if (empty($sentence)) continue;
                
                $current_length = strlen($current_page);
                $sentence_length = strlen($sentence);
                
                if ($current_length + $sentence_length > $page_size_target && $current_length > 200) {
                    $pages[] = $current_page;
                    $current_page = $sentence;
                } else {
                    if (!empty($current_page)) {
                        $current_page .= " ";
                    }
                    $current_page .= $sentence;
                }
            }
            
            if (!empty(trim($current_page)) && strlen(trim($current_page)) > 150) {
                $pages[] = trim($current_page);
            }
        }
        
        if (empty($pages)) {
            $page_size = 600;
            $overlap = 100;
            
            $num_pages = ceil($content_length / $page_size);
            
            for ($i = 0; $i < $num_pages; $i++) {
                $start = $i * $page_size;
                
                $page_content = substr($content, $start, $page_size + $overlap);
                $last_good_break = max(
                    strrpos($page_content, "\n\n"),
                    strrpos($page_content, ". "),
                    strrpos($page_content, "! "),
                    strrpos($page_content, "? ")
                );
                
                if ($last_good_break > $page_size * 0.5 && $i < $num_pages - 1) {
                    $page_content = substr($page_content, 0, $last_good_break + 2);
                } else {
                    $page_content = substr($page_content, 0, $page_size);
                }
                
                if (strlen(trim($page_content)) > 100) {
                    $pages[] = trim($page_content);
                }
            }
        }
        
        if (empty($pages)) {
            $pages[] = substr($content, 0, min(3000, $content_length));
        }
        
        if (count($pages) < 10) {
            $new_pages = [];
            foreach ($pages as $page) {
                if (strlen($page) > 1000) {
                    $chunks = ceil(strlen($page) / 600);
                    for ($i = 0; $i < $chunks; $i++) {
                        $start = $i * 600;
                        $chunk = substr($page, $start, 600);
                        
                        if ($i < $chunks - 1) {
                            $break_point = strrpos($chunk, '. ');
                            if ($break_point !== false && $break_point > 300) {
                                $chunk = substr($chunk, 0, $break_point + 2);
                            }
                        }
                        
                        if (strlen(trim($chunk)) > 100) {
                            $new_pages[] = trim($chunk);
                        }
                    }
                } else {
                    $new_pages[] = $page;
                }
            }
            $pages = $new_pages;
        }
        
        return $pages;
    }
    
    /**
     * Filter out initial pages that are likely table of contents, chapter titles, etc.
     */
    private function filterInitialPages($pages) {
        $filtered_pages = [];
        $skip_patterns = [
            '/^\s*(table\s+of\s+contents|contents|index)\s*$/i',
            '/^\s*(chapter\s+\d+|unit\s+\d+|section\s+\d+)\s*$/i',
            '/^\s*(introduction|preface|foreword|acknowledgments)\s*$/i',
            '/^\s*[\d\s\.]+$/', // Pages with only numbers and dots
            '/^.{0,50}$/', // Very short pages (likely titles)
        ];
        
        foreach ($pages as $index => $page) {
            $page_trimmed = trim($page);
            $should_skip = false;
            
            if ($index < 3 && strlen($page_trimmed) < 200) {
                $should_skip = true;
            }
            
            foreach ($skip_patterns as $pattern) {
                if (preg_match($pattern, $page_trimmed)) {
                    $should_skip = true;
                    break;
                }
            }
            
            if (!$should_skip) {
                $filtered_pages[] = $page;
            }
        }
        
        return empty($filtered_pages) ? $pages : $filtered_pages;
    }
    
    /**
     * Filter content paragraphs to remove non-content elements
     */
    private function filterContentParagraphs($paragraphs) {
        $filtered = [];
        $skip_patterns = [
            '/^\s*(page|chapter|section)\s*\d*\s*$/i',
            '/^\s*(table\s+of\s+contents|index|bibliography|references)\s*$/i',
            '/^\s*[\d\s\.\-]+$/', // Lines with only numbers, spaces, dots, dashes
            '/^.{0,20}$/', // Very short paragraphs
        ];
        
        foreach ($paragraphs as $paragraph) {
            $paragraph_trimmed = trim($paragraph);
            $should_skip = false;
            
            foreach ($skip_patterns as $pattern) {
                if (preg_match($pattern, $paragraph_trimmed)) {
                    $should_skip = true;
                    break;
                }
            }
            
            if (!$should_skip && strlen($paragraph_trimmed) > 30) {
                $filtered[] = $paragraph;
            }
        }
        
        return empty($filtered) ? $paragraphs : $filtered;
    }
    
    /**
     * Filter content sentences to remove non-content elements
     */
    private function filterContentSentences($sentences) {
        $filtered = [];
        $skip_patterns = [
            '/^\s*(page|chapter|section)\s*\d*\s*[\.:]*\s*$/i',
            '/^\s*[\d\s\.\-]+$/', // Lines with only numbers, spaces, dots, dashes
            '/^.{0,15}$/', // Very short sentences
        ];
        
        foreach ($sentences as $sentence) {
            $sentence_trimmed = trim($sentence);
            $should_skip = false;
            
            foreach ($skip_patterns as $pattern) {
                if (preg_match($pattern, $sentence_trimmed)) {
                    $should_skip = true;
                    break;
                }
            }
            
            if (!$should_skip && strlen($sentence_trimmed) > 20) {
                $filtered[] = $sentence;
            }
        }
        
        return empty($filtered) ? $sentences : $filtered;
    }
    
    /**
     * Build Prompt with content split into pages
     */
    private function buildPromptWithPages($pages, $learning_style, $content_units, $total_questions) {
        $style_text = $this->getLearningStyleText($learning_style);
        
        $pages_content = "";
        $total_content_length = 0;
        $max_total_length = 15000;
        
        foreach ($pages as $index => $page_content) {
            $page_number = $index + 1;
            $page_excerpt = substr(trim($page_content), 0, 1500);
            
            if ($total_content_length + strlen($page_excerpt) > $max_total_length) {
                break;
            }
            
            $pages_content .= "=== PAGE {$page_number} ===\n" . $page_excerpt . "\n\n";
            $total_content_length += strlen($page_excerpt);
        }
        
        return "You are an expert educational assistant. Create {$total_questions} multiple choice questions STRICTLY based on the provided content below.

STUDY MATERIAL CONTENT:
{$pages_content}

CRITICAL INSTRUCTIONS:
- Generate questions ONLY from the content provided above
- DO NOT use external knowledge or information not in the content
- Every question must be directly answerable from the provided text
- All answer options must be based on information within the content
- If the content doesn't provide enough information for a question, skip that topic

FORMAT FOR EACH QUESTION:
Question 1: [Question based on the provided content]
A) Option from content
B) Option from content
C) Option from content
D) Option from content
Correct Answer: A

Question 2: [Question based on the provided content]
A) Option from content
B) Option from content
C) Option from content
D) Option from content
Correct Answer: B

REQUIREMENTS:
- Generate exactly {$total_questions} questions
- Each question has 4 options (A, B, C, D)
- Mark the correct answer clearly
- Cover different topics from the provided content ONLY
- Make questions clear and specific to the content
- Ensure all information comes from the study material above

Generate all {$total_questions} questions based ONLY on the provided content:";
    }
    
    private function cleanContent($content) {
        if (empty($content)) return '';
        
        // Remove page numbers and metadata but keep Arabic and English text
        $content = preg_replace('/Page \d+ of \d+/i', '', $content ?? '');
        $content = preg_replace('/\d{1,2}\/\d{1,2}\/\d{2,4}/', '', $content ?? '');
        $content = preg_replace('/Copyright \d{4}/i', '', $content ?? '');
        $content = preg_replace('/All rights reserved/i', '', $content ?? '');
        
        // Remove table of contents
        $content = preg_replace('/Table of Contents?.*?\n\n/is', '', $content ?? '');
        $content = preg_replace('/Contents.*?\n\n/is', '', $content ?? '');
        
        // Keep Arabic, English, numbers, punctuation, and whitespace
        // Arabic: \x{0600}-\x{06FF}, \x{FB50}-\x{FDFF}, \x{FE70}-\x{FEFF}
        // English: \x20-\x7E
        $content = preg_replace('/[^\x{0600}-\x{06FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}\x20-\x7E\n\r\t]/u', ' ', $content ?? '');
        
        // Clean up whitespace
        $content = preg_replace('/[ \t]+/', ' ', $content ?? '');
        $content = preg_replace('/\n[ \t]+\n/', "\n\n", $content ?? '');
        $content = preg_replace('/\n{3,}/', "\n\n", $content ?? '');
        
        // Remove navigation words but keep content
        $content = preg_replace('/\b(page|chapter|section|table of contents|index|bibliography|references)\s*\d*\b/i', '', $content ?? '');
        
        return trim($content ?? '');
    }
    
    private function analyzeContentStructure($content) {
        $analysis = [
            'topics' => [],
            'key_concepts' => [],
            'definitions' => [],
            'examples' => [],
            'formulas' => [],
            'lists' => [],
            'important_phrases' => [],
            'question_indicators' => [],
            'content_type' => 'general',
            'chapters' => [],
            'sections' => [],
            'difficulty_indicators' => [],
            'content_depth' => 'medium',
            'sentences' => [],
            'paragraphs' => []
        ];
        
        preg_match_all('/(?:^|\n)\s*(?:Chapter|Unit|Section|Topic|الفصل|الباب)\s*\d*[:\-\s]*([^\n]+)/im', $content, $matches);
        $analysis['chapters'] = array_unique(array_filter(array_slice($matches[1], 0, 20)));
        
        preg_match_all('/(?:^|\n)\s*\d+\.\d*\s*([A-Z][^\n]+)/m', $content, $matches);
        $analysis['sections'] = array_unique(array_filter(array_slice($matches[1], 0, 30)));
        
        preg_match_all('/\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+){0,3}\b/', $content, $matches);
        $concepts = array_count_values($matches[0]);
        arsort($concepts);
        $analysis['key_concepts'] = array_keys(array_slice($concepts, 0, 40, true));
        
        preg_match_all('/([A-Z][a-zA-Z\s]{2,40})\s+(?:is defined as|means|refers to|is|are|represents?|indicates?)\s+([^.!?\n]{15,200}[.!?])/', $content, $matches);
        for ($i = 0; $i < min(count($matches[1]), 25); $i++) {
            $term = trim($matches[1][$i]);
            $definition = trim($matches[2][$i]);
            if (strlen($term) > 3 && strlen($definition) > 15) {
                $analysis['definitions'][] = [
                    'term' => $term,
                    'definition' => $definition,
                    'difficulty' => $this->assessDefinitionDifficulty($term, $definition)
                ];
            }
        }
        
        preg_match_all('/(?:for example|such as|including|like|e\.g\.|i\.e\.|for instance)\s+([^.!?\n]{10,150}[.!?]?)/', $content, $matches);
        $analysis['examples'] = array_filter(array_slice($matches[1], 0, 30));
        
        preg_match_all('/(?:[A-Za-z]\s*[=]\s*[^.!?\n]+|[A-Za-z]+\s*\+\s*[A-Za-z]+|∑|∫|√)/', $content, $matches);
        $analysis['formulas'] = array_slice($matches[0], 0, 15);
        
        preg_match_all('/(?:^|\n)\s*(?:\d+\.|[•\-\*]|\([a-z]\))\s+([^\n]{15,250})/', $content, $matches);
        $analysis['lists'] = array_filter(array_slice($matches[1], 0, 40));
        
        preg_match_all('/"([^"]{5,100})"|\'([^\']{5,100})\'|\*([^*]{5,100})\*/', $content, $matches);
        $phrases = array_merge($matches[1], $matches[2], $matches[3]);
        $analysis['important_phrases'] = array_filter(array_slice($phrases, 0, 25));
        
        preg_match_all('/\b(?:what|how|why|when|where|which|who)\b[^.!?\n]*[?]/', $content, $matches);
        $analysis['question_indicators'] = array_slice($matches[0], 0, 12);
        
        $sentences = preg_split('/(?<=[.!?])\s+/', $content);
        $analysis['sentences'] = array_filter(array_map('trim', $sentences), function($s) {
            return strlen($s) > 20 && strlen($s) < 300;
        });
        $analysis['sentences'] = array_slice($analysis['sentences'], 0, 100);
        
        $paragraphs = preg_split('/\n\s*\n/', $content);
        $analysis['paragraphs'] = array_filter(array_map('trim', $paragraphs), function($p) {
            return strlen($p) > 50 && strlen($p) < 1000;
        });
        $analysis['paragraphs'] = array_slice($analysis['paragraphs'], 0, 50);
        
        $analysis = $this->assessContentComplexity($content, $analysis);
        
        $analysis['topics'] = $this->extractTopicStructure($content);
        
        return $analysis;
    }
    
    private function assessDefinitionDifficulty($term, $definition) {
        $term_length = strlen($term);
        $def_length = strlen($definition);
        $complex_words = preg_match_all('/\b[a-z]{8,}\b/i', $definition);
        
        if ($term_length > 20 || $def_length > 100 || $complex_words > 3) {
            return 'Hard';
        } elseif ($term_length > 10 || $def_length > 50 || $complex_words > 1) {
            return 'Medium';
        }
        return 'Easy';
    }
    
    private function assessContentComplexity($content, $analysis) {
        $complexity_score = 0;
        
        if (preg_match_all('/\b(?:theorem|proof|equation|formula|calculate|solve|derive)\b/i', $content) > 5) {
            $analysis['content_type'] = 'mathematical';
            $complexity_score += 3;
        }
        
        if (preg_match_all('/\b(?:research|study|analysis|methodology|hypothesis)\b/i', $content) > 3) {
            $analysis['content_type'] = 'academic';
            $complexity_score += 2;
        }
        
        if (preg_match_all('/\b(?:step|procedure|method|process|algorithm)\b/i', $content) > 5) {
            $analysis['content_type'] = 'procedural';
            $complexity_score += 1;
        }
        
        if (count($analysis['formulas']) > 5) $complexity_score += 2;
        if (count($analysis['definitions']) > 10) $complexity_score += 2;
        
        if ($complexity_score >= 6) {
            $analysis['content_depth'] = 'advanced';
        } elseif ($complexity_score >= 3) {
            $analysis['content_depth'] = 'intermediate';
        } else {
            $analysis['content_depth'] = 'basic';
        }
        
        return $analysis;
    }
    
    private function extractTopicStructure($content) {
        $topics = [];
        
        $paragraphs = preg_split('/\n\s*\n/', $content);
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (strlen($paragraph) < 50) continue;
            
            preg_match('/^([^.!?]+[.!?])/', $paragraph, $matches);
            if (isset($matches[1])) {
                $topic_candidate = trim($matches[1]);
                if (strlen($topic_candidate) > 20 && strlen($topic_candidate) < 100) {
                    $topics[] = $topic_candidate;
                }
            }
        }
        
        return array_slice($topics, 0, 15);
    }
    
    private function generateSmartQuestions($content_analysis, $pages, $total_questions) {
        $smart_questions = [];
        
        $difficulty_distribution = $this->calculateOptimalDifficultyDistribution($content_analysis, $total_questions);
        
        $topic_questions = $this->generateTopicBasedQuestions($content_analysis, $difficulty_distribution);
        
        return array_slice($topic_questions, 0, $total_questions);
    }
    
    private function calculateOptimalDifficultyDistribution($content_analysis, $total_questions) {
        $content_depth = $content_analysis['content_depth'];
        $has_formulas = count($content_analysis['formulas']) > 0;
        $has_definitions = count($content_analysis['definitions']) > 5;
        
        $easy_ratio = 0.25;
        $medium_ratio = 0.45;
        $hard_ratio = 0.30;
        
        switch ($content_depth) {
            case 'basic':
                $easy_ratio = 0.40;
                $medium_ratio = 0.40;
                $hard_ratio = 0.20;
                break;
            case 'intermediate':
                $easy_ratio = 0.25;
                $medium_ratio = 0.50;
                $hard_ratio = 0.25;
                break;
            case 'advanced':
                $easy_ratio = 0.20;
                $medium_ratio = 0.40;
                $hard_ratio = 0.40;
                break;
        }
        
        if ($has_formulas) {
            $hard_ratio += 0.10;
            $easy_ratio -= 0.05;
            $medium_ratio -= 0.05;
        }
        
        if ($has_definitions) {
            $easy_ratio += 0.10;
            $medium_ratio -= 0.05;
            $hard_ratio -= 0.05;
        }
        
        $easy_count = max(1, floor($total_questions * $easy_ratio));
        $medium_count = max(1, floor($total_questions * $medium_ratio));
        $hard_count = max(2, floor($total_questions * $hard_ratio));
        
        if ($hard_count < (int)ceil($total_questions * 0.25)) {
            $hard_count = max(2, (int)ceil($total_questions * 0.25));
        }
        
        $total_assigned = $easy_count + $medium_count + $hard_count;
        if ($total_assigned > $total_questions) {
            $excess = $total_assigned - $total_questions;
            if ($easy_count > 1) {
                $easy_count = max(1, $easy_count - $excess);
            }
        }
        
        return [
            'easy' => $easy_count,
            'medium' => $medium_count,
            'hard' => $hard_count
        ];
    }
    
    private function generateTopicBasedQuestions($content_analysis, $difficulty_distribution) {
        $questions = [];
        $topics_covered = [];
        
        $all_topics = array_merge(
            $content_analysis['chapters'],
            $content_analysis['sections'],
            $content_analysis['topics']
        );
        
        $topics_per_difficulty = [
            'easy' => array_slice($all_topics, 0, $difficulty_distribution['easy']),
            'medium' => array_slice($all_topics, 0, $difficulty_distribution['medium']),
            'hard' => array_slice($all_topics, 0, $difficulty_distribution['hard'])
        ];
        
        $easy_count = 0;
        foreach ($content_analysis['definitions'] as $def) {
            if ($easy_count >= $difficulty_distribution['easy']) break;
            
            $questions[] = [
                'question' => "What is " . $def['term'] . "?",
                'type' => 'definition',
                'difficulty' => 'Easy',
                'source_content' => $def['term'],
                'topic' => $this->findRelatedTopic($def['term'], $all_topics)
            ];
            $easy_count++;
        }
        
        foreach ($content_analysis['key_concepts'] as $concept) {
            if ($easy_count >= $difficulty_distribution['easy']) break;
            
            $questions[] = [
                'question' => "What does " . $concept . " refer to?",
                'type' => 'concept',
                'difficulty' => 'Easy',
                'source_content' => $concept,
                'topic' => $this->findRelatedTopic($concept, $all_topics)
            ];
            $easy_count++;
        }
        
        $medium_count = 0;
        foreach ($content_analysis['examples'] as $example) {
            if ($medium_count >= $difficulty_distribution['medium']) break;
            
            $questions[] = [
                'question' => "Based on the example: " . substr($example, 0, 50) . "..., which principle applies?",
                'type' => 'application',
                'difficulty' => 'Medium',
                'source_content' => $example,
                'topic' => $this->findRelatedTopic($example, $all_topics)
            ];
            $medium_count++;
        }
        
        foreach ($content_analysis['key_concepts'] as $concept) {
            if ($medium_count >= $difficulty_distribution['medium']) break;
            
            $questions[] = [
                'question' => "How does " . $concept . " relate to the main concepts discussed?",
                'type' => 'comprehension',
                'difficulty' => 'Medium',
                'source_content' => $concept,
                'topic' => $this->findRelatedTopic($concept, $all_topics)
            ];
            $medium_count++;
        }
        
        $hard_count = 0;
        
        foreach ($content_analysis['formulas'] as $formula) {
            if ($hard_count >= $difficulty_distribution['hard']) break;
            
            $questions[] = [
                'question' => "Analyze the relationship in: " . $formula,
                'type' => 'analysis',
                'difficulty' => 'Hard',
                'source_content' => $formula,
                'topic' => 'Mathematical Analysis'
            ];
            $hard_count++;
        }
        
        foreach ($content_analysis['key_concepts'] as $concept) {
            if ($hard_count >= $difficulty_distribution['hard']) break;
            
            $questions[] = [
                'question' => "Evaluate the implications of " . $concept . " in the broader context.",
                'type' => 'evaluation',
                'difficulty' => 'Hard',
                'source_content' => $concept,
                'topic' => $this->findRelatedTopic($concept, $all_topics)
            ];
            $hard_count++;
        }
        
        foreach ($content_analysis['paragraphs'] as $paragraph) {
            if ($hard_count >= $difficulty_distribution['hard']) break;
            
            $questions[] = [
                'question' => "Critically analyze the main argument in: " . substr($paragraph, 0, 80) . "...",
                'type' => 'critical_analysis',
                'difficulty' => 'Hard',
                'source_content' => $paragraph,
                'topic' => $this->findRelatedTopic($paragraph, $all_topics)
            ];
            $hard_count++;
        }
        
        foreach ($content_analysis['sentences'] as $sentence) {
            if ($hard_count >= $difficulty_distribution['hard']) break;
            
            $questions[] = [
                'question' => "What are the broader implications of: " . substr($sentence, 0, 70) . "...",
                'type' => 'synthesis',
                'difficulty' => 'Hard',
                'source_content' => $sentence,
                'topic' => $this->findRelatedTopic($sentence, $all_topics)
            ];
            $hard_count++;
        }
        
        while ($hard_count < $difficulty_distribution['hard']) {
            $random_concept = !empty($content_analysis['key_concepts']) ? 
                $content_analysis['key_concepts'][array_rand($content_analysis['key_concepts'])] : 
                'the main topic';
                
            $questions[] = [
                'question' => "Synthesize and evaluate the relationship between " . $random_concept . " and other concepts discussed.",
                'type' => 'synthesis',
                'difficulty' => 'Hard',
                'source_content' => $random_concept,
                'topic' => $this->findRelatedTopic($random_concept, $all_topics)
            ];
            $hard_count++;
        }
        
        return $questions;
    }
    
    private function findRelatedTopic($content, $topics) {
        foreach ($topics as $topic) {
            if (stripos($topic, $content) !== false || stripos($content, $topic) !== false) {
                return $topic;
            }
        }
        return 'General Topic';
    }
    
    private function addFallbackHardQuestions($questions, $content_analysis, $total_questions) {
        $target_hard = max(2, (int)ceil($total_questions * 0.30));
        $current_hard = 0;
        
        foreach ($questions as $q) {
            if (strtolower($q['difficulty'] ?? '') === 'hard') $current_hard++;
        }
        
        $needed_hard = $target_hard - $current_hard;
        
        if ($needed_hard <= 0) return $questions;
        
        $fallback_questions = [];
        $concepts = $content_analysis['key_concepts'] ?? [];
        $sentences = $content_analysis['sentences'] ?? [];
        $paragraphs = $content_analysis['paragraphs'] ?? [];
        
        for ($i = 0; $i < $needed_hard; $i++) {
            if (!empty($paragraphs) && $i < count($paragraphs)) {
                $paragraph = $paragraphs[$i];
                $fallback_questions[] = [
                    'question' => "Analyze the main argument presented in: " . substr($paragraph, 0, 80) . "...",
                    'options' => [
                        substr($paragraph, 0, 100) . "...",
                        "This concept is not discussed in the material",
                        "The argument focuses on different aspects",
                        "Alternative interpretation of the content"
                    ],
                    'correct_answer' => 0,
                    'type' => 'multiple_choice',
                    'difficulty' => 'Hard',
                    'page_unit' => '1',
                    'points' => 5,
                    'time_limit' => 120
                ];
            } elseif (!empty($concepts)) {
                $concept = $concepts[array_rand($concepts)];
                $fallback_questions[] = [
                    'question' => "Evaluate the broader implications of " . $concept . " in the context of the material.",
                    'options' => [
                        "It demonstrates key relationships between concepts",
                        "It has limited relevance to the main topic",
                        "It contradicts other presented ideas",
                        "It serves as a supporting detail only"
                    ],
                    'correct_answer' => 0,
                    'type' => 'multiple_choice',
                    'difficulty' => 'Hard',
                    'page_unit' => '1',
                    'points' => 5,
                    'time_limit' => 120
                ];
            } else {
                $fallback_questions[] = [
                    'question' => "What critical analysis can be drawn from the main themes discussed in the material?",
                    'options' => [
                        "The themes interconnect to form a comprehensive understanding",
                        "The themes are presented without clear connections",
                        "The material lacks thematic coherence",
                        "The themes contradict established principles"
                    ],
                    'correct_answer' => 0,
                    'type' => 'multiple_choice',
                    'difficulty' => 'Hard',
                    'page_unit' => '1',
                    'points' => 5,
                    'time_limit' => 120
                ];
            }
        }
        
        $easy_questions = [];
        $medium_questions = [];
        $hard_questions = [];
        
        foreach ($questions as $q) {
            $diff = strtolower($q['difficulty'] ?? 'medium');
            if ($diff === 'easy') $easy_questions[] = $q;
            elseif ($diff === 'hard') $hard_questions[] = $q;
            else $medium_questions[] = $q;
        }
        
        $hard_questions = array_merge($hard_questions, $fallback_questions);
        
        $all_questions = array_merge($easy_questions, $medium_questions, $hard_questions);
        shuffle($all_questions);
        
        return array_slice($all_questions, 0, $total_questions);
    }
    
    private function buildEnhancedPrompt($pages, $learning_style, $content_units, $total_questions, $content_analysis, $smart_questions) {
        $style_text = $this->getLearningStyleText($learning_style);
        
        $pages_content = "";
        $total_content_length = 0;
        $max_total_length = 30000;
        
        $total_pages = count($pages);
        $chars_per_page = (int)($max_total_length / max($total_pages, 1));
        $chars_per_page = min($chars_per_page, 3000);
        
        foreach ($pages as $index => $page_content) {
            $page_number = $index + 1;
            $page_excerpt = substr(trim($page_content), 0, $chars_per_page);
            
            if ($total_content_length + strlen($page_excerpt) > $max_total_length) {
                break;
            }
            
            $pages_content .= "=== PAGE {$page_number} ===\n" . $page_excerpt . "\n\n";
            $total_content_length += strlen($page_excerpt);
        }
        
        
        $smart_context = "COMPREHENSIVE CONTENT ANALYSIS:\n";
        $smart_context .= "Content Type: " . $content_analysis['content_type'] . "\n";
        $smart_context .= "Content Depth: " . $content_analysis['content_depth'] . "\n";
        $smart_context .= "Total Pages: {$total_pages}\n";
        $smart_context .= "Content Length: {$total_content_length} characters\n";
        
        if (!empty($content_analysis['chapters'])) {
            $smart_context .= "Chapters Found: " . count($content_analysis['chapters']) . " (" . implode(', ', array_slice($content_analysis['chapters'], 0, 8)) . ")\n";
        }
        
        if (!empty($content_analysis['topics'])) {
            $smart_context .= "Key Topics: " . count($content_analysis['topics']) . " (" . implode(', ', array_slice($content_analysis['topics'], 0, 5)) . ")\n";
        }
        
        if (!empty($content_analysis['key_concepts'])) {
            $smart_context .= "Key Concepts: " . implode(', ', array_slice($content_analysis['key_concepts'], 0, 12)) . "\n";
        }
        
        if (!empty($content_analysis['definitions'])) {
            $smart_context .= "Definitions: " . count($content_analysis['definitions']) . " terms\n";
            $def_examples = array_slice($content_analysis['definitions'], 0, 3);
            foreach ($def_examples as $def) {
                $smart_context .= "  - " . $def['term'] . " (Difficulty: " . $def['difficulty'] . ")\n";
            }
        }
        
        if (!empty($content_analysis['formulas'])) {
            $smart_context .= "Mathematical Content: " . count($content_analysis['formulas']) . " formulas/equations\n";
        }
        
        if (!empty($content_analysis['examples'])) {
            $smart_context .= "Examples Found: " . count($content_analysis['examples']) . "\n";
        }
        
        $smart_context .= "\n";
        
        return "You are an expert educational assistant with advanced content analysis capabilities. Create {$total_questions} multiple choice questions using the intelligent analysis below.

{$smart_context}

STUDY MATERIAL CONTENT:
{$pages_content}

INTELLIGENT QUESTION STRATEGY:
- Read and understand the ENTIRE content provided above
- Extract questions directly from the text - do NOT make up information
- Cover ALL major topics and chapters identified
- Focus on key concepts: " . implode(', ', array_slice($content_analysis['key_concepts'], 0, 8)) . "
- Include definition questions from identified terms
- Use examples, formulas, and real content from the material
- Adapt to content complexity level: {$content_analysis['content_depth']}
- Ensure questions are distributed across ALL pages, not just the first few
- Balance between breadth (covering all topics) and depth (testing understanding)

STRICT DIFFICULTY DISTRIBUTION (ABSOLUTELY MANDATORY):
YOU MUST GENERATE EXACTLY:
- " . max(1, (int)round($total_questions * 0.25)) . " Easy Questions [Difficulty: Easy] - Basic definitions, simple facts, recall
- " . max(1, (int)round($total_questions * 0.45)) . " Medium Questions [Difficulty: Medium] - Application, comprehension, examples  
- " . max(2, $total_questions - max(1, (int)round($total_questions * 0.25)) - max(1, (int)round($total_questions * 0.45))) . " Hard Questions [Difficulty: Hard] - Analysis, synthesis, complex relationships

MINIMUM HARD QUESTIONS REQUIRED: " . max(2, (int)ceil($total_questions * 0.30)) . " (30% of total, minimum 2)

CRITICAL: You MUST generate ALL THREE difficulty levels. Do NOT skip Hard questions!
Label each question clearly: [Difficulty: Easy], [Difficulty: Medium], or [Difficulty: Hard]

COMPREHENSIVE COVERAGE REQUIREMENTS:
- Cover ALL major topics and chapters identified
- Include questions from different sections of the material
- Ensure no important concept is missed
- Balance between breadth and depth of coverage

CRITICAL INSTRUCTIONS (MUST FOLLOW):
- Generate questions ONLY from the content provided above - NO external knowledge
- Every question and ALL 4 options must come directly from the text
- If the answer is not in the content, DO NOT create that question
- Verify each question can be answered by reading the provided material
- Wrong options should be plausible but clearly different from correct answer
- Cover content from ALL pages, not just the beginning
- Maintain the EXACT difficulty distribution specified above (30-40-30)

FORMAT FOR EACH QUESTION:
Question 1: [Difficulty: Easy/Medium/Hard] [Question text]
A) Option from content
B) Option from content  
C) Option from content
D) Option from content
Correct Answer: A

REQUIREMENTS (FOLLOW EXACTLY):
1. Generate EXACTLY {$total_questions} questions total
2. EVERY question MUST have [Difficulty: Easy/Medium/Hard] label at the start
3. Each question MUST have exactly 4 options (A, B, C, D)
4. Mark correct answer as 'Correct Answer: A/B/C/D'

GENERATION ORDER (MANDATORY):
Step 1: Generate " . max(1, (int)round($total_questions * 0.25)) . " questions with [Difficulty: Easy]
Step 2: Generate " . max(1, (int)round($total_questions * 0.45)) . " questions with [Difficulty: Medium]
Step 3: Generate " . max(2, $total_questions - max(1, (int)round($total_questions * 0.25)) - max(1, (int)round($total_questions * 0.45))) . " questions with [Difficulty: Hard]

DO NOT SKIP STEP 3! Hard questions are MANDATORY!

EXAMPLE FORMAT:
Question 1: [Difficulty: Easy] What is the definition of X?
A) Option 1
B) Option 2
C) Option 3
D) Option 4
Correct Answer: A

Now generate ALL {$total_questions} questions following this EXACT format and distribution:";
    }
    
    private function callExternalAPI($prompt, $max_questions, $content_analysis = null) {
        if (empty($this->api_key)) {
            throw new Exception('Service key not found');
        }
        
        
        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert educational assistant that creates high-quality exam questions. You MUST follow all instructions precisely, especially difficulty distribution. Generate EXACTLY the number requested for EACH difficulty level: Easy, Medium, AND Hard. CRITICAL: You MUST generate Hard questions - they are MANDATORY and cannot be skipped under any circumstances. Hard questions should test analysis, synthesis, evaluation, and critical thinking. Always label each question with [Difficulty: Easy/Medium/Hard].'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => MAX_TOKENS,
            'top_p' => 0.95,
            'stream' => false
        ];
        
        // Check if curl is available, otherwise use file_get_contents
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->api_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->api_key
                ],
                CURLOPT_TIMEOUT => 240,
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            if ($http_code !== 200) {
                throw new Exception("API connection failed (HTTP {$http_code}): " . ($curl_error ?: 'Unknown error') . ". Response: " . substr($response, 0, 200));
            }
        } else {
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $this->api_key
                    ],
                    'content' => json_encode($data),
                    'timeout' => 240
                ]
            ]);
            
            $response = @file_get_contents($this->api_url, false, $context);
            
            if ($response === false) {
                $error = error_get_last();
                throw new Exception("API connection failed: " . ($error['message'] ?? 'Network error. Please check your internet connection.'));
            }
            
            if (isset($http_response_header)) {
                $status_line = $http_response_header[0] ?? '';
                if (!preg_match('/HTTP\/\d\.\d\s+200/', $status_line)) {
                    throw new Exception("API request failed: {$status_line}. Response: " . substr($response, 0, 200));
                }
            }
        }
        
        $response_data = json_decode($response, true);
        
        if (!isset($response_data['choices'][0]['message']['content'])) {
            if (isset($response_data['error'])) {
                throw new Exception('API Error: ' . $response_data['error']['message'] ?? 'Unknown API error');
            }
            
            throw new Exception('Invalid response structure from AI service. Check API configuration.');
        }
        
        $content = $response_data['choices'][0]['message']['content'];
        
        return $this->parseAIResponse($content, $max_questions, $content_analysis);
    }
    
    private function parseAIResponse($content, $max_questions, $content_analysis = null) {
        
        $patterns = [
            '/Question\s+\d+:/i',
            '/\d+\.\s*[A-Z]/',
            '/Q\d+:/i'
        ];
        
        $blocks = [];
        foreach ($patterns as $pattern) {
            $blocks = preg_split($pattern, $content, -1, PREG_SPLIT_NO_EMPTY);
            if (count($blocks) > 1) {
                break;
            }
        }
        
        if (count($blocks) <= 1) {
            return $this->fallbackQuestionParsing($content, $max_questions);
        }
        
        $questions = [];
        foreach ($blocks as $block_index => $block) {
            if (count($questions) >= $max_questions) {
                break;
            }
            
            $block = trim($block);
            if (empty($block) || strlen($block) < 20) {
                continue;
            }
            
            $parsed_question = $this->parseQuestionBlock($block);
            if ($parsed_question) {
                $questions[] = $parsed_question;
            }
        }
        
        if (count($questions) < 3) {
            $questions = $this->fallbackQuestionParsing($content, $max_questions);
        }
        
        $questions = $this->ensureDifficultyDistribution($questions);
        
        $questions = $this->enhanceQuestionQuality($questions, $content_analysis);
        
        $coverage_report = $this->analyzeCoverageQuality($questions, $content_analysis);
        
        return $questions;
    }
    
    private function enhanceQuestionQuality($questions, $content_analysis = null) {
        if (empty($questions)) return $questions;
        
        $enhanced_questions = [];
        
        foreach ($questions as $question) {
            $enhanced_question = $question;
            
            $enhanced_question['question'] = $this->improveQuestionClarity($question['question']);
            
            if (isset($question['options']) && is_array($question['options'])) {
                $enhanced_question['options'] = $this->improveOptionsQuality($question['options']);
            }
            
            if ($content_analysis) {
                $enhanced_question['difficulty'] = $this->assignIntelligentDifficulty($question, $content_analysis);
            }
            
            $enhanced_question['points'] = $this->calculateQuestionPoints($enhanced_question['difficulty']);
            $enhanced_question['time_limit'] = $this->calculateTimeLimit($enhanced_question['difficulty']);
            
            $enhanced_questions[] = $enhanced_question;
        }
        
        return $enhanced_questions;
    }
    
    private function analyzeCoverageQuality($questions, $content_analysis) {
        $coverage_report = [
            'total_questions' => count($questions),
            'difficulty_distribution' => ['Easy' => 0, 'Medium' => 0, 'Hard' => 0],
            'topics_covered' => [],
            'concepts_covered' => [],
            'coverage_score' => 0
        ];
        
        foreach ($questions as $question) {
            $difficulty = $question['difficulty'] ?? 'Medium';
            $coverage_report['difficulty_distribution'][$difficulty]++;
        }
        
        $all_topics = array_merge(
            $content_analysis['chapters'] ?? [],
            $content_analysis['sections'] ?? [],
            $content_analysis['topics'] ?? []
        );
        
        foreach ($questions as $question) {
            $question_text = strtolower($question['question'] ?? '');
            foreach ($all_topics as $topic) {
                if (stripos($question_text, strtolower($topic)) !== false) {
                    $coverage_report['topics_covered'][] = $topic;
                }
            }
        }
        
        foreach ($questions as $question) {
            $question_text = strtolower($question['question'] ?? '');
            foreach ($content_analysis['key_concepts'] ?? [] as $concept) {
                if (stripos($question_text, strtolower($concept)) !== false) {
                    $coverage_report['concepts_covered'][] = $concept;
                }
            }
        }
        
        $total_topics = count($all_topics);
        $covered_topics = count(array_unique($coverage_report['topics_covered']));
        $coverage_report['coverage_score'] = $total_topics > 0 ? round(($covered_topics / $total_topics) * 100, 1) : 0;
        
        return $coverage_report;
    }
    
    private function improveQuestionClarity($question_text) {
        $question_text = preg_replace('/\*\*+/', '', $question_text);
        $question_text = preg_replace('/__+/', '', $question_text);
        $question_text = preg_replace('/~~+/', '', $question_text);
        $question_text = preg_replace('/^\s*[\*\-\+]\s+/', '', $question_text);
        $question_text = preg_replace('/\b(which of the following|what is the|what are the)\b/i', '', $question_text);
        $question_text = trim($question_text);
        
        if (!preg_match('/[?]$/', $question_text)) {
            $question_text .= '?';
        }
        
        return $question_text;
    }
    
    private function improveOptionsQuality($options) {
        $improved_options = [];
        
        foreach ($options as $option) {
            $option = trim($option);
            $option = preg_replace('/^[A-D]\)\s*/', '', $option);
            $option = preg_replace('/\*\*+/', '', $option);
            $option = preg_replace('/__+/', '', $option);
            $option = preg_replace('/~~+/', '', $option);
            $option = trim($option);
            $improved_options[] = $option;
        }
        
        return $improved_options;
    }
    
    private function assignIntelligentDifficulty($question, $content_analysis) {
        $question_text = strtolower($question['question']);
        
        if (preg_match('/\b(what is|define|meaning|definition)\b/', $question_text)) {
            return 'Easy';
        }
        
        if (preg_match('/\b(analyze|evaluate|compare|contrast|synthesize|critique)\b/', $question_text)) {
            return 'Hard';
        }
        
        foreach ($content_analysis['key_concepts'] as $concept) {
            if (stripos($question_text, strtolower($concept)) !== false) {
                return 'Medium';
            }
        }
        
        return 'Medium'; // Default
    }
    
    private function calculateQuestionPoints($difficulty) {
        switch (strtolower($difficulty)) {
            case 'easy': return 2;
            case 'hard': return 5;
            default: return 3;
        }
    }
    
    private function calculateTimeLimit($difficulty) {
        switch (strtolower($difficulty)) {
            case 'easy': return 60;
            case 'hard': return 120;
            default: return 90;
        }
    }
    
    private function parseQuestionBlock($block) {
        $lines = array_filter(array_map('trim', explode("\n", $block)));
        $question_text_with_markers = '';
        $options = [];
        $correct_answer = 0;
        
        
        foreach ($lines as $line) {
            if (empty($line)) continue;
            
            if (empty($question_text_with_markers) && !preg_match('/^[A-D]\)/i', $line) && !preg_match('/Correct\s+Answer/i', $line)) {
                $question_text_with_markers = trim($line);
                continue;
            }
            
            if (preg_match('/^([A-D])\)\s*(.+)/i', $line, $matches)) {
                $option_text = trim($matches[2]);
                $option_text = preg_replace('/\*\*+/', '', $option_text);
                $option_text = preg_replace('/__+/', '', $option_text);
                $option_text = preg_replace('/~~+/', '', $option_text);
                $option_text = trim($option_text);
                $options[] = $option_text;
            }
            
            if (preg_match('/Correct\s+[Aa]nswer\s*:?\s*([A-D])/i', $line, $matches)) {
                $correct_answer = $this->englishLetterToIndex(strtoupper($matches[1]));
            }
        }
        
        if (count($options) >= 3 && !empty($question_text_with_markers)) {
            while (count($options) < 4) {
                $options[] = "Option " . (count($options) + 1);
            }
            
            $difficulty = 'Medium';
            
            $page = '1';
            
            if (preg_match('/\[Difficulty:\s*(Easy|Medium|Hard)\]/i', $question_text_with_markers, $matches)) {
                 $difficulty = trim($matches[1]);
            } elseif (preg_match('/\((Difficulty:\s*(Easy|Medium|Hard))\)/i', $question_text_with_markers, $matches)) {
                 $difficulty = trim($matches[2]);
            }
            
            if (preg_match('/\((Page\s*(\d+))\)/i', $question_text_with_markers, $matches)) {
                 $page = trim($matches[2]);
            }
            
            $question_text = $question_text_with_markers;
            $question_text = preg_replace('/\s*\[Difficulty:\s*(Easy|Medium|Hard)\]\s*/i', '', $question_text);
            $question_text = preg_replace('/\s*\((Difficulty:\s*(Easy|Medium|Hard))\)\s*/i', '', $question_text);
            $question_text = preg_replace('/\s*\((Page\s*\d+)\)\s*/i', '', $question_text);
            $question_text = preg_replace('/\s*\((Type:\s*[^)]+)\)\s*/i', '', $question_text);
            $question_text = preg_replace('/\*\*+/', '', $question_text);
            $question_text = preg_replace('/__+/', '', $question_text);
            $question_text = preg_replace('/~~+/', '', $question_text);
            $question_text = preg_replace('/^\s*[\*\-\+]\s+/', '', $question_text);
            $question_text = trim($question_text);
            
            $result = [
                'question' => $question_text,
                'options' => array_slice($options, 0, 4), // Ensure exactly 4 options
                'correct_answer' => min($correct_answer, 3), // Ensure valid index
                'type' => 'multiple_choice',
                'difficulty' => $difficulty,
                'page_unit' => $page,
                'points' => 2,
                'time_limit' => 90
            ];
            
            return $result;
        }
        
        return null;
    }
    
    private function fallbackQuestionParsing($content, $max_questions) {
        $questions = [];
        
        $lines = explode("\n", $content);
        $current_question = '';
        $current_options = [];
        $correct_answer = 0;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if (preg_match('/\?$/', $line) && empty($current_options)) {
                $current_question = $line;
                continue;
            }
            
            if (preg_match('/^([A-D])\)\s*(.+)/i', $line, $matches)) {
                $option_text = trim($matches[2]);
                $option_text = preg_replace('/\*\*+/', '', $option_text);
                $option_text = preg_replace('/__+/', '', $option_text);
                $option_text = preg_replace('/~~+/', '', $option_text);
                $option_text = trim($option_text);
                $current_options[] = $option_text;
                continue;
            }
            
            if (preg_match('/Correct\s+[Aa]nswer\s*:?\s*([A-D])/i', $line, $matches)) {
                $correct_answer = $this->englishLetterToIndex(strtoupper($matches[1]));
                
                if (!empty($current_question) && count($current_options) >= 3) {
                    while (count($current_options) < 4) {
                        $current_options[] = "Option " . (count($current_options) + 1);
                    }
                    
                    static $fallback_counter = 0;
                    $fallback_counter++;
                    
                    if ($fallback_counter % 10 <= 3) {
                        $fallback_difficulty = 'Easy';
                    } elseif ($fallback_counter % 10 <= 7) {
                        $fallback_difficulty = 'Medium';
                    } else {
                        $fallback_difficulty = 'Hard';
                    }
                    
                    $clean_question = $current_question;
                    $clean_question = preg_replace('/\s*\[Difficulty:\s*(Easy|Medium|Hard)\]\s*/i', '', $clean_question);
                    $clean_question = preg_replace('/\s*\((Difficulty:\s*(Easy|Medium|Hard))\)\s*/i', '', $clean_question);
                    $clean_question = preg_replace('/\*\*+/', '', $clean_question);
                    $clean_question = preg_replace('/__+/', '', $clean_question);
                    $clean_question = preg_replace('/~~+/', '', $clean_question);
                    $clean_question = preg_replace('/^\s*[\*\-\+]\s+/', '', $clean_question);
                    $clean_question = trim($clean_question);
                    
                    $questions[] = [
                        'question' => $clean_question,
                        'options' => array_slice($current_options, 0, 4),
                        'correct_answer' => min($correct_answer, 3),
                        'type' => 'multiple_choice',
                        'difficulty' => $fallback_difficulty,
                        'page_unit' => '1',
                        'points' => 2,
                        'time_limit' => 90
                    ];
                    
                    $current_question = '';
                    $current_options = [];
                    $correct_answer = 0;
                    
                    if (count($questions) >= $max_questions) {
                        break;
                    }
                }
            }
        }
        
        return $questions;
    }
    
    private function ensureDifficultyDistribution($questions) {
        if (empty($questions)) return $questions;
        
        $total = count($questions);
        $target_easy = max(1, (int)round($total * 0.25));
        $target_medium = max(1, (int)round($total * 0.45));
        $target_hard = max(2, $total - $target_easy - $target_medium);
        
        if ($target_hard < (int)ceil($total * 0.30)) {
            $target_hard = max(2, (int)ceil($total * 0.30));
            $remaining = $total - $target_hard;
            $target_easy = max(1, (int)round($remaining * 0.35));
            $target_medium = $remaining - $target_easy;
        }
        
        foreach ($questions as &$q) {
            $original_difficulty = $q['difficulty'] ?? 'Medium';
            $smart_difficulty = $this->analyzeQuestionDifficulty($q);
            
            if ($smart_difficulty !== $original_difficulty) {
                $q['difficulty'] = $smart_difficulty;
            }
        }
        unset($q);
        
        $easy_count = 0;
        $medium_count = 0;
        $hard_count = 0;
        
        foreach ($questions as $q) {
            $difficulty = $q['difficulty'];
            if ($difficulty === 'Easy') $easy_count++;
            elseif ($difficulty === 'Hard') $hard_count++;
            else $medium_count++;
        }
        
        $easy_questions = [];
        $medium_questions = [];
        $hard_questions = [];
        
        foreach ($questions as $q) {
            if ($q['difficulty'] === 'Easy') $easy_questions[] = $q;
            elseif ($q['difficulty'] === 'Hard') $hard_questions[] = $q;
            else $medium_questions[] = $q;
        }
        
        $all_questions = array_merge($hard_questions, $easy_questions, $medium_questions);
        shuffle($all_questions);
        
        if (count($all_questions) < $total) {
        }
        
        $redistributed = [];
        
        for ($i = 0; $i < $target_easy && $i < count($all_questions); $i++) {
            $q = $all_questions[$i];
            $q['difficulty'] = 'Easy';
            $q['points'] = 2;
            $q['time_limit'] = 60;
            $redistributed[] = $q;
        }
        
        for ($i = $target_easy; $i < $target_easy + $target_medium && $i < count($all_questions); $i++) {
            $q = $all_questions[$i];
            $q['difficulty'] = 'Medium';
            $q['points'] = 3;
            $q['time_limit'] = 90;
            $redistributed[] = $q;
        }
        
        for ($i = $target_easy + $target_medium; $i < $total && $i < count($all_questions); $i++) {
            $q = $all_questions[$i];
            $q['difficulty'] = 'Hard';
            $q['points'] = 5;
            $q['time_limit'] = 120;
            $redistributed[] = $q;
        }
        
        $final_easy = count(array_filter($redistributed, fn($x) => $x['difficulty'] === 'Easy'));
        $final_medium = count(array_filter($redistributed, fn($x) => $x['difficulty'] === 'Medium'));
        $final_hard = count(array_filter($redistributed, fn($x) => $x['difficulty'] === 'Hard'));
        
        
        return $redistributed;
    }
    
    private function analyzeQuestionDifficulty($question) {
        $text = strtolower($question['question']);
        $text_length = strlen($question['question']);
        $options_avg_length = 0;
        
        if (!empty($question['options'])) {
            foreach ($question['options'] as $opt) {
                $options_avg_length += strlen($opt);
            }
            $options_avg_length = $options_avg_length / count($question['options']);
        }
        
        $hard_keywords = ['analyze', 'evaluate', 'compare', 'contrast', 'synthesize', 'critique', 'justify', 'assess', 'relationship between', 'why does', 'how does', 'explain why', 'most likely', 'best explains', 'implications', 'consequences'];
        $easy_keywords = ['what is', 'define', 'who is', 'when did', 'where is', 'list', 'name', 'identify', 'which of the following is'];
        
        $hard_score = 0;
        $easy_score = 0;
        
        foreach ($hard_keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $hard_score += 2;
            }
        }
        
        foreach ($easy_keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $easy_score += 2;
            }
        }
        
        if ($text_length > 150) $hard_score += 1;
        if ($text_length < 60) $easy_score += 1;
        
        if ($options_avg_length > 50) $hard_score += 1;
        if ($options_avg_length < 20) $easy_score += 1;
        
        if (preg_match('/\b(however|although|despite|whereas|nevertheless)\b/i', $text)) {
            $hard_score += 1;
        }
        
        if ($hard_score > $easy_score + 2) {
            return 'Hard';
        } elseif ($easy_score > $hard_score + 1) {
            return 'Easy';
        } else {
            return 'Medium';
        }
    }
    
    private function englishLetterToIndex($letter) {
        $mapping = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3];
        return $mapping[$letter] ?? 0;
    }
    
    private function getLearningStyleText($learning_style) {
        if (empty($learning_style)) {
            return "• Learning Style: Balanced";
        }
        
        $text = "";
        foreach ($learning_style as $key => $value) {
            if (!empty($value)) {
                $text .= "• {$key}: {$value}\n";
            }
        }
        
        return $text ?: "• Learning Style: Balanced";
    }
    
    private function saveQuestionsToDB($user_id, $file_name, $content, $learning_style, $questions) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO ai_generated_questions 
                (user_id, file_name, file_content, questionlevel, questions, ai_model) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user_id,
                $file_name,
                mb_substr($content, 0, 5000),
                json_encode($learning_style),
                json_encode($questions),
                'AI Assistant Pro Enhanced'
            ]);
            
            $saved_id = $pdo->lastInsertId();
            
            return $saved_id;
            
        } catch (Exception $e) {
            return null;
        }
    }
}

/**
 * Helper functions for schedule generation
 */
class ScheduleHelper {
    /**
     * Generate time slots based on pages and activity type
     */
    public static function generateTimeSlots($pages, $type = 'study') {
        $time_slots = [];
        
        if ($type === 'review') {
            // وقت المراجعة
            return [
                ['time' => '09:00-10:00', 'activity' => 'Review Key Concepts & Definitions'],
                ['time' => '10:15-11:15', 'activity' => 'Review Difficult Topics & Formulas'],
                ['time' => '11:30-12:30', 'activity' => 'Practice Questions & Solutions'],
                ['time' => '14:00-15:00', 'activity' => 'Final Summary & Important Points'],
                ['time' => '16:00-16:30', 'activity' => 'Quick Revision & Mental Preparation']
            ];
        }
        
        // وقت الدراسة العادية
        $study_hours = self::calculateDailyHours($pages);
        $pages_per_session = ceil($pages / $study_hours);
        
        for ($session = 1; $session <= $study_hours; $session++) {
            $session_pages = min($pages_per_session, $pages - (($session-1) * $pages_per_session));
            
            if ($session_pages > 0) {
                $time_slots[] = [
                    'time' => (8 + ($session-1)) . ':00-' . (9 + ($session-1)) . ':00',
                    'activity' => 'Study Session - ' . $session_pages . ' pages'
                ];
                
                // إضافة استراحة بعد كل جلسة ماعدا الأخيرة
                if ($session < $study_hours) {
                    $time_slots[] = [
                        'time' => (9 + ($session-1)) . ':00-' . (9 + ($session-1)) . ':15',
                        'activity' => 'Short Break & Refresh'
                    ];
                }
            }
        }
        
        // إضافة جلسة مراجعة نهائية في اليوم
        $time_slots[] = [
            'time' => (8 + $study_hours) . ':00-' . (8 + $study_hours) . ':30',
            'activity' => 'Daily Review & Summary'
        ];
        
        return $time_slots;
    }
    
    /**
     * Calculate daily hours based on pages
     */
    public static function calculateDailyHours($pages) {
        $pages_per_hour = 8; // متوسط 8 صفحات في الساعة
        
        if ($pages == 0) {
            return 4; // أيام المراجعة 4 ساعات
        }
        
        $calculated_hours = max(1, ceil($pages / $pages_per_hour));
        return min(6, $calculated_hours); // حد أقصى 6 ساعات
    }
}

// الدالة الرئيسية لإنشاء الأسئلة
function generateAIQuestions($content, $learning_style, $user_id, $file_name = 'uploaded_file.txt') {
    $generator = new AIQuestionGenerator();
    return $generator->generateQuestions($content, $learning_style, $user_id, $file_name);
}

function getUserLearningStyle($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT question_number, answer_value FROM study_behavior_answers WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        $behavior_answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($behavior_answers)) {
            return [
                'planning_style' => 'Organized',
                'problem_solving' => 'Analytical',
                'test_preference' => 'Mixed',
                'learning_type' => 'Visual'
            ];
        }

        $profile = [
            'planning_style' => 'Organized',
            'problem_solving' => 'Analytical', 
            'test_preference' => 'Mixed',
            'learning_type' => 'Visual'
        ];

        return $profile;

    } catch (Exception $e) {
        return [
            'planning_style' => 'Organized',
            'problem_solving' => 'Analytical',
            'test_preference' => 'Mixed',
            'learning_type' => 'Visual'
        ];
    }
}

function getEnglishStyleName($style) {
    $styles = [
        'منظم' => 'Organized',
        'مرن' => 'Flexible', 
        'تعاوني' => 'Collaborative',
        'عفوي' => 'Spontaneous',
        'تحليلي' => 'Analytical',
        'باحث' => 'Research-oriented',
        'مؤجل' => 'Procrastinator',
        'مقالي' => 'Essay-oriented',
        'اختيار متعدد' => 'Multiple Choice',
        'مختلط' => 'Mixed',
        'بصري' => 'Visual',
        'سمعي' => 'Auditory',
        'حركي' => 'Kinesthetic',
        'متوازن' => 'Balanced'
    ];
    
    return $styles[$style] ?? $style;
}

function calculateQuestionCount($content_length, $estimated_pages, $learning_style) {
    $content_units = max(1, $estimated_pages);
    $base_questions = $content_units * 4;
    return min(300, max(50, $base_questions));
}

function calculateExamTime($number_of_questions) {
    $time_per_question = 180;
    $total_time = $number_of_questions * $time_per_question;
    return min(18000, max(900, $total_time));
}

/**
 * Smart study schedule generation function - IMPROVED & OPTIMIZED
 */
function generateSmartSchedule($user_id, $subject_name, $total_pages, $exam_date) {
    global $pdo;
    
    try {
        $learning_style = getUserLearningStyle($user_id);
        
        $today = new DateTime();
        $exam_day = new DateTime($exam_date);
        $days_remaining = $today->diff($exam_day)->days;
        
        if ($days_remaining < 0) {
            throw new Exception("Exam date must be in the future");
        }
        
        $study_days = $days_remaining + 1;
        
        // إذا كان هناك يوم واحد فقط (يوم الاختبار)
        if ($study_days == 0) {
            return [
                'success' => true,
                'schedule_data' => [
                    'total_pages' => 0,
                    'days_remaining' => 1,
                    'daily_hours' => 2,
                    'daily_plan' => [
                        [
                            'date' => $today->format('Y-m-d'),
                            'pages' => 0,
                            'activity' => 'Exam Day - Good Luck!',
                            'time_slots' => [
                                ['time' => '08:00-09:00', 'activity' => 'Final Quick Review'],
                                ['time' => '09:30-10:00', 'activity' => 'Mental Preparation'],
                                ['time' => 'Before Exam', 'activity' => 'Rest & Relax']
                            ]
                        ]
                    ]
                ]
            ];
        }
        
        $daily_plan = [];
        $current_date = clone $today;
        
        // إذا كان هناك يوم واحد فقط قبل الاختبار
        if ($study_days == 1) {
            $daily_plan[] = [
                'date' => $current_date->format('Y-m-d'),
                'pages' => 0,
                'activity' => 'Comprehensive Review Day',
                'time_slots' => ScheduleHelper::generateTimeSlots(0, 'review')
            ];
            
            $daily_hours = 4;
        } 
        else if ($study_days == 2) {
            $pages_day1 = min($total_pages, 15); 
            
            $daily_plan[] = [
                'date' => $current_date->format('Y-m-d'),
                'pages' => $pages_day1,
                'time_slots' => ScheduleHelper::generateTimeSlots($pages_day1, 'study')
            ];
            
            $current_date->modify('+1 day');
            
            if ($current_date->format('Y-m-d') !== $exam_day->format('Y-m-d')) {
                $daily_plan[] = [
                    'date' => $current_date->format('Y-m-d'),
                    'pages' => 0,
                    'activity' => 'Review Day',
                    'time_slots' => ScheduleHelper::generateTimeSlots(0, 'review')
                ];
            }
            
            $daily_hours = 3;
        }
        // إذا كان هناك 3 أيام أو أكثر
        else {
            // 🎯 IMPROVED: حساب أيام الدراسة الفعلية (استبعاد يوم المراجعة)
            $actual_study_days = $study_days - 1; // يوم واحد للمراجعة
            
            // توزيع الصفحات على أيام الدراسة
            $base_pages_per_day = floor($total_pages / $actual_study_days);
            $remaining_pages = $total_pages % $actual_study_days;
            
            // ضمان حد أدنى 10 صفحات في اليوم
            $pages_per_day = max(10, $base_pages_per_day);
            
            // إذا كانت الصفحات قليلة، نوزعها على الأيام المتاحة
            if ($total_pages < ($actual_study_days * 10)) {
                $pages_per_day = 10;
                $remaining_pages = 0;
            }
            
            // إنشاء خطة الدراسة لأيام الدراسة
            for ($i = 0; $i < $actual_study_days; $i++) {
                $today_pages = $pages_per_day;
                
                // توزيع الصفحات المتبقية
                if ($remaining_pages > 0) {
                    $today_pages++;
                    $remaining_pages--;
                }
                
                // لا تتجاوز الصفحات المتبقية
                if ($today_pages > ($total_pages - array_sum(array_column($daily_plan, 'pages')))) {
                    $today_pages = $total_pages - array_sum(array_column($daily_plan, 'pages'));
                }
                
                $daily_plan[] = [
                    'date' => $current_date->format('Y-m-d'),
                    'pages' => $today_pages,
                    'time_slots' => ScheduleHelper::generateTimeSlots($today_pages, 'study')
                ];
                
                $current_date->modify('+1 day');
                
                // إذا انتهت الصفحات، نوقف
                if (array_sum(array_column($daily_plan, 'pages')) >= $total_pages) {
                    break;
                }
            }
            
            // 🎯 يوم المراجعة (آخر يوم قبل الاختبار)
            $daily_plan[] = [
                'date' => $current_date->format('Y-m-d'),
                'pages' => 0,
                'activity' => 'Comprehensive Review Day',
                'time_slots' => ScheduleHelper::generateTimeSlots(0, 'review')
            ];
            
            $daily_hours = ScheduleHelper::calculateDailyHours($pages_per_day);
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO study_schedules 
            (user_id, subject_name, total_pages, exam_date, study_hours, schedule_data, learning_style) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $schedule_data = [
            'total_pages' => $total_pages,
            'days_remaining' => $study_days,
            'daily_hours' => $daily_hours,
            'daily_plan' => $daily_plan
        ];
        
        $stmt->execute([
            $user_id,
            $subject_name,
            $total_pages,
            $exam_date,
            $daily_hours,
            json_encode($schedule_data),
            json_encode($learning_style)
        ]);
        
        return [
            'success' => true,
            'schedule_data' => $schedule_data,
            'schedule_id' => $pdo->lastInsertId()
        ];
        
    } catch (Exception $e) {
        error_log("Schedule Generation Error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => "Failed to generate schedule: " . $e->getMessage()
        ];
    }
}

function getAIHelp($user_question, $study_context = '') {
    $responses = [
        'definition' => [
            "This concept refers to a fundamental principle in the subject matter.",
            "Based on the study material, this topic covers key aspects of the field.",
            "The definition involves understanding the core elements and their relationships."
        ],
        'explanation' => [
            "Let me break this down: the concept works by connecting related ideas.",
            "To understand this better, consider how different components interact.",
            "The key principle here is the relationship between theory and practice."
        ],
        'example' => [
            "For example, you can apply this concept in real-world scenarios.",
            "A practical application would be using this principle to solve problems.",
            "Consider a situation where this knowledge helps analyze complex cases."
        ],
        'help' => [
            "I'm here to help! Try breaking down the problem into smaller parts.",
            "A good approach is to review the key concepts first, then apply them.",
            "Don't worry, let's work through this step by step together."
        ]
    ];
    
    $question_lower = strtolower($user_question);
    
    if (preg_match('/\b(what|define|meaning|definition)\b/', $question_lower)) {
        return $responses['definition'][array_rand($responses['definition'])];
    } elseif (preg_match('/\b(how|explain|why)\b/', $question_lower)) {
        return $responses['explanation'][array_rand($responses['explanation'])];
    } elseif (preg_match('/\b(example|show|demonstrate)\b/', $question_lower)) {
        return $responses['example'][array_rand($responses['example'])];
    } else {
        return $responses['help'][array_rand($responses['help'])];
    }
}

?>