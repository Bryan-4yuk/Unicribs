<?php
$page_title = 'Post New Room - UNICRIBS';
require_once '../../core/init.php';

// Check if user is logged in and is a landlord
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    header('Location: ../../index.html');
    exit();
}

$user = new User($pdo);
$userData = $user->getUser($_SESSION['user_id']);

// Get universities for dropdown
$university = new University($pdo);
$universities = $university->getAllUniversities();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token";
        header('Location: post_room.php');
        exit();
    }

    // Process form data
    $data = [
        'title' => trim($_POST['title']),
        'description' => trim($_POST['description']),
        'price' => floatval($_POST['price']),
        'address' => trim($_POST['address']),
        'latitude' => isset($_POST['latitude']) ? floatval($_POST['latitude']) : null,
        'longitude' => isset($_POST['longitude']) ? floatval($_POST['longitude']) : null,
        'university_id' => intval($_POST['university_id']),
        'room_type' => $_POST['room_type'],
        'gender_preference' => $_POST['gender_preference'],
        'available_from' => $_POST['available_from'],
        'features' => isset($_POST['features']) ? $_POST['features'] : []
    ];

    // Validate required fields
    if (empty($data['title']) || empty($data['description']) || empty($data['price']) || 
        empty($data['address']) || empty($data['university_id'])) {
        $_SESSION['error'] = "Please fill in all required fields";
        header('Location: post_room.php');
        exit();
    }

    // Handle image uploads
    $uploadedImages = [];
    if (!empty($_FILES['images']['name'][0])) {
        $uploadDir = '../../assets/images/rooms/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true); // Changed to 0755 for better security
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) continue;

            $fileName = $_FILES['images']['name'][$key];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($fileExt, $allowed)) {
                $newFileName = uniqid('room_', true) . '.' . $fileExt;
                $fileDest = $uploadDir . $newFileName;

                if (move_uploaded_file($tmp_name, $fileDest)) {
                    $uploadedImages[] = 'assets/images/rooms/' . $newFileName; // Store relative path
                }
            }
        }
    }

    // Add images to data if any were uploaded
    if (!empty($uploadedImages)) {
        $data['images'] = $uploadedImages;
    }

    // Create room
    $room = new Room($pdo);
    $roomId = $room->create($_SESSION['user_id'], $data);

    if ($roomId) {
        $_SESSION['success'] = "Room posted successfully!";
        header('Location: manage_rooms.php?id=' . $roomId);
        exit();
    } else {
        $_SESSION['error'] = "Failed to create room. Please try again.";
        header('Location: post_room.php');
        exit();
    }
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/UNICRIBS/includes/head.php'; ?>
<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <?php include '../../includes/header.php'; ?>
        <?php include '../../includes/sidebar.php'; ?>
        
        <!-- Page Content -->
            <main class="flex-1 container mx-auto px-4 py-6 pt-20">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Post a New Room</h1>
                    <a href="home_l.php" class="text-primary hover:underline">Back to Dashboard</a>
                </div>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <form method="post" enctype="multipart/form-data" id="roomForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <!-- Basic Information -->
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-gray-800 mb-4">Basic Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Room Title*</label>
                                    <input type="text" id="title" name="title" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                                </div>
                                <div>
                                    <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Monthly Price (XAF)*</label>
                                    <input type="number" id="price" name="price" min="0" step="1000" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                                </div>
                                <div class="md:col-span-2">
                                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description*</label>
                                    <textarea id="description" name="description" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Room Type -->
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-gray-800 mb-4">Room Type</h2>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <input type="radio" id="single" name="room_type" value="single" class="hidden peer" checked>
                                    <label for="single" class="flex flex-col items-center p-4 border border-gray-300 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-red-50">
                                        <i class="ri-hotel-bed-line text-2xl mb-2"></i>
                                        <span>Single Room</span>
                                    </label>
                                </div>
                                <div>
                                    <input type="radio" id="shared" name="room_type" value="shared" class="hidden peer">
                                    <label for="shared" class="flex flex-col items-center p-4 border border-gray-300 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-red-50">
                                        <i class="ri-hotel-line text-2xl mb-2"></i>
                                        <span>Shared Room</span>
                                    </label>
                                </div>
                                <div>
                                    <input type="radio" id="apartment" name="room_type" value="apartment" class="hidden peer">
                                    <label for="apartment" class="flex flex-col items-center p-4 border border-gray-300 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-red-50">
                                        <i class="ri-building-2-line text-2xl mb-2"></i>
                                        <span>Apartment</span>
                                    </label>
                                </div>
                                <div>
                                    <input type="radio" id="studio" name="room_type" value="studio" class="hidden peer">
                                    <label for="studio" class="flex flex-col items-center p-4 border border-gray-300 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-red-50">
                                        <i class="ri-home-3-line text-2xl mb-2"></i>
                                        <span>Studio</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Location Information -->
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-gray-800 mb-4">Location Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="university_id" class="block text-sm font-medium text-gray-700 mb-1">Nearby University*</label>
                                    <select id="university_id" name="university_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                                        <option value="">Select a university</option>
                                        <?php foreach ($universities as $uni): ?>
                                            <option value="<?php echo $uni['id']; ?>"><?php echo htmlspecialchars($uni['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="available_from" class="block text-sm font-medium text-gray-700 mb-1">Available From*</label>
                                    <input type="date" id="available_from" name="available_from" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                                </div>
                                <div class="md:col-span-2">
                                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Full Address*</label>
                                    <input type="text" id="address" name="address" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                                </div>
                                <div class="md:col-span-2 location-fields">
                                    <!-- Hidden fields for coordinates -->
                                    <input type="hidden" name="latitude" id="latitude">
                                    <input type="hidden" name="longitude" id="longitude">
                                    
                                    <div class="flex items-center space-x-4 mb-2">
                                        <button type="button" id="getLocationBtn" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition flex items-center">
                                            <i class="ri-map-pin-line mr-2"></i> Use Current Location for Coordinates
                                        </button>
                                        <small class="text-gray-500">or drag the marker on the map</small>
                                    </div>
                                    <div id="mapPreview"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Room Features -->
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-gray-800 mb-4">Room Features</h2>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                <div>
                                    <input type="checkbox" id="wifi" name="features[]" value="wifi" class="hidden peer">
                                    <label for="wifi" class="flex flex-col items-center p-4 border border-gray-300 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-red-50">
                                        <i class="ri-wifi-line text-2xl mb-2"></i>
                                        <span class="text-sm">WiFi</span>
                                    </label>
                                </div>
                                <div>
                                    <input type="checkbox" id="air_conditioning" name="features[]" value="air_conditioning" class="hidden peer">
                                    <label for="air_conditioning" class="flex flex-col items-center p-4 border border-gray-300 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-red-50">
                                        <i class="ri-snowy-line text-2xl mb-2"></i>
                                        <span class="text-sm">Air Conditioning</span>
                                    </label>
                                </div>
                                <div>
                                    <input type="checkbox" id="private_bathroom" name="features[]" value="private_bathroom" class="hidden peer">
                                    <label for="private_bathroom" class="flex flex-col items-center p-4 border border-gray-300 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-red-50">
                                        <i class="ri-shower-line text-2xl mb-2"></i>
                                        <span class="text-sm">Private Bathroom</span>
                                    </label>
                                </div>
                                <div>
                                    <input type="checkbox" id="shared_kitchen" name="features[]" value="shared_kitchen" class="hidden peer">
                                    <label for="shared_kitchen" class="flex flex-col items-center p-4 border border-gray-300 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-red-50">
                                        <i class="ri-fridge-line text-2xl mb-2"></i>
                                        <span class="text-sm">Shared Kitchen</span>
                                    </label>
                                </div>
                                <div>
                                    <input type="checkbox" id="parking" name="features[]" value="parking" class="hidden peer">
                                    <label for="parking" class="flex flex-col items-center p-4 border border-gray-300 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-red-50">
                                        <i class="ri-parking-line text-2xl mb-2"></i>
                                        <span class="text-sm">Parking</span>
                                    </label>
                                </div>
                                <div>
                                    <input type="checkbox" id="security" name="features[]" value="security" class="hidden peer">
                                    <label for="security" class="flex flex-col items-center p-4 border border-gray-300 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-red-50">
                                        <i class="ri-shield-check-line text-2xl mb-2"></i>
                                        <span class="text-sm">Security</span>
                                    </label>
                                </div>
                                <div>
                                    <input type="checkbox" id="furnished" name="features[]" value="furnished" class="hidden peer">
                                    <label for="furnished" class="flex flex-col items-center p-4 border border-gray-300 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-red-50">
                                        <i class="ri-sofa-line text-2xl mb-2"></i>
                                        <span class="text-sm">Furnished</span>
                                    </label>
                                </div>
                                <div>
                                    <input type="checkbox" id="study_desk" name="features[]" value="study_desk" class="hidden peer">
                                    <label for="study_desk" class="flex flex-col items-center p-4 border border-gray-300 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-red-50">
                                        <i class="ri-desk-line text-2xl mb-2"></i>
                                        <span class="text-sm">Study Desk</span>
                                    </label>
                                </div>
                                <div>
                                    <input type="checkbox" id="wardrobe" name="features[]" value="wardrobe" class="hidden peer">
                                    <label for="wardrobe" class="flex flex-col items-center p-4 border border-gray-300 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-red-50">
                                        <i class="ri-t-shirt-line text-2xl mb-2"></i>
                                        <span class="text-sm">Wardrobe</span>
                                    </label>
                                </div>
                                <div>
                                    <input type="checkbox" id="balcony" name="features[]" value="balcony" class="hidden peer">
                                    <label for="balcony" class="flex flex-col items-center p-4 border border-gray-300 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-red-50">
                                        <i class="ri-home-4-line text-2xl mb-2"></i>
                                        <span class="text-sm">Balcony</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <!-- Gender Preference -->
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-gray-800 mb-4">Gender Preference</h2>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <input type="radio" id="male" name="gender_preference" value="male" class="hidden peer">
                                    <label for="male" class="flex flex-col items-center p-4 border border-gray-300 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-red-50">
                                        <i class="ri-men-line text-2xl mb-2"></i>
                                        <span>Male Only</span>
                                    </label>
                                </div>
                                <div>
                                    <input type="radio" id="female" name="gender_preference" value="female" class="hidden peer">
                                    <label for="female" class="flex flex-col items-center p-4 border border-gray-300 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-red-50">
                                        <i class="ri-women-line text-2xl mb-2"></i>
                                        <span>Female Only</span>
                                    </label>
                                </div>
                                <div>
                                    <input type="radio" id="any" name="gender_preference" value="any" class="hidden peer" checked>
                                    <label for="any" class="flex flex-col items-center p-4 border border-gray-300 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-red-50">
                                        <i class="ri-team-line text-2xl mb-2"></i>
                                        <span>Any Gender</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Room Images -->
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-gray-800 mb-4">Room Images</h2>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                                <input type="file" name="images[]" id="images" multiple accept="image/*" class="hidden">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="ri-image-add-line text-4xl text-gray-400 mb-2"></i>
                                    <p class="text-gray-500 mb-4">Upload up to 10 images of your room (first image will be primary)</p>
                                    <button type="button" onclick="document.getElementById('images').click()" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-red-700 transition">
                                        Select Images
                                    </button>
                                </div>
                                <div id="imagePreview" class="mt-4 flex flex-wrap"></div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-red-700 transition font-medium">
                                Post Room Listing
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize map variables
        let map, marker;
        let mapInitialized = false;
        let geocoder;
        
        // Set default available from date to today
        document.getElementById('available_from').valueAsDate = new Date();
        
        // Get current location button
        document.getElementById('getLocationBtn').addEventListener('click', function() {
            if (navigator.geolocation) {
                const btn = this;
                btn.innerHTML = '<i class="ri-loader-4-line animate-spin mr-2"></i> Getting location...';
                btn.disabled = true;
                
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        // Update hidden fields
                        document.getElementById('latitude').value = lat;
                        document.getElementById('longitude').value = lng;
                        
                        // Reverse geocode to verify
                        geocodeLatLng(lat, lng, function(address) {
                            if (address) {
                                document.getElementById('address').value = address;
                            }
                            
                            // Initialize or update map
                            if (!mapInitialized) {
                                initMap(lat, lng);
                                mapInitialized = true;
                            } else {
                                const newLocation = new google.maps.LatLng(lat, lng);
                                map.setCenter(newLocation);
                                marker.setPosition(newLocation);
                            }
                            
                            // Show the map
                            document.getElementById('mapPreview').style.display = 'block';
                            
                            // Reset button
                            btn.innerHTML = '<i class="ri-map-pin-line mr-2"></i> Use Current Location for Coordinates';
                            btn.disabled = false;
                        });
                    },
                    // Error handling remains the same
                );
            } else {
                alert('Geolocation is not supported by your browser.');
            }
        });

        // Add reverse geocoding function
        function geocodeLatLng(lat, lng, callback) {
            if (!geocoder) {
                geocoder = new google.maps.Geocoder();
            }
            
            const latlng = { lat: parseFloat(lat), lng: parseFloat(lng) };
            
            geocoder.geocode({ location: latlng }, function(results, status) {
                if (status === "OK") {
                    if (results[0]) {
                        callback(results[0].formatted_address);
                    } else {
                        callback(null);
                    }
                } else {
                    console.error("Geocoder failed due to: " + status);
                    callback(null);
                }
            });
        }
        // Initialize Google Map for form
        function initMap(lat, lng) {
            const mapOptions = {
                center: { lat: lat, lng: lng },
                zoom: 15,
                mapTypeId: 'roadmap',
                streetViewControl: false,
                mapTypeControl: false
            };
            
            map = new google.maps.Map(document.getElementById('mapPreview'), mapOptions);
            
            marker = new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: map,
                draggable: true,
                title: "Drag to adjust location"
            });
            
            geocoder = new google.maps.Geocoder();
            
            // Listen for marker drag events
            google.maps.event.addListener(marker, 'dragend', function(e) {
                const newPos = marker.getPosition();
                document.getElementById('latitude').value = newPos.lat();
                document.getElementById('longitude').value = newPos.lng();
            });
        }

        // Image preview functionality
        document.getElementById('images').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (this.files && this.files.length > 0) {
                // Limit to 10 images
                const files = Array.from(this.files).slice(0, 10);
                
                files.forEach((file, i) => {
                    if (!file.type.match('image.*')) return;
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'preview-image';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Preview ' + (i+1);
                        img.loading = 'lazy';
                        
                        const removeBtn = document.createElement('span');
                        removeBtn.className = 'remove-image';
                        removeBtn.innerHTML = '&times;';
                        removeBtn.addEventListener('click', function() {
                            previewItem.remove();
                            // You would need to update the files array here
                            // This requires more complex handling as FileList is read-only
                        });
                        
                        previewItem.appendChild(img);
                        previewItem.appendChild(removeBtn);
                        preview.appendChild(previewItem);
                    };
                    reader.readAsDataURL(file);
                });
            }
        });

        // Form validation
        document.getElementById('roomForm').addEventListener('submit', function(e) {
            const address = document.getElementById('address').value;
            const latitude = document.getElementById('latitude').value;
            const longitude = document.getElementById('longitude').value;
            
            if (!address) {
                e.preventDefault();
                alert('Please provide the full address for your room.');
                return false;
            }
            
            if (!latitude || !longitude) {
                e.preventDefault();
                alert('Please provide location coordinates by clicking "Use Current Location for Coordinates".');
                return false;
            }
            
            return true;
        });
    });
    </script>
</body>
</html>