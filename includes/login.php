<?php
require_once '../core/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    $user = new User($pdo);
    
    if ($user->login($email, $password)) {
        // Redirect based on user type
        if ($_SESSION['user_type'] === 'student') {
            header('Location: ../home.php');
        } else {
            header('Location: ../home_l.php');
        }
        exit();
    } else {
        // Login failed
        $_SESSION['error'] = 'Invalid email or password';
        header('Location: ../index.html');
        exit();
    }
} else {
    header('Location: ../index.html');
    exit();
}
?>