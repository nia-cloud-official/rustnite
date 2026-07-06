<?php
$page_title = "Sign In";

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
        $error = "Invalid username or password";
    }
}
?>
<div style="display:flex; align-items:center; justify-content:center; min-height:80vh; animation: fade-in 0.5s ease-out;">
    <div style="width:100%; max-width:420px; margin:0 auto;">
        <div class="tw-card">
            <div class="tw-card-body">
                <div style="text-align:center; margin-bottom:32px;">
                    <img src="assets/logo.png" alt="Rustnite" style="height: 48px; margin:0 auto 16px; display:block;">
                    <h1 class="text-2xl font-bold gradient-text">Welcome Back</h1>
                    <p class="text-sm text-twitch-muted mt-1">Sign in to continue your coding journey</p>
                </div>

                <?php if ($error): ?>
                    <div style="background:rgba(233,25,123,0.1); border:1px solid rgba(233,25,123,0.2); border-radius:8px; padding:12px; margin-bottom:20px;">
                        <span style="color:#E9197B; font-size:13px;"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars(
                            $error,
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

                    <?php if (
                        defined("GITHUB_CLIENT_ID") &&
                        !empty(GITHUB_CLIENT_ID)
                    ): ?>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
