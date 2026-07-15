<?php

class QuestionBank {
    
    private $questionTemplates = [
        'Easy' => [
            [
                'template' => 'What is the definition of {concept}?',
                'type' => 'definition',
                'points' => 2,
                'time_limit' => 60
            ],
            [
                'template' => '{concept} refers to:',
                'type' => 'identification',
                'points' => 2,
                'time_limit' => 60
            ],
            [
                'template' => 'Which of the following best describes {concept}?',
                'type' => 'description',
                'points' => 2,
                'time_limit' => 60
            ],
            [
                'template' => 'The main purpose of {concept} is:',
                'type' => 'purpose',
                'points' => 2,
                'time_limit' => 60
            ],
            [
                'template' => '{concept} is used for:',
                'type' => 'application',
                'points' => 2,
                'time_limit' => 60
            ],
            [
                'template' => 'What does {concept} mean?',
                'type' => 'meaning',
                'points' => 2,
                'time_limit' => 60
            ],
            [
                'template' => 'Identify the correct statement about {concept}:',
                'type' => 'identification',
                'points' => 2,
                'time_limit' => 60
            ],
            [
                'template' => '{concept} can be defined as:',
                'type' => 'definition',
                'points' => 2,
                'time_limit' => 60
            ]
        ],
        'Medium' => [
            [
                'template' => 'How does {concept1} relate to {concept2}?',
                'type' => 'relationship',
                'points' => 3,
                'time_limit' => 90
            ],
            [
                'template' => 'What is the main difference between {concept1} and {concept2}?',
                'type' => 'comparison',
                'points' => 3,
                'time_limit' => 90
            ],
            [
                'template' => 'In the context of {topic}, {concept} is best understood as:',
                'type' => 'contextual',
                'points' => 3,
                'time_limit' => 90
            ],
            [
                'template' => 'Apply {concept} to solve the following situation:',
                'type' => 'application',
                'points' => 3,
                'time_limit' => 90
            ],
            [
                'template' => 'Which factor most influences {concept}?',
                'type' => 'analysis',
                'points' => 3,
                'time_limit' => 90
            ],
            [
                'template' => 'The relationship between {concept1} and {concept2} demonstrates:',
                'type' => 'relationship',
                'points' => 3,
                'time_limit' => 90
            ],
            [
                'template' => 'When implementing {concept}, which approach is most effective?',
                'type' => 'strategy',
                'points' => 3,
                'time_limit' => 90
            ],
            [
                'template' => 'Compare and contrast {concept1} with {concept2}:',
                'type' => 'comparison',
                'points' => 3,
                'time_limit' => 90
            ],
            [
                'template' => 'What happens when {concept} is applied to {situation}?',
                'type' => 'application',
                'points' => 3,
                'time_limit' => 90
            ],
            [
                'template' => 'Explain how {concept} works in practice:',
                'type' => 'explanation',
                'points' => 3,
                'time_limit' => 90
            ]
        ],
        'Hard' => [
            [
                'template' => 'Analyze the implications of {concept} in the context of {situation}:',
                'type' => 'analysis',
                'points' => 5,
                'time_limit' => 120
            ],
            [
                'template' => 'Evaluate the effectiveness of {concept} when combined with {concept2}:',
                'type' => 'evaluation',
                'points' => 5,
                'time_limit' => 120
            ],
            [
                'template' => 'Synthesize the relationship between {concept1}, {concept2}, and {concept3}:',
                'type' => 'synthesis',
                'points' => 5,
                'time_limit' => 120
            ],
            [
                'template' => 'Critically assess the strengths and weaknesses of {concept}:',
                'type' => 'critical_thinking',
                'points' => 5,
                'time_limit' => 120
            ],
            [
                'template' => 'Design a solution using {concept} to address {problem}:',
                'type' => 'problem_solving',
                'points' => 5,
                'time_limit' => 120
            ],
            [
                'template' => 'What are the long-term consequences of implementing {concept}?',
                'type' => 'prediction',
                'points' => 5,
                'time_limit' => 120
            ],
            [
                'template' => 'Justify the use of {concept} over alternative approaches:',
                'type' => 'justification',
                'points' => 5,
                'time_limit' => 120
            ],
            [
                'template' => 'Integrate {concept1} and {concept2} to create a comprehensive understanding:',
                'type' => 'integration',
                'points' => 5,
                'time_limit' => 120
            ]
        ]
    ];

