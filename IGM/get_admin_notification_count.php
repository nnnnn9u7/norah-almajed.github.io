<?php
session_start();
require_once 'db_config.php';
require_once 'admin_notification_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['unread_count' => 0]);
    exit();
}

$admin_id = $_SESSION['admin_id'];
$unread_count = $adminNotificationManager->getUnreadCount($admin_id);

echo json_encode(['unread_count' => $unread_count]);
?>