<?php
$page_title = "Profile";
$user = get_user_by_id($_SESSION["user_id"]);
$progress = get_user_progress($_SESSION["user_id"]);
$completed_lessons = array_filter(
    $progress,
    fn($lesson) => $lesson["completed"],
);
$languages = get_languages();

// Handle profile update
$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {
    $bio = sanitize($_POST["bio"] ?? "");
    $github = sanitize($_POST["github_username"] ?? "");
    $twitter = sanitize($_POST["twitter_username"] ?? "");
    $website = sanitize($_POST["website"] ?? "");
    $location = sanitize($_POST["location"] ?? "");
    $public = isset($_POST["public_profile"]) ? 1 : 0;
    $preferred_lang = (int) ($_POST["preferred_language"] ?? 1);

    $stmt = $pdo->prepare(
        "UPDATE users SET bio=?, github_username=?, twitter_username=?, website=?, location=?, public_profile=?, preferred_language=? WHERE id=?",
    );

    if (
        $stmt->execute([
            $bio,
            $github,
            $twitter,
            $website,
            $location,
            $public,
            $preferred_lang,
            $_SESSION["user_id"],
        ])
    ) {
        $message = "Profile updated successfully!";
        $message_type = "success";
        $user = get_user_by_id($_SESSION["user_id"]);
    } else {
        $message = "Failed to update profile.";
        $message_type = "error";
    }
}

// Get badges
$stmt = $pdo->prepare(
    "SELECT b.*, ub.earned_at FROM user_badges ub JOIN badges b ON ub.badge_id = b.id WHERE ub.user_id = ? ORDER BY ub.earned_at DESC",
);
$stmt->execute([$_SESSION["user_id"]]);
$badges = $stmt->fetchAll();

// Stats
$stmt = $pdo->prepare(
    "SELECT COUNT(*) as count FROM user_progress WHERE user_id = ? AND completed = 1",
);
$stmt->execute([$_SESSION["user_id"]]);
$completed_count = $stmt->fetch()["count"];

$stmt = $pdo->prepare(
    "SELECT COUNT(*) + 1 as user_rank FROM users WHERE xp > ?",
);
$stmt->execute([$user["xp"]]);
$rank = $stmt->fetch()["user_rank"];