    public function generateQuestions($content_analysis, $total_questions) {
        error_log("=== Starting Question Generation ===");
        error_log("Total questions requested: $total_questions");
        
        $questions = [];
        
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        
        $concepts = $this->extractConcepts($content_analysis);
        
        if (empty($concepts)) {
            error_log("⚠️ No concepts extracted, using alternative sources");
            $concepts = array_merge(
                $content_analysis['key_concepts'] ?? [],
                array_keys($content_analysis['definitions'] ?? []),
                ['concept', 'topic', 'subject']
            );
        }
        
        error_log("📚 Extracted " . count($concepts) . " concepts");
        
        $easy_questions = $this->generateEasyQuestions($concepts, $distribution['easy'], $content_analysis);
        $medium_questions = $this->generateMediumQuestions($concepts, $distribution['medium'], $content_analysis);
        $hard_questions = $this->generateHardQuestions($concepts, $distribution['hard'], $content_analysis);
        
        error_log("Generated breakdown - Easy: " . count($easy_questions) . " (target: {$distribution['easy']}), Medium: " . count($medium_questions) . " (target: {$distribution['medium']}), Hard: " . count($hard_questions) . " (target: {$distribution['hard']})");
        
        if (count($easy_questions) < $distribution['easy']) {
            error_log("⚠️ Not enough Easy questions! Generated: " . count($easy_questions) . ", Needed: {$distribution['easy']}");
        }
        
        if (count($medium_questions) < $distribution['medium']) {
            error_log("⚠️ Not enough Medium questions! Generated: " . count($medium_questions) . ", Needed: {$distribution['medium']}");
        }
        
        if (count($hard_questions) < $distribution['hard']) {
            error_log("⚠️ Not enough Hard questions! Generated: " . count($hard_questions) . ", Needed: {$distribution['hard']}");
        }
        
        $questions = array_merge($easy_questions, $medium_questions, $hard_questions);
        
        $questions = $this->removeDuplicateQuestions($questions);
        
        $questions = $this->validateQuestions($questions, $content_analysis);
        
        error_log("After validation: " . count($questions) . " valid questions");
        
        if (count($questions) < $total_questions) {
            error_log("⚠️ Not enough valid questions (" . count($questions) . " < $total_questions)");
        }
        
        shuffle($questions);
        
        $final_questions = array_slice($questions, 0, $total_questions);
        
        error_log("=== Final Question Count: " . count($final_questions) . " ===");
        
        return $final_questions;
    }
    
    private function removeDuplicateQuestions($questions) {
        $unique_questions = [];
        $seen_questions = [];
        
        foreach ($questions as $question) {
            $question_key = strtolower(trim($question['question']));
            
            if (!in_array($question_key, $seen_questions)) {
                $seen_questions[] = $question_key;
                $unique_questions[] = $question;
            }
        }
        
        error_log("📊 Removed " . (count($questions) - count($unique_questions)) . " duplicate questions");
        
        return $unique_questions;
    }
    
    private function validateQuestions($questions, $content_analysis) {
        $valid_questions = [];
        $invalid_by_difficulty = ['Easy' => 0, 'Medium' => 0, 'Hard' => 0];
        
        foreach ($questions as $question) {
            if ($this->isQuestionValid($question, $content_analysis)) {
                $valid_questions[] = $question;
            } else {
                $difficulty = $question['difficulty'] ?? 'Unknown';
                if (isset($invalid_by_difficulty[$difficulty])) {
                    $invalid_by_difficulty[$difficulty]++;
                }
                error_log("❌ Invalid question ({$difficulty}): " . substr($question['question'], 0, 50));
            }
        }
        
        error_log("✅ Validated " . count($valid_questions) . " out of " . count($questions) . " questions");
        error_log("❌ Invalid by difficulty - Easy: {$invalid_by_difficulty['Easy']}, Medium: {$invalid_by_difficulty['Medium']}, Hard: {$invalid_by_difficulty['Hard']}");
        
        return $valid_questions;
    }
    
