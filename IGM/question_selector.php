<?php

require_once 'question_bank.php';

class QuestionSelector {
    
    private $questionBank;
    
    public function __construct() {
        $this->questionBank = new QuestionBank();
    }
    
    public function selectQuestions($content_analysis, $total_questions) {
        error_log("🎯 Selecting {$total_questions} questions from question bank");
        
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        
        $distribution = $this->calculateBalancedDistribution($total_questions);
        
        error_log("📊 Distribution - Easy: {$distribution['easy']}, Medium: {$distribution['medium']}, Hard: {$distribution['hard']}");
        
        $questions = $this->questionBank->generateQuestions($content_analysis, $total_questions);
        
        $questions = $this->ensureDistribution($questions, $distribution);
        
        $questions = $this->assignSequentialOrder($questions);
        
        error_log("✅ Successfully selected {$total_questions} questions with balanced distribution");
        
        return $questions;
    }
    
    private function calculateBalancedDistribution($total) {
        $easy = (int)round($total * 0.30);
        $medium = (int)round($total * 0.40);
        $hard = $total - $easy - $medium;
        
        return [
            'easy' => max(1, $easy),
            'medium' => max(1, $medium),
            'hard' => max(1, $hard)
        ];
    }
    
    private function ensureDistribution($questions, $target_distribution) {
        $current_distribution = [
            'Easy' => 0,
            'Medium' => 0,
            'Hard' => 0
        ];
        
        foreach ($questions as $q) {
            if (isset($current_distribution[$q['difficulty']])) {
                $current_distribution[$q['difficulty']]++;
            }
        }
        
        error_log("📈 Current: Easy={$current_distribution['Easy']}, Medium={$current_distribution['Medium']}, Hard={$current_distribution['Hard']}");
        error_log("🎯 Target: Easy={$target_distribution['easy']}, Medium={$target_distribution['medium']}, Hard={$target_distribution['hard']}");
        
        if ($current_distribution['Easy'] == $target_distribution['easy'] && 
            $current_distribution['Medium'] == $target_distribution['medium'] && 
            $current_distribution['Hard'] == $target_distribution['hard']) {
            error_log("✅ Distribution already balanced!");
            return $questions;
        }
        
        $easy_questions = [];
        $medium_questions = [];
        $hard_questions = [];
        
        foreach ($questions as $question) {
            switch ($question['difficulty']) {
                case 'Easy':
                    $easy_questions[] = $question;
                    break;
                case 'Medium':
                    $medium_questions[] = $question;
                    break;
                case 'Hard':
                    $hard_questions[] = $question;
                    break;
            }
        }
        
        $balanced_questions = [];
        
        $easy_needed = $target_distribution['easy'];
        $medium_needed = $target_distribution['medium'];
        $hard_needed = $target_distribution['hard'];
        
        for ($i = 0; $i < $easy_needed && $i < count($easy_questions); $i++) {
            $balanced_questions[] = $easy_questions[$i];
        }
        
        for ($i = 0; $i < $medium_needed && $i < count($medium_questions); $i++) {
            $balanced_questions[] = $medium_questions[$i];
        }
        
        for ($i = 0; $i < $hard_needed && $i < count($hard_questions); $i++) {
            $balanced_questions[] = $hard_questions[$i];
        }
        
        error_log("✅ Final balanced - Easy: " . min($easy_needed, count($easy_questions)) . ", Medium: " . min($medium_needed, count($medium_questions)) . ", Hard: " . min($hard_needed, count($hard_questions)));
        
        return $balanced_questions;
    }
    
    private function assignSequentialOrder($questions) {
        $ordered = [];
        
        $easy_questions = array_filter($questions, function($q) {
            return $q['difficulty'] === 'Easy';
        });
        $medium_questions = array_filter($questions, function($q) {
            return $q['difficulty'] === 'Medium';
        });
        $hard_questions = array_filter($questions, function($q) {
            return $q['difficulty'] === 'Hard';
        });
        
        shuffle($easy_questions);
        shuffle($medium_questions);
        shuffle($hard_questions);
        
        $easy_questions = array_values($easy_questions);
        $medium_questions = array_values($medium_questions);
        $hard_questions = array_values($hard_questions);
        
        $e_idx = 0;
        $m_idx = 0;
        $h_idx = 0;
        
        $pattern = ['Easy', 'Easy', 'Medium', 'Easy', 'Medium', 'Hard', 'Medium', 'Hard', 'Medium', 'Hard'];
        $pattern_index = 0;
        
        while (count($ordered) < count($questions)) {
            $next_difficulty = $pattern[$pattern_index % count($pattern)];
            
            if ($next_difficulty === 'Easy' && $e_idx < count($easy_questions)) {
                $ordered[] = $easy_questions[$e_idx++];
            } elseif ($next_difficulty === 'Medium' && $m_idx < count($medium_questions)) {
                $ordered[] = $medium_questions[$m_idx++];
            } elseif ($next_difficulty === 'Hard' && $h_idx < count($hard_questions)) {
                $ordered[] = $hard_questions[$h_idx++];
            } else {
                if ($e_idx < count($easy_questions)) {
                    $ordered[] = $easy_questions[$e_idx++];
                } elseif ($m_idx < count($medium_questions)) {
                    $ordered[] = $medium_questions[$m_idx++];
                } elseif ($h_idx < count($hard_questions)) {
                    $ordered[] = $hard_questions[$h_idx++];
                }
            }
            
            $pattern_index++;
        }
        
        return $ordered;
    }
}

?>
