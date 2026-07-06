<?php
$page_title = "Sign In";

// Handle login form submission
$error = "";
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["username"]) &&
    isset($_POST["password"])
) {
    $username = sanitize($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        $stmt = $pdo->prepare(
            "SELECT * FROM users WHERE username = ? OR email = ?",
        );
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password"])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            update_user_activity($user["id"]);
            header("Location: index.php?page=dashboard");
            exit();
        } else {
            $error = "Invalid username or password";
        }
    }
}

// Handle GitHub OAuth callback
if (isset($_GET["github_callback"]) && isset($_GET["code"])) {
    $code = $_GET["code"];

    // Exchange code for access token
    $token_url = "https://github.com/login/oauth/access_token";
    $post_data = [
        "client_id" => GITHUB_CLIENT_ID,
        "client_secret" => GITHUB_CLIENT_SECRET,
        "code" => $code,
        "redirect_uri" => GITHUB_REDIRECT_URI,
    ];

    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200 && $response) {
        $token_data = json_decode($response, true);
        $access_token = $token_data["access_token"] ?? "";

        if (!empty($access_token)) {
            // Get GitHub user info
            $ch = curl_init("https://api.github.com/user");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $access_token,
                "User-Agent: Rustnite",
                "Accept: application/json",
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $user_response = curl_exec($ch);
            $user_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($user_http === 200 && $user_response) {
                $github_user = json_decode($user_response, true);
                $github_id = $github_user["id"] ?? "";
                $github_username = $github_user["login"] ?? "";
                $github_avatar = $github_user["avatar_url"] ?? "";
                $github_email = $github_user["email"] ?? "";

                if (!empty($github_id)) {
                    // Check if user exists by github_id
                    $stmt = $pdo->prepare(
                        "SELECT * FROM users WHERE github_id = ?",
                    );
                    $stmt->execute([(string) $github_id]);
                    $existing = $stmt->fetch();

                    if ($existing) {
                        $_SESSION["user_id"] = $existing["id"];
                        $_SESSION["username"] = $existing["username"];
                        update_user_activity($existing["id"]);
                        header("Location: index.php?page=dashboard");
                        exit();
                    }

                    // Check if user exists by email
                    if (!empty($github_email)) {
                        $stmt = $pdo->prepare(
                            "SELECT * FROM users WHERE email = ?",
                        );
                        $stmt->execute([$github_email]);
                        $email_user = $stmt->fetch();
                        if ($email_user) {
                            $stmt = $pdo->prepare(
                                "UPDATE users SET github_id = ?, avatar_url = ? WHERE id = ?",
                            );
                            $stmt->execute([
                                (string) $github_id,
                                $github_avatar,
                                $email_user["id"],
                            ]);
                            $_SESSION["user_id"] = $email_user["id"];
                            $_SESSION["username"] = $email_user["username"];
                            update_user_activity($email_user["id"]);
                            header("Location: index.php?page=dashboard");
                            exit();
                        }
                    }

                    // Create new user
                    $username_base = preg_replace(
                        "/[^a-zA-Z0-9_]/",
                        "",
                        $github_username,
                    );
                    $username = $username_base;
                    $suffix = 1;
                    while (true) {
                        $stmt = $pdo->prepare(
                            "SELECT id FROM users WHERE username = ?",
                        );
                        $stmt->execute([$username]);
                        if (!$stmt->fetch()) {
                            break;
                        }
                        $username = $username_base . $suffix;
                        $suffix++;
                    }

                    $email = $github_email ?: $github_id . "@github.user";
                    $stmt = $pdo->prepare(
                        "INSERT INTO users (username, email, password, github_id, avatar_url, xp, level) VALUES (?, ?, ?, ?, ?, 0, 1)",
                    );
                    $stmt->execute([
                        $username,
                        $email,
                        "",
                        (string) $github_id,
                        $github_avatar,
                    ]);

                    $_SESSION["user_id"] = $pdo->lastInsertId();
                    $_SESSION["username"] = $username;
                    update_user_activity($_SESSION["user_id"]);
                    header("Location: index.php?page=dashboard");
                    exit();
                }
            }
        }
    }

    $_SESSION["github_error"] = "GitHub login failed. Please try again.";
    header("Location: index.php?page=login");
    exit();
}

session_write_close();

$github_error = $_SESSION["github_error"] ?? "";
unset($_SESSION["github_error"]);
$display_error = $error ?: $github_error;
?>
<div style="display:flex; align-items:center; justify-content:center; min-height:80vh; animation: fade-in 0.5s ease-out;">
    <div style="width:100%; max-width:420px; margin:0 auto;">
        <div class="tw-card">
            <div class="tw-card-body">
                <div style="text-align:center; margin-bottom:32px;">
                    <img src="assets/logo.png" alt="Rustnite" style="height: 30px; margin:0 auto 16px; display:block;">
                    <h1 class="text-2xl font-bold gradient-text">Welcome Back</h1>
                    <p class="text-sm text-twitch-muted mt-1">Sign in to continue your coding journey</p>
                </div>

                <?php if ($display_error): ?>
                    <div style="background:rgba(233,25,123,0.1); border:1px solid rgba(233,25,123,0.2); border-radius:8px; padding:12px; margin-bottom:20px;">
                        <span style="color:#E9197B; font-size:13px;"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars(
                            $display_error,
                        ) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-twitch-muted mb-1">Username or Email</label>
                            <input type="text" name="username" required value="<?= htmlspecialchars(
                                $_POST["username"] ?? "",
                            ) ?>" class="w-full p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text focus:outline-none focus:border-twitch-purple" placeholder="Enter your username or email">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-twitch-muted mb-1">Password</label>
                            <input type="password" name="password" required class="w-full p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text focus:outline-none focus:border-twitch-purple" placeholder="Enter your password">
                        </div>
                        <button type="submit" class="tw-btn tw-btn-primary tw-btn-block tw-btn-lg">
                            <i class="fas fa-sign-in-alt"></i>
                            Sign In
                        </button>
                    </div>
                </form>

                <div style="text-align:center; margin-top:24px; padding-top:20px; border-top:1px solid #2D2D35;">
                    <p class="text-sm text-twitch-muted">
                        Don't have an account?
                        <a href="index.php?page=register" class="font-medium" style="color:#9147FF;">Create one</a>
                    </p>

                    <div style="margin-top:16px; border-top:1px solid #2D2D35; padding-top:16px;">
                        <a href="https://github.com/login/oauth/authorize?client_id=<?= GITHUB_CLIENT_ID ?>&redirect_uri=<?= urlencode(
    GITHUB_REDIRECT_URI,
) ?>&scope=user:email"
                           class="tw-btn tw-btn-block"
                           style="background:#24292e; color:white; justify-content:center; border:1px solid #3A3A45;">
                            <i class="fab fa-github"></i>
                            Sign in with GitHub
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
