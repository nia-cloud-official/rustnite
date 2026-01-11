<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page = $_GET['page'] ?? 'home';
$allowed_pages = ['home', 'login', 'register', 'dashboard', 'lessons', 'lesson', 'projects', 'leaderboard', 'profile', 'donate', 'logout'];

if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}

// Handle logout
if ($page === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Handle form processing for pages that need it BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($page) {
        case 'login':
            if (isset($_POST['username']) && isset($_POST['password'])) {
                $username = sanitize($_POST['username']);
                $password = $_POST['password'];
                
                if (!empty($username) && !empty($password)) {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
                    $stmt->execute([$username, $username]);
                    $user = $stmt->fetch();
                    
                    if ($user && password_verify($password, $user['password'])) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        
                        $redirect = $_GET['redirect'] ?? 'dashboard';
                        header("Location: index.php?page={$redirect}");
                        exit;
                    }
                }
            }
            break;
            
        case 'register':
            if (isset($_POST['username']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['confirm_password'])) {
                $username = sanitize($_POST['username']);
                $email = sanitize($_POST['email']);
                $password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'];
                
                $errors = [];
                
                // Validation
                if (strlen($username) < 3) {
                    $errors[] = "Username must be at least 3 characters long";
                }
                
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                    $errors[] = "Username can only contain letters, numbers, and underscores";
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Please enter a valid email address";
                }
                
                if (strlen($password) < 6) {
                    $errors[] = "Password must be at least 6 characters long";
                }
                
                if ($password !== $confirm_password) {
                    $errors[] = "Passwords don't match";
                }
                
                // Check if username or email already exists
                if (empty($errors)) {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                    $stmt->execute([$username, $email]);
                    if ($stmt->fetch()) {
                        $errors[] = "Username or email already exists";
                    }
                }
                
                // Create user if no errors
                if (empty($errors)) {
                    $hashed_password = password_hash($password, HASH_ALGO);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
                    
                    if ($stmt->execute([$username, $email, $hashed_password])) {
                        $user_id = $pdo->lastInsertId();
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $username;
                        
                        // Create initial progress entries for all lessons
                        $stmt = $pdo->prepare("
                            INSERT INTO user_progress (user_id, lesson_id, completed) 
                            SELECT ?, id, 0 FROM lessons
                        ");
                        $stmt->execute([$user_id]);
                        
                        header('Location: index.php?page=dashboard');
                        exit;
                    }
                }
            }
            break;
    }
}

// Check if user is logged in for protected pages
$protected_pages = ['dashboard', 'lessons', 'lesson', 'leaderboard', 'profile'];
if (in_array($page, $protected_pages) && !isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

// Now include the header and page content
include 'includes/header.php';
include "pages/{$page}.php";
include 'includes/footer.php';
?>