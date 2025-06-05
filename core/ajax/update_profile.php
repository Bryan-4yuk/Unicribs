<?php
require_once '../init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$userId = (int)$_POST['user_id'];

// Verify user can only update their own profile
if ($userId !== $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Not authorized to update this profile']);
    exit();
}

// Validate and sanitize input
$fullName = trim($_POST['full_name'] ?? '');
$bio = trim($_POST['bio'] ?? '');
$phoneNumber = trim($_POST['phone_number'] ?? '');
$whatsappNumber = trim($_POST['whatsapp_number'] ?? '');
$cniNumber = ($_SESSION['user_type'] === 'landlord') ? trim($_POST['cni_number'] ?? '') : null;

// Basic validation
if (empty($fullName)) {
    echo json_encode(['success' => false, 'message' => 'Full name is required']);
    exit();
}

// Prepare update data
$updateData = [
    'full_name' => $fullName,
    'bio' => $bio,
    'phone_number' => $phoneNumber,
    'whatsapp_number' => $whatsappNumber,
    'updated_at' => date('Y-m-d H:i:s')
];

if ($_SESSION['user_type'] === 'landlord') {
    $updateData['cni_number'] = $cniNumber;
}

try {
    $pdo->beginTransaction();
    
    $user = new User($pdo);
    $success = $user->updateUser($userId, $updateData);
    
    if ($success) {
        $pdo->commit();
        echo json_encode(['success' => true]);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Profile update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}