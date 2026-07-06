<?php
$page_title = "Daily Challenge";
$user = get_user_by_id($_SESSION["user_id"]);
$languages = get_languages();
$daily = get_daily_challenge();

// Auto-generate today's challenge if none exists
if (!$daily) {
    $langs = get_languages();
    if (!empty($langs)) {
        $lang = $langs[array_rand($langs)];
        $difficulties = ["beginner", "intermediate", "advanced"];
        $difficulty = $difficulties[array_rand($difficulties)];
        $challenge_types = ["coding", "debugging", "optimization", "algorithm"];
        $challenge_type = $challenge_types[array_rand($challenge_types)];

        // Try AI-generated challenge first
        $ai_challenge = generate_ai_daily_challenge(
            $lang,
            $difficulty,
            $challenge_type,
        );

        if ($ai_challenge) {
            $data = [
                "title" => $ai_challenge["title"],
                "description" => $ai_challenge["description"],
                "language_id" => $lang["id"],
                "difficulty" => $difficulty,
                "challenge_type" => $challenge_type,
                "starter_code" => $ai_challenge["starter_code"],
                "test_cases" => $ai_challenge["test_cases"] ?? [],
                "xp_reward" => XP_DAILY_CHALLENGE,
                "bonus_xp" => 50,
                "date" => date("Y-m-d"),
            ];
        } else {
            // Fallback to basic challenge
            $data = [
                "title" =>
                    $lang["name"] .
                    ": " .
                    ucfirst($challenge_type) .
                    " Challenge",
                "description" =>
                    "Complete today's " .
                    $difficulty .
                    " " .
                    $challenge_type .
                    " challenge in " .
                    $lang["name"] .
                    ". Write working code and test it.",
                "language_id" => $lang["id"],
                "difficulty" => $difficulty,
                "challenge_type" => $challenge_type,
                "starter_code" => "// Write your solution here",
                "test_cases" => [],
                "xp_reward" => XP_DAILY_CHALLENGE,
                "bonus_xp" => 50,
                "date" => date("Y-m-d"),
            ];
        }

        try {
            $challenge_id = create_daily_challenge($data);
            $daily = get_daily_challenge();
        } catch (PDOException $e) {
            // Challenge may already exist
        }
    }
}

