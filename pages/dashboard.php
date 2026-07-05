<?php
$page_title = "Dashboard";
$user = get_user_by_id($_SESSION["user_id"]);
$languages = get_languages();
$active_matches = get_active_br_matches();
$games = get_mini_games();
$chats = get_ai_chats($_SESSION["user_id"]);
$notifications = get_user_notifications($_SESSION["user_id"], 5);

// Get total stats
$stmt = $pdo->prepare("
    SELECT
        COUNT(DISTINCT CASE WHEN up.completed = 1 THEN up.lesson_id END) as completed_lessons,
        COUNT(DISTINCT l.id) as total_lessons,
        COUNT(DISTINCT l.language_id) as languages_used,
        COUNT(DISTINCT CASE WHEN up.completed = 1 THEN l.language_id END) as languages_completed
    FROM lessons l
    LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = ?
");
$stmt->execute([$_SESSION["user_id"]]);
$stats = $stmt->fetch();

// Get per-language progress
$lang_progress = [];
foreach ($languages as $lang) {
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN up.completed = 1 THEN 1 ELSE 0 END) as completed
        FROM lessons l
        LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = ?
        WHERE l.language_id = ?
    ");
    $stmt->execute([$_SESSION["user_id"], $lang["id"]]);
    $lang_progress[$lang["id"]] = $stmt->fetch();
}

