<?php
$page_title = 'User Profile - UNICRIBS';
require_once $_SERVER['DOCUMENT_ROOT'] . '/UNICRIBS/includes/head.php'; 
require_once '../core/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user = new User($pdo);
$currentUser = $user->getUser($_SESSION['user_id']);

// Check if viewing another user's profile
$profileId = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];
$profileUser = $user->getUser($profileId);

if (!$profileUser) {
    header('Location: ../index.php');
    exit();
}

// Get user's rooms if landlord
$room = new Room($pdo);
$rooms = ($profileUser['user_type'] === 'landlord') ? $room->getLandlordRooms($profileId) : [];

// Get user reviews
$booking = new Booking($pdo);

// Get student activity if student
$studentActivity = [];
if ($profileUser['user_type'] === 'student') {
    $studentActivity = [
        'liked_rooms' => $room->getLikedRooms($profileId),
        'bookings' => $booking->getStudentBookings($profileId),
        'comments' => $booking->getStudentComments($profileId)
    ];
}

// Check if current user can edit this profile
$canEdit = ($_SESSION['user_id'] == $profileId);

// Helper function to get safe image path
function getSafeImagePath($imagePath, $folder, $defaultImage) {
    if (empty($imagePath)) {
        return $defaultImage;
    }
    
    $fullPath = "../uploads/{$folder}/" . $imagePath;
    if (file_exists($fullPath)) {
        return $fullPath;
    }
    
    return $defaultImage;
}

// Get safe image paths
$profileAvatar = getSafeImagePath($profileUser['profile_picture'] ?? '', 'avatars', '../assets/images/default-avatar.svg');
$profileCover = getSafeImagePath($profileUser['cover_image'] ?? '', 'covers', '../assets/images/default-cover.jpg');

