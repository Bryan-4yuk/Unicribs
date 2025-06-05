<?php
require_once '../core/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name' => trim($_POST['full_name']),
        'email' => trim($_POST['email']),
        'password' => $_POST['password'],
        'user_type' => $_POST['user_type'],
        'cni' => $_POST['user_type'] === 'landlord' ? trim($_POST['cni']) : null
    ];
    
    $user = new User($pdo);
    
    // Validate data
    if (empty($data['full_name']) || empty($data['email']) || empty($data['password'])) {
        $_SESSION['error'] = 'All fields are required';
        header('Location: ../index.html');
        exit();
    }
    
    if ($data['user_type'] === 'landlord' && empty($data['cni'])) {
        $_SESSION['error'] = 'CNI number is required for landlords';
        header('Location: ../index.html');
        exit();
    }
    
    if ($user->emailExists($data['email'])) {
        $_SESSION['error'] = 'Email already exists';
        header('Location: ../index.html');
        exit();
    }
    
    // Create user
    if ($user->register($data)) {
        // Redirect based on user type
        if ($_SESSION['user_type'] === 'student') {
            header('Location: ../home.php');
        } else {
            header('Location: ../home_l.php');
        }
        exit();
    } else {
        $_SESSION['error'] = 'Registration failed. Please try again.';
        header('Location: ../index.html');
        exit();
    }
} else {
    header('Location: ../index.html');
    exit();
}
?>