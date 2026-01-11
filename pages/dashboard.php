<?php
$user = get_user_by_id($_SESSION['user_id']);
$progress = get_user_progress($_SESSION['user_id']);
$completed_lessons = array_filter($progress, fn($lesson) => $lesson['completed']);
$total_lessons = count($progress);

// Get user statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN up.completed = 1 THEN 1 END) as completed_count,
        COUNT(*) as total_available,
        SUM(CASE WHEN up.completed = 1 THEN l.xp_reward ELSE 0 END) as total_xp_earned,
        AVG(CASE WHEN up.completed = 1 THEN l.xp_reward ELSE NULL END) as avg_xp_per_lesson
    FROM lessons l 
    LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

// Get user rank
$stmt = $pdo->prepare("SELECT COUNT(*) + 1 as rank FROM users WHERE xp > ?");
$stmt->execute([$user['xp']]);
$user_rank = $stmt->fetch()['rank'];

// Get recent activity
$stmt = $pdo->prepare("
    SELECT l.title, l.difficulty, l.xp_reward, up.completed_at 
    FROM user_progress up 
    JOIN lessons l ON up.lesson_id = l.id 
    WHERE up.user_id = ? AND up.completed = 1 
    ORDER BY up.completed_at DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_activity = $stmt->fetchAll();

// Get learning streak
$stmt = $pdo->prepare("
    SELECT DATE(completed_at) as completion_date 
    FROM user_progress 
    WHERE user_id = ? AND completed = 1 
    ORDER BY completed_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$completion_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

$current_streak = 0;
$today = new DateTime();
$yesterday = new DateTime('-1 day');

if (!empty($completion_dates)) {
    $last_completion = new DateTime($completion_dates[0]);
    
    // Check if completed today or yesterday
    if ($last_completion->format('Y-m-d') === $today->format('Y-m-d') || 
        $last_completion->format('Y-m-d') === $yesterday->format('Y-m-d')) {
        
        $current_streak = 1;
        $check_date = clone $last_completion;
        
        // Count consecutive days
        for ($i = 1; $i < count($completion_dates); $i++) {
            $prev_date = new DateTime($completion_dates[$i]);
            $check_date->modify('-1 day');
            
            if ($prev_date->format('Y-m-d') === $check_date->format('Y-m-d')) {
                $current_streak++;
            } else {
                break;
            }
        }
    }
}

// Handle filter changes
$filter = $_GET['filter'] ?? 'popular';
$valid_filters = ['popular', 'recent', 'difficulty'];
if (!in_array($filter, $valid_filters)) {
    $filter = 'popular';
}
?>

<!-- Main Dashboard Content -->
<div class="title-large">Rustnite</div>
<div class="text-secondary mb-8">
    Master Rust programming through battle-tested challenges based on the official Rust Book. 
    Build real projects and compete with developers worldwide.
</div>

<!-- Filter Buttons -->
<div class="flex items-center space-x-4 mb-6">
    <a href="?page=dashboard&filter=popular" 
       class="<?= $filter === 'popular' ? 'btn-primary' : 'btn-secondary' ?>">
        <i class="fas fa-fire mr-2"></i>
        Popular
    </a>
    <a href="?page=dashboard&filter=recent" 
       class="<?= $filter === 'recent' ? 'btn-primary' : 'btn-secondary' ?>">
        <i class="fas fa-clock mr-2"></i>
        Recent
    </a>
    <a href="?page=dashboard&filter=difficulty" 
       class="<?= $filter === 'difficulty' ? 'btn-primary' : 'btn-secondary' ?>">
        <i class="fas fa-chart-line mr-2"></i>
        By Difficulty
    </a>
</div>

<!-- Main Content Card -->
<div class="content-card">
    <div class="flex items-start justify-between mb-6">
        <div class="flex-1">
            <div class="title-medium">Rust Programming Journey</div>
            <div class="text-secondary mb-6">
                Learn Rust through hands-on coding challenges, build real-world projects like CLI tools, 
                web servers, and system programs. Master memory safety, ownership, and concurrency.
            </div>
            
            <div class="flex items-center space-x-4 mb-6">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-cog text-sm"></i>
                    </div>
                    <span class="text-sm text-muted">Systems Programming</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-server text-sm"></i>
                    </div>
                    <span class="text-sm text-muted">Backend Development</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-globe text-sm"></i>
                    </div>
                    <span class="text-sm text-muted">Web Applications</span>
                </div>
            </div>
            
            <a href="index.php?page=lessons" class="btn-primary">
                Start Learning <i class="fas fa-play ml-2"></i>
            </a>
        </div>
        
        <!-- Rust Logo/Icon Preview -->
        <div class="w-64 h-48 bg-gradient-to-br from-orange-500/20 to-transparent rounded-lg flex items-center justify-center">
            <div class="text-center">
                <i class="fas fa-cog text-6xl text-orange-500 mb-4 animate-spin" style="animation-duration: 8s;"></i>
                <div class="text-orange-500 font-bold text-xl">RUST</div>
                <div class="text-sm text-muted mt-2">Systems Programming</div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats Overview -->
<div class="grid grid-cols-3 gap-6 mb-8">
    <div class="content-card text-center">
        <div class="text-3xl font-bold text-green-400 mb-2"><?= $stats['completed_count'] ?></div>
        <div class="text-sm text-muted">Lessons Completed</div>
    </div>
    <div class="content-card text-center">
        <div class="text-3xl font-bold text-blue-400 mb-2"><?= round(($stats['completed_count'] / max($stats['total_available'], 1)) * 100) ?>%</div>
        <div class="text-sm text-muted">Progress</div>
    </div>
    <div class="content-card text-center">
        <div class="text-3xl font-bold text-orange-400 mb-2"><?= $current_streak ?></div>
        <div class="text-sm text-muted">Day Streak</div>
    </div>
</div>

<!-- Learning Paths -->
<div class="mt-8">
    <div class="flex items-center justify-between mb-6">
        <div class="title-medium">Learning Paths</div>
        <a href="index.php?page=lessons" class="text-orange-400 hover:text-orange-300 text-sm font-medium">
            View All <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>
    
    <div class="grid grid-cols-3 gap-6">
        <!-- Fundamentals Path -->
        <div class="content-card hover:border-green-500/50 transition-all cursor-pointer" onclick="window.location.href='index.php?page=lessons&difficulty=beginner'">
            <div class="text-center">
                <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-500 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-book text-2xl text-white"></i>
                </div>
                <div class="font-bold text-lg mb-2">FUNDAMENTALS</div>
                <div class="text-muted text-sm mb-4">Basic Rust Concepts</div>
                
                <?php
                $beginner_count = $pdo->prepare("SELECT COUNT(*) as count FROM lessons WHERE difficulty = 'beginner'");
                $beginner_count->execute();
                $beginner_total = $beginner_count->fetch()['count'];
                
                $beginner_completed = $pdo->prepare("
                    SELECT COUNT(*) as count FROM user_progress up 
                    JOIN lessons l ON up.lesson_id = l.id 
                    WHERE up.user_id = ? AND up.completed = 1 AND l.difficulty = 'beginner'
                ");
                $beginner_completed->execute([$_SESSION['user_id']]);
                $beginner_done = $beginner_completed->fetch()['count'];
                ?>
                
                <div class="text-xs text-green-400"><?= $beginner_done ?>/<?= $beginner_total ?> completed</div>
                <div class="w-full bg-gray-700 rounded-full h-2 mt-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: <?= $beginner_total > 0 ? ($beginner_done / $beginner_total) * 100 : 0 ?>%"></div>
                </div>
            </div>
        </div>
        
        <!-- Intermediate Path -->
        <div class="content-card hover:border-blue-500/50 transition-all cursor-pointer" onclick="window.location.href='index.php?page=lessons&difficulty=intermediate'">
            <div class="text-center">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-microchip text-2xl text-white"></i>
                </div>
                <div class="font-bold text-lg mb-2">SYSTEMS</div>
                <div class="text-muted text-sm mb-4">Low-level Programming</div>
                
                <?php
                $intermediate_count = $pdo->prepare("SELECT COUNT(*) as count FROM lessons WHERE difficulty = 'intermediate'");
                $intermediate_count->execute();
                $intermediate_total = $intermediate_count->fetch()['count'];
                
                $intermediate_completed = $pdo->prepare("
                    SELECT COUNT(*) as count FROM user_progress up 
                    JOIN lessons l ON up.lesson_id = l.id 
                    WHERE up.user_id = ? AND up.completed = 1 AND l.difficulty = 'intermediate'
                ");
                $intermediate_completed->execute([$_SESSION['user_id']]);
                $intermediate_done = $intermediate_completed->fetch()['count'];
                ?>
                
                <div class="text-xs text-blue-400"><?= $intermediate_done ?>/<?= $intermediate_total ?> completed</div>
                <div class="w-full bg-gray-700 rounded-full h-2 mt-2">
                    <div class="bg-blue-500 h-2 rounded-full" style="width: <?= $intermediate_total > 0 ? ($intermediate_done / $intermediate_total) * 100 : 0 ?>%"></div>
                </div>
            </div>
        </div>
        
        <!-- Advanced Path -->
        <div class="content-card hover:border-orange-500/50 transition-all cursor-pointer" onclick="window.location.href='index.php?page=lessons&difficulty=advanced'">
            <div class="text-center">
                <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-red-500 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-globe text-2xl text-white"></i>
                </div>
                <div class="font-bold text-lg mb-2">ADVANCED</div>
                <div class="text-muted text-sm mb-4">Complex Applications</div>
                
                <?php
                $advanced_count = $pdo->prepare("SELECT COUNT(*) as count FROM lessons WHERE difficulty = 'advanced'");
                $advanced_count->execute();
                $advanced_total = $advanced_count->fetch()['count'];
                
                $advanced_completed = $pdo->prepare("
                    SELECT COUNT(*) as count FROM user_progress up 
                    JOIN lessons l ON up.lesson_id = l.id 
                    WHERE up.user_id = ? AND up.completed = 1 AND l.difficulty = 'advanced'
                ");
                $advanced_completed->execute([$_SESSION['user_id']]);
                $advanced_done = $advanced_completed->fetch()['count'];
                ?>
                
                <div class="text-xs text-orange-400"><?= $advanced_done ?>/<?= $advanced_total ?> completed</div>
                <div class="w-full bg-gray-700 rounded-full h-2 mt-2">
                    <div class="bg-orange-500 h-2 rounded-full" style="width: <?= $advanced_total > 0 ? ($advanced_done / $advanced_total) * 100 : 0 ?>%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Section -->
<div class="mt-8">
    <div class="title-medium mb-6">Your Statistics</div>
    
    <div class="grid grid-cols-3 gap-6">
        <!-- Total Learning Time -->
        <div class="stat-item">
            <div class="flex items-center justify-between mb-4">
                <div class="text-muted">Total Learning Time</div>
                <div class="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-white text-sm"></i>
                </div>
            </div>
            <div class="stat-value"><?= number_format(($stats['completed_count'] * 45) / 60, 1) ?>h</div>
            <div class="text-xs text-green-400 mt-1">+<?= $stats['completed_count'] > 0 ? round(($stats['completed_count'] / 7) * 100) : 0 ?>% this week</div>
        </div>
        
        <!-- Problems Solved -->
        <div class="stat-item">
            <div class="flex items-center justify-between mb-4">
                <div class="text-muted">Problems Solved</div>
                <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-puzzle-piece text-white text-sm"></i>
                </div>
            </div>
            <div class="stat-value"><?= $stats['completed_count'] ?></div>
            <div class="text-xs text-blue-400 mt-1">Avg <?= $stats['avg_xp_per_lesson'] ? round($stats['avg_xp_per_lesson']) : 0 ?> XP each</div>
        </div>
        
        <!-- Global Rank -->
        <div class="stat-item">
            <div class="flex items-center justify-between mb-4">
                <div class="text-muted">Global Rank</div>
                <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-trophy text-white text-sm"></i>
                </div>
            </div>
            <div class="stat-value">#<?= $user_rank ?></div>
            <div class="text-xs text-purple-400 mt-1">
                <?php
                $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                $percentile = round((($total_users - $user_rank + 1) / $total_users) * 100);
                echo "Top {$percentile}%";
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Continue Learning -->
<div class="mt-8">
    <div class="flex items-center justify-between mb-6">
        <div class="title-medium">Continue Learning</div>
        <div class="flex items-center space-x-2">
            <span class="text-sm text-muted">Streak: <?= $current_streak ?> days</span>
            <div class="text-orange-400">🔥</div>
        </div>
    </div>
    
    <div class="space-y-4">
        <?php 
        // Get next lessons based on filter
        switch ($filter) {
            case 'recent':
                $lesson_query = "
                    SELECT l.*, up.completed, up.completed_at 
                    FROM lessons l 
                    LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = ?
                    ORDER BY l.id DESC 
                    LIMIT 4
                ";
                break;
            case 'difficulty':
                $lesson_query = "
                    SELECT l.*, up.completed, up.completed_at 
                    FROM lessons l 
                    LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = ?
                    ORDER BY 
                        CASE l.difficulty 
                            WHEN 'beginner' THEN 1 
                            WHEN 'intermediate' THEN 2 
                            WHEN 'advanced' THEN 3 
                        END, l.order_num 
                    LIMIT 4
                ";
                break;
            default: // popular
                $lesson_query = "
                    SELECT l.*, up.completed, up.completed_at,
                           (SELECT COUNT(*) FROM user_progress up2 WHERE up2.lesson_id = l.id AND up2.completed = 1) as completion_count
                    FROM lessons l 
                    LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = ?
                    ORDER BY completion_count DESC, l.xp_reward DESC 
                    LIMIT 4
                ";
        }
        
        $stmt = $pdo->prepare($lesson_query);
        $stmt->execute([$_SESSION['user_id']]);
        $featured_lessons = $stmt->fetchAll();
        
        foreach ($featured_lessons as $lesson): 
        ?>
            <div class="content-card hover:border-orange-500/50 transition-all">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 <?= $lesson['completed'] ? 'bg-green-500' : 'bg-orange-500' ?> rounded-lg flex items-center justify-center">
                            <i class="fas fa-<?= $lesson['completed'] ? 'check' : 'play' ?> text-white"></i>
                        </div>
                        <div class="flex-1">
                            <div class="font-semibold text-lg"><?= htmlspecialchars($lesson['title']) ?></div>
                            <div class="text-sm text-muted mb-2"><?= htmlspecialchars(substr($lesson['description'], 0, 80)) ?>...</div>
                            <div class="flex items-center space-x-4">
                                <span class="text-xs bg-<?= $lesson['difficulty'] === 'beginner' ? 'green' : ($lesson['difficulty'] === 'intermediate' ? 'blue' : 'orange') ?>-500/20 text-<?= $lesson['difficulty'] === 'beginner' ? 'green' : ($lesson['difficulty'] === 'intermediate' ? 'blue' : 'orange') ?>-400 px-2 py-1 rounded-full">
                                    <?= ucfirst($lesson['difficulty']) ?>
                                </span>
                                <span class="text-xs text-gray-500">
                                    <i class="fas fa-star"></i> <?= $lesson['xp_reward'] ?> XP
                                </span>
                                <?php if ($lesson['completed']): ?>
                                    <span class="text-xs text-green-400">
                                        <i class="fas fa-check-circle"></i> Completed
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <a href="index.php?page=lesson&id=<?= $lesson['id'] ?>" 
                           class="<?= $lesson['completed'] ? 'btn-secondary' : 'btn-primary' ?> px-6 py-2">
                            <?= $lesson['completed'] ? 'Review' : 'Start' ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($featured_lessons)): ?>
            <div class="content-card text-center py-12">
                <i class="fas fa-book text-6xl text-gray-600 mb-4"></i>
                <div class="title-medium mb-4">No Lessons Available</div>
                <div class="text-secondary mb-6">Lessons are being prepared for your Rust journey!</div>
                <a href="index.php?page=lessons" class="btn-primary">
                    Check Back Later
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Add interactive features
document.addEventListener('DOMContentLoaded', function() {
    // Animate statistics on load
    const statValues = document.querySelectorAll('.stat-value');
    statValues.forEach(stat => {
        const finalValue = parseInt(stat.textContent);
        if (!isNaN(finalValue)) {
            let currentValue = 0;
            const increment = finalValue / 30;
            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= finalValue) {
                    stat.textContent = finalValue;
                    clearInterval(timer);
                } else {
                    stat.textContent = Math.floor(currentValue);
                }
            }, 50);
        }
    });
    
    // Add hover effects to learning path cards
    const pathCards = document.querySelectorAll('.content-card[onclick]');
    pathCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>