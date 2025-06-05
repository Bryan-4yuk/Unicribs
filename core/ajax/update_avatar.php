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

// Verify user can only update their own avatar
if ($userId !== $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Not authorized to update this avatar']);
    exit();
}

// Check if file was uploaded
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit();
}

$file = $_FILES['avatar'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, and GIF images are allowed']);
    exit();
}

// Create uploads directory if it doesn't exist
$uploadDir = '../../uploads/avatars/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
$filePath = $uploadDir . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $filePath)) {
    try {
        $pdo->beginTransaction();
        
        // Delete old avatar if exists
        $user = new User($pdo);
        $currentUser = $user->getUser($userId);
        
        if (!empty($currentUser['profile_picture'])) {
            $oldFilePath = $uploadDir . $currentUser['profile_picture'];
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }
        
        // Update database
        $success = $user->updateUser($userId, [
            'profile_picture' => $filename,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($success) {
            $pdo->commit();
            echo json_encode(['success' => true]);
        } else {
            $pdo->rollBack();
            unlink($filePath); // Clean up the uploaded file
            echo json_encode(['success' => false, 'message' => 'Failed to update avatar']);
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        unlink($filePath); // Clean up the uploaded file
        error_log("Avatar update error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
}