<?php
$page_title = 'Manage Rooms - UNICRIBS';
require_once '../../core/init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    header('Location: ../../index.php');
    exit();
}

$user = new User($pdo);
$userData = $user->getUser($_SESSION['user_id']);

$room = new Room($pdo);
$rooms = $room->getLandlordRooms($_SESSION['user_id']);

// Get booking requests
$booking = new Booking($pdo);

// Handle price update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_price'])) {
    $room_id = intval($_POST['room_id']);
    $new_price = intval($_POST['new_price']);
    // Only update price for this landlord's room
    $stmt = $pdo->prepare("UPDATE rooms SET price = ? WHERE id = ? AND landlord_id = ?");
    if ($stmt->execute([$new_price, $room_id, $_SESSION['user_id']])) {
        $_SESSION['success'] = "Room price updated successfully";
    } else {
        $_SESSION['error'] = "Failed to update price";
    }
    header("Location: manage_rooms.php");
    exit();
}

// Handle room status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    $room_id = intval($_POST['room_id']);
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE rooms SET status = ? WHERE id = ? AND landlord_id = ?");
    $stmt->execute([$status, $room_id, $_SESSION['user_id']]);
    $_SESSION['success'] = "Room status updated successfully";
    header("Location: manage_rooms.php");
    exit();
}

// Handle room deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_room'])) {
    $room_id = intval($_POST['room_id']);
    if ($room->delete($room_id, $_SESSION['user_id'])) {
        $_SESSION['success'] = "Room deleted successfully";
    } else {
        $_SESSION['error'] = "Failed to delete room";
    }
    header("Location: manage_rooms.php");
    exit();
}

// Use the same image function as in reservations.php
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

// Helper to get room status label and color
function get_room_status_label($room) {
    // status: active, inactive, pending, rejected, etc.
    if ($room['status'] === 'inactive') {
        return ['Inactive', 'gray'];
    }
    if ($room['status'] === 'pending') {
        return ['Pending Approval', 'yellow'];
    }
    if ($room['status'] === 'rejected') {
        return ['Rejected', 'red'];
    }
    if ($room['status'] === 'active') {
        if (!$room['is_available']) {
            return ['Booked', 'yellow'];
        }
        return ['Available', 'green'];
    }
    // fallback
    return [ucfirst($room['status']), 'gray'];
}

