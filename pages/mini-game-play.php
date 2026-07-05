<?php
$page_title = "Mini-Game";
$game_id = (int) ($_GET["id"] ?? 0);

$stmt = $pdo->prepare(
    "SELECT * FROM mini_games WHERE id = ? AND is_active = TRUE",
);
$stmt->execute([$game_id]);
$game = $stmt->fetch();

if (!$game) {
    header("Location: index.php?page=mini-games");
    exit();
}

$game_data = json_decode($game["game_data"], true);

// Handle score submission
$play_result = null;
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_score"])) {
    $score = (int) ($_POST["score"] ?? 0);
    $time_taken = (int) ($_POST["time_taken"] ?? 0);
    $play_result = play_mini_game(
        $game_id,
        $_SESSION["user_id"],
        $score,
        $time_taken,
    );
}
?>
<div style="animation: fade-in 0.5s ease-out;">
    <div class="flex items-center gap-3 mb-6">
        <a href="index.php?page=mini-games" class="tw-btn tw-btn-ghost">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold"><?= htmlspecialchars(
                $game["title"],
            ) ?></h1>
            <p class="text-twitch-muted text-sm"><?= htmlspecialchars(
                $game["description"],
            ) ?></p>
        </div>
    </div>

    <?php if ($play_result && $play_result["success"]): ?>
        <div class="tw-card tw-card-body mb-6" style="border-color:rgba(0,217,90,0.3); background:rgba(0,217,90,0.1); text-align:center; padding:40px;">
            <i class="fas fa-trophy" style="font-size:48px; color:#FFD700; margin-bottom:12px;"></i>
            <h2 class="text-2xl font-bold mb-2">Game Complete!</h2>
            <p class="text-twitch-muted mb-2">Score: <span class="font-bold text-white"><?= $play_result[
                "score"
            ] ?></span></p>
            <p class="text-twitch-muted mb-4">Time: <span class="font-bold text-white"><?= $play_result[
                "time_taken"
            ] ?>s</span></p>
            <p class="text-lg font-bold gradient-text">+<?= $play_result[
                "xp_earned"
            ] ?> XP Earned!</p>
            <?php if ($play_result["xp_earned"] >= 100): ?>
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
            <?php if ($game["type"] === "syntax_speed"): ?>
                <!-- Syntax Speed Game -->
                <div id="syntax-game">
                    <div class="flex items-center justify-between mb-6">
                        <div class="text-lg font-bold">Score: <span id="score" style="color:#9147FF;">0</span></div>
                        <div class="text-lg font-bold">Time: <span id="timer" style="color:#E9197B;"><?= $game_data[
                            "time_limit"
                        ] ?? 30 ?></span>s</div>
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
                const questions = <?= json_encode(
                    $game_data["questions"] ?? [],
                ) ?>;
                let currentQuestion = 0;
                let score = 0;
                let timeLeft = <?= $game_data["time_limit"] ?? 30 ?>;
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
                    timeLeft = <?= $game_data["time_limit"] ?? 30 ?>;
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

                    const timeTaken = <?= $game_data["time_limit"] ??
                        30 ?> - timeLeft;

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

            <?php elseif ($game["type"] === "output_prediction"): ?>
                <!-- Output Prediction Game -->
                <div id="prediction-game">
                    <?php $questions = $game_data["questions"] ?? []; ?>
                    <div id="prediction-area">
                        <div class="text-center mb-6">
                            <p class="text-lg font-bold mb-4">Question <span id="q-num">1</span>/<?= count(
                                $questions,
                            ) ?></p>
                            <pre id="code-display" class="text-left p-6 bg-twitch-medium rounded-lg border border-twitch-border font-mono text-sm overflow-x-auto" style="min-height:100px;"><?= htmlspecialchars(
                                $questions[0]["code"] ?? "",
                            ) ?></pre>
                        </div>

                        <div id="options" class="grid grid-cols-2 gap-4 mb-6">
                            <?php foreach (
                                $questions[0]["options"] ?? []
                                as $i => $opt
                            ): ?>
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

            <?php elseif ($game["type"] === "bug_hunt"): ?>
                <!-- Bug Hunt Game -->
                <div id="bug-hunt-game">
                    <div class="flex items-center justify-between mb-6">
                        <div class="text-lg font-bold">Score: <span id="bh-score" style="color:#9147FF;">0</span></div>
                        <div class="text-lg font-bold">Bugs Fixed: <span id="bh-fixed" style="color:#00D95A;">0</span>/<?= count(
                            $game_data["bugs"] ?? [],
                        ) ?></div>
                        <div class="text-lg font-bold">Time: <span id="bh-timer" style="color:#E9197B;"><?= $game_data[
                            "time_limit"
                        ] ?? 120 ?></span>s</div>
                    </div>

                    <div class="text-center mb-6">
                        <p class="text-sm text-twitch-muted mb-2">Find and fix the bug in this code:</p>
                        <div id="bug-code" class="text-left p-6 bg-twitch-medium rounded-lg border border-twitch-border font-mono text-sm overflow-x-auto" style="min-height:80px; white-space:pre-wrap;">
                            Loading...
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-twitch-muted mb-2">Type the fixed code:</label>
                        <textarea id="bug-fix-input" class="w-full p-4 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text font-mono text-sm focus:outline-none focus:border-twitch-purple" rows="4" placeholder="Enter the corrected code..." autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"></textarea>
                    </div>

                    <div class="flex items-center gap-3">
                        <button id="bh-start-btn" onclick="startBugHunt()" class="tw-btn tw-btn-primary tw-btn-lg">
                            <i class="fas fa-play"></i> Start Hunt
                        </button>
                        <button id="bh-check-btn" onclick="checkBugFix()" class="tw-btn tw-btn-secondary tw-btn-lg" style="display:none;">
                            <i class="fas fa-check"></i> Check Fix
                        </button>
                        <div id="bh-result" class="text-sm font-bold"></div>
                    </div>
                </div>

                <script>
                const bugs = <?= json_encode($game_data["bugs"] ?? []) ?>;
                let currentBug = 0;
                let bhScore = 0;
                let bhFixed = 0;
                let bhTimeLeft = <?= $game_data["time_limit"] ?? 120 ?>;
                let bhTimerInterval = null;
                let bhGameActive = false;

                const bugCodeEl = document.getElementById('bug-code');
                const bugFixInput = document.getElementById('bug-fix-input');
                const bhScoreEl = document.getElementById('bh-score');
                const bhFixedEl = document.getElementById('bh-fixed');
                const bhTimerEl = document.getElementById('bh-timer');
                const bhResultEl = document.getElementById('bh-result');
                const bhStartBtn = document.getElementById('bh-start-btn');
                const bhCheckBtn = document.getElementById('bh-check-btn');

                bugFixInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && e.ctrlKey && bhGameActive) {
                        checkBugFix();
                    }
                });

                function startBugHunt() {
                    if (bugs.length === 0) {
                        bhResultEl.textContent = 'No bugs available!';
                        bhResultEl.style.color = '#E9197B';
                        return;
                    }

                    bhScore = 0;
                    currentBug = 0;
                    bhFixed = 0;
                    bhTimeLeft = <?= $game_data["time_limit"] ?? 120 ?>;
                    bhGameActive = true;

                    bhStartBtn.disabled = true;
                    bhStartBtn.style.opacity = '0.5';
                    bhCheckBtn.style.display = 'flex';
                    bugFixInput.disabled = false;
                    bugFixInput.focus();

                    bhTimerInterval = setInterval(() => {
                        bhTimeLeft--;
                        bhTimerEl.textContent = bhTimeLeft;

                        if (bhTimeLeft <= 0) {
                            endBugHunt();
                        }

                        if (bhTimeLeft <= 10) {
                            bhTimerEl.style.color = '#E9197B';
                            bhTimerEl.style.animation = 'pulse-ring 1s infinite';
                        }
                    }, 1000);

                    showBug();
                }

                function showBug() {
                    if (currentBug >= bugs.length) {
                        endBugHunt();
                        return;
                    }

                    const bug = bugs[currentBug];
                    bugCodeEl.textContent = bug.code || 'No code provided';
                    bugFixInput.value = '';
                    bugFixInput.focus();
                    bhResultEl.textContent = '';
                }

                function checkBugFix() {
                    const fix = bugFixInput.value.trim();
                    const bug = bugs[currentBug];
                    const correct = (bug.fix || '').trim();

                    if (fix.toLowerCase() === correct.toLowerCase()) {
                        const points = 50;
                        bhScore += points;
                        bhFixed++;
                        bhScoreEl.textContent = bhScore;
                        bhFixedEl.textContent = bhFixed;
                        bhResultEl.textContent = '\u2705 Correct! +' + points;
                        bhResultEl.style.color = '#00D95A';
                        currentBug++;
                        setTimeout(showBug, 800);
                    } else {
                        bhResultEl.textContent = '\u274c Not quite! Hint: ' + (bug.hint || 'Look carefully at the syntax');
                        bhResultEl.style.color = '#E9197B';
                        bugFixInput.value = '';
                        bugFixInput.focus();
                    }
                }

                function endBugHunt() {
                    bhGameActive = false;
                    clearInterval(bhTimerInterval);
                    bugFixInput.disabled = true;
                    bhCheckBtn.style.display = 'none';
                    bhStartBtn.disabled = false;
                    bhStartBtn.style.opacity = '1';
                    bhTimerEl.style.animation = '';

                    const timeTaken = <?= $game_data["time_limit"] ??
                        120 ?> - bhTimeLeft;

                    // Bonus points for fixing all bugs
                    if (bhFixed >= bugs.length) {
                        bhScore += 200;
                        bhResultEl.textContent = '\uD83C\uDFC6 All bugs fixed! +200 bonus!';
                        bhResultEl.style.color = '#FFD700';
                    }

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="submit_score" value="1">
                        <input type="hidden" name="score" value="${bhScore}">
                        <input type="hidden" name="time_taken" value="${timeTaken}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
                </script>

            <?php elseif ($game["type"] === "code_race"): ?>
                <!-- Code Race Game -->
                <div id="code-race-game">
                    <div class="flex items-center justify-between mb-6">
                        <div class="text-lg font-bold">Score: <span id="cr-score" style="color:#9147FF;">0</span></div>
                        <div class="text-lg font-bold">Round: <span id="cr-round">1</span>/<?= count(
                            $game_data["rounds"] ?? [],
                        ) ?></div>
                        <div class="text-lg font-bold">Time: <span id="cr-timer" style="color:#E9197B;"><?= $game_data[
                            "time_limit"
                        ] ?? 60 ?></span>s</div>
                    </div>

                    <div class="text-center mb-6">
                        <p class="text-sm text-twitch-muted mb-2">Type this code as fast as you can:</p>
                        <pre id="cr-target" class="text-left p-6 bg-twitch-dark rounded-lg border border-twitch-border font-mono text-sm overflow-x-auto" style="min-height:80px;">Loading...</pre>
                    </div>

                    <div class="mb-4">
                        <textarea id="cr-input" class="w-full p-4 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text font-mono text-sm focus:outline-none focus:border-twitch-purple" rows="4" placeholder="Type the code exactly as shown..." autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" disabled></textarea>
                    </div>

                    <div class="flex items-center gap-3">
                        <button id="cr-start-btn" onclick="startCodeRace()" class="tw-btn tw-btn-primary tw-btn-lg">
                            <i class="fas fa-flag-checkered"></i> Start Race
                        </button>
                        <div id="cr-result" class="text-sm font-bold"></div>
                    </div>
                </div>

                <script>
                const crRounds = <?= json_encode($game_data["rounds"] ?? []) ?>;
                let crCurrentRound = 0;
                let crScore = 0;
                let crTimeLeft = <?= $game_data["time_limit"] ?? 60 ?>;
                let crTimerInterval = null;
                let crGameActive = false;

                const crTargetEl = document.getElementById('cr-target');
                const crInput = document.getElementById('cr-input');
                const crScoreEl = document.getElementById('cr-score');
                const crRoundEl = document.getElementById('cr-round');
                const crTimerEl = document.getElementById('cr-timer');
                const crResultEl = document.getElementById('cr-result');
                const crStartBtn = document.getElementById('cr-start-btn');

                crInput.addEventListener('input', function() {
                    if (!crGameActive) return;
                    const target = crRounds[crCurrentRound]?.code || '';
                    const typed = this.value;

                    if (typed === target) {
                        // Round complete!
                        const roundTime = <?= $game_data["time_limit"] ??
                            60 ?> - crTimeLeft;
                        const accuracy = Math.round((typed.length / target.length) * 100);
                        const points = Math.round(100 * (1 - roundTime / <?= $game_data[
                            "time_limit"
                        ] ?? 60 ?>) * (accuracy / 100));
                        crScore += Math.max(10, points);
                        crScoreEl.textContent = crScore;
                        crCurrentRound++;

                        if (crCurrentRound >= crRounds.length) {
                            endCodeRace();
                        } else {
                            crInput.value = '';
                            crRoundEl.textContent = crCurrentRound + 1;
                            crTargetEl.textContent = crRounds[crCurrentRound].code;
                            crResultEl.textContent = '✅ Round complete! +' + Math.max(10, points);
                            crResultEl.style.color = '#00D95A';
                        }
                    }

                    // Highlight matching text
                    const matchLen = getMatchLength(typed, target);
                    if (matchLen < typed.length) {
                        crInput.style.borderColor = '#E9197B';
                    } else {
                        crInput.style.borderColor = '#00D95A';
                    }
                });

                function getMatchLength(a, b) {
                    let i = 0;
                    while (i < a.length && i < b.length && a[i] === b[i]) i++;
                    return i;
                }

                function startCodeRace() {
                    if (crRounds.length === 0) {
                        crResultEl.textContent = 'No rounds available!';
                        crResultEl.style.color = '#E9197B';
                        return;
                    }

                    crScore = 0;
                    crCurrentRound = 0;
                    crTimeLeft = <?= $game_data["time_limit"] ?? 60 ?>;
                    crGameActive = true;

                    crStartBtn.disabled = true;
                    crStartBtn.style.opacity = '0.5';
                    crInput.disabled = false;
                    crInput.value = '';
                    crInput.focus();

                    crTimerInterval = setInterval(() => {
                        crTimeLeft--;
                        crTimerEl.textContent = crTimeLeft;

                        if (crTimeLeft <= 0) {
                            endCodeRace();
                        }

                        if (crTimeLeft <= 10) {
                            crTimerEl.style.color = '#E9197B';
                            crTimerEl.style.animation = 'pulse-ring 1s infinite';
                        }
                    }, 1000);

                    crTargetEl.textContent = crRounds[0].code || 'Write code here';
                    crResultEl.textContent = 'Go!';
                    crResultEl.style.color = '#00D95A';
                }

                function endCodeRace() {
                    crGameActive = false;
                    clearInterval(crTimerInterval);
                    crInput.disabled = true;
                    crStartBtn.disabled = false;
                    crStartBtn.style.opacity = '1';
                    crTimerEl.style.animation = '';
                    crInput.style.borderColor = '';

                    const timeTaken = <?= $game_data["time_limit"] ??
                        60 ?> - crTimeLeft;

                    if (crScore > 0) {
                        crResultEl.textContent = '🏁 Race finished! Score: ' + crScore;
                        crResultEl.style.color = '#FFD700';
                    }

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="submit_score" value="1">
                        <input type="hidden" name="score" value="${crScore}">
                        <input type="hidden" name="time_taken" value="${timeTaken}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
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
