<?php
require_once '../init.php';
require_once '../classes/notification.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (empty($input['booking_id']) || empty($input['start_date']) || empty($input['end_date'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

$booking_id = (int)$input['booking_id'];
$start_date = $input['start_date'];
$end_date = $input['end_date'];

try {
    $booking = new Booking($pdo);
    $booking_data = $booking->getBooking($booking_id);
    
    if (!$booking_data) {
        throw new Exception('Booking not found');
    }
    
    // Verify user owns this booking
    if ($booking_data['student_id'] !== $_SESSION['user_id']) {
        throw new Exception('Not authorized to update this booking');
    }
    
    // Validate dates
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $today = new DateTime();
    
    if ($start < $today) {
        throw new Exception('Start date cannot be in the past');
    }
    
    if ($end <= $start) {
        throw new Exception('End date must be after start date');
    }
    
    // Check room availability (excluding current booking)
    $stmt = $this->pdo->prepare("
        SELECT id FROM bookings 
        WHERE room_id = ? 
        AND id != ?
        AND status IN ('pending', 'approved')
        AND (
            (start_date BETWEEN ? AND ?)
            OR (end_date BETWEEN ? AND ?)
            OR (? BETWEEN start_date AND end_date)
            OR (? BETWEEN start_date AND end_date)
        )
    ");
    $stmt->execute([
        $booking_data['room_id'],
        $booking_id,
        $start_date, $end_date,
        $start_date, $end_date,
        $start_date, $end_date
    ]);
    
    if ($stmt->rowCount() > 0) {
        throw new Exception('Room is not available for the selected dates');
    }
    
    // Calculate new total amount
    $interval = $start->diff($end);
    $months = $interval->m + ($interval->y * 12);
    if ($interval->d > 0) $months += 1;
    $total_amount = $booking_data['price'] * $months;
    
    // Update booking
    $stmt = $this->pdo->prepare("
        UPDATE bookings 
        SET start_date = ?, end_date = ?, total_amount = ?, status = 'pending'
        WHERE id = ?
    ");
    $stmt->execute([$start_date, $end_date, $total_amount, $booking_id]);
    
    // Notify landlord
    $notification = new Notification($pdo);
    $notification->create(
        $booking_data['landlord_id'],
        'Booking Modified',
        "The student has modified their booking for {$booking_data['room_title']}",
        'booking',
        $booking_id
    );
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Booking updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>