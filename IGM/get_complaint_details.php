<?php
session_start();
require_once 'db_config.php';

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Complaint ID required']);
    exit();
}

$complaint_id = (int)$_GET['id'];

try {
    $query = "
        SELECT cm.*, u.full_name as user_full_name, u.email as user_email, u.phone as user_phone, 
               a.username as responded_by_name 
        FROM contact_messages cm 
        LEFT JOIN users u ON cm.user_id = u.id 
        LEFT JOIN admin_users a ON cm.responded_by = a.id 
        WHERE cm.id = ?
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$complaint_id]);
    $complaint = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($complaint) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'complaint' => $complaint]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Complaint not found']);
    }
    
} catch (PDOException $e) {
    error_log("Database error in get_complaint.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>