// User rank
$stmt = $pdo->prepare(
    "SELECT COUNT(*) + 1 as user_rank FROM users WHERE xp > ?",
);
$stmt->execute([$user["xp"]]);
$user_rank = $stmt->fetch()["user_rank"];
?>
<div style="animation: fade-in 0.5s ease-out;">
    <!-- Welcome + Quick Stats -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
        <!-- Welcome Card -->
        <div class="tw-card tw-card-body lg:col-span-2" style="background: linear-gradient(135deg, rgba(145,71,255,0.1), rgba(233,25,123,0.05)); border-color: rgba(145,71,255,0.2);">
            <div class="flex items-center gap-4">
                <div class="tw-avatar online" style="width:56px; height:56px; font-size:24px;"><?= get_avatar_letter(
                    $user["username"],
                ) ?></div>
                <div>
                    <h1 class="text-2xl font-bold">Welcome back, <?= htmlspecialchars(
                        $user["username"],
                    ) ?>!</h1>
                    <p class="text-twitch-muted text-sm">
                        <?php
                        $hour = date("H");
                        if ($hour < 12) {
                            echo "Good morning! ☀️";
                        } elseif ($hour < 18) {
                            echo "Good afternoon! 🌤️";
                        } else {
                            echo "Good evening! 🌙";
                        }
                        ?>
                        Ready to write some code?
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-4 mt-4 text-sm">
                <span class="flex items-center gap-1"><i class="fas fa-fire" style="color:#FF6B35;"></i> <?= $user[
                    "current_streak"
                ] ?> day streak</span>
                <span class="flex items-center gap-1"><i class="fas fa-ranking-star" style="color:#A970FF;"></i> Rank #<?= $user_rank ?></span>
                <span class="flex items-center gap-1"><i class="fas fa-layer-group" style="color:#00D95A;"></i> Level <?= $user[
                    "level"
                ] ?></span>
            </div>
        </div>

        <!-- Language Progress -->
        <div class="tw-card tw-card-body">
            <div class="text-sm font-bold text-twitch-muted uppercase tracking-wider mb-3">Languages</div>
            <div class="space-y-2">
                <?php foreach (array_slice($languages, 0, 4) as $lang):

                    $lp = $lang_progress[$lang["id"]] ?? [
                        "total" => 0,
                        "completed" => 0,
                    ];
                    $pct =
                        $lp["total"] > 0
                            ? round(($lp["completed"] / $lp["total"]) * 100)
                            : 0;
                    ?>
                    <div class="flex items-center gap-2">
                        <i class="<?= $lang["icon"] ?>" style="color:<?= $lang[
    "color"
] ?>; width:16px; text-align:center;"></i>
                        <span class="text-xs font-medium flex-1"><?= $lang[
                            "name"
                        ] ?></span>
                        <span class="text-xs text-twitch-muted"><?= $lp[
                            "completed"
                        ] ?>/<?= $lp["total"] ?></span>
                        <div class="xp-bar-container" style="width:60px;">
                            <div class="xp-bar" style="width:<?= $pct ?>%;"></div>
                        </div>
                    </div>
                <?php
                endforeach; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="tw-card tw-card-body">
            <div class="text-sm font-bold text-twitch-muted uppercase tracking-wider mb-3">Quick Actions</div>
            <div class="grid grid-cols-2 gap-2">
                <a href="index.php?page=lessons" class="tw-btn tw-btn-secondary tw-btn-sm">
                    <i class="fas fa-graduation-cap"></i> Learn
                </a>
                <a href="index.php?page=battle-royale" class="tw-btn tw-btn-secondary tw-btn-sm">
                    <i class="fas fa-crosshairs"></i> Battle
                </a>
                <a href="index.php?page=mini-games" class="tw-btn tw-btn-secondary tw-btn-sm">
                    <i class="fas fa-gamepad"></i> Games
                </a>
                <a href="index.php?page=ai-tutor" class="tw-btn tw-btn-secondary tw-btn-sm">
                    <i class="fas fa-robot"></i> AI Tutor
                </a>
            </div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Learning Progress -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Stats Row -->
            <div class="grid grid-cols-4 gap-4">
                <div class="tw-card tw-card-body" style="text-align:center;">
                    <div class="text-2xl font-black" style="color:#9147FF;"><?= $stats[
                        "completed_lessons"
                    ] ?></div>
                    <div class="text-xs text-twitch-muted">Lessons Done</div>
                </div>
                <div class="tw-card tw-card-body" style="text-align:center;">
                    <div class="text-2xl font-black" style="color:#00D95A;"><?= number_format(
                        $user["total_xp_earned"] ?: $user["xp"],
                    ) ?></div>
                    <div class="text-xs text-twitch-muted">Total XP</div>
                </div>
                <div class="tw-card tw-card-body" style="text-align:center;">
                    <div class="text-2xl font-black" style="color:#A970FF;"><?= $stats[
                        "languages_completed"
                    ] ?:
                        $stats["languages_used"] ?:
                        1 ?></div>
                    <div class="text-xs text-twitch-muted">Languages</div>
                </div>
                <div class="tw-card tw-card-body" style="text-align:center;">
                    <div class="text-2xl font-black" style="color:#FF6B35;"><?= $user[
                        "level"
                    ] ?></div>
                    <div class="text-xs text-twitch-muted">Level</div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="tw-card">
                <div class="tw-card-header">
                    <h3 class="font-bold"><i class="fas fa-clock-rotate mr-2" style="color:#9147FF;"></i> Recent Activity</h3>
                    <a href="index.php?page=leaderboard" class="text-xs text-twitch-purple hover:underline">View All</a>
                </div>
                <div class="tw-card-body">
                    <?php if (!empty($notifications)): ?>
                        <div class="space-y-3">
                            <?php foreach ($notifications as $notif): ?>
                                <div class="flex items-start gap-3 p-3 rounded-lg <?= $notif[
                                    "read_at"
                                ]
                                    ? ""
                                    : "bg-twitch-purple/5" ?>" style="border-left: 3px solid <?= $notif[
    "read_at"
]
    ? "#3A3A45"
    : "#9147FF" ?>;">
                                    <div>
                                        <?php
                                        $notif_icons = [
                                            "badge_earned" =>
                                                '<i class="fas fa-medal" style="color:#FFD700;"></i>',
                                            "level_up" =>
                                                '<i class="fas fa-arrow-up" style="color:#00D95A;"></i>',
                                            "lesson_completed" =>
                                                '<i class="fas fa-check-circle" style="color:#9147FF;"></i>',
                                            "br_event" =>
                                                '<i class="fas fa-crosshairs" style="color:#E9197B;"></i>',
                                            "streak" =>
                                                '<i class="fas fa-fire" style="color:#FF6B35;"></i>',
                                        ];
                                        echo $notif_icons[$notif["type"]] ??
                                            '<i class="fas fa-bell" style="color:#ADADB8;"></i>';
                                        ?>
                                    </div>
                                    <div style="flex:1;">
                                        <div class="text-sm font-medium"><?= htmlspecialchars(
                                            $notif["title"],
                                        ) ?></div>
                                        <div class="text-xs text-twitch-muted"><?= htmlspecialchars(
                                            $notif["message"],
                                        ) ?></div>
                                        <div class="text-xs text-twitch-muted mt-1"><?= time_ago(
                                            $notif["created_at"],
                                        ) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:20px; color:#ADADB8;">
                            <i class="fas fa-inbox" style="font-size:32px; margin-bottom:8px; color:#2D2D35;"></i>
                            <p class="text-sm">No recent activity. Start learning to see your progress!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Continue Learning (Next Lessons) -->
            <div class="tw-card">
                <div class="tw-card-header">
                    <h3 class="font-bold"><i class="fas fa-graduation-cap mr-2" style="color:#9147FF;"></i> Continue Learning</h3>
                    <a href="index.php?page=lessons" class="text-xs text-twitch-purple hover:underline">All Lessons</a>
                </div>
                <div class="tw-card-body">
                    <?php
                    // Find next uncompleted lesson
                    $stmt = $pdo->prepare("
                        SELECT l.*, lang.name as lang_name, lang.color as lang_color, lang.icon as lang_icon
                        FROM lessons l
                        JOIN languages lang ON l.language_id = lang.id
                        LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = ?
                        WHERE (up.completed IS NULL OR up.completed = 0)
                        ORDER BY l.language_id, l.order_num
                        LIMIT 3
                    ");
                    $stmt->execute([$_SESSION["user_id"]]);
                    $next_lessons = $stmt->fetchAll();
                    ?>

                    <?php if (!empty($next_lessons)): ?>
                        <div class="space-y-3">
                            <?php foreach ($next_lessons as $lesson): ?>
                                <a href="index.php?page=lesson&id=<?= $lesson[
                                    "id"
                                ] ?>" class="flex items-center gap-4 p-4 rounded-lg hover:bg-twitch-medium transition-all" style="text-decoration:none; display:flex;">
                                    <div style="width:40px; height:40px; border-radius:10px; background:<?= $lesson[
                                        "lang_color"
                                    ] ?>20; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                        <i class="<?= $lesson[
                                            "lang_icon"
                                        ] ?>" style="color:<?= $lesson[
    "lang_color"
] ?>;"></i>
                                    </div>
                                    <div style="flex:1;">
                                        <div class="text-sm font-medium text-twitch-text"><?= htmlspecialchars(
                                            $lesson["title"],
                                        ) ?></div>
                                        <div class="text-xs text-twitch-muted"><?= $lesson[
                                            "lang_name"
                                        ] ?> · <?= ucfirst(
     $lesson["difficulty"],
 ) ?> · +<?= $lesson["xp_reward"] ?> XP</div>
                                    </div>
                                    <i class="fas fa-chevron-right" style="color:#ADADB8;"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:20px; color:#ADADB8;">
                            <i class="fas fa-check-circle" style="font-size:32px; margin-bottom:8px; color:#00D95A;"></i>
                            <p class="text-sm font-bold">All lessons completed! 🎉</p>

                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="space-y-6">
            <!-- Daily Challenge -->
            <div class="tw-card">
                <div class="tw-card-header">
                    <h3 class="font-bold"><i class="fas fa-calendar-day mr-2" style="color:#FF6B35;"></i> Daily Challenge</h3>
                </div>
                <div class="tw-card-body">
                    <?php $daily = get_daily_challenge(); ?>
                    <?php if ($daily): ?>
                        <div class="text-center mb-4">
                            <div class="text-3xl mb-2">🎯</div>
                            <div class="font-bold"><?= htmlspecialchars(
                                $daily["title"],
                            ) ?></div>
                            <div class="text-xs text-twitch-muted mt-1"><?= $daily[
                                "language_name"
                            ] ?> · <?= ucfirst($daily["difficulty"]) ?></div>
                        </div>
                        <a href="index.php?page=lesson&id=<?= $daily[
                            "id"
                        ] ?>" class="tw-btn tw-btn-primary tw-btn-sm tw-btn-block">
                            <i class="fas fa-play"></i> Solve +<?= $daily[
                                "xp_reward"
                            ] ?> XP
                        </a>
                    <?php else: ?>
                        <div class="text-center">
                            <div class="text-3xl mb-2">📅</div>
                            <div class="text-sm text-twitch-muted">Complete lessons to get daily challenges!</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Active Battles -->
            <div class="tw-card">
                <div class="tw-card-header">
                    <h3 class="font-bold"><i class="fas fa-crosshairs mr-2" style="color:#E9197B;"></i> Live Battles</h3>
                    <a href="index.php?page=battle-royale" class="text-xs text-twitch-purple hover:underline">Join</a>
                </div>
                <div class="tw-card-body">
                    <?php if (!empty($active_matches)): ?>
                        <div class="space-y-3">
                            <?php foreach (
                                array_slice($active_matches, 0, 3)
                                as $match
                            ): ?>
                                <a href="index.php?page=battle-royale" class="flex items-center gap-3 p-2 rounded hover:bg-twitch-medium transition-all" style="text-decoration:none; display:flex;">
                                    <span class="live-dot"></span>
                                    <div style="flex:1;">
                                        <div class="text-sm font-medium text-twitch-text"><?= htmlspecialchars(
                                            $match["title"],
                                        ) ?></div>
                                        <div class="text-xs text-twitch-muted"><?= $match[
                                            "player_count"
                                        ] ?>/<?= $match[
    "max_players"
] ?> players</div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:12px; color:#ADADB8; font-size:13px;">
                            No active battles right now
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Badges Showcase -->
            <div class="tw-card">
                <div class="tw-card-header">
                    <h3 class="font-bold"><i class="fas fa-medal mr-2" style="color:#FFD700;"></i> Recent Badges</h3>
                    <a href="index.php?page=profile" class="text-xs text-twitch-purple hover:underline">All</a>
                </div>
                <div class="tw-card-body">
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT b.*, ub.earned_at
                        FROM user_badges ub
                        JOIN badges b ON ub.badge_id = b.id
                        WHERE ub.user_id = ?
                        ORDER BY ub.earned_at DESC
                        LIMIT 6
                    ");
                    $stmt->execute([$_SESSION["user_id"]]);
                    $earned_badges = $stmt->fetchAll();
                    ?>

                    <?php if (!empty($earned_badges)): ?>
                        <div class="grid grid-cols-3 gap-3">
                            <?php foreach ($earned_badges as $badge): ?>
                                <div class="text-center" title="<?= htmlspecialchars(
                                    $badge["description"],
                                ) ?>">
                                    <div style="width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg, #9147FF, #772CE8); display:flex; align-items:center; justify-content:center; margin:0 auto 4px;">
                                        <i class="<?= $badge[
                                            "icon"
                                        ] ?>" style="color:white; font-size:16px;"></i>
                                    </div>
                                    <div style="font-size:9px; color:#ADADB8;"><?= htmlspecialchars(
                                        substr($badge["name"], 0, 12),
                                    ) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:12px; color:#ADADB8; font-size:13px;">
                            Complete lessons to earn badges!
                        </div>
                        <a href="index.php?page=lessons" class="tw-btn tw-btn-primary tw-btn-sm tw-btn-block mt-3">
                            <i class="fas fa-graduation-cap"></i> Start Learning
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
