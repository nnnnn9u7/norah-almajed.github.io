<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$schedule_id = $data['schedule_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$schedule_id) {
    echo json_encode(['success' => false, 'message' => 'Schedule ID is required']);
    exit();
}

try {
    // Verify that the schedule belongs to current user
    $stmt = $pdo->prepare("SELECT user_id FROM study_schedules WHERE id = ?");
    $stmt->execute([$schedule_id]);
    $schedule = $stmt->fetch();
    
    if (!$schedule) {
        echo json_encode(['success' => false, 'message' => 'Schedule not found']);
        exit();
    }
    
    if ($schedule['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized to delete this schedule']);
        exit();
    }
    
    // Delete schedule
    $stmt = $pdo->prepare("DELETE FROM study_schedules WHERE id = ? AND user_id = ?");
    $stmt->execute([$schedule_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Schedule deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete schedule']);
    }
    
} catch (Exception $e) {
    error_log("Delete schedule error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>