    private function isQuestionValid($question, $content_analysis) {
        if (empty($question['question']) || strlen($question['question']) < 10) {
            error_log("   Reason: Question text too short or empty");
            return false;
        }
        
        if (empty($question['options']) || count($question['options']) < 4) {
            error_log("   Reason: Not enough options (" . count($question['options'] ?? []) . " < 4)");
            return false;
        }
        
        if (!isset($question['correct_answer']) || $question['correct_answer'] < 0 || $question['correct_answer'] > 3) {
            error_log("   Reason: Invalid correct_answer index");
            return false;
        }
        
        $options = $question['options'];
        if (count(array_unique($options)) < count($options)) {
            error_log("   Reason: Duplicate options");
            return false;
        }
        
        return true;
    }
    
    private function calculateDistribution($total) {
        $easy = (int)round($total * 0.25);
        $medium = (int)round($total * 0.45);
        $hard = $total - $easy - $medium;
        
        if ($hard < 2 && $total >= 5) {
            $hard = max(2, (int)ceil($total * 0.30));
            $remaining = $total - $hard;
            $easy = max(1, (int)round($remaining * 0.35));
            $medium = $remaining - $easy;
        }
        
        error_log("📊 Distribution calculated - Total: $total, Easy: $easy (25%), Medium: $medium (45%), Hard: $hard (30%)");
        
        return [
            'easy' => max(1, $easy),
            'medium' => max(1, $medium),
            'hard' => max(2, $hard)
        ];
    }
    
    private function extractConcepts($content_analysis) {
        $concepts = array_merge(
            $content_analysis['key_concepts'] ?? [],
            array_column($content_analysis['definitions'] ?? [], 'term'),
            $content_analysis['topics'] ?? []
        );
        
        return array_unique(array_filter($concepts));
    }
    
    private function generateEasyQuestions($concepts, $count, $content_analysis) {
        $questions = [];
        $templates = $this->questionTemplates['Easy'];
        $definitions = $content_analysis['definitions'] ?? [];
        $sentences = $content_analysis['sentences'] ?? [];
        $key_concepts = $content_analysis['key_concepts'] ?? [];
        
        $def_index = 0;
        $attempts = 0;
        $max_attempts = max($count * 5, 100);
        
        while (count($questions) < $count && $attempts < $max_attempts) {
            $attempts++;
            
            if ($def_index < count($definitions)) {
                $def = $definitions[$def_index];
                $question_text = "What is " . $def['term'] . "?";
                
                $correct_option = $def['definition'];
                
                $wrong_options = [];
                foreach ($definitions as $other_def) {
                    if ($other_def['term'] !== $def['term'] && count($wrong_options) < 3) {
                        $wrong_options[] = $other_def['definition'];
                    }
                }
                
                while (count($wrong_options) < 3) {
                    if (!empty($sentences)) {
                        $random_sentence = $sentences[array_rand($sentences)];
                        if (!in_array($random_sentence, $wrong_options)) {
                            $wrong_options[] = substr($random_sentence, 0, 120);
                        }
                    } else {
                        $wrong_options[] = "Not mentioned in the material - option " . (count($wrong_options) + 1);
                    }
                }
                
                $options = array_merge([$correct_option], array_slice($wrong_options, 0, 3));
                shuffle($options);
                $correct_index = array_search($correct_option, $options);
                
                if ($correct_index !== false) {
                    $questions[] = [
                        'question' => $question_text,
                        'options' => $options,
                        'correct_answer' => $correct_index,
                        'type' => 'multiple_choice',
                        'difficulty' => 'Easy',
                        'page_unit' => '1',
                        'points' => 2,
                        'time_limit' => 60
                    ];
                }
                
                $def_index++;
            } else {
                if (!empty($concepts)) {
                    $concept = $concepts[array_rand($concepts)];
                    $template = $templates[array_rand($templates)];
                    $question_text = str_replace('{concept}', $concept, $template['template']);
                    
                    $options = $this->generateOptions($concept, 'Easy', $content_analysis);
                    
                    if (!empty($options['options']) && count($options['options']) >= 4) {
                        $questions[] = [
                            'question' => $question_text,
                            'options' => $options['options'],
                            'correct_answer' => $options['correct'],
                            'type' => 'multiple_choice',
                            'difficulty' => 'Easy',
                            'page_unit' => '1',
                            'points' => 2,
                            'time_limit' => 60
                        ];
                    }
                } else if (!empty($key_concepts)) {
                    $concept = $key_concepts[array_rand($key_concepts)];
                    $question_text = "What is the main idea related to " . $concept . "?";
                    
                    $options = $this->generateOptions($concept, 'Easy', $content_analysis);
                    
                    if (!empty($options['options']) && count($options['options']) >= 4) {
                        $questions[] = [
                            'question' => $question_text,
                            'options' => $options['options'],
                            'correct_answer' => $options['correct'],
                            'type' => 'multiple_choice',
                            'difficulty' => 'Easy',
                            'page_unit' => '1',
                            'points' => 2,
                            'time_limit' => 60
                        ];
                    }
                }
            }
        }
        
        if (count($questions) < $count) {
            error_log("⚠️ Easy questions: Only generated " . count($questions) . " out of $count (attempts: $attempts/$max_attempts)");
        } else {
            error_log("📝 Generated " . count($questions) . " Easy questions (requested: $count)");
        }
        
        return $questions;
    }
    