// Handle submission
$submitted = false;
$result = null;
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["submit_challenge"])
) {
    $code = $_POST["code"] ?? "";
    if (!empty($code) && $daily) {
        $xp_earned = $daily["xp_reward"];
        $bonus = $daily["bonus_xp"] ?? 0;
        add_xp($_SESSION["user_id"], $xp_earned + $bonus, "daily_challenge");
        $submitted = true;
        $result = [
            "xp_earned" => $xp_earned + $bonus,
            "base_xp" => $xp_earned,
            "bonus" => $bonus,
        ];
        create_notification(
            $_SESSION["user_id"],
            "lesson_completed",
            "Daily Challenge Complete!",
            "You completed today's challenge and earned {$xp_earned} XP!",
        );
    }
}
?>
<div style="animation: fade-in 0.5s ease-out;">
    <div class="flex items-center gap-3 mb-6">
        <a href="index.php?page=dashboard" class="tw-btn tw-btn-ghost">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-3">
                <i class="fas fa-calendar-day" style="color: #FF6B35;"></i>
                Daily Challenge
            </h1>
            <p class="text-twitch-muted mt-1">Complete today's coding challenge for bonus XP!</p>
        </div>
    </div>

    <?php if ($submitted && $result): ?>
        <div class="tw-card tw-card-body mb-6" style="border-color:rgba(0,217,90,0.3); background:rgba(0,217,90,0.1); text-align:center; padding:40px;">
            <i class="fas fa-trophy" style="font-size:48px; color:#FFD700; margin-bottom:12px;"></i>
            <h2 class="text-2xl font-bold mb-2">Challenge Complete! 🎉</h2>
            <p class="text-twitch-muted mb-2">Base: <span class="font-bold text-white">+<?= $result[
                "base_xp"
            ] ?> XP</span></p>
            <?php if ($result["bonus"] > 0): ?>
                <p class="text-twitch-muted mb-2">Bonus: <span class="font-bold" style="color:#00D95A;">+<?= $result[
                    "bonus"
                ] ?> XP</span></p>
            <?php endif; ?>
            <p class="text-lg font-bold gradient-text">Total: +<?= $result[
                "xp_earned"
            ] ?> XP!</p>
            <a href="index.php?page=dashboard" class="tw-btn tw-btn-primary mt-4">
                <i class="fas fa-home"></i>
                Back to Dashboard
            </a>
        </div>
    <?php endif; ?>

    <?php if ($daily): ?>
        <div class="tw-card">
            <div class="tw-card-body">
                <div class="flex items-center gap-4 mb-6">
                    <div class="text-4xl">🎯</div>
                    <div>
                        <h2 class="text-xl font-bold"><?= htmlspecialchars(
                            $daily["title"],
                        ) ?></h2>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-xs px-2 py-1 rounded-full" style="background:<?= $daily[
                                "language_color"
                            ] ?>20; color:<?= $daily["language_color"] ?>;">
                                <i class="<?php
                                $lang_icon = "fas fa-code";
                                foreach ($languages as $l) {
                                    if ($l["id"] == $daily["language_id"]) {
                                        $lang_icon = $l["icon"];
                                        break;
                                    }
                                }
                                echo $lang_icon;
                                ?>"></i>
                                <?= htmlspecialchars($daily["language_name"]) ?>
                            </span>
                            <span class="text-xs px-2 py-1 rounded-full font-medium
                                <?= $daily["difficulty"] === "beginner"
                                    ? "bg-green-500/20 text-green-400"
                                    : "" ?>
                                <?= $daily["difficulty"] === "intermediate"
                                    ? "bg-blue-500/20 text-blue-400"
                                    : "" ?>
                                <?= $daily["difficulty"] === "advanced"
                                    ? "bg-red-500/20 text-red-400"
                                    : "" ?>
                            "><?= ucfirst($daily["difficulty"]) ?></span>
                            <span class="text-xs px-2 py-1 rounded-full" style="background:rgba(169,112,255,0.1); color:#A970FF;">
                                <i class="fas <?= get_challenge_type_icon(
                                    $daily["challenge_type"],
                                ) ?>"></i>
                                <?= ucfirst($daily["challenge_type"]) ?>
                            </span>
                        </div>
                    </div>
                    <div style="margin-left:auto; text-align:right;">
                        <div class="text-2xl font-bold" style="color:#A970FF;">+<?= $daily[
                            "xp_reward"
                        ] ?> XP</div>
                        <?php if ($daily["bonus_xp"] > 0): ?>
                            <div class="text-xs" style="color:#00D95A;">+<?= $daily[
                                "bonus_xp"
                            ] ?> bonus</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-6">
                    <p class="text-sm text-twitch-muted mb-4"><?= nl2br(
                        htmlspecialchars($daily["description"] ?? ""),
                    ) ?></p>
                </div>

                <form method="POST">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-twitch-muted mb-2">Your Solution</label>
                        <textarea name="code" rows="10" class="w-full p-4 bg-twitch-dark border border-twitch-border rounded-lg text-twitch-text font-mono text-sm focus:outline-none focus:border-twitch-purple" placeholder="Write your code here..." required><?= htmlspecialchars(
                            $daily["starter_code"] ?? "",
                        ) ?></textarea>
                    </div>
                    <button type="submit" name="submit_challenge" class="tw-btn tw-btn-primary tw-btn-lg">
                        <i class="fas fa-check"></i>
                        Submit Challenge
                    </button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="tw-card tw-card-body" style="text-align:center; padding:80px 20px;">
            <i class="fas fa-calendar-day" style="font-size:64px; color:#2D2D35; margin-bottom:16px;"></i>
            <h3 class="text-xl font-bold mb-2">No Challenge Today</h3>
            <p class="text-twitch-muted mb-4">Check back tomorrow for a new daily challenge!</p>
            <a href="index.php?page=lessons" class="tw-btn tw-btn-primary">
                <i class="fas fa-graduation-cap"></i>
                Keep Learning
            </a>
        </div>
    <?php endif; ?>
</div>
