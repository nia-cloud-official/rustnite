<?php
// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

$error = '';

// Only show error if form was submitted but login failed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        // If we reach here, it means the login failed (since we're still on this page)
        $error = "Invalid username or password";
    }
}
?>

<div class="min-h-screen flex items-center justify-center -mt-6 -mx-6">
    <div class="max-w-md w-full mx-auto px-6">
        <div class="content-card">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-r from-orange-500 to-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-code text-2xl text-white"></i>
                </div>
                <div class="title-medium">Welcome Back</div>
                <div class="text-secondary">Sign in to continue your Rust journey</div>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-4 mb-6">
                    <div class="text-red-400 text-sm">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-muted mb-2">Username or Email</label>
                    <input type="text" name="username" required 
                           class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="Enter your username or email">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-muted mb-2">Password</label>
                    <input type="password" name="password" required 
                           class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500"
                           placeholder="Enter your password">
                </div>
                
                <button type="submit" class="w-full btn-primary py-3 text-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Sign In
                </button>
            </form>
            
            <div class="text-center mt-6 pt-6 border-t border-gray-700">
                <p class="text-muted">
                    Don't have an account? 
                    <a href="index.php?page=register" class="text-orange-400 hover:text-orange-300 font-medium">
                        Create one here
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>