?>
<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <?php include '../includes/header.php'; ?>
        <?php include '../includes/sidebar.php'; ?>
        
        <!-- Page Content -->
        <main class="flex-1 container mx-auto px-4 py-6 pt-20">
            <h1 class="text-2xl md:text-3xl font-bold mb-6 text-gray-800">My Profile</h1>
            <!-- Profile Header -->
            <div class="relative">
                <div class="h-64 w-full"
                    style="background-image: url('<?php echo htmlspecialchars($profileCover); ?>'); background-size: cover; background-position: center;">
                </div>
                
                <?php if ($canEdit): ?>
                <button id="editCoverBtn" class="absolute top-4 right-4 bg-white/90 hover:bg-white text-gray-800 px-4 py-2 rounded-full shadow-md flex items-center gap-2 transition-all">
                    <i class="ri-camera-line"></i> <span class="hidden sm:inline">Edit Cover</span>
                </button>
                <?php endif; ?>
                
                <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative">
                    <div class="flex flex-col md:flex-row items-start md:items-end -mt-16 md:-mt-20 relative z-10">
                        <div class="relative group">
                            <img src="<?php echo htmlspecialchars($profileAvatar); ?>" 
                                 alt="<?php echo htmlspecialchars($profileUser['full_name']); ?>" 
                                 class="w-32 h-32 md:w-40 md:h-40 rounded-full border-4 border-white object-cover shadow-lg bg-white"
                                 onerror="this.src='../assets/images/default-avatar.svg'">
                            
                            <?php if ($canEdit): ?>
                            <button id="editAvatarBtn" class="absolute bottom-2 right-2 bg-primary text-white p-2 rounded-full shadow-md hover:bg-red-700 transition-all group-hover:opacity-100 opacity-0">
                                <i class="ri-camera-line"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-4 md:mt-0 md:ml-6 flex-1">
                            <div class="flex flex-col md:flex-row md:items-end justify-between">
                                <div>
                                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 flex items-center gap-2">
                                        <?php echo htmlspecialchars($profileUser['full_name']); ?>
                                        <span class="text-xs px-2 py-1 rounded-full <?php echo $profileUser['user_type'] === 'landlord' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                            <?php echo ucfirst($profileUser['user_type']); ?>
                                        </span>
                                    </h1>
                                    
                                    <?php if (!empty($profileUser['bio'])): ?>
                                    <p class="text-gray-600 mt-1 max-w-2xl"><?php echo nl2br(htmlspecialchars($profileUser['bio'])); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($canEdit): ?>
                                <button id="editProfileBtn" class="mt-4 md:mt-0 px-4 py-2 bg-primary hover:bg-red-700 text-white rounded-lg shadow-md transition-all flex items-center gap-2">
                                    <i class="ri-edit-line"></i> Edit Profile
                                </button>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex flex-wrap gap-4 mt-4 text-sm">
                                <?php if (!empty($profileUser['email'])): ?>
                                <div class="flex items-center text-gray-600">
                                    <i class="ri-mail-line mr-2"></i>
                                    <?php echo htmlspecialchars($profileUser['email']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($profileUser['phone_number'])): ?>
                                <div class="flex items-center text-gray-600">
                                    <i class="ri-phone-line mr-2"></i>
                                    <?php echo htmlspecialchars($profileUser['phone_number']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($profileUser['user_type'] === 'landlord' && !empty($profileUser['cni_number'])): ?>
                                <div class="flex items-center text-gray-600">
                                    <i class="ri-id-card-line mr-2"></i>
                                    CNI: <?php echo htmlspecialchars($profileUser['cni_number']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($profileUser['user_type'] === 'landlord' && !empty($rating['avg_rating'])): ?>
                                <div class="flex items-center text-gray-600">
                                    <div class="flex mr-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="ri-star-fill text-sm <?php echo $i <= round($rating['avg_rating']) ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <?php echo number_format($rating['avg_rating'], 1); ?> (<?php echo $rating['review_count']; ?>)
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left Column -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- About Section -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                                <i class="ri-information-line text-primary"></i>
                                About
                            </h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500 mb-1">Member Since</h3>
                                    <p class="font-medium"><?php echo date('M Y', strtotime($profileUser['created_at'])); ?></p>
                                </div>
                                
                                <?php if (!empty($profileUser['whatsapp_number'])): ?>
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500 mb-1">WhatsApp</h3>
                                    <p class="font-medium"><?php echo htmlspecialchars($profileUser['whatsapp_number']); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($profileUser['user_type'] === 'landlord'): ?>
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500 mb-1">Verified</h3>
                                    <p class="font-medium flex items-center gap-1">
                                        <?php echo !empty($profileUser['cni_number']) ? 'Yes' : 'No'; ?>
                                        <?php if (!empty($profileUser['cni_number'])): ?>
                                        <i class="ri-checkbox-circle-fill text-green-500"></i>
                                        <?php else: ?>
                                        <i class="ri-close-circle-fill text-red-500"></i>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Landlord's Rooms or Student Activity -->
                        <?php if ($profileUser['user_type'] === 'landlord'): ?>
                            <!-- Landlord Rooms Section -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <div class="flex justify-between items-center mb-6">
                                    <h2 class="text-xl font-bold flex items-center gap-2">
                                        <i class="ri-home-4-line text-primary"></i>
                                        Posted Rooms
                                        <?php if (!empty($rooms)): ?>
                                        <span class="text-sm font-normal text-gray-500">(<?php echo count($rooms); ?>)</span>
                                        <?php endif; ?>
                                    </h2>
                                    <?php if ($canEdit): ?>
                                    <a href="../post_room.php" class="px-4 py-2 bg-primary hover:bg-red-700 text-white rounded-lg shadow-md transition-all flex items-center gap-2">
                                        <i class="ri-add-line"></i> Add Room
                                    </a>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (empty($rooms)): ?>
                                <div class="text-center py-12">
                                    <i class="ri-home-4-line text-6xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500 text-lg mb-4">No rooms posted yet.</p>
                                    <?php if ($canEdit): ?>
                                    <a href="../post_room.php" class="inline-block px-6 py-3 bg-primary hover:bg-red-700 text-white rounded-lg shadow-md transition-all">
                                        Post Your First Room
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <?php foreach ($rooms as $room): ?>
                                    <div class="room-card bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all">
                                        <a href="../room.php?id=<?php echo $room['id']; ?>" class="block">
                                            <div class="relative">
                                                <img src="<?php echo !empty($room['primary_image']) ? '../uploads/rooms/'.$room['primary_image'] : '../assets/images/default-room.jpg'; ?>" 
                                                     alt="<?php echo htmlspecialchars($room['title']); ?>" 
                                                     class="w-full h-48 object-cover"
                                                     onerror="this.src='../assets/images/default-room.jpg'">
                                                <span class="absolute top-3 right-3 px-3 py-1 rounded-full text-xs font-medium <?php echo $room['is_available'] ? 'bg-green-500' : 'bg-yellow-500'; ?> text-white">
                                                    <?php echo $room['is_available'] ? 'Available' : 'Booked'; ?>
                                                </span>
                                            </div>
                                            <div class="p-4">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h3 class="font-bold text-lg truncate"><?php echo htmlspecialchars($room['title']); ?></h3>
                                                    <div class="font-bold text-primary text-lg"><?php echo number_format($room['price'], 0); ?> XAF</div>
                                                </div>
                                                <div class="flex items-center text-gray-500 text-sm mb-3">
                                                    <i class="ri-map-pin-line mr-1"></i>
                                                    <span class="truncate"><?php echo htmlspecialchars($room['address']); ?></span>
                                                </div>
                                                <p class="text-gray-600 text-sm line-clamp-3">
                                                    <?php echo htmlspecialchars(substr($room['description'], 0, 120) . (strlen($room['description']) > 120 ? '...' : '')); ?>
                                                </p>
                                            </div>
                                        </a>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- Student Activity Section -->
                            <?php if (!empty($studentActivity['liked_rooms']) || !empty($studentActivity['bookings']) || !empty($studentActivity['comments'])): ?>
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                                    <i class="ri-activity-line text-primary"></i>
                                    Activity Feed
                                </h2>
                                
                                <?php if (!empty($studentActivity['liked_rooms'])): ?>
                                <div class="mb-8">
                                    <h3 class="font-medium text-lg mb-4 text-gray-700 border-b pb-2 flex items-center gap-2">
                                        <i class="ri-heart-line text-red-500"></i>
                                        Recently Liked Rooms
                                    </h3>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <?php foreach ($studentActivity['liked_rooms'] as $room): ?>
                                        <a href="../room.php?id=<?php echo $room['id']; ?>" class="group">
                                            <div class="relative aspect-square overflow-hidden rounded-lg">
                                                <img src="<?php echo !empty($room['primary_image']) ? '../uploads/rooms/'.$room['primary_image'] : '../assets/images/default-room.jpg'; ?>" 
                                                     alt="<?php echo htmlspecialchars($room['title']); ?>" 
                                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                                     onerror="this.src='../assets/images/default-room.jpg'">
                                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition duration-300"></div>
                                            </div>
                                            <div class="mt-2 text-sm font-medium truncate"><?php echo htmlspecialchars($room['title']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo number_format($room['price'], 0); ?> XAF</div>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($studentActivity['bookings'])): ?>
                                <div class="mb-8">
                                    <h3 class="font-medium text-lg mb-4 text-gray-700 border-b pb-2 flex items-center gap-2">
                                        <i class="ri-calendar-check-line text-blue-500"></i>
                                        Booking History
                                    </h3>
                                    <?php foreach ($studentActivity['bookings'] as $booking): ?>
                                    <div class="mb-4 pb-4 border-b border-gray-100 last:border-0">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h4 class="font-medium">Booked <?php echo htmlspecialchars($booking['room_title']); ?></h4>
                                                <p class="text-sm text-gray-500">
                                                    <?php 
                                                        if (!empty($booking['booking_date'])) {
                                                            echo date('M j, Y', strtotime($booking['booking_date']));
                                                        } else {
                                                            echo '<span class="italic text-gray-400">N/A</span>';
                                                        }
                                                    ?>
                                                </p>
                                                <p class="text-sm mt-1 font-medium text-primary"><?php echo number_format($booking['total_amount'], 0); ?> XAF</p>
                                            </div>
                                            <span class="px-3 py-1 text-xs rounded-full font-medium <?php 
                                                echo $booking['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                                ($booking['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                'bg-gray-100 text-gray-800'); ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($studentActivity['comments'])): ?>
                                <div>
                                    <h3 class="font-medium text-lg mb-4 text-gray-700 border-b pb-2 flex items-center gap-2">
                                        <i class="ri-chat-3-line text-green-500"></i>
                                        Recent Comments
                                    </h3>
                                    <?php foreach ($studentActivity['comments'] as $comment): ?>
                                    <div class="mb-4 pb-4 border-b border-gray-100 last:border-0">
                                        <div class="flex items-start gap-3">
                                            <div class="flex-shrink-0">
                                                <img src="<?php echo htmlspecialchars(!empty($comment['room_image']) ? '../uploads/rooms/'.$comment['room_image'] : '../assets/images/default-room.jpg'); ?>" 
                                                     alt="Room" class="w-12 h-12 rounded-lg object-cover"
                                                     onerror="this.src='../assets/images/default-room.jpg'">
                                            </div>
                                            <div>
                                                <h4 class="font-medium">
                                                    Room: <?php echo htmlspecialchars($comment['room_title'] ?? 'N/A'); ?>
                                                    <?php if (!empty($comment['room_number'])): ?>
                                                        <span class="text-xs text-gray-400 ml-2">(#<?php echo htmlspecialchars($comment['room_number']); ?>)</span>
                                                    <?php endif; ?>
                                                </h4>
                                                <p class="text-gray-600 my-1">
                                                    <?php
                                                        // Try to display the comment content using the correct key
                                                        if (isset($comment['comment'])) {
                                                            echo htmlspecialchars($comment['comment']);
                                                        } elseif (isset($comment['content'])) {
                                                            echo htmlspecialchars($comment['content']);
                                                        } elseif (isset($comment['review'])) {
                                                            echo htmlspecialchars($comment['review']);
                                                        } else {
                                                            echo '<span class="italic text-gray-400">No comment text</span>';
                                                        }
                                                    ?>
                                                </p>
                                                <p class="text-sm text-gray-500"><?php echo date('M j, Y', strtotime($comment['created_at'])); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="space-y-6">
                        <!-- Reviews Section -->
                        <?php if (!empty($reviews)): ?>
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                                <i class="ri-star-line text-primary"></i>
                                Reviews
                                <span class="text-sm font-normal text-gray-500">(<?php echo count($reviews); ?>)</span>
                            </h2>
                            
                            <?php foreach ($reviews as $review): ?>
                            <div class="mb-6 pb-6 border-b border-gray-100 last:border-0">
                                <div class="flex items-center gap-3 mb-3">
                                    <img src="<?php echo !empty($review['reviewer_avatar']) ? '../uploads/avatars/'.$review['reviewer_avatar'] : '../assets/images/default-avatar.svg'; ?>" 
                                         alt="<?php echo htmlspecialchars($review['reviewer_name']); ?>" 
                                         class="w-12 h-12 rounded-full"
                                         onerror="this.src='../assets/images/default-avatar.svg'">
                                    <div>
                                        <h4 class="font-semibold"><?php echo htmlspecialchars($review['reviewer_name']); ?></h4>
                                        <div class="flex items-center gap-1">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="ri-star-fill text-sm <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                            <?php endfor; ?>
                                            <span class="text-sm text-gray-500 ml-1"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Contact Card -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                                <i class="ri-chat-3-line text-primary"></i>
                                Contact
                            </h2>
                            
                            <div class="space-y-3">
                                <?php if (!empty($profileUser['email'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($profileUser['email']); ?>" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-all">
                                    <div class="bg-gray-100 p-2 rounded-full">
                                        <i class="ri-mail-line text-gray-600"></i>
                                    </div>
                                    <span><?php echo htmlspecialchars($profileUser['email']); ?></span>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (!empty($profileUser['phone_number'])): ?>
                                <a href="tel:<?php echo htmlspecialchars($profileUser['phone_number']); ?>" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-all">
                                    <div class="bg-gray-100 p-2 rounded-full">
                                        <i class="ri-phone-line text-gray-600"></i>
                                    </div>
                                    <span><?php echo htmlspecialchars($profileUser['phone_number']); ?></span>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (!empty($profileUser['whatsapp_number'])): ?>
                                <a href="https://wa.me/<?php echo htmlspecialchars(str_replace('+', '', $profileUser['whatsapp_number'])); ?>" target="_blank" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-all">
                                    <div class="bg-gray-100 p-2 rounded-full">
                                        <i class="ri-whatsapp-line text-gray-600"></i>
                                    </div>
                                    <span><?php echo htmlspecialchars($profileUser['whatsapp_number']); ?></span>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Profile Modal -->
    <?php if ($canEdit): ?>
    <div id="editProfileModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden transition-opacity duration-300">
        <div class="bg-white rounded-xl p-6 max-w-md w-full max-h-[90vh] overflow-y-auto mx-4 shadow-xl transform transition-all duration-300 scale-95 opacity-0" id="modalContent">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold flex items-center gap-2">
                    <i class="ri-edit-line text-primary"></i>
                    Edit Profile
                </h3>
                <button id="closeEditModal" class="text-gray-500 hover:text-gray-700 p-1 transition-colors">
                    <i class="ri-close-line ri-lg"></i>
                </button>
            </div>
            
            <form id="editProfileForm" class="space-y-4">
                <input type="hidden" name="user_id" value="<?php echo $profileUser['id']; ?>">
                
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" id="full_name" name="full_name" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" 
                           value="<?php echo htmlspecialchars($profileUser['full_name']); ?>" required>
                </div>
                
                <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                    <textarea id="bio" name="bio" 
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" 
                              rows="4" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($profileUser['bio'] ?? ''); ?></textarea>
                </div>
                
                <div>
                    <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="tel" id="phone_number" name="phone_number" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" 
                           value="<?php echo htmlspecialchars($profileUser['phone_number'] ?? ''); ?>" placeholder="+237 6XX XXX XXX">
                </div>
                
                <div>
                    <label for="whatsapp_number" class="block text-sm font-medium text-gray-700 mb-1">WhatsApp Number</label>
                    <input type="tel" id="whatsapp_number" name="whatsapp_number" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" 
                           value="<?php echo htmlspecialchars($profileUser['whatsapp_number'] ?? ''); ?>" placeholder="+237 6XX XXX XXX">
                </div>
                
                <?php if ($profileUser['user_type'] === 'landlord'): ?>
                <div>
                    <label for="cni_number" class="block text-sm font-medium text-gray-700 mb-1">CNI Number</label>
                    <input type="text" id="cni_number" name="cni_number" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" 
                           value="<?php echo htmlspecialchars($profileUser['cni_number'] ?? ''); ?>" placeholder="1234567890123">
                </div>
                <?php endif; ?>
                
                <div class="pt-4">
                    <button type="submit" class="w-full py-3 bg-primary hover:bg-red-700 text-white rounded-lg font-semibold transition-all flex items-center justify-center gap-2 shadow-md">
                        <i class="ri-save-line"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Show loading overlay
        function showLoading() {
            document.getElementById('loadingOverlay').classList.remove('hidden');
        }

        // Hide loading overlay
        function hideLoading() {
            document.getElementById('loadingOverlay').classList.add('hidden');
        }

        // Edit Profile Modal
        const editProfileModal = document.getElementById('editProfileModal');
        const modalContent = document.getElementById('modalContent');
        
        document.getElementById('editProfileBtn')?.addEventListener('click', function() {
            editProfileModal.classList.remove('hidden');
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        });

        document.getElementById('closeEditModal')?.addEventListener('click', function() {
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                editProfileModal.classList.add('hidden');
            }, 300);
        });

        // Close modal when clicking outside
        editProfileModal?.addEventListener('click', function(e) {
            if (e.target === editProfileModal) {
                modalContent.classList.remove('scale-100', 'opacity-100');
                modalContent.classList.add('scale-95', 'opacity-0');
                setTimeout(() => {
                    editProfileModal.classList.add('hidden');
                }, 300);
            }
        });

        // Edit Profile Form Submission
        document.getElementById('editProfileForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            showLoading();
            
            const formData = new FormData(this);
            
            fetch('../core/ajax/update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    // Show success notification
                    showNotification('Profile updated successfully!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification(data.message || 'Failed to update profile', 'error');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
            });
        });

        // Edit Avatar Button
        document.getElementById('editAvatarBtn')?.addEventListener('click', function() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            
            input.onchange = function(e) {
                const file = e.target.files[0];
                if (!file) return;
                
                if (file.size > 2 * 1024 * 1024) {
                    showNotification('Image size should be less than 2MB', 'error');
                    return;
                }
                
                showLoading();
                const formData = new FormData();
                formData.append('avatar', file);
                formData.append('user_id', <?php echo $profileUser['id']; ?>);
                
                fetch('../core/ajax/update_avatar.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        showNotification('Profile picture updated successfully!', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotification(data.message || 'Failed to update avatar', 'error');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    showNotification('An error occurred. Please try again.', 'error');
                });
            };
            
            input.click();
        });

        // Edit Cover Button
        document.getElementById('editCoverBtn')?.addEventListener('click', function() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            
            input.onchange = function(e) {
                const file = e.target.files[0];
                if (!file) return;
                
                if (file.size > 3 * 1024 * 1024) {
                    showNotification('Image size should be less than 3MB', 'error');
                    return;
                }
                
                showLoading();
                const formData = new FormData();
                formData.append('cover', file);
                formData.append('user_id', <?php echo $profileUser['id']; ?>);
                
                fetch('../core/ajax/update_cover.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        showNotification('Cover photo updated successfully!', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotification(data.message || 'Failed to update cover photo', 'error');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    showNotification('An error occurred. Please try again.', 'error');
                });
            };
            
            input.click();
        });

        // Show notification function
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-medium flex items-center gap-2 transition-all transform translate-x-48 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
            notification.innerHTML = `
                <i class="${type === 'success' ? 'ri-checkbox-circle-line' : 'ri-close-circle-line'}"></i>
                ${message}
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.remove('translate-x-48');
                notification.classList.add('translate-x-0');
            }, 10);
            
            setTimeout(() => {
                notification.classList.remove('translate-x-0');
                notification.classList.add('translate-x-48');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }
    </script>
</body>
</html>