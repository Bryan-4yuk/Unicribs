<?php
$page_title = 'Reservations - UNICRIBS';
require $_SERVER['DOCUMENT_ROOT'] . '/UNICRIBS/includes/head.php'; 
require '../../core/init.php';

if (!isset($_SESSION['user_id']) ){
    header('Location: ../../index.html');
    exit();
}

// Handle booking actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking = new Booking($pdo);
    
    if (isset($_POST['approve_booking'])) {
        $booking_id = (int)$_POST['booking_id'];
        try {
            $result = $booking->approveBooking($booking_id, $_SESSION['user_id']);
            $_SESSION['notification'] = [
                'message' => 'Booking approved successfully',
                'type' => 'success'
            ];
        } catch (Exception $e) {
            $_SESSION['notification'] = [
                'message' => $e->getMessage(),
                'type' => 'error'
            ];
        }
        header("Location: reservations.php");
        exit();
    }
    
    if (isset($_POST['reject_booking'])) {
        $booking_id = (int)$_POST['booking_id'];
        $reason = $_POST['reason'] ?? null;
        try {
            $result = $booking->rejectBooking($booking_id, $_SESSION['user_id'], $reason);
            $_SESSION['notification'] = [
                'message' => 'Booking rejected successfully',
                'type' => 'success'
            ];
        } catch (Exception $e) {
            $_SESSION['notification'] = [
                'message' => $e->getMessage(),
                'type' => 'error'
            ];
        }
        header("Location: reservations.php");
        exit();
    }
    
    if (isset($_POST['cancel_booking'])) {
        $booking_id = (int)$_POST['booking_id'];
        try {
            $result = $booking->cancelBooking($booking_id, $_SESSION['user_id']);
            $_SESSION['notification'] = [
                'message' => 'Booking cancelled successfully',
                'type' => 'success'
            ];
        } catch (Exception $e) {
            $_SESSION['notification'] = [
                'message' => $e->getMessage(),
                'type' => 'error'
            ];
        }
        header("Location: reservations.php");
        exit();
    }
}

$booking = new Booking($pdo);
$pending_requests = $booking->getLandlordBookings($_SESSION['user_id'], 'pending');
$active_bookings = $booking->getLandlordBookings($_SESSION['user_id'], 'approved');
$completed = $booking->getLandlordBookings($_SESSION['user_id'], 'completed');
$rejected = $booking->getLandlordBookings($_SESSION['user_id'], 'rejected');
$cancelled = $booking->getLandlordBookings($_SESSION['user_id'], 'cancelled');
$booking_history = array_merge($completed, $rejected, $cancelled);

function get_primary_image($pdo, $room_id) {
    $default = '/UNICRIBS/assets/images/default-room.jpg'; // Use absolute path from root
    if (empty($room_id)) return $default;
    
    $stmt = $pdo->prepare("SELECT image_url FROM room_images WHERE room_id = ? ORDER BY is_primary DESC, id ASC LIMIT 1");
    $stmt->execute([$room_id]);
    $imgRow = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($imgRow && !empty($imgRow['image_url'])) {
        // Ensure the image URL is properly formatted
        if (strpos($imgRow['image_url'], '/UNICRIBS/') === 0) {
            return $imgRow['image_url'];
        } else {
            return '/UNICRIBS/' . ltrim($imgRow['image_url'], '/');
        }
    }
    return $default;
}

