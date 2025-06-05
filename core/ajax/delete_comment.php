<?php
require_once '../init.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commentId = filter_input(INPUT_POST, 'comment_id', FILTER_SANITIZE_NUMBER_INT);
    
    $room = new Room($pdo);
    $result = $room->deleteComment($commentId, $_SESSION['user_id']);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete comment']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>