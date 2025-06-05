<?php
// Get current page to highlight active menu item
$current_page = basename($_SERVER['PHP_SELF']);
// Base URL for the application
$base_url = '/unicribs/';

// Get user info from session
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'student';
?>

<!-- Updated Sidebar to match header theme -->
<div class="hidden md:block w-64 bg-white/95 backdrop-blur-md shadow-lg h-[calc(100vh-4rem)] sticky top-16 border-r border-gray-100">
    <!-- Navigation Menu -->
    <div class="p-4 flex-1 overflow-y-auto h-full">
        <?php if ($user_type === 'student'): ?>
            <!-- Student Sidebar Menu -->
            <nav class="space-y-2">
                <div class="px-3 py-2">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Student Portal
                    </h3>
                </div>
                
                <a href="<?php echo $base_url; ?>home.php" 
                   class="group flex items-center px-4 py-3 text-sm font-medium rounded-button transition-all duration-300 <?php echo $current_page === 'home.php' ? 'bg-red-50 text-primary shadow-sm border border-red-100' : 'text-gray-700 hover:bg-gray-50 hover:text-primary'; ?>">
                    <i class="ri-home-4-line mr-3 text-lg"></i>
                    <span>Home</span>
                </a>
                
                <a href="<?php echo $base_url; ?>user/student/search.php" 
                   class="group flex items-center px-4 py-3 text-sm font-medium rounded-button transition-all duration-300 <?php echo $current_page === 'search.php' ? 'bg-red-50 text-primary shadow-sm border border-red-100' : 'text-gray-700 hover:bg-gray-50 hover:text-primary'; ?>">
                    <i class="ri-search-line mr-3 text-lg"></i>
                    <span>Search Rooms</span>
                </a>
                
                <a href="<?php echo $base_url; ?>user/student/reservations.php" 
                   class="group flex items-center px-4 py-3 text-sm font-medium rounded-button transition-all duration-300 <?php echo $current_page === 'reservations.php' ? 'bg-red-50 text-primary shadow-sm border border-red-100' : 'text-gray-700 hover:bg-gray-50 hover:text-primary'; ?>">
                    <i class="ri-calendar-check-line mr-3 text-lg"></i>
                    <span>My Reservations</span>
                </a>
                
                <div class="my-6">
                    <div class="h-px bg-gray-200"></div>
                </div>
                
                <div class="px-3 py-2">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Account
                    </h3>
                </div>
                
                <a href="<?php echo $base_url; ?>profile/profile.php" 
                   class="group flex items-center px-4 py-3 text-sm font-medium rounded-button transition-all duration-300 <?php echo $current_page === 'profile.php' ? 'bg-red-50 text-primary shadow-sm border border-red-100' : 'text-gray-700 hover:bg-gray-50 hover:text-primary'; ?>">
                    <i class="ri-user-line mr-3 text-lg"></i>
                    <span>Profile</span>
                </a>
                
                <a href="<?php echo $base_url; ?>settings.php" 
                   class="group flex items-center px-4 py-3 text-sm font-medium rounded-button transition-all duration-300 <?php echo $current_page === 'settings.php' ? 'bg-red-50 text-primary shadow-sm border border-red-100' : 'text-gray-700 hover:bg-gray-50 hover:text-primary'; ?>">
                    <i class="ri-settings-3-line mr-3 text-lg"></i>
                    <span>Settings</span>
                </a>
            </nav>
            
        <?php else: ?>
            <!-- Landlord Sidebar Menu -->
            <nav class="space-y-2">
                <div class="px-3 py-2">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Management
                    </h3>
                </div>
                
                <a href="<?php echo $base_url; ?>home_l.php" 
                   class="group flex items-center px-4 py-3 text-sm font-medium rounded-button transition-all duration-300 <?php echo $current_page === 'home_l.php' ? 'bg-red-50 text-primary shadow-sm border border-red-100' : 'text-gray-700 hover:bg-gray-50 hover:text-primary'; ?>">
                    <i class="ri-dashboard-line mr-3 text-lg"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="<?php echo $base_url; ?>user/landlord/post_room.php" 
                   class="group flex items-center px-4 py-3 text-sm font-medium rounded-button transition-all duration-300 <?php echo $current_page === 'post_room.php' ? 'bg-red-50 text-primary shadow-sm border border-red-100' : 'text-gray-700 hover:bg-gray-50 hover:text-primary'; ?>">
                    <i class="ri-add-circle-line mr-3 text-lg"></i>
                    <span>Post Room</span>
                </a>
                
                <a href="<?php echo $base_url; ?>user/landlord/manage_rooms.php" 
                   class="group flex items-center px-4 py-3 text-sm font-medium rounded-button transition-all duration-300 <?php echo $current_page === 'manage_rooms.php' ? 'bg-red-50 text-primary shadow-sm border border-red-100' : 'text-gray-700 hover:bg-gray-50 hover:text-primary'; ?>">
                    <i class="ri-home-4-line mr-3 text-lg"></i>
                    <span>Manage Rooms</span>
                </a>
                
                <a href="<?php echo $base_url; ?>user/landlord/reservations.php" 
                   class="group flex items-center px-4 py-3 text-sm font-medium rounded-button transition-all duration-300 <?php echo $current_page === 'reservations.php' ? 'bg-red-50 text-primary shadow-sm border border-red-100' : 'text-gray-700 hover:bg-gray-50 hover:text-primary'; ?>">
                    <i class="ri-calendar-check-line mr-3 text-lg"></i>
                    <span>Reservations</span>
                </a>
                
                <div class="my-6">
                    <div class="h-px bg-gray-00"></div>
                </div>
                
                <div class="px-3 py-2">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Account
                    </h3>
                </div>
                
                <a href="<?php echo $base_url; ?>profile/profile.php" 
                   class="group flex items-center px-4 py-3 text-sm font-medium rounded-button transition-all duration-300 <?php echo $current_page === 'profile.php' ? 'bg-red-50 text-primary shadow-sm border border-red-100' : 'text-gray-700 hover:bg-gray-50 hover:text-primary'; ?>">
                    <i class="ri-user-line mr-3 text-lg"></i>
                    <span>Profile</span>
                </a>
                
                <a href="<?php echo $base_url; ?>settings.php" 
                   class="group flex items-center px-4 py-3 text-sm font-medium rounded-button transition-all duration-300 <?php echo $current_page === 'settings.php' ? 'bg-red-50 text-primary shadow-sm border border-red-100' : 'text-gray-700 hover:bg-gray-50 hover:text-primary'; ?>">
                    <i class="ri-settings-3-line mr-3 text-lg"></i>
                    <span>Settings</span>
                </a>
            </nav>
        <?php endif; ?>
        <!-- Sign Out Button -->
        <div class="p-4 border-t border-gray-100">
            <a href="<?php echo $base_url; ?>includes/logout.php" 
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-button text-gray-700 hover:bg-red-50 hover:text-red-600 transition-all duration-300">
                <i class="ri-logout-box-r-line mr-3 text-lg"></i>
                <span>Sign Out</span>
            </a>
        </div>
    </div>
    

</div>
<!-- End of Sidebar -->