<?php
// Get current page to highlight active menu item
$current_page = basename($_SERVER['PHP_SELF']);
// Base URL for the application
$base_url = '/unicribs/';

// Get user info from session
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'student';
?>

<!-- Header Navigation -->
<header id="navbar" class="fixed w-full z-50 bg-white/95 backdrop-blur-md shadow-sm border-b border-gray-100">
    <div class="container mx-auto px-3 py-3 flex items-center justify-between">
        <!-- Logo -->
        <a href="<?php echo $base_url . ($user_type === 'student' ? 'home.php' : 'home_l.php'); ?>" class="flex items-center">
            <span class="text-2xl font-['Pacifico'] text-primary">UNICRIBS</span>
        </a>
        
        <!-- Desktop Navigation - App specific links -->
        <nav class="hidden md:flex items-center space-x-6">
            <?php if ($user_type === 'student'): ?>
                <a href="<?php echo $base_url; ?>home.php" class="text-gray-700 hover:text-primary font-medium transition-colors <?php echo $current_page === 'home.php' ? 'text-primary' : ''; ?>">
                    <i class="ri-home-4-line mr-1"></i>Home
                </a>
                <a href="<?php echo $base_url; ?>user/student/search.php" class="text-gray-700 hover:text-primary font-medium transition-colors <?php echo $current_page === 'search.php' ? 'text-primary' : ''; ?>">
                    <i class="ri-search-line mr-1"></i>Search
                </a>
                <a href="<?php echo $base_url; ?>user/student/reservations.php" class="text-gray-700 hover:text-primary font-medium transition-colors <?php echo $current_page === 'reservations.php' ? 'text-primary' : ''; ?>">
                    <i class="ri-calendar-check-line mr-1"></i>Reservations
                </a>
            <?php else: ?>
                <a href="<?php echo $base_url; ?>home_l.php" class="text-gray-700 hover:text-primary font-medium transition-colors <?php echo $current_page === 'home_l.php' ? 'text-primary' : ''; ?>">
                    <i class="ri-dashboard-line mr-1"></i>Dashboard
                </a>
                <a href="<?php echo $base_url; ?>user/landlord/post_room.php" class="text-gray-700 hover:text-primary font-medium transition-colors <?php echo $current_page === 'post_room.php' ? 'text-primary' : ''; ?>">
                    <i class="ri-add-circle-line mr-1"></i>Post Room
                </a>
                <a href="<?php echo $base_url; ?>user/landlord/manage_rooms.php" class="text-gray-700 hover:text-primary font-medium transition-colors <?php echo $current_page === 'manage_rooms.php' ? 'text-primary' : ''; ?>">
                    <i class="ri-home-4-line mr-1"></i>Rooms
                </a>
                <a href="<?php echo $base_url; ?>user/landlord/reservations.php" class="text-gray-700 hover:text-primary font-medium transition-colors <?php echo $current_page === 'reservations.php' ? 'text-primary' : ''; ?>">
                    <i class="ri-calendar-check-line mr-1"></i>Reservations
                </a>
            <?php endif; ?>
        </nav>
        
        <!-- Desktop User Menu -->
        <div class="hidden md:flex items-center space-x-4">
            <!-- Notifications -->
            <button class="relative p-2 text-gray-600 hover:text-primary transition-colors">
                <i class="ri-notification-line ri-lg"></i>
                <span class="absolute -top-1 -right-1 w-4 h-4 bg-primary text-white text-xs rounded-full flex items-center justify-center">3</span>
            </button>
            
            <!-- User Dropdown -->
            <div class="relative">
                <button id="userMenuBtn" class="flex items-center space-x-2 px-3 py-2 text-gray-700 hover:text-primary font-medium hover:bg-gray-50 rounded-button transition-colors">
                    <i class="ri-user-line"></i>
                    <span><?php echo htmlspecialchars($user_name); ?></span>
                    <i class="ri-arrow-down-s-line text-sm"></i>
                </button>
                
                <!-- Dropdown Menu -->
                <div id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 hidden z-50">
                    <div class="py-2">
                        <div class="px-4 py-2 border-b border-gray-100">
                            <p class="text-sm text-gray-500">Signed in as</p>
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user_name); ?></p>
                            <p class="text-xs text-gray-500 capitalize"><?php echo htmlspecialchars($user_type); ?></p>
                        </div>
                        <a href="<?php echo $base_url; ?>profile/profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="ri-user-line mr-2"></i>Profile
                        </a>
                        <a href="<?php echo $base_url; ?>settings.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="ri-settings-3-line mr-2"></i>Settings
                        </a>
                        <div class="border-t border-gray-100 mt-2 pt-2">
                            <a href="<?php echo $base_url; ?>includes/logout.php" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="ri-logout-box-r-line mr-2"></i>Sign Out
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mobile Menu Button -->
        <button id="mobileMenuBtn" class="md:hidden w-10 h-10 flex items-center justify-center text-gray-700 hover:text-primary transition-colors">
            <i class="ri-menu-line ri-lg"></i>
        </button>
    </div>
    
    <!-- Mobile Menu -->
    <div id="mobileMenu" class="md:hidden hidden bg-white shadow-lg border-t border-gray-100">
        <div class="container mx-auto px-4 py-4">
            <!-- User Info -->
            <div class="flex items-center space-x-3 pb-4 border-b border-gray-100 mb-4">
                <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center">
                    <i class="ri-user-line text-white"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($user_name); ?></p>
                    <p class="text-sm text-gray-500 capitalize"><?php echo htmlspecialchars($user_type); ?></p>
                </div>
            </div>
            
            <!-- Mobile Navigation -->
            <nav class="space-y-2">
                <?php if ($user_type === 'student'): ?>
                    <a href="<?php echo $base_url; ?>home.php" class="flex items-center px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-button font-medium transition-colors <?php echo $current_page === 'home.php' ? 'text-primary bg-red-50' : ''; ?>">
                        <i class="ri-home-4-line mr-3"></i>Home
                    </a>
                    <a href="<?php echo $base_url; ?>user/student/search.php" class="flex items-center px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-button font-medium transition-colors <?php echo $current_page === 'search.php' ? 'text-primary bg-red-50' : ''; ?>">
                        <i class="ri-search-line mr-3"></i>Search Rooms
                    </a>
                    <a href="<?php echo $base_url; ?>user/student/reservations.php" class="flex items-center px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-button font-medium transition-colors <?php echo $current_page === 'reservations.php' ? 'text-primary bg-red-50' : ''; ?>">
                        <i class="ri-calendar-check-line mr-3"></i>My Reservations
                    </a>
                <?php else: ?>
                    <a href="<?php echo $base_url; ?>home_l.php" class="flex items-center px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-button font-medium transition-colors <?php echo $current_page === 'home_l.php' ? 'text-primary bg-red-50' : ''; ?>">
                        <i class="ri-dashboard-line mr-3"></i>Dashboard
                    </a>
                    <a href="<?php echo $base_url; ?>user/landlord/post_room.php" class="flex items-center px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-button font-medium transition-colors <?php echo $current_page === 'post_room.php' ? 'text-primary bg-red-50' : ''; ?>">
                        <i class="ri-add-circle-line mr-3"></i>Post Room
                    </a>
                    <a href="<?php echo $base_url; ?>user/landlord/manage_rooms.php" class="flex items-center px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-button font-medium transition-colors <?php echo $current_page === 'manage_rooms.php' ? 'text-primary bg-red-50' : ''; ?>">
                        <i class="ri-home-4-line mr-3"></i>Manage Rooms
                    </a>
                    <a href="<?php echo $base_url; ?>user/landlord/reservations.php" class="flex items-center px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-button font-medium transition-colors <?php echo $current_page === 'reservations.php' ? 'text-primary bg-red-50' : ''; ?>">
                        <i class="ri-calendar-check-line mr-3"></i>Reservations
                    </a>
                <?php endif; ?>
                
                <!-- Account Section -->
                <div class="pt-4 border-t border-gray-100 mt-4">
                    <a href="<?php echo $base_url; ?>profile/profile.php" class="flex items-center px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-button font-medium transition-colors">
                        <i class="ri-user-line mr-3"></i>Profile
                    </a>
                    <a href="<?php echo $base_url; ?>settings.php" class="flex items-center px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-button font-medium transition-colors">
                        <i class="ri-settings-3-line mr-3"></i>Settings
                    </a>
                    <a href="<?php echo $base_url; ?>includes/logout.php" class="flex items-center px-3 py-2 text-red-600 hover:bg-red-50 rounded-button font-medium transition-colors">
                        <i class="ri-logout-box-r-line mr-3"></i>Sign Out
                    </a>
                </div>
            </nav>
        </div>
    </div>
