<?php
$page_title = htmlspecialchars(isset($room_data['title']) ? $room_data['title'] . ' - UNICRIBS' : 'Room Details - UNICRIBS');
require_once 'core/init.php';
require_once 'assets/components/star_rating.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}

// Get profile ID from URL
if (!isset($_GET['id'])) {
    header('Location: home.php');
    exit();
}

$profile_id = (int)$_GET['id'];

// Get user data
$user = new User($pdo);
$profile_data = $user->getUser($profile_id);

if (!$profile_data) {
    header('Location: home.php');
    exit();
}

// Get current user data
$current_user = $user->getUser($_SESSION['user_id']);

// Get rooms for this landlord
$room = new Room($pdo);
$rooms = $room->getLandlordRooms($profile_id);

// Get reviews and rating stats for this landlord
$reviews = $room->getLandlordReviews($profile_id);
$rating_stats = $room->getLandlordRatingStats($profile_id);

// Check if current user is viewing their own profile
$is_own_profile = ($profile_id == $_SESSION['user_id']);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile_data['full_name']); ?> - UNICRIBS</title>
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
    <script src="assets/js/script.js"></script>
    <style>
        .hover-scale {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .hover-scale:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .star-rating i {
            color: #E5E7EB;
        }
        .star-rating i.active {
            color: #F59E0B;
        }
    </style>
</head>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/UNICRIBS/includes/head.php'; ?>
<body class="bg-light font-sans text-dark">
    <div class="flex min-h-screen">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Page Content -->
        <main class="flex-1 container mx-auto px-4 py-6 pt-16">
            <!-- Breadcrumb -->
            <div class="flex items-center text-sm text-gray-500 mb-6">
                <a href="home.php" class="hover:text-primary transition-colors">Home</a>
                <i class="ri-arrow-right-s-line mx-2"></i>
                <span>Profile</span>
            </div>
            
            <!-- Profile Header -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6 hover-scale">
                <!-- Cover Photo -->
                <?php
                    // Helper function to get safe image path
                    function getSafeImagePath($imagePath, $folder, $defaultImage) {
                        if (empty($imagePath)) {
                            return $defaultImage;
                        }
                        $fullPath = "uploads/{$folder}/" . $imagePath;
                        if (file_exists(__DIR__ . '/' . $fullPath)) {
                            return $fullPath;
                        }
                        return $defaultImage;
                    }
                    $profileCover = getSafeImagePath($profile_data['cover_image'] ?? '', 'covers', 'assets/images/default-cover.jpg');
                ?>
                <div class="h-48 bg-gradient-to-r from-primary to-secondary relative" style="background-image: url('<?php echo htmlspecialchars($profileCover); ?>'); background-size: cover; background-position: center;">
                    <?php if ($is_own_profile): ?>
                        <button class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm px-4 py-2 rounded-full text-sm font-medium hover:bg-white transition-all flex items-center">
                            <i class="ri-camera-line mr-1"></i> Edit Cover
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- Profile Info -->
                <div class="px-6 pb-6 relative">
                    <div class="flex flex-col md:flex-row md:items-end md:justify-between">
                        <div class="flex items-end -mt-16 space-x-4">
                            <div class="relative">
                                <img src="<?php echo htmlspecialchars($profile_data['profile_picture'] ?? 'assets/images/default-avatar.svg'); ?>" 
                                     alt="Profile picture" 
                                     class="w-32 h-32 rounded-full border-4 border-white object-cover shadow-md"
                                     onerror="this.src='assets/images/default-avatar.svg'">
                                <?php if ($is_own_profile): ?>
                                    <button class="absolute bottom-2 right-2 bg-white/90 backdrop-blur-sm w-8 h-8 flex items-center justify-center rounded-full hover:bg-white transition-all shadow-sm">
                                        <i class="ri-camera-line text-sm"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-4">
                                <h1 class="text-2xl font-bold text-gray-800 font-display"><?php echo htmlspecialchars($profile_data['full_name']); ?></h1>
                                <div class="flex items-center text-gray-500">
                                    <i class="ri-home-3-line mr-1"></i>
                                    <span>Landlord</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex space-x-3 mt-4 md:mt-0">
                            <?php if (!$is_own_profile): ?>
                                <button class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-indigo-700 transition-all font-medium flex items-center">
                                    <i class="ri-chat-3-line mr-1"></i> Message
                                </button>
                                <button class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-emerald-700 transition-all font-medium flex items-center">
                                    <i class="ri-phone-line mr-1"></i> Call
                                </button>
                            <?php else: ?>
                                <a href="edit_profile.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all font-medium flex items-center">
                                    <i class="ri-edit-line mr-1"></i> Edit Profile
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Bio -->
                    <?php if (!empty($profile_data['bio'])): ?>
                        <div class="mt-4">
                            <p class="text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($profile_data['bio'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Stats -->
                    <div class="flex items-center space-x-8 mt-6 pt-6 border-t border-gray-100">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-primary"><?php echo count($rooms); ?></div>
                            <div class="text-sm text-gray-500">Rooms</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-primary"><?php echo $rating_stats['total_reviews'] ?? 0; ?></div>
                            <div class="text-sm text-gray-500">Reviews</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-primary"><?php echo number_format($rating_stats['avg_rating'] ?? 0, 1); ?></div>
                            <div class="text-sm text-gray-500">Rating</div>
                        </div>
                        <?php if ($profile_data['user_type'] === 'landlord'): ?>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-primary"><?php echo $profile_data['booking_count'] ?? 0; ?></div>
                            <div class="text-sm text-gray-500">Bookings</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Contact & Verification Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-6 hover-scale">
                    <h3 class="font-bold text-lg text-gray-800 mb-4 flex items-center">
                        <i class="ri-contacts-book-line text-primary mr-2"></i>
                        Contact Information
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center text-gray-600 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center mr-3">
                                <i class="ri-mail-line text-primary"></i>
                            </div>
                            <span><?php echo htmlspecialchars($profile_data['email']); ?></span>
                        </div>
                        <?php if (!empty($profile_data['phone_number'])): ?>
                            <div class="flex items-center text-gray-600 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center mr-3">
                                    <i class="ri-phone-line text-primary"></i>
                                </div>
                                <span><?php echo htmlspecialchars($profile_data['phone_number']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($profile_data['whatsapp_number'])): ?>
                            <div class="flex items-center text-gray-600 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center mr-3">
                                    <i class="ri-whatsapp-line text-primary"></i>
                                </div>
                                <span><?php echo htmlspecialchars($profile_data['whatsapp_number']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm p-6 hover-scale">
                    <h3 class="font-bold text-lg text-gray-800 mb-4 flex items-center">
                        <i class="ri-verified-badge-line text-primary mr-2"></i>
                        Verification Status
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full <?php echo $profile_data['email_verified'] ? 'bg-green-100' : 'bg-gray-100'; ?> flex items-center justify-center mr-3">
                                    <i class="ri-mail-<?php echo $profile_data['email_verified'] ? 'check' : 'unread'; ?>-line <?php echo $profile_data['email_verified'] ? 'text-green-500' : 'text-gray-500'; ?>"></i>
                                </div>
                                <span class="text-gray-600">Email Verification</span>
                            </div>
                            <span class="text-sm font-medium <?php echo $profile_data['email_verified'] ? 'text-green-500' : 'text-gray-500'; ?>">
                                <?php echo $profile_data['email_verified'] ? 'Verified' : 'Pending'; ?>
                            </span>
                        </div>
                        <?php if ($profile_data['user_type'] === 'landlord'): ?>
                            <div class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full <?php echo !empty($profile_data['cni_number']) ? 'bg-green-100' : 'bg-gray-100'; ?> flex items-center justify-center mr-3">
                                        <i class="ri-id-card-<?php echo !empty($profile_data['cni_number']) ? 'check' : 'unread'; ?>-line <?php echo !empty($profile_data['cni_number']) ? 'text-green-500' : 'text-gray-500'; ?>"></i>
                                    </div>
                                    <span class="text-gray-600">ID Verification</span>
                                </div>
                                <span class="text-sm font-medium <?php echo !empty($profile_data['cni_number']) ? 'text-green-500' : 'text-gray-500'; ?>">
                                    <?php echo !empty($profile_data['cni_number']) ? 'Verified' : 'Pending'; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <?php if ($profile_data['user_type'] === 'student' && !empty($profile_data['student_id'])): ?>
                            <div class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full <?php echo !empty($profile_data['student_id_verified']) ? 'bg-green-100' : 'bg-gray-100'; ?> flex items-center justify-center mr-3">
                                        <i class="ri-graduation-cap-<?php echo !empty($profile_data['student_id_verified']) ? 'check' : 'unread'; ?>-line <?php echo !empty($profile_data['student_id_verified']) ? 'text-green-500' : 'text-gray-500'; ?>"></i>
                                    </div>
                                    <span class="text-gray-600">Student Verification</span>
                                </div>
                                <span class="text-sm font-medium <?php echo !empty($profile_data['student_id_verified']) ? 'text-green-500' : 'text-gray-500'; ?>">
                                    <?php echo !empty($profile_data['student_id_verified']) ? 'Verified' : 'Pending'; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Rooms Section -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6 hover-scale">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-bold text-lg text-gray-800 flex items-center">
                        <i class="ri-home-3-line text-primary mr-2"></i>
                        Posted Rooms
                    </h3>
                    <?php if ($is_own_profile): ?>
                        <a href="add_room.php" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-indigo-700 transition-all font-medium flex items-center">
                            <i class="ri-add-line mr-1"></i> Add Room
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($rooms)): ?>
                    <div class="text-center py-12">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="ri-home-3-line text-3xl text-gray-400"></i>
                        </div>
                        <h4 class="text-lg font-medium text-gray-700 mb-1">No rooms posted yet</h4>
                        <p class="text-gray-500 max-w-md mx-auto">
                            <?php echo $is_own_profile ? 'Get started by adding your first room listing to attract potential tenants' : 'This landlord hasn\'t posted any rooms yet'; ?>
                        </p>
                        <?php if ($is_own_profile): ?>
                            <a href="add_room.php" class="mt-4 inline-block px-6 py-2 bg-primary text-white rounded-lg hover:bg-indigo-700 transition-all font-medium">
                                Add Your First Room
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($rooms as $room_data): 
                            $is_booked = $room_data['booking_count'] > 0;
                            $rating_info = $room->getRoomRating($room_data['id']);
                        ?>
                            <div class="bg-white rounded-xl shadow-sm overflow-hidden transition-all duration-300 hover:shadow-md hover:-translate-y-1 border border-gray-100">
                                <div class="relative">
                                    <img src="<?php echo htmlspecialchars($room_data['primary_image'] ?? 'assets/images/default-room.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($room_data['title']); ?>" 
                                         class="w-full h-48 object-cover">
                                    <span class="absolute top-3 left-3 bg-primary text-white text-xs font-bold px-3 py-1 rounded-full shadow-md">
                                        <?php echo number_format($room_data['price'], 0); ?> XAF
                                    </span>
                                    <?php if ($is_booked): ?>
                                        <span class="absolute top-3 right-3 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-md">
                                            BOOKED
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="p-4">
                                    <h3 class="text-lg font-bold text-gray-800 mb-1 truncate"><?php echo htmlspecialchars($room_data['title']); ?></h3>
                                    <div class="flex items-center text-sm text-gray-500 mb-2">
                                        <i class="ri-map-pin-line mr-1"></i>
                                        <span class="truncate"><?php echo htmlspecialchars($room_data['address']); ?></span>
                                    </div>
                                    
                                    <!-- Rating -->
                                    <div class="flex items-center mb-3">
                                        <div class="star-rating flex mr-2">
                                            <?php
                                            $rating = $rating_info['rating'] ?? 0;
                                            $full_stars = floor($rating);
                                            $has_half_star = ($rating - $full_stars) >= 0.5;
                                            
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $full_stars) {
                                                    echo '<i class="ri-star-fill text-sm active mr-0.5"></i>';
                                                } elseif ($i == $full_stars + 1 && $has_half_star) {
                                                    echo '<i class="ri-star-half-line text-sm active mr-0.5"></i>';
                                                } else {
                                                    echo '<i class="ri-star-line text-sm mr-0.5"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                        <span class="text-xs text-gray-500">(<?php echo $rating_info['review_count'] ?? 0; ?>)</span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-xs px-2 py-1 bg-gray-100 rounded-full"><?php echo ucfirst($room_data['room_type']); ?></span>
                                            <?php if (!empty($room_data['university_name'])): ?>
                                                <span class="text-xs px-2 py-1 bg-gray-100 rounded-full"><?php echo htmlspecialchars($room_data['university_name']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="room_detail.php?id=<?php echo $room_data['id']; ?>" class="text-primary hover:text-indigo-700 text-sm font-medium flex items-center">
                                            View <i class="ri-arrow-right-line ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Reviews Section -->
            <div class="bg-white rounded-xl shadow-sm p-6 hover-scale">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold flex items-center gap-2">
                        <i class="ri-star-line text-primary"></i>
                        Reviews
                        <?php if (!empty($reviews)): ?>
                        <span class="text-sm font-normal text-gray-500">(<?php echo count($reviews); ?>)</span>
                        <?php endif; ?>
                    </h2>
                    <?php if (!empty($reviews)): ?>
                    <div class="flex items-center">
                        <span class="text-2xl font-bold mr-1"><?php echo number_format($rating_stats['avg_rating'] ?? 0, 1); ?></span>
                        <i class="ri-star-fill text-yellow-400"></i>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($reviews)): ?>
                <div class="text-center py-12">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="ri-star-line text-3xl text-gray-400"></i>
                    </div>
                    <h4 class="text-lg font-medium text-gray-700 mb-1">No reviews yet</h4>
                    <p class="text-gray-500 max-w-md mx-auto">
                        <?php echo $is_own_profile ? 'Reviews will appear here once tenants rate your rooms' : 'This landlord hasn\'t received any reviews yet'; ?>
                    </p>
                </div>
                <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($reviews as $review): ?>
                    <div class="border-b border-gray-100 pb-6 last:border-0">
                        <div class="flex items-start gap-4">
                            <?php if (!empty($review['reviewer_avatar'])): ?>
                                <img src="<?php echo htmlspecialchars($review['reviewer_avatar']); ?>" 
                                    alt="<?php echo htmlspecialchars($review['reviewer_name']); ?>" 
                                    class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm">
                            <?php else: ?>
                                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
                                    <i class="ri-user-line text-gray-400 text-xl"></i>
                                </div>
                            <?php endif; ?>
                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-semibold"><?php echo htmlspecialchars($review['reviewer_name']); ?></h4>
                                        <div class="flex items-center gap-1 mt-1">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="ri-star-fill text-sm <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                            <?php endfor; ?>
                                            <span class="text-xs text-gray-500 ml-2"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mt-2"><?php echo nl2br(htmlspecialchars($review['content'])); ?></p>
                                
                                <!-- Room info -->
                                <div class="mt-3 text-sm text-gray-500 flex items-center">
                                    <i class="ri-home-3-line mr-1"></i>
                                    <a href="room_detail.php?id=<?php echo $review['room_id']; ?>" class="text-primary hover:underline">
                                        <?php echo htmlspecialchars($review['room_title']); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>