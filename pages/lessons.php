<?php
$user_progress = get_user_progress($_SESSION['user_id']);

// Handle filters
$difficulty_filter = $_GET['difficulty'] ?? 'all';
$search_query = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'order';

$valid_difficulties = ['all', 'beginner', 'intermediate', 'advanced'];
$valid_sorts = ['order', 'difficulty', 'xp', 'title'];

if (!in_array($difficulty_filter, $valid_difficulties)) {
    $difficulty_filter = 'all';
}
if (!in_array($sort_by, $valid_sorts)) {
    $sort_by = 'order';
}

// Build query
$where_conditions = [];
$params = [$_SESSION['user_id']];

if ($difficulty_filter !== 'all') {
    $where_conditions[] = "l.difficulty = ?";
    $params[] = $difficulty_filter;
}

if (!empty($search_query)) {
    $where_conditions[] = "(l.title LIKE ? OR l.description LIKE ?)";
    $params[] = "%{$search_query}%";
    $params[] = "%{$search_query}%";
}

$where_clause = !empty($where_conditions) ? 'AND ' . implode(' AND ', $where_conditions) : '';

// Sort clause
$sort_clause = match($sort_by) {
    'difficulty' => "CASE l.difficulty WHEN 'beginner' THEN 1 WHEN 'intermediate' THEN 2 WHEN 'advanced' THEN 3 END, l.order_num",
    'xp' => "l.xp_reward DESC, l.order_num",
    'title' => "l.title ASC",
    default => "l.order_num ASC"
};

$query = "
    SELECT l.*, up.completed, up.completed_at,
           (SELECT COUNT(*) FROM user_progress up2 WHERE up2.lesson_id = l.id AND up2.completed = 1) as completion_count
    FROM lessons l 
    LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = ?
    {$where_clause}
    ORDER BY {$sort_clause}
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$lessons = $stmt->fetchAll();

// Group lessons by difficulty for display
$lessons_by_difficulty = [
    'beginner' => [],
    'intermediate' => [],
    'advanced' => []
];

foreach ($lessons as $lesson) {
    $lessons_by_difficulty[$lesson['difficulty']][] = $lesson;
}

// Calculate statistics
$total_lessons = count($lessons);
$completed_lessons = count(array_filter($lessons, fn($l) => $l['completed']));
$completion_rate = $total_lessons > 0 ? ($completed_lessons / $total_lessons) * 100 : 0;
?>

<!-- Lessons Header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <div class="title-large">Rust Learning Path</div>
        <div class="text-secondary">Master Rust programming through the official Rust Book curriculum</div>
    </div>
    <div class="text-right">
        <div class="text-2xl font-bold text-orange-400"><?= $completed_lessons ?>/<?= $total_lessons ?></div>
        <div class="text-sm text-muted">Lessons Completed</div>
    </div>
</div>

<!-- Progress Overview -->
<div class="content-card mb-8">
    <div class="flex items-center justify-between mb-4">
        <div class="title-medium">Your Progress</div>
        <div class="text-orange-400 font-bold"><?= round($completion_rate, 1) ?>%</div>
    </div>
    
    <div class="w-full bg-gray-700 rounded-full h-3 mb-4">
        <div class="progress-bar h-3 rounded-full transition-all duration-500" style="width: <?= $completion_rate ?>%"></div>
    </div>
    
    <div class="grid grid-cols-3 gap-6">
        <?php foreach (['beginner', 'intermediate', 'advanced'] as $diff): 
            $diff_lessons = array_filter($lessons, fn($l) => $l['difficulty'] === $diff);
            $diff_completed = count(array_filter($diff_lessons, fn($l) => $l['completed']));
            $diff_total = count($diff_lessons);
            $diff_rate = $diff_total > 0 ? ($diff_completed / $diff_total) * 100 : 0;
        ?>
            <div class="text-center">
                <div class="text-lg font-bold text-<?= $diff === 'beginner' ? 'green' : ($diff === 'intermediate' ? 'blue' : 'orange') ?>-400">
                    <?= $diff_completed ?>/<?= $diff_total ?>
                </div>
                <div class="text-sm text-muted capitalize"><?= $diff ?></div>
                <div class="w-full bg-gray-700 rounded-full h-2 mt-2">
                    <div class="bg-<?= $diff === 'beginner' ? 'green' : ($diff === 'intermediate' ? 'blue' : 'orange') ?>-500 h-2 rounded-full" 
                         style="width: <?= $diff_rate ?>%"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Filters and Search -->
