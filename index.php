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
    "mini-games",
    "mini-game-play",
    "ai-tutor",
    "daily-challenge",
    "feed",
    "feed-post",
    "notifications",
];

if (!in_array($page, $allowed_pages)) {
    header("Location: index.php?page=dashboard");
    exit();
}

// Handle logout
if ($page === "logout") {
    if (isset($_SESSION["user_id"])) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_online'");
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare(
                    "UPDATE users SET is_online = FALSE WHERE id = ?",
                );
                $stmt->execute([$_SESSION["user_id"]]);
            }
        } catch (PDOException $e) {
        }
    }
    session_destroy();
    header("Location: index.php");
    exit();
}

// GitHub OAuth callback — must be BEFORE the POST check (GitHub redirects via GET)
if (isset($_GET["github_callback"]) && isset($_GET["code"])) {
    $code = $_GET["code"];
    try {
        if (
            empty($code) ||
            !defined("GITHUB_CLIENT_ID") ||
            empty(GITHUB_CLIENT_ID)
        ) {
            $_SESSION["github_error"] = "GitHub OAuth is not configured.";
        } else {
            $ch = curl_init("https://github.com/login/oauth/access_token");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                http_build_query([
                    "client_id" => GITHUB_CLIENT_ID,
                    "client_secret" => GITHUB_CLIENT_SECRET,
                    "code" => $code,
                ]),
            );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $token_data = json_decode(curl_exec($ch), true);
            curl_close($ch);
            $access_token = $token_data["access_token"] ?? "";

            if (empty($access_token)) {
                $_SESSION["github_error"] =
                    "Could not get access token from GitHub.";
            } else {
                $ch = curl_init("https://api.github.com/user");
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $access_token",
                    "User-Agent: Rustnite/1.0",
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $github_user = json_decode(curl_exec($ch), true);
                curl_close($ch);

                if (empty($github_user["id"])) {
                    $_SESSION["github_error"] = "GitHub returned no user data.";
                } else {
                    $github_id = $github_user["id"];
                    $github_email = $github_user["email"] ?? "";
                    $github_login = $github_user["login"] ?? "";
                    $github_avatar = $github_user["avatar_url"] ?? "";

                    $stmt = $pdo->prepare(
                        "SELECT * FROM users WHERE github_id = ? OR email = ?",
                    );
                    $stmt->execute([$github_id, $github_email]);
                    $user = $stmt->fetch();

                    if ($user) {
                        if (empty($user["github_id"])) {
                            $stmt = $pdo->prepare(
                                "UPDATE users SET github_id = ?, avatar_url = ? WHERE id = ?",
                            );
                            $stmt->execute([
                                $github_id,
                                $github_avatar,
                                $user["id"],
                            ]);
                        }
                        $_SESSION["user_id"] = (int) $user["id"];
                        $_SESSION["username"] = $user["username"];
                    } else {
                        $username = preg_replace(
                            "/[^a-zA-Z0-9_]/",
                            "_",
                            $github_login,
                        );
                        $stmt = $pdo->prepare(
                            "SELECT id FROM users WHERE username = ?",
                        );
                        $stmt->execute([$username]);
                        if ($stmt->fetch()) {
                            $username .= "_" . substr($github_id, 0, 4);
                        }
                        $random_pass = password_hash(
                            bin2hex(random_bytes(16)),
                            PASSWORD_DEFAULT,
                        );
                        $stmt = $pdo->prepare(
                            "INSERT INTO users (username, email, github_id, avatar_url, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
                        );
                        $stmt->execute([
                            $username,
                            $github_email,
                            $github_id,
                            $github_avatar,
                            $random_pass,
                        ]);
                        $user_id = (int) $pdo->lastInsertId();
                        $_SESSION["user_id"] = $user_id;
                        $_SESSION["username"] = $username;
                    }

                    session_regenerate_id(true);
                    session_write_close();
                    header("Location: index.php?page=dashboard");
                    exit();
                }
            }
        }
    } catch (Exception $e) {
        $_SESSION["github_error"] = "GitHub login error: " . $e->getMessage();
    }
}

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
                        try {
                            $stmt = $pdo->prepare(
                                "UPDATE users SET is_online = TRUE WHERE id = ?",
                            );
                            $stmt->execute([$user["id"]]);
                        } catch (PDOException $e) {
                            // Column may not exist yet
                        }

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

        case "feed":
            // Handle add comment (POST before header)
            if (isset($_POST["add_comment"]) && isset($_SESSION["user_id"])) {
                $post_id = (int) $_POST["post_id"];
                $comment = sanitize($_POST["comment_content"] ?? "");
                if (!empty($comment)) {
                    try {
                        $pdo->prepare(
                            "INSERT INTO feed_comments (post_id, user_id, content) VALUES (?, ?, ?)",
                        )->execute([$post_id, $_SESSION["user_id"], $comment]);
                        $pdo->prepare(
                            "UPDATE feed_posts SET comments_count = comments_count + 1 WHERE id = ?",
                        )->execute([$post_id]);
                    } catch (PDOException $e) {
                    }
                }
                header("Location: index.php?page=feed&view=" . $post_id);
                exit();
            }
            break;

        case "ai-tutor":
            // New chat
            if (isset($_POST["new_chat"]) && isset($_SESSION["user_id"])) {
                $lang = $_POST["language"] ?? "rust";
                $chat_id = create_ai_chat($_SESSION["user_id"], $lang);
                header("Location: index.php?page=ai-tutor&chat_id={$chat_id}");
                exit();
            }
            // Delete chat
            if (isset($_GET["delete_chat"]) && isset($_SESSION["user_id"])) {
                $delete_id = (int) $_GET["delete_chat"];
                $stmt = $pdo->prepare(
                    "DELETE FROM ai_chats WHERE id = ? AND user_id = ?",
                );
                $stmt->execute([$delete_id, $_SESSION["user_id"]]);
                header("Location: index.php?page=ai-tutor");
                exit();
            }
            // Send message
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

