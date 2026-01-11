<div class="text-center">
    <div class="battle-royale-bg rounded-lg p-12 mb-8">
        <h1 class="text-6xl font-bold mb-4 rustnite-gradient bg-clip-text text-transparent">
            Welcome to Rustnite
        </h1>
        <p class="text-xl mb-8">Battle-Royale Style Rust Learning Platform</p>
        <p class="text-lg text-gray-300 mb-8">
            Drop into the world of Rust programming with gamified challenges, 
            earn XP, unlock badges, and climb the leaderboards!
        </p>
        
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="space-x-4">
                <a href="/register" class="rustnite-gradient px-8 py-3 rounded-lg text-black font-bold hover:opacity-90 inline-block">
                    <i class="fas fa-rocket"></i> Start Your Battle
                </a>
                <a href="/login" class="border-2 border-orange-500 px-8 py-3 rounded-lg hover:bg-orange-500 inline-block">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            </div>
        <?php else: ?>
            <a href="/dashboard" class="rustnite-gradient px-8 py-3 rounded-lg text-black font-bold hover:opacity-90 inline-block">
                <i class="fas fa-tachometer-alt"></i> Go to Dashboard
            </a>
        <?php endif; ?>
    </div>

    <div class="grid md:grid-cols-3 gap-8 mb-12">
        <?php foreach ($features as $feature): ?>
            <div class="bg-gray-800 p-6 rounded-lg">
                <i class="fas fa-trophy text-3xl text-yellow-500 mb-4"></i>
                <p class="text-lg"><?= htmlspecialchars($feature) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="bg-gray-800 p-8 rounded-lg">
        <h2 class="text-3xl font-bold mb-4">How Rustnite Works</h2>
        <div class="grid md:grid-cols-4 gap-6 text-left">
            <div class="text-center">
                <div class="rustnite-gradient w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-2xl font-bold text-black">1</span>
                </div>
                <h3 class="font-bold mb-2">Register Free</h3>
                <p class="text-gray-300">Create your account and jump into the battle</p>
            </div>
            <div class="text-center">
                <div class="rustnite-gradient w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-2xl font-bold text-black">2</span>
                </div>
                <h3 class="font-bold mb-2">Complete Challenges</h3>
                <p class="text-gray-300">Solve Rust coding challenges to earn XP</p>
            </div>
            <div class="text-center">
                <div class="rustnite-gradient w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-2xl font-bold text-black">3</span>
                </div>
                <h3 class="font-bold mb-2">Earn Rewards</h3>
                <p class="text-gray-300">Unlock badges and climb leaderboards</p>
            </div>
            <div class="text-center">
                <div class="rustnite-gradient w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-2xl font-bold text-black">4</span>
                </div>
                <h3 class="font-bold mb-2">Master Rust</h3>
                <p class="text-gray-300">Become a Rust programming champion</p>
            </div>
        </div>
    </div>
</div>