<?php
session_start();
require_once 'db_config.php';

// التحقق من صلاحية المدير
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $complaint_id = $input['complaint_id'] ?? null;
    $status = $input['status'] ?? null;
    
    if ($complaint_id && $status) {
        try {
            $stmt = $pdo->prepare("UPDATE contact_messages SET status = ?, responded_by = ?, responded_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $_SESSION['admin_id'], $complaint_id]);
            
            echo json_encode(['success' => true, 'message' => 'Complaint status updated successfully']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>