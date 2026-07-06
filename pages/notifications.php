<?php
$page_title = "Notifications";
$user = get_user_by_id($_SESSION["user_id"]);

if (isset($_POST["mark_all_read"])) {
    $stmt = $pdo->prepare(
        "UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL",
    );
    $stmt->execute([$_SESSION["user_id"]]);
    $redirect_url = "index.php?page=notifications";
    echo "<script>window.location.href='$redirect_url';</script>";
    exit();
}

if (isset($_GET["mark_read"])) {
    mark_notification_read((int) $_GET["mark_read"], $_SESSION["user_id"]);
    $redirect_url = "index.php?page=notifications";
    echo "<script>window.location.href='$redirect_url';</script>";
    exit();
}

$notifications = get_user_notifications($_SESSION["user_id"], 50);
$unread_count = get_unread_notification_count($_SESSION["user_id"]);
?>
<div style="animation: fade-in 0.5s ease-out;">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-3">
                <i class="fas fa-bell" style="color: #9147FF;"></i>
                Notifications
            </h1>
            <p class="text-twitch-muted mt-1">Stay up to date with your coding journey.</p>
        </div>
        <?php if ($unread_count > 0): ?>
            <form method="POST">
                <button type="submit" name="mark_all_read" class="tw-btn tw-btn-secondary tw-btn-sm">
                    <i class="fas fa-check-double"></i>
                    Mark All Read (<?= $unread_count ?>)
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (!empty($notifications)): ?>
        <div class="space-y-2">
            <?php foreach ($notifications as $notif):

                $icons = [
                    "badge_earned" => "fa-award",
                    "level_up" => "fa-arrow-up",
                    "lesson_completed" => "fa-check-circle",
                    "br_event" => "fa-crosshairs",
                    "mini_game" => "fa-gamepad",
                    "streak" => "fa-fire",
                    "follow" => "fa-user-plus",
                    "like" => "fa-heart",
                ];
                $icon = $icons[$notif["type"]] ?? "fa-bell";
                $is_unread = $notif["read_at"] === null;
                ?>
                <div class="tw-card tw-card-body" style="<?= $is_unread
                    ? "border-left:3px solid #9147FF;"
                    : "" ?>">
                    <div class="flex items-start gap-3">
                        <div style="width:36px; height:36px; border-radius:50%; background:rgba(145,71,255,0.1); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i class="fas <?= $icon ?>" style="color:#9147FF; font-size:14px;"></i>
                        </div>
                        <div style="flex:1;">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-bold text-sm"><?= htmlspecialchars(
                                    $notif["title"],
                                ) ?></span>
                                <?php if ($is_unread): ?>
                                    <span class="live-dot"></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-twitch-muted mb-1"><?= htmlspecialchars(
                                $notif["message"],
                            ) ?></p>
                            <div class="flex items-center gap-3 text-xs text-twitch-muted">
                                <span><?= time_ago(
                                    $notif["created_at"],
                                ) ?></span>
                                <?php if ($is_unread): ?>
                                    <a href="?page=notifications&mark_read=<?= $notif[
                                        "id"
                                    ] ?>" class="text-twitch-purple">Mark read</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
            endforeach; ?>
        </div>
    <?php else: ?>
        <div class="tw-card tw-card-body" style="text-align:center; padding:80px 20px;">
            <i class="fas fa-bell-slash" style="font-size:64px; color:#2D2D35; margin-bottom:16px;"></i>
            <h3 class="text-xl font-bold mb-2">No Notifications</h3>
            <p class="text-twitch-muted mb-4">You're all caught up! Notifications will appear here when something happens.</p>
            <a href="index.php?page=lessons" class="tw-btn tw-btn-primary">
                <i class="fas fa-graduation-cap"></i>
                Start Learning
            </a>
        </div>
    <?php endif; ?>
</div>
