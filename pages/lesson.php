<?php
$page_title = "Lesson";
$lesson_id = (int) ($_GET["id"] ?? 0);

// Handle AI generate lesson
if (isset($_GET["generate"]) && $lesson_id === 0) {
    $lang_id = (int) ($_GET["language"] ?? 1);
    $diff = $_GET["difficulty"] ?? "beginner";
    $result = generate_ai_lesson($lang_id, $diff);
    if (!isset($result["error"])) {
        header("Location: index.php?page=lesson&id=" . $result["lesson_id"]);
        exit();
    }
    // If generation failed, redirect back with message
    $_SESSION["flash_message"] =
        $result["error"] ?? "Failed to generate lesson";
    header("Location: index.php?page=lessons&language=" . $lang_id);
    exit();
}

$lesson = get_lesson_by_id($lesson_id);

if (!$lesson) {
    header("Location: index.php?page=lessons");
    exit();
}

$is_completed = is_lesson_completed($_SESSION["user_id"], $lesson_id);

// Get user's previous code
$stmt = $pdo->prepare(
    "SELECT code_submitted FROM user_progress WHERE user_id = ? AND lesson_id = ?",
);
$stmt->execute([$_SESSION["user_id"], $lesson_id]);
$previous_code =
    $stmt->fetchColumn() ?:
    ($lesson["starter_code"] ?:
    $lesson["code_template"] ?:
    "");

// Handle lesson completion
$result_message = "";
$result_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_code"])) {
    $submitted_code = $_POST["code"] ?? "";

    if (!empty($submitted_code)) {
        $result = complete_lesson(
            $_SESSION["user_id"],
            $lesson_id,
            $submitted_code,
        );

        if ($result && !isset($result["error"])) {
            $result_message = "🎉 Congratulations! You earned {$result["xp_earned"]} XP!";
            if ($result["streak_bonus"] > 0) {
                $result_message .= " (Streak bonus: +{$result["streak_bonus"]} XP)";
            }
            if ($result["level_up"]) {
                $result_message .= " 🚀 Level up! You're now Level {$result["level_up"]}!";
            }
            if (!empty($result["new_badges"])) {
                $result_message .= " 🏆 New badge earned!";
                fireConfetti(50);
            }
            $result_type = "success";
            $is_completed = true;
        } else {
            $result_message =
                $result["error"] ?? "Code submitted! Keep practicing.";
            $result_type = "info";
        }
    } else {
        $result_message = "Please write some code before submitting!";
        $result_type = "error";
    }
}

// Navigation
$stmt = $pdo->prepare(
    "SELECT id, title FROM lessons WHERE language_id = ? AND order_num < ? ORDER BY order_num DESC LIMIT 1",
);
$stmt->execute([$lesson["language_id"], $lesson["order_num"]]);
$prev_lesson = $stmt->fetch();

$stmt = $pdo->prepare(
    "SELECT id, title FROM lessons WHERE language_id = ? AND order_num > ? ORDER BY order_num ASC LIMIT 1",
);
$stmt->execute([$lesson["language_id"], $lesson["order_num"]]);
$next_lesson = $stmt->fetch();