?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/UNICRIBS/includes/head.php'; ?>
<body class="bg-gray-50">
    <div class="flex flex-col min-h-screen">
        <?php include '../../includes/header.php'; ?> 
        <div class="flex flex-1">
            <?php include '../../includes/sidebar.php'; ?>     
            <!-- Page Content -->
            <main class="flex-1 container mx-auto px-4 py-6 pt-20">
                <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8 gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Manage Your Rooms</h1>
                        <p class="text-gray-600 mt-1">Keep track of all your listed properties</p>
                    </div>
                    <a href="post_room.php" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-red-700 transition flex items-center justify-center w-full md:w-auto">
                        <i class="ri-add-line mr-2"></i> Add New Room
                    </a>
                </div>
                
                <!-- Notifications -->
                <div class="space-y-4 mb-6">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg flex items-center">
                            <i class="ri-checkbox-circle-fill text-green-500 mr-2"></i>
                            <div>
                                <p class="font-medium"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg flex items-center">
                            <i class="ri-error-warning-fill text-red-500 mr-2"></i>
                            <div>
                                <p class="font-medium"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Room Cards Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (empty($rooms)): ?>
                        <div class="col-span-full bg-white rounded-xl shadow-sm p-8 text-center">
                            <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <i class="ri-home-gear-line text-4xl text-gray-400"></i>
                            </div>
                            <h3 class="text-xl font-medium text-gray-700 mb-2">No rooms found</h3>
                            <p class="text-gray-500 mb-4">Get started by posting your first room listing</p>
                            <a href="post_room.php" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-red-700 transition">
                                <i class="ri-add-line mr-2"></i> Post a Room
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($rooms as $room): 
                            $primary_image = get_primary_image($pdo, $room['id']);
                            // Get booking status for this room
                            $booking_status = $booking->getBookingStatus($room['id']);
                            $pending_count = $booking_status['pending_count'];
                            $is_available = $booking_status['is_available'];
                        ?>
                        <div class="bg-white rounded-2xl shadow-md overflow-hidden transition-all duration-200 hover:shadow-lg hover:-translate-y-1 flex flex-col h-full">
                            <!-- Room Image -->
                            <div class="relative h-56 overflow-hidden">
                                <img src="<?php echo htmlspecialchars($primary_image); ?>"
                                    alt="<?php echo htmlspecialchars($room['title']); ?>"
                                    class="w-full h-full object-cover object-center transition-all duration-300 hover:scale-105" 
                                    loading="lazy">
                                <span class="absolute top-4 left-4 bg-primary text-white text-xs font-bold px-3 py-1 rounded-full shadow">
                                    <?php echo htmlspecialchars($room['university_name'] ?? ''); ?>
                                </span>
                                <span class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm text-gray-800 text-xs font-bold px-3 py-1 rounded-full shadow">
                                    <?php echo number_format($room['price'], 0); ?> XAF
                                </span>
                                <?php if (!$is_available): ?>
                                    <span class="absolute bottom-4 left-4 bg-yellow-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow">
                                        Booked
                                    </span>
                                <?php elseif ($pending_count > 0): ?>
                                    <span class="absolute bottom-4 left-4 bg-yellow-400 text-white text-xs font-bold px-3 py-1 rounded-full shadow">
                                        <?php echo $pending_count; ?> Pending Request<?php echo $pending_count > 1 ? 's' : ''; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="absolute bottom-4 left-4 bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow">
                                        Available
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Room Details -->
                            <div class="flex-1 flex flex-col p-5">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-bold text-gray-800 truncate" title="<?php echo htmlspecialchars($room['title']); ?>">
                                            <?php echo htmlspecialchars($room['title']); ?>
                                        </h3>
                                        <div class="flex items-center text-sm text-gray-500 mt-1">
                                            <i class="ri-map-pin-line mr-1"></i>
                                            <span class="truncate block max-w-[180px]" title="<?php echo htmlspecialchars($room['address']); ?>">
                                                <?php echo htmlspecialchars($room['address']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Status and Stats -->
                                <div class="mt-auto pt-4 border-t border-gray-100">
                                    <div class="flex justify-between items-center">
                                        
                                        <div class="flex items-center">
                                            <?php if (!$is_available): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Booked
                                                </span>
                                            <?php elseif ($pending_count > 0): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <?php echo $pending_count; ?> Pending Request<?php echo $pending_count > 1 ? 's' : ''; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Available
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="flex space-x-3 text-gray-500">
                                            <span class="flex items-center" title="Bookings">
                                                <i class="ri-calendar-check-line mr-1"></i>
                                                <span class="text-xs"><?php echo $room['booking_count']; ?></span>
                                            </span>
                                            <span class="flex items-center" title="Likes">
                                                <i class="ri-heart-line mr-1"></i>
                                                <span class="text-xs"><?php echo $room['like_count']; ?></span>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="flex justify-between items-center mt-4">
                                        <button class="change-price-btn text-sm px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded transition"
                                            data-room-id="<?php echo $room['id']; ?>" 
                                            data-current-price="<?php echo $room['price']; ?>">
                                            <i class="ri-money-dollar-circle-line mr-1"></i> Change Price
                                        </button>
                                        
                                        <div class="flex space-x-2">
                                            <a href="manage_room.php?id=<?php echo $room['id']; ?>" 
                                               class="w-8 h-8 flex items-center justify-center bg-blue-100 hover:bg-blue-200 text-blue-800 rounded-full transition"
                                               title="Edit">
                                                <i class="ri-edit-line text-sm"></i>
                                            </a>
                                            <form method="post" onsubmit="return confirm('Are you sure you want to delete this room? This action cannot be undone.');" class="inline">
                                                <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                                <button type="submit" name="delete_room" 
                                                        class="w-8 h-8 flex items-center justify-center bg-red-100 hover:bg-red-200 text-red-800 rounded-full transition"
                                                        title="Delete">
                                                    <i class="ri-delete-bin-line text-sm"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Change Price Modal -->
    <div id="changePriceModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 hidden transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-sm relative transform transition-all duration-300 scale-95 opacity-0"
             id="modalContent">
            <button onclick="closePriceModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 transition">
                <i class="ri-close-line text-xl"></i>
            </button>
            <div class="mb-4">
                <h2 class="text-xl font-bold text-gray-800">Update Room Price</h2>
                <p class="text-sm text-gray-500">Set a new price for your room listing</p>
            </div>
            <form method="post" id="changePriceForm">
                <input type="hidden" name="room_id" id="modalRoomId">
                <div class="mb-5">
                    <label for="new_price" class="block text-sm font-medium text-gray-700 mb-2">New Price (XAF)</label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500">XAF</span>
                        </div>
                        <input type="number" min="0" name="new_price" id="modalNewPrice" 
                               class="block w-full pl-16 pr-12 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary"
                               required>
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closePriceModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition">
                        Cancel
                    </button>
                    <button type="submit" name="update_price" 
                            class="px-4 py-2 text-sm font-medium text-white bg-primary hover:bg-red-700 rounded-md transition flex items-center">
                        <i class="ri-check-line mr-1"></i> Update Price
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Profile dropdown toggle
        document.getElementById('profileDropdownBtn').addEventListener('click', function() {
            document.getElementById('profileDropdown').classList.toggle('hidden');
        });

        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('profileDropdown');
            const button = document.getElementById('profileDropdownBtn');
            if (!dropdown.contains(event.target) && event.target !== button) {
                dropdown.classList.add('hidden');
            }
        });

        // Change Price Modal Logic with animations
        document.querySelectorAll('.change-price-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const modal = document.getElementById('changePriceModal');
                const modalContent = document.getElementById('modalContent');
                
                document.getElementById('modalRoomId').value = this.getAttribute('data-room-id');
                document.getElementById('modalNewPrice').value = this.getAttribute('data-current-price');
                
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modalContent.classList.remove('scale-95', 'opacity-0');
                    modalContent.classList.add('scale-100', 'opacity-100');
                }, 10);
            });
        });

        function closePriceModal() {
            const modal = document.getElementById('changePriceModal');
            const modalContent = document.getElementById('modalContent');
            
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // Close modal on ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === "Escape") closePriceModal();
        });

        // Form submission handling
        document.getElementById('changePriceForm').addEventListener('submit', function(e) {
            const priceInput = document.getElementById('modalNewPrice');
            if (parseInt(priceInput.value) <= 0) {
                e.preventDefault();
                alert('Please enter a valid price greater than 0');
                priceInput.focus();
            }
        });
    </script>
</body>
</html>