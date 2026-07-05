<?php
$page_title = 'Battle Royale';
$user = get_user_by_id($_SESSION['user_id']);
$active_matches = get_active_br_matches();
$languages = get_languages();

// Handle join match
$join_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_match'])) {
    $match_id = (int)$_POST['match_id'];
    $join_result = join_br_match($match_id, $_SESSION['user_id']);
}

// Handle create match
$create_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_match'])) {
    $title = sanitize($_POST['title'] ?? '');
    $type = $_POST['type'] ?? 'solo';
    $language_id = (int)($_POST['language_id'] ?? 1);
    $difficulty = $_POST['difficulty'] ?? 'beginner';
    $challenge_desc = sanitize($_POST['challenge_description'] ?? '');
    $starter_code = $_POST['starter_code'] ?? '';

    if (!empty($title)) {
        $match_id = create_br_match($title, $type, $language_id, $difficulty, [
            'description' => $challenge_desc,
            'starter_code' => $starter_code,
            'expected_output' => $_POST['expected_output'] ?? '',
            'max_players' => (int)($_POST['max_players'] ?? BR_DEFAULT_MAX_PLAYERS),
            'time_limit' => (int)($_POST['time_limit'] ?? BR_DEFAULT_TIME_LIMIT)
        ], $_SESSION['user_id']);

        join_br_match($match_id, $_SESSION['user_id']);
        $create_result = $match_id;
    }
}
?>
<div style="animation: fade-in 0.5s ease-out;">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-3">
                <i class="fas fa-crosshairs" style="color: #E9197B;"></i>
                Battle Royale Arena
            </h1>
            <p class="text-twitch-muted mt-1">Compete in real-time coding battles. Last coder standing wins!</p>
        </div>
        <button onclick="document.getElementById('create-match-modal').classList.remove('hidden')" class="tw-btn tw-btn-primary tw-btn-lg">
            <i class="fas fa-plus"></i>
            Create Match
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8" style="animation: slide-up 0.5s ease-out;">
        <div class="tw-card tw-card-body" style="text-align:center;">
            <div class="text-3xl font-black gradient-text"><?= count($active_matches) ?></div>
            <div class="text-sm text-twitch-muted mt-1">Active Matches</div>
        </div>
        <div class="tw-card tw-card-body" style="text-align:center;">
            <div class="text-3xl font-black" style="color:#9147FF;">
                <?php
                $total = 0;
                foreach ($active_matches as $m) $total += $m['player_count'];
                echo $total;
                ?>
            </div>
            <div class="text-sm text-twitch-muted mt-1">Players in Queue</div>
        </div>
        <div class="tw-card tw-card-body" style="text-align:center;">
            <div class="text-3xl font-black" style="color:#00D95A;">Solo</div>
            <div class="text-sm text-twitch-muted mt-1">Most Popular Mode</div>
        </div>
        <div class="tw-card tw-card-body" style="text-align:center;">
            <div class="text-3xl font-black" style="color:#FF6B35;">
                <?= number_format($user['xp']) ?>
            </div>
            <div class="text-sm text-twitch-muted mt-1">Your XP</div>
        </div>
    </div>

    <!-- Active Matches -->
    <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
        <span class="live-dot"></span>
        Live Matches
    </h2>

    <?php if (!empty($join_result) && isset($join_result['error'])): ?>
        <div class="tw-card tw-card-body mb-4" style="border-color:rgba(233,25,123,0.3); background:rgba(233,25,123,0.1);">
            <div style="color:#E9197B;"><i class="fas fa-exclamation-circle mr-2"></i><?= $join_result['error'] ?></div>
        </div>
    <?php endif; ?>

    <?php if ($create_result): ?>
        <div class="tw-card tw-card-body mb-4" style="border-color:rgba(0,217,90,0.3); background:rgba(0,217,90,0.1);">
            <div style="color:#00D95A;">
                <i class="fas fa-check-circle mr-2"></i>
                Match created! You've been added to the lobby.
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <?php if (!empty($active_matches)): ?>
            <?php foreach ($active_matches as $match): ?>
                <div class="tw-card stream-card" style="animation: slide-up 0.5s ease-out;">
                    <div class="stream-thumb" style="background: linear-gradient(135deg, #1F1F23, #2D2D35); padding: 32px; display:flex; align-items:center; justify-content:center;">
                        <div style="text-align:center;">
                            <i class="<?= get_br_type_icon($match['type']) ?>" style="font-size:48px; color:<?= $match['language_color'] ?>; margin-bottom:12px;"></i>
                            <div style="font-size:14px; font-weight:600; color:#EFEFF1;"><?= htmlspecialchars($match['title']) ?></div>
                            <div style="font-size:12px; color:#ADADB8;"><?= ucfirst($match['type']) ?> · <?= ucfirst($match['difficulty']) ?></div>
                        </div>
                        <?php if ($match['status'] === 'lobby'): ?>
                            <span class="live-badge"><span class="live-dot"></span> LOBBY</span>
                        <?php else: ?>
                            <span class="live-badge" style="background:#00D95A;"><span class="live-dot" style="background:#00D95A;"></span> LIVE</span>
                        <?php endif; ?>
                        <span class="viewer-count"><i class="fas fa-user"></i> <?= $match['player_count'] ?>/<?= $match['max_players'] ?></span>
                    </div>
                    <div class="tw-card-body">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <span class="lang-pill" style="background:<?= $match['language_color'] ?>20; color:<?= $match['language_color'] ?>; border:1px solid <?= $match['language_color'] ?>40;">
                                    <i class="<?= $match['language_icon'] ?>"></i>
                                    <?= $match['language_name'] ?>
                                </span>
                                <span class="text-xs px-2 py-1 rounded-full font-medium
                                    <?= $match['difficulty'] === 'beginner' ? 'bg-green-500/20 text-green-400' : '' ?>
                                    <?= $match['difficulty'] === 'intermediate' ? 'bg-blue-500/20 text-blue-400' : '' ?>
                                    <?= $match['difficulty'] === 'advanced' ? 'bg-red-500/20 text-red-400' : '' ?>
                                "><?= ucfirst($match['difficulty']) ?></span>
                            </div>
                            <span class="text-xs text-twitch-muted">Created by <?= htmlspecialchars($match['creator_name']) ?></span>
                        </div>

                        <?php if ($match['challenge_description']): ?>
                            <p class="text-sm text-twitch-muted mb-4"><?= htmlspecialchars(substr($match['challenge_description'], 0, 150)) ?>...</p>
                        <?php endif; ?>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4 text-xs text-twitch-muted">
                                <span><i class="far fa-clock mr-1"></i> <?= $match['time_limit_minutes'] ?> min</span>
                                <span><i class="fas fa-trophy mr-1"></i> <?= XP_BATTLE_ROYALE_WIN ?> XP Win</span>
                            </div>

                            <?php if ($match['status'] === 'lobby'): ?>
                                <form method="POST">
                                    <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                    <button type="submit" name="join_match" class="tw-btn tw-btn-primary">
                                        <i class="fas fa-sign-in-alt"></i>
                                        Join Match
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-sm font-bold" style="color:#00D95A;">
                                    <i class="fas fa-play"></i> In Progress
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="tw-card tw-card-body lg:col-span-2" style="text-align:center; padding:60px 20px;">
                <i class="fas fa-crosshairs" style="font-size:64px; color:#2D2D35; margin-bottom:16px;"></i>
                <h3 class="text-xl font-bold mb-2">No Active Battles</h3>
                <p class="text-twitch-muted mb-6">Be the first to create a match and challenge other coders!</p>
                <button onclick="document.getElementById('create-match-modal').classList.remove('hidden')" class="tw-btn tw-btn-primary tw-btn-lg">
                    <i class="fas fa-plus"></i>
                    Create First Match
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Match Modal -->
<div id="create-match-modal" class="hidden" style="position:fixed; inset:0; z-index:1000; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.8); backdrop-filter:blur(4px);" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="tw-card" style="width:100%; max-width:600px; max-height:90vh; overflow-y:auto;" onclick="event.stopPropagation()">
        <div class="tw-card-header">
            <h2 class="text-lg font-bold"><i class="fas fa-crosshairs mr-2" style="color:#E9197B;"></i> Create Battle Royale Match</h2>
            <button onclick="document.getElementById('create-match-modal').classList.add('hidden')" class="tw-btn tw-btn-ghost tw-btn-sm">&times;</button>
        </div>
        <div class="tw-card-body">
            <form method="POST">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-twitch-muted mb-1">Match Title</label>
                        <input type="text" name="title" required class="w-full p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text focus:outline-none focus:border-twitch-purple" placeholder="e.g., Friday Night Coding Battle">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-twitch-muted mb-1">Game Mode</label>
                            <select name="type" class="w-full p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text focus:outline-none focus:border-twitch-purple">
                                <option value="solo">Solo (1v1)</option>
                                <option value="duo">Duo (2v2)</option>
                                <option value="squad">Squad (4v4)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-twitch-muted mb-1">Difficulty</label>
                            <select name="difficulty" class="w-full p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text focus:outline-none focus:border-twitch-purple">
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-twitch-muted mb-1">Programming Language</label>
                        <select name="language_id" class="w-full p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text focus:outline-none focus:border-twitch-purple">
                            <?= get_language_select_options() ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-twitch-muted mb-1">Max Players</label>
                            <input type="number" name="max_players" value="10" min="2" max="50" class="w-full p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text focus:outline-none focus:border-twitch-purple">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-twitch-muted mb-1">Time Limit (min)</label>
                            <input type="number" name="time_limit" value="15" min="5" max="60" class="w-full p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text focus:outline-none focus:border-twitch-purple">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-twitch-muted mb-1">Challenge Description</label>
                        <textarea name="challenge_description" rows="3" class="w-full p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text focus:outline-none focus:border-twitch-purple" placeholder="Describe the coding challenge..."></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-twitch-muted mb-1">Starter Code</label>
                        <textarea name="starter_code" rows="4" class="w-full p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text font-mono text-sm focus:outline-none focus:border-twitch-purple" placeholder="// Starter code for participants"></textarea>
                    </div>

                    <button type="submit" name="create_match" class="tw-btn tw-btn-primary tw-btn-block tw-btn-lg">
                        <i class="fas fa-crosshairs"></i>
                        Create Battle Royale
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    #create-match-modal.hidden { display: none !important; }
    #create-match-modal:not(.hidden) { display: flex !important; }
</style>
