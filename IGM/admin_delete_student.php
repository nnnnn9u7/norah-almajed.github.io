<?php
session_start();
require_once 'db_config.php';

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Check if user has permission
if ($_SESSION['admin_role'] != 'super_admin') {
    header("Location: admin_students.php?error=Permission denied");
    exit();
}

// Get student ID
$student_id = $_GET['id'] ?? 0;

if ($student_id > 0) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete related records first
        $pdo->prepare("DELETE FROM exam_sessions WHERE user_id = ?")->execute([$student_id]);
        $pdo->prepare("DELETE FROM study_schedules WHERE user_id = ?")->execute([$student_id]);
        $pdo->prepare("DELETE FROM ai_generated_questions WHERE user_id = ?")->execute([$student_id]);
        $pdo->prepare("DELETE FROM study_behavior_answers WHERE user_id = ?")->execute([$student_id]);
        $pdo->prepare("DELETE FROM study_logs WHERE user_id = ?")->execute([$student_id]);
        
        // Delete student
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$student_id]);
        
        $pdo->commit();
        
        header("Location: admin_students.php?success=Student deleted successfully");
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        header("Location: admin_students.php?error=Error deleting student");
        exit();
    }
} else {
    header("Location: admin_students.php");
    exit();
}
?>