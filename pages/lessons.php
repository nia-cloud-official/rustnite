<?php
$page_title = "Lessons";
$languages = get_languages();
$selected_lang = (int) ($_GET["language"] ?? 0);
$difficulty_filter = $_GET["difficulty"] ?? "all";
$search_query = $_GET["search"] ?? "";
$user_progress = get_user_progress($_SESSION["user_id"]);

// Get lessons with language info
$sql = "
    SELECT l.*, lang.name as language_name, lang.slug as language_slug,
           lang.color as language_color, lang.icon as language_icon,
           up.completed, up.completed_at
    FROM lessons l
    JOIN languages lang ON l.language_id = lang.id
    LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = ?
    WHERE 1=1
";
$params = [$_SESSION["user_id"]];

if ($selected_lang > 0) {
    $sql .= " AND l.language_id = ?";
    $params[] = $selected_lang;
}

if ($difficulty_filter !== "all") {
    $sql .= " AND l.difficulty = ?";
    $params[] = $difficulty_filter;
}

if (!empty($search_query)) {
    $sql .= " AND (l.title LIKE ? OR l.description LIKE ?)";
    $params[] = "%{$search_query}%";
    $params[] = "%{$search_query}%";
}

$sql .= " ORDER BY l.language_id, l.order_num ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lessons = $stmt->fetchAll();

$completed_count = count(array_filter($lessons, fn($l) => $l["completed"]));
$total_count = count($lessons);

// Build filter query string for reuse
$filter_query = "";
if ($difficulty_filter !== "all") {
    $filter_query .= "&difficulty=" . urlencode($difficulty_filter);
}
if (!empty($search_query)) {
    $filter_query .= "&search=" . urlencode($search_query);
}
?>
<div style="animation: fade-in 0.5s ease-out;">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-3">
                <i class="fas fa-graduation-cap" style="color: #9147FF;"></i>
                Learning Path
            </h1>
            <p class="text-twitch-muted mt-1">Master multiple programming languages through interactive challenges.</p>
        </div>
        <div class="flex items-center gap-2 text-sm text-twitch-muted">
            <span><i class="fas fa-check-circle" style="color:#00D95A;"></i> <?= $completed_count ?>/<?= $total_count ?> completed</span>
        </div>
    </div>

    <!-- Language Tabs (Twitch-style category bar) -->
    <div class="tw-card tw-card-body mb-6" style="padding:12px;">
        <div class="flex items-center gap-2 overflow-x-auto" style="scrollbar-width:none;">
            <a href="?page=lessons<?= $filter_query ?>"
               class="tw-btn <?= $selected_lang === 0
                   ? "tw-btn-primary"
                   : "tw-btn-ghost" ?> tw-btn-sm">
                <i class="fas fa-globe"></i>
                All
            </a>
            <?php foreach ($languages as $lang): ?>
                <a href="?page=lessons&language=<?= $lang["id"] .
                    $filter_query ?>"
                   class="tw-btn <?= $selected_lang === $lang["id"]
                       ? "tw-btn-primary"
                       : "tw-btn-ghost" ?> tw-btn-sm"
                   style="<?= $selected_lang === $lang["id"]
                       ? "background:" . $lang["color"] . ";"
                       : "" ?>">
                    <i class="<?= $lang["icon"] ?>"></i>
                    <?= $lang["name"] ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Filters + Search -->
    <div class="flex items-center gap-4 mb-6 flex-wrap">
        <div class="tw-search-container">
            <i class="fas fa-search tw-search-icon"></i>
            <input type="text" class="tw-search" placeholder="Search lessons..." value="<?= htmlspecialchars(
                $search_query,
            ) ?>" onkeypress="if(event.key==='Enter') window.location='?page=lessons&search='+encodeURIComponent(this.value)+'&language=<?= $selected_lang ?>'">
        </div>

        <div class="flex items-center gap-2">
            <?php foreach (
                ["all", "beginner", "intermediate", "advanced"]
                as $diff
            ): ?>
                <a href="?page=lessons&language=<?= $selected_lang ?>&difficulty=<?= $diff .
    (!empty($search_query) ? "&search=" . urlencode($search_query) : "") ?>"
                   class="tw-btn <?= $difficulty_filter === $diff
                       ? "tw-btn-primary"
                       : "tw-btn-ghost" ?> tw-btn-sm">
                    <?= ucfirst($diff) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="ml-auto">
            <a href="?page=lesson&generate=1&language=<?= $selected_lang ?:
                1 ?>" class="tw-btn tw-btn-primary tw-btn-sm" style="background: linear-gradient(135deg, #9147FF, #A970FF);">
                <i class="fas fa-magic"></i>
                Generate AI Lesson
            </a>
        </div>
    </div>

    <!-- Lesson Grid -->
    <?php if (!empty($lessons)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($lessons as $lesson): ?>
                <a href="index.php?page=lesson&id=<?= $lesson[
                    "id"
                ] ?>" class="tw-card" style="text-decoration:none; display:block; animation: slide-up 0.5s ease-out;">
                    <div class="stream-thumb" style="background: linear-gradient(135deg, #1F1F23, #2D2D35); padding:24px; display:flex; align-items:center; justify-content:center; flex-direction:column; position:relative;">
                        <i class="<?= $lesson[
                            "language_icon"
                        ] ?>" style="font-size:36px; color:<?= $lesson[
    "language_color"
] ?>; margin-bottom:8px;"></i>
                        <span class="text-sm font-medium"><?= htmlspecialchars(
                            $lesson["title"],
                        ) ?></span>

                        <?php if ($lesson["completed"]): ?>
                            <div style="position:absolute; top:12px; right:12px; background:#00D95A; color:white; border-radius:50%; width:24px; height:24px; display:flex; align-items:center; justify-content:center; font-size:12px;">
                                <i class="fas fa-check"></i>
                            </div>
                        <?php endif; ?>

                        <span class="viewer-count" style="bottom:8px; right:8px; font-size:10px; background:<?= $lesson[
                            "language_color"
                        ] ?>30;">
                            <i class="<?= $lesson[
                                "language_icon"
                            ] ?>" style="font-size:10px;"></i>
                            <?= $lesson["language_name"] ?>
                        </span>
                    </div>
                    <div class="tw-card-body" style="padding:16px;">
                        <div class="flex items-center gap-2 mb-2">
                            <?= get_difficulty_badge($lesson["difficulty"]) ?>
                            <span class="text-xs" style="color:#A970FF;"><i class="fas fa-star"></i> <?= $lesson[
                                "xp_reward"
                            ] ?> XP</span>
                        </div>
                        <p class="text-xs text-twitch-muted line-clamp-2"><?= htmlspecialchars(
                            substr($lesson["description"], 0, 100),
                        ) ?>...</p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="tw-card tw-card-body" style="text-align:center; padding:60px 20px;">
            <i class="fas fa-book-open" style="font-size:64px; color:#2D2D35; margin-bottom:16px;"></i>
            <h3 class="text-xl font-bold mb-2">No Lessons Found</h3>
            <p class="text-twitch-muted mb-6">Try a different language or difficulty filter, or generate a new lesson with AI!</p>
            <div class="flex items-center justify-center gap-3">
                <a href="?page=lessons" class="tw-btn tw-btn-ghost">
                    <i class="fas fa-undo"></i>
                    Reset Filters
                </a>
                <a href="?page=lesson&generate=1&language=<?= $selected_lang ?:
                    1 ?>" class="tw-btn tw-btn-primary" style="background: linear-gradient(135deg, #9147FF, #A970FF);">
                    <i class="fas fa-magic"></i>
                    Generate AI Lesson
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
