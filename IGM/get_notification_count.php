<?php
session_start();
require_once 'db_config.php';
require_once 'notification_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['unread_count' => 0]);
    exit();
}

$user_id = $_SESSION['user_id'];
$unread_count = $notificationManager->getUnreadCount($user_id);

echo json_encode(['unread_count' => $unread_count]);
?>