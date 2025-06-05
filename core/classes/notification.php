<?php
class Notification {
    private $pdo;
    private $unreadCount = 0;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new notification
     * @param int $userId Recipient user ID
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $type Notification type (booking, system, etc.)
     * @param int|null $referenceId Related entity ID
     * @return bool Success status
     */
    public function create($userId, $title, $message, $type, $referenceId = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO notifications 
            (user_id, title, message, type, reference_id, is_read, created_at) 
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");
        
        return $stmt->execute([$userId, $title, $message, $type, $referenceId]);
    }

    /**
     * Get notifications for a user
     * @param int $userId User ID
     * @param int $limit Number of notifications to return
     * @param bool $unreadOnly Whether to return only unread notifications
     * @return array Array of notifications
     */
    public function getNotifications($userId, $limit = 10, $unreadOnly = false) {
        $sql = "SELECT * FROM notifications WHERE user_id = ?";
        $params = [$userId];
        
        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mark notifications as read
     * @param int $userId User ID
     * @param array $notificationIds Array of notification IDs to mark as read
     * @return bool Success status
     */
    public function markAsRead($userId, $notificationIds = []) {
        if (empty($notificationIds)) {
            // Mark all as read if no specific IDs provided
            $stmt = $this->pdo->prepare("
                UPDATE notifications SET is_read = 1 
                WHERE user_id = ? AND is_read = 0
            ");
            return $stmt->execute([$userId]);
        }
        
        // Convert IDs to integers for safety
        $ids = array_map('intval', $notificationIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        $stmt = $this->pdo->prepare("
            UPDATE notifications SET is_read = 1 
            WHERE user_id = ? AND id IN ($placeholders)
        ");
        
        return $stmt->execute(array_merge([$userId], $ids));
    }

    /**
     * Get count of unread notifications
     * @param int $userId User ID
     * @return int Count of unread notifications
     */
    public function getUnreadCount($userId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Delete a notification
     * @param int $userId User ID (for verification)
     * @param int $notificationId Notification ID to delete
     * @return bool Success status
     */
    public function delete($userId, $notificationId) {
        $stmt = $this->pdo->prepare("
            DELETE FROM notifications 
            WHERE id = ? AND user_id = ?
        ");
        
        return $stmt->execute([$notificationId, $userId]);
    }
}