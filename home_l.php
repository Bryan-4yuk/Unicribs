<?php
$page_title = 'Landlords Dashboard - UNICRIBS';
require_once 'core/init.php';

// Check if user is logged in and is a landlord
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    header('Location: index.html');
    exit();
}

// Get user data
$user = new User($pdo);
$userData = $user->getUser($_SESSION['user_id']);

// Get landlord's rooms
$room = new Room($pdo);
$rooms = $room->getLandlordRooms($_SESSION['user_id']);

// Get booking requests
$booking = new Booking($pdo);
$pendingBookings = $booking->getLandlordBookings($_SESSION['user_id'], 'pending');
$approvedBookings = $booking->getLandlordBookings($_SESSION['user_id'], 'approved');
$totalRooms = count($rooms);
$totalPending = count($pendingBookings);
$totalActive = count($approvedBookings);
?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/UNICRIBS/includes/head.php'; ?>
<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <!-- Page Content -->
        <main class="flex-1 container mx-auto px-4 py-6 pt-20">
            <h1 class="text-2xl md:text-3xl font-bold mb-6 text-gray-800">Landlord Dashboard</h1>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm p-6 transition-all duration-300 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Total Rooms</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $totalRooms; ?></h3>
                        </div>
                        <div class="w-12 h-12 flex items-center justify-center bg-red-100 rounded-full">
                            <i class="ri-home-4-line text-primary text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 transition-all duration-300 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Active Bookings</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $totalActive; ?></h3>
                        </div>
                        <div class="w-12 h-12 flex items-center justify-center bg-green-100 rounded-full">
                            <i class="ri-calendar-check-line text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 transition-all duration-300 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Pending Requests</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $totalPending; ?></h3>
                        </div>
                        <div class="w-12 h-12 flex items-center justify-center bg-yellow-100 rounded-full">
                            <i class="ri-time-line text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pending Booking Requests -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Pending Booking Requests</h2>
                    <span class="text-sm text-gray-500"><?php echo $totalPending; ?> requests</span>
                </div>
                
                <?php if (empty($pendingBookings)): ?>
                    <div class="text-center py-8">
                        <i class="ri-inbox-line text-4xl text-gray-300 mb-2"></i>
                        <p class="text-gray-500">No pending booking requests</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($pendingBookings as $bookingItem): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:border-primary transition">
                            <div class="flex justify-between items-start mb-3">
                                <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($bookingItem['room_title']); ?></h3>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">Pending</span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                                <div class="flex items-center">
                                    <i class="ri-user-line text-gray-500 mr-2"></i>
                                    <div>
                                        <p class="text-xs text-gray-500">Student</p>
                                        <p class="text-sm font-medium"><?php echo htmlspecialchars($bookingItem['student_name']); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <i class="ri-calendar-line text-gray-500 mr-2"></i>
                                    <div>
                                        <p class="text-xs text-gray-500">Dates</p>
                                        <p class="text-sm font-medium"><?php echo date('M j, Y', strtotime($bookingItem['start_date'])); ?> - <?php echo date('M j, Y', strtotime($bookingItem['end_date'])); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <i class="ri-money-dollar-circle-line text-gray-500 mr-2"></i>
                                    <div>
                                        <p class="text-xs text-gray-500">Total Amount</p>
                                        <p class="text-sm font-medium"><?php echo number_format($bookingItem['total_amount'], 0); ?> XAF</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end space-x-2">
                                <button class="px-4 py-2 bg-green-100 text-green-800 rounded-lg hover:bg-green-200 transition" data-booking-id="<?php echo $bookingItem['id']; ?>">
                                    Approve
                                </button>
                                <button class="px-4 py-2 bg-red-100 text-red-800 rounded-lg hover:bg-red-200 transition" data-booking-id="<?php echo $bookingItem['id']; ?>">
                                    Reject
                                </button>
                                <button class="px-4 py-2 bg-gray-100 text-gray-800 rounded-lg hover:bg-gray-200 transition">
                                    View Details
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- My Rooms (Grid like home.php) -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">My Rooms</h2>
                    <a href="user/landlord/post_room.php" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-red-700 transition flex items-center">
                        <i class="ri-add-line mr-1"></i> Add New Room
                    </a>
                </div>
                
                <?php if (empty($rooms)): ?>
                    <div class="text-center py-8">
                        <i class="ri-home-4-line text-4xl text-gray-300 mb-2"></i>
                        <p class="text-gray-500 mb-4">You haven't posted any rooms yet.</p>
                        <a href="user/landlord/post_room.php" class="inline-block px-6 py-2 bg-primary text-white rounded-lg hover:bg-red-700 transition">
                            Post Your First Room
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($rooms as $roomItem): 
                            $booking_status = $booking->getBookingStatus($roomItem['id']);
                            $pending_count = $booking_status['pending_count'];
                            $is_available = $booking_status['is_available'];
                        ?>
                        <div class="bg-white rounded-2xl shadow-md overflow-hidden transition-transform duration-200 hover:shadow-lg hover:-translate-y-1 room-card flex flex-col">
                            <div class="relative">
                                <img src="<?php echo htmlspecialchars($roomItem['primary_image'] ?? 'assets/images/default-room.jpg'); ?>" 
                                    alt="<?php echo htmlspecialchars($roomItem['title']); ?>" 
                                    class="w-full h-64 object-cover object-center transition-all duration-200" loading="lazy">
                                <span class="absolute top-4 left-4 bg-primary text-white text-xs font-bold px-3 py-1 rounded-full shadow">
                                    <?php echo htmlspecialchars($roomItem['university_name'] ?? ''); ?>
                                </span>
                                <?php if (!$is_available): ?>
                                    <span class="absolute bottom-4 left-4 bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow">
                                        BOOKED
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 flex flex-col p-5">
                                <!-- Room Status Indicator -->
                                <div class="flex items-center mb-2">
                                    <?php if (!$is_available): ?>
                                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-medium">
                                            Booked
                                        </span>
                                    <?php elseif ($pending_count > 0): ?>
                                        <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-medium">
                                            <?php echo $pending_count; ?> student<?php echo $pending_count > 1 ? 's' : ''; ?> waiting
                                        </span>
                                    <?php else: ?>
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                                            Available
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-bold text-gray-800 truncate" title="<?php echo htmlspecialchars($roomItem['title']); ?>">
                                            <?php echo htmlspecialchars($roomItem['title']); ?>
                                        </h3>
                                        <div class="flex items-center text-xs text-gray-500 mt-1">
                                            <i class="ri-map-pin-line mr-1"></i>
                                            <span class="truncate block max-w-[180px]" title="<?php echo htmlspecialchars($roomItem['address']); ?>">
                                                <?php echo htmlspecialchars($roomItem['address']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-lg font-bold text-primary whitespace-nowrap ml-2"><?php echo number_format($roomItem['price'], 0); ?> XAF</div>
                                </div>
                                <p class="text-gray-600 text-xs mb-3 line-clamp-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; line-clamp: 2; overflow: hidden;">
                                    <?php echo htmlspecialchars($roomItem['description']); ?>
                                </p>
                                <div class="flex justify-between items-center mt-auto pt-2">
                                    <div class="flex space-x-3">
                                        <span class="text-gray-500" title="Likes">
                                            <i class="ri-heart-line"></i>
                                            <span class="text-xs ml-1 like-count"><?php echo $roomItem['like_count'] ?? 0; ?></span>
                                        </span>
                                        <span class="text-gray-500" title="Bookings">
                                            <i class="ri-calendar-check-line"></i>
                                            <span class="text-xs ml-1"><?php echo $roomItem['booking_count'] ?? 0; ?></span>
                                        </span>
                                    </div>
                                    <a href="user/landlord/manage_rooms.php?id=<?php echo $roomItem['id']; ?>" 
                                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-red-700 transition font-medium">
                                        Manage
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-6 text-center">
                        <a href="user/landlord/manage_rooms.php" class="inline-block px-6 py-2 bg-primary text-white rounded-lg hover:bg-red-700 transition">
                            View All Rooms
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Mobile Footer -->
    <footer class="md:hidden fixed bottom-0 left-0 right-0 bg-white shadow-lg z-50">
        <div class="flex justify-around py-3">
            <a href="home_l.php" class="flex flex-col items-center text-primary">
                <i class="ri-dashboard-line text-xl"></i>
                <span class="text-xs mt-1">Dashboard</span>
            </a>
            <a href="user/landlord/post_room.php" class="flex flex-col items-center text-gray-500 hover:text-primary transition">
                <i class="ri-add-circle-line text-xl"></i>
                <span class="text-xs mt-1">Post Room</span>
            </a>
            <a href="reservations.php" class="flex flex-col items-center text-gray-500 hover:text-primary transition">
                <i class="ri-calendar-check-line text-xl"></i>
                <span class="text-xs mt-1">Reservations</span>
            </a>
            <a href="profile.php" class="flex flex-col items-center text-gray-500 hover:text-primary transition">
                <i class="ri-user-line text-xl"></i>
                <span class="text-xs mt-1">Profile</span>
            </a>
        </div>
    </footer>

    <script>
        // Profile dropdown toggle
        document.getElementById('profileDropdownBtn').addEventListener('click', function() {
            document.getElementById('profileDropdown').classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('profileDropdown');
            const button = document.getElementById('profileDropdownBtn');
            if (!dropdown.contains(event.target)) {
                if (event.target !== button) {
                    dropdown.classList.add('hidden');
                }
            }
        });
    </script>
</body>
</html>