<div class="content-card mb-8">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <!-- Search -->
        <div class="flex-1 min-w-64">
            <form method="GET" class="flex items-center space-x-4">
                <input type="hidden" name="page" value="lessons">
                <input type="hidden" name="difficulty" value="<?= htmlspecialchars($difficulty_filter) ?>">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_by) ?>">
                
                <div class="relative flex-1">
                    <input type="text" name="search" placeholder="Search lessons..." 
                           value="<?= htmlspecialchars($search_query) ?>"
                           class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500 pl-10">
                    <i class="fas fa-search absolute left-3 top-4 text-gray-400"></i>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
        
        <!-- Filters -->
        <div class="flex items-center space-x-4">
            <!-- Difficulty Filter -->
            <div class="flex items-center space-x-2">
                <a href="?page=lessons&difficulty=all&search=<?= urlencode($search_query) ?>&sort=<?= $sort_by ?>" 
                   class="<?= $difficulty_filter === 'all' ? 'btn-primary' : 'btn-secondary' ?> text-sm px-3 py-2">
                    All
                </a>
                <a href="?page=lessons&difficulty=beginner&search=<?= urlencode($search_query) ?>&sort=<?= $sort_by ?>" 
                   class="<?= $difficulty_filter === 'beginner' ? 'btn-primary' : 'btn-secondary' ?> text-sm px-3 py-2">
                    Beginner
                </a>
                <a href="?page=lessons&difficulty=intermediate&search=<?= urlencode($search_query) ?>&sort=<?= $sort_by ?>" 
                   class="<?= $difficulty_filter === 'intermediate' ? 'btn-primary' : 'btn-secondary' ?> text-sm px-3 py-2">
                    Intermediate
                </a>
                <a href="?page=lessons&difficulty=advanced&search=<?= urlencode($search_query) ?>&sort=<?= $sort_by ?>" 
                   class="<?= $difficulty_filter === 'advanced' ? 'btn-primary' : 'btn-secondary' ?> text-sm px-3 py-2">
                    Advanced
                </a>
            </div>
            
            <!-- Sort Options -->
            <select onchange="window.location.href='?page=lessons&difficulty=<?= $difficulty_filter ?>&search=<?= urlencode($search_query) ?>&sort=' + this.value" 
                    class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-orange-500">
                <option value="order" <?= $sort_by === 'order' ? 'selected' : '' ?>>Default Order</option>
                <option value="difficulty" <?= $sort_by === 'difficulty' ? 'selected' : '' ?>>By Difficulty</option>
                <option value="xp" <?= $sort_by === 'xp' ? 'selected' : '' ?>>By XP Reward</option>
                <option value="title" <?= $sort_by === 'title' ? 'selected' : '' ?>>Alphabetical</option>
            </select>
        </div>
    </div>
</div>

