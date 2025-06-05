<?php
$page_title = 'Student Dashboard - UNICRIBS';
require_once $_SERVER['DOCUMENT_ROOT'] . '/UNICRIBS/includes/head.php';
require_once 'core/init.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: index.html');
    exit();
}

// Get user data
$user = new User($pdo);
$userData = $user->getUser($_SESSION['user_id']);

// Process filters from GET parameters
$filters = [];
if (isset($_GET['university_id']) && !empty($_GET['university_id'])) {
    $filters['university_id'] = (int)$_GET['university_id'];
}
if (isset($_GET['price_range']) && !empty($_GET['price_range'])) {
    $priceRange = explode('-', $_GET['price_range']);
    $filters['min_price'] = (int)$priceRange[0];
    $filters['max_price'] = isset($priceRange[1]) ? (int)$priceRange[1] : null;
}
if (isset($_GET['room_type']) && !empty($_GET['room_type'])) {
    $filters['room_type'] = $_GET['room_type'];
}

// Get current page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Get rooms with pagination
$room = new Room($pdo);
$rooms_data = $room->getRooms($filters, $current_page, 12, $_SESSION['user_id']);
$rooms = $rooms_data['rooms'];
$total_pages = $rooms_data['total_pages'];
?>

<body class="bg-gray-50">
    <div class="flex flex-col min-h-screen">
            <?php include 'includes/header.php'; ?> 
            <div class="flex flex-1">
                <?php include 'includes/sidebar.php'; ?>        
                <!-- Page Content -->
                <main class="flex-1 container mx-auto px-4 py-6 pt-20">
                    <div></div>
                        <h1 class="text-2xl md:text-3xl font-bold mb-6 text-gray-800">Available Rooms</h1>
                        
                        <!-- Room Grid -->
                        <?php if (empty($rooms)): ?>
                            <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                                <i class="ri-home-gear-line text-5xl text-gray-300 mb-4"></i>
                                <h3 class="text-xl font-medium text-gray-700 mb-2">No rooms found</h3>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php 
                                $booking = new Booking($pdo);
                                foreach ($rooms as $room): 
                                    $is_liked = $room['is_liked'] ?? false;
                                    $is_booked = $room['booking_count'] > 0;
                                    $booking_status = $booking->getBookingStatus($room['id']);
                                    $pending_count = $booking_status['pending_count'];
                                    $is_available = $booking_status['is_available'];
                                ?>
                                <div class="bg-white rounded-2xl shadow-md overflow-hidden transition-transform duration-200 hover:shadow-lg hover:-translate-y-1 room-card flex flex-col">
                                    <div class="relative">
                                        <img src="<?php echo htmlspecialchars($room['primary_image'] ?? 'assets/images/default-room.jpg'); ?>" 
                                            alt="<?php echo htmlspecialchars($room['title']); ?>" 
                                            class="w-full h-64 object-cover object-center transition-all duration-200" loading="lazy">
                                        <span class="absolute top-4 left-4 bg-primary text-white text-xs font-bold px-3 py-1 rounded-full shadow">
                                            <?php echo htmlspecialchars($room['university_name']); ?>
                                        </span>
                                        <button class="like-btn absolute top-4 right-4 bg-white/90 backdrop-blur-sm w-10 h-10 flex items-center justify-center rounded-full hover:bg-white transition"
                                                data-room-id="<?php echo $room['id']; ?>"
                                                data-liked="<?php echo $is_liked ? 'true' : 'false'; ?>">
                                            <i class="ri-heart-<?php echo $is_liked ? 'fill' : 'line'; ?> text-xl <?php echo $is_liked ? 'text-primary fill-current' : 'text-gray-600'; ?>"></i>
                                        </button>
                                        <?php if ($is_booked): ?>
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
                                                <h3 class="text-lg font-bold text-gray-800 truncate" title="<?php echo htmlspecialchars($room['title']); ?>">
                                                    <?php echo htmlspecialchars($room['title']); ?>
                                                </h3>
                                                <div class="flex items-center text-xs text-gray-500 mt-1">
                                                    <i class="ri-map-pin-line mr-1"></i>
                                                    <span class="truncate block max-w-[180px]" title="<?php echo htmlspecialchars($room['address']); ?>">
                                                        <?php echo htmlspecialchars($room['address']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="text-lg font-bold text-primary whitespace-nowrap ml-2"><?php echo number_format($room['price'], 0); ?> XAF</div>
                                        </div>
                                        <p class="text-gray-600 text-xs mb-3 line-clamp-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; line-clamp: 2; overflow: hidden;">
                                            <?php echo htmlspecialchars($room['description']); ?>
                                        </p>
                                        <div class="flex justify-between items-center mt-auto pt-2">
                                            <div class="flex space-x-3">
                                                <button class="text-gray-500 hover:text-primary transition" title="Comments">
                                                    <i class="ri-chat-3-line"></i>
                                                    <span class="text-xs ml-1"><?php echo $room['comment_count'] ?? 0; ?></span>
                                                </button>
                                                <button class="text-gray-500 hover:text-primary transition share-btn" data-room-id="<?php echo $room['id']; ?>" title="Share">
                                                    <i class="ri-share-forward-line"></i>
                                                </button>
                                                <span class="text-gray-500" title="Likes">
                                                    <i class="ri-heart-line"></i>
                                                    <span class="text-xs ml-1 like-count"><?php echo $room['like_count'] ?? 0; ?></span>
                                                </span>
                                            </div>
                                            <a href="room_detail.php?id=<?php echo $room['id']; ?>" 
                                                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-red-700 transition font-medium <?php echo $is_booked ? 'bg-green-600 hover:bg-green-700' : ''; ?>">
                                                <?php echo $is_booked ? 'BOOKED' : 'VIEW'; ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                            <div class="flex justify-center mt-8">
                                <nav class="flex items-center gap-1">
                                    <?php if ($current_page > 1): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>" 
                                        class="px-3 py-1 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                                            <i class="ri-arrow-left-s-line"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                        class="px-3 py-1 rounded-lg <?php echo $i == $current_page ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> transition">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($current_page < $total_pages): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>" 
                                        class="px-3 py-1 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                                            <i class="ri-arrow-right-s-line"></i>
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </main>
            </div>
    </div>
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

        // Share button functionality
        document.querySelectorAll('.share-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const roomId = this.getAttribute('data-room-id');
                const url = `${window.location.origin}/room_detail.php?id=${roomId}`;
                
                if (navigator.share) {
                    navigator.share({
                        title: 'Check out this room on UNICRIBS',
                        url: url
                    }).catch(err => {
                        console.log('Error sharing:', err);
                        copyToClipboard(url);
                    });
                } else {
                    copyToClipboard(url);
                }
            });
        });

        function copyToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            
            alert('Link copied to clipboard!');
        }
    </script>
</body>
</html>