<?php
$user = get_user_by_id($_SESSION['user_id']);
$progress = get_user_progress($_SESSION['user_id']);
$completed_lessons = array_filter($progress, fn($lesson) => $lesson['completed']);

// Handle profile updates
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $bio = trim($_POST['bio'] ?? '');
        $github_username = trim($_POST['github_username'] ?? '');
        $twitter_username = trim($_POST['twitter_username'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $public_profile = isset($_POST['public_profile']) ? 1 : 0;
        
        $stmt = $pdo->prepare("
            UPDATE users SET 
                bio = ?, 
                github_username = ?, 
                twitter_username = ?, 
                website = ?, 
                location = ?,
                public_profile = ?
            WHERE id = ?
        ");
        
        if ($stmt->execute([$bio, $github_username, $twitter_username, $website, $location, $public_profile, $_SESSION['user_id']])) {
            $message = 'Profile updated successfully!';
            $message_type = 'success';
            $user = get_user_by_id($_SESSION['user_id']); // Refresh user data
        } else {
            $message = 'Failed to update profile.';
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['share_code'])) {
        $lesson_id = (int)$_POST['lesson_id'];
        $code = $_POST['code'];
        $description = trim($_POST['description'] ?? '');
        
        $stmt = $pdo->prepare("
            INSERT INTO code_shares (user_id, lesson_id, code, description, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([$_SESSION['user_id'], $lesson_id, $code, $description])) {
            $message = 'Code shared successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to share code.';
            $message_type = 'error';
        }
    }
}

// Get user statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN up.completed = 1 THEN 1 END) as completed_count,
        COUNT(*) as total_available,
        SUM(CASE WHEN up.completed = 1 THEN l.xp_reward ELSE 0 END) as total_xp_earned,
        MIN(CASE WHEN up.completed = 1 THEN up.completed_at END) as first_completion,
        MAX(CASE WHEN up.completed = 1 THEN up.completed_at END) as last_completion
    FROM lessons l 
    LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

// Get user rank
$stmt = $pdo->prepare("SELECT COUNT(*) + 1 as `user_rank` FROM users WHERE xp > ?");
$stmt->execute([$user['xp']]);
$user_rank = $stmt->fetch()['user_rank'];

// Get recent activity
$stmt = $pdo->prepare("
    SELECT l.title, l.difficulty, l.xp_reward, up.completed_at 
    FROM user_progress up 
    JOIN lessons l ON up.lesson_id = l.id 
    WHERE up.user_id = ? AND up.completed = 1 
    ORDER BY up.completed_at DESC 
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$recent_activity = $stmt->fetchAll();

// Get user's badges (with error handling for missing table)
$badges = [];
try {
    $stmt = $pdo->prepare("
        SELECT b.name, b.description, b.icon, ub.earned_at
        FROM user_badges ub
        JOIN badges b ON ub.badge_id = b.id
        WHERE ub.user_id = ?
        ORDER BY ub.earned_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $badges = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table doesn't exist yet, badges will be empty array
    $badges = [];
}

// Get shared code snippets (with error handling for missing table)
$shared_code = [];
try {
    $stmt = $pdo->prepare("
        SELECT cs.*, l.title as lesson_title, l.difficulty
        FROM code_shares cs
        JOIN lessons l ON cs.lesson_id = l.id
        WHERE cs.user_id = ?
        ORDER BY cs.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $shared_code = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table doesn't exist yet, shared_code will be empty array
    $shared_code = [];
}

// Calculate learning streak
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
    
    if ($last_completion->format('Y-m-d') === $today->format('Y-m-d') || 
        $last_completion->format('Y-m-d') === $yesterday->format('Y-m-d')) {
        
        $current_streak = 1;
        $check_date = clone $last_completion;
        
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
?>

<div class="max-w-6xl mx-auto">
    <!-- Profile Header -->
    <div class="content-card mb-8">
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $message_type === 'success' ? 'bg-green-500/20 border border-green-500/50 text-green-400' : 'bg-red-500/20 border border-red-500/50 text-red-400' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="flex items-start justify-between mb-6">
            <div class="flex items-center space-x-6">
                <!-- Avatar -->
                <div class="w-24 h-24 bg-gradient-to-br from-orange-500 to-red-500 rounded-full flex items-center justify-center text-3xl font-bold text-white">
                    <?= strtoupper(substr($user['username'], 0, 2)) ?>
                </div>
                
                <!-- User Info -->
                <div>
                    <div class="flex items-center space-x-4 mb-2">
                        <h1 class="title-large"><?= htmlspecialchars($user['username']) ?></h1>
                        <div class="bg-orange-500 text-white px-3 py-1 rounded-full text-sm font-bold">
                            Level <?= $user['level'] ?>
                        </div>
                        <div class="bg-purple-500 text-white px-3 py-1 rounded-full text-sm font-bold">
                            Rank #<?= $user_rank ?>
                        </div>
                    </div>
                    
                    <?php if ($user['bio'] ?? false): ?>
                        <p class="text-secondary mb-3"><?= htmlspecialchars($user['bio']) ?></p>
                    <?php endif; ?>
                    
                    <div class="flex items-center space-x-4 text-sm text-muted">
                        <?php if ($user['location'] ?? false): ?>
                            <span><i class="fas fa-map-marker-alt mr-1"></i> <?= htmlspecialchars($user['location']) ?></span>
                        <?php endif; ?>
                        
                        <span><i class="fas fa-calendar mr-1"></i> Joined <?= date('M Y', strtotime($user['created_at'])) ?></span>
                        
                        <?php if ($stats['first_completion']): ?>
                            <span><i class="fas fa-play mr-1"></i> Started learning <?= date('M Y', strtotime($stats['first_completion'])) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Social Links -->
                    <?php if (($user['github_username'] ?? false) || ($user['twitter_username'] ?? false) || ($user['website'] ?? false)): ?>
                        <div class="flex items-center space-x-4 mt-3">
                            <?php if ($user['github_username'] ?? false): ?>
                                <a href="https://github.com/<?= htmlspecialchars($user['github_username']) ?>" 
                                   target="_blank" class="text-gray-400 hover:text-white">
                                    <i class="fab fa-github text-lg"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($user['twitter_username'] ?? false): ?>
                                <a href="https://twitter.com/<?= htmlspecialchars($user['twitter_username']) ?>" 
                                   target="_blank" class="text-gray-400 hover:text-blue-400">
                                    <i class="fab fa-twitter text-lg"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($user['website'] ?? false): ?>
                                <a href="<?= htmlspecialchars($user['website']) ?>" 
                                   target="_blank" class="text-gray-400 hover:text-orange-400">
                                    <i class="fas fa-globe text-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Edit Profile Button -->
            <button onclick="toggleEditProfile()" class="btn-secondary">
                <i class="fas fa-edit mr-2"></i>
                Edit Profile
            </button>
        </div>
    </div>

    <!-- Edit Profile Form (Hidden by default) -->
    <div id="edit-profile-form" class="content-card mb-8 hidden">
        <div class="title-medium mb-6">Edit Profile</div>
        
        <form method="POST" class="space-y-6">
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium mb-2">Bio</label>
                    <textarea name="bio" rows="3" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white" 
                              placeholder="Tell us about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Location</label>
                    <input type="text" name="location" value="<?= htmlspecialchars($user['location'] ?? '') ?>" 
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white" 
                           placeholder="City, Country">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">GitHub Username</label>
                    <input type="text" name="github_username" value="<?= htmlspecialchars($user['github_username'] ?? '') ?>" 
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white" 
                           placeholder="your-github-username">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Twitter Username</label>
                    <input type="text" name="twitter_username" value="<?= htmlspecialchars($user['twitter_username'] ?? '') ?>" 
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white" 
                           placeholder="your-twitter-handle">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-2">Website</label>
                    <input type="url" name="website" value="<?= htmlspecialchars($user['website'] ?? '') ?>" 
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white" 
                           placeholder="https://your-website.com">
                </div>
                
                <div class="md:col-span-2">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="public_profile" <?= ($user['public_profile'] ?? 0) ? 'checked' : '' ?> 
                               class="rounded bg-gray-800 border-gray-700 text-orange-500">
                        <span class="text-sm">Make my profile public (visible to other users)</span>
                    </label>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <button type="submit" name="update_profile" class="btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    Save Changes
                </button>
                <button type="button" onclick="toggleEditProfile()" class="btn-secondary">
                    Cancel
                </button>
            </div>
        </form>
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Statistics -->
            <div class="content-card">
                <div class="title-medium mb-6">Learning Statistics</div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="stat-item">
                        <div class="stat-value text-orange-400"><?= number_format($user['xp']) ?></div>
                        <div class="stat-label">Total XP</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-value text-green-400"><?= $stats['completed_count'] ?></div>
                        <div class="stat-label">Lessons Completed</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-value text-blue-400"><?= $current_streak ?></div>
                        <div class="stat-label">Day Streak</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-value text-purple-400"><?= round(($stats['completed_count'] / max($stats['total_available'], 1)) * 100) ?>%</div>
                        <div class="stat-label">Progress</div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="content-card">
                <div class="title-medium mb-6">Recent Activity</div>
                
                <?php if (!empty($recent_activity)): ?>
                    <div class="space-y-4">
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="flex items-center justify-between p-4 bg-gray-800/50 rounded-lg">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                                        <i class="fas fa-check text-white"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium"><?= htmlspecialchars($activity['title']) ?></div>
                                        <div class="text-sm text-muted">
                                            <?= ucfirst($activity['difficulty']) ?> • 
                                            <?= time_ago($activity['completed_at']) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-orange-400 font-bold">
                                    +<?= $activity['xp_reward'] ?> XP
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-clock text-4xl text-gray-600 mb-4"></i>
                        <div class="text-muted">No recent activity</div>
                        <a href="index.php?page=lessons" class="btn-primary mt-4">
                            Start Learning
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Badges -->
            <div class="content-card">
                <div class="title-medium mb-4">Achievements</div>
                
                <?php if (!empty($badges)): ?>
                    <div class="grid grid-cols-3 gap-3">
                        <?php foreach ($badges as $badge): ?>
                            <div class="text-center p-3 bg-gray-800/50 rounded-lg" title="<?= htmlspecialchars($badge['description']) ?>">
                                <i class="<?= $badge['icon'] ?> text-2xl text-yellow-400 mb-2"></i>
                                <div class="text-xs font-medium"><?= htmlspecialchars($badge['name']) ?></div>
                                <div class="text-xs text-muted"><?= date('M Y', strtotime($badge['earned_at'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-6">
                        <i class="fas fa-medal text-4xl text-gray-600 mb-3"></i>
                        <div class="text-muted text-sm">No badges earned yet</div>
                        <div class="text-xs text-secondary mt-1">Complete lessons to earn achievements!</div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Progress Chart -->
            <div class="content-card">
                <div class="title-medium mb-4">Learning Progress</div>
                
                <div class="space-y-4">
                    <?php
                    $difficulties = ['beginner', 'intermediate', 'advanced'];
                    foreach ($difficulties as $difficulty):
                        $stmt = $pdo->prepare("
                            SELECT 
                                COUNT(*) as total,
                                COUNT(CASE WHEN up.completed = 1 THEN 1 END) as completed
                            FROM lessons l
                            LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = ?
                            WHERE l.difficulty = ?
                        ");
                        $stmt->execute([$_SESSION['user_id'], $difficulty]);
                        $diff_stats = $stmt->fetch();
                        $percentage = $diff_stats['total'] > 0 ? ($diff_stats['completed'] / $diff_stats['total']) * 100 : 0;
                    ?>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium capitalize"><?= $difficulty ?></span>
                                <span class="text-sm text-muted"><?= $diff_stats['completed'] ?>/<?= $diff_stats['total'] ?></span>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar" style="width: <?= $percentage ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleEditProfile() {
    const form = document.getElementById('edit-profile-form');
    form.classList.toggle('hidden');
}
</script>