// Language breakdown
$lang_stats = [];
foreach ($languages as $lang) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total, SUM(CASE WHEN up.completed=1 THEN 1 ELSE 0 END) as done
        FROM lessons l LEFT JOIN user_progress up ON l.id=up.lesson_id AND up.user_id=?
        WHERE l.language_id=?
    ");
    $stmt->execute([$_SESSION["user_id"], $lang["id"]]);
    $lang_stats[$lang["id"]] = $stmt->fetch();
}
?>
<div style="animation: fade-in 0.5s ease-out;">
    <!-- Profile Header -->
    <div class="tw-card mb-6" style="background: linear-gradient(135deg, rgba(145,71,255,0.1), rgba(233,25,123,0.05));">
        <div class="tw-card-body">
            <div class="flex items-center gap-6 flex-wrap">
                <?= get_avatar_html($user, 80, "online") ?>
                <div style="flex:1;">
                    <h1 class="text-2xl font-bold"><?= htmlspecialchars(
                        $user["username"],
                    ) ?></h1>
                    <div class="flex items-center gap-4 mt-2 text-sm text-twitch-muted flex-wrap">
                        <span><i class="fas fa-shield-alt" style="color:#9147FF;"></i> Level <?= $user[
                            "level"
                        ] ?></span>
                        <span><i class="fas fa-star" style="color:#A970FF;"></i> <?= number_format(
                            $user["xp"],
                        ) ?> XP</span>
                        <span><i class="fas fa-ranking-star" style="color:#FFD700;"></i> Rank #<?= $rank ?></span>
                        <span><i class="fas fa-fire" style="color:#FF6B35;"></i> <?= $user[
                            "current_streak"
                        ] ?> day streak</span>
                    </div>
                    <?php if ($user["bio"]): ?>
                        <p class="text-sm text-twitch-text mt-3"><?= htmlspecialchars(
                            $user["bio"],
                        ) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="tw-card tw-card-body mb-6" style="<?= $message_type ===
        "success"
            ? "border-color:rgba(0,217,90,0.3); background:rgba(0,217,90,0.1);"
            : "border-color:rgba(233,25,123,0.3); background:rgba(233,25,123,0.1);" ?>">
            <span style="color:<?= $message_type === "success"
                ? "#00D95A"
                : "#E9197B" ?>;">
                <i class="fas fa-<?= $message_type === "success"
                    ? "check-circle"
                    : "exclamation-circle" ?> mr-2"></i>
                <?= $message ?>
            </span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Stats -->
        <div class="space-y-6">
            <div class="tw-card">
                <div class="tw-card-header">
                    <h3 class="font-bold"><i class="fas fa-chart-simple mr-2" style="color:#9147FF;"></i> Statistics</h3>
                </div>
                <div class="tw-card-body">
                    <div class="grid grid-cols-2 gap-4">
                        <div style="text-align:center;">
                            <div class="text-2xl font-black" style="color:#9147FF;"><?= $completed_count ?></div>
                            <div class="text-xs text-twitch-muted">Lessons</div>
                        </div>
                        <div style="text-align:center;">
                            <div class="text-2xl font-black" style="color:#A970FF;"><?= number_format(
                                $user["total_xp_earned"] ?: $user["xp"],
                            ) ?></div>
                            <div class="text-xs text-twitch-muted">Total XP</div>
                        </div>
                        <div style="text-align:center;">
                            <div class="text-2xl font-black" style="color:#00D95A;"><?= $user[
                                "longest_streak"
                            ] ?></div>
                            <div class="text-xs text-twitch-muted">Best Streak</div>
                        </div>
                        <div style="text-align:center;">
                            <div class="text-2xl font-black" style="color:#FF6B35;"><?= count(
                                $badges,
                            ) ?></div>
                            <div class="text-xs text-twitch-muted">Badges</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Language Progress -->
            <div class="tw-card">
                <div class="tw-card-header">
                    <h3 class="font-bold"><i class="fas fa-code mr-2" style="color:#A970FF;"></i> Languages</h3>
                </div>
                <div class="tw-card-body">
                    <div class="space-y-3">
                        <?php foreach ($languages as $lang):

                            $ls = $lang_stats[$lang["id"]] ?? [
                                "total" => 0,
                                "done" => 0,
                            ];
                            $pct =
                                $ls["total"] > 0
                                    ? round(($ls["done"] / $ls["total"]) * 100)
                                    : 0;
                            ?>
                            <div>
                                <div class="flex items-center justify-between text-sm mb-1">
                                    <span class="flex items-center gap-2">
                                        <i class="<?= $lang[
                                            "icon"
                                        ] ?>" style="color:<?= $lang[
    "color"
] ?>;"></i>
                                        <?= $lang["name"] ?>
                                    </span>
                                    <span class="text-xs text-twitch-muted"><?= $ls[
                                        "done"
                                    ] ?>/<?= $ls["total"] ?></span>
                                </div>
                                <div class="xp-bar-container">
                                    <div class="xp-bar" style="width:<?= $pct ?>%;"></div>
                                </div>
                            </div>
                        <?php
                        endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Badges -->
            <div class="tw-card">
                <div class="tw-card-header">
                    <h3 class="font-bold"><i class="fas fa-medal mr-2" style="color:#FFD700;"></i> Badges (<?= count(
                        $badges,
                    ) ?>)</h3>
                </div>
                <div class="tw-card-body">
                    <?php if (!empty($badges)): ?>
                        <div class="grid grid-cols-4 gap-3">
                            <?php foreach ($badges as $badge): ?>
                                <div style="text-align:center;" title="<?= htmlspecialchars(
                                    $badge["description"],
                                ) ?>">
                                    <div style="width:44px; height:44px; border-radius:12px; background:linear-gradient(135deg, #9147FF, #772CE8); display:flex; align-items:center; justify-content:center; margin:0 auto 4px; box-shadow:0 4px 12px rgba(145,71,255,0.3);">
                                        <i class="<?= $badge[
                                            "icon"
                                        ] ?>" style="color:white; font-size:18px;"></i>
                                    </div>
                                    <div style="font-size:9px; color:#ADADB8; line-height:1.2;"><?= htmlspecialchars(
                                        $badge["name"],
                                    ) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:12px; color:#ADADB8; font-size:13px;">
                            Complete lessons and challenges to earn badges!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Profile Settings -->
        <div class="lg:col-span-2">
            <div class="tw-card">
                <div class="tw-card-header">
                    <h3 class="font-bold"><i class="fas fa-gear mr-2" style="color:#9147FF;"></i> Profile Settings</h3>
                </div>
                <div class="tw-card-body">
                    <form method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-twitch-muted mb-1">Bio</label>
                                <textarea name="bio" rows="3" class="w-full p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text focus:outline-none focus:border-twitch-purple"><?= htmlspecialchars(
                                    $user["bio"] ?? "",
                                ) ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-twitch-muted mb-1">Location</label>
                                <input type="text" name="location" value="<?= htmlspecialchars(
                                    $user["location"] ?? "",
                                ) ?>" class="w-full p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text focus:outline-none focus:border-twitch-purple" placeholder="City, Country">

                                <label class="block text-sm font-medium text-twitch-muted mb-1 mt-3">Preferred Language</label>
                                <select name="preferred_language" class="w-full p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text focus:outline-none focus:border-twitch-purple">
                                    <?= get_language_select_options(
                                        $user["preferred_language"],
                                    ) ?>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-twitch-muted mb-1">GitHub</label>
                                <div class="flex items-center gap-2">
                                    <i class="fab fa-github" style="color:#ADADB8;"></i>
                                    <input type="text" name="github_username" value="<?= htmlspecialchars(
                                        $user["github_username"] ?? "",
                                    ) ?>" class="flex-1 p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text focus:outline-none focus:border-twitch-purple" placeholder="username">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-twitch-muted mb-1">Website</label>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-globe" style="color:#ADADB8;"></i>
                                    <input type="url" name="website" value="<?= htmlspecialchars(
                                        $user["website"] ?? "",
                                    ) ?>" class="flex-1 p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text focus:outline-none focus:border-twitch-purple" placeholder="https://">
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="public_profile" value="1" <?= $user[
                                    "public_profile"
                                ]
                                    ? "checked"
                                    : "" ?> class="rounded bg-twitch-medium border-twitch-border">
                                <span class="text-sm text-twitch-muted">Make profile public</span>
                            </label>

                            <button type="submit" name="update_profile" class="tw-btn tw-btn-primary">
                                <i class="fas fa-save"></i>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Recent completions -->
            <div class="tw-card mt-6">
                <div class="tw-card-header">
                    <h3 class="font-bold"><i class="fas fa-clock-rotate mr-2" style="color:#00D95A;"></i> Recent Completions</h3>
                </div>
                <div class="tw-card-body">
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT l.title, l.xp_reward, lang.name as lang_name, lang.icon as lang_icon, lang.color as lang_color, up.completed_at
                        FROM user_progress up
                        JOIN lessons l ON up.lesson_id = l.id
                        JOIN languages lang ON l.language_id = lang.id
                        WHERE up.user_id = ? AND up.completed = 1
                        ORDER BY up.completed_at DESC
                        LIMIT 10
                    ");
                    $stmt->execute([$_SESSION["user_id"]]);
                    $completions = $stmt->fetchAll();
                    ?>

                    <?php if (!empty($completions)): ?>
                        <div class="space-y-2">
                            <?php foreach ($completions as $c): ?>
                                <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-twitch-medium transition-all">
                                    <i class="<?= $c[
                                        "lang_icon"
                                    ] ?>" style="color:<?= $c[
    "lang_color"
] ?>; width:20px; text-align:center;"></i>
                                    <div style="flex:1;">
                                        <div class="text-sm font-medium"><?= htmlspecialchars(
                                            $c["title"],
                                        ) ?></div>
                                        <div class="text-xs text-twitch-muted"><?= $c[
                                            "lang_name"
                                        ] ?> · <?= time_ago(
     $c["completed_at"],
 ) ?></div>
                                    </div>
                                    <span style="color:#A970FF; font-size:12px; font-weight:600;">+<?= $c[
                                        "xp_reward"
                                    ] ?> XP</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:12px; color:#ADADB8;">
                            No lessons completed yet. Start learning!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
