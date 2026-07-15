<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$date = $input['date'] ?? '';
$schedule_id = $input['schedule_id'] ?? 0;

if (empty($date) || $schedule_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Incomplete data']);
    exit();
}

try {
    // Check if record already exists
    $stmt = $pdo->prepare("SELECT id FROM study_logs WHERE user_id = ? AND schedule_id = ? AND study_date = ?");
    $stmt->execute([$user_id, $schedule_id, $date]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update existing record
        $stmt = $pdo->prepare("
            UPDATE study_logs 
            SET pages_studied = pages_studied + 1, 
                study_minutes = study_minutes + 30,
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$existing['id']]);
    } else {
        // Create new record
        $stmt = $pdo->prepare("
            INSERT INTO study_logs (user_id, schedule_id, study_date, pages_studied, study_minutes) 
            VALUES (?, ?, ?, 1, 30)
        ");
        $stmt->execute([$user_id, $schedule_id, $date]);
    }

    echo json_encode(['success' => true, 'message' => 'Study session recorded successfully']);

} catch (Exception $e) {
    error_log("Error marking study day: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>