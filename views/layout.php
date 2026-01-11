<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Rustnite' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .rustnite-gradient { background: linear-gradient(135deg, #ff6b35, #f7931e, #ffd23f); }
        .battle-royale-bg { background: linear-gradient(45deg, #1a1a2e, #16213e, #0f3460); }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <nav class="rustnite-gradient p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <i class="fas fa-code text-2xl"></i>
                <h1 class="text-2xl font-bold">Rustnite</h1>
            </div>
            <div class="space-x-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/dashboard" class="hover:text-gray-200">Dashboard</a>
                    <a href="/lessons" class="hover:text-gray-200">Lessons</a>
                    <a href="/leaderboard" class="hover:text-gray-200">Leaderboard</a>
                    <a href="/logout" class="hover:text-gray-200">Logout</a>
                <?php else: ?>
                    <a href="/login" class="hover:text-gray-200">Login</a>
                    <a href="/register" class="hover:text-gray-200">Register</a>
                <?php endif; ?>
                <a href="/donate" class="bg-yellow-500 text-black px-4 py-2 rounded hover:bg-yellow-400">
                    <i class="fas fa-heart"></i> Donate
                </a>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <?= $content ?>
    </main>

    <footer class="battle-royale-bg p-8 mt-16">
        <div class="container mx-auto text-center">
            <p>&copy; 2024 Rustnite. Battle your way to Rust mastery!</p>
        </div>
    </footer>
</body>
</html>