<?php
$leaderboard = get_leaderboard(50);
$user = get_user_by_id($_SESSION['user_id']);

// Find current user's rank
$user_rank = null;
foreach ($leaderboard as $index => $player) {
    if ($player['username'] === $user['username']) {
        $user_rank = $index + 1;
        break;
    }
}

// If user not in top 50, get their rank
if (!$user_rank) {
    $stmt = $pdo->prepare("SELECT COUNT(*) + 1 as rank FROM users WHERE xp > ?");
    $stmt->execute([$user['xp']]);
    $user_rank = $stmt->fetch()['rank'];
}
?>

<!-- Leaderboard Header -->
<div class="title-large">Global Leaderboard</div>
<div class="text-secondary mb-8">
    See how you stack up against other Rust warriors worldwide. Compete for the top spot!
</div>

<!-- Filter Buttons -->
<div class="flex items-center space-x-4 mb-6">
    <button class="btn-primary">
        <i class="fas fa-trophy mr-2"></i>
        All Time
    </button>
    <button class="btn-secondary">
        <i class="fas fa-calendar-week mr-2"></i>
        This Week
    </button>
    <button class="btn-secondary">
        <i class="fas fa-calendar mr-2"></i>
        This Month
    </button>
</div>

<!-- Your Current Position -->
<div class="content-card mb-6">
    <div class="title-medium mb-6">Your Battle Stats</div>
    <div class="grid grid-cols-4 gap-6">
        <div class="stat-item">
            <div class="stat-value text-orange-400">#{<?= $user_rank ?>}</div>
            <div class="stat-label">Current Rank</div>
        </div>
        <div class="stat-item">
            <div class="stat-value text-yellow-400"><?= number_format($user['xp']) ?></div>
            <div class="stat-label">Total XP</div>
        </div>
        <div class="stat-item">
            <div class="stat-value text-purple-400"><?= $user['level'] ?></div>
            <div class="stat-label">Level</div>
        </div>
        <div class="stat-item">
            <?php
            $completed_count = $pdo->prepare("SELECT COUNT(*) as count FROM user_progress WHERE user_id = ? AND completed = 1");
            $completed_count->execute([$_SESSION['user_id']]);
            $completed = $completed_count->fetch()['count'];
            ?>
            <div class="stat-value text-green-400"><?= $completed ?></div>
            <div class="stat-label">Lessons Completed</div>
        </div>
    </div>
</div>

<!-- Top Players -->
<div class="content-card">
    <div class="title-medium mb-6">Top Rust Warriors</div>
    
    <?php if (!empty($leaderboard)): ?>
        <div class="space-y-4">
            <?php foreach ($leaderboard as $index => $player): ?>
                <div class="flex items-center justify-between p-4 <?= $player['username'] === $user['username'] ? 'bg-orange-500/10 border border-orange-500/20' : 'bg-gray-800/30' ?> rounded-lg">
                    <div class="flex items-center space-x-4">
                        <!-- Rank -->
                        <div class="w-12 text-center">
                            <?php if ($index < 3): ?>
                                <?php 
                                $medal_colors = ['text-yellow-400', 'text-gray-300', 'text-orange-400'];
                                $medal_icons = ['crown', 'medal', 'award'];
                                ?>
                                <i class="fas fa-<?= $medal_icons[$index] ?> text-2xl <?= $medal_colors[$index] ?>"></i>
                            <?php else: ?>
                                <span class="text-xl font-bold text-gray-400">#<?= $index + 1 ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Player Avatar -->
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center">
                            <span class="font-bold text-lg"><?= strtoupper(substr($player['username'], 0, 2)) ?></span>
                        </div>
                        
                        <!-- Player Info -->
                        <div>
                            <div class="font-bold text-lg <?= $player['username'] === $user['username'] ? 'text-orange-400' : 'text-white' ?>">
                                <?= htmlspecialchars($player['username']) ?>
                                <?php if ($player['username'] === $user['username']): ?>
                                    <span class="text-sm text-orange-300 ml-2">(You)</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-sm text-muted">Level <?= $player['level'] ?> Rustacean</div>
                        </div>
                    </div>
                    
                    <!-- XP and Stats -->
                    <div class="text-right">
                        <div class="text-xl font-bold text-yellow-400"><?= number_format($player['xp']) ?> XP</div>
                        <?php if ($index > 0): ?>
                            <?php $xp_diff = $leaderboard[0]['xp'] - $player['xp']; ?>
                            <div class="text-sm text-muted">-<?= number_format($xp_diff) ?> from #1</div>
                        <?php else: ?>
                            <div class="text-sm text-yellow-400">👑 Champion</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-12">
            <i class="fas fa-trophy text-6xl text-gray-600 mb-4"></i>
            <div class="title-medium mb-4">Be the First Champion!</div>
            <div class="text-secondary mb-6">Complete lessons to earn XP and claim your spot on the leaderboard!</div>
            <a href="index.php?page=lessons" class="btn-primary">
                Start Learning
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Motivational Section -->
<div class="content-card mt-6" style="background: linear-gradient(135deg, rgba(255, 107, 53, 0.1), rgba(147, 51, 234, 0.1));">
    <div class="title-medium mb-6">Climb the Ranks!</div>
    <div class="grid grid-cols-3 gap-6 text-center">
        <div>
            <div class="w-16 h-16 bg-orange-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-fire text-2xl text-white"></i>
            </div>
            <div class="font-bold mb-2">Complete Lessons</div>
            <div class="text-sm text-secondary">Earn XP by solving Rust challenges</div>
        </div>
        <div>
            <div class="w-16 h-16 bg-yellow-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-bolt text-2xl text-white"></i>
            </div>
            <div class="font-bold mb-2">Level Up</div>
            <div class="text-sm text-secondary">Gain levels to show your progress</div>
        </div>
        <div>
            <div class="w-16 h-16 bg-purple-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-crown text-2xl text-white"></i>
            </div>
            <div class="font-bold mb-2">Dominate</div>
            <div class="text-sm text-secondary">Rise to the top of the leaderboard</div>
        </div>
    </div>
</div>