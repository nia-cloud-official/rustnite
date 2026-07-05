<?php
$page_title = 'Mini-Games';
$games = get_mini_games();
$languages = get_languages();
?>
<div style="animation: fade-in 0.5s ease-out;">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-3">
                <i class="fas fa-gamepad" style="color: #9147FF;"></i>
                Mini-Games Arena
            </h1>
            <p class="text-twitch-muted mt-1">Sharpen your skills through fun coding games and challenges!</p>
        </div>
    </div>

    <!-- Game Categories -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <?php
        $categories = [
            ['icon' => 'fa-running', 'label' => 'Code Race', 'color' => '#00D95A', 'type' => 'code_race'],
            ['icon' => 'fa-bug', 'label' => 'Bug Hunt', 'color' => '#E9197B', 'type' => 'bug_hunt'],
            ['icon' => 'fa-brain', 'label' => 'Predict Output', 'color' => '#9147FF', 'type' => 'output_prediction'],
            ['icon' => 'fa-tachometer-alt', 'label' => 'Speed Syntax', 'color' => '#FF6B35', 'type' => 'syntax_speed'],
        ];
        foreach ($categories as $cat): ?>
        <div class="tw-card tw-card-body" style="text-align:center; cursor:pointer;" onclick="filterGames('<?= $cat['type'] ?>')">
            <i class="fas <?= $cat['icon'] ?>" style="font-size:32px; color:<?= $cat['color'] ?>; margin-bottom:8px;"></i>
            <div class="font-bold text-sm"><?= $cat['label'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Available Games -->
    <h2 class="text-xl font-bold mb-4">Available Games</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="games-grid">
        <?php if (!empty($games)): ?>
            <?php foreach ($games as $game):
                $game_data = json_decode($game['game_data'], true);
            ?>
                <div class="tw-card game-card" data-type="<?= $game['type'] ?>" style="animation: slide-up 0.5s ease-out;">
                    <div class="stream-thumb" style="background: linear-gradient(135deg, #1F1F23, #2D2D35); padding: 24px; display:flex; align-items:center; justify-content:center; flex-direction:column;">
                        <i class="fas <?= get_mini_game_type_icon($game['type']) ?>" style="font-size:40px; color:#9147FF; margin-bottom:8px;"></i>
                        <span class="text-sm font-bold"><?= $game['title'] ?></span>
                    </div>
                    <div class="tw-card-body">
                        <p class="text-sm text-twitch-muted mb-4"><?= htmlspecialchars($game['description']) ?></p>

                        <div class="flex items-center gap-2 mb-4">
                            <span class="text-xs px-2 py-1 rounded-full font-medium
                                <?= $game['difficulty'] === 'beginner' ? 'bg-green-500/20 text-green-400' : '' ?>
                                <?= $game['difficulty'] === 'intermediate' ? 'bg-blue-500/20 text-blue-400' : '' ?>
                                <?= $game['difficulty'] === 'advanced' ? 'bg-red-500/20 text-red-400' : '' ?>
                            "><?= ucfirst($game['difficulty']) ?></span>
                            <span class="text-xs text-twitch-muted">
                                <i class="fas fa-gamepad mr-1"></i>
                                <?= str_replace('_', ' ', ucfirst($game['type'])) ?>
                            </span>
                        </div>

                        <?php if ($game_data && isset($game_data['time_limit'])): ?>
                            <div class="text-xs text-twitch-muted mb-3">
                                <i class="far fa-clock mr-1"></i> <?= $game_data['time_limit'] ?>s time limit
                            </div>
                        <?php endif; ?>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold" style="color:#A970FF;">
                                <i class="fas fa-star mr-1"></i>
                                +<?= $game['xp_reward'] ?> XP
                            </span>

                            <a href="index.php?page=mini-game-play&id=<?= $game['id'] ?>" class="tw-btn tw-btn-primary tw-btn-sm">
                                <i class="fas fa-play"></i>
                                Play Now
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="tw-card tw-card-body lg:col-span-3" style="text-align:center; padding:60px 20px;">
                <i class="fas fa-gamepad" style="font-size:64px; color:#2D2D35; margin-bottom:16px;"></i>
                <h3 class="text-xl font-bold mb-2">No Games Available</h3>
                <p class="text-twitch-muted">Mini-games are being prepared. Check back soon!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterGames(type) {
    const cards = document.querySelectorAll('.game-card');
    cards.forEach(card => {
        if (type === 'all' || card.dataset.type === type) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });

    // Highlight active filter
    document.querySelectorAll('[onclick^="filterGames"]').forEach(el => {
        el.style.borderColor = el.getAttribute('onclick').includes(type) ? '#9147FF' : '';
    });
}

// Reset filter to show all
document.querySelectorAll('[onclick^="filterGames"]')[0]?.addEventListener('dblclick', () => filterGames('all'));
</script>