</header>

<!-- Mobile Footer Navigation (only visible on mobile) -->
<div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-40">
    <div class="flex items-center justify-around py-2">
        <?php if ($user_type === 'student'): ?>
            <a href="<?php echo $base_url; ?>user/student/search.php" class="flex flex-col items-center p-2 text-gray-600 hover:text-primary transition-colors <?php echo $current_page === 'search.php' ? 'text-primary' : ''; ?>">
                <i class="ri-search-line text-xl"></i>
                <span class="text-xs mt-1">Search</span>
            </a>
            <a href="<?php echo $base_url; ?>user/student/reservations.php" class="flex flex-col items-center p-2 text-gray-600 hover:text-primary transition-colors <?php echo $current_page === 'reservations.php' ? 'text-primary' : ''; ?>">
                <i class="ri-calendar-check-line text-xl"></i>
                <span class="text-xs mt-1">Bookings</span>
            </a>
        <?php else: ?>
            <a href="<?php echo $base_url; ?>user/landlord/post_room.php" class="flex flex-col items-center p-2 text-gray-600 hover:text-primary transition-colors <?php echo $current_page === 'post_room.php' ? 'text-primary' : ''; ?>">
                <i class="ri-add-circle-line text-xl"></i>
                <span class="text-xs mt-1">Post</span>
            </a>
            <a href="<?php echo $base_url; ?>user/landlord/reservations.php" class="flex flex-col items-center p-2 text-gray-600 hover:text-primary transition-colors <?php echo $current_page === 'reservations.php' ? 'text-primary' : ''; ?>">
                <i class="ri-calendar-check-line text-xl"></i>
                <span class="text-xs mt-1">Bookings</span>
            </a>
        <?php endif; ?>
        
        <!-- Center Logo -->
        <a href="<?php echo $base_url . ($user_type === 'student' ? 'home.php' : 'home_l.php'); ?>" class="flex flex-col items-center p-2 text-primary">
            <div class="w-8 h-8 rounded-full flex items-center justify-center">
                <img src="<?php echo $base_url; ?>assets/images/cribs.png" alt="Cribs Icon" class="w-full h-full object-cover">
            </div>
            <span class="text-xs mt-1">Home</span>
        </a>
        
        <a href="<?php echo $base_url; ?>profile/profile.php" class="flex flex-col items-center p-2 text-gray-600 hover:text-primary transition-colors <?php echo $current_page === 'profile.php' ? 'text-primary' : ''; ?>">
            <i class="ri-user-line text-xl"></i>
            <span class="text-xs mt-1">Profile</span>
        </a>
        <a href="<?php echo $base_url; ?>settings.php" class="flex flex-col items-center p-2 text-gray-600 hover:text-primary transition-colors <?php echo $current_page === 'settings.php' ? 'text-primary' : ''; ?>">
            <i class="ri-settings-3-line text-xl"></i>
            <span class="text-xs mt-1">Settings</span>
        </a>
    </div>
</div>

<!-- JavaScript for header functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');

    // Mobile menu toggle
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });
    }

    // User dropdown toggle (desktop)
    if (userMenuBtn && userDropdown) {
        userMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.classList.add('hidden');
            }
        });
    }

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
        if (mobileMenu && !mobileMenu.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
            mobileMenu.classList.add('hidden');
        }
    });
});
</script>