?>
<body class="bg-gray-50">
<div class="flex flex-col min-h-screen">
    <?php include '../../includes/header.php'; ?>
    <div class="flex flex-1">
        <?php include '../../includes/sidebar.php'; ?>
        <main class="flex-1 container mx-auto px-4 py-6 pt-20">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Reservations</h1>
                <p class="text-gray-600">Manage booking requests for your properties</p>
            </div>
            
            <div class="space-y-8">
                <!-- Pending Requests Section -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Pending Requests</h2>
                        <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">
                            <?php echo count($pending_requests); ?> request(s)
                        </span>
                    </div>
                    
                    <?php if (empty($pending_requests)): ?>
                        <div class="text-center py-8">
                            <i class="ri-inbox-line text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">You have no pending booking requests</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($pending_requests as $booking): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                        <div class="flex items-start gap-4">
                                            <img src="<?php echo htmlspecialchars(get_primary_image($pdo, $booking['room_id'])); ?>" 
                                                 alt="Room image" class="w-32 h-32 object-cover rounded-lg">
                                            <div>
                                                <h3 class="font-medium text-gray-800"><?php echo htmlspecialchars($booking['room_title']); ?></h3>
                                                <p class="text-sm text-gray-600 mt-1">
                                                    <?php echo date('M j, Y', strtotime($booking['start_date'])); ?> - 
                                                    <?php echo date('M j, Y', strtotime($booking['end_date'])); ?>
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    <?php echo number_format($booking['total_amount'], 0); ?> XAF
                                                </p>
                                                <div class="flex items-center mt-2">
                                                    <i class="ri-user-line text-gray-500 mr-2"></i>
                                                    <span class="text-sm text-gray-600"><?php echo htmlspecialchars($booking['student_name']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex flex-col sm:flex-row gap-2">
                                            <button class="approve-booking-btn px-4 py-2 bg-green-100 hover:bg-green-200 text-green-800 rounded-lg transition text-sm font-medium"
                                                    data-booking-id="<?php echo $booking['id']; ?>">
                                                Approve
                                            </button>
                                            <button class="reject-booking-btn px-4 py-2 bg-red-100 hover:bg-red-200 text-red-800 rounded-lg transition text-sm font-medium"
                                                    data-booking-id="<?php echo $booking['id']; ?>">
                                                Reject
                                            </button>
                                            <a href="chat.php?user_id=<?php echo $booking['student_id']; ?>&room_id=<?php echo $booking['room_id']; ?>"
                                               class="px-4 py-2 bg-primary hover:bg-red-700 text-white rounded-lg transition text-sm font-medium text-center">
                                                Message Student
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Active Bookings Section -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Active Bookings</h2>
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                            <?php echo count($active_bookings); ?> active
                        </span>
                    </div>
                    
                    <?php if (empty($active_bookings)): ?>
                        <div class="text-center py-8">
                            <i class="ri-home-3-line text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">You have no active bookings</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($active_bookings as $booking): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                        <div class="flex items-start gap-4">
                                           <img src="<?php echo htmlspecialchars(get_primary_image($pdo, $booking['room_id'])); ?>" 
                                                 alt="Room image" class="w-32 h-32 object-cover rounded-lg">
                                            <div>
                                                <h3 class="font-medium text-gray-800"><?php echo htmlspecialchars($booking['room_title']); ?></h3>
                                                <p class="text-sm text-gray-600 mt-1">
                                                    <?php echo date('M j, Y', strtotime($booking['start_date'])); ?> - 
                                                    <?php echo date('M j, Y', strtotime($booking['end_date'])); ?>
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    <?php echo number_format($booking['total_amount'], 0); ?> XAF
                                                </p>
                                                <div class="flex items-center mt-2">
                                                    <i class="ri-user-line text-gray-500 mr-2"></i>
                                                    <span class="text-sm text-gray-600"><?php echo htmlspecialchars($booking['student_name']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex flex-col sm:flex-row gap-2">
                                            <button class="cancel-booking-btn px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg transition text-sm font-medium"
                                                    data-booking-id="<?php echo $booking['id']; ?>">
                                                Cancel Booking
                                            </button>
                                            <a href="chat.php?user_id=<?php echo $booking['student_id']; ?>&room_id=<?php echo $booking['room_id']; ?>"
                                               class="px-4 py-2 bg-primary hover:bg-red-700 text-white rounded-lg transition text-sm font-medium text-center">
                                                Message Student
                                            </a>
                                            <a href="room_detail.php?id=<?php echo $booking['room_id']; ?>"
                                               class="px-4 py-2 bg-blue-100 hover:bg-blue-200 text-blue-800 rounded-lg transition text-sm font-medium text-center">
                                                View Room
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Booking History Section -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">Booking History</h2>
                    
                    <?php if (empty($booking_history)): ?>
                        <div class="text-center py-8">
                            <i class="ri-time-line text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">You have no booking history</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($booking_history as $booking): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-24 w-24">
                                                            <img src="<?php echo htmlspecialchars(get_primary_image($pdo, $booking['room_id'])); ?>" 
                                                                alt="Room image" class="w-32 h-32 object-cover rounded-lg">
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['room_title']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($booking['student_name']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo date('M j, Y', strtotime($booking['start_date'])); ?></div>
                                                <div class="text-sm text-gray-500">to <?php echo date('M j, Y', strtotime($booking['end_date'])); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo number_format($booking['total_amount'], 0); ?> XAF
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php 
                                                $status_classes = [
                                                    'completed' => 'bg-green-100 text-green-800',
                                                    'rejected' => 'bg-red-100 text-red-800',
                                                    'cancelled' => 'bg-gray-100 text-gray-800'
                                                ];
                                                ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_classes[$booking['status']]; ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="room_detail.php?id=<?php echo $booking['room_id']; ?>" class="text-primary hover:text-red-700">View Room</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Reject Booking Modal -->
<div id="rejectModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Reject Booking Request</h3>
                <form id="rejectForm">
                    <input type="hidden" id="rejectBookingId" name="booking_id">
                    <div class="mb-4">
                        <label for="rejectReason" class="block text-sm font-medium text-gray-700">Reason (Optional)</label>
                        <textarea id="rejectReason" name="reason" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"></textarea>
                    </div>
                </form>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" id="confirmRejectBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Confirm Reject
                </button>
                <button type="button" id="cancelRejectBtn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Booking Modal -->
<div id="cancelModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="ri-alert-line text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Cancel Booking</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">Are you sure you want to cancel this booking? This action cannot be undone.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" id="confirmCancelBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Yes, Cancel
                </button>
                <button type="button" id="cancelCancelBtn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    No, Go Back
                </button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Show notification if exists
    <?php if (isset($_SESSION['notification'])): ?>
        showNotification('<?php echo $_SESSION['notification']['message']; ?>', '<?php echo $_SESSION['notification']['type']; ?>');
        <?php unset($_SESSION['notification']); ?>
    <?php endif; ?>

    // Utility function to add event listener if element exists
    function on(id, event, handler) {
        const el = document.getElementById(id);
        if (el) el.addEventListener(event, handler);
    }

    // Notification helper
    function showNotification(msg, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg text-white ${
            type === 'success' ? 'bg-green-500' : 'bg-red-500'
        }`;
        notification.textContent = msg;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('opacity-0', 'transition-opacity', 'duration-500');
            setTimeout(() => notification.remove(), 500);
        }, 3000);
    }

    // Modals
    const rejectModal = document.getElementById('rejectModal');
    const cancelModal = document.getElementById('cancelModal');

    // Approve booking
    document.querySelectorAll('.approve-booking-btn').forEach(button => {
        button.addEventListener('click', function () {
            if (!confirm('Are you sure you want to approve this booking?')) return;
            
            const bookingId = this.getAttribute('data-booking-id');
            
            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'reservations.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'booking_id';
            input.value = bookingId;
            form.appendChild(input);
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'approve_booking';
            actionInput.value = '1';
            form.appendChild(actionInput);
            
            document.body.appendChild(form);
            form.submit();
        });
    });

    // Reject booking buttons
    document.querySelectorAll('.reject-booking-btn').forEach(button => {
        button.addEventListener('click', function () {
            const bookingId = this.getAttribute('data-booking-id');
            document.getElementById('rejectBookingId').value = bookingId;
            document.getElementById('rejectReason').value = '';
            rejectModal.classList.remove('hidden');
        });
    });

    // Confirm reject
    on('confirmRejectBtn', 'click', function () {
        const bookingId = document.getElementById('rejectBookingId').value;
        const reason = document.getElementById('rejectReason').value;
        
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'reservations.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'booking_id';
        input.value = bookingId;
        form.appendChild(input);
        
        const reasonInput = document.createElement('input');
        reasonInput.type = 'hidden';
        reasonInput.name = 'reason';
        reasonInput.value = reason;
        form.appendChild(reasonInput);
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'reject_booking';
        actionInput.value = '1';
        form.appendChild(actionInput);
        
        document.body.appendChild(form);
        form.submit();
    });

    // Cancel reject
    on('cancelRejectBtn', 'click', () => rejectModal.classList.add('hidden'));

    // Cancel booking buttons
    document.querySelectorAll('.cancel-booking-btn').forEach(button => {
        button.addEventListener('click', function () {
            const bookingId = this.getAttribute('data-booking-id');
            cancelModal.setAttribute('data-booking-id', bookingId);
            cancelModal.classList.remove('hidden');
        });
    });

    // Confirm cancel booking
    on('confirmCancelBtn', 'click', function () {
        const bookingId = cancelModal.getAttribute('data-booking-id');
        
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'reservations.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'booking_id';
        input.value = bookingId;
        form.appendChild(input);
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'cancel_booking';
        actionInput.value = '1';
        form.appendChild(actionInput);
        
        document.body.appendChild(form);
        form.submit();
    });

    // Cancel cancel
    on('cancelCancelBtn', 'click', () => cancelModal.classList.add('hidden'));
});
</script>
</body>
</html>