// Handle feed GET actions (must be before header output)
if ($page === "feed" && isset($_SESSION["user_id"])) {
    // Handle like
    if (isset($_GET["like"])) {
        $post_id = (int) $_GET["like"];
        try {
            $stmt = $pdo->prepare(
                "SELECT id FROM feed_likes WHERE post_id = ? AND user_id = ?",
            );
            $stmt->execute([$post_id, $_SESSION["user_id"]]);
            if (!$stmt->fetch()) {
                $pdo->prepare(
                    "INSERT INTO feed_likes (post_id, user_id) VALUES (?, ?)",
                )->execute([$post_id, $_SESSION["user_id"]]);
                $pdo->prepare(
                    "UPDATE feed_posts SET likes_count = likes_count + 1 WHERE id = ?",
                )->execute([$post_id]);
            }
        } catch (PDOException $e) {
        }
        header("Location: index.php?page=feed");
        exit();
    }
    // Handle unlike
    if (isset($_GET["unlike"])) {
        $post_id = (int) $_GET["unlike"];
        try {
            $pdo->prepare(
                "DELETE FROM feed_likes WHERE post_id = ? AND user_id = ?",
            )->execute([$post_id, $_SESSION["user_id"]]);
            $pdo->prepare(
                "UPDATE feed_posts SET likes_count = GREATEST(0, likes_count - 1) WHERE id = ?",
            )->execute([$post_id]);
        } catch (PDOException $e) {
        }
        header("Location: index.php?page=feed");
        exit();
    }
    // Handle delete post
    if (isset($_GET["delete"]) && isset($_GET["confirm"])) {
        $post_id = (int) $_GET["delete"];
        try {
            $pdo->prepare(
                "DELETE FROM feed_posts WHERE id = ? AND user_id = ?",
            )->execute([$post_id, $_SESSION["user_id"]]);
        } catch (PDOException $e) {
        }
        header("Location: index.php?page=feed");
        exit();
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
    "mini-games",
    "mini-game-play",
    "ai-tutor",
    "daily-challenge",
    "feed",
];
if (in_array($page, $protected_pages) && !isset($_SESSION["user_id"])) {
    header("Location: index.php?page=login");
    exit();
}

// Redirect logged-in users away from auth pages (must be before header output)
if (
    ($page === "login" || $page === "register") &&
    isset($_SESSION["user_id"]) &&
    !isset($_GET["github_callback"])
) {
    header("Location: index.php?page=dashboard");
    exit();
}

// Handle lesson page redirects before header output
if ($page === "lesson") {
    $lesson_id = (int) ($_GET["id"] ?? 0);
    // Handle AI generate lesson
    if (isset($_GET["generate"]) && $lesson_id === 0) {
        $lang_id = (int) ($_GET["language"] ?? 1);
        $diff = $_GET["difficulty"] ?? "beginner";
        $result = generate_ai_lesson($lang_id, $diff);
        if (!isset($result["error"])) {
            header(
                "Location: index.php?page=lesson&id=" . $result["lesson_id"],
            );
            exit();
        }
        // If generation failed, redirect back with message
        $_SESSION["flash_message"] =
            $result["error"] ?? "Failed to generate lesson";
        header("Location: index.php?page=lessons&language=" . $lang_id);
        exit();
    }
    // Check lesson exists
    if ($lesson_id > 0) {
        $test_lesson = get_lesson_by_id($lesson_id);
        if (!$test_lesson) {
            header("Location: index.php?page=lessons");
            exit();
        }
    }
}

// Hide sidebar/topbar for home page (landing)
$hide_chrome = $page === "home";
if ($page === "home" && isset($_SESSION["user_id"])) {
    // Logged-in users see the dashboard when at 'home'
    $page = "dashboard";
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
