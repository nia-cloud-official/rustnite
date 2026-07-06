<?php
$page_title = "Leaderboard";
$languages = get_languages();
$lang_filter = (int) ($_GET["language"] ?? 0);

if ($lang_filter > 0) {
    $leaderboard = get_language_leaderboard($lang_filter);
} else {
    $leaderboard = get_leaderboard();
}

$user = get_user_by_id($_SESSION["user_id"]);

// Find user rank
$user_rank = null;
foreach ($leaderboard as $index => $player) {
    if ($player["username"] === $user["username"]) {
        $user_rank = $index + 1;
        break;
    }
}
if (!$user_rank) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) + 1 as rank FROM users WHERE xp > ?",
    );
    $stmt->execute([$user["xp"]]);
    $user_rank = $stmt->fetch()["rank"];
}
?>
<div style="animation: fade-in 0.5s ease-out;">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-3">
                <i class="fas fa-trophy" style="color: #FFD700;"></i>
                Leaderboard
            </h1>
            <p class="text-twitch-muted mt-1">See how you stack up against the best coders.</p>
        </div>
    </div>

    <!-- Your Rank Card -->
    <div class="tw-card tw-card-body mb-6" style="background:linear-gradient(135deg, rgba(145,71,255,0.1), rgba(255,215,0,0.05)); border-color:rgba(145,71,255,0.2);">
        <div class="grid grid-cols-4 gap-6">
            <div style="text-align:center;">
                <div class="text-3xl font-black" style="color:#FFD700;">#<?= $user_rank ?></div>
                <div class="text-xs text-twitch-muted">Your Rank</div>
            </div>
            <div style="text-align:center;">
                <div class="text-3xl font-black" style="color:#A970FF;"><?= number_format(
                    $user["xp"],
                ) ?></div>
                <div class="text-xs text-twitch-muted">Total XP</div>
            </div>
            <div style="text-align:center;">
                <div class="text-3xl font-black" style="color:#00D95A;"><?= $user[
                    "level"
                ] ?></div>
                <div class="text-xs text-twitch-muted">Level</div>
            </div>
            <div style="text-align:center;">
                <div class="text-3xl font-black" style="color:#FF6B35;">
                    <?php
                    $stmt = $pdo->prepare(
                        "SELECT COUNT(*) as count FROM user_progress WHERE user_id = ? AND completed = 1",
                    );
                    $stmt->execute([$_SESSION["user_id"]]);
                    echo $stmt->fetch()["count"];
                    ?>
                </div>
                <div class="text-xs text-twitch-muted">Lessons</div>
            </div>
        </div>
    </div>

    <!-- Language Filter -->
    <div class="tw-card tw-card-body mb-6" style="padding:12px;">
        <div class="flex items-center gap-2 overflow-x-auto" style="scrollbar-width:none;">
            <a href="?page=leaderboard" class="tw-btn <?= $lang_filter === 0
                ? "tw-btn-primary"
                : "tw-btn-ghost" ?> tw-btn-sm">
                <i class="fas fa-globe"></i> Global
            </a>
            <?php foreach ($languages as $lang): ?>
                <a href="?page=leaderboard&language=<?= $lang[
                    "id"
                ] ?>" class="tw-btn <?= $lang_filter === $lang["id"]
    ? "tw-btn-primary"
    : "tw-btn-ghost" ?> tw-btn-sm">
                    <i class="<?= $lang["icon"] ?>"></i> <?= $lang["name"] ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Leaderboard Table -->
    <div class="tw-card">
        <div class="tw-card-body" style="padding:0;">
            <?php if (!empty($leaderboard)): ?>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="border-bottom:1px solid #2D2D35; background:#1F1F23;">
                                <th style="padding:12px 16px; text-align:left; font-size:12px; color:#ADADB8; text-transform:uppercase; letter-spacing:0.5px;">Rank</th>
                                <th style="padding:12px 16px; text-align:left; font-size:12px; color:#ADADB8; text-transform:uppercase; letter-spacing:0.5px;">Player</th>
                                <th style="padding:12px 16px; text-align:center; font-size:12px; color:#ADADB8; text-transform:uppercase; letter-spacing:0.5px;">Level</th>
                                <th style="padding:12px 16px; text-align:center; font-size:12px; color:#ADADB8; text-transform:uppercase; letter-spacing:0.5px;">Lessons</th>
                                <th style="padding:12px 16px; text-align:right; font-size:12px; color:#ADADB8; text-transform:uppercase; letter-spacing:0.5px;">XP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaderboard as $index => $player):
                                $is_current =
                                    $player["username"] ===
                                    $user["username"]; ?>
                                <tr style="border-bottom:1px solid #2D2D35; <?= $is_current
                                    ? "background:rgba(145,71,255,0.08);"
                                    : "" ?> transition:background 0.2s;"
                                    onmouseover="this.style.background='<?= $is_current
                                        ? "rgba(145,71,255,0.12)"
                                        : "#1F1F23" ?>'"
                                    onmouseout="this.style.background='<?= $is_current
                                        ? "rgba(145,71,255,0.08)"
                                        : "transparent" ?>'">
                                    <td style="padding:12px 16px; width:60px;">
                                        <?php if ($index === 0): ?>
                                            <i class="fas fa-crown" style="color:#FFD700; font-size:20px;"></i>
                                        <?php elseif ($index === 1): ?>
                                            <i class="fas fa-medal" style="color:#C0C0C0; font-size:20px;"></i>
                                        <?php elseif ($index === 2): ?>
                                            <i class="fas fa-award" style="color:#CD7F32; font-size:20px;"></i>
                                        <?php else: ?>
                                            <span style="font-weight:700; color:#ADADB8;">#<?= $index +
                                                1 ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:12px 16px;">
                                        <div class="flex items-center gap-3">
                                            <?= get_avatar_html($player, 36) ?>
                                            <div>
                                                <div class="font-medium text-sm <?= $is_current
                                                    ? "text-twitch-purple"
                                                    : "" ?>">
                                                    <?= htmlspecialchars(
                                                        $player["username"],
                                                    ) ?>
                                                    <?php if ($is_current): ?>
                                                        <span style="font-size:10px; color:#9147FF;">(You)</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-xs text-twitch-muted"><?= $player[
                                                    "level"
                                                ] ?> <?= $lang_filter > 0
     ? "Coder"
     : "Rustacean" ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding:12px 16px; text-align:center; font-weight:600;"><?= $player[
                                        "level"
                                    ] ?></td>
                                    <td style="padding:12px 16px; text-align:center; color:#ADADB8;"><?= $player[
                                        "lessons_completed"
                                    ] ?? "-" ?></td>
                                    <td style="padding:12px 16px; text-align:right; font-weight:700; color:#A970FF;"><?= number_format(
                                        $player["xp"],
                                    ) ?></td>
                                </tr>
                            <?php
                            endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align:center; padding:60px 20px;">
                    <i class="fas fa-trophy" style="font-size:64px; color:#2D2D35; margin-bottom:16px;"></i>
                    <h3 class="text-xl font-bold mb-2">No Players Yet</h3>
                    <p class="text-twitch-muted">Be the first to earn XP and claim the top spot!</p>
                    <a href="index.php?page=lessons" class="tw-btn tw-btn-primary mt-4">
                        <i class="fas fa-graduation-cap"></i> Start Learning
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