    private function generateMediumQuestions($concepts, $count, $content_analysis) {
        $questions = [];
        $templates = $this->questionTemplates['Medium'];
        $examples = $content_analysis['examples'] ?? [];
        $lists = $content_analysis['lists'] ?? [];
        $paragraphs = $content_analysis['paragraphs'] ?? [];
        
        $example_index = 0;
        $attempts = 0;
        $max_attempts = max($count * 5, 100);
        
        while (count($questions) < $count && $attempts < $max_attempts) {
            $attempts++;
            
            if ($example_index < count($examples)) {
                $example = $examples[$example_index];
                $question_text = "Based on the example: \"" . substr($example, 0, 60) . "...\", what principle is demonstrated?";
                
                $correct_option = substr($example, 0, 120);
                
                $wrong_options = [];
                foreach ($examples as $other_example) {
                    if ($other_example !== $example && count($wrong_options) < 3) {
                        $wrong_options[] = substr($other_example, 0, 120);
                    }
                }
                
                foreach ($lists as $list_item) {
                    if (count($wrong_options) < 3 && !in_array($list_item, $wrong_options)) {
                        $wrong_options[] = substr($list_item, 0, 120);
                    }
                }
                
                while (count($wrong_options) < 3) {
                    if (!empty($paragraphs)) {
                        $random_para = $paragraphs[array_rand($paragraphs)];
                        if (!in_array($random_para, $wrong_options)) {
                            $wrong_options[] = substr($random_para, 0, 120);
                        }
                    } else {
                        $wrong_options[] = "Not discussed in the material - option " . (count($wrong_options) + 1);
                    }
                }
                
                $options = array_merge([$correct_option], array_slice($wrong_options, 0, 3));
                shuffle($options);
                $correct_index = array_search($correct_option, $options);
                
                if ($correct_index !== false) {
                    $questions[] = [
                        'question' => $question_text,
                        'options' => $options,
                        'correct_answer' => $correct_index,
                        'type' => 'multiple_choice',
                        'difficulty' => 'Medium',
                        'page_unit' => '1',
                        'points' => 3,
                        'time_limit' => 90
                    ];
                }
                
                $example_index++;
            } else {
                if (!empty($concepts)) {
                    $concept1 = $concepts[array_rand($concepts)];
                    $concept2 = $concepts[array_rand($concepts)];
                    $template = $templates[array_rand($templates)];
                    
                    $question_text = str_replace(
                        ['{concept}', '{concept1}', '{concept2}', '{topic}', '{situation}'],
                        [$concept1, $concept1, $concept2, $concept1, $concept2],
                        $template['template']
                    );
                    
                    $options = $this->generateOptions($concept1, 'Medium', $content_analysis);
                    
                    if (!empty($options['options']) && count($options['options']) >= 4) {
                        $questions[] = [
                            'question' => $question_text,
                            'options' => $options['options'],
                            'correct_answer' => $options['correct'],
                            'type' => 'multiple_choice',
                            'difficulty' => 'Medium',
                            'page_unit' => '1',
                            'points' => 3,
                            'time_limit' => 90
                        ];
                    }
                } else if (!empty($lists)) {
                    $list_item = $lists[array_rand($lists)];
                    $question_text = "According to the material, which statement about \"" . substr($list_item, 0, 40) . "...\" is correct?";
                    
                    $options = $this->generateOptions($list_item, 'Medium', $content_analysis);
                    
                    if (!empty($options['options']) && count($options['options']) >= 4) {
                        $questions[] = [
                            'question' => $question_text,
                            'options' => $options['options'],
                            'correct_answer' => $options['correct'],
                            'type' => 'multiple_choice',
                            'difficulty' => 'Medium',
                            'page_unit' => '1',
                            'points' => 3,
                            'time_limit' => 90
                        ];
                    }
                }
            }
        }
        
        if (count($questions) < $count) {
            error_log("⚠️ Medium questions: Only generated " . count($questions) . " out of $count (attempts: $attempts/$max_attempts)");
        } else {
            error_log("📝 Generated " . count($questions) . " Medium questions (requested: $count)");
        }
        
        return $questions;
    }
    
