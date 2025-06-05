<?php
require_once '../init.php';
header('Content-Type: application/json');

// Verify session and user type
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SESSION['user_type'] !== 'landlord') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Get and validate input
$action = $_POST['action'] ?? '';
$booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
$reason = $_POST['reason'] ?? null;

if (!$booking_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid booking ID']);
    exit;
}

try {
    $booking = new Booking($pdo);
    
    switch ($action) {
        case 'approve':
            $result = $booking->approveBooking($booking_id, $_SESSION['user_id']);
            break;
        case 'reject':
            $result = $booking->rejectBooking($booking_id, $_SESSION['user_id'], $reason);
            break;
        default:
            throw new Exception('Invalid action');
    }

    // Mark notification as read
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE reference_id = ? AND user_id = ?")
        ->execute([$booking_id, $_SESSION['user_id']]);

    echo json_encode($result);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    error_log("Booking error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}