// Language-specific editor config
$monaco_languages = [
    "rust" => "rust",
    "python" => "python",
    "javascript" => "javascript",
    "typescript" => "typescript",
    "go" => "go",
    "java" => "java",
    "cpp" => "cpp",
    "c" => "c",
];
$editor_lang = $monaco_languages[$lesson["language_slug"]] ?? "rust";
?>
<div style="animation: fade-in 0.5s ease-out;">
    <!-- Lesson Header -->
    <div class="tw-card mb-6">
        <div class="tw-card-body">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-3">
                    <a href="index.php?page=lessons&language=<?= $lesson[
                        "language_id"
                    ] ?>" class="tw-btn tw-btn-ghost tw-btn-sm">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold"><?= htmlspecialchars(
                            $lesson["title"],
                        ) ?></h1>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="lang-pill" style="background:<?= $lesson[
                                "language_color"
                            ] ?>20; color:<?= $lesson[
    "language_color"
] ?>; border:1px solid <?= $lesson["language_color"] ?>40;">
                                <i class="<?= $lesson["language_icon"] ?>"></i>
                                <?= $lesson["language_name"] ?>
                            </span>
                            <?= get_difficulty_badge($lesson["difficulty"]) ?>
                            <span class="text-xs text-twitch-muted"><i class="fas fa-star" style="color:#A970FF;"></i> <?= $lesson[
                                "xp_reward"
                            ] ?> XP</span>
                        </div>
                    </div>
                </div>

                <?php if ($is_completed): ?>
                    <div class="flex items-center gap-2 px-4 py-2 rounded-lg" style="background:rgba(0,217,90,0.1); border:1px solid rgba(0,217,90,0.3);">
                        <i class="fas fa-check-circle" style="color:#00D95A;"></i>
                        <span class="text-sm font-bold" style="color:#00D95A;">Completed</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Result Message -->
    <?php if ($result_message): ?>
        <div class="tw-card tw-card-body mb-6" style="<?= $result_type ===
        "success"
            ? "border-color:rgba(0,217,90,0.3); background:rgba(0,217,90,0.1);"
            : ($result_type === "error"
                ? "border-color:rgba(233,25,123,0.3); background:rgba(233,25,123,0.1);"
                : "border-color:rgba(145,71,255,0.3); background:rgba(145,71,255,0.1);") ?>">
            <div class="flex items-center gap-2" style="color:<?= $result_type ===
            "success"
                ? "#00D95A"
                : ($result_type === "error"
                    ? "#E9197B"
                    : "#9147FF") ?>;">
                <i class="fas fa-<?= $result_type === "success"
                    ? "check-circle"
                    : ($result_type === "error"
                        ? "exclamation-circle"
                        : "info-circle") ?>"></i>
                <span class="font-medium"><?= $result_message ?></span>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Lesson Content -->
        <div class="space-y-6">
            <div class="tw-card">
                <div class="tw-card-header">
                    <h2 class="font-bold"><i class="fas fa-book-open mr-2" style="color:#9147FF;"></i> Lesson Content</h2>
                </div>
                <div class="tw-card-body">
                    <div class="text-sm leading-relaxed whitespace-pre-line text-twitch-text"><?= htmlspecialchars(
                        $lesson["content"],
                    ) ?></div>

                    <?php if (!empty($lesson["hints"])): ?>
                        <div class="mt-4 p-4 rounded-lg" style="background:rgba(145,71,255,0.1); border:1px solid rgba(145,71,255,0.2);">
                            <h3 class="font-bold mb-2" style="color:#A970FF;"><i class="fas fa-lightbulb mr-2"></i> Hints</h3>
                            <div class="text-sm text-twitch-muted whitespace-pre-line"><?= htmlspecialchars(
                                $lesson["hints"],
                            ) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($lesson["expected_output"]): ?>
                        <div class="mt-4 p-4 rounded-lg" style="background:rgba(0,217,90,0.05); border:1px solid rgba(0,217,90,0.15);">
                            <h3 class="font-bold mb-2" style="color:#00D95A;"><i class="fas fa-terminal mr-2"></i> Expected Output</h3>
                            <pre class="text-sm font-mono p-3 rounded" style="background:#0E0E10; border:1px solid #2D2D35; overflow-x:auto;"><?= htmlspecialchars(
                                $lesson["expected_output"],
                            ) ?></pre>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Navigation -->
            <div class="tw-card">
                <div class="tw-card-body">
                    <div class="flex items-center justify-between">
                        <?php if ($prev_lesson): ?>
                            <a href="index.php?page=lesson&id=<?= $prev_lesson[
                                "id"
                            ] ?>" class="tw-btn tw-btn-ghost">
                                <i class="fas fa-chevron-left mr-2"></i>
                                <div class="text-left">
                                    <div class="text-xs text-twitch-muted">Previous</div>
                                    <div class="text-sm font-medium"><?= htmlspecialchars(
                                        substr($prev_lesson["title"], 0, 30),
                                    ) ?></div>
                                </div>
                            </a>
                        <?php else: ?>
                            <div></div>
                        <?php endif; ?>

                        <?php if ($next_lesson): ?>
                            <a href="index.php?page=lesson&id=<?= $next_lesson[
                                "id"
                            ] ?>" class="tw-btn tw-btn-primary">
                                <div class="text-right">
                                    <div class="text-xs opacity-75">Next</div>
                                    <div class="text-sm font-medium"><?= htmlspecialchars(
                                        substr($next_lesson["title"], 0, 30),
                                    ) ?></div>
                                </div>
                                <i class="fas fa-chevron-right ml-2"></i>
                            </a>
                        <?php else: ?>
                            <a href="index.php?page=lessons" class="tw-btn tw-btn-primary">
                                <i class="fas fa-list mr-2"></i>
                                All Lessons
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Code Editor -->
        <div class="tw-card">
            <div class="tw-card-header">
                <h2 class="font-bold"><i class="fas fa-code mr-2" style="color:#A970FF;"></i> Code Editor</h2>
                <div class="flex items-center gap-2">
                    <span class="lang-pill" style="background:<?= $lesson[
                        "language_color"
                    ] ?>20; color:<?= $lesson[
    "language_color"
] ?>; border:1px solid <?= $lesson["language_color"] ?>40; font-size:10px;">
                        <i class="<?= $lesson["language_icon"] ?>"></i>
                        <?= $lesson["language_name"] ?>
                    </span>
                </div>
            </div>
            <div class="tw-card-body">
                <form method="POST" id="lesson-form">
                    <div class="mb-4">
                        <div id="code-editor" class="border border-twitch-border rounded-lg overflow-hidden" style="height: 400px;"></div>
                        <textarea name="code" id="code-textarea" class="hidden"><?= htmlspecialchars(
                            $previous_code,
                        ) ?></textarea>
                    </div>

                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="runCode()" class="tw-btn tw-btn-secondary tw-btn-sm">
                                <i class="fas fa-play mr-1" style="color:#00D95A;"></i>
                                Test Run
                            </button>
                            <button type="button" onclick="resetCode()" class="tw-btn tw-btn-ghost tw-btn-sm">
                                <i class="fas fa-undo"></i>
                            </button>
                            <button type="button" onclick="formatCode()" class="tw-btn tw-btn-ghost tw-btn-sm">
                                <i class="fas fa-magic"></i>
                            </button>
                        </div>

                        <button type="submit" name="submit_code" class="tw-btn tw-btn-primary">
                            <i class="fas fa-check mr-1"></i>
                            <?= $is_completed
                                ? "Resubmit"
                                : "Submit Solution" ?>
                        </button>
                    </div>
                </form>

                <!-- Output -->
                <div id="output-area" class="mt-4 p-4 rounded-lg" style="background:#0E0E10; border:1px solid #2D2D35; display:none;">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-terminal" style="color:#00D95A;"></i>
                        <span class="text-sm font-bold" style="color:#00D95A;">Output</span>
                    </div>
                    <pre id="output-content" class="text-sm font-mono" style="color:#ADADB8; white-space:pre-wrap;"></pre>
                </div>

                <!-- Tips -->
                <div class="mt-4 p-4 rounded-lg" style="background:rgba(145,71,255,0.05); border:1px solid rgba(145,71,255,0.15);">
                    <h3 class="text-sm font-bold mb-2" style="color:#A970FF;"><i class="fas fa-lightbulb mr-2"></i> Tips</h3>
                    <ul class="text-xs text-twitch-muted space-y-1">
                        <li>• Use <kbd class="px-1 rounded" style="background:#2D2D35;">Ctrl+S</kbd> to save your progress</li>
                        <li>• Press <kbd class="px-1 rounded" style="background:#2D2D35;">Ctrl+Enter</kbd> to run your code</li>
                        <li>• Check the expected output for guidance</li>
                        <li>• Don't worry about perfection - learning is iterative!</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs/loader.min.js"></script>
