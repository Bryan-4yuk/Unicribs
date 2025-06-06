<?php
$page_title = htmlspecialchars(isset($room_data['title']) ? $room_data['title'] . ' - UNICRIBS' : 'Room Details - UNICRIBS');
require_once 'core/init.php';
require_once 'assets/components/star_rating.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}

// Get room ID from URL
if (!isset($_GET['id'])) {
    header('Location: home.php');
    exit();
}

$room_id = (int)$_GET['id'];

// Get room details
$room = new Room($pdo);
$room_data = $room->getRoom($room_id);

// Get room rating info
$rating_info = $room->getRoomRating($room_id);
$room_data['rating'] = $rating_info['rating'];
$room_data['review_count'] = $rating_info['review_count'];

if (!$room_data) {
    header('Location: home.php');
    exit();
}

// Get user data
$user = new User($pdo);
$userData = $user->getUser($_SESSION['user_id']);

// Check if room is liked by current user
$is_liked = $room->isLiked($_SESSION['user_id'], $room_id);

// Get reviews for the room
$reviews = $room->getComments($room_id, false); // Only top-level reviews

// Get similar rooms (based on same university)
$similar_rooms = $room->getRooms(['university_id' => $room_data['university_id']], 1, 4)['rooms'];
$similar_rooms = array_filter($similar_rooms, function($r) use ($room_id) {
    return $r['id'] != $room_id;
});
// Get safe image paths
$profileAvatar = getSafeImagePath($profileUser['profile_picture'] ?? '', 'avatars', '../assets/images/default-avatar.svg');
$profileCover = getSafeImagePath($profileUser['cover_image'] ?? '', 'covers', '../assets/images/default-cover.jpg');

// Process comment/review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $content = trim($_POST['comment']);
    $parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
    $isReview = ($rating !== null && $rating > 0);
    
    if (!empty($content)) {
        $room->addComment($_SESSION['user_id'], $room_id, $content, $parent_id, $isReview, $rating);
        header("Location: room_detail.php?id=$room_id");
        exit();
    }
}
// Process comment deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    $comment_id = (int)$_POST['comment_id'];
    $room->deleteComment($comment_id, $_SESSION['user_id']);
    header("Location: room_detail.php?id=$room_id");
    exit();
}

