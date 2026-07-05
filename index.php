<?php
session_start();
require_once "config.php";
require_once "includes/db.php";
require_once "includes/functions.php";

$page = $_GET["page"] ?? "home";
$allowed_pages = [
    "home",
    "login",
    "register",
    "dashboard",
    "lessons",
    "lesson",
    "projects",
    "leaderboard",
    "profile",
    "donate",
    "logout",
    "battle-royale",
    "battle-royale-lobby",
    "mini-games",
    "mini-game-play",
    "ai-tutor",
    "ai-tutor-chat",
    "daily-challenge",
];

if (!in_array($page, $allowed_pages)) {
    $page = "home";
}

// Handle logout
if ($page === "logout") {
    if (isset($_SESSION["user_id"])) {
        $stmt = $pdo->prepare(
            "UPDATE users SET is_online = FALSE WHERE id = ?",
        );
        $stmt->execute([$_SESSION["user_id"]]);
    }
    session_destroy();
    header("Location: index.php");
    exit();
}

// Handle form processing
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    switch ($page) {
        case "login":
            if (isset($_POST["username"]) && isset($_POST["password"])) {
                $username = sanitize($_POST["username"]);
                $password = $_POST["password"];

                if (!empty($username) && !empty($password)) {
                    $stmt = $pdo->prepare(
                        "SELECT * FROM users WHERE username = ? OR email = ?",
                    );
                    $stmt->execute([$username, $username]);
                    $user = $stmt->fetch();

                    if (
                        $user &&
                        password_verify($password, $user["password"])
                    ) {
                        $_SESSION["user_id"] = $user["id"];
                        $_SESSION["username"] = $user["username"];

                        // Update online status
                        $stmt = $pdo->prepare(
                            "UPDATE users SET is_online = TRUE WHERE id = ?",
                        );
                        $stmt->execute([$user["id"]]);

                        $redirect = $_GET["redirect"] ?? "dashboard";
                        header("Location: index.php?page={$redirect}");
                        exit();
                    }
                }
            }
            break;

        case "register":
            if (
                isset($_POST["username"]) &&
                isset($_POST["email"]) &&
                isset($_POST["password"]) &&
                isset($_POST["confirm_password"])
            ) {
                $username = sanitize($_POST["username"]);
                $email = sanitize($_POST["email"]);
                $password = $_POST["password"];
                $confirm_password = $_POST["confirm_password"];
                $preferred_lang = (int) ($_POST["preferred_language"] ?? 1);

                $errors = [];

                if (strlen($username) < 3) {
                    $errors[] = "Username must be at least 3 characters long";
                }
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                    $errors[] =
                        "Username can only contain letters, numbers, and underscores";
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

                if (empty($errors)) {
                    $stmt = $pdo->prepare(
                        "SELECT id FROM users WHERE username = ? OR email = ?",
                    );
                    $stmt->execute([$username, $email]);
                    if ($stmt->fetch()) {
                        $errors[] = "Username or email already exists";
                    }
                }

                if (empty($errors)) {
                    $hashed_password = password_hash($password, HASH_ALGO);
                    $stmt = $pdo->prepare(
                        "INSERT INTO users (username, email, password, preferred_language, created_at) VALUES (?, ?, ?, ?, NOW())",
                    );

                    if (
                        $stmt->execute([
                            $username,
                            $email,
                            $hashed_password,
                            $preferred_lang,
                        ])
                    ) {
                        $user_id = $pdo->lastInsertId();
                        $_SESSION["user_id"] = $user_id;
                        $_SESSION["username"] = $username;

                        // Create initial progress entries for all lessons
                        $stmt = $pdo->prepare(
                            "INSERT INTO user_progress (user_id, lesson_id, completed) SELECT ?, id, 0 FROM lessons",
                        );
                        $stmt->execute([$user_id]);

                        // Set online
                        $stmt = $pdo->prepare(
                            "UPDATE users SET is_online = TRUE WHERE id = ?",
                        );
                        $stmt->execute([$user_id]);

                        header("Location: index.php?page=dashboard");
                        exit();
                    }
                }
            }
            break;

        case "ai-tutor":
            if (isset($_POST["message"]) && isset($_SESSION["user_id"])) {
                $message = $_POST["message"];
                $language = $_POST["language"] ?? "rust";
                $chat_id = (int) ($_POST["chat_id"] ?? 0);

                if ($chat_id === 0) {
                    $chat_id = create_ai_chat($_SESSION["user_id"], $language);
                }

                $result = ask_ai_tutor(
                    $chat_id,
                    $_SESSION["user_id"],
                    $message,
                );
                header("Content-Type: application/json");
                echo json_encode($result);
                exit();
            }
            break;
    }
}

// Check if user is logged in for protected pages
$protected_pages = [
    "dashboard",
    "lessons",
    "lesson",
    "leaderboard",
    "profile",
    "battle-royale",
    "battle-royale-lobby",
    "mini-games",
    "mini-game-play",
    "ai-tutor",
    "ai-tutor-chat",
    "daily-challenge",
];
if (in_array($page, $protected_pages) && !isset($_SESSION["user_id"])) {
    header("Location: index.php?page=login");
    exit();
}

// Redirect logged-in users away from auth pages (must be before header output)
if (
    ($page === "login" || $page === "register") &&
    isset($_SESSION["user_id"])
) {
    header("Location: index.php?page=dashboard");
    exit();
}

// Home page redirect logic
if ($page === "home") {
    if (isset($_SESSION["user_id"])) {
        header("Location: index.php?page=dashboard");
        exit();
    } else {
        header("Location: index.php?page=login");
        exit();
    }
}

// Now include the header and page content
include "includes/header.php";

if (file_exists("pages/{$page}.php")) {
    include "pages/{$page}.php";
} else {
    // Fallback to login
    include "pages/login.php";
}

include "includes/footer.php";
