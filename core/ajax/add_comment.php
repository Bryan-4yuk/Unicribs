<?php
require_once '../init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomId = filter_input(INPUT_POST, 'room_id', FILTER_SANITIZE_NUMBER_INT);
    $content = trim(filter_input(INPUT_POST, 'content', FILTER_SANITIZE_STRING));
    $parentId = filter_input(INPUT_POST, 'parent_id', FILTER_SANITIZE_NUMBER_INT);
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
        exit();
    }
    
    $room = new Room($pdo);
    $result = $room->addComment($_SESSION['user_id'], $roomId, $content, $parentId);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>