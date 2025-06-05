<?php
class Booking {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function createBooking($room_id, $student_id, $start_date, $end_date, $special_requests = null) {
        if (strtotime($start_date) >= strtotime($end_date)) {
            throw new Exception("End date must be after start date");
        }

        $room = $this->getRoomDetails($room_id);
        if (!$room) throw new Exception("Room not found");

        if ($this->isRoomBooked($room_id, $start_date, $end_date)) {
            throw new Exception("Room is already booked for the selected dates");
        }

        if ($this->hasExistingBooking($room_id, $student_id)) {
            throw new Exception("You already have a booking request for this room");
        }

        $total_amount = $this->calculateTotalAmount($room['price'], $start_date, $end_date);

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("INSERT INTO bookings 
                (room_id, student_id, landlord_id, start_date, end_date, status, payment_status, total_amount, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, 'pending', 'pending', ?, NOW(), NOW())");
            $stmt->execute([$room_id, $student_id, $room['landlord_id'], $start_date, $end_date, $total_amount]);
            $booking_id = $this->pdo->lastInsertId();

            $this->createNotification(
                $room['landlord_id'],
                'New Booking Request',
                "You have a new booking request for {$room['title']}",
                'booking',
                $booking_id
            );

            $this->pdo->commit();
            return [
                'success' => true,
                'booking_id' => $booking_id,
                'message' => 'Booking request submitted successfully'
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function approveBooking($booking_id, $landlord_id) {
        error_log("Attempting to approve booking $booking_id by landlord $landlord_id");
        try {
            // Validate inputs
            if (!is_numeric($booking_id) || $booking_id <= 0) {
                throw new Exception("Invalid booking ID");
            }
            if (!is_numeric($landlord_id) || $landlord_id <= 0) {
                throw new Exception("Invalid landlord ID");
            }

            $booking = $this->getBookingForAction($booking_id, $landlord_id, 'landlord');
            
            if ($booking['status'] !== 'pending') {
                throw new Exception("Only pending bookings can be approved. Current status: " . $booking['status']);
            }

            $this->pdo->beginTransaction();
            
            // Update booking status
            $this->updateBookingStatus($booking_id, 'approved');
            
            // If it's a single room, mark as unavailable
            if (isset($booking['room_type']) && $booking['room_type'] === 'single') {
                $this->setRoomAvailability($booking['room_id'], 0);
            }
            
            // Create notification
            $this->createNotification(
                $booking['student_id'],
                'Booking Approved',
                "Your booking request for {$booking['room_title']} has been approved",
                'booking',
                $booking_id
            );
            
            $this->pdo->commit();
            return ['success' => true, 'message' => 'Booking approved successfully'];
            
        } catch (Exception $e) {
            error_log("Error in approveBooking: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    public function rejectBooking($booking_id, $landlord_id, $reason = null) {
        try {
            // Validate inputs
            if (!is_numeric($booking_id) || $booking_id <= 0) {
                throw new Exception("Invalid booking ID");
            }
            if (!is_numeric($landlord_id) || $landlord_id <= 0) {
                throw new Exception("Invalid landlord ID");
            }

            $booking = $this->getBookingForAction($booking_id, $landlord_id, 'landlord');
            
            if ($booking['status'] !== 'pending') {
                throw new Exception("Only pending bookings can be rejected. Current status: " . $booking['status']);
            }

            $this->pdo->beginTransaction();
            
            // Update booking status
            $this->updateBookingStatus($booking_id, 'rejected');
            
            // Create notification message
            $message = $reason
                ? "Your booking request for {$booking['room_title']} has been rejected. Reason: $reason"
                : "Your booking request for {$booking['room_title']} has been rejected";
            
            $this->createNotification(
                $booking['student_id'],
                'Booking Rejected',
                $message,
                'booking',
                $booking_id
            );
            
            $this->pdo->commit();
            return ['success' => true, 'message' => 'Booking rejected successfully'];
            
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error in rejectBooking: " . $e->getMessage());
            throw $e;
        }
    }

    public function cancelBooking($booking_id, $user_id) {
        try {
            error_log("Attempting to cancel booking $booking_id by user $user_id");
            
            // Validate inputs
            if (!is_numeric($booking_id) || $booking_id <= 0) {
                throw new Exception("Invalid booking ID");
            }
            if (!is_numeric($user_id) || $user_id <= 0) {
                throw new Exception("Invalid user ID");
            }

            $booking = $this->getBookingForAction($booking_id, $user_id, 'either');
            
            if (!in_array($booking['status'], ['pending', 'approved'])) {
                throw new Exception("Only pending or approved bookings can be cancelled. Current status: " . $booking['status']);
            }

            $this->pdo->beginTransaction();
            
            // Update booking status
            $this->updateBookingStatus($booking_id, 'cancelled');
            
            // Update room availability
            $this->updateRoomAvailability($booking['room_id']);
            
            // Determine who to notify and what message to send
            $notified_user = ($booking['student_id'] == $user_id) ? $booking['landlord_id'] : $booking['student_id'];
            $message = ($booking['student_id'] == $user_id)
                ? "Booking for {$booking['room_title']} has been cancelled by the student"
                : "Your booking for {$booking['room_title']} has been cancelled by the landlord";
            
            $this->createNotification(
                $notified_user,
                'Booking Cancelled',
                $message,
                'booking',
                $booking_id
            );
            
            $this->pdo->commit();
            error_log("Successfully cancelled booking $booking_id");
            return ['success' => true, 'message' => 'Booking cancelled successfully'];
            
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error in cancelBooking: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    public function getStudentBookings($student_id, $status = null) {
        return $this->getBookings('student_id', $student_id, $status, true);
    }

    public function getLandlordBookings($landlord_id, $status = null) {
        return $this->getBookings('landlord_id', $landlord_id, $status, false);
    }

    public function getBookingDetails($booking_id, $user_id = null) {
        $sql = "
            SELECT b.*, 
                   r.title as room_title, r.price, r.room_type, r.address,
                   student.full_name as student_name, student.email as student_email,
                   landlord.full_name as landlord_name, landlord.email as landlord_email
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN users student ON b.student_id = student.id
            JOIN users landlord ON b.landlord_id = landlord.id
            WHERE b.id = ?
        ";
        $params = [$booking_id];
        if ($user_id) {
            $sql .= " AND (b.student_id = ? OR b.landlord_id = ?)";
            $params[] = $user_id;
            $params[] = $user_id;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getRoomDetails($room_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT r.*, u.full_name as landlord_name 
                FROM rooms r
                JOIN users u ON r.landlord_id = u.id
                WHERE r.id = ?
            ");
            $stmt->execute([$room_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting room details: " . $e->getMessage());
            return false;
        }
    }

    private function isRoomBooked($room_id, $start_date, $end_date, $exclude_booking_id = null) {
        try {
            $where = "room_id = ? AND status = 'approved' AND 
                     ((start_date BETWEEN ? AND ?) OR 
                      (end_date BETWEEN ? AND ?) OR 
                      (start_date <= ? AND end_date >= ?))";
            $params = [$room_id, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date];
            if ($exclude_booking_id) {
                $where .= " AND id != ?";
                $params[] = $exclude_booking_id;
            }
            $stmt = $this->pdo->prepare("SELECT 1 FROM bookings WHERE $where LIMIT 1");
            $stmt->execute($params);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error checking room booking status: " . $e->getMessage());
            return false;
        }
    }

    private function hasExistingBooking($room_id, $student_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 1 FROM bookings 
                WHERE room_id = ? AND student_id = ? AND status IN ('pending', 'approved')
                LIMIT 1
            ");
            $stmt->execute([$room_id, $student_id]);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error checking existing booking: " . $e->getMessage());
            return false;
        }
    }

    private function calculateTotalAmount($price, $start_date, $end_date) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        $months = ceil($interval->days / 30);
        return $price * max(1, $months);
    }

    private function updateRoomAvailability($room_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as active_count
                FROM bookings 
                WHERE room_id = ? AND status = 'approved' AND end_date >= CURDATE()
            ");
            $stmt->execute([$room_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $is_available = ($result['active_count'] == 0) ? 1 : 0;
            $this->setRoomAvailability($room_id, $is_available);
        } catch (PDOException $e) {
            error_log("Error updating room availability: " . $e->getMessage());
        }
    }

    private function setRoomAvailability($room_id, $is_available) {
        try {
            $stmt = $this->pdo->prepare("UPDATE rooms SET is_available = ? WHERE id = ?");
            $stmt->execute([$is_available, $room_id]);
        } catch (PDOException $e) {
            error_log("Room availability update failed: " . $e->getMessage());
        }
    }

    private function createNotification($user_id, $title, $message, $type, $reference_id = null) {
        try {
            // Check if notifications table exists
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE 'notifications'");
            $stmt->execute();
            if ($stmt->fetch()) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO notifications 
                    (user_id, title, message, type, reference_id, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$user_id, $title, $message, $type, $reference_id]);
            }
        } catch (PDOException $e) {
            error_log("Notification creation failed: " . $e->getMessage());
        }
    }

    private function updateBookingStatus($booking_id, $status) {
        try {
            $stmt = $this->pdo->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$status, $booking_id]);
            if (!$result) {
                throw new Exception("Failed to update booking status");
            }
            if ($stmt->rowCount() === 0) {
                throw new Exception("No booking found with ID: $booking_id");
            }
        } catch (PDOException $e) {
            error_log("Error updating booking status: " . $e->getMessage());
            throw new Exception("Database error updating booking status");
        }
    }

    private function getBookingForAction($booking_id, $user_id, $role = 'either') {
        try {
            $where = "b.id = ?";
            $params = [$booking_id];
            
            if ($role === 'landlord') {
                $where .= " AND b.landlord_id = ?";
                $params[] = $user_id;
            } elseif ($role === 'student') {
                $where .= " AND b.student_id = ?";
                $params[] = $user_id;
            } else {
                $where .= " AND (b.student_id = ? OR b.landlord_id = ?)";
                $params[] = $user_id;
                $params[] = $user_id;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT b.*, r.title as room_title, r.is_available, r.room_type
                FROM bookings b
                JOIN rooms r ON b.room_id = r.id
                WHERE $where
            ");
            $stmt->execute($params);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                throw new Exception("Booking not found or you don't have permission to perform this action");
            }
            
            return $booking;
        } catch (PDOException $e) {
            error_log("Error getting booking for action: " . $e->getMessage());
            throw new Exception("Database error retrieving booking");
        }
    }

    private function getBookings($field, $id, $status = null, $isStudent = true) {
        try {
            $where = "b.$field = ?";
            $params = [$id];
            if ($status) {
                $where .= " AND b.status = ?";
                $params[] = $status;
            }
            $select = $isStudent
                ? "u.full_name as landlord_name, u.profile_picture as landlord_avatar"
                : "u.full_name as student_name, u.profile_picture as student_avatar";
            $userJoin = $isStudent
                ? "JOIN users u ON b.landlord_id = u.id"
                : "JOIN users u ON b.student_id = u.id";
            $stmt = $this->pdo->prepare("
                SELECT b.*, 
                       r.title as room_title, r.price, r.room_type, r.address,
                       $select
                FROM bookings b
                JOIN rooms r ON b.room_id = r.id
                $userJoin
                WHERE $where
                ORDER BY b.created_at DESC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting bookings: " . $e->getMessage());
            return [];
        }
    }

    public function getStudentComments($studentId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    c.id,
                    c.comment,
                    c.created_at,
                    r.title AS room_title,
                    r.id AS room_id,
                    r.primary_image AS room_image,
                    r.room_number
                FROM comments c
                INNER JOIN rooms r ON c.room_id = r.id
                WHERE c.user_id = :student_id
                ORDER BY c.created_at DESC
                LIMIT 20
            ");
            $stmt->execute(['student_id' => $studentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting student comments: " . $e->getMessage());
            return [];
        }
    }

    public function getBookingStatus($room_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as pending_count FROM bookings WHERE room_id = ? AND status = 'pending'");
            $stmt->execute([$room_id]);
            $pending_count = (int)($stmt->fetchColumn() ?: 0);

            $stmt = $this->pdo->prepare("SELECT COUNT(*) as approved_count FROM bookings WHERE room_id = ? AND status = 'approved' AND end_date >= CURDATE()");
            $stmt->execute([$room_id]);
            $is_available = ((int)($stmt->fetchColumn() ?: 0) === 0);

            return [
                'pending_count' => $pending_count,
                'is_available' => $is_available
            ];
        } catch (PDOException $e) {
            error_log("Error getting booking status: " . $e->getMessage());
            return ['pending_count' => 0, 'is_available' => true];
        }
    }
    
}