    private function generateHardQuestions($concepts, $count, $content_analysis) {
        $questions = [];
        $templates = $this->questionTemplates['Hard'];
        $paragraphs = $content_analysis['paragraphs'] ?? [];
        $formulas = $content_analysis['formulas'] ?? [];
        $sentences = $content_analysis['sentences'] ?? [];
        
        $para_index = 0;
        $attempts = 0;
        $max_attempts = max($count * 5, 100);
        
        while (count($questions) < $count && $attempts < $max_attempts) {
            $attempts++;
            
            if ($para_index < count($paragraphs)) {
                $paragraph = $paragraphs[$para_index];
                $question_text = "Analyze the following statement from the material: \"" . substr($paragraph, 0, 80) . "...\" What is the main implication?";
                
                $correct_option = substr($paragraph, 0, 150);
                
                $wrong_options = [];
                foreach ($paragraphs as $other_para) {
                    if ($other_para !== $paragraph && count($wrong_options) < 3) {
                        $wrong_options[] = substr($other_para, 0, 150);
                    }
                }
                
                while (count($wrong_options) < 3) {
                    if (!empty($sentences)) {
                        $random_sent = $sentences[array_rand($sentences)];
                        if (!in_array($random_sent, $wrong_options)) {
                            $wrong_options[] = substr($random_sent, 0, 150);
                        }
                    } else {
                        $wrong_options[] = "This aspect is not covered in the material - option " . (count($wrong_options) + 1);
                    }
                }
                
                $options = array_merge([$correct_option], array_slice($wrong_options, 0, 3));
                shuffle($options);
                $correct_index = array_search($correct_option, $options);
                
                if ($correct_index !== false) {
                    $questions[] = [
                        'question' => $question_text,
                        'options' => $options,
                        'correct_answer' => $correct_index,
                        'type' => 'multiple_choice',
                        'difficulty' => 'Hard',
                        'page_unit' => '1',
                        'points' => 5,
                        'time_limit' => 120
                    ];
                }
                
                $para_index++;
            } else {
                if (!empty($concepts)) {
                    $concept1 = $concepts[array_rand($concepts)];
                    $concept2 = $concepts[array_rand($concepts)];
                    $concept3 = $concepts[array_rand($concepts)];
                    $template = $templates[array_rand($templates)];
                    
                    $question_text = str_replace(
                        ['{concept}', '{concept1}', '{concept2}', '{concept3}', '{situation}', '{problem}'],
                        [$concept1, $concept1, $concept2, $concept3, $concept2, $concept3],
                        $template['template']
                    );
                    
                    $options = $this->generateOptions($concept1, 'Hard', $content_analysis);
                    
                    if (!empty($options['options']) && count($options['options']) >= 4) {
                        $questions[] = [
                            'question' => $question_text,
                            'options' => $options['options'],
                            'correct_answer' => $options['correct'],
                            'type' => 'multiple_choice',
                            'difficulty' => 'Hard',
                            'page_unit' => '1',
                            'points' => 5,
                            'time_limit' => 120
                        ];
                    }
                } else if (!empty($sentences)) {
                    $sentence = $sentences[array_rand($sentences)];
                    $question_text = "What critical conclusion can be drawn from: \"" . substr($sentence, 0, 70) . "...\"?";
                    
                    $options = $this->generateOptions($sentence, 'Hard', $content_analysis);
                    
                    if (!empty($options['options']) && count($options['options']) >= 4) {
                        $questions[] = [
                            'question' => $question_text,
                            'options' => $options['options'],
                            'correct_answer' => $options['correct'],
                            'type' => 'multiple_choice',
                            'difficulty' => 'Hard',
                            'page_unit' => '1',
                            'points' => 5,
                            'time_limit' => 120
                        ];
                    }
                }
            }
        }
        
        if (count($questions) < $count) {
            error_log("⚠️ Hard questions: Only generated " . count($questions) . " out of $count (attempts: $attempts/$max_attempts)");
        } else {
            error_log("📝 Generated " . count($questions) . " Hard questions (requested: $count)");
        }
        
        return $questions;
    }
    
