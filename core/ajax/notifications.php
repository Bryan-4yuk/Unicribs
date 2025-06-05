<?php
require_once '../init.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$action = $_GET['action'] ?? '';
$notification = new Notification($pdo);

try {
    switch ($action) {
        case 'get':
            // Get notifications for current user
            $notifications = $notification->getNotifications($_SESSION['user_id'], 10);
            echo json_encode(['notifications' => $notifications]);
            break;
            
        case 'count':
            // Get unread count
            $count = $notification->getUnreadCount($_SESSION['user_id']);
            echo json_encode(['count' => $count]);
            break;
            
        case 'mark_read':
            // Mark single notification as read
            if (empty($_GET['id'])) {
                throw new Exception('Notification ID required');
            }
            $notification->markAsRead($_SESSION['user_id'], [$_GET['id']]);
            echo json_encode(['success' => true]);
            break;
            
        case 'mark_all_read':
            // Mark all notifications as read
            $notification->markAsRead($_SESSION['user_id']);
            echo json_encode(['success' => true]);
            break;
            
        case 'check_new':
            // Check for new notifications since a given timestamp
            $since = isset($_GET['since']) ? (int)$_GET['since'] : 0;
            
            if ($since > 0) {
                $stmt = $pdo->prepare("
                    SELECT * FROM notifications 
                    WHERE user_id = ? 
                    AND UNIX_TIMESTAMP(created_at) * 1000 > ?
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$_SESSION['user_id'], $since]);
                $newNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['notifications' => $newNotifications]);
            } else {
                echo json_encode(['notifications' => []]);
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}