<script>
let editor;
const originalCode = `<?= addslashes(
    $lesson["code_template"] ?? ($lesson["starter_code"] ?? ""),
) ?>`;
const storageKey = 'rustnite_lesson_<?= $lesson_id ?>';
const editorLanguage = '<?= $editor_lang ?>';

require.config({ paths: { vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs' } });
require(['vs/editor/editor.main'], function () {
    <?php
    // Map monaco language identifiers
    $monaco_theme_map = [
        "rust" => "vs-dark",
        "python" => "vs-dark",
        "javascript" => "vs-dark",
        "typescript" => "vs-dark",
        "go" => "vs-dark",
        "java" => "vs-dark",
        "cpp" => "vs-dark",
        "c" => "vs-dark",
    ];
    $theme = $monaco_theme_map[$editor_lang] ?? "vs-dark";
    ?>

    editor = monaco.editor.create(document.getElementById('code-editor'), {
        value: `<?= addslashes($previous_code) ?>`,
        language: editorLanguage,
        theme: '<?= $theme ?>',
        fontSize: 14,
        minimap: { enabled: false },
        scrollBeyondLastLine: false,
        automaticLayout: true,
        wordWrap: 'on',
        lineNumbers: 'on',
        folding: true,
        roundedSelection: false,
        readOnly: false,
        cursorStyle: 'line',
        smoothScrolling: true,
        cursorBlinking: 'smooth',
        cursorSmoothCaretAnimation: true
    });

    editor.onDidChangeModelContent(() => {
        const code = editor.getValue();
        localStorage.setItem(storageKey, code);
        document.getElementById('code-textarea').value = code;
    });

    editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.Enter, runCode);
    editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, function() {
        showToast('Code saved!', 'success');
    });
});

async function runCode() {
    const code = editor.getValue();
    const outputArea = document.getElementById('output-area');
    const outputContent = document.getElementById('output-content');

    outputArea.style.display = 'block';
    outputContent.textContent = 'Running your code...\n';
    outputContent.style.color = '#ADADB8';

    try {
        const response = await fetch('api/execute.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                code: code,
                lesson_id: <?= $lesson_id ?>,
                language: '<?= $lesson["language_slug"] ?>'
            })
        });

        const result = await response.json();

        if (result.error) {
            outputContent.textContent = 'Error: ' + result.error;
            outputContent.style.color = '#E9197B';
        } else {
            let output = '';

            if (result.stderr && result.stderr.trim()) {
                output += '=== Compilation Errors ===\n' + result.stderr + '\n\n';
                outputContent.style.color = '#E9197B';
            } else {
                outputContent.style.color = '#00D95A';
            }

            if (result.output && result.output.trim()) {
                if (result.stderr && result.stderr.trim()) {
                    output += '=== Program Output ===\n';
                }
                output += result.output;
            }

            if (result.execution_time) {
                output += '\n--- Execution Info ---\nExecution time: ' + result.execution_time;
            }

            outputContent.textContent = output || 'No output generated.';
        }
    } catch (error) {
        outputContent.textContent = 'Network error: ' + error.message;
        outputContent.style.color = '#E9197B';
    }
}

function resetCode() {
    if (confirm('Reset your code to the original template?')) {
        editor.setValue(originalCode);
        localStorage.removeItem(storageKey);
        showToast('Code reset to template', 'info');
    }
}

function formatCode() {
    editor.getAction('editor.action.formatDocument').run();
    showToast('Code formatted!', 'success');
}

document.addEventListener('DOMContentLoaded', function() {
    const savedCode = localStorage.getItem(storageKey);
    if (savedCode && !<?= $is_completed ? "true" : "false" ?>) {
        setTimeout(() => {
            if (editor) editor.setValue(savedCode);
        }, 1000);
    }
    <?php if ($result_type === "success"): ?>
    localStorage.removeItem(storageKey);
    <?php endif; ?>
});

document.getElementById('lesson-form').addEventListener('submit', function(e) {
    document.getElementById('code-textarea').value = editor.getValue();
});
</script>