    private function generateOptions($concept, $difficulty, $content_analysis) {
        $all_concepts = $this->extractConcepts($content_analysis);
        
        $correct_option = $this->generateCorrectOption($concept, $difficulty, $content_analysis);
        
        $wrong_options = [];
        $attempts = 0;
        while (count($wrong_options) < 3 && $attempts < 20) {
            $random_concept = $all_concepts[array_rand($all_concepts)];
            $wrong_option = $this->generateWrongOption($random_concept, $difficulty, $content_analysis);
            
            if (!in_array($wrong_option, $wrong_options) && $wrong_option !== $correct_option) {
                $wrong_options[] = $wrong_option;
            }
            $attempts++;
        }
        
        while (count($wrong_options) < 3) {
            $wrong_options[] = $this->generateGenericWrongOption($difficulty, $content_analysis);
        }
        
        $options = array_merge([$correct_option], $wrong_options);
        shuffle($options);
        
        $correct_index = array_search($correct_option, $options);
        
        return [
            'options' => $options,
            'correct' => $correct_index
        ];
    }
    
    private function generateCorrectOption($concept, $difficulty, $content_analysis) {
        $definitions = $content_analysis['definitions'] ?? [];
        
        foreach ($definitions as $def) {
            if (stripos($def['term'], $concept) !== false || stripos($concept, $def['term']) !== false) {
                return substr($def['definition'], 0, 150);
            }
        }
        
        $examples = $content_analysis['examples'] ?? [];
        foreach ($examples as $example) {
            if (stripos($example, $concept) !== false) {
                return substr($example, 0, 150);
            }
        }
        
        $lists = $content_analysis['lists'] ?? [];
        foreach ($lists as $list_item) {
            if (stripos($list_item, $concept) !== false) {
                return substr($list_item, 0, 150);
            }
        }
        
        $topics = $content_analysis['topics'] ?? [];
        foreach ($topics as $topic) {
            if (stripos($topic, $concept) !== false) {
                return substr($topic, 0, 150);
            }
        }
        
        return "Related to " . $concept . " as mentioned in the study material";
    }
    
    private function generateWrongOption($concept, $difficulty, $content_analysis) {
        $all_definitions = $content_analysis['definitions'] ?? [];
        $all_examples = $content_analysis['examples'] ?? [];
        $all_lists = $content_analysis['lists'] ?? [];
        
        $wrong_options = [];
        
        foreach ($all_definitions as $def) {
            if (stripos($def['term'], $concept) === false && stripos($concept, $def['term']) === false) {
                $wrong_options[] = substr($def['definition'], 0, 120);
            }
        }
        
        foreach ($all_examples as $example) {
            if (stripos($example, $concept) === false && strlen($example) > 30) {
                $wrong_options[] = substr($example, 0, 120);
            }
        }
        
        foreach ($all_lists as $list_item) {
            if (stripos($list_item, $concept) === false && strlen($list_item) > 20) {
                $wrong_options[] = substr($list_item, 0, 120);
            }
        }
        
        if (!empty($wrong_options)) {
            return $wrong_options[array_rand($wrong_options)];
        }
        
        return "Not mentioned in the provided material";
    }
    
    private function generateGenericWrongOption($difficulty, $content_analysis) {
        $all_concepts = $content_analysis['key_concepts'] ?? [];
        
        if (!empty($all_concepts)) {
            $random_concept = $all_concepts[array_rand($all_concepts)];
            return "Refers to " . $random_concept . " which is different";
        }
        
        return "Not found in the study material";
    }
}

?>