<!-- Lessons Content -->
<?php if ($difficulty_filter === 'all'): ?>
    <!-- Show by difficulty groups -->
    <?php foreach ($lessons_by_difficulty as $difficulty => $difficulty_lessons): ?>
        <?php if (!empty($difficulty_lessons)): ?>
            <div class="mb-12">
                <div class="flex items-center mb-6">
                    <div class="w-1 h-8 bg-<?= $difficulty === 'beginner' ? 'green' : ($difficulty === 'intermediate' ? 'blue' : 'orange') ?>-500 rounded mr-4"></div>
                    <h2 class="text-3xl font-bold capitalize"><?= $difficulty ?> Level</h2>
                    <?php 
                    $completed_in_difficulty = count(array_filter($difficulty_lessons, fn($l) => $l['completed']));
                    $total_in_difficulty = count($difficulty_lessons);
                    ?>
                    <span class="ml-4 bg-gray-700 px-3 py-1 rounded text-sm">
                        <?= $completed_in_difficulty ?>/<?= $total_in_difficulty ?> Complete
                    </span>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <?php foreach ($difficulty_lessons as $lesson): ?>
                        <div class="content-card hover:border-<?= $difficulty === 'beginner' ? 'green' : ($difficulty === 'intermediate' ? 'blue' : 'orange') ?>-500/50 transition-all group">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-3">
                                        <div class="w-10 h-10 <?= $lesson['completed'] ? 'bg-green-500' : 'bg-gray-600' ?> rounded-lg flex items-center justify-center">
                                            <i class="fas fa-<?= $lesson['completed'] ? 'check' : 'play' ?> text-white"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-bold text-lg group-hover:text-orange-400 transition-colors">
                                                <?= htmlspecialchars($lesson['title']) ?>
                                            </h3>
                                        </div>
                                    </div>
                                    
                                    <p class="text-gray-300 text-sm mb-4 line-clamp-2">
                                        <?= htmlspecialchars($lesson['description']) ?>
                                    </p>
                                    
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4 text-sm">
                                            <span class="text-yellow-400">
                                                <i class="fas fa-star"></i> <?= $lesson['xp_reward'] ?> XP
                                            </span>
                                            <span class="text-gray-500">
                                                <i class="fas fa-users"></i> <?= $lesson['completion_count'] ?> completed
                                            </span>
                                            <?php if ($lesson['completed']): ?>
                                                <span class="text-green-400">
                                                    <i class="fas fa-check-circle"></i> Done
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <a href="index.php?page=lesson&id=<?= $lesson['id'] ?>" 
                                           class="<?= $lesson['completed'] ? 'btn-secondary' : 'btn-primary' ?> text-sm px-4 py-2 opacity-0 group-hover:opacity-100 transition-all">
                                            <?= $lesson['completed'] ? 'Review' : 'Start' ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php else: ?>
    <!-- Show filtered results -->
    <div class="mb-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold">
                <?php if (!empty($search_query)): ?>
                    Search Results for "<?= htmlspecialchars($search_query) ?>"
                <?php else: ?>
                    <?= ucfirst($difficulty_filter) ?> Lessons
                <?php endif; ?>
            </h2>
            <span class="text-sm text-muted"><?= count($lessons) ?> lessons found</span>
        </div>
        
        <?php if (!empty($lessons)): ?>
            <div class="grid md:grid-cols-2 gap-6">
                <?php foreach ($lessons as $lesson): ?>
                    <div class="content-card hover:border-orange-500/50 transition-all group">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-3">
                                    <div class="w-10 h-10 <?= $lesson['completed'] ? 'bg-green-500' : 'bg-gray-600' ?> rounded-lg flex items-center justify-center">
                                        <i class="fas fa-<?= $lesson['completed'] ? 'check' : 'play' ?> text-white"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-bold text-lg group-hover:text-orange-400 transition-colors">
                                            <?= htmlspecialchars($lesson['title']) ?>
                                        </h3>
                                        <div class="flex items-center space-x-2 mt-1">
                                            <span class="text-xs bg-<?= $lesson['difficulty'] === 'beginner' ? 'green' : ($lesson['difficulty'] === 'intermediate' ? 'blue' : 'orange') ?>-500/20 text-<?= $lesson['difficulty'] === 'beginner' ? 'green' : ($lesson['difficulty'] === 'intermediate' ? 'blue' : 'orange') ?>-400 px-2 py-1 rounded-full">
                                                <?= ucfirst($lesson['difficulty']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <p class="text-gray-300 text-sm mb-4">
                                    <?= htmlspecialchars($lesson['description']) ?>
                                </p>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4 text-sm">
                                        <span class="text-yellow-400">
                                            <i class="fas fa-star"></i> <?= $lesson['xp_reward'] ?> XP
                                        </span>
                                        <span class="text-gray-500">
                                            <i class="fas fa-users"></i> <?= $lesson['completion_count'] ?> completed
                                        </span>
                                        <?php if ($lesson['completed']): ?>
                                            <span class="text-green-400">
                                                <i class="fas fa-check-circle"></i> Done
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <a href="index.php?page=lesson&id=<?= $lesson['id'] ?>" 
                                       class="<?= $lesson['completed'] ? 'btn-secondary' : 'btn-primary' ?> text-sm px-4 py-2 opacity-0 group-hover:opacity-100 transition-all">
                                        <?= $lesson['completed'] ? 'Review' : 'Start' ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="content-card text-center py-12">
                <i class="fas fa-search text-6xl text-gray-600 mb-4"></i>
                <div class="title-medium mb-4">No Lessons Found</div>
                <div class="text-secondary mb-6">
                    <?php if (!empty($search_query)): ?>
                        No lessons match your search criteria. Try different keywords.
                    <?php else: ?>
                        No lessons available for this difficulty level yet.
                    <?php endif; ?>
                </div>
                <a href="index.php?page=lessons" class="btn-primary">
                    View All Lessons
                </a>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (empty($lessons) && empty($search_query) && $difficulty_filter === 'all'): ?>
    <div class="content-card text-center py-12">
        <i class="fas fa-book text-6xl text-gray-600 mb-4"></i>
        <div class="title-medium mb-4">No Lessons Available Yet</div>
        <div class="text-secondary mb-6">Lessons are being prepared for your Rust learning journey!</div>
        <a href="index.php?page=dashboard" class="btn-primary">
            Back to Dashboard
        </a>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add keyboard shortcut for search
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'k') {
            e.preventDefault();
            document.querySelector('input[name="search"]').focus();
        }
    });
    
    // Auto-submit search after typing stops
    let searchTimeout;
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3 || this.value.length === 0) {
                    this.form.submit();
                }
            }, 500);
        });
    }
    
    // Add progress animation
    const progressBars = document.querySelectorAll('.progress-bar, .bg-green-500, .bg-blue-500, .bg-orange-500');
    progressBars.forEach(bar => {
        const width = bar.style.width || bar.getAttribute('style')?.match(/width:\s*(\d+%)/)?.[1];
        if (width) {
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = width;
            }, 100);
        }
    });
});
</script>