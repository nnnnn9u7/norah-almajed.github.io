<?php
require_once 'db_config.php';

class NotificationManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create new notification for user
     */
    public function createNotification($user_id, $title, $message, $type = 'system', $related_id = null, $related_type = null, $scheduled_at = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_notifications 
                (user_id, title, message, type, related_id, related_type, scheduled_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $user_id, 
                $title, 
                $message, 
                $type, 
                $related_id, 
                $related_type,
                $scheduled_at
            ]);
            
            if ($result) {
                error_log("Notification created successfully for user {$user_id}: {$title}");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Notification creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notify about new course
     */
    public function notifyNewCourse($user_id, $course_title, $course_id) {
        $title = "New Course Available";
        $message = "A new course '{$course_title}' has been added. Check it out now!";
        
        return $this->createNotification(
            $user_id, 
            $title, 
            $message, 
            'course', 
            $course_id, 
            'courses'
        );
    }
    
    /**
     * Notify about new hackathon
     */
    public function notifyNewHackathon($user_id, $hackathon_title, $hackathon_id) {
        $title = "New Hackathon Announced";
        $message = "Hackathon '{$hackathon_title}' is available for registration!";
        
        return $this->createNotification(
            $user_id, 
            $title, 
            $message, 
            'hackathon', 
            $hackathon_id, 
            'hackathons'
        );
    }
    
    /**
     * Notify about complaint response
     */
    public function notifyComplaintResponse($user_id, $complaint_id) {
        $title = "Response to Your Support Request";
        $message = "An admin has responded to your support request. Check the response now.";
        
        return $this->createNotification(
            $user_id, 
            $title, 
            $message, 
            'complaint', 
            $complaint_id, 
            'complaints'
        );
    }
    
    /**
     * Notify about upcoming exam
     */
    public function notifyUpcomingExam($user_id, $exam_title, $exam_date) {
        $title = "Upcoming Exam Reminder";
        $message = "You have an exam '{$exam_title}' scheduled on " . date('Y-m-d', strtotime($exam_date));
        
        return $this->createNotification(
            $user_id, 
            $title, 
            $message, 
            'exam', 
            null, 
            null
        );
    }
    
    /**
     * Notify about study time
     */
    public function notifyStudyTime($user_id, $subject, $time) {
        $title = "Study Time Reminder";
        $message = "It's time to study {$subject}. Your study session is scheduled for {$time}.";
        
        return $this->createNotification(
            $user_id, 
            $title, 
            $message, 
            'study', 
            null, 
            null
        );
    }
    
    /**
     * Get unread notifications count
     */
    public function getUnreadCount($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM user_notifications 
                WHERE user_id = ? AND is_read = 0 AND is_dismissed = 0
            ");
            $stmt->execute([$user_id]);
            $count = $stmt->fetchColumn();
            error_log("Unread notifications count for user {$user_id}: {$count}");
            return $count;
        } catch (PDOException $e) {
            error_log("Unread count error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications($user_id, $filter = 'all', $limit = 20, $offset = 0) {
        try {
            $query = "SELECT * FROM user_notifications WHERE user_id = ?";
            $params = [$user_id];

            switch ($filter) {
                case 'unread':
                    $query .= " AND is_read = 0 AND is_dismissed = 0";
                    break;
                case 'read':
                    $query .= " AND is_read = 1 AND is_dismissed = 0";
                    break;
                case 'dismissed':
                    $query .= " AND is_dismissed = 1";
                    break;
                case 'courses':
                    $query .= " AND type = 'course' AND is_dismissed = 0";
                    break;
                case 'hackathons':
                    $query .= " AND type = 'hackathon' AND is_dismissed = 0";
                    break;
                case 'complaints':
                    $query .= " AND type = 'complaint' AND is_dismissed = 0";
                    break;
                case 'exams':
                    $query .= " AND type = 'exam' AND is_dismissed = 0";
                    break;
                default: // 'all'
                    $query .= " AND is_dismissed = 0";
            }

            $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $notifications = $stmt->fetchAll();
            
            error_log("Retrieved " . count($notifications) . " notifications for user {$user_id} with filter '{$filter}'");
            return $notifications;
        } catch (PDOException $e) {
            error_log("Get notifications error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get notification counts by filter
     */
    public function getNotificationCounts($user_id) {
        $counts = [];
        $count_types = ['all', 'unread', 'read', 'dismissed', 'courses', 'hackathons', 'complaints', 'exams'];

        foreach ($count_types as $type) {
            $count_query = "SELECT COUNT(*) FROM user_notifications WHERE user_id = ?";
            
            switch ($type) {
                case 'unread':
                    $count_query .= " AND is_read = 0 AND is_dismissed = 0";
                    break;
                case 'read':
                    $count_query .= " AND is_read = 1 AND is_dismissed = 0";
                    break;
                case 'dismissed':
                    $count_query .= " AND is_dismissed = 1";
                    break;
                case 'courses':
                    $count_query .= " AND type = 'course' AND is_dismissed = 0";
                    break;
                case 'hackathons':
                    $count_query .= " AND type = 'hackathon' AND is_dismissed = 0";
                    break;
                case 'complaints':
                    $count_query .= " AND type = 'complaint' AND is_dismissed = 0";
                    break;
                case 'exams':
                    $count_query .= " AND type = 'exam' AND is_dismissed = 0";
                    break;
                default: // 'all'
                    $count_query .= " AND is_dismissed = 0";
            }
            
            $stmt = $this->pdo->prepare($count_query);
            $stmt->execute([$user_id]);
            $counts[$type] = $stmt->fetchColumn();
        }
        
        error_log("Notification counts for user {$user_id}: " . json_encode($counts));
        return $counts;
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id, $user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE user_notifications 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?
            ");
            $result = $stmt->execute([$notification_id, $user_id]);
            
            if ($result) {
                error_log("Notification {$notification_id} marked as read for user {$user_id}");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Mark as read error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Dismiss notification
     */
    public function dismissNotification($notification_id, $user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE user_notifications 
                SET is_dismissed = 1 
                WHERE id = ? AND user_id = ?
            ");
            $result = $stmt->execute([$notification_id, $user_id]);
            
            if ($result) {
                error_log("Notification {$notification_id} dismissed for user {$user_id}");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Dismiss notification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE user_notifications 
                SET is_read = 1 
                WHERE user_id = ? AND is_read = 0
            ");
            $result = $stmt->execute([$user_id]);
            
            if ($result) {
                $count = $stmt->rowCount();
                error_log("Marked {$count} notifications as read for user {$user_id}");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Mark all as read error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete all read notifications
     */
    public function deleteAllRead($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM user_notifications 
                WHERE user_id = ? AND is_read = 1
            ");
            $result = $stmt->execute([$user_id]);
            
            if ($result) {
                $count = $stmt->rowCount();
                error_log("Deleted {$count} read notifications for user {$user_id}");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Delete all read error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification to all users
     */
    public function notifyAllUsers($title, $message, $type = 'system') {
        try {
            $users = $this->pdo->query("SELECT id FROM users")->fetchAll();
            $count = 0;
            
            foreach ($users as $user) {
                if ($this->createNotification($user['id'], $title, $message, $type)) {
                    $count++;
                }
            }
            
            error_log("Sent notification to {$count} users: {$title}");
            return $count;
        } catch (PDOException $e) {
            error_log("Notify all users error: " . $e->getMessage());
            return 0;
        }
    }
}

// Create notification manager object
$notificationManager = new NotificationManager($pdo);

// Load confirmation message
error_log("NotificationManager class loaded successfully");
?>