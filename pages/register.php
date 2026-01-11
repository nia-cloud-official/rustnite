<?php
// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

$errors = [];

// Check for POST data to show errors (form processing is now in index.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation for display purposes
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
}
?>

<div class="min-h-screen flex items-center justify-center -mt-6 -mx-6">
    <div class="max-w-md w-full mx-auto px-6">
        <div class="content-card">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-r from-orange-500 to-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-rocket text-2xl text-white"></i>
                </div>
                <div class="title-medium">Join Rustnite</div>
                <div class="text-secondary">Start your Rust programming journey today</div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-4 mb-6">
                    <?php foreach ($errors as $error): ?>
                        <div class="text-red-400 text-sm mb-1">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-muted mb-2">Username</label>
                    <input type="text" name="username" required 
                           class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="Choose a unique username">
                    <div class="text-xs text-muted mt-1">3+ characters, letters, numbers, and underscores only</div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-muted mb-2">Email</label>
                    <input type="email" name="email" required 
                           class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="Enter your email address">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-muted mb-2">Password</label>
                    <input type="password" name="password" required 
                           class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500"
                           placeholder="Create a secure password">
                    <div class="text-xs text-muted mt-1">Minimum 6 characters</div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-muted mb-2">Confirm Password</label>
                    <input type="password" name="confirm_password" required 
                           class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500"
                           placeholder="Confirm your password">
                </div>
                
                <button type="submit" class="w-full btn-primary py-3 text-lg">
                    <i class="fas fa-user-plus mr-2"></i>
                    Create Account
                </button>
            </form>
            
            <div class="text-center mt-6 pt-6 border-t border-gray-700">
                <p class="text-muted">
                    Already have an account? 
                    <a href="index.php?page=login" class="text-orange-400 hover:text-orange-300 font-medium">
                        Sign in here
                    </a>
                </p>
            </div>
        </div>
        
        <!-- Features Preview -->
        <div class="content-card mt-6">
            <div class="text-center">
                <div class="text-sm font-medium mb-3">What you'll get:</div>
                <div class="grid grid-cols-3 gap-4 text-xs">
                    <div>
                        <i class="fas fa-book text-orange-400 mb-1"></i>
                        <div>50+ Lessons</div>
                    </div>
                    <div>
                        <i class="fas fa-trophy text-yellow-400 mb-1"></i>
                        <div>XP & Badges</div>
                    </div>
                    <div>
                        <i class="fas fa-code text-blue-400 mb-1"></i>
                        <div>Real Projects</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Real-time username validation
    const usernameInput = document.querySelector('input[name="username"]');
    const usernameHelp = usernameInput.nextElementSibling;
    
    usernameInput.addEventListener('input', function() {
        const username = this.value;
        const isValid = /^[a-zA-Z0-9_]{3,}$/.test(username);
        
        if (username.length === 0) {
            usernameHelp.textContent = '3+ characters, letters, numbers, and underscores only';
            usernameHelp.className = 'text-xs text-muted mt-1';
        } else if (isValid) {
            usernameHelp.textContent = '✓ Username looks good!';
            usernameHelp.className = 'text-xs text-green-400 mt-1';
        } else {
            usernameHelp.textContent = '✗ Invalid username format';
            usernameHelp.className = 'text-xs text-red-400 mt-1';
        }
    });
    
    // Password strength indicator
    const passwordInput = document.querySelector('input[name="password"]');
    const passwordHelp = passwordInput.nextElementSibling;
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strength = getPasswordStrength(password);
        
        passwordHelp.textContent = `${strength.text} (${password.length}/6+ chars)`;
        passwordHelp.className = `text-xs ${strength.color} mt-1`;
    });
    
    function getPasswordStrength(password) {
        if (password.length < 6) return { text: 'Too short', color: 'text-red-400' };
        if (password.length < 8) return { text: 'Weak', color: 'text-yellow-400' };
        if (password.length < 12) return { text: 'Good', color: 'text-blue-400' };
        return { text: 'Strong', color: 'text-green-400' };
    }
});
</script>