<?php
require_once '../init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['room_id']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit();
}

$room = new Room($pdo);
$result = $room->toggleLike($_SESSION['user_id'], $data['room_id']);

echo json_encode([
    'success' => true,
    'like_count' => $result['count'],
    'action' => $result['action']
]);
?>