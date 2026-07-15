<?php
require_once 'db_config.php';

class AdminNotificationManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * إنشاء إشعار جديد للإدمن
     */
    public function createNotification($admin_id, $title, $message, $type = 'system', $related_id = null, $related_type = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO admin_notifications 
                (admin_id, title, message, type, related_id, related_type) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $admin_id, 
                $title, 
                $message, 
                $type, 
                $related_id, 
                $related_type
            ]);
        } catch (PDOException $e) {
            error_log("Admin notification creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * إشعار تسجيل مستخدم جديد
     */
    public function notifyNewUser($admin_id, $username, $user_id) {
        $title = "New User Registration";
        $message = "User {$username} has registered in the system.";
        
        return $this->createNotification(
            $admin_id, 
            $title, 
            $message, 
            'new_user', 
            $user_id, 
            'users'
        );
    }
    
    /**
     * إشعار شكوى جديدة
     */
    public function notifyNewComplaint($admin_id, $complaint_id) {
        $title = "New Support Request";
        $message = "A new support request has been submitted.";
        
        return $this->createNotification(
            $admin_id, 
            $title, 
            $message, 
            'new_complaint', 
            $complaint_id, 
            'complaints'
        );
    }
    
    /**
     * إشعار تحذير نظام
     */
    public function notifySystemWarning($admin_id, $warning_message) {
        $title = "System Warning";
        $message = $warning_message;
        
        return $this->createNotification(
            $admin_id, 
            $title, 
            $message, 
            'warning', 
            null, 
            null
        );
    }
    
    /**
     * إشعار تقرير جاهز
     */
    public function notifyReportReady($admin_id, $report_type) {
        $title = "Report Ready";
        $message = "{$report_type} report is ready for review.";
        
        return $this->createNotification(
            $admin_id, 
            $title, 
            $message, 
            'report', 
            null, 
            null
        );
    }
    
    /**
     * الحصول على عدد إشعارات الإدمن غير المقروءة
     */
    public function getUnreadCount($admin_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM admin_notifications 
                WHERE admin_id = ? AND is_read = 0 AND is_dismissed = 0
            ");
            $stmt->execute([$admin_id]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Admin unread count error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * الحصول على إشعارات الإدمن
     */
    public function getAdminNotifications($admin_id, $filter = 'all', $limit = 20, $offset = 0) {
        try {
            $query = "SELECT * FROM admin_notifications WHERE admin_id = ?";
            $params = [$admin_id];

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
                case 'users':
                    $query .= " AND type = 'new_user' AND is_dismissed = 0";
                    break;
                case 'complaints':
                    $query .= " AND type = 'new_complaint' AND is_dismissed = 0";
                    break;
                case 'warnings':
                    $query .= " AND type = 'warning' AND is_dismissed = 0";
                    break;
                case 'reports':
                    $query .= " AND type = 'report' AND is_dismissed = 0";
                    break;
                default: // 'all'
                    $query .= " AND is_dismissed = 0";
            }

            $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get admin notifications error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * تحديث الإشعار كمقروء
     */
    public function markAsRead($notification_id, $admin_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE admin_notifications 
                SET is_read = 1 
                WHERE id = ? AND admin_id = ?
            ");
            return $stmt->execute([$notification_id, $admin_id]);
        } catch (PDOException $e) {
            error_log("Admin mark as read error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * إخفاء الإشعار
     */
    public function dismissNotification($notification_id, $admin_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE admin_notifications 
                SET is_dismissed = 1 
                WHERE id = ? AND admin_id = ?
            ");
            return $stmt->execute([$notification_id, $admin_id]);
        } catch (PDOException $e) {
            error_log("Admin dismiss notification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تحديد جميع الإشعارات كمقروءة
     */
    public function markAllAsRead($admin_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE admin_notifications 
                SET is_read = 1 
                WHERE admin_id = ? AND is_read = 0
            ");
            return $stmt->execute([$admin_id]);
        } catch (PDOException $e) {
            error_log("Admin mark all as read error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * حذف جميع الإشعارات المقروءة
     */
    public function deleteAllRead($admin_id) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM admin_notifications 
                WHERE admin_id = ? AND is_read = 1
            ");
            return $stmt->execute([$admin_id]);
        } catch (PDOException $e) {
            error_log("Admin delete all read error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * إرسال إشعار لجميع المشرفين
     */
    public function notifyAllAdmins($title, $message, $type = 'system') {
        try {
            $admins = $this->pdo->query("SELECT id FROM admin_users WHERE is_active = 1")->fetchAll();
            
            foreach ($admins as $admin) {
                $this->createNotification($admin['id'], $title, $message, $type);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Notify all admins error: " . $e->getMessage());
            return false;
        }
    }
}

// إنشاء كائن مدير إشعارات الإدمن
$adminNotificationManager = new AdminNotificationManager($pdo);
?>