// Helper: Inline SVG default avatar
function default_avatar_svg($class = 'w-10 h-10') {
    return '<svg class="'.$class.'" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path d="m 8 1 c -1.65625 0 -3 1.34375 -3 3 s 1.34375 3 3 3 s 3 -1.34375 3 -3 s -1.34375 -3 -3 -3 z m -1.5 7 c -2.492188 0 -4.5 2.007812 -4.5 4.5 v 0.5 c 0 1.109375 0.890625 2 2 2 h 8 c 1.109375 0 2 -0.890625 2 -2 v -0.5 c 0 -2.492188 -2.007812 -4.5 -4.5 -4.5 z m 0 0" fill="#2e3436"/></svg>';
}
?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/UNICRIBS/includes/head.php'; ?>
<body class="bg-gray-50">
<div class="flex flex-col min-h-screen">
    <?php include 'includes/header.php'; ?>
    <div class="flex flex-1">
        <?php include 'includes/sidebar.php'; ?>
        <main class="flex-1 container mx-auto px-4 py-6 pt-20">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Image Gallery -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden hover-scale transition">
                        <div class="relative h-64 md:h-80 lg:h-96">
                            <img src="<?php echo htmlspecialchars($room_data['images'][0]['image_url']); ?>"
                                 alt="<?php echo htmlspecialchars($room_data['title']); ?>"
                                 class="w-full h-full object-cover main-image">
                        </div>
                        <div class="p-4 flex overflow-x-auto space-x-3 thumbnails scrollbar-hide">
                            <?php foreach ($room_data['images'] as $image): ?>
                                <img src="<?php echo htmlspecialchars($image['image_url']); ?>"
                                     alt="Room thumbnail"
                                     class="w-16 h-16 object-cover rounded-lg cursor-pointer thumbnail transition <?php echo $image['is_primary'] ? 'ring-2 ring-primary' : 'opacity-80 hover:opacity-100'; ?>">
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Room Details Card -->
                    <div class="bg-white rounded-xl shadow-sm p-6 hover-scale transition">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4">
                            <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($room_data['title']); ?></h1>
                            <div class="flex items-center mt-2 md:mt-0">
                                <i class="ri-map-pin-line text-gray-500 mr-2"></i>
                                <span class="text-gray-600"><?php echo htmlspecialchars($room_data['address']); ?></span>
                            </div>
                        </div>
                        <!-- Room Meta -->
                        <div class="flex flex-wrap gap-4 mb-6">
                            <div class="flex items-center bg-gray-100 px-3 py-1 rounded-full">
                                <i class="ri-home-3-line text-gray-600 mr-2"></i>
                                <span class="text-sm text-gray-700"><?php echo ucfirst($room_data['room_type']); ?></span>
                            </div>
                            <div class="flex items-center bg-gray-100 px-3 py-1 rounded-full">
                                <i class="ri-community-line text-gray-600 mr-2"></i>
                                <span class="text-sm text-gray-700"><?php echo htmlspecialchars($room_data['university_name']); ?></span>
                            </div>
                            <div class="flex items-center bg-gray-100 px-3 py-1 rounded-full">
                                <i class="ri-user-line text-gray-600 mr-2"></i>
                                <span class="text-sm text-gray-700"><?php echo ucfirst($room_data['gender_preference']); ?></span>
                            </div>
                        </div>
                        <!-- Description -->
                        <div class="border-t border-gray-100 py-4 my-4">
                            <h3 class="font-bold text-lg text-gray-800 mb-3">Description</h3>
                            <p class="text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($room_data['description'])); ?></p>
                        </div>
                        <!-- Rating Section -->
                        <div class="border-t border-gray-100 py-4 my-4 flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <?php
                                $rating = $room_data['rating'] ?? 0;
                                $review_count = $room_data['review_count'] ?? count($reviews);
                                renderStarRating($rating, 'w-6 h-6');
                                ?>
                                <span class="ml-2 text-lg font-semibold text-gray-700"><?php echo number_format($rating, 1); ?></span>
                                <span class="text-gray-400 text-sm">(<?php echo $review_count; ?> reviews)</span>
                            </div>
                            <a href="#reviews" class="text-primary hover:underline text-sm font-medium">See all reviews</a>
                        </div>
                        <!-- Amenities -->
                        <div class="mb-6">
                            <h3 class="font-bold text-lg text-gray-800 mb-3">Amenities</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <?php foreach ($room_data['features'] as $feature): ?>
                                    <div class="flex items-center text-gray-600">
                                        <i class="ri-checkbox-circle-fill text-primary mr-2"></i>
                                        <span><?php echo str_replace('_', ' ', ucfirst($feature)); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- Map -->
                        <div class="mb-6">
                            <h3 class="font-bold text-lg text-gray-800 mb-3">Location</h3>
                            <div id="map" class="w-full h-64 rounded-lg overflow-hidden border border-gray-200"></div>
                        </div>                       
                    </div>

                    <!-- Reviews Section -->
                    <div id="reviews" class="bg-white rounded-xl shadow-sm p-6 hover-scale transition">
                        <h2 class="text-xl font-bold text-gray-800 mb-6">Reviews</h2>
                        
                        <!-- Reviews Section - Horizontal Carousel Cards -->
                        <div class="testimonial-slider relative">
                            <?php if (empty($reviews)): ?>
                                <div class="text-center py-8">
                                    <i class="ri-chat-1-line text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500">No reviews yet. Be the first to review!</p>
                                </div>
                            <?php else: ?>
                                <div class="relative overflow-hidden">
                                    <div class="reviews-carousel-track flex flex-nowrap space-x-6 overflow-x-auto scrollbar-hide py-2 px-1" style="scroll-behavior: smooth;">
                                        <?php foreach ($reviews as $review): ?>
                                            <div class="review-card flex flex-col bg-gray-50 rounded-xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 p-5 min-w-[320px] max-w-xs w-[320px]">
                                                <!-- Reviewer Avatar -->
                                                <div class="flex items-center mb-3">
                                                    <?php
                                                    if (!empty($review['profile_picture'])) {
                                                        echo '<img src="'.htmlspecialchars($review['profile_picture']).'" alt="User avatar" class="w-12 h-12 rounded-xl object-cover border border-gray-200 mr-3">';
                                                    } else {
                                                        echo default_avatar_svg('w-12 h-12 mr-3');
                                                    }
                                                    ?>
                                                    <div class="flex-1 min-w-0">
                                                        <h4 class="font-semibold text-gray-900 text-base truncate"><?php echo htmlspecialchars($review['full_name']); ?></h4>
                                                        <span class="text-xs text-gray-400"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></span>
                                                    </div>
                                                    <?php if ($review['user_id'] == $_SESSION['user_id']): ?>
                                                        <form method="POST" class="delete-comment-form ml-2">
                                                            <input type="hidden" name="comment_id" value="<?php echo $review['id']; ?>">
                                                            <button type="submit" name="delete_comment" class="text-red-500 hover:text-red-700 text-xs" title="Delete">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($review['is_review'] && $review['rating']): ?>
                                                    <div class="flex items-center mb-2">
                                                        <?php renderStarRating($review['rating'], 'w-4 h-4'); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="mb-2 flex-1">
                                                    <p class="text-gray-700 text-sm leading-relaxed line-clamp-5"><?php echo nl2br(htmlspecialchars($review['content'])); ?></p>
                                                </div>
                                                <div class="flex items-center space-x-4 border-t border-gray-100 pt-3 mt-2">
                                                    <button class="view-replies-btn text-xs text-gray-500 hover:text-primary transition flex items-center"
                                                        data-review-id="<?php echo $review['id']; ?>"
                                                        data-reply-count="<?php echo count($review['replies'] ?? []); ?>">
                                                        <i class="ri-chat-1-line mr-1"></i>
                                                        <?php echo count($review['replies'] ?? []); ?> repl<?php echo count($review['replies'] ?? []) == 1 ? 'y' : 'ies'; ?>
                                                    </button>
                                                    <button class="reply-btn text-xs text-gray-500 hover:text-primary transition flex items-center">
                                                        <i class="ri-reply-line mr-1"></i> Reply
                                                    </button>
                                                </div>
                                                <!-- Reply Form (hidden by default) -->
                                                <form method="POST" class="reply-form hidden mt-3">
                                                    <input type="hidden" name="parent_id" value="<?php echo $review['id']; ?>">
                                                    <div class="flex items-start space-x-2">
                                                        <?php
                                                        if (!empty($userData['profile_picture'])) {
                                                            echo '<img src="'.htmlspecialchars($userData['profile_picture']).'" alt="User avatar" class="w-7 h-7 rounded-xl object-cover border border-gray-200">';
                                                        } else {
                                                            echo default_avatar_svg('w-7 h-7');
                                                        }
                                                        ?>
                                                        <div class="flex-1">
                                                            <textarea name="comment" rows="2" class="w-full px-2 py-1 border border-gray-200 rounded-lg focus:ring-primary focus:border-primary transition text-xs" placeholder="Write a reply..."></textarea>
                                                            <div class="flex justify-end mt-1">
                                                                <button type="submit" class="px-2 py-1 bg-primary text-white rounded-lg hover:bg-red-700 transition text-xs">Reply</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                                <!-- Replies Container -->
                                                <div id="replies-<?php echo $review['id']; ?>" class="mt-3 pl-4 border-l-2 border-gray-100 space-y-3 hidden">
                                                    <?php if (!empty($review['replies'])): ?>
                                                        <?php foreach ($review['replies'] as $reply): ?>
                                                            <div class="reply flex items-start space-x-2">
                                                                <?php
                                                                if (!empty($reply['profile_picture'])) {
                                                                    echo '<img src="'.htmlspecialchars($reply['profile_picture']).'" alt="User avatar" class="w-7 h-7 rounded-xl object-cover border border-gray-200">';
                                                                } else {
                                                                    echo default_avatar_svg('w-7 h-7');
                                                                }
                                                                ?>
                                                                <div class="flex-1">
                                                                    <div class="flex items-center justify-between">
                                                                        <div>
                                                                            <h4 class="font-medium text-gray-800 text-xs"><?php echo htmlspecialchars($reply['full_name']); ?></h4>
                                                                            <span class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($reply['created_at'])); ?></span>
                                                                        </div>
                                                                        <?php if ($reply['user_id'] == $_SESSION['user_id']): ?>
                                                                            <form method="POST" class="delete-comment-form">
                                                                                <input type="hidden" name="comment_id" value="<?php echo $reply['id']; ?>">
                                                                                <button type="submit" name="delete_comment" class="text-red-500 hover:text-red-700 text-xs" title="Delete">
                                                                                    <i class="ri-delete-bin-line"></i>
                                                                                </button>
                                                                            </form>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <p class="text-gray-600 mt-1 text-xs"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <script>
                                // Carousel scroll logic
                                document.addEventListener('DOMContentLoaded', function () {
                                    const track = document.querySelector('.reviews-carousel-track');
                                    const prevBtn = document.querySelector('.carousel-prev');
                                    const nextBtn = document.querySelector('.carousel-next');
                                    if (track && prevBtn && nextBtn) {
                                        prevBtn.addEventListener('click', function () {
                                            track.scrollBy({ left: -340, behavior: 'smooth' });
                                        });
                                        nextBtn.addEventListener('click', function () {
                                            track.scrollBy({ left: 340, behavior: 'smooth' });
                                        });
                                    }
                                });
                                </script>
                            <?php endif; ?>
                        </div>

                        <!-- Review Form -->
                        <form method="POST" class="mb-8">
                            <h3 class="font-bold text-lg text-gray-800 mb-4">Leave a Review</h3>
                            
                            <!-- Rating Section -->
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-medium mb-2">Your Rating</label>
                                <div class="rating-stars flex space-x-1 mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg data-rating="<?php echo $i; ?>" 
                                            class="star w-10 h-10 text-gray-300 hover:text-yellow-400 cursor-pointer transition" 
                                            fill="currentColor" 
                                            viewBox="0 0 22 20">
                                            <path d="M20.924 7.625a1.523 1.523 0 0 0-1.238-1.044l-5.051-.734-2.259-4.577a1.534 1.534 0 0 0-2.752 0L7.365 5.847l-5.051.734A1.535 1.535 0 0 0 1.463 9.2l3.656 3.563-.863 5.031a1.532 1.532 0 0 0 2.226 1.616L11 17.033l4.518 2.375a1.534 1.534 0 0 0 2.226-1.617l-.863-5.03L20.537 9.2a1.523 1.523 0 0 0 .387-1.575Z"/>
                                        </svg>
                                    <?php endfor; ?>
                                    <input type="hidden" name="rating" value="0">
                                </div>
                            </div>
                            
                            <!-- Review Content -->
                            <div class="mb-4">
                                <label for="review-content" class="block text-gray-700 text-sm font-medium mb-2">Your Review</label>
                                <textarea name="comment" id="review-content" rows="4" 
                                        class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-primary focus:border-primary transition" 
                                        placeholder="Share your experience with this room..."></textarea>
                            </div>
                            
                            <!-- Submit Button -->
                            <button type="submit" 
                                    class="w-full py-3 bg-primary text-white rounded-lg hover:bg-red-700 transition font-medium">
                                Submit Review
                            </button>
                        </form>
                    </div>
                </div>
                <!-- Right Column - Booking and Sidebar -->
                <div class="space-y-6 sticky-sidebar">
                    <?php
                    // Check if user has an existing booking for this room
                    $has_booking = false;
                    $booking_status = '';
                    if (isset($_SESSION['user_id'])) {
                        require_once 'core/classes/Booking.php';
                        $booking = new Booking($pdo);
                        $user_bookings = $booking->getStudentBookings($_SESSION['user_id']);
                        foreach ($user_bookings as $user_booking) {
                            if ($user_booking['room_id'] == $room_id) {
                                $has_booking = true;
                                $booking_status = $user_booking['status'];
                                break;
                            }
                        }
                    }
                    ?>
                    <!-- Booking Card -->
                    <div class="bg-white rounded-xl shadow-sm p-6 hover-scale transition">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-2xl font-bold text-primary"><?php echo number_format($room_data['price'], 0); ?> XAF</span>
                            <span class="text-sm text-gray-500">per month</span>
                        </div>
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                <i class="ri-calendar-line text-gray-500 mr-3"></i>
                                <div>
                                    <p class="text-xs text-gray-500">Available from</p>
                                    <p class="text-gray-700 font-medium"><?php echo date('M j, Y', strtotime($room_data['available_from'])); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                <i class="ri-home-3-line text-gray-500 mr-3"></i>
                                <div>
                                    <p class="text-xs text-gray-500">Room type</p>
                                    <p class="text-gray-700 font-medium"><?php echo ucfirst($room_data['room_type']); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                <i class="ri-user-line text-gray-500 mr-3"></i>
                                <div>
                                    <p class="text-xs text-gray-500">For</p>
                                    <p class="text-gray-700 font-medium"><?php echo $room_data['gender_preference'] === 'any' ? 'Any gender' : ucfirst($room_data['gender_preference']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mb-6">
                            <button id="likeBtn" class="like-btn flex items-center space-x-1 text-gray-500 hover:text-primary transition"
                                    data-room-id="<?php echo $room_id; ?>"
                                    data-liked="<?php echo $is_liked ? 'true' : 'false'; ?>">
                                <i class="ri-heart-<?php echo $is_liked ? 'fill' : 'line'; ?> text-xl <?php echo $is_liked ? 'text-primary fill-current' : 'text-gray-600'; ?>"></i>
                                <span class="like-count"><?php echo $room->getLikeCount($room_id); ?></span>
                            </button>
                            <button class="share-btn flex items-center space-x-1 text-gray-500 hover:text-primary transition"
                                    data-room-id="<?php echo $room_id; ?>">
                                <i class="ri-share-forward-line text-xl"></i>
                                <span>Share</span>
                            </button>
                        </div>
                        <button id="bookBtn" class="w-full py-3 <?php 
                            if ($has_booking) {
                                if ($booking_status === 'pending') {
                                    echo 'bg-yellow-500 hover:bg-yellow-600';
                                } elseif ($booking_status === 'approved') {
                                    echo 'bg-green-500 hover:bg-green-600';
                                } else {
                                    echo 'bg-gray-500 hover:bg-gray-600 cursor-not-allowed';
                                }
                            } else {
                                echo 'bg-primary hover:bg-red-700';
                            }
                        ?> text-white rounded-lg transition font-medium text-lg shadow-md hover:shadow-lg"
                        <?php if ($has_booking) echo 'disabled'; ?>>
                            <?php
                            if ($has_booking) {
                                if ($booking_status === 'pending') {
                                    echo 'Request Sent';
                                } elseif ($booking_status === 'approved') {
                                    echo 'Booked';
                                } else {
                                    echo 'Not Available';
                                }
                            } else {
                                echo 'BOOK NOW';
                            }
                            ?>
                        </button>
                    </div>
                    <!-- Booking Modal -->
                    <div id="bookingModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
                        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                            </div>
                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Book This Room</h3>
                                    <form id="bookingForm">
                                        <input type="hidden" id="modalRoomId" name="room_id">
                                        <div class="mb-4">
                                            <label for="startDate" class="block text-sm font-medium text-gray-700">Start Date</label>
                                            <input type="date" id="startDate" name="start_date" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" required>
                                        </div>
                                        <div class="mb-4">
                                            <label for="endDate" class="block text-sm font-medium text-gray-700">End Date</label>
                                            <input type="date" id="endDate" name="end_date" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" required>
                                        </div>
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700">Duration</label>
                                            <p id="durationDisplay" class="text-sm text-gray-500">Select dates to calculate duration</p>
                                        </div>
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700">Total Amount</label>
                                            <p id="totalAmountDisplay" class="text-lg font-semibold text-gray-900">-</p>
                                        </div>
                                        <div class="mb-4">
                                            <label for="specialRequests" class="block text-sm font-medium text-gray-700">Special Requests (Optional)</label>
                                            <textarea id="specialRequests" name="special_requests" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"></textarea>
                                        </div>
                                    </form>
                                </div>
                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button type="button" id="confirmBookBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm">
                                        Submit Booking Request
                                    </button>
                                    <button type="button" id="closeBookingModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Landlord Card -->
                    <div class="bg-white rounded-xl shadow-sm p-6 hover-scale transition">
                        <?php
                        // Fetch landlord info
                        $landlord = (new User($pdo))->getUser($room_data['landlord_id']);
                        // Get landlord rating stats
                        $landlord_rating = (new Room($pdo))->getLandlordRatingStats($room_data['landlord_id']);
                        // Get landlord avatar path using the same helper as profile page
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
                        $landlordAvatar = getSafeImagePath($landlord['profile_picture'] ?? '', 'avatars', 'assets/images/default-avatar.svg');
                        ?>
                        <div class="flex items-center space-x-4 mb-4">
                            <?php
                            if (!empty($landlordAvatar)) {
                                echo '<img src="' . htmlspecialchars($landlordAvatar) . '" alt="Landlord avatar" class="w-14 h-14 rounded-full" onerror="this.src=\'assets/images/default-avatar.svg\'">';
                            } else {
                                echo default_avatar_svg('w-14 h-14');
                            }
                            ?>
                            <div>
                                <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($landlord['full_name']); ?></h3>
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="ri-star-fill text-yellow-400 mr-1"></i>
                                    <span>
                                        <?php
                                        echo number_format($landlord_rating['avg_rating'], 1) . ' (' . $landlord_rating['total_reviews'] . ' reviews)';
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3 mb-6">
                            <?php if (!empty($landlord['phone_number'])): ?>
                                <a href="tel:<?php echo htmlspecialchars($landlord['phone_number']); ?>" class="flex items-center p-2 rounded-lg hover:bg-gray-50 transition">
                                    <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center mr-3">
                                        <i class="ri-phone-line text-gray-600"></i>
                                    </div>
                                    <span class="text-gray-700"><?php echo htmlspecialchars($landlord['phone_number']); ?></span>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($landlord['email'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($landlord['email']); ?>" class="flex items-center p-2 rounded-lg hover:bg-gray-50 transition">
                                    <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center mr-3">
                                        <i class="ri-mail-line text-gray-600"></i>
                                    </div>
                                    <span class="text-gray-700"><?php echo htmlspecialchars($landlord['email']); ?></span>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($landlord['whatsapp_number'])): ?>
                                <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $landlord['whatsapp_number']); ?>" target="_blank" class="flex items-center p-2 rounded-lg hover:bg-gray-50 transition">
                                    <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center mr-3">
                                        <i class="ri-whatsapp-line text-gray-600"></i>
                                    </div>
                                    <span class="text-gray-700"><?php echo htmlspecialchars($landlord['whatsapp_number']); ?></span>
                                </a>
                            <?php endif; ?>
                        </div>
                        <a href="profile/profile.php?id=<?php echo $landlord['id']; ?>" class="block w-full py-2 text-center border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition font-medium">
                            View Profile
                        </a>
                    </div>
                    <!-- Similar Rooms -->
                    <?php
                    // Get up to 4 rooms (including current), then filter out current, so up to 3 others
                    $similar_rooms = $room->getRooms(['university_id' => $room_data['university_id']], 1, 4)['rooms'];
                    $similar_rooms = array_filter($similar_rooms, function($r) use ($room_id) {
                        return $r['id'] != $room_id;
                    });
                    $similar_rooms = array_slice($similar_rooms, 0, 3);
                    ?>
                    <?php if (!empty($similar_rooms)): ?>
                        <div class="bg-white rounded-xl shadow-sm p-6 hover-scale transition">
                            <h3 class="font-bold text-gray-800 mb-4">Similar Rooms</h3>
                            <div class="space-y-4">
                                <?php foreach ($similar_rooms as $similar): ?>
                                    <a href="room_detail.php?id=<?php echo $similar['id']; ?>" class="flex items-center space-x-3 group p-2 rounded-lg hover:bg-gray-50 transition">
                                        <img src="<?php echo htmlspecialchars($similar['primary_image'] ?? 'assets/images/default-room.jpg'); ?>"
                                             alt="Room thumbnail" class="w-16 h-16 object-cover rounded-lg">
                                        <div>
                                            <h4 class="font-medium text-gray-800 group-hover:text-primary transition"><?php echo htmlspecialchars($similar['title']); ?></h4>
                                            <div class="flex items-center text-sm text-gray-500">
                                                <span><?php echo number_format($similar['price'], 0); ?> XAF</span>
                                                <span class="mx-2">•</span>
                                                <span><?php echo ucfirst($similar['room_type']); ?></span>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Star rating input
    function handleStarClick(e) {
        const rating = parseInt(this.dataset.rating);
        const stars = this.parentElement.querySelectorAll('.star');
        const ratingInput = this.parentElement.querySelector('input[name="rating"]');
        stars.forEach((s, i) => {
            s.classList.toggle('text-yellow-400', i < rating);
            s.classList.toggle('text-gray-300', i >= rating);
        });
        if (ratingInput) ratingInput.value = rating;
    }
    document.querySelectorAll('.star').forEach(star => {
        star.addEventListener('click', handleStarClick);
    });

    // Reply button toggle
    document.querySelectorAll('.reply-btn').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const flex1 = this.closest('.flex-1');
            if (!flex1) return;
            const replyForm = flex1.querySelector('.reply-form');
            document.querySelectorAll('.reply-form').forEach(form => {
                if (form !== replyForm) form.classList.add('hidden');
            });
            if (replyForm) replyForm.classList.toggle('hidden');
        });
    });

    // View replies button
    document.querySelectorAll('.view-replies-btn').forEach(button => {
        button.addEventListener('click', function () {
            const reviewId = this.dataset.reviewId;
            const repliesContainer = document.getElementById(`replies-${reviewId}`);
            if (!repliesContainer) return;
            if (repliesContainer.classList.contains('hidden')) {
                repliesContainer.classList.remove('hidden');
                this.innerHTML = `<i class="ri-arrow-up-s-line mr-1"></i> Hide replies`;
            } else {
                repliesContainer.classList.add('hidden');
                const count = this.getAttribute('data-reply-count') || '';
                this.innerHTML = `<i class="ri-chat-1-line mr-1"></i> View ${count} repl${count > 1 ? 'ies' : 'y'}`;
            }
        });
    });
    
        // Google Map
        window.initMap = function () {
            const location = {
                lat: <?php echo $room_data['latitude'] ?: '3.8480'; ?>,
                lng: <?php echo $room_data['longitude'] ?: '11.5021'; ?>
            };
            const map = new google.maps.Map(document.getElementById("map"), {
                zoom: 15,
                center: location,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
                styles: [{ featureType: "poi", stylers: [{ visibility: "off" }] }]
            });
            new google.maps.Marker({
                position: location,
                map: map,
                title: "<?php echo htmlspecialchars($room_data['title']); ?>"
            });
        };

        // Image Gallery
        const mainImage = document.querySelector('.main-image');
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.addEventListener('click', function () {
                if (mainImage) mainImage.src = this.src;
                document.querySelectorAll('.thumbnail').forEach(t => {
                    t.classList.remove('ring-2', 'ring-primary', 'opacity-80');
                    t.classList.add('opacity-80');
                });
                this.classList.remove('opacity-80');
                this.classList.add('ring-2', 'ring-primary');
            });
        });

        // Like Button
        const likeBtn = document.getElementById('likeBtn');
        if (likeBtn) {
            likeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                const roomId = this.dataset.roomId;
                const isLiked = this.dataset.liked === 'true';
                const icon = this.querySelector('i');
                const likeCount = this.querySelector('.like-count');
                fetch('api/like.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ room_id: roomId, action: isLiked ? 'unlike' : 'like' })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        this.dataset.liked = (!isLiked).toString();
                        icon.className = isLiked ? 'ri-heart-line text-xl text-gray-600' : 'ri-heart-fill text-xl text-primary fill-current';
                        if (likeCount) likeCount.textContent = data.like_count;
                    }
                });
            });
        }

        // Booking functionality
        const bookBtn = document.getElementById('bookBtn');
        const bookingModal = document.getElementById('bookingModal');

        if (bookBtn && bookingModal) {
            bookBtn.addEventListener('click', function() {
                bookingModal.classList.remove('hidden');
                document.getElementById('modalRoomId').value = <?php echo $room_id; ?>;
                bookingModal.setAttribute('data-price', <?php echo $room_data['price']; ?>);
                
                // Set minimum dates
                const today = new Date().toISOString().split('T')[0];
                document.getElementById('startDate').min = today;
                document.getElementById('endDate').min = today;
            });
            
            // Close modal
            document.getElementById('closeBookingModal').addEventListener('click', function() {
                bookingModal.classList.add('hidden');
            });
            
            // Date change handlers
            const startDateInput = document.getElementById('startDate');
            const endDateInput = document.getElementById('endDate');
            
            function calculateDuration() {
                if (startDateInput.value && endDateInput.value) {
                    const start = new Date(startDateInput.value);
                    const end = new Date(endDateInput.value);
                    
                    if (end > start) {
                        const diffTime = Math.abs(end - start);
                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                        const months = Math.ceil(diffDays / 30);
                        
                        document.getElementById('durationDisplay').textContent = `${months} month${months !== 1 ? 's' : ''}`;
                        
                        // Calculate total amount
                        const price = parseFloat(bookingModal.getAttribute('data-price')) || 0;
                        const total = price * months;
                        document.getElementById('totalAmountDisplay').textContent = `${total.toLocaleString()} XAF`;
                    }
                }
            }
            
            startDateInput.addEventListener('change', function() {
                if (this.value) {
                    endDateInput.min = this.value;
                    calculateDuration();
                }
            });
            
            endDateInput.addEventListener('change', calculateDuration);
            
            // Submit booking
            document.getElementById('confirmBookBtn').addEventListener('click', function() {
                const roomId = document.getElementById('modalRoomId').value;
                const startDate = document.getElementById('startDate').value;
                const endDate = document.getElementById('endDate').value;
                const specialRequests = document.getElementById('specialRequests').value;
                
                fetch('core/ajax/book_room.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        room_id: roomId, 
                        start_date: startDate, 
                        end_date: endDate,
                        special_requests: specialRequests
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Booking request submitted successfully!', 'success');
                        bookingModal.classList.add('hidden');
                        
                        // Update button state
                        bookBtn.textContent = 'Request Sent';
                        bookBtn.classList.remove('bg-primary', 'hover:bg-red-700');
                        bookBtn.classList.add('bg-yellow-500', 'hover:bg-yellow-600');
                        bookBtn.disabled = true;
                    } else {
                        showNotification(data.error || 'Failed to submit booking request', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred. Please try again.', 'error');
                });
            });
        }
        // Share Button
        function copyToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            alert('Link copied to clipboard!');
        }
        document.querySelectorAll('.share-btn').forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                const roomId = this.dataset.roomId;
                const url = `${window.location.origin}/room_detail.php?id=${roomId}`;
                if (navigator.share) {
                    navigator.share({ title: 'Check out this room on UNICRIBS', url })
                    .catch(() => copyToClipboard(url));
                } else {
                    copyToClipboard(url);
                }
            });
        });

        

        // Enhanced Star Rating Interaction
    document.querySelectorAll('.rating-stars .star').forEach(star => {
    // Click handler
    star.addEventListener('click', function() {
        const rating = parseInt(this.getAttribute('data-rating'));
        const container = this.closest('.rating-stars');
        container.querySelectorAll('.star').forEach((s, i) => {
            const starRating = parseInt(s.getAttribute('data-rating'));
            if (starRating <= rating) {
                s.classList.add('text-yellow-400');
                s.classList.remove('text-gray-300');
            } else {
                s.classList.add('text-gray-300');
                s.classList.remove('text-yellow-400');
            }
        });
        container.querySelector('input[name="rating"]').value = rating;
    });
    
    // Hover effect
    star.addEventListener('mouseover', function() {
        const rating = parseInt(this.getAttribute('data-rating'));
        const container = this.closest('.rating-stars');
        container.querySelectorAll('.star').forEach((s, i) => {
            const starRating = parseInt(s.getAttribute('data-rating'));
            s.classList.toggle('text-yellow-300', starRating <= rating);
        });
    });
    
        // Mouseout reset
        star.addEventListener('mouseout', function() {
            const container = this.closest('.rating-stars');
            const currentRating = parseInt(container.querySelector('input[name="rating"]').value) || 0;
            container.querySelectorAll('.star').forEach((s, i) => {
                const starRating = parseInt(s.getAttribute('data-rating'));
                if (currentRating > 0) {
                    s.classList.toggle('text-yellow-400', starRating <= currentRating);
                    s.classList.toggle('text-gray-300', starRating > currentRating);
                } else {
                    s.classList.add('text-gray-300');
                    s.classList.remove('text-yellow-400', 'text-yellow-300');
                }
            });
        });
        });
    
    }); // <-- Correct closing for DOMContentLoaded
    
    </script>
</body>
</html>