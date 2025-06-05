<?php
$page_title = 'Settings - UNICRIBS';
require_once $_SERVER['DOCUMENT_ROOT'] . '/UNICRIBS/includes/head.php';
require_once 'core/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user = new User($pdo);
$userData = $user->getUser($_SESSION['user_id']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token";
        header("Location: settings.php");
        exit();
    }

    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $bio = trim($_POST['bio']);
        $phone_number = trim($_POST['phone_number']);
        $whatsapp_number = trim($_POST['whatsapp_number']);

        // Handle profile picture upload
        $profile_picture = $userData['profile_picture'];
        if (!empty($_FILES['profile_picture']['name'])) {
            $uploadDir = 'profile/profilepic/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = $_FILES['profile_picture']['name'];
            $fileTmp = $_FILES['profile_picture']['tmp_name'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($fileExt, $allowed)) {
                $newFileName = 'profile_' . $_SESSION['user_id'] . '_' . uniqid() . '.' . $fileExt;
                $fileDest = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmp, $fileDest)) {
                    // Delete old profile picture if it exists and isn't the default
                    if ($profile_picture && $profile_picture !== 'assets/images/default-avatar.svg' && file_exists($profile_picture)) {
                        unlink($profile_picture);
                    }
                    $profile_picture = 'profiles/profilepic' . $newFileName;
                }
            }
        }

        // Update user data
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, bio = ?, phone_number = ?, whatsapp_number = ?, profile_picture = ? WHERE id = ?");
        if ($stmt->execute([$full_name, $bio, $phone_number, $whatsapp_number, $profile_picture, $_SESSION['user_id']])) {
            $_SESSION['success'] = "Profile updated successfully";
        } else {
            $_SESSION['error'] = "Failed to update profile";
        }
    }

    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (password_verify($current_password, $userData['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                    $_SESSION['success'] = "Password changed successfully";
                } else {
                    $_SESSION['error'] = "Failed to change password";
                }
            } else {
                $_SESSION['error'] = "New passwords do not match";
            }
        } else {
            $_SESSION['error'] = "Current password is incorrect";
        }
    }

    // Handle notification preferences
    if (isset($_POST['update_notifications'])) {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $push_notifications = isset($_POST['push_notifications']) ? 1 : 0;
        $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;

        $stmt = $pdo->prepare("UPDATE users SET email_notifications = ?, push_notifications = ?, sms_notifications = ? WHERE id = ?");
        if ($stmt->execute([$email_notifications, $push_notifications, $sms_notifications, $_SESSION['user_id']])) {
            $_SESSION['success'] = "Notification preferences updated";
        } else {
            $_SESSION['error'] = "Failed to update notification preferences";
        }
    }

    // Handle privacy settings
    if (isset($_POST['update_privacy'])) {
        $profile_visibility = $_POST['profile_visibility'];
        $contact_visibility = $_POST['contact_visibility'];

        $stmt = $pdo->prepare("UPDATE users SET profile_visibility = ?, contact_visibility = ? WHERE id = ?");
        if ($stmt->execute([$profile_visibility, $contact_visibility, $_SESSION['user_id']])) {
            $_SESSION['success'] = "Privacy settings updated";
        } else {
            $_SESSION['error'] = "Failed to update privacy settings";
        }
    }

    header("Location: settings.php");
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

            <main class="flex-1 container mx-auto px-4 py-6 pt-20">
                <h1 class="text-2xl md:text-3xl font-bold mb-6 text-gray-800">Account Settings</h1>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-8">
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px">
                            <button id="profile-tab" class="tab-active py-4 px-6 text-sm font-medium text-center border-b-2 border-primary whitespace-nowrap">
                                Profile
                            </button>
                            <button id="security-tab" class="py-4 px-6 text-sm font-medium text-center border-b-2 border-transparent whitespace-nowrap text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Security
                            </button>
                            <button id="notifications-tab" class="py-4 px-6 text-sm font-medium text-center border-b-2 border-transparent whitespace-nowrap text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Notifications
                            </button>
                            <button id="privacy-tab" class="py-4 px-6 text-sm font-medium text-center border-b-2 border-transparent whitespace-nowrap text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Privacy
                            </button>
                        </nav>
                    </div>
                    
                    <!-- Profile Tab -->
                    <div id="profile-content" class="p-6">
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="update_profile">
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div class="md:col-span-2">
                                    <div class="grid grid-cols-1 gap-6">
                                        <div>
                                            <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($userData['full_name']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        </div>
                                        <div>
                                            <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                                            <textarea id="bio" name="bio" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"><?php echo htmlspecialchars($userData['bio'] ?? ''); ?></textarea>
                                        </div>
                                        <div>
                                            <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                            <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($userData['phone_number'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        </div>
                                        <div>
                                            <label for="whatsapp_number" class="block text-sm font-medium text-gray-700 mb-1">WhatsApp Number</label>
                                            <input type="tel" id="whatsapp_number" name="whatsapp_number" value="<?php echo htmlspecialchars($userData['whatsapp_number'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Profile Picture</label>
                                    <div class="flex flex-col items-center">
                                        <div class="relative mb-4">
                                            <img id="profile-preview" src="<?php echo $userData['profile_picture'] ?? '../../assets/images/default-avatar.svg'; ?>" alt="Profile" class="w-32 h-32 rounded-full object-cover border-2 border-gray-200">
                                            <label for="profile_picture" class="absolute bottom-0 right-0 bg-white rounded-full p-2 shadow-md cursor-pointer">
                                                <i class="ri-camera-line text-primary"></i>
                                            </label>
                                            <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="hidden">
                                        </div>
                                        <p class="text-xs text-gray-500 text-center">Click the camera icon to change your profile picture</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-red-700 transition">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Security Tab -->
                    <div id="security-content" class="p-6 hidden">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="change_password">
                            
                            <div class="grid grid-cols-1 gap-6 max-w-md">
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                                </div>
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                    <input type="password" id="new_password" name="new_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                                    <p class="mt-1 text-xs text-gray-500">Password must be at least 8 characters long</p>
                                </div>
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                                </div>
                            </div>
                            <div class="flex justify-end mt-6">
                                <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-red-700 transition">
                                    Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Notifications Tab -->
                    <div id="notifications-content" class="p-6 hidden">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="update_notifications">
                            
                            <div class="space-y-4">
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="email_notifications" name="email_notifications" type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" <?php echo ($userData['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="email_notifications" class="font-medium text-gray-700">Email Notifications</label>
                                        <p class="text-gray-500">Receive important updates via email</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="push_notifications" name="push_notifications" type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" <?php echo ($userData['push_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="push_notifications" class="font-medium text-gray-700">Push Notifications</label>
                                        <p class="text-gray-500">Get instant notifications on your device</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="sms_notifications" name="sms_notifications" type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" <?php echo ($userData['sms_notifications'] ?? 0) ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="sms_notifications" class="font-medium text-gray-700">SMS Notifications</label>
                                        <p class="text-gray-500">Receive text message alerts (standard rates may apply)</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end mt-6">
                                <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-red-700 transition">
                                    Save Preferences
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Privacy Tab -->
                    <div id="privacy-content" class="p-6 hidden">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="update_privacy">
                            
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Profile Visibility</label>
                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <input id="profile-public" name="profile_visibility" type="radio" value="public" class="h-4 w-4 text-primary focus:ring-primary border-gray-300" <?php echo ($userData['profile_visibility'] ?? 'public') === 'public' ? 'checked' : ''; ?>>
                                            <label for="profile-public" class="ml-3 block text-sm font-medium text-gray-700">
                                                Public - Anyone can see your profile
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input id="profile-private" name="profile_visibility" type="radio" value="private" class="h-4 w-4 text-primary focus:ring-primary border-gray-300" <?php echo ($userData['profile_visibility'] ?? 'public') === 'private' ? 'checked' : ''; ?>>
                                            <label for="profile-private" class="ml-3 block text-sm font-medium text-gray-700">
                                                Private - Only logged in users can see your profile
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Contact Information Visibility</label>
                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <input id="contact-public" name="contact_visibility" type="radio" value="public" class="h-4 w-4 text-primary focus:ring-primary border-gray-300" <?php echo ($userData['contact_visibility'] ?? 'public') === 'public' ? 'checked' : ''; ?>>
                                            <label for="contact-public" class="ml-3 block text-sm font-medium text-gray-700">
                                                Public - Anyone can see your contact information
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input id="contact-private" name="contact_visibility" type="radio" value="private" class="h-4 w-4 text-primary focus:ring-primary border-gray-300" <?php echo ($userData['contact_visibility'] ?? 'public') === 'private' ? 'checked' : ''; ?>>
                                            <label for="contact-private" class="ml-3 block text-sm font-medium text-gray-700">
                                                Private - Only users you interact with can see your contact information
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end mt-6">
                                <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-red-700 transition">
                                    Save Privacy Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Account Actions -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Account Actions</h2>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-medium text-gray-800">Download Your Data</h3>
                                <p class="text-sm text-gray-500">Request a copy of all your personal data</p>
                            </div>
                            <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                                Request Data
                            </button>
                        </div>
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-medium text-gray-800">Deactivate Account</h3>
                                <p class="text-sm text-gray-500">Temporarily disable your account</p>
                            </div>
                            <button class="px-4 py-2 bg-yellow-100 text-yellow-800 rounded-lg hover:bg-yellow-200 transition">
                                Deactivate
                            </button>
                        </div>
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-medium text-gray-800">Delete Account</h3>
                                <p class="text-sm text-gray-500">Permanently delete your account and all data</p>
                            </div>
                            <button class="px-4 py-2 bg-red-100 text-red-800 rounded-lg hover:bg-red-200 transition">
                                Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            
            </main>
   
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
            if (!dropdown.contains(event.target) && event.target !== button) {
                dropdown.classList.add('hidden');
            }
        });

        // Tab switching
        const tabs = ['profile', 'security', 'notifications', 'privacy'];
        tabs.forEach(tab => {
            document.getElementById(`${tab}-tab`).addEventListener('click', function() {
                // Update active tab
                document.querySelectorAll('[id$="-tab"]').forEach(t => {
                    t.classList.remove('tab-active', 'text-primary', 'border-primary');
                    t.classList.add('text-gray-500', 'border-transparent');
                });
                this.classList.add('tab-active', 'text-primary', 'border-primary');
                this.classList.remove('text-gray-500', 'border-transparent');
                
                // Show corresponding content
                document.querySelectorAll('[id$="-content"]').forEach(c => c.classList.add('hidden'));
                document.getElementById(`${tab}-content`).classList.remove('hidden');
            });
        });

        // Profile picture preview
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('profile-preview').src = event.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
</body>
</html>