<?php
$page_title = "Dashboard";
$user = get_user_by_id($_SESSION["user_id"]);
$languages = get_languages();
$lessons = get_user_progress($_SESSION["user_id"]);
$streak = $user["current_streak"] ?? 0;

// Get user rank
$stmt = $pdo->prepare("SELECT id, username, xp,
    (SELECT COUNT(*) + 1 FROM users u2 WHERE u2.xp > u.xp) as user_rank_val
        FROM users u WHERE u.id = ?");
$stmt->execute([$_SESSION["user_id"]]);
$user_rank = $stmt->fetch()["user_rank_val"] ?? 0;

// Recent activity
$stmt = $pdo->prepare("
    SELECT up.*, l.title as lesson_title, l.language_id
    FROM user_progress up
    JOIN lessons l ON up.lesson_id = l.id
    WHERE up.user_id = ? AND up.completed = 1
    ORDER BY up.completed_at DESC
    LIMIT 5
");
$stmt->execute([$_SESSION["user_id"]]);
$recent_completions = $stmt->fetchAll();

// Unearned badges available
$stmt = $pdo->prepare("
    SELECT b.* FROM badges b
    WHERE b.id NOT IN (
        SELECT badge_id FROM user_badges WHERE user_id = ?
    )
    ORDER BY b.requirement_value ASC
    LIMIT 3
");
$stmt->execute([$_SESSION["user_id"]]);
$next_badges = $stmt->fetchAll();

// Get unread notification count
$unread_notifications = get_unread_notification_count($_SESSION["user_id"]);

$xp_for_level = get_xp_for_next_level($user["xp"]);
$current_level_xp = ($user["level"] - 1) * XP_PER_LEVEL;
$level_progress =
    $xp_for_level > $current_level_xp
        ? (($user["xp"] - $current_level_xp) /
                ($xp_for_level - $current_level_xp)) *
            100
        : 0;
$level_progress = max(0, min(100, $level_progress));

$completed_count = count(array_filter($lessons, fn($l) => $l["completed"]));
$total_count = count($lessons);
?>
<div style="animation: fade-in 0.5s ease-out;">
    <!-- Welcome / Stats Header -->
    <div class="tw-card mb-6" style="background: linear-gradient(135deg, rgba(145,71,255,0.1), rgba(233,25,123,0.05)); border-color: rgba(145,71,255,0.2);">
        <div class="tw-card-body">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <?= get_avatar_html(
                            $user,
                            48,
                            $user["is_online"] ?? false ? "online" : "",
                        ) ?>
                        <div>
                            <h1 class="text-2xl font-bold">Welcome back, <?= htmlspecialchars(
                                $user["username"],
                            ) ?>! 👋</h1>
                            <p class="text-twitch-muted text-sm mt-1">
                                <?php
                                $hour = (int) date("G");
                                if ($hour < 12) {
                                    echo "Good morning!";
                                } elseif ($hour < 17) {
                                    echo "Good afternoon!";
                                } else {
                                    echo "Good evening!";
                                }
                                ?> Ready to level up?
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="text-2xl font-bold gradient-text">Level <?= $user[
                            "level"
                        ] ?></div>
                        <div class="text-xs text-twitch-muted"><?= number_format(
                            $user["xp"],
                        ) ?> XP</div>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-4 mt-4 text-sm">
                <span class="flex items-center gap-1"><i class="fas fa-fire" style="color:#FF6B35;"></i> <?= $user[
                    "current_streak"
                ] ?? 0 ?> day streak</span>
                <span class="flex items-center gap-1"><i class="fas fa-ranking-star" style="color:#A970FF;"></i> Rank #<?= $user_rank ?></span>
                <span class="flex items-center gap-1"><i class="fas fa-layer-group" style="color:#00D95A;"></i> Level <?= $user[
                    "level"
                ] ?></span>
            </div>
        </div>
    </div>

    <!-- Quick Actions Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <a href="index.php?page=lessons" class="tw-card tw-card-body" style="text-decoration:none; display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(145,71,255,0.15); display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-graduation-cap" style="color:#9147FF; font-size:18px;"></i>
            </div>
            <div>
                <div class="font-bold text-sm">Learn</div>
                <div class="text-xs text-twitch-muted"><?= $completed_count ?>/<?= $total_count ?> lessons</div>
            </div>
        </a>

        <a href="index.php?page=daily-challenge" class="tw-card tw-card-body" style="text-decoration:none; display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(255,107,53,0.15); display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-calendar-day" style="color:#FF6B35; font-size:18px;"></i>
            </div>
            <div>
                <div class="font-bold text-sm">Daily</div>
                <div class="text-xs text-twitch-muted">+<?= XP_DAILY_CHALLENGE ?> XP</div>
            </div>
        </a>

        <a href="index.php?page=battle-royale" class="tw-card tw-card-body" style="text-decoration:none; display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(233,25,123,0.15); display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-crosshairs" style="color:#E9197B; font-size:18px;"></i>
            </div>
            <div>
                <div class="font-bold text-sm">Battle</div>
                <div class="text-xs text-twitch-muted">Compete now</div>
            </div>
        </a>

        <a href="index.php?page=ai-tutor" class="tw-card tw-card-body" style="text-decoration:none; display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(0,217,90,0.15); display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-robot" style="color:#00D95A; font-size:18px;"></i>
            </div>
            <div>
                <div class="font-bold text-sm">AI Tutor</div>
                <div class="text-xs text-twitch-muted">Get help</div>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- XP Progress -->
        <div class="tw-card">
            <div class="tw-card-header">
                <h2 class="font-bold flex items-center gap-2">
                    <i class="fas fa-chart-line" style="color:#9147FF;"></i>
                    Progress
                </h2>
            </div>
            <div class="tw-card-body">
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-twitch-muted">Level Progress</span>
                        <span class="font-bold"><?= round(
                            $level_progress,
                        ) ?>%</span>
                    </div>
                    <div class="xp-bar-container" style="height: 10px;">
                        <div class="xp-bar" style="width: <?= round(
                            $level_progress,
                        ) ?>%;"></div>
                    </div>
                    <div class="flex justify-between text-xs text-twitch-muted mt-1">
                        <span>Level <?= $user["level"] ?></span>
                        <span>Level <?= $user["level"] + 1 ?></span>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-twitch-muted">Total XP</span>
                        <span class="font-bold"><?= number_format(
                            $user["xp"] ?? 0,
                        ) ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-twitch-muted">Lessons Completed</span>
                        <span class="font-bold"><?= $completed_count ?>/<?= $total_count ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-twitch-muted">Current Streak</span>
                        <span class="font-bold" style="color:#FF6B35;"><?= $user[
                            "current_streak"
                        ] ?? 0 ?> days</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="tw-card">
            <div class="tw-card-header">
                <h2 class="font-bold flex items-center gap-2">
                    <i class="fas fa-clock-rotate" style="color:#A970FF;"></i>
                    Recent Activity
                </h2>
            </div>
            <div class="tw-card-body">
                <?php if (!empty($recent_completions)): ?>
                    <div class="space-y-3">
                        <?php foreach ($recent_completions as $rc): ?>
                            <div class="flex items-center gap-3">
                                <div style="width:32px; height:32px; border-radius:8px; background:rgba(0,217,90,0.15); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <i class="fas fa-check" style="color:#00D95A; font-size:12px;"></i>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <div class="text-sm font-medium truncate"><?= htmlspecialchars(
                                        $rc["lesson_title"],
                                    ) ?></div>
                                    <div class="text-xs text-twitch-muted"><?= time_ago(
                                        $rc["completed_at"],
                                    ) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding:20px;">
                        <i class="fas fa-book-open" style="font-size:32px; color:#2D2D35; margin-bottom:8px;"></i>
                        <p class="text-sm text-twitch-muted">Complete your first lesson to see activity here!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Next Badges -->
        <div class="tw-card">
            <div class="tw-card-header">
                <h2 class="font-bold flex items-center gap-2">
                    <i class="fas fa-award" style="color:#FFD700;"></i>
                    Next Rewards
                </h2>
            </div>
            <div class="tw-card-body">
                <?php if (!empty($next_badges)): ?>
                    <div class="space-y-3">
                        <?php foreach ($next_badges as $badge): ?>
                            <div class="flex items-center gap-3">
                                <div style="width:36px; height:36px; border-radius:50%; background:rgba(255,215,0,0.1); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <i class="fas fa-lock" style="color:#ADADB8; font-size:12px;"></i>
                                </div>
                                <div style="flex:1;">
                                    <div class="text-sm font-medium"><?= htmlspecialchars(
                                        $badge["name"],
                                    ) ?></div>
                                    <div class="text-xs text-twitch-muted"><?= htmlspecialchars(
                                        $badge["description"],
                                    ) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding:20px;">
                        <i class="fas fa-trophy" style="font-size:32px; color:#2D2D35; margin-bottom:8px;"></i>
                        <p class="text-sm text-twitch-muted">Keep learning to unlock badges!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
