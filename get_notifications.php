<?php
// get_notifications.php - API endpoint for admin notifications
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../connection/connection.php'; // Adjust path as needed
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

class NotificationAPI {
    
    /**
     * Get all notifications with pagination
     */
    public static function getNotifications($limit = 50, $offset = 0) {
        try {
            $limit = intval($limit);
            $offset = intval($offset);
            
            $query = "SELECT id, title, message, type, is_read, created_at,
                            DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as formatted_date,
                            CASE 
                                WHEN created_at >= NOW() - INTERVAL 1 HOUR THEN CONCAT(TIMESTAMPDIFF(MINUTE, created_at, NOW()), ' minutes ago')
                                WHEN created_at >= NOW() - INTERVAL 24 HOUR THEN CONCAT(TIMESTAMPDIFF(HOUR, created_at, NOW()), ' hours ago')
                                WHEN created_at >= NOW() - INTERVAL 7 DAY THEN CONCAT(TIMESTAMPDIFF(DAY, created_at, NOW()), ' days ago')
                                ELSE DATE_FORMAT(created_at, '%d %b %Y')
                            END as time_ago
                     FROM notifications 
                     ORDER BY created_at DESC 
                     LIMIT $limit OFFSET $offset";
            
            $result = Database::search($query);
            $notifications = [];
            
            while ($row = $result->fetch_assoc()) {
                $notifications[] = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'message' => $row['message'],
                    'type' => $row['type'],
                    'is_read' => (bool)$row['is_read'],
                    'created_at' => $row['created_at'],
                    'formatted_date' => $row['formatted_date'],
                    'time_ago' => $row['time_ago'],
                    'icon' => self::getIconForType($row['type'])
                ];
            }
            
            return $notifications;
        } catch (Exception $e) {
            error_log("Error getting notifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get unread notifications count
     */
    public static function getUnreadCount() {
        try {
            $query = "SELECT COUNT(*) as count FROM notifications WHERE is_read = 0";
            $result = Database::search($query);
            $row = $result->fetch_assoc();
            return intval($row['count']);
        } catch (Exception $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Mark notification as read
     */
    public static function markAsRead($notificationId) {
        try {
            $notificationId = intval($notificationId);
            $query = "UPDATE notifications SET is_read = 1 WHERE id = $notificationId";
            Database::iud($query);
            return true;
        } catch (Exception $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications as read
     */
    public static function markAllAsRead() {
        try {
            $query = "UPDATE notifications SET is_read = 1 WHERE is_read = 0";
            Database::iud($query);
            return true;
        } catch (Exception $e) {
            error_log("Error marking all notifications as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete old notifications (older than 30 days)
     */
    public static function cleanupOldNotifications() {
        try {
            $query = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
            Database::iud($query);
            return true;
        } catch (Exception $e) {
            error_log("Error cleaning up notifications: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get appropriate icon for notification type
     */
    private static function getIconForType($type) {
        switch ($type) {
            case 'appointment':
                return '📅';
            case 'payment':
                return '💳';
            case 'system':
                return '⚙️';
            default:
                return '📢';
        }
    }
}

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'get_notifications';
    
    switch ($action) {
        case 'get_notifications':
            $limit = $_GET['limit'] ?? 50;
            $offset = $_GET['offset'] ?? 0;
            
            $notifications = NotificationAPI::getNotifications($limit, $offset);
            $unreadCount = NotificationAPI::getUnreadCount();
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
                'total_count' => count($notifications)
            ]);
            break;
            
        case 'get_count':
            $count = NotificationAPI::getUnreadCount();
            echo json_encode([
                'success' => true,
                'unread_count' => $count
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'mark_read':
            $notificationId = $_POST['notification_id'] ?? 0;
            $result = NotificationAPI::markAsRead($notificationId);
            echo json_encode(['success' => $result]);
            break;
            
        case 'mark_all_read':
            $result = NotificationAPI::markAllAsRead();
            echo json_encode(['success' => $result]);
            break;
            
        case 'cleanup':
            $result = NotificationAPI::cleanupOldNotifications();
            echo json_encode(['success' => $result]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
}
?>