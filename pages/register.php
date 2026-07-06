<?php
$page_title = "Create Account";

$errors = [];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = sanitize($_POST["username"] ?? "");
    $email = sanitize($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    if (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username: letters, numbers, underscores only";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email required";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be 6+ characters";
    }
    if ($password !== $confirm) {
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
}

$languages = get_languages();
?>
<div style="display:flex; align-items:center; justify-content:center; min-height:80vh; animation: fade-in 0.5s ease-out;">
    <div style="width:100%; max-width:420px; margin:0 auto;">
        <div class="tw-card">
            <div class="tw-card-body">
                <div style="text-align:center; margin-bottom:32px;">
                    <img src="assets/logo.png" alt="Rustnite" style="height: 48px; margin:0 auto 16px; display:block;">
                    <h1 class="text-2xl font-bold gradient-text">Join Rustnite</h1>
                    <p class="text-sm text-twitch-muted mt-1">Start your coding journey today</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div style="background:rgba(233,25,123,0.1); border:1px solid rgba(233,25,123,0.2); border-radius:8px; padding:12px; margin-bottom:20px;">
                        <?php foreach ($errors as $err): ?>
                            <div style="color:#E9197B; font-size:12px; margin-bottom:4px;"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars(
                                $err,
                            ) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-twitch-muted mb-1">Username</label>
                            <input type="text" name="username" required value="<?= htmlspecialchars(
                                $_POST["username"] ?? "",
                            ) ?>" class="w-full p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text focus:outline-none focus:border-twitch-purple" placeholder="Choose a unique username">
                            <div class="text-xs text-twitch-muted mt-1">3+ characters, letters, numbers, underscores</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-twitch-muted mb-1">Email</label>
                            <input type="email" name="email" required value="<?= htmlspecialchars(
                                $_POST["email"] ?? "",
                            ) ?>" class="w-full p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text focus:outline-none focus:border-twitch-purple" placeholder="your@email.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-twitch-muted mb-1">Password</label>
                            <input type="password" name="password" required class="w-full p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text focus:outline-none focus:border-twitch-purple" placeholder="Create a secure password">
                            <div class="text-xs text-twitch-muted mt-1">Minimum 6 characters</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-twitch-muted mb-1">Confirm Password</label>
                            <input type="password" name="confirm_password" required class="w-full p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text focus:outline-none focus:border-twitch-purple" placeholder="Confirm your password">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-twitch-muted mb-1">Interested Language</label>
                            <select name="preferred_language" class="w-full p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text">
                                <?= get_language_select_options(1) ?>
                            </select>
                        </div>
                        <button type="submit" class="tw-btn tw-btn-primary tw-btn-block tw-btn-lg">
                            <i class="fas fa-user-plus"></i>
                            Create Account
                        </button>
                    </div>
                </form>

                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-top:20px; padding-top:20px; border-top:1px solid #2D2D35;">
                    <div style="text-align:center; font-size:11px; color:#ADADB8;">
                        <i class="fas fa-graduation-cap" style="color:#9147FF; font-size:18px; margin-bottom:4px; display:block;"></i>
                        20+ Lessons
                    </div>
                    <div style="text-align:center; font-size:11px; color:#ADADB8;">
                        <i class="fas fa-trophy" style="color:#FFD700; font-size:18px; margin-bottom:4px; display:block;"></i>
                        XP & Badges
                    </div>
                    <div style="text-align:center; font-size:11px; color:#ADADB8;">
                        <i class="fas fa-robot" style="color:#00D95A; font-size:18px; margin-bottom:4px; display:block;"></i>
                        AI Tutor
                    </div>
                </div>

                <div style="text-align:center; margin-top:16px;">
                    <p class="text-sm text-twitch-muted">
                        Already have an account?
                        <a href="index.php?page=login" class="font-medium" style="color:#9147FF;">Sign in</a>
                    </p>
                </div>

                <div style="margin-top:16px; border-top:1px solid #2D2D35; padding-top:16px;">
                    <?php if (
                        defined("GITHUB_CLIENT_ID") &&
                        !empty(GITHUB_CLIENT_ID)
                    ): ?>
                    <a href="https://github.com/login/oauth/authorize?client_id=<?= GITHUB_CLIENT_ID ?>&redirect_uri=<?= urlencode(
    GITHUB_REDIRECT_URI,
) ?>&scope=user:email"
                       class="tw-btn tw-btn-block"
                       style="background:#24292e; color:white; justify-content:center; border:1px solid #3A3A45;">
                        <i class="fab fa-github"></i>
                        Sign up with GitHub
                    </a>
                    <?php else: ?>
                    <div style="text-align:center; padding:12px; background:rgba(145,71,255,0.05); border:1px solid rgba(145,71,255,0.15); border-radius:8px;">
                        <p class="text-xs text-twitch-muted">
                            <i class="fab fa-github mr-1"></i>
                            GitHub login requires an OAuth App —
                            <a href="https://github.com/settings/developers" target="_blank" style="color:#9147FF; text-decoration:underline;">create one</a>
                            and set <code style="font-size:10px; background:#0E0E10; padding:1px 4px; border-radius:3px;">GITHUB_CLIENT_ID</code> & <code style="font-size:10px; background:#0E0E10; padding:1px 4px; border-radius:3px;">GITHUB_CLIENT_SECRET</code> in config.php
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const usernameInput = document.querySelector('input[name="username"]');
    const passwordInput = document.querySelector('input[name="password"]');

    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            const val = this.value;
            const help = this.nextElementSibling;
            const isValid = /^[a-zA-Z0-9_]{3,}$/.test(val);

            if (val.length === 0) {
                help.textContent = '3+ characters, letters, numbers, underscores';
                help.className = 'text-xs text-twitch-muted mt-1';
            } else if (isValid) {
                help.textContent = '✓ Looks good!';
                help.className = 'text-xs text-green-400 mt-1';
            } else {
                help.textContent = '✗ Invalid format';
                help.className = 'text-xs text-red-400 mt-1';
            }
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const val = this.value;
            const help = this.nextElementSibling;

            if (val.length < 6) {
                help.textContent = `Too short (${val.length}/6)`;
                help.className = 'text-xs text-red-400 mt-1';
            } else if (val.length < 8) {
                help.textContent = 'Weak password';
                help.className = 'text-xs text-yellow-400 mt-1';
            } else if (val.length < 12) {
                help.textContent = 'Good password';
                help.className = 'text-xs text-blue-400 mt-1';
            } else {
                help.textContent = 'Strong password! 💪';
                help.className = 'text-xs text-green-400 mt-1';
            }
        });
    }
});
</script>
