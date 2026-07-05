<?php
$page_title = 'Mini-Game';
$game_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM mini_games WHERE id = ? AND is_active = TRUE");
$stmt->execute([$game_id]);
$game = $stmt->fetch();

if (!$game) {
    header('Location: index.php?page=mini-games');
    exit;
}

$game_data = json_decode($game['game_data'], true);

// Handle score submission
$play_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_score'])) {
    $score = (int)($_POST['score'] ?? 0);
    $time_taken = (int)($_POST['time_taken'] ?? 0);
    $play_result = play_mini_game($game_id, $_SESSION['user_id'], $score, $time_taken);
}
?>
<div style="animation: fade-in 0.5s ease-out;">
    <div class="flex items-center gap-3 mb-6">
        <a href="index.php?page=mini-games" class="tw-btn tw-btn-ghost">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold"><?= htmlspecialchars($game['title']) ?></h1>
            <p class="text-twitch-muted text-sm"><?= htmlspecialchars($game['description']) ?></p>
        </div>
    </div>

    <?php if ($play_result && $play_result['success']): ?>
        <div class="tw-card tw-card-body mb-6" style="border-color:rgba(0,217,90,0.3); background:rgba(0,217,90,0.1); text-align:center; padding:40px;">
            <i class="fas fa-trophy" style="font-size:48px; color:#FFD700; margin-bottom:12px;"></i>
            <h2 class="text-2xl font-bold mb-2">Game Complete!</h2>
            <p class="text-twitch-muted mb-2">Score: <span class="font-bold text-white"><?= $play_result['score'] ?></span></p>
            <p class="text-twitch-muted mb-4">Time: <span class="font-bold text-white"><?= $play_result['time_taken'] ?>s</span></p>
            <p class="text-lg font-bold gradient-text">+<?= $play_result['xp_earned'] ?> XP Earned!</p>
            <?php if ($play_result['xp_earned'] >= 100): ?>
                <script>fireConfetti(80);</script>
            <?php endif; ?>
            <a href="index.php?page=mini-games" class="tw-btn tw-btn-primary mt-4">
                <i class="fas fa-gamepad"></i>
                More Games
            </a>
        </div>
    <?php endif; ?>

    <div class="tw-card">
        <div class="tw-card-body" id="game-area">
            <?php if ($game['type'] === 'syntax_speed'): ?>
                <!-- Syntax Speed Game -->
                <div id="syntax-game">
                    <div class="flex items-center justify-between mb-6">
                        <div class="text-lg font-bold">Score: <span id="score" style="color:#9147FF;">0</span></div>
                        <div class="text-lg font-bold">Time: <span id="timer" style="color:#E9197B;"><?= $game_data['time_limit'] ?? 30 ?></span>s</div>
                        <div class="text-lg font-bold">Level: <span id="level" style="color:#00D95A;">1</span></div>
                    </div>

                    <div class="text-center mb-6">
                        <p class="text-sm text-twitch-muted mb-2">Type the correct code:</p>
                        <div id="prompt" class="text-xl font-bold font-mono p-4 bg-twitch-medium rounded-lg border border-twitch-border" style="min-height:60px;">
                            Loading...
                        </div>
                    </div>

                    <div class="mb-4">
                        <input type="text" id="answer-input" class="w-full p-4 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text font-mono text-lg focus:outline-none focus:border-twitch-purple" placeholder="Type your answer here..." autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
                    </div>

                    <div class="flex items-center gap-3">
                        <button id="start-btn" onclick="startSyntaxGame()" class="tw-btn tw-btn-primary tw-btn-lg">
                            <i class="fas fa-play"></i> Start Game
                        </button>
                        <div id="result" class="text-sm font-bold"></div>
                    </div>
                </div>

                <script>
                const questions = <?= json_encode($game_data['questions'] ?? []) ?>;
                let currentQuestion = 0;
                let score = 0;
                let timeLeft = <?= $game_data['time_limit'] ?? 30 ?>;
                let timerInterval = null;
                let gameActive = false;
                let level = 1;

                const promptEl = document.getElementById('prompt');
                const answerInput = document.getElementById('answer-input');
                const scoreEl = document.getElementById('score');
                const timerEl = document.getElementById('timer');
                const levelEl = document.getElementById('level');
                const resultEl = document.getElementById('result');
                const startBtn = document.getElementById('start-btn');

                answerInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && gameActive) {
                        checkAnswer();
                    }
                });

                function startSyntaxGame() {
                    if (questions.length === 0) {
                        resultEl.textContent = 'No questions available!';
                        resultEl.style.color = '#E9197B';
                        return;
                    }

                    score = 0;
                    currentQuestion = 0;
                    level = 1;
                    timeLeft = <?= $game_data['time_limit'] ?? 30 ?>;
                    gameActive = true;

                    startBtn.disabled = true;
                    startBtn.style.opacity = '0.5';
                    answerInput.disabled = false;
                    answerInput.focus();

                    timerInterval = setInterval(() => {
                        timeLeft--;
                        timerEl.textContent = timeLeft;

                        if (timeLeft <= 0) {
                            endSyntaxGame();
                        }

                        if (timeLeft <= 5) {
                            timerEl.style.color = '#E9197B';
                            timerEl.style.animation = 'pulse-ring 1s infinite';
                        }
                    }, 1000);

                    showQuestion();
                }

                function showQuestion() {
                    if (currentQuestion >= questions.length) {
                        endSyntaxGame();
                        return;
                    }

                    const q = questions[currentQuestion];
                    promptEl.textContent = q.prompt || 'Write the code for:';
                    answerInput.value = '';
                    answerInput.focus();
                    resultEl.textContent = '';

                    // Level up every 3 questions
                    level = Math.floor(currentQuestion / 3) + 1;
                    levelEl.textContent = level;
                }

                function checkAnswer() {
                    const answer = answerInput.value.trim();
                    const q = questions[currentQuestion];
                    const correct = q.answer || '';

                    if (answer.toLowerCase() === correct.toLowerCase()) {
                        const points = 10 * level;
                        score += points;
                        scoreEl.textContent = score;
                        resultEl.textContent = `✅ Correct! +${points}`;
                        resultEl.style.color = '#00D95A';
                        currentQuestion++;
                        setTimeout(showQuestion, 500);
                    } else {
                        resultEl.textContent = `❌ Try again! Hint: ${q.hint || 'Check syntax'}`;
                        resultEl.style.color = '#E9197B';
                        answerInput.value = '';
                        answerInput.focus();
                    }
                }

                function endSyntaxGame() {
                    gameActive = false;
                    clearInterval(timerInterval);
                    answerInput.disabled = true;
                    startBtn.disabled = false;
                    startBtn.style.opacity = '1';
                    timerEl.style.animation = '';

                    const timeTaken = <?= $game_data['time_limit'] ?? 30 ?> - timeLeft;

                    // Submit score
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="submit_score" value="1">
                        <input type="hidden" name="score" value="${score}">
                        <input type="hidden" name="time_taken" value="${timeTaken}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
                </script>

            <?php elseif ($game['type'] === 'output_prediction'): ?>
                <!-- Output Prediction Game -->
                <div id="prediction-game">
                    <?php $questions = $game_data['questions'] ?? []; ?>
                    <div id="prediction-area">
                        <div class="text-center mb-6">
                            <p class="text-lg font-bold mb-4">Question <span id="q-num">1</span>/<?= count($questions) ?></p>
                            <pre id="code-display" class="text-left p-6 bg-twitch-medium rounded-lg border border-twitch-border font-mono text-sm overflow-x-auto" style="min-height:100px;"><?= htmlspecialchars($questions[0]['code'] ?? '') ?></pre>
                        </div>

                        <div id="options" class="grid grid-cols-2 gap-4 mb-6">
                            <?php foreach (($questions[0]['options'] ?? []) as $i => $opt): ?>
                                <button class="tw-card tw-card-body option-btn" data-index="<?= $i ?>" style="text-align:center; cursor:pointer;" onclick="selectOption(this, <?= $i ?>)">
                                    <?= htmlspecialchars($opt) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <div class="flex items-center justify-between">
                            <span id="pred-result" class="text-sm font-bold"></span>
                            <button id="next-btn" class="tw-btn tw-btn-primary" onclick="nextPrediction()" style="display:none;">
                                Next <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <script>
                const predQuestions = <?= json_encode($questions) ?>;
                let predIndex = 0;
                let predScore = 0;
                let startTime = Date.now();

                function selectOption(el, index) {
                    const correct = predQuestions[predIndex].correct;
                    const btns = document.querySelectorAll('.option-btn');

                    btns.forEach(b => {
                        b.style.borderColor = '';
                        b.style.background = '';
                        b.onclick = null;
                    });

                    if (index === correct) {
                        el.style.borderColor = '#00D95A';
                        el.style.background = 'rgba(0,217,90,0.1)';
                        predScore += 100;
                        document.getElementById('pred-result').textContent = '✅ Correct! +100';
                        document.getElementById('pred-result').style.color = '#00D95A';
                    } else {
                        el.style.borderColor = '#E9197B';
                        el.style.background = 'rgba(233,25,123,0.1)';
                        btns[correct].style.borderColor = '#00D95A';
                        btns[correct].style.background = 'rgba(0,217,90,0.1)';
                        document.getElementById('pred-result').textContent = `❌ Wrong! Correct was: ${predQuestions[predIndex].options[correct]}`;
                        document.getElementById('pred-result').style.color = '#E9197B';
                    }

                    document.getElementById('next-btn').style.display = 'flex';
                }

                function nextPrediction() {
                    predIndex++;

                    if (predIndex >= predQuestions.length) {
                        const timeTaken = Math.floor((Date.now() - startTime) / 1000);
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.innerHTML = `
                            <input type="hidden" name="submit_score" value="1">
                            <input type="hidden" name="score" value="${predScore}">
                            <input type="hidden" name="time_taken" value="${timeTaken}">
                        `;
                        document.body.appendChild(form);
                        form.submit();
                        return;
                    }

                    const q = predQuestions[predIndex];
                    document.getElementById('q-num').textContent = predIndex + 1;
                    document.getElementById('code-display').textContent = q.code;
                    document.getElementById('pred-result').textContent = '';
                    document.getElementById('next-btn').style.display = 'none';

                    const optionsDiv = document.getElementById('options');
                    optionsDiv.innerHTML = '';
                    q.options.forEach((opt, i) => {
                        const btn = document.createElement('button');
                        btn.className = 'tw-card tw-card-body option-btn';
                        btn.style.cssText = 'text-align:center; cursor:pointer;';
                        btn.textContent = opt;
                        btn.onclick = () => selectOption(btn, i);
                        optionsDiv.appendChild(btn);
                    });
                }
                </script>

            <?php else: ?>
                <!-- Generic game fallback -->
                <div style="text-align:center; padding:60px 20px;">
                    <i class="fas fa-gamepad" style="font-size:64px; color:#9147FF; margin-bottom:16px;"></i>
                    <h3 class="text-xl font-bold mb-2">Ready to Play?</h3>
                    <p class="text-twitch-muted mb-6">This game type is being prepared. Check back soon!</p>
                    <a href="index.php?page=mini-games" class="tw-btn tw-btn-primary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Games
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
